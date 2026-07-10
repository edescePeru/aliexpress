function format(d) {
    const location = d.location || {};

    const area = location.area ? location.area.name : '-';
    const warehouse = location.warehouse ? location.warehouse.name : '-';
    const shelf = location.shelf ? location.shelf.name : '-';
    const level = location.level ? location.level.name : '-';
    const container = location.container ? location.container.name : '-';
    const position = location.position ? location.position.name : '-';

    const locationText =
        'AR:' + area +
        '|AL:' + warehouse +
        '|ES:' + shelf +
        '|FIL:' + level +
        '|COL:' + container +
        '|POS:' + position;

    const typescrap = d.typescrap
        ? d.typescrap.name
        : 'No tiene';

    return 'Estado: ' + (d.state || '-') + '<br>' +
        'Estado de Item: ' + (d.state_item || '-') + '<br>' +
        'Tipo de Material: ' + typescrap + '<br>' +
        'Ubicación: ' + locationText + '<br>';
}

function escapeHtml(value) {
    return $('<div>').text(value || '').html();
}

function can(permission) {
    return Array.isArray(window.permissions) &&
        window.permissions.includes(permission);
}

$(document).ready(function () {
    var idMaterial = $("#id-material").val();

    var table = $('#dynamic-table').DataTable( {
        processing: true,
        serverSide: true,
        ajax: {
            url: "/dashboard/view/stock/material/all/items/" + idMaterial,
            type: "GET"
        },
        bAutoWidth: false,
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        deferRender: true,
        rowId: 'id',
        columns: [
            {
                className: "details-control",
                orderable: false,
                searchable: false,
                data: null,
                defaultContent: ""
            },
            {
                data: "material_description",
                name: "material.full_name"
            },
            {
                data: "code",
                name: "code",
                render: function (data, type, row) {
                    const code = data || '';

                    let buttonEdit = '';

                    if (can('editarCodeItems_material')) {
                        buttonEdit = `
                                <button type="button"
                                        class="btn btn-xs btn-outline-primary ml-2 btn-edit-item-code"
                                        data-item-id="${row.id}"
                                        title="Editar código">
                                    <i class="fa fa-edit"></i>
                                </button>
                            `;
                    }

                    return `
                        <div class="d-flex align-items-center justify-content-between">
                            <span>${escapeHtml(code)}</span>
                            ${buttonEdit}
                        </div>
                    `;
                }
            },
            { data: "price", name: "price" },
            { data: "percentage", name: "percentage" },
            {
                data: null,
                title: "Estado",
                orderable: false,
                searchable: false,
                render: function (item) {
                    var status = item.state_item === "entered"
                        ? '<span class="badge bg-success">Ingresado</span>'
                        : item.state_item === "reserved"
                            ? '<span class="badge bg-warning">Reservado</span>'
                            : '<span class="badge bg-secondary">Indefinido</span>';

                    return '<p class="mb-0">' + status + '</p>';
                }
            }
        ],

        aaSorting: [],
        select: {
            style: "single"
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

    // Array to track the ids of the details displayed rows
    var detailRows = [];

    $('#dynamic-table tbody').on( 'click', 'tr td.details-control', function () {
        var tr = $(this).closest('tr');
        var row = table.row( tr );
        var idx = $.inArray( tr.attr('id'), detailRows );

        if ( row.child.isShown() ) {
            tr.removeClass( 'details' );
            row.child.hide();

            // Remove from the 'open' array
            detailRows.splice( idx, 1 );
        }
        else {
            tr.addClass( 'details' );
            row.child( format( row.data() ) ).show();

            // Add to the 'open' array
            if ( idx === -1 ) {
                detailRows.push( tr.attr('id') );
            }
        }
    } );

    // On each draw, loop over the `detailRows` array and show any child rows
    table.on( 'draw', function () {
        $.each( detailRows, function ( i, id ) {
            $('#'+id+' td.details-control').trigger( 'click' );
        } );
    } );

    $(document).on('click', '[data-column]', function (e) {
        //e.preventDefault();

        // Get the column API object
        var column = table.column( $(this).attr('data-column') );

        // Toggle the visibility
        column.visible( ! column.visible() );
    } );

    $(document).on('click', '.btn-edit-item-code', function () {
        const itemId = $(this).data('item-id');
        const row = table.row($(this).closest('tr')).data();

        abrirModalEditarCodigoItem(itemId, row.code || '');
    });

    $(document).on('click', '#btnGuardarCodigoItem', function () {

        const itemId = $('#edit_item_id').val();
        const code = ($('#edit_item_code').val() || '').trim();

        if (!itemId) {
            toastr.error('No se encontró el ítem a actualizar.', 'Error');
            return;
        }

        if (!code) {
            $('#edit_item_code').addClass('is-invalid').focus();
            toastr.error('Debe ingresar un código o serie.', 'Error');
            return;
        }

        $('#edit_item_code').removeClass('is-invalid');

        const button = $(this);

        button.prop('disabled', true);
        button.html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: '/dashboard/stock/material/item/' + itemId + '/code',
            type: 'PUT',
            data: {
                code: code,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {

                toastr.success(
                    response.message || 'Código actualizado correctamente.',
                    'Correcto'
                );

                $('#modalEditarCodigoItem').modal('hide');

                // Recarga manteniendo la página actual.
                table.ajax.reload(null, false);
            },
            error: function (xhr) {

                let message = 'No se pudo actualizar el código.';

                if (xhr.status === 422) {
                    message = xhr.responseJSON?.message || message;

                    if (xhr.responseJSON?.errors?.code?.[0]) {
                        message = xhr.responseJSON.errors.code[0];
                    }

                    $('#edit_item_code').addClass('is-invalid').focus();
                }

                toastr.error(message, 'Error');
            },
            complete: function () {
                button.prop('disabled', false);
                button.html('<i class="fa fa-save"></i> Guardar');
            }
        });
    });
});

function abrirModalEditarCodigoItem(itemId, code) {
    $('#edit_item_id').val(itemId);
    $('#edit_item_code').val(code);

    $('#modalEditarCodigoItem').modal('show');

    setTimeout(function () {
        $('#edit_item_code').focus().select();
    }, 250);
}