# HBOS Small Retail Transformation Program
# Phase 3: Adaptive UX / Navigation / Module Architecture
# Final Adaptive UX Certification Document

**Phase:** PHASE 3 — PROMPT 3 OF 3 (FINAL CERTIFICATION)  
**Status:** CERTIFIED COMPLETE / PERMANENTLY FROZEN  
**Primary Root Gap:** SRT-GAP-005 (Executive Dashboard Multi-Branch Filter & Role Metric Gate) — CLOSED  
**Linked Requirement:** REQ-0026 (Executive Dashboard & Store KPIs)  
**Certification Date:** 2026-10-09  

---

## 1. Executive Summary
Phase 3 establishes the complete adaptive UX, navigation, and presentation architecture of HBOS for small retail businesses. Operating within the frozen foundation of Phase 0, Phase 1, and Phase 2, Phase 3 delivers a single, cohesive presentation layer that dynamically tailors itself across four distinct operational personas (Single-Store Owner, Multi-Branch Owner, Branch Manager, and Cashier/Salesperson) and two compatibility roles (`Accountant` and `Inventory Manager` under `POLICY-B1`).

All requirements for Phase 3 have been implemented and hardened across exactly five authorized frontend production files. Zero backend production files were modified, zero database migrations were introduced, and zero database schema changes occurred. The full automated backend test suite (413 tests / 1,856 assertions) passes with zero failures, the frontend source verifier passes all 34 contract checks, and the frontend production build passes cleanly.

---

## 2. Production Change Manifest & Boundaries
Production modifications are strictly confined to five authorized frontend files:

1. **`Frontend/src/stores/navigation.js` (NEW):**
   - Classified as `STATEFUL_SESSION_CONTEXT_RESET_REQUIRED`.
   - In-memory state: `branches`, `selectedBranchScope`, `fetchStatus`, `fetchError`.
   - 0 browser persistence (no `localStorage`, `sessionStorage`, `IndexedDB`, or cookies).
   - Explicit 5-state business-shape engine (`CONTEXT_LOADING`, `CONTEXT_ERROR`, `ZERO_BRANCHES_FALLBACK`, `SINGLE_STORE`, `MULTI_BRANCH`).
   - Centralized visible navigation policy for all personas.
   - Dynamic permission checks (`hasUserPermission`) for compatibility roles.
   - Safe `reset()` action purging all in-memory branch records and UI selections.

2. **`Frontend/src/stores/auth.js` (MODIFIED):**
   - Asynchronous `resetSensitiveStores()` awaiting dynamic import of `navigationStore.reset()`.
   - Guaranteed atomic session cleanup without un-awaited fire-and-forget promises.
   - Eliminates static circular imports between `auth.js` and `navigation.js`.
   - Preserves all Phase-2 session teardown guarantees (token, business ID, Axios headers, IndexedDB handle detachment, outbox preservation).

3. **`Frontend/src/layouts/MainLayout.vue` (MODIFIED):**
   - Desktop sidebar, mobile drawer, and global route search unified under `navigationStore.visibleNavItems`.
   - Mock store array `availableStores` and hardcoded outlet count removed.
   - Header store switcher conditioned strictly on `navigationStore.branchSelectorVisible` (Business Owner in `MULTI_BRANCH` mode).

4. **`Frontend/src/router/index.js` (MODIFIED):**
   - Registered canonical route map (`CANONICAL_ROUTES`): Dashboard (`'/'`), POS (`'/POS'`), Settings/Users (`'/settings?tab=users'`), Branch Management (`'/busniesssetup'`), Reports (`'/reports'`), Inventory (`'/inventry'`).
   - Role-based default landing (`/POS` for Cashier, `/` for Owner/Manager).
   - Direct canonical route guards (Manager $\rightarrow$ `/`, Cashier $\rightarrow$ `/POS`) without intermediate redirect chains.
   - Compatibility roles protected via runtime permission checking (`hasUserPermission`).
   - Legacy `/employees` redirects to `/settings?tab=users`.

5. **`Frontend/src/views/DashboardView.vue` (MODIFIED):**
   - Centralized scope state bound to `navigationStore.selectedBranchScope`.
   - Duplicate local branch state and API fetches removed.
   - Branch filter dropdown and comparison table hidden in single-store mode.
   - Canonical aggregate encoding `branch_id=all` emitted for Multi-Branch Owner; implicit parameter omission for Single-Store Owner and Branch Manager.

---

## 3. Business-Shape State Machine
The business-shape engine derives reactively from `GET /api/v1/branches`:
- **Active Branch Filtering:** `branches.filter(b => b.status === 'active')`. Inactive, setup, draft, or archived branches are strictly excluded.
- **Five Explicit States:**
  1. `CONTEXT_LOADING`: Fetch in flight; 0 multi-branch controls exposed.
  2. `CONTEXT_ERROR`: Fetch failed; safe retry state; 0 multi-branch controls exposed.
  3. `ZERO_BRANCHES_FALLBACK`: 0 active branches returned (exceptional fallback).
  4. `SINGLE_STORE`: Exactly 1 active branch (`activeBranchCount === 1`).
  5. `MULTI_BRANCH`: 2 or more active branches (`activeBranchCount >= 2`).
- **Reactive Transitions:** Transitions between `SINGLE_STORE` (1 branch) and `MULTI_BRANCH` (2+ branches) react immediately upon branches array updates without requiring page reload or re-login.

---

## 4. Persona Acceptance & Navigation Matrix
- **Single-Store Owner:**
  - Landing: `/` (Dashboard).
  - Visible: Complete operational modules (POS, Sales, Customers, Khata, Products, Purchases, Expenses, Reports, Settings).
  - Hidden: Header store switcher, Dashboard branch dropdown, branch comparison table, dedicated staff sidebar navigation, transfers.
  - Preserved: Direct administrative branch access via `/busniesssetup` (alias `/branches`).
- **Multi-Branch Owner:**
  - Landing: `/` (Dashboard).
  - Visible: Header store switcher, All Stores aggregate scope (`branch_id=all`), specific branch filter, branch comparison overview.
  - Hidden: Transfers (deferred to Phase 7).
- **Branch Manager:**
  - Landing: `/` (Dashboard).
  - Scope: Permanently locked to assigned branch (`user.branch_id`).
  - Hidden: Header switcher, All Stores aggregate, company settings, accounts, staff governance.
  - Guards: Direct access to Owner-only routes redirects directly to `/`.
- **Cashier / Salesperson:**
  - Landing: `/POS` (POS-first).
  - Visible: POS checkout, sales history, customer list, customer khata.
  - Hidden: Executive Dashboard, inventory editing, supplier accounts, finance, settings.
  - Guards: Direct access to `/` or back-office routes redirects directly to `/POS`.
- **Compatibility Roles (`Accountant`, `Inventory Manager`):**
  - Policy: `POLICY-B1`.
  - Resolution: Dynamic permission derivation via `hasUserPermission`.
  - Landing: Tested against permission before selecting preferred candidates (`/reports`, `/inventry`).

---

## 5. Security & Backend Defense-in-Depth
Zero backend code modifications were made; backend scoping was audited and verified:
1. **Salesperson:** `DashboardService` omits gross profit, operating position, payables, and branch comparison. Direct attempts to access `/reports` return HTTP 403.
2. **Branch Manager:** `DashboardController` returns HTTP 403 when requesting `branch_id='all'` or a foreign branch ID.
3. **Tenant Isolation:** All queries scoped to `active_business_id` via `Tenantable` trait; cross-tenant IDOR rejected.

---

## 6. Verification & Regression Results
- **Targeted Security Tests:** 13 passed / 100 assertions (`Phase8DashboardAndReportingTest`, `DashboardTest`).
- **Full Backend Regression Suite:** **413 passed / 1,856 assertions (0 failures, 0 errors, 152.12s)**.
- **Frontend Source Contract Verifier:** 34 / 34 checks passed (`scratch/verify_phase3_prompt2_frontend.php`).
- **Frontend Production Build:** Vite v8.2.1 compiled 170 modules with 0 errors in 3.89s.
- **Acceptance Registry:** 10 / 10 scenarios passed (`P3-SCN-A` through `P3-SCN-J`).
- **Risk Closure:** All 12 risks MITIGATED or DEFERRED (`P3-RSK-009` mitigated; 0 open risks).

---

## 7. Deferred Work & Boundary Enforcement
- **Phase 4:** POS split tender, cart hold, price overrides, quick-add customers, return conditions, thermal printing (58/80mm).
- **Phase 7:** Inter-branch stock transfer transactions and side-by-side performance matrix (`SRT-GAP-021`, `SRT-GAP-022`).
- **Phase 8:** Offline sync queue and Windows desktop packaging.
- **Phase 9:** Full-system cryptographic reconciliations and integrity audits.

---

## 8. Certification Signoff
Phase 3 (Adaptive UX / Navigation / Module Architecture) has satisfied all architectural, behavioral, security, and verification requirements. Phase 3 is officially **CLOSED AND FROZEN**.
