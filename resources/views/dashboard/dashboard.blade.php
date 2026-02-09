@extends('layouts.appAdmin2')

@section('title')
    Dashboard
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.standalone.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.standalone.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <style>
        .totales {
            background-color: #D9E1F2; /* Color de fondo para la primera fila */
        }
        .titleHeader {
            background-color: #4472C4;
            color: #ffffff;
        }
        .titleTotal {
            background-color: #FFC000;
            font-weight: bold;
        }
        .totalWorker {
            background-color: #FFF2CC;
        }
        .celdas {
            background-color: #D9D9D9;
        }
        .letraTabla {
            font-family: "Calibri", Arial, sans-serif; /* Utiliza Calibri si está instalado, de lo contrario, usa Arial o una fuente sans-serif similar */
            font-size: 13px; /* Tamaño de fuente 11 */
        }
        .letraTablaGrande {
            font-family: "Calibri", Arial, sans-serif; /* Utiliza Calibri si está instalado, de lo contrario, usa Arial o una fuente sans-serif similar */
            font-size: 16px; /* Tamaño de fuente 11 */
        }

    </style>
@endsection

@section('page-header')
    <h1 class="page-title">Dashboard</h1>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
    </ol>
@endsection

@section('page-title')
    <h5 class="card-title">PANEL PRINCIPAL</h5>
@endsection

@section('content')
    @can('showboxes_dashboard')
    <div class="row">
        @can('list_customer')
        <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $customerCount }}</h3>

                    <p>Clientes</p>
                </div>
                <div class="icon">
                    <i class="ion ion-briefcase"></i>
                </div>
                <a href="{{ route('customer.index') }}" class="small-box-footer">Más detalles <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
        @endcan
        @can('list_contactName')
        <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $contactNameCount }}</h3>

                    <p>Contactos</p>
                </div>
                <div class="icon">
                    <i class="ion ion-clipboard"></i>
                </div>
                <a href="{{ route('contactName.index') }}" class="small-box-footer">Más detalles <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
        @endcan
        @can('list_supplier')
        <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $supplierCount }}</h3>

                    <p>Proveedores</p>
                </div>
                <div class="icon">
                    <i class="ion ion-ios-home-outline"></i>
                </div>
                <a href="{{ route('supplier.index') }}" class="small-box-footer">Más detalles <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
        @endcan
        @can('list_material')
        <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $materialCount }}</h3>

                    <p>Materiales</p>
                </div>
                <div class="icon">
                    <i class="ion ion-ios-box"></i>
                </div>
                <a href="{{ route('material.index') }}" class="small-box-footer">Más detalles <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
        @endcan
        @can('list_entryPurchase')
        <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $entriesCount }}</h3>

                    <p>Entradas a almacén</p>
                </div>
                <div class="icon">
                    <i class="ion ion-ios-cart"></i>
                </div>
                <a href="{{ route('entry.purchase.index') }}" class="small-box-footer">Más detalles <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
        @endcan
        @can('list_invoice')
        <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-fuchsia">
                <div class="inner">
                    <h3>{{ $invoiceCount }}</h3>

                    <p>Facturas</p>
                </div>
                <div class="icon">
                    <i class="ion ion-card"></i>
                </div>
                <a href="{{ route('invoice.index') }}" class="small-box-footer">Más detalles <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
        @endcan
        @can('list_request')
        <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $outputCount }}</h3>

                    <p>Salidas de almacén</p>
                </div>
                <div class="icon">
                    <i class="ion ion-android-exit"></i>
                </div>
                <a href="{{ route('output.request.index') }}" class="small-box-footer">Más detalles <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
        @endcan
    </div>
    @endcan
@endsection

@section('content-report')
    @can('showBoxAlmacen_dashboard')
    <div class="row">
        <div class="col-md-4">
            <div class="card card-info">
                <div class="card-header border-0">
                    <h3 class="card-title">Existencias en almacén </h3>

                    <div class="card-tools">
                        <button type="button" id="btn-refresh" class="btn btn-sm btn-warning float-left"><i class="fas fa-sync text-success"></i> Refrescar</button>

                        <a href="{{ route('report.excel.amount') }}" class="btn btn-sm btn-tool" data-toggle="tooltip" data-placement="top" title="Descargar excel">
                            <i class="fas fa-download text-danger"></i> <span class="text-danger text-bold">Descargar</span>
                        </a>
                    </div>
                </div>
                <div class="card-body" id="element_loader">
                    <div class="d-flex justify-content-between align-items-center border-bottom mb-3">
                        <p class="text-success text-xl">
                            <i class="fas fa-dollar-sign"></i>
                        </p>
                        <p class="d-flex flex-column text-right">
                        <span class="font-weight-bold" id="amount_dollars">

                        </span>
                            <span class="text-muted">MONTO EN DÓLARES</span>
                        </p>
                    </div>
                    <!-- /.d-flex -->
                    <div class="d-flex justify-content-between align-items-center border-bottom mb-3">
                        <p class="text-warning text-xl bold">
                            S/.
                        </p>
                        <p class="d-flex flex-column text-right">
                        <span class="font-weight-bold" id="amount_soles">

                        </span>
                            <span class="text-muted">MONTO EN SOLES</span>
                        </p>
                    </div>
                    <!-- /.d-flex -->
                    <div class="d-flex justify-content-between align-items-center mb-0">
                        <p class="text-danger text-xl">
                            <i class="fas fa-boxes"></i>
                        </p>
                        <p class="d-flex flex-column text-right">
                        <span class="font-weight-bold" id="quantity_items">

                        </span>
                            <span class="text-muted">CANTIDAD DE EXISTENCIAS</span>
                        </p>
                    </div>
                    <!-- /.d-flex -->
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="info-box">
                <span class="info-box-icon bg-info elevation-1">
                    <a href="{{ route('report.excel.materials') }}">
                        <i class="fas fa-database"></i>
                    </a>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">BASE DE DATOS MATERIALES</span>
                    <a href="{{ route('report.excel.materials') }}">
                        <span class="info-box-number">
                            Descargar <i class="fas fa-cloud-download-alt"></i>
                        </span>
                    </a>
                </div>
                <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
            <div class="info-box" id="box">
                <span class="info-box-icon bg-success elevation-1">
                    {{--<a href="--}}{{--{{ route('report.excel.materials') }}--}}{{--">--}}
                        <i class="fas fa-database"></i>
                    {{--</a>--}}
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">BASE DE DATOS POR ALMACEN</span>
                    <button id="btn-download" class="btn btn-sm btn-outline-success">
                        <span class="info-box-number">
                            Descargar <i class="fas fa-cloud-download-alt"></i>
                        </span>
                    </button>
                </div>
                <!-- /.info-box-content -->
            </div>
            <div class="info-box" id="box">
                <span class="info-box-icon bg-success elevation-1">
                    <i class="fas fa-file-excel"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">DESCARGAR INGRESOS</span>
                    <button id="btn-downloadEntries" class="btn btn-sm btn-outline-success">
                        <span class="info-box-number">
                            Descargar <i class="fas fa-cloud-download-alt"></i>
                        </span>
                    </button>
                </div>
                <!-- /.info-box-content -->
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-info">
                <div class="card-header border-transparent">
                    <h3 class="card-title">5 Últimas Rotaciones</h3>

                    <div class="card-tools">
                        <button type="button" id="btn-newRotation" class="btn btn-sm btn-warning float-left"><i class="fas fa-cut"></i> Nuevo corte</button>
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table m-0 table-sm">
                            <thead>
                            <tr>
                                <th>Rotación</th>
                                <th>Fecha</th>
                                <th>Usuario</th>
                            </tr>
                            </thead>
                            <tbody id="body-table">

                            </tbody>
                        </table>
                    </div>
                    <!-- /.table-responsive -->

                </div>
                <!-- /.card-body -->
                <div class="card-footer clearfix">
                    <div class="d-flex flex-stack flex-wrap pt-1">
                        <div class="fs-6 fw-bold text-gray-700" id="textPagination"></div>
                        <!--begin::Pages-->
                        <ul class="pagination" style="margin-left: auto;" id="pagination">

                        </ul>
                        <!--end::Pages-->
                    </div>
                </div>
                <!-- /.card-footer -->
            </div>
        </div>
    </div>
    @endcan
    @can('showBoxMetas_dashboard')
    <div class="row">
        <div class="col-lg-6">
            <div class="card card-success">
                <div class="card-header {{--d-flex justify-content-between align-items-center--}}">
                    <h3 class="card-title">
                        <i class="fas fa-trophy mr-1"></i> Ranking de Cumplimiento de Metas
                    </h3>
                    <div class="card-tools d-flex align-items-center">
                        @if($rankingPeriodText)
                            <span class="mr-3 text-sm text-light">
                                {{ $rankingPeriodText }}
                            </span>
                        @endif
                        <a href="{{ route('metas.ranking') }}" class="btn btn-warning btn-sm">
                            Ver más
                        </a>
                        <button type="button" class="btn btn-tool " data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>

                    </div>
                </div>

                <div class="card-body">
                    @if(empty($rankingMetasDashboard))
                        <p class="text-muted mb-0">
                            No hay datos de metas configuradas para el periodo actual.
                        </p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach($rankingMetasDashboard as $row)
                                @php
                                    $pct = max(0, min(100, $row['porcentaje']));
                                    if ($pct < 40)      $barClass = 'bg-danger';
                                    elseif ($pct < 80) $barClass = 'bg-warning';
                                    else               $barClass = 'bg-success';
                                @endphp
                                <li class="mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span><strong>{{ $row['worker_name'] }}</strong></span>
                                        <span class="text-muted">
                                {{ number_format($row['porcentaje'], 1) }}% cumplimiento
                            </span>
                                    </div>
                                    <div class="progress progress-xs mt-1" style="height: 8px;">
                                        <div class="progress-bar {{ $barClass }}" style="width: {{ $pct }}%;"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endcan
    {{--<div class="row">
        <div class="col-lg-6">
            <div class="card card-warning">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title">Cotizaciones elevadas en dólares</h3>
                        <a href="#" id="report_dollars_quote">Ver reporte detallado</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex">
                        <p class="d-flex flex-column">
                            <span class="text-bold text-lg" id="total_dollars">$0.00</span>
                            <span>Total de cotizaciones</span>
                        </p>
                        <p class="ml-auto d-flex flex-column text-right">
                    <span class="text-success">
                      <i class="fas fa-arrow-up"></i> <span id="percentage_dollars">0.00%</span>
                    </span>
                            <span class="text-muted">Cantidad 7 meses</span>
                        </p>
                    </div>
                    <!-- /.d-flex -->

                    <div class="position-relative mb-4">
                        <canvas id="sales-chart" height="200"></canvas>
                    </div>

                    <div class="d-flex flex-row justify-content-end">
                          <span class="mr-2">
                            <i class="fas fa-square text-primary"></i> Total en dólares
                          </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-warning">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title">Cotizaciones elevadas en soles</h3>
                        <a href="#" id="report_soles_quote">Ver reporte detallado</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex">
                        <p class="d-flex flex-column">
                            <span class="text-bold text-lg" id="total_soles">S/. 0.00</span>
                            <span>Total de cotizaciones</span>
                        </p>
                        <p class="ml-auto d-flex flex-column text-right">
                            <span class="text-success">
                                <i class="fas fa-arrow-up"></i> <span id="percentage_soles">0.00%</span>
                            </span>
                            <span class="text-muted">Cantidad 7 meses</span>
                        </p>
                    </div>
                    <!-- /.d-flex -->

                    <div class="position-relative mb-4">
                        <canvas id="sales-chart2" height="200"></canvas>
                    </div>

                    <div class="d-flex flex-row justify-content-end">
                        <span>
                            <i class="fas fa-square text-gray"></i> Total en soles
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card card-primary">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title">Egresos VS Ingresos en Dólares</h3>
                        <a href="#" id="report_expenses_income_dollars">Ver reporte detallado</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex">
                        <p class="d-flex flex-column">
                            <span class="text-bold text-lg"><i id="arrow_balance_general_dollars" class="fas fa-arrow-up"></i><span id="balance_general_dollars">0.00</span></span>
                            <span>Balance general</span>
                        </p>
                        <p class="ml-auto d-flex flex-column text-right">
                            <span class="text-success">
                                <i class="fas fa-arrow-up"></i> <span id="balance_general_dollars">0.00</span>
                            </span>
                            <span class="text-muted">Balance general</span>
                        </p>

                    </div>
                    <!-- /.d-flex -->

                    <div class="position-relative mb-4">
                        <canvas id="expenses-income" height="200"></canvas>
                    </div>

                    <div class="d-flex flex-row justify-content-end">
                        <span class="mr-2">
                            <i class="fas fa-square text-primary"></i> Ingresos
                        </span>

                        <span>
                            <i class="fas fa-square text-gray"></i> Egresos
                        </span>
                    </div>
                </div>
            </div>
            <!-- /.card -->
        </div>
        <div class="col-lg-6">
            <div class="card card-primary">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title">Egresos VS Ingresos en Soles</h3>
                        <a href="#" id="report_expenses_income_soles">Ver reporte detallado</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex">
                        <p class="d-flex flex-column">
                            <span class="text-bold text-lg"><i id="arrow_balance_general_soles" class="fas fa-arrow-up"></i><span id="balance_general_soles">0.00</span></span>
                            <span>Balance general</span>
                        </p>
                        <p class="ml-auto d-flex flex-column text-right">
                            <span class="text-success">
                                <i class="fas fa-arrow-up"></i> <span id="balance_general_dollars">0.00</span>
                            </span>
                            <span class="text-muted">Balance general</span>
                        </p>

                    </div>
                    <!-- /.d-flex -->

                    <div class="position-relative mb-4">
                        <canvas id="expenses-income2" height="200"></canvas>
                    </div>

                    <div class="d-flex flex-row justify-content-end">
                        <span class="mr-2">
                            <i class="fas fa-square text-primary"></i> Ingresos
                        </span>

                        <span>
                            <i class="fas fa-square text-gray"></i> Egresos
                        </span>
                    </div>
                </div>
            </div>
            <!-- /.card -->
        </div>
        <div class="col-lg-6">
            <div class="card card-primary">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title">Egresos VS Ingresos en General</h3>
                        <a href="#" id="report_expenses_income_mix">Ver reporte detallado</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex">
                        <p class="d-flex flex-column">
                            <span class="text-bold text-lg"><i id="arrow_balance_general_mix" class="fas fa-arrow-up"></i><span id="balance_general_mix">0.00</span></span>
                            <span>Balance general</span>
                        </p>
                        <p class="ml-auto d-flex flex-column text-right">
                            <span class="text-success">
                                <i class="fas fa-arrow-up"></i> <span id="balance_general_dollars">0.00</span>
                            </span>
                            <span class="text-muted">Balance general</span>
                        </p>

                    </div>
                    <!-- /.d-flex -->

                    <div class="position-relative mb-4">
                        <canvas id="expenses-income3" height="200"></canvas>
                    </div>

                    <div class="d-flex flex-row justify-content-end">
                        <span class="mr-2">
                            <i class="fas fa-square text-primary"></i> Ingresos
                        </span>

                        <span>
                            <i class="fas fa-square text-gray"></i> Egresos
                        </span>
                    </div>
                </div>
            </div>
            <!-- /.card -->
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card card-success">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title">Utilidades en Dólares</h3>
                        <a href="#" id="report_utilities_dollars">Ver reporte detallado</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex">
                        <p class="d-flex flex-column">
                            <span class="text-bold text-lg"><i id="arrow_utilities_dollars" class="fas fa-arrow-up"></i><span id="utilities_dollars">0.00</span></span>
                            <span>Balance general</span>
                        </p>
                    </div>
                    <!-- /.d-flex -->

                    <div class="position-relative mb-4">
                        <canvas id="utilities_d" height="200"></canvas>
                    </div>

                    <div class="d-flex flex-row justify-content-end">
                        <span class="mr-2">
                            <i class="fas fa-square text-primary"></i> Utilidades
                        </span>
                    </div>
                </div>
            </div>
            <!-- /.card -->
        </div>
        <div class="col-lg-6">
            <div class="card card-success">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title">Utilidades en Soles</h3>
                        <a href="#" id="report_utilities_soles">Ver reporte detallado</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex">
                        <p class="d-flex flex-column">
                            <span class="text-bold text-lg"><i id="arrow_utilities_soles" class="fas fa-arrow-up"></i><span id="utilities_soles">0.00</span></span>
                            <span>Balance general</span>
                        </p>
                    </div>
                    <!-- /.d-flex -->

                    <div class="position-relative mb-4">
                        <canvas id="utilities_s" height="200"></canvas>
                    </div>

                    <div class="d-flex flex-row justify-content-end">
                        <span class="mr-2">
                            <i class="fas fa-square text-gray"></i> Utilidades
                        </span>
                    </div>
                </div>
            </div>
            <!-- /.card -->
        </div>
        <div class="col-lg-6">
            <div class="card card-success">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title">Utilidades en General</h3>
                        <a href="#" id="report_utilities_mix">Ver reporte detallado</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex">
                        <p class="d-flex flex-column">
                            <span class="text-bold text-lg"><i id="arrow_utilities_mix" class="fas fa-arrow-up"></i><span id="utilities_mix">0.00</span></span>
                            <span>Balance general</span>
                        </p>
                    </div>
                    <!-- /.d-flex -->

                    <div class="position-relative mb-4">
                        <canvas id="utilities_m" height="200"></canvas>
                    </div>

                    <div class="d-flex flex-row justify-content-end">
                        <span class="mr-2">
                            <i class="fas fa-square text-primary"></i> Utilidades
                        </span>
                    </div>
                </div>
            </div>
            <!-- /.card -->
        </div>
    </div>

    <div id="modalViewReportDollars" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Ver reporte detallado en dólares</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <strong> Seleccione un rango de fechas: </strong>
                        </div>
                        <div class="col-md-12" id="sandbox-container">
                            <div class="input-daterange input-group" id="datepicker">
                                <input type="text" class="form-control form-control-sm date-range-filter" id="start" name="start">
                                <span class="input-group-addon">&nbsp;&nbsp;&nbsp; al &nbsp;&nbsp;&nbsp; </span>
                                <input type="text" class="form-control form-control-sm date-range-filter" id="end" name="end">
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-sm-4 offset-4">
                            <button type="button" id="btnViewReportDollarsQuote" class="btn btn-outline-success btn-block">Ver grafico</button>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-lg-10 offset-1">
                            <div class="card">
                                <div class="card-header border-0">
                                    <div class="d-flex justify-content-between">
                                        <h3 class="card-title">Cotizaciones elevadas en dólares</h3>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex">
                                        <p class="d-flex flex-column">
                                            <span class="text-bold text-lg" id="total_dollars_view_d">$. 0.00</span>
                                            <span>Total de cotizaciones</span>
                                        </p>
                                        <p class="ml-auto d-flex flex-column text-right">
                                            <span class="text-success">
                                                <i class="fas fa-arrow-up"></i> <span id="percentage_dollars_view_d">0.00%</span>
                                            </span>
                                        </p>
                                    </div>
                                    <!-- /.d-flex -->

                                    <div class="position-relative mb-4">
                                        <canvas id="sales-chart3" height="200"></canvas>
                                    </div>

                                    <div class="d-flex flex-row justify-content-end">
                                        <span>
                                            <i class="fas fa-square text-primary"></i> Total en dolares
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cancelar</button>
                </div>

            </div>
        </div>
    </div>

    <div id="modalViewReportSoles" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Ver reporte detallado en soles</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <strong> Seleccione un rango de fechas: </strong>
                        </div>
                        <div class="col-md-12" id="sandbox-container">
                            <div class="input-daterange input-group" id="datepicker">
                                <input type="text" class="form-control form-control-sm date-range-filter" id="start_s" name="start">
                                <span class="input-group-addon">&nbsp;&nbsp;&nbsp; al &nbsp;&nbsp;&nbsp; </span>
                                <input type="text" class="form-control form-control-sm date-range-filter" id="end_s" name="end">
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-sm-4 offset-4">
                            <button type="button" id="btnViewReportSolesQuote" class="btn btn-outline-success btn-block">Ver gráfico</button>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-lg-10 offset-1">
                            <div class="card">
                                <div class="card-header border-0">
                                    <div class="d-flex justify-content-between">
                                        <h3 class="card-title">Cotizaciones elevadas en soles</h3>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex">
                                        <p class="d-flex flex-column">
                                            <span class="text-bold text-lg" id="total_soles_view_s">S/. 0.00</span>
                                            <span>Total de cotizaciones</span>
                                        </p>
                                        <p class="ml-auto d-flex flex-column text-right">
                                            <span class="text-success">
                                                <i class="fas fa-arrow-up"></i> <span id="percentage_soles_view_s">0.00%</span>
                                            </span>
                                        </p>
                                    </div>
                                    <!-- /.d-flex -->

                                    <div class="position-relative mb-4">
                                        <canvas id="sales-chart4" height="200"></canvas>
                                    </div>

                                    <div class="d-flex flex-row justify-content-end">
                                        <span>
                                            <i class="fas fa-square text-gray"></i> Total en soles
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cerrar</button>
                </div>

            </div>
        </div>
    </div>

    <div id="modalViewReportIncomeExpenseDollars" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Ver Ingresos VS Egresos en dólares</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <strong> Seleccione un rango de fechas: </strong>
                        </div>
                        <div class="col-md-12" id="sandbox-container">
                            <div class="input-daterange input-group" id="datepicker">
                                <input type="text" class="form-control form-control-sm date-range-filter" id="startIncomeExpenseDollars" name="start">
                                <span class="input-group-addon">&nbsp;&nbsp;&nbsp; al &nbsp;&nbsp;&nbsp; </span>
                                <input type="text" class="form-control form-control-sm date-range-filter" id="endIncomeExpenseDollars" name="end">
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-sm-4 offset-4">
                            <button type="button" id="btnViewReportIncomeExpenseDollars" class="btn btn-outline-success btn-block">Ver grafico</button>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-lg-10 offset-1">
                            <div class="card">
                                <div class="card-header border-0">
                                    <div class="d-flex justify-content-between">
                                        <h3 class="card-title">Ingresos VS Egresos en Dólares</h3>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex">
                                        <p class="d-flex flex-column">
                                            <span class="text-bold text-lg"><i id="arrow_balance_general_view_dollars" class="fas fa-arrow-up"></i><span id="balance_general_view_dollars">0.00</span></span>
                                            <span>Balance general</span>
                                        </p>
                                        <p class="ml-auto d-flex flex-column text-right">
                                            <span class="text-success">
                                                <i class="fas fa-arrow-up"></i> <span id="balance_general_dollars">0.00</span>
                                            </span>
                                            <span class="text-muted">Balance general</span>
                                        </p>

                                    </div>
                                    <!-- /.d-flex -->

                                    <div class="position-relative mb-4">
                                        <canvas id="expenses-income-view-dollars" height="200"></canvas>
                                    </div>

                                    <div class="d-flex flex-row justify-content-end">
                                        <span class="mr-2">
                                            <i class="fas fa-square text-primary"></i> Ingresos
                                        </span>

                                        <span>
                                            <i class="fas fa-square text-gray"></i> Egresos
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cancelar</button>
                </div>

            </div>
        </div>
    </div>

    <div id="modalViewReportIncomeExpenseSoles" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Ver Ingresos VS Egresos en soles</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <strong> Seleccione un rango de fechas: </strong>
                        </div>
                        <div class="col-md-12" id="sandbox-container">
                            <div class="input-daterange input-group" id="datepicker">
                                <input type="text" class="form-control form-control-sm date-range-filter" id="startIncomeExpenseSoles" name="start">
                                <span class="input-group-addon">&nbsp;&nbsp;&nbsp; al &nbsp;&nbsp;&nbsp; </span>
                                <input type="text" class="form-control form-control-sm date-range-filter" id="endIncomeExpenseSoles" name="end">
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-sm-4 offset-4">
                            <button type="button" id="btnViewReportIncomeExpenseSoles" class="btn btn-outline-success btn-block">Ver grafico</button>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-lg-10 offset-1">
                            <div class="card">
                                <div class="card-header border-0">
                                    <div class="d-flex justify-content-between">
                                        <h3 class="card-title">Ingresos VS Egresos en Soles</h3>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex">
                                        <p class="d-flex flex-column">
                                            <span class="text-bold text-lg"><i id="arrow_balance_general_view_soles" class="fas fa-arrow-up"></i><span id="balance_general_view_soles">0.00</span></span>
                                            <span>Balance general</span>
                                        </p>
                                        <p class="ml-auto d-flex flex-column text-right">
                                            <span class="text-success">
                                                <i class="fas fa-arrow-up"></i> <span id="balance_general_dollars">0.00</span>
                                            </span>
                                            <span class="text-muted">Balance general</span>
                                        </p>

                                    </div>
                                    <!-- /.d-flex -->

                                    <div class="position-relative mb-4">
                                        <canvas id="expenses-income-view-soles" height="200"></canvas>
                                    </div>

                                    <div class="d-flex flex-row justify-content-end">
                                        <span class="mr-2">
                                            <i class="fas fa-square text-primary"></i> Ingresos
                                        </span>

                                        <span>
                                            <i class="fas fa-square text-gray"></i> Egresos
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cerrar</button>
                </div>

            </div>
        </div>
    </div>

    <div id="modalViewReportIncomeExpenseMix" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Ver reporte detallado en general</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <strong> Seleccione un rango de fechas: </strong>
                        </div>
                        <div class="col-md-12" id="sandbox-container">
                            <div class="input-daterange input-group" id="datepicker">
                                <input type="text" class="form-control form-control-sm date-range-filter" id="startIncomeExpenseMix" name="start">
                                <span class="input-group-addon">&nbsp;&nbsp;&nbsp; al &nbsp;&nbsp;&nbsp; </span>
                                <input type="text" class="form-control form-control-sm date-range-filter" id="endIncomeExpenseMix" name="end">
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-sm-4 offset-4">
                            <button type="button" id="btnViewReportIncomeExpenseMix" class="btn btn-outline-success btn-block">Ver grafico</button>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-lg-10 offset-1">
                            <div class="card">
                                <div class="card-header border-0">
                                    <div class="d-flex justify-content-between">
                                        <h3 class="card-title">Ingresos VS Egresos en general</h3>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex">
                                        <p class="d-flex flex-column">
                                            <span class="text-bold text-lg"><i id="arrow_balance_general_view_mix" class="fas fa-arrow-up"></i><span id="balance_general_view_mix">0.00</span></span>
                                            <span>Balance general</span>
                                        </p>
                                    </div>
                                    <!-- /.d-flex -->

                                    <div class="position-relative mb-4">
                                        <canvas id="expenses-income-view-mix" height="200"></canvas>
                                    </div>

                                    <div class="d-flex flex-row justify-content-end">
                                        <span class="mr-2">
                                            <i class="fas fa-square text-primary"></i> Ingresos
                                        </span>

                                        <span>
                                            <i class="fas fa-square text-gray"></i> Egresos
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cerrar</button>
                </div>

            </div>
        </div>
    </div>

    <div id="modalViewReportUtilitiesDollars" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Ver Utilidades en dólares</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <strong> Seleccione un rango de fechas: </strong>
                        </div>
                        <div class="col-md-12" id="sandbox-container">
                            <div class="input-daterange input-group" id="datepicker">
                                <input type="text" class="form-control form-control-sm date-range-filter" id="startUtilitiesDollars" name="start">
                                <span class="input-group-addon">&nbsp;&nbsp;&nbsp; al &nbsp;&nbsp;&nbsp; </span>
                                <input type="text" class="form-control form-control-sm date-range-filter" id="endUtilitiesDollars" name="end">
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-sm-4 offset-4">
                            <button type="button" id="btnViewReportUtilitiesDollars" class="btn btn-outline-success btn-block">Ver grafico</button>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-lg-10 offset-1">
                            <div class="card">
                                <div class="card-header border-0">
                                    <div class="d-flex justify-content-between">
                                        <h3 class="card-title">Utilidades en Dólares</h3>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex">
                                        <p class="d-flex flex-column">
                                            <span class="text-bold text-lg"><i id="arrow_utilities_view_dollars" class="fas fa-arrow-up"></i><span id="utilities_view_dollars">0.00</span></span>
                                            <span>Balance general</span>
                                        </p>
                                    </div>
                                    <!-- /.d-flex -->

                                    <div class="position-relative mb-4">
                                        <canvas id="utilities-view-dollars" height="200"></canvas>
                                    </div>

                                    <div class="d-flex flex-row justify-content-end">
                                        <span class="mr-2">
                                            <i class="fas fa-square text-primary"></i> Utilidades
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cancelar</button>
                </div>

            </div>
        </div>
    </div>

    <div id="modalViewReportUtilitiesSoles" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Ver Utilidades en soles</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <strong> Seleccione un rango de fechas: </strong>
                        </div>
                        <div class="col-md-12" id="sandbox-container">
                            <div class="input-daterange input-group" id="datepicker">
                                <input type="text" class="form-control form-control-sm date-range-filter" id="startUtilitiesSoles" name="start">
                                <span class="input-group-addon">&nbsp;&nbsp;&nbsp; al &nbsp;&nbsp;&nbsp; </span>
                                <input type="text" class="form-control form-control-sm date-range-filter" id="endUtilitiesSoles" name="end">
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-sm-4 offset-4">
                            <button type="button" id="btnViewReportUtilitiesSoles" class="btn btn-outline-success btn-block">Ver grafico</button>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-lg-10 offset-1">
                            <div class="card">
                                <div class="card-header border-0">
                                    <div class="d-flex justify-content-between">
                                        <h3 class="card-title">Utilidades en Soles</h3>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex">
                                        <p class="d-flex flex-column">
                                            <span class="text-bold text-lg"><i id="arrow_utilities_view_soles" class="fas fa-arrow-up"></i><span id="utilities_view_soles">0.00</span></span>
                                            <span>Balance general</span>
                                        </p>
                                    </div>
                                    <!-- /.d-flex -->

                                    <div class="position-relative mb-4">
                                        <canvas id="utilities-view-soles" height="200"></canvas>
                                    </div>

                                    <div class="d-flex flex-row justify-content-end">
                                        <span class="mr-2">
                                            <i class="fas fa-square text-gray"></i> Utilidades
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cancelar</button>
                </div>

            </div>
        </div>
    </div>

    <div id="modalViewReportUtilitiesMix" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Ver Utilidades en General</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <strong> Seleccione un rango de fechas: </strong>
                        </div>
                        <div class="col-md-12" id="sandbox-container">
                            <div class="input-daterange input-group" id="datepicker">
                                <input type="text" class="form-control form-control-sm date-range-filter" id="startUtilitiesMix" name="start">
                                <span class="input-group-addon">&nbsp;&nbsp;&nbsp; al &nbsp;&nbsp;&nbsp; </span>
                                <input type="text" class="form-control form-control-sm date-range-filter" id="endUtilitiesMix" name="end">
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-sm-4 offset-4">
                            <button type="button" id="btnViewReportUtilitiesMix" class="btn btn-outline-success btn-block">Ver grafico</button>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-lg-10 offset-1">
                            <div class="card">
                                <div class="card-header border-0">
                                    <div class="d-flex justify-content-between">
                                        <h3 class="card-title">Utilidades en General</h3>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex">
                                        <p class="d-flex flex-column">
                                            <span class="text-bold text-lg"><i id="arrow_utilities_view_mix" class="fas fa-arrow-up"></i><span id="utilities_view_mix">0.00</span></span>
                                            <span>Balance general</span>
                                        </p>
                                    </div>
                                    <!-- /.d-flex -->

                                    <div class="position-relative mb-4">
                                        <canvas id="utilities-view-mix" height="200"></canvas>
                                    </div>

                                    <div class="d-flex flex-row justify-content-end">
                                        <span class="mr-2">
                                            <i class="fas fa-square text-primary"></i> Utilidades
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cancelar</button>
                </div>

            </div>
        </div>
    </div>--}}
    @can('showBoxGraficosVentas_dashboard')
    <div class="row">
        <div class="col-md-6">

            <div class="card bg-gradient-info">
                <div class="card-header border-0">
                    <h3 class="card-title">
                        <i class="fas fa-th mr-1"></i>
                        Grafico de ventas
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm filter-btn-sale" data-filter="daily">Diario</button>
                        <button type="button" class="btn btn-secondary btn-sm filter-btn-sale" data-filter="weekly">Semanal</button>
                        <button type="button" class="btn btn-warning btn-sm filter-btn-sale" data-filter="monthly">Mensual</button>
                        <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#dateRangeModalSale">
                            Por Fechas
                        </button>
                        <button type="button" class="btn bg-info btn-sm" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas class="chart" id="sale-chart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
                <!-- /.card-body -->
                <div class="card-footer bg-transparent">
                    <div class="row">
                        <div class="col-4 text-center">
                            {{--<input type="text" id="knobTotalSale"  class="knob text-white" data-readonly="true" value="0" data-width="60" data-height="60" data-fgColor="#39CCCC">
                            --}}<div class="text-white">Total</div>
                            <span id="quantityKnobTotalSale">0</span>
                        </div>
                    </div>
                </div>
                <!-- /.card-footer -->
            </div>
            <!-- /.card -->
        </div>
        <div class="col-md-6">

            <div class="card bg-gradient-info">
                <div class="card-header border-0">
                    <h3 class="card-title">
                        <i class="fas fa-th mr-1"></i>
                        Grafico de Ingresos VS Egresos
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm filter-btn-utilidad" data-filter="daily">Diario</button>
                        <button type="button" class="btn btn-secondary btn-sm filter-btn-utilidad" data-filter="weekly">Semanal</button>
                        <button type="button" class="btn btn-warning btn-sm filter-btn-utilidad" data-filter="monthly">Mensual</button>
                        <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#dateRangeModalUtilidad">
                            Por Fechas
                        </button>
                        <button type="button" class="btn bg-info btn-sm" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas class="chart" id="utilidad-chart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
                <!-- /.card-body -->
                <div class="card-footer bg-transparent">
                    <div class="row">
                        <div class="col-4 text-center">
                            <div class="text-white">Ingresos</div>
                            <span id="quantityKnobIngresos">0</span>
                        </div>
                        <div class="col-4 text-center">
                            <div class="text-white">Egresos</div>
                            <span id="quantityKnobEgresos">0</span>
                        </div>
                        <div class="col-4 text-center">
                            <div class="text-white">Neto</div>
                            <span id="quantityKnobUtilidad">0</span>
                        </div>
                    </div>
                </div>
                <!-- /.card-footer -->
            </div>
            <!-- /.card -->
        </div>
    </div>
    @endcan
    @can('showBoxGraficosRRHH_dashboard')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-info">
                <div class="card-header border-0">
                    <h3 class="card-title">
                        <i class="fas fa-th mr-1"></i>
                        Grafico de RRHH
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn bg-info btn-sm" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="year">Seleccione un año <span class="right badge badge-danger">(*)</span></label>
                                <select id="year" name="year" class="form-control select2" style="width: 100%;">
                                    <option></option>
                                    @foreach( $years as $year )
                                        <option value="{{ $year->year }}" {{ ($year->year == $currentYear) ? 'selected':'' }}>{{ $year->year }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3" id="sandbox-container">
                            <label for="weekStart">Seleccione un mes <span class="right badge badge-danger">(*)</span></label>

                            <select id="month" name="month" class="form-control select2" style="width: 100%;">
                                <option></option>
                                @foreach( $months as $month )
                                    <option value="{{ $month->month}}" {{ ($month->month == $currentMonth) ? 'selected':'' }}>{{ $month->month_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="btn-outputs">&nbsp;</label><br>
                            <button type="button" id="btn-pays" class="btn  btn-outline-success btn-block"> Buscar</button>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="card card-navy">
                            <div class="card-header">
                                <h3 class="card-title" id="titleCard2"><strong>RESUMEN SUELDOS MENSUALES</strong></h3>

                                <div class="card-tools">

                                    <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                        <i class="fas fa-minus"></i></button>
                                </div>
                            </div>
                            <div class="card-body" >
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="table-responsive" id="tablaContainer3" >

                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <canvas id="lineChart"></canvas>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <!-- /.card -->
        </div>
    </div>
    @endcan
    <br>
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
            <td data-fecha></td>
            <td data-user></td>
        </tr>
    </template>

    <template id="item-table-empty">
        <tr>
            <td colspan="3" align="center">No se ha encontrado ningún dato</td>
        </tr>
    </template>

    <div id="modalLocations" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Seleccionar un almacén para realizar la descarga</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>

                <div class="modal-body">
                    <select id="location" name="location" class="form-control select2" style="width: 100%;">
                        <option></option>
                        @for( $i=0; $i<count($almacenes); $i++ )
                            <option value="{{ $almacenes[$i]['id'] }}">{{ $almacenes[$i]['warehouse'] }}</option>
                        @endfor
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btn-submitDownload" class="btn btn-primary" >Descargar</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal" >Cancelar</button>
                </div>

            </div>
        </div>
    </div>

    <div id="modalEntries" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Seleccionar los filtros para realizar la descarga</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>

                <div class="modal-body">
                    <div class="form-group row">
                        <div class="col-md-6">
                            <label for="typeEntry" class="col-12 col-form-label">Tipo de entradas <span class="right badge badge-danger">(*)</span></label>
                            <div class="col-sm-12">
                                <select id="typeEntry" name="typeEntry" class="form-control form-control-sm select2" style="width: 100%;">
                                    <option></option>
                                    <option value="1" selected>TODAS</option>
                                    <option value="2" >POR COMPRA</option>
                                    <option value="3" >INVENTARIO</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="typeEntry" class="col-12 col-form-label">Fechas de entradas <span class="right badge badge-danger">(*)</span></label>
                            <div class="col-md-12" id="sandbox-container">
                                <div class="input-daterange input-group" id="datepicker">
                                    <input type="text" class="form-control form-control-sm date-range-filter" id="start" name="start" autocomplete="off">
                                    <span class="input-group-addon">&nbsp;&nbsp;&nbsp; al &nbsp;&nbsp;&nbsp; </span>
                                    <input type="text" class="form-control form-control-sm date-range-filter" id="end" name="end" autocomplete="off">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" id="btn-submitExport" class="btn btn-primary" >Descargar</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal" >Cancelar</button>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="dateRangeModalSale" role="dialog" aria-labelledby="dateRangeModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dateRangeModalLabel">Seleccionar Rango de Fechas</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="dateRangeForm">
                        <div class="form-group">
                            <label for="start_date">Fecha Inicio</label>
                            <input type="date" class="form-control" id="start_date_sale">
                        </div>
                        <div class="form-group">
                            <label for="end_date">Fecha Fin</label>
                            <input type="date" class="form-control" id="end_date_sale">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary filter-btn-sale" data-filter="date_range" data-dismiss="modal">Aplicar</button>
                    <button
                            type="button"
                            class="btn btn-success"
                            id="btn-export-range-sales"
                            data-url="{{ route('sales.export.range') }}"
                    >
                        <i class="fas fa-file-excel"></i> Exportar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="dateRangeModalUtilidad" role="dialog" aria-labelledby="dateRangeModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dateRangeModalLabel">Seleccionar Rango de Fechas</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="dateRangeForm">
                        <div class="form-group">
                            <label for="start_date">Fecha Inicio</label>
                            <input type="date" class="form-control" id="start_date_utilidad">
                        </div>
                        <div class="form-group">
                            <label for="end_date">Fecha Fin</label>
                            <input type="date" class="form-control" id="end_date_utilidad">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary filter-btn-utilidad" data-filter="date_range" data-dismiss="modal">Aplicar</button>
                    <button
                            type="button"
                            class="btn btn-success"
                            id="btn-export-cashflow-range"
                            data-url="{{ route('cashflow.export.range') }}"
                    >
                        <i class="fas fa-file-excel"></i> Exportar
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>

    <!-- jQuery Knob Chart -->
    <script src="{{ asset('admin/plugins/jquery-knob/jquery.knob.min.js') }}"></script>

    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/locales/bootstrap-datepicker.es.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/jquery_loading/loadingoverlay.min.js')}}"></script>

    <script src="{{ asset('admin/plugins/chart.js/Chart.min.js') }}"></script>
    <script src="{{ asset('js/report/reportAmount.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/dashboard/ordersChart.js')}}?v={{ time() }}"></script>
    {{--<script src="{{ asset('js/report/viewReport.js') }}"></script>--}}
    {{--<script src="{{ asset('js/report/charts.js') }}"></script>--}}
    <script>
        $(function () {
            //Initialize Select2 Elements
            $('#location').select2({
                placeholder: "Selecione un almacén",
            });
            $('#typeEntry').select2({
                placeholder: "Selecione Tipo",
                allowClear: true
            });

            $('#month').select2({
                placeholder: "Selecione Tipo",
                allowClear: true
            });
            $('#year').select2({
                placeholder: "Selecione Tipo",
                allowClear: true
            });
        })
    </script>
@endsection
