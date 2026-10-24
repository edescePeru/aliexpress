@csrf
<input type="hidden" name="form_tenant_id" value="{{ \App\Support\TenantContext::tenantId() }}">
<input type="hidden" name="form_company_id" value="{{ \App\Support\TenantContext::companyId() }}">
<input type="hidden" name="form_branch_id" value="{{ \App\Support\TenantContext::branchId() }}">
