# GUIDE-01 — Persistencia de respuesta inicial

Se verificó aplicado el paquete consulta. Nueva entrega reutiliza validación, preservación documental y bloqueo/huella en la respuesta de send. La huella se toma dentro de la creación antes del commit, con todos los atributos leídos de la base; no se obtiene una versión ya modificada tras liberar el registro.

La interfaz distingue guía persistida de creación fallida. Se mantiene el estado actual y se devuelve 409 ante conflicto. No se deduplican emisiones ni se añade reenvío automático. Preservar el documento es distinto de verificar su vigencia remota. GUIDE-01 parcial hasta QA y pendientes identificados.

Verificación ejecutada: sintaxis PHP del controlador y servicio, sintaxis JavaScript, ocho casos previos de consulta y cuatro casos de respuesta de emisión sobre SQLite en memoria. Sin llamadas al proveedor. No se ejecutó el formulario HTTP completo ni concurrencia real MySQL.

