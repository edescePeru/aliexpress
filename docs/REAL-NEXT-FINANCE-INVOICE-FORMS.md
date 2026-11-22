# REAL NEXT — FINANCE INVOICE — CREATE + EDIT

2026-11-18. Create/Edit: **FRONTEND INTEGRATED — MUTATION QA PENDING**.
Scope TENANT + COMPANY. Backend UNCHANGED. CSS nuevo:0. Sin commit.
Listados/reportes ya integrados no se modificaron ni reauditaron.

## Routes / Permissions

| Surface | Route / name | Permission | Scope | Read/Mutation |
|---|---|---|---|---|
| invoice.create_invoice | GET /dashboard/crear/factura/compra — invoice.create | create_invoice | tenant/company; branch para caja | Read |
| invoice.edit_invoice | GET /dashboard/factura/compra/editar/{entry} — invoice.edit | update_invoice | tenant/company + finance=true | Read |
| Guardar | POST /dashboard/invoice/store — invoice.store | create_invoice | contexto financiero | Mutation; no ejecutada |
| Actualizar | POST /dashboard/invoice/update — invoice.update | update_invoice | contexto financiero | Mutation; no ejecutada |
| Categoría auxiliar | POST /dashboard/category/invoice/store — categoryInvoice.store | create_categoryInvoice | CategoryInvoice company | Mutation; no ejecutada |

Middleware de las vistas: web/auth/check.user.enabled/password.changed/tenant.context
más permission específico. Selectores principales se entregan desde controller,
no nuevos endpoints ni permisos auxiliares para lectura. Modal de categoría conserva
su ruta y permiso backend; no se guardó categoría. No se añadió permiso implícito.
Allowlist usa **nombres de ruta**, no nombres Blade: sólo invoice.create/invoice.edit.

## Scope / Edit Binding

InvoiceController::financeEntries(): Entry::query()->where(FinanceInvoiceContext::ids())
->where(finance,true). Edit revalida findOrFail(entry.id) después del binding.
Branch no se añadió a ownership Entry. Proveedor no se convirtió en company-scoped.

QA en transacción MySQL READ ONLY/ROLLBACK, Auth real en memoria (Owner EDESCE4 y
Owner Gamarra7), llamadas a createInvoice/editInvoice y render real. No certifica
sesión HTTP completa. Permisos actuales create/update=true en ambos owners.

## Dataset

Cinco cabeceras finance=true T1/C1, todas PEN: IDs29,30,36,38,55.
30 está anulada; las otras cuatro no. GET Edit permite las cinco en C1/B1 y C1/B8.
Update además exige state_annulled=false: **GET elegible no significa actualización
permitida**. No se probó ni alteró ninguna cabecera.

C2/B2 y Gamarra T3/C3/B4: los cinco IDs dan404. No finance IDs1(C1),49(C2),45(C3)
dan404; inexistente999999 da404 en cada contexto. Se verificó revalidación del
controller con modelos reales sin scope para representar binding ajeno; no se
relajó ningún scope runtime. No fixtures DB.

## Supplier

Supplier usa BelongsToTenant + SoftDeletes; selector Supplier::all() preservado.
C1/B1,C1/B8 y C2:IDs[1]; Gamarra:[2]. No filtro company artificial.
Store/Update validan supplier_id nullable/integer y exists dentro del tenant.

## CategoryInvoice

CategoryInvoice::forCurrentCompany() y currentCategoryRule verifican tenant/company,
no eliminada. IDs C1:[3,2,1]; C2/Gamarra:vacío. Selector/valor de Edit intactos.

## FinanceInvoiceContext / Cash/Subtype/Session

ids()=tenant/company. boxes():activas, branch actual o branch NULL.
sessions():tenant/company/branch actual, usuario autenticado, status abierto.
Create limita cajas a las que tienen exactamente una sesión propia abierta.

C1/B1 cajas[1,2,5], subtipos inmediatos[1,2,4,6]. C1/B8 cajas[], mismos subtipos
company; C2/Gamarra cajas[] y subtipos[]. Sin crear ni abrir sesiones.
payment() verifica moneda de caja, subtipo inmediato compartido o de esa caja y
sesión única con lock. No se invocó payment() ni se tomó ese lock durante QA.
El JS existente muestra subtipos compatibles y cajas por moneda del payload;
no se añadieron filtros JS de seguridad. Backend sigue siendo autoridad.
Edit no recibe cashBoxes/subtypes y no expone cambio de contexto de pago; no se
inventaron esos campos para lograr paridad visual.

## Lines

Create conserva description/material_search, material_unit, quantity, importe total,
finance-tax-kind y templates/data attributes. invoice.js envía JSON items con
material,quantity,total,unit_id,tax_kind y valores actuales. No nuevos campos.
Edit conserva líneas reales, old=1, id y hooks de borrado. No se borró ninguna línea.
Backend rechaza items nuevos old!=1 en Update; el editor legacy todavía ofrece
Agregar según purchase_order. Se conserva y documenta esa discrepancia, sin
presentarla como mutación validada ni cambiar lógica en una fase visual.

## Tax / Money

FinanceInvoiceAmounts valida1..200 líneas, cantidad/importe>0 y formato de hasta
2 decimales, unidad del tenant y tax_kind gravada/exonerada/inafecta.
Backend calcula en centavos; gravada usa gross/1.18 y snapshot finance_tax_kind /
finance_tax_amount; precio unitario redondeado a2. No reglas fiscales nuevas.
Create conserva resúmenes gravada/exonerada/inafecta/subtotal/IGV/total.
Edit muestra valores/accesores existentes y no fabrica snapshot para históricos.
Moneda PEN/USD y switches/payload originales intactos. Update rechaza cambio de
moneda. No se convirtió moneda ni llamó tipo de cambio real durante QA.
No modificación de amounts, paid/accounted, redondeo ni tratamiento financiero.

## Validation

StoreInvoiceRequest/UpdateInvoiceRequest sin cambios: invoice requerido string5..255,
purchase_order nullable string5..255, entry_type/type_order/date_invoice requeridos;
category contextual y supplier tenant. Store además exige items JSON y caja integer.
Laravel errors/toastr y manejo422 de ambos JS intactos. Forms no tenían old() ni
bloques Blade de error; se conserva su contrato AJAX, sin afirmar recuperación old()
que no existe. No reglas frontend nuevas. No POST para probar validación mutadora.

## Legacy AJAX Boundary

/get/materials y /get/locations siguen dentro del comentario inicial invoice.js.
No dependencias activas, no reactivadas. Edit usa edit_invoice.js y sus líneas
renderizadas. Ningún JS funcional modificado. Modales legacy no se eliminan.

## Visual Integration

Referencia inspeccionada: material.create, next-page-heading/page-toolbar,
next-section-header/kicker, next-form-section y acciones primarias/secundarias.
Formularios conservan grids, hooks collapse, nombres, CSRF, data-url y valores;
superficies neutrales con gutters, encabezado de documento/proveedor, líneas,
resumen monetario y footer compacto. Modales usan next-aux-modal existente.
Resumen ocupa ancho móvil completo y media grilla desktop. Tabla compacta con
scroll local; importes derecha y cantidades/unidad centro. Se retiró override
local de Select2; sin CSS nuevo. Paridad Create/Edit salvo campos contractuales.

## Responsive / QA

320/375/390/576/768/992/1200/1440:document scrollWidth=viewport, sin overflow global.
Seis superficies:Create C1/B1,C1/B8,C2,Gamarra y Edit29/55.54 comprobaciones,
cero page errors. Select2 abre/cierra. Líneas Create añadidas únicamente en memoria:
118 gravada y50 exonerada; UI conserva subtotal150,IGV18,total168; no guardadas.
Edit conserva líneas reales. No click en delete/save/update/payment/annul.
Lectura de dataset/controlador y evidencia visual separadas de QA de mutaciones.
No prueba funcional de nuevas líneas en Edit (backend las rechaza).
Comparación estática de names,data-url,data-delete,data-i antes/después:PASS.
PHP7.3 artisan view:cache y git diff --check:PASS.

## Mutation Boundary / Documents

**MUTATION QA PENDING**:store/update/annul/payment/delete detail/reversal,
creación de categoría y subida de adjuntos no ejecutados.
**DOCUMENT SECURITY — BACKEND/INFRA OPEN**:PDF/attachments no abiertos ni certificados.
Requests a images/entries interceptados durante navegador (incluso placeholder);
la imagen rota en evidencia interceptada no demuestra fallo del archivo real.
/api/sunat y AJAX auxiliar del shell interceptados, sin invocar proveedor externo.

## Backend Sync Notes / Revisit Triggers

[OPEN] GET Edit permite anulada30; Update no. Documentar como límite actual,
no modificar backend ni garantizar guardado sólo por render200.
[OPEN] Edit presenta moneda/Agregar legacy aunque Update rechaza cambio de moneda
/nuevas líneas. Reauditar sólo cuando DEV entregue cambios a ese contrato.
[OPEN] Documentos:deuda backend/infra existente, no cerrada por esta fase.
No cerrar mutaciones financieras con evidencia exclusivamente read-only.

## Files / Evidence

- resources/views/invoice/create_invoice.blade.php
- resources/views/invoice/edit_invoice.blade.php
- config/venti-next.php (invoice.create/invoice.edit)
- docs/REAL-NEXT-FINANCE-INVOICE-FORMS.md

Backend/JS/rutas/requests/modelos/dataset sin cambios. CSS0. Sin commit.
Evidencia local temporal:%TEMP%/finance-contract.json,finance-render.php,
finance-browser-qa.json,finance-browser-qa.cjs y finance-{create/edit}-*.png.
No credenciales ni datos QA versionados.

## Visual + Cash Select Fix — 2026-11-18

### Causa de Caja / contexto verificado

No se confirmó pérdida de opciones en frontend. El formulario HTTP real de Owner
EDESCE (user 4) tiene tenant 1, company 1 y **branch 8 / OUTLET DE EDESCE**, moneda
PEN. Se confirmó mediante el header del navegador y lectura sanitizada de sesión
(sin registrar tokens). El Blade recibe `window.PV_CASHBOXES = []`.

| Caja de company 1 | Branch | Moneda | Resultado en branch 8 |
|---|---|---|---|
| 1 Efectivo Local Principal | 1 | PEN | Excluida: otro local |
| 2 Bancario BCP | compartida/null | PEN | Candidata, sin sesión propia abierta en branch 8 |
| 5 Bancario Interbank | compartida/null | PEN | Candidata, sin sesión propia abierta en branch 8 |
| 6 Efectivo Outlet Edesce | 8 | PEN | Candidata, sin sesión propia abierta en branch 8 |

Candidatas contextuales: [6,2,5]. Sesiones propias abiertas: []. Elegibles: [].
Clasificación **B: backend devuelve cero por contrato**; causa: ninguna sesión
válida en ese local (condición C), no un fallo Select2. `createInvoice()` ya aplica
el HAVING COUNT(*) = 1 antes de entregar las cajas al Blade.

Contraste read-only en branch 1: elegibles [1,2,5], sesiones propias 34→1,
33→2 y 29→5, respectivamente. Estas sesiones NO habilitan las cajas en branch 8.
Todas las candidatas son PEN; USD no tendría opciones en este dataset.
Subtipos inmediatos activos de company 1: [1,2,4,6]; 1/2/4 compartidos y 6 sólo
BCP. Ningún subtipo sustituye la exigencia de sesión. No cambios al contexto,
moneda, filtrado, selección automática de efectivo ni cascada de subtipos.
Consultas dentro de transacción READ ONLY con ROLLBACK; no sesiones abiertas/cerradas.

### Corrección de presentación

- Se quitaron los dos controles collapse y sus wrappers en Create y Edit.
- Factura ocupa el mismo ancho que Fecha (columnas 6/6); en Edit se conserva
  Diferido, en una fila propia. Inputs de 36px y mismo eje en desktop.
- `.next-required` de `material.create` sustituye badges legacy.
- Sección 02 usa `bg-light` existente y `next-section-header`/kicker; sin CSS nuevo.
- Captura usa grid responsive y `align-items-end`, form-group uniforme.
  Seis controles en una fila desde xl; dos columnas en tablet; apilados en móvil.
- Segundo título: `Detalle agregado`. IDs/handlers/names sin cambios.
- Input file sigue siendo nativo. Sin subir archivos ni abrir documentos.
- Caja muestra `Sin cajas disponibles` y ayuda completa con la causa y moneda.
  El JS sólo actualiza ese texto/visibilidad al refrescar opciones; no modifica
  la elegibilidad, payload, eventos ni valores. El option informativo sigue con value vacío.

### QA de esta corrección

Navegador autenticado real, GET Create; lectura DOM y screenshots desktop/móvil.

| Width | Overflow global | Captura |
|---|---|---|
| 320 | 0 px | apilada |
| 375 | 0 px | apilada |
| 390 | 0 px | apilada |
| 576 | 0 px | apilada |
| 768 | 0 px | dos columnas |
| 992 | 0 px | dos columnas |
| 1200 | 0 px | una fila |
| 1440 | 0 px | una fila |

Fecha/Factura: ancho y altura iguales; mismo Y desde 768px. En móvil pertenecen
a sus columnas apiladas. Los controles de captura comparten Y en 1200/1440,
incluido Agregar. Cero controles collapse dentro del formulario.
Empty state real y header OUTLET verificados; sin errores de consola observados.
Viewport temporal restaurado después de QA.

Render read-only repetido de Create en los cuatro contextos documentados y Edit
29/30/36/38/55 en company 1: PASS; otra company/tenant e inexistente: 404.
Este render de Edit no equivale a una nueva matriz visual completa ni a pruebas
de guardado. Sus diferencias funcionales (Diferido, ausencia de Caja y afectación
por línea) se conservaron. No se reabre la auditoría financiera.

Clasificación: **VISUAL PARITY** para superficies/grid/badges; **VALID REAL CONTRACT
DIFFERENCE** para campos específicos de Finance y elegibilidad de Caja.
CSS nuevo: 0. Backend modificado: NO. JS: SÍ, sólo estado informativo de Caja.
Mutaciones financieras, uploads y sesiones de caja: NO EJECUTADAS.

Archivos de esta corrección: ambos Blade invoice/create_invoice y edit_invoice,
public/js/invoice/invoice.js, este documento. config/venti-next.php conserva el
cambio previo de allowlist, sin cambios adicionales en esta corrección.
Validación: PHP 7.3 `artisan view:cache`, `node --check public/js/invoice/invoice.js`,
`git diff --check`: PASS. Sin commit.
