# QA-01 — Etiqueta de pagos mixtos

19/11/2026. Presentación histórica revisada: método instalado correcto, documento idéntico, prueba específica y suite completa de detallado aprobadas.

Hallazgo siguiente: resolveFormaPago tomaba solo el primer movimiento y asignaba Efectivo ante ausencia de una caja resoluble. Esto ocultaba otros canales en pagos mixtos y podía etiquetar referencias incompletas como efectivo.

Corrección preparada: recorrer movimientos originales sale/income asociados directamente a venta y mismo tenant/company/branch, ordenar por ID, excluir hijos de regularización y cancelaciones de diferidos, deduplicar nombres. Mostrar Mixto si hay más de un nombre distinto. Resolver subtipo y caja solo con referencias de empresa/tenant/caja coherentes; admitir caja compartida y sesión legacy compatible, sin exigir estado abierto. Sin datos compatibles mostrar N/D. No cambia cálculos ni registros.

Prueba con modelos reales y relaciones precargadas: dos canales, duplicados, egresos, regularización hija, cancelación, empresa/local/venta distintos, subtipo y caja ajenos, sesión legacy y ausencia de cobros. Se aprobó sin conectar a base. No certifica consultas lazy/eager, carga masiva, archivo XLSX ni HTTP. El método puede producir consultas por relación; optimización y prueba de descarga completa pendientes.

Límites: los nombres provienen del catálogo actual; dos canales distintos con igual nombre se presentan una sola vez. La etiqueta no expresa cantidades, estado de regularización, saldo o devolución. Pagos parciales sin sale_id directo y sus estados requieren revisión específica antes de declarar cobertura completa. La regularización tras cierre crea un hijo con sale_id nulo; su canal se identifica por el original, sin contar el hijo como otro cobro.

Estado QA-01: parcialmente atendido, cambio preparado pendiente de instalación. Siguen pendientes pagos parciales, redondeos, autorización/descarga XLSX real y familias Daily Profit/Finance. No se considera cerrado el hallazgo completo.
