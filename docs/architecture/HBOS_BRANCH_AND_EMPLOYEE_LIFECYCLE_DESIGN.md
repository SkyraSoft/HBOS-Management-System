# HBOS Branch and Employee Lifecycle Design
**SkyraSoft HBOS Retail Platform & Workforce Integrity Program**

---

## 1. Branch Architecture & Lifecycle

### Entity Model Target
The `branches` table represents physical retail locations and operational stores:
- `id` (PK)
- `business_id` (FK to businesses)
- `name` (e.g., "Peshawar Saddar Branch")
- `code` (e.g., "PEW-01", unique within tenant)
- `is_primary` (boolean flag)
- `phone` (store contact phone)
- `email` (store contact email)
- `address` (street address)
- `city` (location city)
- `manager_id` (nullable FK to users, indicating assigned Branch Manager)
- `status` ('active' | 'inactive')

### Branch Creation Lifecycle Steps
1. **Creation**: Super Owner submits branch name, code, address, contact.
2. **Auto-Provisioning**:
   - Creates a dedicated financial cash account (`FinancialAccount`: "Cash - [Branch Code]")
   - Initializes zero-balance stock ledger rows in `branch_inventories` for all catalog products
   - Links the creating owner to `branch_user` pivot.
3. **Manager & Staff Assignment**:
   - Owner assigns a Branch Manager (`branches.manager_id`).
   - Staff members are assigned via the `branch_user` pivot table.
4. **Deactivation & Invariant Safeguards**:
   - A branch with active inventory or historic ledger transactions cannot be hard deleted.
   - Deactivating a branch blocks new POS checkouts while preserving complete historical ledger records.

---

## 2. Employee Identity & Profile Architecture

### Auth User vs Staff Profile
HBOS maintains a clean unified user model inside `users` enhanced with structured staff attributes (implemented via GAP-029 and Phase 9):
- `name`, `email`, `phone` (Identity & contact)
- `password`, `is_active`, `last_login_at` (Authentication & Security)
- `business_id`, `branch_id` (Default tenant & primary branch assignment)
- `role` & Spatie team roles (Governance & permission scopes)
- `salary_amount`, `payment_cycle`, `next_payment_date` (Compensation & HR)

### Employee Recruitment & Password Lifecycle
1. **Creation**: Owner/Manager enters Name, Email, Phone, Role, Assigned Branch, and Salary.
2. **Account Provisioning**:
   - User account created with `is_active = true`.
   - Spatie team role assigned in current `business_id`.
   - Linked to `branch_user` pivot.
3. **Password Setup Flow**:
   - Option A (Preferred): System generates an activation/setup token; employee receives setup link and creates their own password.
   - Option B (Offline/Direct): Admin assigns initial temporary password; employee is flagged with `must_change_password = true` and forced to update on first login.
4. **Deactivation & Token Revocation**:
   - Toggling `is_active = false` immediately deletes all active Sanctum tokens (`$user->tokens()->delete()`) to terminate active sessions instantly across all devices.

---

## 3. Privilege Escalation Prevention Matrix

| Actor Role | Can Create Owner? | Can Create Manager? | Can Create Staff? | Can Change Own Role? | Can Deactivate Last Owner? |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Business Super Owner** | YES | YES | YES | NO (Self-Governance Invariant) | NO (Last Owner Invariant) |
| **Branch Manager** | NO | NO | YES (Cashier/Staff) | NO | NO |
| **Salesperson / Cashier**| NO | NO | NO | NO | NO |
| **Accountant** | NO | NO | NO | NO | NO |
| **Inventory Manager** | NO | NO | NO | NO | NO |
