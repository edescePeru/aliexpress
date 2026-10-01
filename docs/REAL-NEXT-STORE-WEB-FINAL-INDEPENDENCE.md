# REAL NEXT 9.6 — Store Web Final Independence & Cleanup Audit

Auditoría: 2026-09-29. Alcance: independencia de archivos y regresión del estado temporal actual. No se implementan contratos públicos, no se modifica backend comercial y no se recompila CSS.

**STORE WEB INDEPENDENCE VERIFIED — PROJECT-CATALOG SAFE TO DELETE**

## Runtime references

**RUNTIME REFERENCES TO project-catalog: 0**

Búsqueda de texto en Blade, PHP de presentación, JS, CSS, Sass, middleware, configuración, rutas, bootstrap, package/composer, webpack/Mix, manifests y archivos de tests. La búsqueda general del checkout excluyó dependencias instaladas, logs, Git y documentación histórica; una segunda búsqueda cubrió los directorios runtime/build explícitamente. No se encontró un include, import, URL, loader ni dependencia de build hacia la carpeta temporal.

La única ruta de carga fija restante estaba en el servidor **opcional de comparación histórica**, `tests/StoreWebPreview/router.php`. Se eliminó ese acoplamiento: ahora `/reference/*` requiere `STORE_WEB_QA_REFERENCE=1` y un directorio existente suministrado explícitamente por `STORE_WEB_QA_REFERENCE_ROOT`. Sin ambos, no sirve referencia. No es una ruta Laravel ni un recurso del build. Las herramientas de comparación de píxeles necesitan una referencia externa archivada si se quieren volver a ejecutar después de eliminar la carpeta; las pruebas de aplicación y regresión no la necesitan.

Las menciones restantes en tests son **aserciones negativas**, el scanner de independencia y el procedimiento autorizado de renombrado/restauración. No cargan assets del origen temporal. Las menciones en manifests de procedencia, AGENTS y documentos históricos son evidencia, no dependencias runtime. No se ocultaron resultados cambiando el texto de las aserciones.

## Hashes

**54/54 assets migrados coinciden con su `finalHash` de 9.1.** Detalle por archivo: [independence-static-qa.json](store-web/independence-static-qa.json), contrastado con [asset-migration-manifest.json](store-web/asset-migration-manifest.json).

`public/store-web/css/style.css` conserva exactamente SHA-256:

```text
eb43009f04a0298356f56781b9c128c5969fdb4ef9333f7fde05a508b57e1431
```

No se compiló ni modificó CSS/Sass. `catalog-main.js` conserva el hash congelado posterior a la extracción del tester en 9.1. Los adaptadores agregados en 9.3–9.5 no formaban parte de las 54 copias iniciales y permanecen sin cambios en esta fase.

## Asset paths

| Recurso | Ubicación permanente |
| --- | --- |
| Bootstrap, Font Awesome, Elegant Icons, style.css | `public/store-web/css/` |
| Visual JS y adaptadores Home/Shop/Detail | `public/store-web/js/` |
| Montserrat, ElegantIcons, Font Awesome | `public/store-web/fonts/` |
| Imágenes del preview, logo y placeholder | `public/store-web/img/` |
| Fallback de producto | `public/store-web/img/no_image.png` |
| Fuentes Sass preservadas | `resources/sass/store-web/` |
| Layout y partials | `resources/views/layouts/storeWeb.blade.php`, `resources/views/shop/partials/store-web/` |
| Datos explícitos de Home preview | `resources/store-web/preview/home.php` |
| Adaptador de presentación Detail | `resources/store-web/detail.php` |
| Uploads reales, según contratos actuales | `public/images/material/`, `public/images/material/variants/`, `public/images/logo/` |

Los `url(../fonts/...)` de CSS resuelven dentro de `public/store-web`. Mix sigue compilando solamente `resources/js/app.js` y `resources/sass/app.scss`; no consume el origen temporal ni el Sass Store Web. El Sass histórico del tester permanece preservado, sin recompilación ni ejecución; su exclusión de un build futuro sigue siendo una tarea explícita, no parte de esta auditoría.

## Removal simulation

Se utilizó `tests/StoreWebPreview/independence-simulation.ps1` desde el proyecto real. Comprueba las rutas absolutas dentro del proyecto, rechaza un destino existente y ejecuta:

1. Captura de hashes de todos los archivos de la referencia.
2. Renombrado temporal `project-catalog` → `project-catalog.__disabled`.
3. Pruebas PHP en procesos nuevos y navegación al Laravel normal en `http://127.0.0.1:8080`; sin recurrir al servidor aislado de referencia.
4. Regresión de las tres páginas y renderizado de respuestas autenticadas capturadas durante la ausencia.
5. Restauración mediante `finally`, incluso si falla una prueba, y comparación de cantidad/hashes.

La primera ejecución descubrió una espera faltante en el nuevo test: Escape se enviaba antes de completar la apertura/foco del menú. Se restauró la carpeta, se añadió la espera observable del foco/ARIA y se repitió la simulación. No se cambió el JS visual.

La ejecución final pasó entre 15:56:05 y 15:57:11 (America/Lima). Se restauraron los **56 archivos**, todos con los mismos hashes. La evidencia final del renombrado, horas, resultado y restauración está en [independence-removal-simulation.json](store-web/independence-removal-simulation.json). **No hubo eliminación definitiva.**

## Three-view regression

| Página | Preview anónimo local | Sesión Gamarra | Sesión EDESCE |
| --- | --- | --- | --- |
| `/store-web/inicio` | Fixture explícito | Fixture explícito, sin binding editorial real | Fixture explícito, sin binding editorial real |
| `/store-web/catalogo` | Fixture, sin requests de negocio | Contrato real actual, bridge validado | Contrato real actual, bridge validado |
| `/store-web/product/{material}` | Fixture sin lookup del ID | Producto real del contexto | Producto real del contexto |

Home sigue **FRONTEND READY / BACKEND CONTRACT PENDING**. Autenticarse no lo transforma en una Home productiva ni se usa como fallback ante fallos del backend. El valor reservado `frontend-fixture` sigue siendo un preview identificado, incluso con sesión.

Pruebas PHP 7.3.33: **10 tests / 520 assertions PASS** mientras la referencia estaba ausente. Las pruebas reales usan usuarios/membresías existentes y sesiones autenticadas del kernel Laravel, dentro de transacciones SQL **READ ONLY**, con rollback; no crean credenciales, registros ni stock. Se prueban Gamarra (1 producto) y EDESCE (11), filtros, páginas, settings, detalle, rechazo de IDs ajenos e ignorar tenant/company enviados por query.

El navegador autentica **ninguna sesión artificial**: para las variantes reales reproduce HTML/JSON recién obtenidos de esas sesiones de kernel y carga los assets permanentes desde el Laravel normal. Se diferencia así la prueba efectiva de middleware/controller de la prueba de render responsive; no se afirma un login manual en navegador.

Evidencias: [independence-phpunit.xml](store-web/independence-phpunit.xml), [independence-fixture-qa.json](store-web/independence-fixture-qa.json), [independence-responsive-qa.json](store-web/independence-responsive-qa.json).

Los uploads ausentes de EDESCE siguen siendo **data quality**. La auditoría anterior identificó 22 rutas de detalle ausentes; esta fase no cambia sus registros ni los rellena con mocks. Sus requests pueden devolver 404 y luego mostrar el fallback permanente. No se afirma cero errores de red para esos uploads; los assets permanentes de preview responden correctamente.

## Responsive matrix

Cada fila comprende las tres páginas anónimas y las tres páginas en cada uno de los dos contextos autenticados: **30 vistas fixture + 60 renderizados de sesiones autenticadas**.

| Ancho | Home | Shop | Detail | Home/Shop bottom nav | Detail CTA | Summary sticky |
| ---: | --- | --- | --- | --- | --- | --- |
| 320 | PASS | PASS | PASS | Visible | Fijo móvil | No |
| 375 | PASS | PASS | PASS | Visible | Fijo móvil | No |
| 390 | PASS | PASS | PASS | Visible | Fijo móvil | No |
| 576 | PASS | PASS | PASS | Visible | Fijo móvil | No |
| 767 | PASS | PASS | PASS | Visible | Fijo móvil | No |
| 768 | PASS | PASS | PASS | Oculto | Inline | No |
| 991 | PASS | PASS | PASS | Oculto | Inline | No |
| 992 | PASS | PASS | PASS | Oculto | Inline | Sí |
| 1200 | PASS | PASS | PASS | Oculto | Inline | Sí |
| 1440 | PASS | PASS | PASS | Oculto | Inline | Sí |

Menú/foco/Escape, bottom nav, drawer móvil/desktop y cierre, galería/contador/imagen activa, CTA móvil e inline, fuentes e imágenes con fallback: comprobados. Cero overflow horizontal y cero excepciones JS. El preview anónimo comprueba además consola y ausencia de XHR/fetch de negocio. Scroll-snap de Home se registra mediante estilo computado; las reglas de Home y relacionados fixture, junto con safe-area, permanecen idénticas por hash. No se certifican gestos ni insets de un dispositivo físico: Edge emulado tiene inset físico cero.

## Legacy consumers

**Intactos; no eliminados ni reactivados.** Los 116 archivos protegidos se compararon con HEAD admitiendo únicamente normalización CRLF/LF; sus hashes actuales quedan en `independence-static-qa.json`.

- `/store-web/tienda` conserva ruta `store-web.tienda`, acción `StoreWebController::tienda` y Blade `shop.catalogNoPrice`. El guard actual continúa devolviendo 404: “intacto” no significa acceso público reabierto.
- `resources/views/shop/catalogNoPrice.blade.php` y `public/js/shop/catalogNoPrice.js` preservados.
- `resources/views/shop/detailCatalogNotPrice.blade.php` preservado; no se inventa una nueva ruta para probarlo.
- Todo `public/shop/` preservado.
- `resources/views/layouts/appShop.blade.php` preservado.
- StoreWebController, rutas comerciales, TenantScope y webpack/Mix sin cambios.

## Fixture inventory

Inventario exacto de cada imagen, clasificación y decisión de movimiento: `fixtureImages` en [independence-static-qa.json](store-web/independence-static-qa.json).

| Fixture/recurso restante | Clasificación | Decisión |
| --- | --- | --- |
| `resources/store-web/preview/home.php` | Necesario para preview local: copy, banner, 4 destacados, 2 promociones | Conservar; ya está fuera de public |
| Rama fixture de `shop/catalog.blade.php` | Necesaria para QA visual: 9 cards/paginación de muestra | Conservar protegida |
| Rama fixture de `shop/detailCatalog.blade.php` y gallery | Necesaria para QA visual: resumen, atributos, descripción, características y 4 relacionados | Conservar protegida |
| Contenido fixture de drawer/partials | Necesario para comprobar diseño sin backend | Conservar; el modo real usa facets actuales o controles pendientes inertes |
| `img/product/product-1.jpg`…`product-6.jpg` (6) | Necesario para Home preview | Conservar en public/store-web |
| `img/shop/shop-1.jpg`…`shop-9.jpg` (9) | Necesario para Shop preview | Conservar en public/store-web |
| `img/product/details/product-1.jpg`…`product-4.jpg` + `thumb-1.jpg`…`thumb-4.jpg` (8) | Necesario para galería fixture | Conservar en public/store-web |
| `img/product/related/rp-1.jpg`…`rp-4.jpg` (4) | Necesario para relacionados fixture | Conservar en public/store-web |
| `img/no_image.png` | Fallback permanente, también usado con datos reales | Conservar; no es mock comercial |
| `img/logo.png` | Shell aprobado y fallback de logo | Conservar mientras siga vigente el contrato actual |
| Casos sintéticos en tests de Detail / respuestas controladas Shop | Necesarios para pruebas de bordes; nunca endpoints de negocio | Conservar fuera de public; no persistidos |
| JSON de QA, manifest y capturas | Solo evidencia visual/técnica | JSON en docs; capturas y snapshots en directorio local de tarea, fuera de public |
| Referencia completa temporal | Fuente histórica visual; ya no requerida por aplicación/build | Candidata a eliminación indicada abajo; comparación histórica necesita copia externa si se desea repetir |

No hay un fixture de los 27 JPG permanentes que pueda sacarse de `public/` ahora sin reemplazar su forma de servir el preview. Esa reorganización no se hace en esta fase. No se borró ningún fixture necesario. Los precios/promociones de muestra no aparecen como productos reales en Shop/Detail.

## JavaScript separation

- `catalog-main.js`: interacción visual congelada, sin lógica de consultas comerciales.
- `catalog-data.js`: contratos Shop; en fixture retorna antes del fetch; en real recibe productos/facets actuales y no recurre a arrays mock ante errores.
- `product-detail.js`: fallback y WhatsApp; requiere contrato real y `data-store-web-fixture=false`; no combina variantes ni realiza AJAX comercial.
- `home-preview.js`: navegación GET de Home; no consulta ni fabrica colecciones.

Las pruebas confirman que Shop/Detail reales no renderizan badges/cards/relacionados mock. Home permanece explícitamente fixture por contrato pendiente, no se presenta como modo real autenticado. No se modificó ninguno de estos JS de aplicación en 9.6.

## Security bridge

Producción, staging/testing y preview OFF permanecen bloqueados. Visitantes anónimos locales solo obtienen fixtures; APIs de datos quedan en 404. Datos reales exigen conjuntamente local + preview boolean true + usuario autenticado habilitado + tenant activo propio + company activa con membresía autorizada, antes del implicit binding. IDs de contexto vienen exclusivamente de la sesión existente, nunca de query/body. No se relajan guards, ni se cambian scopes o queries.

## Backend sync status

| Punto | Estado actual |
| --- | --- |
| SW-01 contexto público/aislamiento | BACKEND SYNC OPEN |
| SW-02 publicación/disponibilidad | BACKEND SYNC OPEN |
| SW-03 precio/moneda/flags | BACKEND SYNC OPEN |
| SW-04 filtros | BACKEND SYNC OPEN |
| SW-05 variantes/presentaciones/WhatsApp | BACKEND SYNC OPEN |
| SW-06 editorial/descripcion/características/relacionados | BACKEND SYNC OPEN |
| SW-07 branding/assets | BACKEND SYNC OPEN |

No hubo cambio backend que justifique cerrar un punto. La independencia de archivos no convierte el catálogo en publicable en producción.

## Licenses / notices

Presencia documental y hashes verificados:

- `public/store-web/fonts/Montserrat-OFL.txt`: copyright Montserrat y texto SIL Open Font License.
- `docs/store-web/vendor/REFERENCE-NOTICE.txt`: procedencia/notice del template Colorlib, copiado del original.
- Manifest de migración, auditoría y contratos 9.1–9.5 permanecen en `docs/`.

Se verifica su presencia y conservación; no se emite conclusión legal sobre licencias o cumplimiento.

## Deletion readiness

**project-catalog puede eliminarse: YES.** Runtime/build no dependen de ella; las pruebas con el path ausente pasaron y la carpeta fue restaurada íntegra. Esta conclusión certifica independencia de archivos del estado local actual, no cierre de los pendientes backend.

La única carpeta que puede borrarse en el alcance es:

`C:\wamp64\www\venti-adminlte-ui\aliexpress\project-catalog\`

Comprende sus **56 archivos**: HTML de referencia, CSS, JS, Sass, fonts, img y notice original. El listado exacto con SHA-256 está en [independence-deletion-inventory.json](store-web/independence-deletion-inventory.json). Sus copias requeridas ya están en ubicaciones permanentes. **No** incluye `public/store-web/`, `resources/sass/store-web/`, `resources/store-web/`, Blades/partials, `docs/store-web/`, pruebas, uploads ni consumidores legacy.

No eliminar definitivamente durante esta fase. La carpeta se restaura al terminar; una eliminación posterior se limita al path exacto anterior. Los runners opcionales de comparación histórica necesitan referencia externa, no una copia escondida requerida por runtime.

## Changes in this phase

Solo tooling, pruebas y documentación: loader opcional de referencia configurable; comprobación explícita de Home en sesiones autenticadas; nuevos scripts `independence-static.cjs`, `independence-qa.cjs`, `independence-simulation.ps1`; README y evidencias de esta auditoría. Ningún cambio en Blade, assets públicos, CSS/Sass, controladores, middleware de aplicación, rutas o datos comerciales.
