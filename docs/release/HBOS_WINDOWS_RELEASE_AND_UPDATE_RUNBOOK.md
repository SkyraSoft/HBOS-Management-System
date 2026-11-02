# SKYRASOFT HBOS WINDOWS RELEASE & UPDATE RUNBOOK
## AUTHORITATIVE OPERATIONAL GUIDE FOR BUILDING, PACKAGING, AND DISTRIBUTING CLIENT UPDATES

---

### 1. Build & Release Architecture

The distributed Windows desktop application is compiled from the unified frontend codebase using Vite and the Tauri desktop shell.

```
+---------------------+      npm run build       +----------------------+
|   Vue 3 Source      | -----------------------> |    dist/ (Web SPA)   |
+---------------------+                          +----------------------+
                                                            |
                                                 cargo tauri build (MSIX)
                                                            |
                                                            v
                                                 +----------------------+
                                                 |  HBOS_1.0.0.0_x64.   |
                                                 |        msix          |
                                                 +----------------------+
```

---

### 2. Step-by-Step Production Release Workflow

#### Step 1: Set App Version in Source Authority
Update semantic version across core version files:
1. `api/config/hbos_version.php` (`latest_client_version => '1.0.0'`, `min_supported_client_version => '1.0.0'`)
2. `frontend/package.json` (`"version": "1.0.0"`)
3. `frontend/src/api.js` (`export const APP_VERSION = '1.0.0';`)

#### Step 2: Compile Frontend Production Assets
```bash
cd c:\xampp\htdocs\HBOS\frontend
npm run build
```

#### Step 3: Package Desktop MSIX Installer
```bash
npm run build:desktop
# Generates target/release/bundle/msix/SkyraSoft_HBOS_1.0.0_x64.msix
```

#### Step 4: Validate Package with Windows App Certification Kit (WACK)
```powershell
appcert.exe test -appxpackagepath ".\SkyraSoft_HBOS_1.0.0_x64.msix" -reportoutputpath ".\wack_report.xml"
```

#### Step 5: Upload to Microsoft Partner Center
1. Log in to [Microsoft Partner Center](https://partner.microsoft.com/dashboard).
2. Select **SkyraSoft HBOS Management System** $\rightarrow$ **Packages**.
3. Upload the certified `.msix` bundle.
4. Submit for automated Store ingestion.

---

### 3. Store Updates vs. Backend Version Handshake Gating

```
+-------------------------------------------------------------------------------+
|                             VERSION GATING MATRIX                             |
+---------------------+-------------------+-------------------+-----------------+
| Installed Client    | Min Supported Ver | Backend Action    | Client Behavior |
+---------------------+-------------------+-------------------+-----------------+
| v1.2.0              | v1.0.0            | HTTP 200 OK       | Normal POS/Sync |
| v1.0.0 (Older)      | v1.0.0            | HTTP 200 OK + Hdr | Update Banner   |
| v0.9.0 (Outdated)   | v1.0.0            | HTTP 426 Required | Locks Sync / UI |
+---------------------+-------------------+-------------------+-----------------+
```

#### Operational Rule for Deprecating Old Versions:
When a breaking change is released in the cloud API:
1. Deploy new backend with `min_supported_client_version => '1.1.0'` in `api/config/hbos_version.php`.
2. Existing clients running `<= 1.0.0` receive `HTTP 426 Upgrade Required` on their next API request, prompting the user to update via the Microsoft Store.

---

### 4. Zero Data Loss Offline Update Guarantees

1. **IndexedDB Durability**: Local database directories (`HBOS_Store_Biz_${business_id}`) reside in `%LOCALAPPDATA%\SkyraSoft.HBOS\EBWebView\` and are **never deleted** during application binary updates.
2. **Pending Outbox Work**: Unsynced sales and offline customer payments remain queued in the local outbox store and automatically replay once the application is restarted post-update.
