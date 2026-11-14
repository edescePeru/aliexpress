@extends('layouts.appAdmin2')
@section('title', 'Empresas')
@section('activeTenantCompanyConfig', 'active')
@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Configuración de empresa</span><h1 class="page-title">Empresas</h1><p class="next-page-description">Identidad y sucursales de las empresas de tu grupo empresarial.</p></div>
@endsection
@section('page-breadcrumb')
<ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('tenantCompany.index') }}">Empresas</a></li><li class="breadcrumb-item active">Listado</li></ol>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Listado de empresas</strong><span>Empresas del grupo empresarial.</span></div><div class="next-toolbar-actions flex-wrap">
@can('create_company')<a href="{{ route('tenantCompany.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1" aria-hidden="true"></i>Nueva empresa</a>@endcan
</div></div>
@endsection
@section('content')
<div class="next-operational-list">
<div class="next-list-summary"><strong>{{ $companies->count() }} empresas</strong><span>Activas primero · razón social</span></div>
<div class="table-responsive" tabindex="0" aria-label="Empresas; desplazamiento horizontal">
<table class="table table-hover table-sm next-data-table">
<thead><tr><th class="text-center">RUC</th><th>Razón social</th><th>Nombre comercial</th><th>Slug</th><th class="text-center">Sucursales</th><th class="text-center">Estado</th><th class="text-center">Acciones</th></tr></thead>
<tbody>
@forelse($companies as $company)
<tr>
<td class="text-center">{{ $company->ruc }}</td>
<td>{{ $company->business_name }}</td>
<td>{{ $company->trade_name ?: '—' }}</td>
<td>{{ $company->catalog_slug ?: 'Sin configurar' }}</td>
<td class="text-center">@can('list_branch')<a href="{{ route('tenantBranch.index', $company->id) }}" aria-label="Ver {{ $company->branches_count }} sucursales de {{ $company->business_name }}">{{ $company->branches_count }}</a>@else{{ $company->branches_count }}@endcan</td>
<td class="text-center"><span class="badge {{ $company->is_active ? 'badge-success' : 'badge-secondary' }}">{{ $company->is_active ? 'Activa' : 'Inactiva' }}</span></td>
<td class="text-center">
@if(auth()->user()->can('list_branch') || auth()->user()->can('edit_company') || auth()->user()->can('enable_company'))
<div class="dropdown next-row-actions">
<button type="button" class="btn btn-outline-secondary btn-sm next-row-actions-toggle" data-toggle="dropdown" aria-expanded="false" aria-label="Acciones de {{ $company->business_name }}"><i class="fas fa-ellipsis-h" aria-hidden="true"></i></button>
<div class="dropdown-menu dropdown-menu-right">
@can('list_branch')<a href="{{ route('tenantBranch.index', $company->id) }}" class="dropdown-item"><i class="fas fa-store" aria-hidden="true"></i>Ver sucursales</a>@endcan
@can('edit_company')<a href="{{ route('tenantCompany.edit', $company->id) }}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i>Editar</a>@endcan
@can('enable_company')
<button type="button" class="dropdown-item {{ $company->is_active ? 'text-danger' : '' }}"
 data-toggle-company-status data-company-id="{{ $company->id }}" data-company-name="{{ $company->business_name }}"
 data-is-active="{{ $company->is_active ? 1 : 0 }}" data-url="{{ route('tenantCompany.toggleStatus', $company->id) }}">
<i class="fas {{ $company->is_active ? 'fa-ban' : 'fa-check' }}" aria-hidden="true"></i>{{ $company->is_active ? 'Desactivar' : 'Activar' }}</button>
@endcan
</div></div>
@endif
</td>
</tr>
@empty
<tr><td colspan="7" class="text-center py-5 text-muted">No hay empresas registradas.</td></tr>
@endforelse
</tbody></table>
</div></div>
@endsection
@section('scripts')
<script src="{{ asset('js/tenantCompany/index.js') }}"></script>
@endsection
