@extends('layouts.appAdmin2')

@section('openDiscountContribution')
    menu-open
@endsection

@section('activeDiscountContribution')
    active
@endsection

@section('openExpense')
    menu-open
@endsection

@section('activeCreateExpense')
    active
@endsection

@section('title')
    Rendición de Gastos
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
    <div class="next-page-heading"><span class="next-page-eyebrow">Operaciones · Rendición de gastos</span><h1 class="page-title">Nuevo gasto</h1><p class="next-page-description">Trabajador, tipo de gasto, fecha e importe.</p></div>
@endsection

@section('page-title')
    <div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Datos del gasto</strong><span>Completa los datos de la rendición.</span></div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <span>Rendición de gastos</span>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Nuevo</li>
    </ol>
@endsection

@section('content')

    <form id="formCreate" class="form-horizontal" data-url="{{ route('expense.store') }}" enctype="multipart/form-data">
        @csrf
        <section class="next-form-section">
        <div class="row">
            <div class="col-md-6 form-group">
                <label for="worker_id">Trabajador: </label>
                <select id="worker_id" name="worker_id" class="form-control select2 w-100 @error('worker_id') is-invalid @enderror">
                    <option></option>
                    @foreach( $workers as $worker )
                        <option value="{{ $worker->id }}" {{ (string) old('worker_id') === (string) $worker->id ? 'selected' : '' }} data-supervisor="{{  $worker->first_name . ' ' . $worker->last_name }}">{{ $worker->first_name . ' ' . $worker->last_name}}</option>
                    @endforeach
                </select>
                @error('worker_id')<span class="text-danger small">{{ $message }}</span>@enderror
            </div>

            <div class="col-md-6 form-group">
                <label for="bill_id">Tipo de Gastos </label>

                <select id="bill_id" name="bill_id" class="form-control select2 w-100 @error('bill_id') is-invalid @enderror">
                    <option></option>
                    @foreach( $bills as $bill )
                        <option value="{{ $bill->id }}" {{ (string) old('bill_id') === (string) $bill->id ? 'selected' : '' }} >{{ $bill->description}}</option>
                    @endforeach
                </select>
                @error('bill_id')<span class="text-danger small">{{ $message }}</span>@enderror

            </div>

        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="date_expense">Fecha</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                    </div>
                    <input type="text" id="date_expense" value="{{ old('date_expense') }}" name="date_expense" class="form-control @error('date_expense') is-invalid @enderror" data-inputmask-alias="datetime" data-inputmask-inputformat="dd/mm/yyyy" data-mask>
                </div>
                @error('date_expense')<span class="text-danger small">{{ $message }}</span>@enderror
            </div>
            <div class="col-md-6 form-group">
                <label for="total">Monto</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                    </div>
                    <input type="number" id="total" value="{{ old('total') }}" min="0" step="0.01" name="total" class="form-control @error('total') is-invalid @enderror">
                </div>
                @error('total')<span class="text-danger small">{{ $message }}</span>@enderror
            </div>
        </div>

        </section>
        <div class="next-list-toolbar"><div class="next-toolbar-actions flex-wrap">
            <button type="reset" class="btn btn-outline-secondary">Cancelar</button>
            <button type="button" id="btn-submit" class="btn btn-primary">Guardar</button>
        </div></div>
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
            $('#date_expense').inputmask('dd/mm/yyyy', { 'placeholder': 'dd/mm/yyyy' });
            $('#worker_id').select2({
                placeholder: "Selecione trabajador",
                width: '100%',
            });
            $('#bill_id').select2({
                placeholder: "Selecione tipo de gasto",
                width: '100%',
            });

        })
    </script>
    <script src="{{ asset('js/expense/create.js') }}"></script>
@endsection
