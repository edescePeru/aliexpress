$(document).ready(function () {
    $permissions = JSON.parse($('#permissions').val());

    loadStockItems(1);

    $('#btn-search-stock-item').on('click', function () {
        loadStockItems(1);
    });

    $('#search-stock-item').on('keyup', function (e) {
        if (e.keyCode === 13) {
            loadStockItems(1);
        }
    });

    $(document).on('click', '.pagination-stock-item a', function (e) {
        e.preventDefault();

        let page = $(this).data('page');
        if (page) {
            loadStockItems(page);
        }
    });

    // Toggle Inventariable
    $(document).on('click', '.btn-toggle-inventory', function () {
        let id = $(this).data('id');
        let current = parseInt($(this).data('value'));

        updateInventoryToggle(id, current);
    });

    // Toggle Activo
    $(document).on('click', '.btn-toggle-active', function () {
        let id = $(this).data('id');
        let variantId = $(this).data('variant');
        let current = parseInt($(this).data('value'));

        updateActiveToggle(id, variantId, current);
    });

    $(document).on('click', '[data-ver_inventario]', function () {
        const stockItemId = $(this).data('id');

        if (!stockItemId) {
            toastr.error('No se encontró el stock item.');
            return;
        }

        openInventoryLevelsModal(stockItemId);
    });

    $('#formInventoryLevels').on('submit', function (e) {
        e.preventDefault();

        const stockItemId = $('#modal_stock_item_id').val();

        if (!stockItemId) {
            toastr.error('No se encontró el stock item.');
            return;
        }

        const url = window.stockItemInventoryLevelsUpdateUrl.replace(':id', stockItemId);
        const formData = $(this).serialize();

        $('#btn-save-inventory-levels').prop('disabled', true);

        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            success: function (response) {
                toastr.success(response.message || 'Cambios guardados correctamente.');
                $('#modalInventoryLevels').modal('hide');

                // Recargar listado actual si ya tienes una función de refresh
                /*if (typeof loadStockItems === 'function') {
                    loadStockItems(currentStockItemsPage || 1);
                }*/
            },
            error: function (xhr) {
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    Object.keys(xhr.responseJSON.errors).forEach(function (key) {
                        toastr.error(xhr.responseJSON.errors[key][0]);
                    });
                } else {
                    toastr.error(
                        (xhr.responseJSON && xhr.responseJSON.message)
                            ? xhr.responseJSON.message
                            : 'Ocurrió un error al guardar.'
                    );
                }
            },
            complete: function () {
                $('#btn-save-inventory-levels').prop('disabled', false);
            }
        });
    });

    $(document).on('click', '[data-ajustar_stock]', function () {
        const stockItemId = $(this).data('id');
        const displayName = $(this).data('display-name') || '';
        const stockActual = parseFloat($(this).data('stock-actual') || 0);

        if (!stockItemId) {
            toastr.error('No se encontró el stock item.');
            return;
        }

        $('#adjust_stock_item_id').val(stockItemId);
        $('#adjust_display_name').val(displayName);
        $('#adjust_stock_actual').val(stockActual);
        $('#adjust_quantity').val('');

        $('#modalAdjustStock').modal('show');
    });

    $('#formAdjustStock').on('submit', function (e) {
        e.preventDefault();

        const stockItemId = $('#adjust_stock_item_id').val();
        const quantity = parseFloat($('#adjust_quantity').val() || 0);
        const stockActual = parseFloat($('#adjust_stock_actual').val() || 0);

        if (!stockItemId) {
            toastr.error('No se encontró el stock item.');
            return;
        }

        if (quantity <= 0) {
            toastr.error('La cantidad debe ser mayor a cero.');
            return;
        }

        if (quantity > stockActual) {
            toastr.error('La cantidad no puede ser mayor al stock actual.');
            return;
        }

        $.ajax({
            url: window.APP.URLS.ADJUST_STOCK_ITEM,
            method: 'POST',
            data: {
                stock_item_id: stockItemId,
                quantity: quantity,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function () {
                $('#formAdjustStock button[type="submit"]').prop('disabled', true);
            },
            success: function (response) {
                toastr.success(response.message || 'Stock ajustado correctamente.');

                $('#modalAdjustStock').modal('hide');

                // Aquí llamas tu función para recargar la tabla
                // Ejemplo:
                loadStockItems();
            },
            error: function (xhr) {
                let message = 'No se pudo ajustar el stock.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }

                toastr.error(message);
            },
            complete: function () {
                $('#formAdjustStock button[type="submit"]').prop('disabled', false);
            }
        });
    });
});

var $permissions;

function openInventoryLevelsModal(stockItemId) {
    const url = window.stockItemInventoryLevelsUrl.replace(':id', stockItemId);

    $.ajax({
        url: url,
        method: 'GET',
        beforeSend: function () {
            $('#tbody-modal-inventory-levels').html(`
                <tr>
                    <td colspan="8" class="text-center">Cargando...</td>
                </tr>
            `);
            $('#modalInventoryLevels').modal('show');
        },
        success: function (response) {
            fillInventoryLevelsModal(response);
        },
        error: function (xhr) {
            let message = 'No se pudo cargar el inventario.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            toastr.error(message);
        }
    });
}

function can(permission) {
    return $.inArray(permission, $permissions) !== -1;
}

function fillInventoryLevelsModal(response) {
    const stockItem = response.stock_item || {};
    const levels = Array.isArray(response.inventory_levels) ? response.inventory_levels : [];

    $('#modal_stock_item_id').val(stockItem.id || '');
    $('#modal_sku').val(stockItem.sku || '');
    $('#modal_barcode').val(stockItem.barcode || '');
    $('#modal_display_name').val(stockItem.display_name || '');

    renderInventoryLevelsModalRows(levels);
}

function renderInventoryLevelsModalRows(levels) {
    const $tbody = $('#tbody-modal-inventory-levels');
    $tbody.empty();

    if (!levels.length) {
        $tbody.html(`
            <tr>
                <td colspan="8" class="text-center text-muted">
                    No hay inventory levels registrados.
                </td>
            </tr>
        `);
        return;
    }

    levels.forEach(function (level, index) {
        $tbody.append(`
            <tr>
                <td>
                    <input type="hidden" name="inventory_levels[${index}][id]" value="${escapeHtml(level.id || '')}">
                    <input type="text" class="form-control form-control-sm" value="${escapeHtml(level.warehouse_name || '')}" readonly>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" value="${escapeHtml(level.location_name || '')}" readonly>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm" value="${normalizeNumber(level.qty_on_hand)}" readonly>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm" value="${normalizeNumber(level.qty_reserved)}" readonly>
                </td>
                <td>
                    <input type="number"
                           class="form-control form-control-sm"
                           name="inventory_levels[${index}][min_alert]"
                           value="${normalizeNumber(level.min_alert)}"
                           min="0" step="0.01">
                </td>
                <td>
                    <input type="number"
                           class="form-control form-control-sm"
                           name="inventory_levels[${index}][max_alert]"
                           value="${normalizeNumber(level.max_alert)}"
                           min="0" step="0.01">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm" value="${normalizeNumber(level.average_cost)}" readonly>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm" value="${normalizeNumber(level.last_cost)}" readonly>
                </td>
            </tr>
        `);
    });
}

function normalizeNumber(value) {
    if (value === null || value === undefined || value === '') {
        return 0;
    }

    const parsed = parseFloat(value);
    return isNaN(parsed) ? 0 : parsed;
}

function updateInventoryToggle(id, current) {
    $.ajax({
        url: `/dashboard/stock-items/${id}/toggle-inventory`,
        method: 'POST',
        data: {
            value: current === 1 ? 0 : 1,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function () {
            toastr.success('Inventario actualizado');
            loadStockItems();
        },
        error: function () {
            toastr.error('Error al actualizar inventario');
        }
    });
}

function updateActiveToggle(id, variantId, current) {
    $.ajax({
        url: `/dashboard/stock-items/${id}/toggle-active`,
        method: 'POST',
        data: {
            value: current === 1 ? 0 : 1,
            variant_id: variantId,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function () {
            toastr.success('Estado actualizado');
            loadStockItems();
        },
        error: function () {
            toastr.error('Error al actualizar estado');
        }
    });
}

function loadStockItems(page = 1) {
    let search = $('#search-stock-item').val().trim();
    let stockItemsUrl = window.APP.URLS.STOCK_ITEMS;
    $.ajax({
        url: stockItemsUrl,
        method: 'GET',
        data: {
            search: search,
            page: page
        },
        success: function (response) {
            renderStockItemsTable(response.data);
            renderStockItemsPagination(response);
            renderStockItemsInfo(response);
        },
        error: function () {
            toastr.error('No se pudo cargar el listado de stock items.', 'Error');
        }
    });
}

function renderStockItemsTable(items) {
    let html = '';

    if (!items || items.length === 0) {
        html = `
                <tr>
                    <td colspan="13" class="text-center">No se encontraron registros.</td>
                </tr>
            `;
        $('#tbody-stock-items').html(html);
        return;
    }

    items.forEach(function (item) {
        let materialName = item.material ? (item.material.full_name || '') : '';
        let variantText = '';

        if (item.variant) {
            if (item.variant.attribute_summary) {
                variantText = item.variant.attribute_summary;
            } else {
                let talla = item.variant.talla ? (item.variant.talla.short_name || item.variant.talla.name || '') : '';
                let color = item.variant.color ? (item.variant.color.name || '') : '';
                variantText = [talla, color].filter(Boolean).join(' / ');
            }
        }

        let inventariable = parseInt(item.tracks_inventory) === 1
            ? '<span class="badge badge-success">Sí</span>'
            : '<span class="badge badge-danger">No</span>';

        let activo = parseInt(item.is_active) === 1
            ? '<span class="badge badge-success">Activo</span>'
            : '<span class="badge badge-secondary">Inactivo</span>';

        //let stockActual = item.inventory_level ? (item.inventory_level.qty_on_hand ?? 0) : 0;
        //let stockReservado = item.inventory_level ? (item.inventory_level.qty_reserved ?? 0) : 0;
        let stockActual = 0;
        let stockReservado = 0;

        if (Array.isArray(item.inventory_levels)) {
            item.inventory_levels.forEach(function(level) {
                stockActual += parseFloat(level.qty_on_hand || 0);
                stockReservado += parseFloat(level.qty_reserved || 0);
            });
        }

        let unitMeasure = item.unit_measure ? (item.unit_measure.name || '') : '';

        let buttons = '';

        if (can('configInventariable_material')) {
            buttons += `
                <button class="btn btn-sm btn-warning btn-toggle-inventory"
                    data-id="${item.id}"
                    data-value="${item.tracks_inventory}">
                    Config. Inv
                </button>
            `;
        }

        if (can('configActive_material')) {
            buttons += `
                <button class="btn btn-sm btn-secondary btn-toggle-active"
                    data-id="${item.id}"
                    data-variant="${item.variant_id || ''}"
                    data-value="${item.is_active}">
                    Config. Act
                </button>
            `;
        }

        if (can('verInventario_material')) {
            buttons += `
                <button class="btn btn-sm btn-outline-primary"
                    data-id="${item.id}"
                    data-variant="${item.variant_id || ''}"
                    data-ver_inventario>
                    Ver Inventario
                </button>
            `;
        }

        if (can('ajustarStock_material')) {
            buttons += `
                <button class="btn btn-sm btn-outline-success"
                    data-id="${item.id}"
                    data-variant="${item.variant_id || ''}"
                    data-display-name="${escapeHtml(item.display_name || '')}"
                    data-stock-actual="${stockActual}"
                    data-ajustar_stock>
                    Ajustar Stock
                </button>
            `;
        }

        if (can('verItems_material') && item.material.tipo_venta_id == 3) {
            buttons += `
                <a 
                    data-ver_items
                    href="${document.location.origin}/dashboard/view/stock/material/items/${item.id}"
                    class="btn btn-outline-info btn-sm"
                    data-toggle="tooltip"
                    data-placement="top"
                    title="Ver items">
                    Ver Items</i>
                </a>
            `;
        }

        if (buttons === '') {
            buttons = `<span class="text-muted">Sin acciones</span>`;
        }

        html += `
            <tr>
                <td>${escapeHtml(item.sku || '')}</td>
                <td>${escapeHtml(item.barcode || '')}</td>
                <td>${escapeHtml(item.display_name || '')}</td>
                <td>${escapeHtml(materialName)}</td>
                <td>${escapeHtml(variantText)}</td>
                <td>${escapeHtml(unitMeasure)}</td>
                <td>${inventariable}</td>
                <td>${activo}</td>
                <td>${stockActual}</td>
                <td>${stockReservado}</td>
                <td>${buttons}</td>
            </tr>
        `;
    });

    $('#tbody-stock-items').html(html);
}

function getPaginationPages(currentPage, lastPage) {
    currentPage = parseInt(currentPage);
    lastPage = parseInt(lastPage);

    if (lastPage <= 7) {
        return Array.from({ length: lastPage }, (_, i) => i + 1);
    }

    if (currentPage <= 2) {
        return [1, 2, 3, 4, '...', lastPage];
    }

    if (currentPage === 3) {
        return [1, 2, 3, 4, '...', lastPage];
    }

    if (currentPage >= lastPage - 2) {
        return [1, '...', lastPage - 3, lastPage - 2, lastPage - 1, lastPage];
    }

    return [1, '...', currentPage - 1, currentPage, currentPage + 1, '...', lastPage];
}

function renderStockItemsPagination(response) {
    let html = '';

    if (response.last_page <= 1) {
        $('#stock-items-pagination').html('');
        return;
    }

    let currentPage = parseInt(response.current_page);
    let lastPage = parseInt(response.last_page);
    let pages = getPaginationPages(currentPage, lastPage);

    html += '<ul class="pagination pagination-sm mb-0 pagination-stock-item">';

    html += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a href="#" class="page-link" data-page="${currentPage - 1}">«</a>
        </li>
    `;

    pages.forEach(function (page) {
        if (page === '...') {
            html += `
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>
            `;
        } else {
            html += `
                <li class="page-item ${currentPage === page ? 'active' : ''}">
                    <a href="#" class="page-link" data-page="${page}">${page}</a>
                </li>
            `;
        }
    });

    html += `
        <li class="page-item ${currentPage === lastPage ? 'disabled' : ''}">
            <a href="#" class="page-link" data-page="${currentPage + 1}">»</a>
        </li>
    `;

    html += '</ul>';

    $('#stock-items-pagination').html(html);
}

function renderStockItemsPaginationO(response) {
    let html = '';

    if (response.last_page <= 1) {
        $('#stock-items-pagination').html('');
        return;
    }

    html += '<ul class="pagination pagination-sm mb-0 pagination-stock-item">';

    html += `
            <li class="page-item ${response.current_page === 1 ? 'disabled' : ''}">
                <a href="#" class="page-link" data-page="${response.current_page - 1}">«</a>
            </li>
        `;

    for (let i = 1; i <= response.last_page; i++) {
        html += `
                <li class="page-item ${response.current_page === i ? 'active' : ''}">
                    <a href="#" class="page-link" data-page="${i}">${i}</a>
                </li>
            `;
    }

    html += `
            <li class="page-item ${response.current_page === response.last_page ? 'disabled' : ''}">
                <a href="#" class="page-link" data-page="${response.current_page + 1}">»</a>
            </li>
        `;

    html += '</ul>';

    $('#stock-items-pagination').html(html);
}

function renderStockItemsInfo(response) {
    let from = response.from || 0;
    let to = response.to || 0;
    let total = response.total || 0;

    $('#stock-items-info').html(`Mostrando ${from} a ${to} de ${total} registros`);
}

function escapeHtml(text) {
    return $('<div>').text(text).html();
}