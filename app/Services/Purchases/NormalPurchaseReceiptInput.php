<?php
namespace App\Services\Purchases;

use Illuminate\Validation\ValidationException;

class NormalPurchaseReceiptInput
{
    public function normalize($json, array $summary): array
    {
        if (!$summary['can_receive']) {
            $this->invalid(implode(' ', $summary['errors']) ?: 'La orden ya está completa.');
        }
        $items = is_string($json) ? json_decode($json) : null;
        if (!is_array($items) || !$items) { $this->invalid('Indique cantidades para recibir.'); }
        $lines = collect($summary['lines'])->keyBy('order_purchase_detail_id');
        $seen = []; $result = [];
        foreach ($items as $item) {
            if (!is_object($item) || !preg_match('/^[1-9][0-9]*$/D', (string) ($item->order_purchase_detail_id ?? ''))) {
                $this->invalid('Una línea no es válida.');
            }
            $id = (int) $item->order_purchase_detail_id;
            $line = $lines->get($id);
            if (!$line || isset($seen[$id])) { $this->invalid('Hay líneas ajenas a la orden o repetidas.'); }
            $seen[$id] = true;
            if ((isset($item->material_lote) && !is_string($item->material_lote))
                || (isset($item->date_vence) && !is_string($item->date_vence))) {
                $this->invalid('Lote y vencimiento deben tener un formato válido.');
            }
            $qty = $this->units($item->entered ?? null);
            if ($qty > $this->units($line['pending'])) { $this->invalid('La cantidad supera lo pendiente. Actualice la pantalla.'); }
            if (!$qty) { continue; }
            // Identidad, precio y cantidades pedidas nunca se toman del navegador.
            $result[] = (object) [
                'order_purchase_detail_id' => $id,
                'quantity' => $qty / 100,
                'id_material' => $line['material_id'],
                'stock_item_id' => $line['stock_item_id'],
                'warehouse_id' => $item->warehouse_id ?? null,
                'id_location' => $item->id_location ?? null,
                'material_lote' => trim((string) ($item->material_lote ?? '')),
                'date_vence' => trim((string) ($item->date_vence ?? '')),
            ];
        }
        if (!$result) { $this->invalid('Ingrese una cantidad mayor que cero en al menos una línea.'); }
        usort($result, function ($a, $b) {
            return [$a->stock_item_id, $a->warehouse_id, $a->order_purchase_detail_id]
                <=> [$b->stock_item_id, $b->warehouse_id, $b->order_purchase_detail_id];
        });
        return $result;
    }

    private function units($value): int
    {
        if ((!is_string($value) && !is_int($value) && !is_float($value))
            || !preg_match('/^([0-9]{1,9})(?:\.([0-9]{1,2}))?$/D', (string) $value, $m)) {
            $this->invalid('Use cantidades positivas con hasta dos decimales.');
        }
        return (int) $m[1] * 100 + (int) str_pad($m[2] ?? '', 2, '0');
    }

    private function invalid(string $message): void
    {
        throw ValidationException::withMessages(['items' => $message]);
    }
}
