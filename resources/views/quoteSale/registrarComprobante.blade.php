@extends('layouts.appAdmin2')

@section('openQuote')
    menu-open
@endsection

@section('activeQuote')
    active
@endsection

@section('activeCreateQuote')
    active
@endsection

@section('title')
    Cotizaciones
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.standalone.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.standalone.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/typehead/typeahead.css') }}">
    <!-- summernote -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/summernote/summernote-bs4.css') }}">
    <!-- Images -->
@endsection


@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Ventas · cotizaciones</span><h1 class="page-title">Registrar {{ $typeComprobante }}</h1><p class="next-page-description">Selecciona una cotización y revisa los datos del comprobante.</p></div>
@endsection

@section('page-title')
    <h5 class="card-title">Registrar comprobante de cotización</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('quoteSale.index') }}"><i class="fa fa-key"></i> Cotizaciones</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Nuevo</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">

    <form id="formCreate" class="form-horizontal next-form-section" data-url="{{ route('quoteSale.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">DATOS GENERALES</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body next-form-section">
                        <div class="form-group row align-items-end">

                            <input type="hidden" id="quote_id" name="quote_id">

                            <input type="hidden"
                                   name="negocio_acepta_pagos_parciales"
                                   id="negocio_acepta_pagos_parciales"
                                   value="{{ $pagos_parciales }}">

                            <div class="col-sm-6 col-lg-2 mb-3">
                                <label for="typeComprobante">Tipo Comprobante</label>
                                <input type="text" id="typeComprobante" name="typeComprobante" value="{{ $typeComprobante }}" class="form-control form-control-sm" readonly>
                            </div>
                            <div class="col-sm-6 col-lg-1 mb-3" style="display: none">
                                <label for="numDocumento">Documento </label>
                                <input type="text" id="numDocumento" name="numDocumento" class="form-control form-control-sm">
                            </div>
                            <div class="col-sm-6 col-lg-1 mb-3">
                                <label for="moneda">Moneda </label>
                                <input type="text" id="moneda" name="moneda" value="{{ ($currency === 'PEN') ? 'SOLES':'DOLARES' }}" class="form-control form-control-sm" readonly>
                            </div>
                            <div class="col-sm-6 col-lg-2 mb-3">
                                <label for="fechaDocumento">Fecha <span class="right badge badge-danger">(*)</span></label>
                                <input type="date"
                                       id="fechaDocumento"
                                       name="fechaDocumento"
                                       class="form-control form-control-sm"
                                       value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
                            </div>

                            @if ($pagos_parciales == 's')
                                <div class="col-sm-6 col-lg-1 mb-3">
                                    <label>Parcial</label><br>

                                    <input type="checkbox"
                                           id="pagos_parciales_venta"
                                           value="s"
                                           data-bootstrap-switch
                                           data-on-text="SI"
                                           data-off-text="NO"
                                           data-on-color="success"
                                           data-off-color="danger"
                                           data-state="false"
                                           data-disabled="{{ $typeComprobante == 'Boleta' || $typeComprobante == 'Factura' ? 'true' : 'false' }}">
                                </div>
                            @endif

                            <div class="col-sm-6 col-lg-2 mb-3" id="wrap_pv_cash_box">
                                <label for="pv_cash_box_id">
                                    Caja <span class="right badge badge-danger">(*)</span>
                                </label>

                                <select id="pv_cash_box_id"
                                        class="form-control form-control-sm select2"
                                        style="width: 100%;">
                                    <option value=""></option>

                                    @foreach($cashBoxes as $b)
                                        <option
                                                value="{{ $b['id'] }}"
                                                data-type="{{ $b['type'] }}"
                                                data-uses_subtypes="{{ $b['uses_subtypes'] ? 1 : 0 }}"
                                        >
                                            {{ $b['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-6 col-lg-2 mb-3" id="pv_cash_box_subtype_wrap" style="display:none;">
                                <label for="pv_cash_box_subtype_id">
                                    Canal / Subtipo <span class="right badge badge-danger">(*)</span>
                                </label>

                                <select id="pv_cash_box_subtype_id"
                                        class="form-control form-control-sm select2"
                                        style="width: 100%;">
                                    <option value=""></option>
                                </select>

                                <small class="text-muted">
                                    Solo aplica cuando la caja es bancaria y usa subtipos.
                                </small>
                            </div>
                            <div class="col-sm-6 col-lg-2 mb-3">
                                <label for="fechaDocumento">&nbsp;</label>
                                <button type="button" class="btn btn-outline-primary btn-block btn-sm" data-toggle="modal" data-target="#modalBuscarComprobante">
                                    Seleccionar cotización
                                </button>
                            </div>
                        </div>
                        @if ( $typeComprobante == 'Boleta' )
                            <div class="form-group row" id="datosBoleta">
                                {{--<div class="col-md-4">
                                    <label for="dniCliente">DNI <span class="right badge badge-danger">(*)</span></label>
                                    <input type="text" maxlength="8" id="dniCliente" name="dniCliente" class="form-control form-control-sm">
                                </div>--}}
                                <div class="col-md-4">
                                    <label for="dniCliente">
                                        DNI <span class="right badge badge-danger">(*)</span>
                                    </label>

                                    <div class="input-group input-group-sm">
                                        <input
                                                type="text"
                                                maxlength="8"
                                                id="dniCliente"
                                                name="dniCliente"
                                                class="form-control"
                                                inputmode="numeric"
                                                enterkeyhint="search"
                                        >

                                        <div class="input-group-append">
                                            <button
                                                    type="button"
                                                    class="btn btn-primary btnConsultarCliente"
                                                    data-input="#dniCliente"
                                                    title="Consultar DNI"
                                            >
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="nameCliente">Nombre cliente <span class="right badge badge-danger">(*)</span></label>
                                    <input type="text" id="nameCliente" name="nameCliente" value="" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-4">
                                    <label for="emailCliente">Email (opcional)</label>
                                    <input type="text" id="emailCliente" name="emailCliente" class="form-control form-control-sm">
                                </div>
                            </div>
                        @elseif( $typeComprobante == 'Factura' )
                            <div class="form-group row" id="datosFactura">
                                {{--<div class="col-md-3">
                                    <label for="rucCliente">RUC <span class="right badge badge-danger">(*)</span></label>
                                    <input type="text" id="rucCliente" name="rucCliente" value="" class="form-control form-control-sm">
                                </div>--}}
                                <div class="col-md-3">
                                    <label for="rucCliente">
                                        RUC <span class="right badge badge-danger">(*)</span>
                                    </label>

                                    <div class="input-group input-group-sm">
                                        <input
                                                type="text"
                                                maxlength="11"
                                                id="rucCliente"
                                                name="rucCliente"
                                                value=""
                                                class="form-control"
                                                inputmode="numeric"
                                                enterkeyhint="search"
                                        >

                                        <div class="input-group-append">
                                            <button
                                                    type="button"
                                                    class="btn btn-primary btnConsultarCliente"
                                                    data-input="#rucCliente"
                                                    title="Consultar RUC"
                                            >
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label for="razonCliente">Razón Social <span class="right badge badge-danger">(*)</span></label>
                                    <input type="text" maxlength="11" id="razonCliente" name="razonCliente" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-3">
                                    <label for="direccionCliente">Dirección Fiscal <span class="right badge badge-danger">(*)</span></label>
                                    <input type="text" id="direccionCliente" name="direccionCliente" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-3">
                                    <label for="emailCliente">Email (opcional)</label>
                                    <input type="text" id="emailCliente" name="emailCliente" class="form-control form-control-sm">
                                </div>
                            </div>
                        @endif
                        <div class="form-group row">
                            <div class="col-md-12">
                                <label for="descriptionQuote">Descripción general de cotización </label>
                                <input type="text" id="descriptionQuote" onkeyup="mayus(this);" name="code_description" class="form-control form-control-sm" readonly>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-6 col-lg-2 mb-3">
                                <label for="description">Código de cotización </label>
                                <input type="text" id="codeQuote" readonly value="" onkeyup="mayus(this);" name="code_quote" class="form-control form-control-sm">
                            </div>

                            <div class="col-sm-6 col-lg-2 mb-3">
                                <label for="date_quote">Fecha de cotización </label>
                                <input type="text" class="form-control form-control-sm" id="date_quote" name="date_quote" readonly>
                            </div>
                            <div class="col-sm-6 col-lg-2 mb-3">
                                <label for="date_validate">Válido hasta </label>
                                <input type="text" class="form-control form-control-sm" id="date_validate" name="date_validate" readonly>
                            </div>

                            <div class="col-sm-6 col-lg-2 mb-3">
                                <label for="paymentQuote">Forma de pago </label>
                                <input type="text" id="paymentQuote" name="payment_deadline" class="form-control form-control-sm" readonly>

                            </div>

                            <div class="col-sm-6 col-lg-2 mb-3">
                                <label for="description">Tiempo de entrega </label>
                                <div class="input-group input-group-sm mb-3">
                                    <input type="number" id="timeQuote" step="1" min="0" name="delivery_time" class="form-control form-control-sm" readonly>
                                    <div class="input-group-append">
                                        <span class="input-group-text" id="basic-addon2"> DIAS</span>
                                    </div>
                                </div>

                            </div>
                            <div class="col-sm-6 col-lg-2 mb-3">
                                <label for="customer_id">Cliente </label>
                                <input type="text" id="customer_id" name="customer_id" class="form-control form-control-sm" readonly>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="contact_id">Contacto </label>
                                <input type="text" id="contact_id" name="contact_id" class="form-control form-control-sm" readonly>

                            </div>
                            <div class="col-md-8 mb-3">
                                <label for="observations">Observaciones </label>
                                <textarea class="textarea_observations" id="observations" name="observations" placeholder="Place some text here"
                                          style="width: 100%; height: 200px; font-size: 14px; line-height: 18px; border: 1px solid #dddddd; padding: 10px;"></textarea>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="row" id="body-equipment">
            <div class="col-xl-12">
                <div class="card" data-equip="asd">
                    <div class="card-header">
                        <h3 class="card-title">COTIZACIÓN</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>

                        </div>
                    </div>
                    <div class="card-body next-form-section">
                        <div class="form-group row" style="display: none">

                            <div class="col-xl-12">
                                <label for="description">Detalles de la cotización</label>
                                <textarea class="textarea_edit" data-detailequipment placeholder="Place some text here"
                                          style="width: 100%; height: 200px; font-size: 14px; line-height: 18px; border: 1px solid #dddddd; padding: 10px;"></textarea>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">PRODUCTOS</h3>

                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body next-form-section">
                                <div data-bodyConsumable>
                                    <div class="row d-none d-xl-flex">
                                        <div class="col-xl-5">
                                            <div class="form-group">
                                                <strong>Descripción</strong>
                                            </div>
                                        </div>
                                        <div class="col-xl-1">
                                            <div class="form-group">
                                                <strong>Present.</strong>
                                            </div>
                                        </div>
                                        <div class="col-xl-1">
                                            <div class="form-group">
                                                <strong>Unidad</strong>
                                            </div>
                                        </div>
                                        <div class="col-xl-1">
                                            <div class="form-group">
                                                <strong>Cantidad</strong>
                                            </div>
                                        </div>
                                        <div class="col-xl-1">
                                            <div class="form-group">
                                                <strong>V/U</strong>
                                            </div>
                                        </div>
                                        <div class="col-xl-1">
                                            <div class="form-group">
                                                <strong>P/U</strong>
                                            </div>
                                        </div>
                                        <div class="col-xl-2">
                                            <div class="form-group">
                                                <strong>IMPORTE</strong>
                                            </div>
                                        </div>

                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">SERVICIOS ADICIONALES</h3>

                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body next-form-section">

                                <div class="row d-none d-xl-flex">
                                    <div class="col-xl-5">
                                        <div class="form-group">
                                            <strong>Descripción</strong>
                                        </div>
                                    </div>
                                    <div class="col-xl-1">
                                        <div class="form-group">
                                            <strong>Unidad</strong>
                                        </div>
                                    </div>
                                    <div class="col-xl-1">
                                        <div class="form-group">
                                            <strong>Cantidad</strong>
                                        </div>
                                    </div>
                                    <div class="col-xl-1">
                                        <div class="form-group">
                                            <strong>V/U</strong>
                                        </div>
                                    </div>
                                    <div class="col-xl-1">
                                        <div class="form-group">
                                            <strong>P/U</strong>
                                        </div>
                                    </div>
                                    <div class="col-xl-2">
                                        <div class="form-group">
                                            <strong>Importe</strong>
                                        </div>
                                    </div>

                                    <div class="col-xl-1">
                                        <div class="form-group">
                                            <strong>Facturar</strong>
                                        </div>
                                    </div>

                                </div>
                                <div data-bodyService>

                                </div>
                            </div>
                        </div>
                        <!-- /.card -->

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">DESCUENTO GLOBAL</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="card-body next-form-section" id="discountSection"
                                 data-discount_type=""
                                 data-discount_input_mode=""
                                 data-discount_value="">

                                <div class="row">
                                    <!-- Tipo -->
                                    <div class="col-md-3">
                                        <label>Tipo</label>
                                        <div class="form-group clearfix">
                                            <div class="icheck-primary d-inline">
                                                <input type="radio" name="discount_type"
                                                       id="discount_type_amount"
                                                       value="amount"
                                                        >
                                                <label for="discount_type_amount">Monto (S/)</label>
                                            </div>
                                            <div class="icheck-primary d-inline ml-3">
                                                <input type="radio" name="discount_type"
                                                       id="discount_type_percent"
                                                       value="percent"
                                                        >
                                                <label for="discount_type_percent">Porcentaje (%)</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Valor -->
                                    <div class="col-md-3">
                                        <label>Valor</label>
                                        <input type="number"
                                               class="form-control"
                                               id="discount_value"
                                               min="0"
                                               step="0.01"
                                               value="">
                                    </div>

                                    <!-- Modo -->
                                    <div class="col-md-4">
                                        <label>Modo de ingreso</label>
                                        <div class="form-group clearfix">
                                            <div class="icheck-primary d-inline">
                                                <input type="radio" name="discount_input_mode"
                                                       id="discount_mode_without"
                                                       value="without_igv"
                                                        >
                                                <label for="discount_mode_without">SIN IGV (base)</label>
                                            </div>
                                            <div class="icheck-primary d-inline ml-3">
                                                <input type="radio" name="discount_input_mode"
                                                       id="discount_mode_with"
                                                       value="with_igv"
                                                        >
                                                <label for="discount_mode_with">CON IGV (del total)</label>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                            </div>
                        </div>
                        <!-- /.card -->
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>

        @can('showPrices_quote')
        <div class="row">
            <!-- accepted payments column -->

            <!-- /.col -->
            <div class="col-12 col-md-8 col-xl-5 ml-auto">
                <p class="lead">Resumen de Cotización</p>

                <div class="table-responsive">
                    <table class="table table-sm">
                        <tr>
                            <th style="width:50%">DESCUENTO (-): </th>
                            <td class="text-right">{{ $currency}} <span id="descuento" class="align-right">0.00</span></td>
                        </tr>
                        <tr>
                            <th style="width:50%">GRAVADA: </th>
                            <td class="text-right">{{ $currency}} <span id="gravada" class="align-right">0.00</span></td>
                        </tr>
                        <tr>
                            <th style="width:50%">IGV {{ $igv }}%: </th>
                            <td class="text-right">{{ $currency}} <span id="igv_total" class="align-right">0.00</span></td>
                        </tr>
                        <tr>
                            <th style="width:50%">TOTAL: </th>
                            <td class="text-right">{{ $currency}} <span id="total_importe" class="align-right">0.00</span></td>
                        </tr>
                    </table>
                </div>

            </div>
            <!-- /.col -->
        </div>
        @endcan

        <div class="row">
            <div class="col-12">
                <button type="button" id="btn-submit" class="btn btn-primary float-right">Guardar comprobante</button>
            </div>
        </div>
        <!-- /.card-footer -->
    </form>

    <template id="template-consumable">
        <div class="row" data-consumableRow>

            {{-- Descripción --}}
            <div class="col-12 col-xl-5">
                <div class="form-group">
                    <span class="d-block d-xl-none small text-muted">Descripción</span>
<input type="text" class="form-control form-control-sm" data-consumableDescription readonly>

                    <small
                            data-selected_items
                            class="text-muted d-block mt-1"
                            style="display:none; font-size:12px; line-height:1.2;">
                    </small>

                    {{-- se leen por attr() --}}
                    <input type="hidden" data-consumableid>
                    <input type="hidden" data-descuento>
                    <input type="hidden" data-type_promotion>

                    {{-- presentación --}}
                    <input type="hidden" data-presentation_id>
                    <input type="hidden" data-units_per_pack>
                    <input type="hidden" data-units_equivalent>
                </div>
            </div>

            {{-- Presentación --}}
            <div class="col-6 col-sm-4 col-xl-1">
                <div class="form-group">
                    <span class="d-block d-xl-none small text-muted">Presentación</span>
<input type="text" class="form-control form-control-sm" data-presentation_text readonly>
                </div>
            </div>

            {{-- Unidad --}}
            <div class="col-6 col-sm-4 col-xl-1">
                <div class="form-group">
                    <span class="d-block d-xl-none small text-muted">Unidad</span>
<input type="text" class="form-control form-control-sm" data-consumableUnit readonly>
                </div>
            </div>

            {{-- Cantidad (packs o unidades) --}}
            <div class="col-6 col-sm-4 col-xl-1">
                <div class="form-group">
                    <span class="d-block d-xl-none small text-muted">Cantidad</span>
<input type="number" class="form-control form-control-sm"
                           min="0" step="0.01" data-consumableQuantity
                           oninput="calculateTotalC(this);" readonly>
                </div>
            </div>

            {{-- V/U (sin IGV) --}}
            <div class="col-6 col-sm-4 col-xl-1">
                <div class="form-group">
                    <span class="d-block d-xl-none small text-muted">V/U</span>
<input type="number" class="form-control form-control-sm" data-consumableValor readonly>
                </div>
            </div>

            {{-- P/U (con IGV) --}}
            <div class="col-6 col-sm-4 col-xl-1">
                <div class="form-group">
                    <span class="d-block d-xl-none small text-muted">P/U</span>
<input type="number" class="form-control form-control-sm" data-consumablePrice readonly>
                </div>
            </div>

            {{-- Importe --}}
            <div class="col-6 col-sm-4 col-xl-2">
                <div class="form-group">
                    <span class="d-block d-xl-none small text-muted">Importe</span>
<input type="number" class="form-control form-control-sm text-right" data-consumableImporte readonly>
                </div>
            </div>

        </div>
    </template>

    <template id="template-service">
        <div class="row" data-serviceRow>
            <div class="col-12 col-xl-5">
                <div class="form-group">
                    <span class="d-block d-xl-none small text-muted">Descripción</span>
<input type="text" onkeyup="mayus(this);" class="form-control form-control-sm" data-serviceDescription>
                    <input type="hidden" data-serviceId>
                </div>
            </div>

            <div class="col-6 col-sm-4 col-xl-1">
                <div class="form-group">
                    <span class="d-block d-xl-none small text-muted">Unidad</span>
<input type="text" class="form-control form-control-sm" data-serviceUnit readonly>
                </div>
            </div>

            <div class="col-6 col-sm-4 col-xl-1">
                <div class="form-group">
                    <span class="d-block d-xl-none small text-muted">Cantidad</span>
<input type="number" class="form-control form-control-sm"
                           placeholder="0.00" min="0" step="0.01" data-serviceQuantity>
                </div>
            </div>

            <div class="col-6 col-sm-4 col-xl-1">
                <div class="form-group">
                    <span class="d-block d-xl-none small text-muted">V/U</span>
<input type="number" class="form-control form-control-sm"
                           placeholder="0.00" min="0" data-serviceVU readonly
                           @cannot('showPrices_quote') style="display:none" @endcannot>
                </div>
            </div>

            <div class="col-6 col-sm-4 col-xl-1">
                <div class="form-group">
                    <span class="d-block d-xl-none small text-muted">P/U</span>
<input type="number" class="form-control form-control-sm"
                           placeholder="0.00" min="0" step="0.01" data-servicePU
                           @cannot('showPrices_quote') style="display:none" @endcannot>
                </div>
            </div>

            <div class="col-6 col-sm-4 col-xl-2">
                <div class="form-group">
                    <span class="d-block d-xl-none small text-muted">Importe</span>
<input type="number" class="form-control form-control-sm"
                           placeholder="0.00" min="0" data-serviceImporte readonly
                           @cannot('showPrices_quote') style="display:none" @endcannot>
                </div>
            </div>

            <div class="col-6 col-sm-4 col-xl-1">
                <div class="form-group text-center">
                    <div class="icheck-primary d-inline">
                        <span class="d-block d-xl-none small text-muted">Facturar</span>
<input type="checkbox" data-serviceBillable data-billable-id>
                        <label data-billable-label></label>
                    </div>
                </div>
            </div>

        </div>
    </template>

    <!-- Modal -->
    <div class="modal fade next-aux-modal" id="promotionModal" tabindex="-1" role="dialog" aria-labelledby="promotionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Promociones disponibles</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="promotion-content">
                    <!-- Aquí se cargan dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade next-aux-modal" id="modalBuscarComprobante" tabindex="-1" role="dialog" aria-labelledby="modalBuscarComprobanteLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalBuscarComprobanteLabel">Buscar Cotización</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-row">
                        <div class="col-md-5">
                            <label for="codigoBusqueda">Código</label>
                            <input type="text" id="codigoBusqueda" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-5">
                            <label for="nombreBusqueda">Nombre</label>
                            <input type="text" id="nombreBusqueda" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label for="btnBuscarCotizacion">&nbsp;</label><br>
                            <button type="button" data-url="{{ route('quotes.buscar') }}" class="btn btn-primary btn-block btn-sm" id="btnBuscarCotizacion">Buscar</button>
                        </div>
                    </div>


                    <hr>
                    <div id="resultadosCotizacion" class="table-responsive" tabindex="0" aria-label="Resultados de cotizaciones; desplazamiento horizontal">
                        <!-- Aquí se pintan los resultados por AJAX -->
                    </div>
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
    <script src="{{asset('admin/plugins/typehead/typeahead.bundle.js')}}"></script>
    <script src="{{asset('admin/plugins/summernote/summernote-bs4.min.js')}}"></script>
    <script src="{{asset('admin/plugins/summernote/lang/summernote-es-ES.js')}}"></script>
    <script>
        $(function () {

            $('.textarea_observations').summernote({
                lang: 'es-ES',
                placeholder: 'Ingrese los detalles',
                tabsize: 2,
                height: 120,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['fontname', ['fontname']],
                    ['para', ['ul', 'ol']],
                    ['insert', ['link']],
                    ['view', ['codeview', 'help']]
                ]
            });
            $('.textarea_edit').summernote({
                lang: 'es-ES',
                placeholder: 'Ingrese los detalles',
                tabsize: 2,
                height: 120,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['fontname', ['fontname']],
                    ['para', ['ul', 'ol']],
                    ['insert', ['link']],
                    ['view', ['codeview', 'help']]
                ]
            });


            $('#tipoPago').select2({
                placeholder: "Seleccione Tipo",
            });

            $('#sandbox-container .input-daterange').datepicker({
                todayBtn: "linked",
                clearBtn: true,
                language: "es",
                multidate: false,
                autoclose: true
            });

        })
    </script>

    <script>
        window.PV_CASHBOXES = @json($cashBoxes);
        window.PV_SUBTYPES  = @json($subtypes);
    </script>
    <script src="{{ asset('js/quoteSale/registrarComprobante.js') }}?v={{ time() }}"></script>
@endsection
