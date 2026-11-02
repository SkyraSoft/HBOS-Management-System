<template>
<div class="purchase-page-wrapper">


            <!-- TOPBAR -->

            


            <!-- =====================================================
           PURCHASE LIST VIEW
      ====================================================== -->

            <section class="content purchase-view" id="purchaseListView" v-show="!isNewPurchase">

                <div class="page-header">

                    <div>

                        <h1 class="page-title">
                            Purchases
                        </h1>

                        <p class="page-description">
                            Manage purchases, supplier invoices, stock receiving, and supplier payments.
                        </p>

                    </div>

                    <div class="header-actions">

                        <button class="btn" @click="goToSupplier">
                            + Add Supplier
                        </button>

                        <button class="btn btn-black" @click="showNewPurchase">
                            + New Purchase
                        </button>

                    </div>

                </div>


                <!-- STATS -->

                <div class="stats">

                    <div class="stat-card">

                        <div class="stat-label">
                            Today's Purchases
                        </div>

                        <div class="stat-value">
                            PKR {{ todayPurchasesTotal.toLocaleString('en-PK') }}
                        </div>

                    </div>


                    <div class="stat-card">

                        <div class="stat-label">
                            This Month
                        </div>

                        <div class="stat-value">
                            PKR {{ thisMonthPurchasesTotal.toLocaleString('en-PK') }}
                        </div>

                    </div>


                    <div class="stat-card">

                        <div class="stat-label">
                            Pending Purchases
                        </div>

                        <div class="stat-value">
                            {{ pendingPurchasesCount }}
                        </div>

                    </div>


                    <div class="stat-card">

                        <div class="stat-label">
                            Supplier Payables
                        </div>

                        <div class="stat-value danger">
                            PKR {{ supplierPayablesTotal.toLocaleString('en-PK') }}
                        </div>

                    </div>

                </div>


                <!-- TABLE -->

                <div class="table-card">

                    <div class="filters">

                        <div class="search-box">
                            <span>⌕</span>
                            <input type="text" id="purchaseSearch" placeholder="Search invoices, suppliers..." v-model="purchaseSearchQuery">
                        </div>

                        <select class="filter-select" id="dateFilter" v-model="filterDateRange">
                            <option value="all">Date: All Time</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                        </select>

                        <select class="filter-select" id="supplierFilter" v-model="filterSupplierId">
                            <option value="">Supplier: All</option>
                            <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>

                        <select class="filter-select" id="paymentStatusFilter" v-model="filterPaymentStatus">
                            <option value="">Payment: All</option>
                            <option value="Paid">Paid</option>
                            <option value="Partial">Partial</option>
                            <option value="Unpaid">Unpaid</option>
                        </select>

                    </div>


                    <div class="table-wrap">

                        <table>

                            <thead>

                                <tr>
                                    <th>Purchase</th>
                                    <th>Supplier</th>
                                    <th>Items</th>
                                    <th>Total (PKR)</th>
                                    <th>Paid (PKR)</th>
                                    <th>Outstanding (PKR)</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>

                            </thead>


                            <tbody id="purchaseTable">
                                <tr v-for="purchase in paginatedPurchases" :key="purchase.id">
                                    <td>
                                        <div class="purchase-id">
                                            {{ purchase.po_number }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="supplier">
                                            {{ purchase.supplier ? purchase.supplier.name : (purchase.notes || 'Unknown') }}
                                        </div>
                                    </td>

                                    <td>{{ purchase.items ? purchase.items.length : 0 }}</td>

                                    <td class="amount">
                                        {{ formatNumber(purchase.total) }}
                                    </td>

                                    <td class="amount">
                                        {{ formatNumber(purchase.paid_amount ?? purchase.paid ?? 0) }}
                                    </td>

                                    <td class="amount">
                                        {{ formatNumber(purchase.due_amount ?? (Number(purchase.total || purchase.total_amount || 0) - Number(purchase.paid_amount ?? purchase.paid ?? 0))) }}
                                    </td>

                                    <td>
                                        <span :class="['status', getPurchaseStatusClass(purchase)]">
                                            {{ getPurchaseStatusLabel(purchase) }}
                                        </span>
                                    </td>

                                    <td>
                                        {{ purchase.date ? new Date(purchase.date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : 'N/A' }}
                                    </td>

                                    <td>
                                        <button class="action-btn">
                                            ◉
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="filteredPurchases.length === 0">
                                    <td colspan="9" style="text-align: center; padding: 25px; color: var(--muted);">
                                        No purchases found. {{ (purchases && purchases.length > 0) ? 'Try clearing your search/filter.' : 'Click "+ New Purchase" to create one.' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="table-footer">
                        <div>
                            <span v-if="filteredPurchases.length === 0">Showing 0 entries</span>
                            <span v-else>Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, filteredPurchases.length) }} of {{ filteredPurchases.length }} entries</span>
                        </div>

                        <div class="pagination" v-if="totalPages > 1">
                            <button class="page-btn" :disabled="currentPage <= 1" @click="currentPage--">‹</button>
                            <button v-for="pg in totalPages" :key="pg" class="page-btn" :class="{ active: pg === currentPage }" @click="currentPage = pg">{{ pg }}</button>
                            <button class="page-btn" :disabled="currentPage >= totalPages" @click="currentPage++">›</button>
                        </div>
                    </div>

                </div>

            </section>


            <!-- =====================================================
           NEW PURCHASE VIEW
      ====================================================== -->

            <section class="content purchase-view" id="newPurchaseView" v-show="isNewPurchase">

                <div class="purchase-header" v-if="!isModal">

                    <div class="back-title">

                        <button class="back-btn" @click="showPurchaseList" aria-label="Back to purchases">
                            ←
                        </button>

                        <div>

                            <h1 class="page-title">
                                New Purchase
                            </h1>

                            <p class="page-description">
                                Record incoming stock from suppliers.
                            </p>

                        </div>

                    </div>


                    <div class="header-actions">

                        <button class="btn" @click="showPurchaseList">
                            Cancel
                        </button>

                    </div>

                </div>


                <div class="purchase-layout">


                    <!-- =================================================
               LEFT
          ================================================== -->

                    <div class="purchase-left">


                        <!-- PURCHASE DETAILS -->

                        <div class="card">

                            <h2 class="card-title">
                                Purchase Details
                            </h2>

                            <div class="form-grid">


                                <div class="form-group">

                                    <label>
                                        Supplier <span class="required">*</span>
                                    </label>

                                    <div class="searchable-dropdown" style="position: relative;">
                                        <input 
                                            type="text" 
                                            class="select" 
                                            v-model="supplierSearchQuery" 
                                            @focus="showSupplierDropdown = true" 
                                            @blur="hideSupplierDropdown"
                                            placeholder="Select a supplier..."
                                            style="width: 100%;"
                                        >
                                        <div 
                                            v-if="showSupplierDropdown" 
                                            class="dropdown-list" 
                                            style="position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid var(--border); border-radius: 8px; max-height: 200px; overflow-y: auto; z-index: 10; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-top: 5px;"
                                        >
                                            <div 
                                                v-for="s in filteredSuppliers" 
                                                :key="s.id" 
                                                @mousedown="selectSupplier(s)"
                                                style="padding: 10px 15px; cursor: pointer; border-bottom: 1px solid var(--border-light);"
                                                onmouseover="this.style.background='#f0f3fa'"
                                                onmouseout="this.style.background='transparent'"
                                            >
                                                {{ s.name }}
                                            </div>
                                            <div v-if="filteredSuppliers.length === 0" style="padding: 10px 15px; color: var(--muted);">
                                                No suppliers found
                                            </div>
                                        </div>
                                    </div>

                                </div>


                                <div class="form-group">

                                    <label>
                                        Purchase Date
                                    </label>

                                    <input class="input" type="date" id="purchaseDate">

                                </div>


                                <div class="form-group full">

                                    <label>
                                        Invoice / Reference No.
                                    </label>

                                    <input class="input" type="text" v-model="invoiceNumber" placeholder="e.g. INV-2026-991">

                                </div>

                            </div>

                        </div>


                        <!-- PRODUCTS -->

                        <div class="card">

                            <div class="products-heading">

                                <h2 class="card-title" style="margin:0;">
                                    Products
                                </h2>

                                <div style="position: relative; width: 250px;">
    <input class="product-search" type="search" placeholder="Search products..." v-model="productSearchQuery" @focus="showProductDropdown = true" @blur="setTimeout(() => showProductDropdown = false, 200)" style="width: 100%;">
    <ul v-if="showProductDropdown && filteredProducts.length > 0" class="dropdown-menu show" style="position: absolute; top: 100%; left: 0; width: 100%; z-index: 1000; max-height: 200px; overflow-y: auto;">
        <li v-for="prod in filteredProducts" :key="prod.id" style="cursor: pointer;">
            <a class="dropdown-item py-2" @mousedown.prevent="selectProduct(prod)">
                <div class="fw-bold">{{ prod.name }}</div>
                <small class="text-secondary">{{ prod.sku }} | PKR {{ prod.cost || prod.price || 0 }}</small>
            </a>
        </li>
    </ul>
</div>

                            </div>


                            <!-- HEADER -->

                            <div class="product-row header">

                                <div>Product</div>
                                <div>Qty</div>
                                <div>Unit</div>
                                <div>Price</div>
                                <div>Disc %</div>
                                <div style="text-align: right;">Landed Cost</div>
                                <div>Total</div>
                                <div></div>

                            </div>


                            <!-- EMPTY STATE -->
                            <div v-if="purchaseItems.length === 0" style="text-align: center; padding: 36px 16px; color: #64748b; font-size: 14px; background: #fafafa; border-radius: 8px; margin: 12px 0;">
                                <i class="bi bi-cart-x" style="font-size: 28px; display: block; margin-bottom: 8px; color: #94a3b8;"></i>
                                <span class="fw-semibold text-dark">No products added yet</span>
                                <p class="text-muted small m-0 mt-1">Search and select products above to build your purchase order.</p>
                            </div>

                            <!-- REACTIVE PRODUCTS -->
                            <div class="product-row purchase-product" v-for="(item, index) in purchaseItems" :key="index">
                                <div>
                                    <input v-model="item.name" style="border: none; background: transparent; width: 100%; font-weight: 600; font-size: 14px; outline: none; margin-bottom: 4px;" placeholder="Item Name" />
                                    <input v-model="item.sku" style="border: none; background: transparent; width: 100%; font-size: 12px; color: #8893a7; outline: none;" placeholder="SKU" />
                                </div>
                                <div>
                                    <input class="mini-input qty" type="number" v-model.number="item.quantity" min="0.001" step="0.001">
                                </div>
                                <div>
                                    <input class="mini-input" style="width: 50px;" v-model="item.unit">
                                </div>
                                <div>
                                    <input class="mini-input price" type="number" v-model.number="item.price" min="0" step="0.01">
                                    <!-- Supplier Price History Tooltip & Trend Badge (SRT-GAP-016) -->
                                    <div v-if="item.product_id && supplierPriceHistory[item.product_id]" style="margin-top: 4px; font-size: 10px;">
                                        <template v-if="supplierPriceHistory[item.product_id].found">
                                            <span 
                                                v-if="item.price > supplierPriceHistory[item.product_id].last_price" 
                                                class="price-trend-badge warning"
                                                style="color: #b45309; background: #fef3c7; padding: 2px 4px; border-radius: 4px; display: inline-flex; align-items: center; gap: 2px; cursor: help; font-weight: 600; font-size: 9px;"
                                                :title="`Last price from supplier: PKR ${supplierPriceHistory[item.product_id].last_price} on ${supplierPriceHistory[item.product_id].last_date || 'N/A'} (PO #${supplierPriceHistory[item.product_id].po_number || 'N/A'}) | Prior Trend: ${supplierPriceHistory[item.product_id].trend_percentage > 0 ? '+' : ''}${supplierPriceHistory[item.product_id].trend_percentage}%`"
                                            >
                                                ▲ +{{ (((item.price - supplierPriceHistory[item.product_id].last_price) / supplierPriceHistory[item.product_id].last_price) * 100).toFixed(1) }}% vs Last ({{ supplierPriceHistory[item.product_id].last_price }})
                                            </span>
                                            <span 
                                                v-else 
                                                class="price-trend-badge normal"
                                                style="color: #047857; background: #d1fae5; padding: 2px 4px; border-radius: 4px; display: inline-flex; align-items: center; gap: 2px; cursor: help; font-weight: 500; font-size: 9px;"
                                                :title="`Last price from supplier: PKR ${supplierPriceHistory[item.product_id].last_price} on ${supplierPriceHistory[item.product_id].last_date || 'N/A'} (PO #${supplierPriceHistory[item.product_id].po_number || 'N/A'}) | Prior Trend: ${supplierPriceHistory[item.product_id].trend_percentage > 0 ? '+' : ''}${supplierPriceHistory[item.product_id].trend_percentage}%`"
                                            >
                                                ● Last: PKR {{ supplierPriceHistory[item.product_id].last_price }}
                                            </span>
                                        </template>
                                        <template v-else>
                                            <span style="color: #9ca3af; font-size: 9px;" title="No historical purchases found from this supplier">New supplier price</span>
                                        </template>
                                    </div>
                                </div>
                                <div>
                                    <input class="mini-input discount" type="number" v-model.number="item.discount" min="0" max="100">
                                </div>
                                <!-- Landed Cost Display (SRT-GAP-015) -->
                                <div class="landed-cost-col" style="font-size: 12px; color: #4338ca; text-align: right;">
                                    <div style="font-weight: 700;">PKR {{ formatNumber(allocatedLandedCosts[index]?.landedUnitCost || item.price) }}</div>
                                    <div v-if="shippingCost > 0" style="font-size: 10px; color: #6366f1;">
                                        +PKR {{ formatNumber(allocatedLandedCosts[index]?.allocatedFreight || 0) }} frt
                                    </div>
                                </div>
                                <div class="row-total">
                                    <span class="line-total">{{ formatNumber((item.quantity * item.price) * (1 - (item.discount / 100))) }}</span>
                                </div>
                                <button class="delete-btn" @click="removePurchaseItem(index)" title="Remove product">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>

                            <button class="add-item" @click="addCustomItem">
                                + Add Custom Item
                            </button>

                            <span class="items-count">
                                {{ purchaseItems.length }} items
                            </span>

                        </div>

                    </div>


                    <!-- =================================================
               RIGHT SUMMARY
          ================================================== -->

                    <aside class="card summary-card">

                        <h2 class="card-title">
                            Summary
                        </h2>


                        <div class="summary-line">
                            <span>Subtotal</span>
                            <span id="subtotal">{{ formatNumber(subtotal) }}</span>
                        </div>


                        <div class="summary-line">
                            <span>Item Discounts</span>
                            <span id="discountTotal">-{{ formatNumber(discountTotal) }}</span>
                        </div>


                        <div class="summary-line">
                            <span>
                                Shipping
                            </span>
                            <input class="mini-input" id="shipping" type="number" v-model.number="shippingCost" min="0" style="width:96px;">
                        </div>

                        <div class="summary-line" v-if="shippingCost > 0" style="font-size: 11px; color: #4338ca; background: #eef2ff; padding: 4px 8px; border-radius: 4px;">
                            <span>Freight Allocation:</span>
                            <span style="font-weight: 600;">Value-Proportional</span>
                        </div>

                        <div class="summary-total">
                            <span>
                                Grand Total
                            </span>
                            <span id="grandTotal">{{ formatNumber(grandTotal) }}</span>
                        </div>

                        <!-- PAYMENT -->
                        <div class="payment-section">
                            <h2 class="card-title">
                                Payment
                            </h2>

                            <label>
                                Payment Status
                            </label>
                            <div class="payment-options">
                                <button type="button" class="payment-option" :class="{ active: paymentStatus === 'paid' }" @click="setPaymentStatus('paid')">
                                    Paid in Full
                                </button>
                                <button type="button" class="payment-option" :class="{ active: paymentStatus === 'partial' }" @click="setPaymentStatus('partial')">
                                    Partial
                                </button>
                                <button type="button" class="payment-option" :class="{ active: paymentStatus === 'credit' }" @click="setPaymentStatus('credit')">
                                    Credit
                                </button>
                            </div>

                            <label>
                                Amount Paid
                            </label>
                            <input class="input" id="amountPaid" type="number" v-model.number="amountPaid" min="0">

                            <label style="margin-top:16px;">
                                Payment Method
                            </label>
                            <select class="select" id="paymentMethod" v-model="selectedPaymentMethod">
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Card">Card</option>
                                <option value="JazzCash">JazzCash</option>
                                <option value="Easypaisa">Easypaisa</option>
                            </select>


                            <button class="btn btn-black" @click="confirmPurchase" :disabled="isSubmitting">
                                <i class="fa-solid fa-check"></i> {{ isSubmitting ? 'Confirming...' : 'Confirm Purchase' }}
                            </button>


                            <button class="btn" @click="showPurchaseList">
                                Cancel
                            </button>

                        </div>

                    </aside>

                </div>

            </section>

        
</div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import api from '@/api';
import { usePurchasesStore } from '@/stores/purchases';
import { useSuppliersStore } from '@/stores/suppliers';
import { useRetailStore } from '@/stores/retail';

const formatNumber = (val) => Number(val).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const getPurchaseStatusLabel = (p) => {
    const total = Number(p.total ?? p.total_amount ?? 0);
    const paid = Number(p.paid_amount ?? p.paid ?? 0);
    const due = Number(p.due_amount ?? (total - paid));
    if (due <= 0 || paid >= total) return 'Paid';
    if (paid > 0 && due > 0) return 'Partial';
    return 'Unpaid';
};

const getPurchaseStatusClass = (p) => {
    const label = getPurchaseStatusLabel(p);
    if (label === 'Paid') return 'paid';
    if (label === 'Partial') return 'partial';
    return 'unpaid';
};

const router = useRouter();
const route = useRoute();
const props = defineProps({ isModal: { type: Boolean, default: false } });
const emit = defineEmits(['success']);

const store = usePurchasesStore();
const suppliersStore = useSuppliersStore();
const retailStore = useRetailStore();

const amountPaid = ref(0);
const paymentStatus = ref('paid');
const selectedPaymentMethod = ref('Cash');

const setPaymentStatus = (status) => {
    paymentStatus.value = status;
    if (status === 'paid') {
        amountPaid.value = grandTotal.value;
    } else if (status === 'credit') {
        amountPaid.value = 0;
    }
};

const purchases = computed(() => store.purchases);
const suppliers = computed(() => suppliersStore.suppliers);

const todayPurchasesTotal = computed(() => {
    const today = new Date().toISOString().split('T')[0];
    return (purchases.value || [])
        .filter(p => (p.purchase_date || p.date || p.created_at || '').startsWith(today))
        .reduce((sum, p) => sum + (Number(p.total_amount || p.total || p.grand_total) || 0), 0);
});

const thisMonthPurchasesTotal = computed(() => {
    const currentMonth = new Date().toISOString().slice(0, 7);
    return (purchases.value || [])
        .filter(p => (p.purchase_date || p.date || p.created_at || '').startsWith(currentMonth))
        .reduce((sum, p) => sum + (Number(p.total_amount || p.total || p.grand_total) || 0), 0);
});

const pendingPurchasesCount = computed(() => {
    return (purchases.value || []).filter(p => (p.status || '').toLowerCase() === 'pending' || (p.payment_status || '').toLowerCase() === 'pending').length;
});

const supplierPayablesTotal = computed(() => {
    return (suppliers.value || []).reduce((sum, s) => sum + (Number(s.balance || s.payable || s.current_balance) || 0), 0);
});

const filterSupplierId = ref('');
const filterPaymentStatus = ref('');
const filterDateRange = ref('all');
const purchaseSearchQuery = ref('');
const currentPage = ref(1);
const perPage = ref(10);

const filteredPurchases = computed(() => {
    let list = [...(purchases.value || [])];

    if (filterSupplierId.value) {
        list = list.filter(p => {
            const sId = p.supplier_id || p.supplier?.id;
            const sName = p.supplier?.name || p.notes || '';
            return String(sId) === String(filterSupplierId.value) || sName.toLowerCase().includes(filterSupplierId.value.toLowerCase());
        });
    }

    if (filterPaymentStatus.value) {
        list = list.filter(p => {
            const status = getPurchaseStatusLabel(p);
            return status.toLowerCase() === filterPaymentStatus.value.toLowerCase();
        });
    }

    if (purchaseSearchQuery.value.trim()) {
        const q = purchaseSearchQuery.value.trim().toLowerCase();
        list = list.filter(p => {
            const po = (p.po_number || '').toLowerCase();
            const suppName = (p.supplier?.name || p.notes || '').toLowerCase();
            const id = String(p.id || '');
            return po.includes(q) || suppName.includes(q) || id.includes(q);
        });
    }

    if (filterDateRange.value !== 'all') {
        const now = new Date();
        const todayStr = now.toISOString().split('T')[0];
        if (filterDateRange.value === 'today') {
            list = list.filter(p => (p.purchase_date || p.date || p.created_at || '').startsWith(todayStr));
        } else if (filterDateRange.value === 'month') {
            const monthStr = now.toISOString().slice(0, 7);
            list = list.filter(p => (p.purchase_date || p.date || p.created_at || '').startsWith(monthStr));
        }
    }

    return list;
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredPurchases.value.length / perPage.value)));

const paginatedPurchases = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredPurchases.value.slice(start, start + perPage.value);
});

watch([filterSupplierId, filterPaymentStatus, filterDateRange, purchaseSearchQuery], () => {
    currentPage.value = 1;
});

const isNewPurchase = ref(props.isModal ? true : route.query.new === 'true');

watch(() => route.query.new, (isNew) => {
    isNewPurchase.value = isNew === 'true';
});

const showNewPurchase = () => {
    isNewPurchase.value = true;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const showPurchaseList = () => {
    if (route.query.from === 'supplier') {
        router.push('/supplierpayable?tab=purchase-orders');
    } else {
        isNewPurchase.value = false;
        router.push('/purchase');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const goToSupplier = () => {
    router.push('/supplier');
};

const purchaseItems = ref([]);

const shippingCost = ref(0);
const invoiceNumber = ref('');
const isSubmitting = ref(false);

const supplierSearchQuery = ref('');
const selectedSupplierId = ref(null);
const showSupplierDropdown = ref(false);

const productSearchQuery = ref('');
const showProductDropdown = ref(false);

// Historical supplier price lookup cache (SRT-GAP-016)
const supplierPriceHistory = ref({});

const fetchSupplierPriceHistory = async (supplierId, productId) => {
    if (!supplierId || !productId) return;
    try {
        const res = await api.get(`/purchases?action=last-price&supplier_id=${supplierId}&product_id=${productId}`);
        if (res && res.data) {
            supplierPriceHistory.value = {
                ...supplierPriceHistory.value,
                [productId]: res.data
            };
        }
    } catch (e) {
        console.error('Failed to fetch supplier price history', e);
    }
};

const filteredSuppliers = computed(() => {
    if (!supplierSearchQuery.value) return suppliers.value;
    return suppliers.value.filter(s => 
        s.name.toLowerCase().includes(supplierSearchQuery.value.toLowerCase())
    );
});

const selectSupplier = (supplier) => {
    selectedSupplierId.value = supplier.id;
    supplierSearchQuery.value = supplier.name;
    showSupplierDropdown.value = false;
    
    // Refresh price history for items in cart with product_id
    purchaseItems.value.forEach(item => {
        if (item.product_id) {
            fetchSupplierPriceHistory(supplier.id, item.product_id);
        }
    });
};

const hideSupplierDropdown = () => {
    setTimeout(() => {
        showSupplierDropdown.value = false;
        const match = suppliers.value.find(s => s.id === selectedSupplierId.value);
        if (match) {
            supplierSearchQuery.value = match.name;
        } else {
            supplierSearchQuery.value = '';
            selectedSupplierId.value = null;
        }
    }, 200);
};

const filteredProducts = computed(() => {
    const list = retailStore.products || [];
    if (!productSearchQuery.value.trim()) return list.slice(0, 20);
    const q = productSearchQuery.value.toLowerCase().trim();
    return list.filter(p => 
        (p.name && p.name.toLowerCase().includes(q)) ||
        (p.sku && p.sku.toLowerCase().includes(q)) ||
        (p.barcode && p.barcode.toLowerCase().includes(q)) ||
        (p.alternateBarcodes && p.alternateBarcodes.some(b => String(b).toLowerCase().includes(q)))
    ).slice(0, 20);
});

const selectProduct = (prod) => {
    const defaultCost = Number(prod.costPrice || prod.cost || prod.purchase_price || prod.price || 0);
    const item = {
        product_id: prod.id,
        name: prod.name,
        sku: prod.sku || '',
        quantity: 1,
        unit: prod.saleUnit || prod.unit || 'pcs',
        price: defaultCost,
        discount: 0,
        allow_fractional: prod.allowFractional || prod.allow_fractional || false
    };
    purchaseItems.value.push(item);
    productSearchQuery.value = '';
    showProductDropdown.value = false;

    if (selectedSupplierId.value) {
        fetchSupplierPriceHistory(selectedSupplierId.value, prod.id);
    }
};

const addCustomItem = () => {
    purchaseItems.value.push({
        product_id: null,
        name: '',
        sku: '',
        quantity: 1,
        unit: 'pcs',
        price: 0,
        discount: 0,
        allow_fractional: true
    });
};

const removePurchaseItem = (index) => {
    purchaseItems.value.splice(index, 1);
};

const subtotal = computed(() => {
    return purchaseItems.value.reduce((sum, item) => sum + (Number(item.quantity || 0) * Number(item.price || 0)), 0);
});

const discountTotal = computed(() => {
    return purchaseItems.value.reduce((sum, item) => sum + ((Number(item.quantity || 0) * Number(item.price || 0)) * (Number(item.discount || 0) / 100)), 0);
});

const grandTotal = computed(() => {
    return Math.max(0, subtotal.value - discountTotal.value + Number(shippingCost.value || 0));
});

watch(grandTotal, (newVal) => {
    if (paymentStatus.value === 'paid') {
        amountPaid.value = newVal;
    }
}, { immediate: true });

// SRT-GAP-015: Value-proportional landed cost allocation with highest-subtotal remainder reconciliation
const allocatedLandedCosts = computed(() => {
    const freight = Number(shippingCost.value || 0);
    const sub = Number(subtotal.value || 0);
    const items = purchaseItems.value;
    if (!items.length) return [];

    let totalAllocated = 0;
    let highestIndex = -1;
    let highestLineTotal = -1;

    const results = items.map((item, idx) => {
        const lineTotal = Math.round(((Number(item.quantity || 0) * Number(item.price || 0)) * (1 - (Number(item.discount || 0) / 100))) * 100) / 100;
        let allocated = 0;
        if (freight > 0 && sub > 0) {
            allocated = Math.round((freight * (lineTotal / sub)) * 100) / 100;
        }
        totalAllocated += allocated;
        if (lineTotal > highestLineTotal) {
            highestLineTotal = lineTotal;
            highestIndex = idx;
        }
        return {
            lineTotal,
            allocatedFreight: allocated,
            landedUnitCost: Number(item.price || 0)
        };
    });

    // Residual discrepancy reconciliation to highest subtotal line item
    if (freight > 0 && sub > 0 && highestIndex >= 0) {
        const residual = Math.round((freight - totalAllocated) * 100) / 100;
        if (residual !== 0) {
            results[highestIndex].allocatedFreight = Math.round((results[highestIndex].allocatedFreight + residual) * 100) / 100;
        }
    }

    results.forEach((res, idx) => {
        const qty = Number(items[idx].quantity || 0);
        const basePrice = Number(items[idx].price || 0);
        res.landedUnitCost = qty > 0 
            ? Math.round((basePrice + (res.allocatedFreight / qty)) * 10000) / 10000 
            : basePrice;
    });

    return results;
});

const confirmPurchase = async () => {
    try {
        if (!selectedSupplierId.value) {
            alert('Please select a supplier before confirming purchase.');
            return;
        }

        if (purchaseItems.value.length === 0) {
            alert('Please add at least one item.');
            return;
        }

        let defaultProductId = 1;
        if (retailStore.products && retailStore.products.length > 0) {
            defaultProductId = retailStore.products[0].id;
        }

        let poNumber = invoiceNumber.value.trim();
        if (!poNumber) {
            poNumber = 'PO-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
        }
        
        const dateInput = document.getElementById('purchaseDate');
        const purchaseDate = (dateInput && dateInput.value) ? dateInput.value : new Date().toISOString().split('T')[0];

        if (isSubmitting.value) return;
        isSubmitting.value = true;

        const purchaseData = {
            supplier_id: selectedSupplierId.value,
            po_number: poNumber,
            date: purchaseDate,
            subtotal: subtotal.value,
            freight_amount: Number(shippingCost.value || 0),
            total: grandTotal.value,
            paid_amount: Number(amountPaid.value || 0),
            payment_method: selectedPaymentMethod.value || 'Cash',
            status: 'received',
            notes: 'Created via Confirm Purchase',
            items: purchaseItems.value.map(item => ({
                product_id: item.product_id || defaultProductId,
                quantity: Number(item.quantity),
                unit_cost: Number(item.price),
                total: (Number(item.quantity) * Number(item.price)) * (1 - (Number(item.discount || 0) / 100))
            }))
        };

        const result = await store.addPurchase(purchaseData);
        if (result.success) {
            alert('Purchase completed successfully.');
            invoiceNumber.value = '';
            selectedSupplierId.value = null;
            supplierSearchQuery.value = '';
            purchaseItems.value = [];
            amountPaid.value = 0;
            paymentStatus.value = 'paid';
            await store.fetchPurchases();
            await suppliersStore.fetchSuppliers();
            showPurchaseList();
        } else {
            alert('Failed to save purchase: ' + result.message);
        }
    } catch (e) {
        console.error(e);
        alert('An error occurred');
    } finally {
        isSubmitting.value = false;
    }
};

const hydratePurchaseData = async () => {
    await Promise.allSettled([
        store.fetchPurchases(),
        suppliersStore.fetchSuppliers(),
        retailStore.fetchProducts()
    ]);
};

onMounted(async () => {
    await hydratePurchaseData();
});

watch(() => isNewPurchase.value, async (val) => {
    if (val) {
        if (!retailStore.products || retailStore.products.length === 0) {
            await retailStore.fetchProducts();
        }
        if (!suppliersStore.suppliers || suppliersStore.suppliers.length === 0) {
            await suppliersStore.fetchSuppliers();
        }
    }
});
</script>

<style scoped>

        /* =========================================================
       RESET
    ========================================================= */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family:
                Inter,
                ui-sans-serif,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;

            background: #f6f8fb;
            color: #171a1f;
            min-height: 100vh;
        }

        button,
        input,
        select {
            font: inherit;
        }

        button {
            cursor: pointer;
        }

        /* =========================================================
       APP
    ========================================================= */

        .app {
            min-height: 100vh;
            display: flex;
        }

        /* =========================================================
       SIDEBAR
    ========================================================= */

        .sidebar {
            width: 255px;
            min-width: 255px;
            min-height: 100vh;
            background: #f8fafc;
            border-right: 1px solid #d9dee7;

            display: flex;
            flex-direction: column;

            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;

            z-index: 100;
        }

        .brand {
            padding: 20px 24px 18px;
            border-bottom: 1px solid #d9dee7;
        }

        .brand-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
            width: 48px;
            height: 48px;
            border-radius: 9px;
            background: #111111;
            color: #ffffff;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 18px;
            font-weight: 800;
        }

        .brand-name {
            font-size: 22px;
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .brand-subtitle {
            margin-top: 4px;
            font-size: 13px;
            color: #4f5661;
        }

        .quick-sale {
            margin: 20px 16px 16px;
            width: calc(100% - 32px);

            height: 40px;
            border: 0;
            border-radius: 8px;

            background: #000;
            color: #fff;

            font-weight: 600;
        }

        .quick-sale:hover {
            background: #222;
        }

        .nav {
            padding: 4px 10px;
        }

        .nav-item {
            width: 100%;
            height: 44px;

            border: 0;
            border-radius: 8px;

            background: transparent;
            color: #454b55;

            display: flex;
            align-items: center;
            gap: 13px;

            padding: 0 14px;

            font-size: 15px;
            font-weight: 500;

            text-align: left;
        }

        .nav-item:hover {
            background: #eef2f7;
        }

        .nav-item.active {
            background: #e8efff;
            color: #0758db;
            font-weight: 700;
            box-shadow: inset -3px 0 #0758db;
        }

        .nav-icon {
            width: 22px;
            text-align: center;
            font-size: 18px;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding: 16px;
            border-top: 1px solid #d9dee7;
        }

        .bottom-item {
            height: 40px;
            display: flex;
            align-items: center;
            gap: 12px;

            color: #4e5560;
            font-size: 14px;
            padding: 0 10px;
        }

        .bottom-item+.bottom-item {
            margin-top: 4px;
        }

        /* =========================================================
       MAIN
    ========================================================= */


        .purchase-page-wrapper {
            width: 100% !important;
            max-width: none !important;
            min-width: 0;
            flex: 1;
            box-sizing: border-box;
        }

        .content {
            padding: 28px 24px 50px;
            width: 100% !important;
            max-width: none !important;
            min-width: 0;
            box-sizing: border-box;
        }

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
        }

        .page-title {
            font-size: 32px;
            line-height: 1.15;
            font-weight: 800;
            letter-spacing: -0.8px;
        }

        .page-description {
            margin-top: 8px;
            color: #555c67;
            font-size: 15px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* =========================================================
       BUTTONS
    ========================================================= */

        .btn {
            min-height: 40px;
            padding: 0 17px;

            border-radius: 7px;
            border: 1px solid #bfc6d1;

            background: #fff;
            color: #1c2025;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            font-size: 14px;
            font-weight: 600;
        }

        .btn:hover {
            background: #f5f7fa;
        }

        .btn-black {
            background: #000;
            color: #fff;
            border-color: #000;
        }

        .btn-black:hover {
            background: #1d1d1d;
        }

        .btn-blue {
            background: #0758db;
            border-color: #0758db;
            color: #fff;
        }

        .btn-blue:hover {
            background: #0349ba;
        }

        /* =========================================================
       STATS
    ========================================================= */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #ccd2dc;
            border-radius: 12px;
            padding: 20px 22px;
            min-height: 118px;
        }

        .stat-label {
            font-size: 14px;
            color: #525965;
            margin-bottom: 12px;
        }

        .stat-value {
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .stat-value.danger {
            color: #c52020;
        }

        /* =========================================================
       PURCHASE TABLE CARD
    ========================================================= */

        .table-card {
            background: #fff;
            border: 1px solid #ccd2dc;
            border-radius: 12px;
            overflow: hidden;
        }

        .filters {
            padding: 16px;
            border-bottom: 1px solid #d4d9e1;

            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-box {
            height: 40px;
            width: 370px;
            max-width: 100%;

            border: 1px solid #c5cbd4;
            border-radius: 7px;

            display: flex;
            align-items: center;
            gap: 10px;

            padding: 0 12px;
            background: #fff;
        }

        .search-box span {
            font-size: 18px;
            color: #5e6671;
        }

        .search-box input {
            width: 100%;
            border: 0;
            outline: 0;
            font-size: 14px;
        }

        .filter-select {
            height: 40px;
            min-width: 130px;

            border: 1px solid #c5cbd4;
            border-radius: 7px;

            padding: 0 12px;
            background: #fff;

            color: #363b43;
            outline: none;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th {
            background: #f7f8fa;
            color: #4c535e;

            font-size: 12px;
            font-weight: 700;
            text-align: left;

            padding: 14px 16px;
            border-bottom: 1px solid #d1d6de;

            white-space: nowrap;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #d9dde4;

            font-size: 14px;
            color: #24282e;
        }

        tbody tr:hover {
            background: #fafbfc;
        }

        .purchase-id {
            font-weight: 700;
        }

        .supplier {
            font-weight: 500;
        }

        .amount {
            font-variant-numeric: tabular-nums;
        }

        .amount-danger {
            color: #d21d1d;
            font-weight: 600;
        }

        .status {
            display: inline-flex;
            align-items: center;

            min-height: 25px;
            padding: 3px 10px;

            border-radius: 999px;

            font-size: 12px;
            font-weight: 700;
        }

        .status.paid {
            background: #d9f8e9;
            color: #087848;
        }

        .status.partial {
            background: #fff0c9;
            color: #995500;
        }

        .status.unpaid {
            background: #ffe0e0;
            color: #b91c1c;
        }

        .action-btn {
            width: 34px;
            height: 34px;

            border: 0;
            background: transparent;

            font-size: 18px;
        }

        .table-footer {
            padding: 14px 16px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            color: #555c66;
            font-size: 14px;
        }

        .pagination {
            display: flex;
            gap: 6px;
        }

        .page-btn {
            width: 34px;
            height: 34px;

            border: 1px solid #ccd2da;
            border-radius: 6px;

            background: #fff;
        }

        .page-btn.active {
            background: #111;
            color: #fff;
            border-color: #111;
        }

        /* =========================================================
       NEW PURCHASE
    ========================================================= */

        /* v-show handles visibility — no manual display:none needed */

        .purchase-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;

            gap: 20px;
            margin-bottom: 22px;
        }

        .back-title {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .back-btn {
            width: 40px;
            height: 40px;

            border: 1px solid #c8ced8;
            border-radius: 7px;

            background: #fff;
            font-size: 20px;
        }

        .purchase-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 16px;
            align-items: start;
            width: 100% !important;
            max-width: none !important;
        }

        .purchase-left {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .card {
            background: #fff;
            border: 1px solid #ccd2dc;
            border-radius: 12px;
            padding: 22px;
        }

        .card-title {
            font-size: 20px;
            font-weight: 750;
            margin-bottom: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #424851;
            margin-bottom: 7px;
        }

        .required {
            color: #d51e1e;
        }

        .input,
        .select {
            width: 100%;
            height: 42px;

            border: 1px solid #c7cdd6;
            border-radius: 7px;

            padding: 0 13px;

            background: #fff;
            color: #20242a;

            outline: none;
        }

        .input:focus,
        .select:focus {
            border-color: #0758db;
            box-shadow: 0 0 0 3px rgba(7, 88, 219, 0.08);
        }

        /* =========================================================
       PRODUCTS
    ========================================================= */

        .products-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 18px;
        }

        .product-search {
            width: 290px;
            max-width: 100%;
            height: 40px;

            border: 1px solid #c7cdd6;
            border-radius: 7px;

            padding: 0 12px;
            outline: none;
        }

        .product-row {
            display: grid;

            grid-template-columns:
                minmax(140px, 1.4fr) 75px 55px 115px 80px 105px 105px 35px;

            gap: 10px;
            align-items: center;

            padding: 14px 0;
            border-bottom: 1px solid #d8dde4;
        }

        .product-row.header {
            padding-top: 0;
            color: #555c66;
            font-size: 12px;
            font-weight: 700;
        }

        .product-name {
            font-weight: 600;
        }

        .product-sku {
            display: block;
            margin-top: 4px;
            font-size: 11px;
            color: #737b86;
        }

        .mini-input {
            width: 100%;
            height: 38px;

            border: 1px solid #c6ccd5;
            border-radius: 6px;

            padding: 0 8px;
            text-align: center;

            outline: none;
        }

        .row-total {
            font-weight: 650;
            text-align: right;
        }

        .delete-btn {
            width: 32px;
            height: 32px;

            border: 0;
            background: transparent;

            color: #777;
        }

        .delete-btn:hover {
            color: #d11;
        }

        .add-item {
            margin-top: 16px;

            border: 0;
            background: transparent;

            color: #0758db;
            font-weight: 650;
            font-size: 14px;
        }

        .items-count {
            color: #555c66;
            font-size: 13px;
        }

        /* =========================================================
       SUMMARY
    ========================================================= */

        .summary-card {
            position: sticky;
            top: 82px;
        }

        .summary-line {
            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 8px 0;

            font-size: 14px;
            color: #4d545e;
        }

        .summary-total {
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-top: 10px;
            padding-top: 16px;

            border-top: 1px solid #d0d5dd;

            font-size: 21px;
            font-weight: 800;
        }

        .payment-section {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #d0d5dd;
        }

        .payment-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 7px;
            margin-bottom: 17px;
        }

        .payment-option {
            min-height: 42px;

            border: 1px solid #c8ced7;
            border-radius: 7px;

            background: #fff;

            font-size: 12px;
            font-weight: 600;
        }

        .payment-option.active {
            background: #0758db;
            color: #fff;
            border-color: #0758db;
        }

        .summary-card .btn {
            width: 100%;
            margin-top: 10px;
        }

        /* =========================================================
       MOBILE OVERLAY
    ========================================================= */

        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.35);
            z-index: 90;
        }

        .overlay.active {
            display: block;
        }

        /* =========================================================
       RESPONSIVE
    ========================================================= */

        @media (max-width: 1200px) {
            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .purchase-layout {
                grid-template-columns: minmax(0, 1fr) 300px;
            }

            .product-row {
                grid-template-columns:
                    minmax(130px, 1.4fr) 65px 55px 100px 75px 95px 30px;
            }
        }

        @media (max-width: 900px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.25s ease;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main {
                margin-left: 0;
                width: 100%;
            }

            .mobile-menu {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .global-search {
                width: 100%;
            }

            .purchase-layout {
                grid-template-columns: 1fr;
            }

            .summary-card {
                position: static;
            }

            .purchase-header {
                flex-direction: column;
            }

            .header-actions {
                width: 100%;
            }
        }

        @media (max-width: 700px) {
            .topbar {
                padding: 0 14px;
            }

            .topbar-right {
                gap: 7px;
            }

            .top-icon {
                display: none;
            }

            .content {
                padding: 20px 14px 40px;
            }

            .page-header {
                flex-direction: column;
            }

            .page-title {
                font-size: 27px;
            }

            .header-actions {
                width: 100%;
            }

            .header-actions .btn {
                flex: 1;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .filters {
                align-items: stretch;
            }

            .search-box {
                width: 100%;
            }

            .filter-select {
                flex: 1;
            }

            .table-footer {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .card {
                padding: 17px;
            }

            .products-heading {
                flex-direction: column;
                align-items: stretch;
            }

            .product-search {
                width: 100%;
            }

            .product-row.header {
                display: none;
            }

            .product-row {
                grid-template-columns: 1fr 70px 70px;
                gap: 10px;

                padding: 15px 0;
            }

            .product-row> :nth-child(3),
            .product-row> :nth-child(4),
            .product-row> :nth-child(5),
            .product-row> :nth-child(6) {
                display: none;
            }

            .row-total {
                grid-column: 2;
                grid-row: 1;
            }

            .delete-btn {
                grid-column: 3;
                grid-row: 1;
                justify-self: end;
            }

            .payment-options {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .brand {
                padding: 18px;
            }

            .content {
                padding-left: 10px;
                padding-right: 10px;
            }

            .page-title {
                font-size: 24px;
            }

            .page-description {
                font-size: 14px;
            }

            .topbar {
                height: 58px;
            }

            .profile {
                width: 32px;
                height: 32px;
            }

            .global-search {
                height: 38px;
            }

            .stat-card {
                padding: 17px;
            }

            .purchase-header .back-title {
                width: 100%;
            }

            .purchase-header .header-actions {
                flex-direction: column;
            }

            .purchase-header .header-actions .btn {
                width: 100%;
            }

            .summary-total {
                font-size: 19px;
            }
        }
    

</style>
