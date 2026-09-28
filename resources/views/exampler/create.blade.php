@extends('layouts.appAdmin2')

@section('openConfig')
    menu-open
@endsection

@section('activeConfig')
    active
@endsection

@section('openExampler')
    menu-open
@endsection

@section('activeExampler')
    active
@endsection

@section('activeCreateExampler')
    active
@endsection

@section('title')
    Nuevo modelo
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Nuevo modelo</h1>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>Datos del modelo</strong>
            <span>Los campos marcados con (*) son obligatorios.</span>
        </div>
        <div class="next-toolbar-actions">
            <button type="reset" class="btn btn-outline-secondary" form="formCreate">Cancelar</button>
            <button type="submit" class="btn btn-primary" form="formCreate">Guardar modelo</button>
        </div>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.principal') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('exampler.index') }}">Modelos</a></li>
        <li class="breadcrumb-item active" aria-current="page">Nuevo</li>
    </ol>
@endsection

@section('content')
    <form id="formCreate" class="form-horizontal" data-url="{{ route('exampler.store') }}" enctype="multipart/form-data">
        @csrf
        <section class="next-form-section" aria-labelledby="exampler-create-title">
            <div class="next-section-header">
                <div>
                    <span class="next-section-kicker">01</span>
                    <h2 id="exampler-create-title">Relación Marca → Modelo</h2>
                    <p>Selecciona la marca padre y define el modelo asociado.</p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="brand_id">Marca <span class="next-required">(*)</span></label>
                    <select id="brand_id" name="brand_id" class="form-control select2" required>
                        <option value=""></option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label for="exampler-name">Modelo <span class="next-required">(*)</span></label>
                    <input type="text" class="form-control" id="exampler-name" name="name"
                        onkeyup="mayus(this);" placeholder="Ej.: Runner 90" maxlength="255" required autofocus>
                </div>
                <div class="form-group col-12">
                    <label for="exampler-comment">Comentario</label>
                    <input type="text" class="form-control" id="exampler-comment" name="comment"
                        onkeyup="mayus(this);" placeholder="Ej.: Modelo deportivo" maxlength="255">
                </div>
            </div>
        </section>
    </form>
@endsection

@section('plugins')
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
@endsection

@section('scripts')
    <script src="{{ asset('js/exampler/create.js') }}"></script>
@endsection
