@extends('layouts.appAdmin2')

@section('title')
    Colores
@endsection

@section('openConfig')
    menu-open
@endsection

@section('activeConfig')
    active
@endsection

@section('openColor')
    menu-open
@endsection

{{--@section('activeColor')
    active
@endsection--}}

@section('activeListColor')
    active
@endsection

@section('page-header')
    <h1 class="page-title">Colores</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Listado de colores</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('color.index') }}"><i class="fa fa-archive"></i> Colores</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Listado</li>
    </ol>
@endsection

@section('content')

    <div
            id="color-app"
            class="container-fluid"

            data-url-data="{{ route('color.data') }}"

            data-url-edit="{{route('color.edit',['id' => ':id'])}}"

            data-url-delete="{{route('color.destroy')}}"

            data-url-delete-multiple="{{route('color.deleteMultiple')}}"

            data-can-update="{{auth()->user()->can('update_color')? 1: 0}}"

            data-can-delete="{{auth()->user()->can('destroy_color')? 1: 0}}"

    >

        <div class="card card-outline card-primary">

            <div class="card-header">

                <h3 class="card-title">
                    <i class="fas fa-palette mr-1"></i>
                    Colores
                </h3>

                <div class="card-tools">

                    @can('create_color')

                        <a href="{{ route('color.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus mr-1"></i>
                            Nuevo color
                        </a>

                    @endcan

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
                                    id="colorSearch"
                                    class="form-control"
                                    placeholder="Buscar color..."
                            >

                        </div>

                    </div>


                    <div class="col-md-2">

                        <div class="form-group mb-0">

                            <label>
                                Mostrar
                            </label>

                            <select
                                    id="colorPerPage"
                                    class="form-control"
                            >
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>

                        </div>

                    </div>


                    @can('destroy_color')

                        <div class=" col-md-4 d-flex align-items-end">

                            <div class="form-group mb-0 w-100">

                                <button
                                        type="button"
                                        id="btnDeleteSelectedColors"
                                        class="btn btn-outline-danger btn-block"
                                        disabled
                                >
                                    <i class="fas fa-trash mr-1"></i>
                                    Eliminar seleccionados
                                </button>

                            </div>

                        </div>

                    @endcan

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

                            @can('destroy_color')
                                <th
                                        class="text-center"
                                        style="width:45px;"
                                >
                                    <input
                                            type="checkbox"
                                            id="checkAllColors"
                                    >
                                </th>
                            @endcan

                            <th style="width:80px;">
                                Color
                            </th>

                            <th>
                                Nombre
                            </th>

                            <th>
                                Código
                            </th>

                            <th>
                                Nombre corto
                            </th>

                            <th style="width:170px;">
                                Acciones
                            </th>

                        </tr>

                        </thead>


                        <tbody id="colorTableBody">

                        </tbody>

                    </table>

                </div>


                <div
                        id="colorEmpty"
                        class="
                    alert
                    alert-light
                    border
                    text-center
                    d-none
                "
                >
                    No se encontraron colores.
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
                            id="colorPaginationInfo"
                            class="text-muted"
                    ></div>

                    <div
                            id="colorPagination"
                    ></div>

                </div>

            </div>

        </div>

    </div>

@endsection


@section('scripts')

    <script src="{{ asset('js/color/index.js') }}"></script>

@endsection