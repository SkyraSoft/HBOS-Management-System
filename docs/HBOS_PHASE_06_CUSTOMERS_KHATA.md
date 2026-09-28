# HBOS Phase 6 — Customers & Khata / Udhaar Engineering Record

======================================================================
HBOS MASTER ROADMAP — FROZEN AT EXACTLY 10 PHASES
======================================================================

| Phase | Description | Status |
|---|---|---|
| **PHASE 1** | Multi-Tenancy, Branches, Users & RBAC | COMPLETE |
| **PHASE 2** | Product Catalog & Master Data | COMPLETE |
| **PHASE 3** | Inventory & Stock Ledger | COMPLETE |
| **PHASE 4** | Purchases & Suppliers | COMPLETE |
| **PHASE 5** | Sales & POS | COMPLETE |
| **PHASE 6** | **Customers & Khata / Udhaar** | **COMPLETE** |
| **PHASE 7** | Cash, Bank & Expenses | NOT STARTED |
| **PHASE 8** | Dashboard & Reporting | NOT STARTED |
| **PHASE 9** | Audit, Settings & System Governance | NOT STARTED |
| **PHASE 10** | End-to-End Hardening, QA & Release Readiness | NOT STARTED |

---

## Final Canonical Architecture & Status Summary

> [!IMPORTANT]
> **PHASE 6 STATUS: COMPLETE**
> - **Prompt 1/4 (Discovery / Preparation):** COMPLETE
> - **Prompt 2/4 (Core Implementation):** COMPLETE
> - **Prompt 3/4 (Integration / Security / Automated Testing):** COMPLETE
> - **Prompt 4/4 (Final Verification / Phase Completion):** COMPLETE
> - **Next Authorized Phase:** Phase 7 — Cash, Bank & Expenses (Prompt 1/4 Discovery)

### Canonical Architecture At A Glance
- **Current Canonical Balance Formula:**
  $$\text{raw\_account\_balance} = \text{opening\_balance} + \sum_{\text{All Eligible Sales}} \text{effective\_due} - \sum_{\text{Posted Payments}} \text{amount}$$
  $$\text{customer\_outstanding} = \max(\text{raw\_account\_balance}, 0)$$
  $$\text{customer\_credit} = \max(-\text{raw\_account\_balance}, 0)$$
- **Customer Identity:** Business-wide (`business_id` scoped).
- **`customers.balance`:** Signed Business-wide cached projection column (guarded from mass-assignment/client writes).
- **Manager Scope:** Dynamic assigned-Branch exposure ($\max(\sum_{\text{Branch Sales}} \text{effective\_due} - \sum_{\text{Branch Payments}}, 0)$).
- **Legacy Khata:** `khata_transactions` table is read-only historical detail (`GET /api/v1/khata`). Mutation endpoints (`POST`, `DELETE`) return `HTTP 410 Gone`. Legacy Khata events do not affect modern runtime `CustomerAccountService` formulas (absorbed into `opening_balance` during cutover migration).
- **Canonical Date Field:** `customer_payments.date` (explicit date, no `payment_date` alias).
- **Phase 7 Boundary:** Zero Cash/Bank ledger movements posted in Phase 6. `payment_method` is metadata only.

---

## 1. Phase 6 Objective & Boundaries

### Objective
Phase 6 establishes a reliable customer receivables and Khata/Udhaar system built on top of completed Sale and SaleReturn transaction history. It provides whole-business customer identity, branch-aware receivable tracking, customer opening balances, and append-only collection/payment event recording without treating `customers.balance` as a freely mutable column.

### Scope
- Business-level Customer master data (`business_id` scoped).
- Customer opening balance provenance (`customers.opening_balance`).
- Customer receivable tracking based on active `Sale` events (`total`, `paid_amount`, `due_amount`, `status`) and `SaleReturn` events (`refund_amount`).
- Append-only `CustomerPayment` collection events (`business_id`, `branch_id`, `customer_id`, `amount`, `payment_method`, `date`, `user_id`, `sale_id`, `notes`).
- Customer account ledger view / projection (chronological history of credit sales, returns, payments, opening balance).
- Mathematical reconciliation via `CustomerAccountService` where `customers.balance` is a cached projection derived strictly from authoritative events.
- Branch-aware transaction visibility and RBAC filtering (Owner sees whole-business balance, Manager sees assigned-branch exposure/activity, Salesperson has POS selection & minimal due lookup).

### Out of Scope (Explicit Boundaries)
- **Phase 7 Cash/Bank Posting:** Customer payments record receivable reduction in Phase 6; physical cash drawer / bank account ledger movement remains Phase 7.
- **Double-Entry Accounting:** Full general ledger, debits/credits journal entries, chart of accounts.
- **Broad Reporting & Aging Dashboards:** Complex A/R aging reports belong in Phase 8.
- **Automated Credit Engine:** Complex credit scoring, automated debt collection workflows.

---

## 2. Prompt 1 Discovery Summary & Codebase Audit

### Existing Customer Architecture
- **Model:** `App\Models\Customer`
- **Scoping:** Uses `Tenantable` trait (scoped by `business_id`). Identity is Business-wide (no `branch_id` on `customers` table).
- **Current Schema (`customers`):**
  - `id` (bigint unsigned)
  - `business_id` (bigint unsigned)
  - `name` (string)
  - `phone` (string, nullable)
  - `email` (string, nullable)
  - `address` (text, nullable)
  - `balance` (decimal 12,2, default 0.00)
  - `created_at`, `updated_at`, `deleted_at` (soft deletes)
- **Legacy Write Paths:**
  - `CustomerController@store` & `@update` accept raw `balance` payload from request input!
  - `CustomerController@destroy` executes `$customer->delete()`.
  - `KhataController@store` mutates `$customer->balance` directly using `$customer->increment('balance', $amount)` for type `give` and `$customer->decrement('balance', $amount)` for type `got`.
  - `KhataController@destroy` deletes `khata_transactions` record and reverses `$customer->balance` mutation via `increment`/`decrement`.
  - **Classification:** Current write paths are **DANGEROUS** and **LEGACY**. They allow unvalidated client-side balance setting and hard balance mutations without transaction provenance.

### Existing Khata Architecture
- **Model:** `App\Models\KhataTransaction`
- **Schema (`khata_transactions`):**
  - `id`, `business_id`, `customer_id`, `type` (`give` / `got`), `amount`, `date`, `notes`, `created_at`, `updated_at`.
- **Controller:** `App\Http\Controllers\Api\V1\KhataController` (index, store, destroy).
- **Limitations:** Lacks `branch_id`, `user_id`, `sale_id`, idempotency key, or reversal tracking. `destroy` performs destructive deletion.

### Existing Sale & SaleReturn Credit Integration (Phase 5 Baseline)
- Credit Sales in Phase 5 record posting snapshots: `total`, `paid_amount`, `due_amount`, `status`.
- Phase 5 explicitly requires a registered `customer_id` when `due_amount > 0`.
- Phase 5 explicitly **refrained** from mutating `customers.balance` during checkout, leaving Khata ledger integration to Phase 6.
- Cancelled Sales maintain original `due_amount` snapshot but change `status = 'cancelled'`. Cancelled Sales contribute 0 to active receivables.
- SaleReturns record `refund_amount` and decrease net Sale total.

---

## 3. Data Audit & Historical Reconciliation Strategy

### Development DB Audit Findings
- **Customers Count:** 17
- **Customers with `balance != 0`:** 10
- **Sales Count:** 25
- **Sales with `due_amount > 0`:** 0
- **Cancelled Sales:** 0
- **SaleReturns:** 0
- **KhataTransactions Count:** 13

### Migration Strategy for Legacy Data
1. Add `opening_balance` column to `customers` table (decimal 12,2, default 0.00).
2. Any pre-existing `customers.balance` in development data that cannot be accounted for by posted Phase 5 credit sales will be safely preserved as `opening_balance = balance`.
3. Legacy `khata_transactions` table will be replaced / refactored into the new append-only `customer_payments` / `customer_ledger_entries` architecture with complete forward migration, preserving historical amounts under opening balance or migrated payment events.

---

## 4. Phase 6 Architectural Decisions

### 1. Source-of-Truth Decision
- **Domain Events as Primary Truth:** Active `Sale` records (credit due), `SaleReturn` records (receivable reductions), `CustomerPayment` records (collection events), and `Customer.opening_balance` form the authoritative source of truth.
- **`customers.balance` as Cached Projection:** `customers.balance` is **NOT** freely editable truth. It is a cached column updated atomically during domain events and completely reconcilable via `CustomerAccountService::recalculateBalance($customer)`.

### 2. Mathematical Reconciliation Formula
$$\text{Customer Outstanding} = \text{opening\_balance} + \sum_{\text{Active Sales}} \text{effective\_due} - \sum \text{CustomerPayments}$$

Where:
- $\text{effective\_net\_sale} = \text{Sale.total} - \sum \text{SaleReturn.refund\_amount}$
- $\text{effective\_due} = \max(0, \text{effective\_net\_sale} - \text{Sale.paid\_amount})$ (for active non-cancelled Sales with registered `customer_id`).
- Cancelled Sales yield $\text{effective\_due} = 0$.
- `CustomerPayment` events represent collections posted *after* sale completion (so initial POS `paid_amount` is never double-counted).

### 3. CustomerPayment Event Model
- **New Table:** `customer_payments`
- **Fields:**
  - `id` (bigint unsigned)
  - `business_id` (bigint unsigned, foreign key)
  - `branch_id` (bigint unsigned, foreign key)
  - `customer_id` (bigint unsigned, foreign key)
  - `sale_id` (bigint unsigned, nullable, foreign key)
  - `user_id` (bigint unsigned, foreign key)
  - `payment_number` (string, tenant-unique, e.g. `CP-20260915-XXXX`)
  - `amount` (decimal 12,2)
  - `payment_method` (string: `cash`, `bank_transfer`, `card`, `cheque`, `other`)
  - `date` (date)
  - `notes` (text, nullable)
  - `idempotency_key` (string, nullable)
  - `status` (enum: `posted`, `voided`)
  - `reversed_at`, `reversed_by`, `reversal_reason`
  - `created_at`, `updated_at`

### 4. Overpayment & Credit Policy
- For Phase 6 MVP, payments exceeding total customer outstanding receivable are **rejected** with HTTP 422 ("Payment amount exceeds customer outstanding balance").
- Sign Convention: Positive balance = Customer owes Business. `0` = Fully settled.

### 5. Idempotency & Reversal Policy
- Customer payment submission requires an optional `idempotency_key`. Retrying with identical key returns existing payment record; retrying with conflicting payload returns HTTP 422.
- Customer payments are **append-only and immutable**. Hard deletion is prohibited if status is `posted`. Reversal creates a voided status or compensating reversal entry.

### 6. RBAC & Branch Visibility Rules
- **Customer Master:** Business-wide identity (`business_id`).
- **Business Owner:** Full access across all branches, views total whole-business customer balance and complete account ledger.
- **Branch Manager:** Access restricted to assigned branch operations. Can view customer identity and transactions/payments originating from assigned branch. Can record payments for assigned branch.
- **Salesperson:** Can search customers at POS and check current outstanding balance. Cannot record arbitrary customer payments or edit customer master data.

---

## 5. Proposed API Endpoints

- `GET /api/v1/customers` — List business customers (Owner: whole-business due; Manager: branch exposure).
- `POST /api/v1/customers` — Create customer master record.
- `GET /api/v1/customers/{customer}` — Customer details & current outstanding breakdown.
- `PATCH /api/v1/customers/{customer}` — Update basic customer details (name, phone, email, address, notes). `balance` is ignored/blocked.
- `GET /api/v1/customers/{customer}/ledger` — Chronological account history statement.
- `POST /api/v1/customers/{customer}/payments` — Record customer collection payment (requires active business & branch context, idempotency key).
- `GET /api/v1/customers/{customer}/payments` — List customer payment receipts.
- `POST /api/v1/customer-payments/{payment}/reverse` — Reverse/void a recorded customer payment (Owner/Manager authorized).

---

## 6. Phase 6 Binding Acceptance Criteria (40 Criteria)

1. **Tenant Isolation:** Customer master data and Khata/payment records are strictly isolated by `business_id`.
2. **Cross-Tenant IDOR Protection:** Attempting to view, update, or post payments for a Customer in another business returns HTTP 403/404.
3. **Business-Wide Identity:** Customer identity is scoped to `business_id` without duplicating customer records per branch.
4. **Branch Provenance:** Every `CustomerPayment` event records `branch_id` of the branch where collection occurred.
5. **No Client Balance Writes:** Client-submitted `balance` fields in `POST/PATCH /customers` are ignored or rejected.
6. **Opening Balance Provenance:** Customer opening balance is explicitly stored in `customers.opening_balance` and cannot be mutated arbitrarily after creation.
7. **Credit Sale Integration:** Credit sales (`due_amount > 0`) contribute to active customer receivable without mutating cash/bank ledgers.
8. **No Double-Counting POS Payments:** Initial checkout `paid_amount` reduces `due_amount` at sale posting and is not counted as a separate `CustomerPayment`.
9. **Cancelled Sale Receivables:** Cancelled sales (`status = 'cancelled'`) contribute exactly 0 to customer receivable.
10. **SaleReturn Receivable Reduction:** Returns against credit sales reduce effective customer receivable by `refund_amount`.
11. **Refund Exposure Boundary:** Excess return refunds exceeding paid amounts do not create false positive customer debt.
12. **Source-of-Truth Consistency:** `customers.balance` is a cached projection identical to `CustomerAccountService::recalculateBalance()`.
13. **CustomerPayment Creation:** Valid `POST /customers/{id}/payments` creates an append-only `CustomerPayment` record with tenant and branch provenance.
14. **Tenant-Unique Payment Numbers:** Payment receipt numbers (`payment_number`) are unique per business tenant.
15. **Payment Amount Validation:** Customer payment amount must be greater than 0.
16. **Overpayment Prevention:** Payment exceeding current customer outstanding balance is rejected with HTTP 422 in MVP.
17. **Payment Idempotency (Identical Payload):** Duplicate payment submission with same idempotency key and identical payload returns original payment record.
18. **Payment Idempotency (Conflicting Payload):** Duplicate submission with same key but different amount/customer returns HTTP 422.
19. **Append-Only Immutability:** Posted `CustomerPayment` records cannot be updated or hard-deleted.
20. **Payment Reversal Workflow:** Reversing a customer payment updates status to `voided`, records audit trail, and recalculates customer balance atomically.
21. **Customer Hard Delete Restriction:** Customers with associated sales, returns, or payment history cannot be hard-deleted.
22. **Customer Soft Delete / Inactivation:** Deactivating or soft-deleting a customer preserves historical ledger entries and outstanding balance.
23. **Owner Access Scope:** Business Owner can view whole-business customer list, overall customer balances, and cross-branch account ledgers.
24. **Manager Access Scope:** Branch Manager can view customers and record payments within assigned branch, seeing branch-scoped ledger events.
25. **Manager Cross-Branch Boundary:** Branch Manager cannot post customer payments for unassigned branches.
26. **Salesperson POS Access:** Salesperson can search customer identity and view current due summary at POS.
27. **Salesperson Payment Restriction:** Salesperson cannot record arbitrary customer payments or alter customer master data.
28. **Active Business Context:** Active business selection resolved via `ResolveActiveBusiness` middleware governs all customer operations.
29. **Multi-Tenant User Isolation:** Users belonging to multiple businesses see distinct customer lists and balances per active business.
30. **Deterministic Ledger Order:** Customer account ledger entries are sorted deterministically by `date`, `created_at`, `id`.
31. **Running Balance Accuracy:** Chronological running balance in customer statement exactly matches cumulative mathematical formula.
32. **Optional Sale Allocation:** Customer payment can optionally specify `sale_id` for specific sale settlement or remain unassigned account payment.
33. **Phase 7 Boundary Preservation:** Customer payments update receivable ledger without posting to Phase 7 Cash/Bank ledgers.
34. **Database Atomicity:** Payment creation and cached balance updates execute inside a single DB transaction.
35. **Forward Migration Discipline:** Schema changes for Phase 6 use clean forward migration files without modifying executed past migrations.
36. **Zero Migration Drift:** Total migration files match total database migration rows with 0 pending migrations.
37. **Phase 1 Regressions Green:** Multi-tenancy, user membership, and RBAC tests remain 100% green.
38. **Phase 2–5 Regressions Green:** Product catalog, inventory ledger, supplier purchasing, and POS sales tests remain 100% green.
39. **Full Backend Suite Green:** Complete backend test suite passes with 0 failures.
40. **Frontend Build Success:** `npm run build` succeeds without TypeScript or bundle errors.

---

## 7. Execution Baseline Verification

### Backend Test Baseline
- **Command:** `php artisan test`
- **Result:** **191 passed / 508 assertions (0 failures)**
- **Duration:** 29.72s

### Frontend Build Baseline
- **Command:** `npm run build` (in `Frontend/`)
- **Result:** **Vite build SUCCESS in 1.94s**

---

## 8. Files Inspected During Prompt 1 Discovery
1. `app/Models/Customer.php`
2. `app/Http/Controllers/Api/V1/CustomerController.php`
3. `app/Models/KhataTransaction.php`
4. `app/Http/Controllers/Api/V1/KhataController.php`
5. `app/Models/Sale.php`
6. `app/Services/SaleService.php`
7. `app/Http/Controllers/Api/V1/SaleController.php`
8. `database/migrations/2026_08_24_051931_create_customers_table.php`
9. `database/migrations/2026_08_24_051931_create_khata_transactions_table.php`
10. `routes/api.php`
11. `Frontend/src/views/CustomerView.vue`
12. `Frontend/src/views/KhataView.vue`
13. `Frontend/src/views/ProfilecustomerView.vue`
14. `Frontend/src/views/PosSaleView.vue`

---

## 9. Historical Prompt 1 Status Snapshot [SUPERSEDED BY PROMPTS 2–4]

> [!NOTE]
> **HISTORICAL SNAPSHOT (PROMPT 1 COMPLETION)**
> This section records the status as it existed at the end of Prompt 1. Current canonical status for Phase 6 is **COMPLETE** (Prompts 1–4 complete). See Section 14 for the final phase closure decision.

### Historical Prompt 1 Position
Phase 6 of 10 — Prompt 1 of 4

### Historical Status
IN PROGRESS (At Prompt 1 completion)

### Completed at Prompt 1
- Prompt 1/4 — Discovery / Audit / Preparation (Architecture Correction Complete)

### Status After Prompt 4 Execution
- Prompt 1/4 — COMPLETE
- Prompt 2/4 — COMPLETE
- Prompt 3/4 — COMPLETE
- Prompt 4/4 — COMPLETE
- **Phase 6 Status:** **VERIFIED COMPLETE**

---

## 10. Prompt 1 Architecture Correction & Detailed Database Audit

### A. Legacy Khata Reconciliation Audit & Cutover Strategy
An explicit customer-by-customer database reconciliation audit was performed against all 17 customer records and 13 legacy `khata_transactions` in the development database.

#### Detailed Audit Findings:
- **Total Customer Records:** 17
- **Zero Balance & No Events:** 7 customers (IDs: 3, 12, 13, 14, 15, 16, 17) with `$0.00` balance and 0 `khata_transactions`.
- **Exactly Reconcilable:** 7 customers (IDs: 1, 2, 6, 7, 9, 10, 11) where `customers.balance == (SUM(give) - SUM(got))`. Total 13 legacy `khata_transactions` account 100% mathematically for these balances.
- **Partially Reconcilable:** 0 customers.
- **No Legacy Events (Balance Exists):** 3 customers (IDs: 4 [70,000.00], 5 [80,000.00], 8 [15,000.00]) where `customers.balance` was set directly via raw input without any `khata_transactions` recorded.
- **Inconsistent:** 0 customers.

#### Single Source-of-Truth Migration Policy & Cutover Strategy:
- **Cutover Policy:** Existing pre-Phase-6 historical balances are summarized into `customers.opening_balance` at the Phase 6 cutover point:
  $$\text{opening\_balance} = \text{legacy } \mathtt{customers.balance} - \text{receivables derivable from active Phase 5 credit Sales}$$
  *(Since current development DB contains 0 active credit sales with `due_amount > 0`, `opening_balance` equals current legacy `customers.balance`).*
- **Legacy Khata Post-Cutover Role:** Legacy `khata_transactions` remain available as **read-only historical pre-cutover details** for UI reference, but **MUST NOT** affect the post-cutover `CustomerAccountService` balance calculation (to prevent double-counting historical events already embedded in `opening_balance`).
- **Initial Proposed Formula (SUPERSEDED):**
  $$\text{Customer Outstanding} = \text{opening\_balance} + \sum_{\text{Post-Cutover Active Sales}} \text{effective\_due} - \sum_{\text{Posted CustomerPayments}}$$
  *(Note: The phrase "Post-Cutover Active Sales" was the initial proposal. As documented in Section 11.A below, runtime calculation evaluates **all eligible non-cancelled Sales** directly because historical credit-sale receivables were subtracted during opening_balance migration calculation).*

### B. Customer Phone Uniqueness Decision
- **Database Audit:** 17 customer records audited. Non-null phones count = 17.
- **Duplicates Found:** String `"Not provided"` is shared by 2 customers. Phone `"03328055649"` is shared by 2 customers in Business ID 3. Phone `"03189525288"` is shared across businesses.
- **Decision:** **NO mandatory unique constraint on `(business_id, phone)`**. Customer phone numbers are not unique business identifiers (family members, shared store contacts, or default placeholder strings exist). Customer lookup follows tenant-safe ID / query filters without enforcing phone uniqueness.

### C. Business-Wide Cached Balance vs Branch Exposure
- **`customers.balance` Cache:** Represents **whole-business** customer outstanding receivable (`opening_balance` + active credit Sales - posted `CustomerPayments`).
- **Owner View:** Business Owner sees whole-business `customers.balance`.
- **Manager View:** Branch Manager sees dynamically computed **branch exposure** (`branch_outstanding`):
  $$\text{branch\_outstanding} = \sum_{\text{Branch Sales}} \text{effective\_due} - \sum_{\text{Branch CustomerPayments}}$$
  *(Note: Business-wide unallocated legacy `opening_balance` has no branch provenance and is visible to Owner only; Manager branch exposure is strictly branch-attributed).*
- **Manager Exposure Boundary:** Manager branch exposure calculation **MUST NOT** mutate or overwrite the business-wide `customers.balance` cached projection column.

### D. Voided Payment & Reversal Formula
- **Posted Payment Filtering:** Only `CustomerPayment` records with `status = 'posted'` reduce customer outstanding.
- **Voided Payment Effect:** Reversing a customer payment updates `status = 'voided'`, sets `reversed_at`, `reversed_by`, and `reversal_reason`, and contributes **0** to customer outstanding balance.
- **Atomic Recalculation:** Payment reversal updates `status` and atomically recalculates `customers.balance` inside a database transaction. The original payment remains visible in account history as `voided` for full auditability.

### E. Optional `sale_id` Reference Semantics
- `customer_payments.sale_id` is an optional reference/provenance link for linking collections to specific invoices.
- In MVP, payments reduce customer account-level outstanding balance.

### F. Manager vs Owner Payment Collection Limits
- **Owner Payment Maximum:** Can record payment up to whole-business `Customer Outstanding`.
- **Manager Payment Maximum:** Can record payment up to assigned-branch `branch_outstanding` to prevent collecting against debt belonging to another branch without authorization.

### G. Opening Balance Immutability & Customer Soft Delete
- **Opening Balance Edit Policy:** `opening_balance` can only be set during initial customer creation or migration. Once any post-cutover transaction (`Sale`, `CustomerPayment`) exists for the customer, direct modification of `opening_balance` via `PATCH` is blocked.
- **Customer Delete Policy:** Hard deletion (`$customer->delete()`) is strictly prohibited if financial history exists. Customers use Laravel `SoftDeletes` (`deleted_at`), which preserves historical ledger entries while deactivating the customer for new transactions.

### H. Legacy Khata Endpoint Deprecation
- Write endpoints on `/api/v1/khata` (`POST`, `DELETE`) are **disabled / deprecated** upon Phase 6 deployment.
- Frontend `KhataView.vue` is refactored to interact strictly with the new `/api/v1/customers/{id}/payments` endpoints.
- Legacy `khata_transactions` table remains read-only for historical reference.

### I. Revised Phase 6 Acceptance Criteria Highlights (40 Criteria Total)
- Criterion 5: Client-submitted `balance` or post-activity `opening_balance` fields are ignored or rejected.
- Criterion 12: `customers.balance` is a business-wide cached projection equal to `opening_balance + active_sales_due - posted_customer_payments`.
- Criterion 20: Payment reversal updates `status` to `voided`, records audit trail, and recalculates balance atomically without deducting voided payments.
- Criterion 24: Manager receives dynamically computed branch-scoped exposure and cannot collect beyond branch outstanding.
- Criterion 40: Phone numbers do not enforce tenant or global uniqueness constraints.

---

## 12. Prompt 2 — Core Implementation Record

### A. Implementation Summary
Prompt 2 implemented the core Phase 6 architecture:
1. Created clean forward migrations adding `customers.opening_balance`, `customers.deleted_at`, and creating the `customer_payments` table with tenant-scoped composite unique constraints.
2. Implemented `App\Models\CustomerPayment` model and updated `App\Models\Customer` with soft deletes, guarded balances, and relationships.
3. Implemented `App\Services\CustomerAccountService` with authoritative raw balance, customer outstanding, customer credit, branch exposure, and account ledger generation.
4. Implemented `App\Services\CustomerPaymentService` with structural row locking, tenant/branch validation, authorized role payment ceilings, idempotency collision safety, tenant-unique payment numbering (`CP-YYYYMMDD-XXXX`), atomic balance recalculation, and payment reversals.
5. Integrated automatic customer balance recalculation into `App\Services\SaleService` for credit sale creation, unpaid sale cancellation, and sale return processing.
6. Hardened `App\Http\Controllers\Api\V1\CustomerController`, created `App\Http\Controllers\Api\V1\CustomerPaymentController`, and disabled legacy manual Khata write endpoints in `App\Http\Controllers\Api\V1\KhataController`.
7. Registered Phase 6 API routes in `routes/api.php` under Sanctum and `ResolveActiveBusiness` middleware.

### B. Files Created
1. `api/database/migrations/2026_09_19_000000_add_opening_balance_to_customers_table.php`
2. `api/database/migrations/2026_09_19_000001_create_customer_payments_table.php`
3. `api/app/Models/CustomerPayment.php`
4. `api/app/Services/CustomerAccountService.php`
5. `api/app/Services/CustomerPaymentService.php`
6. `api/app/Http/Controllers/Api/V1/CustomerPaymentController.php`

### C. Files Modified
1. `api/app/Models/Customer.php` (added `SoftDeletes`, `opening_balance` cast, guarded `balance`, added relationships)
2. `api/app/Http/Controllers/Api/V1/CustomerController.php` (hardened validation, blocked client balance manipulation, added ledger endpoint)
3. `api/app/Http/Controllers/Api/V1/KhataController.php` (disabled `store` and `destroy` endpoints with HTTP 410, preserved read-only `index`)
4. `api/app/Services/SaleService.php` (integrated `CustomerAccountService::recalculateBalance` for credit sales, cancellations, and returns)
5. `api/app/Http/Middleware/ResolveActiveBusiness.php` (added static `getActiveBusinessId` and `getBusinessId` helper methods)
6. `api/routes/api.php` (registered customer payments and customer ledger endpoints)
7. `api/tests/Feature/SaleCreditAndCustomerTest.php` (updated assertion to reflect Phase 6 credit balance calculation)
8. `api/tests/Feature/FinancialTest.php` (updated to assert legacy Khata deprecation and new customer payment API)

### D. Verification & Regression Evidence
- **Migration Status:** 46 migration files = 46 migration ledger rows (0 pending migrations).
- **Development Database Invariant:** **17 / 17 exact matches (100%)** on live MySQL `hbos` database.
- **Legacy Negative Credit Accounts:** 7 accounts preserved as signed negative raw balances with `outstanding = 0.00` and `credit = positive absolute value`.
- **Backend Test Suite:** **191 passed / 506 assertions (0 failures)**.
- **Frontend Production Build:** **Vite build SUCCESS in 1.08s**.

### E. Historical Prompt 2 Position & Status [SUPERSEDED]
- **Position at Prompt 2 Completion:** Phase 6 of 10 — Prompt 2 of 4 COMPLETE.
- **Current Canonical Status:** Phase 6 COMPLETE (All Prompts 1–4 complete; see Section 14).


---

## 11. Prompt 1 Final Reconciliation Semantics & Verified Invariants

### A. Runtime Formula Without Cutover Filter
The runtime `CustomerAccountService` does NOT rely on fragile cutover timestamp filters.
Instead, existing credit-Sale receivables are explicitly subtracted from legacy balances during migration when computing `opening_balance`:
$$\text{opening\_balance} = \text{legacy } \mathtt{customers.balance} - \text{existing\_authoritative\_sale\_receivable}$$

At runtime, `CustomerAccountService` evaluates **all eligible authoritative Sales** without cutover date branching:
$$\text{raw\_account\_balance} = \text{opening\_balance} + \sum_{\text{All Eligible Sales}} \text{effective\_due} - \sum_{\text{Posted CustomerPayments}}$$

### B. Verified Migration Invariant (17 / 17 Exact Matches)
For every customer record in the database, post-migration recalculated balance matches pre-migration legacy balance with 100% precision:
$$\text{recalculated\_balance} = \text{opening\_balance} + \text{existing\_authoritative\_sale\_receivable} - 0 = \text{legacy } \mathtt{customers.balance}$$

- **Authoritative Sale Receivable Audit:** Total existing credit-Sale receivable across all 17 customers = `$0.00` (all 25 existing Sales in development database were fully paid cash POS sales).
- **Invariant Result:** **17 / 17 EXACT MATCHES**. Zero discrepancies across all 17 customer accounts.

### C. Negative Legacy Balance Semantics & Customer Credit
- **Audit Findings:** 7 customers have negative balances (e.g., `-125,000.00`, `-55,000.00`, `-25,000.00`, `-20,000.00`) caused by legacy `got` collection events exceeding `give` events.
- **Semantic Rule:** Negative `opening_balance` represents **migrated historical customer credit / amount owed back to customer**.
- **Derived Concepts:**
  - `raw_account_balance` = `opening_balance + SUM(effective_due) - SUM(posted CustomerPayments)` (stored signed cache in `customers.balance`).
  - `customer_outstanding` = `max(raw_account_balance, 0)` (amount customer owes business).
  - `customer_credit` = `max(-raw_account_balance, 0)` (amount business owes customer / available account credit).
- **Future Credit Sale Offset:** Historical negative opening credit automatically offsets future credit Sale receivables (e.g., credit `-500` + new credit Sale `1,000` = `raw_account_balance 500`, `customer_outstanding 500`).
- **New Operations Non-Negative Rule:** For NEW Phase 6 operations:
  1. New customer creation requires `opening_balance >= 0`.
  2. Collection payments (`CustomerPayment`) cannot exceed `customer_outstanding` (cannot push `raw_account_balance` below zero).
- **Refund Exposure Boundary:** Phase 5 `SaleReturn` `refund_exposure` remains separate economic metadata for Phase 7 cash settlement and is **NOT** automatically converted into negative customer receivable credit.

### D. Legacy Branch & Actor Provenance Audit
- **Branch Provenance:** All 13 legacy `khata_transactions` rows have `branch_id NOT NULL`, referencing valid branches (`ID 2` & `ID 3`) belonging to the same business.
- **Branch Exposure Policy:** Legacy `khata_transactions` are summarized into Business-wide `opening_balance`. Unallocated legacy `opening_balance` is a Business-wide figure visible to Owner only. Manager `branch_outstanding` evaluates strictly branch-attributed post-cutover events:
  $$\text{branch\_outstanding} = \max\left(\sum_{\text{Branch Sales}} \text{effective\_due} - \sum_{\text{Branch CustomerPayments}}, 0\right)$$
- **Actor Provenance:** `khata_transactions` lacks a `user_id` column. Historical actor provenance is classified as **`UNKNOWN / LEGACY SYSTEM`**.

### E. Final Business-Wide vs Branch Formulas
- **Business-Wide (Owner):**
  $$\text{raw\_account\_balance} = \text{opening\_balance} + \sum_{\text{Eligible Sales}} \text{effective\_due} - \sum_{\text{Posted CustomerPayments}}$$
  $$\text{customer\_outstanding} = \max(\text{raw\_account\_balance}, 0)$$
  $$\text{customer\_credit} = \max(-\text{raw\_account\_balance}, 0)$$
- **Branch Exposure (Manager):**
  $$\text{branch\_outstanding} = \max\left(\sum_{\text{Branch Sales}} \text{effective\_due} - \sum_{\text{Branch CustomerPayments}}, 0\right)$$

### F. Revised 40-Point Acceptance Contract (Fully Aligned)
All 40 acceptance criteria remain strictly active, updated to incorporate deterministic migration formulas (no cutover filter), negative opening balance credit semantics, non-negative payment validation, and explicit branch exposure separation.

---

## 13. Prompt 3 — Integration / Security / Automated Testing Record

### A. Prompt 2 Re-Audit
1. **Model & Controller Integrity:** Confirmed `Customer.balance` is guarded from `$fillable` and client update payloads. Confirmed `CustomerPayment` records are append-only.
2. **Direct Balance Mutation Audit:** Zero `increment('balance')` or `decrement('balance')` operations found for customers across the codebase. All balance changes are routed through `CustomerAccountService::recalculateBalance()`.
3. **Legacy Khata Deprecation:** Confirmed `POST /api/v1/khata` and `DELETE /api/v1/khata/{id}` return HTTP 410 Gone, while `GET /api/v1/khata` is preserved for historical read-only query.
4. **Phase 7 Cash/Bank Boundary:** Zero `CashMovement` or `BankMovement` records created. Payment method remains transaction metadata.

### B. Migration Drift & Schema Parity
1. **Isolated SQLite Parity Test:** Built fresh database from zero and compared against live development database:
   - `customers` columns: `[id, business_id, name, phone, email, address, balance, created_at, updated_at, opening_balance, deleted_at]` — **EXACT MATCH (100%)**.
   - `customer_payments` columns: `[id, business_id, branch_id, customer_id, sale_id, user_id, payment_number, amount, payment_method, date, notes, idempotency_key, status, reversed_at, reversed_by, reversal_reason, created_at, updated_at]` — **EXACT MATCH (100%)**.
2. **Rollback & Re-apply Verification:** Phase 6 migrations rolled back cleanly (dropping `customer_payments`, `opening_balance`, `deleted_at`) and re-applied without error.
3. **Migration Ledger Parity:** 46 migration files = 46 migration ledger rows (0 pending migrations).

### C. Dedicated Phase 6 Automated Test Suites Created
1. `tests/Feature/CustomerTenantIsolationTest.php` (8 tests) — Verifies cross-tenant viewing, updating, deletion, ledger, payments, and reversals are blocked.
2. `tests/Feature/CustomerActiveBusinessSwitchingTest.php` (2 tests) — Verifies multi-business user switching via `X-Business-ID` header isolates customers and Spatie permissions.
3. `tests/Feature/CustomerBranchSecurityTest.php` (2 tests) — Verifies Branch Manager exposure ceiling, branch assignment validation, and Salesperson payment restrictions.
4. `tests/Feature/CustomerAccountReconciliationTest.php` (3 tests) — Verifies 8 opening balance scenarios, negative credit offset, and negative opening balance rejection on creation.
5. `tests/Feature/CustomerPaymentIdempotencyTest.php` (3 tests) — Verifies identical payload retry, conflicting payload rejection (amount, customer, branch, method, date), and cross-business same key isolation.
6. `tests/Feature/CustomerPaymentLifecycleTest.php` (2 tests) — Verifies payment creation, reversal audit fields, double-reversal rejection, and concurrency row locking simulation.
7. `tests/Feature/CustomerLedgerTest.php` (1 test) — Verifies exact 5-step chronological ledger sequence, Model B return double-count prevention, and running balance parity with `calculateRawBalance()`.
8. `tests/Feature/CustomerSoftDeleteAndSecurityTest.php` (4 tests) — Verifies customer mass-assignment balance protection, soft delete preservation, and legacy Khata HTTP 410 deprecation.

### D. Regression Test Results
- **Phase 1 Security Regressions:** 48 passed / 101 assertions (0 failures).
- **Dedicated Phase 6 Suites:** 30 passed / 115 assertions (0 failures).
- **Full Backend Suite:** **216 passed / 610 assertions (0 failures)**.
- **Frontend Production Build:** **Vite build SUCCESS in 6.78s**.

### E. Historical Prompt 3 Position & Status [SUPERSEDED]
- **Position at Prompt 3 Completion:** Phase 6 of 10 — Prompt 3 of 4 COMPLETE.
- **Current Canonical Status:** Phase 6 COMPLETE (All Prompts 1–4 complete; see Section 14).

---

## 14. Prompt 4 — Final Verification / Phase Completion Record

### A. Final Verification Summary
All 10 verification areas specified in Prompt 4 were evaluated, executed, and confirmed:
1. **Financial Reconciliation:** 100% exact match across all 17 development customers (`17 / 17 matches`, `0 mismatches`, `0.0 max absolute discrepancy`).
2. **Schema Integrity:** Canonical `date` field in `customer_payments`, `opening_balance` and `deleted_at` in `customers`. `UNIQUE(business_id, payment_number)` and `UNIQUE(business_id, idempotency_key)` indexes verified.
3. **Tenant & Branch Security:** Foreign tenant customer/payment access blocked (404/403). Owner has whole-business scope; Manager is restricted to assigned branch; Salesperson blocked from recording or reversing payments.
4. **CustomerPayment Integrity:** Reversals audit status `voided`, timestamp, user ID, and reason. Double reversal blocked. Concurrency protected via `Customer::lockForUpdate()`.
5. **Legacy Khata Preservation:** Legacy `khata_transactions` table preserved read-only (`GET /api/v1/khata`). Mutation endpoints (`POST`, `DELETE`) return `HTTP 410 Gone`.
6. **Sale Integration:** Credit sales update customer balance; cancellations remove active receivables; partial and full returns reduce net due without double-counting refund exposure (Model B).
7. **Migration Safety & Parity:** 46 migration files = 46 ledger rows (0 pending). `php artisan migrate` outputs "Nothing to migrate." Upgrade vs fresh isolated schema parity is 100% exact match. Rollback (step=2) and re-apply verified clean.
8. **Automated Regressions:**
   - Phase 1 Security: 48 passed / 101 assertions
   - Cumulative Tenant Isolation: 70 passed / 148 assertions
   - Phase 2 Catalog: 42 passed / 96 assertions
   - Phase 3 Inventory: 32 passed / 94 assertions
   - Phase 4 Purchases: 33 passed / 99 assertions
   - Phase 5 Sales: 28 passed / 96 assertions
   - Dedicated Phase 6: 25 passed / 105 assertions (30 / 115 with integrated sale tests)
   - Full Backend Suite: **216 passed / 611 assertions (0 failures, 73.34s)**
9. **Frontend Production Build:** Vite build SUCCESS in 968ms (`dist/` directory generated clean).
10. **40-Point Acceptance Contract:** **40 / 40 PASS**.

### B. Final Phase 6 Completion Decision
**PHASE 6 IS VERIFIED COMPLETE.**
- **Completed Prompts:** Prompt 1/4, Prompt 2/4, Prompt 3/4, Prompt 4/4.
- **Next Authorized Phase:** Phase 7 — Cash, Bank & Expenses.
- **Next Authorized Work:** Phase 7 — Prompt 1/4 — Discovery / Audit / Preparation.
- **Phase 7 Boundary:** Phase 7 core implementation remains locked until Phase 7 Prompt 1 discovery is completed and reviewed.


