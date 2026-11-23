/* Historical GETs only. No restore, regularization, documents or active-detail navigation. */
$(function () {
    'use strict';
    const root = document.getElementById('finance-history');
    if (!root) return;
    const deleted = root.dataset.mode === 'deleted';
    const permissions = JSON.parse($('#permissions').val() || '[]');
    const escape = value => $('<div>').text(value == null || value === '' ? '—' : String(value)).html();
    const text = getter => (row, type) => {
        const value = getter(row);
        return type === 'display' ? escape(value) : (value == null ? '' : value);
    };
    const date = field => (row, type) => {
        const value = row[field] ? String(row[field]).slice(0, 10) : '';
        return type === 'display' ? escape(value ? value.split('-').reverse().join('/') : null) : value;
    };
    let loaded = false;
    const columns = [
        {data:null, className:'text-center', render:text(r => r.id)},
        {data:null, className:'text-center', render:text(r => r.code)},
        {data:null, className:'text-center', render:date('date_order')},
        {data:null, className:'text-center', render:date('date_delivery')},
        {data:null, render:text(r => r.observation)},
        {data:null, render:text(r => r.supplier ? r.supplier.business_name : 'No disponible')},
        {data:null, render:text(r => r.approved_user ? r.approved_user.name : 'Sin aprobar')},
        {data:null, className:'text-center', render:text(r => r.currency_order)},
        {data:null, className:'text-right', render:(row, type) => permissions.includes('showPrices_quote') ? text(r => r.total)(row, type) : ''},
        {data:null, className:'text-center', render:function (row, type) {
            const label = deleted ? 'Eliminada' : 'Regularizada';
            return type === 'display' ? '<span class="badge badge-' + (deleted ? 'danger' : 'success') + '">' + label + '</span>' : label;
        }}
    ];
    if (deleted) columns.push({data:'id', className:'text-center', orderable:false, searchable:false, render:function (id, type) {
        if (type !== 'display' || !permissions.includes('list_orderPurchaseFinance')) return '';
        return '<a class="btn btn-outline-secondary btn-sm next-table-cell-action" href="' + escape(root.dataset.detailUrl + '/' + encodeURIComponent(id)) + '" title="Ver detalle histórico" aria-label="Ver detalle histórico"><i class="fas fa-eye" aria-hidden="true"></i></a>';
    }});
    const table = $('#dynamic-table').DataTable({
        data:[], autoWidth:false, order:[], pageLength:10, columns:columns,
        dom:'<"next-list-toolbar d-flex flex-wrap justify-content-between"lf><"table-responsive"t><"next-list-pagination"ip>',
        drawCallback:function () {
            if (!loaded) return;
            const api = this.api();
            const total = api.rows().count();
            const filtered = api.rows({search:'applied'}).count();
            $('#history-status').text(!total ? 'Esta empresa no tiene órdenes financieras ' + (deleted ? 'eliminadas.' : 'regularizadas.') : (!filtered ? 'No hay órdenes que coincidan con la búsqueda.' : filtered + ' órdenes · Empresa activa'));
        },
        language:{lengthMenu:'Mostrar _MENU_', search:'Buscar:', emptyTable:'No hay órdenes para mostrar.', zeroRecords:'No se encontraron coincidencias.', info:'Mostrando _START_ a _END_ de _TOTAL_ órdenes', infoEmpty:'0 órdenes', infoFiltered:'(de _MAX_ órdenes)', paginate:{first:'Primera', last:'Última', next:'Siguiente', previous:'Anterior'}}
    });
    $('#dynamic-table_filter').addClass('mw-100').find('label').addClass('d-flex flex-wrap align-items-center mb-0');
    $('#dynamic-table_filter input').addClass('ml-0 mw-100');
    $('#dynamic-table').closest('.table-responsive').attr({tabindex:'0', 'aria-label':'Órdenes financieras; desplazamiento horizontal local'});
    $.ajax({url:root.dataset.url, type:'GET', dataType:'json'}).done(function (response) {
        if (!response || !Array.isArray(response.data)) { fail('El servidor devolvió una respuesta inesperada.'); return; }
        loaded = true;
        table.rows.add(response.data).draw();
    }).fail(function (xhr) {
        fail(xhr.status === 403 ? 'No tienes permiso o un contexto válido para consultar estas órdenes (HTTP 403).' : 'No se pudieron consultar las órdenes (HTTP ' + xhr.status + '). Recarga la página para intentar nuevamente.');
    });
    function fail(message) {
        $('#history-results').addClass('d-none');
        $('#history-error').text(message).removeClass('d-none');
        $('#history-status').text('Consulta fallida. No se han cargado resultados.');
    }
});
