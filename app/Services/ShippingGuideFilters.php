<?php
namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ShippingGuideFilters
{
    public static function listing(Request $request)
    {
        $input = $request->query();
        foreach (['date_from', 'date_to', 'page'] as $field) {
            if (isset($input[$field]) && $input[$field] === '') { $input[$field] = null; }
        }
        $rules = [
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'buscar_doc' => ['nullable', 'string', 'max:64'],
            'page' => ['nullable', 'integer', 'min:1', 'max:'.intdiv(PHP_INT_MAX, 20)],
        ];
        if (!empty($input['date_from']) && !empty($input['date_to'])) { $rules['date_to'][] = 'after_or_equal:date_from'; }
        $data = Validator::make($input, $rules)->validate();
        $result = [
            'date_from' => $data['date_from'] ?? null, 'date_to' => $data['date_to'] ?? null,
            'page' => (int) ($data['page'] ?? 1), 'serie' => null, 'numero' => null,
        ];
        $document = strtoupper(trim($data['buscar_doc'] ?? ''));
        if ($document === '') { return $result; }
        if (strpos($document, '-') !== false) {
            $parts = array_map('trim', explode('-', $document));
            if (count($parts) !== 2 || !preg_match('/^[A-Z0-9]{4}$/D', $parts[0])) { self::invalidDocument(); }
            $result['serie'] = $parts[0];
            $result['numero'] = self::number($parts[1]);
        } elseif (ctype_digit($document)) {
            $result['numero'] = self::number($document);
        } elseif (preg_match('/^[A-Z0-9]{4}$/D', $document)) {
            $result['serie'] = $document;
        } else { self::invalidDocument(); }
        return $result;
    }

    public static function export(Request $request)
    {
        return Validator::make($request->all(), [
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ], [
            'date_from.required' => 'Selecciona la fecha Desde.',
            'date_to.required' => 'Selecciona la fecha Hasta.',
            'date_to.after_or_equal' => 'La fecha Hasta no puede ser menor que Desde.',
        ])->validate();
    }

    private static function number($value)
    {
        if (!ctype_digit($value)) { self::invalidDocument(); }
        $number = ltrim($value, '0');
        if ($number === '' || strlen($number) > 10) { self::invalidDocument(); }
        return $number;
    }

    private static function invalidDocument()
    {
        throw ValidationException::withMessages(['buscar_doc' => 'Ingresa una serie de cuatro caracteres, un número o serie-número; por ejemplo TTT1-123.']);
    }
}
