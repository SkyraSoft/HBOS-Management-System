# HBOS Role & Permission Target Architecture
**SkyraSoft HBOS Retail Platform & Workforce Integrity Program**

---

## 1. Canonical Role Hierarchy

```text
Platform Security Domain
└── Platform Super Admin (SkyraSoft internal; zero automatic tenant data browsing)

Customer Tenant Security Domain
└── Business Super Owner (Root tenant admin; consolidated + branch view; View-As preview)
    ├── Branch Manager (Operational leader for assigned store/branch)
    │   └── Salesperson / Cashier (Front-counter POS sales & scanning)
    ├── Accountant (Tenant-wide financial ledgers, Khata, payables, expenses, bank accounts)
    └── Inventory Manager (Catalog, stock adjustments, branch transfers, GRN receiving)
```

---

## 2. Canonical Permissions by Role

| Permission Name | Super Owner | Branch Manager | Salesperson / Cashier | Accountant | Inventory Manager |
| :--- | :---: | :---: | :---: | :---: | :---: |
| `manage business settings` |  |  |  |  |  |
| `manage branches` |  |  |  |  |  |
| `manage users` |  |  |  |  |  |
| `view full reports` |  |  |  |  |  |
| `manage branch settings` |  |  |  |  |  |
| `manage local inventory` |  |  |  |  |  |
| `view branch reports` |  |  |  |  |  |
| `view sales` |  |  |  (Own only)|  |  |
| `create sales` |  |  |  |  |  |
| `manage sales` |  |  |  |  |  |
| `return sales` |  |  |  |  |  |
| `override sale price` |  |  |  |  |  |
| `view products` |  |  |  |  |  |
| `manage products` |  |  |  |  |  |
| `view categories` |  |  |  |  |  |
| `manage categories` |  |  |  |  |  |
| `view brands` |  |  |  |  |  |
| `manage brands` |  |  |  |  |  |
| `view inventory` |  |  |  |  |  |
| `adjust inventory` |  |  |  |  |  |
| `transfer inventory` |  |  |  |  |  |
| `view stock movements` |  |  |  |  |  |
| `view suppliers` |  |  |  |  |  |
| `manage suppliers` |  |  |  |  |  |
| `view purchases` |  |  |  |  |  |
| `manage purchases` |  |  |  |  |  |
| `return purchases` |  |  |  |  |  |
| `record supplier payments` |  |  |  |  |  |
| `create customers` |  |  |  |  |  |
| `manage financial accounts`|  |  |  |  |  |
| `record expenses` |  |  |  |  |  |

---

## 3. Post-Login Routing Matrix

| Role | Initial Landing Page | Default Scope | Allowed Switching? |
| :--- | :--- | :--- | :--- |
| **Business Super Owner** | `/dashboard` | Consolidated All Branches | YES (Can switch branch context or activate View-As) |
| **Branch Manager** | `/dashboard` | Assigned Primary Branch | NO (Locked to assigned branch unless multi-branch assigned) |
| **Salesperson / Cashier**| `/POS` | Assigned Primary Branch | NO (Locked to assigned branch terminal) |
| **Accountant** | `/dashboard` | Business-Wide Financials | YES (Branch financial filters) |
| **Inventory Manager** | `/inventory` | Business-Wide Catalog & Stock | YES (Branch stock filters) |
