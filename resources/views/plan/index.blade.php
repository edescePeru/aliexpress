@extends('layouts.appAdmin2')
@section('title', 'Planes')
@section('activePlatformPlans', 'active')

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Venti360 · Plataforma</span><h1 class="page-title">Planes</h1><p class="next-page-description">Administra capacidades y disponibilidad del catálogo global de planes.</p></div>
@endsection
@section('page-title')
    <div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Catálogo de planes</strong><span>Busca y administra planes globales.</span></div><div class="next-toolbar-actions"><button type="button" id="btnNewPlan" class="btn btn-primary btn-sm">Nuevo plan</button></div></div>
@endsection
@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('platform.dashboard') }}">Superadministración</a></li><li class="breadcrumb-item active" aria-current="page">Planes</li></ol>
@endsection

@section('content')
<section id="plan-app" class="next-operational-list" aria-label="Listado de planes" data-url-list="{{ route('plan.data') }}" data-url-store="{{ route('plan.store') }}" data-url-update="{{ route('plan.update', ':id') }}" data-url-toggle="{{ route('plan.toggleStatus', ':id') }}">
    <div class="next-list-toolbar"><div class="next-list-search-row"><div class="next-list-search-control"><label class="sr-only" for="searchPlan">Buscar planes</label><input type="search" id="searchPlan" class="form-control" placeholder="Buscar por nombre o código"></div><div class="next-list-length"><label for="perPagePlan">Mostrar</label><select id="perPagePlan" class="form-control"><option>10</option><option>20</option><option>50</option></select></div></div></div>
    <div class="next-list-summary"><div class="next-list-summary-copy"><strong>Planes globales</strong><span>ordenados por registro reciente</span></div></div>
    <div class="next-list-content"><div class="table-responsive" tabindex="0"><table class="table table-bordered table-hover table-sm next-data-table"><thead><tr><th class="text-center">ID</th><th class="text-center">Código</th><th class="text-left">Plan</th><th class="text-center">Usuarios</th><th class="text-center">Tenants</th><th class="text-center">Estado</th><th class="text-center" data-buttons>Acciones</th></tr></thead><tbody id="bodyPlans"></tbody></table></div></div>
    <div class="next-list-pagination"><span class="next-list-page-context">Paginación de planes</span><div id="paginationPlans"></div></div>
</section>

@foreach(['Create' => 'Nuevo plan', 'Edit' => 'Editar plan'] as $mode => $title)
<div class="modal fade next-aux-modal" id="modal{{ $mode }}Plan" tabindex="-1" role="dialog" aria-labelledby="modal{{ $mode }}PlanLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered" role="document"><form id="form{{ $mode }}Plan" class="next-aux-modal-form"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="modal{{ $mode }}PlanLabel">{{ $title }}</h5><button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>@csrf @if($mode === 'Edit')<input type="hidden" id="edit_plan_id">@endif<div class="modal-body">@include('plan.partials.form', ['prefix' => strtolower($mode)])</div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">{{ $mode === 'Create' ? 'Guardar' : 'Guardar cambios' }}</button></div></div></form></div></div>
@endforeach
@endsection
@section('scripts')<script src="{{ asset('js/plan/index.js') }}"></script>@endsection
