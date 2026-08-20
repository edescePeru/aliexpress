@extends('layouts.appAdmin2')

@section('title')
    Crear talla
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

@section('activeCreateTalla')
    active
@endsection

@section('page-header')
    <h1 class="page-title">Tallas</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Crear tallas</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('talla.index') }}"><i class="fa fa-archive"></i> Tallas</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Nueva</li>
    </ol>
@endsection

@section('content')

    <div
            id="talla-form-app"
            class="container-fluid"

            data-url-store="{{route('talla.store')}}"
            data-url-index="{{route('talla.index')}}"
    >

        <div class="row">

            <div
                    class="
                col-lg-12
                col-md-12
                mx-auto
            "
            >

                <div
                        class="
                    card
                    card-outline
                    card-primary
                "
                >

                    <div class="card-header">

                        <h3 class="card-title">

                            <i
                                    class="
                                fas
                                fa-ruler-combined
                                mr-1
                            "
                            ></i>

                            Nueva talla

                        </h3>

                    </div>


                    <form id="formTalla">

                        @csrf


                        <div class="card-body">

                            <div class="row">

                                <div class="col-md-8">

                                    <div class="form-group">

                                        <label for="name">

                                            Nombre

                                            <span class="text-danger">
                                            *
                                        </span>

                                        </label>

                                        <input
                                                type="text"
                                                id="name"
                                                name="name"
                                                class="form-control"
                                                maxlength="191"
                                                placeholder="Ej. TALLA 41, L, 256 GB"
                                                required
                                        >

                                    </div>

                                </div>


                                <div class="col-md-4">

                                    <div class="form-group">

                                        <label for="short_name">
                                            Nombre corto
                                        </label>

                                        <input
                                                type="text"
                                                id="short_name"
                                                name="short_name"
                                                class="form-control"
                                                maxlength="191"
                                                placeholder="Ej. 41, L, 256GB"
                                        >

                                    </div>

                                </div>

                            </div>


                            <div class="form-group">

                                <label for="description">
                                    Descripción
                                </label>

                                <input
                                        type="text"
                                        id="description"
                                        name="description"
                                        class="form-control"
                                        maxlength="255"
                                        placeholder="Descripción opcional"
                                >

                            </div>


                            <div class="alert alert-light border mb-0">

                                <i class="fas fa-info-circle mr-1"></i>

                                La talla se utilizará como atributo de
                                las variantes. También puede representar
                                valores como capacidad o presentación,
                                por ejemplo 256 GB o 1 TB.

                            </div>

                        </div>


                        <div class="card-footer text-right">

                            <a
                                    href="{{ route('talla.index') }}"
                                    class="
                                btn
                                btn-outline-secondary
                                mr-2
                            "
                            >
                                Cancelar
                            </a>


                            <button
                                    type="submit"
                                    id="btnSaveTalla"
                                    class="btn btn-success"
                            >

                                <i class="fas fa-save mr-1"></i>

                                Guardar talla

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

@endsection


@section('scripts')

    <script src="{{ asset('js/talla/form.js') }}"></script>

@endsection