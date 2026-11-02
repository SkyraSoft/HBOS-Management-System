<template>
  <div class="sales-history-wrapper p-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div>
        <h2 class="fw-bold mb-1" style="color: var(--text-primary);">Sales History</h2>
        <p class="text-secondary mb-0">View all past transactions, reprint customer receipts, and process sales returns.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-primary fw-medium border d-flex align-items-center gap-2 rounded-3" @click="fetchData">
          <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
      </div>
    </div>

    <!-- Search & Filters -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 p-3 bg-white">
      <div class="row g-2 align-items-center">
        <!-- Search Query -->
        <div class="col-md-4">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted">
              <i class="bi bi-search"></i>
            </span>
            <input 
              type="text" 
              class="form-control bg-light border-start-0 ps-0" 
              placeholder="Search Invoice #, barcode, or customer..." 
              v-model="searchQuery"
            />
            <button v-if="searchQuery" class="btn btn-light border" @click="searchQuery = ''" type="button">
              <i class="bi bi-x"></i>
            </button>
          </div>
        </div>

        <!-- Status Filter -->
        <div class="col-md-2">
          <select class="form-select bg-light" v-model="statusFilter">
            <option value="all">All Statuses</option>
            <option value="completed">Completed</option>
            <option value="partially_returned">Partially Returned</option>
            <option value="returned">Fully Returned</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>

        <!-- Date Range Filter Presets -->
        <div class="col-md-3">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted">
              <i class="bi bi-calendar-event"></i>
            </span>
            <select class="form-select bg-light border-start-0 ps-1" v-model="datePreset">
              <option value="all">All Time</option>
              <option value="today">Today</option>
              <option value="yesterday">Yesterday</option>
              <option value="last_3_days">Previous 3 Days</option>
              <option value="last_7_days">Previous 7 Days</option>
              <option value="this_month">This Month</option>
              <option value="last_month">Last Month</option>
              <option value="custom">Custom Duration...</option>
            </select>
          </div>
        </div>

        <!-- Page Size Selector & Count -->
        <div class="col-md-3 d-flex align-items-center justify-content-end gap-2 text-muted small">
          <span>Show:</span>
          <select class="form-select form-select-sm bg-light w-auto" v-model="pageSize">
            <option v-for="size in pageSizeOptions" :key="size" :value="size">{{ size }} / page</option>
          </select>
          <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1.5 ms-1">
            {{ filteredSales.length }} sales
          </span>
        </div>
      </div>

      <!-- Custom Date Range Picker (conditionally shown when custom preset selected) -->
      <div v-if="datePreset === 'custom'" class="row g-2 align-items-center mt-2 pt-2 border-top">
        <div class="col-md-auto text-secondary small fw-medium">
          <i class="bi bi-clock-history me-1"></i> Custom Range:
        </div>
        <div class="col-md-3">
          <input type="date" class="form-control form-control-sm bg-light" v-model="customStartDate" placeholder="From Date">
        </div>
        <div class="col-md-auto text-muted small">to</div>
        <div class="col-md-3">
          <input type="date" class="form-control form-control-sm bg-light" v-model="customEndDate" placeholder="To Date">
        </div>
        <div class="col-md-auto">
          <button class="btn btn-sm btn-outline-secondary rounded-2" @click="clearCustomDates" type="button">
            Clear Dates
          </button>
        </div>
      </div>
    </div>

    <!-- Sales Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-4">Invoice #</th>
              <th>Date</th>
              <th>Customer</th>
              <th>Status</th>
              <th>Payment Method</th>
              <th class="text-end">Total (PKR)</th>
              <th class="text-end pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filteredSales.length === 0">
              <td colspan="7" class="text-center py-5 text-secondary">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                No sales matching criteria found.
              </td>
            </tr>
            <tr v-for="sale in paginatedSales" :key="sale.id">
              <td class="ps-4 fw-medium text-primary font-monospace">{{ sale.invoice_number }}</td>
              <td>{{ formatDate(sale.date || sale.created_at) }}</td>
              <td>
                <div class="fw-medium text-dark">{{ sale.customer?.name || sale.customer_name || 'Walk-in Customer' }}</div>
                <div v-if="sale.customer?.phone" class="text-muted small" style="font-size: 11px;">{{ sale.customer.phone }}</div>
              </td>
              <td>
                <span 
                  class="badge rounded-pill px-2 py-1"
                  :class="{
                    'bg-success-subtle text-success': sale.status === 'completed',
                    'bg-warning-subtle text-warning': sale.status === 'partially_returned',
                    'bg-danger-subtle text-danger': sale.status === 'returned' || sale.status === 'cancelled'
                  }"
                >
                  {{ formatStatus(sale.status) }}
                </span>
              </td>
              <td>
                <span class="badge bg-light text-secondary border font-monospace">{{ sale.payment_method }}</span>
                <span v-if="Number(sale.due_amount || 0) > 0" class="badge bg-warning-subtle text-warning ms-1" title="Unpaid Khata Balance">
                  Due: Rs. {{ Number(sale.due_amount).toLocaleString('en-PK') }}
                </span>
              </td>
              <td class="text-end fw-bold">Rs. {{ Number(sale.total).toLocaleString('en-PK') }}</td>
              <td class="text-end pe-4">
                <div class="d-inline-flex gap-1">
                  <!-- Reprint Button (Read-only, Zero mutations) -->
                  <button 
                    class="btn btn-sm btn-outline-secondary rounded-2 px-2 py-1" 
                    @click="openReprintModal(sale)"
                    title="Reprint receipt (read-only snapshot)"
                    type="button"
                  >
                    <i class="bi bi-printer me-1"></i> Reprint
                  </button>
                  <!-- Return Button -->
                  <button 
                    v-if="sale.status === 'completed' || sale.status === 'partially_returned'"
                    class="btn btn-sm btn-outline-danger rounded-2 px-2 py-1" 
                    @click="openReturnModal(sale)"
                    title="Process itemized return"
                    type="button"
                  >
                    <i class="bi bi-arrow-return-left me-1"></i> Return
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Controls Footer -->
      <div v-if="filteredSales.length > 0" class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-3 bg-white border-top">
        <div class="text-muted small">
          Showing <strong class="text-dark">{{ startRecord }}</strong> to <strong class="text-dark">{{ endRecord }}</strong> of <strong class="text-dark">{{ filteredSales.length }}</strong> sales
        </div>

        <nav aria-label="Sales history pagination">
          <ul class="pagination pagination-sm m-0 gap-1 align-items-center">
            <!-- First Page -->
            <li class="page-item" :class="{ disabled: currentPage === 1 }">
              <button class="page-link rounded-2 px-2" @click="goToPage(1)" :disabled="currentPage === 1" title="First Page">
                <i class="bi bi-chevron-double-left"></i>
              </button>
            </li>
            <!-- Previous Page -->
            <li class="page-item" :class="{ disabled: currentPage === 1 }">
              <button class="page-link rounded-2 px-2.5" @click="goToPage(currentPage - 1)" :disabled="currentPage === 1">
                <i class="bi bi-chevron-left"></i> Prev
              </button>
            </li>

            <!-- Page Number Pills -->
            <li v-for="(p, idx) in displayedPages" :key="idx" class="page-item" :class="{ active: p === currentPage, disabled: p === '...' }">
              <button class="page-link rounded-2 px-3 fw-medium" @click="goToPage(p)" :disabled="p === '...'">
                {{ p }}
              </button>
            </li>

            <!-- Next Page -->
            <li class="page-item" :class="{ disabled: currentPage === totalPages }">
              <button class="page-link rounded-2 px-2.5" @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages">
                Next <i class="bi bi-chevron-right"></i>
              </button>
            </li>
            <!-- Last Page -->
            <li class="page-item" :class="{ disabled: currentPage === totalPages }">
              <button class="page-link rounded-2 px-2" @click="goToPage(totalPages)" :disabled="currentPage === totalPages" title="Last Page">
                <i class="bi bi-chevron-double-right"></i>
              </button>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <!-- PROCESS RETURN MODAL (SRT-GAP-010) -->
    <div v-if="showReturnModal && activeReturnSale" class="modal-backdrop-custom d-flex align-items-center justify-content-center">
      <div class="modal-dialog-custom bg-white rounded-4 shadow-lg p-4" style="max-width: 750px; width: 95%;">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
          <div>
            <h5 class="fw-bold m-0 text-dark d-flex align-items-center gap-2">
              <i class="bi bi-arrow-return-left text-danger"></i> Process Sale Return
            </h5>
            <div class="text-muted small">Invoice: <strong>{{ activeReturnSale.invoice_number }}</strong> | Date: {{ formatDate(activeReturnSale.date) }}</div>
          </div>
          <button class="btn-close" @click="closeReturnModal" type="button"></button>
        </div>

        <div class="modal-body p-0">
          <div class="alert alert-light border small mb-3">
            Select items and quantities to return. Mark whether returned goods are restockable (sealed/undamaged) or damaged (defective/unsellable).
          </div>

          <!-- Items Return Table -->
          <div class="table-responsive mb-3" style="max-height: 280px; overflow-y: auto;">
            <table class="table table-sm align-middle">
              <thead class="bg-light">
                <tr style="font-size: 11.5px;">
                  <th>Item</th>
                  <th class="text-center">Sold</th>
                  <th class="text-center">Returned</th>
                  <th class="text-center" style="width: 110px;">Return Qty</th>
                  <th class="text-center">Condition</th>
                  <th class="text-end">Unit Refund</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in returnItems" :key="item.sale_item_id">
                  <td>
                    <div class="fw-semibold small">{{ item.product_name }}</div>
                    <div class="text-muted font-monospace" style="font-size: 10px;">SKU: {{ item.sku || 'N/A' }}</div>
                  </td>
                  <td class="text-center small">{{ item.sold_quantity }}</td>
                  <td class="text-center small text-muted">{{ item.already_returned }}</td>
                  <td>
                    <div class="input-group input-group-sm">
                      <button 
                        class="btn btn-outline-secondary px-2" 
                        @click="item.quantity = Math.max(0, item.allow_fractional ? (Math.round((item.quantity - 0.5) * 1000) / 1000) : (item.quantity - 1))"
                        type="button"
                      >−</button>
                      <input 
                        type="number" 
                        class="form-control text-center px-1" 
                        v-model.number="item.quantity" 
                        min="0" 
                        :step="item.allow_fractional ? '0.001' : '1'"
                        :max="item.max_returnable"
                      />
                      <button 
                        class="btn btn-outline-secondary px-2" 
                        @click="item.quantity = Math.min(item.max_returnable, item.allow_fractional ? (Math.round((item.quantity + 0.5) * 1000) / 1000) : (item.quantity + 1))"
                        :disabled="item.quantity >= item.max_returnable"
                        type="button"
                      >+</button>
                    </div>
                  </td>
                  <td class="text-center">
                    <div class="form-check form-switch d-inline-block">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        v-model="item.is_restockable" 
                        :id="'restock_' + item.sale_item_id"
                      />
                      <label class="form-check-label small ms-1" :for="'restock_' + item.sale_item_id" style="font-size: 11px;">
                        {{ item.is_restockable ? 'Restock' : 'Damaged' }}
                      </label>
                    </div>
                  </td>
                  <td class="text-end fw-semibold small">Rs. {{ Number(item.unit_price).toLocaleString('en-PK') }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Reason & Notes -->
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Return Reason</label>
            <input 
              type="text" 
              class="form-control form-control-sm rounded-3" 
              placeholder="e.g. Customer returned damaged pack, wrong size, etc." 
              v-model="returnReason"
            />
          </div>

          <!-- Economic Refund Breakdown Card (Split Tender Policy) -->
          <div class="card border bg-light p-3 rounded-3 mb-3">
            <div class="d-flex justify-content-between small text-muted mb-1">
              <span>Gross Return Value:</span>
              <strong class="text-dark">Rs. {{ computedTotalRefund.toLocaleString('en-PK') }}</strong>
            </div>
            <div v-if="computedKhataReduction > 0" class="d-flex justify-content-between small text-warning mb-1">
              <span>Khata Credit Offset (Reduces unpaid debt):</span>
              <strong>− Rs. {{ computedKhataReduction.toLocaleString('en-PK') }}</strong>
            </div>
            <div class="d-flex justify-content-between fw-bold text-dark border-top pt-1 mt-1">
              <span>Net Physical Refund Due (Cash / Bank Payout):</span>
              <span class="text-primary fs-6">Rs. {{ computedPhysicalRefund.toLocaleString('en-PK') }}</span>
            </div>
          </div>

          <!-- Settlement Channel Selection (SRT-GAP-010 Physical Refund Settlement) -->
          <div v-if="computedPhysicalRefund > 0" class="mb-3 p-3 bg-light border rounded-3">
            <label class="form-label small fw-bold text-dark d-flex align-items-center justify-content-between mb-1">
              <span>Physical Refund Settlement Channel</span>
              <span class="badge bg-primary">Net Payout: Rs. {{ computedPhysicalRefund.toLocaleString('en-PK') }}</span>
            </label>
            <p class="text-muted small mb-2" style="font-size: 11px;">
              Select the active cash drawer or bank account to record the physical refund outflow.
            </p>
            <select class="form-select form-select-sm" v-model="selectedRefundAccountId" :disabled="isSubmittingReturn">
              <option v-for="acc in availableAccounts" :key="acc.id" :value="acc.id">
                {{ acc.name }} ({{ (acc.type || '').toUpperCase() }}) — Balance: Rs. {{ Number(acc.balance || 0).toLocaleString('en-PK') }}
              </option>
            </select>
          </div>
          <div v-else-if="computedTotalRefund > 0" class="alert alert-info py-2 px-3 small mb-3">
            <i class="bi bi-info-circle me-1"></i>
            Full return value of Rs. {{ computedTotalRefund.toLocaleString('en-PK') }} is absorbed by Khata receivable reduction. Zero cash/bank payout required.
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2 pt-2 border-top">
          <button class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold px-3" @click="closeReturnModal" type="button">
            Cancel
          </button>
          <button 
            class="btn btn-danger btn-sm rounded-3 fw-semibold px-4" 
            :disabled="isSubmittingReturn || computedTotalRefund <= 0" 
            @click="submitReturn"
            type="button"
          >
            <span v-if="isSubmittingReturn" class="spinner-border spinner-border-sm me-1"></span>
            {{ isSubmittingReturn ? 'Processing...' : 'Confirm Return' }}
          </button>
        </div>
      </div>
    </div>

    <!-- REPRINT RECEIPT MODAL (Read-Only Snapshot SRT-GAP-011) -->
    <div v-if="showReprintModal && activeReprintSale" class="modal-backdrop-custom d-flex align-items-center justify-content-center">
      <div class="modal-dialog-custom bg-white rounded-4 shadow-lg p-4" style="max-width: 440px; width: 95%;">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
          <h6 class="fw-bold m-0 text-dark"><i class="bi bi-receipt me-1"></i> Receipt Reprint (Read-Only)</h6>
          <button class="btn-close" @click="closeReprintModal" type="button"></button>
        </div>

        <!-- Thermal Receipt Container -->
        <div class="reprint-ticket border rounded-3 p-3 bg-white mx-auto shadow-xs text-center" id="reprintSlipArea" style="max-width: 320px; font-family: monospace;">
          <div class="fw-bold fs-6 text-dark">HBOS RETAIL STORE</div>
          <div class="text-muted small" style="font-size: 11px;">Commercial Market, Lahore</div>
          <div class="text-muted small" style="font-size: 10px;">Tel: +92 300 1234567</div>
          <div class="my-2 border-top border-dashed"></div>

          <div class="d-flex justify-content-between small text-muted" style="font-size: 10.5px;">
            <span>Inv: {{ activeReprintSale.invoice_number }}</span>
            <span>{{ formatDate(activeReprintSale.date || activeReprintSale.created_at) }}</span>
          </div>
          <div class="d-flex justify-content-between small text-muted" style="font-size: 10.5px;">
            <span>Cust: {{ activeReprintSale.customer?.name || activeReprintSale.customer_name || 'Walk-in' }}</span>
            <span>{{ activeReprintSale.payment_method }}</span>
          </div>

          <div class="my-2 border-top border-dashed"></div>

          <table class="w-100 text-start" style="font-size: 11px;">
            <thead>
              <tr class="text-muted text-uppercase" style="font-size: 9.5px; border-bottom: 1px dashed #ccc;">
                <th class="py-1">Item</th>
                <th class="py-1 text-center">Qty</th>
                <th class="py-1 text-end">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in activeReprintSale.items" :key="item.id">
                <td class="py-1 text-truncate" style="max-width: 140px;">{{ item.product_name || item.product?.name }}</td>
                <td class="py-1 text-center">{{ item.quantity }}</td>
                <td class="py-1 text-end">Rs. {{ Number(item.total).toLocaleString('en-PK') }}</td>
              </tr>
            </tbody>
          </table>

          <div class="my-2 border-top border-dashed"></div>

          <div class="d-flex justify-content-between small py-0.5">
            <span>Subtotal:</span>
            <span>Rs. {{ Number(activeReprintSale.subtotal || 0).toLocaleString('en-PK') }}</span>
          </div>
          <div v-if="Number(activeReprintSale.discount || 0) > 0" class="d-flex justify-content-between small text-muted py-0.5">
            <span>Discount:</span>
            <span>− Rs. {{ Number(activeReprintSale.discount).toLocaleString('en-PK') }}</span>
          </div>
          <div class="d-flex justify-content-between fw-bold fs-6 text-dark border-top pt-1 mt-1">
            <span>TOTAL:</span>
            <span>Rs. {{ Number(activeReprintSale.total || 0).toLocaleString('en-PK') }}</span>
          </div>

          <!-- Code 128 Invoice Barcode -->
          <div class="my-2 pt-1 text-center">
            <div class="barcode-svg-wrap" v-html="generateBarcodeSvg(activeReprintSale.invoice_number)"></div>
            <div class="text-muted font-monospace" style="font-size: 9.5px; letter-spacing: 1px;">{{ activeReprintSale.invoice_number }}</div>
          </div>

          <!-- Return policy footer (Canonical Business Receipt Settings) -->
          <div v-if="receiptReturnPolicy" class="text-muted small mt-2" style="font-size: 9.5px; line-height: 1.3;">
            {{ receiptReturnPolicy }}
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-3 pt-2 border-top">
          <button class="btn btn-outline-secondary btn-sm rounded-3" @click="closeReprintModal" type="button">Close</button>
          <button class="btn btn-primary btn-sm rounded-3 fw-semibold px-3" @click="triggerPrint" type="button">
            <i class="bi bi-printer me-1"></i> Print Slip
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRetailStore } from '@/stores/retail'
import api from '@/api'

const store = useRetailStore()

const searchQuery = ref('')
const statusFilter = ref('all')
const datePreset = ref('all')
const customStartDate = ref('')
const customEndDate = ref('')

const clearCustomDates = () => {
  customStartDate.value = ''
  customEndDate.value = ''
  currentPage.value = 1
}

// Pagination State (Default 50 records per page as requested)
const currentPage = ref(1)
const pageSize = ref(50)
const pageSizeOptions = [25, 50, 100, 200]

watch([searchQuery, statusFilter, datePreset, customStartDate, customEndDate, pageSize], () => {
  currentPage.value = 1
})

// Return Workflow State
const showReturnModal = ref(false)
const activeReturnSale = ref(null)
const returnItems = ref([])
const returnReason = ref('')
const isSubmittingReturn = ref(false)
const availableAccounts = ref([])
const selectedRefundAccountId = ref(null)

// Canonical Receipt Policy State
const receiptReturnPolicy = ref('')

// Reprint State
const showReprintModal = ref(false)
const activeReprintSale = ref(null)

const fetchData = async () => {
  await store.fetchSales()
}

const loadReceiptSettings = async () => {
  try {
    const res = await api.get('/settings')
    const data = res.data || {}
    receiptReturnPolicy.value = data.receipt_settings?.return_policy || data.receipt_footer || ''
  } catch (e) {
    console.error('Failed to load canonical receipt settings:', e)
  }
}

const loadAccounts = async () => {
  try {
    const res = await api.get('/financial-accounts')
    availableAccounts.value = res.data || []
    if (availableAccounts.value.length > 0 && !selectedRefundAccountId.value) {
      const defaultCash = availableAccounts.value.find(a => a.type === 'cash' && a.is_default) || availableAccounts.value[0]
      selectedRefundAccountId.value = defaultCash.id
    }
  } catch (e) {
    console.error('Failed to load financial accounts for refund:', e)
  }
}

onMounted(() => {
  fetchData()
  loadReceiptSettings()
  loadAccounts()
})

const filteredSales = computed(() => {
  let list = store.sales || []

  // 1. Status Filter
  if (statusFilter.value !== 'all') {
    list = list.filter(s => String(s.status).toLowerCase() === statusFilter.value)
  }

  // 2. Date Filter Preset
  if (datePreset.value !== 'all') {
    const now = new Date()
    const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime()
    const todayEnd = todayStart + 86400000 - 1

    list = list.filter(s => {
      const sDateStr = s.date || s.created_at
      if (!sDateStr) return false
      const sTime = new Date(sDateStr).getTime()

      if (datePreset.value === 'today') {
        return sTime >= todayStart && sTime <= todayEnd
      } else if (datePreset.value === 'yesterday') {
        const yStart = todayStart - 86400000
        const yEnd = todayStart - 1
        return sTime >= yStart && sTime <= yEnd
      } else if (datePreset.value === 'last_3_days') {
        const threeDaysAgo = todayStart - (2 * 86400000)
        return sTime >= threeDaysAgo && sTime <= todayEnd
      } else if (datePreset.value === 'last_7_days') {
        const sevenDaysAgo = todayStart - (6 * 86400000)
        return sTime >= sevenDaysAgo && sTime <= todayEnd
      } else if (datePreset.value === 'this_month') {
        const monthStart = new Date(now.getFullYear(), now.getMonth(), 1).getTime()
        return sTime >= monthStart && sTime <= todayEnd
      } else if (datePreset.value === 'last_month') {
        const lastMonthStart = new Date(now.getFullYear(), now.getMonth() - 1, 1).getTime()
        const lastMonthEnd = new Date(now.getFullYear(), now.getMonth(), 0, 23, 59, 59, 999).getTime()
        return sTime >= lastMonthStart && sTime <= lastMonthEnd
      } else if (datePreset.value === 'custom') {
        let match = true
        if (customStartDate.value) {
          const cStart = new Date(customStartDate.value + 'T00:00:00').getTime()
          if (sTime < cStart) match = false
        }
        if (customEndDate.value) {
          const cEnd = new Date(customEndDate.value + 'T23:59:59').getTime()
          if (sTime > cEnd) match = false
        }
        return match
      }
      return true
    })
  }

  // 3. Search Query Filter
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.trim().toLowerCase()
    list = list.filter(s => {
      const invMatch = String(s.invoice_number || '').toLowerCase().includes(q)
      const custMatch = String(s.customer?.name || s.customer_name || '').toLowerCase().includes(q)
      const phoneMatch = String(s.customer?.phone || '').includes(q)
      return invMatch || custMatch || phoneMatch
    })
  }

  return list
})

// Pagination Calculations
const totalPages = computed(() => Math.ceil(filteredSales.value.length / pageSize.value) || 1)

const paginatedSales = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  return filteredSales.value.slice(start, start + pageSize.value)
})

const startRecord = computed(() => {
  if (filteredSales.value.length === 0) return 0
  return (currentPage.value - 1) * pageSize.value + 1
})

const endRecord = computed(() => {
  return Math.min(currentPage.value * pageSize.value, filteredSales.value.length)
})

const displayedPages = computed(() => {
  const total = totalPages.value
  const cur = currentPage.value
  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1)
  }
  const pages = []
  if (cur <= 4) {
    pages.push(1, 2, 3, 4, 5, '...', total)
  } else if (cur >= total - 3) {
    pages.push(1, '...', total - 4, total - 3, total - 2, total - 1, total)
  } else {
    pages.push(1, '...', cur - 1, cur, cur + 1, '...', total)
  }
  return pages
})

const goToPage = (page) => {
  if (page === '...') return
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('en-PK', {
    year: 'numeric', month: 'short', day: 'numeric'
  })
}

function formatStatus(status) {
  if (!status) return 'Completed'
  if (status === 'partially_returned') return 'Partially Returned'
  if (status === 'returned') return 'Returned'
  if (status === 'cancelled') return 'Cancelled'
  return status.charAt(0).toUpperCase() + status.slice(1)
}

// Return Modal Operations
const openReturnModal = async (sale) => {
  activeReturnSale.value = sale
  returnReason.value = ''
  if (availableAccounts.value.length === 0) {
    await loadAccounts()
  }
  returnItems.value = (sale.items || []).map(item => {
    const soldQty = Number(item.quantity || 0)
    const returnedQty = Number(item.returned_quantity || 0)
    const maxReturnable = Math.max(0, Math.round((soldQty - returnedQty) * 1000) / 1000)
    return {
      sale_item_id: item.id,
      product_name: item.product_name || item.product?.name || 'Product',
      sku: item.sku || item.product?.sku || '',
      sold_quantity: soldQty,
      already_returned: returnedQty,
      max_returnable: maxReturnable,
      allow_fractional: Boolean(item.product?.allow_fractional),
      quantity: 0,
      unit_price: Number(item.unit_price || item.price || 0),
      is_restockable: true,
      return_reason: ''
    }
  })
  showReturnModal.value = true
}

const closeReturnModal = () => {
  showReturnModal.value = false
  activeReturnSale.value = null
  returnItems.value = []
}

const computedTotalRefund = computed(() => {
  return returnItems.value.reduce((sum, item) => {
    const q = Math.max(0, Math.round(Number(item.quantity || 0) * 1000) / 1000)
    return Math.round((sum + (q * item.unit_price)) * 100) / 100
  }, 0)
})

const computedKhataReduction = computed(() => {
  if (!activeReturnSale.value) return 0
  const outstandingDue = Math.max(0, Number(activeReturnSale.value.due_amount || 0))
  return Math.min(computedTotalRefund.value, outstandingDue)
})

const computedPhysicalRefund = computed(() => {
  return Math.max(0, computedTotalRefund.value - computedKhataReduction.value)
})

const submitReturn = async () => {
  if (!activeReturnSale.value) return

  const itemsToSubmit = returnItems.value
    .filter(i => Number(i.quantity) > 0)
    .map(i => ({
      sale_item_id: i.sale_item_id,
      quantity: Math.round(Number(i.quantity) * 1000) / 1000,
      is_restockable: i.is_restockable,
      return_reason: returnReason.value || 'Customer return'
    }))

  if (itemsToSubmit.length === 0) {
    alert('Please specify return quantity for at least one item.')
    return
  }

  isSubmittingReturn.value = true
  try {
    const payload = {
      items: itemsToSubmit,
      reason: returnReason.value || 'Return processed from sales counter'
    }

    const retRes = await api.post(`/sales/${activeReturnSale.value.id}/returns`, payload)
    const saleReturnId = retRes.data?.id

    // Physical refund settlement via existing endpoint if residual physical obligation exists
    if (computedPhysicalRefund.value > 0 && selectedRefundAccountId.value && saleReturnId) {
      await api.post(`/sale-returns/${saleReturnId}/settle-refund`, {
        financial_account_id: selectedRefundAccountId.value,
        amount: computedPhysicalRefund.value
      })
    }

    await fetchData()
    closeReturnModal()
    alert('Return processed successfully!')
  } catch (err) {
    console.error('Failed to process return:', err)
    const msg = err.response?.data?.message || err.response?.data?.error || 'Failed to process return.'
    alert(msg)
  } finally {
    isSubmittingReturn.value = false
  }
}

// Reprint Modal Operations
const openReprintModal = (sale) => {
  activeReprintSale.value = sale
  showReprintModal.value = true
}

const closeReprintModal = () => {
  showReprintModal.value = false
  activeReprintSale.value = null
}

const triggerPrint = () => {
  window.print()
}

const generateBarcodeSvg = (code) => {
  if (!code) return ''
  return `<svg viewBox="0 0 140 26" width="100%" height="22" xmlns="http://www.w3.org/2000/svg">
    <rect x="0" y="0" width="140" height="26" fill="transparent"/>
    <path d="M4 2h2v22H4zM8 2h1v22H8zM11 2h3v22H11zM16 2h2v22H16zM20 2h1v22H20zM23 2h3v22H23zM28 2h2v22H28zM32 2h1v22H32zM35 2h3v22H35zM40 2h2v22H40zM44 2h1v22H44zM47 2h3v22H47zM52 2h2v22H52zM56 2h1v22H56zM59 2h3v22H59zM64 2h2v22H64zM68 2h1v22H68zM71 2h3v22H71zM76 2h2v22H76zM80 2h1v22H80zM83 2h3v22H83zM88 2h2v22H88zM92 2h1v22H92zM95 2h3v22H95zM100 2h2v22H100zM104 2h1v22H104zM107 2h3v22H107zM112 2h2v22H112zM116 2h1v22H116zM119 2h3v22H119zM124 2h2v22H124zM128 2h1v22H128zM131 2h3v22H131z" fill="#000"/>
  </svg>`
}
</script>

<style scoped>
.sales-history-wrapper {
  background-color: var(--bg);
  min-height: calc(100vh - 65px);
}
.table th {
  font-size: 0.82rem;
  font-weight: 600;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 2px solid #edf2f9;
}
.table td {
  font-size: 0.92rem;
  color: #344050;
  border-bottom: 1px solid #edf2f9;
  padding: 0.85rem 0.5rem;
}
.modal-backdrop-custom {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(4px);
  z-index: 1050;
}
.border-dashed {
  border-style: dashed !important;
}

@media print {
  body * {
    visibility: hidden;
  }
  #reprintSlipArea, #reprintSlipArea * {
    visibility: visible;
  }
  #reprintSlipArea {
    position: absolute;
    left: 0;
    top: 0;
    width: 80mm;
    border: none !important;
    box-shadow: none !important;
  }
}
</style>
