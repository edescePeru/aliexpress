@extends('layouts.appAdmin2')

@section('title', 'Superadministración')
@section('activePlatformDashboard', 'active')

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Venti360 · Plataforma</span>
        <h1 class="page-title">Superadministración</h1>
        <p class="next-page-description">Gestiona el catálogo global, los tenants y la trazabilidad de la plataforma.</p>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar next-page-toolbar-context-only">
        <div class="next-toolbar-context">
            <strong>Consola de plataforma</strong>
            <span>Sesión global de {{ $user->name }} · {{ $user->email }}</span>
        </div>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right"><li class="breadcrumb-item active" aria-current="page">Superadministración</li></ol>
@endsection

@section('content')
    <section class="next-operational-list" aria-labelledby="platform-overview-title">
        <div class="p-3 p-md-4">
            <div class="row">
                <div class="col-12">
                    <div class="next-list-surface-heading">
                        <strong id="platform-overview-title">Administración global</strong>
                        <span>Accesos directos a las áreas operativas de la plataforma.</span>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                @php
                    $platformLinks = [
                        ['Directorio', 'Tenants', 'Consulta cuentas, planes, owners y estado operativo.', route('platformTenant.index')],
                        ['Catálogo global', 'Planes', 'Administra capacidades y disponibilidad de los planes.', route('plan.index')],
                        ['Acceso', 'Plantillas de perfiles', 'Define permisos base reutilizables por tenant.', route('roleTemplate.index')],
                        ['Acceso por tenant', 'Roles por tenant', 'Mantén perfiles dentro de un tenant seleccionado.', route('tenantRole.index')],
                        ['Trazabilidad', 'Auditoría', 'Consulta acciones administrativas y su contexto.', route('platformActivity.index')],
                    ];
                @endphp
                @foreach($platformLinks as $link)
                    <div class="col-lg-4 col-md-6 mb-3">
                        <a class="card h-100 mb-0 text-decoration-none" href="{{ $link[3] }}">
                            <div class="card-body"><span class="next-page-eyebrow">{{ $link[0] }}</span><h2 class="h5 mt-2 mb-1">{{ $link[1] }}</h2><p class="text-muted mb-0">{{ $link[2] }}</p></div>
                        </a>
                    </div>
                @endforeach
                @can('list_percentageWorker')
                    <div class="col-lg-4 col-md-6 mb-3">
                        <a class="card h-100 mb-0 text-decoration-none" href="{{ route('platformPercentageWorker.index') }}">
                            <div class="card-body"><span class="next-page-eyebrow">Configuración global</span><h2 class="h5 mt-2 mb-1">Parámetros laborales</h2><p class="text-muted mb-0">Mantén los valores laborales compartidos por el sistema.</p></div>
                        </a>
                    </div>
                @endcan
            </div>
        </div>
    </section>
@endsection
