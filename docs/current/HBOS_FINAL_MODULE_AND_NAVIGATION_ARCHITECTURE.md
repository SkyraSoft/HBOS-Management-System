# HBOS Final Module & Navigation Architecture

## 21 Audited Modules & Navigation Blueprint

| Module | Target Name | Primary User | Action |
|---|---|---|---|
| `ONBOARDING` | Store Setup & Registration | Business Owner | `SIMPLIFY` |
| `BRANCH_MANAGEMENT` | Stores & Branches | Business Owner | `REFINE` |
| `USERS_AND_ACCESS` | Staff & Security Governance | Business Owner | `ROLE_VARIANT` |
| `POS` | Point of Sale Counter | Salesperson | `REFINE` |
| `CUSTOMERS` | Customer Directory | Business Owner / Salesperson | `KEEP` |
| `KHATA` | Customer Khata Ledger | Business Owner / Salesperson | `KEEP` |
| `RETURNS` | Sales Returns & Refunds | Salesperson / Branch Manager | `REFINE` |
| `PRODUCTS` | Product Catalog & Pricing | Business Owner / Branch Manager | `REFINE` |
| `INVENTORY` | Stock Intelligence & Adjustments | Business Owner / Branch Manager | `REFINE` |
| `STOCK_TRANSFERS` | Inter-Branch Stock Transfers | Business Owner / Branch Manager | `NEW_SUBMODULE` |
| `PURCHASES` | Purchasing & Stock Ingestion | Business Owner / Branch Manager | `REFINE` |
| `SUPPLIERS` | Supplier Directory & Payables | Business Owner / Branch Manager | `KEEP` |
| `EXPENSES` | Store Expenses | Business Owner / Branch Manager | `KEEP` |
| `FINANCIAL_ACCOUNTS` | Accounts & Treasury | Business Owner | `KEEP` |
| `DAILY_CLOSE` | Cash Shift & Day Close | Salesperson / Business Owner | `NEW_SUBMODULE` |
| `DASHBOARD` | Executive & Store Overview | Business Owner / Branch Manager / Salesperson | `REFINE` |
| `REPORTING` | Business Reports & P&L | Business Owner / Branch Manager | `REFINE` |
| `TASKS_ALERTS` | Branch Action Notes | Business Owner / Branch Manager | `NEW_SUBMODULE` |
| `OFFLINE` | Offline & Sync Engine | All Users | `REFINE` |
| `PRINTING` | Thermal Receipt & Label Hardware | Salesperson / Branch Manager | `REFINE` |
| `AUDIT_ACTIVITY` | Audit Log Trail | Business Owner | `NEW_SUBMODULE` |
