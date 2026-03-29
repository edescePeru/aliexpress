$(document).ready(function () {
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
});

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

        let stockActual = item.inventory_level ? (item.inventory_level.qty_on_hand ?? 0) : 0;
        let stockReservado = item.inventory_level ? (item.inventory_level.qty_reserved ?? 0) : 0;
        let stockMin = item.inventory_level ? (item.inventory_level.min_alert ?? 0) : 0;
        let stockMax = item.inventory_level ? (item.inventory_level.max_alert ?? 0) : 0;
        let unitMeasure = item.unit_measure ? (item.unit_measure.name || '') : '';

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
                    <td>${stockMin}</td>
                    <td>${stockMax}</td>
                    <td>
                        <button class="btn btn-sm btn-warning btn-toggle-inventory"
                            data-id="${item.id}"
                            data-value="${item.tracks_inventory}">
                            Inv
                        </button>
                    
                        <button class="btn btn-sm btn-secondary btn-toggle-active"
                            data-id="${item.id}"
                            data-variant="${item.variant_id || ''}"
                            data-value="${item.is_active}">
                            Act
                        </button>
                    </td>
                </tr>
            `;
    });

    $('#tbody-stock-items').html(html);
}

function renderStockItemsPagination(response) {
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