# HBOS Phase 0 — Target Repository Structure
**Program**: SkyraSoft HBOS Small Retail Transformation Program  
**Phase**: Phase 0 — Repository Cleanup & Baseline Normalization  
**Document**: Architecture Blueprint for Clean Normalized Repository  
**Date**: October 3, 2026  
**Status**: TARGET BLUEPRINT (Design Only — Execution in Prompt 0.2)

---

## 1. Clean Top-Level Structure Overview

```
HBOS/
├── api/                     # Laravel 10 Backend API
│   ├── app/                 # Controllers, Models, Services, Middleware, Commands
│   ├── config/              # Framework and App Configuration
│   ├── database/
│   │   ├── migrations/      # 66 Immutable Schema Migrations
│   │   └── seeders/         # Essential Seeders
│   ├── routes/              # API and Console Routes
│   ├── storage/             # Framework and File Storage
│   └── tests/               # PHPUnit Automated Regression Suites
├── Frontend/                # Vue 3 + Vite Frontend Web App
│   ├── public/              # Static Assets & Icons
│   ├── src/                 # Views, Components, Router, Stores, Services
│   ├── package.json         # NPM Dependencies
│   └── vite.config.js       # Vite Build Configuration
├── desktop/                 # Desktop Distribution Shell (Tauri)
│   └── tauri.conf.json      # Tauri Packaging Configuration
├── android/                 # Mobile Distribution Shell (Capacitor)
│   └── capacitor.config.json # Capacitor Packaging Configuration
├── database_backups/        # Local Development Database Snapshots
├── docs/                    # Authoritative & Structured Documentation
│   ├── README.md            # Master Developer & System Guide
│   ├── current/             # Active Phase Transformation Documents
│   ├── operations/          # Live Operating Manuals, SOPs & Runbooks
│   ├── architecture/        # Authoritative Architecture, RBAC & Lineage Audits
│   ├── release/             # Packaging, Shell Decisions & Release Runbooks
│   └── archive/             # Archived Historical Audits, Certifications & Evidence
│       ├── phase10-certification/
│       ├── functional-audit/
│       ├── phase9-remediation/
│       ├── public-platform/
│       ├── retail-pre-transformation/
│       ├── progress/
│       └── evidence/        # Historical JSON Gap & Audit Evidence
└── scratch/                 # Temporary Scratch & Living Tools
    ├── active-tools/        # Reusable diagnostic tools & benchmark scripts
    └── manifests/           # Authoritative phase cleanup & gap manifests
```

---

## 2. Documentation Reorganization Blueprint

In Prompt 0.2, the 82 historical and operational documents will be organized into intuitive domain directories:

### 2.1 `docs/current/` (Active Transformation Phase)
- `HBOS_PHASE_00_REPOSITORY_CLEANUP_AUDIT.md`
- `HBOS_PHASE_00_CLEANUP_CLASSIFICATION_POLICY.md`
- `HBOS_PHASE_00_TARGET_REPOSITORY_STRUCTURE.md`
- `phase-00-repository-cleanup-progress.md`

### 2.2 `docs/operations/` (Live Operational Manuals & SOPs)
- `HBOS_DEFINITIVE_MANAGEMENT_SYSTEM_OPERATING_GUIDE.md`
- `HBOS_END_TO_END_OPERATING_SOPS.md`
- `HBOS_PAGE_TAB_OPERATOR_MANUAL.md`
- `HBOS_ADMIN_SUPPORT_AND_RECONCILIATION_GUIDE.md`
- `HBOS_PUBLIC_ACCOUNT_SUPPORT_RUNBOOK.md`
- `HBOS_BUSINESS_ONBOARDING_OPERATOR_FLOW.md`
- `HBOS_BRANCH_MANAGEMENT_OPERATOR_FLOW.md`
- `HBOS_EMPLOYEE_ACCOUNT_AND_PROFILE_FLOW.md`

### 2.3 `docs/architecture/` (Authoritative Architecture & Lineage)
- `HBOS_FINAL_SYSTEM_CERTIFICATION.md`
- `HBOS_FIELD_DATA_LINEAGE.md`
- `HBOS_REVERSE_DATA_LINEAGE.md`
- `HBOS_ENTITY_LIFECYCLE_AND_CRUD_AUDIT.md`
- `HBOS_CROSS_MODULE_BUSINESS_FLOW_AUDIT.md`
- `HBOS_CALCULATION_ACCOUNTING_RULE_AUDIT.md`
- `HBOS_UI_FORM_INTERACTION_AUDIT.md`
- `HBOS_RBAC_VALIDATION_STATE_CONTROL_AUDIT.md`
- `HBOS_OFFLINE_SYNC_HARDWARE_PRINT_AUDIT.md`
- `HBOS_AUTHORITATIVE_SYSTEM_GAP_REGISTER.md`
- `HBOS_PUBLIC_DISTRIBUTION_TENANCY_ARCHITECTURE_AUDIT.md`
- `HBOS_ROLE_AND_PERMISSION_TARGET_ARCHITECTURE.md`
- `HBOS_PUBLIC_ACCOUNT_ONBOARDING_DESIGN.md`
- `HBOS_RETAIL_ORGANIZATION_ARCHITECTURE_AUDIT.md`
- `HBOS_RETAIL_IDENTITY_AND_AUDIT_FOUNDATION.md`
- `HBOS_ROLE_SPECIFIC_UI_MATRIX.md`
- `HBOS_SUPER_OWNER_EXPERIENCE_DESIGN.md`
- `HBOS_BUSINESS_ONBOARDING_FIELD_SPECIFICATION.md`
- `HBOS_BRANCH_AND_EMPLOYEE_LIFECYCLE_DESIGN.md`
- `HBOS_ACTIVITY_AND_AUDIT_ATTRIBUTION_DESIGN.md`

### 2.4 `docs/release/` (Distribution, Shell & Updates)
- `HBOS_WINDOWS_DISTRIBUTION_UPDATE_DESIGN.md`
- `HBOS_WINDOWS_RELEASE_AND_UPDATE_RUNBOOK.md`
- `HBOS_WINDOWS_DESKTOP_SHELL_DECISION.md`

### 2.5 `docs/archive/` (Historical Records & Audit Evidence)
- `docs/archive/phase10-certification/` (Phase 1 through 9 certification markdown files)
- `docs/archive/functional-audit/` (`HBOS_PAGE_LEVEL_MAPPING.md`, `HBOS_MAPPING_COVERAGE_AUDIT.md`, `HBOS_ZERO_OMISSION_MASTER_MAP.md`)
- `docs/archive/phase9-remediation/` (`HBOS_PHASE_9_REMEDIATION_BLUEPRINT.md`, `HBOS_PHASE_9_IMPLEMENTATION_REPORT.md`)
- `docs/archive/public-platform/` (`HBOS_PUBLIC_PLATFORM_IMPLEMENTATION_REPORT.md`)
- `docs/archive/retail-pre-transformation/` (`HBOS_RETAIL_PROMPT2_IMPLEMENTATION_REPORT.md`)
- `docs/archive/progress/` (Phase 1–10 progress logs)
- `docs/archive/evidence/` (54 historical JSON gap registries and audit outputs)

---

## 3. Test Suite Normalization Blueprint

In future transformation phases, the 67 test files will be organized by domain namespaces:

```
api/tests/Feature/
├── Auth/               # Auth, Tokens, Roles, Permissions, User Governance
├── Tenancy/            # Multi-Tenancy, Branch Scoping, Tenant Isolation
├── POS/                # Sale Creation, Idempotency, Checkout, Returns, Discounts
├── Inventory/          # Product Catalog, Stock Deductions, Backfill, Transfers
├── Khata/              # Customer Ledger, Credit Limits, Khata Payments
├── Finance/            # Expenses, Supplier Payments, Financial Movements
├── Offline/            # BulkSync, Device Pairing, Conflict Replay
├── Retail/             # Workforce Governance, Invitations, Staged Onboarding
└── Integrity/          # Migration Rollbacks, Concurrency, Hardening Invariants
```

---

## 4. Scratch Directory Normalization Plan

The scratch directory will transition from an unstructured flat directory to:
- `scratch/active-tools/`: Retains permanent diagnostic tools (`verify_phase0_prompt1.php`, `reconcile_dev_customers.php`, `performance_benchmark.php`).
- Removal of 247 one-off generation/lineage populator scripts in Prompt 0.2 whose outputs are permanently preserved in documentation.
- Archiving of 54 JSON evidence registries into `docs/archive/evidence/`.

This achieves a pristine, professional repository ready for Phase 1 (300-Question Requirements & Final Product Blueprint).
