# SKYRASOFT HBOS — COMPLETE FINANCIAL RECONCILIATION & ACCOUNTING INVARIANTS
**Document Reference:** `docs/release/HBOS_COMPLETE_FINANCIAL_RECONCILIATION.md`  
**Stage:** Stage 2 — Complete Real-Business Operations, Security, Financial Integrity & Offline Reliability  
**Checkpoints Covered:** Checkpoint 2.2, Checkpoint 2.4  
**Status:** AUTHORITATIVE AUDITED SPECIFICATION & RECONCILIATION PROOF

---

## 1. Executive Summary

HBOS enforces a strict double-entry and ledger-backed financial integrity model tailored for Pakistani independent retailers. Commercial transactions never rely on floating-point arithmetic, simulated totals, or arbitrary balance overrides. Every financial mutation is backed by immutable account movements, audit logs, and deterministic mathematical proofs.

---

## 2. Invariant 1: Supplier Purchases and Cash Outflows

### 2.1 The Accounting Rule
A cash-funded purchase or supplier settlement must produce an identical cash-account outflow. If the account outflow fails or the cash drawer has insufficient funds, the supplier payment transaction must fail closed and roll back atomically.

### 2.2 Mathematical Proof
$$\Delta \text{Supplier Liability} = - \text{Payment Amount}$$
$$\Delta \text{Drawer Balance} = - \text{Payment Amount}$$

Using the authoritative fixture (`ThirdCodexDefectRepairsTest.php` and `SmallRetailScenariosAcceptanceTest.php`):
- **Initial Drawer Float:** PKR 5,000.00
- **Purchase Order #1 (Total PKR 2,200.00):**
  - Paid at PO creation: PKR 1,200.00 cash
  - Remaining liability: PKR 1,000.00 due
  - **Account Movement #1:** Outflow PKR 1,200.00 (`reference_type: Purchase`, `reference_id: #1`)
  - **Drawer Balance:** $\text{PKR } 5,000.00 - 1,200.00 = \text{PKR } 3,800.00$
- **Supplier Settlement #1:**
  - Paid remaining due: PKR 1,000.00 cash
  - **Account Movement #2:** Outflow PKR 1,000.00 (`reference_type: SupplierPayment`, `reference_id: #1`)
  - **Drawer Balance:** $\text{PKR } 3,800.00 - 1,000.00 = \text{PKR } 2,800.00$
  - **Supplier Due:** $\text{PKR } 0.00$
- **Reversal Safety:**
  - If Supplier Settlement #1 is reversed, a compensating inflow movement of PKR 1,000.00 is posted.
  - Reversal restores purchase `due_amount` to PKR 1,000.00.
  - Drawer balance restores to PKR 3,800.00.
  - Historical records remain completely preserved (zero row deletion).

---

## 3. Invariant 2: Cash Day Closing and Reconciliation Formula

### 3.1 The Canonical Equation
$$\text{Expected Drawer} = \text{Opening Float} + \sum \text{Cash Inflows} - \sum \text{Cash Outflows}$$

Where:
- $\sum \text{Cash Inflows} = \text{Cash Sales (Live \& Synced Offline)} + \text{Customer Khata Collections} + \text{Capital Injections} + \text{Cash Transfers In}$
- $\sum \text{Cash Outflows} = \text{Supplier Cash Purchases} + \text{Supplier Settlements} + \text{Operating Expenses} + \text{Cash Refunds} + \text{Owner Drawings}$

### 3.2 Canonical Scenario A Day Closing Reconciliation
| Movement / Event | Type | Inflow (PKR) | Outflow (PKR) | Running Drawer (PKR) |
|---|---|---|---|---|
| Opening Float | Float Session Base | 5,000.00 | — | 5,000.00 |
| Purchase PO-2026-001 | Purchase Outflow | — | 1,200.00 | 3,800.00 |
| POS Cash Sale (Rice 2.5 kg) | Cash Sale Inflow | 875.00 | — | 4,675.00 |
| Customer Khata Collection | Customer Repayment | 500.00 | — | 5,175.00 |
| Supplier Settlement (Remaining PO) | Payout Outflow | — | 1,000.00 | 4,175.00 |
| Operating Expense (Refreshments) | Expense Outflow | — | 300.00 | 3,875.00 |
| **Total Session Movements** | | **1,375.00** | **2,500.00** | **Net: -1,125.00** |
| **Expected Cash at Close** | | | | **3,875.00** |

- **Counted Cash:** PKR 3,875.00
- **System Variance:** PKR 0.00 (Balanced Close)
- **Status:** Closed and locked. Immutable forever.

### 3.3 Blind Close and Variance Safeguards
1. **Blind Close:** Cashier/Salesperson entering day-close counts physical notes without seeing expected totals.
2. **Discrepancy Control:** If Counted $\ne$ Expected, a mandatory `variance_reason` is enforced.
3. **No Fabricated Balancing Movements:** If a shortage occurs, it is recorded as an auditable loss; system never invents fake cash inflows.

---

## 4. Invariant 3: Customer Khata, Credit Limits & Overpayments

### 4.1 Khata Accounting Invariant
- **Credit Sale:**
  $$\Delta \text{Customer Receivable} = + \text{Invoice Total}$$
  $$\Delta \text{Cash Drawer} = 0.00$$
- **Customer Repayment:**
  $$\Delta \text{Customer Receivable} = - \text{Repayment Amount}$$
  $$\Delta \text{Cash Drawer} = + \text{Repayment Amount}$$

### 4.2 Protection Rules
- **Overpayment Rejection:** Repayments exceeding outstanding receivable balance are rejected with 422 Unprocessable Entity.
- **Credit Limit Gate:** POS checkout refuses Khata credit if current balance + cart total exceeds customer's authorized `credit_limit`.
- **Repayment Reversal:** When a mistaken customer payment is reversed:
  - Compensating outflow movement is posted to the drawer.
  - Customer balance is restored.
  - Original payment is marked `reversed` with reason and authoring actor.

---

## 5. Invariant 4: True Profitability & Small Retail Statement

### 5.1 Clear Separation of Cash vs Profit
A small retailer's cash drawer balance is not profit. HBOS strictly distinguishes:

$$\text{Gross Goods Sales} = \sum (\text{Sold Quantity} \times \text{Selling Price})$$
$$\text{Commercial Invoice Sales} = \text{Gross Goods Sales} - \text{Discounts} + \text{Tax}$$
$$\text{Net Sales (Revenue Basis)} = \text{Commercial Invoice Sales} - \text{Economic Returns}$$
$$\text{Gross COGS} = \sum (\text{Sold Quantity} \times \text{Snapshot Cost Price})$$
$$\text{Returned COGS} = \sum (\text{Restockable Returned Quantity} \times \text{Snapshot Cost Price})$$
$$\text{Net COGS} = \text{Gross COGS} - \text{Returned COGS}$$
$$\text{Gross Profit} = \text{Net Sales} - \text{Net COGS}$$
$$\text{Operating Profit (Net)} = \text{Gross Profit} - \text{Operating Expenses}$$

### 5.2 Damaged Goods Restocking Invariant
- **Restockable Return:** Returned to inventory; reverses COGS deduction.
- **Damaged Return:** Written off to damage; stock remains removed; COGS deduction is **NOT** reversed.

---

## 6. Verification Status

All invariants are mathematically verified in automated suites:
- `api/tests/Feature/ThirdCodexDefectRepairsTest.php` (9 tests, 108 assertions)
- `api/tests/Feature/CashRegisterSessionTest.php` (14 tests, 37 assertions)
- `api/tests/Feature/SmallRetailProfitabilityTest.php` (8 tests, 58 assertions)
- `api/tests/Feature/FinancialIntegrityCorrectionTest.php` (8 tests, 26 assertions)
- `api/tests/Feature/SmallRetailScenariosAcceptanceTest.php` (5 tests, 33 assertions)
