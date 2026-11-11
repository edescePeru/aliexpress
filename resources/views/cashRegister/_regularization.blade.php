@can('regularize_caja')
    <div id="cash-regularization-context" class="d-none"
         data-tenant="{{ \App\Support\TenantContext::tenantId() }}"
         data-company="{{ \App\Support\TenantContext::companyId() }}"
         data-branch="{{ \App\Support\TenantContext::branchId() }}"
         data-session="{{ isset($cashRegister) ? $cashRegister->id : '' }}"
         data-url="{{ route('cashMovement.regularize') }}"></div>
    <div id="modalRegularize" class="modal fade next-aux-modal" tabindex="-1" role="dialog" aria-labelledby="regularize-title" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="formRegularize">
                    @csrf
                    <div class="modal-header">
                        <h4 class="modal-title" id="regularize-title">Regularizar movimiento diferido</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="cash_movement_id" name="cash_movement_id">
                        <div class="alert alert-info">
                            Ingrese el monto neto recibido. La diferencia con el importe original se registrará como comisión cuando el canal lo permita.
                            Si la sesión original está cerrada, el abono ingresará en tu sesión abierta de esta misma caja y local.
                        </div>
                        <p class="text-muted" id="regularize-original-amount"></p>
                        <div class="form-group">
                            <label for="regularize_amount">Monto neto recibido</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="regularize_amount" name="amount_regularize" required>
                        </div>
                        <div class="form-group">
                            <label for="regularize_observation">Observación (opcional)</label>
                            <textarea class="form-control" id="regularize_observation" name="observation" maxlength="1000" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" id="btn_regularizar" class="btn btn-success">Regularizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
