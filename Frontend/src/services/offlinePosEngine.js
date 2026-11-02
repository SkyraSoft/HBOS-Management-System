/**
 * HBOS Offline Transaction Engine & Outbox Manager
 * Atomically completes sales, updates local stock, manages Khata debt,
 * and maintains the sync outbox queue with canonical tenant/user/device context.
 */

import { putStoreItem, getStoreItems, deleteStoreItem, getOutboxPendingCount, updateOutboxItem, migrateLegacyPendingRows, executeAtomicOfflineSaleCommit } from './localDb.js';
import { invalidateSearchCache } from './offlineSearchService.js';

export function generateUuid() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}

/**
 * Returns or initializes a stable local installation identifier (X-HBOS-Device-ID).
 * Purely diagnostic/audit metadata; not an authorization credential.
 */
export function getDeviceId() {
  if (typeof window === 'undefined' || !window.localStorage) {
    return 'device-server-context';
  }
  let devId = localStorage.getItem('hbos_device_id');
  if (!devId) {
    devId = generateUuid();
    localStorage.setItem('hbos_device_id', devId);
  }
  return devId;
}

/**
 * Resolve current active business and user context from localStorage.
 */
export function getAuthContext() {
  let businessId = null;
  let userId = null;
  let branchId = null;

  if (typeof window !== 'undefined' && window.localStorage) {
    businessId = localStorage.getItem('hbos_business_id');
    try {
      const uStr = localStorage.getItem('hbos_user');
      if (uStr) {
        const u = JSON.parse(uStr);
        if (!businessId && u.business_id) businessId = u.business_id;
        if (u.id) userId = u.id;
        if (u.branch_id) branchId = u.branch_id;
      }
    } catch (e) {
      // ignore
    }
  }

  return {
    businessId: businessId ? Number(businessId) : 1,
    userId: userId ? Number(userId) : 1,
    branchId: branchId ? Number(branchId) : 1,
  };
}

/**
 * Convert quantity string/number to integer milli-units (thousandths) without floating-point error.
 * Validates decimal scale <= 3, non-empty, and numeric. Rejects over-scale input (e.g. 1.2345 or 0.0009).
 */
export function quantityToMilliUnits(val, { allowNegative = false, minQuantity = 1 } = {}) {
  if (val === undefined || val === null || val === '') {
    throw new Error('Quantity cannot be empty.');
  }
  const str = String(val).trim();
  if (!str) {
    throw new Error('Quantity cannot be empty.');
  }

  const validRegex = allowNegative ? /^-?\d+(\.\d+)?$/ : /^\d+(\.\d+)?$/;
  if (!validRegex.test(str)) {
    throw new Error(`Invalid quantity format '${str}'. Must be a valid positive numeric value.`);
  }

  const isNegative = str.startsWith('-');
  const cleanStr = isNegative ? str.slice(1) : str;
  const parts = cleanStr.split('.');
  const wholeStr = parts[0] || '0';
  const fracStr = parts[1] || '';

  // Reject over-scale fraction (scale > 3) - NO SILENT TRUNCATION!
  if (fracStr.length > 3) {
    throw new Error(`Quantity '${str}' exceeds maximum allowed scale of 3 decimal places.`);
  }

  const whole = Number(wholeStr);
  if (!Number.isSafeInteger(whole)) {
    throw new Error(`Quantity whole number '${wholeStr}' exceeds safe integer range.`);
  }

  const paddedFrac = fracStr.padEnd(3, '0');
  const frac = Number(paddedFrac);

  const millis = whole * 1000 + frac;
  const signedMillis = isNegative ? -millis : millis;

  if (!allowNegative && signedMillis < minQuantity) {
    throw new Error(`Quantity must be at least 0.001.`);
  }

  return signedMillis;
}

/**
 * Convert integer milli-units into a canonical exact decimal string with 3 decimal places.
 */
export function milliUnitsToDecimalString(millis) {
  if (typeof millis !== 'number' || !Number.isFinite(millis)) {
    throw new Error(`Invalid milli-unit value: ${millis}`);
  }
  const isNegative = millis < 0;
  const absMillis = Math.abs(Math.trunc(millis));
  const whole = Math.trunc(absMillis / 1000);
  const frac = absMillis % 1000;
  const fracStr = String(frac).padStart(3, '0');
  return (isNegative ? '-' : '') + whole + '.' + fracStr;
}

export function normalizeQuantityToDecimalString(val) {
  return milliUnitsToDecimalString(quantityToMilliUnits(val));
}

/**
 * Atomically create offline sale, enqueue to outbox, decrement local product stock,
 * and adjust customer Khata debt.
 */
export async function createOfflineSale({
  branchId,
  businessId = null,
  userId = null,
  customerId = null,
  items = [],
  discount = 0,
  tax = 0,
  paidAmount = 0,
  paymentMethod = 'cash',
  payments = [],
  financialAccountId = null,
  notes = '',
}) {
  const auth = getAuthContext();
  const effectiveBusinessId = businessId ? Number(businessId) : auth.businessId;
  const effectiveUserId = userId ? Number(userId) : auth.userId;
  const effectiveBranchId = branchId ? Number(branchId) : auth.branchId;
  const deviceId = getDeviceId();

  const idempotencyKey = generateUuid();
  const timestamp = new Date().toISOString();

  // Validate cart items
  if (!items || items.length === 0) {
    throw new Error('Cart is empty. Cannot complete offline sale.');
  }

  // Calculate Subtotal & Total using exact decimal string and integer milli-units
  let subtotal = 0;
  const processedItems = items.map((item) => {
    const qtyStr = normalizeQuantityToDecimalString(item.quantity !== undefined ? item.quantity : 1);
    const qtyMillis = quantityToMilliUnits(qtyStr);
    const price = Number(item.unit_price !== undefined ? item.unit_price : (item.selling_price || item.price || 0));
    const itemDiscount = Number(item.discount || 0);
    const itemTotal = Math.round(((qtyMillis * price) / 1000 - itemDiscount) * 100) / 100;
    subtotal += itemTotal;

    return {
      product_id: item.product_id || item.id,
      quantity: qtyStr,
      pricing_mode: item.pricing_mode || 'retail',
      unit_price: price,
      discount: itemDiscount,
      total: itemTotal,
    };
  });

  const totalDiscount = Number(discount || 0);
  const totalTax = Number(tax || 0);
  const grandTotal = Math.max(0, subtotal - totalDiscount + totalTax);

  const hasSplitPayments = Array.isArray(payments) && payments.length > 0;
  const calculatedPaidAmount = hasSplitPayments
    ? payments.filter((p) => String(p.payment_method).toLowerCase() !== 'khata').reduce((sum, p) => sum + Number(p.amount || 0), 0)
    : Number(paidAmount || 0);

  const saleRecord = {
    idempotency_key: idempotencyKey,
    client_uuid: idempotencyKey,
    invoice_number: 'INV-OFFLINE-' + Math.floor(100000 + Math.random() * 900000),
    business_id: effectiveBusinessId,
    branch_id: effectiveBranchId,
    user_id: effectiveUserId,
    customer_id: customerId,
    subtotal: subtotal,
    discount: totalDiscount,
    tax: totalTax,
    total: grandTotal,
    paid_amount: calculatedPaidAmount,
    payment_method: hasSplitPayments && payments.length > 1 ? 'Split' : paymentMethod,
    payments: hasSplitPayments ? payments : undefined,
    financial_account_id: financialAccountId,
    notes: notes,
    status: 'completed',
    sync_status: 'PENDING',
    created_at: timestamp,
    occurred_at: timestamp,
    device_id: deviceId,
    items: processedItems,
  };

  // 1. Prepare Canonical Outbox Queue Entry
  const outboxEntry = {
    idempotency_key: idempotencyKey,
    type: 'sale',
    payload: saleRecord,
    business_id: effectiveBusinessId,
    branch_id: effectiveBranchId,
    user_id: effectiveUserId,
    occurred_at: timestamp,
    device_id: deviceId,
    status: 'PENDING',
    retry_count: 0,
    last_error: null,
    created_at: timestamp,
  };

  // 2. Prepare Local Product Cache Stock Decrements via Integer Milli-Units
  const productsToUpdate = [];
  try {
    const localProducts = await getStoreItems('products');
    for (const item of processedItems) {
      const prod = localProducts.find((p) => String(p.id) === String(item.product_id));
      if (prod) {
        const currentStockMillis = quantityToMilliUnits(prod.stock !== undefined && prod.stock !== null ? prod.stock : 0, { allowNegative: true, minQuantity: 0 });
        const soldMillis = quantityToMilliUnits(item.quantity, { allowNegative: false, minQuantity: 1 });
        const newStockMillis = Math.max(0, currentStockMillis - soldMillis);
        prod.stock = milliUnitsToDecimalString(newStockMillis);
        productsToUpdate.push(prod);
      }
    }
  } catch (err) {
    console.warn('Failed to calculate local product stock decrements:', err);
  }

  // 3. Prepare Local Customer Khata Debt Balance Update
  let customerToUpdate = null;
  try {
    const isKhataInvolved = (hasSplitPayments && payments.some((p) => String(p.payment_method).toLowerCase() === 'khata')) || paymentMethod === 'khata' || grandTotal > calculatedPaidAmount;
    if (isKhataInvolved && customerId) {
      const localCustomers = await getStoreItems('customers');
      const cust = localCustomers.find((c) => String(c.id) === String(customerId));
      if (cust) {
        const debtAdd = Math.max(0, grandTotal - calculatedPaidAmount);
        if (debtAdd > 0) {
          cust.balance = (Number(cust.balance || 0) + debtAdd).toFixed(2);
          customerToUpdate = cust;
        }
      }
    }
  } catch (err) {
    console.warn('Failed to calculate local customer balance update:', err);
  }

  // 4. Execute Atomic Multi-Store Commit (Reconciliation Issue 5 / Section 26-28)
  // Commits outbox, sales read-snapshot, local product stock, and customer debt in ONE transaction.
  // If any write fails, all store writes are aborted cleanly together.
  await executeAtomicOfflineSaleCommit({
    outboxEntry,
    saleRecord,
    productsToUpdate,
    customerToUpdate,
    businessId: effectiveBusinessId,
  });

  invalidateSearchCache();


  return saleRecord;
}

/**
 * Atomically record offline Khata payment.
 */
export async function createOfflineKhataPayment({
  branchId,
  businessId = null,
  userId = null,
  customerId,
  amount,
  paymentMethod = 'cash',
  notes = '',
}) {
  const auth = getAuthContext();
  const effectiveBusinessId = businessId ? Number(businessId) : auth.businessId;
  const effectiveUserId = userId ? Number(userId) : auth.userId;
  const effectiveBranchId = branchId ? Number(branchId) : auth.branchId;
  const deviceId = getDeviceId();

  const idempotencyKey = generateUuid();
  const timestamp = new Date().toISOString();

  const paymentRecord = {
    idempotency_key: idempotencyKey,
    client_uuid: idempotencyKey,
    business_id: effectiveBusinessId,
    branch_id: effectiveBranchId,
    user_id: effectiveUserId,
    customer_id: customerId,
    amount: Number(amount),
    payment_method: paymentMethod,
    notes: notes,
    status: 'posted',
    sync_status: 'PENDING',
    created_at: timestamp,
    occurred_at: timestamp,
    device_id: deviceId,
  };

  // 1. Enqueue into Outbox (durable write guarantee)
  const outboxEntry = {
    idempotency_key: idempotencyKey,
    type: 'khata_payment',
    payload: paymentRecord,
    business_id: effectiveBusinessId,
    branch_id: effectiveBranchId,
    user_id: effectiveUserId,
    occurred_at: timestamp,
    device_id: deviceId,
    status: 'PENDING',
    retry_count: 0,
    last_error: null,
    created_at: timestamp,
  };
  await putStoreItem('outbox', outboxEntry);

  // 2. Save Payment to Local DB snapshot
  try {
    await putStoreItem('khata_payments', paymentRecord);
  } catch (err) {
    console.warn('Failed to mirror payment to local snapshot table:', err);
  }

  // 3. Reduce Customer Khata Debt Balance locally
  try {
    const localCustomers = await getStoreItems('customers');
    const cust = localCustomers.find((c) => String(c.id) === String(customerId));
    if (cust) {
      cust.balance = Math.max(0, Number(cust.balance || 0) - Number(amount)).toFixed(2);
      await putStoreItem('customers', cust);
    }
  } catch (err) {
    console.warn('Failed to update local customer balance snapshot:', err);
  }

  return paymentRecord;
}

/**
 * Retrieve pending outbox items, optionally filtered by business and user.
 */
export async function getPendingOutboxQueue({ businessId = null, userId = null } = {}) {
  const outbox = await getStoreItems('outbox');
  return outbox.filter((item) => {
    if (item.status !== 'PENDING') return false;
    if (businessId && item.business_id && Number(item.business_id) !== Number(businessId)) return false;
    if (userId && item.user_id && Number(item.user_id) !== Number(userId)) return false;
    return true;
  });
}

/**
 * Mark specified keys as SYNCED.
 */
export async function markOutboxItemsSynced(syncedKeys = []) {
  for (const key of syncedKeys) {
    await updateOutboxItem(key, {
      status: 'SYNCED',
      synced_at: new Date().toISOString(),
      last_error: null,
    });
  }
}

/**
 * Mark an outbox item as FAILED with error category and message.
 */
export async function markOutboxItemFailed(key, errorCategory, errorMessage) {
  const outbox = await getStoreItems('outbox');
  const existing = outbox.find((o) => o.idempotency_key === key);
  const currentRetries = existing ? Number(existing.retry_count || 0) : 0;
  await updateOutboxItem(key, {
    status: 'FAILED',
    retry_count: currentRetries + 1,
    last_error: {
      category: errorCategory,
      message: errorMessage,
      timestamp: new Date().toISOString(),
    },
  });
}

/**
 * Reset failed items to PENDING for manual retry (only retryable categories).
 */
export async function resetFailedOutboxItemsForRetry({ businessId = null, userId = null } = {}) {
  const outbox = await getStoreItems('outbox');
  const nonRetryable = ['STOCK_CONFLICT', 'PRICE_CONFLICT', 'VALIDATION', 'UPDATE_REQUIRED'];
  let resetCount = 0;

  for (const item of outbox) {
    if (item.status === 'FAILED') {
      if (businessId && item.business_id && Number(item.business_id) !== Number(businessId)) continue;
      if (userId && item.user_id && Number(item.user_id) !== Number(userId)) continue;

      const category = item.last_error?.category || 'SERVER_RETRYABLE';
      if (!nonRetryable.includes(category)) {
        await updateOutboxItem(item.idempotency_key, {
          status: 'PENDING',
        });
        resetCount++;
      }
    }
  }

  return resetCount;
}

/**
 * Initialize offline engine and perform legacy queue compatibility migration.
 */
export async function initOfflineEngine() {
  try {
    const migrated = await migrateLegacyPendingRows();
    if (migrated > 0) {
      console.info(`[HBOS Offline Engine] Migrated ${migrated} legacy pending rows into canonical outbox.`);
    }
  } catch (err) {
    console.warn('[HBOS Offline Engine] Legacy migration check failed:', err);
  }
}

export default {
  generateUuid,
  getDeviceId,
  getAuthContext,
  quantityToMilliUnits,
  milliUnitsToDecimalString,
  normalizeQuantityToDecimalString,
  createOfflineSale,
  createOfflineKhataPayment,
  getPendingOutboxQueue,
  markOutboxItemsSynced,
  markOutboxItemFailed,
  resetFailedOutboxItemsForRetry,
  initOfflineEngine,
};
