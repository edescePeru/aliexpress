# PUR-01 — Reporte de entradas por compra

## Revisión del 17/11/2026

CUSTOMER-02 fue encontrado aplicado: no se encontraron test-ruc/test-dni ni sus acciones en las rutas y el controlador; existe su documentación. PHP validó la sintaxis de ambos archivos. Falta certificar el comportamiento HTTP y de caché del despliegue.

## Corrección preparada

EntryController::getJsonEntriesPurchaseReport tenía TenantScope indirecto mediante Entry, pero carecía del filtro de empresa. Su POST no declaraba list_entryPurchase, aunque la vista GET sí lo hacía.

El paquete exige el permiso en ambas acciones, exige tenant y empresa activos antes de consultar, aplica ambos filtros explícitos y valida las fechas. Conserva entry_type=Por compra, finance=false, created_at y la respuesta DataTables. No modifica el modelo Entry global ni los criterios de sucursal, anulaciones o fecha comercial.

## Seguimiento

PUR-01 permanece parcialmente atendido hasta revisar las compras financieras y el resto de consumidores legacy. Preparado para instalación manual, con pruebas de aislamiento y autorización pendientes. Mantener separado del cierre de compras normales y del futuro dashboard de Owner/Platform Admin.
