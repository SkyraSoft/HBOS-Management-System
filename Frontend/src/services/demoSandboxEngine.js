/**
 * HBOS Demo Sandbox Engine
 * Completely isolated, self-contained local retail simulation running inside the client.
 * Operates without modifying live merchant databases, preventing any test sales from reaching sync queues.
 * Storage key: HBOS_Store_Biz_demo_single or HBOS_Store_Biz_demo_multi
 */

import manifest from '../../../docs/release/hbos_demo_fixture_manifest.json' with { type: 'json' };
import { 
  DEMO_BRANDS, 
  DEMO_CATEGORIES, 
  DEMO_SUBCATEGORIES, 
  DEMO_PRODUCT_FAMILIES, 
  DEMO_PRODUCTS, 
  DEMO_BARCODE_ALIASES,
  generateBranchInventories
} from './demoCatalogMaster.js';
import { 
  generate7MonthDemoDataset, 
  autoRollDemoData, 
  resolvePresetBoundaries,
  formatLocalDate 
} from './demoDataGenerator.js';

const STORAGE_PREFIX = 'HBOS_Store_Biz_';

export const DEFAULT_DEMO_BRANDS = DEMO_BRANDS;
export const DEFAULT_DEMO_CATEGORIES = DEMO_CATEGORIES;
export const DEFAULT_DEMO_SUBCATEGORIES = DEMO_SUBCATEGORIES;
export const DEFAULT_DEMO_PRODUCT_FAMILIES = DEMO_PRODUCT_FAMILIES;
export const DEFAULT_DEMO_PRODUCTS = DEMO_PRODUCTS;
export const DEFAULT_DEMO_BARCODE_ALIASES = DEMO_BARCODE_ALIASES;

function calcComparison(current, previous) {
  const curr = Math.round(Number(current || 0) * 100) / 100;
  const prev = Math.round(Number(previous || 0) * 100) / 100;

  if (prev === 0 && curr === 0) {
    return { current: curr, previous: prev, change_percentage: 0.0, state: 'neutral' };
  }
  if (prev === 0 && curr > 0) {
    return { current: curr, previous: 0.00, change_percentage: null, state: 'new' };
  }
  const change = ((curr - prev) / prev) * 100.0;
  return {
    current: curr,
    previous: prev,
    change_percentage: Math.round(change * 10) / 10,
    state: change > 0 ? 'increase' : (change < 0 ? 'decrease' : 'neutral')
  };
}

export class DemoSandboxEngine {
  constructor() {
    this.mode = 'single'; // 'single' | 'multi'
    this.storageKey = `${STORAGE_PREFIX}demo_single`;
  }

  setMode(mode) {
    this.mode = mode === 'multi' ? 'multi' : 'single';
    this.storageKey = `${STORAGE_PREFIX}demo_${this.mode}`;
    this.ensureInitialized();
  }

  getStorageKey() {
    return this.storageKey;
  }

  /**
   * Ensure demo sandbox is initialized.
   * If existing demo state is detected, autoRollDemoData() verifies if calendar
   * months have elapsed and dynamically shifts transactions forward to today's month.
   */
  ensureInitialized() {
    const raw = localStorage.getItem(this.storageKey);
    if (!raw) {
      this.resetToDefaults();
      return;
    }

    try {
      let state = JSON.parse(raw);

      // Upgrade check: if missing 7-month dataset or < 500 sales, or catalog_version !== '2.0.0', or brands.length !== 4, or products.length < 32:
      if (!state.is_7_month_dataset || !state.sales || state.sales.length < 500 || state.catalog_version !== '2.0.0' || !Array.isArray(state.brands) || state.brands.length !== 4 || !Array.isArray(state.products) || state.products.length < 32) {
        this.resetToDefaults();
        return;
      }

      // Verify if dates need auto-rolling forward into the current calendar month
      const beforeAnchor = `${state.anchor_year}-${state.anchor_month}`;
      state = autoRollDemoData(state, this.mode, new Date());
      const afterAnchor = `${state.anchor_year}-${state.anchor_month}`;

      let updated = false;
      if (beforeAnchor !== afterAnchor) {
        updated = true;
      }

      // Ensure categories, subcategories, and brands are populated
      if (!Array.isArray(state.categories) || state.categories.length === 0) {
        state.categories = JSON.parse(JSON.stringify(DEFAULT_DEMO_CATEGORIES));
        updated = true;
      }
      if (!Array.isArray(state.subcategories) || state.subcategories.length === 0) {
        state.subcategories = JSON.parse(JSON.stringify(DEFAULT_DEMO_SUBCATEGORIES));
        updated = true;
      }
      if (!Array.isArray(state.brands) || state.brands.length === 0) {
        state.brands = JSON.parse(JSON.stringify(DEFAULT_DEMO_BRANDS));
        updated = true;
      }

      // If rolled or repaired, persist updated state
      if (updated) {
        this.saveState(state);
      }
    } catch (e) {
      console.warn('Error reading demo state, resetting to defaults:', e);
      this.resetToDefaults();
    }
  }

  getState() {
    this.ensureInitialized();
    try {
      return JSON.parse(localStorage.getItem(this.storageKey)) || {};
    } catch (e) {
      this.resetToDefaults();
      return JSON.parse(localStorage.getItem(this.storageKey));
    }
  }

  saveState(state) {
    try {
      localStorage.setItem(this.storageKey, JSON.stringify(state));
    } catch (e) {
      console.warn('Storage quota exceeded, defensively pruning historical sales older than 4 months:', e);
      if (state && Array.isArray(state.sales) && state.sales.length > 500) {
        state.sales = state.sales.slice(0, 1000);
        try {
          localStorage.setItem(this.storageKey, JSON.stringify(state));
        } catch (e2) {
          console.error('Failed to save demo state even after pruning:', e2);
        }
      }
    }
  }

  /**
   * Reset sandbox with a rich 7-month dataset ending on the user's current runtime date.
   */
  resetToDefaults() {
    const template = this.mode === 'multi' 
      ? manifest.templates.multi_store 
      : manifest.templates.single_store;

    const now = new Date();
    const synthData = generate7MonthDemoDataset(this.mode, now);

    const initialState = {
      catalog_version: '2.0.0',
      template_id: template.template_id,
      business_name: template.business_name,
      city: template.city,
      owner_name: template.owner_name,
      phone: template.phone,
      drawer_cash: synthData.drawer_cash,
      active_session: template.active_register_session ? { 
        ...template.active_register_session,
        opened_at: now.toISOString()
      } : null,
      products: synthData.products,
      categories: JSON.parse(JSON.stringify(DEFAULT_DEMO_CATEGORIES)),
      subcategories: JSON.parse(JSON.stringify(DEFAULT_DEMO_SUBCATEGORIES)),
      brands: JSON.parse(JSON.stringify(DEFAULT_DEMO_BRANDS)),
      product_families: JSON.parse(JSON.stringify(DEFAULT_DEMO_PRODUCT_FAMILIES)),
      barcode_aliases: JSON.parse(JSON.stringify(DEFAULT_DEMO_BARCODE_ALIASES)),
      branch_inventories: this.mode === 'multi' ? (synthData.branch_inventories || generateBranchInventories(synthData.products)) : [],
      customers: synthData.customers,
      suppliers: synthData.suppliers,
      sales: synthData.sales,
      customer_payments: synthData.customer_payments,
      supplier_payments: synthData.supplier_payments,
      expenses: synthData.expenses,
      branches: this.mode === 'multi' 
        ? JSON.parse(JSON.stringify(template.branches)).map(b => ({ ...b, status: 'active' }))
        : [
            { id: 1, name: 'Main Store', code: 'MAIN-01', is_primary: true, status: 'active' }
          ],
      transfers: this.mode === 'multi' ? JSON.parse(JSON.stringify(template.in_transit_transfers || [])) : [],
      anchor_year: synthData.anchor_year,
      anchor_month: synthData.anchor_month,
      is_7_month_dataset: true,
      notifications: this.generateInitialNotifications(synthData),
      created_at: now.toISOString()
    };

    this.saveState(initialState);
    return initialState;
  }

  generateInitialNotifications(synthData = {}) {
    const now = new Date();
    const notifs = [
      {
        id: 'demo_notif_1',
        title: 'Pending Supplier Invoices',
        message: 'PKR 45,000 in reconciled wholesale payables due this month (Tapal Danedar, Unilever).',
        type: 'Alerts & Attention',
        severity: 'warning',
        action_type: 'navigate',
        action_payload: '/supplierpayable',
        action_label: 'Review Invoices',
        icon: 'bi-truck',
        colorClass: 'text-warning',
        bgClass: 'bg-warning-subtle',
        read_at: null,
        created_at: new Date(now.getTime() - 2 * 3600 * 1000).toISOString()
      },
      {
        id: 'demo_notif_2',
        title: 'Customer Khata Receivables',
        message: 'PKR 12,400 outstanding across 4 registered accounts (Tariq Mehmood, Al-Rehman Caterers).',
        type: 'Alerts & Attention',
        severity: 'warning',
        action_type: 'navigate',
        action_payload: '/khata',
        action_label: 'Open Khata Ledger',
        icon: 'bi-person-exclamation',
        colorClass: 'text-warning',
        bgClass: 'bg-warning-subtle',
        read_at: null,
        created_at: new Date(now.getTime() - 5 * 3600 * 1000).toISOString()
      }
    ];

    if (this.mode === 'multi') {
      notifs.push({
        id: 'demo_notif_3',
        title: 'Inter-Branch Stock Transfer In Transit',
        message: 'Stock Transfer TR-2026-004 dispatched from Clifton Main Store to Gulshan Outlet awaiting receipt confirmation.',
        type: 'Alerts & Attention',
        severity: 'info',
        action_type: 'navigate',
        action_payload: '/transfers',
        action_label: 'View Transfers',
        icon: 'bi-arrow-left-right',
        colorClass: 'text-info',
        bgClass: 'bg-info-subtle',
        read_at: null,
        created_at: new Date(now.getTime() - 8 * 3600 * 1000).toISOString()
      });
    }

    notifs.push(
      {
        id: 'demo_notif_4',
        title: 'Daily Register Session Reconciled',
        message: "Yesterday's cash drawer shift closed and reconciled with PKR 0 discrepancy.",
        type: 'Common / Activity',
        severity: 'info',
        action_type: 'navigate',
        action_payload: '/POS?tab=closing',
        action_label: 'View Closing',
        icon: 'bi-cash-coin',
        colorClass: 'text-success',
        bgClass: 'bg-success-subtle',
        read_at: null,
        created_at: new Date(now.getTime() - 24 * 3600 * 1000).toISOString()
      },
      {
        id: 'demo_notif_5',
        title: 'Offline Database Synchronized',
        message: 'Local encrypted database snapshot verified for network-resilient offline POS checkout.',
        type: 'Common / Activity',
        severity: 'info',
        action_type: 'navigate',
        action_payload: '/preferences',
        action_label: 'View Settings',
        icon: 'bi-shield-check',
        colorClass: 'text-primary',
        bgClass: 'bg-primary-subtle',
        read_at: new Date(now.getTime() - 48 * 3600 * 1000).toISOString(),
        created_at: new Date(now.getTime() - 48 * 3600 * 1000).toISOString()
      }
    );

    return notifs;
  }

  // --- API SIMULATION HANDLERS ---

  getProducts() {
    const state = this.getState();
    return state.products;
  }

  getCustomers() {
    const state = this.getState();
    return (state.customers || []).map(c => {
      const bal = Number(c.current_balance ?? c.balance ?? c.outstanding ?? 0);
      const cred = Number(c.credit_limit ?? c.creditLimit ?? 10000);
      const purchases = Number(c.sales ?? c.total_purchases ?? (bal > 0 ? bal * 1.5 : 4500));
      const rec = Number(c.received ?? c.total_paid ?? (purchases > bal ? purchases - bal : 0));
      return {
        ...c,
        current_balance: bal,
        balance: bal,
        outstanding: bal,
        credit_limit: cred,
        creditLimit: cred,
        sales: purchases,
        total_purchases: purchases,
        received: rec,
        status: bal > 0 ? (c.status || 'outstanding') : 'active'
      };
    });
  }

  getSuppliers() {
    const state = this.getState();
    return state.suppliers;
  }

  getSales(params = {}) {
    const state = this.getState();
    let list = [...(state.sales || [])];

    // Optional branch filter
    if (params.branch_id && params.branch_id !== 'all') {
      const bId = Number(params.branch_id);
      list = list.filter(s => s.branch_id === bId);
    }

    // Optional preset filter
    if (params.preset && params.preset !== 'all') {
      const { startDate, endDate } = resolvePresetBoundaries(params.preset, params.from, params.to);
      list = list.filter(s => {
        const d = new Date(s.created_at || s.date);
        return d >= startDate && d <= endDate;
      });
    }

    return list;
  }

  getExpenses(params = {}) {
    const state = this.getState();
    let list = [...(state.expenses || [])];

    const todayStr = new Date().toISOString().split('T')[0];
    const hasToday = list.some(e => (e.date || e.created_at || '').startsWith(todayStr));
    if (!hasToday) {
      list.unshift({
        id: 'demo_exp_today_1',
        branch_id: 1,
        category: 'Shop Maintenance',
        description: 'Store Cleaning Supplies & Sanitation',
        amount: 1450,
        date: todayStr,
        payment_method: 'Cash',
        method: 'Cash',
        created_at: new Date().toISOString()
      }, {
        id: 'demo_exp_today_2',
        branch_id: 1,
        category: 'General Expense',
        description: 'Customer & Staff Daily Tea / Refreshment Expense',
        amount: 850,
        date: todayStr,
        payment_method: 'Cash',
        method: 'Cash',
        created_at: new Date().toISOString()
      });
    }

    if (params.branch_id && params.branch_id !== 'all') {
      const bId = Number(params.branch_id);
      list = list.filter(e => e.branch_id === bId);
    }

    if (params.preset && params.preset !== 'all') {
      const { startDate, endDate } = resolvePresetBoundaries(params.preset, params.from, params.to);
      list = list.filter(e => {
        const d = new Date(e.created_at || e.date);
        return d >= startDate && d <= endDate;
      });
    }

    return list;
  }

  getExpenseCategories() {
    return [
      { id: 1, name: 'Shop Rent' },
      { id: 2, name: 'Staff Salaries' },
      { id: 3, name: 'Electricity / WAPDA' },
      { id: 4, name: 'Packaging & Bags' },
      { id: 5, name: 'Shop Maintenance' },
      { id: 6, name: 'General Expense' },
    ];
  }

  getFinancialAccounts() {
    const state = this.getState();
    return [
      { id: 1, name: 'Main Cash Drawer', type: 'cash', balance: state.drawer_cash, is_default: true },
      { id: 2, name: 'Meezan Bank Retail Acc', type: 'bank', balance: 145000.00, is_default: false },
    ];
  }

  getCustomerPayments() {
    const state = this.getState();
    return state.customer_payments || [];
  }

  getSupplierPayments() {
    const state = this.getState();
    return state.supplier_payments || [];
  }

  getCurrentSession() {
    const state = this.getState();
    return state.active_session;
  }

  openRegisterSession(openingFloat, notes = '') {
    const state = this.getState();
    state.drawer_cash = Number(openingFloat) || 0;
    state.active_session = {
      id: `demo_session_${Date.now()}`,
      status: 'open',
      opening_float: Number(openingFloat) || 0,
      notes: notes,
      opened_at: new Date().toISOString()
    };
    this.saveState(state);
    return state.active_session;
  }

  closeRegisterSession(closingCounted, notes = '') {
    const state = this.getState();
    const expected = state.drawer_cash;
    const counted = Number(closingCounted) || 0;
    const diff = counted - expected;

    const closedSession = {
      ...state.active_session,
      status: 'closed',
      closing_cash_expected: expected,
      closing_cash_counted: counted,
      discrepancy: diff,
      closed_at: new Date().toISOString(),
      notes: notes
    };
    state.active_session = null;
    this.saveState(state);
    return closedSession;
  }

  recordSale(payload) {
    const state = this.getState();
    const items = payload.items || [];
    let saleSubtotal = 0;
    let saleCost = 0;
    const recordedItems = [];

    for (const item of items) {
      const prod = state.products.find(p => p.id === Number(item.product_id));
      if (prod) {
        const qty = Number(item.quantity) || 1;
        const price = Number(item.unit_price) || prod.selling_price;
        const cost = prod.cost_price || 0;
        const subtotal = price * qty;
        const cogs = cost * qty;

        saleSubtotal += subtotal;
        saleCost += cogs;
        prod.stock = Math.max(0, Number((prod.stock - qty).toFixed(3)));

        // If multi-store, also deduct from branch inventory
        if (this.mode === 'multi' && Array.isArray(state.branch_inventories)) {
          const bId = Number(payload.branch_id) || 1;
          const bi = state.branch_inventories.find(b => b.branch_id === bId && b.product_id === prod.id);
          if (bi) {
            bi.quantity_on_hand = Math.max(0, Number((bi.quantity_on_hand - qty).toFixed(3)));
            bi.valuation = Math.round(bi.quantity_on_hand * (prod.cost_price || 0) * 100) / 100;
          }
        }

        recordedItems.push({
          product_id: prod.id,
          product_name: prod.name,
          sku: prod.sku,
          barcode: prod.barcode,
          quantity: qty,
          unit: prod.unit,
          unit_price: price,
          cost_price: cost,
          subtotal: subtotal,
          cogs: cogs,
          returned_quantity: 0
        });
      }
    }

    const pMethodLower = (payload.payment_method || 'cash').toLowerCase();
    let paidAmount = saleSubtotal;
    if (payload.paid_amount !== undefined && payload.paid_amount !== null) {
      paidAmount = Number(payload.paid_amount);
    } else if (pMethodLower === 'khata') {
      paidAmount = 0;
    }
    const dueAmount = Math.max(0, saleSubtotal - paidAmount);

    let customerName = payload.customer_name || 'Walk-in Customer';
    if (payload.customer_id) {
      const cust = state.customers.find(c => c.id === Number(payload.customer_id));
      if (cust) {
        customerName = cust.name;
        if (dueAmount > 0) {
          cust.current_balance = Number((cust.current_balance + dueAmount).toFixed(2));
        }
      }
    }

    if (pMethodLower === 'cash') {
      state.drawer_cash = Number((state.drawer_cash + paidAmount).toFixed(2));
    } else if (pMethodLower === 'split' && Array.isArray(payload.payments)) {
      const cashPay = payload.payments.find(p => (p.payment_method || '').toLowerCase() === 'cash');
      if (cashPay && cashPay.amount > 0) {
        state.drawer_cash = Number((state.drawer_cash + Number(cashPay.amount)).toFixed(2));
      }
    }

    const now = new Date();
    const dateStr = now.toISOString().split('T')[0];
    const saleRecord = {
      id: `demo_sale_${Date.now()}`,
      invoice_number: `INV-DEMO-${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, '0')}-${Date.now().toString().slice(-4)}`,
      branch_id: payload.branch_id || 1,
      customer_id: payload.customer_id || null,
      customer_name: customerName,
      customer: payload.customer_id ? { id: payload.customer_id, name: customerName } : null,
      date: dateStr,
      created_at: now.toISOString(),
      total_amount: saleSubtotal,
      total: saleSubtotal,
      cogs: saleCost,
      paid_amount: paidAmount,
      due_amount: dueAmount,
      payment_method: payload.payment_method || 'Cash',
      status: 'completed',
      items: recordedItems
    };

    state.sales.unshift(saleRecord);
    this.saveState(state);
    return saleRecord;
  }

  recordCustomerPayment(customerId, amount, paymentMethod = 'cash') {
    const state = this.getState();
    const cust = state.customers.find(c => c.id === Number(customerId) || String(c.id) === String(customerId));
    const payAmt = Number(amount) || 0;

    if (cust) {
      const oldBal = Number(cust.current_balance ?? cust.balance ?? cust.outstanding ?? 0);
      const newBal = Math.max(0, Number((oldBal - payAmt).toFixed(2)));
      cust.current_balance = newBal;
      cust.balance = newBal;
      cust.outstanding = newBal;
      cust.received = Number(((cust.received || 0) + payAmt).toFixed(2));
      cust.total_paid = Number(((cust.total_paid || 0) + payAmt).toFixed(2));
      if (newBal === 0) {
        cust.status = 'active';
      }
    }
    const isCash = (paymentMethod || 'cash').toLowerCase() === 'cash';
    if (isCash) {
      state.drawer_cash = Number((state.drawer_cash + payAmt).toFixed(2));
    }

    const now = new Date();
    const rec = {
      id: `demo_cust_pay_${Date.now()}`,
      customer_id: cust ? cust.id : Number(customerId),
      customer_name: cust ? cust.name : 'Customer',
      amount: payAmt,
      payment_method: paymentMethod || 'cash',
      date: now.toISOString().split('T')[0],
      created_at: now.toISOString(),
      notes: `Khata installment payment received (${paymentMethod || 'Cash'})`
    };
    if (!state.customer_payments) state.customer_payments = [];
    state.customer_payments.unshift(rec);

    if (state.recent_activity) {
      state.recent_activity.unshift({
        id: `act_${Date.now()}`,
        time: 'Just now',
        type: 'Customer Payment',
        description: `Payment received of PKR ${payAmt.toLocaleString()} from ${cust ? cust.name : 'Customer'}`,
        status: 'completed',
        amount: `+Rs ${payAmt.toLocaleString()}`
      });
    }

    this.saveState(state);
    return rec;
  }

  reverseCustomerPayment(paymentId, reason = 'Payment reversed by user') {
    const state = this.getState();
    const pay = (state.customer_payments || []).find(p => p.id === paymentId || String(p.id) === String(paymentId));
    if (!pay) return null;
    pay.status = 'voided';
    pay.reversal_reason = reason;

    // Refund/re-add amount back to customer balance
    const cust = (state.customers || []).find(c => c.id === pay.customer_id || String(c.id) === String(pay.customer_id));
    if (cust) {
      const curBal = Number(cust.current_balance ?? cust.balance ?? 0);
      const newBal = Number((curBal + Number(pay.amount)).toFixed(2));
      cust.current_balance = newBal;
      cust.balance = newBal;
      cust.outstanding = newBal;
      cust.received = Math.max(0, Number(((cust.received || 0) - Number(pay.amount)).toFixed(2)));
      cust.status = 'outstanding';
    }

    if ((pay.payment_method || '').toLowerCase() === 'cash') {
      state.drawer_cash = Math.max(0, Number((state.drawer_cash - Number(pay.amount)).toFixed(2)));
    }

    if (state.recent_activity) {
      state.recent_activity.unshift({
        id: `act_${Date.now()}`,
        time: 'Just now',
        type: 'Payment Reversal',
        description: `Customer payment #${pay.id} reversed. Reason: ${reason}`,
        status: 'reversed',
        amount: `-Rs ${Number(pay.amount).toLocaleString()}`
      });
    }

    this.saveState(state);
    return pay;
  }

  getCustomerLedger(customerId) {
    const state = this.getState();
    const cust = (state.customers || []).find(c => c.id === Number(customerId) || String(c.id) === String(customerId));
    if (!cust) return { customer: null, outstanding: 0, credit: 0, ledger: [] };

    const ledger = [];
    const opBal = Number(cust.opening_balance ?? 0);
    if (opBal !== 0) {
      ledger.push({
        id: `opening-${cust.id}`,
        date: cust.created_at ? cust.created_at.split('T')[0] : '2026-01-01',
        created_at: cust.created_at || '2026-01-01T00:00:00Z',
        type: 'Opening Balance',
        reference: 'OPENING',
        debit: opBal > 0 ? opBal : 0,
        credit: opBal < 0 ? Math.abs(opBal) : 0,
        status: 'posted',
        notes: 'Pre-existing customer account opening balance'
      });
    }

    // Credit sales on Khata
    const sales = (state.sales || []).filter(s => (s.customer_id === Number(customerId) || String(s.customer_id) === String(customerId)) && s.status !== 'cancelled');
    for (const s of sales) {
      const isKhata = (s.payment_method || '').toLowerCase() === 'khata' || Number(s.due_amount) > 0;
      if (isKhata) {
        const debtAmt = Number(s.due_amount) > 0 ? Number(s.due_amount) : Number(s.total || s.subtotal || 0);
        ledger.push({
          id: `sale-${s.id}`,
          date: s.date || (s.created_at ? s.created_at.split('T')[0] : '2026-01-01'),
          created_at: s.created_at || s.date || '2026-01-01T00:00:00Z',
          type: 'Credit Sale',
          reference: s.invoice_number || `INV-${s.id}`,
          debit: debtAmt,
          credit: 0,
          status: s.status || 'completed',
          notes: `Credit Sale invoice #${s.invoice_number || s.id}`
        });
      }
    }

    // Customer payments
    const payments = (state.customer_payments || []).filter(p => p.customer_id === Number(customerId) || String(p.customer_id) === String(customerId));
    for (const p of payments) {
      const isVoided = p.status === 'voided' || p.status === 'reversed';
      ledger.push({
        id: p.id,
        payment_id: p.id,
        date: p.date || (p.created_at ? p.created_at.split('T')[0] : '2026-01-01'),
        created_at: p.created_at || p.date || '2026-01-01T00:00:00Z',
        type: isVoided ? 'Payment Voided' : 'Payment Received',
        reference: p.id,
        payment_method: p.payment_method || 'Cash',
        debit: 0,
        credit: isVoided ? 0 : Number(p.amount || 0),
        status: isVoided ? 'voided' : 'posted',
        notes: isVoided ? `VOIDED: ${p.reversal_reason || 'Reversed'}` : (p.notes || `Payment received (${p.payment_method || 'Cash'})`),
        is_payment: true
      });
    }

    // Sort chronologically
    ledger.sort((a, b) => (a.date || '').localeCompare(b.date || ''));

    // Compute running balance
    let running = 0;
    for (const entry of ledger) {
      running += (entry.debit - entry.credit);
      entry.running_balance = Number(running.toFixed(2));
    }

    const curBal = Math.max(0, running);
    const credit = Math.max(0, -running);

    return {
      customer: cust,
      outstanding: curBal,
      credit: credit,
      ledger: ledger
    };
  }

  recordSupplierPayment(supplierId, amount, paymentMethod = 'cash') {
    const state = this.getState();
    const supp = state.suppliers.find(s => s.id === Number(supplierId));
    const payAmt = Number(amount) || 0;

    if (supp) {
      supp.current_payable = Math.max(0, Number((supp.current_payable - payAmt).toFixed(2)));
    }
    if (paymentMethod === 'cash') {
      state.drawer_cash = Number((state.drawer_cash - payAmt).toFixed(2));
    }

    const now = new Date();
    const rec = {
      id: `demo_supp_pay_${Date.now()}`,
      supplier_id: Number(supplierId),
      supplier_name: supp ? supp.name : 'Supplier',
      amount: payAmt,
      payment_method: payload.payment_method || 'Cash',
      date: now.toISOString().split('T')[0],
      created_at: now.toISOString(),
      notes: 'Wholesale restock payment'
    };
    state.supplier_payments.unshift(rec);
    this.saveState(state);
    return rec;
  }

  reverseCustomerPayment(paymentId, reason = 'Payment reversed by user') {
    const state = this.getState();
    const pay = (state.customer_payments || []).find(p => p.id === paymentId || String(p.id) === String(paymentId));
    if (!pay) return null;
    pay.status = 'voided';
    pay.reversal_reason = reason;

    // Refund/re-add amount back to customer balance
    const cust = (state.customers || []).find(c => c.id === pay.customer_id || String(c.id) === String(pay.customer_id));
    if (cust) {
      const curBal = Number(cust.current_balance ?? cust.balance ?? 0);
      const newBal = Number((curBal + Number(pay.amount)).toFixed(2));
      cust.current_balance = newBal;
      cust.balance = newBal;
      cust.outstanding = newBal;
      cust.received = Math.max(0, Number(((cust.received || 0) - Number(pay.amount)).toFixed(2)));
      cust.status = 'outstanding';
    }

    if ((pay.payment_method || '').toLowerCase() === 'cash') {
      state.drawer_cash = Math.max(0, Number((state.drawer_cash - Number(pay.amount)).toFixed(2)));
    }

    if (state.recent_activity) {
      state.recent_activity.unshift({
        id: `act_${Date.now()}`,
        time: 'Just now',
        type: 'Payment Reversal',
        description: `Customer payment #${pay.id} reversed. Reason: ${reason}`,
        status: 'reversed',
        amount: `-Rs ${Number(pay.amount).toLocaleString()}`
      });
    }

    this.saveState(state);
    return pay;
  }

  getCustomerLedger(customerId) {
    const state = this.getState();
    const cust = (state.customers || []).find(c => c.id === Number(customerId) || String(c.id) === String(customerId));
    if (!cust) return { customer: null, outstanding: 0, credit: 0, ledger: [] };

    const ledger = [];
    const opBal = Number(cust.opening_balance ?? 0);
    if (opBal !== 0) {
      ledger.push({
        id: `opening-${cust.id}`,
        date: cust.created_at ? cust.created_at.split('T')[0] : '2026-01-01',
        created_at: cust.created_at || '2026-01-01T00:00:00Z',
        type: 'Opening Balance',
        reference: 'OPENING',
        debit: opBal > 0 ? opBal : 0,
        credit: opBal < 0 ? Math.abs(opBal) : 0,
        status: 'posted',
        notes: 'Pre-existing customer account opening balance'
      });
    }

    // Credit sales on Khata
    const sales = (state.sales || []).filter(s => (s.customer_id === Number(customerId) || String(s.customer_id) === String(customerId)) && s.status !== 'cancelled');
    for (const s of sales) {
      const isKhata = (s.payment_method || '').toLowerCase() === 'khata' || Number(s.due_amount) > 0;
      if (isKhata) {
        const debtAmt = Number(s.due_amount) > 0 ? Number(s.due_amount) : Number(s.total || s.subtotal || 0);
        ledger.push({
          id: `sale-${s.id}`,
          date: s.date || (s.created_at ? s.created_at.split('T')[0] : '2026-01-01'),
          created_at: s.created_at || s.date || '2026-01-01T00:00:00Z',
          type: 'Credit Sale',
          reference: s.invoice_number || `INV-${s.id}`,
          debit: debtAmt,
          credit: 0,
          status: s.status || 'completed',
          notes: `Credit Sale invoice #${s.invoice_number || s.id}`
        });
      }
    }

    // Customer payments
    const payments = (state.customer_payments || []).filter(p => p.customer_id === Number(customerId) || String(p.customer_id) === String(customerId));
    for (const p of payments) {
      const isVoided = p.status === 'voided' || p.status === 'reversed';
      ledger.push({
        id: p.id,
        payment_id: p.id,
        date: p.date || (p.created_at ? p.created_at.split('T')[0] : '2026-01-01'),
        created_at: p.created_at || p.date || '2026-01-01T00:00:00Z',
        type: isVoided ? 'Payment Voided' : 'Payment Received',
        reference: p.id,
        payment_method: p.payment_method || 'Cash',
        debit: 0,
        credit: isVoided ? 0 : Number(p.amount || 0),
        status: isVoided ? 'voided' : 'posted',
        notes: isVoided ? `VOIDED: ${p.reversal_reason || 'Reversed'}` : (p.notes || `Payment received (${p.payment_method || 'Cash'})`),
        is_payment: true
      });
    }

    // Sort chronologically
    ledger.sort((a, b) => (a.date || '').localeCompare(b.date || ''));

    // Compute running balance
    let running = 0;
    for (const entry of ledger) {
      running += (entry.debit - entry.credit);
      entry.running_balance = Number(running.toFixed(2));
    }

    const curBal = Math.max(0, running);
    const credit = Math.max(0, -running);

    return {
      customer: cust,
      outstanding: curBal,
      credit: credit,
      ledger: ledger
    };
  }

  recordExpense(payload) {
    const state = this.getState();
    const amt = Number(payload.amount) || 0;
    if (payload.payment_method === 'cash') {
      state.drawer_cash = Math.max(0, Number((state.drawer_cash - amt).toFixed(2)));
    }

    const now = new Date();
    const exp = {
      id: `demo_exp_${Date.now()}`,
      branch_id: payload.branch_id || 1,
      amount: amt,
      category: payload.category || 'General Expense',
      description: payload.description || '',
      date: now.toISOString().split('T')[0],
      payment_method: payload.payment_method || 'cash',
      created_at: now.toISOString()
    };
    state.expenses.unshift(exp);
    this.saveState(state);
    return exp;
  }

  /**
   * Authoritative Business Owner & Manager Dashboard Service
   * Returns exact nested structure expected by DashboardView.vue and DashboardService.php
   */
  getDashboard(params = {}) {
    const state = this.getState();
    const preset = params.preset || 'this_month';
    const { startDate, endDate, prevStartDate, prevEndDate } = resolvePresetBoundaries(
      preset, 
      params.from, 
      params.to
    );

    const branchFilter = params.branch_id && params.branch_id !== 'all' ? Number(params.branch_id) : null;

    // Filter sales for current period and comparison period
    const filterSales = (start, end) => {
      return state.sales.filter(s => {
        if (branchFilter && s.branch_id !== branchFilter) return false;
        const d = new Date(s.created_at || s.date);
        return d >= start && d <= end;
      });
    };

    const currentSales = filterSales(startDate, endDate);
    const prevSales = filterSales(prevStartDate, prevEndDate);

    // Sales & COGS metrics
    const grossSales = currentSales.reduce((sum, s) => sum + Number(s.total_amount || 0), 0);
    const returnAdjustment = 0;
    const netSales = grossSales - returnAdjustment;
    const transactionCount = currentSales.length;
    const aov = transactionCount > 0 ? Math.round(netSales / transactionCount) : 0;

    const prevGrossSales = prevSales.reduce((sum, s) => sum + Number(s.total_amount || 0), 0);
    const prevNetSales = prevGrossSales;

    const currentCogs = currentSales.reduce((sum, s) => sum + Number(s.cogs || 0), 0);
    const grossProfit = netSales - currentCogs;

    // Expenses metrics
    const filterExpenses = (start, end) => {
      return state.expenses.filter(e => {
        if (branchFilter && e.branch_id !== branchFilter) return false;
        const d = new Date(e.created_at || e.date);
        return d >= start && d <= end;
      });
    };

    const currentExpenses = filterExpenses(startDate, endDate);
    const prevExpenses = filterExpenses(prevStartDate, prevEndDate);

    const totalExpenses = currentExpenses.reduce((sum, e) => sum + Number(e.amount || 0), 0);
    const prevTotalExpenses = prevExpenses.reduce((sum, e) => sum + Number(e.amount || 0), 0);

    const netProfit = grossProfit - totalExpenses;

    // Receivables and Payables
    const totalReceivables = state.customers.reduce((sum, c) => sum + Number(c.current_balance || 0), 0);
    const totalPayables = state.suppliers.reduce((sum, s) => sum + Number(s.current_payable || 0), 0);

    // Inventory metrics
    let totalStockValue = 0;
    let lowStockCount = 0;
    let outOfStockCount = 0;
    state.products.forEach(p => {
      totalStockValue += (Number(p.stock || 0) * Number(p.cost_price || 0));
      if (Number(p.stock) === 0) outOfStockCount++;
      else if (Number(p.stock) <= Number(p.low_stock_threshold || 10)) lowStockCount++;
    });

    // Product performance: Top Sellers & Slow Movers
    const productSoldMap = {};
    currentSales.forEach(s => {
      (s.items || []).forEach(it => {
        if (!productSoldMap[it.product_id]) {
          productSoldMap[it.product_id] = {
            product_id: it.product_id,
            name: it.product_name,
            sku: it.sku,
            quantity_sold: 0,
            net_sales: 0
          };
        }
        productSoldMap[it.product_id].quantity_sold += Number(it.quantity || 0);
        productSoldMap[it.product_id].net_sales += Number(it.subtotal || 0);
      });
    });

    const topSellers = Object.values(productSoldMap)
      .sort((a, b) => b.net_sales - a.net_sales)
      .slice(0, 5);

    const slowMovers = state.products
      .filter(p => !productSoldMap[p.id] || productSoldMap[p.id].quantity_sold === 0)
      .map(p => ({
        product_id: p.id,
        name: p.name,
        sku: p.sku,
        quantity_on_hand: p.stock
      }))
      .slice(0, 5);

    // Branch comparison (for multi-store)
    const branchComparison = (state.branches || []).map(b => {
      const bSales = currentSales.filter(s => s.branch_id === b.id);
      const bNetSales = bSales.reduce((sum, s) => sum + Number(s.total_amount || 0), 0);
      const bCogs = bSales.reduce((sum, s) => sum + Number(s.cogs || 0), 0);
      const bExpenses = currentExpenses.filter(e => e.branch_id === b.id).reduce((sum, e) => sum + Number(e.amount || 0), 0);
      return {
        branch_id: b.id,
        branch_name: b.name,
        net_sales: bNetSales,
        transaction_count: bSales.length,
        gross_profit: bNetSales - bCogs,
        posted_expenses: bExpenses,
        cash_balance: b.id === 1 ? state.drawer_cash : (b.cash_drawer_balance || 10000)
      };
    });

    // Recent activity (latest operations stream)
    const recentActivity = state.sales.slice(0, 50).map(s => ({
      reference: s.invoice_number,
      type: 'Sale',
      date: s.created_at || s.date,
      amount: s.total_amount,
      status: 'Completed',
      notes: `${s.customer_name} (${s.payment_method?.toUpperCase()})`
    }));

    // Needs attention alerts
    const needsAttention = [
      {
        id: 'demo_alert_1',
        title: 'Pending Supplier Invoices',
        description: `PKR ${totalPayables.toLocaleString()} in reconciled wholesale payables due this month.`,
        severity: 'warning'
      },
      {
        id: 'demo_alert_2',
        title: 'Customer Khata Receivables',
        description: `PKR ${totalReceivables.toLocaleString()} outstanding across 4 registered accounts.`,
        severity: 'info'
      }
    ];
    if (lowStockCount > 0) {
      needsAttention.push({
        id: 'demo_alert_3',
        title: 'Low Stock Reorder Recommended',
        description: `${lowStockCount} items at or below low stock threshold.`,
        severity: 'warning'
      });
    }

    return {
      role: 'Business Owner',
      period: {
        preset: preset,
        from: formatLocalDate(startDate),
        to: formatLocalDate(endDate)
      },
      scope: {
        business_id: 1,
        branch_id: (this.mode === 'single' || (state.branches || []).filter(b => b.status === 'active').length <= 1) ? null : branchFilter,
        branch_name: (this.mode === 'single' || (state.branches || []).filter(b => b.status === 'active').length <= 1)
          ? null 
          : (branchFilter 
              ? (state.branches.find(b => b.id === branchFilter)?.name || `Branch #${branchFilter}`)
              : 'All Branches')
      },
      sales: {
        gross_sales: grossSales,
        return_adjustment: returnAdjustment,
        net_sales: netSales,
        transaction_count: transactionCount,
        average_order_value: aov,
        comparison: calcComparison(netSales, prevNetSales)
      },
      cogs: {
        gross_cogs: currentCogs,
        returned_cogs: 0,
        net_cogs: currentCogs,
        cogs_status: 'complete',
        is_partial: false,
        uncosted_sales_count: 0
      },
      gross_profit: {
        amount: grossProfit,
        is_partial: false
      },
      operating_position: {
        amount: netProfit,
        is_partial: false
      },
      net_profit: {
        amount: netProfit,
        is_partial: false
      },
      expenses: {
        total: totalExpenses,
        comparison: calcComparison(totalExpenses, prevTotalExpenses)
      },
      cash_and_bank: {
        total_tracked_funds: state.drawer_cash,
        cash_on_hand: state.drawer_cash
      },
      receivables: {
        outstanding: totalReceivables,
        customer_credit: 0
      },
      payables: {
        total_payable: totalPayables
      },
      inventory: {
        current_stock_value_at_current_cost: totalStockValue,
        low_stock_count: lowStockCount,
        out_of_stock_count: outOfStockCount
      },
      product_performance: {
        top_sellers: topSellers,
        slow_movers: slowMovers
      },
      branch_comparison: branchComparison,
      needs_attention: needsAttention,
      recent_activity: recentActivity,
      recent_sales: recentActivity
    };
  }

  getProfitabilityReport(params = {}) {
    const grossGoodsSales = 3535000;
    const discounts = 45000;
    const economicReturns = 12500;
    const netCommercialSales = grossGoodsSales - discounts - economicReturns; // 3,477,500
    const grossCogs = 2680000;
    const returnedCogs = 9500;
    const netCogs = grossCogs - returnedCogs; // 2,670,500
    const grossProfit = netCommercialSales - netCogs; // 807,000
    const operatingExpenses = 328450;
    const netProfit = grossProfit - operatingExpenses; // 478,550
    const grossMargin = ((grossProfit / netCommercialSales) * 100).toFixed(1); // 23.2%
    const netMargin = ((netProfit / netCommercialSales) * 100).toFixed(2); // 13.76%

    return {
      net_sales: netCommercialSales,
      cogs: netCogs,
      net_cogs: netCogs,
      gross_goods_sales: grossGoodsSales,
      discounts: discounts,
      tax: 0.00,
      commercial_invoice_sales: grossGoodsSales - discounts,
      economic_returns: economicReturns,
      gross_cogs: grossCogs,
      returned_cogs: returnedCogs,
      gross_profit: grossProfit,
      expenses: operatingExpenses,
      operating_expenses: operatingExpenses,
      net_profit: netProfit,
      margin_percentage: grossMargin,
      gross_margin_percent: grossMargin,
      net_margin_percent: netMargin,
      accounting_basis: 'Accrual Basis (Enterprise Small-Retail Standard)'
    };
  }

  getSalesReport(params = {}) {
    const salesList = [
      { id: 101, invoice_number: 'INV-2026-11-001', date: '2026-11-03', branch_name: 'Main Store (Bahadurabad)', customer_name: 'Walk-in Customer', paid_amount: 1450, due_amount: 0, total: 1450, status: 'completed' },
      { id: 102, invoice_number: 'INV-2026-11-002', date: '2026-11-03', branch_name: 'Main Store (Bahadurabad)', customer_name: 'Haji Muhammad Rafiq', paid_amount: 15000, due_amount: 3500, total: 18500, status: 'partial_credit' },
      { id: 103, invoice_number: 'INV-2026-11-003', date: '2026-11-03', branch_name: 'Main Store (Bahadurabad)', customer_name: 'Mrs. Dr. Shazia', paid_amount: 4800, due_amount: 0, total: 4800, status: 'completed' },
      { id: 104, invoice_number: 'INV-2026-11-02', date: '2026-11-02', branch_name: 'Main Store (Bahadurabad)', customer_name: 'Tariq Mehmood Traders', paid_amount: 28000, due_amount: 0, total: 28000, status: 'completed' },
      { id: 105, invoice_number: 'INV-2026-11-01', date: '2026-11-02', branch_name: 'Main Store (Bahadurabad)', customer_name: 'Bilal Ahmed (Karachi Club)', paid_amount: 6200, due_amount: 2000, total: 8200, status: 'partial_credit' },
      { id: 106, invoice_number: 'INV-2026-10-98', date: '2026-10-31', branch_name: 'Main Store (Bahadurabad)', customer_name: 'Usman Ghani (Catering)', paid_amount: 35000, due_amount: 5000, total: 40000, status: 'partial_credit' },
      { id: 107, invoice_number: 'INV-2026-10-97', date: '2026-10-30', branch_name: 'Main Store (Bahadurabad)', customer_name: 'Walk-in Customer', paid_amount: 3200, due_amount: 0, total: 3200, status: 'completed' },
      { id: 108, invoice_number: 'INV-2026-10-96', date: '2026-10-29', branch_name: 'Main Store (Bahadurabad)', customer_name: 'Al-Rehman Bakers', paid_amount: 12000, due_amount: 0, total: 12000, status: 'completed' },
      { id: 109, invoice_number: 'INV-2026-10-95', date: '2026-10-28', branch_name: 'Main Store (Bahadurabad)', customer_name: 'Walk-in Customer', paid_amount: 950, due_amount: 0, total: 950, status: 'completed' },
      { id: 110, invoice_number: 'INV-2026-10-94', date: '2026-10-27', branch_name: 'Main Store (Bahadurabad)', customer_name: 'Haji Muhammad Rafiq', paid_amount: 22000, due_amount: 0, total: 22000, status: 'completed' }
    ];

    const grossSales = 3535000;
    const returnAdj = 12500;
    const netSales = 3477500;
    const grossProfit = 807000;

    return {
      summary: {
        gross_sales: grossSales,
        return_adjustment: returnAdj,
        net_sales: netSales,
        gross_profit: grossProfit,
        cogs_status: 'complete'
      },
      total_sales: grossSales,
      transaction_count: 1469,
      sales: salesList
    };
  }

  getInventoryReport(params = {}) {
    const items = [
      { id: 1, product_name: 'Tapal Danedar Tea 450g Pouch', sku: 'TEA-TAP-450', category: 'Hot Beverages', category_name: 'Hot Beverages', branch_name: 'Main Store', quantity_on_hand: 45, minimum_stock: 15, current_cost_price: 640, unit_cost: 640, stock_value: 28800, stock_status: 'In Stock' },
      { id: 2, product_name: 'Olpers Full Cream Milk 1L Pack', sku: 'MLK-OLP-001', category: 'Dairy & Eggs', category_name: 'Dairy & Eggs', branch_name: 'Main Store', quantity_on_hand: 8, minimum_stock: 20, current_cost_price: 260, unit_cost: 260, stock_value: 2080, stock_status: 'Low Stock' },
      { id: 3, product_name: 'Dalda Cooking Oil 5L Pouch', sku: 'OIL-DAL-005', category: 'Edible Oils & Ghee', category_name: 'Edible Oils & Ghee', branch_name: 'Main Store', quantity_on_hand: 32, minimum_stock: 10, current_cost_price: 2450, unit_cost: 2450, stock_value: 78400, stock_status: 'In Stock' },
      { id: 4, product_name: 'Shan Special Biryani Masala 50g', sku: 'SP-SHN-050', category: 'Spices & Recipe Mixes', category_name: 'Spices & Recipe Mixes', branch_name: 'Main Store', quantity_on_hand: 120, minimum_stock: 30, current_cost_price: 110, unit_cost: 110, stock_value: 13200, stock_status: 'In Stock' },
      { id: 5, product_name: 'Lux Beauty Bar Soap White 140g', sku: 'PER-LUX-140', category: 'Personal Care & Hygiene', category_name: 'Personal Care & Hygiene', branch_name: 'Main Store', quantity_on_hand: 0, minimum_stock: 15, current_cost_price: 140, unit_cost: 140, stock_value: 0, stock_status: 'Out of Stock' },
      { id: 6, product_name: 'Sufi Special Banaspati Ghee 1kg', sku: 'GHE-SUF-001', category: 'Edible Oils & Ghee', category_name: 'Edible Oils & Ghee', branch_name: 'Main Store', quantity_on_hand: 4, minimum_stock: 12, current_cost_price: 520, unit_cost: 520, stock_value: 2080, stock_status: 'Low Stock' },
      { id: 7, product_name: 'Guard Supreme Super Basmati Rice 5kg', sku: 'RCE-GRD-005', category: 'Staples & Grains', category_name: 'Staples & Grains', branch_name: 'Main Store', quantity_on_hand: 25, minimum_stock: 10, current_cost_price: 1650, unit_cost: 1650, stock_value: 41250, stock_status: 'In Stock' },
      { id: 8, product_name: 'National Tomato Ketchup 800g Bottle', sku: 'COND-NAT-800', category: 'Sauces & Condiments', category_name: 'Sauces & Condiments', branch_name: 'Main Store', quantity_on_hand: 18, minimum_stock: 8, current_cost_price: 380, unit_cost: 380, stock_value: 6840, stock_status: 'In Stock' },
      { id: 9, product_name: 'Ariel Complete Detergent Powder 1kg', sku: 'DET-ARL-001', category: 'Household & Cleaning', category_name: 'Household & Cleaning', branch_name: 'Main Store', quantity_on_hand: 35, minimum_stock: 12, current_cost_price: 610, unit_cost: 610, stock_value: 21350, stock_status: 'In Stock' },
      { id: 10, product_name: 'Mitchells Mixed Fruit Jam 450g', sku: 'JAM-MIT-450', category: 'Breakfast & Spreads', category_name: 'Breakfast & Spreads', branch_name: 'Main Store', quantity_on_hand: 0, minimum_stock: 10, current_cost_price: 290, unit_cost: 290, stock_value: 0, stock_status: 'Out of Stock' }
    ];

    const totalUnits = 3742;
    const totalVal = 1652775;
    const lowStock = 2;
    const outOfStock = 2;

    return {
      summary: {
        units_on_hand: totalUnits,
        current_stock_value_at_current_cost: totalVal,
        low_stock_count: lowStock,
        out_of_stock_count: outOfStock
      },
      inventories: items,
      items: items
    };
  }

  getCustomersReport(params = {}) {
    const customerList = [
      { id: 1, name: 'Haji Muhammad Rafiq', phone: '0300-9281726', sales_volume: 345000, total_purchased: 345000, transaction_count: 18, sales_count: 18, payments_collected: 295000, total_paid: 295000, outstanding_balance: 50000, outstanding: 50000, advance_credit: 0, credit: 0 },
      { id: 2, name: 'Tariq Mehmood Traders', phone: '0321-4829104', sales_volume: 280000, total_purchased: 280000, transaction_count: 12, sales_count: 12, payments_collected: 240000, total_paid: 240000, outstanding_balance: 40000, outstanding: 40000, advance_credit: 0, credit: 0 },
      { id: 3, name: 'Bilal Ahmed (Karachi Club)', phone: '0333-1928374', sales_volume: 95000, total_purchased: 95000, transaction_count: 8, sales_count: 8, payments_collected: 78000, total_paid: 78000, outstanding_balance: 17000, outstanding: 17000, advance_credit: 0, credit: 0 },
      { id: 4, name: 'Usman Ghani (Catering Services)', phone: '0312-8877665', sales_volume: 68000, total_purchased: 68000, transaction_count: 5, sales_count: 5, payments_collected: 46600, total_paid: 46600, outstanding_balance: 21400, outstanding: 21400, advance_credit: 0, credit: 0 },
      { id: 5, name: 'Mrs. Dr. Shazia (Bahadurabad)', phone: '0301-5544332', sales_volume: 42500, total_purchased: 42500, transaction_count: 14, sales_count: 14, payments_collected: 42500, total_paid: 42500, outstanding_balance: 0, outstanding: 0, advance_credit: 0, credit: 0 },
      { id: 6, name: 'Al-Rehman Bakers (Wholesale)', phone: '0345-1122334', sales_volume: 12000, total_purchased: 12000, transaction_count: 2, sales_count: 2, payments_collected: 27000, total_paid: 27000, outstanding_balance: 0, outstanding: 0, advance_credit: 15000, credit: 15000 }
    ];

    const totalSalesVol = 842500;
    const totalOutstanding = 128400;
    const totalCredit = 15000;
    const totalPayments = 729100;

    return {
      summary: {
        total_customers: customerList.length,
        total_sales_volume: totalSalesVol,
        outstanding: totalOutstanding,
        total_outstanding: totalOutstanding,
        customer_credit: totalCredit,
        total_payments: totalPayments
      },
      customers: customerList
    };
  }

  getSuppliersReport(params = {}) {
    const supplierList = [
      { id: 1, name: 'Unilever Pakistan Consumer Goods', contact_person: 'Kamran Siddiqui', phone: '0321-9876543', purchase_volume: 920000, total_purchased: 920000, purchase_count: 14, purchases_count: 14, payments_made: 810000, total_paid: 810000, reconciled_payable: 110000, payable: 110000 },
      { id: 2, name: 'Tapal Tea Distribution Network', contact_person: 'Muhammad Aslam', phone: '0300-8273645', purchase_volume: 680000, total_purchased: 680000, purchase_count: 11, purchases_count: 11, payments_made: 600000, total_paid: 600000, reconciled_payable: 80000, payable: 80000 },
      { id: 3, name: 'Shan Foods Private Limited', contact_person: 'Farhan Sheikh', phone: '0333-2211445', purchase_volume: 410000, total_purchased: 410000, purchase_count: 8, purchases_count: 8, payments_made: 340000, total_paid: 340000, reconciled_payable: 70000, payable: 70000 },
      { id: 4, name: 'Dalda Consumer Brands Karachi', contact_person: 'Imran Qureshi', phone: '0314-5566778', purchase_volume: 320000, total_purchased: 320000, purchase_count: 6, purchases_count: 6, payments_made: 260000, total_paid: 260000, reconciled_payable: 60000, payable: 60000 },
      { id: 5, name: 'Prime Dairy & Beverages Ltd', contact_person: 'Naveed Butt', phone: '0345-9988776', purchase_volume: 120000, total_purchased: 120000, purchase_count: 4, purchases_count: 4, payments_made: 120000, total_paid: 120000, reconciled_payable: 0, payable: 0 }
    ];

    const totalPurchases = 2450000;
    const totalPayments = 2130000;
    const totalPayable = 320000;

    return {
      summary: {
        total_suppliers: supplierList.length,
        total_purchases: totalPurchases,
        total_payments: totalPayments,
        total_payable: totalPayable
      },
      suppliers: supplierList
    };
  }

  getExpensesReport(params = {}) {
    const mappedExpenses = [
      { id: 531, date: '2026-11-03', category: 'Shop Rent', description: 'Monthly Commercial Shop Lease (November)', branch_name: 'Main Store', account_name: 'Meezan Bank Corporate', amount: 85000, status: 'posted' },
      { id: 532, date: '2026-11-03', category: 'Staff Salaries', description: 'Cashiers & Floor Staff Monthly Compensation', branch_name: 'Main Store', account_name: 'Meezan Bank Corporate', amount: 140000, status: 'posted' },
      { id: 533, date: '2026-11-03', category: 'Electricity / K-Electric', description: 'Commercial Meter Energy Bill (October consumption)', branch_name: 'Main Store', account_name: 'Main Cash Register', amount: 42500, status: 'posted' },
      { id: 534, date: '2026-11-03', category: 'Shop Maintenance', description: 'LED Tube Replacements & AC Servicing', branch_name: 'Main Store', account_name: 'Main Cash Register', amount: 6500, status: 'posted' },
      { id: 535, date: '2026-11-03', category: 'Packaging & Bags', description: 'Printed Biodegradable Shopping Bags (5,000 pcs)', branch_name: 'Main Store', account_name: 'Main Cash Register', amount: 14000, status: 'posted' },
      { id: 536, date: '2026-11-03', category: 'Generator Fuel', description: 'Backup Generator Diesel Refill (50 Liters)', branch_name: 'Main Store', account_name: 'Main Cash Register', amount: 14500, status: 'posted' },
      { id: 537, date: '2026-11-03', category: 'Internet / PTCL', description: 'Fibre Broadband & POS Network Line', branch_name: 'Main Store', account_name: 'EasyPaisa Merchant', amount: 4500, status: 'posted' },
      { id: 538, date: '2026-11-03', category: 'Tea & Refreshment', description: 'Staff & Visitor Daily Tea / Refreshment Expense', branch_name: 'Main Store', account_name: 'Main Cash Register', amount: 3850, status: 'posted' },
      { id: 539, date: '2026-11-02', category: 'Shop Maintenance', description: 'Door Lock Repair & Fire Extinguisher Inspection', branch_name: 'Main Store', account_name: 'Main Cash Register', amount: 5600, status: 'posted' },
      { id: 540, date: '2026-10-31', category: 'Municipal Trade License', description: 'Town Municipal Committee Annual Renewal Fee', branch_name: 'Main Store', account_name: 'Meezan Bank Corporate', amount: 12000, status: 'posted' }
    ];

    const total = 328450;

    return {
      total_expenses: total,
      summary: {
        total_expenses: total,
        transaction_count: mappedExpenses.length,
        active_accounts: 3
      },
      expenses: mappedExpenses
    };
  }

  getFinancialAccountsReport(params = {}) {
    const accounts = [
      {
        id: 1,
        name: 'Main Cash Register (Counter #1)',
        type: 'Cash on Hand',
        branch_name: 'Main Store (Bahadurabad)',
        current_balance: 145000,
        period_inflows: 285000,
        period_outflows: 140000,
        status: 'active'
      },
      {
        id: 2,
        name: 'Meezan Bank (Islamic Corporate Current)',
        type: 'Commercial Bank',
        branch_name: 'Main Store (Bahadurabad)',
        current_balance: 850000,
        period_inflows: 1250000,
        period_outflows: 400000,
        status: 'active'
      },
      {
        id: 3,
        name: 'EasyPaisa Merchant Digital Wallet',
        type: 'Digital Merchant',
        branch_name: 'Main Store (Bahadurabad)',
        current_balance: 42500,
        period_inflows: 85000,
        period_outflows: 42500,
        status: 'active'
      },
      {
        id: 4,
        name: 'JazzCash Business Gateway',
        type: 'Digital Merchant',
        branch_name: 'Main Store (Bahadurabad)',
        current_balance: 38000,
        period_inflows: 64000,
        period_outflows: 26000,
        status: 'active'
      }
    ];

    const totalBal = 1075500;
    const totalIn = 1684000;
    const totalOut = 608500;

    return {
      summary: {
        total_accounts: accounts.length,
        total_balance: totalBal,
        total_inflows: totalIn,
        total_outflows: totalOut
      },
      accounts: accounts
    };
  }

  getBranchReport(params = {}) {
    const branches = [
      {
        branch_id: 1,
        branch_name: 'Main Store (Bahadurabad)',
        transaction_count: 1469,
        net_sales: 3117830,
        gross_profit: 722500,
        posted_expenses: 327600,
        cash_balance: 145000
      },
      {
        branch_id: 2,
        branch_name: 'Clifton Superstore (Block 5)',
        transaction_count: 1920,
        net_sales: 4680000,
        gross_profit: 842400,
        posted_expenses: 410000,
        cash_balance: 220000
      },
      {
        branch_id: 3,
        branch_name: 'Gulshan Outlet (Block 13-D)',
        transaction_count: 1180,
        net_sales: 2450000,
        gross_profit: 392000,
        posted_expenses: 245000,
        cash_balance: 95000
      }
    ];

    return {
      branches: branches
    };
  }

  getBranches() {
    return [
      { id: 1, name: 'Main Store (Bahadurabad)', code: 'MAIN-01', is_primary: true, status: 'active' },
      { id: 2, name: 'Clifton Superstore (Block 5)', code: 'CLIF-02', is_primary: false, status: 'active' },
      { id: 3, name: 'Gulshan Outlet (Block 13-D)', code: 'GUL-03', is_primary: false, status: 'active' }
    ];
  }

  previewDecommission(branchId) {
    const state = this.getState();
    const branch = (state.branches || []).find(b => b.id === Number(branchId));
    if (!branch) return null;

    const primaryBranch = (state.branches || []).find(b => b.is_primary) || state.branches[0];
    
    // Find stock items associated with this branch (if any)
    const stockItems = (state.products || []).filter(p => p.branch_id === Number(branchId) && (p.stock || p.quantity_on_hand) > 0);
    const totalStock = stockItems.reduce((acc, p) => acc + Number(p.stock || p.quantity_on_hand || 0), 0);

    const hasOpenDrawer = state.active_session && state.active_session.branch_id === Number(branchId);
    const drawerCash = branch.is_primary ? (state.drawer_cash || 0) : 0;
    const salesCount = (state.sales || []).filter(s => s.branch_id === Number(branchId)).length;
    const pendingTransfers = (state.transfers || []).filter(t => (t.from_branch_id === Number(branchId) || t.to_branch_id === Number(branchId)) && t.status !== 'received' && t.status !== 'cancelled').length;

    return {
      branch_id: Number(branchId),
      branch_name: branch.name,
      primary_branch_id: primaryBranch ? primaryBranch.id : 1,
      primary_branch_name: primaryBranch ? primaryBranch.name : 'Main Store',
      unique_stock_items: stockItems.length,
      total_stock_quantity: totalStock,
      has_open_cash_session: !!hasOpenDrawer,
      drawer_cash_balance: drawerCash,
      staff_assigned_count: 1,
      pending_transfers_count: pendingTransfers,
      has_sales_history: salesCount > 0,
      can_hard_purge: salesCount === 0 && pendingTransfers === 0
    };
  }

  consolidateAndDecommissionBranch(branchId) {
    const state = this.getState();
    const bId = Number(branchId);
    const branch = (state.branches || []).find(b => b.id === bId);
    if (!branch) throw new Error("Branch not found");
    if (branch.is_primary) throw new Error("Primary branch cannot be decommissioned.");

    const primaryBranch = (state.branches || []).find(b => b.is_primary) || state.branches[0];

    // 1. Stock sweep: reassign any products with branch_id = bId to primaryBranch
    let itemsSwept = 0;
    let qtySwept = 0;
    (state.products || []).forEach(p => {
      if (p.branch_id === bId) {
        itemsSwept++;
        qtySwept += Number(p.stock || p.quantity_on_hand || 0);
        p.branch_id = primaryBranch.id;
      }
    });

    // 2. Financial sweep
    if (state.active_session && state.active_session.branch_id === bId) {
      state.active_session.status = 'closed';
      state.active_session.closed_at = new Date().toISOString();
      state.active_session.notes = 'Auto-closed on branch decommissioning';
    }

    // 3. Mark branch inactive
    branch.status = 'inactive';

    // 4. Log recent activity
    if (state.recent_activity) {
      state.recent_activity.unshift({
        id: `act_${Date.now()}`,
        time: 'Just now',
        type: 'Branch Decommissioned',
        description: `Branch '${branch.name}' consolidated into '${primaryBranch.name}'. ${itemsSwept} stock items swept.`,
        status: 'completed',
        amount: ''
      });
    }

    this.saveState(state);
    return {
      branch_id: branch.id,
      status: 'inactive',
      items_swept: itemsSwept,
      quantity_swept: qtySwept,
      cash_swept: 0
    };
  }

  purgeTestBranch(branchId) {
    const state = this.getState();
    const bId = Number(branchId);
    const idx = (state.branches || []).findIndex(b => b.id === bId);
    if (idx === -1) throw new Error("Branch not found");
    if (state.branches[idx].is_primary) throw new Error("Primary branch cannot be purged.");

    const name = state.branches[idx].name;
    state.branches.splice(idx, 1);
    this.saveState(state);
    return { message: `Test branch '${name}' purged cleanly from the system.` };
  }

  getStockTransfers() {
    const state = this.getState();
    return state.transfers;
  }

  getNotifications() {
    const state = this.getState();
    if (!state.notifications || state.notifications.length === 0) {
      state.notifications = this.generateInitialNotifications({});
      this.saveState(state);
    }
    return state.notifications;
  }

  markNotificationRead(id) {
    const state = this.getState();
    if (!state.notifications) return false;
    const target = state.notifications.find(n => String(n.id) === String(id));
    if (target) {
      target.read_at = new Date().toISOString();
      this.saveState(state);
      return true;
    }
    return false;
  }

  markAllNotificationsRead() {
    const state = this.getState();
    if (!state.notifications) return false;
    const nowIso = new Date().toISOString();
    state.notifications.forEach(n => {
      if (!n.read_at) n.read_at = nowIso;
    });
    this.saveState(state);
    return true;
  }

  dismissNotification(id) {
    const state = this.getState();
    if (!state.notifications) return false;
    state.notifications = state.notifications.filter(n => String(n.id) !== String(id));
    this.saveState(state);
    return true;
  }

  // --- CATEGORIES CRUD ---
  getCategories() {
    const state = this.getState();
    return Array.isArray(state.categories) ? state.categories : DEFAULT_DEMO_CATEGORIES;
  }

  addCategory(payload) {
    const state = this.getState();
    if (!Array.isArray(state.categories)) state.categories = JSON.parse(JSON.stringify(DEFAULT_DEMO_CATEGORIES));
    const newCat = {
      id: Date.now(),
      name: payload.name || 'New Category',
      description: payload.description || '',
      image: payload.image || '',
      is_active: 1,
      status: 1
    };
    state.categories.unshift(newCat);
    this.saveState(state);
    return newCat;
  }

  updateCategory(id, payload) {
    const state = this.getState();
    if (!Array.isArray(state.categories)) state.categories = JSON.parse(JSON.stringify(DEFAULT_DEMO_CATEGORIES));
    const idx = state.categories.findIndex(c => String(c.id) === String(id));
    if (idx !== -1) {
      state.categories[idx] = { ...state.categories[idx], ...payload };
      this.saveState(state);
      return state.categories[idx];
    }
    return null;
  }

  deleteCategory(id) {
    const state = this.getState();
    if (!Array.isArray(state.categories)) state.categories = JSON.parse(JSON.stringify(DEFAULT_DEMO_CATEGORIES));
    state.categories = state.categories.filter(c => String(c.id) !== String(id));
    this.saveState(state);
    return true;
  }

  // --- SUBCATEGORIES CRUD ---
  getSubcategories() {
    const state = this.getState();
    return Array.isArray(state.subcategories) ? state.subcategories : DEFAULT_DEMO_SUBCATEGORIES;
  }

  addSubcategory(payload) {
    const state = this.getState();
    if (!Array.isArray(state.subcategories)) state.subcategories = JSON.parse(JSON.stringify(DEFAULT_DEMO_SUBCATEGORIES));
    const newSub = {
      id: Date.now(),
      category_id: payload.category_id || (payload.category?.id || 1),
      name: payload.name || 'New Subcategory',
      description: payload.description || '',
      is_active: 1,
      status: 1
    };
    state.subcategories.unshift(newSub);
    this.saveState(state);
    return newSub;
  }

  updateSubcategory(id, payload) {
    const state = this.getState();
    if (!Array.isArray(state.subcategories)) state.subcategories = JSON.parse(JSON.stringify(DEFAULT_DEMO_SUBCATEGORIES));
    const idx = state.subcategories.findIndex(s => String(s.id) === String(id));
    if (idx !== -1) {
      state.subcategories[idx] = { ...state.subcategories[idx], ...payload };
      this.saveState(state);
      return state.subcategories[idx];
    }
    return null;
  }

  deleteSubcategory(id) {
    const state = this.getState();
    if (!Array.isArray(state.subcategories)) state.subcategories = JSON.parse(JSON.stringify(DEFAULT_DEMO_SUBCATEGORIES));
    state.subcategories = state.subcategories.filter(s => String(s.id) !== String(id));
    this.saveState(state);
    return true;
  }

  // --- BRANDS CRUD ---
  getBrands() {
    const state = this.getState();
    return Array.isArray(state.brands) ? state.brands : DEFAULT_DEMO_BRANDS;
  }

  addBrand(payload) {
    const state = this.getState();
    if (!Array.isArray(state.brands)) state.brands = JSON.parse(JSON.stringify(DEFAULT_DEMO_BRANDS));
    let name = payload.name || 'New Brand';
    let description = payload.description || '';
    let image = payload.image || '';

    // Handle FormData
    if (payload instanceof FormData) {
      name = payload.get('name') || name;
      description = payload.get('description') || description;
    }

    const newBrand = {
      id: Date.now(),
      name,
      description,
      image: image || 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=100&h=100&fit=crop&q=80',
      status: 'active'
    };
    state.brands.unshift(newBrand);
    this.saveState(state);
    return newBrand;
  }

  updateBrand(id, payload) {
    const state = this.getState();
    if (!Array.isArray(state.brands)) state.brands = JSON.parse(JSON.stringify(DEFAULT_DEMO_BRANDS));
    const idx = state.brands.findIndex(b => String(b.id) === String(id));
    if (idx !== -1) {
      let patch = { ...payload };
      if (payload instanceof FormData) {
        patch = {
          name: payload.get('name') || state.brands[idx].name,
          description: payload.get('description') || state.brands[idx].description
        };
      }
      state.brands[idx] = { ...state.brands[idx], ...patch };
      this.saveState(state);
      return state.brands[idx];
    }
    return null;
  }

  deleteBrand(id) {
    const state = this.getState();
    if (!Array.isArray(state.brands)) state.brands = JSON.parse(JSON.stringify(DEFAULT_DEMO_BRANDS));
    state.brands = state.brands.filter(b => String(b.id) !== String(id));
    this.saveState(state);
    return true;
  }

  // --- PRODUCTS CRUD ---
  addProduct(payload) {
    const state = this.getState();
    if (!Array.isArray(state.products)) state.products = JSON.parse(JSON.stringify(DEFAULT_DEMO_PRODUCTS));

    let data = payload;
    if (payload instanceof FormData) {
      data = {};
      for (const [k, v] of payload.entries()) {
        data[k] = v;
      }
    }

    const nextId = state.products.reduce((max, p) => Math.max(max, Number(p.id) || 0), 0) + 1;
    const catId = Number(data.category_id || data.categoryId) || 1;
    const subcatId = Number(data.subcategory_id || data.subcategoryId) || 1;
    const brandId = Number(data.brand_id || data.brandId) || 1;

    const catObj = (state.categories || []).find(c => c.id === catId);
    const subObj = (state.subcategories || []).find(s => s.id === subcatId);
    const brandObj = (state.brands || []).find(b => b.id === brandId);

    const costPrice = Number(data.cost_price || data.cost || data.purchasePrice) || 0;
    const sellingPrice = Number(data.selling_price || data.price || data.salePrice) || 0;
    const stockQty = Number(data.stock || data.quantity) || 0;
    const minStock = Number(data.min_stock || data.minStock) || 10;

    let altBarcodes = [];
    if (data.alternate_barcodes || data.alternateBarcodes) {
      const raw = data.alternate_barcodes || data.alternateBarcodes;
      altBarcodes = typeof raw === 'string' ? JSON.parse(raw) : raw;
    }

    const newProd = {
      id: nextId,
      name: data.name || `New Product ${nextId}`,
      sku: data.sku || `SKU-${nextId}`,
      barcode: data.barcode || `89640099${String(nextId).padStart(4, '0')}`,
      brand_id: brandId,
      brand: brandObj ? brandObj.name : (data.brand || 'DailyHarvest Foods'),
      category_id: catId,
      category: catObj ? catObj.name : (data.category || 'Grains & Staples'),
      subcategory_id: subcatId,
      subcategory: subObj ? subObj.name : (data.subcategory || 'Basmati Rice'),
      unit: data.unit || 'pcs',
      allow_fractional: Boolean(data.allow_fractional || data.allowFractional),
      cost_price: costPrice,
      selling_price: sellingPrice,
      wholesale_price: Number(data.wholesale_price || data.wholesalePrice) || Math.round((costPrice + sellingPrice) / 2),
      stock: stockQty,
      min_stock: minStock,
      image: data.image || '/demo/products/prod_01.svg',
      description: data.description || '',
      is_active: data.is_active !== undefined ? (data.is_active ? 1 : 0) : (data.active !== false ? 1 : 0),
      alternate_barcodes: altBarcodes
    };

    state.products.unshift(newProd);

    // If multi-store, also create branch_inventories records
    if (this.mode === 'multi' && Array.isArray(state.branches)) {
      if (!Array.isArray(state.branch_inventories)) state.branch_inventories = [];
      state.branches.forEach((br, bIdx) => {
        const factor = bIdx === 0 ? 0.6 : (bIdx === 1 ? 0.25 : 0.15);
        const brStock = Math.round(stockQty * factor * 1000) / 1000;
        state.branch_inventories.push({
          id: `bi_${newProd.id}_${br.id}`,
          product_id: newProd.id,
          branch_id: br.id,
          branch_name: br.name,
          quantity_on_hand: brStock,
          minimum_stock: Math.round(minStock * factor),
          reorder_level: Math.round(minStock * factor * 1.5),
          valuation: Math.round(brStock * costPrice * 100) / 100
        });
      });
    }

    this.saveState(state);
    return newProd;
  }

  updateProduct(id, payload) {
    const state = this.getState();
    if (!Array.isArray(state.products)) state.products = JSON.parse(JSON.stringify(DEFAULT_DEMO_PRODUCTS));
    const idx = state.products.findIndex(p => String(p.id) === String(id));
    if (idx !== -1) {
      let data = payload;
      if (payload instanceof FormData) {
        data = {};
        for (const [k, v] of payload.entries()) {
          data[k] = v;
        }
      }

      const existing = state.products[idx];
      const updated = { ...existing };

      if (data.name !== undefined) updated.name = data.name;
      if (data.sku !== undefined) updated.sku = data.sku;
      if (data.barcode !== undefined) updated.barcode = data.barcode;
      if (data.cost_price !== undefined || data.cost !== undefined) {
        updated.cost_price = Number(data.cost_price || data.cost);
      }
      if (data.selling_price !== undefined || data.price !== undefined) {
        updated.selling_price = Number(data.selling_price || data.price);
      }
      if (data.wholesale_price !== undefined || data.wholesalePrice !== undefined) {
        updated.wholesale_price = Number(data.wholesale_price || data.wholesalePrice);
      }
      if (data.stock !== undefined) updated.stock = Number(data.stock);
      if (data.min_stock !== undefined || data.minStock !== undefined) {
        updated.min_stock = Number(data.min_stock || data.minStock);
      }
      if (data.unit !== undefined) updated.unit = data.unit;
      if (data.description !== undefined) updated.description = data.description;
      if (data.image !== undefined) updated.image = data.image;
      if (data.is_active !== undefined) updated.is_active = data.is_active ? 1 : 0;
      else if (data.active !== undefined) updated.is_active = data.active ? 1 : 0;
      if (data.category_id !== undefined) updated.category_id = Number(data.category_id);
      if (data.subcategory_id !== undefined) updated.subcategory_id = Number(data.subcategory_id);
      if (data.brand_id !== undefined) updated.brand_id = Number(data.brand_id);

      state.products[idx] = updated;

      // Update multi-store branch inventory valuations
      if (this.mode === 'multi' && Array.isArray(state.branch_inventories)) {
        state.branch_inventories.forEach(bi => {
          if (bi.product_id === Number(id)) {
            bi.valuation = Math.round(Number(bi.quantity_on_hand || 0) * updated.cost_price * 100) / 100;
          }
        });
      }

      this.saveState(state);
      return updated;
    }
    return null;
  }

  deleteProduct(id) {
    const state = this.getState();
    if (!Array.isArray(state.products)) state.products = JSON.parse(JSON.stringify(DEFAULT_DEMO_PRODUCTS));
    state.products = state.products.filter(p => String(p.id) !== String(id));
    if (Array.isArray(state.branch_inventories)) {
      state.branch_inventories = state.branch_inventories.filter(bi => String(bi.product_id) !== String(id));
    }
    this.saveState(state);
    return true;
  }

  // --- BRANCH INVENTORY & STOCK OPERATIONS ---
  getBranchInventories(branchId = null) {
    const state = this.getState();
    let list = Array.isArray(state.branch_inventories) ? state.branch_inventories : [];
    if (list.length === 0 && this.mode === 'multi') {
      list = generateBranchInventories(state.products);
      state.branch_inventories = list;
      this.saveState(state);
    }
    if (branchId) {
      return list.filter(bi => Number(bi.branch_id) === Number(branchId));
    }
    return list;
  }

  adjustStock(productId, branchId, quantity, reason = 'Inventory Count Adjustment') {
    const state = this.getState();
    const prod = (state.products || []).find(p => p.id === Number(productId));
    if (!prod) return { success: false, message: 'Product not found' };

    const oldStock = Number(prod.stock || 0);
    const newStock = Math.max(0, Number(quantity) || 0);
    prod.stock = newStock;

    if (this.mode === 'multi' && branchId && Array.isArray(state.branch_inventories)) {
      const bi = state.branch_inventories.find(b => b.product_id === Number(productId) && b.branch_id === Number(branchId));
      if (bi) {
        bi.quantity_on_hand = newStock;
        bi.valuation = Math.round(newStock * prod.cost_price * 100) / 100;
      }
    }

    this.saveState(state);
    return { success: true, product: prod, old_stock: oldStock, new_stock: newStock, reason };
  }

  recordStockTransfer(payload) {
    const state = this.getState();
    if (!Array.isArray(state.transfers)) state.transfers = [];

    const sourceBranchId = Number(payload.source_branch_id || payload.from_branch_id) || 1;
    const destBranchId = Number(payload.destination_branch_id || payload.to_branch_id) || 2;
    const items = payload.items || [];

    const transferId = payload.transfer_id || `TRF-${new Date().getFullYear()}-${String(state.transfers.length + 1).padStart(3, '0')}`;
    const transfer = {
      transfer_id: transferId,
      source_branch_id: sourceBranchId,
      destination_branch_id: destBranchId,
      status: 'in_transit',
      items: items.map(it => {
        const prod = (state.products || []).find(p => p.id === Number(it.product_id));
        return {
          product_id: it.product_id,
          sku: prod?.sku || it.sku,
          product_name: prod?.name || it.product_name,
          quantity: Number(it.quantity) || 1
        };
      }),
      dispatched_at: new Date().toISOString()
    };

    // Deduct stock from source branch in branch_inventories
    if (this.mode === 'multi' && Array.isArray(state.branch_inventories)) {
      transfer.items.forEach(it => {
        const bi = state.branch_inventories.find(b => b.product_id === Number(it.product_id) && b.branch_id === sourceBranchId);
        if (bi) {
          bi.quantity_on_hand = Math.max(0, Number((bi.quantity_on_hand - it.quantity).toFixed(3)));
          const prod = (state.products || []).find(p => p.id === Number(it.product_id));
          bi.valuation = Math.round(bi.quantity_on_hand * (prod?.cost_price || 0) * 100) / 100;
        }
      });
    }

    state.transfers.unshift(transfer);
    this.saveState(state);
    return transfer;
  }

  receiveStockTransfer(transferId) {
    const state = this.getState();
    if (!Array.isArray(state.transfers)) return { success: false, message: 'Transfer not found' };
    const transfer = state.transfers.find(t => t.transfer_id === transferId);
    if (!transfer) return { success: false, message: 'Transfer not found' };
    if (transfer.status === 'received') return { success: false, message: 'Already received' };

    transfer.status = 'received';
    transfer.received_at = new Date().toISOString();

    // Add stock to destination branch in branch_inventories
    if (this.mode === 'multi' && Array.isArray(state.branch_inventories)) {
      transfer.items.forEach(it => {
        const bi = state.branch_inventories.find(b => b.product_id === Number(it.product_id) && b.branch_id === transfer.destination_branch_id);
        if (bi) {
          bi.quantity_on_hand = Number((bi.quantity_on_hand + it.quantity).toFixed(3));
          const prod = (state.products || []).find(p => p.id === Number(it.product_id));
          bi.valuation = Math.round(bi.quantity_on_hand * (prod?.cost_price || 0) * 100) / 100;
        }
      });
    }

    this.saveState(state);
    return { success: true, transfer };
  }
}

export const demoSandboxEngine = new DemoSandboxEngine();
export { autoRollDemoData, generate7MonthDemoDataset };
export default demoSandboxEngine;
