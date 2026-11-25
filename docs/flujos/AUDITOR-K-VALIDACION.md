# Texto para el auditor — únicamente K

**K — Créditos de proveedor: validación agrupada.** Se ejecutó una prueba reproducible sobre los controladores y modelos instalados usando SQLite descartable. Pasaron asociación Entrada→Crédito sin duplicar, registro/retiro de abonos, saldos por historial, centavos de los casos probados, reintentos secuenciales, conflictos de referencia, fechas/plazos/marcas y rechazos por contexto/permisos.

También pasaron escritura/lectura de PDF en almacenamiento privado de prueba, acceso por objeto, cabeceras privadas, compatibilidad histórica controlada y reversión completa ante un fallo después de guardar el archivo. La reversión elimina solo el archivo nuevo y conserva comprobantes históricos. No se reprodujo un defecto nuevo que requiera cambiar el código en estos casos.

Se adjuntan prueba, salida y huellas del código verificado. No se registraron pagos en la base del negocio ni se llamó a proveedores.

**Estado: parcialmente certificado.** Continúan pendientes HTTP real con autenticación/CSRF, roles/navegador, concurrencia y precisión DECIMAL/MySQL, ciclo completo de imágenes y bloqueo de URLs públicas históricas en Apache. Esta evidencia no declara cerrado todo K ni el bloque documental P.
