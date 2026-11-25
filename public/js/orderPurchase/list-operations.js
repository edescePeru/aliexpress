/* R3.1: existing purchase operations; backend remains the authorization authority. */
(function ($) {
    'use strict';
    function escape(value) { return $('<span>').text(value == null ? '' : value).html(); }
    function item(label, icon, attrs, destructive) {
        return '<' + (attrs.href ? 'a' : 'button type="button"') + ' class="dropdown-item' + (destructive ? ' text-danger' : '') + '" role="menuitem" ' + Object.keys(attrs).map(function (key) { return key + '="' + escape(attrs[key]) + '"'; }).join(' ') + '><i class="fas ' + icon + ' next-row-action-item-icon" aria-hidden="true"></i><span>' + label + '</span></' + (attrs.href ? 'a' : 'button') + '>';
    }
    window.PurchaseListOperations = {
        render: function (row, permissions, deleted, recoverNormalEdit) {
            var can = function (permission) { return permissions.indexOf(permission) !== -1; };
            var id = encodeURIComponent(row.id), links = '';
            if (deleted) {
                if (can('list_orderPurchaseNormal') && can('destroy_orderPurchaseNormal')) links += item('Ver detalle histórico', 'fa-eye', {href:'/dashboard/ver/orden/compra/eliminada/' + id, 'data-ver_orden':''});
                if (can('destroy_orderPurchaseNormal')) links += item('Restaurar', 'fa-trash-restore', {'data-restore':row.id, 'data-name':row.code});
                if (can('list_orderPurchaseNormal')) links += item('Imprimir orden eliminada', 'fa-print', {href:'/dashboard/imprimir/orden/compra/eliminada/' + id, target:'_blank', rel:'noopener', 'data-imprimir':''});
            } else {
                if (row.type === 'n' && can('list_orderPurchaseNormal')) links += item('Ver orden', 'fa-eye', {href:'/dashboard/ver/orden/compra/normal/' + id, 'data-ver_orden':''});
                if (recoverNormalEdit === true && row.type === 'n' && can('update_orderPurchaseNormal')) links += item('Editar orden', 'fa-pen', {href:'/dashboard/editar/orden/compra/normal/' + id, 'data-editar':''});
                if ((row.type === 'n' || row.type === 'e') && can('list_orderPurchaseNormal')) links += item('Imprimir orden', 'fa-print', {href:'/dashboard/imprimir/orden/compra/' + id, target:'_blank', rel:'noopener', 'data-imprimir':''});
                if (row.type === 'n' && can('update_orderPurchaseNormal')) links += item('Cambiar estado', 'fa-exchange-alt', {'data-estado':'', 'data-state':row.id, 'data-name':row.code});
                if (row.type === 'n' && can('destroy_orderPurchaseNormal')) links += item('Anular orden', 'fa-trash', {'data-anular':'', 'data-delete':row.id, 'data-name':row.code}, true);
                // Recovery is explicit for the active Normal list, not legacy General/Express.
            }
            if (!links) return '—';
            var trigger = 'purchase-actions-' + (deleted ? 'trash-' : '') + row.id;
            return '<div class="dropdown next-row-actions"><button type="button" id="' + escape(trigger) + '" class="btn btn-sm next-row-actions-trigger" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false" aria-label="Acciones de ' + escape(row.code) + '"><i class="fas fa-ellipsis-h" aria-hidden="true"></i></button><div class="dropdown-menu dropdown-menu-right next-row-actions-menu" role="menu" aria-labelledby="' + escape(trigger) + '">' + links + '</div></div>';
        }
    };
    function fail(xhr) {
        var body = xhr.responseJSON || {}, errors = body.errors || {};
        var messages = Object.keys(errors).map(function (key) { return [].concat(errors[key]).join(' '); });
        $.alert(escape(messages.join(' ') || body.message || 'No se pudo completar la operación.'));
    }
    function refresh() {
        if (typeof window.getDataOrderPurchases === 'function') window.getDataOrderPurchases(1);
        else $(document).trigger('purchase:refresh');
    }
    function post(url, data, button, done) {
        if (button.data('purchaseBusy')) return;
        button.data('purchaseBusy', true).prop('disabled', true);
        $.ajax({url:url, method:'POST', headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}, data:data, processData:false, contentType:false})
            .done(function (response) { if (done) done(); $.alert(escape(response.message || 'Operación completada.')); refresh(); })
            .fail(fail).always(function () { button.removeData('purchaseBusy').prop('disabled', false); });
    }
    $(function () {
        $(document).on('click.purchaseOperations', '[data-delete], [data-restore]', function () {
            var button = $(this), restoring = button.is('[data-restore]');
            if (button.data('purchaseConfirm') || button.data('purchaseBusy')) return;
            button.data('purchaseConfirm', true);
            $.confirm({title:restoring ? '¿Restaurar esta orden de compra?' : '¿Anular esta orden de compra?', content:escape(button.attr('data-name')), onDestroy:function () { button.removeData('purchaseConfirm'); }, buttons:{confirm:{text:'Confirmar', btnClass:restoring ? 'btn-primary' : 'btn-danger', action:function () {
                post('/dashboard/' + (restoring ? 'restore/order/purchase/delete/' : 'destroy/order/purchase/normal/') + encodeURIComponent(button.attr(restoring ? 'data-restore' : 'data-delete')), undefined, button);
            }}, cancel:{text:'Cancelar'}}});
        });
        var stateRequest;
        $(document).on('click.purchaseOperations', '[data-estado]', function () {
            var id = $(this).attr('data-state');
            if (stateRequest) stateRequest.abort();
            $('#orderPurchase_id').val('');
            stateRequest = $.getJSON('/dashboard/get/state/order/purchase/' + encodeURIComponent(id)).done(function (data) {
                $('#orderPurchase_id').val(id); $('#stateOrder').val(data.state).trigger('change'); $('#modalState').modal('show');
            }).fail(function (xhr, status) { if (status !== 'abort') fail(xhr); });
        });
        $('#btn-changeState').on('click.purchaseOperations', function () {
            var button = $(this), form = $('#formStates')[0];
            if (button.data('purchaseConfirm') || button.data('purchaseBusy')) return;
            button.data('purchaseConfirm', true);
            $.confirm({title:'¿Guardar el estado de la orden?', content:'Este cambio modificará el estado de la orden.', onDestroy:function () { button.removeData('purchaseConfirm'); }, buttons:{confirm:{text:'Confirmar', action:function () {
                post('/dashboard/change/state/order/purchase', new FormData(form), button, function () { $('#modalState').modal('hide'); });
            }}, cancel:{text:'Cancelar'}}});
        });
        $('#btn-export').on('click.purchaseOperations', function () {
            var query = {start:$('#start').val(), end:$('#end').val()};
            function download() { window.location.href = '/dashboard/exportar/reporte/ordenes/compra/v2/?' + $.param(query); }
            if (!query.start || !query.end) $.confirm({title:'No especificó fechas', content:'Se descargarán todas las órdenes de compra de la empresa activa.', buttons:{confirm:{text:'Descargar', action:download}, cancel:{text:'Cancelar'}}});
            else download();
        });
    });
})(jQuery);
