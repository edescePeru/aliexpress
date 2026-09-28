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

@section('activeListSubcategory')
    active
@endsection

@section('title')
    Editar subcategoría
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Editar subcategoría</h1>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>{{ $subcategory->name }}</strong>
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
            <a href="{{ route('subcategory.index') }}">Subcategorías</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Editar</li>
    </ol>
@endsection

@section('content')
    <form id="formEdit" class="form-horizontal" data-url="{{ route('subcategory.update') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="subcategory_id" value="{{ $subcategory->id }}">

        <section class="next-form-section" aria-labelledby="subcategory-edit-title">
            <div class="next-section-header">
                <div>
                    <span class="next-section-kicker">01</span>
                    <h2 id="subcategory-edit-title">Categoría y subcategoría</h2>
                    <p>Actualiza la categoría padre, el nombre o la descripción del registro.</p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="category_id">Categoría <span class="next-required">(*)</span></label>
                    <select id="category_id" name="category_id" class="form-control select2" required>
                        <option></option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ $category->id === $subcategory->category_id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">La categoría seleccionada determina la relación padre de esta subcategoría.</small>
                </div>

                <div class="form-group col-md-4">
                    <label for="subcategory-name">Subcategoría <span class="next-required">(*)</span></label>
                    <input
                        type="text"
                        class="form-control"
                        id="subcategory-name"
                        name="name"
                        onkeyup="mayus(this);"
                        placeholder="Ej.: Calzado deportivo"
                        value="{{ $subcategory->name }}"
                        maxlength="255"
                        required
                        autofocus>
                </div>

                <div class="form-group col-md-4">
                    <label for="subcategory-description">Descripción</label>
                    <input
                        type="text"
                        class="form-control"
                        id="subcategory-description"
                        name="description"
                        onkeyup="mayus(this);"
                        placeholder="Ej.: Calzado para actividad deportiva"
                        value="{{ $subcategory->description }}"
                        maxlength="255">
                </div>
            </div>
        </section>
    </form>
@endsection

@section('plugins')
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
@endsection

@section('scripts')
    <script src="{{ asset('js/subcategory/edit.js') }}"></script>
@endsection
