let $materials=[];
let $locations=[];
let $materialsComplete=[];
let $locationsComplete=[];
let $items=[];
$(document).ready(function () {
    /*$.ajax({
        url: "/dashboard/get/materials",
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
    });*/

    // ==============================
    // Seleccionar caja EFECTIVO por defecto
    // ==============================
    function pvSelectDefaultCashBox() {
        const $cash = $('#pv_cash_box_id');

        // Buscar option con data-type="cash"
        const $cashOption = $cash.find('option').filter(function () {
            return ($(this).data('type') || '') === 'cash';
        }).first();

        if ($cashOption.length) {
            $cash.val($cashOption.val()).trigger('change');
        }
    }

    // ==============================
    // Cascada caja -> subtipo
    // ==============================
    function pvFillSubtypes(selectId, subtypes) {
        const $sel = $(selectId);
        $sel.empty().append('<option value="">Seleccione subtipo...</option>');

        (subtypes || []).forEach(s => {
            $sel.append(new Option(s.name, s.id, false, false));
        });

        if ($sel.hasClass('select2-hidden-accessible')) {
            $sel.trigger('change.select2');
        }
    }

    function pvToggleSubtypesByCashBox() {
        const $cash = $('#pv_cash_box_id');
        const $sub  = $('#pv_cash_box_subtype_id');
        const $wrap = $('#pv_cash_box_subtype_wrap');

        const $opt = $cash.find('option:selected');
        const type = ($opt.data('type') || $opt.attr('data-type') || '').toString();
        const uses = String($opt.data('uses_subtypes') || $opt.attr('data-uses_subtypes') || '0') === '1';

        if (type === 'bank' && uses) {
            if ($wrap.length) $wrap.show();
            pvFillSubtypes('#pv_cash_box_subtype_id', window.PV_SUBTYPES || []);
        } else {
            if ($wrap.length) $wrap.hide();
            pvFillSubtypes('#pv_cash_box_subtype_id', []);
            $sub.val('').trigger('change');
        }
    }

    // OJO: aquí NO rellenamos cajas por JS porque el Blade ya las imprime.
    pvToggleSubtypesByCashBox();

    pvSelectDefaultCashBox();          // 👈 AQUÍ

    $('#pv_cash_box_id').on('change', function () {
        pvToggleSubtypesByCashBox();
    });

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
    $('#btn-submit').on('click', storeInvoice);
    //$formCreate.on('submit', storeInvoice);

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

function mayus(e) {
    e.value = e.value.toUpperCase();
}

function saveTableItems() {

    var series_selected = [];
    var locations_selected = [];
    var states_selected = [];

    if ($('[name="my-checkbox"]').is(':checked')) {
        let quantity = $('#quantity_GroupSelected').val();
        let material_name = $('#material_GroupSelected').val();
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
            console.log(material);
            $items.push({ 'id': $items.length+1, 'price': material_price, 'material': material_name, 'id_material': material.id, 'item': code, 'location': location.location, 'id_location':location.id, 'state': state, 'state_description': state_description });
            //renderTemplateMaterial($items.length, material_price, material_name, code,  location.location, state_description);
        }
        const material = $materialsComplete.find( material => material.material === material_name );
        console.log(material);
        var subtotal =((quantity*material_price)/1.18).toFixed(2);
        var taxes = (subtotal*0.18).toFixed(2);
        var total = (quantity*material_price).toFixed(2);

        renderTemplateMaterial(material.id, material.code, material.material, quantity, material.unit, material_price, subtotal, taxes, total);

        $('#material_search').val('');
        $("#material_unit").val('').trigger('change');
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
        let material_quantity = $('#quantity_selected').val();
        let material_price = parseFloat($('#price_selected').val()).toFixed(2);

        for ( var i=0; i<series_selected.length; i++ )
        {
            const result = $materialsComplete.find( material => material.material === material_name );
            $items.push({ 'id': $items.length+1, 'price': material_price, 'material': material_name, 'id_material': result.id, 'item': series_selected[i], 'location': locations_selected[i].location, 'id_location':locations_selected[i].id, 'state': states_selected[i].state, 'state_description': states_selected[i].description });
            //console.log(result);
            //renderTemplateMaterial($items.length, material_price, material_name, series_selected[i],  locations_selected[i].location, states_selected[i].description);
            $('.select2').select2();
        }

        const material = $materialsComplete.find( material => material.material === material_name );
        console.log(material);
        var subtotal2 =((material_quantity*material_price)/1.18).toFixed(2);
        var taxes2 = (subtotal2*0.18).toFixed(2);
        var total2 = (material_quantity*material_price).toFixed(2);

        renderTemplateMaterial(material.id, material.code, material.material, material_quantity, material.unit, material_price, subtotal2, taxes2, total2);

        $('#material_search').val('');
        $('#quantity').val('');
        $('#price').val('');
        $("#material_unit").val('').trigger('change');
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

    if ( $('#material_unit').val() == '' )
    {
        toastr.error('Debe elegir una unidad', 'Error',
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

    let material_name = $('#material_search').val();
    let material_unit = $( "#material_unit option:selected" ).text();
    let material_quantity = parseFloat($('#quantity').val()).toFixed(2);
    // TODO: Este precio ahora es total
    let material_price = parseFloat($('#price').val()).toFixed(2);

    var subtotal = parseFloat(material_price/1.18).toFixed(2);
    var taxes = parseFloat(subtotal*0.18).toFixed(2);
    var total = parseFloat(material_price).toFixed(2);

    $items.push({ 'id': $items.length+1, 'price': parseFloat(parseFloat(material_price)/parseFloat(material_quantity)).toFixed(4), 'material': material_name, 'quantity': material_quantity, 'unit': material_unit, 'subtotal': subtotal, 'taxes': taxes, 'total':total});

    renderTemplateMaterial($items.length, material_name, material_quantity, material_unit, parseFloat(material_price/material_quantity).toFixed(2), subtotal, taxes, total);

    $('#material_search').val('');
    $('#quantity').val('');
    $("#material_unit").val('').trigger('change');
    $('#price').val('');

    updateSummaryInvoice();
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
    $items = $items.filter(material => material.id !== materialId);
    //console.log($(this).parent().parent().parent());
    $(this).parent().parent().remove();
    updateSummaryInvoice();
}

function renderTemplateMaterial(id, description, quantity, unit, price, subtotal, taxes, total) {
    var clone = activateTemplate('#materials-selected');
    clone.querySelector("[data-id]").innerHTML = id;
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

function storeInvoice() {
    event.preventDefault();
    // Obtener la URL
    $("#btn-submit").attr("disabled", true);
    if( $items.length == 0 )
    {
        toastr.error('No se puede crear una factura sin detalles.', 'Error',
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
        $("#btn-submit").attr("disabled", false);
        return;
    }
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
