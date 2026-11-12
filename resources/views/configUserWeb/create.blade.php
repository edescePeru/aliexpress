@extends('layouts.appAdmin2')
@section('openDataGeneral')
    menu-open
@endsection
@section('activeDataGeneral')
    active
@endsection
@section('activeConfigUserWeb')
    active
@endsection
@section('title')
    Nuevo usuario
@endsection
@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Configuración · Accesos</span><h1 class="page-title">Nuevo usuario</h1><p class="next-page-description">Usuarios del grupo empresarial.</p></div>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Nuevo usuario</strong><span>Acceso y contexto autorizado del usuario.</span></div>
                <div
                        class="
                        next-toolbar-actions flex-wrap
                        justify-content-end
                    "
                >
                    <a
                            href="{{ route('configUserWeb.index') }}"
                            class="btn btn-outline-secondary"
                    >
                        Cancelar
                    </a>
                    <button
                            type="submit" form="formCreateTenantUser"
                            id="btnSaveTenantUser"
                            class="btn btn-primary"
                    >
                        <i class="fas fa-save mr-1"></i>
                        Crear usuario
                    </button>
                </div></div>
@endsection
@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a
                    href="{{ route('dashboard.principal') }}"
            >
                <i class="fa fa-home"></i>
                Dashboard
            </a>
        </li>
        <li class="breadcrumb-item">
            <a
                    href="{{ route('configUserWeb.index') }}"
            >
                Usuarios
            </a>
        </li>
        <li class="breadcrumb-item active">
            Nuevo
        </li>
    </ol>
@endsection
@section('content')
    <div
            id="config-user-create-app"
            data-url-index="{{
            route('configUserWeb.index')
        }}"
            data-url-store="{{
            route('configUserWeb.store')
        }}"
    >
        <div class="next-form-section">
            <strong>
                Tenant:
            </strong>
            {{ $tenant->name }}
            <br>
            <small class="text-muted">
                El usuario será creado dentro
                de este grupo empresarial.
            </small>
        </div>
        <form id="formCreateTenantUser">
            @csrf
            <div class="next-form-section">
                <div class="next-section-header">
                    <h2>
                        Datos de acceso
                    </h2>
                </div>
                <div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">
                                    Nombre
                                    <span class="badge badge-danger">
                                        *
                                    </span>
                                </label>
                                <input
                                        type="text"
                                        id="name"
                                        name="name"
                                        class="form-control"
                                        maxlength="255"
                                        required
                                >
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">
                                    Correo electrónico
                                    <span class="badge badge-danger">
                                        *
                                    </span>
                                </label>
                                <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        class="form-control"
                                        maxlength="255"
                                        required
                                >
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="role_id">
                                    Perfil de acceso
                                    <span class="badge badge-danger">
                                        *
                                    </span>
                                </label>
                                <select
                                        id="role_id"
                                        name="role_id"
                                        class="form-control"
                                        required
                                >
                                    <option value="">
                                        Seleccione un perfil
                                    </option>
                                    @foreach(
                                        $roles as $role
                                    )
                                        <option
                                                value="{{ $role->id }}"
                                        >
                                            {{
                                                $role->description
                                                ?: $role->name
                                            }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="next-form-section">
                <div class="next-section-header">
                    <h2>
                        Empresas y locales autorizados
                    </h2>
                </div>
                <div>
                    @forelse(
                        $companies as $company
                    )
                        <div
                                class="
                                card
                                mb-3
                            "
                                data-company-card="{{
                                $company->id
                            }}"
                        >
                            <div class="card-header">
                                <div
                                        class="
                                        custom-control
                                        custom-checkbox
                                    "
                                >
                                    <input
                                            type="checkbox"
                                            class="
                                            custom-control-input
                                            company-checkbox
                                        "
                                            id="company_{{
                                            $company->id
                                        }}"
                                            name="companies[]"
                                            value="{{
                                            $company->id
                                        }}"
                                    >
                                    <label
                                            class="
                                            custom-control-label
                                            font-weight-bold
                                        "
                                            for="company_{{
                                            $company->id
                                        }}"
                                    >
                                        {{
                                            $company->trade_name
                                            ?: $company->business_name
                                        }}
                                    </label>
                                </div>
                            </div>
                            <div class="next-form-section">
                                @forelse(
                                    $company->branches
                                    as $branch
                                )
                                    <div class="py-1">
                                        <div
                                                class="
                                                custom-control
                                                custom-checkbox
                                            "
                                        >
                                            <input
                                                    type="checkbox"
                                                    class="
                                                    custom-control-input
                                                    branch-checkbox
                                                "
                                                    id="branch_{{
                                                    $branch->id
                                                }}"
                                                    name="branches[]"
                                                    value="{{
                                                    $branch->id
                                                }}"
                                                    data-company-id="{{
                                                    $company->id
                                                }}"
                                                    disabled
                                            >
                                            <label
                                                    class="custom-control-label"
                                                    for="branch_{{
                                                    $branch->id
                                                }}"
                                            >
                                                {{ $branch->name }}
                                                @if(
                                                    $branch->is_main
                                                )
                                                    <span
                                                            class="
                                                            badge
                                                            badge-info
                                                            ml-1
                                                        "
                                                    >
                                                        Principal
                                                    </span>
                                                @endif
                                            </label>
                                        </div>
                                    </div>
                                @empty
                                    <span class="text-muted">
                                        Esta empresa no tiene
                                        locales activos.
                                    </span>
                                @endforelse
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-warning">
                            El tenant no tiene empresas activas.
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="next-form-section">
                <div class="next-section-header">
                    <h2>
                        Contexto inicial
                    </h2>
                </div>
                <div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="default_company_id">
                                    Empresa predeterminada
                                </label>
                                <select
                                        id="default_company_id"
                                        name="default_company_id"
                                        class="form-control"
                                        required
                                >
                                    <option value="">
                                        Seleccione una empresa
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="default_branch_id">
                                    Local predeterminado
                                </label>
                                <select
                                        id="default_branch_id"
                                        name="default_branch_id"
                                        class="form-control"
                                        required
                                >
                                    <option value="">
                                        Seleccione un local
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
@section('scripts')
    <script
            src="{{ asset('js/configUserWeb/create.js') }}"
    ></script>
@endsection