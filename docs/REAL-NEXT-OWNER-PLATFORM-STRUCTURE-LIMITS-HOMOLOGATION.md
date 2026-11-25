# R7.7D — Owner, Platform & Structure Limits Homologation

Fecha: 2026-11-25. Rama feature/venti-next-frontend. PHP7.3.33 / http://127.0.0.1:8080. No commit, push ni merge.

## Superficies y URLs actuales

12 páginas revisadas; Planes contiene además Create/Edit en modales, no rutas independientes. Dos dashboards nuevos homologados; dos formularios de Planes reconciliados. Nueve páginas restantes ya homologadas y sin cambios. No se reabrió ningún módulo operativo ni CRUD legacy.

| Superficie | URL exacta | Clasificación |
|---|---|---|
| Dashboard Owner | [http://127.0.0.1:8080/dashboard/principal](http://127.0.0.1:8080/dashboard/principal) | NEW DEV SURFACE — HOMOLOGATION REQUIRED → HOMOLOGATED |
| Dashboard Platform | [http://127.0.0.1:8080/dashboard/plataforma](http://127.0.0.1:8080/dashboard/plataforma) | NEW DEV SURFACE — HOMOLOGATION REQUIRED → HOMOLOGATED |
| Empresas | [http://127.0.0.1:8080/dashboard/configuracion/empresas](http://127.0.0.1:8080/dashboard/configuracion/empresas) | ALREADY HOMOLOGATED — NO CHANGES |
| Nueva empresa | [http://127.0.0.1:8080/dashboard/configuracion/empresas/crear](http://127.0.0.1:8080/dashboard/configuracion/empresas/crear) | ALREADY HOMOLOGATED — NO CHANGES |
| Editar empresa1 | [http://127.0.0.1:8080/dashboard/configuracion/empresas/1/editar](http://127.0.0.1:8080/dashboard/configuracion/empresas/1/editar) | ALREADY HOMOLOGATED — NO CHANGES |
| Locales empresa1 | [http://127.0.0.1:8080/dashboard/configuracion/empresas/1/sucursales](http://127.0.0.1:8080/dashboard/configuracion/empresas/1/sucursales) | ALREADY HOMOLOGATED — NO CHANGES |
| Nuevo local empresa1 | [http://127.0.0.1:8080/dashboard/configuracion/empresas/1/sucursales/crear](http://127.0.0.1:8080/dashboard/configuracion/empresas/1/sucursales/crear) | ALREADY HOMOLOGATED — NO CHANGES |
| Editar local1 | [http://127.0.0.1:8080/dashboard/configuracion/sucursales/1/editar](http://127.0.0.1:8080/dashboard/configuracion/sucursales/1/editar) | ALREADY HOMOLOGATED — NO CHANGES |
| Planes (Create/Edit en modales) | [http://127.0.0.1:8080/dashboard/plataforma/plans](http://127.0.0.1:8080/dashboard/plataforma/plans) | VISUAL RECONCILIATION REQUIRED → RECONCILED |
| Usuarios Web | [http://127.0.0.1:8080/dashboard/configuracion/usuarios/web](http://127.0.0.1:8080/dashboard/configuracion/usuarios/web) | ALREADY HOMOLOGATED — NO CHANGES |
| Nuevo usuario Web | [http://127.0.0.1:8080/dashboard/configuracion/usuarios/web/nuevo](http://127.0.0.1:8080/dashboard/configuracion/usuarios/web/nuevo) | ALREADY HOMOLOGATED — NO CHANGES |
| Editar usuario4 | [http://127.0.0.1:8080/dashboard/configuracion/usuarios/web/4/editar](http://127.0.0.1:8080/dashboard/configuracion/usuarios/web/4/editar) | ALREADY HOMOLOGATED — NO CHANGES |

**Corrección de referencia:** /dashboard/empresas no es la ruta registrada del mantenedor Owner en HEAD; tenantCompany.index usa /dashboard/configuracion/empresas. No se agregó alias ni se modificó routing. Edit del usuario4 se abrió únicamente para revisar render, sin cambios al Owner.

## Fuentes y alcance

AGENTS.md; informes DEV integration/reconciliation2026-11-25; plan post-DEV; backlogs funcional/DEV; informe R7.7C; patrones canónicos y dirección visual. Docs DEV: DASHBOARD-OWNER, DASHBOARD-PLATFORM-ADMIN, DASHBOARDS-OWNER-PLATFORM-ALCANCE, DASHBOARD-OWNER-USUARIOS-MENU, DASHBOARD-OWNER-AJUSTE-VISUAL y planes-cupos-tenant.

Se conserva evidencia histórica: la instalación pendiente descrita en R7.5 ya fue resuelta por el propietario. Lectura actual de migrations confirma 2026_11_21_000001_add_structure_limits_to_plans, batch180 (id576). No ejecutamos migraciones ni configuramos máximos.

## Contratos / permisos

| Familia | Controller / autoridad | Permisos y contexto |
|---|---|---|
| Owner | HomeController::dashboard → OwnerWorkspace::data | auth/check.user.enabled/password.changed/tenant.context; Owner activo del tenant activo, no Platform. Conserva dashboard.owner aun con empresa/local |
| Platform | Platform/PlatformDashboardController → PlatformDashboardSummary | auth + platform.admin; servicio revalida Platform activo; limpia contexto residual, sin company/branch requerido |
| Empresas | TenantCompanyController | tenant.owner + list_company/create_company/edit_company/enable_company; tenant, no obligación de contexto operativo |
| Locales | TenantBranchController | tenant.owner + list_branch/create_branch/edit_branch/enable_branch; empresa del tenant y branch de esa empresa; create sólo empresa activa |
| Planes | PlanController | auth + platform.admin; global; listado/GET data y POST store/update/toggle sin cambio |
| Usuarios Web | ConfigUserWebController | Owner tenant + listUser/createUser/editUser/changeStatusUser/resetPasswordUser_configUserWeb según acción; guard propietario servidor intacto |

Todos utilizan layouts.appAdmin2. Owner/Platform no tienen un JS nuevo de página: reutilizan layout/tenant-context.js, y Owner añade dashboard/entry-export.js sólo bajo las condiciones originales. Planes conserva public/js/plan/index.js. Empresas/locales/configUserWeb conservan sus scripts y contratos AJAX.

Allowlist: las12 rutas de vista ya estaban incluidas. **MATCH YES; 0 adiciones necesarias.** No se agregan endpoints, comodines ni modales como rutas. Cargan venti.min.css y venti-next.css.

## Estados Owner / contexto / shell

- Sin contexto: tenant permanece, selector y administración disponibles según permisos; ningún autoselect de empresa/local. No se fuerza dashboard operativo.
- Con empresa+local: mismo dashboard Owner y acceso operativo por permisos; export de entradas disponible sólo con contexto y sus capacidades originales.
- Selección parcial: elegir empresa carga opciones y mantiene Local vacío. Es estado transitorio del selector, no un nuevo contexto operativo persistido. La UI DEV solicita empresa+local para enviar.
- Cambiar contexto y volver a administración: handlers y CSRF intactos, POST interceptados; no cambiaron preferencias ni pivots reales.
- Elegir local conserva event.stopPropagation de R7.5, dropdown queda abierto y foco en selector.
- Platform no recibe selector ni menú operativo. Sus nueve accesos administrativos permanecen. Operador conserva su rama de HomeController/sidebar; no se ejecutó dashboard operativo ni sus consultas fuera de alcance.
- Estado sin empresas y cupos alcanzados/excedidos representado mediante fixture **SIMULATED** de datos del render, nunca en DB.

No cambios a sidebar/navbar ni tenant-context.js. La separación de menús se comprobó en HTML real Owner sin/con contexto y Platform, además del contrato vigente; no se afirma nueva certificación de login/cookies ni operador/permisos restringidos.

## Semántica de cupos — autoridad TenantPlanService

Empresas: activas del tenant. Locales: todos los activos de empresas del tenant, incluso empresa inactiva; principal cuenta. Nueva empresa exige1 empresa +1 local. Reactivaciones consumen capacidad según servicio; editar descripción y desactivar conservan reglas existentes. Reducir/reasignar plan no borra ni desactiva registros automáticamente; bloquea nuevas altas/reactivaciones sin disponibilidad.

Estructura NULL = pendiente de configurar, **no ilimitado**; available0 y configuredfalse. Cero (si existe histórico) es configurado pero sin capacidad de alta; nunca ilimitado. Positivos son máximos. Formulario/backend de Planes exige enteros1..10000; no modificamos validación. Usuarios usa contrato independiente maxActiveUsers y activeUsersCount, incluye Owner/excluye Platform; el servicio hace cast entero del máximo existente (NULL de un plan existente se convierte en0); no se equipara automáticamente a la semántica nullable de estructura. Sin plan Owner muestra Sin configurar. No se introdujeron cálculos de disponibilidad en JS.

Mensajes DEV pendientes/alcanzado/excedido y controles de alta/edición/reactivación conservados, sin usar el badge como guard. Bloqueos/transacciones/concurrencia son evidencia DEV, no pruebas repetidas de esta fase.

## Dataset real leído

Owner EDESCE usuario4, tenant1: **3 empresas activas,6 locales activos,4/6 usuarios**; empresas autorizadas1,2,5 y locales asignados1,8 /2 /6,7. El total de locales del tenant no se reemplaza por el conteo de locales autorizados al usuario. Límites empresas/locales NULL, sin configurar.

Platform: **4 tenants (4 activos),5 empresas activas,8 locales activos,7 usuarios activos**; cuatro avisos de planes con máximos estructurales pendientes. No son métricas comerciales. Consultas reales del servicio, sin datos estáticos de reemplazo.

## Homologación visual

Owner/Platform reutilizan next-dashboard-shell, next-dashboard, next-executive-hero/content/kicker y next-form-section sobre cards existentes. Se retiraron estilos inline con colores hardcodeados y !important; bienvenida conserva lenguaje navy mediante componente aprobado. Cards compactas de cupos/indicadores, ayudas y enlaces con gutters coherentes. Tabla Platform usa next-data-table y cantidades centradas, scroll local. Nueve accesos de Platform conservados con Font Awesome; no nuevas métricas.

Plan Create/Edit: modal-lg aprobado, código/nombre en fila proporcional4/8; tres máximos agrupados en fila alineada por abajo (tablet/móvil redistribuye); descripción y explicación comunes preservadas. Todos los campos, IDs, min/max/step/required, valores iniciales, CSRF, rutas y handlers conservados.

Empresas/locales ya incluyen tenantCompany._plan_capacity con next-form-section y mensajes DEV; Usuarios Web ya presenta plan/consumo/disponibilidad y rechazo. Sin cambios innecesarios a esas vistas ni a la ficha Platform Tenant, que reutiliza el mismo partial de capacidad.

## Paridad funcional / export

Inventario de atributos funcionales estáticos antes/después idéntico: Owner5/5, Platform4/4, Plan index23/23, partial20/20. Es conteo de atributos estáticos verificables, **no** de capacidades FR. Revisión del diff mantiene directivas de permisos/contexto, loops y valores backend. Formularios sólo cambian estructura/clases. Sin JS nuevo/modificado.

FR-104 Exportar entradas preservado: contexto empresa+local, showBoxAlmacen_dashboard y list_entryPurchase/list_entryInventory; tipo1/2/3 mantiene condiciones. Modal y script originales. Solicitud interceptada:
`/dashboard/exportar/entradas/almacen/v2?typeEntry=1&start=01%2F01%2F2026&end=25%2F11%2F2026`.
Sin ejecución ni descarga. INV-01/METRIC-01/DASH-R7-01 permanecen abiertos; no recuperamos sus gráficos/reportes bloqueados.

## QA propio — navegador aislado

Chrome headless propio, no escritorio ni navegador del propietario. Laravel renderizado con usuario/contexto reales y PDO MySQL READ ONLY + ROLLBACK; sesión/cache de proceso en memoria. DTO reales de servicios/GET para planes, usuarios, almacenes y cupos. HTML y DTO servidos por interceptación en navegador aislado; no se presenta como QA HTTP end-to-end autenticada de despliegue.

61 comprobaciones:40 layouts (Owner sin/con contexto, Platform, Planes ×320/375/390/576/768/992/1200/1440/1920/2560),10 comprobaciones relacionadas/estado simulado a1440,8 modales Create/Edit de Planes a320/375/768/1440 y3 aserciones de interacción/frontera. Sin overflow global ni pageerrors en la corrida final. Primera corrida tenía errores de fixtures por endpoints no provistos; se corrigió el harness para servir respuestas reales de usuarios/almacenes, sin cambios en la aplicación.

Selector: dropdown abierto, Local vacío después de elegir empresa, POST company_id/branch_id/_token interceptado422; clear POST sólo token interceptado422. Rechazos no persisten preferencias. Export interceptado422 con mensaje. Modales Planes abren y precargan máximos actuales; no submit de planes. Responsive histórico de las nueve páginas no modificadas se conserva; revisión focalizada desktop, no nueva matriz exhaustiva por CRUD.

Se revisaron capturas visualmente (no sólo métricas): Owner desktop/móvil, Platform desktop/móvil, densidad1920/2560, modales Planes y estados de cupos. Se esperó la animación de los modales. La captura fullPage de Chromium mostró un desplazamiento espurio de Platform pese a margin-left computado de250px; se sustituyó la evidencia final de dashboards por capturas del viewport, comprobando además la geometría real del shell. No se cambió CSS para compensar el artefacto de captura.

Recursos fuera del foco interceptados sin proveedor: fuentes externas/Ionicons, tipo de cambio y notificaciones operativas. Avatar histórico admin1.png (y2.webp) ausente en assets locales; se conserva, sin usar imágenes personales ni ampliar fase Perfil. No afirmar carga perfecta de esos recursos o certificación de proveedores.

Artefactos en `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/`: r77d-data.json, r77d-results.json, r77d-parity.json, scripts read/browser, capturas r77d-owner-none/context-375/1440/1920/2560.png, r77d-platform-375/1440/1920/2560.png, r77d-plan-create/edit-375/1440.png y r77d-owner-simulated-1440.png. Datos reales y fixture explícitamente diferenciados. Capturas no reemplazan aprobación del propietario.

## Pendientes / R8

Persistencia de altas/edición/reactivación/planes/cupos/contexto y export real: **QA PENDING, no ejecutada**. Reglas backend y pruebas aisladas/concurrencia DEV preservadas, no recertificadas. No nuevos defectos backend comprobados. Configurar límites comerciales pendientes corresponde al propietario/Platform, no a fixtures ni migración frontend.

Candidato explícito R8: `/dashboard/editar/cotizacion/venta/79` (familia /{id}), botón Guardar datos generales comentado. Investigar origen Git, intención, alcance histórico y contrato actual. No presumir retiro, no intervenir durante R7.7D ni asignar FR duplicado.

## Archivos / estado final

Runtime: resources/views/dashboard/owner.blade.php; resources/views/platform/dashboard/index.blade.php; resources/views/plan/index.blade.php; resources/views/plan/partials/form.blade.php. Documentación: este informe, plan post-DEV y anotación R8 del backlog funcional. Allowlist verificada, sin diff necesario. No rutas/controllers/services/modelos ni valores de planes modificados.

OWNER / PLATFORM: FRONTEND INTEGRATED. Plan forms: VISUAL RECONCILIATION PASS. Company/Branch/Users: ALREADY HOMOLOGATED. Structure limits visual parity PASS; contexto/export preservados. Backend0 / JS0 / CSS nuevo0 / infraestructura0 / mutaciones comerciales0. Blade compile PASS; git diff --check PASS. Capacidades perdidas0; no nuevos FR ni DEV findings duplicados. **74 recuperadas +10 bloqueadas +23 excluidas =107**. No R7.7E/R8. Esperar revisión del propietario.
