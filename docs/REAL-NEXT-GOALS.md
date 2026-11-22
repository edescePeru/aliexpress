# REAL NEXT — Metas: DEV delta / readiness

2026-11-13. **FRONTEND INTEGRATED — READ PATH / MUTATION QA PENDING**. Reanudación post-migración: SCHEMA PASS; READY FOR FRONTEND confirmado e integración completada. Backend intacto; no migraciones ni escrituras MySQL ejecutadas por esta fase. No commit. El bloqueo1054 de la revisión anterior era exclusivamente esquema local desactualizado.

## DEV Delta

Commit `4567897e7ad9d1d73e56b92b394aa50520c1beae` (MEtas y KArdex), comparado con su padre `62ba1a3`. HEAD `28c413a` es merge posterior; no añade diferencias en MetaController/Meta/MetaProgress. El paquete incluye otros módulos, excluidos de esta auditoría. DEV no agregó tests de Metas en ese commit; esta fase incorpora GoalContextContractTest.

| File | Change | Why it matters |
|---|---|---|
| app/Http/Controllers/MetaController.php | context guard; forCurrentCompany en list/edit/update/delete; eligibleUsers; locks y solapamientos; ranking delegado | Elimina queries Meta/Worker globales del flujo operativo |
| app/Meta.php | tenant/company fillable, scope fail-closed sin contexto, users via meta_users | Contrato company explícito; NO global scope automático |
| app/Services/Sales/MetaProgress.php | Nuevo agregado por created_by, tenant/company, PEN | Meta y ventas comparten contexto; sin fallback Worker |
| app/Helpers/MetaCalendarHelper.php | Semana filtrada también por month | Igual recorte mensual que store |
| app/Http/Controllers/HomeController.php | Widget usa MetaProgress; gate showBoxMetas_dashboard | Comparte cálculo, top10 por porcentaje; no certifica otros indicadores |
| database/migrations/2026_11_13_000001_add_company_context_and_users_to_metas.php | Columnas nullable tenant/company; meta_users con PK compuesta y FK | Requisito ahora aplicado; no backfill de legacy |
| routes/web.php | Gates list/create/edit/destroy/progress; weeks create o progress; retiro configTipo | Permisos también sobre AJAX |
| config/permissions_catalog.php | edit_metas y destroy_metas | Nuevas capacidades declaradas; asignación efectiva pendiente de QA |
| resources/views/metas/{index,create,edit,ranking}.blade.php | Usuarios en lugar de trabajadores, controles/permisos y columnas ajustados | Cambios DEV funcionales, distinguidos de la integración Venti Next posterior de esta fase |
| resources/views/metas/rankingPending.blade.php | Vista pendiente añadida | No es retorno actual de MetaController |
| resources/views/dashboard/dashboard.blade.php; layouts/appAdmin2.blade.php | Widget usuario y navegación por permisos | No atribuir al trabajo frontend previo |
| docs/flujos/metas-contexto.md; ranking-metas-usuario.md | Contrato company/usuario y PEN | Primer documento conserva texto obsoleto «progreso pendiente»; segundo y código implementan progreso |

Sin requests dedicados nuevos: validación inline en controller. JS de estas superficies sigue embebido en Blade, jQuery/Select2 y AJAX; no archivo JS propio nuevo.

## Previous Blocker

PRIORITY-REAUDIT filas39/W y sección Dashboard/reportes: Meta sin tenant/company, Worker como actor, ventas globales y rutas sin permiso granular. REAUDIT-2 mantiene Meta/Ranking junto a Dashboard/Financial Summary bloqueados. Finding canónico **METRIC-01**. El delta corrige esas causas en código de Metas; no cierra automáticamente todo METRIC-01 ni USER-01.

## Current Contract

Meta comercial de ventas por **empresa**, objetivo monetario individual para cada User asignado, todas las sucursales de esa empresa. No meta por Worker ni por branch. Campos nombre, monto, tipo, fecha_inicio/fin; pertenencia asignada por servidor. No estado activo/cumplida/vencida persistido. Sin show, toggle, cierre, deleted/restore.

Create: POST metas/store, CSRF, nombre, monto (>0, máximo99999999.99), year1900–9998, month1–12, week o quincena1/2 según frecuencia, users[] requerido/distinct. Edit: POST con _method PUT; nombre/monto/users[]; conserva fechas/tipo. IDs form-meta, worker-select, btn-add-worker, btn-add-all-workers, workers-list/hidden-inputs siguen llamándose worker por compatibilidad, pero transportan User IDs. Nombre max255 frente a VARCHAR191 local: coherencia de límite pendiente, no probado con escritura.

Frecuencia sales.goals.frequency contextual vía SettingService: semanal/quincenal/mensual. Sin frecuencia válida create redirige con error y ranking data400. Semanal usa DateDimension year/month/week_of_year y recorta al mes. Quincena1 días1–15; quincena2 día16–fin; mensual mes completo. Ranking exige coincidencia exacta tipo/inicio/fin. Timezone aplicación America/Lima; fechas de venta desde inicio inclusivo hasta día posterior al fin exclusivo. No alteración de rangos.

GET index/create/edit/ranking/weeks/ranking-data no guardan Meta ni estado; helpers leen calendario. Store/update serializan por company y validan solapamiento por usuario. Destroy detach legacy workers y delete; meta_users tiene cascade previsto en migración. Mutaciones no ejecutadas/certificadas.

## Routes / Permissions

Prefijo /dashboard/metas; middleware común web/auth/check.user.enabled/password.changed/tenant.context, más guard MaterialParameters::context: usuario habilitado no Platform, tenant coincidente, company activa del tenant, membresía company_user activa. No Owner bypass asumido.

| Surface | Route | Action | Permission | Scope | Read/Mutation | Status |
|---|---|---|---|---|---|---|
| List | GET / (metas.index) | index | list_metas | T/C | Read | PASS aislado / lectura real vacía |
| Create | GET /create | create | create_metas | T/C, candidatos | Read | Integrado |
| Store | POST /store | store | create_metas | T/C | Mutation | QA PENDING |
| Edit | GET /{meta}/edit | edit | edit_metas | T/C findOrFail | Read | PASS aislado / lectura real vacía |
| Update | PUT /{meta}/update | update | edit_metas | T/C | Mutation | QA PENDING |
| Delete | DELETE /delete/{meta} | destroy | destroy_metas | T/C | Mutation | QA PENDING |
| Weeks | GET /weeks | getWeeksByYearMonth | create_metas OR progress_metas | contexto + calendario común | Read | GET real PASS |
| Ranking | GET /ranking | ranking | progress_metas | T/C | Read | Integrado |
| Ranking JSON | GET /ranking/data | getRankingData | progress_metas | T/C | Read | PASS aislado / lectura real vacía |
| Widget dashboard | GET dashboard/principal (parte del dashboard) | HomeController | showBoxMetas_dashboard para cálculo/widget | T/C, todas branches | Read | Servicio compartido PASS; otros indicadores fuera de alcance |

Nombres rutas: metas.create/store/edit/update/destroy/weeks/ranking/ranking.data. Parámetros inválidos ranking422; weeks sin año/mes400. No convertir errores en empty state.

## Scope

forCurrentCompany añade ambos predicados, o 1=0 sin contexto. Edit propio retorna la vista; otra company, otro tenant e inexistente lanzan ModelNotFoundException (404 contractual), comprobados con fixture aislado. Operación company-wide deliberada: C1/B1 y C1/B8 deben compartir metas y ventas. C2/C5 quedan excluidas desde C1; T3/C3 excluye EDESCE. Comprobado con IDs positivos aislados y consultas reales vacías; no se crearon metas comerciales.

## Aggregates

| Metric | Source | Scope | Result |
|---|---|---|---|
| Objetivo | metas.monto | T/C + período exacto, por usuario asignado | PASS fixture aislado |
| Vendido | SUM sales.importe_total grouped created_by | mismo T/C, PEN, rango, no anulada/no reversed | PASS fixture aislado |
| Cumplimiento | total/objetivo*100, round2 | mismo row usuario/meta | Backend, sin cálculo UI nuevo |
| Puesto ranking | ventas_total DESC, desempate user_id | filas contextuales | Código auditado |
| Dashboard top10 | mismas filas ordenadas porcentaje DESC | mismo contexto | No certifica dashboard completo |

Ventas sin created_by no atribuidas a Worker. USD excluido; no suma global ni filtro branch porque contrato incluye todas las sucursales.

## Dataset

SCHEMA — PASS. migrate:status PHP7.3: Yes, batch167. SHOW CREATE TABLE confirma tenant_id/company_id nullable y metas_company_period_idx(T,C,fecha_inicio,fecha_fin); meta_users PK(meta_id,user_id), FK meta→metas CASCADE y user→users RESTRICT. La migración no declara FKs tenant/company adicionales: no se inventan.

REAL DATASET — EMPTY: metas=0, meta_users=0, meta_workers=0. PDO START TRANSACTION READ ONLY/ROLLBACK; mismo query real forCurrentCompany devuelve [] sin1054 en T1/C1/B1, T1/C1/B8, T1/C2/B2, T1/C5/B6 y T3/C3/B4. Company-wide: B1 y B8 deben compartir conjunto, no es fuga.

Test DB SQLite :memory: conexión goals_qa explícita y assert antes de fixtures; migraciones reales de Meta/meta_users, tablas mínimas sintéticas para requisitos. No RefreshDatabase contra MySQL. 6 tests /62 assertions: propio permitido; IDs2/3/999999 rechazados desde C1; ausencia de contexto devuelve vacío; company2 y tenant3 reciben sólo sus IDs; C1/B1 y B8 mismoID. Usuarios sin membresía/activa o ajenos rechazados por validateAssignments real; pivot propio conserva user1.

Progreso exacto: 50PEN en B1 al inicio +100PEN en B8 al último segundo =150PEN, objetivo200,75%, puesto1. Se excluyen ocho distractores de900: otra company, otro tenant, otro actor, USD, anulada, reversed, antes del inicio, día posterior al fin. Ranking JSON real coincide con filas MetaProgress. Dashboard comparte llamada (comprobación estática del consumidor, no QA global).

Periodos: mensual febrero2026=1–28; quincenas noviembre1–15/16–30; semana44 con día octubre y día noviembre se recorta al mes. No probar escritura store para determinar fechas. Permisos: rutas registradas + PermissionMiddleware real rechaza403 sin capacidad y permite con capacidad (Gate controlado en test; no asignación de roles compartidos). No equivale a QA HTTP completo de todos los roles.

## Currency

Contrato actual explícito PEN para objetivo/progreso; sales.currency=PEN. No campo currency en Meta ni conversión USD. Extensión USD/FX pendiente de Producto, no impide contrato PEN por sí sola. No se reinterpretó fiscalidad ni redondeo.

## Lookups

eligibleUsers limita tenant, enable=true, no Platform y company_user activa de company actual. No User::all. Edit suma asignados existentes para conservar usuarios posteriormente deshabilitados, conforme documento DEV; asignación nueva validada en test; datos históricos no existen en dataset local. DateDimension es calendario técnico común, no fuga de usuarios. No se reemplazó User por Worker. La migración no asigna contexto a metas históricas ni transforma meta_workers; histórico no contextual quedaría excluido (dataset actual vacío).

## Readiness Decision

| Gate | Result |
|---|---|
| Schema | PASS — MySQL real |
| Tenant | PASS — fixture aislado positivo/negativo |
| Company | PASS — fixture aislado positivo/negativo |
| Branch semantics | PASS — company-wide B1/B8 |
| Eligible users | PASS — query/validator reales, fixture |
| Progress | PASS —150PEN/75%, ocho exclusiones |
| Periods | PASS — semanal/quincenal/mensual |
| Ranking | PASS — JSON usa servicio; widget mismo servicio |
| Permissions | PASS — rutas + middleware con Gate controlado |
| Dataset real | EMPTY — no bloquea frontend |

**READY FOR FRONTEND → FRONTEND INTEGRATED — READ PATH.** METRIC-01 / GOALS — FIXED IN BACKEND + QA PASSED para alcance comprobado; no cerrar METRIC-01 completo. Nombre max255/schema191 y decisión USD continúan documentados, sin mutaciones certificadas.

## Visual Integration

Cuatro Blades reales metas.index/create/edit/ranking. Reutilización Configuration CRUD/Operational List: next-page-heading, toolbar/context, next-form-section, Bootstrap form-row, tablas con alineación semántica, acciones flex-wrap. Sin CSS nuevo; CSS preexistente de progreso conservado. No nuevo dashboard, cálculo ni endpoint. Empty state normal con Crear Meta condicionado a permiso/configuración; no warning por vacío. Ranking conserva valor backend/representación preexistente.

Allowlist exclusivamente metas.index/metas.create/metas.edit/metas.ranking; sin wildcard/JSON. venti.min.css + venti-next.css observados. CSRF/actions/method/names/hidden/hook worker-* (IDs de User)/fechas/importe/validaciones/permisos preservados. Labels asociados y Select2 width100% como adaptación visual; acciones financieras ninguna.

QA navegador: Owner Gamarra T3/C3/B4 listado vacío, frecuencia no configurada correctamente presentada. Owner EDESCE T1/C1/B1 listado vacío, alta semanal; year2026/month11 carga semanas44–49; asignar todos/quitar usuario sólo DOM; ranking semana46 devuelve vacío válido para9–15nov. No POST ni DELETE de negocio. Edit real sin dataset se valida renderizando Blade con Meta1/User1 de SQLite y shell de prueba con assets permanentes; periodo01–30nov y objetivo200 preservados. Fixture temporal fuera del repo, banner QA, submit bloqueado; no ruta productiva añadida. No se afirma edición HTTP de un registro compartido inexistente.

## Responsive

| Vista | 320 | 375 | 390 | 576 | 768 | 992 | 1200 | 1440 |
|---|---|---|---|---|---|---|---|---|
| List real | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| Create real | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| Ranking real | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| Edit fixture | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |

Sin overflow global; tabla puede desplazarse localmente. Capturas goals-list-desktop.png, goals-create-desktop.png, goals-edit-fixture-mobile.png en evidencias de sesión. Fixture inicialmente omitía dependencias: se corrigió shell de prueba con AdminLTE/Select2 y se repitió matriz; consola final sin errores. Consola real sin errores observados.

## Remaining Mutation QA

Crear/actualizar/eliminar/cerrar/recalcular: no ejecutados en MySQL. MUTATION QA PENDING. No cambio de roles/settings comerciales. Positivos comerciales/paginación multipágina pendientes de dataset real; cubiertos contratos principales con fixtures aislados, no certificación de escrituras ni concurrencia. No tests gigantescos ni cambios backend.

Validación: GoalContextContractTest 6/62 PASS; render cuatro vistas; node --check de scripts inline renderizados PASS; PHP7.3 artisan view:cache PASS; git diff --check PASS. Warning PHPUnit existente: esquema XML de configuración deprecated, no impide tests.

Archivos de fase: cuatro Blades, config/venti-next.php, test focalizado, documento y addendum backlog. Cambios Warehouses anteriores preservados, no atribuidos a Metas. Sin staging ni commit.
