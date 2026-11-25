# R8.2D — Branch & GRE Row Actions Visual Homologation

Fecha: 2026-11-25. Rama `feature/venti-next-frontend`; baseline compartido `6df57e7`. PHP 7.3.33; aplicación e infraestructura actuales preservadas.

## Observaciones originales y alcance

R8.1 documentó dos desviaciones visuales, no pérdidas funcionales:

- S328: `REAL-NEXT-R8-FUNCTIONAL-PARITY-AUDIT.md`, sección Company/Branch/ConfigUserWeb: `tenantBranch.index` conservaba Editar/Activar como botones individuales.
- S306: mismo informe, sección «GRE S305/306/307 - cierre de paridad»: el listado conservaba botones individuales por fila.

Se reutiliza la auditoría funcional existente. No se reaudita backend, ni se intervienen formularios, detalle, documentos, recuperación GRE o cupos. `frmGreRecovery` existe en el partial incluido por Show: no se duplica ni se agrega al listado.

## Patrón aplicado

Reutilización directa de Row Actions Dropdown de Component Patterns, Ventas, Compra Normal y Marcas: `.next-row-actions`, trigger compacto con `fa-ellipsis-h`, `.next-row-actions-menu`, `.dropdown-item`, Font Awesome y `.next-row-action-item-icon`. El portal compartido de `public/js/layout/admin2.js` mantiene el menú fuera del contenedor con scroll de la tabla. No se modifica ese componente.

Dos o más acciones visibles: menú único. Una sola: botón directo compacto, con icono y texto; no menú innecesario. Sin acciones autorizadas en Branch: no trigger. Los gates originales de cada acción siguen siendo la autoridad. La comprobación adicional de ambos permisos sólo elige el contenedor visual.

| Superficie | Acción / orden conservado | Icono | Contrato preservado |
|---|---|---|---|
| Branch | Editar | `fa-pen` | `edit_branch`, `tenantBranch.edit`, ID original |
| Branch | Desactivar / Activar | `fa-ban` / `fa-check` | `enable_branch`, todos los `data-*`, POST toggleStatus, CSRF y confirmaciones; desactivar usa `next-row-action-danger` |
| GRE | Ver | `fa-eye` | showView con ID original; también es la única acción ante identity_conflict |
| GRE | Consultar | `fa-sync-alt` | greCanConsult y condiciones originales de status/emission_state; `.btnConsultar`, `data-id`, POST consult, CSRF |
| GRE | PDF | `fa-file-pdf` | ACCEPTED, documents_available.pdf y greCanPdf; lector autorizado |
| GRE | XML | `fa-file-code` | ACCEPTED, documents_available.xml y greCanDocuments; lector autorizado |
| GRE | CDR | `fa-file-archive` | ACCEPTED, documents_available.cdr y greCanDocuments; lector autorizado |

Texto, orden, URLs, target/rel y condiciones conservados. GRE sólo modifica representación en buildActions y añade presentActions; handlers, filtros, paginación y mensajes permanecen intactos. Branch JS sin cambios: principal activa sigue protegida; activar/desactivar mantiene sus confirmaciones y tratamiento de errores/cupos DEV. No botones históricos recuperados.

## Evidencia focalizada

Render PHP de ambos listados con Owner EDESCE, tenant 1/company 1/branch 1; contexto inicializado y permisos de listado comprobados, DB en transacción READ ONLY con rollback. Dos sucursales existentes (principal y outlet), cuatro acciones comparadas antes/después: misma etiqueta, tag, href y atributos no visuales. Esto es render autorizado aislado; no nueva certificación HTTP de ownership.

Chrome headless aislado, sin escritorio/navegador del propietario. Todas las solicitudes interceptadas; assets locales reales y HTML generado por las vistas reales. GRE usa tres filas **SIMULATED** (990001–990003), nunca persistidas: aceptada con PDF/XML/CDR, pendiente con Consultar, conflicto con sólo Ver.

- 960 combinaciones SIMULATED del renderer GRE: 8 combinaciones de permisos × 5 estados × 6 fases de emisión × conflicto sí/no × documentos sí/no. Acciones anteriores y posteriores equivalentes en tag, texto, URL, target/rel, data-id y clase del handler. Todas las acciones poseen icono.
- 10 combinaciones responsive: ambos listados en **375, 768, 1440, 1920 y 2560 px**. Sin overflow global; tabla conserva scroll local. Menús dentro del viewport, sin clipping, iconos/textos alineados.
- Apertura, Escape y click fuera: PASS. Portal compartido utilizado sin JS adicional de posicionamiento.
- Consulta GRE pendiente: handler original envía POST al ID simulado 990002, con CSRF; interceptado y respondido 422 antes del servidor. Cero consultas reales a proveedor.
- Branch principal: aviso original «La sucursal principal no puede ser desactivada», sin POST. No se ejecuta activación/desactivación ni se certifica su persistencia.
- Sin pageerrors. No se abren documentos ni se ejecutan emisión, reenvío, recuperación o exports.
- `node --check public/js/shipping_guides/index.js`: PASS.
- PHP 7.3.33 `artisan view:cache`: PASS.
- `git diff --check`: PASS.

Artefactos externos: `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/`, archivos `r82d-read.php`, `r82d-browser.cjs`, `r82d-evidence.json`, HTML antes/después de Branch, renderer GRE anterior y capturas `r82d-{branch|gre}-{ancho}.png`. Fixtures y arneses separados del runtime productivo.

## Archivos y límites

Runtime: `resources/views/tenantBranch/index.blade.php` y `public/js/shipping_guides/index.js`. Documentación: este informe, inventario semántico y plan R8.

Backend 0 / Blade 1 / JS 1 / CSS 0 / allowlist 0 / permisos 0 / rutas 0 / infraestructura 0. Las dos superficies ya estaban allowlisted. No se modifica `tenantBranch/index.js` ni `shipping_guides/index.blade.php`.

Mutaciones comerciales reales 0; llamadas a proveedores 0; capacidades recuperadas/perdidas 0/0; nuevos findings DEV 0. QA de mutaciones y certificaciones backend conserva sus límites anteriores.

## Resultado

**R8.2D COMPLETE — FUNCTIONAL / VISUAL PARITY PASS; 2/2 superficies revisadas.** R8.2A–D completadas dentro de su alcance autorizado. Las observaciones visuales Branch/GRE quedan resueltas; cohorte B histórica 2/2 homologada. No equivale a cierre global R8: permanecen los blockers/contratos y decisiones de alcance documentados, sin reabrir universo C.

**76 recuperadas + 11 bloqueadas + 23 excluidas = 110**, sin nueva recuperación. Sin commit, push ni merge. R8.3 no iniciada; pendiente revisión del propietario.

## Revisión manual

- http://127.0.0.1:8080/dashboard/configuracion/empresas/1/sucursales
- http://127.0.0.1:8080/dashboard/shipping-guides/listado
