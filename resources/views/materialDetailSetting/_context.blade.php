<input type="hidden" name="material_context_tenant" value="{{ \App\Support\TenantContext::tenantId() }}">
<input type="hidden" name="material_context_company" value="{{ \App\Support\TenantContext::companyId() }}">
<input type="hidden" name="material_parameters_version" value="{{ $materialParameterVersion }}">
