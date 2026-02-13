# Refactor Roadmap (Jira-Ready)

## Context
- Goal: backend reusable, simple, and consistent across work-item types.
- Goal: move business logic out of controllers and Blade views into dedicated application/domain layers.
- Note: SQLite is not required for production. It is used here as fast in-memory DB for automated tests. Migrations must stay DB-driver compatible so tests remain reliable.

## Current Progress (Completed)
- `TASK`: Fix migration compatibility on `units` refactor for SQLite test runs.
- `TASK`: Make JSON performance index migration conditional for MySQL only.
- `TASK`: Restore `PlinthCeramicFormula` compatibility class and polymorphic naming in trace mode.
- `Result`: `PlinthCeramicFormulaTest` now passes.

## Refactor Status Tracker (Updated: 2026-02-13)

### Epic 4 - Material CRUD Platformization
- `STATUS`: In Progress

#### Story 4.1 - Reusable Form Requests
- `[x]` Create per-domain Form Requests for create/update rules (web + API material controllers).
- `[x]` Move inline `store/update` validation arrays from controllers to Form Requests.
- `[~]` Move index filter validation to Form Requests/query objects.
  - Stage-1 done: shared index query objects introduced (`MaterialIndexQuery`, `MaterialApiIndexQuery`) and wired to web + API material index endpoints.
  - Stage-2 done: shared lookup query object introduced (`MaterialLookupQuery`) and wired across web + API read/filter endpoints (`getFieldValues`, `getAllStores`, `getAddressesByStore`).
- `[~]` Complete explicit reuse mapping between web + API validators for all read/filter endpoints.
  - Stage-1 done: shared lookup spec introduced (`MaterialLookupSpec`) for allowed field/filter mapping and adopted across web + API material lookup endpoints.

#### Story 4.2 - Material Write Actions
- `[x]` Extract duplicate photo upload/delete flow to `MaterialPhotoService` and wire into web material controllers (`Brick/Cement/Sand/Cat/Nat`).
- `[x]` Centralize duplicate detection invocation into `MaterialDuplicateService::ensureNoDuplicate()` across web material controllers (`Brick/Cement/Sand/Cat/Nat/Ceramic`).
- `[~]` Create generic actions: `CreateMaterialAction`, `UpdateMaterialAction`, `DeleteMaterialAction`.
  - Stage-1 done: actions created and wired into web controllers (`Brick/Cement/Sand/Cat/Nat`) for create/update/delete persistence calls.

#### Story 4.3 - Material Query Layer
- `[~]` Introduce shared query/filter object for search + sorting + pagination.
  - Stage-1 done: `MaterialIndexQuery` + `MaterialIndexSpec` introduced and used by web material index controllers.
  - Stage-2 done: API index parameter resolution + index execution branch unified through `MaterialApiIndexQuery` across material API controllers.
  - Stage-3 done: read/filter parameter contract unified via `MaterialLookupQuery` across web + API material lookup endpoints.
- `[~]` Replace repeated `allowedSorts` blocks with configurable query spec per material.
  - Stage-1 done: repeated index `allowedSorts`/`sortMap` logic removed from `Brick/Cement/Sand/Cat/Nat/Ceramic` web controllers.
  - Pending: finalize residual validation contract alignment for read/filter endpoints.

### Epic 5 - Thin Controllers (Web + API)
- `STATUS`: In Progress

#### Story 5.1 - MaterialCalculationController Refactor
- `[~]` Stage-1 thinning done: repeated validation rules extracted into dedicated private methods.
- `[~]` Stage-2 routing split done: routes separated into `MaterialCalculationPageController`, `MaterialCalculationExecutionController`, and `MaterialCalculationTraceController` (behavior preserved via inheritance).
- `[~]` Stage-3 endpoint ownership done: routed methods now declared explicitly on split controllers via `parent::...` wrappers (zero behavior change).
- `[x]` Stage-4 body migration done: all routed web endpoints are now implemented directly on split controllers (`MaterialCalculationPageController`, `MaterialCalculationExecutionController`, `MaterialCalculationTraceController`) with equivalent logic.
- `[~]` Stage-4 parity hardening done: local execution validation helper methods restored in `MaterialCalculationExecutionController` to preserve request-validation behavior for direct methods.
- `[x]` Split controller into `Page/Execution/Trace` controllers.
- `[ ]` Enforce target: no method > 80 LOC.

#### Story 5.2 - API V1 Calculation Controller Refactor
- `[~]` Stage-1 thinning done: repeated validation rules extracted into dedicated private methods.
- `[~]` Stage-2 routing split done: API v1 calculation routes separated into `CalculationReadApiController`, `CalculationWriteApiController`, and `CalculationExecutionApiController` (behavior preserved via inheritance).
- `[~]` Stage-3 endpoint ownership done: routed methods now declared explicitly on split API controllers via `parent::...` wrappers (zero behavior change).
- `[x]` Stage-4 body migration done: all routed read/write/execution endpoints are now implemented directly on split API controllers (`CalculationReadApiController`, `CalculationWriteApiController`, `CalculationExecutionApiController`) with equivalent logic.
- `[ ]` Move orchestration logic to application actions/resources.
- `[~]` Add/verify API schema contract tests after split.
  - Stage-1 done: `CalculationApiSchemaContractTest` added for split endpoints and validates execution endpoint error contracts (`calculate`, `preview`, `compare`, `trace`).
  - Stage-1 note: read/write not-found contract checks (`index`, `show`, `update`, `destroy`) are present but skipped when `brick_calculations` table is unavailable in current test environment.

---

## Epic 1 - Stabilize Baseline & Refactor Safety Net
**Epic Summary**: Ensure refactor can run safely with deterministic tests and migration compatibility.

### Story 1.1 - Cross-DB Migration Compatibility
- `TASK` Audit all migrations for engine-specific SQL (`JSON_UNQUOTE`, generated columns, index syntax).
- `TASK` Add DB-driver guards (`mysql`, `pgsql`, `sqlite`) for non-portable statements.
- `TASK` Ensure every `down()` path is symmetrical and safe.
- `Acceptance Criteria`:
  - Targeted migration suite runs in testing DB without SQL errors.
  - `php artisan test --filter=Migration` (or equivalent targeted suite) passes.

### Story 1.2 - Test Bootstrap Standardization
- `TASK` Standardize `.env.testing` and testing DB configuration.
- `TASK` Ensure `RefreshDatabase` usage is consistent on DB-dependent tests.
- `TASK` Add `tests/Feature/Smoke/DatabaseSmokeTest.php` for core table existence.
- `Acceptance Criteria`:
  - Full test command runs with consistent DB setup.
  - No flaky migration/bootstrap failures across reruns.

### Story 1.3 - Architecture Guardrails
- `TASK` Add architecture tests for controller thinness and forbidden direct heavy logic.
- `TASK` Add guard tests to detect oversized controller methods and Blade inline business logic.
- `Acceptance Criteria`:
  - Guard tests fail when business rules are reintroduced into controllers/views.

---

## Epic 2 - Formula Domain Refactor (Reusable Calculation Core)
**Epic Summary**: Remove duplication in formula classes and centralize shared calculation primitives.

### Story 2.1 - Formula Contracts & DTO
- `TASK` Introduce `CalculationInputData` DTO (validated normalized payload).
- `TASK` Introduce `CalculationResultData` DTO (typed output for UI/API).
- `TASK` Standardize formula trace format contract.
- `Acceptance Criteria`:
  - Formula classes consume DTO, not raw request arrays.
  - Result structure is consistent across all formulas.

### Story 2.2 - Shared Formula Base & Helpers
- `TASK` Create `AbstractFormula` with shared validation helpers and material loading.
- `TASK` Extract repeated mortar/grout/tile math into reusable service classes.
- `TASK` Refactor formula classes (`Brick*`, `Tile*`, `Plinth*`, `Grout*`) to use shared helpers.
- `Acceptance Criteria`:
  - Formula LOC reduced significantly.
  - No duplicated calculation blocks across formula classes.

### Story 2.3 - Formula Registry Hardening
- `TASK` Add deterministic formula discovery order.
- `TASK` Prevent duplicate formula code registration at runtime.
- `TASK` Add unit tests for registry conflict behavior.
- `Acceptance Criteria`:
  - Duplicate code detection yields clear exception/log.
  - Registry output stable across environments.

---

## Epic 3 - Calculation Orchestration Decomposition
**Epic Summary**: Break down giant calculation services/controllers into focused use-case actions.

### Story 3.1 - Action-Based Use Cases
- `TASK` Create actions: `PrepareCalculationContextAction`, `GenerateCombinationsAction`, `BuildPreviewPayloadAction`, `PersistCalculationAction`.
- `TASK` Move orchestration logic from controllers/services into these actions.
- `Acceptance Criteria`:
  - Controllers only validate input and call actions.
  - End-to-end behavior remains unchanged (feature tests pass).

### Story 3.2 - Combination Service Split
- `TASK` Split `CombinationGenerationService` into modules:
  - `FilteringEngine`
  - `PricingEngine`
  - `StoreCoverageEngine`
  - `RecommendationEngine`
- `TASK` Add contract interfaces and integration tests per module.
- `Acceptance Criteria`:
  - Each module testable in isolation.
  - Main service acts as thin orchestrator.

---

## Epic 4 - Material CRUD Platformization
**Epic Summary**: Unify repeated CRUD patterns across Brick/Cement/Sand/Cat/Nat/Ceramic controllers.

### Story 4.1 - Reusable Form Requests
- `TASK` Create per-domain Form Requests for create/update/index filters.
- `TASK` Move all inline `$request->validate()` rules to Form Requests.
- `Acceptance Criteria`:
  - Controllers no longer contain raw validation rule arrays.
  - Validation rule reuse between web + API is explicit.

### Story 4.2 - Material Write Actions
- `TASK` Create generic actions: `CreateMaterialAction`, `UpdateMaterialAction`, `DeleteMaterialAction`.
- `TASK` Extract duplicate photo upload/delete flow to `MaterialPhotoService`.
- `TASK` Centralize duplicate detection invocation to one pipeline.
- `Acceptance Criteria`:
  - Store/update flow behavior equivalent across material types.
  - Duplicate photo/transaction code removed from controllers.

### Story 4.3 - Material Query Layer
- `TASK` Introduce query/filter object for search + sorting + pagination.
- `TASK` Replace repeated `allowedSorts` blocks with configurable query spec per material.
- `Acceptance Criteria`:
  - Index endpoints share one query pattern.
  - Sorting/search behavior remains backward compatible.

---

## Epic 5 - Thin Controllers (Web + API)
**Epic Summary**: Convert fat controllers into thin HTTP adapters.

### Story 5.1 - MaterialCalculationController Refactor
- `TASK` Split controller responsibilities into:
  - `MaterialCalculationPageController`
  - `MaterialCalculationExecutionController`
  - `MaterialCalculationTraceController`
- `TASK` Keep one controller per bounded responsibility.
- `Acceptance Criteria`:
  - No method > 80 LOC in target controllers.
  - Shared domain logic only inside actions/services.

### Story 5.2 - API V1 Calculation Controller Refactor
- `TASK` Move business logic to application actions + resources.
- `TASK` Keep API controller focused on request/response mapping.
- `Acceptance Criteria`:
  - API and web layers reuse same calculation use-cases.
  - Contract tests for API response schemas pass.

---

## Epic 6 - View Logic Extraction (Blade Cleanup)
**Epic Summary**: Remove business and transformation logic from Blade.

### Story 6.1 - ViewModel / Presenter Layer
- `TASK` Add presenters for:
  - preview combinations
  - material calculations create/edit
  - material listing page payloads
- `TASK` Move conditional formatting and aggregation logic to presenters.
- `Acceptance Criteria`:
  - Heavy `@php` blocks reduced drastically.
  - Blade files mostly declarative rendering.

### Story 6.2 - Blade Components
- `TASK` Extract reusable components:
  - filter bars
  - material cards/rows
  - pricing/summary panels
  - recommendation tables
- `TASK` Standardize component props and naming.
- `Acceptance Criteria`:
  - Shared UI fragments no longer duplicated across views.
  - Rendering parity verified by feature/snapshot tests.

---

## Epic 7 - Domain Rules, Types, and Consistency
**Epic Summary**: Improve maintainability with typed domain objects and explicit rules.

### Story 7.1 - Enums & Value Objects
- `TASK` Create enums for work types, price filters, material categories.
- `TASK` Create value objects for money, dimensions, coordinates where needed.
- `Acceptance Criteria`:
  - String literals for core domain states replaced by typed objects/enums.

### Story 7.2 - Rule Normalization
- `TASK` Centralize reusable numeric/range/coordinate validation rules.
- `TASK` Add unit tests for each shared rule set.
- `Acceptance Criteria`:
  - Same validation behavior across web and API for shared fields.

---

## Epic 8 - Quality Gates, Rollout, and Documentation
**Epic Summary**: Lock in quality and prevent architecture regression.

### Story 8.1 - Test Coverage Expansion
- `TASK` Add feature tests for critical calculation workflows per work type.
- `TASK` Add unit tests for formula helper modules and orchestration actions.
- `Acceptance Criteria`:
  - Critical calculation paths covered by automated tests.

### Story 8.2 - Static Analysis & CI Guardrails
- `TASK` Enforce Pint + static analysis in CI.
- `TASK` Add architecture checks to CI pipeline.
- `Acceptance Criteria`:
  - Pull requests fail when architecture constraints are violated.

### Story 8.3 - Refactor ADR & Dev Guide
- `TASK` Document architecture decisions and coding conventions.
- `TASK` Add migration playbook for formula/material module additions.
- `Acceptance Criteria`:
  - New contributor can add one work-item formula via documented workflow only.

---

## Suggested Execution Order (Incremental)
1. Epic 1
2. Epic 2
3. Epic 3
4. Epic 5
5. Epic 4
6. Epic 6
7. Epic 7
8. Epic 8

## Suggested Jira Labels
- `refactor`
- `backend-architecture`
- `formula-engine`
- `controller-thinning`
- `blade-cleanup`
- `test-stability`
- `migration-safety`
