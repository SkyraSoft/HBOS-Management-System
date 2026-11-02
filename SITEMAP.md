# HBOS (Hybrid Business Operating System) — Master Project Sitemap

**Project**: SkyraSoft HBOS Small Retail Transformation Program  
**Normalized Date**: October 3, 2026  
**Status**: Clean Baseline Repository Sitemap (Phase-0 Normalized)  

---

## 1. Top-Level Repository Directory Tree

```
HBOS/
├── api/                            # Laravel 12.67.0 Backend REST API (PHP 8.2+)
│   ├── app/
│   │   ├── Console/Commands/       # Operational CLI & Data Reconciliation Commands
│   │   ├── Http/
│   │   │   ├── Controllers/Api/V1/ # REST API V1 Business Controllers (32 controllers)
│   │   │   ├── Middleware/         # Auth, Tenant Context & Client Version Gates
│   │   │   └── Requests/           # Form Validation Request Classes
│   │   ├── Models/                 # Eloquent Domain Models (33 models)
│   │   ├── Services/               # Domain Business Logic & Mutation Services
│   │   └── Traits/                 # Multi-Tenancy (ActiveBusinessScope) & Audit Traits
│   ├── config/                     # Framework, DB, Auth, Version & CORS Config
│   ├── database/
│   │   ├── migrations/             # 66 Migration Files (44 unique tables detected via Schema::create)
│   │   └── seeders/                # Role, Permission & Initial System Seeders
│   ├── routes/                     # api.php (147 endpoints), web.php, console.php
│   ├── storage/                    # Logs, App Storage & Framework Cache
│   └── tests/                      # 67 Automated PHPUnit Feature/Unit Test Suites
│       ├── Feature/                # Auth, POS, Inventory, Tenancy, Finance Tests
│       └── Unit/                   # Unit Tests
├── Frontend/                       # Vue 3 (Composition API) + Vite 8.2 Frontend App
│   ├── public/                     # Static Assets, Icons & Offline Manifests
│   ├── src/
│   │   ├── assets/                 # Global CSS & Styles
│   │   ├── components/             # Reusable UI & Modal Components (9 components)
│   │   ├── router/                 # Vue Router Configuration (router/index.js)
│   │   ├── services/               # Device Pairing, Native IndexedDB & API Network
│   │   ├── stores/                 # Pinia State Stores (Auth, POS, Inventory, Khata, etc.)
│   │   └── views/                  # Primary Screen Views & Onboarding Steps (40 views)
│   ├── package.json                # Frontend NPM Dependencies
│   └── vite.config.js              # Vite Build Pipeline Configuration
├── desktop/                        # Desktop Distribution Wrapper
│   └── tauri.conf.json             # Tauri Configuration present and parse-valid (CONFIG_PRESENT; packaging & distribution build will be verified in Phase 8)
├── android/                        # Mobile Distribution Wrapper
│   └── capacitor.config.json       # Capacitor Configuration present and parse-valid (CONFIG_PRESENT; packaging & distribution build will be verified in Phase 8)
├── database_backups/               # Development Database Snapshots & Test Fixtures (2 SQL files)
├── docs/                           # Central Documentation Architecture
│   ├── README.md                   # Master System Documentation Index
│   ├── current/                    # Active Transformation Specs & Completion Certificates
│   ├── operations/                 # Definitive Operating Manuals, SOPs & Runbooks
│   ├── architecture/               # Data Lineage & Security Audits
│   ├── release/                    # Windows Distribution & Update Runbooks
│   └── archive/                    # Archived Historical Certifications & JSON Evidence
│       ├── phase10-certification/  # Historical Phase 1–9 Certification Reports
│       ├── functional-audit/       # Historical Lineage & Mapping Coverage Audits
│       ├── phase9-remediation/     # Historical Phase 9 Remediation Blueprints
│       ├── public-platform/        # Historical Public Distribution Reports
│       ├── retail-pre-transformation/ # Retail Prompt 2 Implementation Reports
│       ├── progress/               # Historical Phase 1–10 Progress Logs
│       └── evidence/               # 121 Historical JSON Gap & Audit Registries
├── scratch/                        # Active Diagnostic Tools & Transformation Registries
├── .gitignore                      # Git Tracking Exclusion Rules
├── BACKUP_RESTORE.md               # Database Backup & Recovery Guide
├── HBOS_PHASE_STATUS.md            # Program Phase Status Matrix
├── INSTALLATION.md                 # System Installation & Local Setup Guide
├── PRODUCTION_CONFIG.md            # Production Deployment & Environment Guide
├── RELEASE_NOTES.md                # System Release Notes & Change Log
└── SITEMAP.md                      # This Master Repository Sitemap
```

---

## 2. Core Modules & Architecture Relationships

### 2.1 Multi-Tenant Identity & RBAC Engine
- **Backend**: [AuthController.php](file:///c:/xampp/htdocs/HBOS/api/app/Http/Controllers/Api/V1/AuthController.php), [UserGovernanceController.php](file:///c:/xampp/htdocs/HBOS/api/app/Http/Controllers/Api/V1/UserGovernanceController.php), [User.php](file:///c:/xampp/htdocs/HBOS/api/app/Models/User.php), [Business.php](file:///c:/xampp/htdocs/HBOS/api/app/Models/Business.php), [Branch.php](file:///c:/xampp/htdocs/HBOS/api/app/Models/Branch.php).
- **Frontend**: [auth.js](file:///c:/xampp/htdocs/HBOS/Frontend/src/stores/auth.js), [LoginView.vue](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/LoginView.vue), [RegisterView.vue](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/RegisterView.vue), [UsersView.vue](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/Settings/UsersView.vue).
- **Relationship**: Enforces strict row-level isolation via `business_id` and Spatie-backed permission assignments. The current technical roles persisted in `RolesAndPermissionsSeeder.php` are `Business Owner`, `Branch Manager`, `Salesperson`, `Accountant`, and `Inventory Manager`. These represent the CURRENT TECHNICAL roles; the final small-retail V1 active role model and product normalization will be formalized in Phase 1.

### 2.2 Point of Sale (POS), Sales & Returns
- **Backend**: [SaleController.php](file:///c:/xampp/htdocs/HBOS/api/app/Http/Controllers/Api/V1/SaleController.php), [SaleService.php](file:///c:/xampp/htdocs/HBOS/api/app/Services/SaleService.php), [Sale.php](file:///c:/xampp/htdocs/HBOS/api/app/Models/Sale.php), [SaleItem.php](file:///c:/xampp/htdocs/HBOS/api/app/Models/SaleItem.php), [SalePayment.php](file:///c:/xampp/htdocs/HBOS/api/app/Models/SalePayment.php), [SaleReturn.php](file:///c:/xampp/htdocs/HBOS/api/app/Models/SaleReturn.php).
- **Frontend**: [PosSaleView.vue](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/PosSaleView.vue), [SalesView.vue](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/SalesView.vue), [cart.js](file:///c:/xampp/htdocs/HBOS/Frontend/src/stores/cart.js).
- **Relationship**: Atomic POS checkout deducting stock from the active branch inventory, persisting COGS snapshots, logging idempotency keys, and updating customer khata balances when on credit.

### 2.3 Product Catalog, Stock & Inventory Control
- **Backend**: [ProductController.php](file:///c:/xampp/htdocs/HBOS/api/app/Http/Controllers/Api/V1/ProductController.php), [BrandController.php](file:///c:/xampp/htdocs/HBOS/api/app/Http/Controllers/Api/V1/BrandController.php), [CategoryController.php](file:///c:/xampp/htdocs/HBOS/api/app/Http/Controllers/Api/V1/CategoryController.php), [Product.php](file:///c:/xampp/htdocs/HBOS/api/app/Models/Product.php), [BranchInventory.php](file:///c:/xampp/htdocs/HBOS/api/app/Models/BranchInventory.php), [InventoryMovement.php](file:///c:/xampp/htdocs/HBOS/api/app/Models/InventoryMovement.php).
- **Frontend**: [InventryView.vue](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/InventryView.vue), [inventory.js](file:///c:/xampp/htdocs/HBOS/Frontend/src/stores/inventory.js).
- **Relationship**: Multi-branch stock tracking with ledger movement history (`opening`, `purchase`, `sale`, `return`, `adjustment`, `transfer`).

### 2.4 Purchases, Suppliers & Supplier Returns
- **Backend**: [PurchaseController.php](file:///c:/xampp/htdocs/HBOS/api/app/Http/Controllers/Api/V1/PurchaseController.php), [PurchaseService.php](file:///c:/xampp/htdocs/HBOS/api/app/Services/PurchaseService.php), [SupplierController.php](file:///c:/xampp/htdocs/HBOS/api/app/Http/Controllers/Api/V1/SupplierController.php), [SupplierPaymentController.php](file:///c:/xampp/htdocs/HBOS/api/app/Http/Controllers/Api/V1/SupplierPaymentController.php), [SupplierReturnController.php](file:///c:/xampp/htdocs/HBOS/api/app/Http/Controllers/Api/V1/SupplierReturnController.php), [Supplier.php](file:///c:/xampp/htdocs/HBOS/api/app/Models/Supplier.php).
- **Frontend**: [PurchaseView.vue](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/PurchaseView.vue), [SupplierView.vue](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/SupplierView.vue), [SupplierPaymentView.vue](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/SupplierPaymentView.vue), [suppliers.js](file:///c:/xampp/htdocs/HBOS/Frontend/src/stores/suppliers.js).
- **Relationship**: Purchasing workflow increasing branch stock, recording supplier payables, and managing payments via dedicated financial accounts.

### 2.5 Customers & Khata Credit Ledger
- **Backend**: [CustomerController.php](file:///c:/xampp/htdocs/HBOS/api/app/Http/Controllers/Api/V1/CustomerController.php), [CustomerPaymentController.php](file:///c:/xampp/htdocs/HBOS/api/app/Http/Controllers/Api/V1/CustomerPaymentController.php), [Customer.php](file:///c:/xampp/htdocs/HBOS/api/app/Models/Customer.php), [CustomerPayment.php](file:///c:/xampp/htdocs/HBOS/api/app/Models/CustomerPayment.php).
- **Frontend**: [CustomerView.vue](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/CustomerView.vue), [KhataView.vue](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue), [customers.js](file:///c:/xampp/htdocs/HBOS/Frontend/src/stores/customers.js), [khata.js](file:///c:/xampp/htdocs/HBOS/Frontend/src/stores/khata.js).
- **Relationship**: Customer profile management, credit limits, khata ledger transaction history, and settlement receipts.

### 2.6 Financial Accounts, Expenses & Cash Management
- **Backend**: [FinancialAccountController.php](file:///c:/xampp/htdocs/HBOS/api/app/Http/Controllers/Api/V1/FinancialAccountController.php), [ExpenseController.php](file:///c:/xampp/htdocs/HBOS/api/app/Http/Controllers/Api/V1/ExpenseController.php), [ExpenseCategoryController.php](file:///c:/xampp/htdocs/HBOS/api/app/Http/Controllers/Api/V1/ExpenseCategoryController.php), [AccountMovement.php](file:///c:/xampp/htdocs/HBOS/api/app/Models/AccountMovement.php), [Expense.php](file:///c:/xampp/htdocs/HBOS/api/app/Models/Expense.php).
- **Frontend**: [ExpensesView.vue](file:///c:/xampp/htdocs/HBOS/Frontend/src/views/ExpensesView.vue), [expenses.js](file:///c:/xampp/htdocs/HBOS/Frontend/src/stores/expenses.js).
- **Relationship**: Account-movement and cash-flow ledger with transaction attribution (tracking cash drawers, bank/digital accounts, inflows/outflows, expenses, and customer khata; full double-entry GL is not implemented).

### 2.7 Offline Synchronization & Client Storage
- **Backend**: [SyncController.php](file:///c:/xampp/htdocs/HBOS/api/app/Http/Controllers/Api/V1/SyncController.php), [BulkSyncService.php](file:///c:/xampp/htdocs/HBOS/api/app/Services/BulkSyncService.php), [DevicePairing.php](file:///c:/xampp/htdocs/HBOS/api/app/Models/DevicePairing.php).
- **Frontend**: [localDb.js](file:///c:/xampp/htdocs/HBOS/Frontend/src/services/localDb.js), [devicePairingService.js](file:///c:/xampp/htdocs/HBOS/Frontend/src/services/devicePairingService.js).
- **Relationship**: Client-side native browser IndexedDB storage (tenant-partitioned per business ID), offline transaction staging, bulk sync ingest, deterministic uuid reconciliation, and multi-device pairing.

---

## 3. Authoritative Documentation Sitemap

| Category | Document Path | Focus & Purpose |
|---|---|---|
| **Index** | [docs/README.md](file:///c:/xampp/htdocs/HBOS/docs/README.md) | Central developer navigation portal |
| **Current** | [docs/current/HBOS_CURRENT_DOCUMENT_AUTHORITY.md](file:///c:/xampp/htdocs/HBOS/docs/current/HBOS_CURRENT_DOCUMENT_AUTHORITY.md) | Tiered document reading order & authority rules |
| **Current** | [docs/current/HBOS_CLEAN_TRANSFORMATION_BASELINE.md](file:///c:/xampp/htdocs/HBOS/docs/current/HBOS_CLEAN_TRANSFORMATION_BASELINE.md) | Technical stack and architecture baseline |
| **Current** | [docs/current/HBOS_PHASE_0_COMPLETION_CERTIFICATE.md](file:///c:/xampp/htdocs/HBOS/docs/current/HBOS_PHASE_0_COMPLETION_CERTIFICATE.md) | Official Phase 0 completion certificate |
| **Operations** | [docs/operations/HBOS_DEFINITIVE_MANAGEMENT_SYSTEM_OPERATING_GUIDE.md](file:///c:/xampp/htdocs/HBOS/docs/operations/HBOS_DEFINITIVE_MANAGEMENT_SYSTEM_OPERATING_GUIDE.md) | Master management system operating guide |
| **Operations** | [docs/operations/HBOS_END_TO_END_OPERATING_SOPS.md](file:///c:/xampp/htdocs/HBOS/docs/operations/HBOS_END_TO_END_OPERATING_SOPS.md) | Standard Operating Procedures |
| **Operations** | [docs/operations/HBOS_PAGE_TAB_OPERATOR_MANUAL.md](file:///c:/xampp/htdocs/HBOS/docs/operations/HBOS_PAGE_TAB_OPERATOR_MANUAL.md) | Operator UI form & tab manual |
| **Operations** | [docs/operations/HBOS_ADMIN_SUPPORT_AND_RECONCILIATION_GUIDE.md](file:///c:/xampp/htdocs/HBOS/docs/operations/HBOS_ADMIN_SUPPORT_AND_RECONCILIATION_GUIDE.md) | Support & ledger reconciliation runbook |
| **Architecture** | [docs/architecture/HBOS_REVERSE_DATA_LINEAGE.md](file:///c:/xampp/htdocs/HBOS/docs/architecture/HBOS_REVERSE_DATA_LINEAGE.md) | Reverse schema & mutation lineage (historical line-by-line catalog) |
| **Architecture** | [docs/architecture/HBOS_FIELD_DATA_LINEAGE.md](file:///c:/xampp/htdocs/HBOS/docs/architecture/HBOS_FIELD_DATA_LINEAGE.md) | Database attribute lineage (historical 438-field catalog) |
| **Release** | [docs/release/HBOS_WINDOWS_RELEASE_AND_UPDATE_RUNBOOK.md](file:///c:/xampp/htdocs/HBOS/docs/release/HBOS_WINDOWS_RELEASE_AND_UPDATE_RUNBOOK.md) | Windows build & updater release runbook |
| **Archive** | [docs/archive/ARCHIVE_MANIFEST.md](file:///c:/xampp/htdocs/HBOS/docs/archive/ARCHIVE_MANIFEST.md) | Historical certification & JSON evidence manifest |
