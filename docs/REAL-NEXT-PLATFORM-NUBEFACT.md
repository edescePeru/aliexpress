# REAL NEXT — Platform Nubefact Configuration

Fecha: 2026-11-11. Frontend integrado sobre contrato vigente. Sin guardar configuración, consultar/descifrar tokens, probar conexión externa ni emitir. Backend y consumidores sin cambios. Sin commit.

## Contract Audit

Fuentes: `Platform/NubefactConfigController`, `NubefactCompanyConfigService`, `NubefactCompanyConnection`, `CompanyNubefactConfig`, `PlatformAuditService`, `config/nubefact.php`, migración `2026_10_13_000001_create_company_nubefact_configs_table`, rutas y los dos Blade.

Listado server-rendered de empresas con tenant/configuración, filtro GET `tenant_id` y paginación de 20. El filtro administrativo no modifica TenantContext. Edición POST convencional con CSRF, errores Blade y redirect. Se conserva prevención de doble submit, validación nativa, restablecimiento en pageshow y alerta de éxito. No existe botón de probar conexión.

## Routes

| Método | Ruta | Nombre | Acción |
| --- | --- | --- | --- |
| GET | `/dashboard/plataforma/nubefact` | `platformNubefact.index` | index |
| GET | `/dashboard/plataforma/tenants/{tenantId}/companies/{companyId}/nubefact` | `platformNubefact.edit` | edit |
| POST | `/dashboard/plataforma/tenants/{tenantId}/companies/{companyId}/nubefact` | `platformNubefact.update` | update |

IDs de ruta numéricos. Middleware web, auth, check.user.enabled, password.changed, platform.admin; constructor refuerza auth/platform.admin. No permiso granular adicional en estas rutas.

## Platform Scope

Administrador global con `is_platform_admin`. `EnsurePlatformAdmin` limpia TenantContext. El par seleccionado viene de la ruta administrativa, no de la sesión operacional. La vista no permite elegir tenant/company/RUC en el POST. `Company` no tiene TenantScope; las consultas administrativas de configuración retiran explícitamente TenantScope y restringen el par.

## Tenant / Company Pair Validation

`edit` y `update` buscan Company por ID y tenant_id juntos con firstOrFail. Relación tenant y FK del esquema sostienen pertenencia; update vuelve a bloquear/buscar tenant y company dentro de transacción. Company ID único en configuraciones. Modelo saving valida par y RUC coincidente de 11 dígitos.

| Ruta GET | Tenant | Company | Guard | Permission | Resultado QA |
| --- | --- | --- | --- | --- | --- |
| `/dashboard/plataforma/nubefact` | Global | Global | auth + platform.admin | is_platform_admin | Listado: 5 empresas |
| `/dashboard/plataforma/nubefact?tenant_id=3` | 3 | Filtro | Mismo | Mismo | Solo Gamarra; volver a Todos restaura 5 |
| `/dashboard/plataforma/tenants/1/companies/1/nubefact` | EDESCE (1) | EDESCE (1) | Mismo + par | Mismo | Abre configuración habilitada |
| `/dashboard/plataforma/tenants/3/companies/3/nubefact` | Gamarra (3) | Gamarra (3) | Mismo + par | Mismo | Abre Sin configurar |
| `/dashboard/plataforma/tenants/1/companies/3/nubefact` | EDESCE (1) | Gamarra (3) | Mismo + par | Mismo | 404 Not Found |

QA con cuenta autorizada Administrador Venti360, sin cambiar contexto operativo. No se exige que la empresa esté activa para abrir la administración; habilitar exige tenant y empresa activos en backend. Ausencia de tenant/company deriva en rechazo de búsqueda; ese caso no se añadió como prueba de navegador.

## Fields

| Campo | Contrato preservado |
| --- | --- |
| api_url | Requerido, URL/string máximo 2048; trim al guardar |
| api_token | Password, máximo 4096; vacío conserva credencial existente. Alta exige token; cambiar URL o RUC también exige uno nuevo |
| is_enabled | Boolean requerido; hidden 0 + checkbox 1, mismo ID/name |
| serie_factura / serie_boleta | Requeridas, cuatro caracteres, defaults existentes FFF1/BBB1 |
| serie_nc_factura / serie_nc_boleta | Opcionales, cuatro caracteres |
| version | Hidden nullable string máximo 40, `old(version, updated_at)` o vacío sin configuración |
| RUC / tenant / company | Solo contexto mostrado/resuelto por backend; no inputs editables |

No existen campos de ambiente, serie guía ni nota débito en este contrato. No se añadieron. Estados preservados: Sin configurar, Habilitada, Deshabilitada. Habilitada solo describe el flag; no significa Conectado ni certifica integridad de credenciales. No se inventa un estado Incompleto sin señal backend.

## Secret Handling

- El modelo cifra mediante Crypt::encryptString en api_token_encrypted, oculta api_token y api_token_encrypted en serialización y descifra únicamente mediante accessor. El servicio valida antes de construir una conexión con propiedades privadas.
- No se llamó al servicio ni al accessor en QA; no se consultaron secretos desde DB o .env.
- Input password sin value ni old(api_token), autocomplete=new-password. DOM de configuración existente: valor vacío, no atributo value, no required; formulario sin configuración: vacío y required.
- `safeInput` excluye token; Handler dontFlash excluye token/ciphertext. Auditoría usa una lista explícita sin token/ciphertext: RUC, flag, host y series, antes/después, además de token_changed booleano. Catch general registra IDs y clase de excepción, no request completo.
- TOKEN EXPOSED IN HTML: NO — plantilla sin binding del secreto; input inspeccionado vacío.
- TOKEN EXPOSED IN JS: NO — script preservado sin lectura ni serialización del secreto a consola; POST nativo solo ocurriría al guardar.
- TOKEN EXPOSED IN SCREENSHOT: NO — capturas del listado y formulario sin configuración, campos vacíos.
- TOKEN REPOPULATED: NO.

Conclusiones limitadas a estas superficies y código auditado; no son una auditoría de todos los logs históricos o infraestructura. No se verificó un error POST real con token por exclusión de mutaciones.

## Host / URL

Allowlist actual exacta: api.nubefact.com en config/nubefact.php. HTTPS; puerto ausente o 443; sin usuario/password, query ni fragment; path `/api/v1/[A-Za-z0-9-]+` con slash final opcional. Controller y servicio aplican estas reglas. No existe selector sandbox/producción ni se deduce ambiente de la URL. Solo se añadió ayuda textual; sin nueva validación JS.

## Series

Factura y NC factura: `^F[A-Z0-9]{3}$`. Boleta y NC boleta: `^B[A-Z0-9]{3}$`. Notas opcionales; `creditNoteSeries` rechaza emitir una nota sin serie configurada. `saleSeries` soporta tipos 01/03. Inputs mantienen pattern/maxlength/requerimiento/old/defaults. No normalización nueva ni numeración local; se conserva ayuda existente sobre numeración del proveedor.

## Versioning

OPTIMISTIC VERSIONING: PRESENT. Token de versión es updated_at convertido a string. Update bloquea tenant/company/config, compara versión recibida con actual y devuelve error si cambió. Se conserva hidden con old(), sin regeneración JS. Snapshot de configuración incluye RUC para detectar cambio de emisor; auditoría conserva valores no secretos antes/después. MUTATION QA PENDING: no se probó stale por POST. No se certifica concurrencia o precisión temporal con este QA visual.

## Visual Mapping

REUSE WITH CONTRACT ADAPTATION: patrones aprobados de `venti-next/resources/views/material/create.blade.php` y Platform Admin, con contrato real Nubefact.

- Listado: next-page-toolbar, next-operational-list, next-list-toolbar, summary, next-data-table, badges y empty state; paginador/filtro existentes intactos.
- Formulario: secciones Conexión/Series, next-section-header y next-form-section; se eliminan cards anidadas; Surface Spacing Standard, grid de series 1/2/4 columnas, alert informativo y acciones compartidas.
- Se preservan todas las etiquetas input originales, IDs/names, CSRF, version, values/old y script funcional. Diff automatizado confirmó inputs y bloque scripts idénticos.
- ALLOWLIST MATCH: YES para platformNubefact.index y platformNubefact.edit exclusivamente; POST no añadido. Carga comprobada de venti.min.css y venti-next.css en edit y shell NEXT en listado.
- CSS nuevo: 0. No cambios JS funcionales/backend.

## Consumers

- POS/PuntoVentaController, PosEmissionPayload y SaleEmissionService consumen conexión empresarial y series de venta.
- NubefactController resuelve conexión desde Sale mediante forSale.
- QuoteSaleController y NubefactTrait usan conexión empresarial; notas de crédito total/parcial resuelven desde la venta y exigen serie NC.
- Guías: ShippingGuideController utiliza NubefactGreService, que lee `services.nubefact.url` y `services.nubefact.token`. Es configuración separada; este formulario NO configura ni certifica guías.
- No se modificó ni invocó emisión, consultas, notas, guías o servicios externos.

## Responsive QA

| Ancho | Listado | Edit existente | Overflow global |
| --- | --- | --- | --- |
| 320 | PASS | PASS | NONE |
| 375 | PASS | PASS | NONE |
| 390 | PASS | PASS | NONE |
| 576 | PASS | PASS | NONE |
| 768 | PASS | PASS | NONE |
| 992 | PASS | PASS | NONE |
| 1200 | PASS | PASS | NONE |
| 1440 | PASS | PASS | NONE |

Medición DOM: scrollWidth <= viewport; inputs y botón dentro de ancho disponible. Tabla con contenedor de scroll local; formulario crece verticalmente. Inspección visual adicional Gamarra sin configuración en 1440 y 375: ayudas, URL/token, series y acciones legibles. Navegación Volver y filtro GET PASS. Consola final sin errores capturados. Paginación conserva contrato; dataset de cinco empresas no requiere segunda página. Estado deshabilitado/errores auditados en plantilla, no forzados mediante escritura.

Evidencia externa al repo: carpeta `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/`, archivos platform-nubefact-list.png, platform-nubefact-edit-desktop.png y platform-nubefact-edit-mobile.png. Sin secretos.

Checks: PHP 7.3 artisan view:cache y git diff --check PASS. Script funcional sin cambios; no se añadieron archivos JS.

## Mutation Exclusions

Configuration Mutation — QA PENDING. No se pulsó Guardar, no POST de configuración, no activación, cambio de URL/token/series ni pruebas de version stale. External Connection / Emission — OUT OF SCOPE. No se llamó Nubefact ni se ejecutaron comprobantes/consultas/guías. La integración visual no aprueba emisión.

## Security Notes

Par cruzado rechazado en GET; backend controla pertenencia y emisor también al persistir. No cambió autorización, TenantContext ni manejo de secretos. Host permitido y cifrado se auditaron en código, sin credenciales reales. Plataforma opera sin seleccionar sucursal.

## Backend Sync Notes

[OPEN]
Point: guías mantienen una fuente de configuración separada.
File / endpoint: NubefactGreService / services.nubefact.
Impact: este mantenimiento no configura emisión de guías.
What backend needs to define or correct: confirmar si la separación es permanente o planificar convergencia empresarial en una fase backend independiente.
Frontend can continue: Yes.

Persistencia, stale protection, rotación de credenciales y auditoría real de actualización requieren QA autorizado de mutaciones. No son blockers visuales ni fueron declarados resueltos. Backend UNCHANGED.

## Revisit Triggers

Cambios de hosts, URL/series, claves/almacenamiento, versionado, scope/permisos, campos de ambiente, integración de guías o reglas de emisión. Reauditar manejo de secretos si cambia cómo se entregan datos a Blade/JS. No reabrir TypeTax/Settings sin regresión directa.

## Final Status

- Platform Nubefact List — FRONTEND INTEGRATED
- Platform Nubefact Edit — FRONTEND INTEGRATED
- Platform Scope — QA PASSED
- Secret Handling — QA PASSED (alcance de esta UI)
- Configuration Mutation — QA PENDING
- External Connection / Emission — OUT OF SCOPE
- Backend — UNCHANGED
