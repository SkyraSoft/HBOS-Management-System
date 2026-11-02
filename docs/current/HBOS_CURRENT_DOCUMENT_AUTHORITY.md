# HBOS Current Document Authority Guide

**Program**: SkyraSoft HBOS Small Retail Transformation Program  
**Document**: Authoritative Documentation Ranking & Developer Reading Order  
**Date**: October 3, 2026  
**Status**: ACTIVE GUIDELINE

---

## 1. Documentation Authority Hierarchy

To prevent confusion across different engineering audits and operational manuals, documentation in HBOS is partitioned into three distinct tiers of authority:

```
┌─────────────────────────────────────────────────────────────┐
│ Tier 1: CURRENT_OPERATING_AUTHORITY (Master System Manuals) │
├─────────────────────────────────────────────────────────────┤
│ Tier 2: CURRENT_ARCHITECTURE_REFERENCE (Lineage & Audits)   │
├─────────────────────────────────────────────────────────────┤
│ Tier 3: HISTORICAL_REFERENCE (Archived Certifications & Logs│
└─────────────────────────────────────────────────────────────┘
```

---

## 2. Tier 1: Current Operating Authority (Must Read First)

These documents govern current day-to-day business operations, operator workflows, user interfaces, and administrative support:

| Document | Category | Key Scope |
|---|---|---|
| [HBOS_DEFINITIVE_MANAGEMENT_SYSTEM_OPERATING_GUIDE.md](../operations/HBOS_DEFINITIVE_MANAGEMENT_SYSTEM_OPERATING_GUIDE.md) | `CURRENT_OPERATING_AUTHORITY` | Master system manual: roles, permissions, sales, purchasing, expenses, and tenant governance. |
| [HBOS_END_TO_END_OPERATING_SOPS.md](../operations/HBOS_END_TO_END_OPERATING_SOPS.md) | `CURRENT_OPERATING_AUTHORITY` | End-to-end standard operating procedures for cashier POS, inventory adjustments, and returns. |
| [HBOS_PAGE_TAB_OPERATOR_MANUAL.md](../operations/HBOS_PAGE_TAB_OPERATOR_MANUAL.md) | `CURRENT_OPERATING_AUTHORITY` | UI navigation manual detailing form inputs, actions, and expected responses per page/tab. |
| [HBOS_ADMIN_SUPPORT_AND_RECONCILIATION_GUIDE.md](../operations/HBOS_ADMIN_SUPPORT_AND_RECONCILIATION_GUIDE.md) | `CURRENT_OPERATING_AUTHORITY` | Support runbook for financial ledger reconciliations and discrepancy audits. |
| [HBOS_PUBLIC_ACCOUNT_SUPPORT_RUNBOOK.md](../operations/HBOS_PUBLIC_ACCOUNT_SUPPORT_RUNBOOK.md) | `CURRENT_OPERATING_AUTHORITY` | Multi-tenant user provisioning and support guidelines. |
| [HBOS_BUSINESS_ONBOARDING_OPERATOR_FLOW.md](../operations/HBOS_BUSINESS_ONBOARDING_OPERATOR_FLOW.md) | `CURRENT_OPERATING_AUTHORITY` | Staged business creation and onboarding workflow. |
| [HBOS_BRANCH_MANAGEMENT_OPERATOR_FLOW.md](../operations/HBOS_BRANCH_MANAGEMENT_OPERATOR_FLOW.md) | `CURRENT_OPERATING_AUTHORITY` | Branch lifecycle and staff assignment operations. |
| [HBOS_EMPLOYEE_ACCOUNT_AND_PROFILE_FLOW.md](../operations/HBOS_EMPLOYEE_ACCOUNT_AND_PROFILE_FLOW.md) | `CURRENT_OPERATING_AUTHORITY` | Staff invitation, security tokens, and profile management. |

---

## 3. Tier 2: Current Architecture & Technical Reference

These documents provide authoritative structural and data lineage baselines for backend and frontend engineers:

| Document | Category | Key Scope |
|---|---|---|
| [HBOS_FINAL_SYSTEM_CERTIFICATION.md](../architecture/HBOS_FINAL_SYSTEM_CERTIFICATION.md) | `CURRENT_ARCHITECTURE_REFERENCE` | Certified baseline architecture covering multi-tenancy, RBAC, and data integrity. |
| [HBOS_REVERSE_DATA_LINEAGE.md](../architecture/HBOS_REVERSE_DATA_LINEAGE.md) | `CURRENT_ARCHITECTURE_REFERENCE` | Master reverse data lineage covering all 66 tables, models, mutations, and side effects. |
| [HBOS_FIELD_DATA_LINEAGE.md](../architecture/HBOS_FIELD_DATA_LINEAGE.md) | `CURRENT_ARCHITECTURE_REFERENCE` | 438-field comprehensive database attribute lineage. |
| [HBOS_ENTITY_LIFECYCLE_AND_CRUD_AUDIT.md](../architecture/HBOS_ENTITY_LIFECYCLE_AND_CRUD_AUDIT.md) | `CURRENT_ARCHITECTURE_REFERENCE` | Complete CRUD lifecycle matrix for all HBOS business entities. |
| [HBOS_CROSS_MODULE_BUSINESS_FLOW_AUDIT.md](../architecture/HBOS_CROSS_MODULE_BUSINESS_FLOW_AUDIT.md) | `CURRENT_ARCHITECTURE_REFERENCE` | Cross-module flow audit across procurement, POS sales, and Khata credit settlements. |
| [HBOS_ROLE_AND_PERMISSION_TARGET_ARCHITECTURE.md](../architecture/HBOS_ROLE_AND_PERMISSION_TARGET_ARCHITECTURE.md) | `CURRENT_ARCHITECTURE_REFERENCE` | Canonical tenant role hierarchies and permission boundaries. |
| [HBOS_WINDOWS_RELEASE_AND_UPDATE_RUNBOOK.md](../release/HBOS_WINDOWS_RELEASE_AND_UPDATE_RUNBOOK.md) | `CURRENT_ARCHITECTURE_REFERENCE` | Packaging, versioning, and auto-update mechanisms for desktop distribution. |

---

## 4. Tier 3: Historical Reference (`docs/archive/`)

Documents under `docs/archive/` (such as `HBOS_PHASE_1_CERTIFICATION.md` through `HBOS_PHASE_9_CERTIFICATION.md`, legacy mapping files, and progress logs) are preserved for historical audit trails. When designing new features, developers must reference **Tier 1** and **Tier 2** documentation.
