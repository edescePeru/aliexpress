@extends('layouts.appAdmin2')
@section('title', 'Editar empresa')
@section('activeTenantCompanyConfig', 'active')
@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Configuración de empresa</span><h1 class="page-title">Editar empresa</h1><p class="next-page-description">Identidad de la empresa y enlace público.</p></div>
@endsection
@section('page-breadcrumb')
<ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('tenantCompany.index') }}">Empresas</a></li><li class="breadcrumb-item active">Editar</li></ol>
@endsection
@section('page-title')
<div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Configuración de empresa</strong><span>Empresas del grupo empresarial.</span></div></div>
@endsection
@section('content')
<form id="formEditTenantCompany" data-url="{{ route('tenantCompany.update', $company->id) }}">
@csrf
<section class="next-form-section" aria-labelledby="company-identity-title"><div class="next-section-header"><div><span class="next-section-kicker">01</span><h2 id="company-identity-title">Datos de la empresa</h2><p>Los campos con (*) son obligatorios.</p></div></div><div class="row">
<div class="form-group col-md-4">
<label for="company-ruc">RUC *</label>
<input type="text" id="company-ruc" name="ruc" class="form-control @error('ruc') is-invalid @enderror" maxlength="11" value="{{ old('ruc', $company->ruc) }}">

@error('ruc')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="form-group col-md-8">
<label for="company-business-name">Razón social *</label>
<input type="text" id="company-business-name" name="business_name" class="form-control @error('business_name') is-invalid @enderror" maxlength="200" value="{{ old('business_name', $company->business_name) }}">

@error('business_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="form-group col-md-6">
<label for="company-trade-name">Nombre comercial</label>
<input type="text" id="company-trade-name" name="trade_name" class="form-control @error('trade_name') is-invalid @enderror" maxlength="150" value="{{ old('trade_name', $company->trade_name) }}">

@error('trade_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="form-group col-md-6">
<label for="company-email">Correo</label>
<input type="email" id="company-email" name="email" class="form-control @error('email') is-invalid @enderror" maxlength="150" value="{{ old('email', $company->email) }}">

@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="form-group col-md-8">
<label for="company-address">Dirección</label>
<input type="text" id="company-address" name="address" class="form-control @error('address') is-invalid @enderror" maxlength="250" value="{{ old('address', $company->address) }}">

@error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="form-group col-md-4">
<label for="company-phone">Teléfono</label>
<input type="text" id="company-phone" name="phone" class="form-control @error('phone') is-invalid @enderror" maxlength="30" value="{{ old('phone', $company->phone) }}">

@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
</div></section>
<section class="next-form-section" aria-labelledby="company-public-title"><div class="next-section-header"><div><span class="next-section-kicker">02</span><h2 id="company-public-title">Enlace público</h2></div></div><div class="row">
<div class="form-group col-md-12">
<label for="catalog_slug">Nombre para el enlace público</label>
<input type="text" id="catalog_slug" name="catalog_slug" class="form-control @error('catalog_slug') is-invalid @enderror" maxlength="80" pattern="[a-z0-9]+([-_][a-z0-9]+)*" value="{{ old('catalog_slug', $company->catalog_slug) }}">
<small class="form-text text-muted">Usa minúsculas, números, guiones o guiones bajos. Vacío: sin enlace público de empresa. Cambiarlo modifica la dirección del catálogo.</small>
@error('catalog_slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
</div></section>
<div class="next-list-toolbar"><div class="next-toolbar-actions flex-wrap justify-content-end">
<a href="{{ route('tenantCompany.index') }}" class="btn btn-outline-secondary">Cancelar</a>
<button type="submit" class="btn btn-primary">Guardar cambios</button>
</div></div>
</form>
@include('inventory.managedWarehouses', [
    'warehouseSurfaceClass' => 'next-form-section',
    'branchWarehouseContext' => false,
    'warehouseCompanyId' => $company->id,
    'warehouseBranchId' => null,
])
@endsection
@section('scripts')
<script src="{{ asset('js/tenantCompany/edit.js') }}"></script>
<script src="{{ asset('js/inventory/managedWarehouses.js') }}"></script>
@endsection
