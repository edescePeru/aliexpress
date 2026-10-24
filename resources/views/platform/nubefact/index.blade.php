@extends('layouts.appAdmin2')

@section('title', 'Conexiones Nubefact')

@section('activePlatformNubefact', 'active')

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Venti360 · Plataforma</span><h1 class="page-title">Conexiones Nubefact</h1><p class="next-page-description">Administra la conexión de facturación electrónica de cada empresa.</p></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('platform.dashboard') }}">Superadministración</a></li><li class="breadcrumb-item active">Nubefact</li></ol>
@endsection

@section('page-title')
    <h5 class="card-title">Empresas y conexiones</h5>
@endsection

@section('content')
    <form method="GET" action="{{ route('platformNubefact.index') }}" class="form-inline">
        <label for="tenant_id" class="mr-2">Tenant</label>
        <select name="tenant_id" id="tenant_id" class="form-control form-control-sm mr-2 mb-2">
            <option value="">Todos</option>
            @foreach($tenants as $tenant)
                <option value="{{ $tenant->id }}" {{ (int) $tenantId === (int) $tenant->id ? 'selected' : '' }}>{{ $tenant->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary btn-sm mb-2" type="submit">Filtrar</button>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-bordered mb-0">
            <thead><tr><th class="text-left">Tenant</th><th class="text-left">Empresa</th><th class="text-center">RUC</th><th class="text-center">Conexión</th><th class="text-center">Series</th><th class="text-center">Acción</th></tr></thead>
            <tbody>
            @forelse($companies as $company)
                @php($connection = $company->nubefactConfig)
                <tr>
                    <td>{{ optional($company->tenant)->name }}</td><td>{{ $company->business_name }}</td><td class="text-center">{{ $company->ruc }}</td>
                    <td class="text-center">
                        @if(!$connection)<span class="badge badge-secondary">Sin configurar</span>
                        @elseif($connection->is_enabled)<span class="badge badge-success">Habilitada</span>
                        @else<span class="badge badge-secondary">Deshabilitada</span>@endif
                    </td>
                    <td class="text-center">{{ $connection ? $connection->serie_factura . ' / ' . $connection->serie_boleta : '—' }}</td>
                    <td class="text-center"><a class="btn btn-outline-primary btn-sm" href="{{ route('platformNubefact.edit', [$company->tenant_id, $company->id]) }}">Configurar</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">No hay empresas para este filtro.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $companies->appends(['tenant_id' => $tenantId])->links() }}</div>
@endsection
