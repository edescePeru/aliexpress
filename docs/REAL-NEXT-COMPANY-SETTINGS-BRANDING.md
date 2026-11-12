# REAL NEXT — Company Settings & Branding

Fecha: 2026-11-11. Ruta `/dashboard/configuracion/configuraciones/empresa` (`companySetting.index`). Backend unchanged. Sin guardar Settings, subir imágenes, modificar DB ni hacer commit. Inventario, Store Web y POS no modificados.

## Contract Audit

Revisados CompanySettingController, CompanySettingImageService, SettingService, SettingDefinition/SettingValue, config/settings_catalog.php, routes/web.php, EnsureTenantOwner, Blade y JS actuales. El catálogo de config sirve de definición inicial; la pantalla usa **setting_definitions activa en DB**, company + editable_by_owner, orden module/label y groupBy(module). Confirmadas36 definiciones activas en9 módulos. No asumir que config sustituye al catálogo runtime.

Baseline reutilizado: venti-next/resources/views/material/create.blade.php (Complex Form), Surface Spacing Standard y composición de tenantBranch/create. Se mantienen inputs dinámicos, IDs, names, old(), CSRF, FormData y hooks de imagen. No se copian contratos del experimental.

## Settings Definitions

Defaults de definiciones, no valores privados de overrides. Todas las filas siguientes tienen scope company, editable_by_owner=true e is_active=true.

|Setting|Scope|Tipo|Editable|Default|Persistencia|Validación|
|---|---|---|---|---|---|---|
|catalog.footer_description|company|string|Sí|null|value_text|nullable / cast string|
|catalog.show_prices|company|boolean|Sí|1|value_number|normalizeBoolean: 0/1 y representaciones aceptadas|
|catalog.show_presentations|company|boolean|Sí|1|value_number|normalizeBoolean: 0/1 y representaciones aceptadas|
|company.social.facebook|company|string|Sí|null|value_text|nullable / cast string|
|company.social.instagram|company|string|Sí|null|value_text|nullable / cast string|
|company.branding.logo|company|string → image|Sí|null|value_text + PNG contextual|PNG/JPG/WebP, 2MB, 4096px/lado, 8MP|
|company.branding.logo_monochrome|company|string → image|Sí|null|value_text + PNG contextual|PNG/JPG/WebP, 2MB, 4096px/lado, 8MP|
|company.social.pinterest|company|string|Sí|null|value_text|nullable / cast string|
|company.branding.quote_reference|company|string|Sí|null|value_text|nullable / cast string|
|company.profile.website|company|string|Sí|null|value_text|nullable / cast string|
|company.branding.quote_text|company|string|Sí|null|value_text|nullable / cast string|
|company.social.tiktok|company|string|Sí|null|value_text|nullable / cast string|
|company.social.twitter|company|string|Sí|null|value_text|nullable / cast string|
|company.profile.whatsapp|company|string|Sí|null|value_text|nullable / cast string|
|company.social.youtube|company|string|Sí|null|value_text|nullable / cast string|
|finance.base_currency|company|enum|Sí|PEN|value_text|nullable / pertenencia estricta a options_json: PEN, USD|
|hr.break_hours|company|decimal|Sí|1|value_number|nullable / is_numeric|
|inventory.rotation.low_end|company|decimal|Sí|40|value_number|nullable / is_numeric|
|inventory.rotation.medium_end|company|decimal|Sí|70|value_number|nullable / is_numeric|
|inventory.rotation.high_start|company|decimal|Sí|70|value_number|nullable / is_numeric|
|inventory.rotation.low_start|company|decimal|Sí|0|value_number|nullable / is_numeric|
|inventory.rotation.medium_start|company|decimal|Sí|40|value_number|nullable / is_numeric|
|inventory.minimum_stock.show_alerts|company|boolean|Sí|0|value_number|normalizeBoolean: 0/1 y representaciones aceptadas|
|inventory.stock_notifications.popup|company|boolean|Sí|0|value_number|normalizeBoolean: 0/1 y representaciones aceptadas|
|inventory.stock_notifications.bell|company|boolean|Sí|0|value_number|normalizeBoolean: 0/1 y representaciones aceptadas|
|inventory.stock_notifications.email|company|boolean|Sí|0|value_number|normalizeBoolean: 0/1 y representaciones aceptadas|
|inventory.stock_notifications.telegram|company|boolean|Sí|0|value_number|normalizeBoolean: 0/1 y representaciones aceptadas|
|payroll.days_per_month|company|integer|Sí|30|value_number|nullable / FILTER_VALIDATE_INT|
|payroll.days_per_week|company|integer|Sí|7|value_number|nullable / FILTER_VALIDATE_INT|
|payroll.pay_slip_frequency|company|enum|Sí|semanal|value_text|nullable / pertenencia estricta a options_json: semanal, mensual, anual|
|payroll.hours_per_day|company|decimal|Sí|8|value_number|nullable / is_numeric|
|payroll.hours_per_week|company|decimal|Sí|48|value_number|nullable / is_numeric|
|pos.require_worker|company|boolean|Sí|0|value_number|normalizeBoolean: 0/1 y representaciones aceptadas|
|sales.goals.frequency|company|enum|Sí|null|value_text|nullable / pertenencia estricta a options_json: semanal, quincenal, mensual|
|sales.allow_partial_payments|company|boolean|Sí|0|value_number|normalizeBoolean: 0/1 y representaciones aceptadas|
|supplier_credit.expiration_warning_days|company|integer|Sí|7|value_number|nullable / FILTER_VALIDATE_INT|

Persistencia en setting_values por setting_definition_id + tenant_id + company_id + branch_id NULL. Ausencia de override vuelve al default casteado. El backend serializa actualizaciones por company y normaliza valores en SettingService. No se inventaron rangos min/max, formatos URL/teléfono ni campos adicionales.

## Scope

Ruta bajo auth, check.user.enabled, password.changed, tenant.context, tenant.owner. EnsureTenantOwner exige Owner y rechaza Platform Admin. ImageService.authorize añade usuario habilitado, mismo tenant, company activa, membresía company_user activa y permiso. Branch aparece en shell, pero **Settings es company-wide**, branch_id NULL; no se cambió este contrato.

## Permissions

GET companySetting.index: list_companySetting. POST companySetting.update (`/dashboard/configuracion/configuraciones/empresa/update`): edit_companySetting. Botón Guardar conserva @can. La autorización final pertenece al backend; no se realizaron POST de Settings.

## Sections

Orden exacto del backend: CATALOG, COMPANY, FINANCE, HR, INVENTORY, PAYROLL, POS, SALES, SUPPLIER CREDIT. No se separó branding/contacto/redes arbitrariamente: siguen dentro de COMPANY según definición. Cada módulo usa next-form-section y next-section-header numerado, sin cards anidadas. Labels/ayuda a la izquierda y control a la derecha desde lg; apilado debajo. Se conserva la jerarquía de controles y la densidad ERP.

## Input Types

- Boolean: custom-control/custom-switch compacto existente, checkbox value1 y hidden fallback0 del mismo name; checked efectivo y old() intactos.
- Enum: select con opciones backend. Corrección necesaria: opción vacía «Sin configurar» representa null/empty, ya admitido por SettingService. Antes el navegador mostraba y enviaba la primera opción aunque el valor efectivo fuera null. No se inventó un default.
- Integer: number step1. Decimal: number step0.01, sin nueva escala o límites.
- String: text. No se sustituyó por URL/tel/textarea con otra validación.
- Image: input file setting_images[id], no settings[key].
- JSON mantiene mensaje no editable; no hay definiciones JSON activas en este dataset. Tipo desconocido conserva aviso existente.

JS conserva POST/FormData, processData:false, contentType:false, captura antes de deshabilitar, restauración de controles, errores AJAX y previews tras éxito. Único cambio JS: actualizar solo .custom-control-label del toggle. El selector anterior también sobrescribía el label principal del setting; comprobado y corregido sin cambiar su valor/payload.

## Branding

Logo y logo monocromático, website, WhatsApp, seis redes (Facebook, Instagram, Pinterest, TikTok, Twitter/X, YouTube), texto institucional y referencia. Catálogo tiene footer, show_prices y show_presentations. No se agregaron color, favicon, tema ni otros campos. Ninguna definición activa es un secreto; no se copiaron valores de overrides en este informe. No existe metadata secret en SettingDefinition: si aparecen secretos en el futuro será necesaria revisión explícita, no tratarlos automáticamente como string público.

## Images

ImageService.supports exige scope company + string + options_json.input=image. Validación real de archivo e imagen: JPEG/PNG/WebP, >0 y <=2097152bytes, <=4096px por lado y <=8388608pixels. Orientación y resize hasta1200x600 preservando proporción/sin upsize, recodificación PNG con transparencia.

Destino: public/images/logo/tenant-N/company-N/setting-ID-RANDOM.png. No se modifica archivo anterior hasta guardar nueva ruta correctamente. Ante fallo se descartan nuevos archivos sin referencias; limpieza previa solo para patrón propio de company actual y sin referencias globales. Archivos legacy/ajenos no se eliminan. preview comprueba path contenido en images/logo y permite compatibilidad legacy; no debe confundirse con autorización HTTP de archivos públicos.

UI conserva imagen real (180px de atributo, img-fluid/img-thumbnail), sin dummy, estado vacío explícito y ayuda completa de formato/tamaño. JS local existente usa object URL, estado «Nueva imagen pendiente de guardar» y revocación. Inspección estática de selección local; **no se seleccionó ni subió ningún archivo en QA**. UPLOAD MUTATION QA PENDING. Persistencia, recodificación y cleanup no ejecutados.

## Snapshot

Dos hidden originales: settings_context_tenant y settings_context_company. FormData captura ambos desde Blade; no se regeneran desde JS. ImageService.authorize compara contexto antes de validaciones/transacción. Prueba directa de authorize en transacción SQL READ ONLY: contexto válido PASS y tenant/company stale422 en los tres contextos; no se invocó update ni store. No afirmar que es prueba HTTP de guardado.

No hay version/hash de valores: protege cambio de contexto, no ediciones concurrentes de dos pestañas de la misma company. Contrato preservado, sin añadir optimistic locking desde frontend.

## Store Web Consumers

|Setting|Consumer confirmado|
|---|---|
|company.branding.logo|StoreWebController; documentos de PuntoVenta/QuoteSale/OrderPurchase|
|company.profile.whatsapp|StoreWebController; PublicCatalogController.detail vía PublicCatalog.setting|
|catalog.show_prices|StoreWebController; PublicCatalogController.showPrices (también suprime filtros precio)|
|catalog.show_presentations|StoreWebController; no afirmar conexión equivalente en PublicCatalogController|
|catalog.footer_description|StoreWebController|
|company.social.*|StoreWebController, mapa social de seis redes|
|company.profile.website|QuoteSaleController y OrderPurchaseController para documentos|
|company.branding.quote_text / quote_reference|QuoteSaleController para documentos comerciales|

PublicCatalog.setting resuelve explícitamente tenant/company del catálogo público y branch NULL. Estos consumidores se rastrearon en código; no se modificaron ni se declara cerrada su integración visual/funcional. SW-01…SW-07 no se cierran por modernizar Settings.

## POS Consumers

PosScreenOptions.data v1/v2 usa pos.require_worker (selección **opcional**, aunque el key se llame require) y sales.allow_partial_payments (s/n). PosSaleService verifica pagos parciales; FreeSaleController y QuoteSaleController también consumen ese setting. PuntoVentaController usa logo/logo_monochrome en documentos. POS V1/V2 permanece congelado e intacto.

## Responsive QA

ALLOWLIST MATCH: YES para companySetting.index. Observados venti.min.css, venti-next.css y JS compartido del layout. **0 reglas CSS nuevas**. Reutilizados next-page-heading, next-page-toolbar, next-form-section, next-section-header, next-toolbar-actions, controles Bootstrap y preview existente. Se retiran márgenes negativos del footer y cards/body anidados. Se conserva sticky inferior mediante propiedades inline ya existentes bottom0; z-index20 alineado con header operacional, sin usar1030. Gutters del wrapper, no márgenes por campo.

|Viewport|Root/client px|Selector de archivo contenido|Resultado|
|---:|---|---|---|
|320|305/305|239/239|PASS|
|375|360/360|294/294|PASS|
|390|375/375|309/309|PASS|
|576|561/561|479/479|PASS|
|768|753/753|647/647|PASS|
|992|977/977|501.83/501.83|PASS|
|1200|1185/1185|623.16/623.16|PASS|
|1440|1425/1425|763.16/763.16|PASS|

Sin overflow global. Diferencia15px es scrollbar vertical. Labels largos, claves con text-break, controles, ayuda y footer contenidos. Revisado toggle con cambio local y reversión sin guardar: label principal permanece y solo cambia el estado. Blade compilado con PHP7.3.33, node --check y git diff --check PASS. Comparación de atributos contra HEAD: names/IDs/types/steps/accept/action/method/enctype preservados; única opción de valor añadida es vacío del enum nullable.

## Context QA

|Cuenta / rol|T/C/B|Definiciones|Overrides company|Branding|
|---|---|---:|---:|---|
|Owner EDESCE user4 / owner|1/1/1|36|36|Dos imágenes existentes, cargan desde path tenant1/company1|
|Owner EDESCE user4 / owner|1/2/2|36|1|Sin imagen, monedaUSD, defaults restantes|
|Owner Gamarra user7 / owner|3/3/4|36|0|Sin imagen, monedaPEN/defaults|

Validado servicio/controller de lectura en SQL START TRANSACTION READ ONLY + ROLLBACK, y navegador autenticado con cambio real de company/sesión. Misma lista de definiciones no significa valores cruzados: catálogo global de definiciones, overrides contextuales. EDESCE1/2 difieren en6 claves; EDESCE1/Gamarra en5. IDs de overrides separados (C1:3,5–39; C2:4; C3:ninguno). No se publican sus valores privados. Acceso servicio a company3 usando OwnerEDESCE/tenant1:403. Snapshot stale tenant y company:422 por authorize sin escribir. **Context Isolation QA PASSED para el read path y guard auditados**.

Evidencia local: C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/company-settings-qa.json (metadatos, IDs y resultados; sin contraseñas ni valores privados de overrides).

## Mutation Exclusions

No click Guardar; no POST de Settings, upload, store, set/reset, cleanup ni modificaciones DB/archivos persistidos. Login/logout y cambio de contexto autorizados solamente. Setting Mutations: QA PENDING. Image Upload: QA PENDING. No bloquean la fachada visual.

## Backend Sync Notes

[OPEN]
Point: validación de mutaciones y upload no ejecutada por alcance explícito.
File / endpoint: companySetting.update / CompanySettingImageService.
Impact: no certificar persistencia, recodificación, rollback ni cleanup end-to-end.
What backend needs to define or correct: no corrección confirmada; reservar QA autorizado de escritura.
Frontend can continue: Yes.

[OPEN]
Point: snapshot es T/C, sin versión de valores.
File / endpoint: CompanySettingImageService.authorize.
Impact: no detecta edición concurrente en la misma company; conservar como contrato actual.
What backend needs to define or correct: política de concurrencia solo si se requiere protección adicional.
Frontend can continue: Yes.

[RESOLVED FRONTEND]
Point: toggle reemplazaba nombre de setting; enum null aparentaba primera opción.
Final contract: etiqueta de estado acotada al switch; null representado por opción vacía admitida por backend.
Date: 2026-11-11. Backend sin cambios.

## Revisit Triggers

Nuevas definiciones/tipos, secretos, cambio de options_json, scopes, permisos, snapshots/versionado, reglas de imágenes/storage, nuevos consumidores PublicCatalog/POS o autorización para QA de escritura.

## Final status

- Company Settings UI: FRONTEND INTEGRATED.
- Branding: FRONTEND INTEGRATED.
- Context Isolation: QA PASSED (lectura + authorize; no guardado real).
- Setting Mutations: QA PENDING.
- Image Upload: QA PENDING / UPLOAD MUTATION QA PENDING.
- Backend: UNCHANGED.

Archivos modificados: resources/views/companySetting/index.blade.php; public/js/companySetting/index.js (etiqueta visual únicamente); config/venti-next.php; este documento. Sin commit.

## Evidencia visual y cierre

Capturas verificadas en la carpeta local de QA: `company-settings-branding-desktop.png` y `company-settings-branding-mobile.png`. Preview real cargado y contenido (180px en móvil), ayuda y botón sticky visibles, sin overflow. Sesión final restaurada a Owner EDESCE/company1/Local Principal; viewport temporal retirado. No quedó ningún cambio local de formulario pendiente.
