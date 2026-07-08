<?php

namespace App\Http\Controllers\Traits;

use App\CreditNote;
use App\Sale;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

trait NubefactTrait
{
    private function buildNubefactData(Sale $order): array
    {
        $order->loadMissing([
            'details.material',
            'details.stockItem']);

        $isFactura = $order->type_document === '01';
        $serie = $isFactura ? 'FFF1' : 'BBB1';
        /*$serie = $isFactura
            ? config('services.nubefact.serie_factura', 'FFF1')
            : config('services.nubefact.serie_boleta', 'BBB1');*/
        $tipoCliente = $order->tipo_documento_cliente ?: ($isFactura ? '6' : '1');

        $items = $order->details->map(function ($item) {

            // 1) Determinar si es servicio (workforce) o producto
            $isService = empty($item->material_id); // null => servicio

            $qty = (string) ((float)$item->quantity);
            if ((float)$qty <= 0) {
                throw new \Exception("Cantidad inválida en detalle {$item->id}");
            }

            $qty = (string) ( ($item->material_presentation_id == null) ? (float)$item->quantity: (float)$item->packs);

            // ✅ TOTAL DE LÍNEA (con IGV) desde tu BD, no recalcular con price*qty
            //$totalLine = number_format((float)$item->total, 2, '.', ''); // "115.00"
            $totalLine = $item->total;

            // ✅ precio_unitario = total / qty (6 decimales)
            //$precioUnitario = bcdiv($totalLine, $qty, 10); // "38.333333"
            $precioUnitario = $item->price;

            // ✅ valor_unitario = precio_unitario / 1.18
            //$valorUnitario  = bcdiv($precioUnitario, '1.18', 10);
            $valorUnitario = $item->valor_unitario;

            // ✅ subtotal = valor_unitario * qty (2 decimales para dinero)
            $subtotal = bcmul($valorUnitario, $qty, 10); // "97.46" (ej)

            // ✅ igv = total - subtotal (2 decimales)
            $igv = bcsub($totalLine, $subtotal, 10);

            //$present = (string) ( ($item->material_presentation_id == null) ? round($item->quantity): $item->units_per_pack.'und');

            if ($isService) {
                $descripcion = strtoupper($item->description) ?: 'Servicio';
                $present = 'srv';
            } else {
                $present = (string) (
                $item->material_presentation_id == null
                    ? round($item->quantity)
                    : $item->units_per_pack . 'und'
                );

                $descripcion = "(".$present.") ";

                if (!empty($item->stock_item_id) && $item->stockItem) {
                    $descripcion .= $item->stockItem->display_name;
                } elseif ($item->material) {
                    $descripcion .= $item->material->full_name;
                } else {
                    $descripcion .= 'Material ' . $item->material_id;
                }

                /*
 * Agregar códigos de ítems físicos vendidos.
 * Se usa item_snapshot para no depender de OutputDetail.
 */
                $itemSnapshot = $item->item_snapshot ?? [];

                if (is_string($itemSnapshot)) {
                    $decodedSnapshot = json_decode($itemSnapshot, true);
                    $itemSnapshot = is_array($decodedSnapshot) ? $decodedSnapshot : [];
                }

                if (!is_array($itemSnapshot)) {
                    $itemSnapshot = [];
                }

                $itemCodes = collect($itemSnapshot)
                    ->map(function ($snapshotItem) {
                        if (is_array($snapshotItem)) {
                            return $snapshotItem['code'] ?? null;
                        }

                        if (is_object($snapshotItem)) {
                            return $snapshotItem->code ?? null;
                        }

                        return null;
                    })
                    ->filter()
                    ->values()
                    ->toArray();

                if (!empty($itemCodes)) {
                    $descripcion .= ' | Items: ' . implode(', ', $itemCodes);
                }
            }

            return [
                "unidad_de_medida" => "NIU",
                "codigo" => "",
                "descripcion" => $descripcion,
                "cantidad" => (float) $qty,

                // Nubefact usa estos para recalcular/mostrar:
                "valor_unitario" => (float) $valorUnitario,
                "precio_unitario" => (float) $precioUnitario,

                // Totales de línea consistentes:
                "subtotal" => (float) $subtotal,
                "tipo_de_igv" => "1",
                "igv" => (float) $igv,
                "total" => (float) $totalLine,
            ];
        })->toArray();

        // Total gravada = suma de subtotales (base imponible)
        // Suma con precisión (usa "subtotal" con 3+ decimales si lo tienes, o calcula raw)
        //$totalGravadaRaw = '0.000';

        /*foreach ($items as $it) {
            $subRaw = number_format((float)$it['valor_unitario'] * (float)$it['cantidad'], 10, '.', ''); // 3 decimales
            $totalGravadaRaw = bcadd($totalGravadaRaw, $subRaw, 10);
        }*/

        // base = total_gravada - descuento (ambos a 2 decimales)
        $discount = $order->total_descuentos;
        /*dump("discount");
        dump($discount);*/
        $total_gravada = $order->op_gravada;
        /*dump("total_gravada");
        dump($total_gravada);*/

        //$base = bcsub($total_gravada, 0, 10);
        $base = $total_gravada;
        /*dump("base");
        dump($base);*/

        $total_igv = bcmul($base, '0.18', 10);
        /*dump("total_igv");
        dump($total_igv);*/

        $total = bcadd($base, $total_igv, 10);

        /*dump("total");
        dump($total);*/

        return [
                "operacion" => "generar_comprobante",
                "tipo_de_comprobante" => $isFactura ? "1" : "2",
                "serie" => $serie,
                "numero" => "",
                "codigo_unico" => (string) Str::uuid(),
                "sunat_transaction" => "1",
                "cliente_tipo_de_documento" => $tipoCliente,
                "cliente_numero_de_documento" => $order->numero_documento_cliente,
                "cliente_denominacion" => $order->nombre_cliente,
                "cliente_direccion" => $order->direccion_cliente ?: "",
                "cliente_email" => $order->email_cliente ?: "",
                "fecha_de_emision" => now()->format('d-m-Y'),
                "moneda" => "1",
                "porcentaje_de_igv" => 18.00,
                "total_gravada" => $base,
                "total_igv" => $total_igv,
                "total" => $total,
                "total_a_pagar" => $total,
            ]
            + ($discount > 0 ? [
                "descuento_global" => number_format($discount, 10, '.', ''),
                "total_descuento" => number_format($discount, 10, '.', ''),
            ] : [])
            + [
                "items" => $items,
            ];
    }

    private function trunc2($value): string
    {
        // asegura string con decimales
        $s = (string) $value;

        if (strpos($s, '.') === false) {
            return $s . '.00';
        }

        [$int, $dec] = explode('.', $s, 2);
        $dec = substr($dec . '00', 0, 2); // completa y corta a 2
        return $int . '.' . $dec;
    }

    private function round2($value): string
    {
        return number_format(round((float)$value, 10, PHP_ROUND_HALF_UP), 10, '.', '');
    }

    private function generarComprobanteNubefactParaVenta(Sale $order): array
    {
        if (!$order->type_document) {
            throw new \Exception('El tipo de comprobante no está definido.');
        }

        $data = $this->buildNubefactData($order);
        /*dump("Nubefact data");
        dump($data);
        dd();*/

        /*$token = env('NUBEFACT_TOKEN');
        $url   = env('NUBEFACT_API_URL');*/
        $token = config('services.nubefact.token');
        $url   = config('services.nubefact.url');

        if (!$token || !$url) {
            throw new \Exception('Faltan credenciales Nubefact en .env (NUBEFACT_TOKEN / NUBEFACT_API_URL).');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Token token=' . $token,
            'Content-Type'  => 'application/json',
        ])->post($url, $data);

        $result = $response->json();

        if (!$response->ok()) {
            $msg = is_array($result) ? json_encode($result) : $response->body();
            throw new \Exception('Nubefact respondió error HTTP: ' . $msg);
        }

        if (isset($result['errors'])) {
            throw new \Exception('Error desde Nubefact: ' . $result['errors']);
        }

        return $result;
    }

    private function persistNubefactFilesAndUpdateSale(Sale $order, array $result): void
    {
        $filename = 'ORD' . $order->id;

        $pdfFilename = $filename . '.pdf';
        $xmlFilename = $filename . '.xml';
        $cdrFilename = $filename . '.zip';

        // Crear carpetas si no existen
        foreach (['pdfs', 'xmls', 'cdrs'] as $folder) {
            if (!file_exists(public_path("comprobantes/$folder"))) {
                mkdir(public_path("comprobantes/$folder"), 0777, true);
            }
        }

        // Descargar archivos desde Nubefact
        if (!empty($result['enlace_del_pdf'])) {
            $pdfContent = Http::get($result['enlace_del_pdf'])->body();
            file_put_contents(public_path('comprobantes/pdfs/' . $pdfFilename), $pdfContent);
        }

        if (!empty($result['enlace_del_xml'])) {
            $xmlContent = Http::get($result['enlace_del_xml'])->body();
            file_put_contents(public_path('comprobantes/xmls/' . $xmlFilename), $xmlContent);
        }

        if (!empty($result['enlace_del_cdr'])) {
            $cdrContent = Http::get($result['enlace_del_cdr'])->body();
            file_put_contents(public_path('comprobantes/cdrs/' . $cdrFilename), $cdrContent);
        }

        // Actualizar la venta con los nombres de archivo y estado SUNAT
        $order->update([
            'serie_sunat'   => $result['serie'] ?? null,
            'numero'        => $result['numero'] ?? null,
            'sunat_ticket'  => $result['sunat_ticket'] ?? null,
            'sunat_status'  => $result['sunat_description'] ?? 'Enviado',
            'sunat_message' => $result['sunat_note'] ?? '',
            'xml_path'      => file_exists(public_path('comprobantes/xmls/' . $xmlFilename)) ? $xmlFilename : null,
            'cdr_path'      => file_exists(public_path('comprobantes/cdrs/' . $cdrFilename)) ? $cdrFilename : null,
            'pdf_path'      => file_exists(public_path('comprobantes/pdfs/' . $pdfFilename)) ? $pdfFilename : null,
            'fecha_emision' => now()->toDateString(),
        ]);
    }

    private function buildNubefactVoidData(Sale $sale, string $motivo): array
    {
        if (!$sale->type_document || !in_array($sale->type_document, ['01', '03'], true)) {
            throw new \Exception('La venta no tiene factura o boleta electrónica para anular.');
        }

        if (empty($sale->serie_sunat) || empty($sale->numero)) {
            throw new \Exception('La venta no tiene serie o número SUNAT para anular.');
        }

        return [
            "operacion" => "generar_anulacion",
            "tipo_de_comprobante" => $sale->type_document === '01' ? "1" : "2",
            "serie" => $sale->serie_sunat,
            "numero" => (string) $sale->numero,
            "motivo" => $motivo ?: "Anulación de comprobante",
            "codigo_unico" => (string) Str::uuid(),
        ];
    }

    private function anularComprobanteNubefact(Sale $sale, string $motivo): array
    {
        $data = $this->buildNubefactVoidData($sale, $motivo);

        $token = config('services.nubefact.token');
        $url   = config('services.nubefact.url');

        if (!$token || !$url) {
            throw new \Exception('Faltan credenciales Nubefact en .env.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Token token=' . $token,
            'Content-Type'  => 'application/json',
        ])->post($url, $data);

        $result = $response->json();

        if (!$response->ok()) {
            $msg = is_array($result) ? json_encode($result) : $response->body();
            throw new \Exception('Nubefact respondió error HTTP al anular: ' . $msg);
        }

        if (isset($result['errors'])) {
            throw new \Exception('Error desde Nubefact al anular: ' . $result['errors']);
        }

        return $result;
    }

    private function persistNubefactAnnulmentResult(Sale $sale, array $result, string $motivo): void
    {
        $accepted = (bool) ($result['aceptada_por_sunat'] ?? false);

        $description = $result['sunat_description'] ?? null;
        $note = $result['sunat_note'] ?? null;
        $soapError = $result['sunat_soap_error'] ?? null;
        $responseCode = $result['sunat_responsecode'] ?? null;

        $ticket = $result['sunat_ticket_numero']
            ?? $result['sunat_ticket']
            ?? null;

        $key = $result['key'] ?? null;

        $pdfUrl = $result['enlace_del_pdf'] ?? null;
        $xmlUrl = $result['enlace_del_xml'] ?? null;
        $cdrUrl = $result['enlace_del_cdr'] ?? null;

        $filename = 'ANULACION_ORD' . $sale->id;

        $pdfFilename = $filename . '.pdf';
        $xmlFilename = $filename . '.xml';
        $cdrFilename = $filename . '.cdr';

        foreach ([
                     'anulaciones/pdfs',
                     'anulaciones/xmls',
                     'anulaciones/cdrs',
                 ] as $folder) {
            if (!file_exists(public_path("comprobantes/$folder"))) {
                mkdir(public_path("comprobantes/$folder"), 0777, true);
            }
        }

        if (!empty($pdfUrl)) {
            $pdfContent = Http::get($pdfUrl)->body();
            file_put_contents(public_path('comprobantes/anulaciones/pdfs/' . $pdfFilename), $pdfContent);
        }

        if (!empty($xmlUrl)) {
            $xmlContent = Http::get($xmlUrl)->body();
            file_put_contents(public_path('comprobantes/anulaciones/xmls/' . $xmlFilename), $xmlContent);
        }

        if (!empty($cdrUrl)) {
            $cdrContent = Http::get($cdrUrl)->body();
            file_put_contents(public_path('comprobantes/anulaciones/cdrs/' . $cdrFilename), $cdrContent);
        }

        $finalMessage = $soapError
            ?: ($note ?: ($description ?: null));

        $sale->annulment_response = json_encode($result, JSON_UNESCAPED_UNICODE);
        $sale->annulment_ticket = $ticket;
        $sale->annulment_key = $key;
        $sale->annulment_reason = $motivo;
        $sale->annulment_requested_at = $sale->annulment_requested_at ?: now();

        $sale->annulment_pdf_url = $pdfUrl;
        $sale->annulment_xml_url = $xmlUrl;
        $sale->annulment_cdr_url = $cdrUrl;

        $sale->annulment_pdf_path = file_exists(public_path('comprobantes/anulaciones/pdfs/' . $pdfFilename)) ? $pdfFilename : null;
        $sale->annulment_xml_path = file_exists(public_path('comprobantes/anulaciones/xmls/' . $xmlFilename)) ? $xmlFilename : null;
        $sale->annulment_cdr_path = file_exists(public_path('comprobantes/anulaciones/cdrs/' . $cdrFilename)) ? $cdrFilename : null;

        $sale->annulment_sunat_responsecode = $responseCode;

        if ($accepted) {
            $sale->annulment_status = 'accepted';
            $sale->annulment_accepted_at = now();
            $sale->annulment_sunat_status = 'Aceptado';
            $sale->annulment_sunat_message = $finalMessage ?: 'Anulación aceptada por SUNAT.';
            $sale->annulment_error = null;
        } elseif (!empty($soapError) || !empty($responseCode)) {
            $sale->annulment_status = 'rejected';
            $sale->annulment_sunat_status = 'Rechazado';
            $sale->annulment_sunat_message = $finalMessage ?: 'SUNAT rechazó la anulación.';
            $sale->annulment_error = $finalMessage ?: 'SUNAT rechazó la anulación.';
        } else {
            $sale->annulment_status = 'pending';
            $sale->annulment_sunat_status = 'Pendiente';
            $sale->annulment_sunat_message = $finalMessage ?: 'Anulación enviada a Nubefact. Pendiente de aceptación SUNAT.';
            $sale->annulment_error = null;
        }

        $sale->save();
    }

    private function buildNubefactConsultAnnulmentData(Sale $sale): array
    {
        if (!$sale->type_document || !in_array($sale->type_document, ['01', '03'], true)) {
            throw new \Exception('La venta no tiene factura o boleta electrónica para consultar anulación.');
        }

        if (empty($sale->serie_sunat) || empty($sale->numero)) {
            throw new \Exception('La venta no tiene serie o número SUNAT.');
        }

        return [
            "operacion" => "consultar_anulacion",
            "tipo_de_comprobante" => $sale->type_document === '01' ? 1 : 2,
            "serie" => $sale->serie_sunat,
            "numero" => (int) $sale->numero,
        ];
    }

    private function consultarAnulacionNubefact(Sale $sale): array
    {
        $data = $this->buildNubefactConsultAnnulmentData($sale);

        $token = config('services.nubefact.token');
        $url   = config('services.nubefact.url');

        if (!$token || !$url) {
            throw new \Exception('Faltan credenciales Nubefact.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Token token=' . $token,
            'Content-Type'  => 'application/json',
        ])->post($url, $data);

        $result = $response->json();

        if (!$response->ok()) {
            $msg = is_array($result) ? json_encode($result) : $response->body();
            throw new \Exception('Error HTTP consultando anulación en Nubefact: ' . $msg);
        }

        if (isset($result['errors'])) {
            throw new \Exception(
                is_array($result['errors'])
                    ? json_encode($result['errors'], JSON_UNESCAPED_UNICODE)
                    : $result['errors']
            );
        }

        return $result;
    }

    private function buildNubefactCreditNoteTotalData(Sale $sale, CreditNote $creditNote): array
    {
        $sale->loadMissing([
            'details.material',
            'details.stockItem'
        ]);

        $isFactura = $sale->type_document === '01';

        $serieNotaCredito = $isFactura
            ? config('services.nubefact.serie_nc_factura', 'FFF1')
            : config('services.nubefact.serie_nc_boleta', 'BBB1');

        $items = $sale->details->map(function ($item) {

            $qty = (float) $item->quantity;
            $subtotal = (float) $item->valor_unitario * $qty;
            $igv = (float) $item->total - $subtotal;

            if ($qty <= 0) {
                throw new \Exception("Cantidad inválida en detalle {$item->id}");
            }

            $descripcion = '';

            if (empty($item->material_id)) {
                $descripcion = strtoupper($item->description ?: 'Servicio');
            } else {
                if (!empty($item->stock_item_id) && $item->stockItem) {
                    $descripcion = $item->stockItem->display_name;
                } elseif ($item->material) {
                    $descripcion = $item->material->full_name;
                } else {
                    $descripcion = 'Material ' . $item->material_id;
                }
            }

            return [
                "unidad_de_medida" => "NIU",
                "codigo" => "",
                "descripcion" => $descripcion,
                "cantidad" => $qty,
                "valor_unitario" => (float) $item->valor_unitario,
                "precio_unitario" => (float) $item->price,
                "subtotal" => round($subtotal, 2),
                "tipo_de_igv" => "1",
                "igv" => round($igv, 2),
                "total" => round((float) $item->total, 2),
            ];
        })->toArray();

        return [
            "operacion" => "generar_comprobante",
            "tipo_de_comprobante" => "3", // Nota de crédito
            "serie" => $serieNotaCredito,
            "numero" => "",
            "codigo_unico" => 'NC-' . $sale->id . '-' . now()->timestamp . '-' . Str::random(8),
            "sunat_transaction" => "1",

            "cliente_tipo_de_documento" => $sale->tipo_documento_cliente,
            "cliente_numero_de_documento" => $sale->numero_documento_cliente,
            "cliente_denominacion" => $sale->nombre_cliente,
            "cliente_direccion" => $sale->direccion_cliente ?: "",
            "cliente_email" => $sale->email_cliente ?: "",

            "fecha_de_emision" => now()->format('d-m-Y'),
            "moneda" => "1",
            "porcentaje_de_igv" => 18.00,

            "tipo_de_nota_de_credito" => $creditNote->reason_code,
            "motivo_o_sustento_de_nota_de_credito" => $creditNote->reason_description,

            "documento_que_se_modifica_tipo" => $sale->type_document === '01' ? "1" : "2",
            "documento_que_se_modifica_serie" => $sale->serie_sunat,
            "documento_que_se_modifica_numero" => $sale->numero,

            "total_gravada" => (float) $sale->op_gravada,
            "total_igv" => (float) $sale->igv,
            "total" => (float) $sale->importe_total,
            "total_a_pagar" => (float) $sale->importe_total,

            "items" => $items,
        ];
    }

    private function generarNotaCreditoNubefact(Sale $sale, CreditNote $creditNote): array
    {
        $data = $this->buildNubefactCreditNoteTotalData($sale, $creditNote);

        //dd($data);

        $token = config('services.nubefact.token');
        $url = config('services.nubefact.url');

        if (!$token || !$url) {
            throw new \Exception('Faltan credenciales Nubefact.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Token token=' . $token,
            'Content-Type' => 'application/json',
        ])->post($url, $data);

        $result = $response->json();

        if (!$response->ok()) {
            $msg = is_array($result) ? json_encode($result) : $response->body();
            throw new \Exception('Nubefact respondió error HTTP al generar Nota de Crédito: ' . $msg);
        }

        if (isset($result['errors'])) {
            throw new \Exception('Error desde Nubefact: ' . $result['errors']);
        }

        return $result;
    }

    private function persistNubefactCreditNoteResult(CreditNote $creditNote, array $result): void
    {
        $accepted = (bool) ($result['aceptada_por_sunat'] ?? false);

        $description = $result['sunat_description'] ?? null;
        $note = $result['sunat_note'] ?? null;
        $soapError = $result['sunat_soap_error'] ?? null;
        $responseCode = $result['sunat_responsecode'] ?? null;

        $pdfUrl = $result['enlace_del_pdf'] ?? null;
        $xmlUrl = $result['enlace_del_xml'] ?? null;
        $cdrUrl = $result['enlace_del_cdr'] ?? null;

        $filename = 'NC_' . $creditNote->id;

        $pdfFilename = $filename . '.pdf';
        $xmlFilename = $filename . '.xml';
        $cdrFilename = $filename . '.zip';

        foreach ([
                     'notas_credito/pdfs',
                     'notas_credito/xmls',
                     'notas_credito/cdrs',
                 ] as $folder) {
            if (!file_exists(public_path("comprobantes/$folder"))) {
                mkdir(public_path("comprobantes/$folder"), 0777, true);
            }
        }

        if (!empty($pdfUrl)) {
            $pdfContent = Http::get($pdfUrl)->body();
            file_put_contents(public_path('comprobantes/notas_credito/pdfs/' . $pdfFilename), $pdfContent);
        }

        if (!empty($xmlUrl)) {
            $xmlContent = Http::get($xmlUrl)->body();
            file_put_contents(public_path('comprobantes/notas_credito/xmls/' . $xmlFilename), $xmlContent);
        }

        if (!empty($cdrUrl)) {
            $cdrContent = Http::get($cdrUrl)->body();
            file_put_contents(public_path('comprobantes/notas_credito/cdrs/' . $cdrFilename), $cdrContent);
        }

        $finalMessage = $soapError ?: ($note ?: ($description ?: null));

        $creditNote->serie = $result['serie'] ?? $creditNote->serie;
        $creditNote->numero = $result['numero'] ?? $creditNote->numero;
        $creditNote->sunat_ticket = $result['sunat_ticket'] ?? null;
        $creditNote->nubefact_key = $result['key'] ?? null;
        $creditNote->nubefact_response = json_encode($result, JSON_UNESCAPED_UNICODE);

        $creditNote->pdf_url = $pdfUrl;
        $creditNote->xml_url = $xmlUrl;
        $creditNote->cdr_url = $cdrUrl;

        $creditNote->pdf_path = file_exists(public_path('comprobantes/notas_credito/pdfs/' . $pdfFilename)) ? $pdfFilename : null;
        $creditNote->xml_path = file_exists(public_path('comprobantes/notas_credito/xmls/' . $xmlFilename)) ? $xmlFilename : null;
        $creditNote->cdr_path = file_exists(public_path('comprobantes/notas_credito/cdrs/' . $cdrFilename)) ? $cdrFilename : null;

        if ($accepted) {
            $creditNote->status = 'accepted';
            $creditNote->accepted_at = now();
            $creditNote->sunat_status = 'Aceptado';
            $creditNote->sunat_message = $finalMessage ?: 'Nota de Crédito aceptada por SUNAT.';
        } elseif (!empty($soapError) || (!empty($responseCode) && $responseCode !== '0')) {
            $creditNote->status = 'rejected';
            $creditNote->sunat_status = 'Rechazado';
            $creditNote->sunat_message = $finalMessage ?: 'SUNAT rechazó la Nota de Crédito.';
        } else {
            $creditNote->status = 'pending';
            $creditNote->sunat_status = 'Pendiente';
            $creditNote->sunat_message = $finalMessage ?: 'Nota de Crédito enviada a Nubefact. Pendiente de aceptación SUNAT.';
        }

        $creditNote->save();
    }

    private function buildNubefactConsultCreditNoteData(CreditNote $creditNote): array
    {
        if (empty($creditNote->serie) || empty($creditNote->numero)) {
            throw new \Exception('La Nota de Crédito no tiene serie o número para consultar.');
        }

        return [
            "operacion" => "consultar_comprobante",
            "tipo_de_comprobante" => 3,
            "serie" => $creditNote->serie,
            "numero" => (int) $creditNote->numero,
        ];
    }

    private function consultarNotaCreditoNubefact(CreditNote $creditNote): array
    {
        $data = $this->buildNubefactConsultCreditNoteData($creditNote);

        //dd($data);

        $token = config('services.nubefact.token');
        $url   = config('services.nubefact.url');

        if (!$token || !$url) {
            throw new \Exception('Faltan credenciales Nubefact.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Token token=' . $token,
            'Content-Type'  => 'application/json',
        ])->post($url, $data);

        $result = $response->json();

        if (!$response->ok()) {
            $msg = is_array($result) ? json_encode($result) : $response->body();
            throw new \Exception('Error HTTP consultando Nota de Crédito en Nubefact: ' . $msg);
        }

        if (isset($result['errors'])) {
            throw new \Exception(
                is_array($result['errors'])
                    ? json_encode($result['errors'], JSON_UNESCAPED_UNICODE)
                    : $result['errors']
            );
        }

        return $result;
    }

    private function generarNotaCreditoParcialNubefact(Sale $sale, CreditNote $creditNote): array
    {
        $data = $this->buildNubefactCreditNotePartialData($sale, $creditNote);

        $token = config('services.nubefact.token');
        $url   = config('services.nubefact.url');

        if (!$token || !$url) {
            throw new \Exception('Faltan credenciales Nubefact.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Token token=' . $token,
            'Content-Type'  => 'application/json',
        ])->post($url, $data);

        $result = $response->json();

        if (!$response->ok()) {
            $msg = is_array($result)
                ? json_encode($result, JSON_UNESCAPED_UNICODE)
                : $response->body();

            throw new \Exception('Nubefact respondió error HTTP al generar Nota de Crédito parcial: ' . $msg);
        }

        if (isset($result['errors'])) {
            throw new \Exception(
                is_array($result['errors'])
                    ? json_encode($result['errors'], JSON_UNESCAPED_UNICODE)
                    : $result['errors']
            );
        }

        return $result;
    }

    private function buildNubefactCreditNotePartialData(Sale $sale, CreditNote $creditNote): array
    {
        $creditNote->loadMissing(['details']);

        if ($creditNote->details->isEmpty()) {
            throw new \Exception('La Nota de Crédito parcial no tiene detalles.');
        }

        $isFactura = $sale->type_document === '01';

        $serieNotaCredito = $isFactura
            ? config('services.nubefact.serie_nc_factura', 'FFF1')
            : config('services.nubefact.serie_nc_boleta', 'BBB1');

        $items = $creditNote->details->map(function ($detail) {
            return [
                "unidad_de_medida" => "NIU",
                "codigo" => "",
                "descripcion" => $detail->description,
                "cantidad" => (float) $detail->quantity,
                "valor_unitario" => (float) $detail->valor_unitario,
                "precio_unitario" => (float) $detail->price,
                "subtotal" => (float) $detail->subtotal,
                "tipo_de_igv" => "1",
                "igv" => (float) $detail->igv,
                "total" => (float) $detail->total,
            ];
        })->toArray();

        return [
            "operacion" => "generar_comprobante",
            "tipo_de_comprobante" => "3",
            "serie" => $serieNotaCredito,
            "numero" => "",
            "codigo_unico" => 'NC-PARCIAL-' . $sale->id . '-' . now()->timestamp . '-' . \Illuminate\Support\Str::random(8),

            "sunat_transaction" => "1",

            "cliente_tipo_de_documento" => $sale->tipo_documento_cliente,
            "cliente_numero_de_documento" => $sale->numero_documento_cliente,
            "cliente_denominacion" => $sale->nombre_cliente,
            "cliente_direccion" => $sale->direccion_cliente ?: "",
            "cliente_email" => $sale->email_cliente ?: "",

            "fecha_de_emision" => now()->format('d-m-Y'),
            "moneda" => "1",
            "porcentaje_de_igv" => 18.00,

            "tipo_de_nota_de_credito" => $creditNote->reason_code ?: "07",
            "motivo_o_sustento_de_nota_de_credito" => $creditNote->reason_description ?: "Devolución parcial",

            "documento_que_se_modifica_tipo" => $sale->type_document === '01' ? "1" : "2",
            "documento_que_se_modifica_serie" => $sale->serie_sunat,
            "documento_que_se_modifica_numero" => $sale->numero,

            "total_gravada" => (float) $creditNote->op_gravada,
            "total_igv" => (float) $creditNote->igv,
            "total" => (float) $creditNote->importe_total,
            "total_a_pagar" => (float) $creditNote->importe_total,

            "items" => $items,
        ];
    }
}
