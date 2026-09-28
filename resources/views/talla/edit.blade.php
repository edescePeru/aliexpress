@extends('layouts.appAdmin2')

@section('openConfig') menu-open @endsection
@section('activeConfig') active @endsection
@section('openTalla') menu-open @endsection
@section('activeTalla') active @endsection
@section('activeListTalla') active @endsection

@section('title') Editar talla @endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Editar talla</h1>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>{{ $talla->name }}</strong>
            <span>Los campos marcados con (*) son obligatorios.</span>
        </div>
        <div class="next-toolbar-actions">
            <button type="reset" class="btn btn-outline-secondary" form="formTalla">Cancelar</button>
            <button type="submit" id="btnSaveTalla" class="btn btn-primary" form="formTalla">Guardar cambios</button>
        </div>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.principal') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('talla.index') }}">Tallas</a></li>
        <li class="breadcrumb-item active" aria-current="page">Editar</li>
    </ol>
@endsection

@section('content')
    <div id="talla-form-app" data-url-update="{{ route('talla.update') }}" data-url-index="{{ route('talla.index') }}">
        <form id="formTalla" class="form-horizontal">
            @csrf
            <input type="hidden" name="talla_id" value="{{ $talla->id }}">
            <section class="next-form-section" aria-labelledby="talla-edit-title">
                <div class="next-section-header">
                    <div>
                        <span class="next-section-kicker">01</span>
                        <h2 id="talla-edit-title">Información general</h2>
                        <p>Actualiza el nombre operativo, su abreviatura o la descripción.</p>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="name">Nombre <span class="next-required">(*)</span></label>
                        <input type="text" id="name" name="name" class="form-control" maxlength="191" value="{{ $talla->name }}" required autofocus>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="short_name">Nombre corto</label>
                        <input type="text" id="short_name" name="short_name" class="form-control" maxlength="191" value="{{ $talla->short_name }}">
                    </div>
                    <div class="form-group col-12">
                        <label for="description">Descripción</label>
                        <input type="text" id="description" name="description" class="form-control" maxlength="255" value="{{ $talla->description }}">
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
