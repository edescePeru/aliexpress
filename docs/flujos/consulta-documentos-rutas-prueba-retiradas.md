# Retiro de endpoints de prueba Decolecta

CUSTOMER-02 retira test-ruc y test-dni, declarados fuera del grupo autenticado, y sus acciones de controlador. El código operativo consulta por sus rutas existentes de dashboard. La verificación del despliegue requiere limpiar la caché de rutas y probar las URLs antiguas con un proveedor simulado, debido a las rutas públicas de catálogo por slug.
