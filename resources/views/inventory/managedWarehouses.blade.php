<div class="container-fluid">
    <div class="card js-managed-warehouses"
         data-url="{{ $branchWarehouseContext
             ? route('managedWarehouse.branch', [$warehouseCompanyId, $warehouseBranchId])
             : route('managedWarehouse.company', [$warehouseCompanyId]) }}">
        <div class="card-header">{{ $branchWarehouseContext ? 'Almacenes del local' : 'Almacenes generales' }}</div>
        <div class="card-body">
            <div class="alert alert-warning js-warehouse-warning" role="alert" hidden></div>
            <p class="text-muted">Cada almacén utiliza una ubicación operativa. Los cambios se guardan de forma independiente.</p>
            <p class="js-warehouse-status" role="status">Cargando almacenes…</p>
            <button type="button" class="btn btn-outline-secondary btn-sm js-warehouse-reload">Actualizar lista</button>
            <div class="table-responsive mt-3">
                <table class="table table-bordered">
                    <thead><tr><th>Nombre</th><th>Observación</th><th>Configuración</th><th>Acciones</th></tr></thead>
                    <tbody class="js-warehouse-rows"></tbody>
                </table>
            </div>
            <form class="js-warehouse-form mt-3">
                @csrf
                <input type="hidden" name="warehouse_id">
                <div class="form-group">
                    <label for="managed-warehouse-name">Nombre del almacén</label>
                    <input id="managed-warehouse-name" class="form-control" name="name" required maxlength="255">
                </div>
                <div class="form-group">
                    <label for="managed-warehouse-comment">Observación</label>
                    <input id="managed-warehouse-comment" class="form-control" name="comment" maxlength="255">
                </div>
                <button type="submit" class="btn btn-primary" disabled>Crear almacén</button>
                <button type="button" class="btn btn-secondary js-warehouse-cancel">Cancelar edición</button>
            </form>
            @if($branchWarehouseContext)
                <form class="js-warehouse-preference mt-4">
                    @csrf
                    <div class="form-group">
                        <label for="managed-warehouse-preference">Destino habitual para ingresos de este local</label>
                        <select id="managed-warehouse-preference" name="warehouse_id" class="form-control" disabled></select>
                        <small class="text-muted">Sin preferencia se utilizará el general predeterminado. La selección se aplicará al conectar el formulario de ingresos.</small>
                    </div>
                    <button type="submit" class="btn btn-primary" disabled>Guardar preferencia</button>
                </form>
            @endif
        </div>
    </div>
</div>
