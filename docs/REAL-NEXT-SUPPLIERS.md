# REAL NEXT — Proveedores

Fecha: 2026-11-12. Roadmap C, fase 2. Integración de fachada; sin commit ni mutaciones de negocio.

## Contract Audit

Fuente funcional: `SupplierController`, `Supplier`, `SupplierAccount`, Store/Update/Delete/RestoreSupplierRequest y rutas actuales. Se preservaron endpoints, métodos, payloads, permisos, AJAX, Bootstrap Switch y CSRF. No se modificaron backend, compras, Finance, SupplierAccount ni ContactName.

Listado usa DataTables client-side y `data` de GET all/suppliers; búsqueda, orden, tamaño de página y paginación siguen locales. Eliminados usa GET all/suppliers/destroy. Crear/editar conservan los scripts existentes: botón `btn-submit`, FormData, `special` agregado como string true/false y manejo de errores JSON por toastr. Cancelar continúa siendo reset, no navegación.

## Routes

Todas las URI siguientes llevan prefijo `/dashboard/`. Middleware común: auth, check.user.enabled, password.changed, tenant.context, además del permiso indicado.

| Operación | Ruta / nombre | Permission | Tenant | Company | Branch | Read/Write |
|---|---|---|---|---|---|---|
| Listado | GET proveedores · supplier.index | list_supplier | Sí | No | No | Read |
| Datos | GET all/suppliers | list_supplier | Sí | No | No | Read |
| Crear vista | GET crear/proveedor · supplier.create | create_supplier | Sí | No | No | Read |
| Crear registro | POST supplier/store · supplier.store | create_supplier | Sí | No | No | Write |
| Editar vista | GET editar/proveedor/{id} · supplier.edit | update_supplier | Sí | No | No | Read |
| Actualizar | POST supplier/update · supplier.update | update_supplier | Sí | No | No | Write |
| Eliminar | POST supplier/destroy · supplier.destroy | destroy_supplier | Sí | No | No | Write |
| Eliminados | GET proveedores/restore · supplier.indexrestore | destroy_supplier | Sí | No | No | Read |
| Datos eliminados | GET all/suppliers/destroy | destroy_supplier | Sí | No | No | Read |
| Restaurar | POST supplier/restore · supplier.restore | destroy_supplier | Sí | No | No | Write |
| Exportar | GET download/supplierexcel | exportreport_supplier | Sí, explícito | No | No | Export, no ejecutado |

## Permissions

No existe permiso restore separado: las tres rutas de restauración usan destroy_supplier. Controller index entrega getPermissionsViaRoles; acciones JS conservan ese array. Los @can existentes se conservan. Ambos Owners probados tienen list/create/update/destroy/exportreport_supplier y list_supplierAccount.

| Action | Route | Permission | Read/Mutation | Included |
|---|---|---|---|---|
| Nuevo | supplier.create/store | create_supplier | Vista / Mutation | Sí; solo vista QA |
| Editar | supplier.edit/update | update_supplier | Vista / Mutation | Sí; solo vista QA |
| Eliminar | supplier.destroy | destroy_supplier | Mutation | Modal abierto/cancelado; submit excluido |
| Eliminados/restaurar | supplier.indexrestore/restore | destroy_supplier | Vista / Mutation | Vista vacía; restore excluido |
| Cuentas | supplier.accounts.index | list_supplierAccount | Navegación | Enlace conservado; mantenedor fuera del alcance |
| Exportar | download/supplierexcel | exportreport_supplier | Export | Botón conservado, no ejecutado |

## Tenant Scope

Supplier y SupplierAccount usan BelongsToTenant/TenantScope. Edit usa findOrFail; destroy/update hacen validación tenant y findOrFail. Restore mantiene TenantScope con onlyTrashed. El export usa DB::table con tenant_id explícito y cuentas relacionadas por supplier_id.

TenantScope sin contexto no filtra por diseño compartido; no se convierte en una garantía fuera de las rutas/contextos auditados. No se modificó esta política. QA autorizado realizado con Owners y tenant activo.

## Company / Branch Non-Scope

NOT APPLICABLE: Supplier no tiene scope de empresa ni sucursal. No se agregaron selectores, hidden company/branch ni filtros JS. Los documentos que lo consumen pueden tener scope company/branch independiente; su uso histórico no convierte al proveedor en exclusivo de esa empresa.

## Fields

| Campo | Contrato |
|---|---|
| business_name | Required string max255; mayus(this) preservado |
| ruc → RUC | Required string; unique dentro del tenant, incluyendo eliminados; update ignora ID propio |
| special | Checkbox btn-grouped, Bootstrap Switch SI/NO; AJAX string true/false; controller considera true solamente el string true |
| address | Nullable string max255, mayus preservado |
| phone | Nullable string max255 |
| email | Nullable string email max255; tipo email en create/text en edit preservado |
| supplier_id | Hidden edit/delete/restore; validación tenant y deleted_at según operación |
| code | Generado backend PROV- + ID global padded a5; solo lectura en listado |
| deleted_at | Soft delete; no campo editable de estado |

Controller store/update rechaza RUC >11 caracteres cuando special no es true. No se añadió lógica fiscal ni validación duplicada en JS. Se añadieron labels asociados, old() y bloques @error; errores AJAX siguen con el manejador existente. Activo/Eliminado representa pertenencia al listado no eliminado/onlyTrashed, no un estado comercial nuevo.

## Accounts

Relación Supplier::accounts hasMany por supplier_id; SupplierAccount también tenant-scoped. GET all/suppliers eager-loads accounts sin proyección específica: atributos serializables del modelo (id/tenant/supplier, number_account, currency, bank_id y timestamps cuando existan). UI muestra number_account si tiene list_supplierAccount. Cuentas se administran en ruta separada `/dashboard/registrar/cuentas/proveedores/{id}`; no se integró ni alteró esa superficie. No son CompanyBankAccount.

Dataset: EDESCE sin cuentas; Gamarra una cuenta ID5 del supplier2/tenant3 con number_account vacío. Se muestra “Número no registrado”, sin generar número ni corregir datos. Valores HTML de etiquetas/atributos se escapan en renderers tocados.

## Contacts

No existe campo contacto ni relación ContactName en Supplier actual. ContactName tiene customer_id, customer y quotes; usa BelongsToTenant y SoftDeletes. No se añadió un contrato de contacto a Proveedores.

## Soft Delete

getSuppliers excluye eliminados mediante SoftDeletes. Destroy llama delete(), no forceDelete. El listado muestra Activo. Modal, supplier_id, data-delete y CSRF intactos. Se abrió y canceló sin enviar.

## Restore

getSuppliersDestroy usa onlyTrashed; RestoreSupplierRequest exige ID del tenant con deleted_at no null; controller vuelve a resolver onlyTrashed()->firstOrFail(). Listado con badge Eliminado cuando hay filas. Empty state visible incluso a320px. data-restore/data-code/data-company y formRestore preservados; corregida comilla suelta del HTML previo del botón.

RESTORE POSITIVE QA — PENDING DATASET: ambos tenants tienen cero eliminados. No se fabricó dataset. Validación de RestoreSupplierRequest evaluada sin llamar al controller mutador: rechaza IDs activos1/2 y99999999 en ambos contextos. No equivale a una restauración positiva ni a probar HTTP POST.

## Purchase Consumers

OrderPurchaseController: listados normales, filtros y selects Supplier, supplier_id y relaciones históricas. EntryController: selects/proveedores de entradas, órdenes normales y vinculación de créditos. Modelos/documentos conservan sus propios scopes. No se modificaron consumidores ni se concluye QA integral de compras por esta fase.

## Finance Consumers

InvoiceController usa Supplier para selects y supplier_id/relación de factura; ExpenseSupplierController lo usa para filtro y presentación del proveedor de documentos de gasto. También existen referencias en créditos de proveedores. Ninguna pantalla Finance fue alterada.

## Visual Mapping

REUSE WITH CONTRACT ADAPTATION: Configuration CRUD de Empresas/Payment Deadlines/Invoice Categories; next-page-toolbar, next-toolbar-actions flex-wrap, next-form-section, next-section-header, next-operational-list, next-list-toolbar/summary/pagination y next-data-table. Acciones usan next-row-actions-trigger/menu con data-boundary viewport: evita corte del dropdown dentro de tabla con scroll local. Modales Bootstrap existentes conservados.

0 CSS nuevo. Eliminadas reglas inline Select2 innecesarias (estas vistas no incluyen selects Select2); plugins conservados. DataTables aplica text-wrap a su propio info y text-left/text-wrap al estado vacío; resuelve overflow a320 y texto vacío fuera del área visible sin nueva regla CSS.

ALLOWLIST MATCH: YES, solo supplier.index/create/edit/indexrestore en config/venti-next.php. CSS verificado en DOM: venti.min.css y venti-next.css. SupplierAccount no añadido.

## Responsive QA

| Ancho | List | Create | Edit | Restore |
|---|---|---|---|---|
| 320 | PASS | PASS | PASS | PASS |
| 375 | PASS | PASS | PASS | PASS |
| 390 | PASS | PASS | PASS | PASS |
| 576 | PASS | PASS | PASS | PASS |
| 768 | PASS | PASS | PASS | PASS |
| 992 | PASS | PASS | PASS | PASS |
| 1200 | PASS | PASS | PASS | PASS |
| 1440 | PASS | PASS | PASS | PASS |

Matriz DOM: scrollWidth <= innerWidth en las cuatro vistas. Revisión visual desktop y móvil: formularios apilados, gutters compartidos, botones wrap, tabla con scroll horizontal local, menú completo fuera del clipping, empty state legible. No overflow global. No nueva CSS. Capturas/matriz fuera del repositorio: carpeta `suppliers` de la evidencia de esta sesión.

Búsqueda con texto sin coincidencia y limpieza: PASS. Paginación visible con una página; transición multi-página PENDING DATASET. No errores/warnings de consola observados en QA final. Confirmación de eliminación abierta y cancelada. Create/edit solo lectura; validación mutadora no ejecutada.

## Cross-tenant QA

| Sesión autorizada | Rol | Contexto | Activos | Cuentas | Eliminados | Edit propio | Edit cruzado | Inexistente |
|---|---|---|---|---|---|---|---|---|
| Owner EDESCE | owner | Tenant1 / Company1 / Branch1 Local Principal | ID1 | 0 | 0 | 200 | ID2:404 | 99999999:404 |
| Owner Gamarra | owner | Tenant3 / Company3 / Branch4 Local Principal | ID2 | 1 | 0 | 200 | ID1:404 | 99999999:404 |

Navegador: list/create/edit/restore en ambas sesiones, contraste positivo y ediciones cruzadas404. ID inexistente confirmado en navegador Gamarra y en ambos contextos por controlador. Auditoría PHP adicional: transacción explícitamente READ ONLY, rollback final, sesión array y Auth::setUser; consulta del endpoint devuelve solo [1]/[2]. No contraseñas almacenadas en scripts, documentación o repositorio. Sesión final del navegador: Owner Gamarra.

Tenant Isolation — QA PASSED para los contextos/dataset probados. Sin matriz de usuarios de permisos limitados; no implica validar todas las combinaciones de autorización.

## Mutation Exclusions

No crear, actualizar, eliminar, restaurar ni exportar. No cambios en SupplierAccount/ContactName. No invocación de los controllers mutadores ni POST de negocio. MUTATION QA PENDING. Restore positivo PENDING DATASET. No commit.

## Backend Sync Notes

[OPEN]
Point: exposición de accounts vs permiso separado.
File / endpoint: SupplierController@getSuppliers, GET /dashboard/all/suppliers.
Impact: con list_supplier se serializa accounts aunque UI los renderiza bajo list_supplierAccount. Ocultar la celda no autoriza ni protege el payload.
What backend needs to define or correct: confirmar si list_supplier autoriza también leer cuentas; si no, limitar serialización backend por permiso. No corregido mediante frontend.
Frontend can continue: Yes.

[OPEN]
Point: permisos directos vs roles.
File / endpoint: SupplierController@index usa getPermissionsViaRoles; Blade/rutas usan can/permission.
Impact: una autorización directa fuera del rol podría permitir endpoint y no mostrarse en acciones JS. No reproducido con Owners (permisos disponibles).
What backend needs to define or correct: confirmar si soporta permisos directos y alinear la lista entregada si corresponde.
Frontend can continue: Yes. Se conserva contrato actual.

[OPEN — DATASET]
Point: cuenta5 de Gamarra sin número registrado.
Impact: no hay número para mostrar. No se alteró información real.
What backend needs to define or correct: revisar integridad histórica según política de cuentas; fuera de este mantenedor.
Frontend can continue: Yes, ausencia explícita.

## Revisit Triggers

Cambios en Supplier scopes/requests, autorización de accounts/permisos directos, introducción de contactos, nuevos campos/estados, dataset eliminado para restore positivo, autorización para QA mutador, o regresión responsive. No reabrir Empresas salvo regresión directa.

## Files and validation

Modificados: config/venti-next.php; resources/views/supplier/{index,create,edit,restore}.blade.php; public/js/supplier/{index,restore}.js; este documento.
JS create/edit intactos. Backend y CSS sin cambios. PHP7.3 artisan view:cache PASS; node --check de ambos JS PASS; git diff --check PASS.

Supplier List / Create / Edit / Restore UI — FRONTEND INTEGRATED.
Tenant Isolation — QA PASSED.
Company / Branch Scope — NOT APPLICABLE.
Mutations — QA PENDING.
Restore Positive QA — PENDING DATASET.
Backend — UNCHANGED.
