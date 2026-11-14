@extends('layouts.appAdmin2')
@section('openQuote') menu-open @endsection
@section('activeQuote') active @endsection
@section('activeGeneralQuote') active @endsection
@section('title') Cotización {{ $quote->code }} @endsection
@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Ventas · consulta</span><h1 class="page-title">Cotización {{ $quote->code }}</h1><p class="next-page-description">{{ $quote->description_quote }}</p></div>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Detalle de cotización</strong><span>{{ $quote->branch_id === null ? 'Histórico — sin sucursal' : 'Cotización del local activo' }}</span></div><div class="next-toolbar-actions">
<a href="{{ route($quote->branch_id === null ? 'quoteSale.pendingBranches' : 'quoteSale.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1" aria-hidden="true"></i> Volver al listado</a>
</div></div>
@endsection
@section('page-breadcrumb')
<ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('quoteSale.index') }}">Cotizaciones</a></li><li class="breadcrumb-item active" aria-current="page">Detalle</li></ol>
@endsection
@section('content')
@if($quote->branch_id === null)
<div class="alert alert-warning" role="status">Histórico — sin sucursal. Disponible para consulta; la asignación y revisión de reservas no se realizan desde esta pantalla.</div>
@endif
<section class="next-form-section" aria-labelledby="quote-identity">
    <div class="next-section-header"><div><h2 id="quote-identity">Identidad e información comercial</h2><p>Datos registrados en la cotización.</p></div></div>
    <dl class="row mb-0">
        <dt class="col-sm-3">Estado registrado</dt><dd class="col-sm-3"><span class="badge badge-secondary">{{ $quote->state }}</span></dd>
        <dt class="col-sm-3">Operación</dt><dd class="col-sm-3">{{ $quote->state_active === 'close' ? 'Finalizada' : $quote->state_active }}</dd>
        <dt class="col-sm-3">Código</dt><dd class="col-sm-9 text-break">{{ $quote->code }}</dd>
        <dt class="col-sm-3">Descripción</dt><dd class="col-sm-9 text-break">{{ $quote->description_quote }}</dd>
        <dt class="col-sm-3">Fecha de cotización</dt><dd class="col-sm-3">{{ $quote->date_quote ? $quote->date_quote->format('d/m/Y') : '—' }}</dd>
        <dt class="col-sm-3">Válido hasta</dt><dd class="col-sm-3">{{ $quote->date_validate ? $quote->date_validate->format('d/m/Y') : '—' }}</dd>
        <dt class="col-sm-3">Forma de pago</dt><dd class="col-sm-3">{{ optional($quote->deadline)->description ?: 'No tiene forma de pago' }}</dd>
        <dt class="col-sm-3">Tiempo de entrega</dt><dd class="col-sm-3">{{ $quote->time_delivery }} días</dd>
    </dl>
</section>
<section class="next-form-section" aria-labelledby="quote-customer">
    <div class="next-section-header"><h2 id="quote-customer">Cliente y contacto</h2></div>
    <dl class="row mb-0"><dt class="col-sm-3">Cliente</dt><dd class="col-sm-9 text-break">{{ optional($quote->customer)->business_name ?: 'No tiene cliente' }}</dd><dt class="col-sm-3">Contacto</dt><dd class="col-sm-9 text-break">{{ optional($quote->contact)->name ?: 'No tiene contacto' }}</dd><dt class="col-sm-3">Observaciones</dt><dd class="col-sm-9 text-break">{!! nl2br(e($quote->observations)) !!}</dd></dl>
</section>
@forelse($quote->equipments as $equipment)
<section class="next-form-section" aria-labelledby="quote-lines-{{ $equipment->id }}">
    <div class="next-section-header"><div><h2 id="quote-lines-{{ $equipment->id }}">Productos{{ $quote->equipments->count() > 1 ? ' · '.($loop->iteration) : '' }}</h2><p class="text-break">{!! nl2br(e($equipment->detail)) !!}</p></div></div>
    <p class="small text-muted">Color del producto: gris, sin cambios; azul, precio actualizado; rojo, sin stock según el indicador actual. Los ítems seleccionados corresponden al registro de la cotización.</p>
    <div class="table-responsive" tabindex="0" role="region" aria-label="Productos cotizados">
        <table class="table table-bordered table-hover table-sm next-data-table">
            <thead><tr><th scope="col" class="text-left">Descripción</th><th scope="col" class="text-center">Presentación</th><th scope="col" class="text-center">Unidad</th><th scope="col" class="text-center">Cantidad</th>@can('showPrices_quote')<th scope="col" class="text-right">V/U</th><th scope="col" class="text-right">P/U</th><th scope="col" class="text-right">Importe</th>@endcan</tr></thead>
            <tbody>
            @forelse($equipment->consumables as $consumable)
                @php($itemCodes = $quoteItemCodesByConsumable[$consumable->id] ?? [])
                <tr>
                    <td class="text-left"><span @if(optional($consumable->stockItem)->ui_color) style="color: {{ $consumable->stockItem->ui_color }}" @endif>{{ optional($consumable->stockItem)->display_name ?: 'Producto histórico no disponible' }}</span>
                        @if(count($itemCodes))<details class="small text-muted mt-1"><summary>Ítems seleccionados ({{ count($itemCodes) }})</summary><span class="text-break">{{ implode(', ', $itemCodes) }}</span></details>@endif
                    </td>
                    <td class="text-center">{{ $consumable->material_presentation_id === null ? 'Unidad' : $consumable->units_per_pack.' Und' }}</td>
                    <td class="text-center">{{ optional(optional($consumable->material)->unitMeasure)->description ?: '—' }}</td>
                    <td class="text-center">{{ $consumable->material_presentation_id === null ? $consumable->quantity : $consumable->packs }}</td>
                    @can('showPrices_quote')<td class="text-right">{{ round($consumable->valor_unitario, 2) }}</td><td class="text-right">{{ round($consumable->price, 2) }}</td><td class="text-right">{{ round($consumable->total, 2) }}</td>@endcan
                </tr>
            @empty
                <tr><td colspan="{{ auth()->user()->can('showPrices_quote') ? 7 : 4 }}" class="text-center">No hay productos registrados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="next-section-header mt-4"><h2>Servicios adicionales</h2></div>
    <div class="table-responsive" tabindex="0" role="region" aria-label="Servicios adicionales">
        <table class="table table-bordered table-hover table-sm next-data-table">
            <thead><tr><th scope="col" class="text-left">Descripción</th><th scope="col" class="text-center">Unidad</th><th scope="col" class="text-center">Cantidad</th>@can('showPrices_quote')<th scope="col" class="text-right">V/U sin IGV</th><th scope="col" class="text-right">P/U con IGV</th><th scope="col" class="text-right">Importe</th>@endcan<th scope="col" class="text-center">Facturable</th></tr></thead>
            <tbody>
            @forelse($equipment->workforces as $workforce)
                <tr><td class="text-left">{{ $workforce->description }}</td><td class="text-center">{{ $workforce->unit }}</td><td class="text-center">{{ rtrim(rtrim(number_format((float) $workforce->quantity, 2, '.', ''), '0'), '.') }}</td>
                    @can('showPrices_quote')<td class="text-right">{{ round((float) $workforce->price / (1 + ($igv / 100)), 2) }}</td><td class="text-right">{{ round($workforce->price, 2) }}</td><td class="text-right">{{ round($workforce->total, 2) }}</td>@endcan
                    <td class="text-center"><span class="badge {{ ($workforce->billable ?? true) ? 'badge-success' : 'badge-secondary' }}">{{ ($workforce->billable ?? true) ? 'Sí' : 'No' }}</span></td></tr>
            @empty
                <tr><td colspan="{{ auth()->user()->can('showPrices_quote') ? 7 : 4 }}" class="text-center">No hay servicios adicionales registrados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@empty
<section class="next-form-section"><p class="text-muted mb-0">No hay líneas registradas en esta cotización.</p></section>
@endforelse
<section class="next-form-section" aria-labelledby="quote-discount">
    <div class="next-section-header"><h2 id="quote-discount">Descuento global</h2></div>
    <dl class="row mb-0"><dt class="col-sm-2">Tipo</dt><dd class="col-sm-2">{{ ($quote->discount_type ?? 'amount') === 'amount' ? 'Monto (S/)' : 'Porcentaje (%)' }}</dd><dt class="col-sm-2">Valor</dt><dd class="col-sm-2">{{ $quote->discount_input_value ?? 0 }}</dd><dt class="col-sm-2">Modo de ingreso</dt><dd class="col-sm-2">{{ ($quote->discount_input_mode ?? 'without_igv') === 'without_igv' ? 'Sin IGV (base)' : 'Con IGV (del total)' }}</dd></dl>
</section>
@can('showPrices_quote')
<section class="next-form-section" aria-labelledby="quote-totals">
    <div class="next-section-header"><h2 id="quote-totals">Resumen de cotización</h2></div>
    <div class="row justify-content-end"><div class="col-12 col-lg-6"><table class="table table-sm mb-0"><tbody>
        <tr><th scope="row" class="text-left">Descuento (−)</th><td class="text-right">{{ $currency }} <span id="descuento">{{ round($quote->descuento, 2) }}</span></td></tr>
        <tr><th scope="row" class="text-left">Gravada</th><td class="text-right">{{ $currency }} <span id="gravada">{{ round($quote->gravada, 2) }}</span></td></tr>
        <tr><th scope="row" class="text-left">IGV {{ $igv }}%</th><td class="text-right">{{ $currency }} <span id="igv_total">{{ round($quote->igv_total, 2) }}</span></td></tr>
        <tr><th scope="row" class="text-left">Total</th><td class="text-right font-weight-bold">{{ $currency }} <span id="total_importe">{{ round($quote->total_importe, 2) }}</span></td></tr>
    </tbody></table></div></div>
</section>
@endcan
@endsection
