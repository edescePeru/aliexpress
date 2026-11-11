let $entriesComplete=[];
let $entriesJson=[];
let $materialsComplete=[];
let $locationsComplete=[];
let $items=[];
$(document).ready(function () {
    function checkDateRange(startDate, endDate) {
        var start = moment(startDate, 'DD/MM/YYYY');
        var end = moment(endDate, 'DD/MM/YYYY');
        var diffInMonths = end.diff(start, 'months');

        if (diffInMonths >= 6) {
            toastr.warning('El rango seleccionado es mayor a 6 meses, puede que demore en cargar los datos.', 'Advertencia', {
                "closeButton": true,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "3500",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut",
                onShown: function () {
                    $('.toast').css('color', 'black');
                  }
            });
        }
    }

    $('#sandbox-container .input-daterange').datepicker({
        todayBtn: "linked",
        clearBtn: true,
        language: "es",
        multidate: false,
        autoclose: true
    });

    $('#submitButton').click(function () {
        const $button = $(this);
        const $results = $('#element_loader');
        const startDate = $('#start').val();
        const endDate = $('#end').val();

        if ($button.prop('disabled')) {
            return;
        }
        if (!startDate || !endDate) {
            toastr.warning('Selecciona ambas fechas.', 'Reporte de entradas');
            return;
        }

        $button.prop('disabled', true);
        $results.attr('aria-busy', 'true').LoadingOverlay('show', {
            background: 'rgba(255, 255, 255, 0.94)',
            imageColor: '#495057',
            text: 'Buscando…',
            textColor: '#343a40'
        });

        $.ajax({
            type: 'POST',
            url: '/dashboard/get/json/entries/purchase/report',
            data: {
                '_token': $('input[name=_token]').val(),
                'start': startDate,
                'end': endDate
            },
            success: function (data) {
                renderDataTable(data.data);
            },
            error: function (xhr) {
                const response = xhr.responseJSON || {};
                toastr.error(response.error || response.message || 'No se pudo cargar el reporte.', 'Error');
            },
            complete: function () {
                $results.LoadingOverlay('hide', true).removeAttr('aria-busy');
                $button.prop('disabled', false);
            }
        });
    });

    $('#start, #end').change(function () {
        var startDate = $('#start').val();
        var endDate = $('#end').val();

        checkDateRange(startDate, endDate);
    });

    $modalAddItems = $('#modalAddItems');

    $modalItems = $('#modalItems');

    $modalImage = $('#modalImage');

    $(document).on('click', '[data-detail]', showItems);

    $(document).on('click', '[data-image]', showImage);

    $(document).on('click', '[data-delete]', cancelEntry);

    
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


function cancelEntry() {
    var entry_id = $(this).data('delete');

    $.confirm({
        icon: 'fas fa-frown',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'red',
        title: '¿Está seguro de eliminar esta entrada?',
        content: 'También se eliminarán los items ingresados',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                action: function (e) {
                    $.ajax({
                        url: '/dashboard/entry_purchase/destroy/'+entry_id,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            console.log(data);
                            $.alert("Entrada eliminada.");
                            setTimeout( function () {
                                location.reload();
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

function showItems() {
    $('#table-items').html('');
    $('#table-details').html('');
    var entry_id = $(this).data('detail');
    $.ajax({
        url: "/dashboard/get/json/items/"+entry_id,
        type: 'GET',
        dataType: 'json',
        success: function (json) {
            for (var i=0; i<json.details.length; i++)
            {
                renderTemplateItemDetail(json.details[i].code, json.details[i].material, json.details[i].ordered_quantity, json.details[i].unit_price);
            }
            for (var j=0; j<json.items.length; j++)
            {
                renderTemplateItemItems(json.items[j].id, json.items[j].material, json.items[j].code, json.items[j].length, json.items[j].width, json.items[j].weight, json.items[j].price, json.items[j].location, json.items[j].state);
            }

        }
    });
    $modalItems.modal('show');
}

function renderTemplateItemItems(id, material, code, length, width, weight, price, location, state) {
    var status = (state === 'good') ? '<span class="badge bg-success">En buen estado</span>' :
        (state === 'bad') ? '<span class="badge bg-secondary">En mal estado</span>' :
            'Indefinido';
    var clone = activateTemplate('#template-item');
    clone.querySelector("[data-i]").innerHTML = id;
    clone.querySelector("[data-material]").innerHTML = material;
    clone.querySelector("[data-code]").innerHTML = code;
    clone.querySelector("[data-length]").innerHTML = length;
    clone.querySelector("[data-width]").innerHTML = width;
    clone.querySelector("[data-weight]").innerHTML = weight;
    clone.querySelector("[data-price]").innerHTML = price;
    clone.querySelector("[data-location]").innerHTML = location;
    clone.querySelector("[data-state]").innerHTML = status;
    $('#table-items').append(clone);
}

function renderTemplateItemDetail(code, material, quantity, price) {
    var clone = activateTemplate('#template-detail');
    clone.querySelector("[data-code]").innerHTML = code;
    clone.querySelector("[data-material]").innerHTML = material;
    clone.querySelector("[data-quantity]").innerHTML = quantity;
    clone.querySelector("[data-price]").innerHTML = price;
    $('#table-details').append(clone);
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


function renderDataTable(data){
    if ($.fn.DataTable.isDataTable('#dynamic-table')) {
        $('#dynamic-table').DataTable().destroy();
    }

    $(document).ready(function () {
        $('#dynamic-table').DataTable( {
            data: data,
            AutoWidth: false,
            "aoColumns": [
                { data: 'referral_guide' },
                { data: 'purchase_order' },
                { data: 'invoice' },
                { data: 'entry_type' },
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
                    title: 'Fecha',
                    wrap: true,
                    "render": function (item)
                    {
                        return '<p> '+ moment(item.date_entry).format('DD/MM/YYYY') +'</p>'
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
                    title: 'Imagen / PDF',
                    wrap: true,
                    "render": function (item)
                    {
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

                    }
                },
                { data: 'total' },
                { data: 'currency_invoice' },
                { data: null,
                    title: 'Acciones',
                    wrap: true,
                    "render": function (item)
                    {
                        var text = '';
                        text = text + ' <button data-detail="'+item.id+'" '+
                            ' class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver detalles"><i class="fa fa-eye"></i></button>';

                        return text; 
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
    });
}