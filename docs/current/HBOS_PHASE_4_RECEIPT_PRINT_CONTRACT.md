# HBOS Phase 4 — Receipt & Print Contract
**Program:** SkyraSoft HBOS Small Retail Transformation Program  
**Phase:** 4 (Sales / POS / Customers / Khata / Returns)  
**Document Status:** FROZEN DESIGN CONTRACT  

---

## 1. 58mm vs 80mm Thermal Receipt Specifications (SRT-GAP-011 / REQ-0031)
HBOS receipts are rendered via an optimized hidden browser iframe applying native CSS print media rules:
- **80mm Standard POS Width:**
  - CSS container width: `290px` (approx 72mm printable area)
  - Typography: 11.5px Inter/sans-serif with 42-48 columns
  - Ideal for standard desktop POS printers (EPSON, Xprinter, Bixolon).
- **58mm Compact POS Width:**
  - CSS container width: `196px` (approx 48mm printable area)
  - Typography: 10px compact font with 30-32 columns and condensed spacing
  - Ideal for compact 2-inch counter terminals and mobile Bluetooth printers.
- **Configuration Hierarchy:**
  - Primary setting loaded from `business.settings.receipt_settings.paperSize`.
  - Terminal-specific override stored in client `localStorage.getItem('hbos_receipt_paper_size')` allowing different registers in the same shop to use different printer roll sizes.

---

## 2. Receipt Barcode & Re-Lookup Contract
- **Encoding Standard:** Native high-contrast SVG rendering of Code 128 encoding the exact `invoice_number` (e.g. `INV-20261009-84729104`).
- **Primary Function:** Fast receipt lookup and return processing. When a customer returns goods, the cashier scans this barcode at the POS counter to populate the return workflow instantly.
- **Distinction from Product Labels:** The invoice lookup barcode identifies an entire historical transaction on a printed receipt chit. Product barcode labels identify physical items on shelves.

---

## 3. Product Barcode Label Sheet Generator (SRT-GAP-011 / REQ-0031)
- **Canonical Gap Owner:** **PHASE 4** (`SRT-GAP-011 / REQ-0031`).
- **Component Placement:** Hosted in `Frontend/src/views/Settings/ReceiptsView.vue`.
- **Workflow:**
  1. Cashier/Manager selects existing product(s) from the catalog search.
  2. Specifies the number of labels required per product (e.g. 24 labels for a newly arrived carton).
  3. Previews a printable grid (standard A4 or continuous sticker roll sheets).
  4. Renders crisp Code 128 barcodes embedding the existing product barcode/SKU alongside product name and selling price.
  5. Prints using browser `window.print()` (100% offline-capable from cached catalog).
- **Phase Boundary Clarification:** Phase 4 owns standard barcode label sheet generation from existing product records. Phase 5 (`SRT-GAP-012`) owns alternate barcode indexing and fractional loose-item barcodes. Cross-phase ownership conflict = 0.

---

## 4. Receipt Content & Return Policy Footer
Receipt chits dynamically render:
1. **Header:** Configured Store Name, Branch Address, Store Contact Number, NTN/STRN tax registration number.
2. **Metadata:** Unique Invoice Number, Date/Time (PKT), Cashier Name, Customer Name.
3. **Itemized Table:** Item name with variant/pack, Quantity, Effective Unit Price, Net Line Amount.
4. **Summary:** Subtotal, Line & Order Discounts, Tax, Grand Total (PKR).
5. **Payment Breakdown:** Breakdown per applied tender (Cash, Card, JazzCash, Easypaisa, Khata Credit), Cash Tendered, Change Given, Customer's updated Khata balance.
6. **Configurable Return Policy Footer:** Configured in `ReceiptsView.vue` and saved to `business.settings.receipt_settings.return_policy` (e.g. "Returns accepted within 7 days with original receipt. Damaged goods are non-refundable.").

---

## 5. Reprint Immutability & Printer Failure Safety
- **Immutability:** Reprinting an old receipt from Sales History is strictly read-only. It reads the historical database snapshot without executing any state-mutating API calls, inventory adjustments, or ledger postings.
- **Failure Resilience:** If a physical printer jams or runs out of thermal paper, the completed sale is already safely committed to the database. The cashier can immediately trigger a reprint without duplicating the financial transaction.
