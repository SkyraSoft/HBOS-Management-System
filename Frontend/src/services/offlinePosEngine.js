/**
 * HBOS Offline Transaction Engine & Outbox Manager
 * Atomically completes sales, updates local stock, manages Khata debt,
 * and maintains the sync outbox queue.
 */

import { putStoreItem, getStoreItems, deleteStoreItem } from './localDb.js';
import { invalidateSearchCache } from './offlineSearchService.js';

function generateUuid() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}

export async function createOfflineSale({
  branchId,
  customerId = null,
  items = [],
  discount = 0,
  tax = 0,
  paidAmount = 0,
  paymentMethod = 'cash',
  financialAccountId = null,
  notes = '',
}) {
  const idempotencyKey = generateUuid();
  const timestamp = new Date().toISOString();

  // Calculate Subtotal & Total
  let subtotal = 0;
  const processedItems = items.map((item) => {
    const qty = Number(item.quantity || 1);
    const price = Number(item.unit_price || item.selling_price || 0);
    const itemDiscount = Number(item.discount || 0);
    const itemTotal = qty * price - itemDiscount;
    subtotal += itemTotal;

    return {
      product_id: item.product_id || item.id,
      quantity: qty,
      unit_price: price,
      discount: itemDiscount,
      total: itemTotal,
    };
  });

  const totalDiscount = Number(discount || 0);
  const totalTax = Number(tax || 0);
  const grandTotal = Math.max(0, subtotal - totalDiscount + totalTax);

  const saleRecord = {
    idempotency_key: idempotencyKey,
    client_uuid: idempotencyKey,
    invoice_number: 'INV-OFFLINE-' + Math.floor(100000 + Math.random() * 900000),
    branch_id: branchId,
    customer_id: customerId,
    subtotal: subtotal,
    discount: totalDiscount,
    tax: totalTax,
    total: grandTotal,
    paid_amount: Number(paidAmount || 0),
    payment_method: paymentMethod,
    financial_account_id: financialAccountId,
    notes: notes,
    status: 'completed',
    sync_status: 'PENDING',
    created_at: timestamp,
    items: processedItems,
  };

  // 1. Save Sale to Local DB
  await putStoreItem('sales', saleRecord);

  // 2. Decrement Local Stock in Products Table
  const localProducts = await getStoreItems('products');
  for (const item of processedItems) {
    const prod = localProducts.find((p) => String(p.id) === String(item.product_id));
    if (prod) {
      prod.stock = Math.max(0, (prod.stock || 0) - item.quantity);
      await putStoreItem('products', prod);
    }
  }
  invalidateSearchCache();

  // 3. Update Local Customer Khata Debt Balance
  if (paymentMethod === 'khata' && customerId) {
    const localCustomers = await getStoreItems('customers');
    const cust = localCustomers.find((c) => String(c.id) === String(customerId));
    if (cust) {
      const debtAdd = Math.max(0, grandTotal - Number(paidAmount || 0));
      cust.balance = (Number(cust.balance || 0) + debtAdd).toFixed(2);
      await putStoreItem('customers', cust);
    }
  }

  // 4. Enqueue into Outbox Queue
  const outboxEntry = {
    idempotency_key: idempotencyKey,
    type: 'sale',
    payload: saleRecord,
    status: 'PENDING',
    created_at: timestamp,
  };
  await putStoreItem('outbox', outboxEntry);

  return saleRecord;
}

export async function createOfflineKhataPayment({ branchId, customerId, amount, paymentMethod = 'cash', notes = '' }) {
  const idempotencyKey = generateUuid();
  const timestamp = new Date().toISOString();

  const paymentRecord = {
    idempotency_key: idempotencyKey,
    client_uuid: idempotencyKey,
    branch_id: branchId,
    customer_id: customerId,
    amount: Number(amount),
    payment_method: paymentMethod,
    notes: notes,
    status: 'posted',
    sync_status: 'PENDING',
    created_at: timestamp,
  };

  // 1. Save Payment to Local DB
  await putStoreItem('khata_payments', paymentRecord);

  // 2. Reduce Customer Khata Debt Balance locally
  const localCustomers = await getStoreItems('customers');
  const cust = localCustomers.find((c) => String(c.id) === String(customerId));
  if (cust) {
    cust.balance = Math.max(0, Number(cust.balance || 0) - Number(amount)).toFixed(2);
    await putStoreItem('customers', cust);
  }

  // 3. Enqueue into Outbox
  const outboxEntry = {
    idempotency_key: idempotencyKey,
    type: 'khata_payment',
    payload: paymentRecord,
    status: 'PENDING',
    created_at: timestamp,
  };
  await putStoreItem('outbox', outboxEntry);

  return paymentRecord;
}

export async function getPendingOutboxQueue() {
  const outbox = await getStoreItems('outbox');
  return outbox.filter((item) => item.status === 'PENDING');
}

export async function markOutboxItemsSynced(syncedKeys = []) {
  for (const key of syncedKeys) {
    const item = await putStoreItem('outbox', { idempotency_key: key, status: 'SYNCED', synced_at: new Date().toISOString() });
  }
}
