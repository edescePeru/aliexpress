# GUIDE-01 — Guías electrónicas: permisos y contexto

Retiro legacy verificado en archivos y constructor; diez rutas sustituidas. El constructor instalado usa el nombre corto RetiredReferralGuideController, equivalente al nombre completo por compartir namespace.

Nuevo paquete: doce acciones operativas con permisos existentes, middleware coherente de tenant/empresa/local y visibilidad de consulta al proveedor según edit_referralGuide. Se conserva el read model contextual moderno y no se llama a Nubefact/SUNAT al preparar el paquete.

Pendientes QA HTTP, filtros y documentos. La referencia a shipping_guides.download de la vista no se encontró registrada en routes/web.php y requiere siguiente corrección; no declarar GUIDE-01 cerrado.

Validación: PHP lint del controlador, sintaxis JavaScript y compilación/lint de dos vistas aprobados. SQLite en memoria confirma asignación de los doce permisos por acción, rechazo de contexto ausente/empresa ajena/local ajeno y continuidad con contexto coherente. No se ejecutaron acciones de emisión ni llamadas al proveedor; matriz HTTP de roles pendiente.
