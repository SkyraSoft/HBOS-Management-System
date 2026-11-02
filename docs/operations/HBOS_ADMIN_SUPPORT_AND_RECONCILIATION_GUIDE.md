# SkyraSoft HBOS Management System — Administrator Support & Historical Data Reconciliation Guide
## Non-Destructive Maintenance Procedures, CLI Diagnostics & Historical Truth Preservation

**Document Version:** 10.0  
**Target Audience:** System Administrators, Technical Support Engineers, Senior Financial Controllers  
**Operating Policy:** Zero Destructive Edits — Strict Historical Truth Preservation  

---

## 1. Core Principles of Historical Data Integrity

In enterprise ERP and POS systems, historical records must never be fabricated, mutated, or deleted to create false mathematical neatness. HBOS strictly adheres to the following principles:

1. **New Transactions are Authoritative & Complete:** All post-Phase-9 transactions capture immutable snapshots (`sale_items`), normalized split payments (`sale_payments`), strict expense account links (`financial_account_id`), and precise landed costs.
2. **Historical Data Gaps are Acknowledged:** Transactions recorded prior to Phase-9 migrations have known structural limits (e.g. NULL snapshots, unallocated expense accounts).
3. **Reconciliation Tools are Non-Destructive:** All Artisan maintenance commands default to `--dry-run`, never infer speculative facts, and flag backfilled data as approximate.
4. **Discrepancies Require Compensating Entries:** When historical ledger balances differ from physical statements, support staff post explicit compensating adjustment entries with audit notes rather than editing original posted rows.

---

## 2. Historical Data Repair Commands & Audit Specifications

HBOS provides 5 specialized Artisan commands located in `api/app/Console/Commands/`:

### 1. `REPAIR-001`: Historical Sale Item Snapshot Backfill
- **Source File:** `api/app/Console/Commands/BackfillSaleItemSnapshotsCommand.php`
- **Related GAP:** `GAP-001 / MIG-001 (LIM-001)`
- **Problem Addressed:** Sales created prior to migration MIG-001 contain NULL values for `product_name`, `sku`, `unit_cost`, and `barcode` in `sale_items`.
- **Safety Mechanism:** Reads current master product data and applies them to legacy sale items with NULL snapshots. Defaults to safe DRY-RUN inspection. Logs approximation provenance in command output without altering original sales totals or financial records.
- **Invocation Examples:**
  - *Dry-Run Inspection (Default):* `php artisan hbos:backfill-sale-item-snapshots`
  - *Execute Live Apply:* `php artisan hbos:backfill-sale-item-snapshots --apply`

### 2. `REPAIR-002`: Historical Single-Tender Payment Backfill
- **Source File:** `api/app/Console/Commands/BackfillSalePaymentsCommand.php`
- **Related GAP:** `GAP-003 / MIG-003`
- **Problem Addressed:** Normalizes legacy sales created before the `sale_payments` table existed by mapping their single-tender payment method into a normalized payment record.
- **Safety Mechanism:** Deterministically migrates legacy `payment_method` and `paid_amount` into `sale_payments` without data loss or amount modification. Defaults to DRY-RUN mode.
- **Invocation Examples:**
  - *Dry-Run Inspection (Default):* `php artisan hbos:backfill-sale-payments`
  - *Execute Live Apply:* `php artisan hbos:backfill-sale-payments --apply`

### 3. `REPAIR-003`: Legacy NULL Expense Account Audit & Reclassification
- **Source File:** `api/app/Console/Commands/RepairNullExpenseAccountsCommand.php`
- **Related GAP:** `GAP-013 / MIG-004 (LIM-003)`
- **Problem Addressed:** Audits historical expense rows created prior to mandatory financial account validation.
- **Safety Mechanism:** Generates an itemized reconciliation log of legacy NULL expenses. Does NOT guess or fabricate arbitrary bank accounts. Allocates unassigned expenses to an explicit holding account `UNALLOCATED_HISTORICAL_EXPENSES` upon apply.
- **Invocation Examples:**
  - *Dry-Run Inspection (Default):* `php artisan hbos:repair-null-expense-accounts`
  - *Execute Live Apply:* `php artisan hbos:repair-null-expense-accounts --apply`

### 4. `REPAIR-004`: Orphan Financial Movement Reconciliation
- **Source File:** `api/app/Console/Commands/ReconcileFinancialMovementsCommand.php`
- **Related GAP:** `GAP-014 (LIM-004)`
- **Problem Addressed:** Identifies historical account movements for soft-deleted expenses lacking compensating entries.
- **Safety Mechanism:** Flags orphan records for accountant review. Creates compensating void movements upon apply to balance ledgers; never executes destructive `DELETE` queries.
- **Invocation Examples:**
  - *Dry-Run Inspection (Default):* `php artisan hbos:reconcile-financial-movements`
  - *Execute Live Apply:* `php artisan hbos:reconcile-financial-movements --apply`

### 5. `REPAIR-005`: Customer Khata Ledger Discrepancy Audit
- **Source File:** `api/app/Console/Commands/AuditKhataLedgerDiscrepanciesCommand.php`
- **Related GAP:** `GAP-008 (LIM-002)`
- **Problem Addressed:** Audits mathematical variance between `customers.balance` and cumulative double-entry movements in `customer_ledgers`.
- **Safety Mechanism:** Generates a read-only discrepancy report detailing the exact variance for each customer. Requires manual review with the customer before posting an explicit compensating adjustment via `CustomerKhataView.vue`.
- **Invocation Examples:**
  - *Read-Only Diagnostic Audit:* `php artisan hbos:audit-khata-ledger-discrepancies`

---

## 3. Practical Troubleshooting Guide

| Issue / Symptom | Likely Cause | Safe Operator / Support Action |
| :--- | :--- | :--- |
| **Login rejected: "Inactive Account"** | User profile deactivated by administrator (`users.is_active = false`). | Business Owner must navigate to Users (/users) and click 'Activate User' (SOP-025). |
| **Product search yields zero results** | Product inactive or not synced to local Dexie IndexedDB cache. | Click 'Sync / Refresh Catalog' on POS header or verify product status in ProductsView. |
| **POS Checkout blocked: "Insufficient Stock"** | Branch stock quantity is zero or lower than requested cart quantity. | Perform Stock Adjustment (SOP-013) or receive inventory transfer from central branch. |
| **Sale submission returns 409 Conflict** | Concurrency race condition; another POS register sold the same stock. | Server locks stock pessimistically; refresh cart to view updated remaining quantity. |
| **Purchase Cancellation rejected** | Received items have already been sold or transferred to another branch. | Purchase cancellation blocked to prevent negative stock overdraft; issue Supplier Return (SOP-017). |
| **Expense Submission rejected: 422 Unprocessable** | Funding financial account was omitted in expense form. | Select valid Cash Register or Bank Account (mandatory per GAP-013). |
| **Device Pairing rejected: "Invalid Subnet"** | Tablet POS connected to a different Wi-Fi subnet or public WAN IP. | Ensure both devices are on the same local Wi-Fi router (RFC1918 LAN range, e.g. 192.168.1.x). |
| **Thermal Printer fails after checkout** | Physical paper jam, cable disconnected, or printer power loss. | Do NOT re-enter the sale. Fix paper roll and click 'Reprint' in Sales History (SOP-031). |
| **Offline Sync shows "STOCK_CONFLICT"** | Multiple offline tablets oversold the same item while disconnected. | Manager opens Sync Manager (/sync-manager), reviews deficit, and records compensating stock adjustment. |
| **Khata Balance does not match expected debt** | Historical pre-remediation discrepancy or unrecorded manual payment. | Run `php artisan hbos:audit-khata-ledger-discrepancies` and post verified compensating entry (SOP-034). |

---

## 4. Administrative Golden Safety Rules

1. **Always Use Dry-Run First:** Never execute a live apply command without reviewing the default dry-run summary report.
2. **Never Execute Direct Database SQL Updates:** Modifying balances directly via `UPDATE` bypasses double-entry movement triggers and corrupts audit trails.
3. **Preserve Historical Provenance:** Acknowledge historical NULL snapshots without fabricating speculative transaction history.
4. **Never Guess Funding Sources:** Legacy unallocated expenses must be recorded with holding accounts rather than guessing arbitrary company bank accounts.
