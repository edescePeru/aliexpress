var $formDelete;
var $modalDelete;
var deleteTrigger = null;

$(document).ready(function () {
    var $table = $('#dynamic-table');
    var canCreate = $table.attr('data-can-create') === 'true';
    var canUpdate = $table.attr('data-can-update') === 'true';
    var canDestroy = $table.attr('data-can-destroy') === 'true';
    var columns = [];

    if (canDestroy) {
        columns.push({
            data: null,
            orderable: false,
            searchable: false,
            className: 'text-center',
            render: function (data, type, row) {
                var checkboxId = 'typescrap-select-' + escapeHtml(row.id);
                var accessibleName = 'Seleccionar ' + escapeHtml(row.name);

                return '<div class="custom-control custom-checkbox d-inline-block">' +
                    '<input type="checkbox" class="custom-control-input row-checkbox" id="' +
                    checkboxId + '" value="' + escapeHtml(row.id) + '" aria-label="' +
                    accessibleName + '">' +
                    '<label class="custom-control-label" for="' + checkboxId + '">' +
                    '<span class="sr-only">' + accessibleName + '</span></label></div>';
            }
        });
    }

    columns.push({
        data: 'name',
        className: 'text-left'
    });

    columns.push({
        data: 'length',
        className: 'text-center'
    });

    columns.push({
        data: 'width',
        className: 'text-center'
    });

    if (canUpdate || canDestroy) {
        columns.push({
            data: null,
            orderable: false,
            searchable: false,
            className: 'text-center',
            createdCell: function (cell) {
                $(cell).attr('data-buttons', '');
            },
            render: function (item) {
                return renderRowActions(item, canUpdate, canDestroy, $table.data('url-edit'));
            }
        });
    }

    var emptyState = '<div class="next-table-empty">No hay tipos de retacería registrados.';

    if (canCreate) {
        emptyState += ' <a href="' + $table.data('url-create') + '">Crear el primer tipo</a>.';
    }

    emptyState += '</div>';

    var dataTable = $table.DataTable({
        ajax: {
            url: $table.data('url-data'),
            dataSrc: 'data'
        },
        bAutoWidth: false,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        aoColumns: columns,
        aaSorting: [],
        dom:
            '<"next-list-toolbar"<"next-list-search-row"<"next-list-search-control"f><"next-list-length"l>>>' +
            '<"next-list-summary"i>' +
            '<"next-list-content"<"table-responsive"t>>' +
            '<"next-list-pagination"p>',
        language: {
            processing: 'Procesando...',
            lengthMenu: 'Mostrar _MENU_ registros',
            zeroRecords: '<div class="next-table-empty">No se encontraron tipos con la búsqueda actual.</div>',
            emptyTable: emptyState,
            info: 'Mostrando _START_–_END_ de _TOTAL_ tipos',
            infoEmpty: 'Mostrando 0 tipos',
            infoFiltered: '(filtrado de _MAX_ registros)',
            search: 'Buscar:',
            loadingRecords: 'Cargando tipos de retacería...',
            paginate: {
                first: 'Primero',
                last: 'Último',
                next: 'Siguiente',
                previous: 'Anterior'
            },
            aria: {
                sortAscending: ': activar para ordenar ascendente',
                sortDescending: ': activar para ordenar descendente'
            }
        },
        initComplete: function () {
            var $wrapper = $table.closest('.dataTables_wrapper');
            var $filter = $wrapper.find('.dataTables_filter');
            var $filterLabel = $filter.find('label');
            var $filterInput = $filter.find('input');
            var $lengthLabel = $wrapper.find('.dataTables_length label');
            var $info = $wrapper.find('.next-list-summary .dataTables_info');

            $filter.removeClass('text-right').addClass('text-left mb-0');
            $filterLabel.addClass('w-100 mb-0');
            $filterLabel.contents().filter(function () {
                return this.nodeType === 3;
            }).remove();
            $filterLabel.prepend('<span class="sr-only">Buscar tipos de retacería</span>');
            $filterInput
                .removeClass('ml-2')
                .addClass('w-100 ml-0')
                .attr('placeholder', 'Buscar por nombre o dimensión')
                .attr('aria-label', 'Buscar por nombre o dimensión');
            $lengthLabel.addClass('d-flex align-items-center mb-0');
            $info.addClass('d-flex align-items-center justify-content-between flex-wrap w-100');
            $wrapper.find('.next-list-pagination').prepend(
                '<div class="next-list-page-context" aria-live="polite"></div>'
            );

            updateDataTableContext(this.api(), $wrapper);
        },
        drawCallback: function () {
            $('#select-all').prop('checked', false);
            updateDataTableContext(this.api(), $table.closest('.dataTables_wrapper'));
        }
    });

    $formDelete = $('#formDelete');
    $modalDelete = $('#modalDelete');

    $formDelete.on('submit', destroyTypescrap);
    $(document).on('click', '[data-delete]', openModalDelete);

    $modalDelete.on('shown.bs.modal', function () {
        $modalDelete.find('[data-delete-cancel]').trigger('focus');
    });

    $modalDelete.on('hidden.bs.modal', function () {
        if (deleteTrigger) {
            $(deleteTrigger).trigger('focus');
        }
        deleteTrigger = null;
    });

    $('#select-all').on('click', function () {
        var rows = dataTable.rows({ search: 'applied' }).nodes();
        $('input[type="checkbox"].row-checkbox', rows).prop('checked', this.checked);
    });

    $('#delete-selected').on('click', function () {
        var $bulkButton = $(this);

        if ($bulkButton.prop('disabled') || $bulkButton.attr('data-backend-sync-open') === 'true') {
            return;
        }
    });
});

function updateDataTableContext(dataTable, $wrapper) {
    var page = dataTable.page.info();
    var total = page.recordsDisplay;
    var start = total === 0 ? 0 : page.start + 1;
    var end = page.end;
    var noun = total === 1 ? 'tipo' : 'tipos';
    var summary = '<div class="next-list-summary-copy">' +
        '<strong class="next-list-count">' + total + '</strong>' +
        '<span>' + noun + ' ' + (total === 1 ? 'encontrado' : 'encontrados') + '</span>' +
        '</div>' +
        '<span class="next-list-sort-context">' +
        '<i class="fas fa-sort-alpha-down" aria-hidden="true"></i> Orden alfabético' +
        '</span>';

    $wrapper.find('.next-list-summary .dataTables_info').html(summary);
    $wrapper.find('.next-list-page-context').text(
        'Mostrando ' + start + ' a ' + end + ' de ' + total + ' ' + noun + '.'
    );
}

function renderRowActions(item, canUpdate, canDestroy, editUrl) {
    var actions = '';
    var escapedName = escapeHtml(item.name);

    if (canUpdate) {
        actions += '<a class="dropdown-item" href="' + editUrl + '/' + encodeURIComponent(item.id) + '">' +
            '<i class="fas fa-pen next-row-action-item-icon" aria-hidden="true"></i>' +
            '<span>Editar</span></a>';
    }

    if (canUpdate && canDestroy) {
        actions += '<div class="dropdown-divider"></div>';
    }

    if (canDestroy) {
        actions += '<button type="button" class="dropdown-item next-row-action-danger" data-delete="' +
            escapeHtml(item.id) + '" data-name="' + escapedName + '">' +
            '<i class="fas fa-trash next-row-action-item-icon" aria-hidden="true"></i>' +
            '<span>Eliminar</span></button>';
    }

    return '<div class="dropdown next-row-actions">' +
        '<button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle next-row-actions-trigger" ' +
        'data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" ' +
        'aria-label="Abrir acciones de ' + escapedName + '">' +
        '<i class="fas fa-ellipsis-h" aria-hidden="true"></i></button>' +
        '<div class="dropdown-menu dropdown-menu-right next-row-actions-menu">' + actions + '</div></div>';
}

function openModalDelete(event) {
    event.preventDefault();

    var dropdownOwner = $(this).closest('.next-row-actions-menu').data('nextRowActionsOwner');

    deleteTrigger = dropdownOwner
        ? $(dropdownOwner).children('.next-row-actions-trigger').first()[0]
        : this;
    $modalDelete.find('#typeScrap_id').val($(this).data('delete'));
    $modalDelete.find('#name').text($(this).data('name'));
    $modalDelete.modal('show');
}

function destroyTypescrap(event) {
    event.preventDefault();

    $.ajax({
        url: $formDelete.data('url'),
        method: 'POST',
        data: new FormData(this),
        processData: false,
        contentType: false,
        success: function (data) {
            toastr.success(data.message, 'Éxito', {
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-top-right',
                timeOut: '2000'
            });
            $modalDelete.modal('hide');
            setTimeout(function () {
                location.reload();
            }, 2000);
        },
        error: function (xhr) {
            var response = xhr.responseJSON || {};

            if (response.errors) {
                $.each(response.errors, function (property, messages) {
                    toastr.error(messages, 'Error', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: 'toast-top-right',
                        timeOut: '3000'
                    });
                });
                return;
            }

            toastr.error(response.message || 'No se pudo eliminar el tipo de retacería.', 'Error', {
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-top-right',
                timeOut: '3000'
            });
        }
    });
}

function escapeHtml(value) {
    return String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
