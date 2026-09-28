@extends('layouts.appAdmin2')

@section('openConfig')
    menu-open
@endsection

@section('activeConfig')
    active
@endsection

@section('openSubcategory')
    menu-open
@endsection

@section('activeSubcategory')
    active
@endsection

@section('activeCreateSubcategory')
    active
@endsection

@section('title')
    Nuevas subcategorías
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Nuevas subcategorías</h1>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>Categoría y subcategorías</strong>
            <span>Los campos marcados con (*) son obligatorios.</span>
        </div>
        <div class="next-toolbar-actions">
            <button type="reset" class="btn btn-outline-secondary" form="formCreate">Cancelar</button>
            <button type="button" id="btn-submit" class="btn btn-primary" form="formCreate">Guardar subcategorías</button>
        </div>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}">Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('subcategory.index') }}">Subcategorías</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Nuevas</li>
    </ol>
@endsection

@section('content')
    <form id="formCreate" class="form-horizontal" data-url="{{ route('subcategory.store') }}" enctype="multipart/form-data">
        @csrf
        <section class="next-form-section" aria-labelledby="subcategory-create-title">
            <div class="next-section-header">
                <div>
                    <span class="next-section-kicker">01</span>
                    <h2 id="subcategory-create-title">Categoría y subcategorías</h2>
                    <p>Selecciona la categoría padre y agrega una o más subcategorías asociadas.</p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="category_id">Categoría <span class="next-required">(*)</span></label>
                    <select id="category_id" name="category_id" class="form-control select2" required>
                        <option></option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">Cada subcategoría creada quedará asociada a esta categoría.</small>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
                <div>
                    <h3 class="h6 mb-1">Subcategorías</h3>
                    <p class="text-muted mb-0">Agrega el nombre y una descripción opcional para cada registro.</p>
                </div>
                <button type="button" id="add-subcategory" class="btn btn-outline-primary btn-sm mt-2 mt-sm-0">
                    Agregar otra subcategoría
                </button>
            </div>

            <div id="subcategory-container">
                <div class="form-row align-items-end subcategory-group">
                    <div class="form-group col-md-5">
                        <label for="subcategory-name-0">Nombre <span class="next-required">(*)</span></label>
                        <input
                            type="text"
                            class="form-control"
                            id="subcategory-name-0"
                            name="subcategories[0][name]"
                            placeholder="Ej.: Calzado deportivo"
                            onkeyup="mayus(this);"
                            maxlength="255"
                            required
                            autofocus>
                    </div>
                    <div class="form-group col-md-5">
                        <label for="subcategory-description-0">Descripción</label>
                        <input
                            type="text"
                            class="form-control"
                            id="subcategory-description-0"
                            name="subcategories[0][description]"
                            placeholder="Ej.: Calzado para actividad deportiva"
                            onkeyup="mayus(this);"
                            maxlength="255">
                    </div>
                    <div class="form-group col-md-2">
                        <button type="button" class="btn btn-outline-danger btn-block remove-subcategory" aria-label="Quitar subcategoría">
                            Quitar
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </form>
@endsection

@section('plugins')
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
@endsection

@section('scripts')
    <script src="{{ asset('js/subcategory/create.js') }}"></script>
@endsection
