@extends('layouts.appAdmin2')

@section('title')
    Tenants
@endsection

@section('activePlatformTenants')
    active
@endsection


@section('content')

    <div id="platform-tenants-app" class="container-fluid"
         data-url-data="{{route('platformTenant.data')}}"
         data-url-show="{{route('platformTenant.show',['id' => ':id'])}}"
         data-url-create="{{route('platformTenant.create')}}"
    >

        <div class="card card-outline card-primary">

            <div class="card-header">

                <h3 class="card-title">
                    <i class="fas fa-building mr-1"></i>
                    Tenants
                </h3>

                <div class="card-tools">

                    <button
                            type="button"
                            id="btnNewTenant"
                            class="btn btn-success btn-sm"
                    >
                        <i class="fas fa-plus mr-1"></i>
                        Nuevo tenant
                    </button>

                </div>

            </div>


            <div class="card-body">

                <div class="row">

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Buscar
                            </label>

                            <input
                                    type="text"
                                    id="filterSearch"
                                    class="form-control"
                                    placeholder="Nombre del tenant..."
                            >

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="form-group">

                            <label>
                                Plan
                            </label>

                            <select
                                    id="filterPlan"
                                    class="form-control"
                            >

                                <option value="">
                                    Todos
                                </option>

                                @foreach(
                                    $plans as $plan
                                )

                                    <option
                                            value="{{ $plan->id }}"
                                    >
                                        {{ $plan->name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="form-group">

                            <label>
                                Estado
                            </label>

                            <select
                                    id="filterStatus"
                                    class="form-control"
                            >

                                <option value="">
                                    Todos
                                </option>

                                <option value="1">
                                    Activos
                                </option>

                                <option value="0">
                                    Inactivos
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-md-2">

                        <div class="form-group">

                            <label>
                                Mostrar
                            </label>

                            <select
                                    id="filterPerPage"
                                    class="form-control"
                            >

                                <option value="10">
                                    10
                                </option>

                                <option value="25">
                                    25
                                </option>

                                <option value="50">
                                    50
                                </option>

                            </select>

                        </div>

                    </div>

                </div>


                <div class="table-responsive">

                    <table
                            class="
                        table
                        table-bordered
                        table-hover
                    "
                    >

                        <thead>

                        <tr>

                            <th>
                                Tenant
                            </th>

                            <th>
                                Plan
                            </th>

                            <th>
                                Usuarios
                            </th>

                            <th>
                                Companies
                            </th>

                            <th>
                                Owner
                            </th>

                            <th>
                                Estado
                            </th>

                            <th style="width:120px;">
                                Acciones
                            </th>

                        </tr>

                        </thead>


                        <tbody
                                id="tenantTableBody"
                        ></tbody>

                    </table>

                </div>


                <div
                        id="tenantEmpty"
                        class="
                    alert
                    alert-light
                    border
                    text-center
                    d-none
                "
                >
                    No se encontraron tenants.
                </div>


                <div
                        class="
                    d-flex
                    justify-content-between
                    align-items-center
                    flex-wrap
                    mt-3
                "
                >

                    <div
                            id="tenantPaginationInfo"
                            class="text-muted"
                    ></div>

                    <div
                            id="tenantPagination"
                    ></div>

                </div>

            </div>

        </div>

    </div>

@endsection


@section('scripts')

    <script
            src="{{asset('js/platform/tenants/index.js')}}"
    ></script>

@endsection