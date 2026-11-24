@extends('layouts.appAdmin2')
@section('openOrderPurchaseGeneral', 'menu-open')
@section('activeOrderPurchaseGeneral', 'active')
@section('activeListOrderPurchaseFinanceDelete', 'active')
@section('title', 'Orden financiera · Histórico')
@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Compras · Finanzas</span><h1 class="page-title">Orden {{ $order->code ?: ('#' . $order->id) }}</h1><p class="next-page-description">Consulta histórica de la empresa activa · Solo lectura.</p></div>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Detalle de orden financiera</strong><span>{{ $order->currency_order ?: 'Moneda no registrada' }}</span></div><div class="next-toolbar-actions flex-wrap">
@can('destroy_orderPurchaseFinance')<a class="btn btn-outline-secondary btn-sm" href="{{ route('order.purchase.finance.delete') }}">Órdenes eliminadas</a>@endcan
@can('list_orderPurchaseFinance')<a class="btn btn-outline-secondary btn-sm" href="{{ route('order.purchase.finance.list.regularize') }}">Órdenes regularizadas</a>@endcan
</div></div>
@endsection
@section('content')
<section class="next-form-section" aria-label="Datos generales">
<h2 class="h6">Datos generales</h2>
<div class="row">
<div class="col-sm-6 col-lg-3"><p class="text-muted mb-1">Registro</p><p><span class="badge badge-{{ $order->deleted_at ? 'danger' : 'secondary' }}">{{ $order->deleted_at ? 'Eliminada' : 'Activa' }}</span></p></div>
<div class="col-sm-6 col-lg-3"><p class="text-muted mb-1">Fecha de eliminación</p><p>{{ $order->deleted_at ? $order->deleted_at->format('d/m/Y H:i') : 'No eliminada' }}</p></div>
<div class="col-sm-6 col-lg-3"><p class="text-muted mb-1">Fecha de orden</p><p>{{ $order->date_order ? $order->date_order->format('d/m/Y') : 'No registrada' }}</p></div>
<div class="col-sm-6 col-lg-3"><p class="text-muted mb-1">Fecha de entrega</p><p>{{ $order->date_delivery ? $order->date_delivery->format('d/m/Y') : 'No registrada' }}</p></div>
<div class="col-sm-6 col-lg-3"><p class="text-muted mb-1">Proveedor</p><p class="text-break">{{ optional($order->supplier)->business_name ?: 'No registrado / no disponible' }}</p></div>
<div class="col-sm-6 col-lg-3"><p class="text-muted mb-1">Aprobado por</p><p class="text-break">{{ optional($order->approved_user)->name ?: 'Sin aprobar / no disponible' }}</p></div>
<div class="col-sm-6 col-lg-3"><p class="text-muted mb-1">Plazo de pago</p><p class="text-break">{{ optional($order->deadline)->description ?: 'No registrado' }}</p></div>
<div class="col-sm-6 col-lg-3"><p class="text-muted mb-1">Regularización</p><p><span class="badge badge-{{ $order->regularize === 'r' ? 'success' : 'secondary' }}">{{ ['r' => 'Regularizada', 'nr' => 'No regularizada'][$order->regularize] ?? ($order->regularize ?: 'No registrada') }}</span></p></div>
<div class="col-sm-6"><p class="text-muted mb-1">Cotización de proveedor</p><p class="text-break">{{ $order->quote_supplier ?: 'No registrada' }}</p></div>
<div class="col-sm-6"><p class="text-muted mb-1">Cotización vinculada (ID)</p><p>{{ $order->quote_id ?? 'Sin trabajo' }}</p></div>
<div class="col-12"><p class="text-muted mb-1">Observación</p><p class="text-break mb-0">{{ $order->observation ?: 'Sin observaciones' }}</p></div>
</div></section>
<div class="next-list-summary"><strong>Líneas registradas</strong><span>{{ $details->count() }} líneas · {{ $order->currency_order ?: 'Moneda no registrada' }}</span></div>
<div class="next-list-content"><div class="table-responsive" tabindex="0" aria-label="Líneas financieras; desplazamiento horizontal local"><table class="table table-bordered table-hover table-sm next-data-table"><thead><tr><th class="text-center">ID</th><th>Descripción</th><th class="text-center">Unidad</th><th class="text-center">Cantidad</th><th class="text-right">Precio registrado</th><th class="text-right">IGV registrado</th><th class="text-right">Total registrado</th><th class="text-center">Registro</th></tr></thead><tbody id="body-materials">
@forelse($details as $detail)
<tr><td class="text-center">{{ $detail->id }}</td><td>{{ $detail->material ?? 'No registrada' }}</td><td class="text-center">{{ $detail->unit ?? '—' }}</td><td class="text-center">{{ $detail->quantity ?? 'No registrada' }}</td><td class="text-right">{{ $detail->price ?? 'No registrado' }}</td><td class="text-right">{{ $detail->igv ?? 'No registrado' }}</td><td class="text-right">{{ $detail->total_detail ?? 'No registrado' }}</td><td class="text-center"><span class="badge badge-{{ $detail->deleted_at ? 'danger' : 'secondary' }}">{{ $detail->deleted_at ? 'Eliminada' : 'Activa' }}</span></td></tr>
@empty<tr><td colspan="8" class="next-table-empty">Esta orden no tiene líneas registradas.</td></tr>@endforelse
</tbody></table></div></div>
<section class="next-form-section" aria-label="Importes registrados"><div class="row"><div class="col-sm-4"><p class="text-muted mb-1">Moneda</p><strong>{{ $order->currency_order ?: 'No registrada' }}</strong></div><div class="col-sm-4"><p class="text-muted mb-1">IGV registrado</p><strong id="taxes">{{ $order->igv ?? 'No registrado' }}</strong></div><div class="col-sm-4"><p class="text-muted mb-1">Total registrado</p><strong id="total">{{ $order->total ?? 'No registrado' }}</strong></div></div></section>
@endsection
