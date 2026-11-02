# HBOS Phase 3: Adaptive UX Architecture Specification

## 1. Executive Summary & Principles
The HBOS Small Retail Transformation Program dictates a fundamental architectural rule:
> **One Domain + Role / Business-Shape Variants**

Rather than building separate codebases, apps, or duplicated views for single-store retailers, multi-branch chains, managers, and cashiers, the single HBOS frontend adapts dynamically through reactive presentation state.

### Core Principles
1. **Presentation Adapts, Identity Persists:** Business shape (`SINGLE_STORE` vs `MULTI_BRANCH`) is purely client presentation state derived from active branch counts. It never alters tenant identity or deletes historical data.
2. **Progressive Disclosure:** Features irrelevant to a single-store merchant (branch switchers, transfers, comparison matrices) remain hidden until a second active branch exists.
3. **Defense-in-Depth:** UI hiding is matched with route guard redirects and backend authorization gates.
4. **Zero Magic Database IDs & Canonical All-Stores Encoding:** The "All Stores" aggregate scope is canonically represented via `branch_id=all` on the frontend (with backend compatibility preserving omitted `branch_id`), never fake records with `branch_id=0`.
5. **Stateful Session Store Safety:** `navigationStore` is classified as `STATEFUL_SESSION_CONTEXT_RESET_REQUIRED`. It owns mutable in-memory branch records and UI scope with zero browser localStorage persistence, resetting via `navigationStore.reset()` during logout to preserve Phase-2 session isolation.

---

## 2. Business Shape Determination
- **Source of Truth:** Response from `GET /api/v1/branches`.
- **Classification Algorithm:**
  ```javascript
  const activeBranches = branches.filter(b => b.status === 'active');
  const activeBranchCount = activeBranches.length;
  const isSingleStore = activeBranchCount === 1;
  const isMultiBranch = activeBranchCount >= 2;
  ```
- **Loading & Error Safety:**
  - Before branch data resolves or if API returns an error, the system defaults to `SINGLE_STORE` mode (least-privilege).
  - Inactive, draft, or archived branches are strictly excluded from the count.
- **Reversibility:**
  - If a second branch is deactivated, the UI instantly reverts to `SINGLE_STORE` mode. Historical records and transfers remain untouched in the database.

---

## 3. Four Target Persona Experiences

### A. Single-Store Owner
- **Scope:** 1 active Main Store (`is_primary = true`), 0 required employees.
- **Experience:** Clean, focused retail shop management.
- **Navigation:** POS, Sales, Products, Inventory, Purchases, Suppliers, Customers, Khata, Expenses, Financial Accounts, Reports, Settings.
- **Hidden:** Header store selector, Inter-Branch Transfers nav item, Stores/Branches nav item, Dashboard branch comparison table.

### B. Multi-Branch Owner
- **Scope:** $\ge 2$ active branches.
- **Experience:** Centralized chain control and cross-branch visibility.
- **Revealed:** Header store switcher (with "All Stores" aggregate option), Inter-Branch Transfers nav item, Stores management nav item, Dashboard branch comparison cards.

### C. Branch Manager
- **Scope:** Assigned branch workspace only.
- **Experience:** Day-to-day store operations without cross-branch leakage.
- **Hidden / Forbidden:** Header store switcher (fixed to assigned branch), All Stores scope, Central Treasury/Financial Accounts, Business Settings, Staff governance.

### D. Cashier / Salesperson
- **Scope:** POS-first operational terminal.
- **Default Landing:** Directly to `/pos`.
- **Experience:** Fast checkout, permitted sales history lookup, customer receipt search.
- **Hidden / Forbidden:** Executive Dashboard, Cost/Profit metrics, Inventory costing, Supplier payables, Reports, Settings.

---

## 4. Architectural State Machine
```
[User Authenticated]
        │
        ├──> Determine Canonical Role (authStore.user.role)
        │
        ├──> Fetch Branches (GET /api/v1/branches)
        │       │
        │       ├──> Count Active: filter(status === 'active')
        │       │
        │       ├──> Count == 1 ────> SINGLE_STORE_MODE
        │       │                      ├── Hide Header Store Selector
        │       │                      ├── Hide Transfers Nav
        │       │                      └── Hide Dashboard Comparison
        │       │
        │       └──> Count >= 2 ────> MULTI_BRANCH_MODE
        │                              ├── Reveal Header Store Selector
        │                              ├── Reveal Transfers Nav (Owner)
        │                              └── Reveal Dashboard Comparison
        │
        └──> Calculate Visible Navigation & Default Landing
                ├── Salesperson ───> Default Landing: /pos
                └── Owner/Manager ──> Default Landing: /dashboard
```
