const fs = require('fs');

const html_path = 'c:\\\\xampp\\\\htdocs\\\\HBOS\\\\scratch_inventry_html.html';
const css_path = 'c:\\\\xampp\\\\htdocs\\\\HBOS\\\\src\\\\views\\\\InventryView.vue';
const script_path = 'c:\\\\xampp\\\\htdocs\\\\HBOS\\\\snippet_script.txt';

let html_content = fs.readFileSync(html_path, 'utf8');
let css_content = fs.readFileSync(css_path, 'utf8');
let script_setup = fs.readFileSync(script_path, 'utf8');

const css_match = css_content.match(/<style scoped>([\s\S]*?)<\/style>/);
const css = css_match ? css_match[1] : '';

let template_inner = html_content;
const main_match = html_content.match(/<main class="main">([\s\S]*?)<\/main>/);
if (main_match) {
    template_inner = main_match[1];
} else {
    const body_match = html_content.match(/<body>([\s\S]*?)<\/body>/);
    if (body_match) template_inner = body_match[1];
}

template_inner = template_inner.replace(/<!-- ==================================================\s*SIDEBAR[\s\S]*?<!-- MAIN -->/, '');
template_inner = template_inner.replace(/<aside class="sidebar"[\s\S]*?<\/aside>/, '');
template_inner = template_inner.replace(/<main class="main"[^>]*>/, '');
template_inner = template_inner.replace('</main>', '');
template_inner = template_inner.replace('<div class="app">', '');
template_inner = template_inner.replace('</div>\n\n</body>', '');

template_inner = template_inner.replace('id="productsPage"', 'id="productsPage" v-if="activeTab === \'products\'"');
template_inner = template_inner.replace('id="invoicePage"', 'id="invoicePage" v-if="activeTab === \'invoice\'"');
template_inner = template_inner.replace('id="settingsPage"', 'id="settingsPage" v-if="activeTab === \'settings\'"');
template_inner = template_inner.replace('id="helpPage"', 'id="helpPage" v-if="activeTab === \'help\'"');
template_inner = template_inner.replace('class="page products-page hide"', 'class="page products-page"');
template_inner = template_inner.replace('class="simple-page show"', 'class="simple-page"');

template_inner = template_inner.replace('id="overlay"', 'id="overlay" :class="{ show: isPanelOpen }" @click="closeProductPanel"');
template_inner = template_inner.replace('id="productPanel"', 'id="productPanel" :class="{ show: isPanelOpen }"');

template_inner = template_inner.replace('id="productSearch"', 'id="productSearch" v-model="searchQuery" @input="filterProducts"');
template_inner = template_inner.replace('id="categoryFilter"', 'id="categoryFilter" v-model="categoryFilter" @change="filterProducts"');
template_inner = template_inner.replace('id="stockFilter"', 'id="stockFilter" v-model="stockFilter" @change="filterProducts"');
template_inner = template_inner.replace('id="globalSearch"', 'id="globalSearch" v-model="globalSearchQuery" @input="handleGlobalSearch"');

template_inner = template_inner.replace('id="purchasePrice"', 'id="purchasePrice" v-model.number="newProduct.purchasePrice" @input="calculateMargin"');
template_inner = template_inner.replace('id="sellingPrice"', 'id="sellingPrice" v-model.number="newProduct.sellingPrice" @input="calculateMargin"');
template_inner = template_inner.replace('id="marginValue"', 'id="marginValue"');
template_inner = template_inner.replace('id="productName"', 'id="productName" v-model="newProduct.name"');
template_inner = template_inner.replace('id="productSKU"', 'id="productSKU" v-model="newProduct.sku"');
template_inner = template_inner.replace('id="productCategory"', 'id="productCategory" v-model="newProduct.category"');
template_inner = template_inner.replace('id="initialStock"', 'id="initialStock" v-model.number="newProduct.stock"');
template_inner = template_inner.replace('id="productUnit"', 'id="productUnit" v-model="newProduct.unit"');
template_inner = template_inner.replace('id="minStock"', 'id="minStock" v-model.number="newProduct.minStock"');
template_inner = template_inner.replace('id="productNotes"', 'id="productNotes" v-model="newProduct.notes"');

template_inner = template_inner.replace('id="addProductBtn"', 'id="addProductBtn" @click="openProductPanel"');
template_inner = template_inner.replace('id="closePanel"', 'id="closePanel" @click="closeProductPanel"');
template_inner = template_inner.replace('id="cancelPanel"', 'id="cancelPanel" @click="closeProductPanel"');
template_inner = template_inner.replace('id="productForm"', 'id="productForm" @submit.prevent="saveProduct"');
template_inner = template_inner.replace('id="saveProduct"', 'id="saveProduct" type="submit"');
template_inner = template_inner.replace('id="newProductBtn"', 'id="newProductBtn" @click="openProductPanelFromInvoice"');
template_inner = template_inner.replace('id="backProducts"', 'id="backProducts" @click="showProducts"');
template_inner = template_inner.replace('id="printInvoiceBtn"', 'id="printInvoiceBtn" @click="printInvoice"');
template_inner = template_inner.replace('id="receiptPrint"', 'id="receiptPrint" @click="printInvoice"');
template_inner = template_inner.replace('id="shareInvoice"', 'id="shareInvoice" @click="shareInvoice"');
template_inner = template_inner.replace('id="downloadInvoice"', 'id="downloadInvoice" @click="downloadInvoice"');

const table_body_start = template_inner.indexOf('<tbody id="productTableBody">');
if (table_body_start !== -1) {
    const table_body_end = template_inner.indexOf('</tbody>', table_body_start);
    if (table_body_end !== -1) {
        const vfor_row = `
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
                                    <td>PKR {{ formatMoney(product.purchasePrice) }}</td>
                                    <td>PKR {{ formatMoney(product.sellingPrice) }}</td>
                                    <td>{{ product.stock }}</td>
                                    <td>
                                        <span class="badge" :class="getStockBadge(product.stock)">{{ getStockText(product.stock) }}</span>
                                    </td>
                                    <td>
                                        <button class="action-btn">View</button>
                                    </td>
                                </tr>
`;
        template_inner = template_inner.substring(0, table_body_start + '<tbody id="productTableBody">'.length) + vfor_row + template_inner.substring(table_body_end);
    }
}

template_inner = template_inner.replace(/>25\.00%</, '>{{ marginDisplay }}%<');
template_inner = template_inner.replace(/id="invoiceNumber">INV-482914</, 'id="invoiceNumber">{{ invoiceData.number }}<');
template_inner = template_inner.replace(/id="invoiceProduct">Premium Rice 5kg</, 'id="invoiceProduct">{{ invoiceData.product }}<');
template_inner = template_inner.replace(/id="invoiceSKU">GRO-4011</, 'id="invoiceSKU">{{ invoiceData.sku }}<');
template_inner = template_inner.replace(/id="invoiceCategory">Grocery</, 'id="invoiceCategory">{{ invoiceData.category }}<');
template_inner = template_inner.replace(/id="invoiceQuantity">120 Pieces</, 'id="invoiceQuantity">{{ invoiceData.quantity }} Pieces<');
template_inner = template_inner.replace(/id="invoiceTotal">198,000\.00</, 'id="invoiceTotal">{{ formatMoney(invoiceData.total) }}<');

template_inner = template_inner.replace(/id="receiptInvoice">INV-482914</, 'id="receiptInvoice">{{ invoiceData.number }}<');
template_inner = template_inner.replace(/id="receiptDate">Aug 15, 2024 14:32 PM</, 'id="receiptDate">{{ invoiceData.date }}<');
template_inner = template_inner.replace(/id="receiptProduct">Premium Rice 5kg</, 'id="receiptProduct">{{ invoiceData.product }}<');
template_inner = template_inner.replace(/id="receiptQty">120</, 'id="receiptQty">{{ invoiceData.quantity }}<');
template_inner = template_inner.replace(/id="receiptAmount">1,650\.00</, 'id="receiptAmount">{{ formatMoney(invoiceData.sellingPrice || (invoiceData.total / (invoiceData.quantity || 1))) }}<');
template_inner = template_inner.replace(/id="receiptSubtotal">198,000\.00</, 'id="receiptSubtotal">{{ formatMoney(invoiceData.total) }}<');
template_inner = template_inner.replace(/id="receiptGrand">198,000\.00</, 'id="receiptGrand">{{ formatMoney(invoiceData.total) }}<');

template_inner = template_inner.replace('<div class="toast" id="toast">Product added successfully!</div>', '<div class="toast" :class="{ show: isToastOpen }">{{ toastMsg }}</div>');

const vue_file = script_setup + `

<template>
<div class="inventry-page-wrapper">
${template_inner}
</div>
</template>

<style scoped>
.inventry-page-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  width: 100%;
}

${css}

.page {
  overflow-x: hidden;
}

@media print {
    body * { visibility: hidden; }
    .receipt-card, .receipt-card * { visibility: visible; }
    .receipt-card { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>
`;

fs.writeFileSync(css_path, vue_file);
console.log('Generated InventryView.vue');
