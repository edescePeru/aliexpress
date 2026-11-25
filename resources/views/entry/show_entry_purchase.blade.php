@extends('layouts.appAdmin2')
@section('openEntryPurchase', 'menu-open')
@section('activeEntryPurchase', 'active')
@section('title', 'Detalle de entrada por compra')
@section('page-header')
<div class="next-page-heading">
    <span class="next-page-eyebrow">Compras · entradas</span>
    <h1 class="page-title">Detalle de entrada</h1>
    <p class="next-page-description">Consulta de la entrada de la empresa activa.</p>
</div>
@endsection
@section('page-breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="{{ route('entry.purchase.indexV2') }}">Entradas por compra</a></li>
    <li class="breadcrumb-item active">Entrada #{{ $entry->id }}</li>
</ol>
@endsection
@section('page-title')
<div class="next-page-toolbar">
    <div class="next-toolbar-context"><strong>Entrada #{{ $entry->id }}</strong><span>Consulta de solo lectura.</span></div>
    <div class="next-toolbar-actions flex-wrap">
        <span class="badge {{ $entry->state_annulled ? 'badge-danger' : 'badge-success' }}">{{ $entry->state_annulled ? 'Anulada' : 'Activa' }}</span>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('entry.purchase.indexV2') }}">Volver al listado</a>
    </div>
</div>
@endsection
@section('content')
<section class="next-form-section" aria-labelledby="entry-summary">
    <h2 class="h6" id="entry-summary">Proveedor y documento</h2>
    <dl class="row mb-0">
        @foreach([
            'Proveedor' => optional($suppliers->firstWhere('id', $entry->supplier_id))->business_name,
            'Factura' => $entry->invoice,
            'Fecha de entrada' => optional($entry->date_entry)->format('d/m/Y'),
            'Moneda' => $entry->currency_invoice,
            'Guía de remisión' => $entry->referral_guide,
            'Referencia de orden de compra' => $entry->purchase_order,
            'Diferido' => ($entry->deferred_invoice === 'on' ? 'Sí' : 'No'),
            'Tipo de ingreso' => 'Por compra'
        ] as $label => $value)
        <div class="col-sm-6 col-lg-3 mb-3"><dt class="text-muted">{{ $label }}</dt><dd class="text-break mb-0">{{ $value === null || $value === '' ? 'No registrado' : $value }}</dd></div>
        @endforeach
    </dl>
</section>
<section aria-labelledby="entry-products">
    <div class="next-form-section"><h2 class="h6 mb-0" id="entry-products">Productos · {{ $entry->currency_invoice ?: 'Moneda no registrada' }}</h2></div>
    <div class="table-responsive" tabindex="0" aria-label="Productos de la entrada; desplazamiento horizontal">
        <table class="table table-bordered table-hover table-sm next-data-table">
            <thead><tr>
                <th class="text-center">Código / SKU</th><th>Descripción</th><th class="text-center">Cantidad</th><th class="text-center">Unidad</th>
                <th class="text-right">Precio unitario</th><th class="text-right">Subtotal</th><th class="text-right">Impuestos</th><th class="text-right">Importe</th>
            </tr></thead>
            <tbody id="body-materials">
            @forelse($entry->details as $detail)
                <tr>
                    <td class="text-center" data-code>{{ $detail->material_code }}</td><td data-description>{{ $detail->material_description }}</td>
                    <td class="text-center" data-quantity>{{ $detail->entered_quantity }}</td><td class="text-center" data-unit>{{ $detail->unit }}</td>
                    <td class="text-right" data-price>{{ $detail->unit_price }}</td><td class="text-right" data-subtotal>{{ $detail->sub_total }}</td>
                    <td class="text-right" data-taxes>{{ $detail->taxes }}</td><td class="text-right" data-total>{{ $detail->total }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="next-table-empty">No hay productos registrados en esta entrada.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
<section class="next-form-section" aria-labelledby="entry-totals">
    <h2 class="h6" id="entry-totals">Resumen de factura</h2>
    <dl class="row mb-0">
        @foreach(['Subtotal' => $entry->sub_total, 'Impuestos' => $entry->taxes, 'Total' => $entry->total] as $label => $value)
        <div class="col-md-4 mb-2"><dt class="text-muted">{{ $label }} ({{ $entry->currency_invoice ?: 'Moneda no registrada' }})</dt><dd class="font-weight-bold mb-0">{{ $value }}</dd></div>
        @endforeach
    </dl>
</section>
<section class="next-form-section" aria-labelledby="entry-observations">
    <h2 class="h6" id="entry-observations">Observaciones</h2>
    <p class="text-break mb-0">{{ $entry->observation ?: 'Sin observaciones.' }}</p>
</section>
<section class="next-form-section" aria-labelledby="entry-documents">
    <h2 class="h6" id="entry-documents">Adjuntos</h2>
    @include('entry.partials.document-links', ['documentPermission' => 'list_entryPurchase', 'includeObservation' => true])
</section>
@endsection
