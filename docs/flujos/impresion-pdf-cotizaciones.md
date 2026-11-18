# Generación de PDFs de cotizaciones

La impresión de cotizaciones de venta devuelve el PDF en memoria. La impresión interna y de cliente del módulo anterior valida el contexto y utiliza un archivo temporal único para combinar adjuntos, eliminado al salir de la operación. Se retira el endpoint de prueba que generaba una cotización fija y limpiaba public/pdfs. El acceso directo a documentos existentes se revisa en DOC-01.
