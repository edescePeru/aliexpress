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
            <div class="col-md-8">
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
                                <input type="text" id="description" {{--onkeyup="mayus(this);"--}} name="description" class="form-control" value="{{ $material->description }}">
                            </div>
                        </div>

                        <div class="form-group row">
                            {{--<div class="col-md-6">
                                <label for="measure">Medida </label>
                                <input type="text" id="measure" --}}{{--onkeyup="mayus(this);"--}}{{-- name="measure" class="form-control" value="{{ $material->measure }}">
                            </div>--}}

                            @if(in_array('unit_measure', $enabled, true))
                            <div class="col-md-4">
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
                            @if(in_array('brand', $enabled, true))
                            <div class="col-md-4">
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
                            <div class="col-md-4">
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
                        </div>

                        <div class="form-group row">
                            @if(in_array('genero', $enabled, true))
                            <div class="col-md-4">
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
                            @if(in_array('talla', $enabled, true))
                            <div class="col-md-4">
                                <label for="talla">Talla </label>
                                <div class="input-group">
                                    <select id="talla" name="talla" class="form-control select2" style="width: 83%;">
                                        <option></option>
                                        @foreach( $tallas as $talla )
                                            <option value="{{ $talla->id }}" {{ ($talla->id === $material->quality_id) ? 'selected':'' }}>{{ $talla->description }}</option>
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
                            @if(in_array('perecible', $enabled, true))
                            <div class="col-md-4">
                                <label for="perecible">Perecible </label>
                                <select id="perecible" name="perecible" class="form-control select2" style="width: 100%;">
                                    <option></option>
                                    <option value="s" {{ ($material->perecible == "s") ? 'selected':'' }}>SI</option>
                                    <option value="n" {{ ($material->perecible == "n") ? 'selected':'' }}>NO</option>
                                </select>
                            </div>
                            @endif
                        </div>

                        <div class="form-group row">
                            @if(in_array('category', $enabled, true))
                            <div class="col-md-4">
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
                            <div class="col-md-4">
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
                            <div class="col-md-4">
                                <label for="subcategory">Cantidad por paquete </label>
                                <div class="form-group clearfix">
                                    <div class="icheck-primary d-inline">
                                        <input type="checkbox" name="pack" id="checkboxPack" {{ ($material->isPack == 1) ? 'checked':'' }}>
                                        <label for="checkboxPack">Es paquete</label>
                                        <input type="number" class="form-control form-control-sm d-inline ml-2" style="width: 70px;" id="inputPack" name="inputPack" value="{{ $material->quantityPack  }}" min="0" {{ ($material->isPack == 0) ? 'disabled':'' }}>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="form-group">
                            <label for="name">Nombre completo</label>
                            <div class="input-group mb-3">
                                <input type="text" class="form-control rounded-0" id="name" {{--onkeyup="mayus(this);"--}} name="name" value="{{ $material->full_name }}" readonly>
                                <span class="input-group-append">
                                    <button type="button" class="btn btn-info btn-flat" id="btn-generate"> <i class="fa fa-redo"></i> Actualizar</button>
                                </span>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="unit_price">Precio Unitario </label>
                                <input type="number" id="unit_price" name="unit_price" class="form-control" placeholder="0.00" min="0" value="{{ $material->unit_price }}" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" readonly>
                            </div>
                            <div class="col-md-4">
                                <label for="codigo">Código del producto </label>
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control rounded-0" id="codigo" name="codigo" value="{{ $material->codigo }}">
                                    <span class="input-group-append">
                                        <button type="button" class="btn btn-info btn-flat" id="btn-generateCode"> <i class="fas fa-random"></i></button>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="tipo_venta">Tipo de Venta </label>
                                <select id="tipo_venta" name="tipo_venta" class="form-control select2" style="width: 100%;">
                                    <option></option>
                                    @foreach( $tipoVentas as $tipo )
                                        <option value="{{$tipo->id}}" {{ ($tipo->id === $material->tipo_venta_id) ? 'selected': ''}}>{{ $tipo->description }}</option>
                                    @endforeach
                                </select>
                            </div>

                        </div>

                        <div class="form-group">
                            <label for="image">Imagen </label>
                            <input type="file" id="image" name="image" class="form-control">
                            <img src="{{ asset('images/material/'.$material->image) }}" width="100px" height="100px" alt="{{ $material->description }}">
                        </div>

                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <div class="col-md-4">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Categoría y Stock</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="stock_max">Stock Máximo <span class="right badge badge-danger">(*)</span></label>
                            <input type="number" id="stock_max" name="stock_max" class="form-control" placeholder="0.00" min="0" value="{{ $material->stock_max }}" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                    this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                    ">
                        </div>
                        <div class="form-group">
                            <label for="stock_min">Stock Mínimo <span class="right badge badge-danger">(*)</span></label>
                            <input type="number" id="stock_min" name="stock_min" class="form-control" placeholder="0.00" min="0" value="{{ $material->stock_min }}" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                    this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                    ">
                        </div>

                        <div class="form-group">
                            <label for="stock_current">Stock Actual <span class="right badge badge-danger">(*)</span></label>
                            <input type="number" id="stock_current" name="stock_current" class="form-control" placeholder="0.00" min="0" value="{{ $material->stock_current }}" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                    this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                    " readonly>
                        </div>


                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
                {{--<div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Promociones</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        @foreach( $discountQuantities as $discountQuantity )
                            @php
                                $isChecked = isset($materialsDiscounts[$discountQuantity->id]);
                                $percentage = $isChecked ? $materialsDiscounts[$discountQuantity->id] : $discountQuantity->percentage;
                            @endphp
                            <div class="form-group clearfix">
                                <div class="icheck-primary d-inline">
                                    <input type="checkbox" name="discount[{{$discountQuantity->id}}]" id="checkboxPrimary{{$discountQuantity->id}}" {{ $isChecked ? 'checked' : '' }}>
                                    <label for="checkboxPrimary{{$discountQuantity->id}}">
                                        {{$discountQuantity->description}} - {{ $percentage }}%
                                    </label>
                                    <input type="number" class="form-control form-control-sm d-inline ml-2" style="width: 70px;" id="percentageInput{{$discountQuantity->id}}" name="percentage[{{$discountQuantity->id}}]" value="{{ $percentage }}" min="0" max="100"> %
                                </div>
                            </div>
                        @endforeach

                    </div>
                    <!-- /.card-body -->
                </div>--}}
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

@endsection

@section('plugins')
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
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
            $('#tipo_venta').select2({
                placeholder: "Seleccione Tipo Venta",
                allowClear: true,
            });
            $("input[data-bootstrap-switch]").each(function(){
                $(this).bootstrapSwitch();
            });
        })
    </script>
    <script src="{{ asset('js/material/edit.js') }}?v={{ time() }}"></script>
@endsection
