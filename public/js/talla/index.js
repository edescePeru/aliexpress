var tallaCurrentPage = 1;
var tallaSearchTimeout = null;
var tallaRoutes = {};
var canCreateTalla = false;
var canUpdateTalla = false;
var canDeleteTalla = false;
var tallaDeleteTrigger = null;

$(function () {
    var $app = $('#talla-app');

    tallaRoutes = {
        data: $app.data('url-data'),
        create: $app.data('url-create'),
        edit: $app.data('url-edit'),
        delete: $app.data('url-delete'),
        deleteMultiple: $app.data('url-delete-multiple')
    };

    canCreateTalla = parseInt($app.data('can-create'), 10) === 1;
    canUpdateTalla = parseInt($app.data('can-update'), 10) === 1;
    canDeleteTalla = parseInt($app.data('can-delete'), 10) === 1;

    loadTallas();

    $('#tallaSearch').on('input', function () {
        clearTimeout(tallaSearchTimeout);
        tallaSearchTimeout = setTimeout(function () {
            tallaCurrentPage = 1;
            loadTallas();
        }, 350);
    });

    $('#tallaPerPage').on('change', function () {
        tallaCurrentPage = 1;
        loadTallas();
    });

    $(document).on('click', '[data-talla-page]', function () {
        if ($(this).prop('disabled')) {
            return;
        }

        var page = parseInt($(this).data('talla-page'), 10);

        if (!page || page === tallaCurrentPage) {
            return;
        }

        tallaCurrentPage = page;
        loadTallas();
    });

    $(document).on('click', '[data-delete-talla]', openDeleteTallaModal);

    $('#formDelete').on('submit', function (event) {
        event.preventDefault();
        sendDeleteTalla(new FormData(this));
    });

    $('#modalDelete').on('shown.bs.modal', function () {
        $(this).find('[data-delete-cancel]').trigger('focus');
    });

    $('#modalDelete').on('hidden.bs.modal', function () {
        if (tallaDeleteTrigger) {
            $(tallaDeleteTrigger).trigger('focus');
        }
        tallaDeleteTrigger = null;
    });

    $(document).on('change', '.talla-checkbox', function () {
        updateBulkSelection();
        updateCheckAllTallasState();
    });

    $('#checkAllTallas').on('change', function () {
        $('.talla-checkbox').prop('checked', $(this).is(':checked'));
        updateBulkSelection();
    });

    $('#btnDeleteSelectedTallas').on('click', function () {
        if ($(this).prop('disabled') || $(this).attr('data-backend-sync-open') === 'true') {
            return;
        }
    });
});

function loadTallas() {
    var colspan = getTallaColumnCount();

    $('#tallaEmpty').addClass('d-none').empty();
    $('#tallaTableBody').html(
        '<tr><td colspan="' + colspan + '" class="text-center py-4">' +
        '<i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i>Cargando tallas...' +
        '</td></tr>'
    );

    $.ajax({
        url: tallaRoutes.data,
        type: 'GET',
        dataType: 'json',
        data: {
            page: tallaCurrentPage,
            search: $('#tallaSearch').val(),
            per_page: $('#tallaPerPage').val()
        },
        success: renderTallas,
        error: function () {
            $('#tallaTableBody').html(
                '<tr><td colspan="' + colspan + '" class="text-center text-danger py-4">' +
                'No se pudieron cargar las tallas.' +
                '</td></tr>'
            );
            $('#tallaResultSummary').empty();
            $('#tallaPaginationInfo').empty();
            $('#tallaPagination').empty();
        }
    });
}

function renderTallas(response) {
    var tallas = response.data || [];
    var total = parseInt(response.total, 10) || 0;
    var noun = total === 1 ? 'talla' : 'tallas';
    var resultLabel = total === 1 ? 'encontrada' : 'encontradas';

    $('#tallaResultSummary').html(
        '<div class="next-list-summary-copy">' +
        '<strong class="next-list-count">' + total + '</strong>' +
        '<span>' + noun + ' ' + resultLabel + '</span>' +
        '</div>' +
        '<span class="next-list-sort-context">' +
        '<i class="fas fa-sort-alpha-down" aria-hidden="true"></i> Orden alfabético' +
        '</span>'
    );

    if (!tallas.length) {
        var hasSearch = $.trim($('#tallaSearch').val()) !== '';
        var emptyMessage = hasSearch
            ? 'No se encontraron tallas con la búsqueda actual.'
            : 'No hay tallas registradas.';

        if (!hasSearch && canCreateTalla) {
            emptyMessage += ' <a href="' + getCreateTallaUrl() + '">Crear la primera talla</a>.';
        }

        $('#tallaTableBody').empty();
        $('#tallaEmpty').removeClass('d-none').html(emptyMessage);
        $('#tallaPaginationInfo').text('Mostrando 0 tallas.');
        $('#tallaPagination').empty();
        resetTallaSelection();
        return;
    }

    $('#tallaEmpty').addClass('d-none').empty();

    var html = '';

    $.each(tallas, function (index, talla) {
        var escapedId = escapeTallaHtml(talla.id);
        var escapedName = escapeTallaHtml(talla.name || '-');
        var checkboxId = 'talla-select-' + escapedId;
        var editUrl = tallaRoutes.edit.replace(':id', encodeURIComponent(talla.id));

        html += '<tr>';

        if (canDeleteTalla) {
            html +=
                '<td class="text-center" data-selection>' +
                '<div class="custom-control custom-checkbox d-inline-block">' +
                '<input type="checkbox" class="custom-control-input talla-checkbox" id="' + checkboxId +
                '" value="' + escapedId + '" aria-label="Seleccionar ' + escapedName + '">' +
                '<label class="custom-control-label" for="' + checkboxId + '">' +
                '<span class="sr-only">Seleccionar ' + escapedName + '</span>' +
                '</label></div></td>';
        }

        html += '<td class="text-left"><strong>' + escapedName + '</strong></td>';
        html += '<td class="text-center">' + (
            talla.short_name
                ? '<span class="badge badge-info">' + escapeTallaHtml(talla.short_name) + '</span>'
                : '<span aria-label="Sin nombre corto">—</span>'
        ) + '</td>';
        html += '<td class="text-left">' + escapeTallaHtml(talla.description || '—') + '</td>';

        if (canUpdateTalla || canDeleteTalla) {
            html += '<td class="text-center" data-buttons>' +
                renderTallaRowActions(talla, editUrl) +
                '</td>';
        }

        html += '</tr>';
    });

    $('#tallaTableBody').html(html);
    resetTallaSelection();
    renderTallaPagination(response);
}

function renderTallaRowActions(talla, editUrl) {
    var escapedName = escapeTallaHtml(talla.name || 'talla');
    var actions = '';

    if (canUpdateTalla) {
        actions +=
            '<a class="dropdown-item" href="' + editUrl + '">' +
            '<i class="fas fa-pen next-row-action-item-icon" aria-hidden="true"></i>' +
            '<span>Editar</span></a>';
    }

    if (canUpdateTalla && canDeleteTalla) {
        actions += '<div class="dropdown-divider"></div>';
    }

    if (canDeleteTalla) {
        actions +=
            '<button type="button" class="dropdown-item next-row-action-danger" ' +
            'data-delete-talla="' + escapeTallaHtml(talla.id) + '" data-name="' + escapedName + '">' +
            '<i class="fas fa-trash next-row-action-item-icon" aria-hidden="true"></i>' +
            '<span>Eliminar</span></button>';
    }

    return '<div class="dropdown next-row-actions">' +
        '<button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle next-row-actions-trigger" ' +
        'data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" ' +
        'aria-label="Abrir acciones de ' + escapedName + '">' +
        '<i class="fas fa-ellipsis-h" aria-hidden="true"></i>' +
        '</button>' +
        '<div class="dropdown-menu dropdown-menu-right next-row-actions-menu">' +
        actions +
        '</div></div>';
}

function openDeleteTallaModal(event) {
    event.preventDefault();

    var dropdownOwner = $(this).closest('.next-row-actions-menu').data('nextRowActionsOwner');
    tallaDeleteTrigger = dropdownOwner
        ? $(dropdownOwner).children('.next-row-actions-trigger').first()[0]
        : this;

    $('#talla_id').val($(this).data('delete-talla'));
    $('#tallaDeleteName').text($(this).data('name'));
    $('#modalDelete').modal('show');
}

function sendDeleteTalla(formData) {
    $.ajax({
        url: tallaRoutes.delete,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            toastr.success(response.message, 'Éxito', {
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-top-right',
                timeOut: '2000'
            });
            $('#modalDelete').modal('hide');
            loadTallas();
        },
        error: function (xhr) {
            showTallaIndexError(xhr, 'No se pudo eliminar la talla.');
        }
    });
}

function renderTallaPagination(response) {
    var total = parseInt(response.total, 10) || 0;
    var from = parseInt(response.from, 10) || 0;
    var to = parseInt(response.to, 10) || 0;
    var currentPage = parseInt(response.current_page, 10) || 1;
    var lastPage = parseInt(response.last_page, 10) || 1;
    var noun = total === 1 ? 'talla' : 'tallas';

    $('#tallaPaginationInfo').text(
        'Mostrando ' + from + ' a ' + to + ' de ' + total + ' ' + noun + '.'
    );

    if (lastPage <= 1) {
        $('#tallaPagination').empty();
        return;
    }

    var start = Math.max(1, currentPage - 2);
    var end = Math.min(lastPage, currentPage + 2);
    var html = '<ul class="pagination pagination-sm mb-0">';

    html += renderTallaPageButton('Anterior', currentPage - 1, currentPage === 1, false);

    for (var page = start; page <= end; page++) {
        html += renderTallaPageButton(String(page), page, false, page === currentPage);
    }

    html += renderTallaPageButton('Siguiente', currentPage + 1, currentPage === lastPage, false);
    html += '</ul>';
    $('#tallaPagination').html(html);
}

function renderTallaPageButton(label, page, disabled, active) {
    return '<li class="page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '') + '">' +
        '<button type="button" class="page-link" data-talla-page="' + page + '"' +
        (disabled ? ' disabled' : '') +
        (active ? ' aria-current="page"' : '') +
        '>' + label + '</button></li>';
}

function updateBulkSelection() {
    var $button = $('#btnDeleteSelectedTallas');

    if ($button.attr('data-backend-sync-open') === 'true') {
        $button.prop('disabled', true).attr('aria-disabled', 'true');
        return;
    }

    $button.prop('disabled', $('.talla-checkbox:checked').length === 0);
}

function updateCheckAllTallasState() {
    var total = $('.talla-checkbox').length;
    var selected = $('.talla-checkbox:checked').length;

    $('#checkAllTallas')
        .prop('checked', total > 0 && selected === total)
        .prop('indeterminate', selected > 0 && selected < total);
}

function resetTallaSelection() {
    $('#checkAllTallas').prop('checked', false).prop('indeterminate', false);
    updateBulkSelection();
}

function getTallaColumnCount() {
    var count = 3;

    if (canDeleteTalla) {
        count++;
    }

    if (canUpdateTalla || canDeleteTalla) {
        count++;
    }

    return count;
}

function getCreateTallaUrl() {
    return tallaRoutes.create;
}

function showTallaIndexError(xhr, defaultMessage) {
    var message = defaultMessage;

    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
        var errorKeys = Object.keys(xhr.responseJSON.errors);
        var first = errorKeys.length ? xhr.responseJSON.errors[errorKeys[0]] : null;

        if (first && first[0]) {
            message = first[0];
        }
    } else if (xhr.responseJSON && xhr.responseJSON.message) {
        message = xhr.responseJSON.message;
    }

    toastr.error(message, 'Aviso', {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: '3000'
    });
}

function escapeTallaHtml(value) {
    return String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
