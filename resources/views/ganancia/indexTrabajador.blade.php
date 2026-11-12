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

@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Reportes · Ventas</span><h1 class="page-title">Ganancias por usuario</h1><p class="next-page-description">Consulta de ventas del local activo · PEN.</p></div>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Listado operativo</strong><span>Importes calculados por el reporte de ganancias.</span></div><div class="next-toolbar-actions flex-wrap">
@can('exportDetail_gananciaDiaria')<button type="button" id="btn-export-detallado" class="btn btn-outline-secondary btn-sm"><i class="far fa-file-excel mr-1" aria-hidden="true"></i>Excel detallado</button>@endcan
</div></div>
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

    <section class="next-operational-list" aria-label="Ganancias por usuario">
    <div class="next-form-section">
        <div class="row align-items-end">
            <div class="col-lg-3 form-group"><label for="creator">Usuario que registró la venta</label><select id="creator" name="creator" class="form-control form-control-sm select2 w-100"><option value="">TODOS</option>@foreach($authors as $author)<option value="{{ $author->created_by }}">{{ $report->author($author) }}</option>@endforeach</select></div>
            <div class="col-lg form-group"><label for="start">Fechas de venta</label><div id="sandbox-container"><div class="input-daterange input-group input-group-sm" id="datepicker"><input type="text" class="form-control form-control-sm date-range-filter" id="start" name="start" autocomplete="off" aria-label="Fecha inicial"><div class="input-group-append input-group-prepend"><span class="input-group-text">al</span></div><input type="text" class="form-control form-control-sm date-range-filter" id="end" name="end" autocomplete="off" aria-label="Fecha final"></div></div></div>
            <div class="col-lg-auto form-group"><div class="next-toolbar-actions flex-wrap flex-lg-nowrap"><button type="button" id="btn-search" class="btn btn-primary btn-sm"><i class="fas fa-search mr-1" aria-hidden="true"></i>Buscar</button>@can('export_gananciaDiaria')<button type="button" id="btn-export" class="btn btn-outline-secondary btn-sm">Exportar Excel</button>@endcan</div></div>
        </div><small class="text-muted">Los filtros de usuario y fechas también se aplican a los exports. El trabajador opcional no sustituye al usuario que registró la venta.</small>
    </div>
    <div class="next-list-summary"><div class="next-list-summary-copy"><strong><span id="numberItems">—</span> ventas</strong><span>Fecha de venta · más recientes primero</span></div></div>
    <div class="next-list-content"><div class="table-responsive" tabindex="0" aria-label="Ventas; desplazamiento horizontal"><table class="table table-bordered table-hover table-sm next-data-table"><thead><tr><th class="text-center">Venta</th><th class="text-center">Fecha</th><th>Usuario</th><th class="text-center">Cantidad vendida</th><th class="text-right">Vendido (S/)</th><th class="text-right">Ganancia (S/)</th><th class="text-center">Acciones</th></tr></thead><tbody id="body-table"></tbody></table></div></div>
    <div class="next-list-toolbar"><div id="textPagination" aria-live="polite"></div><ul class="pagination mb-0" id="pagination"></ul></div>
    <div class="next-form-section"><div class="row"><div class="col-md-4"><p class="text-muted mb-1">Cantidad total vendida</p><strong id="resumen-quantity">—</strong></div><div class="col-md-4"><p class="text-muted mb-1">Total vendido (PEN)</p><strong id="resumen-total-sale">—</strong></div><div class="col-md-4"><p class="text-muted mb-1">Ganancia total (PEN)</p><strong id="resumen-total-utility">—</strong></div></div></div>
    </section>

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
            <td class="text-center" data-id></td>
            <td class="text-center" data-date_resumen></td>
            <td data-author></td>
            <td class="text-center" data-quantity_sale></td>
            <td class="text-right" data-total_sale></td>
            <td class="text-right" data-total_utility></td>
            <td class="text-center" data-buttons></td>
        </tr>
    </template>

    <template id="item-table-empty">
        <tr>
            <td colspan="7" class="next-table-empty">No se ha encontrado ningún dato</td>
        </tr>
    </template>

    <template id="template-button">
        <div class="dropdown next-row-actions"><button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle next-row-actions-trigger" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false" aria-label="Acciones de venta"><i class="fas fa-ellipsis-h" aria-hidden="true"></i></button><div class="dropdown-menu dropdown-menu-right next-row-actions-menu"><a data-ver_detalles href="#" class="dropdown-item">Comprobante</a><a data-profit-detail href="#" class="dropdown-item">Ver ganancia</a></div></div>
    </template>
    <div id="export-overlay" class="modal" tabindex="-1" role="status" aria-label="Generando reporte"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-body text-center"><strong>Generando reporte</strong><p>Por favor espere…</p><div class="spinner-border spinner-border-sm" aria-hidden="true"></div></div></div></div></div>
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
                placeholder: "Seleccione usuario",
                width: "100%",
                allowClear: true
            });

        })
    </script>
    <script src="{{ asset('js/ganancias/indexTrabajador.js') }}?v={{ filemtime(public_path('js/ganancias/indexTrabajador.js')) }}"></script>
@endsection
