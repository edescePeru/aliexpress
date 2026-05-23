let $materials=[];
let $locations=[];
let $materialsComplete=[];
let $locationsComplete=[];
let $items=[];
let $materialSelected = null;
let $stockItemsVariantSelected = [];

$(document).ready(function () {

    fillItems();

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
            highlight: true,
            minLength: 1
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

    $(document).on('click', '[data-edit]', editItem);

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

    $(document).on('input', '[data-quantity]', function () {
        updateMaterialRow($(this).closest('.material-row'), 'quantity');
    });

    $(document).on('input', '[data-price]', function () {
        updateMaterialRow($(this).closest('.material-row'), 'price');
    });

    $(document).on('input', '[data-price2]', function () {
        updateMaterialRow($(this).closest('.material-row'), 'price2');
    });

    $(document).on('input', '[data-total]', function () {
        updateMaterialRow($(this).closest('.material-row'), 'total');
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

function fillItems() {
    $("#element_loader").LoadingOverlay("show", {
        background: "rgba(236, 91, 23, 0.5)"
    });

    $items = [];

    $('.material-row').each(function () {
        const $row = $(this);

        const detailId = $row.find('[data-detail-id]').first().data('detail-id') || null;

        const stockItemId = parseInt($row.find('[data-id]').val());
        const stockItemSku = $row.find('[data-code]').val();
        const description = $row.find('[data-description]').val();

        const quantity = parseFloat($row.find('[data-quantity]').val()) || 0;
        const price = parseFloat($row.find('[data-price]').val()) || 0;
        const priceWithoutIgv = parseFloat($row.find('[data-price2]').val()) || 0;
        const total = parseFloat($row.find('[data-total]').val()) || 0;

        $items.push({
            detail_id: detailId,

            stock_item_id: stockItemId,
            stock_item_sku: stockItemSku,

            material: description,

            price: price,
            price_without_igv: priceWithoutIgv,
            quantity: quantity,
            total: total
        });
    });

    $("#element_loader").LoadingOverlay("hide", true);
}

function agregarStockItemsVariantAEntrada(selectedRows) {

    let material = $materialSelected;

    let totalGeneral = 0;

    selectedRows.forEach(function (row) {
        console.log(row);
        let quantity = parseFloat(row.quantity);
        let unitPrice = parseFloat(row.price);
        let totalPrice = parseFloat(unitPrice * quantity).toFixed(4);

        $items.push({
            'detail_id': '',
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
        $items.push({'detail_id':'', 'price': price, 'quantity':quantity ,'material': description, 'id_material': id, 'total': quantity*price });
        $('#material_search').val('');
        $('#quantity').val(0);
        renderTemplateMaterial(id, code, description, quantity, price);
        $('#btn-submit').removeClass( "btn-outline-success" );
        $('#btn-submit').addClass( "btn-outline-danger" );
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
        'detail_id': '',
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

    $('#subtotal').val(subtotal.toFixed(2));
    $('#taxes').val(taxes.toFixed(2));
    $('#total').val(total.toFixed(2));

}

function editItem() {
    var button = $(this);
    var detail_id = $(this).attr('data-edit');
    var total = parseFloat($(this).parent().parent().prev().children().children().val());
    var price = parseFloat($(this).parent().parent().prev().prev().prev().children().children().val());
    var quantity = parseFloat($(this).parent().parent().prev().prev().prev().prev().children().children().val());
    var description = $(this).parent().parent().prev().prev().prev().prev().prev().children().children().children().val();
    var id = $(this).parent().parent().prev().prev().prev().prev().prev().prev().prev().children().children().children().val();
    var modifiedItem = [];
    modifiedItem.push({'detail_id':detail_id, 'price': price, 'quantity':quantity ,'material': description, 'id_material': id, 'total': total });
    console.log(modifiedItem);
    var valParam = JSON.stringify(modifiedItem);
    $.confirm({
        icon: 'fas fa-smile',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'green',
        title: 'Guardar cambios',
        content: 'Se guardará en la base de datos',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                action: function (e) {
                    $.ajax({
                        url: '/dashboard/update/detail/order/purchase/normal/'+detail_id,
                        method: 'POST',
                        data: { items: valParam },
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        success: function (data) {
                            console.log(data);
                            button.removeClass('btn-outline-warning');
                            button.addClass( "btn-outline-success" );
                            updateSummaryInvoice();
                            $('#btn-submit').removeClass( "btn-outline-success" );
                            $('#btn-submit').addClass( "btn-outline-danger" );
                            $.alert(data.message);
                            setTimeout( function () {
                                //location.reload();
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


                        },
                    });

                },
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Modificación cancelada. Si hay una modificación, por favor guarde los cambios.");
                },
            },
        },
    });
}

function deleteItem() {
    var button = $(this);

    var stockItemId = button.data('stock-item');
    var idDetail = button.data('delete');

    if (idDetail) {
        $.confirm({
            icon: 'fas fa-frown',
            theme: 'modern',
            closeIcon: true,
            animation: 'zoom',
            type: 'red',
            title: 'Eliminar detalle',
            content: 'Se eliminará en la base de datos',
            buttons: {
                confirm: {
                    text: 'CONFIRMAR',
                    action: function () {
                        $.ajax({
                            url: '/dashboard/destroy/detail/order/purchase/normal/' + idDetail + '/stock-item/' + stockItemId,
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            processData: false,
                            contentType: false,
                            success: function (data) {
                                $items = $items.filter(item =>
                                    parseInt(item.stock_item_id) !== parseInt(stockItemId)
                                );

                                button.closest('.material-row').remove();

                                updateSummaryInvoice();

                                $('#btn-submit')
                                    .removeClass('btn-outline-success')
                                    .addClass('btn-outline-danger');

                                $.alert(data.message);
                            },
                            error: function (data) {
                                if (data.responseJSON && data.responseJSON.message && !data.responseJSON.errors) {
                                    toastr.error(data.responseJSON.message, 'Error', {
                                        closeButton: true,
                                        progressBar: true,
                                        positionClass: 'toast-top-right',
                                        timeOut: '2000'
                                    });
                                }

                                if (data.responseJSON && data.responseJSON.errors) {
                                    for (var property in data.responseJSON.errors) {
                                        toastr.error(data.responseJSON.errors[property], 'Error', {
                                            closeButton: true,
                                            progressBar: true,
                                            positionClass: 'toast-top-right',
                                            timeOut: '2000'
                                        });
                                    }
                                }
                            }
                        });
                    },
                },
                cancel: {
                    text: 'CANCELAR',
                    action: function () {
                        $.alert('Eliminación cancelada.');
                    },
                },
            },
        });

    } else {
        $items = $items.filter(item =>
            parseInt(item.stock_item_id) !== parseInt(stockItemId)
        );

        button.closest('.material-row').remove();

        updateSummaryInvoice();

    }
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
    clone.querySelector("[data-delete]").setAttribute('data-delete', "");
    clone.querySelector("[data-delete]").setAttribute('data-stock-item', stock_item_id);
    $('#body-materials').append(clone);
}

function updateMaterialRow($row, changedField) {
    const stockItemId = parseInt($row.find('[data-id]').val());
    const stockItemSku = $row.find('[data-code]').val();
    const description = $row.find('[data-description]').val();

    let quantity = parseFloat($row.find('[data-quantity]').val()) || 0;
    let priceWithIgv = parseFloat($row.find('[data-price]').val()) || 0;
    let priceWithoutIgv = parseFloat($row.find('[data-price2]').val()) || 0;
    let total = parseFloat($row.find('[data-total]').val()) || 0;

    if (changedField === 'quantity') {
        total = quantity * priceWithIgv;
        $row.find('[data-total]').val(total.toFixed(2));
    }

    if (changedField === 'price') {
        priceWithoutIgv = priceWithIgv / 1.18;
        total = quantity * priceWithIgv;

        $row.find('[data-price2]').val(priceWithoutIgv.toFixed(2));
        $row.find('[data-total]').val(total.toFixed(2));
    }

    if (changedField === 'price2') {
        priceWithIgv = priceWithoutIgv * 1.18;
        total = quantity * priceWithIgv;

        $row.find('[data-price]').val(priceWithIgv.toFixed(2));
        $row.find('[data-total]').val(total.toFixed(2));
    }

    if (changedField === 'total') {
        if (quantity > 0) {
            priceWithIgv = total / quantity;
            priceWithoutIgv = priceWithIgv / 1.18;

            $row.find('[data-price]').val(priceWithIgv.toFixed(2));
            $row.find('[data-price2]').val(priceWithoutIgv.toFixed(2));
        }
    }

    const existing = $items.find(item => parseInt(item.stock_item_id) === stockItemId);

    if (existing) {
        existing.quantity = quantity;
        existing.price = priceWithIgv;
        existing.total = total;
        existing.material = description;
        existing.stock_item_id = stockItemId;
        existing.stock_item_sku = stockItemSku;
    } else {
        $items.push({
            stock_item_id: stockItemId,
            stock_item_sku: stockItemSku,
            material: description,
            quantity: quantity,
            price: priceWithIgv,
            total: total
        });
    }

    updateSummaryInvoice();
}

function activateTemplate(id) {
    var t = document.querySelector(id);
    return document.importNode(t.content, true);
}

function storeOrderPurchase() {
    event.preventDefault();
    // Obtener la URL
    $("#btn-submit").attr("disabled", true);

    var subtotal_send = $('#subtotal').val();
    var taxes_send = $('#taxes').val();
    var total_send = $('#total').val();

    var state = $('#btn-currency').bootstrapSwitch('state');
    var regularize = $('#btn-regularize').bootstrapSwitch('state');
    console.log(regularize);

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
    form.append('state', state);
    form.append('regularize', regularize);
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
                $('#btn-submit').removeClass( "btn-outline-danger" );
                $('#btn-submit').addClass( "btn-outline-success" );
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
            $('#btn-submit').removeClass( "btn-outline-danger" );
            $('#btn-submit').addClass( "btn-outline-success" );
            $("#btn-submit").attr("disabled", false);
        },
    });
}
