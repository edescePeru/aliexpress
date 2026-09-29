@extends('layouts.appAdmin2')
@section('title', 'Roles por tenant')
@section('activePlatformTenantRoles', 'active')

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Venti360 · Plataforma</span><h1 class="page-title">Roles por tenant</h1><p class="next-page-description">Consulta y administra perfiles dentro del tenant seleccionado.</p></div>
@endsection
@section('page-title')
    <div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Perfiles del tenant</strong><span>La selección de tenant delimita todos los registros y acciones.</span></div><div class="next-toolbar-actions"><button type="button" id="btnNewTenantRole" class="btn btn-primary btn-sm" disabled>Nuevo rol personalizado</button></div></div>
@endsection
@section('page-breadcrumb')<ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('platform.dashboard') }}">Superadministración</a></li><li class="breadcrumb-item active" aria-current="page">Roles por tenant</li></ol>@endsection

@section('content')
<section id="tenant-role-app" class="next-operational-list" aria-label="Listado de roles por tenant" data-url-list="{{ route('tenantRole.data') }}" data-url-create="{{ route('tenantRole.create', ':tenantId') }}" data-url-edit="{{ route('tenantRole.edit', [':tenantId', ':roleId']) }}" data-url-toggle="{{ route('tenantRole.toggleStatus', [':tenantId', ':roleId']) }}">
    <div class="next-list-toolbar"><div class="row next-list-filters-grid"><div class="col-lg-5 col-md-6 next-list-filter"><label for="tenantRoleTenant">Tenant</label><select id="tenantRoleTenant" class="form-control"><option value="">Seleccione un tenant</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}" {{ $selectedTenantId === $tenant->id ? 'selected' : '' }}>{{ $tenant->name }}</option>@endforeach</select></div><div class="col-lg-5 col-md-4 next-list-filter"><label for="searchTenantRole">Buscar</label><input type="search" id="searchTenantRole" class="form-control" placeholder="Código o descripción" disabled></div><div class="col-lg-2 col-md-2 next-list-filter"><label for="perPageTenantRole">Mostrar</label><select id="perPageTenantRole" class="form-control" disabled><option>10</option><option>20</option><option>50</option></select></div></div></div>
    <div class="next-list-summary"><div class="next-list-summary-copy"><strong>Roles disponibles</strong><span>según el tenant activo en este filtro</span></div></div>
    <div class="next-list-content"><div class="table-responsive" tabindex="0"><table class="table table-bordered table-hover table-sm next-data-table"><thead><tr><th class="text-center">ID</th><th class="text-center">Código</th><th class="text-left">Descripción</th><th class="text-left">Origen</th><th class="text-center">Tipo</th><th class="text-center">Permisos</th><th class="text-center">Owner</th><th class="text-center">Estado</th><th class="text-center" data-buttons>Acciones</th></tr></thead><tbody id="bodyTenantRoles"><tr><td colspan="9"><div class="next-table-empty">Seleccione un tenant.</div></td></tr></tbody></table></div></div>
    <div class="next-list-pagination"><span class="next-list-page-context">Paginación de roles</span><div id="paginationTenantRoles"></div></div>
</section>
@endsection
@section('scripts')<script src="{{ asset('js/tenantRole/index.js') }}"></script>@endsection
