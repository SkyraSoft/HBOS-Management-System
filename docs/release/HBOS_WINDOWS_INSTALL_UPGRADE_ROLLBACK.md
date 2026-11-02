# SKYRASOFT HBOS — WINDOWS INSTALLATION, UPGRADE AND ROLLBACK RUNBOOK
**Document Reference:** `docs/release/HBOS_WINDOWS_INSTALL_UPGRADE_ROLLBACK.md`  
**Stage:** Stage 3 — Standardized Demo System, Windows Release Candidate & Owner Acceptance  
**Checkpoint Covered:** Checkpoint 3.3  
**Status:** AUTHORITATIVE OPERATIONAL RUNBOOK

---

## 1. Executive Summary

This runbook defines the operational procedures for installing, upgrading, and rolling back the SkyraSoft HBOS Windows desktop application safely. Under the non-negotiable workstation safety principles:
1. The installation must never interfere with the user's active desktop, unrelated applications, or system security settings.
2. An upgrade must NEVER destroy or corrupt pending offline transactions stored locally in the durable outbox.
3. No automatic or unattended updates may be pushed to customer laptops without explicit authorization.

---

## 2. Windows Application Storage Architecture

The HBOS Windows application operates via a lightweight native Tauri/Rust shell embedding Microsoft Edge WebView2.

### 2.1 File System Paths
- **Application Executable (Per-User Scope):**
  `%LOCALAPPDATA%\Programs\HBOS POS\HBOS POS.exe`
- **Application Configuration & State:**
  `%APPDATA%\com.skyrasoft.hbos\`
- **WebView2 Profile, LocalStorage & Durable IndexedDB:**
  `%LOCALAPPDATA%\com.skyrasoft.hbos\EBWebView\`
  - Durable IndexedDB stores:
    - Real Tenant Cache: `HBOS_Store_Biz_${businessId}`
    - Real Tenant Pending Outbox: `HBOS_Store_Biz_${businessId}_outbox`
    - Single-Store Demo Sandbox: `HBOS_Store_Biz_demo_single`
    - Multi-Store Demo Sandbox: `HBOS_Store_Biz_demo_multi`

### 2.2 Critical Offline Work Preservation Invariant
> [!IMPORTANT]
> A server-side database backup contains ONLY transactions that have successfully reached the server. It does NOT contain sales currently held in a local counter's unsynced offline outbox.
>
> Therefore, before any uninstall, clean reinstall, or binary replacement, the operator MUST verify whether `%LOCALAPPDATA%\com.skyrasoft.hbos\EBWebView\` holds pending outbox transactions (`hbos_pending_sync_count > 0`).

---

## 3. Fresh Installation Procedure (Safe UAT Testing)

1. **System Prerequisites:**
   - Windows 10 (Version 1809+) or Windows 11 (64-bit).
   - Microsoft Edge WebView2 Runtime (pre-installed on modern Windows 10/11).
   - Minimum screen resolution: 1024×600 (Recommended: 1366×768 or higher).
2. **Installation Steps:**
   - Do NOT run MSI installer with elevated administrative rights unless prompted for machine-wide deployment.
   - Standard execution installs into user directory `%LOCALAPPDATA%\Programs\HBOS POS\`.
   - Desktop and Start Menu shortcuts are created under the user's personal profile.
3. **Verification on First Launch:**
   - Window opens in a floating, resizable window (Width: 1366px, Height: 768px).
   - Native minimize, maximize, and close controls are active in the title bar.
   - Initial screen is `WelcomeView` with exactly three choices: **Log In**, **Create My Business**, and **Try Demo**.
   - Zero real merchant data or random dashboards are rendered.

---

## 4. Safe Upgrade Procedure (Data-Preserving)

To upgrade an existing installation to a newer release candidate:

1. **Pre-Upgrade Outbox Audit:**
   - Launch existing HBOS application.
   - Inspect the Sync Status Badge in the top header.
   - Ensure `Pending Sync Count == 0`.
   - If pending offline transactions exist, restore internet connectivity or run manual sync (`POST /api/v1/sync/bulk-transactions`) until the queue is clear.
2. **Safe Staging:**
   - Download/stage the new binary candidate (`HBOS POS.exe` or MSI).
   - Close the running HBOS application cleanly using the in-app "Exit Application" button or the window close [X].
3. **Binary Replacement:**
   - Run the new installer or replace the candidate executable in the staging test folder.
   - Notice: WebView2 data folder (`%LOCALAPPDATA%\com.skyrasoft.hbos\EBWebView\`) is preserved untouched.
4. **Post-Upgrade Verification:**
   - Reopen application.
   - Verify previously authenticated sessions restore cleanly without data loss.
   - Verify that catalog products, customer balances, and historical invoices remain intact.

---

## 5. Rollback Procedure

If a new candidate build exhibits unexpected behavior or fails Windows UAT:

1. **Emergency Exit:**
   - Close application using `Alt+F4` or the title-bar [X].
   - If unresponsive, open Windows Task Manager (`Ctrl+Shift+Esc`) and terminate `HBOS POS.exe`.
2. **Restoring Previous Candidate:**
   - Re-run the installer of the previous stable build, or copy the previous executable back into the application directory.
   - Do NOT delete `%LOCALAPPDATA%\com.skyrasoft.hbos\EBWebView\` during rollback, as this preserves local merchant settings and outbox idempotency keys.
3. **Rollback Health Check:**
   - Relaunch previous binary.
   - Verify connection to the API backend (`/api/v1/health`).
   - Log in and confirm financial balances and inventory quantities reconcile.

---

## 6. Workstation Non-Interference Checklist

- [x] Application does NOT require Windows administrator privileges for standard operations.
- [x] Application does NOT install persistent background daemon services or startup hooks.
- [x] Application does NOT capture system mouse cursor, keyboard focus, or clipboard.
- [x] Window starts with `fullscreen: false`, `decorations: true`, `resizable: true`.
- [x] User can freely minimize, maximize, move, resize, and switch windows via `Alt+Tab`.
