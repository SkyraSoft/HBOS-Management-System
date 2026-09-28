/**
 * HBOS Client-Side Native IndexedDB Engine
 * Zero-dependency, ultra-fast local database for offline POS operations.
 */

const DB_NAME = 'HBOS_Local_Store_DB';
const DB_VERSION = 1;

let dbInstance = null;

export function openLocalDb() {
  if (dbInstance) {
    return Promise.resolve(dbInstance);
  }

  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);

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

      // 8. Outbox Queue Table
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
      console.error('IndexedDB error:', event.target.error);
      reject(event.target.error);
    };
  });
}

// Generic CRUD Utilities
export async function getStoreItems(storeName) {
  const db = await openLocalDb();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(storeName, 'readonly');
    const store = tx.objectStore(storeName);
    const req = store.getAll();
    req.onsuccess = () => resolve(req.result || []);
    req.onerror = () => reject(req.error);
  });
}

export async function putStoreItem(storeName, item) {
  const db = await openLocalDb();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(storeName, 'readwrite');
    const store = tx.objectStore(storeName);
    const req = store.put(item);
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => reject(req.error);
  });
}

export async function bulkPutStoreItems(storeName, items) {
  const db = await openLocalDb();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(storeName, 'readwrite');
    const store = tx.objectStore(storeName);
    items.forEach((item) => store.put(item));
    tx.oncomplete = () => resolve(true);
    tx.onerror = () => reject(tx.error);
  });
}

export async function deleteStoreItem(storeName, key) {
  const db = await openLocalDb();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(storeName, 'readwrite');
    const store = tx.objectStore(storeName);
    const req = store.delete(key);
    req.onsuccess = () => resolve(true);
    req.onerror = () => reject(req.error);
  });
}
