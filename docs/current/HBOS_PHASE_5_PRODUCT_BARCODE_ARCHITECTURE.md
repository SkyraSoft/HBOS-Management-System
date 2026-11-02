# HBOS PHASE 5 — PRODUCT CATALOG & BARCODE ARCHITECTURE
## SPECIFICATION & CONTRACT DOCUMENT

**Program:** SkyraSoft HBOS Small Retail Transformation Program  
**Phase:** Phase 5 — Products / Pricing / Stock / Purchases / Suppliers  
**Gap Ownership:** SRT-GAP-012 (Alternate Barcode Indexing & Loose Item Fractional Sales)  
**Linked Canonical Requirements:** REQ-0014, REQ-0015  

---

## 1. Product Identifier & Master Catalog Architecture
The HBOS product catalog operates as a unified, business-wide master catalog shared across all branch locations:
- **Product ID (`products.id`)**: Global auto-increment primary key.
- **SKU (`products.sku`)**: Unique inventory identifier scoped per business tenant (`UNIQUE(business_id, sku)`).
- **Primary Barcode (`products.barcode`)**: Canonical manufacturer or store barcode (`UNIQUE(business_id, barcode)`).
- **Unit (`products.unit`)**: String identifier representing base sales unit (`pcs`, `kg`, `ltr`, `mtr`, `box`, `pack`).
- **Active Status (`products.is_active`)**: Boolean flag controlling catalog availability.

---

## 2. Alternate Barcode Indexing Model (`product_barcodes`)
Many consumer goods in Pakistani retail feature multiple valid packaging barcodes (e.g., imported packaging variations, multi-distributor labels, or promotional packs) that map to the identical inventory item. Phase 5 introduces child table indexing:

### Table Definition: `product_barcodes`
```sql
CREATE TABLE product_barcodes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    barcode VARCHAR(100) NOT NULL,
    type VARCHAR(30) DEFAULT 'alternate',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_pbar_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    CONSTRAINT fk_pbar_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT uq_pbar_tenant_barcode UNIQUE (business_id, barcode)
);
```

### Uniqueness and Collision Prevention Contract
1. **Tenant Isolation**: Every barcode is unique per business. Unrelated business tenants may use identical barcodes without conflict.
2. **Deterministic Lookup Precedence**:
   - Step 1: Query `products` where `business_id = ?` and `barcode = ?`.
   - Step 2: Query `product_barcodes` where `business_id = ?` and `barcode = ?` -> resolves to parent `product_id`.
   - Step 3: Substring search on `products.sku` or `products.name`.
3. **Collision Enforcement**:
   - Backend validation verifies that a barcode cannot exist simultaneously as a primary barcode and an alternate barcode in the same business.
   - An alternate barcode cannot be mapped to two distinct products within the same business.

---

## 3. Offline Barcode Lookup Architecture
The HBOS POS counter operates offline via client-side IndexedDB (`HBOS_Store_Biz_${business_id}`).
1. **Cached Data Representation**:
   When syncing products to the local database, each product entity contains an array attribute:
   ```json
   {
     "id": 105,
     "name": "Dalda Cooking Oil 1L",
     "sku": "OIL-DAL-1L",
     "barcode": "896101400201",
     "alternate_barcodes": ["896101400202", "896101400203"],
     "price": 520.00,
     "stock": 42.000,
     "unit": "ltr"
   }
   ```
2. **Scanner Search Logic (`offlineSearchService.js`)**:
   ```javascript
   const barcodeMatch = (p.barcode && p.barcode.toLowerCase() === q) ||
     (p.alternate_barcodes && p.alternate_barcodes.some(b => b.toLowerCase() === q));
   ```
   Ensures instantaneous scanner resolution (< 5ms) whether connected or disconnected.

---

## 4. Loose-Item Fractional Quantity Architecture
To support bulk commodities sold by weight, volume, or length (e.g., 0.5 kg sugar, 1.25 L oil, 2.5 m fabric), all quantity columns are migrated from `INTEGER` to `DECIMAL(12, 3)`:

### Migrated Tables & Columns
- `products`: `stock`, `min_stock`
- `branch_inventories`: `quantity_on_hand`, `minimum_stock`, `reorder_level`
- `inventory_movements`: `quantity`, `balance`
- `sale_items`: `quantity`, `returned_quantity`
- `sale_return_items`: `quantity`
- `purchase_items`: `quantity`
- `supplier_return_items`: `quantity`

### Precision & Stock Movement Invariants
- **Database Precision**: 3 decimal places (`0.001`).
- **Minimum Positive Quantity**: `0.001`.
- **Atomic Decrement**: `InventoryService::issueStock` verifies available decimal stock using `lockForUpdate()`. If available is 10.000 and requested is 1.250, new balance is 8.750.
- **Restockable Returns**: Exact decimal fraction returned restores sellable branch inventory. Non-restockable returns do not increment inventory.
- **Zero Floating-Point Drift**: Exact mathematical rounding avoiding IEEE 754 binary floating-point representation leaks.

---

## 5. Phase-4 Non-Regression
- **Barcode Label Sheet Generator**: Preserved in `ReceiptsView.vue`. Prints primary barcode and SKU by default.
- **Held Carts & Split Payments**: Fractional quantities serialize cleanly into held carts and split tender payloads.
