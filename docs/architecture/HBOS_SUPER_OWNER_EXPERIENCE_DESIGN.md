# HBOS Super Owner Experience, View-As Preview & Action Requests Design
**SkyraSoft HBOS Retail Platform & Workforce Integrity Program**

---

## 1. Executive Multi-Branch Dashboard
The Super Owner dashboard delivers a top-level command center for multi-store retail enterprises:

### Core Widgets & Analytical Visualizations
1. **Consolidated Financial Summary**:
   - Gross Revenue across all stores
   - Net Sales (deducting returns and discounts)
   - Total Cost of Goods Sold (COGS)
   - Gross & Net Profit Margin percentages
   - Time filters: Today, Yesterday, This Week, This Month, Last Month, Custom
2. **Branch Comparative Matrix**:
   - Store ranking by sales volume and ticket counts
   - Average Transaction Value (ATV) per branch
   - Contribution percentage to overall tenant profit
3. **Inventory & Critical Reorder Exposure**:
   - Aggregate inventory valuation at cost and retail price
   - Consolidated list of critical low-stock and out-of-stock items across all branches
4. **Cash, Receivables & Payables Position**:
   - Real-time customer Khata exposure (outstanding credit)
   - Total supplier payables
   - Total liquid cash and bank balances across all accounts

---

## 2. Read-Only "View As" Role & Branch Preview Mode

### Core Principles & Non-Impersonation
- The owner's actual authenticated identity and JWT/Sanctum token remain **completely intact**.
- No employee token or password hash is borrowed or fabricated.
- The owner requests a preview context: `preview_role_id` and `preview_branch_id`.

```text
Actual Authenticated Actor: Business Owner (Ahmed)
Preview Context: Role = Salesperson/Cashier, Branch = Peshawar Saddar
Execution Mode: STRICT READ-ONLY
```

### Safety Invariants & Enforcement
1. **Server-Side Mutation Guard**: All HTTP write operations (`POST`, `PUT`, `PATCH`, `DELETE`) are blocked at the middleware layer when a preview header/state is active, returning `403 Forbidden - Preview mode is strictly read-only`.
2. **UI Visual Indication**:
   - A persistent, high-contrast amber/navy top banner displays:
     `👁️ PREVIEW MODE — Viewing as: [Role Name] at [Branch Name] (READ ONLY)`
   - Includes a prominent, one-click **"Exit Preview"** button.
3. **Audit Logging**:
   - Entering preview mode logs `view_as_preview_started` with owner ID, preview role, and branch.
   - Exiting preview logs `view_as_preview_exited`.

---

## 3. Owner Review Notes & Action Requests
When inspecting a store in preview mode or reviewing dashboards, the owner cannot mutate transactions directly but can issue structured **Action Requests**:

```json
{
  "business_id": 1,
  "branch_id": 2,
  "created_by_owner_id": 10,
  "target_user_id": 15,
  "target_role": "Branch Manager",
  "title": "Investigate High Return Rate on SKU-1049",
  "description": "Notice 8 return requests recorded yesterday. Please inspect batch quality and report back.",
  "priority": "high",
  "status": "pending",
  "related_resource_type": "product",
  "related_resource_id": 1049,
  "created_at": "2026-10-03T01:30:00Z"
}
```
- Action Requests appear in the target staff member's notification center and dashboard alert feed.
- Staff can mark requests as `acknowledged` and `resolved` with resolution notes.
