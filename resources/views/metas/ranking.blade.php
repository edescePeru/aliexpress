@extends('layouts.appAdmin2')

@section('openMetas') menu-open @endsection
@section('activeMetas') active @endsection
@section('activeMetasProgreso', 'active') {{-- o crea sección específica para ranking --}}
@section('title', 'Ranking de Metas')

@section('styles-plugins')
@endsection

@section('styles')
    <style>
        .meta-progress {
            position: relative;
            height: 14px;
            background-color: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }

        .meta-progress-fill {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            border-radius: 3px;
        }

        .meta-progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 10px;
            font-weight: 600;
            color: #000; /* aquí lo tienes siempre en NEGRO (o cambia a #fff si lo quieres blanco) */
            white-space: nowrap;
        }

    </style>
@endsection

@section('content')
    <div class="container-fluid">

        <div class="row mb-3">
            <div class="col">
                <h4>Ranking de Cumplimiento de Metas</h4>
            </div>
        </div>

        @if(!$tipoMetaValida)
            <div class="alert alert-warning">
                El <strong>tipo de meta</strong> no está configurado correctamente.
                Configura primero el tipo de meta para poder ver el ranking.
            </div>
            @return
        @endif

        {{-- Filtros de periodo --}}
        <div class="card mb-3">
            <div class="card-header">
                Ventas en soles · Período de análisis (Tipo: {{ strtoupper($tipoMeta) }})
            </div>
            <div class="card-body">
                <div class="form-row">
                    {{-- Año --}}
                    <div class="form-group col-md-4">
                        <label>Año <span class="text-danger">*</span></label>
                        <select id="rank-year" class="form-control">
                            <option value="">Seleccione año</option>
                        </select>
                    </div>

                    {{-- Mes --}}
                    <div class="form-group col-md-4">
                        <label>Mes <span class="text-danger">*</span></label>
                        <select id="rank-month" class="form-control" disabled>
                            <option value="">Seleccione mes</option>
                        </select>
                    </div>

                    {{-- Semana / Quincena --}}
                    @if($tipoMeta === 'semanal')
                        <div class="form-group col-md-4">
                            <label>Semana <span class="text-danger">*</span></label>
                            <select id="rank-week" class="form-control" disabled>
                                <option value="">Seleccione semana</option>
                            </select>
                        </div>
                    @elseif($tipoMeta === 'quincenal')
                        <div class="form-group col-md-4">
                            <label>Quincena <span class="text-danger">*</span></label>
                            <select id="rank-quincena" class="form-control" disabled>
                                <option value="">Seleccione quincena</option>
                                <option value="1">Primera quincena (1–15)</option>
                                <option value="2">Segunda quincena (16–fin de mes)</option>
                            </select>
                        </div>
                    @endif
                </div>

                <button id="btn-buscar-ranking" class="btn btn-primary">
                    Buscar ranking
                </button>
            </div>
        </div>

        {{-- Tabla de ranking --}}
        <div class="card">
            <div class="card-header">
                Resultado
            </div>
            <div class="card-body">
                <div id="rank-period-text" class="mb-2 text-muted" style="display:none;"></div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-striped" id="ranking-table">
                        <thead class="thead-light">
                        <tr>
                            <th>Puesto</th>
                            <th>Usuario</th>
                            <th>Meta</th>
                            <th>Monto Meta (S/.)</th>
                            <th>Ventas (S/.)</th>
                            <th>% Cumplimiento</th>
                        </tr>
                        </thead>
                        <tbody>
                        {{-- se llena por JS --}}
                        </tbody>
                    </table>
                </div>

                <div id="ranking-empty" class="text-center text-muted" style="display:none;">
                    No se encontraron metas o ventas para el periodo seleccionado.
                </div>
            </div>
        </div>

    </div>
@endsection

@section('scripts')
    <script>
        const tipoMeta     = @json($tipoMeta);
        const calendarData = @json($calendarData);

        const yearSel  = document.getElementById('rank-year');
        const monthSel = document.getElementById('rank-month');
        const weekSel  = document.getElementById('rank-week');
        const quinSel  = document.getElementById('rank-quincena');

        // Poblar años
        Object.keys(calendarData).forEach(year => {
            const opt = document.createElement('option');
            opt.value = year;
            opt.text  = year;
            yearSel.appendChild(opt);
        });

        function resetMonth() {
            monthSel.innerHTML = '<option value="">Seleccione mes</option>';
            monthSel.disabled = true;
        }
        function resetWeek() {
            if (weekSel) {
                weekSel.innerHTML = '<option value="">Seleccione semana</option>';
                weekSel.disabled = true;
            }
        }
        function resetQuincena() {
            if (quinSel) {
                quinSel.value = '';
                quinSel.disabled = true;
            }
        }

        // Cambio año
        yearSel.addEventListener('change', function(){
            resetMonth();
            resetWeek();
            resetQuincena();

            const y = this.value;
            if (!y || !calendarData[y]) return;
            const months = calendarData[y]['months'] || {};
            Object.keys(months).forEach(m => {
                const opt = document.createElement('option');
                opt.value = m;
                opt.text  = months[m];
                monthSel.appendChild(opt);
            });
            monthSel.disabled = false;
        });

        // Cambio mes
        monthSel.addEventListener('change', function(){
            resetWeek();
            resetQuincena();

            const y = yearSel.value;
            const m = this.value;
            if (!y || !m) return;

            if (tipoMeta === 'semanal') {
                const weeks = (calendarData[y]['weeks'] || {})[m] || [];
                weeks.forEach(w => {
                    const opt = document.createElement('option');
                    opt.value = w.number; // número real de semana
                    opt.text  = 'Semana ' + w.number + ' (' + w.start + ' - ' + w.end + ')';
                    weekSel.appendChild(opt);
                });
                weekSel.disabled = false;
            }

            if (tipoMeta === 'quincenal') {
                quinSel.disabled = true;
                quinSel.value = '';
                // la lógica de fechas la hacemos en el backend, aquí solo habilitamos quincena
                quinSel.disabled = false;
            }
        });

        // ====== AJAX Ranking ======
        $('#btn-buscar-ranking').on('click', function () {
            const year  = $('#rank-year').val();
            const month = $('#rank-month').val();
            const week  = $('#rank-week').val();
            const quin  = $('#rank-quincena').val();

            if (!year || !month || (tipoMeta === 'semanal' && !week) || (tipoMeta === 'quincenal' && !quin)) {
                alert('Debe completar los campos requeridos del periodo.');
                return;
            }

            $('#ranking-table tbody').empty();
            $('#ranking-empty').hide();
            $('#rank-period-text').hide();

            $.ajax({
                url: '{{ route('metas.ranking.data') }}',
                method: 'GET',
                data: {
                    year: year,
                    month: month,
                    week: week,
                    quincena: quin
                },
                success: function (res) {
                    if (!res.ok) {
                        alert(res.error || 'Ocurrió un error.');
                        return;
                    }

                    const data = res.data || [];

                    if (res.period) {
                        $('#rank-period-text')
                            .text('Periodo analizado: ' + res.period.start + ' al ' + res.period.end)
                            .show();
                    }

                    const $tbody = $('#ranking-table tbody');
                    $tbody.empty();

                    if (data.length === 0) {
                        $('#ranking-empty').show();
                        return;
                    } else {
                        $('#ranking-empty').hide();
                    }

                    data.forEach(row => {
                        const pctRaw   = parseFloat(row.porcentaje || 0);
                        const widthPct = Math.max(1, Math.min(100, pctRaw)); // nunca 0 para que se vea algo

                        // colores semáforo (opcional, puedes dejar todo verde si quieres)
                        let color = '#28a745'; // verde
                        if (pctRaw < 40) {
                            color = '#dc3545'; // rojo
                        } else if (pctRaw < 80) {
                            color = '#ffc107'; // naranja
                        }

                        const tr = `
                            <tr>
                                <td>${row.puesto}</td>
                                <td>${$('<div>').text(row.user_name).html()}</td>
                                <td>${$('<div>').text(row.meta_nombre).html()}</td>
                                <td>S/ ${parseFloat(row.meta_monto).toFixed(2)}</td>
                                <td>S/ ${parseFloat(row.ventas_total).toFixed(2)}</td>
                                <td>
                                    <div class="meta-progress">
                                        <div class="meta-progress-fill"
                                             style="width: ${widthPct}%; background-color: ${color};">
                                        </div>
                                        <span class="meta-progress-text">${pctRaw.toFixed(1)}%</span>
                                    </div>
                                </td>
                            </tr>
                        `;
                        $tbody.append(tr);
                    });
                },
                error: function (xhr) {
                    console.error(xhr);
                    alert('Error al obtener el ranking.');
                }
            });
        });
    </script>
@endsection