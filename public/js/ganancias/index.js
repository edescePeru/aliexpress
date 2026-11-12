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
    getDataGanancias(1);

    $(document).on('click', '[data-item]', showData);

});

function showData(event) {
    event.preventDefault();
    var numberPage = $(this).attr('data-item');

    getDataGanancias(numberPage)
}

function getDataGanancias($numberPage) {
    $('[data-toggle="tooltip"]').tooltip('dispose').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    var detailId = $('#ganancia_id').val();
    var endpoint = detailId ? '/dashboard/get/data/ganancia/details/V2/' : '/dashboard/get/data/ganancias/V2/';
    $.get(endpoint+$numberPage, detailId ? {ganancia_id: detailId} : {},function(data) {
        if ( data.data.length == 0 )
        {
            renderDataGananciasEmpty(data);
        } else {
            renderDataGanancias(data);
        }


    }).fail(function(jqXHR, textStatus, errorThrown) {
        // Función de error, se ejecuta cuando la solicitud GET falla
        console.error(textStatus, errorThrown);
        if ((jqXHR.responseJSON || {}).message && !(jqXHR.responseJSON || {}).errors) {
            toastr.error((jqXHR.responseJSON || {}).message, 'Error', {
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
        for (var property in (jqXHR.responseJSON || {}).errors) {
            toastr.error((jqXHR.responseJSON || {}).errors[property], 'Error', {
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



    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+(pagination.totalFilteredRecords ? pagination.startRecord : 0)+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' registros');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    renderDataTableEmpty();
}

function renderDataGanancias(data) {
    var dataCombos = data.data;
    var pagination = data.pagination;

    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+(pagination.totalFilteredRecords ? pagination.startRecord : 0)+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' registros.');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

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
            renderItemPage(pagination.totalPages, pagination.currentPage);
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
    var detail = !!$('#ganancia_id').val();
    clone.querySelector('[data-id]').textContent = detail ? data.material_description : data.id;
    clone.querySelector('[data-date_resumen]').textContent = detail ? data.date_detail : data.date_resumen;
    clone.querySelector('[data-quantity_sale]').textContent = detail ? data.quantity : data.quantity_sale;
    clone.querySelector('[data-total_sale]').textContent = detail ? data.price_sale : data.total_sale;
    var utility = detail ? data.utility : data.total_utility;
    clone.querySelector('[data-total_utility]').textContent = utility === null ? 'Sin costo registrado' : utility;
    if (detail && data.id === 0) clone.querySelector('tr').classList.add('font-weight-bold', 'table-light');
    if (!detail) {
        var cloneBtn = activateTemplate('#template-button');
        cloneBtn.querySelector('[data-ver_detalles]').setAttribute('href', '/dashboard/listado/ganancia/detalles/' + encodeURIComponent(data.id));
        clone.querySelector('[data-buttons]').append(cloneBtn);
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

function activateTemplate(id) {
    var t = document.querySelector(id);
    return document.importNode(t.content, true);
}
