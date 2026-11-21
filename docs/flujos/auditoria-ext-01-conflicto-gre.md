# EXT-01 — Conflicto local/remoto TTT1-1

## Evidencia y decisión

El 20/11/2026 el primer intento autorizado del ERP dejó guía local id 10, tenant 1/company 1/branch 1, TTT1-1, FLORA Y FAUNA, documento 10482175235 y partida 150101. La respuesta original no quedó registrada. Consultas posteriores devolvieron HTTP 400/código 24. El diagnóstico seguro se instaló y funcionó.

Después el usuario creó manualmente TTT1-1 en Nubefact para Jorge Arcadio Gonzales. Recibió rechazo 3363 por ubigeo de partida 000000 y, tras corregirlo, aceptación código 0. El usuario no instaló el paquete de recuperación y pulsó Consultar en el ERP: el mecanismo anterior importó la aceptación/documentos por serie/número, aunque el destinatario local era diferente. Esto valida la consulta, no la generación original ni igualdad entre las operaciones.

El usuario autorizó preparar este paquete para aislar el registro local, conservar evidencia y bloquear su uso. Se conserva el documento aceptado remoto. Este paquete reemplaza la recuperación anterior (no instalada). No agrega dicha ruta/tabla ni ejecuta llamadas externas.

## Implementación

Nueva marca identity_conflict_at (no mass assignable) y tabla shipping_guide_identity_conflicts, con una fila única por guía. El comando exige identidad explícita y esperado documento local; sin --apply solo inspecciona. La aplicación transaccional bloquea la fila, archiva raw original de cabecera y detalles cifrado con APP_KEY y SHA-256 de integridad, y retira respuesta/enlaces/aceptación atribuidos erróneamente. El payload local original y datos comerciales no se alteran. La evidencia no se expone mediante modelos públicos, vistas ni rutas.

Se usa ERROR como estado técnico compatible con el enum existente, acompañado por la marca de conflicto que domina la presentación. No se agrega otro valor al enum ni se interpreta como rechazo SUNAT del documento local. La aceptación previa existe solo como evidencia archivada. Repetir el comando no sobrescribe el archivo previo.

ShippingGuideDocument oculta disponibilidad y bloquea resolución de links/ZIP. ShippingGuideController bloquea consulta antes de resolver conexión. ShippingGuideConsultation comprueba la marca bajo lock antes de aplicar respuesta, para impedir que una consulta en vuelo reponga documentos. NubefactGreService bloquea envío/buildPayload de una guía marcada. Listado y detalle muestran conflicto y retiran botones; el Excel no declara aceptación ni entrega PDF.

No hay desbloqueo automático, borrado, renumeración, importación manual ni cambios de configuración. La migración no hace backfill ni aplica el conflicto automáticamente. Solo el comando explícito afecta la guía identificada. No se editó el ERP real ni la base de usuario durante la preparación del paquete.

## Pruebas y límites

Se ejecutó la migración y servicios reales sobre SQLite en memoria, cifrado real con clave efímera privada, controlador/consulta/documentos/export y comando Symfony/Laravel. Se verificaron contextos equivocados, identidad incorrecta, repetición, rollback de escritura/cifrado, snapshot exacto, ausencia de red, bloqueos y comportamiento no afectado de otra guía. Se compila la vista con Blade real y se comprueba sintaxis PHP/JS. No certifica despliegue, router/HTTP con roles reales, MySQL concurrente ni emisión externa.

Un enlace externo ya copiado o un PDF ya descargado queda fuera del alcance del ERP. No se revocan documentos en el proveedor. La guarda evita nuevas llamadas posteriores a la marca; no cancela una petición remota ya transmitida. Una respuesta posterior en vuelo sí queda impedida de alterar la guía marcada.

## Continuación

Instalar, migrar únicamente este archivo, comprobar identidad local por comando sin --apply y aplicar el aislamiento. Verificar listado/detalle/descarga/consulta. Luego preparar una guía nueva desde el ERP con número libre, sin crear antes su homóloga manual en Nubefact. Mantener EXT-01 parcialmente atendido hasta completar esa emisión y obtener aceptación/documentos atribuibles a la operación correcta.
