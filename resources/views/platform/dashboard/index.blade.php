@extends('layouts.appAdmin2')
@section('title', 'Superadministración')
@section('body-class', 'platform-dashboard-shell next-dashboard-shell')
@section('activePlatformDashboard', 'active')
@section('page-header')
    <h1 class="m-0 text-dark">Superadministración</h1>
@endsection
@section('page-title')<h2 class="card-title">Resumen de la plataforma</h2>@endsection
@section('page-breadcrumb')<ol class="breadcrumb float-sm-right"><li class="breadcrumb-item active">Venti360 · Plataforma</li></ol>@endsection

@section('content')
<div class="next-dashboard">
    <div class="next-executive-hero mb-3"><div class="next-executive-hero-content"><span class="next-executive-hero-kicker">Venti360 · Plataforma</span><h2 class="h3">Hola, {{ $user->name }}</h2><p class="mb-0">Administra los negocios y la configuración global de Venti360. Esta vista no requiere seleccionar una empresa o local.</p></div></div>
    <div class="row">
        <div class="col-md-6 col-xl-3 mb-3"><div class="card h-100 mb-0"><div class="card-body next-form-section"><p class="text-muted mb-1">Tenants</p><p class="h3">{{ $platformStats['tenants'] }}</p><small>{{ $platformStats['active_tenants'] }} activos · {{ $platformStats['tenants'] - $platformStats['active_tenants'] }} inactivos</small></div></div></div>
        <div class="col-md-6 col-xl-3 mb-3"><div class="card h-100 mb-0"><div class="card-body next-form-section"><p class="text-muted mb-1">Empresas activas</p><p class="h3">{{ $platformStats['active_companies'] }}</p><small>RUCs de todos los tenants</small></div></div></div>
        <div class="col-md-6 col-xl-3 mb-3"><div class="card h-100 mb-0"><div class="card-body next-form-section"><p class="text-muted mb-1">Locales activos</p><p class="h3">{{ $platformStats['active_branches'] }}</p><small>Incluye locales de empresas inactivas</small></div></div></div>
        <div class="col-md-6 col-xl-3 mb-3"><div class="card h-100 mb-0"><div class="card-body next-form-section"><p class="text-muted mb-1">Usuarios activos</p><p class="h3">{{ $platformStats['active_users'] }}</p><small>Incluye owners; excluye Platform Admin</small></div></div></div>
    </div>
    <p class="small text-muted">Los estados de empresas, locales y usuarios se cuentan tal como están registrados, incluyendo los de tenants inactivos. No representan actividad comercial ni facturación.</p>
    <section class="card" aria-labelledby="platform-review-title">
        <div class="card-header d-flex flex-wrap align-items-center"><h2 id="platform-review-title" class="h5 mb-0">Planes que requieren revisión <span class="badge badge-warning">{{ $platformReviewCount }}</span></h2><a href="{{ route('platformTenant.index') }}" class="btn btn-outline-primary btn-sm ml-auto">Ver tenants</a></div>
        <div class="card-body next-form-section">
            @if($platformReviews->isEmpty())<p class="mb-0">No hay tenants activos con alertas de plan o capacidad en esta revisión.</p>
            @else
                <p class="small text-muted">Se muestran hasta 8 tenants activos. Los avisos no desactivan registros ni cambian el plan.</p>
                <div class="table-responsive"><table class="table table-bordered table-sm next-data-table"><thead><tr><th>Tenant</th><th>Plan</th><th class="text-center">Usuarios activos</th><th class="text-center">Empresas activas</th><th class="text-center">Locales activos</th><th>Revisión</th></tr></thead><tbody>
                    @foreach($platformReviews as $tenant)
                        <tr><td><a href="{{ route('platformTenant.show', ['id'=>$tenant->id]) }}">{{ $tenant->name }}</a></td><td>{{ $tenant->plan_name ?: 'Sin plan' }}</td>
                            @foreach(['users','companies','branches'] as $key)<td class="text-center">{{ $tenant->{'used_'.$key} }} / {{ $tenant->{'max_active_'.$key} === null ? 'Sin configurar' : $tenant->{'max_active_'.$key} }}</td>@endforeach
                            <td>@foreach($tenant->review_reasons as $reason)<div class="small">{{ $reason }}</div>@endforeach</td></tr>
                    @endforeach
                </tbody></table></div>
            @endif
        </div>
    </section>
    <h2 class="h5 mb-3">Administración de plataforma</h2>
    @php
        $platformLinks = [
            ['Tenants','Cuentas, owners, planes y capacidad.','platformTenant.index','fas fa-building'],
            ['Planes','Límites de usuarios, empresas y locales.','plan.index','fas fa-layer-group'],
            ['Plantillas de perfiles','Permisos base reutilizables.','roleTemplate.index','fas fa-user-tag'],
            ['Roles por tenant','Perfiles de cada negocio.','tenantRole.index','fas fa-users-cog'],
            ['Auditoría','Acciones administrativas y trazabilidad.','platformActivity.index','fas fa-history'],
            ['Nubefact','Conexiones y series por empresa.','platformNubefact.index','fas fa-file-invoice'],
            ['Catálogos de guías','Catálogos de remisión de plataforma.','platformShippingCatalogs.index','fas fa-list-alt'],
            ['Tipos de impuesto','Mantenedor global de impuestos.','platformTypeTax.index','fas fa-percentage'],
            ['Parámetros laborales','Valores laborales globales.','platformPercentageWorker.index','fas fa-briefcase'],
        ];
    @endphp
    <div class="row">@foreach($platformLinks as $link)<div class="col-md-6 col-xl-4 mb-3"><a href="{{ route($link[2]) }}" class="card d-block h-100 text-decoration-none mb-0"><div class="card-body next-form-section"><h3 class="h5"><i class="{{ $link[3] }} next-row-action-item-icon mr-2" aria-hidden="true"></i>{{ $link[0] }}</h3><p class="text-muted mb-0">{{ $link[1] }}</p></div></a></div>@endforeach</div>
</div>
@endsection
