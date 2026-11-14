$(document).ready(function () {
    $permissions = JSON.parse($('#permissions').val());
    $('#dynamic-table').DataTable( {
        ajax: {
            url: "/dashboard/all/suppliers",
            dataSrc: 'data'
        },
        bAutoWidth: false,
        drawCallback: function () { $(this.api().table().container()).find('.dataTables_empty').addClass('text-left text-wrap'); },
        initComplete: function () { $(this.api().table().container()).find('.dataTables_info').addClass('text-wrap'); },
        dom: '<"next-list-toolbar"<"next-list-search-row"<"next-list-search-control"f><"next-list-length"l>>><"next-list-summary text-wrap"i><"next-list-content"<"table-responsive"t>><"next-list-pagination"p>',
        columnDefs: [{targets: [0, 2, 7, 8], className: 'text-center'}, {targets: 8, orderable: false, searchable: false}],
        "aoColumns": [
            { data: 'code' },
            { data: 'business_name' },
            { data: 'RUC' },
            { data: 'address' },
            { data: 'phone' },
            { data: 'email' },
            {
                data: null,
                title: 'Cuentas',
                wrap: true,
                render: function (item) {
                    var text = '';
                    if ($.inArray('list_supplierAccount', $permissions) !== -1) {
                        if (item.accounts && item.accounts.length > 0) {
                            item.accounts.forEach(function (account) {
                                text += '<span class="badge badge-light">' + supplierDisplay(account.number_account || "N\u00famero no registrado") + '</span>';
                            });
                        } else {
                            text = '<span class="badge badge-light">Sin cuentas</span>';
                        }
                    }
                    return text;
                }
            },
            { data: null, defaultContent: '<span class="badge badge-success">Activo</span>' },
            { data: null,
                title: 'Acciones',
                wrap: true,
                "render": function (item)
                {
                    var text = '';
                    if ( $.inArray('update_supplier', $permissions) !== -1 ) {
                        text = text +'<a href="'+document.location.origin+ '/dashboard/editar/proveedor/'+item.id+
                            '" class="dropdown-item"><i class="fa fa-pen" aria-hidden="true"></i> Editar</a>';
                    }
                    if ( $.inArray('destroy_supplier', $permissions) !== -1 ) {
                        text = text + ' <button data-delete="'+item.id+'" data-company="'+supplierDisplay(item.business_name)+'" '+
                            ' type="button" class="dropdown-item text-danger"><i class="fa fa-trash" aria-hidden="true"></i> Eliminar</button>';
                    }
                    if ( $.inArray('list_supplierAccount', $permissions) !== -1 ) {
                        text = text + '<a href="'+document.location.origin+ '/dashboard/registrar/cuentas/proveedores/'+item.id+'" class="dropdown-item"><i class="fas fa-money-bill" aria-hidden="true"></i> Cuentas</a>  ';
                    }
                    return text ? '<div class="dropdown next-row-actions"><button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle next-row-actions-trigger" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false" aria-label="Acciones del proveedor"><i class="fas fa-ellipsis-h" aria-hidden="true"></i></button><div class="dropdown-menu dropdown-menu-right next-row-actions-menu">' + text + '</div></div>' : '';

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
            "emptyTable": "No hay proveedores activos",
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
    $formDelete.on('submit', destroySupplier);
    $modalDelete = $('#modalDelete');
    $(document).on('click', '[data-delete]', openModalDelete);
    $('#btn-download').on('click', downloadExcelSupplier);
});

var $formDelete;
var $modalDelete;
var $permissions;
function downloadExcelSupplier() {

    $.confirm({
        icon: 'fas fa-file-excel',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'green',
        title: 'Descargar reporte de proveedores ',
        content: 'Se descargará la lista de proveedores completa incluyendo los proveedores eliminados',
        buttons: {
            confirm: {
                text: 'DESCARGAR',
                action: function (e) {
                    $.alert('Descargando archivo ...');

                    var url = "/dashboard/download/supplierexcel";

                    window.location = url;

                },
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Exportación cancelada.");
                },
            },
        },
    });
}
function openModalDelete() {
    var supplier_id = $(this).data('delete');
    var company = $(this).data('company');

    $modalDelete.find('[id=supplier_id]').val(supplier_id);
    $modalDelete.find('[id=company]').html(company);

    $modalDelete.modal('show');
}

function destroySupplier() {
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
                    "timeOut": "2000",
                    "extendedTimeOut": "1000",
                    "showEasing": "swing",
                    "hideEasing": "linear",
                    "showMethod": "fadeIn",
                    "hideMethod": "fadeOut"
                });
            $modalDelete.modal('hide');
            setTimeout( function () {
                location.reload();
            }, 2000 )
        },
        error: function (data) {
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
                for (var property in data.responseJSON.errors) {
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
                            "timeOut": "2000",
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
function supplierDisplay(value) { return $('<div>').text(value == null ? '' : value).html().replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }
