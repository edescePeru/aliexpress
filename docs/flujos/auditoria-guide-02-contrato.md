# Segundo informe — GUIDE-02 y contrato de transmisión GRE

El paquete Worker/PROFILE-01/PAY-01 se cotejó con los 12 archivos instalados y se reejecutaron sus pruebas. El usuario pidió continuar. Este paquete aborda la comparación de contenido y el estado externo; GUIDE-01 ya tiene permisos y se amplía su evidencia aislada.

## Identidad de operación

ShippingGuideRequestFingerprint construye SHA-256 de versión 1, contexto servidor y campos admitidos. No incluye CSRF, emission_key, número provisional ni campos ajenos. Normaliza escalares/trim/orden de claves, conserva filas, y se calcula después de normalizar placas. Se guarda emission_fingerprint oculto en JSON.

ShippingGuideEmission::existing exige huella para devolver una operación previa y se llama antes de validar y después del lock de empresa. Diferencia de contenido y registro histórico sin huella producen 409 antes de nueva persistencia/emisión. Continúan las restricciones únicas shipping_guides_emission_unique y gre_company_document_unique; se comprobó su existencia mediante metadata local de solo lectura.

No se infiere una huella de payloads antiguos: el request contiene IDs/selecciones que el payload fiscal no permite reconstruir inequívocamente. Un histórico se consulta por su registro, no reenviando Store. Una clave nueva constituye una operación diferente, no un retry.

## Transmisión

submission_state separa REGISTERED, UNKNOWN, API_ERROR, PENDING_SUNAT, ACCEPTED y REJECTED. El conflicto existente domina la representación sin añadir un valor al enum status. Antes de persistir no hay operación recuperable; después del commit la misma fila y número permanecen. REGISTERED se confirma con el payload; UNKNOWN se confirma antes de HTTP. Es un estado conservador tanto durante la petición como ante un resultado incierto, no una certificación de recepción.

Errores HTTP 4xx del proveedor se muestran API_ERROR, excepto código 23 que indica posible documento existente y permanece UNKNOWN; HTTP 5xx, conexión o respuesta inválida también UNKNOWN. No implica que una guía esté libre: cualquier recuperación continúa consultando primero. Referencia y código se guardan sin cuerpos privados. Los mensajes cifrados previos se conservan mediante el diagnóstico ya instalado.

ShippingGuideConsultation actualiza el contrato junto al estado original y clasifica respuesta negativa con código SUNAT no vacío/no cero como rechazo, tanto en emisión como en consulta. Aceptación false/código0 queda pendiente. Snapshot y guarda de conflicto evitan que un fallo o respuesta tardía sobrescriba otro estado más nuevo.

Recuperación adapta elegibilidad a REGISTERED y API_ERROR, conserva UNKNOWN y todas las reservas/bloqueos previos, incluyendo missing_checked explícito. Solo normaliza placas; no agrega corrección silenciosa de destinatarios ni reinicio de intentos de emisión. La clave de creación conserva la identidad original aun si un flujo auditado de recuperación normaliza el payload histórico.

## UI e históricos

Modelo expone emission_state/emission_state_label; listado y detalle muestran etiquetas explícitas. El resultado de creación API_ERROR navega a aviso de error del proveedor, no éxito. Un 409 por cambio de contenido muestra conflicto y bloquea el botón, sin navegar como si hubiera sido aceptada. Se conserva Enviando/bloqueo y los avisos anteriores.

No hay backfill de estados/huellas. TTT1-1 permanece en conflicto; TTT1-2 no se reenvía ni se reinterpreta a partir de logs; TTT1-3 mantiene aceptación existente. Los registros históricos sin estado operativo usan una interpretación conservadora de su status. Las columnas nuevas permiten registrar futuras transiciones con evidencia; no certifican retrospectivamente una emisión.

## QA

Store real, migración real e índices únicos sobre SQLite privado; proveedor/catálogo simulados. Casos de contenido, replay, históricos, fallo previo/posterior, clasificación y rechazo, guardas de snapshot e intercalación antes de terminar HTTP. Regresión de recuperación con cliente HTTP interceptado, evidencia cifrada y conflicto. GUIDE-01 mediante middleware User/Spatie/Gate real y cotejo de siete rutas.

Se verifica sintaxis y vistas. No hay escrituras de pruebas sobre ERP real ni emisiones externas. Falta concurrencia MySQL con conexiones simultáneas y comprobación HTTP/UI completa instalada. La intercalación privada no se presenta como prueba de lockForUpdate real. No se declara cierre integral del primer informe ni de EXT-01.
