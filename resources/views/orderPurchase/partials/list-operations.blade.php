@can('update_orderPurchaseNormal')
<div id="modalState" class="modal fade next-aux-modal" tabindex="-1" role="dialog" aria-labelledby="purchase-state-title" aria-hidden="true">
    <div class="modal-dialog" role="document"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="purchase-state-title">Cambiar estado de la orden</h5><button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>
        <form id="formStates" class="next-aux-modal-form" data-url="{{ route('state.order.purchase.change') }}" onsubmit="return false;">
            @csrf
            <div class="modal-body"><input type="hidden" id="orderPurchase_id" name="orderPurchase_id"><div class="form-group mb-0"><label for="stateOrder">Estado de la orden</label><select id="stateOrder" name="stateOrder" class="form-control"><option value=""></option><option value="stand_by">PENDIENTE</option><option value="send">ENVIADO</option><option value="pick_up">RECOGIDO</option></select></div></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button><button type="button" id="btn-changeState" class="btn btn-primary">Guardar</button></div>
        </form>
    </div></div>
</div>
@endcan
