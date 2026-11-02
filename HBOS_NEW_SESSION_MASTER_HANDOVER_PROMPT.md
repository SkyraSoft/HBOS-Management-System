# HBOS ENTERPRISE SUITE — MASTER CONTINUATION PROMPT & SYSTEM HANDOVER

> **Purpose:** Provide complete, authoritative, and unambiguous context for continuing development, testing, and operations of SkyraSoft HBOS on any device or new AI agent conversation.

---

## 1. REPOSITORY & CODE PROVENANCE

* **GitHub Repository:** `git@github.com:SkyraSoft/HBOS-Management-System.git`
* **Active Branch:** `main`
* **Latest Verified Commit:** `a444e07` (*"feat: complete canonical inventory architecture, WebP visual assets, customer khata ledger repairs, and desktop release"*)
* **Local Project Directory (Primary Machine):** `c:\xampp\htdocs\HBOS`
* **Sub-projects:**
  * `Frontend/`: Vue 3 + Vite SPA (Pinia, Vue Router, Tailwind/Bootstrap CSS, IndexedDB offline engine)
  * `api/`: Laravel 11 REST API (PHP 8.3, MariaDB / MySQL, Sanctum auth, multi-tenant scoping)
  * `desktop/`: Tauri v1.6.3 desktop shell (Rust + Microsoft Edge WebView2, WiX MSI installer)
  * `scripts/`: Verification, asset generation, and lifecycle management scripts
  * `docs/release/`: Canonical release manifests, financial reconciliation, and architecture runbooks

---

## 2. PRODUCTION HOSTING & SSH CREDENTIALS

* **Cloud Provider:** Hostinger Premium Cloud Hosting
* **Live Web Subdomain & REST API:** `https://hbos.skyrasoft.com`
* **Health Check Endpoint:** `https://hbos.skyrasoft.com/api/v1/health` (Returns HTTP 200 `{"status":"ok","database":"ok"}`)
* **Official Distribution Download Portal:** `https://hbos.skyrasoft.com/download.html`
* **Direct MSI Download Link:** `https://hbos.skyrasoft.com/downloads/HBOS_POS_1.0.0_STAGE3_UAT_x64_en-US.msi`
* **Demo Visual Assets Directory:** `https://hbos.skyrasoft.com/demo/` (`brands/`, `categories/`, `products/`)

### SSH & SCP Remote Access:
* **Host IP:** `82.29.191.191`
* **Port:** `65002`
* **SSH Username:** `u856400307`
* **Authentication:** SSH Private Key (`id_ed25519`)
* **Remote Application Root:** `/home/u856400307/domains/skyrasoft.com/public_html/hbos`
* **Production Database:** MariaDB 11.8.9 (`u856400307_hbos_db`), User: `u856400307_hbos_user`
* **Standard SSH Command Format:**
  ```powershell
  ssh -n -i $HOME/.ssh/id_ed25519 -p 65002 u856400307@82.29.191.191 "<command>"
  ```
* **Standard SCP Upload Format:**
  ```powershell
  scp -i $HOME/.ssh/id_ed25519 -P 65002 "<local-file>" u856400307@82.29.191.191:domains/skyrasoft.com/public_html/hbos/<remote-path>
  ```

---

## 3. ACTUAL HBOS INVENTORY ARCHITECTURE (STRICT RULES)

### A. Independent Stockable SKUs for Physical Pack Sizes
* HBOS does **NOT** use a virtual parent non-stockable matrix table.
* Every distinctly sellable physical package (e.g. *DailyHarvest Super Basmati Rice 1 kg*, *5 kg*, and *10 kg*) is an **independent product record** in `products` (and `branch_inventories` in multi-store).
* Each SKU record possesses:
  * Unique numeric Product ID
  * Unique alphanumeric SKU code
  * Unique primary EAN-13 barcode
  * Brand ID, Category ID, Subcategory ID
  * Concrete unit of measure (`kg`, `litre`, `pack`, `bottle`, `can`)
  * Cost price & Selling price (verified `cost_price < selling_price` with positive retail margin)
  * Minimum stock reorder threshold
  * Independent stock quantity & independent valuation

### B. Alternate Barcode Multipliers (`product_barcodes`)
* `product_barcodes` maps alternate barcodes to a base product with a `quantity_multiplier`.
* **Strict Usage Rule:** Alternate barcodes are used **exclusively for multi-unit cases/cartons of the exact same physical product** (e.g., Master Carton containing $12\times$ 1 L oil bottles). Scanning the carton barcode automatically multiplies the quantity in POS line items.
* Never merge distinct pack sizes (e.g. 1 kg vs 5 kg) into barcode aliases.

### C. Canonical Catalogue Counts:
* **Brands:** Exactly 4 (*DailyHarvest Foods*, *Khyber Tea & Spices*, *Indus Dairy & Oils*, *PakClean & Care*)
* **Categories:** Exactly 8 (*Grains & Staples*, *Flours & Baking*, *Beverages & Refreshments*, *Dairy & Breakfast*, *Cooking Oils & Ghee*, *Spices & Seasonings*, *Snacks & Confectionery*, *Hygiene & Cleaning*)
* **Subcategories:** Exactly 16 (exactly 2 per category)
* **Product Families:** Exactly 32 (exactly 2 named families per subcategory)
* **Stockable SKU Records:** 44 concrete records (11 families expand into multiple physical pack sizes)
* **Carton Barcode Aliases:** 8 wholesale carton multipliers ($6\times$, $12\times$, $24\times$, $36\times$)
* **Visual Assets:** 56 vector SVGs + 56 high-DPI WebP files (< 10 KB each) bundled in `Frontend/public/demo/` and deployed to `https://hbos.skyrasoft.com/demo/`.

---

## 4. SINGLE-STORE VS. MULTI-STORE OPERATIONAL MECHANISMS

```
+----------------------------------------------------------------------------------------------------+
|                                      HBOS STORE MODES                                              |
+--------------------------------------------------+-------------------------------------------------+
|               SINGLE-STORE MODE                  |                MULTI-STORE MODE                 |
+--------------------------------------------------+-------------------------------------------------+
| Shop Identity: Al-Madina General Store           | Shop Identity: Al-Madina Supermarket            |
| Location: Bahadurabad Commercial Area, Karachi   | Branches: 3 Operational Branches in Karachi     |
| Owner: Haji Mohammad Irfan                       | 1. Clifton Main Store (BR-01, Primary)          |
| Cash Float: PKR 8,500.00                         | 2. Saddar Express (BR-02)                       |
| Catalog Valuation: PKR 1,652,775                 | 3. Gulshan Outlet (BR-03)                       |
| Inventory: Direct product records on shop        | Inventory: 132 branch_inventories records       |
| UI Shielding: Branch selectors, transfers, and   | Transfers: Inter-branch transfer dispatch,      |
| multi-branch dropdowns are completely hidden     | in-transit tracking, and receiving workflow     |
+--------------------------------------------------+-------------------------------------------------+
```

### A. How Single-Store Operates:
* Designed for small standalone retail shops and sole proprietors.
* **UI Shielding:** All multi-branch elements (branch switchers, inter-store transfers, multi-branch badges) are hidden from view. The merchant operates with simple, uncluttered POS, inventory, expenses, and customer credit.
* **Storage:** Operates locally on IndexedDB namespace `HBOS_Store_Biz_demo_single` (or local tenant SQLite/MariaDB).

### B. How Multi-Store Operates:
* Designed for retail chains, supermarkets, and multi-location businesses.
* **Branch Isolation:** Each branch has independent stock levels, reorder alerts, cash registers, and physical footfall.
* **Stock Transfers:** Supported via `stock_transfers` and `stock_transfer_items`. Dispatches deduct stock from source branch, place items in transit, and receipt adds stock to destination branch.
* **Centralized Reporting:** Consolidated view of sales, profit, and debt across all 3 branches.

### C. Converting Single Store to Multi-Store:
* When a merchant expands, they access the Branch Expansion flow.
* Registering a second branch automatically transitions the UI to reveal branch-switching controls and provisions `branch_inventories` rows mapped to the central product catalog.

### D. Converting Multi-Store Back to Single Store (Decommissioning):
* If an owner decides to close branch outlets and return to a single shop:
* **The 1-Click "Decommission & Consolidate Branch" Wizard:**
  1. Identifies any pending in-transit transfers and recalls/reroutes them to the primary branch.
  2. Sweeps all remaining physical inventory quantities from the closing branch into the primary store's `branch_inventories`.
  3. Sets the closing branch status to `decommissioned` / inactive.
  4. Preserves all historical sales, invoices, and ledger records without deleting foreign keys.
  5. Once only 1 active branch remains, the system automatically reactivates Single-Store UI Shielding.

---

## 5. OBSERVING & TESTING THE DEMO SANDBOX

### Cold Startup & Entry Experience:
1. Launch the application (or run `npm run dev` in `Frontend/`).
2. Cold startup always lands cleanly on the unauthenticated **Welcome Screen** (`Frontend/src/views/WelcomeView.vue`) presenting 3 equal cards:
   * **Card 1: Log In** (Existing shop account)
   * **Card 2: Create My Business** (New retailer registration)
   * **Card 3: Try Demo** (Instant sandbox exploration)
3. Clicking **Try Demo** opens a selection modal offering:
   * **Single-Store Demo** (*Al-Madina General Store*)
   * **Multi-Store Demo** (*Al-Madina Supermarket — 3 Branches*)
4. Selecting a demo activates the persistent amber top banner (`DemoModeBanner.vue`):
   * Controls: `Switch Demo Type`, `Reset Demo Data`, `Exit Demo`, `Create My Business`.
5. **Simulation Engine (`demoSandboxEngine.js`):**
   * Operates completely offline inside browser / WebView2 memory and IndexedDB.
   * Generates a 7-month dynamic rolling timeline ending on the current calendar day.
   * Real-time POS checkout decrements stock and updates today's sales.
   * Customer debt recovery payments update customer balances and cash drawer.
   * **Fail-Closed Mutation Barrier:** Any unsupported mutation returns HTTP 400 with `"This action is not available in Demo mode."` Zero phantom records or fake toasts.

---

## 6. CUSTOMER KHATA & ACCOUNTS CONSOLIDATION

* Customer profiles, outstanding credit limits, receivables balances, and khata ledgers are unified into a single coherent workflow.
* In **Customers** (`Frontend/src/views/CustomerView.vue`):
  * Outstanding balance, credit limit, and total purchases bind directly to customer ledger state.
  * **Record Payment:** Allows cash or bank collection against customer credit with immediate balance adjustment.
  * **Payment Reversals:** Accidental payment entries can be reversed with audit logging.
  * **Customer Profile:** Provides full transaction history of credit sales, recoveries, and net balance.

---

## 7. WINDOWS DESKTOP APP & PACKAGING

* **Desktop Shell Framework:** Tauri v1.6.3 (`desktop/`) + Microsoft Edge WebView2.
* **Window Parameters (`desktop/tauri.conf.json`):**
  * `width: 1366`, `height: 768`, `minWidth: 1024`, `minHeight: 600`
  * `resizable: true`, `fullscreen: false`, `decorations: true` (standard Win32 title bar, minimize, maximize, and close buttons preventing kiosk trapping).
* **Building the Desktop MSI Package:**
  ```powershell
  # 1. Compile Frontend web SPA
  cd c:\xampp\htdocs\HBOS\Frontend
  npm run build

  # 2. Clear stale Cargo compilation cache
  cd c:\xampp\htdocs\HBOS
  Remove-Item -Recurse -Force "desktop\target\release\build\hbos-pos*" -ErrorAction SilentlyContinue
  Remove-Item -Recurse -Force "desktop\target\release\deps\hbos_pos*" -ErrorAction SilentlyContinue

  # 3. Build WiX MSI bundle
  cd desktop
  npx -y @tauri-apps/cli@1.6.3 build --bundles msi
  ```
* Output installer is generated at:  
  `desktop\target\release\bundle\msi\HBOS POS_1.0.0_x64_en-US.msi`

---

## 8. QUICK RUNBOOK FOR A NEW AGENT / DEVICE

When resuming on another machine:

```powershell
# 1. Clone repository
git clone git@github.com:SkyraSoft/HBOS-Management-System.git
cd HBOS-Management-System

# 2. Install & Run Frontend
cd Frontend
npm install
npm run dev
# Accessible locally at http://localhost:5173

# 3. Run Backend Feature Tests
cd ../api
composer install
php artisan test --filter=DemoSandboxIsolationAndReconciliationTest

# 4. Run Automated Demo Architecture Verification (All 20 Checks)
cd ..
node scripts/verify_inventory_demo.js
```

---

## 9. ACTIVE SYSTEM STATUS & SUMMARY FOR NEXT AGENT

* **GitHub Status:** 100% synchronized (`origin/main` at commit `a444e07`).
* **Live Website Status:** Operational at `https://hbos.skyrasoft.com` with active download portal and WebP visuals.
* **MSI Status:** Rebuilt, tested, and published (22.41 MB, SHA-256 `CC033239C30F14A5B6B5DC54C517FCA38EF206048F229C2CD0329E4A04DE15E3`).
* **Verification Status:** All 20 Section 9 architectural verification checks passing.
