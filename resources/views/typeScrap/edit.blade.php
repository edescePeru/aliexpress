@extends('layouts.appAdmin2')

@section('openConfig')
    menu-open
@endsection

@section('activeConfig')
    active
@endsection

@section('openTypeScrap')
    menu-open
@endsection

@section('activeTypeScrap')
    active
@endsection

@section('activeListTypeScrap')
    active
@endsection

@section('title')
    Editar tipo de retacería
@endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Editar tipo de retacería</h1>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>{{ $typeScrap->name }}</strong>
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
        <li class="breadcrumb-item"><a href="{{ route('typescrap.index') }}">Tipos de retacería</a></li>
        <li class="breadcrumb-item active" aria-current="page">Editar</li>
    </ol>
@endsection

@section('content')
    <form id="formEdit" class="form-horizontal" data-url="{{ route('typescrap.update') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="typeScrap_id" value="{{ $typeScrap->id }}">

        <section class="next-form-section" aria-labelledby="typescrap-edit-title">
            <div class="next-section-header">
                <div>
                    <span class="next-section-kicker">01</span>
                    <h2 id="typescrap-edit-title">Identidad y dimensiones</h2>
                    <p>Actualizar este catálogo no modifica automáticamente los Items existentes.</p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="typescrap-name">Nombre <span class="next-required">(*)</span></label>
                    <input type="text" class="form-control" id="typescrap-name" name="name"
                        onkeyup="mayus(this);" placeholder="Ej.: Plancha" maxlength="191"
                        value="{{ $typeScrap->name }}" required autofocus>
                </div>
                <div class="form-group col-md-4">
                    <label for="typescrap-length">Largo <span class="next-required">(*)</span></label>
                    <input type="number" class="form-control" id="typescrap-length" name="length"
                        min="0" max="99999.99" step="0.01" value="{{ $typeScrap->length }}" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="typescrap-width">Ancho <span class="next-required">(*)</span></label>
                    <input type="number" class="form-control" id="typescrap-width" name="width"
                        min="0" max="99999.99" step="0.01" value="{{ $typeScrap->width }}" required>
                </div>
            </div>
        </section>
    </form>
@endsection

@section('scripts')
    <script src="{{ asset('js/typescrap/edit.js') }}"></script>
@endsection
