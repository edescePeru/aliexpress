# Metas comerciales por empresa

La frecuencia semanal, quincenal o mensual se administra mediante sales.goals.frequency en Configuración de Empresa. La meta guarda su período y pertenece a un tenant y una empresa. La tabla meta_users registra las asignaciones. El monto es el objetivo individual de cada usuario asignado. El guardado bloquea la empresa y rechaza períodos solapados para el mismo usuario. Una asignación existente puede conservarse aunque el usuario se haya deshabilitado posteriormente.

Las metas históricas y meta_workers se conservan. El listado operativo solo incluye metas con contexto. El diagnóstico de solo lectura identifica candidatos a partir del vínculo Worker → User y la membresía de empresa. Los candidatos deben revisarse por meta antes de migrarlos, especialmente si apuntan a distintas empresas, si hay vínculos incompletos o si se solapan períodos.

El cálculo anterior por Worker se pausa en ranking y dashboard. Queda pendiente implementar el progreso por sales.created_by y definir la regla de moneda antes de sumar ventas.
