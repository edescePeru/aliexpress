$(document).ready(function () {
    $permissions = JSON.parse($('#permissions').val());

    let currentMaterialId = null;

    //console.log($permissions);

    $('.custom-control-input').change(function() {
        updateData();
    });


    // Variable para almacenar los nombres clave de los checkboxes activos
    var activeColumns = getActiveColumns();

    // Función para obtener y mostrar los datos iniciales
    function initData() {
        activeColumns = getActiveColumns();
        console.log(activeColumns);
        getDataMaterials(1, activeColumns);
    }

    // Función para obtener y mostrar los datos con los checkboxes actuales
    function updateData() {
        activeColumns = getActiveColumns();
        getDataMaterials(1, activeColumns);
    }

    // Función para obtener y mostrar los datos con los checkboxes activos y criterios de búsqueda
    function showDataSearch() {
        activeColumns = getActiveColumns();
        getDataMaterials(1, activeColumns);
    }

    // Evento al cargar la página
    initData();

    $("#btnBusquedaAvanzada").click(function(e){
        e.preventDefault();
        $(".busqueda-avanzada").slideToggle();
    });

    $(document).on('click', '[data-item]', showData);

    $("#btn-search").on('click', showDataSearch);

    $('body').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    $('#btn-export').on('click', exportExcel);

    $modalImage = $('#modalImage');

    $formDelete = $('#formDelete');
    $formDelete.on('submit', disableMaterial);
    $modalDelete = $('#modalDelete');
    $(document).on('click', '[data-delete]', openModalDisable);

    $(document).on('click', '[data-image]', showImage);

    $selectCategory = $('#category');

    $selectSubCategory = $('#subcategory');

    $selectType = $('#material_type');

    $selectSubtype = $('#sub_type');

    $selectCategory.change(function () {
        $selectSubCategory.empty();
        $selectType.val('0');
        $selectType.trigger('change');
        $selectSubtype.val('0');
        $selectSubtype.trigger('change');
        var category =  $selectCategory.val();
        $.get( "/dashboard/get/subcategories/"+category, function( data ) {
            $selectSubCategory.append($("<option>", {
                value: '',
                text: 'Ninguna'
            }));
            for ( var i=0; i<data.length; i++ )
            {
                $selectSubCategory.append($("<option>", {
                    value: data[i].id,
                    text: data[i].subcategory
                }));
            }
        });

    });

    $selectSubCategory.change(function () {
        let subcategory = $selectSubCategory.select2('data');
        let option = $selectSubCategory.find(':selected');

        console.log(option);
        if(subcategory[0].text == 'INOX' || subcategory[0].text == 'FENE') {
            $selectType.empty();
            var subcategoria =  subcategory[0].id;
            $.get( "/dashboard/get/types/"+subcategoria, function( data ) {
                $selectType.append($("<option>", {
                    value: '',
                    text: 'Ninguno'
                }));
                for ( var i=0; i<data.length; i++ )
                {
                    $selectType.append($("<option>", {
                        value: data[i].id,
                        text: data[i].type
                    }));
                }
            });
        } else {
            console.log(subcategory[0].text);
            $selectType.val('0');
            $selectType.trigger('change');
            $selectSubtype.val('0');
            $selectSubtype.trigger('change');
            $selectSubCategory.select2('close');
        }
    });

    $selectType.change(function () {
        $selectSubtype.empty();
        var type = $selectType.select2('data');
        console.log(type);
        if( type.length !== 0)
        {
            $.get( "/dashboard/get/subtypes/"+type[0].id, function( data ) {
                $selectSubtype.append($("<option>", {
                    value: '',
                    text: 'Ninguno'
                }));
                for ( var i=0; i<data.length; i++ )
                {
                    $selectSubtype.append($("<option>", {
                        value: data[i].id,
                        text: data[i].subtype
                    }));
                }
            });
        }


    });

    $(document).on('click', '[data-precioDirecto]', openModalPrecioDirecto);
   /* $(document).on('click', '[data-precioPorcentaje]', openModalPrecioPorcentaje);*/

    $modalPrecioDirecto = $('#modalPrecioDirecto');
    /*$modalPrecioPercentage = $('#modalPrecioPercentage');*/

    $formPrecioDirecto = $('#formPrecioDirecto');
    /*$formPrecioPorcentaje = $('#formPrecioPorcentaje');*/

    $('#btn-submit_priceList').on('click', setPriceList);
    /*$('#btn-submit_pricePercentage').on('click', setPricePercentage);*/


    $(document).on('click', '[data-separate]', openModalSeparate);

    $formSeparate = $('#formSeparate');
    $modalSeparate = $('#modalSeparate');

    $('#btn-submitSeparate').on('click', submitSeparate);


    $(document).on('click', '[data-assign_child]', openModalAssignChild);
    $formAssignChild = $('#formAssignChild');
    $modalAssignChild = $('#modalAssignChild');

    $('#btn-submitAssignChild').on('click', submitAssignChild);

    // Evento delegado para eliminar un hijo
    $(document).on('click', '.btn-remove-child', function() {
        var button = $(this);
        var material_id = button.data('material_id');
        var unpack_id = button.data('material_unpack_id');

        if (!confirm('¿Estás seguro de eliminar este hijo?')) return;

        $.ajax({
            url: '/dashboard/material-unpack/' + unpack_id,
            type: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content') // CSRF token
            },
            success: function(response) {
                // Recargar hijos después de eliminar
                $.get('/dashboard/material-unpack/' + material_id + '/childs', function(res) {
                    $('#body-childs').empty();
                    if (res.length > 0) {
                        $.each(res, function(index, item) {
                            var row = `
                            <tr>
                                <th scope="row">${index + 1}</th>
                                <td>${item.name}</td>
                                <td>
                                    <button 
                                        type="button" 
                                        class="btn btn-outline-danger btn-block btn-remove-child" 
                                        data-material_id="${material_id}" 
                                        data-material_unpack_id="${item.id}">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                            $('#body-childs').append(row);
                        });
                    }
                });
            },
            error: function() {
                alert('Hubo un error al intentar eliminar.');
            }
        });
    });

    $(document).on('click', '[data-show_vencimiento]', function() {
        var materialId = $(this).data('material');
        var description = $(this).data('description');

        $('#modalVencimientosLabel').text('Fechas de vencimiento - ' + description);

        $.ajax({
            url: '/dashboard/fechas/vencimientos/material/' + materialId,
            method: 'GET',
            success: function(response) {
                var vencimientosHtml = '';

                if(response.length > 0) {
                    $.each(response, function(index, vencimiento) {
                        var color = calcularColor(vencimiento.fecha_vencimiento);

                        vencimientosHtml += `
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge badge-${color} p-2 mr-2">&nbsp;</span> 
                                    ${vencimiento.fecha_vencimiento}
                                </div>
                                <button class="btn btn-danger btn-sm eliminar-vencimiento" data-store_material_vencimiento="${vencimiento.id}">
                                    Eliminar
                                </button>
                            </div>
                        `;
                    });
                } else {
                    vencimientosHtml = '<p class="text-center text-muted">No hay fechas de vencimiento registradas.</p>';
                }

                $('#vencimientos-content').html(vencimientosHtml);
                $('#modalVencimientos').modal('show');
            },
            error: function() {
                alert('Error al cargar las fechas de vencimiento.');
            }
        });
    });

    // Eliminar vencimiento
    $(document).on('click', '.eliminar-vencimiento', function() {
        var vencimientoId = $(this).data('store_material_vencimiento');

        $.confirm({
            title: '¿Eliminar fecha?',
            content: '¿Estás seguro de que deseas eliminar esta fecha de vencimiento?',
            buttons: {
                confirmar: {
                    text: 'Sí, eliminar',
                    btnClass: 'btn-red',
                    action: function() {
                        $.ajax({
                            url: '/dashboard/eliminar/fechas/vencimientos/material/' + vencimientoId,
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function() {
                                $.alert({
                                    title: 'Eliminado',
                                    content: 'La fecha fue eliminada exitosamente.',
                                    buttons: {
                                        ok: {
                                            action: function() {
                                                // Refrescamos el modal
                                                $('[data-show_vencimiento][data-material]').trigger('click');
                                            }
                                        }
                                    }
                                });
                            },
                            error: function() {
                                $.alert('Error al eliminar la fecha de vencimiento.');
                            }
                        });
                    }
                },
                cancelar: {
                    text: 'Cancelar',
                    btnClass: 'btn-default'
                }
            }
        });
    });

    // Abrir modal
    $(document).on('click', '[data-manage_presentations]', function () {
        currentMaterialId = $(this).data('material');
        const desc = $(this).data('description') || '';

        $('#mp-material-title').text(desc ? `— ${desc}` : '');
        $('#presentaciones-content').html('<div class="text-muted">Cargando...</div>');

        $('#modalPresentaciones').modal('show');
        loadPresentations(currentMaterialId);
    });

    // Recargar
    $(document).on('click', '[data-mp-refresh]', function () {
        if (!currentMaterialId) return;
        loadPresentations(currentMaterialId);
    });

    // Crear
    $(document).on('click', '[data-mp-create]', function () {
        if (!currentMaterialId) return;

        const quantity = $('[data-mp-new-quantity]').val();
        const price = $('[data-mp-new-price]').val();

        if (!quantity || !price) {
            return $.alert({ title: 'Validación', content: 'Completa cantidad y precio.' });
        }

        $.confirm({
            title: 'Confirmar',
            content: '¿Deseas crear esta presentación?',
            buttons: {
                cancelar: function () {},
                confirmar: {
                    btnClass: 'btn-success',
                    action: function () {
                        return $.post(`/dashboard/materials-presentations/material/${currentMaterialId}/presentations`, { quantity, price })
                            .done(res => {
                                const p = res.presentation;
                                const $list = $('#mp-list');

                                const rowHtml = buildRowHtml(p); // ✅ aquí

                                if ($list.find('[data-mp-row]').length === 0) $list.empty();

                                $list.prepend(rowHtml);

                                $('[data-mp-new-quantity]').val('');
                                $('[data-mp-new-price]').val('');

                                $.alert({ title: 'OK', content: res.message });
                            })
                            .fail(xhr => {
                                $.alert({
                                    title: 'Error',
                                    content: xhr.responseJSON?.message || 'No se pudo crear.'
                                });
                            });
                    }
                }
            }
        });
    });

    // Editar (habilita inputs)
    $(document).on('click', '[data-mp-edit]', function () {
        const $row = $(this).closest('[data-mp-row]');
        $row.find('[data-mp-quantity],[data-mp-price]').prop('disabled', false);

        // guardar valores originales para cancelar
        $row.data('origQuantity', $row.find('[data-mp-quantity]').val());
        $row.data('origPrice', $row.find('[data-mp-price]').val());

        $row.find('[data-mp-edit]').addClass('d-none');
        $row.find('[data-mp-save],[data-mp-cancel]').removeClass('d-none');
    });

    // Cancelar edición
    $(document).on('click', '[data-mp-cancel]', function () {
        const $row = $(this).closest('[data-mp-row]');
        $row.find('[data-mp-quantity]').val($row.data('origQuantity')).prop('disabled', true);
        $row.find('[data-mp-price]').val($row.data('origPrice')).prop('disabled', true);

        $row.find('[data-mp-edit]').removeClass('d-none');
        $row.find('[data-mp-save],[data-mp-cancel]').addClass('d-none');
    });

    // Guardar edición
    $(document).on('click', '[data-mp-save]', function () {
        const $row = $(this).closest('[data-mp-row]');
        const id = $row.data('id');

        const quantity = $row.find('[data-mp-quantity]').val();
        const price = $row.find('[data-mp-price]').val();

        if (!quantity || !price) {
            return $.alert({ title: 'Validación', content: 'Completa cantidad y precio.' });
        }

        $.confirm({
            title: 'Confirmar',
            content: '¿Guardar cambios en esta presentación?',
            buttons: {
                cancelar: function () {},
                confirmar: {
                    btnClass: 'btn-primary',
                    action: function () {
                        return $.ajax({
                            url: `/dashboard/materials-presentations/presentation/${id}`,
                            method: 'PUT',
                            data: { quantity, price }
                        })
                            .done(res => {
                                $row.find('[data-mp-quantity],[data-mp-price]').prop('disabled', true);

                                $row.find('[data-mp-edit]').removeClass('d-none');
                                $row.find('[data-mp-save],[data-mp-cancel]').addClass('d-none');

                                $.alert({ title: 'OK', content: res.message });
                            })
                            .fail(xhr => {
                                $.alert({
                                    title: 'Error',
                                    content: xhr.responseJSON?.message || 'No se pudo actualizar.'
                                });
                            });
                    }
                }
            }
        });
    });

    // Eliminar
    $(document).on('click', '[data-mp-toggle]', function () {
        const $row = $(this).closest('[data-mp-row]');
        const id = $row.data('id');
        const active = parseInt($row.attr('data-active'), 10) === 1;

        $.confirm({
            title: active ? 'Desactivar' : 'Activar',
            content: active
                ? '¿Seguro que deseas desactivar esta presentación? No aparecerá al vender, pero quedará para historial.'
                : '¿Seguro que deseas activar esta presentación? Volverá a estar disponible en ventas.',
            buttons: {
                cancelar: function () {},
                confirmar: {
                    btnClass: active ? 'btn-danger' : 'btn-success',
                    action: function () {
                        return $.ajax({
                            url: `/dashboard/materials-presentations/presentation/${id}/toggle`,
                            method: 'PATCH'
                        })
                            .done(res => {
                                // Lo más simple y seguro: recargar lista desde server
                                loadPresentations(currentMaterialId);
                                $.alert({ title: 'OK', content: res.message });
                            })
                            .fail(xhr => {
                                $.alert({
                                    title: 'Error',
                                    content: xhr.responseJSON?.message || 'No se pudo cambiar el estado.'
                                });
                            });
                    }
                }
            }
        });
    });

    $('#btn-resumen-stock').on('click', function (e) {
        e.preventDefault();

        var resumenHtml = $('#resumen-stock-html').html(); // leemos el HTML del partial incluido

        $.confirm({
            title: 'Resumen de stock por material',
            useBootstrap: true,
            columnClass: 'col-md-8',
            content: resumenHtml,  // aquí va el HTML ya listo
            buttons: {
                cerrar: {
                    text: 'Cerrar',
                    btnClass: 'btn-secondary'
                }
            }
        });
    });

    if ($('#hay-alertas').val() === '1') {
        var resumenHtml = $('#resumen-stock-html').html();

        $.confirm({
            title: 'Resumen de stock por material',
            useBootstrap: true,
            columnClass: 'col-md-8',
            content: resumenHtml,
            autoClose: 'cerrar|10000',
            buttons: {
                cerrar: {
                    text: 'Cerrar',
                    btnClass: 'btn-secondary'
                }
            }
        });
    }

    $(document).on('click', '[data-ver-inventario]', function () {
        const materialId = $(this).data('material-id');

        if (!materialId) {
            toastr.error('No se encontró el stock item.');
            return;
        }

        openInventoryLevelsModal(materialId);
    });
});

var $formAssignChild;
var $modalAssignChild;

var $formSeparate;

var $formDelete;
var $modalDelete;
var $permissions;
var $selectCategory;
var $selectSubCategory;
var $selectType;
var $selectSubtype;

var $modalPrecioDirecto;
var $modalPrecioPercentage;
var $formPrecioDirecto;
var $formPrecioPorcentaje;

function openInventoryLevelsModal(materialId) {
    const url = window.materialInventoryLevelsUrl.replace(':id', materialId);

    $.ajax({
        url: url,
        method: 'GET',
        beforeSend: function () {
            $('#tbody-modal-inventory-levels').html(`
                <tr>
                    <td colspan="8" class="text-center">Cargando...</td>
                </tr>
            `);
            $('#modalInventoryLevels').modal('show');
        },
        success: function (response) {
            fillInventoryLevelsModal(response);
        },
        error: function (xhr) {
            let message = 'No se pudo cargar el inventario.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            toastr.error(message);
        }
    });
}

function fillInventoryLevelsModal(response) {
    const levels = Array.isArray(response.inventory_levels) ? response.inventory_levels : [];
    const materialName = response.material && response.material.full_name
        ? response.material.full_name
        : 'Inventario';

    $('#modalInventoryLevelsLabel').text(`Inventario - ${materialName}`);

    renderInventoryLevelsModalRows(levels);
}


function escapeHtml(text) {
    return $('<div>').text(text).html();
}

function renderInventoryLevelsModalRows(levels) {
    const $tbody = $('#tbody-modal-inventory-levels');
    $tbody.empty();

    if (!levels.length) {
        $tbody.html(`
            <tr>
                <td colspan="10" class="text-center text-muted">
                    No hay inventory levels registrados.
                </td>
            </tr>
        `);
        return;
    }

    levels.forEach(function (level, index) {
        const stockLabel = [
            level.stock_item_name || '',
            level.variant_text || '',
            level.stock_item_sku ? `SKU: ${level.stock_item_sku}` : ''
        ].filter(Boolean).join('<br>');

        $tbody.append(`
            <tr>
                <td>
                    <input type="hidden" name="inventory_levels[${index}][id]" value="${escapeHtml(level.id || '')}">
                    <input type="hidden" name="inventory_levels[${index}][stock_item_id]" value="${escapeHtml(level.stock_item_id || '')}">
                    ${stockLabel}
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" value="${escapeHtml(level.warehouse_name || '')}" readonly>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" value="${escapeHtml(level.location_name || '')}" readonly>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm" value="${normalizeNumber(level.qty_on_hand)}" readonly>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm" value="${normalizeNumber(level.qty_reserved)}" readonly>
                </td>
                <td>
                    <input type="number"
                           class="form-control form-control-sm"
                           name="inventory_levels[${index}][min_alert]"
                           value="${normalizeNumber(level.min_alert)}"
                           min="0" step="0.01" readonly>
                </td>
                <td>
                    <input type="number"
                           class="form-control form-control-sm"
                           name="inventory_levels[${index}][max_alert]"
                           value="${normalizeNumber(level.max_alert)}"
                           min="0" step="0.01" readonly>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm" value="${normalizeNumber(level.average_cost)}" readonly>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm" value="${normalizeNumber(level.last_cost)}" readonly>
                </td>
            </tr>
        `);
    });
}

function normalizeNumber(value) {
    if (value === null || value === undefined || value === '') {
        return 0;
    }

    const parsed = parseFloat(value);
    return isNaN(parsed) ? 0 : parsed;
}

function tpl(id, data) {
    let html = $(id).html();
    Object.keys(data || {}).forEach(k => {
        const v = (data[k] ?? '').toString();
        html = html.replaceAll('{' + k + '}', v);
    });
    return html;
}

function moneyFormat(v) {
    // si quieres forzar 2 decimales visualmente:
    // return (parseFloat(v) || 0).toFixed(2);
    return v;
}

function renderModal(material, presentations) {
    $('#mp-material-title').text(material?.description ? `— ${material.description}` : '');
    $('#presentaciones-content').html(tpl('#tpl-mp-wrapper'));

    const $list = $('#mp-list');
    $list.empty();

    if (!presentations || !presentations.length) {
        $list.html('<div class="text-muted">No hay presentaciones registradas aún.</div>');
        return;
    }

    presentations.forEach(p => {
        $list.append(buildRowHtml(p));
    });
}

function loadPresentations(materialId) {
    return $.get(`/dashboard/materials-presentations/material/${materialId}/presentations`)
        .done(res => renderModal(res.material, res.presentations))
        .fail(xhr => {
            $.alert({
                title: 'Error',
                content: xhr.responseJSON?.message || 'No se pudo cargar las presentaciones.'
            });
        });
}

function buildRowHtml(p) {
    const isActive = (p.active === true || p.active === 1 || p.active === "1");

    return tpl('#tpl-mp-row', {
        id: p.id,
        quantity: p.quantity,
        price: moneyFormat(p.price),
        active: isActive ? 1 : 0,

        row_class: isActive ? '' : 'bg-light text-muted',
        badge_class: isActive ? 'badge-success' : 'badge-secondary',
        status_text: isActive ? 'ACTIVA' : 'INACTIVA',

        toggle_text: isActive ? 'Desactivar' : 'Activar',
        toggle_btn_class: isActive ? 'btn-outline-danger' : 'btn-outline-success',

        edit_disabled: isActive ? '' : 'disabled'
    });
}

function submitAssignChild() {
    var child_id = $('#material').val();
    var parent_id = $('#material_id').val();

    if (!child_id) {
        $.alert({
            title: 'Error',
            content: 'Debes seleccionar un material hijo.',
            type: 'red',
            typeAnimated: true
        });
        return;
    }

    $.post('/dashboard/material-unpack/store', {
        _token: $('meta[name="csrf-token"]').attr('content'),
        parent_material_id: parent_id,
        child_material_id: child_id
    }, function(response) {
        $.alert({
            title: 'Éxito',
            content: 'Material hijo asignado correctamente.',
            type: 'green',
            typeAnimated: true
        });

        // Vaciar el select (opcional)
        $('#material').val(null).trigger('change');

        // Volver a cargar hijos
        $.get('/dashboard/material-unpack/' + parent_id + '/childs', function(res) {
            $('#body-childs').empty();
            if (res.length > 0) {
                $.each(res, function(index, item) {
                    var row = `
                        <tr>
                            <th scope="row">${index + 1}</th>
                            <td>${item.name}</td>
                            <td>
                                <button 
                                    type="button" 
                                    class="btn btn-outline-danger btn-block btn-remove-child" 
                                    data-material_id="${parent_id}" 
                                    data-material_unpack_id="${item.id}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    $('#body-childs').append(row);
                });
            }
        });
    }).fail(function() {
        $.alert({
            title: 'Error',
            content: 'No se pudo asignar el material. Intenta nuevamente.',
            type: 'red',
            typeAnimated: true
        });
    });
}

function openModalAssignChild() {
    var material_id = $(this).data('material');
    var description = $(this).data('description');

    // Traer los hijos si hay
// Limpiar tabla
    $('#body-childs').empty();

    // Obtener hijos vía AJAX
    $.get('/dashboard/material-unpack/' + material_id + '/childs', function(response) {
        if (response.length > 0) {
            $.each(response, function(index, item) {
                var row = `
                    <tr>
                        <th scope="row">${index + 1}</th>
                        <td>${item.name}</td>
                        <td>
                            <button 
                                type="button" 
                                class="btn btn-outline-danger btn-block btn-remove-child" 
                                data-material_id="${material_id}" 
                                data-material_unpack_id="${item.id}">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                `;
                $('#body-childs').append(row);
            });
        }
    });

    $modalAssignChild.find('[id=material_id]').val(material_id);
    $modalAssignChild.find('[id=name_material]').html(description);
    $modalAssignChild.modal('show');
}

function submitSeparate() {
    event.preventDefault();
    $("#btn-submitSeparate").attr("disabled", true);
    // Obtener la URL
    var createUrl = $formSeparate.data('url');
    var form = new FormData($('#formSeparate')[0]);
    $.ajax({
        url: createUrl,
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
                $("#btn-submitSeparate").attr("disabled", false);
                $modalSeparate.modal('hide');
                var activeColumns = getActiveColumns();
                getDataMaterials(1, activeColumns);
            }, 1000 )
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
                        "timeOut": "4000",
                        "extendedTimeOut": "1000",
                        "showEasing": "swing",
                        "hideEasing": "linear",
                        "showMethod": "fadeIn",
                        "hideMethod": "fadeOut"
                    });
            }
            $("#btn-submitSeparate").attr("disabled", false);

        },
    });


}

function openModalSeparate() {
    var material_id = $(this).data('material');
    var description = $(this).data('description');
    var quantity = $(this).data('quantity');

    $modalSeparate.find('[id=material_id]').val(material_id);
    $modalSeparate.find('[id=name_material]').html(description);
    $modalSeparate.find('[id=packs_total]').val(quantity);
    $modalSeparate.find('[id=packs_separate]').val(0);

    // Limpiar y deshabilitar temporalmente el select
    let $select = $modalSeparate.find('#materialChild');
    $select.empty().append('<option value="">Cargando...</option>').prop('disabled', true);

    // Obtener los materiales hijos
    $.get('/dashboard/material-unpack/' + material_id + '/child-materials', function(res) {
        $select.empty().append('<option value="">Seleccione un material</option>');

        if (res.length > 0) {
            $.each(res, function(index, item) {
                $select.append('<option value="' + item.id + '">' + item.name + '</option>');
            });
        }

        $select.prop('disabled', false);
    });

    $modalSeparate.modal('show');
}

function setPricePercentage() {
    event.preventDefault();
    $("#btn-submit_pricePercentage").attr("disabled", true);
    // Obtener la URL
    var createUrl = $formPrecioPorcentaje.data('url');
    var form = new FormData($('#formPrecioPorcentaje')[0]);
    $.ajax({
        url: createUrl,
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
                $("#btn-submit_pricePercentage").attr("disabled", false);
                $modalPrecioPercentage.modal('hide');
                var activeColumns = getActiveColumns();
                console.log(activeColumns);
                getDataMaterials(1, activeColumns);
            }, 2000 )
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
                        "timeOut": "4000",
                        "extendedTimeOut": "1000",
                        "showEasing": "swing",
                        "hideEasing": "linear",
                        "showMethod": "fadeIn",
                        "hideMethod": "fadeOut"
                    });
            }
            $("#btn-submit_pricePercentage").attr("disabled", false);

        },
    });
}

function setPriceList(event) {
    event.preventDefault();

    const $btn = $("#btn-submit_priceList");
    $btn.attr("disabled", true);

    var createUrl = $formPrecioDirecto.data('url');
    var form = new FormData($('#formPrecioDirecto')[0]);

    $.ajax({
        url: createUrl,
        method: 'POST',
        data: form,
        processData: false,
        contentType: false,
        success: function (data) {
            toastr.success(data.message, 'Éxito', {
                closeButton: true,
                debug: false,
                newestOnTop: false,
                progressBar: true,
                positionClass: "toast-top-right",
                preventDuplicates: false,
                onclick: null,
                showDuration: "300",
                hideDuration: "1000",
                timeOut: "2000",
                extendedTimeOut: "1000",
                showEasing: "swing",
                hideEasing: "linear",
                showMethod: "fadeIn",
                hideMethod: "fadeOut"
            });

            setTimeout(function () {
                $btn.attr("disabled", false);
                $modalPrecioDirecto.modal('hide');

                var activeColumns = getActiveColumns();
                getDataMaterials(1, activeColumns);
            }, 1000);
        },
        error: function (xhr) {
            if (xhr.responseJSON && xhr.responseJSON.message) {
                toastr.error(xhr.responseJSON.message, 'Error', {
                    closeButton: true,
                    debug: false,
                    newestOnTop: false,
                    progressBar: true,
                    positionClass: "toast-top-right",
                    preventDuplicates: false,
                    onclick: null,
                    showDuration: "300",
                    hideDuration: "1000",
                    timeOut: "4000",
                    extendedTimeOut: "1000",
                    showEasing: "swing",
                    hideEasing: "linear",
                    showMethod: "fadeIn",
                    hideMethod: "fadeOut"
                });
            }

            if (xhr.responseJSON && xhr.responseJSON.errors) {
                for (var property in xhr.responseJSON.errors) {
                    toastr.error(xhr.responseJSON.errors[property], 'Error', {
                        closeButton: true,
                        debug: false,
                        newestOnTop: false,
                        progressBar: true,
                        positionClass: "toast-top-right",
                        preventDuplicates: false,
                        onclick: null,
                        showDuration: "300",
                        hideDuration: "1000",
                        timeOut: "4000",
                        extendedTimeOut: "1000",
                        showEasing: "swing",
                        hideEasing: "linear",
                        showMethod: "fadeIn",
                        hideMethod: "fadeOut"
                    });
                }
            }

            $btn.attr("disabled", false);
        }
    });
}

function openModalPrecioDirecto() {
    var material_id = $(this).data('material');
    var material_name = $(this).data('description');

    $modalPrecioDirecto.find('#material_id').val(material_id);
    $modalPrecioDirecto.find('#descriptionMaterialPrice').html(material_name);
    $modalPrecioDirecto.find('#price_mode').val('legacy');
    $modalPrecioDirecto.find('#material_priceBase').val('0.00');
    $modalPrecioDirecto.find('#material_priceList').val('0.00');

    $('#legacy-price-container').show();
    $('#stock-items-price-container').hide();
    $('#tbody-modal-price-list').html(`
        <tr>
            <td colspan="5" class="text-center text-muted">Cargando...</td>
        </tr>
    `);

    $modalPrecioDirecto.modal('show');

    $.ajax({
        url: '/dashboard/get/price/list/material/' + material_id,
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            fillModalPrecioDirecto(data);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error(textStatus, errorThrown);

            let message = 'No se pudo cargar la información de precios.';

            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                message = jqXHR.responseJSON.message;
            }

            toastr.error(message, 'Error', {
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-top-right',
                timeOut: '2000'
            });
        }
    });
}

function fillModalPrecioDirecto(data) {
    let priceBase = parseFloat(data.priceBase || data.price_base || 0);
    $modalPrecioDirecto.find('#material_priceBase').val(priceBase.toFixed(2));

    if (data.mode === 'stock_items') {
        $modalPrecioDirecto.find('#price_mode').val('stock_items');
        $('#legacy-price-container').hide();
        $('#stock-items-price-container').show();

        renderStockItemsPrices(data.stockItems || data.stock_items || []);
        return;
    }

    $modalPrecioDirecto.find('#price_mode').val('legacy');
    $('#legacy-price-container').show();
    $('#stock-items-price-container').hide();

    let priceList = parseFloat(data.priceList || data.price_list || 0);
    $modalPrecioDirecto.find('#material_priceList').val(priceList.toFixed(2));
}

function renderStockItemsPrices(items) {
    const $tbody = $('#tbody-modal-price-list');
    $tbody.empty();

    if (!items.length) {
        $tbody.html(`
            <tr>
                <td colspan="5" class="text-center text-muted">
                    No hay stock items registrados.
                </td>
            </tr>
        `);
        return;
    }

    items.forEach(function(item, index) {
        let descripcion = item.display_name || '';
        let variante = item.variant_text || '';
        let sku = item.sku || '';
        let barcode = item.barcode || '';
        let priceList = parseFloat(item.price_list || 0).toFixed(2);

        $tbody.append(`
            <tr>
                <td>
                    <input type="hidden" name="stock_items[${index}][stock_item_id]" value="${escapeHtml2(item.stock_item_id || '')}">
                    <input type="hidden" name="stock_items[${index}][price_list_item_id]" value="${escapeHtml2(item.price_list_item_id || '')}">
                    ${escapeHtml2(descripcion)}
                </td>
                <td>${escapeHtml2(variante)}</td>
                <td>${escapeHtml2(sku)}</td>
                <td>${escapeHtml2(barcode)}</td>
                <td>
                    <input
                        type="number"
                        class="form-control form-control-sm"
                        name="stock_items[${index}][price_list]"
                        value="${priceList}"
                        min="0"
                        step="0.01"
                        required
                    >
                </td>
            </tr>
        `);
    });
}

function escapeHtml2(text) {
    if (text === null || text === undefined) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function openModalPrecioPorcentaje() {
    var material_id = $(this).data('material');

    var pricePercentage = 0;

    $.get('/dashboard/get/price/percentage/material/'+material_id, function(data) {
        pricePercentage = data.pricePercentage;
        $modalPrecioPercentage.find('[id=material_pricePercentage]').val(pricePercentage);

    }).fail(function(jqXHR, textStatus, errorThrown) {
        // Función de error, se ejecuta cuando la solicitud GET falla
        console.error(textStatus, errorThrown);
        if (jqXHR.responseJSON.message && !jqXHR.responseJSON.errors) {
            toastr.error(jqXHR.responseJSON.message, 'Error', {
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
        for (var property in jqXHR.responseJSON.errors) {
            toastr.error(jqXHR.responseJSON.errors[property], 'Error', {
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
    }, 'json')
        .done(function() {
            // Configuración de encabezados
            var headers = {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            };

            $.ajaxSetup({
                headers: headers
            });
        });

    $modalPrecioPercentage.find('[id=material_id]').val(material_id);

    $modalPrecioPercentage.modal('show');
}

// Función para obtener los nombres clave de los checkboxes activos
function getActiveColumns() {
    var activeColumns = [];
    $('input[type="checkbox"]:checked').each(function() {
        activeColumns.push($(this).data('column'));
    });
    return activeColumns;
}

function openModalDisable() {
    var material_id = $(this).data('delete');
    var description = $(this).data('description');

    $modalDelete.find('[id=material_id]').val(material_id);
    $modalDelete.find('[id=descriptionDelete]').html(description);

    $modalDelete.modal('show');
}

function disableMaterial() {
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
            /*for ( var property in data.responseJSON.errors ) {
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
            }*/


        },
    });
}

function showImage() {
    var path = $(this).data('src');
    $('#image-document').attr('src', path);
    $modalImage.modal('show');
}

function exportExcel() {
    var start  = $('#start').val();
    var end  = $('#end').val();
    var startDate   = moment(start, "DD/MM/YYYY");
    var endDate     = moment(end, "DD/MM/YYYY");

    console.log(start);
    console.log(end);
    console.log(startDate);
    console.log(endDate);

    if ( start == '' || end == '' )
    {
        console.log('Sin fechas');
        $.confirm({
            icon: 'fas fa-file-excel',
            theme: 'modern',
            closeIcon: true,
            animation: 'zoom',
            type: 'green',
            title: 'No especificó fechas',
            content: 'Si no hay fechas se descargará todos los ingresos',
            buttons: {
                confirm: {
                    text: 'DESCARGAR',
                    action: function (e) {
                        //$.alert('Descargado igual');
                        console.log(start);
                        console.log(end);

                        var query = {
                            start: start,
                            end: end
                        };

                        $.alert('Descargando archivo ...');

                        var url = "/dashboard/exportar/reporte/egresos/proveedores/?" + $.param(query);

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
    } else {
        console.log('Con fechas');
        console.log(JSON.stringify(start));
        console.log(JSON.stringify(end));

        var query = {
            start: start,
            end: end
        };

        toastr.success('Descargando archivo ...', 'Éxito',
            {
                "closeButton": true,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "2000",
                "timeOut": "2000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            });

        var url = "/dashboard/exportar/reporte/egresos/proveedores/?" + $.param(query);

        window.location = url;

    }

}

/*function showDataSearch() {
    getDataMaterials(1)
}*/

function showData() {
    //event.preventDefault();
    var numberPage = $(this).attr('data-item');
    console.log(numberPage);
    var activeColumns = getActiveColumns();
    getDataMaterials(numberPage, activeColumns)
}

function getDataMaterials($numberPage, $activeColumns) {
    $('[data-toggle="tooltip"]').tooltip('dispose').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    var description = $('#description').val();
    var code = $('#code').val();
    var category = $('#category').val();
    var subcategory = $('#subcategory').val();
    var material_type = $('#material_type').val();
    var sub_type = $('#sub_type').val();
    var cedula = $('#cedula').val();
    var calidad = $('#calidad').val();
    var marca = $('#marca').val();
    var retaceria = $('#retaceria').val();
    var rotation = $('#rotation').val();
    var isPack = $('#isPack').val();

    $.get('/dashboard/get/data/material/v2/'+$numberPage, {
        description:description,
        code:code,
        category: category,
        subcategory: subcategory,
        material_type: material_type,
        sub_type: sub_type,
        cedula: cedula,
        calidad: calidad,
        marca: marca,
        retaceria: retaceria,
        rotation: rotation,
        isPack: isPack
    }, function(data) {
        if ( data.data.length == 0 )
        {
            renderDataMaterialsEmpty(data);
        } else {
            renderDataMaterials(data, $activeColumns);
        }


    }).fail(function(jqXHR, textStatus, errorThrown) {
        // Función de error, se ejecuta cuando la solicitud GET falla
        console.error(textStatus, errorThrown);
        if (jqXHR.responseJSON.message && !jqXHR.responseJSON.errors) {
            toastr.error(jqXHR.responseJSON.message, 'Error', {
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
        for (var property in jqXHR.responseJSON.errors) {
            toastr.error(jqXHR.responseJSON.errors[property], 'Error', {
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
    }, 'json')
        .done(function() {
            // Configuración de encabezados
            var headers = {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            };

            $.ajaxSetup({
                headers: headers
            });
        });
}

function renderDataMaterialsEmpty(data) {
    var dataAccounting = data.data;
    var pagination = data.pagination;
    console.log(dataAccounting);
    console.log(pagination);

    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' materiales');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    renderDataTableEmpty();
}

function renderDataMaterials(data, activeColumns) {
    var dataQuotes = data.data;
    var pagination = data.pagination;
    console.log(dataQuotes);
    console.log(pagination);
    console.log(activeColumns);

    $("#header-table").html('');
    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' materiales.');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    renderDataTableHeader(activeColumns);

    for (let j = 0; j < dataQuotes.length ; j++) {
        renderDataTable(dataQuotes[j], activeColumns);
    }

    if (pagination.currentPage > 1)
    {
        renderPreviousPage(pagination.currentPage-1);
    }

    if (pagination.totalPages > 1)
    {
        if (pagination.currentPage > 3)
        {
            renderItemPage(1);

            if (pagination.currentPage > 4) {
                renderDisabledPage();
            }
        }

        for (var i = Math.max(1, pagination.currentPage - 2); i <= Math.min(pagination.totalPages, pagination.currentPage + 2); i++)
        {
            renderItemPage(i, pagination.currentPage);
        }

        if (pagination.currentPage < pagination.totalPages - 2)
        {
            if (pagination.currentPage < pagination.totalPages - 3)
            {
                renderDisabledPage();
            }
            renderItemPage(i, pagination.currentPage);
        }

    }

    if (pagination.currentPage < pagination.totalPages)
    {
        renderNextPage(pagination.currentPage+1);
    }
}

function renderDataTableEmpty() {
    var clone = activateTemplate('#item-table-empty');
    $("#body-table").append(clone);
}

function renderDataTableHeader(activeColumns) {
    var cloneHeader = document.querySelector('#item-header').content.cloneNode(true);
    var headerRow = cloneHeader.querySelector('tr');

    headerRow.querySelectorAll('[data-column]').forEach(function(column) {
        var columnName = column.dataset.column;
        if (activeColumns.includes(columnName)) {
            column.style.display = 'table-cell';
        } else {
            column.style.display = 'none';
        }
    });

    $("#header-table").append(cloneHeader);

}

function renderDataTable(data, activeColumns) {
    console.log(data);
    var clone = document.querySelector('#item-table').content.cloneNode(true);

    // Iterar sobre cada columna en el cuerpo de la tabla y mostrar u ocultar según los checkboxes activos
    clone.querySelectorAll('[data-column]').forEach(function(column) {
        var columnName = column.dataset.column;
        if (activeColumns.includes(columnName)) {
            column.style.display = 'table-cell';
        } else {
            column.style.display = 'none';
        }
    });

    // Llenar los datos en cada celda según el objeto de datos
    clone.querySelector("[data-codigo]").innerHTML = data.codigo;
    if ( data.update_price == 1 )
    {
        clone.querySelector("[data-descripcion]").innerHTML = '<p class="text-blue">'+data.descripcion+'</p>';
    } else {
        clone.querySelector("[data-descripcion]").innerHTML = data.descripcion;
    }

    /*clone.querySelector("[data-medida]").innerHTML = data.medida;*/
    clone.querySelector("[data-unidad_medida]").innerHTML = data.unidad_medida;
    /*clone.querySelector("[data-stock_max]").innerHTML = data.stock_max;
    clone.querySelector("[data-stock_min]").innerHTML = data.stock_min;*/
    let stockContainer = clone.querySelector("[data-stock_actual]");

    if (data.stock_current !== null) {
        stockContainer.innerHTML = data.stock_current;
    } else {
        stockContainer.innerHTML = `
            <div class="d-flex flex-column align-items-center">
                <button class="btn btn-sm btn-outline-primary mt-1"
                    data-material-id="${data.id}"
                    data-ver-inventario>
                    Ver Inv.
                </button>
            </div>
        `;
    }

    let stockMinContainer = clone.querySelector("[data-stock_min]");

    if (data.stock_min !== null) {
        stockMinContainer.innerHTML = data.stock_min;
    } else {
        stockMinContainer.innerHTML = `
            <div class="d-flex flex-column align-items-center">
                <button class="btn btn-sm btn-outline-primary mt-1"
                    data-material-id="${data.id}"
                    data-ver-inventario>
                    Ver Inv.
                </button>
            </div>
        `;
    }

    let stockMaxContainer = clone.querySelector("[data-stock_max]");

    if (data.stock_max !== null) {
        stockMaxContainer.innerHTML = data.stock_max;
    } else {
        stockMaxContainer.innerHTML = `
            <div class="d-flex flex-column align-items-center">
                <button class="btn btn-sm btn-outline-primary mt-1"
                    data-material-id="${data.id}"
                    data-ver-inventario>
                    Ver Inv.
                </button>
            </div>
        `;
    }

    /*clone.querySelector("[data-prioridad]").innerHTML = data.prioridad;*/
    /*clone.querySelector("[data-precio_unitario]").innerHTML = data.precio_unitario;
    clone.querySelector("[data-precio_lista]").innerHTML = data.precio_lista;*/
    clone.querySelector("[data-categoria]").innerHTML = data.categoria;
    clone.querySelector("[data-sub_categoria]").innerHTML = data.sub_categoria;
    /*clone.querySelector("[data-tipo]").innerHTML = data.tipo;
    clone.querySelector("[data-sub_tipo]").innerHTML = data.sub_tipo;
    clone.querySelector("[data-cedula]").innerHTML = data.cedula;
    clone.querySelector("[data-calidad]").innerHTML = data.calidad;*/
    clone.querySelector("[data-marca]").innerHTML = data.marca;
    clone.querySelector("[data-modelo]").innerHTML = data.modelo;
    /*clone.querySelector("[data-retaceria]").innerHTML = data.retaceria;*/

    let url_image = document.location.origin + '/images/material/' + data.image;
    clone.querySelector("[data-ver_imagen]").setAttribute("data-src", url_image);
    clone.querySelector("[data-ver_imagen]").setAttribute("data-image", data.id);

    clone.querySelector("[data-rotation]").innerHTML = data.rotation;

    // Configurar enlaces y botones según los permisos y datos
    if ($.inArray('update_material', $permissions) !== -1) {
        let url = document.location.origin + '/dashboard/editar/material/' + data.id;
        clone.querySelector("[data-editar_material]").setAttribute("href", url);
    } else {
        let element = clone.querySelector("[data-editar_material]");
        if (element) {
            element.style.display = 'none';
        }
    }

    if ($.inArray('enable_material', $permissions) !== -1) {
        clone.querySelector("[data-deshabilitar]").setAttribute("data-delete", data.id);
        clone.querySelector("[data-deshabilitar]").setAttribute("data-description", data.descripcion);
        clone.querySelector("[data-deshabilitar]").setAttribute("data-measure", data.medida);
    } else {
        let element = clone.querySelector("[data-deshabilitar]");
        if (element) {
            element.style.display = 'none';
        }
    }


    /*clone.querySelector("[data-precioPorcentaje]").setAttribute("data-material", data.id);
    clone.querySelector("[data-precioPorcentaje]").setAttribute("data-description", data.descripcion);*/
    clone.querySelector("[data-precioDirecto]").setAttribute("data-material", data.id);
    clone.querySelector("[data-precioDirecto]").setAttribute("data-description", data.descripcion);

    if ( data.has_variants == 1 )
    {
        let url2 = document.location.origin + '/dashboard/view/material/variants/' + data.id;
        clone.querySelector("[data-ver_variants]").setAttribute("href", url2);
    } else {
        let element = clone.querySelector("[data-ver_variants]");
        if (element) {
            element.style.display = 'none';
        }
    }

    clone.querySelector("[data-show_vencimiento]").setAttribute("data-material", data.id);
    clone.querySelector("[data-show_vencimiento]").setAttribute("data-description", data.descripcion);

    clone.querySelector("[data-manage_presentations]").setAttribute("data-material", data.id);
    clone.querySelector("[data-manage_presentations]").setAttribute("data-description", data.descripcion);

    /*let url3 = document.location.origin + '/dashboard/enviar/material/a/tienda/' + data.id;
    clone.querySelector("[data-send_store]").setAttribute("href", url3);*/

    if (data.isPack == 1) {
        clone.querySelector("[data-assign_child]").setAttribute("data-material", data.id);
        clone.querySelector("[data-assign_child]").setAttribute("data-description", data.descripcion);

        clone.querySelector("[data-separate]").setAttribute("data-material", data.id);
        clone.querySelector("[data-separate]").setAttribute("data-description", data.descripcion);
        clone.querySelector("[data-separate]").setAttribute("data-measure", data.medida);
        clone.querySelector("[data-separate]").setAttribute("data-quantity", data.stock_actual);
    } else {
        let element = clone.querySelector("[data-separate]");
        let element2 = clone.querySelector("[data-assign_child]");
        if (element) {
            element.style.display = 'none';
        }
        if (element2) {
            element2.style.display = 'none';
        }
    }

    // Agregar la fila clonada al cuerpo de la tabla
    $("#body-table").append(clone);

    // Inicializar tooltips si es necesario
    $('[data-toggle="tooltip"]').tooltip();
}

function renderPreviousPage($numberPage) {
    var clone = activateTemplate('#previous-page');
    clone.querySelector("[data-item]").setAttribute('data-item', $numberPage);
    $("#pagination").append(clone);
}

function renderDisabledPage() {
    var clone = activateTemplate('#disabled-page');
    $("#pagination").append(clone);
}

function renderItemPage($numberPage, $currentPage) {
    var clone = activateTemplate('#item-page');
    if ( $numberPage == $currentPage )
    {
        clone.querySelector("[data-item]").setAttribute('data-item', $numberPage);
        clone.querySelector("[data-active]").setAttribute('class', 'page-item active');
        clone.querySelector("[data-item]").innerHTML = $numberPage;
    } else {
        clone.querySelector("[data-item]").setAttribute('data-item', $numberPage);
        clone.querySelector("[data-item]").innerHTML = $numberPage;
    }

    $("#pagination").append(clone);
}

function renderNextPage($numberPage) {
    var clone = activateTemplate('#next-page');
    clone.querySelector("[data-item]").setAttribute('data-item', $numberPage);
    $("#pagination").append(clone);
}
