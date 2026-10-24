@extends('layouts.appAdmin2')
@section('openEntryPurchase', 'menu-open')
@section('activeEntryPurchase', 'active')
@section('activeListOrdersInEntries', 'active')
@section('title', 'Ingreso desde orden de compra')
@section('page-header')<h1 class="page-title">Ingreso desde orden de compra</h1>@endsection
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
    <div class="card card-success">
        <div class="card-header"><h3 class="card-title">Datos del ingreso</h3></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 form-group"><label>Orden</label><input class="form-control" value="{{ $orderPurchase->code }}" readonly></div>
                <div class="col-md-5 form-group"><label>Proveedor</label><input class="form-control" value="{{ optional($orderPurchase->supplier)->business_name }}" readonly></div>
                <div class="col-md-3 form-group"><label>Moneda</label><input class="form-control" value="{{ $orderPurchase->currency_order }}" readonly></div>
                <div class="col-md-4 form-group"><label for="receipt-date">Fecha del comprobante *</label><input id="receipt-date" class="form-control" name="date_invoice" value="{{ now()->format('d/m/Y') }}" placeholder="dd/mm/aaaa" required></div>
                <div class="col-md-4 form-group"><label for="receipt-invoice">Comprobante *</label><input id="receipt-invoice" class="form-control" name="invoice" minlength="5" maxlength="255" required></div>
                <div class="col-md-4 form-group"><label for="receipt-guide">Guía de remisión</label><input id="receipt-guide" class="form-control" name="referral_guide" minlength="5" maxlength="255"></div>
                <div class="col-md-6 form-group"><label for="receipt-image">Archivo del comprobante</label><input id="receipt-image" class="form-control-file" type="file" name="image" accept=".jpg,.jpeg,.png,.pdf"></div>
                <div class="col-md-6 form-group"><label for="receipt-image-ob">Archivo de observación</label><input id="receipt-image-ob" class="form-control-file" type="file" name="imageOb" accept=".jpg,.jpeg,.png,.pdf"></div>
                <div class="col-12 form-group"><label for="receipt-observation">Observación</label><textarea id="receipt-observation" class="form-control" name="observation" maxlength="2000"></textarea></div>
                <div class="col-12 form-group"><label><input type="checkbox" name="deferred_invoice" value="on"> Comprobante diferido</label></div>
            </div>
        </div>
    </div>
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title">Productos de la orden — {{ $receiptSummary['state_text'] }}</h3></div>
        <div class="card-body">
            <p>Indique cuánto recibe ahora. Deje cero en los productos que todavía no llegaron.</p>
            <div class="table-responsive"><table class="table table-bordered table-sm">
                <thead><tr><th>Producto / variante</th><th>Pedido</th><th>Recibido</th><th>Pendiente</th><th>Ingresa ahora</th><th>Almacén</th><th>Lote</th><th>Vencimiento</th></tr></thead>
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
                        <td>{{ $line['ordered'] }}</td><td>{{ $line['received'] }}</td><td>{{ $line['pending'] }}</td>
                        <td><input aria-label="Cantidad que ingresa" class="form-control" type="number" min="0" max="{{ $line['pending'] }}" step="{{ $integerUnits ? '1' : '0.01' }}" value="0" data-receipt-quantity @if($disabled) disabled @endif></td>
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
    <div class="text-right mb-3">
        <a class="btn btn-secondary" href="{{ route('order.purchase.list.indexV2') }}">Volver</a>
        @if($canReceive)<button type="submit" class="btn btn-primary" id="receipt-save">Guardar ingreso</button>@endif
    </div>
</form>
@endsection
@section('scripts')
<script src="{{ asset('js/entry/normalPurchaseReceipt.js') }}"></script>
@endsection
