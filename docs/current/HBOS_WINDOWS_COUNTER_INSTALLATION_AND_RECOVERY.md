# SkyraSoft HBOS — Windows Counter Installation and Disaster Recovery Guide

**Scope:** Windows POS Terminal Deployment, Operation, and Local Crash Recovery.  

---

## 1. Installation Instructions

1. **Prerequisites:**
   * Windows 10/11 x64 (Build 19041 or higher).
   * Microsoft Edge WebView2 Evergreen Runtime (included with modern Windows).
2. **Installer Execution:**
   * Locate `HBOS POS_1.0.0_x64_en-US.msi`.
   * Double-click installer. Standard per-user install requires no administrative elevation.
   * Target directory: `%LOCALAPPDATA%\Programs\HBOS POS\`.
   * Desktop and Start Menu shortcuts are created automatically.

---

## 2. Terminal Offline Resilience & Crash Recovery

1. **Local Storage Architecture:**
   * Offline catalog, customers, and pending transactions reside in browser/WebView2 IndexedDB under namespace `HBOS_Store_Biz_${business_id}`.
   * Object stores: `products`, `categories`, `brands`, `customers`, `sales`, `khata_payments`, `expenses`, `outbox`, `images`.
2. **Crash & Power Outage Recovery:**
   * If counter terminal experiences sudden power loss, all queued offline transactions in `outbox` remain safely persisted on disk in IndexedDB.
   * Upon restarting `HBOS POS.exe`, the application checks network status. If connected, `SyncCoordinator` automatically replays the outbox via HTTP `POST /api/v1/sync/bulk`.
   * Invoices use client-generated unique IDs and server idempotency keys (`sales_business_idempotency_unique`) to guarantee zero duplicate postings.

---

## 3. Uninstallation and Clean Reinstall

* **Standard Uninstall:** Use Windows Settings -> Apps -> Installed apps -> Uninstall "HBOS POS".
* **Data Retention:** IndexedDB cache files remain in `%LOCALAPPDATA%\com.skyrasoft.hbos\EBWebView\Default\IndexedDB\` unless explicitly purged by the operator.
