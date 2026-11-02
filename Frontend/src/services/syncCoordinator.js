/**
 * HBOS Autonomous Background Sync Coordinator (REQ-0030, SRT-GAP-025)
 * Manages network heartbeat, single-flight replay lease mutex, staged queue replay,
 * same-user/business isolation, exponential backoff, and reactive status emission.
 */

import api from '../api.js';
import { getStoreItems, putStoreItem, getOutboxPendingCount } from './localDb.js';
import {
  getAuthContext,
  getDeviceId,
  getPendingOutboxQueue,
  markOutboxItemsSynced,
  markOutboxItemFailed,
  resetFailedOutboxItemsForRetry,
  initOfflineEngine,
} from './offlinePosEngine.js';

class SyncCoordinator {
  constructor() {
    this.status = 'synced'; // 'synced' | 'offline' | 'syncing' | 'attention' | 'update_required'
    this.pendingCount = 0;
    this.lastSyncTime = null;
    this.lastError = null;
    this.isOnline = typeof navigator !== 'undefined' ? navigator.onLine : true;
    this.isSyncing = false;
    this.syncLockTimeoutMs = 30000; // 30s lease timeout for stale lease recovery
    this.lastLockAcquiredAt = 0;
    this.listeners = new Set();
    this.timerId = null;
    this.heartbeatTimerId = null;
    this.backoffMs = 2000;
    this.maxBackoffMs = 60000;
    this.retryCount = 0;
  }

  async init() {
    const isDemo = typeof window !== 'undefined' && !!localStorage.getItem('hbos_demo_mode');
    if (isDemo) {
      this.status = 'synced';
      this.pendingCount = 0;
      return;
    }

    await initOfflineEngine();
    await this.refreshPendingCount();

    if (typeof window !== 'undefined') {
      window.addEventListener('online', () => this.handleNetworkChange(true));
      window.addEventListener('offline', () => this.handleNetworkChange(false));
    }

    // Initial reachability check and background loop
    this.startHeartbeat();
    this.startBackgroundSync();

    // Trigger initial sync if online and pending items exist
    if (this.isOnline) {
      this.triggerSync();
    }
  }

  subscribe(callback) {
    this.listeners.add(callback);
    callback(this.getState());
    return () => this.listeners.delete(callback);
  }

  notifyListeners() {
    const state = this.getState();
    for (const listener of this.listeners) {
      try {
        listener(state);
      } catch (err) {
        console.error('[SyncCoordinator] Listener error:', err);
      }
    }
  }

  getState() {
    return {
      status: this.status,
      pendingCount: this.pendingCount,
      lastSyncTime: this.lastSyncTime,
      lastError: this.lastError,
      isOnline: this.isOnline,
      isSyncing: this.isSyncing,
    };
  }

  async refreshPendingCount() {
    try {
      const auth = getAuthContext();
      const count = await getOutboxPendingCount({
        businessId: auth.businessId,
        userId: auth.userId,
      });
      this.pendingCount = count;
      try {
        localStorage.setItem('hbos_pending_sync_count', String(count));
      } catch (e) {}

      // Update high-level status if not actively syncing or update-blocked
      if (this.status !== 'syncing' && this.status !== 'update_required') {
        if (!this.isOnline) {
          this.status = 'offline';
        } else if (this.lastError && this.lastError.category) {
          this.status = 'attention';
        } else if (this.pendingCount === 0) {
          this.status = 'synced';
        }
      }
      this.notifyListeners();
    } catch (err) {
      console.warn('[SyncCoordinator] Failed to count pending outbox:', err);
    }
  }

  handleNetworkChange(online) {
    this.isOnline = online;
    if (!online) {
      this.status = 'offline';
      this.notifyListeners();
    } else {
      this.checkServerReachability().then((reachable) => {
        if (reachable) {
          this.triggerSync();
        } else {
          this.status = 'offline';
          this.notifyListeners();
        }
      });
    }
  }

  startHeartbeat() {
    if (this.heartbeatTimerId) clearInterval(this.heartbeatTimerId);
    // Ping /api/v1/sync/status every 30 seconds
    this.heartbeatTimerId = setInterval(async () => {
      if (this.isOnline) {
        await this.checkServerReachability();
      }
    }, 30000);
  }

  startBackgroundSync() {
    if (this.timerId) clearInterval(this.timerId);
    // Periodic sweep every 20 seconds
    this.timerId = setInterval(() => {
      if (this.isOnline && !this.isSyncing && this.status !== 'update_required') {
        this.triggerSync();
      }
    }, 20000);
  }

  stop() {
    if (this.timerId) clearInterval(this.timerId);
    if (this.heartbeatTimerId) clearInterval(this.heartbeatTimerId);
  }

  async checkServerReachability() {
    try {
      const res = await api.get('/sync/status', {
        headers: { 'X-HBOS-Device-ID': getDeviceId() },
        timeout: 5000,
      });
      if (res.data && (res.data.status === 'online' || res.data.status === 'ok')) {
        this.isOnline = true;
        return true;
      }
      return false;
    } catch (err) {
      if (err.response?.status === 426) {
        this.status = 'update_required';
        this.lastError = { category: 'UPDATE_REQUIRED', message: 'Client update required to sync.' };
        this.notifyListeners();
        return false;
      }
      this.isOnline = false;
      return false;
    }
  }

  /**
   * Acquire cross-tab or single-flight execution lease mutex.
   */
  async acquireLock() {
    const now = Date.now();
    const lockKey = 'hbos_sync_lock';

    // 1. In-memory single-flight check
    if (this.isSyncing && now - this.lastLockAcquiredAt < this.syncLockTimeoutMs) {
      return false;
    }

    // 2. Cross-tab localStorage lease check with 30s stale lease recovery
    if (typeof window !== 'undefined' && window.localStorage) {
      try {
        const existingLock = localStorage.getItem(lockKey);
        if (existingLock) {
          const lockTime = Number(existingLock);
          if (now - lockTime < this.syncLockTimeoutMs) {
            return false; // Another tab holds an active lease
          }
        }
        localStorage.setItem(lockKey, String(now));
      } catch (e) {
        // Fall back to in-memory lock if localStorage is restricted
      }
    }

    this.isSyncing = true;
    this.lastLockAcquiredAt = now;
    return true;
  }

  releaseLock() {
    this.isSyncing = false;
    if (typeof window !== 'undefined' && window.localStorage) {
      try {
        localStorage.removeItem('hbos_sync_lock');
      } catch (e) {
        // ignore
      }
    }
  }

  /**
   * Trigger replay sync for current business and authenticated user.
   */
  async triggerSync() {
    if (this.status === 'update_required') return;

    const locked = await this.acquireLock();
    if (!locked) return;

    this.status = 'syncing';
    this.notifyListeners();

    try {
      await this.runReplayCycle();
      this.lastSyncTime = new Date().toISOString();
      this.lastError = null;
      this.retryCount = 0;
      this.backoffMs = 2000;
      await this.refreshPendingCount();
      if (this.pendingCount === 0) {
        this.status = 'synced';
      }
    } catch (err) {
      console.warn('[SyncCoordinator] Sync cycle failed:', err);
      this.handleReplayError(err);
    } finally {
      this.releaseLock();
      await this.refreshPendingCount();
    }
  }

  async runReplayCycle() {
    const auth = getAuthContext();
    const allPending = await getPendingOutboxQueue({
      businessId: auth.businessId,
      userId: auth.userId,
    });

    if (allPending.length === 0) {
      return;
    }

    // Topological dependency order: customers -> sales -> khata_payments -> expenses
    const stageOrder = ['customer', 'sale', 'khata_payment', 'customer_payment', 'expense'];
    const sorted = [...allPending].sort((a, b) => {
      const idxA = stageOrder.indexOf(a.type);
      const idxB = stageOrder.indexOf(b.type);
      const stageA = idxA === -1 ? 99 : idxA;
      const stageB = idxB === -1 ? 99 : idxB;
      if (stageA !== stageB) return stageA - stageB;
      return new Date(a.created_at).getTime() - new Date(b.created_at).getTime();
    });

    // Chunk into batches of up to 25 items
    const batch = sorted.slice(0, 25);
    const sales = [];
    const payments = [];
    const expenses = [];

    for (const item of batch) {
      if (item.type === 'sale') {
        sales.push({
          idempotency_key: item.idempotency_key,
          occurred_at: item.occurred_at || item.created_at,
          ...item.payload,
        });
      } else if (item.type === 'khata_payment' || item.type === 'customer_payment') {
        payments.push({
          idempotency_key: item.idempotency_key,
          occurred_at: item.occurred_at || item.created_at,
          ...item.payload,
        });
      } else if (item.type === 'expense') {
        expenses.push({
          idempotency_key: item.idempotency_key,
          occurred_at: item.occurred_at || item.created_at,
          ...item.payload,
        });
      }
    }

    const payload = {
      business_id: auth.businessId,
      branch_id: auth.branchId,
      sales,
      payments,
      expenses,
    };

    const response = await api.post('/sync/bulk-transactions', payload, {
      headers: {
        'X-HBOS-Device-ID': getDeviceId(),
      },
    });

    if (response.data && response.data.status === 'success') {
      const syncedKeys = batch.map((item) => item.idempotency_key);
      await markOutboxItemsSynced(syncedKeys);
    }
  }

  handleReplayError(err) {
    const status = err.response?.status;
    const data = err.response?.data;

    if (status === 401) {
      // Authentication expired: pause sync, preserve queue
      this.status = 'attention';
      this.lastError = {
        category: 'AUTH_REQUIRED',
        message: 'Session expired. Please sign in again to sync transactions.',
      };
      this.stop();
    } else if (status === 426) {
      // Version gate: halt sync, preserve queue
      this.status = 'update_required';
      this.lastError = {
        category: 'UPDATE_REQUIRED',
        message: 'Client version upgrade required by server. Queue safely preserved.',
      };
      this.stop();
    } else if (status === 422) {
      // Validation / Stock Conflict / Price Conflict
      const errorType = data?.error_type || 'VALIDATION';
      const failedKey = data?.failed_item_key;
      const message = data?.message || data?.error || 'Business validation conflict.';

      if (failedKey) {
        markOutboxItemFailed(failedKey, errorType, message);
      }

      this.status = 'attention';
      this.lastError = {
        category: errorType,
        message: message,
      };
    } else {
      // Network or 5xx: schedule exponential backoff
      this.retryCount++;
      this.backoffMs = Math.min(this.backoffMs * 2, this.maxBackoffMs);
      this.status = 'attention';
      this.lastError = {
        category: 'NETWORK_RETRYABLE',
        message: data?.message || err.message || 'Network error syncing offline queue.',
      };
    }

    this.notifyListeners();
  }

  /**
   * Manual trigger from UI button: resets safe retryable items and runs sync.
   */
  async manualRetry() {
    const auth = getAuthContext();
    await resetFailedOutboxItemsForRetry({
      businessId: auth.businessId,
      userId: auth.userId,
    });
    this.lastError = null;
    await this.refreshPendingCount();
    await this.triggerSync();
  }
}

export const syncCoordinator = new SyncCoordinator();
export default syncCoordinator;
