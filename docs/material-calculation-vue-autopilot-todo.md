# Material Calculation Vue Migration - Autopilot TODO

## Goal
- Migrasi frontend `material_calculations/create.blade.php` bertahap ke modul JS terpisah (bridge-first), lalu menuju full Vue.
- Wajib tetap 1:1 dari sisi UI, layout, warna, style, dan logic.

## Current Status (Done)
- [x] Vue bridge foundation + loading/search/session change bridge.
- [x] Loading progress stuck fix.
- [x] Extract engine: page search.
- [x] Extract engine: taxonomy scroll tracker.
- [x] Extract engine: scroll FAB.
- [x] Extract engine: store search mode controls.
- [x] Extract engine: ceramic type filter autocomplete.
- [x] Extract engine: filter checkbox selection logic.
- [x] Extract engine: work taxonomy filters.
- [x] Extract engine: multi material type filters.
- [x] Extract engine: additional taxonomy autocomplete.
- [x] Extract engine: additional worktype autocomplete.
- [x] Extract engine: additional material type filters.
- [x] Extract engine: additional item focus helpers.
- [x] Extract engine: additional item header refresh.
- [x] Extract engine: bundle floor stable sort helper.
- [x] Extract engine: floor sort queue helpers.
- [x] Extract engine: main floor card sort.
- [x] Extract engine: rebuild floor order swap.
- [x] Extract engine: additional item list sort.
- [x] Extract engine: main taxonomy footer relocation.
- [x] Extract engine: additional taxonomy footer relocation.
- [x] Extract engine: inline taxonomy layout helpers.
- [x] Extract engine: additional row kind visibility logic.
- [x] Extract engine: additional field get/set helpers.
- [x] Fix sync bug: auto-open customize panel saat custom value tersinkron.

## Autopilot Rules
- Jangan ubah behavior user-facing tanpa kebutuhan bugfix.
- Setiap ekstraksi wajib:
  - tambah file engine di `public/js/vue/`
  - ubah fungsi inline menjadi wrapper + fallback aman
  - tambahkan `try/catch` untuk blok high-risk
  - `node --check` semua file engine
- Jika ada regresi layout/logic: stop ekstraksi baru, fix regresi dulu.

## Remaining Work Plan

### Phase 1 - Low Risk / High Win
- [x] Extract engine: dimension expression block
  - Functions utama: parser/evaluator/binder expression + summary update.
  - Acceptance:
    - input `2+3*4` ter-evaluate benar
    - hint expression muncul/hilang normal
    - submit payload tetap numeric seperti sebelumnya

### Phase 2 - Medium Risk
- [ ] Extract engine: additional work taxonomy autocomplete
  - Floor/Area/Field autocomplete + duplicate auto-merge hooks.
  - Acceptance:
    - add item/area/field tetap normal
    - sorting floor dan sticky taxonomy tetap jalan
    - no duplicate regression
  - Progress:
    - [x] `initAdditionalWorkTaxonomyAutocomplete` extracted to engine
    - [x] `initAdditionalWorkTypeAutocomplete` extracted to engine
    - [x] `createAdditionalWorkItemForm` extracted to engine
    - [x] `createAndFocusAdditionalWorkItem` + `showTaxonomyActionError` extracted
    - [x] `refreshAdditionalWorkItemHeader` extracted (with fallback)
    - [x] `sortBundleItemsByFloorStable` extracted (with fallback)
    - [x] `markFloorSortPending`/`flushPendingFloorSort`/`flushFloorSortWhenFocusLeaves` extracted
    - [x] `sortMainFloorCards` extracted (with fallback)
    - [x] `rebuildBundleUiFromSortedFloorOrder` extracted (with fallback)
    - [x] `sortAdditionalWorkItems` extracted (with fallback)
    - [x] `relocateMainTaxonomyActionButtonsToFooter` extracted (with fallback)
    - [x] `ensureAdditionalTaxonomyActionsFooter` extracted (with fallback)
    - [x] `getDirectAdditionalRowHost`/`getAdditionalRowLayoutParts`/`applyAdditionalInlineTaxonomyRowLayout` extracted
    - [x] `setAdditionalWorkItemRowKind` extracted (with fallback)
    - [x] `getAdditionalFieldValue` + `setAdditionalFieldValue` extracted (with fallback)
    - [ ] lanjut blok taxonomy/additional yang tersisa (event wiring + restore/session lane)

### Phase 3 - Medium/High Risk
- [ ] Extract engine: additional material type filters (per additional item)
  - Include dynamic extra rows + customize panel cloning.
  - Acceptance:
    - tambah/hapus row normal
    - sync shared custom antar material type sama tetap realtime
    - panel custom auto-open tetap konsisten
  - Progress:
    - [x] `initAdditionalMaterialTypeFilters` extracted to engine
    - [ ] validasi manual acceptance untuk additional material flow

### Phase 4 - High Risk (Session)
- [ ] Extract engine: session serialization/saving
- [ ] Extract engine: session restore/apply
  - Acceptance:
    - refresh/back-forward restore state 1:1
    - preview resume flow tetap benar
    - tidak ada submit/auto-submit regression

### Phase 5 - Final Hardening
- [ ] Review dependency cross-engine (shared helper collisions, event leaks).
- [ ] Naming cleanup + docs mini map engine.
- [ ] Prepare switch plan ke full Vue component (optional next stage).

## Regression Test Checklist (Manual)
- [ ] Map + Filter By grid tampil normal.
- [ ] Semua autocomplete open/close/filter/enter/escape normal.
- [ ] Additional item add/remove/reorder/sort normal.
- [ ] Bundle material + customize sync antar item normal.
- [ ] Loading overlay/progress normal sampai redirect hasil.
- [ ] Session restore normal (reload, back-forward, resume).

## Completion Criteria
- Semua checklist `Phase 1-5` selesai.
- Tidak ada error console blocker.
- Flow create calculation dari awal sampai hasil tetap 1:1.
