let $materials=[];
let $locations=[];
let $materialsComplete=[];
let $locationsComplete=[];
let $items=[];
let $material;

$(document).ready(function () {
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

    $modalAddGroupItems = $('#modalAddGroupItems');

    $('#btn-saveItems').on('click', saveTableItems);

    $('#btn-saveGroupItems').on('click', saveTableItems);

    $(document).on('click', '[data-delete]', deleteItem);

    $formCreate = $("#formCreate");
    $formCreate.on('submit', storeOrderPurchase);

    $('#btn-currency').on('switchChange.bootstrapSwitch', function (event, state) {

        if (this.checked) // if changed state is "CHECKED"
        {
            console.log($(this));
            $('.moneda').html('USD');

        } else {
            console.log($(this));
            $('.moneda').html('PEN');
        }
    });

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

    $(document).on('typeahead:select', '#material_search', function(ev, suggestion) {
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
            $("#almacen").typeahead('val',$locations[0]).trigger('change');
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
                    $('#date_vence').prop('readonly', true);
                    $('#date_vence').prop('disabled', true);
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

let $modalAddItems;
let $modalAddGroupItems;

let $caracteres = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

let $longitud = 20;

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

    $('#subtotal').html(subtotal.toFixed(2));
    $('#taxes').html(taxes.toFixed(2));
    $('#total').html(total.toFixed(2));
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
            const material = $materialsComplete.find( material => material.material === material_name );
            const location = $locationsComplete.find( location => location.location === material_location );
            const code = rand_code($caracteres, $longitud);

            $items.push({ 'id': $items.length+1, 'price': parseFloat(parseFloat(material_price)/parseFloat(quantity)).toFixed(4), 'quantity':1 ,'material': material_name, 'id_material': material.id, 'item': code, 'location': location.location, 'id_location':location.id, 'state': state, 'state_description': state_description });
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
        $('#material_GroupSelected').val('');
        $('#quantity_GroupSelected').val('');
        $('#price_GroupSelected').val('');
        $('#locationGroup').val('');
        $('#locationGroup').typeahead('destroy');

        updateSummaryInvoice();
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
            const result = $materialsComplete.find( material => material.material === material_name );
            $items.push({
                'id': $items.length+1,
                'price': parseFloat(parseFloat(material_price)/parseFloat(material_quantity)).toFixed(4),
                'quantity':1,
                'material': material_name,
                //'id_material': result.id,
                'item': series_selected[i],
                'location': locations_selected[i].location,
                'id_location':locations_selected[i].id,
                'state': states_selected[i].state,
                'state_description': states_selected[i].description,
                'id_material': result.material_id,
                'stock_item_id': result.stock_item_id,
            });
            //renderTemplateMaterial($items.length, material_price, material_name, series_selected[i],  locations_selected[i].location, states_selected[i].description);
            $('.select2').select2();
        }

        const material = $materialsComplete.find( material => material.material === material_name );
        console.log(material);
        var subtotal2 =parseFloat((material_price)/1.18).toFixed(2);
        var taxes2 = parseFloat(subtotal2*0.18).toFixed(2);
        var total2 = parseFloat(material_price).toFixed(2);

        renderTemplateMaterial(material.id, material.code, material.material, material_quantity, material.unit, parseFloat(material_price/material_quantity).toFixed(2), subtotal2, taxes2, total2);

        $('#material_search').val('');
        $('#quantity').val('');
        $('#price').val('');
        $('#material_selected').val('');
        $('#quantity_selected').val('');
        $('#price_selected').val('');
        $('#body-items').html('');
        $('#locationGroup').val(' ');
        $('#locationGroup').typeahead('destroy');

        updateSummaryInvoice();

        $modalAddItems.modal('hide');
    }


}

function addItems() {
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
            updateSummaryInvoice();
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
    var materialId = $(this).data('delete');
    $items = $items.filter(material => material.id_material != materialId);
    //console.log($(this).parent().parent().parent());
    $(this).parent().parent().remove();
    updateSummaryInvoice();
}

function renderTemplateMaterial(id, code, description, quantity, unit, price, subtotal, taxes, total) {
    var clone = activateTemplate('#materials-selected');
    clone.querySelector("[data-code]").innerHTML = id;
    clone.querySelector("[data-description]").innerHTML = description;
    clone.querySelector("[data-quantity]").innerHTML = quantity;
    clone.querySelector("[data-unit]").innerHTML = unit;
    clone.querySelector("[data-price]").innerHTML = price;
    clone.querySelector("[data-subtotal]").innerHTML = subtotal;
    clone.querySelector("[data-taxes]").innerHTML = taxes;
    clone.querySelector("[data-total]").innerHTML = total;
    clone.querySelector("[data-delete]").setAttribute('data-delete', id);
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

function storeOrderPurchase() {
    event.preventDefault();
    // Obtener la URL
    $("#btn-submit").attr("disabled", true);
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
