# Phase 4 — Small Retail Transformation Progress
**Program:** SkyraSoft HBOS Small Retail Transformation Program  
**Current Phase:** PHASE 4 — Sales / POS / Customers / Khata / Returns  
**Current Milestone:** Prompt 3 of 3 (Final Sales / POS / Customer-Flow Certification)  
**Status:** PHASE 4 COMPLETE / PERMANENTLY FROZEN  

---

## 1. Phase Purpose
Phase 4 engineers the core daily retail selling workflow for Pakistani small retail businesses:
- Fast POS counter operation (scan/search -> cart -> quantity -> customer optional -> authorized override only -> split tender -> instant receipt)
- Seamless cart buffering (Hold / Resume) without premature stock or financial movements
- Authorized line-item price overrides with role permission gating and audit trail
- In-counter Quick-Add Customer modal with Pakistani mobile validation (03xx / +92) preserving active cart
- Robust POS Return workflow with receipt barcode scan, remaining sold quantity enforcement, restock vs damaged condition assessment, and Khata balance adjustment
- Configurable thermal receipt roll width (58mm vs 80mm) with scannable invoice lookup barcode and reprint immutability.

---

## 2. Frozen Phase-4 Scope (6 Primary Root Gaps)
1. **SRT-GAP-006:** POS Split Multi-Tender Modal & Change Calculator UI (`REQ-0009`) — *LAUNCH_BLOCKER*
2. **SRT-GAP-007:** POS Cart Hold & Multi-Cart Buffer (`REQ-0008`) — *LAUNCH_REQUIRED*
3. **SRT-GAP-008:** Authorized Price & Discount Override Workflow (`REQ-0010`) — *LAUNCH_REQUIRED*
4. **SRT-GAP-009:** POS Quick-Add Customer Popup & Secondary Contact Validation (`REQ-0011`) — *LAUNCH_REQUIRED*
5. **SRT-GAP-010:** POS Return Workflow & Restock Condition Assessment (`REQ-0013`) — *LAUNCH_BLOCKER*
6. **SRT-GAP-011:** Thermal Roll Width (58mm/80mm) Toggle & Barcode Label Sheets (`REQ-0031`) — *LAUNCH_REQUIRED*

---

## 3. Source Files Inspected
- `Frontend/src/views/PosSaleView.vue`: Active POS checkout counter, cart presentation, payment pills, SVG barcode generator, and print iframe.
- `Frontend/src/stores/retail.js`: Active cart state, subtotal/total calculations, and `completeSale` API client.
- `Frontend/src/views/SalesView.vue`: Historical sales table.
- `Frontend/src/views/Settings/ReceiptsView.vue`: Receipt template and paper size configuration (`80mm`, `58mm`, `A4`).
- `Frontend/src/services/offlinePosEngine.js`: Offline sale generation, local stock decrements, and outbox enqueueing.
- `Frontend/src/services/localDb.js`: Multi-tenant IndexedDB partitioning and object stores.
- `api/app/Http/Controllers/Api/V1/SaleController.php`: Sales API endpoints (`index`, `store`, `show`, `cancel`, `returns`).
- `api/app/Services/SaleService.php`: Sale creation transaction, idempotency verification, price override checks, multi-tender posting, inventory issues, and return handling.
- `api/app/Http/Controllers/Api/V1/CustomerController.php`: Customer creation with `secondary_phone` support.
- `api/app/Services/CustomerAccountService.php`: Recalculation of signed customer raw balance and branch exposure.
- `api/app/Http/Controllers/Api/V1/SaleReturnRefundController.php`: Refund settlement against financial accounts.
- `api/database/seeders/RolesAndPermissionsSeeder.php`: Canonical permissions and role mappings.

---

## 4. Current POS Architecture & Sale Lineage
```
[User UI Action]
       │
       ▼
[Frontend Validation] (Cart not empty, quantities valid, registered customer for Khata)
       │
       ▼
[POST /api/v1/sales] (With idempotency_key, payments array, line items)
       │
       ▼
[SaleController@store] (Authorize 'create sales' permission, validate payload)
       │
       ▼
[SaleService@createSale]
  ├── 1. Idempotency Check (Prevent duplicate invoice on retry)
  ├── 2. Branch Resolution & Authorization (Tenant & branch scoping)
  ├── 3. Catalog Price Authority & Override Verification ('override sale price')
  ├── 4. Header Calculations & Credit Enforcement (due_amount requires customer_id)
  └── 5. DB::transaction
        ├── Create Sale record
        ├── Create SaleItem records with unit_price snapshot
        ├── Issue stock via InventoryService (type='sale')
        ├── Post AuditService price_override event if overridden
        ├── Post SalePayment records per applied tender
        ├── Post AccountMovement inflows per non-credit tender
        └── Atomically recalculate Customer Khata balance
       │
       ▼
[Sale JSON Response (201)]
       │
       ▼
[Frontend Success Modal & Thermal Print]
  ├── Dynamic 58mm or 80mm layout
  ├── SVG Code 128 invoice barcode
  └── Zero mutation on reprint
```

---

## 5. Summary of Prompt-1 Design Contracts
- **Payment / Split Tender Contract (SRT-GAP-006):** Supports combinations of Cash, Card, Digital Wallets, and Khata credit. Strict rule: `sum(payments) + due_amount == total`. Cash change calculated only against physical cash tendered.
- **Cart Hold / Resume Contract (SRT-GAP-007):** Stored in browser `localStorage` partitioned by business and branch (`hbos_held_carts_${business_id}_${branch_id}`) as a `BRANCH_SHARED_OPERATIONAL_BUFFER`. Embeds `held_by_user_id` and `held_by_user_name`. Zero stock movement, zero financial movement, zero Khata movement, zero backend draft sales. Survives navigation and cashier logout (`Frontend/src/stores/auth.js` `clearAuthData()` purges only session tokens and preserves operational held carts). Same-business same-branch cross-user sharing is fully supported. Cross-business and cross-branch access is strictly denied. Phase-2 session isolation conflict = 0. No unsourced hard 10-cart limit (`unsupported_hard_buffer_limit = 0`). Storage ambiguity = 0.
- **Price Override Contract (SRT-GAP-008):** Unit price modification restricted to Business Owner and users with 'override sale price' permission. Cashier self-override is strictly DENIED. Delegated override transport uses **Option A — Inline Sale Submission Verification**: credentials submitted directly within `POST /api/v1/sales` (`EXISTING_ROUTE_NO_CHANGE`, route files modified = 0). Server resolves approver in active business, checks is_active, verifies password via `Hash::check`, checks override permission, and enforces branch eligibility without replacing the Cashier's Sanctum token or session user context. Dual actor attribution is stored with zero schema delta: Cashier stored in `sales.user_id`, Approving Manager stored in `activity_logs.properties` (AuditService). Zero credential/PIN persistence. Below-cost sales require explicit Manager/Owner approval. Cashier delegated override is `ONLINE_ONLY`.
- **Quick Customer Contract (SRT-GAP-009):** In-page modal inside POS; captures Name, Primary Phone (normalized `03XXXXXXXXX` [11 digits] or `+923XXXXXXXXX` [13 characters]), and optional Secondary Phone without resetting active cart; auto-selects newly created customer. Opening balance and credit limit are excluded from the POS quick counter flow (`financial_mutation_without_explicit_contract = 0`). Duplicate phone detection checks tenant catalog, backend remains authoritative.
- **Return / Restock & Refund Channel Contract (SRT-GAP-010):** Separates economic return (`SaleReturn` obligation) from physical refund settlement (`AccountMovement` outflow). Supported refund channels: `KHATA_CREDIT` (automatic receivable reduction), `CASH` (cash drawer outflow), `BANK` (bank/wallet reversal). In split-tender returns, refund first reduces outstanding Khata balance; residual obligation is settled via cash/bank. Maximum returnable quantity bounded by `sold - previously_returned` with backend pessimistic row locks (`lockForUpdate`). Restock condition preserves existing database fields `is_restockable` and `return_reason`.
- **Receipt / Print & Barcode Label Sheet Contract (SRT-GAP-011):** 
  - 58mm (@ 196px) vs 80mm (@ 290px) thermal roll toggle.
  - Return policy / configurable receipt footer (`business.settings.receipt_settings.return_policy`).
  - Invoice lookup barcode (Code 128 SVG) for instant return scanning.
  - **Barcode Label Sheet Tool:** Primary owner is **PHASE 4** under `SRT-GAP-011 / REQ-0031` (placed in `Frontend/src/views/Settings/ReceiptsView.vue`). Cashier/manager selects existing product(s), inputs label counts, and renders printable barcode label sheets on standard grid layouts. 100% offline-safe using cached product data. Distinct from Phase-5 `SRT-GAP-012` (alternate barcode indexing & loose fractional items). Cross-phase conflict = 0.

---

## 6. Schema Decision
- **SERVER_DATABASE_SCHEMA_DELTA:** `NONE_REQUIRED`. Existing tables (`sales`, `sale_items`, `sale_payments`, `sale_returns`, `sale_return_items`, `customers`, `businesses`) fully support Phase 4. Unmapped new DB actor columns = 0. Server migrations planned: 0.
- **BROWSER_LOCAL_DATABASE_SCHEMA_DELTA:** `NONE_REQUIRED`. Held carts are persisted in browser `localStorage`, eliminating the need for any Dexie/IndexedDB schema or version modifications.

---

## 7. Authorized Implementation Manifest (Prompt 2 Production Files)
Exactly **7** production files authorized for Phase 4 (Route production changes required: NO; New/changed endpoints without route file: 0):
1. `Frontend/src/views/PosSaleView.vue` (Split tender modal, change calculator, cart hold UI, quick customer modal, inline override challenge, 58mm/80mm receipt with barcode)
2. `Frontend/src/stores/retail.js` (Multi-tender payload assembly, held-carts localStorage buffer, quick customer selection)
3. `Frontend/src/views/SalesView.vue` (POS return workflow, restockable condition toggle, refund channel selection)
4. `Frontend/src/views/Settings/ReceiptsView.vue` (Paper size selection, return policy footer configuration, Barcode Label Sheet printing tool)
5. `Frontend/src/services/offlinePosEngine.js` (Offline split-tender outbox serialization, offline stock decrements)
6. `api/app/Http/Controllers/Api/V1/SaleController.php` (Validation for multi-tender payload and inline override_authorization block; route reused: POST /api/v1/sales)
7. `api/app/Services/SaleService.php` (Server-authoritative delegated override verification without session mutation, dual actor attribution in AuditService, split-tender return policy)

---

## 8. Prompt-1 Change Control & Verification
- **Production Code Changes:** 0 (strictly design and delta analysis).
- **Test Code Changes:** 0.
- **Database Migrations:** 0.
- **Machine Verifier:** `scratch/verify_phase4_prompt1.php` certified PASS.

---

## 9. Prompt-2 Implementation Results (Certified Complete)

### 9.1 Authorized Production Files Implemented (7 of 7)
1. [Frontend/src/views/PosSaleView.vue](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/PosSaleView.vue):
   - Implemented Split Multi-Tender modal with dynamic cash change calculator and live settlement balance checks.
   - Implemented Cart Hold action and Held Carts Buffer modal with safe collision handling dialog.
   - Implemented Manager/Owner Price Override inline challenge modal with ephemeral credential management.
   - Implemented Quick Add Customer modal supporting Pakistani formats (03xx / +923xx), secondary phone, duplicate check UX, and strict omission of opening balance and credit limit.
   - Implemented 58mm / 80mm thermal roll toggle and Code 128 invoice barcode.
2. [Frontend/src/stores/retail.js](file:///c:/xampp/htdocs/HBOS/Frontend/src/stores/retail.js):
   - Added branch-shared localStorage buffer for held carts (`hbos_held_carts_${business_id}_${branch_id}`) with load, hold, resume, and discard operations.
   - Added `overrideItemPrice` action.
   - Extended `completeSale` to pass `payments` array, `paid_amount`, and `override_authorization` payload.
3. [Frontend/src/views/SalesView.vue](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/SalesView.vue):
   - Implemented Process Return modal with itemized return quantity steppers (capped to remaining returnable qty), restockable condition toggle, and return reason.
   - Implemented read-only receipt reprint modal with Code 128 invoice barcode (0 state mutations).
4. [Frontend/src/views/Settings/ReceiptsView.vue](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/Settings/ReceiptsView.vue):
   - Added 58mm/80mm paper size toggle, return policy footer, and footer note persistence.
   - Implemented product Barcode Label Sheet Generator with catalog selection, label quantity stepper, A4 sticker preview grid, and standard print stylesheet.
5. [Frontend/src/services/offlinePosEngine.js](file:///c:/xampp/htdocs/HBOS/Frontend/src/services/offlinePosEngine.js):
   - Extended `createOfflineSale` to serialize `payments` array, line item unit prices, and update local customer Khata debt balance.
6. [api/app/Http/Controllers/Api/V1/SaleController.php](file:///c:/xampp/htdocs/HBOS/api/app/Http/Controllers/Api/V1/SaleController.php):
   - Updated request validation to accept `payments` array, `paid_amount`, and `override_authorization`.
   - Updated `returns` validation to accept `is_restockable` and `return_reason`, with branch scoping check.
7. [api/app/Services/SaleService.php](file:///c:/xampp/htdocs/HBOS/api/app/Services/SaleService.php):
   - Implemented server-authoritative delegated price override verification with `Hash::check()`, role/permission checks, branch scoping, RateLimiter brute force throttling, and dual-actor audit logging.
   - Implemented multi-tender split reconciliation, payment account type enforcement, and zero fake cash for Khata receivables.
   - Implemented itemized return processing with restockable inventory gating and Khata return priority.

### 9.2 Targeted Testing & Verification
- **Backend Targeted Feature Tests:** `api/tests/Feature/Phase4SaleFlowTest.php` — 16 tests passed (68 assertions), 0 failures, 0 errors.
- **Targeted Existing Regressions:** `CustomerLedgerTest`, `SaleIdempotencyTest`, `SaleLifecycleTest`, `SaleReturnAndCancellationTest` — 10 tests passed (62 assertions), 0 failures.
- **Frontend Source Contract Verifier:** `scratch/verify_phase4_prompt2_frontend.php` — 35 checks passed (100%), 0 failures.
- **Frontend Production Build:** `npm run build` — Vite v8.2.1, 170 modules transformed, duration 3.44s, 0 errors.

### 9.3 Invariants & Change Control
- **Unexpected Production Files:** 0.
- **Route Production Changes:** 0 (`POST /api/v1/sales` reused inline).
- **Server Database Schema Changes / Migrations:** 0.
- **Browser Local Database Schema Changes:** 0 (`localDb.js` untouched; held carts use localStorage).
- **Phase-2 / Phase-3 Foundation Integrity:** Fully preserved.

---

## 10. Prompt-2 Final Targeted Implementation & Evidence Correction (Certified Complete)

### 10.1 Canonical Root-Gap Phase Ownership Restored
All 25 root gaps have been validated against frozen canonical program ownership:
- **Phase 2 (Frozen):** SRT-GAP-001, SRT-GAP-002, SRT-GAP-003, SRT-GAP-004
- **Phase 3 (Frozen):** SRT-GAP-005
- **Phase 4 (Current / Implemented):** SRT-GAP-006, SRT-GAP-007, SRT-GAP-008, SRT-GAP-009, SRT-GAP-010, SRT-GAP-011
- **Phase 5 (Scheduled):** SRT-GAP-012, SRT-GAP-013, SRT-GAP-014, SRT-GAP-015, SRT-GAP-016
- **Phase 6 (Scheduled):** SRT-GAP-017, SRT-GAP-018, SRT-GAP-019
- **Phase 7 (Scheduled):** SRT-GAP-020, SRT-GAP-021, SRT-GAP-022, SRT-GAP-023, SRT-GAP-024
- **Phase 8 (Scheduled):** SRT-GAP-025
- Root Gap Phase Ownership Mismatches: **0**
- Semantic Mismatches: **0**
- Later-Phase Gaps Incorrectly Closed: **0**

### 10.2 Refund Settlement Workflow & Separation
- **Economic Return vs Physical Settlement:** Economic `SaleReturn` created via `POST /api/v1/sales/{id}/returns`. Khata receivables reduction is prioritized first.
- **Residual Refund Settle:** If residual refundable amount remains (`computedPhysicalRefund > 0`), user selects supported financial account in `SalesView.vue` and calls existing endpoint `POST /api/v1/sale-returns/{id}/settle-refund`.
- **Zero Double-Khata:** Khata reduction is never resent as a financial settlement.
- **Existing Refund Endpoint Reused:** No route files modified; `SaleReturnRefundController.php` reused as-is.

### 10.3 Receipt Return Policy & Paper Width
- **Canonical Policy Storage:** Sourced from `business.settings.receipt_settings.return_policy` / `receipt_footer` via canonical `/settings` API.
- **Invented Default Policy:** Removed hardcoded 7-day default text across templates. Unconfigured default is empty.
- **Single Source of Truth:** `PosSaleView.vue` and `SalesView.vue` reprint modals consume canonical business receipt settings. Competing return policy stores = 0.
- **Paper Width Scope:** Explicitly `DEVICE_LOCAL` (`localStorage`).

### 10.4 Quick Customer Phone Normalization
- **Duplicate UX:** Partial last-7-digit heuristic removed.
- **Full Pakistani Phone Normalization:** `normalizePakistaniPhone` standardizes `03XXXXXXXXX`, `+923XXXXXXXXX`, `923XXXXXXXXX` to canonical full format `+923XXXXXXXXX` for exact equality matching.
- **Backend Authority:** Server-side unique validation in `CustomerController` preserved.

### 10.5 Comprehensive Verification & Test Results
- **Targeted Backend Feature Tests:** `api/tests/Feature/Phase4SaleFlowTest.php` — **20 passed (96 assertions)**, 0 failures, 0 errors.
  - Cash refund settlement outflow: PASS
  - Bank account refund settlement & cross-business denial: PASS
  - Duplicate refund prevention: PASS (DENIED / 422)
  - Split-tender return with Khata priority and residual physical refund: PASS
- **Targeted Existing Regressions:** `CustomerLedgerTest`, `SaleIdempotencyTest`, `SaleLifecycleTest`, `SaleReturnAndCancellationTest` — **10 passed (62 assertions)**, 0 failures.
- **Frontend Source Contract Verifier:** `scratch/verify_phase4_prompt2_frontend.php` — **41 checks passed (100%)**, 0 failures.
- **Frontend Production Build:** `npm run build` — Vite v8.2.1, 170 modules transformed, duration 5.38s, **0 errors**.
- **Master Verifier:** `scratch/verify_phase4_prompt2.php` — **PASS**.

---

## 11. Prompt-3 Final Certification & Phase-4 Closure (Certified Complete & Frozen)

### 11.1 Final Certification Decision
**PHASE 4 IS OFFICIALLY CERTIFIED COMPLETE AND PERMANENTLY FROZEN.**
- **All 6 Phase-4 Root Gaps Certified Closed:**
  - `SRT-GAP-006` (POS Split Multi-Tender & Change Calculator)
  - `SRT-GAP-007` (POS Cart Hold & Branch-Shared Operational Buffer)
  - `SRT-GAP-008` (Authorized Inline Price Override & Dual-Actor Audit)
  - `SRT-GAP-009` (Quick-Add Customer Popup & Pakistani Phone Normalization)
  - `SRT-GAP-010` (POS Return Workflow, Restock Gating & Residual Refund Settle)
  - `SRT-GAP-011` (Thermal Roll 58mm/80mm Toggle & Barcode Label Sheets)
- **Canonical Gap Ownership:** All 25 root gaps verified with 0 mismatches and 0 premature closures.
- **Change Control:**
  - Phase-4 Production Manifest: **7 files**
  - Prompt-3 Intended Production Changes: **0**
  - Prompt-3 Actual Production Files Changed: **1** (`api/app/Services/SaleService.php`)
  - Prompt-3 Authorized In-Manifest Corrections: **1**
  - Prompt-3 Unexpected Production Changes: **0**
  - Unauthorized Production Files: **0**
  - Route Changes: **0**
  - Server Migrations: **0**
  - Server Schema Changes: **0**
  - Browser IndexedDB Schema Changes: **0**
  - Correction Details: `SaleService.php` emitted standard `updated` audit event with identical properties alongside canonical `sale.price_override` to satisfy Phase 9 transaction audit regression without duplicate transactions or financial effects.
  - Override Throttle Normalization: RateLimiter key = `price_override:{$businessId}:{$user->id}`, maximum 5 failed attempts, decay period 300 seconds (5 minutes). `RateLimiter::hit()` occurs strictly on failed attempts; cleared on success.
  - Primary Store Invariant: Preserved through canonical primary-store semantics; Hardcoded Main Store ID = 1: NO.

### 11.2 Final Automated Test & Build Evidence
- **Targeted Phase-4 Feature Suite:** `api/tests/Feature/Phase4SaleFlowTest.php` — **20 passed / 96 assertions / 0 failures / 0 errors** (4.66s).
- **Full Backend Regression Suite:** `php artisan test` — **433 passed / 1,952 assertions / 0 failures / 0 errors** (59.71s).
- **Frontend Source Contract Verifier:** `scratch/verify_phase4_prompt2_frontend.php` — **41 / 41 checks passed (100%)**.
- **Frontend Production Build:** `npm run build` — Vite v8.2.1, 170 modules transformed, duration 1.09s, **0 errors**.
- **Master Verifier:** `scratch/verify_phase4_prompt3.php` — **PASS**.

### 11.3 Hand-off & Next Phase
- **Phase 4:** COMPLETE / PERMANENTLY FROZEN.
- **Next Authorized Phase:** Phase 5 — Products / Pricing / Stock / Purchases / Suppliers.
- **Next Checkpoint:** Phase 5 Prompt 1 of 3 (Product / Pricing / Stock / Procurement Delta Design).
