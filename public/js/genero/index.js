$(document).ready(function () {
    $permissions = JSON.parse($('#permissions').val());
    console.log($permissions);
    $('#dynamic-table').DataTable( {
        ajax: {
            url: "/dashboard/all/generos",
            dataSrc: 'data'
        },
        bAutoWidth: false,
        "aoColumns": [
            {
                data: null,
                orderable: false,
                render: function (data, type, row) {
                    return `<input type="checkbox" class="row-checkbox" value="${row.id}">`;
                }
            },
            { data: 'name' },
            { data: 'description' },
            { data: null,
                title: 'Acciones',
                wrap: true,
                "render": function (item)
                {
                    var text = '';
                    if ( $.inArray('update_warrant', $permissions) !== -1 ) {
                        text = text + '<a href="'+document.location.origin+ '/dashboard/editar/genero/'+item.id+
                            '" class="btn btn-outline-warning btn-sm"><i class="fa fa-pen"></i></a>';
                    }
                    if ( $.inArray('destroy_warrant', $permissions) !== -1 ) {
                        text = text + '<button data-delete="'+item.id+'" data-name="'+item.name+'" '+
                            'class="btn btn-outline-danger btn-sm"><i class="fa fa-trash"></i></button>';
                    }
                    return text;

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

    $formDelete = $('#formDelete');
    $formDelete.on('submit', destroyWarrant);
    $modalDelete = $('#modalDelete');
    $(document).on('click', '[data-delete]', openModalDelete);

    $('#select-all').on('click', function () {
        let rows = $('#dynamic-table').DataTable().rows({ search: 'applied' }).nodes();
        $('input[type="checkbox"].row-checkbox', rows).prop('checked', this.checked);
    });

    $('#delete-selected').on('click', function () {
        let ids = [];
        $('.row-checkbox:checked').each(function () {
            ids.push($(this).val());
        });

        if (ids.length === 0) {
            $.alert({
                title: 'Atención',
                content: 'No hay elementos seleccionados.',
                type: 'orange',
                typeAnimated: true
            });
            return;
        }

        $.confirm({
            title: '¿Confirmar eliminación?',
            content: '¿Estás seguro que deseas eliminar los elementos seleccionados?',
            type: 'red',
            buttons: {
                confirmar: {
                    text: 'Sí, eliminar',
                    btnClass: 'btn-red',
                    action: function () {
                        $.ajax({
                            url: '/dashboard/genero/delete-multiple',
                            type: 'POST',
                            data: {
                                ids: ids,
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (response) {
                                $.alert({
                                    title: 'Éxito',
                                    content: response.message,
                                    type: 'green',
                                    typeAnimated: true
                                });
                                $('#dynamic-table').DataTable().ajax.reload();
                            },
                            error: function (xhr) {
                                $.alert({
                                    title: 'Error',
                                    content: 'Error al eliminar elementos.',
                                    type: 'red',
                                    typeAnimated: true
                                });
                            }
                        });
                    }
                },
                cancelar: {
                    text: 'Cancelar',
                    action: function () { }
                }
            }
        });
    });
});

var $formDelete;
var $modalDelete;
var $permissions;

function openModalDelete() {
    var genero_id = $(this).data('delete');
    var name = $(this).data('name');

    $modalDelete.find('[id=genero_id]').val(genero_id);
    $modalDelete.find('[id=name]').html(name);

    $modalDelete.modal('show');
}

function destroyWarrant() {
    event.preventDefault();
    // Obtener la URL
    var deleteUrl = $formDelete.data('url');
    $.ajax({
        url: deleteUrl,
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
                    "timeOut": "3000",
                    "extendedTimeOut": "1000",
                    "showEasing": "swing",
                    "hideEasing": "linear",
                    "showMethod": "fadeIn",
                    "hideMethod": "fadeOut"
                });
            $modalDelete.modal('hide');
            setTimeout( function () {
                location.reload();
            }, 3000 )
        },
        error: function (data) {
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
                        "timeOut": "3000",
                        "extendedTimeOut": "1000",
                        "showEasing": "swing",
                        "hideEasing": "linear",
                        "showMethod": "fadeIn",
                        "hideMethod": "fadeOut"
                    });
            }


        },
    });
}