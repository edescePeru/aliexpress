# REAL NEXT — Post-DEV Functional Recovery Plan (R7.6)

## R7.7B — reconciliación Perfil / Material Edit — 2026-11-25

Ver [informe focalizado](REAL-NEXT-PROFILE-MATERIAL-POST-DEV-RECONCILIATION.md). Avatar y Guardar material ya disponibles: FRONTEND PARITY PASS, sin gates obsoletos encontrados ni cambios runtime. Chrome aislado, payloads/errores/éxitos interceptados; 24 checks responsive sin overflow y cero pageerrors. Persistencia real no ejecutada, QA mutadora pendiente. MAT-01 permite historia/stock/shared-master; MAT-03 preserva Items y ownership sin prohibición general shared-master. IDs canónicos: MAT-04 multinivel, MAT-05 flags (la tabla R7.5 invirtió los títulos). PROFILE-01 fix DEV preservado, no nuevo cierre USER-01. No se repite certificación backend. Sin nuevos findings ni FR: **74 recuperadas +10 bloqueadas +23 excluidas =107**. R7.7C/R8 no iniciadas. Historia anterior conservada.


## Seguimiento R7.7A — 2026-11-25

Normal Create/Edit y Entry Purchase Create homologados3/3. Ver REAL-NEXT-NORMAL-PURCHASE-ENTRY-FORMS-HOMOLOGATION.md. Campos, modales, acciones y payloads preservados;24 checks responsive PASS; mutaciones interceptadas, ninguna compra/entrada real. Backend/JS/CSS0. Allowlist limitada a3 vistas. FR017/018/046 ya recuperadas en R7.6: **no sumar otra vez**. Contabilidad74 recuperadas+10 bloqueadas+23 excluidas=107. ENTRY-R75-01/ENTRY-R4-01 intactos. R7.7B/R8 no iniciadas; sin commit/push/merge. Evidencia anterior conservada.


## Estado vigente R7.6.1 — Document Functional Recovery — 2026-11-25

Decisión explícita del propietario: virtual host descartado; mantener http://127.0.0.1:8080 y PHP7.3.33. La limitación de artisan serve no bloquea por sí sola los controles que usan el contrato autorizado DEV. Esta sección sustituye la clasificación de instalación pendiente de R7.6; la evidencia anterior se conserva como historia, no como estado vigente.

**DOCUMENT CAPABILITIES RESTORED: 8/8.** FR-049/054/083/088/093/096/097/098 recuperadas mediante entry.document.main / observation / extra y navegación contextual existente. No asset() para adjuntos ni fallback público. Los paths de los lectores coinciden intencionalmente con /images/entries/... por contrato DEV; usar route() no convierte artisan serve en un servidor que aplique .htaccess.

**107 FR = 74 recuperados + 10 bloqueados backend + 23 excluidos.** R7.6 acumula 12/12 recuperadas (4 anteriores +8 documentales). Permanecen FR047/048/059/063/064/102/103/105/106/107; no nuevos blockers del lector demostrados. DOC-01 conserva observación de entorno y verificación HTTP directa pendiente en despliegue, fuera del conteo de pérdidas frontend. No equivale a certificación integral de seguridad.

Validación propia: 8 tests Node PASS (4 documentales +4 regresión R7.6), Blade compile y sintaxis JS PASS; 16 comprobaciones Chrome headless aislado (8 superficies ×375/1440), 16 navegaciones interceptadas; cero pageerrors y overflow global. Metadatos documentales SIMULADOS sobre render/DTO real leído sin escrituras; ninguna descarga comercial. Permisos negativos simulados en renderer. No se repite certificación backend DEV ni J/P/K. Backend/CSS/infraestructura/mutaciones comerciales:0. Sin commit/push/merge. R7.7/R8 no iniciadas.

Propuesta Apache descartada: retirado docs/environment/aliexpress-apache-local.conf.example, generado exclusivamente para esa propuesta y nunca instalado. URL vigente: http://127.0.0.1:8080. Ver informe R76 para matriz y evidencia.

## Evidencia anterior preservada

## Historia R7.6 — 2026-11-25

Ver REAL-NEXT-POST-DEV-FUNCTIONAL-RECOVERY-R76.md. **4/12 restauradas**: FR017/018/036/046 (navegación crear/editar Normal, print eliminadas y Create Entry). Formularios DEV intactos, sin homologación nueva. **8/12 pendientes de instalación documental**, no de mera QA: FR049/054/083/088/093/096/097/098. PHP builtin :8080 entrega archivos existentes antes de Laravel; .htaccess DEV no aplica. HEAD anónimo placeholder200 frente a inexistente302/login demuestra dispatch; no documentos comerciales descargados. Cierre J/P por negocio preservado; DOC01 limitación local documentada, no nuevo finding.

Migración cupos Yes batch180 y ambas columnas existentes; no planes ni migraciones ejecutados aquí. Platform prerrequisito schema resuelto; UI/configuración fuera de alcance.

Contabilidad107 IDs: **66 recuperados +8 instalación pendiente +10 bloqueados backend +23 excluidos**. Pendientes activos18. No contar backend como UI. R7.7/R8 no iniciadas; no commit/push/merge. Cuatro tests renderer PASS,24 checks responsive PASS, Blade/JS/diffcheck PASS; mutaciones reales0.

## Historia anterior preservada

Fecha2026-11-25. Plan, no implementación masiva. No R8. Leer reconciliación R7.5 primero. SourceDEV41ed99f/0129133; frontend se adapta a permisos actuales, no copia baseline antiguo indiscriminadamente.

| Pantalla | URL local | Capacidad | Blocker anterior | Evidencia DEV | Restricción frontend actual | Trabajo requerido | Estado |
|---|---|---|---|---|---|---|---|
| Compras normales create | http://127.0.0.1:8080/dashboard/crear/orden/compra/normal | FR017 crear | Preload global | PurchaseFormReadAccess consumer, scope stock | Link omitido R3 | Recuperar navegación y adaptar formulario al consumer actual; mutation aparte | READY FOR FRONTEND RECOVERY |
| Compras normales edit | Desde listado http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2 (ID real requerido) | FR018 editar | Preload/binding | NormalPurchaseReadAccess + update_orderPurchaseNormal | Acción omitida | Restaurar acción sin mutar; mantener permiso/scope | READY FOR FRONTEND RECOVERY |
| Compras eliminadas | http://127.0.0.1:8080/dashboard/ordenes/compra/eliminadas | FR036 imprimir | onlyTrashed faltaba | query(true) + list permiso | Print omitido | Enlace autorizado, documento QA separado sin export real aquí | READY FOR FRONTEND RECOVERY |
| Crear Entrada compra | http://127.0.0.1:8080/dashboard/crear/entrada/compra | FR046 crear | Preload global | consumer entry_create contextual | Link/form recovery pendiente | Modernizar sin Store; preservar consumer/JS DEV | READY FOR FRONTEND RECOVERY |
| Editar Entrada compra | Desde http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2 (ID real requerido) | FR047 editar | Preload | Permiso nuevo equivocado ENTRY-R75-01 | Edit sigue retirado | DEV alinear update_entryPurchase antes de recuperar | BACKEND STILL BLOCKED |
| Entradas / facturas / reportes | http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2 ; http://127.0.0.1:8080/dashboard/entradas/compra/ordenes/v2 ; http://127.0.0.1:8080/dashboard/factura/compra ; http://127.0.0.1:8080/dashboard/factura/compra/anuladas ; http://127.0.0.1:8080/dashboard/reporte/faturas/finanzas ; http://127.0.0.1:8080/dashboard/reporte/faturas/finanzas/sin/orden | FR049/054/083/088/093/096/097/098 adjuntos | DOC01 | EntryDocumentController autoriza path/main/observation/extra; Invoice annulled usa state_annulled no deleted_at | Acciones documentales omitidas | Mapear rutas autorizadas/permiso/tipo. No fallback público. Adjuntos anulados conservan contrato de Entry activo; no habilitar softdeleted | READY FOR FRONTEND RECOVERY |
| Perfil | http://127.0.0.1:8080/dashboard/perfil | Avatar | PROFILE01 | Servicio real image/UUID/rollback | DEV ya conecta form/profile.js | No doble recuperación; eventual QA HTTP aislado | ALREADY AVAILABLE |
| Material Edit/Save | Desde http://127.0.0.1:8080/dashboard/listado/materiales/v2 (ID requerido) | Guardado flags/posiciones | MAT01/03/04/05 | Nuevas reglas + pruebas aisladas | No contabilizar pérdida FR nueva; revisar gate concreto al entrar en fase | QA focalizado de adapter contra nuevo contrato, sin inventar bloqueos | QA REQUIRED |
| Cotización venta | http://127.0.0.1:8080/dashboard/crear/cotizacion/venta | Create/edit/contactos | D | QuoteFormReadAccess/permisos | Formulario/gates heredados requieren contraste focalizado | Candidato posterior, no asumir homologado ni contar como FR recuperado | QA REQUIRED |
| Reporte Órdenes por Material | http://127.0.0.1:8080/dashboard/reporte/de/ordenes/compra/por/materiales | Reporte vigente | Lookup legacy | Q conserva permiso report_orderPurchaseExpress | No recuperación FR contabilizada | Revisar UI en fase propia, sin ejecutar export | QA REQUIRED |
| Ventas / documentos | http://127.0.0.1:8080/dashboard/listado/ventas | R1 docs/emisión/NC/etc | P | Lector actual y renderer protegido | Ningún fallback público recuperado | Conservar; P cierre negocio no recertificación | ALREADY AVAILABLE |
| Cajas / subtipos | http://127.0.0.1:8080/dashboard/cash-boxes ; http://127.0.0.1:8080/dashboard/cash-box-subtypes | Edición configuración | CASH02/03/04 | CashConfigurationGuard + tests aislados | UI ya integrada | No nuevo gate; mostrar error backend; mutaciones reales aparte | ALREADY AVAILABLE |
| Owner / Platform | http://127.0.0.1:8080/dashboard/principal ; http://127.0.0.1:8080/dashboard/plataforma | Administración/cupos | Desarrollo nuevo | Nuevos servicios + migración | R7 export preservado, homologación nueva pendiente | Instalar columnas/configurar límites antes de certificar Platform | INSTALLATION REQUIRED |
| Dashboard operativo / ajuste | http://127.0.0.1:8080/dashboard/principal ; http://127.0.0.1:8080/dashboard/stock-items | FR059/063/064/102/103/105/106/107 | INV01/METRIC01/DASH-R7-01 | Sin fix específico | Gates conservados | DEV antes de recuperar | BACKEND STILL BLOCKED |
| Anular Entrada | Desde listado entradas compra | FR048 | ENTRY-R4-01 | finance no descartado en reversa | Acción no recuperada | DEV frontera financiera | BACKEND STILL BLOCKED |
| Legacy/pausados/retirados | Ver matriz vigencia R7.5 | 23 FR excluidos | Decisión negocio | Gates y documentos DEV | No restaurar | Ninguna recuperación hasta decisión posterior | PRODUCT SCOPE EXCLUDED |

## Orden recomendado

1. Resolver instalación de cupos (paso manual separado del merge). No usar una falla schema para invalidar fixes independientes.
2. R7.6A Compras normales Create/Edit + impresión eliminadas: FR017/018/036, contrato Q presente. Verificar campos/handlers/permisos con dataset sin mutaciones, luego recuperar controles.
3. R7.6B Create Entry FR046. Edit FR047 sólo después del fix de ENTRY-R75-01. Mantener FR048 bloqueado.
4. R7.6C Ocho capacidades de documentos Entries/Invoice con lectores protegidos. P negocio cerrado; no exigir la recertificación excluida ni inventar URL pública. No abrir archivos reales en esta fase.
5. Avatar/Cash ya disponibles: no presentarlos como nuevas recuperaciones. Material/QuoteSale/reporteMaterial requieren QA y clasificación exacta de control; no inflar los12 FR.
6. Dashboard Owner/Platform homologación visual separada, preservando nuevo flujo. R8 sólo por autorización posterior.

R7.6 GO WITH CONDITIONS. 12 candidatos FR;62 recuperados actuales;10 pendientes backend;23 excluidos. Backend fix no cierra pérdida frontend. NO PRODUCT DECISION REQUIRED para resolver este merge; máximos de plan requieren configuración comercial. J/P/K negocio cerrado con límites técnicos documentados.

## R7.7C — QuoteSale / Reporte por Material — 2026-11-25

Tres superficies homologadas: quoteSale.create, quoteSale.edit (cotización79 EDESCE C1) y report.orders.by.materials. Ver REAL-NEXT-QUOTE-SALE-PURCHASE-MATERIAL-REPORT-HOMOLOGATION.md. Permisos auxiliares Create OR Edit y contexto DEV preservados. Exportar del reporte alineado con report_orderPurchaseExpress, reemplazando gate obsoleto por roles; Express no reactivado. JS sólo loading/error no JSON del reporte. 24 checks responsive y12 modales PASS; guardados/export interceptados, cero mutaciones comerciales. Observación preexistente: botón de guardado general de Edit comentado; no se reactivó ni se certifica ese control. Backend/CSS/infraestructura0. Contabilidad sin duplicados: **74 recuperadas +10 bloqueadas +23 excluidas =107**. Persistencia pendiente; no recertificación backend/J/P. R7.7D/R8 no iniciadas. Sin commit/push/merge.

## R7.7D — Owner / Platform / Structure Limits — 2026-11-25

Ver REAL-NEXT-OWNER-PLATFORM-STRUCTURE-LIMITS-HOMOLOGATION.md. Dos dashboards homologados y Create/Edit de Planes reconciliados;12 páginas revisadas,9 páginas relacionadas ya conformes sin cambios. Allowlist existente suficiente. Migración estructural confirmada batch180; máximos empresas/locales reales NULL: pendientes, no ilimitados ni modificados.61 comprobaciones focalizadas Chrome aislado; contexto y export FR104 interceptados. Backend/JS/CSS nuevo/infraestructura/mutaciones0. Contabilidad74+10+23=107 intacta.

Candidato R8 explícito: QuoteSale Edit /dashboard/editar/cotizacion/venta/79, Guardar datos generales comentado; investigar origen/intención/alcanzabilidad/contrato antes de decidir recuperación o retiro. No intervenir aquí. R7.7E/R8 no iniciadas. Sin commit/push/merge; revisión del propietario pendiente.

## R7.7E — Cash Box / Subtype — 2026-11-25

Checkpoint compartido **6df57e7**, rama feature/venti-next-frontend, working tree inicial limpio. Este commit es el baseline de la futura R8; sin fetch/pull/merge. Ver [reconciliación CASH-02/03/04](REAL-NEXT-CASH-BOX-SUBTYPE-POST-DEV-RECONCILIATION.md).

Dos mantenedores reconciliados con los contratos actuales. Crear/editar/activar/desactivar ya estaban disponibles; cero gates obsoletos y cero capacidades nuevas contabilizadas. Restricciones transaccionales DEV preservadas; QA pendiente no oculta controles. Ajuste visual mínimo en dos Blade: grilla Bootstrap en los modales para agrupar campos relacionados y mejorar densidad; cero JS/CSS/backend/infraestructura.

DTO real EDESCE C1:4 cajas (1,2,5,6),5 subtipos (1,2,3,4,6). Lectura con transacción MySQL read-only. Chrome aislado:34 comprobaciones focalizadas,8 POST interceptados con rechazo SIMULATED, cero escrituras comerciales.375/1440/1920/2560 sin overflow global; precarga bancaria/diferida, iconos, modales y recuperación tras error PASS. Blade compile y diff check PASS. Sin recertificar pruebas backend DEV ni concurrencia; mutaciones reales permanecen pendientes.

**R7.7 COMPLETE en alcance frontend. R8 GO WITH CONDITIONS**, a la espera del propietario; R8 no iniciada. Condiciones: conservar10 blockers/23 exclusiones, candidato QuoteSale Edit de R7.7D, límites de evidencia y QA mutadora pendiente. No findings DEV nuevos ni cambios de backlogs. Contabilidad: **74 recuperadas +10 bloqueadas +23 excluidas =107**. No commit/push/merge.
