<?php

namespace App\Http\Controllers;

use App\Sale;
use App\Services\NubefactCompanyConfigService;
use App\Services\NubefactCompanyConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class NubefactController extends Controller
{
    public function generarComprobante(Request $request)
    {
        // Validación básica
        $request->validate([
            'order_id' => 'required|exists:sales,id',
        ]);

        $order = Sale::findOrFail($request->order_id);

        if (!$order->type_document) {
            return response()->json(['message' => 'El tipo de comprobante no está definido.'], 422);
        }

        // Armamos el JSON para Nubefact
        $connection = app(NubefactCompanyConfigService::class)->forSale($order);
        $data = $this->buildNubefactData($order, $connection);

        /*dump($data);
        dd();*/

        // Token y URL de Nubefact desde .env
        $token = $connection->token();
        $url = $connection->url();

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Token token=' . $token,
                'Content-Type' => 'application/json',
            ])->post($url, $data);

            $result = $response->json();

            if (isset($result['errors'])) {
                return response()->json([
                    'message' => 'Error desde Nubefact: ' . $result['errors']
                ], 500);
            }

            // Definir nombre base de archivo
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

            // Guardar solo los nombres de archivo
            $order->update([
                'serie_sunat'   => isset($result['serie']) ? $result['serie'] : null,
                'numero'        => isset($result['numero']) ? $result['numero'] : null,
                'sunat_ticket'  => isset($result['sunat_ticket']) ? $result['sunat_ticket'] : null,
                'sunat_status'  => isset($result['sunat_description']) ? $result['sunat_description'] : 'Enviado',
                'sunat_message' => isset($result['sunat_note']) ? $result['sunat_note'] : '',
                'xml_path'      => file_exists(public_path('comprobantes/xmls/' . $xmlFilename)) ? $xmlFilename : null,
                'cdr_path'      => file_exists(public_path('comprobantes/cdrs/' . $cdrFilename)) ? $cdrFilename : null,
                'pdf_path'      => file_exists(public_path('comprobantes/pdfs/' . $pdfFilename)) ? $pdfFilename : null,
                'fecha_emision' => now()->toDateString(),
            ]);

            return response()->json([
                'message' => 'Comprobante generado correctamente.',
                'pdf_url' => isset($result['enlace_del_pdf']) ? $result['enlace_del_pdf'] : null,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al conectar con Nubefact: ' . $e->getMessage()
            ], 500);
        }
    }

    private function buildNubefactData(Sale $order, NubefactCompanyConnection $connection)
    {
        $isFactura = $order->type_document === '01';
        $serie = $connection->saleSeries($order->type_document);
        $tipoCliente = $order->tipo_documento_cliente ?: ($isFactura ? '6' : '1');

        $discount = round($order->total_descuentos, 2);

        $items = $order->details->map(function ($item) {
            $valor_unitario = round($item->price / 1.18, 6);
            $subtotal = $valor_unitario * $item->quantity; // 20.25
            $igv = ($item->price * $item->quantity) - $subtotal; // 23.90 - 20.25 = 3.65

            return [
                "unidad_de_medida" => "NIU",
                "codigo" => $item->material_id,
                "descripcion" => $item->material->full_name,
                "cantidad" => $item->quantity,
                "valor_unitario" => round($valor_unitario, 6),
                "precio_unitario" => round($item->price, 6),
                "subtotal" => round($subtotal, 6), // 34.66
                "tipo_de_igv" => "1", // gravado
                "igv" => round($igv, 2), // 6.24
                "total" => round($item->price*$item->quantity, 6) // 40.90
            ];
        })->toArray();


        $total_gravada = array_sum(array_column($items, 'subtotal'));
        /*$total_igv = array_sum(array_column($items, 'igv'));
        $total = array_sum(array_column($items, 'total'));*/

        return [
                "operacion" => "generar_comprobante",
                "tipo_de_comprobante" => $isFactura ? "1" : "2",
                "serie" => $serie,
                "numero" => "", // Nubefact lo autogenera si está vacío
                "codigo_unico" => (string) Str::uuid(),
                "sunat_transaction" => "1",
                "cliente_tipo_de_documento" => $tipoCliente,
                "cliente_numero_de_documento" => $order->numero_documento_cliente,
                "cliente_denominacion" => $order->nombre_cliente,
                "cliente_direccion" => $order->direccion_cliente ?: "",
                "cliente_email" => $order->email_cliente ?: "",
                "fecha_de_emision" => now()->format('d-m-Y'),
                "moneda" => "1", // soles
                "porcentaje_de_igv" => 18.00,
                "total_gravada" => number_format($total_gravada - $discount, 2, '.', ''),
                "total_igv" => number_format(($total_gravada - $discount) * 0.18, 2, '.', ''),
                "total" => number_format(($total_gravada - $discount) * 1.18, 2, '.', ''),
                "total_a_pagar" => number_format(($total_gravada - $discount) * 1.18, 2, '.', ''),
            ] + ($discount > 0 ? [
                "descuento_global" => number_format($discount, 2, '.', ''),
                "total_descuento" => number_format($discount, 2, '.', '')
            ] : []) + [
                "items" => $items,
            ];
    }

    public function generarRecibo()
    {
        $order = Order::find(107);

        $userCoupon = UserCoupon::where('order_id', $order->id)->first();
        $discount = $userCoupon ? round($userCoupon->discount_amount, 2) : 0;

        $amount_shipping = round($order->amount_shipping, 2);

        $items = $order->details->map(function ($item) {
            $valor_unitario = round($item->price / 1.18, 6);
            $subtotal = $valor_unitario * $item->quantity; // 20.25
            $igv = ($item->price * $item->quantity) - $subtotal; // 23.90 - 20.25 = 3.65

            return [
                "unidad_de_medida" => "NIU",
                "codigo" => $item->product_id,
                "descripcion" => $item->product->full_name,
                "cantidad" => $item->quantity,
                "valor_unitario" => round($valor_unitario, 6),
                "precio_unitario" => round($item->price, 6),
                "subtotal" => round($subtotal, 6), // 34.66
                "tipo_de_igv" => "1", // gravado
                "igv" => round($igv, 2), // 6.24
                "total" => round($item->price*$item->quantity, 6) // 40.90
            ];
        })->toArray();

        if ($amount_shipping > 0) {
            $valor_unitario_envio = round($amount_shipping / 1.18, 6);
            $igv_envio = round($amount_shipping - $valor_unitario_envio, 6);

            $items[] = [
                "unidad_de_medida" => "NIU",
                "codigo" => "ENVIO",
                "descripcion" => "Costo de envío",
                "cantidad" => 1,
                "valor_unitario" => $valor_unitario_envio,
                "precio_unitario" => $amount_shipping,
                "subtotal" => $valor_unitario_envio,
                "tipo_de_igv" => "1",
                "igv" => $igv_envio,
                "total" => $amount_shipping
            ];
        }

        $total_gravada = array_sum(array_column($items, 'subtotal'));

        $total_gravada_final = number_format($total_gravada - $discount, 2, '.', '');
        $total_igv = number_format(($total_gravada - $discount) * 0.18, 2, '.', '');
        $total = number_format(($total_gravada - $discount) * 1.18, 2, '.', '');
        $total_a_pagar = number_format(($total_gravada - $discount) * 1.18, 2, '.', '');

        return [
                "total_gravada" => number_format($total_gravada - $discount, 2, '.', ''),
                "total_igv" => number_format(($total_gravada - $discount) * 0.18, 2, '.', ''),
                "total" => number_format(($total_gravada - $discount) * 1.18, 2, '.', ''),
                "total_a_pagar" => number_format(($total_gravada - $discount) * 1.18, 2, '.', ''),
            ] + ($discount > 0 ? [
                "descuento_global" => number_format($discount, 2, '.', ''),
                "total_descuento" => number_format($discount, 2, '.', '')
            ] : []) + [
                "items" => $items,
            ];

    }
}
