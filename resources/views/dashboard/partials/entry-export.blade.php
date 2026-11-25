<div class="modal fade next-aux-modal" id="modalEntries" tabindex="-1" role="dialog" aria-labelledby="modalEntriesTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form class="modal-content" id="dashboard-entry-export" method="GET" action="{{ url('dashboard/exportar/entradas/almacen/v2/') }}">
            <div class="modal-header"><h2 class="modal-title h5" id="modalEntriesTitle">Exportar entradas de almacén</h2><button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>
            <div class="modal-body">
                <div id="dashboard-entry-export-error" class="alert alert-danger d-none" role="alert"></div>
                <div class="form-group"><label for="typeEntry">Tipo de entradas</label><select class="form-control" id="typeEntry" name="typeEntry" required>
                    @if(auth()->user()->can('list_entryPurchase') && auth()->user()->can('list_entryInventory'))<option value="1">Todas: por compra e inventario</option>@endif
                    @can('list_entryPurchase')<option value="2">Por compra</option>@endcan
                    @can('list_entryInventory')<option value="3">Inventario</option>@endcan
                </select></div>
                <div class="form-group"><label for="start">Fecha de inicio</label><input type="text" class="form-control dashboard-entry-date" id="start" name="start" placeholder="dd/mm/aaaa" autocomplete="off" aria-describedby="dashboard-entry-export-help"></div>
                <div class="form-group mb-0"><label for="end">Fecha de fin</label><input type="text" class="form-control dashboard-entry-date" id="end" name="end" placeholder="dd/mm/aaaa" autocomplete="off" aria-describedby="dashboard-entry-export-help"></div>
                <small id="dashboard-entry-export-help" class="form-text text-muted">Indica ambas fechas o deja ambas vacías para incluir todas las fechas. El reporte conserva la moneda de cada entrada; no se limita al local activo.</small>
            </div>
            <div class="modal-footer"><span id="dashboard-entry-export-status" class="text-muted" role="status" aria-live="polite"></span><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary" id="btn-submitExport"><i class="fas fa-file-excel mr-1" aria-hidden="true"></i>Descargar</button></div>
        </form>
    </div>
</div>
