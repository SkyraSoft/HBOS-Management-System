# SkyraSoft HBOS — Final Release Blocker Register

**Status:** Authoritative Blocker Register for Public Production Launch  
**Current Launch Classification:** `PRODUCTION_LAUNCH_NO_GO`  

---

## 1. Summary of Blockers

While all software and database engineering components have passed comprehensive automated assurance, public production launch is blocked by the following four mandatory real-world operational gates:

| Blocker ID | Domain | Severity | Description | Resolution Prerequisite |
|---|---|---|---|---|
| **BLK-001** | Physical Hardware | `CRITICAL_GATE` | Physical 80mm ESC/POS thermal printer, USB HID barcode scanner, and RJ11 cash drawer have not undergone on-site physical counter testing. | Connect physical peripherals at pilot retail counter and execute hardware acceptance procedures. |
| **BLK-002** | Merchant UAT | `CRITICAL_GATE` | Real store owner and staff operational sign-offs remain pending. | Store staff complete simulated trading day and sign the UAT Acceptance Form. |
| **BLK-003** | Code Signing | `SECURITY_GATE` | Windows installer (`HBOS POS_1.0.0_x64_en-US.msi`) and binary (`HBOS POS.exe`) are unsigned with an Authenticode certificate. | Acquire EV/OV code signing certificate and sign artifacts prior to public Windows distribution. |
| **BLK-004** | Live Infrastructure | `OPERATIONAL_GATE` | Dedicated production database with automated replication, binary logging, and SSL certificates has not been provisioned. | Provision production MariaDB server with replication, backups, and live merchant credentials. |

---

## 2. Resolved & Withdrawn Items

* **Historical hbos Modification:** The previous recommendation to delete `role_has_permissions` records 34–40 in `hbos` was withdrawn. Database `hbos` is preserved intact.
* **Database Isolation:** Enforced via centralized fail-closed safety guard (`scratch/test_db_guard.php`).
* **Test Token Revocation:** 20 synthetic tokens safely revoked from staging; zero production or human sessions impacted.
