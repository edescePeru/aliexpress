(function () {
    'use strict';
    const root = document.getElementById('supplier-credits');
    if (!root) return;
    const pending = root.dataset.mode === 'pending';
    const error = document.getElementById('credit-error');
    const esc = value => $('<div>').text(value === null || value === undefined || value === '' ? '—' : String(value)).html();
    const date = value => value ? String(value).slice(0, 10).split('-').reverse().join('/') : '';
    const states = {outstanding:'Pendiente', by_expire:'Por vencer', expired:'Vencido', paid_out:'Pagado'};
    const badge = value => '<span class="badge badge-' + (value === 'expired' ? 'danger' : value === 'by_expire' ? 'warning' : value === 'paid_out' ? 'success' : 'secondary') + '">' + esc(states[value] || value) + '</span>';
    const columns = pending ? [
        ['Orden','order','center'],['Correlativo','correlativo','center'],['Proveedor','proveedor'],['Moneda','moneda','center'],['Condición','condicion'],
        ['Monto USD','montoDolares','right'],['Monto PEN','montoSoles','right'],['Saldo USD','deudaActualDolares','right'],['Saldo PEN','deudaActualSoles','right'],
        ['Pagado','adelanto','right'],['Saldo actual','deudaActual','right'],['Factura','factura','center'],['Fecha emisión','fechaEmision','center'],
        ['Vencimiento','fechaVencimiento','center'],['Vence en','estado','center'],['Estado pago','estadoPago','center'],['Fecha pago','fechaPago','center'],['Observaciones','observaciones'],['Estado crédito','stateCredit','center']
    ] : [
        ['Proveedor',r=>r.supplier && r.supplier.business_name],['Factura','invoice','center'],['Orden de compra','code_order','center'],['Importe PEN','total_soles','right'],['Importe USD','total_dollars','right'],
        ['Fecha emisión',r=>date(r.date_issue),'center'],['Vencimiento',r=>date(r.date_expiration),'center'],['Plazo de pago',r=>r.deadline && r.deadline.description],
        ['Días para vencer','days_to_expiration','center'],['Observación','observation'],['Estado crédito','state_credit','center'],['Observación adicional','observation_extra'],['Fecha pago',r=>date(r.date_paid),'center']
    ];
    columns.forEach(c => $('<th>').addClass(c[2] ? 'text-'+c[2] : '').text(c[0]).appendTo('#dynamic-table2 thead tr'));
    $('<th class="text-center">Consulta</th>').appendTo('#dynamic-table2 thead tr');
    $.fn.dataTable.ext.errMode = 'none';
    $.fn.dataTable.ext.search.push(function (settings, data, index, row) {
        if (settings.nTable.id !== 'dynamic-table2') return true;
        const from = $('#start2').val(), to = $('#end2').val();
        if (!from || !to) return true; // Existing range contract requires both bounds.
        const raw = pending ? (row.fechaEmision || '').split('/').reverse().join('-') : String(row.date_issue || '').slice(0,10);
        return raw >= from && raw <= to;
    });
    const table = $('#dynamic-table2').DataTable({
        autoWidth:false, pageLength:10,
        drawCallback:function(){const api=this.api(), count=api.rows({search:'applied'}).count();$('#credit-empty').toggleClass('d-none',count!==0).text(api.rows().count()?'No hay créditos que coincidan con la búsqueda.':'No hay créditos registrados en esta empresa.');}, order:[[pending ? 12 : 5,'desc']],
        ajax:{url:root.dataset.url, dataSrc:'data', error:function(xhr){$('#credit-empty').addClass('d-none');error.classList.remove('d-none');error.textContent='No se pudieron consultar los créditos (HTTP '+xhr.status+').';}},
        columns:columns.map(c=>({data:null,className:c[2]?'text-'+c[2]:'',render:function(row,type){const value=typeof c[1]==='function'?c[1](row):row[c[1]];if(type!=='display')return value==null?'':value;return ['state_credit','stateCredit'].includes(c[1])?badge(value):esc(value);}})).concat([{data:null,orderable:false,searchable:false,className:'text-center',render:()=>'<button type="button" class="btn btn-outline-secondary btn-sm" data-credit-detail>Ver detalle</button>'}]),
        language:{emptyTable:'No hay créditos registrados en esta empresa.',zeroRecords:'No hay créditos que coincidan con la búsqueda.',search:'Buscar:',lengthMenu:'Mostrar _MENU_',info:'Mostrando _START_ a _END_ de _TOTAL_ créditos',infoEmpty:'0 créditos',infoFiltered:'(de _MAX_ créditos)',loadingRecords:'Cargando créditos…',paginate:{first:'Primera',last:'Última',next:'Siguiente',previous:'Anterior'}}
    });
    $('#start2,#end2').on('change',()=>table.draw());
    if(pending) columns.forEach((c,i)=>{const wrap=$('<div class="col-sm-6 col-lg-3 mb-2">');const label=$('<label class="mb-0">');const input=$('<input type="checkbox" checked>').attr('aria-label',c[0]);input.on('change',()=>table.column(i).visible(input.prop('checked')));label.append(input,document.createTextNode(' '+c[0]));wrap.append(label).appendTo('#credit-columns');});
    let version=0;
    $('#dynamic-table2').on('click','[data-credit-detail]',function(){
        const row=table.row($(this).closest('tr')).data(); const current=++version;
        $('#credit-detail-body').text('Cargando detalle…');$('#credit-pays').text('Cargando historial…');$('#credit-detail').modal('show');
        $.getJSON(root.dataset.detailUrl+'/'+encodeURIComponent(row.id)).done(function(res){
            if(current!==version)return;
            const c=res.credit, dl=$('<dl class="row mb-0">');
            const values=[['Proveedor',c.supplier && c.supplier.business_name],['Factura',c.invoice],['Referencia',c.code_order],['Importe PEN',c.total_soles],['Importe USD',c.total_dollars],['Emisión',date(c.date_issue)],['Vencimiento',date(c.date_expiration)],['Plazo',c.deadline && c.deadline.description],['Observación',c.observation],['Observación adicional',c.observation_extra]];
            if(pending)values.push(['Moneda',row.moneda],['Pagado (backend)',row.adelanto],['Saldo (backend)',row.deudaActual],['Estado proyectado',states[row.stateCredit]||row.stateCredit],['Revisión de anticipo histórico',row.legacyAdvancePendingReview?'Pendiente':'No indicada']);
            values.forEach(([label,value])=>dl.append($('<div class="col-sm-6 mb-3">').append($('<dt class="text-muted">').text(label),$('<dd class="text-break mb-0">').text(value==null||value===''?'No registrado':value))));
            $('#credit-detail-body').empty().append(dl);
        }).fail(xhr=>{if(current===version)$('#credit-detail-body').text('No se pudo consultar el detalle (HTTP '+xhr.status+').');});
        $.getJSON(root.dataset.paysUrl+'/'+encodeURIComponent(row.id)).done(function(res){
            if(current!==version)return;
            const pays=res.pays||[];if(!pays.length){$('#credit-pays').text('Sin pagos registrados.');return;}
            const t=$('<table class="table table-sm table-bordered"><thead><tr><th class="text-center">ID</th><th class="text-center">Fecha</th><th class="text-right">Importe</th></tr></thead><tbody></tbody></table>');
            pays.forEach(p=>t.find('tbody').append($('<tr>').append($('<td class="text-center">').text(p.id),$('<td class="text-center">').text(p.fecha||'No registrado'),$('<td class="text-right">').text(p.monto))));$('#credit-pays').empty().append(t);
        }).fail(xhr=>{if(current===version)$('#credit-pays').text('No se pudo consultar el historial (HTTP '+xhr.status+').');});
    });
    $('#credit-detail').on('hidden.bs.modal',()=>{version++;});
})();