# HBOS Phase 3: Navigation Contract Specification

## 1. Scope & Authority
This specification defines the navigation structure, visibility rules, route guards, and landing contracts for the HBOS Small Retail Transformation.

---

## 2. Navigation Inventory & Grouping

| Nav Item | Target Route | Target Group | Single-Store Owner | Multi-Branch Owner | Branch Manager | Cashier / Salesperson | Compatibility Roles |
|---|---|---|---|---|---|---|---|
| **Dashboard** | `/dashboard` | Core | VISIBLE | VISIBLE | VISIBLE (Assigned Branch) | HIDDEN / FORBIDDEN | VISIBLE (Permitted) |
| **POS** | `/pos` | Operations | VISIBLE | VISIBLE | VISIBLE | VISIBLE (Default Landing) | VISIBLE |
| **Sales** | `/sales` | Operations | VISIBLE | VISIBLE | VISIBLE | VISIBLE (Read/Own) | VISIBLE |
| **Customers** | `/customers` | Operations | VISIBLE | VISIBLE | VISIBLE | VISIBLE (Lookup) | VISIBLE |
| **Khata** | `/khata` | Operations | VISIBLE | VISIBLE | VISIBLE | VISIBLE (Allowed) | VISIBLE |
| **Products** | `/products` | Catalog | VISIBLE | VISIBLE | VISIBLE | HIDDEN / FORBIDDEN | VISIBLE (Inv Mgr) |
| **Purchases** | `/purchases` | Supply | VISIBLE | VISIBLE | VISIBLE | HIDDEN / FORBIDDEN | VISIBLE (Inv Mgr) |
| **Transfers** | `/transfers` (Unrouted) | Supply | **HIDDEN** | **HIDDEN (Phase 7)** | HIDDEN | HIDDEN | HIDDEN |
| **Expenses** | `/expenses` | Finance | VISIBLE | VISIBLE | VISIBLE (Branch) | HIDDEN / FORBIDDEN | VISIBLE (Accountant) |
| **Financial Accounts** | `/accounts` | Finance | VISIBLE | VISIBLE | HIDDEN / FORBIDDEN | HIDDEN / FORBIDDEN | VISIBLE (Accountant) |
| **Reports** | `/reports` | Analytics | VISIBLE | VISIBLE | VISIBLE (Branch) | HIDDEN / FORBIDDEN | VISIBLE (Accountant) |
| **Stores** | `/branches` | Organization | **HIDDEN** | **VISIBLE** | HIDDEN / FORBIDDEN | HIDDEN / FORBIDDEN | HIDDEN |
| **Settings** | `/settings` | Administration | VISIBLE | VISIBLE | HIDDEN / FORBIDDEN | HIDDEN / FORBIDDEN | HIDDEN |

---

## 3. Route Guard & Landing Behavior

### A. Default Landing Rules
- **Business Owner:** `/dashboard`
- **Branch Manager:** `/dashboard` (Scoped to assigned branch)
- **Cashier / Salesperson:** `/pos` (Direct POS terminal landing)
- **Compatibility Roles:** `/dashboard` or first authorized module

### B. Hidden vs Forbidden Policy
1. **HIDDEN_BUT_AUTHORIZED:** In Single-Store mode, `/branches` is hidden from the sidebar to eliminate clutter. If a Single-Store Owner directly navigates to `/branches`, the router permits direct access (`DIRECT_ACCESS_ALLOWED`). Transfers (`/transfers`) remains unrouted and hidden until Phase 7.
2. **FORBIDDEN:** If a Cashier or Manager navigates directly to an unauthorized route (e.g. Cashier navigating to `/settings` or `/reports`), the router guard intercepts the request and redirects them to their default landing (`/pos` for Cashier, `/dashboard` for Manager).

---

## 4. Multi-Surface Synchronization
MainLayout maintains identical navigation rules across all UI surfaces:
1. **Desktop Sidebar:** Displays `navigationStore.visibleNavItems`.
2. **Mobile Drawer:** Displays the identical `navigationStore.visibleNavItems`.
3. **Header Topbar:** Branch switcher rendered if and only if `navigationStore.isBranchSelectorVisible` is true (Owner with $\ge 2$ active branches).
