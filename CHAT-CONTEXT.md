# Project Context — production.atj

Consolidated context from every Claude Code chat session for this project, read start to finish.

- **Project path:** `C:\wamp64\www\production.atj`
- **Stack:** Laravel 10/11 + Livewire 3 + Blade, FabKin Bootstrap 5 admin theme, jQuery 3.7.1, bootstrap-select, Cleave.js, SweetAlert, MySQL (WAMP)
- **Parent service:** `C:\wamp64\www\auth-service` (DB `erp_auth`) — auth, permissions, shared master data, inventory API
- **Sibling project:** `C:\wamp64\www\operation.atj`
- **Generated:** 2026-08-11 · from 11 project sessions (7 with full transcripts, 4 cleaned up and listed by title only)

---

## 1. Architecture

This is a **production/planning module**. It owns planning and stock *documents* but not master data or inventory — those live in the parent Laravel service (`auth-service`), reached over HTTP through `app/Services/ApiService.php` and `app/Services/InventoryService.php`.

**Owned by the parent (`erp_auth`)**
- `items`, `item_units`, `item_types`, warehouses, companies
- users, roles, permissions
- `warehouse_inventories` — the single source of truth for stock

**Owned locally (`production_atj`)**
- `month_plans` → `plans` → `events`; `event_types`, `event_type_items`
- `recipes`, `recipe_inputs`, recipe side products
- `production_lines`, `preparations`, `lines`, factories
- Stock documents: stock in / stock out / waste / transfer, each with `report_items`
- `event_status_logs`, `event_quantities`, `event_pause_activities`

Because items/units/warehouses are remote, **local models have no Eloquent relations to them**. Anything that looks like `$stockIn->warehouse`, `$reportItem->item`, `$reportItem->itemUnit`, `$transfer->warehouseFrom` will fail — names must be resolved from the API payload already loaded in `mount()` (a recurring bug source; all four view blades once broke this way).

### Parent API endpoints in use

| Endpoint | Returns |
|---|---|
| `GET /v1/items?item_type=` | items (list) |
| `GET /v1/items/{id}` | one item **with its units** |
| `GET /v1/item-types` | id → name map (cached per request) |
| `GET /v1/warehouses/{id}/items` | items the warehouse can hold, each with `item_type_id` + embedded `units` (already filtered by the warehouse's configured item types) |
| `GET /v1/warehouses/{id}/item-types` | `[{id, name}, …]` |
| `GET /v1/warehouse-inventories?warehouse_id=` | inventory rows for a warehouse |
| `GET /v1/warehouse-inventories/find?warehouse_id=&item_id=&item_unit_id=` | one row (on-hand + all holds) |
| `POST /v1/warehouse-inventories/apply` | atomically apply a batch of `operations` |

### The inventory contract

`warehouse_inventories` columns: `quantity`, `quantity_pending_in`, `quantity_pending_out`, `quantity_in_process`.

| Operation | Effect | Used by |
|---|---|---|
| `reserve_in` / `release_in` | `quantity_pending_in ±` | stock in, transfer (destination) |
| `reserve_out` / `release_out` | `quantity_pending_out ±` | stock out, waste, transfer (source) |
| `confirm_in` | `pending_in −`, `quantity +` | approve stock in / receive transfer |
| `confirm_out` | `pending_out −`, `quantity −` | approve stock out / waste / load transfer |
| `reserve_process` / `release_process` | `quantity_in_process ±` | **events only** |
| `confirm_process_in` | `in_process −`, `quantity +` | event produced items / side products |
| `confirm_process_out` | `in_process −`, `quantity −` | event consumed inputs |

Rules:
- **Events never touch the pending columns; stock documents never touch `quantity_in_process`.** This separation was requested explicitly and `quantity_in_process` was added to the parent for it.
- Every `confirm_out` / `confirm_process_out` is validated against on-hand **before** anything is written. If any line is short the whole batch is rejected `422` and nothing changes. The message resolves real names — *"Not enough stock. Sugar Refined (Kilogram) — available 0, required 5"* — and the JSON `shortages` array carries `item_name` / `unit_name`. Multiple shortages join with `; `.
- Plan-board availability formula: `available = quantity − quantity_pending_out − quantity_in_process`, so events and stock-outs can't double-claim the same stock.
- `InventoryService` op builders: `reserveIn/Out`, `releaseIn/Out`, `confirmIn/Out`, `reserveProcess`, `releaseProcess`, `confirmProcessIn`, `confirmProcessOut`. Reads: `forWarehouse()`, `find()`. Writes: `apply()` / `applyOrFail()`.
- Every component injects `InventoryService` in `boot()`, builds an ops array, and calls `applyOrFail($ops)` **as the last step inside its existing DB transaction** — a throw rolls the local transaction back and surfaces the message via SweetAlert.
- **Cross-service atomicity caveat:** local writes and the API call can't share one transaction. Residual risk is API-succeeds-then-local-commit-fails. The fix (not implemented) is an idempotency key on `apply` plus a reconcile job.
- The local `WarehouseInventory` model was deleted and a drop migration staged but **not run** — data must be copied to `erp_auth` first. **This app does not work until the parent table + API are live.**

### Permissions
- `hasPermission()` grants everything to **Super Admin**; otherwise it checks the exact slug and fails closed. `@hasPermission` supports `@else`.
- `authorizeRequest()` **aborts 403** on failure.
- Known mismatch, pre-existing and untouched: the create-page `mount()` gates use `production.stockIn-create`, `stockOut-create`, `rawMaterialWaste-create` — **none of these slugs exist**. The real ones are `itemStockIn-create`, `itemStockOut-create`, etc. So non-super-admins get 403 on those create/edit pages. Transfer correctly uses `itemTransfer-create`. All four `item*-view` slugs do exist and gate the view pages/buttons correctly.

---

## 2. Domain model & rules

### Plans and events
`MonthPlan` (per month) → `Plan` (per day, has `date` + `factory_id`) → `Event` (belongs to a plan, placed on a *placeable*: a `Preparation` or a `Line`).

### Events crossing midnight
- `events.from_time` / `to_time` are **DATETIME**, declared directly in `create_events_table` (nullable). The follow-up `alter_events_from_time_nullable` migration was **deleted** — it ran `$table->time('from_time')->change()` and would have reverted the column to TIME on a fresh migrate.
- Why datetime: with TIME columns "crosses midnight" was an *inference* (`to_time <= from_time`), which can only express one wrap and breaks on an exactly-24h event (`from == to`) or a 30-hour event.
- `events.to_plan_id` — nullable FK to `plans`, SET NULL on delete, indexed; the plan of the day the event *ends* on, null for same-day. Relations `Event::toPlan()` / `Plan::carryOverEvents()`. It's denormalization, kept because it makes the carry-over query trivial and the auto-created link inspectable.
- All three write paths — board drop, `EventCreate::submit`, unplace — go through `app/Services/PlanCarryOverService.php::carryOverLink()`, which creates plans for **every day the event touches** (and month plans across month/year boundaries, copying `factory_name`). It's idempotent, and clears the link when an event no longer crosses. Unplace now also clears `from_time`/`to_time` (it used to leave stale values).
- The board finds spill-ins by **datetime overlap** with the day (same factory), so a three-day event renders on all three days: clipped at midnight on day 1, full width on middle days, `00:00 → end` on the last. An event ending exactly at midnight is correctly *not* a carry-over.
- Labels show real dates ("started 15 Jul", "ends 17 Jul", "(+2 days)"); Excel export uses full datetimes.
- Deliberate behavior: moving an event back so it no longer crosses **does not delete** the auto-created plan (it may have gained its own events).
- `shifts.from_time`/`to_time` stay TIME — they're per-day definitions.

### Event lifecycle
Planned → **Start** → In Progress → **Pause** ⇄ **Resume**, **End** from either running state. Transitions validated server-side against an allowed-transitions map; `submitEventAction` re-validates so a stale modal can't corrupt state. Gated on `production.event-create`.

Naming: the UI says **"End"** but the internal action key stays `terminate` and the status stays `terminated` (badge reads "Ended") — no data migration. Likewise the UI says **"Emergency Event"** but the model stays `EventPauseActivity` on table `event_pause_activities`, deliberately: an `EmergencyEvent` model next to the existing `Event` model would be confusing, and the table name describes the mechanics accurately.

**Guards:**
- Cannot start an event that isn't placed on a preparation/line (server guard; the Start button is hidden in the list view and replaced by a muted "Place to start" hint). Placement determines the warehouse, so this is a hard requirement.
- A started event cannot change lane, and cannot be unplaced — either would orphan its stock reservation. Repositioning in time on the *same* lane is still allowed. This guarantees the warehouse is stable between reserve and confirm.
- Resume and End are blocked while the event has any **unended emergency event** ("This event has N ongoing emergency events…"), checked both before the modal opens and again on submit. To avoid a deadlock the Emergency button also appears on non-paused events that still have open emergencies (red `!` badge, via a `withExists` subquery); in that cleanup mode the modal hides the "add" section and only offers End buttons.

### Warehouse resolution for events
From the **placeable**, never the plan's factory:

| Placeable | Consumed from (`outWarehouseId`) | Produced into (`inWarehouseId`) |
|---|---|---|
| Preparation | `rm_warehouse_id` (raw material) | `fg_warehouse_id` |
| Line | `sfg_warehouse_id` (semi-finished) | `fg_warehouse_id` |

Returns `null` if unconfigured, in which case that leg of the inventory update is skipped silently — make sure those columns are populated on lines you run events on.

### Quantity capture
- **Recipe events, start:** rows from `recipe_inputs` — Item · Unit · Original Qty (disabled) · Used Qty · % Difference (live). One global Notes textarea, plus a Start Time field.
- **Non-recipe events, start:** the modal **always opens**. Rows come from `event_type_items` (the type's defined unit + planned qty becomes Original Qty). If the type has no items defined, the modal still opens with an alert — *"No items are defined for this event type… You can still start the event below."*
- **Emergency events:** same 5-column table, grouped by item type, sourced from `event_type_items`; the unit is read-only text (fixed by the definition). Stored with `source = 'emergency'` and `event_pause_activity_id`, including planned qty and computed percentage.
- **End:** always opens a modal. Recipe events get produced-item + side-product tables; non-recipe events get End Time + Notes only.
- **Percentage is a variance:** `(actual − original) / original × 100`. 8 of 10 → **−20%**; 12 of 10 → **+20%**. (The division was added to the originally-stated formula, since `(used − original) × 100` isn't a percentage.)
- **Original quantities are the raw recipe values** — *not* multiplied by the event's `batch_count`. Changing that is a small edit in `prepareStartModal`/`prepareTerminateModal`.
- `event_quantities` also carries `warehouse_id` (which store the movement hit) and `confirmed_at` (when it actually changed on-hand; null while in-process), which is what lets the warehouse activity report show event movements.

### Logging tables
- `event_status_logs` — one row per status change: action, from/to status, actual duration, reason, notes, `happened_at`, and a snapshot of who did it (`changed_by`, `changed_by_name`).
- `event_quantities` — source (`input` / `output` / `side_product` / `emergency`), planned vs actual, percentage, item/unit **name snapshots** so history renders without API calls, `warehouse_id`, `confirmed_at`.
- `event_pause_activities` — `event_id`, `event_status_log_id` (the pause it belongs to), `event_type_id` + name/duration snapshot, `reason` (required), end time + end note, who added it.
- **`happened_at` vs `created_at`:** every modal has a time field defaulting to now. The user-entered value goes to `happened_at` and drives *everything* downstream — history timestamps, pause→resume duration math (clamped at 0 if backdated before the pause), the Actual/Downtime boards, and the delay badges. `created_at` stays the true insert time.

### Board modes
A **Planned | Actual | Downtime** segmented control, persisted in the URL as `?boardMode=`, all sharing the same grid skeleton (production lines → lanes → 24h × 15-min columns) so orientation is never lost.
- **Planned** — the original drag-and-drop grid plus the unplaced-events tray. Drop zones exist only here.
- **Actual** — each card spans its real run window (first `start` log → last `end` log). Never-started events don't appear. Running events extend to now with a dotted open edge and a "09:12 – now" label. **Emergency events render as red diagonal-striped overlay segments** positioned exactly where they happened, with tooltips. Overlapping items stack into tracks per lane.
- **Downtime** — only the emergency events, on their event's lane, click-through to the parent event.

### Event types
- `has_recipe = false` → the **Item Type** dropdown is hidden in the add/edit event popup and `item_type_id` is forced to `null` on save (even if the type was switched mid-edit).
- Non-recipe event types get an **items table** in their modal: every active item of the selected item types, grouped by item type, each with a unit dropdown and a quantity input. Rebuilt live when the item-type selection or the recipe toggle changes, preserving entered values. Saved to `event_type_items` (only rows with a quantity; delete + recreate). Modal is `modal-lg` scrollable.
- The pause/emergency type dropdown lists **all event types with `has_recipe = false`** (Cleaning, Maintenance, …) — there's no dedicated flag.

### Stock document lifecycle

| | Create / Edit | Confirm (approve) | Delete |
|---|---|---|---|
| **Stock In** | `reserve_in` on destination | `confirm_in` | releases reservation if still pending |
| **Stock Out** | `reserve_out` | validate, then `confirm_out` | releases if still pending |
| **Waste** | `reserve_out` | validate, then `confirm_out` | releases if still pending |
| **Transfer** | source `reserve_out` + dest `reserve_in` | **Load**: validate + `confirm_out` on source. **Receive**: `confirm_in` on destination with the received quantity | releases both sides (only pending is deletable) |

- Transfer's split — source deducted at **Load**, not at Receive — was an explicit user choice ("Split by step") when asked.
- Edit reverses the previous reservation and re-applies the new one, so changing quantity/item/unit/warehouse nets out. Edits are UI-gated to `pending`, so actual stock is never touched during an edit.
- Statuses: `pending` → `approved`; transfers add `loaded`. Delete buttons are hidden for non-pending records.
- **Deleting an already-approved record does not unwind actual quantity** — approved movements are treated as committed.

### Warehouse inventory report & Check Activity popup
- Table columns: On Hand · Pending In · Pending Out · **In Process**, with colored badges (muted when zero).
- The activity popup shows **pending as well as approved** movements. `movementMeta()` buckets each `ReportItem` into `on_hand` / `pending_in` / `pending_out` / `in_process`; `eventMovementMeta()` does the same for `EventQuantity` rows, and both streams merge into one chronologically-sorted timeline.
- Bucketing: approved stock-in/out/waste → *On Hand*; unapproved stock-in → *Pending In*; unapproved stock-out/waste → *Pending Out*; transfers follow their flow (pending → *Pending Out*, loaded/approved source → *On Hand*, incoming until received → *Pending In*, received → *On Hand*). Event/emergency **used** → *In Process* while running, *On Hand* (−qty) once confirmed; event **output / side product** → *On Hand* (+qty) at end.
- The four summary cards are **clickable filters** (active one gets a highlight ring, plus a Clear filter button); `activityFilter` defaults to `'all'` and resets on every open.
- Each row shows `#id` linking to that document's view page in a new tab, gated by the matching `@hasPermission` with a plain-text fallback. Event rows link to the **plan board** (there's no per-event view page) gated by `production.event-create`.
- Movement dates use the confirm time for events (record time while held), keeping the running Stock Total chronologically sensible.
- Item/unit name lookup covers **all active items**, not just `Raw Material` — otherwise warehouses holding other types rendered "N/A".

### Users — the shift-manager cascade
**Warehouses → Production Lines → Preparations + Lines.**
- `getProductionLines()` filters `production_lines` by `factory_id` (the warehouse id), locally — no API needed.
- `getPreparations()` / `getLines()` resolve through the `production_line_preparation` and `line_production_line` pivots (deduplicated via `whereHas`), replacing the old department-based filtering.
- Selections persist in a new `production_line_user_info` pivot, mirroring the existing `preparation_user_info` / `line_user_info`. Relations added to `UserInfo`, `Preparation`, `Line`, and synced by `syncShiftManagerInfo()`.
- Routes: `GET /admin/users/production-lines?warehouse_ids[]=`, `…/production-lines/preparations?production_line_ids[]=`, `…/production-lines/lines?production_line_ids[]=` (replacing two department-based routes).
- JS keeps still-valid selections when options reload, normalizing ids to numbers since `.val()` returns strings. Changing department clears the whole cascade.
- Pre-existing limitation, unchanged: AJAX-loaded options are lost after a failed *create* submission.

---

## 3. Conventions & gotchas

**Icons — Bootstrap Icons only.** The template's `icons.min.css` ships **only** `bi-*`. Tabler `ti-*` classes (`ti-trash`, `ti-plus`, `ti-check`) have zero glyph definitions and render blank. This went unnoticed for a long time because every button also had a text label; an icon-only button exposed it. Use `bi bi-trash`, `bi bi-plus-lg`, `bi bi-check-lg`, `bi bi-info-circle`. Still outstanding: every selectpicker in the app carries `data-icon-base="ti" data-tick-icon="ti-check"`, so the selected-item tick never renders — an app-wide sweep was offered and not taken.

**bootstrap-select**
- Set a value with `$(el).selectpicker('val', value)`. Never `.val(x).selectpicker('refresh')` — `refresh` only re-syncs the UI and can miss the value.
- **Don't use `data-container="body"`.** It was tried and rejected: bootstrap-select renders the portaled menu as an ugly full-width strip at the page bottom. If a dropdown is clipped, find the ancestor with `overflow: hidden` and remove it (that was the actual cause — `overflow:hidden` on `.ir-editor` added for rounded corners; corners were instead rounded on the header row, last row and Add button).
- Re-init on `livewire:navigated`, `morph.added`, and `shown.bs.modal`. Populate dependent pickers with the global `setOptions($(el), options)` helper in `public/assets/js/app.js`; the standard pattern is a jQuery `change` handler that does `$wire.dispatch('getX', {...})` and a `$wire.on('setX', ...)` that calls `setOptions`.
- In `wire:ignore` rows, **target elements by a stable row key, not by numeric index** — `wire:ignore` freezes the DOM, so ids/indices go stale when a middle row is removed. Element ids look like `ec_item_type_{key}` and the server row index is computed from the card's live DOM position.

**Cleave.js quantity inputs** — every quantity field is a Cleave input, so `"1,000"` reaches the server. Each write path calls `sanitizeQuantities()` **first**, before validation and before the values are used; otherwise the `numeric` rule fails and `(float)` truncates to `1`. Wired into StockIn/StockOut/Waste `submit()`, Transfer `submit()`/`confirmLoad()`/`confirmReceive()` (also `received_quantity`), and Recipe. Decimals survive (`,` is thousands, `.` is decimal). Call `triggerCleave()` after adding rows.

**`format_quantity()`** (`app/Helpers/helpers.php`, alongside `authUser` / `authorizeRequest`) formats DB values on edit-load — thousands separators, trailing zeros trimmed, lossless to 6 decimals (`1250.000000` → `1,250`; `1250.123456` stays intact). Only safe on pages whose save path strips commas. Applied in the edit-load maps of StockIn/StockOut/Waste/Transfer and Recipe. It preserves `null` for nullable decimal columns rather than turning them into `""`.

**Livewire**
- Percentage-recomputing inputs use `wire:model.live.debounce.600ms` — plain `.live` round-trips on every keystroke and clobbers fast typing.
- View state that must survive re-renders (the Board/List switcher, the board mode) is a Livewire property with `#[Url]`, **not** a Bootstrap tab — a DOM-only active state resets on every re-render.
- When grouping rows in a Blade partial, **preserve each row's original array index** as the key, or `wire:model="rows.{i}.field"` binds to the wrong row.

**Typed returns + catch blocks** — `TransferCreate::submit/confirmLoad/confirmReceive` declared `: mixed` but their `catch` blocks only dispatched without returning, so any inventory throw produced *"Return value must be of type mixed, none returned."* Fixed with `return null;`. An app-wide audit found no other instance: Stock In/Out/Waste action methods have no return type and their index catches `return $this->dispatch(...)`.

**CSS specificity in this template** — Bootstrap's `.table > :not(caption) > * > *` (0,1,1) beats a plain class selector (0,1,0), and a `td { padding: 0 !important }` reset (0,1,1) beats `.some-class { padding: … !important }` (0,1,0). This silently defeated the entire intended look of the grouped-items header band (padding, indigo left accent bar, tint) — it had *never* rendered. Fix by scoping the reset (`border-bottom: 0`) and raising specificity (`.aqt-table td.aqt-group-title`), not by sprinkling `!important`.

**Design system** — the project already has a "Plan View" language in `public/assets/css/production.css`: `pv-header`, `pv-title`, `pv-chips`/`pv-chip`, `pv-stats`/`pv-stat--primary|success|warning`, `pv-board`, `pvc-card`, `pv-empty`. Reuse it rather than inventing new looks. Other blocks: `sv-*` (stock/transfer detail pages), `ir-*` (the compact row editor), `aqt-*` (the grouped action-quantity table, shared between the event-type modal and the plans status-action modal), `pbc-*` (board cards). Vuexy-isms like `bg-label-*` and `avatar` do **not** exist here. `production.css` is served with `filemtime` cache-busting — no build step.

**Tooling**
- `php artisan tinker` hangs on an interactive PsySH trust prompt — write a standalone script that boots Laravel directly instead.
- PowerShell swallows `$vars` in inline PHP; write a script file. Piping through `Select-String` has triggered trust prompts.
- Verification pattern used throughout: `php -l`, a full Blade compile check (`view:cache` then `view:clear`), and rolled-back DB round-trip scripts. The **live UI can't be exercised** — the app sits behind the external auth-service login. The in-app Browser pane also can't screenshot (not composited) and blocks `file://` and CSP-external CSS, so visual verification was done by measuring computed styles in a self-contained repro.
- `rows` is a reserved word in MySQL — alias it.

---

## 4. Session log

### 4.1 PlanBoard event modals and tracking — through 2026-08-11 *(largest, 1516 messages)*

The single longest thread; it built essentially the whole event-tracking and inventory-integration story, in this order.

1. **Status modals + logging tables.** Start (recipe) opens a modal of `recipe_inputs` with original qty disabled, a used-qty input, a live "% Used", and one global notes textarea; End shows produced item + side products; Pause/Resume take a reason and a type with expected vs actual duration. Created `event_status_logs`, `event_quantities`, the `EventStatusLog`/`EventQuantity` models and `Event::statusLogs()`/`quantities()`, plus a per-row history button opening a read-only timeline. Also renamed `recipe_inputs.input_type` → `item_type_id` and widened it from tinyint to `unsignedBigInteger` (it stores API item-type ids), updating `RecipeCreate` in both places.
2. **Pause reworked.** Pause modal became reason-only; a separate "Activities" button while paused adds multiple types at once. The migration batch was rolled back and rebuilt rather than patched: `pause_event_type_id` and `expected_duration` dropped from `event_status_logs`, new `event_pause_activities` table + model.
3. **Bug — old pause activities invisible.** They were being filtered to the *latest* pause log, so anything from an earlier pause (or from before logging existed, hence unlinked) vanished. The modal now lists every activity for the event; the history modal gained a block for unlinked legacy ones.
4. **Five changes:** percentage → variance; a time field in every modal defaulting to now (new `happened_at` on both log tables) driving all downstream math; ability to end an emergency with note + time; "pause activity" → **Emergency Event** and "Terminate" → **End** in the UI only.
5. **Ongoing-emergency guards** on Resume and End, plus the cleanup-mode Emergency button so the rule is always satisfiable. End notes surfaced in the history modal.
6. **Three board grids** — Planned / Actual / Downtime (see §2).
7. **List view planned-vs-actual + delay badge** (`eventActualTimes()` reading `happened_at` from the start/terminate logs), and **all add-event dropdowns converted to selectpicker** with the `ec-cascade` → `setOptions` pattern. Item Type became always-rendered to keep the shared picker's DOM stable, which required guarding `onItemTypeChanged` so changing it on a non-recipe event no longer wipes the event-type duration.
8. **Item tables grouped by item type** (Raw Material / Packaging headers, count pill, `.aqt-group-title`), preserving original row indices. Groups appear in first-appearance order.
9. **Reason made required** on emergency submit and on resume (new `reason` column).
10. **Non-recipe event types** — Item Type hidden and stored null; the emergency modal gained an items/unit/qty-used table driven by the type's `item_type_ids`; `event_pause_activity_id` FK added to `event_quantities`; used items surfaced in the recorded-emergencies table and both history sections.
11. **Non-recipe start** got the same popup with Original Qty hidden — then % Difference hidden too (same `showOriginal` flag, colspan corrected).
12. **Inventory integration** into the lifecycle (reserve on start/emergency-start, confirm on end/emergency-end, produced + side products added in).
13. **Explicit availability pre-check** (`stockAvailable`) before any write, aggregating duplicate item/unit rows and treating a missing inventory row as zero; the `reserveOut` + `applyOrFail` remains as a race-condition backstop. Plus the 600ms debounce.
14. **Placement/lane guards, placeable-based warehouses, and the Transfer `: mixed` bug** + app-wide audit.
15. **`event_type_items`** table, model and modal editor.
16. **`quantity_in_process`** added to the parent with four new ops; events migrated off the pending columns; In Process column + stat card on the inventory page. Verified end-to-end against the parent DB in a rolled-back transaction, confirming `quantity_pending_out` stays untouched through every event op.
17. **`warehouse_id` + `confirmed_at` on `event_quantities`**, and event/emergency movements merged into the warehouse activity timeline.

Ended on an open design question: **which warehouse each item type is drawn from when an event's inputs span several warehouses.** Recommendation given, not implemented — a JSON override map on the preparation/line (`rm_item_type_warehouses = {itemTypeId: warehouseId}`) defaulting to `rm_warehouse_id`/`sfg_warehouse_id`, edited in the preparation/line modal after event types are picked, resolved at *start* only since `event_quantities.warehouse_id` already makes confirm correct. Rejected alternatives: production-line/department level (siblings can differ), on the item in the parent DB (an item sits in several warehouses), auto-pick by stock (implicit and ambiguous), ask at start (adds the action the user ruled out). Open sub-decision: item-type granularity vs item-level.

*Session note: started on Fable 5, switched to Opus 4.8 partway when credits ran out, later Opus 5.*

### 4.2 Add/remove rows UI and UX improvements — 2026-08-11 (371 messages)

1. **Row-editor redesign** across stock in / out / waste / transfer / recipe. Replaced tall stacked per-item cards with a dense aligned table (`# · Item · Unit · Quantity · ⋯`) with a header row and a live count badge; **the Add button moved to the bottom** as a full-width dashed button — the direct fix for having to scroll up after filling the last row. Remove is a small trash button, disabled when one row remains. On phones each row stacks into a labelled card. Shared `.ir-*` CSS.
2. **Auto-select the unit when an item has exactly one** — the old hook was dead code (misnamed `updatedRawMaterials`); the logic went into `getItemUnits`. Recipe and item-requests already did this (item-requests has a correctly-named `updatedItems` hook), so no change was needed there.
3. **Recipe side products made optional** — blank rows skipped in validation and save, section marked "Optional".
4. `data-container="body"` tried, then reverted (see §3).
5. `selectpicker('val', …)` correction, applied to the four new handlers plus a pre-existing recipe one. Recorded in memory.
6. `format_quantity()` on edit-load for the four stock pages.
7. The blank-remove-button investigation → the `ti-*` icon-font discovery, all five create views swapped to `bi-*` (including submit/approve buttons, which were also silently iconless). Recorded in memory.
8. Cleave + format-on-edit + `sanitizeQuantities()` wired into the recipe component.

Session hit the usage limit mid-verification of step 8.

### 4.3 Stock out/waste/transfer create components — 2026-08-06 (732 messages)

1. **Warehouse → items → units cascade** replicated from StockIn onto StockOut, Waste and Transfer. Dropped the eager `/v1/items?item_type=Raw Material` load in favour of `#[On('getWarehouseItems')]` → `/v1/warehouses/{id}/items`, and `#[On('getItemUnits')]` → `/v1/items/{id}`. Renamed `rawMaterials` → `stockOutItems` / `wasteItems` / `transferItems` everywhere including validation keys and labels. **Transfer** limits items to item types common to *both* warehouses (destination's types from `/v1/warehouses/{to}/item-types`, intersected against the source's items by `item_type_id`). Fixed a latent 500: StockOut/Waste edit referenced a non-existent `inputs` relation — only `reportItems` exists.
2. **Pending quantities** added locally (`quantity_pending_in/out`, decimal(10,2)) with model helpers centralizing the math, wired into create/edit/approve/delete for all four documents. The Transfer split (source deducted at Load) was an explicit choice by the user when asked. Verified by running the whole lifecycle against a throwaway inventory row.
3. **View blades fixed** — they'd been written before the API split and referenced Eloquent relations that don't exist. Added `warehouseName()` / `resolvedRows()` resolvers using data already loaded in `mount()`, dropped a broken `->company` line, fixed wrong Back routes, added missing view buttons to the index blades, and tied view-mode authorization to the matching `item*-view` permission. This is where the create-gate slug mismatch was discovered.
4. **View blades redesigned** on the existing `pv-*` Plan View language plus new `sv-*` styles: header band with status pill, stat tiles, a notes panel (notes had never been displayed), an items board with circular row numbers, and for Transfer a From → To route strip and a Received Qty column. A hosted preview artifact was published because the Browser pane couldn't render local files.
5. **Delete button hidden for approved stocks**; `WarehouseInventoryIndex` sourced from the model with pending columns and a pending summary in the modal.
6. **The move to the parent DB** — created the parent migration, model, controller and routes; built `InventoryService`; migrated **9 components** to batched `applyOrFail()` calls; dropped the per-component availability pre-checks; deleted the local model and staged the drop migration. Delivered with an explicit 4-step deploy sequence.
7. Item and unit **names in shortage messages** (resolved parent-side, where the models live).
8. **Cleave comma stripping** (`sanitizeQuantities()`).

Ended with the request that opened session 4.2.

### 4.4 Event-type-index item table spacing — 2026-07-31 (109 messages)

The item-type name in the event-type modal's grouped item table was jammed against the table border. Root cause was pure CSS specificity, and worse than it looked: padding, the indigo left accent bar **and** the tint band were all being overridden, so the intended design had never rendered at all. Fixed at the root in `production.css` (scope the reset to `border-bottom`, raise the selector to `.aqt-table td.aqt-group-title`, drop the now-unneeded `!important`), plus `me-1` on the tag icon to match the canonical plans version. Verified by measuring computed styles in a throwaway repro that linked the real stylesheets, since screenshots time out. Before → after: padding-left 0 → 12px, band height 20px → 36px, accent bar 0 → 3px indigo, tint transparent → `rgba(129,140,248,.10)`. The `.aqt-*` CSS is shared with the plans status-action modal, which gets the same correction.

### 4.5 Warehouse inventory activity popup — 2026-07-31 (107 messages)

Rebuilt the Check Activity popup: pending as well as approved rows, `movementMeta()` bucketing, clickable summary-card filters, `#id` links to the source document behind the right permission, and the four new quantity columns with a running Stock Total. Flagged at the time that the In Process column would stay empty because events create `EventQuantity` rows rather than `ReportItem`s — which the 2026-08-11 session then wired in.

### 4.6 Production lines dropdown in UserController — 2026-07-13 (111 messages)

The Warehouses → Production Lines → Preparations + Lines cascade (see §2). Migration run and verified against local data.

### 4.7 Events crossing midnight handling — 2026-07-12 (185 messages)

Two rounds. First, `PlanCarryOverService` auto-creating the next-day plan (and month plan on a boundary) when an event wraps past midnight, hooked into `PlanBoard::dropEvent` and `EventCreate::submit`, with a toast naming what was created. Then, on the user's own suggestion and after a design discussion, the deeper fix: DATETIME columns + `to_plan_id` + multi-day rendering. `migrate:fresh --seed` was explicitly authorized. On request the schema was folded into `create_events_table` and the alter migration deleted — along with `alter_events_from_time_nullable`, which would otherwise have reverted the column type. 25 rolled-back checks pass. Heads-up recorded: any other environment on this migration history needs `migrate:fresh`.

### 4.8 PlanBoard event list view with tabs — 2026-07-06 (124 messages)

Board | List segmented switcher backed by a `#[Url]` property (chosen over Bootstrap tabs precisely because the list's action buttons trigger Livewire re-renders). New `_event-list.blade.php` renders the day chronologically — carry-overs first (muted, no actions, since they belong to yesterday's plan), then placed and unplaced by start time — with time (+ cross-midnight markers), event type with color dot and batch count, recipe, duration, production line, lane or an Unplaced badge, status badge and actions. `updateEventStatus()` introduced with the validated transition map, and the details modal gained friendly status labels. Noted: everyone with `production.event-create` can also drive lifecycle; a dedicated `production.event-status` permission would need adding in the auth service.

### 4.9 Sessions whose transcripts were cleaned up

No content recoverable — titles and dates only. Judging by the surrounding work, these covered the plan-board foundations that later sessions build on.

| Session | Last activity |
|---|---|
| Cross-database user role linking | 2026-07-07 |
| PlanBoard navigation and event modal updates | 2026-07-03 |
| MonthPlan model and factory validation | 2026-07-01 |
| PlanBoard 15-minute time slots | 2026-07-01 |

*(Excluded as other projects: "Gym subscription website" → `gym-saas`; "ClientBalanceSummaryExport per branch" → `operation.atj`.)*

---

## 5. Open items

1. **Multi-warehouse item sourcing for events** — designed, not built (§4.1). Awaiting go-ahead and the item-type vs item-level decision.
2. **Parent deploy sequence** — `auth-service` migrate → copy inventory rows from `production_atj.warehouse_inventories` → deploy production.atj → run the local drop migration. Confirm what's already been run.
3. **In-flight events from before `quantity_in_process`** reserved into `quantity_pending_out`; ending them now confirms out of `in_process`, leaving stuck pending. End them or migrate the held amounts.
4. **Events started before the inventory integration** have no reservation at all, so confirm-out at end will fail for them.
5. **`ti-*` selectpicker tick icons** still don't render app-wide (`data-icon-base="ti" data-tick-icon="ti-check"`).
6. **Item-requests quantity fields** still cast `(float)` without stripping commas — do not add Cleave or `format_quantity()` there until the save path sanitizes.
7. **Create-permission slug mismatch** — `stockIn-create` / `stockOut-create` / `rawMaterialWaste-create` don't exist; non-super-admins get 403 on those create/edit pages.
8. **Dedicated `production.event-status` permission** if operators should run events without edit rights.
9. **Idempotency key + reconcile job** on `apply` if cross-service atomicity ever needs to be bulletproof.
10. **Quantity column headers** still read "% Used" / "% Produced" though the value is now a signed variance.
11. **Units fallback in the emergency items load** — if `/v1/items` doesn't return embedded units, it falls back to one API call per item (cached per request). A batch endpoint would fix it if it proves slow.
12. **Optional polish offered, never taken:** a ghost "planned" bar behind Actual cards to visualize slippage; deleting approved records unwinding actual stock; cleanup of empty auto-created carry-over plans; a fixed group order (Raw Material always before Packaging) instead of first-appearance.
