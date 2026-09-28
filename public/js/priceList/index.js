$(function () {

    const $app = $('#priceListApp');

    const urls = {
        lists:
            $app.data('url-lists'),

        store:
            $app.data('url-store'),

        updateTemplate:
            $app.data('url-update-template'),

        materialsTemplate:
            $app.data('url-materials-template'),

        saveMaterialsTemplate:
            $app.data('url-save-materials-template'),

        variantsTemplate:
            $app.data('url-variants-template'),

        saveVariantsTemplate:
            $app.data('url-save-variants-template')
    };

    const $priceList =
        $('#priceList');

    const $search =
        $('#searchMaterialPrice');

    const $body =
        $('#materialPriceBody');

    const $pagination =
        $('#materialPricePagination');

    const $btnNew =
        $('#btnNewPriceList');

    const $btnSave =
        $('#btnSaveMaterialPrices');

    let currentPriceListId = null;

    let currentPage = 1;

    let priceListsCache = [];

    let hasUnsavedChanges = false;

    /*
     * ============================================================
     * HELPERS
     * ============================================================
     */

    function buildUrl(template, id)
    {
        return template.replace(
            '__LIST__',
            id
        );
    }

    function buildVariantUrl(
        template,
        priceListId,
        materialId
    ) {
        return template
            .replace(
                '__LIST__',
                priceListId
            )
            .replace(
                '__MATERIAL__',
                materialId
            );
    }

    function escapeHtml(value)
    {
        return $('<div>')
            .text(value ?? '')
            .html();
    }

    /*
     * ============================================================
     * PRICE LISTS
     * ============================================================
     */

    function updatePriceListState()
    {
        const list = getCurrentPriceList();

        const isActive =
            list && !!list.is_active;

        $('[data-material-price]')
            .prop('disabled', !isActive);

        $btnSave
            .prop('disabled', !isActive);

        $('[data-material-variants]')
            .prop('disabled', !isActive);
    }

    function loadPriceLists(preferredId = null)
    {
        $priceList
            .empty()
            .append(
                '<option value="">Cargando...</option>'
            )
            .prop('disabled', true);

        $.ajax({
            url: urls.lists,
            method: 'GET',

            success: function (response) {

                const lists =
                    response.data || [];

                priceListsCache = lists;

                $priceList.empty();

                if (!lists.length) {

                    currentPriceListId = null;

                    $priceList.append(
                        '<option value="">Sin listas de precios</option>'
                    );

                    clearMaterials();

                    return;
                }

                let selectedId = null;

                lists.forEach(function (list) {

                    if (list.is_default) {
                        selectedId = list.id;
                    }

                    let label = list.name;

                    if (list.is_default) {
                        label += ' · PREDETERMINADA';
                    }

                    if (!list.is_active) {
                        label += ' · INACTIVA';
                    }

                    $priceList.append(
                        '<option value="' +
                        list.id +
                        '">' +
                        escapeHtml(label) +
                        '</option>'
                    );
                });

                /*
                 * Si no existe default,
                 * usamos la primera lista.
                 */
                if (!selectedId) {
                    selectedId =
                        lists[0].id;
                }

                /*
                 * Después de editar una lista,
                 * mantenemos esa lista seleccionada.
                 */
                if (
                    preferredId &&
                    lists.some(function (list) {
                        return parseInt(list.id) ===
                            parseInt(preferredId);
                    })
                ) {
                    selectedId =
                        preferredId;
                }

                $priceList.val(
                    selectedId
                );

                currentPriceListId =
                    parseInt(selectedId);

                loadMaterials(1);
            },

            error: function (xhr) {

                currentPriceListId = null;

                clearMaterials();

                toastr.error(
                    xhr.responseJSON?.message
                    || 'No se pudieron cargar las listas de precios.'
                );
            },

            complete: function () {

                $priceList.prop(
                    'disabled',
                    false
                );
            }
        });
    }

    function getCurrentPriceList()
    {
        return priceListsCache.find(function (list) {
            return parseInt(list.id) === parseInt(currentPriceListId);
        }) || null;
    }

    const $btnEdit =
        $('#btnEditPriceList');

    $btnEdit.on(
        'click',
        function () {

            if (hasUnsavedChanges) {
                toastr.warning(
                    'Guarde los cambios antes de editar la lista de precios.'
                );

                return;
            }

            const priceList =
                getCurrentPriceList();

            if (!priceList) {
                toastr.warning(
                    'Seleccione una lista de precios.'
                );

                return;
            }

            openEditPriceListModal(
                priceList
            );
        }
    );

    function openEditPriceListModal(priceList)
    {
        $.confirm({
            title:
                'Editar lista de precios',

            content: `
            <form>

                <div class="form-group">
                    <label>
                        Nombre
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        name="name"
                        value="${escapeHtml(priceList.name)}"
                    >
                </div>

                <div class="form-group">
                    <label>
                        Moneda
                    </label>

                    <select
                        class="form-control"
                        name="currency"
                    >
                        <option
                            value="PEN"
                            ${priceList.currency === 'PEN' ? 'selected' : ''}
                        >
                            PEN
                        </option>

                        <option
                            value="USD"
                            ${priceList.currency === 'USD' ? 'selected' : ''}
                        >
                            USD
                        </option>
                    </select>
                </div>

                <div class="custom-control custom-checkbox mb-2">

                    <input
                        type="checkbox"
                        class="custom-control-input"
                        id="editPriceListDefault"
                        name="is_default"
                        ${priceList.is_default ? 'checked' : ''}
                    >

                    <label
                        class="custom-control-label"
                        for="editPriceListDefault"
                    >
                        Lista predeterminada
                    </label>

                </div>

                <div class="custom-control custom-checkbox">

                    <input
                        type="checkbox"
                        class="custom-control-input"
                        id="editPriceListActive"
                        name="is_active"
                        ${priceList.is_active ? 'checked' : ''}
                    >

                    <label
                        class="custom-control-label"
                        for="editPriceListActive"
                    >
                        Activa
                    </label>

                </div>

            </form>
        `,

            type:
                'blue',

            buttons: {

                save: {
                    text:
                        'Guardar',

                    btnClass:
                        'btn-primary',

                    action:
                        function () {

                            const $form =
                                this.$content.find(
                                    'form'
                                );

                            const name =
                                $.trim(
                                    $form
                                        .find(
                                            '[name="name"]'
                                        )
                                        .val()
                                );

                            if (!name) {
                                toastr.warning(
                                    'Ingrese el nombre de la lista.'
                                );

                                return false;
                            }

                            updatePriceList(
                                priceList.id,
                                {
                                    name:
                                    name,

                                    currency:
                                        $form
                                            .find(
                                                '[name="currency"]'
                                            )
                                            .val(),

                                    is_default:
                                        $form
                                            .find(
                                                '[name="is_default"]'
                                            )
                                            .is(':checked')
                                            ? 1
                                            : 0,

                                    is_active:
                                        $form
                                            .find(
                                                '[name="is_active"]'
                                            )
                                            .is(':checked')
                                            ? 1
                                            : 0
                                }
                            );
                        }
                },

                cancel: {
                    text:
                        'Cancelar'
                }
            }
        });
    }


    function updatePriceList(
        priceListId,
        data
    ) {
        const url =
            buildUrl(
                urls.updateTemplate,
                priceListId
            );

        $.ajax({
            url:
            url,

            method:
                'PUT',

            headers: {
                'X-CSRF-TOKEN':
                    $(
                        'meta[name="csrf-token"]'
                    ).attr('content')
            },

            data:
            data,

            success:
                function (response) {

                    toastr.success(
                        response.message
                        || 'Lista actualizada correctamente.'
                    );

                    loadPriceLists(priceListId);
                },

            error:
                function (xhr) {

                    toastr.error(
                        xhr.responseJSON?.message
                        || 'No se pudo actualizar la lista.'
                    );
                }
        });
    }

    /*
     * ============================================================
     * MATERIALS
     * ============================================================
     */

    function loadMaterials(page = 1)
    {
        if (!currentPriceListId) {
            clearMaterials();
            return;
        }

        currentPage = page;

        const url = buildUrl(
            urls.materialsTemplate,
            currentPriceListId
        );

        $body.html(`
            <tr>
                <td colspan="3" class="text-center py-4">
                    Cargando productos...
                </td>
            </tr>
        `);

        $.ajax({
            url: url,
            method: 'GET',

            data: {
                search:
                    $.trim(
                        $search.val()
                    ),

                page:
                page
            },

            success: function (response) {

                renderMaterials(
                    response.data || []
                );

                updatePriceListState();

                renderPagination(response);
            },

            error: function (xhr) {

                clearMaterials();

                toastr.error(
                    xhr.responseJSON?.message
                    || 'No se pudieron cargar los productos.'
                );
            }
        });
    }

    function renderMaterials(materials)
    {
        $body.empty();

        if (!materials.length) {

            $body.html(`
                <tr>
                    <td colspan="3" class="text-center text-muted py-4">
                        No se encontraron productos habilitados para esta empresa.
                    </td>
                </tr>
            `);

            return;
        }

        materials.forEach(function (material) {

            const price =
                material.price === null
                    ? ''
                    : material.price;

            const row = `
                <tr
                    data-material-row
                    data-material-id="${material.id}"
                >

                    <td>
                        <strong>
                            ${escapeHtml(material.name)}
                        </strong>
                    </td>

                    <td>
                        <div class="d-flex align-items-center">
                    
                            <span class="mr-2">
                                ${material.stock_items_count}
                            </span>
                    
                            ${
                                    material.stock_items_count > 0
                                        ? `
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-material-variants
                                            data-material-id="${material.id}"
                                            data-material-name="${escapeHtml(material.name)}"
                                        >
                                            <i class="fas fa-layer-group"></i>
                                            Variantes
                                        </button>
                                    `
                                        : ''
                                    }
                    
                        </div>
                    </td>

                    <td>
                        <div class="input-group">

                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    S/
                                </span>
                            </div>

                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                class="form-control"
                                data-material-price
                                data-original-value="${price}"
                                value="${price}"
                                placeholder="Sin precio"
                            >

                        </div>
                    </td>

                </tr>
            `;

            $body.append(row);
        });
    }

    function recalculateUnsavedChanges()
    {
        hasUnsavedChanges = false;

        $('[data-material-price]').each(function () {

            const $input = $(this);

            const originalValue =
                $.trim(
                    String(
                        $input.data('original-value') ?? ''
                    )
                );

            const currentValue =
                $.trim(
                    String(
                        $input.val() ?? ''
                    )
                );

            if (originalValue !== currentValue) {
                hasUnsavedChanges = true;

                return false;
            }
        });
    }

    function clearMaterials()
    {
        $body.html(`
            <tr>
                <td colspan="3" class="text-center text-muted py-4">
                    Seleccione una lista de precios.
                </td>
            </tr>
        `);

        $pagination.empty();
    }

    /*
     * ============================================================
     * PAGINATION
     * ============================================================
     */

    function renderPagination(response)
    {
        $pagination.empty();

        if (
            !response.last_page ||
            response.last_page <= 1
        ) {
            return;
        }

        const $nav =
            $('<nav></nav>');

        const $ul =
            $('<ul class="pagination justify-content-center mb-0"></ul>');

        for (
            let page = 1;
            page <= response.last_page;
            page++
        ) {

            const active =
                page === response.current_page
                    ? ' active'
                    : '';

            $ul.append(`
                <li class="page-item${active}">
                    <a
                        href="#"
                        class="page-link"
                        data-price-page="${page}"
                    >
                        ${page}
                    </a>
                </li>
            `);
        }

        $nav.append($ul);

        $pagination.append($nav);
    }

    $(document).on(
        'click',
        '[data-price-page]',
        function (event) {

            event.preventDefault();

            if (hasUnsavedChanges) {
                toastr.warning(
                    'Guarde los cambios antes de cambiar de página.'
                );

                return;
            }

            const page =
                parseInt(
                    $(this).data('price-page')
                );

            loadMaterials(page);
        }
    );

    /*
     * ============================================================
     * CAMBIO DE PRICE LIST
     * ============================================================
     */

    $priceList.on(
        'change',
        function () {

            if (hasUnsavedChanges) {
                toastr.warning(
                    'Guarde los cambios antes de cambiar de lista de precios.'
                );

                $priceList.val(
                    currentPriceListId
                ).trigger('change.select2');

                return;
            }

            currentPriceListId =
                parseInt(
                    $(this).val()
                ) || null;

            loadMaterials(1);
        }
    );

    /*
     * ============================================================
     * SEARCH
     * ============================================================
     */

    let searchTimer = null;

    $search.on(
        'input',
        function () {

            clearTimeout(
                searchTimer
            );

            searchTimer =
                setTimeout(
                    function () {

                        if (hasUnsavedChanges) {
                            toastr.warning(
                                'Guarde los cambios antes de realizar otra búsqueda.'
                            );

                            return;
                        }

                        loadMaterials(1);
                    },
                    350
                );
        }
    );

    /*
     * ============================================================
     * GUARDAR PRECIOS
     * ============================================================
     */

    $btnSave.on(
        'click',
        function () {

            if (!currentPriceListId) {

                toastr.warning(
                    'Seleccione una lista de precios.'
                );

                return;
            }

            const prices = [];

            $('[data-material-row]')
                .each(function () {

                    const $row =
                        $(this);

                    const materialId =
                        parseInt(
                            $row.data(
                                'material-id'
                            )
                        );

                    const rawPrice =
                        $.trim(
                            $row
                                .find(
                                    '[data-material-price]'
                                )
                                .val()
                        );

                    prices.push({
                        material_id:
                        materialId,

                        price:
                            rawPrice === ''
                                ? null
                                : rawPrice
                    });
                });

            if (!prices.length) {
                return;
            }

            $.confirm({
                title:
                    'Guardar precios',

                content:
                    '¿Desea guardar los precios ingresados para esta lista?',

                type:
                    'blue',

                buttons: {

                    confirm: {
                        text:
                            'Guardar',

                        btnClass:
                            'btn-primary',

                        action:
                            function () {
                                saveMaterialPrices(
                                    prices
                                );
                            }
                    },

                    cancel: {
                        text:
                            'Cancelar'
                    }
                }
            });
        }
    );

    function saveMaterialPrices(prices)
    {
        const url = buildUrl(
            urls.saveMaterialsTemplate,
            currentPriceListId
        );

        $btnSave.prop(
            'disabled',
            true
        );

        $.ajax({
            url: url,

            method: 'POST',

            headers: {
                'X-CSRF-TOKEN':
                    $(
                        'meta[name="csrf-token"]'
                    ).attr('content')
            },

            data: {
                prices:
                prices
            },

            success: function (response) {

                hasUnsavedChanges = false;

                toastr.success(
                    response.message
                    || 'Precios guardados correctamente.'
                );

                loadMaterials(
                    currentPage
                );
            },

            error: function (xhr) {

                toastr.error(
                    xhr.responseJSON?.message
                    || 'No se pudieron guardar los precios.'
                );
            },

            complete: function () {

                $btnSave.prop(
                    'disabled',
                    false
                );
            }
        });
    }

    /*
     * ============================================================
     * NUEVA PRICE LIST
     * ============================================================
     */

    $btnNew.on(
        'click',
        function () {

            if (hasUnsavedChanges) {
                toastr.warning(
                    'Guarde los cambios antes de crear una nueva lista de precios.'
                );

                return;
            }

            $.confirm({
                title:
                    'Nueva lista de precios',

                content: `
                    <form>

                        <div class="form-group">
                            <label>
                                Nombre
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                name="name"
                                placeholder="Ej. REGULAR"
                            >
                        </div>

                        <div class="form-group">
                            <label>
                                Moneda
                            </label>

                            <select
                                class="form-control"
                                name="currency"
                            >
                                <option value="PEN">
                                    PEN
                                </option>

                                <option value="USD">
                                    USD
                                </option>
                            </select>
                        </div>

                        <div class="custom-control custom-checkbox">

                            <input
                                type="checkbox"
                                class="custom-control-input"
                                id="newPriceListDefault"
                                name="is_default"
                            >

                            <label
                                class="custom-control-label"
                                for="newPriceListDefault"
                            >
                                Lista predeterminada
                            </label>

                        </div>
                        
                        <div class="custom-control custom-checkbox mt-2">

                            <input
                                type="checkbox"
                                class="custom-control-input"
                                id="newPriceListActive"
                                name="is_active"
                                checked
                            >
                        
                            <label
                                class="custom-control-label"
                                for="newPriceListActive"
                            >
                                Activa
                            </label>
                        
                        </div>

                    </form>
                `,

                type:
                    'blue',

                buttons: {

                    save: {
                        text:
                            'Guardar',

                        btnClass:
                            'btn-primary',

                        action:
                            function () {

                                const $form =
                                    this.$content
                                        .find(
                                            'form'
                                        );

                                const name =
                                    $.trim(
                                        $form
                                            .find(
                                                '[name="name"]'
                                            )
                                            .val()
                                    );

                                if (!name) {

                                    toastr.warning(
                                        'Ingrese el nombre de la lista.'
                                    );

                                    return false;
                                }

                                const isDefault =
                                    $form
                                        .find('[name="is_default"]')
                                        .is(':checked');

                                const isActive =
                                    $form
                                        .find('[name="is_active"]')
                                        .is(':checked');

                                if (isDefault && !isActive) {
                                    toastr.warning(
                                        'Una lista predeterminada debe estar activa.'
                                    );

                                    return false;
                                }

                                createPriceList({
                                    name: name,

                                    currency:
                                        $form
                                            .find(
                                                '[name="currency"]'
                                            )
                                            .val(),

                                    is_default:
                                        $form
                                            .find(
                                                '[name="is_default"]'
                                            )
                                            .is(':checked')
                                            ? 1
                                            : 0,

                                    is_active:
                                        $form
                                            .find(
                                                '[name="is_active"]'
                                            )
                                            .is(':checked')
                                            ? 1
                                            : 0
                                });
                            }
                    },

                    cancel: {
                        text:
                            'Cancelar'
                    }
                }
            });
        }
    );

    function createPriceList(data)
    {
        $.ajax({
            url:
            urls.store,

            method:
                'POST',

            headers: {
                'X-CSRF-TOKEN':
                    $(
                        'meta[name="csrf-token"]'
                    ).attr('content')
            },

            data:
            data,

            success:
                function (response) {

                    toastr.success(
                        response.message
                        || 'Lista creada correctamente.'
                    );

                    loadPriceLists();
                },

            error:
                function (xhr) {

                    toastr.error(
                        xhr.responseJSON?.message
                        || 'No se pudo crear la lista.'
                    );
                }
        });
    }


    $(document).on(
        'input',
        '[data-material-price]',
        function () {
            recalculateUnsavedChanges();
        }
    );

    $(document).on(
        'click',
        '[data-material-variants]',
        function () {

            if (hasUnsavedChanges) {
                toastr.warning(
                    'Guarde primero los cambios de precios base.'
                );

                return;
            }

            if (!currentPriceListId) {
                toastr.warning(
                    'Seleccione una lista de precios.'
                );

                return;
            }

            const materialId =
                parseInt(
                    $(this).data(
                        'material-id'
                    )
                );

            openMaterialVariants(
                materialId
            );
        }
    );

    function openMaterialVariants(
        materialId
    ) {
        const url =
            buildVariantUrl(
                urls.variantsTemplate,
                currentPriceListId,
                materialId
            );

        $.ajax({
            url:
            url,

            method:
                'GET',

            success:
                function (response) {

                    showMaterialVariantsModal(
                        materialId,
                        response
                    );
                },

            error:
                function (xhr) {

                    toastr.error(
                        xhr.responseJSON?.message
                        || 'No se pudieron cargar las variantes.'
                    );
                }
        });
    }

    function showMaterialVariantsModal(
        materialId,
        response
    ) {
        const material =
            response.material;

        const items =
            response.data || [];

        let rows = '';

        if (!items.length) {

            rows = `
            <tr>
                <td
                    colspan="4"
                    class="text-center text-muted"
                >
                    No existen StockItems habilitados.
                </td>
            </tr>
        `;

        } else {

            items.forEach(
                function (item) {

                    const basePrice =
                        item.base_price === null
                            ? '-'
                            : parseFloat(
                            item.base_price
                            ).toFixed(2);

                    const effectivePrice =
                        item.effective_price === null
                            ? '-'
                            : parseFloat(
                            item.effective_price
                            ).toFixed(2);

                    const overridePrice =
                        item.override_price === null
                            ? ''
                            : item.override_price;

                    const source =
                        item.source === 'stock_item'
                            ? 'Especial'
                            : (
                                item.source === 'material'
                                    ? 'Base'
                                    : 'Sin precio'
                            );

                    rows += `
                    <tr
                        data-variant-price-row
                        data-stock-item-id="${item.stock_item_id}"
                    >

                        <td>
                            <strong>
                                ${escapeHtml(item.name)}
                            </strong>

                            ${
                        item.sku
                            ? `
                                        <div class="small text-muted">
                                            SKU: ${escapeHtml(item.sku)}
                                        </div>
                                    `
                            : ''
                        }
                        </td>

                        <td class="text-right">
                            ${
                        basePrice === '-'
                            ? '-'
                            : 'S/ ' + basePrice
                        }
                        </td>

                        <td class="text-right">
                            ${
                        effectivePrice === '-'
                            ? '-'
                            : 'S/ ' + effectivePrice
                        }

                            <div class="small text-muted">
                                ${source}
                            </div>
                        </td>

                        <td>
                            <div class="input-group input-group-sm">

                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        S/
                                    </span>
                                </div>

                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="form-control"
                                    data-variant-override-price
                                    value="${overridePrice}"
                                    placeholder="Heredar"
                                >

                            </div>
                        </td>

                    </tr>
                `;
                }
            );
        }

        $.confirm({
            title:
                escapeHtml(
                    material.name
                ),

            columnClass:
                'col-md-10 col-md-offset-1',

            content: `
            <div class="mb-3">

                <div>
                    <strong>
                        Precio base:
                    </strong>

                    ${
                material.base_price === null
                    ? '<span class="text-muted">Sin precio</span>'
                    : 'S/ ' +
                    parseFloat(
                        material.base_price
                    ).toFixed(2)
                }
                </div>

                <small class="text-muted">
                    Deje el precio especial vacío para utilizar el precio base del producto.
                </small>

            </div>

            <div class="table-responsive">

                <table class="table table-sm table-hover">

                    <thead>

                        <tr>
                            <th>
                                Variante
                            </th>

                            <th class="text-right">
                                Base
                            </th>

                            <th class="text-right">
                                Efectivo
                            </th>

                            <th style="width: 180px;">
                                Precio especial
                            </th>
                        </tr>

                    </thead>

                    <tbody>
                        ${rows}
                    </tbody>

                </table>

            </div>
        `,

            type:
                'blue',

            buttons: {

                save: {
                    text:
                        'Guardar',

                    btnClass:
                        'btn-primary',

                    action:
                        function () {

                            const itemsToSave = [];

                            this.$content
                                .find(
                                    '[data-variant-price-row]'
                                )
                                .each(function () {

                                    const $row =
                                        $(this);

                                    const stockItemId =
                                        parseInt(
                                            $row.data(
                                                'stock-item-id'
                                            )
                                        );

                                    const rawPrice =
                                        $.trim(
                                            $row
                                                .find(
                                                    '[data-variant-override-price]'
                                                )
                                                .val()
                                        );

                                    itemsToSave.push({
                                        stock_item_id:
                                        stockItemId,

                                        price:
                                            rawPrice === ''
                                                ? null
                                                : rawPrice
                                    });
                                });

                            saveMaterialVariants(
                                materialId,
                                itemsToSave
                            );
                        }
                },

                cancel: {
                    text:
                        'Cancelar'
                }
            }
        });
    }

    function saveMaterialVariants(
        materialId,
        items
    ) {
        const url =
            buildVariantUrl(
                urls.saveVariantsTemplate,
                currentPriceListId,
                materialId
            );

        $.ajax({
            url:
            url,

            method:
                'POST',

            headers: {
                'X-CSRF-TOKEN':
                    $(
                        'meta[name="csrf-token"]'
                    ).attr('content')
            },

            data: {
                items:
                items
            },

            success:
                function (response) {

                    toastr.success(
                        response.message
                        || 'Precios de variantes guardados correctamente.'
                    );

                    /*
                     * Recargamos Materials por si después
                     * queremos mostrar indicadores de overrides.
                     */
                    loadMaterials(
                        currentPage
                    );
                },

            error:
                function (xhr) {

                    toastr.error(
                        xhr.responseJSON?.message
                        || 'No se pudieron guardar los precios de las variantes.'
                    );
                }
        });
    }


    /*
     * ============================================================
     * INITIAL LOAD
     * ============================================================
     */

    $('.select2').select2({
        width:
            '100%'
    });

    loadPriceLists();
});