@extends('layouts.appAdmin2')

@section('body-class', 'next-pos-shell')

@section('openPuntoVenta')
    menu-open
@endsection

@section('activePuntoVenta')
    active
@endsection

@section('activeCreatePuntoVentaV2')
    active
@endsection

@section('title')
    Punto de Venta
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/typehead/typeahead.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.standalone.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.standalone.css') }}">

@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('admin/dist/css/venti-next-pos.css') }}">
@endsection

@section('page-header')
    <h1 class="page-title">Punto de Venta</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Crear nueva venta</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('puntoVenta.list') }}"><i class="fa fa-archive"></i> Punto de venta</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Nueva venta</li>
    </ol>
@endsection

@section('content')
    @include('puntoVenta._catalogContext')
    <form id="formCreate" class="form-horizontal" data-pos-image-fallback="{{ asset('images/material/no_image.png') }}" data-url="{{ route('puntoVenta.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="next-pos-layout">
            <div class="next-pos-sale-column">
                <div class="card next-pos-sale">
                    <div class="card-header">
                        <h3 class="card-title">Venta actual</h3>
                    </div>
                    <div class="card-body">
                        <div class="shopping-cart">
                            <div class="next-pos-checkout">
                                <div class="next-pos-sale-section">
                                    <div id="body-cart" class="next-pos-cart-lines" aria-live="polite"></div>

                                    <h3 class="next-pos-section-label">Resumen monetario</h3>

                                    <div class="d-flex justify-content-between next-pos-total-row">
                                        <p class="fw-bold">OP. EXONERADA:</p>
                                        <p class="fw-bold" id="op_exonerada">0.00</p>
                                    </div>
                                    <div class="d-flex justify-content-between next-pos-total-row">
                                        <p class="fw-bold">OP. INAFECTA</p>
                                        <p class="fw-bold" id="op_inafecta">0.00</p>
                                    </div>
                                    <div class="d-flex justify-content-between next-pos-total-row">
                                        <p class="fw-bold">OP. GRAVADA:</p>
                                        <p class="fw-bold" id="op_gravada">0.00</p>
                                    </div>
                                    <div class="d-flex justify-content-between next-pos-total-row">
                                        <p class="fw-bold">I.G.V.:</p>
                                        <p class="fw-bold" id="total_igv">0.00</p>
                                    </div>
                                    <div class="d-flex justify-content-between next-pos-total-row">
                                        <p class="fw-bold">TOTAL DESCUENTOS:</p>
                                        <p class="fw-bold" id="total_descuentos">0.00</p>
                                    </div>
                                    <div class="d-flex justify-content-between next-pos-grand-total">
                                        <h5 class="fw-bold mb-0">Total</h5>
                                        <h5 class="fw-bold mb-0" id="total_importe">0.00</h5>
                                    </div>

                                </div>
                                <div class="next-pos-sale-section">
                                    <h3 class="next-pos-section-label">Datos de cobro</h3>
                                    @if($askWorker)<p class="next-pos-help">Trabajador opcional: se selecciona al cobrar.</p>@endif
                                    <input type="hidden"
                                           name="negocio_acepta_pagos_parciales"
                                           id="negocio_acepta_pagos_parciales"
                                           value="{{ $pagos_parciales }}">

                                    <div data-mdb-input-init class="form-outline mb-1">
                                        {{--@foreach( $tipoPagos as $tipoPago )
                                            <div class="form-group clearfix">
                                                <div class="icheck-primary d-inline">
                                                    <input type="radio" data-vuelto="{{$tipoPago->vuelto}}" name="tipo_pago" value="{{$tipoPago->id}}" id="checkboxPrimary{{$tipoPago->id}}">
                                                    <label for="checkboxPrimary{{$tipoPago->id}}">
                                                        {{$tipoPago->description}}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach--}}
                                        @if ($pagos_parciales == 's')
                                            <div class="form-group next-pos-partial-payment">
                                                <label class="mb-0" for="pagos_parciales_venta">Pagos parciales</label>

                                                <input type="checkbox"
                                                       name="pagos_parciales_venta" id="pagos_parciales_venta"
                                                       value="s"
                                                       data-bootstrap-switch data-size="mini"
                                                       data-on-text="SI"
                                                       data-off-text="NO"
                                                       data-on-color="success"
                                                       data-off-color="danger">
                                            </div>
                                        @endif
                                        <div id="wrap_pago_normal">
                                            <div class="form-group">
                                                <label><b>Caja (CashBox)</b></label>
                                                <select id="pv_cash_box_id" class="form-control select2" style="width:100%;">
                                                    <option value="">Seleccione caja...</option>
                                                    @foreach($cashBoxes as $b)
                                                        <option value="{{ $b->id }}"
                                                                data-type="{{ $b->type }}"
                                                                data-uses_subtypes="{{ $b->uses_subtypes ? 1 : 0 }}">
                                                            {{ $b->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="form-group" id="wrap_pv_subtype" style="display:none;">
                                                <label><b>Subtipo bancario</b></label>
                                                <select id="pv_cash_box_subtype_id" class="form-control select2" style="width:100%;">
                                                    <option value="">Seleccione subtipo...</option>
                                                    @foreach($subtypes as $st)
                                                        <option value="{{ $st->id }}"
                                                                data-cash-box-id="{{ $st->cash_box_id }}" data-is_deferred="{{ $st->is_deferred ? 1 : 0 }}">
                                                            {{ $st->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted" id="pv_subtype_hint" style="display:none;">
                                                    Este canal es diferido: quedará pendiente hasta regularización.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="next-pos-sale-section" id="wrap_comprobante">
                                    <div class="accordion" id="accordionInvoice">
                                        <!-- Sección del acordeón -->
                                        <div class="card">
                                            <div class="card-header p-0" id="headingOneInvoice">
                                                <p class="mb-0 d-flex align-items-center justify-content-between">
                                                    <!-- Título del acordeón -->
                                                    <span class="d-flex align-items-center">

                                                        Comprobante
                                                    </span>
                                                    <!-- Link de agregar -->
                                                    <a href="#" class="btn btn-link p-0 d-inline-flex align-items-center" data-toggle="collapse" data-target="#collapseOneInvoice" aria-expanded="false" aria-controls="collapseOneInvoice">
                                                        Solicitar
                                                    </a>
                                                </p>
                                            </div>

                                            <!-- Contenido del acordeón -->
                                            <div id="collapseOneInvoice" class="collapse" aria-labelledby="headingOne" data-parent="#accordionInvoice">
                                                <div class="card-body">
                                                    <!-- Botones radio -->
                                                    <div class="form-group">
                                                        <label>Tipo de comprobante:</label><br>

                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="invoice_type" id="radio_none" value="ninguno" checked>
                                                            <label class="form-check-label" for="radio_none">Ticket</label>
                                                        </div>
                                                        @can('generarBoleta_puntoVenta')
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="invoice_type" id="radio_boleta" value="boleta">
                                                            <label class="form-check-label" for="radio_boleta">Boleta</label>
                                                        </div>
                                                        @endcan
                                                        @can('generarFactura_puntoVenta')
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="invoice_type" id="radio_factura" value="factura">
                                                            <label class="form-check-label" for="radio_factura">Factura</label>
                                                        </div>
                                                        @endcan
                                                    </div>

                                                    <!-- Campos para Boleta -->
                                                    <div id="datos_boleta" class="d-none">
                                                        <div class="form-group">
                                                            <label for="dni">DNI <span style="color:red;">*</span></label>
                                                            <div class="input-group input-group-sm">
                                                                <input
                                                                        type="text"
                                                                        maxlength="8"
                                                                        id="dni"
                                                                        name="dni"
                                                                        class="form-control"
                                                                        inputmode="numeric"
                                                                        enterkeyhint="search"
                                                                >
                                                                <div class="input-group-append">
                                                                    <button
                                                                            type="button"
                                                                            class="btn btn-primary btnConsultarCliente"
                                                                            data-input="#dni"
                                                                            title="Consultar DNI"
                                                                    >
                                                                        <i class="fas fa-search"></i>
                                                                    </button>
                                                                </div>
                                                                {{--<input type="text" name="dni" id="dni" class="form-control" >--}}
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="dni">Nombre <span style="color:red;">*</span></label>
                                                            <input type="text" name="name" id="name" class="form-control">
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="email_invoice_boleta">Email (Opcional)</label>
                                                            <input type="text" name="email_invoice_boleta" class="form-control">
                                                        </div>
                                                    </div>

                                                    <!-- Campos para Factura -->
                                                    <div id="datos_factura" class="d-none">
                                                        <div class="form-group">
                                                            <label for="ruc">RUC <span style="color:red;">*</span></label>
                                                            <div class="input-group input-group-sm">
                                                                <input
                                                                        type="text"
                                                                        maxlength="11"
                                                                        id="ruc"
                                                                        name="ruc"
                                                                        value=""
                                                                        class="form-control"
                                                                        inputmode="numeric"
                                                                        enterkeyhint="search"
                                                                >
                                                                {{--<input type="text" name="ruc" id="ruc" class="form-control" >--}}
                                                                <div class="input-group-append">
                                                                    <button
                                                                            type="button"
                                                                            class="btn btn-primary btnConsultarCliente"
                                                                            data-input="#ruc"
                                                                            title="Consultar RUC"
                                                                    >
                                                                        <i class="fas fa-search"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="razon_social">Razón Social <span style="color:red;">*</span></label>
                                                            <input type="text" name="razon_social" id="razon_social" class="form-control" >
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="direccion_fiscal">Dirección Fiscal <span style="color:red;">*</span></label>
                                                            <input type="text" name="direccion_fiscal" id="direccion_fiscal" class="form-control" >
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="email_invoice_factura">Email (Opcional)</label>
                                                            <input type="text" name="email_invoice_factura" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="next-pos-sale-section next-pos-checkout-actions">
                                    <button type="button" id="btn-pay" class="btn btn-primary btn-block">Cobrar</button>

                                    <a href="#" target="_blank" id="btn-printDocument" class="btn btn-primary btn-block btn-lg">IMPRIMIR COMPROBANTE</a>

                                    <button  type="button" id="btn-newSale" class="btn btn-warning btn-block btn-lg">NUEVA VENTA</button>

                                </div>
                            </div>
                        </div>

                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <div class="next-pos-catalog-column">
                <div class="card next-pos-catalog">
                    <div class="card-header">
                        <h3 class="card-title">Productos</h3>
                    </div>
                    <div class="card-body">
                        <div class="next-pos-search-row" role="search">
                            <div class="next-pos-search"><div class="input-group">
                                    <input type="text" class="form-control" id="product_search" placeholder="Buscar producto, SKU o código" aria-label="Buscar producto, SKU o código">
                                    <div class="input-group-append">
                                        <button type="button" id="btn_search" class="btn btn-primary" title="Buscar productos" aria-label="Buscar productos">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-info" data-pos-stock-search
                                                title="Buscar en otras sucursales" aria-label="Buscar en otras sucursales" data-placement="top">
                                            <i class="fas fa-search-location" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div></div>
                        <div class="next-pos-catalog-options"><label class="sr-only" for="type_id">Vista</label><div><select title="Vista" id="type_id" class="form-control select2" name="type_id" data-states style="width: 100%;">
                                    <option></option>
                                    <option value="c" selected>Celdas</option>
                                    <option value="f">Filas</option>
                                </select></div></div>
                            <div class="next-pos-category"><label class="sr-only" for="category_id">Categoría</label><select id="category_id" class="form-control select2" name="category_id" data-states style="width: 100%;">
                                    <option></option>
                                    @foreach( $categories as $category )
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select></div>
                        </div>
                        <div class="next-pos-results">
                            <div class="d-flex flex-wrap align-items-center">
                                <h5 class="next-pos-result-count"><span id="numberItems"></span> Productos encontrados</h5>
                            </div>
                        </div>

                        <div class="next-pos-product-grid" id="body-card" aria-live="polite">
                        </div>

                        <div class="next-list-pagination next-pos-pagination">
                            <div class="fs-6 fw-bold text-gray-700" id="textPagination"></div>
                            <ul class="pagination" id="pagination"></ul>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </form>

    <div id="modalVuelto" class="modal fade next-aux-modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Ingrese el monto</h4>
                </div>

                <div class="modal-body">
                    <div class="form-group row">
                        <div class="col-md-6">
                            <label for="monto_total">Monto Total</label>
                            <input type="number" min="0" class="form-control" id="monto_total" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="importe_total">Importe</label>
                            <input type="number" min="0" class="form-control" id="importe_total">
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-6 ">
                            <label for="vuelto">Vuelto</label>
                            <input type="number" min="0" class="form-control" id="vuelto" readonly>
                        </div>
                        <div class="col-md-6 ">
                            {{--<label for="vuelto">Caja</label>
                            <select id="type_caja" class="form-control select2" name="type_caja" data-states style="width: 100%;">
                                <option></option>
                                <option value="efectivo" selected>Efectivo</option>
                                <option value="yape">Yape</option>
                                <option value="plin">Plin</option>
                                <option value="bancario">Bancario</option>
                            </select>--}}
                            <label for="pv_vuelto_cash_box_id">Caja para el vuelto</label>
                            <select id="pv_vuelto_cash_box_id" class="form-control select2" style="width: 100%;">
                                <option value="">Seleccione caja...</option>
                                @foreach($cashBoxes as $b)
                                    <option value="{{ $b->id }}"
                                            data-type="{{ $b->type }}"
                                            data-uses_subtypes="{{ $b->uses_subtypes ? 1 : 0 }}"
                                            {{ $b->type === 'cash' ? 'selected' : '' }}>
                                        {{ $b->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Por defecto el vuelto sale de efectivo, pero puedes cambiarlo.</small>

                            <div id="wrap_vuelto_subtype" style="display:none; margin-top:10px;">
                                <label for="pv_vuelto_subtype_id">Subtipo bancario (para vuelto)</label>
                                <select id="pv_vuelto_subtype_id" class="form-control select2" style="width: 100%;">
                                    <option value="">Seleccione subtipo...</option>
                                    @foreach($subtypes as $st)
                                        <option value="{{ $st->id }}">{{ $st->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Si el vuelto sale de bancario, seleccione el canal (Yape/Plin/Transfer/POS).</small>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btn-notSave">Cancelar</button>
                    <button type="button" id="btn-save" class="btn btn-success">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    {{--<div id="modalQuantity" class="modal fade next-aux-modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Ingrese cantidad</h4>
                </div>

                <input type="hidden" id="quantity_productId">
                <input type="hidden" id="quantity_productPrice">
                <input type="hidden" id="quantity_productStock">
                <input type="hidden" id="quantity_productName">
                <input type="hidden" id="quantity_productUnit">
                <input type="hidden" id="quantity_productTax">
                <input type="hidden" id="quantity_productType">

                <div class="modal-body">
                    <div class="form-group row">
                        <div class="col-md-6">
                            <label for="quantity_total">Cantidad</label>
                            <input type="number" min="0" class="form-control" id="quantity_total">
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btn-notAddProduct">Cancelar</button>
                    <button type="button" id="btn-add_product" class="btn btn-success">Agregar</button>
                </div>
            </div>
        </div>
    </div>--}}
    {{--<div id="modalQuantity" class="modal fade next-aux-modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h4 class="modal-title">Ingrese cantidad / presentaciones</h4>
                </div>

                <input type="hidden" id="quantity_productId">
                <input type="hidden" id="quantity_materialId">
                <input type="hidden" id="quantity_productPrice">
                <input type="hidden" id="quantity_productStock">
                <input type="hidden" id="quantity_productName">
                <input type="hidden" id="quantity_productUnit">
                <input type="hidden" id="quantity_productTax">
                <input type="hidden" id="quantity_productType">

                <div class="modal-body">

                    <!-- Cantidad unitaria (igual que antes) -->
                    <div class="form-group row">
                        <div class="col-md-6">
                            <label for="quantity_total">Cantidad (Unidad)</label>
                            <input type="number" min="0" step="1" class="form-control" id="quantity_total" value="0">
                            <small class="text-muted">Esto vende en unidad usando el precio base del producto.</small>
                        </div>
                        <div class="col-md-6">
                            <label>Stock disponible (unidades)</label>
                            <input type="text" class="form-control" id="quantity_stock_show" readonly>
                        </div>
                    </div>

                    <hr>

                    <!-- Presentaciones -->
                    <div class="mb-2">
                        <strong>Presentaciones</strong>
                        <div class="text-muted" style="font-size: 12px;">
                            Ingresa cuántos “paquetes” (docenas, cuartos, etc.) quieres vender.
                        </div>
                    </div>

                    <div id="presentationsArea">
                        <div class="text-muted">Cargando presentaciones...</div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btn-notAddProduct">Cancelar</button>
                    <button type="button" id="btn-add_product" class="btn btn-success">Agregar</button>
                </div>

            </div>
        </div>
    </div>--}}

    <div id="modalPresentaciones" class="modal fade next-aux-modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header py-2">
                    <h5 class="modal-title">Presentaciones</h5>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <input type="hidden" id="quantity_materialId">
                    <input type="hidden" id="quantity_stockItemId">

                    <div class="form-group mb-2">
                        <label class="mb-1 font-weight-bold">
                            Unidades disponibles
                        </label>
                        <input
                                type="text"
                                class="form-control form-control-sm"
                                id="quantity_stock_show"
                                readonly
                                style="max-width: 140px;">
                    </div>

                    <div class="mb-2">
                        <strong>Presentaciones</strong>
                        <div class="text-muted" style="font-size: 12px;">
                            Ingresa cuántos “paquetes” quieres vender.
                        </div>
                    </div>

                    <div id="presentationsArea">
                        <div class="text-muted">Cargando presentaciones...</div>
                    </div>

                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="button" id="btnAgregarPresentaciones" class="btn btn-success btn-sm">
                        Agregar
                    </button>
                </div>

            </div>
        </div>
    </div>
    {{--<template id="item-cart">
        <div class="d-flex align-items-center mb-3 w-100">
            <div class="flex-grow-1 ms-3">
                <a href="#!" class="float-right" data-delete><i class="fas fa-times"></i></a>
                <h5 class="text-primary" data-name>Samsung Galaxy M11 64GB</h5>
                <h6 style="color: #9e9e9e;" data-price data-stock></h6>
                <div class="d-flex align-items-center justify-content-between w-100">

                    <div class="def-number-input number-input safari_only">
                        <button type="button" data-mdb-button-init onclick="decrementQuantity(this)" data-product_id_minus class="minus"></button>
                        <input class="quantity fw-bold bg-body-tertiary text-body large-input" data-quantity min="0" name="quantity" type="number" step="0.01">
                        <button type="button" data-mdb-button-init onclick="incrementQuantity(this)" data-product_id_plus class="plus"></button>
                    </div>
                    <p class="fw-bold mb-0 me-5 pe-3" data-priceTotal>799$</p>
                </div>
                <div class="align-items-center justify-content-between">
                    <h6 class="text-success" data-discount></h6>
                </div>

                <hr class="mb-4" style="height: 0.8px; background-color: #9e9e9e; opacity: 1;">
            </div>
        </div>
    </template>--}}

    <template id="item-cart">
        <div class="d-flex align-items-center w-100 next-pos-cart-line" data-cart-row>
            <div class="flex-grow-1 next-pos-cart-copy">

                <a href="#!" class="float-right next-pos-remove" aria-label="Quitar producto" title="Quitar producto" data-delete><i class="fas fa-times"></i></a>

                <h5 class="next-pos-cart-name" data-name>Producto</h5>

                <!-- NUEVO: etiqueta de presentación -->
                <div class="text-muted" style="font-size: 12px;" data-presentation_label></div>

                <!-- Solo aparecerá para productos itemeables -->
                <small
                        data-selected_items
                        class="text-muted d-block"
                        style="display:none; font-size: 11px;">
                </small>

                <h6 style="color: #9e9e9e;" data-price data-stock></h6>

                <div class="d-flex align-items-center justify-content-between w-100">
                    <div class="def-number-input number-input safari_only next-pos-quantity">
                        <button type="button" onclick="decrementQuantity(this)" data-item_key_minus class="minus" aria-label="Disminuir cantidad" title="Disminuir cantidad"><i class="fas fa-minus" aria-hidden="true"></i></button>

                        <input class="quantity fw-bold bg-body-tertiary text-body large-input"
                               data-quantity aria-label="Cantidad del producto"
                               min="0"
                               name="quantity"
                               type="number"
                               step="1">

                        <button type="button" onclick="incrementQuantity(this)" data-item_key_plus class="plus" aria-label="Aumentar cantidad" title="Aumentar cantidad"><i class="fas fa-plus" aria-hidden="true"></i></button>
                    </div>

                    <p class="fw-bold mb-0 me-5 pe-3" data-priceTotal>0.00</p>
                </div>

                <div class="align-items-center justify-content-between">
                    <h6 class="text-success" data-discount></h6>
                </div>
            </div>
        </div>
    </template>

    <template id="item-card">
        <article class="next-pos-product product-card">
            <div class="next-pos-product-media"><img data-image1 alt="" loading="lazy"></div>
            <div class="next-pos-product-body">
                <h3 class="next-pos-product-name" data-name></h3>
                <div class="next-pos-product-meta" data-pos-sku></div>
                <div class="next-pos-product-meta" data-pos-presentation></div>
                <p class="next-pos-product-price" data-price></p>
                <p class="next-pos-product-stock" data-pos-local-stock></p>
                <div class="next-pos-product-actions">
                    <button type="button" class="btn btn-primary btn-sm next-pos-icon" data-add_cart_special data-product_id data-product_price data-product_stock data-product_name data-product_unit data-product_tax data-product_type aria-label="Seleccionar opciones" title="Seleccionar variante / presentación"><i class="fas fa-layer-group" aria-hidden="true"></i></button>
                </div>
            </div>
        </article>
    </template>

    <template id="item-card-empty">
        <div class="next-pos-empty" role="status">
            No se ha encontrado ningún producto
        </div>
    </template>

    <template id="previous-page">
        <li class="page-item previous">
            <a href="#" class="page-link" data-item><
                <i class="previous"></i>
            </a>
        </li>
    </template>

    <template id="item-page">
        <li class="page-item" data-active>
            <a href="#" class="page-link" data-item="">5</a>
        </li>
    </template>

    <template id="next-page">
        <li class="page-item next">
            <a href="#" class="page-link" data-item>>
                <i class="next"></i>
            </a>
        </li>
    </template>

    <template id="disabled-page">
        <li class="page-item disabled">
            <span class="page-link">...</span>
        </li>
    </template>

    <template id="item-table">
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Imagen</th>
                <th>Nombre</th>
                <th>Precio</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody id="table-body">
            <tr data-row>
                <td>
                    <img class="img-thumbnail product-image" data-image1 width="50" height="50"/>
                </td>
                <td class="product-title" data-name>Yail wrist watch</td>
                <td class="product-price" data-price>$299</td>
                <td>
                    <a href="#" class="btn details btn-sm"
                       data-add_cart_special data-product_id data-product_price
                       data-product_name data-product_unit data-product_tax data-product_type>
                        ADD SPECIAL
                    </a>
                    <a href="#" class="btn cart btn-sm btn-primary"
                       data-add_cart data-product_id data-product_price
                       data-product_name data-product_unit data-product_tax>
                        ADD TO CART
                    </a>
                </td>
            </tr>
            </tbody>
        </table>
    </template>

    <div class="modal fade next-aux-modal next-pos-wide" id="modalStockItemsVenta" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">

                <div class="modal-header py-2">
                    <h5 class="modal-title">
                        Seleccionar presentación / variante
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body p-2">

                    <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                        <table class="table table-sm table-bordered table-hover mb-0 next-pos-variant-table">
                            <thead class="thead-light">
                            <tr>
                                <th class="next-pos-col-variant text-left">Variante</th>
                                <th class="next-pos-col-sku text-center">SKU</th>
                                <th class="next-pos-col-barcode text-center">Código barras</th>
                                <th class="next-pos-col-available text-center">Disponible</th>
                                <th class="next-pos-col-price text-right">Precio tienda</th>
                                <th class="next-pos-col-quantity text-center">Cantidad</th>
                                <th class="next-pos-col-action text-center">Acción</th>
                            </tr>
                            </thead>
                            <tbody id="tbody-stock-items-venta">
                            <tr>
                                <td colspan="7" class="text-center">
                                    Cargando...
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                        Cerrar
                    </button>

                    <button type="button" class="btn btn-success btn-sm" id="btn-agregar-todos-stock-items">
                        Agregar al carrito
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div id="modalSelectItemeableItemsV2" class="modal fade next-aux-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">

                {{--<div class="modal-header py-2">
                    <h5 class="modal-title">
                        Seleccionar ítems físicos
                    </h5>

                    <button type="button" class="close" id="btn-close-itemeable-items-v2">
                        <span>&times;</span>
                    </button>
                </div>--}}

                <div class="modal-body">

                    <div class="mb-2">
                        <strong id="itemeable-product-name-v2"></strong>
                    </div>

                    <div class="alert alert-info py-2 mb-2">
                        Debe seleccionar exactamente
                        <strong id="itemeable-required-count-v2">0</strong>
                        ítem(s).

                        Seleccionados:
                        <strong id="itemeable-selected-count-v2">0</strong>
                        /
                        <strong id="itemeable-selected-required-count-v2">0</strong>
                    </div>

                    <div class="form-group mb-2">
                        <label for="itemeable-item-search-v2">
                            Escanear o ingresar código de ítem
                        </label>
                        <input
                                type="text"
                                id="itemeable-item-search-v2"
                                class="form-control form-control-sm"
                                autocomplete="off"
                                placeholder="Escanee o escriba el código del ítem">
                    </div>

                    <div id="itemeable-items-loading-v2" class="text-center text-muted py-3">
                        Cargando ítems disponibles...
                    </div>

                    <div id="itemeable-items-empty-v2" class="text-center text-muted py-3" style="display:none;">
                        No se encontraron ítems disponibles.
                    </div>

                    <div id="itemeable-items-error-v2" class="alert alert-danger" style="display:none;">
                        Ocurrió un error al consultar los ítems disponibles.
                    </div>

                    <div
                            id="itemeable-items-table-container-v2"
                            class="table-responsive"
                            style="display:none; max-height:330px; overflow-y:auto;">

                        <table class="table table-sm table-bordered table-hover mb-0">
                            <thead class="thead-light">
                            <tr>
                                <th style="width:55px;">Elegir</th>
                                <th>Código</th>
                                <th>Lote</th>
                                <th>Ubicación</th>
                            </tr>
                            </thead>

                            <tbody id="itemeable-items-table-body-v2"></tbody>
                        </table>
                    </div>

                </div>

                <div class="modal-footer py-2">
                    <button
                            type="button"
                            class="btn btn-secondary btn-sm"
                            id="btn-cancel-itemeable-items-v2">
                        Cancelar
                    </button>

                    <button
                            type="button"
                            class="btn btn-success btn-sm"
                            id="btn-confirm-itemeable-items-v2">
                        Confirmar selección
                    </button>
                </div>

            </div>
        </div>
    </div>
    @include('puntoVenta._stockLookup')
@endsection

@section('plugins')
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/locales/bootstrap-datepicker.es.min.js') }}"></script>

@endsection

@section('scripts')
    <script src="{{asset('admin/plugins/typehead/typeahead.bundle.js')}}"></script>
    <script>
        $("input[data-bootstrap-switch]").each(function(){
            $(this).bootstrapSwitch();
        });
        $('#category_id').select2({
            placeholder: "Categorías",
            allowClear: true
        });
        $('#type_id').select2({
            placeholder: "Seleccione",
            allowClear: true
        });
        $('#type_caja').select2({
            placeholder: "Seleccione",
            allowClear: true
        });


    </script>
    <script>
        // flag: ¿pedir trabajador en el pago?
        window.PV_ASK_WORKER = @json($askWorker);
        window.APP_POS_V2 = window.APP_POS_V2 || {};
        window.APP_POS_V2.URLS = window.APP_POS_V2.URLS || {};

        window.APP_POS_V2.URLS.AVAILABLE_ITEMS =
            "{{ route('pos.items.available', ':stockItemId') }}";
        // lista simple de trabajadores para el popup
        window.PV_WORKERS = @json(
            $workers->map(function($w){
                return [
                    'id'   => $w->id,
                    'name' => $w->first_name.' '.$w->last_name,
                ];
            })
        );

        // aquí guardaremos el id elegido
        window.PV_SELECTED_WORKER_ID = null;
    </script>

    <script src="{{ asset('js/puntoVenta/catalog-stock.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/puntoVenta/checkout-context.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/puntoVenta/indexV2.js') }}?v={{ time() }}"></script>

@endsection
