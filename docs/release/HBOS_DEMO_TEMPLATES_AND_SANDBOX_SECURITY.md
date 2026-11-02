# SKYRASOFT HBOS — DEMO TEMPLATES AND SANDBOX SECURITY
**Document Reference:** `docs/release/HBOS_DEMO_TEMPLATES_AND_SANDBOX_SECURITY.md`  
**Stage:** Stage 3 — Standardized Demo System, Windows Release Candidate & Owner Acceptance  
**Checkpoints Covered:** Checkpoint 3.1, Checkpoint 3.2  
**Status:** AUTHORITATIVE DEMO ARCHITECTURE & DATASET SPECIFICATION

---

## 1. Executive Summary

A critical mandate from the product owner is providing an immediately accessible, standardized demonstration that allows prospective shopkeepers, partners, and sales representatives to experience HBOS without creating a real account, typing passwords, or waiting for sales enablement.

Crucially:
- A standardized demonstration must NOT mean a single shared writable database where visitors see, overwrite, or corrupt each other's work.
- Demo operations must NEVER contaminate real business records or submit mock transactions to production merchant sync routes.

---

## 2. Sandbox Security & Data Isolation Architecture

```
[Welcome Screen]
       |
[Click "Try Demo"]
       |
+------+------+
|             |
v             v
[Single-Store Demo]               [Multi-Store Demo]
       |                                 |
Storage Partition:                Storage Partition:
`HBOS_Store_Biz_demo_single`      `HBOS_Store_Biz_demo_multi`
       |                                 |
+----------------------------------------+
|
v
[Client-Side Sandbox Layer]
- Blocks all push requests to real sync endpoint (`/api/v1/sync/bulk-transactions`)
- Attaches header `X-HBOS-Demo-Mode: true`
- Completely local mutations in partitioned IndexedDB store
- "Reset Demo" button purges and re-seeds that visitor's sandbox only
```

### 2.1 Mode Separation Rules
1. **Zero Registration / Zero Credentials:** Visitors launch Single-Store or Multi-Store directly from Welcome modal.
2. **Dedicated Storage Namespaces:**
   - Single-Store Demo: `HBOS_Store_Biz_demo_single`
   - Multi-Store Demo: `HBOS_Store_Biz_demo_multi`
   - Real Merchant: `HBOS_Store_Biz_${businessId}`
3. **No Cross-Contamination:** A visitor creating a sale in Demo mode modifies only the local demo sandbox. When logging into a real merchant account, the demo store is completely bypassed.
4. **Isolated Reset:** Clicking "Reset Demo" clears only the active demo store key and restores the pristine template.

---

## 3. Template 1: Single-Store Demonstration (Al-Madina General Store)

### 3.1 Business Identity & Setup
- **Business Name:** Al-Madina General Store
- **Store Persona:** Haji Muhammad Shoaib (Independent Owner-Operator)
- **Store Location:** Shop #14, Main Market, Lahore
- **Currency & Format:** PKR (Rs.), 2 decimal places for currency, 3 decimal places for loose goods.
- **Operating Model:** Situation 1 (One person running one shop — Owner has full POS, purchasing, Khata, expense, and closing rights without fictional managers or accountants).

### 3.2 Product Catalog Sample
| Product Name | SKU | Barcode | Unit | Fractional? | Cost Price | Retail Price | Initial Stock | Low Stock Alert |
|---|---|---|---|---|---|---|---|---|
| Super Kernel Basmati Rice | RICE-SKB | 8964000101 | kg | **Yes** (scale 3) | Rs. 280.00 | Rs. 350.00 | 45.500 kg | 10.000 kg |
| Chakki Atta Premium (Loose) | FLOUR-WHT | 8964000102 | kg | **Yes** (scale 3) | Rs. 110.00 | Rs. 135.00 | 120.000 kg | 25.000 kg |
| White Sugar Fine Grain | SUG-WHT | 8964000103 | kg | **Yes** (scale 3) | Rs. 140.00 | Rs. 165.00 | 8.250 kg *(Low)* | 15.000 kg |
| Dalda Cooking Oil 1L Pouch | OIL-DAL-1L | 8964000201 | pouch | No | Rs. 510.00 | Rs. 560.00 | 36 pcs | 10 pcs |
| Nestle MilkPak 1000ml | MILK-NP-1L | 8964000202 | pack | No | Rs. 260.00 | Rs. 295.00 | 48 pcs | 12 pcs |
| Tapal Danedar Tea 450g | TEA-TAP-450 | 8964000301 | pack | No | Rs. 620.00 | Rs. 710.00 | 18 pcs | 5 pcs |

### 3.3 Customer Khata Sample Ledger
- **Customer 1:** Chaudhry Akram (`0333-5556677`)
  - Credit Limit: Rs. 10,000.00
  - Current Outstanding Balance: Rs. 3,250.00
  - Last Transaction: Credit sale of Rs. 1,450.00 (3 days ago)
- **Customer 2:** Dr. Kamran Ahmed (`0300-8889911`)
  - Credit Limit: Rs. 15,000.00
  - Current Outstanding Balance: Rs. 1,200.00
  - Status: Regular customer, clean repayment record.

### 3.4 Supplier Payables & Cash Position
- **Supplier:** Punjab Grains Wholesale (`0321-4445566`)
  - Outstanding Due: Rs. 8,500.00 (PO-2026-089)
- **Main Cash Drawer:**
  - Opening Float: Rs. 5,000.00
  - Current Expected Cash: Rs. 9,420.00
  - Status: Active Open Session (Register #1)

### 3.5 Interactive Walkthrough: Single Store
1. **Open POS Counter:** Experience the barcode search, fractional quantity entry (e.g. 1.750 kg rice), and instant price calculation.
2. **Create a Khata Sale:** Select Chaudhry Akram, add items, and complete checkout on Khata credit.
3. **Record Khata Recovery:** Go to Khata, record Rs. 1,000 cash collection from customer, and observe customer debt deduction and drawer inflow.
4. **Review Day Close:** Open Cash Register, view blind close workflow, and inspect true profit reporting.

---

## 4. Template 2: Multi-Store Demonstration (Al-Madina Supermarket)

### 4.1 Enterprise Structure & Branches
- **Business:** Al-Madina Supermarket (National NTN: `8942103-7`)
- **Branches:**
  1. **Main Store (Commercial Center):** Primary branch (`is_primary = true`), central procurement and warehousing.
  2. **Gulberg Branch (Express Store):** Branch Manager: Tariq Mehmood, 2 Cashier counters.
  3. **DHA Emporium (Flagship Outlet):** Branch Manager: Farhan Akhtar, 3 Cashier counters.

### 4.2 Multi-Branch Stock & Transfers
- **Shared Master Catalog:** All branches share SKU and barcode definitions.
- **Independent Branch Stock Levels:**
  - Basmati Rice: Main Store (250 kg), Gulberg (45 kg), DHA (80 kg).
  - Dalda Cooking Oil: Main Store (120 pcs), Gulberg (18 pcs), DHA (30 pcs).
- **In-Transit Sample Transfer:**
  - Transfer #TR-042: 20 pcs Cooking Oil from Main Store to Gulberg Express.
  - Status: `in_transit` (Stock decremented at Main Store, awaiting receipt confirmation at Gulberg).

### 4.3 Multi-Role Persona Switcher (Demo Sandbox Only)
- Allows visitor to experience HBOS through three lenses:
  1. **Business Owner:** Full All-Stores visibility, comparative branch revenue, inter-branch transfer approvals, and executive profit statements.
  2. **Branch Manager (Gulberg):** Assigned branch operational dashboard, local stock receive, local cash session, restricted from other branches.
  3. **Cashier / Salesperson:** POS-first high-speed checkout, no access to wholesale costs or business profit.

### 4.4 Comparative Branch Reporting
- Side-by-side branch sales comparisons:
  - Today's Sales: Main Store (Rs. 42,500) | Gulberg (Rs. 18,300) | DHA (Rs. 29,800)
  - Gross Margins: Main Store (24.5%) | Gulberg (22.8%) | DHA (26.1%)

---

## 5. Walkthrough Guidance Engine

- Non-intrusive floating guide banner in top header.
- Visitors can freely explore any menu, or follow the 5-minute guided tour.
- "Dismiss Guide" or "Reset Tour" available at any time.

---

## 6. Verification Status

- [x] WelcomeView includes direct "Single-Store" and "Multi-Store" launch options.
- [x] Client storage keys partitioned (`demo_single`, `demo_multi`).
- [x] DemoModeBanner implemented and rendered sticky in main layout and POS.
- [x] Demo reset and exit behavior verified.
- [x] Real merchant endpoints protected from demo sync.
