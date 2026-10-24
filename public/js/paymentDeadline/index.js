$(document).ready(function () {
    $permissions = JSON.parse($('#permissions').val());
    console.log($permissions);
    $('#dynamic-table').DataTable( {
        ajax: {
            url: "/dashboard/all/paymentDeadlines",
            dataSrc: 'data'
        },
        bAutoWidth: false,
        "aoColumns": [
            {data: 'description', render: $.fn.dataTable.render.text()},
            {data: 'days'},
            {data: 'type', render: function (value) { return value === 'purchases' ? 'COMPRAS / SERVICIOS' : 'COTIZACIONES'; }},
            {data: 'credit', render: function (value) { return value ? 'SI' : 'NO'; }},
            {data: 'is_active', render: function (value) { return value ? 'Activo' : 'Inactivo'; }},
            {data: null, render: function (item) {
                var actions = $('<div>');
                if ($.inArray('update_paymentDeadline', $permissions) !== -1) {
                    $('<a>').attr('href', document.location.origin + '/dashboard/editar/plazo/pago/' + item.id)
                        .attr('title', 'Editar').addClass('btn btn-outline-warning btn-sm').html('<i class="fa fa-pen"></i>').appendTo(actions);
                }
                if (item.is_active && $.inArray('destroy_paymentDeadline', $permissions) !== -1) {
                    $('<button>').attr('data-delete', item.id).attr('data-name', item.description).attr('title', 'Desactivar')
                        .addClass('btn btn-outline-danger btn-sm').html('<i class="fa fa-ban"></i>').appendTo(actions);
                }
                return actions.html();
            }}
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

    $formDelete = $('#formDelete');
    $('#btn-submit').on('click', destroyPaymentDeadline);
    //$formDelete.on('submit', destroyPaymentDeadline);
    $modalDelete = $('#modalDelete');
    $(document).on('click', '[data-delete]', openModalDelete);
});

var $formDelete;
var $modalDelete;
var $permissions;

function openModalDelete() {
    var paymentDeadline_id = $(this).data('delete');
    var name = $(this).data('name');

    $modalDelete.find('[id=paymentDeadline_id]').val(paymentDeadline_id);
    $modalDelete.find('[id=name]').text(name);

    $modalDelete.modal('show');
}

function destroyPaymentDeadline(event) {
    event.preventDefault();
    var button = $('#btn-submit');
    if (button.prop('disabled')) return;
    button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Desactivando');
    $.ajax({url: $formDelete.data('url'), type: 'POST', data: new FormData($formDelete[0]),
        processData: false, contentType: false, headers: {'Accept': 'application/json'}})
        .done(function (data) {
            $modalDelete.modal('hide');
            var refresh = function () { window.location.reload(); };
            if ($.alert) $.alert({title: 'Plazo desactivado', content: $('<div>').text(data.message).html(), type: 'green', onClose: refresh});
            else { toastr.success(data.message); refresh(); }
        })
        .fail(function (xhr) {
            var data = xhr.responseJSON || {};
            var messages = data.errors ? Object.keys(data.errors).reduce(function (all, key) { return all.concat(data.errors[key]); }, []) : [data.message || 'No se pudo desactivar.'];
            messages.forEach(function (message) { toastr.error(message); });
            button.prop('disabled', false).text('Desactivar');
        });
}
