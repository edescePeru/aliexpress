# Guías — estados visibles durante creación

El usuario verificó TTT1-3 aceptada en el ERP tras consultar el resultado posterior de SUNAT. Solicitó que el botón verde explique el trabajo en curso y que el aviso final siga visible después de navegar al listado.

Antes, create.js deshabilitaba el botón sin cambiar texto, mostraba un toast y navegaba inmediatamente; always volvía a habilitar el botón incluso al redirigir. Ahora existe un bloqueo desde la confirmación, texto/icono de progreso y recuperación solo al cancelar o recibir una validación/denegación confirmada antes de persistir.

El resultado navega con un código de aviso de lista permitida y una referencia opcional de 24 caracteres hexadecimales. index.js renderiza un banner por texto, elimina estos parámetros de la URL y conserva el listado normal. El banner no cambia el estado real ni permisos; las filas siguen consultándose al servidor. No se lleva texto remoto ni datos del cliente en la navegación.

Se conserva distinción entre envío recibido pendiente de SUNAT y ACCEPTED. No se anuncia éxito ante REJECTED, estado desconocido o error de conexión. Los errores inciertos mantienen el bloqueo hasta abrir el listado para revisar, en lugar de invitar a repetir el envío.

Archivos completos: create.js, index.js e index.blade.php. Sin cambios de backend, esquema, rutas o permisos. No afecta las guías históricas ni la recuperación. Validación limitada a sintaxis JS, compilación Blade/PHP y revisión de flujo; prueba visual real pendiente. No se efectuaron emisiones durante la preparación.
