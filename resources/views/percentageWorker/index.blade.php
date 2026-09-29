@extends('layouts.appAdmin2')
@section('openPercentageWorker', 'menu-open')
@section('activeListPercentageWorker', 'active')
@section('title', 'Parámetros laborales')

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Venti360 · Plataforma</span><h1 class="page-title">Parámetros laborales</h1><p class="next-page-description">Mantén los valores laborales globales utilizados por el sistema.</p></div>
@endsection
@section('page-title')<div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Configuración laboral</strong><span>Valores globales con edición individual controlada.</span></div></div>@endsection
@section('page-breadcrumb')<ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('platform.dashboard') }}">Superadministración</a></li><li class="breadcrumb-item active" aria-current="page">Parámetros laborales</li></ol>@endsection

@section('content')
<section class="next-operational-list" aria-label="Parámetros laborales">
    <div class="next-list-summary"><div class="next-list-summary-copy"><strong class="next-list-count">{{ $porcentages->count() }}</strong><span>{{ $porcentages->count() === 1 ? 'parámetro configurado' : 'parámetros configurados' }}</span></div></div>
    <div class="next-list-content"><div class="table-responsive" tabindex="0"><table class="table table-bordered table-hover table-sm next-data-table"><thead><tr><th class="text-left">Parámetro</th><th class="text-right">Valor</th><th class="text-center" data-buttons>Acciones</th></tr></thead><tbody>@forelse($porcentages as $percentage)<tr><td>@switch($percentage->name)@case('assign_family') Asignación familiar @break @case('essalud') EsSalud @break @case('rmv') Remuneración mínima vital @break @default {{ $percentage->name }} @endswitch</td><td class="text-right" data-total>{{ $percentage->value }}</td><td class="text-center" data-buttons><div class="dropdown next-row-actions"><button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle next-row-actions-trigger" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Abrir acciones del parámetro"><i class="fas fa-ellipsis-h" aria-hidden="true"></i></button><div class="dropdown-menu dropdown-menu-right next-row-actions-menu"><a class="dropdown-item" href="{{ route('platformPercentageWorker.edit', $percentage->id) }}"><i class="fas fa-pen next-row-action-item-icon" aria-hidden="true"></i><span>Editar</span></a></div></div></td></tr>@empty<tr><td colspan="3"><div class="next-table-empty">No existen parámetros configurados.</div></td></tr>@endforelse</tbody></table></div></div>
</section>
@endsection
