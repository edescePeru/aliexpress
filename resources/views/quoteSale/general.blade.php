@extends('layouts.appAdmin2')
@section('openQuote') menu-open @endsection
@section('activeQuote') active @endsection
@section('activeGeneralQuote') active @endsection
@section('title') Cotizaciones @endsection
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
<div class="next-page-heading"><span class="next-page-eyebrow">Ventas</span><h1 class="page-title">Cotizaciones</h1><p class="next-page-description">Consulta comercial del local activo.</p></div>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Listado operativo</strong><span>Filtros, importes y acciones de cada cotización.</span></div><div class="next-toolbar-actions">
<a href="{{ route('quoteSale.index.facturadas') }}" class="btn btn-outline-secondary btn-sm">Facturadas</a>
@can('create_quoteSale')<a href="{{ route('quoteSale.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1" aria-hidden="true"></i> Nueva cotización</a>@endcan
</div></div>
@endsection
@section('page-breadcrumb')
<ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('dashboard.principal') }}">Dashboard</a></li><li class="breadcrumb-item active" aria-current="page">Cotizaciones</li></ol>
@endsection
@section('content')
@can('generarComprobante_quoteSale')
<div class="next-page-toolbar mb-3"><div class="next-toolbar-context"><strong>Registrar comprobante</strong></div><div class="next-toolbar-actions">
@can('generarBoleta_quoteSale')<a href="{{ route('show.register.comprobante', 'Boleta') }}" class="btn btn-outline-secondary btn-sm">Boleta</a>@endcan
@can('generarFactura_quoteSale')<a href="{{ route('show.register.comprobante', 'Factura') }}" class="btn btn-outline-secondary btn-sm">Factura</a>@endcan
<a href="{{ route('show.register.comprobante', 'Ticket') }}" class="btn btn-outline-secondary btn-sm">Ticket de venta</a>
</div></div>
@endcan
@include('quoteSale._operationalList')
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

    <script>
        $(function () {
            $('#decimals').select2({ dropdownParent: $('#modalDecimals'), width: '100%', placeholder: "Seleccione"
            });


        })
    </script>
@endsection

@section('scripts')
    <script>
        $(function () {
            //Initialize Select2 Elements
            $('#year').select2({
                width: '100%', placeholder: "Selecione año",
                allowClear: true
            });

            $('#customer').select2({
                width: '100%', placeholder: "Selecione",
                allowClear: true
            });

            $('#creator').select2({
                width: '100%', placeholder: "Selecione",
                allowClear: true
            });

            $('#stateQuote').select2({
                width: '100%', placeholder: "Selecione",
                allowClear: true
            });

            $('#decimals').select2({ dropdownParent: $('#modalDecimals'), width: '100%', placeholder: "Seleccione"
            });

        })
    </script>
    <script src="{{ asset('js/quoteSale/general.js') }}"></script>

@endsection