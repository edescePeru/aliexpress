# REAL NEXT 5.8 — Material Configuration CRUD: Colores

## Current Contract

Color is a tenant-scoped material configuration catalog implemented by `ColorController`, the `Color` model and the `BelongsToTenant` global scope. It uses its own server-paginated AJAX adapter; it does not use DataTables.

| Operation | Route | Method | Permission | Contract |
| --- | --- | --- | --- | --- |
| Index | `color.index` (`/dashboard/colors`) | GET | `list_color` | Renders the catalog shell. |
| Data | `color.data` (`/dashboard/colors/data`) | GET | `list_color` | Accepts `page`, `search`, `per_page`; returns a Laravel paginator. |
| Create | `color.create` (`/dashboard/crear/color`) | GET | `create_color` | Separate page. |
| Store | `color.store` (`/dashboard/color/store`) | POST | `create_color` | AJAX JSON response. |
| Edit | `color.edit` (`/dashboard/editar/color/{id}`) | GET | `update_color` | Separate page; tenant scope applies to lookup. |
| Update | `color.update` (`/dashboard/color/update`) | POST | `update_color` | AJAX JSON response. |
| Delete | `color.destroy` (`/dashboard/color/destroy`) | POST | `destroy_color` | Tenant-aware request and variant dependency guard. |
| Bulk delete | `color.deleteMultiple` (`/dashboard/color/delete-multiple`) | POST | `destroy_color` | Exists, but remains disabled in the UI pending a dedicated request contract. |

The list adapter accepts only `10`, `25`, or `50` records per page, searches `name`, `code`, and `short_name`, and sorts by `name ASC`. The JSON response is the standard Laravel paginator shape (`data`, `current_page`, `last_page`, `from`, `to`, `total`).

## Resource Fields

- `name`: required string, maximum 255, unique within the active tenant.
- `short_name`: required string, maximum 255, unique within the active tenant.
- `code`: optional string with exact `#RRGGBB` format.
- `color_id`: update/delete identifier; integer and required to exist within the active tenant.
- `tenant_id`: assigned through the tenant model contract and never accepted from the browser.

The create/update forms preserve `#formColor`, `#name`, `#short_name`, `#code`, `#colorPicker`, `#colorPreview`, `#btnSaveColor`, CSRF, existing AJAX URLs and serialized payload. Picker and preview are UI-only controls without `name`, so they do not alter persistence.

## Differences vs Size Pilot

Color uses the same Page Header, toolbar, Operational List, Result Summary, table, pagination, Row Actions Dropdown, auxiliary delete modal and create/edit form surface as Tallas. Its only functional exception is hexadecimal color representation:

- the table renders a native disabled color swatch for valid codes;
- the textual hexadecimal value remains in its own centered column;
- records without a valid code display `Sin código`/an em dash rather than inventing a value;
- create/edit retain the native color picker and a separate preview;
- no Color-specific CSS or inline visual style is required.

This representation is not color-only: name, short name, hexadecimal text, accessible labels and neutral missing-value text remain available.

## Permissions

- `list_color`: index and JSON data endpoint.
- `create_color`: create/store, primary CTA, sidebar create child, and Material Create/Edit auxiliary action.
- `update_color`: edit/update and the row `Editar` action.
- `destroy_color`: individual delete, row `Eliminar`, selection controls, and the disabled bulk control.

FormRequest `authorize()` returns true; route middleware is the authorization boundary. Blade actions mirror those permissions.

## Multitenancy

`Color` uses `BelongsToTenant`. Store/update/delete validation is tenant-aware through `TenantContext`, and controller lookups inherit the model scope. Read-only database verification found distinct current datasets for EDESCE (tenant 1) and Gamarra (tenant 3), with no cross-tenant variant/color joins.

Material Create/Edit loads `Color::orderBy('name')` under the same global scope. Variant validation and persistence also resolve `color_id` inside the active tenant. No tenant or company identifier is supplied by the frontend.

## Delete Behavior

Individual delete is a hard delete: `Color` does not use `SoftDeletes`. The controller blocks the operation when `variants()` exists and returns HTTP 422 with the backend message. The database foreign key on `variants.color_id` is nullable with `nullOnDelete`, but the controller guard is the intended application policy.

Stock items, inventory levels and operational history do not own a direct `color_id`; they reach Color through Variant. The individual action therefore uses the approved confirmation modal but was not executed during QA.

## Bulk Delete

The route and permission exist, and the controller tenant-scopes the selected IDs, checks every selected Color for Variant dependencies, and performs an all-or-nothing transaction. However, it accepts a generic `Request` and only checks that `ids` is a non-empty array; it has no dedicated FormRequest or `ids.*` integer/existence validation.

For that reason, the shared bulk action is rendered for authorized users but stays disabled and marked `data-backend-sync-open="true"`. No bulk request is sent.

## Material Integration

- Color is available only when the material capability list contains `color`.
- Create/Edit selectors keep `#color`, `name="color[]"`, Select2 multiple selection and `data-short-name` used by variant generation/SKU labels.
- The `+` action is permission-gated by `create_color` in both Material Create and Edit.
- Both auxiliary modals post `name`, `code`, and `short_name` to `color.store`, use the shared auxiliary modal surface and refresh the Select2 selection with the returned `id`, `description`, and `short_name`.
- Option insertion uses `new Option` plus an explicit `data-short-name` assignment, preserving the response contract without interpolating response text into HTML.
- The store response does not return `code`; therefore the material selector intentionally remains text/short-name based and does not invent a visual swatch after an inline create.

## Backend Sync Notes

`BACKEND SYNC OPEN`: replace the generic bulk `Request` with a dedicated tenant-aware FormRequest validating `ids`, `ids.*`, and the intended delete policy before enabling the bulk action.

No backend change is required for the individual CRUD or the Material variant integration.

## Revisit Triggers

- Enable bulk delete only after the dedicated validation contract is approved.
- Revisit Material Select2 rendering only if the store response officially adds `code` and product design approves visual swatches inside the selector.
- Revisit hard-delete policy if audit/history requirements require soft deletion.
- Re-audit dependencies if any table gains a direct `color_id` relationship.
