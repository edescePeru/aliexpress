$(document).ready(function () {
    $permissions = JSON.parse($('#permissions').val());
    //console.log($permissions);
    $('#sandbox-container .input-daterange').datepicker({
        todayBtn: "linked",
        clearBtn: true,
        language: "es",
        multidate: false,
        autoclose: true
    });

    getDataQuotes(1);

    $("#btnBusquedaAvanzada").click(function(e){
        e.preventDefault();
        $(".busqueda-avanzada").slideToggle();
    });

    $(document).on('click', '[data-item]', showData);
    $("#btn-search").on('click', showDataSearch);

    $('body').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    $formDelete = $('#formDelete');
    $formDelete.on('submit', destroySubCategory);
    $modalDelete = $('#modalDelete');
    $formDecimals = $('#formDecimals');
    $modalDecimals = $('#modalDecimals');
    $(document).on('click', '[data-delete]', cancelQuote);

    $(document).on('click', '[data-finish]', finishQuote);

    $(document).on('click', '[data-confirm]', confirmQuote);

    $(document).on('click', '[data-send]', sendQuote);




    $(document).on('click', '[data-decimals]', showModalDecimals);

    $('#btn-changeDecimals').on('click', saveDecimals);






    $(document).on('click', '[data-active_quote]', activeQuote);


    $(document).on('click', '[data-recotizar]', function () {
        const $button = $(this);

        const quoteId = parseInt(
            $button.attr('data-renew') || 0
        );

        const quoteName = $.trim(
            $button.attr('data-name') || ''
        );

        if (!quoteId) {
            $.alert({
                title: 'Aviso',
                content: 'No se pudo identificar la cotización.',
                type: 'orange',
                buttons: {
                    ok: {
                        text: 'Aceptar',
                        btnClass: 'btn-warning'
                    }
                }
            });

            return;
        }

        const description = quoteName !== ''
            ? '<br><br><strong>' +
            $('<div>').text(quoteName).html() +
            '</strong>'
            : '';

        $.confirm({
            icon: 'fas fa-sync',
            theme: 'modern',
            animation: 'zoom',
            closeAnimation: 'scale',
            closeIcon: true,
            type: 'green',

            title: 'Recotizar cotización',

            content:
                '¿Está seguro de generar una nueva cotización a partir de esta cotización?' +
                description +
                '<br><br>' +
                'Los precios y el stock serán validados nuevamente.',

            buttons: {
                confirm: {
                    text: 'RECOTIZAR',
                    btnClass: 'btn-success',

                    action: function () {
                        const confirmInstance = this;

                        /*
                         * Evitar múltiples clics.
                         */
                        $button.prop('disabled', true);

                        confirmInstance.buttons.confirm.disable();
                        confirmInstance.buttons.cancel.disable();

                        confirmInstance.setContent(
                            '<div class="text-center">' +
                            '<i class="fas fa-spinner fa-spin fa-2x"></i>' +
                            '<br><br>' +
                            'Recotizando, por favor espere...' +
                            '</div>'
                        );

                        $.ajax({
                            url:
                                document.location.origin +
                                '/dashboard/cotizaciones/venta/' +
                                quoteId +
                                '/recotizar',

                            method: 'POST',

                            headers: {
                                'X-CSRF-TOKEN': $(
                                    'meta[name="csrf-token"]'
                                ).attr('content')
                            },

                            success: function (response) {
                                confirmInstance.close();

                                $.alert({
                                    icon: 'fas fa-check-circle',
                                    theme: 'modern',
                                    animation: 'zoom',
                                    type: 'green',

                                    title: 'Cotización recotizada',

                                    content:
                                        response.message ||
                                        'La nueva cotización fue creada correctamente.',

                                    buttons: {
                                        ok: {
                                            text: 'Aceptar',
                                            btnClass: 'btn-success',

                                            action: function () {
                                                window.location.href =
                                                    response.url;
                                            }
                                        }
                                    }
                                });
                            },

                            error: function (xhr) {
                                $button.prop('disabled', false);

                                confirmInstance.close();

                                let message =
                                    'Sucedió un error al recotizar la cotización.';

                                if (
                                    xhr.responseJSON &&
                                    xhr.responseJSON.message
                                ) {
                                    message =
                                        xhr.responseJSON.message;
                                }

                                $.alert({
                                    icon: 'fas fa-exclamation-triangle',
                                    theme: 'modern',
                                    animation: 'zoom',
                                    type: 'orange',

                                    title: 'No se pudo recotizar',

                                    content: $('<div>')
                                        .text(message)
                                        .html(),

                                    buttons: {
                                        ok: {
                                            text: 'Aceptar',
                                            btnClass: 'btn-warning'
                                        }
                                    }
                                });
                            }
                        });

                        /*
                         * Mantiene abierto jquery-confirm mientras
                         * finaliza el AJAX.
                         */
                        return false;
                    }
                },

                cancel: {
                    text: 'CANCELAR'
                }
            }
        });
    });
});

var $formDelete;
var $modalDelete;
var $modalDecimals;
var $formDecimals;

var $permissions;

function showLoader(msg = "Procesando solicitud...") {
    return $.dialog({
        title: false,
        closeIcon: false,
        backgroundDismiss: false,
        theme: 'modern',
        content:
            '<div class="text-center" style="padding:20px;">' +
            '<div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>' +
            '<p class="mt-3">'+ msg +'</p>' +
            '</div>',
    });
}

function cancelQuote() {
    const $button = $(this);
    if ($button.prop('disabled') || $button.data('quoteCancelPending')) return;
    const id = String($button.attr('data-delete') || '');
    if (!/^[1-9][0-9]*$/.test(id)) return;
    const description = $('<div>').text($button.attr('data-name') || '').html();
    $button.data('quoteCancelPending', true);
    const original = $button.html();
    $.confirm({
        icon: 'fas fa-ban', theme: 'modern', animation: 'zoom', type: 'red',
        closeIcon: false, escapeKey: false, backgroundDismiss: false,
        title: 'Anular cotización',
        content: '¿Desea anular esta cotización y liberar sus reservas?' + (description ? '<br><br>' + description : ''),
        onClose: function () { $button.removeData('quoteCancelPending'); },
        buttons: {
            confirm: {
                text: 'Anular', btnClass: 'btn-danger',
                action: function () {
                    const dialog = this;
                    if ($button.prop('disabled')) return false;
                    $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Anulando');
                    dialog.buttons.confirm.disable(); dialog.buttons.cancel.disable();
                    dialog.buttons.confirm.setText('<i class="fas fa-spinner fa-spin"></i> Anulando');
                    $.ajax({
                        url: '/dashboard/destroy/quote/sale/' + id,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        data: quoteSaleSnapshot(),
                        success: function (response) {
                            dialog.close();
                            if (!response.ok) {
                                $.alert({title: 'No se pudo anular', type: 'red', content: $('<div>').text(response.message || 'Revise la cotización.').html()});
                                return;
                            }
                            $.alert({title: 'Cotización anulada', type: 'green', content: $('<div>').text(response.message).html(),
                                buttons: {ok: {text: 'Aceptar', action: function () { getDataQuotes(1); }}}});
                        },
                        error: function (xhr) { dialog.close(); $.alert({title: 'No se pudo anular', type: 'red', content: quoteSaleError(xhr)}); },
                        complete: function () { $button.prop('disabled', false).html(original).removeData('quoteCancelPending'); }
                    });
                    return false;
                }
            },
            cancel: {text: 'Cancelar'}
        }
    });
}

function destroySubCategory() {
    event.preventDefault();
    // Obtener la URL
    var deleteUrl = $formDelete.data('url');
    $.ajax({
        url: deleteUrl,
        method: 'POST',
        data: new FormData(this),
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
            $modalDelete.modal('hide');
            setTimeout( function () {
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


        },
    });
}

function exportExcel() {
    var start  = $('#start').val();
    var end  = $('#end').val();
    var startDate   = moment(start, "DD/MM/YYYY");
    var endDate     = moment(end, "DD/MM/YYYY");

    console.log(start);
    console.log(end);
    console.log(startDate);
    console.log(endDate);

    if ( start == '' || end == '' )
    {
        console.log('Sin fechas');
        $.confirm({
            icon: 'fas fa-file-excel',
            theme: 'modern',
            closeIcon: true,
            animation: 'zoom',
            type: 'green',
            title: 'No especificó fechas',
            content: 'Si no hay fechas se descargará todos los ingresos',
            buttons: {
                confirm: {
                    text: 'DESCARGAR',
                    action: function (e) {
                        //$.alert('Descargado igual');
                        console.log(start);
                        console.log(end);

                        var query = {
                            start: start,
                            end: end
                        };

                        $.alert('Descargando archivo ...');

                        var url = "/dashboard/exportar/reporte/egresos/proveedores/?" + $.param(query);

                        window.location = url;

                    },
                },
                cancel: {
                    text: 'CANCELAR',
                    action: function (e) {
                        $.alert("Exportación cancelada.");
                    },
                },
            },
        });
    } else {
        console.log('Con fechas');
        console.log(JSON.stringify(start));
        console.log(JSON.stringify(end));

        var query = {
            start: start,
            end: end
        };

        toastr.success('Descargando archivo ...', 'Éxito',
            {
                "closeButton": true,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "2000",
                "timeOut": "2000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            });

        var url = "/dashboard/exportar/reporte/egresos/proveedores/?" + $.param(query);

        window.location = url;

    }

}

function showDataSearch() {
    getDataQuotes(1)
}

function showData() {
    //event.preventDefault();
    var numberPage = $(this).attr('data-item');
    console.log(numberPage);
    getDataQuotes(numberPage)
}

function getDataQuotes($numberPage) {
    $('[data-toggle="tooltip"]').tooltip('dispose').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    var description_quote = $('#description_quote').val();
    var year = $('#year').val();
    var code = $('#code').val();
    var order = $('#order').val();
    var customer = $('#customer').val();
    var creator = $('#creator').val();
    var stateQuote = $('#stateQuote').val();
    var startDate = $('#start').val();
    var endDate = $('#end').val();

    $.get('/dashboard/get/data/quotes/sale/index/v2/'+$numberPage, {
        description_quote:description_quote,
        year: year,
        code: code,
        order: order,
        customer: customer,
        creator: creator,
        stateQuote: stateQuote,
        startDate: startDate,
        endDate: endDate,
    }, function(data) {
        if ( data.data.length == 0 )
        {
            renderDataQuotesEmpty(data);
        } else {
            renderDataQuotes(data);
        }


    }).fail(function(jqXHR, textStatus, errorThrown) {
        // Función de error, se ejecuta cuando la solicitud GET falla
        console.error(textStatus, errorThrown);
        if (jqXHR.responseJSON.message && !jqXHR.responseJSON.errors) {
            toastr.error(jqXHR.responseJSON.message, 'Error', {
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
        for (var property in jqXHR.responseJSON.errors) {
            toastr.error(jqXHR.responseJSON.errors[property], 'Error', {
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
    }, 'json')
        .done(function() {
            // Configuración de encabezados
            var headers = {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            };

            $.ajaxSetup({
                headers: headers
            });
        });
}

function renderDataQuotesEmpty(data) {
    var dataAccounting = data.data;
    var pagination = data.pagination;
    console.log(dataAccounting);
    console.log(pagination);

    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' cotizaciones');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    renderDataTableEmpty();
}

function renderDataQuotes(data) {
    var dataQuotes = data.data;
    var pagination = data.pagination;
    console.log(dataQuotes);
    console.log(pagination);

    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' cotizaciones.');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    for (let j = 0; j < dataQuotes.length ; j++) {
        renderDataTable(dataQuotes[j]);
    }

    if (pagination.currentPage > 1)
    {
        renderPreviousPage(pagination.currentPage-1);
    }

    if (pagination.totalPages > 1)
    {
        if (pagination.currentPage > 3)
        {
            renderItemPage(1);

            if (pagination.currentPage > 4) {
                renderDisabledPage();
            }
        }

        for (var i = Math.max(1, pagination.currentPage - 2); i <= Math.min(pagination.totalPages, pagination.currentPage + 2); i++)
        {
            renderItemPage(i, pagination.currentPage);
        }

        if (pagination.currentPage < pagination.totalPages - 2)
        {
            if (pagination.currentPage < pagination.totalPages - 3)
            {
                renderDisabledPage();
            }
            renderItemPage(i, pagination.currentPage);
        }

    }

    if (pagination.currentPage < pagination.totalPages)
    {
        renderNextPage(pagination.currentPage+1);
    }
}

function renderDataTableEmpty() {
    var clone = activateTemplate('#item-table-empty');
    $("#body-table").append(clone);
}

function renderDataTable(data) {
    var clone = activateTemplate('#item-table');
    clone.querySelector("[data-id]").innerHTML = data.id;
    clone.querySelector("[data-code]").innerHTML = data.code;
    clone.querySelector("[data-description]").innerHTML = data.description;
    clone.querySelector("[data-date_quote]").innerHTML = data.date_quote;
    //clone.querySelector("[data-date_validate]").innerHTML = data.date_validate;
    clone.querySelector("[data-deadline]").innerHTML = data.deadline;
    clone.querySelector("[data-time_delivery]").innerHTML = data.time_delivery;
    clone.querySelector("[data-customer]").innerHTML = data.customer;
    clone.querySelector("[data-order]").innerHTML = data.order;
    clone.querySelector("[data-total_sunat]").innerHTML = data.total_sunat;
    clone.querySelector("[data-total]").innerHTML = data.total_cliente;
    clone.querySelector("[data-services_add]").innerHTML = data.total_services;
    clone.querySelector("[data-currency]").innerHTML = data.currency;
    clone.querySelector("[data-state]").innerHTML = data.stateText;
    //clone.querySelector("[data-created_at]").innerHTML = data.created_at;
    clone.querySelector("[data-creator]").innerHTML = data.creator;
   /* clone.querySelector("[data-decimals]").innerHTML = data.decimals;*/

    quoteSaleRenderActions(clone.querySelector("[data-buttons]"), data);

    $("#body-table").append(clone);

    $('[data-toggle="tooltip"]').tooltip();
}

function renderPreviousPage($numberPage) {
    var clone = activateTemplate('#previous-page');
    clone.querySelector("[data-item]").setAttribute('data-item', $numberPage);
    $("#pagination").append(clone);
}

function renderDisabledPage() {
    var clone = activateTemplate('#disabled-page');
    $("#pagination").append(clone);
}

function renderItemPage($numberPage, $currentPage) {
    var clone = activateTemplate('#item-page');
    if ( $numberPage == $currentPage )
    {
        clone.querySelector("[data-item]").setAttribute('data-item', $numberPage);
        clone.querySelector("[data-active]").setAttribute('class', 'page-item active');
        clone.querySelector("[data-item]").innerHTML = $numberPage;
    } else {
        clone.querySelector("[data-item]").setAttribute('data-item', $numberPage);
        clone.querySelector("[data-item]").innerHTML = $numberPage;
    }

    $("#pagination").append(clone);
}

function renderNextPage($numberPage) {
    var clone = activateTemplate('#next-page');
    clone.querySelector("[data-item]").setAttribute('data-item', $numberPage);
    $("#pagination").append(clone);
}

function submitFormEditFacturacion(event) {
    event.preventDefault();
    var button = $(this);

    button.prop('disabled', true);
    var createUrl = $formEditFacturacion.data('url');
    $.ajax({
        url: createUrl, // La URL a la que enviarás la solicitud
        method: 'POST', // El método HTTP que utilizarás (en este caso, POST)
        data: new FormData($('#formEditFacturacion')[0]),
        processData:false,
        contentType:false,
        success: function(data) {
            // Esta función se ejecutará si la solicitud fue exitosa
            // La variable 'response' contendrá los datos devueltos por el servidor (según el tipo de datos especificado en 'dataType')
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
            $modalEditFacturacion.modal('hide');
            setTimeout( function () {
                button.attr("disabled", false);
                showDataSearch();
                //location.reload();
            }, 100 )
        },
        error: function(data) {
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
            button.attr("disabled", false);
            // Esta función se ejecutará si ocurre un error en la solicitud
            // Puedes utilizar las variables 'jqXHR', 'textStatus' y 'errorThrown' para obtener información sobre el error
        }
    });
}

function showModalEditFacturacion() {
    var invoice_id = $(this).attr('data-formEditFacturacion');
    var type = $(this).attr('data-type');

    $formEditFacturacion.find("[id=invoice_id]").val(invoice_id);
    $formEditFacturacion.find("[id=type]").val(type);

    if ( invoice_id == "" )
    {
        invoice_id = "nn";
    }

    $.get("/dashboard/get/info/facturacion/expense/supplier/"+invoice_id+"/"+type, function (data) {
        console.log(data);

        //$formEditFacturacion.find("[id=invoice_id]").val(invoice_id);

        $('#state').val(data.state);
        $('#state').trigger('change');

        $modalEditFacturacion.modal('show');
    }, "json");

}

function activateTemplate(id) {
    var t = document.querySelector(id);
    return document.importNode(t.content, true);
}
function quoteSaleSnapshot() {
    const context = document.getElementById('quote-sale-action-context');
    return {
        form_tenant_id: context.getAttribute('data-tenant'),
        form_company_id: context.getAttribute('data-company'),
        form_branch_id: context.getAttribute('data-branch')
    };
}

function quoteSaleError(xhr) {
    const body = xhr.responseJSON || {};
    const errors = body.errors ? Object.values(body.errors).reduce(function (all, list) { return all.concat(list); }, []) : [];
    return $('<div>').text(errors[0] || body.message || 'No se pudo completar la operación.').html();
}

function quoteSalePost(button, action, payload, modal) {
    const $button = $(button);
    if ($button.prop('disabled')) return;
    const original = $button.html();
    $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando');
    $.ajax({
        url: '/dashboard/quote-sale/' + action,
        method: 'POST',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        data: Object.assign({}, quoteSaleSnapshot(), payload || {}),
        success: function (response) {
            if (modal) $(modal).modal('hide');
            $.alert({title: 'Guardado correctamente', type: 'green', content: $('<div>').text(response.message).html(), buttons: {ok: {text: 'Aceptar', action: function () { getDataQuotes(1); }}}});
        },
        error: function (xhr) { $.alert({title: 'No se pudo guardar', type: 'red', content: quoteSaleError(xhr)}); },
        complete: function () { $button.prop('disabled', false).html(original); }
    });
}

function quoteSaleConfirmAction(button, id, action, title) {
    $.confirm({title: title, content: '¿Desea continuar con esta cotización?', buttons: {
        confirmar: {text: 'Confirmar', btnClass: 'btn-primary', action: function () { quoteSalePost(button, id + '/' + action); }},
        cancelar: {text: 'Cancelar'}
    }});
}
function confirmQuote() { quoteSaleConfirmAction(this, $(this).attr('data-confirm'), 'confirm', 'Confirmar cotización'); }
function sendQuote() { quoteSaleConfirmAction(this, $(this).attr('data-send'), 'send', 'Marcar como enviada'); }
function finishQuote() { quoteSaleConfirmAction(this, $(this).attr('data-finish'), 'close', 'Cerrar cotización'); }
function activeQuote() { quoteSaleConfirmAction(this, $(this).attr('data-active_quote'), 'reopen', 'Reabrir cotización'); }

function quoteSaleOpenOption(button, field, modal, form, input) {
    const id = $(button).attr('data-decimals');
    const $form = $(form); const $save = $(modal).find('button[id^="btn-change"]');
    if ($save.prop('disabled')) return;
    $form.find('[name="quote_id"]').val('');
    $save.prop('disabled', true);
    $.ajax({url: '/dashboard/quote-sale/' + id + '/decimals', method: 'GET',
        success: function (data) { $form.find('[name="quote_id"]').val(id); $(input).val(String(data[field])).trigger('change'); $(modal).modal('show'); },
        error: function (xhr) { $.alert({title: 'No se pudo consultar', type: 'red', content: quoteSaleError(xhr)}); },
        complete: function () { $save.prop('disabled', false); }
    });
}
function showModalDecimals() { quoteSaleOpenOption(this, 'decimals', '#modalDecimals', '#formDecimals', '#decimals'); }
function saveDecimals() { quoteSalePost(this, 'decimals', {quote_id: $('#formDecimals [name="quote_id"]').val(), decimals: $('#decimals').val()}, '#modalDecimals'); }

function quoteSaleRenderActions(container, data) {
    const can = function (permission) { return $.inArray(permission, $permissions) !== -1; };
    const add = function (permission, title, icon, attribute, color, url) {
        if (!can(permission)) return;
        const element = document.createElement(url ? 'a' : 'button');
        element.className = 'btn btn-outline-' + color + ' btn-sm';
        element.setAttribute('title', title); element.setAttribute('data-toggle', 'tooltip');
        if (url) { element.setAttribute('href', url); if (url.indexOf('/imprimir/') !== -1) { element.setAttribute('target', '_blank'); element.setAttribute('rel', 'noopener'); } }
        else { element.setAttribute('type', 'button'); element.setAttribute(attribute, data.id); element.setAttribute('data-name', data.description || ''); if (attribute === 'data-recotizar') element.setAttribute('data-renew', data.id); }
        const symbol = document.createElement('i'); symbol.className = icon; element.appendChild(symbol); container.appendChild(element);
    };
    add('list_quoteSale', 'Ver detalles', 'fa fa-eye', null, 'primary', '/dashboard/ver/cotizacion/venta/' + data.id);
    add('printCustomer_quote', 'Imprimir para cliente', 'fa fa-print', null, 'info', '/dashboard/imprimir/cotizacion/cliente/' + data.id);
    if (['canceled', 'requote'].indexOf(data.state) !== -1) {
        if (data.state === 'requote') add('renew_quoteSale', 'Recotizar', 'fas fa-sync', 'data-recotizar', 'success');
        return;
    }
    if (data.state === 'close') {
        add('finish_quoteSale', 'Reabrir cotización', 'fas fa-lock-open', 'data-active_quote', 'warning');
    } else {
        if (!data.has_active_sale) {
            add('edit_quoteSale', 'Editar cotización', 'fas fa-pencil-alt', null, 'warning', '/dashboard/editar/cotizacion/venta/' + data.id);
            add('destroy_quoteSale', 'Anular cotización', 'fa fa-trash', 'data-delete', 'danger');
            if (['created', 'send'].indexOf(data.state) !== -1) add('confirm_quoteSale', 'Confirmar cotización', 'fas fa-check', 'data-confirm', 'success');
        }
        if (!data.send_state) add('send_quoteSale', 'Marcar como enviada', 'fas fa-paper-plane', 'data-send', 'info');
        add('finish_quoteSale', 'Cerrar cotización', 'fas fa-lock', 'data-finish', 'secondary');
    }
    add('edit_quoteSale', 'Visualización de decimales', 'fas fa-toggle-on', 'data-decimals', 'info');
}
