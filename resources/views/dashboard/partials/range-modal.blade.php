<div class="modal fade next-aux-modal" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalId }}Label">{{ $title }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="start_date_{{ $fieldSuffix }}">Fecha de inicio</label>
                    <input type="date" class="form-control" id="start_date_{{ $fieldSuffix }}">
                </div>
                <div class="form-group mb-0">
                    <label for="end_date_{{ $fieldSuffix }}">Fecha de fin</label>
                    <input type="date" class="form-control" id="end_date_{{ $fieldSuffix }}">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-outline-secondary" id="{{ $exportId }}" data-url="{{ $exportUrl }}"><i class="fas fa-file-excel" aria-hidden="true"></i> Exportar</button>
                <button type="button" class="btn btn-primary {{ $filterClass }}" data-filter="date_range" data-dismiss="modal">Aplicar</button>
            </div>
        </div>
    </div>
</div>
