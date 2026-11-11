<?php
namespace App\Services;

use App\UnitMeasure;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FinanceInvoiceAmounts
{
    public function lines(string $json): array
    {
        $lines = json_decode($json, true);
        $validated = Validator::make(['items' => $lines], [
            'items' => 'required|array|min:1|max:200',
            'items.*.material' => 'required|string|max:255',
            'items.*.quantity' => ['required','numeric','gt:0','regex:/^\d{1,7}(\.\d{1,2})?$/D'],
            'items.*.total' => ['required','numeric','gt:0','regex:/^\d{1,7}(\.\d{1,2})?$/D'],
            'items.*.unit_id' => 'required|integer|min:1',
            'items.*.tax_kind' => 'required|in:gravada,exonerada,inafecta',
        ])->validate();

        $units = UnitMeasure::query()->where('tenant_id', \App\Support\TenantContext::tenantId())
            ->whereIn('id', array_column($validated['items'], 'unit_id'))->pluck('name', 'id');

        $result = [];
        foreach ($validated['items'] as $line) {
            $unit = $units->get($line['unit_id']);
            if ($unit === null) {
                throw ValidationException::withMessages(['items' => 'Seleccione una unidad de medida del tenant activo.']);
            }
            $gross = (int) round((float) $line['total'] * 100);
            $base = $line['tax_kind'] === 'gravada' ? (int) round($gross / 1.18) : $gross;
            $result[] = [
                'material_name' => $line['material'],
                'material_unit' => $unit,
                'entered_quantity' => $line['quantity'],
                'ordered_quantity' => $line['quantity'],
                'unit_price' => round($gross / 100 / (float) $line['quantity'], 2),
                'total_detail' => $gross / 100,
                'finance_tax_kind' => $line['tax_kind'],
                'finance_tax_amount' => ($gross - $base) / 100,
            ];
        }
        return $result;
    }
}
