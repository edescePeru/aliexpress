# PROMO-01 — Retiro autorizado

19/11/2026. FOLLOW-01 verificado aplicado: 17 archivos coinciden, diez rutas retiradas, ruta de orden conservada, pruebas aisladas pasan. Validación de navegador/scheduler sigue pendiente.

Usuario decide retirar promociones legacy y conservar sus datos, revisando cotizaciones. Se retiran 18 rutas, tres controladores y enlaces. Modelos PromotionLimit, SeasonalPromotion, PromotionOrder y PromotionUsage se vuelven inactivos para consultas/escrituras habituales, conservando tablas. No se retiran combos ni contratos independientes de descuentos por cantidad.

Middleware de QuoteSale y eventos de EquipmentConsumable impiden nuevos marcadores/consumos/copias promocionales. Se conserva historia sin recalcular: una renovación histórica marcada puede quedar bloqueada y requiere revisión, no conversión automática. No se certifican precios manuales ni emisión de históricos por retirar el motor.

Local: las cuatro tablas promocionales tienen cero registros, lo que no acredita producción. Casos positivos deben probarse con fixtures. Pendientes instalación, QA HTTP/transacciones/emisión histórica y menús. PROMO-01 parcial hasta verificar retiro.

Verificación ejecutada: sintaxis de 13 PHP, rutas completas y constructor QuoteSale ensamblados; dos vistas compiladas con directivas reales y JS comprobado. Pruebas aisladas SQLite de modelos inactivos, eventos de guardado/borrado, builders con scope sin efecto, marcadores neutrales/promocionales, middleware con JSON anidado y rollback de copia marcada dentro de transacción; importes/marcadores históricos conservados. Se registraron 18 rutas y los adaptadores no hicieron consultas. No se ejecutó emisión ni flujo comercial en ERP. Pendientes instalación, HTTP/roles, renovación y emisión histórica completas, y QA de combos/precios independientes.

