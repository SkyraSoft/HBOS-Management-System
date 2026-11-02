# SKYRASOFT HBOS — ACTIVE MODULE FUNCTIONALITY MATRIX
**Document Reference:** `docs/release/HBOS_ACTIVE_MODULE_FUNCTIONALITY_MATRIX.md`  
**Stage:** Stage 2 — Complete Real-Business Operations, Security, Financial Integrity & Offline Reliability  
**Checkpoints Covered:** Checkpoint 2.2, Checkpoint 2.4  
**Status:** AUTHORITATIVE COMPONENT, FORM & API INTEGRATION AUDIT

---

## 1. Classification Definitions
- `IMPLEMENTED_AND_AUTOMATED_TESTED`: Feature code, UI form, and backend API routes exist and are verified by passing automated unit/feature tests.
- `IMPLEMENTED_AWAITING_WINDOWS_UAT`: Feature implemented and verified in development/automated tests; awaiting final physical product owner verification on Windows desktop hardware.
- `PARTIAL`: Feature partially implemented or requires additional polish/refinement.
- `FAILED`: Test or implementation failure discovered and unresolved.
- `BLOCKED`: Blocked by external hardware dependency (e.g. physical thermal printer or barcode scanner).
- `NOT_TESTED`: Neither automated nor manual test executed.

---

## 2. Core Functional Domains and Active Route Status

### Domain 1: Product Catalog & Master Data
- **Live Component:** `Frontend/src/views/InventryView.vue`
- **Active Routes:** `/products`, `/inventory/products`, `/inventry`
- **Canonical API Endpoints:** `GET/POST /api/v1/products`, `PUT/DELETE /api/v1/products/{id}`, `GET /api/v1/categories`, `GET /api/v1/brands`
- **Audit Findings & Features:**
  - Category and Brand selection: Fully connected.
  - SKU & Barcode uniqueness: Enforced by unique database constraints.
  - Alternate barcode support: Verified (`Cycle4RemediationTest.php`).
  - Fractional loose quantities (scale 3, e.g. 10.500 kg): Supported and preserved across forms and database.
  - Packaged unit conversion: Implemented in model and services.
  - Active / Inactive status toggle: Fully functional; inactive items rejected at checkout.
  - Zero starting stock & legitimate opening stock: Supported.
- **Status:** `IMPLEMENTED_AND_AUTOMATED_TESTED`

### Domain 2: Inventory Ledger & Stock Movement
- **Live Components:** `Frontend/src/views/InventryView.vue`, `Frontend/src/views/ReorderView.vue`, `Frontend/src/views/ReorderDetailView.vue`
- **Active Routes:** `/inventry`, `/reorder`, `/reorderdetail`
- **Canonical API Endpoints:** `GET /api/v1/inventory`, `GET /api/v1/inventory/movements`, `POST /api/v1/inventory/adjustments`, `GET /api/v1/inventory/reorder-suggestions`
- **Audit Findings & Features:**
  - Branch stock balance tracking: Scoped by `branch_id`.
  - Immutable inventory movement ledger: Every sale, return, adjustment, and transfer creates an `InventoryMovement`.
  - Stock adjustments (damage, theft, expiry, audit count): Enforces mandatory adjustment reasons.
  - Reorder suggestions & velocity criteria: Implemented in `StockIntelligenceService`.
  - Negative stock prevention: Concurrency locks and validation reject negative quantities.
- **Status:** `IMPLEMENTED_AND_AUTOMATED_TESTED`

### Domain 3: POS Checkout Counter
- **Live Component:** `Frontend/src/views/PosSaleView.vue`
- **Active Routes:** `/POS`, `/pos`
- **Canonical API Endpoints:** `GET /api/v1/products`, `POST /api/v1/sales`, `GET /api/v1/cash-registers/active`, `POST /api/v1/sync/bulk-transactions`
- **Audit Findings & Features:**
  - Owner & Cashier checkout counter: POS-first landing for Salesperson role.
  - Fast barcode/SKU/name lookup: Fully functional.
  - Multi-item cart & fractional quantities: Supported.
  - Cart Hold & Resume: Up to 5 concurrent held carts supported.
  - Split tender (Cash, Card, JazzCash/Easypaisa, Khata): Handled with exact change calculation.
  - Authorized discount & price overrides: Audited and logged.
  - Offline durable checkout: Syncs via IndexedDB outbox.
- **Status:** `IMPLEMENTED_AND_AUTOMATED_TESTED`

### Domain 4: Customers & Khata Ledger
- **Live Components:** `Frontend/src/views/CustomerView.vue`, `Frontend/src/views/ProfilecustomerView.vue`, `Frontend/src/views/KhataView.vue`
- **Active Routes:** `/customer`, `/customers`, `/profilecustomer`, `/khata`, `/khatas`
- **Canonical API Endpoints:** `GET/POST /api/v1/customers`, `GET /api/v1/customers/{id}/ledger`, `POST /api/v1/customers/{id}/payments`, `POST /api/v1/customer-payments/{id}/reverse`
- **Audit Findings & Features:**
  - Customer directory & Pakistani phone validation: Fully implemented.
  - Credit limit checks: Checked during POS credit checkout.
  - Khata repayment endpoint: Canonical route `POST /api/v1/customers/{id}/payments`.
  - Overpayment rejection: Repayments exceeding outstanding balance rejected with 422.
  - Payment reversal: Restores receivable and drawer balance with auditable compensating movement.
  - Customer profile & Khata statement parity: Synchronized.
- **Status:** `IMPLEMENTED_AND_AUTOMATED_TESTED`

### Domain 5: Suppliers & Purchases
- **Live Components:** `Frontend/src/views/PurchaseView.vue`, `Frontend/src/views/SupplierView.vue`, `Frontend/src/views/SupplierpayableView.vue`, `Frontend/src/views/SupplierPaymentView.vue`
- **Active Routes:** `/purchase`, `/supplier`, `/supplierpayable`, `/supplierpayment`
- **Canonical API Endpoints:** `GET/POST /api/v1/purchases`, `GET/POST /api/v1/suppliers`, `POST /api/v1/suppliers/{id}/payments`, `POST /api/v1/supplier-payments/{id}/reverse`
- **Audit Findings & Features:**
  - Supplier management & contacts: Fully connected.
  - Purchase orders: Handles paid, unpaid, and partial payment terms.
  - Landed cost & freight allocation: Proportionally allocated across PO line items.
  - Supplier cash payouts: Creates corresponding cash drawer outflow movement.
  - Outstanding payables ledger: Accurate `due_amount` tracking without simulated figures.
- **Status:** `IMPLEMENTED_AND_AUTOMATED_TESTED`

### Domain 6: Returns, Exchanges & Refunds
- **Live Component:** `Frontend/src/views/SalesView.vue`
- **Active Routes:** `/sales`
- **Canonical API Endpoints:** `POST /api/v1/sales/{id}/returns`, `POST /api/v1/sale-returns/{id}/settle-refund`, `POST /api/v1/sale-returns/{id}/reverse-refund`
- **Audit Findings & Features:**
  - Original sale lookup & item selection: Verified.
  - Restockable vs damaged item separation: Restockable items restore inventory and reverse COGS; damaged items do not restore sellable stock.
  - Cash refund settlement: Produces auditable cash drawer outflow.
  - Replay and duplicate refund prevention: Enforced.
- **Status:** `IMPLEMENTED_AND_AUTOMATED_TESTED`

### Domain 7: Cash Drawer, Accounts & Treasury
- **Live Component:** `Frontend/src/views/ExpensesView.vue`, `Frontend/src/views/SettingsView.vue` (Tabs: Sync, Security)
- **Active Routes:** `/expenses`, `/settings`
- **Canonical API Endpoints:** `GET /api/v1/cash-register-sessions/current`, `POST /api/v1/cash-register-sessions/open`, `POST /api/v1/cash-register-sessions/close`, `GET/POST /api/v1/expenses`, `POST /api/v1/financial-accounts/{id}/capital-in`
- **Audit Findings & Features:**
  - Automatic cash drawer provisioning upon registration: Default cash account created with status `active` and 0.00 balance.
  - Opening float establishment: Initialized upon register open or capital-in.
  - Exact mathematical cash closing: `Expected = Float + Inflows - Outflows`.
  - Discrepancy reasoning: Variance reason required if physical count deviates.
  - Closed session immutability: Cannot be reopened or modified.
- **Status:** `IMPLEMENTED_AND_AUTOMATED_TESTED`

### Domain 8: Dashboards & Reporting
- **Live Components:** `Frontend/src/views/DashboardView.vue`, `Frontend/src/views/ReportsView.vue`
- **Active Routes:** `/` (alias `/dashboard`), `/reports`, `/analytics`
- **Canonical API Endpoints:** `GET /api/v1/dashboard`, `GET /api/v1/dashboard/stats`, `GET /api/v1/reports/sales`, `GET /api/v1/reports/profitability`, `GET /api/v1/reports/inventory`
- **Audit Findings & Features:**
  - Small Retail Net Profitability Statement: Net sales, gross profit, operating expenses, and net profit strictly calculated.
  - Cashier privacy: Salespersons denied executive dashboards and reports (403).
  - Multi-branch filtering: Branch Managers restricted to assigned branch; Business Owners access All Stores consolidated reports.
  - Truth in financial reporting: Zero hardcoded metrics.
- **Status:** `IMPLEMENTED_AND_AUTOMATED_TESTED`

---

## 3. Physical Hardware Interfaces (Release Constraints)

| Hardware Category | Subsystem | Software Readiness | Hardware Test Disposition |
|---|---|---|---|
| **Thermal Receipt Printers** | 58mm & 80mm ESC/POS formatting | Implemented in CSS and printing templates | `BLOCKED` (Pending physical printer testing) |
| **USB Barcode Scanners** | Keyboard-wedge emulation | Implemented via global key-listener | `IMPLEMENTED_AWAITING_WINDOWS_UAT` |
| **Cash Drawers** | RJ11/RJ12 drawer kick via printer driver | Implemented via driver kick code | `BLOCKED` (Pending physical cash drawer testing) |
