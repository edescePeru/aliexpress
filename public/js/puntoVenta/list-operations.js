/* Sales operational adapter. Current backend is authoritative; no business writes on load. */
(function ($) {
    'use strict';
    let adapter, paymentSequence = 0, ncSequence = 0, paymentRequest, ncRequest;
    let ncPartialData = null, ncPartialSelectedItems = {}, operationTrigger, activePaymentRow;
    const pending = new Set();
    function context() { return document.getElementById('sale-list-context'); }
    function permissions() { return JSON.parse(context().dataset.permissions || '{}'); }
    function flag(value) { return value === true || value === 1 || value === '1'; }
    function escapeHtml(value) { return $('<span>').text(value == null ? '' : String(value)).html(); }
    function message(xhr) {
        const body = xhr.responseJSON || {};
        if (body.errors) return Object.keys(body.errors).map(k => [].concat(body.errors[k]).join(' ')).join(' ');
        if (body.message) return body.message;
        return ({401: 'La sesión ha caducado.', 419: 'La sesión ha caducado. Recarga la página.',
            403: 'No tienes permiso para esta operación.', 404: 'El registro no está disponible en el contexto actual.'})[xhr.status] || 'No se pudo completar la operación.';
    }
    function notice(text, title) { $.alert({title: title || 'Información', content: escapeHtml(text), buttons: {ok: {text: 'Cerrar'}}}); }
    function reloadCurrentPageOrders() { if (adapter) adapter.reload(); }
    function headers() {
        const n = context();
        return {'X-Sales-Tenant': n.dataset.tenant, 'X-Sales-Company': n.dataset.company,
            'X-Sales-Branch': n.dataset.branch, 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), Accept: 'application/json'};
    }
    function request(url, method, data, json) {
        return $.ajax({url: url, method: method, data: json ? JSON.stringify(data) : data, headers: headers(),
            dataType: 'json', contentType: json ? 'application/json' : 'application/x-www-form-urlencoded; charset=UTF-8', processData: !json});
    }
    function mutate(url, method, data, callback, json) {
        if (pending.has(url)) return;
        pending.add(url);
        const controls = $('.sale-operation-submit, #btnGuardarPagoParcial, #btnConfirmarGenerarComprobante, #btn-submit-nc-partial, #btn-confirm-nc-partial-items');
        controls.prop('disabled', true);
        request(url, method, data, json).done(function (res) {
            if (res.success === false || res.error) { notice(res.message || res.error, 'Operación no completada'); return; }
            if (callback) callback(res);
            notice(res.message || 'Operación completada.', 'Resultado');
            // Never open url_print/pdf_url returned by legacy mutations: refresh guarded DTO links instead.
            reloadCurrentPageOrders();
        }).fail(function (xhr) { notice(message(xhr), 'Operación no completada'); reloadCurrentPageOrders(); })
            .always(function () { pending.delete(url); if (!pending.size) controls.prop('disabled', false); });
    }
    function confirm(title, text, action) {
        $.confirm({title: title, content: escapeHtml(text), buttons: {
            confirmar: {text: 'Confirmar', btnClass: 'btn-primary', action: action}, cancelar: {text: 'Cancelar'}}});
    }
    function choices(row, errors) {
        const p = permissions(), active = !flag(row.is_annulled), electronic = ['01', '03'].includes(String(row.type_document));
        const valid = electronic && row.sunat_status !== 'Error';
        const items = [];
        const add = (hook, label, destructive) => items.push({hook, label, destructive: !!destructive});
        if (errors) {
            if (p.annul && flag(row.can_discard_error)) add('descartar_error_comprobante', 'Descartar error de comprobante', true);
            add('ver_error_sunat', 'Ver diagnóstico SUNAT');
            return items;
        }
        if (row.has_rejected_credit_note) add('ver_error_nota_credito', 'Ver rechazo de nota de crédito');
        if (row.sunat_error_is_discarded) add('ver_solucion_sunat_error', 'Ver solución del error SUNAT');
        if (!active) return items;
        if (p.emit && !valid) add('generar_comprobante', 'Generar comprobante');
        if (p.annul && flag(row.can_annul) && !['pending', 'waiting_sunat_process', 'accepted'].includes(row.annulment_status)) add('anular', 'Anular venta', true);
        // History is a read permission, even when amount due is zero or payments permission is absent.
        if (row.pagos_parciales_venta === 's') add('pagos_parciales', p.payments ? 'Pagos parciales / historial' : 'Historial de pagos');
        if (p.dispatch) add('cambiar_despacho', row.dispatch_status === 'despachado' ? 'Marcar despacho pendiente' : 'Marcar como despachado');
        if (p.annul && ['pending', 'waiting_sunat_process'].includes(row.annulment_status)) add('consultar_anulacion', 'Consultar anulación');
        if (p.annul && row.has_pending_credit_note) add('consultar_nota_credito', 'Consultar nota de crédito');
        else if (p.annul && !row.has_rejected_credit_note && flag(row.can_generate_credit_note)) add('generar_nota_credito', 'Generar nota de crédito total');
        if (p.annul && !flag(row.free_sale) && valid && !row.has_pending_credit_note && !row.has_rejected_credit_note) add('generar_nota_credito_parcial', 'Generar nota de crédito parcial');
        if (p.documents && flag(row.can_retry_nubefact_files)) add('recuperar_archivos_nubefact', 'Recuperar archivos Nubefact');
        return items;
    }
    function menu(row, errors) {
        const icons = {
            generar_comprobante: 'fa-file-invoice', anular: 'fa-trash-alt',
            pagos_parciales: 'fa-money-bill-wave', cambiar_despacho: 'fa-truck',
            consultar_anulacion: 'fa-search', generar_nota_credito: 'fa-file-invoice-dollar',
            generar_nota_credito_parcial: 'fa-file-invoice', consultar_nota_credito: 'fa-search',
            recuperar_archivos_nubefact: 'fa-sync-alt', descartar_error_comprobante: 'fa-ban',
            ver_error_sunat: 'fa-exclamation-circle', ver_error_nota_credito: 'fa-exclamation-circle',
            ver_solucion_sunat_error: 'fa-info-circle'
        };
        return choices(row, errors).map(function (a) {
            return '<button type="button" class="dropdown-item' + (a.destructive ? ' text-danger' : '') + '" role="menuitem" data-sale-operation="' + a.hook + '" data-' + a.hook + ' data-id="' + escapeHtml(row.id) + '" data-sale_id="' + escapeHtml(row.id) + '" data-free_sale="' + (flag(row.free_sale) ? '1' : '0') + '" data-type_document="' + escapeHtml(row.type_document) + '"><i class="fas ' + icons[a.hook] + ' next-row-action-item-icon' + (a.destructive ? ' text-danger' : '') + '" aria-hidden="true"></i><span>' + escapeHtml(a.label) + '</span></button>';
        }).join('');
    }
    function rowFor(element) { return adapter && adapter.row(String($(element).attr('data-sale_id'))); }
    function showPayments(row) {
        activePaymentRow = row;
        $('#pp_sale_id').val(row.id); $('#pp_monto').val('');
        const now = new Date(); $('#pp_fecha_pago').val(now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0') + '-' + String(now.getDate()).padStart(2,'0'));
        $('#pp_cash_box_id, #pp_cash_box_subtype_id').val('').trigger('change');
        $('#pp_title').text('Pagos parciales · ' + row.code);
        $('#modalPagosParciales').modal('show'); cargarPagosParciales(row.id);
    }
    function cargarPagosParciales(id) {
        const token = ++paymentSequence;
        if (paymentRequest) paymentRequest.abort();
        $('#pp_feedback').addClass('d-none').text(''); $('#pp_body_pagos').empty();
        $('#pp_total_venta, #pp_total_abonado').val(''); $('#pp_progress_text').text('Cargando pagos...');
        paymentRequest = request('/dashboard/sale/partial-payment/' + encodeURIComponent(id), 'GET').done(function (res) {
            if (token !== paymentSequence) return;
            const currency = activePaymentRow.currency || '';
            $('#pp_total_venta').val(currency + ' ' + res.total_venta); $('#pp_total_abonado').val(currency + ' ' + res.total_abonado);
            $('#pp_progress_text').text('Saldo pendiente: ' + currency + ' ' + res.saldo_pendiente);
            res.pagos.forEach(function (payment) {
                const tr = $('<tr>');
                [['payment_date','center'],['amount','right'],['cash_box_label','left']].forEach(c => $('<td>').addClass('text-'+c[1]).text(payment[c[0]]).appendTo(tr));
                const td = $('<td class="text-center">');
                if (permissions().payments) $('<button type="button" class="btn btn-outline-danger btn-sm" data-eliminar_pago_parcial><i class="fas fa-trash-alt mr-1" aria-hidden="true"></i>Eliminar</button>').attr('data-id',payment.id).appendTo(td);
                tr.append(td); $('#pp_body_pagos').append(tr);
            });
            if (!res.pagos.length) $('#pp_body_pagos').append('<tr><td colspan="4" class="text-center">Sin pagos registrados</td></tr>');
        }).fail(function (xhr,status) { if (status !== 'abort' && token === paymentSequence) { $('#pp_feedback').removeClass('d-none').text(message(xhr)); $('#pp_progress_text').text('Consulta no disponible'); } });
    }
    function openInvoice(row) {
        $('#gc_sale_id').val(row.id); $('#gc_free_sale').val(flag(row.free_sale) ? '1' : '0');
        $('#modalGenerarComprobante input:not([type="hidden"])').val('');
        $('#gc_name, #gc_razon_social, #gc_direccion_fiscal').prop('readonly', true);
        ['boleta','factura'].forEach(type => $('#gc_'+type+'_tab').parent().prop('hidden', $('#gc_tabs').attr('data-'+type) !== '1'));
        const first = $('#gc_tabs').attr('data-boleta') === '1' ? '#gc_boleta_tab' : '#gc_factura_tab';
        $(first).tab('show'); $('#modalGenerarComprobante').modal('show');
    }
    let lookupSequence = {dni: 0, ruc: 0};
    function lookup(type) {
        const id = $('#gc_'+type).val(), length = type === 'dni' ? 8 : 11, token = ++lookupSequence[type];
        if (!(new RegExp('^\\d{'+length+'}$')).test(id)) { notice('Ingrese '+length+' dígitos.'); return; }
        request('/dashboard/customer/decolecta/'+encodeURIComponent(id),'GET').done(function(res) {
            if (token !== lookupSequence[type] || $('#gc_'+type).val() !== id) return;
            if (!res.success) { manual(res.message); return; }
            const c=res.customer;
            if(type==='dni') $('#gc_name').val(c.business_name || '');
            else { $('#gc_razon_social').val(c.business_name || ''); $('#gc_direccion_fiscal').val(c.address || ''); }
        }).fail(function(xhr) { if(token===lookupSequence[type] && $('#gc_'+type).val()===id) manual(message(xhr)); });
        function manual(msg) {
            $(type==='dni' ? '#gc_name' : '#gc_razon_social, #gc_direccion_fiscal').val('').prop('readonly',false);
            notice((msg || 'Consulta sin resultado.')+' Puede ingresarlo manualmente.');
        }
    }
    function prepareInvoice() {
        if (!permissions().emit) return;
        const type = $('#gc_tabs .nav-link.active').attr('href') === '#gc_factura' ? 'factura' : 'boleta';
        if ($('#gc_tabs').attr('data-'+type) !== '1') return;
        const data = {sale_id: $('#gc_sale_id').val(), tipo_comprobante: type,
            dni: $('#gc_dni').val(), name: $('#gc_name').val(), email_boleta: $('#gc_email_boleta').val(),
            ruc: $('#gc_ruc').val(), razon_social: $('#gc_razon_social').val(), direccion_fiscal: $('#gc_direccion_fiscal').val(), email_factura: $('#gc_email_factura').val()};
        if (type === 'boleta' ? !/^\d{8}$/.test(data.dni) || !data.name : !/^\d{11}$/.test(data.ruc) || !data.razon_social || !data.direccion_fiscal) { notice('Complete los datos obligatorios del comprobante.'); return; }
        const url = $('#gc_free_sale').val()==='1' ? '/dashboard/ventas-libres/generate-invoice' : '/dashboard/sale/generate-invoice';
        confirm('Generar comprobante', 'Se emitirá el comprobante seleccionado mediante el proveedor. ¿Desea continuar?', function(){ mutate(url,'POST',data,function(){ $('#modalGenerarComprobante').modal('hide'); }); });
    }
    function openPartial(row) {
        const token=++ncSequence;
        if(ncRequest) ncRequest.abort(); ncPartialData=null; ncPartialSelectedItems={};
        ncRequest=request('/dashboard/nota-credito/parcial/data/'+row.id,'GET').done(function(data){
            if(token!==ncSequence) return;
            ncPartialData=data; $('#nc_partial_sale_id').val(data.sale_id); $('#nc_partial_sale_code').text(data.code);
            $('#nc_partial_customer').text(data.cliente); $('#nc_partial_document').text((data.type_document==='01'?'Factura':'Boleta')+' '+data.serie_sunat+'-'+data.numero);
            $('#nc_partial_currency').text(row.currency || ''); $('#body-nc-partial-items').empty();
            data.items.forEach(function(item){
                const tr=$('<tr data-nc-partial-row>').attr('data-sale_detail_id',item.sale_detail_id).attr('data-is_itemable',item.is_itemable?1:0);
                $('<td>').text(item.description).appendTo(tr);
                ['sold_quantity','credited_quantity','available_quantity'].forEach(k=>$('<td class="text-center">').text(item[k]).appendTo(tr));
                const input=$('<input type="number" class="form-control form-control-sm nc-partial-qty" min="0" value="0">')
                    .attr({'aria-label':'Cantidad a devolver: '+item.description,'data-sale_detail_id':item.sale_detail_id,'data-price':item.price,'data-max':item.available_quantity,'data-is_itemable':item.is_itemable?1:0,max:item.available_quantity,step:item.is_itemable?1:0.01});
                $('<td>').append(input).appendTo(tr); $('<td class="text-right"><span class="nc-partial-line-total">0.00</span></td>').appendTo(tr); $('#body-nc-partial-items').append(tr);
            });
            $('#nc_partial_total').text('0.00'); $('#modalNotaCreditoParcial').modal('show');
        }).fail(function(xhr,status){ if(status!=='abort' && token===ncSequence) notice(message(xhr)); });
    }
    function prepararEnvioNotaCreditoParcial() {
        if(!permissions().annul || !ncPartialData) return;
        const items=[];
        $('.nc-partial-qty').each(function(){ const quantity=Number($(this).val()), id=Number($(this).attr('data-sale_detail_id')); if(quantity>0) items.push({sale_detail_id:id,quantity:quantity,selected_item_ids:ncPartialSelectedItems[id] || []}); });
        if(!items.length) { notice('Seleccione al menos un producto a devolver.'); return; }
        const url='/dashboard/generar/nota-credito/parcial/'+$('#nc_partial_sale_id').val();
        confirm('Generar nota de crédito parcial','Se enviará al proveedor y se devolverán los Items seleccionados. ¿Desea continuar?',function(){
            mutate(url,'POST',{reason_code:'07',reason_description:'Devolución parcial',items:items},function(){ $('#modalNotaCreditoParcial, #modalNcPartialItems').modal('hide'); ncPartialData=null; ncPartialSelectedItems={}; },true);
        });
    }
    function discard(row) {
        $.confirm({title:'Descartar comprobante con error',content:'<p>Se quitará de la bandeja de errores sin cambiar el estado SUNAT original.</p><div class="form-group"><label for="discard_reason_select">Motivo</label><select id="discard_reason_select" class="form-control"><option>Generado manualmente en Nubefact</option><option>Generado manualmente en SUNAT</option><option>No corresponde emitir comprobante</option><option>Otro</option></select></div><div class="form-group"><label for="discard_reason_text">Comentario adicional</label><textarea id="discard_reason_text" class="form-control" rows="3"></textarea></div>',buttons:{confirmar:{text:'Descartar',btnClass:'btn-danger',action:function(){
            const extra=$('#discard_reason_text').val().trim();
            mutate('/dashboard/sales/errors/'+row.id+'/discard','POST',{reason:$('#discard_reason_select').val()+(extra?' - '+extra:'')});
        }},cancelar:{text:'Cancelar'}}});
    }
    function act(hook,row) {
        const free=flag(row.free_sale), id=encodeURIComponent(row.id);
        const simple={
            anular:[free?'/dashboard/ventas-libres/'+id+'/anular':'/dashboard/anular/order/'+id,'Anular venta','Esta operación puede revertir pagos y movimientos y comunicarse con SUNAT. Verifique la venta antes de confirmar.'],
            consultar_anulacion:[free?'/dashboard/ventas-libres/'+id+'/consultar-anulacion':'/dashboard/consultar/anulacion/'+id,'Consultar anulación','Se consultará al proveedor y se actualizará el estado de anulación.'],
            generar_nota_credito:['/dashboard/generar/nota-credito/total/'+id,'Generar nota de crédito total','Se generará una nota de crédito total mediante el proveedor.'],
            consultar_nota_credito:['/dashboard/consultar/nota-credito/'+id,'Consultar nota de crédito','Se consultará al proveedor y se actualizará el estado de la nota de crédito.'],
            recuperar_archivos_nubefact:['/dashboard/ventas/'+id+'/recuperar-archivos-nubefact','Recuperar archivos Nubefact','Se consultará al proveedor y se descargarán los archivos del comprobante.']
        };
        if(simple[hook]) { const op=simple[hook]; confirm(op[1],op[2],function(){mutate(op[0],'POST',{});}); return; }
        if(hook==='pagos_parciales') return showPayments(row);
        if(hook==='generar_comprobante') return openInvoice(row);
        if(hook==='generar_nota_credito_parcial') return openPartial(row);
        if(hook==='descartar_error_comprobante') return discard(row);
        if(hook==='cambiar_despacho') {
            const state=row.dispatch_status==='despachado'?'pendiente':'despachado';
            confirm('Cambiar despacho','Cambiar la venta '+row.code+' a '+state+'?',function(){mutate('/dashboard/sale/update-dispatch-status','POST',{sale_id:row.id,dispatch_status:state});}); return;
        }
        if(hook==='ver_error_sunat') notice(row.sunat_message || row.sunat_message_short || 'Sin diagnóstico registrado.','Diagnóstico SUNAT');
        if(hook==='ver_error_nota_credito') notice([row.rejected_credit_note_id, row.rejected_credit_note_responsecode, row.rejected_credit_note_message].filter(Boolean).join(' · ') || 'SUNAT rechazó la nota de crédito.','Nota de crédito rechazada');
        if(hook==='ver_solucion_sunat_error') notice([row.sunat_error_discarded_at,row.sunat_error_discard_reason || 'Sin motivo registrado.'].filter(Boolean).join(' · '),'Solución del comprobante');
    }
    window.SaleListOperations={menu:menu,choices:choices,attach:function(value){adapter=value;}};
    $(function(){
        $(document).on('click','[data-sale-operation]',function(){
            const row=rowFor(this), hook=$(this).attr('data-sale-operation');
            if(!row || !choices(row,context().dataset.listMode==='errors').some(a=>a.hook===hook)) return;
            if (pending.size) { notice('Hay una operación en curso. Espere su resultado.'); return; }
            ++ncSequence; if (ncRequest) ncRequest.abort();
            operationTrigger=document.getElementById('sales-actions-'+row.id);
            $(operationTrigger).dropdown('hide'); act(hook,row);
        });
        $('#pp_cash_box_id').on('change',function(){
            const opt=$(this).find(':selected'), uses=opt.attr('data-type')==='bank' && opt.attr('data-uses_subtypes')==='1';
            $('#pp_cash_box_subtype_wrap').prop('hidden',!uses); $('#pp_cash_box_subtype_id').val('').trigger('change.select2');
            $('#pp_cash_box_subtype_id option').each(function(){const parent=$(this).attr('data-cash-box'); $(this).prop('disabled',!!parent && parent!==String(opt.val()));});
        });
        $('#btnGuardarPagoParcial').on('click',function(){
            if(!permissions().payments) return;
            const opt=$('#pp_cash_box_id :selected'), uses=opt.attr('data-type')==='bank' && opt.attr('data-uses_subtypes')==='1';
            const data={sale_id:$('#pp_sale_id').val(),payment_date:$('#pp_fecha_pago').val(),amount:$('#pp_monto').val(),cash_box_id:$('#pp_cash_box_id').val(),cash_box_subtype_id:uses?$('#pp_cash_box_subtype_id').val():null};
            if(!data.payment_date || !(Number(data.amount)>0) || !data.cash_box_id || (uses&&!data.cash_box_subtype_id)) {notice('Complete fecha, monto, caja y subtipo cuando corresponda.');return;}
            confirm('Registrar pago parcial','Se registrará el pago y su movimiento de caja. ¿Desea continuar?',function(){mutate('/dashboard/sale/partial-payment/store','POST',data,function(){ $('#pp_monto').val(''); cargarPagosParciales(data.sale_id); });});
        });
        $(document).on('click','[data-eliminar_pago_parcial]',function(){
            if(!permissions().payments) return;
            const paymentId=$(this).attr('data-id'), saleId=$('#pp_sale_id').val();
            confirm('Eliminar pago parcial','Se revertirá el movimiento de caja asociado. ¿Desea continuar?',function(){mutate('/dashboard/sale/partial-payment/'+encodeURIComponent(paymentId),'DELETE',{},function(){cargarPagosParciales(saleId);});});
        });
        $('#btnConfirmarGenerarComprobante').on('click',prepareInvoice);
        ['dni','ruc'].forEach(function(type){
            $('#btnBuscarGc'+(type==='dni'?'Dni':'Ruc')).on('click',()=>lookup(type));
            $('#gc_'+type).on('keydown',function(e){if(e.key==='Enter'){e.preventDefault();lookup(type);}}).on('input',function(){ ++lookupSequence[type]; $(type==='dni'?'#gc_name':'#gc_razon_social, #gc_direccion_fiscal').val(''); });
        });
        $('#modalPagosParciales').on('hidden.bs.modal',function(){++paymentSequence;if(paymentRequest)paymentRequest.abort();});
        $('#modalGenerarComprobante').on('hidden.bs.modal',function(){++lookupSequence.dni;++lookupSequence.ruc;});
        $('#modalPagosParciales, #modalGenerarComprobante').on('hidden.bs.modal',function(){if(operationTrigger && document.contains(operationTrigger))operationTrigger.focus();});
        $('#nc-items-back').on('click',function(){ $('#modalNcPartialItems').one('hidden.bs.modal',function(){$('#modalNotaCreditoParcial').modal('show');}); });
    $(document).on('input', '.nc-partial-qty', function () {
        let input = $(this);
        let qty = parseFloat(input.val()) || 0;
        let max = parseFloat(input.data('max')) || 0;
        let price = parseFloat(input.data('price')) || 0;

        if (qty > max) {
            qty = max;
            input.val(max);
        }

        if (qty < 0) {
            qty = 0;
            input.val(0);
        }

        let totalLine = qty * price;

        input.closest('tr').find('.nc-partial-line-total').text(totalLine.toFixed(2));

        let total = 0;

        $('.nc-partial-qty').each(function () {
            let q = parseFloat($(this).val()) || 0;
            let p = parseFloat($(this).data('price')) || 0;
            total += q * p;
        });

        $('#nc_partial_total').text(total.toFixed(2));
    });

    $(document).on('click', '#btn-submit-nc-partial', function () {

        if (!ncPartialData) {
            $.alert({
                title: 'Aviso',
                content: 'No se ha cargado la información de la venta.',
                type: 'orange'
            });

            return;
        }

        let selectedLines = [];
        let itemableLines = [];
        let hasErrors = false;

        $('.nc-partial-qty').each(function () {

            const input = $(this);

            const saleDetailId = parseInt(
                input.data('sale_detail_id')
            );

            const qty = parseFloat(input.val()) || 0;
            const max = parseFloat(input.data('max')) || 0;
            const price = parseFloat(input.data('price')) || 0;
            const isItemable = parseInt(input.data('is_itemable')) === 1;

            if (qty < 0 || qty > max) {
                hasErrors = true;

                $.alert({
                    title: 'Cantidad inválida',
                    content: 'La cantidad a devolver no puede superar la cantidad disponible.',
                    type: 'orange'
                });

                return false;
            }

            /*
             * Los productos itemeables solo admiten cantidades enteras.
             */
            if (
                isItemable &&
                qty > 0 &&
                !Number.isInteger(qty)
            ) {
                hasErrors = true;

                $.alert({
                    title: 'Cantidad inválida',
                    content: 'Los productos itemeables deben devolverse en cantidades enteras.',
                    type: 'orange'
                });

                return false;
            }

            if (qty <= 0) {
                return;
            }

            const itemData = ncPartialData.items.find(function (row) {
                return parseInt(row.sale_detail_id) === saleDetailId;
            });

            if (!itemData) {
                hasErrors = true;
                return false;
            }

            const line = {
                sale_detail_id: saleDetailId,
                quantity: qty,
                price: price,
                is_itemable: isItemable,
                description: itemData.description,
                available_items: itemData.available_items || []
            };

            selectedLines.push(line);

            if (isItemable) {
                itemableLines.push(line);
            }
        });

        if (hasErrors) {
            return;
        }

        if (selectedLines.length === 0) {
            $.alert({
                title: 'Aviso',
                content: 'Debe seleccionar al menos un producto para devolver.',
                type: 'orange'
            });

            return;
        }

        /*
         * Si no existen productos itemeables,
         * posteriormente enviaremos directamente al backend.
         */
        if (itemableLines.length === 0) {
            prepararEnvioNotaCreditoParcial(selectedLines);
            return;
        }

        /*
         * Si existen productos itemeables,
         * abrimos el segundo modal.
         */
        renderNcPartialItemSelection(itemableLines);

        $('#modalNotaCreditoParcial').one('hidden.bs.modal', function () { $('#modalNcPartialItems').modal('show'); }).modal('hide');
    });

    $(document).on('change', '.nc-partial-item-checkbox', function () {

        const checkbox = $(this);

        const saleDetailId = parseInt(
            checkbox.data('sale_detail_id')
        );

        const requiredQty = parseInt(
            checkbox.data('required_qty')
        );

        const group = $(
            '[data-itemable-group][data-sale_detail_id="' +
            saleDetailId +
            '"]'
        );

        const checkedItems = group.find(
            '.nc-partial-item-checkbox:checked'
        );

        if (checkedItems.length > requiredQty) {

            checkbox.prop('checked', false);

            $.alert({
                title: 'Selección inválida',
                content:
                    'Solo debe seleccionar ' +
                    requiredQty +
                    ' item(s) para este producto.',
                type: 'orange'
            });
        }

        updateNcPartialItemCounters();
        reorderNcPartialSelectedItems();
    });

    $(document).on('click', '#btn-confirm-nc-partial-items', function () {

        let valid = true;
        let selections = {};

        $('[data-itemable-group]').each(function () {

            const group = $(this);

            const saleDetailId = parseInt(
                group.data('sale_detail_id')
            );

            const requiredQty = parseInt(
                group.data('required_qty')
            );

            const selectedItemIds = [];

            group.find('.nc-partial-item-checkbox:checked')
                .each(function () {
                    selectedItemIds.push(
                        parseInt($(this).data('item_id'))
                    );
                });

            if (selectedItemIds.length !== requiredQty) {
                valid = false;

                $.alert({
                    title: 'Selección incompleta',
                    content:
                        'Debe seleccionar exactamente '
                        + requiredQty
                        + ' item(s) para cada producto.',
                    type: 'orange'
                });

                return false;
            }

            selections[saleDetailId] = selectedItemIds;
        });

        if (!valid) {
            return;
        }

        ncPartialSelectedItems = selections;



        /*
         * En el siguiente paso aquí prepararemos el payload
         * y enviaremos la Nota de Crédito al backend.
         */
        prepararEnvioNotaCreditoParcial();
    });


        $('#nc_partial_item_search').on('keydown',function(e){if(e.key==='Enter'){e.preventDefault();searchAndSelectNcPartialItem();}});
        $('#btn-search-nc-partial-item').on('click',searchAndSelectNcPartialItem);
        $('#modalNcPartialItems').on('shown.bs.modal',function(){$('#nc_partial_item_search').val('').focus();});
    });
function updateNcPartialItemCounters() {

    $('[data-itemable-group]').each(function () {

        const group = $(this);
        const requiredQty = parseInt(group.data('required_qty')) || 0;

        const selectedQty = group.find(
            '.nc-partial-item-checkbox:checked'
        ).length;

        const counter = group.find('[data-selected-counter]');

        counter.text(selectedQty + ' / ' + requiredQty);

        counter.removeClass(
            'badge-secondary badge-success badge-danger'
        );

        if (selectedQty === requiredQty) {
            counter.addClass('badge-success');
        } else {
            counter.addClass('badge-danger');
        }
    });
}

function renderNcPartialItemSelection(itemableLines) {
    const container = $('#body-nc-partial-item-selection');

    container.empty();

    itemableLines.forEach(function (line) {

        let itemsHtml = '';

        line.available_items.forEach(function (item) {

            const itemId = parseInt(item.item_id);

            const isChecked =
                Array.isArray(ncPartialSelectedItems[line.sale_detail_id]) &&
                ncPartialSelectedItems[line.sale_detail_id].includes(itemId);

            const itemCode = item.code || '';

            itemsHtml += `
                <div class="nc-partial-item-row border rounded p-2 mb-2 ${isChecked ? 'border-success bg-light' : ''}"
                     data-item-row
                     data-item_id="${itemId}"
                     data-item-code="${escapeHtml(itemCode)}"
                     data-sale_detail_id="${line.sale_detail_id}">

                    <div class="custom-control custom-checkbox">

                        <input type="checkbox"
                               class="custom-control-input nc-partial-item-checkbox"
                               id="nc_item_${line.sale_detail_id}_${itemId}"
                               data-sale_detail_id="${line.sale_detail_id}"
                               data-item_id="${itemId}"
                               data-item_code="${escapeHtml(itemCode)}"
                               data-required_qty="${line.quantity}"
                               ${isChecked ? 'checked' : ''}>

                        <label class="custom-control-label"
                               for="nc_item_${line.sale_detail_id}_${itemId}">

                            <strong>
                                ${escapeHtml(itemCode || 'SIN CÓDIGO')}
                            </strong>

                            <br>

                            <small class="text-muted">
                                ${escapeHtml(item.description)}
                            </small>

                        </label>

                    </div>

                </div>
            `;
        });

        container.append(`
            <div class="card mb-3"
                 data-itemable-group
                 data-sale_detail_id="${line.sale_detail_id}"
                 data-required_qty="${line.quantity}">

                <div class="card-header">

                    <strong>
                        ${escapeHtml(line.description)}
                    </strong>

                    <br>

                    <small>
                        Debe seleccionar:
                        <strong>${line.quantity}</strong>
                    </small>

                    <span class="badge badge-secondary float-right"
                          data-selected-counter>
                        0 / ${line.quantity}
                    </span>

                </div>

                <div class="card-body next-form-section"
                     data-item-list>

                    ${itemsHtml || `
                        <div class="alert alert-warning mb-0">
                            No existen items disponibles para seleccionar.
                        </div>
                    `}

                </div>

            </div>
        `);
    });

    updateNcPartialItemCounters();
    reorderNcPartialSelectedItems();

    $('#nc_partial_item_search').val('');
    $('#nc-partial-item-search-message').empty();
}

function normalizeNcPartialItemCode(value) {
    return String(value || '')
        .trim()
        .toUpperCase();
}

function searchAndSelectNcPartialItem() {

    const searchInput = $('#nc_partial_item_search');
    const searchCode = normalizeNcPartialItemCode(
        searchInput.val()
    );

    const messageContainer = $('#nc-partial-item-search-message');

    messageContainer.empty();

    if (searchCode === '') {
        messageContainer.html(`
            <div class="alert alert-warning py-2">
                Ingrese o pistolee un código.
            </div>
        `);

        searchInput.focus();
        return;
    }

    let foundRow = null;

    $('[data-item-row]').each(function () {

        const row = $(this);

        const itemCode = normalizeNcPartialItemCode(
            row.attr('data-item-code')
        );

        if (itemCode === searchCode) {
            foundRow = row;
            return false;
        }
    });

    if (!foundRow) {
        messageContainer.html(`
            <div class="alert alert-danger py-2">
                No se encontró un item disponible con el código
                <strong>${escapeHtml(searchCode)}</strong>.
            </div>
        `);

        searchInput.select();
        return;
    }

    const checkbox = foundRow.find(
        '.nc-partial-item-checkbox'
    );

    if (checkbox.is(':checked')) {
        messageContainer.html(`
            <div class="alert alert-info py-2">
                El item
                <strong>${escapeHtml(searchCode)}</strong>
                ya se encuentra seleccionado.
            </div>
        `);

        foundRow[0].scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });

        searchInput.val('').focus();
        return;
    }

    const saleDetailId = parseInt(
        checkbox.data('sale_detail_id')
    );

    const requiredQty = parseInt(
        checkbox.data('required_qty')
    );

    const group = $(
        '[data-itemable-group][data-sale_detail_id="' +
        saleDetailId +
        '"]'
    );

    const selectedQty = group.find(
        '.nc-partial-item-checkbox:checked'
    ).length;

    if (selectedQty >= requiredQty) {
        messageContainer.html(`
            <div class="alert alert-warning py-2">
                Ya se seleccionó la cantidad requerida para este producto.
                Desmarque un item antes de seleccionar otro.
            </div>
        `);

        searchInput.select();
        return;
    }

    checkbox.prop('checked', true).trigger('change');

    messageContainer.html(`
        <div class="alert alert-success py-2">
            Item
            <strong>${escapeHtml(searchCode)}</strong>
            seleccionado correctamente.
        </div>
    `);

    /*
     * Luego de reordenar, volvemos a encontrar la fila,
     * ya que cambió de posición en el DOM.
     */
    setTimeout(function () {

        let selectedRow = null;

        $('[data-item-row]').each(function () {

            const row = $(this);

            if (
                normalizeNcPartialItemCode(
                    row.attr('data-item-code')
                ) === searchCode
            ) {
                selectedRow = row;
                return false;
            }
        });

        if (selectedRow && selectedRow.length) {
            selectedRow[0].scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

    }, 100);

    searchInput.val('').focus();
}

function escapeSelectorValue(value) {
    if (window.CSS && CSS.escape) {
        return CSS.escape(String(value));
    }

    return String(value).replace(
        /([ !"#$%&'()*+,./:;<=>?@[\\\]^`{|}~])/g,
        '\\$1'
    );
}

function reorderNcPartialSelectedItems() {

    $('[data-itemable-group]').each(function () {

        const group = $(this);
        const itemList = group.find('[data-item-list]');

        const selectedRows = itemList
            .find('[data-item-row]')
            .filter(function () {
                return $(this)
                    .find('.nc-partial-item-checkbox')
                    .is(':checked');
            });

        const unselectedRows = itemList
            .find('[data-item-row]')
            .filter(function () {
                return !$(this)
                    .find('.nc-partial-item-checkbox')
                    .is(':checked');
            });

        selectedRows.each(function () {
            $(this)
                .addClass('border-success bg-light')
                .removeClass('border-secondary');

            itemList.append(this);
        });

        unselectedRows.each(function () {
            $(this)
                .removeClass('border-success bg-light');

            itemList.append(this);
        });
    });
}


})(jQuery);
