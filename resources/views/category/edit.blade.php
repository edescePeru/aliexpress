@extends('layouts.appAdmin2')

@section('openConfig')
    menu-open
@endsection

@section('activeConfig')
    active
@endsection

@section('openCategory')
    menu-open
@endsection

@section('activeCategory')
    active
@endsection

@section('activeListCategory')
    active
@endsection

@section('title')
    Editar categoría
@endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Editar categoría</h1>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>{{ $category->name }}</strong>
            <span>Los campos marcados con (*) son obligatorios.</span>
        </div>
        <div class="next-toolbar-actions">
            <button type="reset" class="btn btn-outline-secondary" form="formEdit">Cancelar</button>
            <button type="submit" class="btn btn-primary" form="formEdit">Guardar cambios</button>
        </div>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}">Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('category.index') }}">Categorías</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Editar</li>
    </ol>
@endsection

@section('content')
    <form id="formEdit" class="form-horizontal" data-url="{{ route('category.update') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="category_id" value="{{ $category->id }}">

        <section class="next-form-section" aria-labelledby="category-edit-title">
            <div class="next-section-header">
                <div>
                    <span class="next-section-kicker">01</span>
                    <h2 id="category-edit-title">Información general</h2>
                    <p>Actualiza el nombre operativo o la descripción de la categoría.</p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="category-name">Categoría <span class="next-required">(*)</span></label>
                    <input
                        type="text"
                        class="form-control"
                        id="category-name"
                        name="name"
                        onkeyup="mayus(this);"
                        placeholder="Ej.: Accesorios"
                        value="{{ $category->name }}"
                        maxlength="255"
                        required
                        autofocus>
                </div>

                <div class="form-group col-md-6">
                    <label for="category-description">Descripción</label>
                    <input
                        type="text"
                        class="form-control"
                        id="category-description"
                        name="description"
                        onkeyup="mayus(this);"
                        placeholder="Ej.: Materiales y accesorios complementarios"
                        value="{{ $category->description }}"
                        maxlength="255">
                </div>
            </div>
        </section>
    </form>
@endsection

@section('scripts')
    <script src="{{ asset('js/Category/edit.js') }}"></script>
@endsection
