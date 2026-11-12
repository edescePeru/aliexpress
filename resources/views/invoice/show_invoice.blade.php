@extends('layouts.appAdmin2')
@section('openInvoice', 'menu-open')
@section('activeInvoice', 'active')
@section('activeListInvoice', 'active')
@section('title', 'Detalle de factura financiera')
@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Finanzas</span><h1 class="page-title">Detalle de factura financiera</h1><p class="next-page-description">Datos e importes del comprobante de la empresa activa.</p></div>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>{{ $entry->invoice }}</strong><span>Consulta de solo lectura.</span></div><div class="next-toolbar-actions flex-wrap"><span class="badge {{ $entry->state_annulled ? 'badge-danger' : 'badge-success' }}">{{ $entry->state_annulled ? 'Anulada' : 'Activa' }}</span><a class="btn btn-outline-secondary btn-sm" href="{{ route($entry->state_annulled ? 'invoice.index.annulled' : 'invoice.index') }}">Volver al listado</a></div></div>
@endsection
@section('content')
<div class="next-form-section"><h2 class="h6">Datos generales</h2><dl class="row mb-0">
@foreach(['Fecha' => optional($entry->date_entry)->format('d/m/Y'), 'Documento' => $entry->invoice, 'Proveedor' => optional($entry->supplier)->business_name, 'Categoría' => optional($entry->category_invoice)->name, 'Referencia de orden' => $entry->purchase_order, 'Tipo de orden' => ($entry->type_order === 'service' ? 'Por servicio' : 'Por compra'), 'Moneda' => $entry->currency_invoice, 'Diferido' => ($entry->deferred_invoice === 'off' ? 'No' : 'Sí')] as $label => $value)
<div class="col-md-6 col-lg-3 mb-3"><dt class="text-muted">{{ $label }}</dt><dd class="text-break mb-0">{{ $value ?? 'No registrado' }}</dd></div>
@endforeach
<div class="col-12"><dt class="text-muted">Observación</dt><dd class="text-break mb-0">{{ $entry->observation ?: 'Sin observaciones' }}</dd></div>
</dl></div>
<div class="next-form-section"><h2 class="h6">Líneas de la factura · {{ $entry->currency_invoice }}</h2><p class="text-muted mb-0">Los importes proceden del backend. “Sin snapshot” identifica líneas históricas sin clasificación fiscal almacenada.</p></div>
<div class="table-responsive" tabindex="0" aria-label="Líneas de factura; desplazamiento horizontal"><table class="table table-bordered table-hover table-sm next-data-table"><thead><tr><th>Descripción</th><th class="text-center">Cantidad</th><th class="text-center">Unidad</th><th class="text-right">Precio unitario</th><th class="text-right">Subtotal</th><th class="text-center">Condición fiscal</th><th class="text-right">Impuesto snapshot</th><th class="text-right">Impuestos backend</th><th class="text-right">Total</th></tr></thead><tbody>
@forelse($entry->details as $detail)
<tr><td>{{ $detail->material_description }}</td><td class="text-center">{{ $detail->entered_quantity }}</td><td class="text-center">{{ $detail->unit }}</td><td class="text-right">{{ $detail->unit_price }}</td><td class="text-right">{{ $detail->sub_total }}</td><td class="text-center">{{ $detail->finance_tax_kind ?? 'Sin snapshot' }}</td><td class="text-right">{{ $detail->finance_tax_amount ?? 'No registrado' }}</td><td class="text-right">{{ $detail->taxes }}</td><td class="text-right">{{ $detail->total }}</td></tr>
@empty
<tr><td colspan="9" class="next-table-empty">No hay líneas registradas.</td></tr>
@endforelse
</tbody></table></div>
<div class="next-form-section"><div class="row"><div class="col-md-4"><span class="text-muted">Subtotal ({{ $entry->currency_invoice }})</span><p class="font-weight-bold">{{ $entry->sub_total }}</p></div><div class="col-md-4"><span class="text-muted">Impuestos backend ({{ $entry->currency_invoice }})</span><p class="font-weight-bold">{{ $entry->taxes }}</p></div><div class="col-md-4"><span class="text-muted">Total ({{ $entry->currency_invoice }})</span><p class="font-weight-bold">{{ $entry->total }}</p></div></div>
@if($entry->details->contains(function ($line) { return $line->finance_tax_amount === null; }))
<p class="text-muted mb-0">Hay líneas sin snapshot fiscal. El backend aplica su cálculo histórico a esas líneas; estos importes no certifican una tasa fiscal almacenada.</p>
@endif
</div>
<div class="next-form-section"><h2 class="h6">Documento adjunto</h2><p class="text-muted mb-0">{{ $entry->image ? 'Adjunto registrado. Acceso documental pendiente de validación.' : 'Sin adjunto registrado.' }}</p></div>
@endsection
