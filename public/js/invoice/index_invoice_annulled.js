let $entriesComplete=[];
let $entriesJson=[];
let $materialsComplete=[];
let $locationsComplete=[];
let $items=[];

/*
function format ( d ) {
    var mensaje = "";
    var detalles = d.details;
    console.log(detalles);
    for ( var i=0; i<detalles.length; i++ )
    {
        var state = ( d.details[i].isComplete === 1 ) ? 'Completa' : 'Faltante';
        mensaje = mensaje +
            'Material: '+d.details[i].material_description+'<br>';
    }
    return 'DETALLES DE ENTRADA'+'<br>'+
        mensaje ;
}
*/

$(document).ready(function () {
    $('#sandbox-container .input-daterange').datepicker({
        todayBtn: "linked",
        clearBtn: true,
        language: "es",
        multidate: false,
        autoclose: true
    });
    $permissions = JSON.parse($('#permissions').val());
    //console.log($permissions);
    var table = $('#dynamic-table').DataTable( {
        ajax: {
            url: "/dashboard/get/json/invoices/purchase/annulled",
            dataSrc: 'data'
        },
        bAutoWidth: false,
        "aoColumns": [
            { data: null,
                title: 'Fecha de Factura',
                wrap: true,
                "render": function (item)
                {
                    var date_final = '';
                    if ( item.date_entry == null )
                    {
                        date_final = item.date_invoice;
                    } else {
                        date_final = item.date_entry;
                    }

                    return '<p> '+ moment(date_final).format('DD/MM/YYYY') +'</p>'
                }
            },
            { data: null,
                title: 'OC/OS',
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
                title: 'Tipo de Orden',
                wrap: true,
                "render": function (item)
                {
                    if ( item.code == null )
                    {
                        if ( item.type_order == null )
                        {
                            return '<p> Por compra </p>';
                        } else {
                            if ( item.type_order == 'purchase' )
                                return '<p> Por compra </p>';
                            else
                                return '<p> Por servicio </p>';
                        }

                    }
                    else{
                        return '<p> Por servicio </p>';
                    }

                }
            },
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
                title: 'Diferido',
                wrap: true,
                "render": function (item)
                {
                    if ( item.deferred_invoice === 'off' )
                        return '<span class="badge bg-success">NO</span>';
                    else
                        return '<span class="badge bg-warning">SI</span>';
                }
            },
            { data: null,
                title: 'Categoría',
                wrap: true,
                "render": function (item)
                {
                    if ( item.category_invoice_id != null )
                        return '<span class="badge bg-success">'+ item.category_invoice.name +'</span>';
                    else
                        return '<span class="badge bg-warning">No tiene</span>';
                }
            },
            { data: null,
                title: 'Subtotal',
                wrap: true,
                "render": function (item)
                {
                    if ( item.code == null )
                    {
                        return item.currency_invoice+' '+ item.sub_total;
                    } else {
                        return item.currency_order+' '+ (parseFloat(item.total) - parseFloat(item.igv));
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
                        return item.currency_invoice+' '+item.taxes;
                    } else {
                        return item.currency_order+' '+item.igv;
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
                        return item.currency_invoice+' '+item.total;
                    } else {
                        return item.currency_order+' '+item.total;
                    }

                }
            },
            { data: null,
                title: 'Imagen / PDF',
                wrap: true,
                "render": function (item)
                {
                    if (item.code == null){
                        var id = item.image;
                        if ( id != null ){
                            var string = id.substr(id.length - 3);
                            if( string.toUpperCase() == 'PDF')
                            {
                                return ' <a target="_blank" href="'+document.location.origin+ '/images/entries/'+item.image+'" '+
                                    ' class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver PDF"><i class="fa fa-file-pdf"></i></a>';

                            } else {
                                return ' <button data-src="'+document.location.origin+ '/images/entries/'+item.image+'" data-image="'+item.id+'" '+
                                    ' class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver Imagen"><i class="fa fa-image"></i></button>';

                            }
                        } else {
                            return 'No tiene';
                        }

                        /*return ' <button data-src="'+document.location.origin+ '/images/entries/'+item.image+'" data-image="'+item.id+'" '+
                            ' class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver Imagen"><i class="fa fa-image"></i></button>';
*/
                    } else {
                        var id2 = item.image_invoice;
                        if ( id2 != null ) {
                            var string2 = id2.substr(id2.length - 3);
                            if( string2.toUpperCase() == 'PDF')
                            {
                                return ' <a target="_blank" href="'+document.location.origin+ '/images/orderServices/'+item.image+'" '+
                                    ' class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver PDF"><i class="fa fa-file-pdf"></i></a>';

                            } else {
                                return ' <button data-src="'+document.location.origin+ '/images/orderServices/'+item.image_invoice+'" data-image="'+item.id+'" '+
                                    ' class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver Imagen"><i class="fa fa-image"></i></button>';

                            }
                        } else {
                            return 'No tiene';
                        }

                        /*return ' <button data-src="'+document.location.origin+ '/images/orderServices/'+item.image_invoice+'" data-image="'+item.id+'" '+
                            ' class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver Imagen"><i class="fa fa-image"></i></button>';
*/
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
                    console.log(item.material_name);
                    if ( item.code == null ) {
                        if (!item.finance) {
                            text = text + '<button type="button" data-details="' + item.id + '" data-code="0" class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver detalles"><i class="fa fa-eye"></i> </button>';

                        } else {

                            text = text + '<button type="button" data-details="' + item.id + '" data-code="0" class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver detalles"><i class="fa fa-eye"></i> </button>';

                        }
                    } else {

                        text = text + '<button type="button" data-details="' + item.id + '" data-code="1" class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver detalles"><i class="fa fa-eye"></i> </button>';

                    }

                    return text; /*'<a href="'+document.location.origin+ '/dashboard/entrada/compra/editar/'+item.id+'" class="btn btn-outline-warning btn-sm"><i class="fa fa-pen"></i> </a>  <button data-delete="'+item.id+'" data-description="'+item.description+'" data-measure="'+item.measure+'" class="btn btn-outline-danger btn-sm"><i class="fa fa-trash"></i> </button>' */
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
    // Array to track the ids of the details displayed rows
    var detailRows = [];

    /*$('#dynamic-table tbody').on( 'click', 'tr td.details-control', function () {
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
*/
    // On each draw, loop over the `detailRows` array and show any child rows
    /*table.on( 'draw', function () {
        $.each( detailRows, function ( i, id ) {
            $('#'+id+' td.details-control').trigger( 'click' );
        } );
    } );*/

    /*$(document).on('click', '[data-column]', function (e) {
        //e.preventDefault();

        // Get the column API object
        var column = table.column( $(this).attr('data-column') );

        // Toggle the visibility
        column.visible( ! column.visible() );
    } );*/

    $modalAddItems = $('#modalAddItems');

    //$(document).on('click', '[data-delete]', deleteItem);

    $modalItems = $('#modalItems');

    $modalImage = $('#modalImage');

    $(document).on('click', '[data-details]', showDetails);

    $(document).on('click', '[data-image]', showImage);

    $(document).on('click', '[data-delete]', deleteItem);

    $(document).on('click', '[data-deleteOS]', deleteOS);

    // Extend dataTables search
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var min  = $('#start').val();
            var max  = $('#end').val();
            var createdAt = data[0]; // Our date column in the table
            var startDate   = moment(min, "DD/MM/YYYY");
            var endDate     = moment(max, "DD/MM/YYYY");
            var diffDate = moment(createdAt, "DD/MM/YYYY");

            if ( (min === "" || max === "") ||  (diffDate.isBetween(startDate, endDate, null, '[]')) )
            {
                console.log("Es true" + (diffDate.isBetween(startDate, endDate, null, '[]')) );
                console.log(min + " " + max + " " + createdAt + " " + startDate + " " + endDate + " " + diffDate + " " );
                return true;
            }
            console.log("Es false" + (diffDate.isBetween(startDate, endDate, null, '[]')) );
            console.log(min + " " + max + " " + createdAt + " " + startDate + " " + endDate + " " + diffDate);

            return false;

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

    $('body').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

});

let $modalItems;

let $modalImage;

let $formCreate;

let $modalAddItems;

let $caracteres = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

let $longitud = 20;

var $permissions;

function deleteItem() {
    var id = $(this).data('delete');
    var button = $(this);
    console.log(id);
    $.confirm({
        icon: 'fas fa-frown',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'red',
        title: '¿Está seguro de eliminar esta factura?',
        content: 'Se anulará la factura y se revertira en caja.',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                action: function (e) {
                    $.ajax({
                        url: '/dashboard/destroy/total/invoice/'+id,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            console.log(data);

                            $.alert(data.message);
                            setTimeout( function () {
                                location.reload();
                            }, 2000 )

                        },
                        error: function (data) {
                            if( data.responseJSON.message && !data.responseJSON.errors )
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
                                        "timeOut": "2000",
                                        "extendedTimeOut": "1000",
                                        "showEasing": "swing",
                                        "hideEasing": "linear",
                                        "showMethod": "fadeIn",
                                        "hideMethod": "fadeOut"
                                    });
                            }
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
                                        "timeOut": "2000",
                                        "extendedTimeOut": "1000",
                                        "showEasing": "swing",
                                        "hideEasing": "linear",
                                        "showMethod": "fadeIn",
                                        "hideMethod": "fadeOut"
                                    });
                            }


                        },
                    });
                },
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Anulación cancelada.");
                },
            },
        },
    });
}

function deleteOS() {
    var id = $(this).data('deleteos');
    var button = $(this);
    console.log(id);
    $.confirm({
        icon: 'fas fa-frown',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'red',
        title: '¿Está seguro de eliminar esta factura?',
        content: 'Se eliminará directamente de la base de datos',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                action: function (e) {
                    $.ajax({
                        url: '/dashboard/destroy/order/service/'+id,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            console.log(data);

                            $.alert(data.message);
                            setTimeout( function () {
                                location.reload();
                            }, 2000 )

                        },
                        error: function (data) {
                            if( data.responseJSON.message && !data.responseJSON.errors )
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
                                        "timeOut": "2000",
                                        "extendedTimeOut": "1000",
                                        "showEasing": "swing",
                                        "hideEasing": "linear",
                                        "showMethod": "fadeIn",
                                        "hideMethod": "fadeOut"
                                    });
                            }
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
                                        "timeOut": "2000",
                                        "extendedTimeOut": "1000",
                                        "showEasing": "swing",
                                        "hideEasing": "linear",
                                        "showMethod": "fadeIn",
                                        "hideMethod": "fadeOut"
                                    });
                            }


                        },
                    });
                },
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Anulación cancelada.");
                },
            },
        },
    });
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

