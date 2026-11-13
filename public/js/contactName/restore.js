$(document).ready(function () {
    $('#dynamic-table').DataTable( {
        ajax: {
            url: "/dashboard/all/contacts/destroy",
            dataSrc: 'data'
        },
        bAutoWidth: false,
        dom: '<"next-list-toolbar"<"next-list-search-row"<"next-list-search-control"f><"next-list-length"l>>><"next-list-summary text-wrap"i><"next-list-content"<"table-responsive"t>><"next-list-pagination"p>',
        columnDefs: [{targets: [0, 6], className: 'text-center'}, {targets: 6, orderable: false, searchable: false}],
        preDrawCallback: function () { $(this.api().table().container()).find('.dataTables_info').addClass('text-wrap'); },
        drawCallback: function () { $(this.api().table().container()).find('.dataTables_empty').addClass('text-left text-wrap'); },

        "aoColumns": [
            { data: 'code', render: $.fn.dataTable.render.text() },
            { data: 'name', render: $.fn.dataTable.render.text() },
            { data: 'customer.business_name', defaultContent: '', render: $.fn.dataTable.render.text() },
            { data: null,
                title: 'Área',
                wrap: true,
                "render": function (item)
                {
                    return (item.area == null || item.area == "") ? 'Sin área':contactDisplay(item.area);
                }
            },
            { data: 'phone', render: $.fn.dataTable.render.text() },
            { data: 'email', render: $.fn.dataTable.render.text() },
            { data: null,
                title: 'Acciones',
                wrap: true,
                "render": function (item) {
                    var actions = '';
                    actions += '<button type="button" data-restore="' + item.id + '" data-contact="' + contactDisplay(item.name) + '" data-company="' + contactDisplay(item.customer ? item.customer.business_name : '') + '" data-idcompany="' + (item.customer ? item.customer.id : '') + '" class="dropdown-item"><i class="fas fa-trash-restore" aria-hidden="true"></i> Restaurar</button>';
                    return actions ? '<div class="dropdown next-row-actions"><button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle next-row-actions-trigger" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false" aria-label="Acciones del contacto"><i class="fas fa-ellipsis-h" aria-hidden="true"></i></button><div class="dropdown-menu dropdown-menu-right">' + actions + '</div></div>' : '—';
                } },

        ],
        "aaSorting": [],

        select: {
            style: 'single'
        },
        language: {
            "processing": "Procesando...",
            "lengthMenu": "Mostrar _MENU_ registros",
            "zeroRecords": "No se encontraron resultados",
            "emptyTable": "Ningún dato disponible en esta tabla",
            "info": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "infoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "infoFiltered": "(filtrado de un total de _MAX_ registros)",
            "search": "Buscar:",
            "infoThousands": ",",
            "loadingRecords": "Cargando...",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            },
            "aria": {
                "sortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sortDescending": ": Activar para ordenar la columna de manera descendente"
            },
            "buttons": {
                "copy": "Copiar",
                "colvis": "Visibilidad",
                "collection": "Colección",
                "colvisRestore": "Restaurar visibilidad",
                "copyKeys": "Presione ctrl o u2318 + C para copiar los datos de la tabla al portapapeles del sistema. <br \/> <br \/> Para cancelar, haga clic en este mensaje o presione escape.",
                "copySuccess": {
                    "1": "Copiada 1 fila al portapapeles",
                    "_": "Copiadas %d fila al portapapeles"
                },
                "copyTitle": "Copiar al portapapeles",
                "csv": "CSV",
                "excel": "Excel",
                "pageLength": {
                    "-1": "Mostrar todas las filas",
                    "1": "Mostrar 1 fila",
                    "_": "Mostrar %d filas"
                },
                "pdf": "PDF",
                "print": "Imprimir"
            },
            "autoFill": {
                "cancel": "Cancelar",
                "fill": "Rellene todas las celdas con <i>%d<\/i>",
                "fillHorizontal": "Rellenar celdas horizontalmente",
                "fillVertical": "Rellenar celdas verticalmentemente"
            },
            "decimal": ",",
            "searchBuilder": {
                "add": "Añadir condición",
                "button": {
                    "0": "Constructor de búsqueda",
                    "_": "Constructor de búsqueda (%d)"
                },
                "clearAll": "Borrar todo",
                "condition": "Condición",
                "conditions": {
                    "date": {
                        "after": "Despues",
                        "before": "Antes",
                        "between": "Entre",
                        "empty": "Vacío",
                        "equals": "Igual a",
                        "not": "No",
                        "notBetween": "No entre",
                        "notEmpty": "No Vacio"
                    },
                    "number": {
                        "between": "Entre",
                        "empty": "Vacio",
                        "equals": "Igual a",
                        "gt": "Mayor a",
                        "gte": "Mayor o igual a",
                        "lt": "Menor que",
                        "lte": "Menor o igual que",
                        "not": "No",
                        "notBetween": "No entre",
                        "notEmpty": "No vacío"
                    },
                    "string": {
                        "contains": "Contiene",
                        "empty": "Vacío",
                        "endsWith": "Termina en",
                        "equals": "Igual a",
                        "not": "No",
                        "notEmpty": "No Vacio",
                        "startsWith": "Empieza con"
                    }
                },
                "data": "Data",
                "deleteTitle": "Eliminar regla de filtrado",
                "leftTitle": "Criterios anulados",
                "logicAnd": "Y",
                "logicOr": "O",
                "rightTitle": "Criterios de sangría",
                "title": {
                    "0": "Constructor de búsqueda",
                    "_": "Constructor de búsqueda (%d)"
                },
                "value": "Valor"
            },
            "searchPanes": {
                "clearMessage": "Borrar todo",
                "collapse": {
                    "0": "Paneles de búsqueda",
                    "_": "Paneles de búsqueda (%d)"
                },
                "count": "{total}",
                "countFiltered": "{shown} ({total})",
                "emptyPanes": "Sin paneles de búsqueda",
                "loadMessage": "Cargando paneles de búsqueda",
                "title": "Filtros Activos - %d"
            },
            "select": {
                "1": "%d fila seleccionada",
                "_": "%d filas seleccionadas",
                "cells": {
                    "1": "1 celda seleccionada",
                    "_": "$d celdas seleccionadas"
                },
                "columns": {
                    "1": "1 columna seleccionada",
                    "_": "%d columnas seleccionadas"
                }
            },
            "thousands": ".",
            "datetime": {
                "previous": "Anterior",
                "next": "Proximo",
                "hours": "Horas"
            }
        }

    } );

    $formRestore = $('#formRestore');
    $formRestore.on('submit', restoreCustomer);
    $modalRestore = $('#modalRestore');
    $(document).on('click', '[data-restore]', openModalRestore);
});

var $formRestore;
var $modalRestore;

function openModalRestore() {

    var customer_id = $(this).data('restore'); 
    var contact = $(this).data('contact');
    var company = $(this).data('company');
    var company_id = $(this).data('idcompany');

    $modalRestore.find('[id=contactName_id]').val(customer_id);
    $modalRestore.find('[id=customer_id]').val(company_id);
    $modalRestore.find('[id=contact]').text('Contacto: '+contact);
    $modalRestore.find('[id=company]').text('Cliente: '+company);

    $modalRestore.modal('show');
}

function restoreCustomer() {
    event.preventDefault();
    // Obtener la URL
    var restoreUrl = $formRestore.data('url');
    $.ajax({
        url: restoreUrl,
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
            $modalRestore.modal('hide');
            setTimeout( function () {
                location.reload();
            }, 2000 )
        },
        error: function (data) {
            console.log(data);
            if ( data.responseJSON.message && !data.responseJSON.errors )
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
                        "timeOut": "4000",
                        "extendedTimeOut": "1000",
                        "showEasing": "swing",
                        "hideEasing": "linear",
                        "showMethod": "fadeIn",
                        "hideMethod": "fadeOut"
                    });
            } else {
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
                            "timeOut": "4000",
                            "extendedTimeOut": "1000",
                            "showEasing": "swing",
                            "hideEasing": "linear",
                            "showMethod": "fadeIn",
                            "hideMethod": "fadeOut"
                        });
                }
            }

        },
    });
}
function contactDisplay(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
        return {'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[c];
    });
}
