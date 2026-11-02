# SKYRASOFT HBOS — MULTI-BRANCH AND OFFLINE INTEGRITY
**Document Reference:** `docs/release/HBOS_MULTI_BRANCH_AND_OFFLINE_INTEGRITY.md`  
**Stage:** Stage 2 — Complete Real-Business Operations, Security, Financial Integrity & Offline Reliability  
**Checkpoints Covered:** Checkpoint 2.3, Checkpoint 2.4  
**Status:** AUTHORITATIVE TECHNICAL SPECIFICATION & EVIDENCE BASELINE

---

## 1. Executive Summary

HBOS is designed to support the natural evolution of Pakistani retail businesses, from a single shopkeeper to an expanding multi-counter, multi-branch commercial enterprise. Crucially, multi-branch scaling and offline operational resilience must never introduce inventory phantom records, duplicate transactions, or tenant boundary leaks.

This document details the architectural guarantees, state machines, and verified behaviors of HBOS multi-branch expansion, inter-branch transfers, offline outbox synchronization, and tenant security.

---

## 2. Multi-Branch Architecture & Primary Store Immutability

### 2.1 The Expansion Invariant
- **Rule:** When an existing Main Store expands by creating Branch 2 and Branch 3:
  - The original Main Store remains flagged as `is_primary = true`.
  - Main Store historical sales, inventory movements, customer Khata records, and financial ledger remain strictly unchanged.
  - New branches are initialized with independent branch inventory records, separate cash drawer sessions, and separate staff counter assignments.
- **Deactivation Guards:**
  - The Primary Main Store can NEVER be deactivated (`PUT /api/v1/branches/{id}` with `status: inactive` is rejected with 422).
  - Secondary branches cannot be deactivated if they have:
    1. An open cash register session.
    2. Positive inventory on hand (`quantity_on_hand > 0`).
    3. In-transit incoming or outgoing stock transfers.
    4. Unsynced transactions in the client outbox.

---

## 3. Inter-Branch Stock Transfer Lifecycle

```
[Draft Request] 
      |
[Dispatch Action] ----> Source Stock Decrement (In-Transit Created)
      |                 Goods CANNOT be sold at Source or Destination
      v
[In-Transit Status]
      |
      +------------------------+
      |                        |
[Receive Action]         [Cancel Action (Compensating Inflow)]
      |                        |
Destination Stock        Source Stock
Incremented              Restored
```

### 3.1 Stock Conservation Equation
$$\text{Stock}_{\text{Source}} + \text{Stock}_{\text{In-Transit}} + \text{Stock}_{\text{Destination}} = \text{Constant}$$

1. **Draft Creation (`POST /api/v1/transfers`):**
   - Validates products, quantities, source branch, and destination branch.
   - Rejects transfer if source branch equals destination branch.
   - Zero inventory movements created at draft stage.
2. **Dispatch (`POST /api/v1/transfers/{id}/dispatch`):**
   - Decrements `quantity_on_hand` at source branch.
   - Creates `InventoryMovement` with `type = transfer_out`.
   - Transfer state becomes `in_transit`. Goods in transit are unavailable for sale at either location.
3. **Receive (`POST /api/v1/transfers/{id}/receive`):**
   - Increments `quantity_on_hand` at destination branch.
   - Creates `InventoryMovement` with `type = transfer_in`.
   - Transfer state becomes `received`.
4. **Cancellation Safeguards:**
   - A dispatched transfer can be cancelled; doing so posts a compensating `transfer_in` to the source branch.
   - An already received transfer can NEVER be cancelled. Double receive and double dispatch are strictly rejected.

---

## 4. Offline POS Operations & Bulk Synchronization Engine

### 4.1 Architecture & Durable Client Outbox
- **Catalog Delta Caching:** POS client caches permitted catalog items, barcodes, prices, and customers into IndexedDB store `HBOS_Store_Biz_${businessId}` via `GET /api/v1/sync/catalog-delta?branch_id={id}`.
- **Client Outbox:** When network connectivity is lost, sales are recorded locally with a client-generated UUID (`idempotency_key`).
- **Restart Survival:** Outbox records are stored in durable IndexedDB (`HBOS_Store_Biz_${businessId}_outbox`) and persist across browser/Tauri window closure, Alt+F4, and application restarts.

### 4.2 Bulk Ingestion Endpoint (`POST /api/v1/sync/bulk-transactions`)
- **Payload Contract:**
  ```json
  {
    "sales": [
      {
        "idempotency_key": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
        "branch_id": 1,
        "customer_id": null,
        "payment_method": "cash",
        "paid_amount": 240.00,
        "financial_account_id": 1,
        "items": [
          { "product_id": 10, "quantity": 2, "unit_price": 120.00 }
        ]
      }
    ]
  }
  ```
- **Idempotency & Replay Protection:**
  - Server verifies `idempotency_key`. If the sale was already processed, the server returns the existing invoice without creating duplicate inventory movements or cash records.
  - Prevents double-counting when network drops mid-request and client retries.
- **Late Synchronization After Register Session Close:**
  - If an offline sale is synced after the active register session was already closed:
    - The closed session's historical closing balance and snapshot remain **strictly immutable**.
    - The late sale is recorded with an auditable `is_late_synced = true` flag and accounted in the subsequent operational session.

---

## 5. Security & Tenant Isolation

1. **Multi-Tenant Context Middleware (`ResolveActiveBusiness`):**
   - Every protected API request requires authentication and tenant binding via header `X-Business-ID`.
   - The user must have a verified database membership in the requested business (`user.businesses()->where('businesses.id', $id)->exists()`).
   - If missing or unauthorized, returns 403 Forbidden.
2. **Role Permission Enforcement:**
   - Salespersons / Cashiers are restricted from executive financial reports (`/api/v1/reports/*`), back-office settings, supplier payments, and inter-branch transfers outside their assigned counter.
   - Direct API calls attempting privilege escalation are rejected server-side with 403.
3. **Owner View-As Read-Only Mode (`CheckViewAsReadOnly`):**
   - When an Owner enters "View-As Manager" or "View-As Cashier" to preview branch screens, all write endpoints (`POST`, `PUT`, `PATCH`, `DELETE`) are rejected by middleware with 403 Forbidden (`VIEW_AS_READ_ONLY_ACTIVE`).
4. **Material Audit Trail:**
   - Every sale, discount override, purchase, supplier payout, Khata repayment, refund, inventory adjustment, and transfer records:
     - `actor_id` (authenticated user)
     - `business_id`
     - `branch_id`
     - `action_type`
     - `properties` (before and after state diffs)
     - `timestamp`

---

## 6. Verification Status

Verified across passing test suites:
- `api/tests/Feature/InterBranchStockTransferTest.php` (21 tests, 57 assertions)
- `api/tests/Feature/OfflineLateReplayAndSessionTest.php` (4 tests, 29 assertions)
- `api/tests/Feature/OfflineReplayIdempotencyTest.php` (12 tests, 35 assertions)
- `api/tests/Feature/BranchExpansionAndReadinessTest.php` (12 tests, 40 assertions)
- `api/tests/Feature/TenantIsolationMatrixTest.php` (9 tests, 11 assertions)
- `api/tests/Feature/SmallRetailScenariosAcceptanceTest.php` (Scenarios B, C, D, E)
