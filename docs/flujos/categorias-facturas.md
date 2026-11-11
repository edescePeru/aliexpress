# Categorías de facturas por empresa

Cada categoría pertenece al tenant y a la empresa. Las sucursales de la misma empresa comparten su catálogo. El listado, el AJAX, la edición y la eliminación filtran por el contexto activo. La creación toma ambos identificadores del servidor. Se reutilizan los permisos de categorías de facturas.

Los formularios de factura usan las categorías de la empresa. Al guardar se valida la categoría y se vuelve a comprobar dentro de la transacción. Las categorías asociadas a facturas se conservan. La relación histórica permite leer categorías que se eliminaron lógicamente antes del cambio.

La migración usa las facturas existentes para asignar cada categoría. Si hay referencias de varias empresas, conserva el ID original en una y crea copias para las demás, ajustando sus referencias. Las categorías sin uso se asignan cuando existe una única empresa identificable en el historial clasificado. En los casos ambiguos se conservan sin asignar y quedan fuera de los listados operativos hasta su revisión.

Este bloque cubre las categorías y su selección en facturas. El resto del módulo de Finanzas requiere su propia revisión de alcance.
