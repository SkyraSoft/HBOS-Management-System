# SKYRASOFT HBOS — SCENARIO A FINANCIAL RECONCILIATION PROOF
**Document Reference:** `docs/release/HBOS_SCENARIO_A_FINANCIAL_RECONCILIATION_PROOF.md`  
**Derivation Source:** `Tests\Feature\SmallRetailScenariosAcceptanceTest::test_scenario_a_owner_complete_retail_day`  
**Verification Target:** Cash Drawer Day-End Balancing, Customer/Supplier Khata Balances & Net Profitability  
**Status:** **MATHEMATICALLY & TRANSACTIONALLY PROVEN (0.00 DISCREPANCY)**

---

## 1. RECTIFICATION OF ARITHMETIC DOCUMENTATION DEFECT

In the prior executive summary, the formula for expected closing cash was typed as:
$$\text{Incorrect written text: } 5,000 + 875 + 500 - 1,000 - 300 = 3,875 \quad (\text{Mathematically } 5,075)$$

This was a documentation error that omitted the **PKR 1,200 cash-paid purchase downpayment** executed in Step 5 of the actual test code.

### Corrected, Fully Reconciled Mathematical Formula:
$$\text{Expected Closing Cash} = \text{Opening Float} - \text{Cash Purchase Downpayment} + \text{Cash Sales} + \text{Khata Collections} - \text{Supplier Settlement} - \text{Operating Expenses}$$
$$\mathbf{5,000.00 - 1,200.00 + 875.00 + 500.00 - 1,000.00 - 300.00 = 3,875.00\text{ PKR}}$$

The physical cash count entered was exactly **PKR 3,875.00**, resulting in a drawer discrepancy of **PKR 0.00**.

---

## 2. TRANSACTION-BY-TRANSACTION LEDGER & ACCOUNT MOVEMENTS

The following audit trail details every discrete transaction, actor, account movement, and running balance generated during the test execution in an isolated SQLite database:

| Step | Action & Route | Description | Flow | Amount | Running Drawer Balance | Counterparty / Target Balance |
| :---: | :--- | :--- | :---: | :---: | :---: | :--- |
| **1** | `POST /api/v1/cash-register-sessions/open` | Owner opens register with morning float | INFLOW | +5,000.00 | **PKR 5,000.00** | Session #1 Open |
| **2** | `POST /api/v1/purchases` | Purchase PO-2026-001 (10 kg Rice @ 220 = 2,200). Paid 1,200 cash, 1,000 due. | OUTFLOW | -1,200.00 | **PKR 3,800.00** | Punjab Grains Payable: PKR 1,000.00 |
| **3** | `POST /api/v1/sales` | Cash Sale: 2.500 kg Rice @ 350.00 = 875.00 | INFLOW | +875.00 | **PKR 4,675.00** | Rice Stock: 10.5 kg $\to$ 8.0 kg |
| **4** | `POST /api/v1/sales` | Khata Credit Sale: 2.000 kg Rice @ 350.00 = 700.00 on credit | *(None)* | 0.00 | **PKR 4,675.00** | Chaudhry Akram Khata: PKR 700.00 |
| **5** | `POST /api/v1/customers/{id}/payments` | Khata Repayment: Customer pays 500.00 cash | INFLOW | +500.00 | **PKR 5,175.00** | Chaudhry Akram Khata: PKR 200.00 |
| **6** | `POST /api/v1/suppliers/{id}/payments` | Supplier Settlement: Pay remaining 1,000.00 to Punjab Grains | OUTFLOW | -1,000.00 | **PKR 4,175.00** | Punjab Grains Payable: PKR 0.00 (Cleared) |
| **7** | `POST /api/v1/expenses` | Operating Expense: Shop refreshments / tea | OUTFLOW | -300.00 | **PKR 3,875.00** | Category: Refreshments |
| **8** | `POST /api/v1/cash-register-sessions/close` | Owner counts physical drawer: 3,875.00 entered | RECONCILE | 0.00 | **PKR 3,875.00** | **Variance: 0.00 PKR (Exact)** |

---

## 3. COMPREHENSIVE FINANCIAL SUB-LEDGERS RECONCILIATION

### 3.1. Customer Khata Ledger (Chaudhry Akram)
* **Opening Balance:** PKR 0.00
* **Credit Sale Added (Step 4):** +PKR 700.00
* **Cash Debt Repayment (Step 5):** -PKR 500.00
* **Ending Receivable Balance:** $\mathbf{200.00\text{ PKR}}$
* *Audit Verification:* Matched in database (`Customer::find($custId)->current_balance == 200.00`).

### 3.2. Supplier Payables Ledger (Punjab Grains Wholesale)
* **Opening Balance:** PKR 0.00
* **Purchase Bill Total (Step 2):** +PKR 2,200.00
* **Cash Paid Downpayment (Step 2):** -PKR 1,200.00
* **Supplier Settlement Payment (Step 6):** -PKR 1,000.00
* **Ending Payable Balance:** $\mathbf{0.00\text{ PKR}}$ (Fully cleared)
* *Audit Verification:* Matched in database (`Supplier::find($suppId)->current_payable == 0.00`).

### 3.3. Sales Revenue, COGS & Realized Profitability
* **Cash Sales Revenue:** PKR 875.00
* **Credit Khata Sales Revenue:** PKR 700.00
* **Gross Revenue (Net Sales):** $\mathbf{1,575.00\text{ PKR}}$
* *Audit Verification:* Asserted by `GET /api/v1/reports/profitability`:
  `$this->assertEquals(1575.00, (float)$reportResp->json('net_sales'))`.

---

## 4. INVENTORY STOCK CONSERVATION PROOF

* **Initial Rice Stock (Fractional Loose):** 10.500 kg
* **Purchase PO-2026-001 (Packaged inventory):** 10.000 units
* **Cash Sale 1 Deducted:** -2.500 kg
* **Khata Sale 2 Deducted:** -2.000 kg
* **Total Fractional Stock Consumed:** 4.500 kg
* **Remaining Fractional Rice Stock:** $10.500\text{ kg} - 4.500\text{ kg} = \mathbf{6.000\text{ kg}}$
* *Audit Verification:* Decimal precision (3 decimal places) strictly preserved without integer truncation.
