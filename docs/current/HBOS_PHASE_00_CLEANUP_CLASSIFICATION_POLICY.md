# HBOS Phase 0 — Cleanup Classification Policy
**Program**: SkyraSoft HBOS Small Retail Transformation Program  
**Phase**: Phase 0 — Repository Cleanup & Baseline Normalization  
**Document**: Policy & Rule Specification for Repository Hygiene  
**Date**: October 3, 2026  
**Status**: AUTHORITATIVE POLICY

---

## 1. Core Principles

1. **Analysis First, Zero Destructive Actions in Prompt 1**: Prompt 1 is strictly analytical. No file shall be deleted, renamed, or moved during Prompt 1.
2. **Positive Proof of Deletion (`DELETE_SAFE`)**: A file is never deleted based on the assumption that it "looks old" or "seems unused". A file may only be marked `DELETE_SAFE` when affirmative evidence proves it is unreferenced across routes, templates, dependency injection, build systems, test suites, and operational runbooks.
3. **Preservation of Test Coverage**: The automated test suite is the repository's immutable safety net. No test file may be deleted unless identical or stronger assertions are already proven in a canonical test suite.
4. **Preservation of Schema History**: All executed or committed migrations in `api/database/migrations/` constitute immutable database lineage and must never be squashed or deleted in Phase 0.
5. **Auditable Archiving**: Historical documentation and certification artifacts are not discarded; they are systematically organized into `docs/archive/` to maintain the integrity of past engineering audits.

---

## 2. Classification Definitions

### 2.1 Documentation Classifications
- `KEEP_ACTIVE`: Authoritative, live operational manuals, system runbooks, and active transformation guides.
- `KEEP_REFERENCE`: Active architecture references, canonical data lineages, and security audits required for ongoing development.
- `ARCHIVE_HISTORICAL`: Phase completion certificates, intermediate gap findings, and historical progress logs preserved under `docs/archive/`.
- `MERGE_INTO_CANONICAL`: Documentation fragments to be synthesized into canonical master manuals before archival.
- `DELETE_GENERATED_DUPLICATE`: Redundant copies of auto-generated documentation whose canonical version exists.
- `DELETE_OBSOLETE`: Temporary scratch markdown notes with zero reference value.
- `REVIEW_REQUIRED`: Unclear or ambiguous documentation requiring human/operator sign-off.

### 2.2 Automated Test Classifications
- `KEEP_CORE_REGRESSION`: Foundational business logic tests (POS checkout, inventory deductions, Khata ledger, payments).
- `KEEP_SECURITY_REGRESSION`: RBAC, token revocation, permissions, password policies, and security headers.
- `KEEP_TENANCY_REGRESSION`: Multi-tenant isolation, cross-tenant leak prevention, and branch scoping.
- `KEEP_OFFLINE_REGRESSION`: Offline device pairing, sync replay, and conflict resolution.
- `KEEP_RETAIL_REGRESSION`: Retail workforce, branch employee governance, and staged onboarding.
- `MERGE_TESTS`: Phase-specific test suites with overlapping assertions marked for future consolidation into canonical domain directories.
- `REWRITE_LATER`: Tests requiring modernization or refactoring in future transformation phases.
- `DELETE_REDUNDANT_TEST`: Pure duplicates (none in Phase 0).
- `DELETE_OBSOLETE_TEST`: Obsolete tests targeting removed endpoints (none in Phase 0).

### 2.3 Scratch Directory Classifications
- `KEEP_ACTIVE_TOOL`: Living diagnostic tools, active verifiers, and maintenance commands.
- `KEEP_CURRENT_EVIDENCE`: Authoritative Phase 0 manifests, classification registries, and machine baselines.
- `ARCHIVE_EVIDENCE`: Historical JSON gap registries, audit dumps, and test results preserved in `docs/archive/evidence/`.
- `DELETE_ONE_OFF_SCRIPT`: One-off data population, scanning, and temporary verifier scripts whose outputs are already baked into markdown documentation.
- `DELETE_TEMPORARY`: Temporary text dumps, debug logs, and scratch scraps.

### 2.4 Production Code Classifications
- `ACTIVE_PRODUCTION`: Production code routed and invoked in runtime execution.
- `ACTIVE_CONFIGURATION`: System, environment, or framework configuration.
- `ACTIVE_SCHEMA`: Database migrations forming schema lineage.
- `ACTIVE_BUILD_CONFIGURATION`: Vite, Composer, NPM, Tailwind/CSS, or Tauri build configurations.
- `LEGACY_RETAINED`: Unrouted or older templates intentionally kept for backward compatibility or reference (e.g., `ProductInvoiceView.vue`).
- `UNROUTED_BUT_INTENTIONAL`: Views or components rendered dynamically or as sub-components within routed views.
- `POTENTIALLY_UNUSED_PRODUCTION`: Code candidates requiring reference analysis before any action.

---

## 3. Delete-Safe Proof Matrix

Before any file can be marked `DELETE_SAFE`, all 11 conditions must be satisfied:

1. **Not Imported**: Not referenced via PHP `use`/`require` or JS `import`/`require`.
2. **Not Routed**: Not bound in `api/routes/api.php` or `Frontend/src/router/index.js`.
3. **Not Required by Build**: Not included in `vite.config.js`, `composer.json`, `package.json`, or build scripts.
4. **Not Referenced by Tests**: Not invoked directly or indirectly by PHPUnit or integration tests.
5. **Not Required by Migration History**: Not part of `api/database/migrations/`.
6. **Not Part of Current Documentation Authority**: Not linked in current operational manuals or architecture guides.
7. **Not Required as Unique Certification Evidence**: Historical conclusions already captured in documentation.
8. **Regenerable if Generated**: Any generated cache or build output can be cleanly recreated.
9. **Not Business/User Data**: Not real customer data, business transactions, or customer receipts.
10. **Not Secret Material**: Does not contain private credentials, API keys, or certificates requiring secure shredding.
11. **Git Worktree Status Verified**: Tracked vs untracked status clearly documented in the cleanup manifest.

---

## 4. Risk Level Definitions

| Risk Level | Definition | Scope / Examples |
|---|---|---|
| `LOW_RISK` | One-off scratch scripts, temporary files, build caches, generated discovery scripts | Safe for automated cleanup in Prompt 0.2 |
| `MEDIUM_RISK` | Historical certification documents, JSON evidence registries, legacy view templates | Archived to `docs/archive/` in Prompt 0.2; zero deletion |
| `HIGH_RISK` | Production code, database migrations, automated tests, database backups | Strictly protected; NO deletion or modification permitted |

---

## 5. Summary

This policy establishes complete safety and traceability for Phase 0. Prompt 0.2 will execute non-destructive archiving and safe disposal of low-risk scratch scripts strictly according to this policy.
