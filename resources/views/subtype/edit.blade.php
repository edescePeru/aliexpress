@extends('layouts.appAdmin2')

@section('openConfig')
    menu-open
@endsection
@section('activeConfig')
    active
@endsection
@section('openSubType')
    menu-open
@endsection
@section('activeSubType')
    active
@endsection
@section('activeListSubType')
    active
@endsection

@section('title')
    Editar subtipo
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Editar subtipo</h1>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>{{ $subtype->name }}</strong>
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
        <li class="breadcrumb-item"><a href="{{ route('dashboard.principal') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('subtype.index') }}">Subtipos</a></li>
        <li class="breadcrumb-item active" aria-current="page">Editar</li>
    </ol>
@endsection

@section('content')
    <form id="formEdit" class="form-horizontal" data-url="{{ route('subtype.update') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="subtype_id" value="{{ $subtype->id }}">

        <section class="next-form-section" aria-labelledby="subtype-edit-title">
            <div class="next-section-header">
                <div>
                    <span class="next-section-kicker">01</span>
                    <h2 id="subtype-edit-title">Tipo de material y subtipo</h2>
                    <p>Actualiza el tipo de material padre, el nombre o la descripción del subtipo.</p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="material_type_id">Tipo de material <span class="next-required">(*)</span></label>
                    <select id="material_type_id" name="material_type_id" class="form-control select2" required>
                        <option></option>
                        @foreach($materialTypes as $materialType)
                            <option value="{{ $materialType->id }}" {{ $materialType->id === $subtype->material_type_id ? 'selected' : '' }}>{{ $materialType->name }}</option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">La Categoría y Subcategoría se determinan mediante el Tipo de material seleccionado.</small>
                </div>

                <div class="form-group col-md-4">
                    <label for="subtype-name">Subtipo <span class="next-required">(*)</span></label>
                    <input type="text" class="form-control" id="subtype-name" name="name"
                        onkeyup="mayus(this);" placeholder="Ej.: Algodón" value="{{ $subtype->name }}"
                        maxlength="255" required autofocus>
                </div>

                <div class="form-group col-md-4">
                    <label for="subtype-description">Descripción</label>
                    <input type="text" class="form-control" id="subtype-description" name="description"
                        onkeyup="mayus(this);" placeholder="Ej.: Subtipo para materiales de algodón"
                        value="{{ $subtype->description }}" maxlength="255">
                </div>
            </div>
        </section>
    </form>
@endsection

@section('plugins')
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
@endsection

@section('scripts')
    <script src="{{ asset('js/subtype/edit.js') }}"></script>
@endsection
