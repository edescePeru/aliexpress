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

@section('activeCreateTypeScrap')
    active
@endsection

@section('title')
    Nuevo tipo de retacería
@endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Nuevo tipo de retacería</h1>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>Datos del tipo de retacería</strong>
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
        <li class="breadcrumb-item"><a href="{{ route('dashboard.principal') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('typescrap.index') }}">Tipos de retacería</a></li>
        <li class="breadcrumb-item active" aria-current="page">Nuevo</li>
    </ol>
@endsection

@section('content')
    <form id="formCreate" class="form-horizontal" data-url="{{ route('typescrap.store') }}" enctype="multipart/form-data">
        @csrf
        <section class="next-form-section" aria-labelledby="typescrap-create-title">
            <div class="next-section-header">
                <div>
                    <span class="next-section-kicker">01</span>
                    <h2 id="typescrap-create-title">Identidad y dimensiones</h2>
                    <p>Define el nombre y las dimensiones base utilizadas por este tipo.</p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="typescrap-name">Nombre <span class="next-required">(*)</span></label>
                    <input type="text" class="form-control" id="typescrap-name" name="name"
                        onkeyup="mayus(this);" placeholder="Ej.: Plancha" maxlength="191" required autofocus>
                </div>
                <div class="form-group col-md-4">
                    <label for="typescrap-length">Largo <span class="next-required">(*)</span></label>
                    <input type="number" class="form-control" id="typescrap-length" name="length"
                        min="0" max="99999.99" step="0.01" placeholder="Ej.: 6000.00" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="typescrap-width">Ancho <span class="next-required">(*)</span></label>
                    <input type="number" class="form-control" id="typescrap-width" name="width"
                        min="0" max="99999.99" step="0.01" placeholder="Ej.: 3000.00" required>
                </div>
            </div>
        </section>
    </form>
@endsection

@section('scripts')
    <script src="{{ asset('js/typescrap/create.js') }}"></script>
@endsection
