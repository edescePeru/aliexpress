# DATA-01 — Inventario antes de reconciliar

AUTH-01: ambos índices aplicados con getAllPermissions; prueba real User/Spatie/Gate SQLite aprobada. QA HTTP/visual pendiente.

DATA-01 se aborda mediante consultas de lectura y evidencia local adjunta. No se asigna tenant/company/branch ni se inventan números de cuenta. Los campos de imagen vacíos no demuestran archivos ausentes.

Siguiente paso: comparar el inventario del servidor; para cada candidato identificar documento, caja/sesión, proveedor o archivo de origen. Preparar correcciones por familia, con evidencia, respaldo y validación posterior. Los registros sin evidencia se mantienen pendientes, sin atribución automática. No se requiere aplicar SQL de escritura en esta etapa. Estado: diagnóstico preparado; reconciliación y validación del servidor pendientes.

Resultados locales del 19/11/2026: ventas sin contexto: 0; sesiones sin sucursal: 23; movimientos sin sucursal: 243, incluidos 2 sin empresa. Estos conteos son globales y no atribuyen los registros a la empresa 1. En tenant 1 no se detectaron cuentas sin número ni cuentas con proveedor incompatible. Metadatos de imagen vacíos: 0/15 materiales y 18/38 variantes; revisar si hay herencia de imagen antes de considerarlo incidencia. Referencias de contexto incompatibles en empresa 1: ninguna en las tres familias consultadas. No se comprobó integridad monetaria, ni archivos físicos, ni todas las relaciones entre movimientos, cajas y sesiones.

Verificación: las 11 consultas SELECT se ejecutaron sin error sobre el esquema MySQL local; las dos instrucciones SET solo definen variables de conexión. No se ejecutaron escrituras ni migraciones. Pendiente ejecución en servidor y revisión de evidencia de origen; DATA-01 permanece parcialmente atendido.
