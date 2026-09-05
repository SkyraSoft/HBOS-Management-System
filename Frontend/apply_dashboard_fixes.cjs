const fs = require('fs');

// 1. Fix ProductView.vue
let pView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', 'utf8');
pView = pView.replace(/<header class="pv-topbar">/, '<header v-if="!isModal" class="pv-topbar">');
fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', pView);

// 2. Fix CustomerView.vue onMounted
let cView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/CustomerView.vue', 'utf8');
const mountedPattern = /onMounted\(async \(\) => \{/;
const newMounted = `onMounted(async () => {
    if (props.isModal && props.modalPage === 'payment') {
        setTimeout(() => openRecordPaymentModal(), 150);
    }`;
cView = cView.replace(mountedPattern, newMounted);
fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/CustomerView.vue', cView);

// 3. Update DashboardView.vue for URLs and Low Stock
let dView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/DashboardView.vue', 'utf8');

// A. URLs
const openModalPattern = /const openModal = \(type\) => \{\s*activeModal\.value = type\s*\}/;
const newOpenModal = `const openModal = (type) => {
  activeModal.value = type
  let path = '/';
  if (type === 'product') path = '/add-product';
  else if (type === 'payment') path = '/record-payment';
  else if (type === 'expense') path = '/add-expense';
  window.history.pushState(null, '', path);
}`;
dView = dView.replace(openModalPattern, newOpenModal);

const closeModalPattern = /const closeModal = \(\) => \{\s*activeModal\.value = null\s*\}/;
const newCloseModal = `const closeModal = () => {
  activeModal.value = null
  window.history.pushState(null, '', '/');
}`;
dView = dView.replace(closeModalPattern, newCloseModal);

// B. Low Stock logic
// First, import retail store in DashboardView
if (!dView.includes('useRetailStore')) {
  dView = dView.replace(/import { useAuthStore } from '\.\.\/stores\/auth'/, "import { useAuthStore } from '../stores/auth'\nimport { useRetailStore } from '../stores/retail'");
  dView = dView.replace(/const authStore = useAuthStore\(\)/, "const authStore = useAuthStore()\nconst retailStore = useRetailStore()");
  dView = dView.replace(/fetchRecentTransactions\(\);/, "fetchRecentTransactions();\n  retailStore.fetchProducts();"); // fetch products if not loaded
}

// Compute low stock items
const lowStockScriptPattern = /const userName = computed/;
const lowStockLogic = `
const lowStockProducts = computed(() => {
  if (!retailStore.products) return [];
  return retailStore.products.filter(p => (p.stock || 0) <= (p.minStock ?? p.min ?? 10)).slice(0, 5);
});
const userName = computed`;
if (!dView.includes('const lowStockProducts')) {
  dView = dView.replace(lowStockScriptPattern, lowStockLogic);
}

// Replace hardcoded low stock HTML
const lowStockHtmlPattern = /<div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">\s*<div>\s*<div class="fw-medium">Milk 1L<\/div>\s*<div class="small text-secondary">Current: 12 \/ Min: 20<\/div>\s*<\/div>\s*<button class="btn btn-sm btn-light border fw-medium text-primary rounded-pill px-3">Restock<\/button>\s*<\/div>\s*<div class="d-flex justify-content-between align-items-center">\s*<div>\s*<div class="fw-medium">Bread \(Large\)<\/div>\s*<div class="small text-secondary">Current: 4 \/ Min: 15<\/div>\s*<\/div>\s*<button class="btn btn-sm btn-light border fw-medium text-primary rounded-pill px-3">Restock<\/button>\s*<\/div>/;

const newLowStockHtml = `
          <div v-for="(item, index) in lowStockProducts" :key="item.id" class="d-flex justify-content-between align-items-center pb-3 mb-3" :class="{'border-bottom': index !== lowStockProducts.length - 1}">
            <div>
              <div class="fw-medium">{{ item.name }}</div>
              <div class="small text-secondary">Current: {{ item.stock || 0 }} / Min: {{ item.minStock ?? item.min ?? 10 }}</div>
            </div>
            <button @click="router.push('/inventry')" class="btn btn-sm btn-light border fw-medium text-primary rounded-pill px-3">Restock</button>
          </div>
          <div v-if="lowStockProducts.length === 0" class="text-secondary small text-center py-3">No low stock items.</div>
`;

dView = dView.replace(lowStockHtmlPattern, newLowStockHtml);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/DashboardView.vue', dView);
console.log('All fixes applied.');
