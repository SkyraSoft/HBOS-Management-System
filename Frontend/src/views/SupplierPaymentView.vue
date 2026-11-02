<template>
  <div class="supplierpayment-page-wrapper">
    <!-- TOP NOTIFICATION ALERT -->
    <transition name="alert-slide">
      <div v-if="alertMessage" :class="['supplier-top-alert', alertType === 'error' ? 'alert-danger' : 'alert-success']" role="alert">
        <div class="d-flex align-items-center gap-2">
          <i :class="['bi fs-5', alertType === 'error' ? 'bi-exclamation-triangle-fill text-danger' : 'bi-check-circle-fill text-success']"></i>
          <div>
            <strong>{{ alertType === 'error' ? 'Notice:' : 'Success!' }}</strong> {{ alertMessage }}
          </div>
        </div>
        <button type="button" class="btn-close" @click="alertMessage = ''" aria-label="Close"></button>
      </div>
    </transition>

    <div class="supplier-main-container">
      <!-- PAGE HEADER -->
      <div class="supplier-topbar">
        <div>
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="supplier-badge-pill">Procurement & Disbursements</span>
            <span class="text-muted small">• Settlements & Allocation</span>
          </div>
          <h1 class="supplier-main-title">Supplier Payments & Settlements</h1>
          <p class="supplier-main-sub">
            Record supplier settlements, allocate payments to purchase invoices, deduct financial accounts, and manage payment reversals.
          </p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
          <button 
            type="button" 
            class="btn btn-primary btn-sm rounded-3 fw-semibold px-3 shadow-sm d-flex align-items-center gap-2"
            id="recordPaymentButton"
            style="height: 40px;"
            @click="openPaymentModal()"
          >
            <i class="bi bi-credit-card"></i>
            Record Payment
          </button>
        </div>
      </div>

      <!-- 4 KPI METRIC CARDS -->
      <section class="supplier-kpi-grid mb-4">
        <!-- 1. Total Purchases -->
        <div class="supplier-kpi-box">
          <div class="supplier-kpi-icon bg-primary-subtle text-primary">
            <i class="bi bi-bag-check"></i>
          </div>
          <div class="supplier-kpi-data">
            <span class="supplier-kpi-label">Total Purchases</span>
            <div class="supplier-kpi-num">PKR {{ totalPurchases.toLocaleString() }}</div>
            <span class="supplier-kpi-note text-muted">Billed procurement volume</span>
          </div>
        </div>

        <!-- 2. Active Suppliers -->
        <div class="supplier-kpi-box">
          <div class="supplier-kpi-icon bg-info-subtle text-info">
            <i class="bi bi-truck"></i>
          </div>
          <div class="supplier-kpi-data">
            <span class="supplier-kpi-label">Active Suppliers</span>
            <div class="supplier-kpi-num">{{ suppliers.length }}</div>
            <span class="supplier-kpi-note text-success fw-medium">● Active Network</span>
          </div>
        </div>

        <!-- 3. Outstanding Payables -->
        <div class="supplier-kpi-box" :class="{ 'overdue-box': totalPayables > 0 }">
          <div class="supplier-kpi-icon bg-danger-subtle text-danger">
            <i class="bi bi-credit-card-2-back"></i>
          </div>
          <div class="supplier-kpi-data">
            <span class="supplier-kpi-label">Total Payables</span>
            <div class="supplier-kpi-num" :class="totalPayables > 0 ? 'text-danger' : 'text-dark'">
              PKR {{ totalPayables.toLocaleString() }}
            </div>
            <span class="supplier-kpi-note" :class="totalPayables > 0 ? 'text-danger-emphasis' : 'text-muted'">
              {{ totalPayables > 0 ? 'Unsettled liabilities' : 'All accounts clear' }}
            </span>
          </div>
        </div>

        <!-- 4. Total Settled -->
        <div class="supplier-kpi-box">
          <div class="supplier-kpi-icon bg-success-subtle text-success">
            <i class="bi bi-cash-stack"></i>
          </div>
          <div class="supplier-kpi-data">
            <span class="supplier-kpi-label">Total Settled</span>
            <div class="supplier-kpi-num text-success">PKR {{ totalSettled.toLocaleString() }}</div>
            <span class="supplier-kpi-note text-muted">Cumulative disbursements</span>
          </div>
        </div>
      </section>

      <!-- SUPPLIER SELECTOR & ACTIVE PROFILE BANNER -->
      <section class="supplier-profile-hero-card mb-4" v-if="selectedSupplier">
        <div class="profile-hero-header">
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="supplier-large-avatar" :style="getAvatarColorStyle(selectedSupplier.name)">
              {{ selectedSupplier.name ? selectedSupplier.name.charAt(0).toUpperCase() : 'S' }}
            </div>
            <div>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <h2 class="supplier-hero-name m-0">{{ selectedSupplier.name }}</h2>
                <span class="badge-status-active">
                  <span class="pill-dot"></span> {{ (selectedSupplier.balance || 0) > 0 ? 'Outstanding Due' : 'All Settled' }}
                </span>
              </div>
              <p class="supplier-hero-sub m-0">
                <span class="text-muted">Supplier Code:</span> <strong class="text-dark">{{ selectedSupplier.code || ('SUP-' + String(selectedSupplier.id).padStart(4, '0')) }}</strong>
                <span class="mx-2">•</span>
                <span class="text-muted">Phone:</span> <strong class="text-dark">{{ selectedSupplier.phone || 'N/A' }}</strong>
              </p>
            </div>
          </div>

          <div class="hero-actions-bar">
            <select class="select-supplier-dropdown" v-model="selectedSupplierId" @change="handleSupplierChange">
              <option v-for="s in suppliers" :key="s.id" :value="s.id">
                {{ s.name }} (Due: PKR {{ (Number(s.balance) || 0).toLocaleString() }})
              </option>
            </select>
            <button class="btn btn-outline-primary btn-sm rounded-3 fw-semibold px-3" @click="openPaymentModal()" type="button" style="height: 38px;">
              <i class="bi bi-plus-circle me-1"></i> Pay Supplier
            </button>
          </div>
        </div>

        <div class="profile-hero-body p-3 bg-white">
          <div class="row g-3 text-center">
            <div class="col-6 col-md-3 border-end">
              <span class="text-muted small text-uppercase fw-bold">Total Purchases</span>
              <div class="fs-5 fw-bold text-dark mt-1">PKR {{ (Number(selectedSupplier.total_purchases) || 0).toLocaleString() }}</div>
            </div>
            <div class="col-6 col-md-3 border-end">
              <span class="text-muted small text-uppercase fw-bold">Settled Amount</span>
              <div class="fs-5 fw-bold text-success mt-1">PKR {{ (Number(selectedSupplier.total_payments) || 0).toLocaleString() }}</div>
            </div>
            <div class="col-6 col-md-3 border-end">
              <span class="text-muted small text-uppercase fw-bold">Outstanding Liability</span>
              <div class="fs-5 fw-bold text-danger mt-1">PKR {{ (Number(selectedSupplier.balance) || 0).toLocaleString() }}</div>
            </div>
            <div class="col-6 col-md-3">
              <span class="text-muted small text-uppercase fw-bold">Purchase Invoices</span>
              <div class="fs-5 fw-bold text-primary mt-1">{{ supplierPurchases.length }} Orders</div>
            </div>
          </div>
        </div>
      </section>

      <!-- TWO-PANEL CONTENT: OUTSTANDING PURCHASES & RECENT DISBURSEMENTS -->
      <div class="row g-4">
        <!-- OUTSTANDING PURCHASES TABLE -->
        <div class="col-12 col-xl-6">
          <div class="supplier-panel-card h-100">
            <div class="supplier-panel-header">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-receipt-cutoff text-primary fs-5"></i>
                <h3 class="supplier-panel-title m-0">Unsettled Purchases</h3>
              </div>
              <span class="badge bg-light text-dark border px-2 py-1 rounded-pill small">
                {{ outstandingPurchases.length }} Due
              </span>
            </div>

            <div class="supplier-table-responsive">
              <table class="supplier-table">
                <thead>
                  <tr>
                    <th>Invoice / PO</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Due Amount</th>
                    <th style="text-align: right;">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="loadingPurchases">
                    <td colspan="6" class="text-center py-4 text-muted">
                      <div class="spinner-border spinner-border-sm me-2 text-primary"></div>
                      Loading purchase orders...
                    </td>
                  </tr>
                  <tr v-else-if="outstandingPurchases.length === 0">
                    <td colspan="6" class="text-center py-5">
                      <div class="empty-state-wrap">
                        <i class="bi bi-check2-circle text-success" style="font-size: 2.2rem;"></i>
                        <h6 class="fw-bold text-dark mt-2 mb-1">No Unsettled Purchases</h6>
                        <p class="text-muted small mb-0">All purchase invoices for this supplier are fully paid.</p>
                      </div>
                    </td>
                  </tr>
                  <tr v-for="p in outstandingPurchases" :key="p.id" class="supplier-table-row">
                    <td>
                      <strong class="text-primary font-monospace">PO-{{ p.id }}</strong>
                      <div class="text-muted small">{{ p.invoice_number || 'Cash/Direct' }}</div>
                    </td>
                    <td>{{ p.date || (p.created_at ? p.created_at.slice(0, 10) : '—') }}</td>
                    <td class="fw-semibold">PKR {{ (Number(p.total) || 0).toLocaleString() }}</td>
                    <td class="text-success">PKR {{ (Number(p.paid_amount) || 0).toLocaleString() }}</td>
                    <td class="text-danger fw-bold">PKR {{ (Number(p.due_amount) || 0).toLocaleString() }}</td>
                    <td style="text-align: right;">
                      <button 
                        class="btn btn-sm btn-outline-primary rounded-2 px-2.5 py-1 fw-semibold"
                        @click="openPaymentModal(p)"
                        title="Allocate payment to this purchase"
                        type="button"
                      >
                        Settle Due
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- SETTLEMENT HISTORY & REVERSALS TABLE -->
        <div class="col-12 col-xl-6">
          <div class="supplier-panel-card h-100">
            <div class="supplier-panel-header">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-clock-history text-success fs-5"></i>
                <h3 class="supplier-panel-title m-0">Payment History & Reversals</h3>
              </div>
              <span class="badge bg-light text-dark border px-2 py-1 rounded-pill small">
                {{ supplierPayments.length }} Records
              </span>
            </div>

            <div class="supplier-table-responsive">
              <table class="supplier-table">
                <thead>
                  <tr>
                    <th>TRX #</th>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Allocated PO</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="loadingPayments">
                    <td colspan="7" class="text-center py-4 text-muted">
                      <div class="spinner-border spinner-border-sm me-2 text-primary"></div>
                      Loading payment logs...
                    </td>
                  </tr>
                  <tr v-else-if="supplierPayments.length === 0">
                    <td colspan="7" class="text-center py-5">
                      <div class="empty-state-wrap">
                        <i class="bi bi-receipt text-muted" style="font-size: 2.2rem;"></i>
                        <h6 class="fw-bold text-dark mt-2 mb-1">No Payments Logged Yet</h6>
                        <p class="text-muted small mb-0">Disbursements recorded for this supplier will appear here.</p>
                      </div>
                    </td>
                  </tr>
                  <tr v-for="pay in supplierPayments" :key="pay.id" class="supplier-table-row">
                    <td>
                      <span class="font-monospace fw-bold text-dark">TRX-{{ pay.id }}</span>
                    </td>
                    <td>{{ pay.date || pay.created_at?.slice(0, 10) }}</td>
                    <td>
                      <span class="badge bg-light text-dark border text-capitalize">
                        {{ pay.payment_method || 'Cash' }}
                      </span>
                    </td>
                    <td>
                      <span v-if="pay.purchase_id" class="text-primary font-monospace fw-semibold">
                        PO-{{ pay.purchase_id }}
                      </span>
                      <span v-else class="text-muted small">General Bal</span>
                    </td>
                    <td class="fw-bold text-dark">
                      PKR {{ (Number(pay.amount) || 0).toLocaleString() }}
                    </td>
                    <td>
                      <span 
                        class="stock-pill" 
                        :class="pay.status === 'reversed' ? 'badge-out' : 'badge-in'"
                      >
                        <span class="pill-dot"></span>
                        {{ pay.status === 'reversed' ? 'Reversed' : 'Completed' }}
                      </span>
                    </td>
                    <td style="text-align: right;">
                      <button 
                        v-if="pay.status !== 'reversed'"
                        class="btn btn-sm btn-outline-danger rounded-2 px-2 py-0.5"
                        @click="openReverseModal(pay)"
                        title="Reverse this payment and restore liability & funds"
                        type="button"
                      >
                        Reverse
                      </button>
                      <span v-else class="text-muted small italic">Reversed</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ====================================================
         RECORD PAYMENT MODAL
    ===================================================== -->
    <div v-if="showPaymentModal" class="supplier-modal-overlay" @click.self="showPaymentModal = false">
      <div class="supplier-modal-card">
        <div class="supplier-modal-header">
          <div class="supplier-modal-header-left">
            <div class="supplier-modal-icon">
              <i class="bi bi-credit-card"></i>
            </div>
            <div class="supplier-modal-header-text">
              <h3 class="supplier-modal-title">Record Supplier Payment</h3>
              <p class="supplier-modal-subtitle">Post cash or bank disbursement to clear supplier liability.</p>
            </div>
          </div>
          <button type="button" class="supplier-modal-close" @click="showPaymentModal = false" aria-label="Close">✕</button>
        </div>

        <div class="supplier-modal-body">
          <form @submit.prevent="submitPayment">
            <div class="row g-3">
              <!-- Supplier Select -->
              <div class="col-12">
                <label class="form-label fw-semibold">Supplier <span class="text-danger">*</span></label>
                <select class="form-select" v-model="paymentForm.supplier_id" @change="onModalSupplierChange" required>
                  <option v-for="s in suppliers" :key="s.id" :value="s.id">
                    {{ s.name }} (Outstanding Due: PKR {{ (Number(s.balance) || 0).toLocaleString() }})
                  </option>
                </select>
              </div>

              <!-- Purchase Allocation (Optional) -->
              <div class="col-md-6">
                <label class="form-label fw-semibold">Allocate to Purchase (Optional)</label>
                <select class="form-select" v-model="paymentForm.purchase_id" @change="onModalPurchaseChange">
                  <option :value="null">-- General Account Balance --</option>
                  <option v-for="p in outstandingPurchases" :key="p.id" :value="p.id">
                    PO-{{ p.id }} (Due: PKR {{ (Number(p.due_amount) || 0).toLocaleString() }})
                  </option>
                </select>
              </div>

              <!-- Payment Amount -->
              <div class="col-md-6">
                <label class="form-label fw-semibold">Payment Amount (PKR) <span class="text-danger">*</span></label>
                <input 
                  type="number" 
                  class="form-control fw-bold" 
                  v-model.number="paymentForm.amount" 
                  min="0.01" 
                  step="0.01" 
                  required 
                />
              </div>

              <!-- Financial Account -->
              <div class="col-md-6">
                <label class="form-label fw-semibold">Deduct From Account <span class="text-danger">*</span></label>
                <select class="form-select" v-model="paymentForm.account_id" required>
                  <option v-for="acc in financialAccounts" :key="acc.id" :value="acc.id">
                    {{ acc.name }} ({{ acc.type }}) — Bal: PKR {{ (Number(acc.balance) || 0).toLocaleString() }}
                  </option>
                </select>
                <div class="form-text text-muted small">Account balance will be deducted atomically.</div>
              </div>

              <!-- Payment Method -->
              <div class="col-md-6">
                <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                <select class="form-select" v-model="paymentForm.payment_method" required>
                  <option value="cash">Cash</option>
                  <option value="bank">Bank Transfer</option>
                  <option value="cheque">Cheque</option>
                  <option value="online_transfer">Online Transfer</option>
                </select>
              </div>

              <!-- Date -->
              <div class="col-md-6">
                <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" v-model="paymentForm.date" required />
              </div>

              <!-- Notes -->
              <div class="col-md-6">
                <label class="form-label fw-semibold">Reference / Notes</label>
                <input type="text" class="form-control" v-model="paymentForm.notes" placeholder="e.g. Cheque #4421, Slip #88" />
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
              <button type="button" class="btn btn-light rounded-3 px-3" @click="showPaymentModal = false">Cancel</button>
              <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold" :disabled="isSubmittingPayment">
                <span v-if="isSubmittingPayment" class="spinner-border spinner-border-sm me-1"></span>
                Record Settlement
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ====================================================
         REVERSE PAYMENT CONFIRMATION MODAL
    ===================================================== -->
    <div v-if="showReverseModal" class="supplier-modal-overlay" @click.self="showReverseModal = false">
      <div class="supplier-modal-card" style="max-width: 480px;">
        <div class="supplier-modal-header">
          <div class="supplier-modal-header-left">
            <div class="supplier-modal-icon bg-danger-subtle text-danger">
              <i class="bi bi-arrow-counterclockwise"></i>
            </div>
            <div class="supplier-modal-header-text">
              <h3 class="supplier-modal-title">Reverse Supplier Payment</h3>
              <p class="supplier-modal-subtitle">Undo disbursement and restore account balance.</p>
            </div>
          </div>
          <button type="button" class="supplier-modal-close" @click="showReverseModal = false" aria-label="Close">✕</button>
        </div>

        <div class="supplier-modal-body" v-if="paymentToReverse">
          <div class="alert alert-warning mb-3">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Are you sure you want to reverse <strong>TRX-{{ paymentToReverse.id }}</strong> for 
            <strong>PKR {{ (Number(paymentToReverse.amount) || 0).toLocaleString() }}</strong>?
          </div>

          <p class="text-muted small">
            This action will:
            <ul class="mb-0 ps-3">
              <li>Restore the supplier's liability balance.</li>
              <li>Reopen allocated purchase order due amount (if any).</li>
              <li>Post a compensating inflow to restore the financial account drawer balance.</li>
            </ul>
          </p>

          <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
            <button type="button" class="btn btn-light rounded-3 px-3" @click="showReverseModal = false">Cancel</button>
            <button 
              type="button" 
              class="btn btn-danger rounded-3 px-4 fw-semibold" 
              :disabled="isReversingPayment"
              @click="confirmReversePayment"
            >
              <span v-if="isReversingPayment" class="spinner-border spinner-border-sm me-1"></span>
              Confirm Reversal
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useSuppliersStore } from '@/stores/suppliers';
import api from '@/api';

const store = useSuppliersStore();
const suppliers = computed(() => store.suppliers || []);

// Active selected supplier
const selectedSupplierId = ref(null);
const selectedSupplier = computed(() => {
  if (suppliers.value.length === 0) return null;
  if (selectedSupplierId.value) {
    const s = suppliers.value.find(item => item.id === selectedSupplierId.value);
    if (s) return s;
  }
  return suppliers.value[0];
});

// Purchases & Payments data
const supplierPurchases = ref([]);
const supplierPayments = ref([]);
const financialAccounts = ref([]);
const loadingPurchases = ref(false);
const loadingPayments = ref(false);

// Alert Notification
const alertMessage = ref('');
const alertType = ref('success');
let alertTimer = null;
const showAlert = (msg, type = 'success') => {
  alertMessage.value = msg;
  alertType.value = type;
  if (alertTimer) clearTimeout(alertTimer);
  alertTimer = setTimeout(() => {
    alertMessage.value = '';
  }, 4500);
};

// Computed KPIs
const totalPurchases = computed(() => {
  return suppliers.value.reduce((sum, s) => sum + (Number(s.total_purchases) || 0), 0);
});

const totalPayables = computed(() => {
  return suppliers.value.reduce((sum, s) => sum + (Number(s.balance) || 0), 0);
});

const totalSettled = computed(() => {
  return suppliers.value.reduce((sum, s) => sum + (Number(s.total_payments) || 0), 0);
});

// Outstanding purchases for selected supplier
const outstandingPurchases = computed(() => {
  return (supplierPurchases.value || []).filter(p => (Number(p.due_amount) || 0) > 0);
});

// Fetch financial accounts
const fetchFinancialAccounts = async () => {
  try {
    const res = await api.get('/financial-accounts');
    financialAccounts.value = (res.data || []).filter(a => a.is_active !== false);
  } catch (err) {
    console.error('Failed to load financial accounts:', err);
  }
};

// Fetch purchases for current supplier
const fetchSupplierPurchases = async (supplierId) => {
  if (!supplierId) return;
  loadingPurchases.value = true;
  try {
    const res = await api.get(`/purchases?supplier_id=${supplierId}`);
    supplierPurchases.value = res.data || [];
  } catch (err) {
    console.error('Failed to fetch supplier purchases:', err);
    supplierPurchases.value = [];
  } finally {
    loadingPurchases.value = false;
  }
};

// Fetch payments for current supplier
const fetchSupplierPayments = async (supplierId) => {
  if (!supplierId) return;
  loadingPayments.value = true;
  try {
    const res = await api.get(`/suppliers/${supplierId}/payments`);
    supplierPayments.value = res.data || [];
  } catch (err) {
    console.error('Failed to fetch supplier payments:', err);
    supplierPayments.value = [];
  } finally {
    loadingPayments.value = false;
  }
};

const handleSupplierChange = async () => {
  if (selectedSupplierId.value) {
    await Promise.all([
      fetchSupplierPurchases(selectedSupplierId.value),
      fetchSupplierPayments(selectedSupplierId.value)
    ]);
  }
};

// Modal State: Record Payment
const showPaymentModal = ref(false);
const isSubmittingPayment = ref(false);
const paymentForm = ref({
  supplier_id: null,
  purchase_id: null,
  amount: 0,
  account_id: null,
  payment_method: 'cash',
  date: new Date().toISOString().slice(0, 10),
  notes: ''
});

const openPaymentModal = (targetPurchase = null) => {
  const currentSupId = selectedSupplierId.value || (suppliers.value[0]?.id || null);
  const defaultAcc = financialAccounts.value.find(a => a.type === 'cash' && a.is_default) || financialAccounts.value[0];
  
  let prefillAmount = 0;
  let purchaseId = null;

  if (targetPurchase) {
    purchaseId = targetPurchase.id;
    prefillAmount = Number(targetPurchase.due_amount) || 0;
  } else if (selectedSupplier.value) {
    prefillAmount = Math.max(0, Number(selectedSupplier.value.balance) || 0);
  }

  paymentForm.value = {
    supplier_id: currentSupId,
    purchase_id: purchaseId,
    amount: prefillAmount,
    account_id: defaultAcc ? defaultAcc.id : null,
    payment_method: 'cash',
    date: new Date().toISOString().slice(0, 10),
    notes: ''
  };
  showPaymentModal.value = true;
};

const onModalSupplierChange = async () => {
  if (paymentForm.value.supplier_id) {
    await fetchSupplierPurchases(paymentForm.value.supplier_id);
    const sup = suppliers.value.find(s => s.id === paymentForm.value.supplier_id);
    if (sup && !paymentForm.value.purchase_id) {
      paymentForm.value.amount = Math.max(0, Number(sup.balance) || 0);
    }
  }
};

const onModalPurchaseChange = () => {
  if (paymentForm.value.purchase_id) {
    const p = (supplierPurchases.value || []).find(item => item.id === paymentForm.value.purchase_id);
    if (p) {
      paymentForm.value.amount = Number(p.due_amount) || 0;
    }
  }
};

const submitPayment = async () => {
  if (!paymentForm.value.supplier_id) {
    showAlert('Please select a supplier.', 'error');
    return;
  }
  if (!paymentForm.value.amount || paymentForm.value.amount <= 0) {
    showAlert('Please enter a valid payment amount.', 'error');
    return;
  }
  if (!paymentForm.value.account_id) {
    showAlert('Please select a financial account to deduct payment from.', 'error');
    return;
  }

  isSubmittingPayment.value = true;
  try {
    const payload = {
      amount: paymentForm.value.amount,
      payment_method: paymentForm.value.payment_method,
      account_id: paymentForm.value.account_id,
      purchase_id: paymentForm.value.purchase_id || null,
      date: paymentForm.value.date,
      notes: paymentForm.value.notes
    };

    const res = await api.post(`/suppliers/${paymentForm.value.supplier_id}/payments`, payload);
    showAlert('Supplier payment recorded successfully!');
    showPaymentModal.value = false;

    // Refresh suppliers, purchases, payments, and accounts
    await Promise.all([
      store.fetchSuppliers(),
      fetchFinancialAccounts(),
      fetchSupplierPurchases(paymentForm.value.supplier_id),
      fetchSupplierPayments(paymentForm.value.supplier_id)
    ]);
  } catch (err) {
    const errorMsg = err.response?.data?.error || err.response?.data?.message || 'Failed to record supplier payment.';
    showAlert(errorMsg, 'error');
  } finally {
    isSubmittingPayment.value = false;
  }
};

// Modal State: Reverse Payment
const showReverseModal = ref(false);
const isReversingPayment = ref(false);
const paymentToReverse = ref(null);

const openReverseModal = (pay) => {
  paymentToReverse.value = pay;
  showReverseModal.value = true;
};

const confirmReversePayment = async () => {
  if (!paymentToReverse.value) return;
  isReversingPayment.value = true;
  try {
    await api.post(`/supplier-payments/${paymentToReverse.value.id}/reverse`);
    showAlert('Payment reversed successfully! Balances and accounts restored.');
    showReverseModal.value = false;

    // Refresh data
    await Promise.all([
      store.fetchSuppliers(),
      fetchFinancialAccounts(),
      fetchSupplierPurchases(selectedSupplierId.value),
      fetchSupplierPayments(selectedSupplierId.value)
    ]);
  } catch (err) {
    const errorMsg = err.response?.data?.error || err.response?.data?.message || 'Failed to reverse payment.';
    showAlert(errorMsg, 'error');
  } finally {
    isReversingPayment.value = false;
  }
};

// Avatar Color Generator
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

onMounted(async () => {
  await store.fetchSuppliers();
  await fetchFinancialAccounts();
  if (suppliers.value.length > 0) {
    selectedSupplierId.value = suppliers.value[0].id;
    await Promise.all([
      fetchSupplierPurchases(selectedSupplierId.value),
      fetchSupplierPayments(selectedSupplierId.value)
    ]);
  }
});
</script>

<style scoped>
.supplierpayment-page-wrapper {
  width: 100%;
  min-height: 100%;
  background: #f8fafc;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  color: #0f172a;
}

.supplier-main-container {
  padding: 24px 32px;
}

/* TOPBAR */
.supplier-topbar {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 24px;
}

.supplier-badge-pill {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  padding: 2px 8px;
  border-radius: 6px;
  background: #e0e7ff;
  color: #4338ca;
}

.supplier-main-title {
  font-size: 1.65rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.02em;
  margin: 0 0 4px 0;
}

.supplier-main-sub {
  font-size: 0.88rem;
  color: #64748b;
  margin: 0;
  max-width: 700px;
}

/* 4 KPI GRID */
.supplier-kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 16px;
}

.supplier-kpi-box {
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

.supplier-kpi-box:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
}

.overdue-box {
  background: #fff8f8;
  border-color: #fee2e2;
}

.supplier-kpi-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}

.supplier-kpi-data {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.supplier-kpi-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #64748b;
  margin-bottom: 2px;
}

.supplier-kpi-num {
  font-size: 1.35rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
}

.supplier-kpi-note {
  font-size: 12px;
  margin-top: 2px;
}

/* PROFILE HERO CARD */
.supplier-profile-hero-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
  overflow: hidden;
}

.profile-hero-header {
  padding: 18px 24px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
}

.supplier-large-avatar {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  font-weight: 800;
  flex-shrink: 0;
}

.supplier-hero-name {
  font-size: 1.3rem;
  font-weight: 800;
  color: #0f172a;
}

.supplier-hero-sub {
  font-size: 13px;
  color: #64748b;
  margin-top: 2px;
}

.badge-status-active {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 11.5px;
  font-weight: 700;
  background: #ecfdf5;
  color: #059669;
}

.hero-actions-bar {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.select-supplier-dropdown {
  height: 38px;
  padding: 0 32px 0 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 13.5px;
  font-weight: 600;
  color: #1e293b;
  background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 10px center;
  appearance: none;
  cursor: pointer;
  outline: none;
  transition: all 0.15s ease;
  min-width: 220px;
}

.select-supplier-dropdown:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* PANELS & TABLES */
.supplier-panel-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
  display: flex;
  flex-direction: column;
}

.supplier-panel-header {
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #ffffff;
}

.supplier-panel-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
}

.supplier-table-responsive {
  overflow-x: auto;
  flex: 1;
}

.supplier-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13.5px;
}

.supplier-table th {
  background: #f8fafc;
  padding: 12px 16px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #64748b;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
}

.supplier-table td {
  padding: 13px 16px;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.supplier-table-row:hover {
  background: #f8fafc;
}

/* BADGES & PILLS */
.stock-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 8px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
}

.pill-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.badge-in {
  background: #ecfdf5;
  color: #059669;
}

.badge-out {
  background: #fef2f2;
  color: #dc2626;
}

/* TOP ALERT */
.supplier-top-alert {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 1080;
  padding: 14px 20px;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  min-width: 340px;
}

.supplier-top-alert.alert-success {
  background: #ffffff;
  border-left: 5px solid #10b981;
  color: #064e3b;
}

.supplier-top-alert.alert-danger {
  background: #ffffff;
  border-left: 5px solid #ef4444;
  color: #7f1d1d;
}

.alert-slide-enter-active,
.alert-slide-leave-active {
  transition: all 0.3s ease;
}

.alert-slide-enter-from,
.alert-slide-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

/* MODAL OVERLAY & CARD */
.supplier-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  z-index: 1060;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.supplier-modal-card {
  background: #ffffff;
  border-radius: 16px;
  width: 100%;
  max-width: 600px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  overflow: hidden;
  animation: modalPop 0.25s ease-out;
}

@keyframes modalPop {
  from {
    opacity: 0;
    transform: scale(0.96) translateY(10px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.supplier-modal-header {
  padding: 18px 24px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.supplier-modal-header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.supplier-modal-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #e0e7ff;
  color: #4338ca;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.supplier-modal-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.supplier-modal-subtitle {
  font-size: 12.5px;
  color: #64748b;
  margin: 2px 0 0;
}

.supplier-modal-close {
  background: transparent;
  border: none;
  font-size: 18px;
  color: #94a3b8;
  cursor: pointer;
  padding: 4px;
}

.supplier-modal-close:hover {
  color: #0f172a;
}

.supplier-modal-body {
  padding: 24px;
}
</style>
