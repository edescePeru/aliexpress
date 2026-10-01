# Venti Next — Material Create Contract

This document freezes the frontend/backend contract of `material.create` before
the Venti Next reconstruction. Visual markup may change, but the contracts below
must remain compatible unless a later phase explicitly authorizes a change.

## Entry point and authorization

- Page route: `GET /dashboard/crear/material`
- Route name: `material.create`
- Controller: `MaterialController@create`
- Middleware: `web`, `auth`, `check.user.enabled`,
  `permission:create_material`
- Store route: `POST /dashboard/material/store`
- Store route name: `material.store`
- Store controller: `MaterialController@store`
- Store request: `StoreMaterialRequest`
- Store middleware: `web`, `auth`, `check.user.enabled`,
  `permission:create_material`

The Blade contains no `@can` or `@canany` directives. Auxiliary create actions
are currently rendered according to enabled material sections, while their POST
routes enforce their own middleware where configured.

## View data

`MaterialController@create` supplies:

- `enabled`
- `discountQuantities`
- `tipoVentas`
- `tallas`
- `generos`
- `categories`
- `warrants`
- `brands`
- `qualities`
- `typescraps`
- `unitMeasures`
- `colors`

Conditional sections currently depend on `enabled`: `brand`, `exampler`,
`category`, `subcategory`, `genero`, `unit_measure`, `perecible`, and `talla`.
Colors are loaded and rendered unconditionally.

## Main form

- Form ID: `formCreate`
- Encoding: `multipart/form-data`
- Store URL source: `data-url="{{ route('material.store') }}"`
- CSRF: Blade `@csrf`, serialized as `_token`
- There is intentionally no HTML `action` or `method` in the current contract.
- Submit trigger: click on `#btn-submit`.
- `#btn-submit` must remain `type="button"` in this phase.
- JavaScript builds `new FormData($('#formCreate')[0])` and posts it with
  `processData: false` and `contentType: false`.
- On success, Toastr displays `data.message`; after two seconds the button is
  enabled and the page reloads.
- On validation failure, `responseJSON.errors` entries are displayed through
  Toastr and the button is enabled again.

## Main input names

The following submitted names must remain compatible:

```text
_token
description
name
brand
exampler
category
subcategory
genero
unit_measure
tipo_venta
perecible
variantes
sku_sin_variantes
codigo_sin_variantes
pack
inputPack
afecto_inventario_sin_variantes
stock_min
stock_max
talla[]
color[]
```

JavaScript appends these additional fields:

```text
image
tipo_variantes
variantes_json
variant_image_0
variant_image_1
variant_image_N
```

`image` is the single file retained by the Dropzone `uploadedImage` variable.
Variant image field names are generated from each row index and must match that
row's `image_key` in `variantes_json`.

## Main IDs used by JavaScript

```text
formCreate
btn-submit
description
name
brand
exampler
category
subcategory
genero
unit_measure
tipo_venta
perecible
sin_variantes
con_variantes
seccion_sin_variantes
seccion_con_variantes
sku_sin_variantes
codigo_sin_variantes
checkboxPack
inputPack
afecto_inventario_sin_variantes
stock_min
stock_max
talla
color
btn-generate
btn-generateCodeSinVariantes
btn-generate_variantes
body-variantes
template-variante
brand_id_hidden
categoria_id_hidden
image-dropzone
```

Current JavaScript also references historical IDs that are absent from this
Blade. They are recorded to prevent accidental behavior changes during NEXT 1:

```text
btn-add
feature-body
type
subtype
warrant
quality
material_type
feature
is_active
codigo_con_variantes
specification
content
template-specification
body-specifications
```

## Classes and data attributes used by JavaScript/plugins

Functional classes:

```text
select2
btn-generateCode
item-variante
```

Functional attributes:

```text
data-url
data-delete
data-talla_text
data-talla_id
data-color_text
data-color_id
data-sku_sugerido
data-codigo_barras
data-image_variante
data-is_active_variante
data-afecto_inventario_variante
data-stock_minimo
data-stock_maximo
data-bootstrap-switch
data-size
data-on-text
data-off-text
data-on-color
data-off-color
data-short-name
data-toggle
data-target
data-dismiss
data-card-widget
data-tracks_inventory_sin_variantes
```

The inputs inside `#template-variante` deliberately have no `name`; JavaScript
reads their `data-*` hooks and serializes them into `variantes_json`.

## Exact `variantes_json` structure

`tipo_variantes` is `0` for a product without variants and `1` for a product
with variants. `variantes_json` is a JSON string containing an array of objects:

```json
[
  {
    "talla_id": null,
    "color_id": null,
    "sku": "ABC-DEF",
    "codigo_barras": "",
    "stock_minimo": 0,
    "stock_maximo": 0,
    "is_active": 1,
    "afecto_inventario": 1,
    "pack": 0,
    "cantidad_pack": 1,
    "image_key": null
  }
]
```

For multiple variants, `talla_id` and `color_id` are required by backend
validation. When a row contains a file, `image_key` is `variant_image_{index}`
and that exact key is appended to the same `FormData`.

## AJAX endpoints and response contracts

### Dependent selects

- `GET /dashboard/get/subcategories/{category_id}`
  - Expected array item: `{ "id": ..., "subcategory": ... }`
- `GET /dashboard/get/exampler/{brand_id}`
  - Expected array item: `{ "id": ..., "exampler": ... }`
- `GET /dashboard/get/subtypes/{type_id}`
  - Historical selector integration remains in JavaScript, although the related
    controls are not rendered by this Blade.

### Auxiliary create forms

| Form ID | Trigger ID | Route | Required frontend response |
| --- | --- | --- | --- |
| `formCreateUnitMeasure` | `btnSaveUnitMeasure` | `unitmeasure.store` | `success`, `data.id`, `data.description` |
| `formCreateBrand` | `btn-saveBrand` | `brand.store` | `success`, `data.id`, `data.name` |
| `formCreateExampler` | `btn-saveExampler` | `exampler.store` | `id`, `exampler` |
| `formCreateGenero` | `btnSaveGenero` | `genero.store` | `success`, `data.id`, `data.description` |
| `formCreateTalla` | `btnSaveTalla` | `talla.store` | `success`, `data.id`, `data.description` |
| `formCreateCategoria` | `btnSaveCategoria` | `category.store` | `success`, `data.id`, `data.description` |
| `formCreateSubCategoria` | `btn-saveSubCategoria` | `subcategory.store.individual` | `data[0].id`, `data[0].name` |
| `formCreateColor` | `btnSaveColor` | `color.store` | `success`, `data.id`, `data.description`, `data.short_name` |

All auxiliary forms include `_token` and are serialized with jQuery. Their
effective HTTP method is POST through JavaScript and their URL comes from
`data-url`.

### NEXT 3 auxiliary modal behavior

The eight auxiliary forms use the reusable `.next-aux-modal` presentation
pattern without changing their IDs, `data-url`, field names, save triggers, or
AJAX callbacks. `Cancelar` now performs an explicit local form reset through
`data-modal-cancel` and closes through Bootstrap `data-dismiss="modal"`; it does
not execute a request. On open, focus moves to the first designated field, and
on close it returns to the originating trigger when that trigger remains in the
document.

Auxiliary hidden fields:

- `brand_id` / `#brand_id_hidden`
- `category_id` / `#categoria_id_hidden`

## Auxiliary route permissions

- Brand: `permission:create_brand`
- Model: `permission:create_exampler`
- Category: `permission:create_category`
- Subcategory: `permission:create_subcategory`
- Unit measure: `permission:create_unitMeasure`
- Gender, size, and color: only the enclosing `auth` and
  `check.user.enabled` middleware are currently active; their route permission
  declarations are commented out.

## Plugin contracts

- Select2 4.0.13: initialized by stable select IDs and requires
  `data-short-name` on size/color options for SKU generation.
- iCheck Bootstrap 3.0.1: relies on the existing input/label ID pairing.
- Bootstrap Switch 3.3.4: initializes every
  `input[data-bootstrap-switch]`, including newly cloned variant rows.
- Dropzone 5.9.3:
  - target `#image-dropzone`;
  - `autoProcessQueue: false`;
  - `maxFiles: 1`;
  - `acceptedFiles: image/*`;
  - assigns and clears the global `uploadedImage` variable from `addedfile` and
    `removedfile` callbacks.
- Bootstrap Modal: depends on existing modal IDs and `data-target` values.
- AdminLTE CardWidget: depends on `data-card-widget="collapse"`.
- Toastr and jquery-confirm provide feedback; their callback payload assumptions
  must remain compatible.

Authorized view-only exception (2026-09-23): the reconstructed main surface of
`material.create` intentionally has no CardWidget collapse trigger. This removes
only the optional presentation behavior of that surface; it does not alter the
global CardWidget integration or any backend, serialization, or business
contract.

## Validation contract

`StoreMaterialRequest` requires `description`, `name`, `tipo_variantes`, and
`variantes_json`. It validates nullable foreign keys, image type, numeric stock,
SKU presence, min/max order, and size/color presence for multiple variants.

Frontend validation remains intentionally unchanged during NEXT 1. Backend
validation is authoritative and returns HTTP 422 on failure.

## NEXT 1 preservation rule

NEXT 1 may reorganize visual markup, Bootstrap grid, headings, actions, and
surfaces. It must not clean historical JavaScript, rename the contracts above,
change endpoints, alter payload serialization, submit data during visual QA, or
change backend behavior.
