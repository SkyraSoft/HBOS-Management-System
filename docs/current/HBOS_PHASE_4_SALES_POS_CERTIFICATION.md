# SKYRASOFT HBOS SMALL RETAIL TRANSFORMATION PROGRAM
# PHASE 4 — FINAL SALES / POS / CUSTOMER-FLOW CERTIFICATION
**Document ID:** `HBOS-DOC-P4-CERTIFICATION-001`  
**Phase:** 4 of 10 — Sales / POS / Customers / Khata / Returns  
**Checkpoint:** Prompt 3 of 3 (Final Closure Certification)  
**Status:** **CERTIFIED COMPLETE & PERMANENTLY FROZEN**  

---

## 1. Executive Summary & Program Scope

Phase 4 of the HBOS Small Retail Transformation Program focuses on retail counter checkout, split payments, operational cart buffering, authorized price overrides, frictionless customer onboarding, customer debt (Khata) reconciliation, itemized returns, thermal receipt generation, and product barcode sheet printing.

With the execution of Prompt 3, all six primary Phase-4 root gaps (`SRT-GAP-006` through `SRT-GAP-011`) are officially certified as complete. All 25 root gaps across the 10-phase program have had their frozen canonical ownership verified with zero ownership drift and zero premature closures.

The implementation strictly honors the frozen seven-file production boundary with zero route additions, zero database schema changes, zero database migrations, and zero IndexedDB schema modifications.

---

## 2. Certified Root Gaps & Canonical Ownership

| Root Gap ID | Canonical Title | Owner Phase | Certified Status |
|:---|:---|:---:|:---:|
| **SRT-GAP-006** | POS Split Multi-Tender Modal & Change Calculator UI | **Phase 4** | **CERTIFIED_COMPLETE_PHASE4** |
| **SRT-GAP-007** | POS Cart Hold & Multi-Cart Buffer | **Phase 4** | **CERTIFIED_COMPLETE_PHASE4** |
| **SRT-GAP-008** | Authorized Price & Discount Override Workflow | **Phase 4** | **CERTIFIED_COMPLETE_PHASE4** |
| **SRT-GAP-009** | POS Quick-Add Customer Popup & Secondary Contact Validation | **Phase 4** | **CERTIFIED_COMPLETE_PHASE4** |
| **SRT-GAP-010** | POS Return Workflow & Restock Condition Assessment | **Phase 4** | **CERTIFIED_COMPLETE_PHASE4** |
| **SRT-GAP-011** | Thermal Roll Width (58mm/80mm) Toggle & Barcode Label Sheets | **Phase 4** | **CERTIFIED_COMPLETE_PHASE4** |

### Program Gap Allocation Integrity
- **Phase 2 (Frozen):** `SRT-GAP-001` through `SRT-GAP-004` (4 gaps)
- **Phase 3 (Frozen):** `SRT-GAP-005` (1 gap)
- **Phase 4 (Current / Frozen):** `SRT-GAP-006` through `SRT-GAP-011` (6 gaps)
- **Phase 5 (Scheduled):** `SRT-GAP-012` through `SRT-GAP-016` (5 gaps)
- **Phase 6 (Scheduled):** `SRT-GAP-017` through `SRT-GAP-019` (3 gaps)
- **Phase 7 (Scheduled):** `SRT-GAP-020` through `SRT-GAP-024` (5 gaps)
- **Phase 8 (Scheduled):** `SRT-GAP-025` (1 gap)
- **Ownership Mismatches:** 0
- **Later-Phase Gaps Prematurely Closed:** 0

---

## 3. Frozen Seven-File Production Manifest

| # | File Path | Scope & Responsibilities |
|:---:|:---|:---|
| 1 | `Frontend/src/views/PosSaleView.vue` | Split-tender payment modal, dynamic change calculator, cart hold/resume dialog, quick customer popup with Pakistani phone normalization, inline price override challenge modal, 58mm/80mm thermal receipt with Code 128 invoice barcode. |
| 2 | `Frontend/src/stores/retail.js` | Multi-tender payload assembly, branch-shared `localStorage` buffer for held carts (`hbos_held_carts_${biz}_${branch}`), quick customer state selection, `overrideItemPrice` action. |
| 3 | `Frontend/src/views/SalesView.vue` | POS return workflow, itemized quantity stepper with lock protection, restockable condition toggle, return reason input, residual physical refund channel selection, read-only reprint modal. |
| 4 | `Frontend/src/views/Settings/ReceiptsView.vue` | Device-local thermal paper width toggle (`58mm`/`80mm`), canonical business return policy/footer persistence, Product Barcode Label Sheet Generator. |
| 5 | `Frontend/src/services/offlinePosEngine.js` | Offline split-tender serialization (`payments` array), unit prices, offline stock decrements, local customer debt tracking. |
| 6 | `api/app/Http/Controllers/Api/V1/SaleController.php` | Validation rules for `payments` array, `paid_amount`, inline `override_authorization` credentials block, and branch scoping on returns. |
| 7 | `api/app/Services/SaleService.php` | Server-authoritative delegated override verification via `Hash::check()`, RateLimiter brute force throttling, dual-actor audit logging, multi-tender atomic reconciliation, itemized return processing with restock gating and Khata debt reduction priority. |

**Invariants:**
- Unexpected Production Files: **0**
- Route Production Changes: **0**
- Server Migrations: **0**
- Server Database Schema Changes: **0**
- Browser IndexedDB Schema Changes: **0**

---

## 4. Subsystem Certification Details

### 4.1 Split Tender & Single-Tender Non-Regression (SRT-GAP-006)
- **Tender Combination:** Supports combinations of Cash, Card, Digital Wallets (JazzCash/Easypaisa), and Khata credit.
- **Reconciliation Authority:** Server authoritatively verifies:
  $$\sum \text{payments.amount} + \text{due\_amount} = \text{total}$$
- **Cash Change Derivation:** Cash change is derived strictly from Cash tendered:
  $$\text{change} = \max(0, \text{cash\_tendered} - \text{cash\_applied})$$
  Change is never treated as revenue, never deducted from non-cash tenders, and never written to `SalePayment` rows.
- **Single-Tender Non-Regression:** Ordinary cash sales and walk-in sales bypass split modals and execute with zero friction.
- **Khata Guard:** Credit tender is strictly disallowed for walk-in/guest customers without registered customer ID.

### 4.2 Held Cart Operational Buffer (SRT-GAP-007)
- **Buffer Scope:** `BRANCH_SHARED_OPERATIONAL_BUFFER` in `localStorage` under `hbos_held_carts_${business_id}_${branch_id}`.
- **Cross-User Collaboration:** Cashier A can hold a cart; Cashier B logging in at the same branch can view, resume, or discard it. Original actor attribution (`held_by_user_id`, `held_by_user_name`) is preserved.
- **Zero Business Effects:** Holding a cart creates 0 sales, 0 backend drafts, 0 inventory decrements, 0 accounting entries, and 0 customer Khata movements.
- **Zero Sensitive Data:** Persisted buffer records contain zero passwords, auth tokens, PINs, or confidential manager credentials.

### 4.3 Authorized Price Override & Dual-Actor Audit (SRT-GAP-008)
- **Authorization Transport:** `SALE_SUBMISSION_INLINE_VERIFICATION` in `POST /api/v1/sales`. Cashier session and Sanctum token are never modified or replaced.
- **Role Verification:** Business Owners can override directly. Branch Managers require `override sale price` permission and assignment to the sale branch. Cashiers cannot self-approve.
- **Rate Limiting:** Failed approval attempts are throttled by `RateLimiter`: key = `price_override:{$businessId}:{$user->id}`, maximum 5 failed attempts, decay period 300 seconds (5 minutes). `RateLimiter::hit()` occurs strictly on failed credential/authorization attempts and is cleared via `RateLimiter::clear()` upon successful authorization. Exceeding the limit throws a `ValidationException` (HTTP 422) with retry-after seconds.
- **Dual-Actor Audit:** Audit logs capture both the counter operator (`actual_sale_actor_user_id`) and the approver (`override_authorized_by_user_id`), emitting canonical `sale.price_override` and compatibility `updated` (`action=price_override`) audit events. Approver credentials are never stored or logged. Zero financial transaction or sale duplication.

### 4.4 Quick Customer Onboarding (SRT-GAP-009)
- **Counter Preservation:** In-page popup creates customer without route navigation or active cart loss.
- **Strict Scope:** Collects Name, Primary Phone, and optional Secondary Phone. Strictly excludes opening balance and credit limit setup.
- **Phone Normalization:** Full Pakistani phone format normalization (`normalizePakistaniPhone`) ensures `03XXXXXXXXX` and `+923XXXXXXXXX` are evaluated with exact equality. Partial 7-digit suffix matching is completely absent.
- **Offline Compatibility:** Reuses client UUID temp ID preprocessing path for synchronization.

### 4.5 Return Workflow & Refund Settlement (SRT-GAP-010)
- **Pessimistic Concurrency:** Uses `lockForUpdate` on `SaleItem` to guarantee return quantity does not exceed sold quantity minus prior returns.
- **Restock Condition:** `is_restockable = true` restores sellable stock via `InventoryService::receiveStock`. `is_restockable = false` preserves stock unchanged and records damaged reason.
- **Economic Return vs Settlement:** `SaleReturn` records economic obligation. Khata debt reduction is prioritized first.
- **Residual Physical Refund:** If return value exceeds Khata debt, the remaining residual obligation is settled to an authorized financial account via `POST /api/v1/sale-returns/{id}/settle-refund`.
- **Duplicate Protection:** Second settlement attempts are blocked with HTTP 422. Outflows and ledger movements are created exactly once.

### 4.6 Thermal Receipts & Barcode Label Sheets (SRT-GAP-011)
- **Paper Width Scope:** `DEVICE_LOCAL` (`localStorage`). Supports 58mm (compact 196px width) and 80mm (standard 290px width) layouts.
- **Canonical Return Policy:** Sourced from `business.settings.receipt_settings.return_policy` / `receipt_footer` via canonical `/settings` API. Invented 7-day default policy removed.
- **Invoice Barcode:** Code 128 SVG barcode encodes canonical `invoice_number` for scanner lookup.
- **Reprint Immutability:** Historical reprints are read-only, generating zero new transactions or stock adjustments.
- **Barcode Label Tool:** ReceiptsView provides a catalog selection tool to preview and print A4 label sticker grids using existing product barcodes without cost/margin exposure.

---

## 5. Test Evidence & Verification Results

### 5.1 Targeted Phase-4 Feature Suite
Command: `php artisan test tests/Feature/Phase4SaleFlowTest.php`
- **Tests Total:** 20
- **Passed:** 20
- **Assertions:** 96
- **Failures:** 0
- **Errors:** 0
- **Duration:** 4.66s

### 5.2 Complete Backend Regression Suite
Command: `php artisan test`
- **Tests Total:** 433
- **Passed:** 433
- **Assertions:** 1,952
- **Failures:** 0
- **Errors:** 0
- **Duration:** 59.71s

### 5.3 Frontend Source Contract Verifier
Command: `php scratch/verify_phase4_prompt2_frontend.php`
- **Total Checks:** 41
- **Passed:** 41
- **Failed:** 0
- **Result:** **PASS (100%)**

### 5.4 Frontend Production Build
Command: `npm run build`
- **Tool:** Vite v8.2.1
- **Modules Transformed:** 170
- **Duration:** 1.09s
- **Errors:** 0

---

## 6. Prompt-3 Change Control & Invariants

- **Prompt-3 Intended Production Changes:** 0
- **Prompt-3 Actual Production Files Changed:** 1 (`api/app/Services/SaleService.php`)
- **Prompt-3 Authorized In-Manifest Corrections:** 1
- **Prompt-3 Unexpected Production Changes:** 0
- **Correction Detail:** During initial regression execution, `Phase9TransactionAuditTest` failed on line 224 because it queried `ActivityLog` with `event='updated'` and `properties->action='price_override'`, whereas Phase 4 emitted `event='sale.price_override'`. Under Section 67 change control, `SaleService.php` was updated to emit both canonical `sale.price_override` and compatibility `updated` audit events sharing identical properties. Financial transactions duplicated: 0; sale records duplicated: 0; credential leakage: 0.
- **Main Store / Primary Branch Invariant:** Primary branch is recognized through canonical primary-store semantics without database ID assumptions (Hardcoded Main Store ID = 1: NO).
- **Verification:** Both `Phase9TransactionAuditTest` (8/8 passed, 66 assertions) and `Phase4SaleFlowTest` (20/20 passed, 96 assertions) pass completely. Full regression passed: 433 passed, 1952 assertions, 0 failures.

---

## 7. Risk Closure & Phase 5 Boundaries

All 16 Phase-4 risks in `scratch/phase4_risk_registry.json` are **MITIGATED** (open Phase-4 risks = 0).

### Strict Boundary Against Deferred Capabilities:
- **Phase 5 (Next Authorized):** Alternate barcode indexing per SKU, fractional loose-unit pricing, wholesale pricing tiers, gross margin indicators, stock reorder alerts, landed cost freight, and supplier price history.
- **Phase 6:** Shift session day-open float, day-end cash drawer close, and small retail net profit reporting.
- **Phase 7:** View-As branch preview, inter-branch stock transfers, and multi-branch comparison matrices.
- **Phase 8:** Windows/Tauri desktop distribution and direct ESC/POS spooler bypass.

---

## 8. Final Phase Certification Decision

**PHASE 4 — SALES / POS / CUSTOMERS / KHATA / RETURNS IS FULLY CERTIFIED AND PERMANENTLY FROZEN.**

- All 6 Phase-4 root gaps certified closed.
- 0 route additions, 0 database schema changes, 0 migrations, 0 IndexedDB schema modifications.
- 100% test pass rate across targeted and full regression test suites.
- Next authorized phase: **Phase 5 — Products / Pricing / Stock / Purchases / Suppliers**.
