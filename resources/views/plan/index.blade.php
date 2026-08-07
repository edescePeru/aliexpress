@extends('layouts.appAdmin2')

@section('title')
    Planes
@endsection

@section('styles-plugins')
    <link
            rel="stylesheet"
            href="{{ asset('admin/plugins/select2/css/select2.min.css') }}"
    >
@endsection

@section('page-header')
    <h1 class="page-title">
        Planes Venti360
    </h1>
@endsection

@section('page-title')
    <h5 class="card-title">
        Administración de planes
    </h5>

    <button
            type="button"
            id="btnNewPlan"
            class="btn btn-outline-success btn-sm float-right"
    >
        <i class="fa fa-plus"></i>
        Nuevo plan
    </button>
@endsection

@section('content')

    <div
            id="plan-app"

            data-url-list="{{ route('plan.data') }}"

            data-url-store="{{ route('plan.store') }}"

            data-url-update="{{
            route(
                'plan.update',
                ':id'
            )
        }}"

            data-url-toggle="{{
            route(
                'plan.toggleStatus',
                ':id'
            )
        }}"
    >

        <div class="row mb-3">

            <div class="col-md-6">
                <input
                        type="text"
                        id="searchPlan"
                        class="form-control"
                        placeholder="Buscar por nombre o código..."
                >
            </div>

            <div class="col-md-2">
                <select
                        id="perPagePlan"
                        class="form-control"
                >
                    <option value="10">
                        10
                    </option>

                    <option value="20">
                        20
                    </option>

                    <option value="50">
                        50
                    </option>
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
                    <th>Plan</th>
                    <th>Usuarios</th>
                    <th>Tenants</th>
                    <th>Estado</th>
                    <th width="160">
                        Acciones
                    </th>
                </tr>
                </thead>

                <tbody id="bodyPlans">
                </tbody>
            </table>
        </div>

        <div
                id="paginationPlans"
                class="d-flex justify-content-center"
        ></div>

    </div>


    {{-- MODAL CREATE --}}
    <div
            class="modal fade"
            id="modalCreatePlan"
            tabindex="-1"
    >
        <div class="modal-dialog">

            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">
                        Nuevo plan
                    </h5>

                    <button
                            type="button"
                            class="close"
                            data-dismiss="modal"
                    >
                        <span>
                            &times;
                        </span>
                    </button>
                </div>

                <form id="formCreatePlan">

                    @csrf

                    <div class="modal-body">

                        @include(
                            'plan.partials.form',
                            [
                                'prefix' => 'create'
                            ]
                        )

                    </div>

                    <div class="modal-footer">

                        <button
                                type="button"
                                class="btn btn-outline-secondary"
                                data-dismiss="modal"
                        >
                            Cancelar
                        </button>

                        <button
                                type="submit"
                                class="btn btn-outline-success"
                        >
                            Guardar
                        </button>

                    </div>

                </form>

            </div>

        </div>
    </div>


    {{-- MODAL EDIT --}}
    <div
            class="modal fade"
            id="modalEditPlan"
            tabindex="-1"
    >
        <div class="modal-dialog">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Editar plan
                    </h5>

                    <button
                            type="button"
                            class="close"
                            data-dismiss="modal"
                    >
                        <span>
                            &times;
                        </span>
                    </button>

                </div>

                <form id="formEditPlan">

                    @csrf

                    <input
                            type="hidden"
                            id="edit_plan_id"
                    >

                    <div class="modal-body">

                        @include(
                            'plan.partials.form',
                            [
                                'prefix' => 'edit'
                            ]
                        )

                    </div>

                    <div class="modal-footer">

                        <button
                                type="button"
                                class="btn btn-outline-secondary"
                                data-dismiss="modal"
                        >
                            Cancelar
                        </button>

                        <button
                                type="submit"
                                class="btn btn-outline-primary"
                        >
                            Guardar cambios
                        </button>

                    </div>

                </form>

            </div>

        </div>
    </div>

@endsection


@section('scripts')
    <script src="{{ asset('js/plan/index.js') }}" ></script>
@endsection