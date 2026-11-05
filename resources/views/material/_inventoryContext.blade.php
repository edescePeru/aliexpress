<div id="material-inventory-context" class="d-none"
     data-tenant="{{ \App\Support\TenantContext::tenantId() }}"
     data-company="{{ \App\Support\TenantContext::companyId() }}"
     data-branch="{{ \App\Support\TenantContext::branchId() }}">
    <input type="hidden" name="material_inventory_tenant" value="{{ \App\Support\TenantContext::tenantId() }}">
    <input type="hidden" name="material_inventory_company" value="{{ \App\Support\TenantContext::companyId() }}">
    <input type="hidden" name="material_inventory_branch" value="{{ \App\Support\TenantContext::branchId() }}">
</div>
