<template>
  <div class="dashboard-page px-4 py-4">

    <!-- PAGE HEADER -->
    <div class="dash-topbar-header mb-4 pb-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div class="dash-greeting-box">
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="badge bg-primary-subtle text-primary px-2.5 py-1 rounded-pill small fw-bold">{{ userRole }}</span>
          <span class="text-muted small">• {{ scopeText }}</span>
        </div>
        <h2 class="fw-bold mb-1 dash-greeting-title" style="color: var(--text-primary);">Good Day, {{ userName }}</h2>
        <p class="text-secondary mb-0 dash-greeting-sub">Authoritative operational performance overview.</p>
      </div>

      <div class="dash-header-controls d-flex align-items-center gap-2 flex-wrap">
        <!-- Branch Selector (Owner only) -->
        <div v-if="isOwner && branches.length > 0" class="dash-select-wrap me-2">
          <select class="form-select form-select-sm rounded-3 fw-semibold shadow-sm" v-model="selectedBranchId" @change="fetchDashboardData">
            <option value="all">All Branches</option>
            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
        </div>

        <!-- Global Date Range Preset -->
        <div class="dash-date-selector d-flex align-items-center gap-2">
          <select class="form-select form-select-sm rounded-3 fw-semibold shadow-sm" v-model="dateFilterPreset" @change="onPresetChange">
            <option value="today">Today</option>
            <option value="yesterday">Yesterday</option>
            <option value="last_7_days">Last 7 Days</option>
            <option value="this_month">This Month</option>
            <option value="last_month">Last Month</option>
            <option value="last_30_days">Last 30 Days</option>
            <option value="custom">Custom Range...</option>
          </select>
          
          <div v-if="dateFilterPreset === 'custom'" class="d-flex align-items-center gap-1">
            <input type="date" class="form-control form-control-sm" v-model="customStartDate" :max="todayDate" @change="onCustomDateChange" />
            <span class="text-muted small">to</span>
            <input type="date" class="form-control form-control-sm" v-model="customEndDate" :min="customStartDate" :max="todayDate" @change="onCustomDateChange" />
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="dash-actions-row d-flex gap-2">
          <button @click="handleNewSale" class="btn btn-primary btn-sm rounded-3 fw-semibold shadow-sm px-3" type="button" title="Open POS Terminal">
            <i class="bi bi-cart-plus me-1"></i> New Sale
          </button>
        </div>
      </div>
    </div>

    <!-- LOADING STATE -->
    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading dashboard data...</span>
      </div>
      <p class="text-muted small mt-2">Computing dynamic metrics...</p>
    </div>

    <div v-else>

      <!-- OWNER & MANAGER: NEEDS ATTENTION ALERTS -->
      <div v-if="needsAttention.length > 0 && (isOwner || isManager)" class="mb-4">
        <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-warning overflow-hidden">
          <div class="card-header bg-warning-subtle border-0 py-2.5 px-4 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
              <h6 class="fw-bold mb-0 text-dark">Needs Your Attention ({{ needsAttention.length }})</h6>
            </div>
            <span class="badge bg-warning text-dark fw-bold">Action Required</span>
          </div>
          <div class="card-body p-0">
            <div class="list-group list-group-flush">
              <div v-for="(alert, idx) in needsAttention" :key="idx" class="list-group-item px-4 py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                  <div class="fw-bold text-dark mb-0.5">{{ alert.title }}</div>
                  <div class="text-secondary small">{{ alert.message }}</div>
                </div>
                <span :class="'badge bg-' + (alert.severity === 'high' ? 'danger' : 'warning') + '-subtle text-' + (alert.severity === 'high' ? 'danger' : 'warning') + ' fw-semibold'">
                  {{ alert.type }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- DYNAMIC KPI CARDS GRID -->
      <div class="row g-4 mb-4">
        <div v-for="(kpi, index) in kpis" :key="index" class="col-xl-3 col-md-4 col-sm-6">
          <div class="card border-0 shadow-sm rounded-4 h-100 p-4 transition-hover">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <span class="text-secondary fw-semibold small">{{ kpi.title }}</span>
              <div :class="'p-2 rounded-3 bg-' + kpi.color + '-subtle text-' + kpi.color">
                <i :class="'bi ' + kpi.icon + ' fs-5'"></i>
              </div>
            </div>
            <div class="fs-4 fw-bold text-dark mb-1">{{ kpi.value }}</div>
            <div v-if="kpi.subtext" class="text-muted small fw-medium">{{ kpi.subtext }}</div>
          </div>
        </div>
      </div>

      <!-- OWNER & MANAGER: PERFORMANCE TABLES & RECENT ACTIVITY -->
      <div v-if="isOwner || isManager" class="row g-4 mb-4">
        
        <!-- Top Sellers -->
        <div class="col-lg-6 col-12">
          <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-trophy text-warning me-1.5"></i> Top Selling Products
              </h6>
              <span class="text-muted small">Return-Adjusted</span>
            </div>

            <div v-if="topSellers.length === 0" class="text-center text-muted py-4 small">
              No sales recorded for this period.
            </div>

            <div v-else class="table-responsive">
              <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                  <tr>
                    <th>Product</th>
                    <th class="text-center">Units Sold</th>
                    <th class="text-end">Net Sales</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in topSellers" :key="item.product_id">
                    <td class="fw-semibold text-dark">{{ item.name }}</td>
                    <td class="text-center"><span class="badge bg-secondary-subtle text-dark">{{ item.net_units_sold }}</span></td>
                    <td class="text-end fw-bold text-success">PKR {{ Number(item.net_sales).toLocaleString() }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Slow Movers -->
        <div class="col-lg-6 col-12">
          <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-hourglass-bottom text-danger me-1.5"></i> Slow Moving Products
              </h6>
              <span class="text-muted small">Stock > 0 &amp; Zero Net Sales</span>
            </div>

            <div v-if="slowMovers.length === 0" class="text-center text-muted py-4 small">
              No slow movers detected in period.
            </div>

            <div v-else class="table-responsive">
              <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                  <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th class="text-end">Stock On Hand</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in slowMovers" :key="item.product_id">
                    <td class="fw-semibold text-dark">{{ item.name }}</td>
                    <td class="text-muted">{{ item.sku }}</td>
                    <td class="text-end fw-bold text-danger">{{ item.quantity_on_hand }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>

      <!-- OWNER ONLY: BRANCH COMPARISON -->
      <div v-if="isOwner && branchComparison.length > 0" class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <h6 class="fw-bold mb-3 text-dark">
          <i class="bi bi-building me-1.5 text-primary"></i> Branch Performance Comparison
        </h6>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
              <tr>
                <th>Branch Name</th>
                <th class="text-end">Net Sales</th>
                <th class="text-center">Transactions</th>
                <th class="text-end">Gross Profit</th>
                <th class="text-end">Expenses</th>
                <th class="text-end">Cash Position</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="b in branchComparison" :key="b.branch_id">
                <td class="fw-bold text-dark">{{ b.branch_name }}</td>
                <td class="text-end fw-semibold text-success">PKR {{ Number(b.net_sales).toLocaleString() }}</td>
                <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ b.transaction_count }}</span></td>
                <td class="text-end font-monospace">{{ b.gross_profit !== null ? 'PKR ' + Number(b.gross_profit).toLocaleString() : 'Partial' }}</td>
                <td class="text-end text-danger">PKR {{ Number(b.posted_expenses).toLocaleString() }}</td>
                <td class="text-end fw-bold text-dark">PKR {{ Number(b.cash_balance).toLocaleString() }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- RECENT ACTIVITY STREAM -->
      <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-activity me-1.5 text-info"></i> {{ isSalesperson ? 'Your Recent Sales' : 'Recent Operational Activity' }}
          </h6>
        </div>

        <div v-if="recentActivity.length === 0" class="text-center text-muted py-4 small">
          No recent activity recorded.
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
              <tr>
                <th>Reference</th>
                <th>Type</th>
                <th>Date</th>
                <th class="text-end">Amount</th>
                <th class="text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(act, i) in recentActivity" :key="i">
                <td class="fw-semibold text-dark">{{ act.reference || act.invoice_number }}</td>
                <td><span class="badge bg-light text-dark border">{{ act.type || 'Sale' }}</span></td>
                <td class="text-muted">{{ act.date }}</td>
                <td class="text-end fw-bold text-dark">PKR {{ Number(act.amount || act.total).toLocaleString() }}</td>
                <td class="text-center">
                  <span :class="'badge bg-' + (act.status === 'completed' || act.status === 'posted' ? 'success' : 'secondary') + '-subtle text-' + (act.status === 'completed' || act.status === 'posted' ? 'success' : 'secondary')">
                    {{ act.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../api'

const router = useRouter()
const authStore = useAuthStore()

const isLoading = ref(true)
const dashboardData = ref(null)
const branches = ref([])
const selectedBranchId = ref('all')

const dateFilterPreset = ref('this_month')
const customStartDate = ref('')
const customEndDate = ref('')

const userName = computed(() => authStore.user?.name || 'User')
const userRole = computed(() => dashboardData.value?.role || 'Business Owner')

const isOwner = computed(() => userRole.value === 'Business Owner')
const isManager = computed(() => userRole.value === 'Branch Manager')
const isSalesperson = computed(() => userRole.value === 'Salesperson')

const scopeText = computed(() => dashboardData.value?.scope?.branch_name || 'Operational Scope')

const getTodayLocal = () => {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}
const todayDate = computed(() => getTodayLocal())

const handleNewSale = () => {
  router.push('/POS?tab=sales')
}

const onPresetChange = () => {
  fetchDashboardData()
}

const onCustomDateChange = () => {
  if (dateFilterPreset.value === 'custom' && customStartDate.value && customEndDate.value) {
    fetchDashboardData()
  }
}

const fetchBranches = async () => {
  try {
    const res = await api.get('/settings/branches')
    branches.value = res.data?.data || res.data || []
  } catch (e) {
    console.warn('Could not fetch branches list:', e)
  }
}

const fetchDashboardData = async () => {
  isLoading.value = true
  try {
    const params = {
      preset: dateFilterPreset.value,
    }
    if (dateFilterPreset.value === 'custom') {
      params.from = customStartDate.value
      params.to = customEndDate.value
    }
    if (selectedBranchId.value && selectedBranchId.value !== 'all') {
      params.branch_id = selectedBranchId.value
    }

    const response = await api.get('/dashboard', { params })
    dashboardData.value = response.data
  } catch (error) {
    console.error('Error fetching dashboard metrics:', error)
  } finally {
    isLoading.value = false
  }
}

const kpis = computed(() => {
  if (!dashboardData.value) return []

  if (isSalesperson.value) {
    const ps = dashboardData.value.personal_sales || {}
    return [
      { title: "Personal Net Sales", value: `PKR ${Number(ps.net_sales || 0).toLocaleString()}`, icon: "bi-cash-stack", color: "primary", subtext: `${ps.transaction_count || 0} Transactions` },
      { title: "Return Adjustment", value: `PKR ${Number(ps.return_adjustment || 0).toLocaleString()}`, icon: "bi-arrow-return-left", color: "warning", subtext: "Returns in period" },
      { title: "Customers Served", value: `${ps.customers_served || 0}`, icon: "bi-people", color: "success", subtext: "Unique customers" },
    ]
  }

  if (isManager.value) {
    const sales = dashboardData.value.sales || {}
    const exp = dashboardData.value.expenses || {}
    const cb = dashboardData.value.cash_and_bank || {}
    const rec = dashboardData.value.receivables || {}
    const inv = dashboardData.value.inventory || {}

    return [
      { title: "Branch Net Sales", value: `PKR ${Number(sales.net_sales || 0).toLocaleString()}`, icon: "bi-graph-up-arrow", color: "primary", subtext: `${sales.transaction_count || 0} Orders` },
      { title: "Branch Expenses", value: `PKR ${Number(exp.total || 0).toLocaleString()}`, icon: "bi-receipt", color: "danger", subtext: "Posted expenses" },
      { title: "Cash Drawer", value: `PKR ${Number(cb.cash_on_hand || 0).toLocaleString()}`, icon: "bi-wallet2", color: "success", subtext: "Branch cash position" },
      { title: "Customer Exposure", value: `PKR ${Number(rec.outstanding || 0).toLocaleString()}`, icon: "bi-person-exclamation", color: "warning", subtext: "Branch receivables" },
      { title: "Low Stock Alert", value: `${inv.low_stock_count || 0}`, icon: "bi-exclamation-triangle", color: "warning", subtext: "Items at/below min stock" },
      { title: "Out of Stock", value: `${inv.out_of_stock_count || 0}`, icon: "bi-x-circle", color: "danger", subtext: "Zero quantity on hand" },
    ]
  }

  // Business Owner KPIs
  const s = dashboardData.value.sales || {}
  const gp = dashboardData.value.gross_profit || {}
  const op = dashboardData.value.operating_position || {}
  const ex = dashboardData.value.expenses || {}
  const cb = dashboardData.value.cash_and_bank || {}
  const rec = dashboardData.value.receivables || {}
  const pay = dashboardData.value.payables || {}
  const inv = dashboardData.value.inventory || {}

  return [
    { title: "Net Sales", value: `PKR ${Number(s.net_sales || 0).toLocaleString()}`, icon: "bi-graph-up-arrow", color: "primary", subtext: `Gross: PKR ${Number(s.gross_sales || 0).toLocaleString()}` },
    { title: "Gross Profit", value: gp.is_partial ? "Partial" : `PKR ${Number(gp.amount || 0).toLocaleString()}`, icon: "bi-currency-dollar", color: "success", subtext: gp.is_partial ? "Uncosted sales exist" : "Net Sales - Net COGS" },
    { title: "Operating Position", value: op.is_partial ? "Partial" : `PKR ${Number(op.amount || 0).toLocaleString()}`, icon: "bi-pie-chart", color: "info", subtext: "Gross Profit - Expenses" },
    { title: "Posted Expenses", value: `PKR ${Number(ex.total || 0).toLocaleString()}`, icon: "bi-receipt", color: "danger", subtext: "Status: Posted" },
    { title: "Cash & Bank Funds", value: `PKR ${Number(cb.total_tracked_funds || 0).toLocaleString()}`, icon: "bi-bank", color: "success", subtext: `Cash: PKR ${Number(cb.cash_on_hand || 0).toLocaleString()}` },
    { title: "Outstanding Receivables", value: `PKR ${Number(rec.outstanding || 0).toLocaleString()}`, icon: "bi-person-exclamation", color: "warning", subtext: `Credit: PKR ${Number(rec.customer_credit || 0).toLocaleString()}` },
    { title: "Supplier Payables", value: `PKR ${Number(pay.total_payable || 0).toLocaleString()}`, icon: "bi-truck", color: "danger", subtext: "Reconciled balance" },
    { title: "Stock Valuation", value: `PKR ${Number(inv.current_stock_value_at_current_cost || 0).toLocaleString()}`, icon: "bi-box-seam", color: "secondary", subtext: `Low: ${inv.low_stock_count || 0} | Out: ${inv.out_of_stock_count || 0}` },
  ]
})

const needsAttention = computed(() => dashboardData.value?.needs_attention || [])
const topSellers = computed(() => dashboardData.value?.product_performance?.top_sellers || [])
const slowMovers = computed(() => dashboardData.value?.product_performance?.slow_movers || [])
const branchComparison = computed(() => dashboardData.value?.branch_comparison || [])
const recentActivity = computed(() => dashboardData.value?.recent_activity || dashboardData.value?.recent_sales || [])

onMounted(() => {
  fetchBranches()
  fetchDashboardData()
})
</script>

<style scoped>
.dashboard-page {
  max-width: 1400px;
  margin: 0 auto;
}
.transition-hover {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.transition-hover:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.08) !important;
}
</style>
