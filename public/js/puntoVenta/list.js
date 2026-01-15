$(document).ready(function () {
    //$permissions = JSON.parse($('#permissions').val());
    //console.log($permissions);
    $('#sandbox-container .input-daterange').datepicker({
        todayBtn: "linked",
        clearBtn: true,
        language: "es",
        multidate: false,
        autoclose: true
    });

    getDataOrders(1);

    $("#btnBusquedaAvanzada").click(function(e){
        e.preventDefault();
        $(".busqueda-avanzada").slideToggle();
    });

    $(document).on('click', '[data-item]', showData);
    $("#btn-search").on('click', showDataSearch);

    $('body').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    $(document).on('click', '[data-ver_detalles]', showDetails);

    $(document).on('click', '[data-anular]', anularOrder);

    /*$(document).on('click', '[data-generar_comprobante]', function () {
        const orderId = $(this).data('order-id');

        $.confirm({
            title: 'Confirmación',
            content: '¿Deseas generar el comprobante?',
            buttons: {
                confirmar: {
                    text: 'Sí, generar',
                    action: function () {
                        $.ajax({
                            url: `/factura/generar/${orderId}`,
                            type: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (response) {
                                if (response.error) {
                                    $.alert('Error: ' + response.error);
                                } else {
                                    $.alert('Comprobante generado con éxito. Ticket: ' + response.ticket);
                                }
                            },
                            error: function (xhr) {
                                $.alert('Error al procesar la solicitud. Intenta nuevamente.');
                            }
                        });
                    }
                },
                cancelar: {
                    text: 'Cancelar',
                    action: function () {}
                }
            }
        });
    });

    $(document).on('click', '[data-imprimir_comprobante]', function () {
        const orderId = $(this).data('order-id');
        window.open(`/factura/imprimir/${orderId}`, '_blank');
    });*/

    $(document).on('click', '[data-facturador]', function () {
        const btn = $(this);
        if (!$('#downloadSection').hasClass('d-none')) {
            $('#downloadSection').addClass('d-none');
        }
        // Asignar ID
        $('#order_id').val(btn.data('id'));

        const tipo = btn.data('tipo-comprobante');
        const docTipo = btn.data('tipo-documento-cliente');
        const numero = btn.data('numero-documento-cliente');
        const direccion = btn.data('direccion-cliente');
        const email = btn.data('email-cliente');
        const nombre = btn.data('nombre-cliente');

        // Limpiar campos anteriores
        $('#formFacturador')[0].reset();
        $('#datos_boleta, #datos_factura').addClass('d-none');

        if (tipo === 'boleta') {
            $('#radio_boleta').prop('checked', true);
            $('#datos_boleta').removeClass('d-none');

            $('#dni').val(numero);
            $('#email_invoice_boleta').val(email);
            $('#name').val(nombre);
        } else if (tipo === 'factura') {
            $('#radio_factura').prop('checked', true);
            $('#datos_factura').removeClass('d-none');

            $('#ruc').val(numero);
            $('#razon_social').val(nombre);
            $('#direccion_fiscal').val(direccion);
            $('#email_invoice_factura').val(email);
        }

        $('#modalFacturador').modal('show');
    });

    $('input[name="invoice_type"]').on('change', function () {
        const selected = $(this).val();
        if (selected === 'boleta') {
            $('#datos_boleta').removeClass('d-none');
            $('#datos_factura').addClass('d-none');
        } else {
            $('#datos_factura').removeClass('d-none');
            $('#datos_boleta').addClass('d-none');
        }
    });

    $('#btnGuardarDatos').on('click', function () {
        const tipoComprobante = $('input[name="invoice_type"]:checked').val();
        const orderId = $('#order_id').val();

        let data = {
            order_id: orderId,
            type_document: tipoComprobante,
        };

        if (tipoComprobante === 'boleta') {
            data.dni = $('#dni').val();
            data.email = $('#email_invoice_boleta').val();
            data.name = $('#name').val();
        } else if (tipoComprobante === 'factura') {
            data.ruc = $('#ruc').val();
            data.razon_social = $('#razon_social').val();
            data.direccion_fiscal = $('#direccion_fiscal').val();
            data.email = $('#email_invoice_factura').val();
        }

        $.ajax({
            url: '/dashboard/sales/update-invoice-data',
            type: 'POST',
            data: data,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                $.alert({
                    title: 'Éxito',
                    content: response.message,
                    type: 'green'
                });
                //$('#modalFacturador').modal('hide');
            },
            error: function (xhr) {
                $.alert({
                    title: 'Error',
                    content: xhr.responseJSON.message || 'Ocurrió un error',
                    type: 'red'
                });
            }
        });
    });

    $('#btnGenerarComprobante').on('click', function () {
        const orderId = $('#order_id').val();

        $.ajax({
            url: '/dashboard/facturador/generar', // Puedes cambiar el nombre si quieres
            type: 'POST',
            data: {
                order_id: orderId
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function () {
                $('#btnGenerarComprobante').prop('disabled', true).text('Generando...');
            },
            success: function (response) {
                $.alert({
                    title: 'Comprobante generado',
                    content: response.message,
                    type: 'green'
                });
                if (response.pdf_url) {
                    $('#btnDescargarPDF').attr('href', response.pdf_url);
                    $('#downloadSection').removeClass('d-none');
                }
                //$('#modalFacturador').modal('hide');
            },
            error: function (xhr) {
                $.alert({
                    title: 'Error',
                    content: xhr.responseJSON.message || 'No se pudo generar el comprobante',
                    type: 'red'
                });
            },
            complete: function () {
                $('#btnGenerarComprobante').prop('disabled', false).text('Generar comprobante');
            }
        });
    });
});

var $formDelete;
var $modalDelete;

var $permissions;

function anularOrder() {
    var order_id = $(this).data('id');

    $.confirm({
        icon: 'fas fa-trash-alt',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'red',
        title: '¿Está seguro de anular esta order?',
        content: 'ORDEN - '+order_id,
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                action: function (e) {
                    $.ajax({
                        url: '/dashboard/anular/order/'+order_id,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            console.log(data);
                            $.alert(data.message);
                            setTimeout( function () {
                                getDataOrders(1);
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
                    $.alert("Cambio de estado cancelado.");
                },
            },
        },
    });

}

function printOrder() {
    const orderId = $(this).data('id');

    // Realizar una solicitud AJAX para obtener los detalles del pedido
    $.ajax({
        url: `/print/order/${orderId}`,
        method: 'POST',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        processData:false,
        contentType:false,
        success: function (response) {
            if (response.error) {
                // Generar dinámicamente el contenido del modal
                toastr.success(response.message, 'Éxito', {
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

            } else {
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
        },
        error: function (xhr) {
            console.error('Error:', xhr.responseText);
            toastr.error(xhr.responseText, 'Error', {
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
    });
}

function showDetails() {
    const orderId = $(this).data('id');

    // Realizar una solicitud AJAX para obtener los detalles del pedido
    $.ajax({
        url: `/dashboard/sales/${orderId}/details`,
        method: 'GET',
        success: function (response) {
            if (response.details) {
                // Generar dinámicamente el contenido del modal
                let content = '';
                response.details.forEach((detail, index) => {
                    content += `
                        <div class="mb-4">
                            <h6><strong>Producto:</strong> ${detail.code} | ${detail.producto}</h6>
                            <p><strong>Cantidad:</strong> ${detail.quantity}</p>
                            <p><strong>Precio:</strong> ${detail.price}</p>
                            <p><strong>Total:</strong> ${detail.total}</p>
                    `;

                    content += `</div><hr />`;
                });

                // Insertar el contenido generado en el modal
                $('#order-details-content').html(content);

                // Mostrar el modal
                $('#orderDetailsModal').modal('show');
            } else {
                toastr.error('No se encontraron detalles para este pedido.', 'Error', {
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
        error: function (xhr) {
            console.error('Error:', xhr.responseText);
            toastr.error('Ocurrió un error al obtener los detalles del pedido.', 'Error', {
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
    });
}

function showDataSearch() {
    getDataOrders(1)
}

function showData() {
    //event.preventDefault();
    var numberPage = $(this).attr('data-item');
    console.log(numberPage);
    getDataOrders(numberPage)
}

function getDataOrders($numberPage) {
    $('[data-toggle="tooltip"]').tooltip('dispose').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    var code = $('#code').val();
    var year = $('#year').val();
    var startDate = $('#start').val();
    var endDate = $('#end').val();

    $.get('/dashboard/get/data/sales/'+$numberPage, {
        code: code,
        year: year,
        startDate: startDate,
        endDate: endDate,
    }, function(data) {
        if ( data.data.length == 0 )
        {
            renderDataOrdersEmpty(data);
        } else {
            renderDataOrders(data);
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

function renderDataOrdersEmpty(data) {
    var dataAccounting = data.data;
    var pagination = data.pagination;
    console.log(dataAccounting);
    console.log(pagination);

    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' pedidos de clientes.');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    renderDataTableEmpty();
}

function renderDataOrders(data) {
    var dataQuotes = data.data;
    var pagination = data.pagination;

    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' pedidos de clientes.');
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

    clone.querySelector("[data-code]").innerHTML = data.code;
    clone.querySelector("[data-date]").innerHTML = data.date;
    clone.querySelector("[data-currency]").innerHTML = data.currency;
    clone.querySelector("[data-total]").innerHTML = data.total;
    clone.querySelector("[data-tipo_pago]").innerHTML = data.tipo_pago;

    var botones = clone.querySelector("[data-buttons]");

    var cloneBtnActive = activateTemplate('#template-active');

    cloneBtnActive.querySelector("[data-ver_detalles]").setAttribute("data-id", data.id);

    const printBtn = cloneBtnActive.querySelector("[data-print_recibo]");
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

    cloneBtnActive.querySelector("[data-anular]").setAttribute("data-id", data.id);

    botones.append(cloneBtnActive);
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