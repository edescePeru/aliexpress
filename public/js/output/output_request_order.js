let $materials=[];
let $users=[];
let $usersComplete=[];
let $materialsComplete=[];
let $items=[];
let $itemsComplete=[];
let $itemsSelected=[];
let $materials_order=[];
var $permissions;

$(document).ready(function () {
    $permissions = JSON.parse($('#permissions').val());

    $materials_order = JSON.parse($('#materials').val());
    $("#element_loader").LoadingOverlay("show", {
        background  : "rgba(236, 91, 23, 0.5)"
    });
    $('input[name="request_date"]').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        minYear: 1901,
        maxYear: parseInt(moment().format('YYYY'),20),
        startDate: moment().format('DD/MM/YYYY'),
        locale: {
            "format": 'DD/MM/YYYY',
            "applyLabel": "Guardar",
            "cancelLabel": "Cancelar",
            "fromLabel": "Desde",
            "toLabel": "Hasta",
            "customRangeLabel": "Personalizar",
            "daysOfWeek": [
                "Do",
                "Lu",
                "Ma",
                "Mi",
                "Ju",
                "Vi",
                "Sa"
            ],
            "monthNames": [
                "Enero",
                "Febrero",
                "Marzo",
                "Abril",
                "Mayo",
                "Junio",
                "Julio",
                "Agosto",
                "Setiembre",
                "Octubre",
                "Noviembre",
                "Diciembre"
            ],
            "firstDay": 1
        }
    });

    $.ajax({
        url: "/dashboard/get/materials",
        type: 'GET',
        dataType: 'json',
        success: function (json) {

            for (var i=0; i<json.length; i++)
            {
                for ( var j=0; j<$materials_order.length; j++ )
                {
                    //console.log(json[i]);
                    if ( $materials_order[j].material_id === json[i].id )
                    {
                        //console.log('Hay igualdad');
                        $materials.push(json[i].material);
                        $materialsComplete.push(json[i]);
                    }

                }

            }
            $("#element_loader").LoadingOverlay("hide", true);
        }
    });
    $.ajax({
        url: "/dashboard/get/users",
        type: 'GET',
        dataType: 'json',
        success: function (json) {
            for (var i=0; i<json.length; i++)
            {
                $users.push(json[i].name);
                $usersComplete.push(json[i]);
            }

        }
    });

    /*$('#responsible_user').typeahead({
            hint: true,
            highlight: true, /!* Enable substring highlighting *!/
            minLength: 1 /!* Specify minimum characters required for showing suggestions *!/
        },
        {
            limit: 12,
            source: substringMatcher($users)
        });*/

    $('.typeahead').typeahead({
            hint: true,
            highlight: true, /* Enable substring highlighting */
            minLength: 1 /* Specify minimum characters required for showing suggestions */
        },
        {
            limit: 12,
            source: substringMatcher($materials)
        });

    $('#btn-add').on('click', addItems);
    $modalAddItems = $('#modalAddItems');

    $('#btn-saveItems').on('click', saveTableItems);

    $('#btn-request-quantity').on('click', requestItemsQuantity);
    $('#material_selected_quantity').on('keyup', requestItemsQuantity2);

    $('#width_new_custom').on('keyup', saveTableItemsCustom2);
    $('#length_new_custom').on('keyup', saveTableItemsCustom2);

    $('#btn-add-scrap').on('click', addItemsScrap);

    // TODO: Agregamos los retazos personalizados
    $modalAddItemsCustom = $('#modalAddItemsCustom');
    $('#btn-add-custom').on('click', addItemsCustom);
    $('#btn-saveItemsCustom').on('click', saveTableItemsCustom);

    $(document).on('click', '[data-delete]', deleteItem);

    $(document).on('change', '[data-selected]', selectItem);

    $formCreate = $("#formCreate");
    //$formCreate.on('submit', storeOutputRequest);
    $('#btn-submit').on('click', storeOutputRequest);

    $('#btn-follow').on('click', followMaterial);

    $('#btn-unfollow').on('click', unfollowMaterial);

    $(document).on('click', '[data-show]', showDetailsMaterial);
    $modalShowDetailsMaterial = $('#modalShowDetailsMaterial');

    $(document).on('click', '[data-follow]', showFollowMaterial);
    $modalFollowDetailsMaterial = $('#modalFollowDetailsMaterial');
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

let $modalAddItems;

let $modalAddItemsCustom;
let $modalShowDetailsMaterial;
let $modalFollowDetailsMaterial;

let $caracteres = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

let $longitud = 20;

function showFollowMaterial() {
    event.preventDefault();
    $('#body-follow').html('');
    var button = $(this);
    button.attr("disabled", true);
    var material_id = $(this).data('material');
    var quote_id = $(this).data('quote');
    var material_name = $(this).data('name');
    /* FOLLOW-01: consulta/acción legacy de seguimiento retirada. */
}

function showDetailsMaterial() {
    event.preventDefault();
    var button = $(this);
    button.attr("disabled", true);
    var material_id = $(this).data('material');
    var quote_id = $(this).data('quote');
    var material_name = $(this).data('name');
    $.ajax({
        url: "/dashboard/get/json/quantity/output/material/"+quote_id+"/"+material_id,
        type: 'GET',
        dataType: 'json',
        success: function (json) {
            console.log(json);
            $('#material_selected_details').val(material_name);
            $('#quantity_material').val(json.quantity);
            $('#request_material').val(json.request);
            $('#missing_material').val(json.missing);
            button.attr("disabled", false);
            $modalShowDetailsMaterial.modal('show');

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
        }
    });
}

function followMaterial() {
    var material_id = $(this).data('follow');
    $('#btn-follow').attr("disabled", true);
    /* FOLLOW-01: consulta/acción legacy de seguimiento retirada. */
}

function unfollowMaterial() {
    var material_id = $(this).data('unfollow');
    $('#btn-unfollow').attr("disabled", true);

    /* FOLLOW-01: consulta/acción legacy de seguimiento retirada. */
}

function saveTableItems() {
    event.preventDefault();
    console.log($itemsSelected);
    var equipment_id = $modalAddItems.find('[id=equipment]').val();
    var equipment_name = $modalAddItems.find('[id=equipment_name]').val();

    for ( let j=0; j<$itemsSelected.length; j++ )
    {
        if ( $items.find(x => x.item === $itemsSelected[j].id ) )
        {
            toastr.error('Hay items repetios. Elija otro', 'Error',
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
    }

    for ( let i=0; i<$itemsSelected.length; i++ )
    {
        $items.push({'material_id':$itemsSelected[i].material_id,'equipment_name':equipment_name,'equipment_id': equipment_id,'item': $itemsSelected[i].id, 'percentage': $itemsSelected[i].percentage, 'length':$itemsSelected[i].length, 'width':$itemsSelected[i].width, 'price':$itemsSelected[i].price});
        renderTemplateMaterial(equipment_name, $itemsSelected[i].material, $itemsSelected[i].code, $itemsSelected[i].location, $itemsSelected[i].state,  $itemsSelected[i].price, $itemsSelected[i].id, $itemsSelected[i].length,$itemsSelected[i].width);
    }

    $('#material_search').val('');
    $('#material_selected').val('');
    $('#body-items').html('');
    $("#equipments_order").val('').trigger('change');

    $itemsSelected = [];

    $modalAddItems.modal('hide');
}

function selectItem() {
    event.preventDefault();
    if (this.checked) {
        let itemId = $(this).data('selected');
        const result = $itemsComplete.find( item => item.id === itemId );
        $itemsSelected.push(result);
        console.log($itemsSelected);
    } else {
        let itemD = $(this).data('selected');
        const result = $itemsComplete.find( item => item.id === itemD );
        if (result)
        {
            $itemsSelected = $.grep($itemsSelected, function(e){
                return e.id !== itemD;
            });
        }
        console.log($itemsSelected);
    }
    $modalAddItems.scrollTop( 0 );
}

function addItems() {
    $itemsComplete = [];
    $itemsSelected = [];
    $('#show-btn-follow').hide();
    $('#show-btn-unfollow').hide();

    //$("#material_unit").val('').trigger('change');
    //$( "#material_unit option:selected" ).text();
    console.log($('#equipments_order').val());
    var equipment = $('#equipments_order').val();
    var equipment_name = $('#equipments_order option:selected').text();

    if( $('#equipments_order').val() == '' )
    {
        toastr.error('Debe elegir un equipo', 'Error',
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
    } else {
        let result2 = $materialsComplete.find( material => material.material.trim() === $('#material_search').val().trim() );
        if ( !result2  ){
            toastr.error('No hay coincidencias de lo escrito con algún material', 'Error',
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
        } else {
            if ( parseFloat(result2.stock_current) <= 0 )
            {
                toastr.error('No hay stock del material', 'Error',
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
        }
    }

    let material_name = $('#material_search').val();
    $modalAddItems.find('[id=material_selected]').val(material_name);
    $modalAddItems.find('[id=material_selected]').prop('disabled', true);
    $modalAddItems.find('[id=material_selected_quantity]').prop('disabled', false);

    $('#body-items').html('');

    const result = $materialsComplete.find( material => material.material.trim() === material_name.trim() );

    $("#body-items-load").LoadingOverlay("show", {
        background  : "rgba(236, 91, 23, 0.5)"
    });

    $('#show-btn-follow').hide();
    $('#show-btn-unfollow').hide();
    // TODO: Agregamos la logica de preguntar si lo esta siguiendo al material o no
    /* FOLLOW-01: consulta/acción legacy de seguimiento retirada. */

    $.ajax({
        url: "/dashboard/get/items/output/complete/"+result.id,
        type: 'GET',
        dataType: 'json',
        success: function (json) {
            for (var i=0; i<json.length; i++)
            {
                //$users.push(json[i].name);
                $itemsComplete.push(json[i]);
                renderTemplateItem(i+1, json[i].code, json[i].location, json[i].length, json[i].width, json[i].weight, json[i].price, json[i].id);
            }
            $("#body-items-load").LoadingOverlay("hide", true);
        }
    });

    console.log($itemsComplete);

    $('#material_selected_quantity').val('');
    $modalAddItems.find('[id=equipment]').val(equipment);
    $modalAddItems.find('[id=equipment_name]').val(equipment_name);
    $modalAddItems.find('[id=show_btn_request_quantity]').show();
    $modalAddItems.modal('show');

    /*$items.push({
        "productId" : sku,
        "qty" : qty,
        "price" : price
    });*/
}

function requestItemsQuantity() {

    let material_name = $('#material_selected').val();
    let material_quantity = $('#material_selected_quantity').val();
    const result = $materialsComplete.find( material => material.material.trim() === material_name.trim() );
    let material_stock = result.stock_current;
    if( material_name.trim() === '' )
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
    if( parseFloat(material_quantity) > parseFloat(material_stock) )
    {
        toastr.error('No hay stock suficiente en el almacén', 'Error',
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

    $('#body-items').html('');
    $itemsSelected = [];

    $.ajax({
        url: "/dashboard/get/items/output/complete/"+result.id,
        type: 'GET',
        dataType: 'json',
        success: function (json){
            let iterator = 1;
            for (var i=0; i<json.length; i++)
            {
                //$users.push(json[i].name);
                //$itemsComplete.push(json[i]);
                if (iterator <= material_quantity)
                {
                    renderTemplateItemSelected(i+1, json[i].code, json[i].location, json[i].length, json[i].width, json[i].weight, json[i].price, json[i].id);
                    const result = $itemsComplete.find( item => item.id == json[i].id );
                    $itemsSelected.push(result);
                    iterator+=1;
                } else {
                    renderTemplateItem(i+1, json[i].code, json[i].location, json[i].length, json[i].width, json[i].weight, json[i].price, json[i].id);
                    iterator+=1;
                }
            }

        }
    });



}

function requestItemsQuantity2(event) {
    if (event.keyCode === 13) {
        let material_name = $('#material_selected').val();
        let material_quantity = $('#material_selected_quantity').val();
        const result = $materialsComplete.find( material => material.material.trim() === material_name.trim() );
        let material_stock = result.stock_current;
        if( material_name.trim() === '' )
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
        if( parseFloat(material_quantity) > parseFloat(material_stock) )
        {
            toastr.error('No hay stock suficiente en el almacén', 'Error',
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

        $('#body-items').html('');
        $itemsSelected = [];

        $.ajax({
            url: "/dashboard/get/items/output/complete/"+result.id,
            type: 'GET',
            dataType: 'json',
            success: function (json){
                let iterator = 1;
                for (var i=0; i<json.length; i++)
                {
                    //$users.push(json[i].name);
                    //$itemsComplete.push(json[i]);
                    if (iterator <= material_quantity)
                    {
                        renderTemplateItemSelected(i+1, json[i].code, json[i].location, json[i].length, json[i].width, json[i].weight, json[i].price, json[i].id);
                        const result = $itemsComplete.find( item => item.id == json[i].id );
                        $itemsSelected.push(result);
                        iterator+=1;
                    } else {
                        renderTemplateItem(i+1, json[i].code, json[i].location, json[i].length, json[i].width, json[i].weight, json[i].price, json[i].id);
                        iterator+=1;
                    }
                }

            }
        });
    }
}

function addItemsScrap() {
    $itemsComplete = [];
    $itemsSelected = [];
    $('#show-btn-follow').hide();
    $('#show-btn-unfollow').hide();

    //$("#material_unit").val('').trigger('change');
    //$( "#material_unit option:selected" ).text();
    console.log($('#equipments_order').val());
    var equipment = $('#equipments_order').val();
    var equipment_name = $('#equipments_order option:selected').text();

    if( $('#equipments_order').val() === '' )
    {
        toastr.error('Debe elegir un equipo', 'Error',
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

    console.log($('#equipments_order').val());

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
    } else {
        let result2 = $materialsComplete.find( material => material.material.trim() === $('#material_search').val().trim() );
        if ( !result2  ){
            toastr.error('No hay coincidencias de lo escrito con algún material', 'Error',
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
        } else {
            if ( result2.typescrap == "" || result2.typescrap == null ){
                toastr.error('El material no permite retazos.', 'Error',
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
            if ( parseFloat(result2.stock_current) <= 0 )
            {
                toastr.error('No hay stock del material', 'Error',
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
        }
    }

    let material_name = $('#material_search').val();
    $modalAddItems.find('[id=material_selected]').val(material_name);
    $modalAddItems.find('[id=material_selected]').prop('disabled', true);
    $modalAddItems.find('[id=material_selected_quantity]').prop('disabled', true);
    $modalAddItems.find('[id=show_btn_request_quantity]').hide();
    $('#material_selected_quantity').val('');
    $('#body-items').html('');

    $("#body-items-load").LoadingOverlay("show", {
        background  : "rgba(236, 91, 23, 0.5)"
    });

    const result = $materialsComplete.find( material => material.material.trim() === material_name.trim() );
    console.log(result);

    $('#show-btn-follow').hide();
    $('#show-btn-unfollow').hide();
    // TODO: Agregamos la logica de preguntar si lo esta siguiendo al material o no
    /* FOLLOW-01: consulta/acción legacy de seguimiento retirada. */

    $.ajax({
        url: "/dashboard/get/items/output/scraped/"+result.id,
        type: 'GET',
        dataType: 'json',
        success: function (json) {
            for (var i=0; i<json.length; i++)
            {
                //$users.push(json[i].name);
                $itemsComplete.push(json[i]);
                renderTemplateItem(i+1, json[i].code, json[i].location, json[i].length, json[i].width, json[i].weight, json[i].price, json[i].id);
            }
            $("#body-items-load").LoadingOverlay("hide", true);
        }
    });

    console.log($itemsComplete);

    $modalAddItems.find('[id=equipment]').val(equipment);
    $modalAddItems.find('[id=equipment_name]').val(equipment_name);
    $modalAddItems.modal('show');

}

// TODO: agregamos item custom
function addItemsCustom() {
    $itemsComplete = [];
    $itemsSelected = [];
    $('#show-btn-follow2').hide();
    $('#show-btn-unfollow2').hide();
    //$("#material_unit").val('').trigger('change');
    //$( "#material_unit option:selected" ).text();

    console.log($('#equipments_order').val());
    var equipment = $('#equipments_order').val();
    var equipment_name = $('#equipments_order option:selected').text();

    if( $('#equipments_order').val() === '' )
    {
        toastr.error('Debe elegir un equipo', 'Error',
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

    console.log($('#equipments_order').val());

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
    } else {
        let result2 = $materialsComplete.find( material => material.material.trim() === $('#material_search').val().trim() );
        if ( !result2  ){
            toastr.error('No hay coincidencias de lo escrito con algún material', 'Error',
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
        } else {
            if ( result2.typescrap == "" || result2.typescrap == null ){
                toastr.error('El material no permite retazos.', 'Error',
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
            if ( parseFloat(result2.stock_current) <= 0 )
            {
                toastr.error('No hay stock del material', 'Error',
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
        }
    }

    let material_name = $('#material_search').val();
    $modalAddItemsCustom.find('[id=material_selected]').val(material_name);
    $modalAddItemsCustom.find('[id=material_selected]').prop('disabled', true);
    $modalAddItemsCustom.find('[id=material_selected_quantity]').prop('disabled', true);
    $modalAddItemsCustom.find('[id=show_btn_request_quantity]').hide();
    $('#material_selected_quantity').val('');
    $('#body-items').html('');

    $("#body-items-load").LoadingOverlay("show", {
        background  : "rgba(236, 91, 23, 0.5)"
    });

    const result = $materialsComplete.find( material => material.material.trim() === material_name.trim() );
    console.log(result);

    $('#material_selected_custom').val(result.material);
    if ( result.typescrap == 1 || result.typescrap == 2 )
    {
        $('#length_item_custom').show();
        $('#width_item_custom').show();
        $('#length_custom').val(result.full_typescrap.length);
        $('#width_custom').val(result.full_typescrap.width);

        $('#length_new_item_custom').show();
        $('#length_new_item_custom').val(0);
        $('#width_new_item_custom').show();
        $('#width_new_item_custom').val(0);
    }
    if ( result.typescrap == 3 || result.typescrap == 4 )
    {
        $('#length_item_custom').show();
        $('#width_item_custom').hide();
        $('#length_custom').val(result.full_typescrap.length);
        $('#width_custom').val(result.full_typescrap.width);

        $('#length_new_item_custom').show();
        $('#length_new_item_custom').val(0);
        $('#width_new_item_custom').hide();
        $('#width_new_item_custom').val(0);
    }

    $('#show-btn-follow2').hide();
    $('#show-btn-unfollow2').hide();
    // TODO: Agregamos la logica de preguntar si lo esta siguiendo al material o no
    /* FOLLOW-01: consulta/acción legacy de seguimiento retirada. */
    $modalAddItemsCustom.find('[id=equipment_custom]').val(equipment);
    $modalAddItemsCustom.find('[id=equipment_name_custom]').val(equipment_name);
    $modalAddItemsCustom.modal('show');

    /*$items.push({
        "productId" : sku,
        "qty" : qty,
        "price" : price
    });*/
}

function saveTableItemsCustom() {
    event.preventDefault();
    var equipment_id = $modalAddItemsCustom.find('[id=equipment_custom]').val();
    var equipment_name = $modalAddItemsCustom.find('[id=equipment_name_custom]').val();

    const result = $materialsComplete.find( material => material.material.trim() === $('#material_selected_custom').val().trim() );


    if ( result.typescrap == 1 || result.typescrap == 2 )
    {
        if ($('#length_new_custom').val() == '' || $('#length_new_custom').val() == 0)
        {
            toastr.error('Debe colocar un largo adecuado', 'Error',
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
        if ($('#width_new_custom').val() == '' || $('#width_new_custom').val() == 0)
        {
            toastr.error('Debe colocar un ancho adecuado', 'Error',
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
    }
    if ( result.typescrap == 3 || result.typescrap == 4 )
    {
        if ($('#length_new_custom').val() == '' || $('#length_new_custom').val() == 0)
        {
            toastr.error('Debe colocar un largo adecuado', 'Error',
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
    }

    if ( result.typescrap == 1 || result.typescrap == 2 )
    {
        let largo = $('#length_new_custom').val();
        console.log(largo);
        let ancho = $('#width_new_custom').val();
        console.log(ancho);
        let areaPedida = parseFloat(largo) * parseFloat(ancho);
        console.log(areaPedida);
        let areaTotal = parseFloat(result.full_typescrap.length) * parseFloat(result.full_typescrap.width);
        console.log(areaTotal);
        let porcentaje = parseFloat(areaPedida/areaTotal).toFixed(2);
        console.log(porcentaje);
        let precio = result.price * porcentaje;
        console.log(precio);

        let code = rand_code($caracteres, 5);
        $items.push({'material_id':result.id,'equipment_name':equipment_name,'equipment_id': equipment_id,'item': 'Personalizado_'+code, 'percentage': porcentaje, 'length': largo, 'width': ancho, 'price': precio, 'material': result.id});
        renderTemplateMaterial(equipment_name, result.material, 'Personalizado_'+code, 'Sin ubicación', 'Sin estado',  precio, 'Personalizado_'+code, largo, ancho);

    }
    if ( result.typescrap == 3 || result.typescrap == 4 )
    {
        let largo = $('#length_new_custom').val();
        let areaPedida = parseFloat(largo);
        let areaTotal = parseFloat(result.full_typescrap.length);
        let porcentaje = parseFloat((areaPedida/areaTotal)).toFixed(2);
        let precio = parseFloat(result.price * porcentaje).toFixed(2);
        let code = rand_code($caracteres, 5);
        $items.push({'material_id':result.id,'equipment_name':equipment_name,'equipment_id': equipment_id,'item': 'Personalizado_'+code, 'percentage': porcentaje, 'length': largo, 'width': null, 'price': precio, 'material': result.id});
        renderTemplateMaterial(equipment_name,result.material, 'Personalizado_'+code, 'Sin ubicación', 'Sin estado',  precio, 'Personalizado_'+code, largo, 'S/N');

    }

    $('#material_selected_custom').val('');
    $('#length_custom').val('');
    $('#width_custom').val('');
    $('#length_new_custom').val('');
    $('#width_new_custom').val('');
    $("#equipments_order").val('').trigger('change');
    $itemsSelected = [];

    $modalAddItemsCustom.modal('hide');
}

function saveTableItemsCustom2(event) {
    if (event.keyCode === 13) {
        event.preventDefault();

        var equipment_id = $modalAddItemsCustom.find('[id=equipment_custom]').val();
        var equipment_name = $modalAddItemsCustom.find('[id=equipment_name_custom]').val();

        const result = $materialsComplete.find( material => material.material.trim() === $('#material_selected_custom').val().trim() );

        if ( result.typescrap == 1 || result.typescrap == 2 )
        {
            if ($('#length_new_custom').val() == '' || $('#length_new_custom').val() == 0)
            {
                toastr.error('Debe colocar un largo adecuado', 'Error',
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
            if ($('#width_new_custom').val() == '' || $('#width_new_custom').val() == 0)
            {
                toastr.error('Debe colocar un ancho adecuado', 'Error',
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
        }
        if ( result.typescrap == 3 || result.typescrap == 4 )
        {
            if ($('#length_new_custom').val() == '' || $('#length_new_custom').val() == 0)
            {
                toastr.error('Debe colocar un largo adecuado', 'Error',
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
        }

        if ( result.typescrap == 1 || result.typescrap == 2 )
        {
            let largo = $('#length_new_custom').val();
            console.log(largo);
            let ancho = $('#width_new_custom').val();
            console.log(ancho);
            let areaPedida = parseFloat(largo) * parseFloat(ancho);
            console.log(areaPedida);
            let areaTotal = parseFloat(result.full_typescrap.length) * parseFloat(result.full_typescrap.width);
            console.log(areaTotal);
            let porcentaje = parseFloat(areaPedida/areaTotal).toFixed(2);
            console.log(porcentaje);
            let precio = result.price * porcentaje;
            console.log(precio);

            let code = rand_code($caracteres, 5);
            //$items.push({'item': 'Personalizado_'+code, 'percentage': porcentaje, 'length': largo, 'width': ancho, 'price': precio, 'material': result.id});
            $items.push({'material_id':result.id,'equipment_name':equipment_name,'equipment_id': equipment_id,'item': 'Personalizado_'+code, 'percentage': porcentaje, 'length': largo, 'width': ancho, 'price': precio, 'material': result.id});

            //renderTemplateMaterial(result.material, 'Personalizado_'+code, 'Sin ubicación', 'Sin estado',  precio, 'Personalizado_'+code, largo, ancho);
            renderTemplateMaterial(equipment_name, result.material, 'Personalizado_'+code, 'Sin ubicación', 'Sin estado',  precio, 'Personalizado_'+code, largo, ancho);

        }
        if ( result.typescrap == 3 || result.typescrap == 4 )
        {
            let largo = $('#length_new_custom').val();
            let areaPedida = parseFloat(largo);
            let areaTotal = parseFloat(result.full_typescrap.length);
            let porcentaje = parseFloat((areaPedida/areaTotal)).toFixed(2);
            let precio = parseFloat(result.price * porcentaje).toFixed(2);
            let code = rand_code($caracteres, 5);
            //$items.push({'item': 'Personalizado_'+code, 'percentage': porcentaje, 'length': largo, 'width': null, 'price': precio, 'material': result.id});
            $items.push({'material_id':result.id,'equipment_name':equipment_name,'equipment_id': equipment_id,'item': 'Personalizado_'+code, 'percentage': porcentaje, 'length': largo, 'width': null, 'price': precio, 'material': result.id});

            //renderTemplateMaterial(result.material, 'Personalizado_'+code, 'Sin ubicación', 'Sin estado',  precio, 'Personalizado_'+code, largo, 'S/N');
            renderTemplateMaterial(equipment_name,result.material, 'Personalizado_'+code, 'Sin ubicación', 'Sin estado',  precio, 'Personalizado_'+code, largo, 'S/N');

        }
        $('#material_search').val('');
        $('#material_selected').val('');
        $('#material_selected_custom').val('');
        $('#length_custom').val('');
        $('#width_custom').val('');
        $('#length_new_custom').val('');
        $('#width_new_custom').val('');
        $("#equipments_order").val('').trigger('change');
        $itemsSelected = [];

        $modalAddItemsCustom.modal('hide');
    }

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

function deleteItem() {
    //console.log($(this).parent().parent().parent());
    var itemId = $(this).data('delete');
    $items = $items.filter(item => item.item !== itemId);
    $(this).parent().parent().remove();
}

function renderTemplateFollow(json) {
    var array = json[0].orders;
    var array2 = json[0].dates;
    var orders = '';
    for (let i = 0; i < array.length; i++) {
        orders = orders + '<a target="_blank" href="'+document.location.origin+ '/dashboard/visualizar/orden/compra/'+array[i]+'"> <span class="badge bg-success">' + array[i] + '</span> </a> ';
    }

    var dates = '';
    for (let i = 0; i < array2.length; i++) {
        dates = dates + ' <span class="badge bg-primary">' + array2[i] + '</span> ';
    }
    var clone = activateTemplate('#template-follow');

    var state = (json[0].state === 'red') ? '<span class="badge bg-danger">Sin stock</span>':
        (json[0].state === 'yellow') ? '<span class="badge bg-warning">En orden</span>': '<span class="badge bg-success">En almacén</span>' ;

    clone.querySelector("[data-code]").innerHTML = json[0].code;
    clone.querySelector("[data-stock]").innerHTML = json[0].stock;
    clone.querySelector("[data-state]").innerHTML = state;
    clone.querySelector("[data-orders]").innerHTML = orders;
    clone.querySelector("[data-dates]").innerHTML = dates;
    $('#body-follow').append(clone);
}


function renderTemplateMaterial(equipment, material, item, location, state, price, id, length, width) {
    var clone = activateTemplate('#materials-selected');
    if ( $.inArray('showPrices_quote', $permissions) !== -1 ) {
        clone.querySelector("[data-equipment]").innerHTML = equipment;
        clone.querySelector("[data-description]").innerHTML = material;
        clone.querySelector("[data-item]").innerHTML = item;
        clone.querySelector("[data-price]").innerHTML = parseFloat(price).toFixed(2);
        clone.querySelector("[data-state]").innerHTML = state;
        clone.querySelector("[data-length]").innerHTML = length;
        clone.querySelector("[data-width]").innerHTML = width;
        clone.querySelector("[data-delete]").setAttribute('data-delete', id);
        $('#body-materials').append(clone);
    } else {
        clone.querySelector("[data-equipment]").innerHTML = equipment;
        clone.querySelector("[data-description]").innerHTML = material;
        clone.querySelector("[data-item]").innerHTML = item;
        clone.querySelector("[data-price]").innerHTML = '';
        clone.querySelector("[data-state]").innerHTML = state;
        clone.querySelector("[data-length]").innerHTML = length;
        clone.querySelector("[data-width]").innerHTML = width;
        clone.querySelector("[data-delete]").setAttribute('data-delete', id);
        $('#body-materials').append(clone);
    }
}

function renderTemplateItem(i, code, location, length, width, weight, price, id) {
    var clone = activateTemplate('#template-item');
    if ( $.inArray('showPrices_quote', $permissions) !== -1 ) {
        clone.querySelector("[data-id]").innerHTML = i;
        clone.querySelector("[data-serie]").innerHTML = code;
        clone.querySelector("[data-location]").innerHTML = location;
        clone.querySelector("[data-length]").innerHTML = length;
        clone.querySelector("[data-width]").innerHTML = width;
        clone.querySelector("[data-price]").innerHTML = price;
        clone.querySelector("[data-selected]").setAttribute('data-selected', id);
        clone.querySelector("[data-selected]").setAttribute('id', 'checkboxSuccess'+id);
        clone.querySelector("[data-label]").setAttribute('for', 'checkboxSuccess'+id);

    } else {
        clone.querySelector("[data-id]").innerHTML = i;
        clone.querySelector("[data-serie]").innerHTML = code;
        clone.querySelector("[data-location]").innerHTML = location;
        clone.querySelector("[data-length]").innerHTML = length;
        clone.querySelector("[data-width]").innerHTML = width;
        clone.querySelector("[data-price]").innerHTML = '';
        clone.querySelector("[data-selected]").setAttribute('data-selected', id);
        clone.querySelector("[data-selected]").setAttribute('id', 'checkboxSuccess'+id);
        clone.querySelector("[data-label]").setAttribute('for', 'checkboxSuccess'+id);

    }
    $('#body-items').append(clone);
}

function renderTemplateItemSelected(i, code, location, length, width, weight, price, id) {
    var clone = activateTemplate('#template-item');
    clone.querySelector("[data-id]").innerHTML = i;
    clone.querySelector("[data-serie]").innerHTML = code;
    clone.querySelector("[data-location]").innerHTML = location;
    clone.querySelector("[data-length]").innerHTML = length;
    clone.querySelector("[data-width]").innerHTML = width;
    clone.querySelector("[data-weight]").innerHTML = weight;
    clone.querySelector("[data-price]").innerHTML = price;
    clone.querySelector("[data-selected]").setAttribute('data-selected', id);
    clone.querySelector("[data-selected]").setAttribute('id', 'checkboxSuccess'+id);
    clone.querySelector("[data-selected]").setAttribute('checked', 'checked');
    clone.querySelector("[data-label]").setAttribute('for', 'checkboxSuccess'+id);
    $('#body-items').append(clone);
}

function activateTemplate(id) {
    var t = document.querySelector(id);
    return document.importNode(t.content, true);
}

function storeOutputRequest() {
    event.preventDefault();
    $("#btn-submit").attr("disabled", true);
    // Obtener la URL
    var createUrl = $formCreate.data('url');
    var items = JSON.stringify($items);
    var form = new FormData($('#formCreate')[0]);
    form.append('items', items);
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
                if ( $.inArray('list_request', $permissions) !== -1 ) {
                    location.href = data.url;
                }
            }, 2000 )
        },
        error: function (data) {
            console.log(data);
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
