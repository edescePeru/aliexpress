<?php
namespace App\Services\Inventory;

use App\{CreditNote, CreditNoteDetail, OutputDetail, StockLot, Sale};
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

/** Historical evidence only. This service never increases stock or changes money. */
class CreditNoteReturnHistory
{
    public function inspect(int $noteId, int $tenantId, int $companyId, string $currency, bool $apply = false): array
    {
        if ($tenantId < 1 || $companyId < 1 || $noteId < 1 || !in_array($currency, ['PEN', 'USD'], true)) {
            throw new \DomainException('Indique tenant, empresa, nota y moneda histórica válidos.');
        }
        if ((TenantContext::tenantIdOrNull() && TenantContext::tenantIdOrNull() !== $tenantId)
            || (TenantContext::companyIdOrNull() && TenantContext::companyIdOrNull() !== $companyId)) {
            throw new \DomainException('El contexto activo no coincide con la empresa solicitada.');
        }
        return DB::transaction(function () use ($noteId, $tenantId, $companyId, $currency, $apply) {
            if (!DB::table('companies')->where('id', $companyId)->where('tenant_id', $tenantId)->exists()) {
                throw new \DomainException('La empresa no pertenece al tenant solicitado.');
            }
            $reference = DB::table('credit_notes as n')->join('sales as s', 's.id', '=', 'n.sale_id')
                ->where('n.id', $noteId)->where('s.tenant_id', $tenantId)->where('s.company_id', $companyId)->select('s.id')->first();
            if (!$reference) { throw new \DomainException('La nota no pertenece a la empresa solicitada.'); }
            $sale = Sale::where('tenant_id', $tenantId)->where('company_id', $companyId)->lockForUpdate()->findOrFail($reference->id);
            $note = CreditNote::where('sale_id', $sale->id)->lockForUpdate()->findOrFail($noteId);
            if ($note->credit_note_type !== 'partial' || $note->status !== 'accepted' || $note->internal_reversal_status !== 'reversed'
                || !$note->internal_reversed_at) {
                throw new \DomainException('Solo se reconstruyen notas parciales aceptadas cuyo reingreso físico ya fue aplicado y tiene fecha.');
            }
            if (!DB::table('branches')->where('id', $sale->branch_id)->where('company_id', $companyId)->exists()) {
                throw new \DomainException('No se puede confirmar el local histórico de la venta.');
            }
            $note->setRelation('sale', $sale);
            $details = $note->details()->with(['saleDetail', 'items'])->get();
            if ($details->isEmpty()) { throw new \DomainException('La nota no tiene detalles verificables.'); }
            $existing = DB::table('credit_note_stock_returns')->where('credit_note_id', $note->id)->get();
            if ((int) $note->stock_return_version === 1) {
                foreach ($details as $detail) {
                    $rows = $existing->where('credit_note_detail_id', $detail->id);
                    if ($rows->isEmpty() || abs((float) $rows->sum('quantity') - (float) $detail->quantity) > 0.000001) {
                        throw new \DomainException('La nota está marcada como registrada, pero su evidencia está incompleta. Requiere revisión.');
                    }
                    foreach ($rows as $row) {
                        if ($row->base_currency !== null && $row->base_currency !== $currency) { throw new \DomainException('La moneda confirmada no coincide con la evidencia ya registrada.'); }
                        if ((int) $row->tenant_id !== $tenantId || (int) $row->company_id !== $companyId || (int) $row->sale_id !== (int) $sale->id
                            || (int) $row->branch_id !== (int) $sale->branch_id) { throw new \DomainException('La evidencia existente tiene contexto incompatible.'); }
                    }
                }
                return ['note_id' => $noteId, 'state' => 'already_recorded', 'rows' => []];
            }
            if ($existing->isNotEmpty()) { throw new \DomainException('Hay registros parciales sin marca de finalización. No se completarán automáticamente.'); }
            $plan = [];
            $ledger = app(CreditNoteReturnLedger::class);
            foreach ($details as $detail) {
                $sold = $detail->saleDetail;
                if (!$sold || (int) $sold->sale_id !== (int) $sale->id) { throw new \DomainException('Detalle de venta histórico incompatible.'); }
                $outputs = OutputDetail::where('tenant_id', $tenantId)->where('company_id', $companyId)
                    ->where('sale_detail_id', $sold->id)->orderBy('id')->lockForUpdate()->get();
                if ($outputs->isEmpty()) { throw new \DomainException('No se conservan las salidas originales.'); }
                if ($detail->items->isNotEmpty()) {
                    if (abs((float) $detail->items->sum('quantity') - (float) $detail->quantity) > 0.000001
                        || $detail->items->pluck('output_detail_id')->unique()->count() !== $detail->items->count()) {
                        throw new \DomainException('La selección histórica de ítems es incompleta o repetida.');
                    }
                    foreach ($detail->items as $item) {
                        $output = $outputs->firstWhere('id', $item->output_detail_id);
                        if (!$output || (int) $output->item_id !== (int) $item->item_id || (int) $output->stock_lot_id !== (int) $item->stock_lot_id
                            || (float) $item->quantity !== 1.0) { throw new \DomainException('El ítem no conserva su salida y lote originales.'); }
                        $duplicateItem = DB::table('credit_note_detail_items as hi')
                            ->join('credit_note_details as hid', 'hid.id', '=', 'hi.credit_note_detail_id')
                            ->join('credit_notes as hin', 'hin.id', '=', 'hid.credit_note_id')
                            ->where('hi.output_detail_id', $output->id)->where('hin.sale_id', $sale->id)
                            ->where('hin.id', '!=', $note->id)->where('hin.internal_reversal_status', 'reversed')->exists();
                        if ($duplicateItem) { throw new \DomainException('El ítem aparece devuelto en otra nota aplicada. Requiere conciliación.'); }
                        $plan[] = $this->row($ledger, $note, $detail, $output, 1, $currency);
                    }
                    continue;
                }
                if ($outputs->contains(function ($o) { return (bool) $o->item_id; })) { throw new \DomainException('Falta la selección explícita de ítems devueltos.'); }
                if ((int) $note->settlement_version !== 1 && $outputs->count() !== 1) {
                    throw new \DomainException('La devolución legacy abarcaba varios lotes y no conserva una distribución demostrable.');
                }
                if (abs((float) $outputs->sum('percentage') - (float) $sold->quantity) > 0.000001) { throw new \DomainException('Las salidas no cubren exactamente la cantidad histórica vendida.'); }
                $previous = CreditNoteDetail::where('sale_detail_id', $sold->id)->whereHas('creditNote', function ($q) use ($note) {
                    $q->where('sale_id', $note->sale_id)->where('id', '<', $note->id)->where('credit_note_type', 'partial')->where('internal_reversal_status', 'reversed');
                })->with('creditNote')->get();
                foreach ($previous as $prior) {
                    if ((int) $prior->creditNote->settlement_version !== 1 && $outputs->count() > 1) {
                        throw new \DomainException('Una devolución anterior no conserva distribución verificable entre los lotes.');
                    }
                    if (!$prior->creditNote->internal_reversed_at || $prior->creditNote->internal_reversed_at > $note->internal_reversed_at) {
                        throw new \DomainException('No se puede confirmar el orden histórico de las devoluciones.');
                    }
                }
                $skip = (float) $previous->sum('quantity');
                $remaining = (float) $detail->quantity;
                if ($remaining <= 0 || $skip + $remaining > (float) $sold->quantity + 0.000001) { throw new \DomainException('Las devoluciones superan las unidades vendidas.'); }
                foreach ($outputs as $output) {
                    $original = (float) $output->percentage;
                    if ($original <= 0) { throw new \DomainException('No se conserva la cantidad original por lote.'); }
                    $skipped = min($skip, $original); $skip -= $skipped;
                    $take = min($remaining, $original - $skipped);
                    if ($take > 0) { $plan[] = $this->row($ledger, $note, $detail, $output, $take, $currency); $remaining -= $take; }
                    if ($remaining < 0.000001) { break; }
                }
                if ($remaining > 0.000001) { throw new \DomainException('La distribución de la devolución no cubre todo el detalle.'); }
            }
            if ($apply) {
                foreach ($plan as $row) {
                    DB::table('credit_note_stock_returns')->insert(array_merge($row, [
                        'recorded_by' => null, 'record_source' => 'historical_backfill', 'created_at' => now(), 'updated_at' => now(),
                    ]));
                }
                $note->stock_return_version = 1;
                $note->save();
            }
            return ['note_id' => $noteId, 'state' => $apply ? 'recorded_history_only' : 'eligible_dry_run', 'rows' => $plan];
        }, 3);
    }

    private function row($ledger, $note, $detail, $output, $quantity, $currency): array
    {
        $lot = StockLot::where('tenant_id', $note->sale->tenant_id)->where('company_id', $note->sale->company_id)
            ->lockForUpdate()->find($output->stock_lot_id);
        if (!$lot) { throw new \DomainException('El lote histórico no está disponible para verificar la procedencia.'); }
        return $ledger->row($note, $detail, $output, $lot, $quantity, $note->internal_reversed_at, $currency);
    }
}
