# INV-01 — Lookups del Kardex

19/11/2026. items-scope aplicado: controlador, servicio y documentación idénticos, regresiones aisladas aprobadas.

KardexLookup centraliza los dos selectores, exige list_kardex y contexto operativo por sí mismo. El catálogo reutiliza StockItemScreenAccess para evitar referencias de material ajenas/borradas y productos no habilitados. Mantiene productos activos, límite y contrato id/text. El catálogo es de empresa, no una promesa de existencia o disponibilidad del almacén. Los almacenes siguen siendo los del local y compartidos autorizados.

MaterialController delega con dos métodos completos en TXT. Sin rutas/SQL ni modificación de stocks, movimientos o precios. Historia de entradas y selección operativa conservan sus contratos propios.

Pendientes: StockItemController (permisos, referencias de variantes/atributos y lecturas de inventario), definición real de inventory_movements_view, pruebas HTTP/roles/MySQL positivo y conciliación histórica. INV-01 parcial.

Verificación ejecutada: sintaxis del servicio y de los dos métodos TXT dentro de una clase de comprobación; regresiones anteriores más pruebas SQLite de ambos selectores (permisos, búsqueda, límites, orden y referencias inválidas). Ejecución de ambas consultas y delegación JSON en MySQL local con autorización simulada, sin mostrar registros ni modificar datos. Esto valida SQL/contrato, no acceso HTTP real ni datos positivos de cada rol. Pendiente QA de pantalla y permisos reales.

