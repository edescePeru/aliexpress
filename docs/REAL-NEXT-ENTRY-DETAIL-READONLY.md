# REAL NEXT — ENTRY DETAIL — READ-ONLY FRONTEND INTEGRATION

Fecha: 2026-11-18. ENTRY DETAIL — FRONTEND INTEGRATED — READ PATH.

## DEV Fix ENTRY-01

Se utiliza el guard DEV del merge5fb180d sin modificarlo. `showEntryPurchase` resuelve Entry por tenant_id/company_id y findOrFail. No se reconstruyó la auditoría; smoke acotado de IDs y presentación. ENTRY-01 — FIXED / QA PASSED / FRONTEND INTEGRATED.

## Route / Permission

| Route | Permission | Scope | Read/Mutation |
|---|---|---|---|
| GET /dashboard/entrada/compra/visualizar/{entry}, entry.purchase.show | list_entryPurchase | tenant + company | Read |

Action EntryController::showEntryPurchase; Blade entry.show_entry_purchase. Middleware heredado auth/check.user.enabled/password.changed/tenant.context más permiso específico. Allowlist: sólo entry.purchase.show agregado; ALLOWLIST MATCH: YES. HTML renderizado carga venti.min.css y venti-next.css.
JS anterior edit_entry_purchase.js cargaba catálogos de materiales/locations y handlers de edición/Items. Esta superficie deja de cargarlo; el archivo y las vistas de edición no se modifican. Sin JS específico nuevo.

## Tenant / Company Contract

Company-wide. No se añade restricción branch ni se exige que el warehouse coincida con el local activo. Suppliers es la colección existente enviada por controller; se selecciona el proveedor por ID sin query adicional. Si no hay correspondencia se muestra ausencia, sin buscar fuera del contexto.

## Dataset

MySQL actual aliexpress_multitenant_3, conexión fijada PDO READ ONLY, rollback final. No registros creados/modificados. Render real de Laravel con Auth Owner y sesión contextual en proceso aislado, no login HTTP nuevo.

| Contexto | Entry | Líneas | Moneda | Total backend | Permiso |
|---|---|---|---|---|---|
| Owner EDESCE4, T1/C1/B1 | 4 | 2 | PEN | 800.00 | PASS |
| Owner EDESCE4, T1/C2/B2 | 49 | 1 | PEN | 200.00 | PASS |
| Owner Gamarra7, T3/C3/B4 | 45 | 1 | PEN | 200.00 | PASS |

Los tres registros finance=0. No se certifica dataset Finance positivo. Smoke controller: C1→49/45/999999404; C2→4/45/999999404; Gamarra→4/49/999999404. Caso propio devuelve vista y render completo. No suite extensa nueva. Comprobación permisos contra catálogo actual con registrar en cache-array; sin limpiar caché compartida.

## Header

ID Entry, estado Activa/Anulada por state_annulled, proveedor, factura, date_entry, currency_invoice, referral_guide, purchase_order, diferido por valor persistido on, tipo de ingreso Por compra (etiqueta preexistente de esta superficie), observación. Se sustituyeron inputs readonly por dl/valores y badge semántico. Botón único de navegación al listado V2 ya integrado.

## Detail Lines

Código/SKU (accessor material_code), material_description, entered_quantity, unit, unit_price, sub_total, taxes y total. El código deja de dereferenciar material->code directamente; utiliza accessor existente para soportar StockItem/material nulo sin inventar datos. No consultas de UI nuevas ni cambios en modelos. Se conservan atributos data de las celdas y body-materials.

Importes monetarios alineados derecha, código/cantidad/unidad center, descripción left. Empty state de ocho columnas; tabla con scroll horizontal local y tabindex. Totales usan exclusivamente accessors de Entry; moneda persistida sin conversión ni combinación.

Fiscalidad preservada: DetailEntry usa finance_tax_amount cuando existe, y cálculo histórico /1.18 cuando falta. Entry aplica snapshot al subtotal/impuesto bajo finance, si existe; resto histórico. La vista no recalcula, inventa tasas ni fabrica snapshots. El redondeo de líneas puede diferir del total backend; no corregido desde frontend. No se certifica la regla fiscal.

## Purchase / Warehouse Relations

purchase_order/referral_guide se presentan como referencias de texto, sin links a órdenes ni consultas adicionales. La vista anterior no exponía warehouse/location de recepción: no se añaden, infieren ni reconstruyen Items/estructura legacy. No se abre Warehouse. No gestión StockItem/Items/InventoryLevel/reservas.

## Documents Boundary

DOCUMENT SECURITY — BACKEND/INFRA OPEN. DOC-01 no cerrado. Se muestra sólo presencia de image/imageOb, sin URLs ni src; no abrir/renderizar/descargar imágenes o PDF. Se retiraron de esta vista modal de imagen, formularios de edición y modales AddItems/AddGroupItems. No se borraron endpoints ni archivos comunes.

| Action | Permission | Read/Mutation | Included |
|---|---|---|---|
| Visualizar / volver a listado V2 | list_entryPurchase | Read | Sí |
| Recepción / crear entrada | create_entryPurchase | Mutation | No |
| Editar / cantidades / costos / Items | update_entryPurchase | Mutation | No |
| Eliminar / restaurar | destroy_entryPurchase | Mutation | No |
| Regularización | Según endpoint específico, no utilizado | Mutation | No |
| Imprimir / abrir adjunto | No certificada aquí | Documento | No |
| Modificar adjunto | update_entryPurchase en edición | Mutation | No |

## Responsive

HTML completo generado por Laravel con dataset real en transacción READ ONLY, cargado en Chrome headless a la URL correspondiente mediante interceptación de navegación. Assets locales reales; AJAX del shell sustituido por respuestas vacías contractuales para evitar proveedor externo/notificaciones. No equivale a QA HTTP end-to-end de login/middleware ni de AJAX del shell.

| Ancho | Entry4 | Entry49 | Entry45 | Overflow global |
|---|---|---|---|---|
| 320 | PASS | PASS | PASS | NONE |
| 375 | PASS | PASS | PASS | NONE |
| 390 | PASS | PASS | PASS | NONE |
| 576 | PASS | PASS | PASS | NONE |
| 768 | PASS | PASS | PASS | NONE |
| 992 | PASS | PASS | PASS | NONE |
| 1200 | PASS | PASS | PASS | NONE |
| 1440 | PASS | PASS | PASS | NONE |

Tabla local ~1008px mínimo por patrón compartido; scroll local en anchos insuficientes. Capturas desktop1440/móvil375 revisadas; se espera fin de transición del shell antes de captura. Sin JS de edición ni referencias de adjuntos en DOM. Primer harness produjo error de notificaciones por stub [] incorrecto; corregido a notifications:[] sin tocar aplicación.

Patrones reutilizados: Invoice Detail/warehouseTransfer moderno, next-page-heading/page-toolbar/toolbar-actions, next-form-section, dl.row, next-data-table, next-table-empty. CSS nuevo0; no cambio visual global.

## Final Status

- ENTRY DETAIL — FRONTEND INTEGRATED — READ PATH.
- ENTRY-01 — FIXED / QA PASSED / FRONTEND INTEGRATED.
- Backend UNCHANGED; mutaciones, recepción y documentos fuera de alcance.
- Blade compile: PASS PHP7.3.33. JS funcional: sin modificaciones. git diff --check: PASS.
- Archivos de esta fase: resources/views/entry/show_entry_purchase.blade.php; config/venti-next.php; este documento; fila ENTRY-01 del backlog.
- Se preservan los cambios documentales de la revisión post-merge que ya estaban sin commit. Sin commit nuevo.
