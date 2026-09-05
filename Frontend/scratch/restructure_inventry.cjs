const fs = require('fs');

// 1. Update router to use EmptyLayout for /inventry
const routerFile = 'c:/xampp/htdocs/HBOS/src/router/index.js';
let routerTxt = fs.readFileSync(routerFile, 'utf8');
routerTxt = routerTxt.replace(
    /path: '\/inventry',[\s\S]*?meta: \{ layout: 'MainLayout' \}/,
    `path: '/inventry',\n      name: 'Inventry',\n      component: () => import('../views/InventryView.vue'),\n      meta: { layout: 'EmptyLayout' }`
);
fs.writeFileSync(routerFile, routerTxt);

// 2. Rebuild InventryView.vue using the exact layout from legacy_html/Inventry.html
const legacyFile = 'c:/xampp/htdocs/HBOS/legacy_html/Inventry.html';
const vueFile = 'c:/xampp/htdocs/HBOS/src/views/InventryView.vue';
let legacyTxt = fs.readFileSync(legacyFile, 'utf8');
let vueTxt = fs.readFileSync(vueFile, 'utf8');

// Extract the exact body content from legacy
const bodyStart = legacyTxt.indexOf('<div class="app">');
const bodyEnd = legacyTxt.indexOf('<script>', bodyStart);
let newTemplate = legacyTxt.substring(bodyStart, bodyEnd);

// Make the active tab dynamic
newTemplate = newTemplate.replace(
    /<section class="page products-page" id="productsPage">/,
    `<section class="page products-page" id="productsPage" v-if="activeTab === 'products'">`
);
newTemplate = newTemplate.replace(
    /<section class="page invoice-page" id="invoicePage">/,
    `<section class="page invoice-page" id="invoicePage" v-if="activeTab === 'invoice'">`
);
newTemplate = newTemplate.replace(
    /<section class="page simple-page" id="settingsPage">/,
    `<section class="page simple-page" id="settingsPage" v-if="activeTab === 'settings'">`
);
newTemplate = newTemplate.replace(
    /<section class="page simple-page" id="helpPage">/,
    `<section class="page simple-page" id="helpPage" v-if="activeTab === 'help'">`
);

// Fix navigation clicks
newTemplate = newTemplate.replace(/id="productsNav"/g, `id="productsNav" @click="activeTab = 'products'" :class="{ active: activeTab === 'products' }"`);
newTemplate = newTemplate.replace(/id="invoiceNav"/g, `id="invoiceNav" @click="activeTab = 'invoice'" :class="{ active: activeTab === 'invoice' }"`);
newTemplate = newTemplate.replace(/id="settingsBtn"/g, `id="settingsBtn" @click="activeTab = 'settings'" :class="{ active: activeTab === 'settings' }"`);
newTemplate = newTemplate.replace(/id="helpBtn"/g, `id="helpBtn" @click="activeTab = 'help'" :class="{ active: activeTab === 'help' }"`);

// Back button for Inventory sidebar
newTemplate = newTemplate.replace(
    /<div class="nav-label">Management<\/div>/,
    `<div class="nav-label">Management</div>
                <button class="nav-item" @click="$router.push('/')" style="margin-bottom: 10px;">
                    <span class="nav-icon">←</span>
                    <span>Dashboard</span>
                </button>`
);

// Add bindings to Add Product Button
newTemplate = newTemplate.replace(
    /<button class="primary-btn" id="addProductBtn">/,
    `<button class="primary-btn" id="addProductBtn" @click="openProductPanel">`
);

// Add dynamic stats
newTemplate = newTemplate.replace(
    /<div class="stat-value" id="totalProducts">[\s\S]*?<\/div>/,
    `<div class="stat-value" id="totalProducts">{{ totalProducts }}</div>`
);
// For active products, legacy HTML had no ID. Replace by finding the label.
newTemplate = newTemplate.replace(
    /<div class="stat-label">Active Products<\/div>\s*<div class="stat-value">[\s\S]*?<\/div>/,
    `<div class="stat-label">Active Products</div>\n                        <div class="stat-value">{{ activeProducts }}</div>`
);
// For low stock
newTemplate = newTemplate.replace(
    /<div class="stat-label">Low Stock<\/div>\s*<div class="stat-value red">[\s\S]*?<\/div>/,
    `<div class="stat-label">Low Stock</div>\n                        <div class="stat-value red">{{ lowStockProducts }}</div>`
);
// For out of stock
newTemplate = newTemplate.replace(
    /<div class="stat-label">Out of Stock<\/div>\s*<div class="stat-value red">[\s\S]*?<\/div>/,
    `<div class="stat-label">Out of Stock</div>\n                        <div class="stat-value red">{{ outOfStockProducts }}</div>`
);

// Bind search and filters
newTemplate = newTemplate.replace(
    /id="productSearch"/,
    `id="productSearch" v-model="searchQuery"`
);
newTemplate = newTemplate.replace(
    /id="categoryFilter"/,
    `id="categoryFilter" v-model="categoryFilter"`
);
newTemplate = newTemplate.replace(
    /id="stockFilter"/,
    `id="stockFilter" v-model="stockFilter"`
);
newTemplate = newTemplate.replace(
    /id="globalSearch"/,
    `id="globalSearch" v-model="globalSearchQuery" @keyup.enter="handleGlobalSearch"`
);

// Bind the v-for table
const tableBodyRegex = /<tbody id="productTableBody">[\s\S]*?<\/tbody>/;
const dynamicTableBody = `<tbody id="productTableBody">
                                <tr v-for="product in filteredProducts" :key="product.sku">
                                    <td>
                                        <div class="product-cell">
                                            <div class="product-image">📦</div>
                                            <div>
                                                <div class="product-name">{{ product.name }}</div>
                                                <div class="product-sku">{{ product.sku }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ product.sku }}</td>
                                    <td>{{ product.category }}</td>
                                    <td>PKR {{ formatMoney(product.cost || product.purchasePrice) }}</td>
                                    <td>PKR {{ formatMoney(product.price || product.sellingPrice) }}</td>
                                    <td>{{ product.stock }}</td>
                                    <td>
                                        <span class="badge" :class="getStockBadge(product.stock)">{{ getStockText(product.stock) }}</span>
                                    </td>
                                    <td>
                                        <button class="action-btn">View</button>
                                    </td>
                                </tr>
                            </tbody>`;
newTemplate = newTemplate.replace(tableBodyRegex, dynamicTableBody);


// Inject Invoice Logic (from our previous step)
newTemplate = newTemplate.replace(
    /<div class="receipt-header">[\s\S]*?<\/div>/,
    `<div class="receipt-header">
                                    <div class="invoice-number" id="receiptInvoiceNumber">
                                        {{ invoiceData.number }}
                                    </div>
                                    <div class="invoice-date" id="receiptDate">
                                        {{ invoiceData.date }}
                                    </div>
                                </div>`
);

newTemplate = newTemplate.replace(
    /<td id="receiptProduct">\s*Product\s*<\/td>/,
    '<td id="receiptProduct">\n                                            {{ invoiceData.product }} <br> <small>{{ invoiceData.sku }}</small>\n                                        </td>'
);

newTemplate = newTemplate.replace(
    /<td id="receiptQty">\s*1\s*<\/td>/,
    '<td id="receiptQty">\n                                            {{ invoiceData.quantity }}\n                                        </td>'
);

newTemplate = newTemplate.replace(
    /<td id="receiptAmount">\s*0\s*<\/td>/,
    '<td id="receiptAmount">\n                                            PKR {{ invoiceData.total }}\n                                        </td>'
);

newTemplate = newTemplate.replace(
    /<span id="receiptPurchase">\s*PKR 0\s*<\/span>/,
    '<span id="receiptPurchase">\n                                        PKR {{ invoiceData.purchasePrice }}\n                                    </span>'
);

newTemplate = newTemplate.replace(
    /<span id="receiptQuantity">\s*1\s*<\/span>/,
    '<span id="receiptQuantity">\n                                        {{ invoiceData.quantity }}\n                                    </span>'
);

newTemplate = newTemplate.replace(
    /<span id="receiptTotal">\s*PKR 0\s*<\/span>/,
    '<span id="receiptTotal">\n                                        PKR {{ invoiceData.total }}\n                                    </span>'
);

newTemplate = newTemplate.replace(
    /<button class="black" id="newProductBtn">[\s\S]*?<\/button>/,
    `<button class="primary-btn" id="newProductBtn" @click="openProductPanelFromInvoice" style="margin-top: 20px; width: auto; padding: 10px 20px;">
                                ＋ Add New Product
                            </button>`
);

newTemplate = newTemplate.replace(
    /<div class="receipt-buttons">/,
    `<div class="receipt-buttons">

                            <button id="whatsappInvoice" @click="whatsappInvoice" style="background: #25d366; color: white; border-color: #25d366;">
                                <i class="bi bi-whatsapp"></i> WhatsApp
                            </button>`
);
newTemplate = newTemplate.replace(/id="receiptPrint"/, `id="receiptPrint" @click="printInvoice"`);
newTemplate = newTemplate.replace(/id="shareInvoice"/, `id="shareInvoice" @click="shareInvoice"`);
newTemplate = newTemplate.replace(/id="downloadInvoice"/, `id="downloadInvoice" @click="downloadInvoice"`);


// Bind Add Product Form
newTemplate = newTemplate.replace(/class="side-panel"/, `class="side-panel" :class="{ show: isPanelOpen }"`);
newTemplate = newTemplate.replace(/class="overlay"/, `class="overlay" :class="{ show: isPanelOpen }" @click="closeProductPanel"`);
newTemplate = newTemplate.replace(/class="close-btn"/, `class="close-btn" @click="closeProductPanel"`);
newTemplate = newTemplate.replace(/class="form"/, `class="form" @submit.prevent="saveProduct"`);

// Inputs in form
newTemplate = newTemplate.replace(/<input type="text" placeholder="e.g. Fresh Milk 1L" required \/>/, `<input type="text" v-model="newProduct.name" placeholder="e.g. Fresh Milk 1L" required />`);
newTemplate = newTemplate.replace(/<input type="text" placeholder="e.g. MILK-001" required \/>/, `<input type="text" v-model="newProduct.sku" placeholder="e.g. MILK-001" required />`);
newTemplate = newTemplate.replace(/<select required>/, `<select v-model="newProduct.category" required>`);
newTemplate = newTemplate.replace(/<input type="number" min="0" step="0.01" placeholder="0.00" required \/>/, `<input type="number" v-model="newProduct.purchasePrice" @input="calculateMargin" min="0" step="0.01" placeholder="0.00" required />`);
newTemplate = newTemplate.replace(/<input type="number" min="0" step="0.01" placeholder="0.00" required \/>/, `<input type="number" v-model="newProduct.sellingPrice" @input="calculateMargin" min="0" step="0.01" placeholder="0.00" required />`);
newTemplate = newTemplate.replace(/<input type="number" min="1" required \/>/, `<input type="number" v-model="newProduct.stock" min="1" required />`);
newTemplate = newTemplate.replace(/<select>/, `<select v-model="newProduct.unit">`);
newTemplate = newTemplate.replace(/<input type="number" min="0" \/>/, `<input type="number" v-model="newProduct.minStock" min="0" />`);
newTemplate = newTemplate.replace(/<textarea placeholder="Additional product details..."><\/textarea>/, `<textarea v-model="newProduct.notes" placeholder="Additional product details..."></textarea>`);

newTemplate = newTemplate.replace(/<span>22.2%<\/span>/, `<span>{{ marginDisplay }}%</span>`);
newTemplate = newTemplate.replace(/<button type="button" class="secondary-btn">Cancel<\/button>/, `<button type="button" class="secondary-btn" @click="closeProductPanel">Cancel</button>`);
newTemplate = newTemplate.replace(/<button type="submit" class="save-btn">✓ Save Product<\/button>/, `<button type="submit" class="save-btn">✓ Save Product</button>`);

// Now replace the <template> in vueTxt
const vueTemplateStart = vueTxt.indexOf('<template>') + 10;
const vueTemplateEnd = vueTxt.lastIndexOf('</template>');

vueTxt = vueTxt.substring(0, vueTemplateStart) + '\n' + newTemplate + '\n' + vueTxt.substring(vueTemplateEnd);

// Also need to ensure the CSS in InventryView.vue matches legacy exactly!
const legacyStyleStart = legacyTxt.indexOf('<style>');
const legacyStyleEnd = legacyTxt.indexOf('</style>', legacyStyleStart) + 8;
const legacyStyle = legacyTxt.substring(legacyStyleStart, legacyStyleEnd);

// Remove existing <style scoped> and replace with legacy style
const vueStyleStart = vueTxt.indexOf('<style scoped>');
if (vueStyleStart > -1) {
    vueTxt = vueTxt.substring(0, vueStyleStart) + legacyStyle.replace('<style>', '<style scoped>');
} else {
    vueTxt += '\n' + legacyStyle.replace('<style>', '<style scoped>');
}

fs.writeFileSync(vueFile, vueTxt);
console.log("InventryView.vue restructured to exactly match screenshot/legacy HTML");
