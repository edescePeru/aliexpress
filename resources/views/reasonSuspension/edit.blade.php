@extends('layouts.appAdmin2')

@section('openHourSpecial')
    menu-open
@endsection

@section('activeHourSpecial')
    active
@endsection

@section('openSuspension')
    menu-open
@endsection

@section('activeListReasonSuspension')
    active
@endsection

@section('title')
    Razones de suspensión
@endsection

@section('styles-plugins')
    <!-- Datatables -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Configuración · RRHH</span><h1 class="page-title">Motivos de suspensión</h1><p class="next-page-description">Catálogo laboral compartido.</p></div>
@endsection

@section('page-title')
    <div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Editar registro</strong><span>Datos del catálogo laboral.</span></div><div class="next-toolbar-actions flex-wrap"><a href="{{ route('reasonSuspension.index') }}" class="btn btn-outline-secondary btn-sm">Volver al listado</a></div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('reasonSuspension.index') }}"><i class="fa fa-archive"></i> Razones de Suspensión</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Nuevo</li>
    </ol>
@endsection

@section('content')

    <form id="formCreate" class="form-horizontal" data-url="{{ route('reasonSuspension.update') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="reason_id" value="{{ $reason->id }}">

        <section class="next-form-section"><div class="row">
            <div class="col-md-6 form-group">
                <label for="reason">Motivo </label>

                <textarea name="reason" id="reason" class="form-control">{{ old('reason', $reason->reason) }}</textarea>

            </div>

            <div class="col-md-6 form-group">
                <label for="days">Días de Suspensión</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-sort-numeric-up"></i></span>
                    </div>
                    <input type="number" name="days" id="days" class="form-control" min="1" value="{{ old('days', $reason->days) }}" step="1">
                </div>
            </div>
        </div>

        </section>
        <div class="next-list-toolbar"><div class="next-toolbar-actions flex-wrap">
            <button type="button" id="btn-submit" class="btn btn-primary">Guardar</button>
            <button type="reset" class="btn btn-outline-secondary">Cancelar</button>
        </div>
        </div>
        <!-- /.card-footer -->
    </form>
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

@endsection

@section('scripts')
    <script>
        $(function () {
            //$('#datemask').inputmask()
            $('#date_start').inputmask('dd/mm/yyyy', { 'placeholder': 'dd/mm/yyyy' });
            $('#date_end').inputmask('dd/mm/yyyy', { 'placeholder': 'dd/mm/yyyy' });

        })
    </script>
    <script src="{{ asset('js/reasonSuspension/edit.js') }}"></script>
@endsection
