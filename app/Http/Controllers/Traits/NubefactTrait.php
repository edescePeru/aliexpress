<?php

namespace App\Http\Controllers\Traits;

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

}
