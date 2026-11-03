<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useNotificationStore } from '../stores/notifications'
import { useNavigationStore } from '../stores/navigation'
import { useAuthStore } from '../stores/auth'
import { useSessionModeStore } from '../stores/sessionMode'
import ViewAsBanner from '../components/ViewAsBanner.vue'
import DemoModeBanner from '../components/DemoModeBanner.vue'
import SyncStatusBadge from '../components/SyncStatusBadge.vue'
import { syncCoordinator } from '../services/syncCoordinator.js'

const notificationStore = useNotificationStore()
const navigationStore = useNavigationStore()
const authStore = useAuthStore()
const sessionStore = useSessionModeStore()

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

// Adaptive Navigation items centralized from navigationStore
const navItems = computed(() => navigationStore.visibleNavItems)
const bottomNavItems = computed(() => navigationStore.bottomNavItems)

const expandedMenus = ref({});

const isStoreDropdownOpen = ref(false);
const showNotifications = ref(false);

const currentSelectedScopeName = computed(() => {
  if (navigationStore.selectedBranchScope === 'all') {
    return 'All Stores'
  }
  const branch = navigationStore.activeBranches.find(b => String(b.id) === String(navigationStore.selectedBranchScope))
  if (branch) {
    return (branch.is_primary === true || branch.is_primary === 1) ? 'Main Store' : branch.name
  }
  return 'All Stores'
})

const toggleStoreDropdown = () => {
  isStoreDropdownOpen.value = !isStoreDropdownOpen.value;
};

const selectBranchScope = (scope) => {
  navigationStore.setSelectedBranchScope(scope);
  isStoreDropdownOpen.value = false;
  if (scope !== 'all') {
    authStore.setActiveBranch(scope);
  }
};

const globalSearchQuery = ref('');
const showGlobalSearch = ref(false);

const allSearchableRoutes = computed(() => {
  const routes = [];
  const processItems = (items) => {
    (items || []).forEach(item => {
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
  processItems(navItems.value);
  processItems(bottomNavItems.value);
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

const isUserDropdownOpen = ref(false);

const isDemo = computed(() => {
  const mode = localStorage.getItem('hbos_demo_mode');
  return mode === 'single' || mode === 'multi';
});

const userName = computed(() => {
  return authStore.user?.name || (isDemo.value ? 'Demo Administrator' : 'Administrator');
});

const userEmail = computed(() => {
  return authStore.user?.email || (isDemo.value ? 'demo@hbos.pk' : 'admin@hbos.pk');
});

const userRole = computed(() => {
  return authStore.user?.role || (isDemo.value ? 'Store Owner (Demo)' : 'Business Owner');
});

const userInitial = computed(() => {
  const name = userName.value;
  return name ? name.charAt(0).toUpperCase() : 'H';
});

const toggleUserDropdown = () => {
  isUserDropdownOpen.value = !isUserDropdownOpen.value;
};

const handleLogout = async () => {
  isUserDropdownOpen.value = false;
  try {
    if (localStorage.getItem('hbos_demo_mode')) {
      sessionStore.exitDemo();
    }
    await authStore.logout();
  } catch (error) {
    console.error('Logout error:', error);
  } finally {
    router.push('/login');
  }
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
  if (!e.target.closest('.user-menu-wrapper') && !e.target.closest('.user-avatar-mobile-btn')) {
    isUserDropdownOpen.value = false;
  }
};

const dropdownNotifTab = ref('all');

const dropdownFilteredNotifications = computed(() => {
  const list = notificationStore.formattedNotifications || [];
  if (dropdownNotifTab.value === 'alerts') {
    return list.filter(n => n.type === 'Alerts & Attention' || n.type === 'Emergency/Critical' || n.severity === 'warning' || n.severity === 'critical');
  }
  if (dropdownNotifTab.value === 'activity') {
    return list.filter(n => n.type === 'Common / Activity' || n.type === 'Common/General');
  }
  return list;
});

const handleNotificationClick = (notif) => {
  if (!notif.isRead) {
    notificationStore.markAsRead(notif.id);
  }
  showNotifications.value = false;
  if (notif.action_payload && typeof notif.action_payload === 'string' && notif.action_payload.trim() !== '') {
    router.push(notif.action_payload);
  } else {
    router.push('/notifications');
  }
};

onMounted(() => {
  document.addEventListener('click', handleDocumentClick);
  if (window.innerWidth <= 768) {
    isSidebarCollapsed.value = true;
  }
  // Fetch initial notifications, branches, and pending counts if authenticated or in demo mode
  const token = localStorage.getItem('hbos_token');
  const isDemo = localStorage.getItem('hbos_demo_mode');
  if (token || isDemo) {
    notificationStore.fetchNotifications();
    navigationStore.fetchBranches();
    if (token) {
      navigationStore.fetchActionNotesPendingCount();
      syncCoordinator.init();
    }
  }
});

onUnmounted(() => {
  document.removeEventListener('click', handleDocumentClick);
});

watch(() => [route.path, route.query], () => {
  [...(navItems.value || []), ...(bottomNavItems.value || [])].forEach(item => {
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
      
      <!-- Demo Mode Workspace Banner (Checkpoints 3.1 & 3.2) -->
      <DemoModeBanner />

      <!-- View-As Read-Only Preview Banner (REQ-0006) -->
      <ViewAsBanner />

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
              style="max-width: 320px; width: 100%; min-width: 140px; height: 42px; padding-left: 45px; font-size: 14.5px;"
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
          
          <!-- Phase-8 Sync Status Badge (REQ-0030 / SRT-GAP-025) -->
          <SyncStatusBadge />

          <!-- Action Notes Badge (REQ-0029 / SRT-GAP-023) -->
          <router-link 
            v-if="navigationStore.actionNotesPendingCount > 0"
            to="/?tab=action-notes"
            class="d-flex align-items-center gap-1 text-decoration-none px-2 py-1 rounded-pill"
            style="background: #fef3c7; color: #b45309; font-size: 13px; font-weight: 600; border: 1px solid #fde68a;"
            title="Pending Operational Action Notes"
          >
            <i class="bi bi-clipboard-check"></i>
            <span>{{ navigationStore.actionNotesPendingCount }}</span>
          </router-link>

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
              title="Notifications & Alerts"
            >
              <i class="bi bi-bell"></i>
              <span 
                v-if="notificationStore.unreadCount > 0" 
                class="position-absolute badge rounded-pill bg-danger shadow-sm" 
                style="top: -4px; right: -6px; font-size: 10px; padding: 2px 5px; min-width: 17px; line-height: 12px;"
              >
                {{ notificationStore.unreadCount }}
              </span>
            </button>
            
            <div 
              class="notification-dropdown shadow-lg rounded-3 bg-white" 
              v-if="showNotifications" 
              @click.stop 
              style="position: absolute; top: 120%; right: -10px; width: 350px; z-index: 1050; border: 1px solid #e2e8f0;"
            >
              <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light rounded-top-3">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-bell-fill text-primary"></i>
                  <h6 class="m-0 fw-bold text-dark">Notifications &amp; Alerts</h6>
                </div>
                <span class="badge bg-primary rounded-pill">{{ notificationStore.unreadCount }} New</span>
              </div>

              <!-- Interactive Tabs inside Dropdown -->
              <div class="px-3 py-2 border-bottom bg-white d-flex gap-1.5 align-items-center">
                <button
                  type="button"
                  class="btn btn-sm rounded-pill px-2.5 py-0.5 fw-semibold border-0"
                  :class="dropdownNotifTab === 'all' ? 'btn-primary text-white' : 'btn-light text-secondary'"
                  style="font-size: 11px;"
                  @click="dropdownNotifTab = 'all'"
                >
                  All ({{ notificationStore.formattedNotifications.length }})
                </button>
                <button
                  type="button"
                  class="btn btn-sm rounded-pill px-2.5 py-0.5 fw-semibold border-0 d-flex align-items-center gap-1"
                  :class="dropdownNotifTab === 'alerts' ? 'btn-warning text-dark' : 'btn-light text-secondary'"
                  style="font-size: 11px;"
                  @click="dropdownNotifTab = 'alerts'"
                >
                  <i class="bi bi-exclamation-triangle-fill" style="font-size: 10px;"></i>
                  Alerts ({{ notificationStore.alertCount }})
                </button>
                <button
                  type="button"
                  class="btn btn-sm rounded-pill px-2.5 py-0.5 fw-semibold border-0"
                  :class="dropdownNotifTab === 'activity' ? 'btn-secondary text-white' : 'btn-light text-secondary'"
                  style="font-size: 11px;"
                  @click="dropdownNotifTab = 'activity'"
                >
                  Activity
                </button>
              </div>
              
              <div class="list-group list-group-flush" style="max-height: 350px; overflow-y: auto;">
                <div 
                  v-for="notif in dropdownFilteredNotifications.slice(0, 8)" 
                  :key="notif.id" 
                  class="list-group-item list-group-item-action p-3"
                  :class="{
                    'bg-light-subtle': !notif.isRead,
                    'border-start border-3 border-warning': notif.type === 'Alerts & Attention'
                  }"
                  @click="handleNotificationClick(notif)"
                  style="cursor: pointer; transition: background-color 0.15s ease;"
                >
                  <div class="d-flex gap-2.5">
                    <div class="mt-0.5 fs-5 flex-shrink-0" :class="notif.colorClass">
                      <i class="bi" :class="notif.icon"></i>
                    </div>
                    <div class="flex-grow-1">
                      <div class="d-flex align-items-center justify-content-between mb-1">
                        <h6 class="mb-0 text-dark fw-bold" style="font-size: 13.5px;">{{ notif.title }}</h6>
                        <span 
                          v-if="notif.type === 'Alerts & Attention'" 
                          class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill" 
                          style="font-size: 10px;"
                        >
                          Alert
                        </span>
                      </div>
                      <p class="mb-1 text-muted small" style="font-size: 12px; line-height: 1.4;">{{ notif.message }}</p>
                      <div class="d-flex justify-content-between align-items-center mt-1">
                        <small class="text-muted" style="font-size: 11px;">{{ notif.time }}</small>
                        <span v-if="notif.action_payload" class="text-primary fw-semibold small" style="font-size: 11px;">
                          {{ notif.action_label || 'View' }} &rarr;
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div v-if="dropdownFilteredNotifications.length === 0" class="p-4 text-center text-muted small">
                  {{ dropdownNotifTab === 'alerts' ? 'No active alerts requiring attention.' : dropdownNotifTab === 'activity' ? 'No recent activity logs.' : 'No new notifications or alerts.' }}
                </div>
              </div>
              
              <div class="p-2.5 border-top d-flex justify-content-between align-items-center bg-light rounded-bottom-3 px-3">
                <button 
                  type="button" 
                  class="btn btn-link btn-sm p-0 text-decoration-none text-muted small" 
                  @click="notificationStore.markAllAsRead"
                >
                  Mark all read
                </button>
                <router-link 
                  to="/notifications" 
                  class="text-decoration-none small fw-semibold text-primary" 
                  @click="showNotifications = false"
                >
                  View All Notifications &rarr;
                </router-link>
              </div>
            </div>
          </div>

          <!-- Store Selector Dropdown: Visible ONLY for Multi-Branch Owner (>= 2 active branches) -->
          <div v-if="navigationStore.branchSelectorVisible" class="store-selector-wrapper position-relative">
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
              <span class="store-current-name">{{ currentSelectedScopeName }}</span>
              <i class="bi bi-chevron-down store-arrow-icon" :class="{ 'rotated': isStoreDropdownOpen }"></i>
            </button>

            <!-- Dropdown Menu -->
            <div class="store-dropdown-menu shadow-lg" v-if="isStoreDropdownOpen" @click.stop>
              <div class="store-dropdown-header">
                <span class="store-dropdown-title">Select Store / Branch</span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5" style="font-size: 11px;">
                  {{ navigationStore.activeBranchCount }} Outlets
                </span>
              </div>

              <div class="store-list-items">
                <!-- ALL STORES AGGREGATE OPTION -->
                <div 
                  class="store-menu-item"
                  :class="{ 'selected': navigationStore.selectedBranchScope === 'all' }"
                  @click="selectBranchScope('all')"
                >
                  <div class="store-item-icon-box" :class="{ 'selected': navigationStore.selectedBranchScope === 'all' }">
                    <i class="bi bi-buildings"></i>
                  </div>
                  <div class="store-item-details">
                    <div class="d-flex align-items-center justify-content-between">
                      <span class="store-item-name">All Stores</span>
                      <span class="badge bg-secondary-subtle text-dark">Chain</span>
                    </div>
                    <span class="store-item-location">Aggregate Chain Scope</span>
                  </div>
                  <i class="bi bi-check2 text-primary fs-5 ms-2" v-if="navigationStore.selectedBranchScope === 'all'"></i>
                </div>

                <!-- ACTIVE BRANCHES -->
                <div 
                  v-for="s in navigationStore.activeBranches" 
                  :key="s.id"
                  class="store-menu-item"
                  :class="{ 'selected': String(navigationStore.selectedBranchScope) === String(s.id) }"
                  @click="selectBranchScope(s.id)"
                >
                  <div class="store-item-icon-box" :class="{ 'selected': String(navigationStore.selectedBranchScope) === String(s.id) }">
                    <i class="bi bi-shop"></i>
                  </div>
                  <div class="store-item-details">
                    <div class="d-flex align-items-center justify-content-between">
                      <span class="store-item-name">{{ (s.is_primary === true || s.is_primary === 1) ? 'Main Store' : s.name }}</span>
                      <span class="store-item-code" v-if="s.code">{{ s.code }}</span>
                    </div>
                    <span class="store-item-location" v-if="s.address || s.city">{{ s.address || s.city }}</span>
                  </div>
                  <i class="bi bi-check2 text-primary fs-5 ms-2" v-if="String(navigationStore.selectedBranchScope) === String(s.id)"></i>
                </div>
              </div>

              <div class="store-dropdown-footer">
                <router-link to="/settings?tab=users" class="store-manage-link" @click="isStoreDropdownOpen = false">
                  <i class="bi bi-gear me-1"></i> Store &amp; Branch Settings
                </router-link>
              </div>
            </div>
          </div>

          <!-- User Menu Dropdown -->
          <div class="user-menu-wrapper position-relative">
            <button 
              type="button" 
              class="user-avatar-btn d-flex align-items-center gap-2 border-0 bg-transparent p-0 rounded-circle"
              @click.stop="toggleUserDropdown"
              aria-haspopup="true"
              :aria-expanded="isUserDropdownOpen"
              title="User Account Menu"
            >
              <div class="user-avatar rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px; font-weight: 600; font-size: 15px;">
                {{ userInitial }}
              </div>
            </button>

            <!-- User Dropdown Menu -->
            <transition name="user-dropdown-fade">
              <div class="user-dropdown-menu shadow-lg" v-if="isUserDropdownOpen" @click.stop>
                <!-- User Profile Header -->
                <div class="user-dropdown-header">
                  <div class="d-flex align-items-center gap-3">
                    <div class="user-avatar-lg rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-5">
                      {{ userInitial }}
                    </div>
                    <div class="user-info-text overflow-hidden">
                      <div class="user-name text-truncate" :title="userName">{{ userName }}</div>
                      <div class="user-email text-muted text-truncate" :title="userEmail">{{ userEmail }}</div>
                      <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5 mt-1" style="font-size: 10.5px;">
                        {{ userRole }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Menu Links -->
                <div class="user-dropdown-body">
                  <router-link to="/settings?tab=profile" class="user-dropdown-item" @click="isUserDropdownOpen = false">
                    <div class="user-item-icon"><i class="bi bi-person-gear"></i></div>
                    <div class="user-item-text">
                      <span class="user-item-title">Profile &amp; Account</span>
                      <span class="user-item-sub">Manage personal profile details</span>
                    </div>
                  </router-link>

                  <router-link to="/settings?tab=account-security" class="user-dropdown-item" @click="isUserDropdownOpen = false">
                    <div class="user-item-icon"><i class="bi bi-shield-check"></i></div>
                    <div class="user-item-text">
                      <span class="user-item-title">Security &amp; Password</span>
                      <span class="user-item-sub">Change password &amp; sessions</span>
                    </div>
                  </router-link>

                  <router-link to="/support" class="user-dropdown-item" @click="isUserDropdownOpen = false">
                    <div class="user-item-icon"><i class="bi bi-question-circle"></i></div>
                    <div class="user-item-text">
                      <span class="user-item-title">Help &amp; Support</span>
                      <span class="user-item-sub">Documentation &amp; support desk</span>
                    </div>
                  </router-link>
                </div>

                <!-- Footer / Logout -->
                <div class="user-dropdown-footer">
                  <button type="button" class="user-logout-btn w-100" @click="handleLogout">
                    <i class="bi bi-box-arrow-right me-2 text-danger"></i>
                    <span class="text-danger fw-semibold">Log Out</span>
                  </button>
                </div>
              </div>
            </transition>
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

/* User Menu & Dropdown */
.user-menu-wrapper {
  position: relative;
  user-select: none;
}

.user-avatar-btn {
  cursor: pointer;
  transition: transform 0.15s ease, opacity 0.15s ease;
}

.user-avatar-btn:hover {
  transform: scale(1.04);
}

.user-avatar {
  transition: box-shadow 0.2s ease;
}

.user-avatar:hover {
  box-shadow: 0 0 0 3px rgba(36, 71, 198, 0.25) !important;
}

.user-avatar-lg {
  width: 44px;
  height: 44px;
  flex-shrink: 0;
}

.user-dropdown-menu {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 280px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  z-index: 10000;
  overflow: hidden;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
}

.user-dropdown-header {
  padding: 14px 16px;
  background: #f8fafc;
  border-bottom: 1px solid #f1f5f9;
}

.user-name {
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
  line-height: 1.2;
}

.user-email {
  font-size: 12px;
  line-height: 1.3;
  margin-top: 2px;
}

.user-dropdown-body {
  padding: 6px;
}

.user-dropdown-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 9px 12px;
  border-radius: 9px;
  text-decoration: none;
  transition: background-color 0.15s ease;
}

.user-dropdown-item:hover {
  background-color: #f1f5f9;
}

.user-item-icon {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  color: #475569;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  flex-shrink: 0;
}

.user-dropdown-item:hover .user-item-icon {
  background: #ffffff;
  color: #2447c6;
  border-color: #cbd5e1;
}

.user-item-text {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.user-item-title {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  line-height: 1.2;
}

.user-item-sub {
  font-size: 11px;
  color: #94a3b8;
  line-height: 1.2;
  margin-top: 2px;
}

.user-dropdown-footer {
  padding: 8px 10px;
  background: #f8fafc;
  border-top: 1px solid #f1f5f9;
}

.user-logout-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid #fee2e2;
  background: #fff;
  cursor: pointer;
  font-size: 13px;
  transition: all 0.15s ease;
}

.user-logout-btn:hover {
  background: #fef2f2;
  border-color: #fca5a5;
}

.user-dropdown-fade-enter-active,
.user-dropdown-fade-leave-active {
  transition: opacity 0.16s ease, transform 0.16s cubic-bezier(0.16, 1, 0.3, 1);
}

.user-dropdown-fade-enter-from,
.user-dropdown-fade-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.98);
}

</style>

