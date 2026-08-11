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
    Editar usuario
@endsection

@section('page-header')
    <h1 class="page-title">
        Usuarios del negocio
    </h1>
@endsection

@section('page-title')
    <h5 class="card-title">
        Editar usuario:
        {{ $userEdit->name }}
    </h5>
@endsection

@section('page-breadcrumb')

    <ol class="breadcrumb float-sm-right">

        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}">
                <i class="fa fa-home"></i>
                Dashboard
            </a>
        </li>

        <li class="breadcrumb-item">
            <a href="{{ route('configUserWeb.index') }}">
                Usuarios
            </a>
        </li>

        <li class="breadcrumb-item active">
            Editar
        </li>

    </ol>

@endsection


@section('styles')

    <style>

        .company-access-card {
            border: 1px solid #dee2e6;
            margin-bottom: 15px;
        }

        .company-access-card .card-header {
            background: #f4f6f9;
        }

        .branch-item {
            padding: 5px 0;
        }

        .user-edit-action-bar {
            position: sticky;
            bottom: 0;
            z-index: 1000;

            background: #ffffff;

            border-top:
                    1px solid #dee2e6;

            box-shadow:
                    0 -2px 7px
                    rgba(0, 0, 0, 0.08);

            padding: 12px;

            margin-top: 25px;
        }

    </style>

@endsection


@section('content')

    <div id="config-user-edit-app"

         data-url-index="{{route('configUserWeb.index')}}"

         data-url-update="{{route('configUserWeb.update',$userEdit->id)}}"

         data-default-company-id="{{ $defaultCompanyId }}"

         data-default-branch-id="{{ $defaultBranchId }}"
    >

        <div class="alert alert-light border">

            <strong>
                Tenant:
            </strong>

            {{ $tenant->name }}

            @if($userEdit->is_tenant_owner)

                <br>

                <span class="badge badge-primary mt-2">
                    Propietario del Tenant
                </span>

                <small
                        class="text-muted ml-2"
                >
                    El perfil y alcance del propietario
                    no pueden modificarse desde este módulo.
                </small>

            @endif

        </div>


        <form
                id="formEditTenantUser"
                enctype="multipart/form-data"
        >

            @csrf


            <div
                    class="
                    card
                    card-outline
                    card-primary
                "
            >

                <div class="card-header">

                    <h3 class="card-title">
                        Datos de acceso
                    </h3>

                </div>


                <div class="card-body">

                    <div class="row">

                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="name">
                                    Nombre
                                </label>

                                <input
                                        type="text"
                                        id="name"
                                        name="name"
                                        class="form-control"
                                        value="{{ $userEdit->name }}"
                                        required
                                >

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="email">
                                    Correo electrónico
                                </label>

                                <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        class="form-control"
                                        value="{{ $userEdit->email }}"
                                        required
                                >

                            </div>

                        </div>

                    </div>


                    <div class="row">

                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="image">
                                    Imagen
                                </label>

                                <input
                                        type="file"
                                        id="image"
                                        name="image"
                                        class="form-control"
                                        accept="image/*"
                                >

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="form-group">

                                <label>
                                    Imagen actual
                                </label>

                                <div>

                                    <img
                                            id="imagePreview"

                                            src="{{
                                            $userEdit->image
                                                ? asset(
                                                    'images/users/' .
                                                    $userEdit->image
                                                )
                                                : asset(
                                                    'images/users/no_image.png'
                                                )
                                        }}"

                                            style="
                                            width:80px;
                                            height:80px;
                                            object-fit:cover;
                                            border-radius:50%;
                                        "
                                    >

                                </div>

                            </div>

                        </div>

                    </div>


                    @if(!$userEdit->is_tenant_owner)

                        <div class="row">

                            <div class="col-md-6">

                                <div class="form-group">

                                    <label for="role_id">
                                        Perfil
                                    </label>

                                    <select
                                            id="role_id"
                                            name="role_id"
                                            class="form-control"
                                            required
                                    >

                                        <option value="">
                                            Seleccione
                                        </option>

                                        @foreach(
                                            $roles as $role
                                        )

                                            <option
                                                    value="{{ $role->id }}"

                                                    {{
                                                        $currentRole &&
                                                        $currentRole->id
                                                            ===
                                                        $role->id
                                                            ? 'selected'
                                                            : ''
                                                    }}
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

                    @else

                        <div class="form-group">

                            <label>
                                Perfil
                            </label>

                            <input
                                    type="text"
                                    class="form-control"
                                    value="{{
                                    $currentRole
                                        ? (
                                            $currentRole->description
                                            ?: $currentRole->name
                                        )
                                        : 'Propietario'
                                }}"
                                    readonly
                            >

                        </div>

                    @endif

                </div>

            </div>


            @if(!$userEdit->is_tenant_owner)

                <div
                        class="
                        card
                        card-outline
                        card-secondary
                    "
                >

                    <div class="card-header">

                        <h3 class="card-title">
                            Empresas y locales autorizados
                        </h3>

                    </div>


                    <div class="card-body">

                        @foreach(
                            $companies as $company
                        )

                            <div
                                    class="
                                    card
                                    company-access-card
                                "
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

                                                {{
                                                    in_array(
                                                        $company->id,
                                                        $selectedCompanyIds
                                                    )
                                                        ? 'checked'
                                                        : ''
                                                }}
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


                                <div class="card-body">

                                    @foreach(
                                        $company->branches
                                        as $branch
                                    )

                                        <div class="branch-item">

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

                                                        {{
                                                            in_array(
                                                                $branch->id,
                                                                $selectedBranchIds
                                                            )
                                                                ? 'checked'
                                                                : ''
                                                        }}

                                                        {{
                                                            in_array(
                                                                $company->id,
                                                                $selectedCompanyIds
                                                            )
                                                                ? ''
                                                                : 'disabled'
                                                        }}
                                                >

                                                <label
                                                        class="custom-control-label"
                                                        for="branch_{{
                                                        $branch->id
                                                    }}"
                                                >

                                                    {{ $branch->name }}

                                                </label>

                                            </div>

                                        </div>

                                    @endforeach

                                </div>

                            </div>

                        @endforeach

                    </div>

                </div>


                <div
                        class="
                        card
                        card-outline
                        card-info
                    "
                >

                    <div class="card-header">

                        <h3 class="card-title">
                            Contexto inicial
                        </h3>

                    </div>


                    <div class="card-body">

                        <div class="row">

                            <div class="col-md-6">

                                <div class="form-group">

                                    <label
                                            for="default_company_id"
                                    >
                                        Empresa predeterminada
                                    </label>

                                    <select
                                            id="default_company_id"
                                            name="default_company_id"
                                            class="form-control"
                                            required
                                    ></select>

                                </div>

                            </div>


                            <div class="col-md-6">

                                <div class="form-group">

                                    <label
                                            for="default_branch_id"
                                    >
                                        Local predeterminado
                                    </label>

                                    <select
                                            id="default_branch_id"
                                            name="default_branch_id"
                                            class="form-control"
                                            required
                                    ></select>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            @endif


            <div class="user-edit-action-bar">

                <div
                        class="
                        d-flex
                        justify-content-end
                    "
                >

                    <a
                            href="{{ route('configUserWeb.index') }}"
                            class="btn btn-outline-secondary mr-2"
                    >
                        Cancelar
                    </a>

                    <button
                            type="submit"
                            id="btnSaveTenantUser"
                            class="btn btn-success"
                    >
                        <i class="fas fa-save mr-1"></i>
                        Guardar cambios
                    </button>

                </div>

            </div>

        </form>

    </div>

@endsection


@section('scripts')

    <script src="{{ asset('js/configUserWeb/edit.js') }}"></script>

@endsection