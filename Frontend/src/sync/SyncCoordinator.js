import localDb from '../services/localDb.js';
import offlinePosEngine from '../services/offlinePosEngine.js';
import printerService from '../services/printerService.js';

/**
 * HBOS Bidirectional Sync Coordinator & Shift-Close Exit Guard
 * Manages background catalog updates, opportunistic transaction push,
 * and mandatory shift-close bulk synchronization with cryptographic offline seals.
 */
class SyncCoordinator {
  constructor() {
    this.apiBaseUrl = 'https://hbos.skyrasoft.com/api/v1';
    this.syncIntervalMs = 5 * 60 * 1000; // 5 minutes
    this.timerId = null;
    this.isSyncing = false;
    this.isOnline = typeof navigator !== 'undefined' ? navigator.onLine : true;
    this.listeners = new Set();
  }

  init() {
    if (typeof window !== 'undefined') {
      window.addEventListener('online', () => this.handleOnlineStatus(true));
      window.addEventListener('offline', () => this.handleOnlineStatus(false));
    }
    this.startBackgroundSync();
  }

  handleOnlineStatus(online) {
    this.isOnline = online;
    this.notifyListeners({ type: 'NETWORK_CHANGE', isOnline: online });
    if (online) {
      this.triggerOpportunisticPush();
      this.pullCatalogDelta();
    }
  }

  subscribe(callback) {
    this.listeners.add(callback);
    return () => this.listeners.delete(callback);
  }

  notifyListeners(event) {
    for (const listener of this.listeners) {
      try {
        listener(event);
      } catch (err) {
        console.error('Sync listener error:', err);
      }
    }
  }

  startBackgroundSync() {
    if (this.timerId) clearInterval(this.timerId);
    this.timerId = setInterval(() => {
      if (this.isOnline && !this.isSyncing) {
        this.syncRoutine();
      }
    }, this.syncIntervalMs);
  }

  stopBackgroundSync() {
    if (this.timerId) {
      clearInterval(this.timerId);
      this.timerId = null;
    }
  }

  async syncRoutine() {
    if (this.isSyncing) return;
    this.isSyncing = true;
    this.notifyListeners({ type: 'SYNC_STARTED' });

    try {
      await this.triggerOpportunisticPush();
      await this.pullCatalogDelta();
      this.notifyListeners({ type: 'SYNC_COMPLETED', success: true });
    } catch (error) {
      console.error('[HBOS Sync] Sync routine failed:', error);
      this.notifyListeners({ type: 'SYNC_FAILED', error: error.message });
    } finally {
      this.isSyncing = false;
    }
  }

  async triggerOpportunisticPush() {
    const unSyncedSales = await localDb.sales.where('synced').equals(0).toArray();
    const unSyncedPayments = await localDb.customerPayments.where('synced').equals(0).toArray();

    if (unSyncedSales.length === 0 && unSyncedPayments.length === 0) {
      return { pushedSales: 0, pushedPayments: 0 };
    }

    const payload = {
      client_uuid: offlinePosEngine.generateUuid(),
      branch_id: 1,
      business_id: 1,
      shift_id: 1,
      sent_at: new Date().toISOString(),
      sales: unSyncedSales.map(s => ({
        local_id: s.id,
        uuid: s.uuid,
        receipt_number: s.receipt_number,
        customer_id: s.customer_id,
        total_amount: s.total_amount,
        discount_amount: s.discount_amount,
        net_amount: s.net_amount,
        paid_amount: s.paid_amount,
        change_amount: s.change_amount,
        payment_type: s.payment_type,
        created_at: s.created_at,
        items: s.items || []
      })),
      khata_payments: unSyncedPayments.map(p => ({
        local_id: p.id,
        uuid: p.uuid,
        customer_id: p.customer_id,
        amount: p.amount,
        payment_method: p.payment_method,
        notes: p.notes,
        created_at: p.created_at
      }))
    };

    // Attempt push to cloud API
    try {
      const response = await fetch(`${this.apiBaseUrl}/sync/bulk-transactions`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      });

      if (response.ok) {
        const data = await response.json();
        // Mark items as synced in local IndexedDB
        for (const sale of unSyncedSales) {
          await localDb.sales.update(sale.id, { synced: 1 });
        }
        for (const payment of unSyncedPayments) {
          await localDb.customerPayments.update(payment.id, { synced: 1 });
        }
        return { pushedSales: unSyncedSales.length, pushedPayments: unSyncedPayments.length, data };
      }
    } catch (e) {
      console.warn('[HBOS Sync] Cloud API unreachable during push, items remain in local outbox.');
    }

    return { pushedSales: 0, pushedPayments: 0, offline: true };
  }

  async pullCatalogDelta() {
    const lastSyncTimestamp = localStorage.getItem('hbos_last_catalog_sync') || '1970-01-01 00:00:00';
    try {
      const response = await fetch(`${this.apiBaseUrl}/sync/catalog-delta?since=${encodeURIComponent(lastSyncTimestamp)}`);
      if (response.ok) {
        const delta = await response.json();
        if (delta.products && delta.products.length > 0) {
          for (const prod of delta.products) {
            await localDb.products.put(prod);
          }
        }
        if (delta.customers && delta.customers.length > 0) {
          for (const cust of delta.customers) {
            await localDb.customers.put(cust);
          }
        }
        localStorage.setItem('hbos_last_catalog_sync', new Date().toISOString());
      }
    } catch (e) {
      console.warn('[HBOS Sync] Cloud API unreachable during catalog delta pull.');
    }
  }

  /**
   * Shift-Close Exit Guard Execution
   * Executes atomic bulk sync, generates Z-Report, creates cryptographic offline seal if network down.
   */
  async executeShiftCloseExitGuard(shiftSummary) {
    const statusLog = [];
    statusLog.push({ step: 'INITIALIZING', msg: 'Initiating Mandatory Shift-Close Guard...' });

    // Step 1: Check pending outbox items
    const unSyncedSales = await localDb.sales.where('synced').equals(0).toArray();
    const unSyncedPayments = await localDb.customerPayments.where('synced').equals(0).toArray();
    const totalPending = unSyncedSales.length + unSyncedPayments.length;

    statusLog.push({ step: 'OUTBOX_COUNT', count: totalPending, msg: `Found ${totalPending} pending outbox items for sync.` });

    let syncSuccess = false;
    let offlineChecksum = null;

    if (totalPending > 0) {
      statusLog.push({ step: 'UPLOADING', msg: 'Transmitting pending transaction batch to Hostinger Cloud Backend...' });
      const res = await this.triggerOpportunisticPush();
      if (!res.offline) {
        syncSuccess = true;
        statusLog.push({ step: 'CLOUD_ACK', msg: `Successfully synced ${totalPending} transactions. Cloud HTTP 200 OK.` });
      } else {
        statusLog.push({ step: 'CLOUD_OFFLINE', msg: 'Hostinger Cloud API unreachable. Generating Cryptographic Offline Seal...' });
        offlineChecksum = await this.generateCryptographicSeal(unSyncedSales, unSyncedPayments);
        statusLog.push({ step: 'SEAL_GENERATED', checksum: offlineChecksum, msg: `Offline Seal Generated: ${offlineChecksum.slice(0, 16)}...` });
      }
    } else {
      syncSuccess = true;
      statusLog.push({ step: 'NO_PENDING', msg: 'Zero pending outbox items. Local database clean.' });
    }

    // Step 2: Print Shift-Close Z-Report
    statusLog.push({ step: 'PRINTING_Z_REPORT', msg: 'Printing Cashier Shift Z-Report...' });
    const zReportData = {
      title: 'SHIFT CLOSE Z-REPORT',
      shiftId: shiftSummary.shiftId || 1,
      cashier: shiftSummary.cashierName || 'Counter Cashier',
      openingFloat: shiftSummary.openingFloat || 0,
      totalSales: shiftSummary.totalSales || 0,
      cashCollected: shiftSummary.cashCollected || 0,
      khataCollected: shiftSummary.khataCollected || 0,
      totalExpenses: shiftSummary.totalExpenses || 0,
      expectedCash: shiftSummary.expectedCash || 0,
      actualCash: shiftSummary.actualCash || 0,
      variance: shiftSummary.variance || 0,
      syncedToCloud: syncSuccess,
      offlineChecksum: offlineChecksum,
      closedAt: new Date().toISOString()
    };

    const printRes = await printerService.printThermalReceipt(zReportData);
    statusLog.push({ step: 'Z_REPORT_PRINTED', success: printRes.success });

    // Step 3: Record shift close in local DB
    await localDb.shifts.add({
      ...zReportData,
      synced: syncSuccess ? 1 : 0
    });

    statusLog.push({ step: 'GUARD_COMPLETE', msg: 'Shift-Close Guard complete. Ready for secure application shutdown.' });

    return {
      success: true,
      syncSuccess,
      offlineChecksum,
      totalSynced: totalPending,
      statusLog
    };
  }

  async generateCryptographicSeal(sales, payments) {
    const rawData = JSON.stringify({ sales, payments, timestamp: new Date().toISOString() });
    let hashHex = '';
    if (typeof crypto !== 'undefined' && crypto.subtle) {
      const msgUint8 = new TextEncoder().encode(rawData);
      const hashBuffer = await crypto.subtle.digest('SHA-256', msgUint8);
      const hashArray = Array.from(new Uint8Array(hashBuffer));
      hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    } else {
      // Fallback simple hash calculation
      let hash = 0;
      for (let i = 0; i < rawData.length; i++) {
        const char = rawData.charCodeAt(i);
        hash = (hash << 5) - hash + char;
        hash |= 0;
      }
      hashHex = `OFFLINE_SEAL_${Math.abs(hash).toString(16).toUpperCase()}`;
    }
    return hashHex;
  }
}

export const syncCoordinator = new SyncCoordinator();
export default syncCoordinator;
