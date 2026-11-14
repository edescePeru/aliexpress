@extends('layouts.appAdmin2')

@section('openMetas')
    menu-open
@endsection

@section('activeMetas')
    active
@endsection

@section('activeMetasCreate', 'active')

@section('title', 'Crear Meta')

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">

@endsection

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Ventas · Metas</span><h1 class="page-title">Crear meta</h1><p class="next-page-description">Objetivos por usuario de la empresa · Todas las sucursales · PEN.</p></div>
@endsection
@section('page-title')
    <div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Crear meta</strong><span>Metas comerciales de la empresa activa.</span></div></div>
@endsection

@section('content')
    <div class="next-operational-list">


        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('metas.store') }}" method="POST" id="form-meta">
            @csrf

            {{-- Tipo de meta fijo --}}
            <div class="next-form-section">
                <div class="next-section-header">
                    Tipo de Meta
                </div>
                <div class="p-0">
                    <p class="mb-0">
                        Todas las metas actuales se configurarán como:
                        <strong>{{ strtoupper($tipoMeta) }}</strong>
                    </p>
                </div>
            </div>

            {{-- Rango de fechas (año / mes / semana o quincena) --}}
            <div class="next-form-section">
                <div class="next-section-header">
                    Periodo de la Meta
                </div>
                <div class="p-0">
                    <div class="form-row">
                        {{-- Año --}}
                        <div class="form-group col-md-4">
                            <label for="year">Año <span class="text-danger">*</span></label>
                            <select name="year" id="year" class="form-control">
                                <option value="">Seleccione año</option>
                            </select>
                            @error('year')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>

                        {{-- Mes --}}
                        <div class="form-group col-md-4">
                            <label for="month">Mes <span class="text-danger">*</span></label>
                            <select name="month" id="month" class="form-control" disabled>
                                <option value="">Seleccione mes</option>
                            </select>
                            @error('month')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>

                        {{-- Semana / Quincena según tipo --}}
                        @if($tipoMeta === 'semanal')
                            <div class="form-group col-md-4">
                                <label for="week">Semana <span class="text-danger">*</span></label>
                                <select name="week" id="week" class="form-control" disabled>
                                    <option value="">Seleccione semana</option>
                                </select>
                                @error('week')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                        @elseif($tipoMeta === 'quincenal')
                            <div class="form-group col-md-4">
                                <label for="quincena">Quincena <span class="text-danger">*</span></label>
                                <select name="quincena" id="quincena" class="form-control" disabled>
                                    <option value="">Seleccione quincena</option>
                                    <option value="1">Primera quincena (1–15)</option>
                                    <option value="2">Segunda quincena (16–fin de mes)</option>
                                </select>
                                @error('quincena')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                        @endif
                    </div>
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
                                   value="{{ old('nombre') }}">
                            @error('nombre')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>

                        <div class="form-group col-md-4">
                            <label for="meta-monto">Monto objetivo (S/.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="monto" id="meta-monto" class="form-control"
                                   value="{{ old('monto') }}">
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
                        {{-- aquí se agregan dinámicamente --}}
                    </ul>

                    {{-- inputs hidden --}}
                    <div id="workers-hidden-inputs"></div>

                    @error('users')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
            </div>

            <div class="next-list-toolbar"><div class="next-toolbar-actions flex-wrap">
            <button type="submit" class="btn btn-primary">
                Guardar Meta
            </button>
            <a href="{{ route('metas.index') }}" class="btn btn-outline-secondary">
                Cancelar
            </a>
            </div></div>
        </form>
    </div>
@endsection

@section('plugins')
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
@endsection

@section('scripts')
    <script>
        // Datos de calendario generados en el backend (solo usamos años y meses)
        const tipoMeta     = @json($tipoMeta);
        const calendarData = @json($calendarData);

        const yearSelect  = document.getElementById('year');
        const monthSelect = document.getElementById('month');
        const weekSelect  = document.getElementById('week');
        const quinSelect  = document.getElementById('quincena');

        // ====== POBLAR AÑOS ======
        Object.keys(calendarData).forEach(year => {
            const opt = document.createElement('option');
            opt.value = year;
            opt.text  = year;
            yearSelect.appendChild(opt);
        });

        // Helpers reset
        function resetMonth() {
            monthSelect.innerHTML = '<option value="">Seleccione mes</option>';
            monthSelect.disabled = true;
        }
        function resetWeek() {
            if (weekSelect) {
                weekSelect.innerHTML = '<option value="">Seleccione semana</option>';
                weekSelect.disabled = true;
            }
        }
        function resetQuincena() {
            if (quinSelect) {
                quinSelect.value = '';
                quinSelect.disabled = true;
            }
        }

        // ====== CAMBIO DE AÑO ======
        yearSelect.addEventListener('change', function () {
            resetMonth();
            resetWeek();
            resetQuincena();

            const year = this.value;
            if (!year || !calendarData[year]) return;

            const months = calendarData[year]['months'] || {};
            Object.keys(months).forEach(m => {
                const opt = document.createElement('option');
                opt.value = m;
                opt.text  = months[m];
                monthSelect.appendChild(opt);
            });
            monthSelect.disabled = false;
        });

        // ====== CAMBIO DE MES ======
        monthSelect.addEventListener('change', function () {
            resetWeek();
            resetQuincena();

            const year  = yearSelect.value;
            const month = this.value;
            if (!year || !month) return;

            // -- Semanal: pedir semanas reales al backend --
            if (tipoMeta === 'semanal') {
                $.get("{{ route('metas.weeks') }}", {year: year, month: month})
                    .done(function (weeks) {
                        weekSelect.innerHTML = '<option value="">Seleccione semana</option>';

                        weeks.forEach(function (w) {
                            const opt = document.createElement('option');
                            opt.value = w.value;   // ej. 44
                            opt.text  = w.label;   // "Semana 44 (01/11/2025 - 07/11/2025)"
                            weekSelect.appendChild(opt);
                        });

                        weekSelect.disabled = false;
                    })
                    .fail(function () {
                        console.error('No se pudieron cargar las semanas');
                    });
            }

            // -- Quincenal: solo habilitar combo de quincena --
            if (tipoMeta === 'quincenal') {
                quinSelect.disabled = false;
            }
        });

        // ====== SELECT2 + ASIGNAR TRABAJADORES ======
        $(function () {
            $('#worker-select').select2({
                placeholder: 'Buscar usuario...',
                width: '100%'
            });
        });

        const workersList  = document.getElementById('workers-list');
        const hiddenInputs = document.getElementById('workers-hidden-inputs');

        function addWorker(id, text) {
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

        @php
            $initialUsers = $users->whereIn('id', (array) old('users', []))->map->only(['id', 'name'])->values();
        @endphp
        const previousUsers = @json($initialUsers);
        previousUsers.forEach(function (user) { addWorker(user.id, user.name); });

        // Asignar seleccionado
        document.getElementById('btn-add-worker').addEventListener('click', function () {
            const select = $('#worker-select').select2('data')[0];
            if (!select || !select.id) return;
            addWorker(select.id, select.text);
        });

        // Asignar todos
        document.getElementById('btn-add-all-workers').addEventListener('click', function () {
            $('#worker-select option').each(function () {
                const id   = $(this).val();
                const text = $(this).text();
                if (id) addWorker(id, text);
            });
        });

        // Quitar usuario
        workersList.addEventListener('click', function (e) {
            if (!e.target.classList.contains('btn-remove-worker')) return;
            const id = e.target.getAttribute('data-id');
            document.getElementById('worker-li-' + id)?.remove();
            document.getElementById('worker-input-' + id)?.remove();
        });
    </script>
@endsection