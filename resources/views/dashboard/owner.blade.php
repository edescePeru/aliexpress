@extends('layouts.appAdmin2')
@section('title', 'Administración del negocio')
@section('body-class', 'owner-dashboard-shell')
@section('page-header')
    <h1 class="m-0 text-dark">Dashboard del propietario</h1>
@endsection
@section('page-title')
    <h2 class="card-title">Administración del negocio</h2>
@endsection
@section('styles')
    <style>
        body.owner-dashboard-shell #content-body .owner-welcome {
            background: #20364a !important;
            border: 1px solid #20364a !important;
            color: #fff !important;
        }
        body.owner-dashboard-shell #content-body .owner-welcome h2,
        body.owner-dashboard-shell #content-body .owner-welcome p {
            color: #fff !important;
        }
        body.owner-dashboard-shell #content-body .owner-welcome .owner-welcome-plan {
            color: #dce7f1 !important;
        }
    </style>
@endsection

@section('content')
<div class="container-fluid py-3">
    <div class="card owner-welcome">
        <div class="card-body">
            <h2 class="h3">Hola, {{ Auth::user()->name }}</h2>
            <p class="mb-0 owner-welcome-plan">{{ $workspaceTenant->name }} · {{ $workspacePlan ? $workspacePlan->name : 'Plan pendiente de configurar' }}</p>
        </div>
    </div>
    @if(session('status'))<div class="alert alert-info" role="status">{{ session('status') }}</div>@endif
    @if($workspaceCompany && $workspaceBranch)
        <div class="alert alert-success" role="status"><strong>Estás trabajando en {{ $workspaceCompany->trade_name ?: $workspaceCompany->business_name }} — {{ $workspaceBranch->name }}.</strong><br>Los módulos disponibles aparecen en el menú según tus permisos. Puedes cambiar de local o volver a administración desde el selector superior.</div>
    @else
        <div class="alert alert-info" role="status"><strong>Elige dónde trabajar.</strong><br>Selecciona una empresa y un local en el selector superior. Mientras tanto puedes administrar tus empresas y usuarios.</div>
    @endif
    <div class="row">
        @can('list_company')
            @foreach(['companies'=>'Empresas activas', 'branches'=>'Locales activos del negocio'] as $key=>$label)
                @php($usage = $workspaceStructure[$key])
                <div class="col-md-4"><div class="card"><div class="card-body"><p class="text-muted mb-1">{{ $label }}</p><p class="h4 mb-1">{{ $usage['used'] }} / {{ $usage['configured'] ? $usage['limit'] : 'Sin configurar' }}</p><small>{{ $usage['over_limit'] ? 'Por encima del límite. Revisa el plan antes de nuevas altas.' : ($usage['configured'] ? $usage['available'].' cupos disponibles' : 'Consulta la configuración de tu plan.') }}</small></div></div></div>
            @endforeach
        @endcan
        @can('listUser_configUserWeb')
            <div class="col-md-4"><div class="card"><div class="card-body"><p class="text-muted mb-1">Usuarios activos</p><p class="h4 mb-1">{{ $workspaceUsers }} / {{ $workspaceUserLimit === null ? 'Sin configurar' : $workspaceUserLimit }}</p><small>Incluye tu cuenta de propietario.</small></div></div></div>
        @endcan
    </div>
    <div class="mb-3">
        @can('list_company')<a href="{{ route('tenantCompany.index') }}" class="btn btn-outline-primary mr-2 mb-2"><i class="fas fa-building mr-1"></i>Administrar empresas</a>@endcan
        @can('listUser_configUserWeb')<a href="{{ route('configUserWeb.index') }}" class="btn btn-outline-primary mb-2"><i class="fas fa-users mr-1"></i>Administrar usuarios</a>@endcan
    </div>
    <h2 class="h5">Empresas disponibles para trabajar</h2>
    <div class="row">
        @forelse($workspaceCompanies as $company)
            <div class="col-md-6 col-xl-4"><div class="card h-100"><div class="card-body">
                <h3 class="h5">{{ $company->trade_name ?: $company->business_name }}</h3>
                <p class="text-muted mb-2">{{ $company->business_name }}<br>RUC {{ $company->ruc }}</p>
                <p>{{ $company->workspaceBranches->count() }} locales activos autorizados</p>
                @if($company->workspaceBranches->isNotEmpty())
                    <button type="button" class="btn btn-primary" data-owner-company="{{ $company->id }}">Elegir local</button>
                @else
                    <p class="small text-muted mb-0">No tienes un local activo asignado en esta empresa. Revisa sus locales y las autorizaciones del usuario.</p>
                @endif
            </div></div></div>
        @empty
            <div class="col-12"><div class="card"><div class="card-body">No hay empresas activas asignadas para trabajar. Puedes revisar tus empresas desde Administración del negocio.</div></div></div>
        @endforelse
    </div>
</div>
@endsection
