# HBOS Phase 3: Dashboard Scope Contract Specification (SRT-GAP-005)

## 1. Primary Gap Identity & Ownership
- **Gap ID:** `SRT-GAP-005`
- **Gap Title:** Executive Dashboard Multi-Branch Filter & Role Metric Gate
- **Linked Requirement:** `REQ-0026` (Executive Dashboard & Store KPIs)
- **Primary Owning Phase:** Phase 3

---

## 2. Dashboard Metric Matrix & Security Scoping

| Metric / Section | Owner (Single-Store) | Owner (Multi-Branch: All Stores) | Owner (Multi-Branch: Specific) | Branch Manager | Cashier / Salesperson | Backend Security Enforcement |
|---|---|---|---|---|---|---|
| **Sales Today** | Main Store Total | Chain Aggregate | Branch Total | Assigned Branch Total | Own Personal Sales | BACKEND_SCOPE_SAFE |
| **Sales Count** | Main Store Count | Chain Aggregate | Branch Count | Assigned Branch Count | Own Sale Count | BACKEND_SCOPE_SAFE |
| **Gross Profit** | Main Store Profit | Chain Aggregate | Branch Profit | **OMITTED (Backend)** | **OMITTED (Backend)** | BACKEND_SCOPE_SAFE |
| **Margin %** | Main Store Margin | Chain Aggregate | Branch Margin | **OMITTED (Backend)** | **OMITTED (Backend)** | BACKEND_SCOPE_SAFE |
| **Operating Position** | Main Store Position | Chain Aggregate | Branch Position | **OMITTED (Backend)** | **OMITTED (Backend)** | BACKEND_SCOPE_SAFE |
| **Receivables (Khata)** | Main Store Receivables | Chain Aggregate | Branch Receivables | Assigned Branch | **OMITTED (Backend)** | BACKEND_SCOPE_SAFE |
| **Payables (Suppliers)**| Main Store Payables | Chain Aggregate | Branch Payables | **OMITTED (Backend)** | **OMITTED (Backend)** | BACKEND_SCOPE_SAFE |
| **Branch Comparison** | **HIDDEN (Frontend)** | Chain Comparison | Selected Branch | **OMITTED (Backend)** | **OMITTED (Backend)** | BACKEND_SCOPE_SAFE |
| **Stock Alerts** | Main Store Low Stock | Chain Aggregate | Branch Alerts | Assigned Branch Alerts | **OMITTED (Backend)** | BACKEND_SCOPE_SAFE |

---

## 3. Branch Selector & Aggregation Contract
1. **Single-Store Owner:**
   - Active branch count = 1.
   - Header store switcher: **HIDDEN**.
   - Dashboard branch filter: **HIDDEN**.
   - Scope is implicitly the Main Store (`is_primary = true`).
2. **Multi-Branch Owner:**
   - Active branch count $\ge 2$.
   - Header store switcher & Dashboard filter: **VISIBLE**.
   - Scope options: "All Stores" (`branch_id=all`) or individual active branch IDs.
   - Canonical Frontend Encoding: `branch_id=all` (Backend compatibility: omitted `branch_id` preserved as fallback).
   - Zero magic IDs: Aggregate scope never queries `branch_id=0`.
3. **Branch Manager:**
   - Store switcher / dropdown: **HIDDEN**.
   - Scope: Fixed permanently to assigned branch.
   - Backend returns `HTTP 403 Forbidden` if Manager requests `branch_id=all` or another branch.
4. **Cashier / Salesperson:**
   - Direct POS landing.
   - Store switcher: **HIDDEN**.
   - Dashboard is hidden from navigation.
