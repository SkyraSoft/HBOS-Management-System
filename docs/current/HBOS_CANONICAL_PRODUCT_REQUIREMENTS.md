# HBOS Canonical Product Requirements Master Register

**Program**: SkyraSoft HBOS Small Retail Transformation Program  
**Phase**: Phase 1 — Prompt 1 of 3: Canonical 300-Question Requirement Registry  
**Status**: AUTHORITATIVE CANONICAL PRODUCT SPECIFICATION (FINAL CONSISTENCY CERTIFIED)  
**Total Canonical Atomic Requirements**: 32  
**Total Unique Source Questions Covered**: 300 / 300 (100% Coverage, 0 Unmapped)  
**Question-Requirement Mapping Edges**: 317  

---

## [REQ-0001] Staged Business Registration & Store Identity Setup

- **Primary Domain**: `BUSINESS_ONBOARDING`
- **Business Problem**: Small retailer needs to register a new HBOS business account with store name, phone, address, and local retail defaults without friction or enterprise configuration overhead.
- **Plain Language Requirement**: A new shop owner can register their business in under two minutes, establishing their store identity, Pakistani phone number, and default local store.
- **Source Question Coverage (5 questions)**: `SQ-140`, `SQ-141`, `SQ-142`, `MQ-001`, `MQ-002`
- **Business Shapes**: `BOTH`
- **Target Module**: `BUSINESS_ONBOARDING`
- **Actors**: `BUSINESS_OWNER`
- **Priority**: `P0_MUST_WORK_TO_RUN_STORE`
- **Launch Criticality**: `LAUNCH_BLOCKER`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `LOW`
- **Primary Phase**: `Phase 2`
- **Secondary Phases**: `Phase 3`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Single store owner completes onboarding and immediately lands on their active store POS/Dashboard.
- **Multi-Branch Behavior**: Establishes the Primary/Main Store baseline for future multi-branch expansion without re-registration.
- **Business Owner Role**: Full administrative control over business settings and contact details.
- **Branch Manager Role**: Read-only store profile details.
- **Cashier / Salesperson Role**: No access to business registration or profile settings.
- **Offline Behavior**: Requires online connectivity for initial account registration and token issuance.
- **Printing & Receipts**: Captures store header info used on receipt printing.
- **Reporting Relevance**: Establishes root business entity for all consolidated reporting.
- **Pakistani Retail Relevance**: Pakistani mobile format (+92 / 03xx), Asia/Karachi timezone, and PKR currency defaults.

### Acceptance & Success Criteria
- **Acceptance Criteria**: Owner registers with business name, owner name, mobile number, and password; business and primary branch are created atomically with PKR default.
- **Measurable Success Condition**: 100% of newly registered businesses have a valid primary branch and default settings initialized.

---

## [REQ-0002] Main Store / Primary Branch Immutability & Expansion Growth

- **Primary Domain**: `BUSINESS_ONBOARDING`
- **Business Problem**: Store owner starts with one shop and later adds a second branch without breaking existing historical transaction data, customer balances, or inventory logs.
- **Plain Language Requirement**: The original store automatically becomes the immutable Primary Branch upon multi-branch expansion, preserving all historical sales, balances, and inventory.
- **Source Question Coverage (6 questions)**: `SQ-148`, `SQ-149`, `SQ-150`, `MQ-003`, `MQ-004`, `MQ-005`
- **Business Shapes**: `BOTH`
- **Target Module**: `BRANCH_MANAGEMENT`
- **Actors**: `BUSINESS_OWNER`
- **Priority**: `P0_MUST_WORK_TO_RUN_STORE`
- **Launch Criticality**: `LAUNCH_BLOCKER`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `MEDIUM`
- **Primary Phase**: `Phase 2`
- **Secondary Phases**: `Phase 7`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: System operates with one primary store; branch switcher is completely hidden.
- **Multi-Branch Behavior**: Second branch added; branch switcher reveals; primary branch remains master store.
- **Business Owner Role**: Can configure branch properties but cannot delete the root primary branch.
- **Branch Manager Role**: Assigned to specific branch; cannot modify primary store status.
- **Cashier / Salesperson Role**: Operates within assigned store counter.
- **Offline Behavior**: Primary branch ID remains immutable during offline sync reconciliation.
- **Printing & Receipts**: Default receipt header points to primary branch until branch specific headers are configured.
- **Reporting Relevance**: All historical single-store reports seamlessly map to Primary Branch data.
- **Pakistani Retail Relevance**: Standard expansion model for Pakistani commercial markets (Main Shop -> Adda / Branch Shop).

### Acceptance & Success Criteria
- **Acceptance Criteria**: Adding a second branch transitions business to multi-branch mode without requiring data migration or breaking historical IDs.
- **Measurable Success Condition**: Zero orphaned transactions or foreign key errors after expanding from 1 to N branches.

---

## [REQ-0003] Branch Provisioning, Deactivation & Store Settings

- **Primary Domain**: `BRANCHES`
- **Business Problem**: Store owner opening or closing retail branches needs to provision branch metadata, set operating status, and manage branch contact information.
- **Plain Language Requirement**: Business Super Owner can add new branch locations, configure branch contact details and addresses, or deactivate closed branches without deleting historical transactions.
- **Source Question Coverage (9 questions)**: `MQ-006`, `MQ-007`, `MQ-008`, `MQ-009`, `MQ-010`, `MQ-011`, `MQ-012`, `MQ-013`, `MQ-014`
- **Business Shapes**: `MULTI_BRANCH_ONLY`
- **Target Module**: `BRANCH_MANAGEMENT`
- **Actors**: `BUSINESS_OWNER`
- **Priority**: `P1_CORE_DAILY_OPERATION`
- **Launch Criticality**: `LAUNCH_REQUIRED`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `MEDIUM`
- **Primary Phase**: `Phase 7`
- **Secondary Phases**: `Phase 2`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Hidden completely in single-store mode (Progressive Complexity).
- **Multi-Branch Behavior**: Visible in Settings / Branch Management.
- **Business Owner Role**: Full CRUD and status toggle for all branches.
- **Branch Manager Role**: No access to branch lifecycle management.
- **Cashier / Salesperson Role**: No access.
- **Offline Behavior**: Active branches synced to client; deactivated branches blocked from new offline transactions.
- **Printing & Receipts**: Branch specific phone, address, and receipt notes configured per branch.
- **Reporting Relevance**: Reports filterable by active and historical deactivated branches.
- **Pakistani Retail Relevance**: Pakistani phone and location formatting per branch.

### Acceptance & Success Criteria
- **Acceptance Criteria**: Owner adds new branch with unique name, code, contact info; primary branch cannot be deactivated; inactive branches reject new POS sales.
- **Measurable Success Condition**: Branch lifecycle operations execute atomically without transaction corruption.

---

## [REQ-0004] Small Retail 3-Role Access Model & Staff Invitation

- **Primary Domain**: `USERS_AND_ACCESS`
- **Business Problem**: Store owner needs to delegate daily selling to cashiers and store supervision to managers without exposing central profit margins, supplier debts, or owner settings.
- **Plain Language Requirement**: Simple 3-role system: Business Owner (full control), Branch Manager (branch-scoped operations), and Cashier/Salesperson (POS checkout and daily operations).
- **Source Question Coverage (9 questions)**: `SQ-143`, `SQ-144`, `SQ-145`, `SQ-146`, `SQ-147`, `MQ-019`, `MQ-020`, `MQ-021`, `MQ-024`
- **Business Shapes**: `BOTH`
- **Target Module**: `USERS_AND_ACCESS`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER, SALESPERSON`
- **Priority**: `P0_MUST_WORK_TO_RUN_STORE`
- **Launch Criticality**: `LAUNCH_BLOCKER`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `MEDIUM`
- **Primary Phase**: `Phase 2`
- **Secondary Phases**: `Phase 3, Phase 9`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Owner operates directly with zero staff required; optional cashier account can be added.
- **Multi-Branch Behavior**: Owner assigns Branch Managers and Cashiers to specific branches.
- **Business Owner Role**: Super Owner can view and perform any operational action across any store.
- **Branch Manager Role**: Manager operates strictly within assigned branch; blocked from central finance and other branches.
- **Cashier / Salesperson Role**: Cashier restricted to POS sales, customer lookup, and own shift transactions.
- **Offline Behavior**: Local cached credential validation and role boundary enforcement.
- **Printing & Receipts**: Staff name printed on receipts as cashier identifier.
- **Reporting Relevance**: Staff sales performance tracked per user ID.
- **Pakistani Retail Relevance**: Mobile number login and PIN/password options suitable for retail staff.

### Acceptance & Success Criteria
- **Acceptance Criteria**: Cashier cannot view cost prices, total store profits, or edit settings; Manager cannot access other branches; Owner has universal access.
- **Measurable Success Condition**: Zero cross-role or cross-branch permission escalation in automated security tests.

---

## [REQ-0005] Super Owner Universal Direct Operation (No Dummy Accounts)

- **Primary Domain**: `USERS_AND_ACCESS`
- **Business Problem**: In small retail, the business owner frequently stands at the counter to sell, receive stock, or collect cash without needing to switch into a dummy cashier account.
- **Plain Language Requirement**: Business Super Owner can operate any register, perform sales, receive purchases, and collect payments directly without creating or switching into simulated staff accounts.
- **Source Question Coverage (6 questions)**: `SQ-001`, `SQ-002`, `MQ-015`, `MQ-016`, `MQ-017`, `MQ-018`
- **Business Shapes**: `BOTH`
- **Target Module**: `POS`
- **Actors**: `BUSINESS_OWNER`
- **Priority**: `P0_MUST_WORK_TO_RUN_STORE`
- **Launch Criticality**: `LAUNCH_BLOCKER`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `LOW`
- **Primary Phase**: `Phase 2`
- **Secondary Phases**: `Phase 3, Phase 4`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Owner sells directly on single store counter.
- **Multi-Branch Behavior**: Owner can select active branch counter and sell directly without role change.
- **Business Owner Role**: Direct operation across all modules without permission prompts.
- **Branch Manager Role**: Limited to assigned branch operations.
- **Cashier / Salesperson Role**: Standard restricted counter operation.
- **Offline Behavior**: Owner token cached locally with full counter capabilities.
- **Printing & Receipts**: Receipt displays owner name or configured store counter name.
- **Reporting Relevance**: Sales reports attribute transactions directly to owner.
- **Pakistani Retail Relevance**: Standard practice where Pakistani shop owners actively sell during peak rush hours.

### Acceptance & Success Criteria
- **Acceptance Criteria**: Owner can open POS counter, add items, take payment, and complete sale without creating separate cashier persona.
- **Measurable Success Condition**: 100% of core operational flows executable by Super Owner role directly.

---

## [REQ-0006] Owner Read-Only "View-As" Branch Counter Preview Mode

- **Primary Domain**: `USERS_AND_ACCESS`
- **Business Problem**: Business Owner needs to inspect what a Branch Manager or Cashier sees on their screen to audit operational views and assist staff without impersonation or mutation risks.
- **Plain Language Requirement**: Owner can activate a strictly READ-ONLY "View-As" mode for any branch and role (Manager/Cashier); owner identity is preserved and all mutation requests are blocked by the server.
- **Source Question Coverage (4 questions)**: `MQ-022`, `MQ-023`, `MQ-025`, `MQ-026`
- **Business Shapes**: `MULTI_BRANCH_ONLY`
- **Target Module**: `USERS_AND_ACCESS`
- **Actors**: `BUSINESS_OWNER`
- **Priority**: `P2_IMPORTANT_MANAGEMENT`
- **Launch Criticality**: `POST_LAUNCH_SAFE`
- **V1 Scope Classification**: `V1_SIMPLIFIED`
- **Complexity**: `MEDIUM`
- **Primary Phase**: `Phase 2`
- **Secondary Phases**: `Phase 7, Phase 9`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Hidden in single-store mode.
- **Multi-Branch Behavior**: Owner can select "View Branch X as Cashier" from user management or store switcher.
- **Business Owner Role**: Can preview any branch and role; mutations disabled with visual warning.
- **Branch Manager Role**: No access to View-As mode.
- **Cashier / Salesperson Role**: No access to View-As mode.
- **Offline Behavior**: View-As mode is available online; offline mutations strictly forbidden.
- **Printing & Receipts**: Receipt printing disabled or watermarked as PREVIEW during View-As.
- **Reporting Relevance**: Audit log captures every View-As session initiation and exit.
- **Pakistani Retail Relevance**: Allows Pakistani multi-shop owner to troubleshoot branch POS screens remotely from home.

### Acceptance & Success Criteria
- **Acceptance Criteria**: Owner toggles View-As for Branch B as Cashier; UI adapts to cashier layout; attempting any checkout or stock edit returns read-only error; audit log records preview session.
- **Measurable Success Condition**: Zero accidental state modifications permitted during View-As mode in backend automated security tests.

---

## [REQ-0007] Same-Device User Session & Cache Isolation

- **Primary Domain**: `USERS_AND_ACCESS`
- **Business Problem**: Multiple staff members (Owner, Manager, Cashier) share the same physical POS counter PC/tablet during shift changes; previous user permissions or cached sensitive data must never leak to the next user.
- **Plain Language Requirement**: Logging out purges in-memory session tokens, clears cached cost/profit sensitive states, resets branch context to login defaults, and ensures clean attribution for the next logging-in user.
- **Source Question Coverage (4 questions)**: `SQ-143`, `SQ-144`, `MQ-019`, `MQ-020`
- **Business Shapes**: `BOTH`
- **Target Module**: `USERS_AND_ACCESS`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER, SALESPERSON`
- **Priority**: `P0_MUST_WORK_TO_RUN_STORE`
- **Launch Criticality**: `LAUNCH_BLOCKER`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `MEDIUM`
- **Primary Phase**: `Phase 2`
- **Secondary Phases**: `Phase 8, Phase 9`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Ensures cashier cannot access owner tabs if owner steps away and locks screen.
- **Multi-Branch Behavior**: Ensures branch manager login locks view to their assigned branch only.
- **Business Owner Role**: Can quickly lock screen with PIN or logout.
- **Branch Manager Role**: Logs in with own credentials; sees branch data only.
- **Cashier / Salesperson Role**: Logs in with counter PIN; restricted to POS checkout.
- **Offline Behavior**: Offline user switching validates PIN against locally cached hash without revealing previous user un-synced buffers.
- **Printing & Receipts**: Active cashier name dynamically updates on receipt footer.
- **Reporting Relevance**: Transactions attributed strictly to authenticated user at the exact moment of sale.
- **Pakistani Retail Relevance**: Standard reality in Pakistani shops where 1 counter PC is shared by 2-3 shifts.

### Acceptance & Success Criteria
- **Acceptance Criteria**: Owner logs out on counter PC; Cashier logs in immediately after; Cashier cannot access profit reports or view previous owner financial state via browser back button or cached stores.
- **Measurable Success Condition**: 100% token and state purge verified in automated session security tests.

---

## [REQ-0008] Ultra-Fast POS Retail Checkout & Barcode Scanning

- **Primary Domain**: `POS`
- **Business Problem**: Retail counter requires instant item addition via barcode scan, product code, or quick grid tap, with instantaneous subtotaling during peak shopping rush.
- **Plain Language Requirement**: Cashier or Owner scans barcodes or taps fast-moving items, adjust quantities, applies line items, and totals the bill in under 3 seconds per transaction.
- **Source Question Coverage (13 questions)**: `SQ-008`, `SQ-009`, `SQ-010`, `SQ-011`, `SQ-012`, `SQ-013`, `SQ-014`, `SQ-015`, `SQ-016`, `MQ-028`, `MQ-029`, `MQ-030`, `MQ-031`
- **Business Shapes**: `BOTH`
- **Target Module**: `POS`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER, SALESPERSON`
- **Priority**: `P0_MUST_WORK_TO_RUN_STORE`
- **Launch Criticality**: `LAUNCH_BLOCKER`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `MEDIUM`
- **Primary Phase**: `Phase 4`
- **Secondary Phases**: `Phase 8`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Pulls stock from active single store inventory.
- **Multi-Branch Behavior**: Pulls stock specifically from active branch counter inventory.
- **Business Owner Role**: Unrestricted POS operation with cost price visibility toggle.
- **Branch Manager Role**: POS operation for assigned branch.
- **Cashier / Salesperson Role**: High-speed counter checkout without cost price exposure.
- **Offline Behavior**: Operates 100% offline via local IndexedDB product cache.
- **Printing & Receipts**: Sends raw ESC/POS thermal command on sale completion.
- **Reporting Relevance**: Feeds real-time daily sales volume and item velocity counters.
- **Pakistani Retail Relevance**: Optimized for high-throughput Pakistani retail shops (general stores, grocery, clothing).

### Acceptance & Success Criteria
- **Acceptance Criteria**: Scanning a valid barcode adds product to cart in <100ms; pressing checkout posts sale and returns change calculation.
- **Measurable Success Condition**: Zero UI latency stutter during rapid 20-item cart scanning.

---

## [REQ-0009] Split Multi-Tender Payment Processing & Cash Change Calculation

- **Primary Domain**: `POS`
- **Business Problem**: Customers frequently pay using multiple payment methods (e.g. part Cash, part JazzCash/Easypaisa, or part Khata) and cashiers must give exact change.
- **Plain Language Requirement**: Support recording split payments across Cash, Bank, Digital Wallets (JazzCash, Easypaisa, Raast), and Khata on a single receipt, with automatic exact change return calculation for cash tendered.
- **Source Question Coverage (12 questions)**: `SQ-003`, `SQ-017`, `SQ-018`, `SQ-019`, `SQ-020`, `SQ-021`, `SQ-022`, `SQ-023`, `MQ-032`, `MQ-033`, `MQ-034`, `MQ-035`
- **Business Shapes**: `BOTH`
- **Target Module**: `POS`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER, SALESPERSON`
- **Priority**: `P0_MUST_WORK_TO_RUN_STORE`
- **Launch Criticality**: `LAUNCH_BLOCKER`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `MEDIUM`
- **Primary Phase**: `Phase 4`
- **Secondary Phases**: `Phase 6`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Attributes payments to single store financial accounts.
- **Multi-Branch Behavior**: Attributes cash to branch drawer and digital tenders to central/branch accounts.
- **Business Owner Role**: Can process and review split payments across all methods.
- **Branch Manager Role**: Processes split payments on branch counter.
- **Cashier / Salesperson Role**: Processes customer tenders; change displayed in large bold text.
- **Offline Behavior**: Payment records stored in offline IndexedDB sale payload.
- **Printing & Receipts**: Receipt prints tender breakdown (Cash Paid, JazzCash Paid, Change Given, Khata Balance).
- **Reporting Relevance**: Daily close breaks down totals by payment method and drawer cash.
- **Pakistani Retail Relevance**: Essential for Pakistan retail where mobile digital wallets and cash are mixed continuously.

### Acceptance & Success Criteria
- **Acceptance Criteria**: On PKR 1,500 bill: Customer pays PKR 500 JazzCash + PKR 1000 Cash Note -> System records PKR 500 digital + PKR 1000 cash, prompts PKR 0 change, balances sale.
- **Measurable Success Condition**: 100% mathematical integrity between total sale amount, payments table, and change disbursed.

---

## [REQ-0010] POS Cart Hold/Resume, Order Discounts & Item Price Overrides

- **Primary Domain**: `POS`
- **Business Problem**: Customers often leave counter to grab another item while other customers are waiting, or bargain for a bill discount / rounded price.
- **Plain Language Requirement**: Cashier can put active cart on hold and serve next customer, resume held cart later, apply percentage/fixed discount, or override item price within allowed permissions.
- **Source Question Coverage (8 questions)**: `SQ-024`, `SQ-025`, `SQ-026`, `SQ-027`, `SQ-028`, `MQ-036`, `MQ-037`, `MQ-038`
- **Business Shapes**: `BOTH`
- **Target Module**: `POS`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER, SALESPERSON`
- **Priority**: `P1_CORE_DAILY_OPERATION`
- **Launch Criticality**: `LAUNCH_REQUIRED`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `MEDIUM`
- **Primary Phase**: `Phase 4`
- **Secondary Phases**: `Phase 8`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Held carts saved on local machine counter.
- **Multi-Branch Behavior**: Held carts isolated per branch POS counter.
- **Business Owner Role**: Unrestricted discount and price override authority.
- **Branch Manager Role**: Discount within store limit; can authorize cashier price override.
- **Cashier / Salesperson Role**: Can hold/resume carts; price overrides subject to role permission check.
- **Offline Behavior**: Held carts stored in client IndexedDB memory.
- **Printing & Receipts**: Receipt prints original price, discount amount, and net payable.
- **Reporting Relevance**: Discount summary report reveals total revenue given away in discounts per cashier.
- **Pakistani Retail Relevance**: Accommodates customary Pakistani retail bargaining (riayat / round-off).

### Acceptance & Success Criteria
- **Acceptance Criteria**: Cashier holds cart with 3 items, scans new sale for customer B, finishes sale B, resumes cart A with all 3 items intact.
- **Measurable Success Condition**: Zero lost items or orphaned cart buffers during hold/resume cycles.

---

## [REQ-0011] Lightweight Customer Directory & POS Fast-Add

- **Primary Domain**: `CUSTOMERS`
- **Business Problem**: Cashier needs to quickly link a sale to a customer or create a new customer in seconds without leaving the checkout screen.
- **Plain Language Requirement**: Search customers by name or 11-digit Pakistani mobile number; quick-add new customer popup right inside POS checkout modal.
- **Source Question Coverage (6 questions)**: `SQ-029`, `SQ-030`, `SQ-031`, `MQ-039`, `MQ-040`, `MQ-041`
- **Business Shapes**: `BOTH`
- **Target Module**: `CUSTOMERS`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER, SALESPERSON`
- **Priority**: `P1_CORE_DAILY_OPERATION`
- **Launch Criticality**: `LAUNCH_REQUIRED`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `LOW`
- **Primary Phase**: `Phase 4`
- **Secondary Phases**: `Phase 3`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Customer directory scoped to business store.
- **Multi-Branch Behavior**: Customer directory shared across all branches; customer can purchase at any branch.
- **Business Owner Role**: Full CRUD on customer directory, credit limit setting.
- **Branch Manager Role**: View customer profile, edit contact info, add new customers.
- **Cashier / Salesperson Role**: Search customers, quick-add customer during checkout.
- **Offline Behavior**: Local customer cache allows offline lookup and quick-add queuing.
- **Printing & Receipts**: Customer name and phone printed on invoice header.
- **Reporting Relevance**: Customer purchase frequency and total lifetime spend.
- **Pakistani Retail Relevance**: Supports 03xx-xxxxxxx Pakistani mobile formatting.

### Acceptance & Success Criteria
- **Acceptance Criteria**: Typing last 4 digits of customer phone filters customer list in <50ms; clicking Add Customer creates record and selects them in cart immediately.
- **Measurable Success Condition**: 100% duplicate mobile detection within tenant.

---

## [REQ-0012] Pakistani Customer Khata (Udhaar) Credit Ledger & Collections

- **Primary Domain**: `KHATA`
- **Business Problem**: Small retail depends heavily on credit sales (Khata/Udhaar); shopkeepers must track who owes money, enforce credit limits, and record partial balance recoveries.
- **Plain Language Requirement**: Track customer outstanding credit balances, record credit sales with limit enforcement, generate PDF/WhatsApp Khata statements, and record partial/full repayments.
- **Source Question Coverage (15 questions)**: `SQ-004`, `SQ-032`, `SQ-033`, `SQ-034`, `SQ-035`, `SQ-036`, `SQ-037`, `SQ-038`, `MQ-042`, `MQ-043`, `MQ-044`, `MQ-045`, `MQ-046`, `MQ-047`, `MQ-048`
- **Business Shapes**: `BOTH`
- **Target Module**: `KHATA`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER, SALESPERSON`
- **Priority**: `P0_MUST_WORK_TO_RUN_STORE`
- **Launch Criticality**: `LAUNCH_BLOCKER`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `MEDIUM`
- **Primary Phase**: `Phase 4`
- **Secondary Phases**: `Phase 6`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Single shop credit ledger.
- **Multi-Branch Behavior**: Centralized customer Khata ledger accessible across all branches; payment at Branch B updates total balance.
- **Business Owner Role**: Set customer credit limits, view all store receivables, forgive bad debt.
- **Branch Manager Role**: View customer balance, record credit recovery payment for assigned store.
- **Cashier / Salesperson Role**: Sell on Khata if allowed; record cash recovery payment into drawer.
- **Offline Behavior**: Offline Khata sales queued with customer UUID; sync reconciles balance.
- **Printing & Receipts**: Prints Previous Balance, Current Bill, Amount Paid, and Net Remaining Balance on receipt.
- **Reporting Relevance**: Total Outstanding Khata summary report with aging brackets (30/60/90 days).
- **Pakistani Retail Relevance**: Core traditional Pakistani retail credit workflow (Baqaaya / Udhaar Khata).

### Acceptance & Success Criteria
- **Acceptance Criteria**: Sale with PKR 500 Khata tender increases customer balance from PKR 1,000 to PKR 1,500; collecting PKR 1,000 cash reduces balance to PKR 500 and records PKR 1,000 cash drawer inflow.
- **Measurable Success Condition**: Zero mathematical drift between customer balance and sum of ledger debits/credits.

---

## [REQ-0013] POS Customer Returns, Exchanges, Restock Condition & Refunds

- **Primary Domain**: `RETURNS`
- **Business Problem**: Customers return purchased items for cash refund, exchange for another item, or credit balance adjustment; returned stock must be audited for restockable vs damaged condition.
- **Plain Language Requirement**: Process full/partial customer returns with invoice lookup, inspect item condition (Restockable vs Damaged/Discarded), adjust stock accordingly, and disburse refund or store credit.
- **Source Question Coverage (11 questions)**: `SQ-039`, `SQ-040`, `SQ-041`, `SQ-042`, `SQ-043`, `SQ-044`, `MQ-049`, `MQ-050`, `MQ-051`, `MQ-052`, `MQ-053`
- **Business Shapes**: `BOTH`
- **Target Module**: `RETURNS`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER, SALESPERSON`
- **Priority**: `P0_MUST_WORK_TO_RUN_STORE`
- **Launch Criticality**: `LAUNCH_BLOCKER`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `MEDIUM`
- **Primary Phase**: `Phase 4`
- **Secondary Phases**: `Phase 5, Phase 6`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Returns processed against single store invoices.
- **Multi-Branch Behavior**: Returns can be processed at purchase branch or cross-branch if policy permits.
- **Business Owner Role**: Full return and refund approval authority without invoice restriction.
- **Branch Manager Role**: Approve branch returns with invoice lookup.
- **Cashier / Salesperson Role**: Process return with original receipt scan.
- **Offline Behavior**: Return transactions validated against cached invoice payload.
- **Printing & Receipts**: Return voucher printed with refunded amount and updated customer balance.
- **Reporting Relevance**: Return rate percentage report and daily refund cash deduction tracking.
- **Pakistani Retail Relevance**: Handles Pakistani market return conventions (Wapsi / Tabdeeli policy).

### Acceptance & Success Criteria
- **Acceptance Criteria**: Returning 1 restockable unit increments branch stock by 1 and deducts cash refund from drawer; returning 1 damaged unit logs write-off without restock.
- **Measurable Success Condition**: 100% accurate stock and cash drawer reconciliation after return processing.

---

## [REQ-0014] Product Catalog Master, Categories, Brands & Barcodes

- **Primary Domain**: `PRODUCTS`
- **Business Problem**: Store owner needs to organize thousands of retail products with clean SKU codes, manufacturer barcodes, categories, and brand tags for fast counter lookup.
- **Plain Language Requirement**: Manage product master catalog with product name, SKU, primary and secondary barcodes, category, brand, and active/inactive status.
- **Source Question Coverage (9 questions)**: `SQ-045`, `SQ-046`, `SQ-047`, `SQ-048`, `SQ-049`, `SQ-050`, `MQ-054`, `MQ-055`, `MQ-056`
- **Business Shapes**: `BOTH`
- **Target Module**: `PRODUCTS`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER`
- **Priority**: `P0_MUST_WORK_TO_RUN_STORE`
- **Launch Criticality**: `LAUNCH_BLOCKER`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `LOW`
- **Primary Phase**: `Phase 5`
- **Secondary Phases**: `Phase 4`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Catalog directly reflects single shop inventory.
- **Multi-Branch Behavior**: Centralized catalog shared across all branches (create once, sell anywhere).
- **Business Owner Role**: Create, edit, archive products, categories, and brands.
- **Branch Manager Role**: Can view catalog; can create products if owner grants permission.
- **Cashier / Salesperson Role**: Read-only access for barcode lookup and selling.
- **Offline Behavior**: Product catalog fully cached in IndexedDB for offline barcode lookup.
- **Printing & Receipts**: Generates printable barcode shelf and item adhesive stickers.
- **Reporting Relevance**: Sales and profit analysis grouped by Category and Brand.
- **Pakistani Retail Relevance**: Supports both international UPC/EAN barcodes and custom internal 4-digit codes.

### Acceptance & Success Criteria
- **Acceptance Criteria**: Adding product "Lipton Yellow Label 500g" with barcode "896101400101" allows immediate counter scanning in <50ms.
- **Measurable Success Condition**: Zero duplicate barcode collisions permitted within same tenant.

---

## [REQ-0015] Loose/Packaged Units, Wholesale/Retail Tiered Pricing & Below-Cost Warning

- **Primary Domain**: `PRICING`
- **Business Problem**: Retailers sell items in loose quantities (kg, grams, liters) or bulk packages (carton/box/piece) and need tiered pricing (retail vs wholesale) with protection against selling below purchase cost.
- **Plain Language Requirement**: Support fractional unit sales (kg, meter), unit conversions (1 carton = 24 pieces), retail/wholesale tiered pricing, and warning/blocking when sale price drops below cost price.
- **Source Question Coverage (10 questions)**: `SQ-051`, `SQ-052`, `SQ-053`, `SQ-054`, `SQ-055`, `SQ-056`, `MQ-057`, `MQ-058`, `MQ-059`, `MQ-060`
- **Business Shapes**: `BOTH`
- **Target Module**: `PRODUCTS`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER, SALESPERSON`
- **Priority**: `P0_MUST_WORK_TO_RUN_STORE`
- **Launch Criticality**: `LAUNCH_BLOCKER`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `MEDIUM`
- **Primary Phase**: `Phase 5`
- **Secondary Phases**: `Phase 4`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Applies prices on single store counter.
- **Multi-Branch Behavior**: Allows standard master pricing with optional branch-level price overrides.
- **Business Owner Role**: Sets purchase cost, retail price, wholesale price, and unit conversions.
- **Branch Manager Role**: Views prices; can switch between Retail and Wholesale tier at POS.
- **Cashier / Salesperson Role**: Selects tier or enters fractional quantity; receives warning if price invalid.
- **Offline Behavior**: Unit conversion multipliers and tiered prices cached in localDb.
- **Printing & Receipts**: Receipt prints unit quantity (e.g. 1.75 kg @ PKR 400/kg = PKR 700).
- **Reporting Relevance**: Gross profit margin calculation based on exact unit cost vs selling price.
- **Pakistani Retail Relevance**: Critical for Pakistani grocery, cloth, grain, and wholesale markets.

### Acceptance & Success Criteria
- **Acceptance Criteria**: Entering 0.5 kg of Sugar @ PKR 160/kg charges PKR 80; discounting below cost price of PKR 140 triggers warning and blocks cashier token.
- **Measurable Success Condition**: 100% precision in fractional unit math without rounding leaks.

---

## [REQ-0016] Branch Stock Levels & Physical Adjustments / Discrepancy Audits

- **Primary Domain**: `INVENTORY`
- **Business Problem**: Store stock levels drift over time due to shoplifting, breakage, or counting mistakes; store keepers need to audit physical stock and record adjustments with audit reasons.
- **Plain Language Requirement**: Track current physical stock per branch, record stock adjustments (Damage, Theft, Expiry, Audit Correction) with mandatory notes, and maintain full inventory ledger history.
- **Source Question Coverage (7 questions)**: `SQ-057`, `SQ-058`, `SQ-059`, `SQ-060`, `MQ-061`, `MQ-062`, `MQ-063`
- **Business Shapes**: `BOTH`
- **Target Module**: `INVENTORY`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER`
- **Priority**: `P0_MUST_WORK_TO_RUN_STORE`
- **Launch Criticality**: `LAUNCH_BLOCKER`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `MEDIUM`
- **Primary Phase**: `Phase 5`
- **Secondary Phases**: `Phase 9`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Adjusts stock for single store.
- **Multi-Branch Behavior**: Adjusts stock specifically for selected branch location.
- **Business Owner Role**: Full adjustment authority and stock loss review across all branches.
- **Branch Manager Role**: Can submit physical stock adjustment for assigned branch.
- **Cashier / Salesperson Role**: No access to stock adjustment tools.
- **Offline Behavior**: Displays cached stock level on offline counter; sync updates server inventory.
- **Printing & Receipts**: Prints physical inventory count sheets for manual shelf auditing.
- **Reporting Relevance**: Stock loss / write-off summary report by reason and financial value.
- **Pakistani Retail Relevance**: Addresses routine shelf audits and shrinkage in Pakistani retail.

### Acceptance & Success Criteria
- **Acceptance Criteria**: Adjusting stock from 12 to 10 with reason "Broken on shelf" updates quantity to 10 and creates an inventory_movement record of -2 with PKR cost value.
- **Measurable Success Condition**: Zero unexplained variance between stock ledger delta and current stock quantity.

---

## [REQ-0017] Stock Intelligence: Low-Stock Alerts, Out-of-Stock Tracking & Velocity Analytics

- **Primary Domain**: `INVENTORY`
- **Business Problem**: Store owners lose sales when popular items run out unobserved, or tie up working capital in dead items that do not sell.
- **Plain Language Requirement**: Configure low-stock alert thresholds, view instant lists of low/out-of-stock items, track fast-moving vs dead/slow-moving inventory, and calculate suggested reorder quantities.
- **Source Question Coverage (16 questions)**: `SQ-005`, `SQ-006`, `SQ-007`, `SQ-061`, `SQ-062`, `SQ-063`, `SQ-064`, `SQ-065`, `SQ-066`, `MQ-064`, `MQ-065`, `MQ-066`, `MQ-067`, `MQ-068`, `MQ-069`, `MQ-070`
- **Business Shapes**: `BOTH`
- **Target Module**: `INVENTORY`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER`
- **Priority**: `P1_CORE_DAILY_OPERATION`
- **Launch Criticality**: `LAUNCH_REQUIRED`
- **V1 Scope Classification**: `V1_SIMPLIFIED`
- **Complexity**: `MEDIUM`
- **Primary Phase**: `Phase 5`
- **Secondary Phases**: `Phase 3, Phase 6`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Stock intelligence scoped to single store.
- **Multi-Branch Behavior**: Stock intelligence filterable by All Stores or specific branch, with cross-branch stock visibility.
- **Business Owner Role**: Reviews business-wide stock velocity and locked dead stock capital.
- **Branch Manager Role**: Reviews branch low-stock list and submits reorder suggestions.
- **Cashier / Salesperson Role**: Sees out-of-stock warning badge on counter grid.
- **Offline Behavior**: Low stock visual badges shown in local product catalog.
- **Printing & Receipts**: Generates printable reorder shopping list with supplier contacts.
- **Reporting Relevance**: Dead stock report (items with 0 sales in 30/60/90 days) with locked capital value.
- **Pakistani Retail Relevance**: Helps Pakistani shopkeepers optimize limited cash flow on high-demand stock.

### Acceptance & Success Criteria
- **Acceptance Criteria**: Product X with threshold 5 and stock 3 triggers low-stock badge; reorder report displays supplier name and suggested reorder of 20 units based on 7-day sales velocity.
- **Measurable Success Condition**: 100% accurate classification of items into Out of Stock, Low Stock, Fast Moving, and Dead Stock.

---

## [REQ-0018] Inter-Branch Stock Transfers, Dispatch, In-Transit & Receive Verification

- **Primary Domain**: `STOCK_TRANSFERS`
- **Business Problem**: Multi-branch retail requires moving inventory from main warehouse/store to branch shops or between branches with strict tracking so items do not disappear in transit.
- **Plain Language Requirement**: Create stock transfer requests, dispatch stock from Source Branch (deducts stock and marks In-Transit), receive and verify stock at Destination Branch (adds stock), and log discrepancy notes.
- **Source Question Coverage (10 questions)**: `MQ-071`, `MQ-072`, `MQ-073`, `MQ-074`, `MQ-075`, `MQ-076`, `MQ-077`, `MQ-078`, `MQ-079`, `MQ-080`
- **Business Shapes**: `MULTI_BRANCH_ONLY`
- **Target Module**: `STOCK_TRANSFERS`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER`
- **Priority**: `P1_CORE_DAILY_OPERATION`
- **Launch Criticality**: `LAUNCH_REQUIRED`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `MEDIUM`
- **Primary Phase**: `Phase 7`
- **Secondary Phases**: `Phase 5`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Hidden completely in single-store mode.
- **Multi-Branch Behavior**: Full Stock Transfer module visible in navigation.
- **Business Owner Role**: Can initiate, approve, or cancel transfers across any branch.
- **Branch Manager Role**: Can dispatch from own branch and receive/acknowledge at own branch.
- **Cashier / Salesperson Role**: No access to transfer module.
- **Offline Behavior**: Transfers initiated online; offline alerts prevent transferring non-existent local stock.
- **Printing & Receipts**: Generates Delivery Challan / Gate Pass with item list and vehicle/driver info.
- **Reporting Relevance**: In-Transit inventory valuation report and inter-branch transfer history.
- **Pakistani Retail Relevance**: Standard inter-branch inventory transit practice (Bilty / Challan / Rider transfer).

### Acceptance & Success Criteria
- **Acceptance Criteria**: Dispatching 10 units from Main Store to Branch B decrements Main Store by 10 and sets status to IN_TRANSIT; receiving at Branch B increments Branch B by 10 and marks COMPLETED.
- **Measurable Success Condition**: Zero stock duplication or disappearance during transfer lifecycle.

---

## [REQ-0019] Purchasing, Supplier Profiles, Bills & Landed Cost Freight Allocation

- **Primary Domain**: `PURCHASES`
- **Business Problem**: Store owner buys inventory from wholesale distributors on cash or credit, incurring carriage/bilty/freight expenses that must be factored into true product unit cost.
- **Plain Language Requirement**: Record supplier purchase bills, auto-increment branch stock, allocate freight/landed cost across items to calculate true landed cost, track supplier balances, and cancel unpaid bills.
- **Source Question Coverage (11 questions)**: `SQ-067`, `SQ-068`, `SQ-069`, `SQ-070`, `SQ-074`, `SQ-075`, `MQ-081`, `MQ-082`, `MQ-083`, `MQ-084`, `MQ-085`
- **Business Shapes**: `BOTH`
- **Target Module**: `PURCHASES`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER`
- **Priority**: `P0_MUST_WORK_TO_RUN_STORE`
- **Launch Criticality**: `LAUNCH_BLOCKER`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `MEDIUM`
- **Primary Phase**: `Phase 5`
- **Secondary Phases**: `Phase 6`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Receives stock into single store; records supplier payable.
- **Multi-Branch Behavior**: Receives stock into specified branch; supplier balance unified across business.
- **Business Owner Role**: Full purchase management, supplier price negotiations, bill approval.
- **Branch Manager Role**: Can record incoming stock delivery for assigned branch.
- **Cashier / Salesperson Role**: No access to purchases or supplier costs.
- **Offline Behavior**: Purchase bills can be staged offline and synced.
- **Printing & Receipts**: Purchase invoice / Goods Received Note (GRN) printing.
- **Reporting Relevance**: Purchase summary report, supplier price trend history, and unpaid supplier bills.
- **Pakistani Retail Relevance**: Handles Pakistani wholesale bilti, mazdoori / carriage freight expense allocation.

### Acceptance & Success Criteria
- **Acceptance Criteria**: Bill with PKR 10,000 items + PKR 500 freight distributes PKR 500 across item line costs and increments branch stock and supplier debt accordingly.
- **Measurable Success Condition**: 100% parity between purchase line items and stock movement records.

---

## [REQ-0020] Supplier Payables Ledger, Supplier Payments & Supplier Returns

- **Primary Domain**: `SUPPLIERS`
- **Business Problem**: Store owner needs to pay suppliers from cash drawer or bank accounts, track outstanding supplier debts, and return damaged goods for balance credit.
- **Plain Language Requirement**: Manage supplier payables ledger, record partial/full supplier payments from bank/cash accounts, and return goods to suppliers with debt reduction and stock decrement.
- **Source Question Coverage (14 questions)**: `SQ-076`, `SQ-077`, `SQ-078`, `SQ-079`, `SQ-080`, `SQ-081`, `SQ-082`, `SQ-083`, `SQ-084`, `MQ-086`, `MQ-087`, `MQ-088`, `MQ-089`, `MQ-090`
- **Business Shapes**: `BOTH`
- **Target Module**: `SUPPLIERS`
- **Actors**: `BUSINESS_OWNER`
- **Priority**: `P0_MUST_WORK_TO_RUN_STORE`
- **Launch Criticality**: `LAUNCH_BLOCKER`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `MEDIUM`
- **Primary Phase**: `Phase 5`
- **Secondary Phases**: `Phase 6`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Supplier balance reflects single shop procurements.
- **Multi-Branch Behavior**: Centralized supplier ledger with payments attributed to specific financial accounts.
- **Business Owner Role**: Sole authority to record supplier payments and approve supplier returns.
- **Branch Manager Role**: Can view supplier contact; cannot disburse supplier payments without owner authority.
- **Cashier / Salesperson Role**: No access.
- **Offline Behavior**: Queues supplier payment for sync.
- **Printing & Receipts**: Supplier payment voucher with payment details and remaining debt.
- **Reporting Relevance**: Total supplier payables aging report and supplier payment ledger.
- **Pakistani Retail Relevance**: Supports payment via Cash, Cheque, Bank Transfer, JazzCash/Easypaisa to suppliers.

### Acceptance & Success Criteria
- **Acceptance Criteria**: Recording PKR 20,000 payment to supplier with PKR 50,000 balance reduces payable to PKR 30,000 and logs PKR 20,000 outflow in selected bank/cash account.
- **Measurable Success Condition**: Zero balance discrepancy between supplier payments and financial account ledger.

---

## [REQ-0021] Supplier Intelligence: Last Purchase Price, Price Change History & Supplier Comparison

- **Primary Domain**: `SUPPLIERS`
- **Business Problem**: Wholesalers frequently change commodity prices; store owners need to know what they paid last time, detect supplier price hikes, and compare rates across suppliers.
- **Plain Language Requirement**: Track historical purchase prices per supplier, highlight price increase/decrease on purchase entry, and compare supplier quotes and purchasing terms for the same product.
- **Source Question Coverage (8 questions)**: `SQ-071`, `SQ-072`, `SQ-073`, `MQ-091`, `MQ-092`, `MQ-093`, `MQ-094`, `MQ-095`
- **Business Shapes**: `BOTH`
- **Target Module**: `PURCHASES`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER`
- **Priority**: `P2_IMPORTANT_MANAGEMENT`
- **Launch Criticality**: `POST_LAUNCH_SAFE`
- **V1 Scope Classification**: `V1_SIMPLIFIED`
- **Complexity**: `LOW`
- **Primary Phase**: `Phase 5`
- **Secondary Phases**: `Phase 6`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Supplier price history for single store.
- **Multi-Branch Behavior**: Consolidated supplier purchasing intelligence across all branches.
- **Business Owner Role**: Full access to price variance reports and supplier comparisons.
- **Branch Manager Role**: Views last purchase price when receiving goods.
- **Cashier / Salesperson Role**: No access.
- **Offline Behavior**: Last purchase prices cached in local database.
- **Printing & Receipts**: Supplier price comparison summary export.
- **Reporting Relevance**: Product cost price trend graph over 30/60/90 days.
- **Pakistani Retail Relevance**: Empowers Pakistani retailers to negotiate effectively with wholesale market agents.

### Acceptance & Success Criteria
- **Acceptance Criteria**: When creating a purchase bill, entering Product A automatically displays "Last purchase price from Supplier X: PKR 250 on 12-Sep (+5% vs prior)".
- **Measurable Success Condition**: 100% accurate historical cost tracking per supplier and SKU.

---

## [REQ-0022] Daily Store Expense Recording, Categories & Monthly Budgets

- **Primary Domain**: `EXPENSES`
- **Business Problem**: Retail shops pay daily miscellaneous expenses (tea, electricity, rent, staff lunch, transport) and need to categorize them, attribute to financial accounts, and track monthly spending.
- **Plain Language Requirement**: Record daily store expenses with category, description, payment source (Cash Drawer, Bank, Wallet), date, and optional monthly category budget warning.
- **Source Question Coverage (11 questions)**: `SQ-085`, `SQ-086`, `SQ-087`, `SQ-088`, `SQ-089`, `SQ-090`, `MQ-096`, `MQ-097`, `MQ-098`, `MQ-099`, `MQ-100`
- **Business Shapes**: `BOTH`
- **Target Module**: `EXPENSES`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER`
- **Priority**: `P1_CORE_DAILY_OPERATION`
- **Launch Criticality**: `LAUNCH_REQUIRED`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `LOW`
- **Primary Phase**: `Phase 6`
- **Secondary Phases**: `Phase 4`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Records expense for single store.
- **Multi-Branch Behavior**: Attributes expense to specific branch or general business overhead.
- **Business Owner Role**: Full expense management, budget creation, and spending audit.
- **Branch Manager Role**: Records daily branch expenses from petty cash.
- **Cashier / Salesperson Role**: No access, or petty cash expense recording if explicitly allowed.
- **Offline Behavior**: Expenses can be recorded offline and synced.
- **Printing & Receipts**: Expense payment receipt voucher.
- **Reporting Relevance**: Monthly expense breakdown by category and expense-to-sales ratio.
- **Pakistani Retail Relevance**: Pre-loaded with common Pakistani retail categories (Chai/Pani, Bijli Bill, Kiraya, Mazdoori, Safai).

### Acceptance & Success Criteria
- **Acceptance Criteria**: Recording PKR 200 for "Tea & Refreshment" deducts PKR 200 from active cash drawer and adds PKR 200 to Daily Expenses total.
- **Measurable Success Condition**: Zero unaccounted cash drainage from petty store expenses.

---

## [REQ-0023] Multi-Account Treasury Management (Cash Drawer, Banks, Wallets) & Fund Transfers

- **Primary Domain**: `FINANCIAL_ACCOUNTS`
- **Business Problem**: Store owner receives money across various bank accounts, physical cash drawers, and mobile wallets (JazzCash, Easypaisa, Raast) and needs to track balances and transfer funds between them.
- **Plain Language Requirement**: Manage financial accounts (Cash, Bank, Mobile Wallets), view live running balances, record inter-account fund transfers (e.g. Cash to Bank deposit), and log owner capital injections/drawings.
- **Source Question Coverage (10 questions)**: `SQ-091`, `SQ-092`, `SQ-093`, `SQ-094`, `SQ-095`, `MQ-101`, `MQ-102`, `MQ-103`, `MQ-104`, `MQ-105`
- **Business Shapes**: `BOTH`
- **Target Module**: `FINANCIAL_ACCOUNTS`
- **Actors**: `BUSINESS_OWNER`
- **Priority**: `P1_CORE_DAILY_OPERATION`
- **Launch Criticality**: `LAUNCH_REQUIRED`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `MEDIUM`
- **Primary Phase**: `Phase 6`
- **Secondary Phases**: `Phase 2`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Single shop treasury management.
- **Multi-Branch Behavior**: Central treasury with branch cash drawers linked to central bank accounts.
- **Business Owner Role**: Full control over financial accounts, bank reconciliations, and fund transfers.
- **Branch Manager Role**: Views branch cash drawer balance only.
- **Cashier / Salesperson Role**: No access to financial accounts.
- **Offline Behavior**: Local cached account balances.
- **Printing & Receipts**: Account statement ledger export.
- **Reporting Relevance**: Total Liquidity & Cash-on-Hand summary report across all accounts.
- **Pakistani Retail Relevance**: Supports Pakistani commercial banks (Meezan, HBL, Alfalah) and digital wallets (JazzCash, Easypaisa, Nayapay, Sadapay).

### Acceptance & Success Criteria
- **Acceptance Criteria**: Transferring PKR 50,000 from Main Cash Drawer to Meezan Bank deducts PKR 50,000 from Cash and credits PKR 50,000 to Meezan Bank atomically.
- **Measurable Success Condition**: Zero money loss or unbalanced account transfers across all financial entities.

---

## [REQ-0024] Cash Register Day-Open & Shift Session Tracking

- **Primary Domain**: `DAILY_CLOSE`
- **Business Problem**: Store counters start the day with opening float cash and need to track cashier shifts, opening balances, and active session duration.
- **Plain Language Requirement**: Start counter shift with opening cash float amount, log active cashier session, track cash drawer additions/withdrawals during the shift, and ensure shift accountability.
- **Source Question Coverage (6 questions)**: `SQ-096`, `SQ-097`, `SQ-098`, `MQ-106`, `MQ-107`, `MQ-108`
- **Business Shapes**: `BOTH`
- **Target Module**: `DAILY_CLOSE`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER, SALESPERSON`
- **Priority**: `P0_MUST_WORK_TO_RUN_STORE`
- **Launch Criticality**: `LAUNCH_BLOCKER`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `LOW`
- **Primary Phase**: `Phase 6`
- **Secondary Phases**: `Phase 4`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Single store counter session.
- **Multi-Branch Behavior**: Branch specific register sessions for each active counter.
- **Business Owner Role**: Can view and supervise all active counter shifts across all branches.
- **Branch Manager Role**: Opens and reviews shifts for assigned branch.
- **Cashier / Salesperson Role**: Enters opening cash float and starts selling.
- **Offline Behavior**: Shift session opened and stored in offline IndexedDB.
- **Printing & Receipts**: Shift opening chit with opening float and cashier name.
- **Reporting Relevance**: Shift duration and sales velocity per staff member.
- **Pakistani Retail Relevance**: Standard opening cash change float (Rezgari / Float) tracking.

### Acceptance & Success Criteria
- **Acceptance Criteria**: Cashier opens shift with PKR 2,000 opening float; system records session start and initializes expected cash at PKR 2,000.
- **Measurable Success Condition**: 100% session audit trail from shift open to day close.

---

## [REQ-0025] Day-End Cash Balancing, Drawer Reconciliation & Day Close

- **Primary Domain**: `DAILY_CLOSE`
- **Business Problem**: At end of day, shopkeepers count physical cash in drawer, compare against calculated expected cash (Sales + Open Float + Khata Inflow - Expenses - Refunds), and identify cash shortages or overages.
- **Plain Language Requirement**: Perform end-of-day register close: enter physical cash count, auto-calculate expected cash, compute shortage/excess difference, record closing notes, and lock the daily register session.
- **Source Question Coverage (14 questions)**: `SQ-099`, `SQ-100`, `SQ-101`, `SQ-102`, `SQ-103`, `SQ-104`, `SQ-105`, `MQ-109`, `MQ-110`, `MQ-111`, `MQ-112`, `MQ-113`, `MQ-114`, `MQ-115`
- **Business Shapes**: `BOTH`
- **Target Module**: `DAILY_CLOSE`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER, SALESPERSON`
- **Priority**: `P0_MUST_WORK_TO_RUN_STORE`
- **Launch Criticality**: `LAUNCH_BLOCKER`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `MEDIUM`
- **Primary Phase**: `Phase 6`
- **Secondary Phases**: `Phase 4, Phase 9`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Closes single shop daily hisab.
- **Multi-Branch Behavior**: Each branch closes independent register; owner reviews consolidated multi-branch closing summary.
- **Business Owner Role**: Reviews daily close across all stores, investigates cash shortages, signs off on day close.
- **Branch Manager Role**: Performs cash drawer count and closes branch session.
- **Cashier / Salesperson Role**: Enters physical cash count and submits for manager review.
- **Offline Behavior**: Closing record stored locally; synced to central server on reconnection.
- **Printing & Receipts**: Prints Daily Z-Report / Shift Close Summary chit on thermal printer.
- **Reporting Relevance**: Daily cash discrepancy history report tracking cashier accuracy over time.
- **Pakistani Retail Relevance**: Essential daily closing routine (Roznamcha / Hisab Kitab) for Pakistani shops.

### Acceptance & Success Criteria
- **Acceptance Criteria**: Expected cash is PKR 45,000; cashier enters physical count of PKR 44,800 -> System flags PKR 200 Shortage, prompts for note, and closes register with full audit log.
- **Measurable Success Condition**: Zero mathematical ambiguity in cash reconciliation formula across all shifts.

---

## [REQ-0026] Store Executive Dashboard & Real-Time Operational KPI Summary

- **Primary Domain**: `DASHBOARD`
- **Business Problem**: Store owner opening their phone/laptop needs an immediate 5-second snapshot of today's total sales, gross profit, cash in drawer, unpaid customer Udhaar, and low-stock warnings.
- **Plain Language Requirement**: Clean executive dashboard with real-time summary cards: Today's Sales, Net Profit Estimate, Drawer Cash, Total Khata Receivables, Total Supplier Payables, and Quick Action buttons.
- **Source Question Coverage (12 questions)**: `SQ-106`, `SQ-107`, `SQ-108`, `SQ-109`, `SQ-110`, `SQ-111`, `SQ-112`, `MQ-116`, `MQ-117`, `MQ-118`, `MQ-119`, `MQ-120`
- **Business Shapes**: `BOTH`
- **Target Module**: `DASHBOARD`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER`
- **Priority**: `P1_CORE_DAILY_OPERATION`
- **Launch Criticality**: `LAUNCH_REQUIRED`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `LOW`
- **Primary Phase**: `Phase 3`
- **Secondary Phases**: `Phase 6`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Displays single store KPIs.
- **Multi-Branch Behavior**: Displays consolidated business KPIs with branch filter dropdown.
- **Business Owner Role**: Full visibility into all business finances and branch comparative cards.
- **Branch Manager Role**: Displays assigned branch KPIs only; central financials hidden.
- **Cashier / Salesperson Role**: Redirected to POS counter; simplified shift summary only.
- **Offline Behavior**: Displays cached metrics with clear "Offline - Last synced at HH:MM" indicator.
- **Printing & Receipts**: Print dashboard summary snapshot.
- **Reporting Relevance**: Real-time aggregate data feed.
- **Pakistani Retail Relevance**: Designed for busy Pakistani retail owners who want answers in seconds without digging through menus.

### Acceptance & Success Criteria
- **Acceptance Criteria**: Dashboard loads in <300ms displaying accurate live counts for Today Sales, Profit, Cash, Khata, and Supplier Debts.
- **Measurable Success Condition**: 100% data consistency between dashboard summary cards and underlying ledger tables.

---

## [REQ-0027] Comprehensive Small Retail Reporting & Net Profit Calculation

- **Primary Domain**: `REPORTING`
- **Business Problem**: Store owner needs accurate business reports (Sales Report, Profit & Loss Statement, Expense Summary, Customer Ledger) to understand real net profit after subtracting all costs and expenses.
- **Plain Language Requirement**: Generate and export clean reports: Sales Summary, Itemized Profitability (Revenue - Cost of Goods - Operating Expenses = True Net Profit), Tax/Discount reports, with date range filters.
- **Source Question Coverage (13 questions)**: `SQ-113`, `SQ-114`, `SQ-115`, `SQ-116`, `SQ-117`, `SQ-118`, `SQ-119`, `SQ-120`, `MQ-121`, `MQ-122`, `MQ-123`, `MQ-124`, `MQ-125`
- **Business Shapes**: `BOTH`
- **Target Module**: `REPORTING`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER`
- **Priority**: `P1_CORE_DAILY_OPERATION`
- **Launch Criticality**: `LAUNCH_REQUIRED`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `MEDIUM`
- **Primary Phase**: `Phase 6`
- **Secondary Phases**: `Phase 3`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Reports on single store operations.
- **Multi-Branch Behavior**: Reports filterable by All Stores or specific branch.
- **Business Owner Role**: Full access to all reports, profit calculations, and historical data exports.
- **Branch Manager Role**: Can generate sales and stock reports for assigned branch; profit access subject to owner permission.
- **Cashier / Salesperson Role**: Can generate own shift sales summary only.
- **Offline Behavior**: Local cached reports available for recent 7 days.
- **Printing & Receipts**: Print formatted A4 and 80mm thermal summary reports.
- **Reporting Relevance**: Primary reporting engine for small retail operations.
- **Pakistani Retail Relevance**: Standard Pakistani fiscal year and monthly reporting filters with PKR currency.

### Acceptance & Success Criteria
- **Acceptance Criteria**: Generating Profit & Loss report for date range calculates Total Revenue (PKR 500,000) - Total COGS (PKR 380,000) = Gross Profit (PKR 120,000) - Expenses (PKR 35,000) = Net Profit (PKR 85,000).
- **Measurable Success Condition**: Zero mathematical discrepancy between P&L statement and individual transaction ledger rows.

---

## [REQ-0028] Multi-Branch Comparative Performance & Cross-Store KPI Drilldown

- **Primary Domain**: `REPORTING`
- **Business Problem**: Multi-branch retail owner needs to compare branches side-by-side (which store is making the most profit, which has highest expenses, which branch has high Khata risk) to make informed expansion and staffing decisions.
- **Plain Language Requirement**: Side-by-side branch comparison matrix showing Sales, Gross Profit, Expense Ratio, Customer Khata Exposure, Stock Valuation, and Cash-on-Hand per branch with drill-down capability.
- **Source Question Coverage (10 questions)**: `MQ-126`, `MQ-127`, `MQ-128`, `MQ-129`, `MQ-130`, `MQ-131`, `MQ-132`, `MQ-133`, `MQ-134`, `MQ-135`
- **Business Shapes**: `MULTI_BRANCH_ONLY`
- **Target Module**: `REPORTING`
- **Actors**: `BUSINESS_OWNER`
- **Priority**: `P1_CORE_DAILY_OPERATION`
- **Launch Criticality**: `LAUNCH_REQUIRED`
- **V1 Scope Classification**: `V1_SIMPLIFIED`
- **Complexity**: `MEDIUM`
- **Primary Phase**: `Phase 7`
- **Secondary Phases**: `Phase 6`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Hidden completely in single-store mode.
- **Multi-Branch Behavior**: Available in Reports / Branch Comparison.
- **Business Owner Role**: Full access to rank, compare, and drill down into any branch.
- **Branch Manager Role**: No access to other branches or comparison matrix.
- **Cashier / Salesperson Role**: No access.
- **Offline Behavior**: Cached multi-branch summary available on owner device.
- **Printing & Receipts**: Generates executive branch comparison summary report.
- **Reporting Relevance**: Top performing branch and lowest performing branch ranking.
- **Pakistani Retail Relevance**: Enables Pakistani entrepreneurs managing multiple shops to identify underperforming branches quickly.

### Acceptance & Success Criteria
- **Acceptance Criteria**: Branch comparison screen displays Branch A vs Branch B vs Branch C in a single comparative table with sorted rankings by revenue, margin, and credit risk.
- **Measurable Success Condition**: 100% mathematical match between sum of branch metrics and consolidated business total.

---

## [REQ-0029] Owner-to-Manager Operational Task Delegation, Notes & Resolution Status

- **Primary Domain**: `TASKS_ALERTS`
- **Business Problem**: Multi-shop owner visiting or reviewing a remote branch needs to assign specific tasks (e.g. "Do stock take on Drinks section", "Collect Khata from Customer Y", "Return damaged cartons") and track when manager resolves them.
- **Plain Language Requirement**: Super Owner can create operational tasks/notes assigned to a specific Branch Manager; Manager sees tasks on their dashboard, acknowledges, marks in-progress or resolved with notes, and Owner tracks status.
- **Source Question Coverage (6 questions)**: `MQ-027`, `MQ-146`, `MQ-147`, `MQ-148`, `MQ-149`, `MQ-150`
- **Business Shapes**: `MULTI_BRANCH_ONLY`
- **Target Module**: `TASKS_ALERTS`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER`
- **Priority**: `P2_IMPORTANT_MANAGEMENT`
- **Launch Criticality**: `POST_LAUNCH_SAFE`
- **V1 Scope Classification**: `V1_SIMPLIFIED`
- **Complexity**: `LOW`
- **Primary Phase**: `Phase 7`
- **Secondary Phases**: `Phase 3`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Hidden in single-store mode or used as personal owner reminder notebook.
- **Multi-Branch Behavior**: Full branch task delegation workflow enabled.
- **Business Owner Role**: Create tasks, assign to branch managers, set due dates, review resolution proof.
- **Branch Manager Role**: View branch task list, update status (Pending -> In Progress -> Completed), add resolution notes.
- **Cashier / Salesperson Role**: No access.
- **Offline Behavior**: Tasks cached locally on manager device.
- **Printing & Receipts**: Print daily branch task sheet.
- **Reporting Relevance**: Task completion rate per branch manager.
- **Pakistani Retail Relevance**: Addresses real-world supervision gap when Pakistani shop owners cannot physically be at all branches simultaneously.

### Acceptance & Success Criteria
- **Acceptance Criteria**: Owner creates task "Audit Rice shelf" for Branch B; Branch B Manager sees red task badge on login, clicks "Mark Resolved" with note "Counted 45 bags"; Owner sees task completed with timestamp.
- **Measurable Success Condition**: 100% audit tracking of task lifecycle and resolution status.

---

## [REQ-0030] Native Browser IndexedDB Offline POS & Idempotent Sync Queue

- **Primary Domain**: `OFFLINE`
- **Business Problem**: Frequent internet disconnections and power outages in Pakistan halt retail counters if software requires live server connectivity for checkout.
- **Plain Language Requirement**: Full offline POS checkout: client caches catalog and customer data in native browser IndexedDB, generates client-side UUIDs for sales, and background syncs transactions to server with idempotency keys upon reconnection.
- **Source Question Coverage (10 questions)**: `SQ-121`, `SQ-122`, `SQ-123`, `SQ-124`, `SQ-125`, `MQ-136`, `MQ-137`, `MQ-138`, `MQ-139`, `MQ-140`
- **Business Shapes**: `BOTH`
- **Target Module**: `POS`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER, SALESPERSON`
- **Priority**: `P0_MUST_WORK_TO_RUN_STORE`
- **Launch Criticality**: `LAUNCH_BLOCKER`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `HIGH`
- **Primary Phase**: `Phase 8`
- **Secondary Phases**: `Phase 4`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Full offline POS selling and local receipt printing.
- **Multi-Branch Behavior**: Full offline POS per branch counter; background sync updates central database when internet restores.
- **Business Owner Role**: Can sell offline and review sync queue status.
- **Branch Manager Role**: Can sell offline on branch counter.
- **Cashier / Salesperson Role**: Uninterrupted counter selling regardless of internet connectivity status.
- **Offline Behavior**: Core offline capability powering the entire POS counter.
- **Printing & Receipts**: Prints thermal receipts instantly while offline using local printer driver.
- **Reporting Relevance**: Pending sync badge displays count of unsynced offline transactions.
- **Pakistani Retail Relevance**: Absolute prerequisite for Pakistani retail operations facing regular load-shedding and ISP drops.

### Acceptance & Success Criteria
- **Acceptance Criteria**: Disconnecting Wi-Fi allows 10 consecutive POS sales to complete and print receipts; reconnecting Wi-Fi syncs all 10 sales to backend without data loss or duplicate IDs.
- **Measurable Success Condition**: Zero lost transactions and zero duplicate sales during offline-online network transitions.

---

## [REQ-0031] Pakistani Retail Thermal Printing (80mm/58mm), Branding & Barcode Labels

- **Primary Domain**: `PRINTING`
- **Business Problem**: Pakistani retail shops use standard 80mm/58mm thermal receipt printers for customer receipts, require custom store headers, phone numbers, return policy footers, and barcode label printing.
- **Plain Language Requirement**: Native thermal receipt printing (80mm and 58mm roll widths) with custom store name, branch address, contact numbers, return policy footer, and sticky barcode label printing for unlabelled items.
- **Source Question Coverage (19 questions)**: `SQ-126`, `SQ-127`, `SQ-128`, `SQ-129`, `SQ-130`, `SQ-131`, `SQ-132`, `SQ-133`, `SQ-134`, `SQ-135`, `SQ-136`, `SQ-137`, `SQ-138`, `SQ-139`, `MQ-141`, `MQ-142`, `MQ-143`, `MQ-144`, `MQ-145`
- **Business Shapes**: `BOTH`
- **Target Module**: `PRINTING`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER, SALESPERSON`
- **Priority**: `P1_CORE_DAILY_OPERATION`
- **Launch Criticality**: `LAUNCH_REQUIRED`
- **V1 Scope Classification**: `V1_REQUIRED`
- **Complexity**: `LOW`
- **Primary Phase**: `Phase 4`
- **Secondary Phases**: `Phase 8`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Prints single store receipt header and policy.
- **Multi-Branch Behavior**: Prints branch specific address, phone, and customized receipt footer per location.
- **Business Owner Role**: Configure receipt layout, store logo, header text, and return policy footer.
- **Branch Manager Role**: Can adjust local printer width (80mm vs 58mm) and print barcode labels.
- **Cashier / Salesperson Role**: Automatic receipt printing on sale completion; re-print last receipt button.
- **Offline Behavior**: Direct USB/Network thermal printing operates 100% offline.
- **Printing & Receipts**: Primary printing system for receipts, tokens, reports, and barcode stickers.
- **Reporting Relevance**: Printable receipt chits for shift close, daily summaries, and expense vouchers.
- **Pakistani Retail Relevance**: Standard Urdu/English receipt format, PKR currency symbol, and local return policy wording (e.g. "Khareeda hua maal 3 din mein wapas ho sakta hai").

### Acceptance & Success Criteria
- **Acceptance Criteria**: Completing a sale sends formatted 80mm/58mm thermal print job in <500ms with store header, itemized list, payment details, and policy footer; barcode print tool generates scannable label stickers.
- **Measurable Success Condition**: Zero misaligned columns or truncated receipt lines on standard 80mm/58mm printers.

---

## [REQ-0032] Material Business Action Audit Trail & Actor Attribution

- **Primary Domain**: `AUDIT_ACTIVITY`
- **Business Problem**: Store owner needs verifiable accountability for sensitive operational actions (price overrides, discounts, sale cancellations, physical stock adjustments, returns, debt waivers, supplier payments, cash drawer shortages) to prevent employee theft and identify operational mistakes.
- **Plain Language Requirement**: Record an immutable audit log for all material business events capturing the actual authenticated actor, their active role, store branch, timestamp, device identifier, and action payload, which remains permanently preserved even if staff members later change roles or deactivate.
- **Source Question Coverage (13 questions)**: `SQ-028`, `SQ-060`, `SQ-075`, `SQ-084`, `SQ-090`, `SQ-105`, `MQ-024`, `MQ-038`, `MQ-063`, `MQ-080`, `MQ-090`, `MQ-100`, `MQ-115`
- **Business Shapes**: `BOTH`
- **Target Module**: `USERS_AND_ACCESS`
- **Actors**: `BUSINESS_OWNER, BRANCH_MANAGER, SALESPERSON`
- **Priority**: `P1_CORE_DAILY_OPERATION`
- **Launch Criticality**: `LAUNCH_REQUIRED`
- **V1 Scope Classification**: `V1_SIMPLIFIED`
- **Complexity**: `MEDIUM`
- **Primary Phase**: `Phase 2`
- **Secondary Phases**: `Phase 6, Phase 9`
- **Final Verification Phase**: `Phase 10`

### Detailed Operational Behaviors
- **Single-Store Behavior**: Logs single shop actions and cashier overrides.
- **Multi-Branch Behavior**: Logs branch attributed actions and multi-branch configuration changes.
- **Business Owner Role**: Full access to search, filter, and inspect complete audit trail across all stores.
- **Branch Manager Role**: Can view audit history for assigned branch events.
- **Cashier / Salesperson Role**: No access to audit logs.
- **Offline Behavior**: Locally queued audit events synced alongside transaction payloads.
- **Printing & Receipts**: Print audit log activity summaries.
- **Reporting Relevance**: Filterable audit log report by date, actor, branch, and action type.
- **Pakistani Retail Relevance**: Critical anti-theft safeguard for Pakistani retail owners managing staff at remote branches.

### Acceptance & Success Criteria
- **Acceptance Criteria**: Cashier overrides price on Product X from PKR 500 to PKR 450 -> System logs activity_log record with actor=Cashier A, role=Salesperson, branch=Branch B, action=PRICE_OVERRIDE, old_value=500, new_value=450, timestamp=NOW; record remains viewable by Owner.
- **Measurable Success Condition**: 100% actor attribution across all sensitive transaction types with zero missing log events.

---

