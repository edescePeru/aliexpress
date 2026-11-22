# REAL NEXT — Platform Shipping Catalogs

2026-11-18 · FRONTEND INTEGRATED · GLOBAL PLATFORM · Backend UNCHANGED · CSS nuevo0.

## Catalog Map

Una Blade platform.shippingCatalogs.index. Mapa cerrado: transporte/TransportType, motivos/TransferReason, documentos/IdentityDocumentType, peso/WeightUnit, indicadores/SunatShippingIndicator. Clave inválida404, verificado por llamada directa al controller. No modelos arbitrarios.

## Routes

Prefijo /dashboard/plataforma/catalogos-guias.

| Catalog | Method | Route / name | Permission | Read/Mutation |
|---|---|---|---|---|
| Todos; default transporte | GET | /{catalog?} · platformShippingCatalogs.index | platform.admin | Read |
| motivos, indicadores | POST | /{catalog} · platformShippingCatalogs.store | platform.admin | Mutation pendiente |
| Todos | PUT | /{catalog}/{id} · platformShippingCatalogs.update | platform.admin | Mutation pendiente |

ID numérico; PUT findOrFail dentro del catálogo. No DELETE/restore. Allowlist: exclusivamente platformShippingCatalogs.index (nombre real de ruta).

## Platform Scope

web/auth/check.user.enabled/password.changed/platform.admin. EnsurePlatformAdmin exige is_platform_admin y limpia TenantContext. No scope ni selector tenant/company/branch. Prueba directa: Admin id1 permitido; Owner EDESCE id4 denegado403. No constituye login HTTP end-to-end. Navegación Platform Admin preservada.

## Dataset

Revalidado en MySQL READ ONLY + ROLLBACK: transporte2, motivos13, documentos9, peso2, indicadores3 =29. Todos activos. IDs/códigos/nombres actuales:

| Catalog | ID | Code | Name |
|---|---:|---|---|
| transporte | 1 | 01 | Público (Agencia) |
| transporte | 2 | 02 | Privado (Propio) |
| motivos | 1 | 01 | VENTA |
| motivos | 2 | 02 | COMPRA |
| motivos | 3 | 04 | TRASLADO ENTRE ESTABLECIMIENTOS DE LA MISMA EMPRESA |
| motivos | 4 | 14 | VENTA SUJETA A CONFIRMACIÓN DEL COMPRADOR |
| motivos | 5 | 18 | TRASLADO EMISOR ITINERANTE CP |
| motivos | 6 | 08 | IMPORTACIÓN |
| motivos | 7 | 09 | EXPORTACIÓN |
| motivos | 8 | 05 | CONSIGNACION |
| motivos | 9 | 17 | TRASLADO DE BIENES PARA TRANSFORMACION |
| motivos | 10 | 03 | VENTA CON ENTREGA A TERCEROS |
| motivos | 11 | 06 | DEVOLUCION |
| motivos | 12 | 07 | RECOJO DE BIENES TRANSFORMADOS |
| motivos | 13 | 13 | OTROS |
| documentos | 1 | 1 | DNI - DOC. NACIONAL DE IDENTIDAD |
| documentos | 2 | 4 | CARNET DE EXTRANJERÍA |
| documentos | 3 | 7 | PASAPORTE |
| documentos | 4 | A | CÉDULA DIPLOMATICA DE IDENTIDAD |
| documentos | 5 | G | SALVOCONDUCTO |
| documentos | 6 | B | DOC. IDENT. PAIS. RESIDENCIA - NO.D |
| documentos | 7 | C | Tax Identification Number - TIN – Doc Trib PP.NN |
| documentos | 8 | D | Identification Number - IN – Doc Trib PP.JJ |
| documentos | 9 | 6 | RUC |
| peso | 1 | KGM | KILOGRAMO |
| peso | 2 | TNE | TONELADA (TONELADA MÉTRICA) |
| indicadores | 1 | IndicadorRetornoVehiculoEnvaseVacio | Retorno de vehículo con envase vacío |
| indicadores | 2 | IndicadorRetornoVehiculoVacio | Retorno de vehículo vacío |
| indicadores | 3 | IndicadorTrasladoVehiculoM1L | Traslado en vehículo M1 o L |

## Fields

id, code, name, sort_order, is_active. CSRF, spoof PUT, actions, hidden record_id y old() conservados. IDs new-code/new-name/new-order/new-active y code-/name-/sort-/active-{id} preservados. Code readonly sin name enviable en edición.

## Create Capability

Sólo motivos e indicadores. Se conserva formulario desplegable en next-form-section si availableCodes no está vacío. Hoy ambos tienen todos los códigos: aviso sin botón Crear. Alta/empty validados con colección sólo en memoria, sin fixtures DB. UI INTEGRATED / QA PENDING (persistencia).

## Supported Codes

Motivos: 01,02,03,04,05,06,07,08,09,13,14,17,18 desde REMITTER_REASONS. Indicadores: IndicadorRetornoVehiculoEnvaseVacio, IndicadorRetornoVehiculoVacio, IndicadorTrasladoVehiculoM1L desde keys de NubefactGreService::REMITTER_INDICATORS. No sustituir esas keys por sus valores externos04/05/06. Sin llamada remota ni lista JS paralela.

Store: code required/string/in/unique; name required/string/max120 (motivos) o160 (indicadores); sort_order required/integer/between0,65535; is_active required/boolean. Reglas no modificadas ni ejecutadas por POST.

## Edit Contract

Tabla compacta con botón Editar y modal Bootstrap next-aux-modal/form. Se trasladan los forms existentes conservando payload. Name max120 salvo documentos/indicadores160; orden0–65535; estado boolean. Record_id restaura old(); modal con error se reabre y muestra errores dentro. Código no editable. No AJAX nuevo: sólo script de reapertura visual tras validación. UI INTEGRATED / QA PENDING para PUT.

## Active / Sorting

orderBy(sort_order)->orderBy(code) intacto, sin sorting JS. Inactivos siguen presentes en query; badges Activo/Inactivo con texto. Sin cambios de disponibilidad GRE. Delete NOT AVAILABLE.

## Visual Integration

REUSE WITH CONTRACT ADAPTATION: baseline venti-next, Platform Tax Types y Surface Spacing Standard. next-page-heading/toolbar, nav-pills/aria-current, next-list-summary, next-data-table, next-table-empty, next-form-section y next-aux-modal. Tabla con scroll local; código/orden/estado/acciones centrados, nombre izquierda. CSS nuevo0; ninguna dependencia nueva.

## Responsive

Chromium headless con HTML del controller/Blade real, autenticación en memoria y assets locales8080. Requests ajenas al catálogo interceptadas; toda mutación bloqueada. Matriz320/375/390/576/768/992/1200/1440 de los cinco listados/edición y alta/empty controlados. No equivale a sesión HTTP completa. Evidencia temporal: shipping-contract.json, shipping-qa.json, shipping-list-{375,1440}.png, shipping-edit-{375,1440}.png en TEMP. Resultado final abajo.

## ShippingGuide Boundary

No cambios en ShippingGuide ni ReferralGuide, no emisión GRE ni llamadas Nubefact/SUNAT. ShippingGuide Create STILL WAIT FOR DEV; GUIDE-01/02 OPEN.

## Mutation Boundary

POST/PUT no ejecutados: MUTATION QA PENDING. No registros creados/editados, sin cambios de activo/orden/códigos/nombres. No delete/restore.

## Backend Sync Notes

[OPEN] GUIDE-01/02: autorización e idempotencia/failure contract de ShippingGuide Create/Store, fuera de esta superficie. Frontend de catálogos puede continuar: Sí. Ningún nuevo defecto backend identificado aquí.

Archivos de esta fase: resources/views/platform/shippingCatalogs/index.blade.php, config/venti-next.php y este documento. Readiness/backlog ya estaban modificados al inicio; no se alteraron en esta fase. Sin commit.

## QA Closure

- PASS: cinco listados × ocho anchos, edición × ocho anchos; alta/empty en memoria × ocho anchos. Scroll global igual al viewport; tabla permite scroll local. Códigos de indicadores largos sin overflow global.
- PASS: apertura modal, Escape con foco dentro y código readonly; captura desktop1440 y móvil375 revisada. El primer harness enviaba Escape antes de finalizar transición/foco; se corrigió el timing del harness, no el plugin.
- PASS: validación visual controlada sin POST: old name vacío, active0 y error conservados; modal correcto reabierto. Alta vacía checkValidity=false en ocho anchos. Esto no certifica validación por HTTP ni persistencia.
- PASS: allowlist en cinco rutas GET; venti.min.css y venti-next.css cargados. Cero excepciones JavaScript; cero POST/PUT intentados. Única respuesta404 de asset: avatar preexistente /images/users/1.png en shell Platform Admin; no modificado, no se afirma red completamente limpia.
- PASS: PHP7.3.33 artisan view:cache; php -l config/venti-next.php; git diff --check. Script de reapertura ejecutado sin error en Chromium.
- Limitación: QA con render real y contexto Admin en proceso, no navegación autenticada HTTP completa. Mutaciones permanecen QA PENDING. No llamadas GRE ni proveedor.

Final: **PLATFORM SHIPPING CATALOGS — FRONTEND INTEGRATED**. Create/Edit UI INTEGRATED / QA PENDING; GLOBAL PLATFORM; platform.admin;29 records; DELETE NOT AVAILABLE; backend UNCHANGED; CSS0.
