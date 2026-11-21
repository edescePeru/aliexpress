# GUIDE-01 — Documentos de guías electrónicas

Verificación: permisos/contexto aplicados; controlador, script, vistas y documentación coinciden. Se confirma referencia a shipping_guides.download sin ruta, alternativas ZIP consultadas en atributos no existentes y enlaces públicos directos en listado/detalle.

Se incorpora ruta autorizada por ID/contexto, descarga de ZIP desde last_nubefact_response o redirección a enlace Nubefact validado. No hay fetch del servidor, extracción ZIP, emisión ni mutación al descargar. Se ocultan enlaces/base64/diagnóstico en serialización y en respuesta operativa; el dato almacenado se conserva.

Manual PDF del usuario revisado para campos de respuesta. Fuente oficial de integración: https://www.nubefact.com/integracion . El control del ERP no revoca enlaces externos ya conocidos. Mantener GUIDE-01 parcial hasta QA real y revisión restante.

Validación: PHP lint, JavaScript y Blade compilado aprobados. SQLite en memoria sin red valida ZIP, enlace HTTPS normalizado, permisos PDF/XML, contexto ajeno, documento ausente, tipos/URLs inválidos y serialización/redacción sin enlaces/base64. No hubo emisión ni consulta al proveedor. QA HTTP y documentos reales pendientes.
