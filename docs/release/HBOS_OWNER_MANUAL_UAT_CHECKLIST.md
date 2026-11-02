# SKYRASOFT HBOS — PRODUCT OWNER HARDWARE ACCEPTANCE RUNBOOK & UAT CHECKLIST
**Document Reference:** `docs/release/HBOS_OWNER_MANUAL_UAT_CHECKLIST.md`  
**Execution Platform:** Physical Windows 10/11 Desktop/Laptop Hardware  
**Structure:** Exactly 19 Operational Test Areas — Exactly 33 Sequenced Verification Items  
**Release Gate Status:** PENDING (Strictly awaiting Product Owner personal execution on physical hardware)  
**Production Decision:** PRODUCTION NO-GO  

---

## 1. GUIDANCE FOR THE PRODUCT OWNER

This manual UAT checklist is specifically written for the **Product Owner** to execute on their physical Windows laptop/PC. It requires **zero technical knowledge** of coding, databases, or terminal commands.

### Safe Testing Rules:
1. **Zero Disruption:** The application runs in a normal native window. You can minimize, move, resize, or close it at any time.
2. **Zero Data Pollution:** Testing in 'Try Demo' operates in an isolated sandbox. It never touches real accounts.
3. **Isolated Test Business:** When testing real registration, create a test account (e.g. *Karachi Test Retail*). Never use live production customer records.
4. **Evaluation:** For each item, record **[PASS]**, **[FAIL]**, or **[BLOCKED]** in the check box.

---

## 2. THE 19 OPERATIONAL TEST AREAS (33 VERIFICATION ITEMS)

### Area 1: Safe Launch, Taskbar Switching & Native Window Exit

#### [UAT-01-01] Initial Window Launch & Dimension Verification
- **How to Reach:** Double-click "HBOS POS.exe" in desktop/target/release
- **Action to Perform:** Launch app without pressing any keys
- **Input / Values:** `None`
- **Expected Visible Result:** Application opens centered on screen in a normal 1366x768 window with native Windows title bar, minimize, maximize, and close (X) buttons. No screen-trapping or forced fullscreen.
- **Expected Financial / Stock Effect:** None
- **Prerequisites:** Windows 10/11 x64 workstation
- **Safe Exit & Reset:** Keep window open for next test (No reset needed)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

#### [UAT-01-02] Window Manipulation & Taskbar Switching
- **How to Reach:** Active HBOS application window
- **Action to Perform:** Click Minimize button (_), then click taskbar icon to restore. Press Alt+Tab.
- **Input / Values:** `None`
- **Expected Visible Result:** Window minimizes to taskbar smoothly; clicking restores it. Alt+Tab cycles between windows without HBOS seizing focus.
- **Expected Financial / Stock Effect:** None
- **Prerequisites:** None
- **Safe Exit & Reset:** Restore window (No reset needed)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

#### [UAT-01-03] Safe Application Exit via Native Controls
- **How to Reach:** Welcome screen
- **Action to Perform:** Click top-right close (X) button or press Alt+F4
- **Input / Values:** `None`
- **Expected Visible Result:** Application closes cleanly. Process terminates in Task Manager without hanging or leaving orphaned WebView2 processes.
- **Expected Financial / Stock Effect:** None
- **Prerequisites:** None
- **Safe Exit & Reset:** Re-launch app for next tests (No reset needed)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

---

### Area 2: Three Welcome Choices (Log In, Create Business, Try Demo)

#### [UAT-02-01] Welcome Screen Visual Presentation & Choice Separation
- **How to Reach:** Launch application to /welcome
- **Action to Perform:** Inspect screen layout
- **Input / Values:** `None`
- **Expected Visible Result:** Three prominent cards: 1. Log In (teal), 2. Create My Business (blue), 3. Try Demo (purple). Connectivity indicator visible. In-app Exit button visible at top right.
- **Expected Financial / Stock Effect:** Zero accounts or sample records loaded on unauthenticated screen.
- **Prerequisites:** None
- **Safe Exit & Reset:** Remain on Welcome screen (No reset needed)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

#### [UAT-02-02] Navigation to Login and Back to Welcome
- **How to Reach:** Welcome screen
- **Action to Perform:** Click "Log In" card, then click "← Back to Welcome"
- **Input / Values:** `None`
- **Expected Visible Result:** Navigates to /login form with clean inputs. Clicking "Back to Welcome" immediately returns to /welcome.
- **Expected Financial / Stock Effect:** None
- **Prerequisites:** None
- **Safe Exit & Reset:** Remain on Welcome screen (No reset needed)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

#### [UAT-02-03] Navigation to Register and Back to Welcome
- **How to Reach:** Welcome screen
- **Action to Perform:** Click "Create My Business" card, then click "← Back to Welcome"
- **Input / Values:** `None`
- **Expected Visible Result:** Navigates to /register form. Clicking "Back to Welcome" immediately returns to /welcome.
- **Expected Financial / Stock Effect:** None
- **Prerequisites:** None
- **Safe Exit & Reset:** Remain on Welcome screen (No reset needed)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

---

### Area 3: Standard Single-Store Demo Experience

#### [UAT-03-01] Launch Single-Store Demo from Modal
- **How to Reach:** Welcome screen
- **Action to Perform:** Click "Try Demo", select "Single-Store Demo (Al-Madina General Store)", click "Launch Single-Store Demo →"
- **Input / Values:** `None`
- **Expected Visible Result:** Navigates to POS/Dashboard with sticky amber DEMO MODE banner at top indicating "Single Store Demo (Al-Madina General Store)". Fictional data disclaimer displayed.
- **Expected Financial / Stock Effect:** Opening float initialized to PKR 8,500.00. 15 products loaded. 4 Khata customers (PKR 12,400 receivables). 3 suppliers (PKR 45,000 payables).
- **Prerequisites:** None (uses client sandbox)
- **Safe Exit & Reset:** Stay in Demo mode (Reset button on banner available)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

#### [UAT-03-02] Single-Store Demo Cash Sale & Stock Decrement
- **How to Reach:** Demo POS counter (/pos)
- **Action to Perform:** Tap "Super Kernel Basmati Rice", enter 2 kg, click "Pay Cash", enter 700, click "Complete Sale"
- **Input / Values:** `Quantity: 2, Cash: 700`
- **Expected Visible Result:** Sale completes with invoice INV-DEMO-XXXX. Rice stock decreases from 100 kg to 98 kg on screen.
- **Expected Financial / Stock Effect:** Drawer cash increases from PKR 8,500 to PKR 9,200. Zero server requests sent.
- **Prerequisites:** Demo session active
- **Safe Exit & Reset:** Stay in Demo mode (Reset available)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

#### [UAT-03-03] Single-Store Demo Khata Credit Sale
- **How to Reach:** Demo POS counter (/pos)
- **Action to Perform:** Tap "Dal Chana Special", select customer "Tariq Mehmood", select "Khata" tender, click "Complete Sale"
- **Input / Values:** `Quantity: 1, Amount: 240`
- **Expected Visible Result:** Credit sale recorded without cash inflow. Tariq Mehmood balance increases from PKR 3,200 to PKR 3,440.
- **Expected Financial / Stock Effect:** Drawer cash remains PKR 9,200. Total receivables increase to PKR 12,640.
- **Prerequisites:** Demo session active
- **Safe Exit & Reset:** Stay in Demo mode (Reset available)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

#### [UAT-03-04] Single-Store Demo Reset Functionality
- **How to Reach:** Demo banner
- **Action to Perform:** Click "Reset Demo Data" on sticky banner
- **Input / Values:** `Confirm dialog`
- **Expected Visible Result:** Toast confirms "Demo data reset to initial template". Rice stock restored to 100 kg, Tariq Mehmood balance restored to PKR 3,200, drawer restored to PKR 8,500.
- **Expected Financial / Stock Effect:** Sandbox state deterministic match to manifest.
- **Prerequisites:** Demo session active
- **Safe Exit & Reset:** Click "Exit Demo" to return to Welcome (Complete)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

---

### Area 4: Standard Multi-Store Demo Experience

#### [UAT-04-01] Launch Multi-Store Demo from Modal
- **How to Reach:** Welcome screen
- **Action to Perform:** Click "Try Demo", select "Multi-Store Demo (Al-Madina Supermarket — 3 Branches)", click "Launch Multi-Store Demo →"
- **Input / Values:** `None`
- **Expected Visible Result:** Banner displays "Multi-Store Demo (Al-Madina Supermarket — 3 Branches)". Branch switcher visible in header.
- **Expected Financial / Stock Effect:** Three branches provisioned: Clifton Main, Saddar Express, Gulshan Outlet.
- **Prerequisites:** None
- **Safe Exit & Reset:** Stay in Multi-Store Demo (Reset available)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

#### [UAT-04-02] Multi-Branch In-Transit Stock Transfer Visibility
- **How to Reach:** Transfers view (/stock-transfers)
- **Action to Perform:** Navigate to Transfers
- **Input / Values:** `None`
- **Expected Visible Result:** Transfer #TRF-2026-001 visible in list: 5 units Sufi Sunflower Oil from Clifton Main to Saddar Express with status "In-Transit".
- **Expected Financial / Stock Effect:** Stock is deducted from Clifton Main and not yet added to Saddar until received.
- **Prerequisites:** Multi-Store demo active
- **Safe Exit & Reset:** Click "Exit Demo" to return to Welcome (No reset needed)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

---

### Area 5: Clean New Business Registration (Zero-Data Guarantee)

#### [UAT-05-01] Fresh Store Owner Registration
- **How to Reach:** Welcome screen -> Create My Business (/register)
- **Action to Perform:** Fill owner details and click "Create Account"
- **Input / Values:** `Name: Test Owner, Phone: 03001112233, Biz: Test Retail Store, Password: ValidSecurePass1!`
- **Expected Visible Result:** Registration succeeds with 201 Created. Lands on store setup wizard / dashboard.
- **Expected Financial / Stock Effect:** Exactly ZERO products, ZERO sales, ZERO customers, ZERO suppliers, ZERO expenses in the new business account.
- **Prerequisites:** Backend API reachable (e.g. local XAMPP/testing backend)
- **Safe Exit & Reset:** Stay logged in (Use test business)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

#### [UAT-05-02] Verify Clean Zero-State Catalog & Cash
- **How to Reach:** Products view (/inventory/products) and Dashboard (/dashboard)
- **Action to Perform:** Navigate to Products and Dashboard
- **Input / Values:** `None`
- **Expected Visible Result:** Products table shows "No products found". Dashboard shows PKR 0 sales, PKR 0 expenses, PKR 0 receivables.
- **Expected Financial / Stock Effect:** Zero records confirmed.
- **Prerequisites:** Fresh business logged in
- **Safe Exit & Reset:** Remain in fresh business (None)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

---

### Area 6: Existing-Account Login & Session Restoration

#### [UAT-06-01] Login with Valid Merchant Credentials
- **How to Reach:** Welcome screen -> Log In (/login)
- **Action to Perform:** Enter credentials and click "Sign In"
- **Input / Values:** `Email/Phone and Password`
- **Expected Visible Result:** Authenticates successfully, stores Bearer token in hbos_token, redirects to Dashboard.
- **Expected Financial / Stock Effect:** Loads genuine saved business history for the authenticated tenant.
- **Prerequisites:** Registered merchant account
- **Safe Exit & Reset:** Stay logged in (None)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

#### [UAT-06-02] Remembered Session Banner on Welcome Screen
- **How to Reach:** Log out or restart app with saved token
- **Action to Perform:** View Welcome screen
- **Input / Values:** `None`
- **Expected Visible Result:** Saved session banner appears at top: "Continue as [Owner Name] ([Store Name])" with "Resume Session →" button.
- **Expected Financial / Stock Effect:** No data leakage.
- **Prerequisites:** Prior logged in session
- **Safe Exit & Reset:** Resume session (None)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

---

### Area 7: Real POS Counter Operations

#### [UAT-07-01] POS Cash Sale with Exact Change Calculation
- **How to Reach:** POS Counter (/pos)
- **Action to Perform:** Add product to cart, click Pay, enter cash tendered greater than bill, click Complete
- **Input / Values:** `Bill: PKR 875, Tendered: PKR 1,000`
- **Expected Visible Result:** Change return displayed prominently: PKR 125. Receipt generation modal opens.
- **Expected Financial / Stock Effect:** Drawer cash increases by PKR 875. Stock decrements correctly.
- **Prerequisites:** Active drawer session and product
- **Safe Exit & Reset:** Return to POS (None)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

---

### Area 8: Products, Fractional Units & Stock Inventory

#### [UAT-08-01] Create Fractional Loose Product (e.g. Rice/Sugar)
- **How to Reach:** Products view (/inventory/products)
- **Action to Perform:** Click "Add Product", enter name, unit "kg", check "Allow Fractional", enter stock 10.500
- **Input / Values:** `Name: Basmati Rice, Unit: kg, Stock: 10.500`
- **Expected Visible Result:** Product created with 3 decimal precision. Stock displayed as 10.500 kg.
- **Expected Financial / Stock Effect:** Initial inventory valuation calculated accurately.
- **Prerequisites:** Logged in as Owner
- **Safe Exit & Reset:** Return to products list (None)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

---

### Area 9: Purchasing, Landed Cost & Supplier Khata

#### [UAT-09-01] Purchase Bill with Partial Cash Payment
- **How to Reach:** Purchases view (/purchases)
- **Action to Perform:** Click "New Purchase", select supplier, add items (Total 2,200), paid 1,200 cash, due 1,000
- **Input / Values:** `Total: 2,200, Paid: 1,200 cash, Due: 1,000`
- **Expected Visible Result:** Purchase recorded as "Partial". Supplier payable shows PKR 1,000 due.
- **Expected Financial / Stock Effect:** Drawer cash decreases by PKR 1,200. Stock increments by purchased units.
- **Prerequisites:** Supplier created
- **Safe Exit & Reset:** Return to purchases (None)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

#### [UAT-09-02] Supplier Debt Settlement Payment
- **How to Reach:** Suppliers view (/suppliers)
- **Action to Perform:** Select supplier, click "Pay Supplier", enter PKR 1,000 cash, submit
- **Input / Values:** `Amount: 1,000 cash`
- **Expected Visible Result:** Payment recorded. Supplier payable drops to PKR 0.00.
- **Expected Financial / Stock Effect:** Drawer cash decreases by PKR 1,000.
- **Prerequisites:** Supplier with balance
- **Safe Exit & Reset:** Return to suppliers (None)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

---

### Area 10: Customer Khata (Udhaar) Credit & Collections

#### [UAT-10-01] POS Credit Sale with Limit Check
- **How to Reach:** POS Counter (/pos)
- **Action to Perform:** Select customer with credit limit PKR 5,000, add item PKR 700, tender Khata, complete
- **Input / Values:** `Amount: 700 on Khata`
- **Expected Visible Result:** Sale completes. Customer Khata ledger reflects +PKR 700 debt.
- **Expected Financial / Stock Effect:** Zero cash inflow. Total receivables increase by PKR 700.
- **Prerequisites:** Customer created
- **Safe Exit & Reset:** Return to POS (None)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

#### [UAT-10-02] Customer Cash Debt Repayment Collection
- **How to Reach:** Customer profile (/customers/:id)
- **Action to Perform:** Click "Record Payment", enter PKR 500 cash, click Save
- **Input / Values:** `Amount: 500 cash`
- **Expected Visible Result:** Customer balance decreases from PKR 700 to PKR 200. Receipt issued.
- **Expected Financial / Stock Effect:** Drawer cash increases by PKR 500. Total receivables decrease by PKR 500.
- **Prerequisites:** Customer with balance
- **Safe Exit & Reset:** Return to customers (None)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

---

### Area 11: Returns, Exchanges & Damaged Stock

#### [UAT-11-01] Customer Return with Restock Condition Assessment
- **How to Reach:** Sales history (/sales)
- **Action to Perform:** Select completed cash sale, click "Return Items", select Restockable or Damaged, refund cash
- **Input / Values:** `Quantity returned: 1`
- **Expected Visible Result:** Return invoice generated. If Restockable, inventory increments; if Damaged, routed to damage quarantine.
- **Expected Financial / Stock Effect:** Drawer cash decrements by refund amount.
- **Prerequisites:** Completed sale exists
- **Safe Exit & Reset:** Return to sales list (None)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

---

### Area 12: Operating Expenses & Petty Cash

#### [UAT-12-01] Record Petty Expense from Cash Drawer
- **How to Reach:** Expenses view (/expenses)
- **Action to Perform:** Click "Add Expense", category "Refreshments", amount 300, payment "Cash Drawer", submit
- **Input / Values:** `Amount: 300, Desc: Tea for guests`
- **Expected Visible Result:** Expense appears in list. Dashboard Today's Expenses shows PKR 300.
- **Expected Financial / Stock Effect:** Drawer cash decrements by PKR 300. Net profit adjusts accordingly.
- **Prerequisites:** Cash drawer active
- **Safe Exit & Reset:** Return to expenses (None)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

---

### Area 13: Cash Register Day-End Balancing & Close

#### [UAT-13-01] End-of-Day Cash Count & Exact Balancing
- **How to Reach:** POS Counter -> Click "Day Close"
- **Action to Perform:** Count physical notes and coins, enter counted cash, click "Close Register"
- **Input / Values:** `Enter exact expected cash (e.g. PKR 3,875)`
- **Expected Visible Result:** System displays Expected Cash vs Counted Cash. Discrepancy shows 0.00 PKR. Shift locked with closed summary.
- **Expected Financial / Stock Effect:** Session marked closed. Cash account balanced without false adjustments.
- **Prerequisites:** Active open register session
- **Safe Exit & Reset:** Register closed (None)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

---

### Area 14: Offline Counter Preservation & Idempotent Sync

#### [UAT-14-01] Offline POS Sale Creation with Network Disconnected
- **How to Reach:** POS Counter (/pos)
- **Action to Perform:** Disconnect network / simulate offline, ring up sale, tender cash, complete sale
- **Input / Values:** `Amount: 500 cash`
- **Expected Visible Result:** Sale completes locally. Sync badge changes to amber with "1 pending transaction". Transaction stored in client IndexedDB outbox.
- **Expected Financial / Stock Effect:** Local drawer updated. Sale queued with client-side UUID.
- **Prerequisites:** Network disconnected
- **Safe Exit & Reset:** Remain in POS (None)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

#### [UAT-14-02] Automatic Idempotent Background Synchronization
- **How to Reach:** POS Counter
- **Action to Perform:** Reconnect network
- **Input / Values:** `None`
- **Expected Visible Result:** Sync coordinator detects connection, background uploads pending sale. Sync badge turns green "All synced".
- **Expected Financial / Stock Effect:** Backend processes transaction exactly once. Retry creates zero duplicate sales or stock deductions.
- **Prerequisites:** Network restored
- **Safe Exit & Reset:** Return to dashboard (None)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

---

### Area 15: Multi-Branch Roles, Permissions & Inter-Branch Stock Transfers

#### [UAT-15-01] Cashier Role Restriction Enforcement
- **How to Reach:** Log in as Cashier account
- **Action to Perform:** Attempt to open /reports or /settings/users
- **Input / Values:** `None`
- **Expected Visible Result:** Access denied with clear 403 Forbidden feedback or menu links hidden. Cashier restricted to POS counter.
- **Expected Financial / Stock Effect:** Financial profit metrics protected from cashier viewing.
- **Prerequisites:** Cashier account created
- **Safe Exit & Reset:** Log out (None)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

#### [UAT-15-02] Inter-Branch Stock Transfer Dispatch & Receive
- **How to Reach:** Stock Transfers view (/stock-transfers)
- **Action to Perform:** Create transfer: Main Store to Branch 2, 5 units. Click Dispatch. Switch to Branch 2, click Receive.
- **Input / Values:** `Quantity: 5`
- **Expected Visible Result:** Dispatch marks items In-Transit. Receive adds items to Branch 2 stock.
- **Expected Financial / Stock Effect:** Total company stock strictly conserved (Main decreases by 5, Branch 2 increases by 5).
- **Prerequisites:** Multi-branch setup
- **Safe Exit & Reset:** Return to transfers (None)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

---

### Area 16: Executive Reporting & Mathematical Truth

#### [UAT-16-01] Profit & Loss Statement Reconciliation
- **How to Reach:** Reports view (/reports/profitability)
- **Action to Perform:** Select Today date range, view Net Sales, COGS, Gross Profit, Expenses, Net Profit
- **Input / Values:** `Date: Today`
- **Expected Visible Result:** All figures reconcile: Net Sales - COGS = Gross Profit. Gross Profit - Operating Expenses = True Net Profit.
- **Expected Financial / Stock Effect:** Mathematical exact match across all cards and sub-ledgers.
- **Prerequisites:** Completed retail day transactions
- **Safe Exit & Reset:** Return to reports (None)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

---

### Area 17: Safe App Restart, Update & Data Recovery

#### [UAT-17-01] Exit Guard with Pending Offline Outbox
- **How to Reach:** App with pending offline transaction
- **Action to Perform:** Click in-app Exit button
- **Input / Values:** `None`
- **Expected Visible Result:** Warning prompt appears: "You have unsynced offline transactions. Exiting now may delay server synchronization. Do you still want to exit?"
- **Expected Financial / Stock Effect:** Pending IndexedDB queue preserved across application restart.
- **Prerequisites:** Pending outbox count > 0
- **Safe Exit & Reset:** Cancel exit or force exit (None)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

---

### Area 18: Printing & Hardware Integration (Where Available)

#### [UAT-18-01] Thermal Receipt Preview & Printing (80mm/58mm)
- **How to Reach:** POS sale complete modal or Settings / Preferences
- **Action to Perform:** Click "Print Receipt"
- **Input / Values:** `Select 80mm or 58mm`
- **Expected Visible Result:** Clean receipt preview rendered with Urdu/English store name, items, tax, total, return policy footer.
- **Expected Financial / Stock Effect:** None
- **Prerequisites:** Physical printer or PDF printer
- **Safe Exit & Reset:** Close print dialog (None)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

---

### Area 19: Final Release Identity & Owner Sign-Off

#### [UAT-19-01] Release Binary Identity & Version Verification
- **How to Reach:** Settings -> About HBOS (/about)
- **Action to Perform:** View Application Information card
- **Input / Values:** `None`
- **Expected Visible Result:** Displays "SkyraSoft HBOS v1.0.0", Build identifier HBOS-V1.0.0-STAGE3-20261024-WIN64, Status: Release Candidate for Owner UAT.
- **Expected Financial / Stock Effect:** None
- **Prerequisites:** None
- **Safe Exit & Reset:** Ready for formal owner sign-off (None)
- **Result:** `[ ] PASS   [ ] FAIL   [ ] BLOCKED`
- **Notes / Evidence:** ___________________________________________________

---

## 3. FORMAL ACCEPTANCE SIGN-OFF

Once all 33 items across all 19 areas have been evaluated on physical hardware by the Product Owner:

- **Product Owner Name:** ___________________________________
- **Testing Date:** ___________________________________
- **Device Model & OS:** Windows 10/11 x64 (___________________)
- **Total Passed:** _____ / 33
- **Total Failed:** _____ / 33
- **Final Release Verdict:** `[ ] ACCEPTED FOR PILOT STORE RELEASE    [ ] REJECTED (REMEDIATION REQUIRED)`
- **Signature:** ___________________________________
