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
    <div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Empresas y conexiones</strong><span>Configuración administrativa por tenant y empresa.</span></div></div>
@endsection

@section('content')
    <section class="next-operational-list" aria-label="Conexiones por empresa">
    <div class="next-list-toolbar">
    <form method="GET" action="{{ route('platformNubefact.index') }}" class="next-toolbar-actions flex-wrap">
        <label for="tenant_id" class="mr-2">Tenant</label>
        <select name="tenant_id" id="tenant_id" class="form-control form-control-sm">
            <option value="">Todos</option>
            @foreach($tenants as $tenant)
                <option value="{{ $tenant->id }}" {{ (int) $tenantId === (int) $tenant->id ? 'selected' : '' }}>{{ $tenant->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary btn-sm" type="submit">Filtrar</button>
    </form>
    </div>
    <div class="next-list-summary"><div class="next-list-summary-copy"><strong>{{ $companies->total() }} empresas</strong><span>El estado indica habilitación; no verifica conexión con Nubefact.</span></div></div>

    <div class="next-list-content"><div class="table-responsive" tabindex="0" aria-label="Empresas y conexiones; desplazamiento horizontal">
        <table class="table table-hover table-bordered table-sm next-data-table mb-0">
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
                    <td class="text-center"><a class="btn btn-outline-secondary btn-sm" href="{{ route('platformNubefact.edit', [$company->tenant_id, $company->id]) }}">{{ $connection ? 'Editar' : 'Configurar' }}</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="next-table-empty">No hay empresas para este filtro.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    </div>
    <div class="next-list-toolbar">{{ $companies->appends(['tenant_id' => $tenantId])->links() }}</div>
    </section>
@endsection
