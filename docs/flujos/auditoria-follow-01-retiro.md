# FOLLOW-01 — Retiro autorizado y conservación de datos

19/11/2026. Usuario decide retirar acceso a seguimiento/alertas de materiales y conservar sus datos históricos.

Diez rutas pasan a RetiredFollowMaterialController (410). FollowMaterialController conserva firmas como adaptador al aviso sin leer/escribir, enviar mensajes ni exportar. Menús y controles de salidas dejan de exponer la función; se eliminan sus AJAX sin alterar el resto de la operación de salida. stocks:send queda desprogramado e inerte por invocación manual/cron.

No se borran tablas, registros, permisos, clases de modelo/exportador/mail ni archivos históricos. No se ejecuta correo real. Ruta de visualización de orden de compra dentro del bloque permanece igual.

Evidencia INV-01 recibida: capturas de empresa 1/tenant 1 y consultas 02–09 vacías. No demuestra conciliación histórica total ni otras empresas. Mantener pendientes de QA y definición monetaria, sin forzar reparaciones.

Retiro FOLLOW-01 preparado, pendiente de instalación y validación HTTP/visual/programación. La conservación de datos no implica habilitación de consulta histórica global ni cierre por tenantización de un módulo retirado.

Verificación ejecutada: cuatro PHP y bloque de rutas sin errores de sintaxis; siete vistas compiladas con las directivas reales del ERP y comprobadas; cinco JavaScript validados. Pruebas aisladas de JSON/HTML 410, diez adaptadores sin contenedor DB/Mail/Excel, comando inerte, diez rutas retiradas y nombres conservados; se mantuvo la ruta de orden de compra. Comparación estática conserva las URL AJAX ajenas al seguimiento en los cinco scripts. No se ejecutó correo real ni se modificó el ERP/base durante preparación. Pendientes HTTP con auth/roles/CSRF, navegación visual, flujos completos de salida y programación real tras instalar.

