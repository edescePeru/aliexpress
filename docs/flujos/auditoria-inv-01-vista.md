# INV-01 — Uniones contextuales de fuentes ordinarias

19/11/2026. Listado aplicado completo: controlador, servicio y docs idénticos; pruebas aisladas pasan.

La definición MySQL local coincide con la migración previa 2026_09_17 en sus uniones/expressiones. Se prepara reemplazo de vista con igualdad tenant/empresa entre cabecera/detalle/lote, producto/material coherente y almacén/ubicación válidos. No cambia campos de la vista ni expresiones de cantidad/costo; referencias inválidas se excluyen, no se reparan.

Migración nueva depende de dos archivos SQL. Down restaura base anterior revisada; no representa copia de seguridad de un servidor con SQL personalizado. No se aplica migración sobre ERP durante preparación.

Pendientes: InventoryCostService aún tiene lectores sin frontera homogénea; cantidades de detalle repetidas por varios lotes; moneda/costo histórico según configuración actual; integridad de relaciones de traslados modernos; QA de migración y HTTP/roles/datasets. INV-01 parcial.

Verificación ejecutada: sintaxis de migración; ejecución aislada de SQL up/down en SQLite adaptando solo CREATE OR REPLACE y CONCAT, con referencias incompatibles y varias fuentes/contextos. Filas válidas conservan exactamente sus campos, cantidades y costos en el fixture. En MySQL local se ejecutó EXPLAIN y COUNT del SELECT propuesto sin reemplazar la vista: instalada=107 filas, propuesta=107 filas. No se ejecutó DDL/migración en ERP ni se certificó un servidor con definición/datos distintos. QA de despliegue, HTTP y conciliación siguen pendientes.

