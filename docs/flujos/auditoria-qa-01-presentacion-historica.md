# QA-01 — Presentación guardada en la venta

19/11/2026. Paquete anterior auditoria-qa-01-ganancias-detallado revisado: documentación instalada idéntica y suite completa aprobada nuevamente.

Hallazgo: resolvePresentacion consultaba únicamente material_presentation_id y su catálogo actual, sin usar presentation_label_snapshot, units_per_pack y packs, campos que PosSaleService registra. Una presentación modificada o una venta moderna sin FK podían mostrarse con otra etiqueta/cantidad o como UND.

Se entrega reemplazo de un método: priorizar snapshot histórico; usar Paquete si existe packs sin nombre; no inventar unidades faltantes; mantener fallback legacy condicionado a tenant/material coherentes. No cambia cálculos ni guarda datos. Las ventas legacy sin snapshot siguen sin evidencia de su presentación histórica y usan el catálogo actual como compatibilidad.

Prueba aislada con SaleDetail y MaterialPresentation reales, relaciones precargadas y sin base: catálogo modificado, FK ausente, paquete sin nombre, unidades ausentes, nombre sin packs, UND, legacy válido, tenant/material incompatible y relación ausente. Estado: corrección preparada y prueba aprobada; instalación y archivo real pendientes.

QA-01 sigue parcialmente atendido. resolveFormaPago aún selecciona un solo movimiento: requiere revisar pagos mixtos y tipos de movimientos antes de cambiar su contrato. También pendientes redondeos, autorización/descarga HTTP, Daily Profit y Finance. No se declara la exportación completamente certificada.
