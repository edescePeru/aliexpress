@extends('layouts.appAdmin2')

@section('openGanancias')
    menu-open
@endsection

@section('activeGanancias')
    active
@endsection

@section('activeGananciaDiaria')
    active
@endsection

@section('title')
    Detalle de ganancia diaria
@endsection

@section('styles-plugins')
    <!-- Datatables -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/typehead/typeahead.css') }}">
@endsection

@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Reportes · Ventas</span><h1 class="page-title">Detalle de ganancia diaria</h1><p class="next-page-description">Resúmenes persistidos del local activo · PEN.</p></div>
@endsection

@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Detalle del resumen</strong><span>Consulta de solo lectura.</span></div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('ganancia.index') }}"><i class="fa fa-archive"></i> Detalle de ganancia diaria</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Listado</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">

    <input type="hidden" id="ganancia_id" value="{{ $ganancia->id }}">
    <section class="next-operational-list" aria-label="Ganancia diaria">
        <div class="next-form-section"><div class="next-toolbar-actions"><a href="{{ route('ganancia.index') }}" class="btn btn-outline-secondary btn-sm">Volver al listado</a><span class="badge badge-light">{{ $ganancia->currency }}</span></div><h2 class="h5 mt-3">Resumen del {{ $ganancia->date_resumen->format('d/m/Y') }}</h2><p class="text-muted mb-0">Productos agrupados del local activo. Importes almacenados por el backend.</p></div>
        <div class="next-list-summary"><div class="next-list-summary-copy"><strong><span id="numberItems">—</span> productos</strong><span>Detalle del resumen seleccionado</span></div></div>
        <div class="next-list-content"><div class="table-responsive" tabindex="0" aria-label="Resúmenes diarios; desplazamiento horizontal">
            <table class="table table-bordered table-hover table-sm next-data-table"><thead><tr>
                <th>Producto / presentación</th><th class="text-center">Fecha</th><th class="text-right">Cantidad vendida</th><th class="text-right">Venta (PEN)</th><th class="text-right">Utilidad (PEN)</th>
            </tr></thead><tbody id="body-table"></tbody></table>
        </div></div>
        <div class="next-list-toolbar"><div id="textPagination" aria-live="polite"></div><ul class="pagination mb-0" id="pagination"></ul></div>
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
            <td data-id></td>
            <td class="text-center" data-date_resumen></td>
            <td class="text-right" data-quantity_sale></td>
            <td class="text-right" data-total_sale></td>
            <td class="text-right" data-total_utility></td>
        </tr>
    </template>

    <template id="item-table-empty">
        <tr>
            <td colspan="5" class="next-table-empty">No hay productos en este resumen.</td>
        </tr>
    </template>

    <template id="template-button">
        <a data-ver_detalles href="{{--'+document.location.origin+ '/dashboard/editar/material/'+item.id+'--}}" class="btn btn-outline-secondary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver detalles"><i class="fas fa-info"></i> Detalles</a>
    </template>
@endsection

@section('plugins')
    <!-- Datatables -->
    <script src="{{ asset('admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/inputmask/min/jquery.inputmask.bundle.min.js') }}"></script>
    <script src="{{asset('admin/plugins/typehead/typeahead.bundle.js')}}"></script>
@endsection

@section('scripts')
    <script src="{{ asset('js/ganancias/index.js') }}"></script>
@endsection
