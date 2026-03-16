$(document).ready(function () {
    $formCreate = $('#formCreate');
    //$formCreate.on('submit', storeMaterial);
    $('#btn-submit').on('click', storeMaterial);
    
    $('#btn-add').on('click', showTemplateSpecification);

    $(document).on('click', '[data-delete]', deleteSpecification);

    $selectCategory = $('#category');

    $selectSubCategory = $('#subcategory');

    $selectBrand = $('#brand');

    $selectExampler = $('#exampler');

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
    
    $('#btn-generate').on('click', generateNameProduct);

    $('.btn-generateCode').on('click', generateCodeProduct);

    $('#btn-generate_variantes').on('click', generateVariants);

    $('#checkboxPack').on('change', checkInputPack);

    $('#inputPack').val('');
    $('#inputPack').prop('disabled', true);

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

    // Estado inicial
    toggleSeccionesVariantes();

    // Cuando cambie el radio
    $('input[name="variantes"]').on('change', function () {
        toggleSeccionesVariantes();
    });

    $('#description').on('keyup change', generateSku);

    $('#brand, #exampler, #category, #subcategory, #genero, #talla, #color')
        .on('change select2:select select2:clear', generateSku);

    //generateSku();

    $(document).on('click', '[data-delete]', function () {
        $(this).closest('.item-variante').remove();
    });
});

var $formCreate;
var $select;
var $selectCategory;
var $selectSubCategory;
var $selectBrand;
var $selectExampler;
var $selectType;
var $selectSubtype;
let $caracteres = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
let $longitud = 20;
let $btnNewExampler = $('#btn-newExampler');
let $btnNewSubCategoria = $('#btn-newSubCategoria');

function generateVariants() {
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
        toastr.warning('Debe ingresar un SKU base.');
        return;
    }

    let generatedCount = 0;

    colores.forEach(function (color) {
        tallas.forEach(function (talla) {
            if (!existsVariant(talla.id, color.id)) {
                appendVariantRow(talla, color, skuBase);
                generatedCount++;
            }
        });
    });

    if (generatedCount === 0) {
        toastr.warning('Todas las combinaciones seleccionadas ya fueron agregadas.');
        return;
    }

    toastr.success('Se generaron ' + generatedCount + ' variantes correctamente.');
}

function getSelectedShortNameOrText(selector) {
    let selected = $(selector).find('option:selected');
    return selected.data('short-name') || selected.text() || '';
}

function cleanText(text) {
    if (!text) return '';

    return text
        .toString()
        .trim()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-zA-Z0-9\s]/g, '')
        .replace(/\s+/g, ' ')
        .toUpperCase();
}

function getAbbr(text, length = 3) {
    text = cleanText(text);

    if (!text) return '';

    let words = text.split(/\s+/).filter(Boolean);

    // Si es una sola palabra → primeras 3 letras
    if (words.length === 1) {
        return words[0].substring(0, 3);
    }

    // Si son varias palabras → iniciales
    return words.map(w => w.charAt(0)).join('');
}

function getSelectedText(selector) {
    return $(selector).find('option:selected').text() || '';
}

function getSelectedOptionsData(selector) {
    let items = [];

    $(selector).find('option:selected').each(function () {
        items.push({
            id: $(this).val() || '',
            text: ($(this).text() || '').trim(),
            shortName: ($(this).data('short-name') || '').toString().trim()
        });
    });

    return items;
}

function existsVariant(tallaId, colorId) {
    let exists = false;

    $('#body-variantes .item-variante').each(function () {
        let currentTallaId = $(this).find('[data-talla_id]').val();
        let currentColorId = $(this).find('[data-color_id]').val();

        if (String(currentTallaId) === String(tallaId) && String(currentColorId) === String(colorId)) {
            exists = true;
            return false;
        }
    });

    return exists;
}

function appendVariantRow(talla, color, skuBase) {
    let tallaSku = cleanText(talla.shortName || talla.text);
    let colorSku = cleanText(color.shortName || color.text);
    let skuFinal = [skuBase, tallaSku, colorSku].filter(Boolean).join('-');

    let template = document.querySelector('#template-variante').content.cloneNode(true);
    let $template = $(template);

    $template.find('[data-talla_text]').val(talla.text);
    $template.find('[data-talla_id]').val(talla.id);

    $template.find('[data-color_text]').val(color.text);
    $template.find('[data-color_id]').val(color.id);

    $template.find('[data-sku_sugerido]').val(skuFinal);
    $template.find('[data-codigo_barras]').val('');
    $template.find('[data-stock_minimo]').val('');
    $template.find('[data-stock_maximo]').val('');

    $('#body-variantes').append($template);

    $("input[data-bootstrap-switch]").each(function(){
        $(this).bootstrapSwitch();
    });
}

function getSelectedOptionData(selector) {
    let $selected = $(selector).find('option:selected');
    return {
        id: $selected.val() || '',
        text: ($selected.text() || '').trim(),
        shortName: ($selected.data('short-name') || '').toString().trim()
    };
}

function getSwitchValue() {
    return $('#is_active').is(':checked') ? 1 : 0;
}

function getSwitchText() {
    return $('#is_active').is(':checked') ? 'SI' : 'NO';
}

function generateSkuBase() {
    let brand        = getAbbr(getSelectedText('#brand'));
    let exampler     = getAbbr(getSelectedText('#exampler'));
    let subcategory  = getAbbr(getSelectedText('#subcategory'));
    let genero       = getAbbr(getSelectedText('#genero'));

    let parts = [
        subcategory,
        brand,
        exampler,
        genero

    ].filter(part => part !== '');

    let sku;
    sku = parts.join('-');

    return sku;
}

function generateSku() {
    //let description  = getAbbr($('#description').val());
    let brand        = getAbbr(getSelectedText('#brand'));
    let exampler     = getAbbr(getSelectedText('#exampler'));
    let category     = getAbbr(getSelectedText('#category'));
    let subcategory  = getAbbr(getSelectedText('#subcategory'));
    let genero       = getAbbr(getSelectedText('#genero'));

    let talla = cleanText(getSelectedShortNameOrText('#talla'));
    let color = cleanText(getSelectedShortNameOrText('#color'));

    /*let parts = [
        description,
        brand,
        exampler,
        category,
        subcategory,
        genero,
        talla,
        color
    ].filter(part => part !== '');*/

    let parts = [
        subcategory,
        brand,
        exampler,
        genero,
        talla,
        color
    ].filter(part => part !== '');

    let sku = parts.join('-');

    //$('#sku_con_variantes').val(sku);
    $('#sku_sin_variantes').val(sku);
}

function toggleSeccionesVariantes() {
    let tipo = $('input[name="variantes"]:checked').val();

    if (tipo === '1') {
        $('#seccion_sin_variantes').hide();
        $('#seccion_con_variantes').show();
        limpiarSeccionSinVariantes();
    } else {
        $('#seccion_con_variantes').hide();
        $('#seccion_sin_variantes').show();
        limpiarSeccionConVariantes();
    }
}

function limpiarSeccionConVariantes() {
    $('#talla').val(null).trigger('change');
    $('#color').val(null).trigger('change');

    $('#body-variantes').empty();
}

function limpiarSeccionSinVariantes() {
    $('#sku_sin_variantes').val('');
    $('#codigo_sin_variantes').val('');
    $('#stock_max').val(0);
    $('#stock_min').val(0);
}

function saveSubCategoria() {
    let $form = $('#formCreateSubCategoria');
    let url = $form.data('url');
    let formData = $form.serialize();

    $.post(url, formData, function (response) {
        if (response && response.data) {
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
    $('#codigo_sin_variantes').val(codigo);
    $('#codigo_con_variantes').val(codigo);
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
    //if (subcategoria !== 'Ninguno' && subcategoria !== '') partes.push(subcategoria);

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

function getCheckboxValue($element) {
    return $element.is(':checked') ? 1 : 0;
}

function buildSingleVariantPayload() {
    let pack = $('#checkboxPack').is(':checked') ? 1 : 0;
    let cantidadPack = ($('#inputPack').val() || 1);

    return [
        {
            talla_id: null,
            color_id: null,
            sku: ($('#sku_sin_variantes').val() || '').trim(),
            codigo_barras: ($('#codigo_sin_variantes').val() || '').trim(),
            stock_minimo: ($('#stock_min').val() || 0),
            stock_maximo: ($('#stock_max').val() || 0),
            is_active: 1,
            pack: pack,
            cantidad_pack: cantidadPack,
            image_key: null
        }
    ];
}

function buildMultipleVariantsPayload(form) {
    let variantes = [];
    let pack = $('#checkboxPack').is(':checked') ? 1 : 0;
    let cantidadPack = ($('#inputPack').val() || 1);

    $('#body-variantes .item-variante').each(function (index) {
        let $row = $(this);

        let tallaId = $row.find('[data-talla_id]').val() || null;
        let colorId = $row.find('[data-color_id]').val() || null;
        let sku = ($row.find('[data-sku_sugerido]').val() || '').trim();
        let codigoBarras = ($row.find('[data-codigo_barras]').val() || '').trim();
        let stockMinimo = ($row.find('[data-stock_minimo]').val() || 0);
        let stockMaximo = ($row.find('[data-stock_maximo]').val() || 0);

        let $switch = $row.find('[data-is_active_variante]');
        let isActive = $switch.is(':checked') ? 1 : 0;

        let imageInput = $row.find('[data-image_variante]')[0];
        let imageKey = null;

        if (imageInput && imageInput.files && imageInput.files.length > 0) {
            imageKey = 'variant_image_' + index;
            form.append(imageKey, imageInput.files[0]);
        }

        variantes.push({
            talla_id: tallaId,
            color_id: colorId,
            sku: sku,
            codigo_barras: codigoBarras,
            stock_minimo: stockMinimo,
            stock_maximo: stockMaximo,
            is_active: isActive,
            pack: pack,
            cantidad_pack: cantidadPack,
            image_key: imageKey
        });
    });

    return variantes;
}

function validateVariantes(tipo, variantes) {
    if (tipo === '1') {
        if (variantes.length === 0) {
            toastr.warning('Debe ingresar la información del producto.');
            return false;
        }

        if (!variantes[0].sku) {
            toastr.warning('Debe ingresar el SKU.');
            return false;
        }

        return true;
    }

    if (variantes.length === 0) {
        toastr.warning('Debe generar al menos una variante.');
        return false;
    }

    for (let i = 0; i < variantes.length; i++) {
        if (!variantes[i].sku) {
            toastr.warning('Todas las variantes deben tener SKU.');
            return false;
        }
    }

    return true;
}

function storeMaterial(event) {
    event.preventDefault();

    $("#btn-submit").attr("disabled", true);

    let createUrl = $formCreate.data('url');
    let form = new FormData($('#formCreate')[0]);

    if (uploadedImage) {
        form.append('image', uploadedImage);
    }

    let tipo = $('input[name="variantes"]:checked').val();
    let variantes_json = [];

    if (tipo === '0') {
        variantes_json = buildSingleVariantPayload();
    } else {
        variantes_json = buildMultipleVariantsPayload(form);
    }

    if (!validateVariantes(tipo, variantes_json)) {
        $("#btn-submit").attr("disabled", false);
        return;
    }

    form.append('tipo_variantes', tipo);
    form.append('variantes_json', JSON.stringify(variantes_json));

    $.ajax({
        url: createUrl,
        method: 'POST',
        data: form,
        processData: false,
        contentType: false,
        success: function (data) {
            console.log(data);

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
                for (var property in data.responseJSON.errors) {
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
                toastr.error('Ocurrió un error al guardar el producto.', 'Error');
            }

            $("#btn-submit").attr("disabled", false);
        }
    });
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