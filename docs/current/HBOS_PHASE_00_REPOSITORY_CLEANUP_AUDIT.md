# HBOS Phase 0 — Repository Cleanup Audit
**Program**: SkyraSoft HBOS Small Retail Transformation Program  
**Phase**: Phase 0 — Repository Cleanup & Baseline Normalization  
**Prompt**: Prompt 1 of 3 — Repository Inventory, Classification & Cleanup Plan  
**Date**: October 3, 2026  
**Status**: COMPLETE (Analysis & Classification Only — Non-Destructive)

---

## 1. Executive Summary & Repository Baseline

The HBOS repository has undergone significant engineering, architecture audits, 10-phase system certification, platform hardening, retail workforce expansion, and automated verification. This comprehensive audit establishes the exact, authoritative inventory of all repository contents prior to beginning the Small Retail Transformation Program.

### Key Baseline Metrics
- **Git Branch**: `main`
- **Git Commit**: `fa71e856801d6ae0d647891d379a42a4c27c7558`
- **Tracked Files in Git**: 421
- **Working Tree Changes / Untracked Entries**: 128
- **Total Repository Files Scanned** (excluding vendor/node_modules/cache): 906
- **Documentation Files (`docs/`)**: 82
- **Scratch / Audit Files (`scratch/`)**: 324
- **Automated Feature & Unit Test Files (`api/tests/`)**: 67
- **Database Schema Migrations (`api/database/migrations/`)**: 66
- **Database Seeders (`api/database/seeders/`)**: 4
- **Backend Eloquent Models (`api/app/Models/`)**: 33
- **Backend API Controllers (`api/app/Http/Controllers/`)**: 32
- **Frontend Vue Views (`Frontend/src/views/`)**: 40
- **Frontend Vue Components (`Frontend/src/components/`)**: 9
- **Platform Packaging Shells**: 2 (`desktop/tauri.conf.json`, `android/capacitor.config.json`)
- **Database Backups / Snapshots (`database_backups/`)**: 2
- **Backend Test Suite Execution**: **391 Tests Passed, 1741 Assertions, 0 Failures, 0 Skips (86.70s)**
- **Frontend Production Build**: **171 Modules Transformed, 0 Errors (1.90s)**

---

## 2. Git Worktree State & Tracking Inventory

Every file in the repository was inspected against `git status` and `git ls-files`.

| Git Category | Description | Count |
|---|---|---|
| `TRACKED_UNCHANGED` | Tracked files with clean worktree status | 382 |
| `TRACKED_MODIFIED` | Tracked files modified during recent platform/workforce phases | 39 |
| `TRACKED_DELETED` | Tracked files deleted in index/worktree | 0 |
| `UNTRACKED` | Newly created certified artifacts, tests, migrations, docs, scratch files | 485 |
| `IGNORED` | Files ignored via `.gitignore` (`api/.env`, `node_modules`, `vendor`, build caches) | N/A |

### Untracked Files Safety Assurance
Untracked files were not assumed to be disposable. In particular:
- Untracked migrations (`2026_10_02_*`, `2026_10_03_*`) represent essential production schema additions.
- Untracked tests (`Phase9MigrationUpDownTest.php`, `RetailWorkforcePrompt2Test.php`, etc.) represent active regression suites.
- Untracked commands (`BackfillSaleItemSnapshotsCommand.php`, `AuditKhataLedgerDiscrepanciesCommand.php`) represent essential operational tooling.

---

## 3. Production Boundary Inventory

The production boundary encompasses all core runtime source code, schema migrations, configuration files, and client distributions:

### Authoritative Production Boundaries
1. **Backend Application**: `api/app/` (Controllers, Models, Services, Middleware, Requests, Console Commands) — `ACTIVE_PRODUCTION`
2. **Backend Configuration**: `api/config/` (App, Database, Auth, CORS, HBOS Versioning) — `ACTIVE_CONFIGURATION`
3. **Database Schema**: `api/database/migrations/` (66 migration files) — `ACTIVE_SCHEMA` (Immutable history)
4. **Database Seeders**: `api/database/seeders/` (Roles, Permissions, Defaults) — `ACTIVE_CONFIGURATION`
5. **API Routing**: `api/routes/api.php` — `ACTIVE_PRODUCTION`
6. **Frontend Application**: `Frontend/src/` (Views, Components, Stores, Services, Router, Assets) — `ACTIVE_PRODUCTION`
7. **Frontend Build & Tooling**: `Frontend/package.json`, `Frontend/vite.config.js`, `Frontend/index.html` — `ACTIVE_BUILD_CONFIGURATION`
8. **Client Packaging Shells**: `desktop/tauri.conf.json`, `android/capacitor.config.json` — `ACTIVE_BUILD_CONFIGURATION`

---

## 4. Documentation Inventory & Classification (82 Files)

The 82 documentation files across historical development programs were analyzed and classified:

### Summary by Classification
- **`KEEP_ACTIVE` (Authoritative Operational & Phase 0 Guides)**: 12
- **`KEEP_REFERENCE` (Authoritative Architecture & Data Lineage Audits)**: 23
- **`ARCHIVE_HISTORICAL` (Completed Phase Certifications, Implementation Reports & Logs)**: 47
- **`DELETE_SAFE` / `DELETE_OBSOLETE`**: 0 (Preserving full historical auditability in Phase 0 Prompt 1)

### Key Authoritative Current Documents
- `docs/HBOS_DEFINITIVE_MANAGEMENT_SYSTEM_OPERATING_GUIDE.md` (System Master Manual)
- `docs/HBOS_END_TO_END_OPERATING_SOPS.md` (Operational Procedures)
- `docs/HBOS_PAGE_TAB_OPERATOR_MANUAL.md` (Operator UI Reference)
- `docs/HBOS_ADMIN_SUPPORT_AND_RECONCILIATION_GUIDE.md` (Support & Reconciliation Manual)
- `docs/HBOS_PUBLIC_ACCOUNT_SUPPORT_RUNBOOK.md` (Tenant & Support Runbook)
- `docs/HBOS_WINDOWS_RELEASE_AND_UPDATE_RUNBOOK.md` (Windows Distribution Runbook)
- `docs/HBOS_BUSINESS_ONBOARDING_OPERATOR_FLOW.md` (Business Onboarding Guide)
- `docs/HBOS_BRANCH_MANAGEMENT_OPERATOR_FLOW.md` (Branch Lifecycle Guide)
- `docs/HBOS_EMPLOYEE_ACCOUNT_AND_PROFILE_FLOW.md` (Employee Governance Guide)
- `docs/HBOS_PHASE_00_REPOSITORY_CLEANUP_AUDIT.md` (Phase 0 Audit)
- `docs/HBOS_PHASE_00_CLEANUP_CLASSIFICATION_POLICY.md` (Phase 0 Policy)
- `docs/HBOS_PHASE_00_TARGET_REPOSITORY_STRUCTURE.md` (Phase 0 Structure)

---

## 5. Automated Test Suite Inventory (67 Files, 391 Tests)

All 67 test files in `api/tests/` protect system invariants and regression safety.

### Test Classification Breakdown
- **`KEEP_SECURITY_REGRESSION` (Auth, RBAC, Governance)**: 12 test suites
- **`KEEP_TENANCY_REGRESSION` (Tenant isolation, Multi-branch scoping)**: 14 test suites
- **`KEEP_OFFLINE_REGRESSION` (BulkSync, Device pairing, Conflict resolution)**: 6 test suites
- **`KEEP_RETAIL_REGRESSION` (Retail workforce, Staff lifecycle, Onboarding)**: 4 test suites
- **`KEEP_CORE_REGRESSION` (POS sales, Khata, Inventory, Purchases, Expenses, Financial accounts, Invariants)**: 31 test suites

### Preservation Rule
No test files are marked for deletion in Phase 0. All 391 automated tests (1741 assertions) pass cleanly and form the permanent safety net for the Small Retail Transformation Program.

---

## 6. Scratch Directory Inventory (324 Files)

The `scratch/` directory contains tools and artifacts accumulated over prior auditing and certification phases:

### Classification Breakdown
- **`KEEP_ACTIVE_TOOL`**: 5 (Phase 0 Audit & Verifier Scripts)
- **`KEEP_CURRENT_EVIDENCE`**: 6 (Phase 0 Authoritative Manifests & Registries)
- **`ARCHIVE_EVIDENCE`**: 54 (Historical JSON audit registers & test results)
- **`DELETE_ONE_OFF_SCRIPT` (Safe for Prompt 0.2 removal)**: 247 (One-off discovery/lineage population scripts whose data is persisted in markdown)
- **`DELETE_TEMPORARY`**: 12 (Temporary data dumps, intermediate scratch files)

---

## 7. Migration & Seeder Safety

- **Migrations (66 files)**: Evaluated 100% safe. All 66 migrations represent immutable historical schema transformations and must be retained as `REQUIRED_SCHEMA_HISTORY`.
- **Seeders (4 files)**: `RolesAndPermissionsSeeder.php`, `DatabaseSeeder.php`, etc., are active and required for tenant initialization.

---

## 8. Frontend & Backend Unused Code Candidates

- **Frontend Views**: 40 views analyzed. 34 are directly routed in `router/index.js`.
  - `ProductInvoiceView.vue`: Retained as `LEGACY_RETAINED` (Invoice print fallback template).
  - `reports1View.vue`: Retained as `LEGACY_RETAINED` (Legacy reports reference).
  - Unrouted child views (`BusinessSetup1View.vue`, `CompleteReviewView.vue`, etc.) are actively imported in multi-step workflows.
- **Frontend Components**: 9 components analyzed. All 9 are actively imported and rendered.
- **Backend Controllers**: 32 controllers analyzed. 31 are directly bound in `api/routes/api.php`. All are actively used.

---

## 9. Secret & User Data Safety

- **Secrets**: No private keys, certificates, or production secrets are committed in git. `api/.env` is ignored by `.gitignore`.
- **Database Backups**: Two SQL snapshots exist in `database_backups/` (`HBOS_database_snapshot.sql`, `HBOS_database_snapshot_utf8.sql`). They contain development test fixtures and are classified as `KEEP_REFERENCE`.

---

## 10. Conclusion & Handoff to Prompt 0.2

Phase 0 Prompt 1 discovery and classification is 100% complete. Zero files were deleted or moved. The complete machine manifest (`scratch/repository_cleanup_manifest.json`) and registries are generated and ready for Prompt 0.2 execution.
