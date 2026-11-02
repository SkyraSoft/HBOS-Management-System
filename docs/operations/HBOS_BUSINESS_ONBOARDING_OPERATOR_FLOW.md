# HBOS Business Onboarding Operator Flow

**Program**: SkyraSoft HBOS Retail Platform & Workforce Integrity Program  
**Prompt**: Prompt 2 of 5 — Controlled Implementation  
**Status**: COMPLETE / CERTIFIED  

---

## 1. Overview & Architectural Principles

The HBOS Business Onboarding workflow establishes an organization's account and operational foundation without imposing premature administrative or regulatory burdens.

### Core Principles
1. **Minimal Initial Registration**: Initial signup captures only core identity (`Owner Name`, `Owner Email`, `Password`, `Password Confirmation`, `Business Name`, and `Owner Phone`). No billing cards, regulatory licenses, or branch details are required at signup.
2. **Staged Progressive Onboarding**: After account creation, the owner progresses through a 5-step wizard.
3. **Resumable State Machine**: Progress is persisted in the database via `onboarding_status` (`incomplete` vs `completed`) and `onboarding_step` (1 to 5). If an owner closes their browser or app, re-authenticating seamlessly resumes onboarding at the exact step without creating duplicate businesses.
4. **Zero Data Fabrication for Existing Tenants**: Legacy tenants are preserved without fabricated regulatory or address data.

---

## 2. Five-Stage Onboarding State Machine

```
[ POST /api/v1/auth/register ]
          │
          ▼
   Step 1: Account Created ─────────► (onboarding_status: 'incomplete', step: 2)
          │
          ▼
   Step 2: Business Identity ───────► (legal_name, address, city, country, tax_number)
          │                           [ POST /api/v1/onboarding/step, step: 2 ]
          ▼
   Step 3: Primary Branch ──────────► (branch_name, code, phone, location)
          │                           [ POST /api/v1/onboarding/step, step: 3 ]
          ▼
   Step 4: Operational Defaults ────► (currency, timezone, tax_rate, invoice_prefix)
          │                           [ POST /api/v1/onboarding/step, step: 4 ]
          ▼
   Step 5: Review & Activation ─────► (initialize cash drawer & defaults)
          │                           [ POST /api/v1/onboarding/step, step: 5 ]
          ▼
   Onboarding Complete ─────────────► (onboarding_status: 'completed', onboarding_completed_at)
          │
          ▼
   Route to Owner Dashboard
```

---

## 3. Step Specifications

### Step 1: Owner Account & Tenant Provisioning
- **Surface**: `/register` (Vue component: `RegisterView.vue`)
- **Backend API**: `POST /api/v1/auth/register`
- **Fields**:
  - `name`: Human owner name
  - `email`: Authenticated login email
  - `phone`: Mobile / contact phone (persisted to `users.phone`)
  - `password`: Account password (minimum 8 characters)
  - `password_confirmation`: Confirmation match
  - `business_name`: Initial business trading name
- **Database Mutation**:
  - Creates record in `businesses` with `onboarding_status = 'incomplete'`, `onboarding_step = 2`.
  - Creates record in `users` with `is_active = true`, `business_id = $business->id`.
  - Creates flagship branch placeholder (`MAIN`) and sets `is_primary = true`.
  - Attaches user to `business_user` and `branch_user`.
  - Assigns Spatie role `Business Owner` (team-scoped to `$business->id`).

### Step 2: Business Identity
- **Surface**: `/businesssetup1` (Vue component: `BusinessSetup1View.vue`)
- **Backend API**: `POST /api/v1/onboarding/step` with `{ "step": 2, ... }`
- **Fields**:
  - `name`: Display / trading name
  - `legal_name`: Registered corporate / legal entity name
  - `business_type`: Industry category (Retail, Supermarket, Pharmacy, General Store, etc.)
  - `phone`: Official business phone
  - `email`: Official public business email
  - `website`: Official website URL
  - `address`: Physical head office street address
  - `city`: City location
  - `country`: Country of operation (default: `Pakistan`)
  - `tax_number`: NTN, STRN, or VAT identifier
- **Persistence**: Saved directly to `businesses` table. `onboarding_step` advances to 3.

### Step 3: Primary Branch Configuration
- **Surface**: `/preferences` (Vue component: `PreferencesView.vue` Tab 1)
- **Backend API**: `POST /api/v1/onboarding/step` with `{ "step": 3, ... }`
- **Fields**:
  - `branch_name`: Name of flagship / primary retail branch (e.g., "Main Retail Outlet")
  - `branch_code`: Tenant-unique branch shortcode (e.g., "MAIN", "STORE-01")
  - `branch_phone`: Branch direct telephone
  - `branch_address`: Physical branch location
  - `branch_city`: Branch city
- **Persistence**: Updates the primary `branches` record for the business. `onboarding_step` advances to 4.

### Step 4: Business Operational Defaults
- **Surface**: `/preferences` (Vue component: `PreferencesView.vue` Tab 2)
- **Backend API**: `POST /api/v1/onboarding/step` with `{ "step": 4, ... }`
- **Fields**:
  - `currency`: Default transaction currency (e.g., `PKR`, `USD`, `EUR`)
  - `timezone`: Local operational timezone (e.g., `Asia/Karachi`)
  - `tax_rate`: Standard default VAT / Sales tax percentage
  - `invoice_prefix`: Invoice code prefix (e.g., `INV-`)
- **Persistence**: Saves currency and timezone to `businesses`, and settings to `settings` table. `onboarding_step` advances to 5.

### Step 5: Review & Ready
- **Surface**: `/completereview` (Vue component: `CompleteReviewView.vue`)
- **Backend API**: `POST /api/v1/onboarding/step` with `{ "step": 5 }`
- **Actions**:
  - Verifies presence of primary branch and required defaults.
  - Ensures default cash drawer account exists for primary branch (`Main Cash Drawer`).
  - Sets `businesses.onboarding_status = 'completed'`.
  - Sets `businesses.onboarding_completed_at = now()`.
  - Emits `business_onboarding_completed` audit log event.
  - Returns authenticated owner profile and redirects to main dashboard.

---

## 4. Resumption and Interruption Safety

1. **Client Disconnection**: If an owner drops off at Step 3, their account and business record remain intact.
2. **Re-authentication**:
   - The owner logs in via `/api/v1/auth/login`.
   - The client invokes `GET /api/v1/onboarding/status`.
   - The API inspects `business.onboarding_status`. If `incomplete`, it returns `{ "onboarding_status": "incomplete", "onboarding_step": 3 }`.
   - The frontend router automatically routes the owner to `/preferences` to complete the remaining steps.
3. **No Duplicate Business Creation**: Re-authenticating does NOT create a second business. All steps execute on the active business resolved from the authenticated owner context.
