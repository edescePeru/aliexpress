@extends('layouts.appAdmin2')

@section('openConfig') menu-open @endsection
@section('activeConfig') active @endsection
@section('openColor') menu-open @endsection
@section('activeColor') active @endsection
@section('activeCreateColor') active @endsection

@section('title') Nuevo color @endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Nuevo color</h1>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>Datos del color</strong>
            <span>Los campos marcados con (*) son obligatorios.</span>
        </div>
        <div class="next-toolbar-actions">
            <button type="reset" class="btn btn-outline-secondary" form="formColor">Cancelar</button>
            <button type="submit" id="btnSaveColor" class="btn btn-primary" form="formColor">Guardar color</button>
        </div>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.principal') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('color.index') }}">Colores</a></li>
        <li class="breadcrumb-item active" aria-current="page">Nuevo</li>
    </ol>
@endsection

@section('content')
    <div id="color-form-app" data-url-store="{{ route('color.store') }}" data-url-index="{{ route('color.index') }}">
        <form id="formColor" class="form-horizontal">
            @csrf
            <section class="next-form-section" aria-labelledby="color-create-title">
                <div class="next-section-header">
                    <div>
                        <span class="next-section-kicker">01</span>
                        <h2 id="color-create-title">Información general</h2>
                        <p>Define el nombre operativo, la abreviatura y el código hexadecimal del color.</p>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="name">Nombre <span class="next-required">(*)</span></label>
                        <input type="text" id="name" name="name" class="form-control" maxlength="255" placeholder="Ej.: Azul marino" required autofocus>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="short_name">Nombre corto <span class="next-required">(*)</span></label>
                        <input type="text" id="short_name" name="short_name" class="form-control" maxlength="255" placeholder="Ej.: AZM" required>
                    </div>
                    <div class="form-group col-md-8">
                        <label for="code">Código hexadecimal</label>
                        <div class="input-group">
                            <input type="text" id="code" name="code" class="form-control" maxlength="7" placeholder="#FFFFFF">
                            <div class="input-group-append">
                                <label class="sr-only" for="colorPicker">Seleccionar color</label>
                                <span class="input-group-text p-1">
                                    <input type="color" id="colorPicker" class="border-0 p-0 h-100" value="#ffffff" aria-label="Seleccionar color">
                                </span>
                            </div>
                        </div>
                        <small class="form-text text-muted">Formato opcional de seis dígitos, por ejemplo #2B3A4A.</small>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="colorPreview">Vista previa</label>
                        <input type="color" id="colorPreview" class="form-control p-1" value="#ffffff" aria-label="Vista previa del color" disabled>
                        <small class="form-text text-muted">La muestra complementa el código textual.</small>
                    </div>
                </div>
            </section>
        </form>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/color/form.js') }}?v={{ time() }}"></script>
@endsection
