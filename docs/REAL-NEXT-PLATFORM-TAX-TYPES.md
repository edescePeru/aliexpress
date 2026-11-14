# REAL NEXT — Platform Tax Types

Fecha: 2026-11-11. Integración visual del catálogo global; sin cambios backend, CSS ni datos fiscales. Sin commit.

## Contract Audit

Fuente funcional: `TypeTaxController`, `TypeTax`, `TypeTaxResolverService`, rutas de plataforma y `public/js/typeTax/index.js`. Listado server-rendered con todos los registros, ordenados por default descendente y nombre. No existe contrato de búsqueda ni paginación en esta pantalla; no se añade uno.

Se conserva jquery-confirm para crear/editar, AJAX POST, nombres de inputs, checkboxes e IDs existentes, atributos `data-edit-tax`, URLs en `typeTaxApp`, CSRF por header desde meta y lectura de errores JSON. Respuestas: creación 201, actualización 200; validaciones/restricciones 422; registro inexistente 404. No hay eliminación ni endpoints separados para activar/default.

## Routes

| Método | Ruta | Nombre | Acción |
| --- | --- | --- | --- |
| GET | `/dashboard/plataforma/tipos-impuesto` | `platformTypeTax.index` | `TypeTaxController@index` |
| POST | `/dashboard/plataforma/tipos-impuesto` | `platformTypeTax.store` | `TypeTaxController@store` |
| POST | `/dashboard/plataforma/tipos-impuesto/{id}/update` | `platformTypeTax.update` | `TypeTaxController@update` |

Middleware del grupo: `auth`, `check.user.enabled`, `password.changed`, `platform.admin`, además del grupo web/CSRF. Sin permisos granulares adicionales en estas rutas.

## Platform Scope

GLOBAL DE PLATAFORMA. Modelo sin TenantScope ni company scope. `EnsurePlatformAdmin` comprueba `is_platform_admin` y limpia `TenantContext`; no exige seleccionar tenant/company/branch. No se añadieron selectores ni parámetros contextuales.

QA autenticado con Administrador Venti360 (`admin@venti360.com`), cabecera Administrador y sin contexto operacional visible. Listado accesible sin selección empresarial. Al abrir inicialmente con la sesión Owner previa, el guard devolvió 403; las pruebas funcionales posteriores se hicieron exclusivamente como Platform Admin.

## Fields

| Campo | Contrato backend | Presentación |
| --- | --- | --- |
| `code` | Requerido, único, máximo 50, normalización trim/uppercase, regex A-Z/0-9/underscore. No actualizable | Centrado; editable en alta, readonly y excluido del payload en edición |
| `name` | Requerido, string máximo 100, trim al persistir | Nombre alineado a izquierda |
| `tax` | Numérico 0–100, cast decimal:4 | Tasa derecha, hasta cuatro decimales sin ceros finales; input conserva min/max/step |
| `is_active` | Boolean requerido | Badge Activo/Inactivo y checkbox |
| `is_default` | Boolean requerido con restricciones | Badge Predeterminado o guion y checkbox |

El payload sigue enviando booleanos como 1/0. No se agregaron campos de clasificación fiscal. Datos observados: IGV_18 (18%, default activo), EXONERADO (0%, activo), IGV_19 (19%, activo), INAFECTO (0%, activo). Son datos locales, no afirmaciones sobre tasas legales vigentes.

## Default Contract

- Default debe estar activo; de lo contrario retorna 422.
- No se puede quitar/desactivar directamente el default actual. Primero debe elegirse otro; el backend retorna su mensaje explicativo.
- Seleccionar otro default desmarca los anteriores dentro de una transacción.
- `TypeTaxResolverService::resolveDefault()` exige exactamente un default activo: falla si no hay ninguno o hay varios. La resolución por ID admite el registro explícito, incluso histórico inactivo.
- La migración agrega unicidad a `code`, pero solamente índices ordinarios para los flags. No se certifica exclusión concurrente mediante restricción única de default. Crear un registro ordinario tampoco garantiza reparar un catálogo inicialmente sin default.
- El frontend conserva `getErrorMessage`: primer error de validación o `responseJSON.message`, mostrado por `showError`. Se preservó la validación JS preexistente default/inactivo; no se añadió lógica fiscal. El error backend no se provocó mediante POST en QA.

## Consumers

| Superficie | Evidencia y alcance |
| --- | --- |
| Material Create | `MaterialController` carga impuestos activos/default; `StoreMaterialRequest` valida selección activa |
| Material Edit | `MaterialController` y `UpdateMaterialRequest`; mantiene el contrato para impuesto histórico y valida cambios |
| POS | `PosCatalogReadService` carga `material.typeTax`; `PosLineCalculator` valida impuesto activo, tasa y clasificación; `PosSaleService` guarda snapshots fiscales |
| Emisión/Invoice | `PosEmissionPayload` consume snapshots y códigos del proveedor; no se introduce lectura nueva del catálogo desde esta UI |
| Cotizaciones | `QuoteSaleController`, `TypeTaxResolverService`, `QuoteTaxCalculatorService`; materiales, servicios y datos históricos |
| Equipos | Relaciones `typeTax()` en `EquipmentConsumable` y `EquipmentWorkforce` |
| Finance | No se encontró referencia directa TypeTax/type_tax en controladores con nombre Invoice/Finance; no se presume dependencia directa ni se certifica toda la cadena financiera |

El calculador de cotizaciones clasifica IGV_* / EXONERADO / INAFECTO y rechaza códigos no soportados. POS utiliza sus propias reglas de tasa/código/nombre. La validación sintáctica del CRUD no equivale a soporte de un código nuevo en todos los consumidores. Ningún consumidor fue modificado.

## Visual Mapping

- Encabezado y toolbar compacta Platform Admin; `next-page-heading`, `next-page-toolbar`, acciones compartidas.
- Listado `next-operational-list`, summary y `next-data-table`; alineación por tipo, badges semánticos con texto y empty state.
- Action dropdown compartido `next-row-actions` conserva hook de edición delegado.
- Formularios jquery-confirm con grid Bootstrap 4 centrado y `next-form-section` para Surface Spacing Standard; etiquetas asociadas a nuevos IDs de campos que antes no tenían ID. IDs existentes intactos.
- Escape de comillas en valores interpolados para que un nombre no rompa el atributo del input.
- ALLOWLIST MATCH: YES. Solo se agregó `platformTypeTax.index`; no rutas POST. Verificada carga de `admin/dist/css/venti.min.css` y `admin/dist/css/venti-next.css`.
- CSS nuevo: 0. Sin modificación de estilos globales/vendor.

## Responsive QA

Mediciones del listado y ambos modales en navegador local, altura 900px. `documentElement.scrollWidth` igual al viewport en todos los casos; sin overflow interno horizontal del modal.

| Ancho | Listado | Create/Edit | Overflow global |
| --- | --- | --- | --- |
| 320 | Scroll local de tabla | PASS | NONE |
| 375 | Scroll local de tabla | PASS | NONE |
| 390 | Scroll local de tabla | PASS | NONE |
| 576 | Scroll local de tabla | PASS | NONE |
| 768 | Scroll local de tabla | PASS | NONE |
| 992 | Scroll local de tabla | PASS | NONE |
| 1200 | Tabla completa | PASS | NONE |
| 1440 | Tabla completa | PASS | NONE |

Inspección visual desktop 1440 y edición móvil 375×812: formulario, padding y footer accesibles. Apertura/cierre de modales y dropdown de edición PASS. IGV_18 readonly; nombre/tasa/flags coinciden con listado. Validaciones visibles y recorrido de error auditados por código; no se pulsó Guardar. Sin errores de consola capturados. Avatar del shell sin imagen válida preexistente, fuera de alcance.

Evidencia externa al repo: `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/platform-tax-types-desktop.png` y `platform-tax-types-mobile.png`.

Checks: `node --check public/js/typeTax/index.js`, PHP 7.3 `artisan view:cache`, `git diff --check`: PASS. Empty state e inactivo verificados en plantilla; no se vació ni alteró catálogo para simularlos.

## Mutation Exclusions

MUTATION QA PENDING. No crear, editar, activar/desactivar, cambiar default ni eliminar. No POST fiscal, ventas ni cambios en consumidores. Cancelación de los formularios sin guardar. No se verificó persistencia ni respuesta real 422 mediante escritura.

## Backend Sync Notes

Backend UNCHANGED. Sin blocker de integración visual. Quedan abiertos para backend/QA: garantía concurrente de default único (no existe índice único del indicador), contrato de clasificación de códigos nuevos entre consumidores y pruebas de mutaciones autorizadas. No se intenta resolverlos desde JavaScript ni se cierran pendientes de otras fases.

## Revisit Triggers

Reauditar si cambia code/tasa/flags, rutas/payload/errores, restricciones de default, middleware de plataforma, clasificación fiscal de consumidores o esquema. Ejecutar QA de mutaciones únicamente en un entorno/dataset autorizado para escritura. No reabrir Company Settings por esta fase.

## Final Status

- Platform Tax Types — FRONTEND INTEGRATED
- Global Platform Scope — QA PASSED
- Fiscal Mutations — QA PENDING
- Backend — UNCHANGED
