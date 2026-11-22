@extends('layouts.appAdmin2')

@section('title', 'Catálogos de guías de remisión')
@section('activePlatformShippingCatalogs', 'active')
@section('page-title')
    <div class="next-page-toolbar"><div class="next-toolbar-context"><strong>{{ $title }}</strong><span>Administración global de plataforma.</span></div></div>
@endsection

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Venti360 · Plataforma</span><h1 class="page-title">Catálogos de guías</h1><p class="next-page-description">Configuración de transporte, traslado y documentos.</p></div>
@endsection
@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('platform.dashboard') }}">Superadministración</a></li><li class="breadcrumb-item active">Catálogos de guías</li></ol>
@endsection
@section('content')
<section class="next-operational-list" aria-label="Catálogos de guías">
    <div class="next-list-toolbar">
    <nav class="nav nav-pills flex-wrap" aria-label="Catálogos de guías de remisión">
        @foreach ($catalogs as $key => $definition)
            <a class="nav-link {{ $catalog === $key ? 'active' : '' }}"
               @if($catalog === $key) aria-current="page" @endif href="{{ route('platformShippingCatalogs.index', $key) }}">{{ $definition[1] }}</a>
        @endforeach
    </nav>
    </div>
    @if(session('success') || $errors->any() || $availableCodes || $supportedCodes)
    <div class="next-form-section">

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($availableCodes)
        <details class="mb-0" @if (old('code')) open @endif>
            <summary>{{ $catalog === 'motivos' ? 'Nuevo motivo' : 'Nuevo indicador' }}</summary>
            <form method="POST" action="{{ route('platformShippingCatalogs.store', $catalog) }}" class="mt-3">
                @csrf
                <div class="form-row align-items-end">
                    <div class="col-md-3 form-group">
                        <label for="new-code">Código</label>
                        <select id="new-code" name="code" class="form-control" required>
                            <option value="">Seleccionar</option>
                            @foreach ($availableCodes as $code)
                                <option value="{{ $code }}" {{ old('code') === $code ? 'selected' : '' }}>{{ $code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="new-name">Nombre</label>
                        <input id="new-name" name="name" class="form-control" maxlength="{{ $maxName }}"
                               value="{{ old('code') ? old('name') : '' }}" required>
                    </div>
                    <div class="col-md-2 form-group">
                        <label for="new-order">Orden</label>
                        <input id="new-order" name="sort_order" type="number" class="form-control"
                               min="0" max="65535" value="{{ old('code') ? old('sort_order', 0) : 0 }}" required>
                    </div>
                    <div class="col-md-2 form-group">
                        <label for="new-active">Estado</label>
                        <select id="new-active" name="is_active" class="form-control">
                            <option value="1" {{ !old('code') || old('is_active', '1') ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ old('code') && old('is_active') === '0' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="next-toolbar-actions flex-wrap"><button type="submit" class="btn btn-primary btn-sm">Crear</button></div>
            </form>
        </details>
    @elseif ($supportedCodes)
        <p class="text-muted">Ya están registrados todos los códigos habilitados para este catálogo.</p>
    @endif

    </div>
    @endif
    <div class="next-list-summary"><div class="next-list-summary-copy"><strong>{{ $records->count() }} registros</strong><span>Orden configurado; luego código.</span></div></div>
    <div class="next-list-content"><div class="table-responsive" tabindex="0" aria-label="Catálogo; desplazamiento horizontal de tabla">
    <table class="table table-bordered table-hover table-sm next-data-table">
        <thead><tr><th class="text-center">Código</th><th>Nombre</th><th class="text-center">Orden</th><th class="text-center">Estado</th><th class="text-center">Acciones</th></tr></thead>
        <tbody>
        @forelse($records as $record)
        <tr><td class="text-center text-break">{{ $record->code }}</td><td>{{ $record->name }}</td><td class="text-center">{{ $record->sort_order }}</td><td class="text-center"><span class="badge {{ $record->is_active ? 'badge-success' : 'badge-secondary' }}">{{ $record->is_active ? 'Activo' : 'Inactivo' }}</span></td><td class="text-center"><button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#edit-catalog-{{ $record->id }}" aria-label="Editar {{ $record->code }}"><i class="fas fa-edit" aria-hidden="true"></i></button></td></tr>
        @empty
        <tr><td colspan="5" class="next-table-empty">El catálogo todavía no tiene registros.</td></tr>
        @endforelse
        </tbody>
    </table></div></div>
</section>
    @foreach ($records as $record)
        @php($editing = (string) old('record_id') === (string) $record->id)
        <div class="modal fade next-aux-modal" id="edit-catalog-{{ $record->id }}" tabindex="-1" role="dialog" aria-labelledby="edit-title-{{ $record->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="edit-title-{{ $record->id }}">Editar {{ $title }}</h5><button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>
        <form method="POST" action="{{ route('platformShippingCatalogs.update', [$catalog, $record->id]) }}"
              class="next-aux-modal-form">
            @csrf
            @method('PUT')
            <input type="hidden" name="record_id" value="{{ $record->id }}">
            <div class="modal-body">
                @if($editing && $errors->any())
                <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                <div class="form-row align-items-end">
                <div class="col-md-6 form-group">
                    <label for="code-{{ $record->id }}">Código</label>
                    <input id="code-{{ $record->id }}" class="form-control" value="{{ $record->code }}" readonly>
                </div>
                <div class="col-md-6 form-group">
                    <label for="name-{{ $record->id }}">Nombre</label>
                    <input id="name-{{ $record->id }}" name="name" class="form-control" maxlength="{{ $maxName }}"
                           value="{{ $editing ? old('name') : $record->name }}" required>
                </div>
                <div class="col-md-6 form-group">
                    <label for="sort-{{ $record->id }}">Orden</label>
                    <input id="sort-{{ $record->id }}" name="sort_order" type="number" class="form-control"
                           value="{{ $editing ? old('sort_order') : $record->sort_order }}" min="0" max="65535" required>
                </div>
                <div class="col-md-6 form-group">
                    <label for="active-{{ $record->id }}">Estado</label>
                    <select id="active-{{ $record->id }}" name="is_active" class="form-control">
                        <option value="1" {{ ($editing ? old('is_active') : $record->is_active) ? 'selected' : '' }}>Activo</option>
                        <option value="0" {{ ($editing ? old('is_active') : $record->is_active) ? '' : 'selected' }}>Inactivo</option>
                    </select>
                </div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
        </form>
        </div></div></div>
    @endforeach
@endsection
@section('scripts')
    @if(old('record_id') && $records->contains('id', old('record_id')))
    <script>$(function () { $('#edit-catalog-{{ (int) old('record_id') }}').modal('show'); });</script>
    @endif
@endsection
