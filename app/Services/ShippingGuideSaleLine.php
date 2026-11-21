<?php
namespace App\Services;

class ShippingGuideSaleLine
{
    public static function quantity($detail): float
    {
        $value = $detail->packs !== null || $detail->material_presentation_id
            ? $detail->packs : $detail->quantity;
        if (!is_numeric($value) || !is_finite((float)$value) || (float)$value <= 0) {
            throw new \RuntimeException('La venta tiene una cantidad o presentación incompleta. Revise el detalle antes de crear la guía.');
        }
        return (float)$value;
    }

    public static function description($detail): string
    {
        $name = trim((string)$detail->description);
        if ($name === '') $name = (string)(optional($detail->stockItem)->display_name
            ?: (optional($detail->material)->full_name ?: 'MATERIAL #'.$detail->material_id));
        $label = trim((string)$detail->presentation_label_snapshot);
        if ($detail->packs !== null || $label !== '') {
            $label = $label !== '' ? $label : 'Paquete';
            $units = (int)$detail->units_per_pack;
            return $name.' | Presentación: '.$label.($units > 0 ? ' ('.$units.' unidades)' : ' (unidades no registradas)');
        }
        if ($detail->material_presentation_id) {
            $presentation = $detail->materialPresentation;
            if ($presentation && (int)$presentation->tenant_id === (int)$detail->tenant_id
                && (int)$presentation->material_id === (int)$detail->material_id) {
                return $name.' ('.(int)$presentation->quantity.' UND)';
            }
        }
        return $name;
    }
}
