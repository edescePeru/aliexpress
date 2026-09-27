@extends('layouts.appAdmin2')

@section('openConfig')
    menu-open
@endsection

@section('activeConfig')
    active
@endsection

@section('openBrand')
    menu-open
@endsection

@section('activeBrand')
    active
@endsection

@section('activeListBrand')
    active
@endsection

@section('title')
    Editar marca
@endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Editar marca</h1>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>{{ $brand->name }}</strong>
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
        <li class="breadcrumb-item"><a href="{{ route('brand.index') }}">Marcas</a></li>
        <li class="breadcrumb-item active" aria-current="page">Editar</li>
    </ol>
@endsection

@section('content')
    <form id="formEdit" class="form-horizontal" data-url="{{ route('brand.update') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="brand_id" value="{{ $brand->id }}">

        <section class="next-form-section" aria-labelledby="brand-edit-title">
            <div class="next-section-header">
                <div>
                    <span class="next-section-kicker">01</span>
                    <h2 id="brand-edit-title">Información general</h2>
                    <p>Actualiza el nombre comercial o el comentario de la marca.</p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="brand-name">Marca <span class="next-required">(*)</span></label>
                    <input type="text" class="form-control" id="brand-name" name="name"
                        onkeyup="mayus(this);" placeholder="Ej.: Adidas" value="{{ $brand->name }}"
                        maxlength="255" required autofocus>
                </div>
                <div class="form-group col-md-6">
                    <label for="brand-comment">Comentario</label>
                    <input type="text" class="form-control" id="brand-comment" name="comment"
                        onkeyup="mayus(this);" placeholder="Ej.: Marca de indumentaria"
                        value="{{ $brand->comment }}" maxlength="255">
                </div>
            </div>
        </section>
    </form>
@endsection

@section('scripts')
    <script src="{{ asset('js/brand/edit.js') }}"></script>
@endsection
