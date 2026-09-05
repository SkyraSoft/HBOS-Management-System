const fs = require('fs');
let pView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/PurchaseView.vue', 'utf8');

// 1. Vue refs for shipping & amount paid
if (!pView.includes('const shippingCost = ref(50);')) {
    pView = pView.replace(/const isSubmitting = ref\(false\);/, `const isSubmitting = ref(false);\nconst shippingCost = ref(50);\nconst amountPaid = ref(5278.10);\nconst productSearchQuery = ref('');\nconst showProductDropdown = ref(false);\nconst retailStore = useRetailStore();\n\nconst filteredProducts = computed(() => {\n  if (!productSearchQuery.value) return [];\n  return retailStore.products.filter(p => p.name.toLowerCase().includes(productSearchQuery.value.toLowerCase()) || (p.sku && p.sku.toLowerCase().includes(productSearchQuery.value.toLowerCase())));\n});\n\nconst selectProduct = (prod) => {\n  purchaseItems.value.push({\n    name: prod.name,\n    sku: prod.sku || '',\n    quantity: 1,\n    unit: prod.unit || 'pcs',\n    price: prod.cost || prod.price || 0,\n    discount: 0\n  });\n  productSearchQuery.value = '';\n  showProductDropdown.value = false;\n};\n`);
}

// Ensure retailStore is imported
if (!pView.includes('useRetailStore')) {
    pView = pView.replace(/import { useSuppliersStore } from '@\/stores\/suppliers';/, `import { useSuppliersStore } from '@/stores/suppliers';\nimport { useRetailStore } from '@/stores/retail.js';`);
}
// Ensure products are fetched on mount
pView = pView.replace(/await suppliersStore\.fetchSuppliers\(\);/, `await suppliersStore.fetchSuppliers();\n    await retailStore.fetchProducts();`);

// 2. Fix Product Search UI
const productSearchPattern = /<input class="product-search" type="search" placeholder="[^"]*" id="productSearch" oninput="searchProducts\(\)">/;
const newProductSearch = `<div style="position: relative; width: 250px;">
    <input class="product-search" type="search" placeholder="Search products..." v-model="productSearchQuery" @focus="showProductDropdown = true" @blur="setTimeout(() => showProductDropdown = false, 200)" style="width: 100%;">
    <ul v-if="showProductDropdown && filteredProducts.length > 0" class="dropdown-menu show" style="position: absolute; top: 100%; left: 0; width: 100%; z-index: 1000; max-height: 200px; overflow-y: auto;">
        <li v-for="prod in filteredProducts" :key="prod.id" style="cursor: pointer;">
            <a class="dropdown-item py-2" @mousedown.prevent="selectProduct(prod)">
                <div class="fw-bold">{{ prod.name }}</div>
                <small class="text-secondary">{{ prod.sku }} | PKR {{ prod.cost || prod.price || 0 }}</small>
            </a>
        </li>
    </ul>
</div>`;
pView = pView.replace(productSearchPattern, newProductSearch);

// 3. Fix summary totals in HTML
pView = pView.replace(/<span id="subtotal">[\s\S]*?<\/span>/, `<span id="subtotal">{{ formatNumber(subtotal) }}</span>`);
pView = pView.replace(/<span id="discountTotal">[\s\S]*?<\/span>/, `<span id="discountTotal">-{{ formatNumber(discountTotal) }}</span>`);
pView = pView.replace(/<span id="tax">[\s\S]*?<\/span>/, `<span id="tax">{{ formatNumber(tax) }}</span>`);
pView = pView.replace(/<span id="grandTotal">[\s\S]*?<\/span>/, `<span id="grandTotal">{{ formatNumber(grandTotal) }}</span>`);

// 4. Fix input fields for shipping and amount paid
pView = pView.replace(/<input class="mini-input" id="shipping" type="number" value="50" min="0" style="width:96px;" oninput="calculatePurchase\(\)">/, `<input class="mini-input" id="shipping" type="number" v-model.number="shippingCost" min="0" style="width:96px;">`);
pView = pView.replace(/<input class="input" id="amountPaid" type="number" value="5278\.10" min="0" oninput="updateAmountPaid\(\)">/, `<input class="input" id="amountPaid" type="number" v-model.number="amountPaid" min="0">`);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/PurchaseView.vue', pView);
console.log('PurchaseView.vue updated.');
