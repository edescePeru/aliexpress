# EXT-01 — Continuación de missing_checked sin liberar un envío incierto

El usuario instaló recuperación/diagnóstico y pulsó el botón con la casilla de cuenta marcada y la de emisión desmarcada. La captura muestra placa T0N-145 → T0N145 y respuesta Documento no existe, sin autorización ni envío. No constituye nuevo rechazo de generación; el flujo anterior consumió la reserva como missing_checked.

Este ajuste corrige esa restricción: preview vuelve a permitir una guía elegible con reserva missing_checked/consult. run exige consentimiento de generar, mismo usuario y contexto, y una huella nueva que incorpora id/estado/fase de la reserva. La versión de contenido sigue comprobando guía, payload y conexión durante la consulta. Se transiciona la misma fila a running con bloqueo y condición de estado; no se borra ni recrea, ni se reemplaza la evidencia cifrada previa.

El proveedor se vuelve a consultar: solo 400/24 permite aplicar las correcciones y generar una vez. Un número encontrado conserva el bloqueo de identidad; un timeout o fallo deja el estado de revisión existente. No hay autorización implícita ni reintento por tiempo. Formularios viejos, otro usuario, ausencia de consentimiento, cualquier fase de generación o estado distinto de missing_checked quedan bloqueados.

Se entrega solo ShippingGuideRecovery.php completo, instrucciones y pruebas; no hay cambios de esquema/rutas ni llamadas externas. La suite del paquete instalado se ejecutó sobre SQLite privado y HTTP interceptado con el servicio nuevo; incluye nuevas transiciones y regresión de placas, evidencia, contexto, timeout y diagnóstico. No certifica UI/router, MySQL concurrente ni Nubefact real.

Instalar y recargar detalle id 11, marcar ambas casillas y autorizar una vez. No crear otra guía hasta ver el resultado de TTT1-2. EXT-01 sigue parcialmente atendido.
