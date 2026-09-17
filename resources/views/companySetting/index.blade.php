@extends('layouts.appAdmin2')

@section('title')
    Configuración de empresa
@endsection

@section('page-header')
    <h1 class="page-title">Configuración de empresa</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Listado de configuraciones de empresa</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('companySetting.index') }}"><i class="fa fa-archive"></i> Configuración de empresa</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Listado</li>
    </ol>
@endsection

@section('content')
    <form action="{{ route('companySetting.update') }}" method="POST" id="company-setting-form">
        @csrf

        <div class="card-body">

            @if(session('success'))
                <div
                        id="flash-success"
                        data-message="{{ session('success') }}"
                ></div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <strong>
                        No se pudieron guardar las configuraciones.
                    </strong>

                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @forelse($modules as $module => $definitions)

                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h3 class="card-title font-weight-bold mb-0">
                            {{ $module ? strtoupper(str_replace('_', ' ', $module)) : 'GENERAL' }}
                        </h3>
                    </div>

                    <div class="card-body">

                        @foreach($definitions as $definition)

                            @php
                                $fieldId = 'setting_' . str_replace(
                                    ['.', ' '],
                                    '_',
                                    $definition->key
                                );

                                $currentValue =
                                    old(
                                        'settings.' . $definition->key,
                                        $definition->current_value
                                    );
                            @endphp

                            <div class="form-group row align-items-center border-bottom pb-3 mb-3">

                                <div class="col-md-5">
                                    <label
                                            for="{{ $fieldId }}"
                                            class="mb-1"
                                    >
                                        {{ $definition->label }}
                                    </label>

                                    @if(!empty($definition->description))
                                        <small class="form-text text-muted">
                                            {{ $definition->description }}
                                        </small>
                                    @endif

                                    <small class="form-text text-muted">
                                        <code>{{ $definition->key }}</code>
                                    </small>
                                </div>

                                <div class="col-md-7">

                                    {{-- BOOLEAN --}}
                                    @if($definition->value_type === 'boolean')

                                        <input
                                                type="hidden"
                                                name="settings[{{ $definition->key }}]"
                                                value="0"
                                        >

                                        <div class="custom-control custom-switch">
                                            <input
                                                    type="checkbox"
                                                    class="custom-control-input"
                                                    id="{{ $fieldId }}"
                                                    name="settings[{{ $definition->key }}]"
                                                    value="1"
                                                    {{ $currentValue ? 'checked' : '' }}
                                            >

                                            <label
                                                    class="custom-control-label"
                                                    for="{{ $fieldId }}"
                                            >
                                                {{ $currentValue ? 'Activado' : 'Desactivado' }}
                                            </label>
                                        </div>

                                        {{-- ENUM --}}
                                    @elseif($definition->value_type === 'enum')

                                        <select
                                                name="settings[{{ $definition->key }}]"
                                                id="{{ $fieldId }}"
                                                class="form-control"
                                        >
                                            @foreach(($definition->options_json ?? []) as $option)
                                                <option
                                                        value="{{ $option }}"
                                                        {{ (string) $currentValue === (string) $option ? 'selected' : '' }}
                                                >
                                                    {{ ucfirst(str_replace('_', ' ', $option)) }}
                                                </option>
                                            @endforeach
                                        </select>

                                        {{-- INTEGER --}}
                                    @elseif($definition->value_type === 'integer')

                                        <input
                                                type="number"
                                                step="1"
                                                name="settings[{{ $definition->key }}]"
                                                id="{{ $fieldId }}"
                                                class="form-control"
                                                value="{{ $currentValue }}"
                                        >

                                        {{-- DECIMAL --}}
                                    @elseif($definition->value_type === 'decimal')

                                        <input
                                                type="number"
                                                step="0.01"
                                                name="settings[{{ $definition->key }}]"
                                                id="{{ $fieldId }}"
                                                class="form-control"
                                                value="{{ $currentValue }}"
                                        >

                                        {{-- STRING --}}
                                    @elseif($definition->value_type === 'string')

                                        <input
                                                type="text"
                                                name="settings[{{ $definition->key }}]"
                                                id="{{ $fieldId }}"
                                                class="form-control"
                                                value="{{ $currentValue }}"
                                        >

                                        {{-- JSON --}}
                                    @elseif($definition->value_type === 'json')

                                        <div class="alert alert-secondary mb-0">
                                            Este tipo de configuración no puede editarse
                                            desde este mantenedor.
                                        </div>

                                    @else

                                        <div class="alert alert-warning mb-0">
                                            Tipo de configuración no soportado:
                                            <strong>
                                                {{ $definition->value_type }}
                                            </strong>
                                        </div>

                                    @endif

                                </div>
                            </div>

                        @endforeach

                    </div>
                </div>

            @empty

                <div class="text-center py-5 text-muted">
                    <i class="fas fa-sliders-h fa-3x mb-3"></i>

                    <p class="mb-1">
                        No existen configuraciones editables para esta empresa.
                    </p>
                </div>

            @endforelse

        </div>

        @if($modules->isNotEmpty())
            <div
                    class="position-sticky bg-white border-top py-3 px-3"
                    style="
            bottom: 0;
            z-index: 1030;
            margin-left: -1.25rem;
            margin-right: -1.25rem;
        "
            >
                <div class="d-flex justify-content-end">
                    @can('edit_companySetting')
                        <button
                                type="submit"
                                class="btn btn-primary"
                                id="btn-save-settings"
                        >
                            <i class="fas fa-save mr-1"></i>
                            Guardar configuraciones
                        </button>
                    @endcan
                </div>
            </div>
        @endif

    </form>
@endsection

@section('scripts')
    <script src="{{ asset('js/companySetting/index.js') }}"></script>
@endsection