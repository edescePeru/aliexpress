# Validación del fondo en la apertura

La rama de sesión existente devuelve la sesión sin aplicar el fondo otra vez. La apertura nueva crea la sesión provisional en la transacción, valida y registra el fondo mientras conserva el bloqueo de la caja y luego confirma. Si la validación falla, se revierte toda la apertura.
