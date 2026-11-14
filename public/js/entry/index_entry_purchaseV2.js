// Read-only list: detail, documents and receipt actions intentionally excluded.
var listRequestVersion = 0;
$(document).ready(function () {
    $('#sandbox-container .input-daterange').datepicker({todayBtn: 'linked', clearBtn: true, language: 'es', multidate: false, autoclose: true, orientation: 'bottom auto', container: '.wrapper'});
    $('#deliveryDate').datepicker({todayBtn: 'linked', clearBtn: true, language: 'es', autoclose: true, orientation: 'bottom auto', container: '.wrapper'});
    $(document).on('click', '[data-item]', showData);
    $('#btn-search').on('click', showDataSearch);
    $('#list-filters').on('submit', function (event) { event.preventDefault(); showDataSearch(); });
    getDataEntries(1);
});
function activateTemplate(id) { return document.importNode(document.querySelector(id).content, true); }
function showDataSearch() {
    getDataEntries(1)
}

function showData(event) {
    event.preventDefault();
    var numberPage = $(this).attr('data-item');

    getDataEntries(numberPage)
}

function getDataEntries($numberPage) {

    var year = $('#year').val();
    var invoice = $('#invoice').val();
    var supplier = $('#supplier').val();
    var guide = $('#guide').val();
    var order = $('#order').val();
    var startDate = $('#start').val();
    var endDate = $('#end').val();

    var version = ++listRequestVersion;
    $('#list-error').addClass('d-none').text('');
    $.get('/dashboard/get/all/entries/v2/'+$numberPage, {
        year: year,
        supplier: supplier,
        invoice: invoice,
        guide: guide,
        order: order,
        startDate: startDate,
        endDate: endDate,
    }, function(data) {
        if (version !== listRequestVersion) return;
        if ( data.data.length == 0 )
        {
            renderDataEntriesEmpty(data);
        } else {
            renderDataEntries(data);
        }


    }).fail(function () {
        if (version !== listRequestVersion) return;
        $('#body-table, #pagination').empty();
        $('#numberItems, #textPagination').text('');
        $('#list-error').removeClass('d-none').text('No se pudo cargar el listado. Vuelve a buscar.');
    });
}

function renderDataEntriesEmpty(data) {
    var dataAccounting = data.data;
    var pagination = data.pagination;



    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+(pagination.totalFilteredRecords ? pagination.startRecord : 0)+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' ingresos por compra');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    renderDataTableEmpty();
}

function renderDataEntries(data) {
    var dataFinanceWorks = data.data;
    var pagination = data.pagination;



    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+(pagination.totalFilteredRecords ? pagination.startRecord : 0)+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' ingresos por compra.');
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
    ['id', 'guide', 'order', 'invoice', 'type', 'supplier', 'date_entry', 'currency', 'total'].forEach(function (field) { clone.querySelector('[data-' + field + ']').textContent = data[field] == null ? '' : data[field]; });
    clone.querySelector('[data-diferidoText]').innerHTML = data.diferidoText;
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
