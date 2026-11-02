/**
 * HBOS Desktop Bridge Service (Tauri WebView2 Native Integration)
 * Provides clean environment detection and native window / print integration
 * with 100% browser fallback and zero phantom native commands.
 */

export function isTauri() {
  return typeof window !== 'undefined' && (
    window.__TAURI_IPC__ !== undefined ||
    window.__TAURI__ !== undefined ||
    window.__TAURI_METADATA__ !== undefined
  );
}

export async function getDesktopEnvironment() {
  if (isTauri() && window.__TAURI__?.invoke) {
    try {
      return await window.__TAURI__.invoke('get_desktop_environment');
    } catch (e) {
      console.warn('[HBOS DesktopBridge] Native invoke failed, falling back to browser context:', e);
    }
  }

  return {
    environment: isTauri() ? 'tauri_webview2' : 'browser_pwa',
    platform: typeof navigator !== 'undefined' ? navigator.platform : 'unknown',
    app_version: '1.0.0',
    is_native: isTauri(),
  };
}

export async function exitApplication(force = false) {
  if (typeof window === 'undefined') return;

  // Verify if there are pending offline transactions directly from durable IndexedDB outbox
  let pendingCount = 0;
  try {
    const { getOutboxPendingCount } = await import('./localDb.js');
    pendingCount = await getOutboxPendingCount();
  } catch (e) {
    pendingCount = parseInt(localStorage.getItem('hbos_pending_sync_count') || '0', 10);
  }

  if (!force && pendingCount > 0) {
    const confirmExit = window.confirm(
      `Notice: You have ${pendingCount} offline transaction(s) pending sync.\n` +
      `Your transactions are safely saved in local storage and will sync automatically when reconnected.\n\n` +
      `Do you want to exit now?`
    );
    if (!confirmExit) return false;
  }

  if (isTauri() && window.__TAURI__?.invoke) {
    try {
      await window.__TAURI__.invoke('exit_application');
      return true;
    } catch (e) {
      console.warn('[HBOS DesktopBridge] Native exit_application failed, falling back to window.close:', e);
    }
  }

  try {
    window.close();
  } catch (e) {
    console.log('[HBOS DesktopBridge] Window cannot be closed via script in browser context.');
  }
  return true;
}

export async function minimizeWindow() {
  if (isTauri() && window.__TAURI__?.invoke) {
    try {
      await window.__TAURI__.invoke('minimize_application');
      return true;
    } catch (e) {
      console.warn('[HBOS DesktopBridge] Native minimize_application failed:', e);
    }
  }
  return false;
}

export async function toggleMaximize() {
  if (isTauri() && window.__TAURI__?.invoke) {
    try {
      await window.__TAURI__.invoke('toggle_maximize');
      return true;
    } catch (e) {
      console.warn('[HBOS DesktopBridge] Native toggle_maximize failed:', e);
    }
  }
  return false;
}

export function openPrintDialog() {
  if (typeof window !== 'undefined' && typeof window.print === 'function') {
    window.print();
    return true;
  }
  return false;
}

export default {
  isTauri,
  getDesktopEnvironment,
  openPrintDialog,
  exitApplication,
  minimizeWindow,
  toggleMaximize,
};
