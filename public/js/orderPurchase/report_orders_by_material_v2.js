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

    $(document).on('click', '[data-item]', showData);

    $("#btn-search").on('click', showDataSearch);

    $('body').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    $('#btn-export').on('click', exportExcel);

});

var $permissions;

function exportExcel() {
    var start  = $('#start').val();
    var end  = $('#end').val();
    var startDate   = moment(start, "DD/MM/YYYY");
    var endDate     = moment(end, "DD/MM/YYYY");
    var material = $('#material').val();
    var year = $('#year').val();
    console.log(start);
    console.log(end);
    console.log(startDate);
    console.log(endDate);

    if (material != "")
    {
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
                content: 'Si no hay fechas se descargarán todas las órdenes',
                buttons: {
                    confirm: {
                        text: 'DESCARGAR',
                        action: function (e) {
                            //$.alert('Descargado igual');
                            console.log(start);
                            console.log(end);

                            var query = {
                                start: start,
                                end: end,
                                material: material,
                                year: year
                            };

                            $.alert('Descargando archivo ...');

                            var url = "/dashboard/exportar/reporte/ordenes/by/material/v2/?" + $.param(query);

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
                end: end,
                material: material,
                year: year
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

            var url = "/dashboard/exportar/reporte/ordenes/by/material/v2/?" + $.param(query);

            window.location = url;

        }
    } else
    {
        toastr.error("Seleccione un material para poder hacer la búsqueda", 'Error', {
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

function showDataSearch() {
    getDataOrdersRequest(1)
}

function showData() {
    //event.preventDefault();
    var numberPage = $(this).attr('data-item');
    console.log(numberPage);
    getDataOrdersRequest(numberPage)
}

function getDataOrdersRequest($numberPage) {
    $('[data-toggle="tooltip"]').tooltip('dispose').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    var year = $('#year').val();
    var material = $('#material').val();
    var startDate = $('#start').val();
    var endDate = $('#end').val();

    if ( material != "" )
    {
        $('#body-table').html('<tr><td colspan="12" class="text-center text-muted" role="status">Cargando órdenes...</td></tr>');
        $.get('/dashboard/get/data/order/purchase/by/material/'+$numberPage, {
            year: year,
            material: material,
            startDate: startDate,
            endDate: endDate,
        }, function(data) {
            if ( data.data.length == 0 )
            {
                renderDataOrdersRequestEmpty(data);
            } else {
                renderDataOrdersRequest(data);
            }

        }).fail(function(jqXHR, textStatus, errorThrown) {
            // Función de error, se ejecuta cuando la solicitud GET falla
            console.error(textStatus, errorThrown);
            var response = jqXHR.responseJSON || {};
            $('#body-table').html('<tr><td colspan="12" class="text-center text-danger" role="alert">No se pudo consultar el reporte. Vuelva a intentar.</td></tr>');
            $('#pagination, #textPagination, #numberItems').empty();
            if (!response.message && !response.errors) { toastr.error('No se pudo consultar el reporte.', 'Error'); }
            if (response.message && !response.errors) {
                toastr.error(response.message, 'Error', {
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
            for (var property in response.errors) {
                toastr.error(response.errors[property], 'Error', {
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
    } else {
        toastr.error("Seleccione un material para poder hacer la búsqueda", 'Error', {
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

function renderDataOrdersRequestEmpty(data) {
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

function renderDataOrdersRequest(data) {
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
    clone.querySelector("[data-code]").innerHTML = data.code;
    clone.querySelector("[data-year]").innerHTML = data.year;
    clone.querySelector("[data-observation]").innerHTML = data.observation;
    clone.querySelector("[data-date_order]").innerHTML = data.date_order;
    clone.querySelector("[data-date_arrival]").innerHTML = data.date_arrival;
    clone.querySelector("[data-supplier]").innerHTML = data.supplier;
    clone.querySelector("[data-approved_user]").innerHTML = data.approved_user;
    clone.querySelector("[data-state]").innerHTML = data.state;
    clone.querySelector("[data-currency]").innerHTML = data.currency;
    /*clone.querySelector("[data-material]").innerHTML = data.material;*/
    clone.querySelector("[data-quantity]").innerHTML = parseFloat(data.quantity).toFixed(2);
    clone.querySelector("[data-price]").innerHTML = data.currency + " "+ parseFloat(data.price).toFixed(2);
    clone.querySelector("[data-total]").innerHTML = data.currency + " "+ parseFloat(data.total).toFixed(2);

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

function activateTemplate(id) {
    var t = document.querySelector(id);
    return document.importNode(t.content, true);
}