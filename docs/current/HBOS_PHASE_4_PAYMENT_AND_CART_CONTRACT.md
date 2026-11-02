# HBOS Phase 4 — Payment & Cart Contract
**Program:** SkyraSoft HBOS Small Retail Transformation Program  
**Phase:** 4 (Sales / POS / Customers / Khata / Returns)  
**Document Status:** FROZEN DESIGN CONTRACT  

---

## 1. Split Tender Specifications (SRT-GAP-006 / REQ-0009)
The POS checkout interface supports flexible multi-tender payment splitting across the following canonical retail tenders:
1. **Cash:** Physical currency. Supports cash given, change calculation, and automated deposit into the branch cash drawer.
2. **Card (Debit / Credit):** Card swipe/POS terminal with transaction reference number; deposits into linked bank account.
3. **JazzCash:** Mobile digital wallet; deposits into JazzCash business wallet account.
4. **Easypaisa:** Mobile digital wallet; deposits into Easypaisa business wallet account.
5. **Bank Transfer:** Direct IBFT deposit into store bank account.
6. **Khata (Udhaar / Credit):** Customer credit receivable; records `due_amount` and updates customer ledger without cash movement.

### Core Payment Invariants:
- **Exact Settlement Rule:** `sum(applied_payments) + due_amount == grand_total`.
- **Change Calculation Rule:** Change is calculated solely from physical cash tendered: `change = cash_given - cash_portion`. Change is never issued against card, digital, or credit tenders.
- **Negative Tender Guard:** Every applied tender amount must be greater than zero. Negative tenders are strictly rejected.
- **Credit Customer Guard:** If `due_amount > 0` (Khata credit), an authentic registered customer is mandatory. Walk-in customers cannot receive credit.

---

## 2. Cart Model & Lifecycle
The active counter cart is managed in Pinia (`useRetailStore().cart`). Each cart item maintains:
- `cartItemId`: Unique string identifier (`${product_id}_${pack_type}`)
- `productId`: Base product ID
- `name`: Product title
- `sku`: Product SKU
- `barcode`: Primary barcode
- `price`: Effective selling unit price (catalog or authorized override)
- `unitPrice`: Original catalog selling price
- `cost`: Product cost price (snapshotted for margin accuracy)
- `quantity`: Selected units (minimum 1)
- `packType`: Discrete packaging unit (Single Pack, Box, Carton)
- `stock`: Current available branch inventory

### Cart Persistence Rules:
- Survives navigation across views within the same session.
- Discarded or reset upon successful sale confirmation.
- Atomically purged upon user logout as established by Phase-2 session isolation.

---

## 3. Cart Hold & Resume Contract (SRT-GAP-007 / REQ-0008)
To service busy shop queues when a customer pauses checkout, the cashier can buffer active carts locally:
- **Storage Engine:** Browser `localStorage` (strictly zero server database schema or browser IndexedDB schema impact).
- **Storage Partition Key:** `hbos_held_carts_${business_id}_${branch_id}` (tenant-safe and branch-isolated).
- **Buffer Nature:** `BRANCH_SHARED_OPERATIONAL_BUFFER` — allows any terminal on the same branch register to see held tickets while capturing actor attribution.
- **Actor Attribution:** Each held record embeds `held_by_user_id`, `held_by_user_name`, `created_at`, and an optional customer tag/note.
- **Hold Action:** Saves cart snapshot to `localStorage` and resets active counter to empty.
- **Resume Action:** Restores buffered cart items, customer tag, and pricing snapshots into the active counter, verifying available branch stock.
- **Discard Action:** Deletes the buffered cart from `localStorage` without side effects.
- **Buffer Limit Policy:** No unsourced hard limit (such as 10) is part of the semantic product contract (`unsupported_hard_buffer_limit = 0`). A practical UI safety warning may appear for excessive buffers, but the system does not impose an artificial barrier.
- **Logout & Cross-User Behavior:** Survives cashier logout (`Frontend/src/stores/auth.js` `clearAuthData()` removes user session tokens and in-memory Pinia stores, preserving the operational `hbos_held_carts_${business_id}_${branch_id}` buffer). When Cashier A holds a cart and logs out, Cashier B logging into the same business and branch can view and resume the cart with original `held_by_user_id` attribution intact. Cross-business and cross-branch access is strictly denied. Phase-2 session isolation conflict: 0.
- **Sensitive Data Protection:** Held-cart records never store auth tokens, passwords, approval credentials, or sensitive cost/profit margins.
- **Zero-Impact Guarantee:** Held carts create **zero inventory movement**, **zero financial movement**, **zero Khata movement**, and **zero backend draft sales**. They never sync to the server outbox until confirmed as completed sales.
- **Storage Ambiguity:** Exactly 0. Fully frozen to `localStorage`.
