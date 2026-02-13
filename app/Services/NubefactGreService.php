<?php

namespace App\Services;

use App\ShippingGuide;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class NubefactGreService
{
    /**
     * @param ShippingGuide $guide
     * @return array
     */
    public function send(ShippingGuide $guide): array
    {
        $payload = $this->buildPayload($guide);

        $url = config('services.nubefact.url');
        $token = config('services.nubefact.token');

        if (!$url || !$token) {
            throw new \RuntimeException('Config Nubefact incompleta (NUBEFACT_URL / NUBEFACT_TOKEN).');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Token token=' . $token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->timeout(30)->post($url, $payload);

        if (!$response->ok()) {
            throw new \RuntimeException('Error HTTP Nubefact: ' . $response->status() . ' - ' . $response->body());
        }

        return $response->json();
    }

    /**
     * @param string $serie
     * @param mixed  $numero
     * @param int    $tipoDeComprobante
     * @return array
     */

    public function consult(string $serie, $numero, int $tipoDeComprobante): array
    {
        $payload = [
            'operacion' => 'consultar_guia',
            'tipo_de_comprobante' => $tipoDeComprobante,
            'serie' => $serie,
            'numero' => (string)$numero,
        ];

        $url = config('services.nubefact.url');
        $token = config('services.nubefact.token');

        $response = Http::withHeaders([
            'Authorization' => 'Token token=' . $token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->timeout(30)->post($url, $payload);

        if (!$response->ok()) {
            throw new \RuntimeException('Error HTTP Nubefact (consultar): ' . $response->status() . ' - ' . $response->body());
        }

        return $response->json();
    }

    /**
     * @param ShippingGuide $guide
     * @return array
     */
    public function buildPayload(ShippingGuide $guide): array
    {
        // Solo MVP: remitente (7) / transportista (8)
        $tipo = (int)$guide->tipo_de_comprobante;

        $items = $guide->items->map(function ($it) {
            return [
                'unidad_de_medida' => $it->unidad_medida ?: 'NIU',
                'codigo' => $it->codigo ?: '',
                'descripcion' => $it->descripcion,
                'cantidad' => (string)$it->cantidad,
                // Nubefact no exige "detalle_adicional" en su ejemplo,
                // pero si luego lo necesitas, se agrega aquí si el contrato lo soporta.
            ];
        })->values()->all();

        $payload = [
            'operacion' => 'generar_guia',
            'tipo_de_comprobante' => $tipo,
            'serie' => $guide->serie,
            'numero' => (string)$guide->numero,

            // En Remitente, Nubefact llama cliente_* al DESTINATARIO
            'cliente_tipo_de_documento' => (string)$guide->customer_doc_type,
            'cliente_numero_de_documento' => (string)$guide->customer_doc_number,
            'cliente_denominacion' => (string)$guide->customer_name,
            'cliente_direccion' => (string)($guide->customer_address ?? ''),
            'cliente_email' => (string)($guide->customer_email ?? ''),
            'cliente_email_1' => (string)($guide->customer_email_1 ?? ''),
            'cliente_email_2' => (string)($guide->customer_email_2 ?? ''),

            'fecha_de_emision' => $guide->fecha_emision->format('d-m-Y'),
            'observaciones' => (string)($guide->observaciones ?? ''),

            // Traslado
            'motivo_de_traslado' => (string)$guide->motivo_traslado_code,
            'peso_bruto_total' => (string)$guide->peso_bruto_total,
            'peso_bruto_unidad_de_medida' => (string)$guide->peso_bruto_um_code,
            'numero_de_bultos' => (string)$guide->numero_bultos,
            'tipo_de_transporte' => (string)$guide->tipo_transporte,
            'fecha_de_inicio_de_traslado' => $guide->fecha_inicio_traslado->format('d-m-Y'),

            // Partida
            'punto_de_partida_ubigeo' => (string)$guide->partida_ubigeo,
            'punto_de_partida_direccion' => (string)$guide->partida_direccion,
            'punto_de_partida_codigo_establecimiento_sunat' => (string)$guide->partida_cod_establecimiento,

            // Llegada
            'punto_de_llegada_ubigeo' => (string)$guide->llegada_ubigeo,
            'punto_de_llegada_direccion' => (string)$guide->llegada_direccion,
            'punto_de_llegada_codigo_establecimiento_sunat' => (string)$guide->llegada_cod_establecimiento,

            'enviar_automaticamente_al_cliente' => 'false',
            'formato_de_pdf' => '',

            'items' => $items,
        ];

        // Indicador envío SUNAT (opcional)
        if (!empty($guide->sunat_shipping_indicator_code)) {
            $payload['indicador_envio_sunat'] = $guide->sunat_shipping_indicator_code;
        }

        // MTC (opcional)
        if (!empty($guide->mtc_registration_number)) {
            $payload['numero_de_registro_mtc'] = $guide->mtc_registration_number;
        }

        // Transporte público: transportista empresa
        if ($guide->tipo_transporte === '01') {
            $payload['transportista_documento_tipo'] = (string)($guide->transportista_doc_type ?? '6');
            $payload['transportista_documento_numero'] = (string)($guide->transportista_doc_number ?? '');
            $payload['transportista_denominacion'] = (string)($guide->transportista_name ?? '');

            // Vehículo principal (en Nubefact ejemplo está aquí)
            $primaryVehicle = $guide->vehicles->firstWhere('is_primary', true);
            if ($primaryVehicle) {
                $payload['transportista_placa_numero'] = $primaryVehicle->plate_number;
            }
        }

        // Transporte privado: vehículos + conductor(s)
        if ($guide->tipo_transporte === '02') {
            $primaryVehicle = $guide->vehicles->firstWhere('is_primary', true);
            if ($primaryVehicle) {
                $payload['transportista_placa_numero'] = $primaryVehicle->plate_number;
            }

            $primaryDriver = $guide->drivers->firstWhere('is_primary', true);
            if ($primaryDriver) {
                $payload['conductor_documento_tipo'] = (string)$primaryDriver->document_type_code;
                $payload['conductor_documento_numero'] = (string)$primaryDriver->document_number;
                $payload['conductor_nombre'] = (string)$primaryDriver->first_name;
                $payload['conductor_apellidos'] = (string)$primaryDriver->last_name;
                $payload['conductor_numero_licencia'] = (string)$primaryDriver->license_number;
            }

            // Secundarios (máx 2 en UI, aquí no limitamos)
            $secVehicles = $guide->vehicles->where('is_primary', false)->values();
            if ($secVehicles->count() > 0) {
                $payload['vehiculos_secundarios'] = $secVehicles->map(function ($v) {
                    return [
                        'placa_numero' => $v->plate_number,
                    ];
                })->all();
            }

            $secDrivers = $guide->drivers->where('is_primary', false)->values();
            if ($secDrivers->count() > 0) {
                $payload['conductores_secundarios'] = $secDrivers->map(function ($d) {
                    return [
                        'documento_tipo' => (string)$d->document_type_code,
                        'documento_numero' => (string)$d->document_number,
                        'nombre' => (string)$d->first_name,
                        'apellidos' => (string)$d->last_name,
                        'numero_licencia' => (string)$d->license_number,
                    ];
                })->all();
            }
        }

        return $payload;
    }
}
