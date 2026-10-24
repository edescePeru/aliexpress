<?php
namespace App\Services\Transfers;

use App\Services\SettingService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WarehouseTransferCostResolver
{
    public function resolve($lot): array
    {
        $base = strtoupper((string) app(SettingService::class)->get('finance.base_currency'));
        if (!in_array($base, ['PEN', 'USD'], true)) { $this->invalid(); }
        $raw = $this->cost($lot->unit_cost);
        // Una recepciÃ³n de traslado conserva los costos de la asignaciÃ³n anterior.
        $previous = $this->owned('warehouse_transfer_receipt_lines')->where('destination_stock_lot_id', $lot->id)->first();
        if ($previous) {
            $allocation = $this->owned('warehouse_transfer_allocations')->where('id', $previous->allocation_id)->first();
            if (!$allocation || $allocation->base_currency !== $base || bccomp($raw, $allocation->source_unit_cost, 6) !== 0) { $this->invalid(); }
            return ['source_unit_cost' => $raw, 'source_currency' => $allocation->source_currency,
                'base_unit_cost' => $this->cost($allocation->base_unit_cost), 'base_currency' => $base,
                'exchange_rate' => $allocation->exchange_rate];
        }
        $detail = $this->owned('detail_entries')->where('id', $lot->detail_entry_id)->first();
        if (!$detail || (int) $detail->stock_item_id !== (int) $lot->stock_item_id) { $this->invalid(); }
        $entry = $this->owned('entries')->where('id', $detail->entry_id)->whereNull('deleted_at')->first();
        if (!$entry) { $this->invalid(); }
        $currency = strtoupper((string) $entry->currency_invoice);
        if (!in_array($currency, ['PEN', 'USD'], true)) { $this->invalid(); }
        // Reproduce la base de valoraciÃ³n del kardex: total / cantidad si existe total.
        $cost = $this->cost($detail->unit_price);
        if ($detail->total_detail !== null) {
            $total = $this->cost($detail->total_detail);
            if (!is_numeric($detail->entered_quantity) || bccomp((string) $detail->entered_quantity, '0', 8) <= 0) { $this->invalid(); }
            $cost = bcdiv($total, (string) $detail->entered_quantity, 6);
        }
        $rate = '1.00000000';
        if ($currency !== $base) {
            $rate = $base === 'PEN' ? $entry->currency_compra : $entry->currency_venta;
            if (!is_scalar($rate) || !preg_match('/^[0-9]{1,10}(?:\.[0-9]{1,8})?$/D', (string) $rate) || bccomp((string) $rate, '0', 8) <= 0) { $this->invalid(); }
            $rate = bcadd((string) $rate, '0', 8);
            $cost = $base === 'PEN' ? bcmul($cost, $rate, 6) : bcdiv($cost, $rate, 6);
        }
        return ['source_unit_cost' => $raw, 'source_currency' => $currency,
            'base_unit_cost' => $this->cost($cost), 'base_currency' => $base, 'exchange_rate' => $rate];
    }

    private function owned(string $table)
    {
        return DB::table($table)->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId());
    }
    private function cost($value): string
    {
        if (!is_scalar($value) || !preg_match('/^[0-9]{1,12}(?:\.[0-9]{1,6})?$/D', (string) $value)) { $this->invalid(); }
        return bcadd((string) $value, '0', 6);
    }
    private function invalid(): void
    {
        throw ValidationException::withMessages(['transfer' => 'No se puede verificar el costo, moneda o tipo de cambio del lote. Requiere conciliaciÃ³n antes de prepararlo.']);
    }
}
