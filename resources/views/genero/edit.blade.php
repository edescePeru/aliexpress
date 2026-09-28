@extends('layouts.appAdmin2')

@section('openConfig')
    menu-open
@endsection

@section('activeConfig')
    active
@endsection

@section('openGenero')
    menu-open
@endsection

@section('activeGenero')
    active
@endsection

@section('activeListGenero')
    active
@endsection

@section('title')
    Editar género
@endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Editar género</h1>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>{{ $genero->name }}</strong>
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
            <a href="{{ route('genero.index') }}">Géneros</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Editar</li>
    </ol>
@endsection

@section('content')
    <form id="formEdit" class="form-horizontal" data-url="{{ route('genero.update') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="genero_id" value="{{ $genero->id }}">

        <section class="next-form-section" aria-labelledby="genero-edit-title">
            <div class="next-section-header">
                <div>
                    <span class="next-section-kicker">01</span>
                    <h2 id="genero-edit-title">Información general</h2>
                    <p>Actualiza el nombre operativo o la descripción del género.</p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="genero-name">Género <span class="next-required">(*)</span></label>
                    <input type="text" class="form-control" id="genero-name" name="name"
                        onkeyup="mayus(this);" placeholder="Ej.: Unisex" value="{{ $genero->name }}"
                        maxlength="191" required autofocus>
                </div>

                <div class="form-group col-md-6">
                    <label for="genero-description">Descripción</label>
                    <input type="text" class="form-control" id="genero-description" name="description"
                        onkeyup="mayus(this);" placeholder="Ej.: Aplicable a cualquier género"
                        value="{{ $genero->description }}" maxlength="255">
                </div>
            </div>
        </section>
    </form>
@endsection

@section('scripts')
    <script src="{{ asset('js/genero/edit.js') }}"></script>
@endsection
