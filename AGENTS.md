# Venti360 Frontend Next — Real Multitenant Integration

## Purpose

This repository is the REAL active Venti360 application currently being adapted to multitenancy.

This is no longer the experimental frontend laboratory.

The project has two different sources of truth:

1. `aliexpress`
   - functional source of truth
   - backend contracts
   - routes
   - controllers
   - requests
   - permissions
   - tenant/company/branch context
   - data contracts
   - AJAX
   - business behavior
   - persistence behavior

2. `venti-next`
   - approved visual source of truth
   - approved frontend patterns
   - approved component hierarchy
   - approved spacing
   - approved responsive behavior
   - approved shell
   - approved visual language
   - approved reference screens

The goal of this repository is:

> Integrate the approved Venti Next frontend system into the REAL multitenant Venti360 application while preserving the current backend contracts.

This is NOT a redesign-from-zero project.

This is NOT a continuation of the Venti Next experimentation phase.

---

# Core principle

## Real backend contract + approved visual baseline

Always follow this rule:

> `aliexpress` is the functional source of truth.
>
> `venti-next` is the visual source of truth.

If the real application introduces new functional requirements:

- adapt the approved visual pattern;
- do not replace the real contract with an older contract;
- do not invent a new visual system unless the baseline truly has no equivalent solution.

---

# Priority hierarchy

When resolving conflicts, use this order:

1. current real backend contract in `aliexpress`
2. current multitenant rules
3. permissions and authorization
4. current JavaScript/data contracts
5. approved Venti Next visual baseline
6. Venti UI Legacy compatibility behavior
7. old Legacy visual behavior

Never allow a visual reference to override a real backend contract.

Never allow old Legacy markup to override an approved Venti Next visual pattern when reconstruction is safe.

---

# Approved frontend baseline

The Venti Next visual system is considered APPROVED and FROZEN BY DEFAULT.

The approved baseline lives in:

`C:\wamp64\www\venti-adminlte-ui\venti-next`

Do not treat this repository as a source for old backend contracts.

Use it only for:

- visual structure;
- shared CSS;
- approved component markup patterns;
- responsive patterns;
- accessibility patterns;
- visual states;
- toolbar hierarchy;
- modal structure;
- tables;
- forms;
- badges;
- lists;
- spacing;
- typography;
- surfaces;
- shell behavior where compatible.

---

# No uncontrolled visual experimentation

The visual system must NOT be independently redesigned on every screen.

Do not:

- create alternative versions of approved components;
- recreate existing patterns using different CSS;
- introduce new spacing scales without reason;
- introduce new radius scales;
- duplicate colors;
- duplicate design tokens;
- create page-specific visual hacks;
- replace approved markup only because another solution also works;
- create a second responsive system for an already-approved component.

If an approved Venti Next pattern solves the problem, reuse it.

New visual patterns require explicit justification.

---

# When visual evolution is allowed

The approved baseline may evolve only when:

1. the real application introduces a requirement not covered by the baseline;
2. an existing pattern demonstrably fails;
3. a new reusable pattern is clearly better;
4. the user explicitly approves the new direction.

When proposing a change:

1. identify the baseline pattern;
2. explain why it is insufficient;
3. propose the smallest reusable evolution;
4. wait for approval before promoting it globally.

Do not silently evolve the design system.

---

# Preserve backend contracts

Preserve unless explicitly authorized otherwise:

- routes
- route names
- route parameters
- controller contracts
- request validation
- HTTP methods
- form actions
- input `name`
- hidden fields
- CSRF behavior
- permissions
- authorization directives
- middleware
- AJAX endpoints
- AJAX payloads
- response formats
- validation behavior
- business rules
- database behavior
- tenant context
- company context
- branch context
- warehouse context
- IDs required by JavaScript
- classes required by JavaScript/plugins
- `data-*`
- plugin initialization contracts

Do not change backend architecture to simplify frontend work.

---

# Multitenancy rules

The REAL multitenant application always takes priority.

Never assume:

- tenant IDs are global;
- company IDs are global;
- branch IDs are global;
- warehouse IDs are fixed;
- location IDs are fixed;
- catalogs are global;
- inventory is global;
- permissions are identical between tenants;
- enabled fields are identical between companies.

Do not reintroduce hardcoded IDs from experimental or Legacy code.

Examples that must never be restored unless the current backend explicitly requires them:

- `warehouse_id = 1`
- `location_id = 1`
- global catalog assumptions
- global inventory assumptions

If a value is resolved by `TenantContext`, company configuration, branch context, or backend service, preserve that behavior.

---

# Backend Sync Notes

Frontend and backend development proceed in parallel.

Do NOT block frontend progress only because every backend policy is not final.

Use three states:

## IMPLEMENTED

The frontend is fully compatible with the current real contract.

## IMPLEMENTED WITH BACKEND DEPENDENCY

The frontend is implemented using the current contract, but one backend rule remains open.

Document it.

## BLOCKED

Frontend cannot safely proceed without inventing behavior, violating security, or risking data isolation.

Only use BLOCKED when necessary.

---

# Backend Sync documentation format

Every real screen contract document should include:

```text
## Backend Sync Notes

[OPEN]
Point:
File / endpoint:
Impact:
What backend needs to define or correct:
Frontend can continue: Yes / No

[RESOLVED]
Point:
Final contract:
Date / commit if available:
Do not convert backend uncertainty into frontend assumptions.

Visual reuse strategy

Before creating new CSS or markup, always inspect the equivalent approved pattern in:

C:\wamp64\www\venti-adminlte-ui\venti-next

Classify the requirement as:

REUSE DIRECTLY
REUSE WITH CONTRACT ADAPTATION
NEW REAL REQUIREMENT
LEGACY ONLY
BACKEND DEPENDENCY

Prefer this order:

reuse existing baseline markup;
reuse existing baseline CSS;
adapt the minimum required markup;
add the minimum required CSS;
create a new pattern only if necessary.
Do not duplicate approved styles

Before adding:

a token;
radius;
shadow;
spacing;
breakpoint;
modal rule;
form rule;
table rule;
responsive rule;
state rule;

search the approved baseline first.

Do not create aliases for concepts already covered.

Example:

Avoid creating:

--next-ink
--next-line
--next-primary
--next-radius

if equivalent approved tokens already exist.

Prefer the existing approved token.

CSS strategy

Approved layering:

Bootstrap 4.4.1
→ AdminLTE 3
→ Venti UI Legacy
→ Venti Next

AdminLTE owns:

structural geometry;
sidebar mechanics;
PushMenu;
treeview;
responsive shell mechanics;
plugin mechanics when applicable.

Venti UI / Venti Next own:

visual appearance;
colors;
surfaces;
typography;
borders;
shadows;
radius;
spacing;
component hierarchy;
visual states.

Do not override stable AdminLTE geometry unless required.

Venti Next CSS

Primary real CSS:

public/admin/dist/css/venti-next.css

This file should increasingly match the approved shared baseline.

Do not append large page-specific override layers to compensate for incorrect markup.

Prefer:

correct semantic markup;
baseline component class;
small real-contract adaptation.

Avoid:

large override blocks;
nth-child() for semantic sizing when approved classes exist;
IDs as styling selectors;
hardcoded colors when tokens exist;
duplicated modal systems;
duplicated responsive systems;
repeated !important;
inline layout styles.
Blade reconstruction

Existing aliexpress Blade is the functional source of truth.

Existing venti-next Blade is the visual pattern reference.

Before reconstruction:

inspect current real Blade;
inspect current real JS;
inspect route;
inspect controller;
inspect request;
inspect permissions;
inspect plugins;
inspect equivalent approved Venti Next Blade.

Then map:

REAL contract
→ APPROVED visual structure

Do not copy the entire baseline Blade blindly.

Do not recreate the visual structure from memory.

JavaScript

Current aliexpress JavaScript is the functional source of truth.

Never replace a real JS file wholesale with an experimental version.

Before DOM changes inspect:

selectors;
delegated events;
AJAX URLs;
payloads;
serialization;
plugin initialization;
validation;
callbacks;
tenant-dependent data.

When markup changes require JS changes:

preserve business logic;
change only frontend integration;
keep selectors explicit;
avoid unrelated refactors.
Plugins

Existing plugins may remain.

Do not replace plugins only because they are old.

Integrate them visually with approved Venti Next patterns.

Examples:

Select2
Bootstrap Switch
iCheck
Dropzone
Datepicker
DataTables
Toastr
SweetAlert2
Bootstrap Modal

Do not replace plugin contracts without explicit approval.

Forms

Approved forms should reuse the Venti Next form language.

Prefer:

clear section hierarchy;
approved spacing;
approved label treatment;
approved focus state;
approved field actions;
approved responsive grid;
clear primary action;
restrained secondary actions.

Large forms should use meaningful sections.

Do not create arbitrary nested cards.

When the real application adds fields:

place them inside the existing approved grid;
adapt the grid minimally;
do not invent a parallel form system.
Tables and operational lists

For real operational lists prefer approved patterns:

Operational List
Advanced Operational List
List Toolbar
Advanced Filters
Result Summary
Column Visibility Menu
Row Actions Dropdown
Status/Badge language
Pagination
Floating Surface

Do not rebuild these independently per screen.

Wide tables may use local horizontal scrolling.

Do not introduce global document overflow.

Modals

Prefer approved Venti Next modal patterns.

Use shared classes such as:

.next-aux-modal
.next-aux-modal-form

when the baseline supports the requirement.

Do not recreate modal styling using:

ID selectors;
hardcoded colors;
page-specific selectors;
multiple parallel modal systems.

Preserve:

IDs required by JS;
form contracts;
permissions;
callbacks;
focus;
accessibility.
Responsive behavior

Use approved Venti Next responsive patterns and breakpoints whenever possible.

Do not create new breakpoints unless the real requirement genuinely needs them.

Priorities:

desktop operational efficiency
tablet usability
mobile usability

Avoid:

global horizontal overflow;
forced four-column grids too early;
page-specific breakpoint hacks;
duplicating responsive rules already solved in baseline.
Accessibility

Preserve and improve:

keyboard navigation;
visible focus;
labels;
accessible names;
modal focus management;
Escape behavior;
focus restoration;
target sizes;
contrast.

Do not remove accessible behavior for visual parity.

Shared development database

Treat development data as read-mostly unless the user explicitly authorizes persistence.

Do NOT:

create business records;
update business records;
delete records;
change stock;
submit sales;
submit purchases;
approve operations;
annul operations;
persist configuration;
execute destructive actions;

unless explicitly authorized.

Prefer:

rendering;
navigation;
opening modals;
changing selects;
filtering;
client-side variants;
file selection without upload;
non-persistent validation;
GET AJAX;
network inspection;
DOM inspection.
PHP runtime

The real local project currently runs with:

C:\wamp64\bin\php\php7.3.33\php.exe

Use PHP 7.3.33 for Laravel CLI validation unless explicitly instructed otherwise.

Do not validate the real project only with another PHP version and assume compatibility.

Examples:

C:\wamp64\bin\php\php7.3.33\php.exe artisan --version
C:\wamp64\bin\php\php7.3.33\php.exe artisan view:cache
C:\wamp64\bin\php\php7.3.33\php.exe artisan config:clear
Git discipline

Before modifying:

inspect git status;
identify unrelated changes;
do not overwrite backend developer work.

After modifying report:

files changed;
markup changes;
JS selector changes;
CSS changes;
backend assumptions;
Backend Sync Notes;
unresolved risks.

Do not hide unrelated refactors inside frontend work.

Workflow for each REAL screen
Phase A — Real contract audit

Inspect:

route;
controller;
request;
Blade;
JavaScript;
plugins;
permissions;
tenant/company/branch context;
data contract;
responsive behavior;
current visual debt.

Do not modify yet.

Phase B — Approved baseline mapping

Find the equivalent pattern in venti-next.

Identify:

reusable markup;
reusable classes;
reusable CSS;
reusable responsive behavior;
reusable accessibility behavior;
real-contract differences.

Do not redesign if the baseline already solves the problem.

Phase C — Contract adaptation

Map the REAL contract into the approved visual pattern.

Classify each difference:

REUSE DIRECTLY
VALID REAL CONTRACT ADAPTATION
BACKEND SYNC OPEN
NEW PATTERN REQUIRED

Only introduce new visual structure when justified.

Phase D — Implementation

Implement incrementally.

Preserve contracts.

Reuse approved patterns.

Avoid unrelated refactors.

Avoid duplicate CSS.

Phase E — Functional verification

Verify:

rendering;
permissions;
AJAX;
validation;
plugins;
modals;
responsive;
keyboard/focus;
console;
network errors;
tenant isolation;
company isolation;
branch isolation where applicable.
Phase F — Visual parity review

Compare the REAL screen against the approved baseline.

Classify differences as:

VALID REAL DIFFERENCE
VISUAL PARITY
UNJUSTIFIED VISUAL DEVIATION
DUPLICATED / RECREATED STYLE

Do not declare the screen complete with unexplained visual deviations.

Reference screen status

Approved baseline reference screens currently include:

Complex Form

Reference:

material.create

Use for:

large create/edit forms;
numbered sections;
form hierarchy;
inventory/image layout;
variants;
auxiliary modals.
Operational List

Reference:

puntoVenta.list

Use for:

page toolbar;
filters;
result summary;
operational tables;
row actions;
statuses;
pagination.
Advanced Operational List

Reference:

material.indexV2

Use for:

advanced filters;
column visibility;
wide tables;
row actions;
inventory/status information.

These are visual references only.

Always use the REAL current contract in aliexpress.

Current approved visual language

Use the approved Venti Next direction:

navy sidebar;
white navbar;
blue-gray application canvas;
white work surfaces;
border-first hierarchy;
restrained shadows;
strong readable text;
controlled blue brand emphasis;
compact ERP density;
consistent spacing;
soft semantic states;
neutral operational actions;
semantic destructive actions;
modern ERP/SaaS appearance.

Avoid:

excessive whitespace;
oversized controls;
decorative gradients;
arbitrary rounding;
heavy shadows;
washed-out contrast;
page-specific visual inventions;
inconsistent action systems.
Definition of done

A REAL screen is complete when:

real backend behavior is preserved;
multitenant context is preserved;
permissions are preserved;
JS behavior works;
plugins work;
validation works;
responsive works;
keyboard/focus works;
no console errors;
no unintended network errors;
approved visual baseline is reused;
unexplained visual deviations are removed;
unnecessary duplicate CSS is removed;
no unsafe backend assumptions remain undocumented;
Backend Sync Notes are updated;
visual review is approved.
Final rule

When in doubt:

Do not invent.

Inspect the REAL contract.

Find the approved Venti Next pattern.

Reuse it.

Adapt only what the real contract requires.