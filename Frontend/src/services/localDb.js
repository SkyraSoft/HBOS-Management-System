/**
 * HBOS Client-Side Native IndexedDB Engine (Multi-Tenant Isolated)
 * Zero-dependency, ultra-fast local database for offline POS operations.
 * Tenant-partitioned using dynamic database namespace: HBOS_Store_Biz_${business_id}
 */

const DB_VERSION = 1;

let currentBusinessId = null;
let dbInstance = null;

export function getActiveBusinessId() {
  if (currentBusinessId) return currentBusinessId;
  const stored = localStorage.getItem('hbos_business_id');
  return stored ? String(stored) : 'default';
}

export function getDatabaseName(businessId = null) {
  const biz = businessId || getActiveBusinessId();
  return `HBOS_Store_Biz_${biz}`;
}

export function openTenantDatabase(businessId) {
  const targetBiz = businessId ? String(businessId) : getActiveBusinessId();
  if (dbInstance && currentBusinessId !== targetBiz) {
    closeTenantDatabase();
  }
  currentBusinessId = targetBiz;
  return openLocalDb(currentBusinessId);
}

export function closeTenantDatabase() {
  if (dbInstance) {
    try {
      dbInstance.close();
    } catch (e) {
      console.warn('[HBOS LocalDB] Error closing database instance:', e);
    }
    dbInstance = null;
  }
  currentBusinessId = null;
}

export function openLocalDb(businessId = null) {
  const targetBiz = businessId ? String(businessId) : getActiveBusinessId();
  
  if (dbInstance && currentBusinessId === targetBiz) {
    return Promise.resolve(dbInstance);
  }

  if (dbInstance && currentBusinessId !== targetBiz) {
    closeTenantDatabase();
  }

  currentBusinessId = targetBiz;
  const dbName = getDatabaseName(currentBusinessId);

  return new Promise((resolve, reject) => {
    const request = indexedDB.open(dbName, DB_VERSION);

    request.onupgradeneeded = (event) => {
      const db = event.target.result;

      // 1. Products Table
      if (!db.objectStoreNames.contains('products')) {
        const productStore = db.createObjectStore('products', { keyPath: 'id' });
        productStore.createIndex('name', 'name', { unique: false });
        productStore.createIndex('sku', 'sku', { unique: false });
        productStore.createIndex('category_id', 'category_id', { unique: false });
        productStore.createIndex('brand_id', 'brand_id', { unique: false });
      }

      // 2. Categories Table
      if (!db.objectStoreNames.contains('categories')) {
        db.createObjectStore('categories', { keyPath: 'id' });
      }

      // 3. Brands Table
      if (!db.objectStoreNames.contains('brands')) {
        db.createObjectStore('brands', { keyPath: 'id' });
      }

      // 4. Customers Table
      if (!db.objectStoreNames.contains('customers')) {
        const customerStore = db.createObjectStore('customers', { keyPath: 'id' });
        customerStore.createIndex('name', 'name', { unique: false });
        customerStore.createIndex('phone', 'phone', { unique: false });
      }

      // 5. Offline Sales Table
      if (!db.objectStoreNames.contains('sales')) {
        const saleStore = db.createObjectStore('sales', { keyPath: 'idempotency_key' });
        saleStore.createIndex('status', 'status', { unique: false });
        saleStore.createIndex('created_at', 'created_at', { unique: false });
      }

      // 6. Khata Debt Payments Table
      if (!db.objectStoreNames.contains('khata_payments')) {
        const paymentStore = db.createObjectStore('khata_payments', { keyPath: 'idempotency_key' });
        paymentStore.createIndex('customer_id', 'customer_id', { unique: false });
        paymentStore.createIndex('status', 'status', { unique: false });
      }

      // 7. Expenses Table
      if (!db.objectStoreNames.contains('expenses')) {
        const expenseStore = db.createObjectStore('expenses', { keyPath: 'idempotency_key' });
        expenseStore.createIndex('status', 'status', { unique: false });
      }

      // 8. Outbox Queue Table (with tenant and user attribution)
      if (!db.objectStoreNames.contains('outbox')) {
        const outboxStore = db.createObjectStore('outbox', { keyPath: 'idempotency_key' });
        outboxStore.createIndex('status', 'status', { unique: false });
        outboxStore.createIndex('type', 'type', { unique: false });
      }

      // 9. Local Images Table (Base64 Product & Store Images)
      if (!db.objectStoreNames.contains('images')) {
        db.createObjectStore('images', { keyPath: 'key' });
      }
    };

    request.onsuccess = (event) => {
      dbInstance = event.target.result;
      resolve(dbInstance);
    };

    request.onerror = (event) => {
      console.error('[HBOS LocalDB] IndexedDB error:', event.target.error);
      reject(event.target.error);
    };
  });
}

// Generic CRUD Utilities Scoped to Active Tenant DB
export async function getStoreItems(storeName, businessId = null) {
  const db = await openLocalDb(businessId);
  return new Promise((resolve, reject) => {
    const tx = db.transaction(storeName, 'readonly');
    const store = tx.objectStore(storeName);
    const req = store.getAll();
    req.onsuccess = () => resolve(req.result || []);
    req.onerror = () => reject(req.error);
  });
}

export async function putStoreItem(storeName, item, businessId = null) {
  const db = await openLocalDb(businessId);
  return new Promise((resolve, reject) => {
    const tx = db.transaction(storeName, 'readwrite');
    const store = tx.objectStore(storeName);
    const req = store.put(item);
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => reject(req.error);
  });
}

export async function bulkPutStoreItems(storeName, items, businessId = null) {
  const db = await openLocalDb(businessId);
  return new Promise((resolve, reject) => {
    const tx = db.transaction(storeName, 'readwrite');
    const store = tx.objectStore(storeName);
    items.forEach((item) => store.put(item));
    tx.oncomplete = () => resolve(true);
    tx.onerror = () => reject(tx.error);
  });
}

export async function deleteStoreItem(storeName, key, businessId = null) {
  const db = await openLocalDb(businessId);
  return new Promise((resolve, reject) => {
    const tx = db.transaction(storeName, 'readwrite');
    const store = tx.objectStore(storeName);
    const req = store.delete(key);
    req.onsuccess = () => resolve(true);
    req.onerror = () => reject(req.error);
  });
}

/**
 * Get count of actionable pending or failed outbox items for the current device.
 * Excludes already SYNCED records. Zero double counting.
 */
export async function getOutboxPendingCount(businessId = null, userId = null) {
  if (typeof businessId === 'object' && businessId !== null) {
    userId = businessId.userId ?? null;
    businessId = businessId.businessId ?? null;
  }
  try {
    const items = await getStoreItems('outbox', businessId);
    return items.filter((item) => {
      const isPendingOrFailed = item.status === 'PENDING' || item.status === 'FAILED';
      if (!isPendingOrFailed) return false;
      if (userId && item.user_id && String(item.user_id) !== String(userId)) {
        return false;
      }
      return true;
    }).length;
  } catch (e) {
    console.warn('[HBOS LocalDB] Error getting outbox pending count:', e);
    return 0;
  }
}

/**
 * Update an existing outbox item's status, error, and timestamps.
 */
export async function updateOutboxItem(idempotencyKey, updates = {}, businessId = null) {
  const items = await getStoreItems('outbox', businessId);
  const existing = items.find((i) => i.idempotency_key === idempotencyKey);
  if (!existing) return null;

  const merged = { ...existing, ...updates };
  await putStoreItem('outbox', merged, businessId);
  return merged;
}

/**
 * Legacy compatibility bridge: Detects any old pending rows in 'sales' or
 * 'khata_payments' and mirrors them idempotently into 'outbox' without data loss.
 */
export async function migrateLegacyPendingRows(businessId = null) {
  try {
    const outboxItems = await getStoreItems('outbox', businessId);
    const outboxKeys = new Set(outboxItems.map((o) => o.idempotency_key));
    let migratedCount = 0;

    // 1. Check legacy sales
    const sales = await getStoreItems('sales', businessId);
    for (const sale of sales) {
      if ((sale.sync_status === 'PENDING' || !sale.sync_status) && sale.idempotency_key && !outboxKeys.has(sale.idempotency_key)) {
        await putStoreItem('outbox', {
          idempotency_key: sale.idempotency_key,
          type: 'sale',
          payload: sale,
          business_id: sale.business_id || businessId || getActiveBusinessId(),
          branch_id: sale.branch_id || null,
          user_id: sale.user_id || null,
          status: 'PENDING',
          created_at: sale.created_at || new Date().toISOString(),
          occurred_at: sale.created_at || new Date().toISOString(),
          retry_count: 0,
          last_error: null,
          synced_at: null,
        }, businessId);
        outboxKeys.add(sale.idempotency_key);
        migratedCount++;
      }
    }

    // 2. Check legacy khata_payments
    const payments = await getStoreItems('khata_payments', businessId);
    for (const p of payments) {
      if ((p.sync_status === 'PENDING' || !p.sync_status) && p.idempotency_key && !outboxKeys.has(p.idempotency_key)) {
        await putStoreItem('outbox', {
          idempotency_key: p.idempotency_key,
          type: 'khata_payment',
          payload: p,
          business_id: p.business_id || businessId || getActiveBusinessId(),
          branch_id: p.branch_id || null,
          user_id: p.user_id || null,
          status: 'PENDING',
          created_at: p.created_at || new Date().toISOString(),
          occurred_at: p.created_at || new Date().toISOString(),
          retry_count: 0,
          last_error: null,
          synced_at: null,
        }, businessId);
        outboxKeys.add(p.idempotency_key);
        migratedCount++;
      }
    }

    // 3. Check legacy expenses (Reconciliation Issue 5 / Section 29)
    const expenses = await getStoreItems('expenses', businessId);
    for (const exp of expenses) {
      if ((exp.sync_status === 'PENDING' || !exp.sync_status) && exp.idempotency_key && !outboxKeys.has(exp.idempotency_key)) {
        await putStoreItem('outbox', {
          idempotency_key: exp.idempotency_key,
          type: 'expense',
          payload: exp,
          business_id: exp.business_id || businessId || getActiveBusinessId(),
          branch_id: exp.branch_id || null,
          user_id: exp.user_id || null,
          status: 'PENDING',
          created_at: exp.created_at || new Date().toISOString(),
          occurred_at: exp.created_at || new Date().toISOString(),
          retry_count: 0,
          last_error: null,
          synced_at: null,
        }, businessId);
        outboxKeys.add(exp.idempotency_key);
        migratedCount++;
      }
    }

    return migratedCount;
  } catch (e) {
    console.warn('[HBOS LocalDB] Legacy pending migration error:', e);
    return 0;
  }
}

/**
 * Atomic multi-store commit for offline sales (Reconciliation Issue 5 / Section 26-28).
 * Executes outbox, sales read-snapshot, local product cache decrement, and customer
 * debt balance update in a single native IndexedDB readwrite transaction.
 * If any store write fails, the entire transaction rolls back cleanly.
 */
export async function executeAtomicOfflineSaleCommit({
  outboxEntry,
  saleRecord,
  productsToUpdate = [],
  customerToUpdate = null,
  businessId = null,
}) {
  const db = await openLocalDb(businessId);
  const storeNames = ['outbox', 'sales'];
  if (productsToUpdate.length > 0) storeNames.push('products');
  if (customerToUpdate) storeNames.push('customers');

  return new Promise((resolve, reject) => {
    const tx = db.transaction(storeNames, 'readwrite');

    tx.oncomplete = () => resolve(true);
    tx.onerror = (e) => reject(e.target.error || new Error('Atomic offline commit failed'));
    tx.onabort = (e) => reject(e.target.error || new Error('Atomic offline commit aborted'));

    // 1. Durable outbox queue entry
    tx.objectStore('outbox').put(outboxEntry);

    // 2. Local sale read snapshot
    tx.objectStore('sales').put(saleRecord);

    // 3. Local products cache stock update
    if (productsToUpdate.length > 0) {
      const prodStore = tx.objectStore('products');
      for (const prod of productsToUpdate) {
        prodStore.put(prod);
      }
    }

    // 4. Local customer khata debt balance update
    if (customerToUpdate) {
      tx.objectStore('customers').put(customerToUpdate);
    }
  });
}


export default {
  openLocalDb,
  openTenantDatabase,
  closeTenantDatabase,
  getDatabaseName,
  getActiveBusinessId,
  getStoreItems,
  putStoreItem,
  bulkPutStoreItems,
  deleteStoreItem,
  getOutboxPendingCount,
  updateOutboxItem,
  migrateLegacyPendingRows
};
