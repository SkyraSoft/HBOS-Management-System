const fs = require('fs');
const file = 'c:/xampp/htdocs/HBOS/src/views/InventryView.vue';
let txt = fs.readFileSync(file, 'utf8');

// 1. Add computed properties
const computedProps = `
const totalProducts = computed(() => products.value.length)
const activeProducts = computed(() => products.value.filter(p => p.stock > p.minStock).length)
const lowStockProducts = computed(() => products.value.filter(p => p.stock <= p.minStock && p.stock > 0).length)
const outOfStockProducts = computed(() => products.value.filter(p => p.stock === 0).length)
`;

txt = txt.replace(
    "const filteredProducts = computed",
    computedProps + "\nconst filteredProducts = computed"
);

// 2. Replace hardcoded HTML stats
txt = txt.replace(
    /<div class="stat-value" id="totalProducts">[\s\S]*?<\/div>/,
    '<div class="stat-value" id="totalProducts">{{ totalProducts }}</div>'
);
txt = txt.replace(
    /<div class="stat-value" id="activeProducts">[\s\S]*?<\/div>/,
    '<div class="stat-value" id="activeProducts">{{ activeProducts }}</div>'
);
txt = txt.replace(
    /<div class="stat-value" id="lowStock">[\s\S]*?<\/div>/,
    '<div class="stat-value" id="lowStock">{{ lowStockProducts }}</div>'
);
txt = txt.replace(
    /<div class="stat-value text-red" id="outOfStock">[\s\S]*?<\/div>/,
    '<div class="stat-value text-red" id="outOfStock">{{ outOfStockProducts }}</div>'
);

// 3. Fix the table to use real data properties
// In retailStore, properties are: id, name, sku, price, cost, stock, minStock, category, unit
// InventryView.vue's newProduct object expected: name, sku, category, purchasePrice, sellingPrice, stock, unit, minStock
// Wait, the store uses `price` and `cost` instead of `sellingPrice` and `purchasePrice`.
// Let's modify the store reference or the HTML template to match.
// Store uses: { id: 1, name: 'Milk 1L', sku: 'GRO-001', price: 180, cost: 150, stock: 12, minStock: 20, category: 'Dairy', unit: 'Pcs' }
// Let's replace the table row template in InventryView.vue to use price and cost.

txt = txt.replace(/product\.purchasePrice/g, 'product.cost || product.purchasePrice');
txt = txt.replace(/product\.sellingPrice/g, 'product.price || product.sellingPrice');

// 4. Update the layout CSS to remove max-width if it exists, and ensure width: 100%
// The user noted that legacy_html/Inventry.html has `.page { max-width: 1600px; }`.
// In InventryView.vue, `.page` is already `width: 100%;`. Let's ensure there are no `.main` wrappers with constraints.
// Actually, `MainLayout.vue` provides the layout.
// Let's remove `margin: auto;` and `max-width: 1600px;` from ALL `.page` instances in InventryView.vue just in case.

txt = txt.replace(/max-width:\s*1600px;/g, '');
txt = txt.replace(/margin:\s*auto;/g, '');

fs.writeFileSync(file, txt);
console.log('Statistics updated');
