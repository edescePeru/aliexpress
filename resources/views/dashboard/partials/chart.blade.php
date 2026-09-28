<article class="next-dashboard-panel next-chart-panel next-chart-panel--{{ $kind }}" data-dashboard-chart="{{ $kind }}">
    <header class="next-dashboard-panel-header">
        <div>
            <h3>{{ $title }}</h3>
            <p id="{{ $periodId }}">Hoy</p>
        </div>
        <div class="next-chart-filters" role="group" aria-label="Periodo de {{ strtolower($title) }}">
            <button type="button" class="btn btn-outline-secondary btn-sm {{ $buttonClass }} active" data-filter="daily" aria-pressed="true">Diario</button>
            <button type="button" class="btn btn-outline-secondary btn-sm {{ $buttonClass }}" data-filter="weekly" aria-pressed="false">Semanal</button>
            <button type="button" class="btn btn-outline-secondary btn-sm {{ $buttonClass }}" data-filter="monthly" aria-pressed="false">Mensual</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="{{ $kind === 'sales' ? '#dateRangeModalSale' : '#dateRangeModalUtilidad' }}">Fechas</button>
        </div>
    </header>
    <div class="next-dashboard-panel-body">
        <div class="next-chart-canvas">
            <canvas id="{{ $canvasId }}" aria-label="Gráfico de {{ strtolower($title) }}" role="img"></canvas>
            <div class="next-chart-state" data-chart-state role="status">Cargando datos…</div>
        </div>
        @if($kind === 'sales')
            <div class="next-chart-totals">
                <span>Total del periodo</span><strong id="quantityKnobTotalSale">—</strong>
            </div>
        @else
            <div class="next-chart-totals next-chart-totals--three">
                <div><span>Ingresos</span><strong id="quantityKnobIngresos">—</strong></div>
                <div><span>Egresos</span><strong id="quantityKnobEgresos">—</strong></div>
                <div><span>Neto</span><strong id="quantityKnobUtilidad">—</strong></div>
            </div>
        @endif
    </div>
</article>
