<div class="{{ $warehouseSurfaceClass ?? 'container-fluid' }}">
    <div class="card next-operational-list js-managed-warehouses"
         data-url="{{ $branchWarehouseContext
             ? route('managedWarehouse.branch', [$warehouseCompanyId, $warehouseBranchId])
             : route('managedWarehouse.company', [$warehouseCompanyId]) }}">
        <div class="card-header"><div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>{{ $branchWarehouseContext ? 'Almacenes del local' : 'Almacenes generales' }}</strong><span>Configuración de almacenes de {{ $branchWarehouseContext ? 'esta sucursal' : 'esta empresa' }}.</span></div></div></div>
        <div class="card-body p-0">
            <section class="next-form-section">
            <div class="alert alert-warning js-warehouse-warning" role="alert" hidden></div>
            <p class="text-muted mb-0">Cada almacén utiliza una ubicación operativa. Los cambios se guardan de forma independiente.</p>
            </section>
            <div class="next-list-toolbar"><div class="next-toolbar-actions flex-wrap justify-content-between w-100"><span class="js-warehouse-status" role="status">Cargando almacenes…</span>
            <button type="button" class="btn btn-outline-secondary btn-sm js-warehouse-reload">Actualizar lista</button></div></div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0">
                    <thead><tr><th class="text-left">Nombre</th><th class="text-left">Observación</th><th class="text-center">Configuración</th><th class="text-center">Acciones</th></tr></thead>
                    <tbody class="js-warehouse-rows"></tbody>
                </table>
            </div>
            <form class="js-warehouse-form">
                @csrf
                <input type="hidden" name="warehouse_id">
                <section class="next-form-section"><div class="row">
                <div class="form-group col-md-6">
                    <label for="managed-warehouse-name">Nombre del almacén</label>
                    <input id="managed-warehouse-name" class="form-control" name="name" required maxlength="255">
                </div>
                <div class="form-group col-md-6">
                    <label for="managed-warehouse-comment">Observación</label>
                    <input id="managed-warehouse-comment" class="form-control" name="comment" maxlength="255">
                </div>
                </div></section>
                <div class="next-list-toolbar"><div class="next-toolbar-actions flex-wrap">
                <button type="submit" class="btn btn-primary" disabled>Crear almacén</button>
                <button type="button" class="btn btn-outline-secondary js-warehouse-cancel">Cancelar edición</button></div></div>
            </form>
            @if($branchWarehouseContext)
                <form class="js-warehouse-preference">
                    @csrf
                    <section class="next-form-section"><div class="form-group mb-0">
                        <label for="managed-warehouse-preference">Destino habitual para ingresos de este local</label>
                        <select id="managed-warehouse-preference" name="warehouse_id" class="form-control" disabled></select>
                        <small class="text-muted">Sin preferencia se utilizará el general predeterminado. La selección se aplicará al conectar el formulario de ingresos.</small>
                    </div>
                    </section><div class="next-list-toolbar"><div class="next-toolbar-actions flex-wrap"><button type="submit" class="btn btn-primary" disabled>Guardar preferencia</button></div></div>
                </form>
            @endif
        </div>
    </div>
</div>
