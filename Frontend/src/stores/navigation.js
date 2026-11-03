import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '../api';
import { useAuthStore } from './auth';

/**
 * Navigation & Presentation Store
 *
 * Architecture Classification: STATEFUL_SESSION_CONTEXT_RESET_REQUIRED
 * Zero client persistence: Strictly transient in-memory state.
 * Owns mutable in-memory branch records and UI selected scope.
 * Derives businessShape, role-scoped visibleNavItems, defaultLanding, and selector visibility.
 */
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

export const useNavigationStore = defineStore('navigation', () => {
  // ----------------------------------------------------
  // MUTABLE SOURCE STATE (IN-MEMORY ONLY)
  // ----------------------------------------------------
  const branches = ref([]);
  const selectedBranchScope = ref('all'); // 'all' or specific branch_id
  const fetchStatus = ref('IDLE'); // 'IDLE' | 'LOADING' | 'SUCCESS' | 'ERROR'
  const fetchError = ref(null);

  // View-As Preview Mode State (REQ-0006 / SRT-GAP-020)
  const viewAsRole = ref(null);
  const viewAsBranchId = ref(null);
  const viewAsBranchName = ref(null);
  const isViewAsActive = computed(() => !!viewAsRole.value);

  // Action Notes Count (REQ-0029 / SRT-GAP-023)
  const actionNotesPendingCount = ref(0);

  // ----------------------------------------------------
  // DERIVED GETTERS (COMPUTED)
  // ----------------------------------------------------
  const activeBranches = computed(() => {
    return branches.value.filter(b => b && b.status === 'active');
  });

  const activeBranchCount = computed(() => activeBranches.value.length);

  const mainStore = computed(() => {
    return activeBranches.value.find(b => b && (b.is_primary === true || b.is_primary === 1)) 
      || activeBranches.value[0] 
      || null;
  });

  const businessShape = computed(() => {
    if (fetchStatus.value === 'LOADING' || fetchStatus.value === 'IDLE') return 'CONTEXT_LOADING';
    if (fetchStatus.value === 'ERROR') return 'CONTEXT_ERROR';
    if (activeBranchCount.value === 0) return 'ZERO_BRANCHES_FALLBACK';
    if (activeBranchCount.value === 1) return 'SINGLE_STORE';
    return 'MULTI_BRANCH';
  });

  const isSingleStore = computed(() => businessShape.value === 'SINGLE_STORE');
  const isMultiBranch = computed(() => businessShape.value === 'MULTI_BRANCH');

  const branchSelectorVisible = computed(() => {
    const authStore = useAuthStore();
    const role = authStore.user?.role || authStore.user?.roles?.[0]?.name;
    // Visible only for Business Owner in MULTI_BRANCH mode with >= 2 active branches
    return isMultiBranch.value && role === 'Business Owner';
  });

  const defaultLanding = computed(() => {
    const authStore = useAuthStore();
    const user = authStore.user;
    const role = user?.role || user?.roles?.[0]?.name;

    // Curated V1 Personas
    if (role === 'Salesperson') return '/POS';
    if (role === 'Branch Manager') return '/';
    if (role === 'Business Owner') return '/';

    // Compatibility Roles (POLICY-B1): Test permissions in priority order
    if (role === 'Accountant' && (hasUserPermission(user, 'view full reports') || hasUserPermission(user, 'view branch reports'))) {
      return '/reports';
    }
    if (role === 'Inventory Manager' && (hasUserPermission(user, 'view inventory') || hasUserPermission(user, 'view products'))) {
      return '/inventry';
    }

    // Dynamic permission-checked fallback
    if (hasUserPermission(user, 'view full reports') || hasUserPermission(user, 'view branch reports')) {
      return '/reports';
    }
    if (hasUserPermission(user, 'view inventory') || hasUserPermission(user, 'view products')) {
      return '/inventry';
    }
    if (hasUserPermission(user, 'view purchases') || hasUserPermission(user, 'view suppliers')) {
      return '/supplierpayable';
    }
    if (hasUserPermission(user, 'view sales') || hasUserPermission(user, 'view own sales')) {
      return '/sales';
    }
    if (hasUserPermission(user, 'create sales')) {
      return '/POS';
    }

    return '/support';
  });

  const visibleNavItems = computed(() => {
    const authStore = useAuthStore();
    const user = authStore.user;
    const role = user?.role || user?.roles?.[0]?.name;

    // 1. CASHIER / SALESPERSON: POS-First Terminal
    if (role === 'Salesperson') {
      return [
        { name: 'Sales & POS', path: '/POS?tab=sales', icon: 'bi-cart3' },
        { name: 'Sales History', path: '/sales', icon: 'bi-receipt' },
        { name: 'Customers & Khata', path: '/customer?tab=customers', icon: 'bi-people' }
      ];
    }

    // 2. BRANCH MANAGER: Assigned Branch Operational Workspace
    if (role === 'Branch Manager') {
      return [
        { name: 'Main Dashboard', path: '/', icon: 'bi-grid-1x2' },
        { name: 'Sales & POS', path: '/POS?tab=sales', icon: 'bi-cart3' },
        { name: 'Sales History', path: '/sales', icon: 'bi-receipt' },
        { name: 'Customers & Khata', path: '/customer?tab=customers', icon: 'bi-people' },
        { 
          name: 'Inventory', 
          path: '/inventry', 
          icon: 'bi-boxes',
          children: [
            { name: 'All Products & Stock', path: '/inventry?tab=products' },
            { name: 'Categories', path: '/inventry?tab=categories' },
            { name: 'Brands', path: '/inventry?tab=brands' }
          ]
        },
        { name: 'Suppliers', path: '/supplierpayable', icon: 'bi-truck' },
      { name: 'Expenses', path: '/expenses', icon: 'bi-wallet2' },
        { name: 'Stock Transfers', path: '/transfers', icon: 'bi-arrow-left-right' },
        { name: 'Reports', path: '/reports?tab=reports', icon: 'bi-graph-up' }
      ];
    }

    // 3. COMPATIBILITY ROLES (POLICY-B1): Strictly Permission-Derived
    if (role !== 'Business Owner' && role !== 'Branch Manager' && role !== 'Salesperson') {
      const items = [];

      // Reports: requires report viewing permission
      if (hasUserPermission(user, 'view full reports') || hasUserPermission(user, 'view branch reports')) {
        items.push({ name: 'Reports', path: '/reports?tab=reports', icon: 'bi-graph-up' });
      }

      // Expenses: requires full reports or accountant ledger permission
      if (hasUserPermission(user, 'view full reports') || role === 'Accountant') {
        items.push({ name: 'Expenses', path: '/expenses', icon: 'bi-wallet2' });
      }

      // Sales history: requires sales viewing permission
      if (hasUserPermission(user, 'view sales') || hasUserPermission(user, 'view own sales')) {
        items.push({ name: 'Sales History', path: '/sales', icon: 'bi-receipt' });
      }

      // Customers & Khata: requires customer creation/viewing permission
      if (hasUserPermission(user, 'create customers')) {
        items.push({ name: 'Customers & Khata', path: '/customer?tab=customers', icon: 'bi-people' });
      }

      // Inventory: requires inventory or product viewing permission
      if (hasUserPermission(user, 'view inventory') || hasUserPermission(user, 'view products')) {
        items.push({ 
          name: 'Inventory', 
          path: '/inventry', 
          icon: 'bi-boxes',
          children: [
            { name: 'All Products & Stock', path: '/inventry?tab=products' },
            { name: 'Categories', path: '/inventry?tab=categories' },
            { name: 'Brands', path: '/inventry?tab=brands' }
          ]
        });
      }

      // Suppliers & Purchases: requires supplier or purchase viewing permission
      if (hasUserPermission(user, 'view suppliers') || hasUserPermission(user, 'view purchases')) {
        items.push({ name: 'Suppliers', path: '/supplierpayable', icon: 'bi-truck' });
      }

      if (items.length > 0) {
        return items;
      }
    }

    // 4. BUSINESS OWNER: Default Full Tenant Scope
    // Single-store hides multi-branch clutter (no dedicated staff nav, no transfers, no branches item)
    const ownerNav = [
      { name: 'Main Dashboard', path: '/', icon: 'bi-grid-1x2' },
      { name: 'Sales & POS', path: '/POS?tab=sales', icon: 'bi-cart3' },
      { name: 'Sales History', path: '/sales', icon: 'bi-receipt' },
      { 
        name: 'Inventory', 
        path: '/inventry', 
        icon: 'bi-boxes',
        children: [
          { name: 'All Products & Stock', path: '/inventry?tab=products' },
          { name: 'Categories', path: '/inventry?tab=categories' },
          { name: 'Brands', path: '/inventry?tab=brands' }
        ]
      },
      { name: 'Customers & Khata', path: '/customer?tab=customers', icon: 'bi-people' },
      { name: 'Suppliers', path: '/supplierpayable', icon: 'bi-truck' },
      { name: 'Expenses', path: '/expenses', icon: 'bi-wallet2' },
    ];

    // Single-store progressive disclosure: Stock Transfers only shown when activeBranchCount >= 2
    if (isMultiBranch.value) {
      ownerNav.push({ name: 'Stock Transfers', path: '/transfers', icon: 'bi-arrow-left-right' });
    }

    ownerNav.push({ name: 'Reports', path: '/reports?tab=reports', icon: 'bi-graph-up' });
    return ownerNav;
  });

  const bottomNavItems = computed(() => {
    const authStore = useAuthStore();
    const role = authStore.user?.role || authStore.user?.roles?.[0]?.name;

    // Only Business Owner has Settings in bottom nav
    if (role === 'Business Owner' || !role) {
      return [
        { 
          name: 'Settings', 
          path: '/settings', 
          icon: 'bi-gear',
          children: [
            { name: 'My Profile', path: '/settings?tab=profile' },
            { name: 'Security & Password', path: '/settings?tab=account-security' },
            ...(isMultiBranch.value ? [{ name: 'Branches', path: '/settings?tab=branches' }] : []),
            { name: 'User Management', path: '/settings?tab=users' },
            { name: 'Roles & Permissions', path: '/settings?tab=roles' },
            { name: 'Receipts & Invoices', path: '/settings?tab=receipts' },
            { name: 'Audit & Governance', path: '/settings?tab=security' },
            { name: 'Offline Sync', path: '/settings?tab=sync' }
          ]
        },
        { name: 'Help / Support', path: '/support', icon: 'bi-question-circle' }
      ];
    }

    // Other roles only receive Support
    return [
      { name: 'Help / Support', path: '/support', icon: 'bi-question-circle' }
    ];
  });

  // ----------------------------------------------------
  // ACTIONS
  // ----------------------------------------------------
  let pendingFetch = null;

  async function fetchBranches() {
    if (fetchStatus.value === 'LOADING' && pendingFetch) {
      return pendingFetch;
    }

    fetchStatus.value = 'LOADING';
    fetchError.value = null;

    pendingFetch = (async () => {
      try {
        const response = await api.get('/branches');
        const data = response.data?.data || response.data || [];
        branches.value = Array.isArray(data) ? data : [];
        fetchStatus.value = 'SUCCESS';

        // Scope validation: if selectedBranchScope is not 'all' and not among active branches, reset to 'all'
        if (selectedBranchScope.value !== 'all') {
          const exists = activeBranches.value.some(b => String(b.id) === String(selectedBranchScope.value));
          if (!exists) {
            selectedBranchScope.value = 'all';
          }
        }
        return branches.value;
      } catch (err) {
        fetchStatus.value = 'ERROR';
        fetchError.value = err;
        console.warn('[HBOS Navigation] Could not fetch branches:', err);
        return [];
      } finally {
        pendingFetch = null;
      }
    })();

    return pendingFetch;
  }

  function setSelectedBranchScope(scope) {
    selectedBranchScope.value = scope;
  }

  async function fetchActionNotesPendingCount() {
    try {
      const response = await api.get('/action-notes/pending-count');
      actionNotesPendingCount.value = response.data?.count || 0;
      return actionNotesPendingCount.value;
    } catch (e) {
      actionNotesPendingCount.value = 0;
      return 0;
    }
  }

  function enterViewAs(role, branchId, branchName) {
    const authStore = useAuthStore();
    viewAsRole.value = role;
    viewAsBranchId.value = branchId;
    viewAsBranchName.value = branchName;
    authStore.setViewAs(true, role, branchId);
  }

  function exitViewAs() {
    const authStore = useAuthStore();
    viewAsRole.value = null;
    viewAsBranchId.value = null;
    viewAsBranchName.value = null;
    authStore.setViewAs(false);
  }

  function reset() {
    branches.value = [];
    selectedBranchScope.value = 'all';
    fetchStatus.value = 'IDLE';
    fetchError.value = null;
    pendingFetch = null;
    viewAsRole.value = null;
    viewAsBranchId.value = null;
    viewAsBranchName.value = null;
    actionNotesPendingCount.value = 0;
  }

  return {
    // Mutable State
    branches,
    selectedBranchScope,
    fetchStatus,
    fetchError,
    viewAsRole,
    viewAsBranchId,
    viewAsBranchName,
    isViewAsActive,
    actionNotesPendingCount,

    // Derived Getters
    activeBranches,
    activeBranchCount,
    mainStore,
    businessShape,
    isSingleStore,
    isMultiBranch,
    branchSelectorVisible,
    defaultLanding,
    visibleNavItems,
    bottomNavItems,

    // Actions
    fetchBranches,
    setSelectedBranchScope,
    fetchActionNotesPendingCount,
    enterViewAs,
    exitViewAs,
    reset
  };
});
