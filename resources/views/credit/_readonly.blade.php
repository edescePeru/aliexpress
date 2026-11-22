<div id="supplier-credits" data-mode="{{ $creditMode }}" data-url="{{ url($creditMode === 'credits' ? 'dashboard/get/only/credits/supplier' : 'dashboard/get/invoices/pending') }}" data-detail-url="{{ url('dashboard/get/credit/by/id') }}" data-pays-url="{{ url('dashboard/get/pays/credit') }}">
    <div class="next-list-toolbar"><div class="row align-items-end">
        <div class="col-sm-6 col-lg-3 form-group mb-2"><label for="start2">Emisión desde</label><input id="start2" name="start" type="date" class="form-control date-range-filter2"></div>
        <div class="col-sm-6 col-lg-3 form-group mb-2"><label for="end2">Emisión hasta</label><input id="end2" name="end" type="date" class="form-control date-range-filter2"></div>
    </div>
    @if($creditMode === 'pending')
    <p class="text-muted mb-0">Créditos registrados y su saldo actual. Este listado no representa las entradas disponibles para asociar.</p>
    <details class="mt-2"><summary>Columnas visibles</summary><div id="credit-columns" class="row mt-2"></div></details>
    @endif
    </div>
    <div id="credit-error" class="next-form-section d-none" role="alert"></div>
    <div class="next-form-section"><p id="credit-empty" class="text-muted d-none" aria-live="polite"></p><div class="table-responsive" tabindex="0" aria-label="Créditos; desplazamiento horizontal"><table id="dynamic-table2" class="table table-bordered table-hover table-sm next-data-table"><thead><tr></tr></thead><tbody></tbody></table></div></div>
</div>
<div class="modal fade" id="credit-detail" tabindex="-1" aria-labelledby="credit-detail-title" aria-hidden="true"><div class="modal-dialog modal-lg next-aux-modal"><div class="modal-content">
    <div class="modal-header"><h2 class="modal-title h5" id="credit-detail-title">Detalle del crédito</h2><button class="close" type="button" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>
    <div class="modal-body"><div id="credit-detail-body" aria-live="polite"></div><h3 class="h6 mt-3">Historial de pagos</h3><div id="credit-pays" class="table-responsive"></div></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cerrar</button></div>
</div></div></div>