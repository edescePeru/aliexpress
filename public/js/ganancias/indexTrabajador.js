var $materialsTypeahead = [];
var $permissions;
var $materials = [];
var $total = 0;
var $modalOpen;
var $modalClose;
var $modalIncome;
var $modalExpense;

$(document).ready(function () {
    $permissions = JSON.parse($('#permissions').val());

    $('#sandbox-container .input-daterange').datepicker({
        todayBtn: "linked",
        clearBtn: true,
        language: "es",
        multidate: false,
        autoclose: true
    });

    getDataGanancias(1);

    $(document).on('click', '[data-item]', showData);

    $("#btn-search").on('click', showDataSearch);

});

function showDataSearch() {
    getDataGanancias(1)
}

function showData() {
    //event.preventDefault();
    var numberPage = $(this).attr('data-item');
    console.log(numberPage);
    getDataGanancias(numberPage)
}

function getDataGanancias($numberPage) {
    $('[data-toggle="tooltip"]').tooltip('dispose').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    var creator = $('#creator').val();
    var startDate = $('#start').val();
    var endDate = $('#end').val();

    $.get('/dashboard/get/data/ganancias/trabajador/V2/'+$numberPage, {
        creator: creator,
        startDate: startDate,
        endDate: endDate,
    },function(data) {
        if ( data.data.length == 0 )
        {
            renderDataGananciasEmpty(data);
        } else {
            renderDataGanancias(data);
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

function renderDataGananciasEmpty(data) {
    var dataAccounting = data.data;
    var pagination = data.pagination;
    console.log(dataAccounting);
    console.log(pagination);

    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');

    $("#resumen-quantity").text("S/ " +0.00);
    $("#resumen-total-sale").text("S/ " +0.00);
    $("#resumen-total-utility").text("S/ " +0.00);

    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' ganancias');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    renderDataTableEmpty();
}

function renderDataGanancias(data) {
    var dataCombos = data.data;
    var pagination = data.pagination;
    var totals = data.totals;

    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');

    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' ganancias.');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    $('#resumen-quantity').text(totals.quantity_sale_sum);
    $('#resumen-total-sale').text(
        "S/ " + Number(totals.total_sale_sum).toLocaleString('es-PE', { minimumFractionDigits: 2 })
    );

    $('#resumen-total-utility').text(
        "S/ " + Number(totals.total_utility_sum).toLocaleString('es-PE', { minimumFractionDigits: 2 })
    );

    for (let j = 0; j < dataCombos.length ; j++) {
        renderDataTable(dataCombos[j]);
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
    clone.querySelector("[data-date_resumen]").innerHTML = data.date_resumen;
    clone.querySelector("[data-quantity_sale]").innerHTML = data.quantity_sale;
    clone.querySelector("[data-total_sale]").innerHTML = data.total_sale;
    clone.querySelector("[data-total_utility]").innerHTML = data.total_utility;

    var botones = clone.querySelector("[data-buttons]");

    var cloneBtn = activateTemplate('#template-button');

    /*let url = document.location.origin + '/dashboard/listado/ganancia/detalles/' + data.id;
    cloneBtn.querySelector("[data-ver_detalles]").setAttribute("href", url);*/
    const printBtn = cloneBtn.querySelector("[data-ver_detalles]");
    printBtn.setAttribute("data-id", data.id);

    // ✅ URL viene del backend (pdf o ticket)
    printBtn.setAttribute("href", data.print_url || "#");
    printBtn.setAttribute("title", data.print_label || "Ver comprobante");

    if (!data.print_url) {
        printBtn.classList.add('disabled');
        printBtn.removeAttribute('target');
        printBtn.addEventListener('click', function (e) { e.preventDefault(); });
    } else {
        // por si acaso
        printBtn.setAttribute('target', '_blank');
    }

    botones.append(cloneBtn);

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
