# SKYRASOFT HBOS (HYPERLOCAL BUSINESS OPERATING SYSTEM)
## DEFINITIVE SYSTEM ARCHITECTURE, OPERATIONAL HANDBOOK & DEVELOPER CONTINUATION SPECIFICATION

> **Document Reference:** `HBOS_DEFINITIVE_MASTER_SPECIFICATION_AND_OPERATIONAL_HANDBOOK.md`  
> **Repository:** `git@github.com:SkyraSoft/HBOS-Management-System.git` (Branch: `main`)  
> **Production URL:** `https://hbos.skyrasoft.com`  
> **Live Downloads:** `https://hbos.skyrasoft.com/download.html`  
> **Target Audience:** Any new developer, engineering rig, automated agent, or technical product owner continuing development, testing, building, or deploying HBOS from scratch.

---

## TABLE OF CONTENTS
1. [First-Time Environment Setup & Fetching Code from GitHub](#1-first-time-environment-setup--fetching-code-from-github)
2. [Codebase Architecture & Workspace Map](#2-codebase-architecture--workspace-map)
3. [Developer Operations: Git, Builds & Live Cloud Deployment](#3-developer-operations-git-builds--live-cloud-deployment)
4. [Single-Store vs. Multi-Store Architecture & Lifecycle](#4-single-store-vs-multi-store-architecture--lifecycle)
5. [Demo Sandbox Architecture & In-Memory Engine](#5-demo-sandbox-architecture--in-memory-engine)
6. [Complete Specification of All 18 System Modules](#6-complete-specification-of-all-18-system-modules)
7. [The 50 Essential Operational Points & UAT Checklist](#7-the-50-essential-operational-points--uat-checklist)
8. [Automated Verification & Integrity Gates](#8-automated-verification--integrity-gates)

---

## 1. FIRST-TIME ENVIRONMENT SETUP & FETCHING CODE FROM GITHUB

If you are setting up HBOS on a new device, developer laptop, or testing machine, follow this exact sequence to clone and prepare the environment.

### 1.1 Hardware & Software Prerequisites
Ensure the following tools are installed on the target machine (Windows 10/11 x64 recommended):
* **Git:** [https://git-scm.com/](https://git-scm.com/)
* **Node.js:** v20.x or v22.x LTS ([https://nodejs.org/](https://nodejs.org/))
* **PHP:** v8.2 or v8.3 with extensions: `pdo`, `pdo_mysql`, `pdo_sqlite`, `curl`, `mbstring`, `openssl`, `bcmath`, `fileinfo`
* **Composer:** v2.x ([https://getcomposer.org/](https://getcomposer.org/))
* **Rust & Cargo:** (Required for Tauri desktop build) via [https://rustup.rs/](https://rustup.rs/) (Target: `x86_64-pc-windows-msvc`)
* **Visual Studio C++ Build Tools:** Desktop development with C++ workload (for Rust MSVC linker).
* **WiX Toolset v3.14:** Needed for generating MSI installers. Tauri auto-downloads and caches WiX into `%LOCALAPPDATA%\tauri\WixTools314`.

### 1.2 GitHub Authentication & Repository Clone
The repository is private and hosted on GitHub under the `SkyraSoft` organization.

#### Option A: Clone via SSH (Recommended)
1. Verify SSH key authentication:
   ```powershell
   ssh -T git@github.com
   # Expected response: Hi SkyraSoft! You've successfully authenticated...
   ```
2. If using a new SSH key:
   ```powershell
   ssh-keygen -t ed25519 -C "developer@skyrasoft.com"
   Get-Content $HOME/.ssh/id_ed25519.pub
   ```
   Add the public key to **GitHub -> Settings -> SSH and GPG keys**.
3. Clone repository:
   ```powershell
   cd C:\xampp\htdocs   # (or your preferred workspace path)
   git clone git@github.com:SkyraSoft/HBOS-Management-System.git HBOS
   cd HBOS
   ```

#### Option B: Clone via HTTPS
```powershell
git clone https://github.com/SkyraSoft/HBOS-Management-System.git HBOS
cd HBOS
```

#### Synchronize and Confirm Clean Working Tree:
```powershell
git checkout main
git pull origin main
git status
```

### 1.3 Local Environment Installation Commands

#### Step 1: Backend Setup (`api/`)
```powershell
cd C:\xampp\htdocs\HBOS\api

# Copy environment file
if (-not (Test-Path .env)) { Copy-Item .env.example .env }

# Install Composer packages
composer install

# Generate application encryption key
php artisan key:generate

# Run database migrations (SQLite or MariaDB)
php artisan migrate

# Run baseline automated verification tests
php artisan test --filter=DemoSandboxIsolationAndReconciliationTest
```

#### Step 2: Frontend Setup (`Frontend/`)
```powershell
cd C:\xampp\htdocs\HBOS\Frontend

# Install NPM dependencies
npm install

# Start local development server
npm run dev
# Browser opens at http://localhost:5173
```

#### Step 3: Run Master Integrity Check
```powershell
cd C:\xampp\htdocs\HBOS
node scripts/verify_inventory_demo.js
```
*Expected Result:* **ALL 20 ARCHITECTURE VERIFICATION CHECKS PASSED PERFECTLY!**

---

## 2. CODEBASE ARCHITECTURE & WORKSPACE MAP

```
C:\xampp\htdocs\HBOS\
├── api/                             # Laravel 11 Headless REST API (PHP 8.3)
│   ├── app/
│   │   ├── Http/Controllers/        # REST Controllers (Product, Sale, Customer, etc.)
│   │   ├── Models/                  # Eloquent Models with multi-tenant business_id scoping
│   │   ├── Services/                # Business logic (DashboardService, StockIntelligenceService)
│   │   └── Middleware/              # TenantIsolation, RoleVerification
│   ├── config/                      # database.php, hbos_version.php, cors.php
│   ├── database/migrations/         # 54+ multi-tenant database migrations
│   ├── routes/                      # api.php (v1 REST endpoints), web.php (Download gateway)
│   └── tests/Feature/               # PHPUnit test suites
├── Frontend/                        # Vue 3 + Vite Production SPA
│   ├── public/                      # Static assets, download.html, demo visuals (/demo/)
│   │   └── demo/                    # 56 WebP visuals: brands/, categories/, products/
│   ├── src/
│   │   ├── api.js                   # Axios client & fail-closed Demo mutation barrier
│   │   ├── services/
│   │   │   ├── demoCatalogMaster.js # Canonical master catalog (4 brands, 8 cats, 44 SKUs)
│   │   │   ├── demoDataGenerator.js # 7-Month rolling Pakistani retail timeline generator
│   │   │   ├── demoSandboxEngine.js # Autonomous in-memory / IndexedDB simulation engine
│   │   │   ├── localDb.js           # Multi-tenant partitioned IndexedDB engine
│   │   │   └── offlinePosEngine.js  # Offline checkout & outbox transaction builder
│   │   ├── stores/                  # Pinia stores (auth, retail, customers, khata, sessionMode)
│   │   └── views/                   # PosSaleView, InventryView, CustomerView, DashboardView, etc.
│   └── package.json
├── desktop/                         # Tauri v1.6.3 Desktop Application Wrapper
│   ├── tauri.conf.json              # Window settings (1366x768 resizable, decorated), WiX bundle specs
│   ├── build.rs                     # Cargo cache watcher for Frontend/dist
│   └── src/main.rs                  # Rust entry point, Win32 window controls
├── scripts/                         # Verification, SVG-to-WebP conversion, UAT lifecycle scripts
├── docs/release/                    # Authoritative release manifests & architectural runbooks
└── HBOS_DEFINITIVE_MASTER_SPECIFICATION_AND_OPERATIONAL_HANDBOOK.md
```

---

## 3. DEVELOPER OPERATIONS: GIT, BUILDS & LIVE CLOUD DEPLOYMENT

### 3.1 Git Operations (Pushing Code to GitHub)
Always ensure your changes are cleanly committed without adding bulky build directories (`desktop/target/` and `*.msi` are strictly ignored by `.gitignore`).

```powershell
cd C:\xampp\htdocs\HBOS

# 1. Review status
git status

# 2. Stage changes
git add .

# 3. Commit with semantic message
git commit -m "feat(inventory): complete canonical inventory architecture and UAT documentation"

# 4. Push to remote main
git push origin main
```

### 3.2 Packaging the Windows Desktop Application (`.msi`)
To compile the production Windows 64-bit installer with native decorations and zero screen-trapping:

```powershell
# Step 1: Compile Frontend Production Bundle
cd C:\xampp\htdocs\HBOS\Frontend
npm run build

# Step 2: Invalidate Rust build cache (guarantees latest UI assets are packaged)
cd C:\xampp\htdocs\HBOS
Remove-Item -Recurse -Force "desktop\target\release\build\hbos-pos*" -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force "desktop\target\release\deps\hbos_pos*" -ErrorAction SilentlyContinue

# Step 3: Compile WiX MSI Installer
cd desktop
npx -y @tauri-apps/cli@1.6.3 build --bundles msi
```
* **Output MSI Path:** `desktop\target\release\bundle\msi\HBOS POS_1.0.0_x64_en-US.msi`
* **Standard Distribution File:** `HBOS_POS_1.0.0_STAGE3_UAT_x64_en-US.msi` (Approx. 22.4 MB, SHA-256: `CC033239C30F14A5B6B5DC54C517FCA38EF206048F229C2CD0329E4A04DE15E3`).

### 3.3 Deploying to Hostinger Live Server (`hbos.skyrasoft.com`)
Production runs on Hostinger Cloud with MariaDB 11.8.9 and PHP 8.3.

#### Server Connection Details:
* **Host:** `82.29.191.191`
* **SSH Port:** `65002`
* **SSH User:** `u856400307`
* **Authentication:** Private Key `~/.ssh/id_ed25519`
* **Remote Application Root:** `/home/u856400307/domains/skyrasoft.com/public_html/hbos`
* **Live Website URL:** `https://hbos.skyrasoft.com`
* **Public Downloads URL:** `https://hbos.skyrasoft.com/download.html`

#### Operational Deployment Commands:

1. **Upload Updated Windows MSI Installer:**
   ```powershell
   scp -i $HOME/.ssh/id_ed25519 -P 65002 "C:\xampp\htdocs\HBOS\desktop\target\release\bundle\msi\HBOS POS_1.0.0_x64_en-US.msi" u856400307@82.29.191.191:domains/skyrasoft.com/public_html/hbos/public/downloads/HBOS_POS_1.0.0_STAGE3_UAT_x64_en-US.msi
   ```

2. **Upload Download Portal Page:**
   ```powershell
   scp -i $HOME/.ssh/id_ed25519 -P 65002 "C:\xampp\htdocs\HBOS\Frontend\public\download.html" u856400307@82.29.191.191:domains/skyrasoft.com/public_html/hbos/public/download.html
   ```

3. **Upload Demo Visuals (All 56 WebP files):**
   ```powershell
   # Create a small tar archive locally
   cd C:\xampp\htdocs\HBOS\Frontend\public
   tar -czf demo_assets.tar.gz demo/

   # Upload archive via SCP
   scp -i $HOME/.ssh/id_ed25519 -P 65002 demo_assets.tar.gz u856400307@82.29.191.191:domains/skyrasoft.com/public_html/hbos/public/

   # Unpack remotely and clean archive
   ssh -n -i $HOME/.ssh/id_ed25519 -p 65002 u856400307@82.29.191.191 "cd domains/skyrasoft.com/public_html/hbos/public && tar -xzf demo_assets.tar.gz && rm demo_assets.tar.gz"

   # Remove local temporary archive
   Remove-Item demo_assets.tar.gz
   ```

4. **Verify Remote File Integrity via SSH:**
   ```powershell
   ssh -n -i $HOME/.ssh/id_ed25519 -p 65002 u856400307@82.29.191.191 "sha256sum domains/skyrasoft.com/public_html/hbos/public/downloads/HBOS_POS_1.0.0_STAGE3_UAT_x64_en-US.msi"
   ```

5. **Deploy Backend Laravel Code & Run Migrations Remotely:**
   ```powershell
   ssh -n -i $HOME/.ssh/id_ed25519 -p 65002 u856400307@82.29.191.191 "cd domains/skyrasoft.com/public_html/hbos && git pull origin main && php artisan migrate --force && php artisan config:cache && php artisan route:cache"
   ```

---

## 4. SINGLE-STORE VS. MULTI-STORE ARCHITECTURE & LIFECYCLE

HBOS is designed with an adaptive UI that intelligently adapts to whether a business operates a single counter or a multi-location supermarket chain.

### 4.1 Single-Store Architecture (Sole Proprietor Mode)
* **Target:** Single-store kiryana, mart, or pharmacy.
* **Profile:** *Al-Madina General Store*, Commercial Area, Bahadurabad, Karachi.
* **Cash Register Float:** PKR 8,500.00.
* **Total Inventory Value:** PKR 1,652,775.00 across all 44 stockable items.
* **Single-Store UI Shielding:**
  * When `business.branches.length === 1`, all multi-location elements are hidden.
  * The top branch switcher pill, branch transfer navigation items, cross-branch performance comparisons, and warehouse replenishment menus are completely shielded.
  * The retailer experiences a clean, simple single-store POS interface without unnecessary multi-branch complexity.

### 4.2 Multi-Store Architecture (Retail Chain Mode)
* **Target:** Multi-branch retail chains.
* **Profile:** *Al-Madina Supermarket*, Karachi.
* **3 Active Branches:**
  1. `Clifton Main Store` (`BR-01`, Flagship, Cash Float: PKR 15,000.00)
  2. `Saddar Express` (`BR-02`, High-Footfall, Cash Float: PKR 8,000.00)
  3. `Gulshan Outlet` (`BR-03`, Suburban Store, Cash Float: PKR 6,500.00)
* **Independent Stock Tracking:**
  * 132 `branch_inventories` records (44 SKUs $\times$ 3 branches).
  * Every branch has independent physical stock counts, reorder alert thresholds, and valuation.
* **Inter-Branch Stock Transfers:**
  * State lifecycle: `dispatched` $\rightarrow$ `in_transit` $\rightarrow$ `received`.
  * Dispatch immediately decrements stock from the source branch and marks units "In-Transit".
  * Receiving at the destination branch increments stock. Total company inventory is strictly conserved.

### 4.3 Converting Single-Store to Multi-Store (Branch Expansion)
* When the business owner opens a new outlet:
  1. Navigate to **Settings -> Branch Management**.
  2. Click **Add New Branch**, enter branch name, code, address, phone, and opening cash float.
  3. The system automatically initializes independent `branch_inventories` records for the new branch.
  4. Once a second branch is active, the system automatically unshields multi-branch controls:
     * The Branch Switcher pill appears in the top navigation header.
     * The **Stock Transfers** view appears in the sidebar.
     * Inventory tables display branch breakdown tabs.

### 4.4 Converting Multi-Store Back to Single-Store (1-Click Decommission & Consolidation)
* If an owner decides to close branch outlets and consolidate operations back into a single store:
* **The 1-Click "Decommission & Consolidate Branch" Engine (`BranchManagementView.vue`):**
  1. **Transfer Sweeper:** Checks for any pending `in_transit` transfers involving the decommissioned branch and safely cancels/reroutes them back to the primary store.
  2. **Inventory Sweep:** Consolidates all remaining stock balances from the closing branch into the primary store's `branch_inventories`.
  3. **Soft Decommission:** Sets branch status to `decommissioned`.
  4. **Data Integrity Guarantee:** Preserves all historical sales, invoices, customer credit records, and audit logs (never hard-deletes rows, maintaining strict foreign key constraints).
  5. **Auto-Shielding Reactivation:** Once only the primary branch remains active, the interface automatically re-engages Single-Store UI Shielding.

---

## 5. DEMO SANDBOX ARCHITECTURE & IN-MEMORY ENGINE

The demo sandbox allows anyone to test HBOS immediately without creating an account or affecting live databases.

### 5.1 Welcome Screen Journey (`WelcomeView.vue`)
Cold launching the desktop application or opening the web client displays the unauthenticated Welcome Screen with 3 cards:
1. **Log In (Teal):** For merchants with existing cloud credentials.
2. **Create My Business (Blue):** Clean onboarding wizard with a **Zero-Data Guarantee** (creates zero dummy records).
3. **Try Demo (Purple):** Immediate sandbox access with zero setup.
   * Clicking "Try Demo" opens a modal to select either:
     * **Single-Store Demo** (*Al-Madina General Store*)
     * **Multi-Store Demo** (*Al-Madina Supermarket — 3 Branches*)

### 5.2 Top Demo Banner Controls (`DemoModeBanner.vue`)
When active, a sticky amber banner at the top of the window provides quick controls:
* **Switch Demo Mode:** Toggle between Single-Store and Multi-Store instantly.
* **Reset Demo Data:** Wipes the local sandbox and re-seeds deterministic initial data.
* **Exit Demo:** Clears session state and returns to the Welcome Screen.
* **Create My Business:** Shortcut to start a real, clean merchant account.

### 5.3 Simulated Business Operations
* **Dynamic 7-Month Rolling Timeline (`demoDataGenerator.js`):** Generates realistic historical sales, purchases, customer payments, and expenses ending on the current calendar day.
* **POS Counter Operations:** Real-time barcode scanning, search, cart operations, cash tender, change calculation, and receipt generation.
* **Master Carton Multipliers:** Scanning carton barcode `8964000400112` adds 12 pouches of Dalda Oil to the cart.
* **Customer Khata (Credit):** Selling on "Khata" increases customer debt; recording a payment in Customer Profile decreases debt and adds cash to the drawer.
* **Cash Register Balancing:** Tracks opening float, sales cash, expenses, payouts, and day-end close with discrepancy calculations.
* **Fail-Closed Security Barrier (`api.js`):** Any API mutation not handled by the sandbox returns HTTP 400 with `"This action is not available in Demo mode."` Zero fake toasts or phantom data mutations.

---

## 6. COMPLETE SPECIFICATION OF ALL 18 SYSTEM MODULES

### Module 1: POS Checkout Counter & Fast Retail Cashier
* **Live Component:** `Frontend/src/views/PosSaleView.vue`
* **Active Routes:** `/pos`, `/POS`
* **Features:**
  * Keyboard-wedge USB barcode scanner listener (`keydown` handler).
  * Fast product search by name, SKU, or category tabs.
  * Multi-item cart with quantity adjustments (+ / -) and item removal.
  * Split multi-tender payment processing (Cash, Card, JazzCash, Easypaisa, Khata).
  * Exact cash change return calculator.
  * Cart Hold & Resume: Hold up to 5 concurrent shopping carts for waiting customers.
  * Price override & line-item discount authorization with audit logging.
  * ESC/POS thermal receipt generation (58mm & 80mm).

### Module 2: Products, Catalog & Inventory Master
* **Live Component:** `Frontend/src/views/InventryView.vue`
* **Active Routes:** `/inventory/products`, `/products`, `/inventry`
* **Features:**
  * Independent stockable SKU architecture (44 distinct sellable records).
  * Distinct pack sizes (e.g. 1 kg, 5 kg, 10 kg) stored as independent product rows.
  * Master carton barcode aliases with quantity multipliers (e.g. 12x, 24x).
  * Fractional loose units (scale 3, e.g. 10.500 kg) for commodities like sugar and rice.
  * Tiered pricing (Retail Price vs. Wholesale Price).
  * Below-cost selling warnings (`selling_price < cost_price`).
  * 4 Brands, 8 Categories, 16 Subcategories.
  * Local high-DPI WebP visual assets (< 10 KB each).

### Module 3: Inventory Ledger & Stock Intelligence
* **Live Components:** `InventryView.vue`, `ReorderView.vue`, `ReorderDetailView.vue`
* **Active Routes:** `/inventory`, `/reorder`, `/reorderdetail`
* **Features:**
  * Immutable `inventory_movements` ledger: every sale, return, adjustment, and transfer creates an audit movement.
  * Negative stock prevention using database transactions and row-level locks.
  * Physical stock count adjustments with mandatory reason codes (damage, theft, expiry, audit count).
  * Stock Intelligence: Automated reorder suggestions based on sales velocity and safety thresholds.
  * Slow-moving and dead stock identification.

### Module 4: Suppliers, Purchasing & Landed Cost
* **Live Component:** `Frontend/src/views/PurchaseView.vue`
* **Active Routes:** `/purchases`, `/purchase`
* **Features:**
  * Supplier purchase orders supporting Paid, Unpaid, and Partial payment terms.
  * Proportional landed cost freight allocation across line items to establish true unit cost.
  * Automatic stock increments upon purchase order receipt.
  * Historical purchase price variance tracking and supplier price comparison tooltips.

### Module 5: Supplier Payables & Debt Settlement
* **Live Components:** `SupplierView.vue`, `SupplierpayableView.vue`, `SupplierPaymentView.vue`
* **Active Routes:** `/suppliers`, `/supplierpayable`, `/supplierpayment`
* **Features:**
  * Outstanding supplier payables ledger (`due_amount` tracking).
  * Supplier debt payout modal: settlements from Cash Drawer or Bank Account.
  * Auditable payment reversals with compensating journal entries.
  * Supplier return debit notes with corresponding inventory cost adjustments.

### Module 6: Customers & Pakistani Khata (Udhaar) Ledger
* **Live Components:** `CustomerView.vue`, `KhataView.vue`, `ProfilecustomerView.vue`
* **Active Routes:** `/customers`, `/khata`, `/profilecustomer`
* **Features:**
  * Customer directory with Pakistani phone validation (`03XXXXXXXXX`).
  * Customer credit limit enforcement: blocks sales if credit limit is exceeded.
  * Khata repayment modal: Cash, Bank Transfer, or Cheque settlements.
  * Overpayment prevention: Rejects repayment amounts greater than outstanding debt (HTTP 422).
  * Payment reversal with mandatory audit justification.
  * Shareable Khata debt statements (WhatsApp & printable PDF).

### Module 7: Returns, Exchanges & Restock Grading
* **Live Component:** `Frontend/src/views/SalesView.vue`
* **Active Routes:** `/sales`
* **Features:**
  * Original invoice lookup by barcode or invoice number.
  * Restock condition grading:
    * **Restockable:** Restores sellable inventory and reverses Cost of Goods Sold (COGS).
    * **Damaged:** Routes item to damage quarantine without adding to sellable stock.
  * Cash refund settlement with cash drawer payout.
  * Prevention of duplicate or replayed return transactions.

### Module 8: Operating Expenses & Petty Cash
* **Live Component:** `Frontend/src/views/ExpensesView.vue`
* **Active Routes:** `/expenses`
* **Features:**
  * Daily store expense recording (Utilities, Rent, Salaries, Refreshments, Maintenance).
  * Petty cash drawer payouts with corresponding drawer cash decrements.
  * Monthly expense budget limits with threshold alert indicators.
  * Real-time expense deduction in net profit calculations.

### Module 9: Multi-Account Treasury & Financial Accounts
* **Live Component:** `Frontend/src/views/SettingsView.vue`
* **Active Routes:** `/settings` (Treasury tab)
* **Features:**
  * Multi-account management: Cash Drawer, Bank Accounts (Meezan, HBL), Mobile Wallets (JazzCash, Easypaisa).
  * Inter-account fund transfers with double-entry ledger balance conservation.
  * Capital injections (Owner Equity) and drawings recording.

### Module 10: Cash Register & Shift Sessions
* **Live Components:** `PosSaleView.vue`, `ExpensesView.vue`
* **Active Routes:** `/pos` (Register Session Modal)
* **Features:**
  * Morning shift opening with opening float initialization.
  * Real-time tracking: `Expected Cash = Opening Float + Cash Sales + Debt Collections - Supplier Payouts - Petty Expenses - Customer Refunds`.
  * Day-end cash counting modal with discrepancy calculation.
  * Mandatory variance explanation if counted cash does not match expected cash.
  * Shift locking: closed sessions are immutable and cannot be reopened.

### Module 11: Financial Reports & Small Retail Profitability
* **Live Components:** `DashboardView.vue`, `ReportsView.vue`
* **Active Routes:** `/dashboard`, `/reports`, `/analytics`
* **Features:**
  * Small Retail Net Profitability Statement:
    $$\text{Net Sales} - \text{COGS (Snapshot Cost)} = \text{Gross Profit}$$
    $$\text{Gross Profit} - \text{Operating Expenses} = \text{Net Profit}$$
  * Sales breakdown by category, brand, and cashier.
  * Cashier privacy: Salespersons are denied access to executive reports (HTTP 403 Forbidden).
  * Export options: Excel, CSV, and printable PDF.

### Module 12: Multi-Branch Architecture & Expansion
* **Live Component:** `Frontend/src/views/BranchManagementView.vue`
* **Active Routes:** `/branches`, `/settings/branches`
* **Features:**
  * Primary branch immutability.
  * Branch expansion wizard: adds new store locations and provisions independent branch inventory.
  * Adaptive UI: dynamically toggles between Single-Store shielding and Multi-Store controls.
  * 1-Click "Decommission & Consolidate Branch" engine.

### Module 13: Inter-Branch Stock Transfers
* **Live Component:** `Frontend/src/views/StockTransferView.vue`
* **Active Routes:** `/stock-transfers`, `/transfers`
* **Features:**
  * Transfer creation: Source Branch $\rightarrow$ Destination Branch.
  * Dispatch step: marks items "In-Transit" and decrements source inventory.
  * Receiving step: verifies quantities and increments destination inventory.
  * Total company inventory is strictly conserved throughout the transfer lifecycle.

### Module 14: Role Governance & Access Control (RBAC)
* **Live Component:** `Frontend/src/views/UsersView.vue`
* **Active Routes:** `/users`, `/settings/users`
* **Roles:**
  1. **Business Owner:** Full access to all stores, financial reports, settings, and cashier counters.
  2. **Branch Manager:** Scoped to assigned branch; accesses branch inventory, transfers, and expenses.
  3. **Salesperson / Cashier:** Restricted exclusively to the POS checkout counter.
* **Enforcement:** Enforced at both frontend route guards and backend API middleware (HTTP 403 Forbidden).

### Module 15: Offline Counter Resilience & Sync Coordinator
* **Live Services:** `offlinePosEngine.js`, `localDb.js`
* **Features:**
  * IndexedDB outbox storage for sales made while offline.
  * Client-side UUIDs for idempotent backend replay.
  * Network status detector: automatically resumes synchronization upon reconnection.
  * Outbox exit guard: prompts a warning if the user attempts to exit the desktop app with unsynced sales.

### Module 16: Hardware Interfaces & Printing
* **Subsystems:**
  * **Thermal Receipt Printers:** 58mm and 80mm ESC/POS layout formatting with Urdu/English headers and return policy footers.
  * **Barcode Scanners:** USB keyboard-wedge compatibility with auto-focus inputs and fast latency (< 3s).
  * **Cash Drawers:** RJ11/RJ12 drawer kick command triggers via printer driver.

### Module 17: Multi-Tenant Database Isolation & Data Protection
* **Features:**
  * All database tables strictly scoped by `business_id`.
  * Multi-tenant global query scopes on all Eloquent models.
  * Zero-Data Guarantee: new registrations create zero sample or demo records.
  * Client-side session partitioning: local storage and IndexedDB keys are scoped by tenant ID.

### Module 18: Desktop Shell Controls & Lifecycle Management
* **Subsystems:** Tauri v1.6.3 + Win32 Native Chrome.
* **Features:**
  * Standard 1366x768 initial window with resize, minimize, maximize, and close controls.
  * Zero kiosk trapping: users can move, resize, minimize, or close the window at any time.
  * Clean process termination on close without orphaned WebView2 processes.

---

## 7. THE 50 ESSENTIAL OPERATIONAL POINTS & UAT CHECKLIST

Below is the definitive catalog of all 50 operational points, edge cases, and verification criteria across the 19 operational test areas of the system:

| # | Operational Test Area / Requirement | Feature / Edge Case Description | Expected Result & Integrity Rule |
|:---|:---|:---|:---|
| **1** | Native Window Launch | App startup in desktop wrapper | Standard 1366x768 window with native title bar and min/max/close controls. No fullscreen lock. |
| **2** | Window Manipulation | Minimizing, maximizing, Alt+Tab switching | Window minimizes cleanly; Alt+Tab cycles between windows without HBOS seizing focus. |
| **3** | Safe Native Exit | Closing app via top-right (X) or Alt+F4 | Process terminates cleanly in Task Manager with no orphaned WebView2 background processes. |
| **4** | Three Welcome Choices | Layout of `/welcome` screen | Three prominent cards: Log In (teal), Create Business (blue), Try Demo (purple). |
| **5** | Welcome to Login Navigation | Navigation from `/welcome` to `/login` | Form renders clean inputs; clicking "Back to Welcome" immediately returns to `/welcome`. |
| **6** | Welcome to Register Navigation | Navigation from `/welcome` to `/register` | Registration form renders; clicking "Back to Welcome" immediately returns to `/welcome`. |
| **7** | Single-Store Demo Initialization | Launching Single-Store demo | Lands on POS; sticky amber banner displays "Single Store Demo"; cash float initialized to PKR 8,500.00. |
| **8** | Single-Store Demo Cash Sale | Ringing up sale in demo mode | Invoice generated; stock decrements instantly; drawer cash increments; zero server requests sent. |
| **9** | Single-Store Demo Khata Credit Sale | Ringing up credit sale in demo mode | Customer debt increases; drawer cash unchanged; total accounts receivable increase. |
| **10** | Single-Store Demo Idempotent Reset | Clicking "Reset Demo Data" | Sandbox resets to deterministic factory template; stock, customer balances, and drawer restored. |
| **11** | Multi-Store Demo Initialization | Launching Multi-Store demo | Sticky banner shows "Multi-Store Demo"; branch switcher visible; 3 branches provisioned. |
| **12** | Multi-Branch In-Transit Transfer | Viewing `/stock-transfers` in demo | Transfer #TRF-2026-001 visible with status "In-Transit"; 5 units Sufi Oil deducted from Clifton, not yet in Saddar. |
| **13** | Fresh Business Registration | Creating account on `/register` | Account created (201 Created); lands on store setup; exactly 0 products, 0 sales, 0 customers created. |
| **14** | Zero-State Catalog & Cash | Inspecting fresh account dashboard | Products table shows "No products found"; dashboard displays PKR 0 sales, PKR 0 expenses, PKR 0 cash. |
| **15** | Existing Merchant Sign-In | Logging in on `/login` | Authenticates; stores Bearer token in `hbos_token`; redirects to dashboard with genuine tenant data. |
| **16** | Remembered Session Banner | Returning to `/welcome` with token | Banner displays: "Continue as [Owner Name] ([Store Name])" with "Resume Session ->" button. |
| **17** | POS Cash Sale & Exact Change | Tender cash > bill amount in POS | Prominently displays exact change due; completes sale; updates cash drawer and decrements stock. |
| **18** | Fast Barcode Scanning | USB scanner input on POS counter | Detects barcode scan in < 3 seconds; matches product; adds item to cart without manual clicking. |
| **19** | Master Carton Multipliers | Scanning carton barcode alias | Scanning carton barcode automatically increments base product by quantity multiplier (e.g. 12x or 24x). |
| **20** | Cart Hold & Resume | Holding cart for waiting customer | Holds up to 5 concurrent shopping carts; switches between carts seamlessly without losing items. |
| **21** | Fractional Loose Products | Creating & selling loose goods (kg) | Supports 3-decimal precision (e.g. 1.250 kg); calculates correct line item price and stock balance. |
| **22** | Tiered Pricing (Retail/Wholesale) | Toggling wholesale pricing | POS allows selecting wholesale tier when enabled; applies wholesale price and checks margin. |
| **23** | Below-Cost Sale Warning | Selling price set below cost | Visual warning displays if selling price is lower than unit cost; prevents accidental loss. |
| **24** | Authorized Price Override | Cashier overriding item price | Requires supervisor authorization if configured; logs override and user attribution in audit trail. |
| **25** | POS Quick-Add Customer | Adding customer from POS counter | Modal allows adding customer name and phone without leaving the checkout screen. |
| **26** | Credit Limit Enforcement | Credit sale exceeding customer limit | System warns or blocks sale if outstanding debt + sale total exceeds customer's credit limit. |
| **27** | Customer Debt Repayment | Recording payment in Customer Profile | Decreases customer outstanding debt; increases cash drawer balance; issues payment receipt. |
| **28** | Overpayment Rejection Guard | Repaying more than outstanding debt | System rejects payment amount greater than current balance with HTTP 422 Unprocessable Entity. |
| **29** | Customer Payment Reversal | Reversing an accidental payment | Reversal restores customer debt; decrements drawer cash; creates compensating audit record. |
| **30** | Shareable Khata Statement | Exporting customer ledger | Generates shareable WhatsApp text summary and printable PDF statement of account. |
| **31** | Purchase Bill with Partial Cash | Recording purchase on `/purchases` | Stock increments; partial cash payment decrements drawer; remaining balance added to supplier payables. |
| **32** | Proportional Landed Cost | Adding freight/shipping to purchase | Freight cost is proportionally allocated across line items based on value/weight, updating unit cost. |
| **33** | Supplier Price Trend Tooltip | Viewing item purchase history | Tooltip shows previous purchase price and flags whether the supplier increased their rate. |
| **34** | Supplier Debt Settlement | Paying supplier on `/suppliers` | Decrements cash drawer or bank balance; reduces outstanding supplier payable to PKR 0.00. |
| **35** | Supplier Return Debit Note | Returning goods to supplier | Decrements inventory; creates debit note; reduces supplier payable or issues refund credit. |
| **36** | Restock Condition Grading | Returning items on `/sales` | **Restockable:** increments inventory & reverses COGS. **Damaged:** routes to damage quarantine. |
| **37** | Cash Refund Drawer Payout | Settling cash refund on return | Cash drawer cash decrements by refund amount; refund invoice issued. |
| **38** | Petty Cash Store Expenses | Recording expense from cash drawer | Expense recorded under category (e.g. Refreshments); decrements cash drawer; updates daily P&L. |
| **39** | Expense Budget Alerts | Monthly expense category limits | Displays progress bar against monthly budget; alerts owner if expenses exceed budget. |
| **40** | Multi-Account Treasury | Managing multiple payment accounts | Tracks balances across Cash Drawer, Bank Accounts, and Mobile Wallets; supports inter-account transfers. |
| **41** | Shift Day-Open Float Tracking | Opening shift on POS counter | Prompts for opening float amount; initializes register session; drawer baseline established. |
| **42** | Day-End Cash Balancing & Count | Closing shift on POS counter | Prompts for counted cash; compares against expected cash; requires explanation if variance exists. |
| **43** | Closed Session Immutability | Viewing closed register shifts | Closed register sessions cannot be reopened, edited, or deleted; preserves audit trail. |
| **44** | Realized Profit & Loss (P&L) | Viewing `/reports/profitability` | Net Sales - Snapshot COGS = Gross Profit. Gross Profit - Operating Expenses = True Net Profit. |
| **45** | Cashier Role Access Gate | Cashier accessing `/reports` | Cashier role is denied access (HTTP 403 Forbidden); financial metrics and settings remain private. |
| **46** | Single-Store to Multi-Branch | Adding a 2nd branch in Settings | Multi-branch controls automatically unshield: branch switcher, transfer menu, and location tabs appear. |
| **47** | 1-Click Branch Decommission | Decommissioning branch in Settings | Sweeps in-transit transfers; merges inventory into primary branch; soft-decommissions; re-shields UI. |
| **48** | Stock Transfer State Machine | Dispatching and receiving transfers | Dispatch marks items "In-Transit" (decrements source); receiving increments destination; stock conserved. |
| **49** | Offline POS Counter Operation | Ringing up sales with no internet | Completes sale locally; stores transaction in IndexedDB outbox; sync badge displays pending count. |
| **50** | Idempotent Background Sync | Restoring internet connection | Sync coordinator uploads pending outbox transactions using client UUIDs; zero duplicate sales created. |

---

## 8. AUTOMATED VERIFICATION & INTEGRITY GATES

Run the automated verification suite from the terminal to verify that the entire codebase adheres to all architectural constraints:

```powershell
cd C:\xampp\htdocs\HBOS
node scripts/verify_inventory_demo.js
```

### The 20 Architecture Verification Gates:
1. **Exactly 4 brands:** `DailyHarvest Foods`, `Khyber Tea & Spices`, `Indus Dairy & Oils`, `PakClean & Care`.
2. **Exactly 8 categories:** Grains, Flours, Beverages, Dairy, Oils, Spices, Snacks, Hygiene.
3. **Exactly 16 subcategories:** Exactly 2 subcategories per category.
4. **Exactly 32 product families:** Exactly 2 product families per subcategory.
5. **At least 32 independent SKU records:** 44 distinct sellable SKU records implemented.
6. **Physical pack-size distinctions:** 11 families have multiple physical pack sizes/weights.
7. **Unique SKUs:** 44 unique alphanumeric product codes.
8. **Unique primary barcodes:** 44 unique EAN-13 barcodes.
9. **Barcode alias quantity multipliers:** 8 master carton multipliers defined.
10. **Cost and selling prices:** Valid margins (`cost_price < selling_price`) across all items.
11. **Unit handling:** Standard units (`kg`, `litre`, `pack`, `bottle`, `can`) configured.
12. **Minimum-stock alerts:** Individual threshold levels set per SKU.
13. **Inventory valuation:** Accurate valuation calculation matching PKR 1,652,775.00.
14. **Stock deductions after POS sales:** Real-time decrement verified.
15. **Stock updates after purchases:** Restock receipt increments verified.
16. **Stock adjustments and returns:** Manual adjustment reason auditing verified.
17. **Branch-specific stock separation:** Clifton (44), Saddar (44), Gulshan (44) isolated.
18. **Inter-branch stock transfers:** Dispatch (in-transit) and receive lifecycle verified.
19. **Product image integration:** All 44 product images, 4 brand logos, and 8 category icons exist locally.
20. **100% Offline operation:** Zero external runtime image URLs; all assets load from local directories.
