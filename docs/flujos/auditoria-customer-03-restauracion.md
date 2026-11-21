# CUSTOMER-03 — Lotes de borrado de cliente/contactos

19/11/2026. PROMO-01 aplicado: 17 archivos, 18 rutas retiradas y constructor QuoteSale con middleware; pruebas aisladas aprobadas, QA comercial pendiente.

Decisión del usuario: restaurar únicamente los contactos borrados con el cliente; previamente eliminados quedan borrados. Datos anteriores sin evidencia: solo restaurar cliente.

Migración añade customer_deletion_batch nullable a customers/contact_names. CustomerDeletionLifecycle opera con permisos vigentes y tenant autorizado en transacción: padre primero, contactos en orden de ID. Borrado marca y elimina solo activos; restauración filtra lote/tenant/cliente y limpia marcas. Operaciones individuales limpian marca; crear/modificar contacto bloquea padres activos. Se conserva cascada existente como respaldo, sin borrado físico.

No backfill por fechas ni reasignación automática. No se añaden empresa/local a un maestro del tenant. Pendientes instalación, HTTP/roles, concurrencia MySQL y escritores externos. CUSTOMER-03 no se declara verificado en producción por pasar fixtures.

Verificación ejecutada: sintaxis de seis archivos PHP; migración aplicada sobre SQLite en memoria; casos positivos con cliente/contactos activos, borrados antes, otro lote, otro cliente/tenant, ciclos repetidos, historia sin lote y restauración individual. Fallos simulados de borrado y restauración revirtieron cabecera, contactos y UUID. Se comprobaron permisos, contexto, campos ocultos/no mass assignable y llamadas directas de los controladores con entradas de prueba. No se aplicó migración ni se alteraron datos del ERP. Pendientes validadores/middleware HTTP, mensajes visuales y concurrencia MySQL entre escritores reales.

