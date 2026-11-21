<?php
namespace App\Services;

use App\Entry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FinanceInvoiceExportRead
{
    public function data(array $input): array
    {
        $input = array_intersect_key($input, array_flip(['start', 'end']));
        foreach (['start', 'end'] as $key) {
            if (isset($input[$key]) && is_string($input[$key])) {
                $input[$key] = trim($input[$key]);
                if ($input[$key] === '') $input[$key] = null;
            }
        }
        $filters = Validator::make($input, [
            'start' => 'nullable|required_with:end|date_format:d/m/Y',
            'end' => 'nullable|required_with:start|date_format:d/m/Y',
        ])->validate();
        $ids = app(FinanceInvoiceContext::class)->ids();
        $query = Entry::query()->where($ids)->where('finance', true)
            ->with(['supplier', 'details', 'category_invoice'])
            ->orderByDesc('created_at')->orderByDesc('id');
        $dates = 'TOTALES';
        if (!empty($filters['start'])) {
            $from = Carbon::createFromFormat('d/m/Y', $filters['start'])->startOfDay();
            $to = Carbon::createFromFormat('d/m/Y', $filters['end'])->startOfDay();
            if ($to->lt($from)) {
                throw ValidationException::withMessages(['end' => 'La fecha final debe ser igual o posterior a la inicial.']);
            }
            $query->where('date_entry', '>=', $from->toDateTimeString())
                ->where('date_entry', '<', $to->addDay()->toDateTimeString());
            $dates = 'DEL '.$filters['start'].' AL '.$filters['end'];
        }
        $rows = [];
        foreach ($query->get() as $entry) {
            $supplier = $entry->supplier;
            $category = $entry->category_invoice;
            $supplierName = $supplier && (int)$supplier->tenant_id === (int)$ids['tenant_id']
                ? $supplier->business_name : 'No tiene';
            $categoryName = $category && (int)$category->tenant_id === (int)$ids['tenant_id']
                && (int)$category->company_id === (int)$ids['company_id'] ? $category->name : 'No tiene';
            $rows[] = [
                'date' => $entry->date_entry ? Carbon::parse($entry->date_entry)->format('d-m-Y') : '',
                'order' => $entry->purchase_order !== null ? $entry->purchase_order : 'No tiene',
                'invoice' => $entry->invoice,
                'type_order' => $entry->type_order === 'purchase' || $entry->type_order === null ? 'Por compra' : 'Por servicio',
                'supplier' => $supplierName,
                'category' => $categoryName,
                'currency' => $entry->currency_invoice,
                'subtotal' => $entry->sub_total,
                'taxes' => $entry->taxes,
                'total' => $entry->total,
            ];
        }
        return ['invoices' => $rows, 'dates' => $dates];
    }
}
