<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useNotificationStore } from '../stores/notifications'

const notificationStore = useNotificationStore()

const router = useRouter()
const route = useRoute()

const isSidebarCollapsed = ref(false)
const isSidebarOpen = ref(false)

const toggleSidebar = () => {
  if (typeof window !== 'undefined' && window.innerWidth <= 900) {
    isSidebarOpen.value = !isSidebarOpen.value
  } else {
    isSidebarCollapsed.value = !isSidebarCollapsed.value
  }
}

const navItems = [
  { name: 'Main Dashboard', path: '/', icon: 'bi-grid-1x2' },
  { name: 'Sales & POS', path: '/POS?tab=sales', icon: 'bi-cart3' },
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
  { name: 'Customers', path: '/customer?tab=customers', icon: 'bi-people' },
  { name: 'Khata', path: '/khata?tab=khata', icon: 'bi-journal-text' },
  { 
    name: 'Suppliers', 
    path: '/supplierpayable', 
    icon: 'bi-truck',
    children: [
      { name: 'Supplier List', path: '/supplierpayable?tab=supplier-intelligence' },
      { name: 'Supplier Profile', path: '/supplier' },
      { name: 'Purchases', path: '/supplierpayable?tab=purchase-orders' },
      { name: 'Supplier Performance', path: '/supplierpayable?tab=supplier-performance' }
    ]
  },
  { 
    name: 'Expenses', 
    path: '/expenses', 
    icon: 'bi-wallet2',
    children: [
      { name: 'Expense List', path: '/expenses?tab=overview' },
      { name: 'Expense Categories', path: '/expenses?tab=categories' },
      { name: 'Expense Reports', path: '/expenses?tab=reports' }
    ]
  },
  { name: 'Employees & HR', path: '/employees', icon: 'bi-person-badge' },
  { name: 'Reports', path: '/reports?tab=reports', icon: 'bi-graph-up' },
  { name: 'Analytics', path: '/reports1?tab=analytics', icon: 'bi-pie-chart' }
]

const bottomNavItems = [
  { 
    name: 'Settings', 
    path: '/settings', 
    icon: 'bi-gear',
    children: [
      { name: 'Users', path: '/settings?tab=users' },
      { name: 'Roles & Permissions', path: '/settings?tab=roles' },
      { name: 'Receipts & Invoices', path: '/settings?tab=receipts' },
      { name: 'Security & Audit', path: '/settings?tab=security' },
      { name: 'Offline Sync', path: '/settings?tab=sync' }
    ]
  },
  { name: 'Help / Support', path: '/support', icon: 'bi-question-circle' }
]

const expandedMenus = ref({});

import { watch, onUnmounted } from 'vue';

const isStoreDropdownOpen = ref(false);
const showNotifications = ref(false);
const selectedStoreName = ref(localStorage.getItem('hbos_selected_store') || 'Main Store');

const availableStores = ref([
  { id: 1, name: 'Main Store', code: 'HQ-01', location: 'Commercial Area, Karachi' },
  { id: 2, name: 'Warehouse Hub', code: 'WH-02', location: 'Industrial Zone, Lahore' },
  { id: 3, name: 'Downtown Express', code: 'DX-03', location: 'Mall Road, Islamabad' },
  { id: 4, name: 'Gulberg Branch', code: 'GB-04', location: 'Gulberg III, Lahore' }
]);

const toggleStoreDropdown = () => {
  isStoreDropdownOpen.value = !isStoreDropdownOpen.value;
};

const selectStore = (store) => {
  selectedStoreName.value = store.name;
  localStorage.setItem('hbos_selected_store', store.name);
  isStoreDropdownOpen.value = false;
};

const globalSearchQuery = ref('');
const showGlobalSearch = ref(false);

const allSearchableRoutes = computed(() => {
  const routes = [];
  const processItems = (items) => {
    items.forEach(item => {
      if (item.children && item.children.length > 0) {
        routes.push({
          title: item.name,
          subtitle: 'Main Section',
          path: item.path,
          icon: item.icon
        });
        item.children.forEach(child => {
          routes.push({
            title: child.name,
            subtitle: `${item.name} → ${child.name}`,
            path: child.path,
            icon: 'bi-dot'
          });
        });
      } else {
        routes.push({
          title: item.name,
          subtitle: 'Main Section',
          path: item.path,
          icon: item.icon
        });
      }
    });
  };
  processItems(navItems);
  processItems(bottomNavItems);
  return routes;
});

const filteredGlobalSearch = computed(() => {
  if (!globalSearchQuery.value.trim()) return [];
  const q = globalSearchQuery.value.toLowerCase().trim();
  return allSearchableRoutes.value.filter(route => 
    route.title.toLowerCase().includes(q) || route.subtitle.toLowerCase().includes(q)
  );
});

const handleGlobalSearchSelect = (item) => {
  router.push(item.path);
  globalSearchQuery.value = '';
  showGlobalSearch.value = false;
};

const handleDocumentClick = (e) => {
  if (!e.target.closest('.store-selector-wrapper')) {
    isStoreDropdownOpen.value = false;
  }
  if (!e.target.closest('.notification-wrapper')) {
    showNotifications.value = false;
  }
  if (!e.target.closest('.search-box-wrapper')) {
    showGlobalSearch.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleDocumentClick);
  if (window.innerWidth <= 768) {
    isSidebarCollapsed.value = true;
  }
  // Fetch initial notifications
  notificationStore.fetchNotifications();
});

onUnmounted(() => {
  document.removeEventListener('click', handleDocumentClick);
});

watch(() => [route.path, route.query], () => {
  [...navItems, ...bottomNavItems].forEach(item => {
    if (item.children && isItemActive(item)) {
      expandedMenus.value[item.path] = true;
    }
  });
}, { immediate: true, deep: true });

const toggleMenu = (path) => {
  expandedMenus.value[path] = !expandedMenus.value[path];
};

const handleNavClick = (item) => {
  if (isSidebarCollapsed.value) {
    isSidebarCollapsed.value = false;
  }
  const targetPath = typeof item === 'string' ? item : item.path;
  if (item.children && item.children.length > 0) {
    toggleMenu(item.path);
    // If not currently in this section, navigate smoothly to its default child tab
    if (route.path !== item.path.split('?')[0]) {
      const defaultChild = item.children[0]?.path || item.path;
      router.push(defaultChild);
    }
  } else {
    if (route.fullPath !== targetPath) {
      router.push(targetPath);
    }
    isSidebarOpen.value = false;
  }
};

const handleChildClick = (childPath) => {
  if (route.fullPath !== childPath) {
    router.push(childPath);
  }
  isSidebarOpen.value = false;
};

const goToDashboard = () => {
  if (route.path !== '/') {
    router.push('/');
  }
};

function isItemActive(item) {
  const currentBase = route.path;
  const itemBase = item.path.split('?')[0];
  if (currentBase === itemBase) return true;
  if ((currentBase === '/customer' || currentBase === '/customers') && (itemBase === '/customer' || itemBase === '/customers')) return true;
  if ((currentBase === '/reports1' || currentBase === '/analytics') && (itemBase === '/reports1' || itemBase === '/analytics')) return true;
  if ((currentBase === '/reports' || currentBase === '/report') && (itemBase === '/reports' || itemBase === '/report')) return true;
  if ((currentBase === '/khata' || currentBase === '/khatas') && (itemBase === '/khata' || itemBase === '/khatas')) return true;
  if (item.children && item.children.some(c => c.path.split('?')[0] === currentBase)) return true;
  return false;
};

function isChildActive(child, item) {
  const [childBase, childQuery] = child.path.split('?');
  if (route.path !== childBase) return false;
  
  if (childQuery) {
    const childParams = new URLSearchParams(childQuery);
    const expectedTab = childParams.get('tab');
    if (route.query.tab) {
      return route.query.tab === expectedTab;
    } else {
      // Default tabs logic when no query param is in URL
      if (item.name === 'Products' || item.name === 'Inventory') return expectedTab === 'products';
      if (item.name === 'Expenses') return expectedTab === 'overview';
      if (item.name === 'Suppliers') return expectedTab === 'supplier-intelligence';
      if (item.name === 'Settings') return expectedTab === 'users';
    }
  }
  
  return !route.query.tab || route.query.tab === '';
};
</script>

<template>
  <!-- Mobile Header -->
  <header class="mobile-header">
    <button class="mobile-menu" @click="toggleSidebar" type="button" aria-label="Open navigation">
      <i class="bi bi-list"></i>
    </button>
    <strong>HBOS</strong>
    <div class="user-avatar">
      <i class="bi bi-person"></i>
    </div>
  </header>

  <!-- Sidebar Overlay for mobile -->
  <div 
    class="sidebar-overlay" 
    :class="{ 'show': isSidebarOpen }" 
    @click="toggleSidebar"
  ></div>

  <!-- App Wrapper -->
  <div class="app">
    
    <!-- Sidebar -->
    <aside class="sidebar" :class="{ 'collapsed': isSidebarCollapsed, 'open': isSidebarOpen }">
      <div class="brand" @click="goToDashboard" style="cursor: pointer;">
        <div class="brand-logo">H</div>
        <div>
          <div class="brand-name">HBOS</div>
          <div class="brand-subtitle">Retail Management</div>
        </div>
      </div>

      <div class="sidebar-scroll" style="flex: 1; overflow-y: auto; padding: 13px 14px;">
        <template v-for="item in navItems" :key="item.path">
          <!-- Standalone link without children -->
          <router-link
            v-if="!item.children"
            :to="item.path"
            class="nav-link"
            :class="{ active: isItemActive(item) }"
            @click="isSidebarOpen = false"
            style="display: flex; align-items: center; justify-content: space-between;"
          >
            <div style="display: flex; align-items: center; gap: 8px;">
              <i class="bi" :class="item.icon" style="margin-right: 8px;"></i>
              <span>{{ item.name }}</span>
            </div>
          </router-link>

          <!-- Parent with dropdown sub-menu -->
          <div v-else>
            <a 
              href="#" 
              class="nav-link" 
              :class="{ active: isItemActive(item) }"
              @click.prevent="handleNavClick(item)"
              style="display: flex; align-items: center; justify-content: space-between;"
            >
              <div style="display: flex; align-items: center; gap: 8px;">
                <i class="bi" :class="item.icon" style="margin-right: 8px;"></i>
                <span>{{ item.name }}</span>
              </div>
              <i class="bi" :class="expandedMenus[item.path] ? 'bi-chevron-up' : 'bi-chevron-down'" style="font-size: 0.8rem; margin-right: 0;"></i>
            </a>
            
            <div v-if="expandedMenus[item.path]" class="sub-menu ms-4 ps-2 border-start" style="margin-bottom: 8px;">
              <router-link 
                v-for="child in item.children"
                :key="child.path"
                :to="child.path"
                class="nav-link sub-nav-link"
                style="height: 38px; font-size: 14px; margin-bottom: 2px;"
                :class="{ active: isChildActive(child, item) }"
                @click="isSidebarOpen = false"
              >
                <div style="display: flex; align-items: center; gap: 8px;">
                  <div style="width: 4px; height: 4px; border-radius: 50%; background-color: currentColor;"></div>
                  <span>{{ child.name }}</span>
                </div>
              </router-link>
            </div>
          </div>
        </template>
      </div>

      <div class="sidebar-bottom" style="padding: 13px 14px; border-top: 1px solid rgba(0,0,0,0.1);">
        <template v-for="item in bottomNavItems" :key="item.path">
          <!-- Standalone link without children -->
          <router-link
            v-if="!item.children"
            :to="item.path"
            class="nav-link"
            :class="{ active: isItemActive(item) }"
            @click="isSidebarOpen = false"
            style="display: flex; align-items: center; justify-content: space-between;"
          >
            <div style="display: flex; align-items: center; gap: 8px;">
              <i class="bi" :class="item.icon" style="margin-right: 8px;"></i>
              <span>{{ item.name }}</span>
            </div>
          </router-link>

          <!-- Parent with dropdown sub-menu -->
          <div v-else>
            <a 
              href="#" 
              class="nav-link" 
              :class="{ active: isItemActive(item) }"
              @click.prevent="handleNavClick(item)"
              style="display: flex; align-items: center; justify-content: space-between;"
            >
              <div style="display: flex; align-items: center; gap: 8px;">
                <i class="bi" :class="item.icon" style="margin-right: 8px;"></i>
                <span>{{ item.name }}</span>
              </div>
              <i class="bi" :class="expandedMenus[item.path] ? 'bi-chevron-up' : 'bi-chevron-down'" style="font-size: 0.8rem; margin-right: 0;"></i>
            </a>
            
            <div v-if="expandedMenus[item.path]" class="sub-menu ms-4 ps-2 border-start" style="margin-bottom: 8px;">
              <router-link 
                v-for="child in item.children"
                :key="child.path"
                :to="child.path"
                class="nav-link sub-nav-link"
                style="height: 38px; font-size: 14px; margin-bottom: 2px;"
                :class="{ active: isChildActive(child, item) }"
                @click="isSidebarOpen = false"
              >
                <div style="display: flex; align-items: center; gap: 8px;">
                  <div style="width: 4px; height: 4px; border-radius: 50%; background-color: currentColor;"></div>
                  <span>{{ child.name }}</span>
                </div>
              </router-link>
            </div>
          </div>
        </template>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="main" :class="{ 'full-width': isSidebarCollapsed }">
      
      <!-- TOPBAR -->
      <header class="topbar">
        <button class="hamburger-toggle-btn" type="button" @click="toggleSidebar" aria-label="Toggle navigation menu" title="Toggle Sidebar">
          <i class="bi bi-list"></i>
        </button>

        <div class="search-box-wrapper position-relative" v-show="route.path !== '/customer'">
          <div class="search-box position-relative d-flex align-items-center">
            <i class="bi bi-search search-icon text-muted" style="position: absolute; left: 16px;"></i>
            <input 
              type="search" 
              id="globalSearch" 
              placeholder="Search HBOS..." 
              class="form-control border-0 bg-light rounded-pill" 
              style="width: 320px; height: 42px; padding-left: 45px; font-size: 14.5px;"
              v-model="globalSearchQuery"
              @focus="showGlobalSearch = true"
              autocomplete="off"
            >
          </div>
          
          <!-- Search Results Dropdown -->
          <div v-if="showGlobalSearch && globalSearchQuery.trim()" class="search-results-dropdown shadow-lg bg-white rounded-4 position-absolute w-100 overflow-hidden" style="top: 50px; left: 0; z-index: 1060; max-height: 400px; overflow-y: auto; border: 1px solid rgba(0,0,0,0.05);">
            <div v-if="filteredGlobalSearch.length === 0" class="p-4 text-center text-muted">
              <i class="bi bi-search d-block mb-2 fs-3 text-secondary opacity-50"></i>
              <span class="small fw-medium">No results found for "{{ globalSearchQuery }}"</span>
            </div>
            <ul v-else class="list-unstyled mb-0 m-0 py-2">
              <li v-for="(result, index) in filteredGlobalSearch" :key="index">
                <a href="#" class="d-flex align-items-center px-3 py-2 text-decoration-none text-dark hover-bg-light" @click.prevent="handleGlobalSearchSelect(result)" style="transition: background 0.15s ease;">
                  <div class="d-flex align-items-center justify-content-center bg-light rounded-circle text-primary me-3" style="width: 32px; height: 32px; flex-shrink: 0;">
                    <i :class="'bi ' + result.icon"></i>
                  </div>
                  <div>
                    <div class="fw-semibold text-dark" style="font-size: 14px;">{{ result.title }}</div>
                    <div class="text-muted" style="font-size: 12px;">{{ result.subtitle }}</div>
                  </div>
                </a>
              </li>
            </ul>
          </div>
        </div>

        <div class="topbar-actions d-flex align-items-center gap-4">
          
          <!-- Notifications Dropdown -->
          <div class="notification-wrapper position-relative">
            <button 
              class="icon-button position-relative border-0 bg-transparent text-secondary fs-5" 
              type="button" 
              aria-label="Notifications"
              @click.stop="showNotifications = !showNotifications"
              style="cursor: pointer; transition: color 0.2s;"
              onmouseover="this.style.color='#0d6efd'"
              onmouseout="this.style.color=''"
            >
              <i class="bi bi-bell"></i>
              <span v-if="notificationStore.unreadCount > 0" class="notification-dot position-absolute p-1 bg-danger rounded-circle border border-white" style="top: 0px; right: 2px;"></span>
            </button>
            
            <div class="notification-dropdown shadow-lg rounded-3 bg-white" v-if="showNotifications" @click.stop style="position: absolute; top: 120%; right: -10px; width: 300px; z-index: 1050; border: 1px solid #e2e8f0;">
              <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light rounded-top-3">
                <h6 class="m-0 fw-bold">Notifications</h6>
                <span class="badge bg-primary rounded-pill">{{ notificationStore.unreadCount }} New</span>
              </div>
              <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                <a v-for="notif in notificationStore.formattedNotifications.slice(0, 5)" :key="notif.id" href="#" class="list-group-item list-group-item-action p-3" :class="{'bg-light': !notif.isRead}">
                  <div class="d-flex gap-3">
                    <div class="mt-1" :class="notif.colorClass"><i class="bi" :class="notif.icon"></i></div>
                    <div>
                      <h6 class="mb-1 text-dark fs-6">{{ notif.title }}</h6>
                      <p class="mb-0 text-muted small">{{ notif.message }}</p>
                      <small class="text-muted" style="font-size: 11px;">{{ notif.time }}</small>
                    </div>
                  </div>
                </a>
                <div v-if="notificationStore.formattedNotifications.length === 0" class="p-4 text-center text-muted small">
                  No new notifications.
                </div>
              </div>
              <div class="p-2 border-top text-center bg-light rounded-bottom-3">
                <router-link to="/notifications" class="text-decoration-none small fw-semibold text-primary" @click="showNotifications = false">View All Notifications</router-link>
              </div>
            </div>
          </div>

          <!-- Store Selector Dropdown -->
          <div class="store-selector-wrapper position-relative">
            <button 
              type="button" 
              class="store-selector-btn d-flex align-items-center gap-2 text-secondary fw-semibold border-0 bg-transparent"
              :class="{ 'active': isStoreDropdownOpen }"
              @click.stop="toggleStoreDropdown"
              aria-haspopup="true"
              :aria-expanded="isStoreDropdownOpen"
              title="Switch Active Store"
            >
              <i class="bi bi-shop store-icon text-primary"></i>
              <span class="store-current-name">{{ selectedStoreName }}</span>
              <i class="bi bi-chevron-down store-arrow-icon" :class="{ 'rotated': isStoreDropdownOpen }"></i>
            </button>

            <!-- Dropdown Menu -->
            <div class="store-dropdown-menu shadow-lg" v-if="isStoreDropdownOpen" @click.stop>
              <div class="store-dropdown-header">
                <span class="store-dropdown-title">Select Store / Branch</span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5" style="font-size: 11px;">4 Outlets</span>
              </div>

              <div class="store-list-items">
                <div 
                  v-for="s in availableStores" 
                  :key="s.id"
                  class="store-menu-item"
                  :class="{ 'selected': selectedStoreName === s.name }"
                  @click="selectStore(s)"
                >
                  <div class="store-item-icon-box" :class="{ 'selected': selectedStoreName === s.name }">
                    <i class="bi bi-shop"></i>
                  </div>
                  <div class="store-item-details">
                    <div class="d-flex align-items-center justify-content-between">
                      <span class="store-item-name">{{ s.name }}</span>
                      <span class="store-item-code">{{ s.code }}</span>
                    </div>
                    <span class="store-item-location">{{ s.location }}</span>
                  </div>
                  <i class="bi bi-check2 text-primary fs-5 ms-2" v-if="selectedStoreName === s.name"></i>
                </div>
              </div>

              <div class="store-dropdown-footer">
                <router-link to="/busniesssetup" class="store-manage-link" @click="isStoreDropdownOpen = false">
                  <i class="bi bi-gear me-1"></i> Manage Store Branches
                </router-link>
              </div>
            </div>
          </div>

          <div class="user-avatar rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-weight: 600;">
            H
          </div>
        </div>
      </header>

      <!-- Page Specific Content -->
      <div class="page-content-wrapper">
        <router-view />
      </div>

    </main>
  </div>
</template>


<style scoped>
.topbar {
  position: sticky;
  top: 0;
  z-index: 1020; /* Ensure it stays above content, modals might use 1050 */
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(8px);
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  padding: 12px 24px;
  margin-bottom: 0px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 4px 20px -10px rgba(0, 0, 0, 0.05);
}

.search-box input:focus {
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
  background: #fff !important;
}

.sidebar {
  transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.2s ease, width 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar.collapsed {
  width: 88px !important;
}

/* Hide brand subtitle and name when collapsed */
.sidebar.collapsed .brand-subtitle, 
.sidebar.collapsed .brand-name {
  display: none;
}

/* Center brand logo when collapsed */
.sidebar.collapsed .brand {
  padding: 10px;
  justify-content: center;
}
.sidebar.collapsed .brand-logo {
  margin: 0 auto;
}

/* Center nav icons and hide text when collapsed */
.sidebar.collapsed .nav-link {
  padding: 0;
  justify-content: center !important;
}
.sidebar.collapsed .nav-link span {
  display: none;
}
.sidebar.collapsed .nav-link i.bi {
  margin-right: 0 !important;
  font-size: 20px;
}

/* Hide dropdown chevrons when collapsed */
.sidebar.collapsed .nav-link {
  flex-direction: column !important;
  padding: 14px 0 !important;
  gap: 6px;
}

.sidebar.collapsed .nav-link > div {
  justify-content: center;
}

.sidebar.collapsed .nav-link > i.bi-chevron-down,
.sidebar.collapsed .nav-link > i.bi-chevron-up {
  display: block;
  font-size: 10px;
  line-height: 1;
  margin-top: 2px;
}

/* Hide sub-menu entirely when sidebar is collapsed */
.sidebar.collapsed .sub-menu {
  display: none !important;
}

/* Ensure the logo looks exactly like the reference */
.sidebar.collapsed .brand-logo {
  background-color: #1a56db;
  color: #fff;
  border-radius: 8px;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  font-weight: bold;
}

.main {
  transition: margin-left 0.25s cubic-bezier(0.4, 0, 0.2, 1), width 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.main.full-width {
  width: calc(100% - 88px) !important;
  margin-left: 88px !important;
}

.hamburger-toggle-btn {
  width: 38px;
  height: 38px;
  border: 1px solid #d8deea;
  border-radius: 8px;
  background: #ffffff;
  color: #334155;
  font-size: 22px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s ease;
  flex-shrink: 0;
  padding: 0;
  margin-right: 12px;
}

.hamburger-toggle-btn:hover {
  background: #f1f5f9;
  color: #0f172a;
  border-color: #cbd5e1;
}

.store-selector-wrapper {
  position: relative;
  user-select: none;
}

.store-selector-btn {
  padding: 6px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.18s ease;
  font-size: 14px;
}

.store-selector-btn:hover, .store-selector-btn.active {
  background: #f1f5f9;
  color: #1e293b !important;
}

.store-icon {
  font-size: 16px;
}

.store-arrow-icon {
  font-size: 12px;
  transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.store-arrow-icon.rotated {
  transform: rotate(180deg);
}

.store-dropdown-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 290px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  z-index: 10000;
  overflow: hidden;
  animation: storeDropdownFadeIn 0.18s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes storeDropdownFadeIn {
  from {
    opacity: 0;
    transform: translateY(-6px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.store-dropdown-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 14px;
  background: #f8fafc;
  border-bottom: 1px solid #f1f5f9;
}

.store-dropdown-title {
  font-size: 12px;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.store-list-items {
  padding: 6px 0;
  max-height: 240px;
  overflow-y: auto;
}

.store-menu-item {
  display: flex;
  align-items: center;
  padding: 9px 14px;
  cursor: pointer;
  transition: all 0.15s ease;
  border-left: 3px solid transparent;
}

.store-menu-item:hover {
  background: #f8fafc;
}

.store-menu-item.selected {
  background: #f0f7ff;
  border-left-color: #2447c6;
}

.store-item-icon-box {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #f1f5f9;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  margin-right: 10px;
  flex-shrink: 0;
}

.store-item-icon-box.selected {
  background: #dbeafe;
  color: #2447c6;
}

.store-item-details {
  flex: 1;
  min-width: 0;
}

.store-item-name {
  font-size: 13.5px;
  font-weight: 600;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.store-item-code {
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  background: #e2e8f0;
  padding: 1px 5px;
  border-radius: 4px;
}

.store-item-location {
  font-size: 11.5px;
  color: #94a3b8;
  display: block;
}

.store-dropdown-footer {
  padding: 10px 14px;
  background: #f8fafc;
  border-top: 1px solid #f1f5f9;
  text-align: center;
}

.store-manage-link {
  font-size: 12px;
  font-weight: 600;
  color: #2447c6;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
}

.store-manage-link:hover {
  text-decoration: underline;
}

</style>

