# HBOS Branch Management Operator Flow

**Program**: SkyraSoft HBOS Retail Platform & Workforce Integrity Program  
**Prompt**: Prompt 2 of 5 — Controlled Implementation  
**Status**: COMPLETE / CERTIFIED  

---

## 1. Overview & Architecture

HBOS supports multi-branch retail operations with strict tenant isolation, business-scoped uniqueness, and immutable historical preservation.

### Core Architectural Decisions
1. **Business-Scoped Branch Code Uniqueness**:
   - Branch codes (e.g. `MAIN`, `STORE-01`, `NORTH`) are unique within a single business tenant via the composite unique index `unique(['business_id', 'code'])`.
   - Distinct business tenants can legitimately use the same branch code without conflicts or collisions.
2. **Cardinality Architecture (`BRANCH_USER_MEMBERSHIP_PLUS_ROLE`)**:
   - Rather than constraining each branch to a single rigid `manager_id` foreign key column, HBOS utilizes the existing `branch_user` pivot table (`user_id`, `branch_id`, `business_id`).
   - This cleanly and securely supports:
     - Multiple managers assigned to a single high-volume branch.
     - A single roving or regional manager assigned to oversee multiple branches.
     - Automatic assignment of creating business owners to newly created branches.
3. **Lazy Inventory Initialization**:
   - Zero-balance inventory records are NOT eagerly spawned across thousands of catalog items upon branch creation. Branch stock records are created lazily upon physical stock receipt, inventory transfer, or first cycle count.
4. **Non-Destructive Soft Deactivation**:
   - Operational branches with historical transactions (sales, purchases, movements, staff logs) cannot be hard-deleted.
   - Deactivating a branch transitions `status = 'inactive'`, prohibiting new sales or movements while preserving all historical attribution and financial records.
5. **Primary Branch Protection**:
   - Every business must maintain exactly one primary branch. Deactivating or deleting the primary branch without explicit replacement is blocked with HTTP 422.

---

## 2. Branch Lifecycle State Machine

```
              ┌─────────────────────────┐
              │      Owner / Auth       │
              │  POST /api/v1/branches  │
              └────────────┬────────────┘
                           │
                           ▼
              ┌─────────────────────────┐
              │     Branch Created      │
              │      status: active     │
              │  Creating Owner Linked  │
              │  Cash Drawer Provisioned│
              └────────────┬────────────┘
                           │
             ┌─────────────┴─────────────┐
             ▼                           ▼
┌─────────────────────────┐ ┌─────────────────────────┐
│     Update Profile      │ │       Deactivate        │
│   PUT /branches/{id}    │ │   status: 'inactive'    │
│  (Name, Phone, Address) │ │ Historical Data Preserved│
└─────────────────────────┘ └─────────────────────────┘
```

---

## 3. Operator Workflows

### 3.1 Creating a New Branch
1. **Authorization**: Calling actor must possess `manage branches` permission or hold the `Business Owner` role.
2. **Context Resolution**: The API extracts the active tenant ID from the authenticated session / `X-Business-Id` header. Client-supplied foreign `business_id` parameters are ignored to prevent cross-tenant spoofing.
3. **Validation**:
   - `name`: Required string (max 255).
   - `code`: Required string, validated for uniqueness within the active business:
     ```php
     Rule::unique('branches', 'code')->where('business_id', $activeBusinessId)
     ```
   - `phone`, `email`, `address`, `city`: Optional profile attributes.
4. **Execution**:
   - Branch record is persisted.
   - Creating owner is automatically linked in `branch_user` pivot with `['business_id' => $activeBusinessId]`.
   - Branch-specific cash drawer account (`Main Cash Drawer`) is automatically provisioned if cash tracking is active.
   - Audit log event `branch.created` is emitted with causer attribution.

### 3.2 Updating Branch Profile
- **Endpoint**: `PUT /api/v1/branches/{id}`
- **Security Check**: The branch must belong to the active business context; otherwise HTTP 404 is returned.
- **Code Updates**: If `code` is changed, uniqueness within the tenant business is verified.
- **Primary Flag**: If `is_primary` is set to `true`, any previous primary branch within the business has its `is_primary` flag atomically reset to `false`.

### 3.3 Deactivating a Branch
- **Endpoint**: `PUT /api/v1/branches/{id}` with `{ "status": "inactive" }`
- **Guards**:
  - If the branch has `is_primary === true`, the request is rejected with HTTP 422: `"Cannot deactivate the primary branch. Designate a new primary branch first."`
- **Effects**:
  - `status` set to `inactive`.
  - Cashiers and salespersons attempting to record sales or stock transfers in an inactive branch are rejected.
  - Historical reports, past sales, and stock ledger entries continue to accurately reflect the branch name and transactions.

---

## 4. Cross-Tenant Isolation Guarantees

- **No Cross-Tenant Read/Write**: Branch queries strictly enforce `where('business_id', $businessId)`. An owner of Tenant A querying or updating Branch ID belonging to Tenant B receives HTTP 404.
- **Code Portability**: Tenant A having branch code `MAIN` has zero impact on Tenant B having branch code `MAIN`. Both operate in complete independence.
