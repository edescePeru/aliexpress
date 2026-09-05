<?php

namespace App\Services;

use App\Customer;
use Illuminate\Support\Facades\Http;

class DecolectaService
{
    protected $token;
    protected $baseUrl;

    public function __construct()
    {
        $this->token = config('services.decolecta.token');
        $this->baseUrl = config('services.decolecta.url');

        if (!$this->token) {
            throw new \Exception('No se ha configurado el token de Decolecta.');
        }

        if (!$this->baseUrl) {
            throw new \Exception('No se ha configurado la URL base de Decolecta.');
        }
    }

    public function consultarDni($dni)
    {
        $dni = preg_replace('/\D/', '', $dni);

        if (!preg_match('/^\d{8}$/', $dni)) {
            throw new \Exception('El DNI debe tener exactamente 8 dígitos.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->get($this->baseUrl . '/reniec/dni', [
            'numero' => $dni,
        ]);

        if (!$response->successful()) {
            $message = $response->json()['error'] ?? 'No se pudo consultar el DNI.';
            throw new \Exception($message);
        }

        $data = $response->json();

        return [
            'numero_documento' => $data['document_number'] ?? $dni,
            'nombre' => $data['full_name'] ?? null,
            'nombres' => $data['first_name'] ?? null,
            'apellido_paterno' => $data['first_last_name'] ?? null,
            'apellido_materno' => $data['second_last_name'] ?? null,
            'direccion' => null,
            'location' => null,
            'source' => 'api',
        ];
    }

    public function consultarRuc($ruc)
    {
        $ruc = preg_replace('/\D/', '', $ruc);

        if (!preg_match('/^\d{11}$/', $ruc)) {
            throw new \Exception('El RUC debe tener exactamente 11 dígitos.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->get($this->baseUrl . '/sunat/ruc', [
            'numero' => $ruc,
        ]);

        if (!$response->successful()) {
            $message = $response->json()['message'] ?? 'No se pudo consultar el RUC.';
            throw new \Exception($message);
        }

        $data = $response->json();

        return [
            'numero_documento' => $data['numero_documento'] ?? $ruc,
            'nombre' => $data['razon_social'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'departamento' => $data['departamento'] ?? null,
            'provincia' => $data['provincia'] ?? null,
            'location' => trim(($data['provincia'] ?? '') . ' - ' . ($data['departamento'] ?? ''), ' -'),
            'source' => 'api',
        ];
    }

    public function buscarClienteODni($dni)
    {
        $dni = preg_replace('/\D/', '', $dni);

        $customer = Customer::where('RUC', $dni)->first();

        if ($customer) {
            return [
                'numero_documento' => $customer->RUC,
                'nombre' => $customer->business_name,
                'direccion' => $customer->address,
                'location' => $customer->location,
                'source' => 'database',
            ];
        }

        return $this->consultarDni($dni);
    }

    public function buscarClienteORuc($ruc)
    {
        $ruc = preg_replace('/\D/', '', $ruc);

        $customer = Customer::where('RUC', $ruc)->first();

        if ($customer) {
            return [
                'numero_documento' => $customer->RUC,
                'nombre' => $customer->business_name,
                'direccion' => $customer->address,
                'location' => $customer->location,
                'source' => 'database',
            ];
        }

        return $this->consultarRuc($ruc);
    }

    public function buscarOCrearClientePorDocumento($numeroDocumento)
    {
        $numeroDocumento = preg_replace(
            '/\D/',
            '',
            $numeroDocumento
        );

        if (!preg_match(
            '/^\d{8}$|^\d{11}$/',
            $numeroDocumento
        )) {
            throw new \Exception(
                'El documento debe ser un DNI de 8 dígitos o un RUC de 11 dígitos.'
            );
        }


        /*
         * ============================================================
         * BUSCAR EN EL TENANT ACTUAL
         * ============================================================
         *
         * Customer tiene BelongsToTenant/TenantScope.
         *
         * Por tanto, nunca encontrará un Customer perteneciente
         * a otro Tenant aunque tenga el mismo documento.
         */
        $customer = Customer::query()
            ->where(
                'RUC',
                $numeroDocumento
            )
            ->first();


        if ($customer) {
            return [
                'customer' =>
                    $customer,

                'source' =>
                    'database',

                'created' =>
                    false,
            ];
        }


        /*
         * ============================================================
         * CONSULTAR DOCUMENTO
         * ============================================================
         */

        if (strlen($numeroDocumento) === 8) {

            $data =
                $this->consultarDni(
                    $numeroDocumento
                );

            $customer = Customer::create([
                /*
                 * tenant_id será asignado automáticamente
                 * por BelongsToTenant.
                 */
                'business_name' =>
                    $data['nombre'],

                'RUC' =>
                    $data['numero_documento'],

                'address' =>
                    null,

                'location' =>
                    null,

                /*
                 * DNI/RUC consultado en RENIEC/SUNAT:
                 * cliente nacional.
                 */
                'special' =>
                    false,
            ]);

        } else {

            $data =
                $this->consultarRuc(
                    $numeroDocumento
                );

            $customer = Customer::create([
                'business_name' =>
                    $data['nombre'],

                'RUC' =>
                    $data['numero_documento'],

                'address' =>
                    $data['direccion'],

                'location' =>
                    $data['location'],

                'special' =>
                    false,
            ]);
        }


        /*
         * ============================================================
         * GENERAR CÓDIGO CON EL ID REAL
         * ============================================================
         *
         * Evitamos calcularlo utilizando "último Customer + 1".
         */

        $customer->code =
            'C-' .
            str_pad(
                $customer->id,
                5,
                '0',
                STR_PAD_LEFT
            );

        $customer->save();


        return [
            'customer' =>
                $customer->fresh(),

            'source' =>
                'api',

            'created' =>
                true,
        ];
    }

    public function consultarDocumentoEnDecolecta($numeroDocumento)
    {
        $numeroDocumento = preg_replace('/\D/', '', $numeroDocumento);

        if (!preg_match('/^\d{8}$|^\d{11}$/', $numeroDocumento)) {
            throw new \Exception('El documento debe ser un DNI de 8 dígitos o un RUC de 11 dígitos.');
        }

        if (strlen($numeroDocumento) === 8) {

            $data = $this->consultarDni($numeroDocumento);

            return [
                'customer' => [
                    'id'            => null,
                    'business_name' => $data['nombre'],
                    'RUC'           => $data['numero_documento'],
                    'address'       => null,
                    'location'      => null,
                ],
                'source'  => 'api',
                'created' => false,
            ];
        }

        $data = $this->consultarRuc($numeroDocumento);

        return [
            'customer' => [
                'id'            => null,
                'business_name' => $data['nombre'],
                'RUC'           => $data['numero_documento'],
                'address'       => $data['direccion'],
                'location'      => $data['location'],
            ],
            'source'  => 'api',
            'created' => false,
        ];
    }
}