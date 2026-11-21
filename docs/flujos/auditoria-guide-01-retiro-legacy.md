# GUIDE-01 — Módulo antiguo retirado

18/11/2026: el usuario autoriza solo guías electrónicas y conservar datos antiguos. El menú ya usa shipping_guides.view. Se encontraron diez rutas antiguas operativas sobre tablas sin tenant/empresa; en la copia local esas tablas y shipping_guides están vacías, sin extrapolar a producción.

Entrega: diez URLs antiguas hacia aviso 410, constructor de bloqueo del controlador anterior y vista informativa. Se preservan nombres/permisos y todos los datos. No se derivan mutaciones a las rutas modernas. No se realizan llamadas a Nubefact/SUNAT durante esta preparación.

Estado preparado para instalación y QA. GUIDE-01 parcial: revisar por separado el flujo moderno, contexto, descargas y proveedor. No declarar borrado de código ni migración histórica porque no se realizan.

Verificación: PHP lint del controlador, bloque de rutas y controlador antiguo simulado con el constructor; Blade compilado sin errores. Router aislado sin conexión de base de datos: diez URLs GET/POST devuelven 410, JSON sin registros, aviso HTML y guardia del controlador anterior antes de ejecutar acción. Middleware de autorización desactivado únicamente en esa prueba; QA HTTP con sesión/permisos reales pendiente. No se llamó al proveedor.
