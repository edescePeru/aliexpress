@extends('layouts.appAdmin2')
@section('openPuntoVenta') menu-open @endsection
@section('activePuntoVenta') active @endsection
@section('activeListPuntoVenta') active @endsection
@section('title') Ventas @endsection
@section('styles-plugins')
<link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.min.css') }}">
@endsection
@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Ventas</span><h1 class="page-title">{{ $saleScopeLabel }}</h1><p class="next-page-description">Consulta ventas, comprobantes y despacho del local activo.</p></div>
@endsection
@section('page-title')
<div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Listado operativo</strong><span>Consulta de ventas · Solo lectura</span></div></div>
@endsection
@section('page-breadcrumb')
<ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('dashboard.principal') }}">Dashboard</a></li><li class="breadcrumb-item active" aria-current="page">Ventas</li></ol>
@endsection
@section('content')
<div id="sale-list-context" hidden data-tenant="{{ \App\Support\TenantContext::tenantId() }}" data-company="{{ \App\Support\TenantContext::companyId() }}" data-branch="{{ \App\Support\TenantContext::branchId() }}" data-permissions="{{ json_encode($salePermissions) }}"></div>
<p class="text-muted px-3 pt-3 mb-0">Se muestran ventas con local y usuario registrador identificados en la empresa activa.</p>
<section class="next-operational-list next-advanced-operational-list" aria-label="Listado de ventas">
<form class="next-list-toolbar" action="#">
    <div class="next-list-search-row">
        <div class="input-group next-list-search-control">
            <input id="code" class="form-control" placeholder="Código de venta…" aria-label="Código de venta" autocomplete="off" maxlength="100">
            <div class="input-group-append"><button id="btn-search" type="submit" class="btn btn-primary next-list-search"><i class="fas fa-search mr-1" aria-hidden="true"></i> Buscar</button></div>
        </div>
        <button id="btnBusquedaAvanzada" type="button" class="btn btn-link next-list-advanced-toggle" aria-controls="next-sales-advanced-filters" aria-expanded="false"><i class="fas fa-sliders-h mr-1" aria-hidden="true"></i> Filtros</button>
        <button id="sales-reset" type="button" class="btn btn-outline-secondary btn-sm">Limpiar</button>
    </div>
            <div class="busqueda-avanzada next-list-filters" id="next-sales-advanced-filters">
                <div class="next-list-filters-header">
                    <div>
                        <strong>Filtros avanzados</strong>
                        <span>Combina criterios para acotar las ventas.</span>
                    </div>
                </div>

                <div class="form-row next-list-filters-grid">
                    @if(count($saleCreators) > 1)
                    <div class="form-group col-md-6 col-lg-3 next-list-filter">
                        <label for="filter_created_by_id">Usuario registrador</label>
                        <select id="filter_created_by_id" class="form-control form-control-sm select2">
                            <option value="">Todos los autorizados</option>
                            @foreach($saleCreators as $creator)<option value="{{ $creator->id }}">{{ $creator->name }}</option>@endforeach
                        </select>
                    </div>
                    @endif

                    <div class="form-group col-md-4 col-lg-2 next-list-filter">
                        <label for="year">Año de registro</label>
                        <select id="year" class="form-control form-control-sm select2">
                            <option value="">TODOS</option>
                            @for ($i=0; $i<count($arrayYears); $i++)
                                <option value="{{ $arrayYears[$i] }}">{{ $arrayYears[$i] }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="form-group col-md-8 col-lg-4 next-list-filter" id="sandbox-container">
                        <label for="start">Fechas de venta</label>
                        <div class="input-daterange input-group next-list-date-range" id="datepicker">
                            <input type="text" class="form-control form-control-sm date-range-filter" id="start" name="start" autocomplete="off" aria-label="Fecha inicial">
                            <div class="input-group-prepend input-group-append">
                                <span class="input-group-text">al</span>
                            </div>
                            <input type="text" class="form-control form-control-sm date-range-filter" id="end" name="end" autocomplete="off" aria-label="Fecha final">
                        </div>
                    </div>

                    <div class="form-group col-md-6 col-lg-3 next-list-filter">
                        <label for="filter_customer_id">Cliente</label>
                        <select id="filter_customer_id" class="form-control form-control-sm select2">
                            <option value="">TODOS</option>
                            <option value="venta_directa">VENTA DIRECTA</option>
                            <option value="cotizacion_sin_cliente">COTIZACIÓN SIN CLIENTE</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->business_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-6 col-lg-3 next-list-filter">
                        <label for="filter_payment_status">Cumplimiento</label>
                        <select id="filter_payment_status" class="form-control form-control-sm select2">
                            <option value="">TODOS</option>
                            <option value="rojo">Menos del 50%</option>
                            <option value="naranja">50% a 99.99%</option>
                            <option value="verde">100% o más</option>
                        </select>
                    </div>

                    <div class="form-group col-md-6 col-lg-3 next-list-filter">
                        <label for="filter_cash_box_id">Método de pago / Caja</label>
                        <select id="filter_cash_box_id" class="form-control form-control-sm select2">
                            <option value="">TODOS</option>
                            <option value="pago_parcial">PAGO PARCIAL</option>
                            @foreach($cashBoxes as $cashBox)
                                <option value="{{ $cashBox->id }}"
                                        data-type="{{ $cashBox->type }}"
                                        data-uses_subtypes="{{ $cashBox->uses_subtypes ? 1 : 0 }}">
                                    {{ $cashBox->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-6 col-lg-3 next-list-filter" id="filter_cash_box_subtype_wrap" hidden>
                        <label for="filter_cash_box_subtype_id">Canal / Subtipo</label>
                        <select id="filter_cash_box_subtype_id" class="form-control form-control-sm select2">
                            <option value="">TODOS</option>
                            @foreach($subtypes as $subtype)
                                <option value="{{ $subtype->id }}">{{ $subtype->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-6 col-lg-2 next-list-filter">
                        <label for="filter_dispatch_status">Despacho</label>
                        <select id="filter_dispatch_status" class="form-control form-control-sm select2">
                            <option value="">TODOS</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="despachado">Despachado</option>
                        </select>
                    </div>

                    <div class="form-group col-md-6 col-lg-2 next-list-filter">
                        <label for="filter_invoice_status">Comprobante</label>
                        <select id="filter_invoice_status" class="form-control form-control-sm select2">
                            <option value="">TODOS</option>
                            <option value="con_comprobante">Con comprobante</option>
                            <option value="sin_comprobante">Sin comprobante</option>
                        </select>
                    </div>

                    <div class="form-group col-md-6 col-lg-2 next-list-filter">
                        <label for="filter_sale_status">Estado de venta</label>
                        <select id="filter_sale_status" class="form-control form-control-sm select2">
                            <option value="active" selected>Activas</option>
                            <option value="annulled">Anuladas</option>
                        </select>
                    </div>
                </div>
            </div>

</form>
<div id="sales-feedback" class="alert alert-danger d-none" role="alert"></div>
<div class="next-list-summary" aria-live="polite"><div class="next-list-summary-copy"><strong id="numberItems" class="next-list-count">—</strong><span>Ventas encontradas</span></div><span class="next-list-sort-context">Por fecha de creación ↓</span></div>
<div class="next-list-content">
<div id="sales-empty" class="text-center py-5" role="status" hidden><i class="fas fa-search text-muted mb-3" aria-hidden="true"></i><h2 class="h6">No se encontraron ventas</h2><p class="text-muted mb-0">Revisa los filtros. Sólo se incluyen ventas autorizadas con usuario registrador identificado.</p></div>
<div id="sales-table-wrap" class="table-responsive" tabindex="0" aria-label="Ventas; desplazamiento horizontal local">
<table class="table table-bordered table-hover table-sm next-data-table"><thead><tr><th scope="col" class="text-center">Código</th><th scope="col" class="text-center">Fecha venta</th><th scope="col" class="text-left">Cliente</th><th scope="col" class="text-left">Usuario registrador</th><th scope="col" class="text-center">Moneda</th><th scope="col" class="text-right">Importe</th><th scope="col" class="text-left">Método de pago</th><th scope="col" class="text-center">Cumplimiento</th><th scope="col" class="text-center">Despacho / anulación</th><th scope="col" class="text-center">Comprobante / emisión</th><th scope="col" class="text-center">Acciones</th></tr></thead><tbody id="body-table"></tbody></table>
</div>
</div>
<div class="next-list-pagination"><div id="textPagination" aria-live="polite"></div><nav aria-label="Paginación de ventas"><ul class="pagination" id="pagination"></ul></nav></div>
</section>
<div class="modal fade next-aux-modal" id="orderDetailsModal" tabindex="-1" role="dialog" aria-labelledby="sales-detail-title" aria-hidden="true">
<div class="modal-dialog modal-xl modal-dialog-scrollable" role="document"><div class="modal-content">
<div class="modal-header"><h2 class="modal-title h5" id="sales-detail-title">Detalle de venta</h2><button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>
<div class="modal-body"><div id="sales-detail-summary" class="row"></div><div id="order-details-content" aria-live="polite"></div></div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cerrar</button></div>
</div></div></div>
@endsection
@section('plugins')
<script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
<script src="{{ asset('admin/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
<script src="{{ asset('admin/plugins/bootstrap-datepicker/locales/bootstrap-datepicker.es.min.js') }}"></script>
@endsection
@section('scripts')
{{-- Read adapter only: financial handlers remain outside this phase. --}}
<script src="{{ asset('js/puntoVenta/list-readonly.js') }}?v={{ filemtime(public_path('js/puntoVenta/list-readonly.js')) }}"></script>
@endsection
