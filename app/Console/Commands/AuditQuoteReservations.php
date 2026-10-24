<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditQuoteReservations extends Command
{
    protected $signature = 'quotes:reservation-audit {quote_id : ID de la cotización}';
    protected $description = 'Diagnóstico de solo lectura de almacenes e Items reservados por una cotización';

    public function handle()
    {
        $id = $this->argument('quote_id');
        if (!is_scalar($id) || !preg_match('/^[1-9][0-9]*$/D', (string) $id)) { $this->error('Indique un ID válido.'); return 1; }
        $quote = DB::table('quotes')->where('id', $id)->first(['id', 'tenant_id', 'company_id', 'branch_id']);
        if (!$quote) { $this->error('No existe la cotización.'); return 1; }
        $this->line('Cotización '.$quote->id.' | Tenant '.$quote->tenant_id.' | Empresa '.$quote->company_id.' | Local '.($quote->branch_id ?? 'PENDIENTE'));
        $rows = DB::table('quote_stock_lots')->where('quote_id', $id)->orderBy('id')->get();
        $table = []; $review = false;
        foreach ($rows as $row) {
            $lot = DB::table('stock_lots')->where('id', $row->stock_lot_id)->first();
            $warehouse = DB::table('warehouses')->where('id', $row->warehouse_id)->first();
            $location = DB::table('locations')->where('id', $row->location_id)->first();
            $ok = $quote->branch_id !== null && $lot && $warehouse && $location
                && (int) $warehouse->tenant_id === (int) $quote->tenant_id && (int) $warehouse->company_id === (int) $quote->company_id
                && $warehouse->branch_id !== null && (int) $warehouse->branch_id === (int) $quote->branch_id
                && (int) $lot->tenant_id === (int) $quote->tenant_id && (int) $lot->company_id === (int) $quote->company_id
                && (int) $lot->stock_item_id === (int) $row->stock_item_id && (int) $lot->warehouse_id === (int) $row->warehouse_id
                && (int) $lot->location_id === (int) $row->location_id && (int) $location->warehouse_id === (int) $row->warehouse_id
                && (int) $location->tenant_id === (int) $quote->tenant_id && (int) $location->company_id === (int) $quote->company_id;
            $ids = $row->item_ids === null ? [] : json_decode($row->item_ids, true);
            $itemsOk = is_array($ids);
            if ($itemsOk) {
                foreach ($ids as $itemId) {
                    if (!is_scalar($itemId) || !preg_match('/^[1-9][0-9]*$/D', (string) $itemId)) { $itemsOk = false; break; }
                }
            }
            if ($itemsOk && $ids) {
                $itemsOk = count(array_unique($ids)) === count($ids) && (float) count($ids) === (float) $row->quantity
                    && DB::table('items')->where('tenant_id', $quote->tenant_id)->where('company_id', $quote->company_id)
                        ->whereIn('id', $ids)->where('stock_item_id', $row->stock_item_id)->where('stock_lot_id', $row->stock_lot_id)
                        ->where('warehouse_id', $row->warehouse_id)->where('location_id', $row->location_id)
                        ->where('state_item', 'reserved')->where('percentage', 1)->count() === count($ids);
            }
            $review = $review || !$ok || !$itemsOk;
            $table[] = [$row->id, $row->stock_item_id, $row->stock_lot_id, $row->warehouse_id, $warehouse->name ?? 'No existe',
                $warehouse ? ($warehouse->branch_id ?? 'GENERAL') : '-', $row->quantity, $ok ? 'LOCAL OK' : 'REVISAR', $itemsOk ? 'REFERENCIAS OK' : 'REVISAR'];
        }
        $this->table(['Reserva', 'StockItem', 'Lote', 'Almacén ID', 'Almacén', 'Local almacén', 'Cantidad', 'Contexto', 'Items declarados'], $table);
        if ($rows->isEmpty()) { $this->line('No hay reservas registradas.'); }
        if ($review) { $this->warn('Hay referencias que requieren revisión. No reasigne ni libere reservas automáticamente.'); }
        $this->line('Diagnóstico de referencias; no certifica saldos ni cobertura completa de los detalles. La venta los validará dentro de su transacción.');
        $this->line('No se modificaron cotizaciones, reservas, Items, inventario ni ventas.');
        return 0;
    }
}
