# SKYRASOFT HBOS — COMPLETE OPERATIONAL RELEASE SUITE
## Phase 10 Definitive Standard Operating Procedures (SOPs) & Release Manuals
**Target Release:** v1.0.0 (Release Candidate)  
**Scope:** 32 Canonical Requirements (`REQ-0001` .. `REQ-0032`), 25 Root Gaps (`SRT-GAP-001` .. `SRT-GAP-025`)  
**Database Architecture:** MariaDB 10.4.32 / InnoDB  
**Frontend Architecture:** Vue 3 / Vite 8.2.1 / Pinia / Chromium IndexedDB Native Engine  
**Desktop Target:** Tauri v1 Windows Desktop (`com.skyrasoft.hbos`)  

---

## TABLE OF CONTENTS
1. [DOC-01: Store Owner Quickstart & Onboarding Guide](#doc-01-store-owner-quickstart--onboarding-guide)
2. [DOC-02: Cashier POS Counter Operations Manual](#doc-02-cashier-pos-counter-operations-manual)
3. [DOC-03: Branch Manager Store Operations & Supervision](#doc-03-branch-manager-store-operations--supervision)
4. [DOC-04: Product Catalog Master, Barcodes & Pricing Guide](#doc-04-product-catalog-master-barcodes--pricing-guide)
5. [DOC-05: Purchasing, Goods Receipt Note (GRN) & Landed Cost](#doc-05-purchasing-goods-receipt-note-grn--landed-cost)
6. [DOC-06: Customer Khata Credit Ledger & Recovery SOP](#doc-06-customer-khata-credit-ledger--recovery-sop)
7. [DOC-07: POS Returns, Refunds, Damaged Goods & Restock SOP](#doc-07-pos-returns-refunds-damaged-goods--restock-sop)
8. [DOC-08: Cash Register Opening Float, Shifts & Day-End Balancing](#doc-08-cash-register-opening-float-shifts--day-end-balancing)
9. [DOC-09: Daily Store Expense Recording & Category Budgets](#doc-09-daily-store-expense-recording--category-budgets)
10. [DOC-10: Treasury Accounts, Bank Transfers & Drawer Cash](#doc-10-treasury-accounts-bank-transfers--drawer-cash)
11. [DOC-11: Inter-Branch Stock Transfers & In-Transit Guide](#doc-11-inter-branch-stock-transfers--in-transit-guide)
12. [DOC-12: Offline POS Operations & Sync Troubleshooting](#doc-12-offline-pos-operations--sync-troubleshooting)
13. [DOC-13: Thermal Receipt Printer & Barcode Scanner Setup (Hardware Limitations Noted)](#doc-13-thermal-receipt-printer--barcode-scanner-setup)
14. [DOC-14: Windows Desktop App Installation & Configuration](#doc-14-windows-desktop-app-installation--configuration)
15. [DOC-15: Technical Production Deployment Runbook (Hostinger/VPS)](#doc-15-technical-production-deployment-runbook)
16. [DOC-16: Disaster Recovery, Database Backup & Restore Runbook](#doc-16-disaster-recovery-database-backup--restore-runbook)
17. [DOC-17: Release Notes, Version Matrix & Known Limitations](#doc-17-release-notes-version-matrix--known-limitations)

---

### DOC-01: Store Owner Quickstart & Onboarding Guide
- **Target Audience:** Small Retail Business Owner
- **Core Principle:** Single-store simplicity first; seamless multi-branch expansion without enterprise overhead.
- **Workflow:**
  1. **First-Time Sign-in:** Navigate to the HBOS web portal or launch the desktop application. Enter registered business owner credentials.
  2. **Primary Branch Activation:** Upon registration, HBOS automatically provisions the default Primary Branch (Main Store) with default Cash Drawer and capital ledger accounts.
  3. **Role Assignment & Staff Setup:** Go to *Settings > Users*. Create Cashier and Manager accounts. Assign precise branch bindings. Note: In single-store mode, all multi-branch selector controls remain elegantly hidden to maximize counter speed.
  4. **Owner Command Dashboard:** View real-time consolidated sales, gross margin, cash in drawers across branches, and urgent reorder alerts from any device.
  5. **View-As Mode:** Auditing a specific branch or staff member? Activate *View-As* mode to safely simulate cashier or branch manager visibility without altering underlying administrative authorization.

---

### DOC-02: Cashier POS Counter Operations Manual
- **Target Audience:** Front-Counter Cashier / Salesperson
- **Key Features:** Fast keyboard navigation, quick customer lookup, fractional quantities, split payments.
- **Daily Procedure:**
  1. **Shift Opening:** Enter opening cash float count at start of day. Cash drawer is locked to the authenticated cashier session.
  2. **Item Scanning & Entry:** Scan barcode or type SKU/product name. Use `*` or direct keyboard input for fractional weights (e.g. `1.250 kg sugar`).
  3. **Hold / Resume Cart:** When a customer needs to fetch an extra item, click *Hold Cart*. Service the next customer immediately. Click *Resume Cart* when customer returns.
  4. **Tender & Checkout:**
     - **Cash:** Enter tender received, view exact change calculated.
     - **Split Tender:** Split across Cash and Bank/Card (e.g., 2,000 cash + 3,000 digital transfer).
     - **Credit (Khata):** Select verified customer; balance updates instantly.
  5. **Receipt:** Automated thermal print dialog opens; counter drawer kicks automatically upon cash confirmation.

---

### DOC-03: Branch Manager Store Operations & Supervision
- **Target Audience:** Branch Supervisor / Store Manager
- **Responsibilities:** Stock oversight, purchase receipting, transfer acceptance, cash drawer close oversight.
- **Branch Bounds:** Managers are bound to their assigned branch. Access to foreign branch financials or owner-only settings is strictly blocked by backend `TenantScope` and branch middleware.
- **Action Notes:** Review operational action notes sent from the Store Owner. Mark tasks completed with resolution timestamps.

---

### DOC-04: Product Catalog Master, Barcodes & Pricing Guide
- **Target Audience:** Owner & Store Manager
- **Product Hierarchy:**
  - `Product`: Master SKU, barcode, unit cost, retail selling price, wholesale price, stock alert reorder level.
  - `Product Barcodes`: Support for multiple secondary barcodes per SKU (e.g. Carton barcode, inner pack barcode, manufacturer barcode).
  - `Fractional Units`: Flag `allow_fractional = true` allows decimal quantities (loose grain, cloth meters, liquids). Flag `false` restricts to integer piece counts.
- **Pricing Governance:** Server price authority prevents unauthorized cashier discounting. Price overrides require manager credentials or pre-configured discount limits.

---

### DOC-05: Purchasing, Goods Receipt Note (GRN) & Landed Cost
- **Target Audience:** Inventory Manager & Owner
- **Purchase Order & Receiving Workflow:**
  1. Create PO with supplier selection, agreed line-item purchase costs.
  2. When shipment arrives at store, enter physical count in Goods Receipt (GRN).
  3. **Landed Cost Allocation:** Add freight, transport, unloading labor, or customs expenses. HBOS dynamically recalculates the true landed cost per item, updating average moving cost for accurate gross margin tracking.
  4. Supplier balance ledger is automatically updated in real-time.

---

### DOC-06: Customer Khata Credit Ledger & Recovery SOP
- **Target Audience:** Owner & Cashier
- **Khata Rules:**
  1. Khata credit sales require an enrolled customer profile with verified phone number.
  2. **Credit Limit Protection:** If a customer exceeds their defined credit limit, checkout alerts cashier and requires owner authorization.
  3. **Recovery / Collection:** Cashier records Khata payment received at POS. System immediately posts inflow to the cash drawer and decrements outstanding balance.
  4. **Customer Statement:** One-click WhatsApp statement or printed receipt showing historical balance and invoice breakdown.

---

### DOC-07: POS Returns, Refunds, Damaged Goods & Restock SOP
- **Target Audience:** Cashier & Branch Manager
- **Return Workflow:**
  1. Enter original invoice number or scan receipt barcode.
  2. Select item and quantity being returned. Choose return reason: *Customer Return*, *Damaged Goods*, or *Defective*.
  3. **Restock Decision:** Resaleable items are returned to active branch inventory. Damaged items are routed to the write-off/shrinkage ledger.
  4. **Refund Tender:** Refund is issued in cash or credited back to the customer's Khata account.

---

### DOC-08: Cash Register Opening Float, Shifts & Day-End Balancing
- **Target Audience:** Cashier & Owner
- **Blind Closing Protocol:**
  1. At end of shift, cashier enters physical counted cash in drawer (closing float + collected cash).
  2. The system does not reveal the expected balance beforehand (*Blind Close*).
  3. Upon submission, HBOS computes cash variance (`Counted - Expected`).
  4. Variances are permanently logged in `cash_register_sessions` and surfaced to the Owner Command Dashboard for investigation.

---

### DOC-09: Daily Store Expense Recording & Category Budgets
- **Target Audience:** Branch Manager & Cashier
- **SOP:**
  1. Daily petty cash expenses (tea, cleaning, delivery fuel, utilities) must be logged immediately.
  2. Select expense category, amount, payment account (Cash Drawer or Petty Cash fund).
  3. Real-time validation checks category monthly budget. If exceeded, notification is dispatched to the Store Owner.

---

### DOC-10: Treasury Accounts, Bank Transfers & Drawer Cash
- **Target Audience:** Store Owner
- **Treasury Management:**
  1. Define cash drawers, store safes, and commercial bank accounts.
  2. Record inter-account sweeps (e.g. Cash Drawer EOD cash skim transferred to Store Safe or deposited to Meezan Bank).
  3. Atomic ledger movements ensure double-entry precision: total liquid assets remain balanced at all times.

---

### DOC-11: Inter-Branch Stock Transfers & In-Transit Guide
- **Target Audience:** Branch Managers & Store Owner
- **Transfer Lifecycle:**
  1. **Draft:** Source branch prepares transfer request.
  2. **Dispatched / In-Transit:** Source branch marks dispatched. Stock is deducted from source `quantity_on_hand` and placed into `in_transit`. Stock is NOT yet available at destination.
  3. **Received:** Destination branch physically counts goods and clicks *Receive*. Stock moves from `in_transit` to destination `quantity_on_hand`.
  4. **Variance Handling:** Any transit loss or discrepancy triggers an audit note and requires manager sign-off.

---

### DOC-12: Offline POS Operations & Sync Troubleshooting
- **Target Audience:** All Retail Staff
- **Architecture:** Local-first IndexedDB (`HBOS_Store_Biz_${id}`).
- **Offline Behavior:**
  1. When network drops, POS continues operations with 0 ms interruption.
  2. Offline sales are assigned unique client UUIDs and stored durably in the local IndexedDB `outbox`.
  3. When network reconnects, `SyncCoordinator` background worker triggers automatic replay.
  4. Replay is strictly idempotent: re-transmitting an already processed sale returns success without duplicating stock deductions or financial receipts.
- **Troubleshooting:**
  - *Orange Network Indicator:* App is in offline mode. Do NOT clear browser cache or private browsing storage.
  - *Stuck Outbox Item:* Click *Sync Now* in POS header. If conflict occurs (e.g. price change), resolve via review dialog.

---

### DOC-13: Thermal Receipt Printer & Barcode Scanner Setup
- **Target Audience:** Store Owner & Hardware Setup Technician
- **Hardware Integration Status:**
  > [!IMPORTANT]
  > **Physical Hardware UAT Pending:** Physical thermal receipt printer, barcode scanner, and electronic cash drawer units were not attached to the test host environment. Software event handling, standard print dialog styling, and keyboard wedge scan listeners have been verified in Chrome, but physical peripheral validation requires on-site hardware verification.
- **Configuration Instructions:**
  1. **Thermal Printer (58mm / 80mm):** Install manufacturer Windows driver (e.g., EPSON, Xprinter, POS-58). Set as Windows Default Printer. In Chrome/Tauri print settings, select paper size `80mm x 297mm` and set margins to `None`.
  2. **Barcode Scanner (USB / Wireless 2.4G):** Configure scanner to USB HID Keyboard Wedge mode with automatic Carriage Return / Enter suffix (`CR+LF`).
  3. **Cash Drawer:** Connect RJ11 cable from drawer to the receipt printer's DK port. Printer driver will trigger pulse on print completion.

---

### DOC-14: Windows Desktop App Installation & Configuration
- **Target Audience:** Store Owner & IT Technician
- **Prerequisites:**
  - OS: Windows 10 / Windows 11 (64-bit)
  - Microsoft Edge WebView2 Evergreen Runtime (pre-installed on Windows 10/11)
- **Toolchain & Release Status:**
  > [!WARNING]
  > **Internal Test Build Notice:** Local development machine currently lacks the Rust/Cargo native compilation toolchain in PATH and code signing certificate (`certificateThumbprint: null`). The desktop application has been built as a production web bundle in `Frontend/dist`. Native MSI/NSIS packaging requires installing the official Rust toolchain via `rustup` on a configured release runner.
- **Client Configuration:**
  - Settings are stored in `%APPDATA%\com.skyrasoft.hbos\config.json`.
  - Default API endpoint: `https://hbos.skyrasoft.com/api/v1` (or local store server IP).

---

### DOC-15: Technical Production Deployment Runbook
- **Target Audience:** DevOps & System Administrator
- **Hosting Environment:** Hostinger VPS / Ubuntu Linux / Cloud Linux with Nginx, PHP 8.2+, MariaDB 10.4+.
- **Step-by-Step Procedure:**
  1. Clone authorized repository commit into `/var/www/hbos`.
  2. Run `composer install --no-dev --optimize-autoloader`.
  3. Configure `.env` with strict production settings:
     - `APP_ENV=production`
     - `APP_DEBUG=false`
     - `DB_CONNECTION=mysql`
  4. Run `php artisan migrate --force`.
  5. Run `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`.
  6. Deploy compiled frontend assets from `Frontend/dist` to web root.
  7. Configure SSL certificate (Let's Encrypt TLS 1.3).
- **Rollback Runbook:**
  - If post-deployment smoke test detects failure, restore prior release symlink `/var/www/hbos/current` and restore point-in-time database snapshot.

---

### DOC-16: Disaster Recovery, Database Backup & Restore Runbook
- **Target Audience:** Database Administrator
- **Automated Backup Strategy:**
  - Automated nightly `mysqldump` executed at 02:00 AM PKT with `--single-transaction --routines --triggers`.
  - Backup archives encrypted with AES-256 and replicated to off-site cloud storage.
- **Restore Drill Verification:**
  - Tested and verified on MariaDB 10.4.32: full schema dump restored into empty test database `hbos_restore_verify` with 100% record and ledger reconciliation.
- **Client Outbox Rule:**
  - Note: Offline transactions residing in client browser IndexedDB outbox are not captured in server database dumps until synchronized.

---

### DOC-17: Release Notes, Version Matrix & Known Limitations
- **Release Version:** v1.0.0-RC1
- **Program Status:** Phases 1–9 Frozen; Phase 10 Acceptance & Release Preparation Complete.
- **Component Matrix:**
  | Component | Version | Build Status |
  |---|---|---|
  | Backend API | Laravel 11.x / PHP 8.4 / MariaDB 10.4 | Verified (71 migrations, 0 errors) |
  | Web Frontend | Vue 3.5 / Vite 8.2.1 | Production Build PASS (`Frontend/dist`) |
  | Local DB Engine | IndexedDB v1 Multi-Tenant | Verified in Chromium Chrome/152 |
  | Windows Desktop Shell | Tauri v1 (`com.skyrasoft.hbos`) | Configured; Native build awaits Rust toolchain |
- **Known Limitations & Human Acceptance Dependencies:**
  1. *Physical Hardware:* Real on-counter receipt printing, physical barcode scanner wedge, and cash drawer kicking require physical retail counter validation (`PHYSICAL_UAT_PENDING`).
  2. *Human Acceptance:* Final business acceptance sign-off requires real human Store Owner and Cashier execution (`HUMAN_UAT_PENDING`).
  3. *Windows Code Signing:* Desktop installer requires official EV/OV code signing certificate to eliminate Windows SmartScreen warnings.
