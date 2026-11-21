# EXT-01 — Recuperación revisada y mensaje privado del proveedor

## Punto de partida

TTT1-1, id 10, está aislada por conflicto con una guía manual distinta; el usuario mostró listado con CONFLICTO DE NUMERACIÓN. TTT1-2, id 11, contexto 1-1-1, se creó desde el ERP el 20/11/2026 y recibió HTTP 400/código 21 durante generate (referencia 7e0ef261ce4ea1925fa4b8b6). Conserva SENT sin respuesta. El payload histórico tiene transporte privado y placa principal con guion. El paquete posterior corrigió validación/normalización de nuevas creaciones; se verificó que sus dos archivos estaban instalados y las pruebas pasaron, pero no modifica retroactivamente id 11.

El diagnóstico anterior conservó código/HTTP, pero descartó el campo errors explicativo. No se puede recuperar ese mensaje original. El usuario autorizó este ajuste agrupado para conservar futuros detalles y recuperar la misma guía, sin acumular emisiones ni pasar a producción. El paquete de recuperación anterior no se instaló y queda reemplazado por este.

## Flujo

Vista previa en el detalle existente, con placa original→normalizada y dos consentimientos: cuenta (obligatorio) y generación/corrección (opcional, desmarcado). Servidor exige contexto válido y tres permisos conjuntos: listar, crear, editar. Ruta POST bajo web/dashboard/CSRF. La huella vincula registro, payload y conexión revisados.

La acción bloquea brevemente la guía y reserva un intento único. Guarda cifrados la cabecera y relaciones originales. No mantiene una transacción abierta durante HTTP. Consulta primero la misma identidad/cuenta. Una respuesta para el número conserva remote_found y no importa nada; bloquea consulta ordinaria para evitar otra asociación por serie/número. Una excepción HTTP 400/código 24 permite continuar solo con consentimiento. Sin él termina missing_checked sin cambiar placas ni enviar.

Antes de transmitir vuelve a verificar estado, payload y conexión. El histórico debe coincidir con lo que produce la guía actual; otros cambios no se admiten. Solo normaliza placa principal y secundarias mediante GreVehiclePlate, valida formato y actualiza vehículos/payload juntos, marcando generate. Usa el payload guardado corregido y aplica respuesta con ShippingGuideConsultation/snapshot. No recalcula número ni cambia datos comerciales. Conflictos previos no son elegibles.

Un fallo posterior a transmisión conserva review_after_send; uno anterior blocked_before_send. No se reintenta automáticamente ni se libera una reserva por tiempo. running puede significar proceso interrumpido y requiere revisión. response_applied significa respuesta válida aplicada, no necesariamente aceptación. Las correcciones confirmadas antes del envío permanecen si luego falla; el original está archivado.

## Diagnóstico privado

NubefactGreHttpException mantiene un mensaje genérico y metadatos; el campo explicativo errors se conserva aparte, limitado a 32 KiB y sin el token/URL conocidos. ShippingGuideDiagnostic lo cifra en shipping_guide_provider_failures y registra solo detail_archived más referencia/códigos/contexto en el log. Si archivar falla, sigue registrando metadatos sin volcar texto privado.

gre:diagnostic exige referencia y tenant/empresa/local; sin --show devuelve metadatos. --show descifra localmente y elimina URLs, emails, números largos y controles antes de imprimir. No hay endpoint/vista públicos del archivo ni del snapshot original. Este mecanismo captura próximos rechazos; no reconstruye el anterior ni diagnostica por sí solo la causa única del código 21.

## Instalación y pruebas

Nueva migración 000003 crea intentos y diagnósticos; falla si encuentra versiones previas de esas tablas, sin borrar evidencia. No requiere la migración 000001 del paquete descartado ni reemplaza el aislamiento 000002. Se entregan archivos completos más ruta TXT; instalación y emisión quedan a cargo del usuario en su entorno de pruebas.

Pruebas con Eloquent/SQLite, cifrado real de fixture y HTTP interceptado: corrección exclusiva de placas, snapshot exacto, error/timeout, ausencia/incertidumbre, número ocupado, cambios de contexto/conexión, doble/intercalación, rollback, conflicto, archivos privados y comando de lectura. Compilación Blade y sintaxis PHP. No hubo llamadas externas ni escrituras ERP real. No certifica HTTP con roles reales, MySQL concurrente ni Nubefact/SUNAT.

## Siguiente paso

Instalar, abrir id 11, revisar datos/placas/cuenta y autorizar la recuperación una vez. No crear TTT1-3 ahora. Si queda ACCEPTED, verificar identidad de documentos. Si queda pendiente, consultar sin generar otra. Si falla, leer el nuevo diagnóstico saneado para corregir el campo concreto antes de otro intento. EXT-01 sigue parcialmente atendido.
