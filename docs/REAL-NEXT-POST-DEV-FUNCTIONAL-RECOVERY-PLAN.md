# REAL NEXT — Post-DEV Functional Recovery Plan (R7.6)

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
