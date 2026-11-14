@extends('layouts.appAdmin2')
@section('title', 'Crear sucursal')
@section('activeTenantCompanyConfig', 'active')
@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Configuración de empresa</span><h1 class="page-title">Crear sucursal</h1><p class="next-page-description">Datos del local y asignación de su lista de precios.</p></div>
@endsection
@section('page-breadcrumb')
<ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('tenantBranch.index', $company->id) }}">Sucursales</a></li><li class="breadcrumb-item active">Crear</li></ol>
@endsection
@section('page-title')
<div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Configuración de sucursal</strong><span>{{ $company->business_name }} · RUC {{ $company->ruc }}</span></div></div>
@endsection
@section('content')
<form id="formCreateTenantBranch" data-url="{{ route('tenantBranch.store') }}">
    @csrf
    <input type="hidden" name="company_id" value="{{ $company->id }}">
    <section class="next-form-section" aria-labelledby="branch-details-title">
        <div class="next-section-header"><div><span class="next-section-kicker">01</span><h2 id="branch-details-title">Datos del local</h2><p>Los campos con (*) son obligatorios.</p></div></div>

        <div class="row">
<div class="form-group col-md-4"><label for="branch-code">Código *</label><input id="branch-code" type="text" name="code" class="form-control" maxlength="30" value="{{ old('code', '') }}"></div>
<div class="form-group col-md-8"><label for="branch-name">Nombre *</label><input id="branch-name" type="text" name="name" class="form-control" maxlength="150" value="{{ old('name', '') }}"></div>
<div class="form-group col-md-8"><label for="branch-address">Dirección</label><input id="branch-address" type="text" name="address" class="form-control" maxlength="250" value="{{ old('address', '') }}"><small class="form-text text-muted">Si lo deja vacío se utilizará la dirección de la empresa.</small></div>
<div class="form-group col-md-4"><label for="branch-phone">Teléfono</label><input id="branch-phone" type="text" name="phone" class="form-control" maxlength="30" value="{{ old('phone', '') }}"></div>
<div class="form-group col-md-8">
                        <label for="catalog_slug">Nombre para el enlace público</label>
                        <input type="text" class="form-control @error('catalog_slug') is-invalid @enderror" name="catalog_slug" id="catalog_slug" maxlength="80"
                               pattern="[a-z0-9]+([-_][a-z0-9]+)*" value="{{ old('catalog_slug') }}" placeholder="local_outlet">
                        <small class="form-text text-muted">Usa minúsculas, números, guiones o guiones bajos. Déjalo vacío para mantener el catálogo sin publicar.</small>
                        @error('catalog_slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
        </div>
    </section>
    @include('tenantBranch._pricing')
    <div class="next-list-toolbar"><div class="next-toolbar-actions flex-wrap justify-content-end">
        <a href="{{ route('tenantBranch.index', $company->id) }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Crear sucursal</button>
    </div></div>
</form>

@endsection
@section('scripts')
<script src="{{ asset('js/tenantBranch/create.js') }}"></script>

@endsection
