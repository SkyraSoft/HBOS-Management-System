<template>
<div class="customer-view-container" style="width: 100%; height: 100%; overflow: auto; flex: 1; min-width: 0;">
<section v-if="!isModal" class="page">

                <!-- HEADER -->
                <div class="page-header">

                    <div>

                        <h1 class="page-title">
                            Customers
                        </h1>

                        <p class="page-description">
                            Manage client relationships and track balances.
                        </p>

                    </div>

                    <div class="header-actions">

                        <button class="btn-custom" @click="openRecordPaymentModal">

                            <i class="bi bi-credit-card"></i>

                            Record Payment

                        </button>
                        <button class="btn-custom btn-primary-custom" @click="openAddModal">
                            <i class="bi bi-person-plus"></i>
                            Add Customer
                        </button>
                    </div>
                </div>

                <!-- =================================================
             STATISTICS
        ================================================== -->
                <div class="stats-grid">
                    <!-- TOTAL CUSTOMERS -->
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="stat-label">
                            Total<br />
                            Customers
                        </div>
                        <div class="stat-value">
                            842
                        </div>

                        <div class="stat-note success">
                            ↗ +12
                            <span style="color:#555b64;">
                                this month
                            </span>
                        </div>

                    </div>

                    <!-- ACTIVE -->
                    <div class="stat-card active-card">

                        <div class="stat-icon">
                            <i class="bi bi-person-check"></i>
                        </div>

                        <div class="stat-label">
                            Active
                        </div>

                        <div class="stat-value">
                            796
                        </div>

                        <div class="stat-note">
                            94% engagement rate
                        </div>

                    </div>

                    <!-- OUTSTANDING -->
                    <div class="stat-card outstanding-card">

                        <div class="stat-icon">
                            <i class="bi bi-wallet2"></i>
                        </div>

                        <div class="stat-label">
                            Total<br />
                            Outstanding
                        </div>

                        <div class="stat-value currency">
                            PKR 486,500
                        </div>

                        <div class="stat-note">
                            Across 124 accounts
                        </div>

                    </div>

                    <!-- OVERDUE -->
                    <div class="stat-card overdue-card">

                        <div class="stat-icon">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>

                        <div class="stat-label">
                            Overdue
                        </div>

                        <div class="stat-value currency danger">
                            PKR 64,200
                        </div>                        <div class="stat-note danger">
                            Requires immediate<br />
                            attention
                        </div>

                    </div>

                </div>

                <!-- =================================================
             CUSTOMER TABLE
        ================================================== -->

                <div class="customer-panel">

                    <!-- TOOLBAR -->
                    <div class="table-toolbar">

                        <div class="customer-search">

                            <i class="bi bi-search"></i>

                            <input type="text" id="customerSearch" v-model="searchQuery"
                                placeholder="Search customer by name or phone number..." />

                        </div>

                        <select class="filter-select" id="customerType" v-model="filterType">

                            <option value="all">
                                Customer Type: All
                            </option>

                            <option value="regular">
                                Regular
                            </option>

                            <option value="wholesale">
                                Wholesale
                            </option>

                            <option value="business">
                                Business
                            </option>

                        </select>

                        <select class="filter-select" id="creditStatus" v-model="filterStatus">

                            <option value="all">
                                Credit Status: All
                            </option>

                            <option value="clear">
                                Clear
                            </option>

                            <option value="outstanding">
                                Outstanding
                            </option>

                            <option value="overdue">
                                Overdue
                            </option>

                        </select>

                        <div class="toolbar-spacer"></div>

                        <button class="icon-button" title="Filter">

                            <i class="bi bi-funnel"></i>

                        </button>

                        <button class="icon-button" title="Download" onclick="downloadCustomers()">

                            <i class="bi bi-download"></i>

                        </button>

                    </div>

                    <!-- TABLE -->
                    <div class="table-wrapper">

                        <table class="customers-table">

                            <thead>

                                <tr>

                                    <th>
                                        Customer
                                    </th>

                                    <th>
                                        Total<br />
                                        Purchases
                                    </th>

                                    <th>
                                        Outstanding
                                    </th>

                                    <th>
                                        Credit<br />
                                        Limit
                                    </th>

                                    <th>
                                        Last<br />
                                        Transaction
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Actions
                                    </th>

                                </tr>

                            </thead>

                            
                            <tbody id="customerTable">
                                <tr v-for="c in paginatedCustomers" :key="c.id" :data-type="c.type || 'regular'" :data-status="c.status || 'active'">
                                    <td>
                                        <div class="customer-info">
                                            <div class="customer-avatar">
                                                {{ c.name ? c.name.charAt(0).toUpperCase() : 'C' }}
                                            </div>
                                            <div>
                                                <div class="customer-name">
                                                    {{ c.name }}
                                                </div>
                                                <div class="customer-phone">
                                                    {{ c.phone }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="amount">
                                        PKR {{ (c.sales || 0).toLocaleString() }}
                                    </td>
                                    <td :class="['amount', c.status === 'overdue' || c.status === 'outstanding' ? 'danger' : '']">
                                        PKR {{ (c.received || 0).toLocaleString() }}
                                    </td>
                                    <td>
                                        PKR {{ (c.balance || 0).toLocaleString() }}
                                    </td>
                                    <td>
                                        {{ c.created_at ? new Date(c.created_at).toLocaleDateString() : 'N/A' }}<br>
                                        {{ c.type || 'Regular' }}
                                    </td>
                                    <td>
                                        <span :class="['status', c.status || 'active']">
                                            {{ (c.status || 'active').charAt(0).toUpperCase() + (c.status || 'active').slice(1) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn" @click="openViewModal(c)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="action-btn more" @click="openEditModal(c)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="action-btn more text-danger" @click="deleteCustomer(c.id)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="paginatedCustomers.length === 0">
                                    <td colspan="7" class="text-center py-4">No customers found.</td>
                                </tr>
                            </tbody>


                        </table>

                    </div>

                    <!-- FOOTER -->
                    
                    <div class="table-footer">
                        <div class="result-count" id="resultCount">
                            Showing {{ paginationStart }} to {{ paginationEnd }} of {{ filteredCustomers.length }} results
                        </div>
                        <div class="pagination-custom">
                            <button class="page-btn" @click="changePage(currentPage - 1)" :disabled="currentPage === 1">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            
                            <template v-if="visiblePages[0] > 1">
                                <button class="page-btn" @click="changePage(1)">1</button>
                                <button class="page-btn" disabled v-if="visiblePages[0] > 2">...</button>
                            </template>

                            <button class="page-btn" v-for="p in visiblePages" :key="p" :class="{active: currentPage === p}" @click="changePage(p)">
                                {{ p }}
                            </button>

                            <template v-if="visiblePages[visiblePages.length - 1] < totalPages">
                                <button class="page-btn" disabled v-if="visiblePages[visiblePages.length - 1] < totalPages - 1">...</button>
                                <button class="page-btn" @click="changePage(totalPages)">{{ totalPages }}</button>
                            </template>

                            <button class="page-btn" @click="changePage(currentPage + 1)" :disabled="currentPage === totalPages">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

<!-- MODALS -->
    
    
    <!-- ADD CUSTOMER MODAL -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Customer</h5>
                    <button type="button" class="btn-close" @click="closeModal('addCustomerModal')"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Customer Name *</label>
                            <input type="text" class="form-control" v-model="newCustomer.name" placeholder="Enter full name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone *</label>
                            <input type="text" class="form-control" v-model="newCustomer.phone" placeholder="03XX-XXXXXXX">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" v-model="newCustomer.email" placeholder="email@example.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Customer Type</label>
                            <select class="form-select" v-model="newCustomer.type">
                                <option value="regular">Regular</option>
                                <option value="business">Business / B2B</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" rows="2" v-model="newCustomer.address" placeholder="Physical address"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Credit Limit (PKR)</label>
                            <input type="number" class="form-control" v-model.number="newCustomer.creditLimit" placeholder="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Opening Balance (PKR)</label>
                            <input type="number" class="form-control" v-model.number="newCustomer.openingBalance" placeholder="0">
                            <small class="text-muted">Current outstanding amount.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeModal('addCustomerModal')">Cancel</button>
                    <button type="button" class="btn btn-primary" @click="saveNewCustomer">Save Customer</button>
                </div>
            </div>
        </div>
    </div>


    <!-- EDIT CUSTOMER MODAL -->
    <div class="modal fade" id="editCustomerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Customer</h5>
                    <button type="button" class="btn-close" @click="closeModal('editCustomerModal')"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Customer Name</label>
                        <input type="text" class="form-control" v-model="editingCustomer.name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" v-model="editingCustomer.phone">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeModal('editCustomerModal')">Cancel</button>
                    <button type="button" class="btn btn-primary" @click="updateCustomer">Update Customer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- VIEW CUSTOMER MODAL -->
    <div class="modal fade" id="viewCustomerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Customer Details</h5>
                    <button type="button" class="btn-close" @click="closeModal('viewCustomerModal')"></button>
                </div>
                <div class="modal-body" v-if="viewingCustomer">
                    <p><strong>Name:</strong> {{ viewingCustomer.name }}</p>
                    <p><strong>Phone:</strong> {{ viewingCustomer.phone }}</p>
                    <p><strong>Total Sales:</strong> PKR {{ (viewingCustomer.sales || 0).toLocaleString() }}</p>
                    <p><strong>Received:</strong> PKR {{ (viewingCustomer.received || 0).toLocaleString() }}</p>
                    <p><strong>Balance:</strong> PKR {{ (viewingCustomer.balance || 0).toLocaleString() }}</p>
                    <p><strong>Status:</strong> {{ viewingCustomer.status || 'Active' }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeModal('viewCustomerModal')">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- RECORD PAYMENT MODAL -->
    <div class="modal fade" id="recordPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment</h5>
                    <button type="button" class="btn-close" @click="closeModal('recordPaymentModal')"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Customer</label>
                        <select class="form-select" v-model="paymentForm.customerId">
                            <option v-for="c in customers" :key="c.id" :value="c.id">{{c.name}} (Balance: PKR {{(c.balance || 0).toLocaleString()}})</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Amount</label>
                        <input type="number" class="form-control" placeholder="Enter amount..." v-model.number="paymentForm.amount">
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
    <div>
        <button type="button" class="btn btn-info me-2 text-white" @click="$emit('open-purchase')">New Purchase</button>
        <button type="button" class="btn btn-warning" @click="$emit('open-return-debt')">Return Debt</button>
    </div>
    <div>
        <button type="button" class="btn btn-secondary me-2" @click="closeModal('recordPaymentModal')">Cancel</button>
        <button type="button" class="btn btn-primary" @click="savePayment">Record Payment</button>
    </div>
</div>
            </div>
        </div>
    </div>

</div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'
const props = defineProps({
  isModal: { type: Boolean, default: false },
  modalPage: { type: String, default: '' }
})
const emit = defineEmits(['success', 'close', 'open-purchase', 'open-return-debt'])
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useCustomersStore } from '@/stores/customers';
import { useKhataStore } from '@/stores/khata';

const store = useCustomersStore();
const khataStore = useKhataStore();
const route = useRoute();
const router = useRouter();
const customers = computed(() => store.customers);

const searchQuery = ref('');
const filterType = ref('all');
const filterStatus = ref('all');

const currentPage = ref(1);
const itemsPerPage = ref(10);

const paymentForm = ref({ customerId: null, amount: 0 });
const newCustomer = ref({ name: '', phone: '', email: '', type: 'regular', address: '', creditLimit: 0, openingBalance: 0 });
const editingCustomer = ref({ id: null, name: '', phone: '' });
const viewingCustomer = ref(null);

onMounted(async () => {
    if (!props.isModal && !route.query.tab) {
        router.replace({ path: '/customer', query: { ...route.query, tab: 'customers' } });
    }
    if (props.isModal && props.modalPage === 'payment') {
        setTimeout(() => openRecordPaymentModal(), 150);
    }
    await store.fetchCustomers();
});

const filteredCustomers = computed(() => {
    let result = customers.value;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(c => (c.name || '').toLowerCase().includes(q) || (c.phone || '').includes(q));
    }
    if (filterType.value !== 'all') {
        result = result.filter(c => (c.type || 'regular') === filterType.value);
    }
    if (filterStatus.value !== 'all') {
        result = result.filter(c => (c.status || 'active') === filterStatus.value);
    }
    
    // Reset to page 1 if search changes
    // (A watcher is better, but this works fine for a simple app)
    return result;
});

const totalPages = computed(() => {
    return Math.max(1, Math.ceil(filteredCustomers.value.length / itemsPerPage.value));
});

const paginatedCustomers = computed(() => {
    if (currentPage.value > totalPages.value) {
        currentPage.value = 1;
    }
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredCustomers.value.slice(start, end);
});

const paginationStart = computed(() => {
    return filteredCustomers.value.length === 0 ? 0 : (currentPage.value - 1) * itemsPerPage.value + 1;
});

const paginationEnd = computed(() => {
    return Math.min(currentPage.value * itemsPerPage.value, filteredCustomers.value.length);
});

const visiblePages = computed(() => {
    let start = Math.max(1, currentPage.value - 2);
    let end = Math.min(totalPages.value, currentPage.value + 2);
    let pages = [];
    for (let i = start; i <= end; i++) pages.push(i);
    return pages;
});

function changePage(p) {
    if (p >= 1 && p <= totalPages.value) {
        currentPage.value = p;
    }
}

// Modals
function openModal(id) {
    const el = document.getElementById(id);
    if(el) {
        el.style.display = 'block';
        el.style.zIndex = '1060';
        el.classList.add('show');
        if (props.isModal) {
            el.style.background = 'none';
            el.style.pointerEvents = 'none';
            const dialog = el.querySelector('.modal-dialog');
            if (dialog) dialog.style.pointerEvents = 'auto';
        } else {
            const bd = document.createElement('div');
            bd.className = 'modal-backdrop fade show custom-bd-' + id;
            document.body.appendChild(bd);
        }
    }
}

function closeModal(id) {
    const el = document.getElementById(id);
    if(el) {
        el.style.display = 'none';
        el.classList.remove('show');
        if (props.isModal) {
            el.style.background = '';
            el.style.pointerEvents = '';
        } else {
            const bd = document.querySelector('.custom-bd-' + id);
            if(bd) bd.remove();
        }
    }
}

function openAddModal() {
    newCustomer.value = { name: '', phone: '', email: '', type: 'regular', address: '', creditLimit: 0, openingBalance: 0 };
    openModal('addCustomerModal');
}


async function saveNewCustomer() {
    if(!newCustomer.value.name || !newCustomer.value.phone) {
        alert("Name and Phone are required.");
        return;
    }
    
    await store.addCustomer({
        name: newCustomer.value.name,
        phone: newCustomer.value.phone,
        email: newCustomer.value.email,
        address: newCustomer.value.address,
        credit_limit: newCustomer.value.creditLimit || 0,
        opening_balance: newCustomer.value.openingBalance || 0,
        balance: newCustomer.value.openingBalance || 0,
        type: newCustomer.value.type
    });
    
    closeModal('addCustomerModal');
}


function openEditModal(c) {
    editingCustomer.value = { id: c.id, name: c.name, phone: c.phone };
    openModal('editCustomerModal');
}

async function updateCustomer() {
    await store.updateCustomer(editingCustomer.value.id, {
        name: editingCustomer.value.name,
        phone: editingCustomer.value.phone
    });
    closeModal('editCustomerModal');
}

function openViewModal(c) {
    viewingCustomer.value = c;
    openModal('viewCustomerModal');
}

async function deleteCustomer(id) {
    if(confirm('Are you sure you want to delete this customer?')) {
        await store.deleteCustomer(id);
    }
}

function openRecordPaymentModal() {
    if (customers.value.length > 0) paymentForm.value.customerId = customers.value[0].id;
    openModal('recordPaymentModal');
}

function downloadCustomers() {
    const csv = "Customer,Phone,Total Purchases,Outstanding,Credit Limit,Status\n" +
                "Ali Hassan,0300-1234567,PKR 142000,PKR 25500,PKR 50000,Overdue\n" +
                "Fatima Ahmed,0333-7654321,PKR 85400,PKR 12000,PKR 30000,Outstanding\n" +
                "Zain Qureshi,0345-9988776,PKR 210000,PKR 0,PKR 100000,Clear\n" +
                "Sara Tariq,0321-4455667,PKR 45000,PKR 0,PKR 20000,Clear";
    const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = "HBOS-Customers.csv";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

async function savePayment() {
    if (!paymentForm.value.customerId || !paymentForm.value.amount || paymentForm.value.amount <= 0) return;
    
    const payload = {
        customer_id: paymentForm.value.customerId,
        type: 'got',
        amount: paymentForm.value.amount,
        date: new Date().toISOString().split('T')[0],
        notes: 'Payment received (Cash)'
    };
    
    const res = await khataStore.addTransaction(payload);
    
    if (res.success) {
        await store.fetchCustomers();
            closeModal('recordPaymentModal');
            if (props.isModal) {
                emit('success', res.message || 'Payment recorded successfully!');
                return;
            }
        paymentForm.value = { customerId: null, amount: 0 };
    } else {
        alert(res.message || "Failed to record payment.");
    }
}

</script>

<style scoped>

        /* =========================================================
       GLOBAL
    ========================================================= */

        :root {
            --primary: #0757e5;
            --primary-dark: #0046c7;
            --text: #17191d;
            --muted: #626873;
            --border: #cbd0d8;
            --bg: #f6f8fa;
            --white: #ffffff;
            --danger: #d32222;
            --success: #079b68;
            --warning: #d89000;
            --sidebar-width: 258px;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
            margin: 0;
            padding: 0;
            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Arial,
                sans-serif;
            color: var(--text);
            background: var(--bg);
        }

        body {
            overflow-x: hidden;
        }

        button,
        input,
        select {
            font: inherit;
        }

        /* =========================================================
       APP LAYOUT
    ========================================================= */

        .app {
            min-height: 100vh;
            display: flex;
            background: var(--bg);
        }

        /* =========================================================
       SIDEBAR
    ========================================================= */

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: #fff;
            border-right: 1px solid var(--border);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: transform 0.25s ease;
        }

        .brand {
            height: 128px;
            padding: 28px 26px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }

        .brand-icon {
            width: 50px;
            height: 50px;
            border-radius: 3px;
            background: #050505;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .brand-content {
            line-height: 1;
        }

        .brand-title {
            font-size: 27px;
            line-height: 1.1;
            font-weight: 700;
            letter-spacing: -0.8px;
        }

        .brand-subtitle {
            margin-top: 7px;
            font-size: 15px;
            color: #20242a;
            font-weight: 500;
        }

        /* Sidebar navigation */

        .sidebar-nav {
            padding: 20px 10px;
            flex: 1;
        }

        .nav-link-custom {
            width: 100%;
            min-height: 44px;
            padding: 10px 15px;
            margin-bottom: 5px;
            border-radius: 7px;
            color: #4d525a;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 17px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .nav-link-custom i {
            width: 20px;
            text-align: center;
            font-size: 18px;
        }

        .nav-link-custom:hover {
            background: #f1f4f9;
            color: var(--primary);
        }

        .nav-link-custom.active {
            color: var(--primary);
            background: #e8efff;
            border-right: 4px solid var(--primary);
        }

        .sidebar-bottom {
            padding: 0 10px 20px;
        }

        .sidebar-divider {
            height: 1px;
            background: var(--border);
            margin: 0 6px 15px;
        }

        /* =========================================================
       MAIN
    ========================================================= */

        .main {
            width: calc(100% - var(--sidebar-width));
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        /* =========================================================
       TOPBAR
    ========================================================= */

        .topbar {
            height: 86px;
            background: #fff;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 28px;
            gap: 20px;
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .mobile-menu {
            display: none;
            width: 42px;
            height: 42px;
            border: 1px solid var(--border);
            background: #fff;
            border-radius: 7px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
        }

        .global-search {
            width: min(650px, 100%);
            height: 48px;
            border: 1px solid #c9ced7;
            border-radius: 7px;
            background: #fff;
            display: flex;
            align-items: center;
            padding: 0 15px;
        }

        .global-search i {
            font-size: 21px;
            margin-right: 13px;
            color: #252a31;
        }

        .global-search input {
            width: 100%;
            border: 0;
            outline: 0;
            font-size: 16px;
            color: var(--text);
        }

        .global-search input::placeholder {
            color: #747b85;
        }

        .topbar-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 22px;
        }

        .top-icon {
            position: relative;
            border: 0;
            background: transparent;
            width: 38px;
            height: 38px;
            font-size: 22px;
            color: #30343b;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .notification-dot {
            position: absolute;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #d52323;
            top: 4px;
            right: 4px;
            border: 1px solid #fff;
        }

        .profile-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #e6ebf7;
            border: 1px solid #bdc7db;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #252b38;
        }

        /* =========================================================
       PAGE
    ========================================================= */

        .page {
            padding: 24px 30px 40px;
            width: 100% !important;
            min-width: calc(100vw - var(--sidebar-width, 258px)) !important;
            max-width: none !important;
            margin: 0 !important;
            box-sizing: border-box !important;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 22px;
        }

        .page-title {
            font-size: 31px;
            line-height: 1.15;
            font-weight: 700;
            letter-spacing: -0.8px;
            margin: 0 0 6px;
        }

        .page-description {
            color: #555c66;
            font-size: 18px;
            margin: 0;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        .btn-custom {
            min-height: 48px;
            padding: 0 20px;
            border-radius: 7px;
            font-size: 16px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            border: 1px solid var(--border);
            background: #fff;
            color: #17191d;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .btn-custom:hover {
            background: #f4f6f9;
        }

        .btn-primary-custom {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        .btn-primary-custom:hover {
            background: var(--primary-dark);
            color: #fff;
        }

        /* =========================================================
       STAT CARDS
    ========================================================= */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            min-height: 180px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 25px 25px 20px;
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: "";
            position: absolute;
            width: 95px;
            height: 95px;
            border-radius: 50%;
            right: -8px;
            top: -20px;
            background: #f0f1f3;
            opacity: 0.8;
        }

        .stat-card.active-card::after {
            background: #edf9f5;
        }

        .stat-card.outstanding-card::after {
            background: #edf3ff;
        }

        .stat-card.overdue-card::after {
            background: #fff0f0;
        }

        .stat-icon {
            position: absolute;
            z-index: 2;
            right: 25px;
            top: 25px;
            width: 39px;
            height: 39px;
            border-radius: 9px;
            background: #f1f2f4;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #555c65;
            font-size: 19px;
        }

        .stat-card.active-card .stat-icon {
            background: #edf5f2;
            color: #527f71;
        }

        .stat-card.outstanding-card .stat-icon {
            background: #edf3ff;
            color: #536f9e;
        }

        .stat-card.overdue-card .stat-icon {
            background: #ffd9d9;
            color: #df2020;
        }

        .stat-label {
            width: 65%;
            font-size: 15px;
            line-height: 1.35;
            letter-spacing: 1px;
            font-weight: 500;
            color: #4e545d;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .stat-value {
            font-size: 35px;
            line-height: 1;
            font-weight: 700;
            letter-spacing: -1px;
            margin-bottom: 12px;
        }

        .stat-value.currency {
            font-size: 29px;
        }

        .stat-value.danger {
            color: var(--danger);
        }

        .stat-note {
            font-size: 16px;
            color: #5d626b;
        }

        .stat-note.success {
            color: var(--success);
        }

        .stat-note.danger {
            color: #e11b1b;
            line-height: 1.45;
            max-width: 210px;
        }

        /* =========================================================
       CUSTOMER TABLE
    ========================================================= */

        .customer-panel {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
        }

        .table-toolbar {
            min-height: 70px;
            padding: 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .customer-search {
            height: 40px;
            width: 385px;
            max-width: 100%;
            border: 1px solid #c5cad2;
            border-radius: 6px;
            display: flex;
            align-items: center;
            padding: 0 12px;
        }

        .customer-search i {
            color: #4c535c;
            margin-right: 12px;
        }

        .customer-search input {
            border: 0;
            outline: 0;
            width: 100%;
            min-width: 0;
            font-size: 15px;
        }

        .filter-select {
            height: 40px;
            min-width: 180px;
            border: 1px solid #c5cad2;
            border-radius: 6px;
            background: #fff;
            padding: 0 35px 0 12px;
            font-size: 15px;
            color: #282c31;
            outline: none;
        }

        .toolbar-spacer {
            flex: 1;
        }

        .icon-button {
            width: 40px;
            height: 40px;
            border: 1px solid #c5cad2;
            border-radius: 6px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .icon-button:hover {
            background: #f3f5f8;
        }

        /* Table */

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .customers-table {
            width: 100%;
            min-width: 950px;
            border-collapse: collapse;
        }

        .customers-table th {
            height: 55px;
            background: #fafbfc;
            border-bottom: 1px solid var(--border);
            padding: 10px 16px;
            text-align: left;
            color: #4d535d;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .customers-table td {
            height: 74px;
            border-bottom: 1px solid var(--border);
            padding: 10px 16px;
            font-size: 15px;
            color: #4b5058;
            vertical-align: middle;
        }

        .customers-table tbody tr:hover {
            background: #fafcff;
        }

        .customer-info {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 180px;
        }

        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid #c7cbd1;
            background: #f0f1f3;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #5c626b;
            font-weight: 600;
            flex-shrink: 0;
            overflow: hidden;
        }

        .customer-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .customer-name {
            color: #202328;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .customer-phone {
            color: #5f656e;
            font-size: 14px;
        }

        .amount {
            white-space: nowrap;
        }

        .amount.danger {
            color: #e32222;
            font-weight: 500;
        }

        .status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 13px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status.overdue {
            background: #ffe2e2;
            color: #c51e1e;
        }

        .status.outstanding {
            background: #fff0bd;
            color: #a36300;
        }

        .status.clear {
            background: #d9f7e9;
            color: #087a54;
        }

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .action-btn {
            border: 0;
            background: transparent;
            color: var(--primary);
            font-size: 19px;
            padding: 4px;
            cursor: pointer;
        }

        .action-btn.more {
            color: #434951;
        }

        /* =========================================================
       TABLE FOOTER
    ========================================================= */

        .table-footer {
            min-height: 62px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            gap: 15px;
        }

        .result-count {
            color: #555b64;
            font-size: 15px;
        }

        .pagination-custom {
            display: flex;
            align-items: center;
        }

        .page-btn {
            width: 40px;
            height: 38px;
            border: 1px solid #cbd0d8;
            border-left: 0;
            background: #fff;
            color: #41464e;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .page-btn:first-child {
            border-left: 1px solid #cbd0d8;
            border-radius: 6px 0 0 6px;
        }

        .page-btn:last-child {
            border-radius: 0 6px 6px 0;
        }

        .page-btn.active {
            background: #101b37;
            color: #fff;
            border-color: #101b37;
        }

        /* =========================================================
       MOBILE OVERLAY
    ========================================================= */

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.35);
            z-index: 999;
        }

        /* =========================================================
       RESPONSIVE
    ========================================================= */

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .page {
                padding-left: 22px;
                padding-right: 22px;
            }

            .page-header {
                flex-direction: column;
            }

            .header-actions {
                width: 100%;
            }

            .header-actions .btn-custom {
                flex: 1;
            }

            .table-toolbar {
                flex-wrap: wrap;
            }

            .customer-search {
                width: 100%;
            }

            .toolbar-spacer {
                display: none;
            }

            .filter-select {
                flex: 1;
            }
        }

        @media (max-width: 650px) {
            .page {
                padding: 18px 14px 30px;
            }

            .topbar {
                gap: 8px;
            }

            .global-search {
                flex: 1;
                min-width: 0;
            }

            .global-search input {
                font-size: 14px;
            }

            .top-icon {
                display: none;
            }

            .profile-circle {
                width: 38px;
                height: 38px;
            }

            .page-title {
                font-size: 27px;
            }

            .page-description {
                font-size: 15px;
            }

            .header-actions {
                flex-direction: column;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .stat-card {
                min-height: 155px;
                padding: 21px;
            }

            .stat-value {
                font-size: 32px;
            }

            .stat-value.currency {
                font-size: 26px;
            }

            .table-toolbar {
                padding: 12px;
            }

            .filter-select {
                width: 100%;
                flex: 1 1 100%;
            }

            .table-footer {
                flex-direction: column;
                align-items: flex-start;
            }

            .pagination-custom {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 420px) {
            .brand {
                padding-left: 20px;
            }

            .page {
                padding-left: 10px;
                padding-right: 10px;
            }

            .stat-card {
                border-radius: 10px;
            }

            .page-title {
                font-size: 25px;
            }

            .btn-custom {
                width: 100%;
            }
        }
    
</style>