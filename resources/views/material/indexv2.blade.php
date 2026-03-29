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
    <style>
        .select2-search__field{
            width: 100% !important;
        }
        .letraTabla {
            font-family: "Calibri", Arial, sans-serif; /* Utiliza Calibri si está instalado, de lo contrario, usa Arial o una fuente sans-serif similar */
            font-size: 15px; /* Tamaño de fuente 11 */
        }
        .normal-title {
            background-color: #203764; /* Color deseado para el fondo */
            color: #fff; /* Color deseado para el texto */
            text-align: center;
        }
        .cliente-title {
            background-color: #FFC000; /* Color deseado para el fondo */
            color: #000; /* Color deseado para el texto */
            text-align: center;
        }
        .trabajo-title {
            background-color: #00B050; /* Color deseado para el fondo */
            color: #000; /* Color deseado para el texto */
            text-align: center;
        }
        .documentacion-title {
            background-color: #FFC000; /* Color deseado para el fondo */
            color: #000; /* Color deseado para el texto */
            text-align: center;
        }
        .importe-title {
            background-color: #00B050; /* Color deseado para el fondo */
            color: #000; /* Color deseado para el texto */
            text-align: center;
        }
        .facturacion-title {
            background-color: #FFC000; /* Color deseado para el fondo */
            color: #000; /* Color deseado para el texto */
            text-align: center;
        }
        .abono-title {
            background-color: #00B050; /* Color deseado para el fondo */
            color: #000; /* Color deseado para el texto */
            text-align: center;
        }
        .busqueda-avanzada {
            display: none;
        }

        #btnBusquedaAvanzada {
            display: inline-block;
            text-decoration: none;
            color: #007bff;
            border-bottom: 1px solid transparent;
            transition: border-bottom 0.3s ease;
        }
        #btnBusquedaAvanzada:hover {
            border-bottom: 2px solid #007bff;
        }
        .vertical-center {
            display: flex;
            align-items: center;
        }
        .datepicker-orient-top {
            top: 100px !important;
        }
    </style>
@endsection

@section('page-header')
    <h1 class="page-title">Materiales en Almacen</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Listar materiales almacen</h5>
    <button type="button" class="btn btn-outline-info btn-sm float-right" id="btn-resumen-stock">
        Ver resumen de stock
    </button>
    @can('create_material')
        <a href="{{ route('material.create') }}" class="btn btn-outline-success btn-sm float-right" > <i class="fa fa-plus font-20"></i> Nuevo material </a>
    @endcan
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
    <!--begin::Form-->
    <form action="#">
        <!--begin::Card-->
        <!--begin::Input group-->
        <div class="row">
            <div class="col-md-12">
                <!-- Barra de búsqueda -->
                <div class="input-group">
                    <input type="text" id="description" class="form-control" placeholder="Descripción del material..." autocomplete="off">
                    <div class="input-group-append ">
                        <button class="btn btn-primary" type="button" id="btn-search">Buscar</button>
                        <a href="#" id="btnBusquedaAvanzada" class="vertical-center ml-3 mt-2">Búsqueda Avanzada</a>
                    </div>
                </div>

                <!-- Sección de búsqueda avanzada (inicialmente oculta) -->
                <div class="mt-3 busqueda-avanzada">
                    <!-- Aquí coloca más campos de búsqueda avanzada -->
                    <div class="row">

                        <div class="col-md-3">
                            <label for="category">Categoría:</label>
                            <select id="category" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>
                                @for ($i=0; $i<count($arrayCategories); $i++)
                                    <option value="{{ $arrayCategories[$i]['id'] }}">{{ $arrayCategories[$i]['name'] }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="subcategory">SubCategoría:</label>
                            <select id="subcategory" name="subcategory" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>

                            </select>
                        </div>

                        {{--<div class="col-md-2">
                            <label for="material_type">Tipo:</label>
                            <select id="material_type" name="material_type" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>

                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="sub_type">SubTipo:</label>
                            <select id="sub_type" name="sub_type" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>

                            </select>
                        </div>--}}
                        <div class="col-md-2">
                            <label for="rotation">Rotación:</label>
                            <select id="rotation" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>
                                @for ($i=0; $i<count($arrayRotations); $i++)
                                    <option value="{{ $arrayRotations[$i]['value'] }}">{{ $arrayRotations[$i]['display'] }}</option>
                                @endfor
                            </select>
                        </div>

                    </div>

                    <br>

                    <div class="row">
                        {{--<div class="col-md-2">
                            <label for="cedula">Cédula:</label>
                            <select id="cedula" name="cedula" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>
                                @for ($i=0; $i<count($arrayCedulas); $i++)
                                    <option value="{{ $arrayCedulas[$i]['id'] }}">{{ $arrayCedulas[$i]['name'] }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="calidad">Calidad:</label>
                            <select id="calidad" name="calidad" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>
                                @for ($i=0; $i<count($arrayCalidades); $i++)
                                    <option value="{{ $arrayCalidades[$i]['id'] }}">{{ $arrayCalidades[$i]['name'] }}</option>
                                @endfor
                            </select>
                        </div>--}}
                        <div class="col-md-2">
                            <label for="marca">Marca:</label>
                            <select id="marca" name="marca" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>
                                @for ($i=0; $i<count($arrayMarcas); $i++)
                                    <option value="{{ $arrayMarcas[$i]['id'] }}">{{ $arrayMarcas[$i]['name'] }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="retaceria">Retacería:</label>
                            <select id="retaceria" name="retaceria" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>
                                @for ($i=0; $i<count($arrayRetacerias); $i++)
                                    <option value="{{ $arrayRetacerias[$i]['id'] }}">{{ $arrayRetacerias[$i]['name'] }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="quote">Código:</label>
                            <input type="text" id="code" class="form-control form-control-sm" placeholder="791" autocomplete="off">

                        </div>
                        <div class="col-md-2">
                            <label for="isPack">Es paquete:</label>
                            <select id="isPack" name="isPack" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>
                                <option value="0">No</option>
                                <option value="1">Si</option>
                            </select>
                        </div>
                    </div>

                    <br>

                    <!-- Añade más campos según lo necesario -->
                </div>
            </div>
        </div>
        <!--end::Input group-->
        <!--begin:Action-->
        {{--<div class="col-md-1">
            <label for="btn-search">&nbsp;</label><br>
            <button type="button" id="btn-search" class="btn btn-primary me-5">Buscar</button>
        </div>--}}

    </form>
    <!--end::Form-->

    <div class="row mt-3">
        <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
            <input type="checkbox" checked data-column="codigo" class="custom-control-input" id="customSwitch1">
            <label class="custom-control-label" for="customSwitch1">Código</label>
        </div>
        <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
            <input type="checkbox" checked data-column="descripcion" class="custom-control-input" id="customSwitch2">
            <label class="custom-control-label" for="customSwitch2">Descripcion</label>
        </div>
        <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
            <input type="checkbox" checked data-column="unidad_medida" class="custom-control-input" id="customSwitch3">
            <label class="custom-control-label" for="customSwitch3">Unidad Medida</label>
        </div>
        <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
            <input type="checkbox" checked data-column="stock_actual" class="custom-control-input" id="customSwitch4">
            <label class="custom-control-label" for="customSwitch4">Stock Actual</label>
        </div>
        <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
            <input type="checkbox" checked data-column="precio_unitario" class="custom-control-input" id="customSwitch5">
            <label class="custom-control-label" for="customSwitch5">Precio Costo</label>
        </div>
        <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
            <input type="checkbox" checked data-column="precio_lista" class="custom-control-input" id="customSwitch6">
            <label class="custom-control-label" for="customSwitch6">Precio Venta</label>
        </div>
        <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
            <input type="checkbox" checked data-column="categoria" class="custom-control-input" id="customSwitch7">
            <label class="custom-control-label" for="customSwitch7">Categoría</label>
        </div>
        <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
            <input type="checkbox" checked data-column="sub_categoria" class="custom-control-input" id="customSwitch8">
            <label class="custom-control-label" for="customSwitch8">SubCategoría</label>
        </div>
        <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
            <input type="checkbox" checked data-column="marca" class="custom-control-input" id="customSwitch9">
            <label class="custom-control-label" for="customSwitch9">Marca</label>
        </div>
        <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
            <input type="checkbox" checked data-column="modelo" class="custom-control-input" id="customSwitch10">
            <label class="custom-control-label" for="customSwitch10">Modelo</label>
        </div>
        <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
            <input type="checkbox" checked data-column="imagen" class="custom-control-input" id="customSwitch11">
            <label class="custom-control-label" for="customSwitch11">Imagen</label>
        </div>
        <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
            <input type="checkbox" checked data-column="rotation" class="custom-control-input" id="customSwitch12">
            <label class="custom-control-label" for="customSwitch12">Rotación</label>
        </div>
    </div>

    <!--begin::Toolbar-->
    <div class="d-flex flex-wrap flex-stack pb-7">
        <!--begin::Title-->
        <div class="d-flex flex-wrap align-items-center my-1">
            <h3 class="fw-bolder me-5 my-1"><span id="numberItems"></span> Materiales
                <span class="text-gray-400 fs-6">por fecha de creación ↓ </span>
            </h3>
        </div>
        <!--end::Title-->
    </div>
    <!--end::Toolbar-->

    <!--begin::Tab Content-->
    <div class="tab-content">
        <!--begin::Tab pane-->
        <hr>
        <div class="table-responsive">
            <table class="table table-bordered letraTabla table-hover table-sm mb-5">
                <thead id="header-table">
                </thead>
                <tbody id="body-table">

                </tbody>
            </table>
        </div>
        <!--end::Tab pane-->
        <!--begin::Pagination-->
        <div class="d-flex flex-stack flex-wrap pt-1">
            <div class="fs-6 fw-bold text-gray-700" id="textPagination"></div>
            <!--begin::Pages-->
            <ul class="pagination" style="margin-left: auto;" id="pagination">

            </ul>
            <!--end::Pages-->
        </div>
        <!--end::Pagination-->
    </div>
    <!--end::Tab Content-->

    <template id="item-header">
        <tr class="normal-title">
            <th data-column="codigo" data-codigo>Código</th>
            <th data-column="descripcion" data-descripcion>Descripcion</th>
            <th data-column="unidad_medida" data-unidad_medida>Unidad Medida</th>
            <th data-column="stock_actual" data-stock_actual>Stock Actual</th>
            <th data-column="precio_unitario" data-precio_unitario>Precio Costo</th>
            <th data-column="precio_lista" data-precio_lista>Precio Venta</th>
            <th data-column="categoria" data-categoria>Categoría</th>
            <th data-column="sub_categoria" data-sub_categoria>SubCategoría</th>
            <th data-column="marca" data-marca>Marca</th>
            <th data-column="modelo" data-modelo>Modelo</th>
            <th data-column="imagen" data-imagen>Imagen</th>
            <th data-column="rotation" data-rotation>Rotación</th>
            <th></th>
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
            <td data-column="codigo" data-codigo></td>
            <td data-column="descripcion" data-descripcion></td>
            <td data-column="unidad_medida" data-unidad_medida></td>
            <td data-column="stock_actual" data-stock_actual></td>
            <td data-column="precio_unitario" data-precio_unitario></td>
            <td data-column="precio_lista" data-precio_lista></td>
            <td data-column="categoria" data-categoria></td>
            <td data-column="sub_categoria" data-sub_categoria></td>
            <td data-column="marca" data-marca></td>
            <td data-column="modelo" data-modelo></td>
            <td data-column="imagen" data-imagen>
                <button data-ver_imagen data-src="{{--'+document.location.origin+ '/images/material/'+item.image+'--}}" data-image="{{--'+item.id+'--}}" class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver Imagen"><i class="fa fa-image"></i></button>
            </td>
            <td data-column="rotation" data-rotation></td>
            <td>
                <a data-editar_material href="{{--'+document.location.origin+ '/dashboard/editar/material/'+item.id+'--}}" class="btn btn-outline-warning btn-sm" data-toggle="tooltip" data-placement="top" title="Editar"><i class="fa fa-pen"></i> </a>
                <button data-deshabilitar data-delete="{{--'+item.id+'--}}" data-description="{{--'+item.full_description+'--}}" data-measure="{{--'+item.measure+'--}}" class="btn btn-outline-danger btn-sm" data-toggle="tooltip" data-placement="top" title="Deshabilitar"><i class="fas fa-bell-slash"></i> </button>
                {{--<a data-ver_items href="--}}{{--'+document.location.origin+ '/dashboard/view/material/items/'+item.id+'--}}{{--" class="btn btn-outline-info btn-sm" data-toggle="tooltip" data-placement="top" title="Ver items"><i class="fa fa-eye"></i> </a>
                --}}
                <a data-ver_variants href="{{--'+document.location.origin+ '/dashboard/view/material/items/'+item.id+'--}}" class="btn btn-outline-info btn-sm" data-toggle="tooltip" data-placement="top" title="Ver variantes"><i class="fa fa-eye"></i> </a>
                <button data-precioDirecto data-material="{{--'+item.id+'--}}" data-description="{{--'+item.full_description+'--}}" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Gestionar precios"><i class="fas fa-tag"></i> </button>
                <button data-separate data-material="" data-quantity data-description="" data-measure="" class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Separar Paquete"><i class="far fa-object-ungroup"></i></button>
                <button data-assign_child data-material="" data-description="" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Asignar Hijos"><i class="fas fa-boxes"></i></button>
                <button data-show_vencimiento data-material="" data-description="" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Ver fechas"><i class="fas fa-calendar-alt"></i></button>
                <button data-manage_presentations data-material="" data-description="" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Configurar presentaciones"><i class="fas fa-cubes"></i></button>
            </td>
        </tr>
    </template>

    <template id="item-table-empty">
        <tr>
            <td colspan="22" align="center">No se ha encontrado ningún dato</td>
        </tr>
    </template>

    <div id="modalPrecioDirecto" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Gestión de Precios</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="formPrecioDirecto" data-url="{{ route('material.manage.price') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="material_id" name="material_id">
                        <p>¿Está seguro de configurar estos precios?</p>
                        <p id="descriptionMaterialPrice"></p>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="material_priceList">Precio Base: <span class="right badge badge-danger">(*)</span></label>
                                    <input type="number" id="material_priceBase" step="0.01" name="material_priceBase" class="form-control" required min="0" readonly>
                                </div>
                            </div>
                            {{--<div class="col-md-3">
                                <div class="form-group">
                                    <label for="material_priceList">Precio Minimo: <span class="right badge badge-danger">(*)</span></label>
                                    <input type="number" id="material_priceMin" step="0.01" name="material_priceMin" class="form-control" required min="0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="material_priceList">Precio Máximo: <span class="right badge badge-danger">(*)</span></label>
                                    <input type="number" id="material_priceMax" step="0.01" name="material_priceMax" class="form-control" required min="0">
                                </div>
                            </div>--}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="material_priceList">Precio Tienda: <span class="right badge badge-danger">(*)</span></label>
                                    <input type="number" id="material_priceList" step="0.01" name="material_priceList" class="form-control" required min="0">
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btn-submit_priceList" class="btn btn-success">Guardar</button>
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

            $('#calidad').select2({
                placeholder: "Selecione Calidad",
                allowClear: true
            });

            $('#cedula').select2({
                placeholder: "Seleccione Cedula",
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
    <script src="{{ asset('js/material/indexV2.js') }}?v={{ time() }}"></script>

@endsection