# SKYRASOFT HBOS FUNCTIONAL ARCHITECTURE PROGRAM
# PHASE 4 PROGRESS TRACKER — CALCULATIONS, ACCOUNTING & BUSINESS RULES

**Status:** COMPLETE (ALL 4 CHECKPOINTS PASS WITH PREFLIGHT VERIFICATION)
**Timestamp:** 2026-10-02 06:28:31

## CHECKPOINT 4.1: Authoritative Calculation & Financial Metric Registry

- **Status:** CERTIFIED COMPLETE WITH KNOWN GAPS
- **Discovered Calculations:** 32 canonical calculations across 7 domains (`CALC-001` to `CALC-032`)
  - Sales / POS (10): Line subtotal, order subtotal, discount, tax, grand total, due, change, line COGS, gross profit, margin %
  - Procurement (6): Purchase line, subtotal, shipping/freight, tax/discount, grand total, purchase due
  - Inventory & Valuation (2): Moving average cost price, branch inventory asset valuation
  - Returns & Refunds (2): Return line amount, return grand total refund
  - Customer / Khata AR (2): Customer balance, available credit limit
  - Supplier AP (2): Supplier balance, purchase allocation
  - Finance & Cash (4): Financial account balance, cash ledger balance, discrepancy, expense total
  - Reporting & KPIs (4): Net revenue, operating profit, aggregate AR/AP, period cash movement
- **Authority Distribution:** All 32 calculations assigned explicit authority (Backend, Ledger, Master, Report, Query, or Preview).

## CHECKPOINT 4.2: Conflicting Formula Detection

- **Status:** CERTIFIED COMPLETE WITH KNOWN GAPS
- **Audited Occurrences:** 24 formula occurrences across Frontend, Backend, Reports, Dashboard, and Print.
- **Key Findings:**
  - Landed Cost Freight Exclusion: `CALC-013` / `CALC-017` freight costs captured in purchase headers are excluded from moving average product cost calculations (`P4-GAP-005`).
  - Split Tender Aggregation: Multi-tender POS breakdown is collapsed into a single header column without child payment ledger rows (`P4-GAP-003`).

## CHECKPOINT 4.3: Accounting Invariants & Business Rule Validation

- **Status:** CERTIFIED COMPLETE WITH KNOWN GAPS
- **Invariants Audited:** 16 invariants (`INV-001` to `INV-016`)
  - Holds by implementation: 8
  - Holds with limitations: 3
  - Can drift under specific mutations: 4
  - Known violation paths: 1 (`INV-010` Landed cost freight allocation)
- **Balance Domains Audited:** 5 physical schema domains (Product Stock, Customer AR, Supplier AP, Financial Accounts, Cash Ledgers)
- **Business Rules Audited:** 18 rules (`RULE-001` to `RULE-018`)
  - Consistent all layers: 9
  - Backend only: 1 | Frontend only: 1 | Offline mismatch: 2 | Missing rules: 5

## CHECKPOINT 4.4: Phase 4 Verification & Final Certification

- **Automated Verification Script:** `scratch/verify_phase_4_certification.php`
- **Verifier Result:** `RESULT: PASS` (0 errors, 0 unknown authorities, 0 orphan references, 0 invented physical tables)
- **Total Phase 4 Gaps:** 15 tracked gaps categorized (2 New findings, 8 Consequences of earlier gaps, 5 Carryforward references)

