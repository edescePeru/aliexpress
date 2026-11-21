# EXT-01 — Generación TTT1-2 y validación de placas

Tras aplicar el aislamiento de TTT1-1, el usuario emitió desde el ERP una nueva guía TTT1-2, id 11, contexto 1-1-1. El 20/11/2026 a las 20:58:12 el diagnóstico registró generate, HTTP 400, código 21 y referencia 7e0ef261ce4ea1925fa4b8b6. No se vio creada en el panel de Nubefact; localmente permanece SENT/POR CONFIRMAR. No se modificó ese estado durante este trabajo.

Se inspeccionó localmente el payload histórico sin red ni escrituras: tipo de transporte 02 y placa principal con guion, incompatible con el formato declarado por el manual del proveedor. No se imprimieron tokens, número de placa, identidad de conductor ni direcciones. El manual API GRE v1.7 proporcionado por el usuario permite DEMO y exige placa sin guiones de 6 a 8 caracteres; también declara fecha de entrega al transportista para transporte público, campo que no aplica al transporte privado de esta prueba y debe revisarse en un alcance posterior.

El controlador entregado incorpora normalización controlada mediante GreVehiclePlate, antes de validar los campos de vehículos de ambas modalidades. Persiste las placas normalizadas; rechaza formatos incompatibles antes de guardar/enviar. Conserva contexto, permisos, clave de emisión, diagnóstico, tratamiento de errores y guarda de conflicto previamente instalados.

No se altera retroactivamente el payload de id 11, no se importa otra respuesta y no se llama al proveedor. El código 21 aislado no permite determinar todos los errores del contenido. El diagnóstico anterior omitió el texto explicativo del proveedor; esta limitación debe resolverse de forma segura antes de concluir causalidad o preparar una recuperación externa. No usar producción para sortear el error.

Pruebas del store entregado con validación/Eloquent reales, BD SQLite privada y proveedor simulado: MANUAL/SALE, público/privado, repetición/rollback y normalización/rechazo de placas principales/secundarias. Sintaxis PHP comprobada. No certifica router HTTP, despliegue, MySQL concurrente ni aceptación en Nubefact. EXT-01 continúa parcialmente atendido.
