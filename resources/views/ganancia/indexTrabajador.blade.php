@extends('layouts.appAdmin2')

@section('openGanancias')
    menu-open
@endsection

@section('activeGanancias')
    active
@endsection

@section('activeGananciaDiariaTrabajador')
    active
@endsection

@section('title')
    Ganancia Diaria
@endsection

@section('styles-plugins')
    <!-- Datatables -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/typehead/typeahead.css') }}">

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
        .income-row {
            background-color: #d4edda; /* Verde claro */
        }

        .expense-row {
            background-color: #f8d7da; /* Rojo claro */
        }
    </style>
@endsection

@section('page-header')
    <div class="row">
        <div class="col-md-5">
            <h1 class="page-title">Ganancias Diarias</h1>
        </div>
    </div>

@endsection

@section('page-title')
    @can('exportDetail_gananciaDiaria')
    <div class="callout callout-warning mb-2">
        <div class="d-flex justify-content-between align-items-center flex-wrap">

            <div>
                <h5 class="mb-1">
                    <i class="fas fa-exclamation-triangle mr-1"></i> Importante
                </h5>
                <p class="mb-0">
                    Los filtros de <b>Trabajador</b> y <b>Rango de Fechas</b> también se aplicarán
                    al <b>Excel de Ganancia de Ventas Detallado</b>.
                </p>
            </div>

            <div class="ml-3 mt-2 mt-md-0">
                <button type="button" id="btn-export-detallado" class="btn btn-success btn-sm">
                    <i class="far fa-file-excel"></i> Descargar Ganancia Detallada
                </button>
            </div>

        </div>
    </div>
    @endcan
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('ganancia.index') }}"><i class="fa fa-archive"></i> Ganancias Diarias</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Listado</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">

    <div class="row">
        <div class="col-md-4">
            <label for="creator">Trabajador:</label>
            <select id="creator" name="creator" class="form-control form-control-sm select2" style="width: 100%;">
                <option value="">TODOS</option>
                @foreach($workers as $worker)
                    <option value="{{ $worker->id }}">{{ $worker->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label> Fechas de Venta:</label>
            <div class="col-md-12" id="sandbox-container">
                <div class="input-daterange input-group" id="datepicker">
                    <input type="text" class="form-control form-control-sm date-range-filter" id="start" name="start" autocomplete="off">
                    <span class="input-group-addon">&nbsp;&nbsp;&nbsp; al &nbsp;&nbsp;&nbsp; </span>
                    <input type="text" class="form-control form-control-sm date-range-filter" id="end" name="end" autocomplete="off">
                </div>
            </div>
        </div>


        {{--<div class="col-md-2">
            <label> &nbsp;</label>
            <a href="#" id="btn-search" class="btn btn-primary btn-sm btn-block"><i class="fas fa-search"></i> Buscar</a>

        </div>--}}

        <div class="col-md-4">
            <label> &nbsp;</label>

            <div class="d-flex" style="gap: 8px;">
                <a href="#" id="btn-search" class="btn btn-primary btn-sm flex-fill"> <i class="fas fa-search"></i> Buscar</a>
                {{--<a href="#" id="btn-export" class="btn btn-success btn-sm flex-fill">
                    <i class="far fa-file-excel"></i> Exportar
                </a>--}}
                @can( 'export_gananciaDiaria' )
                <button type="button" id="btn-export" class="btn btn-success btn-sm flex-fill">
                    <i class="far fa-file-excel"></i> Exportar Excel
                </button>
                @endcan
            </div>
        </div>
    </div>
    <!--begin::Toolbar-->
    <div class="d-flex flex-wrap flex-stack pb-7">
        <!--begin::Title-->
        <div class="d-flex flex-wrap align-items-center my-1">
            <h3 class="fw-bolder me-5 my-1"><span id="numberItems"></span> Ventas
                <span class="text-gray-400 fs-6"> ordenandos por fecha de creación ↓ </span>
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
                    <th>N°</th>
                    <th>Fecha</th>
                    <th>Cantidad Vendida</th>
                    <th>Total Venta</th>
                    <th>Total Utilidad</th>
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

    <div class="row mt-3">

        <div class="col-md-4">
            <div class="card border-success mb-3">
                <div class="card-header bg-success text-white">
                    Cantidad total vendida
                </div>
                <div class="card-body">
                    <h5 class="card-title mb-0" id="resumen-quantity">
                        0.00
                    </h5>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-primary mb-3">
                <div class="card-header bg-primary text-white">
                    Total vendido
                </div>
                <div class="card-body">
                    <h5 class="card-title mb-0" id="resumen-total-sale">
                        S/ 0.00
                    </h5>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-warning mb-3">
                <div class="card-header bg-warning text-dark">
                    Utilidad total
                </div>
                <div class="card-body">
                    <h5 class="card-title mb-0" id="resumen-total-utility">
                        S/ 0.00
                    </h5>
                </div>
            </div>
        </div>

    </div>

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
            <td data-id></td>
            <td data-date_resumen></td>
            <td data-quantity_sale></td>
            <td data-total_sale></td>
            <td data-total_utility></td>
            <td data-buttons></td>
        </tr>
    </template>

    <template id="item-table-empty">
        <tr>
            <td colspan="6" align="center">No se ha encontrado ningún dato</td>
        </tr>
    </template>

    <template id="template-button">
        <a data-ver_detalles href="{{--'+document.location.origin+ '/dashboard/editar/material/'+item.id+'--}}" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Ver detalles"><i class="fas fa-info"></i> Detalles</a>
    </template>

    <div id="export-overlay" style="display:none; position:fixed; z-index:9999; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,.35);">
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; padding:18px 22px; border-radius:8px; min-width:320px; box-shadow:0 10px 30px rgba(0,0,0,.2); text-align:center;">
            <div style="font-size:15px; font-weight:600; margin-bottom:8px;">Generando reporte</div>
            <div style="font-size:13px; opacity:.8; margin-bottom:12px;">Por favor espere…</div>
            <div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
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
    <script src="{{ asset('admin/plugins/typehead/typeahead.bundle.js')}}"></script>
@endsection

@section('scripts')
    <script>
        $(function () {
            //Initialize Select2 Elements
            $('#creator').select2({
                placeholder: "Selecione trabajador",
                allowClear: true
            });

        })
    </script>
    <script src="{{ asset('js/ganancias/indexTrabajador.js') }}"></script>
@endsection
