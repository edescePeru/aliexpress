@extends('layouts.appAdmin2')

@section('openBankAccounts')
    menu-open
@endsection

@section('activeBankAccounts')
    active
@endsection

@section('title')
    Cuentas bancarias
@endsection

@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Configuración de empresa</span><h1 class="page-title">Cuentas bancarias</h1><p class="next-page-description">Cuentas de la empresa activa.</p></div>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Listado de cuentas</strong><span>Configuración bancaria de empresa.</span></div><div class="next-toolbar-actions flex-wrap">
@can('create_companyBankAccount')<a href="{{ route('companyBankAccount.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1" aria-hidden="true"></i>Nueva cuenta</a>@endcan
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
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Listado</li>
    </ol>
@endsection

@section('content')
    <div
            class="next-operational-list" id="company-bank-account-index"
            data-toggle-url="{{ route('companyBankAccount.toggleStatus', ['id' => '__ID__']) }}"
    >
        @if(session('success'))
            <div
                    id="flash-success"
                    data-message="{{ session('success') }}"
            ></div>
        @endif

        @if($accounts->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fas fa-university fa-3x mb-3"></i>

                <p class="mb-1">
                    No hay cuentas bancarias registradas.
                </p>

                <small>
                    Agrega una cuenta para mostrarla en los documentos comerciales.
                </small>
            </div>
        @else
            <div class="next-list-summary"><strong>{{ $accounts->count() }} cuentas</strong><span>Predeterminada primero</span></div><div class="table-responsive" tabindex="0" aria-label="Cuentas bancarias; desplazamiento horizontal">
                <table class="table table-hover table-sm next-data-table">
                    <thead>
                    <tr>
                        <th>Banco</th>
                        <th>Título</th>
                        <th class="text-center">Número de cuenta</th>
                        <th class="text-center">CCI</th>
                        <th class="text-center">Moneda</th>
                        <th>Titular</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">
                            Acciones
                        </th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($accounts as $account)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if(
                                        $account->bank &&
                                        !empty($account->bank->image)
                                    )
                                        <img
                                                src="{{ asset('images/bank/' . $account->bank->image) }}"
                                                alt="{{ $account->bank->name }}"
                                                style="
                                                        width:28px;
                                                        height:28px;
                                                        object-fit:contain;
                                                        margin-right:8px;
                                                    "
                                        >
                                    @endif

                                    <span>
                                                {{ optional($account->bank)->name ?? 'Sin banco' }}
                                            </span>
                                </div>
                            </td>

                            <td>
                                {{ $account->title }}

                                @if($account->is_default)
                                    <span class="badge badge-primary ml-1">
                                                Predeterminada
                                            </span>
                                @endif
                            </td>

                            <td class="text-center">
                                {{ $account->account_number }}
                            </td>

                            <td class="text-center">
                                {{ $account->cci ?: '—' }}
                            </td>

                            <td class="text-center">
                                @if($account->currency === 'PEN')
                                    Soles
                                @elseif($account->currency === 'USD')
                                    Dólares
                                @else
                                    —
                                @endif
                            </td>

                            <td>
                                {{ $account->account_holder ?: '—' }}
                            </td>

                            <td class="text-center">
                                @if($account->is_active)
                                    <span class="badge badge-success">
                                                Activa
                                            </span>
                                @else
                                    <span class="badge badge-secondary">
                                                Inactiva
                                            </span>
                                @endif
                            </td>

                            <td class="text-center">
                                @if(auth()->user()->can('edit_companyBankAccount') || auth()->user()->can('enable_companyBankAccount'))
                                <div class="dropdown next-row-actions"><button type="button" class="btn btn-outline-secondary btn-sm next-row-actions-toggle" data-toggle="dropdown" aria-expanded="false" aria-label="Acciones de cuenta"><i class="fas fa-ellipsis-h" aria-hidden="true"></i></button><div class="dropdown-menu dropdown-menu-right">
                                @can('edit_companyBankAccount')
                                    <a
                                            href="{{ route('companyBankAccount.edit', $account->id) }}"
                                            class="dropdown-item"
                                    >
                                        <i class="fas fa-edit"></i>
                                        Editar
                                    </a>
                                @endcan

                                @can('enable_companyBankAccount')
                                    <button
                                            type="button"
                                            class="dropdown-item {{ $account->is_active ? 'text-danger' : '' }}"
                                            data-toggle-bank-account
                                            data-id="{{ $account->id }}"
                                            data-active="{{ $account->is_active ? '1' : '0' }}"
                                    >
                                        @if($account->is_active)
                                            <i class="fas fa-ban"></i>
                                            Desactivar
                                        @else
                                            <i class="fas fa-check"></i>
                                            Activar
                                        @endif
                                    </button>
                                @endcan
                                </div></div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/companyBankAccount/index.js') }}"></script>
@endsection