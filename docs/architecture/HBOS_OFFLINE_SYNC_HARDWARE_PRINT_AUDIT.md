# SKYRASOFT HBOS — OFFLINE, SYNC, HARDWARE, PRINT & MULTI-DEVICE INTEGRITY AUDIT

## Architectural Overview & Execution Context
This audit artifact formalizes the complete offline execution, client-side persistence, background synchronization, conflict resolution, multi-device data integrity, thermal/A4 printing, barcode scanning, and peer-to-peer hardware communication architecture of the SkyraSoft HBOS management system.

---

## 1. Local IndexedDB Persistence Engine (7 Dexie/Local Stores)
HBOS utilizes a high-performance, client-side native IndexedDB database (`HBOS_Local_Store_DB`, Schema Version 1) managed via `Frontend/src/services/localDb.js`.

| Store ID | Store Name | Entity ID | Key Path | Purpose & Local Authority | Sync Direction | Certification |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `LOCALSTORE-001` | products | `ENTITY-017` | id | Product catalog cache (`CACHE_ONLY`) | PULL_ONLY | CERTIFIED_SAFE_BY_IMPLEMENTATION |
| `LOCALSTORE-002` | categories | `ENTITY-014` | id | Category taxonomy cache (`CACHE_ONLY`) | PULL_ONLY | CERTIFIED_SAFE_BY_IMPLEMENTATION |
| `LOCALSTORE-003` | brands | `ENTITY-016` | id | Brand master cache (`CACHE_ONLY`) | PULL_ONLY | CERTIFIED_SAFE_BY_IMPLEMENTATION |
| `LOCALSTORE-004` | customers | `ENTITY-023` | id | Customer directory & Khata balance cache (`OFFLINE_WORKING_COPY`) | PULL_ONLY | CERTIFIED_WITH_KNOWN_GAP |
| `LOCALSTORE-005` | sales | `ENTITY-019` | idempotency_key | POS retail sale outbox queue (`PENDING_MUTATION_SOURCE`) | PUSH_ONLY | CERTIFIED_WITH_KNOWN_GAP |
| `LOCALSTORE-006` | khata_payments | `ENTITY-025` | idempotency_key | Customer debt collection outbox queue (`PENDING_MUTATION_SOURCE`) | PUSH_ONLY | CERTIFIED_WITH_KNOWN_GAP |
| `LOCALSTORE-007` | expenses | `ENTITY-029` | idempotency_key | Petty operational cash expense outbox (`PENDING_MUTATION_SOURCE`) | PUSH_ONLY | CERTIFIED_SAFE_BY_IMPLEMENTATION |

---

## 2. Offline Capabilities Matrix
| Capability ID | Capability Name | Route | Flow ID | Classification | Evidence | Gap ID | Certification |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `OFFCAP-001` | POS Offline Retail Checkout & Cash Receipting | `/pos` | `FLOW-001` | OFFLINE_TRANSACTION_SUPPORTED | `PosSaleView.vue`, `offlinePosEngine.js` | `P7-GAP-001` | CERTIFIED_WITH_KNOWN_GAP |
| `OFFCAP-002` | Offline Product Search & Barcode Lookup | `/pos` | `FLOW-001` | OFFLINE_READ_ONLY | `offlineSearchService.js` | N/A | CERTIFIED_READ_ONLY_OFFLINE |
| `OFFCAP-003` | Offline Customer Khata Balance Lookup & Payment Collection | `/khata` | `FLOW-008` | OFFLINE_TRANSACTION_SUPPORTED | `localDb.js`, `SyncCoordinator.js` | `P7-GAP-003` | CERTIFIED_WITH_KNOWN_GAP |
| `OFFCAP-004` | Offline Petty Operating Expense Voucher Recording | `/expenses` | `FLOW-012` | OFFLINE_CREATE_SUPPORTED | `BulkSyncService.php:97-117` | N/A | CERTIFIED_SAFE_BY_IMPLEMENTATION |
| `OFFCAP-005` | Inventory GRN Receiving & Supplier Purchase Management | `/purchases` | `FLOW-009` | ONLINE_ONLY | `purchases.js` store | N/A | CERTIFIED_ONLINE_ONLY |
| `OFFCAP-006` | Inter-Branch Stock Transfer & Approvals | `/inventory` | `FLOW-004` | ONLINE_ONLY | `InventoryController.php@transfer` | N/A | CERTIFIED_ONLINE_ONLY |
| `OFFCAP-007` | Financial Account Bank Transfers & Capital Movements | `/financial-accounts` | `FLOW-015` | ONLINE_ONLY | `AccountTransferController.php` | N/A | CERTIFIED_ONLINE_ONLY |
| `OFFCAP-008` | User Administration, Roles & Security Governance | `/users` | `FLOW-020` | ONLINE_ONLY | `UserGovernanceController.php` | N/A | CERTIFIED_ONLINE_ONLY |

---

## 3. Sync Endpoints & Idempotency Architecture
HBOS operates 11 dedicated sync/mobile endpoints covering full catalog hydration, incremental delta queries, and atomic bulk transaction uploads.

| Sync ID | API ID | HTTP Route | Direction | Idempotency & Dedup | Conflict Strategy | Certification |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `SYNC-001` | `API-151` | `GET /api/v1/sync/status` | PULL_ONLY | SAFE_READ_IDEMPOTENT | NOT_APPLICABLE | CERTIFIED_SAFE_BY_IMPLEMENTATION |
| `SYNC-002` | `API-152` | `GET /api/v1/sync/catalog-delta` | DELTA_DOWNLOAD | SAFE_READ_IDEMPOTENT | SERVER_WINS | CERTIFIED_SAFE_BY_IMPLEMENTATION |
| `SYNC-003` | `API-153` | `POST /api/v1/sync/bulk-transactions` | BATCH_UPLOAD | IDEMPOTENT_ATOMIC_INGEST | APPEND_ONLY_NO_CONFLICT | CERTIFIED_WITH_KNOWN_GAP |
| `SYNC-004` | `API-002` | `POST /api/v1/auth/login` | BIDIRECTIONAL | REPLACEABLE_SESSION_TOKEN | NOT_APPLICABLE | CERTIFIED_SAFE_BY_IMPLEMENTATION |
| `SYNC-005` | `API-004` | `GET /api/v1/auth/me` | PULL_ONLY | SAFE_READ_IDEMPOTENT | NOT_APPLICABLE | CERTIFIED_SAFE_BY_IMPLEMENTATION |
| `SYNC-006` | `API-005` | `GET /api/v1/profile` | PULL_ONLY | SAFE_READ_IDEMPOTENT | NOT_APPLICABLE | CERTIFIED_SAFE_BY_IMPLEMENTATION |
| `SYNC-007` | `API-021` | `GET /api/v1/products` | SNAPSHOT_DOWNLOAD | SAFE_READ_IDEMPOTENT | SERVER_WINS | CERTIFIED_SAFE_BY_IMPLEMENTATION |
| `SYNC-008` | `API-011` | `GET /api/v1/categories` | SNAPSHOT_DOWNLOAD | SAFE_READ_IDEMPOTENT | SERVER_WINS | CERTIFIED_SAFE_BY_IMPLEMENTATION |
| `SYNC-009` | `API-016` | `GET /api/v1/brands` | SNAPSHOT_DOWNLOAD | SAFE_READ_IDEMPOTENT | SERVER_WINS | CERTIFIED_SAFE_BY_IMPLEMENTATION |
| `SYNC-010` | `API-071` | `GET /api/v1/customers` | SNAPSHOT_DOWNLOAD | SAFE_READ_IDEMPOTENT | SERVER_WINS | CERTIFIED_WITH_KNOWN_GAP |
| `SYNC-011` | `API-041` | `GET /api/v1/inventory` | SNAPSHOT_DOWNLOAD | SAFE_READ_IDEMPOTENT | SERVER_WINS | CERTIFIED_WITH_KNOWN_GAP |

---

## 4. Multi-Device Integrity & Concurrency Analysis
1. **Stock Double-Spend Risk (`P7-GAP-002`)**: Disconnected POS devices can sell overlapping inventory units. Server replay allows post-facto inventory deficits.
2. **Khata Balance Reconciliation (`P7-GAP-003`)**: Replay ledger appends apply delta additions, preserving eventual consistency on server, though client UI displays stale balance until next delta poll.
3. **Master Pricing Integrity**: Product prices are frozen in transaction line-item snapshots (`sale_items.unit_price`), preventing retroactive price tampering.

---

## 5. Printing, Barcode & Hardware Pipeline (4 Channels)
| Hardware ID | Device / Bridge | Transport | Protocol | Offline Capable? | Certification |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `HW-001` | Thermal Receipt Printer (80mm/58mm) | USB / Spooler | ESC/POS or HTML Canvas | YES | CERTIFIED_SAFE_BY_IMPLEMENTATION |
| `HW-002` | Wireless Bluetooth Thermal Printer | Bluetooth LE / SPP | Raw ESC/POS Stream | YES | CERTIFIED_SAFE_BY_IMPLEMENTATION |
| `HW-003` | Optical Barcode Scanner | USB / 2.4GHz HID Wedge | Keystroke Stream + Enter | YES | CERTIFIED_SAFE_BY_IMPLEMENTATION |
| `HW-004` | Desktop-to-Mobile P2P Wi-Fi Bridge | TCP/IP LAN (Port 8080) | HTTP REST + QR Auth | YES (Local LAN) | CERTIFIED_WITH_KNOWN_GAP |

---

## 6. Print Pipelines
- `PRINT-001`: Thermal Receipt (80mm/58mm) — Dispatches pre-commit in POS view (`P7-GAP-001`).
- `PRINT-002`: A4/A5 Full-Page Retail Tax Invoice — On-demand post-commit browser print.
- `PRINT-003`: Goods Receiving Note (GRN Voucher) — Purchase receiving voucher.
- `PRINT-004`: Barcode SKU Shelf Label — Adhesive label generator.