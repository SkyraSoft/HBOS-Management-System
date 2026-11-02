# HBOS PHASE 5 — SUPPLIER PRICE HISTORY & COMPARISON CONTRACT
## SPECIFICATION & CONTRACT DOCUMENT

**Program:** SkyraSoft HBOS Small Retail Transformation Program  
**Phase:** Phase 5 — Products / Pricing / Stock / Purchases / Suppliers  
**Gap Ownership:** SRT-GAP-016 (Historical Supplier Price Comparison Tooltip & Trend)  
**Linked Canonical Requirements:** REQ-0021  
**Launch Classification:** POST_LAUNCH_SAFE  
**Semantic Drift:** 0  

---

## 1. Canonical Semantic Identity Confirmation
- **SRT-GAP-016** is strictly **Historical Supplier Price Comparison Tooltip & Trend**.
- **SRT-GAP-016 is NOT "Supplier Purchase Orders"**.
- Core purchase order / GRN / landed cost capabilities belong to **REQ-0019 / SRT-GAP-015**.
- Supplier payables, payments, and supplier returns belong to **REQ-0020** (classified as `CURRENTLY_SATISFIED`).
- Semantic drift between frozen Phase-1 master requirements and Phase-5 design is **0**.

---

## 2. Authoritative Data Source Architecture
To avoid synchronization complexity, storage duplication, and data divergence:
- Historical supplier pricing is **authoritatively derived** directly from existing relational records:
  ```sql
  SELECT pi.unit_cost, p.date, p.created_at, s.name as supplier_name
  FROM purchase_items pi
  JOIN purchases p ON pi.purchase_id = p.id
  JOIN suppliers s ON p.supplier_id = s.id
  WHERE p.business_id = :business_id
    AND pi.product_id = :product_id
    AND p.supplier_id = :supplier_id
    AND p.status = 'received'
  ORDER BY p.date DESC, p.id DESC
  LIMIT 1;
  ```
- **Schema Duplication: 0**. No redundant history cache table is created.

---

## 3. Comparison & Trend Intelligence
When a store owner or manager enters a product on a purchase bill:
1. **Last Purchase Price from Same Supplier**:
   - Compares the entered unit cost with the most recent purchase from the selected supplier.
   - Computes trend delta: `price_delta_% = round(((current_cost - last_cost) / last_cost) * 100, 1)`.
   - Tooltip alert: "Last purchased from [Supplier Name]: PKR 250.00 on 12-Sep (+5.0% vs prior)".
2. **Cross-Supplier Price Benchmark**:
   - Identifies the lowest recent purchase rate for the same product across all registered suppliers.
   - Assists the shopkeeper in price negotiation: "Lowest recent price across all suppliers: PKR 240.00 from Metro Cash & Carry on 15-Aug".
3. **Unit Normalization**:
   - Prices are compared on a normalized base unit rate (e.g. per kg or per piece) to ensure accurate comparisons regardless of differing carton or pack configurations.

---

## 4. Role Security & Authorization Boundary
- **Business Super Owner**: Full access across all supplier pricing histories.
- **Branch Manager**: Operational access when creating branch delivery bills.
- **Cashier / Salesperson**: **ZERO ACCESS**. Cashiers cannot access purchase bill entry, supplier accounts, or supplier price history endpoints.
- **Tenant Partitioning**: Supplier histories are strictly isolated by `business_id`. Unrelated businesses cannot query cross-tenant pricing data.
