# REAL NEXT — REMAINING SCREEN READINESS

2026-11-18 · HEAD `80cb50d` · descubrimiento estático y conteos DB read-only. Sin integración, QA profundo, mutaciones ni commit.

## Backend Delta Since Last Review

Revisados `git log --oneline --decorate -20` y `git log --oneline 5fb180d..HEAD -- app routes database`: **sin delta backend posterior al review de 5fb180d**. Los commits posteriores integran frontend. Se conserva el cierre de las superficies indicado por el usuario; no se reauditan blockers conocidos.

Kardex ya integrado: **HTTP SMOKE PENDING AFTER CACHE REFRESH**, según la última evidencia de `REAL-NEXT-POST-MERGE-DEV-FIX-REVIEW.md`. Esta fase no refresca caché ni repite QA.

## Remaining Screen Inventory

| Superficie restante | Evidencia mínima | Clasificación |
|---|---|---|
| Catálogos de guías de plataforma | ShippingCatalogController; `platform.shippingCatalogs.index`; navegación appAdmin2; cinco catálogos globales explícitos; platform.admin; GET sólo lectura | READY FOR FRONTEND |
| Catálogo de productos por empresa | CompanyStockItemController; `companyStockItem.index`; navegación bajo manage_companyStockItem; tenant y company autorizada; GET sólo lectura | READY FOR FRONTEND |
| SeasonalPromotion / PromotionLimit / PromotionOrder | Sin delta sobre globalidad operativa y GET mutador ya identificados | WAIT FOR DEV |
| SpecialBonus / Refund | Sin camino moderno contextual identificado; mantienen contrato personal/global previamente auditado | WAIT FOR DEV |
| WorkingDay / Regime | Lookups/parents globales y ausencia de autorización granular; no son batch seguro | WAIT FOR DEV |
| Role / Permission legacy | No sustituyen Platform Admin integrado; acceso global legacy | WAIT FOR DEV |
| WorkerAccount / Discount / Loan | Binding/modelos globales sobre datos personales/monetarios; no confundir con Supplier Accounts | WAIT FOR DEV |
| Otras familias registradas: reportes, nómina, compras/quotes legacy | Sin read model contextual nuevo confirmado; desglose cuantitativo abajo; no se promueven por tener ruta | NEEDS VERY SHORT AUDIT |
| Índices anteriores sustituidos por V2 y material.variants | No constituyen nueva fase de frontend | SKIP / OBSOLETE |

## Ready Now

### Catálogos de guías de plataforma

- GET `/dashboard/plataforma/catalogos-guias/{catalog?}` → `platformShippingCatalogs.index`. POST del mismo prefijo y PUT `/{catalog}/{id}` existentes. Middleware auth, usuario habilitado, password.changed y **platform.admin**; Platform Admin no requiere TenantContext.
- Catálogos permitidos por mapa cerrado: transporte, motivos, documentos, peso, indicadores. Clave inválida:404. GET ordena sort_order/code sin escrituras ni llamada Nubefact. `supportedCodes` consulta constantes del servicio, no proveedor remoto.
- Global de plataforma por diseño. PUT resuelve modelo del catálogo y `findOrFail`; campos name/sort_order/is_active. Código no editable. POST sólo motivos/indicadores, códigos soportados y unicidad validados. No delete. No hay binding tenant que simular.
- Dataset actual: transporte2, motivos13, documentos9, peso2, indicadores3 = **29 registros**. Una vista fuera de allowlist. Preservar contratos fiscales/logísticos, no cambiar valores en QA.
- Es una superficie distinta de ShippingGuide list/detail integrados y Create/Store bloqueados por GUIDE-01/02.

### Catálogo de productos por empresa

- Ruta real conserva prefijo duplicado: GET `/dashboard/dashboard/company-stock-items`, GET `/dashboard/dashboard/company-stock-items/data`, POST `/dashboard/dashboard/company-stock-items/status`; nombres companyStockItem.index/data/status.
- Middleware tenant.context y `permission:manage_companyStockItem` en las tres rutas. Owner: companies activas de su tenant; otros usuarios: pivot autorizado y activo. `resolveAllowedCompany` aplica esa autorización también a datos y mutación.
- StockItem, CompanyStockItem, Brand y Category usan scope tenant. Query ofrece SKUs activos del tenant para habilitación por company, deliberadamente incluyendo los aún no habilitados. Estado consultado por company y página de SKUs; no es disponibilidad física ni stock por branch.
- Filtros search/brand/category/status y paginación20. GET sin escrituras. POST existente verifica company autorizada, StockItem del tenant y coincidencia de tenant antes de guardar. No se ejecutó. Branch N/A.
- Dataset actual: **49 StockItems; 63 CompanyStockItems**: T1/C1=47, T1/C2=14, T3/C3=2. No interpretar total49 como dataset uniforme por tenant.
- Integración futura conserva selector y toggle existentes, sin habilitar/deshabilitar productos durante QA. Distinta de StockItems, Material List y Branch Pricing ya cerrados.

Escala1–5: Backend/Value/Dataset altos favorecen; Effort/Risk altos dificultan. Es valoración de readiness estática, no certificación funcional.

| Candidate | Backend | Value | Effort | Risk | Dataset |
|---|---:|---:|---:|---:|---:|
| Catálogos de guías de plataforma | 5 | 3 | 2 | 2 | 5 |
| Catálogo de productos por empresa | 4 | 5 | 3 | 2 | 5 |

## Read-only Ready

Ningún candidato adicional confirmado como **READY FOR READ-ONLY FRONTEND**. No volver a ofrecer Sales List, reportes integrados, compras V2, Supplier Credit o Kardex como nuevas pantallas. No hay candidato adicional confirmado **FRONTEND WITH TECH DEBT** que justifique una tercera fase.

## Best Batch

**Cinco catálogos GRE de plataforma**, misma autoridad, controller, vista y reglas de mantenimiento. Una fase y una Blade; no son cinco CRUD completos: sólo dos admiten alta, todos tienen actualización y ninguno delete. No se encontró batch adicional de tres CRUD completos seguros.

## Wait for Dev

Se heredan los blockers ya decididos: GUIDE-01/02 y failure contract; ReferralGuide; Material save/separate pack; Expense; OrderService; Outputs; Fixed Assets; estructura Warehouse legacy; RRHH personal; User legacy; mutaciones Profile; Admin Cash; OrderPurchaseFinance create/edit/show/lost; documentos; transacciones POS; Public Catalog. Sin delta, ninguno se recomienda como NEXT.

**PROFILE-01**: la deuda equivalente ya está descrita en `REAL-NEXT-MY-PROFILE.md`; se registra su identificador en el backlog canónico, sin duplicar el root cause. Los POST de imagen/settings/password reciben `{user}` sin comprobación explícita SELF. Required Password Change es otro contrato; no cierra USER-01 legacy.

Bloqueo prioritario por valor funcional: **GUIDE-01/02 — autorización de Create/Store e idempotencia/failure contract de emisión**. No intentar resolverlo desde frontend.

## Legacy / Skip

Seis vistas registradas fuera de allowlist se separan como **SKIP / OBSOLETE** para planificación: material.index, material.variants, entry.index_entry_purchase, orderPurchase.indexGeneral, orderPurchase.indexExpress, orderPurchase.indexNormal. No implica autorización para borrar rutas ni archivos. Store Web y landing no pertenecen al motor Next ERP; Public Catalog mantiene su bloqueo independiente.

## Remaining Frontend Estimate

**229 vistas aprox.**: READY2 + WAIT FOR DEV135 + LEGACY/OBSOLETE6 + UNKNOWN86. Son vistas, no módulos ni fases. Las135 agrupan familias bloqueadas; no representan135 defectos independientes. UNKNOWN no equivale a READY.

Medición reproducible sobre este HEAD: cargar rutas Laravel por CLI, seleccionar métodos GET de controller, extraer con ReflectionMethod únicamente su fuente sin comentarios y los literales `return view('…')`; exigir Blade existente; excluir prefijos auth/email/export/pdf/errors/layouts y métodos pdf/print/excel/export/reportPDF; cruzar nombres/URI con config/venti-next.php. Si cualquier alias de una vista está allowlisted, excluir esa vista. Deduplicar por vista dentro de controller. Excluir LandingController, StoreWebController, HomeController y PublicCatalogController del perímetro ERP. No se produjo otro inventario completo de1390 rutas ni se ejecutaron controllers GET.

Es un **proxy de vistas de negocio con ruta registrada**, no certificación de navegación vigente de las229. La navegación se confirmó para los dos candidatos. Delegaciones/view dinámicas pueden subcontar; rutas registradas en desuso pueden sobrecontar. Exports, emails, partials sin ruta y vistas técnicas quedan fuera según las reglas anteriores. La cifra orienta volumen estructural; no significa que falten229 integraciones útiles.

Desglose para repetir la clasificación (nombres sin sufijo Controller):

- READY2: ShippingCatalog1; CompanyStockItem1.
- WAIT135: User2; Role3; Permission1; Material8; Area1; Warehouse2; Shelf1; Level1; Container1; Position1; Location3; EntryInventory3; EntryScraps2; Output17; OrderService11; FollowMaterial2; Contract5; MedicalRest3; Vacation3; License3; UnpaidLicense3; Permit3; PermitHour3; Suspension3; WorkingDay1; Discount3; Refund3; Loan3; Regime1; OrderPurchaseFinance8; Expense3; SpecialBonus4; ExpenseSupplier1; WorkerAccount1; DefaultEquipment3; Proforma5; Inventory1; ReferralGuide4; SeasonalPromotion2; DataGeneral1; PromotionLimit3; PromotionOrder1; ShippingGuide1; FreeSale1.
- UNKNOWN86: Entry10; Quote19; OrderExecution3; OrderPurchase11; Invoice2; Report1; Timeline7; Worker4; Assistance8; Gratification2; FifthCategory2; Alimony2; Boleta4; PersonalPayment1; FinanceWork1; PuntoVenta1; Combo2; CashRegister1; Face2; QuoteSale3. Son otras vistas registradas fuera de allowlist, no una propuesta para reabrir façades cerradas.
- LEGACY6: lista exacta en sección anterior; esta asignación precede la clasificación por controller.

Conteos de los candidatos tomados de la DB local en transacción **READ ONLY** terminada con rollback. No fixtures ni registros nuevos. No QA HTTP, responsive o mutaciones en esta fase.

## Recommended Next Steps

| # | Module | Surface | State | Dataset | Why |
|---|---|---|---|---|---|
| 1 | Catálogos de guías de plataforma | Una vista, cinco mantenedores relacionados | READY FOR FRONTEND | 29 registros | Autoridad global explícita, permisos, sin GET mutador; menor esfuerzo y mayor probabilidad de cierre |
| 2 | Catálogo de productos por empresa | Index/selector/listado y acciones existentes | READY FOR FRONTEND | 49 SKUs / 63 habilitaciones | Contrato tenant/company explícito y navegación operativa vigente |

Tercera fase: **NONE**. No rellenar con polish, QA closure o familias UNKNOWN. Sólo documentación modificada; backend/frontend unchanged; sin commit.
