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

    <!-- Summernote -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/summernote/summernote-bs4.css') }}">
@endsection

@section('styles')
    <style>
        .select2-search__field {
            width: 100% !important;
        }

        .input-group .select2-container {
            flex: 1 1 auto;
            width: 1% !important;
        }

        .input-group .select2-selection {
            height: 100% !important;
        }

        #modalSelectItemeableItems .modal-dialog {
            max-width: 900px;
        }

        #modalSelectItemeableItems .modal-content {
            max-height: calc(100vh - 30px);
            display: flex;
            flex-direction: column;
        }

        #modalSelectItemeableItems .modal-body {
            overflow-y: auto;
        }

        #modalSelectItemeableItems .itemeable-items-scroll {
            max-height: 310px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
        }

        #modalSelectItemeableItems .itemeable-items-scroll table {
            margin-bottom: 0;
        }

        #modalSelectItemeableItems .modal-footer {
            flex-shrink: 0;
            background: #ffffff;
            position: relative;
            z-index: 2;
        }
    </style>
@endsection

@section('page-header')
    <h1 class="page-title">Cotizaciones</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Crear nueva cotización</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}">
                <i class="fa fa-home"></i> Dashboard
            </a>
        </li>

        <li class="breadcrumb-item">
            <a href="{{ route('quoteSale.index') }}">
                <i class="fa fa-key"></i> Cotizaciones
            </a>
        </li>

        <li class="breadcrumb-item">
            <i class="fa fa-plus-circle"></i> Nuevo
        </li>
    </ol>
@endsection

@section('content')

    <input
            type="hidden"
            id="permissions"
            value="{{ json_encode($permissions) }}"
    >

    <form
            id="formCreate"
            class="form-horizontal"
            data-url="{{ route('quoteSale.store') }}"
            enctype="multipart/form-data"
    >
        @csrf

        {{-- ===================================================== --}}
        {{-- DATOS GENERALES --}}
        {{-- ===================================================== --}}

        <div class="row">
            <div class="col-md-12">

                <div class="card card-success">

                    <div class="card-header">
                        <h3 class="card-title">
                            DATOS GENERALES
                        </h3>

                        <div class="card-tools">
                            <button
                                    type="button"
                                    class="btn btn-tool"
                                    data-card-widget="collapse"
                                    data-toggle="tooltip"
                                    title="Collapse"
                            >
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>

                    <div class="card-body">

                        <div class="form-group row">
                            <div class="col-md-12">
                                <label for="descriptionQuote">
                                    Descripción general de cotización
                                </label>

                                <input
                                        type="text"
                                        id="descriptionQuote"
                                        onkeyup="mayus(this);"
                                        name="code_description"
                                        class="form-control form-control-sm"
                                >
                            </div>
                        </div>

                        <div class="form-group row">

                            <div class="col-md-4">
                                <label for="codeQuote">
                                    Código de cotización
                                </label>

                                <input
                                        type="text"
                                        id="codeQuote"
                                        readonly
                                        value="{{ $codeQuote }}"
                                        name="code_quote"
                                        class="form-control form-control-sm"
                                >
                            </div>

                            <div
                                    class="col-md-4"
                                    id="sandbox-container"
                            >
                                <label for="date_quote">
                                    Fecha de cotización
                                </label>

                                <div
                                        class="input-daterange"
                                        id="datepicker"
                                >
                                    <input
                                            type="text"
                                            class="form-control form-control-sm date-range-filter"
                                            id="date_quote"
                                            name="date_quote"
                                    >
                                </div>
                            </div>

                            <div
                                    class="col-md-4"
                                    id="sandbox-container"
                            >
                                <label for="date_validate">
                                    Válido hasta
                                </label>

                                <div
                                        class="input-daterange"
                                        id="datepicker2"
                                >
                                    <input
                                            type="text"
                                            class="form-control form-control-sm date-range-filter"
                                            id="date_validate"
                                            name="date_validate"
                                    >
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label for="paymentQuote">
                                    Forma de pago
                                </label>

                                <select
                                        id="paymentQuote"
                                        name="payment_deadline"
                                        class="form-control form-control-sm select2"
                                        style="width: 100%;"
                                >
                                    <option></option>

                                    @foreach($paymentDeadlines as $paymentDeadline)
                                        <option value="{{ $paymentDeadline->id }}">
                                            {{ $paymentDeadline->description }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="timeQuote">
                                    Tiempo de entrega
                                </label>

                                <div class="input-group input-group-sm mb-3">

                                    <input
                                            type="number"
                                            id="timeQuote"
                                            step="1"
                                            min="0"
                                            name="delivery_time"
                                            class="form-control form-control-sm"
                                    >

                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            DIAS
                                        </span>
                                    </div>

                                </div>
                            </div>

                            <div class="col-md-4">

                                <label for="customer_id">
                                    Cliente
                                </label>

                                <div class="input-group input-group-sm">

                                    <select
                                            id="customer_id"
                                            name="customer_id"
                                            class="form-control select2bs4"
                                    >
                                        <option></option>

                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">
                                                {{ $customer->business_name }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <div class="input-group-append">
                                        <button
                                                type="button"
                                                class="btn btn-primary"
                                                id="btn-add-customer"
                                        >
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>

                                </div>
                            </div>

                            <div class="col-md-4">
                                <label for="contact_id">
                                    Contacto
                                </label>

                                <select
                                        id="contact_id"
                                        name="contact_id"
                                        class="form-control form-control-sm select2"
                                        style="width: 100%;"
                                >
                                    <option></option>
                                </select>
                            </div>

                            <div class="col-md-8">

                                <label for="observations">
                                    Observaciones
                                </label>

                                <textarea
                                        class="textarea_observations"
                                        id="observations"
                                        name="observations"
                                        placeholder="Ingrese observaciones"
                                        style="
                                        width: 100%;
                                        height: 200px;
                                        font-size: 14px;
                                        line-height: 18px;
                                        border: 1px solid #dddddd;
                                        padding: 10px;
                                    "
                                ></textarea>

                            </div>

                        </div>

                    </div>

                </div>

            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- COTIZACIÓN --}}
        {{-- ===================================================== --}}

        <div
                class="row"
                id="body-equipment"
        >
            <div class="col-md-12">

                <div
                        class="card card-success"
                        data-equip="asd"
                >

                    <div class="card-header">

                        <h3 class="card-title">
                            COTIZACIÓN
                        </h3>

                        <div class="card-tools">
                            <button
                                    type="button"
                                    class="btn btn-tool"
                                    data-card-widget="collapse"
                                    data-toggle="tooltip"
                                    title="Collapse"
                            >
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>

                    </div>

                    <div class="card-body">

                        {{-- IGV vigente --}}
                        <input
                                type="hidden"
                                id="igv"
                                value="{{ $igv }}"
                        >

                        <div
                                class="col-md-12"
                                style="display: none"
                        >
                            <label>
                                Detalles de la cotización
                            </label>

                            <textarea
                                    class="textarea_edit"
                                    data-detailequipment
                                    placeholder="Ingrese los detalles"
                                    style="
                                    width: 100%;
                                    height: 200px;
                                    font-size: 14px;
                                    line-height: 18px;
                                    border: 1px solid #dddddd;
                                    padding: 10px;
                                "
                            ></textarea>
                        </div>

                        {{-- ================================================= --}}
                        {{-- PRODUCTOS --}}
                        {{-- ================================================= --}}

                        <div class="card card-warning">

                            <div class="card-header">

                                <h3 class="card-title">
                                    PRODUCTOS
                                </h3>

                                <div class="card-tools">
                                    <button
                                            type="button"
                                            class="btn btn-tool"
                                            data-card-widget="collapse"
                                    >
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>

                            </div>

                            <div class="card-body">

                                <div class="row">

                                    <div class="col-md-10">
                                        <div class="form-group">

                                            <label>
                                                Seleccionar producto
                                                <span class="right badge badge-danger">
                                                    (*)
                                                </span>
                                            </label>

                                            <select
                                                    class="form-control consumable_search"
                                                    data-consumable
                                                    style="width:100%"
                                                    name="consumable_search"
                                            ></select>

                                        </div>
                                    </div>

                                    <div class="col-md-2">

                                        <label>
                                            &nbsp;
                                        </label>

                                        <button
                                                type="button"
                                                data-addConsumable
                                                class="btn btn-block btn-outline-primary"
                                        >
                                            Agregar
                                            <i class="fas fa-arrow-circle-right"></i>
                                        </button>

                                    </div>

                                </div>

                                <hr>

                                <div data-bodyConsumable>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <strong>Descripción</strong>
                                        </div>

                                        <div class="col-md-1">
                                            <strong>Present.</strong>
                                        </div>

                                        <div class="col-md-1">
                                            <strong>Unidad</strong>
                                        </div>

                                        <div class="col-md-1">
                                            <strong>Cantidad</strong>
                                        </div>

                                        <div class="col-md-1">
                                            <strong>V/U</strong>
                                        </div>

                                        <div class="col-md-1">
                                            <strong>P/U</strong>
                                        </div>

                                        <div class="col-md-2">
                                            <strong>Importe</strong>
                                        </div>

                                        <div class="col-md-1">
                                            <strong>Acción</strong>
                                        </div>
                                    </div>

                                </div>

                            </div>
                        </div>

                        {{-- ================================================= --}}
                        {{-- SERVICIOS ADICIONALES --}}
                        {{-- ================================================= --}}

                        <div class="card card-cyan">

                            <div class="card-header">

                                <h3 class="card-title">
                                    SERVICIOS ADICIONALES
                                </h3>

                                <div class="card-tools">
                                    <button
                                            type="button"
                                            class="btn btn-tool"
                                            data-card-widget="collapse"
                                    >
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>

                            </div>

                            <div class="card-body">

                                <div class="row">

                                    <div class="col-md-4">
                                        <div class="form-group">

                                            <label for="material_search">
                                                Descripción
                                                <span class="right badge badge-danger">
                                                    (*)
                                                </span>
                                            </label>

                                            <input
                                                    type="text"
                                                    id="material_search"
                                                    onkeyup="mayus(this);"
                                                    class="form-control"
                                            >

                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <div class="form-group">

                                            <label>
                                                Unidad
                                                <span class="right badge badge-danger">
                                                    (*)
                                                </span>
                                            </label>

                                            <select
                                                    class="form-control select2 unitMeasure"
                                                    style="width: 100%;"
                                            >
                                                <option></option>

                                                @foreach($unitMeasures as $unitMeasure)
                                                    <option value="{{ $unitMeasure->id }}">
                                                        {{ $unitMeasure->name }}
                                                    </option>
                                                @endforeach
                                            </select>

                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <div class="form-group">

                                            <label for="quantity">
                                                Cantidad
                                                <span class="right badge badge-danger">
                                                    (*)
                                                </span>
                                            </label>

                                            <input
                                                    type="number"
                                                    id="quantity"
                                                    class="form-control"
                                                    placeholder="0.00"
                                                    min="0"
                                                    value="0"
                                                    step="0.01"
                                            >

                                        </div>
                                    </div>

                                    @can('showPrices_quote')
                                        <div class="col-md-2">
                                            <div class="form-group">

                                                <label for="price">
                                                    Precio C/IGV
                                                    <span class="right badge badge-danger">
                                                        (*)
                                                    </span>
                                                </label>

                                                <input
                                                        type="number"
                                                        id="price"
                                                        class="form-control"
                                                        placeholder="0.00"
                                                        min="0"
                                                        value="0"
                                                        step="0.01"
                                                >

                                            </div>
                                        </div>
                                    @endcan

                                    <div class="col-md-2">

                                        <label>
                                            &nbsp;
                                        </label>

                                        <button
                                                type="button"
                                                data-addService
                                                class="btn btn-block btn-outline-primary"
                                        >
                                            Agregar
                                            <i class="fas fa-arrow-circle-right"></i>
                                        </button>

                                    </div>

                                </div>

                                <hr>

                                <div class="row">

                                    <div class="col-md-4">
                                        <strong>Descripción</strong>
                                    </div>

                                    <div class="col-md-1">
                                        <strong>Unidad</strong>
                                    </div>

                                    <div class="col-md-1">
                                        <strong>Cantidad</strong>
                                    </div>

                                    <div class="col-md-1">
                                        <strong>V/U</strong>
                                    </div>

                                    <div class="col-md-1">
                                        <strong>P/U</strong>
                                    </div>

                                    <div class="col-md-2">
                                        <strong>Importe</strong>
                                    </div>

                                    <div class="col-md-1">
                                        <strong>Facturar</strong>
                                    </div>

                                    <div class="col-md-1">
                                        <strong>Acción</strong>
                                    </div>

                                </div>

                                <div data-bodyService>

                                    @foreach($workforces as $workforce)

                                        <div
                                                class="row"
                                                data-serviceRow
                                        >

                                            <div class="col-md-4">
                                                <div class="form-group">

                                                    <input
                                                            type="text"
                                                            onkeyup="mayus(this);"
                                                            class="form-control form-control-sm"
                                                            value="{{ $workforce->description }}"
                                                            data-serviceDescription
                                                    >

                                                    <input
                                                            type="hidden"
                                                            data-serviceId
                                                            value="{{ $workforce->id }}"
                                                    >

                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <div class="form-group">

                                                    <input
                                                            type="text"
                                                            class="form-control form-control-sm"
                                                            value="{{ $workforce->unitMeasure->name }}"
                                                            data-serviceUnit
                                                            readonly
                                                    >

                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <div class="form-group">

                                                    <input
                                                            type="number"
                                                            class="form-control form-control-sm"
                                                            data-serviceQuantity
                                                            min="0"
                                                            value="1.00"
                                                            step="0.01"
                                                    >

                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <div class="form-group">

                                                    <input
                                                            type="number"
                                                            value="{{ $workforce->unit_price }}"
                                                            class="form-control form-control-sm"
                                                            data-serviceVU
                                                            readonly
                                                            @cannot('showPrices_quote')
                                                            style="display:none"
                                                            @endcannot
                                                    >

                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <div class="form-group">

                                                    <input
                                                            type="number"
                                                            value="{{ $workforce->unit_price }}"
                                                            class="form-control form-control-sm"
                                                            data-servicePU
                                                            min="0"
                                                            step="0.01"
                                                            @cannot('showPrices_quote')
                                                            style="display:none"
                                                            @endcannot
                                                    >

                                                </div>
                                            </div>

                                            <div class="col-md-2">
                                                <div class="form-group">

                                                    <input
                                                            type="number"
                                                            class="form-control form-control-sm"
                                                            data-serviceImporte
                                                            value="{{ $workforce->unit_price }}"
                                                            min="0"
                                                            step="0.01"
                                                            readonly
                                                            @cannot('showPrices_quote')
                                                            style="display:none"
                                                            @endcannot
                                                    >

                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <div class="form-group text-center">

                                                    <div class="icheck-primary d-inline">

                                                        <input
                                                                type="checkbox"
                                                                id="billable_{{ $loop->index }}"
                                                                data-serviceBillable
                                                                {{ (isset($workforce->billable) ? $workforce->billable : true) ? 'checked' : '' }}
                                                        >

                                                        <label for="billable_{{ $loop->index }}"></label>

                                                    </div>

                                                </div>
                                            </div>

                                            <div class="col-md-1">

                                                <button
                                                        type="button"
                                                        data-deleteService
                                                        class="btn btn-block btn-outline-danger btn-sm"
                                                >
                                                    <i class="fas fa-trash"></i>
                                                </button>

                                            </div>

                                        </div>

                                    @endforeach

                                </div>

                            </div>
                        </div>

                        {{-- ================================================= --}}
                        {{-- DESCUENTO GLOBAL --}}
                        {{-- ================================================= --}}

                        <div class="card card-purple">

                            <div class="card-header">

                                <h3 class="card-title">
                                    DESCUENTO GLOBAL
                                </h3>

                                <div class="card-tools">

                                    <button
                                            type="button"
                                            class="btn btn-tool"
                                            data-card-widget="collapse"
                                    >
                                        <i class="fas fa-minus"></i>
                                    </button>

                                </div>

                            </div>

                            <div
                                    class="card-body"
                                    id="discountSection"
                                    data-discount_type="amount"
                                    data-discount_input_mode="without_igv"
                                    data-discount_value="0"
                            >

                                <div class="row">

                                    <div class="col-md-3">

                                        <label>
                                            Tipo
                                        </label>

                                        <div class="form-group clearfix">

                                            <div class="icheck-primary d-inline">

                                                <input
                                                        type="radio"
                                                        name="discount_type"
                                                        id="discount_type_amount"
                                                        value="amount"
                                                        checked
                                                >

                                                <label for="discount_type_amount">
                                                    Monto
                                                </label>

                                            </div>

                                            <div class="icheck-primary d-inline ml-3">

                                                <input
                                                        type="radio"
                                                        name="discount_type"
                                                        id="discount_type_percent"
                                                        value="percent"
                                                >

                                                <label for="discount_type_percent">
                                                    Porcentaje (%)
                                                </label>

                                            </div>

                                        </div>
                                    </div>

                                    <div class="col-md-3">

                                        <label>
                                            Valor
                                        </label>

                                        <input
                                                type="number"
                                                class="form-control"
                                                id="discount_value"
                                                min="0"
                                                step="0.01"
                                                value="0"
                                        >

                                        <small
                                                class="text-muted"
                                                id="discount_value_hint"
                                        >
                                            Ingrese monto.
                                        </small>

                                    </div>

                                    <div class="col-md-4">

                                        <label>
                                            Modo de ingreso
                                        </label>

                                        <div class="form-group clearfix">

                                            <div class="icheck-primary d-inline">

                                                <input
                                                        type="radio"
                                                        name="discount_input_mode"
                                                        id="discount_mode_without"
                                                        value="without_igv"
                                                        checked
                                                >

                                                <label for="discount_mode_without">
                                                    SIN IGV (base)
                                                </label>

                                            </div>

                                            <div class="icheck-primary d-inline ml-3">

                                                <input
                                                        type="radio"
                                                        name="discount_input_mode"
                                                        id="discount_mode_with"
                                                        value="with_igv"
                                                >

                                                <label for="discount_mode_with">
                                                    CON IGV (del total)
                                                </label>

                                            </div>

                                        </div>
                                    </div>

                                    <div class="col-md-2">

                                        <label>
                                            &nbsp;
                                        </label>

                                        <button
                                                type="button"
                                                class="btn btn-outline-secondary btn-block"
                                                id="btn-clear-discount"
                                        >
                                            Limpiar
                                        </button>

                                    </div>

                                </div>

                                <div
                                        class="alert alert-warning mt-2 mb-0"
                                        style="font-size: 12px;"
                                >
                                    El descuento se aplicará recién al confirmar/guardar.
                                    En facturación, el descuento afecta la base imponible
                                    (SIN IGV).
                                </div>

                            </div>
                        </div>

                        {{-- ================================================= --}}
                        {{-- CONFIRMAR EQUIPMENT --}}
                        {{-- ================================================= --}}

                        <button
                                type="button"
                                data-confirm
                                class="btn btn-outline-primary float-right"
                                title="Confirmar"
                        >
                            <i class="fas fa-check-square"></i>
                            Confirmar cotización
                        </button>

                        <button
                                type="button"
                                class="btn btn-outline-warning float-right"
                                data-saveEquipment=""
                                style="display:none"
                                title="Guardar cambios"
                        >
                            <i class="fas fa-check-square"></i>
                            Guardar cambios
                        </button>

                    </div>

                </div>

            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- RESUMEN --}}
        {{-- ===================================================== --}}

        @can('showPrices_quote')

            <div class="row">

                <div class="col-6">
                </div>

                <div class="col-6">

                    <p class="lead">
                        Resumen de Cotización
                    </p>

                    <div class="table-responsive">

                        <table class="table">

                            <tr>
                                <th style="width:50%">
                                    DESCUENTO (-):
                                </th>

                                <td>
                                    {{ $currency }}
                                    <span
                                            id="descuento"
                                            data-descuento_real
                                            class="align-right"
                                    >
                                        0.00
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <th style="width:50%">
                                    GRAVADA:
                                </th>

                                <td>
                                    {{ $currency }}
                                    <span
                                            id="gravada"
                                            data-gravada_real
                                            class="align-right"
                                    >
                                        0.00
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <th style="width:50%">
                                    IGV {{ $igv }}%:
                                </th>

                                <td>
                                    {{ $currency }}
                                    <span
                                            id="igv_total"
                                            data-igv_total_real
                                            class="align-right"
                                    >
                                        0.00
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <th style="width:50%">
                                    TOTAL:
                                </th>

                                <td>
                                    {{ $currency }}
                                    <span
                                            id="total_importe"
                                            data-total_importe_real
                                            class="align-right"
                                    >
                                        0.00
                                    </span>
                                </td>
                            </tr>

                        </table>

                    </div>

                </div>

            </div>

        @endcan

        <div class="row">
            <div class="col-12">

                <button
                        type="reset"
                        class="btn btn-outline-secondary"
                >
                    Cancelar
                </button>

                <button
                        type="button"
                        id="btn-submit"
                        class="btn btn-outline-success float-right"
                >
                    Guardar cotización
                </button>

            </div>
        </div>

    </form>

    {{-- ========================================================= --}}
    {{-- TEMPLATE PRODUCTO --}}
    {{-- ========================================================= --}}

    <template id="template-consumable">

        <div
                class="row"
                data-consumableRow
        >

            <div class="col-md-4">
                <div class="form-group">

                    <input
                            type="text"
                            class="form-control form-control-sm"
                            data-consumableDescription
                            readonly
                    >

                    <input
                            type="hidden"
                            data-consumableId
                    >

                    <input
                            type="hidden"
                            data-descuento
                    >

                    <input
                            type="hidden"
                            data-type_promotion
                    >

                    <input
                            type="hidden"
                            data-presentation_id
                    >

                    <input
                            type="hidden"
                            data-units_per_pack
                    >

                    <input
                            type="hidden"
                            data-units_equivalent
                    >

                </div>
            </div>

            <div class="col-md-1">
                <div class="form-group">

                    <input
                            type="text"
                            class="form-control form-control-sm"
                            data-presentation_text
                            readonly
                    >

                </div>
            </div>

            <div class="col-md-1">
                <div class="form-group">

                    <input
                            type="text"
                            class="form-control form-control-sm"
                            data-consumableUnit
                            readonly
                    >

                </div>
            </div>

            <div class="col-md-1">
                <div class="form-group">

                    <input
                            type="number"
                            class="form-control form-control-sm"
                            min="0"
                            step="0.01"
                            data-consumableQuantity
                            oninput="calculateTotalC(this);"
                    >

                </div>
            </div>

            <div class="col-md-1">
                <div class="form-group">

                    <input
                            type="number"
                            class="form-control form-control-sm"
                            data-consumableValor
                            data-consumable_valor_real
                            readonly
                    >

                </div>
            </div>

            <div class="col-md-1">
                <div class="form-group">

                    <input
                            type="number"
                            class="form-control form-control-sm"
                            data-consumablePrice
                            data-consumable_price_real
                            readonly
                    >

                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">

                    <input
                            type="number"
                            class="form-control form-control-sm"
                            data-consumableImporte
                            readonly
                    >

                </div>
            </div>

            <div class="col-md-1">

                <button
                        type="button"
                        data-deleteConsumable
                        class="btn btn-block btn-outline-danger btn-sm"
                >
                    <i class="fas fa-trash"></i>
                </button>

            </div>

        </div>

    </template>

    {{-- ========================================================= --}}
    {{-- TEMPLATE SERVICIO --}}
    {{-- ========================================================= --}}

    <template id="template-service">

        <div
                class="row"
                data-serviceRow
        >

            <div class="col-md-4">
                <div class="form-group">

                    <input
                            type="text"
                            onkeyup="mayus(this);"
                            class="form-control form-control-sm"
                            data-serviceDescription
                    >

                    <input
                            type="hidden"
                            data-serviceId
                    >

                </div>
            </div>

            <div class="col-md-1">
                <div class="form-group">

                    <input
                            type="text"
                            class="form-control form-control-sm"
                            data-serviceUnit
                            readonly
                    >

                </div>
            </div>

            <div class="col-md-1">
                <div class="form-group">

                    <input
                            type="number"
                            class="form-control form-control-sm"
                            placeholder="0.00"
                            min="0"
                            step="0.01"
                            data-serviceQuantity
                    >

                </div>
            </div>

            <div class="col-md-1">
                <div class="form-group">

                    <input
                            type="number"
                            class="form-control form-control-sm"
                            placeholder="0.00"
                            min="0"
                            data-serviceVU
                            readonly

                            @cannot('showPrices_quote')
                            style="display:none"
                            @endcannot
                    >

                </div>
            </div>

            <div class="col-md-1">
                <div class="form-group">

                    <input
                            type="number"
                            class="form-control form-control-sm"
                            placeholder="0.00"
                            min="0"
                            step="0.01"
                            data-servicePU

                            @cannot('showPrices_quote')
                            style="display:none"
                            @endcannot
                    >

                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">

                    <input
                            type="number"
                            class="form-control form-control-sm"
                            placeholder="0.00"
                            min="0"
                            data-serviceImporte
                            readonly

                            @cannot('showPrices_quote')
                            style="display:none"
                            @endcannot
                    >

                </div>
            </div>

            <div class="col-md-1">
                <div class="form-group text-center">

                    <div class="icheck-primary d-inline">

                        <input
                                type="checkbox"
                                data-serviceBillable
                                data-billable-id
                        >

                        <label data-billable-label></label>

                    </div>

                </div>
            </div>

            <div class="col-md-1">

                <button
                        type="button"
                        data-deleteService
                        class="btn btn-block btn-outline-danger btn-sm"
                >
                    <i class="fas fa-trash"></i>
                </button>

            </div>

        </div>

    </template>

    {{-- ========================================================= --}}
    {{-- MODAL PROMOCIONES --}}
    {{-- ========================================================= --}}

    <div
            class="modal fade"
            id="promotionModal"
            tabindex="-1"
            role="dialog"
            aria-labelledby="promotionModalLabel"
            aria-hidden="true"
    >
        <div
                class="modal-dialog modal-lg"
                role="document"
        >
            <div class="modal-content">

                <div class="modal-header">

                    <h5
                            class="modal-title"
                            id="promotionModalLabel"
                    >
                        Promociones disponibles
                    </h5>

                    <button
                            type="button"
                            class="close"
                            data-dismiss="modal"
                            aria-label="Cerrar"
                    >
                        <span aria-hidden="true">
                            &times;
                        </span>
                    </button>

                </div>

                <div
                        class="modal-body"
                        id="promotion-content"
                >
                </div>

            </div>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- MODAL CLIENTE --}}
    {{-- ========================================================= --}}

    <div
            class="modal fade"
            id="modalCustomer"
            tabindex="-1"
            role="dialog"
            aria-labelledby="modalCustomerLabel"
            aria-hidden="true"
    >

        <div
                class="modal-dialog modal-lg"
                role="document"
        >
            <div class="modal-content">

                <div class="modal-header">

                    <h5
                            class="modal-title"
                            id="modalCustomerLabel"
                    >
                        Nuevo Cliente
                    </h5>

                    <button
                            type="button"
                            class="close"
                            data-dismiss="modal"
                            aria-label="Cerrar"
                    >
                        <span aria-hidden="true">
                            &times;
                        </span>
                    </button>

                </div>

                <div class="modal-body">

                    <form
                            id="formCreateCustomer"
                            class="form-horizontal"
                            data-url="{{ route('customer.store') }}"
                            enctype="multipart/form-data"
                    >
                        @csrf

                        <div class="form-group row">

                            <div class="col-md-4">

                                <label class="col-12 col-form-label">
                                    RUC
                                    <span class="right badge badge-danger">
                                        (*)
                                    </span>
                                </label>

                                <input
                                        type="text"
                                        class="form-control"
                                        name="ruc"
                                        placeholder="Ejm: 1234678901"
                                >

                            </div>

                            <div class="col-md-2">

                                <label class="col-12 col-form-label">
                                    Extranjero
                                </label>

                                <input
                                        id="btn-grouped"
                                        type="checkbox"
                                        name="special"
                                        data-bootstrap-switch
                                        data-off-color="danger"
                                        data-on-text="SI"
                                        data-off-text="NO"
                                        data-on-color="success"
                                >

                            </div>

                            <div class="col-md-6">

                                <label class="col-12 col-form-label">
                                    Razón Social
                                    <span class="right badge badge-danger">
                                        (*)
                                    </span>
                                </label>

                                <input
                                        type="text"
                                        class="form-control"
                                        onkeyup="mayus(this);"
                                        name="business_name"
                                        placeholder="Ejm: EDESCE EIRL"
                                >

                            </div>

                        </div>

                        <div class="form-group row">

                            <div class="col-md-6">

                                <label class="col-12 col-form-label">
                                    Dirección
                                </label>

                                <input
                                        type="text"
                                        class="form-control"
                                        onkeyup="mayus(this);"
                                        name="address"
                                >

                            </div>

                            <div class="col-md-6">

                                <label class="col-12 col-form-label">
                                    Ubicación
                                </label>

                                <input
                                        type="text"
                                        class="form-control"
                                        onkeyup="mayus(this);"
                                        name="location"
                                >

                            </div>

                        </div>

                    </form>

                </div>

                <div class="modal-footer">

                    <button
                            type="button"
                            id="btn-submit-customer"
                            class="btn btn-outline-success"
                    >
                        Guardar
                    </button>

                    <button
                            type="button"
                            class="btn btn-outline-secondary"
                            data-dismiss="modal"
                    >
                        Cancelar
                    </button>

                </div>

            </div>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- MODAL CANTIDAD / PRESENTACIONES --}}
    {{-- ========================================================= --}}

    <div
            id="modalQuantityConsumable"
            class="modal fade"
            tabindex="-1"
    >

        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">

                    <h4 class="modal-title">
                        Ingrese cantidad / presentaciones
                    </h4>

                </div>

                <input
                        type="hidden"
                        id="c_quantity_productId"
                >

                <input
                        type="hidden"
                        id="c_quantity_is_itemeable"
                        value="0"
                >

                <div class="modal-body">

                    <div class="form-group row">

                        <div class="col-md-6">

                            <label for="c_quantity_total">
                                Cantidad (Unidad)
                            </label>

                            <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="form-control"
                                    id="c_quantity_total"
                                    value="0"
                            >

                            <small class="text-muted">
                                Esto agrega en unidad usando el precio base del producto.
                            </small>

                        </div>

                        <div class="col-md-6">

                            <label>
                                Stock disponible (unidades)
                            </label>

                            <input
                                    type="text"
                                    class="form-control"
                                    id="c_quantity_stock_show"
                                    readonly
                            >

                        </div>

                    </div>

                    <hr>

                    <div class="mb-2">

                        <strong>
                            Presentaciones
                        </strong>

                        <div
                                class="text-muted"
                                style="font-size: 12px;"
                        >
                            Ingresa cuántos paquetes quieres agregar.
                        </div>

                    </div>

                    <div id="c_presentationsArea">
                        <div class="text-muted">
                            Cargando presentaciones...
                        </div>
                    </div>

                </div>

                <div class="modal-footer">

                    <button
                            type="button"
                            class="btn btn-secondary"
                            id="btn-notAddConsumable"
                    >
                        Cancelar
                    </button>

                    <button
                            type="button"
                            id="btn-add_consumable_modal"
                            class="btn btn-success"
                    >
                        Agregar
                    </button>

                </div>

            </div>
        </div>

    </div>

    {{-- ========================================================= --}}
    {{-- MODAL ÍTEMS FÍSICOS --}}
    {{-- ========================================================= --}}

    <div
            id="modalSelectItemeableItems"
            class="modal fade"
            tabindex="-1"
    >

        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-body">

                    <div class="mb-3">
                        <strong id="itemeable-product-name"></strong>
                    </div>

                    <div class="alert alert-info py-2">

                        Debe seleccionar

                        <strong id="itemeable-required-count">
                            0
                        </strong>

                        ítem(s).

                        Seleccionados:

                        <strong id="itemeable-selected-count">
                            0
                        </strong>

                        /

                        <strong id="itemeable-selected-required-count">
                            0
                        </strong>

                    </div>

                    <div
                            id="itemeable-items-loading"
                            class="text-center py-3"
                    >
                        <i class="fa fa-spinner fa-spin"></i>
                        Cargando ítems disponibles...
                    </div>

                    <div
                            id="itemeable-items-empty"
                            class="alert alert-warning"
                            style="display: none;"
                    >
                        No existen ítems disponibles para este producto.
                    </div>

                    <div class="form-group mb-3">

                        <label for="itemeable-item-search">
                            Escanear o ingresar código de ítem
                        </label>

                        <input
                                type="text"
                                id="itemeable-item-search"
                                class="form-control form-control-sm"
                                autocomplete="off"
                                placeholder="Escanee o escriba el código del ítem"
                        >

                    </div>

                    <div
                            id="itemeable-items-table-container"
                            style="display: none;"
                    >

                        <div class="itemeable-items-scroll">

                            <table class="table table-sm table-bordered mb-0">

                                <thead class="thead-light">
                                <tr>

                                    <th
                                            style="width: 70px;"
                                            class="text-center"
                                    >
                                        Elegir
                                    </th>

                                    <th>
                                        Código
                                    </th>

                                    <th>
                                        Lote
                                    </th>

                                    <th>
                                        Ubicación
                                    </th>

                                </tr>
                                </thead>

                                <tbody id="itemeable-items-table-body"></tbody>

                            </table>

                        </div>

                    </div>

                    <div
                            id="itemeable-items-error"
                            class="alert alert-danger"
                            style="display: none;"
                    >
                        No se pudieron cargar los ítems disponibles.
                    </div>

                </div>

                <div class="modal-footer">

                    <button
                            type="button"
                            class="btn btn-secondary btn-sm"
                            id="btn-cancel-itemeable-items"
                    >
                        Cancelar
                    </button>

                    <button
                            type="button"
                            class="btn btn-success btn-sm"
                            id="btn-confirm-itemeable-items"
                            disabled
                    >
                        Confirmar ítems
                    </button>

                </div>

            </div>
        </div>

    </div>

@endsection


@section('plugins')

    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/locales/bootstrap-datepicker.es.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/typehead/typeahead.bundle.js') }}"></script>

@endsection


@section('scripts')

    <script src="{{ asset('admin/plugins/summernote/summernote-bs4.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/summernote/lang/summernote-es-ES.js') }}"></script>

    <script>
        $(function () {

            $('.textarea_observations').summernote({
                lang: 'es-ES',
                placeholder: 'Ingrese los detalles',
                tabsize: 2,
                height: 120,
                toolbar: [
                    ['style', [
                        'bold',
                        'italic',
                        'underline',
                        'clear'
                    ]],
                    ['fontname', [
                        'fontname'
                    ]],
                    ['para', [
                        'ul',
                        'ol'
                    ]],
                    ['insert', [
                        'link'
                    ]],
                    ['view', [
                        'codeview',
                        'help'
                    ]]
                ]
            });

            $('.textarea_edit').summernote({
                lang: 'es-ES',
                placeholder: 'Ingrese los detalles',
                tabsize: 2,
                height: 120,
                toolbar: [
                    ['style', [
                        'bold',
                        'italic',
                        'underline',
                        'clear'
                    ]],
                    ['fontname', [
                        'fontname'
                    ]],
                    ['para', [
                        'ul',
                        'ol'
                    ]],
                    ['insert', [
                        'link'
                    ]],
                    ['view', [
                        'codeview',
                        'help'
                    ]]
                ]
            });

            $('#customer_id').select2({
                placeholder: 'Seleccione cliente',
                theme: 'bootstrap4',
                width: 'resolve'
            });

            $('#contact_id').select2({
                placeholder: 'Seleccione contacto'
            });

            $('#paymentQuote').select2({
                placeholder: 'Seleccione forma de pago'
            });

            $('.unitMeasure').select2({
                placeholder: 'Seleccione unidad'
            });

            $('#date_quote').attr(
                'value',
                moment().format('DD/MM/YYYY')
            );

            $('#date_validate').attr(
                'value',
                moment()
                    .add(5, 'days')
                    .format('DD/MM/YYYY')
            );

            $('#sandbox-container .input-daterange')
                .datepicker({
                    todayBtn: 'linked',
                    clearBtn: true,
                    language: 'es',
                    multidate: false,
                    autoclose: true
                });

            $('input[data-bootstrap-switch]')
                .each(function () {
                    $(this).bootstrapSwitch();
                });

        });
    </script>

    <script>

        window.APP_QUOTE =
            window.APP_QUOTE || {};

        window.APP_QUOTE.URLS =
            window.APP_QUOTE.URLS || {};

        window.APP_QUOTE.URLS.AVAILABLE_ITEMS =
            "{{ route(
                'quotes.stock-items.available-items',
                ':stockItemId'
            ) }}";

    </script>

    <script
            src="{{ asset('js/quoteSale/create.js') }}?v={{ time() }}"
    ></script>

@endsection