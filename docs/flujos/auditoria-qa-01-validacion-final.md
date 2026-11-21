# QA-01 — Estado consolidado y validación final

20/11/2026. Último paquete de permisos completo: documento instalado idéntico y prueba de middleware aprobada otra vez. Comprobaciones consolidadas repetidas: precisión del detallado, ganancias diarias y servicio de exportación financiera, todas aprobadas en fixtures privadas. No hay un cambio de aplicación nuevo en este documento.

## Completado en código y pruebas aisladas

- Columnas monetarias del detallado en K:O, presentación guardada en la venta y etiquetas de pagos mixtos/parciales.
- Precisión conservada en líneas; suma redondeada al final consistente con los casos probados del reporte.
- Conjuntos mayores que una página, límites de fechas, contexto, monedas, costos faltantes y cero.
- Resumen diario: recálculo sin duplicados, total completo entre páginas y rollback ante fallo simulado.
- Finanzas: fechas vacías o rango válido, rechazo de parciales/invertidas/invalidas, referencias ausentes y contexto.
- Middleware de permisos de las tres rutas revisadas, grants directos y por rol, rechazos esperados.

## Una sola validación final pendiente

Realizar en copia de pruebas con los cambios instalados. No modificar registros productivos ni emitir documentos externos para probar.

| Paso | Comprobación | Resultado esperado |
|---|---|---|
| 1 | Descargar ganancias por usuario y detallado con mismo usuario/período; usar más de diez ventas cuando exista dataset | Incluyen conjunto completo; totales comparables al listado completo, no solo página visible |
| 2 | Abrir XLSX detallado con presentación, varios canales/abonos y costos decimales o faltantes | Columnas correctas, etiquetas acordes al historial, precisión conservada y costos faltantes explícitos |
| 3 | Descargar Finanzas sin rango y con rango; intentar un rango incompleto | Todo el contexto o solo período válido; rango incompleto rechazado. Comparar PEN y USD por separado |
| 4 | Repetir acceso por URL con usuario sin permisos y cambiar a otro contexto permitido | Sin descarga no autorizada ni datos ajenos. Finanzas usa list_invoice; no existe separación de exportar en esa ruta |
| 5 | Abrir detalle diario con dos páginas | TOTAL DEL DÍA igual en ambas, coherente con resumen |

Registrar fecha, versión instalada, empresa/local de pruebas, rol, filtros, número de filas y resultado por paso. No adjuntar secretos ni datos bancarios completos. Una incidencia reproducida puede requerir corrección; no se declara que nunca habrá más cambios.

Estado actual: QA-01 parcialmente atendido, pendiente validación real integrada. No seguir dividiendo este punto en paquetes sin un fallo nuevo reproducido. Cerrar solo cuando se complete evidencia real; si el dataset no permite un caso, registrarlo como pendiente, no aprobado.

## Continuación: QA-02

Iniciado inventario de archivos y paquetes existentes para configuración/mantenedores. PaymentDeadlineController ya tiene operaciones transaccionales; existen fixtures previas en plazos-pago-contexto y categorias-eliminacion-segura. Su existencia no certifica el código instalado ni las pruebas UI actuales.

Revisar en grupos con un resultado consolidado por grupo: (1) plazos y categorías/facturas; (2) impuestos, bancos y maestros; (3) settings, uploads, ConfigUserWeb y catálogos de material. Contrastar código instalado, usar fixtures privadas para persistencia/rollback/guards y reservar navegador/HTTP para integración. No ejecutar suites antiguas contra datos reales ni reinstalar migraciones anteriores. No reabrir decisiones de producto ya acordadas sin un defecto reproducido.

Otros puntos del tramo final del backlog: QA-03 traslados/concurrencia, QA-04 restauraciones/roles/paginación y EXT-01 integración externa. DATA-01 mantiene reconciliación histórica pendiente. Este listado no afirma que todos los demás hallazgos del auditor estén cerrados.
