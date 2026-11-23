let $materials=[];
let $locations=[];
let $materialsComplete=[];
let $locationsComplete=[];
let $items=[];
$(document).ready(function () {

    $('#btn-add').on('click', addItem);

    $('#btn-submit').on('click', storeOrderPurchase);

    $(document).on('click', '[data-delete]', deleteItem);

    $formCreate = $("#formCreate");

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
        var unit = $(this).parent().parent().prev().prev().prev().prev().children().children().children().val();
        var service = $(this).parent().parent().prev().prev().prev().prev().prev().children().children().children().val();

        $items = $items.filter(item => item.service != service);
        $items.push({'price': price, 'quantity':quantity ,'service': service, 'unit': unit, 'total': total });
        updateSummaryInvoice();
    });

    $(document).on('input', '[data-price2]', function() {
        var price2 = parseFloat($(this).val());
        var price = parseFloat($(this).parent().parent().prev().children().children().val());
        var quantity = parseFloat($(this).parent().parent().prev().prev().children().children().val());
        var unit = $(this).parent().parent().prev().prev().prev().children().children().children().val();
        var service = $(this).parent().parent().prev().prev().prev().prev().children().children().children().val();

        $items = $items.filter(item => item.service != service);
        $items.push({'price': price, 'quantity':quantity ,'service': service, 'unit': unit, 'total': (price*quantity).toFixed(2) });
        updateSummaryInvoice();

    });

    $(document).on('input', '[data-price]', function() {
        var price = parseFloat($(this).val());
        var quantity = parseFloat($(this).parent().parent().prev().children().children().val());
        var unit = $(this).parent().parent().prev().prev().children().children().children().val();
        var service = $(this).parent().parent().prev().prev().prev().children().children().children().val();

        $items = $items.filter(item => item.service != service);
        $items.push({'price': price, 'quantity':quantity ,'service': service, 'unit': unit, 'total':(price*quantity).toFixed(2) });

        updateSummaryInvoice();

    });

    $(document).on('input', '[data-quantity]', function() {
        var quantity = parseFloat($(this).val());
        var price = parseFloat($(this).parent().parent().next().children().children().val());
        var unit = $(this).parent().parent().prev().children().children().children().val();
        var service = $(this).parent().parent().prev().prev().children().children().children().val();

        $items = $items.filter(item => item.service != service);
        $items.push({'price': price, 'quantity':quantity ,'service': service, 'unit': unit, 'total': (price*quantity).toFixed(2) });
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

function addItem() {

    if( $('#service').val().trim() === '' )
    {
        toastr.error('Ingrese la descripción del servicio', 'Error',
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
        toastr.error('Debe ingresar un precio', 'Error',
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

    let service = $('#service').val();
    let service_unit = $('#unit').select2('data')[0].text;
    let service_quantity = $('#quantity').val();
    let service_price = $('#price').val();

    $items.push({'price': service_price, 'quantity':service_quantity ,'service': service, 'unit': service_unit, 'total':(service_price*service_quantity).toFixed(2) });

    $('#service').val('');
    $('#unit').val(null).trigger('change');
    $('#quantity').val('');
    $('#price').val('');

    renderTemplateService(service, service_unit, service_quantity, service_price);

    updateSummaryInvoice();

}

function mayus(e) {
    e.value = e.value.toUpperCase();
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

function updateSummaryInvoice2() {
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
    var service = $(this).data('delete');
    console.log(service);
    $items = $items.filter(item => item.service != service);
    $(this).parent().parent().remove();

    updateSummaryInvoice();
}

function renderTemplateService(service, service_unit, service_quantity, service_price) {
    var clone = activateTemplate('#service-selected');

    clone.querySelector("[data-service]").setAttribute('value', service);
    clone.querySelector("[data-unit]").setAttribute('value', service_unit);
    clone.querySelector("[data-quantity]").setAttribute('value', (parseFloat(service_quantity)).toFixed(2) );
    clone.querySelector("[data-quantity]").setAttribute('max', service_quantity);
    clone.querySelector("[data-price]").setAttribute('value', (parseFloat(service_price)).toFixed(2) );
    clone.querySelector("[data-price2]").setAttribute('value', (parseFloat(service_price)/1.18).toFixed(2) );
    clone.querySelector("[data-total]").setAttribute('value', (parseFloat(service_price)*parseFloat(service_quantity)).toFixed(2) );
    clone.querySelector("[data-delete]").setAttribute('data-delete', service);
    $('#body-services').append(clone);
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

    var currency = $('#btn-currency').bootstrapSwitch('state');
    var regularize = $('#btn-regularize').bootstrapSwitch('state');

    if ( $items.length === 0 )
    {
        toastr.error('Ingrese detalles a guardar', 'Error',
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
    form.append('subtotal_send', subtotal_send);
    form.append('taxes_send', taxes_send);
    form.append('total_send', total_send);
    form.append('currency', currency);
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
                location.reload();
            }, 2000 )
        },
        error: function (data) {
            data.responseJSON = data.responseJSON || {message: 'No se pudo completar la solicitud (HTTP ' + data.status + ').'};
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
