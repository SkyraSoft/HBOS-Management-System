# SKYRASOFT HBOS — ACTIVE ROUTE AND COMPONENT MAP
**Document Reference:** `docs/release/HBOS_ACTIVE_ROUTE_AND_COMPONENT_MAP.md`  
**Stage:** Stage 1 — Safe Desktop Foundation & Application Shell  
**Checkpoints Covered:** Checkpoint 1.1, Checkpoint 1.3  
**Status:** AUTHORITATIVE FRONTEND COMPONENT AUDIT & ROUTE REGISTRY

---

## 1. Executive Summary

A critical issue discovered in previous QA cycles was divergence between changes made to phantom/dormant Vue SFC files (such as `ProductView.vue`) and the actual components compiled by Vite into the live application (such as `InventryView.vue`).

This document provides the authoritative, audited mapping of every route registered in `Frontend/src/router/index.js`, its live Single File Component (SFC), its associated business domain, and its canonical backend API endpoints.

---

## 2. Master Route-to-Component Mapping Table

| URI Path | Route Name | Live Component File | Business Domain | Key Backend API Endpoints | Notes & Historical Fixes |
|---|---|---|---|---|---|
| `/welcome` | `Welcome` | `src/views/WelcomeView.vue` | Entry & Auth Selection | `/api/v1/ping` | Stage 1 3-option landing: Log In, Create My Business, Try Demo |
| `/login` | `Login` | `src/views/LoginView.vue` | Authentication | `/api/v1/login` | Includes "← Back to Welcome" link |
| `/register` | `Register` | `src/views/RegisterView.vue` | Onboarding (Stage 1) | `/api/v1/register` | Includes "← Back to Welcome" link |
| `/businesssetup1` | `BusinessSetup1` | `src/views/BusinessSetup1View.vue` | Onboarding (Stage 2) | `/api/v1/business` | Business details setup |
| `/mainstoresetup` | `MainStoreSetup` | `src/views/MainStoreSetupView.vue` | Onboarding (Stage 3) | `/api/v1/branches` | Primary Main Store setup |
| `/preferences` | `Preferences` | `src/views/PreferencesView.vue` | Onboarding (Stage 4) | `/api/v1/business/preferences` | Currency, timezone, invoice prefix |
| `/completereview` | `CompleteReview` | `src/views/CompleteReviewView.vue` | Onboarding (Stage 5) | `/api/v1/business/complete-setup` | Review and initialize clean shop |
| `/completesetup` | `CompleteSetup` | `src/views/CompleteSetupView.vue` | Onboarding Success | `/api/v1/me` | Onboarding completion celebration |
| `/` (alias: `/dashboard`) | `Dashboard` | `src/views/DashboardView.vue` | Executive & Branch Dashboard | `/api/v1/dashboard/kpis`, `/api/v1/dashboard/sales-trend` | Protected: Cashiers redirected to `/POS` |
| `/POS` (alias: `/pos`) | `PosSale` | `src/views/PosSaleView.vue` | POS Checkout Counter | `/api/v1/products`, `/api/v1/sales`, `/api/v1/customers`, `/api/v1/cash-registers/active` | Primary checkout for Owner & Cashier; offline durable |
| `/products`, `/inventory/products`, `/inventry` | `Products`, `InventoryProducts`, `Inventry` | `src/views/InventryView.vue` | Catalog & Inventory | `/api/v1/products`, `/api/v1/categories`, `/api/v1/brands`, `/api/v1/inventory/adjustments` | **CRITICAL:** `InventryView.vue` is the live component for all product catalog routes. `ProductView.vue` is dormant. Supports loose/fractional units, alternate barcodes, active/inactive. |
| `/reorder` | `Reorder` | `src/views/ReorderView.vue` | Inventory Intelligence | `/api/v1/inventory/reorder-suggestions` | Low-stock suggestions & reorder criteria |
| `/reorderdetail` | `ReorderDetail` | `src/views/ReorderDetailView.vue` | Inventory Intelligence | `/api/v1/inventory/reorder-suggestions/{id}` | Detailed reorder breakdown |
| `/transfers` (alias: `/stock-transfers`) | `StockTransfers` | `src/views/StockTransferView.vue` | Multi-Branch Transfers | `/api/v1/transfers`, `/api/v1/transfers/{id}/dispatch`, `/api/v1/transfers/{id}/receive` | Inter-branch inventory dispatch & receipt |
| `/sales` | `Sales` | `src/views/SalesView.vue` | Sales Ledger & Receipts | `/api/v1/sales`, `/api/v1/sales/{id}/receipt`, `/api/v1/sales/{id}/refund` | Historical invoices, reprints, returns & refunds |
| `/customer` (alias: `/customers`) | `Customer` | `src/views/CustomerView.vue` | Customer Directory | `/api/v1/customers`, `/api/v1/customers/{id}` | Customer creation, credit limits, search |
| `/profilecustomer` | `Profilecustomer` | `src/views/ProfilecustomerView.vue` | Customer Profile | `/api/v1/customers/{id}` | Deep customer ledger & history |
| `/khata` (alias: `/khatas`) | `Khata` | `src/views/KhataView.vue` | Customer Khata & Collections | `/api/v1/customers/khata`, `/api/v1/customers/{id}/payments`, `/api/v1/customer-payments/{id}/reverse` | Khata statements, repayments, aging, reversals |
| `/purchase` | `Purchase` | `src/views/PurchaseView.vue` | Purchasing & Goods Inward | `/api/v1/purchases`, `/api/v1/suppliers`, `/api/v1/products` | Supplier invoices, landed cost, stock receipt |
| `/supplier` | `Supplier` | `src/views/SupplierView.vue` | Supplier Directory | `/api/v1/suppliers` | Dynamic supplier directory & contacts |
| `/supplierpayable` | `Supplierpayable` | `src/views/SupplierpayableView.vue` | Supplier Payables | `/api/v1/purchases/payables`, `/api/v1/suppliers/summary` | Outstanding supplier liability ledger |
| `/supplierpayment` | `SupplierPayment` | `src/views/SupplierPaymentView.vue` | Supplier Settlement | `/api/v1/purchases/{id}/payments`, `/api/v1/supplier-payments/{id}/reverse` | Payout from selected cash/bank account; produces cash outflow |
| `/expenses` | `Expenses` | `src/views/ExpensesView.vue` | Expense & Cash Outflows | `/api/v1/expenses`, `/api/v1/expense-categories` | Operating expenses by category & branch |
| `/reports` (aliases: `/report`, `/reports1`, `/analytics`) | `Reports` | `src/views/ReportsView.vue` | Financial & Tax Reports | `/api/v1/reports/sales`, `/api/v1/reports/profit-loss`, `/api/v1/reports/tax-summary` | Accurate COGS, Net Profit, reconciliation |
| `/busniesssetup` (alias: `/branches`) | `BusniessSetup` | `src/views/BusniessSetupView.vue` | Branch Management | `/api/v1/branches`, `/api/v1/branches/{id}/status` | Branch creation, activation/deactivation guards |
| `/settings` | `Settings` | `src/views/SettingsView.vue` | System Administration | `/api/v1/business`, `/api/v1/users`, `/api/v1/roles` | Tabs: Branches, Users, Roles, Receipts, Security, Sync |
| `/notifications` | `Notifications` | `src/views/NotificationsView.vue` | System Alerts | `/api/v1/notifications` | Low-stock and operational alerts |
| `/information` | `Information` | `src/views/InformationView.vue` | Store Info & Tax Details | `/api/v1/business` | Legal store configuration |
| `/support` | `Support` | `src/views/SupportView.vue` | Help & Diagnostic | `/api/v1/system/status` | Safe diagnostics & SkyraSoft support contacts |
| `/about` | `About` | `src/views/AboutView.vue` | Application Metadata | Client-side metadata | Software version, build hash, copyright |

---

## 3. Dormant vs Live Component Clarification

1. **`src/views/ProductView.vue` vs `src/views/InventryView.vue`:**
   - `InventryView.vue` is the live component imported by `/products`, `/inventory/products`, and `/inventry`.
   - Any modifications to product forms, barcode inputs, or pricing rules MUST be performed in `InventryView.vue`.
2. **`src/views/EmployeesView.vue` vs `src/views/SettingsView.vue`:**
   - The route `/employees` redirects automatically to `/settings?tab=users`.
   - User and staff assignment is handled within `SettingsView.vue` (`src/views/Settings/UsersView.vue`).

---

## 4. Verification

- All 28 live routes compile successfully via Vite (`npm run build` returned exit code 0).
- All navigational transitions are protected by role-aware guards in `Frontend/src/router/index.js`.
- Session expiration redirects to `/welcome`.
