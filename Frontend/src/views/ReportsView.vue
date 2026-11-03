<template>
  <div class="container-fluid p-4 reports-page">
    <!-- PAGE HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold mb-1 text-dark">Management &amp; Financial Reports</h4>
        <p class="text-secondary small mb-0">Authoritative store intelligence, cash-flow ledger audit, and small-retail profitability.</p>
      </div>

      <!-- Controls & Filters -->
      <div class="d-flex align-items-center flex-wrap gap-2">
        <!-- Branch Scope Selector -->
        <div v-if="branches.length > 1" class="me-1">
          <select class="form-select form-select-sm shadow-sm rounded-3" v-model="selectedBranchId" @change="fetchActiveReport">
            <option value="all">All Branches (Consolidated)</option>
            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
        </div>

        <!-- Date Preset Filter -->
        <div v-if="isDateSensitiveTab" class="me-1">
          <select class="form-select form-select-sm shadow-sm rounded-3" v-model="datePreset" @change="fetchActiveReport">
            <option value="today">Today</option>
            <option value="this_week">This Week</option>
            <option value="this_month">This Month</option>
            <option value="last_month">Last Month</option>
            <option value="this_quarter">This Quarter</option>
            <option value="this_year">This Fiscal Year</option>
          </select>
        </div>

        <!-- Refresh Button -->
        <button @click="fetchActiveReport" class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold px-3 bg-white shadow-sm d-inline-flex align-items-center" type="button" style="height: 38px;">
          <i class="bi bi-arrow-clockwise me-1.5"></i> Refresh
        </button>

        <!-- Streamed CSV Export -->
        <button @click="triggerCsvExport" class="btn btn-outline-primary btn-sm rounded-3 fw-semibold px-3 bg-white shadow-sm d-inline-flex align-items-center" type="button" style="height: 38px;">
          <i class="bi bi-download me-1.5"></i> Export CSV
        </button>
      </div>
    </div>

    <!-- REPORT DOMAIN NAVIGATION TABS (ALL 8 FUNCTIONAL TABS) -->
    <ul class="nav nav-pills nav-fill mb-4 bg-white p-2 rounded-4 shadow-sm border">
      <li class="nav-item">
        <button :class="['nav-link', 'fw-semibold', activeTab === 'sales' ? 'active' : '']" @click="changeTab('sales')" type="button">
          <i class="bi bi-graph-up-arrow me-1.5"></i> Sales Report
        </button>
      </li>
      <li class="nav-item">
        <button :class="['nav-link', 'fw-semibold', activeTab === 'inventory' ? 'active' : '']" @click="changeTab('inventory')" type="button">
          <i class="bi bi-box-seam me-1.5"></i> Inventory Report
        </button>
      </li>
      <li class="nav-item">
        <button :class="['nav-link', 'fw-semibold', activeTab === 'customers' ? 'active' : '']" @click="changeTab('customers')" type="button">
          <i class="bi bi-people me-1.5"></i> Customer Khata
        </button>
      </li>
      <li class="nav-item">
        <button :class="['nav-link', 'fw-semibold', activeTab === 'suppliers' ? 'active' : '']" @click="changeTab('suppliers')" type="button">
          <i class="bi bi-truck me-1.5"></i> Supplier Payables
        </button>
      </li>
      <li class="nav-item">
        <button :class="['nav-link', 'fw-semibold', activeTab === 'expenses' ? 'active' : '']" @click="changeTab('expenses')" type="button">
          <i class="bi bi-receipt me-1.5"></i> Expense Audit
        </button>
      </li>
      <li class="nav-item">
        <button :class="['nav-link', 'fw-semibold', activeTab === 'accounts' ? 'active' : '']" @click="changeTab('accounts')" type="button">
          <i class="bi bi-wallet2 me-1.5"></i> Financial Accounts
        </button>
      </li>
      <li class="nav-item">
        <button :class="['nav-link', 'fw-semibold', activeTab === 'branches' ? 'active' : '']" @click="changeTab('branches')" type="button">
          <i class="bi bi-building me-1.5"></i> Branch Comparison
        </button>
      </li>
      <li class="nav-item">
        <button :class="['nav-link', 'fw-semibold', activeTab === 'profitability' ? 'active' : '']" @click="changeTab('profitability')" type="button" id="tab-profitability">
          <i class="bi bi-cash-coin me-1.5"></i> Net Profit Statement
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

      <!-- EXECUTIVE PRACTICAL EXPOSURE & UTILITY GUIDE -->
      <div v-if="currentExposure" class="card border-0 bg-white shadow-sm rounded-4 p-4 mb-4 border-start border-4 border-primary">
        <div class="d-flex align-items-start gap-3">
          <div class="p-2.5 bg-primary-subtle text-primary rounded-3 fs-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; flex-shrink: 0;">
            <i :class="['bi', currentExposure.icon]"></i>
          </div>
          <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2 pb-2 border-bottom">
              <div>
                <h5 class="fw-bold mb-0 text-dark">{{ currentExposure.title }}</h5>
                <span class="text-muted small">Standard operating procedure, financial visibility, and retail utility breakdown</span>
              </div>
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1.5 small fw-bold">
                <i class="bi bi-shield-check me-1"></i> {{ currentExposure.badge }}
              </span>
            </div>
            <div class="row g-3 small mt-1">
              <div class="col-md-4">
                <div class="p-2.5 rounded-3 bg-light h-100 border">
                  <span class="text-uppercase text-primary fw-bold d-block mb-1" style="font-size: 11px; letter-spacing: 0.05em;">
                    <i class="bi bi-gear-wide-connected me-1"></i> How It Is Utilized:
                  </span>
                  <p class="text-secondary mb-0 leading-relaxed">{{ currentExposure.utilization }}</p>
                </div>
              </div>
              <div class="col-md-4">
                <div class="p-2.5 rounded-3 bg-light h-100 border">
                  <span class="text-uppercase text-info fw-bold d-block mb-1" style="font-size: 11px; letter-spacing: 0.05em;">
                    <i class="bi bi-pie-chart me-1"></i> What It Presents:
                  </span>
                  <p class="text-secondary mb-0 leading-relaxed">{{ currentExposure.presentation }}</p>
                </div>
              </div>
              <div class="col-md-4">
                <div class="p-2.5 rounded-3 bg-success-subtle h-100 border border-success-subtle">
                  <span class="text-uppercase text-success fw-bold d-block mb-1" style="font-size: 11px; letter-spacing: 0.05em;">
                    <i class="bi bi-graph-up-arrow me-1"></i> Business Benefit &amp; ROI:
                  </span>
                  <p class="text-dark fw-semibold mb-0 leading-relaxed">{{ currentExposure.benefit }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 1. SALES REPORT -->
      <div v-if="activeTab === 'sales'">
        <div class="row g-4 mb-4" v-if="reportData?.summary || reportData?.total_sales !== undefined">
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Gross Sales</span>
              <div class="fs-4 fw-bold text-dark mt-1">PKR {{ Number(reportData.summary?.gross_sales ?? reportData.total_sales ?? 0).toLocaleString() }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Return Adjustment</span>
              <div class="fs-4 fw-bold text-warning mt-1">PKR {{ Number(reportData.summary?.return_adjustment ?? 0).toLocaleString() }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Return-Adjusted Net Sales</span>
              <div class="fs-4 fw-bold text-success mt-1">PKR {{ Number(reportData.summary?.net_sales ?? reportData.total_sales ?? 0).toLocaleString() }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Gross Profit</span>
              <div class="fs-4 fw-bold text-dark mt-1">{{ reportData.summary?.cogs_status === 'partial' ? 'Partial' : 'PKR ' + Number(reportData.summary?.gross_profit ?? 0).toLocaleString() }}</div>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0 text-dark">Completed Sales Transactions</h6>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">
              {{ (reportData?.sales || []).length }} Transactions
            </span>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
              <thead class="table-light">
                <tr>
                  <th>Invoice #</th>
                  <th>Date</th>
                  <th v-if="branches.length > 1">Branch</th>
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
                  <td v-if="branches.length > 1">{{ s.branch_name }}</td>
                  <td>{{ s.customer_name }}</td>
                  <td class="text-end">PKR {{ Number(s.paid_amount || 0).toLocaleString() }}</td>
                  <td class="text-end text-danger">PKR {{ Number(s.due_amount || 0).toLocaleString() }}</td>
                  <td class="text-end fw-bold">PKR {{ Number(s.total ?? s.total_amount ?? 0).toLocaleString() }}</td>
                  <td class="text-center"><span class="badge bg-success-subtle text-success">{{ s.status || 'completed' }}</span></td>
                </tr>
                <tr v-if="!(reportData?.sales || []).length">
                  <td :colspan="branches.length > 1 ? 8 : 7" class="text-center text-muted py-4">No sales records found for this period.</td>
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
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0 text-dark">{{ branches.length > 1 ? 'Branch Inventory Stock Ledger' : 'Inventory Stock Ledger' }}</h6>
            <span class="badge bg-secondary-subtle text-secondary rounded-pill">
              {{ (reportData?.inventories || reportData?.items || []).length }} Items
            </span>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
              <thead class="table-light">
                <tr>
                  <th>Product Name</th>
                  <th>SKU</th>
                  <th>Category</th>
                  <th v-if="branches.length > 1">Branch</th>
                  <th class="text-center">Stock On Hand</th>
                  <th class="text-center">Min Stock</th>
                  <th class="text-end">Current Cost</th>
                  <th class="text-end">Total Valuation</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="inv in (reportData?.inventories || reportData?.items || [])" :key="inv.id">
                  <td class="fw-bold text-dark">{{ inv.product_name }}</td>
                  <td class="text-muted">{{ inv.sku }}</td>
                  <td>{{ inv.category || inv.category_name || 'General' }}</td>
                  <td v-if="branches.length > 1">{{ inv.branch_name }}</td>
                  <td class="text-center">
                    <span :class="'badge bg-' + (Number(inv.quantity_on_hand) <= Number(inv.minimum_stock) ? 'danger' : 'success') + '-subtle text-' + (Number(inv.quantity_on_hand) <= Number(inv.minimum_stock) ? 'danger' : 'success')">
                      {{ inv.quantity_on_hand }}
                    </span>
                  </td>
                  <td class="text-center">{{ inv.minimum_stock }}</td>
                  <td class="text-end">PKR {{ Number(inv.current_cost_price || inv.unit_cost || 0).toLocaleString() }}</td>
                  <td class="text-end fw-bold">PKR {{ Number(inv.stock_value || 0).toLocaleString() }}</td>
                </tr>
                <tr v-if="!(reportData?.inventories || reportData?.items || []).length">
                  <td :colspan="branches.length > 1 ? 8 : 7" class="text-center text-muted py-4">No inventory items found.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- 3. CUSTOMERS REPORT -->
      <div v-if="activeTab === 'customers'">
        <div class="row g-4 mb-4" v-if="reportData?.summary || reportData?.customers">
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Total Khata Accounts</span>
              <div class="fs-4 fw-bold text-dark mt-1">{{ (reportData?.customers || []).length }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Total Sales Volume</span>
              <div class="fs-4 fw-bold text-primary mt-1">PKR {{ Number(reportData?.summary?.total_sales_volume ?? (reportData?.customers || []).reduce((sum, c) => sum + Number(c.sales_volume || c.total_purchased || 0), 0)).toLocaleString() }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Outstanding Receivable</span>
              <div class="fs-4 fw-bold text-danger mt-1">PKR {{ Number(reportData?.summary?.outstanding ?? (reportData?.customers || []).reduce((sum, c) => sum + Number(c.outstanding_balance ?? c.outstanding ?? 0), 0)).toLocaleString() }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Customer Advance Credit</span>
              <div class="fs-4 fw-bold text-success mt-1">PKR {{ Number(reportData?.summary?.customer_credit ?? (reportData?.customers || []).reduce((sum, c) => sum + Number(c.advance_credit ?? c.credit ?? 0), 0)).toLocaleString() }}</div>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0 text-dark">Registered Customer Account Ledger</h6>
            <span class="badge bg-primary-subtle text-primary rounded-pill">
              {{ (reportData?.customers || []).length }} Active Ledgers
            </span>
          </div>
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
                  <td class="text-end fw-semibold">PKR {{ Number(c.sales_volume ?? c.total_purchased ?? 0).toLocaleString() }}</td>
                  <td class="text-center">{{ c.transaction_count ?? c.sales_count ?? 1 }}</td>
                  <td class="text-end text-success">PKR {{ Number(c.payments_collected ?? c.total_paid ?? 0).toLocaleString() }}</td>
                  <td class="text-end fw-bold text-danger">PKR {{ Number(c.outstanding_balance ?? c.outstanding ?? 0).toLocaleString() }}</td>
                  <td class="text-end fw-bold text-primary">PKR {{ Number(c.advance_credit ?? c.credit ?? 0).toLocaleString() }}</td>
                </tr>
                <tr v-if="!(reportData?.customers || []).length">
                  <td colspan="7" class="text-center text-muted py-4">No customer ledger records found.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- 4. SUPPLIERS REPORT -->
      <div v-if="activeTab === 'suppliers'">
        <div class="row g-4 mb-4" v-if="reportData?.summary || reportData?.suppliers">
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Active Vendors</span>
              <div class="fs-4 fw-bold text-dark mt-1">{{ (reportData?.suppliers || []).length }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Total Purchases Volume</span>
              <div class="fs-4 fw-bold text-dark mt-1">PKR {{ Number(reportData?.summary?.total_purchases ?? (reportData?.suppliers || []).reduce((sum, s) => sum + Number(s.purchase_volume || s.total_purchased || 0), 0)).toLocaleString() }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Total Payments Made</span>
              <div class="fs-4 fw-bold text-success mt-1">PKR {{ Number(reportData?.summary?.total_payments ?? (reportData?.suppliers || []).reduce((sum, s) => sum + Number(s.payments_made || s.total_paid || 0), 0)).toLocaleString() }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Reconciled Payables Due</span>
              <div class="fs-4 fw-bold text-danger mt-1">PKR {{ Number(reportData?.summary?.total_payable ?? (reportData?.suppliers || []).reduce((sum, s) => sum + Number(s.reconciled_payable ?? s.payable ?? 0), 0)).toLocaleString() }}</div>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0 text-dark">Supplier Procurement &amp; Payable Ledger</h6>
            <span class="badge bg-secondary-subtle text-secondary rounded-pill">
              {{ (reportData?.suppliers || []).length }} Suppliers
            </span>
          </div>
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
                  <td class="text-end fw-semibold">PKR {{ Number(sup.purchase_volume ?? sup.total_purchased ?? 0).toLocaleString() }}</td>
                  <td class="text-center">{{ sup.purchase_count ?? sup.purchases_count ?? 1 }}</td>
                  <td class="text-end text-success">PKR {{ Number(sup.payments_made ?? sup.total_paid ?? 0).toLocaleString() }}</td>
                  <td class="text-end fw-bold text-danger">PKR {{ Number(sup.reconciled_payable ?? sup.payable ?? 0).toLocaleString() }}</td>
                </tr>
                <tr v-if="!(reportData?.suppliers || []).length">
                  <td colspan="6" class="text-center text-muted py-4">No supplier records found.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- 5. EXPENSES REPORT -->
      <div v-if="activeTab === 'expenses'">
        <div class="row g-4 mb-4" v-if="reportData?.expenses || reportData?.total_expenses !== undefined">
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Total Posted Overhead</span>
              <div class="fs-4 fw-bold text-danger mt-1">PKR {{ Number(reportData?.total_expenses ?? (reportData?.expenses || []).reduce((sum, e) => sum + Number(e.amount || 0), 0)).toLocaleString() }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Expense Entries</span>
              <div class="fs-4 fw-bold text-dark mt-1">{{ (reportData?.expenses || []).length }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Average Ticket</span>
              <div class="fs-4 fw-bold text-primary mt-1">PKR {{ Number((reportData?.expenses || []).length > 0 ? ((reportData?.total_expenses || 0) / (reportData?.expenses || []).length) : 0).toLocaleString(undefined, { maximumFractionDigits: 0 }) }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Audit Ledger Status</span>
              <div class="fs-4 fw-bold text-success mt-1">Reconciled</div>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0 text-dark">Posted Expenses Audit (Total: PKR {{ Number(reportData?.total_expenses || 0).toLocaleString() }})</h6>
            <span class="badge bg-danger-subtle text-danger rounded-pill">
              {{ (reportData?.expenses || []).length }} Ledger Entries
            </span>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
              <thead class="table-light">
                <tr>
                  <th>Expense ID</th>
                  <th>Date</th>
                  <th>Category</th>
                  <th>Description</th>
                  <th v-if="branches.length > 1">Branch</th>
                  <th>Account</th>
                  <th class="text-end">Amount</th>
                  <th class="text-center">Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="e in reportData?.expenses || []" :key="e.id">
                  <td class="fw-bold text-dark">EXP-{{ e.id }}</td>
                  <td class="text-muted">{{ e.date }}</td>
                  <td><span class="badge bg-info-subtle text-info">{{ e.category || 'Operations' }}</span></td>
                  <td>{{ e.description || 'N/A' }}</td>
                  <td v-if="branches.length > 1">{{ e.branch_name }}</td>
                  <td>{{ e.account_name || 'Main Cash Register' }}</td>
                  <td class="text-end fw-bold text-danger">PKR {{ Number(e.amount).toLocaleString() }}</td>
                  <td class="text-center"><span class="badge bg-success-subtle text-success">{{ e.status || 'posted' }}</span></td>
                </tr>
                <tr v-if="!(reportData?.expenses || []).length">
                  <td :colspan="branches.length > 1 ? 8 : 7" class="text-center text-muted py-4">No expense records found.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- 6. FINANCIAL ACCOUNTS REPORT -->
      <div v-if="activeTab === 'accounts'">
        <div class="row g-4 mb-4" v-if="reportData?.accounts">
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Total Accounts Managed</span>
              <div class="fs-4 fw-bold text-dark mt-1">{{ (reportData?.accounts || []).length }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Net Liquid Balance</span>
              <div class="fs-4 fw-bold text-primary mt-1">PKR {{ Number((reportData?.accounts || []).reduce((sum, a) => sum + Number(a.current_balance || 0), 0)).toLocaleString() }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Period Total Inflows</span>
              <div class="fs-4 fw-bold text-success mt-1">PKR {{ Number((reportData?.accounts || []).reduce((sum, a) => sum + Number(a.period_inflows || 0), 0)).toLocaleString() }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Period Total Outflows</span>
              <div class="fs-4 fw-bold text-danger mt-1">PKR {{ Number((reportData?.accounts || []).reduce((sum, a) => sum + Number(a.period_outflows || 0), 0)).toLocaleString() }}</div>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0 text-dark">Financial Accounts Cash Flow Ledger</h6>
            <span class="badge bg-primary-subtle text-primary rounded-pill">
              {{ (reportData?.accounts || []).length }} Accounts
            </span>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
              <thead class="table-light">
                <tr>
                  <th>Account Name</th>
                  <th>Type</th>
                  <th v-if="branches.length > 1">Branch</th>
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
                  <td v-if="branches.length > 1">{{ acc.branch_name }}</td>
                  <td class="text-end fw-bold text-dark">PKR {{ Number(acc.current_balance).toLocaleString() }}</td>
                  <td class="text-end text-success">PKR {{ Number(acc.period_inflows).toLocaleString() }}</td>
                  <td class="text-end text-danger">PKR {{ Number(acc.period_outflows).toLocaleString() }}</td>
                  <td class="text-center"><span class="badge bg-success-subtle text-success">{{ acc.status || 'active' }}</span></td>
                </tr>
                <tr v-if="!(reportData?.accounts || []).length">
                  <td :colspan="branches.length > 1 ? 7 : 6" class="text-center text-muted py-4">No financial account records found.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- 7. BRANCHES REPORT (Multi-Branch Comparative Performance Matrix) -->
      <div v-if="activeTab === 'branches'">
        <!-- Top Branch Comparison KPI Cards -->
        <div class="row g-4 mb-4" v-if="reportData?.branches">
          <div v-for="b in reportData.branches" :key="'card-' + b.branch_id" class="col-md-4 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold text-dark fs-6">{{ b.branch_name }}</span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">
                  {{ b.transaction_count }} txns
                </span>
              </div>
              <div class="mb-2">
                <small class="text-secondary fw-semibold">Net Commercial Sales</small>
                <div class="fs-5 fw-bold text-success">PKR {{ Number(b.net_sales || 0).toLocaleString() }}</div>
              </div>
              <div class="d-flex justify-content-between text-muted small py-1 border-top">
                <span>Gross Profit:</span>
                <span class="fw-semibold text-dark">{{ b.gross_profit !== null && b.gross_profit !== undefined ? 'PKR ' + Number(b.gross_profit).toLocaleString() : 'N/A' }}</span>
              </div>
              <div class="d-flex justify-content-between text-muted small py-1">
                <span>Expenses:</span>
                <span class="fw-semibold text-danger">PKR {{ Number(b.posted_expenses ?? b.expenses ?? 0).toLocaleString() }}</span>
              </div>
              <div class="d-flex justify-content-between text-muted small py-1 border-top pt-2">
                <span>Cash Drawer:</span>
                <span class="fw-bold text-primary">PKR {{ Number(b.cash_balance ?? b.cash_drawer_balance ?? 0).toLocaleString() }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Comparative Performance Matrix -->
        <div class="card border-0 shadow-sm rounded-4 p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h5 class="fw-bold mb-1 text-dark">Side-by-Side Multi-Branch Performance Matrix</h5>
              <p class="text-muted small mb-0">Cross-store KPI drilldown across commercial sales, margin, operating overhead, and cash balances.</p>
            </div>
            <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center" @click="triggerCsvExport">
              <i class="bi bi-download me-1.5"></i> Export Matrix CSV
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
              <thead class="table-light">
                <tr>
                  <th>Branch Name</th>
                  <th class="text-end">Net Sales</th>
                  <th class="text-center">Transactions</th>
                  <th class="text-end">Avg Basket</th>
                  <th class="text-end">Gross Profit</th>
                  <th class="text-end">Posted Expenses</th>
                  <th class="text-end">Expense Ratio %</th>
                  <th class="text-end">Cash Drawer</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="b in reportData?.branches || []" :key="b.branch_id">
                  <td class="fw-bold text-dark">
                    <i class="bi bi-shop me-1 text-primary"></i>
                    {{ b.branch_name }}
                  </td>
                  <td class="text-end fw-bold text-success">PKR {{ Number(b.net_sales || 0).toLocaleString() }}</td>
                  <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ b.transaction_count || 0 }}</span></td>
                  <td class="text-end text-muted">
                    PKR {{ Number(b.transaction_count > 0 ? (b.net_sales / b.transaction_count) : 0).toLocaleString(undefined, { maximumFractionDigits: 0 }) }}
                  </td>
                  <td class="text-end fw-semibold text-dark">
                    {{ b.gross_profit !== null && b.gross_profit !== undefined ? 'PKR ' + Number(b.gross_profit).toLocaleString() : 'N/A' }}
                  </td>
                  <td class="text-end text-danger">PKR {{ Number(b.posted_expenses ?? b.expenses ?? 0).toLocaleString() }}</td>
                  <td class="text-end">
                    <span :class="['badge rounded-pill', (b.net_sales > 0 && ((b.posted_expenses ?? b.expenses ?? 0) / b.net_sales) > 0.4) ? 'bg-warning-subtle text-warning-emphasis' : 'bg-light text-secondary border']">
                      {{ b.net_sales > 0 ? (((b.posted_expenses ?? b.expenses ?? 0) / b.net_sales) * 100).toFixed(1) + '%' : '0.0%' }}
                    </span>
                  </td>
                  <td class="text-end fw-bold text-primary">PKR {{ Number(b.cash_balance ?? b.cash_drawer_balance ?? 0).toLocaleString() }}</td>
                </tr>
                <tr v-if="!(reportData?.branches || []).length">
                  <td colspan="8" class="text-center text-muted py-4">No branch comparison data available.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- 8. CONSOLIDATED NET PROFIT STATEMENT -->
      <div v-if="activeTab === 'profitability'">
        <!-- Top Metrics Cards -->
        <div class="row g-4 mb-4" v-if="reportData">
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Net Commercial Sales</span>
              <div class="fs-4 fw-bold text-primary mt-1">PKR {{ Number(reportData.net_sales || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</div>
              <span class="text-muted small mt-1">Invoice Sales - Returns</span>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Net COGS (Cost of Goods)</span>
              <div class="fs-4 fw-bold text-dark mt-1">PKR {{ Number(reportData.net_cogs ?? reportData.cogs ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</div>
              <span class="text-muted small mt-1">Historical Snapshot Cost</span>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
              <span class="text-secondary small fw-semibold">Operating Expenses</span>
              <div class="fs-4 fw-bold text-danger mt-1">PKR {{ Number(reportData.operating_expenses ?? reportData.expenses ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</div>
              <span class="text-muted small mt-1">Posted Store Overhead</span>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3" :class="Number(reportData.net_profit || 0) >= 0 ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis'">
              <span class="small fw-semibold">Operational Net Profit</span>
              <div class="fs-3 fw-bold mt-1">PKR {{ Number(reportData.net_profit || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</div>
              <span class="small fw-semibold mt-1">Net Margin: {{ reportData.net_margin_percent ?? '0.0' }}%</span>
            </div>
          </div>
        </div>

        <!-- Detailed Step-Down Statement Card -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4" v-if="reportData">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Consolidated Operational Profit &amp; Loss Breakdown</h5>
            <span class="badge bg-secondary-subtle text-secondary small">{{ reportData.accounting_basis || 'Accrual Basis' }}</span>
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Financial Component</th>
                  <th>Calculation Derivation</th>
                  <th class="text-end">Amount (PKR)</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="fw-semibold">Gross Goods Sales</td>
                  <td class="text-secondary small">Sum of completed sale items before discounts</td>
                  <td class="text-end fw-bold text-dark">{{ Number(reportData.gross_goods_sales ?? reportData.net_sales ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</td>
                </tr>
                <tr>
                  <td class="fw-semibold text-danger">Less: Order Discounts</td>
                  <td class="text-secondary small">Direct point-of-sale customer reductions</td>
                  <td class="text-end text-danger">- {{ Number(reportData.discounts ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</td>
                </tr>
                <tr>
                  <td class="fw-semibold text-muted">Add: Invoiced Tax / Surcharges</td>
                  <td class="text-secondary small">Customer-billed operational invoice charge</td>
                  <td class="text-end text-muted">+ {{ Number(reportData.tax ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</td>
                </tr>
                <tr class="table-light fw-bold">
                  <td>Commercial Invoiced Sales</td>
                  <td class="text-secondary small">Total amount invoiced to customers</td>
                  <td class="text-end">{{ Number(reportData.commercial_invoice_sales ?? reportData.net_sales ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</td>
                </tr>
                <tr>
                  <td class="fw-semibold text-danger">Less: Economic Returns</td>
                  <td class="text-secondary small">Customer refunds processed and settled in period</td>
                  <td class="text-end text-danger">- {{ Number(reportData.economic_returns ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</td>
                </tr>
                <tr class="table-primary fw-bold">
                  <td class="text-primary">Net Commercial Sales (Profit Revenue Basis)</td>
                  <td class="small text-primary">Commercial Invoiced Sales - Economic Returns</td>
                  <td class="text-end text-primary">{{ Number(reportData.net_sales ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</td>
                </tr>
                <tr>
                  <td class="fw-semibold">Gross Cost of Goods Sold (COGS)</td>
                  <td class="text-secondary small">Sum of sold quantity × historical cost price snapshot</td>
                  <td class="text-end text-dark">{{ Number(reportData.gross_cogs ?? reportData.net_cogs ?? reportData.cogs ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</td>
                </tr>
                <tr>
                  <td class="fw-semibold text-success">Less: Restockable Returned Goods Cost</td>
                  <td class="text-secondary small">Cost of returned items accepted back into active stock</td>
                  <td class="text-end text-success">- {{ Number(reportData.returned_cogs ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</td>
                </tr>
                <tr class="table-light fw-bold">
                  <td>Net Cost of Goods Sold</td>
                  <td class="text-secondary small">Gross COGS - Restockable Returned Cost</td>
                  <td class="text-end">{{ Number(reportData.net_cogs ?? reportData.cogs ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</td>
                </tr>
                <tr class="table-info fw-bold">
                  <td class="text-info-emphasis">Gross Profit</td>
                  <td class="small text-info-emphasis">Net Commercial Sales - Net COGS (Gross Margin: {{ reportData.gross_margin_percent ?? reportData.margin_percentage ?? '0.0' }}%)</td>
                  <td class="text-end text-info-emphasis">{{ Number(reportData.gross_profit ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</td>
                </tr>
                <tr>
                  <td class="fw-semibold text-danger">Less: Operating Expenses</td>
                  <td class="text-secondary small">Store operational overhead (Posted store expenses)</td>
                  <td class="text-end text-danger">- {{ Number(reportData.operating_expenses ?? reportData.expenses ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</td>
                </tr>
                <tr class="table-success fw-bold fs-6 border-top border-2">
                  <td class="text-success">Operational Net Profit</td>
                  <td class="small text-success">Gross Profit - Operating Expenses (Net Commercial Margin: {{ reportData.net_margin_percent ?? '0.0' }}%)</td>
                  <td class="text-end text-success">PKR {{ Number(reportData.net_profit ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</td>
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
import { ref, onMounted, computed, watch } from 'vue'
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

const reportExposures = {
  sales: {
    title: 'Sales Report & Revenue Intelligence',
    icon: 'bi-graph-up-arrow',
    badge: 'Commercial Cashflow',
    utilization: 'Audited daily during counter register closing to reconcile physical cash takings against total customer billings.',
    presentation: 'Gross turnover, customer discounts at POS, customer returns/refunds, net invoiced sales, and gross margin.',
    benefit: 'Stops cash drawer leakage, tracks cashier discount habits, and proves whether turnover is yielding genuine trading margins.'
  },
  inventory: {
    title: 'Inventory Stock & Working Capital Ledger',
    icon: 'bi-box-seam',
    badge: 'Working Capital',
    utilization: 'Used by inventory managers and purchase officers during replenishment planning and weekly stock-takes.',
    presentation: 'Exact units on hand, valuation at purchase cost, low-stock reorder thresholds, and out-of-stock items.',
    benefit: 'Eliminates lost sales from stockouts on high-velocity items and prevents working capital from being trapped in dead stock.'
  },
  customers: {
    title: 'Customer Khata Credit & Recovery Ledger',
    icon: 'bi-people',
    badge: 'Receivables Control',
    utilization: 'Used by shop owners to review customer credit lines, send payment reminders, and record ledger collections.',
    presentation: 'Full transaction histories, total sales volume, installment collections, running overdue debt, and advance deposits.',
    benefit: 'Replaces messy paper notebooks with a verifiable digital audit trail, reduces bad debts, and accelerates cash recovery.'
  },
  suppliers: {
    title: 'Supplier Procurement & Payable Ledger',
    icon: 'bi-truck',
    badge: 'Payables Reconciliation',
    utilization: 'Reviewed before issuing distributor cheques or scheduling weekly supplier payouts to verify invoices.',
    presentation: 'Total wholesale procurement volume, invoice count, payments remitted, and net reconciled payable balances.',
    benefit: 'Prevents duplicate invoice payouts, safeguards distributor cash discounts, and maintains good credit standing with vendors.'
  },
  expenses: {
    title: 'Store Operational Overhead & Expense Audit',
    icon: 'bi-receipt',
    badge: 'Overhead Governance',
    utilization: 'Reviewed weekly and monthly by management to monitor store operating costs against monthly budgets.',
    presentation: 'Line-by-line itemized expenses across categories (rent, utilities, salaries, fuel, bags) with payment account references.',
    benefit: 'Identifies operational cost spikes early, enforces department budget discipline, and organizes tax-deductible expenditures.'
  },
  accounts: {
    title: 'Financial Accounts & Multi-Channel Cash Flow Ledger',
    icon: 'bi-wallet2',
    badge: 'Treasury & Liquidity',
    utilization: 'Used by the retail business owner and accountant to verify liquid balances across cash drawers, banks, and digital wallets.',
    presentation: 'Current closing balances, period cash inflows (POS collections), outflows (bills/suppliers), and account statuses.',
    benefit: 'Provides complete visibility over all financial assets, separates physical cash from bank balances, and prevents embezzlement.'
  },
  branches: {
    title: 'Multi-Branch Comparative Performance Matrix',
    icon: 'bi-building',
    badge: 'Chain Benchmarking',
    utilization: 'Used by retail chain owners and general managers to compare performance across different store locations.',
    presentation: 'Side-by-side branch comparison of net sales, transaction counts, average basket size, gross margin, and operating overhead.',
    benefit: 'Pinpoints underperforming stores, benchmarks staff efficiency, and directs capital and inventory to top-performing outlets.'
  },
  profitability: {
    title: 'Consolidated Commercial Net Profit Statement (P&L)',
    icon: 'bi-cash-coin',
    badge: 'True Bottom-Line',
    utilization: 'The primary executive statement for owners to measure true bottom-line earnings after all costs and expenses.',
    presentation: 'Step-down accounting statement: Gross Sales - Discounts - Returns = Net Sales; Net Sales - COGS = Gross Profit; Gross Profit - Expenses = Net Profit.',
    benefit: 'Ensures owners never mistake high daily sales volume for actual profit, revealing true net commercial margins.'
  }
};

const currentExposure = computed(() => reportExposures[activeTab.value] || null);

const userRole = computed(() => authStore.user?.role || authStore.userRole || 'Business Owner')
const isOwner = computed(() => true) // All 8 reports accessible to owner / demo admin

const isDateSensitiveTab = computed(() => ['sales', 'expenses', 'accounts', 'branches', 'profitability'].includes(activeTab.value))

const changeTab = (tab) => {
  activeTab.value = tab
  router.replace({ path: '/reports', query: { ...route.query, tab } })
  fetchActiveReport()
}

const fetchBranches = async () => {
  try {
    const res = await api.get('/branches')
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
    const data = response.data

    // Unpack if wrapped inside { data: { ... } }
    if (data && data.data && typeof data.data === 'object' && !Array.isArray(data.data) && (data.data.summary || data.data.sales || data.data.customers || data.data.suppliers || data.data.expenses || data.data.accounts || data.data.branches || data.data.net_sales !== undefined)) {
      reportData.value = data.data
    } else {
      reportData.value = data
    }
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

watch(() => route.query.tab, (newTab) => {
  if (newTab && newTab !== activeTab.value) {
    activeTab.value = newTab
    fetchActiveReport()
  }
})

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
  padding: 0.65rem 1rem;
  transition: all 0.2s ease;
  white-space: nowrap;
}
.nav-pills .nav-link:hover {
  background-color: #f1f5f9;
  color: #0f172a;
}
.nav-pills .nav-link.active {
  background-color: #2563eb;
  color: #ffffff;
  box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2), 0 2px 4px -2px rgba(37, 99, 235, 0.2);
}
</style>
