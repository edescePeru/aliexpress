# REAL NEXT — R7.6 — Post-DEV Functional Recovery

## Estado vigente R7.6.1 — Document Functional Recovery — 2026-11-25

Decisión explícita del propietario: virtual host descartado; mantener http://127.0.0.1:8080 y PHP7.3.33. La limitación de artisan serve no bloquea por sí sola los controles que usan el contrato autorizado DEV. Esta sección sustituye la clasificación de instalación pendiente de R7.6; la evidencia anterior se conserva como historia, no como estado vigente.

**DOCUMENT CAPABILITIES RESTORED: 8/8.** FR-049/054/083/088/093/096/097/098 recuperadas mediante entry.document.main / observation / extra y navegación contextual existente. No asset() para adjuntos ni fallback público. Los paths de los lectores coinciden intencionalmente con /images/entries/... por contrato DEV; usar route() no convierte artisan serve en un servidor que aplique .htaccess.

**107 FR = 74 recuperados + 10 bloqueados backend + 23 excluidos.** R7.6 acumula 12/12 recuperadas (4 anteriores +8 documentales). Permanecen FR047/048/059/063/064/102/103/105/106/107; no nuevos blockers del lector demostrados. DOC-01 conserva observación de entorno y verificación HTTP directa pendiente en despliegue, fuera del conteo de pérdidas frontend. No equivale a certificación integral de seguridad.

Validación propia: 8 tests Node PASS (4 documentales +4 regresión R7.6), Blade compile y sintaxis JS PASS; 16 comprobaciones Chrome headless aislado (8 superficies ×375/1440), 16 navegaciones interceptadas; cero pageerrors y overflow global. Metadatos documentales SIMULADOS sobre render/DTO real leído sin escrituras; ninguna descarga comercial. Permisos negativos simulados en renderer. No se repite certificación backend DEV ni J/P/K. Backend/CSS/infraestructura/mutaciones comerciales:0. Sin commit/push/merge. R7.7/R8 no iniciadas.

Propuesta Apache descartada: retirado docs/environment/aliexpress-apache-local.conf.example, generado exclusivamente para esa propuesta y nunca instalado. URL vigente: http://127.0.0.1:8080. Ver informe R76 para matriz y evidencia.

## R7.6.1 — Matriz de recuperación y límites de evidencia

| FR | Superficie / URL local exacta | Control / permiso |
|---|---|---|
| FR-049 | http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2 | Adjunto/observación: list_entryPurchase; Documentos extras: update_entryPurchase |
| FR-054 | http://127.0.0.1:8080/dashboard/entradas/compra/reporte | Adjunto/observación: list_entryPurchase |
| FR-083 | http://127.0.0.1:8080/dashboard/factura/compra | Adjunto/observación: list_invoice |
| FR-088 | http://127.0.0.1:8080/dashboard/reporte/faturas/finanzas | Adjunto/observación: list_invoice |
| FR-093 | http://127.0.0.1:8080/dashboard/reporte/faturas/finanzas/sin/orden | Adjunto/observación: list_invoice |
| FR-096 | http://127.0.0.1:8080/dashboard/factura/compra/anuladas | Adjunto/observación: list_invoice; state_annulled no equivale a soft delete |
| FR-097 | http://127.0.0.1:8080/dashboard/entrada/compra/visualizar/53 | Adjunto/observación: list_entryPurchase |
| FR-098 | http://127.0.0.1:8080/dashboard/factura/compra/ver/29 | Adjunto: list_invoice |

También actualizada la generación de URLs del destino extras: http://127.0.0.1:8080/dashboard/agregar/documentos/extras/entrada/53. IDs53/29 corresponden al contexto EDESCE T1/C1; no son URLs universales para otro contexto. En registros sin archivo se muestra el estado vacío, sin fabricar un adjunto.

### Contrato frontend

- Rutas nombradas entry.document.main, entry.document.observation, entry.document.extra. El filename proviene de metadata actual; sin lookup global ni tenant/company enviado desde JS. El lector valida contexto/permiso real y archivo.
- Renderer compartido conserva handlers operativos, permisos y condiciones anteriores. Iconos fa-file-pdf / fa-image / fa-folder-open con next-row-action-item-icon; menú existente, sin CSS. target=_blank/rel=noopener en nuevos lectores.
- Extras conserva data-image y showImagePreview, forms de DEV y handlers existentes; sólo reemplaza asset() por route(). No ejecución de upload/edit/delete. El GET extras puede registrar Audit técnico; no se invocó por HTTP real durante QA.
- Metadata ausente, placeholder no_image y nombres inválidos no crean enlaces. No fallback a asset(), /index.php ni URL alternativa. No detección de servidor para esconder controles.

### Evidencia DEV, sin atribuirla como ejecución propia

AUDITOR-CIERRE-AGRUPADO-2026-11-24.md informa suite de22 pruebas aisladas sobre clases instaladas; familia P revalida lectores de Entradas/extras y marcadores Apache de lotes previos. AUDITOR-P-EXTRAS-ENTRADAS.md registra permisos, pertenencia, formato, rollback y conservación de archivos. AUDITOR-J-P-K-CIERRE-NEGOCIO.md establece cierre por negocio sin afirmar ejecución de pruebas adicionales de despliegue. Se conserva exactamente ese límite: no certificación global de public, ni verificación HTTP directa realizada en esta fase.

### Evidencia propia focalizada

PHP7.3.33. Captura de controllers/Blades y DTOs actuales mediante conexión dedicada START TRANSACTION READ ONLY + ROLLBACK, session/cache array y cancelación de Audit creating sólo en el proceso QA. No cambio backend ni datos; no equivale a prueba HTTP integral de middleware.

Contextos leídos T1/C1/B1 (Owner EDESCE), T1/C2/B2 y T3/C3/B4 (Owner Gamarra). Primera página Entry C1:53,54,44,43,42,41,40,39,37,35; C2:52,49; C3:45. Facturas activas C1:55,38,36,29; anulada30; C2/C3 vacíos. Sólo metadata, ningún archivo comercial.

QA navegador: render real capturado y metadatos de archivo explícitamente SIMULADOS R761-SIMULATED.pdf/png; nunca guardados. Ocho pantallas ×375/1440 =16 checks, sin overflow global ni pageerrors. Quince enlaces documentales + navegación extras interceptados antes de filesystem/HTTP real. QA adicional extras: seis bindings (PDF/imagen en factura/guía/observación), apertura del modal de preview y destino PDF interceptado; no certifica bytes/decodificación del archivo. Ambos detalles reales sin adjuntos presentan empty state. Pruebas de permisos negativos son fixtures del renderer, no nueva certificación backend.

Artefactos fuera del repositorio: r761-read.php, r761-data.json, r761-browser.cjs/results.json, r761-extras-browser.cjs, r761-extra-results.json, r761-*.html/png en C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/. Capturas desktop y móvil inspeccionadas. node --test:8 PASS; node --check PASS; artisan view:cache PASS; git diff --check PASS.

### Archivos de esta fase

- AGENTS.md
- public/js/entry/list-operations.js
- resources/views/entry/partials/list-operations.blade.php
- resources/views/entry/partials/document-links.blade.php (nuevo)
- resources/views/entry/show_entry_purchase.blade.php
- resources/views/invoice/show_invoice.blade.php
- resources/views/entry/editImages.blade.php
- tests/Frontend/entry-document-recovery.test.cjs (nuevo)
- Este informe, recovery plan, backlog canónico y aclaración de estado DOC-01 en backlog DEV.
- Ejemplo Apache exclusivamente propuesto eliminado; ningún cambio de configuración instalado.

Se preservan los cambios R7.6 previos sin commit. FRONTEND FUNCTIONAL PARITY: ocho capacidades documentales recuperadas, diez pérdidas de otras familias aún bloqueadas. REMAINING BACKEND BLOCKERS documentales nuevos:0. NEW DEV FINDINGS:0. Protección HTTP directa en despliegue: PENDING. BACKEND/CSS/INFRASTRUCTURE CHANGES:0; REAL BUSINESS MUTATIONS:0. Esperar revisión del propietario.

## Evidencia anterior preservada

## Historia — Propuesta de entorno descartada por el propietario

2026-11-25. Apache2.4.46 / servicio wampapache64 RUNNING, puerto80. Virtual hosts efectivos: localhost, construction.corp, computer.corp, computer1.corp, dolareros.co, blog.site, edesce.site, restaurant.rest y game.site. No existe aliexpress. Default DocumentRoot C:/wamp64/www, AllowOverride All. PHP global cargado:8.4.24; no cambiarlo porque otros proyectos lo utilizan. rewrite_module y fcgid_module ya cargados. PHP7.3.33 cgi-fcgi disponible y versión verificada.

Puertos80,8080 y8090 ocupados. No se necesita puerto nuevo: propuesta de name-based vhost en80, ServerName127.0.0.2 (loopback). HEAD a http://127.0.0.2/ hoy responde200 del default Wamp; NO es aún aliexpress. Ningún hostname/vhost existente usa esa dirección. No editar hosts de Windows; localhost sigue siendo el vhost por defecto.

Configuración exacta preparada en `docs/environment/aliexpress-apache-local.conf.example`. DocumentRoot termina en aliexpress/public; Require local; AllowOverride All; sin Indexes/MultiViews; handler FastCGI PHP7.3.33 exclusivamente en ese directorio, manteniendo PHP8.4 global. Validación sin activar: httpd.exe -t -c "Include C:/wamp64/www/venti-adminlte-ui/aliexpress/docs/environment/aliexpress-apache-local.conf.example" → Syntax OK; -S con ese include reconoce127.0.0.2 y mantiene localhost default. Esto NO demuestra todavía ejecución HTTP PHP7.3 ni autorización documental.

APP_URL actual: http://aliexpress.site:8080/; no forceRootUrl/forceScheme propio encontrado. Laravel web genera URLs con el host de la solicitud; al activar se propone APP_URL=http://127.0.0.2 para coherencia de URLs CLI. Sin configuración Laravel cacheada al revisar. No se modificó .env.

### Pasos mínimos propuestos (no ejecutados)

1. Respaldar con nombre único httpd-vhosts.conf y .env antes de modificar; conservar copia local fuera del índice Git.
2. Copiar la configuración revisada a C:/wamp64/bin/apache/apache2.4.46/conf/extra/httpd-aliexpress-local.conf.
3. Añadir al FINAL de httpd-vhosts.conf una sola línea: `Include conf/extra/httpd-aliexpress-local.conf`. No reemplazar los vhosts existentes ni el PHP global.
4. Ejecutar `C:/wamp64/bin/apache/apache2.4.46/bin/httpd.exe -t` y `-S`; si falla, no recargar y restaurar únicamente la inclusión nueva.
5. Con aprobación del propietario, recargar mediante `httpd.exe -k restart -n wampapache64`. La recarga puede afectar brevemente conexiones de los otros proyectos; no se detendrá artisan serve ni cambiarán puertos.
6. Ajustar sólo APP_URL en .env al origen aprobado. No cambiar APP_KEY, DB, permisos, planes ni datos.
7. Verificar PHP HTTP usando probe mínimo propio (sólo versión/SAPI, nunca phpinfo), después borrarlo. Crear archivos sintéticos exclusivos en carpetas protegidas, probar dispatch/auth y limpiar. Probar lector con fixtures aislados para permisos/contexto sin asociar documentos a registros comerciales reales. Recuperar ocho controles sólo tras demostrar la protección.

URL propuesta para siguientes fases: **http://127.0.0.2/dashboard/principal**. No declararla activa antes de los pasos anteriores.

### Estado hasta recibir aprobación

APACHE ENVIRONMENT STATUS: AVAILABLE — VHOST ACTIVATION PENDING. VIRTUAL HOST: PREPARED / SYNTAX PASS, NOT INSTALLED. PHP VERSION: global8.4.24, propuesto porvhost7.3.33 (CGI binario PASS; HTTP pendiente). DOCUMENT DIRECT ACCESS PROTECTION / AUTHORIZED DOCUMENT READER STATUS: PENDING ON PROPOSED HOST. DOCUMENT CAPABILITIES RESTORED:0/8. CONFIGURATION CHANGES:0 aplicadas, sólo ejemplo revisable en repositorio. BACKEND/CSS CHANGES:0. Conteo66+8+10+23=107 intacto. No archivos sintéticos creados todavía, sin mutaciones comerciales/commit/push/merge. No R7.7/R8.

La consulta previa es un requisito explícito del propietario: «No detener servicios, cambiar puertos, modificar hosts de Windows ni alterar configuraciones globales de Apache sin consultarme». No es un gate nuevo de producto ni una reapertura de J/P.

Fecha: 2026-11-25. HEAD inicial ed33a9b1e7e48f0c9bb28fb48fc74ec0d100ecbc; merge98619bc consolidado. feature/venti-next-frontend, working tree inicialmente limpio. Sin commit, push ni merge. No R7.7 ni R8.

## Resultado

TARGET12; RESTORED4; STILL MISSING8; DOCUMENT CAPABILITIES RESTORED0/8 (la navegación de impresión FR036 es una capacidad aparte). FUNCTIONAL PARITY PARTIAL — DOCUMENT INSTALLATION REQUIRED. Los ocho accesos no se eliminan por QA pendiente: permanecen sin recuperar por una protección de runtime demostrablemente ausente en la URL solicitada. No se cambia el cierre J/P por negocio ni se exige su certificación completa.

## Migration / Platform installation

migrate:status confirma Yes / batch180 para 2026_11_21_000001_add_structure_limits_to_plans. Schema real consultado en transacción READ ONLY contiene max_active_companies y max_active_branches. No ejecutamos migraciones ni configuramos planes. El prerrequisito de schema Platform de R7.5 está resuelto; HTTP/UI/configuración global de Platform NO revalidados por exclusión expresa del alcance. No afirmar certificación completa de Platform.

## Doce capacidades individuales

| ID | Capacidad | URL local para revisión | Resultado | Contrato / límite |
|---|---|---|---|---|
| FR-017 | Crear compra normal | [http://127.0.0.1:8080/dashboard/crear/orden/compra/normal](http://127.0.0.1:8080/dashboard/crear/orden/compra/normal) | RESTORED | create_orderPurchaseNormal; toolbar @can; GET actual createNormalV2 |
| FR-018 | Editar compra normal | [http://127.0.0.1:8080/dashboard/editar/orden/compra/normal/4](http://127.0.0.1:8080/dashboard/editar/orden/compra/normal/4) | RESTORED | update_orderPurchaseNormal; opt-in sólo listado Normal; type=n |
| FR-036 | Imprimir compra eliminada | [http://127.0.0.1:8080/dashboard/ordenes/compra/eliminadas](http://127.0.0.1:8080/dashboard/ordenes/compra/eliminadas) | RESTORED | list_orderPurchaseNormal; destino /dashboard/imprimir/orden/compra/eliminada/1; no descargado |
| FR-046 | Crear entrada compra | [http://127.0.0.1:8080/dashboard/crear/entrada/compra](http://127.0.0.1:8080/dashboard/crear/entrada/compra) | RESTORED | create_entryPurchase; toolbar @can; formulario DEV sin homologación nueva |
| FR-049 | Documentos / extras Entrada | [http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2](http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2) | PENDING — INSTALLATION | main/observation: list o update_entryPurchase; extras: update_entryPurchase. Destino de extras renderiza archivos del mismo directorio no protegido en :8080 |
| FR-054 | Adjunto reporte Entrada | [http://127.0.0.1:8080/dashboard/entradas/compra/reporte](http://127.0.0.1:8080/dashboard/entradas/compra/reporte) | PENDING — INSTALLATION | entry.document.main; reader presente, static dispatch lo puede saltar |
| FR-083 | Adjunto facturas | [http://127.0.0.1:8080/dashboard/factura/compra](http://127.0.0.1:8080/dashboard/factura/compra) | PENDING — INSTALLATION | list_invoice/update_invoice según lector; ninguna URL pública recuperada |
| FR-088 | Adjunto reporte Finance | [http://127.0.0.1:8080/dashboard/reporte/faturas/finanzas](http://127.0.0.1:8080/dashboard/reporte/faturas/finanzas) | PENDING — INSTALLATION | mismo lector autorizado, sin fallback |
| FR-093 | Adjunto reporte sin orden | [http://127.0.0.1:8080/dashboard/reporte/faturas/finanzas/sin/orden](http://127.0.0.1:8080/dashboard/reporte/faturas/finanzas/sin/orden) | PENDING — INSTALLATION | mismo lector autorizado, sin fallback |
| FR-096 | Adjunto anuladas | [http://127.0.0.1:8080/dashboard/factura/compra/anuladas](http://127.0.0.1:8080/dashboard/factura/compra/anuladas) | PENDING — INSTALLATION | Registro30 T1/C1 finance1 state_annulled1 deleted_atNULL; no equivale a soft-deleted |
| FR-097 | Adjunto detalle Entrada | [http://127.0.0.1:8080/dashboard/entrada/compra/visualizar/53](http://127.0.0.1:8080/dashboard/entrada/compra/visualizar/53) | PENDING — INSTALLATION | Registro53 T1/C1 finance0 activo; main + observation |
| FR-098 | Adjunto detalle Invoice | [http://127.0.0.1:8080/dashboard/factura/compra/ver/29](http://127.0.0.1:8080/dashboard/factura/compra/ver/29) | PENDING — INSTALLATION | Registro29 T1/C1 finance1 activo; main |

## Recuperación implementada

- Nueva compra normal en toolbar existente. route order.purchase.normal.create, GET, permiso create_orderPurchaseNormal. No cambia form action, request, precio, stock ni Store.
- Editar orden en menú existente, Font Awesome fa-pen + next-row-action-item-icon; href GET editar/orden/compra/normal/{id}. Se preserva data-editar. Permiso update_orderPurchaseNormal y tipo n. El adapter compartido exige cuarto argumento true únicamente desde normalV2.js: no rehabilita General ni Express por accidente.
- Imprimir orden eliminada en menú de papelera, fa-print + patrón existente, target_blank/rel noopener/data-imprimir. Permiso list_orderPurchaseNormal; la pantalla conserva destroy_orderPurchaseNormal. Controller corregido usa NormalPurchaseReadAccess::query(true), sólo órdenes n soft-deleted T/C. Nunca se llamó el generador ni se descargó el PDF.
- Nueva entrada de compra en toolbar, route entry.purchase.create, create_entryPurchase. Triggers de recepción por orden ya recuperados en R4 quedan intactos: listOrdersV2 no modificado, can_receive sigue autoridad. No contar cada trigger como nueva capacidad.
- No editar Entrada ni anularla: ENTRY-R75-01/ENTRY-R4-01 conservados. No cambio de permisos ni bypass.
- Formularios destino createNormalV2/editNormalV2/create_entry_purchaseV2 conservados sin homologar, como pide R7.6; R7.7 pendiente.

## Revalidación funcional focalizada

Normal parent company-wide con ScopedPurchaseCompany T/C; create y edit autorizan capacidades explícitas. Supplier es tenant-scoped mediante BelongsToTenant; usuarios por NormalPurchaseGuard; Quote levantada y abierta con company; PaymentDeadline selectable, histórico seleccionado en edit. Create conserva Audit técnico y cálculo de código provisional, no reserva ni crea orden. Audit creating fue cancelado sólo en proceso QA y toda consulta bajo READ ONLY.

Precarga JS DEV usa consumer purchase_create / purchase_edit / entry_create. PurchaseFormReadAccess valida capacidad real y contexto; StockItems por tenant + asociación activa company, material tenant, variantes coherentes; levels por MaterialInventoryScope. Sin preload global restaurado, sin hardcodes de warehouse/location.

Entry create usa Supplier tenant y EntryWarehouseResolver para opciones actuales. EditEntry sigue exigiendo el consumer equivocado edit_entryPurchase (ruta update_entryPurchase): no tocado. No ejecutar Store/Update, upload ni operaciones de detalle.

## Documentos: por qué no se restauran ocho accesos

Contratos DEV presentes:

- entry.document.main → /images/entries/{filename}
- entry.document.observation → /images/entries/observations/{filename}
- entry.document.extra → /images/entries/extras/{filename}
- Middleware auth/check.user.enabled/password.changed y EntryDocumentAccess: actor activo noPlatform, tenant/company activos, membresía; referencias cross-context o ambiguas404; deleted_at restringido; extras sólo Entry nofinance + update_entryPurchase.
- EntryDocumentFile rechaza traversal/extensión/MIME incompatibles; PDF/JPG/JPEG/PNG/GIF/WebP; path bajo directorio real. Respuesta private/no-store/nosniff. Escritores extra conservan staging, transacción y cleanup. No se reescribieron.

**Evidencia del runtime solicitado:** procesos php.exe ejecutan artisan serve --host=127.0.0.1 --port=8080 y PHP -S con server.php. server.php devuelve false para cualquier archivo existente en public antes de require public/index.php. public/.htaccess sí contiene rewrite images/entries ANTES de !-f, pero PHP builtin no aplica .htaccess.

Dos HEAD anónimos, sin cuerpo ni documentos comerciales:

| Probe | Resultado |
|---|---|
| /images/entries/no_image.png (placeholder compartido existente) | 200 image/png, sin login |
| /images/entries/r76-missing-probe.png (nombre inexistente, no creado) | 302 /login |

El placeholder está permitido por el servicio únicamente después de autenticación/contexto; la divergencia demuestra que archivo existente salta Laravel. No descargamos ni probamos archivos del negocio. No se creó probe ni se tocó Apache/server.php. No hay fallback /index.php artificial ni detección frontend del servidor.

Clasificación: DOC-01 — INSTALLATION/RUNTIME LIMITATION CONFIRMED FOR :8080. No finding duplicado ni reapertura global de P/J. La condicional de la sección6 del encargo exige mantener la capacidad pendiente si la protección no está instalada. La candidatura R7.5 era condicional a esa instalación; no cuenta como frontend recuperado.

Siguiente paso DEV/entorno: usar servidor que aplique el rewrite entregado hacia Laravel aun para archivos físicos, o entregar router de desarrollo equivalente autorizado. No basta migración SQL. Comprobar con placeholder/probe no comercial que el anónimo pasa por auth y preservar guards del lector; después recuperar las ocho acciones con rutas nombradas. No cambiar storage/arquitectura ni recertificar todos los documentos ahora.

## Dataset y evidencia real

PDO dedicado, START TRANSACTION READ ONLY + ROLLBACK; session/cache array; Audit creating anulado sólo en proceso. No sesión persistente ni roles editados. Controllers/views reales, no evidencia HTTP de negocio completo.

| Contexto | Normal activos | Normal papelera elegible para print | Create Normal / Entry | Edit2/3/4 | Preload por cada consumer |
|---|---|---|---|---|---|
| Owner EDESCE T1/C1/B1 | 4,3,2 | 1 | Render200 /200 | 200/200/200 | 47 |
| Owner EDESCE T1/C2/B2 | vacío | vacío | Render200 /200 | 404/404/404 | 14 |
| Owner Gamarra T3/C3/B4 | vacío | vacío | Render200 /200 | 404/404/404 | 2 |

Edit1 (eliminada) e inexistente999999:404 en todos. Id1 onlyTrashed elegible sólo C1, validación de query sin ejecutar print. Entry IDs visibles primera página C1:53,54,44,43,42,41,40,39,37,35; C2:52,49; Gamarra45. Registros de revisión documental29/30/53 verificados T1/C1 activos sin abrir archivos.

Negativos de permisos **simulados**: Gate aislado deniega create_orderPurchaseNormal/update_orderPurchaseNormal/list_orderPurchaseNormal/create_entryPurchase; middleware real403 y @can oculta ambos triggers Create. No modificar asignaciones reales. La prueba de renderer usa arrays de permisos vacíos/restringidos y no concede Owner bypass.

## QA visual / navegación

Chrome headless aislado, perfil propio descartable. HTML capturado del render real + respuestas GET readonly capturadas; assets locales. Todo POST interceptado, destinos documentales interceptados antes de leer bytes. No navegador del usuario.

- 3 superficies ×8 anchos320/375/390/576/768/992/1200/1440 =24 comprobaciones PASS, sin overflow global ni pageerrors.
- Cuatro navegaciones interceptadas: crear Normal, editar4, imprimir eliminada1 y crear Entry. GET correctos. Print se valida en popup simulado; ninguna descarga real.
- Menús mantienen fa-icon + next-row-action-item-icon en cada acción. Edit neutral, anular destructive. Escape funciona. Modal Estado existente abre/cierra sin guardar.
- C2/Gamarra Normal vacío pero Create visible por permiso; sin edit inventado. Entry Create visible en ambos contextos.
- Screenshots desktop/mobile revisados. Los formularios destino sólo render PHP; su homologación/QA exhaustivo NO pertenece a esta fase.
- Node4 tests PASS: permisos/opt-in/tipo, papelera y ruta protegida, capacidades anteriores/iconos, escaping. Blade view:cache PASS; node --check de2 JS PASS; git diff --check PASS.

Artefactos fuera del repo: r76-read.php/results.json, r76-browser.cjs/results.json y r76-{normal,trash,entry}-{375,1440}.png bajo C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/. Sin credenciales. Pruebas simuladas no certifican mutaciones ni PDFs.

## Contabilidad / pendientes

107 IDs únicos =66 recuperados (62+4) +8 pendientes instalación documental +10 bloqueados backend +23 excluidos. Ninguna nueva pérdida ni exclusión contabilizada como éxito. FR restaurados017/018/036/046. Pendientes documentales049/054/083/088/093/096/097/098. Bloqueados047/048/059/063/064/102/103/105/106/107 sin cambios. 18 pérdidas activas pendientes en total.

NEW DEV FINDINGS:0; nueva evidencia de limitación local DOC01, no raíz nueva. Cuatro gates frontend obsoletos retirados. Backend0, CSS0, allowlist0. MUTATION QA PENDING; compras/entradas/stock/uploads/delete/pagos0. Ninguna modificación POS/StoreWeb/SupplierCredits/Owner/Platform. R7.7/R8 no iniciadas.

## R7.7 (sólo identificación)

Create Normal, Edit Normal y Create Entry están alcanzables y requieren revisión/homologación de formularios en R7.7. Edit Entry espera ENTRY-R75-01. Avatar, Material Edit y dashboards nuevos siguen en su fase propia; no reabiertos aquí. Resolver protección de runtime documental antes de completar el residual R7.6.

## Archivos modificados

- resources/views/orderPurchase/normal_v2.blade.php
- resources/views/entry/index_entry_purchasev2.blade.php
- public/js/orderPurchase/list-operations.js
- public/js/orderPurchase/normalV2.js
- tests/Frontend/post-dev-purchase-recovery.test.cjs
- docs/REAL-NEXT-POST-DEV-FUNCTIONAL-RECOVERY-R76.md
- docs/REAL-NEXT-POST-DEV-FUNCTIONAL-RECOVERY-PLAN.md
- docs/REAL-NEXT-FUNCTIONAL-PARITY-RECOVERY-BACKLOG.md
- docs/REAL-NEXT-DEV-TEAM-FINDINGS-BACKLOG.md (sólo evidencia DOC01/schema)
