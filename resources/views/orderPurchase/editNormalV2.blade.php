@extends('layouts.appAdmin2')

@section('openOrderPurchaseGeneral')
    menu-open
@endsection

@section('activeOrderPurchaseGeneral')
    active
@endsection

@section('activeListOrderPurchaseNormal')
    active
@endsection

@section('title')
    Orden de compra
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
<div class="next-page-heading"><span class="next-page-eyebrow">Compras · operaciones</span><h1 class="page-title">Editar compra normal</h1><p class="next-page-description">Datos y materiales de la empresa activa.</p></div>
@endsection

@section('page-title')
    <h5 class="card-title">Orden de compra</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{route('order.purchase.normal.indexV2')}}"><i class="fa fa-key"></i> Ordenes de compra</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Editar</li>
    </ol>
@endsection

@section('content')
    <form id="formCreate" class="form-horizontal next-form-section" data-url="{{ route('order.purchase.normal.update') }}" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Datos generales</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body next-form-section">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <input type="hidden" name="order_id" value="{{ $order->id }}">
                                    <label for="purchase_order">Orden de Compra</label>
                                    <input type="text" id="purchase_order" name="purchase_order" class="form-control" value="{{ $order->code }}" readonly>
                                </div>
                                <div class="form-group " id="sandbox-container">
                                    <label for="date_order">Fecha de Orden</label>
                                    <div class="input-daterange" id="datepicker">
                                        <input type="text" class="form-control date-range-filter" id="date_order" name="date_order" value="{{ \Carbon\Carbon::parse($order->date_order)->format('d/m/Y') }}">
                                    </div>
                                </div>
                                <div class="form-group " id="sandbox-container">
                                    <label for="date_arrival">Fecha de Llegada</label>
                                    <div class="input-daterange" id="datepicker">
                                        <input type="text" class="form-control date-range-filter" id="date_arrival" name="date_arrival" value="{{ \Carbon\Carbon::parse($order->date_arrival)->format('d/m/Y')}}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="observation">Observación </label>
                                    <textarea name="observation" cols="30" class="form-control" style="word-break: break-all;" placeholder="Ingrese observación ....">{{ $order->observation }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label for="quote_supplier">Cotización de proveedeor </label>
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
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="purchase-created-user">Creado por</label>
                                    <input id="purchase-created-user" class="form-control" value="{{ optional($order->createdUser)->name ?? 'No registrado / usuario no disponible' }}" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="purchase-updated-user">Última modificación por</label>
                                    <input id="purchase-updated-user" class="form-control" value="{{ optional($order->updatedUser)->name ?? 'No registrado / usuario no disponible' }}" readonly>
                                </div>
                                @if($order->approved_by)
                                    <div class="form-group">
                                        <label>Aprobador consignado anteriormente</label>
                                        <input class="form-control" value="{{ optional($order->approved_user)->name ?? 'Usuario no disponible' }}" readonly>
                                    </div>
                                @endif
                                <div class="form-group">
                                    <label for="payment_deadline">Forma de pago </label>
                                    {{--<input type="text" id="purchase_condition" name="purchase_condition" class="form-control" value="{{ $order->payment_condition }}">--}}
                                    <select id="payment_deadline" name="payment_deadline_id" class="form-control select2" style="width: 100%;">
                                        <option></option>
                                        @foreach( $payment_deadlines as $payment_deadline )
                                            <option value="{{ $payment_deadline->id }}" {{ ($payment_deadline->id == $order->payment_deadline_id) ? 'selected':'' }}>{{ $payment_deadline->description }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="btn-currency"> Moneda <span class="next-required">*</span></label> <br>
                                    <input id="btn-currency" {{ ($order->currency_order === 'PEN') ? 'checked':''}} type="checkbox" name="currency_order" data-bootstrap-switch data-off-color="primary" data-on-text="SOLES" data-off-text="DOLARES" data-on-color="success" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="btn-regularize"> Regularización <span class="next-required">*</span></label> <br>
                                    <input id="btn-regularize" {{ ($order->regularize === 'r') ? 'checked':''}} type="checkbox" name="regularize_order" data-bootstrap-switch data-off-color="primary" data-on-text="SI" data-off-text="NO" data-on-color="success" readonly>
                                </div>
                                {{--<div class="form-group">
                                    <label for="quote_id">Cotización </label>
                                    <select id="quote_id" name="quote_id" class="form-control select2" style="width: 100%;">
                                        <option></option>
                                        @foreach( $quotesRaised as $quote )
                                            <option value="{{ $quote->id }}" {{ ($quote->id === $order->quote_id) ? 'selected':'' }}>{{ $quote->code . ' ' . $quote->description_quote }}</option>
                                        @endforeach
                                    </select>
                                </div>--}}
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
                        <h3 class="card-title">Detalles de compra</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body next-form-section" id="element_loader">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="material_search">Buscar material <span class="next-required">*</span></label>
                                    <select id="material_search" class="form-control select2" style="width: 100%;" data-url="{{ route('materials.order.purchase.search') }}">>
                                        <option></option>
                                    </select>
                                    <input type="hidden"
                                           id="url_stock_items_entry_base"
                                           value="{{ url('/dashboard/materials') }}">
                                    <input type="hidden" id="material_id" name="material_id">
                                    <input type="hidden" id="stock_item_id" name="stock_item_id">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="quantity">Cantidad <span class="next-required">*</span></label>
                                    <input type="number" id="quantity" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label for="btn-add"> &nbsp; </label>
                                <button type="button" id="btn-add" class="btn btn-block btn-outline-primary">Agregar <i class="fas fa-arrow-circle-right"></i></button>
                            </div>

                        </div>

                        <hr>

                        <div class="table-responsive" tabindex="0" aria-label="Detalles de compra; desplazamiento horizontal local"><table class="table table-bordered table-hover table-sm next-data-table"><thead><tr><th class="text-center">ID</th><th class="text-center">Código</th><th class="text-left">Material</th><th class="text-center">Cantidad</th><th class="text-right">Precio C/IGV</th><th class="text-right">Precio S/IGV</th><th class="text-right">Total C/IGV</th><th class="text-center">Acción</th></tr></thead><tbody id="body-materials">
                            @foreach( $details as $detail )
                            <tr class="material-row">
                                <td class="text-center">
                                    <div class="form-group">
                                        <input type="text" class="form-control form-control-sm" data-id value="{{ $detail->stock_item_id }}" readonly>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="form-group">
                                        <input type="text" class="form-control form-control-sm" data-code value="{{ optional($detail->stockItem)->sku }}" readonly>
                                    </div>
                                </td>
                                <td class="text-left">
                                    <div class="form-group">
                                        <input type="text" class="form-control form-control-sm" data-description value="{{ optional($detail->stockItem)->display_name ?? $detail->material->full_description }}" readonly>
                                    </div>
                                </td>

                                <td class="text-center">
                                    <div class="form-group">
                                        <input type="number" class="form-control form-control-sm" value="{{ $detail->quantity }}" data-quantity data-detail-id="{{ $detail->id }}" step="1">
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="form-group">
                                        <input type="number" class="form-control form-control-sm" value="{{ $detail->price }}" data-price data-detail-id="{{ $detail->id }}" step="0.01">
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="form-group">
                                        <input type="number" class="form-control form-control-sm" value="{{ round((float)($detail->price)/1.18, 2) }}" data-price2 data-detail-id="{{ $detail->id }}" step="0.01">
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="form-group">
                                        <input type="number" class="form-control form-control-sm" value="{{ ($detail->total_detail != null) ? $detail->total_detail : $detail->quantity*$detail->price }}" data-total data-detail-id="{{ $detail->id }}" step="0.01">
                                    </div>
                                </td>

                                <td class="text-center">
                                    <div class="btn-group">
                                        <button
                                                type="button"
                                                aria-label="Guardar detalle" title="Guardar detalle" data-edit="{{ $detail->id }}"
                                                class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-save next-row-action-item-icon"></i>
                                        </button> &nbsp;
                                        <button
                                                type="button"
                                                aria-label="Eliminar material" title="Eliminar material" data-delete="{{ $detail->id }}"
                                                data-stock-item="{{ $detail->stock_item_id }}"
                                                data-material="{{ $detail->material_id }}"
                                                class="btn btn-outline-danger btn-sm">
                                            <i class="fas fa-trash next-row-action-item-icon"></i>
                                        </button>
                                    </div>

                                </td>
                            </tr>
                            @endforeach
                        </tbody></table></div>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>
        <!-- /.card-footer -->
        <div class="row">
            <!-- accepted payments column -->
            <div class="col-12 col-md-6">

            </div>
            <!-- /.col -->
            <div class="col-12 col-md-6">
                <p class="lead">Resumen de factura</p>

                <div class="table-responsive">
                    <table class="table">
                        <tr>
                            <th style="width:50%">Subtotal: </th>
                            <td class="input-group"><span class="moneda">{{ $order->currency_order }}</span> <input type="number" min="0" step="0.01" id="subtotal" data-subtotal class="form-control form-control-sm" value="{{ $order->total - $order->igv }}"> </td>
                        </tr>
                        <tr>
                            <th>Igv: </th>
                            <td class="input-group"><span class="moneda">{{ $order->currency_order }}</span> <input type="number" min="0" step="0.01" id="taxes" data-taxes class="form-control form-control-sm" value="{{ $order->igv }}"> </td>
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
            <div class="col-12 next-toolbar-actions flex-wrap justify-content-between">
                <a class="btn btn-outline-secondary" href="{{ route('order.purchase.normal.indexV2') }}">Regresar</a>
                <button type="button" id="btn-submit" class="btn btn-primary">Guardar cambios y totales</button>
            </div>
        </div>
    </form>

    <template id="materials-selected">
        <tr class="material-row">
            <td class="text-center">
                <div class="form-group">
                    <input type="text" class="form-control form-control-sm" data-id readonly>
                </div>
            </td>

            <td class="text-center">
                <div class="form-group">
                    <input type="text" class="form-control form-control-sm" data-code readonly>
                </div>
            </td>

            <td class="text-left">
                <div class="form-group">
                    <input type="text" class="form-control form-control-sm" data-description readonly>
                </div>
            </td>

            <td class="text-center">
                <div class="form-group">
                    <input type="number" class="form-control form-control-sm" placeholder="0.00" min="0" data-quantity step="1">
                </div>
            </td>

            <td class="text-right">
                <div class="form-group">
                    <input type="number" class="form-control form-control-sm" placeholder="0.00" min="0" data-price step="0.01">
                </div>
            </td>

            <td class="text-right">
                <div class="form-group">
                    <input type="number" class="form-control form-control-sm" placeholder="0.00" min="0" data-price2 step="0.01">
                </div>
            </td>

            <td class="text-right">
                <div class="form-group">
                    <input type="number" class="form-control form-control-sm" placeholder="0.00" min="0" data-total step="0.01">
                </div>
            </td>

            <td class="text-center">
                <button type="button" data-delete=""
                        data-stock-item="" class="btn btn-block btn-outline-danger btn-sm" aria-label="Eliminar material" title="Eliminar material">
                    <i class="fas fa-trash next-row-action-item-icon"></i>
                </button>
            </td>
        </tr>
    </template>

    <div class="modal fade next-aux-modal" id="modalStockItems" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow">

                <div class="modal-body p-3">

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="mb-0 font-weight-bold" id="modalStockItemsTitle">
                                Variantes
                            </h6>
                            <small class="text-muted">
                                Ingrese cantidad y precio costo solo en las variantes que desea agregar.
                            </small>
                        </div>

                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-2 next-data-table" id="tableStockItemsVariant">
                            <thead class="thead-light">
                            <tr>
                                <th style="width: 28%;">Variante</th>
                                <th style="width: 22%;">SKU</th>
                                <th style="width: 16%;">Código de barras</th>
                                <th style="width: 17%;">Cantidad</th>
                                <th style="width: 17%;">Precio costo IGV</th>
                            </tr>
                            </thead>
                            <tbody id="tbodyStockItemsVariant">
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-outline-secondary mr-2" data-dismiss="modal">
                            Cancelar
                        </button>

                        <button type="button" class="btn btn-primary" id="btnSaveStockItemsVariant">
                            <i class="fas fa-save next-row-action-item-icon"></i> Guardar
                        </button>
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
            $('#approved_by').select2({
                placeholder: "Seleccione un usuario",
            });
            $('#customer_id').select2({
                placeholder: "Selecione cliente",
            });
            $('#payment_deadline').select2({
                placeholder: "Selecione plazo",
            });

            $('.unitMeasure').select2({
                placeholder: "Seleccione unidad",
            });

            $('#quote_id').select2({
                placeholder: "Selecione trabajo",
                allowClear: true
            });
        })
    </script>

    <script>window.normalPurchaseContext = {tenant_id: {{ \App\Support\TenantContext::tenantId() }}, company_id: {{ \App\Support\TenantContext::companyId() }}};</script>
    <script src="{{ asset('js/orderPurchase/companyContext.js') }}"></script>
    <script src="{{ asset('js/orderPurchase/editNormalV2.js') }}?v={{ time() }}"></script>
@endsection
