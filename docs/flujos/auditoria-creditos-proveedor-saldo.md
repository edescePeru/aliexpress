# Fuente única de saldo — Créditos de proveedor

18/11/2026: el usuario confirma historial de abonos como fuente única; advance requiere revisión antes de cualquier migración. No se autoriza sumar ambas fuentes ni fabricar pagos.

Se incorpora SupplierCreditBalance para listado, resumen y Excel. El resumen deja de excluir créditos solo por state_pay=canceled y de restar advance. Las marcas manuales no cambian el saldo calculado por dinero registrado. Se advierte sobre adelantos históricos no conciliados, moneda ambigua y saldos negativos.

No migrar, poner a cero ni eliminar adelantos para simular resolución. La conciliación histórica es un trabajo separado con evidencia. Tampoco se certifica privacidad documental ni concurrencia monetaria completa. PUR-01 permanece parcialmente atendido.

Validación: PHP lint y sintaxis JavaScript aprobados. SQLite en memoria confirma fuente única sin advance ni marcas, suma separada PEN/USD, coincidencia listado/resumen, aviso de legacy, exceso visible y rechazo de moneda/contexto ambiguos. La descarga Excel y la interfaz/HTTP reales quedan pendientes.
