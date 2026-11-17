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

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Logística · Guías</span><h1 class="page-title">Guías de remisión</h1><p class="next-page-description">Consulta de guías remitente del local activo.</p></div>
@endsection

@section('page-title')
    <div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Listado operativo</strong><span>Fechas, documento y estado registrado.</span></div><div class="next-toolbar-actions flex-wrap">

    @can('create_referralGuide')
        <button type="button" id="btnEmitir" class="btn btn-outline-secondary btn-sm" > <i class="fa fa-plus font-20"></i> Emitir Guía </button>
    @endcan
    @can('download_referralGuide')
        <button type="button" id="btnExport" class="btn btn-outline-secondary btn-sm" > <i class="fas fa-download"></i> Exportar guías</button>
    @endcan
    </div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('shipping_guides.view') }}"><i class="fa fa-archive"></i> Guías de Remisión</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Listado</li>
    </ol>
@endsection

@section('content')
    <div class="next-operational-list">
        <div class="next-form-section">
            <div>
                <div class="row">
                    <div class="col-12 col-md-6 col-xl-3 form-group mb-2">
                        <label for="fDesde">Desde</label>
                        <input type="date" id="fDesde" class="form-control">
                    </div>
                    <div class="col-12 col-md-6 col-xl-3 form-group mb-2">
                        <label for="fHasta">Hasta</label>
                        <input type="date" id="fHasta" class="form-control">
                    </div>
                    <div class="col-12 col-md-6 col-xl-3 form-group mb-2">
                        <label for="fTipoDoc">Tipo de Doc.</label>
                        <select id="fTipoDoc" class="form-control">
                            <option value="7">GUÍA DE REMISIÓN REMITENTE ELECTRÓNICA</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3 form-group mb-2">
                        <label for="fBuscarDoc">Buscar Documento</label>
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

        <div>
            <div>
                <div class="next-list-summary"><strong id="guideCount">Guías</strong><span>Local activo · registros más recientes primero</span></div>
                <div class="table-responsive" tabindex="0" aria-label="Guías; desplazamiento horizontal">
                    <table class="table table-bordered table-hover table-sm mb-0">
                        <thead>
                        <tr>
                            <th class="text-center">Tipo</th>
                            <th class="text-center">Serie-Número</th>
                            <th class="text-center">Fecha Emisión</th>
                            <th>Motivo</th>
                            <th>Partida → Llegada</th>
                            <th class="text-center">Estado SUNAT</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                        </thead>
                        <tbody id="tbodyGuides">
                        <tr>
                            <td colspan="7" class="text-center p-4">Cargando...</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="next-list-toolbar d-flex flex-wrap justify-content-between align-items-center">
                    <div id="paginationInfo" class="text-muted"></div>
                    <div class="next-toolbar-actions flex-wrap">
                        <button class="btn btn-sm btn-outline-secondary" id="prevPage">Anterior</button>
                        <button class="btn btn-sm btn-outline-secondary" id="nextPage">Siguiente</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Export Excel -->
    <div class="modal fade next-aux-modal next-aux-modal-form" id="modalExportGuides" tabindex="-1" role="dialog" aria-labelledby="modalExportGuidesLabel" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalExportGuidesLabel">Descarga en Excel</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body next-form-section">
                    <p class="text-muted mb-3">Indica el rango de fechas que deseas descargar</p>

                    <div class="form-group">
                        <label>Desde esta fecha</label>
                        <input type="date" class="form-control" id="expDateFrom" value="{{ now()->format('Y-m-d') }}">
                    </div>

                    <div class="form-group">
                        <label>Hasta esta fecha</label>
                        <input type="date" class="form-control" id="expDateTo" value="{{ now()->format('Y-m-d') }}">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-block" id="btnDoExport">DESCARGAR</button>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('plugins')
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>

@endsection

@section('scripts')
    <script>
        window.routes = {
            list: "{{ route('shipping_guides.list') }}",
            create: "{{ route('shipping_guides.create') }}",
            export: "{{ route('shipping_guides.export') }}",
            consult: "{{ route('shipping_guides.consult', ['guide' => ':id']) }}",
            showView: "{{ route('shipping_guides.show', ['guide' => ':id']) }}"
        };
    </script>
    <script src="{{ asset('js/shipping_guides/index.js') }}?v={{ time() }}"></script>
@endsection