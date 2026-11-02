# SKYRASOFT HBOS (HYPERLOCAL BUSINESS OPERATING SYSTEM)
## COMPLETE SYSTEM MASTER MANUAL, DEVELOPER ONBOARDING & CONTINUATION SPECIFICATION

> **Author:** SkyraSoft Engineering  
> **Repository:** `git@github.com:SkyraSoft/HBOS-Management-System.git`  
> **Target Audience:** Any new developer, AI agent, or engineering rig taking over development, testing, deployment, or hardware qualification of HBOS.

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
   ssh-keygen -t ed25519 -C "your_email@example.com"
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
*Current verified commit baseline:* `e08e89e` or later.

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

# Set up local test database (SQLite for instant zero-config testing)
# Or configure MySQL/MariaDB in api/.env:
# DB_CONNECTION=mysql, DB_DATABASE=hbos, DB_USERNAME=root, DB_PASSWORD=

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
* **Useful Remote Commands:**
  ```powershell
  # Check live backend files
  ssh -n -i $HOME/.ssh/id_ed25519 -p 65002 u856400307@82.29.191.191 "ls -lh domains/skyrasoft.com/public_html/hbos/public/downloads/"

  # Check remote SHA256 of published installer
  ssh -n -i $HOME/.ssh/id_ed25519 -p 65002 u856400307@82.29.191.191 "sha256sum domains/skyrasoft.com/public_html/hbos/public/downloads/HBOS_POS_1.0.0_STAGE3_UAT_x64_en-US.msi"

  # Upload new files to production
  scp -i $HOME/.ssh/id_ed25519 -P 65002 "<local-file>" u856400307@82.29.191.191:domains/skyrasoft.com/public_html/hbos/<remote-path>
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

## 6. CUSTOMER KHATA & FINANCIAL RECONCILIATION

Customer credit is integrated into a unified ledger architecture:
* **Table:** `customers` with synchronized ledger calculations.
* **Customer Profile View (`CustomerView.vue`):**
  * Displays: Total Purchases, Outstanding Balance, Credit Limit, Last Transaction Date.
  * **Record Payment Modal:** Records debt recovery payments with payment method (Cash, Bank Transfer, Cheque).
  * **Payment History & Reversals:** Accidental payments can be reversed with a mandatory reason, restoring customer debt and audit logging the correction.
* **Pre-Loaded Khata Customers:**
  1. *Tariq Mehmood* (`03001234567`): Balance PKR 3,200.00
  2. *Haji Abdul Rehman* (`03219876543`): Balance PKR 4,500.00
  3. *Chaudhry Aslam* (`03335551212`): Balance PKR 2,800.00
  4. *Mohammad Imran* (`03457778899`): Balance PKR 1,900.00
  * **Total Receivables:** PKR 12,400.00.

---

## 7. WINDOWS DESKTOP PACKAGING (TAURI & WIX)

The Windows desktop client is built using Tauri v1.6.3 and WiX Toolset.

* **Configuration (`desktop/tauri.conf.json`):**
  * Subsystem: Windows GUI Subsystem
  * Window Parameters: `width: 1366`, `height: 768`, `resizable: true`, `fullscreen: false`, `decorations: true` (standard Win32 title bar, minimize, maximize, and close buttons preventing kiosk trapping).
* **Compilation Command:**
  ```powershell
  # Step 1: Build Frontend SPA
  cd c:\xampp\htdocs\HBOS\Frontend
  npm run build

  # Step 2: Invalidate Rust build cache
  cd c:\xampp\htdocs\HBOS
  Remove-Item -Recurse -Force "desktop\target\release\build\hbos-pos*" -ErrorAction SilentlyContinue
  Remove-Item -Recurse -Force "desktop\target\release\deps\hbos_pos*" -ErrorAction SilentlyContinue

  # Step 3: Package WiX MSI
  cd desktop
  npx -y @tauri-apps/cli@1.6.3 build --bundles msi
  ```
* Output installer location:  
  `desktop\target\release\bundle\msi\HBOS POS_1.0.0_x64_en-US.msi`
* Standard distribution name: `HBOS_POS_1.0.0_STAGE3_UAT_x64_en-US.msi` (Size: 22.41 MB, SHA-256: `CC033239C30F14A5B6B5DC54C517FCA38EF206048F229C2CD0329E4A04DE15E3`).

---

## 8. AUTOMATED VERIFICATION CHECKLIST (20 ARCHITECTURE CHECKS)

Run the automated verification suite anytime:
```powershell
node scripts/verify_inventory_demo.js
```

1. **Exactly 4 brands:** Passed (`DEMO_BRANDS.length === 4`).
2. **Exactly 8 categories:** Passed (`DEMO_CATEGORIES.length === 8`).
3. **Exactly 16 subcategories:** Passed (2 per category).
4. **Exactly 2 product families per subcategory:** Passed (32 total families).
5. **At least 32 independent stockable SKU records:** Passed (44 distinct sellable records).
6. **Physical pack-size distinctions:** Passed (11 families have independent pack sizes).
7. **Unique SKUs:** Passed (44 unique alphanumeric codes).
8. **Unique primary barcodes:** Passed (44 unique EAN-13 barcodes).
9. **Barcode alias quantity multipliers:** Passed (8 carton multipliers defined).
10. **Cost and selling prices:** Passed (All items have `cost_price < selling_price`).
11. **Unit handling:** Passed (`kg`, `litre`, `pack`, `bottle`, `can` valid).
12. **Minimum-stock alerts:** Passed (Individual thresholds configured).
13. **Inventory valuation:** Passed (Accurate valuation: PKR 1,652,775).
14. **Stock deductions after POS sales:** Passed (Real-time decrement verified).
15. **Stock updates after purchases and receiving:** Passed (Restock receipt verified).
16. **Stock adjustments and returns:** Passed (Manual inventory adjustment verified).
17. **Branch-specific stock separation:** Passed (Clifton: 44, Saddar: 44, Gulshan: 44).
18. **Inter-branch stock transfers:** Passed (Transfer dispatch and receiving verified).
19. **Product image integration:** Passed (All 44 product images, 4 brand logos, and 8 category icons exist).
20. **100% Offline operation:** Passed (0 external URLs; all assets load from local public directories).
