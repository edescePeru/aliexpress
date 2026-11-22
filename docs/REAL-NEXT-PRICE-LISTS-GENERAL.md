# REAL NEXT — Listas de Precios — General Maintenance

Fecha: 2026-11-14. Roadmap E, fase 1.

## Estado

- General Price Lists: **FRONTEND INTEGRATED**.
- Scope: **TENANT + COMPANY — QA PASSED**, alcance de lectura detallado abajo.
- Branch: **NOT APPLICABLE**.
- Currency: **PEN/USD CONTRACT PRESERVED**.
- Default / Active: **PRESERVED**.
- Product Prices: **OUT OF SCOPE / MUTATION QA REQUIRED**.
- Mutations: **QA PENDING**. Backend: **UNCHANGED**. CSS nuevo: **0**.

## General vs Branch Contract

A: maestro PriceList, nombre/moneda/activa/default dentro de company activa. B: Branch Pricing, presentaciones/ofertas/resolución de precio efectivo. Sólo A integrado; no se tocaron BranchPriceListService, PriceResolverService, ofertas, POS ni presentaciones. Ver `REAL-NEXT-BRANCH-PRICING-PRESENTATIONS.md` para B.

Existe una sola vista `priceList.index`; create/edit son diálogos jquery-confirm generados por el JS existente. No hay GET create/edit independiente. Se añadió una tabla del maestro al resultado real de `getPriceLists`; el formulario de edición usa el registro de esa respuesta contextual, no un ID arbitrario consultado por un GET edit inexistente.

## Routes / Permissions

Prefijo `/dashboard/prices`. Grupo común: auth, check.user.enabled, password.changed, tenant.context. Permisos conservados, sin Owner bypass nuevo.

| Action | Route | Permission | Read/Mutation |
|---|---|---|---|
| Vista/listado | GET `/`, priceList.index | show_priceList | Read |
| Datos maestro | GET `/lists`, priceList.lists | show_priceList | Read |
| Abrir create | Botón btnNewPriceList en index; sin ruta adicional | manage_priceListMaterial | UI local |
| Abrir edit | Acción de fila / btnEditPriceList; cache del GET lists | manage_priceListMaterial | UI local |
| Crear | POST `/lists`, priceList.store | manage_priceListMaterial | Mutation — no ejecutada |
| Editar / active / default | PUT `/lists/{priceList}`, priceList.update | manage_priceListMaterial | Mutation — no ejecutada |
| Productos | GET `/lists/{priceList}/materials`, priceList.materials | manage_priceListMaterial | Read, editor existente fuera de integración |
| Guardar productos | POST mismo path, priceList.materials.save | manage_priceListMaterial | Mutation — fuera de alcance |
| Variantes | GET `/lists/{priceList}/materials/{material}/variants`, priceList.materialVariants | manage_priceListMaterial | Read, fuera de integración |
| Guardar variantes | POST mismo path, priceList.materialVariants.save | manage_priceListMaterial | Mutation — fuera de alcance |
| Delete / restore | No existen en este contrato | N/A | N/A |

## Scope

PriceList usa BelongsToTenant. `getPriceLists()` añade company activa y ordena default desc/name. `index()` valida Company con tenant/id. `validatePriceListContext()` compara tenant + company antes de operaciones por ID. Company viene del contexto servidor; no se añadió selector/payload de company/tenant/branch.

QA mínimo: lectura directa del controller/modelo/guard en transacción MySQL READ ONLY + ROLLBACK, sin invocar store/update. No equivale a prueba HTTP de PUT ni certificación de mutaciones.

| Contexto | Lectura | Binding + guard |
|---|---|---|
| T1/C1 | IDs [1,6,5] | ID1 pasa; ID2 otra company 404; 999999 404 |
| T1/C2 | IDs [2,4,3] | ID2 pasa; ID1 otra company 404; 999999 404 |
| T3/C3 | [] | IDs1/2 de otro tenant no se resuelven: 404; 999999 404 |
| T1/C1 → modelo T3/C3 sólo en memoria | No existe lista real Gamarra | Guard directo rechaza 404; sin save ni fixture DB |

Sesiones UI reales: Owner EDESCE en C1/Local Principal, luego C2/NEGOCIO DE PRUEBA/Tienda Prueba 1; Owner Gamarra en C3/Local Principal. Tabla y modal edit C1/C2 verificados. Gamarra vacío/create abierto. Al terminar se restauró Owner EDESCE, C1/Local Principal. Cambios de contexto/login no implicaron escritura de configuración comercial. Roles sin show/manage no se impersonaron; permisos auditados en Blade/rutas, no declarar QA dinámico de todos los roles.

## Dataset

Releído el 2026-11-14, sin usar conteos históricos como prueba.

| Tenant / Company | ID | Nombre | Currency | Active | Default |
|---|---:|---|---|---|---|
| T1/C1 EDESCE | 1 | PRECIO GENERAL | PEN | Sí | Sí |
| T1/C1 EDESCE | 5 | PRECIO PREMIUM | PEN | Sí | No |
| T1/C1 EDESCE | 6 | PRECIO OUTLET | PEN | Sí | No |
| T1/C2 Empresa de Prueba Multitenant | 2 | REGULAR | PEN | Sí | Sí |
| T1/C2 | 3 | PREMIUM | PEN | Sí | No |
| T1/C2 | 4 | OUTLET | PEN | Sí | No |
| T3/C3 Gamarra | — | Sin listas (0) | — | — | — |

No se cambió PRICE-07 (base USD/lista efectiva PEN de C2); la UI muestra PEN almacenado, sin conversión. No se reauditó el resolver para volver a demostrar ese finding.

## Fields / Currency

Se preservaron `name`, `currency`, `is_default`, `is_active`, IDs de checks new/edit, btnNewPriceList/btnEditPriceList, data-url y CSRF por X-CSRF-TOKEN. No hidden context original que reconstruir. Se agregaron IDs/labels accesibles a nombre/moneda y un hook de edición por fila; no se renombraron hooks existentes.

Validación inline en PriceListController, no FormRequest separado: nombre string requerido max100, duplicado case-insensitive dentro de company; currency string normalizada uppercase y Rule::in PEN/USD; flags boolean. Tabla muestra exactamente currency persistida. Formulario conserva sólo PEN/USD.

Errores create/update siguen Toastr; ahora presenta primero mensajes reales `errors` de Laravel si existen y después `message`, escapados como texto. No se duplica lógica fiscal/default en JS. QA de respuesta 422 tras escritura queda pendiente: no se enviaron formularios para provocarla.

## Default / Active

HEAD conserva PRICE-01/BP-01: primera lista obtiene default en backend y se valida activa incluso cuando se forzó ese default. Conserva PRICE-02/BP-02: ValidationException dentro de DB::transaction y lock company, sin retorno 422 perdido dentro del closure. Conserva PRICE-03/BP-03: whitelist PEN/USD. Conserva PRICE-04: guard tenant/company en operaciones ID. Confirmación estática; se mantiene **FIXED / QA PASSED** del cierre previo, no se repitió concurrencia ni tests de mutación.

Al marcar otra default, backend desmarca las otras de la misma company. No permite desactivar una lista que actualmente es default; tampoco solicitar default inactiva. El update conserva el flag enviado: no inferir ni imponer desde frontend que siempre queda exactamente una default si se desmarca la actual. No hay toggle independiente; se hace mediante edit/PUT.

La tabla usa únicamente flags del JSON, incluye inactivas si backend las devuelve. Todas las listas actuales son activas: estado inactivo revisado en renderer, QA con registro inactivo **PENDING DATASET**. PriceList no usa SoftDeletes ni tiene rutas destroy/restaurar; inactiva no significa eliminada.

El selector histórico del editor de productos elige default o primera lista como selección de navegación. Eso no establece default ni cambia el badge del maestro; no se modificó esa lógica ajena al maestro.

## Consumers

Branch Pricing consume listas mediante su contrato de branch/fallback; POS y presentaciones usan resolución contextual existente. Public Catalog requiere su propio contrato y seguridad pendientes: esta integración no lo certifica ni lo conecta. Ningún consumidor modificado.

## Product Price Boundary

La misma vista ya alojaba precios base por material y variantes/PriceListItems, búsqueda, paginación y guardado masivo. Clasificación: **OUT OF SCOPE / MUTATION QA REQUIRED**; sus GET son candidatos de lectura separados, no otra integración aprobada.

Se conserva el editor dentro de `Precios de productos`, plegado, bajo el permiso ya exigido por sus endpoints `manage_priceListMaterial`. Sus tablas, handlers, URLs, payloads, guardado y regla de cambios pendientes no se reescribieron. Mantiene la carga GET original para gestores; no se tocaron precios ni se probaron acciones de guardado. Hereda el skin de la vista pero no se declara modernización del editor. El guard de presencia del tbody evita solicitar datos de productos desde una vista de sólo listado sin permiso manage.

La regla inline sticky preexistente de `.price-list-actions` permanece; **0 reglas CSS nuevas**, ningún archivo CSS modificado. No se creó nueva pantalla, selector de branch ni lista de monedas.

## Visual / Allowlist

Configuration CRUD: Company Bank Accounts y Payment Deadlines; shared next-page-heading/toolbar, next-list-toolbar/summary, next-form-section, Bootstrap table-sm/table-responsive, badges y acción compacta. Se preservó jquery-confirm (no se reemplazó plugin); columnas Bootstrap para ancho, Escape/cancel y padding compartido en ambos forms.

Sólo se añadió `priceList.index` a config/venti-next.php. **ALLOWLIST MATCH: YES**. Navegador confirma `venti.min.css` y `venti-next.css`. Ningún endpoint JSON/productos, route wildcard ni nueva vista create/edit en allowlist.

## Empty State / Pagination

Gamarra: `0 listas`, mensaje `No hay listas de precios en esta empresa.`, botón Nueva lista según permiso, modal abre/cierra sin guardar; consola sin errores. Error de carga se muestra como error en summary + Toastr, no como vacío.

El maestro hace `get()` y devuelve array completo: **no tiene búsqueda ni paginación backend**. No se inventaron parámetros ni paginación cliente. MULTIPAGE QA del maestro: NOT APPLICABLE por contrato, no falta de dataset. Búsqueda/paginación de productos quedan intactas y fuera de esta fase.

## Responsive

| Width | List global overflow | Create | Edit |
|---:|---|---|---|
| 320 | NONE | PASS | PASS |
| 375 | NONE | PASS | PASS |
| 390 | NONE | PASS | PASS |
| 576 | NONE | PASS | PASS |
| 768 | NONE | PASS | PASS |
| 992 | NONE | PASS | PASS |
| 1200 | NONE | PASS | PASS |
| 1440 | NONE | PASS | PASS |

Medido documentElement.scrollWidth = innerWidth en tabla y ambos diálogos, usando datos C1. Table scroll local permitido en móvil. Se retiró un sr-only redundante de la acción nueva porque su posicionamiento absoluto propagaba ancho fuera del contenedor; aria-label preserva nombre accesible. Empresa visible también en móvil; controles/cancel visibles. Capturas visuales desktop/listado y móvil/create; cancel verificado, ninguna pulsación Guardar.

Evidencia externa al repo: `price-lists-desktop.jpg`, `price-lists-create-mobile.jpg`, `price-lists-gamarra.jpg` en `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/`.

## Backend Sync Notes

PRICE-01…04 mantienen estado resuelto previo, sin evidencia de regresión. PRICE-05/BP-04 y PRICE-06/BP-05 OPEN (resolución efectiva, no blockers del maestro). PRICE-07 BUSINESS / CONFIGURATION DECISION intacto. PRICE-08 Gamarra sin precios/listas no bloquea empty state del maestro. PRICE-09 concurrencia MySQL QA PENDING, no investigada aquí. Ningún finding backend nuevo, backlog sin cambios.

Revisitar al cambiar contratos de default/currency/scope, permisos, endpoints o al autorizar QA de mutaciones. No usar este cierre como certificación de POS, product pricing ni de resolución efectiva.

## Files / Checks

Archivos de esta fase: resources/views/priceList/index.blade.php; public/js/priceList/index.js; config/venti-next.php (sólo una entrada adicional); este documento. Cambios previos de Warrant y documentos anteriores preservados.

PHP 7.3.33 `artisan view:cache`: PASS. `node --check public/js/priceList/index.js`: PASS. `git diff --check`: PASS. Sin código backend, migraciones, CSS, commit ni escrituras de listas/precios/default/active. **MUTATION QA PENDING**.
