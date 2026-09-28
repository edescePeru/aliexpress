@extends('layouts.appAdmin2')

@section('openConfig')
    menu-open
@endsection

@section('activeConfig')
    active
@endsection

@section('openUnitMeasure')
    menu-open
@endsection

@section('activeUnitMeasure')
    active
@endsection

@section('activeListUnitMeasure')
    active
@endsection

@section('title')
    Editar unidad de medida
@endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Editar unidad de medida</h1>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>{{ $unitMeasure->name }}</strong>
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
            <a href="{{ route('unitmeasure.index') }}">Unidades de medida</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Editar</li>
    </ol>
@endsection

@section('content')
    <form id="formEdit" class="form-horizontal" data-url="{{ route('unitmeasure.update') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="unitMeasure_id" value="{{ $unitMeasure->id }}">

        <section class="next-form-section" aria-labelledby="unit-measure-edit-title">
            <div class="next-section-header">
                <div>
                    <span class="next-section-kicker">01</span>
                    <h2 id="unit-measure-edit-title">Información general</h2>
                    <p>Actualiza el nombre operativo o la descripción de la unidad.</p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="unit-measure-name">Unidad de medida <span class="next-required">(*)</span></label>
                    <input
                        type="text"
                        class="form-control"
                        id="unit-measure-name"
                        name="name"
                        onkeyup="mayus(this);"
                        placeholder="Ej.: Unidad"
                        value="{{ $unitMeasure->name }}"
                        maxlength="255"
                        required
                        autofocus>
                </div>

                <div class="form-group col-md-6">
                    <label for="unit-measure-description">Descripción</label>
                    <input
                        type="text"
                        class="form-control"
                        id="unit-measure-description"
                        name="description"
                        onkeyup="mayus(this);"
                        placeholder="Ej.: Venta y control por unidad"
                        value="{{ $unitMeasure->description }}"
                        maxlength="255">
                </div>
            </div>
        </section>
    </form>
@endsection

@section('scripts')
    <script src="{{ asset('js/unitmeasure/edit.js') }}"></script>
@endsection
