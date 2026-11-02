# HBOS Phase 4 — Return & Refund Contract
**Program:** SkyraSoft HBOS Small Retail Transformation Program  
**Phase:** 4 (Sales / POS / Customers / Khata / Returns)  
**Document Status:** FROZEN DESIGN CONTRACT  

---

## 1. POS Counter Return Initiation (SRT-GAP-010 / REQ-0013)
The POS checkout interface includes a dedicated "Process Return" button and keyboard shortcut (Alt+R / F7):
1. **Invoice Lookup:** Cashier scans the receipt barcode or enters the Invoice Number (e.g. `INV-20261009-84729104`).
2. **Item Selection:** The system loads the original transaction items, showing sold quantity, already-returned quantity, and available returnable units.
3. **Quantity Validation:** `return_qty <= (sold_qty - returned_qty)`. The input is bounded on the frontend and locked with `lockForUpdate()` on the backend.
4. **Restock Condition Assessment:** Cashier specifies the physical condition for each item:
   - **Restockable:** Item is pristine and sealed; received back into inventory via `InventoryService::receiveStock` (type='sale_return').
   - **Damaged / Expired:** Item is unsellable; inventory count is NOT incremented, preventing defective goods from re-entering sellable stock.
5. **Reason Specification:** Captures standardized return reasons (Customer mind change, Defective item, Wrong item purchased, Expired, etc.).

---

## 2. Economic Return vs Physical Refund Settlement
HBOS strictly distinguishes between the economic return event and physical refund disbursement:
1. **Sale Return (Economic Obligation):** Creating a `SaleReturn` records the customer return and calculates the gross refund obligation.
2. **Refund Settlement (Physical Financial Movement):** Disbursing money requires an explicit settlement step via `SaleReturnRefundController` (requiring 'settle refunds' permission or Business Owner role).
3. **Supported Refund Channels:**
   - `KHATA_CREDIT`: Automatically reduces customer's receivable balance in Khata ledger via `CustomerAccountService::recalculateBalance` (no cash drawer movement).
   - `CASH`: Issues cash directly from the branch cash drawer, recording an `AccountMovement` outflow (type='expense'/refund).
   - `BANK`: Issues digital refund from the store's bank or mobile wallet account, posting an `AccountMovement` outflow.
4. **Duplicate Refund Protection:** The system locks and verifies the refund obligation status before disbursement to prevent double-refunding.

---

## 3. Split-Tender Return Policy
When items from a split-tender sale (e.g. Cash + JazzCash + Khata) are returned:
- **Khata Offset First:** Any outstanding Khata balance on the sale/customer is reduced first by the return value:
  ```
  khata_reduction = min(effective_due, total_refund_obligation)
  ```
- **Residual Obligation:** Any remaining refund value beyond the outstanding Khata balance constitutes the residual refund obligation:
  ```
  residual_refund = total_refund_obligation - khata_reduction
  ```
- **Settlement of Residual Amount:** The cashier/manager selects an authorized refund channel (`CASH` or `BANK`) to disburse the remaining amount.
- **Double-Refund Prevention:** The database ensures `sum(settled_refunds) <= total_refund_obligation`.

---

## 4. Return Receipt Slip
Upon return confirmation, a thermal return slip (58mm/80mm) is generated containing:
- Unique Return Number (`RET-YYYYMMDD-XXXXX`)
- Original Invoice Reference
- Cashier and Customer details
- Itemized returned products with condition (Restockable vs Damaged)
- Total refund amount, Khata credit applied, and settlement channel
- Scannable return reference barcode.
