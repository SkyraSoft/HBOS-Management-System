# HBOS V1 Scope & Product Principles

**Program**: SkyraSoft HBOS Small Retail Transformation Program  
**Phase**: Phase 1 — Prompt 1 of 3  
**Status**: AUTHORITATIVE PRODUCT PRINCIPLES (FINAL CERTIFIED)  

---

## 1. Frozen Target Customer Profile

HBOS V1 is engineered exclusively for small Pakistani retail merchants operating single shops or growing from one store to multiple branches.
- **Store Types**: Grocery, general store, pharmacy, electronics, apparel, cosmetics, spare parts, hardware, wholesale/retail traders.
- **Store Scale**: Single store (counter with owner/cashier) or small multi-branch business (primary store plus branch locations).
- **Hardware Environment**: Low-to-mid range Windows POS / Desktop, Android mobile/tablets, 80mm/58mm thermal printers, basic barcode scanners.
- **Connectivity Reality**: Frequent load shedding (electricity outages), intermittent internet connectivity, mixed cellular data and local WiFi.

## 2. Core Operating Principles

1. **Small Business First**: Clean, intuitive UI with zero enterprise bloat, zero unneeded accounting jargon, and zero multi-step setup friction.
2. **Progressive Complexity**: Multi-branch features (branch switcher, stock transfer, inter-branch reporting, branch task delegation) stay completely hidden until a second branch is added.
3. **Owner Does Everything Directly**: Super Owner can sell at POS, receive inventory, record expenses, or collect debts directly without switching into dummy staff accounts.
4. **Offline Resilience**: Full sales counter operation during internet failure with deterministic native browser IndexedDB local storage (`localDb.js`) and background idempotency sync.
5. **Cash & Khata as First-Class Citizens**: Native support for Pakistan retail workflows: split cash/online payments (JazzCash/Easypaisa), partial payments, customer Khata ledgers, and supplier payables.
6. **Clear 3-Role Customer Security**: Universal Owner control, branch-scoped Manager oversight, and tightly scoped Cashier counter operations.
7. **Explicit Owner View-As Mode**: Owner can preview branch counter as Manager or Cashier in strict read-only mode with zero risk of accidental state mutation.
8. **Same-Device Session Safety**: Instant local cache purge and credential isolation when staff members change shifts on shared counter PCs.
9. **Material Action Auditability**: Uncompromising audit logging of discounts, cancellations, stock adjustments, and refunds with preserved actor identity.

## 3. Strict Complexity Guards

- No complex general ledger, manual journal entries, or chart of accounts.
- No multi-tier enterprise warehouse bin/aisle management.
- No custom visual workflow designers or dynamic form builders.
