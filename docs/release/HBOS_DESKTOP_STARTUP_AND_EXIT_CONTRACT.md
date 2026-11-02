# SKYRASOFT HBOS — DESKTOP STARTUP AND EXIT CONTRACT & RISK REGISTER

**Document Reference:** `docs/release/HBOS_DESKTOP_STARTUP_AND_EXIT_CONTRACT.md`  
**Stage:** Stage 1 — Checkpoint 1.1 (Complete Windows Shell and Startup Audit)  
**Execution Agent:** Antigravity (Advanced Agentic Coding)  
**Date:** October 24, 2026  
**Status:** IMPLEMENTED & AUDITED

---

## 1. INVESTIGATION & ROOT CAUSE OF THE SCREEN-TRAPPING INCIDENT

### 1.1 The Incident
During hands-on product owner testing of the installed Windows desktop application, the application occupied the entire physical display and provided no visible title bar, no minimize button, no maximize/restore button, and no close button. Standard desktop task switching was obscured, the Windows taskbar was hidden, and the user was unable to exit or minimize HBOS, forcing a physical laptop restart.

### 1.2 The Architectural Root Cause
Inspection of `desktop/tauri.conf.json` lines 70–80 revealed the exact configuration that caused this incident:

```json
// INCIDENT CAUSE: desktop/tauri.conf.json (Pre-remediation)
"windows": [
  {
    "title": "HBOS POS Counter",
    "width": 1280,
    "height": 800,
    "resizable": false,
    "fullscreen": true,
    "decorations": false,
    "alwaysOnTop": false
  }
]
```

#### Anatomical Flaws in Previous Configuration:
1. `"fullscreen": true`: Forces the Tauri WebView2 native window to claim the entire display hardware buffer, bypassing normal window boundaries and covering the Windows taskbar.
2. `"decorations": false`: Strips the standard Windows Win32 title bar frame (`WS_CAPTION`, `WS_THICKFRAME`, `WS_MINIMIZEBOX`, `WS_MAXIMIZEBOX`, `WS_SYSMENU`). Without window decorations, no standard Windows minimize, maximize, or close (X) buttons exist.
3. `"resizable": false`: Disables standard Windows edge-dragging, Aero Snap, and dual-monitor movement.
4. **Combined Effect:** A borderless, unmovable, non-resizable window that completely covered the Windows desktop without any exit controls. When paired with an unauthenticated startup route that lacked an in-app Exit button, the user was trapped.

---

## 2. DESKTOP STARTUP AND EXIT RISK REGISTER

| Risk ID | Severity | Root Vulnerability | Consequence | Implemented Solution | Verification Mechanism |
|---|---|---|---|---|---|
| **RSK-DESK-001** | CRITICAL (P0) | `fullscreen: true` & `decorations: false` in `tauri.conf.json` | User cannot minimize, resize, or close application, trapping screen and requiring machine reboot. | Set `fullscreen: false`, `decorations: true`, `resizable: true`, `minWidth: 1024`, `minHeight: 600`. Native title bar always present. | Visual inspection & `tauri.conf.json` schema validation |
| **RSK-DESK-002** | HIGH (P1) | Unhandled application exit with pending offline outbox in IndexedDB | If user exits while offline sales are queued, transactions could be silently dropped or partially written. | Added `exitApplication()` lifecycle in `desktopBridgeService.js` verifying `hbos_pending_sync_count`. Warns user before exit that offline transactions are preserved locally. | `desktopBridgeService.js` pending count guard unit tests |
| **RSK-DESK-003** | HIGH (P1) | Missing in-app Exit action on unauthenticated screens | If user cannot reach Windows title bar (e.g. tablet mode), no software command exists to quit. | Added visible "Exit Application" button on Welcome View, Login View, and Settings, calling native `exit_application` IPC. | `WelcomeView.vue` render & click handler tests |
| **RSK-DESK-004** | MEDIUM (P2) | Startup on 1366×768 laptop screens with high-DPI scaling | Dialog buttons and action cards pushed below the visible viewport fold. | Designed Welcome and Auth UX with flexible card layouts, max-height bounds, and clean responsive scrolling. Minimum target resolution: 1024×600. | Viewport responsive inspection |
| **RSK-DESK-005** | MEDIUM (P2) | Local backend unavailability on launch (XAMPP / API offline) | Blank white screen or infinite spinner if local or remote API is unreachable. | Welcome screen performs lightweight health-check ping to `/api/v1/health` and displays immediate status badge ("Connected" vs "Offline Standalone"). | Network status indicator test |
| **RSK-DESK-006** | LOW (P3) | Unhandled Alt+F4 or Escape in POS counter | Unintentional cashier keypress aborts checkout or terminates terminal. | Standard Alt+F4 closes the app cleanly via Windows OS; Escape clears modal/cart without quitting application. | Kiosk accelerator service review |

---

## 3. NON-NEGOTIABLE DESKTOP USABILITY CONTRACT

In accordance with Section A3 and Checkpoint 1.1, the HBOS Windows application guarantees the following native user behaviors at all times:

1. **Native Window Controls:**
   - Standard Windows Minimize button (`-`): Minimizes HBOS to taskbar.
   - Standard Windows Maximize/Restore button (`□`): Toggles between maximized desktop window and default 1366×768 window.
   - Standard Windows Close button (`✕`): Closes the application gracefully.
   - Standard Windows Edge Dragging: Window can be resized, snapped via Windows Snap (Win+Left/Right), and dragged across multiple monitors.
2. **Standard Keyboard Navigation:**
   - `Alt + Tab`: Seamlessly switches between HBOS and any other Windows application (e.g. Chrome, WhatsApp, Excel).
   - `Alt + F4`: Initiates standard Windows close sequence.
   - `Escape`: Dismisses open modals and cancel operations; never terminates the desktop shell.
3. **In-App Application Controls:**
   - Welcome View, Login View, and Main Header provide an explicit **Exit Application** button.
   - When running in Tauri native desktop mode, it invokes the Rust command `exit_application(window)`.
   - When running in a standard browser environment, it gracefully warns or navigates.
4. **Never Forced Kiosk:**
   - Forced kiosk mode is strictly forbidden.
   - An optional "Full-Screen Counter Mode" may be toggled voluntarily by the cashier via POS settings (`F11`), but is never enabled by default on application launch and can be exited immediately by pressing `F11` or `Escape`.

---

## 4. REMEDIATED TAURI WINDOW CONFIGURATION

File: `desktop/tauri.conf.json` (lines 70–82):

```json
"windows": [
  {
    "title": "SkyraSoft HBOS - Retail Operating System",
    "width": 1366,
    "height": 768,
    "minWidth": 1024,
    "minHeight": 600,
    "resizable": true,
    "fullscreen": false,
    "decorations": true,
    "alwaysOnTop": false,
    "center": true
  }
]
```

### Technical Validation:
- `fullscreen: false`: Prevents screen trapping.
- `decorations: true`: Restores Win32 native title bar, Minimize, Maximize, Close buttons.
- `resizable: true`: Restores user window resizing.
- `minWidth: 1024` / `minHeight: 600`: Prevents window collapse on low-resolution hardware.
- `center: true`: Centers the window on the primary monitor upon launch.

---

## 5. NATIVE TAURI IPC COMMAND EXTENSIONS

File: `desktop/src/main.rs`:

```rust
#[tauri::command]
fn exit_application(window: tauri::Window) {
    let _ = window.close();
}

#[tauri::command]
fn minimize_application(window: tauri::Window) {
    let _ = window.minimize();
}

#[tauri::command]
fn toggle_maximize(window: tauri::Window) {
    if let Ok(is_maximized) = window.is_maximized() {
        if is_maximized {
            let _ = window.unmaximize();
        } else {
            let _ = window.maximize();
        }
    }
}
```

Registered into `tauri::generate_handler![get_desktop_environment, exit_application, minimize_application, toggle_maximize]`.

---

## 6. OFFLINE OUTBOX & APP EXIT SAFETY CONTRACT

When the application is closed (via title bar X, Alt+F4, or in-app Exit button):
1. **Durable Local Storage:** All completed offline sales, pending sync batches, and Khata debt payments residing in IndexedDB (`HBOS_Store_Biz_${id}`) remain 100% durable on the local disk.
2. **Exit Check:** `desktopBridgeService.exitApplication()` checks `localStorage.getItem('hbos_pending_sync_count')`. If pending transactions exist, a clear advisory is displayed:
   > *"Notice: You have N offline transaction(s) pending sync. Your transactions are safely saved in local storage and will sync automatically when reconnected. Do you want to exit now?"*
3. **No Data Loss on Relaunch:** Upon relaunch, `SyncCoordinator.js` initializes, discovers the pending IndexedDB records, and resumes background sync as soon as network connectivity to the API is established.
