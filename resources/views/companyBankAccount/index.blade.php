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
    <h1 class="page-title">Cuentas bancarias</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Listado de cuentas bancarias</h5>
    @can('create_companyBankAccount')
        <a
                href="{{ route('companyBankAccount.create') }}"
                class="btn btn-primary btn-sm float-right"
        >
            <i class="fas fa-plus mr-1"></i>
            Nueva cuenta
        </a>
    @endcan
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
            id="company-bank-account-index"
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
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                    <tr>
                        <th>Banco</th>
                        <th>Título</th>
                        <th>Número de cuenta</th>
                        <th>CCI</th>
                        <th>Moneda</th>
                        <th>Titular</th>
                        <th>Estado</th>
                        <th style="width:190px;">
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

                            <td>
                                {{ $account->account_number }}
                            </td>

                            <td>
                                {{ $account->cci ?: '—' }}
                            </td>

                            <td>
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

                            <td>
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

                            <td>
                                @can('edit_companyBankAccount')
                                    <a
                                            href="{{ route('companyBankAccount.edit', $account->id) }}"
                                            class="btn btn-warning btn-sm"
                                    >
                                        <i class="fas fa-edit"></i>
                                        Editar
                                    </a>
                                @endcan

                                @can('enable_companyBankAccount')
                                    <button
                                            type="button"
                                            class="btn btn-sm {{ $account->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}"
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