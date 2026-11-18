# Créditos de proveedor — Permisos de acción

La ubicación de ScopedSupplierCreditCompany y ScopedSupplierCreditPayment fue corregida a app/Traits. El siguiente paquete protege las 20 rutas operativas del controlador con permisos existentes y repite la protección por acción en su middleware.

El cambio manual de estado incorpora permisos por transición y validación de valores. No se usa edit_credit para permitir registrar o revertir pagos. Se conserva la semántica legacy de state_pay, sin inferir conciliación monetaria.

Estado: preparado para instalación y QA HTTP. Afecta indicadores del dashboard y botones legacy que deberán respetar control_credit y permisos de acción. No se considera cerrado el flujo de créditos ni PUR-01.

Validación local: sintaxis de controlador y bloque de rutas aprobada; pruebas SQLite en memoria de permisos de transición y registro de middleware aprobadas. La matriz HTTP de las 20 rutas queda pendiente con roles reales.
