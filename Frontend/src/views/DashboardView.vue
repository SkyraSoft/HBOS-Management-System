<template>
  <div class="dashboard-page px-4 py-4">

    <!-- PAGE HEADER -->
    <div class="dash-topbar-header mb-4 pb-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div class="dash-greeting-box">
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="badge bg-primary-subtle text-primary px-2.5 py-1 rounded-pill small fw-bold">{{ userRole }}</span>
          <span v-if="navigationStore.isMultiBranch && scopeText" class="text-muted small">• {{ scopeText }}</span>
        </div>
        <h2 class="fw-bold mb-1 dash-greeting-title" style="color: var(--text-primary);">Good Day, {{ userName }}</h2>
        <p class="text-secondary mb-0 dash-greeting-sub">Authoritative operational performance overview.</p>
      </div>

      <div class="dash-header-controls d-flex align-items-center gap-2 flex-wrap">
        <!-- Branch Selector (Owner only in MULTI_BRANCH mode) -->
        <div v-if="navigationStore.branchSelectorVisible" class="dash-select-wrap me-2">
          <select 
            class="form-select form-select-sm rounded-3 fw-semibold shadow-sm" 
            :value="navigationStore.selectedBranchScope" 
            @change="onBranchScopeChange($event.target.value)"
          >
            <option value="all">All Stores</option>
            <option v-for="b in navigationStore.activeBranches" :key="b.id" :value="b.id">
              {{ (b.is_primary === true || b.is_primary === 1) ? 'Main Store' : b.name }}
            </option>
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

      <!-- OWNER ONLY + MULTI_BRANCH: BRANCH COMPARISON (Hidden in Single-Store mode) -->
      <div v-if="isOwner && navigationStore.isMultiBranch && branchComparison.length > 0" class="card border-0 shadow-sm rounded-4 p-4 mb-4">
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

      <!-- OWNER & MANAGER: ACTION NOTES OPERATIONAL DELEGATION (REQ-0029 / SRT-GAP-023) -->
      <div v-if="navigationStore.isMultiBranch && (isOwner || isManager)" class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h6 class="fw-bold mb-1 text-dark">
              <i class="bi bi-clipboard-check me-1.5 text-warning"></i> Operational Task Delegation &amp; Action Notes
            </h6>
            <span class="text-muted small">Owner-to-Manager branch operational tasks and resolution audit trail.</span>
          </div>
          <button v-if="isOwner" class="btn btn-sm btn-outline-primary" @click="openActionNoteModal">
            <i class="bi bi-plus-lg me-1"></i> Delegate Task
          </button>
        </div>

        <div v-if="actionNotes.length === 0" class="text-center text-muted py-4 small">
          No pending operational action notes for this branch scope.
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
              <tr>
                <th>Title / Instruction</th>
                <th>Branch</th>
                <th>Assigned To</th>
                <th>Priority</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="n in actionNotes" :key="n.id">
                <td>
                  <div class="fw-semibold text-dark">{{ n.title }}</div>
                  <small class="text-muted" v-if="n.description">{{ n.description }}</small>
                </td>
                <td>{{ n.branch?.name || 'Branch #' + n.branch_id }}</td>
                <td>{{ n.assigned_to?.name || 'Unassigned' }}</td>
                <td>
                  <span :class="['badge rounded-pill', n.priority === 'urgent' ? 'bg-danger' : (n.priority === 'high' ? 'bg-warning text-dark' : 'bg-light text-secondary border')]">
                    {{ n.priority?.toUpperCase() }}
                  </span>
                </td>
                <td>
                  <span :class="['badge rounded-pill', n.status === 'resolved' ? 'bg-success-subtle text-success' : (n.status === 'in_progress' ? 'bg-info-subtle text-info' : 'bg-secondary-subtle text-secondary')]">
                    {{ n.status }}
                  </span>
                </td>
                <td class="text-end">
                  <button 
                    v-if="n.status === 'open'" 
                    class="btn btn-sm btn-outline-info me-1" 
                    @click="updateActionNoteStatus(n.id, 'in_progress')"
                  >
                    Acknowledge
                  </button>
                  <button 
                    v-if="n.status === 'in_progress' || (isOwner && n.status === 'open')" 
                    class="btn btn-sm btn-outline-success" 
                    @click="promptResolveActionNote(n.id)"
                  >
                    Resolve
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Delegate Action Note Modal -->
      <div v-if="showActionNoteModal" class="modal-backdrop-custom">
        <div class="modal-dialog-custom">
          <div class="modal-content p-4 rounded-4 shadow-lg border-0 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="fw-bold m-0">Delegate Operational Task</h5>
              <button class="btn-close" @click="showActionNoteModal = false"></button>
            </div>
            <form @submit.prevent="submitCreateNote">
              <div class="mb-3">
                <label class="form-label small fw-semibold">Target Branch *</label>
                <select v-model="noteForm.branch_id" class="form-select" required>
                  <option value="" disabled>Select branch</option>
                  <option v-for="b in navigationStore.activeBranches" :key="b.id" :value="b.id">
                    {{ b.name }}
                  </option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">Task Title *</label>
                <input v-model="noteForm.title" type="text" class="form-control" required placeholder="e.g. Audit daily closing float discrepancy">
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">Description / Instructions</label>
                <textarea v-model="noteForm.description" class="form-control" rows="2" placeholder="Specific notes or instructions for branch manager..."></textarea>
              </div>
              <div class="mb-4">
                <label class="form-label small fw-semibold">Priority</label>
                <select v-model="noteForm.priority" class="form-select">
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-light" @click="showActionNoteModal = false">Cancel</button>
                <button type="submit" class="btn btn-primary">Delegate Task</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- RECENT ACTIVITY STREAM (CAPPED AT 10 WITH PAGINATION & LINK TO FULL HISTORY) -->
      <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-activity me-1.5 text-info"></i> {{ isSalesperson ? 'Your Recent Sales' : 'Recent Operational Activity' }}
          </h6>
          <button 
            v-if="totalActivityCount > 0"
            class="btn btn-sm btn-outline-primary rounded-3 px-3 d-inline-flex align-items-center gap-1.5 fw-semibold"
            @click="goToFullActivityPage"
            type="button"
          >
            <span>View Full History</span>
            <i class="bi bi-arrow-right"></i>
          </button>
        </div>

        <div v-if="recentActivity.length === 0" class="text-center text-muted py-4 small">
          No recent activity recorded.
        </div>

        <div v-else>
          <div class="table-responsive">
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
                <tr v-for="(act, i) in paginatedRecentActivity" :key="i">
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

          <!-- PAGINATION & CULTIVATION BAR (10 at a time + Link to Full Page) -->
          <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top flex-wrap gap-2">
            <div class="text-muted small">
              Showing <strong>{{ Math.min((activityPage - 1) * activityPerPage + 1, totalActivityCount) }}</strong>–<strong>{{ Math.min(activityPage * activityPerPage, totalActivityCount) }}</strong> of <strong>{{ totalActivityCount }}</strong> operations
            </div>
            
            <div class="d-flex align-items-center gap-2">
              <div v-if="totalActivityPages > 1" class="d-inline-flex align-items-center gap-1">
                <button 
                  class="btn btn-sm btn-outline-secondary rounded-2 px-2 py-1" 
                  :disabled="activityPage <= 1"
                  @click="activityPage--"
                  type="button"
                  title="Previous 10 operations"
                >
                  <i class="bi bi-chevron-left"></i>
                </button>
                <span class="btn btn-sm btn-light border disabled py-1 px-2.5 small fw-semibold">
                  Page {{ activityPage }} of {{ totalActivityPages }}
                </span>
                <button 
                  class="btn btn-sm btn-outline-secondary rounded-2 px-2 py-1" 
                  :disabled="activityPage >= totalActivityPages"
                  @click="activityPage++"
                  type="button"
                  title="Next 10 operations"
                >
                  <i class="bi bi-chevron-right"></i>
                </button>
              </div>

              <button 
                class="btn btn-sm btn-primary rounded-3 px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1.5" 
                @click="goToFullActivityPage"
                type="button"
              >
                <span>View All Operations</span>
                <i class="bi bi-arrow-right"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useNavigationStore } from '../stores/navigation'
import branchActionNoteService from '../services/branchActionNoteService'
import api from '../api'

const router = useRouter()
const authStore = useAuthStore()
const navigationStore = useNavigationStore()

const isLoading = ref(true)
const dashboardData = ref(null)

const dateFilterPreset = ref('this_month')
const customStartDate = ref('')
const customEndDate = ref('')

const userName = computed(() => authStore.user?.name || 'User')
const userRole = computed(() => dashboardData.value?.role || 'Business Owner')

const isOwner = computed(() => userRole.value === 'Business Owner')
const isManager = computed(() => userRole.value === 'Branch Manager')
const isSalesperson = computed(() => userRole.value === 'Salesperson')

const scopeText = computed(() => {
  if (!navigationStore.isMultiBranch || navigationStore.activeBranchCount <= 1) return ''
  return dashboardData.value?.scope?.branch_name || 'All Stores'
})

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

const onBranchScopeChange = (scope) => {
  navigationStore.setSelectedBranchScope(scope)
  fetchDashboardData()
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
    
    // Explicit Scope Scoping:
    // Multi-Branch Owner: send branch_id ('all' or specific active branch ID)
    if (isOwner.value && navigationStore.isMultiBranch) {
      if (navigationStore.selectedBranchScope === 'all') {
        params.branch_id = 'all'
      } else if (navigationStore.selectedBranchScope) {
        params.branch_id = navigationStore.selectedBranchScope
      }
    }
    // Single-Store Owner: SINGLE_STORE_IMPLICIT_SCOPE (omit branch_id)
    // Branch Manager: ASSIGNED_BRANCH_SERVER_SCOPE (omit branch_id)

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
    { title: "Net Profit", value: op.is_partial ? "Partial" : `PKR ${Number(op.amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}`, icon: "bi-cash-stack", color: Number(op.amount || 0) >= 0 ? "success" : "danger", subtext: "Gross Profit - Expenses (REQ-0027)" },
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

// Recent Activity Pagination (Capped at 10 items at a time + full history navigation)
const activityPage = ref(1)
const activityPerPage = 10
const totalActivityCount = computed(() => recentActivity.value.length)
const totalActivityPages = computed(() => Math.max(1, Math.ceil(totalActivityCount.value / activityPerPage)))
const paginatedRecentActivity = computed(() => {
  const start = (activityPage.value - 1) * activityPerPage
  return recentActivity.value.slice(start, start + activityPerPage)
})

const goToFullActivityPage = () => {
  router.push('/sales')
}

// Operational Action Notes (REQ-0029 / SRT-GAP-023)
const actionNotes = ref([])
const showActionNoteModal = ref(false)
const noteForm = ref({
  branch_id: '',
  title: '',
  description: '',
  priority: 'medium',
})

const fetchActionNotes = async () => {
  if (!isOwner.value && !isManager.value) return
  try {
    const params = {}
    if (navigationStore.selectedBranchScope && navigationStore.selectedBranchScope !== 'all') {
      params.branch_id = navigationStore.selectedBranchScope
    }
    actionNotes.value = await branchActionNoteService.list(params)
    navigationStore.fetchActionNotesPendingCount()
  } catch (err) {
    console.warn('Could not load action notes:', err)
  }
}

const openActionNoteModal = () => {
  noteForm.value = {
    branch_id: navigationStore.activeBranches[0]?.id || '',
    title: '',
    description: '',
    priority: 'medium',
  }
  showActionNoteModal.value = true
}

const submitCreateNote = async () => {
  try {
    await branchActionNoteService.create(noteForm.value)
    showActionNoteModal.value = false
    await fetchActionNotes()
  } catch (err) {
    alert('Failed to delegate task: ' + (err.response?.data?.message || err.message))
  }
}

const updateActionNoteStatus = async (id, status) => {
  try {
    await branchActionNoteService.updateStatus(id, status)
    await fetchActionNotes()
  } catch (err) {
    alert('Failed to update task: ' + (err.response?.data?.message || err.message))
  }
}

const promptResolveActionNote = async (id) => {
  const notes = prompt('Enter resolution notes / evidence:', 'Task verified and operational issue resolved.')
  if (notes === null) return
  try {
    await branchActionNoteService.updateStatus(id, 'resolved', notes)
    await fetchActionNotes()
  } catch (err) {
    alert('Failed to resolve task: ' + (err.response?.data?.message || err.message))
  }
}

watch(() => navigationStore.selectedBranchScope, () => {
  if (isOwner.value && navigationStore.isMultiBranch) {
    fetchDashboardData()
  }
  fetchActionNotes()
})

onMounted(async () => {
  if (navigationStore.branches.length === 0 && navigationStore.fetchStatus === 'IDLE') {
    await navigationStore.fetchBranches()
  }
  fetchDashboardData()
  fetchActionNotes()
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
.modal-backdrop-custom {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1060;
  padding: 16px;
}
.modal-dialog-custom {
  width: 100%;
  max-width: 500px;
}
</style>
