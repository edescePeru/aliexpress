@extends('layouts.appAdmin2')

@section('openConfig')
    menu-open
@endsection

@section('activeConfig')
    active
@endsection

@section('openMaterialType')
    menu-open
@endsection

@section('activeMaterialType')
    active
@endsection

@section('activeCreateMaterialType')
    active
@endsection

@section('title')
    Nuevo tipo de material
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Nuevo tipo de material</h1>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>Subcategoría y tipo de material</strong>
            <span>Los campos marcados con (*) son obligatorios.</span>
        </div>
        <div class="next-toolbar-actions">
            <button type="reset" class="btn btn-outline-secondary" form="formCreate">Cancelar</button>
            <button type="submit" class="btn btn-primary" form="formCreate">Guardar tipo</button>
        </div>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}">Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('materialtype.index') }}">Tipos de material</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Nuevo</li>
    </ol>
@endsection

@section('content')
    <form id="formCreate" class="form-horizontal" data-url="{{ route('materialtype.store') }}" enctype="multipart/form-data">
        @csrf
        <section class="next-form-section" aria-labelledby="material-type-create-title">
            <div class="next-section-header">
                <div>
                    <span class="next-section-kicker">01</span>
                    <h2 id="material-type-create-title">Subcategoría y tipo</h2>
                    <p>Selecciona la subcategoría padre y define el tipo de material asociado.</p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="subcategory_id">Subcategoría <span class="next-required">(*)</span></label>
                    <select id="subcategory_id" name="subcategory_id" class="form-control select2" required>
                        <option></option>
                        @foreach($subcategories as $subcategory)
                            <option value="{{ $subcategory->id }}">{{ $subcategory->name }}</option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">La Categoría se determina mediante la Subcategoría seleccionada.</small>
                </div>

                <div class="form-group col-md-4">
                    <label for="material-type-name">Tipo de material <span class="next-required">(*)</span></label>
                    <input
                        type="text"
                        class="form-control"
                        id="material-type-name"
                        name="name"
                        onkeyup="mayus(this);"
                        placeholder="Ej.: Calzado casual"
                        maxlength="255"
                        required
                        autofocus>
                </div>

                <div class="form-group col-md-4">
                    <label for="material-type-description">Descripción</label>
                    <input
                        type="text"
                        class="form-control"
                        id="material-type-description"
                        name="description"
                        onkeyup="mayus(this);"
                        placeholder="Ej.: Tipo de material para línea casual"
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
    <script src="{{ asset('js/materialtype/create.js') }}"></script>
@endsection
