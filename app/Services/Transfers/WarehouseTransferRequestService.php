<?php

namespace App\Services\Transfers;

use App\Support\TenantContext;
use App\Transfers\WarehouseTransfer;
use App\Transfers\WarehouseTransferLine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WarehouseTransferRequestService
{
    // Servicio interno: el futuro controlador debe verificar permisos de la operación.
    public function create(array $data): WarehouseTransfer
    {
        $payload = $this->normalize($data);
        $tenant = TenantContext::tenantId();
        $company = TenantContext::companyId();
        $actor = Auth::id();
        if (!$actor || !DB::table('users')->where('id', $actor)->where('tenant_id', $tenant)->exists()) {
            $this->invalid('Se requiere un usuario autenticado del tenant actual.');
        }
        $hash = hash('sha256', json_encode([$tenant, $company, (int) $actor, $payload], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($payload, $tenant, $company, $actor, $hash) {
            // Serializa solicitudes de la empresa, incluida la primera con esta clave.
            if (!DB::table('companies')->where('tenant_id', $tenant)->where('id', $company)->lockForUpdate()->first()) {
                $this->invalid('La empresa no pertenece al tenant actual.');
            }
            $previous = DB::table('warehouse_transfers')->where('request_key', $payload['request_key'])->first();
            if ($previous) {
                if ((int) $previous->tenant_id !== $tenant || (int) $previous->company_id !== $company
                    || (int) $previous->requested_by !== (int) $actor || !hash_equals((string) $previous->request_hash, $hash)) {
                    $this->invalid('La clave de solicitud ya fue utilizada. Genere otra para una operación diferente.');
                }
                return WarehouseTransfer::query()->with('lines')->findOrFail($previous->id);
            }
            foreach ([$payload['source_warehouse_id'], $payload['destination_warehouse_id']] as $warehouseId) {
                if (!DB::table('warehouses')->where('tenant_id', $tenant)->where('company_id', $company)
                    ->where('id', $warehouseId)->lockForUpdate()->first()) {
                    $this->invalid('Origen y destino deben pertenecer a la empresa actual.');
                }
                $locations = DB::table('locations')->where('tenant_id', $tenant)->where('company_id', $company)
                    ->where('warehouse_id', $warehouseId)->orderBy('id')->lockForUpdate()->get();
                if ($locations->count() !== 1) {
                    $this->invalid('Cada almacén debe tener exactamente una ubicación operativa.');
                }
            }

            $transfer = new WarehouseTransfer(['notes' => $payload['notes']]);
            $transfer->source_warehouse_id = $payload['source_warehouse_id'];
            $transfer->destination_warehouse_id = $payload['destination_warehouse_id'];
            $transfer->request_key = $payload['request_key'];
            $transfer->request_hash = $hash;
            $transfer->save();

            foreach ($payload['lines'] as $row) {
                $line = new WarehouseTransferLine(['requested_quantity' => $row['requested_quantity']]);
                $line->transfer_id = $transfer->id;
                $line->stock_item_id = $row['stock_item_id'];
                $line->save();
            }
            return $transfer->load('lines');
        }, 3);
    }

    private function normalize(array $data): array
    {
        $key = $data['request_key'] ?? null;
        if (!is_string($key) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $key)) {
            $this->invalid('La solicitud requiere una clave UUID que se conserve al reintentar.');
        }
        $source = $this->id($data['source_warehouse_id'] ?? null);
        $destination = $this->id($data['destination_warehouse_id'] ?? null);
        if ($source === $destination) {
            $this->invalid('Origen y destino deben ser diferentes.');
        }
        $notes = $data['notes'] ?? null;
        if ($notes !== null && (!is_string($notes) || mb_strlen($notes) > 2000)) {
            $this->invalid('La observación admite hasta 2000 caracteres.');
        }
        $notes = $notes === null ? null : trim($notes);
        if (empty($data['lines']) || !is_array($data['lines']) || count($data['lines']) > 500) {
            $this->invalid('La solicitud requiere entre 1 y 500 productos.');
        }
        $lines = [];
        foreach ($data['lines'] as $row) {
            if (!is_array($row)) {
                $this->invalid('El detalle de productos no es válido.');
            }
            $id = $this->id($row['stock_item_id'] ?? null);
            $quantity = $row['requested_quantity'] ?? null;
            if ((!is_string($quantity) && !is_int($quantity))
                || !preg_match('/^[0-9]{1,10}(?:\.[0-9]{1,2})?$/D', (string) $quantity)
                || bccomp((string) $quantity, '0', 2) <= 0) {
                $this->invalid('La cantidad debe ser positiva y tener como máximo dos decimales.');
            }
            if (isset($lines[$id])) {
                $this->invalid('Un producto no puede repetirse en la solicitud.');
            }
            $lines[$id] = ['stock_item_id' => $id, 'requested_quantity' => bcadd((string) $quantity, '0', 2)];
        }
        ksort($lines, SORT_NUMERIC);
        return ['request_key' => strtolower($key), 'source_warehouse_id' => $source,
            'destination_warehouse_id' => $destination, 'notes' => $notes === '' ? null : $notes,
            'lines' => array_values($lines)];
    }

    private function id($value): int
    {
        if ((!is_int($value) && !is_string($value)) || !preg_match('/^[1-9][0-9]*$/D', (string) $value)
            || strlen((string) $value) > 18) {
            $this->invalid('El identificador no es válido.');
        }
        return (int) $value;
    }

    private function invalid(string $message): void
    {
        throw ValidationException::withMessages(['transfer' => $message]);
    }
}
