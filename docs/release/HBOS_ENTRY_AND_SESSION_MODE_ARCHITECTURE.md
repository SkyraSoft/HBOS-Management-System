# SKYRASOFT HBOS — ENTRY AND SESSION MODE ARCHITECTURE
**Document Reference:** `docs/release/HBOS_ENTRY_AND_SESSION_MODE_ARCHITECTURE.md`  
**Stage:** Stage 1 — Safe Desktop Foundation, Entry Experience & Data-Mode Boundaries  
**Checkpoints Covered:** Checkpoint 1.2, Checkpoint 1.3  
**Status:** AUTHORITATIVE ARCHITECTURAL SPECIFICATION

---

## 1. Executive Summary

HBOS operates as a Windows-first business operating system for Pakistani independent retailers and expanding small multi-branch businesses. To guarantee user safety, privacy, and commercial trust, the application enforces strict boundaries between:
1. Unauthenticated visitors.
2. New business onboarding.
3. Authenticated real merchants.
4. Offline operational sessions.
5. Standardized guest demo sessions.

Under no circumstances may real merchant transactions leak into a demo session, nor may demo records contaminate a real business ledger or clean onboarding account.

---

## 2. Six Canonical Session Modes

| Mode Name | Identification Key | Primary State & Persona | Storage Partition / Cache Namespace | Backend Authority |
|---|---|---|---|---|
| **UNAUTHENTICATED** | `UNAUTHENTICATED` | Initial state on app startup. Renders WelcomeView with 3 primary entry choices. | `HBOS_Guest_Temp` (volatile memory only) | None (Public health check `/api/v1/ping` only) |
| **AUTHENTICATED_REAL_USER** | `AUTHENTICATED_REAL_USER` | Valid Sanctum Bearer token for registered tenant. Owner, Branch Manager, or Cashier. | `HBOS_Store_Biz_${businessId}` (IndexedDB & LocalStorage) | Full multi-tenant scoped Laravel API |
| **NEW_BUSINESS_ONBOARDING** | `NEW_BUSINESS_ONBOARDING` | Unregistered merchant completing the 5-step onboarding journey. | Memory form state; zero persistent mock records | `POST /api/v1/register` creates clean tenant |
| **DEMO_SINGLE_STORE** | `DEMO_SINGLE_STORE` | Prospective merchant exploring a single-store shop (Al-Madina General Store). | `HBOS_Store_Biz_demo_single` (Isolated IndexedDB) | Client-side sandbox or isolated guest tenant |
| **DEMO_MULTI_STORE** | `DEMO_MULTI_STORE` | Retailer exploring a 3-branch enterprise (Al-Madina Supermarket). | `HBOS_Store_Biz_demo_multi` (Isolated IndexedDB) | Client-side sandbox or isolated guest tenant |
| **VALIDATED_OFFLINE_REAL_SESSION** | `VALIDATED_OFFLINE_REAL_SESSION` | Authenticated real merchant operating POS without network connection. | `HBOS_Store_Biz_${businessId}_outbox` (Durable IndexedDB) | Pending outbox synced upon reconnect via `POST /api/v1/sync/bulk-transactions` |

---

## 3. Strict Mode Boundaries and Isolation Rules

### 3.1 Real Login Isolation
- **Rule:** Real login must NEVER load demo records, demo products, or fictitious financial KPIs.
- **Enforcement:** Upon successful authentication (`POST /api/v1/login`), the client purges `hbos_demo_mode` and binds exclusively to the authenticated tenant's ID (`user.business_id` or `user.businesses[active].id`).
- Local queries exclusively target IndexedDB store `HBOS_Store_Biz_${businessId}`.

### 3.2 Registration Cleanliness
- **Rule:** A newly registered business must start completely clean.
- **Enforcement:** The onboarding workflow creates:
  - Exactly 1 new Business tenant.
  - Exactly 1 Business Owner.
  - Exactly 1 Primary Main Store.
  - Exactly 1 default Main Cash account/drawer.
- **Zero-Tolerance Invariant:** Registration NEVER seeds sample sales, mock purchase history, fake suppliers, fictitious customer debts, or artificial cash movements. All dashboard KPIs truthfully initialize to 0.00.

### 3.3 Demo Sandbox Isolation
- **Rule:** Demo sessions must NEVER mutate real business records, nor submit synchronization payloads to real merchant endpoints.
- **Enforcement:**
  - Demo state is partitioned into isolated client storage namespaces: `HBOS_Store_Biz_demo_single` and `HBOS_Store_Biz_demo_multi`.
  - Demo requests include a header/mode flag `X-HBOS-Demo-Mode: true`.
  - Offline sync engine is blocked from pushing demo transactions to live merchant endpoints.
  - A demo reset action only wipes and re-seeds that visitor's local or guest sandbox.

### 3.4 Same-Device User Handover (Shared Terminal Security)
- **Rule:** When an Owner logs out and a Cashier or Manager logs in on the same physical Windows device, no tokens, cost prices, profit metrics, or private cached data may leak across users.
- **Enforcement:**
  - `logout()` in `auth.js` and `sessionMode.js` invalidates the active bearer token (`POST /api/v1/logout`).
  - Clears `localStorage` sensitive keys: `hbos_token`, `hbos_user`, `hbos_business`, `hbos_roles`, `hbos_permissions`.
  - Resets all in-memory Pinia store state (`useAuthStore`, `useRetailStore`, `useSessionModeStore`).
  - Correctly owned pending offline transactions in `HBOS_Store_Biz_${businessId}_outbox` are sealed with the authoring user's ID and are never exposed or editable by the next logged-in user.

---

## 4. Startup Resolution Flowchart

```
                          [App Launched]
                                |
                   [Check Local Storage & Network]
                                |
       +------------------------+------------------------+
       |                                                 |
[No Token & No Demo]                            [Stored Token Found]
       |                                                 |
       v                                                 v
  Mode: UNAUTHENTICATED                          [Validate Token / Cache]
       |                                                 |
  Render WelcomeView                               +-----+-----+
(Log In / Register / Demo)                         |           |
                                               [Valid]     [Expired/401]
                                                   |           |
                                                   v           v
                                           Mode: AUTHENTICATED  Purge Token
                                                   |           Show Expired Notice
                                           Render Dashboard/POS Redirect to WelcomeView
```

### Detailed Resolution States:
1. **First Installation:** No local credentials found $\rightarrow$ Renders `WelcomeView` with 3 choices.
2. **No Valid Session:** Prior session logged out $\rightarrow$ Renders `WelcomeView`.
3. **Valid Real Session:** Token verified against local cache or `/api/v1/me` $\rightarrow$ Presents option to "Continue as [User]" or automatically transitions to Default Landing (Dashboard for Owner/Manager, POS for Cashier).
4. **Expired Real Session:** Server returns 401 $\rightarrow$ Client purges invalid credentials, alerts user that session expired, and routes safely to `WelcomeView`.
5. **Offline Previously Authenticated Real User:** Network unreachable, but valid cached session exists $\rightarrow$ Enters `VALIDATED_OFFLINE_REAL_SESSION`, unlocks POS counter with cached catalog, and queues sales in local durable outbox.
6. **Guest Demo Session:** Visitor previously selected Demo $\rightarrow$ Restores local sandbox state until user clicks "Reset Demo" or "Exit Demo".

---

## 5. Endpoint and Backend Infrastructure Strategy

The Windows desktop client communicates with the Laravel API following a hybrid-ready architecture:

1. **Development Runtime:**
   - Frontend: Vite dev server or compiled Tauri WebView.
   - API Endpoint: `http://localhost:8000` or `http://127.0.0.1:8000`.
2. **Staged UAT Environment:**
   - Windows desktop package connects to an isolated staging API or a packaged local headless service.
   - Configured via `VITE_API_BASE_URL` at compile/packaging time.
3. **Customer Production Runtime:**
   - Windows application targets the secure, hosted SkyraSoft cloud service (`https://api.hbos.skyrasoft.com/api/v1`) for multi-device sync, central catalog, and remote access.
   - Local durable IndexedDB ensures continuous sales operation during local internet outages.
   - Packaging invariant: Production builds NEVER ship hardcoded developer XAMPP localhost URLs.

---

## 6. Verification and Acceptance Gate

- [x] WelcomeView renders exactly 3 primary entry choices.
- [x] Unauthenticated state displays zero business data.
- [x] Client storage namespaces are separated by tenant ID.
- [x] Demo mode is explicitly labeled and cannot call real merchant sync routes.
- [x] Session expiry cleanly redirects to Welcome without crashing or trapping window.
