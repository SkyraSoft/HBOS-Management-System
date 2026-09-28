# HBOS PHASE 0: SYSTEM BASELINE, DISCOVERY & ARCHITECTURE FREEZE

## A. Executive Summary
This document serves as the absolute baseline for the Hyperlocal Business Operating System (HBOS) project. Phase 0 has definitively mapped the current state, frozen the architecture, and established the roadmap for all future implementation. The architecture is built around three core pillars:
1. Canonical hierarchy: Business (Tenant) → Branches.
2. Three standardized roles: Business Owner, Branch Manager, Salesperson.
3. Decoupled operational modules from double-entry ledgers (Cash/Inventory).

## B. Current Project Stage
The project currently has a foundational Laravel backend and a Vue/Pinia frontend prototype. Several CRUD modules exist, but many lacked atomic transactional safety, proper branch-level data isolation, and accurate double-entry ledger bookkeeping. The tenant boundary has been established, and the branch boundary foundations have been laid during Phase 0.

## C. Existing Architecture
HBOS follows a modular monolith approach utilizing:
*   **Backend:** Laravel 11.x, PHP 8.x, MySQL/MariaDB.
*   **Frontend:** Vue 3, Vite, Pinia (for state management).
*   **Authentication:** Laravel Sanctum (Token-based).
*   **Authorization:** Spatie Laravel-Permission.
*   **Audit Logging:** Spatie Laravel-Activitylog.
*   **Multi-Tenancy:** Single-database row-level tenanting via global `TenantScope`.
*   **Branch Isolation:** Single-database row-level isolation via global `BranchScope`.

## D. Backend Inventory
*   **Laravel Version:** 11.x
*   **PHP Version:** 8.2+
*   **Authentication:** `AuthController` using Sanctum tokens.
*   **Middleware:** Standard API middleware, Sanctum auth, potential upcoming role middlewares.
*   **Models:** `Branch`, `Brand`, `Business`, `CashLedger`, `Category`, `Customer`, `Expense`, `ExpenseCategory`, `InventoryMovement`, `KhataTransaction`, `Product`, `Purchase`, `PurchaseItem`, `RecurringExpense`, `Sale`, `SaleItem`, `Setting`, `Subcategory`, `Supplier`, `SupplierPayment`, `User`.
*   **Traits:** `Tenantable`, `Branchable`.
*   **Scopes:** `TenantScope`, `BranchScope`.
*   **Controllers:** 18+ API Controllers mapping to the models.
*   **Services:** `StockService`, `InventoryMovementService`, `CashLedgerService`.
*   **Activity Logs:** Integrated via `LogsActivity` trait on key models.

## E. Frontend Inventory
*   **Framework:** Vue 3 via Vite.
*   **State Management:** Pinia (stores for auth, products, sales, cart).
*   **Routing:** Vue Router.
*   **API Layer:** Axios instance configured in `api.js`.
*   **Current State:** Includes a basic POS layout, standard CRUD views (tables/forms) for products, categories, suppliers.
*   **Gaps:** Missing robust branch context switching, strictly typed role experiences (Owner vs Manager vs Salesperson dashboards).
*   **Large Monolithic Components:** POS UI is partially monolithic and needs decomposition.

## F. Database Inventory
The database primarily utilizes `id`, `business_id` (foreign key for tenant), and `branch_id` (foreign key for operational isolation).
*   `users`: Core identity table (includes `business_id`, `branch_id`, `role`).
*   `businesses`: Tenants.
*   `branches`: Operational sibling entities under a business.
*   `products`, `categories`, `brands`, `subcategories`: Business-level product catalog.
*   `inventory_movements`: Immutable ledger for stock changes.
*   `cash_ledgers`: Immutable ledger for cash flows.
*   `sales`, `sale_items`: Transactional branch-level sale records.
*   `purchases`, `purchase_items`: Transactional branch-level purchase records.
*   `customers`, `suppliers`: Business-level external identities.
*   `expenses`: Branch-level financial outflows.
*   `khata_transactions`: Branch-level credit management.
*   `settings`: Business-level configuration.

## G. Existing Modules
| Module | Backend | Frontend | Database | Business Logic | Tests | Status | Future Classification |
|---|---|---|---|---|---|---|---|
| Business/Tenancy | Yes | No | Yes | Partial | No | PARTIAL | CORE |
| Branch Isolation | Yes | No | Yes | Early | No | EARLY | CORE |
| Roles (Spatie) | Yes | No | Yes | Yes | No | MOSTLY COMPLETE | CORE |
| Product Catalog | Yes | Yes | Yes | Yes | No | MOSTLY COMPLETE | CORE |
| Inventory Ledger | Yes | No | Yes | Early | No | EARLY | CORE |
| Sales / POS | Yes | Yes | Yes | Partial | No | PARTIAL | CORE |
| Cash Ledger | Yes | No | Yes | Early | No | EARLY | CORE |
| Customers | Yes | Yes | Yes | Yes | No | MOSTLY COMPLETE | CORE |
| Udhaar (Khata) | Yes | Yes | Yes | Partial | No | PARTIAL | CORE |
| Suppliers/Purchases | Yes | Yes | Yes | Partial | No | PARTIAL | CORE |
| Expenses | Yes | Yes | Yes | Yes | No | MOSTLY COMPLETE | CORE |

## H. Current Workflows
*   **Create Sale:** Sends array of items to `SaleController`. Calculates totals, decreases stock directly (to be refactored to `InventoryMovementService`), and records Khata.
*   **Create Product:** Saves product with `current_stock`. Need to migrate opening stock to `InventoryMovementService`.
*   **Authentication:** POST `/auth/login` issues Sanctum token, returns user object.
*   **Tenant Selection:** Implicit. Based on `business_id` attached to authenticated `User`.
*   **Dashboard:** Aggregates Sales, Purchases, Expenses to determine Net Revenue/Gross Profit.

## I. Tenant Security
*   **Verified:** Global tenant isolation (`TenantScope`) actively forces `WHERE business_id = X` on all queries traversing models using the `Tenantable` trait.
*   **Risks Addressed:** Removed fallback query logic in `ProductController` that leaked cross-tenant catalog data.
*   **Outstanding:** Verify background jobs (if added later) inject the correct tenant context.

## J. Current Role/Permission Model
*   Implemented via `spatie/laravel-permission`.
*   Canonical Roles frozen: `Business Owner`, `Branch Manager`, `Salesperson`.
*   Permissions explicitly mapped via `RolesAndPermissionsSeeder`.

## K. Business/Branch Problems
*   **Problem:** Previous assumptions allowed a parent-child branch hierarchy.
*   **Resolution:** Canonical structure frozen as siblings: Business → Branch A, Branch B. No branch owns another branch.

## L. Employee/User Analysis
*   **Problem:** Parallel `employees` table duplicated human identity, causing confusion with `users` authentication.
*   **Resolution:** The `employees` table was dropped via `2026_09_13_104137_restructure_for_hbos_phase_r` migration.
*   **Future:** Single `User` model handles identity, auth, and access. Future HR requirements will use `staff_profiles`.

## M. Product/Inventory Analysis
*   **Architecture:** Products belong to `business_id`. Current stock logic directly modifies `products.current_stock`.
*   **Migration Requirement:** Move to immutable `inventory_movements` (Double-entry inventory ledger) implemented via `InventoryMovementService`. The current `stock` column serves only as a cached summary for rapid reads.

## N. Sales/POS Analysis
*   The Sales API exists but modifies stock directly and is not wrapped in strong database transactions for all nested relationships.
*   Assigned to Phase 3 for full atomic refactoring and lifecycle completeness.

## O. Customers/Udhaar Analysis
*   Customers belong to `business_id` (Business-level).
*   Transactions/Udhaar (Khata) belong to `branch_id`.
*   Implementation is partially complete but needs robust receivables syncing.

## P. Suppliers/Purchases Analysis
*   Suppliers belong to `business_id`.
*   Purchase transactions belong to `branch_id`.
*   Assigned to Phase 5.

## Q. Expenses/Cash/Payments Analysis
*   Currently, money flows are scattered. Sales revenue, expenses, and Udhaar collections don't hit a unified cash repository.
*   **Resolution:** `CashLedger` model and `CashLedgerService` introduced in Phase 0. Full financial engine assigned to Phase 6.

## R. Dashboard/Financial Analysis
*   **Correction:** Phase 0 corrected the Gross Profit calculation formula to Sales - COGS.
*   **Technical Debt:** COGS currently relies on dynamic calculation. Historical cost-basis snapshot must be recorded in `sale_items` in Phase 3.

## S. API Analysis
*   RESTful API design with standard `apiResource` routes under `api/v1/`.
*   Current technical debt: Missing role-based authorization middleware on endpoints.

## T. Security Analysis
*   **CRITICAL:** Financial modifications (Sale cancellations) previously didn't atomically revert stock/Khata. Assigned to Phase 3.
*   **MEDIUM:** Missing strict role middlewares on API routes. Assigned to Phase 1.

## U. Testing Baseline
*   Currently, testing is **MISSING**. There are no comprehensive Unit or Feature tests for sales, tenant isolation, or inventory ledger integrity. This is a massive risk. Phase 1-9 MUST include tests.

## V. Technical Debt
*   Missing automated tests.
*   Controllers contain fat business logic (e.g., Sale/Purchase calculations).
*   Historical cost snapshot not saved on `sale_items`.

## W. Keep/Refactor/Remove Matrix
| Asset | Decision | Reason |
|---|---|---|
| `TenantScope` / `Tenantable` | **KEEP** | Core security foundation. |
| `Employee` Model/Table | **REMOVE** | Replaced by `User` + Spatie Roles. |
| `StockService` (Old) | **REMOVE** | Replaced by `InventoryMovementService`. |
| `CashLedger` | **KEEP** | New canonical money tracking. |
| Dashboard API | **REFACTOR** | Needs cost-basis snapshot data. |
| `ProductController` | **KEEP W/ MODIFICATION** | Cleaned up cross-tenant leakage. |

## X. Future Migration Map
*   **Phase 1:** Add role/permission middlewares, finalize branch assignment UI.
*   **Phase 2:** Move all product operations to `InventoryMovementService`, build `branch_inventory` views/tables if minimum stocks are required.
*   **Phase 3:** Update `sale_items` to record `cost_price` snapshot. Wrap `SaleController` in atomic DB transactions.
*   **Phase 6:** Map all payment methods in Sales/Purchases/Expenses to the `CashLedger`.

## Y. Issues Assigned to Phases 1–9
*   Role Middlewares -> Phase 1
*   Inventory Ledger Migration -> Phase 2
*   Atomic Sales / Cost-Basis Snapshot -> Phase 3
*   Customer Receivable Statements -> Phase 4
*   Supplier Payables -> Phase 5
*   Unified Financial Ledger -> Phase 6

## Z. Phase 0 Final Verdict
Phase 0 is **COMPLETE**. The architecture is frozen, the baseline is established, and the roadmap is strictly defined. Development may now sequentially proceed to Phase 1.
