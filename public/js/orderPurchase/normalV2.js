$(function () {
    $permissions = JSON.parse($('#permissions').val());
    $('#sandbox-container .input-daterange, #sandbox-container1 .input-daterange').datepicker({
        container: '.wrapper', orientation: 'bottom', todayBtn: 'linked', clearBtn: true,
        language: 'es', multidate: false, autoclose: true
    });
    getDataOrderPurchases(1);
    $('#btnBusquedaAvanzada').on('click', function (event) {
        event.preventDefault();
        var panel = document.getElementById('normal-filters');
        panel.hidden = !panel.hidden;
        $(this).attr('aria-expanded', String(!panel.hidden));
    });
    $(document).on('click', '[data-item]', showData);
    $('#btn-search').on('click', showDataSearch);
    $('#code').on('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); showDataSearch(); } });
});
var $permissions;
function showDataSearch() {
    getDataOrderPurchases(1)
}

function showData(event) {
    event.preventDefault();
    //event.preventDefault();
    var numberPage = $(this).attr('data-item');

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
        if (jqXHR.responseJSON && jqXHR.responseJSON.message && !jqXHR.responseJSON.errors) {
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
        for (var property in ((jqXHR.responseJSON || {}).errors || {})) {
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



    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+(pagination.totalFilteredRecords ? pagination.startRecord : 0)+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' órdenes de compra');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    renderDataTableEmpty();
}

function renderDataOrderPurchases(data) {
    var dataFinanceWorks = data.data;
    var pagination = data.pagination;



    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+(pagination.totalFilteredRecords ? pagination.startRecord : 0)+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' órdenes de compra.');
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
            renderItemPage(pagination.totalPages, pagination.currentPage);
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
    ['id', 'code', 'date_order', 'date_arrival', 'observation', 'supplier', 'created_user', 'currency', 'total'].forEach(function (field) {
        clone.querySelector('[data-' + field + ']').textContent = data[field] == null ? '—' : data[field];
    });
    // Status text and type are supplied by the current backend contract.
    clone.querySelector('[data-type]').innerHTML = data.typeText.replace(/bg-(primary|success|warning)/g, 'badge-$1');
    clone.querySelector('[data-state]').innerHTML = data.stateText.replace(/bg-(primary|success|warning)/g, 'badge-$1');
    if (data.type === 'n' && $.inArray('list_orderPurchaseNormal', $permissions) !== -1) {
        var actions = activateTemplate('#template-normal');
        actions.querySelector('[data-ver_orden]').href = '/dashboard/ver/orden/compra/normal/' + data.id;
        clone.querySelector('[data-buttons]').append(actions);
    }
    $('#body-table').append(clone);
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
