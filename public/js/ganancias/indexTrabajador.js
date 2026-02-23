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

    $("#btn-export").on('click', exportGananciasExcel);

    $("#btn-export-detallado").on('click', exportGananciasVentasDetalladoExcel);

});

/* ===========================
   EXPORT EXCEL + OVERLAY
   =========================== */
function exportGananciasVentasDetalladoExcel(e) {
    e.preventDefault();

    var $btn = $('#btn-export-detallado');
    var $overlay = $('#export-overlay');

    if ($btn.data('loading')) return;
    $btn.data('loading', true);

    var originalHtml = $btn.html();
    $btn.prop('disabled', true).addClass('disabled');
    $btn.html('<i class="fa fa-spinner fa-spin"></i> Descargando...');
    $overlay.fadeIn(150);

    var creator = $('#creator').val();
    var startDate = $('#start').val();
    var endDate = $('#end').val();

    var url = '/dashboard/export/ganancia/ventas-detallado'
        + '?creator=' + encodeURIComponent(creator || '')
        + '&startDate=' + encodeURIComponent(startDate || '')
        + '&endDate=' + encodeURIComponent(endDate || '');

    fetch(url, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        }
    })
        .then(async function (response) {
            if (!response.ok) {
                let msg = 'Error al generar el Excel.';
                try {
                    const data = await response.json();
                    msg = data.message || msg;
                } catch (err) {
                    try {
                        const txt = await response.text();
                        if (txt) msg = txt;
                    } catch (e) {}
                }
                throw new Error(msg);
            }

            const blob = await response.blob();

            const disposition = response.headers.get('content-disposition') || '';
            const filename = getFilenameFromDisposition(disposition)
                || ('ganancia_ventas_detallado_' + new Date().toISOString().slice(0,19).replace(/[:T]/g,'-') + '.xlsx');

            const blobUrl = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = blobUrl;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(blobUrl);
        })
        .catch(function (err) {
            console.error(err);
            if (typeof toastr !== 'undefined') {
                toastr.error(err.message || 'Error al descargar el Excel.', 'Error', {
                    closeButton: true,
                    progressBar: true,
                    positionClass: "toast-top-right",
                    timeOut: 2500
                });
            } else {
                alert(err.message || 'Error al descargar el Excel.');
            }
        })
        .finally(function () {
            $overlay.fadeOut(150);
            $btn.html(originalHtml);
            $btn.prop('disabled', false).removeClass('disabled');
            $btn.data('loading', false);
        });
}


function exportGananciasExcel(e) {
    e.preventDefault();

    var $btn = $('#btn-export');
    var $overlay = $('#export-overlay');

    if ($btn.data('loading')) return;
    $btn.data('loading', true);

    var originalHtml = $btn.html();
    $btn.prop('disabled', true).addClass('disabled');
    $btn.html('<i class="fa fa-spinner fa-spin"></i> Descargando...');
    $overlay.fadeIn(150);

    var creator = $('#creator').val();
    var startDate = $('#start').val();
    var endDate = $('#end').val();

    var url = '/dashboard/export/ganancias/trabajador'
        + '?creator=' + encodeURIComponent(creator || '')
        + '&startDate=' + encodeURIComponent(startDate || '')
        + '&endDate=' + encodeURIComponent(endDate || '');

    fetch(url, {
        method: 'GET',
        credentials: 'same-origin', // ✅ importante si estás autenticada por sesión/cookies
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        }
    })
        .then(async function (response) {
            if (!response.ok) {
                // Intentar leer error JSON si el backend lo devuelve
                let msg = 'Error al generar el Excel.';
                try {
                    const data = await response.json();
                    msg = data.message || msg;
                } catch (err) {
                    // si no es JSON, intentar texto
                    try {
                        const txt = await response.text();
                        if (txt) msg = txt;
                    } catch (e) {}
                }
                throw new Error(msg);
            }

            const blob = await response.blob();

            // ✅ Obtener nombre desde Content-Disposition (si existe)
            const disposition = response.headers.get('content-disposition') || '';
            const filename = getFilenameFromDisposition(disposition) || ('ganancias_trabajador_' + new Date().toISOString().slice(0,19).replace(/[:T]/g,'-') + '.xlsx');

            // ✅ Descargar blob
            const blobUrl = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = blobUrl;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(blobUrl);
        })
        .catch(function (err) {
            console.error(err);

            // toastr (si lo usas)
            if (typeof toastr !== 'undefined') {
                toastr.error(err.message || 'Error al descargar el Excel.', 'Error', {
                    closeButton: true,
                    progressBar: true,
                    positionClass: "toast-top-right",
                    timeOut: 2500
                });
            } else {
                alert(err.message || 'Error al descargar el Excel.');
            }
        })
        .finally(function () {
            $overlay.fadeOut(150);
            $btn.html(originalHtml);
            $btn.prop('disabled', false).removeClass('disabled');
            $btn.data('loading', false);
        });
}

// Extrae filename de Content-Disposition
function getFilenameFromDisposition(disposition) {
    // Ej: attachment; filename="reporte.xlsx"
    // o: attachment; filename*=UTF-8''reporte%20final.xlsx
    let filename = null;

    const utf8Match = disposition.match(/filename\*\s*=\s*UTF-8''([^;]+)/i);
    if (utf8Match && utf8Match[1]) {
        try { return decodeURIComponent(utf8Match[1].trim().replace(/(^"|"$)/g, '')); } catch (e) {}
    }

    const asciiMatch = disposition.match(/filename\s*=\s*([^;]+)/i);
    if (asciiMatch && asciiMatch[1]) {
        filename = asciiMatch[1].trim().replace(/(^"|"$)/g, '');
    }

    return filename;
}

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
