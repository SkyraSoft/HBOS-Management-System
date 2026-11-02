# HBOS Business Onboarding Field Specification & Data Model
**SkyraSoft HBOS Retail Platform & Workforce Integrity Program**

---

## 1. Overview & Data Minimization Strategy
HBOS follows a strict **staged onboarding architecture** rather than an overwhelming single-form layout. It enforces data minimization: the initial free tier requires zero payment or billing details, asking only for essential operational identifiers.

---

## 2. Field Classification Taxonomy

| Field Name | Target Entity / Table | Classification | Stage / Step | Rationale & Governance |
| :--- | :--- | :--- | :--- | :--- |
| `owner_name` | `users.name` | **SIGNUP_REQUIRED** | Step 1 (Account) | Personal identity of the primary account holder. |
| `owner_email` | `users.email` | **SIGNUP_REQUIRED** | Step 1 (Account) | Primary authentication handle and password reset target. |
| `owner_password` | `users.password` | **SIGNUP_REQUIRED** | Step 1 (Account) | Minimum 8 characters, confirmed. |
| `owner_phone` | `users.phone` | **ONBOARDING_RECOMMENDED**| Step 1 (Account) | Account recovery and security verification handle. |
| `business_name` | `businesses.name` | **SIGNUP_REQUIRED** | Step 2 (Business) | Legal and commercial trading name of tenant. |
| `business_type` | `businesses.type` | **ONBOARDING_REQUIRED** | Step 2 (Business) | Industry classification (General Store, Grocery, Pharmacy, etc.). |
| `business_phone` | `businesses.phone` | **ONBOARDING_RECOMMENDED**| Step 2 (Business) | Public/customer inquiries contact number. |
| `business_email` | `businesses.email` | **OPTIONAL_BUSINESS_PROFILE**| Step 2 (Business) | Official support contact email. |
| `website` | `businesses.website` | **OPTIONAL_BUSINESS_PROFILE**| Step 2 (Business) | Commercial web link. |
| `country` | `businesses.country` | **ONBOARDING_REQUIRED** | Step 2 (Business) | Country of operation (defaults to Pakistan). |
| `currency` | `businesses.currency` | **ONBOARDING_REQUIRED** | Step 2 (Business) | Default monetary currency symbol/code (PKR, USD, AED, SAR). |
| `timezone` | `businesses.timezone` | **ONBOARDING_REQUIRED** | Step 2 (Business) | Base operating timezone for report date boundaries. |
| `primary_branch_name` | `branches.name` | **ONBOARDING_REQUIRED** | Step 3 (Branch) | Name of primary store (defaults to 'Main Branch'). |
| `primary_branch_code` | `branches.code` | **SYSTEM_DERIVED** | Step 3 (Branch) | Auto-generated code (e.g., MAIN, BR01). |
| `primary_branch_address`| `branches.address` | **ONBOARDING_RECOMMENDED**| Step 3 (Branch) | Street address of primary retail location. |
| `primary_branch_city` | `branches.city` | **ONBOARDING_RECOMMENDED**| Step 3 (Branch) | City of primary retail location. |
| `receipt_header` | `settings.value` | **ONBOARDING_RECOMMENDED**| Step 4 (Defaults) | Custom branding printed at the top of customer receipts. |
| `receipt_footer` | `settings.value` | **ONBOARDING_RECOMMENDED**| Step 4 (Defaults) | Thank-you or policy note at the bottom of receipts. |
| `invoice_prefix` | `settings.value` | **ONBOARDING_RECOMMENDED**| Step 4 (Defaults) | Prefix for invoice numbering (e.g., INV-). |
| `tax_registration_number`| `businesses.tax_number`| **REGULATORY_OPTIONAL**| Step 4 (Defaults) | NTN, STRN, or VAT tax registration ID. |
| `negative_stock_policy`| `settings.value` | **ONBOARDING_RECOMMENDED**| Step 4 (Defaults) | Policy for sales when stock is zero (default: block). |
| `billing_card_number` | None | **DO_NOT_COLLECT** | N/A | Never collected for free public distribution. |

---

## 3. Staged 5-Step Onboarding Flow

```
[ Step 1: Owner Account ]
        │ (Name, Email, Password, Phone)
        ▼
[ Step 2: Business Identity ]
        │ (Business Name, Type, Currency, Country, Timezone)
        ▼
[ Step 3: Primary Store / Branch ]
        │ (Branch Name, Code, Address, City)
        ▼
[ Step 4: Business Defaults ]
        │ (Receipt Header/Footer, Invoice Prefix, Tax NTN, Stock Policy)
        ▼
[ Step 5: Ready / Enter HBOS ]
        │ (Launch into Super Owner Executive Dashboard)
        ▼
[ Enter Dashboard ]
```

### Save/Resume & Re-entry Logic
- If a user completes Step 1 & 2 but disconnects before Step 4, HBOS stores completed step progress in `businesses.onboarding_stage`.
- Upon subsequent login, the frontend checks if onboarding is complete; if incomplete, it routes the owner directly back to the pending setup step.
