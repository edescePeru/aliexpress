# Cupos de empresas y locales por tenant

## Regla aprobada

`plans.max_active_companies` limita las empresas activas del tenant; `plans.max_active_branches` limita todos sus locales activos, distribuidos libremente entre empresas. El principal cuenta. Los locales con `is_active = 1` continúan contando aunque su empresa esté inactiva. La relación `tenants.plan_id` permanece como fuente del plan; no hay tabla adicional ni copia de límites en tenants.

Los nuevos campos admiten NULL únicamente para la transición de planes anteriores: significa pendiente de configurar, nunca ilimitado. El mantenedor exige enteros entre 1 y 10000. La migración no asigna un número comercial arbitrario, no modifica usuarios, empresas, locales ni membresías.

## Escrituras protegidas

- TenantCompanyController::store: dentro de su transacción bloquea tenant, obtiene el plan actual con bloqueo compartido y exige un cupo de empresa y uno de local antes de crear. Conserva la provisión de inventario y membresías; un fallo revierte todo.
- TenantCompanyController::toggleStatus: bloqueo de tenant antes de la empresa. Al reactivar exige un cupo de empresa. No vuelve a sumar locales que ya contaban. Conserva las reglas de última empresa y contexto actual.
- TenantBranchController::store: bloqueo de tenant y comprobación del cupo total de locales; revalida la empresa activa bajo bloqueo antes de guardar. Conserva listas de precios y asignación al owner.
- TenantBranchController::toggleStatus: bloqueo de tenant antes de la sucursal. Reactivar exige cupo y empresa activa. Desactivar conserva las restricciones existentes de principal, último local y contexto actual.
- Platform/TenantController::store: el primer RUC y local pasan por el mismo control. Con un plan pendiente, el alta completa del tenant se revierte y devuelve un mensaje de negocio.
- Platform/TenantController::update: reasignación de plan bajo bloqueo de tenant y comprobación del nuevo plan activo bajo bloqueo compartido.
- PlanController: mantiene protección de Platform Admin, valida ambos máximos, registra valores anteriores/nuevos en auditoría y bloquea el plan durante su edición.

El orden de bloqueo es tenant → plan → empresa/local. Los consumidores de cupo de empresas/locales comparten el bloqueo de tenant. La edición del catálogo de planes toma bloqueo exclusivo de plan, por lo que no modifica el máximo durante una de estas operaciones.

## Reducciones y transición

Reducir un plan o asignar otro de menor capacidad conserva los registros existentes. Se muestra uso excedido y se rechazan las altas/reactivaciones que consuman un cupo sin disponibilidad. Editar datos descriptivos y desactivar según las reglas existentes continúa permitido. Cambiar un plan afecta a todos los tenants que lo utilizan.

No se cambia el límite de usuarios ni su contador: el owner sigue contando. Este paquete no certifica ni modifica la concurrencia de las altas/reactivaciones de usuarios, que es un flujo independiente. No modifica el login, el contexto, los dashboards, GRE ni las sesiones de caja. La restricción existente que impide desactivar el local principal se conserva.

## Interfaz

Planes agrega columnas y campos de empresas/locales; los valores antiguos aparecen como Pendiente. Las pantallas de listado/creación de empresas y locales del owner muestran el consumo global del tenant y los cupos restantes. El detalle administrativo del tenant muestra la misma capacidad. Los enlaces conservan permisos; el servidor vuelve a validar al confirmar, aunque la pantalla estuviera abierta antes de consumirse el cupo.

## Evidencia previa a instalación

Pruebas aisladas con controladores/modelos/validador reales sobre SQLite privado: altas, reactivaciones, aislamiento por tenant, principal, empresa inactiva, cupos nulos, reducciones, rollback completo ante fallo de inventario, validaciones de Planes, auditoría, middleware de plataforma, alta administrativa fallida y reasignación de plan.

Migración probada sobre un plan previo sin asignar máximos automáticamente. Se compiló y verificó la sintaxis de las ocho vistas y el JavaScript.

Prueba de concurrencia MySQL 8.0.31/InnoDB en base temporal independiente: dos altas de empresa compiten por el último cupo; dos locales de empresas distintas compiten por el último cupo de locales; una empresa con principal compite contra un local adicional. Cada escenario admite una sola operación y rechaza la otra, sin empresa parcial. Son pruebas del servicio y transacciones reales entre procesos, no del router HTTP ni del formulario instalado. La base temporal se elimina al terminar. No se migra ni escribe la base del ERP.

Pendiente después de instalar: ejecutar la migración real, configurar máximos comerciales y verificar visualmente las pantallas y mensajes con usuarios autorizados/no autorizados. No se declara cerrado el resto de la auditoría.
