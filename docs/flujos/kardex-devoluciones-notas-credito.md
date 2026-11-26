# Reingresos por notas de crédito: Kardex, costo e históricos

Fecha: 25/11/2026. Estado de implementación: preparado para instalación. Evidencia de QA: fixtures privados SQLite y MySQL; comprobación real en el ERP de pruebas después de copiar archivos y migrar.

## Comportamiento acordado

La nota parcial sigue siendo por devolución de productos. Se espera su aceptación SUNAT para aplicar stock y dinero. Ahora ese mismo acto registra su evidencia de inventario por lote y por ítem, cuando corresponda.

Ejemplo: venta de una unidad a S/ 100 con costo histórico S/ 60. Su devolución acredita el importe correspondiente al cliente según el saldo y canal acordados. El inventario recibe una unidad valorizada en S/ 60. Precio de venta y costo del producto son conceptos distintos.

Se mantiene el tenant, empresa, local, almacén y ubicación de la operación. Una caja bancaria compartida no cambia el almacén del reingreso: se usa la procedencia física de la salida, aunque el canal financiero elegido sea otro.

## Persistencia

`credit_note_stock_returns` conserva nota, detalle, venta, salida original, producto/material, lote, almacén, ubicación, ítem, cantidad, costo unitario, costo total, moneda cuando existe y procedencia de cada dato. La combinación detalle de nota/salida es única. Las referencias impiden borrar la nota o salida mientras sustenten un reingreso registrado.

El costo proviene de `output_details.unit_cost`; si falta, se admite `output_details.total_cost / cantidad original`, dejando registrada esa procedencia. Un costo cero guardado es válido. Si faltan ambos, no se utiliza precio de venta, costo vigente del lote ni costo actual del catálogo: se detiene la preparación para revisar la información.

Las salidas nuevas del POS guardan `cost_currency`, tomada de la moneda base de la empresa. Se asigna como dato interno, sin añadir un campo editable al formulario ni a la asignación masiva del modelo. En salidas anteriores sin esa moneda, se conserva la convención previa del inventario (costo base sin etiqueta histórica), marcada como `legacy_unlabelled`. No se infiere esa moneda desde `sales.currency`. El proceso de reconstrucción exige que el operador confirme la moneda histórica explícitamente; si contradice una moneda guardada, se rechaza.

Los costos se guardan con hasta diez decimales; la cantidad admite hasta seis en el registro. La nota conserva la precisión de su propia tabla (cuatro decimales) y se verifica además que lote y nivel puedan guardar la cantidad sin redondearla. No se amplía silenciosamente la precisión de tablas existentes.

## Transacción y reintentos

La preparación anterior al envío valida procedencia, costo y precisión con un punto de restauración; no deja movimientos de reingreso.

Al aplicar una nota aceptada, con la venta y nota bloqueadas:

1. Se identifica la salida y lote originales y se valida el contexto.
2. Se agrega la evidencia de cada cantidad que realmente reingresa.
3. Se aumenta el lote, se actualiza el ítem si existe y se sincroniza el nivel físico.
4. Se marca `stock_return_version=1`.
5. Se completa el egreso y se marca la reversión interna de la nota.

Si falla la caja o cualquier dato físico, toda esa transacción se revierte. La aceptación fiscal guardada permanece y se puede completar al consultar de nuevo. Las transacciones de aplicación permiten reintentos ante interbloqueos, sin volver a enviar el documento fiscal. Una nota ya aplicada responde de forma idempotente a otra consulta HTTP.

## Fuente común de movimientos

`InventoryTimelineQuery` reúne la vista instalada de compras/salidas, los despachos y recepciones de traslados y el registro de devoluciones. Reutiliza las fronteras físicas y empresariales de los lectores actuales. No reemplaza ni vuelve a instalar la definición de `inventory_movements_view`, preservando sus correcciones previas.

El orden es fecha, orden físico del evento e identificador. Si coinciden fechas, los reingresos por nota se procesan después de las salidas originales. El costo unitario guardado de una salida se conserva; el promedio del saldo se actualiza después de ingresos y egresos. Ambos lectores de `InventoryCostService` usan ese mismo criterio, que ya utiliza el Kardex.

`KardexMovementQuery` conserva la validación del producto habilitado y los almacenes autorizados del local. `InventoryCostService` conserva su alcance de empresa, incluyendo ambos extremos de los traslados internos. Por ello, un promedio de empresa no necesariamente coincide con el de un solo almacén. Las comparaciones de conciliación deben usar el mismo ámbito y fecha.

Una moneda histórica explícita distinta de la moneda base solicitada genera un error de revisión; no se mezcla ni se convierte automáticamente. Los reingresos sin costo no se valoran como cero.

La utilidad de las ventas resta el costo exacto registrado por las unidades devueltas, en lugar de repartir proporcionalmente un costo promedio entre lotes de costos diferentes. Los históricos aplicados sin evidencia verificable muestran costo/margen pendiente de revisión; no se inventa una ganancia.

## Históricos: diagnóstico y aplicación explícita

La migración no reconstruye notas automáticamente. Primero usar `database/sql/diagnostico_devoluciones_nc.sql`, con ambos IDs correctos. Sus consultas son de lectura; los `SET` solo asignan variables de la conexión.

Para una nota interna concreta:

```bat
php artisan inventory:credit-note-returns --tenant=1 --company=1 --currency=PEN --note=15
```

Sin `--apply`, muestra el plan y no guarda datos. Se pueden repetir opciones `--note=15 --note=16`, hasta 100 IDs concretos; el comando los ordena. No existe una opción que abarque todos los tenants ni una reconstrucción global implícita.

El proceso exige nota parcial aceptada, reversión física aplicada con fecha, venta/local de la empresa solicitada, vínculos a salidas y lotes, cantidades y costo históricos. Los serializados requieren la selección explícita del ítem y su salida. Las devoluciones normales de la versión anterior de este paquete de notas se reconstruyen mediante su distribución FIFO verificable. Para devoluciones legacy sin ese contrato, solo se admite un único lote inequívoco. Una distribución antigua ambigua, ítems duplicados, referencias ausentes, fechas incompatibles o un registro parcial inconsistente se dejan para conciliación.

Después de revisar el diagnóstico y confirmar su moneda/distribución:

```bat
php artisan inventory:credit-note-returns --tenant=1 --company=1 --currency=PEN --note=15 --apply
```

`--apply` guarda únicamente las filas históricas y su marca de finalización, con la fecha original de aplicación. No aumenta lotes, no cambia ítems o niveles, no modifica saldos de caja, no crea egresos y no cambia estado fiscal ni importes de la nota. Cada nota se procesa en su propia transacción. Una repetición reconoce la evidencia ya registrada y no la duplica.

Las notas antiguas pendientes o rechazadas que ya hubieran movido stock requieren conciliación fiscal/operativa; el comando no las convierte en aceptadas. Si hay devoluciones físicas aplicadas sin evidencia, el Kardex y costo del producto correspondiente avisan antes de presentar una valorización incompleta. Los candidatos de otra empresa o tenant no se muestran ni modifican desde el contexto solicitado.

## Inventario funcional antes/después

| Superficie/contrato | Antes | Después |
| --- | --- | --- |
| GET `/dashboard/kardex` | Producto/variante y almacén autorizados; permiso `list_kardex` | Mismos controles y permiso. |
| GET `/dashboard/kardex/{materialId}` | El parámetro histórico identifica StockItem; filtro `warehouse_id` y filas IN/OUT | Mismo método, ruta, parámetro, filtro y campos; incorpora entradas con `source_type=credit_note_return`. |
| Origen del Kardex | Entradas, salidas y traslados | Añade «Devolución · NC #ID». Misma tabla, selectores, eventos, paginación existente y estados de carga/error. |
| Emisión/consulta de NC parcial | `SaleListGuard`, permiso de anulación, CSRF y cabeceras de contexto | Mismos endpoints y payloads; la aplicación aceptada agrega evidencia en su transacción. |
| Caja y deuda | Reducción de deuda y devolución por canal propio acordados | Conserva ese contrato; rollback conjunto si falla la operación de inventario. |
| POS V1/V2 | Consumo compartido por `PosInventoryService` | Mantiene el consumo y añade moneda interna a la salida. Formularios y handlers conservados. |
| Lectores de costo | Fuentes separadas para compras/salidas frente a traslados | Fuente común con devoluciones y traslados; costo histórico de egresos y orden coherentes con Kardex. |
| Utilidad | Costo devuelto proporcional a la línea | Costo exacto de los lotes devueltos cuando hay evidencia. |
| Reconstrucción histórica | No había procedimiento específico | Nuevo comando de consola con diagnóstico por defecto y aplicación explícita solo de evidencia. |

Clasificación visual: reutilización del patrón actual. La modificación de frontend es la etiqueta de origen; no se modifica CSS, markup, IDs, selectores ni diseño de la pantalla. No se recuperan módulos legacy ni se reabren cierres J/P o K del auditor.

## Backend Sync Notes

[RESOLVED]
Point: trazabilidad de reingreso y lectura de costos de NC parciales.
Final contract: registro único por detalle/salida, costo original, aplicación al aceptar SUNAT y transacción común; lectores de Kardex/costos comparten movimientos.
Date: 25/11/2026, paquete `kardex-devoluciones-nc`.

[OPEN]
Point: homologación visual/HTTP en el ERP real de pruebas y revisión de históricos de la empresa.
Impact: los fixtures no certifican los datos reales ni una emisión efectiva a Nubefact.
What remains: instalar/migrar, ejecutar diagnóstico si existen candidatos, completar una devolución de prueba y verificar el Kardex del mismo almacén.
Frontend can continue: Yes.

## Verificación

Se conserva la evidencia de regresión del paquete anterior y se agregan pruebas de ledger, costo original distinto a precio/costo actual, varios lotes, utilidad exacta, corte por fecha, rollback de evidencia/stock/caja, serializados, reconstrucción sin duplicar físico, ámbitos por empresa/local, preservación de traslados y comando real de consola.

Se ejecutan también en una instancia privada MySQL 8, con migración e índices reales y proveedor sustituido. Dos procesos ejecutan la misma solicitud de NC simultáneamente y se verifica una sola llamada al proveedor simulado, una nota, un reingreso, un egreso y un aumento físico. Una prueba DECIMAL comprueba que una cantidad que se redondearía en el lote no se emita al proveedor. Consultar el conteo y resultado final en `VERIFICACION.json`.

Las pruebas no cargan el bootstrap ni la base del ERP y no llaman a Nubefact real. La instancia privada queda detenida al finalizar. Estos resultados son evidencia técnica privada, no un cierre de QA de emisión productiva ni de conciliación completa de los históricos reales.
