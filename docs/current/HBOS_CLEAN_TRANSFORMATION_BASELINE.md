# HBOS Clean Transformation Baseline Snapshot

**Program**: SkyraSoft HBOS Small Retail Transformation Program  
**Phase**: Phase 0 — Repository Cleanup & Baseline Normalization  
**Document**: Authoritative Source-Derived Technology & Architecture Baseline  
**Date**: October 3, 2026  
**Status**: CERTIFIED BASELINE FOR PHASE 1–10 TRANSFORMATION

---

## 1. Executive Summary

This document captures the exact, source-derived technology snapshot of the cleaned and normalized HBOS repository. All metrics are established directly from runtime code, configuration manifests, and passing test suites.

---

## 2. Technology Stack & Runtime Baseline

| Component / Layer | Technology / Version | Verification Source |
|---|---|---|
| **Backend Framework** | **Laravel Framework 12.67.0** | `php artisan --version` |
| **PHP Runtime** | **PHP 8.2+ (x64)** | `php -v` |
| **Database Engine** | **MySQL 8.0+ / MariaDB** (InnoDB, UTF8mb4) | `api/config/database.php` |
| **API Architecture** | RESTful JSON API (`/api/v1/*`, 147 routes) | `api/routes/api.php` |
| **Authentication & Tokens**| Laravel Sanctum (Bearer Token / Token Hash) | `api/app/Http/Controllers/Api/V1/AuthController.php` |
| **Frontend Framework** | **Vue.js 3.4.x (Composition API)** | `Frontend/package.json` |
| **Frontend Build Tool** | **Vite 8.2.1** | `Frontend/vite.config.js` |
| **State Management** | **Pinia Store** | `Frontend/src/stores/` |
| **Desktop Shell** | **Tauri Wrapper Config** (`CONFIG_PRESENT`, packaging deferred to Phase 8) | `desktop/tauri.conf.json` |
| **Mobile Shell** | **Capacitor Wrapper Config** (`CONFIG_PRESENT`, packaging deferred to Phase 8) | `android/capacitor.config.json` |
| **Automated Testing** | **PHPUnit 10.x / Laravel Test Runner** | `api/phpunit.xml` |

---

## 3. Core Architecture & Data Invariants

1. **Multi-Tenancy Model**: Single database, row-level tenant scoping via `business_id` enforced across all Eloquent queries via global scopes and `ActiveBusinessScope`.
2. **Branch Scoping**: Multi-branch support with explicit operational boundaries; branch managers scoped to assigned branches; central financial accounts masked from branch operators.
3. **Role-Based Access Control**: Source-derived technical roles (`business_owner`, `branch_manager`, `salesperson`, `accountant`, `inventory_manager`); final V1 product role model normalization belongs to Phase 1.
4. **Idempotency & Financial Conservation**: Transactional mutation locks, strict non-negative inventory constraints, and account-movement cash-flow ledger attribution for customer khata and supplier balances (without full double-entry GL).

---

## 4. Repository Asset Totals

- **Total Tracked & Support Files Scanned**: 722 files (excluding `vendor/`, `node_modules/`, `dist/`, build caches)
- **Active / Reference Documentation**: 39 markdown documents across `current/`, `operations/`, `architecture/`, `release/`
- **Archived Historical Documentation**: 28 markdown files in `docs/archive/`
- **Archived JSON Evidence Registries**: 121 JSON files in `docs/archive/evidence/`
- **Automated Feature Test Files**: 67 test suites in `api/tests/`
- **Database Schema Migrations**: 66 migration files in `api/database/migrations/`
- **Eloquent Models**: 33 models in `api/app/Models/`
- **Backend API Controllers**: 32 controllers in `api/app/Http/Controllers/`
- **Frontend Vue Views**: 40 views in `Frontend/src/views/`
- **Frontend Vue Components**: 9 components in `Frontend/src/components/`
- **Active Scratch Tools & Manifests**: 11 files in `scratch/`

---

## 5. Certified Baseline Execution Results

- **Backend Automated Test Suite**: **391 passed (1741 assertions) [0 failures, 0 skips, Duration: 71.97s]**
- **Frontend Production Build**: **171 modules transformed, 0 errors, built in 1.67s**
- **Total Unauthorized Code Deletions**: **0**
- **Total Production Behavior Changes**: **0**
