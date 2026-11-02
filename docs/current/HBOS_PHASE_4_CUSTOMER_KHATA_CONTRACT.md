# HBOS Phase 4 — Customer & Khata Contract
**Program:** SkyraSoft HBOS Small Retail Transformation Program  
**Phase:** 4 (Sales / POS / Customers / Khata / Returns)  
**Document Status:** FROZEN DESIGN CONTRACT  

---

## 1. POS Quick-Add Customer Workflow (SRT-GAP-009 / REQ-0011)
To prevent cart abandonment when an unrecorded customer requests credit or wishes to be registered, POS features an in-counter modal:
- **Zero Cart Disruption:** Opening, typing in, and submitting the modal does not clear or alter `store.cart`.
- **Minimum POS Fields (Strict Counter Flow):**
  - Name: Required string (e.g. "Muhammad Usman")
  - Primary Phone: Required Pakistani mobile number normalized to standard formats:
    - Standard 11-digit format: `03XXXXXXXXX` (11 numeric digits)
    - International 13-character format: `+923XXXXXXXXX` (13 characters including leading `+92`)
  - Secondary Phone: Optional second contact or shop landline (`customers.secondary_phone`)
- **Exclusion of Financial Mutation Setup:** Opening Balance, Credit Limit, and Address are **strictly excluded** from the POS quick-add popup. Opening balance mutations cannot be performed casually by a Cashier at the counter (`financial_mutation_without_explicit_contract = 0`). Financial parameters belong exclusively to full Customer Profile management.
- **Duplicate Detection & Authority:** Frontend checks cached customer list as immediate UX guidance, offering a quick-select button. However, **Backend remains authoritative** for tenant-scoped phone validation (`where('business_id', $businessId)`).
- **Auto-Selection:** Upon successful save, the modal closes, updates `useCustomersStore().customers`, and automatically sets the customer as selected on the active order.

---

## 2. Customer Scope & Multi-Branch Architecture
- **Tenant Scope:** Customers belong to the active business (`business_id`). A customer registered in Branch 1 is visible across all branches of the same business.
- **Branch Exposure:** Branch Managers see the business-wide customer directory but their financial dashboard displays `branch_outstanding` exposure derived only from sales and payments within their assigned branch.
- **Business Owner Universal View:** The Owner sees the consolidated balance across all branches.

---

## 3. Khata Receivable Ledger & Mathematical Invariant (REQ-0012)
Khata operates as a dedicated customer receivable ledger tracking sale dues, customer payments, returns, payment reversals, and atomic balance recalculations:
```
raw_account_balance = opening_balance + SUM(effective_due across non-cancelled sales) - SUM(posted customer payments)
```
Where:
```
effective_due = max(0.00, (sale.total - refunds) - sale.paid_amount)
```
- **Outstanding Receivable:** `max(raw_account_balance, 0.00)` (Customer owes store).
- **Customer Advance / Credit:** `max(-raw_account_balance, 0.00)` (Store owes customer).
- **Atomic Recalculation:** Every credit sale, return, and customer payment triggers `CustomerAccountService::recalculateBalance` to ensure database cache synchronization without manual ledger reconciliations.
