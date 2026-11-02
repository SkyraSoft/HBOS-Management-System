# SKYRASOFT HBOS PUBLIC ACCOUNT & ONBOARDING SUPPORT RUNBOOK
## AUTHORITATIVE SUPPORT PROCEDURES FOR SELF-SERVICE USERS AND TENANT ISOLATION

---

### 1. Self-Service Registration & Onboarding Inquiries

#### Scenario 1: User encounters duplicate email error during registration
- **Root Cause**: The email address is already registered globally in the `users` table.
- **Support Action**:
  1. Advise the user that an account already exists with that email address.
  2. Direct user to **Sign In** or use **Forgot Password** at `/login`.
  3. Inform the user that an existing account can be added to new branches or businesses without registering a new email.

#### Scenario 2: Signup fails due to password mismatch or complexity
- **Validation Rules**: Password must be at least 8 characters and match `password_confirmation`.
- **Support Action**: Guide user to enter matching 8+ character passwords.

---

### 2. Password Reset & Account Recovery Procedures

#### Self-Service Password Reset Flow:
1. User clicks **Forgot Password?** on the login screen.
2. User submits their registered email address (`POST /api/v1/auth/forgot-password`).
3. Server generates a cryptographically secure token with a 60-minute expiration window.
4. User enters token and new password (`POST /api/v1/auth/reset-password`).
5. **Security Guarantee**: Upon password reset, all active Sanctum tokens are invalidated across all devices, requiring the user to re-authenticate.

---

### 3. Multi-Device Session Invalidation & Account Deactivation

#### Remote Logout Across All Terminals:
- If a cashier or business owner suspects credential compromise on a POS counter, they can invoke **Logout All Devices** from the User Profile or Settings screen (`POST /api/v1/auth/logout-all-devices`).
- This immediately purges all `personal_access_tokens` for that user in the database.

#### Account Deactivation:
- When a business owner sets `is_active = false` on an employee record, subsequent API requests by that employee return `HTTP 403 Forbidden` (`"Your account has been deactivated."`).
- Historical transactions and audit logs authored by that user remain intact for accounting integrity.

---

### 4. Tenant Data Isolation Incident Verification Checklist

If a customer inquires about data sovereignty or isolation:
1. Verify that all catalog and transaction records contain `business_id = {customer_business_id}`.
2. Confirm that the customer's desktop client is running an isolated local IndexedDB instance (`HBOS_Store_Biz_{customer_business_id}`).
3. Verify that `TenantScope` prevents any query from fetching foreign tenant rows.
