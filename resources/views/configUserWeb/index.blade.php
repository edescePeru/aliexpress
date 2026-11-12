@extends('layouts.appAdmin2')
@section('openDataGeneral', 'menu-open')
@section('activeDataGeneral', 'active')
@section('activeConfigUserWeb', 'active')
@section('title', 'Usuarios del negocio')
@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Configuración · Accesos</span><h1 class="page-title">Usuarios del negocio</h1><p class="next-page-description">Usuarios y accesos del grupo empresarial.</p></div>
@endsection
@section('page-breadcrumb')
<ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('dashboard.principal') }}">Dashboard</a></li><li class="breadcrumb-item active">Usuarios del negocio</li></ol>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Usuarios del negocio</strong><span>Perfiles, estado y empresas autorizadas.</span></div><div class="next-toolbar-actions flex-wrap">
@can('createUser_configUserWeb')<button type="button" id="btnNewUser" class="btn btn-primary"><i class="fas fa-user-plus mr-1" aria-hidden="true"></i> Nuevo usuario</button>@endcan
</div></div>
@endsection
@section('content')
<div id="config-user-web-app"
             data-url-users="{{ route('configUserWeb.getUsers') }}"

             data-url-plan-summary="{{route('configUserWeb.planSummary')}}"

             data-url-edit="{{route('configUserWeb.edit',['id' => ':id'])}}"

             data-url-update="{{route('configUserWeb.update',['id' => ':id'])}}"

             data-url-reset-password="{{route('configUserWeb.resetPassword',['id' => ':id'])}}"

             data-url-change-status="{{route('configUserWeb.changeStatus',['id' => ':id'])}}"

             data-url-create="{{route('configUserWeb.create')}}"
             data-can-edit="{{ auth()->user()->can('editUser_configUserWeb') ? '1' : '0' }}"
             data-can-status="{{ auth()->user()->can('changeStatusUser_configUserWeb') ? '1' : '0' }}"
             data-can-reset="{{ auth()->user()->can('resetPasswordUser_configUserWeb') ? '1' : '0' }}"
        >
<section id="planSummaryCard" class="next-form-section" aria-label="Plan del negocio">
<div class="next-section-header"><div><h2>Plan del negocio</h2><p>Disponibilidad de usuarios según el plan actual.</p></div></div>
<div id="planSummaryLoading" class="text-muted" role="status">Cargando información del plan...</div>
<div id="planSummaryContent" class="d-none">
<div class="row">
<div class="col-6 col-lg-3 mb-2"><small class="text-muted d-block">Plan contratado</small><strong id="planName">—</strong></div>
<div class="col-6 col-lg-3 mb-2"><small class="text-muted d-block">Usuarios activos / límite</small><strong><span id="activeUsersCount">0</span> / <span id="maxUsersCount">0</span></strong></div>
<div class="col-6 col-lg-3 mb-2"><small class="text-muted d-block">Cupos disponibles</small><strong id="availableUsersCount">0</strong></div>
<div class="col-6 col-lg-3 mb-2"><small class="text-muted d-block">Usuarios inactivos</small><strong id="inactiveUsersCount">0</strong></div>
</div>
<div class="d-flex justify-content-between mb-1"><small>Uso de usuarios del plan</small><small id="planUsageText">0%</small></div>
<div class="progress progress-xs"><div id="planUsageProgress" class="progress-bar bg-success" role="progressbar" aria-label="Uso del plan" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div></div>
<div id="planLimitAlert" class="alert alert-warning mt-3 mb-0 d-none"><strong>Límite alcanzado.</strong> No hay cupos disponibles para activar o crear nuevos usuarios.</div>
</div></section>
<div class="next-list-toolbar"><div class="row next-list-filters-grid">
<div class="col-md-6 next-list-filter"><label for="searchUser">Buscar</label><input id="searchUser" type="search" class="form-control" placeholder="Buscar por nombre o email"></div>
<div class="col-md-3 col-sm-6 next-list-filter"><label for="statusFilter">Estado</label><select id="statusFilter" class="form-control"><option value="active">Activos</option><option value="inactive">Inactivos</option><option value="all">Todos</option></select></div>
<div class="col-md-3 col-sm-6 next-list-filter"><label for="perPage">Mostrar</label><select id="perPage" class="form-control"><option value="10">10 registros</option><option value="25">25 registros</option><option value="50">50 registros</option></select></div>
</div></div>
<div class="table-responsive"><table class="table table-bordered table-hover table-sm mb-0"><thead><tr><th class="text-center">#</th><th>Nombre</th><th>Email</th><th class="text-center">Última modificación</th><th>Perfil</th><th class="text-center">Estado</th><th class="text-center">Imagen</th><th class="text-center">Acciones</th></tr></thead><tbody id="usersTableBody"><tr><td colspan="8" class="text-center py-4">Cargando usuarios...</td></tr></tbody></table></div>
<div class="next-list-toolbar"><div class="d-flex flex-wrap align-items-center justify-content-between"><small id="paginationInfo" aria-live="polite"></small><nav aria-label="Páginas de usuarios"><ul id="paginationLinks" class="pagination pagination-sm flex-wrap mb-0"></ul></nav></div></div>
</div>
@endsection
@section('scripts')
<script src="{{ asset('js/configUserWeb/index.js') }}"></script>
@endsection
