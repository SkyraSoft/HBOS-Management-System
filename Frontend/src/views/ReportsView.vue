<template>
  <div class="reports-page px-4 py-4">

    <!-- PAGE HEADER -->
    <div class="dash-topbar-header mb-4 pb-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="badge bg-primary-subtle text-primary px-2.5 py-1 rounded-pill small fw-bold">Authoritative Reports</span>
          <span class="text-muted small">• Operational &amp; Financial Audits</span>
        </div>
        <h2 class="fw-bold mb-1 dash-greeting-title" style="color: var(--text-primary);">Reports &amp; Business Intelligence</h2>
        <p class="text-secondary mb-0 dash-greeting-sub">Server-verified reporting based on Phase 1–7 operational ledgers.</p>
      </div>

      <div class="d-flex align-items-center gap-2 flex-wrap">
        <!-- Branch Filter (Owner only) -->
        <div v-if="isOwner && branches.length > 0" class="me-2">
          <select class="form-select form-select-sm rounded-3 fw-semibold shadow-sm" v-model="selectedBranchId" @change="fetchActiveReport">
            <option value="all">All Branches</option>
            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
        </div>

        <!-- Date Range Filter (For Date-Sensitive Reports) -->
        <div v-if="isDateSensitiveTab" class="d-flex align-items-center gap-2">
          <select class="form-select form-select-sm rounded-3 fw-semibold shadow-sm" v-model="datePreset" @change="fetchActiveReport">
            <option value="today">Today</option>
            <option value="yesterday">Yesterday</option>
            <option value="last_7_days">Last 7 Days</option>
            <option value="this_month">This Month</option>
            <option value="last_month">Last Month</option>
            <option value="last_30_days">Last 30 Days</option>
          </select>
        </div>

        <!-- Streamed CSV Export -->
        <button @click="triggerCsvExport" class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold px-3 bg-white shadow-sm" type="button" style="height: 38px;">
          <i class="bi bi-download me-1"></i> Export CSV
        </button>
      </div>
    </div>

    <!-- REPORT DOMAIN NAVIGATION TABS -->
    <ul class="nav nav-pills nav-fill mb-4 bg-white p-2 rounded-4 shadow-sm border">
      <li class="nav-item">
        <button :class="['nav-link', 'fw-semibold', activeTab === 'sales' ? 'active' : '']" @click="changeTab('sales')" type="button">
          <i class="bi bi-graph-up-arrow me-1"></i> Sales Report
        </button>
      </li>
      <li class="nav-item">
        <button :class="['nav-link', 'fw-semibold', activeTab === 'inventory' ? 'active' : '']" @click="changeTab('inventory')" type="button">
          <i class="bi bi-box-seam me-1"></i> Inventory Report
        </button>
      </li>
      <li class="nav-item">
        <button :class="['nav-link', 'fw-semibold', activeTab === 'customers' ? 'active' : '']" @click="changeTab('customers')" type="button">
          <i class="bi bi-people me-1"></i> Customer Khata
        </button>
      </li>
      <li v-if="isOwner" class="nav-item">
        <button :class="['nav-link', 'fw-semibold', activeTab === 'suppliers' ? 'active' : '']" @click="changeTab('suppliers')" type="button">
          <i class="bi bi-truck me-1"></i> Supplier Payables
        </button>
      </li>
      <li class="nav-item">
        <button :class="['nav-link', 'fw-semibold', activeTab === 'expenses' ? 'active' : '']" @click="changeTab('expenses')" type="button">
          <i class="bi bi-receipt me-1"></i> Expense Audit
        </button>
      </li>
      <li class="nav-item">
        <button :class="['nav-link', 'fw-semibold', activeTab === 'accounts' ? 'active' : '']" @click="changeTab('accounts')" type="button">
          <i class="bi bi-wallet2 me-1"></i> Financial Accounts
        </button>
      </li>
      <li v-if="isOwner" class="nav-item">
        <button :class="['nav-link', 'fw-semibold', activeTab === 'branches' ? 'active' : '']" @click="changeTab('branches')" type="button">
          <i class="bi bi-building me-1"></i> Branch Comparison
        </button>
      </li>
    </ul>

    <!-- LOADING STATE -->
    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading report...</span>
      </div>
      <p class="text-muted small mt-2">Loading authoritative report data...</p>
    </div>

    <div v-else>

      <!-- 1. SALES REPORT -->
      <div v-if="activeTab === 'sales'">
        <div class="row g-4 mb-4" v-if="reportData?.summary">
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Gross Sales</span>
              <div class="fs-4 fw-bold text-dark mt-1">PKR {{ Number(reportData.summary.gross_sales || 0).toLocaleString() }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Return Adjustment</span>
              <div class="fs-4 fw-bold text-warning mt-1">PKR {{ Number(reportData.summary.return_adjustment || 0).toLocaleString() }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Return-Adjusted Net Sales</span>
              <div class="fs-4 fw-bold text-success mt-1">PKR {{ Number(reportData.summary.net_sales || 0).toLocaleString() }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Gross Profit</span>
              <div class="fs-4 fw-bold text-dark mt-1">{{ reportData.summary.cogs_status === 'partial' ? 'Partial' : 'PKR ' + Number(reportData.summary.gross_profit || 0).toLocaleString() }}</div>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4">
          <h6 class="fw-bold mb-3 text-dark">Completed Sales Transactions</h6>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
              <thead class="table-light">
                <tr>
                  <th>Invoice #</th>
                  <th>Date</th>
                  <th>Branch</th>
                  <th>Customer</th>
                  <th class="text-end">Paid Amount</th>
                  <th class="text-end">Due Amount</th>
                  <th class="text-end">Total</th>
                  <th class="text-center">Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="s in reportData?.sales || []" :key="s.id">
                  <td class="fw-bold text-dark">{{ s.invoice_number }}</td>
                  <td class="text-muted">{{ s.date }}</td>
                  <td>{{ s.branch_name }}</td>
                  <td>{{ s.customer_name }}</td>
                  <td class="text-end">PKR {{ Number(s.paid_amount).toLocaleString() }}</td>
                  <td class="text-end text-danger">PKR {{ Number(s.due_amount).toLocaleString() }}</td>
                  <td class="text-end fw-bold">PKR {{ Number(s.total).toLocaleString() }}</td>
                  <td class="text-center"><span class="badge bg-success-subtle text-success">{{ s.status }}</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- 2. INVENTORY REPORT -->
      <div v-if="activeTab === 'inventory'">
        <div class="row g-4 mb-4" v-if="reportData?.summary">
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Total Stock Units</span>
              <div class="fs-4 fw-bold text-dark mt-1">{{ Number(reportData.summary.units_on_hand || 0).toLocaleString() }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Current Stock Value</span>
              <div class="fs-4 fw-bold text-primary mt-1">PKR {{ Number(reportData.summary.current_stock_value_at_current_cost || 0).toLocaleString() }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Low Stock Count</span>
              <div class="fs-4 fw-bold text-warning mt-1">{{ reportData.summary.low_stock_count || 0 }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Out of Stock</span>
              <div class="fs-4 fw-bold text-danger mt-1">{{ reportData.summary.out_of_stock_count || 0 }}</div>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4">
          <h6 class="fw-bold mb-3 text-dark">Branch Inventory Stock Ledger</h6>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
              <thead class="table-light">
                <tr>
                  <th>Product Name</th>
                  <th>SKU</th>
                  <th>Category</th>
                  <th>Branch</th>
                  <th class="text-center">Stock On Hand</th>
                  <th class="text-center">Min Stock</th>
                  <th class="text-end">Current Cost</th>
                  <th class="text-end">Total Valuation</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="inv in reportData?.inventories || []" :key="inv.id">
                  <td class="fw-bold text-dark">{{ inv.product_name }}</td>
                  <td class="text-muted">{{ inv.sku }}</td>
                  <td>{{ inv.category }}</td>
                  <td>{{ inv.branch_name }}</td>
                  <td class="text-center">
                    <span :class="'badge bg-' + (inv.quantity_on_hand <= inv.minimum_stock ? 'danger' : 'success') + '-subtle text-' + (inv.quantity_on_hand <= inv.minimum_stock ? 'danger' : 'success')">
                      {{ inv.quantity_on_hand }}
                    </span>
                  </td>
                  <td class="text-center">{{ inv.minimum_stock }}</td>
                  <td class="text-end">PKR {{ Number(inv.current_cost_price).toLocaleString() }}</td>
                  <td class="text-end fw-bold">PKR {{ Number(inv.stock_value).toLocaleString() }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- 3. CUSTOMERS REPORT -->
      <div v-if="activeTab === 'customers'">
        <div class="card border-0 shadow-sm rounded-4 p-4">
          <h6 class="fw-bold mb-3 text-dark">Registered Customer Account Ledger</h6>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
              <thead class="table-light">
                <tr>
                  <th>Customer Name</th>
                  <th>Phone</th>
                  <th class="text-end">Sales Volume</th>
                  <th class="text-center">Transactions</th>
                  <th class="text-end">Payments Collected</th>
                  <th class="text-end">Outstanding Receivable</th>
                  <th class="text-end">Advance Credit</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="c in reportData?.customers || []" :key="c.id">
                  <td class="fw-bold text-dark">{{ c.name }}</td>
                  <td class="text-muted">{{ c.phone || 'N/A' }}</td>
                  <td class="text-end fw-semibold">PKR {{ Number(c.sales_volume).toLocaleString() }}</td>
                  <td class="text-center">{{ c.transaction_count }}</td>
                  <td class="text-end text-success">PKR {{ Number(c.payments_collected).toLocaleString() }}</td>
                  <td class="text-end fw-bold text-danger">PKR {{ Number(c.outstanding_balance).toLocaleString() }}</td>
                  <td class="text-end fw-bold text-primary">PKR {{ Number(c.advance_credit).toLocaleString() }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- 4. SUPPLIERS REPORT -->
      <div v-if="activeTab === 'suppliers'">
        <div class="card border-0 shadow-sm rounded-4 p-4">
          <h6 class="fw-bold mb-3 text-dark">Supplier Procurement &amp; Payable Ledger</h6>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
              <thead class="table-light">
                <tr>
                  <th>Supplier Name</th>
                  <th>Contact Person</th>
                  <th class="text-end">Purchase Volume</th>
                  <th class="text-center">Purchases Count</th>
                  <th class="text-end">Payments Made</th>
                  <th class="text-end">Reconciled Payable</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="sup in reportData?.suppliers || []" :key="sup.id">
                  <td class="fw-bold text-dark">{{ sup.name }}</td>
                  <td class="text-muted">{{ sup.contact_person || 'N/A' }}</td>
                  <td class="text-end fw-semibold">PKR {{ Number(sup.purchase_volume).toLocaleString() }}</td>
                  <td class="text-center">{{ sup.purchase_count }}</td>
                  <td class="text-end text-success">PKR {{ Number(sup.payments_made).toLocaleString() }}</td>
                  <td class="text-end fw-bold text-danger">PKR {{ Number(sup.reconciled_payable).toLocaleString() }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- 5. EXPENSES REPORT -->
      <div v-if="activeTab === 'expenses'">
        <div class="card border-0 shadow-sm rounded-4 p-4">
          <h6 class="fw-bold mb-3 text-dark">Posted Expenses Audit (Total: PKR {{ Number(reportData?.total_expenses || 0).toLocaleString() }})</h6>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
              <thead class="table-light">
                <tr>
                  <th>Expense ID</th>
                  <th>Date</th>
                  <th>Category</th>
                  <th>Description</th>
                  <th>Branch</th>
                  <th>Account</th>
                  <th class="text-end">Amount</th>
                  <th class="text-center">Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="e in reportData?.expenses || []" :key="e.id">
                  <td class="fw-bold text-dark">EXP-{{ e.id }}</td>
                  <td class="text-muted">{{ e.date }}</td>
                  <td><span class="badge bg-info-subtle text-info">{{ e.category }}</span></td>
                  <td>{{ e.description || 'N/A' }}</td>
                  <td>{{ e.branch_name }}</td>
                  <td>{{ e.account_name }}</td>
                  <td class="text-end fw-bold text-danger">PKR {{ Number(e.amount).toLocaleString() }}</td>
                  <td class="text-center"><span class="badge bg-success-subtle text-success">{{ e.status }}</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- 6. FINANCIAL ACCOUNTS REPORT -->
      <div v-if="activeTab === 'accounts'">
        <div class="card border-0 shadow-sm rounded-4 p-4">
          <h6 class="fw-bold mb-3 text-dark">Financial Accounts Cash Flow Ledger</h6>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
              <thead class="table-light">
                <tr>
                  <th>Account Name</th>
                  <th>Type</th>
                  <th>Branch</th>
                  <th class="text-end">Current Balance</th>
                  <th class="text-end">Period Inflows</th>
                  <th class="text-end">Period Outflows</th>
                  <th class="text-center">Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="acc in reportData?.accounts || []" :key="acc.id">
                  <td class="fw-bold text-dark">{{ acc.name }}</td>
                  <td><span class="badge bg-secondary-subtle text-dark">{{ acc.type }}</span></td>
                  <td>{{ acc.branch_name }}</td>
                  <td class="text-end fw-bold text-dark">PKR {{ Number(acc.current_balance).toLocaleString() }}</td>
                  <td class="text-end text-success">PKR {{ Number(acc.period_inflows).toLocaleString() }}</td>
                  <td class="text-end text-danger">PKR {{ Number(acc.period_outflows).toLocaleString() }}</td>
                  <td class="text-center"><span class="badge bg-success-subtle text-success">{{ acc.status }}</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- 7. BRANCHES REPORT -->
      <div v-if="activeTab === 'branches' && isOwner">
        <div class="card border-0 shadow-sm rounded-4 p-4">
          <h6 class="fw-bold mb-3 text-dark">Cross-Branch Comparative Audit</h6>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
              <thead class="table-light">
                <tr>
                  <th>Branch Name</th>
                  <th class="text-end">Net Sales</th>
                  <th class="text-center">Transactions</th>
                  <th class="text-end">Gross Profit</th>
                  <th class="text-end">Posted Expenses</th>
                  <th class="text-end">Cash Drawer Balance</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="b in reportData?.branches || []" :key="b.branch_id">
                  <td class="fw-bold text-dark">{{ b.branch_name }}</td>
                  <td class="text-end fw-bold text-success">PKR {{ Number(b.net_sales).toLocaleString() }}</td>
                  <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ b.transaction_count }}</span></td>
                  <td class="text-end">{{ b.gross_profit !== null ? 'PKR ' + Number(b.gross_profit).toLocaleString() : 'Partial' }}</td>
                  <td class="text-end text-danger">PKR {{ Number(b.posted_expenses).toLocaleString() }}</td>
                  <td class="text-end fw-bold text-dark">PKR {{ Number(b.cash_balance).toLocaleString() }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../api'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const activeTab = ref(route.query.tab || 'sales')
const isLoading = ref(false)
const reportData = ref(null)
const branches = ref([])
const selectedBranchId = ref('all')
const datePreset = ref('this_month')

const userRole = computed(() => authStore.userRole || 'Business Owner')
const isOwner = computed(() => userRole.value === 'Business Owner')

const isDateSensitiveTab = computed(() => ['sales', 'expenses', 'accounts', 'branches'].includes(activeTab.value))

const changeTab = (tab) => {
  activeTab.value = tab
  router.replace({ path: '/reports', query: { ...route.query, tab } })
  fetchActiveReport()
}

const fetchBranches = async () => {
  try {
    const res = await api.get('/settings/branches')
    branches.value = res.data?.data || res.data || []
  } catch (e) {
    console.warn('Could not load branches:', e)
  }
}

const fetchActiveReport = async () => {
  isLoading.value = true
  try {
    const params = {
      preset: datePreset.value
    }
    if (selectedBranchId.value && selectedBranchId.value !== 'all') {
      params.branch_id = selectedBranchId.value
    }

    let endpoint = `/reports/${activeTab.value}`
    if (activeTab.value === 'accounts') endpoint = '/reports/financial-accounts'

    const response = await api.get(endpoint, { params })
    reportData.value = response.data
  } catch (error) {
    console.error(`Error loading ${activeTab.value} report:`, error)
  } finally {
    isLoading.value = false
  }
}

const triggerCsvExport = () => {
  const params = new URLSearchParams()
  params.append('export', 'csv')
  params.append('preset', datePreset.value)
  if (selectedBranchId.value && selectedBranchId.value !== 'all') {
    params.append('branch_id', selectedBranchId.value)
  }

  let endpoint = `/reports/${activeTab.value}`
  if (activeTab.value === 'accounts') endpoint = '/reports/financial-accounts'

  const token = authStore.token || localStorage.getItem('token')
  const baseUrl = api.defaults.baseURL || '/api/v1'
  const exportUrl = `${baseUrl}${endpoint}?${params.toString()}`

  window.open(exportUrl, '_blank')
}

onMounted(() => {
  fetchBranches()
  fetchActiveReport()
})
</script>

<style scoped>
.reports-page {
  max-width: 1400px;
  margin: 0 auto;
}
.nav-pills .nav-link {
  color: #64748b;
  border-radius: 0.75rem;
}
.nav-pills .nav-link.active {
  background-color: #2563eb;
  color: #ffffff;
}
</style>
