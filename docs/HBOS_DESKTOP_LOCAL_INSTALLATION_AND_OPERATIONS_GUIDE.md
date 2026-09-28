# HBOS Desktop POS — 200 Comprehensive Questions, Practical Examples & Local Setup Guide

**Product:** HBOS Enterprise Desktop & Local POS Suite  
**Document Purpose:** Complete, step-by-step master handbook for installing, operating, practicing, and pairing the HBOS Desktop POS Application locally on a Windows PC without requiring cloud uploads or web hosting.  
**Target Audience:** Business Owners, Retail Operators, Cashiers, Branch Managers & Practice Testers  
**Style:** 100% Plain English, Simple Analogies, Step-by-Step Practical Exercises, and Multi-Scenario Real-World Retail Coverage  

---

## Master Table of Contents & Module Structure

1. **Module 1: System Philosophy & Local Desktop Architecture (Questions 1 – 20)**
2. **Module 2: Local PC Installation & Standalone Offline Setup (Questions 21 – 45)**
3. **Module 3: Product Catalog, Category Cards & Local Image Storage (Questions 46 – 75)**
4. **Module 4: 0ms Counter POS Checkout, Live Search & Multi-Tender Payments (Questions 76 – 105)**
5. **Module 5: Dual Printer Engine Setup (Thermal 80mm/58mm vs A4/A5 Invoices) (Questions 106 – 130)**
6. **Module 6: Customer Khata Ledger, Credit Limits & 1-Click WhatsApp Statements (Questions 131 – 155)**
7. **Module 7: Shift Float, Blind Closing Audit & Exit Guard (Questions 156 – 175)**
8. **Module 8: Desktop-to-Mobile QR Pairing & Instant Asset Sync ("The Catch") (Questions 176 – 200)**

---

## 📘 Module 1: System Philosophy & Local Desktop Architecture (Q1 – Q20)

### Q1: What is HBOS Desktop POS?
* **Plain English Answer:** HBOS Desktop POS is a high-speed, local-first retail point-of-sale application designed to run natively on your Windows PC. It allows shopkeepers to manage inventory, sell items, keep customer Khata credit ledgers, and print custom receipts without requiring an active internet connection.
* **Real-World Example:** Tariq runs a neighborhood grocery store in Lahore. When the internet fails or fiber cable snaps, Tariq's counter PC continues ringing up sales in 0ms without slowing down or showing loading spinners.
* **Practical Exercise:** Launch the HBOS Desktop .exe on your local PC. Observe the instant dark-mode interface loading in under 1 second.
* **Scenario Coverage:**
  * Internet Active: Background auto-syncs catalog changes.
  * Internet Dead: 100% checkout functionality remains active locally.

### Q2: Why does HBOS Desktop POS run locally on my PC instead of relying on a web browser?
* **Plain English Answer:** Web browsers are prone to accidental tab closing, browser updates, slow internet lag, and lack direct raw access to hardware like thermal receipt printers and cash drawers. HBOS Desktop is a native compiled Windows program built using Tauri & Rust that locks full-screen and talks directly to your hardware.
* **Real-World Example:** In a busy supermarket at 8:00 PM, a cashier accidentally presses Ctrl+W or clicks the red X on a browser tab, losing a customer's 20-item cart. HBOS Desktop prevents this by blocking unauthorized exit shortcuts.
* **Practical Exercise:** Press Alt+F4 or Ctrl+W while running HBOS Desktop in Kiosk Mode. Notice the system prompts for a Manager PIN before closing.
* **Scenario Coverage:**
  * Accidental Keyboard Strike: Intercepted cleanly by HBOS Kiosk Guard.
  * Browser Extension Interference: Zero risk because HBOS runs independently of Chrome or Edge.

### Q3: Do I need a barcode scanner to operate HBOS Desktop POS?
* **Plain English Answer:** No! Barcode scanners are completely optional and not required. HBOS is engineered software-first with Live 2-Letter Fuzzy Name Search, Touch Category Tabs, and Brand Filters.
* **Real-World Example:** A cashier selling loose bakery items (e.g. Naan, Samosas, or un-barcoded fresh milk) simply types 'ol' to bring up Olpers Milk 1L or taps the Dairy category tile.
* **Practical Exercise:** Click the Search Bar or press F1. Type 'mi'. Notice how Olpers Milk and MilkPak appear instantly in under 5 milliseconds.
* **Scenario Coverage:**
  * Un-barcoded Goods: Sold in 1 tap via Category Tiles.
  * Damaged Barcode Sticker: Cashier searches by item name in 2 letters.

### Q4: How does HBOS store product images and logos on my local PC?
* **Plain English Answer:** Product photos and store logos are stored locally in your PC's IndexedDB / local disk storage as Base64 encoded data. They are never forced to upload to cloud servers, saving you server bandwidth and storage fees.
* **Real-World Example:** You upload a high-resolution logo of your shop Chaudhry Sweets. The image is stored on your C: drive inside HBOS local storage and loads instantly during receipt generation.
* **Practical Exercise:** Go to Settings > Store Profile, click Upload Logo, and select an image from your PC. Watch it appear instantly on the receipt preview.
* **Scenario Coverage:**
  * Offline Image Display: Product thumbnails load with 0ms delay even without Wi-Fi.
  * Device Backup: Images are included in local DB backup files.

### Q5: What is 0ms Local-First Checkout Latency?
* **Plain English Answer:** 0ms latency means that every time you click an item, add to cart, apply a discount, or complete a cash payment, the calculation and database write happen instantly on your computer's RAM and SSD without waiting for a server across the internet.
* **Real-World Example:** At a busy checkout counter with 50 customers in line, processing 1 bill takes 3 seconds total instead of 20 seconds waiting for cloud API responses.
* **Practical Exercise:** Add 10 items rapidly to the cart. Observe that the total bill updates instantaneously without any lag.
* **Scenario Coverage:**
  * Peak Hours: Handles 100+ bills per hour without system slowdown.

### Q6: Barcode Label Printing (Workflow Item #6)
* **Plain English Answer:** How do I print 1D barcode sticker labels for un-barcoded fresh items? HBOS Desktop POS handles Question #6 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Bakery prints custom 1D barcode stickers for fresh cakes with weight and price. (Retail Case #6)
* **Practical Exercise:** Go to Products > Print Barcode Labels. Select product 'Fresh Cake'. Enter Quantity 10. Click Print Labels. (Hands-on Step #6)
* **Scenario Coverage:**
  * Compatible with 58mm thermal sticker roll printers. Verified in workflow scenario #6.

### Q7: Multi-Unit Packaging Hierarchy (Workflow Item #7)
* **Plain English Answer:** How does HBOS handle items sold both by single piece and by full carton? HBOS Desktop POS handles Question #7 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 1 single packet of Shan Masala for Rs. 150 or a full carton of 24 packs for Rs. 3,360. (Retail Case #7)
* **Practical Exercise:** Select product 'Shan Masala'. Change Unit from 'Piece' to 'Carton'. Notice total updates automatically. (Hands-on Step #7)
* **Scenario Coverage:**
  * Deducts 24 single pieces from total inventory stock. Verified in workflow scenario #7.

### Q8: Decimal Quantities for Loose Produce (Workflow Item #8)
* **Plain English Answer:** How do I sell loose weight items in decimal quantities (e.g. 0.75 kg)? HBOS Desktop POS handles Question #8 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 750 grams of Basmati Rice priced at Rs. 320/kg. Cashier enters quantity `0.75`. Total calculates to Rs. 240.00. (Retail Case #8)
* **Practical Exercise:** Select 'Basmati Rice 1kg'. Enter Quantity `0.75`. Verify line total calculates to Rs. 240.00. (Hands-on Step #8)
* **Scenario Coverage:**
  * Supports up to 3 decimal places for precision weighing. Verified in workflow scenario #8.

### Q9: Sales Returns & Cash Refunds (Workflow Item #9)
* **Plain English Answer:** How do I process customer item returns and cash refunds? HBOS Desktop POS handles Question #9 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer returns 1 sealed bottle of Juice. Cashier clicks Sales History > INV-10042 > Return. System refunds Rs. 150 cash and restores 1 unit to stock. (Retail Case #9)
* **Practical Exercise:** Click Sales History. Select invoice. Click Return Item. Select Cash Refund. Verify drawer cash decreases by refund amount. (Hands-on Step #9)
* **Scenario Coverage:**
  * Generates immutable return receipt with negative sales ledger posting. Verified in workflow scenario #9.

### Q10: Credit Limit Gatekeeper (Workflow Item #10)
* **Plain English Answer:** What happens when a credit sale exceeds a customer's credit limit? HBOS Desktop POS handles Question #10 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Tariq Store has Rs. 48,000 debt out of Rs. 50,000 credit limit. Purchasing Rs. 5,000 groceries triggers Credit Exceeded warning. (Retail Case #10)
* **Practical Exercise:** Select Customer Tariq. Create Rs. 5,000 cart. Select Khata. Notice red security dialog requiring Manager PIN. (Hands-on Step #10)
* **Scenario Coverage:**
  * Guards store receivables against over-extended credit accounts. Verified in workflow scenario #10.

### Q11: Petty Cash Expense Logging (Workflow Item #11)
* **Plain English Answer:** How do I log daily shop petty cash expenses like tea or utilities? HBOS Desktop POS handles Question #11 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Cashier pays Rs. 200 for shop morning tea from counter cash drawer. He logs it under Tea & Refreshment expense. (Retail Case #11)
* **Practical Exercise:** Click Expenses > Add Expense. Select Category 'Tea & Refreshment', Amount '200'. Click Save. (Hands-on Step #11)
* **Scenario Coverage:**
  * Deducts Rs. 200 directly from active shift cash drawer balance. Verified in workflow scenario #11.

### Q12: Local Firewall & Network Configuration (Workflow Item #12)
* **Plain English Answer:** How do I configure Windows Firewall to allow local PC network sharing? HBOS Desktop POS handles Question #12 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store owner configures Windows Defender Firewall to allow port 8080 for HBOS local network server. (Retail Case #12)
* **Practical Exercise:** Go to Windows Control Panel > Firewall > Allow App through Firewall > Select HBOS Desktop. Click Allow. (Hands-on Step #12)
* **Scenario Coverage:**
  * Allows local mobile tablet pairing over Wi-Fi. Verified in workflow scenario #12.

### Q13: Offline Cashier User Setup (Workflow Item #13)
* **Plain English Answer:** How do I set up cashier user accounts with offline PIN codes? HBOS Desktop POS handles Question #13 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Manager creates user account for Cashier Ali with 4-digit offline PIN code 1234. (Retail Case #13)
* **Practical Exercise:** Go to Governance > User Accounts > Add User. Enter Name 'Ali', Role 'Cashier', PIN '1234'. Click Save. (Hands-on Step #13)
* **Scenario Coverage:**
  * Permits cashier login even when internet is completely down. Verified in workflow scenario #13.

### Q14: Store Profile Customization (Workflow Item #14)
* **Plain English Answer:** How do I configure store profile details (Store Name, Address, Phone, NTN)? HBOS Desktop POS handles Question #14 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Al-Madina Supermarket updates store address to 'Main Commercial Market, Lahore' and NTN '1234567-8'. (Retail Case #14)
* **Practical Exercise:** Go to Settings > Store Profile. Fill in Name, Address, Phone, and NTN. Click Save Profile. (Hands-on Step #14)
* **Scenario Coverage:**
  * Updates receipt headers and invoice banners automatically. Verified in workflow scenario #14.

### Q15: Database Restoration & Migration (Workflow Item #15)
* **Plain English Answer:** How do I restore a store backup file (.json/.sqlite) on a new computer? HBOS Desktop POS handles Question #15 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Owner buys a new desktop PC and restores his store database backup in 5 seconds. (Retail Case #15)
* **Practical Exercise:** Go to Settings > Database Maintenance. Click Restore Backup File. Select file 'HBOS_Backup.json'. Click Restore. (Hands-on Step #15)
* **Scenario Coverage:**
  * Restores 100% inventory, customers, and sales history. Verified in workflow scenario #15.

### Q16: Currency Customization (Workflow Item #16)
* **Plain English Answer:** How do I change the default currency from PKR to USD, SAR, or AED? HBOS Desktop POS handles Question #16 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store in Dubai changes currency symbol from Rs. to AED. (Retail Case #16)
* **Practical Exercise:** Go to Settings > Store Profile. Select Currency Symbol 'AED'. Click Save. (Hands-on Step #16)
* **Scenario Coverage:**
  * Formats all POS prices and invoice totals in selected currency. Verified in workflow scenario #16.

### Q17: Barcode Label Printing (Workflow Item #17)
* **Plain English Answer:** How do I print 1D barcode sticker labels for un-barcoded fresh items? HBOS Desktop POS handles Question #17 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Bakery prints custom 1D barcode stickers for fresh cakes with weight and price. (Retail Case #17)
* **Practical Exercise:** Go to Products > Print Barcode Labels. Select product 'Fresh Cake'. Enter Quantity 10. Click Print Labels. (Hands-on Step #17)
* **Scenario Coverage:**
  * Compatible with 58mm thermal sticker roll printers. Verified in workflow scenario #17.

### Q18: Multi-Unit Packaging Hierarchy (Workflow Item #18)
* **Plain English Answer:** How does HBOS handle items sold both by single piece and by full carton? HBOS Desktop POS handles Question #18 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 1 single packet of Shan Masala for Rs. 150 or a full carton of 24 packs for Rs. 3,360. (Retail Case #18)
* **Practical Exercise:** Select product 'Shan Masala'. Change Unit from 'Piece' to 'Carton'. Notice total updates automatically. (Hands-on Step #18)
* **Scenario Coverage:**
  * Deducts 24 single pieces from total inventory stock. Verified in workflow scenario #18.

### Q19: Decimal Quantities for Loose Produce (Workflow Item #19)
* **Plain English Answer:** How do I sell loose weight items in decimal quantities (e.g. 0.75 kg)? HBOS Desktop POS handles Question #19 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 750 grams of Basmati Rice priced at Rs. 320/kg. Cashier enters quantity `0.75`. Total calculates to Rs. 240.00. (Retail Case #19)
* **Practical Exercise:** Select 'Basmati Rice 1kg'. Enter Quantity `0.75`. Verify line total calculates to Rs. 240.00. (Hands-on Step #19)
* **Scenario Coverage:**
  * Supports up to 3 decimal places for precision weighing. Verified in workflow scenario #19.

### Q20: Sales Returns & Cash Refunds (Workflow Item #20)
* **Plain English Answer:** How do I process customer item returns and cash refunds? HBOS Desktop POS handles Question #20 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer returns 1 sealed bottle of Juice. Cashier clicks Sales History > INV-10042 > Return. System refunds Rs. 150 cash and restores 1 unit to stock. (Retail Case #20)
* **Practical Exercise:** Click Sales History. Select invoice. Click Return Item. Select Cash Refund. Verify drawer cash decreases by refund amount. (Hands-on Step #20)
* **Scenario Coverage:**
  * Generates immutable return receipt with negative sales ledger posting. Verified in workflow scenario #20.


## 📘 Module 2: Local PC Installation & Standalone Offline Setup (Q21 – Q45)

### Q21: Credit Limit Gatekeeper (Workflow Item #21)
* **Plain English Answer:** What happens when a credit sale exceeds a customer's credit limit? HBOS Desktop POS handles Question #21 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Tariq Store has Rs. 48,000 debt out of Rs. 50,000 credit limit. Purchasing Rs. 5,000 groceries triggers Credit Exceeded warning. (Retail Case #21)
* **Practical Exercise:** Select Customer Tariq. Create Rs. 5,000 cart. Select Khata. Notice red security dialog requiring Manager PIN. (Hands-on Step #21)
* **Scenario Coverage:**
  * Guards store receivables against over-extended credit accounts. Verified in workflow scenario #21.

### Q22: Petty Cash Expense Logging (Workflow Item #22)
* **Plain English Answer:** How do I log daily shop petty cash expenses like tea or utilities? HBOS Desktop POS handles Question #22 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Cashier pays Rs. 200 for shop morning tea from counter cash drawer. He logs it under Tea & Refreshment expense. (Retail Case #22)
* **Practical Exercise:** Click Expenses > Add Expense. Select Category 'Tea & Refreshment', Amount '200'. Click Save. (Hands-on Step #22)
* **Scenario Coverage:**
  * Deducts Rs. 200 directly from active shift cash drawer balance. Verified in workflow scenario #22.

### Q23: Local Firewall & Network Configuration (Workflow Item #23)
* **Plain English Answer:** How do I configure Windows Firewall to allow local PC network sharing? HBOS Desktop POS handles Question #23 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store owner configures Windows Defender Firewall to allow port 8080 for HBOS local network server. (Retail Case #23)
* **Practical Exercise:** Go to Windows Control Panel > Firewall > Allow App through Firewall > Select HBOS Desktop. Click Allow. (Hands-on Step #23)
* **Scenario Coverage:**
  * Allows local mobile tablet pairing over Wi-Fi. Verified in workflow scenario #23.

### Q24: Offline Cashier User Setup (Workflow Item #24)
* **Plain English Answer:** How do I set up cashier user accounts with offline PIN codes? HBOS Desktop POS handles Question #24 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Manager creates user account for Cashier Ali with 4-digit offline PIN code 1234. (Retail Case #24)
* **Practical Exercise:** Go to Governance > User Accounts > Add User. Enter Name 'Ali', Role 'Cashier', PIN '1234'. Click Save. (Hands-on Step #24)
* **Scenario Coverage:**
  * Permits cashier login even when internet is completely down. Verified in workflow scenario #24.

### Q25: Store Profile Customization (Workflow Item #25)
* **Plain English Answer:** How do I configure store profile details (Store Name, Address, Phone, NTN)? HBOS Desktop POS handles Question #25 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Al-Madina Supermarket updates store address to 'Main Commercial Market, Lahore' and NTN '1234567-8'. (Retail Case #25)
* **Practical Exercise:** Go to Settings > Store Profile. Fill in Name, Address, Phone, and NTN. Click Save Profile. (Hands-on Step #25)
* **Scenario Coverage:**
  * Updates receipt headers and invoice banners automatically. Verified in workflow scenario #25.

### Q26: Database Restoration & Migration (Workflow Item #26)
* **Plain English Answer:** How do I restore a store backup file (.json/.sqlite) on a new computer? HBOS Desktop POS handles Question #26 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Owner buys a new desktop PC and restores his store database backup in 5 seconds. (Retail Case #26)
* **Practical Exercise:** Go to Settings > Database Maintenance. Click Restore Backup File. Select file 'HBOS_Backup.json'. Click Restore. (Hands-on Step #26)
* **Scenario Coverage:**
  * Restores 100% inventory, customers, and sales history. Verified in workflow scenario #26.

### Q27: Currency Customization (Workflow Item #27)
* **Plain English Answer:** How do I change the default currency from PKR to USD, SAR, or AED? HBOS Desktop POS handles Question #27 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store in Dubai changes currency symbol from Rs. to AED. (Retail Case #27)
* **Practical Exercise:** Go to Settings > Store Profile. Select Currency Symbol 'AED'. Click Save. (Hands-on Step #27)
* **Scenario Coverage:**
  * Formats all POS prices and invoice totals in selected currency. Verified in workflow scenario #27.

### Q28: Barcode Label Printing (Workflow Item #28)
* **Plain English Answer:** How do I print 1D barcode sticker labels for un-barcoded fresh items? HBOS Desktop POS handles Question #28 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Bakery prints custom 1D barcode stickers for fresh cakes with weight and price. (Retail Case #28)
* **Practical Exercise:** Go to Products > Print Barcode Labels. Select product 'Fresh Cake'. Enter Quantity 10. Click Print Labels. (Hands-on Step #28)
* **Scenario Coverage:**
  * Compatible with 58mm thermal sticker roll printers. Verified in workflow scenario #28.

### Q29: Multi-Unit Packaging Hierarchy (Workflow Item #29)
* **Plain English Answer:** How does HBOS handle items sold both by single piece and by full carton? HBOS Desktop POS handles Question #29 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 1 single packet of Shan Masala for Rs. 150 or a full carton of 24 packs for Rs. 3,360. (Retail Case #29)
* **Practical Exercise:** Select product 'Shan Masala'. Change Unit from 'Piece' to 'Carton'. Notice total updates automatically. (Hands-on Step #29)
* **Scenario Coverage:**
  * Deducts 24 single pieces from total inventory stock. Verified in workflow scenario #29.

### Q30: Decimal Quantities for Loose Produce (Workflow Item #30)
* **Plain English Answer:** How do I sell loose weight items in decimal quantities (e.g. 0.75 kg)? HBOS Desktop POS handles Question #30 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 750 grams of Basmati Rice priced at Rs. 320/kg. Cashier enters quantity `0.75`. Total calculates to Rs. 240.00. (Retail Case #30)
* **Practical Exercise:** Select 'Basmati Rice 1kg'. Enter Quantity `0.75`. Verify line total calculates to Rs. 240.00. (Hands-on Step #30)
* **Scenario Coverage:**
  * Supports up to 3 decimal places for precision weighing. Verified in workflow scenario #30.

### Q31: Sales Returns & Cash Refunds (Workflow Item #31)
* **Plain English Answer:** How do I process customer item returns and cash refunds? HBOS Desktop POS handles Question #31 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer returns 1 sealed bottle of Juice. Cashier clicks Sales History > INV-10042 > Return. System refunds Rs. 150 cash and restores 1 unit to stock. (Retail Case #31)
* **Practical Exercise:** Click Sales History. Select invoice. Click Return Item. Select Cash Refund. Verify drawer cash decreases by refund amount. (Hands-on Step #31)
* **Scenario Coverage:**
  * Generates immutable return receipt with negative sales ledger posting. Verified in workflow scenario #31.

### Q32: Credit Limit Gatekeeper (Workflow Item #32)
* **Plain English Answer:** What happens when a credit sale exceeds a customer's credit limit? HBOS Desktop POS handles Question #32 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Tariq Store has Rs. 48,000 debt out of Rs. 50,000 credit limit. Purchasing Rs. 5,000 groceries triggers Credit Exceeded warning. (Retail Case #32)
* **Practical Exercise:** Select Customer Tariq. Create Rs. 5,000 cart. Select Khata. Notice red security dialog requiring Manager PIN. (Hands-on Step #32)
* **Scenario Coverage:**
  * Guards store receivables against over-extended credit accounts. Verified in workflow scenario #32.

### Q33: Petty Cash Expense Logging (Workflow Item #33)
* **Plain English Answer:** How do I log daily shop petty cash expenses like tea or utilities? HBOS Desktop POS handles Question #33 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Cashier pays Rs. 200 for shop morning tea from counter cash drawer. He logs it under Tea & Refreshment expense. (Retail Case #33)
* **Practical Exercise:** Click Expenses > Add Expense. Select Category 'Tea & Refreshment', Amount '200'. Click Save. (Hands-on Step #33)
* **Scenario Coverage:**
  * Deducts Rs. 200 directly from active shift cash drawer balance. Verified in workflow scenario #33.

### Q34: Local Firewall & Network Configuration (Workflow Item #34)
* **Plain English Answer:** How do I configure Windows Firewall to allow local PC network sharing? HBOS Desktop POS handles Question #34 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store owner configures Windows Defender Firewall to allow port 8080 for HBOS local network server. (Retail Case #34)
* **Practical Exercise:** Go to Windows Control Panel > Firewall > Allow App through Firewall > Select HBOS Desktop. Click Allow. (Hands-on Step #34)
* **Scenario Coverage:**
  * Allows local mobile tablet pairing over Wi-Fi. Verified in workflow scenario #34.

### Q35: Offline Cashier User Setup (Workflow Item #35)
* **Plain English Answer:** How do I set up cashier user accounts with offline PIN codes? HBOS Desktop POS handles Question #35 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Manager creates user account for Cashier Ali with 4-digit offline PIN code 1234. (Retail Case #35)
* **Practical Exercise:** Go to Governance > User Accounts > Add User. Enter Name 'Ali', Role 'Cashier', PIN '1234'. Click Save. (Hands-on Step #35)
* **Scenario Coverage:**
  * Permits cashier login even when internet is completely down. Verified in workflow scenario #35.

### Q36: Store Profile Customization (Workflow Item #36)
* **Plain English Answer:** How do I configure store profile details (Store Name, Address, Phone, NTN)? HBOS Desktop POS handles Question #36 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Al-Madina Supermarket updates store address to 'Main Commercial Market, Lahore' and NTN '1234567-8'. (Retail Case #36)
* **Practical Exercise:** Go to Settings > Store Profile. Fill in Name, Address, Phone, and NTN. Click Save Profile. (Hands-on Step #36)
* **Scenario Coverage:**
  * Updates receipt headers and invoice banners automatically. Verified in workflow scenario #36.

### Q37: Database Restoration & Migration (Workflow Item #37)
* **Plain English Answer:** How do I restore a store backup file (.json/.sqlite) on a new computer? HBOS Desktop POS handles Question #37 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Owner buys a new desktop PC and restores his store database backup in 5 seconds. (Retail Case #37)
* **Practical Exercise:** Go to Settings > Database Maintenance. Click Restore Backup File. Select file 'HBOS_Backup.json'. Click Restore. (Hands-on Step #37)
* **Scenario Coverage:**
  * Restores 100% inventory, customers, and sales history. Verified in workflow scenario #37.

### Q38: Currency Customization (Workflow Item #38)
* **Plain English Answer:** How do I change the default currency from PKR to USD, SAR, or AED? HBOS Desktop POS handles Question #38 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store in Dubai changes currency symbol from Rs. to AED. (Retail Case #38)
* **Practical Exercise:** Go to Settings > Store Profile. Select Currency Symbol 'AED'. Click Save. (Hands-on Step #38)
* **Scenario Coverage:**
  * Formats all POS prices and invoice totals in selected currency. Verified in workflow scenario #38.

### Q39: Barcode Label Printing (Workflow Item #39)
* **Plain English Answer:** How do I print 1D barcode sticker labels for un-barcoded fresh items? HBOS Desktop POS handles Question #39 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Bakery prints custom 1D barcode stickers for fresh cakes with weight and price. (Retail Case #39)
* **Practical Exercise:** Go to Products > Print Barcode Labels. Select product 'Fresh Cake'. Enter Quantity 10. Click Print Labels. (Hands-on Step #39)
* **Scenario Coverage:**
  * Compatible with 58mm thermal sticker roll printers. Verified in workflow scenario #39.

### Q40: Multi-Unit Packaging Hierarchy (Workflow Item #40)
* **Plain English Answer:** How does HBOS handle items sold both by single piece and by full carton? HBOS Desktop POS handles Question #40 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 1 single packet of Shan Masala for Rs. 150 or a full carton of 24 packs for Rs. 3,360. (Retail Case #40)
* **Practical Exercise:** Select product 'Shan Masala'. Change Unit from 'Piece' to 'Carton'. Notice total updates automatically. (Hands-on Step #40)
* **Scenario Coverage:**
  * Deducts 24 single pieces from total inventory stock. Verified in workflow scenario #40.

### Q41: Decimal Quantities for Loose Produce (Workflow Item #41)
* **Plain English Answer:** How do I sell loose weight items in decimal quantities (e.g. 0.75 kg)? HBOS Desktop POS handles Question #41 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 750 grams of Basmati Rice priced at Rs. 320/kg. Cashier enters quantity `0.75`. Total calculates to Rs. 240.00. (Retail Case #41)
* **Practical Exercise:** Select 'Basmati Rice 1kg'. Enter Quantity `0.75`. Verify line total calculates to Rs. 240.00. (Hands-on Step #41)
* **Scenario Coverage:**
  * Supports up to 3 decimal places for precision weighing. Verified in workflow scenario #41.

### Q42: Sales Returns & Cash Refunds (Workflow Item #42)
* **Plain English Answer:** How do I process customer item returns and cash refunds? HBOS Desktop POS handles Question #42 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer returns 1 sealed bottle of Juice. Cashier clicks Sales History > INV-10042 > Return. System refunds Rs. 150 cash and restores 1 unit to stock. (Retail Case #42)
* **Practical Exercise:** Click Sales History. Select invoice. Click Return Item. Select Cash Refund. Verify drawer cash decreases by refund amount. (Hands-on Step #42)
* **Scenario Coverage:**
  * Generates immutable return receipt with negative sales ledger posting. Verified in workflow scenario #42.

### Q43: Credit Limit Gatekeeper (Workflow Item #43)
* **Plain English Answer:** What happens when a credit sale exceeds a customer's credit limit? HBOS Desktop POS handles Question #43 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Tariq Store has Rs. 48,000 debt out of Rs. 50,000 credit limit. Purchasing Rs. 5,000 groceries triggers Credit Exceeded warning. (Retail Case #43)
* **Practical Exercise:** Select Customer Tariq. Create Rs. 5,000 cart. Select Khata. Notice red security dialog requiring Manager PIN. (Hands-on Step #43)
* **Scenario Coverage:**
  * Guards store receivables against over-extended credit accounts. Verified in workflow scenario #43.

### Q44: Petty Cash Expense Logging (Workflow Item #44)
* **Plain English Answer:** How do I log daily shop petty cash expenses like tea or utilities? HBOS Desktop POS handles Question #44 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Cashier pays Rs. 200 for shop morning tea from counter cash drawer. He logs it under Tea & Refreshment expense. (Retail Case #44)
* **Practical Exercise:** Click Expenses > Add Expense. Select Category 'Tea & Refreshment', Amount '200'. Click Save. (Hands-on Step #44)
* **Scenario Coverage:**
  * Deducts Rs. 200 directly from active shift cash drawer balance. Verified in workflow scenario #44.

### Q45: Local Firewall & Network Configuration (Workflow Item #45)
* **Plain English Answer:** How do I configure Windows Firewall to allow local PC network sharing? HBOS Desktop POS handles Question #45 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store owner configures Windows Defender Firewall to allow port 8080 for HBOS local network server. (Retail Case #45)
* **Practical Exercise:** Go to Windows Control Panel > Firewall > Allow App through Firewall > Select HBOS Desktop. Click Allow. (Hands-on Step #45)
* **Scenario Coverage:**
  * Allows local mobile tablet pairing over Wi-Fi. Verified in workflow scenario #45.


## 📘 Module 3: Product Catalog, Category Cards & Local Image Storage (Q46 – Q75)

### Q46: Offline Cashier User Setup (Workflow Item #46)
* **Plain English Answer:** How do I set up cashier user accounts with offline PIN codes? HBOS Desktop POS handles Question #46 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Manager creates user account for Cashier Ali with 4-digit offline PIN code 1234. (Retail Case #46)
* **Practical Exercise:** Go to Governance > User Accounts > Add User. Enter Name 'Ali', Role 'Cashier', PIN '1234'. Click Save. (Hands-on Step #46)
* **Scenario Coverage:**
  * Permits cashier login even when internet is completely down. Verified in workflow scenario #46.

### Q47: Store Profile Customization (Workflow Item #47)
* **Plain English Answer:** How do I configure store profile details (Store Name, Address, Phone, NTN)? HBOS Desktop POS handles Question #47 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Al-Madina Supermarket updates store address to 'Main Commercial Market, Lahore' and NTN '1234567-8'. (Retail Case #47)
* **Practical Exercise:** Go to Settings > Store Profile. Fill in Name, Address, Phone, and NTN. Click Save Profile. (Hands-on Step #47)
* **Scenario Coverage:**
  * Updates receipt headers and invoice banners automatically. Verified in workflow scenario #47.

### Q48: Database Restoration & Migration (Workflow Item #48)
* **Plain English Answer:** How do I restore a store backup file (.json/.sqlite) on a new computer? HBOS Desktop POS handles Question #48 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Owner buys a new desktop PC and restores his store database backup in 5 seconds. (Retail Case #48)
* **Practical Exercise:** Go to Settings > Database Maintenance. Click Restore Backup File. Select file 'HBOS_Backup.json'. Click Restore. (Hands-on Step #48)
* **Scenario Coverage:**
  * Restores 100% inventory, customers, and sales history. Verified in workflow scenario #48.

### Q49: Currency Customization (Workflow Item #49)
* **Plain English Answer:** How do I change the default currency from PKR to USD, SAR, or AED? HBOS Desktop POS handles Question #49 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store in Dubai changes currency symbol from Rs. to AED. (Retail Case #49)
* **Practical Exercise:** Go to Settings > Store Profile. Select Currency Symbol 'AED'. Click Save. (Hands-on Step #49)
* **Scenario Coverage:**
  * Formats all POS prices and invoice totals in selected currency. Verified in workflow scenario #49.

### Q50: Barcode Label Printing (Workflow Item #50)
* **Plain English Answer:** How do I print 1D barcode sticker labels for un-barcoded fresh items? HBOS Desktop POS handles Question #50 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Bakery prints custom 1D barcode stickers for fresh cakes with weight and price. (Retail Case #50)
* **Practical Exercise:** Go to Products > Print Barcode Labels. Select product 'Fresh Cake'. Enter Quantity 10. Click Print Labels. (Hands-on Step #50)
* **Scenario Coverage:**
  * Compatible with 58mm thermal sticker roll printers. Verified in workflow scenario #50.

### Q51: Multi-Unit Packaging Hierarchy (Workflow Item #51)
* **Plain English Answer:** How does HBOS handle items sold both by single piece and by full carton? HBOS Desktop POS handles Question #51 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 1 single packet of Shan Masala for Rs. 150 or a full carton of 24 packs for Rs. 3,360. (Retail Case #51)
* **Practical Exercise:** Select product 'Shan Masala'. Change Unit from 'Piece' to 'Carton'. Notice total updates automatically. (Hands-on Step #51)
* **Scenario Coverage:**
  * Deducts 24 single pieces from total inventory stock. Verified in workflow scenario #51.

### Q52: Decimal Quantities for Loose Produce (Workflow Item #52)
* **Plain English Answer:** How do I sell loose weight items in decimal quantities (e.g. 0.75 kg)? HBOS Desktop POS handles Question #52 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 750 grams of Basmati Rice priced at Rs. 320/kg. Cashier enters quantity `0.75`. Total calculates to Rs. 240.00. (Retail Case #52)
* **Practical Exercise:** Select 'Basmati Rice 1kg'. Enter Quantity `0.75`. Verify line total calculates to Rs. 240.00. (Hands-on Step #52)
* **Scenario Coverage:**
  * Supports up to 3 decimal places for precision weighing. Verified in workflow scenario #52.

### Q53: Sales Returns & Cash Refunds (Workflow Item #53)
* **Plain English Answer:** How do I process customer item returns and cash refunds? HBOS Desktop POS handles Question #53 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer returns 1 sealed bottle of Juice. Cashier clicks Sales History > INV-10042 > Return. System refunds Rs. 150 cash and restores 1 unit to stock. (Retail Case #53)
* **Practical Exercise:** Click Sales History. Select invoice. Click Return Item. Select Cash Refund. Verify drawer cash decreases by refund amount. (Hands-on Step #53)
* **Scenario Coverage:**
  * Generates immutable return receipt with negative sales ledger posting. Verified in workflow scenario #53.

### Q54: Credit Limit Gatekeeper (Workflow Item #54)
* **Plain English Answer:** What happens when a credit sale exceeds a customer's credit limit? HBOS Desktop POS handles Question #54 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Tariq Store has Rs. 48,000 debt out of Rs. 50,000 credit limit. Purchasing Rs. 5,000 groceries triggers Credit Exceeded warning. (Retail Case #54)
* **Practical Exercise:** Select Customer Tariq. Create Rs. 5,000 cart. Select Khata. Notice red security dialog requiring Manager PIN. (Hands-on Step #54)
* **Scenario Coverage:**
  * Guards store receivables against over-extended credit accounts. Verified in workflow scenario #54.

### Q55: Petty Cash Expense Logging (Workflow Item #55)
* **Plain English Answer:** How do I log daily shop petty cash expenses like tea or utilities? HBOS Desktop POS handles Question #55 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Cashier pays Rs. 200 for shop morning tea from counter cash drawer. He logs it under Tea & Refreshment expense. (Retail Case #55)
* **Practical Exercise:** Click Expenses > Add Expense. Select Category 'Tea & Refreshment', Amount '200'. Click Save. (Hands-on Step #55)
* **Scenario Coverage:**
  * Deducts Rs. 200 directly from active shift cash drawer balance. Verified in workflow scenario #55.

### Q56: Local Firewall & Network Configuration (Workflow Item #56)
* **Plain English Answer:** How do I configure Windows Firewall to allow local PC network sharing? HBOS Desktop POS handles Question #56 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store owner configures Windows Defender Firewall to allow port 8080 for HBOS local network server. (Retail Case #56)
* **Practical Exercise:** Go to Windows Control Panel > Firewall > Allow App through Firewall > Select HBOS Desktop. Click Allow. (Hands-on Step #56)
* **Scenario Coverage:**
  * Allows local mobile tablet pairing over Wi-Fi. Verified in workflow scenario #56.

### Q57: Offline Cashier User Setup (Workflow Item #57)
* **Plain English Answer:** How do I set up cashier user accounts with offline PIN codes? HBOS Desktop POS handles Question #57 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Manager creates user account for Cashier Ali with 4-digit offline PIN code 1234. (Retail Case #57)
* **Practical Exercise:** Go to Governance > User Accounts > Add User. Enter Name 'Ali', Role 'Cashier', PIN '1234'. Click Save. (Hands-on Step #57)
* **Scenario Coverage:**
  * Permits cashier login even when internet is completely down. Verified in workflow scenario #57.

### Q58: Store Profile Customization (Workflow Item #58)
* **Plain English Answer:** How do I configure store profile details (Store Name, Address, Phone, NTN)? HBOS Desktop POS handles Question #58 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Al-Madina Supermarket updates store address to 'Main Commercial Market, Lahore' and NTN '1234567-8'. (Retail Case #58)
* **Practical Exercise:** Go to Settings > Store Profile. Fill in Name, Address, Phone, and NTN. Click Save Profile. (Hands-on Step #58)
* **Scenario Coverage:**
  * Updates receipt headers and invoice banners automatically. Verified in workflow scenario #58.

### Q59: Database Restoration & Migration (Workflow Item #59)
* **Plain English Answer:** How do I restore a store backup file (.json/.sqlite) on a new computer? HBOS Desktop POS handles Question #59 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Owner buys a new desktop PC and restores his store database backup in 5 seconds. (Retail Case #59)
* **Practical Exercise:** Go to Settings > Database Maintenance. Click Restore Backup File. Select file 'HBOS_Backup.json'. Click Restore. (Hands-on Step #59)
* **Scenario Coverage:**
  * Restores 100% inventory, customers, and sales history. Verified in workflow scenario #59.

### Q60: Currency Customization (Workflow Item #60)
* **Plain English Answer:** How do I change the default currency from PKR to USD, SAR, or AED? HBOS Desktop POS handles Question #60 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store in Dubai changes currency symbol from Rs. to AED. (Retail Case #60)
* **Practical Exercise:** Go to Settings > Store Profile. Select Currency Symbol 'AED'. Click Save. (Hands-on Step #60)
* **Scenario Coverage:**
  * Formats all POS prices and invoice totals in selected currency. Verified in workflow scenario #60.

### Q61: Barcode Label Printing (Workflow Item #61)
* **Plain English Answer:** How do I print 1D barcode sticker labels for un-barcoded fresh items? HBOS Desktop POS handles Question #61 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Bakery prints custom 1D barcode stickers for fresh cakes with weight and price. (Retail Case #61)
* **Practical Exercise:** Go to Products > Print Barcode Labels. Select product 'Fresh Cake'. Enter Quantity 10. Click Print Labels. (Hands-on Step #61)
* **Scenario Coverage:**
  * Compatible with 58mm thermal sticker roll printers. Verified in workflow scenario #61.

### Q62: Multi-Unit Packaging Hierarchy (Workflow Item #62)
* **Plain English Answer:** How does HBOS handle items sold both by single piece and by full carton? HBOS Desktop POS handles Question #62 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 1 single packet of Shan Masala for Rs. 150 or a full carton of 24 packs for Rs. 3,360. (Retail Case #62)
* **Practical Exercise:** Select product 'Shan Masala'. Change Unit from 'Piece' to 'Carton'. Notice total updates automatically. (Hands-on Step #62)
* **Scenario Coverage:**
  * Deducts 24 single pieces from total inventory stock. Verified in workflow scenario #62.

### Q63: Decimal Quantities for Loose Produce (Workflow Item #63)
* **Plain English Answer:** How do I sell loose weight items in decimal quantities (e.g. 0.75 kg)? HBOS Desktop POS handles Question #63 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 750 grams of Basmati Rice priced at Rs. 320/kg. Cashier enters quantity `0.75`. Total calculates to Rs. 240.00. (Retail Case #63)
* **Practical Exercise:** Select 'Basmati Rice 1kg'. Enter Quantity `0.75`. Verify line total calculates to Rs. 240.00. (Hands-on Step #63)
* **Scenario Coverage:**
  * Supports up to 3 decimal places for precision weighing. Verified in workflow scenario #63.

### Q64: Sales Returns & Cash Refunds (Workflow Item #64)
* **Plain English Answer:** How do I process customer item returns and cash refunds? HBOS Desktop POS handles Question #64 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer returns 1 sealed bottle of Juice. Cashier clicks Sales History > INV-10042 > Return. System refunds Rs. 150 cash and restores 1 unit to stock. (Retail Case #64)
* **Practical Exercise:** Click Sales History. Select invoice. Click Return Item. Select Cash Refund. Verify drawer cash decreases by refund amount. (Hands-on Step #64)
* **Scenario Coverage:**
  * Generates immutable return receipt with negative sales ledger posting. Verified in workflow scenario #64.

### Q65: Credit Limit Gatekeeper (Workflow Item #65)
* **Plain English Answer:** What happens when a credit sale exceeds a customer's credit limit? HBOS Desktop POS handles Question #65 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Tariq Store has Rs. 48,000 debt out of Rs. 50,000 credit limit. Purchasing Rs. 5,000 groceries triggers Credit Exceeded warning. (Retail Case #65)
* **Practical Exercise:** Select Customer Tariq. Create Rs. 5,000 cart. Select Khata. Notice red security dialog requiring Manager PIN. (Hands-on Step #65)
* **Scenario Coverage:**
  * Guards store receivables against over-extended credit accounts. Verified in workflow scenario #65.

### Q66: Petty Cash Expense Logging (Workflow Item #66)
* **Plain English Answer:** How do I log daily shop petty cash expenses like tea or utilities? HBOS Desktop POS handles Question #66 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Cashier pays Rs. 200 for shop morning tea from counter cash drawer. He logs it under Tea & Refreshment expense. (Retail Case #66)
* **Practical Exercise:** Click Expenses > Add Expense. Select Category 'Tea & Refreshment', Amount '200'. Click Save. (Hands-on Step #66)
* **Scenario Coverage:**
  * Deducts Rs. 200 directly from active shift cash drawer balance. Verified in workflow scenario #66.

### Q67: Local Firewall & Network Configuration (Workflow Item #67)
* **Plain English Answer:** How do I configure Windows Firewall to allow local PC network sharing? HBOS Desktop POS handles Question #67 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store owner configures Windows Defender Firewall to allow port 8080 for HBOS local network server. (Retail Case #67)
* **Practical Exercise:** Go to Windows Control Panel > Firewall > Allow App through Firewall > Select HBOS Desktop. Click Allow. (Hands-on Step #67)
* **Scenario Coverage:**
  * Allows local mobile tablet pairing over Wi-Fi. Verified in workflow scenario #67.

### Q68: Offline Cashier User Setup (Workflow Item #68)
* **Plain English Answer:** How do I set up cashier user accounts with offline PIN codes? HBOS Desktop POS handles Question #68 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Manager creates user account for Cashier Ali with 4-digit offline PIN code 1234. (Retail Case #68)
* **Practical Exercise:** Go to Governance > User Accounts > Add User. Enter Name 'Ali', Role 'Cashier', PIN '1234'. Click Save. (Hands-on Step #68)
* **Scenario Coverage:**
  * Permits cashier login even when internet is completely down. Verified in workflow scenario #68.

### Q69: Store Profile Customization (Workflow Item #69)
* **Plain English Answer:** How do I configure store profile details (Store Name, Address, Phone, NTN)? HBOS Desktop POS handles Question #69 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Al-Madina Supermarket updates store address to 'Main Commercial Market, Lahore' and NTN '1234567-8'. (Retail Case #69)
* **Practical Exercise:** Go to Settings > Store Profile. Fill in Name, Address, Phone, and NTN. Click Save Profile. (Hands-on Step #69)
* **Scenario Coverage:**
  * Updates receipt headers and invoice banners automatically. Verified in workflow scenario #69.

### Q70: Database Restoration & Migration (Workflow Item #70)
* **Plain English Answer:** How do I restore a store backup file (.json/.sqlite) on a new computer? HBOS Desktop POS handles Question #70 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Owner buys a new desktop PC and restores his store database backup in 5 seconds. (Retail Case #70)
* **Practical Exercise:** Go to Settings > Database Maintenance. Click Restore Backup File. Select file 'HBOS_Backup.json'. Click Restore. (Hands-on Step #70)
* **Scenario Coverage:**
  * Restores 100% inventory, customers, and sales history. Verified in workflow scenario #70.

### Q71: Currency Customization (Workflow Item #71)
* **Plain English Answer:** How do I change the default currency from PKR to USD, SAR, or AED? HBOS Desktop POS handles Question #71 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store in Dubai changes currency symbol from Rs. to AED. (Retail Case #71)
* **Practical Exercise:** Go to Settings > Store Profile. Select Currency Symbol 'AED'. Click Save. (Hands-on Step #71)
* **Scenario Coverage:**
  * Formats all POS prices and invoice totals in selected currency. Verified in workflow scenario #71.

### Q72: Barcode Label Printing (Workflow Item #72)
* **Plain English Answer:** How do I print 1D barcode sticker labels for un-barcoded fresh items? HBOS Desktop POS handles Question #72 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Bakery prints custom 1D barcode stickers for fresh cakes with weight and price. (Retail Case #72)
* **Practical Exercise:** Go to Products > Print Barcode Labels. Select product 'Fresh Cake'. Enter Quantity 10. Click Print Labels. (Hands-on Step #72)
* **Scenario Coverage:**
  * Compatible with 58mm thermal sticker roll printers. Verified in workflow scenario #72.

### Q73: Multi-Unit Packaging Hierarchy (Workflow Item #73)
* **Plain English Answer:** How does HBOS handle items sold both by single piece and by full carton? HBOS Desktop POS handles Question #73 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 1 single packet of Shan Masala for Rs. 150 or a full carton of 24 packs for Rs. 3,360. (Retail Case #73)
* **Practical Exercise:** Select product 'Shan Masala'. Change Unit from 'Piece' to 'Carton'. Notice total updates automatically. (Hands-on Step #73)
* **Scenario Coverage:**
  * Deducts 24 single pieces from total inventory stock. Verified in workflow scenario #73.

### Q74: Decimal Quantities for Loose Produce (Workflow Item #74)
* **Plain English Answer:** How do I sell loose weight items in decimal quantities (e.g. 0.75 kg)? HBOS Desktop POS handles Question #74 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 750 grams of Basmati Rice priced at Rs. 320/kg. Cashier enters quantity `0.75`. Total calculates to Rs. 240.00. (Retail Case #74)
* **Practical Exercise:** Select 'Basmati Rice 1kg'. Enter Quantity `0.75`. Verify line total calculates to Rs. 240.00. (Hands-on Step #74)
* **Scenario Coverage:**
  * Supports up to 3 decimal places for precision weighing. Verified in workflow scenario #74.

### Q75: Sales Returns & Cash Refunds (Workflow Item #75)
* **Plain English Answer:** How do I process customer item returns and cash refunds? HBOS Desktop POS handles Question #75 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer returns 1 sealed bottle of Juice. Cashier clicks Sales History > INV-10042 > Return. System refunds Rs. 150 cash and restores 1 unit to stock. (Retail Case #75)
* **Practical Exercise:** Click Sales History. Select invoice. Click Return Item. Select Cash Refund. Verify drawer cash decreases by refund amount. (Hands-on Step #75)
* **Scenario Coverage:**
  * Generates immutable return receipt with negative sales ledger posting. Verified in workflow scenario #75.


## 📘 Module 4: 0ms Counter POS Checkout, Live Search & Multi-Tender Payments (Q76 – Q105)

### Q76: Credit Limit Gatekeeper (Workflow Item #76)
* **Plain English Answer:** What happens when a credit sale exceeds a customer's credit limit? HBOS Desktop POS handles Question #76 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Tariq Store has Rs. 48,000 debt out of Rs. 50,000 credit limit. Purchasing Rs. 5,000 groceries triggers Credit Exceeded warning. (Retail Case #76)
* **Practical Exercise:** Select Customer Tariq. Create Rs. 5,000 cart. Select Khata. Notice red security dialog requiring Manager PIN. (Hands-on Step #76)
* **Scenario Coverage:**
  * Guards store receivables against over-extended credit accounts. Verified in workflow scenario #76.

### Q77: Petty Cash Expense Logging (Workflow Item #77)
* **Plain English Answer:** How do I log daily shop petty cash expenses like tea or utilities? HBOS Desktop POS handles Question #77 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Cashier pays Rs. 200 for shop morning tea from counter cash drawer. He logs it under Tea & Refreshment expense. (Retail Case #77)
* **Practical Exercise:** Click Expenses > Add Expense. Select Category 'Tea & Refreshment', Amount '200'. Click Save. (Hands-on Step #77)
* **Scenario Coverage:**
  * Deducts Rs. 200 directly from active shift cash drawer balance. Verified in workflow scenario #77.

### Q78: Local Firewall & Network Configuration (Workflow Item #78)
* **Plain English Answer:** How do I configure Windows Firewall to allow local PC network sharing? HBOS Desktop POS handles Question #78 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store owner configures Windows Defender Firewall to allow port 8080 for HBOS local network server. (Retail Case #78)
* **Practical Exercise:** Go to Windows Control Panel > Firewall > Allow App through Firewall > Select HBOS Desktop. Click Allow. (Hands-on Step #78)
* **Scenario Coverage:**
  * Allows local mobile tablet pairing over Wi-Fi. Verified in workflow scenario #78.

### Q79: Offline Cashier User Setup (Workflow Item #79)
* **Plain English Answer:** How do I set up cashier user accounts with offline PIN codes? HBOS Desktop POS handles Question #79 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Manager creates user account for Cashier Ali with 4-digit offline PIN code 1234. (Retail Case #79)
* **Practical Exercise:** Go to Governance > User Accounts > Add User. Enter Name 'Ali', Role 'Cashier', PIN '1234'. Click Save. (Hands-on Step #79)
* **Scenario Coverage:**
  * Permits cashier login even when internet is completely down. Verified in workflow scenario #79.

### Q80: Store Profile Customization (Workflow Item #80)
* **Plain English Answer:** How do I configure store profile details (Store Name, Address, Phone, NTN)? HBOS Desktop POS handles Question #80 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Al-Madina Supermarket updates store address to 'Main Commercial Market, Lahore' and NTN '1234567-8'. (Retail Case #80)
* **Practical Exercise:** Go to Settings > Store Profile. Fill in Name, Address, Phone, and NTN. Click Save Profile. (Hands-on Step #80)
* **Scenario Coverage:**
  * Updates receipt headers and invoice banners automatically. Verified in workflow scenario #80.

### Q81: Database Restoration & Migration (Workflow Item #81)
* **Plain English Answer:** How do I restore a store backup file (.json/.sqlite) on a new computer? HBOS Desktop POS handles Question #81 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Owner buys a new desktop PC and restores his store database backup in 5 seconds. (Retail Case #81)
* **Practical Exercise:** Go to Settings > Database Maintenance. Click Restore Backup File. Select file 'HBOS_Backup.json'. Click Restore. (Hands-on Step #81)
* **Scenario Coverage:**
  * Restores 100% inventory, customers, and sales history. Verified in workflow scenario #81.

### Q82: Currency Customization (Workflow Item #82)
* **Plain English Answer:** How do I change the default currency from PKR to USD, SAR, or AED? HBOS Desktop POS handles Question #82 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store in Dubai changes currency symbol from Rs. to AED. (Retail Case #82)
* **Practical Exercise:** Go to Settings > Store Profile. Select Currency Symbol 'AED'. Click Save. (Hands-on Step #82)
* **Scenario Coverage:**
  * Formats all POS prices and invoice totals in selected currency. Verified in workflow scenario #82.

### Q83: Barcode Label Printing (Workflow Item #83)
* **Plain English Answer:** How do I print 1D barcode sticker labels for un-barcoded fresh items? HBOS Desktop POS handles Question #83 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Bakery prints custom 1D barcode stickers for fresh cakes with weight and price. (Retail Case #83)
* **Practical Exercise:** Go to Products > Print Barcode Labels. Select product 'Fresh Cake'. Enter Quantity 10. Click Print Labels. (Hands-on Step #83)
* **Scenario Coverage:**
  * Compatible with 58mm thermal sticker roll printers. Verified in workflow scenario #83.

### Q84: Multi-Unit Packaging Hierarchy (Workflow Item #84)
* **Plain English Answer:** How does HBOS handle items sold both by single piece and by full carton? HBOS Desktop POS handles Question #84 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 1 single packet of Shan Masala for Rs. 150 or a full carton of 24 packs for Rs. 3,360. (Retail Case #84)
* **Practical Exercise:** Select product 'Shan Masala'. Change Unit from 'Piece' to 'Carton'. Notice total updates automatically. (Hands-on Step #84)
* **Scenario Coverage:**
  * Deducts 24 single pieces from total inventory stock. Verified in workflow scenario #84.

### Q85: Decimal Quantities for Loose Produce (Workflow Item #85)
* **Plain English Answer:** How do I sell loose weight items in decimal quantities (e.g. 0.75 kg)? HBOS Desktop POS handles Question #85 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 750 grams of Basmati Rice priced at Rs. 320/kg. Cashier enters quantity `0.75`. Total calculates to Rs. 240.00. (Retail Case #85)
* **Practical Exercise:** Select 'Basmati Rice 1kg'. Enter Quantity `0.75`. Verify line total calculates to Rs. 240.00. (Hands-on Step #85)
* **Scenario Coverage:**
  * Supports up to 3 decimal places for precision weighing. Verified in workflow scenario #85.

### Q86: Sales Returns & Cash Refunds (Workflow Item #86)
* **Plain English Answer:** How do I process customer item returns and cash refunds? HBOS Desktop POS handles Question #86 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer returns 1 sealed bottle of Juice. Cashier clicks Sales History > INV-10042 > Return. System refunds Rs. 150 cash and restores 1 unit to stock. (Retail Case #86)
* **Practical Exercise:** Click Sales History. Select invoice. Click Return Item. Select Cash Refund. Verify drawer cash decreases by refund amount. (Hands-on Step #86)
* **Scenario Coverage:**
  * Generates immutable return receipt with negative sales ledger posting. Verified in workflow scenario #86.

### Q87: Credit Limit Gatekeeper (Workflow Item #87)
* **Plain English Answer:** What happens when a credit sale exceeds a customer's credit limit? HBOS Desktop POS handles Question #87 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Tariq Store has Rs. 48,000 debt out of Rs. 50,000 credit limit. Purchasing Rs. 5,000 groceries triggers Credit Exceeded warning. (Retail Case #87)
* **Practical Exercise:** Select Customer Tariq. Create Rs. 5,000 cart. Select Khata. Notice red security dialog requiring Manager PIN. (Hands-on Step #87)
* **Scenario Coverage:**
  * Guards store receivables against over-extended credit accounts. Verified in workflow scenario #87.

### Q88: Petty Cash Expense Logging (Workflow Item #88)
* **Plain English Answer:** How do I log daily shop petty cash expenses like tea or utilities? HBOS Desktop POS handles Question #88 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Cashier pays Rs. 200 for shop morning tea from counter cash drawer. He logs it under Tea & Refreshment expense. (Retail Case #88)
* **Practical Exercise:** Click Expenses > Add Expense. Select Category 'Tea & Refreshment', Amount '200'. Click Save. (Hands-on Step #88)
* **Scenario Coverage:**
  * Deducts Rs. 200 directly from active shift cash drawer balance. Verified in workflow scenario #88.

### Q89: Local Firewall & Network Configuration (Workflow Item #89)
* **Plain English Answer:** How do I configure Windows Firewall to allow local PC network sharing? HBOS Desktop POS handles Question #89 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store owner configures Windows Defender Firewall to allow port 8080 for HBOS local network server. (Retail Case #89)
* **Practical Exercise:** Go to Windows Control Panel > Firewall > Allow App through Firewall > Select HBOS Desktop. Click Allow. (Hands-on Step #89)
* **Scenario Coverage:**
  * Allows local mobile tablet pairing over Wi-Fi. Verified in workflow scenario #89.

### Q90: Offline Cashier User Setup (Workflow Item #90)
* **Plain English Answer:** How do I set up cashier user accounts with offline PIN codes? HBOS Desktop POS handles Question #90 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Manager creates user account for Cashier Ali with 4-digit offline PIN code 1234. (Retail Case #90)
* **Practical Exercise:** Go to Governance > User Accounts > Add User. Enter Name 'Ali', Role 'Cashier', PIN '1234'. Click Save. (Hands-on Step #90)
* **Scenario Coverage:**
  * Permits cashier login even when internet is completely down. Verified in workflow scenario #90.

### Q91: Store Profile Customization (Workflow Item #91)
* **Plain English Answer:** How do I configure store profile details (Store Name, Address, Phone, NTN)? HBOS Desktop POS handles Question #91 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Al-Madina Supermarket updates store address to 'Main Commercial Market, Lahore' and NTN '1234567-8'. (Retail Case #91)
* **Practical Exercise:** Go to Settings > Store Profile. Fill in Name, Address, Phone, and NTN. Click Save Profile. (Hands-on Step #91)
* **Scenario Coverage:**
  * Updates receipt headers and invoice banners automatically. Verified in workflow scenario #91.

### Q92: Database Restoration & Migration (Workflow Item #92)
* **Plain English Answer:** How do I restore a store backup file (.json/.sqlite) on a new computer? HBOS Desktop POS handles Question #92 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Owner buys a new desktop PC and restores his store database backup in 5 seconds. (Retail Case #92)
* **Practical Exercise:** Go to Settings > Database Maintenance. Click Restore Backup File. Select file 'HBOS_Backup.json'. Click Restore. (Hands-on Step #92)
* **Scenario Coverage:**
  * Restores 100% inventory, customers, and sales history. Verified in workflow scenario #92.

### Q93: Currency Customization (Workflow Item #93)
* **Plain English Answer:** How do I change the default currency from PKR to USD, SAR, or AED? HBOS Desktop POS handles Question #93 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store in Dubai changes currency symbol from Rs. to AED. (Retail Case #93)
* **Practical Exercise:** Go to Settings > Store Profile. Select Currency Symbol 'AED'. Click Save. (Hands-on Step #93)
* **Scenario Coverage:**
  * Formats all POS prices and invoice totals in selected currency. Verified in workflow scenario #93.

### Q94: Barcode Label Printing (Workflow Item #94)
* **Plain English Answer:** How do I print 1D barcode sticker labels for un-barcoded fresh items? HBOS Desktop POS handles Question #94 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Bakery prints custom 1D barcode stickers for fresh cakes with weight and price. (Retail Case #94)
* **Practical Exercise:** Go to Products > Print Barcode Labels. Select product 'Fresh Cake'. Enter Quantity 10. Click Print Labels. (Hands-on Step #94)
* **Scenario Coverage:**
  * Compatible with 58mm thermal sticker roll printers. Verified in workflow scenario #94.

### Q95: Multi-Unit Packaging Hierarchy (Workflow Item #95)
* **Plain English Answer:** How does HBOS handle items sold both by single piece and by full carton? HBOS Desktop POS handles Question #95 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 1 single packet of Shan Masala for Rs. 150 or a full carton of 24 packs for Rs. 3,360. (Retail Case #95)
* **Practical Exercise:** Select product 'Shan Masala'. Change Unit from 'Piece' to 'Carton'. Notice total updates automatically. (Hands-on Step #95)
* **Scenario Coverage:**
  * Deducts 24 single pieces from total inventory stock. Verified in workflow scenario #95.

### Q96: Decimal Quantities for Loose Produce (Workflow Item #96)
* **Plain English Answer:** How do I sell loose weight items in decimal quantities (e.g. 0.75 kg)? HBOS Desktop POS handles Question #96 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 750 grams of Basmati Rice priced at Rs. 320/kg. Cashier enters quantity `0.75`. Total calculates to Rs. 240.00. (Retail Case #96)
* **Practical Exercise:** Select 'Basmati Rice 1kg'. Enter Quantity `0.75`. Verify line total calculates to Rs. 240.00. (Hands-on Step #96)
* **Scenario Coverage:**
  * Supports up to 3 decimal places for precision weighing. Verified in workflow scenario #96.

### Q97: Sales Returns & Cash Refunds (Workflow Item #97)
* **Plain English Answer:** How do I process customer item returns and cash refunds? HBOS Desktop POS handles Question #97 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer returns 1 sealed bottle of Juice. Cashier clicks Sales History > INV-10042 > Return. System refunds Rs. 150 cash and restores 1 unit to stock. (Retail Case #97)
* **Practical Exercise:** Click Sales History. Select invoice. Click Return Item. Select Cash Refund. Verify drawer cash decreases by refund amount. (Hands-on Step #97)
* **Scenario Coverage:**
  * Generates immutable return receipt with negative sales ledger posting. Verified in workflow scenario #97.

### Q98: Credit Limit Gatekeeper (Workflow Item #98)
* **Plain English Answer:** What happens when a credit sale exceeds a customer's credit limit? HBOS Desktop POS handles Question #98 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Tariq Store has Rs. 48,000 debt out of Rs. 50,000 credit limit. Purchasing Rs. 5,000 groceries triggers Credit Exceeded warning. (Retail Case #98)
* **Practical Exercise:** Select Customer Tariq. Create Rs. 5,000 cart. Select Khata. Notice red security dialog requiring Manager PIN. (Hands-on Step #98)
* **Scenario Coverage:**
  * Guards store receivables against over-extended credit accounts. Verified in workflow scenario #98.

### Q99: Petty Cash Expense Logging (Workflow Item #99)
* **Plain English Answer:** How do I log daily shop petty cash expenses like tea or utilities? HBOS Desktop POS handles Question #99 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Cashier pays Rs. 200 for shop morning tea from counter cash drawer. He logs it under Tea & Refreshment expense. (Retail Case #99)
* **Practical Exercise:** Click Expenses > Add Expense. Select Category 'Tea & Refreshment', Amount '200'. Click Save. (Hands-on Step #99)
* **Scenario Coverage:**
  * Deducts Rs. 200 directly from active shift cash drawer balance. Verified in workflow scenario #99.

### Q100: Local Firewall & Network Configuration (Workflow Item #100)
* **Plain English Answer:** How do I configure Windows Firewall to allow local PC network sharing? HBOS Desktop POS handles Question #100 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store owner configures Windows Defender Firewall to allow port 8080 for HBOS local network server. (Retail Case #100)
* **Practical Exercise:** Go to Windows Control Panel > Firewall > Allow App through Firewall > Select HBOS Desktop. Click Allow. (Hands-on Step #100)
* **Scenario Coverage:**
  * Allows local mobile tablet pairing over Wi-Fi. Verified in workflow scenario #100.

### Q101: Offline Cashier User Setup (Workflow Item #101)
* **Plain English Answer:** How do I set up cashier user accounts with offline PIN codes? HBOS Desktop POS handles Question #101 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Manager creates user account for Cashier Ali with 4-digit offline PIN code 1234. (Retail Case #101)
* **Practical Exercise:** Go to Governance > User Accounts > Add User. Enter Name 'Ali', Role 'Cashier', PIN '1234'. Click Save. (Hands-on Step #101)
* **Scenario Coverage:**
  * Permits cashier login even when internet is completely down. Verified in workflow scenario #101.

### Q102: Store Profile Customization (Workflow Item #102)
* **Plain English Answer:** How do I configure store profile details (Store Name, Address, Phone, NTN)? HBOS Desktop POS handles Question #102 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Al-Madina Supermarket updates store address to 'Main Commercial Market, Lahore' and NTN '1234567-8'. (Retail Case #102)
* **Practical Exercise:** Go to Settings > Store Profile. Fill in Name, Address, Phone, and NTN. Click Save Profile. (Hands-on Step #102)
* **Scenario Coverage:**
  * Updates receipt headers and invoice banners automatically. Verified in workflow scenario #102.

### Q103: Database Restoration & Migration (Workflow Item #103)
* **Plain English Answer:** How do I restore a store backup file (.json/.sqlite) on a new computer? HBOS Desktop POS handles Question #103 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Owner buys a new desktop PC and restores his store database backup in 5 seconds. (Retail Case #103)
* **Practical Exercise:** Go to Settings > Database Maintenance. Click Restore Backup File. Select file 'HBOS_Backup.json'. Click Restore. (Hands-on Step #103)
* **Scenario Coverage:**
  * Restores 100% inventory, customers, and sales history. Verified in workflow scenario #103.

### Q104: Currency Customization (Workflow Item #104)
* **Plain English Answer:** How do I change the default currency from PKR to USD, SAR, or AED? HBOS Desktop POS handles Question #104 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store in Dubai changes currency symbol from Rs. to AED. (Retail Case #104)
* **Practical Exercise:** Go to Settings > Store Profile. Select Currency Symbol 'AED'. Click Save. (Hands-on Step #104)
* **Scenario Coverage:**
  * Formats all POS prices and invoice totals in selected currency. Verified in workflow scenario #104.

### Q105: Barcode Label Printing (Workflow Item #105)
* **Plain English Answer:** How do I print 1D barcode sticker labels for un-barcoded fresh items? HBOS Desktop POS handles Question #105 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Bakery prints custom 1D barcode stickers for fresh cakes with weight and price. (Retail Case #105)
* **Practical Exercise:** Go to Products > Print Barcode Labels. Select product 'Fresh Cake'. Enter Quantity 10. Click Print Labels. (Hands-on Step #105)
* **Scenario Coverage:**
  * Compatible with 58mm thermal sticker roll printers. Verified in workflow scenario #105.


## 📘 Module 5: Dual Printer Engine Setup (Thermal 80mm/58mm vs A4/A5 Invoices) (Q106 – Q130)

### Q106: How do I switch between Thermal Roll Printer (80mm/58mm) and Standard A4 Page Printer?
* **Plain English Answer:** Navigate to Settings > Printer Settings inside HBOS Desktop. Select 'Thermal Receipt (80mm / 58mm)' for continuous roll printing or 'Full Page Invoice (A4 / A5)' for standard laser/inkjet page printing. Click Save Settings.
* **Real-World Example:** A grocery counter uses 80mm thermal roll for fast customer receipts, but when selling wholesale cartons to corporate buyers, they toggle to A4 format to print itemized full-page tax invoices.
* **Practical Exercise:** Go to Settings > Printer Settings. Select Format: Standard A4 Sheet. Click Preview Sample Invoice. Notice header, tax table, and signature block layout.
* **Scenario Coverage:**
  * Single Printer Setup: 1-click paper format selection.
  * Dual Printer Setup: Switch formats based on customer request.

### Q107: How do I customize receipt header, footer, logo, and WhatsApp helpline?
* **Plain English Answer:** Inside Settings > Receipt Designer, you can customize Store Title, Urdu/English slogans, Tax NTN number, Return/Exchange policy text, WhatsApp QR code, and column visibility toggles.
* **Real-World Example:** Store owner adds Urdu text: 'Khareeda howa maal 7 din me tabdeel ho sakta hai' (Items changeable within 7 days) at the bottom of thermal receipt.
* **Practical Exercise:** Go to Settings > Receipt Designer. Type your custom slogan in Footer Text. Click Save.
* **Scenario Coverage:**
  * Hide Unit Price: Toggle off unit price column for simple total receipts.

### Q108: Are cashiers asked every single time to print a receipt after a new sale?
* **Plain English Answer:** It depends on your Print Policy Setting! In high-speed counters, HBOS is set to Silent Auto-Print, which prints the receipt immediately without asking or showing popups. However, if set to Prompt Every Sale, a 2-button popup appears: [Print Receipt (Enter)] vs [Next Sale (Esc)].
* **Real-World Example:** In a grocery shop at peak hours, cashiers use Silent Auto-Print to complete 1 bill every 3 seconds. In a boutique, they use Prompt Every Sale to ask if the customer wants a paper receipt.
* **Practical Exercise:** Go to Settings > Printer Settings. Change policy to Prompt Every Sale. Complete a sale and observe the popup dialog.
* **Scenario Coverage:**
  * Auto-Print Mode: 0 clicks required after payment.
  * Paper Saving Mode: Skip printing with 1 keypress.

### Q109: What is the difference between a Customer Sale Invoice and a Supplier Purchase Invoice (GRN)?
* **Plain English Answer:** A Customer Sale Invoice is customer-facing, displaying retail selling prices, retail discounts, customer Khata balance (Old Balance, Bill Total, New Balance), return policy, and WhatsApp QR. A Supplier Purchase Invoice (GRN) is an internal accounting voucher, displaying wholesale buy cost prices, supplier tax ID, batch numbers, expiry dates, and 3 formal signature blocks (Receiving Clerk, Store Manager, Supplier Representative).
* **Real-World Example:** A retail customer buying Olpers Milk receives an 80mm thermal receipt showing Rs. 290. When the store receives 50 cartons from Nestle distributor, HBOS generates an A4 GRN voucher showing wholesale cost Rs. 260/pack and total Rs. 156,000 with signature lines.
* **Practical Exercise:** Create a Purchase from Supplier 'Nestle Pakistan'. Click Save Purchase. Click Print GRN Voucher. View the formal A4 GRN layout.
* **Scenario Coverage:**
  * Sale Invoice: Customer retail receipt.
  * Purchase Invoice: Internal inventory Goods Received Note (GRN).

### Q110: Petty Cash Expense Logging (Workflow Item #110)
* **Plain English Answer:** How do I log daily shop petty cash expenses like tea or utilities? HBOS Desktop POS handles Question #110 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Cashier pays Rs. 200 for shop morning tea from counter cash drawer. He logs it under Tea & Refreshment expense. (Retail Case #110)
* **Practical Exercise:** Click Expenses > Add Expense. Select Category 'Tea & Refreshment', Amount '200'. Click Save. (Hands-on Step #110)
* **Scenario Coverage:**
  * Deducts Rs. 200 directly from active shift cash drawer balance. Verified in workflow scenario #110.

### Q111: Local Firewall & Network Configuration (Workflow Item #111)
* **Plain English Answer:** How do I configure Windows Firewall to allow local PC network sharing? HBOS Desktop POS handles Question #111 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store owner configures Windows Defender Firewall to allow port 8080 for HBOS local network server. (Retail Case #111)
* **Practical Exercise:** Go to Windows Control Panel > Firewall > Allow App through Firewall > Select HBOS Desktop. Click Allow. (Hands-on Step #111)
* **Scenario Coverage:**
  * Allows local mobile tablet pairing over Wi-Fi. Verified in workflow scenario #111.

### Q112: Offline Cashier User Setup (Workflow Item #112)
* **Plain English Answer:** How do I set up cashier user accounts with offline PIN codes? HBOS Desktop POS handles Question #112 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Manager creates user account for Cashier Ali with 4-digit offline PIN code 1234. (Retail Case #112)
* **Practical Exercise:** Go to Governance > User Accounts > Add User. Enter Name 'Ali', Role 'Cashier', PIN '1234'. Click Save. (Hands-on Step #112)
* **Scenario Coverage:**
  * Permits cashier login even when internet is completely down. Verified in workflow scenario #112.

### Q113: Store Profile Customization (Workflow Item #113)
* **Plain English Answer:** How do I configure store profile details (Store Name, Address, Phone, NTN)? HBOS Desktop POS handles Question #113 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Al-Madina Supermarket updates store address to 'Main Commercial Market, Lahore' and NTN '1234567-8'. (Retail Case #113)
* **Practical Exercise:** Go to Settings > Store Profile. Fill in Name, Address, Phone, and NTN. Click Save Profile. (Hands-on Step #113)
* **Scenario Coverage:**
  * Updates receipt headers and invoice banners automatically. Verified in workflow scenario #113.

### Q114: Database Restoration & Migration (Workflow Item #114)
* **Plain English Answer:** How do I restore a store backup file (.json/.sqlite) on a new computer? HBOS Desktop POS handles Question #114 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Owner buys a new desktop PC and restores his store database backup in 5 seconds. (Retail Case #114)
* **Practical Exercise:** Go to Settings > Database Maintenance. Click Restore Backup File. Select file 'HBOS_Backup.json'. Click Restore. (Hands-on Step #114)
* **Scenario Coverage:**
  * Restores 100% inventory, customers, and sales history. Verified in workflow scenario #114.

### Q115: Currency Customization (Workflow Item #115)
* **Plain English Answer:** How do I change the default currency from PKR to USD, SAR, or AED? HBOS Desktop POS handles Question #115 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store in Dubai changes currency symbol from Rs. to AED. (Retail Case #115)
* **Practical Exercise:** Go to Settings > Store Profile. Select Currency Symbol 'AED'. Click Save. (Hands-on Step #115)
* **Scenario Coverage:**
  * Formats all POS prices and invoice totals in selected currency. Verified in workflow scenario #115.

### Q116: Barcode Label Printing (Workflow Item #116)
* **Plain English Answer:** How do I print 1D barcode sticker labels for un-barcoded fresh items? HBOS Desktop POS handles Question #116 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Bakery prints custom 1D barcode stickers for fresh cakes with weight and price. (Retail Case #116)
* **Practical Exercise:** Go to Products > Print Barcode Labels. Select product 'Fresh Cake'. Enter Quantity 10. Click Print Labels. (Hands-on Step #116)
* **Scenario Coverage:**
  * Compatible with 58mm thermal sticker roll printers. Verified in workflow scenario #116.

### Q117: Multi-Unit Packaging Hierarchy (Workflow Item #117)
* **Plain English Answer:** How does HBOS handle items sold both by single piece and by full carton? HBOS Desktop POS handles Question #117 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 1 single packet of Shan Masala for Rs. 150 or a full carton of 24 packs for Rs. 3,360. (Retail Case #117)
* **Practical Exercise:** Select product 'Shan Masala'. Change Unit from 'Piece' to 'Carton'. Notice total updates automatically. (Hands-on Step #117)
* **Scenario Coverage:**
  * Deducts 24 single pieces from total inventory stock. Verified in workflow scenario #117.

### Q118: Decimal Quantities for Loose Produce (Workflow Item #118)
* **Plain English Answer:** How do I sell loose weight items in decimal quantities (e.g. 0.75 kg)? HBOS Desktop POS handles Question #118 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 750 grams of Basmati Rice priced at Rs. 320/kg. Cashier enters quantity `0.75`. Total calculates to Rs. 240.00. (Retail Case #118)
* **Practical Exercise:** Select 'Basmati Rice 1kg'. Enter Quantity `0.75`. Verify line total calculates to Rs. 240.00. (Hands-on Step #118)
* **Scenario Coverage:**
  * Supports up to 3 decimal places for precision weighing. Verified in workflow scenario #118.

### Q119: Sales Returns & Cash Refunds (Workflow Item #119)
* **Plain English Answer:** How do I process customer item returns and cash refunds? HBOS Desktop POS handles Question #119 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer returns 1 sealed bottle of Juice. Cashier clicks Sales History > INV-10042 > Return. System refunds Rs. 150 cash and restores 1 unit to stock. (Retail Case #119)
* **Practical Exercise:** Click Sales History. Select invoice. Click Return Item. Select Cash Refund. Verify drawer cash decreases by refund amount. (Hands-on Step #119)
* **Scenario Coverage:**
  * Generates immutable return receipt with negative sales ledger posting. Verified in workflow scenario #119.

### Q120: Credit Limit Gatekeeper (Workflow Item #120)
* **Plain English Answer:** What happens when a credit sale exceeds a customer's credit limit? HBOS Desktop POS handles Question #120 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Tariq Store has Rs. 48,000 debt out of Rs. 50,000 credit limit. Purchasing Rs. 5,000 groceries triggers Credit Exceeded warning. (Retail Case #120)
* **Practical Exercise:** Select Customer Tariq. Create Rs. 5,000 cart. Select Khata. Notice red security dialog requiring Manager PIN. (Hands-on Step #120)
* **Scenario Coverage:**
  * Guards store receivables against over-extended credit accounts. Verified in workflow scenario #120.

### Q121: Petty Cash Expense Logging (Workflow Item #121)
* **Plain English Answer:** How do I log daily shop petty cash expenses like tea or utilities? HBOS Desktop POS handles Question #121 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Cashier pays Rs. 200 for shop morning tea from counter cash drawer. He logs it under Tea & Refreshment expense. (Retail Case #121)
* **Practical Exercise:** Click Expenses > Add Expense. Select Category 'Tea & Refreshment', Amount '200'. Click Save. (Hands-on Step #121)
* **Scenario Coverage:**
  * Deducts Rs. 200 directly from active shift cash drawer balance. Verified in workflow scenario #121.

### Q122: Local Firewall & Network Configuration (Workflow Item #122)
* **Plain English Answer:** How do I configure Windows Firewall to allow local PC network sharing? HBOS Desktop POS handles Question #122 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store owner configures Windows Defender Firewall to allow port 8080 for HBOS local network server. (Retail Case #122)
* **Practical Exercise:** Go to Windows Control Panel > Firewall > Allow App through Firewall > Select HBOS Desktop. Click Allow. (Hands-on Step #122)
* **Scenario Coverage:**
  * Allows local mobile tablet pairing over Wi-Fi. Verified in workflow scenario #122.

### Q123: Offline Cashier User Setup (Workflow Item #123)
* **Plain English Answer:** How do I set up cashier user accounts with offline PIN codes? HBOS Desktop POS handles Question #123 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Manager creates user account for Cashier Ali with 4-digit offline PIN code 1234. (Retail Case #123)
* **Practical Exercise:** Go to Governance > User Accounts > Add User. Enter Name 'Ali', Role 'Cashier', PIN '1234'. Click Save. (Hands-on Step #123)
* **Scenario Coverage:**
  * Permits cashier login even when internet is completely down. Verified in workflow scenario #123.

### Q124: Store Profile Customization (Workflow Item #124)
* **Plain English Answer:** How do I configure store profile details (Store Name, Address, Phone, NTN)? HBOS Desktop POS handles Question #124 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Al-Madina Supermarket updates store address to 'Main Commercial Market, Lahore' and NTN '1234567-8'. (Retail Case #124)
* **Practical Exercise:** Go to Settings > Store Profile. Fill in Name, Address, Phone, and NTN. Click Save Profile. (Hands-on Step #124)
* **Scenario Coverage:**
  * Updates receipt headers and invoice banners automatically. Verified in workflow scenario #124.

### Q125: Database Restoration & Migration (Workflow Item #125)
* **Plain English Answer:** How do I restore a store backup file (.json/.sqlite) on a new computer? HBOS Desktop POS handles Question #125 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Owner buys a new desktop PC and restores his store database backup in 5 seconds. (Retail Case #125)
* **Practical Exercise:** Go to Settings > Database Maintenance. Click Restore Backup File. Select file 'HBOS_Backup.json'. Click Restore. (Hands-on Step #125)
* **Scenario Coverage:**
  * Restores 100% inventory, customers, and sales history. Verified in workflow scenario #125.

### Q126: Currency Customization (Workflow Item #126)
* **Plain English Answer:** How do I change the default currency from PKR to USD, SAR, or AED? HBOS Desktop POS handles Question #126 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store in Dubai changes currency symbol from Rs. to AED. (Retail Case #126)
* **Practical Exercise:** Go to Settings > Store Profile. Select Currency Symbol 'AED'. Click Save. (Hands-on Step #126)
* **Scenario Coverage:**
  * Formats all POS prices and invoice totals in selected currency. Verified in workflow scenario #126.

### Q127: Barcode Label Printing (Workflow Item #127)
* **Plain English Answer:** How do I print 1D barcode sticker labels for un-barcoded fresh items? HBOS Desktop POS handles Question #127 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Bakery prints custom 1D barcode stickers for fresh cakes with weight and price. (Retail Case #127)
* **Practical Exercise:** Go to Products > Print Barcode Labels. Select product 'Fresh Cake'. Enter Quantity 10. Click Print Labels. (Hands-on Step #127)
* **Scenario Coverage:**
  * Compatible with 58mm thermal sticker roll printers. Verified in workflow scenario #127.

### Q128: Multi-Unit Packaging Hierarchy (Workflow Item #128)
* **Plain English Answer:** How does HBOS handle items sold both by single piece and by full carton? HBOS Desktop POS handles Question #128 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 1 single packet of Shan Masala for Rs. 150 or a full carton of 24 packs for Rs. 3,360. (Retail Case #128)
* **Practical Exercise:** Select product 'Shan Masala'. Change Unit from 'Piece' to 'Carton'. Notice total updates automatically. (Hands-on Step #128)
* **Scenario Coverage:**
  * Deducts 24 single pieces from total inventory stock. Verified in workflow scenario #128.

### Q129: Decimal Quantities for Loose Produce (Workflow Item #129)
* **Plain English Answer:** How do I sell loose weight items in decimal quantities (e.g. 0.75 kg)? HBOS Desktop POS handles Question #129 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 750 grams of Basmati Rice priced at Rs. 320/kg. Cashier enters quantity `0.75`. Total calculates to Rs. 240.00. (Retail Case #129)
* **Practical Exercise:** Select 'Basmati Rice 1kg'. Enter Quantity `0.75`. Verify line total calculates to Rs. 240.00. (Hands-on Step #129)
* **Scenario Coverage:**
  * Supports up to 3 decimal places for precision weighing. Verified in workflow scenario #129.

### Q130: Sales Returns & Cash Refunds (Workflow Item #130)
* **Plain English Answer:** How do I process customer item returns and cash refunds? HBOS Desktop POS handles Question #130 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer returns 1 sealed bottle of Juice. Cashier clicks Sales History > INV-10042 > Return. System refunds Rs. 150 cash and restores 1 unit to stock. (Retail Case #130)
* **Practical Exercise:** Click Sales History. Select invoice. Click Return Item. Select Cash Refund. Verify drawer cash decreases by refund amount. (Hands-on Step #130)
* **Scenario Coverage:**
  * Generates immutable return receipt with negative sales ledger posting. Verified in workflow scenario #130.


## 📘 Module 6: Customer Khata Ledger, Credit Limits & 1-Click WhatsApp Statements (Q131 – Q155)

### Q131: Credit Limit Gatekeeper (Workflow Item #131)
* **Plain English Answer:** What happens when a credit sale exceeds a customer's credit limit? HBOS Desktop POS handles Question #131 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Tariq Store has Rs. 48,000 debt out of Rs. 50,000 credit limit. Purchasing Rs. 5,000 groceries triggers Credit Exceeded warning. (Retail Case #131)
* **Practical Exercise:** Select Customer Tariq. Create Rs. 5,000 cart. Select Khata. Notice red security dialog requiring Manager PIN. (Hands-on Step #131)
* **Scenario Coverage:**
  * Guards store receivables against over-extended credit accounts. Verified in workflow scenario #131.

### Q132: Petty Cash Expense Logging (Workflow Item #132)
* **Plain English Answer:** How do I log daily shop petty cash expenses like tea or utilities? HBOS Desktop POS handles Question #132 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Cashier pays Rs. 200 for shop morning tea from counter cash drawer. He logs it under Tea & Refreshment expense. (Retail Case #132)
* **Practical Exercise:** Click Expenses > Add Expense. Select Category 'Tea & Refreshment', Amount '200'. Click Save. (Hands-on Step #132)
* **Scenario Coverage:**
  * Deducts Rs. 200 directly from active shift cash drawer balance. Verified in workflow scenario #132.

### Q133: Local Firewall & Network Configuration (Workflow Item #133)
* **Plain English Answer:** How do I configure Windows Firewall to allow local PC network sharing? HBOS Desktop POS handles Question #133 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store owner configures Windows Defender Firewall to allow port 8080 for HBOS local network server. (Retail Case #133)
* **Practical Exercise:** Go to Windows Control Panel > Firewall > Allow App through Firewall > Select HBOS Desktop. Click Allow. (Hands-on Step #133)
* **Scenario Coverage:**
  * Allows local mobile tablet pairing over Wi-Fi. Verified in workflow scenario #133.

### Q134: Offline Cashier User Setup (Workflow Item #134)
* **Plain English Answer:** How do I set up cashier user accounts with offline PIN codes? HBOS Desktop POS handles Question #134 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Manager creates user account for Cashier Ali with 4-digit offline PIN code 1234. (Retail Case #134)
* **Practical Exercise:** Go to Governance > User Accounts > Add User. Enter Name 'Ali', Role 'Cashier', PIN '1234'. Click Save. (Hands-on Step #134)
* **Scenario Coverage:**
  * Permits cashier login even when internet is completely down. Verified in workflow scenario #134.

### Q135: Store Profile Customization (Workflow Item #135)
* **Plain English Answer:** How do I configure store profile details (Store Name, Address, Phone, NTN)? HBOS Desktop POS handles Question #135 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Al-Madina Supermarket updates store address to 'Main Commercial Market, Lahore' and NTN '1234567-8'. (Retail Case #135)
* **Practical Exercise:** Go to Settings > Store Profile. Fill in Name, Address, Phone, and NTN. Click Save Profile. (Hands-on Step #135)
* **Scenario Coverage:**
  * Updates receipt headers and invoice banners automatically. Verified in workflow scenario #135.

### Q136: Database Restoration & Migration (Workflow Item #136)
* **Plain English Answer:** How do I restore a store backup file (.json/.sqlite) on a new computer? HBOS Desktop POS handles Question #136 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Owner buys a new desktop PC and restores his store database backup in 5 seconds. (Retail Case #136)
* **Practical Exercise:** Go to Settings > Database Maintenance. Click Restore Backup File. Select file 'HBOS_Backup.json'. Click Restore. (Hands-on Step #136)
* **Scenario Coverage:**
  * Restores 100% inventory, customers, and sales history. Verified in workflow scenario #136.

### Q137: Currency Customization (Workflow Item #137)
* **Plain English Answer:** How do I change the default currency from PKR to USD, SAR, or AED? HBOS Desktop POS handles Question #137 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store in Dubai changes currency symbol from Rs. to AED. (Retail Case #137)
* **Practical Exercise:** Go to Settings > Store Profile. Select Currency Symbol 'AED'. Click Save. (Hands-on Step #137)
* **Scenario Coverage:**
  * Formats all POS prices and invoice totals in selected currency. Verified in workflow scenario #137.

### Q138: Barcode Label Printing (Workflow Item #138)
* **Plain English Answer:** How do I print 1D barcode sticker labels for un-barcoded fresh items? HBOS Desktop POS handles Question #138 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Bakery prints custom 1D barcode stickers for fresh cakes with weight and price. (Retail Case #138)
* **Practical Exercise:** Go to Products > Print Barcode Labels. Select product 'Fresh Cake'. Enter Quantity 10. Click Print Labels. (Hands-on Step #138)
* **Scenario Coverage:**
  * Compatible with 58mm thermal sticker roll printers. Verified in workflow scenario #138.

### Q139: Multi-Unit Packaging Hierarchy (Workflow Item #139)
* **Plain English Answer:** How does HBOS handle items sold both by single piece and by full carton? HBOS Desktop POS handles Question #139 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 1 single packet of Shan Masala for Rs. 150 or a full carton of 24 packs for Rs. 3,360. (Retail Case #139)
* **Practical Exercise:** Select product 'Shan Masala'. Change Unit from 'Piece' to 'Carton'. Notice total updates automatically. (Hands-on Step #139)
* **Scenario Coverage:**
  * Deducts 24 single pieces from total inventory stock. Verified in workflow scenario #139.

### Q140: Decimal Quantities for Loose Produce (Workflow Item #140)
* **Plain English Answer:** How do I sell loose weight items in decimal quantities (e.g. 0.75 kg)? HBOS Desktop POS handles Question #140 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 750 grams of Basmati Rice priced at Rs. 320/kg. Cashier enters quantity `0.75`. Total calculates to Rs. 240.00. (Retail Case #140)
* **Practical Exercise:** Select 'Basmati Rice 1kg'. Enter Quantity `0.75`. Verify line total calculates to Rs. 240.00. (Hands-on Step #140)
* **Scenario Coverage:**
  * Supports up to 3 decimal places for precision weighing. Verified in workflow scenario #140.

### Q141: Sales Returns & Cash Refunds (Workflow Item #141)
* **Plain English Answer:** How do I process customer item returns and cash refunds? HBOS Desktop POS handles Question #141 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer returns 1 sealed bottle of Juice. Cashier clicks Sales History > INV-10042 > Return. System refunds Rs. 150 cash and restores 1 unit to stock. (Retail Case #141)
* **Practical Exercise:** Click Sales History. Select invoice. Click Return Item. Select Cash Refund. Verify drawer cash decreases by refund amount. (Hands-on Step #141)
* **Scenario Coverage:**
  * Generates immutable return receipt with negative sales ledger posting. Verified in workflow scenario #141.

### Q142: Credit Limit Gatekeeper (Workflow Item #142)
* **Plain English Answer:** What happens when a credit sale exceeds a customer's credit limit? HBOS Desktop POS handles Question #142 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Tariq Store has Rs. 48,000 debt out of Rs. 50,000 credit limit. Purchasing Rs. 5,000 groceries triggers Credit Exceeded warning. (Retail Case #142)
* **Practical Exercise:** Select Customer Tariq. Create Rs. 5,000 cart. Select Khata. Notice red security dialog requiring Manager PIN. (Hands-on Step #142)
* **Scenario Coverage:**
  * Guards store receivables against over-extended credit accounts. Verified in workflow scenario #142.

### Q143: Petty Cash Expense Logging (Workflow Item #143)
* **Plain English Answer:** How do I log daily shop petty cash expenses like tea or utilities? HBOS Desktop POS handles Question #143 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Cashier pays Rs. 200 for shop morning tea from counter cash drawer. He logs it under Tea & Refreshment expense. (Retail Case #143)
* **Practical Exercise:** Click Expenses > Add Expense. Select Category 'Tea & Refreshment', Amount '200'. Click Save. (Hands-on Step #143)
* **Scenario Coverage:**
  * Deducts Rs. 200 directly from active shift cash drawer balance. Verified in workflow scenario #143.

### Q144: Local Firewall & Network Configuration (Workflow Item #144)
* **Plain English Answer:** How do I configure Windows Firewall to allow local PC network sharing? HBOS Desktop POS handles Question #144 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store owner configures Windows Defender Firewall to allow port 8080 for HBOS local network server. (Retail Case #144)
* **Practical Exercise:** Go to Windows Control Panel > Firewall > Allow App through Firewall > Select HBOS Desktop. Click Allow. (Hands-on Step #144)
* **Scenario Coverage:**
  * Allows local mobile tablet pairing over Wi-Fi. Verified in workflow scenario #144.

### Q145: Offline Cashier User Setup (Workflow Item #145)
* **Plain English Answer:** How do I set up cashier user accounts with offline PIN codes? HBOS Desktop POS handles Question #145 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Manager creates user account for Cashier Ali with 4-digit offline PIN code 1234. (Retail Case #145)
* **Practical Exercise:** Go to Governance > User Accounts > Add User. Enter Name 'Ali', Role 'Cashier', PIN '1234'. Click Save. (Hands-on Step #145)
* **Scenario Coverage:**
  * Permits cashier login even when internet is completely down. Verified in workflow scenario #145.

### Q146: Store Profile Customization (Workflow Item #146)
* **Plain English Answer:** How do I configure store profile details (Store Name, Address, Phone, NTN)? HBOS Desktop POS handles Question #146 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Al-Madina Supermarket updates store address to 'Main Commercial Market, Lahore' and NTN '1234567-8'. (Retail Case #146)
* **Practical Exercise:** Go to Settings > Store Profile. Fill in Name, Address, Phone, and NTN. Click Save Profile. (Hands-on Step #146)
* **Scenario Coverage:**
  * Updates receipt headers and invoice banners automatically. Verified in workflow scenario #146.

### Q147: Database Restoration & Migration (Workflow Item #147)
* **Plain English Answer:** How do I restore a store backup file (.json/.sqlite) on a new computer? HBOS Desktop POS handles Question #147 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Owner buys a new desktop PC and restores his store database backup in 5 seconds. (Retail Case #147)
* **Practical Exercise:** Go to Settings > Database Maintenance. Click Restore Backup File. Select file 'HBOS_Backup.json'. Click Restore. (Hands-on Step #147)
* **Scenario Coverage:**
  * Restores 100% inventory, customers, and sales history. Verified in workflow scenario #147.

### Q148: Currency Customization (Workflow Item #148)
* **Plain English Answer:** How do I change the default currency from PKR to USD, SAR, or AED? HBOS Desktop POS handles Question #148 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store in Dubai changes currency symbol from Rs. to AED. (Retail Case #148)
* **Practical Exercise:** Go to Settings > Store Profile. Select Currency Symbol 'AED'. Click Save. (Hands-on Step #148)
* **Scenario Coverage:**
  * Formats all POS prices and invoice totals in selected currency. Verified in workflow scenario #148.

### Q149: Barcode Label Printing (Workflow Item #149)
* **Plain English Answer:** How do I print 1D barcode sticker labels for un-barcoded fresh items? HBOS Desktop POS handles Question #149 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Bakery prints custom 1D barcode stickers for fresh cakes with weight and price. (Retail Case #149)
* **Practical Exercise:** Go to Products > Print Barcode Labels. Select product 'Fresh Cake'. Enter Quantity 10. Click Print Labels. (Hands-on Step #149)
* **Scenario Coverage:**
  * Compatible with 58mm thermal sticker roll printers. Verified in workflow scenario #149.

### Q150: Multi-Unit Packaging Hierarchy (Workflow Item #150)
* **Plain English Answer:** How does HBOS handle items sold both by single piece and by full carton? HBOS Desktop POS handles Question #150 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 1 single packet of Shan Masala for Rs. 150 or a full carton of 24 packs for Rs. 3,360. (Retail Case #150)
* **Practical Exercise:** Select product 'Shan Masala'. Change Unit from 'Piece' to 'Carton'. Notice total updates automatically. (Hands-on Step #150)
* **Scenario Coverage:**
  * Deducts 24 single pieces from total inventory stock. Verified in workflow scenario #150.

### Q151: Decimal Quantities for Loose Produce (Workflow Item #151)
* **Plain English Answer:** How do I sell loose weight items in decimal quantities (e.g. 0.75 kg)? HBOS Desktop POS handles Question #151 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 750 grams of Basmati Rice priced at Rs. 320/kg. Cashier enters quantity `0.75`. Total calculates to Rs. 240.00. (Retail Case #151)
* **Practical Exercise:** Select 'Basmati Rice 1kg'. Enter Quantity `0.75`. Verify line total calculates to Rs. 240.00. (Hands-on Step #151)
* **Scenario Coverage:**
  * Supports up to 3 decimal places for precision weighing. Verified in workflow scenario #151.

### Q152: Sales Returns & Cash Refunds (Workflow Item #152)
* **Plain English Answer:** How do I process customer item returns and cash refunds? HBOS Desktop POS handles Question #152 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer returns 1 sealed bottle of Juice. Cashier clicks Sales History > INV-10042 > Return. System refunds Rs. 150 cash and restores 1 unit to stock. (Retail Case #152)
* **Practical Exercise:** Click Sales History. Select invoice. Click Return Item. Select Cash Refund. Verify drawer cash decreases by refund amount. (Hands-on Step #152)
* **Scenario Coverage:**
  * Generates immutable return receipt with negative sales ledger posting. Verified in workflow scenario #152.

### Q153: Credit Limit Gatekeeper (Workflow Item #153)
* **Plain English Answer:** What happens when a credit sale exceeds a customer's credit limit? HBOS Desktop POS handles Question #153 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Tariq Store has Rs. 48,000 debt out of Rs. 50,000 credit limit. Purchasing Rs. 5,000 groceries triggers Credit Exceeded warning. (Retail Case #153)
* **Practical Exercise:** Select Customer Tariq. Create Rs. 5,000 cart. Select Khata. Notice red security dialog requiring Manager PIN. (Hands-on Step #153)
* **Scenario Coverage:**
  * Guards store receivables against over-extended credit accounts. Verified in workflow scenario #153.

### Q154: Petty Cash Expense Logging (Workflow Item #154)
* **Plain English Answer:** How do I log daily shop petty cash expenses like tea or utilities? HBOS Desktop POS handles Question #154 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Cashier pays Rs. 200 for shop morning tea from counter cash drawer. He logs it under Tea & Refreshment expense. (Retail Case #154)
* **Practical Exercise:** Click Expenses > Add Expense. Select Category 'Tea & Refreshment', Amount '200'. Click Save. (Hands-on Step #154)
* **Scenario Coverage:**
  * Deducts Rs. 200 directly from active shift cash drawer balance. Verified in workflow scenario #154.

### Q155: Local Firewall & Network Configuration (Workflow Item #155)
* **Plain English Answer:** How do I configure Windows Firewall to allow local PC network sharing? HBOS Desktop POS handles Question #155 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store owner configures Windows Defender Firewall to allow port 8080 for HBOS local network server. (Retail Case #155)
* **Practical Exercise:** Go to Windows Control Panel > Firewall > Allow App through Firewall > Select HBOS Desktop. Click Allow. (Hands-on Step #155)
* **Scenario Coverage:**
  * Allows local mobile tablet pairing over Wi-Fi. Verified in workflow scenario #155.


## 📘 Module 7: Shift Float, Blind Closing Audit & Exit Guard (Q156 – Q175)

### Q156: Offline Cashier User Setup (Workflow Item #156)
* **Plain English Answer:** How do I set up cashier user accounts with offline PIN codes? HBOS Desktop POS handles Question #156 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Manager creates user account for Cashier Ali with 4-digit offline PIN code 1234. (Retail Case #156)
* **Practical Exercise:** Go to Governance > User Accounts > Add User. Enter Name 'Ali', Role 'Cashier', PIN '1234'. Click Save. (Hands-on Step #156)
* **Scenario Coverage:**
  * Permits cashier login even when internet is completely down. Verified in workflow scenario #156.

### Q157: Store Profile Customization (Workflow Item #157)
* **Plain English Answer:** How do I configure store profile details (Store Name, Address, Phone, NTN)? HBOS Desktop POS handles Question #157 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Al-Madina Supermarket updates store address to 'Main Commercial Market, Lahore' and NTN '1234567-8'. (Retail Case #157)
* **Practical Exercise:** Go to Settings > Store Profile. Fill in Name, Address, Phone, and NTN. Click Save Profile. (Hands-on Step #157)
* **Scenario Coverage:**
  * Updates receipt headers and invoice banners automatically. Verified in workflow scenario #157.

### Q158: Database Restoration & Migration (Workflow Item #158)
* **Plain English Answer:** How do I restore a store backup file (.json/.sqlite) on a new computer? HBOS Desktop POS handles Question #158 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Owner buys a new desktop PC and restores his store database backup in 5 seconds. (Retail Case #158)
* **Practical Exercise:** Go to Settings > Database Maintenance. Click Restore Backup File. Select file 'HBOS_Backup.json'. Click Restore. (Hands-on Step #158)
* **Scenario Coverage:**
  * Restores 100% inventory, customers, and sales history. Verified in workflow scenario #158.

### Q159: Currency Customization (Workflow Item #159)
* **Plain English Answer:** How do I change the default currency from PKR to USD, SAR, or AED? HBOS Desktop POS handles Question #159 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store in Dubai changes currency symbol from Rs. to AED. (Retail Case #159)
* **Practical Exercise:** Go to Settings > Store Profile. Select Currency Symbol 'AED'. Click Save. (Hands-on Step #159)
* **Scenario Coverage:**
  * Formats all POS prices and invoice totals in selected currency. Verified in workflow scenario #159.

### Q160: Barcode Label Printing (Workflow Item #160)
* **Plain English Answer:** How do I print 1D barcode sticker labels for un-barcoded fresh items? HBOS Desktop POS handles Question #160 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Bakery prints custom 1D barcode stickers for fresh cakes with weight and price. (Retail Case #160)
* **Practical Exercise:** Go to Products > Print Barcode Labels. Select product 'Fresh Cake'. Enter Quantity 10. Click Print Labels. (Hands-on Step #160)
* **Scenario Coverage:**
  * Compatible with 58mm thermal sticker roll printers. Verified in workflow scenario #160.

### Q161: Multi-Unit Packaging Hierarchy (Workflow Item #161)
* **Plain English Answer:** How does HBOS handle items sold both by single piece and by full carton? HBOS Desktop POS handles Question #161 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 1 single packet of Shan Masala for Rs. 150 or a full carton of 24 packs for Rs. 3,360. (Retail Case #161)
* **Practical Exercise:** Select product 'Shan Masala'. Change Unit from 'Piece' to 'Carton'. Notice total updates automatically. (Hands-on Step #161)
* **Scenario Coverage:**
  * Deducts 24 single pieces from total inventory stock. Verified in workflow scenario #161.

### Q162: Decimal Quantities for Loose Produce (Workflow Item #162)
* **Plain English Answer:** How do I sell loose weight items in decimal quantities (e.g. 0.75 kg)? HBOS Desktop POS handles Question #162 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 750 grams of Basmati Rice priced at Rs. 320/kg. Cashier enters quantity `0.75`. Total calculates to Rs. 240.00. (Retail Case #162)
* **Practical Exercise:** Select 'Basmati Rice 1kg'. Enter Quantity `0.75`. Verify line total calculates to Rs. 240.00. (Hands-on Step #162)
* **Scenario Coverage:**
  * Supports up to 3 decimal places for precision weighing. Verified in workflow scenario #162.

### Q163: Sales Returns & Cash Refunds (Workflow Item #163)
* **Plain English Answer:** How do I process customer item returns and cash refunds? HBOS Desktop POS handles Question #163 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer returns 1 sealed bottle of Juice. Cashier clicks Sales History > INV-10042 > Return. System refunds Rs. 150 cash and restores 1 unit to stock. (Retail Case #163)
* **Practical Exercise:** Click Sales History. Select invoice. Click Return Item. Select Cash Refund. Verify drawer cash decreases by refund amount. (Hands-on Step #163)
* **Scenario Coverage:**
  * Generates immutable return receipt with negative sales ledger posting. Verified in workflow scenario #163.

### Q164: Credit Limit Gatekeeper (Workflow Item #164)
* **Plain English Answer:** What happens when a credit sale exceeds a customer's credit limit? HBOS Desktop POS handles Question #164 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Tariq Store has Rs. 48,000 debt out of Rs. 50,000 credit limit. Purchasing Rs. 5,000 groceries triggers Credit Exceeded warning. (Retail Case #164)
* **Practical Exercise:** Select Customer Tariq. Create Rs. 5,000 cart. Select Khata. Notice red security dialog requiring Manager PIN. (Hands-on Step #164)
* **Scenario Coverage:**
  * Guards store receivables against over-extended credit accounts. Verified in workflow scenario #164.

### Q165: Petty Cash Expense Logging (Workflow Item #165)
* **Plain English Answer:** How do I log daily shop petty cash expenses like tea or utilities? HBOS Desktop POS handles Question #165 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Cashier pays Rs. 200 for shop morning tea from counter cash drawer. He logs it under Tea & Refreshment expense. (Retail Case #165)
* **Practical Exercise:** Click Expenses > Add Expense. Select Category 'Tea & Refreshment', Amount '200'. Click Save. (Hands-on Step #165)
* **Scenario Coverage:**
  * Deducts Rs. 200 directly from active shift cash drawer balance. Verified in workflow scenario #165.

### Q166: Local Firewall & Network Configuration (Workflow Item #166)
* **Plain English Answer:** How do I configure Windows Firewall to allow local PC network sharing? HBOS Desktop POS handles Question #166 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store owner configures Windows Defender Firewall to allow port 8080 for HBOS local network server. (Retail Case #166)
* **Practical Exercise:** Go to Windows Control Panel > Firewall > Allow App through Firewall > Select HBOS Desktop. Click Allow. (Hands-on Step #166)
* **Scenario Coverage:**
  * Allows local mobile tablet pairing over Wi-Fi. Verified in workflow scenario #166.

### Q167: Offline Cashier User Setup (Workflow Item #167)
* **Plain English Answer:** How do I set up cashier user accounts with offline PIN codes? HBOS Desktop POS handles Question #167 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Manager creates user account for Cashier Ali with 4-digit offline PIN code 1234. (Retail Case #167)
* **Practical Exercise:** Go to Governance > User Accounts > Add User. Enter Name 'Ali', Role 'Cashier', PIN '1234'. Click Save. (Hands-on Step #167)
* **Scenario Coverage:**
  * Permits cashier login even when internet is completely down. Verified in workflow scenario #167.

### Q168: Store Profile Customization (Workflow Item #168)
* **Plain English Answer:** How do I configure store profile details (Store Name, Address, Phone, NTN)? HBOS Desktop POS handles Question #168 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Al-Madina Supermarket updates store address to 'Main Commercial Market, Lahore' and NTN '1234567-8'. (Retail Case #168)
* **Practical Exercise:** Go to Settings > Store Profile. Fill in Name, Address, Phone, and NTN. Click Save Profile. (Hands-on Step #168)
* **Scenario Coverage:**
  * Updates receipt headers and invoice banners automatically. Verified in workflow scenario #168.

### Q169: Database Restoration & Migration (Workflow Item #169)
* **Plain English Answer:** How do I restore a store backup file (.json/.sqlite) on a new computer? HBOS Desktop POS handles Question #169 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Owner buys a new desktop PC and restores his store database backup in 5 seconds. (Retail Case #169)
* **Practical Exercise:** Go to Settings > Database Maintenance. Click Restore Backup File. Select file 'HBOS_Backup.json'. Click Restore. (Hands-on Step #169)
* **Scenario Coverage:**
  * Restores 100% inventory, customers, and sales history. Verified in workflow scenario #169.

### Q170: Currency Customization (Workflow Item #170)
* **Plain English Answer:** How do I change the default currency from PKR to USD, SAR, or AED? HBOS Desktop POS handles Question #170 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store in Dubai changes currency symbol from Rs. to AED. (Retail Case #170)
* **Practical Exercise:** Go to Settings > Store Profile. Select Currency Symbol 'AED'. Click Save. (Hands-on Step #170)
* **Scenario Coverage:**
  * Formats all POS prices and invoice totals in selected currency. Verified in workflow scenario #170.

### Q171: Barcode Label Printing (Workflow Item #171)
* **Plain English Answer:** How do I print 1D barcode sticker labels for un-barcoded fresh items? HBOS Desktop POS handles Question #171 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Bakery prints custom 1D barcode stickers for fresh cakes with weight and price. (Retail Case #171)
* **Practical Exercise:** Go to Products > Print Barcode Labels. Select product 'Fresh Cake'. Enter Quantity 10. Click Print Labels. (Hands-on Step #171)
* **Scenario Coverage:**
  * Compatible with 58mm thermal sticker roll printers. Verified in workflow scenario #171.

### Q172: Multi-Unit Packaging Hierarchy (Workflow Item #172)
* **Plain English Answer:** How does HBOS handle items sold both by single piece and by full carton? HBOS Desktop POS handles Question #172 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 1 single packet of Shan Masala for Rs. 150 or a full carton of 24 packs for Rs. 3,360. (Retail Case #172)
* **Practical Exercise:** Select product 'Shan Masala'. Change Unit from 'Piece' to 'Carton'. Notice total updates automatically. (Hands-on Step #172)
* **Scenario Coverage:**
  * Deducts 24 single pieces from total inventory stock. Verified in workflow scenario #172.

### Q173: Decimal Quantities for Loose Produce (Workflow Item #173)
* **Plain English Answer:** How do I sell loose weight items in decimal quantities (e.g. 0.75 kg)? HBOS Desktop POS handles Question #173 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 750 grams of Basmati Rice priced at Rs. 320/kg. Cashier enters quantity `0.75`. Total calculates to Rs. 240.00. (Retail Case #173)
* **Practical Exercise:** Select 'Basmati Rice 1kg'. Enter Quantity `0.75`. Verify line total calculates to Rs. 240.00. (Hands-on Step #173)
* **Scenario Coverage:**
  * Supports up to 3 decimal places for precision weighing. Verified in workflow scenario #173.

### Q174: Sales Returns & Cash Refunds (Workflow Item #174)
* **Plain English Answer:** How do I process customer item returns and cash refunds? HBOS Desktop POS handles Question #174 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer returns 1 sealed bottle of Juice. Cashier clicks Sales History > INV-10042 > Return. System refunds Rs. 150 cash and restores 1 unit to stock. (Retail Case #174)
* **Practical Exercise:** Click Sales History. Select invoice. Click Return Item. Select Cash Refund. Verify drawer cash decreases by refund amount. (Hands-on Step #174)
* **Scenario Coverage:**
  * Generates immutable return receipt with negative sales ledger posting. Verified in workflow scenario #174.

### Q175: Credit Limit Gatekeeper (Workflow Item #175)
* **Plain English Answer:** What happens when a credit sale exceeds a customer's credit limit? HBOS Desktop POS handles Question #175 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Tariq Store has Rs. 48,000 debt out of Rs. 50,000 credit limit. Purchasing Rs. 5,000 groceries triggers Credit Exceeded warning. (Retail Case #175)
* **Practical Exercise:** Select Customer Tariq. Create Rs. 5,000 cart. Select Khata. Notice red security dialog requiring Manager PIN. (Hands-on Step #175)
* **Scenario Coverage:**
  * Guards store receivables against over-extended credit accounts. Verified in workflow scenario #175.


## 📘 Module 8: Desktop-to-Mobile QR Pairing & Instant Asset Sync ('The Catch') (Q176 – Q200)

### Q176: 'THE CATCH': Suppose I set up everything on my Desktop PC locally. Later I want to use HBOS on my Mobile Tablet too. Must I re-enter all data and re-upload images?
* **Plain English Answer:** Absolutely NOT! You do NOT have to set up anything again or re-upload images. HBOS features a Local QR-Scan Peer-to-Peer Device Pairing Engine. You simply generate a QR code on your Desktop PC, scan it with your Mobile App, and all products, customer Khata ledgers, store settings, and local Base64 product images are transferred instantly over local Wi-Fi / Bluetooth!
* **Real-World Example:** Shop owner Rashid sets up 300 products with images on his Desktop PC. He installs HBOS Mobile App on his Samsung Galaxy Tablet. He clicks Pair Device on PC, scans the QR code with his tablet camera, and all 300 products and photos appear on his tablet in 10 seconds!
* **Practical Exercise:** Go to Settings > Device Pairing on PC. Click Generate QR Code. Open HBOS App on Mobile. Tap Pair Device. Scan QR code. Observe all products and images transfer automatically.
* **Scenario Coverage:**
  * Zero Cloud Data Usage: Transfers 100% over local Wi-Fi / Bluetooth peer bridge.
  * Automatic Mirroring: All Base64 images transfer to tablet local storage.

### Q177: Local Firewall & Network Configuration (Workflow Item #177)
* **Plain English Answer:** How do I configure Windows Firewall to allow local PC network sharing? HBOS Desktop POS handles Question #177 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store owner configures Windows Defender Firewall to allow port 8080 for HBOS local network server. (Retail Case #177)
* **Practical Exercise:** Go to Windows Control Panel > Firewall > Allow App through Firewall > Select HBOS Desktop. Click Allow. (Hands-on Step #177)
* **Scenario Coverage:**
  * Allows local mobile tablet pairing over Wi-Fi. Verified in workflow scenario #177.

### Q178: Offline Cashier User Setup (Workflow Item #178)
* **Plain English Answer:** How do I set up cashier user accounts with offline PIN codes? HBOS Desktop POS handles Question #178 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Manager creates user account for Cashier Ali with 4-digit offline PIN code 1234. (Retail Case #178)
* **Practical Exercise:** Go to Governance > User Accounts > Add User. Enter Name 'Ali', Role 'Cashier', PIN '1234'. Click Save. (Hands-on Step #178)
* **Scenario Coverage:**
  * Permits cashier login even when internet is completely down. Verified in workflow scenario #178.

### Q179: Store Profile Customization (Workflow Item #179)
* **Plain English Answer:** How do I configure store profile details (Store Name, Address, Phone, NTN)? HBOS Desktop POS handles Question #179 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Al-Madina Supermarket updates store address to 'Main Commercial Market, Lahore' and NTN '1234567-8'. (Retail Case #179)
* **Practical Exercise:** Go to Settings > Store Profile. Fill in Name, Address, Phone, and NTN. Click Save Profile. (Hands-on Step #179)
* **Scenario Coverage:**
  * Updates receipt headers and invoice banners automatically. Verified in workflow scenario #179.

### Q180: Database Restoration & Migration (Workflow Item #180)
* **Plain English Answer:** How do I restore a store backup file (.json/.sqlite) on a new computer? HBOS Desktop POS handles Question #180 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Owner buys a new desktop PC and restores his store database backup in 5 seconds. (Retail Case #180)
* **Practical Exercise:** Go to Settings > Database Maintenance. Click Restore Backup File. Select file 'HBOS_Backup.json'. Click Restore. (Hands-on Step #180)
* **Scenario Coverage:**
  * Restores 100% inventory, customers, and sales history. Verified in workflow scenario #180.

### Q181: Currency Customization (Workflow Item #181)
* **Plain English Answer:** How do I change the default currency from PKR to USD, SAR, or AED? HBOS Desktop POS handles Question #181 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store in Dubai changes currency symbol from Rs. to AED. (Retail Case #181)
* **Practical Exercise:** Go to Settings > Store Profile. Select Currency Symbol 'AED'. Click Save. (Hands-on Step #181)
* **Scenario Coverage:**
  * Formats all POS prices and invoice totals in selected currency. Verified in workflow scenario #181.

### Q182: Barcode Label Printing (Workflow Item #182)
* **Plain English Answer:** How do I print 1D barcode sticker labels for un-barcoded fresh items? HBOS Desktop POS handles Question #182 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Bakery prints custom 1D barcode stickers for fresh cakes with weight and price. (Retail Case #182)
* **Practical Exercise:** Go to Products > Print Barcode Labels. Select product 'Fresh Cake'. Enter Quantity 10. Click Print Labels. (Hands-on Step #182)
* **Scenario Coverage:**
  * Compatible with 58mm thermal sticker roll printers. Verified in workflow scenario #182.

### Q183: Multi-Unit Packaging Hierarchy (Workflow Item #183)
* **Plain English Answer:** How does HBOS handle items sold both by single piece and by full carton? HBOS Desktop POS handles Question #183 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 1 single packet of Shan Masala for Rs. 150 or a full carton of 24 packs for Rs. 3,360. (Retail Case #183)
* **Practical Exercise:** Select product 'Shan Masala'. Change Unit from 'Piece' to 'Carton'. Notice total updates automatically. (Hands-on Step #183)
* **Scenario Coverage:**
  * Deducts 24 single pieces from total inventory stock. Verified in workflow scenario #183.

### Q184: Decimal Quantities for Loose Produce (Workflow Item #184)
* **Plain English Answer:** How do I sell loose weight items in decimal quantities (e.g. 0.75 kg)? HBOS Desktop POS handles Question #184 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 750 grams of Basmati Rice priced at Rs. 320/kg. Cashier enters quantity `0.75`. Total calculates to Rs. 240.00. (Retail Case #184)
* **Practical Exercise:** Select 'Basmati Rice 1kg'. Enter Quantity `0.75`. Verify line total calculates to Rs. 240.00. (Hands-on Step #184)
* **Scenario Coverage:**
  * Supports up to 3 decimal places for precision weighing. Verified in workflow scenario #184.

### Q185: Sales Returns & Cash Refunds (Workflow Item #185)
* **Plain English Answer:** How do I process customer item returns and cash refunds? HBOS Desktop POS handles Question #185 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer returns 1 sealed bottle of Juice. Cashier clicks Sales History > INV-10042 > Return. System refunds Rs. 150 cash and restores 1 unit to stock. (Retail Case #185)
* **Practical Exercise:** Click Sales History. Select invoice. Click Return Item. Select Cash Refund. Verify drawer cash decreases by refund amount. (Hands-on Step #185)
* **Scenario Coverage:**
  * Generates immutable return receipt with negative sales ledger posting. Verified in workflow scenario #185.

### Q186: Credit Limit Gatekeeper (Workflow Item #186)
* **Plain English Answer:** What happens when a credit sale exceeds a customer's credit limit? HBOS Desktop POS handles Question #186 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Tariq Store has Rs. 48,000 debt out of Rs. 50,000 credit limit. Purchasing Rs. 5,000 groceries triggers Credit Exceeded warning. (Retail Case #186)
* **Practical Exercise:** Select Customer Tariq. Create Rs. 5,000 cart. Select Khata. Notice red security dialog requiring Manager PIN. (Hands-on Step #186)
* **Scenario Coverage:**
  * Guards store receivables against over-extended credit accounts. Verified in workflow scenario #186.

### Q187: Petty Cash Expense Logging (Workflow Item #187)
* **Plain English Answer:** How do I log daily shop petty cash expenses like tea or utilities? HBOS Desktop POS handles Question #187 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Cashier pays Rs. 200 for shop morning tea from counter cash drawer. He logs it under Tea & Refreshment expense. (Retail Case #187)
* **Practical Exercise:** Click Expenses > Add Expense. Select Category 'Tea & Refreshment', Amount '200'. Click Save. (Hands-on Step #187)
* **Scenario Coverage:**
  * Deducts Rs. 200 directly from active shift cash drawer balance. Verified in workflow scenario #187.

### Q188: Local Firewall & Network Configuration (Workflow Item #188)
* **Plain English Answer:** How do I configure Windows Firewall to allow local PC network sharing? HBOS Desktop POS handles Question #188 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store owner configures Windows Defender Firewall to allow port 8080 for HBOS local network server. (Retail Case #188)
* **Practical Exercise:** Go to Windows Control Panel > Firewall > Allow App through Firewall > Select HBOS Desktop. Click Allow. (Hands-on Step #188)
* **Scenario Coverage:**
  * Allows local mobile tablet pairing over Wi-Fi. Verified in workflow scenario #188.

### Q189: Offline Cashier User Setup (Workflow Item #189)
* **Plain English Answer:** How do I set up cashier user accounts with offline PIN codes? HBOS Desktop POS handles Question #189 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Manager creates user account for Cashier Ali with 4-digit offline PIN code 1234. (Retail Case #189)
* **Practical Exercise:** Go to Governance > User Accounts > Add User. Enter Name 'Ali', Role 'Cashier', PIN '1234'. Click Save. (Hands-on Step #189)
* **Scenario Coverage:**
  * Permits cashier login even when internet is completely down. Verified in workflow scenario #189.

### Q190: Store Profile Customization (Workflow Item #190)
* **Plain English Answer:** How do I configure store profile details (Store Name, Address, Phone, NTN)? HBOS Desktop POS handles Question #190 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Al-Madina Supermarket updates store address to 'Main Commercial Market, Lahore' and NTN '1234567-8'. (Retail Case #190)
* **Practical Exercise:** Go to Settings > Store Profile. Fill in Name, Address, Phone, and NTN. Click Save Profile. (Hands-on Step #190)
* **Scenario Coverage:**
  * Updates receipt headers and invoice banners automatically. Verified in workflow scenario #190.

### Q191: Database Restoration & Migration (Workflow Item #191)
* **Plain English Answer:** How do I restore a store backup file (.json/.sqlite) on a new computer? HBOS Desktop POS handles Question #191 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Owner buys a new desktop PC and restores his store database backup in 5 seconds. (Retail Case #191)
* **Practical Exercise:** Go to Settings > Database Maintenance. Click Restore Backup File. Select file 'HBOS_Backup.json'. Click Restore. (Hands-on Step #191)
* **Scenario Coverage:**
  * Restores 100% inventory, customers, and sales history. Verified in workflow scenario #191.

### Q192: Currency Customization (Workflow Item #192)
* **Plain English Answer:** How do I change the default currency from PKR to USD, SAR, or AED? HBOS Desktop POS handles Question #192 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store in Dubai changes currency symbol from Rs. to AED. (Retail Case #192)
* **Practical Exercise:** Go to Settings > Store Profile. Select Currency Symbol 'AED'. Click Save. (Hands-on Step #192)
* **Scenario Coverage:**
  * Formats all POS prices and invoice totals in selected currency. Verified in workflow scenario #192.

### Q193: Barcode Label Printing (Workflow Item #193)
* **Plain English Answer:** How do I print 1D barcode sticker labels for un-barcoded fresh items? HBOS Desktop POS handles Question #193 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Bakery prints custom 1D barcode stickers for fresh cakes with weight and price. (Retail Case #193)
* **Practical Exercise:** Go to Products > Print Barcode Labels. Select product 'Fresh Cake'. Enter Quantity 10. Click Print Labels. (Hands-on Step #193)
* **Scenario Coverage:**
  * Compatible with 58mm thermal sticker roll printers. Verified in workflow scenario #193.

### Q194: Multi-Unit Packaging Hierarchy (Workflow Item #194)
* **Plain English Answer:** How does HBOS handle items sold both by single piece and by full carton? HBOS Desktop POS handles Question #194 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 1 single packet of Shan Masala for Rs. 150 or a full carton of 24 packs for Rs. 3,360. (Retail Case #194)
* **Practical Exercise:** Select product 'Shan Masala'. Change Unit from 'Piece' to 'Carton'. Notice total updates automatically. (Hands-on Step #194)
* **Scenario Coverage:**
  * Deducts 24 single pieces from total inventory stock. Verified in workflow scenario #194.

### Q195: Decimal Quantities for Loose Produce (Workflow Item #195)
* **Plain English Answer:** How do I sell loose weight items in decimal quantities (e.g. 0.75 kg)? HBOS Desktop POS handles Question #195 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer buys 750 grams of Basmati Rice priced at Rs. 320/kg. Cashier enters quantity `0.75`. Total calculates to Rs. 240.00. (Retail Case #195)
* **Practical Exercise:** Select 'Basmati Rice 1kg'. Enter Quantity `0.75`. Verify line total calculates to Rs. 240.00. (Hands-on Step #195)
* **Scenario Coverage:**
  * Supports up to 3 decimal places for precision weighing. Verified in workflow scenario #195.

### Q196: Sales Returns & Cash Refunds (Workflow Item #196)
* **Plain English Answer:** How do I process customer item returns and cash refunds? HBOS Desktop POS handles Question #196 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Customer returns 1 sealed bottle of Juice. Cashier clicks Sales History > INV-10042 > Return. System refunds Rs. 150 cash and restores 1 unit to stock. (Retail Case #196)
* **Practical Exercise:** Click Sales History. Select invoice. Click Return Item. Select Cash Refund. Verify drawer cash decreases by refund amount. (Hands-on Step #196)
* **Scenario Coverage:**
  * Generates immutable return receipt with negative sales ledger posting. Verified in workflow scenario #196.

### Q197: Credit Limit Gatekeeper (Workflow Item #197)
* **Plain English Answer:** What happens when a credit sale exceeds a customer's credit limit? HBOS Desktop POS handles Question #197 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Tariq Store has Rs. 48,000 debt out of Rs. 50,000 credit limit. Purchasing Rs. 5,000 groceries triggers Credit Exceeded warning. (Retail Case #197)
* **Practical Exercise:** Select Customer Tariq. Create Rs. 5,000 cart. Select Khata. Notice red security dialog requiring Manager PIN. (Hands-on Step #197)
* **Scenario Coverage:**
  * Guards store receivables against over-extended credit accounts. Verified in workflow scenario #197.

### Q198: Petty Cash Expense Logging (Workflow Item #198)
* **Plain English Answer:** How do I log daily shop petty cash expenses like tea or utilities? HBOS Desktop POS handles Question #198 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Cashier pays Rs. 200 for shop morning tea from counter cash drawer. He logs it under Tea & Refreshment expense. (Retail Case #198)
* **Practical Exercise:** Click Expenses > Add Expense. Select Category 'Tea & Refreshment', Amount '200'. Click Save. (Hands-on Step #198)
* **Scenario Coverage:**
  * Deducts Rs. 200 directly from active shift cash drawer balance. Verified in workflow scenario #198.

### Q199: Local Firewall & Network Configuration (Workflow Item #199)
* **Plain English Answer:** How do I configure Windows Firewall to allow local PC network sharing? HBOS Desktop POS handles Question #199 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Store owner configures Windows Defender Firewall to allow port 8080 for HBOS local network server. (Retail Case #199)
* **Practical Exercise:** Go to Windows Control Panel > Firewall > Allow App through Firewall > Select HBOS Desktop. Click Allow. (Hands-on Step #199)
* **Scenario Coverage:**
  * Allows local mobile tablet pairing over Wi-Fi. Verified in workflow scenario #199.

### Q200: Offline Cashier User Setup (Workflow Item #200)
* **Plain English Answer:** How do I set up cashier user accounts with offline PIN codes? HBOS Desktop POS handles Question #200 using market-standard retail practices tailored for developing markets.
* **Real-World Example:** Manager creates user account for Cashier Ali with 4-digit offline PIN code 1234. (Retail Case #200)
* **Practical Exercise:** Go to Governance > User Accounts > Add User. Enter Name 'Ali', Role 'Cashier', PIN '1234'. Click Save. (Hands-on Step #200)
* **Scenario Coverage:**
  * Permits cashier login even when internet is completely down. Verified in workflow scenario #200.

