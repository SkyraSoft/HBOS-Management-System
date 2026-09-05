const fs = require('fs');

const legacyHtml = fs.readFileSync('c:/xampp/htdocs/HBOS/legacy_html/Customer.html', 'utf8');

// Extract the main content (inside <main class="main">)
let mainContent = legacyHtml.substring(legacyHtml.indexOf('<main class="main">'), legacyHtml.indexOf('</main>') + 7);

// Remove the hardcoded rows in tbody and replace with a v-for
const tbodyRegex = /<tbody[^>]*>[\s\S]*?<\/tbody>/i;
const tbodyTemplate = `
                            <tbody id="customerTable">
                                <tr v-for="c in paginatedCustomers" :key="c.id" :data-type="c.type" :data-status="c.status">
                                    <td>
                                        <div class="customer-info">
                                            <div class="customer-avatar">
                                                {{ c.initials }}
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
                                        PKR {{ c.sales.toLocaleString() }}
                                    </td>
                                    <td :class="['amount', c.status === 'overdue' || c.status === 'outstanding' ? 'danger' : '']">
                                        PKR {{ c.received.toLocaleString() }}
                                    </td>
                                    <td>
                                        PKR {{ c.balance.toLocaleString() }}
                                    </td>
                                    <td>
                                        {{ c.lastActivity }}<br>
                                        {{ c.typeStr }}
                                    </td>
                                    <td>
                                        <span :class="['status', c.status]">
                                            {{ c.status.charAt(0).toUpperCase() + c.status.slice(1) }}
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
`;
mainContent = mainContent.replace(tbodyRegex, tbodyTemplate);

// Replace pagination
const paginationRegex = /<div class="table-footer">[\s\S]*?<\/div>\s*<\/div>\s*<\/section>/i;
const paginationTemplate = `
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
`;
mainContent = mainContent.replace(paginationRegex, paginationTemplate);

// Wire up the header buttons
mainContent = mainContent.replace(/<button class="btn-custom btn-primary"[^>]*>[\s\S]*?<\/button>/, 
    `<button class="btn-custom btn-primary" @click="openAddModal">
        <i class="bi bi-plus-lg"></i>
        <span>Add Customer</span>
    </button>`);

mainContent = mainContent.replace(/<button class="btn-custom btn-outline"[^>]*>[\s\S]*?<\/button>/, 
    `<button class="btn-custom btn-outline" @click="openRecordPaymentModal">
        <i class="bi bi-wallet2"></i>
        <span>Record Payment</span>
    </button>`);

mainContent = mainContent.replace(/<button class="icon-button" title="Export" onclick="downloadCustomers\(\)">/g, 
    `<button class="icon-button" title="Export" @click="downloadCustomers">`);

// Add v-models to search
mainContent = mainContent.replace(/<input type="text" id="customerSearch" placeholder="Search customers..." \/>/,
    `<input type="text" id="customerSearch" placeholder="Search customers..." v-model="searchQuery" />`);

mainContent = mainContent.replace(/<select class="filter-select" id="customerType">/,
    `<select class="filter-select" id="customerType" v-model="filterType">`);
mainContent = mainContent.replace(/<select class="filter-select" id="creditStatus">/,
    `<select class="filter-select" id="creditStatus" v-model="filterStatus">`);

// Replace the existing CustomerView content entirely
let vueFileContent = `
<template>
<div class="app">
    ${mainContent}

    <!-- MODALS -->
    
    <!-- ADD CUSTOMER MODAL -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Customer</h5>
                    <button type="button" class="btn-close" @click="closeModal('addCustomerModal')"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Customer Name</label>
                        <input type="text" class="form-control" v-model="newCustomer.name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" v-model="newCustomer.phone">
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
                    <p><strong>Total Sales:</strong> PKR {{ viewingCustomer.sales.toLocaleString() }}</p>
                    <p><strong>Received:</strong> PKR {{ viewingCustomer.received.toLocaleString() }}</p>
                    <p><strong>Balance:</strong> PKR {{ viewingCustomer.balance.toLocaleString() }}</p>
                    <p><strong>Status:</strong> {{ viewingCustomer.status }}</p>
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
                        <select class="form-select">
                            <option v-for="c in customers" :key="c.id" :value="c.id">{{c.name}} (Balance: PKR {{c.balance.toLocaleString()}})</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Amount</label>
                        <input type="number" class="form-control" placeholder="Enter amount...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeModal('recordPaymentModal')">Cancel</button>
                    <button type="button" class="btn btn-primary" @click="closeModal('recordPaymentModal')">Record Payment</button>
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const customers = ref([]);
const searchQuery = ref('');
const filterType = ref('all');
const filterStatus = ref('all');

const currentPage = ref(1);
const itemsPerPage = ref(10);

const newCustomer = ref({ name: '', phone: '' });
const editingCustomer = ref({ id: null, name: '', phone: '' });
const viewingCustomer = ref(null);

onMounted(() => {
    // Generate mock data to match original 4 rows + 800 more for pagination testing
    const baseCustomers = [
        { name: 'Ali Hassan', phone: '0300-1234567', sales: 142000, received: 25500, balance: 50000, lastActivity: 'Oct 12, 2023', typeStr: 'Payment Recvd', type: 'regular', status: 'overdue' },
        { name: 'Fatima Ahmed', phone: '0333-7654321', sales: 85400, received: 12000, balance: 30000, lastActivity: 'Nov 02, 2023', typeStr: 'Sale (Credit)', type: 'regular', status: 'outstanding' },
        { name: 'Zain Qureshi', phone: '0345-9988776', sales: 210000, received: 0, balance: 100000, lastActivity: 'Nov 05, 2023', typeStr: 'Sale (Cash)', type: 'business', status: 'clear' },
        { name: 'Sara Tariq', phone: '0321-4455667', sales: 45000, received: 0, balance: 20000, lastActivity: 'Oct 28, 2023', typeStr: 'Payment Recvd', type: 'regular', status: 'clear' }
    ];

    let idCounter = 1;
    const generated = [];
    
    // Add the 4 original ones exactly as they were
    for(let i=0; i<4; i++) {
        let c = {...baseCustomers[i]};
        c.id = idCounter++;
        let parts = c.name.split(' ');
        c.initials = parts.length > 1 ? parts[0][0] + parts[1][0] : parts[0][0];
        generated.push(c);
    }
    
    // Add rest to make 842 total
    for (let i = 5; i <= 842; i++) {
        generated.push({
            id: idCounter++,
            name: 'Customer ' + i,
            initials: 'C' + i,
            phone: '0300-' + (1000000 + i),
            sales: 10000 + i * 10,
            received: 5000 + i * 5,
            balance: 5000 + i * 5,
            lastActivity: 'Oct 12, 2023',
            typeStr: 'Sale',
            type: 'regular',
            status: i % 3 === 0 ? 'overdue' : (i % 2 === 0 ? 'clear' : 'outstanding')
        });
    }
    customers.value = generated;
});

const filteredCustomers = computed(() => {
    let result = customers.value;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(c => c.name.toLowerCase().includes(q) || c.phone.includes(q));
    }
    if (filterType.value !== 'all') {
        result = result.filter(c => c.type === filterType.value);
    }
    if (filterStatus.value !== 'all') {
        result = result.filter(c => c.status === filterStatus.value);
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
        el.classList.add('show');
        const bd = document.createElement('div');
        bd.className = 'modal-backdrop fade show custom-bd-' + id;
        document.body.appendChild(bd);
    }
}

function closeModal(id) {
    const el = document.getElementById(id);
    if(el) {
        el.style.display = 'none';
        el.classList.remove('show');
    }
    const bd = document.querySelector('.custom-bd-' + id);
    if (bd) bd.remove();
}

function openAddModal() {
    newCustomer.value = { name: '', phone: '' };
    openModal('addCustomerModal');
}

function saveNewCustomer() {
    if(!newCustomer.value.name) return;
    const newId = customers.value.length ? Math.max(...customers.value.map(c => c.id)) + 1 : 1;
    
    let parts = newCustomer.value.name.split(' ');
    let initials = parts.length > 1 ? parts[0][0] + parts[1][0] : parts[0][0];
    
    customers.value.unshift({
        id: newId,
        name: newCustomer.value.name,
        initials: initials.toUpperCase(),
        phone: newCustomer.value.phone,
        sales: 0,
        received: 0,
        balance: 0,
        lastActivity: 'Today',
        typeStr: 'New',
        type: 'regular',
        status: 'clear'
    });
    
    closeModal('addCustomerModal');
}

function openEditModal(c) {
    editingCustomer.value = { id: c.id, name: c.name, phone: c.phone };
    openModal('editCustomerModal');
}

function updateCustomer() {
    const index = customers.value.findIndex(c => c.id === editingCustomer.value.id);
    if(index > -1) {
        customers.value[index].name = editingCustomer.value.name;
        customers.value[index].phone = editingCustomer.value.phone;
        let parts = editingCustomer.value.name.split(' ');
        customers.value[index].initials = (parts.length > 1 ? parts[0][0] + parts[1][0] : parts[0][0]).toUpperCase();
    }
    closeModal('editCustomerModal');
}

function openViewModal(c) {
    viewingCustomer.value = c;
    openModal('viewCustomerModal');
}

function deleteCustomer(id) {
    if(confirm('Are you sure you want to delete this customer?')) {
        customers.value = customers.value.filter(c => c.id !== id);
    }
}

function openRecordPaymentModal() {
    openModal('recordPaymentModal');
}

function downloadCustomers() {
    const csv = "Customer,Phone,Total Purchases,Outstanding,Credit Limit,Status\\n" +
                "Ali Hassan,0300-1234567,PKR 142000,PKR 25500,PKR 50000,Overdue\\n" +
                "Fatima Ahmed,0333-7654321,PKR 85400,PKR 12000,PKR 30000,Outstanding\\n" +
                "Zain Qureshi,0345-9988776,PKR 210000,PKR 0,PKR 100000,Clear\\n" +
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
</script>

<style scoped>
${legacyHtml.substring(legacyHtml.indexOf('<style>') + 7, legacyHtml.indexOf('</style>')).replace(/max-width:\s*1500px;\s*margin:\s*0\s+auto;/g, 'width: 100%;')}
</style>
`;

fs.writeFileSync('c:/xampp/htdocs/HBOS/src/views/CustomerView.vue', vueFileContent);
console.log("Rebuilt CustomerView.vue");
