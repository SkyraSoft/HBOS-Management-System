# HBOS Retail Identity & Audit Attribution Foundation

**Program**: SkyraSoft HBOS Retail Platform & Workforce Integrity Program  
**Prompt**: Prompt 2 of 5 — Controlled Implementation  
**Status**: COMPLETE / CERTIFIED  

---

## 1. Architectural Scope

This document establishes the actor-attribution, tenant-isolation, and audit-event logging foundation required by Prompt 2 Checkpoint 2.4. It addresses the foundational portions of:
- **RETAIL-GAP-012**: Historical Actor Attribution
- **RETAIL-GAP-013**: Security Event Categorization
- **RETAIL-GAP-014**: Platform Admin / Business Owner Separation

*Note: Role-specific application views, Super Owner executive dashboards, View-As preview mode, and Owner Action Requests belong to Prompt 3 and are explicitly deferred.*

---

## 2. Canonical Attribution Context

Every material operational mutation captured by HBOS includes sufficient structural metadata to deterministically reconstruct the exact human provenance of the action:

```
┌────────────────────────────────────────────────────────┐
│             Canonical Mutation Context                 │
├────────────────────────────────────────────────────────┤
│ 1. Business ID      : Resolved tenant authority        │
│ 2. Branch ID        : Operational location / outlet    │
│ 3. Causer ID        : Authenticated human user         │
│ 4. Role at Action   : Snapshot of user role at time    │
│ 5. Timestamp        : Immutable UTC created_at         │
│ 6. Subject Entity   : Model class & Primary Key ID     │
│ 7. Action / Event   : Semantic action verb             │
│ 8. Sanitized Props  : Delta properties (keys redacted) │
└────────────────────────────────────────────────────────┘
```

---

## 3. Material Mutation Matrix

The following 21 material mutations have been verified and linked to human actor attribution:

| Mutation | Primary Entity | Actor Provenance Column | Audit Event | Causer Logged |
|---|---|---|---|---|
| Sale Create | `Sale` | `user_id` / `created_by` | `sale.created` | Yes |
| Sale Cancel | `Sale` | `cancelled_by` | `sale.cancelled` | Yes |
| Sale Return | `SaleReturn` | `user_id` | `sale.returned` | Yes |
| Customer Payment | `CustomerPayment` | `user_id` | `customer_payment.created` | Yes |
| Payment Reversal | `CustomerPayment` | `cancelled_by` | `customer_payment.reversed` | Yes |
| Purchase Create | `Purchase` | `user_id` | `purchase.created` | Yes |
| Purchase Cancel | `Purchase` | `cancelled_by` | `purchase.cancelled` | Yes |
| Supplier Payment | `SupplierPayment` | `user_id` | `supplier_payment.created` | Yes |
| Supplier Return | `SupplierReturn` | `user_id` | `supplier_return.created` | Yes |
| Stock Adjustment | `InventoryAdjustment` | `adjusted_by` | `inventory.adjusted` | Yes |
| Stock Transfer | `InventoryTransfer` | `transferred_by` | `inventory.transferred` | Yes |
| Expense Create | `Expense` | `created_by` | `expense.created` | Yes |
| Expense Void | `Expense` | `voided_by` | `expense.voided` | Yes |
| Financial Transfer | `AccountTransfer` | `transferred_by` | `financial.transferred` | Yes |
| Employee Create | `User` | N/A (Admin) | `user.created` | Yes |
| Employee Role Change | `User` | N/A (Admin) | `user.role_changed` | Yes |
| Branch Assignment | `User` | N/A (Admin) | `user.branch_assigned` | Yes |
| Employee Deactivate | `User` | N/A (Admin) | `user.deactivated` | Yes |
| Business Profile Update | `Business` | N/A (Owner) | `business.updated` | Yes |
| Branch Create | `Branch` | N/A (Owner) | `branch.created` | Yes |
| Branch Deactivate | `Branch` | N/A (Owner) | `branch.deactivated` | Yes |

---

## 4. Role-at-Action Immutability

When an employee undergoes a role change (for example, promoting a `Salesperson` to `Branch Manager`):
1. **Future Operations**: The employee gains the operational permissions of their new role for all subsequent actions.
2. **Historical Integrity**: All previously created sales, receipts, movements, and audit log entries remain completely untouched.
   - The historical `Sale` still reflects `user_id` pointing to the human actor.
   - The historical `activity_log` record preserves `properties.role_at_action = 'Salesperson'`.
   - No historical records are rewritten, preserving strict legal and accounting auditability.

---

## 5. Security Event Categorization & Secret Redaction

HBOS organizes governance events into standard audit categories:
- `AUTH_LOGIN`
- `AUTH_LOGOUT`
- `PASSWORD_CHANGED`
- `PASSWORD_RESET`
- `USER_CREATED`
- `USER_DEACTIVATED`
- `ROLE_CHANGED`
- `BRANCH_ASSIGNMENT_CHANGED`
- `BUSINESS_PROFILE_CHANGED`
- `BRANCH_CREATED`
- `BRANCH_DEACTIVATED`

### Strict Secret Redaction
`AuditService::sanitizeProperties` performs a recursive scan across all log properties before persistence. Any key matching credentials, tokens, or private secrets (`password`, `password_confirmation`, `invitation_token`, `remember_token`, `access_token`, `secret_key`, `pin`, `cvv`) is automatically replaced with `[REDACTED]`. Plaintext secrets or tokens are never persisted to the audit log table.

---

## 6. Platform Super Admin Separation

To protect multi-tenant integrity:
- **Platform Super Admin is NOT a tenant role**: It is excluded from the customer Spatie team role registry (`RolesAndPermissionsSeeder`).
- **Classification**: Marked as `PLATFORM_ADMIN_DOMAIN_RESERVED`.
- **Tenant Role Assignment Boundary**: `UserGovernanceService` rejects any attempt to assign a role outside the 5 canonical tenant roles (`Business Owner`, `Branch Manager`, `Salesperson`, `Accountant`, `Inventory Manager`). A tenant owner cannot grant themselves or their staff cross-tenant or platform-level privileges.
