# AUTH-01 — Arrays de permisos efectivos por pantalla

19/11/2026. SUPPLIER-01 aplicado: controladores, servicio y documentación coinciden; pruebas aisladas pasan. QA HTTP/XLSX pendiente.

CustomerController::index y ContactNameController::index pasan a getAllPermissions para incluir grants directos, preservando el contrato de sus vistas. SupplierController y SupplierAccountController ya fueron alineados en SUPPLIER-01.

No se otorgan ni revocan permisos. No cambia la autorización del servidor ni el ámbito de maestros del tenant. Se entrega reemplazo de dos métodos en TXT para conservar el resto de CUSTOMER-03 intacto.

Pendientes instalación y QA de las cuatro pantallas con usuarios de permisos directos, por rol y sin permiso, incluyendo rechazo HTTP. No declarar cobertura global de permisos de todo el ERP ni cambios del panel de plataforma.

Verificación ejecutada: métodos ensamblados sobre ambos controladores actuales y sintaxis aprobada. Prueba SQLite con App User, roles/permissions reales de Spatie y Gate registrado, guard web y caché array privada: usuario con grants directos sin rol, solo rol, mixto y sin permisos. Se reprodujo can(update_customer)=true con getPermissionsViaRoles vacío; la nueva vista recibe el permiso directo. Las seis habilidades comprobadas coinciden con Gate y el TenantScope de clientes se conserva. No se cambiaron grants ni cachés de la base real. Pendientes login HTTP y visibilidad/acciones reales en navegador.

