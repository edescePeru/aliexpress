@extends('layouts.appAdmin2')

@section('openOrderPurchaseReports')
    menu-open
@endsection

@section('activeOrderPurchaseReports')
    active
@endsection

@section('activeReportOrderPurchaseByMaterial')
    active
@endsection

@section('title')
    Reportes de Ordenes de compra
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
    <div class="next-page-heading"><span class="next-page-eyebrow">Compras · Reportes</span><h1 class="page-title">Órdenes por material</h1><p class="next-page-description">Consulta de compras por material en la empresa activa.</p></div>
@endsection

@section('page-title')
    <h5 class="card-title">Reportes de Órdenes por Material</h5>

    @can('report_orderPurchaseExpress')
    <button type="button" id="btn-export" class="btn btn-outline-primary btn-sm float-right mr-2" > <i class="far fa-file-excel"></i> Descargar Excel </button>
    @endcan
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-archive"></i> Reporte de Órdenes de Compra </li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">
    <!--begin::Form-->
    <form action="#" class="next-form-section">
        <!--begin::Card-->
        <!--begin::Input group-->
        <div class="row">

            <div class="col-12 col-md-3 col-xl-2">
                <label for="year">Año de la Orden:</label>
                <select id="year" class="form-control select2" style="width: 100%;">
                    <option value="">TODOS</option>
                    @for ($i=0; $i<count($arrayYears); $i++)
                        <option value="{{ $arrayYears[$i] }}">{{ $arrayYears[$i] }}</option>
                    @endfor
                </select>
            </div>

            <div class="col-12 col-md-9 col-xl-5">
                <label for="material">Materiales:</label>
                <select id="material" name="material" class="form-control select2" style="width: 100%;">
                    <option value="">TODOS</option>
                    @for ($i=0; $i<count($arrayMaterials); $i++)
                        <option value="{{ $arrayMaterials[$i]['id'] }}">{{ $arrayMaterials[$i]['code']." ".$arrayMaterials[$i]['full_name'] }}</option>
                    @endfor
                </select>
            </div>

            <div class="col-12 col-md-8 col-xl-4">
                <label for="start">Fechas de orden:</label>
                <div id="sandbox-container">
                    <div class="input-daterange input-group" id="datepicker">
                        <input type="text" class="form-control form-control-sm date-range-filter" id="start" name="start" autocomplete="off">
                        <span class="input-group-addon"> al </span>
                        <input type="text" class="form-control form-control-sm date-range-filter" id="end" name="end" autocomplete="off">
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 col-xl-1">
                <label for="btn-search" class="d-block">&nbsp;</label>
                <button class="btn btn-primary" type="button" id="btn-search">Buscar</button>
            </div>
        </div>
    </form>
    <!--end::Form-->

    <div class="next-list-summary"><div><strong><span id="numberItems">0</span> Órdenes de Compra</strong><span class="text-muted"> · Fecha de orden, más recientes primero</span></div></div>
    <!--begin::Tab Content-->
    <div class="tab-content">
        <!--begin::Tab pane-->

        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm next-data-table mb-0">
                <thead>
                <tr >
                    <th>N°</th>
                    <th>Año</th>
                    <th>Observación</th>
                    <th class="text-center">Fecha de orden</th>
                    <th class="text-center">Fecha Llegada</th>
                    <th>Proveedor</th>
                    <th>Aprovado Por</th>
                    <th class="text-center">Tipo / Estado</th>
                    <th class="text-center">Moneda</th>
                    {{--<th>Material</th>--}}
                    <th class="text-center">Cantidad</th>
                    <th class="text-right">Precio</th>
                    <th class="text-right">Total</th>
                </tr>
                </thead>
                <tbody id="body-table"><tr><td colspan="12" class="text-center text-muted">Seleccione un material y pulse Buscar.</td></tr>

                </tbody>
            </table>
        </div>
        <!--end::Tab pane-->
        <!--begin::Pagination-->
        <div class="next-list-pagination">
            <div class="fs-6 fw-bold text-gray-700" id="textPagination"></div>
            <!--begin::Pages-->
            <ul class="pagination mb-0" id="pagination">

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
            <td data-code class="text-center"></td>
            <td data-year class="text-center"></td>
            <td data-observation></td>
            <td data-date_order class="text-center"></td>
            <td data-date_arrival class="text-center"></td>
            <td data-supplier></td>
            <td data-approved_user></td>
            <td data-state class="text-center"></td>
            <td data-currency class="text-center"></td>
            {{--<td data-material></td>--}}
            <td data-quantity class="text-center"></td>
            <td data-price class="text-right"></td>
            <td data-total class="text-right"></td>
        </tr>
    </template>

    <template id="item-table-empty">
        <tr>
            <td colspan="12" align="center">No se ha encontrado ningún dato</td>
        </tr>
    </template>

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
        $(function () {
            //Initialize Select2 Elements
            $('#year').select2({
                placeholder: "Selecione año",
                allowClear: true
            });

            $('#material').select2({
                placeholder: "Selecione Material",
                allowClear: true
            });

        })
    </script>
    <script src="{{ asset('js/orderPurchase/report_orders_by_material_v2.js') }}"></script>

@endsection