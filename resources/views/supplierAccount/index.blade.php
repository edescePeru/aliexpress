@extends('layouts.appAdmin2')

@section('openSupplier')
    menu-open
@endsection

@section('activeSupplier')
    active
@endsection

@section('activeListSupplier')
    active
@endsection

@section('title')
    Cuentas Bancarias
@endsection

@section('styles-plugins')
    <!-- Datatables -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <!-- VDialog -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/vdialog/css/vdialog.css') }}">

@endsection

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Proveedores · Configuración</span><h1 class="page-title">Cuentas bancarias</h1><p class="next-page-description">{{ $supplier->business_name }} → Cuentas</p></div>
@endsection

@section('page-title')
    <input type="hidden" name="worker_id" id="worker_id" value="{{ $supplier->id }}">
    <div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Cuentas del proveedor</strong><span>Banco, moneda y número de cuenta.</span></div><div class="next-toolbar-actions flex-wrap">
        <a href="{{ route('supplier.index') }}" class="btn btn-outline-secondary btn-sm">Volver a proveedores</a>
        @can('create_supplierAccount')
        <button type="button" id="newAccount" data-url="{{ route('supplier.account.store', $supplier->id) }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1" aria-hidden="true"></i>Agregar cuenta</button>
        @endcan
    </div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('supplier.index') }}"><i class="fa fa-archive"></i> Proveedores</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Listado</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">


    <section class="next-operational-list" aria-label="Cuentas del proveedor">
        <div class="next-list-summary" aria-live="polite"><strong id="account-count">{{ $accounts->count() }} cuentas</strong><span>{{ $supplier->business_name }}</span></div>
        <div id="account-empty" class="next-form-section text-center text-muted {{ $accounts->isEmpty() ? '' : 'd-none' }}">
            <p class="mb-1">Este proveedor no tiene cuentas registradas.</p>
            @can('create_supplierAccount')<small>Usa Agregar cuenta para registrar una cuenta del proveedor.</small>@endcan
        </div>
        <div id="body-accounts">
                    @foreach( $accounts as $account )
                        <div class="next-form-section border-bottom" data-account-row>
                            <div class="row">
                                <div class="col-md-6 col-xl-3">
                                    <label>Número de Cuenta </label>
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-keyboard"></i></span>
                                        </div>
                                        <input type="text" maxlength="191" aria-label="Número de cuenta" data-number_account class="form-control" placeholder="Número de Cuenta" value="{{ $account->number_account }}">
                                    </div>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <div class="form-group">
                                        <label>Banco </label>
                                        <select name="banks[]" data-bank aria-label="Banco" class="bank form-control select2" style="width: 100%;">
                                            <option></option>
                                            @foreach( $banks as $bank )
                                                <option  value="{{ $bank->id }}" data-image_bank="{{ asset('/images/bank/'.$bank->image) }}" {{ ($bank->id == $account->bank_id) ? 'selected':'' }}>{{ $bank->short_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3">
                                    <div class="form-group">
                                        <label>Moneda </label>
                                        <select name="currency[]" data-currency aria-label="Moneda" class="currency form-control select2" style="width: 100%;">
                                            <option></option>
                                            <option value="PEN" {{ ('PEN' == $account->currency) ? 'selected':'' }} >Soles</option>
                                            <option value="USD" {{ ('USD' == $account->currency) ? 'selected':'' }} >Dólares</option>

                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3">
                                    <div class="form-group">
                                        <label>Acciones </label><div class="next-toolbar-actions flex-wrap">
                                        @can('edit_supplierAccount')
                                        <button type="button" data-updateAccount="{{ $account->id }}" data-number="{{ $account->number_account }}" class="btn btn-sm btn-outline-primary">Guardar cambios</button>
                                        @endcan
                                        @can('destroy_supplierAccount')
                                        <button type="button" data-deleteAccount="{{ $account->id }}" data-number="{{ $account->number_account }}" class="btn btn-sm btn-outline-danger ">Eliminar cuenta</button>
                                        @endcan
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
    </section>

    <template id="template-account">
        <div class="next-form-section border-bottom" data-account-row>
            <div class="row">
                <div class="col-md-6 col-xl-3">
                    <label>Número de Cuenta </label>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-keyboard"></i></span>
                        </div>
                        <input type="text" maxlength="191" aria-label="Número de cuenta" data-number_account name="numberAccounts[]" class="form-control" placeholder="Número de Cuenta" value="">
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="form-group">
                        <label>Banco </label>
                        <select name="banks[]" data-bank aria-label="Banco" class="bank form-control select2" style="width: 100%;">
                            <option></option>
                            @foreach( $banks as $bank )
                                <option value="{{ $bank->id }}" data-image_bank="{{ asset('images/bank/'.$bank->image) }}" >{{ $bank->short_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="form-group">
                        <label>Moneda </label>
                        <select name="currency[]" data-currency aria-label="Moneda" class="currency form-control  select2" style="width: 100%;">
                            <option></option>
                            <option value="PEN">Soles</option>
                            <option value="USD">Dólares</option>

                        </select>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="form-group">
                        <label>Acciones </label><div class="next-toolbar-actions flex-wrap">
                        @can('edit_supplierAccount')
                        <button type="button" data-updateAccount data-number class="btn btn-sm btn-outline-primary">Guardar cambios</button>
                                        @endcan
                        @can('destroy_supplierAccount')
                        <button type="button" data-deleteAccount data-number class="btn btn-sm btn-outline-danger ">Eliminar cuenta</button>
                                        @endcan
                                        </div>
                    </div>
                </div>
            </div>

        </div>
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
    <!-- Vdialog -->
    <script src="{{ asset('admin/plugins/vdialog/js/lib/vdialog.js') }}"></script>

@endsection

@section('scripts')
    <script>
        var optionFormat = function(item) {
            if ( !item.id ) {
                return item.text;
            }

            var span = document.createElement('span');
            var imgUrl = item.element.getAttribute('data-image_bank');
            var template = '';

            template += '<img src="' + imgUrl + '" class="rounded-circle" width="25px" alt="image"/>  ';
            template += item.text;

            span.innerHTML = template;

            return $(span);
        };

        $('.bank').select2({
            placeholder: "Seleccione un banco",
            templateSelection: optionFormat,
            templateResult: optionFormat,
        });
        $('.currency').select2({
            placeholder: "Seleccione una moneda",
        })
    </script>
    <script src="{{ asset('js/supplierAccount/create.js') }}"></script>
@endsection