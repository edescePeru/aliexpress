<?php
namespace App\Services\Inventory;

use App\{CreditNote, CreditNoteDetail, OutputDetail, StockLot, Sale};
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

/** Append-only inventory evidence. Physical quantities are changed only by the live controller. */
class CreditNoteReturnLedger
{
    public function row(CreditNote $note, CreditNoteDetail $detail, OutputDetail $output, StockLot $lot,
        $quantity, $movementDate, ?string $confirmedCurrency = null): array
    {
        $sale = $note->sale;
        if (!$sale || !$sale->tenant_id || !$sale->company_id || !$sale->branch_id || $note->credit_note_type !== 'partial'
            || (int) $detail->credit_note_id !== (int) $note->id) {
            throw new \DomainException('La devolución no tiene un contexto o nota parcial válidos.');
        }
        $saleDetail = $detail->saleDetail;
        if (!$saleDetail || (int) $saleDetail->sale_id !== (int) $sale->id
            || (int) $output->sale_detail_id !== (int) $saleDetail->id) {
            throw new \DomainException('La salida original no pertenece al detalle de la devolución.');
        }
        foreach ([$saleDetail, $output, $lot] as $entity) {
            if ((int) $entity->tenant_id !== (int) $sale->tenant_id || (int) $entity->company_id !== (int) $sale->company_id) {
                throw new \DomainException('La devolución contiene referencias de otra empresa o tenant.');
            }
        }
        $header = DB::table('outputs')->where('id', $output->output_id)->where('tenant_id', $sale->tenant_id)
            ->where('company_id', $sale->company_id)->where('branch_id', $sale->branch_id)->first();
        if (!$header || !in_array($header->state, ['attended', 'confirmed'], true)) {
            throw new \DomainException('La salida no está confirmada en el local de la venta.');
        }
        $stock = DB::table('stock_items')->where('id', $output->stock_item_id)->where('tenant_id', $sale->tenant_id)->first();
        if (!$stock || (int) $output->stock_item_id !== (int) $saleDetail->stock_item_id
            || (int) $stock->material_id !== (int) $output->material_id
            || !DB::table('materials')->where('id', $stock->material_id)->where('tenant_id', $sale->tenant_id)->exists()) {
            throw new \DomainException('No se conserva la identidad original del producto devuelto.');
        }
        foreach (['stock_item_id', 'warehouse_id', 'location_id'] as $field) {
            if (!$output->$field || (int) $output->$field !== (int) $lot->$field) {
                throw new \DomainException('El lote no coincide con el producto y ubicación de la salida.');
            }
        }
        if ((int) $output->stock_lot_id !== (int) $lot->id
            || !DB::table('warehouses')->where('id', $lot->warehouse_id)->where('tenant_id', $sale->tenant_id)
                ->where('company_id', $sale->company_id)->where(function ($q) use ($sale) {
                    $q->whereNull('branch_id')->orWhere('branch_id', $sale->branch_id);
                })->exists()
            || !DB::table('locations')->where('id', $lot->location_id)->where('warehouse_id', $lot->warehouse_id)
                ->where('tenant_id', $sale->tenant_id)->where('company_id', $sale->company_id)->exists()) {
            throw new \DomainException('No se conserva la procedencia física del reingreso.');
        }
        $quantity = $this->decimal($quantity, 6);
        $outputQuantity = $this->decimal($output->percentage, 6);
        if (bccomp($quantity, '0', 6) <= 0 || bccomp($quantity, $outputQuantity, 6) > 0) {
            throw new \DomainException('La devolución supera la cantidad de la salida original.');
        }
        if ($output->unit_cost !== null) {
            $unitCost = $this->decimal($output->unit_cost, 10);
            $costSource = 'output_unit_cost';
        } elseif ($output->total_cost !== null && bccomp($outputQuantity, '0', 6) > 0) {
            $unitCost = bcdiv($this->decimal($output->total_cost, 10), $outputQuantity, 10);
            $costSource = 'output_total_cost';
        } else {
            throw new \DomainException('La salida no conserva costo histórico. No se usará el precio de venta ni el costo actual.');
        }
        $currency = $output->cost_currency ? strtoupper($output->cost_currency) : $confirmedCurrency;
        if ($currency !== null && !in_array($currency, ['PEN', 'USD'], true)) {
            throw new \DomainException('La moneda histórica del costo no es válida.');
        }
        if ($confirmedCurrency && $output->cost_currency && $confirmedCurrency !== strtoupper($output->cost_currency)) {
            throw new \DomainException('La moneda confirmada no coincide con la guardada en la salida.');
        }
        try {
            if (!$movementDate || !$header->request_date) { throw new \InvalidArgumentException(); }
            $date = \Carbon\Carbon::parse($movementDate);
            $outputDate = \Carbon\Carbon::parse($header->request_date);
        } catch (\Throwable $e) { throw new \DomainException('No se conservan fechas históricas válidas para el reingreso y su salida.'); }
        if ($date->lt($outputDate)) {
            throw new \DomainException('La fecha del reingreso es anterior a la salida original.');
        }
        return [
            'tenant_id' => $sale->tenant_id, 'company_id' => $sale->company_id, 'branch_id' => $sale->branch_id,
            'sale_id' => $sale->id, 'credit_note_id' => $note->id, 'credit_note_detail_id' => $detail->id,
            'output_detail_id' => $output->id, 'stock_item_id' => $output->stock_item_id, 'material_id' => $output->material_id,
            'stock_lot_id' => $lot->id, 'warehouse_id' => $lot->warehouse_id, 'location_id' => $lot->location_id,
            'item_id' => $output->item_id ?: null, 'quantity' => $quantity, 'unit_cost' => $unitCost,
            'total_cost' => bcmul($quantity, $unitCost, 10), 'base_currency' => $currency,
            'cost_source' => $costSource,
            'currency_source' => $output->cost_currency ? 'output_snapshot' : ($confirmedCurrency ? 'confirmed_backfill' : 'legacy_unlabelled'),
            'movement_date' => $date->format('Y-m-d H:i:s.u'),
        ];
    }

    public function record(array $row, CreditNote $note): void
    {
        if ((int) $row['credit_note_id'] !== (int) $note->id || (int) $row['sale_id'] !== (int) $note->sale_id
            || DB::transactionLevel() < 1 || $note->status !== 'accepted' || $note->internal_reversal_status === 'reversed'
            || (int) $row['tenant_id'] !== (int) TenantContext::tenantId()
            || (int) $row['company_id'] !== (int) TenantContext::companyId()
            || (int) $row['branch_id'] !== (int) TenantContext::branchId()) {
            throw new \DomainException('El reingreso requiere una nota aceptada, contexto válido y una transacción abierta.');
        }
        // Deliberately not firstOrCreate: an existing row with an unapplied note needs review,
        // otherwise the caller could increase stock twice while hiding the duplicate evidence.
        DB::table('credit_note_stock_returns')->insert(array_merge($row, [
            'recorded_by' => auth()->id(), 'record_source' => 'accepted_note', 'created_at' => now(), 'updated_at' => now(),
        ]));
    }

    private function decimal($value, int $scale): string
    {
        if (!is_scalar($value) || !preg_match('/^\d{1,14}(?:\.\d{1,10})?$/D', (string) $value)) {
            throw new \DomainException('Cantidad o costo histórico inválidos.');
        }
        if (bccomp((string) $value, bcadd((string) $value, '0', $scale), 10) !== 0) {
            throw new \DomainException('La precisión histórica supera la admitida por el registro de reingresos.');
        }
        return bcadd((string) $value, '0', $scale);
    }
}
