# HBOS Phase 4 — Purchases & Suppliers

## Frozen 10-Phase Roadmap
1. **Phase 1 — Multi-Tenancy, Branches, Users & RBAC** [COMPLETE]
2. **Phase 2 — Product Catalog & Master Data** [COMPLETE]
3. **Phase 3 — Inventory & Stock Ledger** [COMPLETE]
4. **Phase 4 — Purchases & Suppliers** [COMPLETE]
5. **Phase 5 — Sales & POS** [NOT STARTED]
6. **Phase 6 — Customers & Khata / Udhaar** [NOT STARTED]
7. **Phase 7 — Cash, Bank & Expenses** [NOT STARTED]
8. **Phase 8 — Dashboard & Reporting** [NOT STARTED]
9. **Phase 9 — Audit, Settings & System Governance** [NOT STARTED]
10. **Phase 10 — End-to-End Hardening, QA & Release Readiness** [NOT STARTED]

## Phase Objective
Establish a safe, connected Purchase and Supplier operating workflow within HBOS. A Purchase connects Supplier, Purchase Header, Purchase Items, Branch Stock Receipt via Phase 3 `InventoryService`, Supplier Balance/Payable tracking, and controlled Payment boundaries without violating multi-tenancy or bypassing inventory ledger rules.

## Scope
- Business-level Supplier master data management (`business_id`).
- Branch-level Purchase transactions (`business_id`, `branch_id`, `supplier_id`).
- Strict tenant and branch authorization for Purchase operations.
- Server-side calculation and validation of line item totals, subtotal, and total cost.
- Multi-item Purchase receipt strictly via Phase 3 `InventoryService::receiveStock()`.
- Controlled Purchase status lifecycle (`received`, `cancelled`).
- Controlled Purchase returns/cancellations strictly via Phase 3 `InventoryService::issueStock(type=purchase_return)`.
- Supplier payable balance tracking (`suppliers.balance` maintained by `SupplierBalanceService`).
- Dedicated Spatie permissions (`view suppliers`, `manage suppliers`, `view purchases`, `manage purchases`, `return purchases`, `record supplier payments`).
- Comprehensive Phase 4 automated integration & security test suites.

## Out of Scope
- POS / Sales workflows (Phase 5).
- Customer accounts & Khata (Phase 6).
- Full cash/bank account ledger & operational expense accounting (Phase 7).
- Advanced enterprise procurement (PO approvals, RFQs, vendor portals, complex tax compliance).
- Direct database mutation of `branch_inventories` or `products.stock` outside `InventoryService`.

## Prompt Status
- **Prompt 1/4 — Discovery / Audit / Preparation:** COMPLETE
- **Prompt 2/4 — Core Implementation:** COMPLETE
- **Prompt 3/4 — Integration / Security / Automated Testing:** COMPLETE
- **Prompt 4/4 — Final Verification / Phase Completion:** COMPLETE

---

## Prompt 1 Discovery Summary
Prompt 1 audited existing code and established that Supplier is Business-level master data (`business_id`), Purchase is Branch-level transaction data (`business_id`, `branch_id`), `PurchaseItem` stores historical cost snapshots, client totals were previously trusted, foreign tenant relationship IDs were unvalidated, posted purchase deletions previously erased historical rows, and development database contains 0 legacy purchase records.

---

## Prompt 3 — Integration / Security / Automated Testing Summary
Prompt 3 fixed paid/partially-paid purchase cancellation protection (rejecting direct cancellation with HTTP 422), restricted supplier payment recording strictly to Business Owner, normalized monetary precision with `round(val, 2)`, and added dedicated feature test suites.

---

## Prompt 4 — Final Verification & Phase Completion (Migration Drift Correction Executed)

### 1. Migration Drift Correction & Forward-Only Discipline
- **Issue Corrected:** Prompt 4 initially modified historical migration `2026_09_16_000000_update_purchases_and_suppliers_schema_for_phase_4.php` to add `opening_balance` and `purchase_id`.
- **Forward Migration Applied:**
  1. Restored `2026_09_16_000000` to its exact Prompt 2 executed state.
  2. Created new forward migration `2026_09_16_010000_add_supplier_reconciliation_provenance_for_phase_4.php` containing `suppliers.opening_balance` and `supplier_payments.purchase_id`.
  3. Verified both existing-database upgrade path (`php artisan migrate`) and fresh-install path (testing suite migrations) converge to the exact same schema.

### 2. Mathematical Reconciliation Inconsistency Resolved
- **Discrepancy Addressed:** Resolved potential double-subtraction ambiguity when reconciling `suppliers.balance` from source event rows.
- **Resolution & Provenance Mechanism:**
  1. Initial payments recorded at purchase creation set `purchase_id = $purchase->id`. Standalone supplier account payments recorded later set `purchase_id = null`.
  2. Added immutable `opening_balance` column to `suppliers` table to preserve historical account starting state.
  3. Defined two mathematically equivalent, fully auditable balance reconciliation formulas in `SupplierBalanceService`:
     - **Gross Total Formula:** `reconciled_balance = supplier.opening_balance + SUM(received_purchases.total) - SUM(all_supplier_payments.amount)`
     - **Net Due Formula:** `reconciled_balance = supplier.opening_balance + SUM(received_purchases.due_amount) - SUM(standalone_payments.amount [where purchase_id IS NULL])`
  4. Implemented `test_supplier_balance_reconciliation_formulas` in `SupplierBalanceTest.php` proving both formulas compute the exact same value as `suppliers.balance`.

### 2. Final Repository Re-Audit Findings
- No direct `Supplier.balance` writes outside `SupplierBalanceService` or initial creation.
- No `DELETE` routes erase posted `Purchase` rows (hard deletion rejected with HTTP 422).
- No `PUT/PATCH` routes exist to modify posted `Purchase` or `PurchaseItem` records.
- Client subtotals/totals/dues are strictly ignored and recalculated server-side.
- Inventory movements are generated strictly via Phase 3 `InventoryService`.
- Multi-tenant and branch authorization strictly enforced across all endpoints.

### 3. Final Verification Test Results
- **Phase 1 Security Regressions:** **64 passed / 136 assertions (0 failures)**.
- **Phase 2 Catalog Regressions:** **42 passed / 96 assertions (0 failures)**.
- **Phase 3 Inventory Regressions:** **32 passed / 93 assertions (0 failures)**.
- **Dedicated Phase 4 Test Suites:** **33 passed / 99 assertions (0 failures)**.
- **Full Backend Regression Suite:** **163 passed / 411 assertions (0 failures, duration 20.08s)**.
- **Frontend Build (`npm run build`):** **SUCCESS (`dist/` generated in 1.77s)**.
- **Development Database Safety Audit:** 0 Suppliers, 0 Purchases, 0 PurchaseItems, 0 SupplierPayments (completely clean).

---

## 42-Point Acceptance Criteria Matrix
1. Supplier is strictly Business-level master data — **VERIFIED PASS**
2. Purchase is strictly Branch-level transactional data — **VERIFIED PASS**
3. Supplier tenant isolation works — **VERIFIED PASS**
4. Foreign Supplier assignment to Purchase fails — **VERIFIED PASS**
5. Foreign Product assignment to Purchase fails — **VERIFIED PASS**
6. Foreign/unauthorized Branch assignment fails — **VERIFIED PASS**
7. Supplier code is unique per Business — **VERIFIED PASS**
8. Purchase po_number is unique per Business — **VERIFIED PASS**
9. Purchase item quantity must be > 0 — **VERIFIED PASS**
10. Purchase item unit_cost must be >= 0 — **VERIFIED PASS**
11. Purchase line totals are server-calculated — **VERIFIED PASS**
12. Purchase subtotal/total are server-calculated — **VERIFIED PASS**
13. paid_amount is validated — **VERIFIED PASS**
14. due_amount is server-derived — **VERIFIED PASS**
15. Purchase creation is atomic — **VERIFIED PASS**
16. Purchase receipt uses InventoryService — **VERIFIED PASS**
17. Correct Branch inventory increases — **VERIFIED PASS**
18. Purchase inventory movement is appended with type purchase — **VERIFIED PASS**
19. Duplicate stock receipt through normal Purchase lifecycle is prevented — **VERIFIED PASS**
20. suppliers.balance is not freely editable after Supplier creation — **VERIFIED PASS**
21. Purchase outstanding payable updates Supplier.balance correctly — **VERIFIED PASS**
22. Supplier payment reduces Supplier.balance correctly — **VERIFIED PASS**
23. Supplier overpayment is rejected for MVP — **VERIFIED PASS**
24. Supplier payment history is preserved — **VERIFIED PASS**
25. Posted Purchase is not hard-deleted — **VERIFIED PASS**
26. Purchase cancellation creates purchase_return stock movements — **VERIFIED PASS**
27. Cancellation preserves original Purchase/PurchaseItems — **VERIFIED PASS**
28. Cancellation cannot create negative inventory — **VERIFIED PASS**
29. Cancellation Supplier.balance accounting is mathematically correct — **VERIFIED PASS**
30. Supplier with financial/transaction history cannot be destructively deleted — **VERIFIED PASS**
31. Business Owner permissions work — **VERIFIED PASS**
32. Branch Manager restrictions work — **VERIFIED PASS**
33. Salesperson Supplier/Purchase administration is denied — **VERIFIED PASS**
34. Purchase/Supplier IDOR protections work — **VERIFIED PASS**
35. Product/Purchase tenant integrity works — **VERIFIED PASS**
36. Phase 1 regression remains green — **VERIFIED PASS (64 passed / 136 assertions)**
37. Phase 2 regression remains green — **VERIFIED PASS (42 passed / 96 assertions)**
38. Phase 3 inventory regression remains green — **VERIFIED PASS (32 passed / 93 assertions)**
39. Dedicated Phase 4 tests pass — **VERIFIED PASS (33 passed / 99 assertions)**
40. Full backend regression passes with zero failures — **VERIFIED PASS (163 passed / 411 assertions)**
41. Phase 5 Sales/POS scope is not implemented early — **VERIFIED PASS**
42. Phase 7 Cash/Bank truth is not implemented early — **VERIFIED PASS**

---

## Current Phase Status
- **Phase 4 — Purchases & Suppliers:** COMPLETE
- **Prompt 1/4 — Discovery / Audit / Preparation:** COMPLETE
- **Prompt 2/4 — Core Implementation:** COMPLETE
- **Prompt 3/4 — Integration / Security / Automated Testing:** COMPLETE
- **Prompt 4/4 — Final Verification / Phase Completion:** COMPLETE
- **Date Completed:** 2026-09-14

## Next Authorized Phase
- **Phase 5 — Sales & POS**
- **Next Authorized Prompt:** Phase 5 — Prompt 1/4 — Discovery / Audit / Preparation (PENDING USER AUTHORIZATION)

