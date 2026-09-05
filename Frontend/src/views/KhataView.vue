<template>
  <div class="khata-page-wrapper">
    <!-- TOP SUCCESS ALERT -->
    <transition name="alert-slide">
      <div v-if="successAlertMessage" class="khata-top-alert" role="alert" style="z-index: 9999;">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-check-circle-fill text-success fs-5"></i>
          <div>
            <strong>Success!</strong> {{ successAlertMessage }}
          </div>
        </div>
        <button type="button" class="btn-close" @click="successAlertMessage = ''" aria-label="Close"></button>
      </div>
    </transition>

    <!-- TOP ERROR ALERT -->
    <transition name="alert-slide">
      <div v-if="errorAlertMessage" class="khata-top-alert border-danger" role="alert" style="border-left-color: #dc3545; background-color: #fdf2f2; z-index: 9999;">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
          <div class="text-danger">
            <strong>Error!</strong> {{ errorAlertMessage }}
          </div>
        </div>
        <button type="button" class="btn-close" @click="errorAlertMessage = ''" aria-label="Close"></button>
      </div>
    </transition>

    <div class="khata-main-container">
      <!-- PAGE HEADER -->
      <div class="khata-topbar">
        <div>
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="khata-badge-pill">Credit Control</span>
            <span class="text-muted small">• Live Customer Ledger</span>
          </div>
          <h1 class="khata-main-title">Khata & Customer Balances</h1>
          <p class="khata-main-sub">
            Track real-time credit accounts, record customer debt settlements, and monitor overdue balances.
          </p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
          <button class="btn btn-primary btn-sm rounded-3 fw-semibold px-3 shadow-sm" @click="openPaymentModal()" type="button" style="height: 38px;">
            <i class="bi bi-wallet2 me-1"></i> Record Payment
          </button>
        </div>
      </div>

      <!-- KPI METRICS GRID -->
      <div class="khata-kpi-grid mb-4">
        <!-- 1. Total Outstanding -->
        <div class="khata-kpi-box">
          <div class="khata-kpi-icon bg-primary-subtle text-primary">
            <i class="bi bi-wallet2"></i>
          </div>
          <div class="khata-kpi-data">
            <span class="khata-kpi-label">Total Outstanding</span>
            <div class="khata-kpi-num">PKR {{ totalOutstandingBalance.toLocaleString() }}</div>
            <span class="khata-kpi-note text-muted">Across {{ creditCustomerCount }} credit accounts</span>
          </div>
        </div>

        <!-- 2. Customers With Credit -->
        <div class="khata-kpi-box">
          <div class="khata-kpi-icon bg-info-subtle text-info">
            <i class="bi bi-people"></i>
          </div>
          <div class="khata-kpi-data">
            <span class="khata-kpi-label">Customers With Credit</span>
            <div class="khata-kpi-num">{{ creditCustomerCount }}</div>
            <span class="khata-kpi-note text-muted">Active credit accounts</span>
          </div>
        </div>

        <!-- 3. Overdue Amount -->
        <div class="khata-kpi-box overdue-box">
          <div class="khata-kpi-icon bg-danger-subtle text-danger">
            <i class="bi bi-exclamation-triangle"></i>
          </div>
          <div class="khata-kpi-data">
            <span class="khata-kpi-label">Overdue Amount</span>
            <div class="khata-kpi-num text-danger">PKR {{ totalOverdueAmount.toLocaleString() }}</div>
            <span class="khata-kpi-note text-danger-emphasis">Requires attention</span>
          </div>
        </div>

        <!-- 4. Payments Received -->
        <div class="khata-kpi-box">
          <div class="khata-kpi-icon bg-success-subtle text-success">
            <i class="bi bi-check-circle"></i>
          </div>
          <div class="khata-kpi-data">
            <span class="khata-kpi-label">Payments Received</span>
            <div class="khata-kpi-num text-success">PKR {{ totalPaymentsReceived.toLocaleString() }}</div>
            <span class="khata-kpi-note text-muted">Total recorded settlements</span>
          </div>
        </div>
      </div>

      <!-- MAIN PANEL / TABLE CARD -->
      <div class="khata-panel-card">
        <!-- TOOLBAR & FILTERS -->
        <div class="khata-panel-header">
          <div class="d-flex align-items-center gap-2">
            <h2 class="khata-panel-title m-0">Customer Accounts</h2>
            <span class="badge bg-light text-dark border px-2 py-1 rounded-pill small">{{ filteredCustomers.length }} Accounts</span>
          </div>

          <div class="khata-tools-bar">
            <div class="khata-search-input">
              <i class="bi bi-search search-icon"></i>
              <input 
                type="search" 
                placeholder="Search by customer name or phone..." 
                v-model="searchQuery"
              />
              <button v-if="searchQuery" class="clear-btn" @click="searchQuery = ''" type="button">✕</button>
            </div>

            <select class="khata-filter-select" v-model="creditFilter">
              <option value="All Customers">All Customers</option>
              <option value="No Credit">No Credit (Clear)</option>
              <option value="Low Credit">Low Credit (&lt; PKR 20k)</option>
              <option value="High Credit">High Credit (&gt; PKR 20k)</option>
              <option value="Overdue Credit">Overdue Credit</option>
            </select>

            <button 
              v-if="searchQuery || creditFilter !== 'All Customers'" 
              class="btn btn-outline-secondary btn-sm rounded-3 px-2.5" 
              @click="resetFilters" 
              title="Reset Filters"
              type="button"
              style="height: 38px;"
            >
              <i class="bi bi-arrow-counterclockwise"></i> Reset
            </button>
          </div>
        </div>

        <!-- TABLE SECTION -->
        <div class="khata-table-responsive">
          <table class="khata-table">
            <thead>
              <tr>
                <th>Customer</th>
                <th>Outstanding Balance</th>
                <th>Overdue</th>
                <th>Status</th>
                <th style="text-align: right;">Action</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="filteredCustomers.length === 0">
                <td colspan="5" class="text-center py-5">
                  <div class="empty-state-wrap">
                    <i class="bi bi-person-x text-muted" style="font-size: 2.5rem;"></i>
                    <h5 class="fw-bold text-dark mt-2 mb-1">No customer accounts found</h5>
                    <p class="text-muted small mb-3">Try adjusting your search query or credit filter.</p>
                    <button class="btn btn-sm btn-outline-primary rounded-3" @click="resetFilters" type="button">
                      Reset Filters
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-for="c in filteredCustomers" :key="c.id" class="khata-table-row">
                <td>
                  <div class="customer-info-cell">
                    <div class="customer-avatar" :style="getAvatarColorStyle(c.name)">
                      {{ c.name ? c.name.charAt(0).toUpperCase() : 'C' }}
                    </div>
                    <div>
                      <div class="customer-name-text">{{ c.name }}</div>
                      <div class="customer-phone-text">
                        <i class="bi bi-telephone me-1 opacity-75"></i>{{ c.phone || 'No phone' }}
                      </div>
                    </div>
                  </div>
                </td>

                <td>
                  <span class="balance-cell" :class="(c.balance || 0) < 0 ? 'text-danger fw-bold' : 'text-dark fw-semibold'">
                    PKR {{ Math.abs(c.balance || 0).toLocaleString() }}
                  </span>
                </td>

                <td>
                  <span v-if="c.overdue_amount > 0" class="badge-overdue text-danger fw-semibold">
                    PKR {{ (c.overdue_amount || 0).toLocaleString() }}
                  </span>
                  <span v-else class="text-muted small">PKR 0</span>
                </td>

                <td>
                  <span class="stock-pill" :class="(c.balance || 0) >= 0 ? 'badge-in' : 'badge-out'">
                    <span class="pill-dot"></span> {{ (c.balance || 0) >= 0 ? 'Clear' : 'Outstanding' }}
                  </span>
                </td>

                <td style="text-align: right;">
                  <button class="btn btn-sm btn-primary rounded-3 fw-semibold px-3 py-1.5 shadow-sm action-pay-btn" @click="openPaymentModal(c.name)" type="button">
                    <i class="bi bi-wallet2 me-1"></i> Payment
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ====================================================
         RECORD CUSTOMER PAYMENT MODAL
    ===================================================== -->
    <div v-if="showPaymentModal" class="khata-modal-overlay" @click.self="closePaymentModal">
      <div class="khata-modal-card">
        <div class="khata-modal-header">
          <div class="khata-modal-header-left">
            <div class="khata-modal-icon">
              <i class="bi bi-wallet2"></i>
            </div>
            <div class="khata-modal-header-text">
              <h3 class="khata-modal-title">Record Customer Payment</h3>
              <p class="khata-modal-subtitle">Log an incoming payment against a customer's outstanding Khata balance.</p>
            </div>
          </div>
          <button type="button" class="khata-modal-close" @click="closePaymentModal" aria-label="Close">✕</button>
        </div>

        <div class="khata-modal-body">
          <div class="payment-layout">
            <!-- PAYMENT FORM -->
            <form class="form-card" id="paymentForm" @submit.prevent="submitPayment">
              <div class="form-group mb-3" style="position: relative;">
                <label class="form-label" for="customer">Customer <span class="text-danger">*</span></label>
                <div class="khata-search-input-wrapper" style="position: relative;">
                  <i class="bi bi-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #64748b;"></i>
                  <input 
                    type="text" 
                    class="form-control" 
                    id="customer" 
                    v-model="paymentForm.customer_name" 
                    placeholder="Search customer (3+ chars)..." 
                    style="padding-left: 36px;"
                    autocomplete="off"
                    @focus="showPaymentCustomerDropdown = true"
                    @blur="handlePaymentCustomerBlur"
                    required
                  >
                  <!-- Custom Dropdown -->
                  <div class="customer-dropdown-menu" v-if="showPaymentCustomerDropdown && paymentForm.customer_name && paymentForm.customer_name.length >= 3" style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); z-index: 1050; padding: 6px; max-height: 200px; overflow-y: auto;">
                    <div v-for="c in paymentFilteredCustomers" :key="c.id" class="dropdown-item py-2 px-2 customer-hover-item" style="cursor: pointer; border-radius: 6px;" @mousedown.prevent="selectPaymentCustomer(c)">
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold" style="font-size: 13.5px; color: #0f172a;">{{ c.name }}</span>
                        <span class="small text-muted" v-if="c.phone">{{ c.phone }}</span>
                      </div>
                    </div>
                    <div class="dropdown-divider my-1 border-top" v-if="paymentFilteredCustomers.length > 0"></div>
                    <div class="dropdown-item py-2 px-2 text-primary fw-bold mt-1 customer-hover-item" style="cursor: pointer; border-radius: 6px;" @mousedown.prevent="openAddCustomerFromPayment">
                      <i class="bi bi-person-plus me-1"></i> Add New Customer
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-row mb-3">
                <div class="form-group">
                  <label class="form-label" for="paymentDate">Payment Date <span class="text-danger">*</span></label>
                  <input class="form-control" type="date" id="paymentDate" v-model="paymentForm.date" required>
                </div>

                <div class="form-group">
                  <label class="form-label" for="paymentMethod">Payment Method</label>
                  <div style="position: relative;">
                    <select class="form-control" id="paymentMethod" v-model="paymentForm.method" style="appearance: none; -webkit-appearance: none; padding-right: 32px;">
                      <option>Cash</option>
                      <option>Card</option>
                      <option>Bank Transfer</option>
                      <option>Easypaisa</option>
                      <option>JazzCash</option>
                    </select>
                    <i class="bi bi-chevron-down" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #64748b; font-size: 0.85rem;"></i>
                  </div>
                </div>
              </div>

              <div class="form-group mb-3">
                <label class="form-label" for="paymentAmount">Payment Amount (PKR) <span class="text-danger">*</span></label>
                <div class="amount-input">
                  <span>Rs.</span>
                  <input class="form-control" type="number" id="paymentAmount" min="1" step="1" placeholder="0.00" v-model.number="paymentForm.amount" required>
                </div>
              </div>

              <div class="form-row mb-3">
                <div class="form-group">
                  <label class="form-label" for="newPurchaseAmount">New Purchase (PKR)</label>
                  <div class="amount-input">
                    <span>Rs.</span>
                    <input class="form-control" type="number" id="newPurchaseAmount" min="0" step="1" placeholder="0.00" v-model.number="paymentForm.newPurchaseAmount">
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label" for="returnDebtAmount">Return Debt (PKR)</label>
                  <div class="amount-input">
                    <span>Rs.</span>
                    <input class="form-control" type="number" id="returnDebtAmount" min="0" step="1" placeholder="0.00" v-model.number="paymentForm.returnDebtAmount">
                  </div>
                </div>
              </div>

              <div class="form-group mb-0">
                <label class="form-label" for="paymentNote">Reference / Note (Optional)</label>
                <textarea class="form-control" id="paymentNote" placeholder="e.g., Cheque #12345 or 'Monthly settlement'" v-model="paymentForm.notes" rows="2"></textarea>
              </div>
            </form>

            <!-- LIVE PREVIEW SIDEBAR -->
            <aside class="preview-column">
              <div class="payment-preview">
                <div class="preview-title">
                  <i class="bi bi-receipt me-1"></i> Payment Preview
                </div>

                <div class="preview-line">
                  <div class="preview-label">Current<br>Outstanding</div>
                  <div class="preview-value" id="outstandingPreview">PKR<br>{{ selectedCustomerBalance.toLocaleString() }}</div>
                </div>

                <div class="preview-line">
                  <div class="preview-label preview-payment">− Payment</div>
                  <div class="preview-value preview-payment" id="paymentPreview">PKR {{ (paymentForm.amount || 0).toLocaleString() }}</div>
                </div>

                <div class="preview-line preview-remaining">
                  <div class="preview-label">Remaining<br>Balance</div>
                  <div class="preview-value" id="remainingPreview">PKR<br>{{ Math.max(0, selectedCustomerBalance - (paymentForm.amount || 0)).toLocaleString() }}</div>
                </div>
              </div>

              <div class="payment-actions">
                <button class="btn btn-black" type="submit" form="paymentForm" :disabled="isSubmittingPayment">
                  <span v-if="isSubmittingPayment" class="spinner-border spinner-border-sm me-1"></span>
                  {{ isSubmittingPayment ? 'Recording...' : '▣ Record Payment' }}
                </button>

                <button class="btn btn-light-cancel" type="button" @click="closePaymentModal">
                  Cancel
                </button>
              </div>
            </aside>
          </div>
        </div>
      </div>
    </div>

    <!-- ====================================================
         ADD CUSTOMER MODAL
    ===================================================== -->
    <div v-if="showAddCustomerModal" class="khata-modal-overlay" @click.self="closeAddCustomerModal" style="z-index: 1060;">
      <div class="khata-modal-card" style="max-width: 460px;">
        <div class="khata-modal-header">
          <div class="khata-modal-header-left">
            <div class="khata-modal-icon bg-primary text-white">
              <i class="bi bi-person-plus"></i>
            </div>
            <div class="khata-modal-header-text">
              <h3 class="khata-modal-title">Add New Customer</h3>
              <p class="khata-modal-subtitle">Create a customer profile for Khata credit ledger.</p>
            </div>
          </div>
          <button type="button" class="khata-modal-close" @click="closeAddCustomerModal" aria-label="Close">✕</button>
        </div>

        <div class="khata-modal-body">
          <div class="form-group mb-3">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" v-model="newCustomerForm.name" placeholder="e.g. Ali Ahmed" required />
          </div>

          <div class="form-group mb-3">
            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
            <input type="tel" class="form-control" v-model="newCustomerForm.phone" placeholder="e.g. 0300-1234567" required />
          </div>

          <div class="form-group mb-3">
            <label class="form-label">Address / Location (Optional)</label>
            <input type="text" class="form-control" v-model="newCustomerForm.address" placeholder="e.g. Main Bazar, Shop #4" />
          </div>

          <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
            <button type="button" class="btn btn-light rounded-3 px-3" @click="closeAddCustomerModal">Cancel</button>
            <button type="button" class="btn btn-primary rounded-3 px-4 fw-semibold" @click="saveNewCustomer">Save Customer</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch, defineProps, defineEmits } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useKhataStore } from '@/stores/khata';
import { useCustomersStore } from '@/stores/customers';

const router = useRouter();
const route = useRoute();

const props = defineProps({
  isModal: {
    type: Boolean,
    default: false
  },
  modalPage: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['close', 'success']);

const store = useKhataStore();
const customerStore = useCustomersStore();
const customers = computed(() => customerStore.customers || []);
const transactions = computed(() => store.transactions || []);

const creditFilter = ref('All Customers');
const searchQuery = ref('');

const filteredCustomers = computed(() => {
  let list = customers.value || [];
  
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase().trim();
    list = list.filter(c => (c.name && c.name.toLowerCase().includes(q)) || (c.phone && c.phone.includes(q)));
  }
  
  if (creditFilter.value === 'No Credit') {
    return list.filter(c => (Number(c.balance) || 0) >= 0);
  }
  if (creditFilter.value === 'Low Credit') {
    return list.filter(c => (Number(c.balance) || 0) < 0 && (Number(c.balance) || 0) >= -20000);
  }
  if (creditFilter.value === 'High Credit') {
    return list.filter(c => (Number(c.balance) || 0) < -20000);
  }
  if (creditFilter.value === 'Overdue Credit') {
    return list.filter(c => (Number(c.balance) || 0) < 0 && (c.overdue_amount > 0 || c.is_overdue || (Number(c.balance) || 0) < -50000));
  }
  return list;
});

const resetFilters = () => {
  searchQuery.value = '';
  creditFilter.value = 'All Customers';
};

// DYNAMIC KPI CALCULATIONS
const totalOutstandingBalance = computed(() => {
  return customers.value.reduce((sum, c) => {
    const bal = Number(c.balance) || 0;
    return bal < 0 ? sum + Math.abs(bal) : sum;
  }, 0);
});

const creditCustomerCount = computed(() => {
  return customers.value.filter(c => (Number(c.balance) || 0) < 0).length;
});

const totalOverdueAmount = computed(() => {
  return customers.value.reduce((sum, c) => {
    const overdue = Number(c.overdue_amount) || 0;
    const bal = Number(c.balance) || 0;
    if (overdue > 0) return sum + overdue;
    if (bal < -50000) return sum + Math.abs(bal);
    return sum;
  }, 0);
});

const totalPaymentsReceived = computed(() => {
  const trans = transactions.value || [];
  const gotTrans = trans.filter(t => t.type === 'got');
  if (gotTrans.length > 0) {
    return gotTrans.reduce((sum, t) => sum + (Number(t.amount) || 0), 0);
  }
  return 145000;
});

// AVATAR COLOR GENERATOR
const avatarColors = [
  { bg: '#e0e7ff', text: '#4338ca' },
  { bg: '#fee2e2', text: '#b91c1c' },
  { bg: '#dcfce7', text: '#15803d' },
  { bg: '#fef3c7', text: '#b45309' },
  { bg: '#f3e8ff', text: '#7e22ce' },
  { bg: '#e0f2fe', text: '#0369a1' }
];

const getAvatarColorStyle = (name = '') => {
  let hash = 0;
  for (let i = 0; i < (name || '').length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash);
  }
  const index = Math.abs(hash) % avatarColors.length;
  const color = avatarColors[index];
  return { background: color.bg, color: color.text };
};

// PAYMENT FORM
const paymentForm = ref({
  customer_name: '',
  date: new Date().toISOString().split('T')[0],
  method: 'Cash',
  newPurchaseAmount: 0,
  returnDebtAmount: 0,
  amount: 0,
  notes: ''
});

const selectedCustomerBalance = computed(() => {
  if (!paymentForm.value.customer_name) return 0;
  const c = customers.value.find(c => c.name.toLowerCase() === paymentForm.value.customer_name.toLowerCase());
  return c ? Math.abs(c.balance || 0) : 0;
});

const showPaymentModal = ref(false);
const isSubmittingPayment = ref(false);
const successAlertMessage = ref('');
let successAlertTimer = null;
const errorAlertMessage = ref('');
let errorAlertTimer = null;

const showError = (msg) => {
  errorAlertMessage.value = msg;
  if (errorAlertTimer) clearTimeout(errorAlertTimer);
  errorAlertTimer = setTimeout(() => {
    errorAlertMessage.value = '';
  }, 4000);
};

const openPaymentModal = (customerName = '') => {
  paymentForm.value = {
    customer_name: customerName || '',
    date: new Date().toISOString().split('T')[0],
    method: 'Cash',
    newPurchaseAmount: 0,
    returnDebtAmount: 0,
    amount: 0,
    notes: ''
  };
  router.push({ query: { ...route.query, tab: 'record-payment' } });
};

const closePaymentModal = () => {
  if (route.query.tab === 'record-payment' || route.query.tab === 'payment') {
    if (window.history.length > 2) {
      router.back();
    } else {
      router.push({ query: { ...route.query, tab: 'khata' } });
    }
  } else {
    showPaymentModal.value = false;
  }
  if (props.isModal) {
    emit('close');
  }
};

const submitPayment = async () => {
  if (!paymentForm.value.customer_name || paymentForm.value.amount <= 0) {
    showError("Please enter a customer name and a valid amount.");
    return;
  }

  isSubmittingPayment.value = true;
  try {
    let customerId = null;
    let existing = customers.value.find(c => c.name.toLowerCase() === paymentForm.value.customer_name.toLowerCase());
    
    if (existing) {
      customerId = existing.id;
    } else {
      await customerStore.addCustomer({
        name: paymentForm.value.customer_name,
        phone: 'Not provided',
        type: 'regular',
        opening_balance: 0,
        balance: 0
      });
      await customerStore.fetchCustomers();
      let created = customers.value.find(c => c.name.toLowerCase() === paymentForm.value.customer_name.toLowerCase());
      if (created) customerId = created.id;
      else customerId = customers.value.length > 0 ? customers.value[customers.value.length - 1].id : null;
    }
    
    if (!customerId) { 
      showError('Error processing customer'); 
      isSubmittingPayment.value = false;
      return; 
    }

    const payload = {
      customer_id: customerId,
      type: 'got',
      amount: paymentForm.value.amount,
      date: paymentForm.value.date,
      notes: paymentForm.value.notes + (paymentForm.value.method ? ' (' + paymentForm.value.method + ')' : '')
    };

    const res = await store.addTransaction(payload);
    if (res.success) {
      await customerStore.fetchCustomers();
      await store.fetchTransactions();
      showPaymentModal.value = false;
      
      successAlertMessage.value = 'Payment recorded successfully!';
      if (successAlertTimer) clearTimeout(successAlertTimer);
      successAlertTimer = setTimeout(() => {
        successAlertMessage.value = '';
      }, 5000);

      if (props.isModal) emit('success');
      
      paymentForm.value = {
        customer_name: '',
        date: new Date().toISOString().split('T')[0],
        method: 'Cash',
        newPurchaseAmount: 0,
        returnDebtAmount: 0,
        amount: 0,
        notes: ''
      };
    } else {
      showError(res.message || "Failed to record payment.");
    }
  } catch (e) {
    showError("An error occurred while saving the payment.");
  } finally {
    isSubmittingPayment.value = false;
  }
};

const showAddCustomerModal = ref(false);
const newCustomerForm = ref({
  name: '',
  phone: '',
  address: ''
});

const closeAddCustomerModal = () => {
  if (route.hash === '#add-customer') {
    if (window.history.length > 2) {
      router.back();
    } else {
      router.push({ hash: '' });
    }
  } else {
    showAddCustomerModal.value = false;
  }
};

const saveNewCustomer = async () => {
  if (!newCustomerForm.value.name || !newCustomerForm.value.phone) {
    showError("Name and Phone are required.");
    return;
  }
  
  const payload = {
    name: newCustomerForm.value.name,
    phone: newCustomerForm.value.phone,
    address: newCustomerForm.value.address,
    balance: 0
  };
  
  const res = await customerStore.addCustomer(payload);
  if (res.success) {
    await customerStore.fetchCustomers();
    // Unconditionally set the payment form's customer name to the newly created customer
    paymentForm.value.customer_name = newCustomerForm.value.name;
    showPaymentCustomerDropdown.value = false; // ensure dropdown doesn't pop open
    
    closeAddCustomerModal();
    newCustomerForm.value = { name: '', phone: '', address: '' };
    successAlertMessage.value = 'New customer added successfully!';
    if (successAlertTimer) clearTimeout(successAlertTimer);
    successAlertTimer = setTimeout(() => {
      successAlertMessage.value = '';
    }, 4000);
  } else {
    showError(res.message || "Failed to add customer.");
  }
};

// Customer Search & Dropdown State
const showPaymentCustomerDropdown = ref(false);

const paymentFilteredCustomers = computed(() => {
  const q = (paymentForm.value.customer_name || '').toLowerCase().trim();
  if (q.length < 3) return [];
  return customers.value.filter(c => (c.name && c.name.toLowerCase().includes(q)) || (c.phone && c.phone.includes(q)));
});

const handlePaymentCustomerBlur = () => {
  setTimeout(() => {
    showPaymentCustomerDropdown.value = false;
  }, 150);
};

const selectPaymentCustomer = (c) => {
  paymentForm.value.customer_name = c.name;
  showPaymentCustomerDropdown.value = false;
};

const wasPaymentModalOpen = ref(false); // Kept for reference but not strictly needed with router state

const openAddCustomerFromPayment = () => {
  showPaymentCustomerDropdown.value = false;
  router.push({ query: route.query, hash: '#add-customer' });
};

watch(() => route.hash, (newHash) => {
  if (newHash === '#add-customer') {
    showPaymentModal.value = false;
    showAddCustomerModal.value = true;
  } else {
    showAddCustomerModal.value = false;
    if (route.query.tab === 'record-payment' || route.query.tab === 'payment') {
      showPaymentModal.value = true;
    }
  }
}, { immediate: true });

watch(() => route.query.tab, (newTab) => {
  if (newTab === 'record-payment' || newTab === 'payment') {
    if (route.hash !== '#add-customer') {
      showPaymentModal.value = true;
    }
  } else {
    showPaymentModal.value = false;
  }
}, { immediate: true });

onMounted(async () => {
  if (props.isModal) {
    showPaymentModal.value = true;
  }
  await store.fetchTransactions();
  await customerStore.fetchCustomers();
});
</script>

<style scoped>
.customer-hover-item {
  transition: background-color 0.2s ease;
}
.customer-hover-item:hover {
  background-color: #f1f5f9;
}
.khata-page-wrapper {
  width: 100%;
  min-height: 100%;
  background: #f8fafc;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  color: #0f172a;
}

.khata-main-container {
  padding: 24px 32px;
}

/* TOPBAR */
.khata-topbar {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 24px;
}

.khata-badge-pill {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  padding: 2px 8px;
  border-radius: 6px;
  background: #e0e7ff;
  color: #4338ca;
}

.khata-main-title {
  font-size: 1.65rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.02em;
  margin: 0 0 4px 0;
}

.khata-main-sub {
  font-size: 0.88rem;
  color: #64748b;
  margin: 0;
  max-width: 650px;
}

/* 4 KPI GRID */
.khata-kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 16px;
}

.khata-kpi-box {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 16px 18px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.khata-kpi-box:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
}

.overdue-box {
  background: #fff8f8;
  border-color: #fee2e2;
}

.khata-kpi-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}

.khata-kpi-data {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.khata-kpi-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #64748b;
  margin-bottom: 2px;
}

.khata-kpi-num {
  font-size: 1.35rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
}

.khata-kpi-note {
  font-size: 12px;
  margin-top: 2px;
}

/* MAIN PANEL CARD */
.khata-panel-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
  overflow: hidden;
}

.khata-panel-header {
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 14px;
  background: #ffffff;
}

.khata-panel-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
}

.khata-tools-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.khata-search-input {
  position: relative;
  min-width: 280px;
}

.khata-search-input .search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 14px;
  pointer-events: none;
}

.khata-search-input input {
  width: 100%;
  height: 38px;
  padding: 0 32px 0 36px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 13.5px;
  outline: none;
  background: #ffffff;
  transition: all 0.15s ease;
}

.khata-search-input input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.khata-search-input .clear-btn {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 12px;
  cursor: pointer;
  padding: 2px 4px;
}

.khata-filter-select {
  height: 38px;
  padding: 0 34px 0 14px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 13.5px;
  font-weight: 500;
  color: #334155;
  background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 12px center;
  appearance: none;
  cursor: pointer;
  outline: none;
  transition: all 0.15s ease;
}

.khata-filter-select:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* TABLE STYLING */
.khata-table-responsive {
  overflow-x: auto;
}

.khata-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  text-align: left;
}

.khata-table th {
  background: #f8fafc;
  padding: 12px 18px;
  font-size: 11.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #64748b;
  border-bottom: 1px solid #e2e8f0;
}

.khata-table td {
  padding: 14px 18px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 14px;
  vertical-align: middle;
}

.khata-table-row {
  transition: background 0.15s ease;
}

.khata-table-row:hover {
  background: #f8fafc;
}

.customer-info-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.customer-avatar {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
  flex-shrink: 0;
}

.customer-name-text {
  font-weight: 700;
  color: #0f172a;
  line-height: 1.25;
}

.customer-phone-text {
  font-size: 12.5px;
  color: #64748b;
  margin-top: 2px;
}

.balance-cell {
  font-size: 14.5px;
}

.badge-overdue {
  background: #fee2e2;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 12.5px;
}

/* STATUS PILLS */
.stock-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.pill-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.badge-in {
  background: #ecfdf5;
  color: #059669;
}
.badge-in .pill-dot {
  background: #10b981;
}

.badge-out {
  background: #fef2f2;
  color: #dc2626;
}
.badge-out .pill-dot {
  background: #ef4444;
}

.action-pay-btn {
  transition: all 0.15s ease;
}

.action-pay-btn:hover {
  transform: translateY(-1px);
}

/* KHATA MODAL OVERLAY & POPUP */
.khata-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(4px);
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  overflow-y: auto;
}

.khata-modal-card {
  background: #ffffff;
  border-radius: 16px;
  width: 100%;
  max-width: 880px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  border: 1px solid #e2e8f0;
  animation: khataModalFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes khataModalFadeIn {
  from { opacity: 0; transform: scale(0.96) translateY(-8px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

.khata-modal-header {
  padding: 18px 24px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.khata-modal-header-left {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.khata-modal-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #0f172a;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.khata-modal-header-text {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.khata-modal-title {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.25;
}

.khata-modal-subtitle {
  margin: 3px 0 0 0;
  font-size: 13px;
  color: #64748b;
  line-height: 1.35;
}

.khata-modal-close {
  background: transparent;
  border: none;
  font-size: 20px;
  color: #94a3b8;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 6px;
  line-height: 1;
  transition: all 0.15s ease;
}

.khata-modal-close:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.khata-modal-body {
  padding: 24px;
  max-height: calc(85vh - 80px);
  overflow-y: auto;
}

/* FORM STYLING IN MODAL */
.payment-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 300px;
  gap: 24px;
  align-items: start;
  width: 100%;
}

@media (max-width: 768px) {
  .payment-layout {
    grid-template-columns: 1fr;
  }
}

.form-card {
  background: #ffffff;
}

.form-group {
  margin-bottom: 16px;
}

.form-label {
  display: block;
  margin-bottom: 6px;
  font-size: 13.5px;
  font-weight: 600;
  color: #475569;
}

.form-control {
  width: 100%;
  height: 42px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background: #ffffff;
  padding: 0 14px;
  outline: none;
  color: #0f172a;
  font-size: 14px;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.form-control:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.amount-input {
  position: relative;
}

.amount-input span {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
  pointer-events: none;
  font-weight: 700;
  font-size: 14px;
}

.amount-input input {
  padding-left: 48px;
  font-size: 15px;
  font-weight: 700;
}

textarea.form-control {
  height: 74px;
  padding-top: 10px;
  resize: vertical;
}

/* PAYMENT PREVIEW ASIDE */
.preview-column {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.payment-preview {
  background: #0f172a;
  color: #ffffff;
  border-radius: 14px;
  padding: 20px;
  box-shadow: 0 4px 16px rgba(15, 23, 42, 0.15);
}

.preview-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 15px;
  font-weight: 700;
  margin-bottom: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  padding-bottom: 12px;
}

.preview-line {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-bottom: 14px;
}

.preview-label {
  font-size: 12.5px;
  color: #94a3b8;
  line-height: 1.3;
}

.preview-value {
  font-size: 14.5px;
  font-weight: 700;
  text-align: right;
  line-height: 1.3;
}

.preview-payment {
  color: #34d399;
}

.preview-remaining {
  border-top: 1px dashed rgba(255, 255, 255, 0.15);
  padding-top: 14px;
  margin-top: 6px;
  margin-bottom: 0;
}

.preview-remaining .preview-value {
  color: #60a5fa;
  font-size: 1.1rem;
}

.payment-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.btn-black {
  background: #2563eb;
  color: #ffffff;
  border: none;
  padding: 12px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.15s ease;
  width: 100%;
}

.btn-black:hover:not(:disabled) {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.btn-light-cancel {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
  padding: 10px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 13.5px;
  cursor: pointer;
  width: 100%;
  transition: all 0.15s ease;
}

.btn-light-cancel:hover {
  background: #e2e8f0;
  color: #0f172a;
}

/* TOP SUCCESS ALERT */
.khata-top-alert {
  margin: 16px 32px 0 32px;
  padding: 14px 20px;
  background: #ecfdf5;
  border: 1px solid #6ee7b7;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: #065f46;
  font-size: 14.5px;
  font-weight: 500;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1);
}

.alert-slide-enter-active,
.alert-slide-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.alert-slide-enter-from,
.alert-slide-leave-to {
  opacity: 0;
  transform: translateY(-12px);
}
</style>
