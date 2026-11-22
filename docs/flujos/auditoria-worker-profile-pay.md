# Segundo informe — Worker, PROFILE-01 y retiro PAY-01

## Punto de partida y autorización

Commit de separación ede0552 en multitenancy; el primer informe conserva pendientes QA/DATA. El segundo informe BUGS GUIAS DE REMISION.txt mezcla GUIDE-01/02 y tres puntos adicionales. Se verificó que GUIDE-01 ya tiene autorización funcional en código, que GUIDE-02 carece de conflicto por misma clave/contenido diferente y que el contrato SENT sigue incompleto. Estos puntos GRE no se modifican en este paquete.

Se confirmó rand en getWorkersEnable, ausencia de SELF en las tres mutaciones de perfil y consultas tenant_id/company_id sobre order_services/proformas sin esas columnas. El usuario autorizó retirar AMBOS módulos legacy, conservando datos. No se inventa ownership ni se elimina el scope de plazos para remediar la regresión.

## Worker

El método mantiene WorkerAccess::scope y enable=0, selecciona workers.* y una subconsulta de existencia en contracts correlacionada por worker_id. Devuelve booleano estable en have_contract, sin N+1 por contrato. La existencia incluye registros históricos/deleted_at, como el flujo normal previo con DB::table; no introduce un criterio de contrato activo.

## Perfil propio

SelfProfileAccess compara actor User autenticado e identidad de ruta/modelo. EnsureOwnProfile se registra en UserController solo para changeImage/changeSettings/changePassword y actúa antes de resolver los argumentos FormRequest. También hay guarda al inicio de cada método, antes de datos/archivo/hash. Un administrador no recibe bypass sobre el perfil de otra persona. Los flujos de administración de usuarios no se convierten en SELF.

La comprobación de contraseña actual pasa antes de abrir una transacción; la actualización usa DB::transaction. Se evita el retorno 422 con transacción abierta del método anterior. No se cambian los contratos de FormRequests ni se certifican aquí otras propiedades de almacenamiento de avatar; el alcance principal es ownership.

## Legacy / PAY-01

OrderServiceController y ProformaController conservan nombres de 27 y 17 acciones públicas, respectivamente, pero todas responden mediante RetiredCommercialController con 410 para JSON/HTML. No hay dependencias de documentos/FormRequests legacy ni consultas al seleccionar una acción retirada. Rutas y permisos existentes permanecen; no se requiere editar web.php ni migrar tablas.

Se retiran elementos activos de menú en appAdmin2/3/4, preservando otras secciones. El análisis de los layouts respeta comentarios Blade para no activar bloques históricos. No se eliminan modelos, datos, PDFs u otros archivos. URLs externas o archivos públicos antiguos no quedan certificados por retirar los endpoints; DOC-01 conserva su alcance. Las cotizaciones/servicios modernos ajenos a estos controladores no se retiran.

## Verificación

La suite usa clases entregadas, dependencias reales y SQLite en memoria. WorkerAccess y membresías reales; DataTables simulado solo para serializar filas. Perfil probado con validación/hash/Eloquent y filesystem privado, más despacho del router con binding y middleware para denegar cuerpo inválido de usuario ajeno antes de FormRequest. Las 44 acciones legacy devuelven 410 sin consultas SQL y conservan fixtures de tablas sin columnas de contexto. Se verifica scope PaymentDeadline/selectable y enlaces activos retirados.

Vistas compiladas con Blade y registro de directivas real de Spatie; PHP compilado y archivos comprobados por sintaxis. No se iniciaron sesiones ni se mutó el ERP real, su base ni archivos de usuarios. Pendientes instalación, HTTP completo con autenticación/CSRF y revisión visual. Este documento no declara cierre del primer informe ni certificación global.

## Continuación

Instalar y revisar los tres puntos. Después continuar GUIDE-02 y contrato de estados/reintentos en un paquete coherente, y verificar GUIDE-01 por HTTP negativo. No reabrir el diseño de catálogo/permisos ya aplicado sin una falla reproducible.
