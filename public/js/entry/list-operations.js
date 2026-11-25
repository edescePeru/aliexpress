/* R4. Presentation adapter; authorization and stock/finance rules remain in backend. */
(function ($) {
    'use strict';
    var invoiceRows = Object.create(null), detailRequest, detailVersion = 0;
    function safe(value) { return $('<span>').text(value == null ? '' : String(value)).html(); }
    function can(permission) { return JSON.parse($('#entry-invoice-permissions').val() || '[]').indexOf(permission) !== -1; }
    function documentItem(filename, kind, label) {
        if (typeof filename !== 'string' || !filename || /^no_image\.(png|jpg)$/i.test(filename)
            || /[\x00-\x1f\x7f\\/]/.test(filename) || !/\.(pdf|jpe?g|png|gif|webp)$/i.test(filename)) return '';
        var template = $('#entry-document-routes').attr('data-' + kind);
        if (!template || template.indexOf('__ENTRY_FILE__') === -1) return '';
        return item(label, /\.pdf$/i.test(filename) ? 'fa-file-pdf' : 'fa-image', {
            href: template.replace('__ENTRY_FILE__', encodeURIComponent(filename)), target: '_blank', rel: 'noopener', 'data-entry-document': kind
        });
    }
    function item(label, icon, attrs, destructive) {
        var tag = attrs.href ? 'a' : 'button';
        return '<' + tag + (tag === 'button' ? ' type="button"' : '') + ' class="dropdown-item' + (destructive ? ' text-danger' : '') + '" role="menuitem" ' + Object.keys(attrs).map(function (key) { return key + '="' + safe(attrs[key]) + '"'; }).join(' ') + '><i class="fas ' + icon + ' next-row-action-item-icon" aria-hidden="true"></i><span>' + label + '</span></' + tag + '>';
    }
    window.EntryInvoiceOperations = {
        render: function (row, mode) {
            var id = encodeURIComponent(row.id), links = '';
            if (mode === 'orders') {
                if (can('list_orderPurchaseNormal')) {
                    links += item('Ver orden', 'fa-eye', {href:'/dashboard/ver/orden/compra/normal/' + id, 'data-ver_orden':''});
                    links += item('Imprimir orden', 'fa-print', {href:'/dashboard/imprimir/orden/compra/' + id, target:'_blank', rel:'noopener', 'data-imprimir':''});
                }
                if (row.can_receive === true && can('create_entryPurchase')) links += item('Recibir productos', 'fa-dolly', {href:'/dashboard/crear/entrada/compra/orden/' + id, 'data-crear_entrada':''});
            } else if (mode === 'entry' || mode === 'report') {
                if (can('list_entryPurchase')) {
                    links += item('Ver detalles', 'fa-eye', {'data-detail':row.id});
                    links += documentItem(row.image, 'main', 'Ver adjunto');
                    links += documentItem(row.imageOb, 'observation', 'Ver observación');
                }
                if (mode === 'entry' && can('update_entryPurchase')) {
                    var extras = $('#entry-document-routes').attr('data-extras');
                    if (extras && extras.indexOf('__ENTRY_ID__') !== -1) links += item('Documentos extras', 'fa-folder-open', {href:extras.replace('__ENTRY_ID__', id), 'data-documentos':''});
                }
                // ENTRY-R4-01: purchase deletion lacks an explicit finance=false boundary.
            } else {
                invoiceRows[row.id] = row;
                if (can('list_invoice')) {
                    links += item('Ver factura', 'fa-eye', {href:'/dashboard/factura/compra/ver/' + id});
                    // The scoped list already returns details. No unguarded auxiliary endpoint is needed.
                    links += item('Ver detalles', 'fa-search', {'data-details':row.id, 'data-code':'0'});
                    links += documentItem(row.image, 'main', 'Ver adjunto');
                    links += documentItem(row.imageOb, 'observation', 'Ver observación');
                }
                if (mode === 'invoice-report' && can('update_invoice') && !Number(row.state_annulled)) links += item('Editar factura', 'fa-pen', {href:'/dashboard/factura/compra/editar/' + id});
                var reportCondition = row.purchase_order == null || String(row.purchase_order).charAt(0) === '0';
                if ((mode === 'invoice' || (mode === 'invoice-report' && reportCondition)) && !Number(row.state_annulled) && can('destroy_invoice')) links += item('Anular factura', 'fa-trash', {'data-delete':row.id, 'data-entry-operation':'invoice'}, true);
            }
            if (!links) return '—';
            var trigger = 'entry-actions-' + mode + '-' + row.id;
            return '<div class="dropdown next-row-actions"><button type="button" id="' + safe(trigger) + '" class="btn btn-sm next-row-actions-trigger" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false" aria-label="Acciones de registro ' + safe(row.id) + '"><i class="fas fa-ellipsis-h" aria-hidden="true"></i></button><div class="dropdown-menu dropdown-menu-right next-row-actions-menu" role="menu" aria-labelledby="' + safe(trigger) + '">' + links + '</div></div>';
        }
    };
    function error(xhr) {
        var body = xhr.responseJSON || {}, messages = Object.keys(body.errors || {}).map(function (key) { return [].concat(body.errors[key]).join(' '); });
        return messages.join(' ') || body.message || 'No se pudo completar la operación. Intente nuevamente.';
    }
    function table(title, columns, rows) {
        var html = '<h6>' + safe(title) + '</h6><div class="table-responsive" tabindex="0"><table class="table table-bordered table-hover table-sm next-data-table"><thead><tr>';
        columns.forEach(function (column) { html += '<th class="' + column[2] + '">' + safe(column[0]) + '</th>'; });
        html += '</tr></thead><tbody>';
        rows.forEach(function (row) { html += '<tr>'; columns.forEach(function (column) { html += '<td class="' + column[2] + '">' + safe(row[column[1]]) + '</td>'; }); html += '</tr>'; });
        if (!rows.length) html += '<tr><td colspan="' + columns.length + '">Sin registros.</td></tr>';
        return html + '</tbody></table></div>';
    }
    function openDetails(title) {
        detailVersion++;
        if (detailRequest) detailRequest.abort();
        $('#entryItemsTitle').text(title);
        $('#entry-items-content').empty(); $('#entry-items-status').removeClass('text-danger').text('Cargando…');
        $('#modalItems').modal('show');
        return detailVersion;
    }
    $(function () {
        $(document).on('click.entryOperations', '[data-detail]', function () {
            var version = openDetails('Detalles de entrada ' + $(this).attr('data-detail'));
            detailRequest = $.getJSON('/dashboard/get/json/items/' + encodeURIComponent($(this).attr('data-detail'))).done(function (data) {
                if (version !== detailVersion) return;
                if (!data || !Array.isArray(data.details) || !Array.isArray(data.items)) { $('#entry-items-status').addClass('text-danger').text('Respuesta inesperada del servidor.'); return; }
                $('#entry-items-status').empty();
                $('#entry-items-content').html(table('Líneas', [['Material','material','text-left'],['Código','code','text-center'],['Cantidad','ordered_quantity','text-center'],['Precio','unit_price','text-right']], data.details) + table('Ítems', [['ID','id','text-center'],['Material','material','text-left'],['Código','code','text-center'],['Largo','length','text-center'],['Ancho','width','text-center'],['Peso','weight','text-center'],['Precio','price','text-right'],['Ubicación','location','text-left'],['Estado','state','text-center']], data.items));
            }).fail(function (xhr, status) { if (status !== 'abort' && version === detailVersion) $('#entry-items-status').addClass('text-danger').text(error(xhr)); });
        });
        $(document).on('click.entryOperations', '[data-details]', function () {
            var row = invoiceRows[$(this).attr('data-details')];
            openDetails('Detalles de factura ' + (row ? row.invoice || row.id : ''));
            if (!row || !Array.isArray(row.details)) { $('#entry-items-status').addClass('text-danger').text('No se recibieron las líneas. Recargue el listado.'); return; }
            $('#entry-items-status').text(row.currency_invoice || '');
            $('#entry-items-content').html(table('Líneas', [['Descripción','material_description','text-left'],['Cantidad','entered_quantity','text-center'],['Unidad','unit','text-center'],['Precio','unit_price','text-right'],['Subtotal','sub_total','text-right'],['Impuestos','taxes','text-right'],['Total','total','text-right']], row.details) + table('Resumen', [['Subtotal','sub_total','text-right'],['Impuestos','taxes','text-right'],['Total','total','text-right']], [row]));
        });
        $('#modalItems').on('hidden.bs.modal', function () { detailVersion++; if (detailRequest) detailRequest.abort(); });
        $(document).on('click.entryOperations', '[data-entry-operation]', function () {
            var button = $(this);
            if (button.attr('data-entry-operation') !== 'invoice') return;
            if (button.data('confirming') || button.data('busy')) return;
            button.data('confirming', true);
            $.confirm({title:'¿Anular esta factura?', content:'Se anulará la factura y se revertirá el egreso según las validaciones de caja.', onDestroy:function () { button.removeData('confirming'); }, buttons:{confirm:{text:'Confirmar', btnClass:'btn-danger', action:function () {
                if (button.data('busy')) return;
                button.data('busy', true).prop('disabled', true);
                $.ajax({url:'/dashboard/destroy/total/invoice/' + encodeURIComponent(button.attr('data-delete')), method:'POST', headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}, processData:false, contentType:false})
                    .done(function (data) { $.alert(safe(data.message || 'Operación completada.')); $(document).trigger('entry-invoice:refresh'); })
                    .fail(function (xhr) { $.alert(safe(error(xhr))); })
                    .always(function () { button.removeData('busy').prop('disabled', false); });
            }}, cancel:{text:'Cancelar'}}});
        });
        $('#btn-export').on('click.entryOperations', function () {
            var query = {start:$('#start').val(), end:$('#end').val()};
            function download() { window.location.href = '/dashboard/exportar/reporte/factura?' + $.param(query); }
            if (!query.start || !query.end) $.confirm({title:'Exportar facturas', content:'El servidor exportará las facturas de la empresa según el rango indicado. Sin fechas se incluirán todas. La búsqueda local y la condición sin orden no limitan este export.', buttons:{confirm:{text:'Descargar', action:download}, cancel:{text:'Cancelar'}}});
            else download();
        });
    });
})(jQuery);
