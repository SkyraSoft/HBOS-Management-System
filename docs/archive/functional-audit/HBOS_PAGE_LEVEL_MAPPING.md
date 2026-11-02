# HBOS Management System - Exhaustive Page, Tab, Section, Content Block & UI Elements Map

This document is the **definitive, exhaustive architectural and UI/UX map** of the entire HBOS Enterprise Suite. It catalogues every page, internal tab, sub-view, content block, metric card, data table, column filter, form input, modal popup, banner, and quick action down to the smallest interactive element, alongside its backend Laravel API endpoints and database services.

---

## 1. Web & Download Portal (Backend Rendered)

### Route: `/` & `/download.html` (`public/download.html`)
- **Top Navigation Bar:**
  - SkyraSoft HBOS Logo & Brand Title.
  - "System Status: Live / Operational" Indicator Pill.
  - "API Documentation" link (`https://hbos.skyrasoft.com/api/v1/health`).
- **Hero Banner Block:**
  - Main Headline: *"HBOS Enterprise Suite - Authoritative Retail Management & Point of Sale"*.
  - Sub-headline & release version badge (`v1.0.0 Stable Release`).
  - Primary Download CTAs:
    - **Windows Desktop Installer Button** (`/downloads/HBOS-Setup-x64.exe`): Initiates 64-bit Tauri/Rust Windows executable download.
    - **Android Mobile APK Button** (`/downloads/HBOS-Mobile-POS.apk`): Initiates Capacitor Android app APK download.
- **Feature Highlights Grid:**
  - 1. *100% Offline Resilience (IndexedDB & SQLite engine)*.
  - 2. *High-Speed Thermal Receipt & GRN Voucher Printing*.
  - 3. *Double-Entry Ledgers & Khata Reconciliation*.
  - 4. *P2P QR Device Sync (`hbos-pair://` protocol)*.
- **Footer Section:**
  - Copyright notices, SkyraSoft Engineering info, server timestamp.

---

## 2. Authentication & Onboarding Flow

### 2.1 Route: `/login` (`LoginView.vue`)
- **Layout:** `auth` (Clean centered card layout).
- **Branding Header Block:** HBOS brand icon, system title, subtitle *"Sign in to your business terminal"*.
- **Alert Banner Block:** Error banner (`v-if="errorMessage"`) with dismissal button.
- **Login Form Block:**
  - Username or Email text input (`v-model="loginForm.username"`).
  - Password input (`v-model="loginForm.password"`) with eye toggle to show/hide plaintext.
  - "Remember this terminal" checkbox.
  - "Forgot Password?" hyperlink.
  - "Sign In" Primary CTA button (`handleLogin`) with loading spinner.
- **Footer Links Block:** "Don't have a business account? Register Here" redirect link.
- **API Endpoint:** `POST /api/v1/auth/login` (`AuthController@login`).

---

### 2.2 Route: `/register` (`RegisterView.vue`)
- **Layout:** `auth`.
- **Header Block:** System logo, *"Register New Business & Master Owner Account"*.
- **Tenant Registration Form Block:**
  - Owner Full Name input.
  - Email Address input.
  - Mobile Phone Number input.
  - Business / Store Name input.
  - Business Category selector (Grocery, Pharmacy, General Retail, Apparel, Electronics, Wholesale).
  - Master Password & Confirm Password inputs.
  - Terms & Privacy Agreement checkbox.
  - "Create Business Account" button (`handleRegister`).
- **API Endpoint:** `POST /api/v1/auth/register` (`AuthController@register`).

---

### 2.3 Route: `/busniesssetup`, `/businesssetup1`, `/completereview`, `/completesetup`
- **Step 1 (`BusniessSetupView.vue`):** Business identity, base currency (PKR/USD), Tax/NTN number, address, contact phone.
- **Step 2 (`BusinessSetup1View.vue`):** Branch name (e.g. "Main Branch"), Branch code, POS terminal register name.
- **Step 3 (`CompleteReviewView.vue`):** Read-only configuration review cards with "Edit" badges.
- **Step 4 (`CompleteSetupView.vue`):** Completion splash card, "Proceed to Dashboard" CTA button.
- **API Endpoints:** `POST /api/v1/businesses`, `POST /api/v1/branches`.

---

## 3. Executive Dashboard (`/` or `DashboardView.vue`)

### Top Header & Control Bar Block
- **Greeting & Role Badge:**
  - Role pill (`Business Owner`, `Branch Manager`, `Salesperson`).
  - Active branch scope label (`• All Branches` or `• Main Branch`).
  - Greeting header: *"Good Day, {User Name}"*.
  - Sub-caption: *"Authoritative operational performance overview."*
- **Branch Scope Selector Dropdown** (`isOwner` only): Options: "All Branches", plus dynamic list of store branches.
- **Date Range Preset Selector:**
  - Options: `Today`, `Yesterday`, `Last 7 Days`, `This Month` (default), `Last Month`, `Last 30 Days`, `Custom Range...`.
  - Custom Date Range Picker Block (`v-if="dateFilterPreset === 'custom'"`): Start Date input, "to" label, End Date input.
- **Quick Action CTA:** "New Sale" button (`bi-cart-plus`) routing directly to `/POS?tab=sales`.

### Alert & Warning Banner Block (`v-if="needsAttention.length > 0"`)
- **Card Header:** Warning icon, *"Needs Your Attention (Count)"*, *"Action Required"* badge.
- **Attention List Group Items:**
  - Alert title & descriptive message.
  - Severity badge: High (`danger-subtle`), Medium (`warning-subtle`).
  - Common alert types: Low Stock Thresholds, Overdue Customer Receivables, Pending Sync Queue.

### Dynamic KPI Metric Cards Grid
- **Salesperson View:**
  1. *Personal Net Sales* (PKR sum + transaction count).
  2. *Return Adjustment* (Returns in period).
  3. *Customers Served* (Unique customer count).
- **Branch Manager View:**
  1. *Branch Net Sales* (Total PKR + order count).
  2. *Branch Expenses* (Posted total).
  3. *Cash Drawer Position* (Physical cash on hand).
  4. *Customer Exposure* (Receivables).
  5. *Low Stock Alert Count*.
  6. *Out of Stock Count*.
- **Business Owner View (8 Enterprise KPI Cards):**
  1. *Net Sales* (Net sales + Gross sales subtext).
  2. *Gross Profit* (PKR amount or "Partial" indicator if uncosted sales exist).
  3. *Operating Position* (Gross Profit − Expenses).
  4. *Posted Expenses* (Total operational overhead).
  5. *Cash & Bank Funds* (Total tracked liquidity + Cash on hand subtext).
  6. *Outstanding Receivables* (Customer credit debt).
  7. *Supplier Payables* (Reconciled vendor liability).
  8. *Stock Asset Valuation* (Total inventory value at current cost + Low/Out of stock counts).

### Operational Performance Tables Block (`isOwner || isManager`)
- **Block 1: Top Selling Products Card:**
  - Header: Trophy icon, *"Top Selling Products"*, *"Return-Adjusted"* badge.
  - Empty State: *"No sales recorded for this period."*
  - Data Table: Columns for `Product Name`, `Units Sold` (badge), `Net Sales` (PKR formatted).
- **Block 2: Slow Moving Products Card:**
  - Header: Hourglass icon, *"Slow Moving Products"*, *"Stock > 0 & Zero Net Sales"* badge.
  - Empty State: *"No slow movers detected in period."*
  - Data Table: Columns for `Product Name`, `SKU`, `Stock On Hand` (red alert text).
- **Block 3: Branch Performance Comparison Card (`isOwner` only):**
  - Header: Building icon, *"Branch Performance Comparison"*.
  - Data Table: Columns for `Branch Name`, `Net Sales`, `Transactions` (badge), `Gross Profit`, `Expenses`, `Cash Position`.
- **Block 4: Recent Operational Activity Stream Card:**
  - Header: Activity icon, *"Recent Operational Activity"* (or *"Your Recent Sales"* for cashiers).
  - Empty State: *"No recent activity recorded."*
  - Data Table: Columns for `Reference / Invoice #`, `Type` (Sale, Return, Expense), `Date / Time`, `Amount` (PKR), `Status` badge (`completed`, `posted`).

---

## 4. Master Inventory, Products & Catalog (`/products` or `InventryView.vue`)

### Top Header & Navigation Bar
- **Tab Header Label:** Directory / Partners / Master Control pill.
- **Main Title:** Dynamic: *"Inventory & Product Hub"* / *"Categories"* / *"Brands"*.
- **Sub-description:** Contextual guidance text.
- **"Add New Product" CTA Button** (`activeTab === 'products'`): Triggers `openAddProductModal`.

---

### Tab 1: `products` (Live Store Inventory)

#### The 9 Master KPI Metric Cards Grid:
1. **Total Products:** Count + unique SKU subtitle.
2. **Stock on Hand:** Total physical units count.
3. **In Stock:** Active items count with healthy stock levels.
4. **Low Stock Alert:** Items at or below minimum reorder threshold.
5. **Out of Stock Alert:** Items with 0 inventory.
6. **Asset Valuation:** Total inventory cost valuation (PKR sum).
7. **Retail Potential Value:** Expected sales revenue valuation (PKR sum).
8. **Active Categories:** Count of parent & sub-categories.
9. **Partner Brands:** Count of registered manufacturer brands.

#### Master Inventory Table Card:
- **Table Control Bar:**
  - Title: *"Master Inventory Ledger"*.
  - Total Items Shown Badge: `{{ filteredProducts.length }} items shown`.
  - Sort Dropdown: `Name (A-Z)`, `Name (Z-A)`, `Price (High to Low)`, `Price (Low to High)`, `Stock (High to Low)`, `Stock (Low to High)`.
  - "Clear Filters" Button (`v-if="hasActiveFilters"`).
- **Inline Column Header Filters Row:**
  - *Product Name & SKU Filter*: Search text box (`colFilters.nameOrSku`).
  - *Category Filter*: Dropdown with optgroups for Parent & Sub-categories.
  - *Brand Filter*: Dropdown for registered partner brands.
  - *Cost (PKR) Filter*: Min Cost number input.
  - *Retail Price Filter*: Min Retail price number input.
  - *Margin % Filter*: Dropdown: `All Margins`, `≥ 50% Profit`, `≥ 30% Profit`, `< 15% Profit`.
  - *Stock / Units Filter*: Min Stock level number input.
  - *Stock Status Filter*: Dropdown: `All`, `In Stock`, `Low Stock`, `Out of Stock`.
  - *Actions Column Header*: Clean header with alignment.
- **Table Body Columns:**
  - 1. **Product Name & SKU:** Product title, SKU badge, Barcode subtext, thumbnail preview.
  - 2. **Category:** Category badge + Subcategory pill.
  - 3. **Brand:** Partner brand logo/name.
  - 4. **Cost (PKR):** Unit purchase cost.
  - 5. **Retail (PKR):** Unit selling price.
  - 6. **Margin %:** Computed gross margin percentage with color indicator.
  - 7. **Stock / Units:** Live on-hand quantity count with unit of measure.
  - 8. **Stock Status:** Status badge (`In Stock` green, `Low Stock` orange, `Out of Stock` red).
  - 9. **Actions Cell:**
    - Quick Restock button (`bi-plus-circle`) -> opens `showRestockModal`.
    - View Details button (`bi-eye`) -> opens `showDetailsModal`.
    - Edit Product button (`bi-pencil`) -> opens `showProductModal` (Edit mode).
    - Delete Product button (`bi-trash3` text-danger) -> confirmation prompt.
- **Table Empty State Block:** "No products match your filter criteria" + "Reset Filters" CTA.
- **Pagination Bar Block:** Items range indicator (`Showing X to Y of Z`), Items-per-page selector (`10`, `25`, `50`, `100`), Prev/Next page navigation.

---

### Tab 2: `categories` (Catalog Structure & Hierarchy)
- **Control Bar:** Search Categories input, "Add Category" Primary CTA button.
- **Category Hierarchy Table Card:**
  - Columns: `Category Name`, `Slug / Code`, `Parent Category`, `Total Assigned Products`, `Status` (Active/Inactive), `Actions`.
  - Actions: Edit Category, Delete Category (with integrity check), View Subcategories.
- **Subcategories Expansion Accordion Block:** Lists nested children subcategories with quick delete/edit pills.

---

### Tab 3: `brands` (Partner Brands Directory)
- **Control Bar:** Search Brands input, "Add Brand" Primary CTA button.
- **Brand Directory Grid / Table Card:**
  - Columns: `Brand Logo`, `Brand Name`, `Manufacturer Company`, `Total Active Products`, `Contact Info`, `Actions`.
  - Actions: Edit Brand, Delete Brand.

---

### Popups, Modals & Drawers in Inventory Module:
1. **Add / Edit Product Drawer Modal (`showProductModal`):**
   - *Header:* Title ("Add New Product" / "Edit Product"), Close `✕` button.
   - *Tab 1 (Basic Info):* Product Name (`*`), SKU (`*` with Auto-Generate button), Barcode (EAN-13), Category dropdown, Subcategory dropdown, Brand dropdown, Unit of Measure selector.
   - *Tab 2 (Pricing & Stock):* Cost Price (`*`), Retail Selling Price (`*`), Wholesale Price, Opening Stock Quantity, Minimum Reorder Threshold, Tax Rate % (GST/VAT).
   - *Tab 3 (Media & Notes):* Product Image upload preview, Description / Usage notes.
   - *Footer Actions:* "Cancel", "Save & Add Another", "Save Product".
2. **Quick Restock Modal (`showRestockModal`):**
   - *Header:* Title ("Quick Stock Adjustment"), Close button.
   - *Content:* Product Name display, Current Stock display, Adjustment Type selector (`Add Stock (+)` / `Deduct Stock (-)`), Adjustment Quantity input, Reason dropdown (`Restock`, `Damaged Goods`, `Expired`, `Inventory Audit Count`, `Internal Branch Transfer`), Unit Cost override.
   - *Footer Actions:* "Cancel", "Apply Stock Adjustment".
3. **Product Details Modal (`showDetailsModal`):**
   - *Content:* Product summary card, sales statistics, recent stock ledger movements (Timestamp, Movement Type, Qty Change, Ending Stock, Logged By User).
4. **Add / Edit Category Modal (`showCategoryModal`):**
   - *Fields:* Category Name (`*`), Parent Category selector (optional), Slug, Color theme, Status toggle.
5. **Add / Edit Brand Modal (`showBrandModal`):**
   - *Fields:* Brand Name (`*`), Company Name, Logo URL/Upload, Website/Phone.

---

## 5. Point of Sale (POS) Terminal (`/POS` or `PosSaleView.vue`)

### Top Header Bar
- Cashier name badge, Store branch badge, Online/Offline sync status dot, Fullscreen toggle (`bi-arrows-fullscreen`), Help / Keyboard shortcuts button (`?`).

### Left Panel: Product Catalog & Touch Grid
- **Live Search & Barcode Input Bar:**
  - Barcode scanner autofocus input box (`F2` shortcut).
  - Clear search `✕` button.
- **Category Filter Pills Bar:** Horizontal scrollable buttons (`All Products`, `Grocery`, `Beverages`, `Snacks`, `Household`, etc.).
- **Product Cards Touch Grid:**
  - Card elements: Product image/icon, Product Title, SKU, Retail Price (PKR), Stock on hand badge (turns red if low stock), Variant pill (`Single`, `Pack`, `Carton`).
  - Click Action: Adds 1 unit to cart with audio chime.

### Right Panel: Active Cart & Checkout Engine
- **Customer Selection Block:**
  - Customer Dropdown / Search (Defaults to "Walk-in Cash Customer").
  - "Quick Add Customer" button (`+`).
  - Active Customer Khata Warning Pill (reveals outstanding debt balance & credit limit).
- **Cart Items List Block:**
  - Cart Item Row elements:
    - Item Name & Barcode.
    - Pack variant switcher (`Single`, `Box`, `Carton`).
    - Unit Price input (with dynamic price override indicator).
    - Quantity Stepper (`−` decrease, numeric input, `+` increase).
    - Line item subtotal (PKR).
    - Delete Item button (`✕`).
- **Cart Empty State Block:** Cart icon, *"Cart is Empty"*, *"Scan barcode or click products on the left to start sale"*.
- **Order Summary Calculations Block:**
  - Subtotal amount display (PKR).
  - Discount Row: Discount mode selector (`%` or `PKR`), Discount value input.
  - Tax Row: Tax rate breakdown (`0% Exempt` or configured GST rate).
  - **Net Payable Grand Total Box:** High-contrast large PKR total display.
- **Cart Action Buttons Row:**
  - **"Park / Hold Order" Button (`F4`):** Suspends cart into IndexedDB memory.
  - **"Recall Order" Button:** Reveals suspended carts list modal.
  - **"Clear Cart" Button:** Resets active cart after confirmation.
  - **"Pay / Complete Sale" CTA Button (`F1`):** Large prominent button triggering payment modal.

### POS Popups, Modals & Print Dialogs:
1. **Payment Settlement Modal:**
   - *Payment Mode Selector:* Cash, Card (with Terminal Auth code input), Customer Khata / Credit Ledger, Split Payment (Partial Cash + Partial Card/Khata).
   - *Tendered Amount Input:* Number input for cash received.
   - *Quick Cash Recommendation Buttons:* `Exact Cash`, `+Rs. 500`, `+Rs. 1,000`, `+Rs. 5,000`.
   - *Change Due Box:* High-visibility green display showing calculated return change.
   - *Order Reference / Notes:* Optional text field.
   - *Footer Actions:* "Cancel (`ESC`)", "Confirm & Print Receipt (`Enter`)".
2. **Sale Success & Thermal Receipt Modal (`showInvoiceModal`):**
   - *Header:* Success checkmark animation, *"Transaction Completed Successfully"*.
   - *Invoice Summary Card:* Invoice #, Date/Time, Cashier name, Customer name, Item list table, Subtotal, Discount, Tax, Grand Total, Cash Tendered, Change Due.
   - *Actions Row:*
     - **"Print Receipt" (`printInvoice`):** Generates 58mm/80mm thermal receipt iframe.
     - **"Print A4 Invoice":** Full-page PDF invoice layout.
     - **"New Sale (`Enter` / `ESC`)":** Clears modal and returns to empty POS counter.
3. **Quick Add Customer Modal:**
   - Name (`*`), Mobile Phone (`*`), Credit Limit, Opening Balance.
4. **Keyboard Shortcuts Guide Modal (`?`):**
   - Reference table showing `F1` (Pay), `F2` (Search), `F4` (Hold), `F8` (Discount), `F9` (Reprint), `ESC` (Close).

---

## 6. Sales History & Returns Management (`/sales` & `SalesView.vue`)

### Control Bar & Filters
- Date Range Preset dropdown (`Today`, `This Week`, `This Month`, `Custom`).
- Cashier filter dropdown, Branch selector dropdown, Payment Method filter (`Cash`, `Card`, `Khata`).
- Invoice Search text input.
- "Export Sales CSV" button.

### Sales History Table Card
- **Table Columns:**
  - `Invoice #`, `Date & Time`, `Branch`, `Customer Name`, `Cashier`, `Payment Method`, `Total Items`, `Net Amount (PKR)`, `Paid Amount`, `Due Amount`, `Status` (`Completed`, `Returned`, `Cancelled`), `Actions`.
- **Row Actions:**
  - View Receipt / Details button.
  - Return / Refund Items button (`POST /api/v1/sales/{id}/returns`).
  - Void / Cancel Transaction button (Owner/Manager role with audit reason prompt).

### Popups & Modals:
1. **Invoice Details Modal:** Complete itemized receipt preview, payment transactions log, reprint action.
2. **Sale Return & Refund Modal:**
   - Items list with Return Quantity steppers.
   - "Restock to Inventory?" checkbox (Yes = adds back to stock, No = damaged/scrap).
   - Refund Method selector (`Cash Refund`, `Credit Customer Khata`, `Bank Transfer`).
   - Refund Reason text box.
   - "Confirm Return & Issue Refund" button (`SaleReturnRefundController@settleRefund`).

---

## 7. Purchasing & Supplier Payables (`/purchase` & `/supplierpayable`)

### 7.1 Purchasing Module (`/purchase` or `PurchaseView.vue`)
- **Top Bar:** "New Purchase Bill" title, "Purchase History" view toggle button.
- **Purchase Header Form Card:**
  - Supplier Selector dropdown (with "Add New Supplier" quick modal button).
  - Supplier Invoice / Bill # input.
  - Purchase Date picker.
  - Destination Branch selector.
- **Goods Received Items Table Card:**
  - Table Columns: `Product (Search/Barcode)`, `SKU`, `Quantity Received`, `Unit Purchase Cost (PKR)`, `Line Discount`, `Line Tax`, `Line Total (PKR)`, `Delete Row (✕)`.
  - "Add Product Line" button.
- **Billing & Settlement Summary Column Card:**
  - Items Subtotal display.
  - Shipping & Freight Charges input.
  - Total Bill Amount display.
  - Amount Paid Now input.
  - Balance Added to Supplier Payable display.
  - Payment Account selector (`Cash Register`, `Bank Account`).
  - "Confirm & Receive Inventory" CTA button (`confirmPurchase`).
  - "Print Purchase GRN Voucher" button (Generates formal A4 Goods Received Note).

---

### 7.2 Supplier Payables & Intelligence (`/supplierpayable` or `SupplierpayableView.vue`)
- **Internal Tabs:**
  1. `supplier-intelligence`:
     - *Payable Aging Breakdown Card:* Buckets for `Current (0-30 days)`, `Overdue (31-60 days)`, `Critical (61-90 days)`, `Default Risk (90+ days)`.
     - *Procurement Price Insights Card:* Price variance benchmarks.
     - *Total Outstanding Payables KPI Box*.
  2. `payables`:
     - *Active Supplier Payables Table:* Supplier Name, Phone, Total Invoices, Total Billed, Total Paid, Net Balance Due, Last Payment Date, "Settle Payment" button.
  3. `purchase-orders`:
     - *Purchase Orders History Ledger Table*.
  4. `supplier-performance`:
     - *Supplier Delivery & Accuracy Ratings Matrix*.
- **Popups & Modals:**
  - **Settle Supplier Payment Modal:** Payment Amount, Payment Date, Source Account (Cash/Bank), Cheque/Tx Reference, Note, "Confirm Payment" button.
  - **Add Supplier Modal (`showSupplierModal`):** Company Name, Contact Person, Phone, Email, Address, NTN/Tax ID, Opening Balance.

---

## 8. Customer Management & Khata Ledgers (`/customer` & `/khata`)

### 8.1 Customer Directory (`/customer` & `/profilecustomer`)
- **Control Bar:** Search Customers input, Filter by Balance (`All`, `Debtors (Khata)`, `Zero Balance`), "Record Payment" button, "Add Customer" Primary CTA button.
- **Customer Summary KPI Row:** Total Customers count, Total Outstanding Receivables (PKR), Active Credit Debtors count.
- **Customer Directory Table Card:**
  - Table Columns: `Customer Name`, `Mobile Phone`, `Address / City`, `Credit Limit (PKR)`, `Current Balance (PKR)` (red if debit, green if advance credit), `Status` badge, `Actions`.
  - Actions: Record Payment (`recordPaymentModal`), View Statement (`ProfilecustomerView.vue`), Edit Customer, Delete Customer.
- **Customer Profile & Ledger Statement (`/profilecustomer`):**
  - Customer Information Header card (Contact, Credit limit, Lifetime purchases).
  - Comprehensive Running Ledger Statement Table (Date, Reference, Description, Debit/Charges, Credit/Payments, Running Balance).
  - "Print Ledger Statement" button & "WhatsApp Statement" trigger.

---

### 8.2 Khata Master Ledger (`/khata` or `KhataView.vue`)
- **Control Bar:** Search customer by name/phone, Filter by Overdue / Exceeded Limit, "Record Khata Payment" button.
- **Khata Accounts Table Card:**
  - Table Columns: `Customer Name`, `Phone`, `Credit Limit`, `Current Debt (PKR)`, `Credit Limit Utilization Bar`, `Last Payment Date`, `Overdue Status`, `Actions`.
  - Row Actions:
    - **"Collect Payment" Button:** Opens `showPaymentModal`.
    - **"Send Reminder" Button:** Opens pre-formatted WhatsApp payment reminder.
    - **"View Ledger" Button:** Direct statement view.
- **Khata Settlement Modal (`showPaymentModal`):**
  - Customer selection, Current Debt display, Payment Amount input, Receiving Account dropdown (`Cash Drawer`, `Bank Account`, `EasyPaisa/JazzCash`), Payment Date, Receipt Reference, Notes, "Save Payment" button (`POST /api/v1/customers/{id}/payments`).

---

## 9. Financials, Petty Cash & Expenses (`/expenses` or `ExpensesView.vue`)

### Top Header & Alert Banners
- Success Alert banner (`v-if="successAlertMessage"`).
- Error Alert banner (`v-if="errorAlertMessage"`).
- Cost Control pill & main title.
- "Add Expense" button / "Back to Expense List" button.

---

### Tab 1: `overview` (Expense Ledger)
- **4 Expense KPI Cards Grid:**
  1. *Today's Expenses:* Today's PKR sum + active status.
  2. *This Month:* Monthly PKR sum + % of monthly budget.
  3. *Largest Category:* Top spending category name & total.
  4. *Total Recorded:* Lifetime expense sum.
- **Expense Filter Toolbar:**
  - Search description text box.
  - Category selector dropdown.
  - Payment Method dropdown (`Cash`, `Bank Transfer`, `Card`, `Cheque`).
  - Date Range picker.
  - "Reset Filters" button.
- **Expense Ledger Table Card:**
  - Table Columns: `Date`, `Expense ID`, `Category` (badge), `Description`, `Payment Method`, `Branch`, `Amount (PKR)` (bold red), `Reference #`, `Actions`.
  - Actions: Edit Expense (`openEditExpense`), Void Expense (`deleteExpense`), View Receipt Attachment.
  - Pagination Toolbar: Items count, page steppers.

---

### Tab 2: `add` (Dedicated Full-Page Expense Form)
- **Left Column: Form Card:**
  - Expense Description text input (`*`).
  - Category dropdown (`*`) with "+ New Category" button.
  - Amount input (`*`) in PKR.
  - Expense Date picker (`*`).
  - Payment Method selector (`Cash`, `Bank Transfer`, `Card`, `Cheque`) (`*`).
  - Reference / Receipt Number input.
  - Branch Allocation dropdown.
  - Receipt Image upload file input.
  - Additional Notes textarea.
  - Form Buttons: "Cancel", "Save Expense" (`isSubmitting` spinner).
- **Right Column: Live Preview & Budget Utilization Card:**
  - Dark Theme Expense Receipt Preview widget (Live updates with form inputs).
  - Monthly Budget Progress Bar widget (% utilized of monthly limit).

---

### Tab 3: `categories` (Expense Categories Management)
- **Control Bar:** "Add Expense Category" button.
- **Categories Table Card:** Category Name, Monthly Budget Cap, Total Spent This Month, Budget Health Bar, Actions (Edit, Delete).

---

### Tab 4: `reports` (Expense Analytics)
- Category breakdown pie chart, Month-over-month trend bar chart, "Export Expenses (CSV)" button.

---

## 10. Reports & Business Intelligence (`/reports` & `ReportsView.vue`)

### Navigation Pills Bar
- Tabs: `Sales Report`, `Inventory Report`, `Customer Khata`, `Supplier Payables`, `Expense Audit`, `Financial Accounts`, `Branch Comparison`.
- Global Date Preset Selector (`Today`, `Yesterday`, `Last 7 Days`, `This Month`, `Last Month`, `Last 30 Days`).
- Branch Selector dropdown (`isOwner` only).
- "Export CSV" Button (Streams filtered data directly from server).

### Report Domain Content Blocks:
1. **Sales Report Tab (`sales`):**
   - 4 Summary Cards: Gross Sales, Return Adjustment, Return-Adjusted Net Sales, Gross Profit.
   - Completed Sales Transactions Ledger Table (Invoice #, Date, Branch, Customer, Paid, Due, Total, Status).
2. **Inventory Report Tab (`inventory`):**
   - 4 Summary Cards: Total Stock Units, Current Stock Valuation (PKR), Low Stock Count, Out of Stock Count.
   - Branch Inventory Stock Ledger Table (Product, SKU, Category, Branch, Stock on Hand, Min Stock, Unit Cost, Total Stock Value).
3. **Customer Khata Tab (`customers`):**
   - Registered Customer Account Ledger Table (Customer, Phone, Sales Volume, Transactions Count, Payments Collected, Outstanding Receivable, Advance Credit).
4. **Supplier Payables Tab (`suppliers`):**
   - Supplier Procurement & Payable Ledger Table (Supplier, Contact Person, Purchase Volume, Purchases Count, Payments Made, Reconciled Payable).
5. **Expense Audit Tab (`expenses`):**
   - Posted Expenses Audit Table (Expense ID, Date, Category, Description, Branch, Account, Amount, Status).
6. **Financial Accounts Tab (`accounts`):**
   - Financial Accounts Cash Flow Ledger Table (Account Name, Type, Branch, Current Balance, Period Inflows, Period Outflows, Status).
7. **Branch Comparison Tab (`branches`):**
   - Cross-Branch Comparative Audit Table (Branch Name, Net Sales, Transactions, Gross Profit, Posted Expenses, Cash Drawer Balance).

---

## 11. Settings, Security, Offline Sync & Governance (`/settings`)

### Navigation Tabs Bar
- Sub-views: `Users & Staff`, `Roles & Permissions`, `Receipt & Printer`, `Security & Audit`, `Offline & Sync Center`.

---

### 11.1 Tab: `users` (`Settings/UsersView.vue`)
- **Control Bar:** Staff User Directory title, "Create Staff User" Primary CTA button (`openAddUserModal`).
- **Staff User Directory Table Card:**
  - Columns: `User Avatar & Name`, `Username`, `Email`, `Assigned Branch`, `System Role` (badge), `Status` (Active green / Disabled red), `Last Login`, `Actions`.
  - Actions: Edit User, Reset Password, Toggle Active/Disabled Status (`POST /api/v1/users/{id}/status`).
- **Add / Edit Staff User Modal (`user-modal-card`):**
  - Form Fields: Full Name (`*`), Username (`*`), Email Address, Password (required for new user), Assigned Branch dropdown (`*`), System Role dropdown (`Owner`, `Branch Manager`, `Salesperson`), Granular Permissions checkboxes.
  - Buttons: "Cancel", "Save User Account".

---

### 11.2 Tab: `roles` (`Settings/RolesView.vue`)
- **Role Governance Cards:**
  1. *Business Owner:* Full governance, cross-branch analytics, financial accounts control, user provisioning.
  2. *Branch Manager:* Branch sales, inventory adjustments, purchase receiving, local expense posting.
  3. *Salesperson:* POS checkout counter, customer lookup, thermal receipt printing.

---

### 11.3 Tab: `receipts` (`Settings/ReceiptsView.vue`)
- **Settings Form Card:**
  - Business Name & Tagline header inputs.
  - Header Line 1 & Line 2 inputs (Address, Phone, Tax/NTN).
  - Footer Disclaimer input (e.g. "No refunds without original receipt").
  - Paper Format selector (`58mm Thermal`, `80mm Thermal`, `A4 Invoice`).
  - Barcode & QR Code printing checkboxes.
  - "Upload Store Logo" button.
  - "Save Receipt Settings" button.
  - "Test Print Receipt" button (Fires physical printer diagnostic test).
- **Live Interactive Thermal Receipt Canvas Card:** Real-time visual rendering of thermal receipt.

---

### 11.4 Tab: `security` (`Settings/SecurityView.vue`)
- **Master Password Change Form Card:** Current Password, New Password, Confirm New Password (`PUT /api/v1/profile/password`).
- **Active Sessions Management Card:** Lists logged-in devices/browsers with "Revoke All Other Sessions" button.
- **Immutable Audit Trail Card:**
  - Search & Filter bar by User, Date, Action.
  - Audit Trail Table: `Timestamp`, `User`, `Action` (CREATE, UPDATE, DELETE, VOID, REFUND), `Target Entity`, `IP Address`, `Old Value`, `New Value`.
  - "Export Audit Log (CSV)" button.

---

### 11.5 Tab: `sync` / `offline-sync` (`Settings/SyncView.vue`)
- **Sync Status Dashboard Card:**
  - Cloud Connection Status pill (`Connected` / `Offline`).
  - Last Successful Cloud Sync timestamp.
  - Database Latency metric.
- **Pending Transactions Queue Card:**
  - Lists offline-recorded sales and payments in local IndexedDB waiting for cloud ingestion.
  - "Trigger Manual Cloud Sync" CTA button (`POST /api/v1/sync/bulk-transactions`).
  - "Clear Resolved Sync Logs" button.
- **P2P Mobile Device Pairing Console Card:**
  - "Generate Pairing QR Code" button.
  - Modal displaying dynamic `hbos-pair://` QR code for Capacitor Android scanner integration.

---

## 12. Notifications Center & Action Factory (`/notifications`)

- **Notifications Inbox Card:**
  - Filter Tabs: `All`, `Alerts`, `Inventory`, `Payables`, `System`.
  - "Mark All as Read" button (`PUT /api/v1/notifications/mark-all-read`).
- **Notification Item Row:**
  - Notification icon, Title, Message text, Timestamp, Read/Unread badge, Delete button.
  - **"Take Action" Button:** Dynamically loads `ActionModalFactory.vue`.
- **Action Modal Factory Popups (`ActionModalFactory.vue`):**
  1. *`PAY_EXPENSE` Modal:* Payment Account selector, Tx notes, "Confirm Payment" button.
  2. *`REORDER_STOCK` Modal:* Low Stock warning, "Go to Purchase Order" redirect CTA.
  3. *`PAY_SALARY` Modal:* Salary payout account selector, "Confirm Salary Payout" button.

---

## 13. Help & Support Portal (`/support` or `SupportView.vue`)

- **Header Block:** Help & Support title, search knowledgebase input.
- **Frequently Asked Questions Accordion Block:** Expandable guides on POS checkout, inventory restock, printer setup, and khata reconciliation.
- **Quick Resources Block:** Links to User Manual, Video Tutorials, System Diagnostic Check.
- **Contact Support Card:** Email, WhatsApp direct support button, System Diagnostic dump.

---

## 14. Master Architectural Summary Matrix

| Page / Route | View Component | Internal Tabs | Key Content Blocks & Cards | Primary Popups & Modals | Backend API & Controller |
|---|---|---|---|---|---|
| **Landing** `/` | `download.html` | N/A | Hero, Features, Download Buttons, Footer | N/A | `web.php` (`Route::get`) |
| **Login** `/login` | `LoginView.vue` | N/A | Branding, Login Card, Error Alert, Links | N/A | `AuthController@login` |
| **Register** `/register` | `RegisterView.vue` | N/A | Branding, Registration Form Card, Terms | N/A | `AuthController@register` |
| **Setup** `/busniesssetup*` | `BusniessSetup*.vue` | Steps 1–4 | Wizard Stepper, Form Cards, Review Summary | Skip Warning | `BusinessController`, `BranchController` |
| **Dashboard** `/` | `DashboardView.vue` | Role-Scoped | Header, Attention Banner, 8 Master KPIs, Top/Slow Sellers, Branch Comparison, Activity Stream | Quick POS Redirect | `DashboardController` |
| **Inventory** `/products` | `InventryView.vue` | `products`, `categories`, `brands` | 9 Master KPIs, Filter Bar, Column-Filtered Catalog Table, Category Tree, Brands Grid | Product Modal, Restock Modal, Details Modal, Category Modal, Brand Modal, Barcode Print | `ProductController`, `CategoryController`, `BrandController`, `InventoryController` |
| **POS Terminal** `/POS` | `PosSaleView.vue` | N/A | Header, Search/Barcode Bar, Category Pills, Touch Product Grid, Customer Selector, Cart List, Calculator Box, Action Bar | Payment Modal, Thermal Receipt Modal, Quick Customer Modal, Suspended Carts Modal, Shortcuts Guide | `SaleController`, `SyncController` |
| **Sales** `/sales` | `SalesView.vue` | N/A | Filters, Sales Ledger Table, Pagination | Invoice Modal, Return/Refund Modal | `SaleController`, `SaleReturnRefundController` |
| **Purchasing** `/purchase` | `PurchaseView.vue` | N/A | Header Form, Items Table, Billing & Summary Card | Supplier Quick Add, GRN Print Voucher | `PurchaseController` |
| **Supplier Payables** `/supplierpayable` | `SupplierpayableView.vue` | `supplier-intelligence`, `payables`, `purchase-orders`, `supplier-performance` | Aging Breakdown Buckets, Price Insights, Payables Table, PO History, Ratings | Settle Bill Modal, Add Supplier Modal | `SupplierController`, `SupplierPaymentController` |
| **Customers** `/customer` | `CustomerView.vue` | N/A | Summary Cards, Filters, Customer Directory Table | Add/Edit Customer, View Customer, Record Payment | `CustomerController`, `CustomerPaymentController` |
| **Khata** `/khata` | `KhataView.vue` | N/A | Debtors Ledger Table, Limit Utilization Bars | Settle Payment Modal, WhatsApp Draft | `KhataController`, `CustomerPaymentController` |
| **Expenses** `/expenses` | `ExpensesView.vue` | `overview`, `add`, `categories`, `reports` | 4 KPIs, Filter Bar, Ledger Table, Full-Page Form, Receipt Preview, Categories Table, Charts | Quick Expense Modal, Add Category Modal | `ExpenseController`, `ExpenseCategoryController`, `FinancialAccountController` |
| **Reports** `/reports` | `ReportsView.vue` | 7 Domain Tabs | Domain Pills, Date/Branch Selectors, Summary Cards, Data Tables for Sales, Inventory, Customers, Suppliers, Expenses, Accounts, Branches | Export CSV Handler | `ReportController` |
| **Settings** `/settings` | `SettingsView.vue` | `users`, `roles`, `receipts`, `security`, `sync` | User Table, Role Governance Cards, Receipt Customizer & Live Canvas, Password & Audit Trail, Sync Dashboard & Queue | Add/Edit User Modal, Logo Upload, Test Print Dialog, QR Device Pairing Modal | `SettingController`, `UserGovernanceController`, `AuditLogController`, `SyncController` |
| **Notifications** `/notifications` | `NotificationsView.vue` | Filter Tabs | Inbox List Items, Unread Badges | `ActionModalFactory` (`PAY_EXPENSE`, `REORDER_STOCK`, `PAY_SALARY`) | `NotificationController` |
| **Support** `/support` | `SupportView.vue` | N/A | FAQ Accordion, Resources, Contact Card | Diagnostic Dump Dialog | Diagnostic API |
