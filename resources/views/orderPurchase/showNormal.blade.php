@extends('layouts.appAdmin2')
@section('openOrderPurchaseGeneral', 'menu-open')
@section('activeOrderPurchaseGeneral', 'active')
@section('activeListOrderPurchaseNormal', 'active')
@section('title', 'Orden de compra normal')
@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Compras</span><h1 class="page-title">Orden {{ $order->code ?: ('#' . $order->id) }}</h1><p class="next-page-description">Orden normal · consulta de la empresa activa.</p></div>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Detalle de orden</strong><span>Solo lectura · {{ $order->currency_order ?: 'Moneda no registrada' }}</span></div><div class="next-toolbar-actions"><a href="{{ route('order.purchase.normal.indexV2') }}" class="btn btn-outline-secondary btn-sm">Volver al listado</a></div></div>
@endsection
@section('content')
<section class="next-form-section" aria-label="Datos generales">
<h2 class="h6">Datos generales</h2>
<div class="row">
<div class="col-sm-6 col-lg-3"><p class="text-muted mb-1">Proveedor</p><p>{{ optional($order->supplier)->business_name ?: 'No registrado / no disponible' }}</p></div>
<div class="col-sm-6 col-lg-3"><p class="text-muted mb-1">Fecha de orden</p><p>{{ $order->date_order ? $order->date_order->format('d/m/Y') : 'No registrada' }}</p></div>
<div class="col-sm-6 col-lg-3"><p class="text-muted mb-1">Fecha de llegada</p><p>{{ $order->date_arrival ? $order->date_arrival->format('d/m/Y') : 'No registrada' }}</p></div>
<div class="col-sm-6 col-lg-3"><p class="text-muted mb-1">Estado de orden</p><p>
@if($order->status_order === 'stand_by')<span class="badge badge-warning">Pendiente</span>
@elseif($order->status_order === 'send')<span class="badge badge-primary">Enviado</span>
@elseif($order->status_order === 'pick_up')<span class="badge badge-success">Recogido</span>
@else{{ $order->status_order ?: 'No registrado' }}@endif
</p></div>
<div class="col-sm-6 col-lg-3"><p class="text-muted mb-1">Creado por</p><p>{{ optional($order->createdUser)->name ?? 'No registrado / usuario no disponible' }}</p></div>
<div class="col-sm-6 col-lg-3"><p class="text-muted mb-1">Última modificación por</p><p>{{ optional($order->updatedUser)->name ?? 'No registrado / usuario no disponible' }}</p></div>
<div class="col-sm-6 col-lg-3"><p class="text-muted mb-1">Forma de pago</p><p>{{ optional($order->deadline)->description ?: 'No registrada' }}</p></div>
<div class="col-sm-6 col-lg-3"><p class="text-muted mb-1">Cotización de proveedor</p><p>{{ $order->quote_supplier ?: 'No registrada' }}</p></div>
@if($order->approved_by)<div class="col-sm-6"><p class="text-muted mb-1">Aprobador consignado anteriormente</p><p>{{ optional($order->approved_user)->name ?? 'Usuario no disponible' }}</p></div>@endif
<div class="col-sm-6"><p class="text-muted mb-1">Cotización</p><p class="text-break">{{ $order->quote_id ? (optional($order->quote)->code . ' ' . optional($order->quote)->description_quote) : 'Sin trabajo' }}</p></div>
<div class="col-12"><p class="text-muted mb-1">Observación</p><p class="text-break mb-0">{{ $order->observation ?: 'Sin observaciones' }}</p></div>
</div></section>
<div class="next-list-summary"><strong>Detalles de compra</strong><span>{{ $details->count() }} líneas · {{ $order->currency_order }}</span></div>
<div class="next-list-content"><div class="table-responsive" tabindex="0" aria-label="Items de compra; desplazamiento horizontal"><table class="table table-bordered table-hover table-sm next-data-table"><thead><tr><th class="text-center">ID</th><th class="text-center">Código / SKU</th><th>Material / variante</th><th class="text-center">Unidad</th><th class="text-center">Cantidad</th><th class="text-right">Precio C/IGV</th><th class="text-right">IGV registrado</th><th class="text-right">Total registrado</th></tr></thead><tbody id="body-materials">
@forelse($details as $detail)
<tr><td class="text-center">{{ optional($detail->stockItem)->id ?? $detail->material_id }}</td><td class="text-center">{{ optional($detail->stockItem)->sku ?? optional($detail->material)->code ?? 'No disponible' }}</td><td>{{ optional($detail->stockItem)->display_name ?? optional($detail->material)->full_description ?? 'Material no disponible' }}<small class="d-block text-muted">{{ $detail->stock_item_id ? 'StockItem' : 'Material histórico' }}</small></td><td class="text-center">{{ optional(optional($detail->material)->unitMeasure)->description ?? '—' }}</td><td class="text-center">{{ $detail->quantity }}</td><td class="text-right">{{ $detail->price === null ? 'No registrado' : number_format($detail->price, 2) }}</td><td class="text-right">{{ $detail->igv === null ? 'No registrado' : number_format($detail->igv, 2) }}</td><td class="text-right">{{ $detail->total_detail === null ? 'No registrado' : number_format($detail->total_detail, 2) }}</td></tr>
@empty<tr><td colspan="8" class="next-table-empty">Esta orden no tiene detalles registrados.</td></tr>@endforelse
</tbody></table></div></div>
<section class="next-form-section" aria-label="Importes registrados"><div class="row"><div class="col-sm-4"><p class="text-muted mb-1">Moneda</p><strong>{{ $order->currency_order ?: 'No registrada' }}</strong></div><div class="col-sm-4"><p class="text-muted mb-1">IGV registrado</p><strong id="taxes">{{ $order->igv === null ? 'No registrado' : number_format($order->igv, 2) }}</strong></div><div class="col-sm-4"><p class="text-muted mb-1">Total registrado</p><strong id="total">{{ $order->total === null ? 'No registrado' : number_format($order->total, 2) }}</strong></div></div></section>
@endsection
