import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/products',
      name: 'Products',
      component: () => import('../views/InventryView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/inventory/products',
      name: 'InventoryProducts',
      component: () => import('../views/InventryView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/settings',
      name: 'Settings',
      component: () => import('../views/SettingsView.vue'),
      meta: { layout: 'MainLayout' }
    },
    { path: '/settings/branches', redirect: '/settings?tab=branches' },
    { path: '/settings/users', redirect: '/settings?tab=users' },
    { path: '/settings/roles', redirect: '/settings?tab=roles' },
    { path: '/settings/receipts', redirect: '/settings?tab=receipts' },
    { path: '/settings/security', redirect: '/settings?tab=security' },
    { path: '/settings/offline-sync', redirect: '/settings?tab=sync' },
    {
      path: '/transfers',
      alias: ['/stock-transfers', '/inventory/transfers', '/stocktransfers'],
      name: 'StockTransfers',
      component: () => import('../views/StockTransferView.vue'),
      meta: { layout: 'MainLayout', requiresAuth: true }
    },
    {
      path: '/welcome',
      name: 'Welcome',
      component: () => import('../views/WelcomeView.vue'),
      meta: { layout: 'auth', requiresAuth: false }
    },
    {
      path: '/about',
      name: 'About',
      component: () => import('../views/AboutView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/businesssetup1',
      name: 'BusinessSetup1',
      component: () => import('../views/BusinessSetup1View.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/mainstoresetup',
      name: 'MainStoreSetup',
      component: () => import('../views/MainStoreSetupView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/busniesssetup',
      alias: ['/branches'],
      name: 'BusniessSetup',
      component: () => import('../views/BusniessSetupView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/completereview',
      name: 'CompleteReview',
      component: () => import('../views/CompleteReviewView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/notifications',
      name: 'Notifications',
      component: () => import('../views/NotificationsView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/employees',
      redirect: '/settings?tab=users'
    },
    {
      path: '/completesetup',
      name: 'CompleteSetup',
      component: () => import('../views/CompleteSetupView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/customer',
      alias: ['/customers'],
      name: 'Customer',
      component: () => import('../views/CustomerView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/',
      alias: ['/dashboard'],
      name: 'Dashboard',
      component: () => import('../views/DashboardView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/expenses',
      name: 'Expenses',
      component: () => import('../views/ExpensesView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/information',
      name: 'Information',
      component: () => import('../views/InformationView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/inventry',
      alias: '/inventory',
      name: 'Inventry',
      component: () => import('../views/InventryView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/khata',
      alias: ['/khatas'],
      redirect: to => ({ path: '/customer', query: { tab: 'customers', ...to.query } })
    },
    {
      path: '/login',
      name: 'Login',
      component: () => import('../views/LoginView.vue'),
      meta: { layout: 'auth' }
    },
    {
      path: '/POS',
      alias: ['/pos'],
      name: 'PosSale',
      component: () => import('../views/PosSaleView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/preferences',
      name: 'Preferences',
      component: () => import('../views/PreferencesView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/profilecustomer',
      name: 'Profilecustomer',
      component: () => import('../views/ProfilecustomerView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/purchase',
      name: 'Purchase',
      component: () => import('../views/PurchaseView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/register',
      name: 'Register',
      component: () => import('../views/RegisterView.vue'),
      meta: { layout: 'auth' }
    },
    {
      path: '/reorderdetail',
      name: 'ReorderDetail',
      component: () => import('../views/ReorderDetailView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/reorder',
      name: 'Reorder',
      component: () => import('../views/ReorderView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/reports',
      alias: ['/report'],
      name: 'Reports',
      component: () => import('../views/ReportsView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/reports1',
      alias: ['/analytics'],
      redirect: '/reports'
    },
    {
      path: '/sales',
      name: 'Sales',
      component: () => import('../views/SalesView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/supplierpayable',
      alias: ['/supplierpayables', '/supplier-payable', '/supplier-payables'],
      name: 'Supplierpayable',
      component: () => import('../views/SupplierpayableView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/supplier',
      alias: ['/suppliers', '/supplier-invoices', '/supplierinvoices'],
      name: 'Supplier',
      component: () => import('../views/SupplierView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/supplierpayment',
      name: 'SupplierPayment',
      component: () => import('../views/SupplierPaymentView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/support',
      name: 'Support',
      component: () => import('../views/SupportView.vue'),
      meta: { layout: 'MainLayout', requiresAuth: true }
    },
    {
      path: '/activity',
      alias: ['/activities', '/operational-activity'],
      redirect: '/notifications?tab=activity'
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/'
    }
  ]
})

// Canonical Registered Routes in HBOS Frontend
export const CANONICAL_ROUTES = {
  dashboard: '/',
  pos: '/POS',
  settings_users: '/settings?tab=users',
  branch_management: '/busniesssetup',
  reports: '/reports',
  inventory: '/inventry'
};

// Helper for POLICY-B1 compatibility roles permission verification
function hasUserPermission(user, permission) {
  if (!user) return false;
  const role = user.role || user.roles?.[0]?.name;
  if (role === 'Business Owner') return true;

  if (Array.isArray(user.permissions)) {
    if (user.permissions.some(p => (typeof p === 'string' ? p : p.name) === permission)) {
      return true;
    }
  }

  if (Array.isArray(user.roles)) {
    for (const r of user.roles) {
      if (Array.isArray(r.permissions)) {
        if (r.permissions.some(p => (typeof p === 'string' ? p : p.name) === permission)) {
          return true;
        }
      }
    }
  }

  const canonicalRolePermissions = {
    'Accountant': [
      'view full reports', 'view branch reports', 'view sales', 'view own sales',
      'create customers', 'view suppliers', 'manage suppliers', 'record supplier payments',
      'view purchases', 'view products', 'view categories', 'view brands', 'view inventory'
    ],
    'Inventory Manager': [
      'view products', 'manage products', 'view categories', 'manage categories',
      'view brands', 'manage brands', 'view inventory', 'manage local inventory',
      'adjust inventory', 'transfer inventory', 'view stock movements', 'view suppliers',
      'view purchases', 'manage purchases', 'return purchases'
    ]
  };

  if (canonicalRolePermissions[role]) {
    return canonicalRolePermissions[role].includes(permission);
  }

  return false;
}

function getDefaultLanding(user) {
  const role = user?.role || user?.roles?.[0]?.name;

  // 1. Curated V1 Personas
  if (role === 'Salesperson') return CANONICAL_ROUTES.pos;
  if (role === 'Branch Manager') return CANONICAL_ROUTES.dashboard;
  if (role === 'Business Owner') return CANONICAL_ROUTES.dashboard;

  // 2. Compatibility Roles (POLICY-B1): Test permissions in priority order
  if (role === 'Accountant' && (hasUserPermission(user, 'view full reports') || hasUserPermission(user, 'view branch reports'))) {
    return CANONICAL_ROUTES.reports;
  }
  if (role === 'Inventory Manager' && (hasUserPermission(user, 'view inventory') || hasUserPermission(user, 'view products'))) {
    return CANONICAL_ROUTES.inventory;
  }

  // Dynamic permission-checked fallback
  if (hasUserPermission(user, 'view full reports') || hasUserPermission(user, 'view branch reports')) {
    return CANONICAL_ROUTES.reports;
  }
  if (hasUserPermission(user, 'view inventory') || hasUserPermission(user, 'view products')) {
    return CANONICAL_ROUTES.inventory;
  }
  if (hasUserPermission(user, 'view purchases') || hasUserPermission(user, 'view suppliers')) {
    return '/supplierpayable';
  }
  if (hasUserPermission(user, 'view sales') || hasUserPermission(user, 'view own sales')) {
    return '/sales';
  }
  if (hasUserPermission(user, 'create sales')) {
    return CANONICAL_ROUTES.pos;
  }

  return '/support';
}

// Navigation Guard
router.beforeEach((to, from) => {
  const token = localStorage.getItem('hbos_token');
  const demoMode = localStorage.getItem('hbos_demo_mode');
  const isDemo = demoMode === 'single' || demoMode === 'multi';
  const isAuthenticated = !!token || isDemo;
  
  const publicRoutes = ['Welcome', 'Login', 'Register', 'About'];
  const requiresAuth = !publicRoutes.includes(to.name) && (to.meta?.requiresAuth !== false);

  // Cold startup without real merchant token must always land on Welcome (no auto-booting demo data)
  if (!from.name && !token && (to.path === '/' || to.name === 'Dashboard')) {
    localStorage.removeItem('hbos_demo_mode');
    return { name: 'Welcome' };
  }

  // Unauthenticated landing at root '/' redirects to Welcome view
  if ((to.path === '/' || to.name === 'Dashboard') && !isAuthenticated) {
    return { name: 'Welcome' };
  }

  if (requiresAuth && !isAuthenticated) {
    return { name: 'Welcome' };
  }

  // Parse user for role and permissions
  let user = null;
  if (isDemo) {
    user = {
      name: demoMode === 'multi' ? 'Al-Madina Supermarket Owner' : 'Al-Madina General Store Owner',
      role: 'Business Owner',
      business_name: demoMode === 'multi' ? 'Al-Madina Supermarket' : 'Al-Madina General Store',
      is_demo: true,
      demo_type: demoMode,
    };
  } else if (token) {
    try {
      user = JSON.parse(localStorage.getItem('hbos_user'));
    } catch (e) {
      user = null;
    }
  }

  const role = user?.role || user?.roles?.[0]?.name;

  // Redirect authenticated user away from public login/register/welcome to their default landing
  // (unless explicitly navigating to welcome to switch accounts)
  if ((to.name === 'Login' || to.name === 'Register') && isAuthenticated) {
    return { path: getDefaultLanding(user) };
  }

  // Role-based Route Protection and Adaptive Redirection
  if (isAuthenticated && role) {
    const targetPath = (to.path || '').toLowerCase();

    // Single-Store: Inter-branch transfers route forbidden
    const isSingleStore = isDemo ? (demoMode === 'single') : false;
    if ((targetPath === '/transfers' || targetPath.startsWith('/transfers') || to.name === 'StockTransfers') && isSingleStore) {
      return { path: '/inventry?tab=products' };
    }

    // 1. CASHIER / SALESPERSON: POS-First Terminal
    if (role === 'Salesperson') {
      // Direct access to Dashboard: FORBIDDEN_REDIRECT_TO_POS
      if (targetPath === '/' || targetPath === '/dashboard') {
        return { path: CANONICAL_ROUTES.pos };
      }

      // Cashier forbidden administrative/back-office routes
      const cashierForbiddenPaths = [
        '/inventry', '/inventory', '/products', '/inventory/products',
        '/purchase', '/expenses', '/reports', '/reports1', '/analytics',
        '/supplierpayable', '/supplier', '/supplierpayment',
        '/settings', '/branches', '/busniesssetup', '/businesssetup1',
        '/reorder', '/reorderdetail', '/accounts', '/employees'
      ];

      const isForbidden = cashierForbiddenPaths.some(p => targetPath === p || targetPath.startsWith(p + '/'));
      if (isForbidden) {
        return { path: CANONICAL_ROUTES.pos };
      }
    }

    // 2. BRANCH MANAGER: Assigned Branch Operational Workspace
    if (role === 'Branch Manager') {
      // Manager forbidden Owner-only governance routes: FORBIDDEN_REDIRECT_TO_DASHBOARD (Canonical Dashboard '/')
      const managerForbiddenPaths = [
        '/settings', '/branches', '/busniesssetup', '/businesssetup1', '/accounts', '/employees'
      ];
      const isForbidden = managerForbiddenPaths.some(p => targetPath === p || targetPath.startsWith(p + '/'));
      if (isForbidden) {
        return { path: CANONICAL_ROUTES.dashboard };
      }
    }

    // 3. COMPATIBILITY ROLES (POLICY-B1): PERMISSION_FALLBACK
    if (role !== 'Business Owner' && role !== 'Branch Manager' && role !== 'Salesperson') {
      // Governance routes forbidden to compatibility roles
      const governancePaths = ['/settings', '/branches', '/busniesssetup', '/businesssetup1', '/accounts', '/employees'];
      if (governancePaths.some(p => targetPath === p || targetPath.startsWith(p + '/'))) {
        return { path: getDefaultLanding(user) };
      }

      // Route-level permission checks
      const isReportsPath = targetPath === '/reports' || targetPath === '/reports1' || targetPath === '/analytics';
      if (isReportsPath && !hasUserPermission(user, 'view full reports') && !hasUserPermission(user, 'view branch reports')) {
        return { path: getDefaultLanding(user) };
      }

      const isInventoryPath = targetPath === '/inventry' || targetPath === '/inventory' || targetPath === '/products' || targetPath === '/inventory/products';
      if (isInventoryPath && !hasUserPermission(user, 'view inventory') && !hasUserPermission(user, 'view products')) {
        return { path: getDefaultLanding(user) };
      }

      const isSupplyPath = targetPath === '/supplierpayable' || targetPath === '/supplier' || targetPath === '/supplierpayment' || targetPath === '/purchase';
      if (isSupplyPath && !hasUserPermission(user, 'view suppliers') && !hasUserPermission(user, 'view purchases')) {
        return { path: getDefaultLanding(user) };
      }
    }

    // 4. BUSINESS OWNER: Universal Tenant Scope
    // Single-store owner direct access to /branches /busniesssetup is DIRECT_ACCESS_ALLOWED!
    // No restrictions applied to Business Owner.
  }
})

export default router
