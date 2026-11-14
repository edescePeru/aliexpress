@extends('layouts.appAdmin2')

@section('title')
    Nueva cuenta bancaria
@endsection

@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Configuración de empresa</span><h1 class="page-title">Nueva cuenta bancaria</h1><p class="next-page-description">Cuentas de la empresa activa.</p></div>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Datos de la cuenta</strong><span>Banco, moneda y datos bancarios.</span></div><div class="next-toolbar-actions flex-wrap">
</div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('companyBankAccount.index') }}"><i class="fa fa-archive"></i> Cuentas bancarias</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Nuevo</li>
    </ol>
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('content')
    <form
            action="{{ route('companyBankAccount.store') }}"
            method="POST"
            id="company-bank-account-form"
    >
        @csrf

        <div class="next-form-section">
            @include(
                'companyBankAccount.partials.form',
                [
                    'account' => null,
                    'banks' => $banks,
                ]
            )
        </div>

        <div class="next-list-toolbar"><div class="next-toolbar-actions flex-wrap ml-auto">
            <a
                    href="{{ route('companyBankAccount.index') }}"
                    class="btn btn-outline-secondary"
            >
                Cancelar
            </a>

            <button
                    type="submit"
                    class="btn btn-primary"
                    id="btn-save-bank-account"
            >
                <i class="fas fa-save mr-1"></i>
                Guardar cuenta
            </button>
        </div>
    </div></form>
@endsection

@section('plugins')
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>

@endsection

@section('scripts')
    <script src="{{ asset('js/companyBankAccount/form.js') }}"></script>
@endsection