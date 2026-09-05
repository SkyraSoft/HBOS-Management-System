const fs = require('fs');

let content = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/layouts/MainLayout.vue', 'utf8');

// Replace the script section logic
const oldScriptPattern = /const navItems = \[[\s\S]*?<\/script>/;
const newScript = `const navItems = [
  { name: 'Main Dashboard', path: '/', icon: 'bi-grid-1x2' },
  { name: 'Sales', path: '/possale', icon: 'bi-cart3' },
  { 
    name: 'Products', 
    path: '/products', 
    icon: 'bi-box-seam',
    children: [
      { name: 'Product List', path: '/products?tab=products' },
      { name: 'Add Product', path: '/products?tab=add' },
      { name: 'Categories', path: '/products?tab=categories' }
    ]
  },
  { name: 'Inventory', path: '/inventry', icon: 'bi-boxes' },
  { name: 'Customers', path: '/customer', icon: 'bi-people' },
  { name: 'Khata', path: '/khata', icon: 'bi-journal-text' },
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
      { name: 'Expense Categories', path: '/expenses?tab=analytics' },
      { name: 'Expense Reports', path: '/reports' }
    ]
  },
  { name: 'Reports', path: '/reports', icon: 'bi-graph-up' },
  { name: 'Analytics', path: '/reports1', icon: 'bi-pie-chart' }
]

const bottomNavItems = [
  { name: 'Settings', path: '/settings', icon: 'bi-gear' },
  { name: 'Help / Support', path: '/support', icon: 'bi-question-circle' }
]

const expandedMenus = ref({});

import { watch } from 'vue';

watch(() => [route.path, route.query], () => {
  navItems.forEach(item => {
    if (item.children && isItemActive(item)) {
      expandedMenus.value[item.path] = true;
    }
  });
}, { immediate: true, deep: true });

const toggleMenu = (path) => {
  expandedMenus.value[path] = !expandedMenus.value[path];
};

const handleNavClick = (item) => {
  if (item.children) {
    toggleMenu(item.path);
  } else {
    router.push(item.path);
    isSidebarOpen.value = false;
  }
};

const handleChildClick = (childPath) => {
  router.push(childPath);
  isSidebarOpen.value = false;
};

const goToDashboard = () => {
  router.push('/')
}

const isItemActive = (item) => {
  if (route.path === item.path) return true;
  if (item.children && item.children.some(c => c.path.split('?')[0] === route.path)) return true;
  return false;
};

const isChildActive = (child, item) => {
  if (child.path.includes('?')) {
    if (route.query.tab) {
      return child.path.includes('tab=' + route.query.tab);
    } else {
      // Default tabs logic
      if (item.name === 'Products') return child.path.includes('tab=products');
      if (item.name === 'Expenses') return child.path.includes('tab=overview');
      if (item.name === 'Suppliers') return child.path.includes('tab=supplier-intelligence');
    }
  }
  return route.path === child.path;
};
</script>`;

content = content.replace(oldScriptPattern, newScript);

// Replace template sidebar-scroll content
const oldTemplatePattern = /<div class="sidebar-scroll"[\s\S]*?<div class="sidebar-bottom"/;
const newTemplate = `<div class="sidebar-scroll" style="flex: 1; overflow-y: auto; padding: 13px 14px;">
        <template v-for="item in navItems" :key="item.path">
          <a 
            href="#" 
            class="nav-link" 
            :class="{ active: isItemActive(item) }"
            @click.prevent="handleNavClick(item)"
            style="display: flex; align-items: center; justify-content: space-between;"
          >
            <div style="display: flex; align-items: center;">
              <i class="bi" :class="item.icon"></i>
              <span>{{ item.name }}</span>
            </div>
            <i v-if="item.children" class="bi" :class="expandedMenus[item.path] ? 'bi-chevron-up' : 'bi-chevron-down'" style="font-size: 0.8rem; margin-right: 0;"></i>
          </a>
          
          <div v-if="item.children && expandedMenus[item.path]" class="sub-menu ms-4 ps-2 border-start" style="margin-bottom: 8px;">
            <a 
              v-for="child in item.children"
              :key="child.path"
              href="#"
              class="nav-link sub-nav-link"
              style="height: 38px; font-size: 14px; margin-bottom: 2px;"
              :class="{ active: isChildActive(child, item) }"
              @click.prevent="handleChildClick(child.path)"
            >
              <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 4px; height: 4px; border-radius: 50%; background-color: currentColor;"></div>
                <span>{{ child.name }}</span>
              </div>
            </a>
          </div>
        </template>
      </div>

      <div class="sidebar-bottom"`;

content = content.replace(oldTemplatePattern, newTemplate);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/layouts/MainLayout.vue', content);
console.log('MainLayout updated.');
