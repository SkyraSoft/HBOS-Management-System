# SKYRASOFT HBOS — PHASE 9 REMEDIATION BLUEPRINT
## Authoritative Architecture, Execution Waves & Verification Specification

## 1. Remediation Strategy Overview
The Phase 9 Remediation program executes all code, database schema, transactional integrity, security, offline, hardware, and UI/UX corrections identified across the 31 active root defects (`GAP-001` through `GAP-031`).

Execution is structured into **4 strictly ordered sequential waves**, ensuring that foundational schema, tenant isolation, and core accounting ledger invariants are fully operational and verified before higher-level modules are touched.

---

## 2. Four Implementation Waves

### Wave 1: Foundational Schema, Security Policies & Financial Allocation
- **Foundation Blockers**: 10
- **GAPs Addressed (10)**: `GAP-001`, `GAP-002`, `GAP-003`, `GAP-013`, `GAP-015`, `GAP-016`, `GAP-017`, `GAP-018`, `GAP-019`, `GAP-020`
- **Database Migrations (4)**:
  - `MIG-001`: Add point-in-time snapshot columns (`barcode`, `sku`, `product_name`, `unit_cost`) to `sale_items`.
  - `MIG-002`: Add `wholesale_price` to `products`.
  - `MIG-003`: Create `sale_payments` table for multi-tender split payment persistence.
  - `MIG-004`: Enforce non-null `financial_account_id` on `expenses` table.
- **Data Repairs (4)**:
  - `REPAIR-001`: Convenience backfill from product master (`APPROXIMATE_NON_HISTORICAL_BACKFILL`).
  - `REPAIR-002`: Deterministic backfill of historical `sales` payment methods into `sale_payments`.
  - `REPAIR-003`: Manual reconciliation allocation for legacy NULL account expenses (`MANUAL_RECONCILIATION_REQUIRED`).
  - `REPAIR-004`: Compensating reversal movements for legacy deleted expenses (`COMPENSATING_MOVEMENT_OR_RECONCILIATION`).
- **Core Objectives**:
  - Establish multi-tender payment breakdown schema.
  - Implement server-side sale total recalculation and coupon validation.
  - Enforce backend authorization policies on destructive actions.
  - Fix tenant resolution ordering in implicit route model binding.
  - Apply row-level pessimistic locking (`lockForUpdate`) on GRN receiving.

---

### Wave 2: Inventory Valuation, State Guards & AR Immutability
- **Foundation Blockers**: 0
- **GAPs Addressed (6)**: `GAP-004`, `GAP-006`, `GAP-008`, `GAP-009`, `GAP-010`, `GAP-014`
- **Database Migrations (1)**:
  - `MIG-007`: Add `is_restockable` and `return_reason` to `sale_return_items`.
- **Data Repairs (1)**:
  - `REPAIR-005`: Read-only Khata balance discrepancy audit schedule.
- **Core Objectives**:
  - Route inventory stock adjustment discrepancies to dedicated Inventory Valuation financial accounts.
  - Enforce immutable customer ID binding on posted sales (prohibit editing `customer_id` without full reversal).
  - Add remaining inventory validation before purchase order cancellation.
  - Implement cascading atomic movement reversal on expense deletion.

---

### Wave 3: Procurement Landed Cost, Supplier Returns & Payroll HR
- **Foundation Blockers**: 0
- **GAPs Addressed (5)**: `GAP-005`, `GAP-007`, `GAP-011`, `GAP-012`, `GAP-029`
- **Database Migrations (4)**:
  - `MIG-005`: Add `freight_amount` and `landed_cost_allocated` to `purchases`.
  - `MIG-006`: Add `transfer_status` to `inventory_movements`.
  - `MIG-008`: Create `supplier_returns` table for debit note and AP deduction workflows.
  - `MIG-009`: Add `salary_amount`, `payment_cycle`, and `next_payment_date` to `users` table.
- **Data Repairs**: None.
- **Core Objectives**:
  - Capitalize freight and landed costs into moving average unit costs.
  - Introduce explicit two-step inter-branch stock transfer workflow (Transit/Shipped -> Received).
  - Implement full Supplier Return debit note lifecycle with stock deduction and supplier balance reduction.
  - Implement atomic supplier payment rollback with AP re-accrual.
  - Persist employee wage, salary, and payment schedule in user management.

---

### Wave 4: Offline Queuing, POS Print Timing, UI Safety & Master Field Hardening
- **Foundation Blockers**: 0
- **GAPs Addressed (10)**: `GAP-021`, `GAP-022`, `GAP-023`, `GAP-024`, `GAP-025`, `GAP-026`, `GAP-027`, `GAP-028`, `GAP-030`, `GAP-031`
- **Database Migrations (2)**:
  - `MIG-010`: Add `secondary_phone` to `customers` table.
  - `MIG-011`: Add `monthly_budget` to `expense_categories` table.
- **Data Repairs**: None.
- **Core Objectives**:
  - Reorder POS print sequence: trigger thermal receipt only after successful backend commit or validated local outbox enqueue.
  - Implement local optimistic stock reservation pool for multi-device offline POS checkouts.
  - Implement persistent temporary UUID -> server ID mapping graph in IndexedDB outbox queue.
  - Restrict P2P device pairing to private LAN subnets with static IP validation.
  - Add explicit confirmation modal wrappers on destructive actions.
  - Display persistent active operating branch and shift indicator on POS header.
  - Persist customer secondary phone and expense category monthly budget caps.

---

## 3. Database Migration Specification Matrix (11 Migrations)

| Migration ID | Target Table | Operation | Columns / DDL Change | Target GAPs | Deployment Wave |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `MIG-001` | `sale_items` | ADD_COLUMNS | `barcode`, `sku`, `product_name`, `unit_cost` | `GAP-001` | WAVE_1 |
| `MIG-002` | `products` | ADD_COLUMN | `wholesale_price` (decimal 15,2, nullable) | `GAP-002` | WAVE_1 |
| `MIG-003` | `sale_payments` | CREATE_TABLE | `id`, `business_id`, `sale_id`, `payment_method`, `amount`, `financial_account_id`, `reference_number` | `GAP-003` | WAVE_1 |
| `MIG-004` | `expenses` | ALTER_NULLABILITY | `financial_account_id` (NOT NULL) | `GAP-013` | WAVE_1 |
| `MIG-005` | `purchases` | ADD_COLUMNS | `freight_amount`, `landed_cost_allocated` | `GAP-005` | WAVE_3 |
| `MIG-006` | `inventory_movements` | ADD_COLUMN | `transfer_status` (string, nullable) | `GAP-007` | WAVE_3 |
| `MIG-007` | `sale_return_items` | ADD_COLUMNS | `is_restockable`, `return_reason` | `GAP-010` | WAVE_2 |
| `MIG-008` | `supplier_returns` | CREATE_TABLE | Complete supplier debit note schema | `GAP-011` | WAVE_3 |
| `MIG-009` | `users` | ADD_COLUMNS | `salary_amount`, `payment_cycle`, `next_payment_date` | `GAP-029` | WAVE_3 |
| `MIG-010` | `customers` | ADD_COLUMN | `secondary_phone` (string, nullable) | `GAP-030` | WAVE_4 |
| `MIG-011` | `expense_categories` | ADD_COLUMN | `monthly_budget` (decimal 15,2, nullable) | `GAP-031` | WAVE_4 |

---

## 4. Implementation Dependency Directives (7 Proven Dependencies)

| Dependency ID | Blocking GAP | Dependent GAP | Prerequisite Architecture | Dependency Type |
| :--- | :--- | :--- | :--- | :--- |
| `DEP-001` | `GAP-013` (Wave 1) | `GAP-003` (Wave 1) | AccountMovement ledger foundations for multi-tender split payments | `SHARED_REMEDIATION` |
| `DEP-002` | `GAP-013` (Wave 1) | `GAP-004` (Wave 2) | Designated Inventory Valuation accounts for stock adjustments | `DATA_MODEL_PREREQUISITE` |
| `DEP-003` | `GAP-013` (Wave 1) | `GAP-012` (Wave 3) | Financial account balance rollback methods for supplier payment reversals | `SERVICE_PREREQUISITE` |
| `DEP-004` | `GAP-001` (Wave 1) | `GAP-021` (Wave 4) | Line item snapshot schema for thermal receipt formatting | `SCHEMA_PREREQUISITE` |
| `DEP-005` | `GAP-003` (Wave 1) | `GAP-021` (Wave 4) | Multi-tender breakdown schema for receipt tender breakdown lines | `UI_PREREQUISITE` |
| `DEP-006` | `GAP-019` (Wave 1) | `GAP-009` (Wave 2) | Idempotency middleware infrastructure for payment reversal handlers | `SHARED_REMEDIATION` |
| `DEP-007` | `GAP-012` (Wave 3) | `GAP-011` (Wave 3) | AP balance calculation methods shared between returns and reversals | `SHARED_REMEDIATION` |