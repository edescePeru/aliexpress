@extends('layouts.appAdmin2')

@section('title')
    Configuración de empresa
@endsection

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Configuración</span><h1 class="page-title">Configuración de empresa</h1><p class="next-page-description">Identidad, catálogo y preferencias de la empresa activa.</p></div>
@endsection

@section('page-title')
    <div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Preferencias de empresa</strong><span>Los cambios se aplican únicamente al guardar.</span></div></div>
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
    <form action="{{ route('companySetting.update') }}" method="POST" id="company-setting-form" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="settings_context_tenant" value="{{ \App\Support\TenantContext::tenantId() }}">
        <input type="hidden" name="settings_context_company" value="{{ \App\Support\TenantContext::companyId() }}">

        <div>

            @if(session('success'))
                <div
                        id="flash-success"
                        data-message="{{ session('success') }}"
                ></div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger m-3" role="alert">
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

                <section class="next-form-section" aria-labelledby="settings-module-{{ $loop->iteration }}">
                    <div class="next-section-header"><div>
                        <span class="next-section-kicker">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <h2 id="settings-module-{{ $loop->iteration }}">{{ $module ? strtoupper(str_replace('_', ' ', $module)) : 'GENERAL' }}</h2>
                    </div></div>

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

                            <div class="form-group row align-items-start border-bottom pb-3 mb-3">

                                <div class="col-lg-5 mb-2 mb-lg-0">
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
                                        <code class="text-break">{{ $definition->key }}</code>
                                    </small>
                                </div>

                                <div class="col-lg-7">

                                    {{-- BOOLEAN --}}
                                    @if($definition->is_image_upload)
                                        <div data-setting-image-wrapper="{{ $definition->id }}">
                                            <input type="file" class="form-control-file" id="{{ $fieldId }}"
                                                   name="setting_images[{{ $definition->id }}]"
                                                   accept="image/png,image/jpeg,image/webp" data-setting-image-input>
                                            <small class="form-text text-muted">
                                                PNG, JPG o WebP, máximo 2 MB; hasta 4096 px por lado y 8 megapíxeles. Sin seleccionar otra imagen, se conserva la actual.
                                            </small>
                                            <div class="mt-3">
                                                <img @if($definition->image_preview_url) src="{{ $definition->image_preview_url }}" @endif
                                                     data-current-src="{{ $definition->image_preview_url }}"
                                                     data-setting-image-preview
                                                     class="img-fluid img-thumbnail {{ $definition->image_preview_url ? '' : 'd-none' }}"
                                                     width="180" alt="Vista previa de {{ $definition->label }}">
                                                <small class="form-text text-muted" data-setting-image-status aria-live="polite">
                                                    {{ $definition->image_preview_url ? 'Imagen actual' : 'Sin imagen configurada' }}
                                                </small>
                                            </div>
                                        </div>
                                    @elseif($definition->value_type === 'boolean')

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
                                            <option value="" {{ $currentValue === null || $currentValue === '' ? 'selected' : '' }}>Sin configurar</option>
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

                </section>

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
            {{-- Preserve the existing sticky save action; sections own the gutters. --}}
            <div class="next-list-toolbar position-sticky bg-white border-top" style="bottom: 0; z-index: 20;">
                <div class="next-toolbar-actions flex-wrap justify-content-end">
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
    <script src="{{ asset('js/companySetting/index.js') }}?v={{ filemtime(public_path('js/companySetting/index.js')) }}"></script>
@endsection
