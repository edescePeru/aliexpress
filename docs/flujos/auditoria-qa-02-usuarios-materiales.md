# QA-02 — Altas/edición y parámetros/selectores de material

20/11/2026. Verificado paquete settings/usuarios anterior: documento instalado idéntico y suites repetidas aprobadas. Este bloque agrupa tres suites adicionales sobre código instalado; no hay una corrección nueva de aplicación indicada por los casos probados.

| Grupo | Evidencia aprobada | Límites |
|---|---|---|
| Alta y edición de usuarios operativos | Alta persiste contexto/privilegios operativos, hash y cambio obligatorio; sincroniza rol/empresas/locales; edición actualiza nombre y reemplaza alcance; rechaza rol/empresas/locales/default incompatibles sin filas parciales; fallo de edición revierte usuario y conserva membresías; cupo agotado no crea usuario | Sin avatar ni propietario, sin HTTP, concurrencia ni revocación de sesiones activas |
| Selectores de catálogo | 31 comprobaciones con declaraciones de cuatro rutas instaladas; permisos de formulario/lectura, rechazo de padre de otro tenant, invitado y usuario sin permiso, catálogos compartidos entre empresas del mismo tenant y separación entre tenants | Router privado y actor de permisos simulado; no stack real ni CRUD completo; lectura no otorga escritura |
| Parámetros de material | Dependencias automáticas, unidad obligatoria, clave desconocida rechazada, versión actual aceptada, formulario obsoleto y cambio de empresa rechazados, campos deshabilitados retirados | No persiste formulario ni certifica todos los campos/configuraciones o membresías |

Las suites usan Container/Eloquent/Validator/Spatie y modelos/controladores instalados según cada caso, con SQLite privado. En usuarios las identidades, tenant/plan, rutas de respuesta y report de excepción son privados; no se envían credenciales ni se imprime contraseña. La prueba de rollback induce un cambio real de nombre antes de lanzar el evento para garantizar que se ejecuta persistencia. En catálogos se evalúan solo cuatro declaraciones conocidas del archivo local de rutas, no todas las rutas del ERP.

Estado QA-02 parcialmente atendido: evidencia de familias ampliada en bloque. No declarar completas las operaciones con archivos de usuarios, concurrencia MySQL ni HTTP/UI. Quedan avatar/propietario, persistencia de parámetros y CRUD de catálogos restantes por revisar antes de un cierre completo. QA-01 conserva lista final de descargas reales pendiente. Los siguientes pendientes del backlog siguen siendo QA-03/QA-04/EXT-01, sin confundir pruebas aisladas con certificación global.
