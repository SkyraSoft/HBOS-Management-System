# HBOS Phase 3: Role and Business Shape Matrix

## 1. Persona Definitions
- **Business Owner:** Full tenant authority. Customer display: `Business Super Owner`.
- **Branch Manager:** Operational authority over assigned branch. Customer display: `Branch Manager`.
- **Salesperson:** Frontline point-of-sale operator. Customer display: `Cashier / Salesperson`.
- **Accountant / Inventory Manager:** Technical compatibility roles under `POLICY-B1`. Preserved for legacy users; hidden from new staff creation.

---

## 2. Comprehensive Role × Business Shape Access Matrix

| Feature / Screen | Single-Store Owner | Multi-Branch Owner | Branch Manager | Cashier / Salesperson | Accountant | Inventory Manager |
|---|---|---|---|---|---|---|
| **Dashboard** | VISIBLE (Single-Store) | VISIBLE (Multi-Branch) | VISIBLE (Assigned Branch) | HIDDEN / FORBIDDEN | VISIBLE (Financial) | VISIBLE (Stock) |
| **Header Store Switcher** | HIDDEN | VISIBLE | HIDDEN | HIDDEN | HIDDEN | HIDDEN |
| **POS Checkout** | VISIBLE | VISIBLE | VISIBLE | VISIBLE (Default Landing) | VISIBLE | VISIBLE |
| **Sales History** | VISIBLE | VISIBLE | VISIBLE | VISIBLE (Own/Recent) | VISIBLE | VISIBLE |
| **Customer Lookup** | VISIBLE | VISIBLE | VISIBLE | VISIBLE | VISIBLE | VISIBLE |
| **Khata Management** | VISIBLE | VISIBLE | VISIBLE | VISIBLE (Permitted) | VISIBLE | VISIBLE |
| **Products Catalog** | VISIBLE | VISIBLE | VISIBLE | HIDDEN / FORBIDDEN | VISIBLE (Read) | VISIBLE (Manage) |
| **Purchases / Suppliers** | VISIBLE | VISIBLE | VISIBLE | HIDDEN / FORBIDDEN | VISIBLE | VISIBLE |
| **Inter-Branch Transfers**| HIDDEN_BUT_AUTHORIZED | VISIBLE | CONDITIONAL | HIDDEN / FORBIDDEN | HIDDEN | CONDITIONAL |
| **Expenses** | VISIBLE | VISIBLE | VISIBLE (Branch) | HIDDEN / FORBIDDEN | VISIBLE | HIDDEN |
| **Financial Accounts** | VISIBLE | VISIBLE | FORBIDDEN | FORBIDDEN | VISIBLE | FORBIDDEN |
| **Reports** | VISIBLE | VISIBLE | VISIBLE (Branch) | HIDDEN / FORBIDDEN | VISIBLE | VISIBLE (Stock) |
| **Store Management** | HIDDEN_BUT_AUTHORIZED | VISIBLE | FORBIDDEN | FORBIDDEN | FORBIDDEN | FORBIDDEN |
| **Staff & Roles** | VISIBLE (In Settings) | VISIBLE (In Settings) | FORBIDDEN | FORBIDDEN | FORBIDDEN | FORBIDDEN |
| **Audit Logs** | VISIBLE (In Settings) | VISIBLE (In Settings) | FORBIDDEN | FORBIDDEN | FORBIDDEN | FORBIDDEN |
| **Company Settings** | VISIBLE | VISIBLE | FORBIDDEN | FORBIDDEN | FORBIDDEN | FORBIDDEN |

---

## 3. Policy Guarantees
1. **Zero Device Kiosks as Human Roles:** Device is never treated as a role or user.
2. **Deterministic Role String:** Backend returns canonical role string (`Business Owner`, `Branch Manager`, `Salesperson`, `Accountant`, `Inventory Manager`).
3. **No Cross-Tenant Leaks:** Tenant scoping precedes all role and business-shape logic.
