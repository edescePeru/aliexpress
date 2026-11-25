# Materiales y formularios de compra — alcance del paquete

## Decisión de retacería y límite del maestro compartido

El usuario aprobó conservar todas las piezas existentes al cambiar el tipo de retacería, aplicándolo solo a futuros ingresos y sin afectar otras empresas/tenants. Se elimina la propagación que actualizaba `Item` en estados entered/exited. No se cambian medidas, tipo, stock, reservas ni costos de piezas existentes.

`Material.typescrap_id` pertenece al maestro de tenant, no a una configuración por empresa. Por ello, si cambia el valor, `MaterialRetaceriaPolicy` comprueba pertenencia al tenant y habilitación para la empresa actual, tipo de retacería del mismo tenant y ausencia de vínculos con otras empresas (también vínculos inactivos) o historia sin atribución exclusiva. En esos casos el cambio se rechaza antes de guardar. No se crean overrides por empresa ni se clona el maestro unilateralmente. El material ya está bloqueado por la transacción de update; el rechazo revierte los cambios del formulario.

Para un material exclusivo, se actualiza el maestro para futuros ingresos. Todo `Item` previo conserva sus datos exactos. La política de otros atributos del maestro compartido, SKU y flags con stock/historia sigue siendo MAT-01: este paquete no redefine esos contratos ni declara el maestro completo como configuración aislada por empresa.

## MAT-04 / MAT-05

UpdateMaterialRequest compara el valor original de `is_active`, `afecto_inventario` y `pack` contra 0/1, sus cadenas y booleanos. Rechaza texto, arrays, null explícito y valores fuera del dominio antes de normalizar. La omisión conserva los defaults existentes; no se rediseña la política MAT-01.

La validación ya no considera repetido un almacén por tener dos ubicaciones: identifica cada InventoryLevel y su posición física. Permite dos ubicaciones válidas del mismo almacén, rechaza IDs/posiciones repetidos y valida producto, almacén, ubicación y contexto mediante MaterialInventoryScope. Si se envía location_id debe corresponder al nivel; no se mueve la posición. El servicio de guardado conserva la misma comprobación y solo modifica mínimos/máximos, con rollback del caller.

## C y consulta compartida de Q

PurchaseFormReadAccess define cuatro consumidores:

| Consumidor | Permiso |
|---|---|
| entry_create | create_entryPurchase |
| entry_edit | edit_entryPurchase |
| purchase_create | create_orderPurchaseNormal |
| purchase_edit | edit_orderPurchaseNormal |

El frontend indica el consumidor en la precarga, búsqueda y consulta de variantes. El backend exige la capacidad exacta y contexto de tenant/empresa/local activo con membresías autorizadas. Nombrar otro consumidor no concede su permiso. Los seis scripts que utilizan la precarga compartida se entregan juntos para conservar compatibilidad. Las búsquedas de entradas admiten solo sus dos consumidores y las de órdenes solo los suyos.

La precarga y búsquedas conservan sus contratos de respuesta; los productos requieren material del tenant y habilitación activa en la empresa. El stock se suma únicamente desde niveles coherentes de la empresa, almacenes generales o del local actual y ubicaciones compatibles; se excluyen otros locales/empresas/tenants. La búsqueda de órdenes usa el catálogo de empresa y la consulta de variantes valida el padre habilitado antes de responder. Se conservan precios y su resolución existente.

EntryController::editEntryPurchase resuelve primero un ingreso del tenant/empresa actuales, finance=false y entry_type=Por compra. Se rechazan detalles atribuidos a otro contexto y posiciones de sus piezas que no correspondan a almacén/ubicación de la empresa. La identidad histórica de la entrada sigue siendo por empresa: no se inventa un branch_id en la cabecera. No se convierten ni reparan referencias históricas automáticamente. Los destinos disponibles conservan EntryWarehouseResolver.

No hay cambios de sidebar, SQL ni migraciones. No se retiran Materiales, Entradas, Órdenes normales ni el reporte por materiales.

## Verificación privada

- Request y update reales en SQLite: flags válidos/inválidos, dos ubicaciones del mismo almacén, duplicados, ubicación manipulada y niveles de otro local/empresa/tenant.
- Cambio de retacería exclusivo, conservación byte a byte de filas Item, rechazo del maestro compartido y rollback ante fallo posterior que revierte maestro y niveles.
- Controladores reales de precarga, búsqueda y variantes, con catálogo/contexto reales. Se simula únicamente la resolución de precios para estas comprobaciones; no es certificación del cálculo de precios.
- GET sin escrituras, cuatro capacidades exactas, consumidores manipulados y contexto ausente/ajeno. User/Spatie/Gate reales para permisos directos/rol.
- Padres de entradas financieros/tipo ajeno/empresa ajena/tenant ajeno/inexistentes y referencias físicas incompatibles rechazados. Controlador Edit conserva vista y GET puro.
- Sintaxis PHP y JavaScript. Sin red ni base/archivos de negocio compartidos.

Pendiente tras instalación: HTTP/UI con usuarios reales, comprobación de formularios y compras/ingresos de prueba. No se certifica el ciclo completo de Store/Update de entradas/compras, concurrencia MySQL de todo el formulario, uploads de Material/Entradas ni todos los puntos Q. MAT-01 y otros puntos del auditor permanecen separados.
