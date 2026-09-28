# Phase 3 Execution Document — Native Windows Desktop POS App

**Phase:** Phase 3 (of 8)  
**Status:** IN PROGRESS  
**Target Subsystem:** `desktop/` & `Frontend/src/services/` (Tauri / Rust / Dual Printer Engine / Kiosk Guard)  
**Parent Plan:** [HBOS_MASTER_IMPLEMENTATION_PLAN.md](file:///c:/xampp/htdocs/HBOS/docs/HBOS_MASTER_IMPLEMENTATION_PLAN.md)  

---

## 🎯 Phase 3 Objectives & Deliverables

1. **Tauri Windows App Configuration (`desktop/tauri.conf.json`):**
   * Configures native Windows 10/11 application properties.
   * Kiosk Mode: `fullscreen: true`, `resizable: false`, `decorations: false`.
   * Intercepts `Alt+F4` and close events.

2. **Dual Printer Engine (`src/services/printerService.js`):**
   * **Thermal Mode (80mm / 58mm Roll):** Raw ESC/POS silent binary printing to USB/COM printers (zero print popups) + RJ11 cash drawer kick pulse.
   * **Normal Desktop Mode (A4 / A5 Sheet):** Printable full-page laser/inkjet layout with company header, itemized table, and footer policies.
   * Selectable default in **Settings > Printer Settings**.

3. **Visual Receipt Customizer Engine (`src/services/receiptCustomizerService.js`):**
   * Stores receipt customization settings locally: Logo, Store Name, Phone, Address, NTN/Tax ID, Header/Footer slogans, WhatsApp QR/helpline, and column visibility toggles (Show/Hide Unit Price, Discounts, SKU, Customer Balances).

4. **Kiosk Lockout & Keyboard Shortcut Manager (`src/services/kioskService.js`):**
   * Full-screen counter enforcement (no browser URL bar, no tabs).
   * Counter hotkeys: `F1` Search focus, `F2` Cash tender, `F3` Khata credit, `F4` Discount, `F8` Hold cart, `Esc` Clear/Cancel.
   * Intercepts exit commands with a mandatory Manager PIN prompt.

---

## 📝 Implementation Progress & Task Log

- [x] Initial Phase 3 Scoping & Tauri Desktop Design
- [x] Implement `desktop/tauri.conf.json` (Tauri Windows App Setup)
- [x] Implement `src/services/receiptCustomizerService.js` (Receipt Layout Customizer)
- [x] Implement `src/services/printerService.js` (Dual Printer Engine: Thermal vs Standard A4/A5)
- [x] Implement `src/services/kioskService.js` (Kiosk Lockout & Counter Hotkeys)
- [x] Build Verification Tests for Windows Desktop Services (`scratch/test_phase3_desktop_engine.cjs`)
- [x] Finalize Phase 3 documentation and mark Phase 3 COMPLETED in Master Plan

---

## 🏆 Phase 3 Verification Summary
- **Tauri Windows App:** `desktop/tauri.conf.json` provisions fullscreen kiosk configuration targeting Windows 10/11 (`com.skyrasoft.hbos`).
- **Dual Printer Engine:** `src/services/printerService.js` supports both 80mm/58mm raw silent thermal roll printing and standard A4/A5 laser/inkjet sheet layouts.
- **Receipt Customizer:** `src/services/receiptCustomizerService.js` stores custom shop slogans, logos, NTN tax numbers, and column toggles.
- **Kiosk & Hotkeys:** `src/services/kioskService.js` handles counter keyboard shortcuts (`F1`–`F8`, `Esc`).
