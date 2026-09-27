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

@section('activeCreateBrand')
    active
@endsection

@section('title')
    Nueva marca
@endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Nueva marca</h1>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>Datos de la marca</strong>
            <span>Los campos marcados con (*) son obligatorios.</span>
        </div>
        <div class="next-toolbar-actions">
            <button type="reset" class="btn btn-outline-secondary" form="formCreate">Cancelar</button>
            <button type="submit" class="btn btn-primary" form="formCreate">Guardar marca</button>
        </div>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.principal') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('brand.index') }}">Marcas</a></li>
        <li class="breadcrumb-item active" aria-current="page">Nueva</li>
    </ol>
@endsection

@section('content')
    <form id="formCreate" class="form-horizontal" data-url="{{ route('brand.store') }}" enctype="multipart/form-data">
        @csrf
        <section class="next-form-section" aria-labelledby="brand-create-title">
            <div class="next-section-header">
                <div>
                    <span class="next-section-kicker">01</span>
                    <h2 id="brand-create-title">Información general</h2>
                    <p>Define el nombre comercial y un comentario opcional para la marca.</p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="brand-name">Marca <span class="next-required">(*)</span></label>
                    <input type="text" class="form-control" id="brand-name" name="name"
                        onkeyup="mayus(this);" placeholder="Ej.: Adidas" maxlength="255" required autofocus>
                </div>
                <div class="form-group col-md-6">
                    <label for="brand-comment">Comentario</label>
                    <input type="text" class="form-control" id="brand-comment" name="comment"
                        onkeyup="mayus(this);" placeholder="Ej.: Marca de indumentaria" maxlength="255">
                </div>
            </div>
        </section>
    </form>
@endsection

@section('scripts')
    <script src="{{ asset('js/brand/create.js') }}"></script>
@endsection
