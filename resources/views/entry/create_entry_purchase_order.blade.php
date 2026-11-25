@extends('layouts.appAdmin2')
@section('openEntryPurchase', 'menu-open')
@section('activeEntryPurchase', 'active')
@section('activeListOrdersInEntries', 'active')
@section('title', 'Ingreso desde orden de compra')
@section('page-header')<div class="next-page-heading"><span class="next-page-eyebrow">Compras · almacén</span><h1 class="page-title">Recibir orden de compra</h1><p class="next-page-description">Registra únicamente las cantidades que ingresan ahora.</p></div>@endsection
@section('page-title')<h5 class="card-title">Recibir orden {{ $orderPurchase->code }}</h5>@endsection
@section('page-breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="{{ route('order.purchase.list.indexV2') }}">Órdenes para almacén</a></li>
    <li class="breadcrumb-item active">Recepción</li>
</ol>
@endsection
@section('content')
@php
    $detailsById = $orderPurchase->details->keyBy('id');
    $canReceive = $receiptSummary['can_receive'] && !$warehouseContext['configuration_errors'];
@endphp
<div class="next-form-section">
<div id="receipt-errors" class="alert alert-danger d-none" role="alert"></div>
@foreach(array_merge($receiptSummary['errors'], $warehouseContext['configuration_errors']) as $error)
    <div class="alert alert-warning" role="alert">{{ $error }}</div>
@endforeach
@if($receiptSummary['state'] === 1)
    <div class="alert alert-success">La orden está completa. No tiene cantidades pendientes.</div>
@endif
<form id="receipt-form" action="{{ route('entry.purchase.order.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="purchase_order_id" value="{{ $orderPurchase->id }}">
    <input type="hidden" name="receipt_key" value="{{ $receiptKey }}">
    <input type="hidden" name="entry_context_tenant_id" value="{{ \App\Support\TenantContext::tenantId() }}">
    <input type="hidden" name="entry_context_company_id" value="{{ \App\Support\TenantContext::companyId() }}">
    <input type="hidden" name="entry_context_branch_id" value="{{ \App\Support\TenantContext::branchId() }}">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Datos del ingreso</h3></div>
        <div class="card-body next-form-section">
            <div class="form-row">
                <div class="col-sm-4 col-lg-3 form-group"><label>Orden</label><input aria-label="Orden" class="form-control" value="{{ $orderPurchase->code }}" readonly></div>
                <div class="col-sm-8 col-lg-7 form-group"><label>Proveedor</label><input aria-label="Proveedor" class="form-control" value="{{ optional($orderPurchase->supplier)->business_name }}" readonly></div>
                <div class="col-sm-4 col-lg-2 form-group"><label>Moneda</label><input aria-label="Moneda" class="form-control" value="{{ $orderPurchase->currency_order }}" readonly></div>
            </div>
            <div class="form-row align-items-end">
                <div class="col-sm-6 col-lg-3 form-group"><label for="receipt-date">Fecha del comprobante <span class="next-required">*</span></label><input id="receipt-date" class="form-control" name="date_invoice" value="{{ now()->format('d/m/Y') }}" placeholder="dd/mm/aaaa" required></div>
                <div class="col-sm-6 col-lg-3 form-group"><label for="receipt-invoice">Comprobante <span class="next-required">*</span></label><input id="receipt-invoice" class="form-control" name="invoice" minlength="5" maxlength="255" required></div>
                <div class="col-sm-6 col-lg-4 form-group"><label for="receipt-guide">Guía de remisión</label><input id="receipt-guide" class="form-control" name="referral_guide" minlength="5" maxlength="255"></div>
                <div class="col-sm-6 col-lg-2 form-group"><label class="mb-0"><input type="checkbox" name="deferred_invoice" value="on"> Comprobante diferido</label></div>
            </div>
            <div class="form-row">
                <div class="col-md-6 col-xl-4 form-group"><label for="receipt-image">Archivo del comprobante</label><input id="receipt-image" class="form-control-file" type="file" name="image" accept=".jpg,.jpeg,.png,.pdf"></div>
                <div class="col-md-6 col-xl-4 form-group"><label for="receipt-image-ob">Archivo de observación</label><input id="receipt-image-ob" class="form-control-file" type="file" name="imageOb" accept=".jpg,.jpeg,.png,.pdf"></div>
                <div class="col-xl-4 form-group"><label for="receipt-observation">Observación</label><textarea id="receipt-observation" class="form-control" name="observation" maxlength="2000" rows="2"></textarea></div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Productos de la orden — {{ $receiptSummary['state_text'] }}</h3></div>
        <div class="card-body p-0">
            <div class="next-list-summary">Indique cuánto recibe ahora. Deje cero en los productos que todavía no llegaron.</div>
            <div class="table-responsive" tabindex="0" aria-label="Productos de la orden; desplazamiento horizontal"><table class="table table-bordered table-hover table-sm next-data-table mb-0">
                <thead><tr><th>Producto / variante</th><th class="text-center">Pedido</th><th class="text-center">Recibido</th><th class="text-center">Pendiente</th><th class="text-center text-nowrap">Ingresa ahora</th><th>Almacén</th><th>Lote</th><th class="text-center">Vencimiento</th></tr></thead>
                <tbody>
                @foreach($receiptSummary['lines'] as $line)
                    @php
                        $detail = $detailsById->get($line['order_purchase_detail_id']);
                        $stock = $detail ? $detail->stockItem : null;
                        $material = $detail ? $detail->material : null;
                        $disabled = !$canReceive || (float) $line['pending'] <= 0;
                        $integerUnits = $material && (int) $material->tipo_venta_id === 3;
                    @endphp
                    <tr data-receipt-line="{{ $line['order_purchase_detail_id'] }}">
                        <td>{{ $stock ? $stock->display_name : ($material ? $material->full_name : 'Producto no disponible') }}<br><small>{{ $stock ? $stock->sku : '' }}</small></td>
                        <td class="text-center">{{ $line['ordered'] }}</td><td class="text-center">{{ $line['received'] }}</td><td class="text-center font-weight-bold">{{ $line['pending'] }}</td>
                        <td><input aria-label="Cantidad que ingresa" class="form-control text-center" type="number" min="0" max="{{ $line['pending'] }}" step="{{ $integerUnits ? '1' : '0.01' }}" value="0" data-receipt-quantity @if($disabled) disabled @endif></td>
                        <td><select aria-label="Almacén de destino" class="form-control" data-receipt-warehouse @if($disabled) disabled @endif>
                            <option value="">Seleccione</option>
                            @foreach($warehouseContext['warehouses'] as $warehouse)
                                <option value="{{ $warehouse['id'] }}" data-location="{{ $warehouse['location_id'] }}" @if(!$warehouse['selectable']) disabled @endif @if($warehouse['id'] === $warehouseContext['default_warehouse_id']) selected @endif>{{ $warehouse['name'] }}{{ $warehouse['is_general'] ? ' (general)' : '' }}</option>
                            @endforeach
                        </select></td>
                        <td><input aria-label="Lote" class="form-control" data-receipt-lot maxlength="255" @if($disabled) disabled @endif></td>
                        <td><input aria-label="Vencimiento" class="form-control" type="date" data-receipt-expiration @if($disabled) disabled @endif></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </div>
    </div>
    <div class="next-list-toolbar justify-content-end">
        <div class="next-toolbar-actions flex-wrap">
        <a class="btn btn-outline-secondary" href="{{ route('order.purchase.list.indexV2') }}">Volver</a>
        @if($canReceive)<button type="submit" class="btn btn-primary" id="receipt-save"><i class="fas fa-save" aria-hidden="true"></i> Guardar ingreso</button>@endif
        </div>
    </div>
</form>
</div>
@endsection
@section('scripts')
<script src="{{ asset('js/entry/normalPurchaseReceipt.js') }}"></script>
@endsection
