<div id="quote-sale-action-context" hidden data-tenant="{{ \App\Support\TenantContext::tenantId() }}" data-company="{{ \App\Support\TenantContext::companyId() }}" data-branch="{{ \App\Support\TenantContext::branchId() }}"></div>
<input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">
<section class="next-operational-list" aria-label="Listado de cotizaciones">
    <form action="#" class="next-list-toolbar" role="search">
        <div class="next-list-search-row">
            <div class="input-group next-list-search-control">
                <input type="text" id="description_quote" class="form-control" placeholder="Buscar por descripción de cotización" autocomplete="off" aria-label="Descripción de cotización">
                <div class="input-group-append"><button class="btn btn-primary next-list-search" type="button" id="btn-search"><i class="fas fa-search mr-1" aria-hidden="true"></i> Buscar</button></div>
            </div>
            <button type="button" id="btnBusquedaAvanzada" class="btn btn-link next-list-advanced-toggle" aria-controls="quote-advanced-filters" aria-expanded="false"><i class="fas fa-sliders-h" aria-hidden="true"></i><span>Filtros avanzados</span></button>
        </div>
        <div class="busqueda-avanzada" id="quote-advanced-filters">
            <div class="next-list-filters-header"><div><strong>Filtros avanzados</strong><span>Consulta las cotizaciones del local activo.</span></div></div>
            <div class="row next-list-filters-grid">
                <div class="col-md-6 col-lg-3 next-list-filter"><label for="year">Año de cotización</label><select id="year" class="form-control form-control-sm select2"><option value="">TODOS</option>@foreach($arrayYears as $year)<option value="{{ $year }}">{{ $year }}</option>@endforeach</select></div>
                <div class="col-md-6 col-lg-3 next-list-filter"><label for="code">Código de cotización</label><input type="text" id="code" class="form-control form-control-sm" autocomplete="off"></div>
                <div class="col-md-6 col-lg-3 next-list-filter"><label for="order">Orden de compra / servicio</label><input type="text" id="order" class="form-control form-control-sm" autocomplete="off"></div>
                <div class="col-md-6 col-lg-3 next-list-filter"><label for="customer">Cliente</label><select id="customer" name="customer" class="form-control form-control-sm select2"><option value="">TODOS</option>@foreach($arrayCustomers as $customer)<option value="{{ $customer['id'] }}">{{ $customer['business_name'] }}</option>@endforeach</select></div>
                <div class="col-md-6 col-lg-3 next-list-filter"><label for="stateQuote">Estado</label><select id="stateQuote" name="stateQuote" class="form-control form-control-sm select2"><option value="">TODOS</option>@foreach($arrayStates as $state)<option value="{{ $state['value'] }}">{{ $state['display'] }}</option>@endforeach</select></div>
                <div class="col-md-6 col-lg-6 next-list-filter" id="sandbox-container"><label for="start">Fechas de cotización</label><div class="input-daterange input-group next-list-date-range" id="datepicker"><input type="text" class="form-control form-control-sm date-range-filter" id="start" name="start" autocomplete="off" aria-label="Fecha inicial"><div class="input-group-prepend input-group-append"><span class="input-group-text">al</span></div><input type="text" class="form-control form-control-sm date-range-filter" id="end" name="end" autocomplete="off" aria-label="Fecha final"></div></div>
                <div class="col-md-6 col-lg-3 next-list-filter"><label for="creator">Creador</label><select id="creator" name="creator" class="form-control form-control-sm select2"><option value="">TODOS</option>@foreach($arrayUsers as $creator)<option value="{{ $creator['id'] }}">{{ $creator['name'] }}</option>@endforeach</select></div>
            </div>
        </div>
    </form>
    <div class="next-list-summary">
        <div class="next-list-summary-copy"><h3><span id="numberItems" class="next-list-count"></span> cotizaciones</h3><span class="next-list-sort-context">Fecha de creación · más recientes primero</span></div>
        <a href="{{ route('quoteSale.pendingBranches') }}" class="btn btn-outline-secondary btn-sm">Históricos sin sucursal</a>
    </div>
    <div class="next-list-content">
        <div class="table-responsive" tabindex="0" role="region" aria-label="Cotizaciones, tabla con desplazamiento horizontal">
            <table class="table table-bordered table-hover table-sm next-data-table">
                <thead><tr>
                    <th scope="col" class="text-center">ID</th><th scope="col" class="text-center">Código</th><th scope="col" class="text-left">Descripción</th><th scope="col" class="text-center">Fecha</th><th scope="col" class="text-left">Forma de pago</th><th scope="col" class="text-center">Entrega</th><th scope="col" class="text-left">Cliente</th><th scope="col" class="text-center">Orden</th><th scope="col" class="text-right">Total SUNAT</th><th scope="col" class="text-right">Servicios ad.</th><th scope="col" class="text-right">Total cliente</th><th scope="col" class="text-right">Moneda</th><th scope="col" class="text-center">Estado</th><th scope="col" class="text-left">Creador</th><th scope="col" class="text-center">Acciones</th>
                </tr></thead>
                <tbody id="body-table" aria-live="polite"></tbody>
            </table>
        </div>
        <div class="next-list-pagination"><div id="textPagination" role="status"></div><nav aria-label="Páginas de cotizaciones"><ul class="pagination" id="pagination"></ul></nav></div>
    </div>
</section>
<template id="previous-page"><li class="page-item previous"><a href="#" class="page-link" data-item aria-label="Página anterior"><i class="fas fa-chevron-left" aria-hidden="true"></i></a></li></template>
<template id="item-page"><li class="page-item" data-active><a href="#" class="page-link" data-item></a></li></template>
<template id="next-page"><li class="page-item next"><a href="#" class="page-link" data-item aria-label="Página siguiente"><i class="fas fa-chevron-right" aria-hidden="true"></i></a></li></template>
<template id="disabled-page"><li class="page-item disabled"><span class="page-link">…</span></li></template>
<template id="item-table"><tr>
    <td data-id class="text-center"></td><td data-code class="text-center"></td><td data-description class="text-left"></td><td data-date_quote class="text-center text-nowrap"></td><td data-deadline class="text-left"></td><td data-time_delivery class="text-center"></td><td data-customer class="text-left"></td><td data-order class="text-center"></td><td data-total_sunat class="text-right text-nowrap"></td><td data-services_add class="text-right text-nowrap"></td><td data-total class="text-right text-nowrap"></td><td data-currency class="text-right"></td><td data-state class="text-center"></td><td data-creator class="text-left"></td><td data-buttons class="text-center"></td>
</tr></template>
<template id="item-table-empty"><tr><td colspan="15" class="text-center"><div class="next-table-empty"><i class="fas fa-search" aria-hidden="true"></i><p>No se encontraron cotizaciones con estos filtros.</p></div></td></tr></template>
<div id="modalDecimals" class="modal fade next-aux-modal" tabindex="-1" aria-labelledby="quote-decimals-title" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h4 class="modal-title" id="quote-decimals-title">Visualización de decimales</h4><button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>
        <form id="formDecimals" class="next-aux-modal-form" data-url="{{ route('quoteSale.decimals.change') }}">
            @csrf
            <div class="modal-body"><input type="hidden" id="quote_id" name="quote_id"><label for="decimals">Mostrar decimales</label><select id="decimals" name="decimals" class="form-control select2"><option value=""></option><option value="1">Mostrar decimales</option><option value="0">Ocultar decimales</option></select></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="button" id="btn-changeDecimals" class="btn btn-primary">Guardar</button></div>
        </form>
    </div></div>
</div>
