@extends('layouts.appAdmin2')

@section('openMetas')
    menu-open
@endsection

@section('activeMetas')
    active
@endsection

@section('activeMetasCreate', 'active')

@section('title', 'Editar Meta')

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Ventas · Metas</span><h1 class="page-title">Editar meta</h1><p class="next-page-description">Objetivos por usuario de la empresa · Todas las sucursales · PEN.</p></div>
@endsection
@section('page-title')
    <div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Editar meta</strong><span>Metas comerciales de la empresa activa.</span></div></div>
@endsection

@section('content')
    <div class="next-operational-list">


        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('metas.update', $meta->id) }}" method="POST" id="form-meta">
            @csrf
            @method('PUT')

            {{-- Tipo de meta y periodo (solo lectura) --}}
            <div class="next-form-section">
                <div class="next-section-header">
                    Información de la Meta
                </div>
                <div class="p-0">
                    <p class="mb-1">
                        Tipo de Meta:
                        <strong>{{ strtoupper($meta->tipo ?? $tipoMeta) }}</strong>
                    </p>
                    <p class="mb-0">
                        Periodo:
                        <strong>
                            {{ \Carbon\Carbon::parse($meta->fecha_inicio)->format('d/m/Y') }}
                            -
                            {{ \Carbon\Carbon::parse($meta->fecha_fin)->format('d/m/Y') }}
                        </strong>
                    </p>
                </div>
            </div>

            {{-- Datos de la Meta --}}
            <div class="next-form-section">
                <div class="next-section-header">
                    Datos de la Meta
                </div>
                <div class="p-0">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="meta-nombre">Nombre de la meta <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" id="meta-nombre" class="form-control"
                                   value="{{ old('nombre', $meta->nombre) }}">
                            @error('nombre')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>

                        <div class="form-group col-md-4">
                            <label for="meta-monto">Monto objetivo (S/.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="monto" id="meta-monto" class="form-control"
                                   value="{{ old('monto', $meta->monto) }}">
                            @error('monto')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Usuarios --}}
            <div class="next-form-section">
                <div class="next-section-header">
                    Usuarios asignados a la Meta
                </div>
                <div class="p-0">
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-6">
                            <label for="worker-select">Seleccionar usuario</label>
                            <select id="worker-select" class="form-control select2">
                                <option value="">Buscar usuario...</option>
                                @foreach($users as $w)
                                    <option value="{{ $w->id }}">
                                        {{ $w->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                Puedes conservar los usuarios ya asignados.
                            </small>
                        </div>

                        <div class="form-group col-md-3">
                            <button type="button" id="btn-add-worker" class="btn btn-primary btn-block">
                                Asignar seleccionado
                            </button>
                        </div>

                        <div class="form-group col-md-3">
                            <button type="button" id="btn-add-all-workers" class="btn btn-outline-secondary btn-block">
                                Asignar todos
                            </button>
                        </div>
                    </div>

                    <hr>

                    <h6>Usuarios asignados:</h6>
                    <ul id="workers-list" class="list-group">
                        {{-- se llenará por JS con los ya asignados --}}
                    </ul>

                    {{-- inputs hidden --}}
                    <div id="workers-hidden-inputs"></div>

                    @error('users')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
            </div>

            <div class="next-list-toolbar"><div class="next-toolbar-actions flex-wrap">
            <button type="submit" class="btn btn-primary">
                Guardar cambios
            </button>
            <a href="{{ route('metas.index') }}" class="btn btn-outline-secondary">
                Cancelar
            </a>
            </div></div>
        </form>
    </div>
@endsection

@section('plugins')
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
@endsection

@section('scripts')
    <script>
        // Usuarios ya asignados a esta meta
        @php
            $initialUsers = $users->whereIn('id', (array) old('users', $selectedUserIds))->map->only(['id', 'name'])->values();
        @endphp
        const existingWorkers = @json($initialUsers);

        $(function () {
            $('#worker-select').select2({
                placeholder: 'Buscar usuario...',
                width: '100%'
            });

            const workersList  = document.getElementById('workers-list');
            const hiddenInputs = document.getElementById('workers-hidden-inputs');

            function addWorker(id, text) {
                // evitar duplicados
                if (document.getElementById('worker-li-' + id)) return;

                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center';
                li.id = 'worker-li-' + id;
                li.innerHTML = `
                    <span></span>
                    <button type="button" class="btn btn-sm btn-danger btn-remove-worker" data-id="${id}">
                        Quitar
                    </button>
                `;
                li.querySelector('span').textContent = text;
            workersList.appendChild(li);

                const input = document.createElement('input');
                input.type  = 'hidden';
                input.name  = 'users[]';
                input.value = id;
                input.id    = 'worker-input-' + id;
                hiddenInputs.appendChild(input);
            }

            // Precargar los usuarios ya asignados
            existingWorkers.forEach(function (w) {
                addWorker(w.id, w.name);
            });

            // Asignar seleccionado
            $('#btn-add-worker').on('click', function () {
                const select = $('#worker-select').select2('data')[0];
                if (!select || !select.id) return;
                addWorker(select.id, select.text);
            });

            // Asignar todos (de la lista disponible)
            $('#btn-add-all-workers').on('click', function () {
                $('#worker-select option').each(function () {
                    const id   = $(this).val();
                    const text = $(this).text();
                    if (id) addWorker(id, text);
                });
            });

            // Quitar usuario
            $('#workers-list').on('click', '.btn-remove-worker', function () {
                const id = $(this).data('id');
                $('#worker-li-' + id).remove();
                $('#worker-input-' + id).remove();
            });
        });
    </script>
@endsection