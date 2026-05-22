let $materials=[];
let $locations=[];
let $materialsComplete=[];
let $locationsComplete=[];
let $items=[];
let $materialSelected = null;
let $stockItemsVariantSelected = [];

$(document).ready(function () {
    $("#element_loader").LoadingOverlay("show", {
        background  : "rgba(236, 91, 23, 0.5)"
    });
    /*$.ajax({
        url: "/dashboard/get/materials/entry",
        type: 'GET',
        dataType: 'json',
        success: function (json) {
            for (var i=0; i<json.length; i++)
            {
                $materials.push(json[i].material);
                $materialsComplete.push(json[i]);
            }
            $("#element_loader").LoadingOverlay("hide", true);
        }
    });*/

    $.ajax({
        url: "/dashboard/get/materials/stock/items/entry",
        type: 'GET',
        dataType: 'json',
        success: function (json) {
            for (var i=0; i<json.length; i++)
            {
                $materials.push(json[i].material);
                $materialsComplete.push(json[i]);
            }
            $("#element_loader").LoadingOverlay("hide", true);
        }
    });

    /*$('#material_search').typeahead({
            hint: true,
            highlight: true, /!* Enable substring highlighting *!/
            minLength: 1 /!* Specify minimum characters required for showing suggestions *!/
        },
        {
            limit: 12,
            source: substringMatcher($materials)
        });*/

    //$('#btn-add').on('click', addItem);
    $('#btn-add').on('click', function () {

        if (!$materialSelected) {
            toastr.error('Debe seleccionar un material.', 'Error');
            return;
        }

        if ($materialSelected.has_variants === true) {
            abrirModalStockItemsVariant($materialSelected);
            return;
        }

        addItems();
    });

    $('#btn-submit').on('click', storeOrderPurchase);

    $(document).on('click', '[data-delete]', deleteItem);

    $formCreate = $("#formCreate");

    let materialSearchUrl = $('#material_search').data('url');

    console.log('URL materiales:', materialSearchUrl);

    $('#material_search').select2({
        placeholder: 'Buscar por SKU, código de barras, variante o material...',
        allowClear: true,
        width: '100%',
        minimumInputLength: 1,
        ajax: {
            url: materialSearchUrl,
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return {
                    search: params.term || ''
                };
            },
            processResults: function (data) {
                return {
                    results: data.map(function (item) {
                        return {
                            id: item.material_id,
                            text: item.material,
                            item: item
                        };
                    })
                };
            },
            cache: true
        },
        templateResult: formatMaterialResult,
        templateSelection: formatMaterialSelection
    });

    $(document).on('select2:select', '#material_search', function (e) {

        let selected = e.params.data;

        if (!selected || !selected.item) {
            toastr.error('Debe seleccionar un material', 'Error');
            return;
        }

        let material = selected.item;

        $materialSelected = material;

        console.log('Material padre seleccionado:', material);
        console.log('Variable global $materialSelected:', $materialSelected);

        $('#material_id').val(material.material_id);
        $('#stock_item_id').val('');

        if (material.has_variants === true) {
            prepararFormularioParaMaterialConVariantes(material);
            return;
        }

        prepararFormularioParaMaterialSimple(material);
    });

    $(document).on('select2:clear', '#material_search', function () {

        $('#material_id').val('');
        $('#stock_item_id').val('');

        $('#quantity').val('');

        $('#quantity').prop('readonly', false);
        $('#quantity').prop('disabled', false);

    });

    $(document).on('click', '#btnSaveStockItemsVariant', function () {

        let selectedRows = [];
        let hasError = false;

        $('#tbodyStockItemsVariant tr').each(function () {

            let row = $(this);
            let stockItemId = row.data('stock-item-id');

            if (!stockItemId) {
                return;
            }

            let quantityRaw = row.find('.variant-quantity').val();
            let priceRaw = row.find('.variant-price').val();

            let hasQuantity = quantityRaw !== null && quantityRaw !== '';

            row.removeClass('table-danger');
            row.find('.variant-quantity').removeClass('is-invalid');

            // Si no ingresó cantidad, ignoramos la fila
            if (!hasQuantity) {
                return;
            }

            let quantity = parseFloat(quantityRaw);
            let price = parseFloat(priceRaw) || 0;

            // Validar cantidad
            if (isNaN(quantity) || quantity <= 0 || !Number.isInteger(quantity)) {
                row.find('.variant-quantity').addClass('is-invalid');
                hasError = true;
                return;
            }

            // Buscar stock item seleccionado
            let stockItem = $stockItemsVariantSelected.find(function (item) {
                return parseInt(item.stock_item_id) === parseInt(stockItemId);
            });

            if (!stockItem) {
                hasError = true;
                return;
            }

            // Validar si ya existe en los items agregados
            let alreadyExists = $items.some(function (item) {
                return parseInt(item.stock_item_id) === parseInt(stockItem.stock_item_id);
            });

            if (alreadyExists) {
                row.addClass('table-danger');
                hasError = true;
                return;
            }

            selectedRows.push({
                stock_item_id: stockItem.stock_item_id,
                material_id: stockItem.material_id,
                variant_id: stockItem.variant_id,
                attribute_summary: stockItem.attribute_summary,
                sku: stockItem.sku,
                barcode: stockItem.barcode,
                display_name: stockItem.display_name,
                quantity: quantity,
                price: price
            });
        });

        if (hasError) {
            toastr.error(
                'Revise las variantes seleccionadas. La cantidad debe ser válida y no deben repetirse productos ya agregados.',
                'Error'
            );
            return;
        }

        if (selectedRows.length === 0) {
            toastr.warning(
                'Debe ingresar cantidad en al menos una variante.',
                'Atención'
            );
            return;
        }

        agregarStockItemsVariantAEntrada(selectedRows);

        $('#modalStockItems').modal('hide');
    });

    $('#btn-currency').on('switchChange.bootstrapSwitch', function (event, state) {

        if (this.checked) // if changed state is "CHECKED"
        {
            console.log($(this));
            $('.moneda').html('PEN');

        } else {
            console.log($(this));
            $('.moneda').html('USD');
        }
    });

    $(document).on('input', '[data-total]', function() {
        var total = parseFloat($(this).val());
        var price = parseFloat($(this).parent().parent().prev().prev().children().children().val());
        var quantity = parseFloat($(this).parent().parent().prev().prev().prev().children().children().val());
        var description = $(this).parent().parent().prev().prev().prev().prev().children().children().children().val();
        var id = $(this).parent().parent().prev().prev().prev().prev().prev().prev().children().children().children().val();

        $items = $items.filter(material => material.id_material != id);
        $items.push({'price': price, 'quantity':quantity ,'material': description, 'id_material': id, 'total': total });
        updateSummaryInvoice();
    });

    $(document).on('input', '[data-price2]', function() {
        var price = parseFloat($(this).parent().parent().prev().children().children().val());
        var quantity = parseFloat($(this).parent().parent().prev().prev().children().children().val());
        var description = $(this).parent().parent().prev().prev().prev().children().children().children().val();
        var id = $(this).parent().parent().prev().prev().prev().prev().prev().children().children().children().val();

        $items = $items.filter(material => material.id_material != id);
        $items.push({'price': price, 'quantity':quantity ,'material': description, 'id_material': id, 'total': quantity*price });
        updateSummaryInvoice();

    });

    $(document).on('input', '[data-price]', function() {
        var price = parseFloat($(this).val());
        var quantity = parseFloat($(this).parent().parent().prev().children().children().val());
        var description = $(this).parent().parent().prev().prev().children().children().children().val();
        var id = $(this).parent().parent().prev().prev().prev().prev().children().children().children().val();

        $items = $items.filter(material => material.id_material != id);
        $items.push({'price': price, 'quantity':quantity ,'material': description, 'id_material': id, 'total': quantity*price });
        updateSummaryInvoice();

    });

    $(document).on('input', '[data-quantity]', function() {
        var quantity = parseFloat($(this).val());
        var price = parseFloat($(this).parent().parent().next().children().children().val());
        var description = $(this).parent().parent().prev().children().children().children().val();
        var id = $(this).parent().parent().prev().prev().prev().children().children().children().val();

        $items = $items.filter(material => material.id_material != id);
        $items.push({'price': price, 'quantity':quantity ,'material': description, 'id_material': id, 'total': quantity*price });
        updateSummaryInvoice();
    });
});

// Initializing the typeahead
var substringMatcher = function(strs) {
    return function findMatches(q, cb) {
        var matches, substringRegex;

        // an array that will be populated with substring matches
        matches = [];

        // regex used to determine if a string contains the substring `q`
        substrRegex = new RegExp(q, 'i');

        // iterate through the pool of strings and for any string that
        // contains the substring `q`, add it to the `matches` array
        $.each(strs, function(i, str) {
            if (substrRegex.test(str)) {
                matches.push(str);
            }
        });

        cb(matches);
    };
};

let $formCreate;

function agregarStockItemsVariantAEntrada(selectedRows) {

    let material = $materialSelected;

    let totalGeneral = 0;

    selectedRows.forEach(function (row) {
        console.log(row);
        let quantity = parseFloat(row.quantity);
        let unitPrice = parseFloat(row.price);
        let totalPrice = parseFloat(unitPrice * quantity).toFixed(4);

        $items.push({
            'id': $items.length + 1,
            'price': unitPrice,
            'quantity': quantity,
            'material': row.display_name || material.material,
            'id_material': material.material_id,
            'stock_item_id': row.stock_item_id,
            'variant_id': row.variant_id,
            'total': totalPrice
        });

        updateSummaryInvoice();

        let subtotal = parseFloat(totalPrice / 1.18).toFixed(2);
        let taxes = parseFloat(subtotal * 0.18).toFixed(2);
        let total = parseFloat(totalPrice).toFixed(2);

        totalGeneral += parseFloat(total);

        renderTemplateMaterial(
            row.stock_item_id,
            row.sku,
            row.display_name,
            quantity,
            material.unit,
            unitPrice,
            subtotal,
            taxes,
            total
        );
    });

    limpiarFormularioEntradaMaterial();
}

function prepararFormularioParaMaterialSimple(material) {

    $('#stock_item_id').val(material.stock_item_id || '');

    // Cantidad activa
    $('#quantity').prop('readonly', false);
    $('#quantity').prop('disabled', false);

}

function prepararFormularioParaMaterialConVariantes(material) {

    $('#stock_item_id').val('');

    // Cantidad desactivada porque todavía no se eligió variante
    $('#quantity').val('');
    $('#quantity').prop('readonly', true);
    $('#quantity').prop('disabled', true);

    toastr.info('Este material tiene variantes. Presione agregar para ingresar cantidades por variante.', 'Material con variantes');
}

function formatMaterialResult(data) {
    if (data.loading) {
        return data.text;
    }

    let item = data.item;

    if (!item) {
        return data.text;
    }

    let unit = item.unit ? item.unit : '';
    let stock = item.stock_current !== undefined ? item.stock_current : 0;

    let badge = '';

    if (item.has_variants === true) {
        badge = `<span class="badge badge-info">Con variantes</span>`;
    } else {
        badge = `<span class="badge badge-secondary">Simple</span>`;
    }

    let html = `
        <div>
            <strong>${item.material}</strong> ${badge}<br>
            <small>Stock total: ${stock} ${unit}</small>
        </div>
    `;

    return $(html);
}

function formatMaterialSelection(data) {
    if (!data.item) {
        return data.text;
    }

    return data.item.material;
}

function abrirModalStockItemsVariant(material) {

    let url = $('#url_stock_items_entry_base').val()
        + '/' + material.material_id
        + '/stock-items-entry';

    $('#modalStockItemsTitle').text(material.material);
    $('#tbodyStockItemsVariant').html(`
        <tr>
            <td colspan="5" class="text-center text-muted">
                Cargando variantes...
            </td>
        </tr>
    `);

    $('#modalStockItems').modal('show');

    $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        success: function (response) {
            $stockItemsVariantSelected = response;
            renderStockItemsVariantTable(response);
        },
        error: function () {
            $('#tbodyStockItemsVariant').html(`
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        No se pudieron cargar las variantes.
                    </td>
                </tr>
            `);

            toastr.error('No se pudieron cargar las variantes.', 'Error');
        }
    });
}

function renderStockItemsVariantTable(stockItems) {

    let tbody = $('#tbodyStockItemsVariant');
    tbody.empty();

    if (!stockItems || stockItems.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="5" class="text-center text-muted">
                    Este material no tiene variantes activas.
                </td>
            </tr>
        `);
        return;
    }

    stockItems.forEach(function (item) {

        let row = `
            <tr data-stock-item-id="${item.stock_item_id}">
                <td>${item.attribute_summary || ''}</td>
                <td>${item.sku || ''}</td>
                <td>${item.barcode || ''}</td>
                <td>
                    <input type="number"
                           class="form-control form-control-sm variant-quantity"
                           min="0"
                           step="1"
                           placeholder="0">
                </td>
                <td>
                    <input type="number"
                           class="form-control form-control-sm variant-price"
                           min="0"
                           step="0.01"
                           value="${item.price || ''}"
                           placeholder="0.00" readonly>
                </td>
            </tr>
        `;

        tbody.append(row);
    });
}

function addItemO() {

    if( $('#material_search').val().trim() === '' )
    {
        toastr.error('Debe elegir un material', 'Error',
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
        return;
    }

    if( $('#quantity').val().trim() === '' || $('#quantity').val()<0 )
    {
        toastr.error('Debe ingresar una cantidad', 'Error',
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
        return;
    }

    let material_name = $('#material_search').val();
    let material_quantity = $('#quantity').val();

    let material = $materialsComplete.find( material => material.material.trim() === material_name.trim() );
    console.log(material);
    let id = material.id;
    let code = material.code;
    let description = material_name;
    let quantity = material_quantity;
    let price = parseFloat(material.price);

    let flag = false;

    $('[data-id]').each(function(e){
        if( $(this).val() == id ) {
            toastr.error('Ya esta agregado este material.', 'Error',
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
            flag = true;
            return false;
        }
    });

    if ( !flag )
    {
        $items.push({'price': price, 'quantity':quantity ,'material': description, 'id_material': id, 'total': quantity*price });
        $('#material_search').val('');
        $('#quantity').val(0);
        renderTemplateMaterial(id, code, description, quantity, price);
        updateSummaryInvoice();
    }

}

function addItems() {

    if (!$materialSelected) {
        toastr.error('Debe elegir un material', 'Error', toastrOptions());
        return;
    }

    let material = $materialSelected;

    if (material.has_variants === true) {
        abrirModalStockItemsVariant(material);
        return;
    }

    let quantity = $('#quantity').val();
    let material_price_raw = material.price;

    if (!quantity || quantity.trim() === '' || parseFloat(quantity) <= 0) {
        toastr.error('Debe ingresar una cantidad válida', 'Error', toastrOptions());
        return;
    }

    if (!material_price_raw || parseFloat(material_price_raw) <= 0) {
        toastr.error('Debe ingresar un precio adecuado', 'Error', toastrOptions());
        return;
    }

    quantity = parseFloat(quantity);

    // Evitar stockItem repetido
    let alreadyExists = $items.some(function (item) {
        return parseInt(item.stock_item_id) === parseInt(material.stock_item_id);
    });

    if (alreadyExists) {
        toastr.danger('Este producto ya fue agregado a la orden.', 'Atención', toastrOptions());
        return;
    }

    let unit_price = parseFloat(material_price_raw).toFixed(2);
    let total_price = parseFloat(material_price_raw * quantity).toFixed(4);

    $items.push({
        'id': $items.length + 1,
        'price': unit_price,
        'quantity': quantity,
        'material': material.material,
        'id_material': material.material_id,
        'stock_item_id': material.stock_item_id,
        'total': quantity * unit_price
    });

    updateSummaryInvoice();

    let subtotal = parseFloat(total_price / 1.18).toFixed(2);
    let taxes = parseFloat(subtotal * 0.18).toFixed(2);
    let total = parseFloat(total_price).toFixed(2);

    renderTemplateMaterial(
        material.stock_item_id || material.material_id,
        material.stock_item_sku || material.code || '',
        material.material,
        quantity,
        material.unit,
        parseFloat(unit_price).toFixed(2),
        subtotal,
        taxes,
        total
    );

    limpiarFormularioEntradaMaterial();
}

function limpiarFormularioEntradaMaterial() {

    $('#material_search').val(null).trigger('change');

    $('#material_id').val('');
    $('#stock_item_id').val('');

    $('#quantity').val('');
    $('#price').val('');
    $('#almacen').val('');
    $('#date_vence').val('');
    $('#lot').val('');

    $materialSelected = null;
    $stockItemsVariantSelected = [];

    $('#quantity').prop('readonly', false);
    $('#quantity').prop('disabled', false);

    $('#price').prop('readonly', false);
    $('#price').prop('disabled', false);

    $('#lot').prop('readonly', false);
    $('#lot').prop('disabled', false);

    $('#date_vence').prop('readonly', false);
    $('#date_vence').prop('disabled', false);

    $('#almacen').prop('readonly', false);
    $('#almacen').prop('disabled', false);

    $('#btn-grouped2').bootstrapSwitch('state', true, true);
    $('#btn-grouped2').bootstrapSwitch('disabled', true);
}

function toastrOptions() {
    return {
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
    };
}

function updateSummaryInvoice() {
    var subtotal = 0;
    var total = 0;
    var taxes = 0;

    for ( var i=0; i<$items.length; i++ )
    {
        subtotal += (parseFloat($items[i].total))/1.18 ;
        total += parseFloat($items[i].total);
        taxes = subtotal*0.18;
    }

    /*$('#subtotal').html(subtotal.toFixed(2));
    $('#taxes').html(taxes.toFixed(2));
    $('#total').html(total.toFixed(2));*/
    $('#subtotal').val(subtotal.toFixed(2));
    $('#taxes').val(taxes.toFixed(2));
    $('#total').val(total.toFixed(2));

}

function calculateTotal(e) {
    var cantidad = e.value;
    var precio = e.parentElement.parentElement.nextElementSibling.firstElementChild.firstElementChild.value;
    e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = (parseFloat(cantidad)*parseFloat(precio)).toFixed(2);
    updateSummaryInvoice();
}

function calculateTotal2(e) {
    var precio = e.value;
    var cantidad = e.parentElement.parentElement.previousElementSibling.firstElementChild.firstElementChild.value;
    e.parentElement.parentElement.nextElementSibling.firstElementChild.firstElementChild.value = (parseFloat(precio)/1.18).toFixed(2);
    e.parentElement.parentElement.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = (parseFloat(cantidad)*parseFloat(precio)).toFixed(2);
    updateSummaryInvoice();
}

function calculateTotal3(e) {
    var precioSI = e.value;
    var precioCI = (parseFloat(precioSI)*1.18).toFixed(2);
    console.log(precioSI);
    console.log(precioCI);
    var cantidad = e.parentElement.parentElement.previousElementSibling.previousElementSibling.firstElementChild.firstElementChild.value;
    console.log(cantidad);
    e.parentElement.parentElement.nextElementSibling.firstElementChild.firstElementChild.value = (parseFloat(cantidad)*parseFloat(precioCI)).toFixed(2);
    e.parentElement.parentElement.previousElementSibling.firstElementChild.firstElementChild.value = precioCI;
    updateSummaryInvoice();
}

function deleteItem() {
    var materialId = $(this).data('delete');
    console.log(materialId);
    $items = $items.filter(material => material.stock_item_id != materialId);
    $(this).parent().parent().remove();

    updateSummaryInvoice();
}

function renderTemplateMaterial(stock_item_id, stock_item_sku, material, quantity, unit, unit_price, subtotal,taxes,total) {
    var clone = activateTemplate('#materials-selected');
    clone.querySelector("[data-id]").setAttribute('value', stock_item_id);
    clone.querySelector("[data-code]").setAttribute('value', stock_item_sku);
    clone.querySelector("[data-description]").setAttribute('value', material);
    clone.querySelector("[data-quantity]").setAttribute('value', (parseFloat(quantity)).toFixed(2) );
    clone.querySelector("[data-quantity]").setAttribute('max', quantity);
    clone.querySelector("[data-price]").setAttribute('value', (parseFloat(unit_price)).toFixed(2) );
    clone.querySelector("[data-price2]").setAttribute('value', (parseFloat(unit_price)/1.18).toFixed(2) );
    clone.querySelector("[data-total]").setAttribute('value', (parseFloat(unit_price)*parseFloat(quantity)).toFixed(2) );
    clone.querySelector("[data-delete]").setAttribute('data-delete', stock_item_id);
    $('#body-materials').append(clone);
}

function activateTemplate(id) {
    var t = document.querySelector(id);
    return document.importNode(t.content, true);
}

function storeOrderPurchase() {
    event.preventDefault();
    // Obtener la URL
    $("#btn-submit").attr("disabled", true);

    /*var subtotal_send = $('#subtotal').html();
    var taxes_send = $('#taxes').html();
    var total_send = $('#total').html();*/
    var subtotal_send = $('#subtotal').val();
    var taxes_send = $('#taxes').val();
    var total_send = $('#total').val();

    /*var arrayId = [];
    var arrayCode = [];
    var arrayDescription = [];
    var arrayQuantity = [];
    var arrayPrice = [];

    $('[data-id]').each(function(e){
        arrayId.push($(this).val());
    });
    $('[data-code]').each(function(e){
        arrayCode.push($(this).val());
    });
    $('[data-description]').each(function(e){
        arrayDescription.push($(this).val());
    });
    $('[data-quantity]').each(function(e){
        arrayQuantity.push($(this).val());
    });
    $('[data-price]').each(function(e){
        arrayPrice.push($(this).val());
    });

    var itemsArray = [];
    for (let i = 0; i < arrayId.length; i++) {
        itemsArray.push({'id':arrayId[i], 'code':arrayCode[i], 'description':arrayDescription[i], 'quantity': arrayQuantity[i], 'price': arrayPrice[i]});
    }*/

    var createUrl = $formCreate.data('url');
    var items = JSON.stringify($items);
    var form = new FormData($('#formCreate')[0]);
    form.append('items', items);
    form.append('subtotal_send', subtotal_send);
    form.append('taxes_send', taxes_send);
    form.append('total_send', total_send);
    $.ajax({
        url: createUrl,
        method: 'POST',
        data: form,
        processData:false,
        contentType:false,
        success: function (data) {
            console.log(data);
            toastr.success(data.message, 'Éxito',
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
            setTimeout( function () {
                $("#btn-submit").attr("disabled", false);
                location.reload();
            }, 2000 )
        },
        error: function (data) {
            if( data.responseJSON.message && !data.responseJSON.errors )
            {
                toastr.error(data.responseJSON.message, 'Error',
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
            }
            for ( var property in data.responseJSON.errors ) {
                toastr.error(data.responseJSON.errors[property], 'Error',
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
            }

            $("#btn-submit").attr("disabled", false);
        },
    });
}
