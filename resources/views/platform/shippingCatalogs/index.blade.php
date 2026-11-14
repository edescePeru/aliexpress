@extends('layouts.appAdmin2')

@section('title', 'Catálogos de guías de remisión')
@section('activePlatformShippingCatalogs', 'active')
@section('page-title')
    <h5 class="card-title">{{ $title }}</h5>
@endsection

@section('content')
    <nav class="nav nav-pills mb-3" aria-label="Catálogos de guías de remisión">
        @foreach ($catalogs as $key => $definition)
            <a class="nav-link {{ $catalog === $key ? 'active' : '' }}"
               href="{{ route('platformShippingCatalogs.index', $key) }}">{{ $definition[1] }}</a>
        @endforeach
    </nav>

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
        <details class="border rounded p-3 mb-3" @if (old('code')) open @endif>
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
                <button type="submit" class="btn btn-primary">Crear</button>
            </form>
        </details>
    @elseif ($supportedCodes)
        <p class="text-muted">Ya están registrados todos los códigos habilitados para este catálogo.</p>
    @endif

    @forelse ($records as $record)
        @php($editing = (string) old('record_id') === (string) $record->id)
        <form method="POST" action="{{ route('platformShippingCatalogs.update', [$catalog, $record->id]) }}"
              class="border rounded p-3 mb-2">
            @csrf
            @method('PUT')
            <input type="hidden" name="record_id" value="{{ $record->id }}">
            <div class="form-row align-items-end">
                <div class="col-md-2 form-group mb-md-0">
                    <label for="code-{{ $record->id }}">Código</label>
                    <input id="code-{{ $record->id }}" class="form-control" value="{{ $record->code }}" readonly>
                </div>
                <div class="col-md-4 form-group mb-md-0">
                    <label for="name-{{ $record->id }}">Nombre</label>
                    <input id="name-{{ $record->id }}" name="name" class="form-control" maxlength="{{ $maxName }}"
                           value="{{ $editing ? old('name') : $record->name }}" required>
                </div>
                <div class="col-md-2 form-group mb-md-0">
                    <label for="sort-{{ $record->id }}">Orden</label>
                    <input id="sort-{{ $record->id }}" name="sort_order" type="number" class="form-control"
                           value="{{ $editing ? old('sort_order') : $record->sort_order }}" min="0" max="65535" required>
                </div>
                <div class="col-md-2 form-group mb-md-0">
                    <label for="active-{{ $record->id }}">Estado</label>
                    <select id="active-{{ $record->id }}" name="is_active" class="form-control">
                        <option value="1" {{ ($editing ? old('is_active') : $record->is_active) ? 'selected' : '' }}>Activo</option>
                        <option value="0" {{ ($editing ? old('is_active') : $record->is_active) ? '' : 'selected' }}>Inactivo</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </form>
    @empty
        <p class="text-muted">El catálogo todavía no tiene registros.</p>
    @endforelse
@endsection