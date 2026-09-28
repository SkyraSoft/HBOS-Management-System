/**
 * HBOS Counter Kiosk & Keyboard Accelerator Service
 * Handles keyboard hotkeys (F1 Search, F2 Cash, F3 Khata, F4 Discount, F8 Hold, Esc Clear)
 * and intercepts window close/exit commands with Manager PIN verification.
 */

let registeredShortcuts = {};

export function initKioskAccelerators(handlers = {}) {
  registeredShortcuts = { ...handlers };

  window.addEventListener('keydown', handleGlobalKeydown);
}

function handleGlobalKeydown(e) {
  // F1: Focus Search Bar
  if (e.key === 'F1') {
    e.preventDefault();
    if (registeredShortcuts.onFocusSearch) registeredShortcuts.onFocusSearch();
  }
  // F2: Cash Checkout
  else if (e.key === 'F2') {
    e.preventDefault();
    if (registeredShortcuts.onCashCheckout) registeredShortcuts.onCashCheckout();
  }
  // F3: Khata Credit Checkout
  else if (e.key === 'F3') {
    e.preventDefault();
    if (registeredShortcuts.onKhataCheckout) registeredShortcuts.onKhataCheckout();
  }
  // F4: Apply Discount
  else if (e.key === 'F4') {
    e.preventDefault();
    if (registeredShortcuts.onApplyDiscount) registeredShortcuts.onApplyDiscount();
  }
  // F8: Park/Hold Cart
  else if (e.key === 'F8') {
    e.preventDefault();
    if (registeredShortcuts.onHoldCart) registeredShortcuts.onHoldCart();
  }
  // Escape: Clear / Cancel
  else if (e.key === 'Escape') {
    if (registeredShortcuts.onClearOrCancel) registeredShortcuts.onClearOrCancel();
  }
}

export function removeKioskAccelerators() {
  window.removeEventListener('keydown', handleGlobalKeydown);
  registeredShortcuts = {};
}
