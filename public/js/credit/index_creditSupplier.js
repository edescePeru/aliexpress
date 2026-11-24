let $entriesComplete=[];
let $entriesJson=[];
let $materialsComplete=[];
let $locationsComplete=[];
let $items=[];

$(document).ready(function () {
    /*$('#sandbox-container .input-daterange').datepicker({
        todayBtn: "linked",
        clearBtn: true,
        language: "es",
        multidate: false,
        autoclose: true
    });*/
    $('#sandbox-container2 .input-daterange').datepicker({
        todayBtn: "linked",
        clearBtn: true,
        language: "es",
        multidate: false,
        autoclose: true
    });
    $permissions = JSON.parse($('#permissions').val());
    //console.log($permissions);
    /*var table = $('#dynamic-table').DataTable( {
        ajax: {
            url: "/dashboard/get/only/invoices/purchase",
            dataSrc: 'data'
        },
        bAutoWidth: false,
        "aoColumns": [
            { data: null,
                title: 'Fecha de Factura',
                wrap: true,
                "render": function (item)
                {
                    return '<p> '+ moment(item.date_entry).format('DD/MM/YYYY') +'</p>'
                }
            },
            { data: null,
                title: 'Orden de compra',
                wrap: true,
                "render": function (item)
                {
                    if ( item.code == null ){
                        if ( item.purchase_order == null ) {
                            return '<p>Sin Orden</p>';
                        } else {
                            return '<p> '+ item.purchase_order +'</p>';
                        }

                    }
                    else {
                        return '<p> '+ item.code +'</p>';
                    }

                }
            },
            { data: 'invoice' },
            { data: null,
                title: 'Proveedor',
                wrap: true,
                "render": function (item)
                {
                    if ( item.supplier !== null )
                        return '<p> '+ item.supplier.business_name +'</p>';
                    else
                        return '<p> Sin proveedor </p>'
                }
            },
            { data: null,
                title: 'Subtotal',
                wrap: true,
                "render": function (item)
                {
                    if ( item.code == null )
                    {
                        return item.sub_total;
                    } else {
                        return parseFloat(item.total) - parseFloat(item.igv);
                    }

                }
            },
            { data: null,
                title: 'Impuestos',
                wrap: true,
                "render": function (item)
                {
                    if ( item.code == null )
                    {
                        return item.taxes;
                    } else {
                        return item.igv;
                    }

                }
            },
            { data: null,
                title: 'Total',
                wrap: true,
                "render": function (item)
                {
                    //console.log(item.code);
                    if ( item.code == null )
                    {
                        return item.total;
                    } else {
                        return item.total;
                    }

                }
            },
            { data: null,
                title: 'Imagen',
                wrap: true,
                "render": function (item)
                {
                    if (item.code == null){
                        return ' <button data-src="'+document.location.origin+ '/images/entries/'+item.image+'" data-image="'+item.id+'" '+
                            ' class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver Imagen"><i class="fa fa-image"></i></button>';

                    } else {
                        return ' <button data-src="'+document.location.origin+ '/images/orderServices/'+item.image_invoice+'" data-image="'+item.id+'" '+
                            ' class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver Imagen"><i class="fa fa-image"></i></button>';

                    }

                    //return '<img data-image src="'+document.location.origin+ '/images/entries/'+item.image+'" width="50px" height="50px">'
                }
            },
            { data: null,
                title: 'Acciones',
                wrap: true,
                "render": function (item)
                {
                    var text = '';

                    text = '<button type="button" data-add="' + item.id + '" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Agregar a créditos"><i class="fa fa-plus"></i> </button>';

                    return text; /!*'<a href="'+document.location.origin+ '/dashboard/entrada/compra/editar/'+item.id+'" class="btn btn-outline-warning btn-sm"><i class="fa fa-pen"></i> </a>  <button data-delete="'+item.id+'" data-description="'+item.description+'" data-measure="'+item.measure+'" class="btn btn-outline-danger btn-sm"><i class="fa fa-trash"></i> </button>' *!/
                }
            },

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
        },

    } );*/

    var table2 = $('#dynamic-table2').DataTable( {
        ajax: {
            url: "/dashboard/get/only/credits/supplier",
            dataSrc: function (json) { $('#registered-list-feedback').text(json.data.length ? '' : 'No hay créditos registrados en esta empresa.').toggleClass('d-none', !!json.data.length).removeClass('text-danger'); return json.data; }, error: function (xhr) { $('#registered-list-feedback').text('No se pudo consultar el listado ('+xhr.status+').').removeClass('d-none').addClass('text-danger'); }
        },
        bAutoWidth: false,
        dom: '<"next-list-toolbar d-flex justify-content-between flex-wrap"lf>rt<"next-list-toolbar d-flex justify-content-between flex-wrap"ip>',
        columnDefs: [{targets:[3,4],className:'text-right'},{targets:[1,2,5,6,7,8,10,12,13,14,15],className:'text-center'},{targets:[0,9,11],className:'text-left'}],
        "aoColumns": [
            { data: null,
                title: 'Proveedor',
                wrap: true,
                "render": function (item)
                {
                    if ( item.supplier !== null )
                        return '<p> '+ item.supplier.business_name +'</p>';
                    else
                        return '<p> Sin proveedor </p>'
                }
            },
            { data: null,
                title: 'Factura',
                wrap: true,
                "render": function (item)
                {
                    if ( item.invoice != null ) {
                        return '<p>'+ item.invoice +'</p>';
                    } else {
                        return '<p> Aún no tiene factura</p>';
                    }

                }
            },
            { data: null,
                title: '# O.C/O.S',
                wrap: true,
                "render": function (item)
                {
                    if ( item.purchase == null ) {
                        return $('<span>').text(item.service ? item.service.code : (item.code_order || 'Sin orden')).html();
                    } else {
                        return '<p> '+ item.purchase.code +'</p>';
                    }

                }
            },
            { data: null,
                title: 'Importe S/.',
                wrap: true,
                "render": function (item)
                {
                    if ( item.total_soles == null )
                    {
                        return '';
                    } else {
                        return parseFloat(item.total_soles);
                    }

                }
            },
            { data: null,
                title: 'Importe $',
                wrap: true,
                "render": function (item)
                {
                    if ( item.total_dollars == null )
                    {
                        return '';
                    } else {
                        return parseFloat(item.total_dollars);
                    }

                }
            },
            { data: null,
                title: 'Fecha Emisión',
                wrap: true,
                "render": function (item)
                {
                    if ( item.date_issue != null )
                    {
                        return '<p> '+ moment(item.date_issue).format('DD/MM/YYYY') +'</p>';
                    } else {
                        return '<p> Aún no tiene factura</p>'
                    }

                }
            },
            { data: null,
                title: 'Fecha Vence',
                wrap: true,
                "render": function (item)
                {
                    if ( item.date_expiration != null )
                    {
                        return '<p> '+ moment(item.date_expiration).format('DD/MM/YYYY') +'</p>';
                    } else {
                        return '<p> Aún no tiene factura</p>';
                    }

                }
            },
            { data: null,
                title: 'Plazo de pago',
                wrap: true,
                "render": function (item)
                {
                    if ( item.deadline == null ){
                        return '';
                    }
                    else {
                        return '<p> '+ item.deadline.days +'</p>';
                    }

                }
            },
            { data: null,
                title: 'Días para vencer',
                wrap: true,
                "render": function (item)
                {
                    //return item.days_to_expiration;
                    if ( item.days_to_expiration !== null )
                        return '<p> '+ item.days_to_expiration +'</p>';
                    else
                        return ''
                }
            },
            { data: null,
                title: 'Observación',
                wrap: true,
                "render": function (item)
                {
                    if ( item.observation != null )
                    {
                        return item.observation;
                    } else {
                        return '';
                    }

                }
            },
            { data: null,
                title: 'Estado de crédito',
                wrap: true,
                "render": function (item)
                {
                    if ( item.state_credit != null )
                    {
                        return (item.state_credit === 'outstanding') ? '<span class="badge badge-secondary">Pendiente</span>' :
                            (item.state_credit === 'by_expire') ? '<span class="badge badge-warning">Por expirar</span>' :
                                (item.state_credit === 'expired') ? '<span class="badge badge-danger">Expirado</span>' :
                                    '<span class="badge badge-success">Pagado</span>';
                    } else {
                        return 'No tiene';
                    }

                }
            },
            { data: null,
                title: 'Observación 2',
                wrap: true,
                "render": function (item)
                {
                    //console.log(item.code);
                    if ( item.observation_extra != null )
                    {
                        return item.observation_extra;
                    } else {
                        return '';
                    }

                }
            },
            { data: null,
                title: 'Imagen Factura',
                wrap: true,
                "render": function (item)
                {
                    if (item.code == null){
                        if ( item.image_invoice != null )
                        {
                            var id = item.image_invoice;
                            var string = id.substr(id.length - 3);
                            if( string == 'pdf' )
                            {
                                return ' <a target="_blank" href="'+document.location.origin+ '/images/entries/'+item.image_invoice+'" '+
                                    ' class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver PDF"><i class="fa fa-file-pdf"></i></a>';

                            } else {
                                return ' <button data-src="'+document.location.origin+ '/images/entries/'+item.image_invoice+'" data-image="'+item.id+'" '+
                                    ' class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver Imagen"><i class="fa fa-image"></i></button>';

                            }
                        } else {
                            return 'No tiene';
                        }
                    }

                    //return '<img data-image src="'+document.location.origin+ '/images/entries/'+item.image+'" width="50px" height="50px">'
                }
            },
            { data: null,
                title: 'Fecha Pago',
                wrap: true,
                "render": function (item)
                {
                    if ( item.date_paid != null )
                    {
                        return '<p> '+ moment(item.date_paid).format('DD/MM/YYYY') +'</p>';
                    } else {
                        return '<p> No tiene</p>';
                    }

                }
            },
            { data: null,
                title: 'Imagen Pago',
                wrap: true,
                "render": function (item)
                {
                    if (item.code == null){
                        if (item.image_paid && item.image_paid !== 'no_image.png' && item.image_paid !== 'no_image.jpg')
                        {
                            var id = item.image_paid;
                            var string = id.substr(id.length - 3);
                            if( string == 'pdf' )
                            {
                                return ' <a target="_blank" href="'+document.location.origin+ '/dashboard/creditos/'+item.id+'/comprobante'+'" '+
                                    ' class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver PDF"><i class="fa fa-file-pdf"></i></a>';

                            } else {
                                return ' <button data-src="'+document.location.origin+ '/dashboard/creditos/'+item.id+'/comprobante'+'" data-image="'+item.id+'" '+
                                    ' class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver Imagen"><i class="fa fa-image"></i></button>';

                            }
                        } else {
                            return '';
                        }

                    }

                    //return '<img data-image src="'+document.location.origin+ '/images/entries/'+item.image+'" width="50px" height="50px">'
                }
            },
            { data: null,
                title: 'Acciones',
                sortable:false,
                wrap: true,
                "render": function (item)
                {
                    var text = '';

                    if ( item.state_credit != 'paid_out' )
                    {
                        if ( $.inArray('edit_credit', $permissions) !== -1 ) {
                            text = text + '<button type="button" data-edit="' + item.id + '" class="dropdown-item" title="Editar crédito"><i class="fa fa-pen next-row-action-item-icon"></i><span>Editar fechas / observaciones</span> </button> ';
                        }
                        if ( $.inArray('pay_credit', $permissions) !== -1 ) {
                            text = text + '<button type="button" data-pay="' + item.id + '" class="dropdown-item" title="Marca manual de pago"><i class="fa fa-dollar-sign next-row-action-item-icon"></i><span>Marcar pagado</span> </button>';
                        }
                    } else {
                        if ( $.inArray('nopay_credit', $permissions) !== -1 ) {
                            text = text + '<button type="button" data-nopay="' + item.id + '" class="dropdown-item next-row-action-danger" title="Revertir marca de pago"><i class="fas fa-strikethrough next-row-action-item-icon"></i><span>Revertir marca de pago</span> </button>';
                        }
                    }

                    if (SupplierCreditUi.can('edit_credit', $permissions)) text += '<button type="button" class="dropdown-item" data-extend-credit="'+item.id+'">Ampliar vencimiento +7 días</button>';
                    var labels = {pending:'Pendiente',pending50:'CANC./PEND.50%',canceled:'Cancelado'};
                    var transitions = '';
                    Object.keys(labels).forEach(function (state) {
                        if (state !== item.state_pay && SupplierCreditUi.transition(item.state_pay, state, $permissions)) transitions += '<button type="button" class="dropdown-item" data-credit-state="'+state+'" data-current="'+item.state_pay+'" data-credit="'+item.id+'">'+labels[state]+'</button>';
                    });
                    if (transitions) text += '<div class="dropdown-divider"></div><h6 class="dropdown-header">Estado manual: '+(labels[item.state_pay] || 'Sin estado')+'</h6>'+transitions;
                    if (!text) return '<span class="text-muted">Sin acciones</span>';
                    return '<div class="dropdown next-row-actions"><button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle next-row-actions-trigger" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false" aria-label="Acciones del crédito"><i class="fas fa-ellipsis-h"></i></button><div class="dropdown-menu dropdown-menu-right next-row-actions-menu">'+text+'</div></div>'; /*'<a href="'+document.location.origin+ '/dashboard/entrada/compra/editar/'+item.id+'" class="btn btn-outline-warning btn-sm"><i class="fa fa-pen"></i> </a>  <button data-delete="'+item.id+'" data-description="'+item.description+'" data-measure="'+item.measure+'" class="btn btn-outline-danger btn-sm"><i class="fa fa-trash"></i> </button>' */
                }
            },

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
        },

    } );

    $(document).on('click', '[data-column]', function (e) {
        //e.preventDefault();

        // Get the column API object
        var column = table.column( $(this).attr('data-column') );

        // Toggle the visibility
        column.visible( ! column.visible() );
    } );

    $modalAddItems = $('#modalAddItems');

    //$(document).on('click', '[data-delete]', deleteItem);

    $modalItems = $('#modalItems');

    $modalEdit = $('#modalEdit');

    $modalPay = $('#modalPay');

    $modalImage = $('#modalImage');

    $formEdit = $('#formEdit');

    $formPay = $('#formPay');

    $("#btn-submit").on('click', updateCredit);

    $("#btn-pay").on('click', paidCredit);

    // Dates are independently editable; backend validates their ordering.
    // The legacy deadline-derived autofill could overwrite an explicit date or produce Invalid date.

    $(document).on('click', '[data-details]', showDetails);

    $(document).on('click', '[data-image]', showImage);

    $(document).on('click', '[data-add]', addCredit);

    $(document).on('click', '[data-edit]', editCredit);

    $(document).on('click', '[data-pay]', payCredit);

    $(document).on('click', '[data-nopay]', cancelPayCredit);

    // Extend dataTables search
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            //console.log(settings.nTable.id);
            if ( settings.nTable.id === 'dynamic-table' )
            {
                var min  = $('#start').val();
                //console.log(min);
                var max  = $('#end').val();
                //console.log(max);
                var createdAt = data[0]; // Our date column in the table
                var startDate   = moment(min, "DD/MM/YYYY");
                var endDate     = moment(max, "DD/MM/YYYY");
                var diffDate = moment(createdAt, "DD/MM/YYYY");

                if ( (min === "" || max === "") ||  (diffDate.isBetween(startDate, endDate, null, '[]')) )
                {
                    //console.log("Es true" + (diffDate.isBetween(startDate, endDate, null, '[]')) );
                    //console.log(min + " " + max + " " + createdAt + " " + startDate + " " + endDate + " " + diffDate + " " );
                    return true;
                }
                //console.log("Es false" + (diffDate.isBetween(startDate, endDate, null, '[]')) );
                //console.log(min + " " + max + " " + createdAt + " " + startDate + " " + endDate + " " + diffDate);

                return false;

            } else {
                if ( settings.nTable.id === 'dynamic-table2' )
                {
                    var min2  = $('#start2').val();
                    //console.log(min2);
                    var max2  = $('#end2').val();
                    //console.log(max2);
                    var createdAt2 = data[5]; // Our date column in the table
                    var startDate2   = moment(min2, "DD/MM/YYYY");
                    var endDate2     = moment(max2, "DD/MM/YYYY");
                    var diffDate2 = moment(createdAt2, "DD/MM/YYYY");

                    if ( (min2 === "" || max2 === "") ||  (diffDate2.isBetween(startDate2, endDate2, null, '[]')) )
                    {
                        //console.log("Es true" + (diffDate2.isBetween(startDate2, endDate2, null, '[]')) );
                        //console.log(min2 + " " + max2 + " " + createdAt2 + " " + startDate2 + " " + endDate2 + " " + diffDate2 + " " );
                        return true;
                    }
                    //console.log("Es false" + (diffDate2.isBetween(startDate2, endDate2, null, '[]')) );
                    //console.log(min2 + " " + max2 + " " + createdAt2 + " " + startDate2 + " " + endDate2 + " " + diffDate2);

                    return false;
                }

            }

            /*return !!((min === "" || max === "")
                ||
                (moment(createdAt).isSameOrAfter(min) && moment(createdAt).isSameOrBefore(max)));

*/
            /*return !!((min === "" || max === "") ||
                (diffDate.isBetween(startDate, endDate)));*/

        }
    );

    // Re-draw the table when the a date range filter changes
    $('.date-range-filter').change( function() {
        table.draw();
    } );

    $('.date-range-filter2').change( function() {
        table2.draw();
    } );

    $('body').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

});

let $modalItems;

let $modalImage;

let $modalEdit;

let $modalPay;

let $formEdit;

let $formPay;

let $modalAddItems;

let $caracteres = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

let $longitud = 20;

var $permissions;

function cancelPayCredit() {
    if (!SupplierCreditUi.allow('nopay_credit', $permissions)) return;
    var id_credit = $(this).attr('data-nopay');
    $.confirm({
        icon: 'fas fa-window-close',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'red',
        title: '¿Revertir la marca manual de pago?',
        content: 'No elimina abonos ni comprobantes. Se actualizará la página.',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                btnClass: 'btn-blue',
                action: function () {
                    $.ajax({
                        url: '/dashboard/cancel/pay/credit/'+id_credit,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            //console.log(data);
                            $.alert(data.message);
                            setTimeout( function () {
                                location.reload();
                            }, 2000 )
                        },
                        error: function (data) {
                            $.alert("Sucedió un error en el servidor. Intente nuevamente.");
                        },
                    });
                    //$.alert('Your name is ' + name);
                }
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Proceso cancelado.");
                },
            },
        }
    });
}

function paidCredit(event) {
    if (!SupplierCreditUi.allow('pay_credit', $permissions)) return;
    if (event) event.preventDefault();
    registeredClearErrors($('#formPay'));
    $("#btn-pay").attr("disabled", true);
    // Obtener la URL
    var paidUrl = $formPay.data('url');
    var formulario = $('#formPay')[0];
    var form = new FormData(formulario);
    $.ajax({
        url: paidUrl,
        method: 'POST',
        data: form,
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
            setTimeout( function () {
                $("#btn-pay").attr("disabled", false);
                $modalPay.modal('hide');
                location.reload();
            }, 2000 )
        },
        error: function (data) { registeredFormError($('#formPay'), data); $('#btn-pay').prop('disabled', false); }
    });
}

function payCredit() {
    if (!SupplierCreditUi.allow('pay_credit', $permissions)) return;
    registeredClearErrors($modalPay);
    $modalPay.find('button[id=btn-pay], button[id=btn-submit]').prop('disabled', true);
    var credit_id = $(this).data('pay');
    $.ajax({
        url: "/dashboard/get/credit/by/id/"+credit_id,
        type: 'GET',
        dataType: 'json',
        success: function (json) {
            $modalPay.find('button[id=btn-pay], button[id=btn-submit]').prop('disabled', false);
            var credit = json.credit;
            console.log(credit);
            //console.log(json[0].supplier);
            var estado = (credit.state_credit === 'outstanding') ? 'Pendiente' :
                (credit.state_credit === 'by_expire') ? 'Por expirar' :
                    (credit.state_credit === 'expired') ? 'Expirado' :
                        'Pagado';
            $modalPay.find('[id=credit_id]').val(credit.id);
            $modalPay.find('[id=days_deadline]').val((credit.deadline ? credit.deadline.days : ''));
            $modalPay.find('[id=supplier]').val((credit.supplier ? credit.supplier.business_name : 'Sin proveedor'));
            $modalPay.find('[id=invoice]').val(credit.invoice);
            $modalPay.find('[id=code_order]').val(credit.code_order);
            $modalPay.find('[id=total_soles]').val(credit.total_soles);
            $modalPay.find('[id=total_dollars]').val(credit.total_dollars);
            $modalPay.find('[id=total_dollars]').val(credit.total_dollars);
            $modalPay.find('[id=date_issue]').val((credit.date_issue ? moment(credit.date_issue).format('DD/MM/YYYY') : ''));
            $modalPay.find('[id=payment_deadline]').val((credit.deadline ? credit.deadline.description : ''));
            $modalPay.find('[id=date_expiration_2]').val((credit.date_expiration ? moment(credit.date_expiration).format('DD/MM/YYYY') : ''));
            $modalPay.find('[id=state_credit]').val(estado);
            $modalPay.find('[id=days_to_expiration]').val(credit.days_to_expiration);
            $modalPay.find('[id=observation]').val(credit.observation);

            $modalPay.find('[id=date_paid]').datepicker('setDate', (credit.date_paid ? moment(credit.date_paid).format('DD/MM/YYYY') : ''));
            $modalPay.find('[id=observation2]').val(credit.observation_extra);

            $('#date_picker_paid .date_picker_paid').datepicker({
                todayBtn: "linked",
                clearBtn: true,
                language: "es",
                multidate: false,
                autoclose: true,
                todayHighlight: true,
                defaultViewDate: moment().format('L')
            });
        },
        error: function (xhr) { registeredFormError($modalPay, xhr); }
    });
    $modalPay.modal('show');
}

function modifyDateIsuue() {
    console.log($(this).val());
    // TODO: Obtener la fecha nueva de emision,
    var deadline_days = parseInt($('#days_deadline').val());
    console.log(deadline_days);
    var date_isuue = moment($(this).val().split(' ')[0].split("/").reverse().join("-"));
    console.log(date_isuue);
    // TODO: Obtener la fecha nueva de expiracion moment("12/25/1995", "MM-DD-YYYY");
    var date_expiration = moment(date_isuue).add(deadline_days, 'd');

    console.log(date_expiration.format('DD/MM/YYYY'));
    // TODO: Modificar los dias restantes teniendo
    // TODO: en cuenta la fecha actual y de expiracion
    var missing_days = date_expiration.diff(moment(), 'days');
    console.log(missing_days);
    $modalEdit.find('[id=days_to_expiration]').val(missing_days);

    $modalEdit.find('[id=date_issue]').val(date_isuue.format('DD/MM/YYYY'));
    $modalEdit.find('[id=date_expiration_2]').val(date_expiration.format('DD/MM/YYYY'));
    $('#date_picker_issue .date_picker_issue').datepicker({
        todayBtn: "linked",
        clearBtn: true,
        language: "es",
        multidate: false,
        autoclose: true,
        todayHighlight: true,
        defaultViewDate: moment().format('L')
    });
    $('#date_expiration .date_picker_expiration').datepicker({
        todayBtn: "linked",
        clearBtn: true,
        language: "es",
        multidate: false,
        autoclose: true,
        todayHighlight: true,
        defaultViewDate: moment().format('L')
    });
    //console.log(date_isuue);

}

function updateCredit(event) {
    if (!SupplierCreditUi.allow('edit_credit', $permissions)) return;
    if (event) event.preventDefault();
    registeredClearErrors($('#formEdit'));
    $("#btn-submit").attr("disabled", true);
    // Obtener la URL
    var editUrl = $formEdit.data('url');
    var formulario = $('#formEdit')[0];
    var form = new FormData(formulario);
    $.ajax({
        url: editUrl,
        method: 'POST',
        data: form,
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
            setTimeout( function () {
                $("#btn-submit").attr("disabled", false);
                $modalEdit.modal('hide');
                location.reload();
            }, 2000 )
        },
        error: function (data) { registeredFormError($('#formEdit'), data); $('#btn-submit').prop('disabled', false); }
    });
}

function addCredit() {
    if (!SupplierCreditUi.allow('edit_credit', $permissions)) return;
    var id_entry = $(this).attr('data-add');
    $.confirm({
        icon: 'fas fa-check-double',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'green',
        title: '¿Está seguro de agregar al listado de créditos?',
        content: 'Se actualizará la página.',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                btnClass: 'btn-blue',
                action: function () {
                    $.ajax({
                        url: '/dashboard/add/invoice/credit/'+id_entry,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            //console.log(data);
                            $.alert(data.message);
                            setTimeout( function () {
                                location.reload();
                            }, 2000 )
                        },
                        error: function (data) {
                            $.alert("Sucedió un error en el servidor. Intente nuevamente.");
                        },
                    });
                    //$.alert('Your name is ' + name);
                }
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Proceso cancelado.");
                },
            },
        }
    });
}

function editCredit() {
    if (!SupplierCreditUi.allow('edit_credit', $permissions)) return;
    registeredClearErrors($modalEdit);
    $modalEdit.find('button[id=btn-pay], button[id=btn-submit]').prop('disabled', true);
    var credit_id = $(this).data('edit');
    $.ajax({
        url: "/dashboard/get/credit/by/id/"+credit_id,
        type: 'GET',
        dataType: 'json',
        success: function (json) {
            $modalEdit.find('button[id=btn-pay], button[id=btn-submit]').prop('disabled', false);
            var credit = json.credit;
            console.log(credit);
            //console.log(json[0].supplier);
            var estado = (credit.state_credit === 'outstanding') ? 'Pendiente' :
                (credit.state_credit === 'by_expire') ? 'Por expirar' :
                    (credit.state_credit === 'expired') ? 'Expirado' :
                    'Pagado';
            $modalEdit.find('[id=credit_id]').val(credit.id);
            $modalEdit.find('[id=days_deadline]').val((credit.deadline ? credit.deadline.days : ''));
            $modalEdit.find('[id=supplier]').val((credit.supplier ? credit.supplier.business_name : 'Sin proveedor'));
            $modalEdit.find('[id=invoice]').val(credit.invoice);
            $modalEdit.find('[id=code_order]').val(credit.code_order);
            $modalEdit.find('[id=total_soles]').val(credit.total_soles);
            $modalEdit.find('[id=total_dollars]').val(credit.total_dollars);
            $modalEdit.find('[id=total_dollars]').val(credit.total_dollars);
            $modalEdit.find('[id=date_issue]').datepicker('setDate', (credit.date_issue ? moment(credit.date_issue).format('DD/MM/YYYY') : ''));
            $modalEdit.find('[id=payment_deadline]').val((credit.deadline ? credit.deadline.description : ''));
            $modalEdit.find('[id=date_expiration_2]').datepicker('setDate', (credit.date_expiration ? moment(credit.date_expiration).format('DD/MM/YYYY') : ''));
            $modalEdit.find('[id=state_credit]').val(estado);
            $modalEdit.find('[id=days_to_expiration]').val(credit.days_to_expiration);
            $modalEdit.find('[id=observation]').val(credit.observation);

            $('#date_picker_issue .date_picker_issue').datepicker({
                todayBtn: "linked",
                clearBtn: true,
                language: "es",
                multidate: false,
                autoclose: true,
                todayHighlight: true,
                defaultViewDate: moment().format('L')
            });
            $('#date_expiration .date_picker_expiration').datepicker({
                todayBtn: "linked",
                clearBtn: true,
                language: "es",
                multidate: false,
                autoclose: true,
                todayHighlight: true,
                defaultViewDate: moment().format('L')
            });
        },
        error: function (xhr) { registeredFormError($modalEdit, xhr); }
    });
    $modalEdit.modal('show');
}

function showImage() {
    var path = $(this).data('src');
    $('#image-document').attr('src', path);
    $modalImage.modal('show');
}

function showDetails() {
    $('#body-materials').html('');
    $('#body-summary').html('');
    var entry_id = $(this).data('details');
    var code = $(this).data('code');
    if ( code == 0 )
    {
        $.ajax({
            url: "/dashboard/get/invoice/by/id/"+entry_id,
            type: 'GET',
            dataType: 'json',
            success: function (json) {
                //
                console.log(json[0].details);
                for (var i=0; i< json[0].details.length; i++)
                {
                    //console.log(json[0].details[i].material_description);
                    renderTemplateItemDetail(json[0].details[i].material_description, json[0].details[i].ordered_quantity, json[0].details[i].unit, json[0].details[i].unit_price, json[0].details[i].sub_total, json[0].details[i].taxes, json[0].details[i].total);
                    //$materials.push(json[i].material);
                }
                renderTemplateSummary(json[0].sub_total, json[0].taxes, json[0].total);

            }
        });
        $modalItems.modal('show');
    } else {
        $.ajax({
            url: "/dashboard/get/service/by/id/"+entry_id,
            type: 'GET',
            dataType: 'json',
            success: function (json) {
                //
                console.log(json[0].details);
                for (var i=0; i< json[0].details.length; i++)
                {
                    //console.log(json[0].details[i].material_description);
                    renderTemplateItemDetail(json[0].details[i].service, json[0].details[i].quantity, json[0].details[i].unit, json[0].details[i].price, (json[0].details[i].price*json[0].details[i].quantity)-json[0].details[i].igv, json[0].details[i].igv, json[0].details[i].price*json[0].details[i].quantity);
                    //$materials.push(json[i].material);
                }
                renderTemplateSummary(json[0].total-json[0].igv, json[0].igv, json[0].total);

            }
        });
        $modalItems.modal('show');
    }

}

function renderTemplateItemDetail(material, quantity, unit, price, subtotal, taxes, total) {
    var clone = activateTemplate('#template-item');
    clone.querySelector("[data-description]").innerHTML = material;
    clone.querySelector("[data-quantity]").innerHTML = quantity;
    clone.querySelector("[data-unit]").innerHTML = unit;
    clone.querySelector("[data-price]").innerHTML = price;
    clone.querySelector("[data-subtotal]").innerHTML = subtotal;
    clone.querySelector("[data-taxes]").innerHTML = taxes;
    clone.querySelector("[data-total]").innerHTML = total;
    $('#body-materials').append(clone);
}

function renderTemplateSummary(subtotal, taxes, total) {
    var clone = activateTemplate('#template-summary');
    clone.querySelector("[data-subtotal]").innerHTML = subtotal;
    clone.querySelector("[data-taxes]").innerHTML = taxes;
    clone.querySelector("[data-total]").innerHTML = total;
    $('#body-summary').append(clone);
}

function addItems() {
    if( $('#material_search').val().trim() === '' )
    {
        toastr.error('Debe elegir un material', 'Error',
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
        return;
    }

    if( $('#quantity').val().trim() === '' || $('#quantity').val()<0 )
    {
        toastr.error('Debe ingresar una cantidad', 'Error',
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
        return;
    }

    if( $('#price').val().trim() === '' || $('#price').val()<0 )
    {
        toastr.error('Debe ingresar un precio adecuado', 'Error',
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
        return;
    }

    let material_name = $('#material_search').val();
    $modalAddItems.find('[id=material_selected]').val(material_name);
    $modalAddItems.find('[id=material_selected]').prop('disabled', true);
    let material_quantity = $('#quantity').val();
    $modalAddItems.find('[id=quantity_selected]').val(material_quantity);
    $modalAddItems.find('[id=quantity_selected]').prop('disabled', true);
    let material_price = $('#price').val();
    $modalAddItems.find('[id=price_selected]').val(material_price);
    $modalAddItems.find('[id=price_selected]').prop('disabled', true);

    $('#body-items').html('');

    for (var i = 0; i<material_quantity; i++)
    {
        renderTemplateItem();
        $('.select2').select2();
    }

    $('.locations').typeahead({
            hint: true,
            highlight: true, /* Enable substring highlighting */
            minLength: 1 /* Specify minimum characters required for showing suggestions */
        },
        {
            limit: 12,
            source: substringMatcher($locations)
        });

    $modalAddItems.modal('show');

    /*$items.push({
        "productId" : sku,
        "qty" : qty,
        "price" : price
    });*/
}

function rand_code($caracteres, $longitud){
    var code = "";
    for (var x=0; x < $longitud; x++)
    {
        var rand = Math.floor(Math.random()*$caracteres.length);
        code += $caracteres.substr(rand, 1);
    }
    return code;
}

function deleteItem() {
    //console.log($(this).parent().parent().parent());
    $(this).parent().parent().parent().remove();
}

function renderTemplateMaterial(id, price, material, item, location, state) {
    var clone = activateTemplate('#materials-selected');
    clone.querySelector("[data-id]").innerHTML = id;
    clone.querySelector("[data-description]").innerHTML = material;
    clone.querySelector("[data-item]").innerHTML = item;
    clone.querySelector("[data-location]").innerHTML = location;
    clone.querySelector("[data-state]").innerHTML = state;
    clone.querySelector("[data-price]").innerHTML = price;
    $('#body-materials').append(clone);
}

function renderTemplateItem() {
    var clone = activateTemplate('#template-item');
    clone.querySelector("[data-series]").setAttribute('value', rand_code($caracteres, $longitud));
    $('#body-items').append(clone);
}

function activateTemplate(id) {
    var t = document.querySelector(id);
    return document.importNode(t.content, true);
}


function registeredClearErrors(form) {
    form.find('[data-credit-error]').addClass('d-none').text('');
    form.find('.invalid-feedback').remove(); form.find('.is-invalid').removeClass('is-invalid').removeAttr('aria-invalid');
}
function registeredFormError(form, xhr) {
    var body = xhr.responseJSON || {};
    form.find('[data-credit-error]').text(body.message || 'No se pudo completar la operación ('+xhr.status+').').removeClass('d-none');
    Object.keys(body.errors || {}).forEach(function (name) {
        form.find('[name]').filter(function () { return this.name === name; }).each(function () {
            $(this).addClass('is-invalid').attr('aria-invalid','true').after($('<div class="invalid-feedback">').text([].concat(body.errors[name]).join(' ')));
        });
    });
}
// Existing backend operations, confirmed through the same dialog plugin as reversal.
$(document).on('click','[data-extend-credit]',function () {
    if (!SupplierCreditUi.allow('edit_credit',$permissions)) return;
    registeredConfirm('Ampliar vencimiento +7 días','Se agregarán exactamente 7 días al vencimiento, si el estado del crédito lo permite.','/dashboard/add/days/credit/'+$(this).attr('data-extend-credit'));
});
$(document).on('click','[data-credit-state]',function () {
    var state=$(this).attr('data-credit-state'), current=$(this).attr('data-current');
    if (!SupplierCreditUi.transition(current,state,$permissions)) return;
    registeredConfirm('Cambiar estado manual','No registra ni elimina abonos. Se aplicará el estado seleccionado.','/dashboard/change/status/credit/'+$(this).attr('data-credit')+'/'+state);
});
function registeredConfirm(title, message, url) {
    $.confirm({title:title,content:message,theme:'modern',buttons:{
        confirm:{text:'Confirmar',btnClass:'btn-primary',action:function(){
            $.ajax({url:url,method:'POST',headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')},success:function(data){toastr.success(data.message);$('#dynamic-table2').DataTable().ajax.reload(null,false);},error:function(xhr){toastr.error((xhr.responseJSON || {}).message || 'No se pudo completar la operación ('+xhr.status+').');}});
        }},cancel:{text:'Cancelar'}
    }});
}
