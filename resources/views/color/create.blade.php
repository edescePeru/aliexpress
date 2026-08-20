@extends('layouts.appAdmin2')

@section('title')
    Crear color
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

@section('activeCreateColor')
    active
@endsection

@section('page-header')
    <h1 class="page-title">Colores</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Nuevo color</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('color.index') }}"><i class="fa fa-archive"></i> Colores</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Nuevo</li>
    </ol>
@endsection

@section('content')

    <div
            id="color-form-app"
            class="container-fluid"

            data-url-store="{{ route('color.store')}}"

            data-url-index="{{ route('color.index')}}"
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
                                fa-palette
                                mr-1
                            "
                            ></i>

                            Nuevo color

                        </h3>

                    </div>


                    <form id="formColor">

                        @csrf


                        <div class="card-body">

                            <div class="row">

                                <div class="col-md-8">

                                    <div class="form-group">

                                        <label for="name">
                                            Nombre
                                            <span
                                                    class="text-danger"
                                            >
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


                                <div class="col-md-4">

                                    <div class="form-group">

                                        <label for="short_name">
                                            Nombre corto
                                            <span
                                                    class="text-danger"
                                            >
                                            *
                                        </span>
                                        </label>

                                        <input
                                                type="text"
                                                id="short_name"
                                                name="short_name"
                                                class="form-control"
                                                maxlength="255"
                                                required
                                        >

                                    </div>

                                </div>

                            </div>


                            <div class="row">

                                <div class="col-md-8">

                                    <div class="form-group">

                                        <label for="code">
                                            Código hexadecimal
                                        </label>


                                        <div class="input-group">

                                            <input
                                                    type="text"
                                                    id="code"
                                                    name="code"
                                                    class="form-control"
                                                    maxlength="7"
                                                    placeholder="#FFFFFF"
                                            >


                                            <div
                                                    class="
                                                input-group-append
                                            "
                                            >

                                                <input
                                                        type="color"
                                                        id="colorPicker"
                                                        value="#ffffff"

                                                        style="
                                                    width:52px;
                                                    height:38px;
                                                    padding:2px;
                                                    border:
                                                        1px
                                                        solid
                                                        #ced4da;
                                                "
                                                >

                                            </div>

                                        </div>


                                        <small
                                                class="
                                            form-text
                                            text-muted
                                        "
                                        >
                                            Ejemplo:
                                            #FFFFFF,
                                            #000000,
                                            #2B3A4A.
                                        </small>

                                    </div>

                                </div>


                                <div class="col-md-4">

                                    <div class="form-group">

                                        <label>
                                            Vista previa
                                        </label>

                                        <div
                                                id="colorPreview"
                                                style="
                                            width:100%;
                                            height:38px;
                                            border-radius:4px;
                                            border:
                                                1px solid
                                                #ced4da;
                                            background:#ffffff;
                                        "
                                        ></div>

                                    </div>

                                </div>

                            </div>

                        </div>


                        <div class="card-footer text-right">

                            <a
                                    href="{{
                                route(
                                    'color.index'
                                )
                            }}"
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
                                    id="btnSaveColor"
                                    class="btn btn-success"
                            >

                                <i
                                        class="
                                    fas
                                    fa-save
                                    mr-1
                                "
                                ></i>

                                Guardar color

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

@endsection


@section('scripts')

    <script src="{{ asset('js/color/form.js') }}"></script>

@endsection