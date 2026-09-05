import re
import os

html_path = r'c:\xampp\htdocs\HBOS\scratch_inventry_html.html'
js_path = r'c:\xampp\htdocs\HBOS\scratch_inventry_js.js'
css_path = r'c:\xampp\htdocs\HBOS\src\views\InventryView.vue'

with open(html_path, 'r', encoding='utf-8') as f:
    html_content = f.read()

with open(js_path, 'r', encoding='utf-8') as f:
    js_content = f.read()

with open(css_path, 'r', encoding='utf-8') as f:
    css_content = f.read()

# Extract the CSS block
css_match = re.search(r'<style scoped>(.*?)</style>', css_content, re.DOTALL)
css = css_match.group(1) if css_match else ""

# Wait, in the legacy HTML, there's `<main class="main">` which contains the topbar, the overlay, the side-panel, and the pages.
main_match = re.search(r'<main class="main">(.*?)</main>', html_content, re.DOTALL)
if main_match:
    template_inner = main_match.group(1)
else:
    # try just the body content
    body_match = re.search(r'<body>(.*?)</body>', html_content, re.DOTALL)
    template_inner = body_match.group(1) if body_match else html_content

# Clean up template_inner for Vue
# 1. Remove the `<aside class="sidebar">` since it's in MainLayout
template_inner = re.sub(r'<!-- ==================================================\s*SIDEBAR.*?<!-- MAIN -->', '', template_inner, flags=re.DOTALL)
template_inner = re.sub(r'<aside class="sidebar".*?</aside>', '', template_inner, flags=re.DOTALL)
template_inner = re.sub(r'<main class="main"[^>]*>', '', template_inner)
template_inner = template_inner.replace('</main>', '')
template_inner = re.sub(r'<div class="app">', '', template_inner)
template_inner = template_inner.replace('</div>\n\n</body>', '')

# Remove topbar (we will use MainLayout's or a simplified one? Wait, user wants EXACT)
# Actually, the user wants the Inventry exact layout, so keep topbar.
# Replace class="show" logic with v-if="activeTab === 'products'" etc.
template_inner = template_inner.replace('id="productsPage"', 'id="productsPage" v-if="activeTab === \'products\'"')
template_inner = template_inner.replace('id="invoicePage"', 'id="invoicePage" v-if="activeTab === \'invoice\'"')
template_inner = template_inner.replace('id="settingsPage"', 'id="settingsPage" v-if="activeTab === \'settings\'"')
template_inner = template_inner.replace('id="helpPage"', 'id="helpPage" v-if="activeTab === \'help\'"')
template_inner = template_inner.replace('class="page products-page hide"', 'class="page products-page"')
template_inner = template_inner.replace('class="simple-page show"', 'class="simple-page"')

# Add product form bindings
template_inner = template_inner.replace('id="overlay"', 'id="overlay" :class="{ show: isPanelOpen }" @click="closeProductPanel"')
template_inner = template_inner.replace('id="productPanel"', 'id="productPanel" :class="{ show: isPanelOpen }"')

template_inner = template_inner.replace('id="productSearch"', 'id="productSearch" v-model="searchQuery" @input="filterProducts"')
template_inner = template_inner.replace('id="categoryFilter"', 'id="categoryFilter" v-model="categoryFilter" @change="filterProducts"')
template_inner = template_inner.replace('id="stockFilter"', 'id="stockFilter" v-model="stockFilter" @change="filterProducts"')
template_inner = template_inner.replace('id="globalSearch"', 'id="globalSearch" v-model="globalSearchQuery" @input="handleGlobalSearch"')

template_inner = template_inner.replace('id="purchasePrice"', 'id="purchasePrice" v-model.number="newProduct.purchasePrice" @input="calculateMargin"')
template_inner = template_inner.replace('id="sellingPrice"', 'id="sellingPrice" v-model.number="newProduct.sellingPrice" @input="calculateMargin"')
template_inner = template_inner.replace('id="marginValue"', 'id="marginValue"')
template_inner = template_inner.replace('id="productName"', 'id="productName" v-model="newProduct.name"')
template_inner = template_inner.replace('id="productSKU"', 'id="productSKU" v-model="newProduct.sku"')
template_inner = template_inner.replace('id="productCategory"', 'id="productCategory" v-model="newProduct.category"')
template_inner = template_inner.replace('id="initialStock"', 'id="initialStock" v-model.number="newProduct.stock"')
template_inner = template_inner.replace('id="productUnit"', 'id="productUnit" v-model="newProduct.unit"')
template_inner = template_inner.replace('id="minStock"', 'id="minStock" v-model.number="newProduct.minStock"')
template_inner = template_inner.replace('id="productNotes"', 'id="productNotes" v-model="newProduct.notes"')

template_inner = template_inner.replace('id="addProductBtn"', 'id="addProductBtn" @click="openProductPanel"')
template_inner = template_inner.replace('id="closePanel"', 'id="closePanel" @click="closeProductPanel"')
template_inner = template_inner.replace('id="cancelPanel"', 'id="cancelPanel" @click="closeProductPanel"')
template_inner = template_inner.replace('id="productForm"', 'id="productForm" @submit.prevent="saveProduct"')
template_inner = template_inner.replace('id="saveProduct"', 'id="saveProduct" type="submit"')
template_inner = template_inner.replace('id="newProductBtn"', 'id="newProductBtn" @click="openProductPanelFromInvoice"')
template_inner = template_inner.replace('id="backProducts"', 'id="backProducts" @click="showProducts"')
template_inner = template_inner.replace('id="printInvoiceBtn"', 'id="printInvoiceBtn" @click="printInvoice"')
template_inner = template_inner.replace('id="receiptPrint"', 'id="receiptPrint" @click="printInvoice"')
template_inner = template_inner.replace('id="shareInvoice"', 'id="shareInvoice" @click="shareInvoice"')
template_inner = template_inner.replace('id="downloadInvoice"', 'id="downloadInvoice" @click="downloadInvoice"')


# We must generate the script setup manually since it's much simpler in Vue
script_setup = """
<script setup>
import { ref, computed } from 'vue'

const activeTab = ref('products')
const isPanelOpen = ref(false)

const searchQuery = ref('')
const categoryFilter = ref('')
const stockFilter = ref('')
const globalSearchQuery = ref('')

const marginDisplay = ref('0.00')

const newProduct = ref({
    name: '',
    sku: '',
    category: 'Grocery',
    purchasePrice: 0,
    sellingPrice: 0,
    stock: 0,
    unit: 'Piece',
    minStock: 10,
    notes: ''
})

const invoiceData = ref({
    number: '',
    date: '',
    product: '',
    sku: '',
    category: '',
    quantity: 0,
    total: 0,
    purchasePrice: 0
})

const products = ref([
    {
        name: "Whole Wheat Bread",
        sku: "BAK-1002",
        category: "Bakery",
        purchasePrice: 120.00,
        sellingPrice: 150.00,
        stock: 45
    },
    {
        name: "Fresh Milk 1L",
        sku: "DAI-2045",
        category: "Dairy",
        purchasePrice: 200.00,
        sellingPrice: 230.00,
        stock: 12
    },
    {
        name: "Orange Juice 500ml",
        sku: "BEV-3091",
        category: "Beverages",
        purchasePrice: 80.00,
        sellingPrice: 110.00,
        stock: 0
    },
    {
        name: "Premium Rice 5kg",
        sku: "GRO-4011",
        category: "Grocery",
        purchasePrice: 1400.00,
        sellingPrice: 1650.00,
        stock: 120
    }
])

const showProducts = () => {
    activeTab.value = 'products'
}

const showInvoice = () => {
    activeTab.value = 'invoice'
}

const openProductPanel = () => {
    isPanelOpen.value = true
}

const closeProductPanel = () => {
    isPanelOpen.value = false
}

const openProductPanelFromInvoice = () => {
    activeTab.value = 'products'
    isPanelOpen.value = true
}

const calculateMargin = () => {
    const p = newProduct.value.purchasePrice || 0
    const s = newProduct.value.sellingPrice || 0
    if (p > 0 && s > 0) {
        const margin = ((s - p) / s) * 100
        marginDisplay.value = margin.toFixed(2)
    } else {
        marginDisplay.value = '0.00'
    }
}

const saveProduct = () => {
    if (!newProduct.value.name || !newProduct.value.sku || !newProduct.value.sellingPrice) {
        toastMsg.value = 'Please fill all required fields.'
        showToast()
        return
    }

    // Add product
    products.value.unshift({
        name: newProduct.value.name,
        sku: newProduct.value.sku,
        category: newProduct.value.category,
        purchasePrice: newProduct.value.purchasePrice,
        sellingPrice: newProduct.value.sellingPrice,
        stock: newProduct.value.stock
    })

    // Prepare invoice
    const invNum = "INV-" + Math.floor(100000 + Math.random() * 900000)
    const now = new Date()
    const dStr = now.toLocaleDateString("en-US", { year: 'numeric', month: 'short', day: 'numeric' })
    const tStr = now.toLocaleTimeString("en-US", { hour: '2-digit', minute: '2-digit' })

    invoiceData.value = {
        number: invNum,
        date: `${dStr} ${tStr}`,
        product: newProduct.value.name,
        sku: newProduct.value.sku,
        category: newProduct.value.category,
        quantity: newProduct.value.stock,
        total: newProduct.value.stock * newProduct.value.sellingPrice,
        purchasePrice: newProduct.value.purchasePrice
    }

    closeProductPanel()
    showInvoice()
    toastMsg.value = 'Product added successfully!'
    showToast()
    
    // Reset form
    newProduct.value = {
        name: '', sku: '', category: 'Grocery', purchasePrice: 0, sellingPrice: 0, stock: 0, unit: 'Piece', minStock: 10, notes: ''
    }
    marginDisplay.value = '0.00'
}

const filteredProducts = computed(() => {
    return products.value.filter(p => {
        const matchesSearch = p.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || p.sku.toLowerCase().includes(searchQuery.value.toLowerCase())
        const matchesCat = categoryFilter.value ? p.category === categoryFilter.value : true
        
        let stockStatus = 'In Stock'
        if (p.stock === 0) stockStatus = 'Out of Stock'
        else if (p.stock <= 15) stockStatus = 'Low Stock' // Assuming 15 for demo
        
        const matchesStock = stockFilter.value ? stockStatus === stockFilter.value : true
        
        return matchesSearch && matchesCat && matchesStock
    })
})

const handleGlobalSearch = () => {
    searchQuery.value = globalSearchQuery.value
    showProducts()
}

const toastMsg = ref('')
const isToastOpen = ref(false)
const showToast = () => {
    isToastOpen.value = true
    setTimeout(() => {
        isToastOpen.value = false
    }, 2500)
}

const formatMoney = (val) => {
    return Number(val).toLocaleString("en-PK", { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const getStockBadge = (stock) => {
    if (stock === 0) return 'badge-red'
    if (stock <= 15) return 'badge-yellow'
    return 'badge-green'
}
const getStockText = (stock) => {
    if (stock === 0) return 'Out of Stock'
    if (stock <= 15) return 'Low Stock'
    return 'In Stock'
}

const printInvoice = () => {
    window.print()
}
const shareInvoice = async () => {
    const text = `HBOS Inventory Invoice\\nProduct: ${invoiceData.value.product}\\nTotal: Rs ${formatMoney(invoiceData.value.total)}`
    if (navigator.share) {
        try {
            await navigator.share({ title: "HBOS Invoice", text })
        } catch (e) {}
    } else {
        try {
            await navigator.clipboard.writeText(text)
            toastMsg.value = "Invoice details copied."
            showToast()
        } catch(e) {}
    }
}
const downloadInvoice = () => {
    const content = `HBOS - MANAGEMENT SYSTEM\\n==============================\\n\\nPRODUCT INVOICE\\n\\nInvoice: ${invoiceData.value.number}\\nProduct: ${invoiceData.value.product}\\nTotal: Rs ${formatMoney(invoiceData.value.total)}\\n\\nProduct successfully added to inventory.\\n`
    const blob = new Blob([content], { type: "text/plain" })
    const url = URL.createObjectURL(blob)
    const link = document.createElement("a")
    link.href = url
    link.download = "HBOS-Product-Invoice.txt"
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)
    toastMsg.value = "Invoice downloaded."
    showToast()
}
</script>
"""

# Now replace the static table rows in template_inner with a v-for
table_body_start = template_inner.find('<tbody id="productTableBody">')
if table_body_start != -1:
    table_body_end = template_inner.find('</tbody>', table_body_start)
    if table_body_end != -1:
        vfor_row = """
                                <tr v-for="product in filteredProducts" :key="product.sku">
                                    <td>
                                        <div class="prod-info">
                                            <div class="prod-img"></div>
                                            <strong>{{ product.name }}</strong>
                                        </div>
                                    </td>
                                    <td class="sku-cell">{{ product.sku }}</td>
                                    <td>{{ product.category }}</td>
                                    <td>Rs {{ formatMoney(product.purchasePrice) }}</td>
                                    <td>Rs {{ formatMoney(product.sellingPrice) }}</td>
                                    <td>
                                        <span class="stock-badge" :class="getStockBadge(product.stock)">
                                            {{ getStockText(product.stock) }} ({{ product.stock }})
                                        </span>
                                    </td>
                                    <td class="action-cell">
                                        <button class="action-btn">Edit</button>
                                        <button class="action-btn delete">Del</button>
                                    </td>
                                </tr>
"""
        template_inner = template_inner[:table_body_start + len('<tbody id="productTableBody">')] + vfor_row + template_inner[table_body_end:]

# Bind margin value
template_inner = template_inner.replace('>25.00%<', '>{{ marginDisplay }}%<')

# Bind invoice data
template_inner = template_inner.replace('id="invoiceNumber">INV-482914<', 'id="invoiceNumber">{{ invoiceData.number }}<')
template_inner = template_inner.replace('id="invoiceProduct">Premium Rice 5kg<', 'id="invoiceProduct">{{ invoiceData.product }}<')
template_inner = template_inner.replace('id="invoiceSKU">GRO-4011<', 'id="invoiceSKU">{{ invoiceData.sku }}<')
template_inner = template_inner.replace('id="invoiceCategory">Grocery<', 'id="invoiceCategory">{{ invoiceData.category }}<')
template_inner = template_inner.replace('id="invoiceQuantity">120 Pieces<', 'id="invoiceQuantity">{{ invoiceData.quantity }} Pieces<')
template_inner = template_inner.replace('id="invoiceTotal">198,000.00<', 'id="invoiceTotal">{{ formatMoney(invoiceData.total) }}<')

template_inner = template_inner.replace('id="receiptInvoice">INV-482914<', 'id="receiptInvoice">{{ invoiceData.number }}<')
template_inner = template_inner.replace('id="receiptDate">Aug 15, 2024 14:32 PM<', 'id="receiptDate">{{ invoiceData.date }}<')
template_inner = template_inner.replace('id="receiptProduct">Premium Rice 5kg<', 'id="receiptProduct">{{ invoiceData.product }}<')
template_inner = template_inner.replace('id="receiptQty">120<', 'id="receiptQty">{{ invoiceData.quantity }}<')
template_inner = template_inner.replace('id="receiptAmount">1,650.00<', 'id="receiptAmount">{{ formatMoney(invoiceData.sellingPrice || (invoiceData.total / (invoiceData.quantity || 1))) }}<')
template_inner = template_inner.replace('id="receiptSubtotal">198,000.00<', 'id="receiptSubtotal">{{ formatMoney(invoiceData.total) }}<')
template_inner = template_inner.replace('id="receiptGrand">198,000.00<', 'id="receiptGrand">{{ formatMoney(invoiceData.total) }}<')

# Add toast
template_inner = template_inner.replace('<div class="toast" id="toast">Product added successfully!</div>', '<div class="toast" :class="{ show: isToastOpen }">{{ toastMsg }}</div>')


vue_file = f"""
{script_setup}

<template>
<div class="inventry-page-wrapper">
{template_inner}
</div>
</template>

<style scoped>
.inventry-page-wrapper {{
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  width: 100%;
}}

{css}

.page {{
  overflow-x: hidden;
}}

/* Print specific rules for invoice */
@media print {{
    body * {{
        visibility: hidden;
    }}
    .receipt-card, .receipt-card * {{
        visibility: visible;
    }}
    .receipt-card {{
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }}
}}
</style>
"""

with open(r'c:\xampp\htdocs\HBOS\src\views\InventryView.vue', 'w', encoding='utf-8') as f:
    f.write(vue_file)

print('Generated InventryView.vue')
