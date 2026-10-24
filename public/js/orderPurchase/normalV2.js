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

    $('#sandbox-container1 .input-daterange').datepicker({
        todayBtn: "linked",
        clearBtn: true,
        language: "es",
        multidate: false,
        autoclose: true
    });

    getDataOrderPurchases(1);

    $("#btnBusquedaAvanzada").click(function(e){
        e.preventDefault();
        $(".busqueda-avanzada").slideToggle();
    });

    $(document).on('click', '[data-item]', showData);
    $("#btn-search").on('click', showDataSearch);

    $('body').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    $('#btn-export').on('click', exportExcel);

    $(document).on('click', '[data-delete]', cancelOrden);

    $modalState = $("#modalState");
    $formStates = $("#formStates");

    $(document).on('click', '[data-estado]', showModalState);

    $('#btn-changeState').on('click', saveState);

});

var $formStates;
var $modalState;

var $permissions;

function saveState() {
    var button = $(this);
    button.attr("disabled", true);
    var form = $formStates[0];
    $.confirm({
        icon: 'fas fa-toggle-off',
        theme: 'modern',
        closeIcon: false,
        animation: 'zoom',
        type: 'green',
        columnClass: 'medium',
        title: '¿Está seguro de guardar el estado de la orden?',
        content: 'Este cambio modificará el estado de la orden.',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                btnClass: 'btn-blue',
                action: function () {
                    $.ajax({
                        url: '/dashboard/change/state/order/purchase',
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        data: new FormData(form),
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            console.log(data);
                            $modalState.modal('hide');
                            $.alert(data.message);
                            setTimeout( function () {
                                button.attr("disabled", false);

                                getDataOrderPurchases(1);
                            }, 2000 )
                        },
                        error: function (data) {
                            button.attr("disabled", false);
                            $.alert("Sucedió un error en el servidor. Intente nuevamente.");
                        },
                    });
                }
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    button.attr("disabled", false);
                    $.alert("No se guardó ninguún dato.");
                },
            },
        }
    });

}

function showModalState() {
    $('#stateOrder').val('');
    $('#stateOrder').trigger('change');
    var orderPurchase_id = $(this).data('state');
    $.ajax({
        url: "/dashboard/get/state/order/purchase/"+orderPurchase_id,
        type: 'GET',
        dataType: 'json',
        success: function (data) {
            $formStates.find("[id=orderPurchase_id]").val(orderPurchase_id);

            $('#stateOrder').val(data.state);
            $('#stateOrder').trigger('change');

            $modalState.modal('show');
        }
    });
}

function cancelOrden() {
    var order_id = $(this).data('delete');
    var description = $(this).data('name');

    $.confirm({
        icon: 'fas fa-frown',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'red',
        title: '¿Está seguro de eliminar esta orden de compra?',
        content: description,
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                action: function (e) {
                    $.ajax({
                        url: '/dashboard/destroy/order/purchase/normal/'+order_id,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            console.log(data);
                            $.alert("Orden de compra normal anulada.");
                            setTimeout( function () {
                                getDataOrderPurchases(1);
                            }, 2000 )
                        },
                        error: function (data) {
                            $.alert("Sucedió un error en el servidor. Intente nuevamente.");
                        },
                    });
                },
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Anulación cancelada.");
                },
            },
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
            content: 'Si no hay fechas se descargará todos las órdenes de compra',
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

                        var url = "/dashboard/exportar/reporte/ordenes/compra/v2/?" + $.param(query);

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

        var url = "/dashboard/exportar/reporte/ordenes/compra/v2/?" + $.param(query);

        window.location = url;

    }

}

function showDataSearch() {
    getDataOrderPurchases(1)
}

function showData() {
    //event.preventDefault();
    var numberPage = $(this).attr('data-item');
    console.log(numberPage);
    getDataOrderPurchases(numberPage)
}

function getDataOrderPurchases($numberPage) {
    $('[data-toggle="tooltip"]').tooltip('dispose').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    var year = $('#year').val();
    var supplier = $('#supplier').val();
    var code = $('#code').val();
    var quote = $('#quote').val();
    var state = $('#state').val();
    var deliveryDate = $('#deliveryDate').val();
    var startDate = $('#start').val();
    var endDate = $('#end').val();

    $.get('/dashboard/get/data/orders/normal/v2/'+$numberPage, {
        year: year,
        supplier: supplier,
        code: code,
        quote: quote,
        state: state,
        deliveryDate: deliveryDate,
        startDate: startDate,
        endDate: endDate,
    }, function(data) {
        if ( data.data.length == 0 )
        {
            renderDataOrderPurchasesEmpty(data);
        } else {
            renderDataOrderPurchases(data);
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

function renderDataOrderPurchasesEmpty(data) {
    var dataAccounting = data.data;
    var pagination = data.pagination;
    console.log(dataAccounting);
    console.log(pagination);

    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' órdenes de compra');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    renderDataTableEmpty();
}

function renderDataOrderPurchases(data) {
    var dataFinanceWorks = data.data;
    var pagination = data.pagination;
    console.log(dataFinanceWorks);
    console.log(pagination);

    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' órdenes de compra.');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    for (let j = 0; j < dataFinanceWorks.length ; j++) {
        renderDataTable(dataFinanceWorks[j]);
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
    clone.querySelector("[data-date_order]").innerHTML = data.date_order;
    clone.querySelector("[data-date_arrival]").innerHTML = data.date_arrival;
    clone.querySelector("[data-observation]").innerHTML = data.observation;
    clone.querySelector("[data-supplier]").innerHTML = data.supplier;
    clone.querySelector("[data-created_user]").textContent = data.created_user;
    clone.querySelector("[data-currency]").innerHTML = data.currency;
    clone.querySelector("[data-total]").innerHTML = data.total;
    clone.querySelector("[data-type]").innerHTML = data.typeText;
    clone.querySelector("[data-state]").innerHTML = data.stateText;

    var botones = clone.querySelector("[data-buttons]");

    if ( data.type == "e" )
    {
        var cloneBtnExpress = activateTemplate('#template-express');

        if ( $.inArray('list_orderPurchaseNormal', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/imprimir/orden/compra/'+data.id;
            cloneBtnExpress.querySelector("[data-imprimir]").setAttribute("href", url);
        } else {
            let element = cloneBtnExpress.querySelector("[data-imprimir]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('list_orderPurchaseNormal', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/ver/orden/compra/express/'+data.id;
            cloneBtnExpress.querySelector("[data-ver_orden]").setAttribute("href", url);
        } else {
            let element = cloneBtnExpress.querySelector("[data-ver_orden]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('update_orderPurchaseExpress', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/editar/orden/compra/express/'+data.id;
            cloneBtnExpress.querySelector("[data-editar]").setAttribute("href", url);
        } else {
            let element = cloneBtnExpress.querySelector("[data-editar]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('destroy_orderPurchaseExpress', $permissions) !== -1 ) {
            cloneBtnExpress.querySelector("[data-anular]").setAttribute("data-delete", data.id);
            cloneBtnExpress.querySelector("[data-anular]").setAttribute("data-name", data.code);
        } else {
            let element = cloneBtnExpress.querySelector("[data-anular]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('update_orderPurchaseExpress', $permissions) !== -1 ) {
            cloneBtnExpress.querySelector("[data-estado]").setAttribute("data-state", data.id);
            cloneBtnExpress.querySelector("[data-estado]").setAttribute("data-name", data.code);
        } else {
            let element = cloneBtnExpress.querySelector("[data-estado]");
            if (element) {
                element.style.display = 'none';
            }
        }

        botones.append(cloneBtnExpress);
    }

    if ( data.type == "n" )
    {
        var cloneBtnNormal = activateTemplate('#template-normal');

        if ( $.inArray('list_orderPurchaseNormal', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/imprimir/orden/compra/'+data.id;
            cloneBtnNormal.querySelector("[data-imprimir]").setAttribute("href", url);
        } else {
            let element = cloneBtnNormal.querySelector("[data-imprimir]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('list_orderPurchaseNormal', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/ver/orden/compra/normal/'+data.id;
            cloneBtnNormal.querySelector("[data-ver_orden]").setAttribute("href", url);
        } else {
            let element = cloneBtnNormal.querySelector("[data-ver_orden]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('update_orderPurchaseNormal', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/editar/orden/compra/normal/'+data.id;
            cloneBtnNormal.querySelector("[data-editar]").setAttribute("href", url);
        } else {
            let element = cloneBtnNormal.querySelector("[data-editar]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('destroy_orderPurchaseNormal', $permissions) !== -1 ) {
            cloneBtnNormal.querySelector("[data-anular]").setAttribute("data-delete", data.id);
            cloneBtnNormal.querySelector("[data-anular]").setAttribute("data-name", data.code);
        } else {
            let element = cloneBtnNormal.querySelector("[data-anular]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('update_orderPurchaseNormal', $permissions) !== -1 ) {
            cloneBtnNormal.querySelector("[data-estado]").setAttribute("data-state", data.id);
            cloneBtnNormal.querySelector("[data-estado]").setAttribute("data-name", data.code);
        } else {
            let element = cloneBtnNormal.querySelector("[data-estado]");
            if (element) {
                element.style.display = 'none';
            }
        }

        botones.append(cloneBtnNormal);
    }

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