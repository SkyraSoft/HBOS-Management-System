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
    { path: '/settings/users', redirect: '/settings?tab=users' },
    { path: '/settings/roles', redirect: '/settings?tab=roles' },
    { path: '/settings/receipts', redirect: '/settings?tab=receipts' },
    { path: '/settings/security', redirect: '/settings?tab=security' },
    { path: '/settings/offline-sync', redirect: '/settings?tab=sync' },
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
      path: '/busniesssetup',
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
      name: 'Employees',
      component: () => import('../views/EmployeesView.vue'),
      meta: { layout: 'MainLayout' }
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
      name: 'Khata',
      component: () => import('../views/KhataView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/login',
      name: 'Login',
      component: () => import('../views/LoginView.vue'),
      meta: { layout: 'auth' }
    },
    {
      path: '/POS',
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
      name: 'reports1',
      component: () => import('../views/reports1View.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/sales',
      name: 'Sales',
      component: () => import('../views/SalesView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/supplierpayable',
      name: 'Supplierpayable',
      component: () => import('../views/SupplierpayableView.vue'),
      meta: { layout: 'MainLayout' }
    },
    {
      path: '/supplier',
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
    }
  ]
})

// Navigation Guard
router.beforeEach((to, from, next) => {
  // Wait until Pinia is initialized to check auth store, usually not an issue but we can get it from localStorage directly or import store inside
  const token = localStorage.getItem('hbos_token');
  const isAuthenticated = !!token;
  
  // By default, assuming all routes require auth except login and register
  const publicRoutes = ['Login', 'Register', 'About'];
  const requiresAuth = !publicRoutes.includes(to.name) && (to.meta.requiresAuth !== false);

  if (requiresAuth && !isAuthenticated) {
    next({ name: 'Login' });
  } else if ((to.name === 'Login' || to.name === 'Register') && isAuthenticated) {
    next({ name: 'Dashboard' }); // Redirect to dashboard if already logged in
  } else {
    next();
  }
})

export default router
