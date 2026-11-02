# HBOS Role Scope Delta Analysis

**Program**: SkyraSoft HBOS Small Retail Transformation Program  
**Phase**: Phase 1 — Prompt 2 of 3  
**Status**: ROLE DELTA SPECIFICATION  

---

## 1. Active V1 Customer Roles

1. **Business Owner** (UI: Business Super Owner): Unrestricted universal operational and financial authority across all stores.
2. **Branch Manager**: Scoped strictly to assigned branch; local stock receiving, petty cash expenses, and branch staff oversight; central profits and cross-branch data blocked.
3. **Salesperson** (UI: Cashier / Salesperson): POS sales, customer lookup, shift cash count; cost prices, profit margins, and settings hidden.

## 2. Technical Compatibility Roles

- `Accountant` and `Inventory Manager` remain seeded in source for compatibility but are hidden from V1 customer invitation UI.
