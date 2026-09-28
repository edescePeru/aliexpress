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
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.standalone.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.standalone.css') }}">

@endsection

@section('styles')
@endsection

@section('page-header')
    <h1 class="page-title">Materiales en Almacén</h1>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>Inventario de materiales</strong>
            <span>Consulta, filtra y administra el catálogo operativo.</span>
        </div>
        <div class="next-toolbar-actions">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-resumen-stock">
                <i class="fas fa-chart-bar mr-1" aria-hidden="true"></i> Ver resumen de stock
            </button>
            @can('create_material')
                <a href="{{ route('material.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus mr-1" aria-hidden="true"></i> Nuevo material
                </a>
            @endcan
        </div>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-archive"></i> Materiales </li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">
    <input type="hidden" id="hay-alertas" value="{{ $hayAlertas ? '1' : '0' }}">
    <section class="next-operational-list next-advanced-operational-list next-material-list" aria-label="Listado de materiales">
        <form action="#" class="next-list-toolbar">
            <div class="next-list-search-row">
                <div class="input-group next-list-search-control">
                    <input type="text" id="description" class="form-control" placeholder="Buscar por descripción del material" autocomplete="off" aria-label="Buscar material por descripción">
                    <div class="input-group-append">
                        <button class="btn btn-primary next-list-search" type="button" id="btn-search">
                            <i class="fas fa-search mr-1" aria-hidden="true"></i> Buscar
                        </button>
                    </div>
                </div>

                <button type="button" id="btnBusquedaAvanzada" class="btn btn-link next-list-advanced-toggle" aria-controls="material-advanced-filters" aria-expanded="false">
                    <i class="fas fa-sliders-h" aria-hidden="true"></i>
                    <span>Búsqueda avanzada</span>
                </button>

                <div class="dropdown next-column-visibility">
                    <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle next-column-visibility-trigger" id="material-columns-trigger" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-columns mr-1" aria-hidden="true"></i> Columnas
                    </button>
                    <div class="dropdown-menu dropdown-menu-right next-column-visibility-menu" aria-labelledby="material-columns-trigger">
                        <div class="next-column-visibility-header">
                            <strong>Columnas visibles</strong>
                            <span>Personaliza esta tabla</span>
                        </div>
                        <div class="next-column-visibility-grid">
                            @php
                                $materialColumns = [
                                    ['columnCodigo', 'codigo', 'Código', true],
                                    ['columnDescripcion', 'descripcion', 'Descripción', true],
                                    ['columnUnidadMedida', 'unidad_medida', 'Unidad de medida', in_array('unit_measure', $enabled, true)],
                                    ['columnStockActual', 'stock_actual', 'Stock actual', true],
                                    ['columnStockMin', 'stock_min', 'Stock mínimo', true],
                                    ['columnStockMax', 'stock_max', 'Stock máximo', true],
                                    ['columnCategoria', 'categoria', 'Categoría', in_array('category', $enabled, true)],
                                    ['columnSubcategoria', 'sub_categoria', 'Subcategoría', in_array('subcategory', $enabled, true)],
                                    ['columnMarca', 'marca', 'Marca', in_array('brand', $enabled, true)],
                                    ['columnModelo', 'modelo', 'Modelo', in_array('exampler', $enabled, true)],
                                    ['columnTipoMaterial', 'tipo_material', 'Tipo material', in_array('material_type', $enabled, true)],
                                    ['columnSubtipo', 'subtipo', 'Subtipo', in_array('subtype', $enabled, true)],
                                    ['columnRetaceria', 'retaceria', 'Tipo retacería', in_array('typescrap', $enabled, true)],
                                    ['columnImagen', 'imagen', 'Imagen', true],
                                    ['columnRotation', 'rotation', 'Rotación', true],
                                ];
                            @endphp
                            @foreach ($materialColumns as [$switchId, $column, $label, $checked])
                                <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                                    <input type="checkbox" {{ $checked ? 'checked' : '' }} data-column="{{ $column }}" class="custom-control-input column-toggle" id="{{ $switchId }}">
                                    <label class="custom-control-label" for="{{ $switchId }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="busqueda-avanzada" id="material-advanced-filters">
                <div class="next-list-filters-header">
                    <div>
                        <strong>Filtros avanzados</strong>
                        <span>Acota el catálogo por clasificación, código o tipo de material.</span>
                    </div>
                </div>
                <div class="row next-list-filters-grid">
                    @if(in_array('category', $enabled, true))
                        <div class="col-lg-3 col-md-6 next-list-filter">
                            <label for="category">Categoría</label>
                            <select id="category" class="form-control form-control-sm select2">
                                <option value="">TODOS</option>
                                @for ($i=0; $i<count($arrayCategories); $i++)
                                    <option value="{{ $arrayCategories[$i]['id'] }}">{{ $arrayCategories[$i]['name'] }}</option>
                                @endfor
                            </select>
                        </div>
                    @endif
                    @if(in_array('subcategory', $enabled, true))
                        <div class="col-lg-3 col-md-6 next-list-filter">
                            <label for="subcategory">Subcategoría</label>
                            <select id="subcategory" name="subcategory" class="form-control form-control-sm select2">
                                <option value="">TODOS</option>
                            </select>
                        </div>
                    @endif
                    @if(in_array('material_type', $enabled, true))
                        <div class="col-lg-3 col-md-6 next-list-filter">
                            <label for="material_type">Tipo de material</label>
                            <select id="material_type" name="material_type" class="form-control form-control-sm select2">
                                <option value="">TODOS</option>
                            </select>
                        </div>
                    @endif
                    @if(in_array('subtype', $enabled, true))
                        <div class="col-lg-3 col-md-6 next-list-filter">
                            <label for="sub_type">Subtipo</label>
                            <select id="sub_type" name="sub_type" class="form-control form-control-sm select2">
                                <option value="">TODOS</option>
                            </select>
                        </div>
                    @endif
                    <div class="col-lg-3 col-md-6 next-list-filter">
                        <label for="rotation">Rotación</label>
                        <select id="rotation" class="form-control form-control-sm select2">
                            <option value="">TODOS</option>
                            @for ($i=0; $i<count($arrayRotations); $i++)
                                <option value="{{ $arrayRotations[$i]['value'] }}">{{ $arrayRotations[$i]['display'] }}</option>
                            @endfor
                        </select>
                    </div>
                    @if(in_array('brand', $enabled, true))
                        <div class="col-lg-3 col-md-6 next-list-filter">
                            <label for="marca">Marca</label>
                            <select id="marca" name="marca" class="form-control form-control-sm select2">
                                <option value="">TODOS</option>
                                @for ($i=0; $i<count($arrayMarcas); $i++)
                                    <option value="{{ $arrayMarcas[$i]['id'] }}">{{ $arrayMarcas[$i]['name'] }}</option>
                                @endfor
                            </select>
                        </div>
                    @endif
                    @if(in_array('typescrap', $enabled, true))
                        <div class="col-lg-3 col-md-6 next-list-filter">
                            <label for="retaceria">Retacería</label>
                            <select id="retaceria" name="retaceria" class="form-control form-control-sm select2">
                                <option value="">TODOS</option>
                                @for ($i=0; $i<count($arrayRetacerias); $i++)
                                    <option value="{{ $arrayRetacerias[$i]['id'] }}">{{ $arrayRetacerias[$i]['name'] }}</option>
                                @endfor
                            </select>
                        </div>
                    @endif
                    <div class="col-lg-3 col-md-6 next-list-filter">
                        <label for="code">Código</label>
                        <input type="text" id="code" class="form-control form-control-sm" placeholder="Ej. 791" autocomplete="off">
                    </div>
                    <div class="col-lg-3 col-md-6 next-list-filter">
                        <label for="isPack">Es paquete</label>
                        <select id="isPack" name="isPack" class="form-control form-control-sm select2">
                            <option value="">TODOS</option>
                            <option value="0">No</option>
                            <option value="1">Sí</option>
                        </select>
                    </div>
                </div>
            </div>
        </form>

        <div class="next-list-summary">
            <div class="next-list-summary-copy">
                <strong class="next-list-count" id="numberItems"></strong>
                <span>materiales encontrados</span>
            </div>
            <span class="next-list-sort-context">
                <i class="fas fa-sort-amount-down" aria-hidden="true"></i> Por fecha de creación
            </span>
        </div>

        <div class="next-list-content">
            <div class="table-responsive" tabindex="0" aria-label="Tabla de materiales; desplázate horizontalmente para ver todas las columnas">
                <table class="table table-bordered table-hover table-sm next-data-table next-materials-table">
                    <thead id="header-table"></thead>
                    <tbody id="body-table"></tbody>
                </table>
            </div>
        </div>

        <nav class="next-list-pagination" aria-label="Paginación de materiales">
            <div id="textPagination"></div>
            <ul class="pagination" id="pagination"></ul>
        </nav>
    </section>

    <template id="item-header">
        <tr>
            <th class="text-center" data-column="codigo" data-codigo>Código</th>
            <th class="text-left" data-column="descripcion" data-descripcion>Descripcion</th>
            <th class="text-center" data-column="unidad_medida" data-unidad_medida>Unidad Medida</th>
            <th class="text-center" data-column="stock_actual" data-stock_actual>Stock Actual</th>
            <th class="text-center" data-column="stock_min" data-stock_min>Stock Minimo</th>
            <th class="text-center" data-column="stock_max" data-stock_max>Stock Maximo</th>
            {{--<th data-column="precio_unitario" data-precio_unitario>Precio Costo</th>
            <th data-column="precio_lista" data-precio_lista>Precio Venta</th>--}}
            <th class="text-left" data-column="categoria" data-categoria>Categoría</th>
            <th class="text-left" data-column="sub_categoria" data-sub_categoria>SubCategoría</th>
            <th class="text-left" data-column="marca" data-marca>Marca</th>
            <th class="text-left" data-column="modelo" data-modelo>Modelo</th>
            <th class="text-left" data-column="tipo_material" data-tipo_material>Tipo material</th>
            <th class="text-left" data-column="subtipo" data-subtipo>Subtipo</th>
            <th class="text-left" data-column="retaceria" data-retaceria>Tipo retacería</th>
            <th class="text-center" data-column="imagen" data-imagen>Imagen</th>
            <th class="text-center" data-column="rotation" data-rotation>Rotación</th>
            <th class="text-center" data-buttons>Acciones</th>
        </tr>
    </template>

    <template id="previous-page">
        <li class="page-item previous">
            <a href="#" class="page-link" data-item>
                <!--<i class="previous"></i>-->
                <i class="fas fa-chevron-left"></i>
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
            <a href="#" class="page-link" data-item>
                <!--<i class="next"></i>-->
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    </template>

    <template id="disabled-page">
        <li class="page-item disabled">
            <span class="page-link">...</span>
        </li>
    </template>

    <template id="item-table">
        <tr>
            <td class="text-center" data-column="codigo" data-codigo></td>
            <td class="text-left" data-column="descripcion" data-descripcion></td>
            <td class="text-center" data-column="unidad_medida" data-unidad_medida></td>
            <td class="text-center" data-column="stock_actual" data-stock_actual></td>
            <td class="text-center" data-column="stock_min" data-stock_min></td>
            <td class="text-center" data-column="stock_max" data-stock_max></td>
            {{--<td data-column="precio_unitario" data-precio_unitario></td>
            <td data-column="precio_lista" data-precio_lista></td>--}}
            <td class="text-left" data-column="categoria" data-categoria></td>
            <td class="text-left" data-column="sub_categoria" data-sub_categoria></td>
            <td class="text-left" data-column="marca" data-marca></td>
            <td class="text-left" data-column="modelo" data-modelo></td>
            <td class="text-left" data-column="tipo_material" data-tipo_material></td>
            <td class="text-left" data-column="subtipo" data-subtipo></td>
            <td class="text-left" data-column="retaceria" data-retaceria></td>
            <td class="text-center" data-column="imagen" data-imagen>
                <button type="button" data-ver_imagen data-src="{{--'+document.location.origin+ '/images/material/'+item.image+'--}}" data-image="{{--'+item.id+'--}}" class="btn btn-outline-secondary btn-sm next-table-cell-action" data-toggle="tooltip" data-placement="top" title="Ver imagen" aria-label="Ver imagen del material"><i class="fa fa-image" aria-hidden="true"></i></button>
            </td>
            <td class="text-center" data-column="rotation" data-rotation></td>
            <td class="text-center" data-buttons>
                <div class="dropdown next-row-actions">
                    <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle next-row-actions-trigger" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false" aria-label="Abrir acciones del material">
                        <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right next-row-actions-menu">
                        <a data-editar_material href="{{--'+document.location.origin+ '/dashboard/editar/material/'+item.id+'--}}" class="dropdown-item">
                            <i class="fa fa-pen next-row-action-item-icon" aria-hidden="true"></i><span>Editar material</span>
                        </a>
                        <a data-ver_variants href="{{--'+document.location.origin+ '/dashboard/view/material/items/'+item.id+'--}}" class="dropdown-item">
                            <i class="fa fa-layer-group next-row-action-item-icon" aria-hidden="true"></i><span>Ver variantes</span>
                        </a>
                        <button type="button" data-precioDirecto data-material="{{--'+item.id+'--}}" data-description="{{--'+item.full_description+'--}}" class="dropdown-item">
                            <i class="fas fa-tag next-row-action-item-icon" aria-hidden="true"></i><span>Gestionar precios</span>
                        </button>
                        <button type="button" data-show_vencimiento data-material="" data-description="" class="dropdown-item">
                            <i class="fas fa-calendar-alt next-row-action-item-icon" aria-hidden="true"></i><span>Ver vencimientos</span>
                        </button>
                        <button type="button" data-manage_presentations data-material="" data-description="" class="dropdown-item">
                            <i class="fas fa-cubes next-row-action-item-icon" aria-hidden="true"></i><span>Configurar presentaciones</span>
                        </button>
                        <button type="button" data-assign_child data-material="" data-description="" class="dropdown-item">
                            <i class="fas fa-boxes next-row-action-item-icon" aria-hidden="true"></i><span>Asignar productos hijos</span>
                        </button>
                        <button type="button" data-separate data-material="" data-quantity data-description="" data-measure="" class="dropdown-item">
                            <i class="far fa-object-ungroup next-row-action-item-icon" aria-hidden="true"></i><span>Separar paquete</span>
                        </button>
                        <button type="button" data-deshabilitar data-delete="{{--'+item.id+'--}}" data-description="{{--'+item.full_description+'--}}" data-measure="{{--'+item.measure+'--}}" class="dropdown-item next-row-action-danger">
                            <i class="fas fa-bell-slash next-row-action-item-icon" aria-hidden="true"></i><span>Deshabilitar material</span>
                        </button>
                    </div>
                </div>
            </td>
        </tr>
    </template>

    <template id="item-table-empty">
        <tr>
            <td colspan="1" class="next-table-empty">No se ha encontrado ningún material.</td>
        </tr>
    </template>

    <div id="modalPrecioDirecto" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Gestión de Precios</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form id="formPrecioDirecto" data-url="{{ route('material.manage.price') }}">
                    @csrf

                    <div class="modal-body">
                        <input type="hidden" id="material_id" name="material_id">
                        <input type="hidden" id="price_mode" name="price_mode" value="legacy">

                        <p id="descriptionMaterialPrice" class="font-weight-bold mb-3"></p>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="material_priceBase">
                                        Precio Costo:
                                        <span class="right badge badge-danger">(*)</span>
                                    </label>
                                    <input
                                            type="number"
                                            id="material_priceBase"
                                            step="0.01"
                                            name="material_priceBase"
                                            class="form-control"
                                            required
                                            min="0"
                                            readonly
                                    >
                                    <small class="text-muted">
                                        Este valor se calcula automáticamente en base al costo promedio.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div id="legacy-price-container">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="material_priceList">
                                            Precio Tienda:
                                            <span class="right badge badge-danger">(*)</span>
                                        </label>
                                        <input
                                                type="number"
                                                id="material_priceList"
                                                step="0.01"
                                                name="material_priceList"
                                                class="form-control"
                                                min="0"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="stock-items-price-container" style="display: none;">
                            <div class="alert alert-info py-2 mb-3">
                                Configure el precio de tienda por cada uno.
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-hover">
                                    <thead>
                                    <tr>
                                        <th>Stock Item</th>
                                        <th>Variante</th>
                                        <th>SKU</th>
                                        <th>Código de barras</th>
                                        <th width="160">Precio Costo</th>
                                        <th width="160">Precio Tienda</th>
                                    </tr>
                                    </thead>
                                    <tbody id="tbody-modal-price-list">
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No hay datos cargados.
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="button" id="btn-submit_priceList" class="btn btn-success">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modalPrecioPercentage" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Confirmar precio porcentaje</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="formPrecioPorcentaje" data-url="{{ route('material.set.price.porcentaje') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="material_id" name="material_id">
                        <p>¿Está seguro de colocar el precio por porcentaje?</p>
                        <p id="descriptionDelete"></p>
                        <div class="form-group">
                            <label for="material_pricePercentage">Precio Porcentaje (%): <span class="right badge badge-danger">(*)</span></label>
                            <input type="number" id="material_pricePercentage" step="0.01" name="material_pricePercentage" class="form-control" required min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btn-submit_pricePercentage" class="btn btn-success">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modalAssignChild" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Asignar Productos Hijos</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="formAssignChild" data-url="{{--{{ route('save.assign.child') }}--}}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="material_id" name="material_id">
                        <strong id="name_material"></strong>
                        <br>
                        <p>Listado de productos hijos</p>

                        <div class="row">
                            <div class="col-md-10">
                                <div class="form-group">
                                    <label for="material">Seleccione el material <span class="right badge badge-danger">(*)</span></label>
                                    <select id="material" name="material" class="form-control select2" style="width: 100%;">
                                        <option></option>
                                        @for( $i=0; $i<count($arrayMaterials); $i++ )
                                            <option value="{{ $arrayMaterials[$i]['id'] }}">{{ $arrayMaterials[$i]['full_name'] }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="material">&nbsp;&nbsp;&nbsp;&nbsp;</label><br>
                                    <button type="button" class="btn btn-outline-success" id="btn-submitAssignChild"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead class="thead-dark">
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Producto</th>
                                            <th scope="col"></th>
                                        </tr>
                                        </thead>
                                        <tbody id="body-childs">
                                        <tr>
                                            <th scope="row">1</th>
                                            <td>Mark</td>
                                            <td>
                                                <button type="button" class="btn btn-outline-danger btn-block"><i class="fas fa-trash-alt"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">2</th>
                                            <td>Jacob</td>
                                            <td>
                                                <button type="button" class="btn btn-outline-danger btn-block"><i class="fas fa-trash-alt"></i></button>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modalSeparate" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Confirmar separación</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="formSeparate" data-url="{{ route('save.separate.pack') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="material_id" name="material_id">
                        <strong id="name_material"></strong>
                        <br>
                        <p>¿Cuántos paquetes necesitas separar</p>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="stock_max">Cantidad Total </label>
                                    <input type="number" id="packs_total" name="packs_total" class="form-control" placeholder="0.00" min="0" value="0" step="1" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="stock_max">Cantidad a separar <span class="right badge badge-danger">(*)</span></label>
                                    <input type="number" id="packs_separate" name="packs_separate" class="form-control" placeholder="0.00" min="0" value="0" step="1">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="materialChild">Seleccione el material <span class="right badge badge-danger">(*)</span></label>
                                    <select id="materialChild" name="materialChild" class="form-control select2" style="width: 100%;">
                                        <option></option>
                                        @for( $i=0; $i<count($arrayMaterials); $i++ )
                                            <option value="{{ $arrayMaterials[$i]['id'] }}">{{ $arrayMaterials[$i]['full_name'] }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btn-submitSeparate" class="btn btn-success">Separar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para ver vencimientos -->
    <div class="modal fade" id="modalPresentaciones" tabindex="-1" role="dialog" aria-labelledby="modalPresentacionesLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPresentacionesLabel">
                        Gestionar presentaciones <span class="text-muted" id="mp-material-title"></span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="presentaciones-content">
                        <!-- Aquí se llenarán las fechas -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver vencimientos -->
    <div class="modal fade" id="modalVencimientos" tabindex="-1" role="dialog" aria-labelledby="modalVencimientosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVencimientosLabel">Fechas de Vencimiento</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="vencimientos-content" class="list-group">
                        <!-- Aquí se llenarán las fechas -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('enable_material')
        <div id="modalDelete" class="modal fade" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Confirmar inhabilitación</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <form id="formDelete" data-url="{{ route('material.disable') }}">
                        @csrf
                        <div class="modal-body">
                            <input type="hidden" id="material_id" name="material_id">
                            <p>¿Está seguro de inhabilitar este material? Ya no se mostrará en los listados</p>
                            <p id="descriptionDelete"></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">Inhabilitar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    <div id="modalImage" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Visualización de la imagen</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <img id="image-document" src="" alt="" width="80%">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <div id="resumen-stock-html" class="d-none">
        @include('material._resumen_popup', ['rows' => $rows])
    </div>

    <div class="modal fade" id="modalInventoryLevels" tabindex="-1" role="dialog" aria-labelledby="modalInventoryLevelsLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalInventoryLevelsLabel">Inventario por almacén</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="modal_stock_item_id">

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Almacén</th>
                                <th>Ubicación</th>
                                <th>Stock actual</th>
                                <th>Reservado</th>
                                <th>Min</th>
                                <th>Max</th>
                                <th>Promedio</th>
                                <th>Últ. costo</th>
                            </tr>
                            </thead>
                            <tbody id="tbody-modal-inventory-levels">
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>

                </div>
            </div>
        </div>
    </div>


@endsection

@section('plugins')
    <!-- Datatables -->
    <script src="{{ asset('admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>

    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>

    <script src="{{ asset('admin/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/locales/bootstrap-datepicker.es.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/inputmask/min/jquery.inputmask.bundle.min.js') }}"></script>
@endsection

@section('scripts')
    <script type="text/template" id="tpl-mp-wrapper">
        <div class="mb-3 p-2 border rounded">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <strong>Nueva presentación</strong>
                    <div class="text-muted" style="font-size:12px;">Agrega cantidad y precio. Ej: 12 unidades con descuento.</div>
                </div>
                <button class="btn btn-sm btn-outline-secondary" data-mp-refresh>
                    <i class="fas fa-sync"></i> Recargar
                </button>
            </div>

            <div class="form-row mt-2">
                <div class="form-group col-md-4">
                    <label class="mb-1">Cantidad</label>
                    <input type="number" step="0.0001" min="0" class="form-control form-control-sm" data-mp-new-quantity>
                </div>
                <div class="form-group col-md-4">
                    <label class="mb-1">Precio</label>
                    <input type="number" step="0.01" min="0" class="form-control form-control-sm" data-mp-new-price>
                </div>
                <div class="form-group col-md-4 d-flex align-items-end">
                    <button class="btn btn-success btn-sm w-100" data-mp-create>
                        <i class="fas fa-plus"></i> Crear
                    </button>
                </div>
            </div>
        </div>

        <div id="mp-list"></div>
    </script>

    <script type="text/template" id="tpl-mp-row">
        <div class="p-2 border rounded mb-2 {row_class}" data-mp-row data-id="{id}" data-active="{active}">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label class="mb-1">Cantidad</label>
                    <input type="number" step="1" min="1" class="form-control form-control-sm" data-mp-quantity value="{quantity}" disabled>
                </div>

                <div class="form-group col-md-4">
                    <label class="mb-1">Precio</label>
                    <input type="number" step="0.01" min="0" class="form-control form-control-sm" data-mp-price value="{price}" disabled>
                </div>

                <div class="form-group col-md-4 d-flex align-items-end">
                    <div class="w-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge {badge_class}" data-mp-status>{status_text}</span>
                        </div>

                        <div class="btn-group w-100" role="group">
                            <button class="btn btn-outline-primary btn-sm" data-mp-edit {edit_disabled}>
                                <i class="fas fa-edit"></i> Editar
                            </button>

                            <button class="btn btn-primary btn-sm d-none" data-mp-save>
                                <i class="fas fa-save"></i> Guardar
                            </button>

                            <button class="btn btn-outline-secondary btn-sm d-none" data-mp-cancel>
                                Cancelar
                            </button>

                            <button class="btn {toggle_btn_class} btn-sm" data-mp-toggle>
                                {toggle_text}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </script>
    <script>
        $(function () {
            //Initialize Select2 Elements
            $('#retaceria').select2({
                placeholder: "Selecione Retacería",
                allowClear: true
            });

            $('#marca').select2({
                placeholder: "Selecione Marca",
                allowClear: true
            });

            $('#category').select2({
                placeholder: "Seleccione Categoría",
                allowClear: true
            });

            $('#subcategory').select2({
                placeholder: "Seleccione SubCategoría",
                allowClear: true
            });

            $('#material_type').select2({
                placeholder: "Seleccione Tipo",
                allowClear: true
            });

            $('#sub_type').select2({
                placeholder: "Seleccione SubTipo",
                allowClear: true
            });

            $('#rotation').select2({
                placeholder: "Seleccione Rotación",
                allowClear: true
            });

            $('#material').select2({
                placeholder: "Seleccione material",
                allowClear: true
            });

            $('#isPack').select2({
                placeholder: "Seleccione pack",
                allowClear: true
            });

        })
    </script>
    <script>
        window.materialInventoryLevelsUrl = "{{ route('material.inventory-levels', ':id') }}";
    </script>
    <script src="{{ asset('js/material/indexV2.js') }}?v={{ time() }}"></script>

@endsection
