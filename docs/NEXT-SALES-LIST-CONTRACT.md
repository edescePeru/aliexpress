# Venti Next — Sales Operational List Contract

This document freezes the frontend/backend contract of `puntoVenta.list`
before its formal Venti Next reconstruction. Markup and visual hierarchy may
change, but the hooks and behaviors below require a coordinated JavaScript or
backend update before they can be renamed or removed.

## Entry point and authorization

- Page route: `GET /dashboard/listado/ventas/`
- Route name: `puntoVenta.list`
- Controller: `PuntoVentaController@listar`
- Blade: `resources/views/puntoVenta/list.blade.php`
- JavaScript: `public/js/puntoVenta/list.js`
- Layout: `layouts.appAdmin2`
- Middleware inherited from the dashboard group: `auth`,
  `check.user.enabled`
- The route has no additional permission middleware and the active Blade
  contains no effective `@can`/`@canany` gate. Commented permission fragments
  are not active authorization.

`PuntoVentaController@listar` supplies `arrayYears`, `cashBoxes`, `subtypes`,
and `customers`. Their option values and `data-type`, `data-uses_subtypes`, and
`data-is_deferred` metadata are functional inputs to the filters and payment
workflows.

## Read-only listing contract

The listing loads through:

```text
GET /dashboard/get/data/sales/{page}
```

Query parameters sent by `getDataOrders`/`showData`:

```text
code
year
startDate
endDate
customer_id
payment_status
dispatch_status
cash_box_id
cash_box_subtype_id
invoice_status
sale_status
```

The response must retain:

```json
{
  "data": [],
  "pagination": {
    "currentPage": 1,
    "totalPages": 1,
    "startRecord": 1,
    "endRecord": 10,
    "totalRecords": 10,
    "totalFilteredRecords": 10
  }
}
```

Each sale object is consumed by the renderer, including identifiers, code,
date, customer, currency, total/payment state, payment method, dispatch state,
invoice/SUNAT state, annulment state, print/recovery URLs, credit-note state,
and capability flags. Field names returned by `getSalesAdmin` must not change
without updating `renderTemplateQuotes` and its action branches.

## Immutable listing hooks

### Search and filters

```text
#code
#btn-search
#btnBusquedaAvanzada
.busqueda-avanzada
#year
#start
#end
#filter_customer_id
#filter_payment_status
#filter_dispatch_status
#filter_cash_box_id
#filter_cash_box_subtype_wrap
#filter_cash_box_subtype_id
#filter_invoice_status
#filter_sale_status
```

Select2 remains initialized on all filter selects. Bootstrap Datepicker remains
initialized on `#sandbox-container .input-daterange`. The cash-box filter uses
option metadata to decide whether the subtype wrapper is shown.

### Results, table, and pagination

```text
#numberItems
#textPagination
#pagination
#body-table
#th-dispatch-or-annulled
#previous-page
#item-page
#next-page
#disabled-page
#item-table
#item-table-empty
#template-active
#template-annulled
```

The table contract is nine rendered columns in this exact functional order:
code, sale date, customer, currency, total, payment method, dispatch/annulment,
invoice status, and actions. The source template hooks are:

```text
data-code
data-date
data-customer
data-currency
data-total
data-tipo_pago
data-dispatch_status
data-estado_comprobante
data-buttons
```

Pagination continues to use the four HTML templates and delegated clicks on
`[data-item]`. It is not replaced by Laravel pagination or a third-party table
plugin.

## Dynamic status and action contract

`renderTemplateQuotes` must continue to:

- preserve all current invoice/SUNAT, annulment, credit-note, and discarded
  error texts;
- apply the partial-payment classes `bg-pago-parcial-rojo`,
  `bg-pago-parcial-naranja`, and `bg-pago-parcial-verde` to `data-total`;
- render the dispatch control with `.switch-dispatch-status`, `data-sale_id`,
  and `data-bootstrap-switch`;
- initialize Bootstrap Switch after rows are appended;
- initialize Bootstrap tooltips for dynamic actions;
- preserve conditional insertion/removal of every action.

Action hooks that cannot be renamed without coordinated JavaScript changes:

```text
data-print_recibo
data-recuperar_archivos_nubefact
data-ver_detalles
data-anular
data-consultar_anulacion
data-generar_comprobante
data-pagos_parciales
data-generar_nota_credito
data-consultar_nota_credito
data-generar_nota_credito_parcial
data-ver_nc_parcial_pdf
data-print_anulacion_pdf
data-print_anulacion_xml
data-print_anulacion_cdr
data-ver_error_nota_credito
data-ver_solucion_sunat_error
```

Associated `data-id`, `data-sale_id`, `data-type_document`, `data-free_sale`,
credit-note IDs, response codes, messages, dates, and URLs remain part of the
delegated-event contract.

## Modal and overlay hooks

The following IDs and their descendant form/control IDs remain unchanged:

```text
#orderDetailsModal
#order-details-content
#mapModal
#mapContainer
#modalFacturador
#formFacturador
#modalPagosParciales
#modalGenerarComprobante
#modalNotaCreditoParcial
#modalNcPartialItems
#globalProcessLoader
#globalProcessLoaderMessage
```

The modals support details, invoice data, invoice generation, partial payments,
total/partial credit notes, and related read/confirmation flows. Their save,
annul, payment, credit-note, dispatch, and invoice actions are persistent and
must not be executed during visual QA.

## AJAX and navigation dependencies

The current JavaScript references these server contracts:

- `GET /dashboard/get/data/sales/{page}`
- `GET /dashboard/sales/{orderId}/details`
- `GET /print/order/{orderId}`
- `POST /dashboard/anular/order/{id}`
- `POST /dashboard/consultar/anulacion/{id}`
- free-sale annulment and consultation endpoints under
  `/dashboard/ventas-libres/...`
- `POST /dashboard/sales/update-invoice-data`
- `POST /dashboard/facturador/generar`
- `GET|POST|DELETE /dashboard/sale/partial-payment/...`
- `POST /dashboard/sale/generate-invoice`
- `POST /dashboard/sale/update-dispatch-status`
- `POST /dashboard/generar/nota-credito/total/{id}`
- `GET /dashboard/consultar/nota-credito/{id}`
- `GET /dashboard/nota-credito/parcial/data/{id}`
- `POST /dashboard/generar/nota-credito/parcial/{id}`
- `GET /dashboard/customer/decolecta/{documento}`
- `POST /dashboard/ventas/{sale}/recuperar-archivos-nubefact`

HTTP methods, CSRF headers, payload keys, response fields, callbacks, opened
URLs, and confirmation flows are business contracts and are outside the visual
reconstruction scope.

## Plugin contracts

- Select2: filter and partial-payment selects.
- Bootstrap Datepicker: `#sandbox-container .input-daterange`, Spanish locale.
- Bootstrap Switch: dynamically generated dispatch controls.
- Bootstrap Tooltip: delegated `[data-toggle="tooltip"]` actions.
- Bootstrap Modal: all modal IDs and `data-dismiss`/tab behavior.
- jquery-confirm and Toastr: confirmations, validation, and response feedback.
- Font Awesome: table action and navigation icons.

There is no DataTables dependency on this screen. Rows and pagination are
custom-rendered from the listing endpoint.

## Reconstruction boundary

NEXT 5 may restructure the page header, search toolbar, advanced-filter grid,
result summary, table container, and pagination markup. It may migrate visual
inline CSS into `venti-next.css` and add reusable semantic classes.

It must not change listing parameters, action hooks, dynamic template IDs,
column meaning/order, status texts, endpoints, response assumptions,
permissions, destructive confirmations, or persistence behavior. Changes to
`public/js/puntoVenta/list.js` are unnecessary unless a preserved selector can
no longer address the reconstructed DOM.

## Row-action dropdown pilot

The sales list presents the conditional action set through one Bootstrap
dropdown trigger per row. This is a presentation wrapper only: each menu item
retains its original element type, delegated `data-*` hook, identifiers, URLs,
conditional availability, callbacks, and confirmation flow.

The trigger uses `.next-row-actions-trigger`; the contextual menu uses
`.next-row-actions-menu`. Neither class is a business selector. The dangerous
`data-anular` action remains conditional, appears last, and is visually
separated. Bootstrap owns trigger focus, `aria-expanded`, outside-click and
Escape behavior.

After equivalent functional and responsive validation in `material.indexV2`,
the visual wrapper is promoted as the shared Row Actions Dropdown documented
in `VENTI-NEXT-COMPONENT-PATTERNS.md`. This promotion does not generalize any
Sales action, permission, URL, callback, template or status rule.
