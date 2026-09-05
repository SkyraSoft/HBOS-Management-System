<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'

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
  { name: 'Products', path: '/inventory/products', icon: 'bi-box-seam' },
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
    <strong>HBOS Products</strong>
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
        <div class="brand-logo">P</div>
        <div>
          <div class="brand-name">Products</div>
          <div class="brand-subtitle">Inventory Management</div>
        </div>
      </div>

      <nav class="sidebar-nav">
        <!-- Back to Dashboard -->
        <a 
          href="#"
          class="nav-item"
          @click.prevent="goBackToDashboard"
          style="color: var(--primary); font-weight: 700; margin-bottom: 10px; background: rgba(36, 71, 184, 0.05);"
        >
          <i class="bi bi-arrow-left"></i>
          Main Dashboard
        </a>

        <div style="border-top: 1px solid var(--border); margin: 10px 0;"></div>

        <a 
          v-for="item in navItems" 
          :key="item.path"
          :href="item.path"
          class="nav-item"
          :class="{ active: route.path === item.path || route.path.startsWith(item.path) }"
          @click.prevent="handleNavClick(item.path)"
        >
          <i class="bi" :class="item.icon"></i>
          {{ item.name }}
        </a>
      </nav>
    </aside>

    <main class="main" :class="{ 'full-width': isSidebarCollapsed }">
      <header class="topbar">
        <button class="mobile-menu" type="button" @click="toggleSidebar" aria-label="Open menu">
          <i class="bi bi-list"></i>
        </button>
        <div class="search-box">
          <i class="bi bi-search search-icon text-muted" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%);"></i>
          <input type="search" id="globalSearch" placeholder="Search products..." class="form-control border-0 bg-light rounded-pill ps-5" style="width: 300px; height: 40px;">
        </div>
        <div class="topbar-actions d-flex align-items-center gap-4">
          <div class="user-avatar rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-weight: 600;">
            H
          </div>
        </div>
      </header>

      <div class="page-content-wrapper p-4">
        <router-view />
      </div>
    </main>
  </div>
</template>

<style scoped>
/* Scoped styles if needed, mostly relies on main.css */
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
