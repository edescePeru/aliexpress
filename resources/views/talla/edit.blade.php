@extends('layouts.appAdmin2')

@section('title')
    Editar talla
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
    <h5 class="card-title">Editar tallas</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('talla.index') }}"><i class="fa fa-archive"></i> Tallas</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Editar</li>
    </ol>
@endsection

@section('content')

    <div
            id="talla-form-app"
            class="container-fluid"

            data-url-update="{{route('talla.update')}}"
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

                            Editar talla

                        </h3>

                    </div>


                    <form id="formTalla">

                        @csrf


                        <input
                                type="hidden"
                                name="talla_id"
                                value="{{ $talla->id }}"
                        >


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
                                                value="{{ $talla->name }}"
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
                                                value="{{
                                            $talla->short_name
                                        }}"
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
                                        value="{{
                                    $talla->description
                                }}"
                                >

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

                                Guardar cambios

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