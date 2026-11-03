import axios from 'axios';
import demoSandboxEngine from './services/demoSandboxEngine';

export const APP_VERSION = '1.0.0';
export const SYNC_SCHEMA_VERSION = 1;

// Default axios adapter fallback
const defaultAdapter = axios.defaults.adapter;

/**
 * Demo Sandbox Custom Adapter
 * Completely intercepts requests when demo mode is active.
 * Guarantees zero network calls to live servers and zero production DB pollution.
 */
const demoAdapter = async (config) => {
  const demoMode = typeof window !== 'undefined' ? localStorage.getItem('hbos_demo_mode') : null;
  if (!demoMode) {
    if (typeof defaultAdapter === 'function') {
      return defaultAdapter(config);
    }
    // If defaultAdapter is not a function in this environment, use standard fetch
    return axios.getAdapter(axios.defaults.adapter)(config);
  }

  demoSandboxEngine.setMode(demoMode);
  const url = config.url || '';
  const method = (config.method || 'get').toLowerCase();

  const makeResponse = (data, status = 200) => ({
    data,
    status,
    statusText: 'OK',
    headers: { 'content-type': 'application/json' },
    config,
  });

  let payload = {};
  if (config.data) {
    try {
      payload = typeof config.data === 'string' ? JSON.parse(config.data) : config.data;
    } catch (e) {
      payload = config.data;
    }
  }

  // Products CRUD
  if (url.match(/\/products\/(\d+)/)) {
    const match = url.match(/\/products\/(\d+)/);
    const prodId = match[1];
    if (method === 'delete') {
      demoSandboxEngine.deleteProduct(prodId);
      return makeResponse({ message: 'Product deleted successfully' });
    }
    if (method === 'post' || method === 'put') {
      const updated = demoSandboxEngine.updateProduct(prodId, payload);
      return makeResponse({ data: updated, message: 'Product updated successfully' });
    }
  }

  if (url.includes('/products')) {
    if (method === 'post') {
      const created = demoSandboxEngine.addProduct(payload);
      return makeResponse({ data: created, message: 'Product added successfully' }, 201);
    }
    const prods = demoSandboxEngine.getProducts();
    return makeResponse(prods);
  }

  // Categories CRUD
  if (url.match(/\/categories\/(\d+)/)) {
    const match = url.match(/\/categories\/(\d+)/);
    const catId = match[1];
    if (method === 'put') {
      const updated = demoSandboxEngine.updateCategory(catId, payload);
      return makeResponse({ data: updated, message: 'Category updated successfully' });
    }
    if (method === 'delete') {
      demoSandboxEngine.deleteCategory(catId);
      return makeResponse({ message: 'Category deleted successfully' });
    }
  }

  if (url.includes('/categories')) {
    if (method === 'post') {
      const created = demoSandboxEngine.addCategory(payload);
      return makeResponse({ data: created, message: 'Category added successfully' }, 201);
    }
    const cats = demoSandboxEngine.getCategories();
    return makeResponse(cats);
  }

  // Subcategories CRUD
  if (url.match(/\/subcategories\/(\d+)/)) {
    const match = url.match(/\/subcategories\/(\d+)/);
    const subId = match[1];
    if (method === 'put') {
      const updated = demoSandboxEngine.updateSubcategory(subId, payload);
      return makeResponse({ data: updated, message: 'Subcategory updated successfully' });
    }
    if (method === 'delete') {
      demoSandboxEngine.deleteSubcategory(subId);
      return makeResponse({ message: 'Subcategory deleted successfully' });
    }
  }

  if (url.includes('/subcategories')) {
    if (method === 'post') {
      const created = demoSandboxEngine.addSubcategory(payload);
      return makeResponse({ data: created, message: 'Subcategory added successfully' }, 201);
    }
    const subs = demoSandboxEngine.getSubcategories();
    return makeResponse(subs);
  }

  // Brands CRUD
  if (url.match(/\/brands\/(\d+)/)) {
    const match = url.match(/\/brands\/(\d+)/);
    const brandId = match[1];
    if (method === 'put') {
      const updated = demoSandboxEngine.updateBrand(brandId, payload);
      return makeResponse({ data: updated, message: 'Brand updated successfully' });
    }
    if (method === 'delete') {
      demoSandboxEngine.deleteBrand(brandId);
      return makeResponse({ message: 'Brand deleted successfully' });
    }
  }

  if (url.includes('/brands')) {
    if (method === 'post') {
      const created = demoSandboxEngine.addBrand(payload);
      return makeResponse(created, 201);
    }
    const brands = demoSandboxEngine.getBrands();
    return makeResponse(brands);
  }

  if (url.match(/\/customers\/([^/?]+)\/ledger/)) {
    const match = url.match(/\/customers\/([^/?]+)\/ledger/);
    const custId = match[1];
    const data = demoSandboxEngine.getCustomerLedger(custId);
    return makeResponse(data);
  }

  if (url.match(/\/customer-payments\/([^/?]+)\/reverse/)) {
    const match = url.match(/\/customer-payments\/([^/?]+)\/reverse/);
    const payId = match[1];
    const rec = demoSandboxEngine.reverseCustomerPayment(payId, payload.reason);
    return makeResponse(rec || { message: 'Payment reversed' });
  }

  if (url.match(/\/customers\/([^/?]+)\/payments/)) {
    const match = url.match(/\/customers\/([^/?]+)\/payments/);
    const custId = match[1];
    if (method === 'post') {
      const rec = demoSandboxEngine.recordCustomerPayment(custId, payload.amount, payload.payment_method);
      return makeResponse({ data: rec, message: 'Payment recorded successfully' }, 201);
    }
    const pays = (demoSandboxEngine.getCustomerPayments() || []).filter(p => String(p.customer_id) === String(custId));
    return makeResponse(pays);
  }

  if (url.includes('/customers')) {
    const custs = demoSandboxEngine.getCustomers();
    return makeResponse(custs);
  }

  if (url.match(/\/suppliers\/(\d+)\/payments/)) {
    const match = url.match(/\/suppliers\/(\d+)\/payments/);
    const suppId = match[1];
    if (method === 'post') {
      const rec = demoSandboxEngine.recordSupplierPayment(suppId, payload?.amount, payload?.payment_method);
      return makeResponse({ data: rec, message: 'Payment recorded' }, 201);
    }
    const pays = (demoSandboxEngine.getSupplierPayments() || []).filter(p => String(p.supplier_id) === String(suppId));
    return makeResponse(pays);
  }

  if (url.includes('/suppliers')) {
    const supps = demoSandboxEngine.getSuppliers();
    return makeResponse(supps);
  }

  // Profile Management (Demo Sandbox)
  if (url.includes('/profile/password') && method === 'put') {
    return makeResponse({ message: 'Password updated successfully in demo sandbox.' });
  }

  if (url.includes('/profile')) {
    if (method === 'put') {
      const storedUser = JSON.parse(localStorage.getItem('hbos_user') || '{}');
      const updatedUser = { ...storedUser, ...payload };
      localStorage.setItem('hbos_user', JSON.stringify(updatedUser));
      return makeResponse({ data: updatedUser, message: 'Profile updated successfully in demo sandbox.' });
    }
    const user = JSON.parse(localStorage.getItem('hbos_user') || 'null') || {
      id: 1,
      name: 'Demo Administrator',
      email: 'demo@hbos.pk',
      phone: '+92 300 1234567',
      role: 'Business Owner',
      business_name: 'HBOS Retail Management',
      created_at: '2024-01-01T00:00:00Z',
    };
    return makeResponse({ data: user });
  }

  if (url.includes('/cash-register-sessions/current') || url.includes('/cash-register-sessions/active')) {
    const session = demoSandboxEngine.getCurrentSession();
    return makeResponse({ session, has_active_session: !!session, status: session ? 'open' : 'closed' });
  }

  if (url.includes('/cash-register-sessions/open') && method === 'post') {
    const session = demoSandboxEngine.openRegisterSession(payload.opening_float, payload.notes);
    return makeResponse({ session, message: 'Register opened successfully' }, 201);
  }

  if (url.includes('/cash-register-sessions/close') && method === 'post') {
    const session = demoSandboxEngine.closeRegisterSession(payload.closing_cash_counted, payload.notes);
    return makeResponse({ session, message: 'Register closed successfully' });
  }

  if (url.includes('/sales')) {
    if (method === 'post') {
      const sale = demoSandboxEngine.recordSale(payload);
      return makeResponse({ data: sale, message: 'Sale completed successfully' }, 201);
    }
    const sales = demoSandboxEngine.getSales(config.params || {});
    return makeResponse(sales);
  }

  if (url.includes('/expense-categories')) {
    const cats = demoSandboxEngine.getExpenseCategories();
    return makeResponse(cats);
  }

  if (url.includes('/expenses')) {
    if (method === 'post') {
      const exp = demoSandboxEngine.recordExpense(payload);
      return makeResponse({ data: exp, message: 'Expense recorded' }, 201);
    }
    const expenses = demoSandboxEngine.getExpenses(config.params || {});
    return makeResponse(expenses);
  }

  if (url.includes('/financial-accounts')) {
    const accs = demoSandboxEngine.getFinancialAccounts();
    return makeResponse(accs);
  }

  if (url.includes('/customer-payments') || url.includes('/khata')) {
    const pays = demoSandboxEngine.getCustomerPayments();
    return makeResponse(pays);
  }

  if (url.includes('/supplier-payments')) {
    const pays = demoSandboxEngine.getSupplierPayments();
    return makeResponse(pays);
  }

  if (url.includes('/dashboard')) {
    const dash = demoSandboxEngine.getDashboard(config.params || {});
    return makeResponse({ data: dash, ...dash });
  }

  if (url.includes('/reports/profitability')) {
    const report = demoSandboxEngine.getProfitabilityReport(config.params || {});
    return makeResponse({ data: report, ...report });
  }

  if (url.includes('/reports/sales')) {
    const report = demoSandboxEngine.getSalesReport(config.params || {});
    return makeResponse({ data: report, ...report });
  }

  if (url.includes('/reports/inventory')) {
    const report = demoSandboxEngine.getInventoryReport(config.params || {});
    return makeResponse({ data: report, ...report });
  }

  if (url.includes('/reports/customers')) {
    const report = demoSandboxEngine.getCustomersReport(config.params || {});
    return makeResponse({ data: report, ...report });
  }

  if (url.includes('/reports/suppliers')) {
    const report = demoSandboxEngine.getSuppliersReport(config.params || {});
    return makeResponse({ data: report, ...report });
  }

  if (url.includes('/reports/expenses')) {
    const report = demoSandboxEngine.getExpensesReport(config.params || {});
    return makeResponse({ data: report, ...report });
  }

  if (url.includes('/reports/financial-accounts')) {
    const report = demoSandboxEngine.getFinancialAccountsReport(config.params || {});
    return makeResponse({ data: report, ...report });
  }

  if (url.includes('/reports/branches')) {
    const report = demoSandboxEngine.getBranchReport(config.params || {});
    return makeResponse({ data: report, ...report });
  }

  if (url.match(/\/branches\/(\d+)\/decommission-preview/)) {
    const match = url.match(/\/branches\/(\d+)\/decommission-preview/);
    const data = demoSandboxEngine.previewDecommission(match[1]);
    return makeResponse({ data });
  }

  if (url.match(/\/branches\/(\d+)\/consolidate-and-decommission/)) {
    const match = url.match(/\/branches\/(\d+)\/consolidate-and-decommission/);
    const data = demoSandboxEngine.consolidateAndDecommissionBranch(match[1]);
    return makeResponse({ message: 'Branch consolidated and decommissioned', data });
  }

  if (url.match(/\/branches\/(\d+)\/purge-test-branch/)) {
    const match = url.match(/\/branches\/(\d+)\/purge-test-branch/);
    const res = demoSandboxEngine.purgeTestBranch(match[1]);
    return makeResponse(res);
  }

  if (url.includes('/branches')) {
    const branches = demoSandboxEngine.getBranches();
    return makeResponse(branches);
  }

  if (url.includes('/branch-inventories') || url.includes('/branch_inventories')) {
    const bId = config.params?.branch_id || null;
    const invs = demoSandboxEngine.getBranchInventories(bId);
    return makeResponse(invs);
  }

  if (url.includes('/stock-adjustments') || url.includes('/inventory/adjust')) {
    const res = demoSandboxEngine.adjustStock(payload.product_id, payload.branch_id, payload.quantity, payload.reason);
    return makeResponse({ data: res, message: 'Stock adjusted successfully' });
  }

  if (url.match(/\/transfers\/([^/?]+)\/receive/) || url.match(/\/stock-transfers\/([^/?]+)\/receive/)) {
    const match = url.match(/\/(?:stock-)?transfers\/([^/?]+)\/receive/);
    const res = demoSandboxEngine.receiveStockTransfer(match[1]);
    return makeResponse(res);
  }

  if (url.includes('/stock-transfers') || url.includes('/transfers')) {
    if (method === 'post') {
      const created = demoSandboxEngine.recordStockTransfer(payload);
      return makeResponse({ data: created, message: 'Stock transfer created successfully' }, 201);
    }
    const transfers = demoSandboxEngine.getStockTransfers();
    return makeResponse(transfers);
  }

  if (url.includes('/notifications')) {
    if (url.includes('/mark-all-read')) {
      demoSandboxEngine.markAllNotificationsRead();
      return makeResponse({ message: 'All notifications marked as read.' });
    }
    const markMatch = url.match(/\/notifications\/([^/]+)\/mark-read/);
    if (markMatch) {
      demoSandboxEngine.markNotificationRead(markMatch[1]);
      return makeResponse({ message: 'Notification marked as read.' });
    }
    const delMatch = url.match(/\/notifications\/([^/]+)/);
    if (delMatch && method === 'delete') {
      demoSandboxEngine.dismissNotification(delMatch[1]);
      return makeResponse({ message: 'Notification dismissed.' });
    }
    const notifs = demoSandboxEngine.getNotifications();
    return makeResponse({ data: notifs });
  }

  if (url.includes('/settings')) {
    return makeResponse({
      receipt_settings: {
        return_policy: 'Exchange within 3 days with original invoice.',
      },
      receipt_footer: 'Thank you for visiting HBOS Retail Demo!'
    });
  }

  const isMutation = ['post', 'put', 'patch', 'delete'].includes(method);
  if (isMutation) {
    const errorResponse = {
      status: 400,
      statusText: 'Bad Request',
      headers: { 'content-type': 'application/json' },
      config,
      data: {
        success: false,
        message: 'This action is not available in Demo mode.',
        error: 'DEMO_UNSUPPORTED_OPERATION',
      },
    };
    const error = new Error('This action is not available in Demo mode.');
    error.response = errorResponse;
    error.config = config;
    return Promise.reject(error);
  }

  return makeResponse({ data: [], message: 'Demo sandbox query' });
};

export const resolveApiBaseUrl = () => {
  if (import.meta.env.VITE_API_BASE_URL) {
    return import.meta.env.VITE_API_BASE_URL;
  }
  if (typeof window !== 'undefined' && window.location.origin.includes('tauri.localhost')) {
    const customEndpoint = localStorage.getItem('hbos_custom_api_endpoint');
    if (customEndpoint) {
      return customEndpoint;
    }
    return 'http://127.0.0.1:8000/api/v1';
  }
  return '/api/v1';
};

const api = axios.create({
    baseURL: resolveApiBaseUrl(),
    adapter: demoAdapter,
    headers: {
        'Accept': 'application/json',
        'X-HBOS-App-Version': APP_VERSION,
        'X-HBOS-Sync-Schema-Version': String(SYNC_SCHEMA_VERSION),
    }
});

// Request interceptor to attach bearer token and active business context
api.interceptors.request.use(config => {
    const token = localStorage.getItem('hbos_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    const businessId = localStorage.getItem('hbos_business_id');
    if (businessId) {
        config.headers['X-Business-ID'] = businessId;
    }
    config.headers['X-HBOS-App-Version'] = APP_VERSION;
    config.headers['X-HBOS-Sync-Schema-Version'] = String(SYNC_SCHEMA_VERSION);
    return config;
}, error => {
    return Promise.reject(error);
});

// Response interceptor to handle versioning, update gating, and 401 Unauthorized
api.interceptors.response.use(response => {
    const updateStatus = response.headers['x-hbos-update-status'];
    const latestVersion = response.headers['x-hbos-latest-version'];
    if (updateStatus === 'UPDATE_AVAILABLE' && typeof window !== 'undefined') {
        window.dispatchEvent(new CustomEvent('hbos:update-available', {
            detail: { latestVersion, currentVersion: APP_VERSION }
        }));
    }
    return response;
}, error => {
    if (error.response) {
        if (error.response.status === 426 && typeof window !== 'undefined') {
            window.dispatchEvent(new CustomEvent('hbos:update-required', {
                detail: error.response.data
            }));
        }

        if (error.response.status === 401) {
            const hadToken = !!localStorage.getItem('hbos_token');
            localStorage.removeItem('hbos_token');
            localStorage.removeItem('hbos_user');
            localStorage.removeItem('hbos_business_id');
            
            const publicPaths = ['/welcome', '/login', '/register', '/about'];
            const isPublicPage = typeof window !== 'undefined' && publicPaths.some(p => window.location.pathname.startsWith(p));

            if (hadToken && !isPublicPage && typeof window !== 'undefined') {
                window.location.href = '/welcome';
            }
        }
    }
    return Promise.reject(error);
});

export default api;
