@extends('layouts.appAdmin2')

@section('openMaterial')
    menu-open
@endsection

@section('activeMaterial')
    active
@endsection

@section('activeListMaterial')
    active
@endsection

@section('title')
    Materiales
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <!-- Dropzone CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css">

@endsection

@section('styles')
    <style>
        .select2-search__field{
            width: 100% !important;
        }
    </style>
@endsection

@section('page-header')
    <h1 class="page-title">Materiales</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Modificar material {{ $material->code }}</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('material.indexV2') }}"><i class="fa fa-archive"></i> Materiales</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Editar</li>
    </ol>
@endsection

@section('content')
    <form id="formEdit" class="form-horizontal" data-url="{{ route('material.update') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="material_id" value="{{ $material->id }}">

        @if(in_array('category', $enabled, true))
            <input type="hidden" id="category_id" value="{{ $material->category_id }}">
        @endif

        @if(in_array('subcategory', $enabled, true))
            <input type="hidden" id="subcategory_id" value="{{ $material->subcategory_id }}">
        @endif

        @if(in_array('exampler', $enabled, true))
            <input type="hidden" id="exampler_id" value="{{ $material->exampler_id }}">
        @endif

        <input type="hidden" id="type_id" value="{{$material->material_type_id}}">
        <input type="hidden" id="subtype_id" value="{{$material->subtype_id}}">

        <div class="row">
            <div class="col-md-12">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Datos generales</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group row">
                            <div class="col-md-12">
                                <label for="description">Descripción <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" id="description" name="description" class="form-control" value="{{ $material->description }}">
                            </div>
                        </div>

                        <div class="form-group row">

                            @if(in_array('brand', $enabled, true))
                                <div class="col-md-3">
                                    <label for="brand">Marca </label>
                                    <div class="input-group">
                                        <select id="brand" name="brand" class="form-control select2" style="width: 83%;">
                                            <option></option>
                                            @foreach( $brands as $brand )
                                                <option value="{{ $brand->id }}" {{ ($brand->id === $material->brand_id) ? 'selected':'' }}>{{ $brand->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalBrand">
                                                +
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if(in_array('exampler', $enabled, true))
                                <div class="col-md-3">
                                    <label for="exampler">Modelo </label>
                                    <div class="input-group">
                                        <select id="exampler" name="exampler" class="form-control select2" style="width: 83%;">

                                        </select>
                                        <div class="input-group-append">
                                            <button type="button" id="btn-newExampler" class="btn btn-primary" data-toggle="modal" data-target="#modalExampler" style="display: none;">
                                                +
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if(in_array('category', $enabled, true))
                                <div class="col-md-3">
                                    <label for="category">Categorías <span class="right badge badge-danger">(*)</span></label>
                                    <div class="input-group">
                                        <select id="category" name="category" class="form-control select2" style="width: 83%;">
                                            <option></option>
                                            @foreach( $categories as $category )
                                                <option value="{{ $category->id }}" {{ ($category->id === $material->category_id) ? 'selected': ''}}>{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalCategoria">
                                                +
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            @endif

                            @if(in_array('subcategory', $enabled, true))
                                <div class="col-md-3">
                                    <label for="subcategory">Subcategorías <span class="right badge badge-danger">(*)</span></label>
                                    <div class="input-group">
                                        <select id="subcategory" name="subcategory" class="form-control select2" style="width: 83%;">
                                            <option></option>

                                        </select>
                                        <div class="input-group-append">
                                            <button type="button" id="btn-newSubCategoria" class="btn btn-primary" data-toggle="modal" data-target="#modalSubCategoria" style="display: none;">
                                                +
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            @endif

                        </div>

                        <div class="form-group row">
                            @if(in_array('genero', $enabled, true))
                            <div class="col-md-3">
                                <label for="genero">Genero </label>
                                <div class="input-group">
                                    <select id="genero" name="genero" class="form-control select2" style="width: 83%;">
                                        <option></option>
                                        @foreach( $generos as $genero )
                                            <option value="{{ $genero->id }}" {{ ($genero->id === $material->warrant_id) ? 'selected':'' }}>{{ $genero->description }}</option>
                                        @endforeach
                                    </select>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalGenero">
                                            +
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @endif

                            @if(in_array('unit_measure', $enabled, true))
                                <div class="col-md-3">
                                    <label for="unit_measure">Unidad de medida <span class="right badge badge-danger">(*)</span></label>

                                    <div class="input-group">
                                        <select id="unit_measure" name="unit_measure" class="form-control select2" style="width: 83%;">
                                            <option></option>
                                            @foreach($unitMeasures as $unitMeasure)
                                                <option value="{{ $unitMeasure->id }}" {{ ($unitMeasure->id === $material->unit_measure_id) ? 'selected': ''}}>{{ $unitMeasure->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalUnitMeasure">
                                                +
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            @endif

                            <div class="col-md-3">
                                <label for="tipo_venta">Tipo de Venta </label>
                                <select id="tipo_venta" name="tipo_venta" class="form-control select2" style="width: 100%;">
                                    <option></option>
                                    @foreach( $tipoVentas as $tipo )
                                        <option value="{{$tipo->id}}" {{ ($tipo->id === $material->tipo_venta_id) ? 'selected': ''}}>{{ $tipo->description }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @if(in_array('perecible', $enabled, true))
                            <div class="col-md-3">
                                <label for="perecible">Perecible </label>
                                <select id="perecible" name="perecible" class="form-control select2" style="width: 100%;">
                                    <option></option>
                                    <option value="s" {{ ($material->perecible == "s") ? 'selected':'' }}>SI</option>
                                    <option value="n" {{ ($material->perecible == "n") ? 'selected':'' }}>NO</option>
                                </select>
                            </div>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="name">Nombre completo</label>
                            <div class="input-group mb-3">
                                <input type="text" class="form-control rounded-0" id="name" onkeyup="mayus(this);" name="name" value="{{ $material->full_name }}" readonly>
                                <span class="input-group-append">
                                    <button type="button" class="btn btn-info btn-flat" id="btn-generate"> <i class="fa fa-redo"></i> Actualizar</button>
                                </span>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-8">
                                <label for="image">Imagen del material</label>
                                <div class="dropzone" id="image-dropzone"></div>
                                <img src="{{ asset('images/material/'.$material->image) }}" width="100px" height="100px" alt="{{ $material->description }}">
                            </div>

                            <div class="col-md-4">
                                <label>Variantes</label>
                                <div class="form-group">

                                    <div class="icheck-primary">
                                        <input type="radio"
                                               id="sin_variantes"
                                               name="variantes"
                                               value="0"
                                               {{ !$tieneVariantes ? 'checked' : '' }}
                                               disabled>
                                        <label for="sin_variantes">Sin Variantes</label>
                                    </div>

                                    <div class="icheck-primary">
                                        <input type="radio"
                                               id="con_variantes"
                                               name="variantes"
                                               value="1"
                                               {{ $tieneVariantes ? 'checked' : '' }}
                                               disabled>
                                        <label for="con_variantes">Con Variantes</label>
                                    </div>

                                    {{--<div class="icheck-danger">
                                        <input type="checkbox"
                                               id="afecto_inventario"
                                               name="afecto_inventario"
                                                {{ optional($material->stockItems->first())->tracks_inventory ? 'checked' : '' }}>
                                        <label for="afecto_inventario">¿Afecto a inventario?</label>
                                    </div>--}}

                                </div>
                            </div>
                        </div>

                        {{--SECCION SIN VARIANES--}}
                        @if ( !$tieneVariantes )
                        <div id="seccion_sin_variantes">

                            <div class="col-md-12">
                                <div class="card card-warning">
                                    <div class="card-header">
                                        <h3 class="card-title">Datos operativos</h3>

                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                                <i class="fas fa-minus"></i></button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group row">
                                            <input type="hidden" name="stock_item_id" id="stock_item_id">
                                            <div class="col-md-4">
                                                <label for="display_name">Nombre completo</label>
                                                <input type="text" id="display_name" name="display_name" class="form-control" readonly>
                                            </div>

                                            <div class="col-md-2">
                                                <label for="sku_sin_variantes">SKU</label>
                                                <input type="text" id="sku_sin_variantes" name="sku_sin_variantes" class="form-control">
                                            </div>

                                            <div class="col-md-2">
                                                <label for="codigo_sin_variantes">Código de barras </label>
                                                <div class="input-group mb-3">
                                                    <input type="text" class="form-control rounded-0" id="codigo_sin_variantes" name="codigo_sin_variantes">
                                                    <span class="input-group-append">
                                                        <button type="button" class="btn btn-info btn-flat btn-generateCode" id="btn-generateCodeSinVariantes"> <i class="fas fa-random"></i></button>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="col-md-2">
                                                <label for="inputPack">Cantidad por paquete </label>
                                                <div class="form-group clearfix">
                                                    <div class="icheck-primary d-inline">
                                                        <input type="checkbox" name="pack" id="checkboxPack" {{ ($material->isPack == 1) ? 'checked':'' }}>
                                                        <label for="checkboxPack">Es paquete</label>
                                                        <input type="number" class="form-control form-control-sm d-inline ml-2" style="width: 50px;" id="inputPack" name="inputPack" value="{{ $material->quantityPack  }}" min="0" {{ ($material->isPack == 0) ? 'disabled':'' }}>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <label for="afecto_inventario_sin_variantes">Inventario </label><br>
                                                <input type="checkbox"
                                                       name="afecto_inventario_sin_variantes"
                                                       id="afecto_inventario_sin_variantes"
                                                       data-tracks_inventory_sin_variantes
                                                       data-bootstrap-switch
                                                       data-size="normal"
                                                       data-off-color="danger"
                                                       data-on-text="SI"
                                                       data-off-text="NO"
                                                       data-on-color="success"
                                                       checked>
                                            </div>
                                            <div class="col-md-1">
                                                <label for="is_active_sin_variante">Activo </label><br>
                                                <input type="checkbox"
                                                       name="is_active_sin_variante"
                                                       id="is_active_sin_variante"
                                                       data-is_active_sin_variante
                                                       data-bootstrap-switch
                                                       data-size="normal"
                                                       data-off-color="danger"
                                                       data-on-text="SI"
                                                       data-off-text="NO"
                                                       data-on-color="success"
                                                       checked>
                                            </div>
                                        </div>

                                    </div>
                                    <!-- /.card-body -->
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="card card-primary">
                                    <div class="card-header">
                                        <h3 class="card-title">Inventario por ubicación</h3>

                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                                <i class="fas fa-minus"></i></button>
                                        </div>
                                    </div>
                                    {{--<div class="card-body">
                                        <div class="form-group row">

                                            <div class="col-md-3">
                                                <label>Almacén</label>
                                                <input type="text" name="warehouse" class="form-control" readonly>
                                                <input type="hidden" name="warehouse_id" class="form-control">
                                            </div>

                                            <div class="col-md-2">
                                                <label for="stock_actual">Stock Actual</label>
                                                <input type="number" id="stock_actual" name="stock_actual" class="form-control" placeholder="0.00" min="0" value="0" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                                    this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                                    " readonly>
                                            </div>

                                            <div class="col-md-1">
                                                <label for="reservado">Reservado</label>
                                                <input type="number" id="reservado" name="reservado" class="form-control" placeholder="0.00" min="0" value="0" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                                    this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                                    " readonly>
                                            </div>

                                            <div class="col-md-2">
                                                <label for="stock_min">Stock Mínimo <span class="right badge badge-danger">(*)</span></label>
                                                <input type="number" id="stock_min" name="stock_min" class="form-control" placeholder="0.00" min="0" value="0" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                                    this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                                    ">
                                            </div>

                                            <div class="col-md-2">
                                                <label for="stock_max">Stock Máximo</label>
                                                <input type="number" id="stock_max" name="stock_max" class="form-control" placeholder="0.00" min="0" value="0" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                                    this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                                    ">
                                            </div>

                                            <div class="col-md-1">
                                                <label for="precio_promedio">Prec Prom</label>
                                                <input type="number" id="precio_promedio" name="precio_promedio" class="form-control" placeholder="0.00" min="0" value="0" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                                    this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                                    " readonly>
                                            </div>

                                            <div class="col-md-1">
                                                <label for="ultimo_costo">Ult Costo</label>
                                                <input type="number" id="ultimo_costo" name="ultimo_costo" class="form-control" placeholder="0.00" min="0" value="0" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                                    this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                                    " readonly>
                                            </div>
                                        </div>

                                    </div>--}}
                                    <div class="card-body table-responsive p-0">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead>
                                            <tr>
                                                <th>Almacén</th>
                                                <th>Stock actual</th>
                                                <th>Reservado</th>
                                                <th>Stock mínimo</th>
                                                <th>Stock máximo</th>
                                                <th>Prec. prom.</th>
                                                <th>Últ. costo</th>
                                            </tr>
                                            </thead>
                                            <tbody id="tbody-inventory-levels-single">
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- /.card-body -->
                                </div>
                            </div>

                        </div>
                        @endif
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>

        <div class="row">
            {{--SECCION CON VARIANES--}}
            <div class="col-md-12" id="seccion_con_variantes" style="@if ( !$tieneVariantes )display: none;@endif">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Datos operativos</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group row">
                            @if(in_array('talla', $enabled, true))
                                <div class="col-md-5">
                                    <label for="talla">Talla </label>
                                    <div class="input-group">
                                        <select id="talla" name="talla[]" class="form-control select2" multiple="multiple" style="width:83%;">
                                            @foreach($tallas as $talla)
                                                <option value="{{ $talla->id }}" data-short-name="{{ $talla->short_name }}">
                                                    {{ $talla->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalTalla">
                                                +
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{--@if(in_array('talla', $enabled, true))--}}
                            <div class="col-md-5">
                                <label for="color">Colores </label>
                                <div class="input-group">
                                    <select id="color" name="color[]" class="form-control select2" multiple="multiple" style="width:83%;">
                                        @foreach($colors as $color)
                                            <option value="{{ $color->id }}" data-short-name="{{ $color->short_name }}">
                                                {{ $color->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalColor">
                                            +
                                        </button>
                                    </div>
                                </div>
                            </div>
                            {{--@endif--}}

                            <div class="col-md-2">
                                <label for="">&nbsp;</label><br>
                                <button type="button" id="btn-generate_variantes" class="btn btn-success btn-block">Generar variantes</button>
                            </div>

                        </div>
                        <hr>
                        <div class="form-group row">

                            <!-- Aqui iran las variantes que seran como una tabla -->
                            <div class="col-md-1">
                                <div class="form-group">
                                    <strong>Talla</strong>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <strong>Color</strong>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <strong>SKU Sugerido</strong>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <strong>Código Barras</strong>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <strong>Imagen</strong>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <strong>Activo</strong>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <strong>Inventariable</strong>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <strong>Stock Mínino</strong>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <strong>Stock Máximo</strong>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <strong>Acción</strong>
                                </div>
                            </div>

                        </div>
                        <div class="form-group row">
                            <div id="body-variantes">

                            </div>
                        </div>

                    </div>
                    <!-- /.card-body -->
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <a href="{{ route('material.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="button" id="btn-submit" class="btn btn-outline-success float-right">Guardar material</button>
            </div>
        </div>
        <!-- /.card-footer -->
    </form>

    <!-- Modal Crear Unidad de Medida -->
    <div class="modal fade" id="modalUnitMeasure" tabindex="-1" role="dialog" aria-labelledby="modalUnitMeasureLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalUnitMeasureLabel">Crear Unidad de Medida</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formCreateUnitMeasure" data-url="{{ route('unitmeasure.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label>Unidad de medida <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" class="form-control" name="name" onkeyup="mayus(this);" placeholder="Ejm: Unidad de medida">
                            </div>

                            <div class="col-md-6">
                                <label>Descripción</label>
                                <input type="text" class="form-control" name="description" onkeyup="mayus(this);" placeholder="Ejm: Descripción">
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="button" id="btnSaveUnitMeasure" class="btn btn-outline-success">Guardar</button>
                            <button type="reset" class="btn btn-outline-secondary">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Marca -->
    <div class="modal fade" id="modalBrand" tabindex="-1" role="dialog" aria-labelledby="modalBrandLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary py-2">
                    <h5 class="modal-title" id="modalBrandLabel">Nueva Marca</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formCreateBrand" class="form-horizontal" data-url="{{ route('brand.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label class="col-12 col-form-label">Marca <span class="right badge badge-danger">(*)</span></label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="name" onkeyup="mayus(this);" placeholder="Ejm: Marca">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="col-12 col-form-label">Comentario</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="comment" onkeyup="mayus(this);" placeholder="Ejm: Descripción">
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="button" id="btn-saveBrand" class="btn btn-outline-success">Guardar</button>
                            <button type="reset" class="btn btn-outline-secondary">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Modelo -->
    <div class="modal fade" id="modalExampler" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form id="formCreateExampler" data-url="{{ route('exampler.store') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title">Registrar Modelo</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label>Modelo <span class="badge badge-danger">(*)</span></label>
                                <input type="text" class="form-control" name="name" placeholder="Ejm: Modelo" onkeyup="mayus(this);">
                            </div>
                            <div class="col-md-6">
                                <label>Comentario</label>
                                <input type="text" class="form-control" name="comment" placeholder="Ejm: Descripción" onkeyup="mayus(this);">
                            </div>
                        </div>
                        <input type="hidden" name="brand_id" id="brand_id_hidden">
                    </div>

                    <div class="modal-footer text-center">
                        <button type="button" id="btn-saveExampler" class="btn btn-outline-success">Guardar</button>
                        <button type="reset" class="btn btn-outline-secondary">Cancelar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Crear Genero -->
    <div class="modal fade" id="modalGenero" tabindex="-1" role="dialog" aria-labelledby="modalGeneroLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalGeneroLabel">Crear Género</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formCreateGenero" data-url="{{ route('genero.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label>Género <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" class="form-control" name="name" onkeyup="mayus(this);" placeholder="Ejm: Genero">
                            </div>

                            <div class="col-md-6">
                                <label>Descripción</label>
                                <input type="text" class="form-control" name="description" onkeyup="mayus(this);" placeholder="Ejm: Descripción">
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="button" id="btnSaveGenero" class="btn btn-outline-success">Guardar</button>
                            <button type="reset" class="btn btn-outline-secondary">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Talla -->
    <div class="modal fade" id="modalTalla" tabindex="-1" role="dialog" aria-labelledby="modalTallaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTallaLabel">Crear Talla</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formCreateTalla" data-url="{{ route('talla.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label>Talla <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" class="form-control" name="name" onkeyup="mayus(this);" placeholder="Ejm: Talla">
                            </div>

                            <div class="col-md-6">
                                <label>Descripción</label>
                                <input type="text" class="form-control" name="description" onkeyup="mayus(this);" placeholder="Ejm: Descripción">
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="button" id="btnSaveTalla" class="btn btn-outline-success">Guardar</button>
                            <button type="reset" class="btn btn-outline-secondary">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Categoria -->
    <div class="modal fade" id="modalCategoria" tabindex="-1" role="dialog" aria-labelledby="modalCategoriaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalCategoriaLabel">Crear Categoría</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formCreateCategoria" data-url="{{ route('category.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label>Categoría <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" class="form-control" name="name" onkeyup="mayus(this);" placeholder="Ejm: Categoria">
                            </div>

                            <div class="col-md-6">
                                <label>Descripción</label>
                                <input type="text" class="form-control" name="description" onkeyup="mayus(this);" placeholder="Ejm: Descripción">
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="button" id="btnSaveCategoria" class="btn btn-outline-success">Guardar</button>
                            <button type="reset" class="btn btn-outline-secondary">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear SubCategoria -->
    <div class="modal fade" id="modalSubCategoria" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form id="formCreateSubCategoria" data-url="{{ route('subcategory.store.individual') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title">Registrar Subcategoría</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label>Subcategoria <span class="badge badge-danger">(*)</span></label>
                                <input type="text" class="form-control" name="subcategories[0][name]" placeholder="Ejm: Subcategoria" onkeyup="mayus(this);">
                            </div>
                            <div class="col-md-6">
                                <label>Descripción</label>
                                <input type="text" class="form-control" name="subcategories[0][description]" placeholder="Ejm: Descripción" onkeyup="mayus(this);">
                            </div>
                        </div>
                        <input type="hidden" name="category_id" id="categoria_id_hidden">
                    </div>

                    <div class="modal-footer text-center">
                        <button type="button" id="btn-saveSubCategoria" class="btn btn-outline-success">Guardar</button>
                        <button type="reset" class="btn btn-outline-secondary">Cancelar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Crear Color -->
    <div class="modal fade" id="modalColor" tabindex="-1" role="dialog" aria-labelledby="modalColorLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalCategoriaLabel">Crear Color</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formCreateColor" data-url="{{ route('color.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label>Color <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" class="form-control" name="name" placeholder="Ejm: Blanco">
                            </div>

                            <div class="col-md-4">
                                <label>Código HEX</label>
                                <input type="text" class="form-control" name="code" onkeyup="mayus(this);" placeholder="Ejm: #000000">
                            </div>

                            <div class="col-md-4">
                                <label>Nombre clave <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" class="form-control" name="short_name" onkeyup="mayus(this);" placeholder="Ejm: BLA">
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="button" id="btnSaveColor" class="btn btn-outline-success">Guardar</button>
                            <button type="reset" class="btn btn-outline-secondary">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{--<template id="template-variante">
        <div class="row item-variante mb-2">
            <input type="hidden" data-variant_id>
            <div class="col-md-1">
                <div class="form-group">
                    <input type="text" class="form-control form-control-sm" data-talla_text readonly>
                    <input type="hidden" data-talla_id>
                </div>
            </div>

            <div class="col-md-1">
                <div class="form-group">
                    <input type="text" class="form-control form-control-sm" data-color_text readonly>
                    <input type="hidden" data-color_id>
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    <input type="text" class="form-control form-control-sm" data-sku_sugerido>
                </div>
            </div>

            <div class="col-md-1">
                <div class="form-group">
                    <input type="text" class="form-control form-control-sm" data-codigo_barras>
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    <input type="file" class="form-control form-control-sm" data-image_variante>
                    <small class="text-muted" data-image_label></small>
                </div>
            </div>

            <div class="col-md-1">
                <input type="checkbox"
                       data-is_active_variante
                       data-bootstrap-switch
                       data-size="normal"
                       data-off-color="danger"
                       data-on-text="SI"
                       data-off-text="NO"
                       data-on-color="success"
                       checked>
            </div>

            <div class="col-md-1">
                <input type="checkbox"
                       data-afecto_inventario_variante
                       data-bootstrap-switch
                       data-size="normal"
                       data-off-color="danger"
                       data-on-text="SI"
                       data-off-text="NO"
                       data-on-color="success"
                       checked>
            </div>

            <div class="col-md-1">
                <div class="form-group">
                    <input type="number" class="form-control form-control-sm" data-stock_minimo min="0" step="0.01">
                </div>
            </div>

            <div class="col-md-1">
                <div class="form-group">
                    <input type="number" class="form-control form-control-sm" data-stock_maximo min="0" step="0.01">
                </div>
            </div>

            <div class="col-md-1">
                <button type="button" data-delete class="btn btn-block btn-outline-danger btn-sm">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </template>--}}
    <template id="template-variante">
        <div class="item-variante border rounded p-2 mb-3">
            <input type="hidden" data-variant_id>

            <div class="row align-items-center">
                <div class="col-md-1">
                    <div class="form-group mb-2">
                        <label class="mb-1">Talla</label>
                        <input type="text" class="form-control form-control-sm" data-talla_text readonly>
                        <input type="hidden" data-talla_id>
                    </div>
                </div>

                <div class="col-md-1">
                    <div class="form-group mb-2">
                        <label class="mb-1">Color</label>
                        <input type="text" class="form-control form-control-sm" data-color_text readonly>
                        <input type="hidden" data-color_id>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group mb-2">
                        <label class="mb-1">SKU</label>
                        <input type="text" class="form-control form-control-sm" data-sku_sugerido>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group mb-2">
                        <label class="mb-1">Código barras</label>
                        <input type="text" class="form-control form-control-sm" data-codigo_barras>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group mb-2">
                        <label class="mb-1">Imagen</label>
                        <input type="file" class="form-control form-control-sm" data-image_variante>
                        <small class="text-muted d-block mt-1" data-image_label></small>
                    </div>
                </div>

                <div class="col-md-1">
                    <div class="form-group mb-2">
                        <label class="mb-1 d-block">Activo</label>
                        <input type="checkbox"
                               data-is_active_variante
                               data-bootstrap-switch
                               data-size="normal"
                               data-off-color="danger"
                               data-on-text="SI"
                               data-off-text="NO"
                               data-on-color="success"
                               checked>
                    </div>
                </div>

                <div class="col-md-1">
                    <div class="form-group mb-2">
                        <label class="mb-1 d-block">Invent.</label>
                        <input type="checkbox"
                               data-afecto_inventario_variante
                               data-bootstrap-switch
                               data-size="normal"
                               data-off-color="danger"
                               data-on-text="SI"
                               data-off-text="NO"
                               data-on-color="success"
                               checked>
                    </div>
                </div>

                <div class="col-md-1">
                    <div class="form-group mb-2">
                        <label class="mb-1">Stock total</label>
                        <input type="number" class="form-control form-control-sm" data-stock_total readonly>
                    </div>
                </div>

                <div class="col-md-1">
                    <div class="form-group mb-2">
                        <label class="mb-1 d-block">Acciones</label>
                        <div class="d-flex flex-column">
                            <button type="button"
                                    class="btn btn-outline-info btn-sm mb-1"
                                    data-toggle_inventory_levels>
                                Inventario
                            </button>

                            {{--<button type="button"
                                    data-delete
                                    class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-trash"></i>
                            </button>--}}
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-2 d-none" data-inventory_levels_wrapper>
                <div class="col-md-12">
                    <div class="card card-outline card-info mb-0">
                        <div class="card-header py-2">
                            <h3 class="card-title text-sm mb-0">Inventario por almacén</h3>
                        </div>

                        <div class="card-body p-2">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead>
                                    <tr>
                                        <th>Almacén</th>
                                        <th>Stock actual</th>
                                        <th>Reservado</th>
                                        <th>Stock mínimo</th>
                                        <th>Stock máximo</th>
                                        <th>Precio prom.</th>
                                        <th>Últ. costo</th>
                                    </tr>
                                    </thead>
                                    <tbody data-inventory_levels_body></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
@endsection

@section('plugins')
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
    <!-- Dropzone JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
@endsection

@section('scripts')
    <script>
        $(function () {
            //Initialize Select2 Elements
            $('#material_type').select2({
                placeholder: "Selecione tipo de material",
            });
            $('#category').select2({
                placeholder: "Selecione categoría",
            });
            $('#subcategory').select2({
                placeholder: "Selecione subcategoría",
            });
            $('#brand').select2({
                placeholder: "Selecione una marca",
            });
            $('#exampler').select2({
                placeholder: "Selecione un modelo",
            });
            $('#priority').select2({
                placeholder: "Selecione una prioridad",
            });
            $('#feature').select2({
                placeholder: "Seleccione característica",
            });
            $('#type').select2({
                placeholder: "Elija",
            });
            $('#subtype').select2({
                placeholder: "Elija",
            });
            $('#warrant').select2({
                placeholder: "Elija",
            });
            $('#quality').select2({
                placeholder: "Elija",
            });
            $('#unit_measure').select2({
                placeholder: "Elija",
            });
            $('#perecible').select2({
                placeholder: "Seleccione ",
                allowClear: true,
            });
            $('#genero').select2({
                placeholder: "Seleccione género",
                allowClear: true,
            });
            $('#talla').select2({
                placeholder: "Seleccione talla",
                allowClear: true,
            });
            $('#color').select2({
                placeholder: 'Seleccione colores',
                allowClear: true
            });
            $('#tipo_venta').select2({
                placeholder: "Seleccione Tipo Venta",
                allowClear: true,
            });
            $("input[data-bootstrap-switch]").each(function(){
                $(this).bootstrapSwitch();
            });
        })
    </script>
    <script>
        Dropzone.autoDiscover = false;
        let uploadedImage = null;

        const myDropzone = new Dropzone("#image-dropzone", {
            url: "#", // no enviamos con Dropzone
            autoProcessQueue: false,
            maxFiles: 1,
            acceptedFiles: 'image/*',
            addRemoveLinks: true,
            dictDefaultMessage: 'Arrastra una imagen aquí o haz clic para seleccionar',
            init: function () {
                this.on("addedfile", function (file) {
                    uploadedImage = file;
                });
                this.on("removedfile", function (file) {
                    uploadedImage = null;
                });
            }
        });
    </script>

    <script>
        const tieneVariantes = @json($tieneVariantes);
        const variantesEdit = @json($variantesEdit);
        const warehousesActivos = @json($warehousesActivos);
    </script>

    <script src="{{ asset('js/material/edit.js') }}?v={{ time() }}"></script>
@endsection
