# SKYRASOFT HBOS (HYPERLOCAL BUSINESS OPERATING SYSTEM)
## COMPLETE SYSTEM MASTER MANUAL, DEVELOPER ONBOARDING & CONTINUATION SPECIFICATION

> **Author:** SkyraSoft Engineering  
> **Repository:** `git@github.com:SkyraSoft/HBOS-Management-System.git` (Branch: `main`)  
> **Production Live URL:** `https://hbos.skyrasoft.com`  
> **Target Audience:** Any new developer, engineering rig, automated agent, or technical product owner taking over development, testing, deployment, or hardware qualification of HBOS.

---

## 1. FIRST-TIME MACHINE SETUP & FETCHING CODE FROM GITHUB

If you are setting up HBOS on a new device, developer laptop, or testing machine, follow this exact sequence to clone and prepare the environment.

### 1.1 Prerequisites Installation
Make sure the following runtimes are installed on the target Windows PC (or Linux/macOS development box):
1. **Git:** [https://git-scm.com/](https://git-scm.com/)
2. **Node.js:** v20.x or v22.x LTS ([https://nodejs.org/](https://nodejs.org/))
3. **PHP:** v8.2 or v8.3 with standard extensions (`pdo`, `pdo_mysql`, `pdo_sqlite`, `curl`, `mbstring`, `openssl`, `bcmath`, `fileinfo`)
4. **Composer:** v2.x ([https://getcomposer.org/](https://getcomposer.org/))
5. **Rust & Cargo:** (Only needed if building the Windows desktop app) via [https://rustup.rs/](https://rustup.rs/) (Target: `x86_64-pc-windows-msvc`)
6. **WiX Toolset v3.14:** (Only needed if compiling the `.msi` Windows installer; Tauri auto-caches WiX to `%LOCALAPPDATA%\tauri\WixTools314`)

---

### 1.2 GitHub SSH Authentication & Cloning
The repository is private and hosted at:
`git@github.com:SkyraSoft/HBOS-Management-System.git` (or `https://github.com/SkyraSoft/HBOS-Management-System.git`)

#### Option A: Using SSH (Recommended)
1. Verify if your SSH key is authorized on GitHub:
   ```powershell
   ssh -T git@github.com
   # Expected output: Hi SkyraSoft! You've successfully authenticated, but GitHub does not provide shell access.
   ```
2. If setting up on a new device with a new SSH key:
   ```powershell
   ssh-keygen -t ed25519 -C "developer@skyrasoft.com"
   # Display and copy the public key:
   Get-Content $HOME/.ssh/id_ed25519.pub
   ```
   Add the copied public key to **GitHub -> Settings -> SSH and GPG keys**.
3. Clone the repository:
   ```powershell
   cd C:\xampp\htdocs   # (or your preferred workspace directory)
   git clone git@github.com:SkyraSoft/HBOS-Management-System.git HBOS
   cd HBOS
   ```

#### Option B: Using HTTPS / Personal Access Token
```powershell
git clone https://github.com/SkyraSoft/HBOS-Management-System.git HBOS
cd HBOS
```

#### Confirming Branch and Clean Synchronization:
```powershell
git checkout main
git pull origin main
git status
```

---

### 1.3 Project Workspace Structure
The HBOS workspace is structured into clean, decoupled layers:

```
c:\xampp\htdocs\HBOS\
├── api/                             # Laravel 11 Headless REST API (PHP 8.3)
│   ├── app/                         # Controllers, Models, Services, Middlewares
│   ├── config/                      # database.php, hbos_version.php, etc.
│   ├── database/migrations/         # 54+ multi-tenant database migrations
│   ├── routes/                      # api.php (REST v1), web.php (Download router)
│   └── tests/Feature/               # PHPUnit test suites (isolation, reconciliation, security)
├── Frontend/                        # Vue 3 + Vite Production SPA
│   ├── public/                      # Static assets, download.html, demo visuals (/demo/)
│   ├── src/
│   │   ├── api.js                   # Axios client & fail-closed Demo mutation adapter
│   │   ├── services/
│   │   │   ├── demoCatalogMaster.js # Canonical master catalog (4 brands, 8 cats, 44 SKUs)
│   │   │   ├── demoDataGenerator.js # 7-Month rolling Pakistani retail timeline generator
│   │   │   ├── demoSandboxEngine.js # Autonomous in-memory / IndexedDB simulation engine
│   │   │   ├── localDb.js           # Multi-tenant partitioned IndexedDB engine
│   │   │   └── offlinePosEngine.js  # Offline checkout & outbox transaction builder
│   │   ├── stores/                  # Pinia stores (auth, retail, customers, khata, sessionMode)
│   │   └── views/                   # PosSaleView, InventryView, CustomerView, DashboardView, etc.
├── desktop/                         # Tauri v1.6.3 Desktop Application Wrapper
│   ├── tauri.conf.json              # Window settings (1366x768 resizable, decorated), WiX bundle specs
│   ├── build.rs                     # Cargo cache invalidation watcher for Frontend/dist
│   └── src/main.rs                  # Rust entry point, Win32 window controls
├── scripts/                         # Verification, SVG-to-WebP conversion, UAT lifecycle scripts
├── docs/release/                    # Authoritative release manifests & architectural runbooks
└── HBOS_MASTER_DEVELOPER_ONBOARDING_AND_SYSTEM_MANUAL.md
```

---

### 1.4 Local Dependency Installation & Environment Setup

#### Step 1: Set Up Backend (`api/`)
```powershell
cd c:\xampp\htdocs\HBOS\api

# Copy default environment file if missing
if (-not (Test-Path .env)) { Copy-Item .env.example .env }

# Install PHP dependencies
composer install

# Generate application encryption key
php artisan key:generate

# Run migrations
php artisan migrate

# Verify test suites
php artisan test --filter=DemoSandboxIsolationAndReconciliationTest
```

#### Step 2: Set Up Frontend (`Frontend/`)
```powershell
cd c:\xampp\htdocs\HBOS\Frontend

# Install Node dependencies
npm install

# Run local development server
npm run dev
# Browser opens at: http://localhost:5173
```

#### Step 3: Run Full System Verification
From the root project folder:
```powershell
cd c:\xampp\htdocs\HBOS
node scripts/verify_inventory_demo.js
```
*Expected Result:* **ALL 20 ARCHITECTURE VERIFICATION CHECKS PASSED PERFECTLY!**

---

## 2. PRODUCTION CLOUD INFRASTRUCTURE & CREDENTIALS

HBOS operates a headless production API and public download distribution hub on Hostinger Cloud Infrastructure.

* **Production URL:** `https://hbos.skyrasoft.com`
* **Health Check:** `https://hbos.skyrasoft.com/api/v1/health`
* **Distribution Portal:** `https://hbos.skyrasoft.com/download.html`
* **Direct MSI Download:** `https://hbos.skyrasoft.com/downloads/HBOS_POS_1.0.0_STAGE3_UAT_x64_en-US.msi`
* **Demo Assets URL:** `https://hbos.skyrasoft.com/demo/products/prod_01.webp`

### SSH & Remote Access Details:
* **Server IP:** `82.29.191.191`
* **SSH Port:** `65002`
* **SSH User:** `u856400307`
* **Authentication Method:** Ed25519 Private Key (`~/.ssh/id_ed25519`)
* **Remote Path:** `/home/u856400307/domains/skyrasoft.com/public_html/hbos`
* **Database Engine:** MariaDB 11.8.9 (`u856400307_hbos_db`), User: `u856400307_hbos_user`

### Standard Remote Administration Commands:
```powershell
# 1. Check live backend files
ssh -n -i $HOME/.ssh/id_ed25519 -p 65002 u856400307@82.29.191.191 "ls -lh domains/skyrasoft.com/public_html/hbos/public/downloads/"

# 2. Check remote SHA256 of published installer
ssh -n -i $HOME/.ssh/id_ed25519 -p 65002 u856400307@82.29.191.191 "sha256sum domains/skyrasoft.com/public_html/hbos/public/downloads/HBOS_POS_1.0.0_STAGE3_UAT_x64_en-US.msi"

# 3. Upload new Windows MSI installer to live downloads portal
scp -i $HOME/.ssh/id_ed25519 -P 65002 "desktop\target\release\bundle\msi\HBOS POS_1.0.0_x64_en-US.msi" u856400307@82.29.191.191:domains/skyrasoft.com/public_html/hbos/public/downloads/HBOS_POS_1.0.0_STAGE3_UAT_x64_en-US.msi

# 4. Upload updated download portal page
scp -i $HOME/.ssh/id_ed25519 -P 65002 "Frontend\public\download.html" u856400307@82.29.191.191:domains/skyrasoft.com/public_html/hbos/public/download.html

# 5. Remote git pull and database migration on live server
ssh -n -i $HOME/.ssh/id_ed25519 -p 65002 u856400307@82.29.191.191 "cd domains/skyrasoft.com/public_html/hbos && git pull origin main && php artisan migrate --force && php artisan config:cache && php artisan route:cache"
```

---

## 3. CORE BUSINESS DOMAIN & INVENTORY ARCHITECTURE

HBOS is purpose-built for the Pakistani retail trade (kiryana stores, superstores, marts, FMCG distributors, pharmacies, and wholesale cash-and-carries). It implements an **independent stockable SKU architecture**.

### 3.1 Distinct Physical Pack Sizes vs. Barcode Aliases

```
+--------------------------------------------------------------------------------------------------+
|                              HBOS PRODUCT DATA ARCHITECTURE                                      |
+--------------------------------------------------------------------------------------------------+
|  CASE 1: SEPARATE PHYSICAL PACK SIZES (e.g., Basmati Rice 1kg vs 5kg vs 10kg)                    |
|  --> Stored as INDEPENDENT product records in `products` table.                                  |
|  --> Each record has its own: ID, unique SKU, unique primary barcode, unit, cost, price, stock.   |
|  --> No virtual parent matrix table is used; every physical pack is independently stockable.     |
+--------------------------------------------------------------------------------------------------+
|  CASE 2: WHOLESALE MASTER CARTONS OF THE SAME SKU (e.g., Master Carton of 12x 1L Canola Oil)     |
|  --> Stored in `product_barcodes` linked to the base 1L Canola Oil product.                     |
|  --> Contains: alternate barcode string, label ("Master Carton 12x"), quantity_multiplier: 12.   |
|  --> When scanned in POS, it automatically increments 12 units of the base product.             |
+--------------------------------------------------------------------------------------------------+
```

### 3.2 Master Catalog Counts
The verified master catalog (`Frontend/src/services/demoCatalogMaster.js` & `docs/release/hbos_demo_fixture_manifest.json`) enforces:
* **4 Brands:**
  1. *DailyHarvest Foods* (`DH`): Grains, pulses, flours, sugars.
  2. *Khyber Tea & Spices* (`KTS`): Teas, recipe mixes, ground spices, whole spices.
  3. *Indus Dairy & Oils* (`IDO`): UHT milk, butter, banaspati ghee, cooking oils.
  4. *PakClean & Care* (`PCC`): Dishwashing, surface cleaners, laundry, personal hygiene.
* **8 Categories & 16 Subcategories (Exactly 2 per category):**
  1. Grains & Staples $\rightarrow$ Rice, Pulses & Lentils
  2. Flours & Baking $\rightarrow$ Atta & Whole Wheat, Sugars & Baking Essentials
  3. Beverages & Refreshments $\rightarrow$ Black Tea & Kahwa, Juices & Syrups
  4. Dairy & Breakfast $\rightarrow$ Milk & Cream, Butter & Spreads
  5. Cooking Oils & Ghee $\rightarrow$ Banaspati Ghee, Edible Cooking Oil
  6. Spices & Seasonings $\rightarrow$ Recipe Spice Mixes, Plain Ground Spices
  7. Snacks & Confectionery $\rightarrow$ Biscuits & Rusks, Savory Nimko & Crisps
  8. Hygiene & Cleaning $\rightarrow$ Surface & Dish Care, Laundry & Detergents
* **32 Named Product Families:** Exactly 2 distinct families per subcategory across all 16 subcategories.
* **44 Sellable SKU Records:** 11 families have multiple physical pack sizes/weights (e.g., 1 kg, 5 kg, 10 kg, 1 L, 5 L, 450 g, 900 g, 250 g), creating 44 concrete stockable records.
* **8 Wholesale Carton Multipliers:** Master carton barcodes with multipliers up to $24\times$.
* **56 High-DPI WebP Visuals (< 10 KB each):** Stored in `Frontend/public/demo/` and `api/public/demo/`.

---

## 4. SINGLE-STORE VS. MULTI-STORE OPERATIONAL SPECIFICATION

### 4.1 Single-Store Operations (Sole Proprietor Mode)
* **Demo Shop Profile:** *Al-Madina General Store*, Commercial Area, Bahadurabad, Karachi.
* **Business Model:** Owner-operated general grocery store with 1 POS counter.
* **Morning Cash Float:** PKR 8,500.00.
* **Stock Valuation:** PKR 1,652,775.00 asset cost value across all 44 items.
* **Single-Store UI Shielding:**
  * When a business has only 1 active branch, the frontend hides all multi-branch chrome.
  * Branch selector pills, branch-transfer navigation items, multi-location filter buttons, and inter-store shipment menus are shielded.
  * The retailer sees a distraction-free, standard single-store POS interface.

### 4.2 Multi-Store Operations (Retail Chain Mode)
* **Demo Shop Profile:** *Al-Madina Supermarket*, Karachi.
* **3 Branches:**
  1. `Clifton Main Store` (`BR-01`, Primary flagship, Block 5 Clifton, Cash float PKR 15,000).
  2. `Saddar Express` (`BR-02`, High-footfall outlet, Preedy Street Saddar, Cash float PKR 8,000).
  3. `Gulshan Outlet` (`BR-03`, Neighborhood supermarket, Block 13-D Gulshan, Cash float PKR 6,500).
* **Branch Inventories:** 132 `branch_inventories` rows (44 SKUs $\times$ 3 branches) maintaining independent stock levels, reorder alerts, and physical counts per branch.
* **Inter-Branch Stock Transfers:**
  * Created via `stock_transfers` and `stock_transfer_items`.
  * Status lifecycle: `dispatched` $\rightarrow$ `in_transit` $\rightarrow$ `received`.
  * Dispatch immediately decrements stock from source branch; receiving increments stock at destination branch.

### 4.3 Converting Single-Store to Multi-Store (Branch Expansion)
* When an owner opens a new location, they go to **Settings $\rightarrow$ Branch Management** and click **Add New Branch**.
* Once a 2nd branch is added, the system automatically unshields multi-branch controls:
  * Top navigation displays the Active Branch Selector pill.
  * Stock Transfer view becomes active in the sidebar.
  * Product inventory tables show branch breakdown tabs.

### 4.4 Converting Multi-Store Back to Single-Store (Consolidation & Decommissioning)
* If an owner decides to close branch outlets and return to a single shop:
* **The 1-Click "Decommission & Consolidate Branch" Engine:**
  1. Scans for any pending in-transit transfers involving the closing branch and safely recalls/reroutes them to the primary branch.
  2. Sweeps all remaining inventory balances from the closing branch and merges them into the primary branch's `branch_inventories`.
  3. Sets the closing branch status to `decommissioned` (soft retirement).
  4. Never deletes historical sales, invoices, customer credit records, or audit trails (preserving strict foreign key and ledger integrity).
  5. Once all non-primary branches are decommissioned, the system automatically reactivates Single-Store UI Shielding.

---

## 5. OBSERVING & TESTING THE DEMO SANDBOX

The demo sandbox runs completely in-memory and inside IndexedDB without connecting to external servers or mutating live business databases.

### 5.1 Welcome Journey
* Cold launching the application always lands on the unauthenticated **Welcome Screen** (`WelcomeView.vue`) presenting 3 equal-prominence cards:
  1. **Log In:** For real merchants with existing cloud accounts.
  2. **Create My Business:** Clean onboarding registration (starts with 0 sample data).
  3. **Try Demo:** Instant preview with zero registration required.
* Clicking **Try Demo** opens a modal offering:
  * **Single-Store Demo** (*Al-Madina General Store*)
  * **Multi-Store Demo** (*Al-Madina Supermarket — 3 Branches*)

### 5.2 Top Demo Banner Controls
When active, an amber top banner (`DemoModeBanner.vue`) is pinned:
* **Switch Demo Type:** Toggle between Single-Store and Multi-Store instantly.
* **Reset Demo Data:** Wipes the local sandbox and restores factory defaults.
* **Exit Demo:** Clears session and returns to the Welcome Screen.
* **Create My Business:** Direct shortcut to start a real business account.

### 5.3 Simulated Business Operations
* **Dynamic 7-Month Rolling Timeline:** The engine (`demoDataGenerator.js`) generates authentic retail sales, purchases, and expenses ending on the current calendar day.
* **POS Checkout:** Add items to cart via 2-letter search, category tabs, or barcode scan. Completing a sale decrements stock and updates the cash drawer.
* **Carton Barcode Multipliers:** Scanning carton barcode `8964000400112` adds 12 pouches of Dalda Oil to the cart.
* **Customer Khata (Credit):** Selling on "Khata" increases customer debt. Recording payment in the Customer Profile reduces debt and adds cash to drawer.
* **Cash Register Balancing:** Opening float, sales additions, payouts, and day-end close with cash counting discrepancy calculation.
* **Fail-Closed Security Barrier:** Any mutation not explicitly handled by the sandbox returns HTTP 400 with `"This action is not available in Demo mode."` Zero fake toasts or phantom ledger records.

---

## 6. COMPLETE SPECIFICATION OF ALL 18 SYSTEM MODULES

### Module 1: POS Checkout Counter & Fast Retail Cashier
* **Live Component:** `Frontend/src/views/PosSaleView.vue` | **Active Routes:** `/pos`, `/POS`
* **Features:** USB keyboard-wedge barcode scanner auto-focus, product search by name/SKU, category filters, multi-item cart, hold/resume up to 5 concurrent carts, split tender (Cash, Card, JazzCash, Easypaisa, Khata), exact change calculator, thermal receipt generation (58mm/80mm), supervisor price override and line-item discount authorization.

### Module 2: Products, Catalog & Master Inventory
* **Live Component:** `Frontend/src/views/InventryView.vue` | **Active Routes:** `/inventory/products`, `/products`
* **Features:** 44 distinct sellable SKU records, unique primary EAN-13 barcodes, master carton barcode aliases with quantity multipliers (e.g. 12x, 24x), loose fractional products with 3-decimal precision (e.g. 10.500 kg), tiered pricing (Retail vs. Wholesale), below-cost selling warnings, 56 bundled WebP visuals (< 10 KB).

### Module 3: Inventory Ledger & Stock Movement
* **Live Components:** `InventryView.vue`, `ReorderView.vue`, `ReorderDetailView.vue` | **Active Routes:** `/inventory`, `/reorder`
* **Features:** Immutable `inventory_movements` ledger for every stock mutation, negative stock prevention via database concurrency locks, physical stock adjustments with mandatory reason codes (theft, damage, expiry, audit count), automated reorder alerts and slow-moving stock intelligence.

### Module 4: Suppliers, Purchasing & Landed Cost
* **Live Component:** `Frontend/src/views/PurchaseView.vue` | **Active Routes:** `/purchases`, `/purchase`
* **Features:** Supplier purchase orders supporting Paid, Unpaid, and Partial payment terms, proportional freight/landed cost allocation to unit cost, automatic stock increments upon receiving, purchase price variance tracking.

### Module 5: Supplier Payables & Debt Settlement
* **Live Components:** `SupplierView.vue`, `SupplierpayableView.vue`, `SupplierPaymentView.vue` | **Active Routes:** `/suppliers`, `/supplierpayable`
* **Features:** Supplier payables ledger (`due_amount` tracking), debt settlement payouts from Cash Drawer or Bank Account, auditable payment reversals with compensating journal entries, supplier return debit notes.

### Module 6: Customers & Pakistani Khata (Udhaar) Ledger
* **Live Components:** `CustomerView.vue`, `KhataView.vue`, `ProfilecustomerView.vue` | **Active Routes:** `/customers`, `/khata`
* **Features:** Customer directory with Pakistani phone validation (`03XXXXXXXXX`), credit limit enforcement, Khata repayment modal (Cash, Bank Transfer, Cheque), overpayment rejection (HTTP 422 if payment > balance), payment reversal with mandatory audit reasons, shareable WhatsApp and PDF statements.

### Module 7: Returns, Exchanges & Restock Grading
* **Live Component:** `Frontend/src/views/SalesView.vue` | **Active Routes:** `/sales`
* **Features:** Original invoice lookup, restock condition assessment:
  * **Restockable:** Increments sellable stock and reverses COGS.
  * **Damaged:** Routes item to damage quarantine without adding to sellable inventory.
* Cash refund settlement from cash drawer, prevention of duplicate return replays.

### Module 8: Operating Expenses & Petty Cash
* **Live Component:** `Frontend/src/views/ExpensesView.vue` | **Active Routes:** `/expenses`
* **Features:** Daily store expense recording (Utilities, Rent, Salaries, Refreshments, Maintenance), petty cash payouts from cash drawer with automatic drawer cash decrement, monthly expense budget tracking with threshold alerts.

### Module 9: Multi-Account Treasury & Financial Accounts
* **Live Component:** `Frontend/src/views/SettingsView.vue` | **Active Routes:** `/settings` (Treasury tab)
* **Features:** Multi-account management: Cash Drawer, Bank Accounts, Mobile Wallets (JazzCash, Easypaisa), inter-account transfers with double-entry balance conservation, capital injections and drawings.

### Module 10: Cash Register & Shift Sessions
* **Live Components:** `PosSaleView.vue`, `ExpensesView.vue` | **Active Routes:** `/pos`
* **Features:** Shift opening float initialization, real-time drawer balance tracking, day-end physical cash counting modal, discrepancy explanation requirement, shift locking (closed sessions cannot be modified).

### Module 11: Financial Reports & Small Retail Profitability
* **Live Components:** `DashboardView.vue`, `ReportsView.vue` | **Active Routes:** `/dashboard`, `/reports`
* **Features:** Realized Profit & Loss statement ($Net\ Sales - Snapshot\ COGS = Gross\ Profit$, $Gross\ Profit - Operating\ Expenses = True\ Net\ Profit$), sales breakdown by category, brand, and cashier, cashier privacy gate (HTTP 403 Forbidden on reports).

### Module 12: Multi-Branch Architecture & Expansion
* **Live Component:** `Frontend/src/views/BranchManagementView.vue` | **Active Routes:** `/branches`
* **Features:** Primary store immutability, branch expansion wizard, dynamic adaptive UI (Single-Store shielding vs. Multi-Store controls), 1-click "Decommission & Consolidate Branch" engine.

### Module 13: Inter-Branch Stock Transfers
* **Live Component:** `Frontend/src/views/StockTransferView.vue` | **Active Routes:** `/stock-transfers`
* **Features:** Transfer dispatch and receiving lifecycle (`dispatched` $\rightarrow$ `in_transit` $\rightarrow$ `received`), stock decremented at source upon dispatch, stock incremented at destination upon receive, strict company inventory conservation.

### Module 14: Role Governance & Access Control (RBAC)
* **Live Component:** `Frontend/src/views/UsersView.vue` | **Active Routes:** `/users`, `/settings/users`
* **Features:** Role hierarchy (Business Owner = universal access; Branch Manager = branch-scoped; Salesperson/Cashier = POS counter only), 403 Forbidden enforcement on reports and settings.

### Module 15: Offline Counter Resilience & Sync Coordinator
* **Live Services:** `offlinePosEngine.js`, `localDb.js`
* **Features:** Native IndexedDB outbox queue, client-side UUID generation for idempotent backend replay, offline sales creation, automatic background sync on network reconnection, unsynced outbox exit guard on desktop shutdown.

### Module 16: Hardware Interfaces & Printing
* **Features:** 58mm & 80mm ESC/POS thermal receipt formatting with Urdu/English store headers and return policy footers, USB keyboard-wedge barcode scanner listener, RJ11/RJ12 cash drawer kick triggers via printer driver.

### Module 17: Multi-Tenant Database Isolation & Data Protection
* **Features:** Strict `business_id` scoping across all database tables and Eloquent models, Zero-Data Guarantee on fresh business registrations, client-side session partitioning in local storage and IndexedDB.

### Module 18: Desktop Shell Controls & Lifecycle Management
* **Subsystems:** Tauri v1.6.3 + Win32 Native Chrome.
* **Features:** Standard 1366x768 initial window with resize, minimize, maximize, and close controls, zero kiosk trapping, clean process termination on close.

---

## 7. THE 50 ESSENTIAL OPERATIONAL POINTS & UAT CHECKLIST

| # | Test Area / Requirement | Operation & Edge Case Description | Expected Result & Verification Standard |
|:---|:---|:---|:---|
| **1** | Native Window Launch | App startup in desktop shell | Standard 1366x768 window with native title bar, minimize, maximize, and close buttons. No kiosk trapping. |
| **2** | Window Manipulation | Minimizing, maximizing, Alt+Tab | Window minimizes smoothly; Alt+Tab cycles between windows without HBOS seizing focus. |
| **3** | Safe Native Exit | Closing app via (X) or Alt+F4 | Process terminates cleanly in Task Manager with zero orphaned WebView2 processes. |
| **4** | Three Welcome Choices | Layout of `/welcome` screen | Three prominent cards: Log In (teal), Create Business (blue), Try Demo (purple). Connectivity indicator visible. |
| **5** | Welcome to Login | Navigating `/welcome` $\rightarrow$ `/login` | Form renders clean inputs; clicking "Back to Welcome" returns to `/welcome`. |
| **6** | Welcome to Register | Navigating `/welcome` $\rightarrow$ `/register` | Registration form renders; clicking "Back to Welcome" returns to `/welcome`. |
| **7** | Single-Store Demo Init | Launching Single-Store demo | Lands on POS; sticky amber banner displays "Single Store Demo"; cash float initialized to PKR 8,500.00. |
| **8** | Single-Store Cash Sale | Ringing up sale in demo mode | Invoice generated; stock decrements instantly; drawer cash increments; zero server requests sent. |
| **9** | Single-Store Khata Sale | Ringing up credit sale in demo | Customer debt increases; drawer cash unchanged; total accounts receivable increase. |
| **10** | Single-Store Demo Reset | Clicking "Reset Demo Data" | Sandbox resets to deterministic factory template; stock, customer balances, and drawer restored. |
| **11** | Multi-Store Demo Init | Launching Multi-Store demo | Sticky banner shows "Multi-Store Demo"; branch switcher visible; 3 branches provisioned. |
| **12** | In-Transit Stock Transfer | Viewing `/stock-transfers` in demo | Transfer #TRF-2026-001 visible with status "In-Transit"; 5 units Sufi Oil deducted from Clifton, not yet in Saddar. |
| **13** | Fresh Account Registration | Creating account on `/register` | Account created (201 Created); lands on store setup; exactly 0 products, 0 sales, 0 customers created. |
| **14** | Zero-State Catalog & Cash | Inspecting fresh account | Products table shows "No products found"; dashboard displays PKR 0 sales, PKR 0 expenses, PKR 0 cash. |
| **15** | Existing Merchant Sign-In | Logging in on `/login` | Authenticates; stores Bearer token in `hbos_token`; redirects to dashboard with genuine tenant data. |
| **16** | Remembered Session Banner | Returning to `/welcome` with token | Banner displays: "Continue as [Owner Name] ([Store Name])" with "Resume Session ->" button. |
| **17** | POS Cash Sale & Change | Tender cash > bill amount in POS | Prominently displays exact change due; completes sale; updates cash drawer and decrements stock. |
| **18** | Fast Barcode Scanning | USB scanner input on POS counter | Detects barcode scan in < 3 seconds; matches product; adds item to cart without manual clicking. |
| **19** | Master Carton Multipliers | Scanning carton barcode alias | Scanning carton barcode automatically increments base product by quantity multiplier (e.g. 12x or 24x). |
| **20** | Cart Hold & Resume | Holding cart for waiting customer | Holds up to 5 concurrent shopping carts; switches between carts seamlessly without losing items. |
| **21** | Fractional Loose Products | Creating & selling loose goods (kg) | Supports 3-decimal precision (e.g. 1.250 kg); calculates correct line item price and stock balance. |
| **22** | Tiered Pricing | Toggling wholesale pricing | POS allows selecting wholesale tier when enabled; applies wholesale price and checks margin. |
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

## 8. AUTOMATED VERIFICATION & RELEASE GATES

Run the automated verification suite to test all 20 architectural invariants:
```powershell
cd C:\xampp\htdocs\HBOS
node scripts/verify_inventory_demo.js
```

### The 20 Architectural Invariants:
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
