let $materials=[];
let $locations=[];
let $materialsComplete=[];
let $locationsComplete=[];
let $items=[];
$(document).ready(function () {

    fillItems();

    $('#btn-add').on('click', addItem);

    $('#btn-submit').on('click', updateOrderPurchase);

    $(document).on('click', '[data-delete]', deleteItem);

    $(document).on('click', '[data-edit]', editItem);


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
        if ( $(this).attr('data-total') != ''  )
        {
            var total_2 = parseFloat($(this).val());
            var price_2 = parseFloat($(this).parent().parent().prev().prev().children().children().val());
            var quantity_2 = parseFloat($(this).parent().parent().prev().prev().prev().children().children().val());
            var unit_2 = $(this).parent().parent().prev().prev().prev().prev().children().children().children().val();
            var service_2 = $(this).parent().parent().prev().prev().prev().prev().prev().children().children().children().val();
            var id2 = $(this).attr('data-total');

            $items = $items.filter(item => item.detail_id != id2);
            $items.push({'detail_id':$(this).attr('data-total'), 'price': price_2, 'quantity':quantity_2 ,'service': service_2, 'unit': unit_2, 'total': total_2 });

            $(this).parent().parent().next().children().children().removeClass( "btn-outline-success" );
            $(this).parent().parent().next().children().children().addClass( "btn-outline-warning" );

            updateSummaryInvoice();
        } else {
            var total = parseFloat($(this).val());
            var price = parseFloat($(this).parent().parent().prev().prev().children().children().val());
            var quantity = parseFloat($(this).parent().parent().prev().prev().prev().children().children().val());
            var unit = $(this).parent().parent().prev().prev().prev().prev().children().children().children().val();
            var service = $(this).parent().parent().prev().prev().prev().prev().prev().children().children().children().val();

            $items = $items.filter(item => item.service != service);
            $items.push({'price': price, 'quantity':quantity ,'service': service, 'unit': unit, 'total': total });
            updateSummaryInvoice();
        }
    });

    $(document).on('input', '[data-price2]', function() {
        if ( $(this).attr('data-price2') != ''  )
        {
            var price2_2 = parseFloat($(this).val());
            var price_2 = parseFloat($(this).parent().parent().prev().children().children().val());
            var quantity_2 = parseFloat($(this).parent().parent().prev().prev().children().children().val());
            var unit_2 = $(this).parent().parent().prev().prev().prev().children().children().children().val();
            var service_2 = $(this).parent().parent().prev().prev().prev().prev().children().children().children().val();
            var id2 = $(this).attr('data-price2');

            $items = $items.filter(item => item.detail_id != id2);
            $items.push({'detail_id':$(this).attr('data-price2'), 'price': price_2, 'quantity':quantity_2 ,'service': service_2, 'unit': unit_2, 'total': (price_2*quantity_2).toFixed(2) });

            $(this).parent().parent().next().next().children().children().removeClass( "btn-outline-success" );
            $(this).parent().parent().next().next().children().children().addClass( "btn-outline-warning" );

            updateSummaryInvoice();
        } else {
            var price2 = parseFloat($(this).val());
            var price = parseFloat($(this).parent().parent().prev().children().children().val());
            var quantity = parseFloat($(this).parent().parent().prev().prev().children().children().val());
            var unit = $(this).parent().parent().prev().prev().prev().children().children().children().val();
            var service = $(this).parent().parent().prev().prev().prev().prev().children().children().children().val();

            $items = $items.filter(item => item.service != service);
            $items.push({'price': price, 'quantity':quantity ,'service': service, 'unit': unit, 'total': (price*quantity).toFixed(2) });

            updateSummaryInvoice();
        }
    });

    $(document).on('input', '[data-price]', function() {
        if ( $(this).attr('data-price') !== ''  )
        {
            var price_2 = parseFloat($(this).val());
            var quantity_2 = parseFloat($(this).parent().parent().prev().children().children().val());
            var unit_2 = $(this).parent().parent().prev().prev().children().children().children().val();
            var service_2 = $(this).parent().parent().prev().prev().prev().children().children().children().val();
            var id2 = $(this).attr('data-price');

            $items = $items.filter(item => item.detail_id != id2);
            $items.push({'detail_id':$(this).attr('data-price'), 'price': price_2, 'quantity':quantity_2 ,'service': service_2, 'unit': unit_2, 'total':(price_2*quantity_2).toFixed(2) });

            $(this).parent().parent().next().next().next().children().children().removeClass( "btn-outline-success" );
            $(this).parent().parent().next().next().next().children().children().addClass( "btn-outline-warning" );

            updateSummaryInvoice();
        } else {
            var price = parseFloat($(this).val());
            var quantity = parseFloat($(this).parent().parent().prev().children().children().val());
            var unit = $(this).parent().parent().prev().prev().children().children().children().val();
            var service = $(this).parent().parent().prev().prev().prev().children().children().children().val();

            $items = $items.filter(item => item.service != service);
            $items.push({'price': price, 'quantity':quantity ,'service': service, 'unit': unit, 'total':(price*quantity).toFixed(2) });

            updateSummaryInvoice();
        }
    });

    $(document).on('input', '[data-quantity]', function() {
        if ( $(this).attr('data-quantity') !== ''  )
        {
            var quantity_2 = parseFloat($(this).val());
            var price_2 = parseFloat($(this).parent().parent().next().children().children().val());
            var unit_2 = $(this).parent().parent().prev().children().children().children().val();
            var service_2 = $(this).parent().parent().prev().prev().children().children().children().val();
            var id2 = $(this).attr('data-quantity');

            $items = $items.filter(item => item.detail_id != id2);
            $items.push({'detail_id':$(this).attr('data-quantity'), 'price': price_2, 'quantity':quantity_2 ,'service': service_2, 'unit': unit_2, 'total': (price_2*quantity_2).toFixed(2) });

            $(this).parent().parent().next().next().next().next().children().children().removeClass( "btn-outline-success" );
            $(this).parent().parent().next().next().next().next().children().children().addClass( "btn-outline-warning" );

            updateSummaryInvoice();
        } else {
            var quantity = parseFloat($(this).val());
            var price = parseFloat($(this).parent().parent().next().children().children().val());
            var unit = $(this).parent().parent().prev().children().children().children().val();
            var service = $(this).parent().parent().prev().prev().children().children().children().val();

            $items = $items.filter(item => item.service != service);
            $items.push({'price': price, 'quantity':quantity ,'service': service, 'unit': unit, 'total': (price*quantity).toFixed(2) });
            updateSummaryInvoice();
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

function fillItems() {
    $("#element_loader").LoadingOverlay("show", {
        background  : "rgba(236, 91, 23, 0.5)"
    });

    var arrayServices = [];
    $('[data-service]').each(function(e){
        arrayServices.push($(this).val());
    });
    var arrayUnits = [];
    $('[data-unit]').each(function(e){
        arrayUnits.push($(this).val());
    });
    var arrayQuantitys = [];
    $('[data-quantity]').each(function(e){
        arrayQuantitys.push($(this).val());
    });
    var arrayPrices = [];
    $('[data-price]').each(function(e){
        arrayPrices.push($(this).val());
    });

    var arrayOrders = [];
    $('[data-price]').each(function(e){
        arrayOrders.push($(this).attr('data-price'));
    });

    var arrayTotals = [];
    $('[data-total]').each(function(e){
        arrayTotals.push($(this).val());
    });

    for (let i = 0; i < arrayServices.length; i++) {
        $items.push({'detail_id': arrayOrders[i], 'price': arrayPrices[i], 'quantity':arrayQuantitys[i] ,'service': arrayServices[i], 'unit': arrayUnits[i], 'total': arrayTotals[i] });
    }

    $("#element_loader").LoadingOverlay("hide", true);
}

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

    $items.push({'detail_id':'', 'price': service_price, 'quantity':service_quantity ,'service': service, 'unit': service_unit, 'total':(service_price*service_quantity).toFixed(2) });

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

function editItem() {
    var detail_id = $(this).attr('data-edit');
    var total = parseFloat($(this).parent().parent().prev().children().children().val());
    var price = parseFloat($(this).parent().parent().prev().prev().prev().children().children().val());
    var quantity = parseFloat($(this).parent().parent().prev().prev().prev().prev().children().children().val());
    var unit = $(this).parent().parent().prev().prev().prev().prev().prev().children().children().children().val();
    var service = $(this).parent().parent().prev().prev().prev().prev().prev().prev().children().children().children().val();

    var modifiedItem = [];

    modifiedItem.push({'detail_id':detail_id, 'price': price, 'quantity':quantity ,'service': service, 'unit': unit, 'total':(total).toFixed(2) });
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
                        url: '/dashboard/update/detail/order/purchase/finance/'+detail_id,
                        method: 'POST',
                        data: { items: valParam },
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        success: function (data) {
                            console.log(data);
                            $(this).removeClass('btn-outline-warning');
                            $(this).addClass( "btn-outline-success" );
                            updateSummaryInvoice();

                            $.alert(data.message);
                            setTimeout( function () {
                                //location.reload();
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
    var idDetail = $(this).data('delete');
    console.log(idDetail);

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
                    action: function (e) {
                        $.ajax({
                            url: '/dashboard/destroy/detail/order/purchase/finance/'+idDetail,
                            method: 'POST',
                            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                            processData:false,
                            contentType:false,
                            success: function (data) {
                                console.log(data);

                                $items = $items.filter(item => item.detail_id != idDetail);
                                button.parent().parent().parent().remove();

                                updateSummaryInvoice();

                                $.alert(data.message);

                                setTimeout( function () {
                                    location.reload();
                                }, 1000 )

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


                            },
                        });

                    },
                },
                cancel: {
                    text: 'CANCELAR',
                    action: function (e) {
                        $.alert("Eliminación cancelada.");
                    },
                },
            },
        });
    } else {
        var service = button.parent().prev().prev().prev().prev().prev().prev().children().children().children().val();
        console.log(service);
        $items = $items.filter(item => item.service != service);
        $(this).parent().parent().remove();

        updateSummaryInvoice();
    }
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
   /* clone.querySelector("[data-delete]").setAttribute('data-delete', '');*/
    $('#body-services').append(clone);
}

function activateTemplate(id) {
    var t = document.querySelector(id);
    return document.importNode(t.content, true);
}

function updateOrderPurchase() {
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
                //location.reload();
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
