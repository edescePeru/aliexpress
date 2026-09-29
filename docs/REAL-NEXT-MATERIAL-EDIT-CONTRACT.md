# REAL NEXT — Material Edit Contract

## Current Contract

`material.edit` renders the persisted Material and its StockItem data inside the approved Material Create Complex Form. `aliexpress` remains the functional source of truth and `material.create` the visual reference. The route is protected by the existing tenant context, authentication and `update_material` permission middleware.

This phase validates rendering and client-side interaction only. `material.update` must not be executed until the backend synchronization risks below are resolved.

## Differences vs Create

- The Simple / Variants mode is persisted and readonly; Edit cannot convert between modes.
- Persisted values take precedence after `old()`; Create defaults are not applied.
- Existing physical stock, reservations, warehouse, location and costs are informational.
- The current material image is displayed before the replacement Dropzone.
- Persisted variants retain their contractual IDs and readonly size/color identity.
- Existing variants are not offered a delete action because the backend has no deletion contract.
- Section 04 is conditional: simple materials use the full width for inventory by location, while variant materials keep the variant configuration table and each StockItem's associated inventory detail.

## Editable Fields

- Description and enabled commercial classification fields.
- Full name through the existing regeneration action.
- For a simple StockItem: SKU, barcode, package configuration, inventariable flag and active state.
- Minimum and maximum stock thresholds for existing company inventory levels.
- For variants: SKU, barcode, active state, inventariable flag, optional replacement image, and inventory minimum/maximum thresholds.
- New size/color combinations may be generated only when the persisted material already uses variants.

## Readonly Fields

- Simple / Variants mode.
- Persisted variant size and color identity.
- Physical stock and reserved quantity.
- Average cost and last cost.
- Warehouse and location.
- Aggregate stock shown for each variant.

Readonly data remains visible and the hidden contractual identifiers remain available to the existing serializer where required.

## Variant Update Contract

Each rendered variant preserves `variant_id`, `talla_id`, `color_id`, SKU, barcode, state, inventory flag and per-company inventory-level IDs. Existing size/color identity is readonly. New combinations use the existing generator and payload builder. No persistent variant deletion is exposed or tested.

## Image Update Contract

The persisted material image is shown as the current image. Dropzone and variant file inputs only stage a local replacement; the existing update request would persist it only after submit. There is no delete-image action because no backend deletion contract exists. QA must not submit a replacement.

## Open Business Decisions

- Whether SKU, barcode and active state may change when a StockItem has transaction history.
- Whether persisted size/color identity requires an explicit backend immutability rule.
- Whether changing `typescrap` is allowed when historical entered/exited Items exist.
- Whether the global `TipoVenta` catalog is acceptable in the multitenant contract.
- Whether Material and StockItem edits should remain tenant-wide when the visible inventory context is company-specific.

## Backend Sync Notes

The current controller can overwrite fields that Edit does not render:

- absent `unit_price` is normalized to `0`;
- absent `type_tax_id` is normalized to `null`;
- discounts are deleted and rebuilt;
- `$enabled`-hidden fields may be written as null/empty values;
- tenant-wide Material/StockItem updates can affect data observed across companies;
- changing `typescrap` can mutate historical item semantics.

These are **BACKEND SYNC OPEN** items. Until they are addressed, visual QA is allowed but Save remains blocked.

Resolved: `/get/types/{subcategory_id}` now uses `permission:list_materialType`.

## Revisit Triggers

Revisit and enable save validation only after:

1. absent fields are preserved rather than defaulted by the controller;
2. discount synchronization has an explicit edit contract;
3. tenant/company ownership for Material, StockItem and inventory writes is confirmed;
4. history-sensitive SKU/barcode/state, size/color and `typescrap` mutations are decided;
5. update feature tests cover simple and variant materials without cross-tenant or cross-company effects.

