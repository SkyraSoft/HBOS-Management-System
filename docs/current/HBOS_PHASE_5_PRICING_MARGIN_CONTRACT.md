# HBOS PHASE 5 — PRICING & GROSS MARGIN CONTRACT
## SPECIFICATION & CONTRACT DOCUMENT

**Program:** SkyraSoft HBOS Small Retail Transformation Program  
**Phase:** Phase 5 — Products / Pricing / Stock / Purchases / Suppliers  
**Gap Ownership:** SRT-GAP-013 (Wholesale Tier Pricing & Gross Margin Indicators)  
**Linked Canonical Requirements:** REQ-0015  

---

## 1. Pricing Structure & Model
Small Pakistani retail stores frequently cater to both regular walk-in consumers (retail) and local shopkeepers/bulk buyers (wholesale / "thok"). HBOS supports:
- **Retail Price (`products.selling_price`)**: Standard catalog price per unit (default for POS counter).
- **Wholesale Price (`products.wholesale_price`)**: Discounted rate applied when selling wholesale lots or to trade customers.
- **Cost Price (`products.cost_price`)**: Landed purchase cost basis, used for gross margin calculations and below-cost safety checks.

---

## 2. Wholesale Pricing Activation Rule
- **Counter Toggle**: Cashier / Manager can toggle "Wholesale Mode" for the whole transaction or select "Wholesale Rate" per line item.
- **Resolution**:
  - When Wholesale Mode is enabled for a line item and `product.wholesale_price > 0`, the line unit price resolves to `product.wholesale_price`.
  - If `product.wholesale_price` is null or zero, the system defaults gracefully to `product.selling_price`.

---

## 3. Price Precedence Hierarchy
To prevent silent overwrites or conflicting price rules, HBOS establishes one canonical order of precedence:

1. **Authorized Price Override (Highest Priority)**:
   - Phase-4 certified line-item price override.
   - Takes absolute precedence for that line item.
   - Requires elevated Manager/Owner credentials if executed by a cashier or if set below cost price.
2. **Pack / Carton Pricing Multiplier**:
   - Explicit packaging multipliers (e.g., Box of 12 or Carton of 24).
3. **Wholesale Tier Price**:
   - Applied when Wholesale Mode is active and `wholesale_price > 0`.
4. **Standard Catalog Retail Price (Base Fallback)**:
   - `product.selling_price`.

*Note on Discounts: Header transaction discounts and item line discounts apply proportionally on top of the resolved effective price.*

---

## 4. Gross Margin Visibility & Formulas
Gross margin represents trading profit before operational overhead:
- **Gross Margin Amount**: `effective_selling_price - cost_price`
- **Gross Margin Percentage**: `round(((effective_selling_price - cost_price) / effective_selling_price) * 100, 1)%`
- **Scope Boundary**: True Net Profit (incorporating rent, electricity, salaries, and daily operational expenses) belongs strictly to **Phase 6** (`SRT-GAP-019`). Phase 5 computes only trading gross profit.

---

## 5. Strict Cashier Cost Privacy Invariant
- **Business Super Owner**: Full visibility of cost prices, margins, and purchase histories across all branches.
- **Branch Manager**: Conditional visibility only if granted explicit `view product costs` permission.
- **Cashier / Salesperson**: **STRICTLY FORBIDDEN**.
  - Cost prices and gross margin badges are never rendered on the cashier POS counter.
  - Cashier API endpoints strip cost price fields from payloads.
  - Printed customer receipts never disclose cost or margin.

---

## 6. Below-Cost Override Non-Regression
Wholesale pricing cannot be used as an unauthorized backdoor to sell items below cost:
- If a product's configured `wholesale_price` is lower than its `cost_price`, or if an override reduces the unit price below cost, the Phase-4 below-cost authorization prompt triggers immediately.
- An elevated Business Owner or Branch Manager approval is mandatory, and a structured governance audit event is recorded.
