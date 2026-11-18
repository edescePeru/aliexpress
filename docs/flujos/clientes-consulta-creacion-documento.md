# Consulta y creación de clientes por documento

El GET antiguo conserva su nombre pero ahora solo busca y consulta. La creación se expone mediante POST con create_customer, CSRF y contexto validado. El POST requiere un documento de 8 u 11 dígitos y usa la información consultada para guardar un cliente con código. Un bloqueo del tenant coordina dos altas por esta operación y el índice único de clientes mantiene la unicidad por tenant.

El endpoint customer/decolecta que usan los formularios actuales conserva su comportamiento de consulta. Las rutas de pruebas de proveedor se revisan en CUSTOMER-02.
