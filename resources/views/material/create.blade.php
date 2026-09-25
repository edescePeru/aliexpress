@extends('layouts.appAdmin2')

@section('openMaterial')
    menu-open
@endsection
@section('activeMaterial')
    active
@endsection

@section('activeCreateMaterial')
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
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales</span>
        <h1 class="page-title">Nuevo material</h1>
        <p class="next-page-description">Define la identidad, clasificación e inventario del nuevo material.</p>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <span class="next-toolbar-icon" aria-hidden="true"><i class="fas fa-cube"></i></span>
            <div>
                <strong>Ficha del material</strong>
                <span>Los campos marcados con (*) son obligatorios.</span>
            </div>
        </div>
        <div class="next-toolbar-actions">
            <button type="reset" form="formCreate" class="btn btn-outline-secondary">Cancelar</button>
            <button type="button" id="btn-submit" class="btn btn-primary">
                <i class="fas fa-save" aria-hidden="true"></i> Guardar material
            </button>
        </div>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('material.indexV2') }}"><i class="fa fa-archive"></i> Materiales</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Nuevo</li>
    </ol>
@endsection

@section('content')
    <form id="formCreate" class="form-horizontal next-material-page" data-url="{{ route('material.store') }}" enctype="multipart/form-data">
        @csrf

        <section class="next-form-section" aria-labelledby="material-identity-title">
            <div class="next-section-header">
                <div>
                    <span class="next-section-kicker">01</span>
                    <h2 id="material-identity-title">Identidad del material</h2>
                    <p>La descripción comercial que identifica al material en todo el sistema.</p>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <div class="form-group">
                        <label for="description">Descripción <span class="next-required">(*)</span></label>
                        <input type="text" id="description" name="description" class="form-control" autocomplete="off">
                    </div>
                </div>
            </div>
        </section>

        <section class="next-form-section" aria-labelledby="material-classification-title">
            <div class="next-section-header">
                <div>
                    <span class="next-section-kicker">02</span>
                    <h2 id="material-classification-title">Clasificación comercial</h2>
                    <p>Organiza el material para búsquedas, reportes y la generación de su nombre operativo.</p>
                </div>
            </div>

            <div class="row">
                @if(in_array('brand', $enabled, true))
                    <div class="col-md-6 col-xl-3">
                        <div class="form-group">
                            <label for="brand">Marca</label>
                            <div class="input-group next-field-action">
                                <select id="brand" name="brand" class="form-control select2">
                                    <option></option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-primary next-field-action-button" data-toggle="modal" data-target="#modalBrand" title="Crear marca" aria-label="Crear marca">
                                        <i class="fas fa-plus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(in_array('exampler', $enabled, true))
                    <div class="col-md-6 col-xl-3">
                        <div class="form-group">
                            <label for="exampler">Modelo</label>
                            <div class="input-group next-field-action">
                                <select id="exampler" name="exampler" class="form-control select2"></select>
                                <div class="input-group-append">
                                    <button type="button" id="btn-newExampler" class="btn btn-outline-primary next-field-action-button" data-toggle="modal" data-target="#modalExampler" style="display: none;" title="Crear modelo" aria-label="Crear modelo">
                                        <i class="fas fa-plus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(in_array('category', $enabled, true))
                    <div class="col-md-6 col-xl-3">
                        <div class="form-group">
                            <label for="category">Categoría</label>
                            <div class="input-group next-field-action">
                                <select id="category" name="category" class="form-control select2">
                                    <option></option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-primary next-field-action-button" data-toggle="modal" data-target="#modalCategoria" title="Crear categoría" aria-label="Crear categoría">
                                        <i class="fas fa-plus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(in_array('subcategory', $enabled, true))
                    <div class="col-md-6 col-xl-3">
                        <div class="form-group">
                            <label for="subcategory">Subcategoría</label>
                            <div class="input-group next-field-action">
                                <select id="subcategory" name="subcategory" class="form-control select2">
                                    <option></option>
                                </select>
                                <div class="input-group-append">
                                    <button type="button" id="btn-newSubCategoria" class="btn btn-outline-primary next-field-action-button" data-toggle="modal" data-target="#modalSubCategoria" style="display: none;" title="Crear subcategoría" aria-label="Crear subcategoría">
                                        <i class="fas fa-plus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif


                @if(in_array('material_type', $enabled, true))
                    <div class="col-md-6 col-xl-3">
                        <div class="form-group">
                            <label for="material_type">Tipo de material</label>
                            <div class="input-group next-field-action">
                                <select id="material_type" name="material_type" class="form-control select2">
                                    <option></option>
                                </select>
                                <div class="input-group-append">
                                    @can('create_materialType')
                                        <button type="button" id="btn-newMaterialType" class="btn btn-outline-primary next-field-action-button" data-toggle="modal" data-target="#modalMaterialType" style="display: none;" title="Crear tipo de material" aria-label="Crear tipo de material">
                                            <i class="fas fa-plus" aria-hidden="true"></i>
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(in_array('subtype', $enabled, true))
                    <div class="col-md-6 col-xl-3">
                        <div class="form-group">
                            <label for="subtype">Subtipo de material</label>
                            <div class="input-group next-field-action">
                                <select id="subtype" name="subtype" class="form-control select2">
                                    <option></option>
                                </select>
                                <div class="input-group-append">
                                    @can('create_subType')
                                        <button type="button" id="btn-newSubtype" class="btn btn-outline-primary next-field-action-button" data-toggle="modal" data-target="#modalSubtype" style="display: none;" title="Crear subtipo de material" aria-label="Crear subtipo de material">
                                            <i class="fas fa-plus" aria-hidden="true"></i>
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(in_array('typescrap', $enabled, true))
                    <div class="col-md-6 col-xl-3">
                        <div class="form-group">
                            <label for="typescrap">Tipo de retacería</label>
                            <div class="input-group next-field-action">
                                <select id="typescrap" name="typescrap" class="form-control select2">
                                    <option></option>
                                    @foreach($typescraps as $typescrap)
                                        <option value="{{ $typescrap->id }}">{{ $typescrap->name }}</option>
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    @can('create_typeScrap')
                                        <button type="button" class="btn btn-outline-primary next-field-action-button" data-toggle="modal" data-target="#modalTypescrap" title="Crear tipo de retacería" aria-label="Crear tipo de retacería">
                                            <i class="fas fa-plus" aria-hidden="true"></i>
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(in_array('genero', $enabled, true))
                    <div class="col-md-6 col-xl-3">
                        <div class="form-group">
                            <label for="genero">Género</label>
                            <div class="input-group next-field-action">
                                <select id="genero" name="genero" class="form-control select2">
                                    <option></option>
                                    @foreach($generos as $genero)
                                        <option value="{{ $genero->id }}">{{ $genero->name }}</option>
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-primary next-field-action-button" data-toggle="modal" data-target="#modalGenero" title="Crear género" aria-label="Crear género">
                                        <i class="fas fa-plus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(in_array('unit_measure', $enabled, true))
                    <div class="col-md-6 col-xl-3">
                        <div class="form-group">
                            <label for="unit_measure">Unidad de medida</label>
                            <div class="input-group next-field-action">
                                <select id="unit_measure" name="unit_measure" class="form-control select2">
                                    <option></option>
                                    @foreach($unitMeasures as $unitMeasure)
                                        <option value="{{ $unitMeasure->id }}">{{ $unitMeasure->name }}</option>
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-primary next-field-action-button" data-toggle="modal" data-target="#modalUnitMeasure" title="Crear unidad de medida" aria-label="Crear unidad de medida">
                                        <i class="fas fa-plus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="col-md-6 col-xl-3">
                    <div class="form-group">
                        <label for="tipo_venta">Tipo de venta</label>
                        <select id="tipo_venta" name="tipo_venta" class="form-control select2">
                            <option></option>
                            @foreach($tipoVentas as $tipo)
                                <option value="{{ $tipo->id }}">{{ $tipo->description }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if(in_array('perecible', $enabled, true))
                    <div class="col-md-6 col-xl-3">
                        <div class="form-group">
                            <label for="perecible">Perecible</label>
                            <select id="perecible" name="perecible" class="form-control select2">
                                <option></option>
                                <option value="s">SI</option>
                                <option value="n">NO</option>
                            </select>
                        </div>
                    </div>
                @endif
            </div>

            <div class="row next-generated-row">
                <div class="col-xl-6">
                    <div class="form-group">
                        <label for="name">Nombre completo</label>
                        <div class="next-generated-field">
                            <div class="input-group">
                                <input type="text" class="form-control" id="name" onkeyup="mayus(this);" name="name">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-primary" id="btn-generate">
                                        <i class="fa fa-redo mr-1" aria-hidden="true"></i> Actualizar
                                    </button>
                                </div>
                            </div>
                            <small>Se genera con descripción, marca, modelo, categoría y género.</small>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="next-form-section" aria-labelledby="material-inventory-title">
            <div class="next-section-header">
                <div>
                    <span class="next-section-kicker">03</span>
                    <h2 id="material-inventory-title">Inventario e imagen</h2>
                    <p>Configura el control operativo y la referencia visual del material.</p>
                </div>
            </div>

            <div class="row next-operational-layout">
                <div class="col-xl-8">
                    <div class="next-operational-panel">
                        @php
                            $variantsEnabled =
                                in_array('talla', $enabled, true)
                                &&
                                in_array('color', $enabled, true);
                        @endphp
                        <div class="next-variant-mode">
                            <div>
                                <strong>Modo de inventario</strong>
                                <span>Elige si el material se controla como una sola referencia o mediante variantes.</span>
                            </div>
                            <div class="next-variant-options" role="group" aria-label="Modo de variantes">
                                <div class="icheck-primary">
                                    <input type="radio" id="sin_variantes" name="variantes" value="0" checked>
                                    <label for="sin_variantes">Sin variantes</label>
                                </div>
                                @if($variantsEnabled)
                                <div class="icheck-primary">
                                    <input type="radio" id="con_variantes" name="variantes" value="1">
                                    <label for="con_variantes">Con variantes</label>
                                </div>
                                @endif
                            </div>
                        </div>

                        <div id="seccion_sin_variantes" class="next-simple-inventory">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="sku_sin_variantes">SKU</label>
                                        <input type="text" id="sku_sin_variantes" name="sku_sin_variantes" class="form-control">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="codigo_sin_variantes">Código de barras</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="codigo_sin_variantes" name="codigo_sin_variantes">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-primary btn-generateCode" id="btn-generateCodeSinVariantes" title="Generar código" aria-label="Generar código">
                                                    <i class="fas fa-random" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="inputPack">Presentación</label>
                                        <div class="next-package-control">
                                            <div class="icheck-primary">
                                                <input type="checkbox" name="pack" id="checkboxPack">
                                                <label for="checkboxPack">Es paquete</label>
                                            </div>
                                            <input type="number" class="form-control form-control-sm" id="inputPack" name="inputPack" value="1" min="0" aria-label="Cantidad por paquete">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="afecto_inventario_sin_variantes">Inventariable</label>
                                        <div class="next-switch-field">
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
                                            <small>Activa el control de existencias.</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="stock_min">Stock mínimo <span class="next-required">(*)</span></label>
                                        <input type="number" id="stock_min" name="stock_min" class="form-control" placeholder="0.00" min="0" value="0" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                            this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                            ">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="stock_max">Stock máximo <span class="next-required">(*)</span></label>
                                        <input type="number" id="stock_max" name="stock_max" class="form-control" placeholder="0.00" min="0" value="0" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                            this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                            ">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="next-image-panel">
                        <div class="next-image-heading">
                            <strong>Imagen del material</strong>
                            <span>Una imagen clara facilita la búsqueda y selección en operación.</span>
                        </div>
                        <div class="dropzone" id="image-dropzone"></div>
                        <small>Formatos de imagen compatibles con Dropzone. Máximo un archivo.</small>
                    </div>
                </div>
            </div>
        </section>

        <section class="next-form-section next-variants-section" id="seccion_con_variantes" style="display: none;" aria-labelledby="material-variants-title">
            <div class="next-section-header">
                <div>
                    <span class="next-section-kicker">04</span>
                    <h2 id="material-variants-title">Configuración de variantes</h2>
                    <p>Combina tallas y colores, y revisa cada referencia antes de guardar.</p>
                </div>
            </div>

            <div class="row next-variants-toolbar">
                @if(in_array('talla', $enabled, true))
                    <div class="col-lg-5">
                        <div class="form-group">
                            <label for="talla">Talla</label>
                            <div class="input-group next-field-action">
                                <select id="talla" name="talla[]" class="form-control select2" multiple="multiple">
                                    @foreach($tallas as $talla)
                                        <option value="{{ $talla->id }}" data-short-name="{{ $talla->short_name }}">
                                            {{ $talla->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-primary next-field-action-button" data-toggle="modal" data-target="#modalTalla" title="Crear talla" aria-label="Crear talla">
                                        <i class="fas fa-plus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(in_array('color', $enabled, true))
                <div class="col-lg-5">
                    <div class="form-group">
                        <label for="color">Colores</label>
                        <div class="input-group next-field-action">
                            <select id="color" name="color[]" class="form-control select2" multiple="multiple">
                                @foreach($colors as $color)
                                    <option value="{{ $color->id }}" data-short-name="{{ $color->short_name }}">
                                        {{ $color->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-primary next-field-action-button" data-toggle="modal" data-target="#modalColor" title="Crear color" aria-label="Crear color">
                                    <i class="fas fa-plus" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="col-lg-2 d-flex align-items-end">
                    <button type="button" id="btn-generate_variantes" class="btn btn-outline-primary btn-block mb-3 next-variants-generate">
                        <i class="fas fa-layer-group mr-1" aria-hidden="true"></i> Generar
                    </button>
                </div>
            </div>

            <div class="next-variants-results" aria-live="polite">
                <table class="table next-variants-table" aria-describedby="material-variants-title">
                    <colgroup>
                        <col class="next-variant-col next-variant-col--identity">
                        <col class="next-variant-col next-variant-col--sku">
                        <col class="next-variant-col next-variant-col--barcode">
                        <col class="next-variant-col next-variant-col--image">
                        <col class="next-variant-col next-variant-col--inventory">
                        <col class="next-variant-col next-variant-col--stock">
                        <col class="next-variant-col next-variant-col--stock">
                        <col class="next-variant-col next-variant-col--status">
                        <col class="next-variant-col next-variant-col--action">
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col">Variante</th>
                            <th scope="col">SKU</th>
                            <th scope="col">Código de barras</th>
                            <th scope="col">Imagen</th>
                            <th scope="col">Inventario</th>
                            <th scope="col">Stock mín.</th>
                            <th scope="col">Stock máx.</th>
                            <th scope="col">Estado</th>
                            <th scope="col" class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="body-variantes" class="next-variants-body">
                        <tr class="next-variants-empty-row">
                            <td colspan="9">
                                <div class="next-variants-empty">
                                    <i class="fas fa-layer-group" aria-hidden="true"></i>
                                    <span>Selecciona talla y color para generar las combinaciones.</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </form>

    <!-- Modal Crear Unidad de Medida -->
    <div class="modal fade next-aux-modal" id="modalUnitMeasure" tabindex="-1" role="dialog" aria-labelledby="modalUnitMeasureLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <form id="formCreateUnitMeasure" class="next-aux-modal-form" data-url="{{ route('unitmeasure.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalUnitMeasureLabel">Nueva unidad de medida</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="modalUnitMeasureName">Unidad de medida <span class="next-required" aria-label="obligatorio">*</span></label>
                                <input type="text" id="modalUnitMeasureName" class="form-control" name="name" onkeyup="mayus(this);" placeholder="Ejm: Unidad de medida" aria-required="true" data-modal-autofocus>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="modalUnitMeasureDescription">Descripción</label>
                                <input type="text" id="modalUnitMeasureDescription" class="form-control" name="description" onkeyup="mayus(this);" placeholder="Ejm: Descripción">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-modal-cancel data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btnSaveUnitMeasure" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Crear Marca -->
    <div class="modal fade next-aux-modal" id="modalBrand" tabindex="-1" role="dialog" aria-labelledby="modalBrandLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <form id="formCreateBrand" class="next-aux-modal-form" data-url="{{ route('brand.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalBrandLabel">Nueva marca</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="modalBrandName">Marca <span class="next-required" aria-label="obligatorio">*</span></label>
                                <input type="text" id="modalBrandName" class="form-control" name="name" onkeyup="mayus(this);" placeholder="Ejm: Marca" aria-required="true" data-modal-autofocus>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="modalBrandComment">Comentario</label>
                                <input type="text" id="modalBrandComment" class="form-control" name="comment" onkeyup="mayus(this);" placeholder="Ejm: Descripción">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-modal-cancel data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btn-saveBrand" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Crear Modelo -->
    <div class="modal fade next-aux-modal" id="modalExampler" tabindex="-1" role="dialog" aria-labelledby="modalExamplerLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <form id="formCreateExampler" class="next-aux-modal-form" data-url="{{ route('exampler.store') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalExamplerLabel">Nuevo modelo</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="modalExamplerName">Modelo <span class="next-required" aria-label="obligatorio">*</span></label>
                                <input type="text" id="modalExamplerName" class="form-control" name="name" placeholder="Ejm: Modelo" onkeyup="mayus(this);" aria-required="true" data-modal-autofocus>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="modalExamplerComment">Comentario</label>
                                <input type="text" id="modalExamplerComment" class="form-control" name="comment" placeholder="Ejm: Descripción" onkeyup="mayus(this);">
                            </div>
                        </div>
                        <input type="hidden" name="brand_id" id="brand_id_hidden">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-modal-cancel data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btn-saveExampler" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Crear Genero -->
    <div class="modal fade next-aux-modal" id="modalGenero" tabindex="-1" role="dialog" aria-labelledby="modalGeneroLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <form id="formCreateGenero" class="next-aux-modal-form" data-url="{{ route('genero.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalGeneroLabel">Nuevo género</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="modalGeneroName">Género <span class="next-required" aria-label="obligatorio">*</span></label>
                                <input type="text" id="modalGeneroName" class="form-control" name="name" onkeyup="mayus(this);" placeholder="Ejm: Género" aria-required="true" data-modal-autofocus>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="modalGeneroDescription">Descripción</label>
                                <input type="text" id="modalGeneroDescription" class="form-control" name="description" onkeyup="mayus(this);" placeholder="Ejm: Descripción">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-modal-cancel data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btnSaveGenero" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Crear Talla -->
    <div class="modal fade next-aux-modal" id="modalTalla" tabindex="-1" role="dialog" aria-labelledby="modalTallaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <form id="formCreateTalla" class="next-aux-modal-form" data-url="{{ route('talla.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTallaLabel">Nueva talla</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="modalTallaName">Talla <span class="next-required" aria-label="obligatorio">*</span></label>
                                <input type="text" id="modalTallaName" class="form-control" name="name" onkeyup="mayus(this);" placeholder="Ejm: Talla" aria-required="true" data-modal-autofocus>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="modalTallaDescription">Descripción</label>
                                <input type="text" id="modalTallaDescription" class="form-control" name="description" onkeyup="mayus(this);" placeholder="Ejm: Descripción">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-modal-cancel data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btnSaveTalla" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Crear Categoria -->
    <div class="modal fade next-aux-modal" id="modalCategoria" tabindex="-1" role="dialog" aria-labelledby="modalCategoriaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <form id="formCreateCategoria" class="next-aux-modal-form" data-url="{{ route('category.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCategoriaLabel">Nueva categoría</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="modalCategoriaName">Categoría <span class="next-required" aria-label="obligatorio">*</span></label>
                                <input type="text" id="modalCategoriaName" class="form-control" name="name" onkeyup="mayus(this);" placeholder="Ejm: Categoría" aria-required="true" data-modal-autofocus>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="modalCategoriaDescription">Descripción</label>
                                <input type="text" id="modalCategoriaDescription" class="form-control" name="description" onkeyup="mayus(this);" placeholder="Ejm: Descripción">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-modal-cancel data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btnSaveCategoria" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Crear SubCategoria -->
    <div class="modal fade next-aux-modal" id="modalSubCategoria" tabindex="-1" role="dialog" aria-labelledby="modalSubCategoriaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <form id="formCreateSubCategoria" class="next-aux-modal-form" data-url="{{ route('subcategory.store.individual') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalSubCategoriaLabel">Nueva subcategoría</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="modalSubCategoriaName">Subcategoría <span class="next-required" aria-label="obligatorio">*</span></label>
                                <input type="text" id="modalSubCategoriaName" class="form-control" name="subcategories[0][name]" placeholder="Ejm: Subcategoría" onkeyup="mayus(this);" aria-required="true" data-modal-autofocus>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="modalSubCategoriaDescription">Descripción</label>
                                <input type="text" id="modalSubCategoriaDescription" class="form-control" name="subcategories[0][description]" placeholder="Ejm: Descripción" onkeyup="mayus(this);">
                            </div>
                        </div>
                        <input type="hidden" name="category_id" id="categoria_id_hidden">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-modal-cancel data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btn-saveSubCategoria" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Crear Color -->
    <div class="modal fade next-aux-modal" id="modalColor" tabindex="-1" role="dialog" aria-labelledby="modalColorLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <form id="formCreateColor" class="next-aux-modal-form" data-url="{{ route('color.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalColorLabel">Nuevo color</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="modalColorName">Color <span class="next-required" aria-label="obligatorio">*</span></label>
                                <input type="text" id="modalColorName" class="form-control" name="name" placeholder="Ejm: Blanco" aria-required="true" data-modal-autofocus>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="modalColorCode">Código HEX</label>
                                <input type="text" id="modalColorCode" class="form-control" name="code" onkeyup="mayus(this);" placeholder="Ejm: #000000">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="modalColorShortName">Nombre clave <span class="next-required" aria-label="obligatorio">*</span></label>
                                <input type="text" id="modalColorShortName" class="form-control" name="short_name" onkeyup="mayus(this);" placeholder="Ejm: BLA" aria-required="true">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-modal-cancel data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btnSaveColor" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Crear MaterialType -->
    <div class="modal fade next-aux-modal" id="modalMaterialType" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">

            <form id="formCreateMaterialType" class="next-aux-modal-form" data-url="{{ url('/dashboard/materialtype/store') }}">
                @csrf

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title">
                            Crear Tipo de Material
                        </h5>

                        <button
                                type="button"
                                class="close"
                                data-dismiss="modal"
                        >
                            &times;
                        </button>

                    </div>

                    <div class="modal-body">

                        <div class="form-group row">

                            <div class="col-md-6">

                                <label>
                                    Tipo de Material
                                    <span class="next-required" aria-label="obligatorio">*</span>
                                </label>

                                <input
                                        type="text"
                                        class="form-control"
                                        name="name"
                                        onkeyup="mayus(this);"
                                        placeholder="Ejm: Acero"
                                >

                            </div>

                            <div class="col-md-6">

                                <label>
                                    Descripción
                                </label>

                                <input
                                        type="text"
                                        class="form-control"
                                        name="description"
                                        onkeyup="mayus(this);"
                                        placeholder="Ejm: Descripción"
                                >

                            </div>

                        </div>

                        <input
                                type="hidden"
                                name="subcategory_id"
                                id="subcategory_id_hidden"
                        >

                    </div>

                    <div class="modal-footer">

                        <button
                                type="reset"
                                class="btn btn-outline-secondary"
                                data-modal-cancel
                                data-dismiss="modal"
                        >
                            Cancelar
                        </button>

                        <button
                                type="button"
                                id="btn-saveMaterialType"
                                class="btn btn-primary"
                        >
                            Guardar
                        </button>

                    </div>

                </div>

            </form>

        </div>
    </div>

    <!-- Modal Crear Subtype -->
    <div class="modal fade next-aux-modal" id="modalSubtype" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">

            <form id="formCreateSubtype" class="next-aux-modal-form" data-url="{{ url('/dashboard/subtype/store') }}">
                @csrf

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title">
                            Crear Subtipo de Material
                        </h5>

                        <button
                                type="button"
                                class="close"
                                data-dismiss="modal"
                        >
                            &times;
                        </button>

                    </div>

                    <div class="modal-body">

                        <div class="form-group row">

                            <div class="col-md-6">

                                <label>
                                    Subtipo
                                    <span class="next-required" aria-label="obligatorio">*</span>
                                </label>

                                <input
                                        type="text"
                                        class="form-control"
                                        name="name"
                                        onkeyup="mayus(this);"
                                        placeholder="Ejm: Brillante"
                                >

                            </div>

                            <div class="col-md-6">

                                <label>
                                    Descripción
                                </label>

                                <input
                                        type="text"
                                        class="form-control"
                                        name="description"
                                        onkeyup="mayus(this);"
                                        placeholder="Ejm: Descripción"
                                >

                            </div>

                        </div>

                        <input
                                type="hidden"
                                name="material_type_id"
                                id="material_type_id_hidden"
                        >

                    </div>

                    <div class="modal-footer">

                        <button
                                type="reset"
                                class="btn btn-outline-secondary"
                                data-modal-cancel
                                data-dismiss="modal"
                        >
                            Cancelar
                        </button>

                        <button
                                type="button"
                                id="btn-saveSubtype"
                                class="btn btn-primary"
                        >
                            Guardar
                        </button>

                    </div>

                </div>

            </form>

        </div>
    </div>

    <!-- Modal Crear Typescrap -->
    <div class="modal fade next-aux-modal" id="modalTypescrap" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">

            <form id="formCreateTypescrap" class="next-aux-modal-form" data-url="{{ url('/dashboard/typescrap/store') }}">
                @csrf

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title">
                            Crear Tipo de Retacería
                        </h5>

                        <button
                                type="button"
                                class="close"
                                data-dismiss="modal"
                        >
                            &times;
                        </button>

                    </div>

                    <div class="modal-body">

                        <div class="form-group row">

                            <div class="col-md-4">

                                <label>
                                    Nombre
                                    <span class="next-required" aria-label="obligatorio">*</span>
                                </label>

                                <input
                                        type="text"
                                        class="form-control"
                                        name="name"
                                        onkeyup="mayus(this);"
                                >

                            </div>

                            <div class="col-md-4">

                                <label>
                                    Ancho
                                </label>

                                <input
                                        type="number"
                                        class="form-control"
                                        name="width"
                                        value="0"
                                        min="0"
                                        step="0.01"
                                >

                            </div>

                            <div class="col-md-4">

                                <label>
                                    Largo
                                </label>

                                <input
                                        type="number"
                                        class="form-control"
                                        name="length"
                                        value="0"
                                        min="0"
                                        step="0.01"
                                >

                            </div>

                        </div>

                    </div>

                    <div class="modal-footer">

                        <button
                                type="reset"
                                class="btn btn-outline-secondary"
                                data-modal-cancel
                                data-dismiss="modal"
                        >
                            Cancelar
                        </button>

                        <button
                                type="button"
                                id="btn-saveTypescrap"
                                class="btn btn-primary"
                        >
                            Guardar
                        </button>

                    </div>

                </div>

            </form>

        </div>
    </div>

    <template id="template-variante">
        <tr class="item-variante">
            <td class="next-variant-cell next-variant-cell--identity" data-label="Variante">
                <div class="next-variant-identity">
                    <strong data-variant-size-label></strong>
                    <span data-variant-color-label></span>
                </div>
                <input type="hidden" data-talla_text>
                <input type="hidden" data-talla_id>
                <input type="hidden" data-color_text>
                <input type="hidden" data-color_id>
            </td>

            <td class="next-variant-cell next-variant-cell--sku" data-label="SKU">
                <input type="text" class="form-control form-control-sm" data-sku_sugerido aria-label="SKU de la variante">
            </td>

            <td class="next-variant-cell next-variant-cell--barcode" data-label="Código de barras">
                <input type="text" class="form-control form-control-sm" data-codigo_barras aria-label="Código de barras de la variante">
            </td>

            <td class="next-variant-cell next-variant-cell--image" data-label="Imagen">
                <label class="next-variant-file">
                    <input type="file" data-image_variante accept="image/*">
                    <span class="next-variant-file-button">
                        <i class="fas fa-paperclip" aria-hidden="true"></i> Imagen
                    </span>
                </label>
                <span class="next-variant-file-name" data-variant-file-name aria-live="polite">Sin archivo</span>
            </td>

            <td class="next-variant-cell next-variant-cell--inventory" data-label="Inventario">
                <div class="next-variant-switch">
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
            </td>

            <td class="next-variant-cell next-variant-cell--stock" data-label="Stock mín.">
                <input type="number" class="form-control form-control-sm" data-stock_minimo min="0" step="0.01" aria-label="Stock mínimo de la variante">
            </td>

            <td class="next-variant-cell next-variant-cell--stock" data-label="Stock máx.">
                <input type="number" class="form-control form-control-sm" data-stock_maximo min="0" step="0.01" aria-label="Stock máximo de la variante">
            </td>

            <td class="next-variant-cell next-variant-cell--status" data-label="Estado">
                <div class="next-variant-status">
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
            </td>

            <td class="next-variant-cell next-variant-cell--action" data-label="Acción">
                <button type="button" data-delete class="btn btn-outline-danger btn-sm next-variant-delete" title="Eliminar variante" aria-label="Eliminar variante">
                    <i class="fas fa-trash" aria-hidden="true"></i>
                </button>
            </td>
        </tr>
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
                allowClear: true,
            });
            $('#category').select2({
                placeholder: "Selecione categoría",
                allowClear: true,
            });
            $('#subcategory').select2({
                placeholder: "Selecione subcategoría",
                allowClear: true,
            });
            $('#brand').select2({
                placeholder: "Selecione una marca",
                allowClear: true,
            });

            $('#subtype').select2({
                placeholder: "Seleccione un subtipo",
                allowClear: true,
            });
            $('#unit_measure').select2({
                placeholder: "Seleccione una unidad",
                allowClear: true,
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
                placeholder: 'Seleccione tallas',
                allowClear: true
            });

            $('#color').select2({
                placeholder: 'Seleccione colores',
                allowClear: true
            });
            $('#tipo_venta').select2({
                placeholder: "Seleccione Tipo Venta",
                allowClear: true,
            });

            $('#typescrap').select2({
                placeholder: 'Seleccione tipo de retacería',
                allowClear: true
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
    <script src="{{ asset('js/material/create.js') }}?v={{ time() }}"></script>
@endsection
