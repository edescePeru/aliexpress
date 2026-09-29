@extends('layouts.appAdmin2')
@section('title', 'Auditoría')
@section('activePlatformActivity', 'active')

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Venti360 · Plataforma</span><h1 class="page-title">Auditoría</h1><p class="next-page-description">Consulta la trazabilidad de acciones administrativas globales y por tenant.</p></div>
@endsection
@section('page-title')<div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Registro de actividad</strong><span>Filtra eventos por acción, tenant, administrador o periodo.</span></div></div>@endsection
@section('page-breadcrumb')<ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('platform.dashboard') }}">Superadministración</a></li><li class="breadcrumb-item active" aria-current="page">Auditoría</li></ol>@endsection

@section('content')
<section id="platform-activity-app" class="next-operational-list" aria-label="Auditoría de plataforma" data-url-data="{{ route('platformActivity.data') }}" data-url-show="{{ route('platformActivity.show', ['id' => ':id']) }}">
    <div class="next-list-toolbar"><div class="row next-list-filters-grid">
        <div class="col-lg-3 col-md-6 next-list-filter"><label for="filterSearch">Buscar</label><input type="search" id="filterSearch" class="form-control" placeholder="Acción o entidad"></div>
        <div class="col-lg-3 col-md-6 next-list-filter"><label for="filterAction">Acción</label><select id="filterAction" class="form-control"><option value="">Todas</option>@foreach($actions as $action)<option value="{{ $action }}">{{ $action }}</option>@endforeach</select></div>
        <div class="col-lg-3 col-md-6 next-list-filter"><label for="filterTenant">Tenant</label><select id="filterTenant" class="form-control"><option value="">Todos</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}">{{ $tenant->name }}</option>@endforeach</select></div>
        <div class="col-lg-3 col-md-6 next-list-filter"><label for="filterCauser">Administrador</label><select id="filterCauser" class="form-control"><option value="">Todos</option>@foreach($platformUsers as $platformUser)<option value="{{ $platformUser->id }}">{{ $platformUser->name }}</option>@endforeach</select></div>
        <div class="col-md-3 next-list-filter"><label for="filterStartDate">Desde</label><input type="date" id="filterStartDate" class="form-control"></div>
        <div class="col-md-3 next-list-filter"><label for="filterEndDate">Hasta</label><input type="date" id="filterEndDate" class="form-control"></div>
        <div class="col-md-2 next-list-filter"><label for="filterPerPage">Mostrar</label><select id="filterPerPage" class="form-control"><option>10</option><option>25</option><option>50</option></select></div>
        <div class="col-md-4 d-flex align-items-end next-list-filter"><button type="button" id="btnClearFilters" class="btn btn-outline-secondary btn-block"><i class="fas fa-eraser mr-1" aria-hidden="true"></i>Limpiar filtros</button></div>
    </div></div>
    <div class="next-list-summary"><div class="next-list-summary-copy"><strong>Eventos registrados</strong><span>más recientes primero</span></div></div>
    <div class="next-list-content"><div class="table-responsive" tabindex="0"><table class="table table-bordered table-hover table-sm next-data-table"><thead><tr><th class="text-center">ID</th><th class="text-center">Fecha</th><th class="text-left">Acción</th><th class="text-left">Tenant</th><th class="text-left">Administrador</th><th class="text-left">Entidad</th><th class="text-center" data-buttons>Detalle</th></tr></thead><tbody id="activityTableBody"></tbody></table></div><div id="activityEmpty" class="next-table-empty d-none">No se encontraron registros de auditoría.</div></div>
    <div class="next-list-pagination"><div id="activityPaginationInfo" class="next-list-page-context" aria-live="polite"></div><div id="activityPagination"></div></div>
</section>
@endsection
@section('scripts')<script src="{{ asset('js/platform/activityLog/index.js') }}"></script>@endsection
