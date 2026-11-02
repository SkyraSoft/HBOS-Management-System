# HBOS ENTITY LIFECYCLE, CRUD & SIDE-EFFECT AUDIT
**Phase 2 Authoritative Functional Architecture Document**
**Status:** CERTIFIED COMPLETE

## 1. Executive Architecture Summary

This document provides the exhaustive, source-verified audit of all 46 database entities (36 business entities + 10 system/framework entities), 71 mutation entry points across controllers and services, 32 entity relationships, and all transaction side effects within the SkyraSoft HBOS platform.

### Summary Metrics
- **Total Entities Cataloged:** 46 (36 Business Entities, 10 System/Framework Entities)
- **Total Mutations Audited:** 71 write entry points across 30 Controllers and 18 Services
- **Total Relationships Audited:** 32
- **Side-Effect Assessments:** 71 / 71
- **Phase 2 Gap Findings (P2-GAP-001 to P2-GAP-020):** 20 recorded product defects
- **Certification Status:** CERTIFIED COMPLETE (with known source-verified product gaps mapped to Phase 9 remediation)

## 2. Canonical Entity Lifecycles (Checkpoint 2.1)

| Entity ID | Entity Name | Primary Table | Model | Entity Type | Classification | Initial State | Valid States | Delete Semantics | Gaps | Status |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `ENTITY-001` | **Business** | `businesses` | `App\Models\Business` | ORGANIZATIONAL | EXPLICIT_STATE_MACHINE | `ACTIVE` | ACTIVE, SUSPENDED, TRIAL, EXPIRED | SOFT_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-002` | **User** | `users` | `App\Models\User` | SECURITY | EXPLICIT_STATE_MACHINE | `ACTIVE` | ACTIVE, INACTIVE, LOCKED | SOFT_DELETE_ALLOWED | P1-GAP-001 | **CERTIFIED_WITH_KNOWN_GAP** |
| `ENTITY-003` | **BusinessUser** | `business_user` | `App\Models\Pivot (business_user)` | SECURITY | STATELESS_MASTER | `ACTIVE` | ACTIVE | HARD_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-004` | **Branch** | `branches` | `App\Models\Branch` | ORGANIZATIONAL | EXPLICIT_STATE_MACHINE | `ACTIVE` | ACTIVE, INACTIVE, CLOSED | SOFT_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-005` | **BranchUser** | `branch_user` | `App\Models\Pivot (branch_user)` | SECURITY | STATELESS_MASTER | `ACTIVE` | ACTIVE | HARD_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-006` | **PersonalAccessToken** | `personal_access_tokens` | `Laravel\Sanctum\PersonalAccessToken` | SYSTEM_FRAMEWORK | HARD_DELETE_LIFECYCLE | `ACTIVE` | ACTIVE, EXPIRED | HARD_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-007` | **PasswordResetToken** | `password_reset_tokens` | `Framework Table` | SYSTEM_FRAMEWORK | HARD_DELETE_LIFECYCLE | `PENDING` | PENDING, CONSUMED, EXPIRED | HARD_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-008` | **Session** | `sessions` | `Framework Table` | SYSTEM_FRAMEWORK | HARD_DELETE_LIFECYCLE | `ACTIVE` | ACTIVE, EXPIRED | HARD_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-009` | **Permission** | `permissions` | `Spatie\Permission\Models\Permission` | SECURITY | STATELESS_MASTER | `ACTIVE` | ACTIVE | HARD_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-010` | **Role** | `roles` | `Spatie\Permission\Models\Role` | SECURITY | STATELESS_MASTER | `ACTIVE` | ACTIVE | HARD_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-011` | **ModelHasPermission** | `model_has_permissions` | `Spatie Pivot` | SECURITY | STATELESS_MASTER | `ACTIVE` | ACTIVE | HARD_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-012` | **ModelHasRole** | `model_has_roles` | `Spatie Pivot` | SECURITY | STATELESS_MASTER | `ACTIVE` | ACTIVE | HARD_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-013` | **RoleHasPermission** | `role_has_permissions` | `Spatie Pivot` | SECURITY | STATELESS_MASTER | `ACTIVE` | ACTIVE | HARD_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-014` | **Category** | `categories` | `App\Models\Category` | MASTER_DATA | SOFT_DELETE_LIFECYCLE | `ACTIVE` | ACTIVE, DELETED | SOFT_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-015` | **Subcategory** | `subcategories` | `App\Models\Subcategory` | MASTER_DATA | SOFT_DELETE_LIFECYCLE | `ACTIVE` | ACTIVE, DELETED | SOFT_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-016` | **Brand** | `brands` | `App\Models\Brand` | MASTER_DATA | SOFT_DELETE_LIFECYCLE | `ACTIVE` | ACTIVE, DELETED | SOFT_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-017` | **Product** | `products` | `App\Models\Product` | MASTER_DATA | EXPLICIT_STATE_MACHINE | `ACTIVE` | ACTIVE, INACTIVE, DISCONTINUED, DELETED | SOFT_DELETE_ALLOWED | P1-GAP-002, P1-GAP-009 | **CERTIFIED_WITH_KNOWN_GAP** |
| `ENTITY-018` | **BranchInventory** | `branch_inventories` | `App\Models\BranchInventory` | BALANCE_CACHE | STATELESS_MASTER | `OUT_OF_STOCK` | IN_STOCK, LOW_STOCK, OUT_OF_STOCK, NEGATIVE_STOCK | RESTRICT | P1-GAP-003, P1-GAP-004 | **CERTIFIED_WITH_KNOWN_GAP** |
| `ENTITY-019` | **InventoryMovement** | `inventory_movements` | `App\Models\InventoryMovement` | EVENT_LEDGER | IMMUTABLE_LEDGER_EVENT | `POSTED` | POSTED, REVERSED | IMMUTABLE_NO_DELETE | P1-GAP-003, P1-GAP-004 | **CERTIFIED_WITH_KNOWN_GAP** |
| `ENTITY-020` | **Sale** | `sales` | `App\Models\Sale` | TRANSACTION_HEADER | EXPLICIT_STATE_MACHINE | `PAID` | PAID, PARTIAL, DUE, CANCELLED, RETURNED, REFUNDED | SOFT_DELETE_ALLOWED | P1-GAP-005, P1-GAP-011 | **CERTIFIED_WITH_KNOWN_GAP** |
| `ENTITY-021` | **SaleItem** | `sale_items` | `App\Models\SaleItem` | TRANSACTION_LINE | APPEND_ONLY_TRANSACTION_RECORD | `ACTIVE` | ACTIVE | RESTRICT (Cascade only with parent sale deletion) | P1-GAP-011 | **CERTIFIED_WITH_KNOWN_GAP** |
| `ENTITY-022` | **SaleReturn** | `sale_returns` | `App\Models\SaleReturn` | TRANSACTION_HEADER | EXPLICIT_STATE_MACHINE | `PENDING` | PENDING, APPROVED, REJECTED, REFUNDED, SETTLED | SOFT_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-023` | **SaleReturnItem** | `sale_return_items` | `App\Models\SaleReturnItem` | TRANSACTION_LINE | APPEND_ONLY_TRANSACTION_RECORD | `ACTIVE` | ACTIVE | CASCADE_WITH_PARENT | None | **CERTIFIED_COMPLETE** |
| `ENTITY-024` | **Customer** | `customers` | `App\Models\Customer` | MASTER_DATA | EXPLICIT_STATE_MACHINE | `ACTIVE` | ACTIVE, INACTIVE, BLOCKED, DELETED | SOFT_DELETE_ALLOWED | P1-GAP-007, P1-GAP-015 | **CERTIFIED_WITH_KNOWN_GAP** |
| `ENTITY-025` | **KhataTransaction** | `khata_transactions` | `App\Models\KhataTransaction` | EVENT_LEDGER | IMMUTABLE_LEDGER_EVENT | `POSTED` | POSTED, REVERSED | IMMUTABLE_NO_DELETE | P1-GAP-007 | **CERTIFIED_WITH_KNOWN_GAP** |
| `ENTITY-026` | **CustomerPayment** | `customer_payments` | `App\Models\CustomerPayment` | TRANSACTION_HEADER | EXPLICIT_STATE_MACHINE | `POSTED` | POSTED, REVERSED | IMMUTABLE_NO_DELETE | P1-GAP-007 | **CERTIFIED_WITH_KNOWN_GAP** |
| `ENTITY-027` | **Supplier** | `suppliers` | `App\Models\Supplier` | MASTER_DATA | EXPLICIT_STATE_MACHINE | `ACTIVE` | ACTIVE, INACTIVE, DELETED | SOFT_DELETE_ALLOWED | P1-GAP-007 | **CERTIFIED_WITH_KNOWN_GAP** |
| `ENTITY-028` | **Purchase** | `purchases` | `App\Models\Purchase` | TRANSACTION_HEADER | EXPLICIT_STATE_MACHINE | `RECEIVED` | RECEIVED, ORDERED, PENDING, CANCELLED | SOFT_DELETE_ALLOWED | P1-GAP-006 | **CERTIFIED_WITH_KNOWN_GAP** |
| `ENTITY-029` | **PurchaseItem** | `purchase_items` | `App\Models\PurchaseItem` | TRANSACTION_LINE | APPEND_ONLY_TRANSACTION_RECORD | `ACTIVE` | ACTIVE | CASCADE_WITH_PARENT | P1-GAP-006 | **CERTIFIED_WITH_KNOWN_GAP** |
| `ENTITY-030` | **SupplierPayment** | `supplier_payments` | `App\Models\SupplierPayment` | TRANSACTION_HEADER | EXPLICIT_STATE_MACHINE | `POSTED` | POSTED, REVERSED | IMMUTABLE_NO_DELETE | None | **CERTIFIED_COMPLETE** |
| `ENTITY-031` | **Expense** | `expenses` | `App\Models\Expense` | TRANSACTION_HEADER | EXPLICIT_STATE_MACHINE | `ACTIVE` | ACTIVE, VOIDED, DELETED | SOFT_DELETE_ALLOWED | P1-GAP-012 | **CERTIFIED_WITH_KNOWN_GAP** |
| `ENTITY-032` | **ExpenseCategory** | `expense_categories` | `App\Models\ExpenseCategory` | MASTER_DATA | STATELESS_MASTER | `ACTIVE` | ACTIVE | HARD_DELETE_ALLOWED | P1-GAP-016 | **CERTIFIED_WITH_KNOWN_GAP** |
| `ENTITY-033` | **RecurringExpense** | `recurring_expenses` | `App\Models\RecurringExpense` | CONFIGURATION | EXPLICIT_STATE_MACHINE | `ACTIVE` | ACTIVE, PAUSED, COMPLETED, CANCELLED | HARD_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-034` | **FinancialAccount** | `financial_accounts` | `App\Models\FinancialAccount` | BALANCE_CACHE | EXPLICIT_STATE_MACHINE | `ACTIVE` | ACTIVE, INACTIVE, FROZEN, DELETED | SOFT_DELETE_ALLOWED | P1-GAP-008 | **CERTIFIED_WITH_KNOWN_GAP** |
| `ENTITY-035` | **AccountMovement** | `account_movements` | `App\Models\AccountMovement` | EVENT_LEDGER | IMMUTABLE_LEDGER_EVENT | `POSTED` | POSTED, VOIDED, REVERSED | IMMUTABLE_NO_DELETE | P1-GAP-008 | **CERTIFIED_WITH_KNOWN_GAP** |
| `ENTITY-036` | **AccountTransfer** | `account_transfers` | `App\Models\AccountTransfer` | TRANSACTION_HEADER | EXPLICIT_STATE_MACHINE | `COMPLETED` | COMPLETED, REVERSED | IMMUTABLE_NO_DELETE | P1-GAP-013 | **CERTIFIED_WITH_KNOWN_GAP** |
| `ENTITY-037` | **CashLedger** | `cash_ledgers` | `App\Models\CashLedger` | EVENT_LEDGER | IMMUTABLE_LEDGER_EVENT | `RECORDED` | RECORDED | IMMUTABLE_NO_DELETE | None | **CERTIFIED_COMPLETE** |
| `ENTITY-038` | **Setting** | `settings` | `App\Models\Setting` | CONFIGURATION | STATELESS_MASTER | `ACTIVE` | ACTIVE | HARD_DELETE_ALLOWED | P1-GAP-010 | **CERTIFIED_WITH_KNOWN_GAP** |
| `ENTITY-039` | **Notification** | `notifications` | `Illuminate\Notifications\DatabaseNotification` | SYSTEM_FRAMEWORK | EXPLICIT_STATE_MACHINE | `UNREAD` | UNREAD, READ, DELETED | HARD_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-040` | **ActivityLog** | `activity_log` | `App\Models\ActivityLog` | SYSTEM_FRAMEWORK | IMMUTABLE_LEDGER_EVENT | `RECORDED` | RECORDED | IMMUTABLE_NO_DELETE | None | **CERTIFIED_COMPLETE** |
| `ENTITY-041` | **Job** | `jobs` | `Framework Queue Job` | SYSTEM_FRAMEWORK | HARD_DELETE_LIFECYCLE | `PENDING` | PENDING, RESERVED, PROCESSED, FAILED | HARD_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-042` | **JobBatch** | `job_batches` | `Framework Job Batch` | SYSTEM_FRAMEWORK | EXPLICIT_STATE_MACHINE | `PENDING` | PENDING, FINISHED, CANCELLED | HARD_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-043` | **FailedJob** | `failed_jobs` | `Framework Failed Job` | SYSTEM_FRAMEWORK | HARD_DELETE_LIFECYCLE | `RECORDED` | RECORDED, RETRIED, DISMISSED | HARD_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-044` | **CacheEntry** | `cache` | `Framework Cache Table` | SYSTEM_FRAMEWORK | HARD_DELETE_LIFECYCLE | `VALID` | VALID, EXPIRED | HARD_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-045` | **CacheLock** | `cache_locks` | `Framework Cache Lock Table` | SYSTEM_FRAMEWORK | HARD_DELETE_LIFECYCLE | `LOCKED` | LOCKED, RELEASED, EXPIRED | HARD_DELETE_ALLOWED | None | **CERTIFIED_COMPLETE** |
| `ENTITY-046` | **Employee** | `employees (UNMIGRATED)` | `App\Models\Employee` | MASTER_DATA | INCOMPLETE_LIFECYCLE | `ACTIVE` | ACTIVE, INACTIVE | DELETE_SEMANTICS_GAP | P1-GAP-014, P2-GAP-001 | **CERTIFIED_WITH_KNOWN_GAP** |

### Detailed State Transition Matrices

#### ENTITY-001 — Business State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `TRIAL` | `subscription_activated` | Valid payment verified | `ACTIVE` | SUPERADMIN / OWNER | api/app/Models/Business.php, api/app/Http/Controllers/Api/V1/BusinessController.php |
| `TRIAL` | `trial_ended` | Trial period elapsed | `EXPIRED` | SYSTEM | api/app/Models/Business.php, api/app/Http/Controllers/Api/V1/BusinessController.php |
| `ACTIVE` | `account_suspended` | Policy or payment dispute | `SUSPENDED` | SUPERADMIN | api/app/Models/Business.php, api/app/Http/Controllers/Api/V1/BusinessController.php |
| `ACTIVE` | `subscription_expired` | Subscription end date reached | `EXPIRED` | SYSTEM | api/app/Models/Business.php, api/app/Http/Controllers/Api/V1/BusinessController.php |
| `SUSPENDED` | `account_reinstated` | Dispute resolved | `ACTIVE` | SUPERADMIN | api/app/Models/Business.php, api/app/Http/Controllers/Api/V1/BusinessController.php |

#### ENTITY-002 — User State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `ACTIVE` | `deactivate_user` | Not sole active owner | `INACTIVE` | OWNER / ADMIN | api/app/Models/User.php, api/app/Http/Controllers/Api/V1/UserGovernanceController.php, api/app/Services/UserGovernanceService.php |
| `ACTIVE` | `failed_logins_exceeded` | Exceeded max auth failure threshold | `LOCKED` | SYSTEM | api/app/Models/User.php, api/app/Http/Controllers/Api/V1/UserGovernanceController.php, api/app/Services/UserGovernanceService.php |
| `INACTIVE` | `activate_user` | Reactivation approved | `ACTIVE` | OWNER / ADMIN | api/app/Models/User.php, api/app/Http/Controllers/Api/V1/UserGovernanceController.php, api/app/Services/UserGovernanceService.php |
| `LOCKED` | `unlock_user` | Admin unlock or password reset | `ACTIVE` | OWNER / ADMIN | api/app/Models/User.php, api/app/Http/Controllers/Api/V1/UserGovernanceController.php, api/app/Services/UserGovernanceService.php |

#### ENTITY-004 — Branch State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `ACTIVE` | `deactivate_branch` | Branch operations suspended | `INACTIVE` | OWNER / ADMIN | api/app/Models/Branch.php, api/app/Http/Controllers/Api/V1/BranchController.php |
| `ACTIVE` | `close_branch` | Zero stock on hand, cash settled | `CLOSED` | OWNER | api/app/Models/Branch.php, api/app/Http/Controllers/Api/V1/BranchController.php |
| `INACTIVE` | `activate_branch` | Branch reopened | `ACTIVE` | OWNER / ADMIN | api/app/Models/Branch.php, api/app/Http/Controllers/Api/V1/BranchController.php |
| `INACTIVE` | `close_branch` | Zero stock on hand, cash settled | `CLOSED` | OWNER | api/app/Models/Branch.php, api/app/Http/Controllers/Api/V1/BranchController.php |

#### ENTITY-006 — PersonalAccessToken State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `ACTIVE` | `token_expiration / logout` | Expiration timestamp reached or user logout | `EXPIRED` | SYSTEM / USER | api/app/Http/Controllers/Api/V1/AuthController.php |

#### ENTITY-007 — PasswordResetToken State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `PENDING` | `password_reset_completed` | Token matches and not expired | `CONSUMED` | USER | api/database/migrations/2014_10_12_100000_create_password_reset_tokens_table.php |
| `PENDING` | `ttl_elapsed` | 60 min elapsed | `EXPIRED` | SYSTEM | api/database/migrations/2014_10_12_100000_create_password_reset_tokens_table.php |

#### ENTITY-008 — Session State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `ACTIVE` | `session_timeout / logout` | Timeout or explicit destroy | `EXPIRED` | SYSTEM / USER | api/database/migrations/2026_09_02_000008_create_sessions_table.php |

#### ENTITY-014 — Category State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `ACTIVE` | `soft_delete_category` | No active products linked | `DELETED` | CATALOG_MANAGER | api/app/Models/Category.php, api/app/Http/Controllers/Api/V1/CategoryController.php |
| `DELETED` | `restore_category` | Parent business active | `ACTIVE` | CATALOG_MANAGER | api/app/Models/Category.php, api/app/Http/Controllers/Api/V1/CategoryController.php |

#### ENTITY-015 — Subcategory State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `ACTIVE` | `soft_delete_subcategory` | No active products linked | `DELETED` | CATALOG_MANAGER | api/app/Models/Subcategory.php, api/app/Http/Controllers/Api/V1/SubcategoryController.php |
| `DELETED` | `restore_subcategory` | Parent category active | `ACTIVE` | CATALOG_MANAGER | api/app/Models/Subcategory.php, api/app/Http/Controllers/Api/V1/SubcategoryController.php |

#### ENTITY-016 — Brand State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `ACTIVE` | `soft_delete_brand` | No active products linked | `DELETED` | CATALOG_MANAGER | api/app/Models/Brand.php, api/app/Http/Controllers/Api/V1/BrandController.php |
| `DELETED` | `restore_brand` | Parent business active | `ACTIVE` | CATALOG_MANAGER | api/app/Models/Brand.php, api/app/Http/Controllers/Api/V1/BrandController.php |

#### ENTITY-017 — Product State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `ACTIVE` | `deactivate_product` | Operator deactivation | `INACTIVE` | CATALOG_MANAGER | api/app/Models/Product.php, api/app/Http/Controllers/Api/V1/ProductController.php |
| `INACTIVE` | `activate_product` | Operator reactivation | `ACTIVE` | CATALOG_MANAGER | api/app/Models/Product.php, api/app/Http/Controllers/Api/V1/ProductController.php |
| `ACTIVE` | `soft_delete_product` | Zero branch stock or operator confirmation | `DELETED` | CATALOG_MANAGER | api/app/Models/Product.php, api/app/Http/Controllers/Api/V1/ProductController.php |
| `INACTIVE` | `soft_delete_product` | Zero branch stock or operator confirmation | `DELETED` | CATALOG_MANAGER | api/app/Models/Product.php, api/app/Http/Controllers/Api/V1/ProductController.php |
| `DELETED` | `restore_product` | Operator restore action | `ACTIVE` | CATALOG_MANAGER | api/app/Models/Product.php, api/app/Http/Controllers/Api/V1/ProductController.php |

#### ENTITY-019 — InventoryMovement State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `POSTED` | `reverse_movement` | Compensating movement posted | `REVERSED` | STOCK_MANAGER / SYSTEM | api/app/Models/InventoryMovement.php, api/app/Services/InventoryMovementService.php, api/app/Services/InventoryService.php |

#### ENTITY-020 — Sale State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `PAID` | `cancel_sale` | No settled returns | `CANCELLED` | MANAGER / CASHIER | api/app/Models/Sale.php, api/app/Services/SaleService.php, api/app/Http/Controllers/Api/V1/SaleController.php |
| `PAID` | `process_return` | Return items processed | `RETURNED` | CASHIER | api/app/Models/Sale.php, api/app/Services/SaleService.php, api/app/Http/Controllers/Api/V1/SaleController.php |
| `PARTIAL` | `settle_customer_payment` | Payment covers remaining due | `PAID` | CASHIER | api/app/Models/Sale.php, api/app/Services/SaleService.php, api/app/Http/Controllers/Api/V1/SaleController.php |
| `PARTIAL` | `cancel_sale` | No returns exist | `CANCELLED` | MANAGER | api/app/Models/Sale.php, api/app/Services/SaleService.php, api/app/Http/Controllers/Api/V1/SaleController.php |
| `DUE` | `settle_customer_payment` | Payment covers full total | `PAID` | CASHIER | api/app/Models/Sale.php, api/app/Services/SaleService.php, api/app/Http/Controllers/Api/V1/SaleController.php |
| `DUE` | `cancel_sale` | No returns exist | `CANCELLED` | MANAGER | api/app/Models/Sale.php, api/app/Services/SaleService.php, api/app/Http/Controllers/Api/V1/SaleController.php |

#### ENTITY-022 — SaleReturn State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `PENDING` | `approve_return` | Return items inspected | `APPROVED` | STORE_MANAGER | api/app/Models/SaleReturn.php, api/app/Http/Controllers/Api/V1/SaleReturnRefundController.php, api/app/Services/SaleService.php |
| `PENDING` | `reject_return` | Item condition invalid | `REJECTED` | STORE_MANAGER | api/app/Models/SaleReturn.php, api/app/Http/Controllers/Api/V1/SaleReturnRefundController.php, api/app/Services/SaleService.php |
| `APPROVED` | `settle_refund` | Refund disbursed via cash/bank or customer credit | `SETTLED` | CASHIER / MANAGER | api/app/Models/SaleReturn.php, api/app/Http/Controllers/Api/V1/SaleReturnRefundController.php, api/app/Services/SaleService.php |

#### ENTITY-024 — Customer State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `ACTIVE` | `deactivate_customer` | Operator deactivation | `INACTIVE` | OPERATOR | api/app/Models/Customer.php, api/app/Services/CustomerAccountService.php, api/app/Http/Controllers/Api/V1/CustomerController.php |
| `ACTIVE` | `block_credit` | Credit limit exceeded or bad debt | `BLOCKED` | MANAGER | api/app/Models/Customer.php, api/app/Services/CustomerAccountService.php, api/app/Http/Controllers/Api/V1/CustomerController.php |
| `INACTIVE` | `activate_customer` | Operator reactivation | `ACTIVE` | OPERATOR | api/app/Models/Customer.php, api/app/Services/CustomerAccountService.php, api/app/Http/Controllers/Api/V1/CustomerController.php |
| `BLOCKED` | `unblock_customer` | Khata dues settled | `ACTIVE` | MANAGER | api/app/Models/Customer.php, api/app/Services/CustomerAccountService.php, api/app/Http/Controllers/Api/V1/CustomerController.php |
| `ACTIVE` | `soft_delete_customer` | Zero outstanding balance or manager confirmation | `DELETED` | MANAGER | api/app/Models/Customer.php, api/app/Services/CustomerAccountService.php, api/app/Http/Controllers/Api/V1/CustomerController.php |
| `DELETED` | `restore_customer` | Operator restoration | `ACTIVE` | MANAGER | api/app/Models/Customer.php, api/app/Services/CustomerAccountService.php, api/app/Http/Controllers/Api/V1/CustomerController.php |

#### ENTITY-025 — KhataTransaction State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `POSTED` | `reverse_khata_transaction` | Sale cancelled or payment reversed | `REVERSED` | MANAGER / SYSTEM | api/app/Models/KhataTransaction.php, api/app/Http/Controllers/Api/V1/KhataController.php, api/app/Services/CustomerAccountService.php |

#### ENTITY-026 — CustomerPayment State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `POSTED` | `reverse_payment` | Payment not previously reversed | `REVERSED` | STORE_MANAGER / OWNER | api/app/Models/CustomerPayment.php, api/app/Services/CustomerPaymentService.php, api/app/Http/Controllers/Api/V1/CustomerPaymentController.php |

#### ENTITY-027 — Supplier State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `ACTIVE` | `deactivate_supplier` | Operator deactivation | `INACTIVE` | PROCUREMENT_MANAGER | api/app/Models/Supplier.php, api/app/Http/Controllers/Api/V1/SupplierController.php, api/app/Services/SupplierBalanceService.php |
| `INACTIVE` | `activate_supplier` | Operator reactivation | `ACTIVE` | PROCUREMENT_MANAGER | api/app/Models/Supplier.php, api/app/Http/Controllers/Api/V1/SupplierController.php, api/app/Services/SupplierBalanceService.php |
| `ACTIVE` | `soft_delete_supplier` | Zero balance or manager confirmation | `DELETED` | PROCUREMENT_MANAGER | api/app/Models/Supplier.php, api/app/Http/Controllers/Api/V1/SupplierController.php, api/app/Services/SupplierBalanceService.php |
| `DELETED` | `restore_supplier` | Operator restoration | `ACTIVE` | PROCUREMENT_MANAGER | api/app/Models/Supplier.php, api/app/Http/Controllers/Api/V1/SupplierController.php, api/app/Services/SupplierBalanceService.php |

#### ENTITY-028 — Purchase State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `ORDERED` | `receive_purchase_goods` | GRN verified, stock inspected | `RECEIVED` | STOCK_KEEPER / MANAGER | api/app/Models/Purchase.php, api/app/Services/PurchaseService.php, api/app/Http/Controllers/Api/V1/PurchaseController.php |
| `ORDERED` | `cancel_purchase` | Goods not received | `CANCELLED` | PURCHASE_MANAGER | api/app/Models/Purchase.php, api/app/Services/PurchaseService.php, api/app/Http/Controllers/Api/V1/PurchaseController.php |
| `RECEIVED` | `cancel_received_purchase` | Stock on hand sufficient to revert, supplier AP balance reverted | `CANCELLED` | OWNER / MANAGER | api/app/Models/Purchase.php, api/app/Services/PurchaseService.php, api/app/Http/Controllers/Api/V1/PurchaseController.php |

#### ENTITY-030 — SupplierPayment State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `POSTED` | `reverse_supplier_payment` | Payment reversal authorized | `REVERSED` | OWNER / ACCOUNTANT | api/app/Models/SupplierPayment.php, api/app/Http/Controllers/Api/V1/SupplierPaymentController.php, api/app/Services/SupplierBalanceService.php |

#### ENTITY-031 — Expense State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `ACTIVE` | `void_expense` | Expense not voided; void reason provided | `VOIDED` | MANAGER / OWNER | api/app/Models/Expense.php, api/app/Services/ExpenseService.php, api/app/Http/Controllers/Api/V1/ExpenseController.php |
| `ACTIVE` | `soft_delete_expense` | Operator delete action | `DELETED` | MANAGER | api/app/Models/Expense.php, api/app/Services/ExpenseService.php, api/app/Http/Controllers/Api/V1/ExpenseController.php |
| `DELETED` | `restore_expense` | Operator restore action | `ACTIVE` | MANAGER | api/app/Models/Expense.php, api/app/Services/ExpenseService.php, api/app/Http/Controllers/Api/V1/ExpenseController.php |

#### ENTITY-033 — RecurringExpense State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `ACTIVE` | `pause_schedule` | Operator action | `PAUSED` | MANAGER | api/app/Models/RecurringExpense.php, api/app/Http/Controllers/Api/V1/RecurringExpenseController.php |
| `PAUSED` | `resume_schedule` | Operator action | `ACTIVE` | MANAGER | api/app/Models/RecurringExpense.php, api/app/Http/Controllers/Api/V1/RecurringExpenseController.php |
| `ACTIVE` | `end_date_reached` | Final recurrence executed | `COMPLETED` | SYSTEM | api/app/Models/RecurringExpense.php, api/app/Http/Controllers/Api/V1/RecurringExpenseController.php |
| `ACTIVE` | `cancel_recurring` | Operator cancellation | `CANCELLED` | MANAGER | api/app/Models/RecurringExpense.php, api/app/Http/Controllers/Api/V1/RecurringExpenseController.php |

#### ENTITY-034 — FinancialAccount State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `ACTIVE` | `deactivate_account` | Account not default | `INACTIVE` | ACCOUNTANT / OWNER | api/app/Models/FinancialAccount.php, api/app/Services/FinancialAccountService.php, api/app/Http/Controllers/Api/V1/FinancialAccountController.php |
| `ACTIVE` | `freeze_account` | Dispute or audit investigation | `FROZEN` | OWNER | api/app/Models/FinancialAccount.php, api/app/Services/FinancialAccountService.php, api/app/Http/Controllers/Api/V1/FinancialAccountController.php |
| `INACTIVE` | `activate_account` | Operator reactivation | `ACTIVE` | ACCOUNTANT / OWNER | api/app/Models/FinancialAccount.php, api/app/Services/FinancialAccountService.php, api/app/Http/Controllers/Api/V1/FinancialAccountController.php |
| `FROZEN` | `unfreeze_account` | Investigation resolved | `ACTIVE` | OWNER | api/app/Models/FinancialAccount.php, api/app/Services/FinancialAccountService.php, api/app/Http/Controllers/Api/V1/FinancialAccountController.php |
| `ACTIVE` | `soft_delete_account` | Zero balance and not default | `DELETED` | OWNER | api/app/Models/FinancialAccount.php, api/app/Services/FinancialAccountService.php, api/app/Http/Controllers/Api/V1/FinancialAccountController.php |
| `DELETED` | `restore_account` | Operator restoration | `ACTIVE` | OWNER | api/app/Models/FinancialAccount.php, api/app/Services/FinancialAccountService.php, api/app/Http/Controllers/Api/V1/FinancialAccountController.php |

#### ENTITY-035 — AccountMovement State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `POSTED` | `void_movement` | Originating transaction voided | `VOIDED` | SYSTEM / MANAGER | api/app/Models/AccountMovement.php, api/app/Services/AccountMovementService.php |
| `POSTED` | `reverse_movement` | Compensating movement posted | `REVERSED` | SYSTEM / ACCOUNTANT | api/app/Models/AccountMovement.php, api/app/Services/AccountMovementService.php |

#### ENTITY-036 — AccountTransfer State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `COMPLETED` | `reverse_transfer` | Destination account has sufficient funds | `REVERSED` | OWNER / ACCOUNTANT | api/app/Models/AccountTransfer.php, api/app/Http/Controllers/Api/V1/AccountTransferController.php, api/app/Services/AccountMovementService.php |

#### ENTITY-039 — Notification State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `UNREAD` | `markAsRead` | Recipient reads notification | `READ` | USER | api/app/Http/Controllers/Api/V1/NotificationController.php |
| `READ` | `destroyNotification` | User dismisses notification | `DELETED` | USER | api/app/Http/Controllers/Api/V1/NotificationController.php |
| `UNREAD` | `destroyNotification` | User dismisses notification | `DELETED` | USER | api/app/Http/Controllers/Api/V1/NotificationController.php |

#### ENTITY-041 — Job State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `PENDING` | `worker_pop` | Worker claims queue item | `RESERVED` | SYSTEM_WORKER | api/database/migrations/2026_09_02_000041_create_jobs_table.php |
| `RESERVED` | `job_succeeded` | Execution completes without exception | `PROCESSED` | SYSTEM_WORKER | api/database/migrations/2026_09_02_000041_create_jobs_table.php |
| `RESERVED` | `job_exception_max_attempts` | Exception thrown and attempts exceeded | `FAILED` | SYSTEM_WORKER | api/database/migrations/2026_09_02_000041_create_jobs_table.php |

#### ENTITY-042 — JobBatch State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `PENDING` | `all_jobs_completed` | pending_jobs == 0 | `FINISHED` | SYSTEM_WORKER | api/database/migrations/2026_09_02_000042_create_job_batches_table.php |
| `PENDING` | `cancel_batch` | Batch cancelled | `CANCELLED` | SYSTEM / ADMIN | api/database/migrations/2026_09_02_000042_create_job_batches_table.php |

#### ENTITY-043 — FailedJob State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `RECORDED` | `artisan_queue_retry` | Admin initiates retry | `RETRIED` | DEVOPS_ENGINEER | api/database/migrations/2026_09_02_000043_create_failed_jobs_table.php |
| `RECORDED` | `artisan_queue_forget` | Admin dismisses failure | `DISMISSED` | DEVOPS_ENGINEER | api/database/migrations/2026_09_02_000043_create_failed_jobs_table.php |

#### ENTITY-044 — CacheEntry State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `VALID` | `ttl_elapsed / cache_flush` | Expiration reached | `EXPIRED` | SYSTEM / OPERATOR | api/database/migrations/2026_09_02_000044_create_cache_table.php |

#### ENTITY-045 — CacheLock State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `LOCKED` | `lock_release` | Process completes atomic section | `RELEASED` | APPLICATION_MUTEX | api/database/migrations/2026_09_02_000045_create_cache_locks_table.php |
| `LOCKED` | `ttl_elapsed` | Lock holder crashed / timeout | `EXPIRED` | SYSTEM | api/database/migrations/2026_09_02_000045_create_cache_locks_table.php |

#### ENTITY-046 — Employee State Machine

| From State | Trigger | Preconditions | To State | Actor | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `ACTIVE` | `deactivate_employee` | Operator deactivation | `INACTIVE` | HR_MANAGER | api/app/Models/Employee.php, api/database/migrations/2026_09_04_102948_create_employees_table.php |
| `INACTIVE` | `activate_employee` | Operator reactivation | `ACTIVE` | HR_MANAGER | api/app/Models/Employee.php, api/database/migrations/2026_09_04_102948_create_employees_table.php |

## 3. CRUD & Mutation Semantics (Checkpoint 2.2)

| Mutation ID | Operation Name | Primary Entity | Route & Controller | Semantic Classification | Transaction Boundary | Reversal Operation | Gaps | Status |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `MUTATION-001` | **Register Business & Owner User** | `ENTITY-001` | `api/v1/auth/register` (`AuthController@register`) | CREATE_ENTITY | ATOMIC_TRANSACTION | N/A | P1-GAP-001, P1-GAP-008, P2-GAP-002 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-002` | **User Login / Issue Token** | `ENTITY-006` | `api/v1/auth/login` (`AuthController@login`) | SYSTEM_MUTATION | ATOMIC_TRANSACTION | MUTATION-003 | None | **CERTIFIED_COMPLETE** |
| `MUTATION-003` | **User Logout / Revoke Token** | `ENTITY-006` | `api/v1/auth/logout` (`AuthController@logout`) | HARD_DELETE | ATOMIC_TRANSACTION | MUTATION-002 | None | **CERTIFIED_COMPLETE** |
| `MUTATION-004` | **Create Staff User & Assign Roles** | `ENTITY-002` | `api/v1/users` (`UserGovernanceController@store`) | CREATE_ENTITY | ATOMIC_TRANSACTION | MUTATION-006 | P1-GAP-001 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-005` | **Update Staff User Details & Roles** | `ENTITY-002` | `api/v1/users/{id}` (`UserGovernanceController@update`) | UPDATE_MASTER_DATA | ATOMIC_TRANSACTION | N/A | P1-GAP-001 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-006` | **Toggle User Active Status** | `ENTITY-002` | `api/v1/users/{id}/toggle-status` (`UserGovernanceController@toggleStatus`) | STATUS_TRANSITION | ATOMIC_TRANSACTION | MUTATION-006 | None | **CERTIFIED_COMPLETE** |
| `MUTATION-007` | **Update Self Profile** | `ENTITY-002` | `api/v1/profile` (`ProfileController@update`) | UPDATE_MASTER_DATA | ATOMIC_TRANSACTION | N/A | P1-GAP-001 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-008` | **Update Self Password** | `ENTITY-002` | `api/v1/profile/password` (`ProfileController@updatePassword`) | UPDATE_MASTER_DATA | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-009` | **Create Tenant Business** | `ENTITY-001` | `api/v1/businesses` (`BusinessController@store`) | CREATE_ENTITY | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-010` | **Update Business Details & Profile** | `ENTITY-001` | `api/v1/businesses/{id}` (`BusinessController@update`) | UPDATE_MASTER_DATA | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-011` | **Soft Delete Business Tenant** | `ENTITY-001` | `api/v1/businesses/{id}` (`BusinessController@destroy`) | SOFT_DELETE | ATOMIC_TRANSACTION | RESTORE_VIA_ELOQUENT | None | **CERTIFIED_COMPLETE** |
| `MUTATION-012` | **Create Physical Operating Branch** | `ENTITY-004` | `api/v1/branches` (`BranchController@store`) | CREATE_ENTITY | ATOMIC_TRANSACTION | MUTATION-014 | None | **CERTIFIED_COMPLETE** |
| `MUTATION-013` | **Update Branch Details** | `ENTITY-004` | `api/v1/branches/{id}` (`BranchController@update`) | UPDATE_MASTER_DATA | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-014` | **Soft Delete Branch** | `ENTITY-004` | `api/v1/branches/{id}` (`BranchController@destroy`) | SOFT_DELETE | ATOMIC_TRANSACTION | RESTORE_VIA_ELOQUENT | None | **CERTIFIED_COMPLETE** |
| `MUTATION-015` | **Create Product Category** | `ENTITY-014` | `api/v1/categories` (`CategoryController@store`) | CREATE_ENTITY | ATOMIC_TRANSACTION | MUTATION-017 | None | **CERTIFIED_COMPLETE** |
| `MUTATION-016` | **Update Product Category** | `ENTITY-014` | `api/v1/categories/{id}` (`CategoryController@update`) | UPDATE_MASTER_DATA | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-017` | **Soft Delete Category** | `ENTITY-014` | `api/v1/categories/{id}` (`CategoryController@destroy`) | SOFT_DELETE | ATOMIC_TRANSACTION | RESTORE_VIA_ELOQUENT | None | **CERTIFIED_COMPLETE** |
| `MUTATION-018` | **Create Product Subcategory** | `ENTITY-015` | `api/v1/subcategories` (`SubcategoryController@store`) | CREATE_ENTITY | ATOMIC_TRANSACTION | MUTATION-020 | None | **CERTIFIED_COMPLETE** |
| `MUTATION-019` | **Update Product Subcategory** | `ENTITY-015` | `api/v1/subcategories/{id}` (`SubcategoryController@update`) | UPDATE_MASTER_DATA | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-020` | **Soft Delete Subcategory** | `ENTITY-015` | `api/v1/subcategories/{id}` (`SubcategoryController@destroy`) | SOFT_DELETE | ATOMIC_TRANSACTION | RESTORE_VIA_ELOQUENT | None | **CERTIFIED_COMPLETE** |
| `MUTATION-021` | **Create Product Brand** | `ENTITY-016` | `api/v1/brands` (`BrandController@store`) | CREATE_ENTITY | ATOMIC_TRANSACTION | MUTATION-023 | None | **CERTIFIED_COMPLETE** |
| `MUTATION-022` | **Update Product Brand** | `ENTITY-016` | `api/v1/brands/{id}` (`BrandController@update`) | UPDATE_MASTER_DATA | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-023` | **Soft Delete Brand** | `ENTITY-016` | `api/v1/brands/{id}` (`BrandController@destroy`) | SOFT_DELETE | ATOMIC_TRANSACTION | RESTORE_VIA_ELOQUENT | None | **CERTIFIED_COMPLETE** |
| `MUTATION-024` | **Create Product & Initialize Branch Stock Rows** | `ENTITY-017` | `api/v1/products` (`ProductController@store`) | CREATE_ENTITY | ATOMIC_TRANSACTION | MUTATION-026 | P1-GAP-002, P1-GAP-009, P2-GAP-003 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-025` | **Update Product Master Data & Prices** | `ENTITY-017` | `api/v1/products/{id}` (`ProductController@update`) | UPDATE_MASTER_DATA | ATOMIC_TRANSACTION | N/A | P1-GAP-002, P1-GAP-009, P2-GAP-003 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-026` | **Soft Delete Product** | `ENTITY-017` | `api/v1/products/{id}` (`ProductController@destroy`) | SOFT_DELETE | ATOMIC_TRANSACTION | RESTORE_VIA_ELOQUENT | None | **CERTIFIED_COMPLETE** |
| `MUTATION-027` | **Adjust Branch Inventory Stock** | `ENTITY-018` | `api/v1/inventory/adjust` (`InventoryController@adjust`) | ADJUST_STOCK | ATOMIC_TRANSACTION | MUTATION-027_COMPENSATING | P1-GAP-003, P1-GAP-004, P2-GAP-004 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-028` | **Transfer Stock Between Branches** | `ENTITY-018` | `api/v1/inventory/transfer` (`InventoryController@transfer`) | TRANSFER_VALUE | ATOMIC_TRANSACTION | MUTATION-028_REVERSE | P1-GAP-013, P2-GAP-005 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-029` | **Process POS Sale Transaction** | `ENTITY-020` | `api/v1/sales` (`SaleController@store`) | POST_TRANSACTION | ATOMIC_TRANSACTION | MUTATION-031 / MUTATION-033 | P1-GAP-005, P1-GAP-011, P2-GAP-006 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-030` | **Update Sale Header / Notes** | `ENTITY-020` | `api/v1/sales/{id}` (`SaleController@update`) | UPDATE_MASTER_DATA | ATOMIC_TRANSACTION | N/A | P2-GAP-007 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-031` | **Cancel Sale & Restore Inventory** | `ENTITY-020` | `api/v1/sales/{id}/cancel` (`SaleController@cancel`) | CANCEL_TRANSACTION | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-032` | **Soft Delete Sale Record** | `ENTITY-020` | `api/v1/sales/{id}` (`SaleController@destroy`) | SOFT_DELETE | ATOMIC_TRANSACTION | RESTORE_VIA_ELOQUENT | P2-GAP-008 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-033` | **Process Sale Return & Items** | `ENTITY-022` | `api/v1/sales/{id}/returns` (`SaleController@returns`) | POST_TRANSACTION | ATOMIC_TRANSACTION | MUTATION-035 | None | **CERTIFIED_COMPLETE** |
| `MUTATION-034` | **Settle Sale Return Refund** | `ENTITY-022` | `api/v1/sale-returns/{id}/settle-refund` (`SaleReturnRefundController@settleRefund`) | RECORD_REFUND | ATOMIC_TRANSACTION | MUTATION-035 | None | **CERTIFIED_COMPLETE** |
| `MUTATION-035` | **Reverse Settled Return Refund** | `ENTITY-022` | `api/v1/sale-returns/{id}/reverse-refund` (`SaleReturnRefundController@reverseRefund`) | REVERSE_TRANSACTION | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-036` | **Create Customer Profile** | `ENTITY-024` | `api/v1/customers` (`CustomerController@store`) | CREATE_ENTITY | ATOMIC_TRANSACTION | MUTATION-038 | P1-GAP-007, P1-GAP-015, P2-GAP-009 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-037` | **Update Customer Profile** | `ENTITY-024` | `api/v1/customers/{id}` (`CustomerController@update`) | UPDATE_MASTER_DATA | ATOMIC_TRANSACTION | N/A | P1-GAP-015 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-038` | **Soft Delete Customer** | `ENTITY-024` | `api/v1/customers/{id}` (`CustomerController@destroy`) | SOFT_DELETE | ATOMIC_TRANSACTION | RESTORE_VIA_ELOQUENT | None | **CERTIFIED_COMPLETE** |
| `MUTATION-039` | **Manual Khata Ledger Entry (Debit/Credit)** | `ENTITY-025` | `api/v1/khata` (`KhataController@store`) | POST_LEDGER_EVENT | ATOMIC_TRANSACTION | MUTATION-040 | P1-GAP-007, P2-GAP-010 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-040` | **Delete / Revert Khata Transaction** | `ENTITY-025` | `api/v1/khata/{id}` (`KhataController@destroy`) | HARD_DELETE | ATOMIC_TRANSACTION | N/A | P2-GAP-011 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-041` | **Record Customer Payment Receipt** | `ENTITY-026` | `api/v1/customer-payments` (`CustomerPaymentController@store`) | RECORD_PAYMENT | ATOMIC_TRANSACTION | MUTATION-042 | P1-GAP-007 | **CERTIFIED_COMPLETE** |
| `MUTATION-042` | **Reverse Customer Payment** | `ENTITY-026` | `api/v1/customer-payments/{id}/reverse` (`CustomerPaymentController@reverse`) | REVERSE_TRANSACTION | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-043` | **Create Supplier Profile** | `ENTITY-027` | `api/v1/suppliers` (`SupplierController@store`) | CREATE_ENTITY | ATOMIC_TRANSACTION | MUTATION-045 | P1-GAP-007, P2-GAP-012 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-044` | **Update Supplier Profile** | `ENTITY-027` | `api/v1/suppliers/{id}` (`SupplierController@update`) | UPDATE_MASTER_DATA | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-045` | **Soft Delete Supplier** | `ENTITY-027` | `api/v1/suppliers/{id}` (`SupplierController@destroy`) | SOFT_DELETE | ATOMIC_TRANSACTION | RESTORE_VIA_ELOQUENT | None | **CERTIFIED_COMPLETE** |
| `MUTATION-046` | **Create & Receive Purchase (GRN)** | `ENTITY-028` | `api/v1/purchases` (`PurchaseController@store`) | POST_TRANSACTION | ATOMIC_TRANSACTION | MUTATION-047 | P1-GAP-006, P2-GAP-013 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-047` | **Cancel Purchase & Revert Stock/AP** | `ENTITY-028` | `api/v1/purchases/{id}/cancel` (`PurchaseController@cancel`) | CANCEL_TRANSACTION | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-048` | **Soft Delete Purchase** | `ENTITY-028` | `api/v1/purchases/{id}` (`PurchaseController@destroy`) | SOFT_DELETE | ATOMIC_TRANSACTION | RESTORE_VIA_ELOQUENT | P2-GAP-014 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-049` | **Record Supplier Payment Disbursal** | `ENTITY-030` | `api/v1/supplier-payments` (`SupplierPaymentController@store`) | RECORD_PAYMENT | ATOMIC_TRANSACTION | N/A | P2-GAP-015 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-050` | **Create Expense Category** | `ENTITY-032` | `api/v1/expense-categories` (`ExpenseCategoryController@store`) | CREATE_ENTITY | ATOMIC_TRANSACTION | MUTATION-052 | P1-GAP-016 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-051` | **Update Expense Category** | `ENTITY-032` | `api/v1/expense-categories/{id}` (`ExpenseCategoryController@update`) | UPDATE_MASTER_DATA | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-052` | **Hard Delete Expense Category** | `ENTITY-032` | `api/v1/expense-categories/{id}` (`ExpenseCategoryController@destroy`) | HARD_DELETE | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-053` | **Create Recurring Expense Schedule** | `ENTITY-033` | `api/v1/recurring-expenses` (`RecurringExpenseController@store`) | CREATE_ENTITY | ATOMIC_TRANSACTION | MUTATION-055 | None | **CERTIFIED_COMPLETE** |
| `MUTATION-054` | **Update Recurring Expense Schedule** | `ENTITY-033` | `api/v1/recurring-expenses/{id}` (`RecurringExpenseController@update`) | UPDATE_MASTER_DATA | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-055` | **Hard Delete Recurring Expense Schedule** | `ENTITY-033` | `api/v1/recurring-expenses/{id}` (`RecurringExpenseController@destroy`) | HARD_DELETE | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-056` | **Create & Disburse Operating Expense** | `ENTITY-031` | `api/v1/expenses` (`ExpenseController@store`) | POST_TRANSACTION | ATOMIC_TRANSACTION | MUTATION-059 | P1-GAP-012, P2-GAP-016 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-057` | **Update Expense Metadata** | `ENTITY-031` | `api/v1/expenses/{id}` (`ExpenseController@update`) | UPDATE_MASTER_DATA | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-058` | **Soft Delete Expense** | `ENTITY-031` | `api/v1/expenses/{id}` (`ExpenseController@destroy`) | SOFT_DELETE | ATOMIC_TRANSACTION | RESTORE_VIA_ELOQUENT | P2-GAP-017 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-059` | **Void Expense & Revert Financial Account** | `ENTITY-031` | `api/v1/expenses/{id}/void` (`ExpenseController@void`) | VOID_TRANSACTION | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-060` | **Create Financial Account** | `ENTITY-034` | `api/v1/financial-accounts` (`FinancialAccountController@store`) | CREATE_ENTITY | ATOMIC_TRANSACTION | MUTATION-062 | P1-GAP-008 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-061` | **Update Financial Account Details** | `ENTITY-034` | `api/v1/financial-accounts/{id}` (`FinancialAccountController@update`) | UPDATE_MASTER_DATA | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-062` | **Soft Delete Financial Account** | `ENTITY-034` | `api/v1/financial-accounts/{id}` (`FinancialAccountController@destroy`) | SOFT_DELETE | ATOMIC_TRANSACTION | RESTORE_VIA_ELOQUENT | None | **CERTIFIED_COMPLETE** |
| `MUTATION-063` | **Deposit Owner Capital Inflow** | `ENTITY-034` | `api/v1/financial-accounts/{id}/capital-in` (`FinancialAccountController@capitalIn`) | POST_LEDGER_EVENT | ATOMIC_TRANSACTION | MUTATION-064 | None | **CERTIFIED_COMPLETE** |
| `MUTATION-064` | **Withdraw Owner Capital Drawing** | `ENTITY-034` | `api/v1/financial-accounts/{id}/capital-out` (`FinancialAccountController@capitalOut`) | POST_LEDGER_EVENT | ATOMIC_TRANSACTION | MUTATION-063 | None | **CERTIFIED_COMPLETE** |
| `MUTATION-065` | **Execute Inter-Account Fund Transfer** | `ENTITY-036` | `api/v1/account-transfers` (`AccountTransferController@store`) | TRANSFER_VALUE | ATOMIC_TRANSACTION | MUTATION-065_COMPENSATING | P1-GAP-013, P2-GAP-018 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-066` | **Save/Upsert System & Business Settings** | `ENTITY-038` | `api/v1/settings` (`SettingController@store`) | CONFIGURATION_CHANGE | ATOMIC_TRANSACTION | N/A | P1-GAP-010, P2-GAP-019 | **CERTIFIED_WITH_KNOWN_GAP** |
| `MUTATION-067` | **Delete Custom Setting Key** | `ENTITY-038` | `api/v1/settings/{key}` (`SettingController@destroy`) | HARD_DELETE | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-068` | **Mark Single Notification as Read** | `ENTITY-039` | `api/v1/notifications/{id}/read` (`NotificationController@markAsRead`) | STATUS_TRANSITION | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-069` | **Mark All Notifications as Read** | `ENTITY-039` | `api/v1/notifications/read-all` (`NotificationController@markAllAsRead`) | STATUS_TRANSITION | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-070` | **Delete Notification** | `ENTITY-039` | `api/v1/notifications/{id}` (`NotificationController@destroy`) | HARD_DELETE | ATOMIC_TRANSACTION | N/A | None | **CERTIFIED_COMPLETE** |
| `MUTATION-071` | **Offline POS Bulk Transaction Ingest** | `ENTITY-020` | `api/v1/sync/bulk-ingest` (`SyncController@bulkIngest`) | IMPORT_SYNC | ATOMIC_TRANSACTION | INDIVIDUAL_TRANSACTION_REVERSALS | P1-GAP-005, P1-GAP-010, P2-GAP-020 | **CERTIFIED_WITH_KNOWN_GAP** |

## 4. Referential & Side-Effect Integrity (Checkpoint 2.3)

### Relationship Registry

| Rel ID | Parent | Child | Source Column -> Target Column | DB FK? | Model Relation | On Delete | Classification | Gaps |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `REL-001` | `ENTITY-001` | `ENTITY-004` | `branches.business_id` -> `businesses.id` | YES | Business::hasMany(Branch), Branch::belongsTo(Business) | RESTRICT | DB_AND_APPLICATION_ENFORCED | None |
| `REL-002` | `ENTITY-001` | `ENTITY-002` | `users.business_id` -> `businesses.id` | YES | Business::hasMany(User), User::belongsTo(Business) | RESTRICT | DB_AND_APPLICATION_ENFORCED | None |
| `REL-003` | `ENTITY-004` | `ENTITY-002` | `users.branch_id` -> `branches.id` | YES | Branch::hasMany(User), User::belongsTo(Branch) | SET NULL | INTENTIONALLY_NULLABLE | None |
| `REL-004` | `ENTITY-001` | `ENTITY-014` | `categories.business_id` -> `businesses.id` | YES | Business::hasMany(Category), Category::belongsTo(Business) | CASCADE | DB_AND_APPLICATION_ENFORCED | None |
| `REL-005` | `ENTITY-014` | `ENTITY-015` | `subcategories.category_id` -> `categories.id` | YES | Category::hasMany(Subcategory), Subcategory::belongsTo(Category) | CASCADE | DB_AND_APPLICATION_ENFORCED | None |
| `REL-006` | `ENTITY-001` | `ENTITY-016` | `brands.business_id` -> `businesses.id` | YES | Business::hasMany(Brand), Brand::belongsTo(Business) | CASCADE | DB_AND_APPLICATION_ENFORCED | None |
| `REL-007` | `ENTITY-001` | `ENTITY-017` | `products.business_id` -> `businesses.id` | YES | Business::hasMany(Product), Product::belongsTo(Business) | CASCADE | DB_AND_APPLICATION_ENFORCED | None |
| `REL-008` | `ENTITY-014` | `ENTITY-017` | `products.category_id` -> `categories.id` | YES | Category::hasMany(Product), Product::belongsTo(Category) | RESTRICT | DB_AND_APPLICATION_ENFORCED | None |
| `REL-009` | `ENTITY-015` | `ENTITY-017` | `products.subcategory_id` -> `subcategories.id` | YES | Subcategory::hasMany(Product), Product::belongsTo(Subcategory) | SET NULL | INTENTIONALLY_NULLABLE | None |
| `REL-010` | `ENTITY-016` | `ENTITY-017` | `products.brand_id` -> `brands.id` | YES | Brand::hasMany(Product), Product::belongsTo(Brand) | SET NULL | INTENTIONALLY_NULLABLE | None |
| `REL-011` | `ENTITY-017` | `ENTITY-018` | `branch_inventories.product_id` -> `products.id` | YES | Product::hasMany(BranchInventory), BranchInventory::belongsTo(Product) | CASCADE | DB_AND_APPLICATION_ENFORCED | None |
| `REL-012` | `ENTITY-004` | `ENTITY-018` | `branch_inventories.branch_id` -> `branches.id` | YES | Branch::hasMany(BranchInventory), BranchInventory::belongsTo(Branch) | CASCADE | DB_AND_APPLICATION_ENFORCED | None |
| `REL-013` | `ENTITY-017` | `ENTITY-019` | `inventory_movements.product_id` -> `products.id` | YES | Product::hasMany(InventoryMovement), InventoryMovement::belongsTo(Product) | RESTRICT | DB_AND_APPLICATION_ENFORCED | None |
| `REL-014` | `ENTITY-004` | `ENTITY-019` | `inventory_movements.branch_id` -> `branches.id` | YES | Branch::hasMany(InventoryMovement), InventoryMovement::belongsTo(Branch) | RESTRICT | DB_AND_APPLICATION_ENFORCED | None |
| `REL-015` | `ENTITY-001` | `ENTITY-020` | `sales.business_id` -> `businesses.id` | YES | Business::hasMany(Sale), Sale::belongsTo(Business) | RESTRICT | DB_AND_APPLICATION_ENFORCED | None |
| `REL-016` | `ENTITY-004` | `ENTITY-020` | `sales.branch_id` -> `branches.id` | YES | Branch::hasMany(Sale), Sale::belongsTo(Branch) | RESTRICT | DB_AND_APPLICATION_ENFORCED | None |
| `REL-017` | `ENTITY-024` | `ENTITY-020` | `sales.customer_id` -> `customers.id` | YES | Customer::hasMany(Sale), Sale::belongsTo(Customer) | SET NULL | INTENTIONALLY_NULLABLE | None |
| `REL-018` | `ENTITY-002` | `ENTITY-020` | `sales.user_id` -> `users.id` | YES | User::hasMany(Sale), Sale::belongsTo(User) | RESTRICT | DB_AND_APPLICATION_ENFORCED | None |
| `REL-019` | `ENTITY-020` | `ENTITY-021` | `sale_items.sale_id` -> `sales.id` | YES | Sale::hasMany(SaleItem), SaleItem::belongsTo(Sale) | CASCADE | DB_AND_APPLICATION_ENFORCED | None |
| `REL-020` | `ENTITY-017` | `ENTITY-021` | `sale_items.product_id` -> `products.id` | YES | Product::hasMany(SaleItem), SaleItem::belongsTo(Product) | RESTRICT | DB_AND_APPLICATION_ENFORCED | None |
| `REL-021` | `ENTITY-020` | `ENTITY-022` | `sale_returns.sale_id` -> `sales.id` | YES | Sale::hasMany(SaleReturn), SaleReturn::belongsTo(Sale) | RESTRICT | DB_AND_APPLICATION_ENFORCED | None |
| `REL-022` | `ENTITY-022` | `ENTITY-023` | `sale_return_items.sale_return_id` -> `sale_returns.id` | YES | SaleReturn::hasMany(SaleReturnItem), SaleReturnItem::belongsTo(SaleReturn) | CASCADE | DB_AND_APPLICATION_ENFORCED | None |
| `REL-023` | `ENTITY-024` | `ENTITY-025` | `khata_transactions.customer_id` -> `customers.id` | YES | Customer::hasMany(KhataTransaction), KhataTransaction::belongsTo(Customer) | CASCADE | DB_AND_APPLICATION_ENFORCED | None |
| `REL-024` | `ENTITY-024` | `ENTITY-026` | `customer_payments.customer_id` -> `customers.id` | YES | Customer::hasMany(CustomerPayment), CustomerPayment::belongsTo(Customer) | RESTRICT | DB_AND_APPLICATION_ENFORCED | None |
| `REL-025` | `ENTITY-027` | `ENTITY-028` | `purchases.supplier_id` -> `suppliers.id` | YES | Supplier::hasMany(Purchase), Purchase::belongsTo(Supplier) | RESTRICT | DB_AND_APPLICATION_ENFORCED | None |
| `REL-026` | `ENTITY-028` | `ENTITY-029` | `purchase_items.purchase_id` -> `purchases.id` | YES | Purchase::hasMany(PurchaseItem), PurchaseItem::belongsTo(Purchase) | CASCADE | DB_AND_APPLICATION_ENFORCED | None |
| `REL-027` | `ENTITY-027` | `ENTITY-030` | `supplier_payments.supplier_id` -> `suppliers.id` | YES | Supplier::hasMany(SupplierPayment), SupplierPayment::belongsTo(Supplier) | RESTRICT | DB_AND_APPLICATION_ENFORCED | None |
| `REL-028` | `ENTITY-032` | `ENTITY-031` | `expenses.category_id` -> `expense_categories.id` | YES | ExpenseCategory::hasMany(Expense), Expense::belongsTo(ExpenseCategory) | SET NULL | INTENTIONALLY_NULLABLE | None |
| `REL-029` | `ENTITY-034` | `ENTITY-031` | `expenses.financial_account_id` -> `financial_accounts.id` | YES | FinancialAccount::hasMany(Expense), Expense::belongsTo(FinancialAccount) | SET NULL | REFERENTIAL_INTEGRITY_GAP | P1-GAP-012 |
| `REL-030` | `ENTITY-034` | `ENTITY-035` | `account_movements.account_id` -> `financial_accounts.id` | YES | FinancialAccount::hasMany(AccountMovement), AccountMovement::belongsTo(FinancialAccount) | RESTRICT | DB_AND_APPLICATION_ENFORCED | None |
| `REL-031` | `ENTITY-034` | `ENTITY-036` | `account_transfers.source_account_id` -> `financial_accounts.id` | YES | FinancialAccount::hasMany(AccountTransfer, source_account_id), AccountTransfer::belongsTo(FinancialAccount, source_account_id) | RESTRICT | DB_AND_APPLICATION_ENFORCED | None |
| `REL-032` | `ENTITY-034` | `ENTITY-036` | `account_transfers.destination_account_id` -> `financial_accounts.id` | YES | FinancialAccount::hasMany(AccountTransfer, destination_account_id), AccountTransfer::belongsTo(FinancialAccount, destination_account_id) | RESTRICT | DB_AND_APPLICATION_ENFORCED | None |

### Side-Effect Matrix Summary

| Mutation ID | Entity | Stock Effect | Inv. Ledger | Customer AR | Supplier AP | Financial Balance | Cash Drawer | Multi-Table | Rollback Safety |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `MUTATION-001` | `ENTITY-001` | None | None | None | None | Active | None | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-002` | `ENTITY-006` | None | None | None | None | None | None | YES | RETURNS_401 |
| `MUTATION-003` | `ENTITY-006` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-004` | `ENTITY-002` | None | None | None | None | None | None | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-005` | `ENTITY-002` | None | None | None | None | None | None | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-006` | `ENTITY-002` | None | None | None | None | None | None | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-007` | `ENTITY-002` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-008` | `ENTITY-002` | None | None | None | None | None | None | YES | N/A |
| `MUTATION-009` | `ENTITY-001` | None | None | None | None | None | None | NO | ROLLBACK_DB_TRANSACTION |
| `MUTATION-010` | `ENTITY-001` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-011` | `ENTITY-001` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-012` | `ENTITY-004` | Active | None | None | None | None | None | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-013` | `ENTITY-004` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-014` | `ENTITY-004` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-015` | `ENTITY-014` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-016` | `ENTITY-014` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-017` | `ENTITY-014` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-018` | `ENTITY-015` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-019` | `ENTITY-015` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-020` | `ENTITY-015` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-021` | `ENTITY-016` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-022` | `ENTITY-016` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-023` | `ENTITY-016` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-024` | `ENTITY-017` | Active | None | None | None | None | None | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-025` | `ENTITY-017` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-026` | `ENTITY-017` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-027` | `ENTITY-018` | Active | Active | None | None | None | None | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-028` | `ENTITY-018` | Active | Active | None | None | None | None | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-029` | `ENTITY-020` | Active | Active | Active | None | Active | Active | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-030` | `ENTITY-020` | None | None | Active | None | None | None | NO | N/A |
| `MUTATION-031` | `ENTITY-020` | Active | Active | Active | None | Active | Active | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-032` | `ENTITY-020` | Active | None | None | None | None | None | NO | N/A |
| `MUTATION-033` | `ENTITY-022` | Active | Active | Active | None | Active | Active | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-034` | `ENTITY-022` | Active | None | Active | None | Active | Active | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-035` | `ENTITY-022` | None | None | Active | None | Active | Active | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-036` | `ENTITY-024` | None | None | Active | None | None | None | NO | N/A |
| `MUTATION-037` | `ENTITY-024` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-038` | `ENTITY-024` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-039` | `ENTITY-025` | None | None | Active | None | Active | None | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-040` | `ENTITY-025` | None | None | Active | None | None | None | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-041` | `ENTITY-026` | None | None | Active | None | Active | Active | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-042` | `ENTITY-026` | None | None | Active | None | Active | Active | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-043` | `ENTITY-027` | None | None | None | Active | None | None | NO | N/A |
| `MUTATION-044` | `ENTITY-027` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-045` | `ENTITY-027` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-046` | `ENTITY-028` | Active | Active | None | Active | Active | Active | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-047` | `ENTITY-028` | Active | Active | None | Active | Active | Active | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-048` | `ENTITY-028` | Active | None | None | None | None | None | NO | N/A |
| `MUTATION-049` | `ENTITY-030` | None | None | None | Active | Active | Active | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-050` | `ENTITY-032` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-051` | `ENTITY-032` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-052` | `ENTITY-032` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-053` | `ENTITY-033` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-054` | `ENTITY-033` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-055` | `ENTITY-033` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-056` | `ENTITY-031` | None | None | None | None | Active | Active | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-057` | `ENTITY-031` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-058` | `ENTITY-031` | None | None | None | None | Active | None | NO | N/A |
| `MUTATION-059` | `ENTITY-031` | None | None | None | None | Active | Active | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-060` | `ENTITY-034` | None | None | None | None | Active | None | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-061` | `ENTITY-034` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-062` | `ENTITY-034` | None | None | None | None | None | None | NO | ROLLBACK_DB_TRANSACTION |
| `MUTATION-063` | `ENTITY-034` | None | None | None | None | Active | None | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-064` | `ENTITY-034` | None | None | None | None | Active | None | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-065` | `ENTITY-036` | None | None | None | None | Active | None | YES | ROLLBACK_DB_TRANSACTION |
| `MUTATION-066` | `ENTITY-038` | None | None | None | None | None | None | NO | ROLLBACK_DB_TRANSACTION |
| `MUTATION-067` | `ENTITY-038` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-068` | `ENTITY-039` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-069` | `ENTITY-039` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-070` | `ENTITY-039` | None | None | None | None | None | None | NO | N/A |
| `MUTATION-071` | `ENTITY-020` | Active | Active | Active | None | Active | Active | YES | BATCH_TRANSACTION_ROLLBACK |

## 5. Phase 2 Gap Register (Checkpoint 2.4 / Findings)

| Gap ID | Entity | Mutation | Category | Current Behavior & Impact | Related P1 Gaps | Remediation Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `P2-GAP-001` | `ENTITY-046` | `N/A` | `LIFECYCLE_UNDEFINED` | **Employee model exists in api/app/Models/Employee.php but migration was not executed into physical database schema, leaving payroll lifecycle completely unpersisted.** <br> *Impact:* Staff payroll attributes (salary, cycle, next payment date) cannot be stored or automated. | P1-GAP-014 | **Phase 9** |
| `P2-GAP-002` | `ENTITY-001` | `MUTATION-001` | `SIDE_EFFECT_MISSING` | **Tenant registration creates default Cash account with 0 balance directly without posting an initial account movement ledger row.** <br> *Impact:* Minor; starting account begins at 0 without audit trail entry. | P1-GAP-008 | **Phase 9** |
| `P2-GAP-003` | `ENTITY-017` | `MUTATION-024` | `SIDE_EFFECT_MISSING` | **Product creation creates BranchInventory records across branches with 0 stock, but wholesale_price is not utilized in pricing tiers and margins are not live-computed in all endpoints.** <br> *Impact:* Wholesale discounts cannot be automatically applied in POS for wholesale customers. | P1-GAP-002, P1-GAP-009 | **Phase 9** |
| `P2-GAP-004` | `ENTITY-018` | `MUTATION-027` | `BUSINESS_EVENT_AS_GENERIC_UPDATE` | **Manual stock adjustments allow specifying a custom unit_cost without updating the product or branch moving-average cost formula.** <br> *Impact:* COGS and asset valuation may become distorted when stock is adjusted manually at non-standard costs. | P1-GAP-003, P1-GAP-004 | **Phase 9** |
| `P2-GAP-005` | `ENTITY-018` | `MUTATION-028` | `LIFECYCLE_UNDEFINED` | **Inter-branch stock transfer executes instantaneous debit/credit without an in-transit status and without financial clearing entries.** <br> *Impact:* Branch manager at destination receives stock instantly before physical delivery arrives. | P1-GAP-013 | **Phase 9** |
| `P2-GAP-006` | `ENTITY-020` | `MUTATION-029` | `PARTIAL_SIDE_EFFECT` | **POS sale transaction stores basic payment_method but does not persist card authorization codes or gateway transaction IDs on multi-tender split payments.** <br> *Impact:* Bank reconciliation for card payments requires manual paper receipt matching. | P1-GAP-005 | **Phase 9** |
| `P2-GAP-007` | `ENTITY-020` | `MUTATION-030` | `EDIT_AFTER_POSTING` | **PUT /sales/{id} allows mutating customer_id on posted sales without transferring historical Khata receivables or regenerating ledger entries.** <br> *Impact:* Modifying a sale customer creates a discrepancy where original customer Khata balance remains debited but sale invoice displays new customer. | P1-GAP-007 | **Phase 9** |
| `P2-GAP-008` | `ENTITY-020` | `MUTATION-032` | `DELETE_AFTER_POSTING` | **Soft deleting a Sale record does not automatically trigger stock restoration or financial reversals; requires separate cancelSale call.** <br> *Impact:* Operator might soft-delete a sale thinking it reverses stock and cash, leaving orphaned movements. | None | **Phase 9** |
| `P2-GAP-009` | `ENTITY-024` | `MUTATION-036` | `SIDE_EFFECT_MISSING` | **Creating a customer with an opening_balance sets customers.balance directly without inserting an opening KhataTransaction ledger event.** <br> *Impact:* Customer account statement (ledger) starts with missing opening row. | P1-GAP-007 | **Phase 9** |
| `P2-GAP-010` | `ENTITY-025` | `MUTATION-039` | `BUSINESS_EVENT_AS_GENERIC_UPDATE` | **Manual Khata entries allow inserting arbitrary debit/credit adjustments without requiring a corresponding invoice, receipt voucher, or debit note.** <br> *Impact:* Cashiers could manually alter customer balances without verifiable source documents. | P1-GAP-007 | **Phase 9** |
| `P2-GAP-011` | `ENTITY-025` | `MUTATION-040` | `HARD_DELETE_HISTORY_LOSS` | **DELETE /khata/{id} physically deletes the KhataTransaction row from the database and recalculates balance.** <br> *Impact:* Audit history of customer transactions is erased upon deletion. | None | **Phase 9** |
| `P2-GAP-012` | `ENTITY-027` | `MUTATION-043` | `SIDE_EFFECT_MISSING` | **Creating a supplier with opening_balance sets suppliers.balance directly without generating an opening payable ledger record.** <br> *Impact:* Supplier statement history lacks explicit opening balance entry. | P1-GAP-007 | **Phase 9** |
| `P2-GAP-013` | `ENTITY-028` | `MUTATION-046` | `PARTIAL_SIDE_EFFECT` | **Purchase creation updates product stock but lacks shipping / freight landed cost allocation across line items.** <br> *Impact:* True product acquisition cost is understated when shipping fees are paid separately. | P1-GAP-006 | **Phase 9** |
| `P2-GAP-014` | `ENTITY-028` | `MUTATION-048` | `DELETE_AFTER_POSTING` | **Soft deleting a received purchase removes the header from queries without executing inventory stock decrements or AP liability reversals.** <br> *Impact:* Soft-deleting a purchase leaves supplier payable balance intact while deleting the invoice view. | None | **Phase 9** |
| `P2-GAP-015` | `ENTITY-030` | `MUTATION-049` | `REVERSAL_MISSING` | **SupplierPaymentController has store method but no dedicated reverse endpoint, unlike CustomerPaymentController.** <br> *Impact:* Reversing an erroneous supplier payment requires manual database intervention or complex offsetting entries. | None | **Phase 9** |
| `P2-GAP-016` | `ENTITY-031` | `MUTATION-056` | `REFERENTIAL_INTEGRITY` | **Expense creation permits financial_account_id to be NULL or unassigned, which skips financial account balance deduction.** <br> *Impact:* Expenses can be recorded in P&L without deducting cash or bank balances, causing asset/expense accounting drift. | P1-GAP-012 | **Phase 9** |
| `P2-GAP-017` | `ENTITY-031` | `MUTATION-058` | `DELETE_AFTER_POSTING` | **Soft deleting an active expense leaves the associated AccountMovement row active in financial_accounts balance without reverting funds.** <br> *Impact:* Deleting an expense without voiding leaves account balance reduced while expense report drops the row. | None | **Phase 9** |
| `P2-GAP-018` | `ENTITY-036` | `MUTATION-065` | `LIFECYCLE_UNDEFINED` | **Inter-account fund transfers execute instantaneous double-entry debit/credit without intermediate clearance or bank transit states.** <br> *Impact:* Inter-bank transfers that take 1-2 business days appear instantly available in destination account. | P1-GAP-013 | **Phase 9** |
| `P2-GAP-019` | `ENTITY-038` | `MUTATION-066` | `CONFIGURATION_CHANGE` | **Settings are stored as key-value pairs without revision history or IP/subnet pairing security validation.** <br> *Impact:* Accidental configuration changes (e.g. tax rates, currency) cannot be rolled back easily. | P1-GAP-010 | **Phase 9** |
| `P2-GAP-020` | `ENTITY-020` | `MUTATION-071` | `IMPORT_SYNC` | **Bulk sync ingest accepts client-provided timestamps without server-authoritative clock drift verification or sequence reconciliation.** <br> *Impact:* An offline POS terminal with wrong clock date could insert invoices out of chronological sequence in financial reports. | P1-GAP-005, P1-GAP-010 | **Phase 9** |

