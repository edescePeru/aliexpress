/* Cash Admin presentation adapter. Authorization and money rules remain backend-owned. */
(function ($) {
    'use strict';
    const root = document.getElementById('admin-movements');
    if (!root) return;
    const escape = value => $('<span>').text(value == null ? '' : String(value)).html();
    function menu(row, permissions) {
        const options = [];
        if (row.sale_id != null) {
            if (permissions.print === '1') options.push({label:'Ver comprobante de venta', icon:'fa-print', href:'/dashboard/imprimir/documento/venta/' + encodeURIComponent(row.sale_id)});
        } else if (row.entry_id != null && permissions.invoice === '1') {
            options.push({label:'Ver factura de compra', icon:'fa-file-invoice', href:'/dashboard/factura/compra/ver/' + encodeURIComponent(row.entry_id)});
        }
        if (permissions.regularize === '1' && !row.deferred_cancelled && (row.regularize === 0 || row.regularize === '0') && ['sale','income'].includes(row.type_raw || row.type)) {
            options.push({label:'Regularizar', icon:'fa-check-double'});
        }
        if (!options.length) return null;
        const wrapper = document.createElement('div'); wrapper.className = 'dropdown next-row-actions';
        const trigger = document.createElement('button'); trigger.type = 'button'; trigger.id = 'cash-actions-' + row.id;
        trigger.className = 'btn btn-sm next-row-actions-trigger'; trigger.setAttribute('data-toggle','dropdown'); trigger.setAttribute('data-boundary','viewport');
        trigger.setAttribute('aria-haspopup','true'); trigger.setAttribute('aria-expanded','false'); trigger.setAttribute('aria-label','Acciones del movimiento ' + row.id);
        trigger.innerHTML = '<i class="fas fa-ellipsis-h" aria-hidden="true"></i>';
        const list = document.createElement('div'); list.className = 'dropdown-menu dropdown-menu-right next-row-actions-menu'; list.setAttribute('aria-labelledby',trigger.id);
        options.forEach(option => {
            const item = document.createElement(option.href ? 'a' : 'button'); item.className = 'dropdown-item';
            if (option.href) { item.href = option.href; item.target = '_blank'; item.rel = 'noopener'; item.setAttribute('data-print_nota',''); item.setAttribute('data-id',row.id); }
            else { item.type = 'button'; item.setAttribute('data-regularizar',''); item.setAttribute('data-id',row.id); item.setAttribute('data-regularization-amount',row.amount); }
            const icon = document.createElement('i'); icon.className = 'fas ' + option.icon + ' next-row-action-item-icon'; icon.setAttribute('aria-hidden','true');
            const label = document.createElement('span'); label.textContent = option.label; item.append(icon,label); list.appendChild(item);
        });
        wrapper.append(trigger,list); return wrapper;
    }
    window.CashAdminOperations = {menu:menu};
    let opening = false;
    $(document).on('click','[data-cashbox-btn]',function () {
        if (opening) return;
        const button = this, id = button.getAttribute('data-cash_box_id'), cash = button.getAttribute('data-cash_box_type') === 'cash';
        let content = '¿Deseas iniciar tu sesión en la caja <b>' + escape(button.getAttribute('data-cash_box_name')) + '</b>?';
        if (cash) content += '<div class="form-group mt-3"><label for="opening_balance">Saldo inicial (Efectivo)</label><input type="number" step="0.01" min="0" class="form-control" id="opening_balance" value="0.00"></div><div class="form-group"><label for="opening_origin">Origen del fondo</label><select class="form-control" id="opening_origin"><option value="">Elegir si el saldo es mayor a cero</option><option value="consolidated">Saldo rendido de esta caja</option><option value="external">Fondos externos</option></select></div><div class="form-group"><label for="opening_provenance">Procedencia (obligatoria para fondos externos)</label><input class="form-control" id="opening_provenance" maxlength="150"></div><div class="form-group"><label for="opening_observation">Observación (opcional)</label><input class="form-control" id="opening_observation" maxlength="500"></div>';
        $.confirm({title:'Iniciar sesión',icon:'fas fa-cash-register',content:content,buttons:{cancelar:{text:'Cancelar'},confirmar:{text:'Confirmar',btnClass:'btn-primary',action:function () {
            if (opening) return false;
            opening = true;
            $.ajax({url:root.dataset.openUrl,method:'POST',dataType:'json',headers:{Accept:'application/json'},data:{
                _token:$('meta[name="csrf-token"]').attr('content'), cash_box_id:id,
                opening_balance:cash ? ($('#opening_balance').val() || 0) : 0,
                opening_origin:cash ? $('#opening_origin').val() : null,
                opening_provenance:cash ? $('#opening_provenance').val() : null,
                opening_observation:cash ? $('#opening_observation').val() : null
            }}).done(function () { window.location.href = root.dataset.sessionUrl + '/' + encodeURIComponent(id); })
                .fail(function (xhr) { const res = xhr.responseJSON || {}, messages = Object.values(res.errors || {}).flat(); $.alert({title:'No se pudo iniciar la sesión',content:escape(messages.join(' ') || res.message || 'Intente nuevamente.')}); })
                .always(function () { opening = false; });
        }}}});
    });
})(window.jQuery);
