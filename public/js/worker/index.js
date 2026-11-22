$(document).ready(function () {
    $permissions = JSON.parse($('#permissions').val());
    //console.log($permissions);
    var table = $('#dynamic-table').DataTable( {
        ajax: {
            url: "/dashboard/get/workers/",
            dataSrc: 'data'
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
                        text = item.contract;
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
                    if ( $.inArray('edit_worker', $permissions) !== -1 ) {
                        text = text + '<a href="'+document.location.origin+ '/dashboard/editar/colaborador/'+item.id+'" class="dropdown-item" data-toggle="tooltip" data-placement="top" title="Editar"><i class="fa fa-pen next-row-action-item-icon" aria-hidden="true"></i><span>Editar</span></a>  ';
                    }
                    if ( $.inArray('destroy_worker', $permissions) !== -1 ) {
                        text = text + '<button data-delete="'+item.id+'" data-nombre="'+item.first_name+' '+item.last_name+'" data-worker_id="'+item.id+'" class="dropdown-item next-row-action-danger" data-toggle="tooltip" data-placement="top" title="Deshabilitar"><i class="fas fa-window-close next-row-action-item-icon" aria-hidden="true"></i><span>Deshabilitar</span></button>  ';
                    }
                    if ( $.inArray('list_workerAccount', $permissions) !== -1 ) {
                        text = text + '<a href="'+document.location.origin+ '/dashboard/registrar/cuentas/trabajador/'+item.id+'" class="dropdown-item" data-toggle="tooltip" data-placement="top" title="Registrar cuentas"><i class="fas fa-money-bill next-row-action-item-icon" aria-hidden="true"></i><span>Registrar cuentas</span></a>  ';
                    }
                    if ( $.inArray('contract_worker', $permissions) !== -1 ) {
                        if ( item.have_contract == 0 )
                        {
                            text = text + '<a href="'+document.location.origin+ '/dashboard/crear/contrato/'+item.id+'" class="dropdown-item" data-toggle="tooltip" data-placement="top" title="Crear contrato"><i class="fas fa-file-signature next-row-action-item-icon" aria-hidden="true"></i><span>Crear contrato</span></a>  ';
                            //text = text + '<button data-createcontract="'+item.id+'" data-nombre="'+item.first_name+' '+item.last_name+'" data-worker_id="'+item.id+'" class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Crear contrato"><i class="fas fa-file-signature"></i> </button>  ';
                        } else {
                            text = text + '<a href="'+document.location.origin+ '/dashboard/renovar/contrato/'+item.id+'" class="dropdown-item" data-toggle="tooltip" data-placement="top" title="Renovar contrato"><i class="fas fa-sync-alt next-row-action-item-icon" aria-hidden="true"></i><span>Renovar contrato</span></a>  ';
                            //text = text + '<button data-renewcontract="'+item.id+'" data-nombre="'+item.first_name+' '+item.last_name+'" data-worker_id="'+item.id+'" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Renovar contrato"><i class="fas fa-sync-alt"></i> </button>  ';
                        }

                        if ( item.canFinishContract == 1 && item.canFinishContractEdit == 0 )
                        {
                            text = text + '<button data-termino_contrato="'+item.id+'" data-nombre="'+item.first_name+' '+item.last_name+'" data-worker_id="'+item.id+'" class="dropdown-item next-row-action-danger" data-toggle="tooltip" data-placement="top" title="Terminar contrato"><i class="fas fa-user-slash next-row-action-item-icon" aria-hidden="true"></i><span>Terminar contrato</span></button>  ';
                        }

                        if ( item.canFinishContract == 0 && item.canFinishContractEdit == 1 )
                        {
                            text = text + '<button data-termino_contrato_edit="'+item.id+'" data-nombre="'+item.first_name+' '+item.last_name+'" data-worker_id="'+item.id+'" class="dropdown-item" data-toggle="tooltip" data-placement="top" title="Editar Terminar contrato"><i class="fas fa-user-slash next-row-action-item-icon" aria-hidden="true"></i><span>Editar Terminar contrato</span></button>  ';
                            text = text + '<button data-termino_contrato_delete="'+item.id+'" data-nombre="'+item.first_name+' '+item.last_name+'" data-worker_id="'+item.id+'" class="dropdown-item next-row-action-danger" data-toggle="tooltip" data-placement="top" title="Eliminar Terminar contrato"><i class="fas fa-user-slash next-row-action-item-icon" aria-hidden="true"></i><span>Eliminar Terminar contrato</span></button>  ';
                        }
                    }

                    if (!text) return '';
                    return '<div data-buttons><div class="dropdown next-row-actions"><button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle next-row-actions-trigger" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false" aria-label="Abrir acciones del colaborador"><i class="fas fa-ellipsis-h" aria-hidden="true"></i></button><div class="dropdown-menu dropdown-menu-right next-row-actions-menu">' + text + '</div></div></div>';
                }
            },

        ],
        "aaSorting": [],
        "columnDefs": [
            { "className": "text-center", "targets": [0, 1, 8, 9, 10, 12, 13, 14, 29] },
            { "className": "text-right", "targets": [15, 16, 17, 18, 19, 20] },
            {
                "visible": false,
                "targets": [ 4, 5, 6, 8, 9, 10, 11, 12, 13,14,15,16,17,18,19,20,21,22,23,24,25,27,28 ]
            }],

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

    $(document).on('click', '[data-delete]', destroyWorker);
    $(document).on('click', '[data-termino_contrato]', finishContractWorker);

    $(document).on('click', '[data-termino_contrato_edit]', finishContractWorkerEdit);
    $(document).on('click', '[data-termino_contrato_delete]', finishContractWorkerDelete);

    $modalFinishContract = $("#modalFinishContract");
    $formFinishContract = $('#formFinishContract');

    $modalFinishContractDelete = $("#modalFinishContractDelete");
    $formFinishContractDelete = $('#formFinishContractDelete');

    $("#btn-exportExcel").on('click', exportExcel);

    $("#btn-finish_contract").on('click', finishContract);
    $("#btn-finish_contract_delete").on('click', finishContractDelete);
});

var $formDelete;
var $modalDelete;
var $permissions;
var $formFinishContract;
var $formFinishContractDelete;

var $modalFinishContract;
var $modalFinishContractDelete;

function finishContractDelete() {
    event.preventDefault();
    // Obtener la URL
    $("#btn-finish_contract_delete").attr("disabled", true);
    var formulario = $('#formFinishContractDelete')[0];
    var form = new FormData(formulario);
    var createUrl = $formFinishContractDelete.data('url');
    $.ajax({
        url: createUrl,
        method: 'POST',
        data: form,
        processData:false,
        contentType:false,
        success: function (data) {
            console.log(data);
            $modalFinishContractDelete.modal('hide');
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
                $("#btn-finish_contract_delete").attr("disabled", false);
                location.reload();
            }, 1500 )
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

            $("#btn-finish_contract_delete").attr("disabled", false);
        },
    });
}

function finishContractWorkerDelete() {
    var worker_id = $(this).data("worker_id");
    var worker_nombre = $(this).data("nombre");

    $.get('/dashboard/get/data/finish/contract/worker/delete/'+worker_id, function(data) {
        // Esta función se ejecutará cuando la petición sea exitosa
        //console.log('Datos recibidos:', data);
        var contract_id = data.contract_id;
        var contract_name = data.contract_name;
        var finish_contract_id = data.finish_contract_id;

        $modalFinishContractDelete.find('[id=type]').val("e");
        $modalFinishContractDelete.find('[id=worker_id]').val(worker_id);
        $modalFinishContractDelete.find('[id=contract_id]').val(contract_id);
        $modalFinishContractDelete.find('[id=name]').html(worker_nombre);
        $modalFinishContractDelete.find('[id=contrato]').html(contract_name);
        $modalFinishContractDelete.find('[id=finish_contract_id]').val(finish_contract_id);

    }, 'json');

    $modalFinishContractDelete.modal("show");
}

function finishContract() {
    event.preventDefault();
    // Obtener la URL
    $("#btn-finish_contract").attr("disabled", true);
    var formulario = $('#formFinishContract')[0];
    var form = new FormData(formulario);
    var createUrl = $formFinishContract.data('url');
    $.ajax({
        url: createUrl,
        method: 'POST',
        data: form,
        processData:false,
        contentType:false,
        success: function (data) {
            console.log(data);
            $modalFinishContract.modal('hide');
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
                $("#btn-finish_contract").attr("disabled", false);
                location.reload();
            }, 1500 )
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

            $("#btn-finish_contract").attr("disabled", false);
        },
    });
}

function finishContractWorkerEdit() {
    var worker_id = $(this).data("worker_id");
    var worker_nombre = $(this).data("nombre");

    $.get('/dashboard/get/data/finish/contract/worker/edit/'+worker_id, function(data) {
        // Esta función se ejecutará cuando la petición sea exitosa
        //console.log('Datos recibidos:', data);
        var contract_id = data.contract_id;
        var contract_name = data.contract_name;
        var finishContract_date = data.date_finish;
        var finishContract_reason = data.reason;
        var finish_contract_id = data.finish_contract_id;

        $modalFinishContract.find('[id=type]').val("e");
        $modalFinishContract.find('[id=worker_id]').val(worker_id);
        $modalFinishContract.find('[id=contract_id]').val(contract_id);
        $modalFinishContract.find('[id=name]').html(worker_nombre);
        $modalFinishContract.find('[id=contrato]').html(contract_name);
        $modalFinishContract.find('[id=date_finish]').val(finishContract_date);
        $modalFinishContract.find('[id=reason]').val(finishContract_reason);
        $modalFinishContract.find('[id=finish_contract_id]').val(finish_contract_id);
        /*var fechaActual = moment().format('DD/MM/YYYY');
        $('#date_finish').val(fechaActual);*/
    }, 'json');

    $modalFinishContract.modal("show");
}

function finishContractWorker() {
    var worker_id = $(this).data("worker_id");
    var worker_nombre = $(this).data("nombre");

    $.get('/dashboard/get/data/finish/contract/worker/'+worker_id, function(data) {
        // Esta función se ejecutará cuando la petición sea exitosa
        //console.log('Datos recibidos:', data);
        var contract_id = data.contract_id;
        var contract_name = data.contract_name;
        $modalFinishContract.find('[id=worker_id]').val(worker_id);
        $modalFinishContract.find('[id=contract_id]').val(contract_id);
        $modalFinishContract.find('[id=name]').html(worker_nombre);
        $modalFinishContract.find('[id=contrato]').html(contract_name);
        $modalFinishContract.find('[id=type]').val("s");
        var fechaActual = moment().format('DD/MM/YYYY');
        $('#date_finish').val(fechaActual);
    }, 'json');

    $modalFinishContract.modal("show");
}

function exportExcel() {
    event.preventDefault();
    // Inicializar un array para almacenar los valores de data-key
    var checkedValues = [];

    // Seleccionar todos los checkboxes con la clase custom-control-input que están marcados
    $('.custom-control-input:checked').each(function() {
        // Obtener el valor de data-key y agregarlo al array
        checkedValues.push($(this).data('key'));
    });

    // Mostrar el array resultante en la consola (puedes hacer lo que quieras con el array)
    console.log(checkedValues);

    $.confirm({
        icon: 'fas fa-file-excel',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'green',
        title: '¿Está seguro de descargar los colaboradores?',
        content: 'Se descargarán con los datos seleccionados.',
        buttons: {
            confirm: {
                text: 'DESCARGAR',
                action: function (e) {
                    var query = {
                        filtros: checkedValues,
                    };

                    $.alert('Descargando archivo ...');

                    var url = "/dashboard/exportar/reporte/colaboradores/?" + $.param(query);

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

function destroyWorker() {
    event.preventDefault();
    var id_worker = $(this).data('delete');
    var button = $(this);
    var nombre = $(this).data('nombre');

    vdialog({
        type:'alert',// alert, success, error, confirm
        title: '¿Esta seguro de inhabilitar este colaborador?',
        content: nombre,
        okValue:'Aceptar',
        modal:true,
        cancelValue:'Cancelar',
        ok: function(){

            $.ajax({
                url: '/dashboard/destroy/worker/'+id_worker,
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
