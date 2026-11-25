/* Historical list with contextual restore. Mutation QA is isolated. */
$(function () {
    const permissions = JSON.parse($('#permissions').val() || '[]');
    const text = $.fn.dataTable.render.text();
    const date = function (value, type) { return type === 'display' ? (value ? moment(value).format('DD/MM/YYYY') : 'No registrada') : value; };
    const table = $('#dynamic-table').DataTable({
        data: [], autoWidth: false, order: [], pageLength: 10,
        dom: '<"next-list-toolbar d-flex flex-wrap justify-content-between"lf><"table-responsive"t><"next-list-pagination"ip>',
        columns: [
            {data:'id', className:'text-center', render:text},
            {data:'code', className:'text-center', render:text},
            {data:'date_order', className:'text-center', render:date},
            {data:'date_arrival', className:'text-center', render:date},
            {data:'observation', defaultContent:'', render:text},
            {data:'supplier.business_name', defaultContent:'No disponible', render:text},
            {data:'approved_user.name', defaultContent:'No registrado', render:text},
            {data:'currency_order', className:'text-center', render:text},
            {data:'total', className:'text-right', render:function(value,type){return permissions.includes('showPrices_quote') ? (type === 'display' ? text.display(value) : value) : ''; }},
            {data:'type', className:'text-center', render:function(){return 'Normal';}},
            {data:null, className:'text-center', render:function(){return '<span class="badge badge-danger">ELIMINADA</span>'; }},
            {data:'id', className:'text-center', orderable:false, searchable:false, render:function(id,type,row){
                return type === 'display' ? PurchaseListOperations.render(row, permissions, true) : id;
            }}
        ],
        language:{lengthMenu:'Mostrar _MENU_',search:'Buscar:',emptyTable:'Sin órdenes eliminadas para mostrar.',zeroRecords:'No se encontraron coincidencias.',info:'Mostrando _START_ a _END_ de _TOTAL_ órdenes',infoEmpty:'0 órdenes',infoFiltered:'(de _MAX_ órdenes)',paginate:{first:'Primera',last:'Última',next:'Siguiente',previous:'Anterior'}}
    });
    $('#dynamic-table_filter').addClass('mw-100').find('label').addClass('d-flex flex-wrap align-items-center mb-0');
    $('#dynamic-table_filter input').addClass('ml-0 mw-100');
    $('#dynamic-table').closest('.table-responsive').attr({tabindex:'0','aria-label':'Órdenes eliminadas; desplazamiento horizontal local'});
    $(document).on('purchase:refresh', load);
    load();
    function load() {
        $.ajax({url:'/dashboard/all/order/delete',type:'GET',dataType:'json'}).done(function(response){
            if(!response || !Array.isArray(response.data)) { fail('Respuesta inesperada del servidor.'); return; }
            table.clear().rows.add(response.data).draw();
            $('#trash-error').addClass('d-none').text('');
            $('#trash-status').text(response.data.length ? response.data.length + ' órdenes eliminadas · Empresa activa' : 'Esta empresa no tiene órdenes normales eliminadas.');
        }).fail(function(xhr){fail(xhr.status === 403 ? 'No tienes permiso o un contexto válido para consultar la papelera.' : 'No se pudo cargar la papelera. Recarga la página para intentar nuevamente.');});
    }
    function fail(message){$('#trash-error').text(message).removeClass('d-none');$('#trash-status').text('Consulta fallida. No se han cargado resultados.');}
});
