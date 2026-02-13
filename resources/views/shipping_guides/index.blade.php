@extends('layouts.appAdmin2')

@section('openReferralGuide')
    menu-open
@endsection

@section('activeReferralGuide')
    active
@endsection

@section('activeListReferralGuide')
    active
@endsection

@section('title')
    Guías de Remisión
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
    <h1 class="page-title">Guías de remisión</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Listado de guías de remisión</h5>

    @can('create_referralGuide')
        <button type="button" id="btnEmitir" class="btn btn-outline-success btn-sm float-right" > <i class="fa fa-plus font-20"></i> Emitir Guía </button>
    @endcan
    @can('download_referralGuide')
        <button type="button" id="btnExport" class="btn btn-outline-success btn-sm float-right mr-2" > <i class="fas fa-download"></i> Exportar guías</button>
    @endcan
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('referral.guide.index') }}"><i class="fa fa-archive"></i> Guías de Remisión</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Listado</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label>Desde</label>
                        <input type="date" id="fDesde" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label>Hasta</label>
                        <input type="date" id="fHasta" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label>Tipo de Doc.</label>
                        <select id="fTipoDoc" class="form-control">
                            <option value="7">GUÍA DE REMISIÓN REMITENTE ELECTRÓNICA</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Buscar Documento</label>
                        <div class="input-group">
                            <input type="text" id="fBuscarDoc" class="form-control" placeholder="TPD1-123">
                            <div class="input-group-append">
                                <button id="btnBuscar" class="btn btn-primary">Buscar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                        <tr class="normal-title">
                            <th>Tipo</th>
                            <th>Serie-Número</th>
                            <th>Fecha Emisión</th>
                            <th>Motivo</th>
                            <th>Partida → Llegada</th>
                            <th>Estado SUNAT</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                        </thead>
                        <tbody id="tbodyGuides">
                        <tr>
                            <td colspan="7" class="text-center p-4">Cargando...</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-3 d-flex justify-content-between align-items-center">
                    <div id="paginationInfo" class="text-muted"></div>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" id="prevPage">Anterior</button>
                        <button class="btn btn-sm btn-outline-secondary" id="nextPage">Siguiente</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        window.routes = {
            list: "{{ route('shipping_guides.list') }}",
            create: "{{ route('shipping_guides.create') }}",
            export: "{{ route('shipping_guides.export') }}"
        };
    </script>
    <script src="{{ asset('js/shipping_guides/index.js') }}"></script>
@endsection