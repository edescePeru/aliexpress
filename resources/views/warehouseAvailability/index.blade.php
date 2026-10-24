@extends('layouts.appAdmin2')
@section('title', 'Disponibilidad por almacén')
@section('activeWarehouseAvailability', 'active')
@section('styles-plugins')
<link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection
@section('page-header')
<div class="next-page-heading"><h1 class="page-title">Disponibilidad por almacén</h1><p class="next-page-description">Consulta dónde hay existencias antes de solicitar un traslado.</p></div>
@endsection
@section('page-breadcrumb')<ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('dashboard.principal') }}">Dashboard</a></li><li class="breadcrumb-item active">Disponibilidad por almacén</li></ol>@endsection
@section('page-title')<h5 class="card-title">Consultar producto o variante</h5>@endsection
@section('content')
@if(isset($errors) && $errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
<form id="wa-search" data-products-url="{{ route('warehouseAvailability.products') }}" data-stock-url="{{ route('warehouseAvailability.data') }}">
    <input type="hidden" name="form_tenant_id" value="{{ \App\Support\TenantContext::tenantId() }}">
    <input type="hidden" name="form_company_id" value="{{ \App\Support\TenantContext::companyId() }}">
    <input type="hidden" name="form_branch_id" value="{{ \App\Support\TenantContext::branchId() }}">
    <div class="form-row align-items-end"><div class="form-group col-md-10"><label for="wa-product">Buscar producto o variante</label><select id="wa-product" class="form-control" aria-describedby="wa-help"><option></option></select></div><div class="form-group col-md-2"><button type="submit" id="wa-refresh" class="btn btn-outline-primary btn-block" disabled><i class="fas fa-sync-alt" aria-hidden="true"></i> Actualizar</button></div></div>
    <p id="wa-help" class="text-muted">Disponible = existencia física menos reservas. La mercancía en tránsito se muestra aparte hasta recibirla.</p>
</form>
<div id="wa-error" class="alert alert-danger d-none" role="alert"></div>
<div id="wa-loading" class="d-none mb-3" role="status"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Consultando existencias…</div>
<div id="wa-summary" class="d-none mb-3" aria-live="polite"><strong id="wa-selected"></strong><small id="wa-checked" class="text-muted d-block"></small></div>
<div id="wa-empty" class="alert alert-light border">Selecciona un producto para ver los almacenes de tu local, los generales y los de las otras sucursales.</div>
<div id="wa-groups" class="row" aria-live="polite"></div>
@endsection
@section('plugins')<script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>@endsection
@section('scripts')<script src="{{ asset('js/warehouseAvailability/index.js') }}"></script>@endsection
