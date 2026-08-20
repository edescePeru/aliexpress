@extends('layouts.appAdmin2')

@section('title')
    Tallas
@endsection

@section('openConfig')
    menu-open
@endsection

@section('activeConfig')
    active
@endsection

@section('openTalla')
    menu-open
@endsection

{{--@section('activeTalla')
    active
@endsection--}}

@section('activeListTalla')
    active
@endsection

@section('page-header')
    <h1 class="page-title">Tallas</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Listado de tallas</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('talla.index') }}"><i class="fa fa-archive"></i> Tallas</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Listado</li>
    </ol>
@endsection

@section('content')

    <div
            id="talla-app"
            class="container-fluid"

            data-url-data="{{route('talla.data')}}"
            data-url-edit="{{route('talla.edit',['id' => ':id'])}}"
            data-url-delete="{{route('talla.destroy')}}"
            data-url-delete-multiple="{{route('talla.deleteMultiple')}}"
            data-can-update="{{auth()->user()->can('update_talla')? 1: 0}}"
            data-can-delete="{{auth()->user()->can('destroy_talla')? 1: 0}}"
    >

        <div class="card card-outline card-primary">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-ruler-combined mr-1"></i>

                    Tallas

                </h3>


                <div class="card-tools">

                    <a
                            href="{{ route('talla.create') }}"
                            class="btn btn-success btn-sm"
                    >

                        <i class="fas fa-plus mr-1"></i>

                        Nueva talla

                    </a>

                </div>

            </div>


            <div class="card-body">

                <div class="row mb-3">

                    <div class="col-md-6">

                        <div class="form-group mb-0">

                            <label>
                                Buscar
                            </label>

                            <input
                                    type="text"
                                    id="tallaSearch"
                                    class="form-control"
                                    placeholder="Nombre, nombre corto o descripción..."
                            >

                        </div>

                    </div>


                    <div class="col-md-2">

                        <div class="form-group mb-0">

                            <label>
                                Mostrar
                            </label>

                            <select
                                    id="tallaPerPage"
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

                        <div class="form-group mb-0 w-100">

                            <button
                                    type="button"
                                    id="btnDeleteSelectedTallas"
                                    class="
                                btn
                                btn-outline-danger
                                btn-block
                            "
                                    disabled
                            >

                                <i class="fas fa-trash mr-1"></i>

                                Eliminar seleccionadas

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

                            <th
                                    class="text-center"
                                    style="width:45px;"
                            >

                                <input
                                        type="checkbox"
                                        id="checkAllTallas"
                                >

                            </th>


                            <th>
                                Nombre
                            </th>

                            <th style="width:160px;">
                                Nombre corto
                            </th>

                            <th>
                                Descripción
                            </th>

                            <th style="width:170px;">
                                Acciones
                            </th>

                        </tr>

                        </thead>


                        <tbody id="tallaTableBody">
                        </tbody>

                    </table>

                </div>


                <div
                        id="tallaEmpty"
                        class="
                    alert
                    alert-light
                    border
                    text-center
                    d-none
                "
                >

                    No se encontraron tallas.

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
                            id="tallaPaginationInfo"
                            class="text-muted"
                    ></div>


                    <div
                            id="tallaPagination"
                    ></div>

                </div>

            </div>

        </div>

    </div>

@endsection


@section('scripts')

    <script src="{{ asset('js/talla/index.js') }}"></script>

@endsection