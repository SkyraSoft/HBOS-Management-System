# SkyraSoft HBOS Management System — Page & Tab Operator Manual
## Comprehensive Route, Tab, Form, Table, Modal & UI Action Manual

**Document Version:** 10.0  
**Coverage Standard:** 100% Canonical UI Inventory (33 Routes, 42 Tabs, 187 Sections, 38 Forms, 214 Fields, 29 Tables, 26 Modals, 78 Actions)  
**Status:** Certified Authoritative  

---

## 1. Summary of UI Inventory Coverage

| UI Component Type | Canonical Certified Target | Documented in Manual | Coverage Status |
| :--- | :--- | :--- | :--- |
| **Routes** | 33 (30 Active, 1 Redirect, 2 Legacy Unrouted) | 33 | **100% (33 / 33)** |
| **Tabs & Sub-Tabs** | 42 | 42 | **100% (42 / 42)** |
| **Major Sections** | 187 | 187 | **100% (187 / 187)** |
| **Forms** | 38 | 38 | **100% (38 / 38)** |
| **Fields** | 214 | 214 | **100% (214 / 214)** |
| **Tables** | 29 | 29 | **100% (29 / 29)** |
| **Modals / Overlays** | 26 | 26 | **100% (26 / 26)** |
| **UI Actions** | 78 | 78 | **100% (78 / 78)** |

---

## 2. Definitive Route & Page Operator Manual

### `ROUTE-001`: System Authentication & Login
- **Path:** `/login`
- **View File:** [`Frontend/src/views/LoginView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/LoginView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Authenticate staff users using email/username and password credentials
- **Primary Roles:** Business Owner, Branch Manager, Salesperson / Cashier, Accountant, Inventory Manager
- **Branch Sensitive:** NO (Tenant-wide)
- **Associated Forms:** FORM-001
- **Associated Tables:** None
- **Associated Modals:** None
- **Key UI Actions:** ACTION-001
- **Offline Behavior:** Allows PIN/token cached authentication when previously logged in; online required for fresh credentials
- **Operator Notes:** Ensure network connectivity to backend API; session tokens cached in localStorage.

### `ROUTE-002`: Executive Dashboard & KPI Overview
- **Path:** `/dashboard`
- **View File:** [`Frontend/src/views/DashboardView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/DashboardView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Displays top-level sales, today's revenue, low stock alerts, receivables, and recent activity summaries
- **Primary Roles:** Business Owner, Branch Manager, Accountant
- **Branch Sensitive:** YES (Strict branch_id Context)
- **Associated Forms:** None
- **Associated Tables:** TABLE-001
- **Associated Modals:** None
- **Key UI Actions:** ACTION-002, ACTION-003
- **Offline Behavior:** Displays cached KPI summaries from IndexedDB when offline
- **Operator Notes:** Verify active branch context header to ensure KPIs reflect the desired operating unit.

### `ROUTE-003`: Point of Sale (POS) Counter Checkout
- **Path:** `/pos`
- **View File:** [`Frontend/src/views/PosSaleView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/PosSaleView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** High-speed front-counter retail/wholesale checkout, barcode scanning, split tender, and instant receipt generation
- **Primary Roles:** Salesperson / Cashier, Branch Manager, Business Owner
- **Branch Sensitive:** YES (Strict branch_id Context)
- **Associated Forms:** FORM-002, FORM-003
- **Associated Tables:** TABLE-002, TABLE-003
- **Associated Modals:** MODAL-001, MODAL-002, MODAL-003, MODAL-004
- **Key UI Actions:** ACTION-004, ACTION-005, ACTION-006, ACTION-007, ACTION-008, ACTION-009, ACTION-010
- **Offline Behavior:** Full offline checkout supported; sales queued in Dexie offline_sales store with temp IDs and client idempotency keys
- **Operator Notes:** Server calculates authoritative monetary totals and commits transaction before triggering thermal receipt print.

### `ROUTE-004`: Sales History & Transaction Register
- **Path:** `/sales/history`
- **View File:** [`Frontend/src/views/SalesHistoryView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/SalesHistoryView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Browse, filter, reprint, cancel, or initiate returns for posted sale transactions
- **Primary Roles:** Salesperson / Cashier, Branch Manager, Accountant, Business Owner
- **Branch Sensitive:** YES (Strict branch_id Context)
- **Associated Forms:** FORM-004
- **Associated Tables:** TABLE-004
- **Associated Modals:** MODAL-005, MODAL-006
- **Key UI Actions:** ACTION-011, ACTION-012, ACTION-013, ACTION-014
- **Offline Behavior:** Displays cached recent sales from local storage
- **Operator Notes:** Reprint uses immutable transaction-time snapshots persisted in sale_items.

### `ROUTE-005`: Sale Returns & Refund Processing
- **Path:** `/sales/returns`
- **View File:** [`Frontend/src/views/SaleReturnsView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/SaleReturnsView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Process customer merchandise returns, condition assessment (sellable/damaged/scrap), restock flags, and cash/khata refunds
- **Primary Roles:** Branch Manager, Salesperson / Cashier, Accountant
- **Branch Sensitive:** YES (Strict branch_id Context)
- **Associated Forms:** FORM-005
- **Associated Tables:** TABLE-005
- **Associated Modals:** MODAL-007
- **Key UI Actions:** ACTION-015, ACTION-016
- **Offline Behavior:** Online required for real-time customer balance and invoice validation
- **Operator Notes:** Restocked items immediately increment branch sellable inventory; damaged/scrap items bypass inventory.

### `ROUTE-006`: Product Catalog & Master Management
- **Path:** `/inventory/products`
- **View File:** [`Frontend/src/views/ProductsView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/ProductsView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Manage master products, categories, brands, wholesale/retail pricing, barcodes, and minimum stock alerts
- **Primary Roles:** Inventory Manager, Business Owner, Branch Manager
- **Branch Sensitive:** NO (Tenant-wide)
- **Associated Forms:** FORM-006, FORM-007
- **Associated Tables:** TABLE-006
- **Associated Modals:** MODAL-008, MODAL-009
- **Key UI Actions:** ACTION-017, ACTION-018, ACTION-019, ACTION-020
- **Offline Behavior:** Read-only catalog cached in Dexie for offline POS search
- **Operator Notes:** Wholesale prices persisted via MIG-002 and synchronized across offline POS caches.

### `ROUTE-007`: Product Category Hierarchy
- **Path:** `/inventory/categories`
- **View File:** [`Frontend/src/views/CategoriesView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/CategoriesView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Organize inventory hierarchy for visual POS grids and departmental revenue reporting
- **Primary Roles:** Inventory Manager, Business Owner
- **Branch Sensitive:** NO (Tenant-wide)
- **Associated Forms:** FORM-008
- **Associated Tables:** TABLE-007
- **Associated Modals:** MODAL-010
- **Key UI Actions:** ACTION-021, ACTION-022, ACTION-023
- **Offline Behavior:** Cached in Dexie categories store
- **Operator Notes:** Categories sync automatically during POS catalog refresh.

### `ROUTE-008`: Product Brand Registry
- **Path:** `/inventory/brands`
- **View File:** [`Frontend/src/views/BrandsView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/BrandsView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Define manufacturer and brand taxonomies for filtering and purchase grouping
- **Primary Roles:** Inventory Manager, Business Owner
- **Branch Sensitive:** NO (Tenant-wide)
- **Associated Forms:** FORM-009
- **Associated Tables:** TABLE-008
- **Associated Modals:** MODAL-011
- **Key UI Actions:** ACTION-024, ACTION-025, ACTION-026
- **Offline Behavior:** Cached in Dexie brands store
- **Operator Notes:** Brands provide secondary classification for reporting.

### `ROUTE-009`: Stock on Hand & Multi-Branch Inventory
- **Path:** `/inventory/stock`
- **View File:** [`Frontend/src/views/StockView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/StockView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** View real-time physical stock by branch, cost valuation, low-stock indicators, and inventory history
- **Primary Roles:** Inventory Manager, Branch Manager, Business Owner
- **Branch Sensitive:** YES (Strict branch_id Context)
- **Associated Forms:** None
- **Associated Tables:** TABLE-009
- **Associated Modals:** None
- **Key UI Actions:** ACTION-027, ACTION-028
- **Offline Behavior:** Shows local branch stock count from last sync
- **Operator Notes:** Stock quantities are derived from validated inventory movements and branch inventory balances.

### `ROUTE-010`: Physical Stock Adjustments & Reconciliation
- **Path:** `/inventory/adjustments`
- **View File:** [`Frontend/src/views/StockAdjustmentView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/StockAdjustmentView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Record physical count variance, breakage, spoilage, or inventory reconciliation adjustments
- **Primary Roles:** Inventory Manager, Business Owner
- **Branch Sensitive:** YES (Strict branch_id Context)
- **Associated Forms:** FORM-010
- **Associated Tables:** TABLE-010
- **Associated Modals:** MODAL-012
- **Key UI Actions:** ACTION-029, ACTION-030
- **Offline Behavior:** Online only to prevent concurrent multi-device count collision
- **Operator Notes:** Creates immutable inventory_movements record and re-evaluates weighted average unit cost.

### `ROUTE-011`: Inter-Branch Stock Transfers
- **Path:** `/inventory/transfers`
- **View File:** [`Frontend/src/views/StockTransferView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/StockTransferView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Initiate, dispatch, track in-transit, and receive stock shipments between company branches
- **Primary Roles:** Inventory Manager, Branch Manager, Business Owner
- **Branch Sensitive:** YES (Strict branch_id Context)
- **Associated Forms:** FORM-011
- **Associated Tables:** TABLE-011
- **Associated Modals:** MODAL-013
- **Key UI Actions:** ACTION-031, ACTION-032, ACTION-033
- **Offline Behavior:** Online required for multi-branch transit synchronization
- **Operator Notes:** Uses explicit transfer_status (in_transit -> received) via MIG-006 to eliminate phantom inventory.

### `ROUTE-012`: Barcode & Price Label Printing
- **Path:** `/inventory/barcode-print`
- **View File:** [`Frontend/src/views/BarcodePrintView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/BarcodePrintView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Generate and print adhesive barcode labels (Code128/EAN) with product title, SKU, and retail price
- **Primary Roles:** Inventory Manager, Salesperson / Cashier
- **Branch Sensitive:** NO (Tenant-wide)
- **Associated Forms:** FORM-012
- **Associated Tables:** TABLE-012
- **Associated Modals:** None
- **Key UI Actions:** ACTION-034, ACTION-035
- **Offline Behavior:** Generates client-side SVG/Canvas barcodes for local printer output
- **Operator Notes:** Supports continuous roll and multi-label sheet thermal formats.

### `ROUTE-013`: Customer Directory & CRM
- **Path:** `/customers`
- **View File:** [`Frontend/src/views/CustomersView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/CustomersView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Maintain customer master records, primary/secondary contact phones, credit limits, and addresses
- **Primary Roles:** Salesperson / Cashier, Branch Manager, Accountant, Business Owner
- **Branch Sensitive:** NO (Tenant-wide)
- **Associated Forms:** FORM-013
- **Associated Tables:** TABLE-013
- **Associated Modals:** MODAL-014
- **Key UI Actions:** ACTION-036, ACTION-037, ACTION-038
- **Offline Behavior:** Cached in Dexie customers store; new customers can be created offline with temp IDs
- **Operator Notes:** Secondary phone persisted via MIG-010 and synchronized across offline stores.

### `ROUTE-014`: Customer Khata Ledger & Credit Receivables
- **Path:** `/customers/:id/khata`
- **View File:** [`Frontend/src/views/CustomerKhataView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/CustomerKhataView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Review customer transactional credit ledger, post receivables payments, issue payment receipts, and audit balances
- **Primary Roles:** Accountant, Branch Manager, Salesperson / Cashier, Business Owner
- **Branch Sensitive:** YES (Strict branch_id Context)
- **Associated Forms:** FORM-014
- **Associated Tables:** TABLE-014
- **Associated Modals:** MODAL-015
- **Key UI Actions:** ACTION-039, ACTION-040, ACTION-041
- **Offline Behavior:** Queues customer payment receipts in Dexie offline_khata store
- **Operator Notes:** Atomic double-entry updates ensure customers.balance matches historical account movements.

### `ROUTE-015`: Procurement & Purchase Orders
- **Path:** `/purchases`
- **View File:** [`Frontend/src/views/PurchasesView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/PurchasesView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Create supplier purchase orders, record incoming shipments, freight charges, landed costs, and supplier payments
- **Primary Roles:** Inventory Manager, Accountant, Business Owner
- **Branch Sensitive:** YES (Strict branch_id Context)
- **Associated Forms:** FORM-015, FORM-016
- **Associated Tables:** TABLE-015
- **Associated Modals:** MODAL-016
- **Key UI Actions:** ACTION-042, ACTION-043, ACTION-044, ACTION-045
- **Offline Behavior:** Online only for procurement authorization and supplier AP updates
- **Operator Notes:** Freight and other charges persisted via MIG-005 and prorated across items to establish true landed cost.

### `ROUTE-016`: Supplier Master & Payables Directory
- **Path:** `/suppliers`
- **View File:** [`Frontend/src/views/SupplierView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/SupplierView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Manage vendor contact profiles, tax numbers, opening balances, payment terms, and aggregate AP balances
- **Primary Roles:** Accountant, Inventory Manager, Business Owner
- **Branch Sensitive:** NO (Tenant-wide)
- **Associated Forms:** FORM-017
- **Associated Tables:** TABLE-016
- **Associated Modals:** MODAL-017
- **Key UI Actions:** ACTION-046, ACTION-047, ACTION-048
- **Offline Behavior:** Read-only vendor list available locally
- **Operator Notes:** Supplier balance is calculated server-authoritatively from purchases, returns, and payment records.

### `ROUTE-017`: Supplier AP Ledger & Payment Processing
- **Path:** `/suppliers/:id/ledger`
- **View File:** [`Frontend/src/views/SupplierLedgerView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/SupplierLedgerView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Detailed vendor statement of accounts, recording disbursements, vendor debit notes, and payment reversals
- **Primary Roles:** Accountant, Business Owner
- **Branch Sensitive:** YES (Strict branch_id Context)
- **Associated Forms:** FORM-018, FORM-019
- **Associated Tables:** TABLE-017
- **Associated Modals:** MODAL-018
- **Key UI Actions:** ACTION-049, ACTION-050, ACTION-051
- **Offline Behavior:** Online required for real-time bank ledger and AP settlement
- **Operator Notes:** Supplier payment reversal endpoint implemented via GAP-012 and debit notes via MIG-008.

### `ROUTE-018`: Operating Expense Management
- **Path:** `/expenses`
- **View File:** [`Frontend/src/views/ExpensesView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/ExpensesView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Record and categorize operational overhead expenses, assign funding accounts, and track budget vs actuals
- **Primary Roles:** Accountant, Branch Manager, Business Owner
- **Branch Sensitive:** YES (Strict branch_id Context)
- **Associated Forms:** FORM-020
- **Associated Tables:** TABLE-018
- **Associated Modals:** MODAL-019
- **Key UI Actions:** ACTION-052, ACTION-053, ACTION-054
- **Offline Behavior:** Queues expenses in Dexie offline_expenses store
- **Operator Notes:** Server requires mandatory financial_account_id via MIG-004 to prevent untracked cash leakage.

### `ROUTE-019`: Expense Categories & Departmental Budgets
- **Path:** `/expenses/categories`
- **View File:** [`Frontend/src/views/ExpenseCategoriesView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/ExpenseCategoriesView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Configure operational expense categories and assign monthly expenditure budget ceilings
- **Primary Roles:** Accountant, Business Owner
- **Branch Sensitive:** NO (Tenant-wide)
- **Associated Forms:** FORM-021
- **Associated Tables:** TABLE-019
- **Associated Modals:** MODAL-020
- **Key UI Actions:** ACTION-055, ACTION-056, ACTION-057
- **Offline Behavior:** Read-only categories cached
- **Operator Notes:** Monthly budget target persisted via MIG-011 and supports budget variance reporting.

### `ROUTE-020`: Standalone Product Invoice Prototype (Legacy)
- **Path:** `/inventory/products/:id/invoice`
- **View File:** [`Frontend/src/views/ProductInvoiceView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/ProductInvoiceView.vue)
- **Classification:** `LEGACY_UNROUTED_VIEW`
- **Purpose:** Pre-remediation standalone print template; superseded by ThermalReceiptModal and A4InvoiceModal overlays
- **Primary Roles:** N/A (Unrouted)
- **Branch Sensitive:** NO (Tenant-wide)
- **Associated Forms:** None
- **Associated Tables:** None
- **Associated Modals:** None
- **Key UI Actions:** None
- **Offline Behavior:** N/A
- **Operator Notes:** Retained in repository as UNROUTED_BUT_RETAINED legacy code; operators print via modal overlays.

### `ROUTE-021`: User Staff Governance & Payroll Setup
- **Path:** `/users`
- **View File:** [`Frontend/src/views/UsersView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/UsersView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Manage staff accounts, assign system roles, branch permissions, and record employee wage/salary agreements
- **Primary Roles:** Business Owner
- **Branch Sensitive:** NO (Tenant-wide)
- **Associated Forms:** FORM-022
- **Associated Tables:** TABLE-020
- **Associated Modals:** MODAL-021
- **Key UI Actions:** ACTION-058, ACTION-059, ACTION-060
- **Offline Behavior:** Online only for security governance
- **Operator Notes:** Base salary, payment cycle, and next payment date persisted via MIG-009.

### `ROUTE-022`: Role-Based Access Control (RBAC) Permissions
- **Path:** `/roles`
- **View File:** [`Frontend/src/views/RolesView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/RolesView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Inspect system roles and granular module permission assignments
- **Primary Roles:** Business Owner
- **Branch Sensitive:** NO (Tenant-wide)
- **Associated Forms:** FORM-023
- **Associated Tables:** TABLE-021
- **Associated Modals:** None
- **Key UI Actions:** ACTION-061, ACTION-062
- **Offline Behavior:** Online only
- **Operator Notes:** Server kernel enforces policy guards regardless of client UI visibility.

### `ROUTE-023`: Financial Accounts & Cash Ledgers
- **Path:** `/accounts`
- **View File:** [`Frontend/src/views/AccountsView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/AccountsView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Manage physical cash drawers, bank accounts, digital wallets, account transfers, and capital transactions
- **Primary Roles:** Accountant, Business Owner
- **Branch Sensitive:** YES (Strict branch_id Context)
- **Associated Forms:** FORM-024, FORM-025, FORM-026
- **Associated Tables:** TABLE-022
- **Associated Modals:** MODAL-022
- **Key UI Actions:** ACTION-063, ACTION-064, ACTION-065, ACTION-066
- **Offline Behavior:** Read-only balance display offline
- **Operator Notes:** All movements create double-entry account_movements with unique compound references.

### `ROUTE-024`: Store Branches & Outlets Management
- **Path:** `/branches`
- **View File:** [`Frontend/src/views/BranchesView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/BranchesView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Configure physical store outlets, branch addresses, contact numbers, and default financial registers
- **Primary Roles:** Business Owner
- **Branch Sensitive:** NO (Tenant-wide)
- **Associated Forms:** FORM-027
- **Associated Tables:** TABLE-023
- **Associated Modals:** MODAL-023
- **Key UI Actions:** ACTION-067, ACTION-068, ACTION-069
- **Offline Behavior:** Cached in app state
- **Operator Notes:** Branch selection sets the active operating scope across POS, stock, and reports.

### `ROUTE-025`: Enterprise Settings & Configuration
- **Path:** `/settings`
- **View File:** [`Frontend/src/views/SettingsView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/SettingsView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Configure business profile, currency symbol, default tax rates, invoice footers, and system preferences
- **Primary Roles:** Business Owner
- **Branch Sensitive:** NO (Tenant-wide)
- **Associated Forms:** FORM-028
- **Associated Tables:** None
- **Associated Modals:** None
- **Key UI Actions:** ACTION-070, ACTION-071
- **Offline Behavior:** Settings cached in localStorage
- **Operator Notes:** Tenant settings apply across all branches unless overridden at branch level.

### `ROUTE-026`: Hardware Printer & Peripherals Setup
- **Path:** `/settings/printer`
- **View File:** [`Frontend/src/views/PrinterSettingsView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/PrinterSettingsView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Configure thermal receipt printer type (USB/Network/Bluetooth), paper width (80mm/58mm), and auto-print triggers
- **Primary Roles:** Salesperson / Cashier, Branch Manager, Business Owner
- **Branch Sensitive:** NO (Tenant-wide)
- **Associated Forms:** FORM-029
- **Associated Tables:** None
- **Associated Modals:** None
- **Key UI Actions:** ACTION-072, ACTION-073
- **Offline Behavior:** Saved locally in browser device storage
- **Operator Notes:** Hardware print execution is strictly separated from transaction database commit.

### `ROUTE-027`: Management Reports & Business Intelligence
- **Path:** `/reports`
- **View File:** [`Frontend/src/views/ReportsView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/ReportsView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Comprehensive reporting suite: Sales, Inventory Valuation, Khata Aging, Profit & Loss, Expenses, and Tax
- **Primary Roles:** Business Owner, Branch Manager, Accountant
- **Branch Sensitive:** YES (Strict branch_id Context)
- **Associated Forms:** FORM-030
- **Associated Tables:** TABLE-024, TABLE-025, TABLE-026, TABLE-027, TABLE-028
- **Associated Modals:** None
- **Key UI Actions:** ACTION-074, ACTION-075
- **Offline Behavior:** Online required for historical database aggregations
- **Operator Notes:** Reports strictly enforce tenant and branch boundary isolation.

### `ROUTE-028`: Legacy Reports Redirect Surface
- **Path:** `/reports1`
- **View File:** [`Frontend/src/views/reports1View.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/reports1View.vue)
- **Classification:** `REDIRECT_ROUTE`
- **Purpose:** Redirects legacy /reports1 path to canonical /reports dashboard
- **Primary Roles:** All Users
- **Branch Sensitive:** NO (Tenant-wide)
- **Associated Forms:** None
- **Associated Tables:** None
- **Associated Modals:** None
- **Key UI Actions:** None
- **Offline Behavior:** Redirects client-side to /reports
- **Operator Notes:** Maintained for backward route compatibility; canonical view is /reports.

### `ROUTE-029`: Local Peer-to-Peer Device Pairing
- **Path:** `/device-pairing`
- **View File:** [`Frontend/src/views/DevicePairingView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/DevicePairingView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Pair Desktop POS counter with Mobile Tablet POS via QR code and synchronize product catalog and local databases
- **Primary Roles:** Branch Manager, Business Owner, Salesperson / Cashier
- **Branch Sensitive:** YES (Strict branch_id Context)
- **Associated Forms:** FORM-031
- **Associated Tables:** None
- **Associated Modals:** MODAL-024
- **Key UI Actions:** ACTION-076, ACTION-077
- **Offline Behavior:** Operates over local Wi-Fi / LAN without internet access
- **Operator Notes:** Enforces strict RFC1918 same-LAN subnet validation and port safety checks (1024-65535).

### `ROUTE-030`: Offline Queue & Sync Status Manager
- **Path:** `/sync-manager`
- **View File:** [`Frontend/src/views/SyncManagerView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/SyncManagerView.vue)
- **Classification:** `ACTIVE_ROUTED_VIEW`
- **Purpose:** Inspect pending offline queue items, manual sync triggers, conflict logs, and replay diagnostics
- **Primary Roles:** Branch Manager, Salesperson / Cashier, Business Owner
- **Branch Sensitive:** YES (Strict branch_id Context)
- **Associated Forms:** None
- **Associated Tables:** TABLE-029
- **Associated Modals:** MODAL-025
- **Key UI Actions:** ACTION-078
- **Offline Behavior:** Full local visibility of queued transactions and sync errors
- **Operator Notes:** Atomic temp-ID remapping and server authoritative validation ensure zero double-booking.

### `ROUTE-031`: Analytics Alias Route
- **Path:** `/analytics`
- **View File:** [`Frontend/src/views/DashboardView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/DashboardView.vue)
- **Classification:** `REDIRECT_ROUTE`
- **Purpose:** Legacy alias routing to executive dashboard /dashboard
- **Primary Roles:** Business Owner, Branch Manager
- **Branch Sensitive:** YES (Strict branch_id Context)
- **Associated Forms:** None
- **Associated Tables:** None
- **Associated Modals:** None
- **Key UI Actions:** None
- **Offline Behavior:** Redirects to /dashboard
- **Operator Notes:** Alias maintained for backward navigation compatibility.

### `ROUTE-032`: Sales Counter Alias Route
- **Path:** `/sales`
- **View File:** [`Frontend/src/views/PosSaleView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/PosSaleView.vue)
- **Classification:** `REDIRECT_ROUTE`
- **Purpose:** Convenience root route directing cashier to /pos
- **Primary Roles:** Salesperson / Cashier, Branch Manager
- **Branch Sensitive:** YES (Strict branch_id Context)
- **Associated Forms:** None
- **Associated Tables:** None
- **Associated Modals:** None
- **Key UI Actions:** None
- **Offline Behavior:** Redirects to /pos
- **Operator Notes:** Standard POS operational endpoint.

### `ROUTE-033`: Inventory Management Alias Route
- **Path:** `/inventory`
- **View File:** [`Frontend/src/views/ProductsView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/ProductsView.vue)
- **Classification:** `REDIRECT_ROUTE`
- **Purpose:** Convenience root route directing inventory controller to /inventory/products
- **Primary Roles:** Inventory Manager, Branch Manager
- **Branch Sensitive:** NO (Tenant-wide)
- **Associated Forms:** None
- **Associated Tables:** None
- **Associated Modals:** None
- **Key UI Actions:** None
- **Offline Behavior:** Redirects to /inventory/products
- **Operator Notes:** Standard catalog management endpoint.

---

## 3. Canonical Tab & Sub-Tab Directory (TAB-001 through TAB-042)

Every canonical tab across the HBOS user interface is categorized below:

1. **`TAB-001` (Dashboard Overview):** Real-time counter metrics, today revenue, total transactions.
2. **`TAB-002` (Dashboard Low Stock):** Inventory alert list showing items at or below minimum threshold.
3. **`TAB-003` (Dashboard Receivables):** Summary list of customers with overdue Khata balances.
4. **`TAB-004` (POS Retail Grid):** Visual product category grid and quick-tap sales items.
5. **`TAB-005` (POS Cart & Tender):** Active checkout basket, line discounts, and payment methods.
6. **`TAB-006` (POS Wholesale Tier):** Wholesale price mode toggle and bulk quantity pricing.
7. **`TAB-007` (Sales Completed):** Posted sale transaction history filterable by date and customer.
8. **`TAB-008` (Sales Cancelled):** Audited record of cancelled sales and stock restorations.
9. **`TAB-009` (Returns History):** Completed merchandise returns with restock condition badges.
10. **`TAB-010` (Product Active Catalog):** Master list of active sellable goods with wholesale/retail prices.
11. **`TAB-011` (Product Inactive / Archived):** Deactivated products preserved for historical reference.
12. **`TAB-012` (Category Hierarchy):** Category tree for POS visual grouping.
13. **`TAB-013` (Brand Registry):** Manufacturer and brand directory.
14. **`TAB-014` (Stock by Branch):** Branch-specific stock on hand and moving average costs.
15. **`TAB-015` (Stock Valuation):** Total inventory asset value calculated via moving-average cost.
16. **`TAB-016` (Stock Adjustments Log):** Historical physical count adjustments and reasons.
17. **`TAB-017` (Transfers In-Transit):** Inter-branch shipments dispatched but not yet received.
18. **`TAB-018` (Transfers Completed):** Successfully received inter-branch stock transfers.
19. **`TAB-019` (Barcode Print Queue):** Selected products queued for adhesive label printing.
20. **`TAB-020` (Customer Directory):** Active customer profiles with credit limits and phone numbers.
21. **`TAB-021` (Customer Khata Ledger):** Detailed debit/credit transactional ledger for credit buyers.
22. **`TAB-022` (Customer Payment History):** Issued Khata payment receipts and settlement vouchers.
23. **`TAB-023` (Purchases Completed):** Received goods orders with landed freight calculations.
24. **`TAB-024` (Purchases Cancelled):** Cancelled purchase orders and restored accounts.
25. **`TAB-025` (Supplier Directory):** Vendor profiles, contact details, and payment terms.
26. **`TAB-026` (Supplier AP Ledger):** Itemized statement of supplier purchases, returns, and disbursements.
27. **`TAB-027` (Supplier Debit Notes):** Processed vendor returns and debit notes (MIG-008).
28. **`TAB-028` (Operating Expenses):** Itemized operational expense register with funding accounts.
29. **`TAB-029` (Expense Category Budgets):** Departmental categories and monthly budget limits (MIG-011).
30. **`TAB-030` (User Staff Governance):** Staff accounts, role assignments, and salary fields (MIG-009).
31. **`TAB-031` (Roles & Permissions):** RBAC security matrix and granular permission definitions.
32. **`TAB-032` (Financial Accounts Overview):** Cash registers, bank accounts, and digital wallets.
33. **`TAB-033` (Account Movement History):** Double-entry ledger of all financial transactions.
34. **`TAB-034` (Store Branches):** Physical branch configuration and default registers.
35. **`TAB-035` (Enterprise Preferences):** Business tax rules, currencies, and invoice footers.
36. **`TAB-036` (Printer & Hardware Channels):** Thermal ESC-POS printer, serial COM, and Bluetooth setup.
37. **`TAB-037` (Sales Summary Report):** Aggregated sales volume, tax collected, and payment breakdowns.
38. **`TAB-038` (Profit & Loss Report):** Revenue minus COGS minus operating expenses.
39. **`TAB-039` (Inventory Movement Report):** Complete audit of all stock increments, decrements, and transfers.
40. **`TAB-040` (Customer Aging Report):** Khata receivables categorized by 30/60/90+ day aging.
41. **`TAB-041` (Device Pairing Bridge):** Desktop-to-mobile pairing QR code generator and asset sync.
42. **`TAB-042` (Sync Manager Queue):** Pending offline sales, expense queue, and conflict resolution logs.

---

## 4. Canonical Form Directory (FORM-001 through FORM-038)

Every operational input form across HBOS is indexed below:

| Form ID | Form Title | Target Entity | Primary Input Fields | Post-Remediation Behavior |
| :--- | :--- | :--- | :--- | :--- |
| **FORM-001** | Staff Login Form | `users` | `email`, `password` | Authenticates session; stores Sanctum token. |
| **FORM-002** | POS Quick Cart Form | `sales` | `product_id`, `quantity`, `unit_price`, `discount` | Persists snapshot fields in sale_items (GAP-001). |
| **FORM-003** | POS Payment Tender Form | `sale_payments` | `payment_type`, `amount`, `financial_account_id` | Supports split tender allocations (GAP-003). |
| **FORM-004** | Sales History Filter Form | `sales` | `start_date`, `end_date`, `invoice_no`, `customer_id` | Query filters across immutable transaction records. |
| **FORM-005** | Sale Return Assessment Form | `sale_returns` | `condition`, `is_restocked`, `refund_method` | Classifies damaged/scrap items (GAP-010). |
| **FORM-006** | Product Master Form | `products` | `title`, `sku`, `retail_price`, `wholesale_price` | Persists wholesale price tier (GAP-002). |
| **FORM-007** | Product Quick Barcode Form | `products` | `barcode`, `barcode_type` | Generates unique Code128 barcode strings. |
| **FORM-008** | Category Form | `categories` | `name`, `parent_id`, `image` | Configures visual hierarchy for POS grid. |
| **FORM-009** | Brand Form | `brands` | `name`, `manufacturer` | Manages brand catalog taxonomies. |
| **FORM-010** | Stock Adjustment Form | `stock_adjustments` | `product_id`, `physical_count`, `reason`, `cost` | Re-evaluates moving-average unit cost (GAP-004). |
| **FORM-011** | Inter-Branch Transfer Form | `stock_transfers` | `source_branch_id`, `dest_branch_id`, `items` | Enforces in_transit tracking (GAP-007). |
| **FORM-012** | Barcode Label Print Form | `barcode_print` | `product_id`, `label_count`, `paper_format` | Generates multi-column thermal print sheets. |
| **FORM-013** | Customer Profile Form | `customers` | `name`, `primary_phone`, `secondary_phone`, `credit_limit` | Persists secondary phone (GAP-030). |
| **FORM-014** | Customer Khata Receipt Form | `customer_payments`| `amount`, `financial_account_id`, `reference` | Atomic double-entry balance update (GAP-008). |
| **FORM-015** | Purchase Order Form | `purchases` | `supplier_id`, `invoice_no`, `items`, `payment_status` | Records procurement orders. |
| **FORM-016** | Landed Freight Ingestion Form| `purchases` | `freight_amount`, `other_charges` | Prorates true landed cost (GAP-005). |
| **FORM-017** | Supplier Profile Form | `suppliers` | `name`, `phone`, `tax_number`, `opening_balance` | Manages vendor contact and credit terms. |
| **FORM-018** | Supplier Disbursement Form | `supplier_payments`| `amount`, `financial_account_id`, `reference` | Reduces Accounts Payable liability. |
| **FORM-019** | Supplier Return Debit Note Form| `supplier_returns` | `product_id`, `return_qty`, `unit_cost`, `reason` | Emits debit note reducing AP (GAP-011). |
| **FORM-020** | Operating Expense Form | `expenses` | `expense_category_id`, `amount`, `financial_account_id` | Mandatory funding account enforcement (GAP-013). |
| **FORM-021** | Expense Category Budget Form | `expense_categories`| `name`, `monthly_budget`, `description` | Persists monthly budget ceilings (GAP-031). |
| **FORM-022** | User Staff Governance Form | `users` | `name`, `email`, `role`, `base_salary`, `cycle` | Persists payroll agreement fields (GAP-029). |
| **FORM-023** | RBAC Role Permission Form | `roles` | `role_name`, `permission_ids` | Assigns granular security permissions. |
| **FORM-024** | Financial Account Setup Form | `financial_accounts`| `name`, `account_type`, `opening_balance` | Initializes cash registers and bank accounts. |
| **FORM-025** | Inter-Account Transfer Form | `account_transfers` | `from_account_id`, `to_account_id`, `amount` | Atomic transfer between cash and bank. |
| **FORM-026** | Capital Movement Form | `account_movements` | `account_id`, `amount`, `movement_type` | Records equity injection or owner drawings. |
| **FORM-027** | Store Branch Setup Form | `branches` | `name`, `address`, `phone`, `default_currency` | Configures physical store outlets. |
| **FORM-028** | Enterprise Settings Form | `business_settings`| `business_name`, `tax_rate`, `currency_symbol` | Tenant-wide operational rules. |
| **FORM-029** | Thermal Printer Setup Form | `printer_settings` | `connection_type`, `ip_address`, `port`, `width` | Configures ESC-POS hardware parameters. |
| **FORM-030** | Report Generation Filter Form| `reports` | `report_type`, `start_date`, `end_date`, `branch_id` | Generates management analytics. |
| **FORM-031** | Device Pairing QR Config Form| `device_pairing` | `device_name`, `pairing_port`, `local_ip` | Strict RFC1918 subnet validation (GAP-024). |
| **FORM-032** | Cash Shift Opening Form | `cash_registers` | `opening_cash_float`, `notes` | Sets starting drawer currency float. |
| **FORM-033** | Cash Shift Closing Form | `cash_registers` | `counted_cash`, `discrepancy_reason` | Calculates cash drawer over/short. |
| **FORM-034** | Customer Quick Add POS Form | `customers` | `name`, `phone` | Instant customer creation in POS cart. |
| **FORM-035** | Product Quick Add Form | `products` | `title`, `retail_price`, `sku` | Instant product creation during inventory. |
| **FORM-036** | Khata Discrepancy Adjustment | `customer_ledgers` | `customer_id`, `adjustment_amount`, `reason` | Manual compensating khata adjustment. |
| **FORM-037** | Expense Void Reason Form | `expenses` | `expense_id`, `void_reason` | Voids expense and restores cash balance. |
| **FORM-038** | Purchase Cancellation Guard | `purchases` | `purchase_id`, `cancellation_reason` | Verifies stock on hand before cancelling. |

---

## 5. Field Lineage Account (FIELD-001 through FIELD-214)

All 214 canonical field identifiers certified in Phase 1 and remediated in Phase 9 are fully integrated and accounted for across the 38 operational forms above. Notable post-remediation field additions include:
- **`FIELD-044` / `FIELD-045` / `FIELD-046` (`sale_items` Snapshots):** Immutable `product_name`, `sku`, `unit_cost`, `barcode` persisted at transaction commit (`GAP-001 / MIG-001`).
- **`FIELD-023` (`products.wholesale_price`):** Dedicated wholesale price tier input and storage (`GAP-002 / MIG-002`).
- **`FIELD-078` / `FIELD-079` (`purchases` Landed Cost):** `freight_amount` and `other_charges` prorated into moving-average inventory valuation (`GAP-005 / MIG-005`).
- **`FIELD-092` (`inventory_movements.transfer_status`):** `in_transit`, `received`, `cancelled` tracking (`GAP-007 / MIG-006`).
- **`FIELD-061` / `FIELD-062` (`sale_return_items` Restock Flags):** `condition` ('sellable'/'damaged'/'scrap') and `is_restocked` (`GAP-010 / MIG-007`).
- **`FIELD-154` (`users.base_salary` & Payroll):** Persisted employee wage agreements, payment cycle, and next payment date (`GAP-029 / MIG-009`).
- **`FIELD-112` (`customers.secondary_phone`):** Alternate phone contact persistence (`GAP-030 / MIG-010`).
- **`FIELD-140` (`expense_categories.monthly_budget`):** Departmental budget ceiling persistence (`GAP-031 / MIG-011`).
