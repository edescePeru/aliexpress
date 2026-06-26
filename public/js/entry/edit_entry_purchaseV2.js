let $materials=[];
let $locations=[];
let $materialsComplete=[];
let $locationsComplete=[];
let $items=[];
let $material;

let $materialSelected = null;
let $stockItemsVariantSelected = [];

$(document).ready(function () {

    $('#btn-grouped2').bootstrapSwitch();

    $('#btn-grouped2').bootstrapSwitch('state', true, true);
    $('#btn-grouped2').bootstrapSwitch('disabled', true);

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

        }
    });
    $.ajax({
        url: "/dashboard/get/locations",
        type: 'GET',
        dataType: 'json',
        success: function (json) {
            for (var i=0; i<json.length; i++)
            {
                $locations.push(json[i].location);
                $locationsComplete.push(json[i]);
            }

        }
    });

    /*$('.typeahead').typeahead({
            hint: true,
            highlight: true, /!* Enable substring highlighting *!/
            minLength: 1 /!* Specify minimum characters required for showing suggestions *!/
        },
        {
            limit: 12,
            source: substringMatcher($materials)
        });*/

    //$('#btn-add').on('click', addItems);

    $('#btn-add').on('click', function () {

        if (!$materialSelected) {
            toastr.error('Debe seleccionar un material asdasd.', 'Error');
            return;
        }

        if ($materialSelected.has_variants === true) {
            abrirModalStockItemsVariant($materialSelected);
            return;
        }

        addItems();
    });

    $modalAddItems = $('#modalAddItems');

    $modalAddGroupItems = $('#modalAddGroupItems');

    $('#btn-saveItems').on('click', saveTableItems);

    $('#btn-saveGroupItems').on('click', saveTableItems);

    $(document).on('click', '[data-delete]', deleteItem);

    $modalImage = $('#modalImage');
    $(document).on('click', '[data-image]', showImage);
    $(document).on('click', '[data-deleteOld]', deleteItemOld);
    $('#btn-submit').on('click', saveNewDetails);

    $formEdit = $("#formEdit");
    $formEdit.on('submit', updateOrderPurchase);

    $('#almacen').typeahead('destroy');
    $('#almacen').typeahead({
            hint: true,
            highlight: true, /* Enable substring highlighting */
            minLength: 1 /* Specify minimum characters required for showing suggestions */
        },
        {
            limit: 12,
            source: substringMatcher($locations)
        });
    //var l = $locations[0];
    $("#almacen").typeahead('val',$locations[0]).trigger('change');

    /*$(document).on('typeahead:select', '#material_search', function(ev, suggestion) {
        var select_material = $(this);
        console.log($(this).val());
        // TODO: Tomar el texto no el val()
        var material_search = select_material.val();
        console.log(material_search);
        //$material = $materials.find( mat=>mat.full_name.trim().toLowerCase() === material_search.trim().toLowerCase() );

        $material = $materialsComplete.find(mat =>
            mat.material.trim().toLowerCase() === material_search.trim().toLowerCase() &&
            mat.enable_status === 1
        );
        console.log($material);
        if( $material === undefined )
        {
            toastr.error('Debe seleccionar un material', 'Error',
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
        console.log($material.tipo_venta_id);
        if ( $material.tipo_venta_id === null )
        {

        } else {
            switch($material.tipo_venta_id) {

                case 1:
                    // SIN ITEMS
                    // If con perecible o no perecible
                    if ( $material.perecible == 'n' )
                    {
                        $('#date_vence').prop('readonly', true);
                        $('#date_vence').prop('disabled', true);
                    } else {
                        $('#date_vence').prop('readonly', false);
                        $('#date_vence').prop('disabled', false);
                    }
                    $("#almacen").typeahead('val',$locations[0]).trigger('change');
                    $('#almacen').prop('readonly', false);
                    $('#almacen').prop('disabled', false);
                    $('#btn-grouped2').bootstrapSwitch('state', false, true);
                    $('#btn-grouped2').bootstrapSwitch('disabled', true);
                    break;
                case 2:
                    // AL PESO
                    // If con perecible o no perecible
                    if ( $material.perecible == 'n' )
                    {
                        $('#date_vence').prop('readonly', true);
                        $('#date_vence').prop('disabled', true);
                    } else {
                        $('#date_vence').prop('readonly', false);
                        $('#date_vence').prop('disabled', false);
                    }
                    $("#almacen").typeahead('val',$locations[0]).trigger('change');
                    $('#almacen').prop('readonly', false);
                    $('#almacen').prop('disabled', false);
                    $('#btn-grouped2').bootstrapSwitch('state', false, true);
                    $('#btn-grouped2').bootstrapSwitch('disabled', true);
                    break;
                case 3:
                    // ITEMEABLE
                    if ( $material.perecible == 'n' )
                    {
                        $('#date_vence').prop('readonly', true);
                        $('#date_vence').prop('disabled', true);
                    } else {
                        $('#date_vence').prop('readonly', false);
                        $('#date_vence').prop('disabled', false);
                    }
                    $('#almacen').prop('readonly', true);
                    $('#almacen').prop('disabled', true);
                    $('#btn-grouped2').bootstrapSwitch('disabled', false);
                    $("#almacen").typeahead('val',$locations[0]).trigger('change');
                    break;

            }
            //var idMaterial = $(this).select2('data').id;
            //$renderMaterial = $(this).parent().parent().parent().parent().next().next().next();
            //$modalAddMaterial.modal('show');
        }
    });*/

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

        const isItemeable = parseInt(material.tipo_venta_id) === 3;

        // Por defecto, agrupado en Sí.
        $('#btn-grouped2').bootstrapSwitch('state', true, true);

        // Solo puede modificarse si el material es itemeable.
        $('#btn-grouped2').bootstrapSwitch('disabled', !isItemeable);

        aplicarLoteYFechaVence(material);

        if (material.has_variants === true) {
            prepararFormularioParaMaterialConVariantes(material);
            return;
        }

        prepararFormularioParaMaterialSimple(material);
    });

    $(document).on('select2:clear', '#material_search', function () {

        $('#material_id').val('');
        $('#stock_item_id').val('');

        $('#lot').val('');
        $('#date_vence').val('');
        $('#quantity').val('');
        $('#price').val('');

        $('#lot').prop('readonly', false);
        $('#lot').prop('disabled', false);

        $('#date_vence').prop('readonly', false);
        $('#date_vence').prop('disabled', false);

        $('#quantity').prop('readonly', false);
        $('#quantity').prop('disabled', false);

        $('#price').prop('readonly', false);
        $('#price').prop('disabled', false);

        $('#almacen').prop('readonly', false);
        $('#almacen').prop('disabled', false);

        $('#btn-grouped2').bootstrapSwitch('state', true, true);
        $('#btn-grouped2').bootstrapSwitch('disabled', true);
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
            let hasPrice = priceRaw !== null && priceRaw !== '';

            row.find('.variant-quantity, .variant-price').removeClass('is-invalid');

            if ((hasQuantity && !hasPrice) || (!hasQuantity && hasPrice)) {
                row.find('.variant-quantity').addClass(!hasQuantity ? 'is-invalid' : '');
                row.find('.variant-price').addClass(!hasPrice ? 'is-invalid' : '');

                hasError = true;
                return;
            }

            if (!hasQuantity && !hasPrice) {
                return;
            }

            let quantity = parseFloat(quantityRaw);
            let price = parseFloat(priceRaw);

            if (isNaN(quantity) || quantity <= 0) {
                row.find('.variant-quantity').addClass('is-invalid');
                hasError = true;
                return;
            }

            if (isNaN(price) || price <= 0) {
                row.find('.variant-price').addClass('is-invalid');
                hasError = true;
                return;
            }

            let stockItem = $stockItemsVariantSelected.find(function (item) {
                return parseInt(item.stock_item_id) === parseInt(stockItemId);
            });

            if (!stockItem) {
                hasError = true;
                return;
            }

            const material = $materialSelected;
            const isItemeable = material && parseInt(material.tipo_venta_id) === 3;
            const isGrouped = $('#btn-grouped2').bootstrapSwitch('state');

            let codes = [];

            if (isItemeable && !isGrouped) {

                codes = $codigosPorVariante[stockItemId] || [];

                if (codes.length !== quantity) {
                    row.find('.btn-registrar-series-variante')
                        .removeClass('btn-success')
                        .addClass('btn-outline-danger');

                    toastr.error(
                        'Debe registrar todas las series de la variante: ' +
                        (stockItem.display_name || stockItem.attribute_summary || ''),
                        'Error'
                    );

                    hasError = true;
                    return;
                }
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
                price: price,

                codes: codes,
                is_grouped: isGrouped ? 1 : 0
            });
        });

        if (hasError) {
            toastr.error('Complete cantidad y precio costo en las variantes seleccionadas.', 'Error');
            return;
        }

        if (selectedRows.length === 0) {
            toastr.warning('Debe ingresar cantidad y precio en al menos una variante.', 'Atención');
            return;
        }

        agregarStockItemsVariantAEntrada(selectedRows);

        $('#modalStockItems').modal('hide');
    });

    $(document).on('click', '#btnGuardarCodigosMaterialSimple', function () {

        if (!$registroPendienteMaterialSimple) {
            toastr.error('No existe información pendiente para registrar.', 'Error', toastrOptions());
            return;
        }

        const codigos = [];
        const codigosNormalizados = [];
        let tieneVacios = false;
        let tieneDuplicados = false;

        $('#tbodyCodigosMaterialSimple .input-codigo-material-simple').each(function () {
            const codigo = ($(this).val() || '').trim();
            const codigoNormalizado = codigo.toUpperCase();

            if (!codigo) {
                tieneVacios = true;
                $(this).addClass('is-invalid');
                return;
            }

            if (codigosNormalizados.includes(codigoNormalizado)) {
                tieneDuplicados = true;
                $(this).addClass('is-invalid');
                return;
            }

            $(this).removeClass('is-invalid');

            codigos.push(codigo);
            codigosNormalizados.push(codigoNormalizado);
        });

        if (tieneVacios) {
            toastr.error(
                'Debe ingresar todos los códigos o series.',
                'Error',
                toastrOptions()
            );
            return;
        }

        if (tieneDuplicados) {
            toastr.error(
                'No puede registrar códigos o series repetidos.',
                'Error',
                toastrOptions()
            );
            return;
        }

        agregarMaterialSimpleConCodigosManuales(
            $registroPendienteMaterialSimple,
            codigos
        );

        $('#modalCodigosMaterialSimple').modal('hide');
        $registroPendienteMaterialSimple = null;
    });

    $(document).on('input change', '.variant-quantity', function () {
        const row = $(this).closest('tr');

        actualizarBotonSeriesVariante(row);
    });

    $(document).on('click', '.btn-registrar-series-variante', function () {
        const stockItemId = $(this).data('stock-item-id');

        abrirModalCodigosVariante(stockItemId);
    });

    $(document).on('click', '#btnGuardarCodigosVariante', function () {

        if (!$registroPendienteVariante) {
            toastr.error('No existe una variante pendiente de registrar.', 'Error');
            return;
        }

        const codigos = [];
        const codigosNormalizados = [];
        let tieneVacios = false;
        let tieneDuplicados = false;

        $('#tbodyCodigosVariante .input-codigo-variante').each(function () {

            const input = $(this);
            const codigo = (input.val() || '').trim();
            const codigoNormalizado = codigo.toUpperCase();

            input.removeClass('is-invalid');

            if (!codigo) {
                input.addClass('is-invalid');
                tieneVacios = true;
                return;
            }

            if (codigosNormalizados.includes(codigoNormalizado)) {
                input.addClass('is-invalid');
                tieneDuplicados = true;
                return;
            }

            codigos.push(codigo);
            codigosNormalizados.push(codigoNormalizado);
        });

        if (tieneVacios) {
            toastr.error('Debe ingresar todos los códigos o series.', 'Error');
            return;
        }

        if (tieneDuplicados) {
            toastr.error('No puede registrar códigos o series repetidos para esta variante.', 'Error');
            return;
        }

        const stockItemId = $registroPendienteVariante.stock_item_id;

        $codigosPorVariante[stockItemId] = codigos;

        const row = $('#tbodyStockItemsVariant tr[data-stock-item-id="' + stockItemId + '"]');

        actualizarBotonSeriesVariante(row);

        $('#modalCodigosVariante').modal('hide');
        $registroPendienteVariante = null;
    });
});

let $modalImage;
let $formEdit;

let $formCreate;

let $modalAddItems;
let $modalAddGroupItems;

let $caracteres = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

let $longitud = 20;

let $registroPendienteMaterialSimple = null;
let $codigosPorVariante = {};
let $registroPendienteVariante = null;

function agregarMaterialSimpleConCodigosManuales(data, codigos) {

    const material = data.material;
    const quantity = parseFloat(data.quantity);
    const materialPrice = parseFloat(data.material_price);
    const location = data.location;
    const materialVence = data.material_vence;
    const materialLote = data.material_lote;

    const unitPrice = parseFloat(materialPrice / quantity).toFixed(4);

    const entryGroupId = generarEntryGroupId();

    codigos.forEach(function (codigo) {
        $items.push({
            'id': $items.length + 1,
            'entry_group_id': entryGroupId,
            'price': unitPrice,
            'quantity': 1,
            'material': material.material,
            'id_material': material.material_id,
            'stock_item_id': material.stock_item_id,
            'item': codigo,
            'id_location': location.id,
            'date_vence': materialVence,
            'material_lote': materialLote,
            'state': 'good',

            // Identifica que estos códigos fueron registrados individualmente.
            'is_grouped': 0
        });
    });

    updateSummaryInvoice();

    const subtotal = parseFloat(materialPrice / 1.18).toFixed(2);
    const taxes = parseFloat(subtotal * 0.18).toFixed(2);
    const total = materialPrice.toFixed(2);

    renderTemplateMaterial(
        material.stock_item_id || material.material_id,
        material.stock_item_sku || material.code || '',
        material.material,
        quantity,
        material.unit,
        parseFloat(materialPrice / quantity).toFixed(2),
        subtotal,
        taxes,
        total,
        entryGroupId
    );

    limpiarFormularioEntradaMaterial();
}

function agregarStockItemsVariantAEntrada(selectedRows) {

    let material = $materialSelected;

    let materialLocation = $('#almacen').val();
    let materialVence = $('#date_vence').val();
    let materialLote = $('#lot').val();

    let location = $locationsComplete.find(function (location) {
        return location.location === materialLocation;
    });

    if (!location) {
        toastr.error('Debe seleccionar un almacén válido.', 'Error');
        return;
    }

    let totalGeneral = 0;

    selectedRows.forEach(function (row) {

        let quantity = parseFloat(row.quantity);
        let totalPrice = parseFloat(row.price);
        let unitPrice = parseFloat(totalPrice / quantity).toFixed(4);

        const usarCodigosManuales = Array.isArray(row.codes) && row.codes.length === quantity;

        const entryGroupId = generarEntryGroupId();

        for (let j = 0; j < quantity; j++) {

            const code = usarCodigosManuales
                ? row.codes[j]
                : rand_code($caracteres, $longitud);

            $items.push({
                'id': $items.length + 1,
                'entry_group_id': entryGroupId,
                'price': unitPrice,
                'quantity': 1,
                'material': row.display_name || material.material,
                'id_material': material.material_id,
                'stock_item_id': row.stock_item_id,
                'variant_id': row.variant_id,
                'item': code,
                'id_location': location.id,
                'date_vence': materialVence,
                'material_lote': materialLote,
                'state': 'good',
                'is_grouped': row.is_grouped
            });
        }

        updateSummaryInvoice();

        let subtotal = parseFloat(totalPrice / 1.18).toFixed(2);
        let taxes = parseFloat(subtotal * 0.18).toFixed(2);
        let total = parseFloat(totalPrice).toFixed(2);

        renderTemplateMaterial(
            row.stock_item_id,
            row.sku,
            row.display_name || material.material,
            quantity,
            material.unit,
            parseFloat(totalPrice / quantity).toFixed(2),
            subtotal,
            taxes,
            total,
            entryGroupId
        );
    });

    limpiarFormularioEntradaMaterial();

    // Ya se agregaron al detalle; limpiamos los registros temporales.
    $codigosPorVariante = {};
    $registroPendienteVariante = null;
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

function abrirModalStockItemsVariant(material) {

    let materialLocation = $('#almacen').val();

    if (!materialLocation) {
        toastr.error('Debe seleccionar un almacén.', 'Error');
        return;
    }

    if (material.perecible === 's' && !$('#date_vence').val()) {
        toastr.error('Debe ingresar la fecha de vencimiento.', 'Error');
        return;
    }

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

    $codigosPorVariante = {};
    $registroPendienteVariante = null;

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

    const material = $materialSelected;
    const isItemeable = material && parseInt(material.tipo_venta_id) === 3;
    const isGrouped = $('#btn-grouped2').bootstrapSwitch('state');

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

        const mostrarSeries = isItemeable && !isGrouped;

        let seriesHtml = `
            <span class="text-muted small">—</span>
        `;

        if (mostrarSeries) {
            seriesHtml = `
                <button type="button"
                        class="btn btn-sm btn-outline-primary btn-registrar-series-variante"
                        data-stock-item-id="${item.stock_item_id}"
                        disabled>
                    Series (0/0)
                </button>
            `;
        }

        let row = `
            <tr data-stock-item-id="${item.stock_item_id}">
                <td>${escapeHtml(item.attribute_summary || '')}</td>
                <td>${escapeHtml(item.sku || '')}</td>
                <td>${escapeHtml(item.barcode || '')}</td>

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
                           placeholder="0.00">
                </td>

                <td class="text-center align-middle">
                    ${seriesHtml}
                </td>
            </tr>
        `;

        tbody.append(row);
    });
}

function actualizarBotonSeriesVariante(row) {

    const material = $materialSelected;
    const isItemeable = material && parseInt(material.tipo_venta_id) === 3;
    const isGrouped = $('#btn-grouped2').bootstrapSwitch('state');

    if (!isItemeable || isGrouped) {
        return;
    }

    const stockItemId = parseInt(row.data('stock-item-id'));
    const btn = row.find('.btn-registrar-series-variante');

    if (!btn.length) {
        return;
    }

    const quantityRaw = row.find('.variant-quantity').val();
    const quantity = parseInt(quantityRaw, 10);

    if (!quantity || quantity <= 0) {
        delete $codigosPorVariante[stockItemId];

        btn
            .prop('disabled', true)
            .removeClass('btn-success')
            .addClass('btn-outline-primary')
            .text('Series (0/0)');

        return;
    }

    const codigos = $codigosPorVariante[stockItemId] || [];
    const cantidadCodigos = codigos.length;

    // Si la cantidad fue modificada luego de registrar códigos,
    // se eliminan para evitar que queden series sobrantes o incompletas.
    if (cantidadCodigos > 0 && cantidadCodigos !== quantity) {
        delete $codigosPorVariante[stockItemId];
    }

    const codigosActuales = $codigosPorVariante[stockItemId] || [];
    const cantidadActual = codigosActuales.length;
    const completo = cantidadActual === quantity;

    btn
        .prop('disabled', false)
        .removeClass('btn-success btn-outline-primary')
        .addClass(completo ? 'btn-success' : 'btn-outline-primary')
        .text(`Series (${cantidadActual}/${quantity})`);
}

function abrirModalCodigosVariante(stockItemId) {

    const row = $('#tbodyStockItemsVariant tr[data-stock-item-id="' + stockItemId + '"]');

    if (!row.length) {
        toastr.error('No se encontró la variante seleccionada.', 'Error');
        return;
    }

    const quantity = parseInt(row.find('.variant-quantity').val(), 10);

    if (!quantity || quantity <= 0) {
        toastr.warning('Primero debe ingresar una cantidad válida para esta variante.', 'Atención');
        return;
    }

    const stockItem = $stockItemsVariantSelected.find(function (item) {
        return parseInt(item.stock_item_id) === parseInt(stockItemId);
    });

    if (!stockItem) {
        toastr.error('No se encontró la información de la variante.', 'Error');
        return;
    }

    $registroPendienteVariante = {
        stock_item_id: parseInt(stockItemId),
        quantity: quantity,
        stock_item: stockItem
    };

    const codigosExistentes = $codigosPorVariante[stockItemId] || [];

    $('#modalCodigosVarianteTitle').text('Registrar códigos / series');

    $('#modalCodigosVarianteInfo').html(
        '<strong>Material:</strong> ' + escapeHtml($materialSelected.material) +
        '<br><strong>Variante:</strong> ' + escapeHtml(stockItem.attribute_summary || stockItem.display_name || '') +
        '<br><strong>Cantidad a registrar:</strong> ' + quantity
    );

    let html = '';

    for (let i = 0; i < quantity; i++) {
        const codigoActual = codigosExistentes[i] || '';

        html += `
            <tr>
                <td class="text-center align-middle">${i + 1}</td>
                <td>
                    <input type="text"
                           class="form-control form-control-sm input-codigo-variante"
                           value="${escapeHtml(codigoActual)}"
                           placeholder="Ingrese código o serie ${i + 1}"
                           autocomplete="off">
                </td>
            </tr>
        `;
    }

    $('#tbodyCodigosVariante').html(html);

    $('#modalCodigosVariante').modal({
        backdrop: 'static',
        keyboard: false
    });

    $('#modalCodigosVariante').modal('show');

    setTimeout(function () {
        $('#tbodyCodigosVariante .input-codigo-variante').first().focus();
    }, 300);
}

function aplicarLoteYFechaVence(material) {

    // Lote siempre activo
    $('#lot').prop('readonly', false);
    $('#lot').prop('disabled', false);

    // Fecha vence solo activa si perecible === "s"
    if (material.perecible === 's') {
        $('#date_vence').prop('readonly', false);
        $('#date_vence').prop('disabled', false);
    } else {
        $('#date_vence').val('');
        $('#date_vence').prop('readonly', true);
        $('#date_vence').prop('disabled', true);
    }
}

function prepararFormularioParaMaterialSimple(material) {

    $('#stock_item_id').val(material.stock_item_id || '');

    // Lote y fecha según perecible
    aplicarLoteYFechaVence(material);

    // Cantidad activa
    $('#quantity').prop('readonly', false);
    $('#quantity').prop('disabled', false);

    // Precio IGV activo
    $('#price').prop('readonly', false);
    $('#price').prop('disabled', false);

    if (material.price !== null && material.price !== undefined) {
        $('#price').val(material.price);
    }

    // Almacén activo
    $('#almacen').prop('readonly', false);
    $('#almacen').prop('disabled', false);

    if (typeof $locations !== 'undefined' && $locations.length > 0) {
        $("#almacen").typeahead('val', $locations[0]).trigger('change');
    }
}

function prepararFormularioParaMaterialConVariantes(material) {

    $('#stock_item_id').val('');

    // Lote y fecha según perecible
    aplicarLoteYFechaVence(material);

    // Cantidad desactivada porque todavía no se eligió variante
    $('#quantity').val('');
    $('#quantity').prop('readonly', true);
    $('#quantity').prop('disabled', true);

    // Precio IGV desactivado porque todavía no se eligió variante
    $('#price').val('');
    $('#price').prop('readonly', true);
    $('#price').prop('disabled', true);

    // Almacén activo
    $('#almacen').prop('readonly', false);
    $('#almacen').prop('disabled', false);

    if (typeof $locations !== 'undefined' && $locations.length > 0) {
        $("#almacen").typeahead('val', $locations[0]).trigger('change');
    }

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

function updateSummaryInvoice() {
    var subtotal = 0;
    var total = 0;
    var taxes = 0;

    for ( var i=0; i<$items.length; i++ )
    {
        subtotal += parseFloat( (parseFloat($items[i].price)*parseFloat($items[i].quantity))/1.18 );
        total += parseFloat((parseFloat($items[i].price)*parseFloat($items[i].quantity)));
        taxes = subtotal*0.18;
    }

    console.log("subtotal "+subtotal);
    console.log("taxes "+taxes);
    console.log("total "+total);

    var subtotalAntes = parseFloat($('#subtotal').html()) + subtotal ;

    var taxesAntes = parseFloat($('#taxes').html()) + taxes ;

    var totalAntes = parseFloat($('#total').html()) + total ;

    $('#subtotal').html(subtotalAntes.toFixed(2));
    $('#taxes').html(taxesAntes.toFixed(2));
    $('#total').html(totalAntes.toFixed(2));
}

function saveTableItems() {

    var series_selected = [];
    var locations_selected = [];
    var states_selected = [];

    if ($('[name="my-checkbox"]').is(':checked')) {
        let quantity = $('#quantity_GroupSelected').val();
        let material_name = $('#material_GroupSelected').val();
        // TODO: Este precio es total
        let material_price = parseFloat($('#price_GroupSelected').val()).toFixed(2);
        let material_location = $('#locationGroup').val();
        let material_state = $('#stateGroup').val();
        let state = $('#stateGroup').children("option:selected").val();
        let state_description = $('#stateGroup').children("option:selected").text();

        for ( var j=0; j<quantity; j++ )
        {
            const material = $materialsComplete.find( material => material.material.trim() === material_name.trim() );
            const location = $locationsComplete.find( location => location.location === material_location );
            const code = rand_code($caracteres, $longitud);
            $items.push({ 'id': $items.length+1, 'price': parseFloat(parseFloat(material_price)/parseFloat(quantity)).toFixed(4), 'quantity':1 ,'material': material_name, 'id_material': material.id, 'item': code, 'location': location.location, 'id_location':location.id, 'state': state, 'state_description': state_description });

            //$items.push({ 'id': $items.length+1, 'price': material_price, 'quantity':1 ,'material': material_name, 'id_material': material.id, 'item': code, 'location': location.location, 'id_location':location.id, 'state': state, 'state_description': state_description });
            //renderTemplateMaterial($items.length, material_price, material_name, code,  location.location, state_description);
        }
        const material = $materialsComplete.find( material => material.material.trim() === material_name.trim() );
        console.log(material);
        var subtotal =parseFloat((material_price)/1.18).toFixed(2);
        var taxes = parseFloat(subtotal*0.18).toFixed(2);
        var total = parseFloat(material_price).toFixed(2);
        /*var subtotal =((quantity*material_price)/1.18).toFixed(2);
        var taxes = (subtotal*0.18).toFixed(2);
        var total = (quantity*material_price).toFixed(2);*/

        renderTemplateMaterial(material.id, material.code, material.material, quantity, material.unit, parseFloat(material_price/quantity).toFixed(2), subtotal, taxes, total);

        //renderTemplateMaterial(material.id, material.code, material.material, quantity, material.unit, material_price, subtotal, taxes, total);

        $('#material_search').val('');
        $('#quantity').val('');
        $('#price').val('');
        $('#material_GroupSelected').val('');
        $('#quantity_GroupSelected').val('');
        $('#price_GroupSelected').val('');
        $('#locationGroup').val('');
        $('#locationGroup').typeahead('destroy');

        var totalAdd = parseFloat(total);
        var taxesAdd = parseFloat(taxes);
        var subtotalAdd = parseFloat(subtotal);

        var subtotalActual = parseFloat($('#subtotal').html());
        var taxesActual = parseFloat($('#taxes').html());
        var totalActual = parseFloat($('#total').html());

        var subtotalNuevo = subtotalActual + subtotalAdd ;
        var taxesNuevo = taxesActual + taxesAdd ;
        var totalNuevo = totalActual + totalAdd ;

        $('#subtotal').html(subtotalNuevo.toFixed(2));
        $('#taxes').html(taxesNuevo.toFixed(2));
        $('#total').html(totalNuevo.toFixed(2));

        //updateSummaryInvoice();
        $modalAddGroupItems.modal('hide');

    } else {
        $("[data-series]").each(function(){
            series_selected.push( $(this).val() );
        });

        $("[data-states]").each(function(){
            states_selected.push( { 'state': $(this).children("option:selected").val(), 'description': $(this).children("option:selected").text()}  );
        });

        console.log(states_selected);

        $("[data-locations]").each(function(){
            if ( $(this).val() !== '' )
            {
                const result = $locationsComplete.find( location => location.location === $(this).val() );
                locations_selected.push( {'id':result.id, 'location':result.location} );
            }

        });

        let material_name = $('#material_selected').val();
        let material_quantity = parseFloat($('#quantity_selected').val()).toFixed(2);
        let material_price = parseFloat($('#price_selected').val()).toFixed(2);

        for ( var i=0; i<series_selected.length; i++ )
        {
            const result = $materialsComplete.find( material => material.material.trim() === material_name.trim() );
            $items.push({ 'id': $items.length+1, 'price': parseFloat(parseFloat(material_price)/parseFloat(material_quantity)).toFixed(4), 'quantity':1, 'material': material_name, 'id_material': result.id, 'item': series_selected[i], 'location': locations_selected[i].location, 'id_location':locations_selected[i].id, 'state': states_selected[i].state, 'state_description': states_selected[i].description });

            //$items.push({ 'id': $items.length+1, 'price': material_price, 'quantity':1, 'material': material_name, 'id_material': result.id, 'item': series_selected[i], 'location': locations_selected[i].location, 'id_location':locations_selected[i].id, 'state': states_selected[i].state, 'state_description': states_selected[i].description });
            //renderTemplateMaterial($items.length, material_price, material_name, series_selected[i],  locations_selected[i].location, states_selected[i].description);
            $('.select2').select2();
        }

        const material = $materialsComplete.find( material => material.material.trim() === material_name.trim() );
        console.log(material);
        var subtotal2 =parseFloat((material_price)/1.18).toFixed(2);
        var taxes2 = parseFloat(subtotal2*0.18).toFixed(2);
        var total2 = parseFloat(material_price).toFixed(2);
        /*var subtotal2 =((material_quantity*material_price)/1.18).toFixed(2);
        var taxes2 = (subtotal2*0.18).toFixed(2);
        var total2 = (material_quantity*material_price).toFixed(2);*/

        renderTemplateMaterial(material.id, material.code, material.material, material_quantity, material.unit, parseFloat(material_price/material_quantity).toFixed(2), subtotal2, taxes2, total2);

        //renderTemplateMaterial(material.id, material.code, material.material, material_quantity, material.unit, material_price, subtotal2, taxes2, total2);

        $('#material_search').val('');
        $('#quantity').val('');
        $('#price').val('');
        $('#material_selected').val('');
        $('#quantity_selected').val('');
        $('#price_selected').val('');
        $('#body-items').html('');
        $('#locationGroup').val(' ');
        $('#locationGroup').typeahead('destroy');

        var totalAdd2 = parseFloat(total2);
        var taxesAdd2 = parseFloat(taxes2);
        var subtotalAdd2 = parseFloat(subtotal2);

        var subtotalActual2 = parseFloat($('#subtotal').html());
        var taxesActual2 = parseFloat($('#taxes').html());
        var totalActual2 = parseFloat($('#total').html());

        var subtotalNuevo2 = subtotalActual2 + subtotalAdd2 ;
        var taxesNuevo2 = taxesActual2 + taxesAdd2 ;
        var totalNuevo2 = totalActual2 + totalAdd2 ;

        $('#subtotal').html(subtotalNuevo2.toFixed(2));
        $('#taxes').html(taxesNuevo2.toFixed(2));
        $('#total').html(totalNuevo2.toFixed(2));

        //updateSummaryInvoice();
        $modalAddItems.modal('hide');
    }


}

function addItemsO() {
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

    if( $('#price').val().trim() === '' || $('#price').val()<0 )
    {
        toastr.error('Debe ingresar un precio adecuado', 'Error',
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

    if ( $material.tipo_venta_id != 3 )
    {
        let quantity = $('#quantity').val();
        let material_name = $('#material_search').val();
        // TODO: Este precio es total
        let material_price = parseFloat($('#price').val()).toFixed(2);
        //let material_location = $('#locationGroup').val();
        let material_location = $('#almacen').val();
        let material_vence = $("#date_vence").val();
        let material_lote = $("#lot").val();
        let location = $locationsComplete.find( location => location.location === material_location );

        for ( var j=0; j<quantity; j++ )
        {
            const material = $materialsComplete.find( material => material.material === material_name );
            const code = rand_code($caracteres, $longitud);

            $items.push({
                'id': $items.length+1,
                'price': parseFloat(parseFloat(material_price)/parseFloat(quantity)).toFixed(4),
                'quantity':1 ,
                'material': material_name,
                'id_material': material.material_id,
                'stock_item_id': material.stock_item_id,
                'item': code,
                'id_location':location.id,
                'date_vence': material_vence,
                'material_lote': material_lote,
                'state': 'good'
            });
            //renderTemplateMaterial($items.length, material_price, material_name, code,  location.location, state_description);
        }
        const material = $materialsComplete.find( material => material.material === material_name );
        console.log(material);
        var subtotal =parseFloat((material_price)/1.18).toFixed(2);
        var taxes = parseFloat(subtotal*0.18).toFixed(2);
        var total = parseFloat(material_price).toFixed(2);

        renderTemplateMaterial(material.id, material.code, material.material, quantity, material.unit, parseFloat(material_price/quantity).toFixed(2), subtotal, taxes, total);

        $('#material_search').val('');
        $('#quantity').val('');
        $('#price').val('');
        $('#almacen').val('');
        $("#date_vence").val('');

    } else {
        let material_name = $('#material_search').val();
        let material_quantity = parseFloat($('#quantity').val()).toFixed(2);
        // TODO: Este precio es el total ahora
        let material_price = parseFloat($('#price').val()).toFixed(2);

        $('#locationGroup').typeahead('destroy');

        if($('[name="my-checkbox"]').is(':checked'))
        {
            //alert('Es agrupado');
            $modalAddGroupItems.find('[id=material_GroupSelected]').val(material_name);
            $modalAddGroupItems.find('[id=material_GroupSelected]').prop('disabled', true);
            $modalAddGroupItems.find('[id=quantity_GroupSelected]').val(material_quantity);
            $modalAddGroupItems.find('[id=quantity_GroupSelected]').prop('disabled', true);
            $modalAddGroupItems.find('[id=price_GroupSelected]').val(material_price);
            $modalAddGroupItems.find('[id=price_GroupSelected]').prop('disabled', true);

            $('#locationGroup').typeahead({
                    hint: true,
                    highlight: true, /* Enable substring highlighting */
                    minLength: 1 /* Specify minimum characters required for showing suggestions */
                },
                {
                    limit: 12,
                    source: substringMatcher($locations)
                });
            //var l = $locations[0];
            $("#locationGroup").typeahead('val',$locations[0]).trigger('change');

            $modalAddGroupItems.modal('show');

        }else{
            //alert('NO es agrupado');
            $modalAddItems.find('[id=material_selected]').val(material_name);
            $modalAddItems.find('[id=material_selected]').prop('disabled', true);
            $modalAddItems.find('[id=quantity_selected]').val(material_quantity);
            $modalAddItems.find('[id=quantity_selected]').prop('disabled', true);
            $modalAddItems.find('[id=price_selected]').val(material_price);
            $modalAddItems.find('[id=price_selected]').prop('disabled', true);

            $('#body-items').html('');

            for (var i = 0; i<material_quantity; i++)
            {
                renderTemplateItem();

            }
            $('.select2').select2();
            $('.locations').typeahead({
                    hint: true,
                    highlight: true, /* Enable substring highlighting */
                    minLength: 1 /* Specify minimum characters required for showing suggestions */
                },
                {
                    limit: 12,
                    source: substringMatcher($locations)
                });

            $(".locations").typeahead('val',$locations[0]).trigger('change');

            $modalAddItems.modal('show');
        }
    }
}

function materialEsItemeable(material) {
    return parseInt(material.tipo_venta_id) === 3;
}

function materialEstaAgrupado() {
    return $('#btn-grouped2').bootstrapSwitch('state');
}

function escapeHtml(value) {
    return $('<div>').text(value || '').html();
}

function abrirModalCodigosMaterialSimple(data) {

    $registroPendienteMaterialSimple = data;

    const material = data.material;
    const quantity = parseInt(data.quantity, 10);

    $('#modalCodigosMaterialSimpleTitle').text(
        'Registrar códigos / series'
    );

    $('#modalCodigosMaterialSimpleInfo').html(
        '<strong>Material:</strong> ' + escapeHtml(material.material) +
        '<br><strong>Cantidad a registrar:</strong> ' + quantity
    );

    let html = '';

    for (let i = 1; i <= quantity; i++) {
        html += `
            <tr>
                <td class="text-center align-middle">${i}</td>
                <td>
                    <input
                        type="text"
                        class="form-control form-control-sm input-codigo-material-simple"
                        data-index="${i - 1}"
                        placeholder="Ingrese código o serie ${i}"
                        autocomplete="off"
                    >
                </td>
            </tr>
        `;
    }

    $('#tbodyCodigosMaterialSimple').html(html);

    $('#modalCodigosMaterialSimple').modal({
        backdrop: 'static',
        keyboard: false
    });

    $('#modalCodigosMaterialSimple').modal('show');

    setTimeout(function () {
        $('#tbodyCodigosMaterialSimple .input-codigo-material-simple').first().focus();
    }, 300);
}

function addItems() {

    if (!$materialSelected) {
        toastr.error('Debe elegir un material', 'Error', toastrOptions());
        return;
    }

    let material = $materialSelected;

    // Si tiene variantes, NO validamos quantity ni price del formulario principal.
    // Abrimos el modal de variantes.
    if (material.has_variants === true) {

        let material_location = $('#almacen').val();

        if (!material_location || material_location.trim() === '') {
            toastr.error('Debe seleccionar un almacén', 'Error', toastrOptions());
            return;
        }

        let location = $locationsComplete.find(function (location) {
            return location.location === material_location;
        });

        if (!location) {
            toastr.error('Debe seleccionar un almacén válido', 'Error', toastrOptions());
            return;
        }

        if (material.perecible === 's') {
            let material_vence = $('#date_vence').val();

            if (!material_vence || material_vence.trim() === '') {
                toastr.error('Debe ingresar la fecha de vencimiento', 'Error', toastrOptions());
                return;
            }
        }

        abrirModalStockItemsVariant(material);
        return;
    }

    // Desde aquí solo aplica para material simple
    let quantity = $('#quantity').val();
    let material_price_raw = $('#price').val();
    console.log("material_price_raw "+material_price_raw);
    let material_location = $('#almacen').val();
    let material_vence = $('#date_vence').val();
    let material_lote = $('#lot').val();

    console.log("material_price_raw "+material_price_raw);

    if (!quantity || quantity.trim() === '' || parseFloat(quantity) <= 0) {
        toastr.error('Debe ingresar una cantidad válida', 'Error', toastrOptions());
        return;
    }

    if (!material_price_raw || material_price_raw.trim() === '' || parseFloat(material_price_raw) <= 0) {
        toastr.error('Debe ingresar un precio adecuado', 'Error', toastrOptions());
        return;
    }

    if (!material_location || material_location.trim() === '') {
        toastr.error('Debe seleccionar un almacén', 'Error', toastrOptions());
        return;
    }

    let location = $locationsComplete.find(function (location) {
        return location.location === material_location;
    });

    if (!location) {
        toastr.error('Debe seleccionar un almacén válido', 'Error', toastrOptions());
        return;
    }

    if (material.perecible === 's') {
        if (!material_vence || material_vence.trim() === '') {
            toastr.error('Debe ingresar la fecha de vencimiento', 'Error', toastrOptions());
            return;
        }
    }

    const isItemeable = materialEsItemeable(material);
    const isGrouped = materialEstaAgrupado();

    quantity = parseFloat(quantity);
    let material_price = parseFloat(material_price_raw).toFixed(2);

    // Material itemeable no agrupado:
    // no debe generar códigos aleatorios; debe solicitar series manuales.
    if (isItemeable && !isGrouped) {

        if (isItemeable && !Number.isInteger(quantity)) {
            toastr.error(
                'Los materiales itemeables deben registrarse con una cantidad entera.',
                'Error',
                toastrOptions()
            );
            return;
        }

        abrirModalCodigosMaterialSimple({
            material: material,
            quantity: quantity,
            material_price: material_price,
            location: location,
            material_vence: material_vence,
            material_lote: material_lote
        });

        return;
    }

    let unit_price = parseFloat(material_price / quantity).toFixed(4);

    const entryGroupId = generarEntryGroupId();

    for (let j = 0; j < quantity; j++) {

        const code = rand_code($caracteres, $longitud);

        $items.push({
            'id': $items.length + 1,
            'entry_group_id': entryGroupId,
            'price': unit_price,
            'quantity': 1,
            'material': material.material,
            'id_material': material.material_id,
            'stock_item_id': material.stock_item_id,
            'item': code,
            'id_location': location.id,
            'date_vence': material_vence,
            'material_lote': material_lote,
            'state': 'good',
            'is_grouped': isGrouped ? 1 : 0
        });


    }

    updateSummaryInvoice();

    let subtotal = parseFloat(material_price / 1.18).toFixed(2);
    let taxes = parseFloat(subtotal * 0.18).toFixed(2);
    let total = parseFloat(material_price).toFixed(2);

    renderTemplateMaterial(
        material.stock_item_id || material.material_id,
        material.stock_item_sku || material.code || '',
        material.material,
        quantity,
        material.unit,
        parseFloat(material_price / quantity).toFixed(2),
        subtotal,
        taxes,
        total,
        entryGroupId
    );

    limpiarFormularioEntradaMaterial();
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

function rand_code($caracteres, $longitud){
    var code = "";
    for (var x=0; x < $longitud; x++)
    {
        var rand = Math.floor(Math.random()*$caracteres.length);
        code += $caracteres.substr(rand, 1);
    }
    return code;
}

function generarEntryGroupId() {
    return 'entry_' + Date.now() + '_' + Math.random().toString(36).substring(2, 10);
}

function deleteItem() {
    //console.log($(this).parent().parent().parent());
    var totalDelete = parseFloat($(this).parent().prev().html());
    var taxesDelete = parseFloat($(this).parent().prev().prev().html());
    var subtotalDelete = parseFloat($(this).parent().prev().prev().prev().html());

    var subtotalActual = parseFloat($('#subtotal').html());
    var taxesActual = parseFloat($('#taxes').html());
    var totalActual = parseFloat($('#total').html());

    var subtotal_Restar = subtotalDelete;
    var taxes_Restar = taxesDelete;
    var total_Restar = totalDelete;

    var subtotalNuevo = subtotalActual - subtotal_Restar ;
    var taxesNuevo = taxesActual - taxes_Restar ;
    var totalNuevo = totalActual - total_Restar ;

    $('#subtotal').html(subtotalNuevo.toFixed(2));
    $('#taxes').html(taxesNuevo.toFixed(2));
    $('#total').html(totalNuevo.toFixed(2));

    $(this).parent().parent().remove();
    const entryGroupId = $(this).data('delete');
    $items = $items.filter(function (item) {
        return item.entry_group_id !== entryGroupId;
    });
    //updateSummaryInvoice();
}

function renderTemplateMaterial(id, code, description, quantity, unit, price, subtotal, taxes, total, entryGroupId) {
    var clone = activateTemplate('#materials-selected');
    clone.querySelector("[data-code]").innerHTML = code;
    clone.querySelector("[data-description]").innerHTML = description;
    clone.querySelector("[data-quantity]").innerHTML = parseFloat(quantity).toFixed(2);
    clone.querySelector("[data-unit]").innerHTML = unit;
    clone.querySelector("[data-price]").innerHTML = parseFloat(price).toFixed(2);
    clone.querySelector("[data-subtotal]").innerHTML = parseFloat(subtotal).toFixed(2);
    clone.querySelector("[data-taxes]").innerHTML = parseFloat(taxes).toFixed(2);
    clone.querySelector("[data-total]").innerHTML = parseFloat(total).toFixed(2);
    clone.querySelector("[data-delete]").setAttribute('data-delete', entryGroupId);
    $('#body-materials').append(clone);
}

function renderTemplateItem() {
    var clone = activateTemplate('#template-item');
    clone.querySelector("[data-series]").setAttribute('value', rand_code($caracteres, $longitud));
    $('#body-items').append(clone);
}

function activateTemplate(id) {
    var t = document.querySelector(id);
    return document.importNode(t.content, true);
}

function deleteItemOld() {
    //console.log($(this).parent().parent().parent());
    var idDetail = $(this).data('deleteold');
    console.log(idDetail);
    var idEntry = $(this).data('entry');
    console.log(idEntry);
    $.confirm({
        icon: 'fas fa-frown',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'red',
        title: '¿Está seguro de eliminar este material?',
        content: 'Se eliminará directamente de la base de datos',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                action: function (e) {
                    $.ajax({
                        url: '/dashboard/destroy/detail/'+idDetail+'/entry/'+idEntry,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            console.log(data);
                            $(this).parent().parent().remove();
                            $.alert(data.message);
                            setTimeout( function () {
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


                        },
                    });
                },
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Material cancelada.");
                },
            },
        },
    });
}

function saveNewDetails() {
    var idEntry = $(this).data('entry');
    $('#btn-submit').attr("disabled", true);
    console.log(idEntry);
    var valParam = JSON.stringify($items);
    $.confirm({
        icon: 'fas fa-smile',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'green',
        title: '¿Está seguro de guardar estos nuevos materiales?',
        content: 'Se agregarán estos materiales a la compra',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                action: function (e) {
                    $.ajax({
                        url: '/dashboard/add/materials/entry/'+idEntry,
                        method: 'POST',
                        data: { items: valParam} ,
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        success: function (data) {
                            console.log(data);
                            $.alert(data.message);
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
                },
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $("#btn-submit").attr("disabled", false);
                    $.alert("Material cancelada.");
                },

            },
        },
    });
}

function showImage() {
    var path = $(this).attr('src');
    $('#image-document').attr('src', path);
    $modalImage.modal('show');
}

function updateOrderPurchase() {
    event.preventDefault();
    // Obtener la URL
    var createUrl = $formEdit.data('url');
    /*var items = JSON.stringify($items);
    var form = new FormData(this);
    form.append('items', items);*/
    $('#btn-submitForm').attr("disabled", true);
    $.ajax({
        url: createUrl,
        method: 'POST',
        data: new FormData($('#formEdit')[0]),
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
                $("#btn-submitForm").attr("disabled", false);
                location.reload();
            }, 2000 )
        },
        error: function (data) {
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
            $("#btn-submitForm").attr("disabled", false);

        },
    });
}