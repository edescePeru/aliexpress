# SUPPLIER-01 — Datos de cuentas según permiso efectivo

19/11/2026. CUSTOMER-03 aplicado: siete archivos coinciden, ambas columnas de trazabilidad presentes y pruebas aisladas pasan. QA HTTP/MySQL concurrente pendiente.

Usuario confirma list_supplierAccount como permiso necesario para leer cuentas. SupplierAccountRead limita listado/Excel por permiso y tenant. Sin permiso no carga cuentas; no depende de ocultar celdas. Página y ruta JSON validan permiso/propietario; getWorkerAccounts implementa el método ausente con ID de proveedor.

Dos pantallas usan permisos efectivos, incluidos directos. Respuesta de creación sin permiso de lectura usa account=null. No cambia política de escritura ni datos bancarios. Exportar proveedores no implica leer cuentas.

Pendientes instalación, HTTP/roles, descarga XLSX real y QA de escrituras de cuentas. No se certifican otros consumidores o exportaciones que no formen parte de estos endpoints. SUPPLIER-01 pendiente de verificar despliegue.

Verificación ejecutada: sintaxis de tres PHP; pruebas SQLite con permiso y sin él, FK de cuenta de otro tenant, exportación de activos/eliminados, permiso directo, método JSON antes ausente y respuesta de creación sin lectura. El log de consultas confirma ausencia de supplier_accounts cuando falta permiso. Se probaron controladores con DataTables/Excel simulados; lecturas MySQL y DataTables real con autorización simulada también pasaron en muestra local de un proveedor. No se generó XLSX real ni hubo escritura en ERP. Se confirmó registrada la migración CUSTOMER-03. Pendientes descarga real, roles HTTP y validación visual.

