# HBOS (Hyperlocal Business Operating System) — Release Notes
## Version: v1.0.0 Release Candidate — Final Verification Passed
*Build Checkpoint: Phase 10 — Prompt 4/4 (Final Release Verification / Project Completion)*

> [!NOTE]
> This release candidate has completed all 10 frozen roadmap phases and passed comprehensive adversarial security, concurrency, idempotency, edge-case, and disaster recovery verification drills.
> Acceptance Criteria AC-10.01 through AC-10.40 have achieved 40/40 PASS.

---

## 1. Executive Summary

HBOS (Hyperlocal Business Operating System) is a multi-tenant, multi-branch retail and retail-enterprise operating system engineered for high-integrity inventory, POS sales, customer ledger (Khata), vendor payables, and branch financial operations.

---

## 2. Core Functional Modules

- **Phase 1 — Multi-Tenancy & RBAC**:
  Strict team-scoped multi-tenancy (`ResolveActiveBusiness`), branch isolation, canonical roles (`Business Owner`, `Branch Manager`, `Salesperson`), and Last Owner safety guarantee.
- **Phase 2 — Product Catalog & Master Data**:
  Tenant-scoped SKU and barcode uniqueness, hierarchical categories and subcategories, brand management, and multi-variation support.
- **Phase 3 — Inventory & Stock Ledger**:
  Strict double-entry stock ledger, branch transfers, automated stock level alerts, and branch inventory balance authority (`branch_inventories.quantity_on_hand`).
- **Phase 4 — Purchases & Suppliers**:
  Supplier profiles, purchase orders, goods receipt workflows, and supplier ledger reconciliation.
- **Phase 5 — Sales & POS**:
  High-speed POS interface, barcode scanning, business-scoped sequential invoice numbering, idempotent transaction submission, and line-item returns.
- **Phase 6 — Customers & Khata (Udhaar)**:
  Customer credit limits, immutable double-entry Khata ledger, partial payment allocation, and idempotent payment processing.
- **Phase 7 — Cash, Bank & Expenses**:
  Multi-account financial management (cash drawers, bank accounts, digital wallets), inter-account transfers, and branch-isolated expense tracking.
- **Phase 8 — Dashboard & Reporting**:
  Real-time KPI computation, aggregated financial statements, inventory velocity (fast/slow movers), and tenant-isolated branch drilldowns.
- **Phase 9 — Audit, Settings & System Governance**:
  Comprehensive mutation audit logging (`activity_log`), currency change invariants, business settings management, and user lifecycle governance.
- **Phase 10 — End-to-End Hardening & QA**:
  Zero legacy tenant fallbacks, frontend 403 authorization fix, dynamic base URL configuration, rate limiting, security headers, and release health diagnostics.

---

## 3. Key Hardening Enhancements in v1.0.0-RC

1. **Frontend 403 Authorization Integrity (AC-10.32)**:
   HTTP 403 Forbidden responses preserve user tokens and sessions while presenting permission-denied UI states; only HTTP 401 triggers session termination.
2. **Dynamic API Configuration (AC-10.31)**:
   Replaced hardcoded localhost endpoints with dynamic `import.meta.env.VITE_API_BASE_URL` with relative `/api/v1` fallback.
3. **Strict Active Business Scope (AC-10.14)**:
   Eliminated all legacy `$request->user()->business_id` controller fallbacks. Multi-business users must explicitly supply `X-Business-ID` header; missing active business context explicitly fails with HTTP 400.
4. **Audit History Preservation (AC-10.09)**:
   Prevented deletion of businesses containing audit records (`activity_log`) with HTTP 422 to protect audit provenance.
5. **Authentication Rate Limiting (AC-10.13)**:
   Protected `/api/v1/auth/login` and `/api/v1/auth/register` with `throttle:6,1` brute-force mitigation.
6. **Release Health Endpoint (AC-10.12)**:
   Implemented `GET /api/v1/health` providing database connectivity checks without exposing database credentials, versions, or SQL errors.
7. **Security Headers (AC-10.39)**:
   Attached `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, and `Referrer-Policy: strict-origin-when-cross-origin` to all API responses.
8. **Canonical Roles Alignment (AC-10.10)**:
   Synchronized all UI components to strictly recognize the 3 canonical roles: `Business Owner`, `Branch Manager`, and `Salesperson`.
