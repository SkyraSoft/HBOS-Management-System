# HBOS Requirement to Implementation Matrix

**Program**: SkyraSoft HBOS Small Retail Transformation Program  
**Phase**: Phase 1 — Prompt 2 of 3  
**Status**: LAYER-BY-LAYER IMPLEMENTATION AUDIT  

---

## Layer Status by Canonical Requirement

### [REQ-0001] Staged Business Registration & Store Identity Setup

- **Overall Status**: `CURRENTLY_SATISFIED`
- **Priority / Criticality**: `P0_MUST_WORK_TO_RUN_STORE` / `LAUNCH_BLOCKER`
- **Existing Module**: `ONBOARDING`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `COMPLETE`, API: `COMPLETE`, Frontend: `COMPLETE`, Offline: `NOT_APPLICABLE`
- **Target Delta / Missing Behaviors**: None. Meets acceptance criteria.

---

### [REQ-0002] Main Store / Primary Branch Immutability & Expansion Growth

- **Overall Status**: `MOSTLY_SATISFIED`
- **Priority / Criticality**: `P0_MUST_WORK_TO_RUN_STORE` / `LAUNCH_BLOCKER`
- **Existing Module**: `BRANCH_MANAGEMENT`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `COMPLETE`, API: `COMPLETE`, Frontend: `PARTIAL`, Offline: `COMPLETE`
- **Target Delta / Missing Behaviors**: SIMPLE_GUIDED_ADD_STORE_WORKFLOW transitioning from 1 store to 2 stores.

---

### [REQ-0003] Branch Provisioning, Deactivation & Store Settings

- **Overall Status**: `MOSTLY_SATISFIED`
- **Priority / Criticality**: `P1_CORE_DAILY_OPERATION` / `LAUNCH_REQUIRED`
- **Existing Module**: `BRANCH_MANAGEMENT`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `COMPLETE`, API: `COMPLETE`, Frontend: `PARTIAL`, Offline: `PARTIAL`
- **Target Delta / Missing Behaviors**: Deactivation guard preventing branch deactivation if unsynced transactions remain in outbox.

---

### [REQ-0004] Small Retail 3-Role Access Model & Staff Invitation

- **Overall Status**: `MOSTLY_SATISFIED`
- **Priority / Criticality**: `P0_MUST_WORK_TO_RUN_STORE` / `LAUNCH_BLOCKER`
- **Existing Module**: `USERS_AND_ACCESS`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `COMPLETE`, API: `COMPLETE`, Frontend: `PARTIAL`, Offline: `PARTIAL`
- **Target Delta / Missing Behaviors**: Hide non-V1 technical roles (Accountant, Inventory Manager) from staff invitation UI.

---

### [REQ-0005] Super Owner Universal Direct Operation (No Dummy Accounts)

- **Overall Status**: `CURRENTLY_SATISFIED`
- **Priority / Criticality**: `P0_MUST_WORK_TO_RUN_STORE` / `LAUNCH_BLOCKER`
- **Existing Module**: `USERS_AND_ACCESS`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `COMPLETE`, API: `COMPLETE`, Frontend: `COMPLETE`, Offline: `COMPLETE`
- **Target Delta / Missing Behaviors**: None. Meets acceptance criteria.

---

### [REQ-0006] Owner Read-Only "View-As" Branch Counter Preview Mode

- **Overall Status**: `MISSING`
- **Priority / Criticality**: `P2_IMPORTANT_MANAGEMENT` / `POST_LAUNCH_SAFE`
- **Existing Module**: `USERS_AND_ACCESS`
- **Layer Breakdown**: Data Model: `PARTIAL`, Backend: `MISSING`, API: `MISSING`, Frontend: `MISSING`, Offline: `NOT_APPLICABLE`
- **Target Delta / Missing Behaviors**: Dedicated read-only View-As endpoint and UI preview banner with mutation rejection.

---

### [REQ-0007] Same-Device User Session & Cache Isolation

- **Overall Status**: `PARTIALLY_SATISFIED`
- **Priority / Criticality**: `P0_MUST_WORK_TO_RUN_STORE` / `LAUNCH_BLOCKER`
- **Existing Module**: `USERS_AND_ACCESS`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `COMPLETE`, API: `COMPLETE`, Frontend: `PARTIAL`, Offline: `PARTIAL`
- **Target Delta / Missing Behaviors**: In-memory Pinia financial state purge on logout to prevent next user viewing previous cached profit metrics.

---

### [REQ-0008] Ultra-Fast POS Retail Checkout & Barcode Scanning

- **Overall Status**: `MOSTLY_SATISFIED`
- **Priority / Criticality**: `P0_MUST_WORK_TO_RUN_STORE` / `LAUNCH_BLOCKER`
- **Existing Module**: `POS`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `COMPLETE`, API: `COMPLETE`, Frontend: `PARTIAL`, Offline: `COMPLETE`
- **Target Delta / Missing Behaviors**: POS Cart Hold & Multi-Cart Buffer with rapid scan debounce.

---

### [REQ-0009] Split Multi-Tender Payment Processing & Cash Change Calculation

- **Overall Status**: `MOSTLY_SATISFIED`
- **Priority / Criticality**: `P0_MUST_WORK_TO_RUN_STORE` / `LAUNCH_BLOCKER`
- **Existing Module**: `POS`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `COMPLETE`, API: `COMPLETE`, Frontend: `PARTIAL`, Offline: `COMPLETE`
- **Target Delta / Missing Behaviors**: POS checkout modal UI for multi-tender split inputs (Cash, JazzCash, Easypaisa, Card, Khata) with large change display.

---

### [REQ-0010] POS Cart Hold/Resume, Order Discounts & Item Price Overrides

- **Overall Status**: `PARTIALLY_SATISFIED`
- **Priority / Criticality**: `P1_CORE_DAILY_OPERATION` / `LAUNCH_REQUIRED`
- **Existing Module**: `POS`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `COMPLETE`, API: `COMPLETE`, Frontend: `PARTIAL`, Offline: `PARTIAL`
- **Target Delta / Missing Behaviors**: AUTHORIZED_OVERRIDE_WORKFLOW for deep line-item discounts and cashier price overrides.

---

### [REQ-0011] Lightweight Customer Directory & POS Fast-Add

- **Overall Status**: `MOSTLY_SATISFIED`
- **Priority / Criticality**: `P1_CORE_DAILY_OPERATION` / `LAUNCH_REQUIRED`
- **Existing Module**: `CUSTOMERS`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `COMPLETE`, API: `COMPLETE`, Frontend: `PARTIAL`, Offline: `COMPLETE`
- **Target Delta / Missing Behaviors**: Inline Quick Add Customer popup inside POS checkout modal and secondary phone contact validation.

---

### [REQ-0012] Pakistani Customer Khata (Udhaar) Credit Ledger & Collections

- **Overall Status**: `CURRENTLY_SATISFIED`
- **Priority / Criticality**: `P0_MUST_WORK_TO_RUN_STORE` / `LAUNCH_BLOCKER`
- **Existing Module**: `KHATA`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `COMPLETE`, API: `COMPLETE`, Frontend: `COMPLETE`, Offline: `COMPLETE`
- **Target Delta / Missing Behaviors**: None. Meets acceptance criteria.

---

### [REQ-0013] POS Customer Returns, Exchanges, Restock Condition & Refunds

- **Overall Status**: `MOSTLY_SATISFIED`
- **Priority / Criticality**: `P0_MUST_WORK_TO_RUN_STORE` / `LAUNCH_BLOCKER`
- **Existing Module**: `RETURNS`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `COMPLETE`, API: `COMPLETE`, Frontend: `PARTIAL`, Offline: `COMPLETE`
- **Target Delta / Missing Behaviors**: Dedicated return invoice lookup modal inside POS view.

---

### [REQ-0014] Product Catalog Master, Categories, Brands & Barcodes

- **Overall Status**: `MOSTLY_SATISFIED`
- **Priority / Criticality**: `P0_MUST_WORK_TO_RUN_STORE` / `LAUNCH_BLOCKER`
- **Existing Module**: `PRODUCTS`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `PARTIAL`, API: `COMPLETE`, Frontend: `PARTIAL`, Offline: `COMPLETE`
- **Target Delta / Missing Behaviors**: Alternate barcode indexing and loose item fractional unit parsing in catalog lookup.

---

### [REQ-0015] Loose/Packaged Units, Wholesale/Retail Tiered Pricing & Below-Cost Warning

- **Overall Status**: `PARTIALLY_SATISFIED`
- **Priority / Criticality**: `P0_MUST_WORK_TO_RUN_STORE` / `LAUNCH_BLOCKER`
- **Existing Module**: `PRODUCTS`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `PARTIAL`, API: `COMPLETE`, Frontend: `PARTIAL`, Offline: `COMPLETE`
- **Target Delta / Missing Behaviors**: Gross profit margin indicators and wholesale rate selector in POS checkout.

---

### [REQ-0016] Branch Stock Levels & Physical Adjustments / Discrepancy Audits

- **Overall Status**: `CURRENTLY_SATISFIED`
- **Priority / Criticality**: `P0_MUST_WORK_TO_RUN_STORE` / `LAUNCH_BLOCKER`
- **Existing Module**: `INVENTORY`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `COMPLETE`, API: `COMPLETE`, Frontend: `COMPLETE`, Offline: `COMPLETE`
- **Target Delta / Missing Behaviors**: None. Meets acceptance criteria.

---

### [REQ-0017] Stock Intelligence: Low-Stock Alerts, Out-of-Stock Tracking & Velocity Analytics

- **Overall Status**: `FOUNDATION_ONLY`
- **Priority / Criticality**: `P1_CORE_DAILY_OPERATION` / `LAUNCH_REQUIRED`
- **Existing Module**: `INVENTORY`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `PARTIAL`, API: `PARTIAL`, Frontend: `PARTIAL`, Offline: `COMPLETE`
- **Target Delta / Missing Behaviors**: Automated sales velocity calculation, CONFIGURABLE_SLOW_MOVING_AND_DEAD_STOCK_CRITERIA, and recommended reorder quantities.

---

### [REQ-0018] Inter-Branch Stock Transfers, Dispatch, In-Transit & Receive Verification

- **Overall Status**: `FOUNDATION_ONLY`
- **Priority / Criticality**: `P1_CORE_DAILY_OPERATION` / `LAUNCH_REQUIRED`
- **Existing Module**: `STOCK_TRANSFERS`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `PARTIAL`, API: `PARTIAL`, Frontend: `MISSING`, Offline: `PARTIAL`
- **Target Delta / Missing Behaviors**: Dedicated Stock Transfer dispatch & receiving workflow with STOCK_TRANSFER_DOCUMENT_AND_GATE_PASS_PRINTING.

---

### [REQ-0019] Purchasing, Supplier Profiles, Bills & Landed Cost Freight Allocation

- **Overall Status**: `MOSTLY_SATISFIED`
- **Priority / Criticality**: `P0_MUST_WORK_TO_RUN_STORE` / `LAUNCH_BLOCKER`
- **Existing Module**: `PURCHASES`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `COMPLETE`, API: `COMPLETE`, Frontend: `PARTIAL`, Offline: `COMPLETE`
- **Target Delta / Missing Behaviors**: Display proportional landed freight cost distribution across line items in purchase view.

---

### [REQ-0020] Supplier Payables Ledger, Supplier Payments & Supplier Returns

- **Overall Status**: `CURRENTLY_SATISFIED`
- **Priority / Criticality**: `P0_MUST_WORK_TO_RUN_STORE` / `LAUNCH_BLOCKER`
- **Existing Module**: `SUPPLIERS`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `COMPLETE`, API: `COMPLETE`, Frontend: `COMPLETE`, Offline: `COMPLETE`
- **Target Delta / Missing Behaviors**: None. Meets acceptance criteria.

---

### [REQ-0021] Supplier Intelligence: Last Purchase Price, Price Change History & Supplier Comparison

- **Overall Status**: `FOUNDATION_ONLY`
- **Priority / Criticality**: `P2_IMPORTANT_MANAGEMENT` / `POST_LAUNCH_SAFE`
- **Existing Module**: `PURCHASES`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `PARTIAL`, API: `PARTIAL`, Frontend: `PARTIAL`, Offline: `COMPLETE`
- **Target Delta / Missing Behaviors**: Historical supplier price comparison tooltip and purchase price increase alerts during bill creation.

---

### [REQ-0022] Daily Store Expense Recording, Categories & Monthly Budgets

- **Overall Status**: `CURRENTLY_SATISFIED`
- **Priority / Criticality**: `P1_CORE_DAILY_OPERATION` / `LAUNCH_REQUIRED`
- **Existing Module**: `EXPENSES`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `COMPLETE`, API: `COMPLETE`, Frontend: `COMPLETE`, Offline: `COMPLETE`
- **Target Delta / Missing Behaviors**: None. Meets acceptance criteria.

---

### [REQ-0023] Multi-Account Treasury Management (Cash Drawer, Banks, Wallets) & Fund Transfers

- **Overall Status**: `CURRENTLY_SATISFIED`
- **Priority / Criticality**: `P1_CORE_DAILY_OPERATION` / `LAUNCH_REQUIRED`
- **Existing Module**: `FINANCIAL_ACCOUNTS`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `COMPLETE`, API: `COMPLETE`, Frontend: `COMPLETE`, Offline: `COMPLETE`
- **Target Delta / Missing Behaviors**: None. Meets acceptance criteria.

---

### [REQ-0024] Cash Register Day-Open & Shift Session Tracking

- **Overall Status**: `FOUNDATION_ONLY`
- **Priority / Criticality**: `P0_MUST_WORK_TO_RUN_STORE` / `LAUNCH_BLOCKER`
- **Existing Module**: `DAILY_CLOSE`
- **Layer Breakdown**: Data Model: `PARTIAL`, Backend: `PARTIAL`, API: `PARTIAL`, Frontend: `MISSING`, Offline: `PARTIAL`
- **Target Delta / Missing Behaviors**: Cash register shift opening float entry, active register session lifecycle, and cash drawer balance tracking.

---

### [REQ-0025] Day-End Cash Balancing, Drawer Reconciliation & Day Close

- **Overall Status**: `FOUNDATION_ONLY`
- **Priority / Criticality**: `P0_MUST_WORK_TO_RUN_STORE` / `LAUNCH_BLOCKER`
- **Existing Module**: `DAILY_CLOSE`
- **Layer Breakdown**: Data Model: `PARTIAL`, Backend: `PARTIAL`, API: `PARTIAL`, Frontend: `MISSING`, Offline: `PARTIAL`
- **Target Delta / Missing Behaviors**: End-of-day register close count entry, expected vs actual cash variance calculation, shortage/excess discrepancy logging, and day-close lock.

---

### [REQ-0026] Store Executive Dashboard & Real-Time Operational KPI Summary

- **Overall Status**: `MOSTLY_SATISFIED`
- **Priority / Criticality**: `P1_CORE_DAILY_OPERATION` / `LAUNCH_REQUIRED`
- **Existing Module**: `DASHBOARD`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `COMPLETE`, API: `COMPLETE`, Frontend: `PARTIAL`, Offline: `COMPLETE`
- **Target Delta / Missing Behaviors**: Branch filter dropdown on dashboard cards and hiding profit stats from Cashier role.

---

### [REQ-0027] Comprehensive Small Retail Reporting & Net Profit Calculation

- **Overall Status**: `MOSTLY_SATISFIED`
- **Priority / Criticality**: `P1_CORE_DAILY_OPERATION` / `LAUNCH_REQUIRED`
- **Existing Module**: `REPORTING`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `COMPLETE`, API: `COMPLETE`, Frontend: `PARTIAL`, Offline: `COMPLETE`
- **Target Delta / Missing Behaviors**: Consolidated Net Profit summary report (Revenue - Cost of Goods - Operating Expenses = True Net Profit).

---

### [REQ-0028] Multi-Branch Comparative Performance & Cross-Store KPI Drilldown

- **Overall Status**: `FOUNDATION_ONLY`
- **Priority / Criticality**: `P1_CORE_DAILY_OPERATION` / `LAUNCH_REQUIRED`
- **Existing Module**: `REPORTING`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `PARTIAL`, API: `PARTIAL`, Frontend: `MISSING`, Offline: `PARTIAL`
- **Target Delta / Missing Behaviors**: Side-by-side branch comparison matrix (Sales, Gross Profit, Expense Ratio, Khata Exposure, Stock Valuation) in UI.

---

### [REQ-0029] Owner-to-Manager Operational Task Delegation, Notes & Resolution Status

- **Overall Status**: `MISSING`
- **Priority / Criticality**: `P2_IMPORTANT_MANAGEMENT` / `POST_LAUNCH_SAFE`
- **Existing Module**: `TASKS_ALERTS`
- **Layer Breakdown**: Data Model: `MISSING`, Backend: `MISSING`, API: `MISSING`, Frontend: `MISSING`, Offline: `NOT_APPLICABLE`
- **Target Delta / Missing Behaviors**: Owner-to-Manager structured action items, task status tracking, and manager acknowledgement.

---

### [REQ-0030] Native Browser IndexedDB Offline POS & Idempotent Sync Queue

- **Overall Status**: `MOSTLY_SATISFIED`
- **Priority / Criticality**: `P0_MUST_WORK_TO_RUN_STORE` / `LAUNCH_BLOCKER`
- **Existing Module**: `OFFLINE`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `COMPLETE`, API: `COMPLETE`, Frontend: `PARTIAL`, Offline: `COMPLETE`
- **Target Delta / Missing Behaviors**: CLEAR_SYNC_STATUS_AND_PENDING_WORK_VISIBILITY with pending transaction counter and manual retry trigger.

---

### [REQ-0031] Pakistani Retail Thermal Printing (80mm/58mm), Branding & Barcode Labels

- **Overall Status**: `PARTIALLY_SATISFIED`
- **Priority / Criticality**: `P1_CORE_DAILY_OPERATION` / `LAUNCH_REQUIRED`
- **Existing Module**: `PRINTING`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `COMPLETE`, API: `COMPLETE`, Frontend: `PARTIAL`, Offline: `COMPLETE`
- **Target Delta / Missing Behaviors**: 58mm/80mm thermal width selector, return policy footer customization, and sticky barcode label sheet printing generator.

---

### [REQ-0032] Material Business Action Audit Trail & Actor Attribution

- **Overall Status**: `MOSTLY_SATISFIED`
- **Priority / Criticality**: `P1_CORE_DAILY_OPERATION` / `LAUNCH_REQUIRED`
- **Existing Module**: `AUDIT_ACTIVITY`
- **Layer Breakdown**: Data Model: `COMPLETE`, Backend: `COMPLETE`, API: `COMPLETE`, Frontend: `PARTIAL`, Offline: `COMPLETE`
- **Target Delta / Missing Behaviors**: Dedicated audit trail table viewer in Settings and interception of price overrides & drawer shortages.

---

