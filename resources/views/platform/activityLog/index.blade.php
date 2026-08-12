@extends('layouts.appAdmin2')

@section('title')
    Auditoría
@endsection

@section('activePlatformActivity')
    active
@endsection


@section('content')

    <div
            class="container-fluid"

            id="platform-activity-app"

            data-url-data="{{route('platformActivity.data')}}"

            data-url-show="{{route('platformActivity.show',['id' => ':id'])}}"
    >

        <div class="card card-outline card-primary">

            <div class="card-header">

                <h3 class="card-title">

                    <i
                            class="
                        fas
                        fa-history
                        mr-1
                    "
                    ></i>

                    Auditoría de plataforma

                </h3>

            </div>


            <div class="card-body">

                <div class="row">

                    <div class="col-lg-3 col-md-6">

                        <div class="form-group">

                            <label>
                                Buscar
                            </label>

                            <input
                                    type="text"
                                    id="filterSearch"
                                    class="form-control"
                                    placeholder="Acción o entidad..."
                            >

                        </div>

                    </div>


                    <div class="col-lg-3 col-md-6">

                        <div class="form-group">

                            <label>
                                Acción
                            </label>

                            <select
                                    id="filterAction"
                                    class="form-control"
                            >

                                <option value="">
                                    Todas
                                </option>

                                @foreach(
                                    $actions as $action
                                )

                                    <option
                                            value="{{
                                        $action
                                    }}"
                                    >
                                        {{ $action }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>


                    <div class="col-lg-3 col-md-6">

                        <div class="form-group">

                            <label>
                                Tenant
                            </label>

                            <select
                                    id="filterTenant"
                                    class="form-control"
                            >

                                <option value="">
                                    Todos
                                </option>

                                @foreach(
                                    $tenants as $tenant
                                )

                                    <option
                                            value="{{
                                        $tenant->id
                                    }}"
                                    >
                                        {{ $tenant->name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>


                    <div class="col-lg-3 col-md-6">

                        <div class="form-group">

                            <label>
                                Administrador
                            </label>

                            <select
                                    id="filterCauser"
                                    class="form-control"
                            >

                                <option value="">
                                    Todos
                                </option>

                                @foreach(
                                    $platformUsers
                                    as $platformUser
                                )

                                    <option
                                            value="{{
                                        $platformUser->id
                                    }}"
                                    >
                                        {{
                                            $platformUser->name
                                        }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                </div>


                <div class="row">

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>
                                Desde
                            </label>

                            <input
                                    type="date"
                                    id="filterStartDate"
                                    class="form-control"
                            >

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="form-group">

                            <label>
                                Hasta
                            </label>

                            <input
                                    type="date"
                                    id="filterEndDate"
                                    class="form-control"
                            >

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


                    <div
                            class="
                        col-md-4
                        d-flex
                        align-items-end
                    "
                    >

                        <div
                                class="
                            form-group
                            w-100
                        "
                        >

                            <button
                                    type="button"
                                    id="btnClearFilters"
                                    class="
                                btn
                                btn-outline-secondary
                                btn-block
                            "
                            >

                                <i
                                        class="
                                    fas
                                    fa-eraser
                                    mr-1
                                "
                                ></i>

                                Limpiar filtros

                            </button>

                        </div>

                    </div>

                </div>


                <div class="table-responsive">

                    <table
                            class="
                        table
                        table-bordered
                        table-hover
                        table-sm
                    "
                    >

                        <thead>

                        <tr>

                            <th style="width:75px;">
                                ID
                            </th>

                            <th>
                                Fecha
                            </th>

                            <th>
                                Acción
                            </th>

                            <th>
                                Tenant
                            </th>

                            <th>
                                Administrador
                            </th>

                            <th>
                                Entidad
                            </th>

                            <th style="width:90px;">
                                Acción
                            </th>

                        </tr>

                        </thead>


                        <tbody
                                id="activityTableBody"
                        ></tbody>

                    </table>

                </div>


                <div
                        id="activityEmpty"
                        class="
                    alert
                    alert-light
                    border
                    text-center
                    d-none
                "
                >

                    No se encontraron registros
                    de auditoría.

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
                            id="activityPaginationInfo"
                            class="
                        text-muted
                        mb-2
                        mb-md-0
                    "
                    ></div>

                    <div
                            id="activityPagination"
                    ></div>

                </div>

            </div>

        </div>

    </div>

@endsection


@section('scripts')

    <script
            src="{{asset('js/platform/activityLog/index.js')}}"
    ></script>

@endsection