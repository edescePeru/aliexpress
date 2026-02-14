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

@section('styles')
    <style>
        .select2-search__field{ width: 100% !important; }
        .section-title { font-weight: 600; }
        .card-header a { text-decoration: none; display:block; }
        .required:after { content:" *"; color:#dc3545; font-weight:700; }
        .bg-soft { background:#f8f9fa; }
    </style>
@endsection

@section('page-header')
    <h1 class="page-title">Guías de remisión</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Emitir guía de remisión (Remitente)</h5>
    <a href="{{ route('referral.guide.index') }}" class="btn btn-outline-secondary btn-sm float-right">
        <i class="fa fa-arrow-left"></i> Volver al listado
    </a>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('referral.guide.index') }}"><i class="fa fa-archive"></i> Guías de Remisión</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Emitir</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid">

        <div class="alert alert-info">
            <strong>Modo MVP:</strong> Guía Remitente. Se enviará directamente a Nubefact/SUNAT al guardar.
        </div>

        <div class="card">
            <div class="card-body">

                <form id="frmGuide">
                    @csrf

                    {{-- Acordeón (solo 1 abierto) --}}
                    <div id="accordionGuide">

                        {{-- 1) ENCABEZADO --}}
                        <div class="card">
                            <div class="card-header bg-soft" id="headingHeader">
                                <div class="section-title">1. Encabezado</div>
                            </div>
                            <div class="card-body">

                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="required">Destinatario (Cliente)</label>
                                        {{--<input type="text" class="form-control" name="customer_name" placeholder="Razón social / Nombre">--}}
                                        <div class="input-group input-group-sm">
                                            <select id="customer_id" name="customer_id" class="form-control select2bs4">
                                                <option></option>
                                                @foreach($customers as $customer)
                                                    <option value="{{ $customer->id }}">{{ $customer->business_name }}</option>
                                                @endforeach
                                            </select>
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-primary" id="btn-add-customer">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="required">Tipo doc</label>
                                        <select class="form-control" name="customer_doc_type" id="customer_doc_type">
                                            <option value="6">RUC</option>
                                            <option value="1">DNI</option>
                                            <option value="4">CE</option>
                                            <option value="7">PASAPORTE</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="required">Nro doc</label>
                                        <input type="text" class="form-control" id="customer_doc_number" name="customer_doc_number" placeholder="Nro documento">
                                    </div>

                                    <div class="col-md-3">
                                        <label>Email (opcional)</label>
                                        <input type="email" class="form-control" name="customer_email" placeholder="correo@ejemplo.com">
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <label>Dirección (opcional)</label>
                                        <input type="text" class="form-control" name="customer_address" placeholder="Dirección del destinatario">
                                    </div>

                                    <div class="col-md-4">
                                        <label>Tipo de documento</label>
                                        <input type="text" class="form-control" value="GUÍA DE REMISIÓN REMITENTE ELECTRÓNICA" readonly>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="required">Serie</label>
                                        <input type="text" class="form-control" name="serie" value="{{ $defaultSerie ?? 'TPD1' }}" readonly>
                                    </div>

                                    <div class="col-md-2">
                                        <label>Número</label>
                                        <input type="text" class="form-control" name="numero" placeholder="Auto/Editable">
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-3">
                                        <label class="required">Fecha emisión</label>
                                        <input type="date" class="form-control" name="fecha_emision" value="{{ now()->format('Y-m-d') }}">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="required">Fecha inicio traslado</label>
                                        <input type="date" class="form-control" name="fecha_inicio_traslado" value="{{ now()->format('Y-m-d') }}">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="required">Tipo transporte</label>
                                        <select class="form-control" name="tipo_transporte" id="tipo_transporte">
                                            <option value="">Elegir</option>
                                            <option value="01">01 - Público (Agencia)</option>
                                            <option value="02">02 - Privado (Propio)</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="required">Motivo traslado</label>
                                        <select class="form-control" name="motivo_traslado_code" id="motivo_traslado_code">
                                            <option value="">Elegir</option>
                                            @foreach($transferReasons as $r)
                                                <option value="{{ $r->code }}">{{ $r->code }} - {{ $r->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                            </div>

                        </div>

                        {{-- 2) ITEMS --}}
                        <div class="card">
                            <div class="card-header bg-soft" id="headingItems">
                                <a class="collapsed section-title" data-toggle="collapse" href="#secItems" aria-expanded="false" aria-controls="secItems">
                                    2. Productos / Items
                                </a>
                            </div>
                            <div id="secItems" class="collapse" aria-labelledby="headingItems" data-parent="#accordionGuide">
                                <div class="card-body">

                                    <div class="row">
                                        <div class="col-md-3">
                                            <label class="required">Modo</label>
                                            <select class="form-control" name="items_mode" id="items_mode">
                                                <option value="SALE">Desde venta (Boleta/Factura)</option>
                                                <option value="MANUAL">Manual</option>
                                            </select>
                                        </div>

                                        <div class="col-md-5" id="boxSaleRef">
                                            <label class="required">Venta (serie-num)</label>
                                            <input type="text" class="form-control" name="sale_ref" placeholder="FFF1-6">
                                            <small class="text-muted">Cargará items tal cual (sin editar cantidades).</small>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="required">Placa vehículo principal</label>
                                            <input type="text" class="form-control" name="vehicle_primary_plate" placeholder="ABC123">
                                        </div>
                                    </div>

                                    <div id="boxItemsManual" class="mt-3" style="display:none;">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong>Items manuales</strong>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddItem">
                                                + Agregar
                                            </button>
                                        </div>
                                        <div id="itemsContainer"></div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        {{-- 3) DATOS DEL TRASLADO --}}
                        <div class="card">
                            <div class="card-header bg-soft" id="headingTraslado">
                                <a class="collapsed section-title" data-toggle="collapse" href="#secTraslado" aria-expanded="false" aria-controls="secTraslado">
                                    3. Datos del traslado
                                </a>
                            </div>
                            <div id="secTraslado" class="collapse" aria-labelledby="headingTraslado" data-parent="#accordionGuide">
                                <div class="card-body">

                                    <div class="row">
                                        <div class="col-md-3">
                                            <label class="required">Peso bruto total</label>
                                            <input type="number" step="0.001" class="form-control" name="peso_bruto_total" value="1">
                                        </div>

                                        <div class="col-md-3">
                                            <label class="required">UM peso</label>
                                            <select class="form-control" name="peso_bruto_um_code">
                                                <option value="">Elegir</option>
                                                @foreach($weightUnits as $u)
                                                    <option value="{{ $u->code }}">{{ $u->code }} - {{ $u->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="required">Número de bultos</label>
                                            <input type="number" class="form-control" name="numero_bultos" value="1">
                                        </div>

                                        <div class="col-md-3">
                                            <label>Indicador envío SUNAT (opcional)</label>
                                            <select class="form-control" name="sunat_shipping_indicator_code">
                                                <option value="">(Opcional)</option>
                                                @foreach($indicators as $i)
                                                    <option value="{{ $i->code }}">{{ $i->code }} - {{ $i->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        {{-- 4) TRANSPORTISTA (PÚBLICO) --}}
                        <div class="card" id="cardPublicTransport" style="display:none;">
                            <div class="card-header bg-soft" id="headingTransportista">
                                <a class="collapsed section-title" data-toggle="collapse" href="#secTransportista" aria-expanded="false" aria-controls="secTransportista">
                                    4. Datos del transportista (Público)
                                </a>
                            </div>
                            <div id="secTransportista" class="collapse" aria-labelledby="headingTransportista" data-parent="#accordionGuide">
                                <div class="card-body">

                                    <div class="row">
                                        <div class="col-md-2">
                                            <label class="required">Tipo doc</label>
                                            <select class="form-control" name="transportista_doc_type">
                                                <option value="6">RUC</option>
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="required">Nro doc</label>
                                            <input type="text" class="form-control" name="transportista_doc_number" placeholder="20123456789">
                                        </div>

                                        <div class="col-md-7">
                                            <label class="required">Razón social</label>
                                            <input type="text" class="form-control" name="transportista_name" placeholder="AGENCIA XYZ SAC">
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        {{-- 5) CONDUCTOR (PRIVADO) --}}
                        <div class="card" id="cardPrivateDriver" style="display:none;">
                            <div class="card-header bg-soft" id="headingDriver">
                                <a class="collapsed section-title" data-toggle="collapse" href="#secDriver" aria-expanded="false" aria-controls="secDriver">
                                    5. Datos del conductor (Privado)
                                </a>
                            </div>
                            <div id="secDriver" class="collapse" aria-labelledby="headingDriver" data-parent="#accordionGuide">
                                <div class="card-body">

                                    <div class="row">
                                        <div class="col-md-3">
                                            <label class="required">Tipo doc</label>
                                            <select class="form-control" name="driver_primary[document_type_code]">
                                                <option value="">Elegir</option>
                                                @foreach($identityDocTypes as $d)
                                                    <option value="{{ $d->code }}">{{ $d->code }} - {{ $d->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="required">Nro doc</label>
                                            <input type="text" class="form-control" name="driver_primary[document_number]">
                                        </div>

                                        <div class="col-md-3">
                                            <label class="required">Nombres</label>
                                            <input type="text" class="form-control" name="driver_primary[first_name]">
                                        </div>

                                        <div class="col-md-3">
                                            <label class="required">Apellidos</label>
                                            <input type="text" class="form-control" name="driver_primary[last_name]">
                                        </div>

                                        <div class="col-md-3 mt-3">
                                            <label class="required">Licencia</label>
                                            <input type="text" class="form-control" name="driver_primary[license_number]">
                                        </div>

                                        <div class="col-md-9 mt-3">
                                            <div class="alert alert-secondary mb-0">
                                                Secundarios (vehículos/conductores) quedan para V2. MVP solo principal.
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        {{-- 6) PUNTO DE PARTIDA --}}
                        <div class="card">
                            <div class="card-header bg-soft" id="headingPartida">
                                <a class="collapsed section-title" data-toggle="collapse" href="#secPartida" aria-expanded="false" aria-controls="secPartida">
                                    6. Punto de partida
                                </a>
                            </div>
                            <div id="secPartida" class="collapse" aria-labelledby="headingPartida" data-parent="#accordionGuide">
                                <div class="card-body">

                                    <div class="row">
                                        <div class="col-md-3">
                                            <label class="required">Ubigeo</label>
                                            <input type="text" class="form-control" name="partida_ubigeo" placeholder="151021">
                                        </div>
                                        <div class="col-md-9">
                                            <label class="required">Dirección</label>
                                            <input type="text" class="form-control" name="partida_direccion" placeholder="Dirección de partida">
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        {{-- 7) PUNTO DE LLEGADA --}}
                        <div class="card">
                            <div class="card-header bg-soft" id="headingLlegada">
                                <a class="collapsed section-title" data-toggle="collapse" href="#secLlegada" aria-expanded="false" aria-controls="secLlegada">
                                    7. Punto de llegada
                                </a>
                            </div>
                            <div id="secLlegada" class="collapse" aria-labelledby="headingLlegada" data-parent="#accordionGuide">
                                <div class="card-body">

                                    <div class="row">
                                        <div class="col-md-3">
                                            <label class="required">Ubigeo</label>
                                            <input type="text" class="form-control" name="llegada_ubigeo" placeholder="211101">
                                        </div>
                                        <div class="col-md-9">
                                            <label class="required">Dirección</label>
                                            <input type="text" class="form-control" name="llegada_direccion" placeholder="Dirección de llegada">
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        {{-- 8) OBSERVACIONES --}}
                        <div class="card">
                            <div class="card-header bg-soft" id="headingObs">
                                <a class="collapsed section-title" data-toggle="collapse" href="#secObs" aria-expanded="false" aria-controls="secObs">
                                    8. Observaciones
                                </a>
                            </div>
                            <div id="secObs" class="collapse" aria-labelledby="headingObs" data-parent="#accordionGuide">
                                <div class="card-body">
                                    <label>Observaciones (opcional)</label>
                                    <textarea class="form-control" name="observaciones" rows="3"></textarea>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="mt-4 text-right">
                        <button type="button" class="btn btn-success" id="btnSubmitGuide">
                            <i class="fa fa-paper-plane"></i> Crear y enviar a SUNAT
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <!-- Modal Cliente -->
    <div class="modal fade" id="modalCustomer" tabindex="-1" role="dialog" aria-labelledby="modalCustomerLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalCustomerLabel">Nuevo Cliente</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <form id="formCreateCustomer" class="form-horizontal" data-url="{{ route('customer.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-12 col-form-label">RUC <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" class="form-control" name="ruc" placeholder="Ejm: 1234678901">
                            </div>

                            <div class="col-md-2">
                                <label class="col-12 col-form-label">Extranjero <span class="right badge badge-danger">(*)</span></label>
                                <input id="btn-grouped" type="checkbox" name="special" data-bootstrap-switch data-off-color="danger" data-on-text="SI" data-off-text="NO" data-on-color="success">
                            </div>

                            <div class="col-md-6">
                                <label class="col-12 col-form-label">Razon Social <span class="right badge badge-danger">(*)</span></label>
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
            index: "{{ route('referral.guide.index') }}",
            customerPayload: "{{ route('customers.payload', ['customer' => ':id']) }}"
        };
    </script>
    <script>
        $(function () {
            $('#customer_id').select2({
                placeholder: "Selecione cliente",
                theme: 'bootstrap4',
                width: 'resolve'
            });
            $("input[data-bootstrap-switch]").each(function(){
                $(this).bootstrapSwitch();
            });
        })
    </script>
    <script src="{{ asset('js/shipping_guides/create.js') }}"></script>
@endsection
