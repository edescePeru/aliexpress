@extends('layouts.appAdmin2')

@section('openQuote')
    menu-open
@endsection

@section('activeQuote')
    active
@endsection

@section('activeListQuote')
    active
@endsection

@section('title')
    Cotizaciones
@endsection

@section('styles-plugins')

    <link rel="stylesheet" href="{{ asset('admin/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">

@endsection

@section('styles')
    <style>
        .select2-search__field{
            width: 100% !important;
        }

        .input-group .select2-container {
            flex: 1 1 auto;       /* que ocupe el espacio disponible */
            width: 1% !important; /* que no se expanda a 100% */
        }
        .input-group .select2-selection {
            height: 100% !important; /* que se ajuste a la altura del input-group */
        }
    </style>
@endsection

@section('page-header')
    <h1 class="page-title">Cotizaciones</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Modificar cotización</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('quoteSale.index') }}"><i class="fa fa-key"></i> Cotizaciones</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Editar</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">
    {{--<input type="hidden" id="materials" value="{{ json_encode($array) }}">--}}

    <div class="col-md-12">
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Importante!</strong> Código de colores en los productos. <br>
            El color gris indica que el material no ha sufrido modificaciones. <br>
            El color <strong style="color: blue;">AZUL</strong> indica que el producto ha sido actualizado el precio. <br>
            El color <strong style="color: red;">ROJO</strong> indica que no hay stock en el almacén. <br>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    </div>

    <form id="formEdit" class="form-horizontal" data-url="{{ route('quoteSale.update') }}" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-md-12">
                <div class="card card-success datos_generales">
                    <div class="card-header">
                        <h3 class="card-title">Datos generales</h3>
                        <input type="hidden" id="customer_quote_id" value="{{ $quote->customer_id }}">
                        <input type="hidden" id="contact_quote_id" value="{{ $quote->contact_id }}">
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group row">
                            <div class="col-md-12">
                                <label for="descriptionQuote">Descripción general de cotización </label>
                                <input type="text" id="descriptionQuote" onkeyup="mayus(this);" name="code_description" class="form-control form-control-sm" value="{{ $quote->description_quote }}" readonly>
                                <input type="hidden" id="quote_id" name="quote_id" value="{{ $quote->id }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="description">Código de cotización </label>
                                <input type="text" id="codeQuote" onkeyup="mayus(this);" name="code_quote" class="form-control form-control-sm" value="{{ $quote->code }}" readonly>
                            </div>

                            <div class="col-md-4" >
                                <label for="date_quote">Fecha de cotización </label>
                                <div class="input-daterange" >
                                    <input type="text" class="form-control form-control-sm date-range-filter" id="date_quote" name="date_quote" value="{{ date('d/m/Y', strtotime($quote->date_quote)) }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4" id="sandbox-container">
                                <label for="date_end">Válido hasta </label>
                                <div class="input-daterange" id="datepicker2">
                                    <input type="text" class="form-control form-control-sm date-range-filter" id="date_validate" name="date_validate" value="{{ date('d/m/Y', strtotime($quote->date_validate)) }}" readonly>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label for="paymentQuote">Forma de pago </label>
                                <input type="text" onkeyup="mayus(this);" name="way_to_pay" class="form-control form-control-sm" value="{{ ($quote->deadline !== null) ? $quote->deadline->description : 'No tiene forma de pago' }} " readonly>

                            </div>

                            <div class="col-md-4">
                                <label for="timeQuote">Tiempo de entrega <span class="right badge badge-danger">(*)</span></label>
                                <div class="input-group input-group-sm mb-3">
                                    <input type="number" id="timeQuote" step="1" min="0" name="delivery_time" class="form-control form-control-sm" value="{{ $quote->time_delivery }}" readonly>
                                    <div class="input-group-append">
                                        <span class="input-group-text" id="basic-addon2"> DIAS</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label for="customer_id">Cliente <span class="right badge badge-danger">(*)</span></label>
                                <input type="text"  onkeyup="mayus(this);" name="delivery_time" class="form-control form-control-sm" value="{{ ($quote->customer !== null) ? $quote->customer->business_name : 'No tiene cliente'}}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label for="contact_id">Contacto <span class="right badge badge-danger">(*)</span></label>
                                <input type="text"  onkeyup="mayus(this);" name="delivery_time" class="form-control form-control-sm" value="{{ ($quote->contact !== null) ? $quote->contact->name : 'No tiene contacto'}}" readonly>
                            </div>
                            <div class="col-md-8">
                                <label for="observations">Observaciones </label>
                                <div>{!! nl2br($quote->observations) !!}</div>
                            </div>
                        </div>

                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>

        <div class="row" id="element_loader">
            @foreach( $quote->equipments as $equipment )
            <div class="col-md-12">
                <div class="card card-success" data-equip>
                    <div class="card-header">
                        <h3 class="card-title">COTIZACIÓN</h3>

                        <div class="card-tools">

                            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group row">
                            <input type="hidden" data-utilityequipment="" value="{{ $equipment->utility }}">
                            <input type="hidden" data-rentequipment="" value="{{ $equipment->rent }}">
                            <input type="hidden" data-letterequipment="" value="{{ $equipment->letter }}">
                            <input type="hidden" name="" id="igv" value="{{ $igv }}">
                            <div class="col-md-12">
                                <label for="description">Detalles de cotización <span class="right badge badge-danger">(*)</span></label>
                                {!! nl2br($equipment->detail) !!}
                            </div>
                        </div>

                        <div class="card card-warning">
                            <div class="card-header">
                                <h3 class="card-title">PRODUCTOS</h3>

                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div data-bodyConsumable>
                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <strong>Descripción</strong>
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-group">
                                                <strong>Present.</strong>
                                            </div>
                                        </div>
                                        <div class="col-md-1">
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
                                                <strong>V/U</strong>
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-group">
                                                <strong>P/U</strong>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <strong>IMPORTE</strong>
                                            </div>
                                        </div>

                                    </div>
                                    @can('showPrices_quote')
                                        @foreach( $equipment->consumables as $consumable )
                                            <div class="row">
                                                {{-- Descripcion --}}
                                                <div class="col-md-5">
                                                    <div class="form-group">
                                                        <input type="text" onkeyup="mayus(this);" class="form-control form-control-sm" value="{{ $consumable->material->full_description }}" data-consumableDescription {{ ($consumable->material->enable_status == 0) ? 'style=color:purple':( ($consumable->material->stock_current == 0) ? 'style=color:red': ( ($consumable->material->state_update_price == 1) ? 'style=color:blue':'' ) ) }} readonly>
                                                        <input type="hidden" data-consumableId="{{ $consumable->material_id }}">
                                                        <input type="hidden" data-descuento="{{ $consumable->discount }}">
                                                        <input type="hidden" data-type_promotion="{{ $consumable->type_promo }}">
                                                    </div>
                                                </div>
                                                <div class="col-md-1">
                                                    <div class="form-group">
                                                        <div class="form-group">
                                                            <input type="text" onkeyup="mayus(this);" class="form-control form-control-sm" value="{{ ($consumable->material_presentation_id == null) ? 'Unidad': $consumable->units_per_pack.' Und' }}" data-consumableUnit readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                                {{-- Unidad --}}
                                                <div class="col-md-1">
                                                    <div class="form-group">
                                                        <div class="form-group">
                                                            <input type="text" onkeyup="mayus(this);" class="form-control form-control-sm" value="{{ $consumable->material->unitMeasure->description }}" data-consumableUnit readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                                {{-- Cantidad --}}
                                                <div class="col-md-1">
                                                    <div class="form-group">
                                                        <input type="number" class="form-control form-control-sm" placeholder="0.00" min="0" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" oninput="calculateTotalC(this);" data-consumableQuantity value="{{ ($consumable->material_presentation_id == null) ? $consumable->quantity: $consumable->packs }}" readonly>
                                                    </div>
                                                </div>
                                                {{-- Valor Unitario --}}
                                                <div class="col-md-1">
                                                    <div class="form-group">
                                                        <input type="number" class="form-control form-control-sm" placeholder="0.00" min="0" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" data-consumableValor onblur="
                                                    this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                                    " value="{{ round($consumable->valor_unitario,2) }}" readonly>
                                                    </div>
                                                </div>
                                                {{-- Precio Unitario --}}
                                                <div class="col-md-1">
                                                    <div class="form-group">
                                                        <input type="number" class="form-control form-control-sm" placeholder="0.00" min="0" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" data-consumablePrice onblur="
                                                    this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                                    " value="{{ round($consumable->price, 2) }}" readonly>
                                                    </div>
                                                </div>
                                                {{-- Importe --}}
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <input type="number" class="form-control form-control-sm" placeholder="0.00" min="0" data-consumableImporte step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                                    this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                                    " value="{{ round($consumable->total, 2) }}" readonly>
                                                    </div>
                                                </div>

                                            </div>
                                        @endforeach
                                    @else
                                        @foreach( $equipment->consumables as $consumable )
                                            <div class="row">
                                                {{-- Descripcion --}}
                                                <div class="col-md-5">
                                                    <div class="form-group">
                                                        <input type="text" onkeyup="mayus(this);" class="form-control form-control-sm" value="{{ $consumable->material->full_description }}" {{ ($consumable->material->enable_status == 0) ? 'style=color:purple':( ($consumable->material->stock_current == 0) ? 'style=color:red': ( ($consumable->material->state_update_price == 1) ? 'style=color:blue':'' ) ) }} data-consumableDescription readonly>
                                                        <input type="hidden" data-consumableId="{{ $consumable->material_id }}">
                                                        <input type="hidden" data-descuento="{{ $consumable->discount }}">
                                                        <input type="hidden" data-type_promotion="{{ $consumable->type_promo }}">
                                                    </div>
                                                </div>
                                                <div class="col-md-1">
                                                    <div class="form-group">
                                                        <div class="form-group">
                                                            <input type="text" onkeyup="mayus(this);" class="form-control form-control-sm" value="{{ ($consumable->material_presentation_id == null) ? 'Unidad': $consumable->units_per_pack.' Und' }}" data-consumableUnit readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                                {{-- Unidad --}}
                                                <div class="col-md-1">
                                                    <div class="form-group">
                                                        <div class="form-group">
                                                            <input type="text" onkeyup="mayus(this);" class="form-control form-control-sm" value="{{ $consumable->material->unitMeasure->description }}" data-consumableUnit readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                                {{-- Cantidad --}}
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <input type="number" class="form-control form-control-sm" placeholder="0.00" min="0" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" data-consumableQuantity oninput="calculateTotalC(this);" onblur="
                                                    this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                                    " value="{{ ($consumable->material_presentation_id == null) ? $consumable->quantity: $consumable->packs }}">
                                                    </div>
                                                </div>
                                                {{-- Valor Unitario --}}
                                                <div class="col-md-1">
                                                    <div class="form-group">
                                                        <input type="number" class="form-control form-control-sm" placeholder="0.00" min="0" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" data-consumablePrice2 onblur="
                                                    this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                                    " value="{{ round($consumable->valor_unitario, 2) }}" style="display: none" readonly>
                                                    </div>
                                                </div>
                                                {{-- Precio Unitario --}}
                                                <div class="col-md-1">
                                                    <div class="form-group">
                                                        <input type="number" class="form-control form-control-sm" placeholder="0.00" min="0" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" data-consumablePrice onblur="
                                                    this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                                    " value="{{ round($consumable->price, 2) }}" style="display: none" readonly>
                                                    </div>
                                                </div>
                                                {{-- Importe --}}
                                                <div class="col-md-1">
                                                    <div class="form-group">
                                                        <input type="number" class="form-control form-control-sm" placeholder="0.00" min="0" data-consumableTotal step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                                    this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                                    " value="{{ round($consumable->total, 2) }}" style="display: none" readonly>
                                                    </div>
                                                </div>

                                            </div>
                                        @endforeach
                                    @endcan

                                </div>
                            </div>
                        </div>

                        <div class="card card-cyan ">
                            <div class="card-header">
                                <h3 class="card-title">SERVICIOS ADICIONALES</h3>

                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <strong>Descripción</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-1">
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
                                            <strong>V/U</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <div class="form-group">
                                            <strong>P/U</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <strong>Importe</strong>
                                        </div>
                                    </div>

                                    <div class="col-md-1">
                                        <div class="form-group">
                                            <strong>Facturar</strong>
                                        </div>
                                    </div>

                                </div>
                                <div data-bodyService>

                                    @foreach($equipment->workforces as $workforce)
                                        @php
                                            $qty = (float) $workforce->quantity;
                                            $pu  = (float) $workforce->price; // P/U con IGV
                                            $vu  = $pu / (1 + ($igv/100));     // V/U sin IGV
                                            $imp = (float) $workforce->total;

                                            $qtyFmt = rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.');
                                        @endphp

                                        <div class="row" data-serviceRow>
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <input type="text" onkeyup="mayus(this);" class="form-control form-control-sm"
                                                           value="{{ $workforce->description }}" data-serviceDescription readonly>
                                                    <input type="hidden" data-serviceId value="{{ $workforce->id }}">
                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <div class="form-group">
                                                    <input type="text" class="form-control form-control-sm"
                                                           value="{{ $workforce->unit }}" data-serviceUnit readonly>
                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <div class="form-group">
                                                    <input type="number" class="form-control form-control-sm"
                                                           data-serviceQuantity min="0" step="0.01"
                                                           value="{{ $qtyFmt }}" readonly>
                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <div class="form-group">
                                                    <input type="number" class="form-control form-control-sm"
                                                           data-serviceVU value="{{ round($vu,2) }}" readonly
                                                           @cannot('showPrices_quote') style="display:none" @endcannot>
                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <div class="form-group">
                                                    <input type="number" class="form-control form-control-sm"
                                                           data-servicePU value="{{ round($pu,2) }}" min="0" step="0.01" readonly
                                                           @cannot('showPrices_quote') style="display:none" @endcannot>
                                                </div>
                                            </div>

                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <input type="number" class="form-control form-control-sm"
                                                           data-serviceImporte value="{{ round($imp,2) }}" readonly
                                                           @cannot('showPrices_quote') style="display:none" @endcannot>
                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <div class="form-group text-center">
                                                    <div class="icheck-primary d-inline">
                                                        <input type="checkbox"
                                                               id="billable_{{ $equipment->id }}_{{ $workforce->id }}"
                                                               data-serviceBillable
                                                                {{ ($workforce->billable ?? true) ? 'checked' : '' }} readonly>
                                                        <label for="billable_{{ $equipment->id }}_{{ $workforce->id }}"></label>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="card card-purple">
                            <div class="card-header">
                                <h3 class="card-title">DESCUENTO GLOBAL</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="card-body" id="discountSection"
                                 data-discount_type="{{ $quote->discount_type ?? 'amount' }}"
                                 data-discount_input_mode="{{ $quote->discount_input_mode ?? 'without_igv' }}"
                                 data-discount_value="{{ $quote->discount_input_value ?? 0 }}">

                                <div class="row">
                                    <!-- Tipo -->
                                    <div class="col-md-3">
                                        <label>Tipo</label>
                                        <div class="form-group clearfix">
                                            <div class="icheck-primary d-inline">
                                                <input type="radio" name="discount_type"
                                                       id="discount_type_amount"
                                                       value="amount"
                                                        {{ ($quote->discount_type ?? 'amount') === 'amount' ? 'checked' : '' }} readonly>
                                                <label for="discount_type_amount">Monto (S/)</label>
                                            </div>
                                            <div class="icheck-primary d-inline ml-3">
                                                <input type="radio" name="discount_type"
                                                       id="discount_type_percent"
                                                       value="percent"
                                                        {{ ($quote->discount_type ?? '') === 'percent' ? 'checked' : '' }} readonly>
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
                                               value="{{ $quote->discount_input_value ?? 0 }}" readonly>
                                    </div>

                                    <!-- Modo -->
                                    <div class="col-md-4">
                                        <label>Modo de ingreso</label>
                                        <div class="form-group clearfix">
                                            <div class="icheck-primary d-inline">
                                                <input type="radio" name="discount_input_mode"
                                                       id="discount_mode_without"
                                                       value="without_igv"
                                                        {{ ($quote->discount_input_mode ?? 'without_igv') === 'without_igv' ? 'checked' : '' }} readonly>
                                                <label for="discount_mode_without">SIN IGV (base)</label>
                                            </div>
                                            <div class="icheck-primary d-inline ml-3">
                                                <input type="radio" name="discount_input_mode"
                                                       id="discount_mode_with"
                                                       value="with_igv"
                                                        {{ ($quote->discount_input_mode ?? '') === 'with_igv' ? 'checked' : '' }} readonly>
                                                <label for="discount_mode_with">CON IGV (del total)</label>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                            </div>
                        </div>

                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            @endforeach
        </div>
        <div class="row" id="body-equipment">
        </div>

        @can('showPrices_quote')
            <div class="row">
                <!-- accepted payments column -->
                <div class="col-sm-7">

                </div>
                <!-- /.col -->
                <div class="col-sm-5">
                    <p class="lead">Resumen de Cotización</p>

                    <div class="table-responsive">
                        <table class="table">
                            <tr>
                                <th style="width:50%">DESCUENTO (-): </th>
                                <td>{{ ($currency == 'pen') ?'PEN' : 'USD' }} <span id="descuento" class="align-right">{{ round($quote->descuento, 2) }}</span></td>
                            </tr>
                            <tr>
                                <th style="width:50%">GRAVADA: </th>
                                <td>{{ ($currency == 'pen') ?'PEN' : 'USD' }} <span id="gravada" class="align-right">{{ round($quote->gravada, 2) }}</span></td>
                            </tr>
                            <tr>
                                <th style="width:50%">IGV {{ $igv }}%: </th>
                                <td>{{ ($currency == 'pen') ?'PEN' : 'USD' }} <span id="igv_total" class="align-right">{{ round($quote->igv_total, 2) }}</span></td>
                            </tr>
                            <tr>
                                <th style="width:50%">TOTAL: </th>
                                <td>{{ ($currency == 'pen') ?'PEN' : 'USD' }} <span id="total_importe" class="align-right">{{ round($quote->total_importe, 2) }}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <!-- /.col -->
            </div>
        @endcan

        <template id="template-consumable">
            <div class="row">
                {{-- Descripcion --}}
                <div class="col-md-5">
                    <div class="form-group">
                        <input type="text" onkeyup="mayus(this);" class="form-control form-control-sm" data-consumableDescription readonly>
                        <input type="hidden" data-consumableId>
                        <input type="hidden" data-descuento>
                        <input type="hidden" data-type_promotion>
                    </div>
                </div>
                {{-- Unidad --}}
                <div class="col-md-1">
                    <div class="form-group">
                        <div class="form-group">
                            <input type="text" onkeyup="mayus(this);" class="form-control form-control-sm" data-consumableUnit readonly>
                        </div>
                    </div>
                </div>
                {{-- Cantidad --}}
                <div class="col-md-1">
                    <div class="form-group">
                        <input type="number" class="form-control form-control-sm" placeholder="0.00" min="0" oninput="calculateTotalC(this);" data-consumableQuantity step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                            this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                            ">
                    </div>
                </div>
                {{-- Valor Unitario --}}
                <div class="col-md-1">
                    <div class="form-group">
                        <input type="number" class="form-control form-control-sm" placeholder="0.00" min="0" data-consumableValor step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                            this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                            " readonly>
                    </div>
                </div>
                {{-- Precio Unitario --}}
                <div class="col-md-1">
                    <div class="form-group">
                        <input type="number" class="form-control form-control-sm" placeholder="0.00" min="0" data-consumablePrice step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                            this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                            " readonly>
                    </div>
                </div>
                {{-- Importe --}}
                <div class="col-md-2">
                    <div class="form-group">
                        <input type="number" class="form-control form-control-sm" placeholder="0.00" min="0" data-consumableImporte step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                            this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                            " readonly>
                    </div>
                </div>
                <div class="col-md-1">
                    <button type="button" data-deleteConsumable class="btn btn-block btn-outline-danger btn-sm"><i class="fas fa-trash"></i> </button>
                </div>
            </div>
        </template>

        {{--<div class="row">
            <div class="col-12">
                <a href="{{ route('quoteSale.index') }}" class="btn btn-outline-secondary">Regresar</a>
                <button type="button" id="btn-submit" class="btn btn-outline-success float-right">Guardar nuevos equipos</button>
            </div>
        </div>--}}
        <!-- /.card-footer -->
    </form>

    <div id="modalChangePercentages" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Cambiar los porcentages de ganancia</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="quote_percentage" id="quote_percentage">
                    <input type="hidden" name="equipment_percentage" id="equipment_percentage">

                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <strong>Importante!</strong> Se recargará automaticamente la página para que hagan efecto los cambios.
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label class="col-sm-12 control-label" for="percentage_utility"> Utilidad </label>

                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control form-control-sm" name="percentage_utility" id="percentage_utility" placeholder="0.00" min="0" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                        this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                        ">
                                <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="col-sm-12 control-label" for="percentage_letter"> Letra </label>

                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control form-control-sm" name="percentage_letter" id="percentage_letter" placeholder="0.00" min="0" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                        this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                        ">
                                <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4" >
                            <label class="col-sm-12 control-label" for="percentage_rent"> Renta </label>

                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control form-control-sm" name="percentage_rent" id="percentage_rent" placeholder="0.00" min="0" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                        this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                        ">
                                <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cancelar</button>
                    <button type="button" id="btn-changePercentage" class="btn btn-outline-primary">Guardar porcentajes</button>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="promotionModal" tabindex="-1" role="dialog" aria-labelledby="promotionModalLabel" aria-hidden="true">
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
    <script src="{{ asset('admin/plugins/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
@endsection

@section('scripts')


    <script>

    </script>

    {{--<script src="{{ asset('js/quoteSale/edit.js') }}?v={{ time() }}"></script>--}}
@endsection
