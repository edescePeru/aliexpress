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
@section('activeCreateSubType')
    active
@endsection

@section('title')
    Nuevo subtipo
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Nuevo subtipo</h1>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>Tipo de material y subtipo</strong>
            <span>Los campos marcados con (*) son obligatorios.</span>
        </div>
        <div class="next-toolbar-actions">
            <button type="reset" class="btn btn-outline-secondary" form="formCreate">Cancelar</button>
            <button type="submit" class="btn btn-primary" form="formCreate">Guardar subtipo</button>
        </div>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.principal') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('subtype.index') }}">Subtipos</a></li>
        <li class="breadcrumb-item active" aria-current="page">Nuevo</li>
    </ol>
@endsection

@section('content')
    <form id="formCreate" class="form-horizontal" data-url="{{ route('subtype.store') }}" enctype="multipart/form-data">
        @csrf
        <section class="next-form-section" aria-labelledby="subtype-create-title">
            <div class="next-section-header">
                <div>
                    <span class="next-section-kicker">01</span>
                    <h2 id="subtype-create-title">Tipo de material y subtipo</h2>
                    <p>Selecciona el tipo de material padre y define el subtipo asociado.</p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="material_type_id">Tipo de material <span class="next-required">(*)</span></label>
                    <select id="material_type_id" name="material_type_id" class="form-control select2" required>
                        <option></option>
                        @foreach($materialTypes as $materialType)
                            <option value="{{ $materialType->id }}">{{ $materialType->name }}</option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">La Categoría y Subcategoría se determinan mediante el Tipo de material seleccionado.</small>
                </div>

                <div class="form-group col-md-4">
                    <label for="subtype-name">Subtipo <span class="next-required">(*)</span></label>
                    <input type="text" class="form-control" id="subtype-name" name="name"
                        onkeyup="mayus(this);" placeholder="Ej.: Algodón" maxlength="255" required autofocus>
                </div>

                <div class="form-group col-md-4">
                    <label for="subtype-description">Descripción</label>
                    <input type="text" class="form-control" id="subtype-description" name="description"
                        onkeyup="mayus(this);" placeholder="Ej.: Subtipo para materiales de algodón" maxlength="255">
                </div>
            </div>
        </section>
    </form>
@endsection

@section('plugins')
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
@endsection

@section('scripts')
    <script src="{{ asset('js/subtype/create.js') }}"></script>
@endsection
