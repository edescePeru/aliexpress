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

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales</span>
        <h1 class="page-title">Editar material</h1>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <div>
                <strong>Ficha del material</strong>
                <span>Material #{{ $material->id }} · Los campos marcados con (*) son obligatorios.</span>
            </div>
        </div>
        <div class="next-toolbar-actions">
            <a href="{{ route('material.indexV2') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="button" id="btn-submit" class="btn btn-primary">
                <i class="fas fa-save" aria-hidden="true"></i> Guardar cambios
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
        <li class="breadcrumb-item"><i class="fa fa-pen"></i> Editar</li>
    </ol>
@endsection

@section('content')
    <form id="formEdit" class="form-horizontal next-material-page" data-url="{{ route('material.update') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="material_id" value="{{ $material->id }}">

        @php
            $selectedBrand = old('brand', $material->brand_id);
            $selectedExampler = old('exampler', $material->exampler_id);
            $selectedCategory = old('category', $material->category_id);
            $selectedSubcategory = old('subcategory', $material->subcategory_id);
            $selectedMaterialType = old('material_type', $material->material_type_id);
            $selectedSubtype = old('subtype', $material->subtype_id);
            $selectedTypescrap = old('typescrap', $material->typescrap_id);
            $selectedGenero = old('genero', $material->genero_id);
            $selectedUnitMeasure = old('unit_measure', $material->unit_measure_id);
            $selectedTipoVenta = old('tipo_venta', $material->tipo_venta_id);
            $selectedPerecible = old('perecible', $material->perecible);
        @endphp

        @if(in_array('category', $enabled, true))
            <input type="hidden" id="category_id" value="{{ $selectedCategory }}">
        @endif

        @if(in_array('subcategory', $enabled, true))
            <input type="hidden" id="subcategory_id" value="{{ $selectedSubcategory }}">
        @endif

        @if(in_array('exampler', $enabled, true))
            <input type="hidden" id="exampler_id" value="{{ $selectedExampler }}">
        @endif

        <section class="next-form-section" aria-labelledby="material-identity-title">
            <div class="next-section-header">
                <div>
                    <span class="next-section-kicker">01</span>
                    <h2 id="material-identity-title">Identidad del material</h2>
                    <p>Actualiza la descripción comercial que identifica al material en todo el sistema.</p>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label for="description">Descripción <span class="next-required">(*)</span></label>
                        <input type="text" id="description" name="description" class="form-control" value="{{ old('description', $material->description) }}" autocomplete="off">
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
                    <div class="col-md-6 col-xl-3"><div class="form-group">
                        <label for="brand">Marca</label>
                        <div class="input-group next-field-action">
                            <select id="brand" name="brand" class="form-control select2"><option></option>@foreach($brands as $brand)<option value="{{ $brand->id }}" {{ (string) $selectedBrand === (string) $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>@endforeach</select>
                            <div class="input-group-append">@can('create_brand')<button type="button" class="btn btn-outline-primary next-field-action-button" data-toggle="modal" data-target="#modalBrand" title="Crear marca" aria-label="Crear marca"><i class="fas fa-plus" aria-hidden="true"></i></button>@endcan</div>
                        </div>
                    </div></div>
                @endif
                @if(in_array('exampler', $enabled, true))
                    <div class="col-md-6 col-xl-3"><div class="form-group">
                        <label for="exampler">Modelo</label>
                        <div class="input-group next-field-action">
                            <select id="exampler" name="exampler" class="form-control select2"><option></option>@foreach($examplers as $exampler)<option value="{{ $exampler->id }}" {{ (string) $selectedExampler === (string) $exampler->id ? 'selected' : '' }}>{{ $exampler->name }}</option>@endforeach</select>
                            <div class="input-group-append">@can('create_exampler')<button type="button" id="btn-newExampler" class="btn btn-outline-primary next-field-action-button d-none" data-toggle="modal" data-target="#modalExampler" title="Crear modelo" aria-label="Crear modelo"><i class="fas fa-plus" aria-hidden="true"></i></button>@endcan</div>
                        </div>
                    </div></div>
                @endif
                @if(in_array('category', $enabled, true))
                    <div class="col-md-6 col-xl-3"><div class="form-group">
                        <label for="category">Categoría</label>
                        <div class="input-group next-field-action">
                            <select id="category" name="category" class="form-control select2"><option></option>@foreach($categories as $category)<option value="{{ $category->id }}" {{ (string) $selectedCategory === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>@endforeach</select>
                            <div class="input-group-append">@can('create_category')<button type="button" class="btn btn-outline-primary next-field-action-button" data-toggle="modal" data-target="#modalCategoria" title="Crear categoría" aria-label="Crear categoría"><i class="fas fa-plus" aria-hidden="true"></i></button>@endcan</div>
                        </div>
                    </div></div>
                @endif
                @if(in_array('subcategory', $enabled, true))
                    <div class="col-md-6 col-xl-3"><div class="form-group">
                        <label for="subcategory">Subcategoría</label>
                        <div class="input-group next-field-action">
                            <select id="subcategory" name="subcategory" class="form-control select2"><option></option>@foreach($subcategories as $subcategory)<option value="{{ $subcategory->id }}" {{ (string) $selectedSubcategory === (string) $subcategory->id ? 'selected' : '' }}>{{ $subcategory->name }}</option>@endforeach</select>
                            <div class="input-group-append">@can('create_subcategory')<button type="button" id="btn-newSubCategoria" class="btn btn-outline-primary next-field-action-button d-none" data-toggle="modal" data-target="#modalSubCategoria" title="Crear subcategoría" aria-label="Crear subcategoría"><i class="fas fa-plus" aria-hidden="true"></i></button>@endcan</div>
                        </div>
                    </div></div>
                @endif
                @if(in_array('material_type', $enabled, true))
                    <div class="col-md-6 col-xl-3"><div class="form-group">
                        <label for="material_type">Tipo de material</label>
                        <div class="input-group next-field-action">
                            <select id="material_type" name="material_type" class="form-control select2"><option></option>@foreach($materialTypes as $materialType)<option value="{{ $materialType->id }}" {{ (string) $selectedMaterialType === (string) $materialType->id ? 'selected' : '' }}>{{ $materialType->name }}</option>@endforeach</select>
                            <div class="input-group-append">@can('create_materialType')<button type="button" id="btn-newMaterialType" class="btn btn-outline-primary next-field-action-button d-none" data-toggle="modal" data-target="#modalMaterialType" title="Crear tipo de material" aria-label="Crear tipo de material"><i class="fas fa-plus" aria-hidden="true"></i></button>@endcan</div>
                        </div>
                    </div></div>
                @endif
                @if(in_array('subtype', $enabled, true))
                    <div class="col-md-6 col-xl-3"><div class="form-group">
                        <label for="subtype">Subtipo de material</label>
                        <div class="input-group next-field-action">
                            <select id="subtype" name="subtype" class="form-control select2"><option></option>@foreach($subtypes as $subtype)<option value="{{ $subtype->id }}" {{ (string) $selectedSubtype === (string) $subtype->id ? 'selected' : '' }}>{{ $subtype->name }}</option>@endforeach</select>
                            <div class="input-group-append">@can('create_subType')<button type="button" id="btn-newSubtype" class="btn btn-outline-primary next-field-action-button d-none" data-toggle="modal" data-target="#modalSubtype" title="Crear subtipo de material" aria-label="Crear subtipo de material"><i class="fas fa-plus" aria-hidden="true"></i></button>@endcan</div>
                        </div>
                    </div></div>
                @endif
                @if(in_array('typescrap', $enabled, true))
                    <div class="col-md-6 col-xl-3"><div class="form-group">
                        <label for="typescrap">Tipo de retacería</label>
                        <div class="input-group next-field-action">
                            <select id="typescrap" name="typescrap" class="form-control select2"><option></option>@foreach($typescraps as $typescrap)<option value="{{ $typescrap->id }}" {{ (string) $selectedTypescrap === (string) $typescrap->id ? 'selected' : '' }}>{{ $typescrap->name }}</option>@endforeach</select>
                            <div class="input-group-append">@can('create_typeScrap')<button type="button" class="btn btn-outline-primary next-field-action-button" data-toggle="modal" data-target="#modalTypescrap" title="Crear tipo de retacería" aria-label="Crear tipo de retacería"><i class="fas fa-plus" aria-hidden="true"></i></button>@endcan</div>
                        </div>
                    </div></div>
                @endif
                @if(in_array('genero', $enabled, true))
                    <div class="col-md-6 col-xl-3"><div class="form-group">
                        <label for="genero">Género</label>
                        <div class="input-group next-field-action">
                            <select id="genero" name="genero" class="form-control select2"><option></option>@foreach($generos as $genero)<option value="{{ $genero->id }}" {{ (string) $selectedGenero === (string) $genero->id ? 'selected' : '' }}>{{ $genero->name }}</option>@endforeach</select>
                            <div class="input-group-append">@can('create_genero')<button type="button" class="btn btn-outline-primary next-field-action-button" data-toggle="modal" data-target="#modalGenero" title="Crear género" aria-label="Crear género"><i class="fas fa-plus" aria-hidden="true"></i></button>@endcan</div>
                        </div>
                    </div></div>
                @endif
                @if(in_array('unit_measure', $enabled, true))
                    <div class="col-md-6 col-xl-3"><div class="form-group">
                        <label for="unit_measure">Unidad de medida</label>
                        <div class="input-group next-field-action">
                            <select id="unit_measure" name="unit_measure" class="form-control select2"><option></option>@foreach($unitMeasures as $unitMeasure)<option value="{{ $unitMeasure->id }}" {{ (string) $selectedUnitMeasure === (string) $unitMeasure->id ? 'selected' : '' }}>{{ $unitMeasure->name }}</option>@endforeach</select>
                            <div class="input-group-append">@can('create_unitMeasure')<button type="button" class="btn btn-outline-primary next-field-action-button" data-toggle="modal" data-target="#modalUnitMeasure" title="Crear unidad de medida" aria-label="Crear unidad de medida"><i class="fas fa-plus" aria-hidden="true"></i></button>@endcan</div>
                        </div>
                    </div></div>
                @endif
                <div class="col-md-6 col-xl-3"><div class="form-group">
                    <label for="tipo_venta">Tipo de venta</label>
                    <select id="tipo_venta" name="tipo_venta" class="form-control select2"><option></option>@foreach($tipoVentas as $tipo)<option value="{{ $tipo->id }}" {{ (string) $selectedTipoVenta === (string) $tipo->id ? 'selected' : '' }}>{{ $tipo->description }}</option>@endforeach</select>
                </div></div>
                @if(in_array('perecible', $enabled, true))
                    <div class="col-md-6 col-xl-3"><div class="form-group">
                        <label for="perecible">Perecible</label>
                        <select id="perecible" name="perecible" class="form-control select2"><option></option><option value="s" {{ $selectedPerecible === 's' ? 'selected' : '' }}>SI</option><option value="n" {{ $selectedPerecible === 'n' ? 'selected' : '' }}>NO</option></select>
                    </div></div>
                @endif
            </div>
            <div class="row next-generated-row"><div class="col-12"><div class="form-group">
                <label for="name">Nombre completo</label>
                <div class="input-group next-field-action">
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $material->full_name) }}" readonly>
                    <div class="input-group-append"><button type="button" class="btn btn-outline-primary" id="btn-generate"><i class="fa fa-redo mr-1" aria-hidden="true"></i> Actualizar</button></div>
                </div>
                <small class="form-text text-muted">Se regenera con la descripción y la clasificación seleccionada.</small>
            </div></div></div>
        </section>

        <section class="next-form-section" aria-labelledby="material-inventory-title">
            <div class="next-section-header"><div><span class="next-section-kicker">03</span><h2 id="material-inventory-title">Inventario e imagen</h2><p>Actualiza la configuración operativa del producto y prepara un reemplazo de imagen cuando corresponda.</p></div></div>
            <div class="row next-operational-layout">
                <div class="col-xl-8"><div class="next-operational-panel">
                    <div class="next-variant-mode">
                        <div><strong>Modo de inventario</strong><span>Definido al crear el material. No puede modificarse desde edición.</span></div>
                        <div class="next-variant-options" aria-label="Modo de inventario actual">
                            <div class="icheck-primary"><input type="radio" id="sin_variantes" name="variantes" value="0" {{ !$tieneVariantes ? 'checked' : '' }} disabled><label for="sin_variantes">Sin variantes</label></div>
                            <div class="icheck-primary"><input type="radio" id="con_variantes" name="variantes" value="1" {{ $tieneVariantes ? 'checked' : '' }} disabled><label for="con_variantes">Con variantes</label></div>
                        </div>
                    </div>
                    @if(!$tieneVariantes)
                        <div id="seccion_sin_variantes" class="next-simple-inventory">
                            <input type="hidden" name="stock_item_id" id="stock_item_id">
                            <h3 class="next-subsection-title">Configuración del producto simple</h3>
                            <div class="row next-stock-grid">
                                <div class="col-md-6 col-xl-4"><div class="form-group"><label for="display_name">Nombre operativo</label><input type="text" id="display_name" name="display_name" class="form-control" readonly></div></div>
                                <div class="col-md-6 col-xl-4"><div class="form-group"><label for="sku_sin_variantes">SKU</label><input type="text" id="sku_sin_variantes" name="sku_sin_variantes" class="form-control"></div></div>
                                <div class="col-md-6 col-xl-4"><div class="form-group"><label for="codigo_sin_variantes">Código de barras</label><div class="input-group next-field-action"><input type="text" class="form-control" id="codigo_sin_variantes" name="codigo_sin_variantes"><div class="input-group-append"><button type="button" class="btn btn-outline-primary btn-generateCode" id="btn-generateCodeSinVariantes" title="Generar código" aria-label="Generar código"><i class="fas fa-random" aria-hidden="true"></i></button></div></div></div></div>
                                <div class="col-md-6 col-xl-4">
                                    <div class="form-group">
                                        <label for="inputPack">Presentación</label>
                                        <div class="next-switch-field">
                                            <div class="next-package-control">
                                                <div class="icheck-primary my-0 d-flex align-items-center">
                                                    <input type="checkbox" name="pack" id="checkboxPack" {{ (int) $material->isPack === 1 ? 'checked' : '' }}>
                                                    <label for="checkboxPack" class="mb-0">Es paquete</label>
                                                </div>
                                                <input type="number" class="form-control form-control-sm" id="inputPack" name="inputPack" value="{{ old('inputPack', $material->quantityPack) }}" min="0" aria-label="Cantidad por paquete" {{ (int) $material->isPack === 0 ? 'disabled' : '' }}>
                                            </div>
                                            <small>Indica cuántas unidades contiene el paquete.</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-4">
                                    <div class="form-group">
                                        <label for="afecto_inventario_sin_variantes">Inventariable</label>
                                        <div class="next-switch-field">
                                            <div class="next-switch-control">
                                                <input type="checkbox" name="afecto_inventario_sin_variantes" id="afecto_inventario_sin_variantes" data-tracks_inventory_sin_variantes data-bootstrap-switch data-size="normal" data-off-color="danger" data-on-text="SI" data-off-text="NO" data-on-color="success">
                                            </div>
                                            <small>Controla existencias para este StockItem.</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-4">
                                    <div class="form-group">
                                        <label for="is_active_sin_variante">Estado</label>
                                        <div class="next-switch-field">
                                            <div class="next-switch-control">
                                                <input type="checkbox" name="is_active_sin_variante" id="is_active_sin_variante" data-is_active_sin_variante data-bootstrap-switch data-size="normal" data-off-color="danger" data-on-text="SI" data-off-text="NO" data-on-color="success">
                                            </div>
                                            <small>Disponibilidad operativa del StockItem.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div></div>
                <div class="col-xl-4"><div class="next-image-panel">
                    <div class="next-image-heading"><strong>Imagen del material</strong><span>La imagen actual se conserva mientras no selecciones un archivo nuevo.</span></div>
                    <div class="d-flex align-items-center mb-3"><img src="{{ asset('images/material/'.$material->image) }}" width="112" height="112" class="img-thumbnail" alt="Imagen actual de {{ $material->description }}"><div class="ml-3"><strong class="d-block">Imagen actual</strong><small class="text-muted">{{ $material->image }}</small></div></div>
                    <div class="dropzone" id="image-dropzone"></div>
                    <p class="next-image-help">Seleccionar un archivo nuevo reemplazaría la imagen al guardar. Esta pantalla no ofrece eliminación porque el backend no tiene ese contrato.</p>
                </div></div>
            </div>
        </section>

        <section class="next-form-section next-variants-section" aria-labelledby="material-section-four-title">
            @if(!$tieneVariantes)
                <div class="next-section-header"><div><span class="next-section-kicker">04</span><h2 id="material-section-four-title">Inventario por ubicación</h2><p>Consulta existencias y costos por almacén; ajusta únicamente los límites mínimo y máximo.</p></div></div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0" aria-describedby="material-section-four-title">
                        <thead><tr><th>Almacén</th><th>Ubicación</th><th class="text-center">Stock actual</th><th class="text-center">Reservado</th><th class="text-center">Stock mínimo</th><th class="text-center">Stock máximo</th><th class="text-right">Costo promedio</th><th class="text-right">Último costo</th></tr></thead>
                        <tbody id="tbody-inventory-levels-single"></tbody>
                    </table>
                </div>
            @else
                <div class="next-section-header"><div><span class="next-section-kicker">04</span><h2 id="material-section-four-title">Configuración de variantes</h2><p>Revisa las variantes persistidas o genera combinaciones nuevas sin eliminar el historial existente.</p></div></div>
                <div id="seccion_con_variantes">
                    <div data-multiple-variant-content>
                    <div class="row next-variants-toolbar">
                        @if(in_array('talla', $enabled, true))
                            <div class="col-lg-5"><div class="form-group"><label for="talla">Talla</label><div class="input-group next-field-action"><select id="talla" name="talla[]" class="form-control select2" multiple="multiple">@foreach($tallas as $talla)<option value="{{ $talla->id }}" data-short-name="{{ $talla->short_name }}">{{ $talla->name }}</option>@endforeach</select><div class="input-group-append">@can('create_talla')<button type="button" class="btn btn-outline-primary next-field-action-button" data-toggle="modal" data-target="#modalTalla" title="Crear talla" aria-label="Crear talla"><i class="fas fa-plus" aria-hidden="true"></i></button>@endcan</div></div></div></div>
                        @endif
                        @if(in_array('color', $enabled, true))
                            <div class="col-lg-5"><div class="form-group"><label for="color">Color</label><div class="input-group next-field-action"><select id="color" name="color[]" class="form-control select2" multiple="multiple">@foreach($colors as $color)<option value="{{ $color->id }}" data-short-name="{{ $color->short_name }}">{{ $color->name }}</option>@endforeach</select><div class="input-group-append">@can('create_color')<button type="button" class="btn btn-outline-primary next-field-action-button" data-toggle="modal" data-target="#modalColor" title="Crear color" aria-label="Crear color"><i class="fas fa-plus" aria-hidden="true"></i></button>@endcan</div></div></div></div>
                        @endif
                        <div class="col-lg-2 d-flex align-items-end"><button type="button" id="btn-generate_variantes" class="btn btn-outline-primary btn-block mb-3 next-variants-generate"><i class="fas fa-layer-group mr-1" aria-hidden="true"></i> Generar</button></div>
                    </div>
                        <div class="next-variants-results" aria-live="polite"><table class="table next-variants-table" aria-describedby="material-section-four-title"><colgroup><col class="next-variant-col next-variant-col--identity"><col class="next-variant-col next-variant-col--sku"><col class="next-variant-col next-variant-col--barcode"><col class="next-variant-col next-variant-col--image"><col class="next-variant-col next-variant-col--inventory"><col class="next-variant-col next-variant-col--stock"><col class="next-variant-col next-variant-col--status"><col class="next-variant-col next-variant-col--action"></colgroup><thead><tr><th>Variante</th><th>SKU</th><th>Código de barras</th><th>Imagen</th><th class="text-center">Inventariable</th><th class="text-center">Stock total</th><th class="text-center">Estado</th><th class="text-center">Inventario</th></tr></thead><tbody id="body-variantes" class="next-variants-body"></tbody></table></div>
                    </div>
                </div>
            @endif
        </section>
    </form>

    <!-- Modal Crear Unidad de Medida -->
    <div class="modal fade next-aux-modal" id="modalUnitMeasure" tabindex="-1" role="dialog" aria-labelledby="modalUnitMeasureLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUnitMeasureLabel">Crear Unidad de Medida</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formCreateUnitMeasure" class="next-aux-modal-form" data-url="{{ route('unitmeasure.store') }}" enctype="multipart/form-data">
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

                        <div class="modal-footer">
                            <button type="button" id="btnSaveUnitMeasure" class="btn btn-primary">Guardar</button>
                            <button type="reset" class="btn btn-outline-secondary" data-modal-cancel data-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Marca -->
    <div class="modal fade next-aux-modal" id="modalBrand" tabindex="-1" role="dialog" aria-labelledby="modalBrandLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalBrandLabel">Nueva Marca</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formCreateBrand" class="form-horizontal next-aux-modal-form" data-url="{{ route('brand.store') }}" enctype="multipart/form-data">
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

                        <div class="modal-footer">
                            <button type="button" id="btn-saveBrand" class="btn btn-primary">Guardar</button>
                            <button type="reset" class="btn btn-outline-secondary" data-modal-cancel data-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Modelo -->
    <div class="modal fade next-aux-modal" id="modalExampler" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <form id="formCreateExampler" class="next-aux-modal-form" data-url="{{ route('exampler.store') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
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

                    <div class="modal-footer">
                        <button type="button" id="btn-saveExampler" class="btn btn-primary">Guardar</button>
                            <button type="reset" class="btn btn-outline-secondary" data-modal-cancel data-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Crear Genero -->
    <div class="modal fade next-aux-modal" id="modalGenero" tabindex="-1" role="dialog" aria-labelledby="modalGeneroLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalGeneroLabel">Crear Género</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formCreateGenero" class="next-aux-modal-form" data-url="{{ route('genero.store') }}" enctype="multipart/form-data">
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

                        <div class="modal-footer">
                            <button type="button" id="btnSaveGenero" class="btn btn-primary">Guardar</button>
                            <button type="reset" class="btn btn-outline-secondary" data-modal-cancel data-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Talla -->
    <div class="modal fade next-aux-modal" id="modalTalla" tabindex="-1" role="dialog" aria-labelledby="modalTallaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTallaLabel">Crear Talla</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formCreateTalla" class="next-aux-modal-form" data-url="{{ route('talla.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-md-5">
                                <label for="modalTallaName">Talla <span class="next-required" aria-label="obligatorio">*</span></label>
                                <input type="text" id="modalTallaName" class="form-control" name="name" onkeyup="mayus(this);" maxlength="191" placeholder="Ejm: Talla" required data-modal-autofocus>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="modalTallaShortName">Nombre corto</label>
                                <input type="text" id="modalTallaShortName" class="form-control" name="short_name" onkeyup="mayus(this);" maxlength="191" placeholder="Ejm: M">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="modalTallaDescription">Descripción</label>
                                <input type="text" id="modalTallaDescription" class="form-control" name="description" onkeyup="mayus(this);" maxlength="255" placeholder="Ejm: Descripción">
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" id="btnSaveTalla" class="btn btn-primary">Guardar</button>
                            <button type="reset" class="btn btn-outline-secondary" data-modal-cancel data-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Categoria -->
    <div class="modal fade next-aux-modal" id="modalCategoria" tabindex="-1" role="dialog" aria-labelledby="modalCategoriaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCategoriaLabel">Crear Categoría</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formCreateCategoria" class="next-aux-modal-form" data-url="{{ route('category.store') }}" enctype="multipart/form-data">
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

                        <div class="modal-footer">
                            <button type="button" id="btnSaveCategoria" class="btn btn-primary">Guardar</button>
                            <button type="reset" class="btn btn-outline-secondary" data-modal-cancel data-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear SubCategoria -->
    <div class="modal fade next-aux-modal" id="modalSubCategoria" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <form id="formCreateSubCategoria" class="next-aux-modal-form" data-url="{{ route('subcategory.store.individual') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
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

                    <div class="modal-footer">
                        <button type="button" id="btn-saveSubCategoria" class="btn btn-primary">Guardar</button>
                            <button type="reset" class="btn btn-outline-secondary" data-modal-cancel data-dismiss="modal">Cancelar</button>
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
                                <label for="modalEditColorName">Color <span class="next-required" aria-label="obligatorio">*</span></label>
                                <input type="text" id="modalEditColorName" class="form-control" name="name" maxlength="255" placeholder="Ejm: Blanco" required aria-required="true" data-modal-autofocus>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="modalEditColorCode">Código HEX</label>
                                <input type="text" id="modalEditColorCode" class="form-control" name="code" maxlength="7" onkeyup="mayus(this);" placeholder="Ejm: #000000">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="modalEditColorShortName">Nombre clave <span class="next-required" aria-label="obligatorio">*</span></label>
                                <input type="text" id="modalEditColorShortName" class="form-control" name="short_name" maxlength="255" onkeyup="mayus(this);" placeholder="Ejm: BLA" required aria-required="true">
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
                                    <span class="badge badge-danger">(*)</span>
                                </label>

                                <input
                                        type="text"
                                        class="form-control"
                                        name="name"
                                        onkeyup="mayus(this);"
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
                                type="button"
                                id="btn-saveMaterialType"
                                class="btn btn-primary"
                        >
                            Guardar
                        </button>

                        <button
                                type="reset"
                                class="btn btn-outline-secondary"
                                data-modal-cancel
                                data-dismiss="modal"
                        >
                            Cancelar
                        </button>

                    </div>

                </div>

            </form>

        </div>
    </div>

    <!-- Modal Crear MaterialType -->
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
                                    <span class="badge badge-danger">(*)</span>
                                </label>

                                <input
                                        type="text"
                                        class="form-control"
                                        name="name"
                                        onkeyup="mayus(this);"
                                >
                            </div>

                            <div class="col-md-6">
                                <label>Descripción</label>

                                <input
                                        type="text"
                                        class="form-control"
                                        name="description"
                                        onkeyup="mayus(this);"
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
                                type="button"
                                id="btn-saveSubtype"
                                class="btn btn-primary"
                        >
                            Guardar
                        </button>

                        <button
                                type="reset"
                                class="btn btn-outline-secondary"
                                data-modal-cancel
                                data-dismiss="modal"
                        >
                            Cancelar
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
                                    <span class="badge badge-danger">(*)</span>
                                </label>

                                <input
                                        type="text"
                                        class="form-control"
                                        name="name"
                                        onkeyup="mayus(this);"
                                >
                            </div>

                            <div class="col-md-4">
                                <label>Ancho</label>

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
                                <label>Largo</label>

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
                                type="button"
                                id="btn-saveTypescrap"
                                class="btn btn-primary"
                        >
                            Guardar
                        </button>

                        <button
                                type="reset"
                                class="btn btn-outline-secondary"
                                data-modal-cancel
                                data-dismiss="modal"
                        >
                            Cancelar
                        </button>

                    </div>

                </div>

            </form>

        </div>
    </div>

    <template id="template-variante">
        <tr class="item-variante">
            <td><input type="hidden" data-variant_id><strong data-variant-size-label></strong><span class="text-muted"> / </span><span data-variant-color-label></span><input type="hidden" data-talla_text><input type="hidden" data-talla_id><input type="hidden" data-color_text><input type="hidden" data-color_id></td>
            <td><input type="text" class="form-control form-control-sm" data-sku_sugerido aria-label="SKU de variante"></td>
            <td><input type="text" class="form-control form-control-sm" data-codigo_barras aria-label="Código de barras de variante"></td>
            <td><input type="file" class="form-control-file form-control-sm" data-image_variante aria-label="Imagen de variante"><small class="text-muted d-block mt-1" data-variant-file-name></small></td>
            <td class="text-center"><input type="checkbox" data-afecto_inventario_variante data-bootstrap-switch data-size="normal" data-off-color="danger" data-on-text="SI" data-off-text="NO" data-on-color="success" checked></td>
            <td class="text-center"><input type="number" class="form-control form-control-sm text-center" data-stock_total readonly aria-label="Stock total de variante"></td>
            <td class="text-center"><input type="checkbox" data-is_active_variante data-bootstrap-switch data-size="normal" data-off-color="danger" data-on-text="SI" data-off-text="NO" data-on-color="success" checked></td>
            <td class="text-center"><button type="button" class="btn btn-outline-info btn-sm" data-toggle_inventory_levels aria-expanded="false"><i class="fas fa-warehouse mr-1" aria-hidden="true"></i> Ver</button></td>
        </tr>
        <tr class="next-variant-inventory-row d-none" data-inventory_levels_wrapper>
            <td colspan="8">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead><tr><th>Almacén</th><th>Ubicación</th><th class="text-center">Stock actual</th><th class="text-center">Reservado</th><th class="text-center">Stock mínimo</th><th class="text-center">Stock máximo</th><th class="text-right">Costo prom.</th><th class="text-right">Últ. costo</th></tr></thead>
                        <tbody data-inventory_levels_body></tbody>
                    </table>
                </div>
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
                placeholder:
                    'Seleccione tipo de material',
                allowClear:
                    true
            });

            $('#subtype').select2({
                placeholder:
                    'Seleccione subtipo',
                allowClear:
                    true
            });

            $('#typescrap').select2({
                placeholder:
                    'Seleccione tipo de retacería',
                allowClear:
                    true
            });

            $('#category').select2({
                placeholder:
                    'Seleccione categoría',
                allowClear:
                    true
            });

            $('#subcategory').select2({
                placeholder:
                    'Seleccione subcategoría',
                allowClear:
                    true
            });

            $('#brand').select2({
                placeholder:
                    'Seleccione una marca',
                allowClear:
                    true
            });

            $('#exampler').select2({
                placeholder:
                    'Seleccione un modelo',
                allowClear:
                    true
            });

            $('#unit_measure').select2({
                placeholder:
                    'Seleccione una unidad',
                allowClear:
                    true
            });

            $('#perecible').select2({
                placeholder:
                    'Seleccione',
                allowClear:
                    true
            });

            $('#genero').select2({
                placeholder:
                    'Seleccione género',
                allowClear:
                    true
            });

            $('#talla').select2({
                placeholder:
                    'Seleccione tallas',
                allowClear:
                    true
            });

            $('#color').select2({
                placeholder:
                    'Seleccione colores',
                allowClear:
                    true
            });

            $('#tipo_venta').select2({
                placeholder:
                    'Seleccione Tipo Venta',
                allowClear:
                    true
            });


            $("input[data-bootstrap-switch]")
                .each(function () {

                    $(this)
                        .bootstrapSwitch();
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
    </script>

    <script src="{{ asset('js/material/edit.js') }}?v={{ time() }}"></script>
@endsection
