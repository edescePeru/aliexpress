# Punto de corte de auditoría — 20/11/2026

Este commit separa los cambios acumulados de auditoría y GRE de los siguientes pendientes. No declara cierre total del primer informe.

GRE: TTT1-3 generada desde el ERP y aceptada en DEMO después de consultar; botones PDF/XML/CDR visibles. Apertura y correspondencia de los tres documentos todavía no acreditadas en esta conversación. TTT1-1 aislada por conflicto con otra operación manual; TTT1-2 conserva el intento fallido por RUC ficticio inválido. Se entregó el indicador Enviando y aviso de resultado; falta observarlo en una próxima operación autorizada.

## Siguiente bloque, aún sin implementar por este corte

- GUIDE-01: autorización funcional ya presente en rutas/controlador; falta validación HTTP negativa con roles reales.
- GUIDE-02: clave persistente, unicidad y replay existentes; falta conflicto por misma clave con contenido diferente y concurrencia MySQL real.
- Contrato GRE: distinguir error API, resultado externo incierto y pendiente SUNAT; no confundir SENT con aceptación o transmisión confirmada.
- Worker/getWorkersEnable: sustituir rand por existencia real de contrato, conservando WorkerAccess y ámbito.
- PROFILE-01: guard común SELF antes de mutar imagen, settings o contraseña.
- PAY-01: resolver documento autorizado sin consultar tenant_id/company_id inexistentes en order_services/proformas y usar selectable(currentId).

QA-01/02/03/04 y DATA-01 conservan los pendientes de sus documentos; las pruebas aisladas no equivalen a certificación integral ni despliegue productivo. El segundo informe BUGS GUIAS DE REMISION.txt se analizó, no se implementa todavía en este punto de corte.
