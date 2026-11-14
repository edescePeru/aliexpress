/* Read-only StockItem screen. Business mutations are intentionally not bound here. */
(function ($) {
    'use strict';
    let permissions = [], listRequest, levelsRequest, modalTrigger, listSequence = 0, levelsSequence = 0;
    const present = value => value === null || value === undefined || value === '' ? '—' : String(value);
    const escapeHtml = value => $('<div>').text(present(value)).html().replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    const can = permission => permissions.indexOf(permission) !== -1;
    function errorMessage(xhr, fallback) {
        const data = xhr.responseJSON || {};
        const errors = data.errors && Object.values(data.errors);
        return errors && errors.length ? [].concat(errors[0])[0] : data.message || fallback;
    }
    function status(value, yes, no) {
        if (value === null || value === undefined) return '—';
        return '<span class="badge ' + (Number(value) === 1 ? 'badge-success' : 'badge-secondary') + '">' + (Number(value) === 1 ? yes : no) + '</span>';
    }
    function quantity(local, general) {
        return 'Local: ' + escapeHtml(local) + '<small class="text-muted d-block">General: ' + escapeHtml(general) + '</small>';
    }
    function loadStockItems(page) {
        $('.next-row-actions-trigger[aria-expanded="true"]').dropdown('hide');
        const sequence = ++listSequence;
        if (listRequest) listRequest.abort();
        $('#tbody-stock-items').html('<tr><td colspan="12" class="next-table-empty">Cargando…</td></tr>');
        $('#stock-items-pagination, #stock-items-info').empty();
        $('#stock-items-count').text('Cargando…');
        listRequest = $.ajax({url: window.APP.URLS.STOCK_ITEMS, method: 'GET', data: {search: $('#search-stock-item').val().trim(), page: page}})
            .done(function (response) {
                if (sequence !== listSequence) return;
                renderStockItemsTable(response.data);
                renderStockItemsPagination(response);
                $('#stock-items-count').text(response.total + ' StockItems');
                $('#stock-items-info').text('Mostrando ' + (response.from || 0) + ' a ' + (response.to || 0) + ' de ' + response.total + ' registros');
            }).fail(function (xhr, status) {
                if (status === 'abort' || sequence !== listSequence) return;
                $('#stock-items-count').text('Consulta no disponible');
                $('#tbody-stock-items').html('<tr><td colspan="12" class="next-table-empty text-danger">' + escapeHtml(errorMessage(xhr, 'No se pudo cargar el listado.')) + '</td></tr>');
            });
    }
    function renderStockItemsTable(items) {
        if (!items || !items.length) {
            $('#tbody-stock-items').html('<tr><td colspan="12" class="next-table-empty">No se encontraron StockItems en este contexto.</td></tr>');
            return;
        }
        $('#tbody-stock-items').html(items.map(function (item) {
            const material = item.material || {}, variant = item.variant || {};
            const variantText = variant.attribute_summary || [variant.talla && (variant.talla.short_name || variant.talla.name), variant.color && variant.color.name].filter(Boolean).join(' / ');
            let actions = '';
            if (can('verInventario_material')) actions += '<button type="button" class="dropdown-item" data-ver_inventario data-id="' + escapeHtml(item.id) + '"><i class="fas fa-boxes next-row-action-item-icon" aria-hidden="true"></i>Ver inventario</button>';
            if (can('verItems_material') && material.tipo_venta_id == 3) actions += '<a class="dropdown-item" data-ver_items href="' + escapeHtml(window.stockItemItemsUrl.replace(':id', encodeURIComponent(item.id))) + '"><i class="fas fa-list next-row-action-item-icon" aria-hidden="true"></i>Ver Items</a>';
            actions = actions ? '<div class="dropdown next-row-actions"><button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle next-row-actions-trigger" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false" aria-label="Acciones de ' + escapeHtml(item.sku) + '"><i class="fas fa-ellipsis-h" aria-hidden="true"></i></button><div class="dropdown-menu dropdown-menu-right next-row-actions-menu">' + actions + '</div></div>' : '<span class="text-muted">Sin acciones</span>';
            return '<tr data-stock-item-id="' + escapeHtml(item.id) + '"><td class="text-center text-nowrap">' + escapeHtml(item.sku) + '</td><td class="text-center text-nowrap">' + escapeHtml(item.barcode) + '</td><td>' + escapeHtml(item.display_name) + '</td><td>' + escapeHtml(material.full_name) + '</td><td>' + escapeHtml(variantText) + '</td><td class="text-center text-nowrap">' + escapeHtml(item.unit_measure && item.unit_measure.name) + '</td><td class="text-center text-nowrap">' + status(item.tracks_inventory, 'Sí', 'No') + '</td><td class="text-center text-nowrap">' + status(item.is_active, 'Activo', 'Inactivo') + '</td><td class="text-center text-nowrap">' + quantity(item.stock_current, item.stock_general) + '</td><td class="text-center text-nowrap">' + quantity(item.stock_reserved, item.stock_general_reserved) + '</td><td class="text-center text-nowrap">' + quantity(item.stock_available, item.stock_general_available) + '</td><td class="text-center text-nowrap">' + actions + '</td></tr>';
        }).join(''));
    }
    function openInventoryLevelsModal(id) {
        const sequence = ++levelsSequence;
        if (levelsRequest) levelsRequest.abort();
        $('#modal_sku, #modal_barcode, #modal_display_name').text('—');
        $('#tbody-modal-inventory-levels').html('<tr><td colspan="9" class="next-table-empty">Cargando…</td></tr>');
        $('#modalInventoryLevels').modal('show');
        levelsRequest = $.ajax({url: window.stockItemInventoryLevelsUrl.replace(':id', encodeURIComponent(id)), method: 'GET'})
            .done(function (response) {
                if (sequence !== levelsSequence) return;
                const item = response.stock_item || {};
                $('#modal_sku').text(present(item.sku)); $('#modal_barcode').text(present(item.barcode)); $('#modal_display_name').text(present(item.display_name));
                const rows = (response.inventory_levels || []).map(function (level) {
                    const scope = level.warehouse_branch_id === null ? 'General' : 'Local ' + present(level.warehouse_branch_id);
                    return '<tr><td>' + escapeHtml(level.warehouse_name) + '<small class="d-block text-muted">' + escapeHtml(scope) + '</small></td><td>' + escapeHtml(level.location_name) + '</td>' + ['qty_on_hand', 'qty_reserved', 'qty_available', 'min_alert', 'max_alert', 'average_cost', 'last_cost'].map(function (key) { return '<td class="' + (key.indexOf('cost') !== -1 ? 'text-right' : 'text-center') + '">' + escapeHtml(level[key]) + '</td>'; }).join('') + '</tr>';
                });
                $('#tbody-modal-inventory-levels').html(rows.join('') || '<tr><td colspan="9" class="next-table-empty">No hay niveles de inventario en este contexto.</td></tr>');
            }).fail(function (xhr, status) {
                if (status === 'abort' || sequence !== levelsSequence) return;
                $('#tbody-modal-inventory-levels').html('<tr><td colspan="9" class="next-table-empty text-danger">' + escapeHtml(errorMessage(xhr, 'No se pudo consultar inventario.')) + '</td></tr>');
            });
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


    $(function () {
        permissions = JSON.parse($('#permissions').val());
        $('#stock-items-search-form').on('submit', function (e) { e.preventDefault(); loadStockItems(1); });
        $(document).on('click', '.pagination-stock-item a', function (e) {
            e.preventDefault();
            if (!$(this).closest('.page-item').is('.disabled, .active')) loadStockItems($(this).data('page'));
        });
        $(document).on('click', '[data-ver_inventario]', function () {
            const owner = $(this).closest('.next-row-actions-menu').data('nextRowActionsOwner');
            modalTrigger = owner ? $(owner).find('.next-row-actions-trigger')[0] : $(this).closest('.next-row-actions').find('.next-row-actions-trigger')[0];
            $('.next-row-actions-trigger[aria-expanded="true"]').dropdown('hide');
            openInventoryLevelsModal($(this).data('id'));
        });
        $('#modalInventoryLevels').on('hidden.bs.modal', function () {
            ++levelsSequence;
            if (levelsRequest) levelsRequest.abort();
            if (modalTrigger && document.contains(modalTrigger)) modalTrigger.focus();
        });
        loadStockItems(1);
    });
})(window.jQuery);
