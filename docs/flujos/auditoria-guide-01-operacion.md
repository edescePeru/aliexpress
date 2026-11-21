# GUIDE-01 — Identidad de operación de emisión

19/11/2026. Respuesta-envio verificado aplicado, con doce pruebas aisladas aprobadas.

La operación de un formulario se identifica con emission_key UUID; vive en shipping_guides y tiene índice único (tenant_id, company_id, emission_key), nullable para históricos. No se expone en respuestas del modelo. Los reintentos retornan el estado actualmente guardado, sin retransmitir. Una clave con otros datos conserva el primer documento e informa que no aplicó los cambios.

El servidor verifica contexto del formulario y UUID antes de buscar reintentos; relee después del bloqueo de la empresa y antes del correlativo. No conserva bloqueo durante la petición al proveedor. Un fallo anterior al commit no consume la clave. La respuesta de emisión conserva la protección de versión del paquete previo.

No se confunde idempotencia por clave con detección del mismo traslado en formularios nuevos, entrega garantizada al proveedor ni aceptación SUNAT. Prohibido reemitir automáticamente como efecto del reintento. QA HTTP/MySQL real, recuperación tras caída y clasificación SUNAT siguen pendientes. GUIDE-01 parcial.

Verificación ejecutada: cuatro PHP de producción/migración sin errores de sintaxis, vista Blade compilada y comprobada; doce casos previos de respuesta y dieciséis comprobaciones de operación sobre SQLite en memoria, incluida ejecución de la migración, índice único, rollback y reintentos del controlador con proveedor simulado sin llamadas send. No equivale a concurrencia MySQL ni emisión HTTP completa.

