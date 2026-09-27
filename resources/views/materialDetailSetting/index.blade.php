@extends('layouts.appAdmin2')

@section('openConfig')
    menu-open
@endsection

@section('activeConfig')
    active
@endsection

@section('openSettingsMaterialDetail')
    menu-open
@endsection

@section('activeSettingsMaterialDetails')
    active
@endsection

@section('title')
    Parámetros de materiales
@endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Parámetros de materiales</h1>
        <p class="next-page-description">Define qué datos estarán disponibles para la empresa activa.</p>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>Configuración por empresa</strong>
            <span>Los cambios se aplican únicamente al contexto empresarial seleccionado.</span>
        </div>
        <div class="next-toolbar-actions">
            <button type="reset" class="btn btn-outline-secondary" form="material-detail-settings-form">Cancelar</button>
            <button type="submit" class="btn btn-primary" form="material-detail-settings-form">Guardar configuración</button>
        </div>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.principal') }}">Dashboard</a></li>
        <li class="breadcrumb-item">Materiales</li>
        <li class="breadcrumb-item active" aria-current="page">Parámetros</li>
    </ol>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    @php
        $parameterGroups = [
            [
                'number' => '01',
                'title' => 'Identidad comercial',
                'description' => 'Controla los datos descriptivos y comerciales disponibles en la ficha del material.',
                'keys' => ['unit_measure', 'brand', 'exampler', 'genero', 'perecible'],
            ],
            [
                'number' => '02',
                'title' => 'Clasificación',
                'description' => 'Configura la jerarquía de clasificación y el tipo de retacería.',
                'keys' => ['category', 'subcategory', 'material_type', 'subtype', 'typescrap'],
            ],
            [
                'number' => '03',
                'title' => 'Variantes',
                'description' => 'Habilita los atributos utilizados para construir combinaciones de inventario.',
                'keys' => ['talla', 'color'],
            ],
        ];

        $parameterHelpers = [
            'exampler' => 'Al guardar, Modelo habilita también Marca.',
            'subcategory' => 'Al guardar, Subcategoría habilita también Categoría.',
            'material_type' => 'Al guardar, Tipo de material habilita Categoría y Subcategoría.',
            'subtype' => 'Al guardar, Subtipo habilita toda la jerarquía de clasificación.',
            'talla' => 'Talla y Color se habilitan conjuntamente al guardar.',
            'color' => 'Talla y Color se habilitan conjuntamente al guardar.',
        ];

        $groupedKeys = collect($parameterGroups)->pluck('keys')->flatten()->all();
        $extraKeys = array_values(array_diff(array_keys($sections), $groupedKeys));

        if (count($extraKeys) > 0) {
            $parameterGroups[] = [
                'number' => str_pad((string) (count($parameterGroups) + 1), 2, '0', STR_PAD_LEFT),
                'title' => 'Otros parámetros',
                'description' => 'Opciones adicionales definidas por la configuración vigente.',
                'keys' => $extraKeys,
            ];
        }
    @endphp

    <form id="material-detail-settings-form" method="POST" action="{{ route('settings.material-details.store') }}">
        @csrf

        @foreach($parameterGroups as $group)
            <section class="next-form-section" aria-labelledby="material-parameters-{{ $group['number'] }}">
                <div class="next-section-header">
                    <div>
                        <span class="next-section-kicker">{{ $group['number'] }}</span>
                        <h2 id="material-parameters-{{ $group['number'] }}">{{ $group['title'] }}</h2>
                        <p>{{ $group['description'] }}</p>
                    </div>
                </div>

                <div class="row">
                    @foreach($group['keys'] as $key)
                        @if(isset($sections[$key]))
                            @php
                                $inputId = 'material-detail-' . str_replace('_', '-', $key);
                                $helperId = isset($parameterHelpers[$key]) ? $inputId . '-help' : null;
                            @endphp
                            <div class="col-md-6 col-xl-4 mb-3">
                                <div class="custom-control custom-switch">
                                    <input
                                        type="checkbox"
                                        class="custom-control-input"
                                        id="{{ $inputId }}"
                                        name="enabled_sections[]"
                                        value="{{ $key }}"
                                        @if($helperId) aria-describedby="{{ $helperId }}" @endif
                                        {{ in_array($key, $enabled, true) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="{{ $inputId }}">
                                        {{ $sections[$key]['label'] }}
                                    </label>
                                </div>
                                @if($helperId)
                                    <small class="form-text text-muted" id="{{ $helperId }}">{{ $parameterHelpers[$key] }}</small>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>
        @endforeach
    </form>
@endsection
