# Phase 3: Adaptive UX / Navigation / Module Architecture
## Small Retail Transformation Program — Progress Log

**Current Status:** COMPLETE / FROZEN (PHASE 3 ADAPTIVE UX CERTIFIED)  
**Primary Root Gap:** SRT-GAP-005 (Executive Dashboard Multi-Branch Filter & Role Metric Gate) — CERTIFIED COMPLETE  
**Linked Requirement:** REQ-0026 (Executive Dashboard & Store KPIs)  
**Architecture Rule:** One Domain + Role / Business-Shape Variants  

---

### 1. Phase Purpose
Phase 3 establishes the adaptive presentation architecture allowing the single, unified HBOS codebase to dynamically tailor its user experience to four operating contexts:
1. **Single-Store Owner:** Streamlined, uncluttered interface for an owner operating one shop (Main Store) with 0 required staff. All multi-branch selectors, comparison tables, and transfer workflows are hidden.
2. **Multi-Branch Owner:** Comprehensive multi-store management revealing the header store switcher, branch comparison cards, and store management.
3. **Branch Manager:** Operational workspace restricted strictly to the manager's assigned branch. No access to multi-store aggregation, company settings, or other branches.
4. **Cashier / Salesperson:** POS-first experience. Landing page defaults to `/pos`. Executive dashboards, financial accounts, profit metrics, and settings are hidden and forbidden.

---

### 2. Frozen Inputs & Foundation Inherited
- **Phase 0:** Repository Cleanup & Baseline Normalization (FROZEN)
- **Phase 1:** 300-Question Requirements & Final Product Blueprint (FROZEN)
- **Phase 2:** Business / Main Store / Branch / Identity Foundation (FROZEN)
  - Canonical Role: `Business Owner` (Display: `Business Super Owner`)
  - Canonical Roles: `Branch Manager`, `Salesperson` (Display: `Cashier / Salesperson`)
  - Compatibility Policy: `POLICY-B1` for `Accountant` and `Inventory Manager`
  - Main Store Invariant: `is_primary = true`, non-deletable, non-deactivatable, 0 required staff
  - Device is NOT a human role
  - Certified Active Pinia Stores (9): `auth`, `counter`, `customers`, `expenses`, `khata`, `notifications`, `purchases`, `retail`, `suppliers`

---

### 3. Source Files Inspected & Reconciled
- `Frontend/src/router/index.js` (24 active routes audited)
- `Frontend/src/layouts/MainLayout.vue` (topbar, desktop sidebar, mobile drawer audited)
- `Frontend/src/views/DashboardView.vue` (metric cards, branch dropdown, comparison table audited)
- `Frontend/src/stores/auth.js` (session state, resetSensitiveStores, logout cleanup audited)
- `Frontend/src/stores/branch.js` (audited; reconciled as `CURRENTLY_UNREFERENCED`; does not exist; Phase-2 store inventory preserved)
- `api/app/Http/Controllers/Api/V1/DashboardController.php` (backend role authorization and HTTP 403 enforcement audited)
- `api/app/Services/DashboardService.php` (backend metric scoping by role and branch audited)

---

### 4. Current Frontend Architecture & Findings
1. **Router (`Frontend/src/router/index.js`):**
   - 24 active routes registered.
   - Global `beforeEach` guard only verifies authentication (`requiresAuth`) and redirects all authenticated users unconditionally to `/dashboard`.
   - Lacks Cashier POS-first redirection.
   - Lacks route-level role authorization checks.
2. **Layout (`Frontend/src/layouts/MainLayout.vue`):**
   - Header contains hardcoded mock stores array (`availableStores`) and renders a store switcher dropdown unconditionally.
   - Desktop sidebar and mobile drawer iterate over a static `navItems` array with no role or business-shape filtering.
3. **Dashboard (`Frontend/src/views/DashboardView.vue`):**
   - Renders a branch filter dropdown even when only 1 branch exists (`activeBranchCount === 1`).
   - Renders a branch comparison table whenever `branchComparison` array is non-empty, causing multi-branch clutter in single-store mode.
4. **Backend Dashboard (`DashboardController.php` & `DashboardService.php`):**
   - **`BACKEND_SCOPE_SAFE`**: Strict server-side scoping already implemented.
   - Salesperson is restricted to own branch, denied other branches with HTTP 403, and payload omits gross profit, operating position, payables, and branch comparisons.
   - Manager is restricted to assigned branch, denied `branch_id='all'` and other branches with HTTP 403, and payload omits gross profit, operating position, payables, and comparison.
   - Owner has full access to aggregate (`branch_id='all'`) or specific branch metrics.
   - **Zero backend production code changes are required for Phase 3.**

---

### 5. Business-Shape Classification Contract
- **Source of Truth:** `GET /api/v1/branches`
- **Active Branch Formula:**
  - `activeBranches = branches.filter(b => b.status === 'active')`
  - Inactive (`status === 'inactive'`), draft, setup, or archived branches are strictly excluded.
- **Explicit States:**
  - `CONTEXT_LOADING`: Branches request in flight; zero multi-branch controls exposed.
  - `CONTEXT_ERROR`: Branches request failed; safe retry state; zero multi-branch controls exposed.
  - `SINGLE_STORE`: Exactly 1 active branch (`activeBranchCount === 1`).
  - `MULTI_BRANCH`: 2 or more active branches (`activeBranchCount >= 2`).
  - `ZERO_BRANCHES_FALLBACK`: 0 active branches returned (exceptional fallback).
- **Scope State Owner:** `navigationStore.selectedBranchScope` (**ONE GLOBAL OWNER SCOPE**).
- **Canonical All-Stores Encoding:** `all_stores_frontend_value: 'all'`, `all_stores_api_encoding: branch_id=all` (Backend compatibility: omitted branch_id preserved as fallback).
- **Zero Magic IDs:** Magic numeric branch IDs (`0`, `-1`): **0**.
- **Reversibility:** If active branch count drops back to 1, UI cleanly reverts to single-store simplicity while preserving historical data.

---

### 6. Role & Navigation Matrix
- **Business Owner:**
  - Single-Store: Dashboard landing, full single-store navigation, store selector hidden, transfers hidden, branch comparison hidden.
  - Multi-Branch: Header store selector visible, All Stores scope available, transfers hidden until Phase 7, branch comparison visible.
- **Branch Manager:**
  - Landing on assigned-branch workspace.
  - Store switcher hidden; scope permanently fixed to assigned branch.
  - Financial accounts, business settings, and staff governance forbidden.
- **Cashier / Salesperson:**
  - Default landing is `/pos`.
  - Sidebar limited to POS, permitted sales history, customer lookup.
  - Dashboard direct navigation is `FORBIDDEN_REDIRECT_TO_POS`.
  - Inventory management, supplier accounting, and settings are hidden and forbidden.
- **Single-Store Staff UX:**
  - Dedicated Staff navigation in main sidebar: **NO**.
  - Accessible via Settings: `/settings?tab=users` classified as `HIDDEN_BUT_AUTHORIZED_SECONDARY_SETTINGS`.
  - Technical staff access preserved: **PASS**.
- **Compatibility Roles (`Accountant`, `Inventory Manager`):**
  - Policy: `PERMISSION_FALLBACK`.
  - Preserved via existing backend permission mappings.

---

### 7. Phase-7 Feature Exposure Policy
- **Transfers:** Current Readiness: `NO_CURRENT_UI` $\rightarrow$ `HIDE_UNTIL_PHASE7`.
- **Branch Comparison:** Current Readiness: `EXISTING_USABLE_CAPABILITY` (overview table on DashboardView) $\rightarrow$ `VISIBLE_EXISTING_DASHBOARD_ONLY_IN_MULTI_BRANCH`. Full comparative performance matrix remains owned by Phase 7 (`SRT-GAP-022`).
- **Unfinished Phase-7 Features Exposed As Complete:** **0**.

---

### 8. Implementation Manifest (Reconciled & Frozen for Prompt 2)
1. `Frontend/src/stores/navigation.js` (NEW, `CONFIRMED_EXPECTED_CHANGE`): Central stateful session store (`STATEFUL_SESSION_CONTEXT_RESET_REQUIRED`); mutable UI/business-context state with derived getters.
2. `Frontend/src/layouts/MainLayout.vue` (MODIFIED, `CONFIRMED_EXPECTED_CHANGE`): Bind sidebar/drawer to `navigationStore.visibleNavItems`; conditional store switcher.
3. `Frontend/src/router/index.js` (MODIFIED, `CONFIRMED_EXPECTED_CHANGE`): Role-based default landings (`/pos` for Cashier); route guards.
4. `Frontend/src/views/DashboardView.vue` (MODIFIED, `CONFIRMED_EXPECTED_CHANGE`): Unclutter single-store; bind branch scope to `navigationStore.selectedBranchScope`.
5. `Frontend/src/stores/auth.js` (MODIFIED, `CONFIRMED_EXPECTED_CHANGE`): Invoke `navigationStore.reset()` in `resetSensitiveStores()` on logout to guarantee Phase-2 session isolation.

- **Protected Baseline Files:** `stores/branch.js`, `DashboardController.php`, `DashboardService.php`.
- **Total Production Files Planned:** **5** (Frontend: 5, Backend: 0).
- **Files With Real Delta:** 5 / 5.
- **Session-Affecting Files Accounted For:** 5 / 5.
- **Schema / Migration Changes Planned:** 0.

---

### 9. Prompt-1 Reconciliation Evidence
- Registries updated in `scratch/`:
  - `phase3_gap005_dashboard_adaptive_design.json`
  - `phase3_navigation_inventory.json`
  - `phase3_route_visibility_contract.json`
  - `phase3_role_module_visibility_matrix.json`
  - `phase3_business_shape_ui_contract.json`
  - `phase3_dashboard_metric_visibility_registry.json`
  - `phase3_navigation_policy_design.json`
  - `phase3_navigation_store_contract.json`
  - `phase3_prompt1_implementation_manifest.json`
  - `phase3_risk_registry.json`
  - `phase3_non_regression_contract.json`
  - `phase3_deferred_to_later_phases.json`
- Prompt 1 Master Verifier `scratch/verify_phase3_prompt1.php`: **RESULT: PASS**.

---

### 10. Prompt-2 Adaptive UX Implementation (COMPLETED)
**Status:** PROMPT 2 IMPLEMENTATION COMPLETE / VERIFIED  
**Production Changes:** Exactly 5 authorized frontend files (0 backend production changes, 0 schema changes, 0 migrations).

#### 10.1 Production Files Changed
1. `Frontend/src/stores/navigation.js` (NEW):
   - Classified as `STATEFUL_SESSION_CONTEXT_RESET_REQUIRED`.
   - Zero browser persistence (no localStorage, sessionStorage, IndexedDB, cookies).
   - In-memory state: `branches`, `selectedBranchScope`, `fetchStatus`, `fetchError`.
   - Explicit business shape state machine: `CONTEXT_LOADING`, `CONTEXT_ERROR`, `ZERO_BRANCHES_FALLBACK`, `SINGLE_STORE`, `MULTI_BRANCH`.
   - Strict active filtering: `branch.status === 'active'`.
   - Centralized visible navigation items for Single-Store Owner, Multi-Branch Owner, Branch Manager, Cashier/Salesperson, Accountant, Inventory Manager.
   - Implements `reset()` clearing all in-memory branch records and UI selected scope.
2. `Frontend/src/stores/auth.js` (MODIFIED):
   - Integrated dynamic import `import('./navigation').then(({ useNavigationStore }) => useNavigationStore().reset())` inside `resetSensitiveStores()`.
   - Eliminates static circular import cycle between `auth.js` and `navigation.js`.
   - Preserved all Phase-2 session cleanup guarantees (token cleared, business ID cleared, Axios headers deleted, IndexedDB handle detached, outbox preserved).
3. `Frontend/src/layouts/MainLayout.vue` (MODIFIED):
   - Removed mock `availableStores` data array and hardcoded outlets count badge.
   - Bound desktop sidebar, mobile drawer, and global route search to unified `navigationStore.visibleNavItems`.
   - Conditioned header store selector on `navigationStore.branchSelectorVisible` (visible only for Business Owner in `MULTI_BRANCH` mode with $\ge 2$ active branches).
   - Bound header scope selector to `navigationStore.selectedBranchScope`.
4. `Frontend/src/router/index.js` (MODIFIED):
   - Configured role-based default landing: `/POS` for Cashier/Salesperson, `/reports` for Accountant, `/inventry` for Inventory Manager, `/` (Dashboard) for Owner and Manager.
   - Guarded direct route access in `beforeEach`:
     - Cashier direct access to `/` or `/dashboard` $\rightarrow$ `FORBIDDEN_REDIRECT_TO_POS`.
     - Cashier direct access to back-office routes $\rightarrow$ `FORBIDDEN_REDIRECT_TO_POS`.
     - Branch Manager direct access to Owner-only routes (`/settings`, `/branches`, `/busniesssetup`, `/accounts`) $\rightarrow$ `FORBIDDEN_REDIRECT_TO_DASHBOARD`.
     - Single-Store Owner direct access to `/branches` $\rightarrow$ `DIRECT_ACCESS_ALLOWED` (via `/busniesssetup` alias).
     - Legacy `/employees` route $\rightarrow$ `redirect: '/settings?tab=users'`.
     - Compatibility roles protected with `PERMISSION_FALLBACK`.
5. `Frontend/src/views/DashboardView.vue` (MODIFIED):
   - Unified branch scope ownership with `navigationStore.selectedBranchScope`.
   - Removed duplicate local `branches` ref and local `/settings/branches` fetch.
   - Conditioned branch filter dropdown on `navigationStore.branchSelectorVisible`.
   - In single-store mode, hid branch filter dropdown and hid branch comparison table.
   - Multi-Branch Owner aggregate scope encoded canonically as `params.branch_id = 'all'`.
   - Single-store Owner and Branch Manager omit `branch_id` (implicit / server-scoped).

#### 10.2 Verification Evidence
- **Frontend Source Contract Verifier:** `scratch/verify_phase3_prompt2_frontend.php`
  - Output: `TOTAL CHECKS: 26 / 26 PASSED` $\rightarrow$ `FRONTEND CONTRACT: PASS`.
- **Targeted Backend Security Tests:**
  - `php artisan test --filter=Phase8DashboardAndReportingTest` $\rightarrow$ **12 passed (95 assertions)**
  - `php artisan test --filter=DashboardTest` $\rightarrow$ **1 passed (5 assertions)**
  - Total: **13 passed (100 assertions)**.
- **Frontend Production Build:**
  - `npm run build` with Vite v8.2.1:
  - Modules transformed: **170**
  - Build errors: **0**
  - Build duration: **3.02s**.
- **Prompt-2 Master Verifier:** `scratch/verify_phase3_prompt2.php`
  - Output: `RESULT: PASS`.

#### 10.3 Phase Boundary & Non-Regression
- Unauthorized Phase-4+ features added: **0**.
- Phase-2 session isolation: **PRESERVED**.
- POLICY-B1 compatibility roles: **PRESERVED**.

---

### 11. Prompt-2 Targeted Implementation Hardening (COMPLETED)
**Status:** TARGETED HARDENING VERIFIED & FROZEN  
**Authorized Production Files:** Exactly 5 (`Frontend/src/stores/navigation.js`, `Frontend/src/stores/auth.js`, `Frontend/src/layouts/MainLayout.vue`, `Frontend/src/router/index.js`, `Frontend/src/views/DashboardView.vue`).  
**Backend Production Changes:** **0**.  
**Database Schema Changes / Migrations:** **0**.

#### 11.1 Correction A: Atomic Navigation Session Reset
- **Problem Mitigated:** Replaced un-awaited fire-and-forget `import('./navigation').then(...)` with safe asynchronous import:
  ```javascript
  const { useNavigationStore } = await import('./navigation')
  useNavigationStore().reset()
  ```
- **Call Chain Trace & Propagation:**
  - `resetSensitiveStores()` is declared `async` and awaits `navigationStore.reset()`.
  - `clearAuthData()` is declared `async` and awaits `resetSensitiveStores()`.
  - `logout()` and `logoutAllDevices()` strictly await `clearAuthData()` before resolving.
- **Contract Guarantees:**
  - Navigation reset awaited before session teardown resolution: **YES**.
  - Detached promises / unhandled rejections: **0**.
  - Static circular import between `auth.js` and `navigation.js`: **NO**.
  - Old business branches / selected branch scope surviving completed logout: **NO**.
  - Phase-2 logout contract (customers, expenses, khata, notifications, purchases, retail, suppliers, IndexedDB handle, outbox preservation): **PRESERVED**.

#### 11.2 Correction B: Canonical Router Destinations & Forbidden Fallbacks
- **Canonical Route Map (`CANONICAL_ROUTES`):**
  - `dashboard_path`: `'/'` (Registered route `/`, alias: `['/dashboard']`, name: `'Dashboard'`).
  - `pos_path`: `'/POS'` (Registered route `/POS`, alias: `['/pos']`, name: `'PosSale'`).
  - `settings_path`: `'/settings'` (Registered route `/settings`, name: `'Settings'`).
  - `settings_users_tab`: `'/settings?tab=users'` (Actively consumed by `SettingsView.vue` lines 50-58 via `route.query.tab`).
  - `branch_management_path`: `'/busniesssetup'` (Registered route `/busniesssetup`, alias: `['/branches']`, name: `'BusniessSetup'`).
  - `reports_path`: `'/reports'` (Registered route `/reports`, name: `'Reports'`).
  - `inventory_path`: `'/inventry'` (Registered route `/inventry`, name: `'Inventry'`).
- **Direct Fallback Resolution (No Double-Redirects):**
  - Manager Forbidden Owner-Only Route: Resolves directly to `CANONICAL_ROUTES.dashboard` (`'/'`).
  - Cashier Forbidden Route: Resolves directly to `CANONICAL_ROUTES.pos` (`'/POS'`).
  - Unregistered guard destination targets: **0**.
- **Single-Store Owner Branch Access:** Preserved direct access via alias `/branches` to `/busniesssetup`. Single-store UI hides header/dashboard branch controls, but direct administrative route access remains authorized.

#### 11.3 Correction C: True Permission Fallback for Compatibility Roles
- **Policy:** `POLICY-B1` remains frozen. `Accountant` and `Inventory Manager` navigation authorization remains strictly permission-derived.
- **Implementation:**
  - Implemented `hasUserPermission(user, permission)` helper across `navigation.js` and `router/index.js` inspecting direct permissions, role permissions, and falling back to canonical Spatie seeder permissions.
  - Compatibility default landing is checked against permissions before selecting preferred candidates (`/reports` for report permission, `/inventry` for product view permission; fallback to first permitted route or safe fallback).
  - Compatibility visible navigation items are dynamically derived based on actual permissions held.
  - Role-only hardcoded authorization bypasses: **0**.
  - New permission grants: **0**.

#### 11.4 Verification & Build Results
- **Frontend Source Contract Verifier (`scratch/verify_phase3_prompt2_frontend.php`):**
  - **TOTAL CHECKS: 34 / 34 PASSED** (`FRONTEND CONTRACT: PASS`).
- **Targeted Backend Security Tests:**
  - `Phase8DashboardAndReportingTest`: 12 passed (95 assertions).
  - `DashboardTest`: 1 passed (5 assertions).
  - Total: 13 passed (100 assertions).
- **Frontend Production Build (`npm run build`):**
  - **170 modules transformed**.
  - **0 errors**.
  - **Build duration: 2.81s** (`PASS`).
- **Master Verifier (`scratch/verify_phase3_prompt2.php`):**
  - **RESULT: PASS**.

---

### 12. Prompt-3 Final Adaptive UX Certification (COMPLETE)
**Status:** PHASE 3 CERTIFIED COMPLETE & FROZEN  
**Primary Gap Closed:** SRT-GAP-005 (Executive Dashboard Multi-Branch Filter & Role Metric Gate)  
**Production Boundary:** Exactly 5 authorized frontend production files (`stores/navigation.js`, `stores/auth.js`, `layouts/MainLayout.vue`, `router/index.js`, `views/DashboardView.vue`).  
**Backend Production Changes:** **0**.  
**Schema Changes / Migrations:** **0**.

#### 12.1 Program & Architectural Acceptance
1. **Semantic Identity:** Canonical requirements: 32 / 32 MATCH. Root SRT gaps: 25 / 25 MATCH. SRT-GAP-005: MATCH. REQ-0026: MATCH. Semantic mismatches: 0.
2. **Business-Shape Engine:** 5 explicit states (`CONTEXT_LOADING`, `CONTEXT_ERROR`, `ZERO_BRANCHES_FALLBACK`, `SINGLE_STORE`, `MULTI_BRANCH`). Reactive transitions between 1 and 2+ active branches certified without reload.
3. **Role Personas:**
   - Single-Store Owner: Streamlined navigation, branch selectors hidden, branch comparison hidden, direct branch management access preserved.
   - Multi-Branch Owner: Header store selector visible, All Stores scope (`branch_id=all`), specific branch filtering, existing comparison table visible.
   - Branch Manager: Direct landing on `/`, assigned-branch scope only, foreign/aggregate access denied with HTTP 403, owner-only routes redirected to `/`.
   - Cashier / Salesperson: Direct landing on `/POS`, Dashboard hidden from nav, direct Dashboard/back-office access redirected to `/POS`, minimal financial payload protected on backend.
   - Compatibility Roles: `POLICY-B1` preserved for `Accountant` and `Inventory Manager` with permission-derived navigation and permission-checked default landings.
4. **Session Foundation:** `navigationStore.reset()` awaited atomically in `clearAuthData()`, `logout()`, and `logoutAllDevices()`. All 7 Phase-2 sensitive stores purged, IndexedDB handle detached, offline outbox preserved.

#### 12.2 Automated Regression & Verification Summary
- **Targeted Backend Security Tests:** 13 passed (100 assertions) across `Phase8DashboardAndReportingTest` and `DashboardTest`.
- **Full Backend Regression Suite (`php artisan test`):**
  - **413 passed**
  - **1856 assertions**
  - **0 failures**
  - **0 errors**
  - **Duration: 152.12s**
- **Frontend Source Contract Verifier (`scratch/verify_phase3_prompt2_frontend.php`):** 34 / 34 checks passed.
- **Frontend Production Build (`npm run build`):** 170 modules transformed, 0 errors, 3.89s duration.
- **Acceptance Registry (`scratch/phase3_acceptance_registry.json`):** 10 / 10 scenarios passed (`P3-SCN-A` through `P3-SCN-J`).
- **Risk Registry (`scratch/phase3_risk_registry.json`):** All 12 risks MITIGATED or DEFERRED_TO_OWNER_PHASE (`P3-RSK-009` mitigated; 0 open risks).
- **Final Master Verifier (`scratch/verify_phase3_prompt3.php`):** **RESULT: PASS**.

---

**Next Authorized Phase:** PHASE 4 — SALES / POS / CUSTOMERS / KHATA / RETURNS  
**Next Authorized Checkpoint:** PHASE 4 — PROMPT 1 OF 3 (SALES / POS / CUSTOMER-FLOW DELTA DESIGN)  
*(DO NOT IMPLEMENT PHASE 4 YET)*



