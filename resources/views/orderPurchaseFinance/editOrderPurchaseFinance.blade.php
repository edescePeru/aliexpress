@extends('layouts.appAdmin2')

@section('openOrderPurchaseGeneral')
    menu-open
@endsection

@section('activeOrderPurchaseGeneral')
    active
@endsection

@section('activeCreateOrderPurchaseFinance')
    active
@endsection

@section('title')
    Orden de Compras Finanzas
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

@endsection



@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Compras · Finanzas</span><h1 class="page-title">Editar orden financiera</h1><p class="next-page-description">Documento financiero de la empresa activa.</p></div>
@endsection

@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Orden de compra financiera</strong><span>Datos, referencias, líneas e importes.</span></div><div class="next-toolbar-actions"><a class="btn btn-outline-secondary btn-sm" href="{{ route('order.purchase.finance.index') }}">Volver al listado</a></div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{route('order.purchase.finance.index')}}"><i class="fa fa-key"></i> Ordenes de Compra Finanzas</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Editar</li>
    </ol>
@endsection

@section('content')
    <form id="formCreate" class="form-horizontal next-form-section" data-url="{{ route('order.purchase.finance.update') }}" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="next-section-header mb-0"><div><span class="next-section-kicker">01</span><h2>Datos generales y referencias</h2></div></div>


                    </div>
                    <div class="card-body next-form-section">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <input type="hidden" name="order_id" value="{{ $order->id }}">
                                    <label for="service_order">Orden de Compra</label>
                                    <input type="text" id="service_order" name="service_order" class="form-control" value="{{ $order->code }}" readonly>
                                </div>
                                <div class="form-group " id="sandbox-container">
                                    <label for="date_order">Fecha de Orden</label>
                                    <div class="input-daterange" id="datepicker">
                                        <input type="text" class="form-control date-range-filter" id="date_order" name="date_order" value="{{ \Carbon\Carbon::parse($order->date_order)->format('d/m/Y') }}">
                                    </div>
                                </div>
                                <div class="form-group " id="sandbox-container">
                                    <label for="date_delivery">Fecha de Entrega</label>
                                    <div class="input-daterange" id="datepicker">
                                        <input type="text" class="form-control date-range-filter" id="date_delivery" name="date_delivery" value="{{ \Carbon\Carbon::parse($order->date_delivery)->format('d/m/Y')}}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="observation">Observación </label>
                                    <textarea name="observation" cols="30" class="form-control text-break" placeholder="Ingrese observación ....">{{ $order->observation }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label for="quote_supplier">Cotización de proveedor </label>
                                    <input type="text" id="quote_supplier" name="quote_supplier" value="{{ $order->quote_supplier }}" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier">Proveedor </label>
                                    <select id="supplier" name="supplier_id" class="form-control select2" style="width: 100%;">
                                        <option></option>
                                        @foreach( $suppliers as $supplier )
                                            <option value="{{ $supplier->id }}" {{ ($supplier->id === $order->supplier_id) ? 'selected':'' }}>{{ $supplier->business_name }}</option>
                                        @endforeach
                                    </select>@if($suppliers->isEmpty())<small class="form-text text-muted">No hay opciones disponibles en el contexto actual.</small>@endif
                                </div>
                                <div class="form-group">
                                    <label for="approved_by">Aprobado por: </label>
                                    @if($order->approved_by && !$users->contains('id', $order->approved_by))
<input type="hidden" id="approved_by" name="approved_by" value="{{ $order->approved_by }}">
<p class="form-control-plaintext mb-0">Aprobador histórico · ID {{ $order->approved_by }}</p><small class="form-text text-muted">Referencia conservada; no está entre las opciones disponibles.</small>
@else
<select id="approved_by" name="approved_by" class="form-control select2" style="width: 100%;">
                                        <option></option>
                                        @foreach( $users as $user )
                                            <option value="{{ $user->id }}" {{ ($user->id === $order->approved_by) ? 'selected':'' }}>{{ $user->name }}</option>
                                        @endforeach
                                    </select>
@endif
@if($users->isEmpty())<small class="form-text text-muted">No hay opciones disponibles en el contexto actual.</small>@endif
                                </div>
                                <div class="form-group">
                                    <label for="payment_deadline">Forma de pago </label>
                                    {{--<input type="text" id="service_condition" name="service_condition" class="form-control" value="{{ $order->payment_condition }}">--}}
                                    <select id="payment_deadline" name="payment_deadline_id" class="form-control select2" style="width: 100%;">
                                        <option></option>
                                        @foreach( $payment_deadlines as $payment_deadline )
                                            <option value="{{ $payment_deadline->id }}" {{ ($payment_deadline->id == $order->payment_deadline_id) ? 'selected':'' }}>{{ $payment_deadline->description }}</option>
                                        @endforeach
                                    </select>@if($payment_deadlines->isEmpty())<small class="form-text text-muted">No hay opciones disponibles en el contexto actual.</small>@endif
                                </div>
                                <div class="form-group">
                                    <label for="btn-currency"> Moneda</label> <br>
                                    <input id="btn-currency" {{ ($order->currency_order === 'PEN') ? 'checked':''}} type="checkbox" name="currency_order" data-bootstrap-switch data-off-color="primary" data-on-text="SOLES" data-off-text="DOLARES" data-on-color="success">
                                </div>
                                <div class="form-group">
                                    <label for="btn-regularize"> Regularización</label> <br>
                                    <input id="btn-regularize" {{ ($order->regularize === 'r') ? 'checked':''}} type="checkbox" name="regularize_order" data-bootstrap-switch data-off-color="primary" data-on-text="SI" data-off-text="NO" data-on-color="success">
                                </div>
                                <div class="form-group">
                                    <label for="quote_id">Cotización </label>
                                    @if($order->quote_id && !$quotesRaised->contains('id', $order->quote_id))
<input type="hidden" id="quote_id" name="quote_id" value="{{ $order->quote_id }}">
<p class="form-control-plaintext mb-0">Cotización histórico · ID {{ $order->quote_id }}</p><small class="form-text text-muted">Referencia conservada; no está entre las opciones disponibles.</small>
@else
<select id="quote_id" name="quote_id" class="form-control select2" style="width: 100%;">
                                        <option></option>
                                        @foreach( $quotesRaised as $quote )
                                            <option value="{{ $quote->id }}" {{ ($quote->id === $order->quote_id) ? 'selected':'' }}>{{ $quote->code . ' ' . $quote->description_quote }}</option>
                                        @endforeach
                                    </select>
@endif
@if($quotesRaised->isEmpty())<small class="form-text text-muted">No hay opciones disponibles en el contexto actual.</small>@endif
                                </div>
                            </div>
                        </div>

                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="next-section-header mb-0"><div><span class="next-section-kicker">02</span><h2>Líneas de compra</h2></div></div>


                    </div>
                    <div class="card-body next-form-section" id="element_loader">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="service">Ingresar concepto <span class="right badge badge-danger">(*)</span></label>
                                    <input type="text" id="service" {{--onkeyup="mayus(this);"--}} class="form-control">

                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="unit">Unidad <span class="right badge badge-danger">(*)</span></label>
                                    <select id="unit" name="unit" class="form-control select2" style="width: 100%;">
                                        <option></option>
                                        @foreach( $unitMeasures as $unitMeasure )
                                            <option value="{{ $unitMeasure->id }}">{{ $unitMeasure->description }}</option>
                                        @endforeach
                                    </select>@if($unitMeasures->isEmpty())<small class="form-text text-muted">No hay opciones disponibles en el contexto actual.</small>@endif
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="quantity">Cantidad <span class="right badge badge-danger">(*)</span></label>
                                    <input type="number" id="quantity" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="price">Precio <span class="right badge badge-danger">(*)</span></label>
                                    <input type="number" id="price" class="form-control" placeholder="0.00" min="0" value="" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                    this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                    ">
                                </div>
                            </div>
                            <div class="col-md-1">
                                <label for="btn-add"> &nbsp; </label>
                                <button type="button" id="btn-add" class="btn btn-block btn-outline-primary"><i class="fas fa-arrow-circle-right"></i></button>
                            </div>

                        </div>

                        <hr>

                        <div class="row d-none d-md-flex">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <strong>Servicio</strong>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <strong>Unidad</strong>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <strong>Cantidad</strong>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <strong>Precio C/Igv</strong>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <strong>Precio S/Igv</strong>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <strong>Total C/Igv</strong>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <strong>Acción</strong>
                                </div>
                            </div>
                        </div>
                        <p id="finance-lines-empty" class="text-muted" role="status">No hay líneas registradas.</p>
                        <div id="body-services">

                            @foreach( $details as $detail )
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <div class="form-group d-flex flex-column">
                                            <input type="text" class="form-control form-control-sm" data-service value="{{ $detail->material }}" readonly><span class="d-md-none order-first small text-muted mb-1">Concepto</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <div class="form-group d-flex flex-column">
                                            <input type="text" onkeyup="mayus(this);" class="form-control form-control-sm" data-unit value="{{ $detail->unit }}" readonly><span class="d-md-none order-first small text-muted mb-1">Unidad</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="form-group d-flex flex-column">
                                        <input type="number" class="form-control form-control-sm" oninput="calculateTotal(this);" placeholder="0.00" min="0" value="{{ $detail->quantity }}" data-quantity="{{$detail->id}}" step="0.01" ><span class="d-md-none order-first small text-muted mb-1">Cantidad</span>
                                    </div>
                                </div>

                                <div class="col-md-1">
                                    <div class="form-group d-flex flex-column">
                                        <input type="number" class="form-control form-control-sm" oninput="calculateTotal2(this);" placeholder="0.00" min="0" data-price="{{$detail->id}}" value="{{ $detail->price }}" step="0.01" pattern="^\d+(?:\.\d{1,2})?$"><span class="d-md-none order-first small text-muted mb-1">Precio C/IGV</span>
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="form-group d-flex flex-column">
                                        <input type="number" class="form-control form-control-sm" oninput="calculateTotal3(this);" placeholder="0.00" min="0" data-price2="{{$detail->id}}" value="{{ round($detail->price/1.18,2) }}" step="0.01" pattern="^\d+(?:\.\d{1,2})?$"><span class="d-md-none order-first small text-muted mb-1">Precio S/IGV</span>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group d-flex flex-column">
                                        <input type="number" class="form-control form-control-sm" placeholder="0.00" min="0" data-total="{{$detail->id}}" step="0.01" value="{{ number_format($detail->total_detail,2, '.', '') }}" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                            this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red' " ><span class="d-md-none order-first small text-muted mb-1">Total C/IGV</span>
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="btn-group">
                                        <button type="button" data-edit="{{ $detail->id }}" class="btn btn-outline-success btn-sm"><i class="fas fa-save"></i> </button> &nbsp;
                                        <button type="button" data-delete="{{ $detail->id }}" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i> </button>
                                    </div>

                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>
        <!-- /.card-footer -->
        <div class="row">
            <!-- accepted payments column -->
            <div class="col-12 col-lg-6">

            </div>
            <!-- /.col -->
            <div class="col-12 col-lg-6">
                <div class="next-section-header"><div><span class="next-section-kicker">03</span><h2>Importes</h2></div></div>

                <div class="table-responsive">
                    <table class="table">
                        <tr>
                            <th style="width:50%">Subtotal: </th>
                            <td class="input-group"><span class="moneda">{{ $order->currency_order }}</span> <input type="number" min="0" step="0.01" id="subtotal" data-subtotal class="form-control form-control-sm" value="{{ $order->total - $order->igv }}"> </td>
                        </tr>
                        <tr>
                            <th>Igv: </th>
                            <td class="input-group"><span class="moneda">{{ $order->currency_order }}</span> <input type="number" min="0" step="0.01" id="taxes" data-taxes class="form-control form-control-sm" value="{{ $order->igv }}"></td>
                        </tr>
                        <tr>
                            <th>Total: </th>
                            <td class="input-group"><span class="moneda">{{ $order->currency_order }}</span> <input type="number" min="0" step="0.01" id="total" data-totalfinal class="form-control form-control-sm" value="{{ $order->total }}"> </td>
                        </tr>
                    </table>
                </div>
            </div>
            <!-- /.col -->
        </div>
        <div class="row">
            <div class="col-12 next-toolbar-actions flex-wrap">
                <a class="btn btn-outline-secondary" href="{{ route('order.purchase.finance.index') }}">Regresar</a>
                <button type="button" id="btn-submit" class="btn btn-primary">Guardar datos generales y nuevos detalles</button>
            </div>
        </div>
    </form>

    <template id="service-selected">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <div class="form-group d-flex flex-column">
                        <input type="text" class="form-control form-control-sm" data-service readonly><span class="d-md-none order-first small text-muted mb-1">Concepto</span>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <div class="form-group d-flex flex-column">
                        <input type="text" class="form-control form-control-sm" data-unit readonly><span class="d-md-none order-first small text-muted mb-1">Unidad</span>
                    </div>
                </div>
            </div>

            <div class="col-md-1">
                <div class="form-group d-flex flex-column">
                    <input type="number" class="form-control form-control-sm" oninput="calculateTotal(this);" placeholder="0.00" min="0" data-quantity step="0.01" ><span class="d-md-none order-first small text-muted mb-1">Cantidad</span>
                </div>
            </div>
            <div class="col-md-1">
                <div class="form-group d-flex flex-column">
                    <input type="number" class="form-control form-control-sm" oninput="calculateTotal2(this);" placeholder="0.00" min="0" data-price step="0.01" pattern="^\d+(?:\.\d{1,2})?$"><span class="d-md-none order-first small text-muted mb-1">Precio C/IGV</span>
                </div>
            </div>
            <div class="col-md-1">
                <div class="form-group d-flex flex-column">
                    <input type="number" class="form-control form-control-sm" oninput="calculateTotal3(this);" placeholder="0.00" min="0" data-price2 step="0.01" pattern="^\d+(?:\.\d{1,2})?$"><span class="d-md-none order-first small text-muted mb-1">Precio S/IGV</span>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group d-flex flex-column">
                    <input type="number" class="form-control form-control-sm" placeholder="0.00" min="0" data-total step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                            this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                            " readonly><span class="d-md-none order-first small text-muted mb-1">Total C/IGV</span>
                </div>
            </div>
            <div class="col-md-1">
                <button type="button" data-delete class="btn btn-block btn-outline-danger btn-sm"><i class="fas fa-trash"></i> </button>
            </div>
        </div>
    </template>

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
document.addEventListener('DOMContentLoaded', function () {
    const lines = document.getElementById('body-services');
    const empty = document.getElementById('finance-lines-empty');
    const refresh = function () { empty.classList.toggle('d-none', lines.children.length > 0); };
    refresh();
    new MutationObserver(refresh).observe(lines, {childList:true});
});
</script>
    <script src="{{asset('admin/plugins/jquery_loading/loadingoverlay.min.js')}}"></script>

    <script>
        $(function () {
            //Initialize Select2 Elements
            /*$('#date_order').attr("value", moment().format('DD/MM/YYYY'));
            $('#date_arrival').attr("value", moment().format('DD/MM/YYYY'));
*/
            $('#sandbox-container .input-daterange').datepicker({
                todayBtn: "linked",
                clearBtn: true,
                language: "es",
                multidate: false,
                autoclose: true,
                todayHighlight: true,
                defaultViewDate: moment().format('L')
            });

            $("input[data-bootstrap-switch]").each(function(){
                $(this).bootstrapSwitch();
            });
            $('#supplier').select2({
                placeholder: "Seleccione un proveedor",
            });
            $('#approved_by').filter('select').select2({
                placeholder: "Seleccione un usuario",
            });
            $('#customer_id').select2({
                placeholder: "Selecione cliente",
            });
            $('#payment_deadline').select2({
                placeholder: "Selecione plazo",
            });

            $('#unit').select2({
                placeholder: "Seleccione ...",
            });
            $('#quote_id').filter('select').select2({
                placeholder: "Selecione trabajo",
                allowClear: true
            });

        })
    </script>

    <script src="{{ asset('js/orderPurchaseFinance/editOrderFinance.js') }}"></script>
@endsection
