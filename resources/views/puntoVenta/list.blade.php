@extends('layouts.appAdmin2')

@section('openPuntoVenta')
    menu-open
@endsection

@section('activePuntoVenta')
    active
@endsection

@section('activeListPuntoVenta')
    active
@endsection

@section('title')
    Ventas
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
    <h1 class="page-title">Pedidos de Clientes</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Listado de pedidos</h5>

    {{--@can('create_referralGuide')
        <a href="{{ route('referral.guide.create') }}" class="btn btn-outline-success btn-sm float-right" > <i class="fa fa-plus font-20"></i> Nueva Guía de remisión </a>
    @endcan
    @can('download_referralGuide')
        <button type="button" id="btn-download" class="btn btn-outline-success btn-sm float-right mr-2" > <i class="fas fa-download"></i> Exportar guías</button>
    @endcan--}}
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('puntoVenta.list') }}"><i class="fa fa-archive"></i> Ventas</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Listado</li>
    </ol>
@endsection

@section('content')
    {{--<input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">--}}
    <!--begin::Form-->
    <form action="#">
        <!--begin::Card-->
        <!--begin::Input group-->
        <div class="row">
            <div class="col-md-12">
                <!-- Barra de búsqueda -->
                <div class="input-group">
                    <input type="text" id="code" class="form-control" placeholder="Código del pedido..." autocomplete="off">
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
                            <label for="year">Año de registro:</label>
                            <select id="year" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>
                                @for ($i=0; $i<count($arrayYears); $i++)
                                    <option value="{{ $arrayYears[$i] }}">{{ $arrayYears[$i] }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="campoExtra">Fechas de venta:</label>
                            <div class="col-md-12" id="sandbox-container">
                                <div class="input-daterange input-group" id="datepicker">
                                    <input type="text" class="form-control form-control-sm date-range-filter" id="start" name="start" autocomplete="off">
                                    <span class="input-group-addon">&nbsp;&nbsp;&nbsp; al &nbsp;&nbsp;&nbsp; </span>
                                    <input type="text" class="form-control form-control-sm date-range-filter" id="end" name="end" autocomplete="off">
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Añade más campos según lo necesario -->
                </div>
            </div>
        </div>
        <!--end::Input group-->

    </form>
    <!--end::Form-->

    <!--begin::Toolbar-->
    <div class="d-flex flex-wrap flex-stack pb-7">
        <!--begin::Title-->
        <div class="d-flex flex-wrap align-items-center my-1">
            <h3 class="fw-bolder me-5 my-1"><span id="numberItems"></span> Pedidos encontrados
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
                <thead>
                <tr class="normal-title">
                    {{--<th>ID</th>--}}
                    <th>Código</th>
                    <th>Fecha Venta</th>
                    <th>Moneda</th>
                    <th>Total</th>
                    <th>Metodo de Pago</th>
                    <th>Acciones</th>
                </tr>
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
            {{--<td data-id></td>--}}
            <td data-code></td>
            <td data-date></td>
            <td data-currency></td>
            <td data-total></td>
            <td data-tipo_pago></td>
            <td data-buttons></td>
        </tr>
    </template>

    <template id="item-table-empty">
        <tr>
            <td colspan="6" align="center">No se ha encontrado ningún dato</td>
        </tr>
    </template>

    <template id="template-active">
        <a href="" target="_blank" data-print_recibo data-id="" class="btn btn-outline-dark btn-sm" data-toggle="tooltip" data-placement="top" title="Imprimir boleta"><i class="fas fa-print"></i></a>
        <button data-ver_detalles data-id="" class="btn btn-outline-secondary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver Detalles"><i class="fas fa-list-ol"></i></button>
        <button data-anular data-id="" class="btn btn-outline-danger btn-sm" data-toggle="tooltip" data-placement="top" title="Anular Orden"><i class="fas fa-trash-alt"></i></button>

        {{--<a href="" data-id="" class="btn btn-warning btn-sm" data-toggle="tooltip" data-placement="top" title="Ver comprobante">
            <img src="{{ asset('images/sale/facturacion_electronica.png') }}" alt="Generar" style="width: 16px; height: 16px;">
        </a>--}}
    </template>

    <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailsModalLabel">Detalles del Pedido</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <!-- Aquí se cargarán los detalles dinámicamente -->
                    <div id="order-details-content"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="mapModal" tabindex="-1" aria-labelledby="mapModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mapModalLabel">Ruta Generada</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="mapContainer" style="width: 100%; height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalFacturador" tabindex="-1" role="dialog" aria-labelledby="modalFacturadorLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="modalFacturadorLabel">Datos del comprobante</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Aquí va el cuerpo dinámico -->
                    <form id="formFacturador">
                        <input type="hidden" name="order_id" id="order_id">

                        <div class="form-group">
                            <label>Tipo de comprobante:</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="invoice_type" id="radio_boleta" value="boleta">
                                <label class="form-check-label" for="radio_boleta">Boleta</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="invoice_type" id="radio_factura" value="factura">
                                <label class="form-check-label" for="radio_factura">Factura</label>
                            </div>
                        </div>

                        <!-- Datos boleta -->
                        <div id="datos_boleta" class="d-none">
                            <div class="form-group">
                                <label for="dni">Nombre <span style="color:red;">*</span></label>
                                <input type="text" name="name" id="name" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="dni">DNI <span style="color:red;">*</span></label>
                                <input type="text" name="dni" id="dni" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="email_invoice_boleta">Email (Opcional)</label>
                                <input type="text" name="email_invoice_boleta" id="email_invoice_boleta" class="form-control">
                            </div>
                        </div>

                        <!-- Datos factura -->
                        <div id="datos_factura" class="d-none">
                            <div class="form-group">
                                <label for="ruc">RUC <span style="color:red;">*</span></label>
                                <input type="text" name="ruc" id="ruc" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="razon_social">Razón Social <span style="color:red;">*</span></label>
                                <input type="text" name="razon_social" id="razon_social" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="direccion_fiscal">Dirección Fiscal <span style="color:red;">*</span></label>
                                <input type="text" name="direccion_fiscal" id="direccion_fiscal" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="email_invoice_factura">Email (Opcional)</label>
                                <input type="text" name="email_invoice_factura" id="email_invoice_factura" class="form-control">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button id="btnGuardarDatos" type="button" class="btn btn-secondary">Guardar datos</button>
                    <button id="btnGenerarComprobante" type="button" class="btn btn-warning">Generar comprobante</button>

                </div>
                <div id="downloadSection" class="mt-3 text-center d-none mb-5">
                    <a id="btnDescargarPDF" href="#" target="_blank" class="btn btn-success">
                        Descargar PDF del Comprobante
                    </a>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('plugins')
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>

    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>

    <script src="{{ asset('admin/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/locales/bootstrap-datepicker.es.min.js') }}"></script>
@endsection

@section('scripts')
    <script>
        $(function () {
            //Initialize Select2 Elements
            $('#year').select2({
                placeholder: "Selecione año",
                allowClear: true
            });

        })
    </script>
    <script src="{{ asset('js/puntoVenta/list.js') }}?v={{ time() }}"></script>

@endsection