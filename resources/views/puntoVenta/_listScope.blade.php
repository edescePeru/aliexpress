<div id="sale-list-context" hidden data-tenant="{{ \App\Support\TenantContext::tenantId() }}" data-company="{{ \App\Support\TenantContext::companyId() }}" data-branch="{{ \App\Support\TenantContext::branchId() }}" data-permissions="{{ json_encode($salePermissions) }}"></div>
<div class="alert alert-info" role="status">
    <strong>{{ $saleScopeLabel }}</strong>. Se muestran ventas con sucursal y usuario registrador identificados de la empresa y local activos.
</div>
@if (count($saleCreators) > 1)
    <div class="form-group">
        <label for="filter_created_by_id">Usuario registrador</label>
        <select id="filter_created_by_id" class="form-control form-control-sm">
            <option value="">Todos los autorizados</option>
            @foreach ($saleCreators as $creator)
                <option value="{{ $creator->id }}">{{ $creator->name }}</option>
            @endforeach
        </select>
    </div>
@endif
