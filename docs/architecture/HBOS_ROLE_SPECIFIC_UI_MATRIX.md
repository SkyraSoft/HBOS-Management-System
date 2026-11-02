# HBOS Role-Specific UI & Screen Access Matrix
**SkyraSoft HBOS Retail Platform & Workforce Integrity Program**

---

## 1. Matrix Key Definitions
- **FULL_ACCESS**: Complete read, write, update, delete, and management capabilities.
- **SCOPED_BRANCH_ACCESS**: Full operational capabilities restricted strictly to the user's assigned branch.
- **ACTION_LIMITED**: Restricted subset of operations (e.g., lookup only, create only).
- **READ_ONLY**: View and inspect data without mutation capabilities.
- **HIDDEN**: Route and navigation link completely hidden from UI; server denies access.
- **NOT_APPLICABLE**: Module irrelevant to the operational responsibilities of the role.

---

## 2. Complete Screen Access Matrix

| Route Path | Route Name | Business Super Owner | Branch Manager | Salesperson / Cashier | Accountant | Inventory Manager |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `/` | `Dashboard` | **FULL_ACCESS** (Consolidated) | **SCOPED_BRANCH_ACCESS** | **HIDDEN** (Redirects `/POS`) | **SCOPED_BRANCH_ACCESS** (Financials) | **SCOPED_BRANCH_ACCESS** (Stock) |
| `/POS` | `PosSale` | **FULL_ACCESS** | **FULL_ACCESS** (Branch POS) | **FULL_ACCESS** (Primary Landing) | **READ_ONLY** | **NOT_APPLICABLE** |
| `/sales` | `Sales` | **FULL_ACCESS** | **SCOPED_BRANCH_ACCESS** | **ACTION_LIMITED** (Own Sales) | **READ_ONLY** | **NOT_APPLICABLE** |
| `/inventory` | `Inventry` | **FULL_ACCESS** | **SCOPED_BRANCH_ACCESS** | **READ_ONLY** (Price/Stock) | **READ_ONLY** | **FULL_ACCESS** (Primary Landing) |
| `/reorder` | `Reorder` | **FULL_ACCESS** | **SCOPED_BRANCH_ACCESS** | **HIDDEN** | **READ_ONLY** | **FULL_ACCESS** |
| `/reorderdetail` | `ReorderDetail`| **FULL_ACCESS** | **SCOPED_BRANCH_ACCESS** | **HIDDEN** | **READ_ONLY** | **FULL_ACCESS** |
| `/purchase` | `Purchase` | **FULL_ACCESS** | **SCOPED_BRANCH_ACCESS** | **HIDDEN** | **READ_ONLY** | **FULL_ACCESS** |
| `/customer` | `Customer` | **FULL_ACCESS** | **SCOPED_BRANCH_ACCESS** | **ACTION_LIMITED** (Lookup/Add)| **FULL_ACCESS** | **NOT_APPLICABLE** |
| `/profilecustomer`| `Profilecustomer`| **FULL_ACCESS** | **SCOPED_BRANCH_ACCESS** | **ACTION_LIMITED** (Ledger) | **FULL_ACCESS** | **NOT_APPLICABLE** |
| `/khata` | `Khata` | **FULL_ACCESS** | **SCOPED_BRANCH_ACCESS** | **HIDDEN** | **FULL_ACCESS** | **NOT_APPLICABLE** |
| `/returndebt` | `ReturnDebt` | **FULL_ACCESS** | **SCOPED_BRANCH_ACCESS** | **HIDDEN** | **FULL_ACCESS** | **NOT_APPLICABLE** |
| `/supplier` | `Supplier` | **FULL_ACCESS** | **SCOPED_BRANCH_ACCESS** | **HIDDEN** | **FULL_ACCESS** | **READ_ONLY** |
| `/supplierpayable`| `Supplierpayable`| **FULL_ACCESS** | **SCOPED_BRANCH_ACCESS** | **HIDDEN** | **FULL_ACCESS** | **HIDDEN** |
| `/supplierpayment`| `SupplierPayment`| **FULL_ACCESS** | **HIDDEN** | **HIDDEN** | **FULL_ACCESS** | **HIDDEN** |
| `/expenses` | `Expenses` | **FULL_ACCESS** | **SCOPED_BRANCH_ACCESS** | **HIDDEN** | **FULL_ACCESS** | **HIDDEN** |
| `/employees` | `Employees` | **FULL_ACCESS** | **SCOPED_BRANCH_ACCESS** (Staff) | **HIDDEN** | **READ_ONLY** (Payroll) | **HIDDEN** |
| `/reports` | `Reports` | **FULL_ACCESS** | **SCOPED_BRANCH_ACCESS** | **HIDDEN** | **FULL_ACCESS** (Financials)| **ACTION_LIMITED** (Stock) |
| `/settings` | `Settings` | **FULL_ACCESS** | **ACTION_LIMITED** (Branch) | **ACTION_LIMITED** (Printer)| **ACTION_LIMITED** (Accounts)| **HIDDEN** |
| `/notifications` | `Notifications` | **FULL_ACCESS** | **FULL_ACCESS** | **FULL_ACCESS** | **FULL_ACCESS** | **FULL_ACCESS** |
| `/support` | `Support` | **FULL_ACCESS** | **FULL_ACCESS** | **FULL_ACCESS** | **FULL_ACCESS** | **FULL_ACCESS** |
