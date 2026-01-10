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

function updateMaterial() {
    event.preventDefault();
    $("#btn-submit").attr("disabled", true);
    // Obtener la URL
    var editUrl = $formEdit.data('url');
    var form = new FormData($('#formEdit')[0]);
    $.ajax({
        url: editUrl,
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
                        "timeOut": "4000",
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