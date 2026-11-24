@php
    $capacity = app(\App\Services\TenantPlanService::class)->structureSummary($capacityTenant);
@endphp
<section class="next-form-section mb-3" aria-label="Capacidad del plan del grupo empresarial">
    <h2 class="h6">Capacidad del plan</h2>
    <div class="row">
        @foreach(['companies' => 'Empresas activas', 'branches' => 'Locales activos del tenant'] as $key => $label)
            <div class="col-md-6 mb-2">
                <strong>{{ $label }}:</strong>
                {{ $capacity[$key]['used'] }} / {{ $capacity[$key]['configured'] ? $capacity[$key]['limit'] : 'Pendiente de configurar' }}
                @if($capacity[$key]['configured'])
                    <span class="text-muted">· {{ $capacity[$key]['available'] }} cupos disponibles</span>
                @endif
                @if(!$capacity[$key]['configured'])
                    <p class="text-warning mb-0">Contacte a Venti360 para configurar este máximo antes de crear o reactivar {{ $key === 'companies' ? 'empresas' : 'locales' }}.</p>
                @elseif($capacity[$key]['over_limit'])
                    <p class="text-warning mb-0">El uso actual supera el plan. Sus registros se conservan; no hay cupo para nuevas altas o reactivaciones.</p>
                @elseif($capacity[$key]['available'] === 0)
                    <p class="text-muted mb-0">Límite alcanzado. Libere un cupo o solicite ampliar su plan.</p>
                @endif
            </div>
        @endforeach
    </div>
    <p class="small text-muted mb-0">Los locales se comparten entre todas las empresas del tenant. El principal también cuenta. Un local activo sigue consumiendo cupo aunque su empresa esté desactivada. Una empresa nueva necesita un cupo de empresa y uno de local.</p>
</section>
