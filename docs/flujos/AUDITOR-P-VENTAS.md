# Texto para el auditor — P, tramo de ventas

**P — Documentos locales de ventas:** Se preparó el bloqueo HTTP directo del directorio de comprobantes y sus subcarpetas, conservando archivos y referencias. Los enlaces locales pendientes pasan al lector existente autorizado por venta, permiso y contexto. El cambio de URL no modifica importes, pagos, generación ni estados de emisión.

El lector de PDF/XML/CDR de ventas, anulaciones y notas de crédito verifica carpeta, nombre, extensión y contenido admitido, y entrega cabeceras privadas sin caché y `nosniff`. XML/ZIP se descargan como adjuntos; el XML no se reserializa ni se procesa con DTD o acceso a red. La nota de crédito debe pertenecer a la venta autorizada.

Pasaron pruebas privadas con controles reales de contexto/permisos, casos negativos y GET sin escrituras de negocio, además de sintaxis PHP. Se comprobó también que una ruta pública de prueba de comprobantes de proveedores ya responde 403 en el servidor local.

**Estado:** paquete preparado; pendiente de instalación y comprobación HTTP del marcador de ventas, navegador y documentos reales autorizados. P permanece parcialmente atendido: siguen pendientes Entradas, archivos históricos de cotizaciones y certificación de correspondencia del proveedor. No se borran históricos ni se declara certificación criptográfica o migración de almacenamiento con este tramo.
