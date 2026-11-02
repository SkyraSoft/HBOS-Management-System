# SkyraSoft HBOS — Full Business Acceptance Test Plan

**Document Purpose:** Complete, step-by-step physical and operational test plan for on-site merchant evaluation.  
**Audience:** Store Owners, Branch Managers, Cashiers, and QA Engineers.  

---

## 1. Setup & Baseline Configuration

1. **Target PC:** Windows 10/11 x64 terminal connected to counter local network.
2. **Installation:** Run `HBOS POS_1.0.0_x64_en-US.msi`. Complete installation in user profile (`%LOCALAPPDATA%\Programs\HBOS POS`).
3. **API Endpoint:** Point application to staging API (`http://127.0.0.1:8000` or local store server).
4. **Initial Database:** Dedicated tenant in `hbos_staging_uat` with synthetic products and initial cash float.

---

## 2. Role-Based Execution Scripts

### 2.1 Owner Workflow (Store Setup & Management)
* **Step 1:** Log in with Business Owner credentials.
* **Step 2:** Review Dashboard; verify total sales, gross margins, and branch comparisons display.
* **Step 3:** Navigate to Products; create new product and edit selling price.
* **Step 4:** Navigate to Purchasing; record new purchase from supplier with landed cost.
* **Step 5:** Navigate to Expenses; log utility expense under assigned category.
* **Step 6:** Log out; verify confidential dashboard data is cleared from memory.

### 2.2 Branch Manager Workflow (Daily Branch Operations)
* **Step 1:** Log in with Branch Manager credentials.
* **Step 2:** Verify manager cannot access global business settings or other branch confidential reports.
* **Step 3:** Open Cash Register session with 5,000 PKR opening float.
* **Step 4:** Dispatch inter-branch stock transfer to Branch 2.
* **Step 5:** Perform mid-day blind register check.
* **Step 6:** Close register session with end-of-day cash count.

### 2.3 Cashier Workflow (Counter Rush Hour)
* **Step 1:** Log in with Cashier credentials.
* **Step 2:** Fast-add customer and scan 5 items using USB barcode scanner.
* **Step 3:** Process Cash sale with split tender (Cash + Bank transfer).
* **Step 4:** Perform Khata credit sale; verify customer balance updates.
* **Step 5:** Disconnect network (unplug Ethernet / disable Wi-Fi); ring up 2 offline sales.
* **Step 6:** Restart application; verify 2 sales remain in outbox. Reconnect network; verify automatic sync.
