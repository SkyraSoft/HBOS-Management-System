# HBOS Phase 1 — Discovery & Baseline Audit Document
**Phase:** 1 — Business, Branches, Users, Roles & Security  
**Checkpoint:** A — DISCOVERY  
**Document Path:** `docs/HBOS_PHASE_1_DISCOVERY.md`  
**Date:** September 13, 2026  

---

## A. Executive Discovery Summary

Phase 1 Checkpoint A provides the complete, authoritative discovery audit of HBOS's identity, organizational hierarchy, tenant boundary, branch isolation, role model, and authorization architecture.

### Key Architectural Baseline Findings:
1. **Canonical Business Hierarchy:** HBOS enforces a single-level hierarchy (`HBOS -> Business -> Siblings Branches`). A branch with `is_primary = true` represents the head office / primary operational location, but is strictly a sibling to all other branches.
2. **Canonical Roles:** Exactly three canonical product roles are established: `Business Owner`, `Branch Manager`, and `Salesperson`.
3. **Tenant & Branch Isolation:** `TenantScope` handles tenant boundary isolation. `BranchScope` was added during Phase 0 baseline work, but discovery reveals global `BranchScope` is fragile for cross-branch reporting, queues, and background services. It must be replaced with an explicit query scope & service-level authorization pattern.
4. **Active Business & Branch Context:** Currently missing in API routes and auth middleware. Users are locked to `users.business_id` and `users.branch_id`. Phase 1 will implement explicit Active Business and Active Branch resolution via request headers (`X-Business-ID`, `X-Branch-ID`) and token context.
5. **Staff Identity Consolidation:** Legacy `employees` table has been dropped in backend migrations. `User` is now the single canonical identity for all human users. Spatie roles define access.

---

## B. Current Business Architecture

- **Model:** [`App\Models\Business`](file:///c:/xampp/htdocs/HBOS/api/app/Models/Business.php)
- **Database Table:** `businesses`
  - Columns: `id`, `name`, `type`, `address`, `phone`, `email`, `logo`, `currency`, `created_at`, `updated_at`
- **Relationships:**
  - `users()`: `belongsToMany(User::class, 'business_user')`
- **Current Deficiencies:**
  - `BusinessController` permits listing all businesses via `Business::all()` to any authenticated user.
  - Registration in `AuthController` creates a `Business` and assigns `user.business_id = $business->id`, but fails to populate the `business_user` membership table.

---

## C. Current Branch Architecture

- **Model:** [`App\Models\Branch`](file:///c:/xampp/htdocs/HBOS/api/app/Models/Branch.php) (uses `Tenantable` and `LogsActivity`)
- **Database Table:** `branches`
  - Columns: `id`, `business_id`, `name`, `code`, `phone`, `address`, `city`, `is_primary`, `status`, `created_at`, `updated_at`
  - Unique Index: `[business_id, name]`, `[business_id, code]`
- **Relationships:**
  - `business()`: `belongsTo(Business::class)`
  - `users()`: `hasMany(User::class)`
- **Current Deficiencies:**
  - No `BranchController` or API endpoints currently exist in `routes/api.php` for creating, listing, updating, or selecting branches.
  - `branches` table relies on `business_id` for tenant isolation.

---

## D. Current User Architecture

- **Model:** [`App\Models\User`](file:///c:/xampp/htdocs/HBOS/api/app/Models/User.php) (uses `HasApiTokens`, `HasFactory`, `Notifiable`, `HasRoles`, `LogsActivity`)
- **Database Table:** `users`
  - Columns: `id`, `business_id`, `branch_id`, `name`, `email`, `role`, `is_active`, `last_login_at`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`
- **Identity Fields & Conflicts:**
  - `users.role` exists as a plain string column (`admin`, `cashier`) alongside Spatie Permission's `roles` table. This causes duplicate role truth.
  - `users.business_id` acts as the active business pointer.
  - `users.branch_id` acts as the assigned branch pointer (`null` for Business Owner).

---

## E. Current Membership Architecture

- **Database Table:** `business_user`
  - Columns: `id`, `business_id`, `user_id`, `created_at`, `updated_at`
  - Unique Constraint: `['business_id', 'user_id']`
- **Audit:**
  - The `business_user` table exists in database schema (migration `2026_09_10_105700_create_business_user_table.php`).
  - However, application code does not read or write to `business_user` during auth or business creation.
  - Recommendation: Elevate `business_user` into the primary source of truth for business memberships, adding `status` (`active`, `suspended`, `invited`) and `role_id` or Spatie team context.

---

## F. Current Role Architecture

HBOS defines three canonical roles:
1. **Business Owner:** Full strategic access across all business branches, settings, reports, staff management, and financial summaries.
2. **Branch Manager:** Operational access restricted to assigned branch (sales, inventory, purchases, local expenses, local reports). Cannot alter business-wide settings or cross-branch data.
3. **Salesperson:** Restricted POS/Sales transactional access (create sales, select customer, accept payment, issue receipt, view personal daily transactions).

---

## G. Current Spatie Configuration

- **Seeder:** [`Database\Seeders\RolesAndPermissionsSeeder`](file:///c:/xampp/htdocs/HBOS/api/database/seeders/RolesAndPermissionsSeeder.php)
  - Seeds roles: `Business Owner`, `Branch Manager`, `Salesperson`.
  - Seeds permissions: `manage business settings`, `manage branches`, `manage users`, `view full reports`, `manage branch settings`, `manage local inventory`, `view branch reports`, `create sales`, `view own sales`, `create customers`.
- **Gaps Identified:**
  - Spatie team feature (`permission.teams`) is currently disabled in `config/permission.php`. This means Spatie roles are assigned globally to users rather than scoped per `business_id`.
  - Routes in `routes/api.php` do NOT enforce Spatie middleware (`role:` or `permission:`). All authenticated users can access all endpoints!

---

## H. TenantScope Analysis

- **Implementation:** [`App\Models\Scopes\TenantScope`](file:///c:/xampp/htdocs/HBOS/api/app/Models/Scopes/TenantScope.php)
  ```php
  if (Auth::check() && Auth::user()->business_id) {
      $builder->where($model->getTable() . '.business_id', Auth::user()->business_id);
  }
  ```
- **Evaluation:**
  - **Strengths:** Automatically applies to all `Tenantable` models during Eloquent queries. Prevents basic cross-tenant data leakage.
  - **Weaknesses:**
    1. Tied strictly to `Auth::user()->business_id`. Does not support active business switching dynamically unless `Auth::user()->business_id` or an active context service is updated.
    2. Raw DB queries (`DB::table(...)`) bypass `TenantScope`.
    3. Queued jobs & CLI tasks do not have `Auth::user()`, so `TenantScope` does not filter unless context is explicitly set.

---

## I. BranchScope Analysis

- **Implementation:** [`App\Models\Scopes\BranchScope`](file:///c:/xampp/htdocs/HBOS/api/app/Models/Scopes/BranchScope.php)
  ```php
  if (Auth::check() && Auth::user()->branch_id) {
      $builder->where($model->getTable() . '.branch_id', Auth::user()->branch_id);
  }
  ```
- **Evaluation:** **UNSAFE AS A IMPLICIT GLOBAL SCOPE**.
  - **Reasons:**
    1. Global `BranchScope` breaks Business Owner cross-branch reporting when an Owner wants to query aggregated data across all branches or filter by a specific branch.
    2. When `Auth::user()->branch_id` is null (Owner), no branch filter is applied. But when a Manager operates, Eloquent will silently hide all other branch records, causing missing model exceptions or unexpected query results during transfers or joins.
    3. In background jobs or exports where `Auth::check()` is false, `BranchScope` completely disappears.
  - **Recommendation:** **REPLACE WITH EXPLICIT BRANCH AUTHORIZATION LAYER & SCOPE TRAITS**. Branch isolation must be explicitly requested or enforced via Middleware/Policy/Service layer rather than a silent global scope.

---

## J. Active Business Analysis

- **Current State:** Non-existent. The active business is strictly derived from `users.business_id`.
- **Recommended Architecture:**
  1. Middleware `ResolveActiveBusiness`: Checks `X-Business-ID` request header or default `user.business_id`.
  2. Validates that `Auth::user()` possesses active membership in `business_user` for that `business_id`.
  3. Sets `app('active_business_id')` in service container, which `TenantScope` consumes.

---

## K. Active Branch Analysis

- **Current State:** Non-existent. Derived from `users.branch_id`.
- **Recommended Architecture:**
  1. For `Business Owner`: Can pass optional `X-Branch-ID` header as a query filter. If omitted, views all branches in active business.
  2. For `Branch Manager` & `Salesperson`: Active branch is strictly bound to assigned `branch_id` from their `branch_user` or `user.branch_id` assignment. Requests trying to pass a different `X-Branch-ID` are rejected with `403 Forbidden`.

---

## L. Employee Migration Analysis

- **Status:** Backend `employees` table has been dropped via migration `2026_09_13_104137_restructure_for_hbos_phase_r.php`.
- **Code Audit:**
  - `routes/api.php` line for `EmployeeController` is commented out.
  - `User` model handles all authentication and identity.
  - Remaining step for Phase 1: Clean up remaining legacy frontend router `/employees` references and remove dead employee store references in `Frontend/src/stores`.

---

## M. Current Route Security

- **Current Middleware:** `auth:sanctum` applied to all protected routes in `routes/api.php`.
- **Deficiencies:**
  - No role or permission authorization middleware (`role:Business Owner`, `permission:...`) exists on any route.
  - `BusinessController` allows any authenticated user to view/modify businesses.
  - `SettingController`, `ReportController`, `DashboardController` are accessible by cashiers/salespeople without authorization checks.

---

## N. Current Policies/Middleware

- **Policies:** Currently 0 Eloquent Policies exist in `app/Policies/`.
- **Middleware:** `EnsureActiveBusiness`, `EnsureBranchAccess`, and Spatie role middleware are missing from HTTP kernel and API route groups.

---

## O. Current Database Relationships

```
businesses (1) <---> (*) branches
businesses (1) <---> (*) users (via business_user pivot)
businesses (1) <---> (*) products, categories, brands, customers, suppliers
branches (1)   <---> (*) users (via users.branch_id)
branches (1)   <---> (*) sales, purchases, expenses, khata_transactions, supplier_payments, inventory_movements, cash_ledgers
```

---

## P. Existing Data Migration Concerns

1. Existing legacy `users` have `business_id` filled, but `business_user` pivot table is empty. Phase 1 must run a migration/seeder script to populate `business_user` from `users.business_id`.
2. Existing operational records (`sales`, `purchases`, `expenses`) created prior to Phase 0 may have `branch_id = NULL`. Backfill script must assign them to the business's primary branch (`is_primary = true`).

---

## Q. Recommended Final Phase 1 Architecture

1. **Hierarchy:** `HBOS -> Business (Tenant) -> Branches (Siblings)`.
2. **Identity:** `User` is the sole human entity. Multi-tenant access managed via `business_user`.
3. **Roles:** `Business Owner`, `Branch Manager`, `Salesperson` enforced via Spatie permissions & Laravel Policies.
4. **Tenant Security:** `TenantScope` bound to container service `app('active_business_id')`.
5. **Branch Security:** Explicit policy & controller/service checking via `EnsureBranchAccess` middleware and `forBranch($branchId)` query scopes.

---

## R. Recommended Membership Model

- **Table:** `business_user`
  - Columns: `id`, `business_id`, `user_id`, `role`, `status`, `created_at`, `updated_at`
  - Unique Constraint: `['business_id', 'user_id']`
  - Roles: `Business Owner`, `Branch Manager`, `Salesperson` stored as team-scoped Spatie role or pivot role field.

---

## S. Recommended Branch Assignment Model

- **Approach:**
  - For Phase 1 MVP: `users.branch_id` (foreign key to `branches.id`, nullable for Business Owners).
  - Multi-branch assignment support prepared via `branch_user` pivot table (`business_id`, `branch_id`, `user_id`).

---

## T. Recommended Role/Permission Model

- **Spatie Teams Enabled:** Enable Spatie team scoping using `business_id` as the team foreign key.
- **Middleware Enforcement:** Apply `permission:` or `role:` middleware to API routes in `routes/api.php`.

---

## U. Permission Matrix

| Permission | Business Owner | Branch Manager | Salesperson |
|---|:---:|:---:|:---:|
| `manage business settings` | ✅ | ❌ | ❌ |
| `manage branches` | ✅ | ❌ | ❌ |
| `manage users` | ✅ | ❌ | ❌ |
| `view full reports` | ✅ | ❌ | ❌ |
| `manage branch settings` | ✅ | ✅ | ❌ |
| `manage local inventory` | ✅ | ✅ | ❌ |
| `view branch reports` | ✅ | ✅ | ❌ |
| `create sales` | ✅ | ✅ | ✅ |
| `view own sales` | ✅ | ✅ | ✅ |
| `create customers` | ✅ | ✅ | ✅ |

---

## V. Security Context Flow

```
HTTP Request
   ↓
[Sanctum Auth Middleware] -> Authenticates User
   ↓
[ResolveActiveBusiness Middleware] -> Validates X-Business-ID / User Business Context
   ↓
[ResolveActiveBranch Middleware] -> Validates X-Branch-ID / User Branch Authority
   ↓
[Spatie Role & Policy Guard] -> Checks Permission for Action
   ↓
[TenantScope & Service Layer] -> Executes Query inside Tenant & Branch Boundary
```

---

## W. Phase 1 Database Change Plan

1. **Migration 1:** `populate_business_user_from_users`: Backfills `business_user` for all existing users.
2. **Migration 2:** `create_branch_user_table`: Creates `branch_user` pivot table (`business_id`, `branch_id`, `user_id`).
3. **Migration 3:** `backfill_branch_id_on_operational_tables`: Ensures all legacy `sales`, `purchases`, `expenses` have valid `branch_id` pointing to primary branch.

---

## X. Phase 1 Automated Test Plan

1. **`tests/Feature/TenantIsolationTest.php`**:
   - Verify Business A user cannot read or update Business B resources.
   - Verify spoofed `X-Business-ID` is rejected if user is not a member of that business.
2. **`tests/Feature/BranchAuthorizationTest.php`**:
   - Verify Branch Manager of Branch 1 cannot access Branch 2 data.
   - Verify Business Owner can view data across all branches.
3. **`tests/Feature/RolePermissionTest.php`**:
   - Verify Salesperson receives 403 on `/api/v1/businesses` and `/api/v1/settings`.
   - Verify Manager can manage branch settings but cannot manage global users.

---

## Y. Phase 1 Acceptance Criteria

1. `Business` is proven as tenant boundary; all APIs enforce tenant context.
2. `Branch` operations are sibling-based; primary branch is designated via `is_primary = true`.
3. `User` identity is unified; legacy `Employee` references removed.
4. Active Business context resolution via `X-Business-ID` / Session / Membership is verified.
5. Three canonical roles (`Business Owner`, `Branch Manager`, `Salesperson`) enforced via Spatie permissions on API routes.
6. Automated security test suite (`TenantIsolationTest`, `BranchAuthorizationTest`, `RolePermissionTest`) passes with 100% success rate.

---

## Z. Risks / Decisions Required Before Implementation

1. **Global `BranchScope` Removal:** `BranchScope` must be unhooked as a global scope and replaced with explicit query scopes & policy checks to prevent breaking aggregated reports and background tasks.
2. **Spatie Teams Configuration:** Enable Spatie team feature with `business_id` as team foreign key so role permissions stay isolated per tenant.

---
**HBOS PHASE 1 CHECKPOINT A DISCOVERY COMPLETE**
