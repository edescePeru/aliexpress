<?php
namespace App\Services\Inventory;

use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** One chronological source for the Kardex and both company cost readers. */
class InventoryTimelineQuery
{
    public function rows(?array $stockIds, ?array $materialIds, $warehouseIds, $until, string $currency)
    {
        if (!in_array($currency, ['PEN', 'USD'], true)) { $this->invalid('La moneda base de inventario no es válida.'); }
        $this->assertReturnHistory($stockIds, $materialIds, $warehouseIds, $until);
        $query = DB::query()->fromSub($this->query(), 'inventory_movements_view')
            ->whereExists(function ($q) {
                $q->selectRaw('1')->from('warehouses as timeline_wh')->whereColumn('timeline_wh.id', 'inventory_movements_view.warehouse_id')
                    ->where('timeline_wh.tenant_id', TenantContext::tenantId())->where('timeline_wh.company_id', TenantContext::companyId());
            })->whereExists(function ($q) {
                $q->selectRaw('1')->from('locations as timeline_loc')->whereColumn('timeline_loc.id', 'inventory_movements_view.location_id')
                    ->whereColumn('timeline_loc.warehouse_id', 'inventory_movements_view.warehouse_id')
                    ->where('timeline_loc.tenant_id', TenantContext::tenantId())->where('timeline_loc.company_id', TenantContext::companyId());
            });
        if ($stockIds !== null) { $query->whereIn('stock_item_id', $stockIds); }
        if ($materialIds !== null) { $query->whereIn('material_id', $materialIds); }
        if ($warehouseIds !== null) { $query->whereIn('warehouse_id', clone $warehouseIds); }
        if ($until !== null) { $query->where('movement_date', '<=', $until); }
        $rows = $query->orderBy('movement_date')->orderBy('movement_order')->orderBy('movement_id')->get();
        foreach ($rows as $row) {
            if ($row->base_currency !== null && $row->base_currency !== $currency) {
                $this->invalid('Hay salidas, traslados o devoluciones valorizados en otra moneda base. Revise su moneda histórica.');
            }
            if ((float) $row->quantity > 0 && ($row->unit_cost !== null && (!is_numeric($row->unit_cost) || (float) $row->unit_cost < 0))) {
                $this->invalid('Hay movimientos con costo histórico inválido.');
            }
            if ($row->movement_type === 'IN' && (float) $row->quantity > 0 && $row->unit_cost === null) {
                $this->invalid('Hay ingresos sin costo histórico; no se sustituirán por costo cero.');
            }
        }
        return $rows;
    }

    private function assertReturnHistory(?array $stocks, ?array $materials, $warehouses, $until): void
    {
        $q = DB::table('credit_note_details as hd')->join('credit_notes as hn', 'hn.id', '=', 'hd.credit_note_id')
            ->join('sales as hs', 'hs.id', '=', 'hn.sale_id')
            ->join('sale_details as hsd', function ($j) {
                $j->on('hsd.id', '=', 'hd.sale_detail_id')->on('hsd.sale_id', '=', 'hs.id')
                    ->on('hsd.tenant_id', '=', 'hs.tenant_id')->on('hsd.company_id', '=', 'hs.company_id');
            })->where('hs.tenant_id', TenantContext::tenantId())->where('hs.company_id', TenantContext::companyId())
            ->where('hn.credit_note_type', 'partial')->where('hn.internal_reversal_status', 'reversed')->where('hd.quantity', '>', 0)
            ->where(function ($q) {
                $q->whereNull('hn.stock_return_version')->orWhere('hn.stock_return_version', '!=', 1)
                    ->orWhereRaw('ABS(hd.quantity - (SELECT COALESCE(SUM(hr.quantity),0) FROM credit_note_stock_returns hr WHERE hr.credit_note_detail_id = hd.id AND hr.credit_note_id = hn.id AND hr.sale_id = hs.id AND hr.tenant_id = hs.tenant_id AND hr.company_id = hs.company_id AND hr.branch_id = hs.branch_id)) > 0.000001');
            });
        if ($stocks !== null) { $q->whereIn('hsd.stock_item_id', $stocks); }
        if ($materials !== null) {
            $q->where(function ($q) use ($materials) {
                $q->whereIn('hsd.material_id', $materials)->orWhereExists(function ($q) use ($materials) {
                    $q->selectRaw('1')->from('stock_items as history_stock')->whereColumn('history_stock.id', 'hsd.stock_item_id')
                        ->whereColumn('history_stock.tenant_id', 'hs.tenant_id')->whereIn('history_stock.material_id', $materials);
                });
            });
        }
        if ($until !== null) { $q->where(function ($q) use ($until) { $q->whereNull('hn.internal_reversed_at')->orWhere('hn.internal_reversed_at', '<=', $until); }); }
        if ($warehouses !== null) {
            $q->where(function ($q) use ($warehouses) {
                $q->where('hs.branch_id', TenantContext::branchId())->orWhereExists(function ($q) use ($warehouses) {
                    $q->selectRaw('1')->from('output_details as ho')->whereColumn('ho.sale_detail_id', 'hsd.id')
                        ->whereColumn('ho.tenant_id', 'hs.tenant_id')->whereColumn('ho.company_id', 'hs.company_id')
                        ->whereIn('ho.warehouse_id', clone $warehouses);
                });
            });
        }
        if ($q->exists()) { $this->invalid('Hay devoluciones aplicadas sin un reingreso histórico verificado. Ejecute primero el diagnóstico de notas de crédito.'); }
    }

    private function invalid(string $message): void
    {
        throw ValidationException::withMessages(['inventory' => $message]);
    }

    private function query()
    {
        $ordinary = DB::table('inventory_movements_view as v')
            ->leftJoin('output_details as currency_output', function ($j) {
                $j->on(DB::raw("CONCAT('OUT_', currency_output.id)"), '=', 'v.movement_id')
                    ->on('currency_output.tenant_id', '=', 'v.tenant_id')->on('currency_output.company_id', '=', 'v.company_id')
                    ->where('v.source_type', '=', 'output');
            })
            ->where('v.tenant_id', TenantContext::tenantId())->where('v.company_id', TenantContext::companyId())
            ->selectRaw("v.movement_id, v.movement_type, v.tenant_id, v.company_id, v.stock_item_id, v.material_id,
                v.warehouse_id, v.location_id, v.stock_lot_id, v.movement_date, v.quantity, v.unit_cost,
                v.source_type, v.source_id, NULL AS receipt_id, currency_output.cost_currency AS base_currency,
                CASE WHEN v.movement_type = 'IN' THEN 0 ELSE 1 END AS movement_order");

        $allocations = DB::table('warehouse_transfer_allocations as a')
            ->join('warehouse_transfer_lines as l', function ($join) {
                $join->on('l.id', '=', 'a.transfer_line_id')
                    ->on('l.tenant_id', '=', 'a.tenant_id')->on('l.company_id', '=', 'a.company_id');
            })
            ->join('warehouse_transfers as t', function ($join) {
                $join->on('t.id', '=', 'l.transfer_id')
                    ->on('t.tenant_id', '=', 'l.tenant_id')->on('t.company_id', '=', 'l.company_id');
            })
            ->join('stock_lots as source_lot', function ($j) {
                $j->on('source_lot.id', '=', 'a.source_stock_lot_id')->on('source_lot.stock_item_id', '=', 'l.stock_item_id')
                    ->on('source_lot.tenant_id', '=', 'a.tenant_id')->on('source_lot.company_id', '=', 'a.company_id')
                    ->on('source_lot.warehouse_id', '=', 't.source_warehouse_id')->on('source_lot.location_id', '=', 't.source_location_id');
            })
            ->join('stock_items as source_stock', function ($j) {
                $j->on('source_stock.id', '=', 'l.stock_item_id')->on('source_stock.tenant_id', '=', 'l.tenant_id');
            })
            ->join('materials as source_material', function ($j) {
                $j->on('source_material.id', '=', 'source_stock.material_id')->on('source_material.tenant_id', '=', 'l.tenant_id');
            })
            ->join('warehouses as source_wh', function ($j) {
                $j->on('source_wh.id', '=', 't.source_warehouse_id')->on('source_wh.tenant_id', '=', 't.tenant_id')->on('source_wh.company_id', '=', 't.company_id');
            })
            ->join('warehouses as destination_wh', function ($j) {
                $j->on('destination_wh.id', '=', 't.destination_warehouse_id')->on('destination_wh.tenant_id', '=', 't.tenant_id')->on('destination_wh.company_id', '=', 't.company_id');
            })
            ->join('locations as source_loc', function ($j) {
                $j->on('source_loc.id', '=', 't.source_location_id')->on('source_loc.warehouse_id', '=', 't.source_warehouse_id')
                    ->on('source_loc.tenant_id', '=', 't.tenant_id')->on('source_loc.company_id', '=', 't.company_id');
            })
            ->join('locations as destination_loc', function ($j) {
                $j->on('destination_loc.id', '=', 't.destination_location_id')->on('destination_loc.warehouse_id', '=', 't.destination_warehouse_id')
                    ->on('destination_loc.tenant_id', '=', 't.tenant_id')->on('destination_loc.company_id', '=', 't.company_id');
            })
            ->where('t.tenant_id', TenantContext::tenantId())
            ->where('t.company_id', TenantContext::companyId())
            ->where('a.status', 'dispatched')
            ->whereNotNull('t.dispatched_at');

        $dispatches = (clone $allocations)
            ->selectRaw("CONCAT('TRANSFER_OUT_', a.id), 'OUT', t.tenant_id, t.company_id, l.stock_item_id, source_stock.material_id, t.source_warehouse_id,
                t.source_location_id, a.source_stock_lot_id, t.dispatched_at, a.quantity, a.base_unit_cost,
                'transfer_dispatch', t.id, NULL, a.base_currency, 1");

        $receipts = (clone $allocations)
            ->join('warehouse_transfer_receipt_lines as rl', function ($join) {
                $join->on('rl.allocation_id', '=', 'a.id')
                    ->on('rl.tenant_id', '=', 'a.tenant_id')->on('rl.company_id', '=', 'a.company_id');
            })
            ->join('warehouse_transfer_receipts as r', function ($join) {
                $join->on('r.id', '=', 'rl.receipt_id')->on('r.transfer_id', '=', 't.id')
                    ->on('r.tenant_id', '=', 'rl.tenant_id')->on('r.company_id', '=', 'rl.company_id');
            })
            ->join('stock_lots as destination_lot', function ($j) {
                $j->on('destination_lot.id', '=', 'rl.destination_stock_lot_id')->on('destination_lot.stock_item_id', '=', 'l.stock_item_id')
                    ->on('destination_lot.tenant_id', '=', 'rl.tenant_id')->on('destination_lot.company_id', '=', 'rl.company_id')
                    ->on('destination_lot.warehouse_id', '=', 't.destination_warehouse_id')->on('destination_lot.location_id', '=', 't.destination_location_id');
            })
            ->whereNotNull('r.received_at')
            ->selectRaw("CONCAT('TRANSFER_IN_', rl.id), 'IN', t.tenant_id, t.company_id, l.stock_item_id, source_stock.material_id, t.destination_warehouse_id,
                t.destination_location_id, rl.destination_stock_lot_id, r.received_at, rl.quantity, a.base_unit_cost,
                'transfer_receipt', t.id, r.id, a.base_currency, 0");



        $returns = DB::table('credit_note_stock_returns as nr')
            ->join('credit_notes as nn', function ($j) { $j->on('nn.id', '=', 'nr.credit_note_id')->on('nn.sale_id', '=', 'nr.sale_id'); })
            ->join('sales as ns', function ($j) {
                $j->on('ns.id', '=', 'nr.sale_id')->on('ns.tenant_id', '=', 'nr.tenant_id')->on('ns.company_id', '=', 'nr.company_id')->on('ns.branch_id', '=', 'nr.branch_id');
            })->join('credit_note_details as nd', function ($j) { $j->on('nd.id', '=', 'nr.credit_note_detail_id')->on('nd.credit_note_id', '=', 'nn.id'); })
            ->join('sale_details as nsd', function ($j) {
                $j->on('nsd.id', '=', 'nd.sale_detail_id')->on('nsd.sale_id', '=', 'ns.id')->on('nsd.tenant_id', '=', 'nr.tenant_id')
                    ->on('nsd.company_id', '=', 'nr.company_id')->on('nsd.stock_item_id', '=', 'nr.stock_item_id');
            })->where('nr.tenant_id', TenantContext::tenantId())->where('nr.company_id', TenantContext::companyId())
            ->where('nn.credit_note_type', 'partial')->where('nn.internal_reversal_status', 'reversed')->where('nn.stock_return_version', 1)
            ->selectRaw("CONCAT('CREDIT_RETURN_', nr.id), 'IN', nr.tenant_id, nr.company_id, nr.stock_item_id, nr.material_id,
                nr.warehouse_id, nr.location_id, nr.stock_lot_id, nr.movement_date, nr.quantity, nr.unit_cost,
                'credit_note_return', nr.credit_note_id, NULL, nr.base_currency, 2");
        $annulments = DB::table('sale_annulment_stock_returns as ar')
            ->join('sale_annulment_operations as ao', 'ao.id', '=', 'ar.operation_id')
            ->join('sales as ars', function ($j) { $j->on('ars.id','=','ar.sale_id')->on('ars.tenant_id','=','ar.tenant_id')->on('ars.company_id','=','ar.company_id')->on('ars.branch_id','=','ar.branch_id'); })
            ->where('ar.tenant_id',TenantContext::tenantId())->where('ar.company_id',TenantContext::companyId())
            ->whereColumn('ao.sale_id','ar.sale_id')->whereColumn('ao.tenant_id','ar.tenant_id')->whereColumn('ao.company_id','ar.company_id')->whereColumn('ao.branch_id','ar.branch_id')
            ->where('ao.reversal_state','reversed')
            ->where(function($q){$q->whereNull('ao.credit_note_id')->orWhereExists(function($q){$q->selectRaw('1')->from('credit_notes as full_cn')->whereColumn('full_cn.id','ao.credit_note_id')->whereColumn('full_cn.sale_id','ar.sale_id')->where('full_cn.credit_note_type','total');});})
            ->selectRaw("CONCAT('SALE_ANNUL_RETURN_', ar.id), 'IN', ar.tenant_id, ar.company_id, ar.stock_item_id, ar.material_id,
                ar.warehouse_id, ar.location_id, ar.stock_lot_id, ar.movement_date, ar.quantity, ar.unit_cost,
                CASE WHEN ao.credit_note_id IS NOT NULL THEN 'credit_note_total_return' ELSE 'sale_annulment_return' END,
                COALESCE(ao.credit_note_id, ar.sale_id), NULL, ar.base_currency, 2");
        return $ordinary->unionAll($dispatches)->unionAll($receipts)->unionAll($returns)->unionAll($annulments);
    }
}
