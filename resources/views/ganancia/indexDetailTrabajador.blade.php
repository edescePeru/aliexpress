@extends('layouts.appAdmin2')
@section('content')
<div class="card">
    <div class="card-header">Ganancia de la venta #{{ $sale->id }} · {{ $report->author($sale) }}</div>
    <div class="card-body table-responsive">
        <table class="table table-bordered">
            <thead><tr><th>Línea</th><th>Vendido (S/)</th><th>Costo (S/)</th><th>Ganancia (S/)</th></tr></thead>
            <tbody>
            @foreach($sale->details as $detail)
                @php
                    [$quantity, $sold, $cost, $profit] = $report->line($detail);
                @endphp
                <tr>
                    <td>{{ $detail->description ?: ('#' . $detail->id) }}</td>
                    <td>{{ number_format($sold, 2) }}</td>
                    <td>{{ $cost === null ? 'Sin costo registrado' : number_format($cost, 2) }}</td>
                    <td>{{ $profit === null ? 'Sin costo registrado' : number_format($profit, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
