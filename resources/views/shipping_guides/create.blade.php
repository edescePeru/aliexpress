@extends('layouts.appAdmin2')

@section('openReferralGuide')
    menu-open
@endsection

@section('activeReferralGuide')
    active
@endsection

@section('activeCreateReferralGuide')
    active
@endsection

@section('title')
    Emitir Guía de Remisión
@endsection

@section('styles-plugins')
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.standalone.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.standalone.css') }}">
@endsection

@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Logística · Guías</span><h1 class="page-title">Nueva guía remitente</h1><p class="next-page-description">Prepara el traslado de la empresa y local activos.</p></div>
@endsection

@section('page-title')
<div class="next-page-toolbar">
<div class="next-toolbar-context"><strong>Guía de remisión remitente</strong><span>Los campos con (*) son obligatorios según el modo seleccionado.</span></div>
<div class="next-toolbar-actions flex-wrap"><a href="{{ route('shipping_guides.view') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left" aria-hidden="true"></i> Volver al listado</a></div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('shipping_guides.view') }}"><i class="fa fa-archive"></i> Guías de Remisión</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Emitir</li>
    </ol>
@endsection

@section('content')
    <form id="frmGuide" class="next-material-page">
        @csrf
        <input type="hidden" name="emission_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
        <input type="hidden" name="gre_tenant" value="{{ \App\Support\TenantContext::tenantId() }}">
        <input type="hidden" name="gre_company" value="{{ \App\Support\TenantContext::companyId() }}">
        <input type="hidden" name="gre_branch" value="{{ \App\Support\TenantContext::branchId() }}">
        <div class="next-form-section">
            <div class="alert alert-info mb-0" role="note">Guía remitente. La acción Crear y enviar transmite la operación a Nubefact/SUNAT.</div>
            @if ($nextNum === null)
                <div class="alert alert-warning mt-2 mb-0" role="alert">No hay numeración disponible. Solicita a Platform Admin revisar la conexión y numeración de guías de esta empresa.</div>
            @endif
            @if ($customers->isEmpty())
                <div class="alert alert-light mt-2 mb-0" role="status">No hay destinatarios disponibles en el contexto actual.</div>
            @endif
            <div id="guideValidation" class="alert alert-danger mt-2 mb-0" role="alert" tabindex="-1" hidden></div>
        </div>
        <section class="next-form-section" aria-labelledby="headingHeader">
            <div class="next-section-header mb-0">
                <h2 id="headingHeader" class="w-100">
                    <button type="button" class="btn btn-link text-reset text-decoration-none text-left p-0 w-100 d-flex align-items-center justify-content-between" data-toggle="collapse" data-target="#guide-panel-01" aria-controls="guide-panel-01" aria-expanded="true">
                        <span><span class="next-section-kicker">01</span><span class="h6 font-weight-bold mb-0">Comprobante y origen</span></span>
                        <i class="fas fa-chevron-up ml-2" aria-hidden="true" data-guide-chevron></i>
                    </button>
                </h2>
            </div>
            <div id="guide-panel-01" class="collapse show" data-guide-section>
                <div class="pt-3">
            <div class="row">
                <div class="col-md-4 form-group">
                    <label for="guide-field-1">Tipo de documento</label>
                    <input type="text" class="form-control" value="GUÍA DE REMISIÓN REMITENTE ELECTRÓNICA" readonly id="guide-field-1">
                </div>
                <div class="col-md-2 form-group">
                    <label for="guide-field-2">Serie</label>
                    <input type="text" class="form-control" name="serie" value="{{ $defaultSerie }}" readonly id="guide-field-2">
                </div>
                <div class="col-md-2 form-group">
                    <label for="guide-field-3">Número</label>
                    <input type="text" class="form-control" name="numero" placeholder="Correlativo referencial" value="{{ $nextNum }}" readonly id="guide-field-3">
                </div>
                <div class="col-md-4 form-group">
                    <label for="guide-field-4">Fecha emisión <span class="next-required">(*)</span></label>
                    <input type="date" class="form-control" name="fecha_emision" value="{{ now()->format('Y-m-d') }}" id="guide-field-4">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 form-group">
                    <label for="items_mode">Modo <span class="next-required">(*)</span></label>
                    <select class="form-control" name="items_mode" id="items_mode">
                        <option value="SALE">Desde venta (Boleta/Factura/Ticket)</option>
                        <option value="MANUAL">Manual</option>
                    </select>
                </div>
                <div class="col-md-8 form-group" id="boxSaleMode">
                    <label for="sale_id">Venta <span class="next-required">(*)</span></label>
                    <div class="row">
                        <div class="col-10 pr-1">
                            <select class="form-control" name="sale_id" id="sale_id" style="width:100%;"></select>
                        </div>
                        <div class="col-2 pl-1">
                            <button type="button" class="btn btn-primary btn-block" id="btnLoadSaleItems" aria-label="Cargar productos de la venta" title="Cargar productos de la venta">
                            <i class="fa fa-sync"></i>
                            </button>
                        </div>
                    </div>
                    <small class="text-muted">
                    Busca por cliente, doc, serie-num o ID. Cargará items tal cual (sin editar cantidades).
                    </small>
                    <div class="mt-2" id="saleMeta" style="display:none;">
                        <span class="text-muted text-break" id="saleMetaText"></span>
                    </div>
                </div>
            </div>

                </div>
            </div>
        </section>
        <section class="next-form-section" aria-labelledby="guide-section-2">
            <div class="next-section-header mb-0">
                <h2 id="guide-section-2" class="w-100">
                    <button type="button" class="btn btn-link text-reset text-decoration-none text-left p-0 w-100 d-flex align-items-center justify-content-between" data-toggle="collapse" data-target="#guide-panel-02" aria-controls="guide-panel-02" aria-expanded="false">
                        <span><span class="next-section-kicker">02</span><span class="h6 font-weight-bold mb-0">Destinatario</span></span>
                        <i class="fas fa-chevron-down ml-2" aria-hidden="true" data-guide-chevron></i>
                    </button>
                </h2>
            </div>
            <div id="guide-panel-02" class="collapse" data-guide-section>
                <div class="pt-3">
            <div class="row">
                <div class="col-md-4 form-group">
                    <label for="customer_id">Destinatario (Cliente) <span class="next-required">(*)</span></label>
                    {{--<input type="text" class="form-control" name="customer_name" placeholder="Razón social / Nombre">--}}
                    <div class="input-group next-field-action flex-nowrap">
                        <select id="customer_id" name="customer_id" class="form-control select2bs4">
                            <option></option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->business_name }}</option>
                            @endforeach
                        </select>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-primary next-field-action-button" id="btn-add-customer" aria-label="Nuevo cliente" title="Nuevo cliente">
                            <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 form-group">
                    <label for="customer_doc_type">Tipo doc <span class="next-required">(*)</span></label>
                    <select class="form-control" name="customer_doc_type" id="customer_doc_type">
                        <option value="">Elegir</option>
                        @foreach ($recipientDocTypes as $documentType)
                            <option value="{{ $documentType->code }}">{{ $documentType->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label for="customer_doc_number">Nro doc <span class="next-required">(*)</span></label>
                    <input type="text" class="form-control" id="customer_doc_number" name="customer_doc_number" placeholder="Nro documento">
                </div>
                <div class="col-md-3 form-group">
                    <label for="guide-field-5">Email (opcional)</label>
                    <input type="email" class="form-control" name="customer_email" placeholder="correo@ejemplo.com" id="guide-field-5">
                </div>
            </div>
            <div class="row">
                <div class="col-12 form-group">
                    <label for="guide-field-6">Dirección (opcional)</label>
                    <input type="text" class="form-control" name="customer_address" placeholder="Dirección del destinatario" id="guide-field-6">
                </div>
            </div>

                </div>
            </div>
        </section>
        <section class="next-form-section" aria-labelledby="headingTraslado">
            <div class="next-section-header mb-0">
                <h2 id="headingTraslado" class="w-100">
                    <button type="button" class="btn btn-link text-reset text-decoration-none text-left p-0 w-100 d-flex align-items-center justify-content-between" data-toggle="collapse" data-target="#guide-panel-03" aria-controls="guide-panel-03" aria-expanded="false">
                        <span><span class="next-section-kicker">03</span><span class="h6 font-weight-bold mb-0">Datos del traslado</span></span>
                        <i class="fas fa-chevron-down ml-2" aria-hidden="true" data-guide-chevron></i>
                    </button>
                </h2>
            </div>
            <div id="guide-panel-03" class="collapse" data-guide-section>
                <div class="pt-3">
            <div class="row">
                <div class="col-md-3 form-group">
                    <label for="guide-field-7">Fecha inicio traslado <span class="next-required">(*)</span></label>
                    <input type="date" class="form-control" name="fecha_inicio_traslado" value="{{ now()->format('Y-m-d') }}" id="guide-field-7">
                </div>
                <div class="col-md-3 form-group">
                    <label for="tipo_transporte">Tipo transporte <span class="next-required">(*)</span></label>
                    <select class="form-control" name="tipo_transporte" id="tipo_transporte">
                        <option value="">Elegir</option>
                        @foreach ($transportTypes as $transportType)
                            <option value="{{ $transportType->code }}">{{ $transportType->code }} - {{ $transportType->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label for="motivo_traslado_code">Motivo traslado <span class="next-required">(*)</span></label>
                    <select class="form-control" name="motivo_traslado_code" id="motivo_traslado_code">
                        <option value="">Elegir</option>
                        @foreach($transferReasons as $r)
                            <option value="{{ $r->code }}">{{ $r->code }} - {{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 form-group">
                    <label for="guide-field-8">Peso bruto total <span class="next-required">(*)</span></label>
                    <input type="number" step="0.001" class="form-control" name="peso_bruto_total" value="1" id="guide-field-8">
                </div>
                <div class="col-md-3 form-group">
                    <label for="guide-field-9">UM peso <span class="next-required">(*)</span></label>
                    <select class="form-control" name="peso_bruto_um_code" id="guide-field-9">
                        <option value="">Elegir</option>
                        @foreach($weightUnits as $u)
                            <option value="{{ $u->code }}">{{ $u->code }} - {{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label for="guide-field-10">Número de bultos <span class="next-required">(*)</span></label>
                    <input type="number" class="form-control" name="numero_bultos" value="1" id="guide-field-10">
                </div>
                <div class="col-md-3 form-group">
                    <label for="guide-field-11">Indicador envío SUNAT (opcional)</label>
                    <select class="form-control" name="sunat_shipping_indicator_code" id="guide-field-11">
                        <option value="">(Opcional)</option>
                        @foreach($indicators as $i)
                            <option value="{{ $i->code }}">{{ $i->code }} - {{ $i->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

                </div>
            </div>
        </section>
        <section class="next-form-section" aria-labelledby="headingPartida">
            <div class="next-section-header mb-0">
                <h2 id="headingPartida" class="w-100">
                    <button type="button" class="btn btn-link text-reset text-decoration-none text-left p-0 w-100 d-flex align-items-center justify-content-between" data-toggle="collapse" data-target="#guide-panel-04" aria-controls="guide-panel-04" aria-expanded="false">
                        <span><span class="next-section-kicker">04</span><span class="h6 font-weight-bold mb-0">Punto de partida</span></span>
                        <i class="fas fa-chevron-down ml-2" aria-hidden="true" data-guide-chevron></i>
                    </button>
                </h2>
            </div>
            <div id="guide-panel-04" class="collapse" data-guide-section>
                <div class="pt-3">
            <div class="row">
                <div class="col-md-4 form-group">
                    <label for="partida_department_id">Departamento <span class="next-required">(*)</span></label>
                    <select id="partida_department_id" class="form-control" style="width:100%"></select>
                </div>
                <div class="col-md-4 form-group">
                    <label for="partida_province_id">Provincia <span class="next-required">(*)</span></label>
                    <select id="partida_province_id" class="form-control" style="width:100%"></select>
                </div>
                <div class="col-md-4 form-group">
                    <label for="partida_ubigeo_select">Distrito <span class="next-required">(*)</span></label>
                    <select id="partida_ubigeo_select" class="form-control" style="width:100%"></select>
                    {{-- este es el que se envía al controller --}}
                    <input type="hidden" name="partida_ubigeo" id="partida_ubigeo">
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 form-group">
                    <label for="guide-field-12">Dirección <span class="next-required">(*)</span></label>
                    <input type="text" class="form-control" name="partida_direccion" placeholder="Dirección de partida" id="guide-field-12">
                </div>
            </div>

                </div>
            </div>
        </section>
        <section class="next-form-section" aria-labelledby="headingLlegada">
            <div class="next-section-header mb-0">
                <h2 id="headingLlegada" class="w-100">
                    <button type="button" class="btn btn-link text-reset text-decoration-none text-left p-0 w-100 d-flex align-items-center justify-content-between" data-toggle="collapse" data-target="#guide-panel-05" aria-controls="guide-panel-05" aria-expanded="false">
                        <span><span class="next-section-kicker">05</span><span class="h6 font-weight-bold mb-0">Punto de llegada</span></span>
                        <i class="fas fa-chevron-down ml-2" aria-hidden="true" data-guide-chevron></i>
                    </button>
                </h2>
            </div>
            <div id="guide-panel-05" class="collapse" data-guide-section>
                <div class="pt-3">
            <div class="row">
                <div class="col-md-4 form-group">
                    <label for="llegada_department_id">Departamento <span class="next-required">(*)</span></label>
                    <select id="llegada_department_id" class="form-control" style="width:100%"></select>
                </div>
                <div class="col-md-4 form-group">
                    <label for="llegada_province_id">Provincia <span class="next-required">(*)</span></label>
                    <select id="llegada_province_id" class="form-control" style="width:100%"></select>
                </div>
                <div class="col-md-4 form-group">
                    <label for="llegada_ubigeo_select">Distrito <span class="next-required">(*)</span></label>
                    <select id="llegada_ubigeo_select" class="form-control" style="width:100%"></select>
                    <input type="hidden" name="llegada_ubigeo" id="llegada_ubigeo">
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 form-group">
                    <label for="guide-field-13">Dirección <span class="next-required">(*)</span></label>
                    <input type="text" class="form-control" name="llegada_direccion" placeholder="Dirección de llegada" id="guide-field-13">
                </div>
            </div>

                </div>
            </div>
        </section>
        <section class="next-form-section" aria-labelledby="guide-section-6">
            <div class="next-section-header mb-0">
                <h2 id="guide-section-6" class="w-100">
                    <button type="button" class="btn btn-link text-reset text-decoration-none text-left p-0 w-100 d-flex align-items-center justify-content-between" data-toggle="collapse" data-target="#guide-panel-06" aria-controls="guide-panel-06" aria-expanded="false">
                        <span><span class="next-section-kicker">06</span><span class="h6 font-weight-bold mb-0">Transporte</span></span>
                        <i class="fas fa-chevron-down ml-2" aria-hidden="true" data-guide-chevron></i>
                    </button>
                </h2>
            </div>
            <div id="guide-panel-06" class="collapse" data-guide-section>
                <div class="pt-3">
            <div class="row">
                <div class="col-md-3 form-group">
                    <label for="vehicle_primary_plate">Placa vehículo principal <span class="next-required">(*)</span></label>
                    <input type="text" class="form-control" name="vehicle_primary_plate" id="vehicle_primary_plate" placeholder="ABC123">
                </div>
            </div>
            <div id="cardPublicTransport" style="display:none;">
                <h3 class="h6" id="headingTransportista">Transportista · Transporte público</h3>
                <div id="secTransportista">
                    <div class="row">
                        <div class="col-md-2 form-group">
                            <label for="guide-field-14">Tipo doc <span class="next-required">(*)</span></label>
                            <select class="form-control" name="transportista_doc_type" id="guide-field-14">
                                @forelse ($carrierDocTypes as $documentType)
                                    <option value="{{ $documentType->code }}">{{ $documentType->name }}</option>
                                @empty
                                    <option value="">RUC no disponible</option>
                                @endforelse
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="guide-field-15">Nro doc <span class="next-required">(*)</span></label>
                            <input type="text" class="form-control" name="transportista_doc_number" placeholder="20123456789" id="guide-field-15">
                        </div>
                        <div class="col-md-7 form-group">
                            <label for="guide-field-16">Razón social <span class="next-required">(*)</span></label>
                            <input type="text" class="form-control" name="transportista_name" placeholder="AGENCIA XYZ SAC" id="guide-field-16">
                        </div>
                    </div></div></div>
            <div id="cardPrivateDriver" style="display:none;">
                <h3 class="h6" id="headingDriver">Conductor · Transporte privado</h3>
                <div id="secDriver">
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label for="guide-field-17">Tipo doc <span class="next-required">(*)</span></label>
                            <select class="form-control" name="driver_primary[document_type_code]" id="guide-field-17">
                                <option value="">Elegir</option>
                                @foreach($identityDocTypes as $d)
                                    <option value="{{ $d->code }}">{{ $d->code }} - {{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="guide-field-18">Nro doc <span class="next-required">(*)</span></label>
                            <input type="text" class="form-control" name="driver_primary[document_number]" id="guide-field-18">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="guide-field-19">Nombres <span class="next-required">(*)</span></label>
                            <input type="text" class="form-control" name="driver_primary[first_name]" id="guide-field-19">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="guide-field-20">Apellidos <span class="next-required">(*)</span></label>
                            <input type="text" class="form-control" name="driver_primary[last_name]" id="guide-field-20">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="guide-field-21">Licencia <span class="next-required">(*)</span></label>
                            <input type="text" class="form-control" name="driver_primary[license_number]" id="guide-field-21">
                        </div>
                    </div></div></div>

                </div>
            </div>
        </section>
        <section class="next-form-section" aria-labelledby="headingItems">
            <div class="next-section-header mb-0">
                <h2 id="headingItems" class="w-100">
                    <button type="button" class="btn btn-link text-reset text-decoration-none text-left p-0 w-100 d-flex align-items-center justify-content-between" data-toggle="collapse" data-target="#guide-panel-07" aria-controls="guide-panel-07" aria-expanded="false">
                        <span><span class="next-section-kicker">07</span><span class="h6 font-weight-bold mb-0">Productos</span></span>
                        <i class="fas fa-chevron-down ml-2" aria-hidden="true" data-guide-chevron></i>
                    </button>
                </h2>
            </div>
            <div id="guide-panel-07" class="collapse" data-guide-section>
                <div class="pt-3">
            <p class="text-muted">Vista previa local de las líneas. Cargar productos no emite la guía.</p>
            <div id="saleItemsHint" class="alert alert-light" role="status">Selecciona una venta y carga sus productos para revisar el detalle.</div>
            <div class="mt-3" id="saleItemsPreview" style="display:none;">
                <div class="next-page-toolbar flex-wrap mb-2">
                    <strong>Items cargados desde la venta</strong>
                    <span class="text-muted" id="saleItemsCount"></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>Descripción</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-center">UM</th>
                            </tr>
                        </thead>
                        <tbody id="tbodySaleItems"></tbody>
                    </table>
                </div>
            </div>
            <div class="mt-3" id="manualItemsBox" style="display:none;">
                <div class="next-page-toolbar flex-wrap mb-2">
                    <strong>Items manuales</strong>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddManualItem">
                    <i class="fa fa-plus"></i> Agregar item
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th style="min-width:260px;">Producto</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-center">UM</th>
                                <th>Detalle adicional</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyManualItems"></tbody>
                    </table>
                </div>
                <p id="manualItemsEmpty" class="text-muted mt-2" role="status">No hay líneas manuales. Usa Agregar item para comenzar.</p><small class="text-muted d-block mt-2">
                Unidad del contrato actual: <b>NIU</b>. Selecciona productos del catálogo autorizado para la empresa activa.
                </small>
            </div>

                </div>
            </div>
        </section>
        <section class="next-form-section" aria-labelledby="headingObs">
            <div class="next-section-header mb-0">
                <h2 id="headingObs" class="w-100">
                    <button type="button" class="btn btn-link text-reset text-decoration-none text-left p-0 w-100 d-flex align-items-center justify-content-between" data-toggle="collapse" data-target="#guide-panel-08" aria-controls="guide-panel-08" aria-expanded="false">
                        <span><span class="next-section-kicker">08</span><span class="h6 font-weight-bold mb-0">Observaciones</span></span>
                        <i class="fas fa-chevron-down ml-2" aria-hidden="true" data-guide-chevron></i>
                    </button>
                </h2>
            </div>
            <div id="guide-panel-08" class="collapse" data-guide-section>
                <div class="pt-3">
            <label for="guide-field-22">Observaciones (opcional)</label>
            <textarea class="form-control" name="observaciones" rows="3" id="guide-field-22"></textarea>

                </div>
            </div>
        </section>
        <div class="next-list-toolbar next-page-toolbar flex-wrap">
            <p class="text-muted mb-0">El correlativo mostrado es referencial; el servidor asigna el número al crear la guía.</p>
            <div class="next-toolbar-actions flex-wrap">
                <button type="button" class="btn btn-primary" id="btnSubmitGuide" @if ($nextNum === null) disabled @endif>
                <i class="fa fa-paper-plane" aria-hidden="true"></i> Crear y enviar a SUNAT
                </button>
            </div>
        </div>
    </form>

    <!-- Modal Cliente -->
    <div class="modal fade next-aux-modal" id="modalCustomer" tabindex="-1" role="dialog" aria-labelledby="modalCustomerLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalCustomerLabel">Nuevo Cliente</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <form id="formCreateCustomer" class="form-horizontal next-aux-modal-form" data-url="{{ route('customer.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-12 col-form-label">RUC <span class="next-required">(*)</span></label>
                                <input type="text" class="form-control" name="ruc" placeholder="Ejm: 1234678901">
                            </div>

                            <div class="col-md-2">
                                <label class="col-12 col-form-label">Extranjero <span class="next-required">(*)</span></label>
                                <input id="btn-grouped" type="checkbox" name="special" data-bootstrap-switch data-off-color="danger" data-on-text="SI" data-off-text="NO" data-on-color="success">
                            </div>

                            <div class="col-md-6">
                                <label class="col-12 col-form-label">Razon Social <span class="next-required">(*)</span></label>
                                <input type="text" class="form-control" onkeyup="mayus(this);" name="business_name" placeholder="Ejm: Edesce EIRL">
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-6">
                                <label class="col-12 col-form-label">Direccion</label>
                                <input type="text" class="form-control" onkeyup="mayus(this);" name="address" placeholder="Ejm: Jr Union">
                            </div>

                            <div class="col-md-6">
                                <label class="col-12 col-form-label">Ubicacion</label>
                                <input type="text" class="form-control" onkeyup="mayus(this);" name="location" placeholder="Ejm: Moche">
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" id="btn-submit-customer" class="btn btn-outline-success">Guardar</button>
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('plugins')
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/locales/bootstrap-datepicker.es.min.js') }}"></script>
    <script src="{{asset('admin/plugins/typehead/typeahead.bundle.js')}}"></script>
@endsection

@section('scripts')
    <script>
        window.routes = {
            store: "{{ route('shipping_guides.store') }}",
            index: "{{ route('shipping_guides.view') }}",
            customerPayload: "{{ route('shipping_guides.customer_payload', ['id' => ':id']) }}",
            saleItems: "{{ route('shipping_guides.sale_items') }}",
            salesSelect2: "{{ route('sales.select2') }}",
            saleItemsById: "{{ url('dashboard/sales') }}/:id/items",
            ubigeoDepartments: "{{ route('ubigeo.departments.select2') }}",
            ubigeoProvinces: "{{ route('ubigeo.provinces.select2')}}",
            ubigeoDistricts: "{{ route('ubigeo.districts.select2')}}",
            materialsSelect2: "{{ route('shipping_guides.products') }}",
        };
    </script>
    <script>
        $(function () {
            $('#customer_id').select2({
                placeholder: "Selecione cliente",
                theme: 'bootstrap4',
                width: '100%'
            });
            $("input[data-bootstrap-switch]").each(function(){
                $(this).bootstrapSwitch();
            });
        })
    </script>
    <script src="{{ asset('js/shipping_guides/create.js') }}?v={{ time() }}"></script>
@endsection
