# HBOS Activity & Audit Attribution Architecture
**SkyraSoft HBOS Retail Platform & Workforce Integrity Program**

---

## 1. Complete Actor Attribution Model
HBOS enforces non-repudiation and complete accountability for every material operation.

### Fundamental Attribution Invariants
Every business mutation records:
1. `business_id` (Tenant scope)
2. `branch_id` (Physical location/store)
3. `created_by` / `causer_id` (Authenticated human actor `users.id`)
4. `role_at_action` (Snapshot of actor's active role at the exact moment of transaction)
5. `timestamp` (Deterministic server UTC timestamp)
6. `before_state` & `after_state` (JSON snapshot of changes in `activity_log.properties`)

---

## 2. Material Business Mutation Matrix

| Operation Category | Database Tables Affected | Primary Actor Column | Branch Scope Column | Historical Snapshot Method |
| :--- | :--- | :--- | :--- | :--- |
| **Sale Checkout** | `sales`, `sale_items`, `sale_payments` | `sales.created_by` | `sales.branch_id` | Snapshots cashier name, role, invoice number in `activity_log`. |
| **Sale Cancellation / Return**| `sales`, `sale_returns`, `sale_return_items`| `sale_returns.created_by`| `sale_returns.branch_id` | Snapshots return reason, refund amount, condition of restocked goods. |
| **Price Override** | `sale_items` | `sales.created_by` | `sales.branch_id` | Explicit `price_overridden` audit log with original price vs overridden price. |
| **Purchases & GRN** | `purchases`, `purchase_items` | `purchases.created_by`| `purchases.branch_id` | Snapshots receiver identity, supplier invoice reference, and landed cost. |
| **Stock Adjustments & Transfers**| `inventory_movements`, `branch_inventories` | `inventory_movements.created_by`| `inventory_movements.branch_id` | Snapshots reason code, old stock qty, new stock qty, and approving staff. |
| **Khata Customer Payments** | `customer_payments`, `khata_transactions` | `customer_payments.created_by`| `customer_payments.branch_id` | Snapshots collecting cashier/accountant, customer balance before and after. |
| **Supplier Payments & Returns** | `supplier_payments`, `supplier_returns` | `supplier_payments.created_by`| `supplier_payments.branch_id` | Snapshots disbursing user, financial account debited, and supplier balance delta. |
| **Expenses & Cash Transfers** | `expenses`, `account_transfers`, `account_movements`| `expenses.created_by` | `expenses.branch_id` | Snapshots expense category, payee, approving manager, and financial account. |
| **User & Governance Changes** | `users`, `model_has_roles`, `branches` | `activity_log.causer_id` | `activity_log.branch_id` | Snapshots old role vs new role, status toggles, and branch assignments. |

---

## 3. Offline Replay Attribution Invariant
When transactions are executed in offline mode (Tauri desktop / IndexedDB queue) and replayed upon reconnection:
- The replay payload **MUST retain the original `user_id`** of the cashier who physically processed the transaction offline.
- The bulk sync processor (`SyncController::bulkIngest`) must **NEVER overwrite** the transaction actor with a generic "Sync Service" or "Platform System" identity.
- The offline creation timestamp is stored as `client_created_at` alongside the server sync ingestion timestamp.

---

## 4. Role Change Historical Correctness
If an employee named *Muhammad Ali* is a **Salesperson** on 2026-08-15 and is promoted to **Branch Manager** on 2026-10-01:
- A sale processed by Ali on 2026-08-15 must continue to show: `Actor: Muhammad Ali | Role at Time: Salesperson`.
- Querying past historical logs uses immutable properties snapshot data in `activity_log`, ensuring promotions never rewrite the historical record.
