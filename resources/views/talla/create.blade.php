@extends('layouts.appAdmin2')

@section('openConfig') menu-open @endsection
@section('activeConfig') active @endsection
@section('openTalla') menu-open @endsection
@section('activeTalla') active @endsection
@section('activeCreateTalla') active @endsection

@section('title') Nueva talla @endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Nueva talla</h1>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>Datos de la talla</strong>
            <span>Los campos marcados con (*) son obligatorios.</span>
        </div>
        <div class="next-toolbar-actions">
            <button type="reset" class="btn btn-outline-secondary" form="formTalla">Cancelar</button>
            <button type="submit" id="btnSaveTalla" class="btn btn-primary" form="formTalla">Guardar talla</button>
        </div>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.principal') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('talla.index') }}">Tallas</a></li>
        <li class="breadcrumb-item active" aria-current="page">Nueva</li>
    </ol>
@endsection

@section('content')
    <div id="talla-form-app" data-url-store="{{ route('talla.store') }}" data-url-index="{{ route('talla.index') }}">
        <form id="formTalla" class="form-horizontal">
            @csrf
            <section class="next-form-section" aria-labelledby="talla-create-title">
                <div class="next-section-header">
                    <div>
                        <span class="next-section-kicker">01</span>
                        <h2 id="talla-create-title">Información general</h2>
                        <p>Define el nombre operativo, su abreviatura y una descripción opcional.</p>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="name">Nombre <span class="next-required">(*)</span></label>
                        <input type="text" id="name" name="name" class="form-control" maxlength="191" placeholder="Ej.: Talla 41, L o 256 GB" required autofocus>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="short_name">Nombre corto</label>
                        <input type="text" id="short_name" name="short_name" class="form-control" maxlength="191" placeholder="Ej.: 41, L o 256GB">
                    </div>
                    <div class="form-group col-12">
                        <label for="description">Descripción</label>
                        <input type="text" id="description" name="description" class="form-control" maxlength="255" placeholder="Descripción opcional">
                        <small class="form-text text-muted">Puede representar una talla, capacidad o presentación usada para diferenciar variantes.</small>
                    </div>
                </div>
            </section>
        </form>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/talla/form.js') }}?v={{ time() }}"></script>
@endsection
