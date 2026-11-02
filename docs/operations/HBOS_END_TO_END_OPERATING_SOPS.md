# SkyraSoft HBOS Management System — End-to-End Standard Operating Procedures (SOPs)
## Authoritative Step-by-Step Operator Manual for Retail, Inventory, Financial & Support Operations

**Document Version:** 10.0  
**Status:** Certified Complete  
**Coverage:** 35 End-to-End SOPs spanning all 11 Operating Domains  

---

## 1. SOP Master Directory

| SOP ID | Domain | SOP Title | Target Roles | Key GAPs / Migrations |
| :--- | :--- | :--- | :--- | :--- |
| **SOP-001** | `SALES_POS` | Standard Retail Cash Sale Checkout | Salesperson / Cashier, Branch Manager | FLOW-001, FORM-002, FORM-003, ACTION-004, ACTION-008, GAP-001, GAP-003, GAP-018, GAP-020, GAP-023 |
| **SOP-002** | `SALES_POS` | Digital / Card / Bank Payment Sale Checkout | Salesperson / Cashier, Branch Manager | FLOW-001, FORM-003, ACTION-008, GAP-003, GAP-018 |
| **SOP-003** | `SALES_POS` | Split-Tender Multi-Payment Sale Checkout | Salesperson / Cashier, Branch Manager | FLOW-001, FORM-003, ACTION-008, GAP-003, MIG-003, P9-EMERGENT-001 |
| **SOP-004** | `SALES_POS` | Wholesale / Tiered Pricing Sale Checkout | Salesperson / Cashier, Branch Manager | FLOW-001, FORM-002, ACTION-004, GAP-002, MIG-002 |
| **SOP-005** | `SALES_POS` | Customer Khata Credit Sale Checkout | Salesperson / Cashier, Branch Manager | FLOW-001, FLOW-007, FORM-003, ACTION-008, GAP-008, GAP-009 |
| **SOP-006** | `SALES_POS` | Thermal & A4 Invoice Reprinting | Salesperson / Cashier, Branch Manager, Accountant | FLOW-001, TABLE-004, ACTION-011, GAP-001, GAP-023, GAP-028 |
| **SOP-007** | `SALES_POS` | Sale Cancellation & Stock Restoration | Branch Manager, Business Owner | FLOW-002, TABLE-004, ACTION-012, GAP-015, GAP-025 |
| **SOP-008** | `SALES_POS` | Sale Return & Restock Condition Handling | Branch Manager, Salesperson / Cashier, Accountant | FLOW-003, FORM-005, ACTION-015, GAP-010, MIG-007 |
| **SOP-009** | `CUSTOMER_KHATA` | Create & Maintain Customer Profile | Salesperson / Cashier, Branch Manager, Accountant | FLOW-006, FORM-013, ACTION-036, GAP-030, MIG-010 |
| **SOP-010** | `CUSTOMER_KHATA` | Customer Khata Payment Receipt & Settlement | Accountant, Branch Manager, Salesperson / Cashier | FLOW-007, FORM-014, ACTION-039, GAP-008, GAP-009 |
| **SOP-011** | `CUSTOMER_KHATA` | Customer Payment Reversal & Khata Correction | Accountant, Business Owner | FLOW-007, ACTION-041, GAP-008, GAP-015 |
| **SOP-012** | `INVENTORY` | Product Catalog Item Creation & Pricing Setup | Inventory Manager, Business Owner | FLOW-008, FORM-006, ACTION-017, GAP-002, MIG-002 |
| **SOP-013** | `INVENTORY` | Physical Stock Count Adjustment & Reconciliation | Inventory Manager, Business Owner | FLOW-010, FORM-010, ACTION-029, GAP-004, GAP-006 |
| **SOP-014** | `INVENTORY` | Inter-Branch Stock Transfer (Dispatch & Receipt) | Inventory Manager, Branch Manager | FLOW-009, FORM-011, ACTION-031, ACTION-032, GAP-007, MIG-006 |
| **SOP-015** | `PROCUREMENT_SUPPLIER` | Create Purchase Order & Goods Receipt (GRN) with Landed Cost | Inventory Manager, Accountant, Business Owner | FLOW-004, FORM-015, FORM-016, ACTION-042, GAP-004, GAP-005, MIG-005 |
| **SOP-016** | `PROCUREMENT_SUPPLIER` | Purchase Cancellation Guarded Workflow | Business Owner, Inventory Manager | FLOW-005, ACTION-044, GAP-006, GAP-015, GAP-025 |
| **SOP-017** | `PROCUREMENT_SUPPLIER` | Supplier Return / Debit Note Processing | Inventory Manager, Accountant, Business Owner | FLOW-005, FORM-019, ACTION-051, GAP-011, MIG-008 |
| **SOP-018** | `PROCUREMENT_SUPPLIER` | Supplier Payment Processing & Reversal | Accountant, Business Owner | FLOW-005, FORM-018, ACTION-049, ACTION-050, GAP-012 |
| **SOP-019** | `EXPENSES` | Operating Expense Posting & Mandatory Account Allocation | Accountant, Branch Manager, Business Owner | FLOW-011, FORM-020, ACTION-052, GAP-013, GAP-031, MIG-004, MIG-011 |
| **SOP-020** | `EXPENSES` | Expense Voiding & Reconciliation | Accountant, Business Owner | FLOW-011, ACTION-054, GAP-014, GAP-025 |
| **SOP-021** | `FINANCIAL_ACCOUNTS` | Financial Account Creation & Opening Balance Setup | Accountant, Business Owner | FLOW-012, FORM-024, ACTION-063, GAP-014 |
| **SOP-022** | `FINANCIAL_ACCOUNTS` | Inter-Account Fund Transfer (Cash to Bank / Bank to Cash) | Accountant, Business Owner | FLOW-013, FORM-025, ACTION-064, GAP-014 |
| **SOP-023** | `FINANCIAL_ACCOUNTS` | Capital Injection & Owner Drawing Management | Business Owner, Accountant | FLOW-014, FORM-026, ACTION-065, ACTION-066, GAP-014 |
| **SOP-024** | `USERS_STAFF` | Staff User Creation, Role Assignment & Wage Persistence | Business Owner | FLOW-015, FORM-022, ACTION-058, GAP-029, MIG-009 |
| **SOP-025** | `USERS_STAFF` | User Deactivation & Access Revocation | Business Owner | FLOW-015, ACTION-060, GAP-015 |
| **SOP-026** | `SETTINGS_BRANCH` | Store Branch Configuration & Multi-Outlet Setup | Business Owner | FLOW-016, FORM-027, ACTION-067 |
| **SOP-027** | `OFFLINE_SYNC` | Preparing POS Device for Offline Operation | Salesperson / Cashier, Branch Manager | FLOW-018, ACTION-078, GAP-021, GAP-030 |
| **SOP-028** | `OFFLINE_SYNC` | Performing Disconnected Offline POS Sales | Salesperson / Cashier | FLOW-019, FORM-002, ACTION-008, GAP-019, GAP-021, GAP-023 |
| **SOP-029** | `OFFLINE_SYNC` | Offline Queue Sync Replay & Stock Conflict Resolution | Salesperson / Cashier, Branch Manager | FLOW-020, TABLE-029, ACTION-078, GAP-019, GAP-021, GAP-022 |
| **SOP-030** | `PRINTING_HARDWARE` | Thermal Receipt & Hardware Printer Setup | Salesperson / Cashier, Branch Manager | FLOW-021, FORM-029, ACTION-072, ACTION-073, GAP-023 |
| **SOP-031** | `PRINTING_HARDWARE` | Printer Failure Post-Transaction Recovery | Salesperson / Cashier | FLOW-021, ACTION-011, GAP-001, GAP-023 |
| **SOP-032** | `PRINTING_HARDWARE` | Desktop-to-Mobile Peer-to-Peer Device Pairing | Branch Manager, Salesperson / Cashier, Business Owner | FLOW-023, FORM-031, ACTION-076, ACTION-077, GAP-024 |
| **SOP-033** | `SUPPORT_RECONCILIATION` | Historical Sale Item Snapshot Reconciliation Drill | Business Owner, Accountant | GAP-001, REPAIR-001, MIG-001, LIM-001 |
| **SOP-034** | `SUPPORT_RECONCILIATION` | Customer Khata Ledger Balance Auditing & Discrepancy Reconciliation | Accountant, Business Owner | GAP-008, REPAIR-005, LIM-002 |
| **SOP-035** | `SUPPORT_RECONCILIATION` | Legacy NULL Expense Account Audit & Reclassification | Accountant, Business Owner | GAP-013, REPAIR-003, MIG-004, LIM-003 |

---

## 2. Definitive Operational SOP Specifications

### `SOP-001`: Standard Retail Cash Sale Checkout

- **Operational Domain:** `SALES_POS`
- **Purpose:** Process a routine cash sales transaction at the POS counter, receive currency, calculate change, decrement stock, and issue thermal receipt
- **Applicable Roles:** Salesperson / Cashier, Branch Manager
- **Prerequisites:** Active cash drawer shift opened, products available in catalog, active branch selected
- **Starting Screen:** POS Counter (/pos)
- **Master Data Required:** Products Catalog, Cash Financial Account, Walk-in Customer
- **Branch / Tenant Context:** Assigned branch_id and active business_id

#### Step-by-Step Operating Procedure
1. Scan product barcode using hardware barcode scanner or search product by name in the POS search bar.
2. Adjust line item quantities and verify unit selling price and applicable tax rate.
3. Select 'Walk-in Customer' or choose existing customer profile.
4. Click 'Checkout' button to open payment allocation modal.
5. Select 'Cash' as payment tender and enter amount tendered by customer.
6. Verify server-calculated change due to customer.
7. Click 'Complete Sale' to commit transaction.
8. Collect cash, hand change to customer, and present auto-printed thermal receipt.

#### System Rules & Business Effects
- **Validation Rules:** Sufficient stock on hand, cash tendered >= sale total (unless partial allowed), valid cashier session
- **System Calculations:** Line Subtotal = Qty * Price; Order Subtotal = Sum(Lines); Tax = Subtotal * TaxRate; Grand Total = Subtotal + Tax - Discount; Change = Tendered - Total
- **Database Mutation Effect:** Creates sales record, persists snapshot columns on sale_items, inserts sale_payments row, records account_movements on cash drawer, decrements branch_inventory
- **Inventory Effect:** Immediate decrement of branch product stock on hand; records 'sale' inventory movement
- **Financial & Accounting Effect:** Increments cash drawer account balance; records revenue account movement
- **Print Output Result:** Thermal receipt (80mm/58mm) generated and printed after confirmed server transaction commit
- **Offline Working Mode:** Queued in Dexie offline_sales with temp-ID; auto-synced upon reconnection

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Sale completed successfully; receipt printed; POS cart reset for next transaction
- **Common Failure Scenarios:** If cash drawer deficit or insufficient stock, UI displays clear error alert and blocks submission
- **Correction & Reversal Path:** Use Sales History (/sales/history) -> Cancel Sale (SOP-010) or Sale Return (SOP-011)
- **Source Traceability:** `FLOW-001, FORM-002, FORM-003, ACTION-004, ACTION-008, GAP-001, GAP-003, GAP-018, GAP-020, GAP-023`

---
### `SOP-002`: Digital / Card / Bank Payment Sale Checkout

- **Operational Domain:** `SALES_POS`
- **Purpose:** Process counter sale using credit card, debit card, or mobile digital wallet settlement
- **Applicable Roles:** Salesperson / Cashier, Branch Manager
- **Prerequisites:** Active bank/POS terminal or merchant QR code available
- **Starting Screen:** POS Counter (/pos)
- **Master Data Required:** Products Catalog, Bank / Digital Wallet Financial Account
- **Branch / Tenant Context:** Assigned branch_id and active business_id

#### Step-by-Step Operating Procedure
1. Add products to POS cart via barcode scanner or grid selection.
2. Click 'Checkout' to trigger payment modal.
3. Select 'Card' or 'Bank Transfer' tender type.
4. Choose corresponding bank financial account and enter transaction / approval reference number.
5. Confirm customer has completed card swipe / digital transfer.
6. Click 'Complete Sale' to commit.
7. Issue customer receipt.

#### System Rules & Business Effects
- **Validation Rules:** Mandatory payment account selection, reference number format validation
- **System Calculations:** Line totals, order total, zero cash change calculated
- **Database Mutation Effect:** Inserts sales, sale_items (with snapshots), sale_payments, and account_movements linked to bank account
- **Inventory Effect:** Branch stock decremented
- **Financial & Accounting Effect:** Bank financial account balance increased by exact sale amount
- **Print Output Result:** Thermal receipt showing 'Paid via Card/Bank [Ref#]'
- **Offline Working Mode:** Queued in Dexie offline store

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Transaction confirmed and bank ledger updated
- **Common Failure Scenarios:** If card declined on terminal, switch tender or cancel cart before commit
- **Correction & Reversal Path:** Sales History -> Cancel Sale or Return
- **Source Traceability:** `FLOW-001, FORM-003, ACTION-008, GAP-003, GAP-018`

---
### `SOP-003`: Split-Tender Multi-Payment Sale Checkout

- **Operational Domain:** `SALES_POS`
- **Purpose:** Process a single sale paid across multiple tender types (e.g. partial cash + partial card + partial customer credit)
- **Applicable Roles:** Salesperson / Cashier, Branch Manager
- **Prerequisites:** Registered customer selected if credit portion used; multiple funding accounts active
- **Starting Screen:** POS Counter (/pos)
- **Master Data Required:** Products Catalog, Customer Master, Cash & Bank Accounts
- **Branch / Tenant Context:** Assigned branch_id and active business_id

#### Step-by-Step Operating Procedure
1. Scan/select items into POS cart.
2. Select registered customer if Khata credit is included.
3. Open Checkout modal and click 'Split Tender / Add Payment Line'.
4. Line 1: Select 'Cash' -> enter amount (e.g. 1000.00).
5. Line 2: Click '+ Add Tender' -> select 'Card' -> enter amount (e.g. 500.00) -> select Bank Account.
6. Line 3 (Optional): If balance remains, select 'Customer Khata / Credit'.
7. Verify 'Total Paid' equals 'Grand Total' (or remaining is within customer credit limit).
8. Click 'Complete Sale' to commit.

#### System Rules & Business Effects
- **Validation Rules:** Sum of tender lines must satisfy sale total; credit portion cannot exceed customer available credit
- **System Calculations:** Total Tendered = Sum(Line amounts); Remaining Due = Grand Total - Total Tendered
- **Database Mutation Effect:** Inserts sales, sale_items (snapshots), multiple sale_payments records (MIG-003), discrete account_movements for cash/bank, and customer khata movement
- **Inventory Effect:** Branch stock decremented
- **Financial & Accounting Effect:** Cash drawer +1000, Bank +500, Customer Khata balance updated
- **Print Output Result:** Thermal receipt itemizing each tender breakdown line
- **Offline Working Mode:** Queued with multi-tender payload in offline_sales

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Transaction committed with normalized split payment records
- **Common Failure Scenarios:** If tender sum mismatch, submit button remains disabled with warning alert
- **Correction & Reversal Path:** Cancel Sale reverses all constituent payment movements atomically
- **Source Traceability:** `FLOW-001, FORM-003, ACTION-008, GAP-003, MIG-003, P9-EMERGENT-001`

---
### `SOP-004`: Wholesale / Tiered Pricing Sale Checkout

- **Operational Domain:** `SALES_POS`
- **Purpose:** Execute wholesale customer checkout applying persisted wholesale prices instead of retail prices
- **Applicable Roles:** Salesperson / Cashier, Branch Manager
- **Prerequisites:** Products configured with wholesale_price (MIG-002)
- **Starting Screen:** POS Counter (/pos)
- **Master Data Required:** Product Catalog with Wholesale Prices, Wholesale Customer Profile
- **Branch / Tenant Context:** Assigned branch_id and active business_id

#### Step-by-Step Operating Procedure
1. Select Wholesale Customer in POS header.
2. Toggle price mode to 'Wholesale Pricing'.
3. Scan/select items; verify line item reflects persisted wholesale_price.
4. Verify bulk order quantities.
5. Proceed to payment modal and complete transaction.

#### System Rules & Business Effects
- **Validation Rules:** Wholesale price must be non-null on product; requires wholesale permission
- **System Calculations:** Line Total = Qty * wholesale_price; Margin = (wholesale_price - unit_cost) * Qty
- **Database Mutation Effect:** Inserts sale with wholesale price snapshots in sale_items
- **Inventory Effect:** Branch stock decremented by bulk quantities
- **Financial & Accounting Effect:** Financial accounts updated with wholesale total
- **Print Output Result:** A4 or thermal invoice indicating wholesale price tier
- **Offline Working Mode:** Wholesale prices available in Dexie products cache

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Wholesale sale completed and cost margins recorded
- **Common Failure Scenarios:** If wholesale price missing, defaults to retail price with override warning
- **Correction & Reversal Path:** Standard cancellation or return procedure
- **Source Traceability:** `FLOW-001, FORM-002, ACTION-004, GAP-002, MIG-002`

---
### `SOP-005`: Customer Khata Credit Sale Checkout

- **Operational Domain:** `SALES_POS`
- **Purpose:** Execute on-credit checkout for an approved customer, adding transaction balance to Accounts Receivable
- **Applicable Roles:** Salesperson / Cashier, Branch Manager
- **Prerequisites:** Customer profile exists, credit limit >= current balance + sale total
- **Starting Screen:** POS Counter (/pos)
- **Master Data Required:** Customer Profile with Credit Limit, Products Catalog
- **Branch / Tenant Context:** Assigned branch_id and active business_id

#### Step-by-Step Operating Procedure
1. Search and select customer by name or phone in POS header.
2. Review current outstanding balance and available credit limit.
3. Add products to cart.
4. Open Checkout modal and select 'On Account / Khata Credit'.
5. Enter optional credit agreement notes or due date.
6. Click 'Complete Sale' to commit.

#### System Rules & Business Effects
- **Validation Rules:** Customer must not be walk-in; total cannot exceed available credit limit
- **System Calculations:** Customer New Balance = Previous Balance + Grand Total
- **Database Mutation Effect:** Inserts sale, sale_items, sale_payments (payment_type='credit'), customer_ledgers debit entry, and updates customers.balance
- **Inventory Effect:** Branch stock decremented
- **Financial & Accounting Effect:** Increments Accounts Receivable; no immediate cash drawer movement
- **Print Output Result:** Invoice showing 'Payment Status: On Account / Due' and customer's updated balance
- **Offline Working Mode:** Queued in offline_sales; customer balance updated locally in Dexie

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Credit sale posted to customer ledger
- **Common Failure Scenarios:** If credit limit exceeded, checkout blocked unless Manager override supplied
- **Correction & Reversal Path:** Cancel sale or process return to credit customer khata
- **Source Traceability:** `FLOW-001, FLOW-007, FORM-003, ACTION-008, GAP-008, GAP-009`

---
### `SOP-006`: Thermal & A4 Invoice Reprinting

- **Operational Domain:** `SALES_POS`
- **Purpose:** Reprint a customer receipt or commercial invoice for a previously completed sale
- **Applicable Roles:** Salesperson / Cashier, Branch Manager, Accountant
- **Prerequisites:** Sale transaction ID known or searched in history
- **Starting Screen:** Sales History (/sales/history)
- **Master Data Required:** Historical Sale Record, Sale Items Snapshots
- **Branch / Tenant Context:** Assigned branch_id and active business_id

#### Step-by-Step Operating Procedure
1. Navigate to Sales History (/sales/history).
2. Search by invoice number, date range, or customer name.
3. Click on target transaction to view details.
4. Click 'Print Thermal Receipt' (80mm/58mm) or 'Print A4 Invoice'.
5. Review modal preview displaying historical snapshot names, SKUs, and prices.
6. Confirm print command to send to local hardware printer.

#### System Rules & Business Effects
- **Validation Rules:** Sale must exist; uses persisted snapshots from sale_items (MIG-001)
- **System Calculations:** Displays frozen historical totals; does not recalculate against current catalog
- **Database Mutation Effect:** Read-only; records optional reprint audit log
- **Inventory Effect:** Zero inventory effect
- **Financial & Accounting Effect:** Zero financial effect
- **Print Output Result:** Exact duplicate receipt marked 'Reprint'
- **Offline Working Mode:** Reprint available for locally cached sales

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Physical invoice reprinted successfully
- **Common Failure Scenarios:** If printer disconnected, review printer settings (SOP-036) without altering sale
- **Correction & Reversal Path:** N/A (Read-only action)
- **Source Traceability:** `FLOW-001, TABLE-004, ACTION-011, GAP-001, GAP-023, GAP-028`

---
### `SOP-007`: Sale Cancellation & Stock Restoration

- **Operational Domain:** `SALES_POS`
- **Purpose:** Cancel an erroneously posted sale, restoring inventory to branch stock and reversing cash/financial movements
- **Applicable Roles:** Branch Manager, Business Owner
- **Prerequisites:** Sale status is 'completed' and cancellation policy permitted
- **Starting Screen:** Sales History (/sales/history)
- **Master Data Required:** Sale Record, Branch Inventory, Financial Accounts
- **Branch / Tenant Context:** Assigned branch_id and active business_id

#### Step-by-Step Operating Procedure
1. Locate transaction in Sales History.
2. Click 'Cancel Sale' action button.
3. Review impact-aware confirmation modal detailing stock restorations and cash reversals.
4. Enter mandatory cancellation reason.
5. Click 'Confirm Cancellation' to execute.

#### System Rules & Business Effects
- **Validation Rules:** Requires Manager authorization; cannot cancel already cancelled or fully refunded sales
- **System Calculations:** Restores exact quantities; posts negative or compensating account movements
- **Database Mutation Effect:** Updates sale status to 'cancelled', inserts compensating inventory_movements, creates reversing account_movements, and adjusts customer balance if on credit
- **Inventory Effect:** Increments branch product stock on hand back to pre-sale level
- **Financial & Accounting Effect:** Reverses tender amounts from cash/bank/khata ledgers
- **Print Output Result:** Cancellation voucher generated
- **Offline Working Mode:** Online only to ensure atomic multi-ledger reversal

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Sale cancelled; inventory and financial balances fully restored
- **Common Failure Scenarios:** If unauthorized, error modal prompts for manager credentials
- **Correction & Reversal Path:** Cannot re-activate cancelled sale; must re-enter new sale if necessary
- **Source Traceability:** `FLOW-002, TABLE-004, ACTION-012, GAP-015, GAP-025`

---
### `SOP-008`: Sale Return & Restock Condition Handling

- **Operational Domain:** `SALES_POS`
- **Purpose:** Process customer merchandise return, classifying items as sellable (restocked) or damaged/scrap (non-restocked), and issuing cash refund or khata credit
- **Applicable Roles:** Branch Manager, Salesperson / Cashier, Accountant
- **Prerequisites:** Original sale invoice verified, returned items physically present
- **Starting Screen:** Sale Returns (/sales/returns)
- **Master Data Required:** Original Sale Record, Sale Return Items Condition Flags
- **Branch / Tenant Context:** Assigned branch_id and active business_id

#### Step-by-Step Operating Procedure
1. Navigate to Sale Returns (/sales/returns) and enter original invoice number.
2. Select line items being returned and enter return quantities (<= sold quantity).
3. For each return line, select Condition: 'Sellable', 'Damaged', or 'Scrap'.
4. Set Restock toggle: 'Restock to Inventory' (enabled for sellable, disabled for damaged/scrap).
5. Select Refund Method: 'Cash Refund' or 'Customer Khata Credit'.
6. Click 'Process Return' to commit.

#### System Rules & Business Effects
- **Validation Rules:** Return qty <= purchased qty; damaged items cannot be restocked without supervisor override
- **System Calculations:** Refund Amount = Sum(Returned Qty * Unit Selling Price); Tax adjusted proportionately
- **Database Mutation Effect:** Inserts sale_returns, inserts sale_return_items with condition and is_restocked (MIG-007), creates inventory_movements only for restocked items, posts refund account_movements
- **Inventory Effect:** Sellable items increment branch stock; damaged/scrap items bypass inventory increment
- **Financial & Accounting Effect:** Decrements cash drawer (cash refund) or decrements customer receivable balance (khata credit)
- **Print Output Result:** Sale Return Credit Note / Refund Receipt printed
- **Offline Working Mode:** Online required for invoice validation and condition recording

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Return processed; stock conditionally restored; refund ledger posted
- **Common Failure Scenarios:** Attempting to return more than sold quantity is blocked by server validation
- **Correction & Reversal Path:** Return transactions are permanent; compensating sale required to adjust
- **Source Traceability:** `FLOW-003, FORM-005, ACTION-015, GAP-010, MIG-007`

---
### `SOP-009`: Create & Maintain Customer Profile

- **Operational Domain:** `CUSTOMER_KHATA`
- **Purpose:** Register a new customer profile with primary and secondary contact phones, credit limit, and address
- **Applicable Roles:** Salesperson / Cashier, Branch Manager, Accountant
- **Prerequisites:** Customer details obtained
- **Starting Screen:** Customer Directory (/customers)
- **Master Data Required:** Customer Master
- **Branch / Tenant Context:** Tenant wide (business_id)

#### Step-by-Step Operating Procedure
1. Navigate to Customers (/customers) and click '+ New Customer'.
2. Enter Full Name, Primary Phone number, and Secondary Phone number (MIG-010).
3. Enter optional Email, Address, and Tax / CNIC number.
4. Set Credit Limit (0.00 for cash-only customers).
5. Enter Opening Balance if migrating existing ledger balance.
6. Click 'Save Customer'.

#### System Rules & Business Effects
- **Validation Rules:** Customer name and primary phone required; phone format validated; credit limit >= 0
- **System Calculations:** Initial balance initialized to opening balance
- **Database Mutation Effect:** Inserts customers row including secondary_phone column; synchronizes with Dexie localDb
- **Inventory Effect:** Zero inventory effect
- **Financial & Accounting Effect:** Opening balance posts initial receivables ledger if > 0
- **Print Output Result:** Customer card / statement available
- **Offline Working Mode:** Can create quick customer in POS offline; synced upon reconnect

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Customer registered and selectable in POS and Khata ledgers
- **Common Failure Scenarios:** Duplicate phone warning alert if already registered
- **Correction & Reversal Path:** Edit customer or deactivate profile if created in error
- **Source Traceability:** `FLOW-006, FORM-013, ACTION-036, GAP-030, MIG-010`

---
### `SOP-010`: Customer Khata Payment Receipt & Settlement

- **Operational Domain:** `CUSTOMER_KHATA`
- **Purpose:** Receive debt settlement payment from customer against outstanding Khata balance, record cash/bank receipt, and issue payment voucher
- **Applicable Roles:** Accountant, Branch Manager, Salesperson / Cashier
- **Prerequisites:** Customer has outstanding credit balance
- **Starting Screen:** Customer Khata Ledger (/customers/:id/khata)
- **Master Data Required:** Customer Profile, Cash or Bank Financial Account
- **Branch / Tenant Context:** Assigned branch_id and active business_id

#### Step-by-Step Operating Procedure
1. Open Customer Khata Ledger for target customer.
2. Click 'Receive Payment'.
3. Enter Amount Paid.
4. Select Receiving Financial Account (Cash Drawer or Bank Account).
5. Enter Reference / Notes (e.g. cheque number, bank transfer ref).
6. Click 'Confirm Payment Receipt'.
7. Issue payment receipt voucher to customer.

#### System Rules & Business Effects
- **Validation Rules:** Amount > 0; mandatory financial account; cannot exceed balance without confirmation
- **System Calculations:** Customer Remaining Balance = Previous Balance - Amount Paid
- **Database Mutation Effect:** Inserts customer_payments, records credit entry on customer_ledgers, creates account_movements on receiving account, atomically decrements customers.balance
- **Inventory Effect:** Zero inventory effect
- **Financial & Accounting Effect:** Increments cash/bank account balance; decrements Accounts Receivable
- **Print Output Result:** Khata Payment Voucher / Receipt printed
- **Offline Working Mode:** Queued in Dexie offline_khata store

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Customer balance reduced and cash account incremented
- **Common Failure Scenarios:** If invalid account selected, validation error displays
- **Correction & Reversal Path:** Accountant can reverse receipt via Customer Khata -> Reverse Payment (SOP-011)
- **Source Traceability:** `FLOW-007, FORM-014, ACTION-039, GAP-008, GAP-009`

---
### `SOP-011`: Customer Payment Reversal & Khata Correction

- **Operational Domain:** `CUSTOMER_KHATA`
- **Purpose:** Reverse an erroneously recorded customer payment (e.g. bounced cheque, wrong customer entered) and restore receivable balance
- **Applicable Roles:** Accountant, Business Owner
- **Prerequisites:** Payment record exists and is not already reversed
- **Starting Screen:** Customer Khata Ledger (/customers/:id/khata)
- **Master Data Required:** Customer Payment Record, Financial Account
- **Branch / Tenant Context:** Assigned branch_id and active business_id

#### Step-by-Step Operating Procedure
1. Locate payment transaction in customer ledger.
2. Click 'Reverse Payment'.
3. Enter mandatory reversal reason (e.g. 'Cheque returned unpaid by bank').
4. Confirm impact-aware reversal modal.
5. Click 'Confirm Reversal'.

#### System Rules & Business Effects
- **Validation Rules:** Requires Accountant/Owner authorization; reason mandatory
- **System Calculations:** Restores customer balance by adding reversed amount back
- **Database Mutation Effect:** Marks payment as reversed, inserts debit adjustment on customer_ledgers, creates reversing account_movements on financial account, updates customers.balance
- **Inventory Effect:** Zero inventory effect
- **Financial & Accounting Effect:** Decrements bank/cash account; restores customer debt
- **Print Output Result:** Reversal voucher generated
- **Offline Working Mode:** Online only

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Customer balance restored to correct debt position
- **Common Failure Scenarios:** Reversal blocked if financial account closed or insufficient bank balance
- **Correction & Reversal Path:** Reversal is permanent; must record new payment if paid later
- **Source Traceability:** `FLOW-007, ACTION-041, GAP-008, GAP-015`

---
### `SOP-012`: Product Catalog Item Creation & Pricing Setup

- **Operational Domain:** `INVENTORY`
- **Purpose:** Create a new product item, configure SKU/barcode, assign category/brand, set unit cost, retail price, and wholesale price tier
- **Applicable Roles:** Inventory Manager, Business Owner
- **Prerequisites:** Product specifications, category and brand configured
- **Starting Screen:** Product Catalog (/inventory/products)
- **Master Data Required:** Categories, Brands, Product Master
- **Branch / Tenant Context:** Tenant wide (business_id)

#### Step-by-Step Operating Procedure
1. Navigate to Products (/inventory/products) and click '+ Add Product'.
2. Enter Product Title, SKU, Barcode (or click 'Generate Barcode').
3. Select Category, Brand, and Unit of Measure (pcs, kg, box).
4. Enter Initial Purchase Cost (unit_cost).
5. Enter Standard Retail Selling Price.
6. Enter Wholesale Selling Price (MIG-002) for tiered wholesale buyers.
7. Set Minimum Alert Quantity for low stock warnings.
8. Click 'Save Product'.

#### System Rules & Business Effects
- **Validation Rules:** Title and SKU unique; selling price >= 0; wholesale price >= 0
- **System Calculations:** Retail Margin = Retail Price - Unit Cost; Wholesale Margin = Wholesale Price - Unit Cost
- **Database Mutation Effect:** Inserts products row (including wholesale_price); initializes branch_inventory records across active branches
- **Inventory Effect:** Initializes stock balance records (stock=0 until purchase/adjustment)
- **Financial & Accounting Effect:** Zero financial effect until stock is received
- **Print Output Result:** Barcode labels can be queued immediately for printing (SOP-017)
- **Offline Working Mode:** Synchronized to Dexie products store on next POS sync

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Product active and searchable across all branch POS registers
- **Common Failure Scenarios:** Duplicate SKU/barcode triggers validation error
- **Correction & Reversal Path:** Edit product or deactivate if added by mistake
- **Source Traceability:** `FLOW-008, FORM-006, ACTION-017, GAP-002, MIG-002`

---
### `SOP-013`: Physical Stock Count Adjustment & Reconciliation

- **Operational Domain:** `INVENTORY`
- **Purpose:** Reconcile physical stock discrepancy (damage, theft, spoilage, or unrecorded variance) and adjust moving-average cost valuation
- **Applicable Roles:** Inventory Manager, Business Owner
- **Prerequisites:** Physical stock count completed for branch
- **Starting Screen:** Stock Adjustments (/inventory/adjustments)
- **Master Data Required:** Product Master, Branch Inventory, Stock Adjustment Reasons
- **Branch / Tenant Context:** Assigned branch_id and active business_id

#### Step-by-Step Operating Procedure
1. Navigate to Stock Adjustments (/inventory/adjustments) and click '+ New Adjustment'.
2. Select Product and target Branch.
3. View current recorded system stock.
4. Enter Actual Physical Count (or +/- adjustment quantity).
5. Select Adjustment Reason: 'Damage / Breakage', 'Spoilage', 'Physical Count Variance', 'Theft / Missing', or 'Found Stock'.
6. Enter optional unit cost if adjustment affects valuation.
7. Click 'Submit Adjustment'.

#### System Rules & Business Effects
- **Validation Rules:** Negative adjustments cannot reduce stock below zero; reason mandatory
- **System Calculations:** Quantity Delta = Physical Count - Current Stock; Value Impact = Delta * Unit Cost; Updates moving-average valuation
- **Database Mutation Effect:** Inserts stock_adjustments, inserts inventory_movements (movement_type='adjustment'), updates branch_inventory stock_quantity and moving_average_cost
- **Inventory Effect:** Branch stock immediately updated to physical count
- **Financial & Accounting Effect:** Records inventory write-off / gain in financial reports
- **Print Output Result:** Stock Adjustment Voucher generated
- **Offline Working Mode:** Online only to prevent race conditions across multiple stock counters

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Branch inventory reconciled to physical reality
- **Common Failure Scenarios:** If adjustment would cause negative stock, operation rejected
- **Correction & Reversal Path:** Submit compensating adjustment with audit reference
- **Source Traceability:** `FLOW-010, FORM-010, ACTION-029, GAP-004, GAP-006`

---
### `SOP-014`: Inter-Branch Stock Transfer (Dispatch & Receipt)

- **Operational Domain:** `INVENTORY`
- **Purpose:** Transfer inventory between two branches with explicit transit status tracking to eliminate phantom inventory
- **Applicable Roles:** Inventory Manager, Branch Manager
- **Prerequisites:** Source branch has sufficient stock on hand; destination branch active
- **Starting Screen:** Stock Transfers (/inventory/transfers)
- **Master Data Required:** Products Catalog, Source & Destination Branches
- **Branch / Tenant Context:** Multi-branch within tenant (business_id)

#### Step-by-Step Operating Procedure
1. Source Branch Operator: Click '+ New Transfer'.
2. Select Source Branch and Destination Branch.
3. Add items and transfer quantities.
4. Click 'Dispatch Shipment' -> Transfer status becomes 'in_transit' (MIG-006).
5. Physical items shipped to destination branch.
6. Destination Branch Operator: Open Stock Transfers, locate transfer, inspect goods.
7. Click 'Receive Transfer' -> Transfer status becomes 'received'.

#### System Rules & Business Effects
- **Validation Rules:** Source stock >= transfer qty; source branch != destination branch
- **System Calculations:** Source branch decremented on dispatch; in-transit inventory tracked; destination branch incremented on receipt
- **Database Mutation Effect:** Inserts stock_transfers, updates transfer_status on inventory_movements (MIG-006), adjusts branch_inventory for source and destination
- **Inventory Effect:** Eliminates phantom stock by strictly maintaining transit state until physical confirmation
- **Financial & Accounting Effect:** Transfers inventory asset valuation between branch ledgers
- **Print Output Result:** Transfer Delivery Challan / Gate Pass printed
- **Offline Working Mode:** Online required for real-time inter-branch synchronization

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Goods safely moved and stock balances updated at both branches
- **Common Failure Scenarios:** If damaged in transit, destination receives partial and records damage adjustment
- **Correction & Reversal Path:** Cancel transfer while 'in_transit' returns stock back to source branch
- **Source Traceability:** `FLOW-009, FORM-011, ACTION-031, ACTION-032, GAP-007, MIG-006`

---
### `SOP-015`: Create Purchase Order & Goods Receipt (GRN) with Landed Cost

- **Operational Domain:** `PROCUREMENT_SUPPLIER`
- **Purpose:** Record vendor purchase shipment, ingest freight and handling charges, prorate landed cost across items, update moving-average cost, and record supplier AP
- **Applicable Roles:** Inventory Manager, Accountant, Business Owner
- **Prerequisites:** Supplier master profile created; vendor invoice/packing slip received
- **Starting Screen:** Purchases (/purchases)
- **Master Data Required:** Supplier Profile, Product Catalog, Receiving Branch
- **Branch / Tenant Context:** Assigned branch_id and active business_id

#### Step-by-Step Operating Procedure
1. Navigate to Purchases (/purchases) and click '+ New Purchase'.
2. Select Supplier, Receiving Branch, and enter Vendor Invoice Number.
3. Add line items, quantities received, and base unit purchase price.
4. Enter Freight Amount and Other Handling Charges (MIG-005).
5. Select Payment Terms: 'Full Payment', 'Partial Payment', or 'On Credit'.
6. If paid, select funding Financial Account (Bank or Cash).
7. Click 'Receive Goods & Save Purchase'.

#### System Rules & Business Effects
- **Validation Rules:** Quantities > 0; unit costs >= 0; valid financial account if payment made
- **System Calculations:** Landed Unit Cost = Base Cost + (Freight + Charges) * (Line Value / Total Value); Moving Average Cost = ((Old Stock * Old Cost) + (New Stock * Landed Cost)) / (Old Stock + New Stock); Total Payable = Item Total + Freight + Charges - Paid
- **Database Mutation Effect:** Inserts purchases (with freight_amount, other_charges), purchase_items, inventory_movements, updates branch_inventory stock and cost, inserts supplier_ledgers debit/credit, and records account_movements for payment
- **Inventory Effect:** Branch stock incremented; product moving-average unit cost updated to true landed cost
- **Financial & Accounting Effect:** Increments Accounts Payable to supplier; decrements funding account if paid
- **Print Output Result:** Goods Receipt Note (GRN) / Purchase Invoice printed
- **Offline Working Mode:** Online only for procurement accounting

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Stock received at true landed cost and vendor liability recorded
- **Common Failure Scenarios:** Missing freight or negative costs rejected by validator
- **Correction & Reversal Path:** Purchase Cancellation (SOP-016) or Supplier Return (SOP-017)
- **Source Traceability:** `FLOW-004, FORM-015, FORM-016, ACTION-042, GAP-004, GAP-005, MIG-005`

---
### `SOP-016`: Purchase Cancellation Guarded Workflow

- **Operational Domain:** `PROCUREMENT_SUPPLIER`
- **Purpose:** Cancel an erroneous purchase order, protected by strict stock availability validation to prevent negative stock deficits
- **Applicable Roles:** Business Owner, Inventory Manager
- **Prerequisites:** Purchase exists; received items are still physically in stock and have not been sold or transferred
- **Starting Screen:** Purchases (/purchases)
- **Master Data Required:** Purchase Record, Branch Inventory Balance
- **Branch / Tenant Context:** Assigned branch_id and active business_id

#### Step-by-Step Operating Procedure
1. Locate purchase order in Purchases list.
2. Click 'Cancel Purchase'.
3. System checks current stock on hand for all constituent line items.
4. If stock is sufficient, review cancellation confirmation modal detailing stock removal and AP reversal.
5. Enter mandatory cancellation reason.
6. Click 'Confirm Cancellation'.

#### System Rules & Business Effects
- **Validation Rules:** Strictly blocked if current branch stock < purchase quantity (prevents negative stock overdraft)
- **System Calculations:** Decrements stock by purchase quantity; reverses supplier AP and cash/bank movements
- **Database Mutation Effect:** Marks purchase 'cancelled', inserts reversing inventory_movements, reverses supplier_ledgers entries, reverses financial account_movements
- **Inventory Effect:** Decrements branch stock on hand back to pre-purchase level
- **Financial & Accounting Effect:** Reverses vendor liability and restores funding account balance
- **Print Output Result:** Purchase Cancellation Voucher printed
- **Offline Working Mode:** Online only

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Purchase cancelled safely without causing negative inventory
- **Common Failure Scenarios:** If items were already sold, system rejects cancellation and directs operator to Supplier Return (SOP-017)
- **Correction & Reversal Path:** Cancelled purchase cannot be un-cancelled; must create new purchase if needed
- **Source Traceability:** `FLOW-005, ACTION-044, GAP-006, GAP-015, GAP-025`

---
### `SOP-017`: Supplier Return / Debit Note Processing

- **Operational Domain:** `PROCUREMENT_SUPPLIER`
- **Purpose:** Return defective or excess merchandise to vendor, decrement branch inventory, and generate a debit note reducing Accounts Payable
- **Applicable Roles:** Inventory Manager, Accountant, Business Owner
- **Prerequisites:** Items in stock, supplier agreement confirmed
- **Starting Screen:** Supplier AP Ledger (/suppliers/:id/ledger)
- **Master Data Required:** Supplier Profile, Product Catalog, Branch Stock
- **Branch / Tenant Context:** Assigned branch_id and active business_id

#### Step-by-Step Operating Procedure
1. Open Supplier Ledger for target vendor.
2. Click 'Create Supplier Return / Debit Note'.
3. Select Product(s) to return and enter return quantities.
4. Verify unit return cost (defaults to purchase cost).
5. Enter Return Reason (e.g. 'Defective batch', 'Wrong specification').
6. Click 'Process Supplier Return'.

#### System Rules & Business Effects
- **Validation Rules:** Return quantity <= branch stock on hand; unit return cost > 0
- **System Calculations:** Debit Note Total = Sum(Qty * Unit Cost); Supplier Balance = Previous Balance - Debit Note Total
- **Database Mutation Effect:** Inserts supplier_returns and supplier_return_items (MIG-008), creates decrementing inventory_movements, inserts debit entry on supplier_ledgers, updates suppliers.balance
- **Inventory Effect:** Decrements branch physical inventory by returned quantities
- **Financial & Accounting Effect:** Decrements Accounts Payable liability owed to supplier
- **Print Output Result:** Supplier Debit Note / Return Delivery Note printed
- **Offline Working Mode:** Online only

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Debit note created, stock decremented, and vendor payable reduced
- **Common Failure Scenarios:** If stock insufficient at branch, return is rejected
- **Correction & Reversal Path:** Compensating purchase entry required if return is rejected by vendor
- **Source Traceability:** `FLOW-005, FORM-019, ACTION-051, GAP-011, MIG-008`

---
### `SOP-018`: Supplier Payment Processing & Reversal

- **Operational Domain:** `PROCUREMENT_SUPPLIER`
- **Purpose:** Process disbursement payment to vendor reducing Accounts Payable, or reverse an erroneous payment restoring liability
- **Applicable Roles:** Accountant, Business Owner
- **Prerequisites:** Supplier has outstanding payable balance; funding account has sufficient funds
- **Starting Screen:** Supplier AP Ledger (/suppliers/:id/ledger)
- **Master Data Required:** Supplier Record, Bank or Cash Financial Account
- **Branch / Tenant Context:** Assigned branch_id and active business_id

#### Step-by-Step Operating Procedure
1. PAYMENT: Open Supplier Ledger -> Click 'Make Payment'.
2. Enter Amount, select Funding Account (Bank/Cash), enter Cheque/Ref Number.
3. Click 'Confirm Payment' -> Supplier AP decremented, bank account decremented.
4. REVERSAL: If payment bounced or recorded in error, locate payment in ledger.
5. Click 'Reverse Payment' (GAP-012) -> Enter mandatory reversal reason.
6. Click 'Confirm Reversal' -> Restores supplier payable and bank balance.

#### System Rules & Business Effects
- **Validation Rules:** Payment amount <= outstanding payable (or advance allowed); reversal requires valid reason
- **System Calculations:** Payment: New AP = Old AP - Amount; Reversal: New AP = Old AP + Amount
- **Database Mutation Effect:** Payment: Inserts supplier_payments, debit supplier_ledgers, account_movements. Reversal: Updates payment status, credit supplier_ledgers, reversing account_movements
- **Inventory Effect:** Zero inventory effect
- **Financial & Accounting Effect:** Payment decrements bank/cash; Reversal increments bank/cash and restores supplier liability
- **Print Output Result:** Supplier Payment Voucher / Reversal Slip printed
- **Offline Working Mode:** Online only

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Vendor liability settled or accurately restored upon reversal
- **Common Failure Scenarios:** Insufficient funding balance displays error modal
- **Correction & Reversal Path:** Use Reverse Payment endpoint implemented in GAP-012
- **Source Traceability:** `FLOW-005, FORM-018, ACTION-049, ACTION-050, GAP-012`

---
### `SOP-019`: Operating Expense Posting & Mandatory Account Allocation

- **Operational Domain:** `EXPENSES`
- **Purpose:** Record an operational expense, assign departmental category, enforce mandatory funding account selection, and verify budget variance
- **Applicable Roles:** Accountant, Branch Manager, Business Owner
- **Prerequisites:** Expense category active; funding financial account active
- **Starting Screen:** Expenses (/expenses)
- **Master Data Required:** Expense Categories with Monthly Budget, Financial Accounts
- **Branch / Tenant Context:** Assigned branch_id and active business_id

#### Step-by-Step Operating Procedure
1. Navigate to Expenses (/expenses) and click '+ Add Expense'.
2. Select Expense Category (e.g. 'Rent & Utilities', 'Store Supplies', 'Salaries').
3. Enter Expense Title / Description and Amount.
4. MANDATORY: Select Funding Financial Account (Cash Drawer or Bank Account).
5. Enter optional Receipt / Tax Invoice Reference.
6. System checks monthly budget ceiling for category (MIG-011).
7. Click 'Save Expense'.

#### System Rules & Business Effects
- **Validation Rules:** Amount > 0; financial_account_id is strictly mandatory (MIG-004 / GAP-013)
- **System Calculations:** Account Balance = Old Balance - Expense Amount; Category Monthly Spend = Sum(Monthly Expenses)
- **Database Mutation Effect:** Inserts expenses row with validated financial_account_id, creates account_movements (movement_category='expense')
- **Inventory Effect:** Zero inventory effect
- **Financial & Accounting Effect:** Decrements funding cash/bank account; records expense in Profit & Loss
- **Print Output Result:** Expense Payment Voucher printed
- **Offline Working Mode:** Queued in Dexie offline_expenses store with validated account ID

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Expense posted and cash ledger decremented without leakage
- **Common Failure Scenarios:** Attempting to submit without financial account is blocked by client and server validators
- **Correction & Reversal Path:** Void Expense (SOP-020) reverses movement
- **Source Traceability:** `FLOW-011, FORM-020, ACTION-052, GAP-013, GAP-031, MIG-004, MIG-011`

---
### `SOP-020`: Expense Voiding & Reconciliation

- **Operational Domain:** `EXPENSES`
- **Purpose:** Void an erroneously posted expense and restore funding financial account balance
- **Applicable Roles:** Accountant, Business Owner
- **Prerequisites:** Expense record active and un-voided
- **Starting Screen:** Expenses (/expenses)
- **Master Data Required:** Expense Record, Financial Account
- **Branch / Tenant Context:** Assigned branch_id and active business_id

#### Step-by-Step Operating Procedure
1. Locate expense in Expenses register.
2. Click 'Void Expense'.
3. Review confirmation modal displaying refund amount to funding account.
4. Enter void reason.
5. Click 'Confirm Void'.

#### System Rules & Business Effects
- **Validation Rules:** Requires Accountant/Owner authorization; cannot void already voided expense
- **System Calculations:** Funding Account Restored Balance = Current Balance + Voided Amount
- **Database Mutation Effect:** Updates expense status to 'voided', creates compensating account_movements restoring financial account balance
- **Inventory Effect:** Zero inventory effect
- **Financial & Accounting Effect:** Restores cash/bank balance; removes expense from active P&L
- **Print Output Result:** Expense Void Slip printed
- **Offline Working Mode:** Online only

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Expense voided and funding account fully restored
- **Common Failure Scenarios:** Unauthorized attempts rejected
- **Correction & Reversal Path:** Void is permanent; must enter new expense if needed
- **Source Traceability:** `FLOW-011, ACTION-054, GAP-014, GAP-025`

---
### `SOP-021`: Financial Account Creation & Opening Balance Setup

- **Operational Domain:** `FINANCIAL_ACCOUNTS`
- **Purpose:** Create a physical cash register, bank account, or digital wallet and establish opening financial balance
- **Applicable Roles:** Accountant, Business Owner
- **Prerequisites:** Account details and bank account numbers confirmed
- **Starting Screen:** Accounts (/accounts)
- **Master Data Required:** Financial Accounts Master
- **Branch / Tenant Context:** Assigned branch_id (or enterprise wide)

#### Step-by-Step Operating Procedure
1. Navigate to Accounts (/accounts) and click '+ Add Account'.
2. Enter Account Name (e.g. 'Main Counter Cash Drawer', 'Meezan Bank Operating').
3. Select Account Type: 'cash', 'bank', or 'digital_wallet'.
4. Enter Account Number / IBAN if bank account.
5. Enter Opening Balance.
6. Assign to specific Branch (or leave global for enterprise bank).
7. Click 'Save Account'.

#### System Rules & Business Effects
- **Validation Rules:** Account name unique within business; opening balance >= 0
- **System Calculations:** Initial balance initialized to opening balance
- **Database Mutation Effect:** Inserts financial_accounts row, creates initial account_movements record for opening balance
- **Inventory Effect:** Zero inventory effect
- **Financial & Accounting Effect:** Establishes starting cash/bank liquidity balance
- **Print Output Result:** Account registration slip
- **Offline Working Mode:** Read-only balance display offline

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Financial account ready to receive sales, expenses, and transfers
- **Common Failure Scenarios:** Duplicate account name rejected
- **Correction & Reversal Path:** Edit account details or archive account if zero balance
- **Source Traceability:** `FLOW-012, FORM-024, ACTION-063, GAP-014`

---
### `SOP-022`: Inter-Account Fund Transfer (Cash to Bank / Bank to Cash)

- **Operational Domain:** `FINANCIAL_ACCOUNTS`
- **Purpose:** Transfer liquid funds between internal accounts (e.g. daily counter cash deposit to bank, or bank withdrawal for cash float)
- **Applicable Roles:** Accountant, Business Owner
- **Prerequisites:** Source account has sufficient balance; destination account active
- **Starting Screen:** Accounts (/accounts)
- **Master Data Required:** Source Account, Destination Account
- **Branch / Tenant Context:** Tenant wide (business_id)

#### Step-by-Step Operating Procedure
1. Navigate to Accounts (/accounts) and click 'Transfer Funds'.
2. Select Source Account (e.g. 'Main Counter Cash').
3. Select Destination Account (e.g. 'Operating Bank Account').
4. Enter Transfer Amount.
5. Enter Transfer Reference / Bank Deposit Slip Number.
6. Click 'Execute Transfer'.

#### System Rules & Business Effects
- **Validation Rules:** Amount > 0; source balance >= transfer amount; source != destination
- **System Calculations:** Source New Balance = Source Old Balance - Amount; Destination New Balance = Destination Old Balance + Amount
- **Database Mutation Effect:** Inserts account_transfers, creates debit account_movements on source, creates credit account_movements on destination with linked compound reference
- **Inventory Effect:** Zero inventory effect
- **Financial & Accounting Effect:** Net business cash stays constant; balances shift between accounts
- **Print Output Result:** Inter-Account Transfer Voucher printed
- **Offline Working Mode:** Online only for atomic double-entry transfer execution

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Funds successfully transferred between accounts
- **Common Failure Scenarios:** Insufficient source balance triggers error and blocks transfer
- **Correction & Reversal Path:** Execute reversing transfer in opposite direction with audit notes
- **Source Traceability:** `FLOW-013, FORM-025, ACTION-064, GAP-014`

---
### `SOP-023`: Capital Injection & Owner Drawing Management

- **Operational Domain:** `FINANCIAL_ACCOUNTS`
- **Purpose:** Record external equity investment into business or owner personal drawings out of company funds
- **Applicable Roles:** Business Owner, Accountant
- **Prerequisites:** Owner authorization confirmed
- **Starting Screen:** Accounts (/accounts)
- **Master Data Required:** Financial Accounts, Owner Equity Accounts
- **Branch / Tenant Context:** Tenant wide (business_id)

#### Step-by-Step Operating Procedure
1. Open Accounts (/accounts) and click 'Capital Transaction'.
2. Select Transaction Type: 'Capital Injection (Deposit)' or 'Owner Drawing (Withdrawal)'.
3. Select target Financial Account.
4. Enter Amount and descriptive notes.
5. Click 'Confirm Capital Movement'.

#### System Rules & Business Effects
- **Validation Rules:** Amount > 0; owner drawing requires sufficient account balance
- **System Calculations:** Injection: Account Balance + Amount; Drawing: Account Balance - Amount
- **Database Mutation Effect:** Inserts account_movements (movement_category='capital_injection' or 'capital_drawing')
- **Inventory Effect:** Zero inventory effect
- **Financial & Accounting Effect:** Alters business equity and liquidity without affecting operating sales/expense P&L
- **Print Output Result:** Capital Transaction Voucher printed
- **Offline Working Mode:** Online only

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Equity transaction recorded accurately in cash flow statement
- **Common Failure Scenarios:** Drawing exceeding account balance rejected
- **Correction & Reversal Path:** Compensating capital adjustment entry
- **Source Traceability:** `FLOW-014, FORM-026, ACTION-065, ACTION-066, GAP-014`

---
### `SOP-024`: Staff User Creation, Role Assignment & Wage Persistence

- **Operational Domain:** `USERS_STAFF`
- **Purpose:** Create employee user credentials, assign branch and RBAC role, and persist wage/salary agreement details
- **Applicable Roles:** Business Owner
- **Prerequisites:** Employee information, role, and salary agreement established
- **Starting Screen:** User Staff Governance (/users)
- **Master Data Required:** User Master, Roles (ROLE-001..005), Branches
- **Branch / Tenant Context:** Tenant wide (business_id)

#### Step-by-Step Operating Procedure
1. Navigate to Users (/users) and click '+ New User'.
2. Enter Full Name, Email / Login Username, and secure Password.
3. Select System Role: 'Business Owner', 'Branch Manager', 'Salesperson / Cashier', 'Accountant', or 'Inventory Manager'.
4. Assign primary Store Branch.
5. Enter Base Salary (MIG-009).
6. Select Payment Cycle: 'monthly', 'weekly', or 'bi-weekly'.
7. Set Next Payment Date.
8. Click 'Save User'.

#### System Rules & Business Effects
- **Validation Rules:** Email/Username unique; password >= 8 chars; salary >= 0 (MIG-009 / GAP-029)
- **System Calculations:** Initializes user account and payroll metadata fields
- **Database Mutation Effect:** Inserts users row with base_salary, payment_cycle, next_payment_date (MIG-009) and assigns role via model_has_roles
- **Inventory Effect:** Zero inventory effect
- **Financial & Accounting Effect:** Zero financial effect until payroll disbursement
- **Print Output Result:** Staff employment record sheet
- **Offline Working Mode:** Online only for security governance

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** User active and capable of logging into assigned branch POS
- **Common Failure Scenarios:** Duplicate login credentials rejected by validator
- **Correction & Reversal Path:** Deactivate user account (SOP-025)
- **Source Traceability:** `FLOW-015, FORM-022, ACTION-058, GAP-029, MIG-009`

---
### `SOP-025`: User Deactivation & Access Revocation

- **Operational Domain:** `USERS_STAFF`
- **Purpose:** Revoke staff system access immediately upon termination or leave without corrupting historical transaction audit trails
- **Applicable Roles:** Business Owner
- **Prerequisites:** User is not currently operating an unclosed cash drawer session
- **Starting Screen:** User Staff Governance (/users)
- **Master Data Required:** User Profile
- **Branch / Tenant Context:** Tenant wide (business_id)

#### Step-by-Step Operating Procedure
1. Locate staff member in Users list.
2. Click 'Deactivate User'.
3. Review confirmation modal verifying user has no open cash drawer shift.
4. Click 'Confirm Deactivation'.

#### System Rules & Business Effects
- **Validation Rules:** Cannot deactivate own current login account; requires Owner permission
- **System Calculations:** Sets is_active = false
- **Database Mutation Effect:** Updates users.is_active = false; revokes active Sanctum API session tokens
- **Inventory Effect:** Zero inventory effect
- **Financial & Accounting Effect:** Zero financial effect
- **Print Output Result:** Revocation audit log
- **Offline Working Mode:** Token revoked on next online handshake

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** User blocked from logging into POS or backend
- **Common Failure Scenarios:** Attempt to deactivate self is blocked
- **Correction & Reversal Path:** Click 'Activate User' to restore access
- **Source Traceability:** `FLOW-015, ACTION-060, GAP-015`

---
### `SOP-026`: Store Branch Configuration & Multi-Outlet Setup

- **Operational Domain:** `SETTINGS_BRANCH`
- **Purpose:** Create a physical branch outlet, assign default cash register, contact details, and receipt headers
- **Applicable Roles:** Business Owner
- **Prerequisites:** Branch physical address confirmed
- **Starting Screen:** Branches (/branches)
- **Master Data Required:** Branch Master, Tenant Profile
- **Branch / Tenant Context:** Tenant wide (business_id)

#### Step-by-Step Operating Procedure
1. Navigate to Branches (/branches) and click '+ Add Branch'.
2. Enter Branch Name, Physical Address, City, and Phone.
3. Select Default Currency and Receipt Header/Footer.
4. Check 'Enable POS Register' to create default cash register.
5. Click 'Save Branch'.

#### System Rules & Business Effects
- **Validation Rules:** Branch name unique within business
- **System Calculations:** Initializes branch inventory records and default accounts
- **Database Mutation Effect:** Inserts branches row, creates default financial_accounts cash register for branch
- **Inventory Effect:** Enables branch stock tracking for all catalog products
- **Financial & Accounting Effect:** Creates branch cash register ledger
- **Print Output Result:** Branch profile slip
- **Offline Working Mode:** Cached in application state

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** New branch available in POS header dropdown and inventory transfers
- **Common Failure Scenarios:** Duplicate branch name rejected
- **Correction & Reversal Path:** Edit branch details or deactivate if outlet closed
- **Source Traceability:** `FLOW-016, FORM-027, ACTION-067`

---
### `SOP-027`: Preparing POS Device for Offline Operation

- **Operational Domain:** `OFFLINE_SYNC`
- **Purpose:** Ensure POS tablet/desktop has a complete local cached copy of product catalog, customers, categories, and settings before losing connectivity
- **Applicable Roles:** Salesperson / Cashier, Branch Manager
- **Prerequisites:** Device connected to local network / internet initially
- **Starting Screen:** POS Counter (/pos) or Sync Manager (/sync-manager)
- **Master Data Required:** Catalog Data, Customers, Categories, Brands
- **Branch / Tenant Context:** Assigned branch_id and active business_id

#### Step-by-Step Operating Procedure
1. While online, open POS Counter (/pos).
2. Click 'Sync / Refresh Catalog' button in top toolbar.
3. System calls `/api/v1/sync/catalog` and populates Dexie local stores (products, categories, brands, customers).
4. Verify 'Offline Ready: YES (XXX Products Cached)' indicator appears in header.
5. Device can now operate completely disconnected from the internet.

#### System Rules & Business Effects
- **Validation Rules:** Must be authenticated online before going offline
- **System Calculations:** Downloads latest products, retail/wholesale prices, and customer balances into Dexie IndexedDB
- **Database Mutation Effect:** Client-side bulkPut into localDb.products, localDb.categories, localDb.brands, localDb.customers
- **Inventory Effect:** Initializes local offline working copy of stock counts
- **Financial & Accounting Effect:** Initializes local customer balance cache
- **Print Output Result:** Sync summary toast notification
- **Offline Working Mode:** Device is fully primed for offline sales, expenses, and khata entries

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** 100% offline operational readiness confirmed
- **Common Failure Scenarios:** If sync fails due to weak Wi-Fi, retry before disconnecting
- **Correction & Reversal Path:** N/A
- **Source Traceability:** `FLOW-018, ACTION-078, GAP-021, GAP-030`

---
### `SOP-028`: Performing Disconnected Offline POS Sales

- **Operational Domain:** `OFFLINE_SYNC`
- **Purpose:** Process sales transactions when internet or server is completely down, printing local thermal receipts and queueing for background sync
- **Applicable Roles:** Salesperson / Cashier
- **Prerequisites:** Catalog cached in Dexie localDb (SOP-027)
- **Starting Screen:** POS Counter (/pos)
- **Master Data Required:** Dexie Local Catalog, Local Cash Drawer
- **Branch / Tenant Context:** Assigned branch_id stored in localStorage

#### Step-by-Step Operating Procedure
1. Observe POS header displays yellow 'OFFLINE MODE' indicator.
2. Scan barcodes or search cached products.
3. Add items to cart and select customer or walk-in.
4. Click 'Checkout' -> Select Cash or Khata payment.
5. Click 'Complete Sale' -> System generates unique client idempotency key (UUID) and temp ID.
6. Transaction is committed instantly to Dexie `offline_sales` store.
7. Local thermal printer issues receipt marked 'Offline Transaction #TEMP-XXXX'.

#### System Rules & Business Effects
- **Validation Rules:** Offline stock checks use local cached counts; client idempotency key generated
- **System Calculations:** Client calculates subtotal, tax, discounts, and change
- **Database Mutation Effect:** Stored in IndexedDB offline_sales queue; zero immediate server DB mutation
- **Inventory Effect:** Local cached stock count decremented immediately in browser memory
- **Financial & Accounting Effect:** Local cash shift total incremented
- **Print Output Result:** Thermal receipt printed via local USB/Serial ESC-POS or Web Bluetooth
- **Offline Working Mode:** Queued safely in persistent browser IndexedDB storage

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Customer checkout completed seamlessly with zero downtime
- **Common Failure Scenarios:** If browser storage full, alert prompts operator to avoid clearing browser history
- **Correction & Reversal Path:** Offline sales can be voided from local queue before sync if entered erroneously
- **Source Traceability:** `FLOW-019, FORM-002, ACTION-008, GAP-019, GAP-021, GAP-023`

---
### `SOP-029`: Offline Queue Sync Replay & Stock Conflict Resolution

- **Operational Domain:** `OFFLINE_SYNC`
- **Purpose:** Reconnect POS device to network, replay queued offline sales/expenses/khata entries to server, remap temp IDs, and resolve stock conflicts
- **Applicable Roles:** Salesperson / Cashier, Branch Manager
- **Prerequisites:** Network connection restored; pending items in offline queue
- **Starting Screen:** Sync Manager (/sync-manager)
- **Master Data Required:** Offline Queues in Dexie, Server API Endpoints
- **Branch / Tenant Context:** Assigned branch_id and active business_id

#### Step-by-Step Operating Procedure
1. When network reconnects, system detects online state and displays 'Sync Pending (X items)'.
2. Background sync triggers automatically (or click 'Sync Now' in Sync Manager).
3. System sends bulk sync payload to `/api/v1/sync/bulk` in deterministic dependency order:
   a. Step 1: New offline customers synced and temp IDs remapped to server IDs.
   b. Step 2: Offline sales replayed with server recalculation and stock validation.
   c. Step 3: Offline khata payments and expenses replayed.
4. If all items succeed, queue is cleared and permanent server invoice numbers returned.
5. If a stock conflict occurs (e.g. concurrent multi-device overdraft):
   - Conflicting sale is flagged as 'STOCK_CONFLICT' with detailed shortfall explanation.
   - Manager inspects conflict in Sync Manager, reviews physical stock, and either approves compensating stock adjustment or cancels sale.

#### System Rules & Business Effects
- **Validation Rules:** Server validates idempotency keys (GAP-019) to prevent double-posting; server validates actual stock availability
- **System Calculations:** Server recalculates all totals authoritatively and remaps temp customer IDs across all dependent sale lines
- **Database Mutation Effect:** Server atomically inserts permanent sales, sale_items (with snapshots), sale_payments, and account_movements; returns mapped IDs
- **Inventory Effect:** Permanent server branch inventory decremented
- **Financial & Accounting Effect:** Server financial accounts and customer balances updated
- **Print Output Result:** Sync completion notification; optional reprint of permanent server invoice
- **Offline Working Mode:** Empties Dexie offline stores upon verified server HTTP 200 response

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** 100% offline transactions reconciled into authoritative server ledger
- **Common Failure Scenarios:** Duplicate submissions recognized via idempotency key and returned without re-inserting; stock conflicts quarantined for manager review
- **Correction & Reversal Path:** Manager reviews rejected items in Sync Manager and takes corrective action
- **Source Traceability:** `FLOW-020, TABLE-029, ACTION-078, GAP-019, GAP-021, GAP-022`

---
### `SOP-030`: Thermal Receipt & Hardware Printer Setup

- **Operational Domain:** `PRINTING_HARDWARE`
- **Purpose:** Configure 80mm or 58mm thermal receipt printer via USB COM port, Network LAN IP, or Web Bluetooth
- **Applicable Roles:** Salesperson / Cashier, Branch Manager
- **Prerequisites:** Thermal printer powered on and connected to device / LAN
- **Starting Screen:** Printer Settings (/settings/printer)
- **Master Data Required:** Printer Settings Store
- **Branch / Tenant Context:** Local Device Context

#### Step-by-Step Operating Procedure
1. Navigate to Settings -> Printer Settings (/settings/printer).
2. Select Connection Type: 'USB / Serial COM', 'Network ESC-POS LAN (IP)', or 'Web Bluetooth'.
3. If Network: enter Printer IP (e.g. `192.168.1.200`) and Port (default `9100`).
4. Select Paper Width: '80mm (Standard POS)' or '58mm (Compact Mobile)'.
5. Toggle 'Auto-Print on Sale Completion' (enabled by default).
6. Click 'Test Print' to verify paper feed and character printing.
7. Click 'Save Printer Preferences'.

#### System Rules & Business Effects
- **Validation Rules:** Valid IP address and port (1024-65535); valid paper width selection
- **System Calculations:** Formats ESC-POS byte stream matching selected paper column width (48 cols for 80mm, 32 cols for 58mm)
- **Database Mutation Effect:** Persists configuration in localStorage and Dexie device settings
- **Inventory Effect:** Zero inventory effect
- **Financial & Accounting Effect:** Zero financial effect
- **Print Output Result:** Test receipt with alignment grid printed
- **Offline Working Mode:** Saved locally in browser; functions without internet

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Thermal printer configured and responsive
- **Common Failure Scenarios:** If test print fails: verify cable connection, paper roll orientation, and IP ping
- **Correction & Reversal Path:** Reset to default browser print dialog if hardware channel fails
- **Source Traceability:** `FLOW-021, FORM-029, ACTION-072, ACTION-073, GAP-023`

---
### `SOP-031`: Printer Failure Post-Transaction Recovery

- **Operational Domain:** `PRINTING_HARDWARE`
- **Purpose:** Handle physical printer failure (paper out, jam, power loss) occurring after transaction has successfully committed to database without re-submitting sale
- **Applicable Roles:** Salesperson / Cashier
- **Prerequisites:** Transaction committed successfully but printer failed to output paper
- **Starting Screen:** POS Counter (/pos) or Sales History (/sales/history)
- **Master Data Required:** Committed Sale Record
- **Branch / Tenant Context:** Assigned branch_id and active business_id

#### Step-by-Step Operating Procedure
1. CRITICAL OPERATING PRINCIPLE: Transaction commit is independent of printer execution (GAP-023). Do NOT click 'Checkout' or re-enter the cart.
2. Inspect printer hardware: replenish paper roll, clear paper jam, or reconnect cable.
3. Once printer is online and ready:
4. On POS Counter: click the green 'Reprint Last Sale' button on the sale confirmation overlay.
5. Or navigate to Sales History (/sales/history), select the top transaction, and click 'Reprint'.
6. Receipt prints immediately using immutable historical snapshots (GAP-001).

#### System Rules & Business Effects
- **Validation Rules:** Never re-enter items as a new sale to fix a print issue (prevents double-billing and duplicate stock decrement)
- **System Calculations:** Zero recalculation; sends existing frozen invoice data to printer pipeline
- **Database Mutation Effect:** Zero database mutation; records optional reprint audit log
- **Inventory Effect:** Zero inventory effect (prevents duplicate stock loss)
- **Financial & Accounting Effect:** Zero financial effect (prevents duplicate cash/khata charge)
- **Print Output Result:** Duplicate thermal receipt printed
- **Offline Working Mode:** Reprint available from local offline storage

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Receipt reprinted safely without duplicating database records
- **Common Failure Scenarios:** If printer remains broken, switch printer setting to standard browser PDF print
- **Correction & Reversal Path:** N/A
- **Source Traceability:** `FLOW-021, ACTION-011, GAP-001, GAP-023`

---
### `SOP-032`: Desktop-to-Mobile Peer-to-Peer Device Pairing

- **Operational Domain:** `PRINTING_HARDWARE`
- **Purpose:** Pair a Mobile Tablet POS with a Desktop Counter POS via QR code to mirror product catalog, images, and local databases over local Wi-Fi
- **Applicable Roles:** Branch Manager, Salesperson / Cashier, Business Owner
- **Prerequisites:** Both devices connected to same local Wi-Fi / LAN subnet
- **Starting Screen:** Device Pairing (/device-pairing)
- **Master Data Required:** Desktop Local DB, Mobile Tablet Dexie DB
- **Branch / Tenant Context:** Assigned branch_id and active business_id

#### Step-by-Step Operating Procedure
1. On Desktop POS: Navigate to Device Pairing (/device-pairing) -> Click 'Generate Desktop Pairing QR Code'.
2. Desktop generates QR token payload containing local private IP (e.g. `192.168.1.100`), port (`8080`), and temporary token.
3. On Mobile Tablet POS: Open Device Pairing -> Click 'Scan Pairing QR Code'.
4. Scan the Desktop QR code using tablet camera.
5. Mobile `devicePairingService` validates pairing IP:
   - Enforces valid IPv4 octet syntax.
   - Enforces strict RFC1918 private address ranges (`10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`).
   - Rejects public WAN addresses and validates unprivileged port (1024-65535).
   - Compares same-subnet network prefix against tablet local IP.
6. Mobile connects to Desktop POS endpoint `http://192.168.1.100:8080/api/sync-bundle`.
7. Products, categories, brands, customers, and base64 store images are streamed and imported into mobile Dexie localDb.

#### System Rules & Business Effects
- **Validation Rules:** Strict same-subnet validation (GAP-024); reject public WAN IPs; valid security token
- **System Calculations:** Counts synced products and images; verifies bundle integrity
- **Database Mutation Effect:** Bulk imports catalog and assets into mobile tablet IndexedDB
- **Inventory Effect:** Mobile tablet receives identical stock catalog
- **Financial & Accounting Effect:** Zero financial effect
- **Print Output Result:** Pairing success screen displaying synced counts
- **Offline Working Mode:** Operates purely over local Wi-Fi without internet connectivity

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Mobile tablet fully paired and ready for mobile counter / floor checkout
- **Common Failure Scenarios:** If subnet mismatch or public IP detected, connection is immediately blocked with security alert
- **Correction & Reversal Path:** Click 'Unpair Device' to disconnect peer link
- **Source Traceability:** `FLOW-023, FORM-031, ACTION-076, ACTION-077, GAP-024`

---
### `SOP-033`: Historical Sale Item Snapshot Reconciliation Drill

- **Operational Domain:** `SUPPORT_RECONCILIATION`
- **Purpose:** Execute administrative dry-run inspection and non-destructive backfill for legacy sale items created prior to Phase-9 snapshot migrations
- **Applicable Roles:** Business Owner, Accountant
- **Prerequisites:** Artisan CLI or Admin Support Console access
- **Starting Screen:** Admin Support Console / CLI
- **Master Data Required:** Legacy Sale Items (NULL snapshots), Product Catalog Master
- **Branch / Tenant Context:** Tenant wide (business_id)

#### Step-by-Step Operating Procedure
1. Open terminal in backend API directory (`c:\xampp\htdocs\HBOS\api`).
2. Execute dry-run audit command: `php artisan hbos:backfill-sale-item-snapshots --dry-run`.
3. Review output report detailing: total legacy sales inspected, items missing snapshots, matched products, and unmatched deleted products.
4. Verify that the backfill flags all populated rows as approximate (`snapshot_is_approximate = true`) to preserve historical truth integrity.
5. If audit verified and approved: execute live backfill: `php artisan hbos:backfill-sale-item-snapshots`.
6. Log execution timestamp and affected record count in administrative audit register.

#### System Rules & Business Effects
- **Validation Rules:** Always execute --dry-run first; never overwrite existing non-null snapshots; flag backfilled rows
- **System Calculations:** Reads product_name, sku, unit_cost, barcode from current product master and applies to legacy sale_items with NULL snapshots
- **Database Mutation Effect:** Populates NULL snapshot columns on legacy sale_items; sets is_approximate flag
- **Inventory Effect:** Zero inventory effect
- **Financial & Accounting Effect:** Zero financial effect; does not alter monetary totals
- **Print Output Result:** Audit reconciliation report log
- **Offline Working Mode:** Server administrative command only

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Legacy invoices have baseline readable snapshots without fabricating authoritative historical truth
- **Common Failure Scenarios:** If database connection fails, command exits cleanly without modifying records
- **Correction & Reversal Path:** Rollback via migration reset or set snapshot columns back to NULL if needed
- **Source Traceability:** `GAP-001, REPAIR-001, MIG-001, LIM-001`

---
### `SOP-034`: Customer Khata Ledger Balance Auditing & Discrepancy Reconciliation

- **Operational Domain:** `SUPPORT_RECONCILIATION`
- **Purpose:** Audit mathematical variances between customers.balance and cumulative customer_ledgers debit/credit movements, and generate audit reports for manual review
- **Applicable Roles:** Accountant, Business Owner
- **Prerequisites:** Accounting audit period open
- **Starting Screen:** Admin Support Console / CLI
- **Master Data Required:** Customer Master, Customer Ledgers Movement History
- **Branch / Tenant Context:** Tenant wide (business_id)

#### Step-by-Step Operating Procedure
1. Execute dry-run audit: `php artisan hbos:audit-khata-ledger --dry-run`.
2. Review discrepancy report itemizing every customer where `balance != Sum(Debits) - Sum(Credits)`.
3. For each flagged discrepancy:
   a. Inspect historical transaction receipts and physical passbooks with customer.
   b. Determine whether difference originates from unrecorded cash receipt or legacy calculation defect.
4. If confirmed: post an explicit compensating ledger entry via Customer Khata interface with detailed audit reason.
5. Re-run `php artisan hbos:audit-khata-ledger --dry-run` to confirm zero remaining variance.

#### System Rules & Business Effects
- **Validation Rules:** Never auto-mutate customer balance without manual audit trail; preserve historical entries
- **System Calculations:** Calculated Ledger Balance = Sum(Debits) - Sum(Credits); Variance = customers.balance - Calculated Ledger Balance
- **Database Mutation Effect:** Dry-run modifies nothing; manual compensation inserts explicit adjustment customer_ledgers row
- **Inventory Effect:** Zero inventory effect
- **Financial & Accounting Effect:** Reconciles Accounts Receivable subledger with general ledger
- **Print Output Result:** Khata Discrepancy Audit Log
- **Offline Working Mode:** Server administrative command only

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Customer balances 100% mathematically reconciled with ledger history
- **Common Failure Scenarios:** Unresolved customer disputes referred to management for settlement
- **Correction & Reversal Path:** Compensating adjustment entries can be reversed with supervisor authorization
- **Source Traceability:** `GAP-008, REPAIR-005, LIM-002`

---
### `SOP-035`: Legacy NULL Expense Account Audit & Reclassification

- **Operational Domain:** `SUPPORT_RECONCILIATION`
- **Purpose:** Audit legacy expenses created prior to mandatory financial account enforcement, generate reconciliation logs, and classify funding sources
- **Applicable Roles:** Accountant, Business Owner
- **Prerequisites:** Legacy expense records exist in database
- **Starting Screen:** Admin Support Console / CLI
- **Master Data Required:** Legacy Expenses Table, Financial Accounts
- **Branch / Tenant Context:** Tenant wide (business_id)

#### Step-by-Step Operating Procedure
1. Execute dry-run inspection: `php artisan hbos:repair-null-expense-accounts --dry-run`.
2. Review report listing all historical expense rows where `financial_account_id IS NULL`.
3. Inspect historical physical vouchers or branch cash sheets for the expense dates.
4. For deterministically proven cash expenses: assign default branch cash account ID.
5. For unidentifiable funding sources: flag record with audit note 'Legacy Pre-Remediation Unassigned Expense'.
6. Execute targeted update with audit log.

#### System Rules & Business Effects
- **Validation Rules:** Do not guess or fabricate funding bank accounts for historical audits
- **System Calculations:** Identifies count and monetary sum of unallocated legacy expenses
- **Database Mutation Effect:** Assigns verified financial_account_id or audit flag; never destructively deletes rows
- **Inventory Effect:** Zero inventory effect
- **Financial & Accounting Effect:** Provides transparent audit trail for historical P&L reporting
- **Print Output Result:** Expense Account Reconciliation Audit Log
- **Offline Working Mode:** Server administrative command only

#### Outcome, Failure & Reversal Handling
- **Expected Success Outcome:** Legacy expenses classified without corrupting financial history
- **Common Failure Scenarios:** Uncertain records retained with explanatory notes
- **Correction & Reversal Path:** Revert account ID if subsequent physical audit uncovers alternate funding
- **Source Traceability:** `GAP-013, REPAIR-003, MIG-004, LIM-003`

---
