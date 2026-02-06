@extends('layouts.appAdmin2')

@section('activeAdminFinancialSummary')
    active
@endsection

@section('title', 'Resumen financiero')

@section('page-header')
    <h1 class="page-title">Resumen financiero</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Resumen financiero – Cajas centrales</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Resumen financiero</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col">
                <small class="text-muted">
                    Solo considera las sesiones activas del usuario propietario (owner).
                </small>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">

                <table class="table table-bordered table-sm mb-0">
                    <thead class="thead-light">
                    <tr>
                        <th>Concepto</th>
                        <th class="text-right">Monto (S/)</th>
                        <th class="text-center">Acción</th>
                    </tr>
                    </thead>
                    <tbody>

                    {{-- ========================= --}}
                    {{-- Cajas (Efectivo / Bancos) --}}
                    {{-- ========================= --}}
                    @foreach($rows as $row)
                        <tr>
                            <td>
                                <strong>{{ $row['label'] }}</strong>
                                @if($row['cash_box_type'] === 'bank' && $row['pending'] > 0)
                                    <br>
                                    <small class="text-danger">
                                        Pendiente por regularizar: S/
                                        {{ number_format($row['pending'], 2) }}
                                    </small>
                                @endif
                            </td>

                            <td class="text-right">
                                {{ number_format($row['balance'], 2) }}
                            </td>

                            <td class="text-center">
                                <a target="_blank" href="{{$row['url_admin']}}"
                                   class="btn btn-sm btn-outline-primary mb-1">
                                    Ir a caja {{ $row['label'] }}
                                </a>
                            </td>
                        </tr>
                    @endforeach

                    {{-- ========================= --}}
                    {{-- Comisiones confirmadas --}}
                    {{-- ========================= --}}
                    <tr class="table-warning">
                        <td>
                            <strong>Diferidos</strong>
                        </td>
                        <td class="text-right">
                            {{ number_format($pendingTotal, 2) }}
                        </td>
                        <td class="text-center">
                            {{--<a target="_blank" href="{{$urlDiferidos}}"
                               class="btn btn-sm btn-outline-primary mb-1">
                                Ir a Diferidos
                            </a>--}}
                        </td>
                    </tr>
                    <tr class="table-danger">
                        <td>
                            <strong>Comisiones / Impuestos</strong>
                        </td>
                        <td class="text-right">
                            {{ number_format($commissionsConfirmed, 2) }}
                        </td>
                        <td class="text-center">

                        </td>
                    </tr>

                    </tbody>

                    <tfoot class="bg-dark text-white">
                    <tr>
                        <th>TOTAL INGRESOS</th>
                        <th class="text-right">
                            {{ number_format($grandTotal, 2) }}
                        </th>
                        <th></th>
                    </tr>
                    </tfoot>
                </table>

            </div>
        </div>

    </div>
@endsection