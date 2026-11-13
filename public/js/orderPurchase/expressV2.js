// Express V2 read path: keep backend query/response contracts; no mutation handlers.
var $permissions;
var expressRequest;
var expressSequence = 0;
$(function () {
    $permissions = JSON.parse($('#permissions').val());
    $('#sandbox-container .input-daterange, #sandbox-container1 .input-daterange').datepicker({
        container: '.wrapper', orientation: 'bottom', todayBtn: 'linked', clearBtn: true,
        language: 'es', multidate: false, autoclose: true
    });
    $('#btnBusquedaAvanzada').on('click', function () {
        var panel = document.getElementById('express-filters');
        panel.hidden = !panel.hidden;
        $(this).attr('aria-expanded', String(!panel.hidden));
    });
    $('#btn-search').on('click', function () { getDataOrderPurchases(1); });
    $('#code, #quote').on('keydown', function (event) {
        if (event.key === 'Enter') { event.preventDefault(); getDataOrderPurchases(1); }
    });
    $(document).on('click', '[data-item]', function (event) {
        event.preventDefault(); getDataOrderPurchases($(this).attr('data-item'));
    });
    getDataOrderPurchases(1);
});
function getDataOrderPurchases(page) {
    var sequence = ++expressSequence;
    if (expressRequest) { expressRequest.abort(); }
    $('#body-table').html('<tr><td colspan="12" class="next-table-empty">Cargando órdenes…</td></tr>');
    $('#numberItems').text('—'); $('#textPagination, #pagination').empty();
    expressRequest = $.get('/dashboard/get/data/orders/express/v2/' + page, {
        year: $('#year').val(), supplier: $('#supplier').val(), code: $('#code').val(),
        quote: $('#quote').val(), state: $('#state').val(),
        deliveryDate: $('#deliveryDate').val(), startDate: $('#start').val(), endDate: $('#end').val()
    }, function (data) {
        if (sequence !== expressSequence) { return; }
        if (!data.data.length) { renderDataOrderPurchasesEmpty(data); }
        else { renderDataOrderPurchases(data); }
    }, 'json').fail(function (xhr, status) {
        if (status === 'abort' || sequence !== expressSequence) { return; }
        $('#body-table').empty().append($('<tr>').append($('<td>', {colspan: 12, 'class': 'next-table-empty', role: 'alert'}).text(
            'No se pudo cargar el listado' + (xhr.status ? ' (HTTP ' + xhr.status + ')' : '') + '. Intente buscar nuevamente.'
        )));
    });
}
function renderDataOrderPurchasesEmpty(data) {
    var pagination = data.pagination;
    $('#body-table, #pagination, #textPagination').empty();
    $('#numberItems').text(pagination.totalFilteredRecords);
    if (pagination.totalFilteredRecords && pagination.currentPage > pagination.totalPages) {
        $('#textPagination').text('Página fuera de rango. Volver a la primera página.');
        renderItemPage(1, pagination.currentPage);
    } else {
        $('#textPagination').text('Mostrando 0 a 0 de 0 órdenes de compra');
    }
    renderDataTableEmpty();
}

function renderDataOrderPurchases(data) {
    var dataFinanceWorks = data.data;
    var pagination = data.pagination;



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
    clone.querySelector("[data-id]").textContent = data.id;
    clone.querySelector("[data-code]").textContent = data.code;
    clone.querySelector("[data-date_order]").textContent = data.date_order;
    clone.querySelector("[data-date_arrival]").textContent = data.date_arrival;
    clone.querySelector("[data-observation]").textContent = data.observation;
    clone.querySelector("[data-supplier]").textContent = data.supplier;
    clone.querySelector("[data-approved_user]").textContent = data.approved_user;
    clone.querySelector("[data-currency]").textContent = data.currency;
    clone.querySelector("[data-total]").textContent = data.total;
    clone.querySelector("[data-type]").innerHTML = data.typeText;
    clone.querySelector("[data-state]").innerHTML = data.stateText;

    var botones = clone.querySelector("[data-buttons]");

    botones.textContent = '—';
    botones.title = 'Acciones fuera del alcance de lectura';

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
