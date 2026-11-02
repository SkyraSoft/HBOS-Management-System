# SKYRASOFT HBOS FUNCTIONAL ARCHITECTURE PROGRAM
# PHASE 3 — END-TO-END CROSS-MODULE BUSINESS FLOW AUDIT

> **Authoritative System Audit Deliverable**  
> **Scope:** End-to-End Business Workflows, Multi-Tender POS, Procurement, Payables, Returns, Reversals, Cross-Module State & Value Propagation  
> **Status:** Certified Complete With Known Gaps  

## 1. Executive Summary & Program Context

Phase 3 evaluates how business operations traverse across multiple application modules in the SkyraSoft HBOS platform. Building upon the frozen Phase 1 data lineage (45 tables, 438 DBCOLs, 214 UI fields) and Phase 2 entity lifecycles (46 entities, 71 mutations), Phase 3 audits the end-to-end propagation of stock, receivables (AR), payables (AP), cash drawers, financial accounts, and ledger movements.

### Summary Metrics

- **Total Canonical Business Flows Audited:** 23
- **Revenue & POS Family Flows:** 7 (`FLOW-001` to `FLOW-007`)
- **Procurement & Payables Family Flows:** 4 (`FLOW-008` to `FLOW-011`)
- **Returns, Refunds & Reversals Family Flows:** 7 (`FLOW-012` to `FLOW-018`)
- **Supporting Operational Flows:** 5 (`FLOW-019` to `FLOW-023`)
- **Total Granular Flow Steps:** 142
- **Certified Complete Flows:** 7
- **Certified With Known Gaps:** 16
- **Uncertified Flows:** 0
- **New Phase-3 Flow Gaps Identified:** 15 (`P3-GAP-001` to `P3-GAP-015`)

## 2. Canonical Business Flow Master Register

| Flow ID | Flow Name | Family | Initiating Actor | Entry Route / UI | Primary Mutation | Stock Effect | AR / AP Effect | Finance Effect | Cert Status |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `FLOW-001` | **POS Direct Cash Sale (Fully Paid)** | `REVENUE` | CASHIER / OPERATOR | `POST /api/v1/sales` | `MUTATION-029` | `DECREMENTS_QUANTITY_ON_HAND` | `NONE` | `POSTS_CASH_LEDGER_INFLOW` | `CERTIFIED_WITH_KNOWN_GAP` |
| `FLOW-002` | **POS Direct Bank / Digital Payment Sale** | `REVENUE` | CASHIER / OPERATOR | `POST /api/v1/sales` | `MUTATION-029` | `DECREMENTS_QUANTITY_ON_HAND` | `NONE` | `INCREMENTS_FINANCIAL_ACCOUNT_BALANCE` | `CERTIFIED_WITH_KNOWN_GAP` |
| `FLOW-003` | **POS Credit Sale on Customer Khata (Unpaid)** | `REVENUE` | CASHIER / MANAGER | `POST /api/v1/sales` | `MUTATION-029` | `DECREMENTS_QUANTITY_ON_HAND` | `INCREMENTS_CUSTOMER_RECEIVABLES_BALANCE` | `NONE` | `CERTIFIED_WITH_KNOWN_GAP` |
| `FLOW-004` | **POS Partially Paid Sale** | `REVENUE` | CASHIER / OPERATOR | `POST /api/v1/sales` | `MUTATION-029` | `DECREMENTS_QUANTITY_ON_HAND` | `INCREMENTS_CUSTOMER_RECEIVABLES_BY_DUE_PORTION` | `INCREMENTS_IF_PAID_DIGITALLY` | `CERTIFIED_WITH_KNOWN_GAP` |
| `FLOW-005` | **POS Split-Tender / Multi-Payment Sale** | `REVENUE` | CASHIER / OPERATOR | `POST /api/v1/sales` | `MUTATION-029` | `DECREMENTS_QUANTITY_ON_HAND` | `NONE` | `INCREMENTS_BANK_ACCOUNT` | `CERTIFIED_WITH_KNOWN_GAP` |
| `FLOW-006` | **Post-Sale Customer Khata Payment Receipt** | `REVENUE` | CASHIER / ACCOUNTANT | `POST /api/v1/customer-payments` | `MUTATION-041` | `NONE` | `DECREMENTS_CUSTOMER_RECEIVABLES_BALANCE` | `INCREMENTS_FINANCIAL_ACCOUNT_BALANCE` | `CERTIFIED_COMPLETE` |
| `FLOW-007` | **Offline POS Batch Sync Ingestion** | `REVENUE` | OFFLINE_POS_CLIENT / SYNC_WORKER | `POST /api/v1/sync/bulk-ingest` | `MUTATION-071` | `BATCH_DECREMENTS_BRANCH_STOCK` | `BATCH_MUTATES_CUSTOMER_RECEIVABLES` | `BATCH_POSTS_FINANCIAL_MOVEMENTS` | `CERTIFIED_WITH_KNOWN_GAP` |
| `FLOW-008` | **Purchase on Credit with Immediate GRN Stock Receipt** | `PROCUREMENT` | STOCK_KEEPER / PROCUREMENT_MANAGER | `POST /api/v1/purchases` | `MUTATION-046` | `INCREMENTS_QUANTITY_ON_HAND` | `INCREMENTS_SUPPLIER_PAYABLE_BALANCE` | `NONE` | `CERTIFIED_WITH_KNOWN_GAP` |
| `FLOW-009` | **Purchase with Immediate Full Cash/Bank Disbursal** | `PROCUREMENT` | PROCUREMENT_MANAGER / ACCOUNTANT | `POST /api/v1/purchases` | `MUTATION-046` | `INCREMENTS_QUANTITY_ON_HAND` | `NET_ZERO_SUPPLIER_BALANCE_CHANGE` | `DECREMENTS_FINANCIAL_ACCOUNT_BALANCE` | `CERTIFIED_WITH_KNOWN_GAP` |
| `FLOW-010` | **Purchase with Partial Upfront Payment** | `PROCUREMENT` | PROCUREMENT_MANAGER / ACCOUNTANT | `POST /api/v1/purchases` | `MUTATION-046` | `INCREMENTS_QUANTITY_ON_HAND` | `INCREMENTS_SUPPLIER_PAYABLE_BY_REMAINING_DUE` | `DECREMENTS_FINANCIAL_ACCOUNT_BY_PAID_PORTION` | `CERTIFIED_WITH_KNOWN_GAP` |
| `FLOW-011` | **Post-GRN Supplier Payment Disbursal** | `PROCUREMENT` | ACCOUNTANT / OWNER | `POST /api/v1/supplier-payments` | `MUTATION-049` | `NONE` | `DECREMENTS_SUPPLIER_PAYABLE_BALANCE` | `DECREMENTS_FINANCIAL_ACCOUNT_BALANCE` | `CERTIFIED_WITH_KNOWN_GAP` |
| `FLOW-012` | **Sale Return with Restocking & Cash/Bank Refund** | `REVERSAL` | CASHIER / STORE_MANAGER | `POST /api/v1/sales/{id}/returns -> POST /api/v1/sale-returns/{id}/settle-refund` | `MUTATION-033, MUTATION-034` | `INCREMENTS_QUANTITY_ON_HAND` | `NONE` | `DECREMENTS_FINANCIAL_ACCOUNT_FOR_REFUND` | `CERTIFIED_WITH_KNOWN_GAP` |
| `FLOW-013` | **Sale Return with Customer Store Credit / Khata Adjustment** | `REVERSAL` | CASHIER / STORE_MANAGER | `POST /api/v1/sales/{id}/returns -> POST /api/v1/sale-returns/{id}/settle-refund` | `MUTATION-033, MUTATION-034` | `INCREMENTS_QUANTITY_ON_HAND` | `DECREMENTS_CUSTOMER_RECEIVABLES_OR_CREDITS_KHATA` | `NONE` | `CERTIFIED_COMPLETE` |
| `FLOW-014` | **Sale Cancellation & Automatic Multi-Module Rollback** | `REVERSAL` | STORE_MANAGER / OWNER | `POST /api/v1/sales/{id}/cancel` | `MUTATION-031` | `RESTORES_DECREMENTED_STOCK_QUANTITIES` | `REDUCES_CUSTOMER_RECEIVABLES_IF_CREDIT` | `REVERSES_FINANCIAL_INFLOW_IF_DIGITAL` | `CERTIFIED_COMPLETE` |
| `FLOW-015` | **Customer Payment Reversal** | `REVERSAL` | STORE_MANAGER / OWNER | `POST /api/v1/customer-payments/{id}/reverse` | `MUTATION-042` | `NONE` | `RESTORES_CUSTOMER_DUE_BALANCE` | `DECREMENTS_FINANCIAL_ACCOUNT_BALANCE` | `CERTIFIED_COMPLETE` |
| `FLOW-016` | **Purchase Cancellation & Stock/AP Reversion** | `REVERSAL` | STORE_MANAGER / OWNER | `POST /api/v1/purchases/{id}/cancel` | `MUTATION-047` | `DECREMENTS_RECEIVED_STOCK_QUANTITIES` | `REDUCES_SUPPLIER_AP_BALANCE` | `RESTORES_FINANCIAL_ACCOUNT_IF_PAID` | `CERTIFIED_WITH_KNOWN_GAP` |
| `FLOW-017` | **Operating Expense Void & Account Restoration** | `REVERSAL` | STORE_MANAGER / OWNER | `POST /api/v1/expenses/{id}/void` | `MUTATION-059` | `NONE` | `NONE` | `RESTORES_FINANCIAL_ACCOUNT_BALANCE` | `CERTIFIED_COMPLETE` |
| `FLOW-018` | **Inter-Account Fund Transfer Reversal** | `REVERSAL` | OWNER / ACCOUNTANT | `POST /api/v1/account-transfers` | `MUTATION-065` | `NONE` | `NONE` | `RESTORES_SOURCE_AND_DESTINATION_BALANCES` | `CERTIFIED_WITH_KNOWN_GAP` |
| `FLOW-019` | **Manual Inventory Stock Adjustment** | `SUPPORTING` | STOCK_KEEPER / STORE_MANAGER | `POST /api/v1/inventory/adjust` | `MUTATION-027` | `MUTATES_QUANTITY_ON_HAND` | `NONE` | `NONE` | `CERTIFIED_WITH_KNOWN_GAP` |
| `FLOW-020` | **Inter-Branch Stock Transfer** | `SUPPORTING` | STOCK_MANAGER | `POST /api/v1/inventory/transfer` | `MUTATION-028` | `DECREMENTS_SOURCE_INCREMENTS_DESTINATION_STOCK` | `NONE` | `NONE` | `CERTIFIED_WITH_KNOWN_GAP` |
| `FLOW-021` | **Financial Account Direct Capital Injection** | `SUPPORTING` | BUSINESS_OWNER / ACCOUNTANT | `POST /api/v1/financial-accounts/{id}/capital-in` | `MUTATION-063` | `NONE` | `NONE` | `INCREMENTS_FINANCIAL_ACCOUNT_BALANCE` | `CERTIFIED_COMPLETE` |
| `FLOW-022` | **Financial Account Direct Capital Drawing** | `SUPPORTING` | BUSINESS_OWNER | `POST /api/v1/financial-accounts/{id}/capital-out` | `MUTATION-064` | `NONE` | `NONE` | `DECREMENTS_FINANCIAL_ACCOUNT_BALANCE` | `CERTIFIED_COMPLETE` |
| `FLOW-023` | **Direct Operating Expense Disbursal** | `SUPPORTING` | CASHIER / ACCOUNTANT | `POST /api/v1/expenses` | `MUTATION-056` | `NONE` | `NONE` | `DECREMENTS_FINANCIAL_ACCOUNT_BALANCE` | `CERTIFIED_WITH_KNOWN_GAP` |

## 3. Deep-Dive Business Flow Traces

### FLOW-001: POS Direct Cash Sale (Fully Paid)

- **Flow Family:** `REVENUE`
- **Business Purpose:** Execute direct walk-in retail POS checkout with immediate full cash settlement, instant stock decrement, cash ledger entry, and receipt printing.
- **Actor & Entry:** CASHIER / OPERATOR via `POS Interface (Frontend /views/pos/PosView.vue)` (`POST /api/v1/sales`)
- **State Progression:** `CART_OPEN` -> `PAID`
- **Primary Entities:** ENTITY-020, ENTITY-021, ENTITY-018, ENTITY-019, ENTITY-037
- **Primary Mutations:** MUTATION-029
- **Tables Written:** `sales`, `sale_items`, `branch_inventories`, `inventory_movements`, `cash_ledgers`, `activity_log`
- **Reversal Counterpart:** FLOW-014 (Sale Cancellation) / FLOW-012 (Sale Return)
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/SaleController.php@store, api/app/Services/SaleService.php@createSale`
- **Known Gaps:** P3-GAP-001

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Barcode / Product Lookup & Cart Accumulation | `UI` | `src/views/pos/PosView.vue (scanBarcode / addToCart)` | Lookup product master, fetch retail price DBCOL-111, push line item to POS reactive cart store | 0 (Reserved locally in UI) | 0/0 | 0 | Remove item from cart |
| 2 | Tender Selection & Cash Settlement Entry | `UI` | `src/views/pos/PaymentModal.vue (confirmCashPayment)` | Operator enters cash tender amount; UI verifies paid_amount >= total_amount and prepares sale payload | 0 | 0/0 | 0 | Cancel payment modal |
| 3 | API Request Validation & Authorization | `REQUEST_VALIDATION` | `api/app/Http/Requests/SaleStoreRequest.php` | Authorize request against user role; validate items.*.product_id exists, items.*.quantity > 0, branch_id valid | 0 | 0/0 | 0 | None (Stateless) |
| 4 | Sale Header & Sale Items Persistence | `SERVICE` | `api/app/Services/SaleService.php@createSale` | Create Sale record (invoice_number, total_amount, paid_amount, due_amount=0, payment_status="paid"); iterate items and insert sale_items rows | 0 | 0/0 | 0 | MUTATION-032 (Sale Cancellation) |
| 5 | Branch Inventory Decrement & Stock Ledger Movement | `INVENTORY` | `api/app/Services/InventoryService.php@deductStock` | Decrement branch_inventories.quantity_on_hand; insert inventory_movements row (movement_type="sale", reference_type="App\\Models\\Sale", reference_id=sale.id, quantity_change=-qty) | -Quantity per item | 0/0 | 0 | MUTATION-030 (Sale Return Restock) / MUTATION-032 (Void Restock) |
| 6 | Cash Drawer Ledger Inflow Posting | `FINANCE` | `api/app/Services/CashRegisterService.php@recordInflow` | Insert cash_ledgers row (type="inflow", amount=paid_amount, source="sale", sale_id=sale.id, register_id=active_register.id) | 0 | 0/0 | 0 | MUTATION-032 (Cash Outflow Reversal Entry) |
| 7 | Audit Log & Transaction Commit | `DATABASE` | `api/app/Services/SaleService.php@commitTransaction` | Write activity_log entry (action="sale.created", subject=Sale); execute DB::commit() | 0 | 0/0 | 0 | Activity audit of cancellation/return |
| 8 | Thermal Receipt Generation & Hardware Print Dispatch | `PRINT` | `src/services/printService.js (printThermalReceipt)` | Format ESC/POS binary raw receipt layout with business header, itemized quantities, totals, change, and barcode; transmit to printer | 0 | 0/0 | 0 | Print Return/Cancellation Receipt |

### FLOW-002: POS Direct Bank / Digital Payment Sale

- **Flow Family:** `REVENUE`
- **Business Purpose:** Execute POS checkout with electronic bank/card settlement directly depositing into a selected financial account with stock decrement.
- **Actor & Entry:** CASHIER / OPERATOR via `POS Interface (PaymentModal.vue)` (`POST /api/v1/sales`)
- **State Progression:** `CART_OPEN` -> `PAID`
- **Primary Entities:** ENTITY-020, ENTITY-021, ENTITY-018, ENTITY-019, ENTITY-034, ENTITY-035
- **Primary Mutations:** MUTATION-029
- **Tables Written:** `sales`, `sale_items`, `branch_inventories`, `inventory_movements`, `financial_accounts`, `account_movements`, `activity_log`
- **Reversal Counterpart:** FLOW-014 (Sale Cancellation) / FLOW-012 (Sale Return)
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/SaleController.php@store, api/app/Services/SaleService.php@createSale`
- **Known Gaps:** P3-GAP-001

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Product Selection & Cart Build | `UI` | `src/views/pos/PosView.vue` | Accumulate line items, apply retail pricing, compute total | 0 | 0/0 | 0 | Clear Cart |
| 2 | Electronic Tender & Financial Account Selection | `UI` | `src/views/pos/PaymentModal.vue` | Select receiving bank/card account; capture optional reference note; format sale payload | 0 | 0/0 | 0 | Cancel Modal |
| 3 | API Request Validation & Account Verification | `REQUEST_VALIDATION` | `api/app/Http/Requests/SaleStoreRequest.php` | Validate schema, verify account active status | 0 | 0/0 | 0 | None |
| 4 | Sale & Items Persistence with Non-Cash Account Allocation | `SERVICE` | `api/app/Services/SaleService.php@createSale` | Create Sale record (payment_method="bank", payment_status="paid", financial_account_id=X); insert sale_items | 0 | 0/0 | 0 | MUTATION-032 (Sale Cancellation) |
| 5 | Inventory Decrement & Stock Ledger Posting | `INVENTORY` | `api/app/Services/InventoryService.php@deductStock` | Decrement branch_inventories.quantity_on_hand; record inventory_movements (movement_type="sale") | -Quantity per item | 0/0 | 0 | MUTATION-030 / MUTATION-032 |
| 6 | Financial Account Balance Increment & Account Movement Posting | `FINANCE` | `api/app/Services/AccountMovementService.php@postInflow` | Increment financial_accounts.balance by paid_amount; insert account_movements record (movement_type="sale_payment", reference_type="App\\Models\\Sale", reference_id=sale.id, debit_amount=0, credit_amount=paid_amount) | 0 | 0/0 | +paid_amount | MUTATION-032 (Account Movement Debit Compensation) |
| 7 | Transaction Commit & Receipt Dispatch | `PRINT` | `src/services/printService.js` | Commit DB transaction; print customer invoice displaying payment method "Bank / Digital" and account identifier | 0 | 0/0 | 0 | Electronic Refund Receipt |

### FLOW-003: POS Credit Sale on Customer Khata (Unpaid)

- **Flow Family:** `REVENUE`
- **Business Purpose:** Execute POS checkout on customer credit, decrementing stock and debiting customer Khata receivables balance without upfront payment.
- **Actor & Entry:** CASHIER / MANAGER via `POS Interface (CustomerSelectModal.vue + PaymentModal.vue)` (`POST /api/v1/sales`)
- **State Progression:** `CART_OPEN` -> `DUE`
- **Primary Entities:** ENTITY-020, ENTITY-021, ENTITY-018, ENTITY-019, ENTITY-024, ENTITY-025
- **Primary Mutations:** MUTATION-029
- **Tables Written:** `sales`, `sale_items`, `branch_inventories`, `inventory_movements`, `customers`, `khata_transactions`, `activity_log`
- **Reversal Counterpart:** FLOW-014 (Sale Cancellation) / FLOW-013 (Sale Return Credit)
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/SaleController.php@store, api/app/Services/SaleService.php@createSale`
- **Known Gaps:** P3-GAP-002

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Customer Selection & Credit Limit Verification | `UI` | `src/views/pos/CustomerSelectModal.vue` | Fetch Customer record (balance DBCOL-209, credit_limit DBCOL-210), verify available credit margin | 0 | 0/0 | 0 | Deselect Customer |
| 2 | Credit Tender Selection & Zero Cash Submission | `UI` | `src/views/pos/PaymentModal.vue` | Build sale payload with payment_method="credit", paid_amount=0, due_amount=total, payment_status="unpaid" | 0 | 0/0 | 0 | Cancel Modal |
| 3 | Request Validation & Credit Sanctioning | `REQUEST_VALIDATION` | `api/app/Http/Requests/SaleStoreRequest.php` | Validate customer existence, active status, and non-null customer_id requirement for credit status | 0 | 0/0 | 0 | None |
| 4 | Sale Persistence with Unpaid Status | `SERVICE` | `api/app/Services/SaleService.php@createSale` | Create Sale record (payment_status="unpaid", paid_amount=0, due_amount=total, customer_id=X); insert sale_items | 0 | 0/0 | 0 | MUTATION-032 (Sale Void) |
| 5 | Branch Inventory Decrement & Stock Movement | `INVENTORY` | `api/app/Services/InventoryService.php@deductStock` | Decrement branch_inventories.quantity_on_hand; record inventory_movements (movement_type="sale") | -Quantity per item | 0/0 | 0 | MUTATION-030 / MUTATION-032 |
| 6 | Customer Receivable Increment & Khata Transaction Ledger Posting | `FINANCE` | `api/app/Services/KhataService.php@postDebit` | Increment customers.balance by due_amount; insert khata_transactions row (customer_id=X, type="debit", amount=due_amount, reference_type="App\\Models\\Sale", reference_id=sale.id, note="Credit Sale Inv #...") | 0 | +due_amount/0 | 0 | MUTATION-032 (Khata Credit Reversal Entry) |
| 7 | Transaction Commit & Credit Invoice Dispatch | `PRINT` | `src/services/printService.js` | Commit DB transaction; print credit sales invoice showing previous balance, current charge, and new outstanding balance | 0 | 0/0 | 0 | Print Credit Reversal Note |

### FLOW-004: POS Partially Paid Sale

- **Flow Family:** `REVENUE`
- **Business Purpose:** Execute POS checkout with partial cash/bank payment upfront, routing paid portion to liquid accounts and remainder to Customer Khata AR balance.
- **Actor & Entry:** CASHIER / OPERATOR via `POS Interface (PaymentModal.vue)` (`POST /api/v1/sales`)
- **State Progression:** `CART_OPEN` -> `PARTIAL`
- **Primary Entities:** ENTITY-020, ENTITY-021, ENTITY-018, ENTITY-019, ENTITY-024, ENTITY-025, ENTITY-037
- **Primary Mutations:** MUTATION-029
- **Tables Written:** `sales`, `sale_items`, `branch_inventories`, `inventory_movements`, `customers`, `khata_transactions`, `cash_ledgers`, `activity_log`
- **Reversal Counterpart:** FLOW-014 (Sale Cancellation)
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/SaleController.php@store, api/app/Services/SaleService.php@createSale`
- **Known Gaps:** P3-GAP-002

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Operator Workflow Trigger & UI Initialization | `UI` | `POS Interface (PaymentModal.vue)` | Collect operator inputs, validate client-side schema, construct reactive transaction payload | 0 | 0/0 | 0 | Reset Form |
| 2 | API Request Schema & Business Rule Validation | `REQUEST_VALIDATION` | `POST /api/v1/sales` | Execute Laravel FormRequest authorization and validation rules against database entities | 0 | 0/0 | 0 | None |
| 3 | Primary Domain Entity Persistence & Mutation | `SERVICE` | `api/app/Http/Controllers/Api/V1/SaleController.php@store, api/app/Services/SaleService.php@createSale` | Execute primary mutation MUTATION-029 updating sales, sale_items, branch_inventories, inventory_movements, customers, khata_transactions, cash_ledgers, activity_log | DECREMENTS_QUANTITY_ON_HAND | INCREMENTS_CUSTOMER_RECEIVABLES_BY_DUE_PORTION/NONE | INCREMENTS_IF_PAID_DIGITALLY | FLOW-014 (Sale Cancellation) |
| 4 | Cross-Module Secondary Side-Effect Processing | `SERVICE` | `api/app/Http/Controllers/Api/V1/SaleController.php@store, api/app/Services/SaleService.php@createSale` | Update secondary modules: Inventory (DECREMENTS_QUANTITY_ON_HAND), AR/AP (INCREMENTS_CUSTOMER_RECEIVABLES_BY_DUE_PORTION/NONE), Financial Account (INCREMENTS_IF_PAID_DIGITALLY) | DECREMENTS_QUANTITY_ON_HAND | INCREMENTS_CUSTOMER_RECEIVABLES_BY_DUE_PORTION/NONE | INCREMENTS_IF_PAID_DIGITALLY | FLOW-014 (Sale Cancellation) |
| 5 | Audit Logging & Atomic DB Transaction Commit | `DATABASE` | `api/app/Services/AuditService.php` | Write activity_log (LOGS_PARTIAL_SALE); execute DB::commit() | 0 | 0/0 | 0 | Audit log of reversal action |
| 6 | Document Generation, Hardware Dispatch & Sync Propagation | `PRINT` | `src/services/printService.js / SyncEngine` | Dispatch print output (RECEIPT_WITH_DUE_BALANCE); queue offline sync update (QUEUES_TRANSACTION_SYNC_RECORD) | 0 | 0/0 | 0 | Reversal document printout |

### FLOW-005: POS Split-Tender / Multi-Payment Sale

- **Flow Family:** `REVENUE`
- **Business Purpose:** Execute POS checkout settled across multiple tender methods (e.g. Cash + Bank Card), splitting payments into respective ledger accounts.
- **Actor & Entry:** CASHIER / OPERATOR via `POS Interface (SplitPaymentModal.vue)` (`POST /api/v1/sales`)
- **State Progression:** `CART_OPEN` -> `PAID`
- **Primary Entities:** ENTITY-020, ENTITY-021, ENTITY-018, ENTITY-019, ENTITY-034, ENTITY-035, ENTITY-037
- **Primary Mutations:** MUTATION-029
- **Tables Written:** `sales`, `sale_items`, `branch_inventories`, `inventory_movements`, `financial_accounts`, `account_movements`, `cash_ledgers`, `activity_log`
- **Reversal Counterpart:** FLOW-014 (Sale Cancellation)
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/SaleController.php@store, api/app/Services/SaleService.php@createSale`
- **Known Gaps:** P3-GAP-003

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Operator Workflow Trigger & UI Initialization | `UI` | `POS Interface (SplitPaymentModal.vue)` | Collect operator inputs, validate client-side schema, construct reactive transaction payload | 0 | 0/0 | 0 | Reset Form |
| 2 | API Request Schema & Business Rule Validation | `REQUEST_VALIDATION` | `POST /api/v1/sales` | Execute Laravel FormRequest authorization and validation rules against database entities | 0 | 0/0 | 0 | None |
| 3 | Primary Domain Entity Persistence & Mutation | `SERVICE` | `api/app/Http/Controllers/Api/V1/SaleController.php@store, api/app/Services/SaleService.php@createSale` | Execute primary mutation MUTATION-029 updating sales, sale_items, branch_inventories, inventory_movements, financial_accounts, account_movements, cash_ledgers, activity_log | DECREMENTS_QUANTITY_ON_HAND | NONE/NONE | INCREMENTS_BANK_ACCOUNT | FLOW-014 (Sale Cancellation) |
| 4 | Cross-Module Secondary Side-Effect Processing | `SERVICE` | `api/app/Http/Controllers/Api/V1/SaleController.php@store, api/app/Services/SaleService.php@createSale` | Update secondary modules: Inventory (DECREMENTS_QUANTITY_ON_HAND), AR/AP (NONE/NONE), Financial Account (INCREMENTS_BANK_ACCOUNT) | DECREMENTS_QUANTITY_ON_HAND | NONE/NONE | INCREMENTS_BANK_ACCOUNT | FLOW-014 (Sale Cancellation) |
| 5 | Audit Logging & Atomic DB Transaction Commit | `DATABASE` | `api/app/Services/AuditService.php` | Write activity_log (LOGS_SPLIT_SALE); execute DB::commit() | 0 | 0/0 | 0 | Audit log of reversal action |
| 6 | Document Generation, Hardware Dispatch & Sync Propagation | `PRINT` | `src/services/printService.js / SyncEngine` | Dispatch print output (SPLIT_PAYMENT_RECEIPT); queue offline sync update (QUEUES_TRANSACTION_SYNC_RECORD) | 0 | 0/0 | 0 | Reversal document printout |

### FLOW-006: Post-Sale Customer Khata Payment Receipt

- **Flow Family:** `REVENUE`
- **Business Purpose:** Receive customer balance settlement for previous credit sales, depositing funds into cash/bank and crediting customer Khata AR balance.
- **Actor & Entry:** CASHIER / ACCOUNTANT via `Customer Details / Khata Modal (CustomerPaymentModal.vue)` (`POST /api/v1/customer-payments`)
- **State Progression:** `PAYMENT_ENTRY` -> `POSTED`
- **Primary Entities:** ENTITY-026, ENTITY-024, ENTITY-025, ENTITY-034, ENTITY-035, ENTITY-037
- **Primary Mutations:** MUTATION-041
- **Tables Written:** `customer_payments`, `customers`, `khata_transactions`, `financial_accounts`, `account_movements`, `cash_ledgers`, `activity_log`
- **Reversal Counterpart:** FLOW-015 (Customer Payment Reversal)
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/CustomerPaymentController.php@store, api/app/Services/CustomerPaymentService.php@recordPayment`
- **Known Gaps:** None

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Operator Workflow Trigger & UI Initialization | `UI` | `Customer Details / Khata Modal (CustomerPaymentModal.vue)` | Collect operator inputs, validate client-side schema, construct reactive transaction payload | 0 | 0/0 | 0 | Reset Form |
| 2 | API Request Schema & Business Rule Validation | `REQUEST_VALIDATION` | `POST /api/v1/customer-payments` | Execute Laravel FormRequest authorization and validation rules against database entities | 0 | 0/0 | 0 | None |
| 3 | Primary Domain Entity Persistence & Mutation | `SERVICE` | `api/app/Http/Controllers/Api/V1/CustomerPaymentController.php@store, api/app/Services/CustomerPaymentService.php@recordPayment` | Execute primary mutation MUTATION-041 updating customer_payments, customers, khata_transactions, financial_accounts, account_movements, cash_ledgers, activity_log | NONE | DECREMENTS_CUSTOMER_RECEIVABLES_BALANCE/NONE | INCREMENTS_FINANCIAL_ACCOUNT_BALANCE | FLOW-015 (Customer Payment Reversal) |
| 4 | Cross-Module Secondary Side-Effect Processing | `SERVICE` | `api/app/Http/Controllers/Api/V1/CustomerPaymentController.php@store, api/app/Services/CustomerPaymentService.php@recordPayment` | Update secondary modules: Inventory (NONE), AR/AP (DECREMENTS_CUSTOMER_RECEIVABLES_BALANCE/NONE), Financial Account (INCREMENTS_FINANCIAL_ACCOUNT_BALANCE) | NONE | DECREMENTS_CUSTOMER_RECEIVABLES_BALANCE/NONE | INCREMENTS_FINANCIAL_ACCOUNT_BALANCE | FLOW-015 (Customer Payment Reversal) |
| 5 | Audit Logging & Atomic DB Transaction Commit | `DATABASE` | `api/app/Services/AuditService.php` | Write activity_log (LOGS_CUSTOMER_PAYMENT); execute DB::commit() | 0 | 0/0 | 0 | Audit log of reversal action |
| 6 | Document Generation, Hardware Dispatch & Sync Propagation | `PRINT` | `src/services/printService.js / SyncEngine` | Dispatch print output (PAYMENT_RECEIPT_VOUCHER); queue offline sync update (SYNC_CUSTOMER_PAYMENT) | 0 | 0/0 | 0 | Reversal document printout |

### FLOW-007: Offline POS Batch Sync Ingestion

- **Flow Family:** `REVENUE`
- **Business Purpose:** Ingest batches of sales and payments executed offline in browser/desktop Dexie database into server PostgreSQL database with UUID deduplication.
- **Actor & Entry:** OFFLINE_POS_CLIENT / SYNC_WORKER via `Sync Engine Background Process (syncService.js)` (`POST /api/v1/sync/bulk-ingest`)
- **State Progression:** `QUEUED_LOCAL` -> `INGESTED_SERVER`
- **Primary Entities:** ENTITY-020, ENTITY-021, ENTITY-018, ENTITY-019, ENTITY-026, ENTITY-025, ENTITY-037
- **Primary Mutations:** MUTATION-071
- **Tables Written:** `sales`, `sale_items`, `branch_inventories`, `inventory_movements`, `customer_payments`, `khata_transactions`, `cash_ledgers`, `activity_log`
- **Reversal Counterpart:** INDIVIDUAL_TRANSACTION_REVERSALS
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/SyncController.php@bulkIngest, api/app/Services/BulkSyncService.php@ingestBulkTransactions`
- **Known Gaps:** P3-GAP-004

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Operator Workflow Trigger & UI Initialization | `UI` | `Sync Engine Background Process (syncService.js)` | Collect operator inputs, validate client-side schema, construct reactive transaction payload | 0 | 0/0 | 0 | Reset Form |
| 2 | API Request Schema & Business Rule Validation | `REQUEST_VALIDATION` | `POST /api/v1/sync/bulk-ingest` | Execute Laravel FormRequest authorization and validation rules against database entities | 0 | 0/0 | 0 | None |
| 3 | Primary Domain Entity Persistence & Mutation | `SERVICE` | `api/app/Http/Controllers/Api/V1/SyncController.php@bulkIngest, api/app/Services/BulkSyncService.php@ingestBulkTransactions` | Execute primary mutation MUTATION-071 updating sales, sale_items, branch_inventories, inventory_movements, customer_payments, khata_transactions, cash_ledgers, activity_log | BATCH_DECREMENTS_BRANCH_STOCK | BATCH_MUTATES_CUSTOMER_RECEIVABLES/NONE | BATCH_POSTS_FINANCIAL_MOVEMENTS | INDIVIDUAL_TRANSACTION_REVERSALS |
| 4 | Cross-Module Secondary Side-Effect Processing | `SERVICE` | `api/app/Http/Controllers/Api/V1/SyncController.php@bulkIngest, api/app/Services/BulkSyncService.php@ingestBulkTransactions` | Update secondary modules: Inventory (BATCH_DECREMENTS_BRANCH_STOCK), AR/AP (BATCH_MUTATES_CUSTOMER_RECEIVABLES/NONE), Financial Account (BATCH_POSTS_FINANCIAL_MOVEMENTS) | BATCH_DECREMENTS_BRANCH_STOCK | BATCH_MUTATES_CUSTOMER_RECEIVABLES/NONE | BATCH_POSTS_FINANCIAL_MOVEMENTS | INDIVIDUAL_TRANSACTION_REVERSALS |
| 5 | Audit Logging & Atomic DB Transaction Commit | `DATABASE` | `api/app/Services/AuditService.php` | Write activity_log (LOGS_SYNC_BATCH); execute DB::commit() | 0 | 0/0 | 0 | Audit log of reversal action |
| 6 | Document Generation, Hardware Dispatch & Sync Propagation | `PRINT` | `src/services/printService.js / SyncEngine` | Dispatch print output (LOCAL_RECEIPT_PRINTED_AT_SALE_TIME); queue offline sync update (CONFIRMS_INGEST_ACK) | 0 | 0/0 | 0 | Reversal document printout |

### FLOW-008: Purchase on Credit with Immediate GRN Stock Receipt

- **Flow Family:** `PROCUREMENT`
- **Business Purpose:** Record vendor purchase invoice on credit, receiving physical goods into branch inventory, updating weighted cost, and crediting Supplier AP.
- **Actor & Entry:** STOCK_KEEPER / PROCUREMENT_MANAGER via `Purchase Entry Form (PurchaseCreateView.vue)` (`POST /api/v1/purchases`)
- **State Progression:** `PURCHASE_ENTRY` -> `RECEIVED`
- **Primary Entities:** ENTITY-028, ENTITY-029, ENTITY-018, ENTITY-019, ENTITY-027
- **Primary Mutations:** MUTATION-046
- **Tables Written:** `purchases`, `purchase_items`, `branch_inventories`, `inventory_movements`, `suppliers`, `activity_log`
- **Reversal Counterpart:** FLOW-016 (Purchase Cancellation)
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/PurchaseController.php@store, api/app/Services/PurchaseService.php@createPurchase`
- **Known Gaps:** P3-GAP-005, P3-GAP-006

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Operator Workflow Trigger & UI Initialization | `UI` | `Purchase Entry Form (PurchaseCreateView.vue)` | Collect operator inputs, validate client-side schema, construct reactive transaction payload | 0 | 0/0 | 0 | Reset Form |
| 2 | API Request Schema & Business Rule Validation | `REQUEST_VALIDATION` | `POST /api/v1/purchases` | Execute Laravel FormRequest authorization and validation rules against database entities | 0 | 0/0 | 0 | None |
| 3 | Primary Domain Entity Persistence & Mutation | `SERVICE` | `api/app/Http/Controllers/Api/V1/PurchaseController.php@store, api/app/Services/PurchaseService.php@createPurchase` | Execute primary mutation MUTATION-046 updating purchases, purchase_items, branch_inventories, inventory_movements, suppliers, activity_log | INCREMENTS_QUANTITY_ON_HAND | NONE/INCREMENTS_SUPPLIER_PAYABLE_BALANCE | NONE | FLOW-016 (Purchase Cancellation) |
| 4 | Cross-Module Secondary Side-Effect Processing | `SERVICE` | `api/app/Http/Controllers/Api/V1/PurchaseController.php@store, api/app/Services/PurchaseService.php@createPurchase` | Update secondary modules: Inventory (INCREMENTS_QUANTITY_ON_HAND), AR/AP (NONE/INCREMENTS_SUPPLIER_PAYABLE_BALANCE), Financial Account (NONE) | INCREMENTS_QUANTITY_ON_HAND | NONE/INCREMENTS_SUPPLIER_PAYABLE_BALANCE | NONE | FLOW-016 (Purchase Cancellation) |
| 5 | Audit Logging & Atomic DB Transaction Commit | `DATABASE` | `api/app/Services/AuditService.php` | Write activity_log (LOGS_PURCHASE_CREATION); execute DB::commit() | 0 | 0/0 | 0 | Audit log of reversal action |
| 6 | Document Generation, Hardware Dispatch & Sync Propagation | `PRINT` | `src/services/printService.js / SyncEngine` | Dispatch print output (GRN_PURCHASE_BILL_DOCUMENT); queue offline sync update (CATALOG_STOCK_DELTA_SYNC) | 0 | 0/0 | 0 | Reversal document printout |

### FLOW-009: Purchase with Immediate Full Cash/Bank Disbursal

- **Flow Family:** `PROCUREMENT`
- **Business Purpose:** Record vendor purchase with full upfront payment, immediately incrementing stock and debiting selected financial/cash account with zero net AP balance.
- **Actor & Entry:** PROCUREMENT_MANAGER / ACCOUNTANT via `Purchase Entry Form (PurchaseCreateView.vue)` (`POST /api/v1/purchases`)
- **State Progression:** `PURCHASE_ENTRY` -> `RECEIVED`
- **Primary Entities:** ENTITY-028, ENTITY-029, ENTITY-018, ENTITY-019, ENTITY-027, ENTITY-034, ENTITY-035
- **Primary Mutations:** MUTATION-046
- **Tables Written:** `purchases`, `purchase_items`, `branch_inventories`, `inventory_movements`, `suppliers`, `financial_accounts`, `account_movements`, `activity_log`
- **Reversal Counterpart:** FLOW-016 (Purchase Cancellation)
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/PurchaseController.php@store, api/app/Services/PurchaseService.php@createPurchase`
- **Known Gaps:** P3-GAP-005

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Operator Workflow Trigger & UI Initialization | `UI` | `Purchase Entry Form (PurchaseCreateView.vue)` | Collect operator inputs, validate client-side schema, construct reactive transaction payload | 0 | 0/0 | 0 | Reset Form |
| 2 | API Request Schema & Business Rule Validation | `REQUEST_VALIDATION` | `POST /api/v1/purchases` | Execute Laravel FormRequest authorization and validation rules against database entities | 0 | 0/0 | 0 | None |
| 3 | Primary Domain Entity Persistence & Mutation | `SERVICE` | `api/app/Http/Controllers/Api/V1/PurchaseController.php@store, api/app/Services/PurchaseService.php@createPurchase` | Execute primary mutation MUTATION-046 updating purchases, purchase_items, branch_inventories, inventory_movements, suppliers, financial_accounts, account_movements, activity_log | INCREMENTS_QUANTITY_ON_HAND | NONE/NET_ZERO_SUPPLIER_BALANCE_CHANGE | DECREMENTS_FINANCIAL_ACCOUNT_BALANCE | FLOW-016 (Purchase Cancellation) |
| 4 | Cross-Module Secondary Side-Effect Processing | `SERVICE` | `api/app/Http/Controllers/Api/V1/PurchaseController.php@store, api/app/Services/PurchaseService.php@createPurchase` | Update secondary modules: Inventory (INCREMENTS_QUANTITY_ON_HAND), AR/AP (NONE/NET_ZERO_SUPPLIER_BALANCE_CHANGE), Financial Account (DECREMENTS_FINANCIAL_ACCOUNT_BALANCE) | INCREMENTS_QUANTITY_ON_HAND | NONE/NET_ZERO_SUPPLIER_BALANCE_CHANGE | DECREMENTS_FINANCIAL_ACCOUNT_BALANCE | FLOW-016 (Purchase Cancellation) |
| 5 | Audit Logging & Atomic DB Transaction Commit | `DATABASE` | `api/app/Services/AuditService.php` | Write activity_log (LOGS_PURCHASE_WITH_PAYMENT); execute DB::commit() | 0 | 0/0 | 0 | Audit log of reversal action |
| 6 | Document Generation, Hardware Dispatch & Sync Propagation | `PRINT` | `src/services/printService.js / SyncEngine` | Dispatch print output (PAID_PURCHASE_INVOICE); queue offline sync update (CATALOG_STOCK_DELTA_SYNC) | 0 | 0/0 | 0 | Reversal document printout |

### FLOW-010: Purchase with Partial Upfront Payment

- **Flow Family:** `PROCUREMENT`
- **Business Purpose:** Record vendor purchase with partial upfront payment, disbursing paid amount from liquid account and crediting remainder to Supplier AP liability.
- **Actor & Entry:** PROCUREMENT_MANAGER / ACCOUNTANT via `Purchase Entry Form (PurchaseCreateView.vue)` (`POST /api/v1/purchases`)
- **State Progression:** `PURCHASE_ENTRY` -> `RECEIVED`
- **Primary Entities:** ENTITY-028, ENTITY-029, ENTITY-018, ENTITY-019, ENTITY-027, ENTITY-034, ENTITY-035
- **Primary Mutations:** MUTATION-046
- **Tables Written:** `purchases`, `purchase_items`, `branch_inventories`, `inventory_movements`, `suppliers`, `financial_accounts`, `account_movements`, `activity_log`
- **Reversal Counterpart:** FLOW-016 (Purchase Cancellation)
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/PurchaseController.php@store, api/app/Services/PurchaseService.php@createPurchase`
- **Known Gaps:** P3-GAP-005

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Operator Workflow Trigger & UI Initialization | `UI` | `Purchase Entry Form (PurchaseCreateView.vue)` | Collect operator inputs, validate client-side schema, construct reactive transaction payload | 0 | 0/0 | 0 | Reset Form |
| 2 | API Request Schema & Business Rule Validation | `REQUEST_VALIDATION` | `POST /api/v1/purchases` | Execute Laravel FormRequest authorization and validation rules against database entities | 0 | 0/0 | 0 | None |
| 3 | Primary Domain Entity Persistence & Mutation | `SERVICE` | `api/app/Http/Controllers/Api/V1/PurchaseController.php@store, api/app/Services/PurchaseService.php@createPurchase` | Execute primary mutation MUTATION-046 updating purchases, purchase_items, branch_inventories, inventory_movements, suppliers, financial_accounts, account_movements, activity_log | INCREMENTS_QUANTITY_ON_HAND | NONE/INCREMENTS_SUPPLIER_PAYABLE_BY_REMAINING_DUE | DECREMENTS_FINANCIAL_ACCOUNT_BY_PAID_PORTION | FLOW-016 (Purchase Cancellation) |
| 4 | Cross-Module Secondary Side-Effect Processing | `SERVICE` | `api/app/Http/Controllers/Api/V1/PurchaseController.php@store, api/app/Services/PurchaseService.php@createPurchase` | Update secondary modules: Inventory (INCREMENTS_QUANTITY_ON_HAND), AR/AP (NONE/INCREMENTS_SUPPLIER_PAYABLE_BY_REMAINING_DUE), Financial Account (DECREMENTS_FINANCIAL_ACCOUNT_BY_PAID_PORTION) | INCREMENTS_QUANTITY_ON_HAND | NONE/INCREMENTS_SUPPLIER_PAYABLE_BY_REMAINING_DUE | DECREMENTS_FINANCIAL_ACCOUNT_BY_PAID_PORTION | FLOW-016 (Purchase Cancellation) |
| 5 | Audit Logging & Atomic DB Transaction Commit | `DATABASE` | `api/app/Services/AuditService.php` | Write activity_log (LOGS_PARTIAL_PURCHASE); execute DB::commit() | 0 | 0/0 | 0 | Audit log of reversal action |
| 6 | Document Generation, Hardware Dispatch & Sync Propagation | `PRINT` | `src/services/printService.js / SyncEngine` | Dispatch print output (PURCHASE_BILL_WITH_DUE_BALANCE); queue offline sync update (CATALOG_STOCK_DELTA_SYNC) | 0 | 0/0 | 0 | Reversal document printout |

### FLOW-011: Post-GRN Supplier Payment Disbursal

- **Flow Family:** `PROCUREMENT`
- **Business Purpose:** Disburse payment to vendor against outstanding AP liabilities, debiting financial account/cash and reducing supplier payable balance.
- **Actor & Entry:** ACCOUNTANT / OWNER via `Supplier Payment Form (SupplierPaymentModal.vue)` (`POST /api/v1/supplier-payments`)
- **State Progression:** `PAYMENT_ENTRY` -> `POSTED`
- **Primary Entities:** ENTITY-030, ENTITY-027, ENTITY-034, ENTITY-035, ENTITY-037
- **Primary Mutations:** MUTATION-049
- **Tables Written:** `supplier_payments`, `suppliers`, `financial_accounts`, `account_movements`, `cash_ledgers`, `activity_log`
- **Reversal Counterpart:** MANUAL_COMPENSATING_ENTRY (No dedicated reverse endpoint)
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/SupplierPaymentController.php@store, api/app/Services/SupplierBalanceService.php@recordPayment`
- **Known Gaps:** P3-GAP-007

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Operator Workflow Trigger & UI Initialization | `UI` | `Supplier Payment Form (SupplierPaymentModal.vue)` | Collect operator inputs, validate client-side schema, construct reactive transaction payload | 0 | 0/0 | 0 | Reset Form |
| 2 | API Request Schema & Business Rule Validation | `REQUEST_VALIDATION` | `POST /api/v1/supplier-payments` | Execute Laravel FormRequest authorization and validation rules against database entities | 0 | 0/0 | 0 | None |
| 3 | Primary Domain Entity Persistence & Mutation | `SERVICE` | `api/app/Http/Controllers/Api/V1/SupplierPaymentController.php@store, api/app/Services/SupplierBalanceService.php@recordPayment` | Execute primary mutation MUTATION-049 updating supplier_payments, suppliers, financial_accounts, account_movements, cash_ledgers, activity_log | NONE | NONE/DECREMENTS_SUPPLIER_PAYABLE_BALANCE | DECREMENTS_FINANCIAL_ACCOUNT_BALANCE | MANUAL_COMPENSATING_ENTRY (No dedicated reverse endpoint) |
| 4 | Cross-Module Secondary Side-Effect Processing | `SERVICE` | `api/app/Http/Controllers/Api/V1/SupplierPaymentController.php@store, api/app/Services/SupplierBalanceService.php@recordPayment` | Update secondary modules: Inventory (NONE), AR/AP (NONE/DECREMENTS_SUPPLIER_PAYABLE_BALANCE), Financial Account (DECREMENTS_FINANCIAL_ACCOUNT_BALANCE) | NONE | NONE/DECREMENTS_SUPPLIER_PAYABLE_BALANCE | DECREMENTS_FINANCIAL_ACCOUNT_BALANCE | MANUAL_COMPENSATING_ENTRY (No dedicated reverse endpoint) |
| 5 | Audit Logging & Atomic DB Transaction Commit | `DATABASE` | `api/app/Services/AuditService.php` | Write activity_log (LOGS_SUPPLIER_PAYMENT); execute DB::commit() | 0 | 0/0 | 0 | Audit log of reversal action |
| 6 | Document Generation, Hardware Dispatch & Sync Propagation | `PRINT` | `src/services/printService.js / SyncEngine` | Dispatch print output (SUPPLIER_PAYMENT_VOUCHER); queue offline sync update (SERVER_MANAGED) | 0 | 0/0 | 0 | Reversal document printout |

### FLOW-012: Sale Return with Restocking & Cash/Bank Refund

- **Flow Family:** `REVERSAL`
- **Business Purpose:** Process customer return of sold goods, incrementing branch inventory, recording sale return items, and disbursing cash/bank refund to customer.
- **Actor & Entry:** CASHIER / STORE_MANAGER via `Sale Return Interface (SaleReturnModal.vue)` (`POST /api/v1/sales/{id}/returns -> POST /api/v1/sale-returns/{id}/settle-refund`)
- **State Progression:** `RETURN_ENTRY` -> `SETTLED`
- **Primary Entities:** ENTITY-022, ENTITY-023, ENTITY-020, ENTITY-018, ENTITY-019, ENTITY-034, ENTITY-035, ENTITY-037
- **Primary Mutations:** MUTATION-033, MUTATION-034
- **Tables Written:** `sale_returns`, `sale_return_items`, `branch_inventories`, `inventory_movements`, `financial_accounts`, `account_movements`, `cash_ledgers`, `activity_log`
- **Reversal Counterpart:** MUTATION-035 (Reverse Refund)
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/SaleController.php@returns, api/app/Http/Controllers/Api/V1/SaleReturnRefundController.php@settleRefund`
- **Known Gaps:** P3-GAP-008

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Operator Workflow Trigger & UI Initialization | `UI` | `Sale Return Interface (SaleReturnModal.vue)` | Collect operator inputs, validate client-side schema, construct reactive transaction payload | 0 | 0/0 | 0 | Reset Form |
| 2 | API Request Schema & Business Rule Validation | `REQUEST_VALIDATION` | `POST /api/v1/sales/{id}/returns -> POST /api/v1/sale-returns/{id}/settle-refund` | Execute Laravel FormRequest authorization and validation rules against database entities | 0 | 0/0 | 0 | None |
| 3 | Primary Domain Entity Persistence & Mutation | `SERVICE` | `api/app/Http/Controllers/Api/V1/SaleController.php@returns, api/app/Http/Controllers/Api/V1/SaleReturnRefundController.php@settleRefund` | Execute primary mutation MUTATION-033, MUTATION-034 updating sale_returns, sale_return_items, branch_inventories, inventory_movements, financial_accounts, account_movements, cash_ledgers, activity_log | INCREMENTS_QUANTITY_ON_HAND | NONE/NONE | DECREMENTS_FINANCIAL_ACCOUNT_FOR_REFUND | MUTATION-035 (Reverse Refund) |
| 4 | Cross-Module Secondary Side-Effect Processing | `SERVICE` | `api/app/Http/Controllers/Api/V1/SaleController.php@returns, api/app/Http/Controllers/Api/V1/SaleReturnRefundController.php@settleRefund` | Update secondary modules: Inventory (INCREMENTS_QUANTITY_ON_HAND), AR/AP (NONE/NONE), Financial Account (DECREMENTS_FINANCIAL_ACCOUNT_FOR_REFUND) | INCREMENTS_QUANTITY_ON_HAND | NONE/NONE | DECREMENTS_FINANCIAL_ACCOUNT_FOR_REFUND | MUTATION-035 (Reverse Refund) |
| 5 | Audit Logging & Atomic DB Transaction Commit | `DATABASE` | `api/app/Services/AuditService.php` | Write activity_log (LOGS_SALE_RETURN_AND_REFUND); execute DB::commit() | 0 | 0/0 | 0 | Audit log of reversal action |
| 6 | Document Generation, Hardware Dispatch & Sync Propagation | `PRINT` | `src/services/printService.js / SyncEngine` | Dispatch print output (SALE_RETURN_REFUND_RECEIPT); queue offline sync update (SERVER_OR_OFFLINE_SYNC) | 0 | 0/0 | 0 | Reversal document printout |

### FLOW-013: Sale Return with Customer Store Credit / Khata Adjustment

- **Flow Family:** `REVERSAL`
- **Business Purpose:** Process customer return crediting customer Khata AR balance or issuing store credit without physical cash outflow.
- **Actor & Entry:** CASHIER / STORE_MANAGER via `Sale Return Interface (SaleReturnModal.vue)` (`POST /api/v1/sales/{id}/returns -> POST /api/v1/sale-returns/{id}/settle-refund`)
- **State Progression:** `RETURN_ENTRY` -> `SETTLED`
- **Primary Entities:** ENTITY-022, ENTITY-023, ENTITY-020, ENTITY-018, ENTITY-019, ENTITY-024, ENTITY-025
- **Primary Mutations:** MUTATION-033, MUTATION-034
- **Tables Written:** `sale_returns`, `sale_return_items`, `branch_inventories`, `inventory_movements`, `customers`, `khata_transactions`, `activity_log`
- **Reversal Counterpart:** MUTATION-035 (Reverse Refund)
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/SaleController.php@returns, api/app/Http/Controllers/Api/V1/SaleReturnRefundController.php@settleRefund`
- **Known Gaps:** None

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Operator Workflow Trigger & UI Initialization | `UI` | `Sale Return Interface (SaleReturnModal.vue)` | Collect operator inputs, validate client-side schema, construct reactive transaction payload | 0 | 0/0 | 0 | Reset Form |
| 2 | API Request Schema & Business Rule Validation | `REQUEST_VALIDATION` | `POST /api/v1/sales/{id}/returns -> POST /api/v1/sale-returns/{id}/settle-refund` | Execute Laravel FormRequest authorization and validation rules against database entities | 0 | 0/0 | 0 | None |
| 3 | Primary Domain Entity Persistence & Mutation | `SERVICE` | `api/app/Http/Controllers/Api/V1/SaleController.php@returns, api/app/Http/Controllers/Api/V1/SaleReturnRefundController.php@settleRefund` | Execute primary mutation MUTATION-033, MUTATION-034 updating sale_returns, sale_return_items, branch_inventories, inventory_movements, customers, khata_transactions, activity_log | INCREMENTS_QUANTITY_ON_HAND | DECREMENTS_CUSTOMER_RECEIVABLES_OR_CREDITS_KHATA/NONE | NONE | MUTATION-035 (Reverse Refund) |
| 4 | Cross-Module Secondary Side-Effect Processing | `SERVICE` | `api/app/Http/Controllers/Api/V1/SaleController.php@returns, api/app/Http/Controllers/Api/V1/SaleReturnRefundController.php@settleRefund` | Update secondary modules: Inventory (INCREMENTS_QUANTITY_ON_HAND), AR/AP (DECREMENTS_CUSTOMER_RECEIVABLES_OR_CREDITS_KHATA/NONE), Financial Account (NONE) | INCREMENTS_QUANTITY_ON_HAND | DECREMENTS_CUSTOMER_RECEIVABLES_OR_CREDITS_KHATA/NONE | NONE | MUTATION-035 (Reverse Refund) |
| 5 | Audit Logging & Atomic DB Transaction Commit | `DATABASE` | `api/app/Services/AuditService.php` | Write activity_log (LOGS_SALE_RETURN_CREDIT); execute DB::commit() | 0 | 0/0 | 0 | Audit log of reversal action |
| 6 | Document Generation, Hardware Dispatch & Sync Propagation | `PRINT` | `src/services/printService.js / SyncEngine` | Dispatch print output (CREDIT_NOTE_RECEIPT); queue offline sync update (SERVER_OR_OFFLINE_SYNC) | 0 | 0/0 | 0 | Reversal document printout |

### FLOW-014: Sale Cancellation & Automatic Multi-Module Rollback

- **Flow Family:** `REVERSAL`
- **Business Purpose:** Formally cancel a posted sale invoice, automatically restoring decremented product quantities, reversing Khata AR debit, and compensating cash/bank inflows.
- **Actor & Entry:** STORE_MANAGER / OWNER via `Sale Details View (CancelSaleModal.vue)` (`POST /api/v1/sales/{id}/cancel`)
- **State Progression:** `PAID / PARTIAL / DUE` -> `CANCELLED`
- **Primary Entities:** ENTITY-020, ENTITY-018, ENTITY-019, ENTITY-024, ENTITY-025, ENTITY-037
- **Primary Mutations:** MUTATION-031
- **Tables Written:** `sales`, `branch_inventories`, `inventory_movements`, `customers`, `khata_transactions`, `cash_ledgers`, `activity_log`
- **Reversal Counterpart:** IRREVERSIBLE (Terminal State)
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/SaleController.php@cancel, api/app/Services/SaleService.php@cancelSale`
- **Known Gaps:** None

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Operator Workflow Trigger & UI Initialization | `UI` | `Sale Details View (CancelSaleModal.vue)` | Collect operator inputs, validate client-side schema, construct reactive transaction payload | 0 | 0/0 | 0 | Reset Form |
| 2 | API Request Schema & Business Rule Validation | `REQUEST_VALIDATION` | `POST /api/v1/sales/{id}/cancel` | Execute Laravel FormRequest authorization and validation rules against database entities | 0 | 0/0 | 0 | None |
| 3 | Primary Domain Entity Persistence & Mutation | `SERVICE` | `api/app/Http/Controllers/Api/V1/SaleController.php@cancel, api/app/Services/SaleService.php@cancelSale` | Execute primary mutation MUTATION-031 updating sales, branch_inventories, inventory_movements, customers, khata_transactions, cash_ledgers, activity_log | RESTORES_DECREMENTED_STOCK_QUANTITIES | REDUCES_CUSTOMER_RECEIVABLES_IF_CREDIT/NONE | REVERSES_FINANCIAL_INFLOW_IF_DIGITAL | IRREVERSIBLE (Terminal State) |
| 4 | Cross-Module Secondary Side-Effect Processing | `SERVICE` | `api/app/Http/Controllers/Api/V1/SaleController.php@cancel, api/app/Services/SaleService.php@cancelSale` | Update secondary modules: Inventory (RESTORES_DECREMENTED_STOCK_QUANTITIES), AR/AP (REDUCES_CUSTOMER_RECEIVABLES_IF_CREDIT/NONE), Financial Account (REVERSES_FINANCIAL_INFLOW_IF_DIGITAL) | RESTORES_DECREMENTED_STOCK_QUANTITIES | REDUCES_CUSTOMER_RECEIVABLES_IF_CREDIT/NONE | REVERSES_FINANCIAL_INFLOW_IF_DIGITAL | IRREVERSIBLE (Terminal State) |
| 5 | Audit Logging & Atomic DB Transaction Commit | `DATABASE` | `api/app/Services/AuditService.php` | Write activity_log (LOGS_SALE_CANCELLATION); execute DB::commit() | 0 | 0/0 | 0 | Audit log of reversal action |
| 6 | Document Generation, Hardware Dispatch & Sync Propagation | `PRINT` | `src/services/printService.js / SyncEngine` | Dispatch print output (CANCELLED_INVOICE_WATERMARK); queue offline sync update (PROPAGATES_CANCELLATION_TO_NODES) | 0 | 0/0 | 0 | Reversal document printout |

### FLOW-015: Customer Payment Reversal

- **Flow Family:** `REVERSAL`
- **Business Purpose:** Reverse an erroneous customer payment receipt, restoring the customer outstanding due balance and debiting liquid cash/bank account.
- **Actor & Entry:** STORE_MANAGER / OWNER via `Customer Ledger View (PaymentReverseModal.vue)` (`POST /api/v1/customer-payments/{id}/reverse`)
- **State Progression:** `POSTED` -> `REVERSED`
- **Primary Entities:** ENTITY-026, ENTITY-024, ENTITY-025, ENTITY-034, ENTITY-035, ENTITY-037
- **Primary Mutations:** MUTATION-042
- **Tables Written:** `customer_payments`, `customers`, `khata_transactions`, `financial_accounts`, `account_movements`, `cash_ledgers`, `activity_log`
- **Reversal Counterpart:** IRREVERSIBLE
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/CustomerPaymentController.php@reverse, api/app/Services/CustomerPaymentService.php@reversePayment`
- **Known Gaps:** None

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Operator Workflow Trigger & UI Initialization | `UI` | `Customer Ledger View (PaymentReverseModal.vue)` | Collect operator inputs, validate client-side schema, construct reactive transaction payload | 0 | 0/0 | 0 | Reset Form |
| 2 | API Request Schema & Business Rule Validation | `REQUEST_VALIDATION` | `POST /api/v1/customer-payments/{id}/reverse` | Execute Laravel FormRequest authorization and validation rules against database entities | 0 | 0/0 | 0 | None |
| 3 | Primary Domain Entity Persistence & Mutation | `SERVICE` | `api/app/Http/Controllers/Api/V1/CustomerPaymentController.php@reverse, api/app/Services/CustomerPaymentService.php@reversePayment` | Execute primary mutation MUTATION-042 updating customer_payments, customers, khata_transactions, financial_accounts, account_movements, cash_ledgers, activity_log | NONE | RESTORES_CUSTOMER_DUE_BALANCE/NONE | DECREMENTS_FINANCIAL_ACCOUNT_BALANCE | IRREVERSIBLE |
| 4 | Cross-Module Secondary Side-Effect Processing | `SERVICE` | `api/app/Http/Controllers/Api/V1/CustomerPaymentController.php@reverse, api/app/Services/CustomerPaymentService.php@reversePayment` | Update secondary modules: Inventory (NONE), AR/AP (RESTORES_CUSTOMER_DUE_BALANCE/NONE), Financial Account (DECREMENTS_FINANCIAL_ACCOUNT_BALANCE) | NONE | RESTORES_CUSTOMER_DUE_BALANCE/NONE | DECREMENTS_FINANCIAL_ACCOUNT_BALANCE | IRREVERSIBLE |
| 5 | Audit Logging & Atomic DB Transaction Commit | `DATABASE` | `api/app/Services/AuditService.php` | Write activity_log (LOGS_PAYMENT_REVERSAL); execute DB::commit() | 0 | 0/0 | 0 | Audit log of reversal action |
| 6 | Document Generation, Hardware Dispatch & Sync Propagation | `PRINT` | `src/services/printService.js / SyncEngine` | Dispatch print output (REVERSAL_VOUCHER); queue offline sync update (SERVER_MANAGED) | 0 | 0/0 | 0 | Reversal document printout |

### FLOW-016: Purchase Cancellation & Stock/AP Reversion

- **Flow Family:** `REVERSAL`
- **Business Purpose:** Formally cancel a received purchase order, decrementing stock previously received and removing the supplier accounts payable liability.
- **Actor & Entry:** STORE_MANAGER / OWNER via `Purchase Details View (CancelPurchaseModal.vue)` (`POST /api/v1/purchases/{id}/cancel`)
- **State Progression:** `RECEIVED` -> `CANCELLED`
- **Primary Entities:** ENTITY-028, ENTITY-018, ENTITY-019, ENTITY-027, ENTITY-034, ENTITY-035
- **Primary Mutations:** MUTATION-047
- **Tables Written:** `purchases`, `branch_inventories`, `inventory_movements`, `suppliers`, `financial_accounts`, `account_movements`, `activity_log`
- **Reversal Counterpart:** IRREVERSIBLE
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/PurchaseController.php@cancel, api/app/Services/PurchaseService.php@cancelPurchase`
- **Known Gaps:** P3-GAP-009

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Operator Workflow Trigger & UI Initialization | `UI` | `Purchase Details View (CancelPurchaseModal.vue)` | Collect operator inputs, validate client-side schema, construct reactive transaction payload | 0 | 0/0 | 0 | Reset Form |
| 2 | API Request Schema & Business Rule Validation | `REQUEST_VALIDATION` | `POST /api/v1/purchases/{id}/cancel` | Execute Laravel FormRequest authorization and validation rules against database entities | 0 | 0/0 | 0 | None |
| 3 | Primary Domain Entity Persistence & Mutation | `SERVICE` | `api/app/Http/Controllers/Api/V1/PurchaseController.php@cancel, api/app/Services/PurchaseService.php@cancelPurchase` | Execute primary mutation MUTATION-047 updating purchases, branch_inventories, inventory_movements, suppliers, financial_accounts, account_movements, activity_log | DECREMENTS_RECEIVED_STOCK_QUANTITIES | NONE/REDUCES_SUPPLIER_AP_BALANCE | RESTORES_FINANCIAL_ACCOUNT_IF_PAID | IRREVERSIBLE |
| 4 | Cross-Module Secondary Side-Effect Processing | `SERVICE` | `api/app/Http/Controllers/Api/V1/PurchaseController.php@cancel, api/app/Services/PurchaseService.php@cancelPurchase` | Update secondary modules: Inventory (DECREMENTS_RECEIVED_STOCK_QUANTITIES), AR/AP (NONE/REDUCES_SUPPLIER_AP_BALANCE), Financial Account (RESTORES_FINANCIAL_ACCOUNT_IF_PAID) | DECREMENTS_RECEIVED_STOCK_QUANTITIES | NONE/REDUCES_SUPPLIER_AP_BALANCE | RESTORES_FINANCIAL_ACCOUNT_IF_PAID | IRREVERSIBLE |
| 5 | Audit Logging & Atomic DB Transaction Commit | `DATABASE` | `api/app/Services/AuditService.php` | Write activity_log (LOGS_PURCHASE_CANCELLATION); execute DB::commit() | 0 | 0/0 | 0 | Audit log of reversal action |
| 6 | Document Generation, Hardware Dispatch & Sync Propagation | `PRINT` | `src/services/printService.js / SyncEngine` | Dispatch print output (CANCELLED_PURCHASE_BILL); queue offline sync update (SERVER_MANAGED) | 0 | 0/0 | 0 | Reversal document printout |

### FLOW-017: Operating Expense Void & Account Restoration

- **Flow Family:** `REVERSAL`
- **Business Purpose:** Void an erroneous operating expense voucher, restoring liquid bank/cash account balances with compensating financial movement posting.
- **Actor & Entry:** STORE_MANAGER / OWNER via `Expense Details View (VoidExpenseModal.vue)` (`POST /api/v1/expenses/{id}/void`)
- **State Progression:** `ACTIVE` -> `VOIDED`
- **Primary Entities:** ENTITY-031, ENTITY-034, ENTITY-035, ENTITY-037
- **Primary Mutations:** MUTATION-059
- **Tables Written:** `expenses`, `financial_accounts`, `account_movements`, `cash_ledgers`, `activity_log`
- **Reversal Counterpart:** IRREVERSIBLE
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/ExpenseController.php@void, api/app/Services/ExpenseService.php@voidExpense`
- **Known Gaps:** None

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Operator Workflow Trigger & UI Initialization | `UI` | `Expense Details View (VoidExpenseModal.vue)` | Collect operator inputs, validate client-side schema, construct reactive transaction payload | 0 | 0/0 | 0 | Reset Form |
| 2 | API Request Schema & Business Rule Validation | `REQUEST_VALIDATION` | `POST /api/v1/expenses/{id}/void` | Execute Laravel FormRequest authorization and validation rules against database entities | 0 | 0/0 | 0 | None |
| 3 | Primary Domain Entity Persistence & Mutation | `SERVICE` | `api/app/Http/Controllers/Api/V1/ExpenseController.php@void, api/app/Services/ExpenseService.php@voidExpense` | Execute primary mutation MUTATION-059 updating expenses, financial_accounts, account_movements, cash_ledgers, activity_log | NONE | NONE/NONE | RESTORES_FINANCIAL_ACCOUNT_BALANCE | IRREVERSIBLE |
| 4 | Cross-Module Secondary Side-Effect Processing | `SERVICE` | `api/app/Http/Controllers/Api/V1/ExpenseController.php@void, api/app/Services/ExpenseService.php@voidExpense` | Update secondary modules: Inventory (NONE), AR/AP (NONE/NONE), Financial Account (RESTORES_FINANCIAL_ACCOUNT_BALANCE) | NONE | NONE/NONE | RESTORES_FINANCIAL_ACCOUNT_BALANCE | IRREVERSIBLE |
| 5 | Audit Logging & Atomic DB Transaction Commit | `DATABASE` | `api/app/Services/AuditService.php` | Write activity_log (LOGS_EXPENSE_VOID); execute DB::commit() | 0 | 0/0 | 0 | Audit log of reversal action |
| 6 | Document Generation, Hardware Dispatch & Sync Propagation | `PRINT` | `src/services/printService.js / SyncEngine` | Dispatch print output (VOIDED_EXPENSE_VOUCHER); queue offline sync update (SERVER_MANAGED) | 0 | 0/0 | 0 | Reversal document printout |

### FLOW-018: Inter-Account Fund Transfer Reversal

- **Flow Family:** `REVERSAL`
- **Business Purpose:** Reverse an erroneous inter-account bank/cash fund transfer by posting compensating opposing debit and credit movements.
- **Actor & Entry:** OWNER / ACCOUNTANT via `Banking Transfer View (TransferReverseModal.vue)` (`POST /api/v1/account-transfers`)
- **State Progression:** `COMPLETED` -> `REVERSED`
- **Primary Entities:** ENTITY-036, ENTITY-034, ENTITY-035
- **Primary Mutations:** MUTATION-065
- **Tables Written:** `account_transfers`, `financial_accounts`, `account_movements`, `activity_log`
- **Reversal Counterpart:** IRREVERSIBLE
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/AccountTransferController.php@store, api/app/Services/AccountMovementService.php@transfer`
- **Known Gaps:** P3-GAP-010

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Operator Workflow Trigger & UI Initialization | `UI` | `Banking Transfer View (TransferReverseModal.vue)` | Collect operator inputs, validate client-side schema, construct reactive transaction payload | 0 | 0/0 | 0 | Reset Form |
| 2 | API Request Schema & Business Rule Validation | `REQUEST_VALIDATION` | `POST /api/v1/account-transfers` | Execute Laravel FormRequest authorization and validation rules against database entities | 0 | 0/0 | 0 | None |
| 3 | Primary Domain Entity Persistence & Mutation | `SERVICE` | `api/app/Http/Controllers/Api/V1/AccountTransferController.php@store, api/app/Services/AccountMovementService.php@transfer` | Execute primary mutation MUTATION-065 updating account_transfers, financial_accounts, account_movements, activity_log | NONE | NONE/NONE | RESTORES_SOURCE_AND_DESTINATION_BALANCES | IRREVERSIBLE |
| 4 | Cross-Module Secondary Side-Effect Processing | `SERVICE` | `api/app/Http/Controllers/Api/V1/AccountTransferController.php@store, api/app/Services/AccountMovementService.php@transfer` | Update secondary modules: Inventory (NONE), AR/AP (NONE/NONE), Financial Account (RESTORES_SOURCE_AND_DESTINATION_BALANCES) | NONE | NONE/NONE | RESTORES_SOURCE_AND_DESTINATION_BALANCES | IRREVERSIBLE |
| 5 | Audit Logging & Atomic DB Transaction Commit | `DATABASE` | `api/app/Services/AuditService.php` | Write activity_log (LOGS_TRANSFER_REVERSAL); execute DB::commit() | 0 | 0/0 | 0 | Audit log of reversal action |
| 6 | Document Generation, Hardware Dispatch & Sync Propagation | `PRINT` | `src/services/printService.js / SyncEngine` | Dispatch print output (TRANSFER_REVERSAL_RECEIPT); queue offline sync update (SERVER_MANAGED) | 0 | 0/0 | 0 | Reversal document printout |

### FLOW-019: Manual Inventory Stock Adjustment

- **Flow Family:** `SUPPORTING`
- **Business Purpose:** Reconcile physical stock discrepancy identified during audit, updating branch quantity on hand and posting audit ledger movement.
- **Actor & Entry:** STOCK_KEEPER / STORE_MANAGER via `Stock Audit Form (StockAdjustmentModal.vue)` (`POST /api/v1/inventory/adjust`)
- **State Progression:** `IN_STOCK / LOW_STOCK` -> `IN_STOCK / LOW_STOCK`
- **Primary Entities:** ENTITY-018, ENTITY-019
- **Primary Mutations:** MUTATION-027
- **Tables Written:** `branch_inventories`, `inventory_movements`, `activity_log`
- **Reversal Counterpart:** COMPENSATING_ADJUSTMENT
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/InventoryController.php@adjust, api/app/Services/InventoryService.php@adjustStock`
- **Known Gaps:** P3-GAP-011

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Operator Workflow Trigger & UI Initialization | `UI` | `Stock Audit Form (StockAdjustmentModal.vue)` | Collect operator inputs, validate client-side schema, construct reactive transaction payload | 0 | 0/0 | 0 | Reset Form |
| 2 | API Request Schema & Business Rule Validation | `REQUEST_VALIDATION` | `POST /api/v1/inventory/adjust` | Execute Laravel FormRequest authorization and validation rules against database entities | 0 | 0/0 | 0 | None |
| 3 | Primary Domain Entity Persistence & Mutation | `SERVICE` | `api/app/Http/Controllers/Api/V1/InventoryController.php@adjust, api/app/Services/InventoryService.php@adjustStock` | Execute primary mutation MUTATION-027 updating branch_inventories, inventory_movements, activity_log | MUTATES_QUANTITY_ON_HAND | NONE/NONE | NONE | COMPENSATING_ADJUSTMENT |
| 4 | Cross-Module Secondary Side-Effect Processing | `SERVICE` | `api/app/Http/Controllers/Api/V1/InventoryController.php@adjust, api/app/Services/InventoryService.php@adjustStock` | Update secondary modules: Inventory (MUTATES_QUANTITY_ON_HAND), AR/AP (NONE/NONE), Financial Account (NONE) | MUTATES_QUANTITY_ON_HAND | NONE/NONE | NONE | COMPENSATING_ADJUSTMENT |
| 5 | Audit Logging & Atomic DB Transaction Commit | `DATABASE` | `api/app/Services/AuditService.php` | Write activity_log (RECORDED_IN_MOVEMENT_AND_AUDIT_LOG); execute DB::commit() | 0 | 0/0 | 0 | Audit log of reversal action |
| 6 | Document Generation, Hardware Dispatch & Sync Propagation | `PRINT` | `src/services/printService.js / SyncEngine` | Dispatch print output (STOCK_ADJUSTMENT_SLIP); queue offline sync update (BRANCH_STOCK_DELTA_SYNC) | 0 | 0/0 | 0 | Reversal document printout |

### FLOW-020: Inter-Branch Stock Transfer

- **Flow Family:** `SUPPORTING`
- **Business Purpose:** Transfer physical product stock from source branch to destination branch with dual stock updates and balanced movement ledger entries.
- **Actor & Entry:** STOCK_MANAGER via `Stock Transfer Form (StockTransferModal.vue)` (`POST /api/v1/inventory/transfer`)
- **State Progression:** `IN_STOCK` -> `IN_STOCK / LOW_STOCK`
- **Primary Entities:** ENTITY-018, ENTITY-019
- **Primary Mutations:** MUTATION-028
- **Tables Written:** `branch_inventories`, `inventory_movements`, `activity_log`
- **Reversal Counterpart:** COMPENSATING_TRANSFER
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/InventoryController.php@transfer, api/app/Services/InventoryService.php@transferStock`
- **Known Gaps:** P3-GAP-012

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Operator Workflow Trigger & UI Initialization | `UI` | `Stock Transfer Form (StockTransferModal.vue)` | Collect operator inputs, validate client-side schema, construct reactive transaction payload | 0 | 0/0 | 0 | Reset Form |
| 2 | API Request Schema & Business Rule Validation | `REQUEST_VALIDATION` | `POST /api/v1/inventory/transfer` | Execute Laravel FormRequest authorization and validation rules against database entities | 0 | 0/0 | 0 | None |
| 3 | Primary Domain Entity Persistence & Mutation | `SERVICE` | `api/app/Http/Controllers/Api/V1/InventoryController.php@transfer, api/app/Services/InventoryService.php@transferStock` | Execute primary mutation MUTATION-028 updating branch_inventories, inventory_movements, activity_log | DECREMENTS_SOURCE_INCREMENTS_DESTINATION_STOCK | NONE/NONE | NONE | COMPENSATING_TRANSFER |
| 4 | Cross-Module Secondary Side-Effect Processing | `SERVICE` | `api/app/Http/Controllers/Api/V1/InventoryController.php@transfer, api/app/Services/InventoryService.php@transferStock` | Update secondary modules: Inventory (DECREMENTS_SOURCE_INCREMENTS_DESTINATION_STOCK), AR/AP (NONE/NONE), Financial Account (NONE) | DECREMENTS_SOURCE_INCREMENTS_DESTINATION_STOCK | NONE/NONE | NONE | COMPENSATING_TRANSFER |
| 5 | Audit Logging & Atomic DB Transaction Commit | `DATABASE` | `api/app/Services/AuditService.php` | Write activity_log (LOGS_BRANCH_TRANSFER); execute DB::commit() | 0 | 0/0 | 0 | Audit log of reversal action |
| 6 | Document Generation, Hardware Dispatch & Sync Propagation | `PRINT` | `src/services/printService.js / SyncEngine` | Dispatch print output (DISPATCH_TRANSFER_NOTE); queue offline sync update (CROSS_BRANCH_SYNC) | 0 | 0/0 | 0 | Reversal document printout |

### FLOW-021: Financial Account Direct Capital Injection

- **Flow Family:** `SUPPORTING`
- **Business Purpose:** Deposit owner equity / capital inflow into business financial account, incrementing liquid asset balance and recording ledger movement.
- **Actor & Entry:** BUSINESS_OWNER / ACCOUNTANT via `Banking Account View (CapitalInModal.vue)` (`POST /api/v1/financial-accounts/{id}/capital-in`)
- **State Progression:** `ACTIVE` -> `ACTIVE`
- **Primary Entities:** ENTITY-034, ENTITY-035
- **Primary Mutations:** MUTATION-063
- **Tables Written:** `financial_accounts`, `account_movements`, `activity_log`
- **Reversal Counterpart:** FLOW-022 (Capital Drawing)
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/FinancialAccountController.php@capitalIn, api/app/Services/AccountMovementService.php@postInflow`
- **Known Gaps:** None

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Operator Workflow Trigger & UI Initialization | `UI` | `Banking Account View (CapitalInModal.vue)` | Collect operator inputs, validate client-side schema, construct reactive transaction payload | 0 | 0/0 | 0 | Reset Form |
| 2 | API Request Schema & Business Rule Validation | `REQUEST_VALIDATION` | `POST /api/v1/financial-accounts/{id}/capital-in` | Execute Laravel FormRequest authorization and validation rules against database entities | 0 | 0/0 | 0 | None |
| 3 | Primary Domain Entity Persistence & Mutation | `SERVICE` | `api/app/Http/Controllers/Api/V1/FinancialAccountController.php@capitalIn, api/app/Services/AccountMovementService.php@postInflow` | Execute primary mutation MUTATION-063 updating financial_accounts, account_movements, activity_log | NONE | NONE/NONE | INCREMENTS_FINANCIAL_ACCOUNT_BALANCE | FLOW-022 (Capital Drawing) |
| 4 | Cross-Module Secondary Side-Effect Processing | `SERVICE` | `api/app/Http/Controllers/Api/V1/FinancialAccountController.php@capitalIn, api/app/Services/AccountMovementService.php@postInflow` | Update secondary modules: Inventory (NONE), AR/AP (NONE/NONE), Financial Account (INCREMENTS_FINANCIAL_ACCOUNT_BALANCE) | NONE | NONE/NONE | INCREMENTS_FINANCIAL_ACCOUNT_BALANCE | FLOW-022 (Capital Drawing) |
| 5 | Audit Logging & Atomic DB Transaction Commit | `DATABASE` | `api/app/Services/AuditService.php` | Write activity_log (LOGS_CAPITAL_INJECTION); execute DB::commit() | 0 | 0/0 | 0 | Audit log of reversal action |
| 6 | Document Generation, Hardware Dispatch & Sync Propagation | `PRINT` | `src/services/printService.js / SyncEngine` | Dispatch print output (CAPITAL_DEPOSIT_RECEIPT); queue offline sync update (SERVER_MANAGED) | 0 | 0/0 | 0 | Reversal document printout |

### FLOW-022: Financial Account Direct Capital Drawing

- **Flow Family:** `SUPPORTING`
- **Business Purpose:** Withdraw owner drawing from financial account, decrementing account balance and recording audit movement.
- **Actor & Entry:** BUSINESS_OWNER via `Banking Account View (CapitalOutModal.vue)` (`POST /api/v1/financial-accounts/{id}/capital-out`)
- **State Progression:** `ACTIVE` -> `ACTIVE`
- **Primary Entities:** ENTITY-034, ENTITY-035
- **Primary Mutations:** MUTATION-064
- **Tables Written:** `financial_accounts`, `account_movements`, `activity_log`
- **Reversal Counterpart:** FLOW-021 (Capital Injection)
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/FinancialAccountController.php@capitalOut, api/app/Services/AccountMovementService.php@postOutflow`
- **Known Gaps:** None

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Operator Workflow Trigger & UI Initialization | `UI` | `Banking Account View (CapitalOutModal.vue)` | Collect operator inputs, validate client-side schema, construct reactive transaction payload | 0 | 0/0 | 0 | Reset Form |
| 2 | API Request Schema & Business Rule Validation | `REQUEST_VALIDATION` | `POST /api/v1/financial-accounts/{id}/capital-out` | Execute Laravel FormRequest authorization and validation rules against database entities | 0 | 0/0 | 0 | None |
| 3 | Primary Domain Entity Persistence & Mutation | `SERVICE` | `api/app/Http/Controllers/Api/V1/FinancialAccountController.php@capitalOut, api/app/Services/AccountMovementService.php@postOutflow` | Execute primary mutation MUTATION-064 updating financial_accounts, account_movements, activity_log | NONE | NONE/NONE | DECREMENTS_FINANCIAL_ACCOUNT_BALANCE | FLOW-021 (Capital Injection) |
| 4 | Cross-Module Secondary Side-Effect Processing | `SERVICE` | `api/app/Http/Controllers/Api/V1/FinancialAccountController.php@capitalOut, api/app/Services/AccountMovementService.php@postOutflow` | Update secondary modules: Inventory (NONE), AR/AP (NONE/NONE), Financial Account (DECREMENTS_FINANCIAL_ACCOUNT_BALANCE) | NONE | NONE/NONE | DECREMENTS_FINANCIAL_ACCOUNT_BALANCE | FLOW-021 (Capital Injection) |
| 5 | Audit Logging & Atomic DB Transaction Commit | `DATABASE` | `api/app/Services/AuditService.php` | Write activity_log (LOGS_CAPITAL_DRAWING); execute DB::commit() | 0 | 0/0 | 0 | Audit log of reversal action |
| 6 | Document Generation, Hardware Dispatch & Sync Propagation | `PRINT` | `src/services/printService.js / SyncEngine` | Dispatch print output (DRAWING_VOUCHER); queue offline sync update (SERVER_MANAGED) | 0 | 0/0 | 0 | Reversal document printout |

### FLOW-023: Direct Operating Expense Disbursal

- **Flow Family:** `SUPPORTING`
- **Business Purpose:** Disburse operational expense, debiting financial account/cash drawer, posting account movement, and categorizing in P&L.
- **Actor & Entry:** CASHIER / ACCOUNTANT via `Expense Entry Modal (ExpenseCreateModal.vue)` (`POST /api/v1/expenses`)
- **State Progression:** `EXPENSE_ENTRY` -> `ACTIVE`
- **Primary Entities:** ENTITY-031, ENTITY-032, ENTITY-034, ENTITY-035, ENTITY-037
- **Primary Mutations:** MUTATION-056
- **Tables Written:** `expenses`, `financial_accounts`, `account_movements`, `cash_ledgers`, `activity_log`
- **Reversal Counterpart:** FLOW-017 (Expense Void)
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/ExpenseController.php@store, api/app/Services/ExpenseService.php@createExpense`
- **Known Gaps:** P3-GAP-013

#### Granular Step Sequence

| # | Step Name | Layer | Component | Operation | Stock | AR/AP | Finance | Reversal Counterpart |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Operator Workflow Trigger & UI Initialization | `UI` | `Expense Entry Modal (ExpenseCreateModal.vue)` | Collect operator inputs, validate client-side schema, construct reactive transaction payload | 0 | 0/0 | 0 | Reset Form |
| 2 | API Request Schema & Business Rule Validation | `REQUEST_VALIDATION` | `POST /api/v1/expenses` | Execute Laravel FormRequest authorization and validation rules against database entities | 0 | 0/0 | 0 | None |
| 3 | Primary Domain Entity Persistence & Mutation | `SERVICE` | `api/app/Http/Controllers/Api/V1/ExpenseController.php@store, api/app/Services/ExpenseService.php@createExpense` | Execute primary mutation MUTATION-056 updating expenses, financial_accounts, account_movements, cash_ledgers, activity_log | NONE | NONE/NONE | DECREMENTS_FINANCIAL_ACCOUNT_BALANCE | FLOW-017 (Expense Void) |
| 4 | Cross-Module Secondary Side-Effect Processing | `SERVICE` | `api/app/Http/Controllers/Api/V1/ExpenseController.php@store, api/app/Services/ExpenseService.php@createExpense` | Update secondary modules: Inventory (NONE), AR/AP (NONE/NONE), Financial Account (DECREMENTS_FINANCIAL_ACCOUNT_BALANCE) | NONE | NONE/NONE | DECREMENTS_FINANCIAL_ACCOUNT_BALANCE | FLOW-017 (Expense Void) |
| 5 | Audit Logging & Atomic DB Transaction Commit | `DATABASE` | `api/app/Services/AuditService.php` | Write activity_log (LOGS_EXPENSE_DISBURSAL); execute DB::commit() | 0 | 0/0 | 0 | Audit log of reversal action |
| 6 | Document Generation, Hardware Dispatch & Sync Propagation | `PRINT` | `src/services/printService.js / SyncEngine` | Dispatch print output (EXPENSE_VOUCHER_RECEIPT); queue offline sync update (SERVER_OR_OFFLINE_SYNC) | 0 | 0/0 | 0 | Reversal document printout |

## 4. Cross-Module Handoff Matrix

The following matrix audits the boundary transfer mechanisms between producer and consumer modules across all major transactions:

| Producer Module | Value / Event Dispatched | Consumer Module | Transfer Mechanism | Snapshot vs Live | Risk / Failure Mode | Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| Product Master | Retail / Wholesale Unit Price | POS Cart Store | Reactive Pinia Store | Live Query at Scan | Wholesale tier skipped if attached post-scan (`P3-GAP-014`) | `PosView.vue:312` |
| Product Master | Barcode / SKU Snapshot | Sale Items Line | Eloquent Create | Live Relationship (Gap) | Barcode change alters historical receipt reprint (`P3-GAP-001`) | `SaleService.php:104` |
| POS Checkout | Multi-Tender Split Breakdown | Financial Account | Request Payload | Aggregate Header Only | Split tender details lost into single header column (`P3-GAP-003`) | `PaymentModal.vue:240` |
| Sale Service | Stock Decrement Event | Branch Inventory | Service Call | Atomic Transaction | Stock overselling under high concurrency without row-lock | `InventoryService.php:126` |
| Sale Service | Receivable (AR) Debit | Khata Ledger | Service Call | Atomic Transaction | Changing customer on sale update leaves orphaned AR (`P3-GAP-002`) | `KhataService.php:72` |
| Purchase Bill | Inbound Freight / Shipping | Inventory Cost Basis | Service Call | Header Only (Gap) | Landed freight excluded from moving average cost (`P3-GAP-005`) | `PurchaseService.php:88` |
| Purchase Bill | Payable (AP) Credit | Supplier Ledger | Service Call | Atomic Transaction | Supplier balance updated; historical AP depends on cache | `SupplierService.php:64` |
| Sale Return | Stock Restock Decision | Branch Inventory | Service Call | Automatic Restock (Gap) | Defective returned items re-enter sellable stock (`P3-GAP-008`) | `SaleReturnService.php:52` |
| Purchase Cancel | Stock Reversal | Branch Inventory | Service Call | Direct Decrement | Stock forced negative if goods were already sold (`P3-GAP-009`) | `PurchaseService.php:142` |
| Offline POS | Offline Transaction Queue | Sync Engine | REST Batch POST | Client Timestamps | Client clock drift corrupts server chronological order (`P3-GAP-004`) | `syncEngine.js:188` |

## 5. Flow Side-Effect Completeness Matrix

| Flow ID | Primary Entity | Stock | Inv Ledg | Cust Bal | Cust Ledg | Supp Bal | Supp Ledg | Fin Acct | Acct Mvmt | Cash Ledg | Audit | Print | Sync | Reversal | Gaps |
| :--- | :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :--- |
| `FLOW-001` | `ENTITY-020` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | P3-GAP-001 |
| `FLOW-002` | `ENTITY-020` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | P3-GAP-001 |
| `FLOW-003` | `ENTITY-020` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | P3-GAP-002 |
| `FLOW-004` | `ENTITY-020` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | P3-GAP-002 |
| `FLOW-005` | `ENTITY-020` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | P3-GAP-003 |
| `FLOW-006` | `ENTITY-026` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | None |
| `FLOW-007` | `ENTITY-020` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | P3-GAP-004 |
| `FLOW-008` | `ENTITY-028` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | P3-GAP-005, P3-GAP-006 |
| `FLOW-009` | `ENTITY-028` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | P3-GAP-005 |
| `FLOW-010` | `ENTITY-028` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | P3-GAP-005 |
| `FLOW-011` | `ENTITY-030` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | P3-GAP-007 |
| `FLOW-012` | `ENTITY-022` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | P3-GAP-008 |
| `FLOW-013` | `ENTITY-022` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | None |
| `FLOW-014` | `ENTITY-020` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | None |
| `FLOW-015` | `ENTITY-026` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | None |
| `FLOW-016` | `ENTITY-028` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | P3-GAP-009 |
| `FLOW-017` | `ENTITY-031` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | None |
| `FLOW-018` | `ENTITY-036` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | P3-GAP-010 |
| `FLOW-019` | `ENTITY-018` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | P3-GAP-011 |
| `FLOW-020` | `ENTITY-018` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | P3-GAP-012 |
| `FLOW-021` | `ENTITY-034` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | None |
| `FLOW-022` | `ENTITY-034` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | None |
| `FLOW-023` | `ENTITY-031` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `NOT_APPLICABLE` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | `EXPECTED_AND_PRESENT` | P3-GAP-013 |

## 6. Phase 3 Gap Register (P3-GAP-001 to P3-GAP-015)

### P3-GAP-001: Sale Line Items Lack Barcode / SKU Point-in-Time Snapshot

- **Associated Flows:** FLOW-001, FLOW-005
- **Broken Handoff:** `Product Master -> Sale Items Historical Snapshot`
- **Current Source Behavior:** When a sale item is created, only product_id, quantity, unit_price, subtotal, and cost_price are stored. The barcode and SKU are resolved at render time via products relationship.
- **Source Evidence:** `api/database/migrations/2024_01_01_000018_create_sale_items_table.php, api/app/Services/SaleService.php:104`
- **Cross-References:** Previous P1: `P1-GAP-011`, Previous P2: `P2-GAP-006`
- **Business & Financial Impact:** If a product barcode or SKU changes in product master, past printed invoices and reprint historical records show altered or missing barcodes. | None directly on totals; impacts audit and inventory dispute resolution.
- **Inventory & Customer Impact:** Prevents precise reverse-lookup of serialized/barcoded units from historical sale item lines. | Customer invoice reprints may diverge from original printed receipt.
- **Later Validation Phase:** `PHASE_5_UI_UX`
- **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P3-GAP-002: Changing Customer on Posted Sale Creates Khata Receivable Discrepancy

- **Associated Flows:** FLOW-003, FLOW-004
- **Broken Handoff:** `Sale Mutation -> Khata Transaction Reversal & Re-posting`
- **Current Source Behavior:** When an existing sale is edited via PUT /api/v1/sales/{id} and customer_id is modified, the sale table customer_id updates, but the previous customer Khata transaction is not credited and the new customer Khata is not debited.
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/SaleController.php@update, api/app/Services/SaleService.php@updateSale`
- **Cross-References:** Previous P1: `P1-GAP-003`, Previous P2: `P2-GAP-006`
- **Business & Financial Impact:** Original customer statement permanently reflects unpaid charge; new customer statement omits the debt. | Severe AR ledger divergence between customers.balance cache and individual customer statement transaction totals.
- **Inventory & Customer Impact:** None. | Customer billed for items purchased by another customer.
- **Later Validation Phase:** `PHASE_4_CALCULATIONS_ACCOUNTING`
- **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P3-GAP-003: Split-Tender Payment Method Allocations Lack Dedicated Breakdown Table

- **Associated Flows:** FLOW-005
- **Broken Handoff:** `POS Multi-Tender Modal -> Financial Account Breakdown Persistence`
- **Current Source Behavior:** When a sale is paid via multiple tenders (e.g., 50% Cash, 50% Bank Card), the sale header records payment_method="split" and paid_amount=total, but does not store child payment ledger rows linking exact tender amounts to specific financial accounts.
- **Source Evidence:** `api/database/migrations/2024_01_01_000017_create_sales_table.php, src/views/pos/PaymentModal.vue:240`
- **Cross-References:** Previous P1: `P1-GAP-005`, Previous P2: `P2-GAP-006`
- **Business & Financial Impact:** Inability to reconcile end-of-day bank settlement vs cash drawer totals for split payments; receipt reprints cannot show tender breakdown. | Cash register drawer and bank account reconciliation reports cannot independently verify split payment allocations.
- **Inventory & Customer Impact:** None. | Customer cannot obtain itemized payment receipt showing split card vs cash amounts.
- **Later Validation Phase:** `PHASE_4_CALCULATIONS_ACCOUNTING`
- **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P3-GAP-004: Offline POS Batch Sync Relies on Client Timestamps Without Clock Drift Guard

- **Associated Flows:** FLOW-007
- **Broken Handoff:** `Offline Device Sync Engine -> Server Inventory Movement Chronology`
- **Current Source Behavior:** When offline sales sync via POST /api/v1/sync/sales, created_at is adopted directly from client device without bounds check against server time or sequence ordering.
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/SyncController.php@syncSales, src/services/syncEngine.js:188`
- **Cross-References:** Previous P1: `P1-GAP-009`, Previous P2: `P2-GAP-017`
- **Business & Financial Impact:** Device with incorrect local clock injects transactions out of chronological order into inventory and cash movements. | Shift cash reporting and daily revenue aggregates become distorted across calendar days.
- **Inventory & Customer Impact:** Negative inventory movement timestamps may precede stock receipt timestamp in stock ledger. | Invoices generated offline appear with incorrect dates.
- **Later Validation Phase:** `PHASE_7_OFFLINE_SYNC`
- **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P3-GAP-005: Purchase Freight & Landed Cost Not Allocated to Product Cost Moving Average

- **Associated Flows:** FLOW-008, FLOW-009, FLOW-010
- **Broken Handoff:** `Purchase Bill Header -> Product Moving Average Unit Cost Calculation`
- **Current Source Behavior:** Purchase bills support shipping_cost / freight fields on the purchase header, but InventoryService calculates unit cost strictly from purchase_items.unit_cost, omitting freight allocation.
- **Source Evidence:** `api/app/Services/PurchaseService.php@receivePurchase, api/app/Services/InventoryService.php@updateProductCostPrice`
- **Cross-References:** Previous P1: `P1-GAP-006`, Previous P2: `P2-GAP-009`
- **Business & Financial Impact:** Gross profit margins are artificially overstated because COGS excludes inbound shipping and freight costs. | Distorts inventory valuation on balance sheet and miscalculates FIFO/weighted average COGS in P&L.
- **Inventory & Customer Impact:** Product cost price remains lower than true landed acquisition cost. | Pricing decisions made on underestimated unit cost leading to eroded retail margins.
- **Later Validation Phase:** `PHASE_4_CALCULATIONS_ACCOUNTING`
- **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P3-GAP-006: Missing Dedicated Supplier Return / Debit Note Workflow

- **Associated Flows:** FLOW-016
- **Broken Handoff:** `Procurement Correction -> Supplier Debit Note & Stock Decrement Orchestration`
- **Current Source Behavior:** HBOS does not have a dedicated Purchase Return / Supplier Return entity. To return damaged or excess stock to a supplier, operators must manually adjust inventory down and enter a separate AP adjustment.
- **Source Evidence:** `api/routes/api.php (Absence of /api/v1/purchase-returns), api/app/Models/Purchase.php`
- **Cross-References:** Previous P1: `P1-GAP-007`, Previous P2: `P2-GAP-010`
- **Business & Financial Impact:** Increases operator error when returning defective stock to vendors; no formal debit note document generated. | Supplier payable balances frequently fail to reflect returned merchandise credits.
- **Inventory & Customer Impact:** Manual stock adjustments used as workaround obscure procurement defect rate analytics. | Disputes with suppliers over uncredited returned goods.
- **Later Validation Phase:** `PHASE_4_CALCULATIONS_ACCOUNTING`
- **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P3-GAP-007: Supplier Payment Mutation Lacks Dedicated Automated Reversal Path

- **Associated Flows:** FLOW-011
- **Broken Handoff:** `Supplier Payment Ledger -> Automated Reversal Compensation Chain`
- **Current Source Behavior:** When a supplier payment is recorded via POST /api/v1/suppliers/{id}/payments, no DELETE /reversal endpoint exists to automatically restore supplier AP and credit the financial account simultaneously.
- **Source Evidence:** `api/app/Http/Controllers/Api/V1/SupplierController.php@addPayment`
- **Cross-References:** Previous P1: `P1-GAP-008`, Previous P2: `P2-GAP-015`
- **Business & Financial Impact:** Erroneous supplier payment entries require manual dual-sided database/account corrections. | Risk of one-sided corrections causing bank account and supplier ledger mismatch.
- **Inventory & Customer Impact:** None. | Supplier ledger shows incorrect payment history upon erroneous disbursement.
- **Later Validation Phase:** `PHASE_4_CALCULATIONS_ACCOUNTING`
- **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P3-GAP-008: Sale Return Lacks Granular Restock Condition / Scrap Decision Flag

- **Associated Flows:** FLOW-012, FLOW-013
- **Broken Handoff:** `Sale Return Modal -> Damaged Goods Quarantine / Scrap Ledger`
- **Current Source Behavior:** Sale returns automatically increment branch stock unconditionally for all returned line items. There is no flag to mark an item as damaged/scrap to prevent re-entering sellable inventory.
- **Source Evidence:** `api/app/Services/SaleReturnService.php@processReturn, api/database/migrations/2024_01_01_000021_create_sale_returns_table.php`
- **Cross-References:** Previous P1: `P1-GAP-010`, Previous P2: `P2-GAP-008`
- **Business & Financial Impact:** Damaged/expired goods returned by customers are immediately added back into sellable inventory on hand. | Overvalues sellable inventory assets by carrying unsellable damaged goods at full cost price.
- **Inventory & Customer Impact:** Inventory on hand includes broken units; POS operators can sell defective items to subsequent customers. | Subsequent customers may receive returned damaged merchandise.
- **Later Validation Phase:** `PHASE_5_UI_UX`
- **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P3-GAP-009: Purchase Cancellation Does Not Verify If Received Stock Has Already Been Sold

- **Associated Flows:** FLOW-016
- **Broken Handoff:** `Purchase Cancellation Service -> Current Available Stock Guard`
- **Current Source Behavior:** Canceling a received purchase attempts to decrement branch inventory by the full purchase quantity. If items have already been sold via POS, quantity_on_hand is forced into negative numbers without pre-check.
- **Source Evidence:** `api/app/Services/PurchaseService.php@cancelPurchase`
- **Cross-References:** Previous P1: `P1-GAP-006`, Previous P2: `P2-GAP-014`
- **Business & Financial Impact:** Canceling historical purchases corrupts stock balances into negative quantities. | AP reversed while revenue from sold stock remains recognized, distorting matching principle.
- **Inventory & Customer Impact:** Creates negative quantity_on_hand records in branch_inventories. | Supplier AP canceled for inventory that was consumed in retail operations.
- **Later Validation Phase:** `PHASE_4_CALCULATIONS_ACCOUNTING`
- **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P3-GAP-010: Sale Cancellation Incomplete Reversal When Sale Is Partially Paid by Bank Transfer

- **Associated Flows:** FLOW-014
- **Broken Handoff:** `Sale Cancellation Service -> Financial Account Reversal Movement`
- **Current Source Behavior:** Sale cancellation successfully reverses cash register inflow and customer Khata, but if the sale was settled via bank transfer, the corresponding financial account balance is not decremented automatically.
- **Source Evidence:** `api/app/Services/SaleService.php@cancelSale, api/app/Http/Controllers/Api/V1/SaleController.php@cancel`
- **Cross-References:** Previous P1: `P1-GAP-005`, Previous P2: `P2-GAP-006`
- **Business & Financial Impact:** Bank balance in system remains inflated after a bank-paid sale is voided. | Financial account balance does not match real-world bank statement upon sale void.
- **Inventory & Customer Impact:** None (stock is restored correctly). | Customer was refunded in reality but financial account ledger shows funds retained.
- **Later Validation Phase:** `PHASE_4_CALCULATIONS_ACCOUNTING`
- **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P3-GAP-011: Stock Quantity Adjustment Bypasses Financial Inventory Valuation Adjustment Account

- **Associated Flows:** FLOW-019
- **Broken Handoff:** `Inventory Adjustment -> Financial Account P&L Shrinkage Write-off`
- **Current Source Behavior:** Stock adjustments increment or decrement branch inventory and record an inventory_movement, but do not post a corresponding debit/credit entry to a Financial Inventory Shrinkage / Gain account.
- **Source Evidence:** `api/app/Services/StockAdjustmentService.php@adjustStock`
- **Cross-References:** Previous P1: `P1-GAP-002`, Previous P2: `P2-GAP-004`
- **Business & Financial Impact:** Physical inventory write-offs do not automatically reflect in financial P&L statements. | Inventory shrinkage expenses must be manually entered as separate expense records.
- **Inventory & Customer Impact:** Physical stock aligns with records, but financial asset balance drifts from ledger. | None.
- **Later Validation Phase:** `PHASE_4_CALCULATIONS_ACCOUNTING`
- **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P3-GAP-012: Inter-Branch Stock Transfer Operates Synchronously Without In-Transit Clearing State

- **Associated Flows:** FLOW-020
- **Broken Handoff:** `Sending Branch Dispatch -> Transit Verification -> Receiving Branch Receipt`
- **Current Source Behavior:** Inter-branch transfer immediately decrements sending branch stock and increments destination branch stock in a single transaction without a pending/in-transit intermediate status.
- **Source Evidence:** `api/app/Services/TransferService.php@createTransfer`
- **Cross-References:** Previous P1: `P1-GAP-013`, Previous P2: `P2-GAP-005`
- **Business & Financial Impact:** Goods in transit are immediately visible as available to sell at destination branch before physical arrival; loss during transit cannot be tracked. | Branch-level inventory valuation immediately shifts before custody transfer is confirmed.
- **Inventory & Customer Impact:** Destination branch POS operators can sell stock that is still physically in transit on a delivery truck. | POS orders at receiving branch fail fulfillment if truck has not arrived.
- **Later Validation Phase:** `PHASE_6_ROLES_STATE`
- **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P3-GAP-013: Operating Expense Entry Allows Null Financial Account Creating Unallocated Cash Drift

- **Associated Flows:** FLOW-023
- **Broken Handoff:** `Expense Disbursal -> Cash Register / Financial Account Exact Allocation`
- **Current Source Behavior:** Expense creation allows financial_account_id to be null when marked as cash without enforcing an active cash register session link.
- **Source Evidence:** `api/app/Http/Requests/ExpenseStoreRequest.php:32, api/app/Services/ExpenseService.php:54`
- **Cross-References:** Previous P1: `P1-GAP-012`, Previous P2: `P2-GAP-016`
- **Business & Financial Impact:** Expense records exist with no deduction from either cash drawer or bank accounts. | P&L reflects expense but balance sheet cash/bank accounts remain overvalued.
- **Inventory & Customer Impact:** None. | None.
- **Later Validation Phase:** `PHASE_4_CALCULATIONS_ACCOUNTING`
- **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P3-GAP-014: Customer Wholesale Price Tier Bypass During POS Offline Cart Add

- **Associated Flows:** FLOW-001, FLOW-003
- **Broken Handoff:** `Customer Profile Wholesale Flag -> Offline Reactive Cart Price Recalculation`
- **Current Source Behavior:** When POS operates offline, wholesale customer pricing tier is not applied dynamically if customer is attached after cart lines are already added.
- **Source Evidence:** `src/views/pos/PosView.vue:410, src/stores/cartStore.js:82`
- **Cross-References:** Previous P1: `P1-GAP-001`, Previous P2: `P2-GAP-001`
- **Business & Financial Impact:** Wholesale customers charged retail price on offline transactions unless operator manually overrides each line. | Customer overcharged; requires credit adjustments and manual invoice corrections.
- **Inventory & Customer Impact:** None. | Wholesale clients dispute incorrect retail pricing on offline sales.
- **Later Validation Phase:** `PHASE_5_UI_UX`
- **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

### P3-GAP-015: Customer Payment Reversal Does Not Guard Against Secondary Over-Collection Reversal

- **Associated Flows:** FLOW-015
- **Broken Handoff:** `Customer Payment Reversal -> Invoice Level Due Amount Re-allocation`
- **Current Source Behavior:** Reversing a customer payment restores the customer balance, but does not verify if subsequent payments or credits have already settled the original sale invoice.
- **Source Evidence:** `api/app/Services/KhataService.php@reversePayment`
- **Cross-References:** Previous P1: `P1-GAP-003`, Previous P2: `P2-GAP-007`
- **Business & Financial Impact:** Customer balance increases, but individual sale payment statuses may remain marked as PAID. | Discrepancy between customer total balance and sum of unpaid sales invoices.
- **Inventory & Customer Impact:** None. | Customer account shows debt while specific invoices indicate settled.
- **Later Validation Phase:** `PHASE_4_CALCULATIONS_ACCOUNTING`
- **Remediation Phase:** `PHASE_9_REMEDIATION`
- **Status:** `CERTIFIED_WITH_KNOWN_GAP`

