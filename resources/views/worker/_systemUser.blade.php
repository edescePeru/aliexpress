<input type="hidden" name="form_tenant_id" value="{{ \App\Support\TenantContext::tenantId() }}">
<input type="hidden" name="form_company_id" value="{{ \App\Support\TenantContext::companyId() }}">
<input type="hidden" name="form_branch_id" value="{{ \App\Support\TenantContext::branchId() }}">
<div class="form-group">
    <label for="worker-system-user">Usuario del sistema asociado (opcional)</label>
    <select id="worker-system-user" name="user_id" class="form-control">
        <option value="">Sin usuario asociado</option>
        @if (isset($worker) && $worker->user_id && !$systemUsers->contains('id', $worker->user_id))
            <option value="{{ $worker->user_id }}" selected>Vínculo actual #{{ $worker->user_id }} no disponible: revise o desvincule</option>
        @endif
        @foreach ($systemUsers as $systemUser)
            <option value="{{ $systemUser->id }}" {{ isset($worker) && (int) $worker->user_id === (int) $systemUser->id ? 'selected' : '' }}>{{ $systemUser->name }} — {{ $systemUser->email }}</option>
        @endforeach
    </select>
    <small class="form-text text-muted">Asocia una cuenta existente de esta empresa. No crea usuarios ni otorga permisos. La sucursal del trabajador es su base laboral.</small>
</div>
