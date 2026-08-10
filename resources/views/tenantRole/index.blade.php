@extends('layouts.appAdmin2')

@section('title')
    Roles por Tenant
@endsection

@section('page-header')
    <h1 class="page-title">
        Roles por Tenant
    </h1>
@endsection

@section('page-title')
    <h5 class="card-title">
        Administración técnica de perfiles
    </h5>

    <button
            type="button"
            id="btnNewTenantRole"
            class="btn btn-outline-success btn-sm float-right"
            disabled
    >
        <i class="fas fa-plus"></i>
        Nuevo rol personalizado
    </button>
@endsection

@section('content')

    <div
            id="tenant-role-app"

            data-url-list="{{ route('tenantRole.data') }}"

            data-url-create="{{
            route(
                'tenantRole.create',
                ':tenantId'
            )
        }}"

            data-url-edit="{{
            route(
                'tenantRole.edit',
                [
                    ':tenantId',
                    ':roleId'
                ]
            )
        }}"

            data-url-toggle="{{
            route(
                'tenantRole.toggleStatus',
                [
                    ':tenantId',
                    ':roleId'
                ]
            )
        }}"
    >

        <div class="row mb-3">

            <div class="col-lg-5 col-md-6">

                <label for="tenantRoleTenant">
                    Tenant
                </label>

                <select
                        id="tenantRoleTenant"
                        class="form-control"
                >
                    <option value="">
                        Seleccione un tenant
                    </option>

                    @foreach($tenants as $tenant)
                        <option
                                value="{{ $tenant->id }}"
                                {{
                                    $selectedTenantId === $tenant->id
                                        ? 'selected'
                                        : ''
                                }}
                        >
                            {{ $tenant->name }}
                        </option>
                    @endforeach

                </select>

            </div>


            <div class="col-lg-5 col-md-4">

                <label for="searchTenantRole">
                    Buscar
                </label>

                <input
                        type="text"
                        id="searchTenantRole"
                        class="form-control"
                        placeholder="Código o descripción..."
                        disabled
                >

            </div>


            <div class="col-lg-2 col-md-2">

                <label for="perPageTenantRole">
                    Mostrar
                </label>

                <select
                        id="perPageTenantRole"
                        class="form-control"
                        disabled
                >
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>

            </div>

        </div>


        <div class="table-responsive">

            <table
                    class="table table-bordered table-hover"
            >
                <thead>
                <tr>
                    <th>#</th>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th>Origen</th>
                    <th>Tipo</th>
                    <th>Permisos</th>
                    <th>Owner</th>
                    <th>Estado</th>
                    <th width="185">
                        Acciones
                    </th>
                </tr>
                </thead>

                <tbody id="bodyTenantRoles">

                <tr>
                    <td
                            colspan="9"
                            class="text-center text-muted"
                    >
                        Seleccione un tenant.
                    </td>
                </tr>

                </tbody>

            </table>

        </div>


        <div
                id="paginationTenantRoles"
                class="d-flex justify-content-center"
        ></div>

    </div>

@endsection


@section('scripts')
    <script
            src="{{ asset('js/tenantRole/index.js') }}"
    ></script>
@endsection