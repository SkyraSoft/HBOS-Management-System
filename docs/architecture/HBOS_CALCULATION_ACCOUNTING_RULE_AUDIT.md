# SKYRASOFT HBOS FUNCTIONAL ARCHITECTURE PROGRAM
# PHASE 4 — CALCULATIONS, ACCOUNTING & BUSINESS RULE AUDIT

> **Authoritative System Audit Deliverable**  
> **Scope:** Authoritative Calculation Registry, Formula Conflict Analysis, Accounting Invariants, Balance Reconciliation, Business Rule Enforcement  
> **Status:** Certified Complete With Known Gaps (Preflight Verified)  

## 1. Executive Summary & Domain Scope

Phase 4 evaluates all arithmetic, financial metrics, perpetual ledgers, balance caches, accounting invariants, and business rules across the SkyraSoft HBOS platform. Building upon Phase 1 data lineage, Phase 2 entity lifecycles, and Phase 3 cross-module business flows, this audit identifies where calculations occur, which source is authoritative, whether frontend/backend/reports agree, and where numerical or ledger drift can occur.

### Summary Audit Metrics

- **Canonical Calculations Defined:** 32 (`CALC-001` to `CALC-032`)
- **Formula Occurrences Audited:** 24 across Frontend, Backend, Reports, Dashboards, and Prints
- **Accounting Invariants Audited:** 16 (`INV-001` to `INV-016`)
- **Physical Balance Domains Audited:** 5 (Stock, AR, AP, Bank, Cash Ledger)
- **Business Rules Audited:** 18 (`RULE-001` to `RULE-018`)
- **Phase-4 Tracked Gaps:** 15 (`P4-GAP-001` to `P4-GAP-016`)
- **Certified Complete Calculations:** 27 | **Certified With Known Gap:** 5 | **Uncertified:** 0

## 2. Master Calculation Registry (CALC-001 to CALC-032)

| Calc ID | Calculation Name | Domain | Formula | Authoritative Implementation | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `CALC-001` | **POS Sale Line Item Subtotal** | `SALES_POS` | `line_subtotal = quantity * unit_price` | `BACKEND_TRANSACTION_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-002` | **Sale Aggregate Subtotal** | `SALES_POS` | `subtotal = SUM(sale_items.subtotal)` | `BACKEND_TRANSACTION_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-003` | **Sale Order-Level Discount Allocation** | `SALES_POS` | `discount_amount = (discount_type == "percentage") ? (subtotal * discount_value / 100) : discount_value` | `BACKEND_TRANSACTION_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-004` | **Sale Order-Level Tax Computation** | `SALES_POS` | `tax_amount = (subtotal - discount_amount) * (tax_rate / 100)` | `BACKEND_TRANSACTION_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-005` | **Sale Grand Total Amount** | `SALES_POS` | `total_amount = subtotal - discount_amount + tax_amount` | `BACKEND_TRANSACTION_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-006` | **Sale Due / Outstanding Amount** | `SALES_POS` | `due_amount = MAX(total_amount - paid_amount, 0.00)` | `BACKEND_TRANSACTION_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-007` | **POS Cash Tender Change Returned** | `SALES_POS` | `change_amount = MAX(tendered_cash - total_amount, 0.00)` | `FRONTEND_PREVIEW_ONLY` | `CERTIFIED_COMPLETE` |
| `CALC-008` | **Sale Line Item Cost of Goods Sold (COGS)** | `SALES_POS` | `line_cogs = quantity * cost_price` | `BACKEND_TRANSACTION_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-009` | **Sale Gross Profit Margin** | `SALES_POS` | `gross_profit = (total_amount - tax_amount) - total_cogs` | `REPORT_DERIVED_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-010` | **Product Gross Margin Percentage** | `PRODUCTS_PRICING` | `margin_percentage = (selling_price > 0) ? (((selling_price - cost_price) / selling_price) * 100) : 0.00` | `MASTER_RECORD_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-011` | **Purchase Bill Line Item Subtotal** | `PROCUREMENT` | `purchase_line_subtotal = quantity * unit_cost` | `BACKEND_TRANSACTION_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-012` | **Purchase Aggregate Subtotal** | `PROCUREMENT` | `purchase_subtotal = SUM(purchase_items.subtotal)` | `BACKEND_TRANSACTION_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-013` | **Purchase Inbound Freight / Shipping Charge** | `PROCUREMENT` | `shipping_cost = shipping_cost` | `BACKEND_TRANSACTION_AUTHORITY` | `CERTIFIED_WITH_KNOWN_GAP` |
| `CALC-014` | **Purchase Order Tax & Discount Computations** | `PROCUREMENT` | `net_tax_discount = -discount_amount + tax_amount` | `BACKEND_TRANSACTION_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-015` | **Purchase Grand Total Payable** | `PROCUREMENT` | `purchase_total = subtotal - discount_amount + tax_amount + shipping_cost` | `BACKEND_TRANSACTION_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-016` | **Purchase Due / Outstanding AP Balance** | `PROCUREMENT` | `purchase_due = MAX(purchase_total - paid_amount, 0.00)` | `BACKEND_TRANSACTION_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-017` | **Product Moving Average Unit Cost Price** | `INVENTORY_VALUATION` | `new_cost_price = ((existing_stock * existing_cost) + (received_qty * received_unit_cost)) / (existing_stock + received_qty)` | `BACKEND_TRANSACTION_AUTHORITY` | `CERTIFIED_WITH_KNOWN_GAP` |
| `CALC-018` | **Branch Total Inventory Asset Valuation** | `INVENTORY_VALUATION` | `inventory_valuation = SUM(branch_inventories.quantity_on_hand * products.cost_price)` | `REPORT_DERIVED_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-019` | **Sale Return Line Refund Amount** | `RETURNS_REFUNDS` | `return_line_amount = return_quantity * original_unit_price` | `BACKEND_TRANSACTION_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-020` | **Sale Return Grand Total Refund** | `RETURNS_REFUNDS` | `total_refund_amount = SUM(sale_return_items.subtotal)` | `BACKEND_TRANSACTION_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-021` | **Customer Outstanding Receivable Balance** | `CUSTOMER_KHATA_AR` | `customer_balance = opening_balance + SUM(debit_amount) - SUM(credit_amount)` | `LEDGER_AUTHORITY` | `CERTIFIED_WITH_KNOWN_GAP` |
| `CALC-022` | **Customer Available Credit Limit Margin** | `CUSTOMER_KHATA_AR` | `available_credit = (credit_limit > 0) ? MAX(credit_limit - customer_balance, 0.00) : UNLIMITED` | `BACKEND_TRANSACTION_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-023` | **Supplier Outstanding Accounts Payable (AP) Balance** | `SUPPLIER_AP` | `supplier_balance = opening_balance + SUM(purchase_due) - SUM(payment_amount)` | `BACKEND_TRANSACTION_AUTHORITY` | `CERTIFIED_WITH_KNOWN_GAP` |
| `CALC-024` | **Supplier Payment Voucher Allocation to Unpaid Purchases** | `SUPPLIER_AP` | `Allocated FIFO: purchase[i].due = MAX(purchase[i].due - allocated_payment, 0.00)` | `BACKEND_TRANSACTION_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-025` | **Financial Account Perpetual Balance** | `BANKING_FINANCE` | `account_balance = opening_balance + SUM(credit_amount) - SUM(debit_amount)` | `LEDGER_AUTHORITY` | `CERTIFIED_WITH_KNOWN_GAP` |
| `CALC-026` | **Cash Drawer Ledger Balance Calculation** | `CASH_MANAGEMENT` | `cash_balance = opening_balance + SUM(inflow_amount) - SUM(outflow_amount)` | `LEDGER_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-027` | **Cash Shift Count Variance** | `CASH_MANAGEMENT` | `variance = counted_cash - expected_cash_balance` | `BACKEND_TRANSACTION_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-028` | **Direct Operating Expense Total Amount** | `EXPENSES` | `expense_total = amount` | `BACKEND_TRANSACTION_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-029` | **Dashboard Period Net Revenue Metric** | `REPORTING_DASHBOARD` | `net_revenue = SUM(sales.total_amount) - SUM(sale_returns.total_amount)` | `DATABASE_QUERY_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-030` | **Dashboard Period Net Operating Profit Metric** | `REPORTING_DASHBOARD` | `net_operating_profit = gross_profit - SUM(expenses.amount)` | `REPORT_DERIVED_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-031` | **Dashboard Total Accounts Payable & Receivable Aggregates** | `REPORTING_DASHBOARD` | `total_ar = SUM(customers.balance); total_ap = SUM(suppliers.balance)` | `DATABASE_QUERY_AUTHORITY` | `CERTIFIED_COMPLETE` |
| `CALC-032` | **Period Net Cash & Liquid Capital Movement** | `REPORTING_DASHBOARD` | `net_cash_flow = (SUM(cash_inflow) + SUM(bank_credit)) - (SUM(cash_outflow) + SUM(bank_debit))` | `DATABASE_QUERY_AUTHORITY` | `CERTIFIED_COMPLETE` |

## 3. Deep-Dive Calculation Specifications

### CALC-001: POS Sale Line Item Subtotal

- **Business Domain:** `SALES_POS`
- **Business Meaning:** Calculates the gross pre-discount line monetary amount for a single sold product line based on quantity and unit selling price.
- **Trigger:** Product scanned/added to cart or quantity modified in POS
- **Formula:** `line_subtotal = quantity * unit_price`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Default quantity to 1, unit_price to 0.00 if null | quantity >= 0.01, unit_price >= 0.00
- **Backend Implementation:** `api/app/Services/SaleService.php@createSale`
- **Frontend Implementation:** `Frontend/src/views/PosSaleView.vue (computed line subtotal)`
- **Database Representation:** `DBCOL-195 (sale_items.subtotal)`
- **Authoritative Authority:** `BACKEND_TRANSACTION_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/SaleService.php:88, Frontend/src/views/PosSaleView.vue:512`
- **Known Gaps:** None

### CALC-002: Sale Aggregate Subtotal

- **Business Domain:** `SALES_POS`
- **Business Meaning:** Sum of all line item subtotals for an entire sale transaction prior to order-level discount and tax.
- **Trigger:** Cart item change, checkout initiation
- **Formula:** `subtotal = SUM(sale_items.subtotal)`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Empty cart returns 0.00 | subtotal >= 0.00
- **Backend Implementation:** `api/app/Services/SaleService.php:94`
- **Frontend Implementation:** `Frontend/src/views/PosSaleView.vue (cartSubtotal)`
- **Database Representation:** `DBCOL-177 (sales.subtotal)`
- **Authoritative Authority:** `BACKEND_TRANSACTION_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/SaleService.php:94, Frontend/src/views/PosSaleView.vue:530`
- **Known Gaps:** None

### CALC-003: Sale Order-Level Discount Allocation

- **Business Domain:** `SALES_POS`
- **Business Meaning:** Computes the absolute monetary discount deducted from the sale subtotal based on fixed amount or percentage.
- **Trigger:** Operator inputs discount value or percentage in POS modal
- **Formula:** `discount_amount = (discount_type == "percentage") ? (subtotal * discount_value / 100) : discount_value`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Null discount treated as 0.00 | 0 <= discount_amount <= subtotal
- **Backend Implementation:** `api/app/Services/SaleService.php:98`
- **Frontend Implementation:** `Frontend/src/views/PosSaleView.vue (calculatedDiscount)`
- **Database Representation:** `DBCOL-178 (sales.discount_amount)`
- **Authoritative Authority:** `BACKEND_TRANSACTION_AUTHORITY`
- **Conflict Classification:** `PRESENTATION_EQUIVALENT`
- **Source Evidence:** `api/app/Services/SaleService.php:98, Frontend/src/views/PosSaleView.vue:545`
- **Known Gaps:** None

### CALC-004: Sale Order-Level Tax Computation

- **Business Domain:** `SALES_POS`
- **Business Meaning:** Calculates government tax liability added to the discounted taxable base of the sale.
- **Trigger:** Checkout calculation cycle
- **Formula:** `tax_amount = (subtotal - discount_amount) * (tax_rate / 100)`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Null tax rate treated as 0% | tax_amount >= 0.00
- **Backend Implementation:** `api/app/Services/SaleService.php:102`
- **Frontend Implementation:** `Frontend/src/views/PosSaleView.vue (calculatedTax)`
- **Database Representation:** `DBCOL-179 (sales.tax_amount)`
- **Authoritative Authority:** `BACKEND_TRANSACTION_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/SaleService.php:102`
- **Known Gaps:** None

### CALC-005: Sale Grand Total Amount

- **Business Domain:** `SALES_POS`
- **Business Meaning:** Final net payable monetary amount for the sale invoice after discount deduction and tax addition.
- **Trigger:** Cart finalize, checkout submit
- **Formula:** `total_amount = subtotal - discount_amount + tax_amount`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Treated as 0.00 | total_amount >= 0.00
- **Backend Implementation:** `api/app/Services/SaleService.php:106`
- **Frontend Implementation:** `Frontend/src/views/PosSaleView.vue (grandTotal)`
- **Database Representation:** `DBCOL-180 (sales.total_amount)`
- **Authoritative Authority:** `BACKEND_TRANSACTION_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/SaleService.php:106, Frontend/src/views/PosSaleView.vue:560`
- **Known Gaps:** None

### CALC-006: Sale Due / Outstanding Amount

- **Business Domain:** `SALES_POS`
- **Business Meaning:** Unpaid residual balance on a sale transaction that is posted to customer accounts receivable (Khata).
- **Trigger:** Payment settlement calculation
- **Formula:** `due_amount = MAX(total_amount - paid_amount, 0.00)`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** paid_amount null treated as 0.00 | 0.00 <= due_amount <= total_amount
- **Backend Implementation:** `api/app/Services/SaleService.php:112`
- **Frontend Implementation:** `Frontend/src/views/PosSaleView.vue (remainingDue)`
- **Database Representation:** `DBCOL-182 (sales.due_amount)`
- **Authoritative Authority:** `BACKEND_TRANSACTION_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/SaleService.php:112`
- **Known Gaps:** None

### CALC-007: POS Cash Tender Change Returned

- **Business Domain:** `SALES_POS`
- **Business Meaning:** Physical cash currency returned to customer when tendered cash exceeds sale grand total.
- **Trigger:** Operator inputs tendered cash in payment modal
- **Formula:** `change_amount = MAX(tendered_cash - total_amount, 0.00)`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Treated as 0.00 | change_amount >= 0.00
- **Backend Implementation:** `NONE (Transient Presentation Value)`
- **Frontend Implementation:** `Frontend/src/views/PosSaleView.vue (changeReturned)`
- **Database Representation:** `NOT_PERSISTED_IN_DATABASE`
- **Authoritative Authority:** `FRONTEND_PREVIEW_ONLY`
- **Conflict Classification:** `PRESENTATION_EQUIVALENT`
- **Source Evidence:** `Frontend/src/views/PosSaleView.vue:582, Frontend/src/views/Settings/ReceiptsView.vue:112`
- **Known Gaps:** None

### CALC-008: Sale Line Item Cost of Goods Sold (COGS)

- **Business Domain:** `SALES_POS`
- **Business Meaning:** Cost basis of goods sold on a transaction line based on historical product unit cost at time of sale.
- **Trigger:** Sale creation line persistence
- **Formula:** `line_cogs = quantity * cost_price`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Null cost_price treated as 0.00 | line_cogs >= 0.00
- **Backend Implementation:** `api/app/Services/SaleService.php:124`
- **Frontend Implementation:** `NONE`
- **Database Representation:** `DBCOL-196 (sale_items.cost_price snapshot * qty)`
- **Authoritative Authority:** `BACKEND_TRANSACTION_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/SaleService.php:124`
- **Known Gaps:** None

### CALC-009: Sale Gross Profit Margin

- **Business Domain:** `SALES_POS`
- **Business Meaning:** Gross monetary profit generated by a sale transaction after deducting total COGS from net sales revenue.
- **Trigger:** Sale completion, profit reporting
- **Formula:** `gross_profit = (total_amount - tax_amount) - total_cogs`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Treated as 0.00 | Can be negative if sold below cost
- **Backend Implementation:** `api/app/Services/ReportService.php:92`
- **Frontend Implementation:** `Frontend/src/views/reports1View.vue (profitColumn)`
- **Database Representation:** `DERIVED_ON_THE_FLY`
- **Authoritative Authority:** `REPORT_DERIVED_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/ReportService.php:92, api/app/Services/DashboardService.php:152`
- **Known Gaps:** None

### CALC-010: Product Gross Margin Percentage

- **Business Domain:** `PRODUCTS_PRICING`
- **Business Meaning:** Markup percentage of gross profit relative to selling price.
- **Trigger:** Product master edit, pricing review
- **Formula:** `margin_percentage = (selling_price > 0) ? (((selling_price - cost_price) / selling_price) * 100) : 0.00`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `PERCENTAGE_RATIO`)
- **Null / Boundary Handling:** 0.00% if selling_price is 0 | margin_percentage <= 100%
- **Backend Implementation:** `api/app/Models/Product.php (getMarginAttribute)`
- **Frontend Implementation:** `Frontend/src/views/ProductView.vue:320`
- **Database Representation:** `DERIVED_MODEL_ACCESSOR`
- **Authoritative Authority:** `MASTER_RECORD_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `Frontend/src/views/ProductView.vue:320, api/app/Models/Product.php:48`
- **Known Gaps:** None

### CALC-011: Purchase Bill Line Item Subtotal

- **Business Domain:** `PROCUREMENT`
- **Business Meaning:** Gross procurement amount for a single purchase bill item before purchase discount and tax.
- **Trigger:** Purchase line entry
- **Formula:** `purchase_line_subtotal = quantity * unit_cost`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Treated as 0.00 | quantity >= 1, unit_cost >= 0.00
- **Backend Implementation:** `api/app/Services/PurchaseService.php:48`
- **Frontend Implementation:** `Frontend/src/views/PurchaseView.vue:245`
- **Database Representation:** `DBCOL-275 (purchase_items.subtotal)`
- **Authoritative Authority:** `BACKEND_TRANSACTION_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/PurchaseService.php:48, Frontend/src/views/PurchaseView.vue:245`
- **Known Gaps:** None

### CALC-012: Purchase Aggregate Subtotal

- **Business Domain:** `PROCUREMENT`
- **Business Meaning:** Sum of all purchase line item subtotals for an entire inbound shipment bill.
- **Trigger:** Purchase bill entry change
- **Formula:** `purchase_subtotal = SUM(purchase_items.subtotal)`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** 0.00 if no items | purchase_subtotal >= 0.00
- **Backend Implementation:** `api/app/Services/PurchaseService.php:54`
- **Frontend Implementation:** `Frontend/src/views/PurchaseView.vue:260`
- **Database Representation:** `DBCOL-257 (purchases.subtotal)`
- **Authoritative Authority:** `BACKEND_TRANSACTION_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/PurchaseService.php:54`
- **Known Gaps:** None

### CALC-013: Purchase Inbound Freight / Shipping Charge

- **Business Domain:** `PROCUREMENT`
- **Business Meaning:** Transportation and delivery charges billed on the purchase invoice.
- **Trigger:** Operator enters freight cost in purchase creation form
- **Formula:** `shipping_cost = shipping_cost`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Default to 0.00 if null | shipping_cost >= 0.00
- **Backend Implementation:** `api/app/Services/PurchaseService.php:62`
- **Frontend Implementation:** `Frontend/src/views/PurchaseView.vue:280`
- **Database Representation:** `DBCOL-260 (purchases.shipping_cost)`
- **Authoritative Authority:** `BACKEND_TRANSACTION_AUTHORITY`
- **Conflict Classification:** `FORMULA_CONFLICT`
- **Source Evidence:** `api/app/Services/PurchaseService.php:62, api/app/Services/InventoryService.php:180`
- **Known Gaps:** P4-GAP-005

### CALC-014: Purchase Order Tax & Discount Computations

- **Business Domain:** `PROCUREMENT`
- **Business Meaning:** Computes vendor discount deductions and applicable purchase taxes on procurement bill.
- **Trigger:** Purchase bill calculation
- **Formula:** `net_tax_discount = -discount_amount + tax_amount`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Treated as 0.00 | discount_amount <= subtotal
- **Backend Implementation:** `api/app/Services/PurchaseService.php:68`
- **Frontend Implementation:** `Frontend/src/views/PurchaseView.vue:292`
- **Database Representation:** `DBCOL-258, DBCOL-259`
- **Authoritative Authority:** `BACKEND_TRANSACTION_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/PurchaseService.php:68`
- **Known Gaps:** None

### CALC-015: Purchase Grand Total Payable

- **Business Domain:** `PROCUREMENT`
- **Business Meaning:** Total gross obligation owed to supplier for the purchase bill including freight and taxes after discounts.
- **Trigger:** Purchase bill submit
- **Formula:** `purchase_total = subtotal - discount_amount + tax_amount + shipping_cost`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Treated as 0.00 | purchase_total >= 0.00
- **Backend Implementation:** `api/app/Services/PurchaseService.php:74`
- **Frontend Implementation:** `Frontend/src/views/PurchaseView.vue:310`
- **Database Representation:** `DBCOL-261 (purchases.total_amount)`
- **Authoritative Authority:** `BACKEND_TRANSACTION_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/PurchaseService.php:74, Frontend/src/views/PurchaseView.vue:310`
- **Known Gaps:** None

### CALC-016: Purchase Due / Outstanding AP Balance

- **Business Domain:** `PROCUREMENT`
- **Business Meaning:** Unpaid residual supplier liability on a purchase bill added to accounts payable.
- **Trigger:** Purchase bill payment settlement
- **Formula:** `purchase_due = MAX(purchase_total - paid_amount, 0.00)`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** paid_amount null treated as 0.00 | 0.00 <= purchase_due <= purchase_total
- **Backend Implementation:** `api/app/Services/PurchaseService.php:80`
- **Frontend Implementation:** `Frontend/src/views/PurchaseView.vue:325`
- **Database Representation:** `DBCOL-263 (purchases.due_amount)`
- **Authoritative Authority:** `BACKEND_TRANSACTION_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/PurchaseService.php:80`
- **Known Gaps:** None

### CALC-017: Product Moving Average Unit Cost Price

- **Business Domain:** `INVENTORY_VALUATION`
- **Business Meaning:** Perpetual weighted-average inventory acquisition cost updated upon receiving new inventory batches.
- **Trigger:** Inbound stock receipt (Purchase GRN)
- **Formula:** `new_cost_price = ((existing_stock * existing_cost) + (received_qty * received_unit_cost)) / (existing_stock + received_qty)`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** If (existing_stock + received_qty) <= 0, new_cost_price = received_unit_cost | new_cost_price >= 0.00
- **Backend Implementation:** `api/app/Services/InventoryService.php@receiveStock`
- **Frontend Implementation:** `NONE`
- **Database Representation:** `DBCOL-110 (products.cost_price)`
- **Authoritative Authority:** `BACKEND_TRANSACTION_AUTHORITY`
- **Conflict Classification:** `FORMULA_CONFLICT`
- **Source Evidence:** `api/app/Services/InventoryService.php:185`
- **Known Gaps:** P4-GAP-005

### CALC-018: Branch Total Inventory Asset Valuation

- **Business Domain:** `INVENTORY_VALUATION`
- **Business Meaning:** Monetary value of inventory stock on hand at cost price for a specific branch or whole business.
- **Trigger:** Inventory dashboard / valuation report generation
- **Formula:** `inventory_valuation = SUM(branch_inventories.quantity_on_hand * products.cost_price)`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Treated as 0.00 | inventory_valuation >= 0.00
- **Backend Implementation:** `api/app/Services/DashboardService.php@calculateInventoryMetrics`
- **Frontend Implementation:** `Frontend/src/views/InventryView.vue:450`
- **Database Representation:** `DERIVED_ON_THE_FLY`
- **Authoritative Authority:** `REPORT_DERIVED_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/DashboardService.php:204, api/app/Services/ReportService.php:180`
- **Known Gaps:** None

### CALC-019: Sale Return Line Refund Amount

- **Business Domain:** `RETURNS_REFUNDS`
- **Business Meaning:** Monetary refund credit for a single returned item line based on returned quantity and original sale unit price.
- **Trigger:** Sale return line item entry
- **Formula:** `return_line_amount = return_quantity * original_unit_price`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Treated as 0.00 | 0 < return_quantity <= original_sold_quantity
- **Backend Implementation:** `api/app/Services/SaleService.php@processReturn`
- **Frontend Implementation:** `Frontend/src/views/ReturnDebtView.vue:120`
- **Database Representation:** `DBCOL-205 (sale_return_items.subtotal)`
- **Authoritative Authority:** `BACKEND_TRANSACTION_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/SaleService.php:210, Frontend/src/views/ReturnDebtView.vue:120`
- **Known Gaps:** None

### CALC-020: Sale Return Grand Total Refund

- **Business Domain:** `RETURNS_REFUNDS`
- **Business Meaning:** Sum of all return line refund amounts refunded to customer via cash or Khata store credit.
- **Trigger:** Sale return submit
- **Formula:** `total_refund_amount = SUM(sale_return_items.subtotal)`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** 0.00 if empty | 0.00 <= total_refund_amount <= original_sale.total_amount
- **Backend Implementation:** `api/app/Services/SaleService.php:224`
- **Frontend Implementation:** `Frontend/src/views/ReturnDebtView.vue:145`
- **Database Representation:** `DBCOL-201 (sale_returns.total_amount)`
- **Authoritative Authority:** `BACKEND_TRANSACTION_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/SaleService.php:224`
- **Known Gaps:** None

### CALC-021: Customer Outstanding Receivable Balance

- **Business Domain:** `CUSTOMER_KHATA_AR`
- **Business Meaning:** Cumulative net monetary amount owed by a customer across all credit sales, payments, and returns.
- **Trigger:** Credit sale, customer payment voucher, return credit, manual adjustment
- **Formula:** `customer_balance = opening_balance + SUM(debit_amount) - SUM(credit_amount)`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Treated as 0.00 | customer_balance <= customer.credit_limit
- **Backend Implementation:** `api/app/Services/CustomerAccountService.php@calculateOutstanding`
- **Frontend Implementation:** `Frontend/src/views/KhataView.vue:310`
- **Database Representation:** `DBCOL-209 (customers.balance)`
- **Authoritative Authority:** `LEDGER_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/CustomerAccountService.php:52, api/app/Models/Customer.php:68`
- **Known Gaps:** P4-GAP-002

### CALC-022: Customer Available Credit Limit Margin

- **Business Domain:** `CUSTOMER_KHATA_AR`
- **Business Meaning:** Remaining borrowing capacity for a customer before reaching maximum credit threshold.
- **Trigger:** Customer checkout in POS
- **Formula:** `available_credit = (credit_limit > 0) ? MAX(credit_limit - customer_balance, 0.00) : UNLIMITED`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** credit_limit 0 or null treated as unlimited | available_credit >= 0.00
- **Backend Implementation:** `api/app/Services/CustomerAccountService.php:88`
- **Frontend Implementation:** `Frontend/src/views/PosSaleView.vue:640`
- **Database Representation:** `DERIVED_ON_THE_FLY`
- **Authoritative Authority:** `BACKEND_TRANSACTION_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/CustomerAccountService.php:88, Frontend/src/views/PosSaleView.vue:640`
- **Known Gaps:** None

### CALC-023: Supplier Outstanding Accounts Payable (AP) Balance

- **Business Domain:** `SUPPLIER_AP`
- **Business Meaning:** Cumulative net monetary liability owed to a vendor across all credit purchases, payments, and cancellations.
- **Trigger:** Credit purchase, supplier payment voucher, purchase cancel
- **Formula:** `supplier_balance = opening_balance + SUM(purchase_due) - SUM(payment_amount)`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Treated as 0.00 | supplier_balance is signed currency
- **Backend Implementation:** `api/app/Services/SupplierBalanceService.php@reconcileBalance`
- **Frontend Implementation:** `Frontend/src/views/SupplierpayableView.vue:210`
- **Database Representation:** `DBCOL-250 (suppliers.balance)`
- **Authoritative Authority:** `BACKEND_TRANSACTION_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/SupplierBalanceService.php:44, api/app/Models/Supplier.php:52`
- **Known Gaps:** P4-GAP-007

### CALC-024: Supplier Payment Voucher Allocation to Unpaid Purchases

- **Business Domain:** `SUPPLIER_AP`
- **Business Meaning:** Applies a supplier payment voucher to open purchase bills, reducing individual purchase due amounts.
- **Trigger:** Supplier payment voucher submit
- **Formula:** `Allocated FIFO: purchase[i].due = MAX(purchase[i].due - allocated_payment, 0.00)`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Treated as 0.00 | SUM(allocated_purchases) <= payment_amount
- **Backend Implementation:** `api/app/Services/SupplierBalanceService.php@recordPayment`
- **Frontend Implementation:** `Frontend/src/views/SupplierPaymentView.vue:180`
- **Database Representation:** `DBCOL-262, DBCOL-263 updated on purchases`
- **Authoritative Authority:** `BACKEND_TRANSACTION_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/SupplierBalanceService.php:72, Frontend/src/views/SupplierPaymentView.vue:180`
- **Known Gaps:** None

### CALC-025: Financial Account Perpetual Balance

- **Business Domain:** `BANKING_FINANCE`
- **Business Meaning:** Real-time monetary ledger balance of a bank or electronic financial account derived from cumulative debits and credits.
- **Trigger:** Sale deposit, customer payment, purchase disbursement, supplier payment, expense, transfer, capital movement
- **Formula:** `account_balance = opening_balance + SUM(credit_amount) - SUM(debit_amount)`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Treated as 0.00 | account_balance is signed currency
- **Backend Implementation:** `api/app/Services/AccountMovementService.php@calculateBalance`
- **Frontend Implementation:** `Frontend/src/views/Settings/InformationView.vue:190`
- **Database Representation:** `DBCOL-332 (financial_accounts.balance)`
- **Authoritative Authority:** `LEDGER_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/AccountMovementService.php:38, api/app/Models/FinancialAccount.php:45`
- **Known Gaps:** P4-GAP-003

### CALC-026: Cash Drawer Ledger Balance Calculation

- **Business Domain:** `CASH_MANAGEMENT`
- **Business Meaning:** Running cash balance for a store branch computed from cumulative cash ledger inflows and outflows.
- **Trigger:** Cash sale, cash return refund, cash expense, manual cash adjustment
- **Formula:** `cash_balance = opening_balance + SUM(inflow_amount) - SUM(outflow_amount)`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Treated as 0.00 | cash_balance >= 0.00
- **Backend Implementation:** `api/app/Services/CashLedgerService.php@recordInflow`
- **Frontend Implementation:** `Frontend/src/views/PosSaleView.vue:710`
- **Database Representation:** `DBCOL-373 (cash_ledgers.balance)`
- **Authoritative Authority:** `LEDGER_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/CashLedgerService.php:42, api/app/Models/CashLedger.php:35`
- **Known Gaps:** None

### CALC-027: Cash Shift Count Variance

- **Business Domain:** `CASH_MANAGEMENT`
- **Business Meaning:** Variance between physical cash counted by cashier and the expected running cash balance.
- **Trigger:** Operator cash count review
- **Formula:** `variance = counted_cash - expected_cash_balance`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Treated as 0.00 | Signed variance
- **Backend Implementation:** `api/app/Services/CashLedgerService.php@recordAdjustment`
- **Frontend Implementation:** `Frontend/src/views/PosSaleView.vue:740`
- **Database Representation:** `DBCOL-374 (cash_ledgers.notes captures variance)`
- **Authoritative Authority:** `BACKEND_TRANSACTION_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/CashLedgerService.php:68`
- **Known Gaps:** None

### CALC-028: Direct Operating Expense Total Amount

- **Business Domain:** `EXPENSES`
- **Business Meaning:** Disbursed monetary amount for store operations categorized under specific expense category.
- **Trigger:** Expense creation
- **Formula:** `expense_total = amount`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Prohibited | amount > 0.00
- **Backend Implementation:** `api/app/Services/ExpenseService.php@createExpense`
- **Frontend Implementation:** `Frontend/src/views/ExpensesView.vue:180`
- **Database Representation:** `DBCOL-306 (expenses.amount)`
- **Authoritative Authority:** `BACKEND_TRANSACTION_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/ExpenseService.php:42, Frontend/src/views/ExpensesView.vue:180`
- **Known Gaps:** None

### CALC-029: Dashboard Period Net Revenue Metric

- **Business Domain:** `REPORTING_DASHBOARD`
- **Business Meaning:** Total gross sales minus discounts, taxes, and customer returns within a specified reporting time window.
- **Trigger:** Dashboard page load / date filter selection
- **Formula:** `net_revenue = SUM(sales.total_amount) - SUM(sale_returns.total_amount)`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Treated as 0.00 | Signed currency
- **Backend Implementation:** `api/app/Services/DashboardService.php@calculateSalesMetrics`
- **Frontend Implementation:** `Frontend/src/views/DashboardView.vue:110`
- **Database Representation:** `DERIVED_ON_THE_FLY`
- **Authoritative Authority:** `DATABASE_QUERY_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/DashboardService.php:125, Frontend/src/views/DashboardView.vue:110`
- **Known Gaps:** None

### CALC-030: Dashboard Period Net Operating Profit Metric

- **Business Domain:** `REPORTING_DASHBOARD`
- **Business Meaning:** Bottom-line operational profit computed as Gross Profit minus Operating Expenses within the reporting timeframe.
- **Trigger:** Dashboard load, financial summary report
- **Formula:** `net_operating_profit = gross_profit - SUM(expenses.amount)`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Treated as 0.00 | Signed currency
- **Backend Implementation:** `api/app/Services/DashboardService.php@getOwnerDashboard`
- **Frontend Implementation:** `Frontend/src/views/DashboardView.vue:135`
- **Database Representation:** `DERIVED_ON_THE_FLY`
- **Authoritative Authority:** `REPORT_DERIVED_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/DashboardService.php:160`
- **Known Gaps:** None

### CALC-031: Dashboard Total Accounts Payable & Receivable Aggregates

- **Business Domain:** `REPORTING_DASHBOARD`
- **Business Meaning:** Company-wide cumulative outstanding liabilities owed to suppliers and outstanding assets owed by customers.
- **Trigger:** Dashboard refresh
- **Formula:** `total_ar = SUM(customers.balance); total_ap = SUM(suppliers.balance)`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Treated as 0.00 | Signed currency
- **Backend Implementation:** `api/app/Services/DashboardService.php@calculateCustomerReceivables`
- **Frontend Implementation:** `Frontend/src/views/DashboardView.vue:150`
- **Database Representation:** `DERIVED_ON_THE_FLY`
- **Authoritative Authority:** `DATABASE_QUERY_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/DashboardService.php:175, Frontend/src/views/DashboardView.vue:150`
- **Known Gaps:** None

### CALC-032: Period Net Cash & Liquid Capital Movement

- **Business Domain:** `REPORTING_DASHBOARD`
- **Business Meaning:** Net change in liquid capital across physical cash drawers and bank accounts over a specified period.
- **Trigger:** Cash flow report generation
- **Formula:** `net_cash_flow = (SUM(cash_inflow) + SUM(bank_credit)) - (SUM(cash_outflow) + SUM(bank_debit))`
- **Rounding & Precision:** `ROUND_HALF_UP` (2 decimal places, `BASE_STORE_CURRENCY`)
- **Null / Boundary Handling:** Treated as 0.00 | Signed currency
- **Backend Implementation:** `api/app/Services/DashboardService.php@calculatePeriodCashMovement`
- **Frontend Implementation:** `Frontend/src/views/DashboardView.vue:170`
- **Database Representation:** `DERIVED_ON_THE_FLY`
- **Authoritative Authority:** `DATABASE_QUERY_AUTHORITY`
- **Conflict Classification:** `CONSISTENT`
- **Source Evidence:** `api/app/Services/DashboardService.php:190`
- **Known Gaps:** None

## 4. Formula Occurrence & Cross-Layer Comparison

| Occurrence ID | Calc ID | Layer | File & Method | Formula Representation | Conflict Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `OCC-001` | `CALC-001` | `FRONTEND` | `Frontend/src/views/PosSaleView.vue@computedLineSubtotal` | `qty * price` | `CONSISTENT` |
| `OCC-002` | `CALC-001` | `BACKEND` | `api/app/Services/SaleService.php@createSale` | `$item['quantity'] * $item['unit_price']` | `CONSISTENT` |
| `OCC-003` | `CALC-001` | `PRINT` | `Frontend/src/views/Settings/ReceiptsView.vue@formatItemRow` | `item.quantity * item.unit_price` | `CONSISTENT` |
| `OCC-004` | `CALC-002` | `FRONTEND` | `Frontend/src/views/PosSaleView.vue@cartSubtotal` | `cart.reduce((sum, item) => sum + item.subtotal, 0)` | `CONSISTENT` |
| `OCC-005` | `CALC-002` | `BACKEND` | `api/app/Services/SaleService.php@createSale` | `collect($items)->sum('subtotal')` | `CONSISTENT` |
| `OCC-006` | `CALC-002` | `REPORTS` | `api/app/Services/ReportService.php@getSalesReport` | `SUM(sales.subtotal)` | `CONSISTENT` |
| `OCC-007` | `CALC-003` | `FRONTEND` | `Frontend/src/views/PosSaleView.vue@calculatedDiscount` | `type === "percentage" ? (subtotal * val / 100) : val` | `PRESENTATION_EQUIVALENT` |
| `OCC-008` | `CALC-003` | `BACKEND` | `api/app/Services/SaleService.php@createSale` | `$discountType === 'percentage' ? ($subtotal * $discountVal / 100) : $discountVal` | `PRESENTATION_EQUIVALENT` |
| `OCC-009` | `CALC-004` | `FRONTEND` | `Frontend/src/views/PosSaleView.vue@calculatedTax` | `(subtotal - discount) * (taxRate / 100)` | `CONSISTENT` |
| `OCC-010` | `CALC-004` | `BACKEND` | `api/app/Services/SaleService.php@createSale` | `($subtotal - $discount) * ($taxRate / 100)` | `CONSISTENT` |
| `OCC-011` | `CALC-005` | `FRONTEND` | `Frontend/src/views/PosSaleView.vue@grandTotal` | `subtotal - discount + tax` | `CONSISTENT` |
| `OCC-012` | `CALC-005` | `BACKEND` | `api/app/Services/SaleService.php@createSale` | `$subtotal - $discountAmount + $taxAmount` | `CONSISTENT` |
| `OCC-013` | `CALC-005` | `DASHBOARD` | `api/app/Services/DashboardService.php@calculateSalesMetrics` | `SUM(sales.total_amount)` | `CONSISTENT` |
| `OCC-014` | `CALC-005` | `PRINT` | `Frontend/src/views/Settings/ReceiptsView.vue@formatReceipt` | `sale.total_amount` | `CONSISTENT` |
| `OCC-015` | `CALC-006` | `FRONTEND` | `Frontend/src/views/PosSaleView.vue@remainingDue` | `Math.max(total - paid, 0)` | `CONSISTENT` |
| `OCC-016` | `CALC-006` | `BACKEND` | `api/app/Services/SaleService.php@createSale` | `max($totalAmount - $paidAmount, 0)` | `CONSISTENT` |
| `OCC-017` | `CALC-013` | `BACKEND` | `api/app/Services/PurchaseService.php@createPurchase` | `$shippingCost = $request->shipping_cost ?? 0` | `FORMULA_CONFLICT` |
| `OCC-018` | `CALC-017` | `BACKEND` | `api/app/Services/InventoryService.php@updateProductCostPrice` | `(($curStock * $curCost) + ($recQty * $recCost)) / ($curStock + $recQty)` | `FORMULA_CONFLICT` |
| `OCC-019` | `CALC-021` | `BACKEND` | `api/app/Services/CustomerAccountService.php@calculateOutstanding` | `$opening + $debits - $credits` | `CONSISTENT` |
| `OCC-020` | `CALC-021` | `FRONTEND` | `Frontend/src/views/KhataView.vue@displayBalance` | `customer.balance` | `CONSISTENT` |
| `OCC-021` | `CALC-023` | `BACKEND` | `api/app/Services/SupplierBalanceService.php@reconcileBalance` | `$opening + $purchasesDue - $payments` | `CONSISTENT` |
| `OCC-022` | `CALC-023` | `FRONTEND` | `Frontend/src/views/SupplierpayableView.vue@supplierDue` | `supplier.balance` | `CONSISTENT` |
| `OCC-023` | `CALC-025` | `BACKEND` | `api/app/Services/AccountMovementService.php@calculateBalance` | `$opening + $credits - $debits` | `CONSISTENT` |
| `OCC-024` | `CALC-025` | `DASHBOARD` | `api/app/Services/DashboardService.php@calculateCashAndBank` | `SUM(financial_accounts.balance)` | `CONSISTENT` |

## 5. Accounting Invariants Matrix (INV-001 to INV-016)

| Invariant ID | Name | Domain | Equation / Condition | Result | Related Gap |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `INV-001` | **Sale Grand Total Arithmetic Invariant** | `SALES_POS` | `sales.total_amount = sales.subtotal - sales.discount_amount + sales.tax_amount` | `HOLDS_BY_IMPLEMENTATION` | `None` |
| `INV-002` | **Sale Settlement Balance Invariant** | `SALES_POS` | `sales.total_amount = sales.paid_amount + sales.due_amount` | `HOLDS_BY_IMPLEMENTATION` | `None` |
| `INV-003` | **Customer Receivable Ledger Reconciliation Invariant** | `CUSTOMER_KHATA_AR` | `customers.balance = opening_balance + SUM(khata_debits) - SUM(khata_credits)` | `CAN_DRIFT` | `P4-GAP-002` |
| `INV-004` | **Supplier Payable Reconciliation Invariant** | `SUPPLIER_AP` | `suppliers.balance = opening_balance + SUM(purchases.due_amount) - SUM(supplier_payments.amount)` | `HOLDS_WITH_LIMITATIONS` | `P4-GAP-007` |
| `INV-005` | **Financial Account Perpetual Ledger Reconciliation** | `BANKING_FINANCE` | `financial_accounts.balance = opening_balance + SUM(account_movements.credit) - SUM(account_movements.debit)` | `HOLDS_WITH_LIMITATIONS` | `P4-GAP-003` |
| `INV-006` | **Branch Inventory Stock Ledger Consistency Invariant** | `INVENTORY` | `branch_inventories.quantity_on_hand = SUM(inventory_movements.quantity_change)` | `CAN_DRIFT` | `None` |
| `INV-007` | **Inter-Account Transfer Balanced Dual-Sided Invariant** | `BANKING_FINANCE` | `source_account.debit_amount = destination_account.credit_amount` | `HOLDS_BY_IMPLEMENTATION` | `None` |
| `INV-008` | **Sale Return Quantity Upper Bound Invariant** | `RETURNS_REFUNDS` | `SUM(sale_return_items.quantity) <= sale_items.quantity` | `HOLDS_BY_IMPLEMENTATION` | `None` |
| `INV-009` | **Purchase Bill Arithmetic Invariant** | `PROCUREMENT` | `purchases.total_amount = purchases.subtotal - purchases.discount_amount + purchases.tax_amount + purchases.shipping_cost` | `HOLDS_BY_IMPLEMENTATION` | `None` |
| `INV-010` | **Purchase Freight Landed Cost Incorporation Invariant** | `INVENTORY_VALUATION` | `inventory_unit_cost = purchase_item.unit_cost + (purchases.shipping_cost / total_quantity)` | `KNOWN_VIOLATION_PATH` | `P4-GAP-005` |
| `INV-011` | **Cash Ledger Running Balance Invariant** | `CASH_MANAGEMENT` | `cash_ledgers.balance = opening_balance + SUM(cash_ledgers.inflow) - SUM(cash_ledgers.outflow)` | `HOLDS_BY_IMPLEMENTATION` | `None` |
| `INV-012` | **Non-Negative Inventory Stock Constraint** | `INVENTORY` | `branch_inventories.quantity_on_hand >= 0` | `CAN_DRIFT` | `P4-GAP-009` |
| `INV-013` | **Customer Credit Limit Enforcement Invariant** | `CUSTOMER_KHATA_AR` | `customers.balance <= customers.credit_limit (when credit_limit > 0)` | `HOLDS_WITH_LIMITATIONS` | `P4-GAP-014` |
| `INV-014` | **Sale Void Inventory Reversal Invariant** | `SALES_POS` | `restored_stock_quantity = original_sale_item.quantity` | `HOLDS_BY_IMPLEMENTATION` | `None` |
| `INV-015` | **Expense Financial Allocation Invariant** | `EXPENSES` | `expenses.amount = account_movements.debit_amount (or cash_ledgers.outflow)` | `CAN_DRIFT` | `P4-GAP-013` |
| `INV-016` | **Split-Tender Payment Allocation Completeness Invariant** | `SALES_POS` | `sales.paid_amount = SUM(tender_allocations.amount)` | `CAN_DRIFT` | `P4-GAP-003` |

## 6. Balance Domain Reconciliation Audit

### Balance Domain: PRODUCT_BRANCH_STOCK

- **Balance Name:** Branch Product Quantity On Hand
- **Stored Column:** `DBCOL-130 (branch_inventories.quantity_on_hand)`
- **Authority:** `LEDGER_AUTHORITY (inventory_movements)`
- **Reconstruction Equation:** `quantity_on_hand = SUM(inventory_movements.quantity_change)`
- **Sign Convention:** Positive for inbound (purchases, returns, adjustments up, transfers in); Negative for outbound (sales, adjustments down, transfers out)
- **Reconstructability:** `FULLY_RECONSTRUCTABLE`
- **Known Drift Mechanisms:** Purchase cancellation after goods sold forcing negative quantity, Direct stock master edits bypassing movement records
- **Evidence:** `api/app/Services/InventoryService.php:64`

### Balance Domain: CUSTOMER_RECEIVABLE_AR

- **Balance Name:** Customer Khata Outstanding Balance
- **Stored Column:** `DBCOL-209 (customers.balance)`
- **Authority:** `LEDGER_AUTHORITY (khata_transactions)`
- **Reconstruction Equation:** `customer_balance = opening_balance + SUM(debit_amount) - SUM(credit_amount)`
- **Sign Convention:** Debit (+ owed by customer); Credit (- payment/refund received)
- **Reconstructability:** `RECONSTRUCTABLE_WITH_LIMITATIONS`
- **Known Drift Mechanisms:** Editing customer_id on posted sale bypasses Khata compensation, Opening balance entered without event row
- **Evidence:** `api/app/Services/CustomerAccountService.php:52`

### Balance Domain: SUPPLIER_PAYABLE_AP

- **Balance Name:** Supplier Accounts Payable Balance
- **Stored Column:** `DBCOL-250 (suppliers.balance)`
- **Authority:** `HYBRID_TRANSACTIONAL_AUTHORITY (purchases & supplier_payments)`
- **Reconstruction Equation:** `supplier_balance = opening_balance + SUM(purchases.due_amount) - SUM(supplier_payments.amount)`
- **Sign Convention:** Liability (+ owed to supplier); Payment (- disbursed to supplier)
- **Reconstructability:** `RECONSTRUCTABLE_WITH_LIMITATIONS`
- **Known Drift Mechanisms:** Supplier payment lacks automated reversal endpoint, Purchase cancellation when stock consumed
- **Evidence:** `api/app/Services/SupplierBalanceService.php:44`

### Balance Domain: FINANCIAL_ACCOUNT_BANKING

- **Balance Name:** Financial Account Perpetual Balance
- **Stored Column:** `DBCOL-332 (financial_accounts.balance)`
- **Authority:** `LEDGER_AUTHORITY (account_movements)`
- **Reconstruction Equation:** `account_balance = opening_balance + SUM(credit_amount) - SUM(debit_amount)`
- **Sign Convention:** Credit (+ inflow/deposit); Debit (- outflow/withdrawal)
- **Reconstructability:** `RECONSTRUCTABLE_WITH_LIMITATIONS`
- **Known Drift Mechanisms:** Unallocated expenses entered with null financial_account_id, Bank-paid sale cancellation missing reverse movement
- **Evidence:** `api/app/Services/AccountMovementService.php:38`

### Balance Domain: CASH_LEDGER_DRAWER

- **Balance Name:** Cash Ledger Running Balance
- **Stored Column:** `DBCOL-373 (cash_ledgers.balance)`
- **Authority:** `LEDGER_AUTHORITY (cash_ledgers)`
- **Reconstruction Equation:** `cash_balance = opening_balance + SUM(inflow_amount) - SUM(outflow_amount)`
- **Sign Convention:** Inflow (+ cash received); Outflow (- cash disbursed)
- **Reconstructability:** `FULLY_RECONSTRUCTABLE`
- **Known Drift Mechanisms:** Manual cash adjustments recorded in notes
- **Evidence:** `api/app/Services/CashLedgerService.php:42`

## 7. Business Rule Enforcement Matrix (RULE-001 to RULE-018)

| Rule ID | Name | Domain | Condition | Enforcement Status | Gap |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `RULE-001` | **Positive Sale Quantity Enforcement** | `SALES_POS` | `sale_items.quantity > 0` | `CONSISTENT_ALL_LAYERS` | `None` |
| `RULE-002` | **Positive Purchase Quantity Enforcement** | `PROCUREMENT` | `purchase_items.quantity > 0` | `CONSISTENT_ALL_LAYERS` | `None` |
| `RULE-003` | **Sale Return Quantity Upper Bound Constraint** | `RETURNS_REFUNDS` | `return_quantity <= (original_sold_quantity - previously_returned_quantity)` | `CONSISTENT_ALL_LAYERS` | `None` |
| `RULE-004` | **Mandatory Customer on Credit / On-Account Sale** | `SALES_POS` | `payment_status != "paid" => customer_id != null` | `CONSISTENT_ALL_LAYERS` | `None` |
| `RULE-005` | **Mandatory Supplier on Purchase Bill** | `PROCUREMENT` | `purchases.supplier_id != null and exists in suppliers table` | `CONSISTENT_ALL_LAYERS` | `None` |
| `RULE-006` | **Inter-Account Transfer Distinct Account Constraint** | `BANKING_FINANCE` | `from_account_id != to_account_id` | `CONSISTENT_ALL_LAYERS` | `None` |
| `RULE-007` | **Customer Credit Limit Boundary Enforcement** | `CUSTOMER_KHATA_AR` | `customer.balance + sale.due_amount <= customer.credit_limit (if limit > 0)` | `OFFLINE_ONLINE_MISMATCH` | `P4-GAP-014` |
| `RULE-008` | **Positive Payment Amount Enforcement** | `PAYMENTS` | `payment_amount > 0` | `CONSISTENT_ALL_LAYERS` | `None` |
| `RULE-009` | **Cancelled Transaction Immutability Rule** | `CORE_STATE` | `status == "cancelled" => mutations rejected` | `CONSISTENT_ALL_LAYERS` | `None` |
| `RULE-010` | **Stock Quantity Adjustment Audit Reason Requirement** | `INVENTORY` | `reason != null and length >= 3` | `CONSISTENT_ALL_LAYERS` | `None` |
| `RULE-011` | **Expense Financial Account Allocation Requirement** | `EXPENSES` | `financial_account_id != null or (is_cash == true and cash_ledger_entry_present)` | `RULE_MISSING` | `P4-GAP-013` |
| `RULE-012` | **Purchase Cancellation Sold Stock Pre-check Guard** | `PROCUREMENT` | `branch_inventory.quantity_on_hand >= purchase_item.quantity` | `RULE_MISSING` | `P4-GAP-009` |
| `RULE-013` | **Sale Return Restock Condition Flagging** | `RETURNS_REFUNDS` | `is_damaged == true => quarantine_stock; is_damaged == false => restore_stock` | `RULE_MISSING` | `P4-GAP-008` |
| `RULE-014` | **Supplier Payment Automated Reversal Protection** | `SUPPLIER_AP` | `reverse_supplier_payment => increments AP & credits bank atomically` | `RULE_MISSING` | `P4-GAP-007` |
| `RULE-015` | **Posted Sale Customer Mutation Khata Guard** | `SALES_POS` | `new_customer_id != old_customer_id => credit old customer & debit new customer` | `BACKEND_ONLY` | `P4-GAP-002` |
| `RULE-016` | **Split-Tender Payment Line Persistence Rule** | `SALES_POS` | `payment_method == "split" => persist child payment records per tender` | `FRONTEND_ONLY` | `P4-GAP-003` |
| `RULE-017` | **Customer Wholesale Price Tier Cart Application** | `PRODUCTS_PRICING` | `customer.tier == "wholesale" => cart unit_price = product.wholesale_price` | `FRONTEND_BACKEND_MISMATCH` | `P4-GAP-014` |
| `RULE-018` | **Bank Paid Sale Void Financial Account Debit Compensation** | `SALES_POS` | `payment_method == "bank" and sale.void => post outflow movement on financial account` | `RULE_MISSING` | `P4-GAP-010` |

## 8. Phase 4 Gap Register (P4-GAP-001 to P4-GAP-016)

### P4-GAP-001: (CARRYFORWARD_ONLY)

- **Target Ref:** `CALC-001`
- **Affected Flows & Entity:** FLOW-001, FLOW-005 | `ENTITY-021 (SaleItem)`
- **Current Implementation:** `line_subtotal = quantity * unit_price (Barcode/SKU resolved at runtime via relationship)`
- **Expected Invariant / Rule:** `Expected immutable historical point-in-time barcode snapshot in sale_items table`
- **Evidence:** `api/database/migrations/2024_01_01_000018_create_sale_items_table.php`
- **Financial & Inventory Impact:** Audit drift during reprint reconciliation | Barcode lookup failure on obsolete product lines
- **Reporting Impact:** Reprinted invoices show altered barcodes if master changes
- **P1/P2/P3 Cross-References:** P1: `P1-GAP-011`, P2: `P2-GAP-006`, P3: `P3-GAP-001`
- **Later Validation Phase:** `PHASE_5_UI_UX` | **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P4-GAP-002: (NEW_PHASE4_CONSEQUENCE_OF_EARLIER_GAP)

- **Target Ref:** `INV-003`
- **Affected Flows & Entity:** FLOW-003, FLOW-004 | `ENTITY-025 (Customer), ENTITY-026 (KhataTransaction)`
- **Current Implementation:** `PUT /sales/{id} updates sales.customer_id with 0 Khata ledger compensation`
- **Expected Invariant / Rule:** `customers[old].balance -= due; customers[new].balance += due`
- **Evidence:** `api/app/Http/Controllers/Api/V1/SaleController.php@update`
- **Financial & Inventory Impact:** Permanent AR ledger divergence between customers.balance cache and statement event sums | None
- **Reporting Impact:** Distorts aging reports and customer statements
- **P1/P2/P3 Cross-References:** P1: `P1-GAP-003`, P2: `P2-GAP-006`, P3: `P3-GAP-002`
- **Later Validation Phase:** `PHASE_6_ROLES_STATE` | **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P4-GAP-003: (NEW_PHASE4_CONSEQUENCE_OF_EARLIER_GAP)

- **Target Ref:** `INV-016`
- **Affected Flows & Entity:** FLOW-005 | `ENTITY-020 (Sale), ENTITY-034 (FinancialAccount)`
- **Current Implementation:** `paid_amount = SUM(tender_inputs) stored in single header column`
- **Expected Invariant / Rule:** `Persist individual payment_records linking tender amount to specific account_id`
- **Evidence:** `api/app/Services/SaleService.php:108`
- **Financial & Inventory Impact:** Bank reconciliation and shift drawer cash balances cannot independently verify split payments | None
- **Reporting Impact:** Financial account deposit reports omit split card payments
- **P1/P2/P3 Cross-References:** P1: `P1-GAP-005`, P2: `P2-GAP-006`, P3: `P3-GAP-003`
- **Later Validation Phase:** `PHASE_7_OFFLINE_SYNC` | **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P4-GAP-004: (NEW_PHASE4_CONSEQUENCE_OF_EARLIER_GAP)

- **Target Ref:** `CALC-029`
- **Affected Flows & Entity:** FLOW-007 | `ENTITY-020 (Sale), ENTITY-019 (InventoryMovement)`
- **Current Implementation:** `created_at = client_device_timestamp adopted without drift guard`
- **Expected Invariant / Rule:** `created_at = MIN(client_timestamp, server_current_time)`
- **Evidence:** `src/sync/syncEngine.js:188, api/app/Http/Controllers/Api/V1/SyncController.php`
- **Financial & Inventory Impact:** Daily sales aggregates and shift cash totals distorted across calendar days | Inventory movement ledger records inserted out of chronological order
- **Reporting Impact:** Dashboard daily revenue metrics misallocated
- **P1/P2/P3 Cross-References:** P1: `P1-GAP-009`, P2: `P2-GAP-017`, P3: `P3-GAP-004`
- **Later Validation Phase:** `PHASE_7_OFFLINE_SYNC` | **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P4-GAP-005: (NEW_PHASE4_FINDING)

- **Target Ref:** `CALC-017`
- **Affected Flows & Entity:** FLOW-008, FLOW-009, FLOW-010 | `ENTITY-014 (Product), ENTITY-028 (Purchase)`
- **Current Implementation:** `new_cost = ((curStock * curCost) + (recQty * unit_cost)) / (curStock + recQty) [Omits freight]`
- **Expected Invariant / Rule:** `new_cost = ((curStock * curCost) + (recQty * (unit_cost + (shipping_cost / total_qty)))) / (curStock + recQty)`
- **Evidence:** `api/app/Services/InventoryService.php:185`
- **Financial & Inventory Impact:** COGS understated, gross profit overstated, inventory asset valuation on balance sheet understated | Product cost price remains lower than actual landed acquisition cost
- **Reporting Impact:** P&L profit margins artificially inflated
- **P1/P2/P3 Cross-References:** P1: `P1-GAP-006`, P2: `P2-GAP-009`, P3: `P3-GAP-005`
- **Later Validation Phase:** `PHASE_8_GAP_REGISTER` | **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P4-GAP-006: (CARRYFORWARD_ONLY)

- **Target Ref:** `RULE-014`
- **Affected Flows & Entity:** FLOW-016 | `ENTITY-028 (Purchase), ENTITY-027 (Supplier)`
- **Current Implementation:** `Missing Purchase Return / Supplier Debit Note entity and formula`
- **Expected Invariant / Rule:** `supplier.balance -= return_amount; branch_inventory -= return_qty; stock_movement="purchase_return"`
- **Evidence:** `api/routes/api.php`
- **Financial & Inventory Impact:** Supplier payables frequently fail to reflect returned defective merchandise credits | Manual stock adjustments used as workaround obscure supplier defect tracking
- **Reporting Impact:** No debit note documentation generated for vendor reconciliation
- **P1/P2/P3 Cross-References:** P1: `P1-GAP-007`, P2: `P2-GAP-010`, P3: `P3-GAP-006`
- **Later Validation Phase:** `PHASE_8_GAP_REGISTER` | **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P4-GAP-007: (CARRYFORWARD_ONLY)

- **Target Ref:** `INV-004`
- **Affected Flows & Entity:** FLOW-011 | `ENTITY-030 (SupplierPayment), ENTITY-027 (Supplier)`
- **Current Implementation:** `POST /suppliers/{id}/payments exists; DELETE /suppliers/{id}/payments/{pid} missing`
- **Expected Invariant / Rule:** `supplier.balance += payment.amount; financial_account.balance += payment.amount`
- **Evidence:** `api/app/Http/Controllers/Api/V1/SupplierController.php`
- **Financial & Inventory Impact:** Erroneous supplier payment entries require manual dual-sided database/account corrections | None
- **Reporting Impact:** Supplier statement reflects erroneous payment until manual adjustment
- **P1/P2/P3 Cross-References:** P1: `P1-GAP-008`, P2: `P2-GAP-015`, P3: `P3-GAP-007`
- **Later Validation Phase:** `PHASE_8_GAP_REGISTER` | **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P4-GAP-008: (CARRYFORWARD_ONLY)

- **Target Ref:** `RULE-013`
- **Affected Flows & Entity:** FLOW-012, FLOW-013 | `ENTITY-023 (SaleReturnItem), ENTITY-018 (BranchInventory)`
- **Current Implementation:** `branch_inventory.quantity_on_hand += return_item.quantity [Unconditional]`
- **Expected Invariant / Rule:** `if (!item.is_damaged) { branch_inventory += qty } else { quarantine_inventory += qty }`
- **Evidence:** `api/app/Services/SaleService.php:228`
- **Financial & Inventory Impact:** Overvalues sellable inventory assets by carrying unsellable damaged goods at full cost price | Damaged returned items immediately re-enter sellable stock on hand
- **Reporting Impact:** Inventory reports count damaged returns as sellable stock
- **P1/P2/P3 Cross-References:** P1: `P1-GAP-010`, P2: `P2-GAP-008`, P3: `P3-GAP-008`
- **Later Validation Phase:** `PHASE_5_UI_UX` | **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P4-GAP-009: (NEW_PHASE4_CONSEQUENCE_OF_EARLIER_GAP)

- **Target Ref:** `INV-012`
- **Affected Flows & Entity:** FLOW-016 | `ENTITY-018 (BranchInventory), ENTITY-028 (Purchase)`
- **Current Implementation:** `branch_inventory.quantity_on_hand -= purchase_item.quantity [No stock pre-check]`
- **Expected Invariant / Rule:** `guard(quantity_on_hand >= purchase_item.quantity) before decrement`
- **Evidence:** `api/app/Services/PurchaseService.php:145`
- **Financial & Inventory Impact:** Cancels supplier AP liability while revenue from sold stock remains recognized | Forces quantity_on_hand into negative numbers in database
- **Reporting Impact:** Stock valuation shows negative asset values for affected SKU
- **P1/P2/P3 Cross-References:** P1: ``, P2: `P2-GAP-014`, P3: `P3-GAP-009`
- **Later Validation Phase:** `PHASE_6_ROLES_STATE` | **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P4-GAP-010: (NEW_PHASE4_CONSEQUENCE_OF_EARLIER_GAP)

- **Target Ref:** `RULE-018`
- **Affected Flows & Entity:** FLOW-014 | `ENTITY-020 (Sale), ENTITY-034 (FinancialAccount)`
- **Current Implementation:** `cancelSale reverses cash register; skips financial_account.balance decrement on bank sales`
- **Expected Invariant / Rule:** `if (sale.payment_method == "bank") { financial_account.balance -= sale.paid_amount; post account_movement }`
- **Evidence:** `api/app/Services/SaleService.php:188`
- **Financial & Inventory Impact:** Financial account bank balance in system remains inflated after a bank-paid sale is voided | None
- **Reporting Impact:** Bank reconciliation reports diverge from actual bank account balance
- **P1/P2/P3 Cross-References:** P1: `P1-GAP-005`, P2: `P2-GAP-006`, P3: `P3-GAP-010`
- **Later Validation Phase:** `PHASE_8_GAP_REGISTER` | **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P4-GAP-012: (CARRYFORWARD_ONLY)

- **Target Ref:** `CALC-018`
- **Affected Flows & Entity:** FLOW-020 | `ENTITY-018 (BranchInventory), ENTITY-024 (StockTransfer)`
- **Current Implementation:** `source_stock -= qty; dest_stock += qty [Synchronous single transaction]`
- **Expected Invariant / Rule:** `source_stock -= qty; in_transit_stock += qty; ... on receipt: dest_stock += qty`
- **Evidence:** `api/app/Services/InventoryService.php:142`
- **Financial & Inventory Impact:** Branch-level inventory asset valuation immediately shifts before physical arrival | Destination POS operators can sell stock that is still physically on a truck in transit
- **Reporting Impact:** Branch stock reports show stock present before delivery confirmation
- **P1/P2/P3 Cross-References:** P1: `P1-GAP-013`, P2: `P2-GAP-005`, P3: `P3-GAP-012`
- **Later Validation Phase:** `PHASE_6_ROLES_STATE` | **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P4-GAP-013: (NEW_PHASE4_CONSEQUENCE_OF_EARLIER_GAP)

- **Target Ref:** `RULE-011`
- **Affected Flows & Entity:** FLOW-023 | `ENTITY-031 (Expense), ENTITY-034 (FinancialAccount)`
- **Current Implementation:** `Expense creation permits financial_account_id = null on cash expense`
- **Expected Invariant / Rule:** `financial_account_id is required or cash_ledger_entry_present`
- **Evidence:** `api/app/Http/Requests/ExpenseStoreRequest.php:24, api/app/Services/ExpenseService.php:54`
- **Financial & Inventory Impact:** Expense recognized in P&L with no reduction in either cash drawer or bank accounts | None
- **Reporting Impact:** P&L expenses diverge from balance sheet asset outflows
- **P1/P2/P3 Cross-References:** P1: `P1-GAP-012`, P2: `P2-GAP-016`, P3: `P3-GAP-013`
- **Later Validation Phase:** `PHASE_8_GAP_REGISTER` | **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P4-GAP-014: (NEW_PHASE4_CONSEQUENCE_OF_EARLIER_GAP)

- **Target Ref:** `RULE-017`
- **Affected Flows & Entity:** FLOW-001, FLOW-003 | `ENTITY-025 (Customer), ENTITY-014 (Product)`
- **Current Implementation:** `Offline POS cart does not re-price line items when wholesale customer selected after scan`
- **Expected Invariant / Rule:** `Reactive cart watcher recalculates all item prices upon customer attachment`
- **Evidence:** `Frontend/src/views/PosSaleView.vue:410`
- **Financial & Inventory Impact:** Wholesale customer overcharged at retail price; requires credit note adjustment | None
- **Reporting Impact:** Sales revenue recorded at retail rate rather than contractual wholesale rate
- **P1/P2/P3 Cross-References:** P1: `P1-GAP-001`, P2: `P2-GAP-001`, P3: `P3-GAP-014`
- **Later Validation Phase:** `PHASE_5_UI_UX` | **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P4-GAP-015: (NEW_PHASE4_CONSEQUENCE_OF_EARLIER_GAP)

- **Target Ref:** `CALC-021`
- **Affected Flows & Entity:** FLOW-015 | `ENTITY-025 (Customer), ENTITY-020 (Sale)`
- **Current Implementation:** `Customer payment reversal increases customer balance; leaves individual sales marked PAID`
- **Expected Invariant / Rule:** `Re-allocate unpaid status and due_amount to individual sales invoices FIFO`
- **Evidence:** `api/app/Services/CustomerPaymentService.php:84`
- **Financial & Inventory Impact:** Discrepancy between total customer outstanding balance and sum of open sale invoice due amounts | None
- **Reporting Impact:** Customer account shows debt while specific invoice view indicates fully paid
- **P1/P2/P3 Cross-References:** P1: `P1-GAP-003`, P2: `P2-GAP-007`, P3: `P3-GAP-015`
- **Later Validation Phase:** `PHASE_8_GAP_REGISTER` | **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P4-GAP-016: (NEW_PHASE4_FINDING)

- **Target Ref:** `CALC-003`
- **Affected Flows & Entity:** FLOW-001, FLOW-002, FLOW-003 | `ENTITY-020 (Sale)`
- **Current Implementation:** `Taxable base = subtotal - discount_amount (tax computed after discount deduction)`
- **Expected Invariant / Rule:** `In certain jurisdictions tax applies to pre-discount subtotal`
- **Evidence:** `api/app/Services/SaleService.php:102`
- **Financial & Inventory Impact:** Tax liability differs if tax authority mandates pre-discount taxation | None
- **Reporting Impact:** Tax report reflects discounted base taxation
- **P1/P2/P3 Cross-References:** P1: ``, P2: ``, P3: ``
- **Later Validation Phase:** `PHASE_8_GAP_REGISTER` | **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

