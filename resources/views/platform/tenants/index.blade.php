@extends('layouts.appAdmin2')
@section('title', 'Tenants')
@section('activePlatformTenants', 'active')

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Venti360 · Plataforma</span><h1 class="page-title">Tenants</h1><p class="next-page-description">Consulta y administra las cuentas principales de la plataforma.</p></div>
@endsection
@section('page-title')<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Directorio de tenants</strong><span>Revisa plan, capacidad, owner y estado operativo.</span></div><div class="next-toolbar-actions"><button type="button" id="btnNewTenant" class="btn btn-primary btn-sm">Nuevo tenant</button></div></div>@endsection
@section('page-breadcrumb')<ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('platform.dashboard') }}">Superadministración</a></li><li class="breadcrumb-item active" aria-current="page">Tenants</li></ol>@endsection

@section('content')
<section id="platform-tenants-app" class="next-operational-list" aria-label="Directorio de tenants" data-url-data="{{ route('platformTenant.data') }}" data-url-show="{{ route('platformTenant.show', ['id' => ':id']) }}" data-url-create="{{ route('platformTenant.create') }}">
    <div class="next-list-toolbar"><div class="row next-list-filters-grid"><div class="col-md-4 next-list-filter"><label for="filterSearch">Buscar</label><input type="search" id="filterSearch" class="form-control" placeholder="Nombre del tenant"></div><div class="col-md-3 next-list-filter"><label for="filterPlan">Plan</label><select id="filterPlan" class="form-control"><option value="">Todos</option>@foreach($plans as $plan)<option value="{{ $plan->id }}">{{ $plan->name }}</option>@endforeach</select></div><div class="col-md-3 next-list-filter"><label for="filterStatus">Estado</label><select id="filterStatus" class="form-control"><option value="">Todos</option><option value="1">Activos</option><option value="0">Inactivos</option></select></div><div class="col-md-2 next-list-filter"><label for="filterPerPage">Mostrar</label><select id="filterPerPage" class="form-control"><option>10</option><option>25</option><option>50</option></select></div></div></div>
    <div class="next-list-summary"><div class="next-list-summary-copy"><strong>Tenants disponibles</strong><span>ordenados por registro reciente</span></div></div>
    <div class="next-list-content"><div class="table-responsive" tabindex="0"><table class="table table-bordered table-hover table-sm next-data-table"><thead><tr><th class="text-left">Tenant</th><th class="text-left">Plan</th><th class="text-center">Usuarios</th><th class="text-center">Empresas</th><th class="text-left">Owner</th><th class="text-center">Estado</th><th class="text-center" data-buttons>Acciones</th></tr></thead><tbody id="tenantTableBody"></tbody></table></div><div id="tenantEmpty" class="next-table-empty d-none">No se encontraron tenants.</div></div>
    <div class="next-list-pagination"><div id="tenantPaginationInfo" class="next-list-page-context" aria-live="polite"></div><div id="tenantPagination"></div></div>
</section>
@endsection
@section('scripts')<script src="{{ asset('js/platform/tenants/index.js') }}"></script>@endsection
