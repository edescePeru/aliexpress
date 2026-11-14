$(document).ready(function () {

    $(document).on('input', '#dni, #ruc', function () {
        this.value = this.value.replace(/\D/g, '');
    });

    setTimeout(function () {
        $('#product_search').focus();
    }, 300);

    $('#product_search').on('keypress', function (e) {
        if (e.which === 13) { // Enter
            e.preventDefault();
            $('#btn_search').click();
        }
    });

    $('#quantity_total').on('keypress', function (e) {
        if (e.which === 13) { // Enter
            e.preventDefault();
            addProduct();
        }
    });

    $('#modalVuelto').on('hidden.bs.modal', function () {
        $('#btn-pay').prop('disabled', false);
    });

    // Detectar cambio en el select de tipo de vista
    $("#type_id").on("change", function() {
        if ($(this).val() === "f") {
            showDataSearchTable();
        } else {
            showDataSearch();
        }
    });

    $(document).on('click', '[data-item]', showData);

    $(document).on('click', '.pagination-btn', function() {
        var page = $(this).data('page');
        getDataTableNew(page);
    });

    getData(1);

    bloquearDatosComprobante();

    $("#btn_search").on('click', showDataSearch);

    $(document).on('click', '[data-add_cart]', addProductCart);

    $(document).on('click', '[data-add_cart_special]', addProductCartSpecial);

    $(document).on('input', '#importe_total', function() {
        var $input = $(this);
        var currentValue = parseFloat($input.val());
        var importe = $("#monto_total").val();

        if ( currentValue >= importe)
        {
            $("#vuelto").val(parseFloat(currentValue-importe).toFixed(2));
        } else {
            $("#vuelto").val(parseFloat(0).toFixed(2));
        }

    });

    $(document).on('click', '[data-delete]', deleteItem);

    $("#btn-pay").on('click', payNow);

    $("#btn-save").on('click', guardarVenta);

    $("#btn-notSave").on('click', cerrarVuelto);

    $formCreate = $('#formCreate');

    $modalVuelto = $('#modalVuelto');

    $modalQuantity = $('#modalQuantity');

    $(document).on('input', 'input.quantity', function() {
        console.log("Input event detected!"); // Para depuración
        var $input = $(this);
        var currentValue = parseFloat($input.val());
        var stringDiscount = "";
        var itemKey = $input.siblings('button.minus').attr('data-item_key_minus');

        if (isNaN(currentValue) || currentValue < 0) {
            currentValue = 0;
            $input.val(currentValue.toFixed(2));
        }

        getDiscountMaterial(itemKey, currentValue.toFixed(2)).then(function(discount) {
            console.log(discount);
            if ( discount != -1 )
            {
                $input.closest('.flex-grow-1').find('h6[data-discount]').html(discount.stringDiscount);
            } else  {
                $input.closest('.flex-grow-1').find('h6[data-discount]').html("");
            }

            updateItems(itemKey, priceTotal, currentValue);
            updateTotalOrder();
        });

        var string = changeStringPrice(itemKey, currentValue.toFixed(2));
        var priceTotal = changePriceTotal(itemKey, currentValue.toFixed(2));

        // Actualizar el string
        $input.closest('.flex-grow-1').find('h6[data-price]').html(string);
        // Actualizar el precio total
        $input.closest('.d-flex').find('p[data-priceTotal]').html(priceTotal);
    });

    $("#btn-pay").show();
    $("#btn-newSale").hide();
    $("#btn-printDocument").hide();

    $("#btn-newSale").on('click', newSale);

    $("#btn-notAddProduct").on('click', notAddProduct);
    $("#btn-add_product").on('click', addProduct);

    $('input[name="invoice_type"]').on('change', function() {
        let tipo = $(this).val();

        $('#datos_boleta input, #datos_factura input').val('');

        if (tipo === 'boleta') {
            $('#datos_boleta').removeClass('d-none');
            $('#datos_factura').addClass('d-none');
        } else if (tipo === 'factura') {
            $('#datos_factura').removeClass('d-none');
            $('#datos_boleta').addClass('d-none');
        } else {
            // Ninguno seleccionado
            $('#datos_boleta').addClass('d-none');
            $('#datos_factura').addClass('d-none');
        }

        bloquearDatosComprobante();

    });

    $('#pv_cash_box_id').on('change', function () {
        const $opt = $(this).find('option:selected');
        const type = $opt.data('type'); // cash|bank
        const usesSub = String($opt.data('uses_subtypes')) === '1';

        if (type === 'bank' && usesSub) {
            $('#wrap_pv_subtype').show();
            $('#pv_cash_box_subtype_id').val('').trigger('change');
        } else {
            $('#wrap_pv_subtype').hide();
            $('#pv_cash_box_subtype_id').val('').trigger('change');
            $('#pv_subtype_hint').hide();
        }
    });

    $('#pv_cash_box_subtype_id').on('change', function () {
        const $opt = $(this).find('option:selected');
        const isDeferred = String($opt.data('is_deferred')) === '1';
        if (isDeferred) $('#pv_subtype_hint').show();
        else $('#pv_subtype_hint').hide();
    });

    $(document).on('change', '#pv_vuelto_cash_box_id', function () {
        refreshVueltoSubtypeUI();
    });

    $(document).on('keydown', '#dni, #ruc', function (e) {
        if (e.key !== 'Enter') {
            return;
        }

        e.preventDefault();

        let input = $(this);
        let documento = input.val().replace(/\D/g, '');
        let tipo = input.attr('id');

        if (tipo === 'dni' && documento.length !== 8) {
            toastr.error('El DNI debe tener exactamente 8 dígitos.', 'Documento inválido');
            return;
        }

        if (tipo === 'ruc' && documento.length !== 11) {
            toastr.error('El RUC debe tener exactamente 11 dígitos.', 'Documento inválido');
            return;
        }

        consultarClientePorDocumento(documento, tipo);
    });

    $(document).on('click', '.btnConsultarCliente', function (e) {
        let selectorInput = $(this).data('input');
        let input = $(selectorInput);

        e.preventDefault();

        let documento = input.val().replace(/\D/g, '');
        let tipo = input.attr('id');

        if (tipo === 'dni' && documento.length !== 8) {
            toastr.error('El DNI debe tener exactamente 8 dígitos.', 'Documento inválido');
            return;
        }

        if (tipo === 'ruc' && documento.length !== 11) {
            toastr.error('El RUC debe tener exactamente 11 dígitos.', 'Documento inválido');
            return;
        }

        consultarClientePorDocumento(documento, tipo);
    });

    $('#pagos_parciales_venta').on('switchChange.bootstrapSwitch', function (event, state) {
        if (state) {
            limpiarDatosPagoNormal();

            $('#wrap_pago_normal').hide();
            $('#wrap_comprobante').hide();
        } else {
            $('#wrap_pago_normal').show();
            $('#wrap_comprobante').show();
        }
    });

    $(document).on('change', '.itemeable-item-checkbox', function () {
        const requiredCount = parseInt(
            $('#btn-confirm-itemeable-items').data('required-count') || 0
        );

        updateItemeableItemsCounterForCart(requiredCount);
    });

    $(document).on('keydown', '#itemeable-item-search', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            selectItemByScannedCodeForCart();
        }
    });

    /*
     * Algunos lectores envían Enter; otros provocan change
     * al terminar el escaneo.
     */
    $(document).on('change', '#itemeable-item-search', function () {
        selectItemByScannedCodeForCart();
    });

    $('#btn-confirm-itemeable-items').on('click', function () {
        const draft = $currentItemeableCartDraft;

        if (!draft) {
            toastr.error('No se encontró el producto que se iba a agregar.', 'Error');
            return;
        }

        const requiredCount = parseInt(draft.unitsEquivalent || 0);

        const selectedItems = [];

        $('#itemeable-items-table-body')
            .find('.itemeable-item-checkbox:checked')
            .each(function () {
                selectedItems.push({
                    id: parseInt($(this).attr('data-item-id')),
                    code: $(this).attr('data-item-code')
                });
            });

        if (selectedItems.length !== requiredCount) {
            toastr.error(
                'Debe seleccionar exactamente ' + requiredCount + ' ítem(s).',
                'Selección incompleta'
            );
            return;
        }

        /*
         * Add Special:
         * reparte los Items elegidos según las líneas construidas
         * en addProduct().
         */
        if (draft.fromSpecialModal === true) {
            let currentPosition = 0;
            let rowsAdded = 0;
            let duplicateError = false;

            draft.lines.forEach(function (line) {
                const unitsForLine = parseInt(line.unitsEquivalent || 0);

                const selectedItemsForLine = selectedItems.slice(
                    currentPosition,
                    currentPosition + unitsForLine
                );

                currentPosition += unitsForLine;

                const added = addProductToCart({
                    productId: line.productId,
                    materialId: line.materialId,
                    productPrice: line.productPrice,
                    productName: line.productName,
                    productUnit: line.productUnit,
                    productTax: line.productTax,
                    productType: line.productType,

                    presentationId: line.presentationId,
                    presentationQty: line.presentationQty,
                    productQuantity: line.productQuantity,
                    presentationLabel: line.presentationLabel,

                    unitsEquivalent: line.unitsEquivalent,

                    selectedItems: selectedItemsForLine
                });

                if (added) {
                    rowsAdded++;
                } else {
                    duplicateError = true;
                }
            });

            /*
             * Si una línea ya existía con exactamente los mismos Items,
             * no cerramos el modal para evitar que el usuario crea
             * que se agregó todo correctamente.
             */
            if (duplicateError) {
                toastr.warning(
                    'Uno o más ítems seleccionados ya existen en el carrito.',
                    'Producto duplicado'
                );
                return;
            }

            $('#modalSelectItemeableItems').modal('hide');

            $currentItemeableCartDraft = null;

            toastr.success(
                'Se agregaron ' + rowsAdded + ' detalle(s) itemeable(s) al carrito.',
                'Producto agregado'
            );

            return;
        }

        /*
         * Add to cart directo:
         * una sola línea de una unidad.
         */
        const added = addProductToCart({
            productId: draft.productId,
            materialId: draft.materialId,
            productPrice: draft.productPrice,
            productName: draft.productName,
            productUnit: draft.productUnit,
            productTax: draft.productTax,
            productType: draft.productType,

            presentationId: draft.presentationId,
            presentationQty: draft.presentationQty,
            productQuantity: draft.productQuantity,
            presentationLabel: draft.presentationLabel,

            unitsEquivalent: draft.unitsEquivalent,

            selectedItems: selectedItems
        });

        if (!added) {
            return;
        }

        $('#modalSelectItemeableItems').modal('hide');

        $currentItemeableCartDraft = null;

        toastr.success(
            'Ítem agregado correctamente al carrito.',
            'Producto agregado'
        );
    });

    $('#btn-cancel-itemeable-items').on('click', function () {
        $currentItemeableCartDraft = null;
        $('#modalSelectItemeableItems').modal('hide');
    });
});

let $items = [];
let $subtotal = 0;
let $taxes = 0;
let $total = 0;
let $formCreate;
let $modalVuelto;

let $fin_total_exonerada = 0;
let $fin_total_igv = 0;
let $fin_total_gravada = 0;
let $fin_total_descuentos = 0;
let $fin_total_importe = 0;
let $fin_vuelto = 0;
let $type_vuelto;

let $modeEdit = 1;
let $sale_id = null;
let $modalQuantity;

let $presentationsCache = {}; // materialId -> array presentations

var $modalSelectItemeableItems = $('#modalSelectItemeableItems');
var $currentItemeableCartDraft = null;

function selectItemByScannedCodeForCart() {
    const code = ($('#itemeable-item-search').val() || '').trim();

    if (!code) {
        return;
    }

    const normalizedCode = code.toLowerCase();

    const $row = $('#itemeable-items-table-body')
        .find('[data-item-row]')
        .filter(function () {
            return String($(this).attr('data-item-code') || '')
                .trim()
                .toLowerCase() === normalizedCode;
        })
        .first();

    if (!$row.length) {
        toastr.warning(
            'No se encontró un ítem disponible con ese código.',
            'Ítem no encontrado'
        );
        return;
    }

    const $checkbox = $row.find('.itemeable-item-checkbox');

    if ($checkbox.prop('disabled') && !$checkbox.is(':checked')) {
        toastr.warning(
            'Ya alcanzó la cantidad máxima permitida.',
            'Límite alcanzado'
        );
        return;
    }

    if (!$checkbox.is(':checked')) {
        $checkbox.prop('checked', true).trigger('change');
    }

    // Mueve el ítem identificado al inicio de la lista.
    $('#itemeable-items-table-body').prepend($row);

    $row.addClass('table-success');

    setTimeout(function () {
        $row.removeClass('table-success');
    }, 1200);

    $('#itemeable-item-search')
        .val('')
        .focus();
}

function openItemeableItemsSelectorForCart(draft) {
    if (!draft || !draft.productId) {
        toastr.error('No se pudo identificar el producto itemeable.', 'Error');
        return;
    }

    const requiredCount = parseInt(draft.unitsEquivalent || 0);

    if (!Number.isInteger(requiredCount) || requiredCount <= 0) {
        toastr.error('La cantidad requerida de ítems no es válida.', 'Error');
        return;
    }

    $('#itemeable-product-name').text(draft.productName || '');
    $('#itemeable-required-count').text(requiredCount);
    $('#itemeable-selected-count').text(0);
    $('#itemeable-selected-required-count').text(requiredCount);

    $('#itemeable-item-search').val('');
    $('#itemeable-items-table-body').empty();

    $('#itemeable-items-loading').show();
    $('#itemeable-items-empty').hide();
    $('#itemeable-items-error').hide();
    $('#itemeable-items-table-container').hide();

    $('#btn-confirm-itemeable-items')
        .prop('disabled', true)
        .data('required-count', requiredCount);

    const $itemeableModal = $('#modalSelectItemeableItems');

    if (!$itemeableModal.length) {
        toastr.error(
            'No se encontró el modal de selección de ítems en la vista de punto de venta.',
            'Error'
        );
        return;
    }

    $itemeableModal.modal({
        backdrop: 'static',
        keyboard: false
    });

    $itemeableModal.modal('show');

    const availableItemsUrl = window.APP_POS
        && window.APP_POS.URLS
        && window.APP_POS.URLS.AVAILABLE_ITEMS;

    if (!availableItemsUrl) {
        toastr.error(
            'No se configuró la URL para consultar los ítems disponibles.',
            'Error'
        );
        return;
    }

    const url = availableItemsUrl.replace(':stockItemId', draft.productId);

    $.ajax({
        url: url,
        method: 'GET',
        success: function (response) {
            $('#itemeable-items-loading').hide();

            if (!response.success) {
                $('#itemeable-items-error').show();
                return;
            }

            const items = response.items || [];

            if (!items.length) {
                $('#itemeable-items-empty').show();
                return;
            }

            renderItemeableItemsForCart(items, requiredCount);

            $('#itemeable-items-table-container').show();

            $('#itemeable-item-search')
                .val('')
                .focus();
        },
        error: function () {
            $('#itemeable-items-loading').hide();
            $('#itemeable-items-error').show();
        }
    });
}

function renderItemeableItemsForCart(items, requiredCount) {
    let html = '';

    items.forEach(function (item) {
        const itemCode = item.code || ('Ítem #' + item.id);

        const lotText = item.stock_lot_code
            || item.lot_code
            || item.stock_lot_id
            || '-';

        const locationText = item.warehouse_name
            || item.location
            || '-';

        html += `
            <tr data-item-row data-item-id="${item.id}" data-item-code="${itemCode}">
                <td class="text-center">
                    <input
                        type="checkbox"
                        class="itemeable-item-checkbox"
                        value="${item.id}"
                        data-item-id="${item.id}"
                        data-item-code="${itemCode}"
                    >
                </td>
                <td>${itemCode}</td>
                <td>${lotText}</td>
                <td>${locationText}</td>
            </tr>
        `;
    });

    $('#itemeable-items-table-body').html(html);

    updateItemeableItemsCounterForCart(requiredCount);
}

function updateItemeableItemsCounterForCart(requiredCount) {
    const selectedCount = $('.itemeable-item-checkbox:checked').length;

    $('#itemeable-selected-count').text(selectedCount);

    if (selectedCount >= requiredCount) {
        $('.itemeable-item-checkbox').not(':checked').prop('disabled', true);
    } else {
        $('.itemeable-item-checkbox').prop('disabled', false);
    }

    $('#btn-confirm-itemeable-items')
        .prop('disabled', selectedCount !== requiredCount);
}

function limpiarDatosPagoNormal() {
    $('#pv_cash_box_id').val('').trigger('change');
    $('#pv_cash_box_subtype_id').val('').trigger('change');

    $('#wrap_pv_subtype').hide();
    $('#pv_subtype_hint').hide();

    limpiarDatosComprobante();
}

function limpiarDatosComprobante() {
    $('#radio_none').prop('checked', true).trigger('change');

    $('#dni').val('');
    $('#name').val('');
    $('input[name="email_invoice_boleta"]').val('');

    $('#ruc').val('');
    $('#razon_social').val('');
    $('#direccion_fiscal').val('');
    $('input[name="email_invoice_factura"]').val('');

    $('#datos_boleta').addClass('d-none');
    $('#datos_factura').addClass('d-none');

    $('#collapseOneInvoice').collapse('hide');
}

function consultarClientePorDocumento(documento, tipo) {
    $.ajax({
        url: `/dashboard/customer/decolecta/${documento}`,
        type: 'GET',
        dataType: 'json',
        beforeSend: function () {
            if (tipo === 'dni') {
                $('#name').val('Consultando...');
            }

            if (tipo === 'ruc') {
                $('#razon_social').val('Consultando...');
                $('#direccion_fiscal').val('Consultando...');
            }
        },
        success: function (response) {
            if (!response.success) {
                toastr.error(response.message || 'No se pudo consultar el documento.', 'Error');
                return;
            }

            let customer = response.customer;

            if (tipo === 'dni') {
                $('#dni').val(customer.RUC);
                $('#name').val(customer.business_name || '');
            }

            if (tipo === 'ruc') {
                $('#ruc').val(customer.RUC);
                $('#razon_social').val(customer.business_name || '');
                $('#direccion_fiscal').val(customer.address || '');
            }

            let mensaje = 'Documento consultado correctamente.';
            toastr.success(mensaje, 'Correcto');
        },
        error: function (xhr) {
            let message = xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : 'No se encontró información. Puede ingresarlo manualmente.';

            toastr.warning(message + ' Puede ingresarlo manualmente.', 'Consulta sin resultado');

            if (tipo === 'dni') {
                $('#name').val('');
                $('#name').prop('readonly', false).focus();
            }

            if (tipo === 'ruc') {
                $('#razon_social').val('');
                $('#direccion_fiscal').val('');

                $('#razon_social').prop('readonly', false).focus();
                $('#direccion_fiscal').prop('readonly', false);
            }
        }
    });
}

function bloquearDatosComprobante() {
    $('input[name="name"]').prop('readonly', true);
    $('input[name="razon_social"]').prop('readonly', true);
    $('input[name="direccion_fiscal"]').prop('readonly', true);
}

function buildItemKey(productId, presentationId, selectedItemIds = []) {
    const presentationKey = presentationId ? presentationId : 'unit';

    const itemKey = Array.isArray(selectedItemIds) && selectedItemIds.length
        ? ':' + selectedItemIds
        .map(function (id) {
            return parseInt(id);
        })
        .filter(function (id) {
            return id > 0;
        })
        .sort(function (a, b) {
            return a - b;
        })
        .join('-')
        : '';

    return productId + ':' + presentationKey + itemKey;
}


function posPresentationName(id, units) {
    for (const rows of Object.values($presentationsCache)) {
        const presentation = rows.find(p => String(p.id) === String(id));
        if (presentation && typeof presentation.label === 'string' && presentation.label.trim() !== '') {
            return presentation.label;
        }
    }
    return String(units) + ' unidades';
}
function posEscapePresentation(value) {
    return String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function fetchPresentations(productId) {

    return $.get(`/dashboard/pos/materials/${productId}/presentations`)
        .then(res => {
            const actives = (res.presentations || []).filter(p => p.active === true || p.active === 1 || p.active === "1");
            $presentationsCache[productId] = actives;
            return actives;
        });
}

function renderPresentationsInModal(presentations) {
    if (!presentations || presentations.length === 0) {
        $('#presentationsArea').html('<div class="text-muted">Este producto no tiene presentaciones habilitadas en este local.</div>');
        return;
    }

    let html = `
    <div class="table-responsive">
      <table class="table table-sm table-bordered mb-0">
        <thead>
          <tr>
            <th style="width: 45%;">Presentación</th>
            <th style="width: 25%;">Precio</th>
            <th style="width: 30%;">Paquetes</th>
          </tr>
        </thead>
        <tbody>
  `;

    presentations.forEach(p => {
        // si no tienes label, mostramos por cantidad
        const label = (p.label && p.label.trim()) ? p.label : `${p.quantity} unidades`;

        html += `
      <tr data-pres-row data-pres-id="${p.id}" data-pres-qty="${p.quantity}" data-pres-price="${p.price}">
        <td><strong>${posEscapePresentation(label)}</strong><div class="text-muted" style="font-size:12px;">Equivale a ${p.quantity} unidades</div></td>
        <td>${window.POS_CURRENCY === 'USD' ? 'US$ ' : 'S/. '}${parseFloat(p.price).toFixed(2)}</td>
        <td>
          <input type="number" min="0" step="1" class="form-control form-control-sm" value="0" data-pres-packs>
        </td>
      </tr>
    `;
    });

    html += `
        </tbody>
      </table>
    </div>
  `;

    $('#presentationsArea').html(html);
}

function notAddProduct() {
    $modalQuantity.modal('hide');
}

function addProductCartSpecialO() {
    event.preventDefault(); // Evitar el comportamiento por defecto del enlace

    $modalQuantity.on('shown.bs.modal', function () {
        $('#quantity_total').trigger('focus');
    });

    let productId = $(this).data('product_id');
    let materialId = $(this).data('material_id');
    let productPrice = $(this).data('product_price');
    let productStock = $(this).data('product_stock');
    let productName = $(this).data('product_name');
    let productUnit = $(this).data('product_unit');
    let productTax = $(this).data('product_tax');
    let productType = $(this).data('product_type');

    // Verificar si el producto ya está en el carrito
    /*let existingProduct = $items.find(item => item.productId == productId);*/

    if ( $modeEdit == 0 )
    {
        toastr.error("Lo sentimos ya no puede agregar mas productos, anule o imprima el comprobante.", 'Error', {
            "closeButton": true,
            "debug": false,
            "newestOnTop": false,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "2000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        });
        return;
    }

    /*if (existingProduct) {
        // Si el producto ya está en el carrito, puedes actualizar la cantidad
        toastr.error("El producto "+productName+" ya esta agregado", 'Error',
            {
                "closeButton": true,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "2000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            });
    } else {
        showModalQuantity(productId, productPrice, productName, productUnit, productTax, productType, productStock);
    }*/
    showModalQuantity(productId, materialId,productPrice, productName, productUnit, productTax, productType, productStock);


}

function addProductCartSpecial() {
    event.preventDefault();

    let button = $(this);

    let productPrice = parseFloat(button.data('product_price'));

    if (isNaN(productPrice) || productPrice <= 0) {
        $.confirm({
            icon: 'fas fa-exclamation-triangle',
            theme: 'modern',
            closeIcon: true,
            animation: 'zoom',
            type: 'orange',
            title: 'Precio en cero',
            content: 'El precio de este producto es 0. ¿Procedemos con la venta?',
            buttons: {
                confirm: {
                    text: 'SÍ, CONTINUAR',
                    btnClass: 'btn-orange',
                    action: function () {
                        continuarAddProductCartSpecial(button);
                    }
                },
                cancel: {
                    text: 'CANCELAR'
                }
            }
        });

        return;
    }

    continuarAddProductCartSpecial(button);
}

function continuarAddProductCartSpecial(button) {

    $modalQuantity.on('shown.bs.modal', function () {
        $('#quantity_total').trigger('focus');
    });

    let productId = button.data('product_id');
    let materialId = button.data('material_id');
    let productPrice = button.data('product_price');
    let productStock = button.data('product_stock');
    let productName = button.data('product_name');
    let productUnit = button.data('product_unit');
    let productTax = button.data('product_tax');
    let productType = button.data('product_type');

    if ($modeEdit == 0) {
        toastr.error("Lo sentimos ya no puede agregar mas productos, anule o imprima el comprobante.", 'Error', {
            "closeButton": true
        });
        return;
    }

    showModalQuantity(
        productId,
        materialId,
        productPrice,
        productName,
        productUnit,
        productTax,
        productType,
        productStock
    );
}

function showModalQuantity(productId, materialId,productPrice, productName, productUnit, productTax, productType, productStock) {

    $("#quantity_productId").val(productId);
    $("#quantity_materialId").val(materialId);
    $("#quantity_productPrice").val(productPrice);
    $("#quantity_productStock").val(productStock);
    $("#quantity_productName").val(productName);
    $("#quantity_productUnit").val(productUnit);
    $("#quantity_productTax").val(productTax);
    $("#quantity_productType").val(productType);

    // reset inputs
    $("#quantity_total").val(0);
    $("#quantity_stock_show").val(productStock);
    $("#presentationsArea").html('<div class="text-muted">Cargando presentaciones...</div>');

    // cargar presentaciones activas
    fetchPresentations(materialId)
        .then(presentations => {
            renderPresentationsInModal(presentations);
            $modalQuantity.modal('show');

            $modalQuantity.on('shown.bs.modal', function () {
                $('#quantity_total').trigger('focus');
            });
        })
        .catch(() => {
            $('#presentationsArea').html('<div class="text-danger">No se pudo cargar presentaciones.</div>');
            $modalQuantity.modal('show');
        });
}

function addProduct() {
    event.preventDefault();

    let productId = parseInt($("#quantity_productId").val());
    let materialId = parseInt($("#quantity_materialId").val());
    let unitPrice = parseFloat($("#quantity_productPrice").val());
    let productStock = parseFloat($("#quantity_productStock").val());
    let productName = $("#quantity_productName").val();
    let productUnit = $("#quantity_productUnit").val();
    let productTax = parseFloat($("#quantity_productTax").val());
    let productType = parseInt($("#quantity_productType").val());

    let unitQty = parseFloat($("#quantity_total").val()) || 0;

    let presentationRows = [];

    $('#presentationsArea').find('tr[data-pres-row]').each(function () {
        const presId = parseInt($(this).attr('data-pres-id'), 10);
        const presQty = parseInt($(this).attr('data-pres-qty'), 10);
        const presPrice = parseFloat($(this).attr('data-pres-price'));
        const packs = parseInt($(this).find('[data-pres-packs]').val(), 10) || 0;

        if (packs > 0) {
            presentationRows.push({
                presentationId: presId,
                presentationQty: presQty,
                price: presPrice,
                packs: packs
            });
        }
    });

    if (unitQty <= 0 && presentationRows.length === 0) {
        toastr.error(
            "Ingrese cantidad (unidad) o paquetes de alguna presentación.",
            'Error',
            { closeButton: true }
        );
        return;
    }

    /*
     * Productos normales tipo 2 sí aceptan decimal en unidad.
     * Itemeables nunca aceptan decimal, aunque por configuración
     * tengan otro tipo.
     */
    const isItemeable = productType === 3;

    if (!isItemeable && productType !== 2 && unitQty % 1 !== 0) {
        toastr.error(
            "Este tipo de producto no acepta decimales en venta por unidad.",
            'Error',
            { closeButton: true }
        );

        $("#quantity_total").val(Math.floor(unitQty));
        return;
    }

    if (isItemeable && unitQty % 1 !== 0) {
        toastr.error(
            "Los productos itemeables solo pueden venderse en unidades enteras.",
            'Error',
            { closeButton: true }
        );

        $("#quantity_total").val(Math.floor(unitQty));
        return;
    }

    /*
     * Construimos cada futura línea del carrito.
     */
    let linesToAdd = [];

    if (unitQty > 0) {
        const qtyToUse = (productType === 2 && !isItemeable)
            ? parseFloat(unitQty)
            : Math.floor(unitQty);

        linesToAdd.push({
            productId: productId,
            materialId: materialId,
            productPrice: unitPrice,
            productName: productName,
            productUnit: productUnit,
            productTax: productTax,
            productType: productType,

            presentationId: null,
            presentationQty: 1,
            productQuantity: qtyToUse,
            presentationLabel: 'Unidad',

            unitsEquivalent: qtyToUse
        });
    }

    presentationRows.forEach(function (row) {
        linesToAdd.push({
            productId: productId,
            materialId: materialId,
            productPrice: row.price,
            productName: productName,
            productUnit: productUnit,
            productTax: productTax,
            productType: productType,

            presentationId: row.presentationId,
            presentationQty: row.presentationQty,
            productQuantity: row.packs,
            presentationLabel: posPresentationName(row.presentationId, row.presentationQty),

            unitsEquivalent: row.packs * row.presentationQty
        });
    });

    /*
     * Stock equivalente total.
     */
    const unitsRequired = linesToAdd.reduce(function (sum, line) {
        return sum + Number(line.unitsEquivalent || 0);
    }, 0);

    if (productStock < unitsRequired) {
        toastr.error(
            `La cantidad sobrepasa el stock del material. Stock: ${productStock} unidades. Requerido: ${unitsRequired} unidades.`,
            'Error',
            { closeButton: true }
        );
        return;
    }

    /*
     * Itemeable:
     * se guarda el borrador y el selector repartirá los Items
     * entre las líneas, respetando sus unidades equivalentes.
     */
    if (isItemeable) {
        if (!Number.isInteger(unitsRequired) || unitsRequired <= 0) {
            toastr.error(
                'La cantidad requerida para un producto itemeable no es válida.',
                'Error',
                { closeButton: true }
            );
            return;
        }

        $currentItemeableCartDraft = {
            productId: productId,
            materialId: materialId,
            productName: productName,
            productType: productType,

            /*
             * Se conservan las líneas completas para repartir
             * los Items seleccionados por presentación.
             */
            lines: linesToAdd,

            unitsEquivalent: unitsRequired,
            fromSpecialModal: true
        };

        $modalQuantity.modal('hide');

        openItemeableItemsSelectorForCart($currentItemeableCartDraft);

        return;
    }

    /*
     * Producto normal:
     * mantiene el comportamiento actual.
     */
    let rowsAdded = 0;

    linesToAdd.forEach(function (line) {
        const added = addProductToCart({
            productId: line.productId,
            materialId: line.materialId,
            productPrice: line.productPrice,
            productName: line.productName,
            productUnit: line.productUnit,
            productTax: line.productTax,
            productType: line.productType,

            presentationId: line.presentationId,
            presentationQty: line.presentationQty,
            productQuantity: line.productQuantity,
            presentationLabel: line.presentationLabel,

            unitsEquivalent: line.unitsEquivalent,
            selectedItems: []
        });

        if (added) {
            rowsAdded++;
        }
    });

    if (rowsAdded > 0) {
        $('#quantity_total').val(0);
        $('#presentationsArea').find('[data-pres-packs]').val(0);
        $modalQuantity.modal('hide');
    }

    updateTotalOrder();
}

function renderDataCartRow(itemKey) {
    const item = $items.find(function (x) {
        return x.itemKey === itemKey;
    });

    if (!item) {
        return;
    }

    var clone = activateTemplate('#item-cart');

    clone.querySelector("[data-delete]").setAttribute("data-delete", itemKey);

    clone.querySelector("[data-name]").innerHTML = item.productName;

    const presLabel = item.presentationId
        ? `Presentación: ${item.presentationLabel}`
        : `Presentación: Unidad`;

    clone.querySelector("[data-presentation_label]").textContent = presLabel;

    /*
     * Requiere agregar este elemento al template:
     * <small data-selected_items class="text-muted"></small>
     *
     * Si todavía no existe, esta condición evita que el JS falle.
     */
    const selectedItemsElement = clone.querySelector("[data-selected_items]");

    if (selectedItemsElement) {
        if (item.isItemeable && item.selected_items_text) {
            selectedItemsElement.innerHTML =
                `Ítems seleccionados: ${item.selected_items_text}`;
            selectedItemsElement.style.display = '';
        } else {
            selectedItemsElement.innerHTML = '';
            selectedItemsElement.style.display = 'none';
        }
    }

    clone.querySelector("[data-price]").innerHTML = changeStringPrice(
        itemKey,
        item.productQuantity
    );

    const minusButton = clone.querySelector("[data-item_key_minus]");
    const plusButton = clone.querySelector("[data-item_key_plus]");

    minusButton.setAttribute("data-item_key_minus", itemKey);
    plusButton.setAttribute("data-item_key_plus", itemKey);

    /*
     * Itemeables:
     * la cantidad depende de los Items seleccionados.
     * No se debe cambiar directamente con + o -.
     */
    if (item.isItemeable) {
        minusButton.disabled = true;
        plusButton.disabled = true;

        minusButton.classList.add('disabled');
        plusButton.classList.add('disabled');

        minusButton.setAttribute(
            'title',
            'La cantidad depende de los ítems físicos seleccionados.'
        );

        plusButton.setAttribute(
            'title',
            'La cantidad depende de los ítems físicos seleccionados.'
        );
    }

    var quantityInput = clone.querySelector("[data-quantity]");

    if (quantityInput) {
        quantityInput.step = item.presentationId
            ? 1
            : (item.productType == 2 ? 0.01 : 1);

        quantityInput.value = item.productQuantity;

        /*
         * También bloqueamos edición manual de cantidad.
         */
        if (item.isItemeable) {
            quantityInput.readOnly = true;
        }
    }

    clone.querySelector("[data-priceTotal]").innerHTML =
        parseFloat(item.productTotal).toFixed(2);

    $("#body-cart").append(clone);

    if (quantityInput) {
        $(quantityInput).trigger('input');
    }
}

function newSale() {
    location.reload();
}

function cerrarVuelto() {
    $modalVuelto.modal('hide');
    $("#btn-pay").attr("disabled", false);
}

function payNow() {
    event.preventDefault();
    $("#btn-pay").attr("disabled", true);

    // 1) Validar items
    if ($items.length === 0) {
        toastr.error("Seleccione productos a la venta.", 'Error', { "closeButton": true });
        $("#btn-pay").attr("disabled", false);
        return;
    }

    const pagosParcialesVenta =
        $('#pagos_parciales_venta').length && $('#pagos_parciales_venta').is(':checked');

    if (pagosParcialesVenta) {
        window.PV_SELECTED_WORKER_ID = null;
        guardarVenta();
        return;
    }

    // 2) Validar CashBox seleccionado
    const cashBoxId = $('#pv_cash_box_id').val();
    if (!cashBoxId) {
        toastr.error("Seleccione una caja (CashBox).", 'Error', { "closeButton": true });
        $("#btn-pay").attr("disabled", false);
        return;
    }

    const $opt = $('#pv_cash_box_id').find('option:selected');
    const boxType = ($opt.data('type') || '').toString();          // 'cash' | 'bank'
    const usesSub = String($opt.data('uses_subtypes')) === '1';     // 1/0

    // 3) Si es bancario con subtypes, validar subtipo
    if (boxType === 'bank' && usesSub) {
        const subtypeId = $('#pv_cash_box_subtype_id').val();
        if (!subtypeId) {
            toastr.error("Seleccione el subtipo bancario (Yape/Plin/POS/Transfer).", 'Error', { "closeButton": true });
            $("#btn-pay").attr("disabled", false);
            return;
        }
    }

    // 4) Definir si requiere vuelto (antes era data-vuelto del radio)
    // Regla recomendada:
    // - cash => puede requerir vuelto (abrimos modal)
    // - bank => no requiere vuelto (guardamos directo)
    const requiresChange = (boxType === 'cash');

    // 5) Trabajador (si aplica)
    if (!window.PV_ASK_WORKER) {
        window.PV_SELECTED_WORKER_ID = null;
        continuarFlujoPago();
        return;
    }

    mostrarPopupTrabajador(continuarFlujoPago);

    function continuarFlujoPago() {
        if (requiresChange) {
            mostrarVuelto();
        } else {
            guardarVenta();
        }
    }
}

function refreshVueltoSubtypeUI() {
    const $opt = $('#pv_vuelto_cash_box_id').find('option:selected');
    const type = ($opt.data('type') || '').toString(); // cash|bank
    const usesSub = String($opt.data('uses_subtypes')) === '1';

    if (type === 'bank' && usesSub) {
        $('#wrap_vuelto_subtype').show();
    } else {
        $('#wrap_vuelto_subtype').hide();
        $('#pv_vuelto_subtype_id').val('').trigger('change');
    }
}

function mostrarPopupTrabajador(doneCallback) {
    // construir options
    if (!(window.PV_WORKERS || []).length) { window.PV_SELECTED_WORKER_ID = null; doneCallback(); return; }
    let optionsHtml = '<option value="">Sin selección adicional (opcional)</option>';
    (window.PV_WORKERS || []).forEach(function (w) {
        optionsHtml += '<option value="'+w.id+'">'+$('<div>').text(w.name).html()+'</option>';
    });

    $.confirm({
        title: 'Trabajador (opcional)',
        content:
            '<form>' +
            '<div class="form-group">' +
            '<label>Trabajador que atiende la venta (opcional)</label>' +
            '<select id="pv-worker-select" class="form-control">' +
            optionsHtml +
            '</select>' +
            '</div>' +
            '</form>',
        type: 'blue',
        buttons: {
            confirmar: {
                text: 'Aceptar',
                btnClass: 'btn-primary',
                action: function () {
                    var workerId = this.$content.find('#pv-worker-select').val();

                    window.PV_SELECTED_WORKER_ID = workerId;

                    if (typeof doneCallback === 'function') {
                        doneCallback();
                    }
                }
            },
            cancelar: {
                text: 'Cancelar',
                btnClass: 'btn-secondary',
                action: function () {
                    $("#btn-pay").attr("disabled", false);
                }
            }
        }
    });
}

function mostrarVuelto() {
    $("#monto_total").val(parseFloat($fin_total_importe).toFixed(2));
    refreshVueltoSubtypeUI();
    $modalVuelto.modal('show');
}

function guardarVenta() {
    event.preventDefault();
    $modalVuelto.modal('hide');

    $("#btn-pay").attr("disabled", true);

    $fin_vuelto = $("#vuelto").val();
    $type_vuelto = $("#type_caja").val();

    if ( $items.length == 0 )
    {
        toastr.error("Seleccione productos a la venta.", 'Error', {
            "closeButton": true,
            "debug": false,
            "newestOnTop": false,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "2000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        });
        $("#btn-pay").attr("disabled", false);
        return;
    }

    const pagosParcialesVenta =
        $('#pagos_parciales_venta').length && $('#pagos_parciales_venta').is(':checked');

    if (!pagosParcialesVenta) {
        let tipo = $('input[name="invoice_type"]:checked').val();

        if (tipo === 'boleta') {
            let dni = $('input[name="dni"]').val().trim();
            if (dni === '' || dni.length !== 8 || isNaN(dni)) {
                toastr.error('Debe ingresar un DNI válido de 8 dígitos');
                $("#btn-pay").attr("disabled", false);
                return;
            }
            let name = $('input[id="name"]').val().trim();
            console.log(name);
            if (name === '') {
                toastr.error('Debe ingresar el nombre del cliente.');
                $("#btn-pay").attr("disabled", false);
                return;
            }
        } else if (tipo === 'factura') {
            let ruc = $('input[name="ruc"]').val().trim();
            let razon = $('input[name="razon_social"]').val().trim();
            let direccion = $('input[name="direccion_fiscal"]').val().trim();

            if (ruc === '' || ruc.length !== 11 || isNaN(ruc)) {
                toastr.error('Debe ingresar un RUC válido de 11 dígitos');
                $("#btn-pay").attr("disabled", false);
                return;
            }
            if (razon === '') {
                toastr.error('Debe ingresar la Razón Social');
                $("#btn-pay").attr("disabled", false);
                return;
            }
            if (direccion === '') {
                toastr.error('Debe ingresar la Dirección Fiscal');
                $("#btn-pay").attr("disabled", false);
                return;
            }
        }
    }

    // ==============================
    // Texto del medio de pago (Caja + Subtipo)
    // ==============================
    let paymentText = '';

    if (pagosParcialesVenta) {
        paymentText = 'venta con pagos parciales';
    } else {

        // Caja seleccionada
        const $cashBoxOpt = $('#pv_cash_box_id option:selected');
        const cashBoxName = $cashBoxOpt.text().trim();
        const cashBoxType = $cashBoxOpt.data('type');
        const usesSubtypes = String($cashBoxOpt.data('uses_subtypes')) === '1';

        // Subtipo (si aplica)
        let subtypeName = '';

        if (cashBoxType === 'bank' && usesSubtypes) {
            const $subOpt = $('#pv_cash_box_subtype_id option:selected');
            subtypeName = $subOpt.length ? $subOpt.text().trim() : '';
        }

        // Texto final
        if (subtypeName) {
            paymentText = cashBoxName + ' – ' + subtypeName;
        } else {
            paymentText = cashBoxName;
        }
    }

    const invalidItemeable = $items.find(function (item) {
        if (!item.isItemeable) {
            return false;
        }

        const selectedItemIds = Array.isArray(item.selected_item_ids)
            ? item.selected_item_ids
            : [];

        const requiredUnits = parseFloat(item.unitsEquivalent || 0);

        return selectedItemIds.length !== requiredUnits;
    });

    if (invalidItemeable) {
        toastr.error(
            'Uno de los productos itemeables no tiene la cantidad correcta de ítems físicos seleccionados.',
            'Validación de ítems'
        );

        $("#btn-pay").attr("disabled", false);
        return;
    }

    // Confirmación con jQuery Confirm
    $.confirm({
        title: 'Confirmar pago',
        content: pagosParcialesVenta
            ? '¿Está seguro de registrar esta venta como <strong>venta con pagos parciales</strong>?'
            : '¿Está seguro de realizar el pago usando <strong>' + paymentText + '</strong>?',
        type: 'blue',
        buttons: {
            confirmar: {
                text: 'Sí, confirmar',
                btnClass: 'btn-primary',
                action: function () {
                    // Proceder con el envío del formulario
                    var createUrl = $formCreate.data('url');
                    var items = JSON.stringify($items);
                    var formulario = $('#formCreate')[0];
                    var form = new FormData(formulario);

                    form.append('items', items);
                    form.append('total_exonerada', $fin_total_exonerada);
                    form.append('total_igv', $fin_total_igv);
                    form.append('total_gravada', $fin_total_gravada);
                    form.append('total_descuentos', $fin_total_descuentos);
                    form.append('total_importe', $fin_total_importe);
                    const submittedChange = !pagosParcialesVenta && $('#pv_cash_box_id option:selected').data('type') === 'cash'
                        ? Number($('#vuelto').val() || 0) : 0;
                    form.append('total_vuelto', submittedChange.toFixed(2));
                    /*form.append('type_vuelto', $type_vuelto);
                    form.append('tipo_pago', tipo_pago);*/
                    // ⬇️ NUEVO: worker elegido (si no hay, va vacío)
                    form.append('worker_id', window.PV_SELECTED_WORKER_ID || '');

                    form.append('cash_box_id', pagosParcialesVenta ? '' : $('#pv_cash_box_id').val());
                    form.append('cash_box_subtype_id', pagosParcialesVenta ? '' : ($('#pv_cash_box_subtype_id').val() || ''));
                    form.append('vuelto_cash_box_id', pagosParcialesVenta ? '' : ($('#pv_vuelto_cash_box_id').val() || ''));
                    form.append('vuelto_cash_box_subtype_id', pagosParcialesVenta ? '' : ($('#pv_vuelto_subtype_id').val() || ''));

                    form.append('pagos_parciales_venta', pagosParcialesVenta ? 's' : 'n');
                    const priceContext = document.getElementById('pos-catalog-context').dataset;
                    form.append('pos_price_list_id', priceContext.priceList || '');
                    form.append('pos_request_key', priceContext.requestKey);
                    form.append('pos_currency', priceContext.currency);
                    ['tenant','company','branch'].forEach(function (key) { form.append('material_inventory_'+key, priceContext[key]); });

                    const tipoComprobante = $('input[name="invoice_type"]:checked').val();

                    let data = {};

                    if (tipoComprobante === 'boleta') {
                        data.name = $('#name').val();
                        data.dni = $('#dni').val();
                        data.email = $('#email_invoice_boleta').val();
                    } else if (tipoComprobante === 'factura') {
                        data.ruc = $('#ruc').val();
                        data.razon_social = $('#razon_social').val();
                        data.direccion_fiscal = $('#direccion_fiscal').val();
                        data.email = $('#email_invoice_factura').val();
                    }

                    var processingDialog = $.dialog({
                        title: false,
                        content:
                            '<div class="text-center py-4">' +
                            '<div class="spinner-border text-primary mb-3" role="status">' +
                            '<span class="sr-only">Procesando...</span>' +
                            '</div>' +
                            '<h5 class="mb-2">Estamos procesando la venta</h5>' +
                            '<p class="text-muted mb-0">' +
                            'Por favor, espere mientras registramos el pago y generamos el comprobante.' +
                            '</p>' +
                            '</div>',
                        columnClass: 'col-md-5 col-md-offset-4',
                        containerFluid: true,
                        backgroundDismiss: false,
                        closeIcon: false,
                        escapeKey: false
                    });

                    $.ajax({
                        url: createUrl,
                        method: 'POST',
                        data: form,
                        processData: false,
                        contentType: false,
                        success: function (data) {
                            console.log(data);
                            if (data.warnings && data.warnings.length) {
                                $.alert({title:'Venta registrada: revisar avisos',content:$('<div>').text(data.warnings.join(' ')).html(),type:'orange'});
                            }
                            toastr.success(data.message, 'Éxito', {
                                "closeButton": true,
                                "progressBar": true,
                                "positionClass": "toast-top-right",
                                "timeOut": "2000"
                            });
                            setTimeout(function () {
                                $("#btn-pay").attr("disabled", false);
                                $modeEdit = 0;
                                $sale_id = data.sale_id;

                                $("#btn-pay").hide();
                                $("#btn-newSale").show();
                                $("#btn-printDocument").show();
                                //$("#btn-printDocument").attr("href", data.url_print);
                                $("#btn-printDocument")
                                    .attr("href", data.url_print)
                                    .attr("target", "_blank");

                                if (data.print_type === 'sunat_pdf') {
                                    $("#btn-printDocument").text('Ver comprobante SUNAT');
                                } else {
                                    $("#btn-printDocument").text('Imprimir ticket');
                                }
                            }, 2000);
                        },
                        error: function (data) {
                            if (data.responseJSON.message && !data.responseJSON.errors) {
                                toastr.error(data.responseJSON.message, 'Error');
                            }
                            for (var property in data.responseJSON.errors) {
                                toastr.error(data.responseJSON.errors[property], 'Error');
                            }
                            $("#btn-pay").attr("disabled", false);
                        },
                        complete: function () {
                            if (processingDialog) {
                                processingDialog.close();
                            }
                        }
                    });
                }
            },
            cancelar: {
                text: 'Cancelar',
                btnClass: 'btn-secondary',
                action: function () {
                    $("#btn-pay").attr("disabled", false);
                }
            }
        }
    });
}

function deleteItem() {
    if ($modeEdit == 0) {
        toastr.error("Lo sentimos ya no puede quitar productos, anule o imprima el comprobante.", 'Error', { "closeButton": true });
        return;
    }

    let itemKey = $(this).attr('data-delete');

    $items = $items.filter(item => item.itemKey !== itemKey);

    updateTotalOrder();

    $(this).closest('[data-cart-row]').remove();
}

function updateItems(itemKey, precioTotal, quantity) {
    let result = $items.find(item => item.itemKey === itemKey);
    if (!result) return;

    result.productQuantity = quantity;

    // Recalcular equivalente de stock:
    // unidad => quantity
    // presentación => quantity * presentationQty
    result.unitsEquivalent = result.presentationId ? (quantity * result.presentationQty) : quantity;

    result.productTotal = parseFloat(precioTotal).toFixed(2);
}

function decrementQuantity(button) {
    var $input = $(button).siblings('input[type="number"]');
    var currentValue = parseFloat($input.val());
    var step = parseFloat($input.attr('step')) || 0.01;
    let itemKey = $(button).attr('data-item_key_minus');
    var string = "";
    var priceTotal = 0;
    var stringDiscount = "";

    const currentItem = $items.find(function (item) {
        return item.itemKey === itemKey;
    });

    if (currentItem && currentItem.isItemeable) {
        toastr.warning(
            'No puede modificar la cantidad directamente. Elimine la línea y vuelva a seleccionar los ítems requeridos.',
            'Producto itemeable'
        );
        return;
    }

    if (currentValue > 0) {
        $input.val((currentValue - step).toFixed(2)).trigger('change');
        string = changeStringPrice( itemKey, (currentValue - step).toFixed(2) );
        priceTotal = changePriceTotal( itemKey, (currentValue - step).toFixed(2) );
        $(button).closest('.flex-grow-1').find('h6[data-price]').html(string);
        $(button).closest('.d-flex').find('p[data-priceTotal]').html(priceTotal);

        getDiscountMaterial(itemKey, currentValue - step).then(function(discount) {
            console.log(discount);
            if ( discount != -1 )
            {
                $(button).closest('.flex-grow-1').find('h6[data-discount]').html(discount.stringDiscount);
            } else  {
                $(button).closest('.flex-grow-1').find('h6[data-discount]').html("");
            }

            //updateItems($(button).attr('data-product_id_plus', currentValue + step), priceTotal, currentValue + step);
        });

        updateItems(itemKey, priceTotal, currentValue - step);

        updateTotalOrder();
    } else {
        $input.val(0);
        string = changeStringPrice( itemKey, 0 );
        priceTotal = changePriceTotal( itemKey, 0 );
        $(button).closest('.flex-grow-1').find('h6[data-price]').html(string);
        $(button).closest('.d-flex').find('p[data-priceTotal]').html(priceTotal);

        getDiscountMaterial(itemKey, currentValue - step).then(function(discount) {
            console.log(discount);
            //$(button).closest('.flex-grow-1').find('h6[data-discount]').html(discount.stringDiscount);
            if ( discount != -1 )
            {
                $(button).closest('.flex-grow-1').find('h6[data-discount]').html(discount.stringDiscount);
            } else  {
                $(button).closest('.flex-grow-1').find('h6[data-discount]').html("");
            }
            //updateItems($(button).attr('data-product_id_plus', currentValue + step), priceTotal, currentValue + step);
        });

        updateItems(itemKey, priceTotal, 0);

        updateTotalOrder();
    }
    //console.log(string);
}

function changePriceTotal(itemKey, quantity) {
    let result = $items.find(item => item.itemKey === itemKey);
    if (!result) return "0.00";
    return (quantity * result.productPrice).toFixed(2);
}

function changeStringPrice(itemKey, quantity) {
    let result = $items.find(item => item.itemKey === itemKey);
    if (!result) return "";

    if (result.presentationId) {
        // paquetes
        return `<strong>${quantity}</strong> paquetes (${result.presentationQty} u) a ${window.POS_CURRENCY === 'USD' ? 'US$ ' : 'S/. '}${parseFloat(result.productPrice).toFixed(2)} / paquete`;
    } else {
        // unidad
        return `<strong>${quantity}</strong> ${result.productUnit} a ${window.POS_CURRENCY === 'USD' ? 'US$ ' : 'S/. '}${parseFloat(result.productPrice).toFixed(2)} / unidad`;
    }
}

function incrementQuantity(button) {
    event.preventDefault();
    var $input = $(button).siblings('input[type="number"]');
    var currentValue = parseFloat($input.val());
    var step = parseFloat($input.attr('step')) || 0.01;
    let itemKey = $(button).attr('data-item_key_plus');

    const currentItem = $items.find(function (item) {
        return item.itemKey === itemKey;
    });

    if (currentItem && currentItem.isItemeable) {
        toastr.warning(
            'No puede modificar la cantidad directamente. Elimine la línea y vuelva a seleccionar los ítems requeridos.',
            'Producto itemeable'
        );
        return;
    }

    $input.val((currentValue + step).toFixed(2)).trigger('change');

    var string = "";
    var priceTotal = 0;
    var discount = 0;

    string = changeStringPrice( itemKey, (currentValue + step).toFixed(2) );
    priceTotal = changePriceTotal( itemKey, (currentValue + step).toFixed(2) );
    //console.log(string);

    $(button).closest('.flex-grow-1').find('h6[data-price]').html(string);
    $(button).closest('.d-flex').find('p[data-priceTotal]').text(priceTotal);

    // Maneja la promesa retornada por getDiscountMaterial
    getDiscountMaterial(itemKey, currentValue + step).then(function(discount) {
        console.log(discount);
        //$(button).closest('.flex-grow-1').find('h6[data-discount]').html(discount.stringDiscount);
        if ( discount != -1 )
        {
            $(button).closest('.flex-grow-1').find('h6[data-discount]').html(discount.stringDiscount);
        } else  {
            $(button).closest('.flex-grow-1').find('h6[data-discount]').html("");
        }
        //updateItems($(button).attr('data-product_id_plus', currentValue + step), priceTotal, currentValue + step);
    });

    //$(button).closest('.flex-grow-1').find('h6[data-discount]').html(discount.stringDiscount);

    updateItems(itemKey, priceTotal, currentValue + step);
    
    updateTotalOrder();
}

function updateTotalOrder() {
    const cents={gravada:0n,exonerada:0n,inafecta:0n,igv:0n,total:0n};
    const fixed4=value=>{
        const number=Number(value);
        return !Number.isFinite(number)||number<0||number>=1e21?0n:BigInt(number.toFixed(4).replace('.',''));
    };
    $items.forEach(function(item){
        item.productDiscount=0;
        const gross=(fixed4(item.productPrice)*fixed4(item.productQuantity)+500000n)/1000000n;
        item.productTotal=(Number(gross)/100).toFixed(2);
        const rate=Number(item.productTax);
        const kind=(window.POS_TAX_KIND_BY_STOCK||{})[item.productId]||(rate>0?'gravada':'exonerada');
        const divisor=1000000n+fixed4(rate);
        const net=kind==='gravada'?(gross*1000000n+divisor/2n)/divisor:gross;
        cents[kind]+=net;cents.igv+=gross-net;cents.total+=gross;
    });
    const values={};Object.keys(cents).forEach(key=>values[key]=Number(cents[key])/100);
    $fin_total_exonerada=values.exonerada;$fin_total_gravada=values.gravada;$fin_total_igv=values.igv;
    $fin_total_descuentos=0;$fin_total_importe=values.total;
    const symbol=window.POS_CURRENCY==='USD'?'US$ ':'S/. ';
    $('#op_exonerada').text(symbol+values.exonerada.toFixed(2));
    $('#op_inafecta').text(symbol+values.inafecta.toFixed(2));
    $('#op_gravada').text(symbol+values.gravada.toFixed(2));
    $('#total_igv').text(symbol+values.igv.toFixed(2));
    $('#total_descuentos').text(symbol+'0.00');$('#total_importe').text(symbol+values.total.toFixed(2));
}

function getDiscountMaterial(product_id, quantity) {
    return $.Deferred().resolve(-1).promise();
}

function addProductCartO() {
    event.preventDefault();

    let productId = $(this).data('product_id');
    let materialId = $(this).data('material_id');
    let productPrice = parseFloat($(this).data('product_price'));
    let productStock = parseFloat($(this).data('product_stock'));
    let productName = $(this).data('product_name');
    let productUnit = $(this).data('product_unit');
    let productTax = parseFloat($(this).data('product_tax'));
    let productType = $(this).data('product_type'); // si no existe en normal, puedes dejarlo null

    if ($modeEdit == 0) {
        toastr.error("Lo sentimos ya no puede agregar mas productos, anule o imprima el comprobante.", 'Error', { "closeButton": true });
        return;
    }

    // 👇 clave única para “unidad”
    const itemKey = buildItemKey(productId, null);

    // Si ya existe la fila unidad, no agregues otra: usa +/-
    let existing = $items.find(x => x.itemKey === itemKey);

    if (existing) {
        toastr.error(`El producto ${productName} (Unidad) ya está agregado. Use + / - para modificar.`, 'Error', { "closeButton": true });
        return;
    }

    // validar stock (unidad)
    if (productStock < 1) {
        toastr.error("Stock insuficiente.", 'Error', { "closeButton": true });
        return;
    }

    // agregar item “unit”
    $items.push({
        itemKey: itemKey,
        productId: productId,
        materialId: materialId,
        presentationId: null,
        presentationQty: 1,
        presentationLabel: 'Unidad',
        priceEffective: productPrice,
        productPrice: productPrice,
        productName: productName,
        productUnit: productUnit,
        productTax: productTax,
        productTotal: parseFloat(productPrice * 1).toFixed(2),
        productTotalTaxes: parseFloat((productPrice * 1) * (1 + (productTax / 100))).toFixed(2),
        productTaxes: parseFloat((productPrice * 1) * (productTax / 100)).toFixed(2),
        productQuantity: 1,       // unidades
        unitsEquivalent: 1,       // unidades
        productDiscount: 0,
        productType: productType  // si lo usas en step etc
    });

    // ✅ usa el nuevo render
    renderDataCartRow(itemKey);

    updateTotalOrder();
}

function addProductCart() {
    event.preventDefault();

    let button = $(this);

    let productPrice = parseFloat(button.data('product_price'));

    if (isNaN(productPrice) || productPrice <= 0) {
        $.confirm({
            icon: 'fas fa-exclamation-triangle',
            theme: 'modern',
            closeIcon: true,
            animation: 'zoom',
            type: 'orange',
            title: 'Precio en cero',
            content: 'El precio de este producto es 0. ¿Procedemos con la venta?',
            buttons: {
                confirm: {
                    text: 'SÍ, CONTINUAR',
                    btnClass: 'btn-orange',
                    action: function () {
                        continuarAddProductCart(button);
                    }
                },
                cancel: {
                    text: 'CANCELAR',
                    action: function () {
                        return;
                    }
                }
            }
        });

        return;
    }

    continuarAddProductCart(button);
}

function continuarAddProductCart(button) {
    let productId = button.data('product_id');
    let materialId = button.data('material_id');
    let productPrice = parseFloat(button.data('product_price'));
    let productStock = parseFloat(button.data('product_stock'));
    let productName = button.data('product_name');
    let productUnit = button.data('product_unit');
    let productTax = parseFloat(button.data('product_tax'));
    let productType = button.data('product_type');

    if ($modeEdit == 0) {
        toastr.error(
            "Lo sentimos ya no puede agregar más productos, anule o imprima el comprobante.",
            'Error',
            { closeButton: true }
        );
        return;
    }

    if (productStock < 1) {
        toastr.error("Stock insuficiente.", 'Error', { closeButton: true });
        return;
    }

    const isItemeable = parseInt(productType || 0) === 3;

    /*
     * Producto itemeable:
     * antes de agregarlo se debe identificar su Item físico.
     */
    if (isItemeable) {
        $currentItemeableCartDraft = {
            productId: parseInt(productId),
            materialId: parseInt(materialId),
            productPrice: productPrice,
            productStock: productStock,
            productName: productName,
            productUnit: productUnit,
            productTax: productTax,
            productType: parseInt(productType),
            presentationId: null,
            presentationQty: 1,
            presentationLabel: 'Unidad',
            unitsEquivalent: 1
        };

        openItemeableItemsSelectorForCart($currentItemeableCartDraft);

        return;
    }

    addProductToCart({
        productId: parseInt(productId),
        materialId: parseInt(materialId),
        productPrice: productPrice,
        productName: productName,
        productUnit: productUnit,
        productTax: productTax,
        productType: productType,
        presentationId: null,
        presentationQty: 1,
        presentationLabel: 'Unidad',
        unitsEquivalent: 1,
        selectedItems: []
    });
}

function addProductToCart(productData) {
    const selectedItems = Array.isArray(productData.selectedItems)
        ? productData.selectedItems
        : [];

    const selectedItemIds = selectedItems
        .map(function (item) {
            return parseInt(item.id);
        })
        .filter(function (itemId) {
            return itemId > 0;
        });

    const isItemeable = parseInt(productData.productType || 0) === 3;

    const itemKey = buildItemKey(
        productData.productId,
        productData.presentationId,
        selectedItemIds
    );

    const existing = $items.find(function (item) {
        return item.itemKey === itemKey;
    });

    if (existing) {
        if (isItemeable) {
            toastr.error(
                `El ítem seleccionado ya está agregado en el carrito.`,
                'Error',
                { closeButton: true }
            );
        } else {
            toastr.error(
                `El producto ${productData.productName} (${posEscapePresentation(productData.presentationLabel)}) ya está agregado. Use + / - para modificar.`,
                'Error',
                { closeButton: true }
            );
        }

        return false;
    }

    // Keep packs sold separate from physical units in each package.
    const quantityVisible = Number(productData.productQuantity
        ?? (productData.presentationId ? 1 : (productData.unitsEquivalent ?? 1)));
    const unitsPerPack = productData.presentationId ? Number(productData.presentationQty) : 1;
    const unitsEquivalent = quantityVisible * unitsPerPack;
    if (!Number.isFinite(quantityVisible) || !Number.isFinite(unitsPerPack) || quantityVisible <= 0
        || unitsPerPack <= 0 || (productData.presentationId && (!Number.isInteger(quantityVisible) || !Number.isInteger(unitsPerPack)))
        || (isItemeable && (!Number.isInteger(unitsEquivalent) || selectedItemIds.length !== unitsEquivalent))) {
        toastr.error('Revise la cantidad de paquetes, sus unidades y los Items seleccionados.', 'Cantidad inválida');
        return false;
    }
    const priceEffective = parseFloat(productData.productPrice || 0);
    const productTax = parseFloat(productData.productTax || 0);

    const selectedItemsText = selectedItems
        .map(function (item) {
            return item.code || ('Ítem #' + item.id);
        })
        .join(', ');

    $items.push({
        itemKey: itemKey,

        productId: parseInt(productData.productId), // stock_item_id
        materialId: parseInt(productData.materialId),

        presentationId: productData.presentationId || null,
        presentationQty: unitsPerPack,
        presentationLabel: productData.presentationLabel || 'Unidad',

        priceEffective: priceEffective,
        productPrice: priceEffective,

        productName: productData.productName,
        productUnit: productData.productUnit,
        productTax: productTax,

        productTotal: parseFloat(priceEffective * quantityVisible).toFixed(2),
        productTotalTaxes: parseFloat(
            (priceEffective * quantityVisible) * (1 + (productTax / 100))
        ).toFixed(2),
        productTaxes: parseFloat(
            (priceEffective * quantityVisible) * (productTax / 100)
        ).toFixed(2),

        productQuantity: quantityVisible,
        unitsEquivalent: unitsEquivalent,

        productDiscount: 0,
        productType: productData.productType,

        isItemeable: isItemeable,
        selected_item_ids: selectedItemIds,
        selected_items: selectedItems,
        selected_items_text: selectedItemsText
    });

    renderDataCartRow(itemKey);
    updateTotalOrder();

    return true;
}

function renderDataCart(productId, productPrice, productName, productUnit) {
    var quantity = 1;
    var clone = activateTemplate('#item-cart');
    clone.querySelector("[data-delete]").setAttribute("data-delete", productId);
    clone.querySelector("[data-stock]").setAttribute("data-stock", productId);
    clone.querySelector("[data-name]").innerHTML = productName;
    clone.querySelector("[data-price]").innerHTML = "<strong>" + quantity + "</strong> "+productUnit+" a " + productPrice + " / Unit";
    clone.querySelector("[data-product_id_minus]").setAttribute("data-product_id_minus", productId);
    clone.querySelector("[data-product_id_plus]").setAttribute("data-product_id_plus", productId);

    var quantityInput = clone.querySelector("[data-quantity]");
    if (quantityInput) {
        quantityInput.value = quantity; // Usa .value en lugar de setAttribute
    }

    var priceTotal;
    priceTotal = quantity * productPrice;

    clone.querySelector("[data-priceTotal]").innerHTML = priceTotal;

    $("#body-cart").append(clone);
}

function renderDataCartQuantity(productId, productPrice, productName, productUnit, productQuantity) {
    var quantity = parseFloat(productQuantity).toFixed(2);
    var clone = activateTemplate('#item-cart');
    clone.querySelector("[data-delete]").setAttribute("data-delete", productId);
    clone.querySelector("[data-name]").innerHTML = productName;
    clone.querySelector("[data-price]").innerHTML = "<strong>" + quantity + "</strong> "+productUnit+" a " + productPrice + " / Unit";
    clone.querySelector("[data-product_id_minus]").setAttribute("data-product_id_minus", productId);
    clone.querySelector("[data-product_id_plus]").setAttribute("data-product_id_plus", productId);
    //clone.querySelector("[data-quantity]").setAttribute("value", quantity);

    // Aquí asegúrate de actualizar el valor del input correctamente
    var quantityInput = clone.querySelector("[data-quantity]");
    if (quantityInput) {
        quantityInput.value = quantity; // Usa .value en lugar de setAttribute
    }

    var priceTotal;
    priceTotal = quantity * productPrice;

    clone.querySelector("[data-priceTotal]").innerHTML = priceTotal;

    $("#body-cart").append(clone);

    updateTotalOrder();

    // Asegúrate de que el input exista antes de disparar el evento
    if (quantityInput) {
        console.log("Vamos a lanzar el evento");
        $(quantityInput).trigger('input');
    }

}

function showData() {
    var numberPage = $(this).attr('data-item');
    console.log(numberPage);
    var type = $("#type_id").val();
    if (type === "f") {
        getDataTableNew(numberPage)
    } else {
        getData(numberPage)
    }
}

function showDataSearch() {
    getData(1)
}

function showDataSearchTable() {
    getDataTableNew(1);
}

function getDataTableNew($numberPage) {
    var category_id = $('#category_id').val();
    var product_search = $("#product_search").val();

    $.get('/dashboard/get/data/products/'+$numberPage, {
        category_id: category_id,
        product_search: product_search
    }, function(data) {
        if (data.data.length == 0) {
            renderDataTableEmptyNew();
        } else {
            renderDataTableNew(data);
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error(textStatus, errorThrown);
        toastr.error("Error al obtener los productos", "Error", {
            "closeButton": true,
            "progressBar": true
        });
    });
}

function renderDataTableEmptyNew() {
    $("#table-body").html('');
    $("#pagination").html('');
    $("#textPagination").html('No se encontraron productos.');
}

function renderDataTableNew(data) {
    $('#body-card, #pagination').empty();
    var p = data.pagination;
    $('#textPagination').text('Mostrando '+p.startRecord+' a '+p.endRecord+' de '+p.totalFilteredRecords+' productos.');
    var table = $('<table class="table table-bordered table-striped"><thead><tr><th>Imagen</th><th>Nombre</th><th>Precio</th><th>Unidad</th><th>Impuesto</th><th>Acción</th></tr></thead><tbody></tbody></table>');
    data.data.forEach(function (item) {
        var row = $('<tr>');
        var image = $('<img width="50" alt="">');
        preparePosProductImage(image[0], item);
        $('<td>').append(image).appendTo(row);
        $('<td>').text(item.full_name).appendTo(row);
        $('<td data-price>').text(item.price).appendTo(row);
        $('<td>').text(item.unit).appendTo(row);
        $('<td>').text(item.tax).appendTo(row);
        var actions = $('<div class="next-pos-product-actions">').appendTo($('<td>').appendTo(row));
        $('<button type="button" class="btn btn-outline-secondary btn-sm next-pos-icon" data-add_cart_special aria-label="Selección especial" title="Selección especial / presentaciones"><i class="fas fa-sliders-h" aria-hidden="true"></i></button>').attr({'data-product_id':item.id,'data-material_id':item.material_id,'data-product_source':item.source,'data-product_price':item.price,'data-product_stock':item.stock,'data-product_name':item.full_name,'data-product_unit':item.unit,'data-product_tax':item.tax,'data-product_type':item.type,'data-product_sku':item.sku,'data-product_barcode':item.barcode}).appendTo(actions);
        $('<button type="button" class="btn btn-primary btn-sm next-pos-icon" data-add_cart aria-label="Agregar al carrito" title="Agregar al carrito"><i class="fas fa-cart-plus" aria-hidden="true"></i></button>').attr({'data-product_id':item.id,'data-material_id':item.material_id,'data-product_source':item.source,'data-product_price':item.price,'data-product_stock':item.stock,'data-product_name':item.full_name,'data-product_unit':item.unit,'data-product_tax':item.tax,'data-product_type':item.type,'data-product_sku':item.sku,'data-product_barcode':item.barcode}).appendTo(actions);
        $('<button type="button" class="btn btn-outline-secondary btn-sm next-pos-icon" data-pos-stock aria-label="Consultar stock" title="Consultar existencias en otros almacenes"><i class="fas fa-search-location" aria-hidden="true"></i></button>').appendTo(actions);
        window.posCatalogPrepareProduct(row[0], item);
        table.find('tbody').append(row);
    });
    $('#body-card').append($('<div class="table-responsive">').append(table));
    renderPaginationNew(p);
}


function renderPaginationNew(pagination) {
    $("#pagination").html('');

    if (pagination.currentPage > 1)
    {
        renderPreviousPage(pagination.currentPage-1);
    }

    if (pagination.totalPages > 1)
    {
        if (pagination.currentPage > 3)
        {
            renderItemPage(1);

            if (pagination.currentPage > 4) {
                renderDisabledPage();
            }
        }

        for (var i = Math.max(1, pagination.currentPage - 2); i <= Math.min(pagination.totalPages, pagination.currentPage + 2); i++)
        {
            renderItemPage(i, pagination.currentPage);
        }

        if (pagination.currentPage < pagination.totalPages - 2)
        {
            if (pagination.currentPage < pagination.totalPages - 3)
            {
                renderDisabledPage();
            }
            renderItemPage(i, pagination.currentPage);
        }

    }

    if (pagination.currentPage < pagination.totalPages)
    {
        renderNextPage(pagination.currentPage+1);
    }
}

function getData($numberPage) {
    var category_id = $('#category_id').val();
    var product_search = $("#product_search").val();
    //console.log(nameCategoryEquipment);
    $.get('/dashboard/get/data/products/'+$numberPage, {
        category_id: category_id,
        product_search: product_search
    }, function(data) {
        if ( data.data.length == 0 )
        {
            renderDataEmpty(data);
        } else {
            renderData(data);
        }


    }).fail(function(jqXHR, textStatus, errorThrown) {
        // Función de error, se ejecuta cuando la solicitud GET falla
        console.error(textStatus, errorThrown);
        if (jqXHR.responseJSON.message && !jqXHR.responseJSON.errors) {
            toastr.error(jqXHR.responseJSON.message, 'Error', {
                "closeButton": true,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "2000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            });
        }
        for (var property in jqXHR.responseJSON.errors) {
            toastr.error(jqXHR.responseJSON.errors[property], 'Error', {
                "closeButton": true,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "2000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            });
        }
    }, 'json')
        .done(function() {
            // Configuración de encabezados
            var headers = {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            };
            $.ajaxSetup({
                headers: headers
            });
        });
}

function renderDataEmpty(data) {
    var dataAccounting = data.data;
    var pagination = data.pagination;

    $("#body-card").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' productos.');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    renderDataCardEmpty();
}

function renderData(data) {
    var dataAccounting = data.data;
    var pagination = data.pagination;

    $("#body-card").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' productos.');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    for (let j = 0; j < dataAccounting.length ; j++) {
        renderDataCard(dataAccounting[j]);
    }

    if (pagination.currentPage > 1)
    {
        renderPreviousPage(pagination.currentPage-1);
    }

    if (pagination.totalPages > 1)
    {
        if (pagination.currentPage > 3)
        {
            renderItemPage(1);

            if (pagination.currentPage > 4) {
                renderDisabledPage();
            }
        }

        for (var i = Math.max(1, pagination.currentPage - 2); i <= Math.min(pagination.totalPages, pagination.currentPage + 2); i++)
        {
            renderItemPage(i, pagination.currentPage);
        }

        if (pagination.currentPage < pagination.totalPages - 2)
        {
            if (pagination.currentPage < pagination.totalPages - 3)
            {
                renderDisabledPage();
            }
            renderItemPage(i, pagination.currentPage);
        }

    }

    if (pagination.currentPage < pagination.totalPages)
    {
        renderNextPage(pagination.currentPage+1);
    }
}

function renderDataCardO(data) {
    var clone = activateTemplate('#item-card');
    let url_image = document.location.origin + '/images/material/' + data.image;
    clone.querySelector("[data-image1]").setAttribute("src", url_image);
    /*clone.querySelector("[data-image2]").setAttribute("src", url_image);*/
    clone.querySelector("[data-name]").innerHTML = data.full_name;
    clone.querySelector("[data-price]").innerHTML = data.price;

    clone.querySelector("[data-add_cart]").setAttribute("data-product_id", data.id);
    clone.querySelector("[data-add_cart]").setAttribute("data-product_price", data.price);
    clone.querySelector("[data-add_cart]").setAttribute("data-product_stock", data.stock);
    clone.querySelector("[data-add_cart]").setAttribute("data-product_name", data.full_name);
    clone.querySelector("[data-add_cart]").setAttribute("data-product_unit", data.unit);
    clone.querySelector("[data-add_cart]").setAttribute("data-product_tax", data.tax);

    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_id", data.id);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_price", data.price);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_stock", data.stock);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_name", data.full_name);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_unit", data.unit);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_tax", data.tax);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_type", data.type);

    $("#body-card").append(clone);

    $('[data-toggle="tooltip"]').tooltip();
}

function preparePosProductImage(image, data) {
    var fallback = document.getElementById('formCreate').getAttribute('data-pos-image-fallback');
    image.alt = data.full_name || '';
    image.onerror = function () { this.onerror = null; this.src = fallback; };
    image.src = data.image_url || fallback;
}

function renderDataCard(data) {
    var clone = activateTemplate('#item-card');
    let url_image = document.location.origin + '/images/material/' + data.image;

    clone.querySelector("[data-image1]").setAttribute("src", data.image_url);
    /*clone.querySelector("[data-image2]").setAttribute("src", url_image);*/
    clone.querySelector("[data-name]").innerHTML = data.full_name;
    clone.querySelector("[data-price]").innerHTML = data.price;

    // Botón normal
    clone.querySelector("[data-add_cart]").setAttribute("data-product_id", data.id);
    clone.querySelector("[data-add_cart]").setAttribute("data-material_id", data.material_id || '');
    clone.querySelector("[data-add_cart]").setAttribute("data-product_source", data.source || '');
    clone.querySelector("[data-add_cart]").setAttribute("data-product_price", data.price);
    clone.querySelector("[data-add_cart]").setAttribute("data-product_stock", data.stock);
    clone.querySelector("[data-add_cart]").setAttribute("data-product_name", data.full_name);
    clone.querySelector("[data-add_cart]").setAttribute("data-product_unit", data.unit);
    clone.querySelector("[data-add_cart]").setAttribute("data-product_tax", data.tax);
    clone.querySelector("[data-add_cart]").setAttribute("data-product_type", data.type);
    clone.querySelector("[data-add_cart]").setAttribute("data-product_sku", data.sku || '');
    clone.querySelector("[data-add_cart]").setAttribute("data-product_barcode", data.barcode || '');

    // Botón especial
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_id", data.id);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-material_id", data.material_id || '');
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_source", data.source || '');
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_price", data.price);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_stock", data.stock);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_name", data.full_name);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_unit", data.unit);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_tax", data.tax);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_type", data.type);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_sku", data.sku || '');
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_barcode", data.barcode || '');

    window.posCatalogPrepareProduct(clone, data);
    var image = clone.querySelector('[data-image1]');
    preparePosProductImage(image, data);
    clone.querySelector('[data-name]').setAttribute('title', data.full_name || '');
    clone.querySelector('[data-pos-sku]').textContent = data.sku ? 'SKU ' + data.sku : '';
    clone.querySelector('[data-pos-presentation]').textContent = data.variant_text || '';
    var localStock = data.stock_available;
    clone.querySelector('[data-pos-local-stock]').textContent = localStock !== null && localStock !== undefined
        ? 'Stock local: ' + localStock + (data.unit ? ' ' + data.unit : '') : '';
    $("#body-card").append(clone);

    $('[data-toggle="tooltip"]').tooltip();
}

function renderDataCardEmpty() {
    var clone = activateTemplate('#item-card-empty');
    $("#body-card").append(clone);
}

function renderPreviousPage($numberPage) {
    var clone = activateTemplate('#previous-page');
    clone.querySelector("[data-item]").setAttribute('data-item', $numberPage);
    $("#pagination").append(clone);
}

function renderDisabledPage() {
    var clone = activateTemplate('#disabled-page');
    $("#pagination").append(clone);
}

function renderItemPage($numberPage, $currentPage) {
    var clone = activateTemplate('#item-page');
    if ( $numberPage == $currentPage )
    {
        clone.querySelector("[data-item]").setAttribute('data-item', $numberPage);
        clone.querySelector("[data-active]").setAttribute('class', 'page-item active');
        clone.querySelector("[data-item]").innerHTML = $numberPage;
    } else {
        clone.querySelector("[data-item]").setAttribute('data-item', $numberPage);
        clone.querySelector("[data-item]").innerHTML = $numberPage;
    }

    $("#pagination").append(clone);
}

function renderNextPage($numberPage) {
    var clone = activateTemplate('#next-page');
    clone.querySelector("[data-item]").setAttribute('data-item', $numberPage);
    $("#pagination").append(clone);
}

function activateTemplate(id) {
    var t = document.querySelector(id);
    return document.importNode(t.content, true);
}
