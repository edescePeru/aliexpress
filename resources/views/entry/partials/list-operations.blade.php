<input type="hidden" id="entry-invoice-permissions" value="{{ json_encode(auth()->user()->getAllPermissions()->pluck('name')->all()) }}">
<div class="modal fade next-aux-modal" id="modalItems" tabindex="-1" role="dialog" aria-labelledby="entryItemsTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="entryItemsTitle">Detalles de entrada</h5><button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>
        <div class="modal-body"><div id="entry-items-status" role="status"></div><div id="entry-items-content"></div></div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cerrar</button></div>
    </div></div>
</div>
