# SKYRASOFT HBOS — AUTHORITATIVE SYSTEM GAP & DEFECT REGISTER
## Phase 8 Handoff Integrity Preflight & Master System Backlog

## Executive Summary & Consolidation Architecture
This document forms the definitive master register of all source-proven system gaps, architectural flaws, schema deficiencies, and security vulnerabilities discovered across Phases 1 through 7 of the SkyraSoft HBOS Functional Architecture Program.

A total of **93 historical audit findings** from Phases 1–7 (including all 17 certified Phase-1 findings `P1-GAP-001` through `P1-GAP-017`) have been ingested and consolidated into **31 canonical global root defects (`GAP-001` through `GAP-031`)**, eliminating duplicate observations, classifying consequence chains, and establishing an unambiguous engineering remediation backlog for Phase 9 implementation.

---

## 1. Master Global Gap Register Summary (31 Root Defects)

| Global Gap ID | Severity | Foundation Blocker | Canonical Title | Primary Domain | Implementation Wave | Source Phase Lineage |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `GAP-001` | HIGH | TRUE | Sale Line Items Lack Barcode, SKU & Point-in-Time Cost Snapshot Persistence | DATA_MODEL | WAVE_1 | P1-GAP-004, P1-GAP-011, P2-GAP-001, P3-GAP-001 |
| `GAP-002` | HIGH | TRUE | Product Master Schema Lacks Authoritative Wholesale Pricing Tier Column | MASTER_DATA | WAVE_1 | P1-GAP-002, P3-GAP-014, P5-GAP-001 |
| `GAP-003` | CRITICAL | TRUE | Split-Tender Multi-Method Payment Allocations Lack Dedicated Breakdown Persistence | SALES_POS | WAVE_1 | P1-GAP-005, P1-GAP-017, P3-GAP-003, P3-GAP-010, P5-GAP-002 |
| `GAP-004` | HIGH | FALSE | Inventory Physical Count Stock Adjustment Bypasses Valuation Adjustment Account | INVENTORY_VALUATION | WAVE_2 | P1-GAP-003, P2-GAP-006, P3-GAP-011, P4-GAP-005 |
| `GAP-005` | MEDIUM | FALSE | Purchase Freight & Landed Costs Not Capitalized Into Product Moving Average Cost | PROCUREMENT | WAVE_3 | P1-GAP-006, P3-GAP-005, P4-GAP-016 |
| `GAP-006` | HIGH | FALSE | Purchase Cancellation Does Not Verify If Received Stock Has Already Been Sold | PROCUREMENT | WAVE_2 | P2-GAP-008, P3-GAP-009, P6-GAP-009 |
| `GAP-007` | MEDIUM | FALSE | Inter-Branch Stock Transfer Operates Synchronously Without In-Transit Clearing State | INVENTORY | WAVE_3 | P1-GAP-013, P2-GAP-018, P3-GAP-012 |
| `GAP-008` | HIGH | FALSE | Modifying Customer ID on Posted Sale Creates Silent Khata Ledger Drift | CUSTOMER_AR | WAVE_2 | P1-GAP-007, P2-GAP-007, P3-GAP-002, P5-GAP-007 |
| `GAP-009` | HIGH | FALSE | Customer Payment Reversal Lacks Guard Against Secondary Over-Collection Reversal Race | CUSTOMER_AR | WAVE_2 | P2-GAP-015, P3-GAP-015 |
| `GAP-010` | MEDIUM | FALSE | Sale Return Workflow Lacks Granular Restock Condition / Scrap Decision Flag | RETURNS_REFUNDS | WAVE_2 | P2-GAP-013, P3-GAP-008 |
| `GAP-011` | HIGH | FALSE | Missing Dedicated Supplier Return / Debit Note Workflow With Stock Reversal & AP Deduction | PROCUREMENT | WAVE_3 | P2-GAP-005, P3-GAP-006 |
| `GAP-012` | HIGH | FALSE | Supplier Payment Mutation Lacks Automated Reversal Mechanism With AP Re-Accrual | SUPPLIER_AP | WAVE_3 | P2-GAP-015, P3-GAP-007 |
| `GAP-013` | CRITICAL | TRUE | Operating Expense Entry Allows Null Financial Account Creating Unallocated Cash Drift | EXPENSES | WAVE_1 | P1-GAP-008, P1-GAP-012, P2-GAP-016, P3-GAP-013, P5-GAP-008 |
| `GAP-014` | HIGH | FALSE | Soft-Deleting Operating Expense Leaves Associated AccountMovement Row Active | EXPENSES | WAVE_2 | P2-GAP-017 |
| `GAP-015` | CRITICAL | TRUE | UI-Only Authorization for High-Risk Destructive Operations Lacks Backend Policy Enforcement | SECURITY_RBAC | WAVE_1 | P6-GAP-001 |
| `GAP-016` | HIGH | TRUE | Implicit Route Model Binding Queries Records Across Tenants Before ResolveActiveBusiness Scope | SECURITY_RBAC | WAVE_1 | P6-GAP-002 |
| `GAP-017` | HIGH | TRUE | Sale Store Endpoint Accepts Arbitrary branch_id In Request Payload Without User Assignment Check | SECURITY_RBAC | WAVE_1 | P6-GAP-003 |
| `GAP-018` | CRITICAL | TRUE | Server Trusts Client-Calculated Sale Totals & Discounts Without Server Recalculation | VALIDATION | WAVE_1 | P1-GAP-001, P6-GAP-004 |
| `GAP-019` | HIGH | TRUE | Sale Return Refund Settlement Endpoint Lacks Client Request UUID Deduplication | CONCURRENCY | WAVE_1 | P6-GAP-007 |
| `GAP-020` | HIGH | TRUE | Purchase GRN Receiving Lacks Pessimistic Row Lock on branch_inventories | CONCURRENCY | WAVE_1 | P6-GAP-006 |
| `GAP-021` | HIGH | FALSE | POS Checkout Dispatches Thermal Receipt Printing Before Verified HTTP Commit Confirmation | PRINT | WAVE_4 | P6-GAP-008, P7-GAP-001 |
| `GAP-022` | HIGH | FALSE | Offline POS Terminals Lack Distributed Stock Reservations Permitting Inventory Double-Spend | OFFLINE | WAVE_4 | P7-GAP-002 |
| `GAP-023` | MEDIUM | FALSE | Offline Customer Khata Balance Projection Displays Stale Balance During Concurrent Online Settlements | OFFLINE | WAVE_4 | P6-GAP-010, P7-GAP-003 |
| `GAP-024` | MEDIUM | FALSE | Peer-to-Peer Device Pairing Service Lacks IP Address & Private Subnet Boundary Validation | HARDWARE | WAVE_4 | P1-GAP-010, P2-GAP-019, P7-GAP-004 |
| `GAP-025` | HIGH | FALSE | Offline Quick Customer Creation Lacks Persistent Temp-to-Server ID Mapping Graph | OFFLINE | WAVE_4 | P7-GAP-005 |
| `GAP-026` | MEDIUM | FALSE | Critical Destructive Mutations Lack Explicit Modal Confirmation Dialogs | UI_UX | WAVE_4 | P5-GAP-006 |
| `GAP-027` | LOW | FALSE | POS Screen Lacks Persistent Active Operating Branch & Shift Indicator in Header | UI_UX | WAVE_4 | P1-GAP-009, P5-GAP-005 |
| `GAP-028` | LOW | FALSE | Legacy Unrouted ProductInvoiceView Component Creates Codebase Confusion | UI_UX | WAVE_4 | P5-GAP-010 |
| `GAP-029` | MEDIUM | FALSE | Employee HR Wage, Salary Amount & Payment Cycle Discarded Without Database Schema Persistence | MASTER_DATA | WAVE_3 | P1-GAP-014 |
| `GAP-030` | LOW | FALSE | Customer Secondary Phone & Alternate Contact Number Discarded Without Schema Persistence | CUSTOMER_AR | WAVE_4 | P1-GAP-015 |
| `GAP-031` | LOW | FALSE | Expense Category Monthly Budget Cap Limit Discarded Without Schema Persistence | EXPENSES | WAVE_4 | P1-GAP-016 |

---

## 2. Historical Finding Disposition & Consolidation Statistics
- **Total Historical Findings Ingested**: **93**
  - Phase 1 Findings Accounted For: **17 / 17**
  - Phase 2 Findings Accounted For: **20 / 20**
  - Phase 3 Findings Accounted For: **15 / 15**
  - Phase 4 Findings Accounted For: **16 / 16**
  - Phase 5 Findings Accounted For: **10 / 10**
  - Phase 6 Findings Accounted For: **10 / 10**
  - Phase 7 Findings Accounted For: **5 / 5**
- **Unique Root Defect Sources**: **30**
- **Consequence Findings**: **45**
- **Duplicate Findings**: **11**
- **Carryforward-Only Findings**: **5**
- **Intentional Behaviors**: **1** (`P5-GAP-009` - Customer opening balance hidden on edit)
- **Retracted Findings**: **1** (`P4-GAP-011` - Full double-entry GL requirement not part of HBOS architecture)
- **Unmapped Historical Findings**: **0**

---

## 3. Severity Distribution
- **CRITICAL (4 Defects)**: `GAP-003`, `GAP-013`, `GAP-015`, `GAP-018`
- **HIGH (16 Defects)**: `GAP-001`, `GAP-002`, `GAP-004`, `GAP-006`, `GAP-008`, `GAP-009`, `GAP-011`, `GAP-012`, `GAP-014`, `GAP-016`, `GAP-017`, `GAP-019`, `GAP-020`, `GAP-021`, `GAP-022`, `GAP-025`
- **MEDIUM (7 Defects)**: `GAP-005`, `GAP-007`, `GAP-010`, `GAP-023`, `GAP-024`, `GAP-026`, `GAP-029`
- **LOW (4 Defects)**: `GAP-027`, `GAP-028`, `GAP-030`, `GAP-031`

---

## 4. Truth-Preserving Data Repair Architecture
All historical data repair strategies have been certified as truth-preserving:
1. `REPAIR-001` (`GAP-001`): `APPROXIMATE_NON_HISTORICAL_BACKFILL` — Populates historical sale items with current product master name/SKU as best-effort convenience backfill; explicitly marked as non-authoritative point-in-time reconstruction.
2. `REPAIR-002` (`GAP-003`): `DETERMINISTIC_BACKFILL` — Constructs single-tender records in `sale_payments` table matching 100% of historical `sales` rows.
3. `REPAIR-003` (`GAP-013`): `MANUAL_RECONCILIATION_REQUIRED` — Directs historical expenses with `NULL` account to a dedicated `UNALLOCATED_HISTORICAL_EXPENSES` holding account rather than fabricating a false bank/cash source.
4. `REPAIR-004` (`GAP-014`): `COMPENSATING_MOVEMENT_OR_RECONCILIATION` — Reconciles orphan account movements from previously deleted expenses through compensating reversal records rather than destructive history removal.
5. `REPAIR-005` (`GAP-008`): `MANUAL_RECONCILIATION_REQUIRED` — Generates read-only Khata customer balance discrepancy schedules for manual operator review.