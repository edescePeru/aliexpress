var colorCurrentPage = 1;
var colorSearchTimeout = null;
var colorRoutes = {};
var canCreateColor = false;
var canUpdateColor = false;
var canDeleteColor = false;
var colorDeleteTrigger = null;

$(function () {
    var $app = $('#color-app');

    colorRoutes = {
        data: $app.data('url-data'),
        create: $app.data('url-create'),
        edit: $app.data('url-edit'),
        delete: $app.data('url-delete'),
        deleteMultiple: $app.data('url-delete-multiple')
    };

    canCreateColor = parseInt($app.data('can-create'), 10) === 1;
    canUpdateColor = parseInt($app.data('can-update'), 10) === 1;
    canDeleteColor = parseInt($app.data('can-delete'), 10) === 1;

    loadColors();

    $('#colorSearch').on('input', function () {
        clearTimeout(colorSearchTimeout);
        colorSearchTimeout = setTimeout(function () {
            colorCurrentPage = 1;
            loadColors();
        }, 350);
    });

    $('#colorPerPage').on('change', function () {
        colorCurrentPage = 1;
        loadColors();
    });

    $(document).on('click', '[data-color-page]', function () {
        if ($(this).prop('disabled')) {
            return;
        }

        var page = parseInt($(this).data('color-page'), 10);

        if (!page || page === colorCurrentPage) {
            return;
        }

        colorCurrentPage = page;
        loadColors();
    });

    $(document).on('click', '[data-delete-color]', openDeleteColorModal);

    $('#formDelete').on('submit', function (event) {
        event.preventDefault();
        sendDeleteColor(new FormData(this));
    });

    $('#modalDelete').on('shown.bs.modal', function () {
        $(this).find('[data-delete-cancel]').trigger('focus');
    });

    $('#modalDelete').on('hidden.bs.modal', function () {
        if (colorDeleteTrigger) {
            $(colorDeleteTrigger).trigger('focus');
        }
        colorDeleteTrigger = null;
    });

    $(document).on('change', '.color-checkbox', function () {
        updateColorBulkSelection();
        updateCheckAllColorsState();
    });

    $('#checkAllColors').on('change', function () {
        $('.color-checkbox').prop('checked', $(this).is(':checked'));
        updateColorBulkSelection();
    });

    $('#btnDeleteSelectedColors').on('click', function () {
        if ($(this).prop('disabled') || $(this).attr('data-backend-sync-open') === 'true') {
            return;
        }
    });
});

function loadColors() {
    var colspan = getColorColumnCount();

    $('#colorEmpty').addClass('d-none').empty();
    $('#colorTableBody').html(
        '<tr><td colspan="' + colspan + '" class="text-center py-4">' +
        '<i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i>Cargando colores...' +
        '</td></tr>'
    );

    $.ajax({
        url: colorRoutes.data,
        type: 'GET',
        dataType: 'json',
        data: {
            page: colorCurrentPage,
            search: $('#colorSearch').val(),
            per_page: $('#colorPerPage').val()
        },
        success: renderColors,
        error: function () {
            $('#colorTableBody').html(
                '<tr><td colspan="' + colspan + '" class="text-center text-danger py-4">' +
                'No se pudieron cargar los colores.' +
                '</td></tr>'
            );
            $('#colorResultSummary').empty();
            $('#colorPaginationInfo').empty();
            $('#colorPagination').empty();
        }
    });
}

function renderColors(response) {
    var colors = response.data || [];
    var total = parseInt(response.total, 10) || 0;
    var noun = total === 1 ? 'color' : 'colores';
    var resultLabel = total === 1 ? 'encontrado' : 'encontrados';

    $('#colorResultSummary').html(
        '<div class="next-list-summary-copy">' +
        '<strong class="next-list-count">' + total + '</strong>' +
        '<span>' + noun + ' ' + resultLabel + '</span>' +
        '</div>' +
        '<span class="next-list-sort-context">' +
        '<i class="fas fa-sort-alpha-down" aria-hidden="true"></i> Orden alfabético' +
        '</span>'
    );

    if (!colors.length) {
        var hasSearch = $.trim($('#colorSearch').val()) !== '';
        var emptyMessage = hasSearch
            ? 'No se encontraron colores con la búsqueda actual.'
            : 'No hay colores registrados.';

        if (!hasSearch && canCreateColor) {
            emptyMessage += ' <a href="' + colorRoutes.create + '">Crear el primer color</a>.';
        }

        $('#colorTableBody').empty();
        $('#colorEmpty').removeClass('d-none').html(emptyMessage);
        $('#colorPaginationInfo').text('Mostrando 0 colores.');
        $('#colorPagination').empty();
        resetColorSelection();
        return;
    }

    $('#colorEmpty').addClass('d-none').empty();

    var html = '';

    $.each(colors, function (index, color) {
        var escapedId = escapeColorHtml(color.id);
        var escapedName = escapeColorHtml(color.name || '-');
        var checkboxId = 'color-select-' + escapedId;
        var editUrl = colorRoutes.edit.replace(':id', encodeURIComponent(color.id));

        html += '<tr>';

        if (canDeleteColor) {
            html +=
                '<td class="text-center" data-selection>' +
                '<div class="custom-control custom-checkbox d-inline-block">' +
                '<input type="checkbox" class="custom-control-input color-checkbox" id="' + checkboxId +
                '" value="' + escapedId + '" aria-label="Seleccionar ' + escapedName + '">' +
                '<label class="custom-control-label" for="' + checkboxId + '">' +
                '<span class="sr-only">Seleccionar ' + escapedName + '</span>' +
                '</label></div></td>';
        }

        html += '<td class="text-center">' + renderColorSwatch(color.code, escapedName) + '</td>';
        html += '<td class="text-left"><strong>' + escapedName + '</strong></td>';
        html += '<td class="text-center">' + (
            isValidHexColor(color.code)
                ? '<code>' + escapeColorHtml(String(color.code).toUpperCase()) + '</code>'
                : '<span aria-label="Sin código">—</span>'
        ) + '</td>';
        html += '<td class="text-center">' + (
            color.short_name
                ? '<span class="badge badge-info">' + escapeColorHtml(color.short_name) + '</span>'
                : '<span aria-label="Sin nombre corto">—</span>'
        ) + '</td>';

        if (canUpdateColor || canDeleteColor) {
            html += '<td class="text-center" data-buttons>' + renderColorRowActions(color, editUrl) + '</td>';
        }

        html += '</tr>';
    });

    $('#colorTableBody').html(html);
    resetColorSelection();
    renderColorPagination(response);
}

function renderColorSwatch(code, escapedName) {
    if (!isValidHexColor(code)) {
        return '<span class="badge badge-light border">Sin código</span>';
    }

    var escapedCode = escapeColorHtml(String(code).toUpperCase());

    return '<input type="color" class="form-control form-control-sm p-0 mx-auto size-32" ' +
        'value="' + escapedCode + '" disabled ' +
        'aria-label="Muestra de ' + escapedName + ': ' + escapedCode + '" title="' + escapedCode + '">';
}

function renderColorRowActions(color, editUrl) {
    var escapedName = escapeColorHtml(color.name || 'color');
    var actions = '';

    if (canUpdateColor) {
        actions += '<a class="dropdown-item" href="' + editUrl + '">' +
            '<i class="fas fa-pen next-row-action-item-icon" aria-hidden="true"></i>' +
            '<span>Editar</span></a>';
    }

    if (canUpdateColor && canDeleteColor) {
        actions += '<div class="dropdown-divider"></div>';
    }

    if (canDeleteColor) {
        actions += '<button type="button" class="dropdown-item next-row-action-danger" ' +
            'data-delete-color="' + escapeColorHtml(color.id) + '" data-name="' + escapedName + '">' +
            '<i class="fas fa-trash next-row-action-item-icon" aria-hidden="true"></i>' +
            '<span>Eliminar</span></button>';
    }

    return '<div class="dropdown next-row-actions">' +
        '<button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle next-row-actions-trigger" ' +
        'data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" ' +
        'aria-label="Abrir acciones de ' + escapedName + '">' +
        '<i class="fas fa-ellipsis-h" aria-hidden="true"></i>' +
        '</button>' +
        '<div class="dropdown-menu dropdown-menu-right next-row-actions-menu">' + actions + '</div></div>';
}

function openDeleteColorModal(event) {
    event.preventDefault();

    var dropdownOwner = $(this).closest('.next-row-actions-menu').data('nextRowActionsOwner');
    colorDeleteTrigger = dropdownOwner
        ? $(dropdownOwner).children('.next-row-actions-trigger').first()[0]
        : this;

    $('#color_id').val($(this).data('delete-color'));
    $('#colorDeleteName').text($(this).data('name'));
    $('#modalDelete').modal('show');
}

function sendDeleteColor(formData) {
    $.ajax({
        url: colorRoutes.delete,
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
            loadColors();
        },
        error: function (xhr) {
            showColorIndexError(xhr, 'No se pudo eliminar el color.');
        }
    });
}

function renderColorPagination(response) {
    var total = parseInt(response.total, 10) || 0;
    var from = parseInt(response.from, 10) || 0;
    var to = parseInt(response.to, 10) || 0;
    var currentPage = parseInt(response.current_page, 10) || 1;
    var lastPage = parseInt(response.last_page, 10) || 1;
    var noun = total === 1 ? 'color' : 'colores';

    $('#colorPaginationInfo').text('Mostrando ' + from + ' a ' + to + ' de ' + total + ' ' + noun + '.');

    if (lastPage <= 1) {
        $('#colorPagination').empty();
        return;
    }

    var start = Math.max(1, currentPage - 2);
    var end = Math.min(lastPage, currentPage + 2);
    var html = '<ul class="pagination pagination-sm mb-0">';

    html += renderColorPageButton('Anterior', currentPage - 1, currentPage === 1, false);

    for (var page = start; page <= end; page++) {
        html += renderColorPageButton(String(page), page, false, page === currentPage);
    }

    html += renderColorPageButton('Siguiente', currentPage + 1, currentPage === lastPage, false);
    html += '</ul>';
    $('#colorPagination').html(html);
}

function renderColorPageButton(label, page, disabled, active) {
    return '<li class="page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '') + '">' +
        '<button type="button" class="page-link" data-color-page="' + page + '"' +
        (disabled ? ' disabled' : '') + (active ? ' aria-current="page"' : '') +
        '>' + label + '</button></li>';
}

function updateColorBulkSelection() {
    var $button = $('#btnDeleteSelectedColors');

    if ($button.attr('data-backend-sync-open') === 'true') {
        $button.prop('disabled', true).attr('aria-disabled', 'true');
        return;
    }

    $button.prop('disabled', $('.color-checkbox:checked').length === 0);
}

function updateCheckAllColorsState() {
    var total = $('.color-checkbox').length;
    var selected = $('.color-checkbox:checked').length;

    $('#checkAllColors')
        .prop('checked', total > 0 && selected === total)
        .prop('indeterminate', selected > 0 && selected < total);
}

function resetColorSelection() {
    $('#checkAllColors').prop('checked', false).prop('indeterminate', false);
    updateColorBulkSelection();
}

function getColorColumnCount() {
    var count = 4;

    if (canDeleteColor) {
        count++;
    }

    if (canUpdateColor || canDeleteColor) {
        count++;
    }

    return count;
}

function isValidHexColor(value) {
    return /^#[0-9A-Fa-f]{6}$/.test(String(value || ''));
}

function showColorIndexError(xhr, defaultMessage) {
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

function escapeColorHtml(value) {
    return String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
