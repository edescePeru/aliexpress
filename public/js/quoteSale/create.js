let $consumables = [];
let $equipments = [];

var $permissions;
var $igv;

var $formCreate;
var $selectCustomer;
var $selectContact;

var $modalConsumableQty;
var $currentConsumableRender = null;
var $currentConsumable = null;

var $modalSelectItemeableItems;


/*
|--------------------------------------------------------------------------
| INICIALIZACIÓN
|--------------------------------------------------------------------------
*/

$(document).ready(function () {

    $permissions = JSON.parse(
        $('#permissions').val() || '[]'
    );

    $igv = parseFloat(
        $('#igv').val() || 18
    );

    $formCreate = $('#formCreate');

    $selectCustomer = $('#customer_id');
    $selectContact = $('#contact_id');

    $modalConsumableQty = $('#modalQuantityConsumable');
    $modalSelectItemeableItems = $('#modalSelectItemeableItems');


    /*
    |--------------------------------------------------------------------------
    | Cargar información completa de productos
    |--------------------------------------------------------------------------
    |
    | Este endpoint devuelve la información comercial que necesitamos
    | cuando el usuario selecciona un StockItem:
    |
    | - id = StockItem
    | - material_id
    | - display_name
    | - full_description
    | - list_price
    | - stock_available
    | - tipo_venta_id
    | - unit_measure
    |
    */

    $.ajax({
        url: '/dashboard/get/quote/sale/materials/totals',
        type: 'GET',
        dataType: 'json',

        success: function (json) {

            $consumables = [];

            if (!Array.isArray(json)) {
                return;
            }

            json.forEach(function (item) {
                $consumables.push(item);
            });
        },

        error: function () {
            toastr.error(
                'No se pudo cargar la información de los productos.',
                'Error'
            );
        }
    });


    /*
    |--------------------------------------------------------------------------
    | Buscador Select2 de productos
    |--------------------------------------------------------------------------
    */

    $('.consumable_search').select2({
        placeholder: 'Selecciona un producto',
        width: '100%',

        ajax: {
            url: '/dashboard/get/quote/sale/materials',
            dataType: 'json',
            type: 'GET',

            processResults: function (data) {

                return {
                    results: $.map(
                        data || [],
                        function (item) {

                            return {
                                text: item.display_name,
                                id: item.id
                            };
                        }
                    )
                };
            }
        }
    });


    /*
    |--------------------------------------------------------------------------
    | Eventos principales
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '[data-confirm]',
        confirmEquipment
    );

    $(document).on(
        'click',
        '[data-saveEquipment]',
        saveEquipment
    );

    $(document).on(
        'click',
        '[data-addConsumable]',
        addConsumable
    );

    $(document).on(
        'click',
        '[data-deleteConsumable]',
        deleteConsumable
    );

    $(document).on(
        'click',
        '[data-addService]',
        addService
    );

    $('#btn-submit').on(
        'click',
        storeQuote
    );


    /*
    |--------------------------------------------------------------------------
    | Cambios en productos
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'input',
        '[data-consumableQuantity]',
        function () {

            calculateTotalC(this);

            markEquipDirty(this);
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Cambios en detalle del Equipment
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'input',
        '[data-detailequipment]',
        function () {
            markEquipDirty(this);
        }
    );

    $(document).on(
        'summernote.change',
        '.textarea_edit',
        function () {
            markEquipDirty(this);
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Servicios
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'input',
        '[data-serviceQuantity]',
        function () {

            const row = $(this)
                .closest('[data-serviceRow]');

            calculateServiceRow(row);

            markEquipDirty(this);
        }
    );

    $(document).on(
        'input',
        '[data-servicePU]',
        function () {

            const row = $(this)
                .closest('[data-serviceRow]');

            calculateServiceRow(row);

            markEquipDirty(this);
        }
    );

    $(document).on(
        'change',
        '[data-serviceBillable]',
        function () {
            markEquipDirty(this);
        }
    );

    $(document).on(
        'click',
        '[data-deleteService]',
        function () {

            markEquipDirty(this);

            $(this)
                .closest('[data-serviceRow]')
                .remove();
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Cliente / contactos
    |--------------------------------------------------------------------------
    */

    $selectCustomer.on(
        'change',
        function () {

            $selectContact.empty();

            const customerId =
                $selectCustomer.val();

            $selectContact.append(
                $('<option>', {
                    value: '',
                    text: 'Seleccione contacto'
                })
            );

            if (!customerId) {
                return;
            }

            $.get(
                '/dashboard/get/contact/' + customerId,
                function (data) {

                    (data || []).forEach(
                        function (contact) {

                            $selectContact.append(
                                $('<option>', {
                                    value: contact.id,
                                    text: contact.contact
                                })
                            );
                        }
                    );
                }
            );
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Modal crear cliente
    |--------------------------------------------------------------------------
    */

    $('#btn-add-customer').on(
        'click',
        function () {

            const form =
                $('#formCreateCustomer')[0];

            if (form) {
                form.reset();
            }

            $('#modalCustomer')
                .modal('show');
        }
    );

    $('#btn-submit-customer').on(
        'click',
        function (e) {

            e.preventDefault();

            const $form =
                $('#formCreateCustomer');

            const url =
                $form.data('url');

            const formData =
                $form.serialize();

            $.ajax({
                type: 'POST',
                url: url,
                data: formData,

                success: function (response) {

                    toastr.success(
                        response.message
                    );

                    $('#modalCustomer')
                        .modal('hide');

                    const customer =
                        response.customer;

                    if (customer) {

                        const newOption =
                            new Option(
                                customer.business_name,
                                customer.id,
                                true,
                                true
                            );

                        $('#customer_id')
                            .append(newOption)
                            .trigger('change');
                    }

                    const form =
                        $('#formCreateCustomer')[0];

                    if (form) {
                        form.reset();
                    }
                },

                error: function (xhr) {

                    const message =
                        xhr.responseJSON &&
                        xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Error al guardar el cliente.';

                    toastr.error(
                        message,
                        'Error'
                    );
                }
            });
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Modal cantidad / presentación
    |--------------------------------------------------------------------------
    */

    $('#btn-notAddConsumable').on(
        'click',
        function () {

            $modalConsumableQty
                .modal('hide');
        }
    );

    $('#btn-add_consumable_modal').on(
        'click',
        addConsumableFromModal
    );


    /*
    |--------------------------------------------------------------------------
    | Descuento global
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'change',
        'input[name="discount_type"]',
        function () {

            const type =
                $(this).val();

            $('#discountSection')
                .attr(
                    'data-discount_type',
                    type
                );

            if (type === 'percent') {

                $('#discount_value_hint')
                    .text(
                        'Ingrese porcentaje (0 a 100).'
                    );

            } else {

                $('#discount_value_hint')
                    .text(
                        'Ingrese monto.'
                    );
            }
        }
    );

    $(document).on(
        'change',
        'input[name="discount_input_mode"]',
        function () {

            const mode =
                $(this).val();

            $('#discountSection')
                .attr(
                    'data-discount_input_mode',
                    mode
                );
        }
    );

    $(document).on(
        'input',
        '#discount_value',
        function () {

            let value =
                parseFloat(
                    $(this).val() || 0
                );

            if (
                isNaN(value) ||
                value < 0
            ) {
                value = 0;
            }

            $('#discountSection')
                .attr(
                    'data-discount_value',
                    value.toFixed(2)
                );
        }
    );

    $('#btn-clear-discount').on(
        'click',
        function () {

            $('#discount_type_amount')
                .prop('checked', true)
                .trigger('change');

            $('#discount_mode_without')
                .prop('checked', true)
                .trigger('change');

            $('#discount_value')
                .val(0)
                .trigger('input');
        }
    );

    $(document).on(
        'change input',
        '#discountSection input, #discount_value',
        function () {

            $('[data-equip]').each(
                function () {
                    markEquipDirty($(this));
                }
            );
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Modal itemeables
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'change',
        '.itemeable-item-checkbox',
        function () {

            const requiredCount =
                parseInt(
                    $('#btn-confirm-itemeable-items')
                        .data('required-count')
                    || 0
                );

            updateItemeableItemsCounter(
                requiredCount
            );
        }
    );

    $('#btn-confirm-itemeable-items').on(
        'click',
        confirmItemeableItems
    );

    $('#btn-cancel-itemeable-items').on(
        'click',
        function () {

            window.$currentItemeableDraft =
                null;

            $modalSelectItemeableItems
                .modal('hide');

            $modalConsumableQty
                .modal('show');
        }
    );

    $(document).on(
        'keydown',
        '#itemeable-item-search',
        function (e) {

            if (e.key === 'Enter') {

                e.preventDefault();

                selectItemByScannedCode();
            }
        }
    );

    $(document).on(
        'change',
        '#itemeable-item-search',
        function () {

            selectItemByScannedCode();
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Al cerrar modal cantidad
    |--------------------------------------------------------------------------
    */

    $('#modalQuantityConsumable').on(
        'hidden.bs.modal',
        function () {

            if (
                document.activeElement &&
                document.activeElement.blur
            ) {
                document.activeElement.blur();
            }
        }
    );
});


/*
|--------------------------------------------------------------------------
| AGREGAR PRODUCTO
|--------------------------------------------------------------------------
*/

function addConsumable() {
    const $button = $(this);

    const consumableID = parseInt(
        $button
            .closest('.row')
            .find('[data-consumable]')
            .val() || 0
    );

    if (!consumableID) {
        toastr.error(
            'Debe seleccionar un producto.',
            'Error'
        );

        return;
    }

    const $render = $button
        .closest('.card-body')
        .find('[data-bodyConsumable]')
        .first();

    const consumable = $consumables.find(
        function (item) {
            return parseInt(item.id) === consumableID;
        }
    );

    if (!consumable) {
        toastr.error(
            'No se encontró la información del producto.',
            'Error'
        );

        return;
    }

    /*
     * ============================================================
     * 1. SIN PRECIO CONFIGURADO
     * ============================================================
     *
     * No es lo mismo que precio = 0.
     *
     * Significa que PriceResolverService no encontró:
     *
     * - PriceListItem
     * - PriceListMaterial
     */
    if (!consumable.has_price) {
        toastr.error(
            'El producto no tiene un precio configurado en la lista de precios "' +
            (consumable.price_list_name || 'predeterminada') +
            '".',
            'Producto sin precio'
        );

        return;
    }

    /*
     * ============================================================
     * 2. PRECIO CONFIGURADO
     * ============================================================
     */

    const consumablePrice = parseFloat(
        consumable.list_price || 0
    );

    /*
     * ============================================================
     * 3. PRECIO EXPLÍCITO EN CERO
     * ============================================================
     *
     * Aquí SÍ existe un registro de precio,
     * pero el valor configurado es 0.
     */
    if (consumablePrice === 0) {
        $.confirm({
            icon: 'fas fa-exclamation-triangle',
            theme: 'modern',
            closeIcon: true,
            animation: 'zoom',
            type: 'orange',

            title: 'Precio en cero',

            content:
                'Este producto tiene un precio de venta configurado en 0. ' +
                '¿Desea continuar con la cotización?',

            buttons: {
                confirm: {
                    text: 'SÍ, CONTINUAR',
                    btnClass: 'btn-orange',

                    action: function () {
                        clearConsumableSearch();

                        showModalQuantityConsumable(
                            $render,
                            consumable
                        );
                    }
                },

                cancel: {
                    text: 'CANCELAR'
                }
            }
        });

        return;
    }

    /*
     * ============================================================
     * 4. PRECIO NORMAL
     * ============================================================
     */

    clearConsumableSearch();

    showModalQuantityConsumable(
        $render,
        consumable
    );
}


function clearConsumableSearch() {

    $('.consumable_search')
        .val(null)
        .trigger('change');
}


/*
|--------------------------------------------------------------------------
| MODAL CANTIDAD / PRESENTACIONES
|--------------------------------------------------------------------------
*/

function showModalQuantityConsumable(
    render,
    consumable
) {

    $currentConsumableRender =
        render;

    $currentConsumable =
        consumable;

    $('#c_quantity_is_itemeable')
        .val(
            parseInt(
                consumable.tipo_venta_id || 0
            ) === 3
                ? 1
                : 0
        );

    $('#c_quantity_productId')
        .val(consumable.id);

    $('#c_quantity_total')
        .val(0);

    $('#c_quantity_stock_show')
        .val(
            consumable.stock_available || 0
        );

    $('#c_presentationsArea')
        .html(
            '<div class="text-muted">' +
            'Cargando presentaciones...' +
            '</div>'
        );

    fetchPresentations(
        consumable.material_id
    )
        .then(function (presentations) {

            const actives =
                (presentations || []).filter(
                    function (item) {

                        return (
                            item.active === true ||
                            item.active === 1 ||
                            item.active === '1'
                        );
                    }
                );

            renderPresentationsInModalConsumable(
                actives
            );

            $modalConsumableQty
                .modal('show');
        })
        .catch(function () {

            $('#c_presentationsArea')
                .html(
                    '<div class="text-danger">' +
                    'No se pudieron cargar las presentaciones.' +
                    '</div>'
                );

            $modalConsumableQty
                .modal('show');
        });
}


function fetchPresentations(
    materialId
) {

    return $.ajax({
        url:
            '/dashboard/materials-presentations/material/' +
            materialId +
            '/presentations',

        method: 'GET',
        dataType: 'json'

    }).then(function (response) {

        return response.presentations || [];
    });
}


function renderPresentationsInModalConsumable(
    presentations
) {

    if (
        !presentations ||
        presentations.length === 0
    ) {

        $('#c_presentationsArea')
            .html(
                '<div class="text-muted">' +
                'Este producto no tiene presentaciones configuradas.' +
                '</div>'
            );

        return;
    }

    let html = `
        <div class="table-responsive">

            <table class="table table-sm table-bordered mb-0">

                <thead>
                    <tr>
                        <th style="width:45%;">
                            Presentación
                        </th>

                        <th style="width:25%;">
                            Precio
                        </th>

                        <th style="width:30%;">
                            Paquetes
                        </th>
                    </tr>
                </thead>

                <tbody>
    `;

    presentations.forEach(
        function (presentation) {

            const quantity =
                parseInt(
                    presentation.quantity || 0
                );

            const price =
                parseFloat(
                    presentation.price || 0
                );

            const label =
                presentation.label &&
                presentation.label.trim()
                    ? presentation.label
                    : quantity + ' und';

            html += `
                <tr
                    data-pres-row
                    data-pres-id="${presentation.id}"
                    data-pres-qty="${quantity}"
                    data-pres-price="${price}"
                    data-pres-label="${label}"
                >

                    <td>

                        <strong>
                            ${label}
                        </strong>

                        <div
                            class="text-muted"
                            style="font-size:12px;"
                        >
                            Equivale a ${quantity} unidades
                        </div>

                    </td>

                    <td>
                        S/. ${price.toFixed(2)}
                    </td>

                    <td>

                        <input
                            type="number"
                            min="0"
                            step="1"
                            class="form-control form-control-sm"
                            value="0"
                            data-pres-packs
                        >

                    </td>

                </tr>
            `;
        }
    );

    html += `
                </tbody>
            </table>
        </div>
    `;

    $('#c_presentationsArea')
        .html(html);
}


function addConsumableFromModal() {

    if (
        !$currentConsumable ||
        !$currentConsumableRender
    ) {

        toastr.error(
            'No hay producto seleccionado.',
            'Error'
        );

        return;
    }

    let linesToAdd = [];

    let hasPresentation =
        false;


    /*
    |--------------------------------------------------------------------------
    | Presentaciones
    |--------------------------------------------------------------------------
    */

    $('[data-pres-row]').each(
        function () {

            const $row =
                $(this);

            const packs =
                parseInt(
                    $row
                        .find('[data-pres-packs]')
                        .val()
                    || 0
                );

            if (packs <= 0) {
                return;
            }

            hasPresentation = true;

            const presentationId =
                parseInt(
                    $row.attr(
                        'data-pres-id'
                    )
                );

            const unitsPerPack =
                parseInt(
                    $row.attr(
                        'data-pres-qty'
                    )
                    || 0
                );

            const presentationPrice =
                parseFloat(
                    $row.attr(
                        'data-pres-price'
                    )
                    || 0
                );

            const presentationLabel =
                $row.attr(
                    'data-pres-label'
                )
                || '';

            const unitsEquivalent =
                packs *
                unitsPerPack;

            linesToAdd.push({
                quantity_to_show:
                packs,

                price:
                presentationPrice,

                presentation: {
                    id:
                    presentationId,

                    text:
                    presentationLabel,

                    packs:
                    packs,

                    unitsPerPack:
                    unitsPerPack,

                    unitsEquivalent:
                    unitsEquivalent,

                    pricePack:
                    presentationPrice
                },

                total_units_required:
                unitsEquivalent
            });
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Venta por unidad
    |--------------------------------------------------------------------------
    */

    if (!hasPresentation) {

        const qty =
            parseFloat(
                $('#c_quantity_total').val()
                || 0
            );

        if (qty <= 0) {

            toastr.error(
                'Ingrese una cantidad o seleccione una presentación.',
                'Error'
            );

            return;
        }

        const unitPrice =
            parseFloat(
                $currentConsumable.list_price
                || 0
            );

        linesToAdd.push({
            quantity_to_show:
            qty,

            price:
            unitPrice,

            presentation:
                null,

            total_units_required:
            qty
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Producto itemeable
    |--------------------------------------------------------------------------
    */

    const isItemeable =
        parseInt(
            $currentConsumable.tipo_venta_id
            || 0
        ) === 3;

    if (isItemeable) {

        const totalUnitsRequired =
            linesToAdd.reduce(
                function (total, line) {

                    return total +
                        parseFloat(
                            line.total_units_required
                            || 0
                        );
                },
                0
            );

        if (
            !Number.isInteger(
                totalUnitsRequired
            )
        ) {

            toastr.error(
                'Los productos itemeables solo pueden venderse en unidades enteras.',
                'Cantidad inválida'
            );

            return;
        }

        if (
            totalUnitsRequired <= 0
        ) {

            toastr.error(
                'Debe seleccionar al menos un ítem.',
                'Error'
            );

            return;
        }

        window.$currentItemeableDraft = {
            consumable:
            $currentConsumable,

            render:
            $currentConsumableRender,

            lines:
            linesToAdd,

            total_units_required:
            totalUnitsRequired
        };

        $modalConsumableQty
            .modal('hide');

        openItemeableItemsSelector(
            window.$currentItemeableDraft
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Producto normal
    |--------------------------------------------------------------------------
    */

    linesToAdd.forEach(
        function (line) {

            renderTemplateConsumable(
                $currentConsumableRender,
                $currentConsumable,
                line.quantity_to_show,
                line.price,
                0,
                true,
                line.presentation
            );
        }
    );

    $modalConsumableQty
        .modal('hide');
}


/*
|--------------------------------------------------------------------------
| PRODUCTOS ITEMEABLES
|--------------------------------------------------------------------------
*/

function openItemeableItemsSelector(
    draft
) {

    if (
        !draft ||
        !draft.consumable
    ) {

        toastr.error(
            'No se pudo preparar la selección de ítems.',
            'Error'
        );

        return;
    }

    const consumable =
        draft.consumable;

    /*
     * El ID recibido corresponde al StockItem.
     */
    const stockItemId =
        parseInt(
            consumable.id || 0
        );

    if (!stockItemId) {

        toastr.error(
            'No se pudo identificar el StockItem del producto.',
            'Error'
        );

        return;
    }

    const requiredCount =
        parseInt(
            draft.total_units_required
            || 0
        );

    if (
        requiredCount <= 0
    ) {

        toastr.error(
            'La cantidad de ítems requerida no es válida.',
            'Error'
        );

        return;
    }

    $('#itemeable-product-name')
        .text(
            consumable.display_name ||
            consumable.full_description ||
            ''
        );

    $('#itemeable-required-count')
        .text(requiredCount);

    $('#itemeable-selected-count')
        .text(0);

    $('#itemeable-selected-required-count')
        .text(requiredCount);

    $('#itemeable-items-loading')
        .show();

    $('#itemeable-items-empty')
        .hide();

    $('#itemeable-items-error')
        .hide();

    $('#itemeable-items-table-container')
        .hide();

    $('#itemeable-items-table-body')
        .empty();

    $('#itemeable-item-search')
        .val('');

    $('#btn-confirm-itemeable-items')
        .prop(
            'disabled',
            true
        )
        .data(
            'required-count',
            requiredCount
        );

    $modalSelectItemeableItems
        .modal({
            backdrop: 'static',
            keyboard: false
        });

    $modalSelectItemeableItems
        .modal('show');

    const url =
        window.APP_QUOTE
            .URLS
            .AVAILABLE_ITEMS
            .replace(
                ':stockItemId',
                stockItemId
            );

    $.ajax({
        url: url,
        method: 'GET',

        success: function (
            response
        ) {

            $('#itemeable-items-loading')
                .hide();

            if (
                !response ||
                !response.success
            ) {

                $('#itemeable-items-error')
                    .show();

                return;
            }

            const items =
                response.items || [];

            if (
                items.length === 0
            ) {

                $('#itemeable-items-empty')
                    .show();

                return;
            }

            renderItemeableItems(
                items,
                requiredCount
            );

            $('#itemeable-items-table-container')
                .show();

            $('#itemeable-item-search')
                .focus();
        },

        error: function () {

            $('#itemeable-items-loading')
                .hide();

            $('#itemeable-items-error')
                .show();
        }
    });
}


function renderItemeableItems(
    items,
    requiredCount
) {

    let html = '';

    items.forEach(
        function (item) {

            const itemCode =
                item.code ||
                (
                    'Ítem #' +
                    item.id
                );

            const lotText =
                item.stock_lot_code ||
                item.lot_code ||
                item.stock_lot_id ||
                '-';

            const locationText =
                item.warehouse_name ||
                item.location ||
                '-';

            html += `
                <tr
                    data-item-row
                    data-item-id="${item.id}"
                    data-item-code="${escapeHtml(itemCode)}"
                >

                    <td class="text-center">

                        <input
                            type="checkbox"
                            class="itemeable-item-checkbox"
                            value="${item.id}"
                            data-item-id="${item.id}"
                            data-item-code="${escapeHtml(itemCode)}"
                        >

                    </td>

                    <td>
                        ${escapeHtml(itemCode)}
                    </td>

                    <td>
                        ${escapeHtml(String(lotText))}
                    </td>

                    <td>
                        ${escapeHtml(String(locationText))}
                    </td>

                </tr>
            `;
        }
    );

    $('#itemeable-items-table-body')
        .html(html);

    updateItemeableItemsCounter(
        requiredCount
    );
}


function updateItemeableItemsCounter(
    requiredCount
) {

    const selectedCount =
        $('.itemeable-item-checkbox:checked')
            .length;

    $('#itemeable-selected-count')
        .text(selectedCount);

    const $checkboxes =
        $('.itemeable-item-checkbox');

    if (
        selectedCount >=
        requiredCount
    ) {

        $checkboxes
            .not(':checked')
            .prop(
                'disabled',
                true
            );

    } else {

        $checkboxes
            .prop(
                'disabled',
                false
            );
    }

    $('#btn-confirm-itemeable-items')
        .prop(
            'disabled',
            selectedCount !==
            requiredCount
        );
}


function confirmItemeableItems() {

    const draft =
        window.$currentItemeableDraft;

    if (!draft) {

        toastr.error(
            'No se encontró la información temporal del producto.',
            'Error'
        );

        return;
    }

    const requiredCount =
        parseInt(
            draft.total_units_required
            || 0
        );

    let selectedItems = [];

    $('.itemeable-item-checkbox:checked')
        .each(
            function () {

                selectedItems.push({
                    id:
                        parseInt(
                            $(this)
                                .data('item-id')
                        ),

                    code:
                        $(this)
                            .data('item-code')
                });
            }
        );

    if (
        selectedItems.length !==
        requiredCount
    ) {

        toastr.error(
            'Debe seleccionar exactamente ' +
            requiredCount +
            ' ítems.',
            'Selección incompleta'
        );

        return;
    }

    let currentPosition = 0;

    draft.lines.forEach(
        function (line) {

            const lineUnitsRequired =
                parseInt(
                    line.total_units_required
                    || 0
                );

            const itemsForThisLine =
                selectedItems.slice(
                    currentPosition,
                    currentPosition +
                    lineUnitsRequired
                );

            currentPosition +=
                lineUnitsRequired;

            renderTemplateConsumable(
                draft.render,
                draft.consumable,
                line.quantity_to_show,
                line.price,
                0,
                true,
                line.presentation,
                itemsForThisLine
            );
        }
    );

    $modalSelectItemeableItems
        .modal('hide');

    markEquipDirty(
        draft.render
    );

    toastr.success(
        'Se agregaron ' +
        selectedItems.length +
        ' ítems a la cotización.',
        'Producto agregado'
    );

    window.$currentItemeableDraft =
        null;
}


function selectItemByScannedCode() {

    const code =
        (
            $('#itemeable-item-search')
                .val()
            || ''
        )
            .trim();

    if (!code) {
        return;
    }

    const normalizedCode =
        code.toLowerCase();

    const $row =
        $('[data-item-row]')
            .filter(
                function () {

                    const itemCode =
                        String(
                            $(this)
                                .attr(
                                    'data-item-code'
                                )
                            || ''
                        )
                            .trim()
                            .toLowerCase();

                    return (
                        itemCode ===
                        normalizedCode
                    );
                }
            )
            .first();

    if (!$row.length) {

        toastr.warning(
            'No se encontró un ítem disponible con ese código.',
            'Ítem no encontrado'
        );

        return;
    }

    const $checkbox =
        $row.find(
            '.itemeable-item-checkbox'
        );

    if (
        $checkbox.prop('disabled') &&
        !$checkbox.is(':checked')
    ) {

        toastr.warning(
            'Ya alcanzó la cantidad máxima de ítems permitidos.',
            'Límite alcanzado'
        );

        return;
    }

    if (
        !$checkbox.is(':checked')
    ) {

        $checkbox
            .prop(
                'checked',
                true
            )
            .trigger('change');
    }

    $('#itemeable-items-table-body')
        .prepend($row);

    $row.addClass(
        'table-success'
    );

    setTimeout(
        function () {

            $row.removeClass(
                'table-success'
            );
        },
        1200
    );

    $('#itemeable-item-search')
        .val('')
        .focus();
}


/*
|--------------------------------------------------------------------------
| RENDER DE PRODUCTOS
|--------------------------------------------------------------------------
*/

function renderTemplateConsumable(
    render,
    consumable,
    quantity,
    discountOrPrice,
    typePromo,
    isPrice,
    presentation,
    selectedItems
) {

    isPrice =
        typeof isPrice === 'undefined'
            ? false
            : isPrice;

    presentation =
        presentation || null;

    selectedItems =
        selectedItems || [];

    const clone =
        activateTemplate(
            '#template-consumable'
        );

    const $clone =
        $(clone);

    const qtyVisible =
        parseFloat(
            quantity || 0
        );

    const isItemeable =
        parseInt(
            consumable.tipo_venta_id
            || 0
        ) === 3;

    const selectedItemIds =
        Array.isArray(selectedItems)
            ? selectedItems
                .map(
                    function (item) {

                        return parseInt(
                            item.id
                        );
                    }
                )
                .filter(
                    function (id) {
                        return id > 0;
                    }
                )
            : [];

    const selectedItemsText =
        Array.isArray(selectedItems)
            ? selectedItems
                .map(
                    function (item) {

                        return (
                            item.code ||
                            (
                                'Ítem #' +
                                item.id
                            )
                        );
                    }
                )
                .join(', ')
            : '';

    const $description =
        $clone.find(
            '[data-consumableDescription]'
        );

    const $id =
        $clone.find(
            '[data-consumableId]'
        );

    const $unit =
        $clone.find(
            '[data-consumableUnit]'
        );

    const $quantity =
        $clone.find(
            '[data-consumableQuantity]'
        );

    const $valor =
        $clone.find(
            '[data-consumableValor]'
        );

    const $price =
        $clone.find(
            '[data-consumablePrice]'
        );

    const $importe =
        $clone.find(
            '[data-consumableImporte]'
        );

    const $presentationText =
        $clone.find(
            '[data-presentation_text]'
        );


    /*
    |--------------------------------------------------------------------------
    | Datos comunes
    |--------------------------------------------------------------------------
    */

    $description.val(
        consumable.full_description ||
        consumable.display_name ||
        ''
    );

    $id.attr(
        'data-consumableid',
        consumable.id
    );

    $id.attr(
        'data-is_itemeable',
        isItemeable
            ? '1'
            : '0'
    );

    $id.attr(
        'data-selected_item_ids',
        JSON.stringify(
            selectedItemIds
        )
    );

    $id.attr(
        'data-selected_items_text',
        selectedItemsText
    );

    $clone
        .find('[data-descuento]')
        .attr(
            'data-descuento',
            isPrice
                ? '0.00'
                : (
                    parseFloat(
                        discountOrPrice || 0
                    )
                ).toFixed(2)
        );

    $clone
        .find('[data-type_promotion]')
        .attr(
            'data-type_promotion',
            typePromo || null
        );

    const unitName =
        consumable.unit_measure
            ? (
                consumable.unit_measure.name ||
                consumable.unit_measure.description ||
                ''
            )
            : '';

    $unit.val(
        unitName
    );


    /*
    |--------------------------------------------------------------------------
    | Presentación
    |--------------------------------------------------------------------------
    */

    if (presentation) {

        const packs =
            parseInt(
                presentation.packs
                || qtyVisible
                || 0
            );

        const unitsPerPack =
            parseInt(
                presentation.unitsPerPack
                || 0
            );

        const unitsEquivalent =
            parseInt(
                presentation.unitsEquivalent
                || (
                    packs *
                    unitsPerPack
                )
                || 0
            );

        const pricePack =
            parseFloat(
                presentation.pricePack
                || discountOrPrice
                || 0
            );

        const valorUnitario =
            pricePack /
            getFactor($igv);

        const importe =
            pricePack *
            packs;

        $quantity.val(
            packs.toFixed(2)
        );

        $price.val(
            pricePack.toFixed(2)
        );

        $price.attr(
            'data-consumable_price_real',
            pricePack.toFixed(10)
        );

        $valor.val(
            valorUnitario.toFixed(2)
        );

        $valor.attr(
            'data-consumable_valor_real',
            valorUnitario.toFixed(10)
        );

        $importe.val(
            importe.toFixed(2)
        );

        $presentationText.val(
            presentation.text || ''
        );

        $clone
            .find('[data-presentation_id]')
            .attr(
                'data-presentation_id',
                presentation.id
            );

        $clone
            .find('[data-units_per_pack]')
            .attr(
                'data-units_per_pack',
                unitsPerPack
            );

        $clone
            .find('[data-units_equivalent]')
            .attr(
                'data-units_equivalent',
                unitsEquivalent
            );

    } else {

        /*
        |--------------------------------------------------------------------------
        | Unidad normal
        |--------------------------------------------------------------------------
        */

        const unitPrice =
            isPrice
                ? parseFloat(
                discountOrPrice || 0
                )
                : parseFloat(
                consumable.list_price || 0
                );

        const valorUnitario =
            unitPrice /
            getFactor($igv);

        const importe =
            unitPrice *
            qtyVisible;

        $quantity.val(
            qtyVisible.toFixed(2)
        );

        $price.val(
            unitPrice.toFixed(2)
        );

        $price.attr(
            'data-consumable_price_real',
            unitPrice.toFixed(10)
        );

        $valor.val(
            valorUnitario.toFixed(2)
        );

        $valor.attr(
            'data-consumable_valor_real',
            valorUnitario.toFixed(10)
        );

        $importe.val(
            importe.toFixed(2)
        );

        $presentationText.val(
            'Unidad'
        );

        $clone
            .find('[data-presentation_id]')
            .attr(
                'data-presentation_id',
                ''
            );

        $clone
            .find('[data-units_per_pack]')
            .attr(
                'data-units_per_pack',
                ''
            );

        $clone
            .find('[data-units_equivalent]')
            .attr(
                'data-units_equivalent',
                qtyVisible
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Tooltip para itemeables
    |--------------------------------------------------------------------------
    */

    if (
        isItemeable &&
        selectedItemsText !== ''
    ) {

        $description.attr(
            'title',
            'Ítems seleccionados: ' +
            selectedItemsText
        );

        $description.attr(
            'data-toggle',
            'tooltip'
        );

        $description.attr(
            'data-placement',
            'top'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Renderizar
    |--------------------------------------------------------------------------
    */

    render.append(clone);

    markEquipDirty(
        render
    );
}


/*
|--------------------------------------------------------------------------
| ELIMINAR PRODUCTO
|--------------------------------------------------------------------------
*/

function deleteConsumable() {

    markEquipDirty(this);

    $(this)
        .closest('[data-consumableRow]')
        .remove();
}


/*
|--------------------------------------------------------------------------
| CAMBIO DE CANTIDAD DE PRODUCTO
|--------------------------------------------------------------------------
*/

function calculateTotalC(
    input
) {

    const row =
        input.closest(
            '[data-consumableRow]'
        );

    if (!row) {
        return;
    }

    let qty =
        parseFloat(
            input.value || 0
        );

    if (
        isNaN(qty) ||
        qty < 0
    ) {
        qty = 0;
    }

    const priceElement =
        row.querySelector(
            '[data-consumablePrice]'
        );

    const valorElement =
        row.querySelector(
            '[data-consumableValor]'
        );

    const importeElement =
        row.querySelector(
            '[data-consumableImporte]'
        );

    const unitsPerPackElement =
        row.querySelector(
            '[data-units_per_pack]'
        );

    const unitsEquivalentElement =
        row.querySelector(
            '[data-units_equivalent]'
        );

    if (
        !priceElement ||
        !valorElement ||
        !importeElement
    ) {
        return;
    }

    const unitsPerPack =
        unitsPerPackElement
            ? parseFloat(
            unitsPerPackElement
                .getAttribute(
                    'data-units_per_pack'
                )
            || 0
            )
            : 0;


    /*
     * Las presentaciones solo pueden usar
     * una cantidad entera de paquetes.
     */
    if (
        unitsPerPack > 0
    ) {

        qty =
            Math.floor(qty);

        if (qty < 0) {
            qty = 0;
        }

        input.value =
            qty;

        if (
            unitsEquivalentElement
        ) {

            unitsEquivalentElement
                .setAttribute(
                    'data-units_equivalent',
                    qty *
                    unitsPerPack
                );
        }

    } else {

        if (
            unitsEquivalentElement
        ) {

            unitsEquivalentElement
                .setAttribute(
                    'data-units_equivalent',
                    qty
                );
        }
    }

    const price =
        parseFloat(
            priceElement.value
            || 0
        );

    const valorUnitario =
        price /
        getFactor($igv);

    const importe =
        qty *
        price;

    importeElement.value =
        importe.toFixed(2);

    valorElement.value =
        valorUnitario.toFixed(2);

    $(valorElement)
        .attr(
            'data-consumable_valor_real',
            valorUnitario.toFixed(10)
        );

    markEquipDirty(
        input
    );
}


/*
|--------------------------------------------------------------------------
| SERVICIOS ADICIONALES
|--------------------------------------------------------------------------
*/

function addService() {

    const $card =
        $(this)
            .closest('.card-body');

    const description =
        (
            $card
                .find('#material_search')
                .val()
            || ''
        )
            .trim();

    const unitId =
        $card
            .find('.unitMeasure')
            .val();

    const unitText =
        (
            $card
                .find(
                    '.unitMeasure option:selected'
                )
                .text()
            || ''
        )
            .trim();

    const quantity =
        parseFloat(
            $card
                .find('#quantity')
                .val()
            || 0
        );

    if (!description) {

        toastr.error(
            'Debe ingresar una descripción.',
            'Error'
        );

        return;
    }

    if (!unitId) {

        toastr.error(
            'Debe seleccionar una unidad.',
            'Error'
        );

        return;
    }

    if (
        !quantity ||
        quantity <= 0
    ) {

        toastr.error(
            'Debe ingresar una cantidad válida.',
            'Error'
        );

        return;
    }

    let price = 0;

    const $priceInput =
        $card.find('#price');

    if (
        $priceInput.length
    ) {

        price =
            parseFloat(
                $priceInput.val()
                || 0
            );

        if (
            !price ||
            price <= 0
        ) {

            toastr.error(
                'Debe ingresar un precio válido.',
                'Error'
            );

            return;
        }
    }

    const $render =
        $card.find(
            '[data-bodyService]'
        );

    const clone =
        activateTemplate(
            '#template-service'
        );

    clone
        .querySelector(
            '[data-serviceDescription]'
        )
        .value =
        description;

    clone
        .querySelector(
            '[data-serviceId]'
        )
        .value =
        '';

    clone
        .querySelector(
            '[data-serviceUnit]'
        )
        .value =
        unitText;

    clone
        .querySelector(
            '[data-serviceQuantity]'
        )
        .value =
        quantity.toFixed(2);

    clone
        .querySelector(
            '[data-servicePU]'
        )
        .value =
        price.toFixed(2);

    $render.append(
        clone
    );

    const $lastRow =
        $render
            .find(
                '[data-serviceRow]'
            )
            .last();

    const uniqueId =
        'billable_' +
        Date.now() +
        '_' +
        Math.floor(
            Math.random() *
            1000
        );

    $lastRow
        .find(
            '[data-billable-id]'
        )
        .attr(
            'id',
            uniqueId
        );

    $lastRow
        .find(
            '[data-billable-label]'
        )
        .attr(
            'for',
            uniqueId
        );

    $lastRow
        .find(
            '[data-serviceBillable]'
        )
        .prop(
            'checked',
            true
        );

    calculateServiceRow(
        $lastRow
    );

    $card
        .find('#material_search')
        .val('');

    $card
        .find('#quantity')
        .val(0);

    if (
        $priceInput.length
    ) {
        $priceInput.val(0);
    }

    $card
        .find('.unitMeasure')
        .val(null)
        .trigger('change');

    markEquipDirty(
        $card
            .closest('[data-equip]')
    );
}


function calculateServiceRow(
    row
) {

    const $row =
        $(row);

    let quantity =
        parseFloat(
            $row
                .find(
                    '[data-serviceQuantity]'
                )
                .val()
            || 0
        );

    let price =
        parseFloat(
            $row
                .find(
                    '[data-servicePU]'
                )
                .val()
            || 0
        );

    if (
        isNaN(quantity) ||
        quantity < 0
    ) {
        quantity = 0;
    }

    if (
        isNaN(price) ||
        price < 0
    ) {
        price = 0;
    }

    const factor =
        getFactor($igv);

    const valorUnitario =
        factor > 0
            ? price / factor
            : 0;

    const importe =
        quantity *
        price;

    $row
        .find(
            '[data-serviceVU]'
        )
        .val(
            valorUnitario.toFixed(2)
        );

    $row
        .find(
            '[data-serviceImporte]'
        )
        .val(
            importe.toFixed(2)
        );
}


function readServicesFromDom(
    container
) {

    const $services =
        container.find(
            '[data-serviceRow]'
        );

    const array = [];

    let sumAll = 0;
    let sumBillable = 0;

    $services.each(
        function () {

            const $row =
                $(this);

            const description =
                (
                    $row
                        .find(
                            '[data-serviceDescription]'
                        )
                        .val()
                    || ''
                )
                    .trim();

            if (!description) {
                return;
            }

            const unit =
                (
                    $row
                        .find(
                            '[data-serviceUnit]'
                        )
                        .val()
                    || ''
                )
                    .trim();

            const quantity =
                parseFloat(
                    $row
                        .find(
                            '[data-serviceQuantity]'
                        )
                        .val()
                    || 0
                );

            const valor =
                parseFloat(
                    $row
                        .find(
                            '[data-serviceVU]'
                        )
                        .val()
                    || 0
                );

            const price =
                parseFloat(
                    $row
                        .find(
                            '[data-servicePU]'
                        )
                        .val()
                    || 0
                );

            const importe =
                parseFloat(
                    $row
                        .find(
                            '[data-serviceImporte]'
                        )
                        .val()
                    || 0
                );

            const billable =
                $row
                    .find(
                        '[data-serviceBillable]'
                    )
                    .is(':checked')
                    ? 1
                    : 0;

            array.push({
                description:
                description,

                unit:
                unit,

                quantity:
                quantity,

                valor:
                valor,

                price:
                price,

                importe:
                importe,

                billable:
                billable
            });

            sumAll =
                round10(
                    sumAll +
                    importe
                );

            if (
                billable === 1
            ) {

                sumBillable =
                    round10(
                        sumBillable +
                        importe
                    );
            }
        }
    );

    return {
        array:
        array,

        sum_all:
            round10(
                sumAll
            ),

        sum_billable:
            round10(
                sumBillable
            )
    };
}


/*
|--------------------------------------------------------------------------
| LEER PRODUCTOS DEL DOM
|--------------------------------------------------------------------------
*/

function readConsumablesFromDom(
    $card
) {

    const $container =
        $card
            .find(
                '[data-bodyConsumable]'
            )
            .first();

    const consumables = [];

    let promotionDiscount = 0;

    let hasValidationError =
        false;

    $container
        .find(
            '[data-consumableRow]'
        )
        .each(
            function () {

                const $row =
                    $(this);

                const $id =
                    $row
                        .find(
                            '[data-consumableId]'
                        )
                        .first();

                const stockItemId =
                    parseInt(
                        $id.attr(
                            'data-consumableid'
                        )
                        || 0
                    );

                if (!stockItemId) {

                    toastr.error(
                        'Se encontró un producto sin StockItem válido.',
                        'Error'
                    );

                    hasValidationError =
                        true;

                    return false;
                }

                const quantity =
                    parseFloat(
                        $row
                            .find(
                                '[data-consumableQuantity]'
                            )
                            .val()
                        || 0
                    );

                const unitsEquivalent =
                    parseFloat(
                        $row
                            .find(
                                '[data-units_equivalent]'
                            )
                            .attr(
                                'data-units_equivalent'
                            )
                        || quantity
                        || 0
                    );

                const discount =
                    parseFloat(
                        $row
                            .find(
                                '[data-descuento]'
                            )
                            .attr(
                                'data-descuento'
                            )
                        || 0
                    );

                promotionDiscount +=
                    discount;

                const isItemeable =
                    parseInt(
                        $id.attr(
                            'data-is_itemeable'
                        )
                        || 0
                    ) === 1;

                let selectedItemIds =
                    [];

                const rawSelectedItems =
                    $id.attr(
                        'data-selected_item_ids'
                    )
                    || '[]';

                try {

                    selectedItemIds =
                        JSON.parse(
                            rawSelectedItems
                        );

                    if (
                        !Array.isArray(
                            selectedItemIds
                        )
                    ) {
                        selectedItemIds = [];
                    }

                    selectedItemIds =
                        selectedItemIds
                            .map(
                                function (
                                    itemId
                                ) {

                                    return parseInt(
                                        itemId
                                    );
                                }
                            )
                            .filter(
                                function (
                                    itemId
                                ) {

                                    return (
                                        itemId > 0
                                    );
                                }
                            );

                } catch (error) {

                    selectedItemIds = [];
                }

                if (isItemeable) {

                    if (
                        !Number.isInteger(
                            unitsEquivalent
                        )
                    ) {

                        toastr.error(
                            'El producto "' +
                            (
                                $row
                                    .find(
                                        '[data-consumableDescription]'
                                    )
                                    .val()
                                || ''
                            ) +
                            '" debe utilizar una cantidad entera.',
                            'Cantidad inválida'
                        );

                        hasValidationError =
                            true;

                        return false;
                    }

                    if (
                        selectedItemIds.length !==
                        unitsEquivalent
                    ) {

                        toastr.error(
                            'La cantidad de ítems seleccionados no coincide con el producto: ' +
                            (
                                $row
                                    .find(
                                        '[data-consumableDescription]'
                                    )
                                    .val()
                                || ''
                            ),
                            'Validación de ítems'
                        );

                        hasValidationError =
                            true;

                        return false;
                    }
                }

                consumables.push({
                    id:
                    stockItemId,

                    description:
                        $row
                            .find(
                                '[data-consumableDescription]'
                            )
                            .val()
                        || '',

                    unit:
                        $row
                            .find(
                                '[data-consumableUnit]'
                            )
                            .val()
                        || '',

                    /*
                     * Cantidad visible:
                     * - unidades
                     * - o cantidad de packs
                     */
                    quantity:
                    quantity,

                    /*
                     * Cantidad que realmente
                     * afecta inventario.
                     */
                    units_equivalent:
                    unitsEquivalent,

                    valor:
                        $row
                            .find(
                                '[data-consumableValor]'
                            )
                            .val()
                        || 0,

                    valorReal:
                        $row
                            .find(
                                '[data-consumableValor]'
                            )
                            .attr(
                                'data-consumable_valor_real'
                            )
                        ??
                        $row
                            .find(
                                '[data-consumableValor]'
                            )
                            .val()
                        ??
                        0,

                    price:
                        $row
                            .find(
                                '[data-consumablePrice]'
                            )
                            .val()
                        || 0,

                    priceReal:
                        $row
                            .find(
                                '[data-consumablePrice]'
                            )
                            .attr(
                                'data-consumable_price_real'
                            )
                        ??
                        $row
                            .find(
                                '[data-consumablePrice]'
                            )
                            .val()
                        ??
                        0,

                    importe:
                        $row
                            .find(
                                '[data-consumableImporte]'
                            )
                            .val()
                        || 0,

                    discount:
                    discount,

                    type_promo:
                        $row
                            .find(
                                '[data-type_promotion]'
                            )
                            .attr(
                                'data-type_promotion'
                            )
                        || null,

                    presentation_id:
                        $row
                            .find(
                                '[data-presentation_id]'
                            )
                            .attr(
                                'data-presentation_id'
                            )
                        || null,

                    units_per_pack:
                        $row
                            .find(
                                '[data-units_per_pack]'
                            )
                            .attr(
                                'data-units_per_pack'
                            )
                        || null,

                    is_itemeable:
                    isItemeable,

                    selected_item_ids:
                    selectedItemIds
                });
            }
        );

    return {
        error:
        hasValidationError,

        consumables:
        consumables,

        promotion_discount:
        promotionDiscount
    };
}


/*
|--------------------------------------------------------------------------
| CALCULAR COTIZACIÓN
|--------------------------------------------------------------------------
*/

function calculateQuoteTotals(
    consumables,
    servicesBillableTotal,
    promotionDiscount
) {

    const igvPct =
        parseFloat(
            $igv || 18
        );

    const factor =
        getFactor(
            igvPct
        );

    let productsWithIgv =
        0;

    consumables.forEach(
        function (item) {

            const quantity =
                Number(
                    item.quantity || 0
                );

            const price =
                Number(
                    item.priceReal
                    ??
                    item.price
                    ??
                    0
                );

            const lineTotal =
                round10(
                    quantity *
                    price
                );

            productsWithIgv =
                round10(
                    productsWithIgv +
                    lineTotal
                );
        }
    );

    productsWithIgv =
        round10(
            productsWithIgv -
            (
                Number(
                    promotionDiscount
                )
                || 0
            )
        );

    if (
        productsWithIgv < 0
    ) {
        productsWithIgv = 0;
    }

    const servicesWithIgv =
        round10(
            Number(
                servicesBillableTotal
            )
            || 0
        );

    const subtotalWithIgv =
        round10(
            productsWithIgv +
            servicesWithIgv
        );

    const discountWithIgv =
        round10(
            computeDiscountWithIgv(
                subtotalWithIgv,
                igvPct
            )
        );

    let totalWithIgv =
        round10(
            subtotalWithIgv -
            discountWithIgv
        );

    if (
        totalWithIgv < 0
    ) {
        totalWithIgv = 0;
    }

    let base =
        round10(
            totalWithIgv /
            factor
        );

    if (base < 0) {
        base = 0;
    }

    const igv =
        round10(
            totalWithIgv -
            base
        );

    const discountBase =
        round10(
            discountWithIgv /
            factor
        );

    return {
        subtotal_with_igv:
        subtotalWithIgv,

        discount_with_igv:
        discountWithIgv,

        discount_base:
        discountBase,

        base:
        base,

        igv:
        igv,

        total:
        totalWithIgv,

        igv_pct:
        igvPct,

        factor:
        factor
    };
}


function computeDiscountWithIgv(
    subtotalWithIgv,
    igvPct
) {

    const $discount =
        $('#discountSection');

    const type =
        $discount.attr(
            'data-discount_type'
        )
        || 'amount';

    const mode =
        $discount.attr(
            'data-discount_input_mode'
        )
        || 'without_igv';

    const value =
        parseFloat(
            $discount.attr(
                'data-discount_value'
            )
            || 0
        );

    if (
        !value ||
        value <= 0
    ) {
        return 0;
    }

    const factor =
        getFactor(
            igvPct
        );

    let discountWithIgv =
        0;

    if (
        type === 'amount'
    ) {

        discountWithIgv =
            mode === 'with_igv'
                ? value
                : value * factor;

    } else {

        const percentage =
            value / 100;

        if (
            mode === 'with_igv'
        ) {

            discountWithIgv =
                subtotalWithIgv *
                percentage;

        } else {

            const base =
                subtotalWithIgv /
                factor;

            discountWithIgv =
                (
                    base *
                    percentage
                ) *
                factor;
        }
    }

    discountWithIgv =
        round10(
            discountWithIgv
        );

    if (
        discountWithIgv >
        subtotalWithIgv
    ) {

        discountWithIgv =
            subtotalWithIgv;
    }

    return (
        discountWithIgv
    );
}


function updateQuoteSummary(
    totals
) {

    $('#descuento')
        .html(
            moneyRound(
                totals.discount_base
            ).toFixed(2)
        )
        .attr(
            'data-descuento_real',
            totals.discount_base
        );

    $('#gravada')
        .html(
            moneyRound(
                totals.base
            ).toFixed(2)
        )
        .attr(
            'data-gravada_real',
            totals.base
        );

    $('#igv_total')
        .html(
            moneyRound(
                totals.igv
            ).toFixed(2)
        )
        .attr(
            'data-igv_total_real',
            totals.igv
        );

    $('#total_importe')
        .html(
            moneyRound(
                totals.total
            ).toFixed(2)
        )
        .attr(
            'data-total_importe_real',
            totals.total
        );
}


/*
|--------------------------------------------------------------------------
| CONFIRMAR EQUIPMENT
|--------------------------------------------------------------------------
*/

function confirmEquipment() {

    const $button =
        $(this);

    $.confirm({
        icon: 'fas fa-smile',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'green',
        title: 'Confirmar productos',
        content:
            'Debe confirmar para almacenar los productos en memoria.',

        buttons: {

            confirm: {
                text: 'CONFIRMAR',

                action: function () {

                    const $card =
                        $button.closest(
                            '[data-equip]'
                        );

                    const result =
                        buildEquipmentFromCard(
                            $card
                        );

                    if (!result) {
                        return false;
                    }

                    /*
                     * La pantalla actual maneja
                     * exactamente un Equipment.
                     */
                    $equipments = [
                        result
                    ];

                    $card
                        .find(
                            '[data-confirm]'
                        )
                        .hide();

                    $card
                        .find(
                            '[data-saveEquipment]'
                        )
                        .attr(
                            'data-saveEquipment',
                            0
                        )
                        .show();

                    markEquipClean(
                        $card
                    );

                    $.alert(
                        'Productos confirmados!'
                    );
                }
            },

            cancel: {
                text: 'CANCELAR',

                action: function () {

                    $.alert(
                        'Confirmación cancelada.'
                    );
                }
            }
        }
    });
}


/*
|--------------------------------------------------------------------------
| GUARDAR CAMBIOS DEL EQUIPMENT
|--------------------------------------------------------------------------
*/

function saveEquipment() {

    const $button =
        $(this);

    $.confirm({
        icon: 'fas fa-smile',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'orange',
        title: 'Guardar cambios',
        content:
            '¿Está seguro de guardar los cambios en los productos?',

        buttons: {

            confirm: {
                text: 'CONFIRMAR',

                action: function () {

                    const $card =
                        $button.closest(
                            '[data-equip]'
                        );

                    const result =
                        buildEquipmentFromCard(
                            $card
                        );

                    if (!result) {
                        return false;
                    }

                    /*
                     * Regla actual:
                     * Quote posee un único Equipment.
                     */
                    $equipments = [
                        result
                    ];

                    $button.attr(
                        'data-saveEquipment',
                        0
                    );

                    markEquipClean(
                        $card
                    );

                    $.alert(
                        'Productos guardados!'
                    );
                }
            },

            cancel: {
                text: 'CANCELAR',

                action: function () {

                    $.alert(
                        'Modificación cancelada.'
                    );
                }
            }
        }
    });
}


/*
|--------------------------------------------------------------------------
| CONSTRUIR EQUIPMENT DESDE EL DOM
|--------------------------------------------------------------------------
*/

function buildEquipmentFromCard(
    $card
) {

    const consumableRead =
        readConsumablesFromDom(
            $card
        );

    if (
        consumableRead.error
    ) {
        return null;
    }

    const consumables =
        consumableRead
            .consumables;

    if (
        consumables.length === 0
    ) {

        toastr.error(
            'La cotización debe contener al menos un producto.',
            'Error'
        );

        return null;
    }

    const servicesContainer =
        $card
            .find(
                '[data-bodyService]'
            )
            .first();

    let servicesRead = {
        array: [],
        sum_all: 0,
        sum_billable: 0
    };

    if (
        servicesContainer.length
    ) {

        servicesRead =
            readServicesFromDom(
                servicesContainer
            );
    }

    const totals =
        calculateQuoteTotals(
            consumables,
            servicesRead.sum_billable,
            consumableRead
                .promotion_discount
        );

    updateQuoteSummary(
        totals
    );

    const detail =
        $card
            .find(
                '[data-detailequipment]'
            )
            .val()
        || '';

    return {
        id:
            0,

        quantity:
            1,

        total:
        totals.total,

        description:
            '',

        detail:
        detail,

        consumables:
        consumables,

        workforces:
        servicesRead.array,

        discount_global: {
            base:
            totals.discount_base,

            meta: {
                subtotal_with_igv:
                totals.subtotal_with_igv,

                discount_with_igv:
                totals.discount_with_igv,

                discount_base:
                totals.discount_base,

                igv_pct:
                totals.igv_pct,

                factor:
                totals.factor
            }
        }
    };
}


/*
|--------------------------------------------------------------------------
| CONTROL DE CAMBIOS
|--------------------------------------------------------------------------
*/

function getEquipCardFromElement(
    element
) {

    return $(element)
        .closest(
            '[data-equip]'
        );
}


function markEquipDirty(
    elementOrCard
) {

    const $card =
        elementOrCard instanceof jQuery
            ? (
                elementOrCard.is(
                    '[data-equip]'
                )
                    ? elementOrCard
                    : elementOrCard.closest(
                    '[data-equip]'
                    )
            )
            : getEquipCardFromElement(
            elementOrCard
            );

    if (
        !$card.length
    ) {
        return;
    }

    $card.attr(
        'data-dirty',
        '1'
    );

    $card
        .removeClass(
            'card-success'
        )
        .addClass(
            'card-gray-dark'
        );
}


function markEquipClean(
    elementOrCard
) {

    const $card =
        elementOrCard instanceof jQuery
            ? (
                elementOrCard.is(
                    '[data-equip]'
                )
                    ? elementOrCard
                    : elementOrCard.closest(
                    '[data-equip]'
                    )
            )
            : getEquipCardFromElement(
            elementOrCard
            );

    if (
        !$card.length
    ) {
        return;
    }

    $card.attr(
        'data-dirty',
        '0'
    );

    $card
        .removeClass(
            'card-gray-dark'
        )
        .addClass(
            'card-success'
        );
}


function editedActive() {

    let dirty =
        false;

    $('[data-equip]')
        .each(
            function () {

                if (
                    $(this).attr(
                        'data-dirty'
                    ) === '1' ||
                    $(this).hasClass(
                        'card-gray-dark'
                    )
                ) {

                    dirty = true;

                    return false;
                }
            }
        );

    return dirty;
}


/*
|--------------------------------------------------------------------------
| GUARDAR COTIZACIÓN
|--------------------------------------------------------------------------
*/

function storeQuote(
    event
) {

    event.preventDefault();

    const $button =
        $('#btn-submit');

    $button.prop(
        'disabled',
        true
    );


    /*
    |--------------------------------------------------------------------------
    | Validar cambios pendientes
    |--------------------------------------------------------------------------
    */

    if (
        editedActive()
    ) {

        toastr.error(
            'No se puede guardar porque hay productos no confirmados.',
            'Error'
        );

        $button.prop(
            'disabled',
            false
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Regla actual: exactamente un Equipment
    |--------------------------------------------------------------------------
    */

    if (
        $equipments.length !== 1
    ) {

        toastr.error(
            'Debe confirmar la cotización antes de guardarla.',
            'Error'
        );

        $button.prop(
            'disabled',
            false
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | FormData
    |--------------------------------------------------------------------------
    */

    const createUrl =
        $formCreate.data(
            'url'
        );

    const formulario =
        $('#formCreate')[0];

    const form =
        new FormData(
            formulario
        );

    form.append(
        'equipments',
        JSON.stringify(
            $equipments
        )
    );


    /*
    |--------------------------------------------------------------------------
    | Totales reales
    |--------------------------------------------------------------------------
    */

    const descuentoReal =
        $('#descuento')
            .attr(
                'data-descuento_real'
            )
        || '0';

    const gravadaReal =
        $('#gravada')
            .attr(
                'data-gravada_real'
            )
        || '0';

    const igvReal =
        $('#igv_total')
            .attr(
                'data-igv_total_real'
            )
        || '0';

    const totalReal =
        $('#total_importe')
            .attr(
                'data-total_importe_real'
            )
        || '0';

    form.append(
        'descuentoReal',
        descuentoReal
    );

    form.append(
        'gravadaReal',
        gravadaReal
    );

    form.append(
        'igvReal',
        igvReal
    );

    form.append(
        'totalReal',
        totalReal
    );


    /*
    |--------------------------------------------------------------------------
    | Información del descuento
    |--------------------------------------------------------------------------
    */

    const $discount =
        $('#discountSection');

    form.set(
        'discount_type',
        $discount.attr(
            'data-discount_type'
        )
        || 'amount'
    );

    form.set(
        'discount_input_mode',
        $discount.attr(
            'data-discount_input_mode'
        )
        || 'without_igv'
    );

    form.set(
        'discount_input_value',
        $discount.attr(
            'data-discount_value'
        )
        || '0'
    );


    /*
    |--------------------------------------------------------------------------
    | AJAX
    |--------------------------------------------------------------------------
    */

    $.ajax({
        url:
        createUrl,

        method:
            'POST',

        data:
        form,

        processData:
            false,

        contentType:
            false,

        success: function (
            data
        ) {

            toastr.success(
                data.message,
                'Éxito'
            );

            setTimeout(
                function () {

                    $button.prop(
                        'disabled',
                        false
                    );

                    location.reload();
                },
                1500
            );
        },

        error: function (
            xhr
        ) {

            const response =
                xhr.responseJSON
                || {};

            if (
                response.message
            ) {

                toastr.error(
                    response.message,
                    'Error'
                );
            }

            if (
                response.errors
            ) {

                Object.keys(
                    response.errors
                )
                    .forEach(
                        function (property) {

                            const error =
                                response.errors[
                                    property
                                    ];

                            if (
                                Array.isArray(
                                    error
                                )
                            ) {

                                error.forEach(
                                    function (
                                        message
                                    ) {

                                        toastr.error(
                                            message,
                                            'Error'
                                        );
                                    }
                                );

                            } else {

                                toastr.error(
                                    error,
                                    'Error'
                                );
                            }
                        }
                    );
            }

            $button.prop(
                'disabled',
                false
            );
        }
    });
}


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function activateTemplate(
    id
) {

    const template =
        document.querySelector(
            id
        );

    return document.importNode(
        template.content,
        true
    );
}


function getFactor(
    igvPct
) {

    return (
        1 +
        (
            (
                Number(
                    igvPct
                )
                || 0
            )
            /
            100
        )
    );
}


function round10(
    number
) {

    return (
        Math.round(
            (
                Number(
                    number
                )
                || 0
            )
            *
            1e10
        )
        /
        1e10
    );
}


function moneyRound(
    number
) {

    return (
        Math.round(
            (
                Number(
                    number
                )
                || 0
            )
            *
            100
        )
        /
        100
    );
}


function mayus(
    element
) {

    element.value =
        (
            element.value
            || ''
        )
            .toUpperCase();
}


/*
|--------------------------------------------------------------------------
| ESCAPAR HTML
|--------------------------------------------------------------------------
|
| El endpoint devuelve información de códigos, lotes y almacenes.
| No insertamos esa información directamente dentro del HTML sin escapar.
|
*/

function escapeHtml(
    value
) {

    return String(
        value == null
            ? ''
            : value
    )
        .replace(
            /&/g,
            '&amp;'
        )
        .replace(
            /</g,
            '&lt;'
        )
        .replace(
            />/g,
            '&gt;'
        )
        .replace(
            /"/g,
            '&quot;'
        )
        .replace(
            /'/g,
            '&#039;'
        );
}