<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const isSidebarCollapsed = ref(false)
const isSidebarOpen = ref(false)
const isStoreDropdownOpen = ref(false)
const selectedStoreName = ref(localStorage.getItem('hbos_selected_store') || 'Main Store')
const availableStores = ref([
  { id: 1, name: 'Main Store', code: 'HQ-01', location: 'Commercial Area, Karachi' },
  { id: 2, name: 'Warehouse Hub', code: 'WH-02', location: 'Industrial Zone, Lahore' },
  { id: 3, name: 'Downtown Express', code: 'DX-03', location: 'Mall Road, Islamabad' },
  { id: 4, name: 'Gulberg Branch', code: 'GB-04', location: 'Gulberg III, Lahore' }
])
const toggleStoreDropdown = () => { isStoreDropdownOpen.value = !isStoreDropdownOpen.value }
const selectStore = (store) => { selectedStoreName.value = store.name; localStorage.setItem('hbos_selected_store', store.name); isStoreDropdownOpen.value = false }

const toggleSidebar = () => {
  if (typeof window !== 'undefined' && window.innerWidth <= 900) {
    isSidebarOpen.value = !isSidebarOpen.value
  } else {
    isSidebarCollapsed.value = !isSidebarCollapsed.value
  }
}

const navItems = [
  { name: 'Users', path: '/settings/users', icon: 'bi-people' },
  { name: 'Roles & Permissions', path: '/settings/roles', icon: 'bi-shield-check' },
  { name: 'Receipts & Invoices', path: '/settings/receipts', icon: 'bi-receipt' },
  { name: 'Security & Audit', path: '/settings/security', icon: 'bi-shield-lock' },
  { name: 'Offline Sync', path: '/settings/offline-sync', icon: 'bi-cloud-arrow-up' }
]

const handleNavClick = (path) => {
  router.push(path)
  isSidebarOpen.value = false
}

const goBackToDashboard = () => {
  router.push('/')
  isSidebarOpen.value = false
}
</script>

<template>
  <header class="mobile-header">
    <button class="mobile-menu" @click="toggleSidebar" type="button" aria-label="Open navigation">
      <i class="bi bi-list"></i>
    </button>
    <strong>HBOS</strong>
    <div class="user-avatar">
      <i class="bi bi-person"></i>
    </div>
  </header>

  <div 
    class="sidebar-overlay" 
    :class="{ 'show': isSidebarOpen }" 
    @click="toggleSidebar"
  ></div>

  <div class="app">
    <aside class="sidebar" :class="{ 'collapsed': isSidebarCollapsed, 'open': isSidebarOpen }">
      <div class="brand">
        <div class="brand-logo" style="background: #333;">S</div>
        <div>
          <div class="brand-name">Settings</div>
          <div class="brand-subtitle">System Configuration</div>
        </div>
      </div>

      <div class="sidebar-scroll" style="flex: 1; overflow-y: auto; padding: 13px 14px;">
        <a 
          v-for="item in navItems" 
          :key="item.path"
          :href="item.path"
          class="nav-link"
          :class="{ active: route.path === item.path || route.path.startsWith(item.path) }"
          @click.prevent="handleNavClick(item.path)"
        >
          <i class="bi" :class="item.icon"></i>
          <span>{{ item.name }}</span>
        </a>
      </div>

      <div class="sidebar-bottom" style="padding: 13px 14px; border-top: 1px solid rgba(0,0,0,0.1);">
        <a 
          href="#"
          class="nav-link"
          @click.prevent=""
        >
          <i class="bi bi-question-circle"></i>
          <span>Help / Support</span>
        </a>
      </div>
    </aside>

    <main class="main" :class="{ 'full-width': isSidebarCollapsed }">
      <header class="topbar">
        <button class="mobile-menu" type="button" @click="toggleSidebar" aria-label="Open menu">
          <i class="bi bi-list"></i>
        </button>
        <div class="topbar-actions ms-auto d-flex align-items-center gap-4">
          <button class="icon-button position-relative border-0 bg-transparent text-secondary fs-5" type="button" aria-label="Notifications">
            <i class="bi bi-bell"></i>
            <span class="notification-dot position-absolute p-1 bg-danger rounded-circle" style="top: 0px; right: 0px;"></span>
          </button>

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

      <div class="main-content">
        <router-view />
      </div>
    </main>
  </div>
</template>

<style scoped>
.main-content {
  max-width: 100% !important;
  margin: 0 !important;
  padding-left: 0 !important;
  padding-right: 0 !important;
  width: 100% !important;
}

.sidebar-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 950;
}
.sidebar-overlay.show {
  display: block;
}
</style>
