# HBOS Employee Account & Profile Flow

**Program**: SkyraSoft HBOS Retail Platform & Workforce Integrity Program  
**Prompt**: Prompt 2 of 5 — Controlled Implementation  
**Status**: COMPLETE / CERTIFIED  

---

## 1. Human Actor Identity Principle

HBOS strictly prohibits generic or shared counter credentials (such as a shared "Cashier" terminal login). Every staff member operates under their own discrete user account:

```
[ Human Staff Member ]
          │
          ▼
   User Account (email, phone, personal password)
          │
          ▼
   Business Membership (business_user)
          │
          ▼
   Branch Membership(s) (branch_user)
          │
          ▼
   Assigned Role (Business Owner, Branch Manager, Salesperson, Accountant, Inventory Manager)
          │
          ▼
   Canonical Permissions (Team-scoped via Spatie Laravel Permission)
```

Every subsequent transaction, sale checkout, stock movement, expense voucher, or approval captures the human user's ID, ensuring full organizational integrity and non-repudiation.

---

## 2. Employee Lifecycle Workflows

### 2.1 Employee Creation & Invitation Flow

```
[ Business Owner / Manager ]
             │
             ▼
   Fill Employee Form (Name, Email, Phone, Role, Branch, Employee Code, Title, Salary)
   Check "Send Invitation / Generate Setup Link"
             │
             ▼
   POST /api/v1/users
             │
             ├──► 1. Generates 64-char cryptographically secure token (bin2hex(random_bytes(32)))
             ├──► 2. Hashes token using bcrypt (Hash::make()) and stores in users.invitation_token
             ├──► 3. Sets users.invitation_expires_at = now() + 48 hours
             ├──► 4. Attaches user to active business_user and branch_user
             ├──► 5. Assigns tenant role (team-scoped to business)
             └──► 6. Returns user resource + plaintext invitation_token in API response
             │
             ▼
   Display Invitation Setup Modal in UI
   (Copyable Setup Link: /accept-invitation?token={token})
```

### 2.2 Token Setup & Account Activation
1. **Operator / Employee Access**: Employee navigates to setup URL or provides token.
2. **Endpoint**: `POST /api/v1/auth/accept-invitation`
   - Payload:
     ```json
     {
       "token": "4a7f9b8c2d...",
       "password": "SecurePassword123!",
       "password_confirmation": "SecurePassword123!"
     }
     ```
3. **Verification**:
   - Locates user with non-null `invitation_token` and `invitation_expires_at > now()`.
   - Verifies provided token against hashed token using `Hash::check()`.
   - Enforces password length and complexity.
4. **Activation**:
   - Updates `users.password = Hash::make($password)`.
   - Clears `users.invitation_token = null` and `invitation_expires_at = null` (**Single-Use Invariant**).
   - Generates Sanctum personal access token and returns `{ access_token, user }`.
   - Subsequent attempts to reuse the token fail with HTTP 422.

---

## 3. Employee Profile Model

The employee profile directly extends the existing `User` model rather than introducing unneeded table overhead:

| Field Name | Type | Description | Visibility & Governance |
|---|---|---|---|
| `name` | string | Full legal or display name | Editable by Owner, Manager, or Self |
| `email` | string | Authenticated login email | Unique across platform, editable by Owner |
| `phone` | string | Contact phone number | Editable by Owner, Manager, or Self |
| `employee_code` | string | Tenant-scoped staff ID (e.g., `EMP-001`) | Business-scoped uniqueness, editable by Owner |
| `job_title` | string | Organizational designation | Editable by Owner/Manager |
| `hire_date` | date | Start of employment | Editable by Owner |
| `salary_amount` | decimal:2 | Agreed recurring wage/salary | Protected, Owner only |
| `payment_cycle` | string | `monthly`, `bi-weekly`, `weekly` | Protected, Owner only |
| `next_payment_date`| date | Projected payroll date | Protected, Owner only |

---

## 4. Privilege Escalation & Security Governance

1. **Role Assignment Hierarchy**:
   - **Salesperson**: Forbidden from creating users, viewing user administrative panels, or assigning roles.
   - **Branch Manager**: Forbidden from creating or promoting users to `Business Owner` or assigning platform-level authority.
   - **Business Owner**: Authorized to manage tenant staff, assign roles, and alter employment profiles.
2. **Self-Service Restrictions**:
   - Employees updating their own profile via `PUT /api/v1/profile` can update contact phone and avatar.
   - Self-mutation of `role`, `permissions`, `branch_id`, `salary_amount`, or `business_id` is strictly blocked by the backend validator.
3. **Non-Destructive Deactivation & Token Revocation**:
   - Deactivating an employee sets `users.is_active = false`.
   - The backend immediately executes `$user->tokens()->delete()`, instantly revoking all active Sanctum sessions across all devices.
   - Any API request bearing an existing bearer token receives HTTP 401 Unauthenticated.
   - Login attempts receive HTTP 403 Forbidden: `"Your account is inactive. Please contact your business administrator."`
   - Historical sales, cash collections, inventory transfers, and audit logs remain linked to the user's permanent ID.
