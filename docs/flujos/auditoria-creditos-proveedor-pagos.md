# Créditos — Escritura de abonos

Revisión: controlador, comando, servicio de vencimiento y documentación anterior coinciden con la entrega instalada; prueba aislada de lecturas sin escrituras repetida con éxito.

Nuevo paquete: validación de importe/fecha/archivo, bloqueo del crédito padre al registrar o borrar abonos y limpieza del archivo recién creado si revierte el registro. Conserva permisos y formato de respuesta.

No cerrar auditoría: FLOAT, duplicados/sobrepagos, moneda, conciliación con estados, caja y privacidad documental quedan pendientes. No se modificaron datos del ERP durante la preparación.

Validación: PHP lint correcto y pruebas SQLite en memoria aprobadas para alta positiva, fecha opcional, importes inválidos, fecha imposible, rechazo entre empresas, rollback ante fallo simulado y eliminación propia/repetida. No se han probado adjuntos reales ni concurrencia MySQL.
