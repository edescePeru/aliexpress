@extends('layouts.appAdmin2')

@section('openMaterial')
    menu-open
@endsection

@section('activeMaterial')
    active
@endsection

@section('activeListStockItems')
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
    <div class="row mb-3">
        <div class="col-md-4">
            <input type="text" id="search-stock-item" class="form-control"
                   placeholder="Buscar por SKU, código de barras o nombre">
        </div>
        <div class="col-md-2">
            <button type="button" id="btn-search-stock-item" class="btn btn-primary btn-block">
                Buscar
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm">
            <thead class="normal-title">
            <tr>
                <th>SKU</th>
                <th>Barcode</th>
                <th>Nombre visible</th>
                <th>Producto padre</th>
                <th>Variante</th>
                <th>UM</th>
                <th>Inventariable</th>
                <th>Activo</th>
                <th>Stock actual</th>
                <th>Reservado</th>
                <th>Acción</th>
            </tr>
            </thead>
            <tbody id="tbody-stock-items">
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-2">
        <div id="stock-items-info"></div>
        <div id="stock-items-pagination"></div>
    </div>

    <div class="modal fade" id="modalInventoryLevels" tabindex="-1" role="dialog" aria-labelledby="modalInventoryLevelsLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <form id="formInventoryLevels">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalInventoryLevelsLabel">Inventario por almacén</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" id="modal_stock_item_id">

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label>SKU</label>
                                <input type="text" class="form-control form-control-sm" id="modal_sku" readonly>
                            </div>
                            <div class="col-md-3">
                                <label>Barcode</label>
                                <input type="text" class="form-control form-control-sm" id="modal_barcode" readonly>
                            </div>
                            <div class="col-md-6">
                                <label>Nombre visible</label>
                                <input type="text" class="form-control form-control-sm" id="modal_display_name" readonly>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                <tr>
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
                        <button type="submit" class="btn btn-primary" id="btn-save-inventory-levels">
                            Guardar cambios
                        </button>
                    </div>
                </form>
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
    <script>
        window.stockItemInventoryLevelsUrl = "{{ route('stockitems.inventory-levels', ':id') }}";
        window.stockItemInventoryLevelsUpdateUrl = "{{ route('stockitems.inventory-levels.update', ':id') }}";
    </script>
    <script>
        window.APP = {
            URLS: {
                STOCK_ITEMS: "{{ route('stockitems.list') }}"
            }
        };
    </script>
    <script src="{{ asset('js/stockItem/list.js') }}?v={{ time() }}"></script>

@endsection