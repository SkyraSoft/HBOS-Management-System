# HBOS Phase 4 — Sales & POS Architecture Contract
**Program:** SkyraSoft HBOS Small Retail Transformation Program  
**Phase:** 4 (Sales / POS / Customers / Khata / Returns)  
**Document Status:** FROZEN DESIGN CONTRACT  

---

## 1. Executive Summary
This document establishes the end-to-end architecture for the daily retail sales counter in HBOS. Designed specifically for Pakistani single-store owners and simple multi-branch retail setups, it emphasizes counter velocity, barcode scanning efficiency, cash change accuracy, safe permission boundaries, and zero-loss financial integrity.

---

## 2. Core POS Component Layout
The checkout counter (`Frontend/src/views/PosSaleView.vue`) consists of four functional quadrants:
1. **Catalog & Search Panel:** Fast product search by title, barcode, and category pills; product cards with dynamic pack variants (Single, Box, Carton) and live stock counts.
2. **Order Summary (Cart Panel):** Active line items with inline quantity increment/decrement, pack type indicators, line total, order discount input, and customer selector.
3. **Checkout & Tender Panel:** Multi-tender split modal, quick cash tender buttons (e.g. 500, 1000, 5000 PKR chits), automated change calculator, and Khata balance indicator.
4. **Post-Sale Actions & Receipt Overlay:** Instant receipt print trigger, WhatsApp sharing, return processing, and clear counter reset for the next customer.

---

## 3. Authoritative Sale Lineage
```
[Scan / Click Product]
       │
       ▼
[Update Pinia Cart State] (store.cart)
       │
       ▼
[Apply Discounts / Overrides] 
   ├── Direct Owner / Permission Check: Allowed
   └── Cashier Inline Delegation: Challenges Manager/Owner credentials
       (Server-authoritative check; Cashier session/token preserved)
       │
       ▼
[Select Tenders] (Cash, Card, Digital Wallet, Khata)
       │
       ▼
[Validate Balance & Change] (sum(payments) + due_amount == grand_total)
       │
       ▼
[POST /api/v1/sales]
       │
       ▼
[SaleController@store] -> [SaleService@createSale]
       │
  (DB::transaction)
  ├── 1. Idempotency Key Lock & Parity Check
  ├── 2. Verify Price Override Authorizer (Capture sale_actor_user_id + override_authorized_by_user_id)
  ├── 3. Inventory Deduction via InventoryService (type='sale')
  ├── 4. Sale Record Creation
  ├── 5. SaleItem Rows Creation with Cost & Price Snapshots
  ├── 6. SalePayment Rows Creation per Applied Tender
  ├── 7. Inflow Movement per Non-Credit Tender via AccountMovementService
  ├── 8. Audit Log Event Creation (Sale created & price overrides)
  └── 9. Atomic Customer Khata Balance Recalculation (CustomerAccountService)
       │
       ▼
[HTTP 201 Response] -> [Thermal Print (58mm/80mm) with Barcode & Return Policy]
```

---

## 4. Business Shape & Branch Context
- **Single-Store Mode:** All sales automatically attribute to the immutable Main Store (`is_primary = true`). The cashier is never prompted to select a branch.
- **Multi-Branch Mode:** Sales attribute strictly to the authenticated user's assigned branch (`user.branch_id`). Business Owners can sell directly in any store. Cross-branch sales by non-owners are blocked.

---

## 5. Offline POS & Sync Invariant
- Core selling workflows function completely offline via native IndexedDB (`localDb.js`).
- Sales created offline are staged in the `outbox` table with a UUID v4 `idempotency_key`.
- Local product stock decrements immediately to prevent accidental counter overselling.
- When network connectivity is restored, the outbox flushes to `POST /api/v1/sales` idempotently.
- **Held Carts:** Buffered in browser `localStorage` under `hbos_held_carts_${business_id}_${branch_id}` as a branch-shared operational buffer. Never queued in outbox.
- **Price Override Offline:** Cashier delegated override is `ONLINE_ONLY` to prevent caching plain manager credentials. Direct override by logged-in Owner/Manager is supported.
- **Barcode Label Sheets:** 100% offline-safe in `ReceiptsView.vue` using cached catalog and browser printing.
