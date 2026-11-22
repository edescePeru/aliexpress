$(document).ready(function () {
    $permissions = JSON.parse($('#permissions').val());

    var table = $('#dynamic-table').DataTable( {
        ajax: {
            url: "/dashboard/get/workers/enable/",
            dataSrc: function (response) {
                $('#worker-enable-error').prop('hidden', true).empty();
                return response.data;
            },
            error: function (xhr) {
                $('#worker-enable-error').prop('hidden', false).text(xhr.status === 403
                    ? 'No tienes permiso para consultar colaboradores deshabilitados.'
                    : 'No se pudo cargar el listado. Vuelve a intentar la consulta.');
                $('#dynamic-table_wrapper').hide();
            }
        },
        bAutoWidth: false,
        "aoColumns": [
            { data: 'id' },
            { data: 'dni' },
            { data: 'first_name' },
            { data: 'last_name' },
            { data: 'personal_address' },
            { data: 'phone' },
            { data: 'email' },
            { data: 'work_function' },
            { data: 'gender' },
            { data: 'birthplace' },
            { data: 'age' },
            { data: 'level_school' },
            { data: 'num_children' },
            { data: 'admission_date' },
            { data: 'termination_date' },
            /*{ data: 'daily_salary' },*/
            { data: null,
                title: 'Salario Diario',
                wrap: true,
                "render": function (item)
                {
                    var text = '';
                    if ( $.inArray('contract_worker', $permissions) !== -1 ) {
                        text = (parseFloat(item.daily_salary) + parseFloat(item.assign_family) ).toFixed(2);
                    }
                    return text ;
                }
            },
            /*{ data: 'monthly_salary' },*/
            { data: null,
                title: 'Salario Mensual',
                wrap: true,
                "render": function (item)
                {
                    var text = '';
                    if ( $.inArray('contract_worker', $permissions) !== -1 ) {
                        text = (parseFloat(item.monthly_salary)).toFixed(2);
                    }
                    return text ;
                }
            },
            /*{ data: 'pension' },*/
            { data: null,
                title: 'Pension Alimentos',
                wrap: true,
                "render": function (item)
                {
                    var text = '';
                    if ( $.inArray('contract_worker', $permissions) !== -1 ) {
                        text = item.pension+"%";
                    }
                    return text ;
                }
            },
            /*{ data: 'essalud' },*/
            { data: null,
                title: 'ESSALUD',
                wrap: true,
                "render": function (item)
                {
                    var text = '';
                    if ( $.inArray('contract_worker', $permissions) !== -1 ) {
                        text = (parseFloat(item.essalud)).toFixed(2);
                    }
                    return text ;
                }
            },
            /*{ data: 'assign_family' },*/
            { data: null,
                title: 'Asignación Familiar',
                wrap: true,
                "render": function (item)
                {
                    var text = '';
                    if ( $.inArray('contract_worker', $permissions) !== -1 ) {
                        text = (parseFloat(item.assign_family)).toFixed(2);
                    }
                    return text ;
                }
            },
            /*{ data: 'five_category' },*/
            { data: null,
                title: 'Quinta Categoría ',
                wrap: true,
                "render": function (item)
                {
                    var text = '';
                    if ( $.inArray('contract_worker', $permissions) !== -1 ) {
                        text = item.five_category;
                    }
                    return text ;
                }
            },
            /*{ data: 'contract' },*/
            { data: null,
                title: 'Contrato',
                wrap: true,
                "render": function (item)
                {
                    var text = '';
                    if ( $.inArray('contract_worker', $permissions) !== -1 ) {
                        var hasContract = item.have_contract === true || item.have_contract === 1 || item.have_contract === '1';
                        text = '<span class="badge ' + (hasContract ? 'badge-success' : 'badge-secondary') + '">' +
                            (hasContract ? 'Con contrato' : 'Sin contrato') + '</span>';
                        if (item.contract) text += '<span class="d-block text-muted">' + $('<div>').text(item.contract).html() + '</span>';
                    }
                    return text ;
                }
            },
            { data: 'civil_status' },
            /*{ data: 'pension_system' },*/
            { data: null,
                title: 'Sistema Pensión',
                wrap: true,
                "render": function (item)
                {
                    var text = '';
                    if ( $.inArray('contract_worker', $permissions) !== -1 ) {
                        text = item.pension_system;
                    }
                    return text ;
                }
            },
            { data: 'percentage_pension_system' },
            { data: 'observation' },
            { data: 'area_worker' },
            { data: 'profession' },
            /*{ data: 'reason_for_termination' },*/
            { data: null,
                title: 'Motivo de Cese',
                wrap: true,
                "render": function (item)
                {
                    var text = '';
                    if ( $.inArray('contract_worker', $permissions) !== -1 ) {
                        text = item.reason_for_termination;
                    }
                    return text ;
                }
            },
            { data: null,
                title: 'Acciones',
                wrap: true,
                "render": function (item)
                {
                    var text = '';

                    // The page/data/action routes enforce restore_worker, as before.
                        var name = $('<div>').text(item.first_name + ' ' + item.last_name).html().replace(/"/g, '&quot;');
                        text = '<button type="button" data-enable="'+item.id+'" data-nombre="'+name+'" data-worker_id="'+item.id+'" class="dropdown-item" title="Habilitar"><i class="fas fa-recycle next-row-action-item-icon" aria-hidden="true"></i><span>Habilitar</span></button>';
                    return '<div data-buttons><div class="dropdown next-row-actions"><button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle next-row-actions-trigger" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false" aria-label="Abrir acciones del colaborador"><i class="fas fa-ellipsis-h" aria-hidden="true"></i></button><div class="dropdown-menu dropdown-menu-right next-row-actions-menu">' + text + '</div></div></div>';
                }
            },

        ],
        "aaSorting": [],
        "columnDefs": [
            { "className": "text-center", "targets": [0, 1, 8, 9, 10, 12, 13, 14, 21, 29] },
            { "className": "text-right", "targets": [15, 16, 17, 18, 19, 20, 24] },
            {
                "visible": false,
                "targets": [ 4, 5, 6, 8, 9, 10, 11, 12, 13,14,15,16,17,18,19,20,22,23,24,25,27,28 ]
            }],

        select: {
            style: 'single'
        },
        language: {
            "processing": "Procesando...",
            "lengthMenu": "Mostrar _MENU_ registros",
            "zeroRecords": "No se encontraron resultados",
            "emptyTable": "No hay colaboradores deshabilitados en esta empresa",
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

    $('.next-column-visibility-menu').on('click', '[data-column]', function (e) {
        //e.preventDefault();

        // Get the column API object
        var column = table.column( $(this).attr('data-column') );

        // Toggle the visibility
        column.visible( ! column.visible() );
    } );
    $('.next-column-visibility-menu').on('keydown', function (event) {
        if (event.key === 'Escape') {
            event.stopPropagation();
            $('#worker-columns-trigger').dropdown('hide').trigger('focus');
        }
    });
    // Same floating-panel interaction as Material V2; keep delegated switches inside it.
    $('.next-column-visibility-menu').on('click', function (event) {
        event.stopPropagation();
    });
    $(document).on('hidden.bs.dropdown', '#dynamic-table .next-row-actions', function () {
        $(this).children('.next-row-actions-trigger').attr('aria-expanded', 'false');
    });
    // Shared row menus are portaled by the shell; restore focus to their owner.
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        var menu = event.target.closest('.next-row-actions-menu');
        var owner = menu && $(menu).data('nextRowActionsOwner');
        if (!owner || !$(owner).closest('#dynamic-table').length) return;
        event.preventDefault();
        event.stopPropagation();
        $(owner).children('.next-row-actions-trigger').dropdown('hide').trigger('focus');
    }, true);
    $('body').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    $(document).on('click', '[data-enable]', destroyWorker);
});

var $formDelete;
var $modalDelete;
var $permissions;

function destroyWorker() {
    event.preventDefault();
    var id_worker = $(this).data('enable');
    var button = $(this);
    var nombre = $(this).data('nombre');

    vdialog({
        type:'alert',// alert, success, error, confirm
        title: '¿Esta seguro de habilitar este colaborador?',
        content: nombre,
        okValue:'Aceptar',
        modal:true,
        cancelValue:'Cancelar',
        ok: function(){

            $.ajax({
                url: '/dashboard/enable/worker/'+id_worker,
                method: 'POST',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                processData:false,
                contentType:false,
                success: function (data) {
                    console.log(data);
                    vdialog.success(data.message);
                    setTimeout( function () {
                        location.reload();
                    }, 2000 )
                },
                error: function (data) {
                    toastr.error('Algo sucedió en el servidor.', 'Error',
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

                },
            });

        },
        cancel: function(){
            vdialog.alert('Colaborador no inhabilitado');

        }
    });
}