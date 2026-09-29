@extends('layouts.appAdmin2')
@section('title', 'Plantillas de perfiles')
@section('activePlatformRoleTemplates', 'active')
@section('styles-plugins')<link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}"><link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">@endsection

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Venti360 · Plataforma</span><h1 class="page-title">Plantillas de perfiles</h1><p class="next-page-description">Administra el catálogo global de perfiles y permisos base.</p></div>
@endsection
@section('page-title')
    <div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Plantillas globales</strong><span>Configura perfiles reutilizables al provisionar tenants.</span></div><div class="next-toolbar-actions"><a href="{{ route('roleTemplate.create') }}" class="btn btn-primary btn-sm">Nueva plantilla</a></div></div>
@endsection
@section('page-breadcrumb')<ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('platform.dashboard') }}">Superadministración</a></li><li class="breadcrumb-item active" aria-current="page">Plantillas</li></ol>@endsection

@section('content')
<section id="role-template-app" class="next-operational-list" aria-label="Listado de plantillas de perfiles" data-url-list="{{ route('roleTemplate.data') }}" data-url-edit="{{ route('roleTemplate.edit', ':id') }}" data-url-toggle="{{ route('roleTemplate.toggleStatus', ':id') }}">
    <div class="next-list-toolbar"><div class="next-list-search-row"><div class="next-list-search-control"><label class="sr-only" for="searchRoleTemplate">Buscar plantillas</label><input type="search" id="searchRoleTemplate" class="form-control" placeholder="Buscar plantilla"></div><div class="next-list-length"><label for="perPageRoleTemplate">Mostrar</label><select id="perPageRoleTemplate" class="form-control"><option>10</option><option>20</option><option>50</option></select></div></div></div>
    <div class="next-list-summary"><div class="next-list-summary-copy"><strong>Plantillas disponibles</strong><span>ordenadas por registro reciente</span></div></div>
    <div class="next-list-content"><div class="table-responsive" tabindex="0"><table class="table table-bordered table-hover table-sm next-data-table"><thead><tr><th class="text-center">ID</th><th class="text-center">Código</th><th class="text-left">Nombre</th><th class="text-center">Permisos</th><th class="text-center">Asignable Owner</th><th class="text-center">Estado</th><th class="text-center" data-buttons>Acciones</th></tr></thead><tbody id="bodyRoleTemplates"></tbody></table></div></div>
    <div class="next-list-pagination"><span class="next-list-page-context">Paginación de plantillas</span><div id="paginationRoleTemplates"></div></div>
</section>
@endsection
@section('plugins')<script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>@endsection
@section('scripts')<script src="{{ asset('js/roleTemplate/index.js') }}"></script>@endsection
