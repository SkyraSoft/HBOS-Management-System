# HBOS PHASE 5 — STOCK INTELLIGENCE & REORDER ANALYTICS CONTRACT
## SPECIFICATION & CONTRACT DOCUMENT

**Program:** SkyraSoft HBOS Small Retail Transformation Program  
**Phase:** Phase 5 — Products / Pricing / Stock / Purchases / Suppliers  
**Gap Ownership:** SRT-GAP-014 (Stock Intelligence, Reorder Analytics & Slow-Moving Criteria)  
**Linked Canonical Requirements:** REQ-0017  

---

## 1. Authoritative Inventory Source
The sole authoritative inventory truth for any branch is the `branch_inventories.quantity_on_hand` column. Products catalog master `products.stock` acts as an aggregate reference only.

---

## 2. Inventory Health Classification

### A. Out of Stock
- **Condition**: `quantity_on_hand <= 0.000`
- **Indicator**: Prominent red badge on POS card and inventory lists. Warns cashier before checkout.

### B. Low Stock / Reorder Trigger
- **Condition**: `0.000 < quantity_on_hand <= minimum_stock` (or `<= reorder_level`)
- **Indicator**: Amber alert in ReorderView and branch dashboard.

### C. Fast Moving Inventory
- **Condition**: High sales turnover within the analysis window.
- **Criteria**: Sales velocity exceeding top percentile of store sales volume.

### D. Slow Moving & Dead Stock Tracking
- **Configurable Criteria**: Analysis windows of 30 days, 60 days, or 90 days. Zero dogmatic hardcoded thresholds.
- **Dead Stock Definition**: Products with `quantity_on_hand > 0.000` and **zero** units sold within the selected analysis window.
- **Locked Capital Metric**: Displays total locked working capital: `sum(quantity_on_hand * cost_price)` for all dead stock items to help the store owner run clearance promotions.

---

## 3. Explainable Reorder Recommendation Formula
Small retailers need clear, explainable suggestions rather than opaque AI black boxes:

$$\text{Daily Sales Velocity} = \frac{\text{Units Sold in Window}}{\text{Days in Window}}$$

$$\text{Suggested Reorder Quantity} = \max\left(0, \left\lceil (\text{Daily Sales Velocity} \times \text{Target Lead Days}) - \text{Current Stock} \right\rceil\right)$$

*Fallback Rule (when sales history is limited):*  
$$\text{Suggested Reorder} = \max(0, (\text{minimum\_stock} \times 2) - \text{quantity\_on\_hand})$$

---

## 4. Branch Scoping & Multi-Branch Intelligence
- **Branch Manager**: Restricted strictly to their assigned `branch_id`. Can view local low-stock items and submit purchase suggestions. Cannot access inventory data for other branches.
- **Business Super Owner**: Can switch between individual branches or view a consolidated business-wide inventory health summary.

---

## 5. Phase-7 Stock Transfer Boundary Invariant
- **Strict Boundary**: REQ-0018 / SRT-GAP-021 (Inter-Branch Stock Transfers, Dispatch, In-Transit & Receive) belongs exclusively to **Phase 7**.
- **No Phase-5 Action Leakage**: In Phase 5, the reorder analytics view allows only "Create Purchase Bill from Supplier" or "Add to Shopping List". Under no circumstances will a "Transfer from Branch B" action button or dispatch workflow be introduced in Phase 5.
