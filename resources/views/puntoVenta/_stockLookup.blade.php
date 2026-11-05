<div class="modal fade" id="posStockLookup" tabindex="-1" role="dialog" aria-labelledby="posStockLookupTitle" aria-hidden="true">
 <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title" id="posStockLookupTitle">Consultar existencias en otros almacenes</h5>
   <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>
  <div class="modal-body">
   <div id="pos-stock-search-panel"><label for="pos-stock-query">Producto, variante, SKU o código de barras</label>
    <div class="input-group mb-2"><input id="pos-stock-query" class="form-control" maxlength="200" autocomplete="off">
     <div class="input-group-append"><button type="button" id="pos-stock-search-submit" class="btn btn-primary">Buscar</button></div></div>
    <div id="pos-stock-results" class="list-group mb-2"></div><div id="pos-stock-pages" class="mb-3"></div>
   </div>
   <p id="pos-stock-product" class="font-weight-bold mb-1"></p><p id="pos-stock-local" class="text-muted"></p>
   <div id="pos-stock-message" role="status"></div><div id="pos-stock-groups"></div>
   <small id="pos-stock-checked" class="text-muted"></small>
   <p class="text-muted mt-2 mb-0">Consulta informativa. Para atender la venta con estas existencias, primero debe realizar y recibir el traslado.</p>
  </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>
 </div></div>
</div>
