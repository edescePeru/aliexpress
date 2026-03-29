$(document).ready(function () {

    $formEdit = $('#formEdit');
    //$formEdit.on('submit', updateMaterial);
    $('#btn-submit').on('click', updateMaterial);

    $('#btn-add').on('click', showTemplateSpecification);

    $(document).on('click', '[data-delete]', deleteSpecification);

    $selectExample = $('#exampler');
    $selectExampler = $('#exampler');

    $selectCategory = $('#category');

    $selectSubCategory = $('#subcategory');

    $selectBrand = $('#brand');

    $selectType = $('#type');

    $selectSubtype = $('#subtype');

    $selectCategory.change(function () {
        $selectSubCategory.empty().trigger('change');
        $('#feature-body').css("display","none");
        $selectType.val('0');
        $selectType.trigger('change');
        $selectSubtype.val('0');
        $selectSubtype.trigger('change');
        $('#warrant').val('0');
        $('#warrant').trigger('change');
        $('#quality').val('0');
        $('#quality').trigger('change');
        var category =  $selectCategory.val();

        $('#categoria_id_hidden').val(category);
        if (!category) {
            $btnNewSubCategoria.hide(); // Ocultar si no hay marca seleccionada
            return;
        }

        $btnNewSubCategoria.show();

        $.get( "/dashboard/get/subcategories/"+category, function( data ) {
            $selectSubCategory.append($("<option>", {
                value: "",
                text: ""
            }));
            for ( var i=0; i<data.length; i++ )
            {
                $selectSubCategory.append($("<option>", {
                    value: data[i].id,
                    text: data[i].subcategory
                }));
            }
        });

    });

    $selectBrand.change(function () {
        var brandId = $(this).val();
        $selectExampler.empty().trigger('change');
        $('#brand_id_hidden').val(brandId); // Marca que se usará en el modal

        if (!brandId) {
            $btnNewExampler.hide(); // Ocultar si no hay marca seleccionada
            return;
        }

        $btnNewExampler.show(); // Mostrar si hay marca

        // Obtener modelos de la marca
        $.get("/dashboard/get/exampler/" + brandId, function (data) {
            $.each(data, function (i, item) {
                $selectExampler.append($("<option>", {
                    value: item.id,
                    text: item.exampler
                }));
            });
        });
    });

    /*$selectSubCategory.change(function () {
        let subcategory = $selectSubCategory.select2('data');
        let option = $selectSubCategory.find(':selected');

        console.log(subcategory[0].id);
        if(subcategory[0].text === 'INOX' || subcategory[0].text === 'FENE') {
            $selectType.empty();
            var subcategoria =  subcategory[0].id;
            $.get( "/dashboard/get/types/"+subcategoria, function( data ) {
                $selectType.append($("<option>", {
                    value: '',
                    text: 'Ninguno'
                }));
                var type_id = $('#type_id').val();
                for ( var i=0; i<data.length; i++ )
                {
                    /!*$selectType.append($("<option>", {
                        value: data[i].id,
                        text: data[i].type
                    }));*!/
                    if (data[i].id === parseInt(type_id)) {
                        var newOption = new Option(data[i].type, data[i].id, false, true);
                        // Append it to the select
                        $selectType.append(newOption).trigger('change');

                    } else {
                        var newOption2 = new Option(data[i].type, data[i].id, false, false);
                        // Append it to the select
                        $selectType.append(newOption2);
                    }
                }
            });
            $('#feature-body').css("display","");
        } else {
            console.log(subcategory[0].text);
            $('#feature-body').css("display","none");
            $selectType.val('0');
            $selectType.trigger('change');
            $selectSubtype.val('0');
            $selectSubtype.trigger('change');
            $('#warrant').val('0');
            $('#warrant').trigger('change');
            $('#quality').val('0');
            $('#quality').trigger('change');
            $selectSubCategory.select2('close');
        }
        //alert(subcategory[0].text);
        /!*switch(subcategory[0].text) {
            case "INOX":
                //alert('Metalico');
                $selectType.empty();
                var subcategoria =  subcategory[0].id;
                $.get( "/dashboard/get/types/"+subcategoria, function( data ) {
                    $selectType.append($("<option>", {
                        value: '',
                        text: 'Ninguno'
                    }));
                    var type =  $('#type_id').val();
                    for ( var i=0; i<data.length; i++ )
                    {
                        if ( data[i].id === parseInt(type) )
                        {
                            var newOption = new Option(data[i].type, data[i].id, false, true);
                            // Append it to the select
                            $selectType.append(newOption).trigger('change');

                        } else {
                            var newOption2 = new Option(data[i].type, data[i].id, false, false);
                            // Append it to the select
                            $selectType.append(newOption2);
                        }
                    }
                });
                $('#feature-body').css("display","");

                break;
            default :
                $('#feature-body').css("display","none");
                $selectType.val('0');
                $selectType.trigger('change');
                $selectSubtype.val('0');
                $selectSubtype.trigger('change');
                $('#warrant').val('0');
                $('#warrant').trigger('change');
                $('#quality').val('0');
                $('#quality').trigger('change');
                generateNameProduct();
                break;
        }*!/
    });

    $selectType.change(function () {
        $selectSubtype.empty();
        let type = $selectType.select2('data');
        if( type.length !== 0) {
            $.get("/dashboard/get/subtypes/" + type[0].id, function (data) {
                $selectSubtype.append($("<option>", {
                    value: '',
                    text: 'Ninguno'
                }));
                var subtype = $('#subtype_id').val();
                for (var i = 0; i < data.length; i++) {
                    /!*$selectSubtype.append($("<option>", {
                        value: data[i].id,
                        text: data[i].subtype
                    }));*!/

                    if (data[i].id === parseInt(subtype)) {
                        var newOption = new Option(data[i].subtype, data[i].id, false, true);
                        // Append it to the select
                        $selectSubtype.append(newOption).trigger('change');

                    } else {
                        var newOption2 = new Option(data[i].subtype, data[i].id, false, false);
                        // Append it to the select
                        $selectSubtype.append(newOption2);
                    }
                }
            });
        }
    });*/

    //generateNameProduct();

    $selectExample.select2({
        placeholder: "Selecione un modelo",
    });

    getExampler();

    $('#btn-generate').on('click', generateNameProduct);
    $('#btn-generateCode').on('click', generateCodeProduct);

    getSubcategory();

    $('#checkboxPack').on('change', checkInputPack);

    $('#btnSaveUnitMeasure').on('click', saveUnitMeasure);

    $('#btn-saveBrand').on('click', saveBrand);

    $('#btnSaveGenero').on('click', saveGenero);

    $('#btnSaveTalla').on('click', saveTalla);

    $('#btnSaveCategoria').on('click', saveCategoria);

    $('#btn-newSubCategoria').on('click', function () {
        const selectedCategoriaId = $('#category').val();

        if (selectedCategoriaId) {
            // Asignar marca al input hidden del formulario
            $('#category_id_hidden').val(selectedCategoriaId);

            // Mostrar el modal
            $('#modalSubCategoria').modal('show');
        } else {
            $.alert({
                title: 'Aviso',
                content: 'Debe seleccionar una marca antes de agregar una subcategoría.',
                type: 'orange'
            });
        }
    });

    // Guardar nuevo modelo
    $(document).on('click', '#btn-saveSubCategoria' , saveSubCategoria);

    // Prevenir abrir el modal si no hay marca seleccionada
    $('#modalSubCategoria').on('show.bs.modal', function () {
        if (!$('#categoria_id_hidden').val()) {
            alert('Primero seleccione una categoría');
            $('#modalSubCategoria').modal('hide');
        }
    });

    // Guardar nuevo modelo
    $(document).on('click', '#btn-saveExampler' , saveExampler);

    // Prevenir abrir el modal si no hay marca seleccionada
    $('#modalExampler').on('show.bs.modal', function () {
        if (!$('#brand_id_hidden').val()) {
            alert('Primero seleccione una marca');
            $('#modalExampler').modal('hide');
        }
    });

    $('#btn-newExampler').on('click', function () {
        const selectedBrandId = $('#brand').val();

        if (selectedBrandId) {
            // Asignar marca al input hidden del formulario
            $('#brand_id_hidden').val(selectedBrandId);

            // Mostrar el modal
            $('#modalExampler').modal('show');
        } else {
            $.alert({
                title: 'Aviso',
                content: 'Debe seleccionar una marca antes de agregar un modelo.',
                type: 'orange'
            });
        }
    });

    /*$selectSubCategory.change(function () {
        let subcategory = $selectSubCategory.select2('data');
        let option = $selectSubCategory.find(':selected');

        console.log(option);
        if(subcategory[0].text === 'INOX' || subcategory[0].text === 'FENE') {
            $selectType.empty();
            var subcategoria =  subcategory[0].id;
            $.get( "/dashboard/get/types/"+subcategoria, function( data ) {
                $selectType.append($("<option>", {
                    value: '',
                    text: 'Ninguno'
                }));
                for ( var i=0; i<data.length; i++ )
                {
                    $selectType.append($("<option>", {
                        value: data[i].id,
                        text: data[i].type
                    }));
                }
            });
            $('#feature-body').css("display","");
        } else {
            console.log(subcategory[0].text);
            $('#feature-body').css("display","none");
            $selectType.val('0');
            $selectType.trigger('change');
            $selectSubtype.val('0');
            $selectSubtype.trigger('change');
            $('#warrant').val('0');
            $('#warrant').trigger('change');
            $('#quality').val('0');
            $('#quality').trigger('change');
            $selectSubCategory.select2('close');
        }
        /!*switch(subcategory[0].text) {
            case "INOX":
                //alert('Metalico');
                $selectType.empty();
                var subcategoria =  subcategory[0].id;
                $.get( "/dashboard/get/types/"+subcategoria, function( data ) {
                    $selectType.append($("<option>", {
                        value: '',
                        text: 'Ninguno'
                    }));
                    for ( var i=0; i<data.length; i++ )
                    {
                        $selectType.append($("<option>", {
                            value: data[i].id,
                            text: data[i].type
                        }));
                    }
                });
                $('#feature-body').css("display","");

                break;
            default :
                $('#feature-body').css("display","none");
                $selectType.val('0');
                $selectType.trigger('change');
                $selectSubtype.val('0');
                $selectSubtype.trigger('change');
                $('#warrant').val('0');
                $('#warrant').trigger('change');
                $('#quality').val('0');
                $('#quality').trigger('change');
                $selectSubCategory.trigger('change');
                generateNameProduct();
                break;
        }*!/
    });

    $selectType.change(function () {
        $selectSubtype.empty();
        var type = $selectType.select2('data');
        console.log(type);
        if( type.length !== 0)
        {
            $.get( "/dashboard/get/subtypes/"+type[0].id, function( data ) {
                $selectSubtype.append($("<option>", {
                    value: '',
                    text: 'Ninguno'
                }));
                for ( var i=0; i<data.length; i++ )
                {
                    $selectSubtype.append($("<option>", {
                        value: data[i].id,
                        text: data[i].subtype
                    }));
                }
            });
        }


    });*/

    $selectExampler.select2({
        placeholder: "Selecione un modelo",
    });

    if (tieneVariantes) {
        $('#con_variantes').prop('checked', true);
        $('#seccion_con_variantes').show();
        $('#seccion_sin_variantes').hide();

        loadVariantsEdit();
    } else {
        $('#sin_variantes').prop('checked', true);
        $('#seccion_con_variantes').hide();
        $('#seccion_sin_variantes').show();

        loadSingleVariantSection();
    }

    $(document).on('click', '#btn-generate_variantes', function () {
        generateVariantsEdit();
    });

});

var $formEdit;
var $selectCategory;
var $selectSubCategory;
var $selectBrand;
var $selectExample;
var $selectType;
var $selectSubtype;
let $caracteres = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
let $longitud = 20;
let $btnNewExampler = $('#btn-newExampler');
let $btnNewSubCategoria = $('#btn-newSubCategoria');

function generateVariantsEdit() {
    let tallas = getSelectedOptionsData('#talla');
    let colores = getSelectedOptionsData('#color');
    let skuBase = generateSkuBase();

    if (tallas.length === 0) {
        toastr.warning('Debe seleccionar al menos una talla.');
        return;
    }

    if (colores.length === 0) {
        toastr.warning('Debe seleccionar al menos un color.');
        return;
    }

    if (!skuBase) {
        toastr.warning('No se pudo generar el SKU base. Verifique marca, modelo, subcategoría o género.');
        return;
    }

    let generatedCount = 0;
    let repeatedCount = 0;

    colores.forEach(function (color) {
        tallas.forEach(function (talla) {
            if (existsVariant(talla.id, color.id)) {
                repeatedCount++;
                return;
            }

            const sku = generateVariantSku(skuBase, talla, color);

            let item = {
                variant_id: null,
                talla_id: talla.id,
                talla_text: talla.text,
                talla_short_name: talla.shortName,
                color_id: color.id,
                color_text: color.text,
                color_short_name: color.shortName,
                attribute_summary: buildAttributeSummary(talla.text, color.text),
                image: null,
                is_active: 1,
                tracks_inventory: 1,
                sku: sku,
                barcode: '',
                display_name: '',
                inventory_levels: buildDefaultInventoryLevels()
            };

            renderVariantRowEdit(item);
            generatedCount++;
        });
    });

    if (generatedCount === 0) {
        toastr.warning('Todas las combinaciones seleccionadas ya fueron agregadas.');
        return;
    }

    toastr.success('Se generaron ' + generatedCount + ' variante(s) correctamente.');

    if (repeatedCount > 0) {
        toastr.info(repeatedCount + ' combinación(es) ya existían y no se duplicaron.');
    }
}

function generateVariantSku(skuBase, tallaData, colorData) {
    const tallaCode = (tallaData.shortName || tallaData.text || '').toString().trim().toUpperCase();
    const colorCode = (colorData.shortName || colorData.text || '').toString().trim().toUpperCase();

    let parts = [
        skuBase,
        tallaCode,
        colorCode
    ].filter(part => part !== '');

    return parts.join('-');
}

function existsVariant(tallaId, colorId) {
    let exists = false;

    $('#body-variantes .item-variante').each(function () {
        let $row = $(this);

        let currentTallaId = ($row.find('[data-talla_id]').val() || '').toString();
        let currentColorId = ($row.find('[data-color_id]').val() || '').toString();

        if (currentTallaId === String(tallaId) && currentColorId === String(colorId)) {
            exists = true;
            return false;
        }
    });

    return exists;
}

function buildAttributeSummary(tallaText, colorText) {
    let parts = [];

    if (tallaText) {
        parts.push(tallaText);
    }

    if (colorText) {
        parts.push(colorText);
    }

    return parts.join(' / ');
}

function getSelectedOptionsData(selector) {
    let results = [];

    $(`${selector} option:selected`).each(function () {
        let $option = $(this);

        results.push({
            id: $option.val(),
            text: $option.text().trim(),
            shortName: ($option.data('short-name') || '').toString().trim()
        });
    });

    return results;
}

function generateSkuBase() {
    let brand = getAbbr(getSelectedText('#brand'));
    let exampler = getAbbr(getSelectedText('#exampler'));
    let subcategory = getAbbr(getSelectedText('#subcategory'));
    let genero = getAbbr(getSelectedText('#genero'));

    let parts = [
        subcategory,
        brand,
        exampler,
        genero
    ].filter(part => part !== '');

    return parts.join('-');
}

function getSelectedText(selector) {
    const $select = $(selector);
    const value = $select.val();

    if (!value) {
        return '';
    }

    return $select.find('option:selected').first().text().trim();
}

function getAbbr(text) {
    if (!text) {
        return '';
    }

    text = text.toString().trim();

    if (text === '') {
        return '';
    }

    // Limpia espacios duplicados
    text = text.replace(/\s+/g, ' ');

    const words = text.split(' ').filter(Boolean);

    // Si es una sola palabra, toma hasta 3 caracteres
    if (words.length === 1) {
        return words[0].substring(0, 3).toUpperCase();
    }

    // Si son varias palabras, toma iniciales
    return words.map(word => word.charAt(0).toUpperCase()).join('');
}

function buildDefaultInventoryLevels() {
    return warehousesActivos.map(function(warehouse) {
        return {
            inventory_level_id: null,
            warehouse_id: warehouse.id,
            warehouse_name: warehouse.name,
            qty_on_hand: 0,
            qty_reserved: 0,
            min_alert: 0,
            max_alert: 0,
            average_cost: 0,
            last_cost: 0
        };
    });
}

function loadSingleVariantSection() {
    if (!Array.isArray(variantesEdit) || variantesEdit.length === 0) {
        return;
    }

    let item = variantesEdit[0];

    $('#stock_item_id').val(item.stock_item_id || '');
    $('#display_name').val(item.display_name || '');
    $('#sku_sin_variantes').val(item.sku || '');
    $('#codigo_sin_variantes').val(item.barcode || '');
    $('#stock_min').val(item.stock_minimo ?? '');
    $('#stock_max').val(item.stock_maximo ?? '');

    let tracksInventory = parseInt(item.tracks_inventory) === 1;

    let $switch = $('#afecto_inventario_sin_variantes');

    $switch.prop('checked', tracksInventory);

    let isActive = parseInt(item.is_active) === 1;

    let $switchActive = $('#is_active_sin_variante');

    $switchActive.prop('checked', isActive);

    if (typeof $switch.bootstrapSwitch === 'function') {
        $switch.bootstrapSwitch('state', tracksInventory, true);
        $switchActive.bootstrapSwitch('state', isActive, true);
    }

    renderInventoryLevelsSingle(item.inventory_levels || []);
}

function renderInventoryLevelsSingle(levels) {
    const $tbody = $('#tbody-inventory-levels-single');
    $tbody.empty();

    if (!Array.isArray(levels) || levels.length === 0) {
        $tbody.append(`
            <tr>
                <td colspan="7" class="text-center text-muted">
                    No hay niveles de inventario registrados.
                </td>
            </tr>
        `);
        return;
    }

    levels.forEach((level, index) => {
        $tbody.append(`
            <tr>
                <td>
                    <input type="hidden" name="inventory_levels[${index}][id]" value="${level.inventory_level_id || ''}">
                    <input type="hidden" name="inventory_levels[${index}][warehouse_id]" value="${level.warehouse_id || ''}">
                    <input type="text" class="form-control form-control-sm" value="${level.warehouse_name || ''}" readonly>
                </td>

                <td>
                    <input type="number" class="form-control form-control-sm" value="${level.qty_on_hand ?? 0}" readonly>
                </td>

                <td>
                    <input type="number" class="form-control form-control-sm" value="${level.qty_reserved ?? 0}" readonly>
                </td>

                <td>
                    <input type="number"
                           name="inventory_levels[${index}][min_alert]"
                           class="form-control form-control-sm"
                           value="${level.min_alert ?? 0}"
                           min="0" step="0.01">
                </td>

                <td>
                    <input type="number"
                           name="inventory_levels[${index}][max_alert]"
                           class="form-control form-control-sm"
                           value="${level.max_alert ?? 0}"
                           min="0" step="0.01">
                </td>

                <td>
                    <input type="number" class="form-control form-control-sm" value="${level.average_cost ?? 0}" readonly>
                </td>

                <td>
                    <input type="number" class="form-control form-control-sm" value="${level.last_cost ?? 0}" readonly>
                </td>
            </tr>
        `);
    });
}

function renderInventoryLevelsVariant($row, item) {
    const levels = Array.isArray(item.inventory_levels) ? item.inventory_levels : [];
    const $tbody = $row.find('[data-inventory_levels_body]');
    $tbody.empty();

    const variantKey = item.variant_id || item.id || 'new';

    if (levels.length === 0) {
        $tbody.append(`
            <tr>
                <td colspan="7" class="text-center text-muted">
                    No hay niveles de inventario registrados.
                </td>
            </tr>
        `);
        return;
    }

    levels.forEach(function(level, index) {
        $tbody.append(`
            <tr>
                <td>
                    <input type="hidden"
                           name="variantes_inventory[${variantKey}][${index}][id]"
                           value="${escapeHtml(level.inventory_level_id || '')}">

                    <input type="hidden"
                           name="variantes_inventory[${variantKey}][${index}][warehouse_id]"
                           value="${escapeHtml(level.warehouse_id || '')}">

                    <input type="text"
                           class="form-control form-control-sm"
                           value="${escapeHtml(level.warehouse_name || '')}"
                           readonly>
                </td>

                <td>
                    <input type="number"
                           class="form-control form-control-sm"
                           value="${normalizeNumber(level.qty_on_hand)}"
                           readonly>
                </td>

                <td>
                    <input type="number"
                           class="form-control form-control-sm"
                           value="${normalizeNumber(level.qty_reserved)}"
                           readonly>
                </td>

                <td>
                    <input type="number"
                           class="form-control form-control-sm"
                           name="variantes_inventory[${variantKey}][${index}][min_alert]"
                           value="${normalizeNumber(level.min_alert)}"
                           min="0"
                           step="0.01">
                </td>

                <td>
                    <input type="number"
                           class="form-control form-control-sm"
                           name="variantes_inventory[${variantKey}][${index}][max_alert]"
                           value="${normalizeNumber(level.max_alert)}"
                           min="0"
                           step="0.01">
                </td>

                <td>
                    <input type="number"
                           class="form-control form-control-sm"
                           value="${normalizeNumber(level.average_cost)}"
                           readonly>
                </td>

                <td>
                    <input type="number"
                           class="form-control form-control-sm"
                           value="${normalizeNumber(level.last_cost)}"
                           readonly>
                </td>
            </tr>
        `);
    });
}

function getStockTotalFromLevels(levels) {
    if (!Array.isArray(levels) || levels.length === 0) {
        return 0;
    }

    return levels.reduce(function(total, level) {
        return total + parseFloat(level.qty_on_hand || 0);
    }, 0);
}

function normalizeNumber(value) {
    if (value === null || value === undefined || value === '') {
        return 0;
    }

    const parsed = parseFloat(value);
    return isNaN(parsed) ? 0 : parsed;
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function renderVariantRowEdit(item) {
    let template = document.querySelector('#template-variante').content.cloneNode(true);
    let $template = $(template);

    // Datos principales de la variante
    $template.find('[data-variant_id]').val(item.variant_id || item.id || '');

    $template.find('[data-talla_text]').val(item.talla_text || '');
    $template.find('[data-talla_id]').val(item.talla_id || '');

    $template.find('[data-color_text]').val(item.color_text || '');
    $template.find('[data-color_id]').val(item.color_id || '');

    $template.find('[data-sku_sugerido]').val(item.sku || '');
    $template.find('[data-codigo_barras]').val(item.barcode || '');

    if (item.image) {
        $template.find('[data-image_label]').text('Imagen actual: ' + item.image);
    } else {
        $template.find('[data-image_label]').text('');
    }

    // Agregar primero al DOM para poder trabajar sobre la fila final
    $('#body-variantes').append($template);

    let $row = $('#body-variantes .item-variante').last();

    // Switch activo
    let isActive = parseInt(item.is_active) === 1;
    let $activeSwitch = $row.find('[data-is_active_variante]');

    $activeSwitch.prop('checked', isActive);

    if (typeof $activeSwitch.bootstrapSwitch === 'function') {
        $activeSwitch.bootstrapSwitch();
        $activeSwitch.bootstrapSwitch('state', isActive, true);
    }

    // Switch afecto inventario
    let tracksInventory = parseInt(item.tracks_inventory) === 1;
    let $tracksSwitch = $row.find('[data-afecto_inventario_variante]');

    if ($tracksSwitch.length) {
        $tracksSwitch.prop('checked', tracksInventory);

        if (typeof $tracksSwitch.bootstrapSwitch === 'function') {
            $tracksSwitch.bootstrapSwitch();
            $tracksSwitch.bootstrapSwitch('state', tracksInventory, true);
        }
    }

    // Stock total = suma de qty_on_hand de todos los inventory levels
    let stockTotal = getStockTotalFromLevels(item.inventory_levels || []);
    $row.find('[data-stock_total]').val(stockTotal);

    // Pintar inventory levels
    renderInventoryLevelsVariant($row, item);

    // Mostrar/ocultar bloque inventario
    $row.find('[data-toggle_inventory_levels]').on('click', function () {
        const $wrapper = $row.find('[data-inventory_levels_wrapper]');
        $wrapper.toggleClass('d-none');
    });
}

function loadVariantsEdit() {
    $('#body-variantes').empty();

    if (!Array.isArray(variantesEdit) || variantesEdit.length === 0) {
        return;
    }

    variantesEdit.forEach(function(item) {
        renderVariantRowEdit(item);
    });
}

function saveSubCategoria() {
    let $form = $('#formCreateSubCategoria');
    let url = $form.data('url');
    let formData = $form.serialize();

    $.post(url, formData, function (response) {
        if (response && response.data) {
            console.log(response.data[0].id);
            console.log(response.data[0].name);

            $('#modalSubCategoria').modal('hide');
            $selectSubCategory.append($('<option>', {
                value: response.data[0].id,
                text: response.data[0].name,
                selected: true
            })).trigger('change');

            $.alert({
                title: 'Éxito',
                content: 'Subcategoría guardada correctamente.',
                type: 'green'
            });
        }
    }).fail(function (xhr) {
        let message = 'Error al guardar subcategoría.';

        if (xhr.responseJSON && xhr.responseJSON.message) {
            message = xhr.responseJSON.message;
        }

        $.alert({
            title: 'Error',
            content: message,
            type: 'red'
        });
    });
}

function saveCategoria() {
    let $form = $('#formCreateCategoria');
    let url = $form.data('url');
    let data = $form.serialize();

    $.ajax({
        url: url,
        type: 'POST',
        data: data,
        success: function(response) {
            if (response.success) {
                // Agregar la nueva opción al select
                $('#category').append(
                    `<option value="${response.data.id}" selected>${response.data.description}</option>`
                ).trigger('change');

                // Cerrar el modal
                $('#modalCategoria').modal('hide');

                // Resetear formulario
                $form[0].reset();

                // Mostrar mensaje de éxito
                $.dialog({
                    title: '¡Éxito!',
                    content: 'Categoría creada correctamente.',
                    type: 'green',
                    boxWidth: '400px',
                    useBootstrap: false
                });

            } else {
                $.alert({
                    title: 'Error',
                    content: response.message || 'No se pudo crear la talla.',
                    type: 'red',
                    boxWidth: '400px',
                    useBootstrap: false
                });
            }
        },
        error: function(xhr) {
            let errors = xhr.responseJSON.errors;
            let message = '';
            $.each(errors, function(key, value) {
                message += `<div>• ${value[0]}</div>`;
            });

            $.alert({
                title: 'Errores de validación',
                content: message,
                type: 'orange',
                boxWidth: '400px',
                useBootstrap: false
            });
        }
    });
}

function saveTalla() {
    let $form = $('#formCreateTalla');
    let url = $form.data('url');
    let data = $form.serialize();

    $.ajax({
        url: url,
        type: 'POST',
        data: data,
        success: function(response) {
            if (response.success) {
                // Agregar la nueva opción al select
                $('#talla').append(
                    `<option value="${response.data.id}" selected>${response.data.description}</option>`
                ).trigger('change');

                // Cerrar el modal
                $('#modalTalla').modal('hide');

                // Resetear formulario
                $form[0].reset();

                // Mostrar mensaje de éxito
                $.dialog({
                    title: '¡Éxito!',
                    content: 'Talla creado correctamente.',
                    type: 'green',
                    boxWidth: '400px',
                    useBootstrap: false
                });

            } else {
                $.alert({
                    title: 'Error',
                    content: response.message || 'No se pudo crear la talla.',
                    type: 'red',
                    boxWidth: '400px',
                    useBootstrap: false
                });
            }
        },
        error: function(xhr) {
            let errors = xhr.responseJSON.errors;
            let message = '';
            $.each(errors, function(key, value) {
                message += `<div>• ${value[0]}</div>`;
            });

            $.alert({
                title: 'Errores de validación',
                content: message,
                type: 'orange',
                boxWidth: '400px',
                useBootstrap: false
            });
        }
    });
}

function saveGenero() {
    let $form = $('#formCreateGenero');
    let url = $form.data('url');
    let data = $form.serialize();

    $.ajax({
        url: url,
        type: 'POST',
        data: data,
        success: function(response) {
            if (response.success) {
                // Agregar la nueva opción al select
                $('#genero').append(
                    `<option value="${response.data.id}" selected>${response.data.description}</option>`
                ).trigger('change');

                // Cerrar el modal
                $('#modalGenero').modal('hide');

                // Resetear formulario
                $form[0].reset();

                // Mostrar mensaje de éxito
                $.dialog({
                    title: '¡Éxito!',
                    content: 'Género creado correctamente.',
                    type: 'green',
                    boxWidth: '400px',
                    useBootstrap: false
                });

            } else {
                $.alert({
                    title: 'Error',
                    content: response.message || 'No se pudo crear el género.',
                    type: 'red',
                    boxWidth: '400px',
                    useBootstrap: false
                });
            }
        },
        error: function(xhr) {
            let errors = xhr.responseJSON.errors;
            let message = '';
            $.each(errors, function(key, value) {
                message += `<div>• ${value[0]}</div>`;
            });

            $.alert({
                title: 'Errores de validación',
                content: message,
                type: 'orange',
                boxWidth: '400px',
                useBootstrap: false
            });
        }
    });
}

function saveExampler() {
    let $form = $('#formCreateExampler');
    let url = $form.data('url');
    let formData = $form.serialize();

    $.post(url, formData, function (response) {
        if (response && response.id) {
            $('#modalExampler').modal('hide');
            $selectExampler.append($('<option>', {
                value: response.id,
                text: response.exampler,
                selected: true
            })).trigger('change');

            $.alert({
                title: 'Éxito',
                content: 'Modelo guardado correctamente.',
                type: 'green'
            });
        }
    }).fail(function (xhr) {
        let message = 'Error al guardar modelo.';

        if (xhr.responseJSON && xhr.responseJSON.message) {
            message = xhr.responseJSON.message;
        }

        $.alert({
            title: 'Error',
            content: message,
            type: 'red'
        });
    });
}

function saveUnitMeasure() {
    let $form = $('#formCreateUnitMeasure');
    let url = $form.data('url');
    let data = $form.serialize();

    $.ajax({
        url: url,
        type: 'POST',
        data: data,
        success: function(response) {
            if (response.success) {
                // Agregar la nueva opción al select
                $('#unit_measure').append(
                    `<option value="${response.data.id}" selected>${response.data.description}</option>`
                ).trigger('change');

                // Cerrar el modal
                $('#modalUnitMeasure').modal('hide');

                // Resetear formulario
                $form[0].reset();

                // Mostrar mensaje de éxito
                $.dialog({
                    title: '¡Éxito!',
                    content: 'Unidad de medida creada correctamente.',
                    type: 'green',
                    boxWidth: '400px',
                    useBootstrap: false
                });

            } else {
                $.alert({
                    title: 'Error',
                    content: response.message || 'No se pudo crear la unidad de medida.',
                    type: 'red',
                    boxWidth: '400px',
                    useBootstrap: false
                });
            }
        },
        error: function(xhr) {
            let errors = xhr.responseJSON.errors;
            let message = '';
            $.each(errors, function(key, value) {
                message += `<div>• ${value[0]}</div>`;
            });

            $.alert({
                title: 'Errores de validación',
                content: message,
                type: 'orange',
                boxWidth: '400px',
                useBootstrap: false
            });
        }
    });
}

function saveBrand() {
    let $form = $('#formCreateBrand');
    let url = $form.data('url');
    let data = $form.serialize();

    $.ajax({
        url: url,
        type: 'POST',
        data: data,
        success: function(response) {
            if (response.success) {
                // Añadir nueva marca al select
                $('#brand').append(
                    `<option value="${response.data.id}" selected>${response.data.name}</option>`
                ).trigger('change');

                // Cerrar modal y limpiar
                $('#modalBrand').modal('hide');
                $form[0].reset();

                // Mostrar éxito
                $.dialog({
                    title: '¡Éxito!',
                    content: 'Marca creada correctamente.',
                    type: 'green',
                    boxWidth: '400px',
                    useBootstrap: false
                });
            } else {
                $.alert({
                    title: 'Error',
                    content: response.message || 'No se pudo crear la marca.',
                    type: 'red',
                    boxWidth: '400px',
                    useBootstrap: false
                });
            }
        },
        error: function(xhr) {
            let errors = xhr.responseJSON.errors;
            let message = '';
            $.each(errors, function(key, value) {
                message += `<div>• ${value[0]}</div>`;
            });

            $.alert({
                title: 'Errores de validación',
                content: message,
                type: 'orange',
                boxWidth: '400px',
                useBootstrap: false
            });
        }
    });
}

function checkInputPack() {
    if ($('#checkboxPack').is(':checked')) {
        $('#inputPack').val(1);
        $('#inputPack').prop('disabled', false);  // Activa el input si el checkbox está marcado
    } else {
        $('#inputPack').val('');
        $('#inputPack').prop('disabled', true);  // Desactiva el input si el checkbox no está marcado
    }
}

function generateCodeProduct() {
    let codigo = rand_code($caracteres, $longitud);
    $('#codigo').val(codigo);
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

function mayus(e) {
    e.value = e.value.toUpperCase();
}

function generateNameProduct() {
    if( $('#description').val().trim() === '' )
    {
        toastr.error('Debe escribir una descripción', 'Error',
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

    $('#name').val('');
    // Obtener los valores de las opciones seleccionadas
    let marca = $('#brand option:selected').text();
    let modelo = $('#exampler option:selected').text();
    let genero = $('#genero option:selected').text();
    let talla = $('#talla option:selected').text();

    let subcategoria = $('#subcategory option:selected').text();

    // Inicializar un arreglo con la descripción
    let partes = [$('#description').val().trim()];

    // Agregar las partes no vacías al arreglo
    if (marca !== 'Ninguno' && marca !== '') partes.push(marca);
    if (modelo !== 'Ninguno' && modelo !== '') partes.push(modelo);
    if (genero !== 'Ninguno' && genero !== '') partes.push(genero);
    if (talla !== 'Ninguno' && talla !== '') partes.push(talla);
    if (subcategoria !== 'Ninguno' && subcategoria !== '') partes.push(subcategoria);

    // Unir las partes con un espacio y asignarlo al campo de nombre
    let name = partes.join(' ');
    $('#name').val(name);

}

function showTemplateSpecification() {
    var specification = $('#specification').val();
    var content = $('#content').val();

    $('#specification').val('');
    $('#content').val('');

    renderTemplateItem(specification, content);
}

function deleteSpecification() {
    //console.log($(this).parent().parent().parent());
    $(this).parent().parent().remove();
}

function getSubcategory() {
    var category =  $('#category').val();
    $.get( "/dashboard/get/subcategories/"+category, function( data ) {
        $selectSubCategory.append($("<option>", {
            value: '',
            text: ''
        }));
        for ( var i=0; i<data.length; i++ )
        {
            if ( data[i].id === parseInt($('#subcategory_id').val()) )
            {
                var newOption = new Option(data[i].subcategory, data[i].id, false, true);
                // Append it to the select
                $selectSubCategory.append(newOption).trigger('change');

            } else {
                var newOption2 = new Option(data[i].subcategory, data[i].id, false, false);
                // Append it to the select
                $selectSubCategory.append(newOption2);
            }

        }
    });
}

function getExampler() {
    //$select.empty();
    var brand =  $('#brand').val();
    console.log(brand);
    if ( typeof brand !== 'undefined' )
    {
        //alert(brand);
        $.get( "/dashboard/get/exampler/"+brand, function( data ) {
            $selectExample.append($("<option>", {
                value: '',
                text: ''
            }));
            for ( var i=0; i<data.length; i++ )
            {
                if ( data[i].id === parseInt($('#exampler_id').val()) )
                {
                    var newOption = new Option(data[i].exampler, data[i].id, false, true);
                    // Append it to the select
                    $selectExample.append(newOption).trigger('change');

                } else {
                    var newOption2 = new Option(data[i].exampler, data[i].id, false, false);
                    // Append it to the select
                    $selectExample.append(newOption2).trigger('change');
                }

            }
        });
    }

}

function validateVariantes(tipo, variantes) {
    console.log(tipo);
    if (!Array.isArray(variantes) || variantes.length === 0) {
        toastr.warning('Debe existir al menos un registro.');
        return false;
    }

    if (tipo === '0') {
        let item = variantes[0];

        if (!item.sku) {
            toastr.warning('Debe ingresar el SKU.');
            return false;
        }

        return true;
    }

    for (let i = 0; i < variantes.length; i++) {
        if (!variantes[i].sku) {
            toastr.warning('Todas las variantes deben tener SKU.');
            return false;
        }
    }

    return true;
}

function updateMaterial(event) {
    event.preventDefault();

    $("#btn-submit").attr("disabled", true);

    let editUrl = $formEdit.data('url');
    let form = new FormData($('#formEdit')[0]);

    let tipo = $('input[name="variantes"]:checked').val();
    let variantes_json = [];

    if (tipo === '0') {
        variantes_json = buildSingleVariantPayloadEdit();
    } else {
        variantes_json = buildMultipleVariantsPayloadEdit(form);
    }

    if (!validateVariantes(tipo, variantes_json)) {
        $("#btn-submit").attr("disabled", false);
        return;
    }

    form.append('tipo_variantes', tipo);
    form.append('variantes_json', JSON.stringify(variantes_json));

    $.ajax({
        url: editUrl,
        method: 'POST',
        data: form,
        processData: false,
        contentType: false,
        success: function (data) {
            toastr.success(data.message, 'Éxito', {
                closeButton: true,
                debug: false,
                newestOnTop: false,
                progressBar: true,
                positionClass: "toast-top-right",
                preventDuplicates: false,
                onclick: null,
                showDuration: "300",
                hideDuration: "1000",
                timeOut: "2000",
                extendedTimeOut: "1000",
                showEasing: "swing",
                hideEasing: "linear",
                showMethod: "fadeIn",
                hideMethod: "fadeOut"
            });

            setTimeout(function () {
                $("#btn-submit").attr("disabled", false);
                location.reload();
            }, 2000);
        },
        error: function (data) {
            if (data.responseJSON && data.responseJSON.errors) {
                for (let property in data.responseJSON.errors) {
                    toastr.error(data.responseJSON.errors[property], 'Error', {
                        closeButton: true,
                        debug: false,
                        newestOnTop: false,
                        progressBar: true,
                        positionClass: "toast-top-right",
                        preventDuplicates: false,
                        onclick: null,
                        showDuration: "300",
                        hideDuration: "1000",
                        timeOut: "4000",
                        extendedTimeOut: "1000",
                        showEasing: "swing",
                        hideEasing: "linear",
                        showMethod: "fadeIn",
                        hideMethod: "fadeOut"
                    });
                }
            } else {
                toastr.error('Ocurrió un error al actualizar el producto.', 'Error');
            }

            $("#btn-submit").attr("disabled", false);
        }
    });
}

function buildSingleVariantPayloadEdit() {
    let tracksInventory = $('#afecto_inventario_sin_variantes').is(':checked') ? 1 : 0;
    let isActive = $('#is_active_sin_variante').is(':checked') ? 1 : 0;
    let pack = $('#checkboxPack').is(':checked') ? 1 : 0;
    let cantidadPack = ($('#inputPack').val() || 1);

    return [
        {
            variant_id: null,
            stock_item_id: $('#stock_item_id').val() || null,
            talla_id: null,
            color_id: null,
            sku: ($('#sku_sin_variantes').val() || '').trim(),
            codigo_barras: ($('#codigo_sin_variantes').val() || '').trim(),
            is_active: isActive,
            afecto_inventario: tracksInventory,
            pack: pack,
            cantidad_pack: cantidadPack,
            image_key: null,
            inventory_levels: buildSingleInventoryLevelsPayload()
        }
    ];
}

function buildSingleInventoryLevelsPayload() {
    let levels = [];
    let isActive = $('#is_active_sin_variante').is(':checked') ? 1 : 0;

    $('#tbody-inventory-levels-single tr').each(function () {
        let $row = $(this);

        let id = $row.find('[name$="[id]"]').val() || null;
        let warehouseId = $row.find('[name$="[warehouse_id]"]').val() || null;
        let minAlert = $row.find('[name$="[min_alert]"]').val();
        let maxAlert = $row.find('[name$="[max_alert]"]').val();

        if (!warehouseId) {
            return;
        }

        levels.push({
            id: id,
            warehouse_id: warehouseId,
            isActive: isActive,
            min_alert: (minAlert !== '' ? minAlert : 0),
            max_alert: (maxAlert !== '' ? maxAlert : 0)
        });
    });

    return levels;
}

function buildMultipleVariantsPayloadEdit(form) {
    let variantes = [];

    $('#body-variantes .item-variante').each(function (index) {
        let $row = $(this);

        let variantId = $row.find('[data-variant_id]').val() || null;
        let tallaId = $row.find('[data-talla_id]').val() || null;
        let colorId = $row.find('[data-color_id]').val() || null;
        let sku = ($row.find('[data-sku_sugerido]').val() || '').trim();
        let codigoBarras = ($row.find('[data-codigo_barras]').val() || '').trim();
        let isActive = $row.find('[data-is_active_variante]').is(':checked') ? 1 : 0;

        let tracksInventory = 1;
        let $tracksSwitch = $row.find('[data-afecto_inventario_variante]');
        if ($tracksSwitch.length) {
            tracksInventory = $tracksSwitch.is(':checked') ? 1 : 0;
        }

        let imageInput = $row.find('[data-image_variante]')[0];
        let imageKey = null;

        if (imageInput && imageInput.files && imageInput.files.length > 0) {
            imageKey = 'variant_image_' + index;
            form.append(imageKey, imageInput.files[0]);
        }

        variantes.push({
            variant_id: variantId,
            talla_id: tallaId,
            color_id: colorId,
            sku: sku,
            codigo_barras: codigoBarras,
            is_active: isActive,
            afecto_inventario: tracksInventory,
            pack: 0,
            cantidad_pack: 1,
            image_key: imageKey,
            inventory_levels: buildVariantInventoryLevelsPayload($row)
        });
    });

    return variantes;
}

function buildVariantInventoryLevelsPayload($row) {
    let levels = [];

    $row.find('[data-inventory_levels_body] tr').each(function () {
        let $tr = $(this);

        let id = $tr.find('[name$="[id]"]').val() || null;
        let warehouseId = $tr.find('[name$="[warehouse_id]"]').val() || null;
        let minAlert = $tr.find('[name$="[min_alert]"]').val();
        let maxAlert = $tr.find('[name$="[max_alert]"]').val();

        if (!warehouseId) {
            return;
        }

        levels.push({
            id: id,
            warehouse_id: warehouseId,
            min_alert: (minAlert !== '' ? minAlert : 0),
            max_alert: (maxAlert !== '' ? maxAlert : 0)
        });
    });

    return levels;
}

function renderTemplateItem(specification, content) {
    var clone = activateTemplate('#template-specification');
    clone.querySelector("[data-name]").setAttribute('value', specification);
    clone.querySelector("[data-content]").setAttribute('value', content);
    $('#body-specifications').append(clone);
}

function activateTemplate(id) {
    var t = document.querySelector(id);
    return document.importNode(t.content, true);
}