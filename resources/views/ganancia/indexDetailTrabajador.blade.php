@extends('layouts.appAdmin2')
@section('openGanancias', 'menu-open')
@section('activeGanancias', 'active')
@section('activeGananciaDiariaTrabajador', 'active')
@section('title', 'Detalle de ganancia')
@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Reportes · Ventas</span><h1 class="page-title">Ganancia de la venta #{{ $sale->id }}</h1><p class="next-page-description">Consulta de solo lectura · {{ $sale->currency }}</p></div>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Detalle de ganancia</strong><span>Costos registrados en las líneas de venta.</span></div><div class="next-toolbar-actions"><a href="{{ route('ganancia.index.trabajador') }}" class="btn btn-outline-secondary btn-sm">Volver al listado</a></div></div>
@endsection
@section('content')
<section class="next-form-section" aria-label="Identidad de venta"><div class="row">
<div class="col-sm-6 col-lg-3"><p class="text-muted mb-1">Fecha de venta</p><p>{{ $sale->date_sale }}</p></div>
<div class="col-sm-6 col-lg-3"><p class="text-muted mb-1">Usuario que registró la venta</p><p>{{ $report->author($sale) }}</p></div>
<div class="col-sm-6 col-lg-3"><p class="text-muted mb-1">Trabajador opcional</p><p>{{ $sale->worker_id ? ('Trabajador #' . $sale->worker_id) : 'No registrado' }}</p></div>
<div class="col-sm-6 col-lg-3"><p class="text-muted mb-1">Cliente</p><p>{{ $sale->nombre_cliente ?: 'No registrado' }}</p></div>
</div><small class="text-muted">Sin costo registrado indica que no hay un costo histórico suficiente para calcular la ganancia. Un costo cero se muestra como 0.00.</small></section>
<div class="next-list-content"><div class="table-responsive" tabindex="0" aria-label="Detalle de ganancia; desplazamiento horizontal"><table class="table table-bordered table-hover table-sm next-data-table"><thead><tr><th>Línea</th><th class="text-center">Cantidad</th><th class="text-right">Vendido (S/)</th><th class="text-right">Costo (S/)</th><th class="text-right">Ganancia (S/)</th></tr></thead><tbody>
@forelse($sale->details as $detail)
@php([$quantity, $sold, $cost, $profit] = $report->line($detail))
<tr><td>{{ $detail->description ?: ('#' . $detail->id) }}</td><td class="text-center">{{ $quantity }}</td><td class="text-right">{{ number_format($sold, 2) }}</td><td class="text-right">@if($cost === null)<span class="badge badge-secondary">Sin costo registrado</span>@else{{ number_format($cost, 2) }}@endif</td><td class="text-right">@if($profit === null)<span class="badge badge-secondary">Sin costo registrado</span>@else{{ number_format($profit, 2) }}@endif</td></tr>
@empty
<tr><td colspan="5" class="next-table-empty">Esta venta no tiene líneas registradas.</td></tr>
@endforelse
</tbody></table></div></div>
@endsection
