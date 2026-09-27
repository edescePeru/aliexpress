@extends('layouts.appAdmin2')

@section('openConfig')
    menu-open
@endsection

@section('activeConfig')
    active
@endsection

@section('openListFiles')
    menu-open
@endsection

@section('activeListStockFiles')
    active
@endsection

@section('title')
    Importar stocks de materiales
@endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Importación</span>
        <h1 class="page-title">Importar stocks de materiales</h1>
        <p class="next-page-description">Actualiza stocks mínimos y máximos mediante un archivo Excel.</p>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>Actualización masiva</strong>
            <span>Revisa el archivo antes de iniciar el procesamiento.</span>
        </div>
        <div class="next-toolbar-actions">
            <button type="reset" class="btn btn-outline-secondary" form="formStocksFile">Cancelar</button>
            <button type="button" id="btn-submitStockFiles" class="btn btn-primary" form="formStocksFile" disabled>
                <i class="fas fa-file-import" aria-hidden="true"></i> Importar archivo
            </button>
        </div>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}">Dashboard</a>
        </li>
        <li class="breadcrumb-item">Materiales</li>
        <li class="breadcrumb-item active" aria-current="page">Importar stocks</li>
    </ol>
@endsection

@section('content')
    <form
        id="formStocksFile"
        class="form-horizontal"
        data-url="{{ route('stocks.files.store') }}"
        enctype="multipart/form-data"
    >
        @csrf

        <section class="next-form-section" aria-labelledby="material-stock-import-title">
            <div class="next-section-header flex-wrap">
                <div>
                    <span class="next-section-kicker">01</span>
                    <h2 id="material-stock-import-title">Archivo de actualización</h2>
                    <p>La primera hoja debe contener Código, Stock mínimo y Stock máximo, en ese orden.</p>
                </div>
                <button
                    class="btn btn-outline-secondary btn-sm"
                    type="button"
                    id="exampleStockFile"
                    data-url="{{ url('/dashboard/download/example/stock/file') }}"
                >
                    <i class="fas fa-download mr-1" aria-hidden="true"></i>
                    Descargar plantilla
                </button>
            </div>

            <div class="alert alert-warning d-flex align-items-start mb-4" role="note" aria-labelledby="stock-import-warning-title">
                <i class="fas fa-exclamation-triangle mt-1 mr-3" aria-hidden="true"></i>
                <div>
                    <strong id="stock-import-warning-title" class="d-block">Antes de importar</strong>
                    <span>Solo se admiten archivos Excel. El procesamiento actualiza los mínimos y máximos de los materiales coincidentes; seleccionar un archivo todavía no realiza cambios.</span>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-lg-8">
                    <div class="form-group mb-0">
                        <label for="stockFile">Archivo Excel <span class="next-required">(*)</span></label>
                        <div class="custom-file">
                            <input
                                type="file"
                                id="stockFile"
                                name="file"
                                class="custom-file-input"
                                accept=".xlsx,.xls"
                                aria-describedby="stock-file-help"
                                required
                            >
                            <label class="custom-file-label text-truncate" for="stockFile" data-browse="Seleccionar" data-stock-file-label>
                                Ningún archivo seleccionado
                            </label>
                        </div>
                        <small id="stock-file-help" class="form-text text-muted">
                            Formatos permitidos: .xlsx y .xls. Tamaño máximo: 10 MB. La primera fila se interpreta como cabecera.
                        </small>
                    </div>
                </div>
            </div>
        </section>
    </form>
@endsection

@section('scripts')
    <script src="{{ asset('js/files/stockFiles.js') }}"></script>
@endsection
