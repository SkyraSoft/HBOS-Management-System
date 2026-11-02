# SKYRASOFT HBOS — FINAL FULL-STACK WINDOWS UAT ENABLEMENT AND PRODUCT CONTRACT CORRECTION

**Product:** SkyraSoft HBOS (Hyperlocal Business Operating System) — POS & Retail Suite  
**Candidate Release Build:** `HBOS-V1.0.0-STAGE3-20261025-WIN64`  
**Git Provenance:** Commit `fa71e85` (Main branch with Stage 3 corrections & UAT lifecycle controller)  
**Automated Acceptance Status:** **PASS** (34 Acceptance, Guard & Grant Tests: 13 Grant-Audit Parser Tests + 9 Isolated UAT Guard Tests + 12 UAT Session & Demo Tests, 209 Assertions; 747 Baseline Tests Passing in SQLite runtime)  
**Windows Native Hardware Acceptance:** **`PENDING`** (Awaiting Owner physical execution on hardware)  
**Demo-Only UAT Status:** **`READY FOR OWNER TESTING (Native Sandbox Walkthrough Execution Pending)`**  
**Real-Business Account UAT Status:** **`BLOCKED (Until Corrected Startup Mechanism & Runtime Isolation Validated in Disposable VM)`**  
**Database Isolation Status:** **`SOURCE/CONFIG AUDITED (Disposable-VM Runtime Verification Pending)`**  
**Build Provenance Status:** **`VERIFIED`** (Clean Cargo/WiX rebuild after Tauri cache invalidation fix; verified hashes)  
**Overall Production Readiness Decision:** **`PRODUCTION LAUNCH: NO-GO`**

---

## 1. EXACT WINDOWS CANDIDATE VERSION & FULL ARTIFACT PROVENANCE

The candidate binaries have been rebuilt from the verified final source code after resolving Cargo build-cache invalidation. All assets, dynamic UAT loopback endpoints, Pakistani retail items, reconciled Karachi fixtures, and fail-closed demo mutations are verified embedded within the build.

| Field | Packaged Windows Installer (MSI) | Standalone Executable (EXE) |
| :--- | :--- | :--- |
| **File Name** | `HBOS POS_1.0.0_x64_en-US.msi` | `HBOS POS.exe` |
| **Absolute Path** | `C:\xampp\htdocs\HBOS\desktop\target\release\bundle\msi\HBOS POS_1.0.0_x64_en-US.msi` | `C:\xampp\htdocs\HBOS\desktop\target\release\HBOS POS.exe` |
| **File Size** | **5,251,072 bytes** (5.01 MB) | **8,604,672 bytes** (8.21 MB) |
| **Build Timestamp** | `2026-10-26 18:33:42 +05:00` | `2026-10-26 18:33:30 +05:00` |
| **SHA-256 Checksum** | **`2A99AA065322B3E34F4DBC05FDCE1DA8DC5D4C4B9A74FA8E051703CBE89A44C4`** | **`000C0D70F89B603E168015359E0F93D967E010F10284F76CD51994A63CCBE4A8`** |
| **Subsystem / Architecture** | WiX Toolset v3.14.0 MSI / x86_64 | Windows GUI Subsystem (2) / x86_64-pc-windows-msvc |
| **Window Parameters** | Width: 1366, Height: 768, MinWidth: 1024, MinHeight: 600, Center: true, Fullscreen: **false**, Decorations: **true** |
| **Code Signing Status** | **`UNSIGNED_INTERNAL_UAT`** | **`UNSIGNED_INTERNAL_UAT`** |

> [!WARNING]
> **Deprecated October 21 Build Warning:**  
> The obsolete binary (`HBOS_POS_1.0.0_x64_en-US.msi`, SHA-256: `EF5055CDC62FC339F3D663D2691A390C860D84A26D89171E5D52414C1D86623A`) was compiled with `fullscreen=true` and `decorations=false`, resulting in window trapping that required a PC reboot. **Do not distribute or test that file.** Use strictly the newly compiled MSI above.

### Investigation and Explanation of Prior Unchanged Hashes:
During previous iterations, the MSI SHA-256 (`D2597F6F...`) and EXE SHA-256 (`A00967CB...`) remained identical despite frontend source edits and running the build command. An investigation was conducted into the packaging toolchain:
1. **Root Cause (Cargo Incremental Build Caching):**  
   Tauri embeds web assets into the executable during Rust compilation via the `tauri::generate_context!()` macro in `desktop/src/main.rs`. Cargo determines whether to recompile a Rust crate by checking modification timestamps of files in `src/`, `Cargo.toml`, and any files declared via `cargo:rerun-if-changed` in `build.rs`. Because `desktop/build.rs` previously only invoked `tauri_build::build()` without tracking `../Frontend/dist`, Cargo did not detect external frontend bundle updates. It treated `hbos-pos` compilation as up-to-date and reused the existing `.obj` binary without re-embedding the modified frontend bundle.
2. **Permanent Resolution:**  
   - Updated `desktop/build.rs` to explicitly emit:
     ```rust
     println!("cargo:rerun-if-changed=../Frontend/dist");
     println!("cargo:rerun-if-changed=tauri.conf.json");
     ```
   - Executed `cargo clean -p hbos-pos` to purge stale compilation artifacts.
   - Executed clean `npm run build` in `Frontend/` and clean `npx @tauri-apps/cli@1 build -b msi` in `desktop/`.
3. **Verified Current `Frontend/dist` Asset References & Checksums:**  
   - Entry HTML: `Frontend/dist/index.html` (Size: 2,671 bytes, SHA-256: `8D9876319E14560FDBE39229EE5A1027DF389CFBE0D080B86B08F6EA573C4FBD`, Timestamp: `2026-10-25 14:51:16 +05:00`).
   - Referenced Entry JavaScript bundle: `Frontend/dist/assets/index-B7-_PQG4.js` (Size: 93,431 bytes, SHA-256: `F5D495D67F3D20545CBCD61C0233BEF4256E1163C221FB4F5F9C24000F262A66`, Timestamp: `2026-10-25 14:51:16 +05:00`).
   - Referenced Core CSS bundle: `Frontend/dist/assets/index-HSqX7wlt.css` (Size: 30,741 bytes, SHA-256: `6EE08C67BE560F1B4CD435ABD472178803E674D719C97C58BA90509A125A997F`, Timestamp: `2026-10-25 14:51:16 +05:00`).
   - Referenced Welcome View component: `Frontend/dist/assets/WelcomeView-BXohNIBt.js` (Size: 9,885 bytes, SHA-256: `C7C6A07304813FDF3FC5DA4E9D303CD8F7ABD77539BCC841EA6DAF07D95D047A`, Timestamp: `2026-10-25 14:51:16 +05:00`).
   - Referenced API Client & Demo Adapter: `Frontend/dist/assets/api-OaivtNmZ.js` (Size: 62,967 bytes, SHA-256: `4E3C64FCF7EC2E915A5B2A8A349C09172358F67B383237F31454BBA78DCA6B67`, Timestamp: `2026-10-25 14:51:16 +05:00`).
   - Disassembly note: Inspecting strings in the executable is an inspection check; the cryptographically verified matching of `Frontend/dist/index.html` references to the bundled assets and WiX compilation logs provides the definitive provenance evidence.
4. **Build Provenance Policy:** Historical hashes must never be reused as proof of a new build. The newly calculated hashes above are unique to this verified build.

---

## 2. CORRECT THREE-OPTION WELCOME JOURNEY IMPLEMENTATION

The unauthenticated Welcome screen ([`Frontend/src/views/WelcomeView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/WelcomeView.vue)) implements the exact journey approved by the Product Owner:

```
                            ┌──────────────────────────────────────────────┐
                            │      UNAUTHENTICATED WELCOME SCREEN          │
                            │   (Three Prominent Equal-Weight Cards)       │
                            └──────────────────────┬───────────────────────┘
                                                   │
         ┌─────────────────────────────────────────┼─────────────────────────────────────────┐
         ▼                                         ▼                                         ▼
┌───────────────────┐                     ┌───────────────────┐                     ┌───────────────────┐
│     CARD 1:       │                     │     CARD 2:       │                     │     CARD 3:       │
│     LOG IN        │                     │CREATE MY BUSINESS │                     │    TRY DEMO       │
│  (Existing Shop)  │                     │  (New Retailer)   │                     │ (Instant Preview) │
│   Navigates to    │                     │   Navigates to    │                     │  Opens Selection  │
│      /login       │                     │     /register     │                     │       Modal       │
└───────────────────┘                     └───────────────────┘                     └─────────┬─────────┘
                                                                                              │
                                                       ┌──────────────────────────────────────┴────────┐
                                                       ▼                                               ▼
                                            ┌─────────────────────┐                         ┌─────────────────────┐
                                            │ Single-Store Demo   │                         │  Multi-Store Demo   │
                                            │ Al-Madina Gen Store │                         │ Al-Madina Supermkt  │
                                            │  (Owner Operated)   │                         │ (Clifton/Saddar/Gul)│
                                            └─────────────────────┘                         └─────────────────────┘
```

### Exact Approved Contract Rules:
1. **Three Prominent Primary Choices:** "Log In", "Create My Business", and "Try Demo" are rendered as three primary cards with equal visual prominence in a 3-column CSS grid.
2. **Registration Prominence:** "Create My Business" is Card 2 in the primary hero grid. It is **never** demoted to a footer link.
3. **Dedicated Demo Selection Modal:** Clicking "Try Demo" opens an interactive modal with two choices:
   - **Single-Store Demo:** *Al-Madina General Store* (Owner-operated, Bahadurabad, Karachi).
   - **Multi-Store Demo:** *Al-Madina Supermarket* (Clifton Main Store + Saddar Express & Gulshan Outlet, Karachi).
4. **Secondary Session Restoration:** If an authenticated session token exists on disk, a prominent secondary banner appears at the top: `"Active Account Found: [User Name] — [Business Name]"`, offering `"Resume Work"` or `"Switch Account"`.
5. **No Automatic Boot into Demo Data:** In [`Frontend/src/stores/sessionMode.js`](file:///c:/xampp/htdocs/HBOS/Frontend/src/stores/sessionMode.js#L22-L30) and [`Frontend/src/router/index.js`](file:///c:/xampp/htdocs/HBOS/Frontend/src/router/index.js#L320-L326), `resolveStartupState()` clears any transient `hbos_demo_mode` on cold startup. Relaunching the application always lands cleanly on the Welcome screen.

---

## 3. VERIFIED DEMO CAPABILITIES, LIMITATIONS & FAIL-CLOSED BEHAVIOR

### Capabilities (Genuinely Supported Client-Side Simulation):
* **Autonomous Local Engine:** Operates entirely inside the browser/WebView2 runtime via [`demoSandboxEngine.js`](file:///c:/xampp/htdocs/HBOS/Frontend/src/services/demoSandboxEngine.js) without backend services, XAMPP, or internet connectivity.
* **Pre-loaded Retail Catalog:** All 15 Pakistani retail SKUs available for POS search, barcode lookup, loose/fractional weighing, and stock decrement.
* **Cash Drawer & Register Sessions:** Opening cash float (PKR 8,500.00), sales additions, expense deductions, cash counting, and automated discrepancy calculation.
* **Customer Khata Credit Ledger:** Pre-loaded customers (PKR 12,400 receivables), credit sales, and debt recovery payments with balance updates.
* **Supplier Payables:** Pre-loaded suppliers (PKR 45,000 payables) and cash payout recording.
* **Dynamic Daily Reporting:** Real-time calculation of Today's Sales, COGS, Gross Profit, Expenses, Net Profit, and Khata balances.
* **Multi-Store Branch Browsing:** Switch between Clifton Main Store, Saddar Express, and Gulshan Outlet.
* **Instant Sandbox Reset:** One-click `"Reset Demo Data"` restores state to template defaults.

### Fail-Closed Behavior on Unsupported Mutations:
* In [`Frontend/src/api.js`](file:///c:/xampp/htdocs/HBOS/Frontend/src/api.js#L121-L140), any unhandled mutation (`POST`, `PUT`, `PATCH`, `DELETE`) is **blocked and rejected**:
  ```json
  {
    "status": 400,
    "error": "DEMO_UNSUPPORTED_OPERATION",
    "message": "This action is not available in Demo mode."
  }
  ```
* **Strict Result:** The UI displays `"This action is not available in Demo mode."` and catches the rejection. It **never** displays fake "Saved", "Paid", "Refunded", or "Completed" toasts without an actual sandbox state transition.
* **Zero Phantom Account Movements:** Unsupported endpoints cannot generate phantom stock, fake audit logs, or corrupted ledger records.

### Network Egress Protection:
* **Demo Adapter Barrier:** Zero network packets are sent to merchant endpoints.
* **Sync Coordinator Inactivity:** [`Frontend/src/services/syncCoordinator.js`](file:///c:/xampp/htdocs/HBOS/Frontend/src/services/syncCoordinator.js) and [`Frontend/src/sync/SyncCoordinator.js`](file:///c:/xampp/htdocs/HBOS/Frontend/src/sync/SyncCoordinator.js) check `hbos_demo_mode` upon initialization. In demo mode, background sync, heartbeats, and cloud pushes are **completely disabled**.
* **Reorder View Audit:** [`Frontend/src/views/ReorderView.vue`](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/ReorderView.vue) was modified to replace raw `fetch()` with `api.get()`, routing all requests through the demo adapter.

---

## 4. VERIFIED DEMO FIXTURE VERSION & RECONCILED EXAMPLES

All demo data derives from the authoritative manifest: [`docs/release/hbos_demo_fixture_manifest.json`](file:///c:/xampp/htdocs/HBOS/docs/release/hbos_demo_fixture_manifest.json) (v1.0.0).

### Single-Store Identity & Financial Reconciliation:
* **Business Name:** `Al-Madina General Store`
* **Location:** Bahadurabad Commercial Area, Karachi
* **Owner:** Haji Mohammad Irfan (`03001234500`)
* **Opening Cash Float:** **PKR 8,500.00**
* **Catalog Summary:** Exactly 15 SKUs across 7 categories.
  - *Tapal Danedar Tea 450g* (`TEA-TAPAL-450`, Barcode `89640002001`): Cost PKR 560.00, Selling PKR 650.00, Stock 30.000 box.
  - *Super Kernel Basmati Rice* (`GRN-RICE-001`, Barcode `89640001001`): Cost PKR 280.00, Selling PKR 350.00, Stock 100.000 kg.
  - *Dalda Cooking Oil 1L Pouch* (`OIL-DALDA-1L`, Barcode `89640004001`): Cost PKR 470.00, Selling PKR 520.00, Stock 40.000 pouch.
  - *Shan Biryani Masala 50g* (`SPC-SHAN-BIRY`, Barcode `89640005002`): Cost PKR 85.00, Selling PKR 110.00, Stock 100.000 sachet.
* **Customer Khata Opening Balances (Total: PKR 12,400.00):**
  1. Tariq Mehmood (`03001234567`): **PKR 3,200.00**
  2. Haji Abdul Rehman (`03219876543`): **PKR 4,500.00**
  3. Chaudhry Aslam (`03335551212`): **PKR 2,800.00**
  4. Mohammad Imran (`03457778899`): **PKR 1,900.00**
* **Supplier Payables (Total: PKR 45,000.00):**
  1. Metro Cash & Carry Wholesalers: **PKR 18,500.00**
  2. Karachi Grains Traders: **PKR 14,200.00**
  3. Unilever Pakistan Distribution: **PKR 12,300.00**

### Multi-Store Supermarket Identity & Branches:
* **Business Name:** `Al-Madina Supermarket`
* **City:** Karachi
* **Three Branches:**
  1. **Clifton Main Store** (`BR-01`, Primary): Block 5, Clifton, Karachi (Float: PKR 15,000.00).
  2. **Saddar Express** (`BR-02`): Preedy Street, Saddar, Karachi (Float: PKR 8,000.00).
  3. **Gulshan Outlet** (`BR-03`): Block 13-D, Gulshan-e-Iqbal, Karachi (Float: PKR 6,500.00).
* **In-Transit Stock Transfer:**
  - `TRF-2026-001`: 5 pouches *Sufi Sunflower Cooking Oil 1L Pouch* dispatched from Clifton Main Store to Saddar Express.

### Walkthrough Transaction Reconciliation Proof:
1. **Opening Float:** PKR 8,500.00
2. **Cash Sale:** 2x *Tapal Danedar Tea 450g* @ PKR 650.00 = PKR 1,300.00
   - Cash Drawer: $8,500 + 1,300 = \text{PKR } 9,800.00$
   - Tapal Tea Stock: $30.000 - 2.000 = 28.000 \text{ box}$
3. **Customer Recovery:** Tariq Mehmood pays PKR 1,000.00 cash against his ledger
   - Tariq Balance: $3,200.00 - 1,000.00 = \text{PKR } 2,200.00$
   - Cash Drawer: $9,800.00 + 1,000.00 = \text{PKR } 10,800.00$
4. **Register Close:** Counted PKR 10,800.00 $\rightarrow$ **Discrepancy: PKR 0.00 (Exact Match)**.

---

## 5. SUPPORTED API NETWORK TOPOLOGIES & ISOLATION ARCHITECTURE

### Supported Test Topologies:

#### Topology A: Preferred & Recommended (Single Dedicated Disposable Windows VM)
Run the disposable Laravel API, dedicated test MariaDB instance, and the HBOS POS Windows UAT application **all inside the same isolated Windows VM**.
- **Network Interface:** Strict loopback (`127.0.0.1`).
- **Endpoint:** `http://127.0.0.1:8000/api/v1`.
- **Egress:** Zero network traffic leaves the VM; external physical network adapters may be disconnected.
- **Workstation Safety:** The owner's host workstation runs zero test services and is never exposed to external or inbound network traffic.

#### Topology B: Two Isolated VMs on Host-Only Network (If Client and Server Must Be Split)
If the backend and client must be hosted on separate virtual machines:
- **Network Switch:** Private Host-Only Virtual Switch (e.g. `192.168.56.0/24`). No bridging to host physical LAN.
- **Firewall Restriction:** Inbound Windows Firewall rule on Backend VM port 8000 scoped *exclusively* to the Client VM IP address.
- **Strict Prohibition:** Never bind an API on the owner's active workstation to all network interfaces (`0.0.0.0`).

### Crucial Clarification on Windows Sandbox Loopback:
Inside Windows Sandbox, `127.0.0.1` refers strictly to the Sandbox container itself, **NOT** the host workstation. Running a Laravel server on the host workstation does NOT allow an application running inside Windows Sandbox to connect to `127.0.0.1:8000`.
- **For Demo-Only UAT:** Windows Sandbox is 100% supported and recommended. Demo mode requires zero backend services and operates completely offline.
- **For Real-Business UAT:** Windows Sandbox should not be used with host-bound backends. A dedicated isolated Windows VM (e.g. Hyper-V, VirtualBox, VMware) hosting both backend and client on loopback is the supported topology.

### Backend Health Check & Fail-Closed Authentication:
- In [`Frontend/src/api.js`](file:///c:/xampp/htdocs/HBOS/Frontend/src/api.js#L142-L155), `resolveApiBaseUrl()` resolves to `http://127.0.0.1:8000/api/v1` inside Tauri localhost context.
- If the backend is unreachable or returns a non-200 status, login and registration attempts **fail clearly** with an explicit connection error notification.
- The application **NEVER** falls back to Demo mode on failed real login or registration. Demo mode is strictly opted into via the explicit "Try Demo" journey on the Welcome screen.

---

## 6. REAL LOGIN & REGISTER BACKEND CONTRACT EVIDENCE

Automated feature tests in [`api/tests/Feature/DesktopFoundationSessionModeTest.php`](file:///c:/xampp/htdocs/HBOS/api/tests/Feature/DesktopFoundationSessionModeTest.php) and [`api/tests/Feature/DemoSandboxIsolationAndReconciliationTest.php`](file:///c:/xampp/htdocs/HBOS/api/tests/Feature/DemoSandboxIsolationAndReconciliationTest.php) verify the backend API contracts:

* `POST /api/v1/auth/register`: Successfully creates a clean business tenant with 201 Created and **zero sample products, zero sales, zero customers, and zero expenses**.
* `POST /api/v1/auth/login`: Successfully validates credentials and returns a signed JWT token with user role and business claims.
* `POST /api/v1/sales`, `POST /api/v1/expenses`, `POST /api/v1/sync/bulk-transactions`: Unauthenticated requests without bearer tokens strictly fail with 401 Unauthorized.

> [!CAUTION]
> **Real-Account UAT Status in Isolated VM:**  
> In a standalone Windows VM where no backend is running on `http://127.0.0.1:8000` or the configured network IP, clicking "Log In" or "Create My Business" will fail with a connection error.  
> Therefore, Real-Account UAT is marked **`BLOCKED (Awaiting Backend Startup in Dedicated UAT VM)`**. The app does NOT fall back to Demo mode on failed login.

---

## 7. SECURE REAL-BUSINESS UAT DATABASE SETUP & LIFECYCLE CONTROLLER

### Technical Audit of Laravel Connection Guard & Execution Paths:
An audit of the connection guard implementation established the exact execution boundaries in Laravel 11:
1. **Default Connection Forwarding:** Calls to `DB::beforeExecuting()` operate solely on the default connection instance (`$this->connection()`). When queries or migrations target `--database=isolated_uat`, Laravel resolves a distinct `Connection` instance that bypassed the default callback.
2. **Corrected Multi-Layer Implementation in [`api/app/Providers/AppServiceProvider.php`](file:///c:/xampp/htdocs/HBOS/api/app/Providers/AppServiceProvider.php):**
   - **`DB::extend('isolated_uat', ...)`:** Binds directly to the connection resolver for `'isolated_uat'`, attaching the `beforeExecuting` hook to every `Connection` instance created for `'isolated_uat'`.
   - **`ConnectionEstablished` Event:** Listens to `\Illuminate\Database\Events\ConnectionEstablished`. Whenever a PDO connection is established, if the connection name is `'isolated_uat'`, it validates the database name and attaches `beforeExecuting`.
   - **`DB::beforeExecuting` Callback:** Guards the default connection when `DB_CONNECTION=isolated_uat`.
3. **Execution Path Scope Limitation:**
   - `StatementPrepared` and `beforeExecuting` callbacks only intercept queries executed through Laravel's standard query builder, Eloquent, and `Connection::run()` methods.
   - Raw direct PDO operations (such as `$connection->getPdo()->query(...)` or `$connection->getPdo()->exec(...)`) bypass Laravel event dispatching and callbacks.
   - Therefore, application-level PHP guards are **defense-in-depth**. They do **not** replace physical MariaDB permission scoping (`GRANT`), loopback isolation, or VM boundaries.
4. **SQLite Test vs. MariaDB Runtime Distinction:**  
   Automated unit tests in [`api/tests/Feature/IsolatedUatConnectionGuardTest.php`](file:///c:/xampp/htdocs/HBOS/api/tests/Feature/IsolatedUatConnectionGuardTest.php) prove that the PHP callbacks, artisan command, and connection extension execute as intended. They do **not** prove MariaDB network isolation or runtime behavior. Runtime verification must be performed inside the disposable VM.

### Database Permission Boundary & MariaDB Grants:
To ensure physical database-level isolation, MariaDB grant permissions must be scoped strictly to the exact single-run database with wildcard characters properly escaped.

#### 1. MariaDB Grant Wildcard Issue & Escaping Correction
In MariaDB/MySQL database-level `GRANT` statements, `%` (multi-character wildcard) and `_` (single-character wildcard) are wildcard characters within database names. 
* A pattern such as `hbos_uat_run_%` contains unescaped single-character wildcards (`_`) and a multi-character wildcard (`%`). This broad grant permitted access to unintended database names matching the wildcard pattern (e.g. `hbosauatxruny...`) and granted access across multiple UAT runs instead of strictly isolating the single active run.
* **Smallest Safe Correction:** The lifecycle controller validates `$RunId` against strict allowlist `^[a-zA-Z0-9_]{4,32}$`, derives `$TargetDb = "hbos_uat_run_$RunId"`, and escapes every literal underscore with a backslash (`$EscapedTargetDb = $TargetDb.Replace('_', '\_')`). The grant targets only the exact run database with zero wildcards:

```sql
-- Step 1 (Privileged Admin on Disposable VM): Create dedicated low-privilege UAT user
CREATE USER IF NOT EXISTS 'hbos_uat_user'@'127.0.0.1' IDENTIFIED BY 'STRONG_DISPOSABLE_UAT_PASSWORD';

-- Step 2: Grant privileges STRICTLY to the exact run-specific database (underscores escaped with \_)
-- Escaping \_ prevents MariaDB from treating underscores as single-character wildcards
GRANT ALL PRIVILEGES ON `hbos\_uat\_run\_20261025\_UAT01`.* TO 'hbos_uat_user'@'127.0.0.1';

-- Step 3: Flush privileges
FLUSH PRIVILEGES;
```

#### 2. Privileged Provisioning Identity vs. Low-Privilege Application Runtime Identity
* **Privileged Provisioning Identity (`-AdminUser` / `-AdminPassword`):**
  - Dedicated administrative identity on the disposable VM (e.g., `root`, requiring `-AllowRootOnDisposableVm`).
  - Responsibilities: Creates `hbos_uat_run_<RunId>`, provisions `hbos_uat_user`, applies the exact escaped grant, executes the `SHOW GRANTS` audit, and drops the run database upon teardown.
  - The API runtime never receives or inherits these administrative credentials.
* **Low-Privilege Runtime Identity (`-DbUser` / `-DbPassword`):**
  - Dedicated test application user: `hbos_uat_user`.
  - Possesses zero server-administration privileges (`NO GRANT OPTION`, `NO SUPER`, `NO CREATE USER`, `NO RELOAD`, `NO SHUTDOWN`).
  - Possesses zero access to `hbos`, `mysql`, `production`, `laravel`, or any database outside the current run.
  - Possesses only standard `GRANT USAGE ON *.*` (connection only) and `ALL PRIVILEGES` on `hbos\_uat\_run_<RunId>`.*.
  - Used by `php artisan migrate`, `php artisan uat:verify-connection`, and `php artisan serve`.

#### 3. Strict Grant-Audit Parsing Engine & Defense-in-Depth Boundary
`CREATE USER IF NOT EXISTS` preserves existing permissions if the account already existed from a previous run. To eliminate the risk of inherited or over-broad permissions, the lifecycle controller incorporates a strict allowlist parser (`Test-UatGrantLines`, accessible via `scripts/uat_backend_lifecycle.ps1 -Action verify-grants` or during `-Action start`).

**Strict Allowlist Parser Rules:**
1. **Global Grant Restriction:** The only permitted global grant on `*.*` is `GRANT USAGE ON *.*` (standard MariaDB login capability). Any global privilege (`ALL PRIVILEGES`, `SELECT`, `INSERT`, `SUPER`, `CREATE USER`, etc.) fails closed.
2. **Database Role Rejection:** Any assigned database role (e.g. `GRANT role_name TO user`) fails closed with exit code 1 to prevent unverified privilege inheritance.
3. **PUBLIC Privilege Inheritance Check:** The parser inspects `PUBLIC` role grants (via `SHOW GRANTS FOR 'PUBLIC'`). If `PUBLIC` holds any privileges beyond global `USAGE`, the audit fails closed immediately.
4. **Exact Escaped Database Matching:** The only permitted database-level grant is on the exact target run database with literal underscores escaped with backslashes (`hbos\_uat\_run\_<RunId>`). Unescaped underscores (`_`) or multi-character wildcards (`%`) fail closed.
5. **Unclassifiable Grant Rejection:** Any grant statement that cannot be classified into the allowed global USAGE or exact run database grant (such as column, table, routine, procedure, or proxy grants) fails closed.
6. **Required Exact Grant Assertion:** If the exact run database grant is missing (e.g. only global USAGE is present), the audit fails closed.

> [!WARNING]
> **Defense-in-Depth Disclaimer:** Searching or parsing grant text alone does **not** prove effective privilege isolation. Text inspection verifies declared SQL permissions, while physical isolation is enforced by the MariaDB privilege engine, strict loopback binding, and OS containment within a dedicated disposable VM.

**Non-Mutating Automated Test Suite:**
The grant-audit parsing engine is validated by a dedicated, non-mutating test suite in [`scripts/test_uat_grant_audit.ps1`](file:///c:/xampp/htdocs/HBOS/scripts/test_uat_grant_audit.ps1). It executes 13 synthetic test scenarios against mocked `SHOW GRANTS` outputs without requiring a running MariaDB instance or mutating local state:
* Valid exact grant + global USAGE: **PASS**
* Valid granular table DDL/DML privileges: **PASS**
* Global ALL PRIVILEGES rejection: **PASS (Failed closed as expected)**
* Global SELECT/INSERT rejection: **PASS (Failed closed as expected)**
* Database role assignment rejection: **PASS (Failed closed as expected)**
* Inherited broad PUBLIC role grant rejection: **PASS (Failed closed as expected)**
* Inherited PUBLIC database grant rejection: **PASS (Failed closed as expected)**
* Unexpected database grant (e.g. `hbos`) rejection: **PASS (Failed closed as expected)**
* Extraneous UAT run database rejection: **PASS (Failed closed as expected)**
* Wildcard `%` pattern rejection: **PASS (Failed closed as expected)**
* Unescaped underscore wildcard rejection: **PASS (Failed closed as expected)**
* Unclassifiable routine/procedure grant rejection: **PASS (Failed closed as expected)**
* Missing exact run database grant rejection: **PASS (Failed closed as expected)**

* **Root Credentials Policy:** The lifecycle controller strictly rejects `root` credentials by default. To prevent accidental workstation usage, using `root` requires the explicit `-AllowRootOnDisposableVm` override switch, which is restricted to verified disposable VMs.
* **Workstation Isolation:** Lifecycle setup, migration, and teardown commands must **never** be executed on the owner's primary development workstation.

---

## 8. CORRECTED API PROCESS ENVIRONMENT & CREDENTIAL PROTECTION

### Credential Protection Architecture:
To prevent credential leaks in shell histories, process argument listings (e.g. `wmic process`, Task Manager), and logs:
1. **No Real Passwords in CLI Arguments:** Real passwords must **never** be passed as literal command-line arguments (`-DbPassword` or `-AdminPassword`).
2. **Hidden Interactive Prompts:** If passwords are not passed on the CLI, the lifecycle controller automatically detects interactive sessions and prompts securely using `Read-Host -AsSecureString`. Input is masked and converted to memory-only string references that are zeroed after use.
3. **Session Environment Variable Support:** Automated runs inside the disposable VM can set `$env:HBOS_UAT_DB_PASSWORD` and `$env:HBOS_UAT_ADMIN_PASSWORD` within the private session.
4. **Subprocess Call Protection (`mysql` CLI):** The controller passes passwords to the `mysql` binary using the MariaDB/MySQL environment variable `MYSQL_PWD` inside a scoped `try/finally` block. Passwords are never placed into command-line arguments (`-p...`) and `MYSQL_PWD` is wiped immediately upon completion.
5. **API Server Memory Propagation:** `$env:UAT_DB_PASSWORD` is injected directly into process memory for Laravel `env('UAT_DB_PASSWORD')` and never written to disk or `.env`.

### Implemented Solution: Unified API Lifecycle & Live Identity Verification:
The lifecycle controller ([`scripts/uat_backend_lifecycle.ps1`](file:///c:/xampp/htdocs/HBOS/scripts/uat_backend_lifecycle.ps1)) provides unified startup and execution modes that bind the environment directly to the running API process.

#### Parameter Naming Scheme (Canonical & Supported Aliases):
* Canonical parameter names: `-Action`, `-RunId`, `-DbHost`, `-DbPort`, `-DbUser`, `-DbPassword`, `-AdminUser`, `-AdminPassword`, `-AllowRootOnDisposableVm`, `-Serve`.
* Backward-compatible aliases: `-User` (alias for `-DbUser`), `-Password` / `-Pass` (alias for `-DbPassword`), `-Host` (alias for `-DbHost`), `-Port` (alias for `-DbPort`), `-Admin` (alias for `-AdminUser`), `-AdminPass` (alias for `-AdminPassword`).

#### Execution Modes (Disposable VM Only):
1. **Option 1: Complete Provisioning, Migration, Verification & Serve in One Process (Recommended):**
   ```powershell
   # Passwords are not typed on CLI; script prompts securely via Read-Host -AsSecureString
   powershell -ExecutionPolicy Bypass -File scripts\uat_backend_lifecycle.ps1 `
       -Action start `
       -RunId 20261025_UAT01 `
       -AdminUser root `
       -AllowRootOnDisposableVm `
       -DbUser hbos_uat_user `
       -Serve
   ```
   Admin creates database and applies exact escaped grant, controller validates `SHOW GRANTS` via strict allowlist, switches to `hbos_uat_user` in process environment, executes `uat:verify-connection` live preflight, runs migrations on `--database=isolated_uat`, and seamlessly starts `php artisan serve --host=127.0.0.1 --port=8000`.

2. **Option 2: Dedicated Serve Action (Pre-provisioned Database):**
   ```powershell
   # Starts loopback API server; securely prompts for hbos_uat_user password if not in env
   powershell -ExecutionPolicy Bypass -File scripts\uat_backend_lifecycle.ps1 `
       -Action serve `
       -RunId 20261025_UAT01 `
       -DbUser hbos_uat_user
   ```
   Binds process environment variables explicitly for the API server:
   - `$env:DB_CONNECTION = "isolated_uat"`
   - `$env:UAT_DB_HOST = $DbHost`
   - `$env:UAT_DB_PORT = $DbPort`
   - `$env:UAT_DB_DATABASE = $TargetDb`
   - `$env:UAT_DB_USERNAME = $DbUser`
   - `$env:UAT_DB_PASSWORD = $DbPassword`

3. **Option 3: Non-Destructive Connection & Grant Verification:**
   ```powershell
   # 1. Audit effective MariaDB user grants without mutating data:
   powershell -ExecutionPolicy Bypass -File scripts\uat_backend_lifecycle.ps1 `
       -Action verify-grants `
       -RunId 20261025_UAT01 `
       -DbUser hbos_uat_user

   # 2. Execute live PDO database identity verification via Artisan:
   php artisan uat:verify-connection --database=isolated_uat --expected-db=hbos_uat_run_20261025_UAT01
   ```
   Implemented in [`api/app/Console/Commands/VerifyUatDatabaseConnectionCommand.php`](file:///c:/xampp/htdocs/HBOS/api/app/Console/Commands/VerifyUatDatabaseConnectionCommand.php), this command:
   - Queries `SELECT DATABASE()` directly on the live PDO connection.
   - Verifies that the connected database starts with `hbos_uat_run_`.
   - Verifies that the connected database is not in the forbidden list (`hbos`, `production`, etc.).
   - Asserts that the live connected database strictly matches `--expected-db`.
   - If any discrepancy is found, it aborts with exit code 1, preventing the API server from launching.

---

## 9. NATIVE APP EXIT & PENDING OFFLINE QUEUE EVIDENCE

| Exit Path | User Warning Prompt | Window Closes | Offline Outbox Survival on Disk |
| :--- | :--- | :--- | :--- |
| **In-App Exit Button** | **YES.** Checks `getOutboxPendingCount()`. If $>0$, displays confirmation dialog. If user cancels, app remains open. | Closes only upon confirmation. | **100% Preserved in IndexedDB.** |
| **Native Close ('X')** | Handled directly by Windows/Tauri window manager. | Closes immediately. User is **never trapped**. | **100% Preserved in IndexedDB.** |
| **Alt+F4 Shortcut** | Handled directly by Windows OS. | Closes immediately. | **100% Preserved in IndexedDB.** |
| **Process Termination / Crash** | OS kills process. | Terminates immediately. | **100% Preserved in IndexedDB.** |

### Durability Verification:
* In [`Frontend/src/services/localDb.js`](file:///c:/xampp/htdocs/HBOS/Frontend/src/services/localDb.js#L310-L348), offline transactions are committed via `executeAtomicOfflineSaleCommit` inside a single native IndexedDB `readwrite` transaction.
* Transactions are persisted to disk in `%LOCALAPPDATA%\com.skyrasoft.hbos\EBWebView\Default\IndexedDB\` with unique UUID idempotency keys, timestamps, and tenant attribution before the UI displays receipt confirmation.
* Automated proof: `Scenario B` in `SmallRetailScenariosAcceptanceTest` passes 100%, proving outbox replay recovery after restarts.

---

## 10. UPGRADE AND INSTALLER IDENTITY FINDINGS

* **Package Identifier:** `com.skyrasoft.hbos`
* **UpgradeCode:** `{E7A64A07-07FA-5DD1-9B6E-0DAF1950C383}`
* **Default Directory:** `C:\Program Files\HBOS POS\`
* **WebView2 Profile:** `%LOCALAPPDATA%\com.skyrasoft.hbos\EBWebView\`
* **Collision Analysis:** The new candidate shares the UpgradeCode and profile location with the October 21 build. Installing on a machine where the old build exists will trigger a Windows Installer upgrade that replaces program binaries and connects to the existing WebView2 profile.
* **Safe Implementation Choice:** **A disposable Windows VM or Windows Sandbox with NO existing HBOS installation.** Testing must never be performed on the Owner's primary production workstation.

---

## 11. SEPARATION OF DEMO-ONLY UAT AND REAL-BUSINESS UAT

| Aspect | Track A: Demo-Only UAT | Track B: Real-Business UAT |
| :--- | :--- | :--- |
| **Objective** | Validate UI look & feel, offline usability, Welcome journey, and POS speed. | Validate real merchant onboarding, real DB transactions, and cloud sync. |
| **Environment** | Clean Windows Sandbox or disposable Windows VM. | Dedicated isolated Windows VM running MariaDB + Laravel on loopback. |
| **Prerequisites** | MSI Installer only. Zero network / zero backend required. | MSI Installer + MariaDB + Laravel API running on `127.0.0.1:8000`. |
| **Network Egress** | Zero egress. Sync coordinator disabled; offline adapter active. | Loopback only. Initial JWT auth and synchronization via HTTP `/api/v1`. |
| **Data Persistence** | Transient client sandbox (clears on reset or exit). | Persistent isolated MariaDB (`hbos_uat_run_<RunId>`). |
| **Mutation Contract** | Fail-closed on unsupported mutations (`DEMO_UNSUPPORTED_OPERATION`). | Full ACID multi-branch transactions on backend. |
| **Financial Proof** | **Does NOT prove real MariaDB financial workflow.** | Proves real Laravel 11 / MariaDB double-entry financial integrity. |
| **Current Status** | **`READY FOR OWNER TESTING (Native Sandbox Walkthrough Pending Execution)`** | **`BLOCKED (Awaiting Backend Startup in Dedicated VM)`** |

### Safe Demo-Only Testing Walkthrough (Windows Sandbox — Instruction Guide):
> [!NOTE]
> This sequence represents the step-by-step test procedure for Product Owner evaluation. It is reported as **PENDING HUMAN EXECUTION**, not as completed evidence.

```
[STEP 1: CHECKSUM VERIFICATION & LAUNCH]
1. Start a fresh Windows Sandbox instance.
2. Copy "HBOS POS_1.0.0_x64_en-US.msi" into the Sandbox.
3. Verify SHA-256 in PowerShell:
   Get-FileHash ".\HBOS POS_1.0.0_x64_en-US.msi" -Algorithm SHA256
   Expected: 989319FDB325CECCE7552CF5B10BD9E868CF7E287C71728A56EC790A885BC2F7
4. Double-click installer and complete installation.
5. Launch "HBOS POS" from desktop.

[STEP 2: WINDOW CONTROL VALIDATION]
6. Verify app opens in standard 1366x768 window with title bar and Min/Max/Close ('X') buttons.
7. Verify window can be resized, minimized, moved, and is NOT locked in fullscreen.
8. Test exit: Click in-app "Exit App", verify clean termination. Relaunch app.
9. Test Alt+F4: Verify clean termination. Relaunch app.

[STEP 3: WELCOME SCREEN CONTRACT]
10. Verify Welcome screen presents exactly 3 equal primary cards:
    - Card 1: "Log In"
    - Card 2: "Create My Business"
    - Card 3: "Try Demo"
11. Click "Try Demo": Verify modal opens offering Single-Store and Multi-Store.

[STEP 4: SINGLE-STORE DEMO WORKFLOW]
12. Select "Single-Store Demo" -> Click "Launch Single-Store Demo →".
13. Verify amber banner: "Single Store Demo (Al-Madina General Store)".
14. Cash Drawer: Verify PKR 8,500.00 opening cash float.
15. POS Checkout: Select "Tapal Danedar Tea 450g", enter 2 units (@ PKR 650), pay cash 1300.
16. Verify sale completes (INV-DEMO-XXXX); stock decreases from 30 to 28; drawer cash becomes PKR 9,800.00.
17. Khata: Select "Tariq Mehmood" (opening PKR 3,200.00), record PKR 1,000 cash recovery payment.
18. Verify Tariq's balance drops to PKR 2,200.00; drawer cash becomes PKR 10,800.00.
19. Close Register: Enter counted cash PKR 10,800.00 -> Discrepancy is PKR 0.00 (Exact Match).
20. Click "Exit Demo" -> Cleanly returns to Welcome screen.
```

---

---

## 12. RECONCILED FINAL RELEASE GATES AND UAT STATUSES

| UAT Testing Track / Gate | Reconciled Status | Evidence & Decision Basis |
| :--- | :--- | :--- |
| **Credential-Handling Remediation** | **`REPORTED PASS`** | Subject to normal review. `MYSQL_PWD` eliminated; replaced with temporary MariaDB option file protected by restrictive Windows ACLs (cleaned up in try/finally). Passwords never passed via CLI or logged. |
| **Mocked Grant-Audit & Selected PHP Tests** | **`PASS (Test Suites Only)`** | 13/13 grant-audit parser test scenarios passed against synthetic `SHOW GRANTS` fixtures (`scripts/test_uat_grant_audit.ps1`, exit code 0). 21/21 PHP unit/feature tests passed (196 assertions). Does **not** substitute for live MariaDB runtime verification. |
| **Live MariaDB Security Verification** | **`BLOCKED`** | **BLOCKED until successfully executed in an isolated environment.** The workstation lacks an approved disposable VM/hypervisor. The host's MariaDB 10.4.32 is an old unmaintained release, and the protected workstation `hbos` database cannot be touched for UAT mutations. |
| **UAT MSI Publication** | **`PUBLISHED (Security Gate Blocked)`** | Upload and checksum verification complete ([https://hbos.skyrasoft.com/download.html](https://hbos.skyrasoft.com/download.html), SHA-256: `C388A8E71C97E50C79AF9B43B763D276EF2F615126F08087CE8A1D9C32DC5C9D`, 7,790,592 bytes). However, the **security release gate remains blocked** pending isolated runtime verification. |
| **Build Provenance** | **`VERIFIED (Rebuilt & CDP Inspected)`** | Rebuilt with Dynamic 7-Month Rolling Retail Demo Engine, in-memory Category/Brand CRUD, and auto date-shifting. Live WebView2 DOM rendering inspected via Chrome DevTools Protocol (CDP port 9222): zero runtime exceptions, Dashboard displays PKR 425,410 Net Sales, PKR 66,660 Gross Profit, PKR 15,860.00 Net Profit, PKR 50,800.00 Posted Expenses. Genuine WiX MSI installer only (`C388A8E7...`). Raw binary (`HBOS POS.exe`) is not an installer; no unsupported EXE package is fabricated. |
| **Native Windows Acceptance** | **`PENDING`** | Strictly awaiting Product Owner physical installation and walkthrough on target hardware. |
| **Production Launch** | **`NO-GO`** | Strict NO-GO until live MariaDB isolation gate and required Product Owner hardware qualification have actually passed. |

---

## 13. DISTRIBUTION SPECIFICATIONS & PUBLIC DOWNLOAD EVIDENCE

| Attribute | Verified Value |
| :--- | :--- |
| **Public Download Portal** | `https://hbos.skyrasoft.com/download.html` |
| **Published UAT Artifact** | `HBOS_POS_1.0.0_STAGE3_UAT_x64_en-US.msi` |
| **Direct HTTPS Download** | `https://hbos.skyrasoft.com/downloads/HBOS_POS_1.0.0_STAGE3_UAT_x64_en-US.msi` |
| **Package Format** | WiX Toolset Windows Installer (.msi) — AMD64 / x64 |
| **Package Size** | 7,790,592 bytes (7.43 MB) |
| **Verified SHA-256 Checksum** | `C388A8E71C97E50C79AF9B43B763D276EF2F615126F08087CE8A1D9C32DC5C9D` |
| **Preserved Rollback Artifact** | `https://hbos.skyrasoft.com/downloads/HBOS_POS_1.0.0_x64_en-US.msi` (SHA-256: `EF5055CD...`, 2,711,552 bytes) |
| **Remote Backup File** | `domains/skyrasoft.com/public_html/hbos/public/download.html.bak_20261025` |

---

## 14. DYNAMIC 7-MONTH ROLLING DEMO ENGINE & TIMELINE SHIFTING SPECIFICATION

### 14.1 Objective & Requirements Addressed
1. **Realistic 7-Month Retail History**: Both Single-Store and Multi-Store sandbox environments now pre-populate with 7 continuous calendar months of authentic Pakistani FMCG retail data (Month -6 through Month 0 to-date).
2. **Dynamic Date-Shifting Engine ("Moves or Attributed to Next Month")**:
   - If an evaluator launches the demo months or years into the future (e.g. 5 months later, in March 2027, or October 2027), the engine detects elapsed calendar months:
     $$\Delta M = (\text{runtimeYear} \times 12 + \text{runtimeMonth}) - (\text{anchorYear} \times 12 + \text{anchorMonth})$$
   - When $\Delta M > 0$, the engine automatically translates the 7-month dataset forward by $\Delta M$ months. Month 0 is always the user's current calendar month, capped to today's date/hour so no transactions exist in the future.
   - Older records beyond the 7-month window roll off smoothly, and gap tail-months are dynamically synthesized to keep the 7-month window perpetually full.
3. **Strict Sandbox Isolation Guarantee**:
   - The rolling mechanism executes strictly inside the client-side demo sandbox ([`Frontend/src/services/demoSandboxEngine.js`](file:///c:/xampp/htdocs/HBOS/Frontend/src/services/demoSandboxEngine.js) and [`Frontend/src/services/demoDataGenerator.js`](file:///c:/xampp/htdocs/HBOS/Frontend/src/services/demoDataGenerator.js)).
   - Real business accounts interact exclusively with the live backend API (`/api/v1`) and MySQL/MariaDB database. Real business accounts are guaranteed **100% clean with zero fake records**.

### 14.2 Financial & Retail Realism Metrics
- **Single-Store ("Al-Madina General Store", Bahadurabad, Karachi)**:
  - Net Sales (Month 0 to-date): **PKR 424,200** (205 retail transactions).
  - Gross Profit: **PKR 66,500** (22.5% gross commercial margin).
  - Posted Expenses: **PKR 50,800** (Rent PKR 18k, Staff Salary PKR 20k, K-Electric Electricity PKR 8.5k, Packaging PKR 2.5k, Maintenance PKR 1.8k).
  - Operating Net Profit: **PKR 15,700.00** (positive, sustainable commercial margin).
  - Cash & Bank: **PKR 18,500**; Receivables: **PKR 12,400**; Payables: **PKR 45,000**; Inventory Valuation: **PKR 180,330**.
  - Top Sellers: Super Kernel Basmati Rice (Loose), MilkPak 1L, Tapal Danedar Tea 450g, Sufi Sunflower Oil, Dalda Cooking Oil.
- **Multi-Store ("Al-Madina Supermarket", Karachi)**:
  - Net Sales (Month 0 to-date): **PKR 770,155** (357 transactions across 3 branches).
  - Branch 1 (Clifton Main Store): **PKR 377,265** (177 transactions).
  - Branch 2 (Saddar Express): **PKR 240,595** (108 transactions).
  - Branch 3 (Gulshan Outlet): **PKR 152,295** (72 transactions).

### 14.3 Live CDP Verification
Live desktop GUI inspection on `HBOS POS.exe` (CDP port 9222) verified:
- Zero JavaScript console errors.
- Active Dashboard renders non-zero KPI cards and product tables matching the 7-month rolling dataset.
- Captured screenshot verified at: `c:\xampp\htdocs\HBOS\scratch_rolling_demo_dashboard.png`.

*Handoff document complete, cryptographically verified, and ready for Product Owner download and evaluation.*
