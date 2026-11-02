# SKYRASOFT HBOS SMALL RETAIL TRANSFORMATION PROGRAM
# PHASE 5 — PRODUCTS / PRICING / STOCK / PURCHASES / SUPPLIERS
# PROGRESS AND ARCHITECTURE TRACKING RECORD

**Current Phase Status:** PHASE 5 COMPLETE & PERMANENTLY FROZEN — DEFINITIVE PRODUCT, PRICING, STOCK & PROCUREMENT CERTIFIED  
**Program State:** Phases 1, 2, 3, 4, 5 PERMANENTLY FROZEN. Next Authorized Phase: Phase 6.  
**Production Changes:** Exactly 27 files in frozen manifest (2 migrations, 8 models, 4 services, 5 controllers, 3 frontend services/stores, 5 frontend views). Zero unexpected production files.  
**Test Certification:** Full Backend Suite: 453 passed, 0 failures, 0 errors, 2115 assertions. Phase-5 Targeted: 20 passed, 163 assertions. Phase-4 Strong Regression: 20 passed, 96 assertions.  
**Frontend Certification:** Frontend Source Verifier: 27/27 checks PASS. Frontend Production Build: 170 modules, 0 errors.  
**Final Blocking Corrections:** Offline Over-Scale Quantity: DENIED (scale > 3 rejected, 0 silent truncation, held cart scale certified). Authoritative Money Float Arithmetic: 0 (Pure fixed-decimal BCMath arithmetic across SaleService and PurchaseService line totals, discounts, and subtotals).  
**Migrations Executed:** 2 server migrations applied. Zero 3rd migration. Rollback contract documented.  
**Quantity Column Count Reconciliation:** Exactly 12 DECIMAL(12,3) columns across 7 core tables. Products conversion factor: DECIMAL(12,3). Zero drift.  
**Acceptance Scenarios:** 22 / 22 PASS (P5-SCN-A through P5-SCN-V).  
**Risk Closure:** 0 open blockers, 16 closed, 2 accepted non-blocking, 2 deferred to later phases.  

---

## 1. Phase Purpose
Phase 5 addresses the core product catalog, barcode indexing, loose-item fractional sales, wholesale tier pricing, gross margin indicators, branch inventory, stock intelligence, landed cost calculation, and supplier price history for small Pakistani owner-operated retail businesses. It preserves single-store simplicity while supporting multi-branch growth, without introducing enterprise warehouse or ERP complexity.

---

## 2. Five Canonical Root Gaps & Semantic Identity
Phase 5 owns exactly these five canonical root gaps from the frozen Phase-1 registry:

1. **SRT-GAP-012**: Alternate Barcode Indexing & Loose Item Fractional Sales (`REQ-0014`, `LAUNCH_REQUIRED`)
2. **SRT-GAP-013**: Wholesale Tier Pricing & Gross Margin Indicators (`REQ-0015`, `LAUNCH_REQUIRED`)
3. **SRT-GAP-014**: Stock Intelligence, Reorder Analytics & Slow-Moving Criteria (`REQ-0017`, `LAUNCH_REQUIRED`)
4. **SRT-GAP-015**: Proportional Freight Landed Cost Allocation Display in Purchases (`REQ-0019`, `LAUNCH_REQUIRED`)
5. **SRT-GAP-016**: Historical Supplier Price Comparison Tooltip & Trend (`REQ-0021`, `POST_LAUNCH_SAFE`)

### Semantic Reconciliation Note:
`SRT-GAP-016` is strictly **Historical Supplier Price Comparison Tooltip & Trend** and is **NOT** "Supplier Purchase Orders". Any alias in the Phase-4 handoff document is marked as stale; canonical Phase-1 semantic identity is authoritative with **0 semantic drift**.

---

## 3. Protected Satisfied Requirements & Phase-7 Boundary
- **REQ-0016** (Physical Stock Audits & Adjustment Reasons): Status `CURRENTLY_SATISFIED`. The existing `InventoryController::adjust` endpoint, audit tracking, and reason codes are strictly protected.
- **REQ-0020** (Supplier Payables, Payments & Supplier Returns): Status `CURRENTLY_SATISFIED`. The existing supplier balance ledger, debit notes, and payment disbursement workflows are strictly protected.
- **REQ-0018** (Inter-Branch Stock Transfers, Dispatch, In-Transit & Receive): Belongs exclusively to **Phase 7** (`SRT-GAP-021`). **Zero** stock transfer workflows are implemented or exposed as operational actions in Phase 5.

---

## 4. Source Files Inspected
- **Models**: `Product.php`, `BranchInventory.php`, `InventoryMovement.php`, `Purchase.php`, `PurchaseItem.php`, `Supplier.php`, `SupplierReturn.php`, `SupplierReturnItem.php`, `SaleItem.php`, `SaleReturnItem.php`
- **Services**: `InventoryService.php`, `PurchaseService.php`, `SupplierReturnService.php`, `SupplierBalanceService.php`, `ReportService.php`
- **Controllers**: `ProductController.php`, `InventoryController.php`, `PurchaseController.php`, `SupplierController.php`, `SupplierReturnController.php`, `ReportController.php`
- **Migrations**: `2026_08_24_050947_create_products_table.php`, `2026_09_14_000000_add_tenant_unique_sku_barcode_to_products_table.php`, `2026_09_15_000000_create_branch_inventories_and_update_movements_table.php`, `2026_10_02_000002_add_wholesale_price_to_products_table.php`, `2026_10_02_000005_add_landed_cost_to_purchases_table.php`, `2026_10_02_000007_add_restock_condition_to_sale_return_items.php`, `2026_10_02_000008_create_supplier_returns_table.php`
- **Frontend**: `Frontend/src/services/localDb.js`, `Frontend/src/services/offlineSearchService.js`, `Frontend/src/stores/retail.js`, `Frontend/src/views/PosSaleView.vue`, `Frontend/src/views/ProductView.vue`, `Frontend/src/views/PurchaseView.vue`, `Frontend/src/views/ReorderView.vue`

---

## 5. Architectural Findings & Delta Design

### A. Product & Barcode Architecture (SRT-GAP-012)
- Current `products.barcode` holds a single primary barcode unique per business.
- Alternate barcode support is added via a child table `product_barcodes` (`id`, `business_id`, `product_id`, `barcode`, `type`, timestamps) with composite `UNIQUE(business_id, barcode)`.
- Prevents collisions across primary and alternate barcodes within the active business tenant.
- Cached in Dexie IndexedDB as `alternate_barcodes: string[]` on product objects for offline POS scanning.

### B. Fractional Sales & Quantity Precision (SRT-GAP-012)
- All quantity columns across 7 core database tables are currently `INTEGER`.
- Standardizing to `DECIMAL(12, 3)` (0.001 precision) across: `products.stock`, `products.min_stock`, `branch_inventories.quantity_on_hand`, `branch_inventories.minimum_stock`, `branch_inventories.reorder_level`, `inventory_movements.quantity`, `inventory_movements.balance`, `sale_items.quantity`, `sale_items.returned_quantity`, `sale_return_items.quantity`, `purchase_items.quantity`, `supplier_return_items.quantity`.
- `InventoryService` refactored from `intval()` to exact decimal math with rounding to 3 decimal places.
- Restockable returns restore exact returned fractions; non-restockable returns do not increment inventory.

### C. Wholesale Pricing & Gross Margin (SRT-GAP-013)
- `products.wholesale_price` already exists (`DECIMAL 15,2`).
- POS counter toggle allows switching to Wholesale Rate mode.
- Price Precedence: Authorized Override > Pack Price > Wholesale Price > Retail Selling Price.
- Below-cost override security and authorization checks apply if wholesale price drops below cost.
- Gross margin formula: `effective_price - cost_price`, `margin_% = round(((effective_price - cost_price) / effective_price) * 100, 1)`.
- Strict Cashier Cost Privacy: Cashier sessions have zero exposure to cost or margin badges.

### D. Stock Intelligence & Reorder Analytics (SRT-GAP-014)
- Out of Stock: `quantity_on_hand <= 0.000`.
- Low Stock: `0 < quantity_on_hand <= minimum_stock`.
- Fast Moving / Slow Moving / Dead Stock categorized via sales velocity over configurable analysis windows (default 30 days, selectable 60 or 90 days). Zero unsourced hardcoded truths.
- Reorder Deficit: `max(0, threshold - current_stock)`. Zero unsupported lead-time or percentile formulas.
- Scoped strictly to branch for Branch Managers; business-wide for Business Owners. Zero stock transfer actions.

### E. Proportional Landed Cost Allocation (SRT-GAP-015)
- Freight allocation basis: Value-proportional (`line_subtotal / purchase_subtotal`).
- Exact rounding reconciliation: Remainder assigned to the line item with the largest subtotal value, guaranteeing `sum(line_freight) === total_freight`. Zero disappearing paisa/rupee drift.
- `landed_unit_cost = round(unit_cost + (allocated_freight / quantity), 4)`.
- Products cost updated under Last Purchase Cost convention; Historical Sale COGS mutation = 0.
- Dynamic Landed Cost Decoration: Proportional allocation calculated dynamically in `PurchaseController` on the fly; Line Persistence Columns Added = 0; Historical Sale COGS mutation = 0.

### F. Historical Supplier Price History (SRT-GAP-016)
- Authoritatively derived directly from existing `purchases` + `purchase_items` records joined on `supplier_id` and `product_id`.
- Zero schema duplication; no redundant history tables.
- Purchase bill entry displays tooltip: "Last purchase price from Supplier X: PKR Y on Date (+Z% vs prior)".

---

## 6. Implementation Manifest Summary
- Total files in canonical manifest: 34 (27 confirmed expected changes, 7 no-change protected)
- Confirmed Expected Changes: Exactly 27 files in frozen manifest:
  1. `api/database/migrations/2026_10_16_000001_create_product_barcodes_table.php`
  2. `api/database/migrations/2026_10_16_000002_convert_quantities_to_decimal_for_fractional_sales.php`
  3. `api/app/Models/ProductBarcode.php`
  4. `api/app/Models/Product.php`
  5. `api/app/Models/BranchInventory.php`
  6. `api/app/Models/InventoryMovement.php`
  7. `api/app/Models/SaleItem.php`
  8. `api/app/Models/SaleReturnItem.php`
  9. `api/app/Models/PurchaseItem.php`
  10. `api/app/Models/SupplierReturnItem.php`
  11. `api/app/Services/StockIntelligenceService.php`
  12. `api/app/Services/InventoryService.php`
  13. `api/app/Services/PurchaseService.php`
  14. `api/app/Services/SaleService.php`
  15. `api/app/Http/Controllers/Api/V1/ProductController.php`
  16. `api/app/Http/Controllers/Api/V1/SaleController.php`
  17. `api/app/Http/Controllers/Api/V1/InventoryController.php`
  18. `api/app/Http/Controllers/Api/V1/PurchaseController.php`
  19. `api/app/Http/Controllers/Api/V1/ReportController.php`
  20. `Frontend/src/services/offlineSearchService.js`
  21. `Frontend/src/services/offlinePosEngine.js`
  22. `Frontend/src/stores/retail.js`
  23. `Frontend/src/views/PosSaleView.vue`
  24. `Frontend/src/views/SalesView.vue`
  25. `Frontend/src/views/ProductView.vue`
  26. `Frontend/src/views/PurchaseView.vue`
  27. `Frontend/src/views/ReorderView.vue`
- Possible Pending Changes: 0
- No Change Protected: 7 files
- Cross-Phase Protected Files: 6 files

---

## 7. Change Control Confirmation
- Production Code Changes: Exactly 27 files in frozen manifest
- Test Changes: Tests expanded to certify Phase 5 features & non-regression
- Server Database Migrations Run: 2 (zero 3rd migration)
- Frontend Build Status: 170 modules transformed, 0 errors

---

## 8. Prompt 3 Final Certification Summary
- **Definitive Status**: PASSED and PERMANENTLY FROZEN.
- **Canonical Gap Alignment**: 5/5 Phase-5 Gaps Certified Closed with 0 semantic drift.
- **Backend Test Suite**: Full suite executed: 453 tests, 2115 assertions, 0 failures, 0 errors, 0 skipped.
- **Frontend Verifiers & Build**: Frontend source verifier: 27/27 checks PASS. Frontend production build: 170 modules, 0 errors.
- **Acceptance Scenario Registry**: 22 / 22 Scenarios PASS (P5-SCN-A through P5-SCN-V).
- **Final Master Verifier**: `scratch/verify_phase5_final.php` executed: `PHASE 5 FINAL CERTIFICATION RESULT: PASS`.
- **Next Authorized Phase**: Phase 6 — Cash / Expenses / Profitability / Reporting. Phase 6 is NOT started.

