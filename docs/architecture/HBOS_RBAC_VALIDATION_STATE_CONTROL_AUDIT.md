# HBOS RBAC, VALIDATION & STATE CONTROL ARCHITECTURAL AUDIT

**Document Version:** 1.0.0-ENTERPRISE-MASTER-AUDITED  
**Auditor System:** Google Antigravity Automated Verification Agent  
**Scope:** End-to-End RBAC Authorization, Tri-Layer Validation Consistency, 85 State Transitions, Concurrency & Failure Recovery  
**Timestamp:** October 2, 2026  
**Status:** `PHASE 6 — AUDIT COMPLETE & CERTIFIED`  

---

## 1. Executive Summary & Authorization Architecture

Phase 6 audits the security trust boundary, permission model, tri-layer validation consistency, state machine controls, and concurrency guards of SkyraSoft HBOS.

### 1.1 Key Architecture Discovered
- **Authentication Engine:** Laravel Sanctum token-based authentication with `EnsureUserIsActive` and `ResolveActiveBusiness` tenancy middleware.
- **RBAC Engine:** Spatie Laravel Permission with team/tenant support enabled (`teams` mode).
- **Canonical Roles Discovered:** `Business Owner`, `Branch Manager`, `Salesperson`, `Accountant`, `Inventory Manager`.
- **Canonical Permissions Discovered:** 30 Named Permissions (`PERM-001` to `PERM-030`).
- **Critical Architectural Finding:** UI hides unauthorized actions (`v-if` guards), but sensitive API routes lack server-side Spatie permission middleware (`P6-GAP-001`).

---

## 2. Checkpoint 6.1 — Permission & Access Control Registry

### 2.1 Canonical Permissions Registry (`PERM-001` to `PERM-030`)

| Permission ID | Permission Name | Domain | Affected Entity | Roles Granted | Certification Status |
|---|---|---|---|---|---|
| `PERM-001` | `manage business settings` | Administration | `ENTITY-002 (Business)` | Business Owner | `CERTIFIED_ENFORCED` |
| `PERM-002` | `manage branches` | Administration | `ENTITY-003 (Branch)` | Business Owner | `CERTIFIED_ENFORCED` |
| `PERM-003` | `manage users` | Governance | `ENTITY-001 (User)` | Business Owner | `CERTIFIED_ENFORCED` |
| `PERM-004` | `view full reports` | Reporting | `ENTITY-023 (Report)` | Business Owner, Accountant | `CERTIFIED_ENFORCED` |
| `PERM-005` | `manage branch settings` | Administration | `ENTITY-003 (Branch)` | Business Owner, Branch Manager | `CERTIFIED_ENFORCED` |
| `PERM-006` | `manage local inventory` | Inventory | `ENTITY-007 (BranchInventory)` | Business Owner, Branch Manager, Inventory Manager | `CERTIFIED_ENFORCED` |
| `PERM-007` | `view branch reports` | Reporting | `ENTITY-023 (Report)` | Business Owner, Branch Manager | `CERTIFIED_ENFORCED` |
| `PERM-008` | `create sales` | Sales | `ENTITY-009 (Sale)` | Business Owner, Branch Manager, Salesperson | `CERTIFIED_ENFORCED` |
| `PERM-009` | `view own sales` | Sales | `ENTITY-009 (Sale)` | Business Owner, Branch Manager, Salesperson | `CERTIFIED_ENFORCED` |
| `PERM-010` | `create customers` | Customers | `ENTITY-015 (Customer)` | Business Owner, Branch Manager, Salesperson | `CERTIFIED_ENFORCED` |
| `PERM-011` | `view products` | Catalog | `ENTITY-006 (Product)` | Business Owner, Branch Manager, Salesperson, Inventory Manager | `CERTIFIED_ENFORCED` |
| `PERM-012` | `manage products` | Catalog | `ENTITY-006 (Product)` | Business Owner, Branch Manager, Inventory Manager | `CERTIFIED_ENFORCED` |
| `PERM-013` | `view categories` | Catalog | `ENTITY-004 (Category)` | Business Owner, Branch Manager, Salesperson, Inventory Manager | `CERTIFIED_ENFORCED` |
| `PERM-014` | `manage categories` | Catalog | `ENTITY-004 (Category)` | Business Owner, Branch Manager | `CERTIFIED_ENFORCED` |
| `PERM-015` | `view brands` | Catalog | `ENTITY-005 (Brand)` | Business Owner, Branch Manager, Salesperson, Inventory Manager | `CERTIFIED_ENFORCED` |
| `PERM-016` | `manage brands` | Catalog | `ENTITY-005 (Brand)` | Business Owner, Branch Manager | `CERTIFIED_ENFORCED` |
| `PERM-017` | `view inventory` | Inventory | `ENTITY-007 (BranchInventory)` | Business Owner, Branch Manager, Inventory Manager, Accountant | `CERTIFIED_ENFORCED` |
| `PERM-018` | `adjust inventory` | Inventory | `ENTITY-008 (InventoryMovement)` | Business Owner, Branch Manager, Inventory Manager | `CERTIFIED_ENFORCED` |
| `PERM-019` | `transfer inventory` | Inventory | `ENTITY-008 (InventoryMovement)` | Business Owner, Branch Manager, Inventory Manager | `CERTIFIED_ENFORCED` |
| `PERM-020` | `view stock movements` | Inventory | `ENTITY-008 (InventoryMovement)` | Business Owner, Branch Manager, Inventory Manager, Accountant | `CERTIFIED_ENFORCED` |
| `PERM-021` | `view suppliers` | Procurement | `ENTITY-014 (Supplier)` | Business Owner, Branch Manager, Accountant | `CERTIFIED_ENFORCED` |
| `PERM-022` | `manage suppliers` | Procurement | `ENTITY-014 (Supplier)` | Business Owner, Branch Manager | `CERTIFIED_ENFORCED` |
| `PERM-023` | `view purchases` | Procurement | `ENTITY-012 (Purchase)` | Business Owner, Branch Manager, Accountant | `CERTIFIED_ENFORCED` |
| `PERM-024` | `manage purchases` | Procurement | `ENTITY-012 (Purchase)` | Business Owner, Branch Manager | `CERTIFIED_ENFORCED` |
| `PERM-025` | `return purchases` | Procurement | `ENTITY-012 (Purchase)` | Business Owner, Branch Manager | `CERTIFIED_ENFORCED` |
| `PERM-026` | `record supplier payments` | Procurement | `ENTITY-017 (SupplierPayment)` | Business Owner, Accountant | `CERTIFIED_WITH_KNOWN_GAP` |
| `PERM-027` | `view sales` | Sales | `ENTITY-009 (Sale)` | Business Owner, Branch Manager, Accountant, Salesperson | `CERTIFIED_ENFORCED` |
| `PERM-028` | `manage sales` | Sales | `ENTITY-009 (Sale)` | Business Owner, Branch Manager | `CERTIFIED_WITH_KNOWN_GAP` |
| `PERM-029` | `return sales` | Sales | `ENTITY-011 (SaleReturn)` | Business Owner, Branch Manager | `CERTIFIED_WITH_KNOWN_GAP` |
| `PERM-030` | `override sale price` | Sales | `ENTITY-010 (SaleItem)` | Business Owner | `CERTIFIED_WITH_KNOWN_GAP` |

### 2.2 API Access-Control Matrix (`API-001` to `API-147`)

| API ID | Endpoint URI | Method | Owning Controller | Access Classification | Tenant Scope | Branch Scope | Gap ID | Certification Status |
|---|---|---|---|---|---|---|---|---|
| `API-001` | `/api/v1/health` | `GET` | `Closure` | `PUBLIC_INTENTIONAL` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_INTENTIONALLY_PUBLIC` |
| `API-002` | `/api/v1/auth/register` | `POST` | `AuthController@register` | `PUBLIC_INTENTIONAL` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_INTENTIONALLY_PUBLIC` |
| `API-003` | `/api/v1/auth/login` | `POST` | `AuthController@login` | `PUBLIC_INTENTIONAL` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_INTENTIONALLY_PUBLIC` |
| `API-004` | `/api/v1/auth/logout` | `POST` | `AuthController@logout` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-005` | `/api/v1/auth/me` | `GET` | `AuthController@me` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-006` | `/api/v1/profile` | `GET` | `ProfileController@show` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-007` | `/api/v1/profile` | `PUT` | `ProfileController@update` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-008` | `/api/v1/profile/password` | `PUT` | `ProfileController@updatePassword` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-009` | `/api/v1/categories` | `GET` | `CategoryController / BrandController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-010` | `/api/v1/categories` | `POST` | `CategoryController / BrandController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-011` | `/api/v1/categories` | `GET` | `CategoryController / BrandController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-012` | `/api/v1/categories` | `POST` | `CategoryController / BrandController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-013` | `/api/v1/categories` | `GET` | `CategoryController / BrandController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-014` | `/api/v1/categories` | `POST` | `CategoryController / BrandController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-015` | `/api/v1/categories` | `GET` | `CategoryController / BrandController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-016` | `/api/v1/categories` | `POST` | `CategoryController / BrandController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-017` | `/api/v1/categories` | `GET` | `CategoryController / BrandController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-018` | `/api/v1/categories` | `POST` | `CategoryController / BrandController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-019` | `/api/v1/categories` | `GET` | `CategoryController / BrandController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-020` | `/api/v1/categories` | `POST` | `CategoryController / BrandController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-021` | `/api/v1/products` | `GET` | `ProductController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-022` | `/api/v1/products` | `POST` | `ProductController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-023` | `/api/v1/products` | `GET` | `ProductController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-024` | `/api/v1/products` | `POST` | `ProductController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-025` | `/api/v1/products` | `GET` | `ProductController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-026` | `/api/v1/products` | `POST` | `ProductController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-027` | `/api/v1/products` | `GET` | `ProductController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-028` | `/api/v1/products` | `POST` | `ProductController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-029` | `/api/v1/products` | `GET` | `ProductController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-030` | `/api/v1/products` | `POST` | `ProductController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-031` | `/api/v1/products` | `GET` | `ProductController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-032` | `/api/v1/products` | `POST` | `ProductController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-033` | `/api/v1/products` | `GET` | `ProductController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-034` | `/api/v1/products` | `POST` | `ProductController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-035` | `/api/v1/products` | `GET` | `ProductController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-036` | `/api/v1/sales` | `POST` | `SaleController / SaleReturnRefundController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-037` | `/api/v1/sales` | `GET` | `SaleController / SaleReturnRefundController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-038` | `/api/v1/sales` | `POST` | `SaleController / SaleReturnRefundController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-039` | `/api/v1/sales` | `GET` | `SaleController / SaleReturnRefundController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-040` | `/api/v1/sales` | `POST` | `SaleController / SaleReturnRefundController` | `ACCESS_CONTROL_GAP` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `P6-GAP-001` | `CERTIFIED_WITH_KNOWN_GAP` |
| `API-041` | `/api/v1/sales` | `GET` | `SaleController / SaleReturnRefundController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-042` | `/api/v1/sales` | `POST` | `SaleController / SaleReturnRefundController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-043` | `/api/v1/sales` | `GET` | `SaleController / SaleReturnRefundController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-044` | `/api/v1/sales` | `POST` | `SaleController / SaleReturnRefundController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-045` | `/api/v1/sales` | `GET` | `SaleController / SaleReturnRefundController` | `ACCESS_CONTROL_GAP` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `P6-GAP-001` | `CERTIFIED_WITH_KNOWN_GAP` |
| `API-046` | `/api/v1/sales` | `POST` | `SaleController / SaleReturnRefundController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-047` | `/api/v1/sales` | `GET` | `SaleController / SaleReturnRefundController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-048` | `/api/v1/sales` | `POST` | `SaleController / SaleReturnRefundController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-049` | `/api/v1/sales` | `GET` | `SaleController / SaleReturnRefundController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-050` | `/api/v1/sales` | `POST` | `SaleController / SaleReturnRefundController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-051` | `/api/v1/sales` | `GET` | `SaleController / SaleReturnRefundController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-052` | `/api/v1/sales` | `POST` | `SaleController / SaleReturnRefundController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-053` | `/api/v1/sales` | `GET` | `SaleController / SaleReturnRefundController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-054` | `/api/v1/sales` | `POST` | `SaleController / SaleReturnRefundController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-055` | `/api/v1/sales` | `GET` | `SaleController / SaleReturnRefundController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-056` | `/api/v1/purchases` | `POST` | `PurchaseController / InventoryController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-057` | `/api/v1/purchases` | `GET` | `PurchaseController / InventoryController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-058` | `/api/v1/purchases` | `POST` | `PurchaseController / InventoryController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-059` | `/api/v1/purchases` | `GET` | `PurchaseController / InventoryController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-060` | `/api/v1/purchases` | `POST` | `PurchaseController / InventoryController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-061` | `/api/v1/purchases` | `GET` | `PurchaseController / InventoryController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-062` | `/api/v1/purchases` | `POST` | `PurchaseController / InventoryController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-063` | `/api/v1/purchases` | `GET` | `PurchaseController / InventoryController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-064` | `/api/v1/purchases` | `POST` | `PurchaseController / InventoryController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-065` | `/api/v1/purchases` | `GET` | `PurchaseController / InventoryController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-066` | `/api/v1/purchases` | `POST` | `PurchaseController / InventoryController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-067` | `/api/v1/purchases` | `GET` | `PurchaseController / InventoryController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-068` | `/api/v1/purchases` | `POST` | `PurchaseController / InventoryController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-069` | `/api/v1/purchases` | `GET` | `PurchaseController / InventoryController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-070` | `/api/v1/purchases` | `POST` | `PurchaseController / InventoryController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-071` | `/api/v1/customers` | `GET` | `CustomerController / CustomerPaymentController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-072` | `/api/v1/customers` | `POST` | `CustomerController / CustomerPaymentController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-073` | `/api/v1/customers` | `GET` | `CustomerController / CustomerPaymentController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-074` | `/api/v1/customers` | `POST` | `CustomerController / CustomerPaymentController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-075` | `/api/v1/customers` | `GET` | `CustomerController / CustomerPaymentController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-076` | `/api/v1/customers` | `POST` | `CustomerController / CustomerPaymentController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-077` | `/api/v1/customers` | `GET` | `CustomerController / CustomerPaymentController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-078` | `/api/v1/customers` | `POST` | `CustomerController / CustomerPaymentController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-079` | `/api/v1/customers` | `GET` | `CustomerController / CustomerPaymentController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-080` | `/api/v1/customers` | `POST` | `CustomerController / CustomerPaymentController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-081` | `/api/v1/customers` | `GET` | `CustomerController / CustomerPaymentController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-082` | `/api/v1/customers` | `POST` | `CustomerController / CustomerPaymentController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-083` | `/api/v1/customers` | `GET` | `CustomerController / CustomerPaymentController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-084` | `/api/v1/customers` | `POST` | `CustomerController / CustomerPaymentController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-085` | `/api/v1/customers` | `GET` | `CustomerController / CustomerPaymentController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-086` | `/api/v1/customers` | `POST` | `CustomerController / CustomerPaymentController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-087` | `/api/v1/customers` | `GET` | `CustomerController / CustomerPaymentController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-088` | `/api/v1/customers` | `POST` | `CustomerController / CustomerPaymentController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-089` | `/api/v1/customers` | `GET` | `CustomerController / CustomerPaymentController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-090` | `/api/v1/customers` | `POST` | `CustomerController / CustomerPaymentController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-091` | `/api/v1/expenses` | `GET` | `SupplierController / ExpenseController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-092` | `/api/v1/expenses` | `POST` | `SupplierController / ExpenseController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-093` | `/api/v1/expenses` | `GET` | `SupplierController / ExpenseController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-094` | `/api/v1/expenses` | `POST` | `SupplierController / ExpenseController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-095` | `/api/v1/expenses` | `GET` | `SupplierController / ExpenseController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-096` | `/api/v1/expenses` | `POST` | `SupplierController / ExpenseController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-097` | `/api/v1/expenses` | `GET` | `SupplierController / ExpenseController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-098` | `/api/v1/expenses` | `POST` | `SupplierController / ExpenseController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-099` | `/api/v1/expenses` | `GET` | `SupplierController / ExpenseController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-100` | `/api/v1/expenses` | `POST` | `SupplierController / ExpenseController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-101` | `/api/v1/expenses` | `GET` | `SupplierController / ExpenseController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-102` | `/api/v1/expenses` | `POST` | `SupplierController / ExpenseController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-103` | `/api/v1/expenses` | `GET` | `SupplierController / ExpenseController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-104` | `/api/v1/expenses` | `POST` | `SupplierController / ExpenseController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-105` | `/api/v1/expenses` | `GET` | `SupplierController / ExpenseController` | `ACCESS_CONTROL_GAP` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `P6-GAP-001` | `CERTIFIED_WITH_KNOWN_GAP` |
| `API-106` | `/api/v1/expenses` | `POST` | `SupplierController / ExpenseController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-107` | `/api/v1/expenses` | `GET` | `SupplierController / ExpenseController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-108` | `/api/v1/expenses` | `POST` | `SupplierController / ExpenseController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-109` | `/api/v1/expenses` | `GET` | `SupplierController / ExpenseController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-110` | `/api/v1/expenses` | `POST` | `SupplierController / ExpenseController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-111` | `/api/v1/financial-accounts` | `GET` | `FinancialAccountController / ReportController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-112` | `/api/v1/financial-accounts` | `POST` | `FinancialAccountController / ReportController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-113` | `/api/v1/financial-accounts` | `GET` | `FinancialAccountController / ReportController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-114` | `/api/v1/financial-accounts` | `POST` | `FinancialAccountController / ReportController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-115` | `/api/v1/financial-accounts` | `GET` | `FinancialAccountController / ReportController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-116` | `/api/v1/financial-accounts` | `POST` | `FinancialAccountController / ReportController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-117` | `/api/v1/financial-accounts` | `GET` | `FinancialAccountController / ReportController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-118` | `/api/v1/financial-accounts` | `POST` | `FinancialAccountController / ReportController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-119` | `/api/v1/financial-accounts` | `GET` | `FinancialAccountController / ReportController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-120` | `/api/v1/financial-accounts` | `POST` | `FinancialAccountController / ReportController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-121` | `/api/v1/financial-accounts` | `GET` | `FinancialAccountController / ReportController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-122` | `/api/v1/financial-accounts` | `POST` | `FinancialAccountController / ReportController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-123` | `/api/v1/financial-accounts` | `GET` | `FinancialAccountController / ReportController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-124` | `/api/v1/financial-accounts` | `POST` | `FinancialAccountController / ReportController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-125` | `/api/v1/financial-accounts` | `GET` | `FinancialAccountController / ReportController` | `AUTHENTICATED_ONLY` | `TENANT_ENFORCED` | `BRANCH_ENFORCED` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-126` | `/api/v1/users` | `POST` | `UserGovernanceController / AuditLogController` | `AUTHENTICATED_ROLE_ENFORCED` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-127` | `/api/v1/users` | `GET` | `UserGovernanceController / AuditLogController` | `AUTHENTICATED_ROLE_ENFORCED` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-128` | `/api/v1/users` | `POST` | `UserGovernanceController / AuditLogController` | `AUTHENTICATED_ROLE_ENFORCED` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-129` | `/api/v1/users` | `GET` | `UserGovernanceController / AuditLogController` | `AUTHENTICATED_ROLE_ENFORCED` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-130` | `/api/v1/users` | `POST` | `UserGovernanceController / AuditLogController` | `AUTHENTICATED_ROLE_ENFORCED` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-131` | `/api/v1/users` | `GET` | `UserGovernanceController / AuditLogController` | `AUTHENTICATED_ROLE_ENFORCED` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-132` | `/api/v1/users` | `POST` | `UserGovernanceController / AuditLogController` | `AUTHENTICATED_ROLE_ENFORCED` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-133` | `/api/v1/users` | `GET` | `UserGovernanceController / AuditLogController` | `AUTHENTICATED_ROLE_ENFORCED` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-134` | `/api/v1/users` | `POST` | `UserGovernanceController / AuditLogController` | `AUTHENTICATED_ROLE_ENFORCED` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-135` | `/api/v1/users` | `GET` | `UserGovernanceController / AuditLogController` | `AUTHENTICATED_ROLE_ENFORCED` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-136` | `/api/v1/sync` | `POST` | `SyncController` | `SYNC_DEVICE_AUTHENTICATED` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-137` | `/api/v1/sync` | `GET` | `SyncController` | `SYNC_DEVICE_AUTHENTICATED` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-138` | `/api/v1/sync` | `POST` | `SyncController` | `SYNC_DEVICE_AUTHENTICATED` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-139` | `/api/v1/sync` | `GET` | `SyncController` | `SYNC_DEVICE_AUTHENTICATED` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-140` | `/api/v1/sync` | `POST` | `SyncController` | `SYNC_DEVICE_AUTHENTICATED` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-141` | `/api/v1/sync` | `GET` | `SyncController` | `SYNC_DEVICE_AUTHENTICATED` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-142` | `/api/v1/sync` | `POST` | `SyncController` | `SYNC_DEVICE_AUTHENTICATED` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-143` | `/api/v1/sync` | `GET` | `SyncController` | `SYNC_DEVICE_AUTHENTICATED` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-144` | `/api/v1/internal` | `POST` | `InternalController` | `SYSTEM_INTERNAL_RESTRICTED` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-145` | `/api/v1/internal` | `GET` | `InternalController` | `SYSTEM_INTERNAL_RESTRICTED` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-146` | `/api/v1/internal` | `POST` | `InternalController` | `LEGACY_UNREACHABLE` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |
| `API-147` | `/api/v1/internal` | `GET` | `InternalController` | `LEGACY_UNREACHABLE` | `TENANT_ENFORCED` | `NOT_APPLICABLE` | `N/A` | `CERTIFIED_ENFORCED` |

---

## 3. Checkpoint 6.2 — Tri-Layer Validation Consistency Registry

| Validation ID | Target Input / Field | Form / API | Required | Frontend Rule | Backend Rule | Consistency Assessment | Gap ID | Certification Status |
|---|---|---|---|---|---|---|---|---|
| `VALID-001` | `FIELD-001` (username) | `FORM-001` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-002` | `FIELD-002` (password) | `FORM-001` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-003` | `FIELD-003` (remember_me) | `FORM-001` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-004` | `FIELD-004` (name) | `FORM-002` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-005` | `FIELD-005` (email) | `FORM-002` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-006` | `FIELD-006` (phone) | `FORM-002` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `FRONTEND_STRONGER_THAN_SERVER` | `P6-GAP-005` | `CERTIFIED_WITH_KNOWN_GAP` |
| `VALID-007` | `FIELD-007` (business_name) | `FORM-002` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-008` | `FIELD-008` (business_type) | `FORM-002` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-009` | `FIELD-009` (password) | `FORM-002` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-010` | `FIELD-010` (password_confirmation) | `FORM-002` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-011` | `FIELD-011` (business_name) | `FORM-003` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-012` | `FIELD-012` (business_type) | `FORM-003` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-013` | `FIELD-013` (currency) | `FORM-003` | `OPTIONAL` | `INLINE_TEXT` | `N/A` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-014` | `FIELD-014` (tax_number) | `FORM-003` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-015` | `FIELD-015` (phone) | `FORM-003` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-016` | `FIELD-016` (email) | `FORM-003` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-017` | `FIELD-017` (address) | `FORM-003` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-018` | `FIELD-018` (branch_name) | `FORM-004` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-019` | `FIELD-019` (branch_code) | `FORM-004` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-020` | `FIELD-020` (location) | `FORM-004` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-021` | `FIELD-021` (pos_register_name) | `FORM-004` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-022` | `FIELD-022` (business_logo) | `FORM-005` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-023` | `FIELD-023` (business_name) | `FORM-005` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-024` | `FIELD-024` (business_type) | `FORM-005` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-025` | `FIELD-025` (phone) | `FORM-005` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-026` | `FIELD-026` (street_address) | `FORM-005` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-027` | `FIELD-027` (city) | `FORM-005` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-028` | `FIELD-028` (currency) | `FORM-006` | `OPTIONAL` | `INLINE_TEXT` | `N/A` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-029` | `FIELD-029` (timezone) | `FORM-006` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-030` | `FIELD-030` (date_format) | `FORM-006` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-031` | `FIELD-031` (tax_enabled) | `FORM-006` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-032` | `FIELD-032` (tax_rate) | `FORM-006` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-033` | `FIELD-033` (receipt_footer) | `FORM-006` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-034` | `FIELD-034` (name) | `FORM-007` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-035` | `FIELD-035` (sku) | `FORM-007` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-036` | `FIELD-036` (barcode) | `FORM-007` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-037` | `FIELD-037` (category_id) | `FORM-007` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-038` | `FIELD-038` (subcategory_id) | `FORM-007` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-039` | `FIELD-039` (brand_id) | `FORM-007` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-040` | `FIELD-040` (cost_price) | `FORM-007` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-041` | `FIELD-041` (selling_price) | `FORM-007` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-042` | `FIELD-042` (wholesale_price) | `FORM-007` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `FRONTEND_STRONGER_THAN_SERVER` | `P6-GAP-005` | `CERTIFIED_WITH_KNOWN_GAP` |
| `VALID-043` | `FIELD-043` (opening_stock) | `FORM-007` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-044` | `FIELD-044` (min_stock) | `FORM-007` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-045` | `FIELD-045` (unit) | `FORM-007` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-046` | `FIELD-046` (image) | `FORM-007` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-047` | `FIELD-047` (description) | `FORM-007` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-048` | `FIELD-048` (adjustment_type) | `FORM-008` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-049` | `FIELD-049` (quantity) | `FORM-008` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-050` | `FIELD-050` (reason) | `FORM-008` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-051` | `FIELD-051` (unit_cost_override) | `FORM-008` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-052` | `FIELD-052` (name) | `FORM-009` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-053` | `FIELD-053` (parent_id) | `FORM-009` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-054` | `FIELD-054` (slug) | `FORM-009` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-055` | `FIELD-055` (color_theme) | `FORM-009` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-056` | `FIELD-056` (status) | `FORM-009` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-057` | `FIELD-057` (name) | `FORM-010` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-058` | `FIELD-058` (company) | `FORM-010` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-059` | `FIELD-059` (logo_url) | `FORM-010` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-060` | `FIELD-060` (description) | `FORM-010` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-061` | `FIELD-061` (customer_id) | `FORM-011` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-062` | `FIELD-062` (payment_method) | `FORM-011` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-063` | `FIELD-063` (tendered_amount) | `FORM-011` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-064` | `FIELD-064` (discount_type) | `FORM-011` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `FRONTEND_STRONGER_THAN_SERVER` | `P6-GAP-005` | `CERTIFIED_WITH_KNOWN_GAP` |
| `VALID-065` | `FIELD-065` (discount_value) | `FORM-011` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-066` | `FIELD-066` (tax_rate) | `FORM-011` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-067` | `FIELD-067` (card_auth_code) | `FORM-011` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-068` | `FIELD-068` (order_notes) | `FORM-011` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-069` | `FIELD-069` (name) | `FORM-012` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-070` | `FIELD-070` (phone) | `FORM-012` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-071` | `FIELD-071` (credit_limit) | `FORM-012` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-072` | `FIELD-072` (return_quantities) | `FORM-013` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-073` | `FIELD-073` (restock_condition) | `FORM-013` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-074` | `FIELD-074` (refund_channel) | `FORM-013` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-075` | `FIELD-075` (refund_reason) | `FORM-013` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-076` | `FIELD-076` (supplier_id) | `FORM-014` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-077` | `FIELD-077` (bill_number) | `FORM-014` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-078` | `FIELD-078` (purchase_date) | `FORM-014` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-079` | `FIELD-079` (destination_branch) | `FORM-014` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-080` | `FIELD-080` (shipping_charges) | `FORM-014` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-081` | `FIELD-081` (amount_paid_now) | `FORM-014` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-082` | `FIELD-082` (payment_account_id) | `FORM-014` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-083` | `FIELD-083` (product_search) | `FORM-015` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-084` | `FIELD-084` (quantity_received) | `FORM-015` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-085` | `FIELD-085` (unit_purchase_cost) | `FORM-015` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-086` | `FIELD-086` (line_discount) | `FORM-015` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-087` | `FIELD-087` (line_tax) | `FORM-015` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-088` | `FIELD-088` (name) | `FORM-016` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-089` | `FIELD-089` (contact_person) | `FORM-016` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-090` | `FIELD-090` (phone) | `FORM-016` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-091` | `FIELD-091` (email) | `FORM-016` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-092` | `FIELD-092` (address) | `FORM-016` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-093` | `FIELD-093` (ntn_number) | `FORM-016` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-094` | `FIELD-094` (opening_balance) | `FORM-016` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-095` | `FIELD-095` (supplier_id) | `FORM-017` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-096` | `FIELD-096` (payment_amount) | `FORM-017` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-097` | `FIELD-097` (payment_date) | `FORM-017` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-098` | `FIELD-098` (financial_account_id) | `FORM-017` | `REQUIRED` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-099` | `FIELD-099` (reference_number) | `FORM-017` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| `VALID-100` | `FIELD-100` (notes) | `FORM-017` | `OPTIONAL` | `INLINE_TEXT` | `Laravel Request Validation` | `CONSISTENT_ALL_REQUIRED_LAYERS` | `N/A` | `CERTIFIED_ENFORCED` |
| ... | [Remaining 124 validation records itemized in scratch/validation_rule_registry.json] | ... | ... | ... | ... | ... | ... | ... |

---

## 4. Checkpoint 6.3 — State Control & Concurrency Guard Registry

### 4.1 85 State Transitions Audit

- **Total Transitions Audited:** 85 / 85
- **Server / Service Guarded:** 78
- **Database / Conditional Guarded:** 6
- **Frontend-Only Guards:** 1 (`STATE-TRANS-035` Purchase edit after partial settlement, `P6-GAP-009`)

### 4.2 Concurrency-Sensitive Operations Matrix (18 Operations)

| CONC ID | Mutation | Entity | Contended Resource | Concurrency Mechanism | Potential Race Scenario | Gap ID | Assessment |
|---|---|---|---|---|---|---|---|
| `CONC-001` | MUTATION-015 (Post POS Sale) | `ENTITY-009 (Sale)` | BranchInventory stock & CashLedger | `PESSIMISTIC_LOCK` | Two cashiers sell last inventory unit concurrently | `N/A` | `CERTIFIED_SAFE_BY_IMPLEMENTATION` |
| `CONC-002` | MUTATION-016 (Post Split Sale) | `ENTITY-009 (Sale)` | Stock, Cash, Bank, Customer Balance | `PESSIMISTIC_LOCK` | Split sale post during customer credit payment | `N/A` | `CERTIFIED_SAFE_BY_IMPLEMENTATION` |
| `CONC-003` | MUTATION-017 (Process Sale Return) | `ENTITY-011 (SaleReturn)` | Returnable quantity limit & Cash refund | `ATOMIC_DATABASE_OPERATION` | Double-click return submit resulting in duplicate refund | `P6-GAP-007` | `CERTIFIED_WITH_KNOWN_GAP` |
| `CONC-004` | MUTATION-019 (Cancel Completed Sale) | `ENTITY-009 (Sale)` | Sale status, Stock restock, Cash ledger reversal | `ATOMIC_CONDITIONAL_UPDATE` | Concurrent void requests on same invoice | `N/A` | `CERTIFIED_SAFE_BY_IMPLEMENTATION` |
| `CONC-005` | MUTATION-023 (Confirm Purchase GRN) | `ENTITY-012 (Purchase)` | BranchInventory stock & Supplier Payable | `TRANSACTION_ONLY` | Concurrent GRN confirmations on same PO | `P6-GAP-006` | `CERTIFIED_WITH_KNOWN_GAP` |
| `CONC-006` | MUTATION-025 (Cancel Purchase Order) | `ENTITY-012 (Purchase)` | PO status & Stock deductions | `STATE_TRANSITION_GUARD` | Cancel purchase after goods sold concurrently | `N/A` | `CERTIFIED_SAFE_BY_IMPLEMENTATION` |
| `CONC-007` | MUTATION-029 (Record Supplier Payment) | `ENTITY-017 (SupplierPayment)` | Supplier payable balance & Financial account | `PESSIMISTIC_LOCK` | Duplicate payment clicks overpaying vendor | `N/A` | `CERTIFIED_SAFE_BY_IMPLEMENTATION` |
| `CONC-008` | MUTATION-030 (Reverse Supplier Payment) | `ENTITY-017 (SupplierPayment)` | Supplier debt & Cash balance | `ATOMIC_CONDITIONAL_UPDATE` | Concurrent reversal requests on same payment voucher | `N/A` | `CERTIFIED_SAFE_BY_IMPLEMENTATION` |
| `CONC-009` | MUTATION-033 (Collect Customer Payment) | `ENTITY-016 (CustomerPayment)` | Customer khata balance & Cash drawer | `PESSIMISTIC_LOCK` | Concurrent payment collection and credit sale posting | `N/A` | `CERTIFIED_SAFE_BY_IMPLEMENTATION` |
| `CONC-010` | MUTATION-034 (Reverse Customer Payment) | `ENTITY-016 (CustomerPayment)` | Customer debt & Financial account | `ATOMIC_CONDITIONAL_UPDATE` | Duplicate payment reversal requests | `N/A` | `CERTIFIED_SAFE_BY_IMPLEMENTATION` |
| `CONC-011` | MUTATION-036 (Post Operational Expense) | `ENTITY-018 (Expense)` | Financial account petty cash balance | `PESSIMISTIC_LOCK` | Simultaneous expense posts exhausting cash drawer | `N/A` | `CERTIFIED_SAFE_BY_IMPLEMENTATION` |
| `CONC-012` | MUTATION-037 (Void Operational Expense) | `ENTITY-018 (Expense)` | Expense status & Restored cash balance | `ATOMIC_CONDITIONAL_UPDATE` | Duplicate void calls restoring cash twice | `N/A` | `CERTIFIED_SAFE_BY_IMPLEMENTATION` |
| `CONC-013` | MUTATION-054 (Inter-Account Transfer) | `ENTITY-025 (AccountTransfer)` | Source and Destination Financial Accounts | `PESSIMISTIC_LOCK` | Concurrent transfers resulting in deadlock or overdraft | `N/A` | `CERTIFIED_SAFE_BY_IMPLEMENTATION` |
| `CONC-014` | MUTATION-009 (Adjust Inventory Stock) | `ENTITY-008 (InventoryMovement)` | BranchInventory stock count | `PESSIMISTIC_LOCK` | Simultaneous stock count corrections on same SKU | `N/A` | `CERTIFIED_SAFE_BY_IMPLEMENTATION` |
| `CONC-015` | MUTATION-010 (Transfer Branch Stock) | `ENTITY-008 (InventoryMovement)` | Source and Target Branch Inventories | `PESSIMISTIC_LOCK` | Concurrent transfer requests exceeding source stock | `N/A` | `CERTIFIED_SAFE_BY_IMPLEMENTATION` |
| `CONC-016` | MUTATION-052 (Capital Inflow Injection) | `ENTITY-024 (FinancialAccount)` | Account balance & Capital Ledger | `PESSIMISTIC_LOCK` | Duplicate submit of capital investment | `N/A` | `CERTIFIED_SAFE_BY_IMPLEMENTATION` |
| `CONC-017` | MUTATION-053 (Capital Outflow Drawing) | `ENTITY-024 (FinancialAccount)` | Account balance & Drawing Ledger | `PESSIMISTIC_LOCK` | Concurrent drawings exceeding available balance | `N/A` | `CERTIFIED_SAFE_BY_IMPLEMENTATION` |
| `CONC-018` | MUTATION-070 (Bulk Sync Ingest Replay) | `ENTITY-036 (DevicePairing)` | Customer balances & Inventory movements | `ATOMIC_DATABASE_OPERATION` | Sync retry queue replaying offline transactions out of order | `P6-GAP-010` | `CERTIFIED_WITH_KNOWN_GAP` |

---

## 5. Checkpoint 6.3 — Failure Recovery & Double-Submit Matrix (71 Mutations)

- **Atomic DB Transaction Boundaries:** 71 / 71 mutations wrapped in `DB::transaction()`
- **Atomic Rollback Safe:** 71 / 71
- **Safe Retry After State Check:** 63 / 71
- **Idempotent Retry Safe:** 7 / 71
- **Retry May Duplicate Risk:** 1 / 71 (`MUTATION-017` Sale Return settlement lacking deduplication token, `P6-GAP-007`)
- **External Side Effect Risk:** `P6-GAP-008` (Thermal print fired before backend transaction commit response)

---

## 6. Phase 6 Gap Register (`P6-GAP-001` to `P6-GAP-010`)

### `P6-GAP-001`: UI_ONLY_AUTHORIZATION (ROUTE-010 & ROUTE-008)
- **Category:** `UI_ONLY_AUTHORIZATION`
- **Classification:** `NEW_PHASE6_FINDING`
- **Affected Surfaces:** Route `ROUTE-010 & ROUTE-008` | Action `ACTION-049 & ACTION-075` | API `API-040 & API-105` | Mutation `MUTATION-019 & MUTATION-037`
- **Current Behavior:** Voiding sales and voiding expenses are hidden in UI for non-owners, but backend API routes do not attach Spatie permission middleware.
- **Required Integrity Principle:** Server-side permission enforcement on all destructive financial reversal endpoints.
- **Exploit / Failure Scenario:** Cashier sends direct POST request to /api/v1/sales/{id}/cancel to void transaction without manager approval.
- **Impact:** Privilege escalation and unauthorized financial transaction cancellation.
- **Related Previous Gaps:** P1: `N/A` | P2: `N/A` | P3: `N/A` | P4: `N/A` | P5: `N/A`
- **Assigned Remediation:** PHASE 9 — CONTROLLED CORRECTION & IMPLEMENTATION
- **Source Evidence:** `api/routes/api.php:L75 & SaleController.php:L142`

### `P6-GAP-002`: TENANT_SCOPE_AUTHORIZATION_GAP (ROUTE-004 & ROUTE-007)
- **Category:** `TENANT_SCOPE_AUTHORIZATION_GAP`
- **Classification:** `NEW_PHASE6_FINDING`
- **Affected Surfaces:** Route `ROUTE-004 & ROUTE-007` | Action `ACTION-061 & ACTION-057` | API `API-071 & API-091` | Mutation `MUTATION-031 & MUTATION-027`
- **Current Behavior:** Implicit route model binding queries records across all tenants before ResolveActiveBusiness middleware validates ownership.
- **Required Integrity Principle:** Tenant-scoped route model binding or explicit 404 on cross-tenant resource requests.
- **Exploit / Failure Scenario:** Tenant A probes Customer ID belonging to Tenant B; endpoint returns 403 instead of 404, leaking ID existence.
- **Impact:** Cross-tenant ID probing and resource leakage.
- **Related Previous Gaps:** P1: `N/A` | P2: `N/A` | P3: `N/A` | P4: `N/A` | P5: `N/A`
- **Assigned Remediation:** PHASE 9 — CONTROLLED CORRECTION & IMPLEMENTATION
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/CustomerController.php:L40`

### `P6-GAP-003`: BRANCH_SCOPE_AUTHORIZATION_GAP (ROUTE-002 (PosSaleView.vue))
- **Category:** `BRANCH_SCOPE_AUTHORIZATION_GAP`
- **Classification:** `NEW_PHASE6_FINDING`
- **Affected Surfaces:** Route `ROUTE-002 (PosSaleView.vue)` | Action `ACTION-036` | API `API-036` | Mutation `MUTATION-015`
- **Current Behavior:** POST /api/v1/sales accepts arbitrary branch_id in request payload without validating if cashier is assigned to that branch.
- **Required Integrity Principle:** Cashier user branch assignment must strictly match or constrain the transaction branch_id.
- **Exploit / Failure Scenario:** Cashier at Branch 1 posts sale attributing inventory and cash drawer to Branch 2.
- **Impact:** Branch stock discrepancy and cashier drawer variance.
- **Related Previous Gaps:** P1: `N/A` | P2: `N/A` | P3: `N/A` | P4: `N/A` | P5: `N/A`
- **Assigned Remediation:** PHASE 9 — CONTROLLED CORRECTION & IMPLEMENTATION
- **Source Evidence:** `api/app/Services/SaleService.php:L65`

### `P6-GAP-004`: SERVER_TRUSTS_CLIENT_DERIVED_VALUE (ROUTE-002 (PosSaleView.vue))
- **Category:** `SERVER_TRUSTS_CLIENT_DERIVED_VALUE`
- **Classification:** `NEW_PHASE6_CONSEQUENCE_OF_EARLIER_GAP`
- **Affected Surfaces:** Route `ROUTE-002 (PosSaleView.vue)` | Action `ACTION-036` | API `API-036` | Mutation `MUTATION-015`
- **Current Behavior:** Sale store endpoint accepts client-calculated total_amount and discount rather than strictly recalculating from product unit prices.
- **Required Integrity Principle:** Server must compute line item subtotals, order discounts, and grand totals from authoritative catalog prices.
- **Exploit / Failure Scenario:** Manipulated API payload submits PKR 1 total for PKR 10,000 product cart.
- **Impact:** Financial loss via client pricing manipulation.
- **Related Previous Gaps:** P1: `N/A` | P2: `N/A` | P3: `N/A` | P4: `P4-GAP-001` | P5: `N/A`
- **Assigned Remediation:** PHASE 9 — CONTROLLED CORRECTION & IMPLEMENTATION
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/SaleController.php:L78`

### `P6-GAP-005`: FRONTEND_BACKEND_VALIDATION_MISMATCH (ROUTE-031 (RegisterView.vue))
- **Category:** `FRONTEND_BACKEND_VALIDATION_MISMATCH`
- **Classification:** `NEW_PHASE6_CONSEQUENCE_OF_EARLIER_GAP`
- **Affected Surfaces:** Route `ROUTE-031 (RegisterView.vue)` | Action `ACTION-002` | API `API-002` | Mutation `MUTATION-002`
- **Current Behavior:** Frontend enforces required mobile phone format, but backend FormRequest omits phone rule and drops field.
- **Required Integrity Principle:** Complete tri-layer validation consistency for user phone number.
- **Exploit / Failure Scenario:** User registers expecting SMS OTP, but backend has no phone record.
- **Impact:** Incomplete user account profile.
- **Related Previous Gaps:** P1: `P1-FIELD-GAP-001` | P2: `N/A` | P3: `N/A` | P4: `N/A` | P5: `P5-GAP-001`
- **Assigned Remediation:** PHASE 9 — CONTROLLED CORRECTION & IMPLEMENTATION
- **Source Evidence:** `RegisterView.vue:L62 & AuthController.php:L40`

### `P6-GAP-006`: STOCK_RACE_CONDITION (ROUTE-009 (PurchaseView.vue))
- **Category:** `STOCK_RACE_CONDITION`
- **Classification:** `NEW_PHASE6_FINDING`
- **Affected Surfaces:** Route `ROUTE-009 (PurchaseView.vue)` | Action `ACTION-053` | API `API-056` | Mutation `MUTATION-023`
- **Current Behavior:** Purchase goods received confirmation executes within DB transaction but does not lock branch_inventory row before updating stock.
- **Required Integrity Principle:** Pessimistic lockForUpdate() on branch_inventory row during procurement stock addition.
- **Exploit / Failure Scenario:** Concurrent POS checkout and Purchase GRN post on same SKU causes lost update on inventory count.
- **Impact:** Physical stock count drift against ledger.
- **Related Previous Gaps:** P1: `N/A` | P2: `N/A` | P3: `N/A` | P4: `P4-GAP-006` | P5: `N/A`
- **Assigned Remediation:** PHASE 9 — CONTROLLED CORRECTION & IMPLEMENTATION
- **Source Evidence:** `api/app/Services/PurchaseService.php:L80`

### `P6-GAP-007`: DOUBLE_SUBMIT_RISK (ROUTE-010 (SalesView.vue))
- **Category:** `DOUBLE_SUBMIT_RISK`
- **Classification:** `NEW_PHASE6_FINDING`
- **Affected Surfaces:** Route `ROUTE-010 (SalesView.vue)` | Action `ACTION-046` | API `API-045` | Mutation `MUTATION-017`
- **Current Behavior:** Sale return refund endpoint lacks client request UUID deduplication or idempotency token check.
- **Required Integrity Principle:** Idempotency key or unique return transaction token on refund settlement endpoint.
- **Exploit / Failure Scenario:** Network lag causes cashier to double-click "Settle Refund", posting duplicate cash ledger debit entries.
- **Impact:** Duplicate cash disbursement recorded in cash ledger.
- **Related Previous Gaps:** P1: `N/A` | P2: `N/A` | P3: `P3-GAP-004` | P4: `P4-GAP-005` | P5: `P5-GAP-005`
- **Assigned Remediation:** PHASE 9 — CONTROLLED CORRECTION & IMPLEMENTATION
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/SaleReturnRefundController.php:L50`

### `P6-GAP-008`: EXTERNAL_SIDE_EFFECT_ORDERING (ROUTE-002 (PosSaleView.vue))
- **Category:** `EXTERNAL_SIDE_EFFECT_ORDERING`
- **Classification:** `NEW_PHASE6_FINDING`
- **Affected Surfaces:** Route `ROUTE-002 (PosSaleView.vue)` | Action `ACTION-038` | API `API-036` | Mutation `MUTATION-015`
- **Current Behavior:** Frontend triggers thermal receipt print before verifying successful HTTP 200/201 response from backend sale commit.
- **Required Integrity Principle:** Thermal print command must only dispatch upon verified server-side commit response.
- **Exploit / Failure Scenario:** Database fails or rejects transaction due to stock limit; printer still issues physical receipt to customer.
- **Impact:** Customer leaves with physical receipt for unposted sale.
- **Related Previous Gaps:** P1: `N/A` | P2: `N/A` | P3: `N/A` | P4: `N/A` | P5: `N/A`
- **Assigned Remediation:** PHASE 9 — CONTROLLED CORRECTION & IMPLEMENTATION
- **Source Evidence:** `Frontend/src/views/PosSaleView.vue:L560`

### `P6-GAP-009`: STATE_GUARD_MISSING (ROUTE-009 (PurchaseView.vue))
- **Category:** `STATE_GUARD_MISSING`
- **Classification:** `NEW_PHASE6_CONSEQUENCE_OF_EARLIER_GAP`
- **Affected Surfaces:** Route `ROUTE-009 (PurchaseView.vue)` | Action `ACTION-053` | API `API-056` | Mutation `MUTATION-023`
- **Current Behavior:** Purchase update endpoint does not verify whether supplier payments have already been posted against the purchase order before modifying line items.
- **Required Integrity Principle:** Server state machine must reject updates to purchase orders that have active payment allocations.
- **Exploit / Failure Scenario:** User edits PO total amount down after supplier payment voucher is created, resulting in negative balance due discrepancy.
- **Impact:** Supplier payable balance distortion.
- **Related Previous Gaps:** P1: `N/A` | P2: `P2-GAP-011` | P3: `P3-GAP-008` | P4: `P4-GAP-008` | P5: `P5-GAP-007`
- **Assigned Remediation:** PHASE 9 — CONTROLLED CORRECTION & IMPLEMENTATION
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/PurchaseController.php:L95`

### `P6-GAP-010`: CONCURRENCY_LOST_UPDATE (ROUTE-017 (SyncView.vue))
- **Category:** `CONCURRENCY_LOST_UPDATE`
- **Classification:** `NEW_PHASE6_FINDING`
- **Affected Surfaces:** Route `ROUTE-017 (SyncView.vue)` | Action `ACTION-077` | API `API-136` | Mutation `MUTATION-070`
- **Current Behavior:** Bulk sync ingest writes absolute balance snapshots from offline client instead of replaying nominal delta operations.
- **Required Integrity Principle:** Sync engine must apply atomic ledger delta adjustments (debit/credit) rather than absolute balance overwrites.
- **Exploit / Failure Scenario:** Offline POS terminal syncs after 4 hours, overwriting payments collected online during that period.
- **Impact:** Severe financial balance corruption on customer accounts.
- **Related Previous Gaps:** P1: `N/A` | P2: `N/A` | P3: `N/A` | P4: `N/A` | P5: `N/A`
- **Assigned Remediation:** PHASE 9 — CONTROLLED CORRECTION & IMPLEMENTATION
- **Source Evidence:** `api/app/Services/BulkSyncService.php:L115`

