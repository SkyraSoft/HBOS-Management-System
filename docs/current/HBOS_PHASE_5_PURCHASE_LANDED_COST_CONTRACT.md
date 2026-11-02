# HBOS PHASE 5 — PURCHASE & LANDED COST CONTRACT
## SPECIFICATION & CONTRACT DOCUMENT

**Program:** SkyraSoft HBOS Small Retail Transformation Program  
**Phase:** Phase 5 — Products / Pricing / Stock / Purchases / Suppliers  
**Gap Ownership:** SRT-GAP-015 (Proportional Freight Landed Cost Allocation Display in Purchases)  
**Linked Canonical Requirements:** REQ-0019  

---

## 1. Purchase Bill & Receiving Lifecycle
Purchases represent incoming wholesale inventory shipments from suppliers:
1. **Creation**: Enter PO Number, Date, Supplier, Branch, and item lines (Product, Quantity, Base Unit Cost).
2. **Freight / Carriage**: Enter total freight/transport expense (bilty / carriage / mazdoori) incurred for delivery.
3. **Allocation**: System computes proportional landed cost breakdown per line item.
4. **Posting**: Atomic receipt increases `branch_inventories.quantity_on_hand`, updates `products.cost_price`, and logs supplier liability for the unpaid portion.

---

## 2. Proportional Freight Allocation Mathematics

### Value-Proportional Basis
Freight is allocated in direct proportion to line item purchase subtotal value:

$$\text{Line Freight Ratio} = \frac{\text{Line Subtotal}}{\text{Purchase Subtotal}}$$

$$\text{Raw Line Freight} = \text{Total Freight} \times \text{Line Freight Ratio}$$

### Exact Rounding Reconciliation Invariant
To ensure 100% financial reconciliation without disappearing paisa or rupee rounding leaks:
$$\sum (\text{Allocated Line Freight}) \equiv \text{Total Purchase Freight}$$

**Deterministic Remainder Assignment**:
1. Round each line freight to 2 decimal places: `line_freight = round(raw_line_freight, 2)`.
2. Compute remainder delta: `delta = total_freight - sum(line_freight)`.
3. If `delta !== 0.00`, add `delta` to the line item possessing the highest line subtotal.

---

## 3. Landed Unit Cost Formula
The true unit cost of stock reflects the base purchase price plus allocated freight:

$$\text{Landed Unit Cost} = \text{Base Unit Cost} + \left(\frac{\text{Allocated Line Freight}}{\text{Item Quantity}}\right)$$

Persisted on `purchase_items` as:
- `purchase_items.freight_allocated`: `DECIMAL(12, 2)`
- `purchase_items.landed_unit_cost`: `DECIMAL(12, 4)`

---

## 4. Costing Method & Historical COGS Immutability
- **Inventory Costing Convention**: Last Purchase Cost basis. `products.cost_price` updates to the newly received batch's `landed_unit_cost`.
- **Historical Sale COGS Immutability**:
  - `SaleItem.cost_price` and `SaleItem.unit_cost` captured at the time of checkout are immutable historical financial snapshots.
  - New purchases with higher or lower landed unit costs **MUST NEVER** retroactively mutate historical sale records.
  - **Required Invariant**: Historical Sale COGS Mutation = 0.

---

## 5. Supplier Payable Boundary
- **Independent Transporter Freight**: Freight paid to a third-party cargo/bilty carrier does NOT increase the supplier's payable ledger balance.
- **Supplier-Billed Carriage**: If freight is billed directly on the supplier invoice, it is included in bill total and tracked under `SupplierBalanceService`.
- **Supplier Returns Non-Regression**: Supplier returns (REQ-0020) deduct inventory at base purchase cost and credit the supplier ledger via debit notes without altering freight accounting.
