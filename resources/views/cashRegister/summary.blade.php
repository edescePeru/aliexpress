@extends('layouts.appAdmin2')

@section('activeAdminFinancialSummary', 'active')
@section('title', 'Resumen financiero')
@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Caja · consulta</span><h1 class="page-title">Resumen financiero</h1><p class="next-page-description">Saldos y actividad de la empresa, separados por moneda.</p></div>
@endsection

@section('page-title')
<h2 class="h6 mb-0">Resumen operativo · Solo lectura</h2>
@endsection
@section('content')
<div class="next-form-section">
    <div class="nav nav-tabs mb-3">
        @foreach (['balances' => 'Saldos de caja', 'activity' => 'Actividad del período'] as $key => $title)
            <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
               href="{{ route('financial.summary', ['tab' => $key, 'branch_id' => $branchId]) }}">{{ $title }}</a>
        @endforeach
    </div>
    <form action="{{ route('financial.summary') }}" method="GET" class="row align-items-end mb-0">
        @if ($tab === 'activity')
            <input type="hidden" name="tab" value="activity">
        @endif
        @if ($branches->count() > 1 || $branchId !== null)
        <div class="col-sm-6 col-lg-3 form-group">
            <label for="branch_id">Sucursal</label>
            <select name="branch_id" id="branch_id" class="form-control">
                <option value="">Todas las sucursales</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        @if ($tab === 'activity')
        <div class="col-sm-6 col-lg-2 form-group">
            <label for="period">Período</label>
            <select id="period" name="period" class="form-control">
                <option value="range" {{ !$allHistory ? 'selected' : '' }}>Rango de fechas</option>
                <option value="all" {{ $allHistory ? 'selected' : '' }}>Desde el inicio</option>
            </select>
        </div>
        <div class="col-sm-6 col-lg-2 form-group">
            <label for="from">Movimientos desde</label>
            <input type="date" name="from" id="from" class="form-control" value="{{ $from }}">
        </div>
        <div class="col-sm-6 col-lg-2 form-group">
            <label for="to">Hasta</label>
            <input type="date" name="to" id="to" class="form-control" value="{{ $to }}">
        </div>
        @endif
        @if ($tab === 'activity' || $branches->count() > 1 || $branchId !== null)
        <div class="col-sm-6 col-lg-3 form-group">
            <button type="submit" class="btn btn-primary">Consultar</button>
        </div>
        @endif
    </form>
    @if ($tab === 'activity')
        <script>
            (() => {
                const period = document.getElementById('period');
                const dates = [document.getElementById('from'), document.getElementById('to')];
                const update = () => dates.forEach(input => {
                    input.disabled = period.value === 'all';
                });
                period.addEventListener('change', update);
                update();
            })();
        </script>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    @if ($tab === 'balances')
    <h2 class="h5">Saldos actuales</h2>
    <p class="text-muted">El saldo rendido y el de los turnos abiertos se muestran por separado. El fondo inicial de una sesión de efectivo puede provenir del saldo ya consolidado.</p>
    @if ($branchId !== null)
        <p class="text-muted">El saldo consolidado de las cajas compartidas se consulta en la vista de empresa.</p>
    @endif

    @forelse ($groups as $currency => $group)
        <section class="mb-3">
            <h3 class="h6">{{ $currency }}</h3>
            <div class="table-responsive">
                <table class="table table-sm table-bordered next-data-table mb-0">
                    <thead>
                        <tr>
                            <th>Caja</th>
                            <th class="text-right">Saldo rendido</th>
                            <th class="text-right">En turnos abiertos</th>
                            <th class="text-right">Por abonar</th>
                            <th class="text-center">Consulta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($group['rows'] as $row)
                            <tr>
                                <td>{{ $row['box']->name }}
                                    <small class="d-block text-muted">
                                        {{ optional($row['box']->branch)->name ?? 'Compartida' }}
                                        @if (!$row['box']->is_active) · Inactiva @endif
                                    </small>
                                </td>
                                <td class="text-right">@if ($row['consolidated'] === null)
                                    <a href="{{ route('financial.summary') }}">Ver saldo de empresa</a>
                                @else
                                    {{ number_format($row['consolidated'], 2) }}
                                @endif</td>
                                <td class="text-right">{{ number_format($row['open'], 2) }}
                                    <small class="d-block text-muted">{{ $row['sessions'] }} sesiones</small>
                                </td>
                                <td class="text-right">{{ number_format($row['pending'], 2) }}</td>
                                <td class="text-center"><a class="btn btn-sm btn-outline-primary"
                                       href="{{ route('financial.summary.movements', ['cashBox' => $row['box']->id, 'branch_id' => $branchId]) }}">Ver movimientos</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Totales por columna</th>
                            @foreach (['consolidated', 'open', 'pending'] as $field)
                                <th class="text-right">{{ number_format($group['totals'][$field], 2) }}</th>
                            @endforeach
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    @empty
        <p class="text-muted">No hay cajas para el contexto seleccionado.</p>
    @endforelse

    @elseif ($groups)
        <h2 class="h5 mt-4">Movimientos del período</h2>
        @if ($allHistory)
        <p class="font-weight-bold">Desde el inicio · Todo el historial registrado</p>
    @endif
    <p class="text-muted">Se agrupan por fecha de registro del movimiento que afecta saldo. Los abonos están expresados en neto. La comisión se muestra como dato del cobro.</p>
        <div class="table-responsive">
            <table class="table table-sm table-bordered next-data-table">
                <thead>
                    <tr>
                        <th>Moneda</th>
                        <th>Caja</th>
                        <th class="text-right">Cobros e ingresos netos</th>
                        <th class="text-right">Egresos</th>
                        <th class="text-right">Comisiones</th>
                        <th class="text-right">Entrada de fondos</th>
                        <th class="text-right">Traspasos de apertura</th>
                        <th class="text-center">Consulta</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($groups as $currency => $group)
                        @foreach ($group['rows'] as $row)
                            <tr>
                                <td>{{ $currency }}</td>
                                <td>{{ $row['box']->name }}</td>
                                @foreach (['receipts', 'expenses', 'commissions', 'external_funds', 'opening_transfers'] as $field)
                                    <td class="text-right">{{ number_format($row[$field], 2) }}</td>
                                @endforeach
                                <td class="text-center"><a class="btn btn-sm btn-outline-primary"
                                       href="{{ route('financial.summary.movements', ['cashBox' => $row['box']->id, 'branch_id' => $branchId, 'date_from' => $from, 'date_to' => $to, 'period' => $allHistory ? 'all' : null]) }}">Ver movimientos</a></td>
                            </tr>
                        @endforeach
                        <tr class="font-weight-bold">
                            <td>{{ $currency }}</td>
                            <td>Total</td>
                            @foreach (['receipts', 'expenses', 'commissions', 'external_funds', 'opening_transfers'] as $field)
                                <td class="text-right">{{ number_format($group['totals'][$field], 2) }}</td>
                            @endforeach
                            <td></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-muted">No hay cajas para el contexto seleccionado.</p>
    @endif
</div>
@endsection
