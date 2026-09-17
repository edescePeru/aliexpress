@extends('layouts.appAdmin2')

@section('title')
    Editar cuenta bancaria
@endsection

@section('page-header')
    <h1 class="page-title">Cuentas bancarias</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Modificar cuentas bancarias</h5>

@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('companyBankAccount.index') }}"><i class="fa fa-archive"></i> Cuentas bancarias</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Modificar</li>
    </ol>
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('content')
    <form
            action="{{ route('companyBankAccount.update', $account->id) }}"
            method="POST"
            id="company-bank-account-form"
    >
        @csrf
        @method('PUT')

        <div class="card-body">
            @include(
                'companyBankAccount.partials.form',
                [
                    'account' => $account,
                    'banks' => $banks,
                ]
            )
        </div>

        <div class="card-footer">
            <a
                    href="{{ route('companyBankAccount.index') }}"
                    class="btn btn-secondary"
            >
                Cancelar
            </a>

            <button
                    type="submit"
                    class="btn btn-primary"
                    id="btn-save-bank-account"
            >
                <i class="fas fa-save mr-1"></i>
                Guardar cambios
            </button>
        </div>
    </form>
@endsection

@section('plugins')
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>

@endsection

@section('scripts')
    <script src="{{ asset('js/companyBankAccount/form.js') }}"></script>
@endsection