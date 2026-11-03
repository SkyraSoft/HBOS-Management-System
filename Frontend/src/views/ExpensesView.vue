<template>
  <div class="expenses-page-wrapper">
    <!-- TOP SUCCESS ALERT -->
    <transition name="alert-slide">
      <div v-if="successAlertMessage" class="expense-top-alert" role="alert">
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
      <div v-if="errorAlertMessage" class="expense-top-alert" style="background: #fdf2f2; border-color: #f87171; color: #dc2626;" role="alert">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
          <div>
            <strong>Error!</strong> {{ errorAlertMessage }}
          </div>
        </div>
        <button type="button" class="btn-close" @click="errorAlertMessage = ''" aria-label="Close"></button>
      </div>
    </transition>

    <div class="expense-main-container">

      <!-- ====================================================
           VIEW A: EXPENSE CATEGORIES MANAGEMENT
           (Dedicated view when viewing/managing categories)
      ==================================================== -->
      <div v-if="currentView === 'categories'" class="categories-view mb-4">
        <!-- TOP BREADCRUMB / BACK BAR -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
          <button class="btn btn-sm btn-light border rounded-3 fw-semibold px-3 py-2 shadow-sm d-flex align-items-center gap-2" @click="closeCategoriesView" type="button">
            <i class="bi bi-arrow-left"></i> Back to Expenses &amp; Reports
          </button>
          <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 font-monospace">
            {{ expensesByCategory.length }} Active Categories
          </span>
        </div>

        <div class="expense-topbar mb-4">
          <div>
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="expense-badge-pill">Category Governance</span>
              <span class="text-muted small">&bull; Expense Classifications</span>
            </div>
            <h1 class="expense-main-title">Expense Categories &amp; Budget Limits</h1>
            <p class="expense-main-sub">Categorize store expenditures, assign monthly budget caps, and track category spending share.</p>
          </div>
        </div>

        <div class="row g-4">
          <!-- CATEGORY BREAKDOWN LIST -->
          <div class="col-12 col-xl-7">
            <div class="expense-panel-card h-100 p-4">
              <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <h4 class="fw-bold m-0 text-dark fs-5">Active Expense Categories</h4>
                <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill small">Allocation Overview</span>
              </div>

              <div class="category-breakdown-list">
                <div v-for="(cat, index) in expensesByCategory" :key="cat.name" class="category-breakdown-item mb-3 p-3 bg-light rounded-3 border">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-2">
                      <span class="category-color-dot" :style="{ background: getCategoryColor(index) }"></span>
                      <strong class="text-dark fs-6">{{ cat.name }}</strong>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                      <div class="text-end">
                        <span class="fw-bold text-dark">{{ formatCurrency(cat.amount) }}</span>
                        <span class="text-muted small ms-2">({{ cat.percentage }}%)</span>
                      </div>
                      <button 
                        type="button" 
                        class="btn btn-sm btn-outline-danger rounded-2 px-2 py-0.5" 
                        style="font-size: 11px;"
                        @click="handleDeleteCategory(cat.name)"
                        :disabled="isDeletingCategory"
                        title="Delete Category"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </div>

                  <div class="progress" style="height: 8px; border-radius: 4px; background: #e2e8f0;">
                    <div 
                      class="progress-bar rounded-pill" 
                      :style="{ width: cat.percentage + '%', background: getCategoryColor(index) }"
                    ></div>
                  </div>
                </div>

                <div v-if="expensesByCategory.length === 0" class="text-center py-5 text-muted">
                  <i class="bi bi-tags fs-1 opacity-50 d-block mb-2"></i>
                  <h6>No Categories Configured</h6>
                  <p class="small text-muted mb-0">Use the form on the right to add your first expense category.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- ADD CATEGORY & BUDGET SETTINGS -->
          <div class="col-12 col-xl-5">
            <div class="expense-panel-card h-100 p-4">
              <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <h4 class="fw-bold m-0 text-dark fs-5">Add New Category</h4>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 small">New Class</span>
              </div>

              <form @submit.prevent="handleAddCategory" class="mb-4">
                <div class="mb-3">
                  <label class="form-label fw-semibold small text-secondary">Category Name <span class="text-danger">*</span></label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="newCategoryName" 
                    placeholder="e.g. Utilities, Shop Rent, Marketing" 
                    required
                  />
                  <small class="text-muted" style="font-size: 11px;">Name used on receipts and expenditure ledgers</small>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-semibold py-2 rounded-3" :disabled="isAddingCategory">
                  <span v-if="isAddingCategory" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="bi bi-plus-lg me-1"></i> Add Category
                </button>
              </form>

              <!-- BUDGET LIMIT PROGRESS BOX -->
              <div class="p-3 bg-light rounded-3 border">
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted small fw-semibold">Monthly Expense Budget</span>
                  <strong class="text-dark">{{ formatCurrency(monthlyLimit) }}</strong>
                </div>
                <div class="progress mb-2" style="height: 10px; border-radius: 6px; background: #e2e8f0;">
                  <div 
                    class="progress-bar" 
                    :class="Number(budgetUtilization) > 85 ? 'bg-danger' : 'bg-primary'"
                    :style="{ width: budgetUtilization + '%' }"
                  ></div>
                </div>
                <div class="d-flex justify-content-between text-muted small" style="font-size: 11.5px;">
                  <span>Spent: {{ formatCurrency(totalExpensesThisMonth) }}</span>
                  <span>Utilization: {{ budgetUtilization }}%</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ====================================================
           VIEW B: UNIFIED EXPENSES & REPORTS HUB
           (List + Analytics Reports integrated together!)
      ==================================================== -->
      <div v-else class="main-expenses-hub">
        <!-- PAGE HEADER -->
        <div class="expense-topbar">
          <div>
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="expense-badge-pill">Cost Control &amp; Ledger</span>
              <span class="text-muted small">&bull; Real-time Operations</span>
            </div>
            <h1 class="expense-main-title">Expenses &amp; Cost Analytics</h1>
            <p class="expense-main-sub">Track operating expenditures, monitor category budgets, and analyze store cost trends in one place.</p>
          </div>

          <div class="d-flex align-items-center gap-2 flex-wrap">
            <button class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold px-3 shadow-sm d-flex align-items-center gap-1.5" @click="exportExpenses" type="button" style="height: 38px;">
              <i class="bi bi-file-earmark-spreadsheet"></i> Export Report
            </button>
            <button class="btn btn-outline-primary btn-sm rounded-3 fw-semibold px-3 shadow-sm d-flex align-items-center gap-1.5" @click="openCategoriesView" type="button" style="height: 38px;">
              <i class="bi bi-tags"></i> Manage Categories
            </button>
            <button class="btn btn-primary btn-sm rounded-3 fw-semibold px-3 shadow-sm d-flex align-items-center gap-1.5" @click="openAddExpense" type="button" style="height: 38px;">
              <i class="bi bi-plus-lg"></i> Record Expense
            </button>
          </div>
        </div>

        <!-- 4 FLUID KPI CARDS (AUTO-FIT GRID) -->
        <div class="expense-kpi-grid mb-4">
          <!-- Today's Expenses -->
          <div class="expense-kpi-box">
            <div class="expense-kpi-icon bg-primary-subtle text-primary">
              <i class="bi bi-calendar-check"></i>
            </div>
            <div class="expense-kpi-data">
              <span class="expense-kpi-label">Today's Expenses</span>
              <div class="expense-kpi-num">{{ formatCurrency(totalExpensesToday) }}</div>
              <span class="expense-kpi-note text-success fw-medium">&bull; Active operations</span>
            </div>
          </div>

          <!-- This Month -->
          <div class="expense-kpi-box">
            <div class="expense-kpi-icon bg-info-subtle text-info">
              <i class="bi bi-calendar3"></i>
            </div>
            <div class="expense-kpi-data">
              <span class="expense-kpi-label">This Month</span>
              <div class="expense-kpi-num">{{ formatCurrency(totalExpensesThisMonth) }}</div>
              <span class="expense-kpi-note text-muted">{{ budgetUtilization }}% of monthly budget</span>
            </div>
          </div>

          <!-- Avg Daily Spend -->
          <div class="expense-kpi-box">
            <div class="expense-kpi-icon bg-primary-subtle text-primary">
              <i class="bi bi-speedometer2"></i>
            </div>
            <div class="expense-kpi-data">
              <span class="expense-kpi-label">Avg Daily Spend</span>
              <div class="expense-kpi-num">{{ formatCurrency(avgDailyExpense) }}</div>
              <span class="expense-kpi-note text-muted">Current Month Average</span>
            </div>
          </div>

          <!-- Monthly Limit / Budget -->
          <div class="expense-kpi-box">
            <div class="expense-kpi-icon bg-success-subtle text-success">
              <i class="bi bi-shield-check"></i>
            </div>
            <div class="expense-kpi-data">
              <span class="expense-kpi-label">Monthly Limit</span>
              <div class="expense-kpi-num">{{ formatCurrency(monthlyLimit) }}</div>
              <span class="expense-kpi-note text-success fw-medium">
                {{ formatCurrency(Math.max(0, monthlyLimit - totalExpensesThisMonth)) }} remaining
              </span>
            </div>
          </div>
        </div>

        <!-- ====================================================
             REPORTS & ANALYTICS SECTION
             (Integrated directly on the page above the list!)
        ==================================================== -->
        <div class="row g-4 mb-4">
          <!-- 1. CATEGORY COST DISTRIBUTION -->
          <div class="col-12 col-xl-7">
            <div class="expense-panel-card h-100 p-4">
              <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-pie-chart text-primary fs-5"></i>
                  <h4 class="fw-bold m-0 text-dark fs-6">Category Cost Distribution</h4>
                </div>
                <button class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold text-primary" @click="openCategoriesView">
                  Manage Categories &rarr;
                </button>
              </div>

              <div class="category-breakdown-list">
                <div v-for="(cat, index) in expensesByCategory" :key="cat.name" class="category-breakdown-item mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="d-flex align-items-center gap-2">
                      <span class="category-color-dot" :style="{ background: getCategoryColor(index) }"></span>
                      <strong class="text-dark small">{{ cat.name }}</strong>
                    </div>
                    <div class="text-end">
                      <span class="fw-bold text-dark small">{{ formatCurrency(cat.amount) }}</span>
                      <span class="text-muted small ms-1.5" style="font-size: 11px;">({{ cat.percentage }}%)</span>
                    </div>
                  </div>
                  <div class="progress" style="height: 7px; border-radius: 4px; background: #f1f5f9;">
                    <div 
                      class="progress-bar rounded-pill" 
                      :style="{ width: cat.percentage + '%', background: getCategoryColor(index) }"
                    ></div>
                  </div>
                </div>

                <div v-if="expensesByCategory.length === 0" class="text-center py-4 text-muted small">
                  No category expenses recorded this month.
                </div>
              </div>
            </div>
          </div>

          <!-- 2. BUDGET ALLOCATION & INSIGHTS -->
          <div class="col-12 col-xl-5">
            <div class="expense-panel-card h-100 p-4">
              <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-graph-up text-success fs-5"></i>
                  <h4 class="fw-bold m-0 text-dark fs-6">Budget &amp; Spending Analysis</h4>
                </div>
                <span class="badge bg-light text-dark border px-2 py-0.5 rounded-pill small">Monthly</span>
              </div>

              <!-- BUDGET METER -->
              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="text-muted small fw-semibold">Monthly Limit Usage</span>
                  <strong class="text-dark small">{{ budgetUtilization }}%</strong>
                </div>
                <div class="progress mb-2" style="height: 10px; border-radius: 6px; background: #f1f5f9;">
                  <div 
                    class="progress-bar" 
                    :class="Number(budgetUtilization) > 85 ? 'bg-danger' : 'bg-primary'"
                    :style="{ width: budgetUtilization + '%' }"
                  ></div>
                </div>
                <div class="d-flex justify-content-between text-muted" style="font-size: 11.5px;">
                  <span>Spent: <strong>{{ formatCurrency(totalExpensesThisMonth) }}</strong></span>
                  <span>Limit: <strong>{{ formatCurrency(monthlyLimit) }}</strong></span>
                </div>
              </div>

              <!-- LARGEST SPENDING CATEGORY BOX -->
              <div class="p-3 rounded-3 bg-light border mt-3">
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <span class="text-muted small text-uppercase fw-semibold d-block" style="font-size: 11px;">Largest Expense Category</span>
                    <strong class="text-dark fs-6">{{ largestCategoryThisMonth.name || 'N/A' }}</strong>
                  </div>
                  <div class="text-end">
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2.5 py-1 fw-bold fs-6">
                      {{ formatCurrency(largestCategoryThisMonth.amount) }}
                    </span>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center mt-3 pt-2 text-muted" style="font-size: 11.5px;">
                <span>Remaining Budget:</span>
                <span class="fw-bold text-success">{{ formatCurrency(Math.max(0, monthlyLimit - totalExpensesThisMonth)) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- ====================================================
             MASTER EXPENSES LEDGER & LIST TABLE
        ==================================================== -->
        <div class="expense-panel-card">
          <!-- TABLE TOOLBAR / FILTERS -->
          <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <h5 class="fw-bold m-0 text-dark me-1">Expense Ledger</h5>
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill me-2 px-2.5 py-1 small fw-bold">
                PKR {{ Number(filteredTotalAmount).toLocaleString() }} ({{ filteredExpenses.length }})
              </span>

              <!-- DURATION FILTER SELECT -->
              <select class="form-select form-select-sm rounded-3" v-model="selectedDuration" style="min-width: 150px; height: 36px;">
                <option value="all">All Time</option>
                <option value="today">Today</option>
                <option value="yesterday">Yesterday</option>
                <option value="this_week">This Week (Last 7 Days)</option>
                <option value="this_month">This Month</option>
                <option value="last_month">Last Month</option>
                <option value="this_quarter">This Quarter (3 Months)</option>
                <option value="this_year">This Year (2026)</option>
                <option value="custom">Custom Date Range</option>
              </select>

              <!-- CUSTOM DATE RANGE INPUTS -->
              <div v-if="selectedDuration === 'custom'" class="d-inline-flex align-items-center gap-1.5 bg-white border p-1 rounded-3">
                <input type="date" class="form-control form-control-sm border-0 bg-light" v-model="customStartDate" style="height: 28px; font-size: 12px;" />
                <span class="text-muted small px-1">to</span>
                <input type="date" class="form-control form-control-sm border-0 bg-light" v-model="customEndDate" style="height: 28px; font-size: 12px;" />
              </div>

              <!-- CATEGORY FILTER SELECT -->
              <select class="form-select form-select-sm rounded-3" v-model="selectedCategory" style="min-width: 160px; height: 36px;">
                <option value="">All Categories</option>
                <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
              </select>

              <!-- PAYMENT METHOD FILTER -->
              <select class="form-select form-select-sm rounded-3" v-model="selectedMethod" style="min-width: 140px; height: 36px;">
                <option value="">All Methods</option>
                <option value="Cash">Cash</option>
                <option value="Bank">Bank Transfer</option>
                <option value="Card">Card</option>
                <option value="Online">Online</option>
              </select>

              <!-- RESET BUTTON -->
              <button 
                v-if="searchQuery || selectedCategory || selectedMethod || selectedDuration !== 'all' || customStartDate || customEndDate" 
                class="btn btn-sm btn-light border rounded-3 px-2.5 py-1 text-muted" 
                @click="resetFilters" 
                type="button"
                style="height: 36px;"
              >
                <i class="bi bi-x-circle me-1"></i> Clear
              </button>
            </div>

            <!-- SEARCH BAR -->
            <div class="position-relative" style="min-width: 280px;">
              <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
              <input 
                type="text" 
                class="form-control form-control-sm rounded-pill ps-5 bg-light border-0" 
                placeholder="Search description or reference..."
                v-model="searchQuery"
                style="height: 36px;"
              />
            </div>
          </div>

          <!-- TABLE -->
          <div class="table-responsive">
            <table class="table table-hover align-middle m-0 expense-table">
              <thead>
                <tr>
                  <th style="padding-left: 20px;">Date</th>
                  <th>Reference #</th>
                  <th>Description</th>
                  <th>Category</th>
                  <th v-if="navigationStore.isMultiBranch">Branch</th>
                  <th>Payment Method</th>
                  <th class="text-end">Amount</th>
                  <th class="text-end" style="padding-right: 20px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="filteredExpenses.length === 0">
                  <td :colspan="navigationStore.isMultiBranch ? 8 : 7" class="text-center py-5 text-muted">
                    <i class="bi bi-wallet2 fs-1 text-muted opacity-50 d-block mb-2"></i>
                    <h6>No Expenses Found</h6>
                    <p class="small text-muted mb-3">No expense entries match your current search or filter criteria.</p>
                    <button class="btn btn-sm btn-primary rounded-3 px-3" @click="openAddExpense" type="button">
                      <i class="bi bi-plus-lg me-1"></i> Record New Expense
                    </button>
                  </td>
                </tr>

                <tr v-for="expense in paginatedExpenses" :key="expense.id" class="expense-row">
                  <td style="padding-left: 20px;" class="text-muted small">
                    {{ expense.date || expense.created_at?.slice(0, 10) }}
                  </td>
                  <td class="font-monospace small text-primary fw-semibold">
                    #{{ expense.reference || ('EXP-' + expense.id) }}
                  </td>
                  <td>
                    <div class="fw-semibold text-dark">{{ expense.description }}</div>
                    <small v-if="expense.notes" class="text-muted text-truncate d-block" style="max-width: 220px;">
                      {{ expense.notes }}
                    </small>
                  </td>
                  <td>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 small">
                      {{ expense.category }}
                    </span>
                  </td>
                  <td v-if="navigationStore.isMultiBranch" class="text-muted small">
                    {{ expense.branch || 'Main Store' }}
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border text-uppercase" style="font-size: 11px;">
                      {{ expense.method || 'Cash' }}
                    </span>
                  </td>
                  <td class="text-end fw-bold text-dark fs-6">
                    {{ formatCurrency(expense.amount) }}
                  </td>
                  <td class="text-end" style="padding-right: 20px;">
                    <div class="d-inline-flex align-items-center gap-1.5">
                      <button 
                        class="btn btn-sm btn-light border rounded-3 px-2 py-1" 
                        @click="openEditExpense(expense)" 
                        type="button" 
                        title="Edit Expense"
                      >
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button 
                        class="btn btn-sm btn-light border text-danger rounded-3 px-2 py-1" 
                        @click="deleteExpense(expense.id)" 
                        type="button" 
                        title="Delete Expense"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- PAGINATION -->
          <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2 bg-light">
            <span class="text-muted small">
              Showing {{ filteredExpenses.length ? (currentPage - 1) * itemsPerPage + 1 : 0 }} to 
              {{ Math.min(currentPage * itemsPerPage, filteredExpenses.length) }} of {{ filteredExpenses.length }} entries
            </span>
            <div class="d-flex align-items-center gap-1.5" v-if="totalPages > 1">
              <button class="btn btn-sm btn-light border rounded-3 px-3" :disabled="currentPage === 1" @click="prevPage" type="button">
                Previous
              </button>
              <span class="small fw-semibold px-2">Page {{ currentPage }} of {{ totalPages }}</span>
              <button class="btn btn-sm btn-light border rounded-3 px-3" :disabled="currentPage === totalPages" @click="nextPage" type="button">
                Next
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- ====================================================
         IN-PAGE POPUP MODAL (Add / Edit Expense)
    ===================================================== -->
    <div v-if="showExpenseModal" class="expense-modal-overlay" @click.self="closeExpenseModal">
      <div class="expense-modal-card">
        <div class="expense-modal-header">
          <div class="expense-modal-header-left">
            <div class="expense-modal-icon">
              <i class="bi bi-wallet2"></i>
            </div>
            <div class="expense-modal-header-text">
              <h3 class="expense-modal-title">{{ isEditing ? 'Edit Expense Record' : 'Record New Expense' }}</h3>
              <p class="expense-modal-subtitle">
                {{ isEditing ? 'Update the details and allocation of this operational expense.' : 'Log a new business operational cost into your store ledger.' }}
              </p>
            </div>
          </div>
          <button type="button" class="expense-modal-close" @click="closeExpenseModal" aria-label="Close">&#x2715;</button>
        </div>

        <div class="expense-modal-body">
          <div class="expense-modal-layout">
            <!-- FORM COLUMN -->
            <form @submit.prevent="submitExpenseForm" id="expenseModalForm">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label fw-semibold small text-secondary">Expense Description <span class="text-danger">*</span></label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="expenseForm.description" 
                    placeholder="e.g., Shop Utility Bill (Electricity)" 
                    required 
                  />
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold small text-secondary">Amount (PKR) <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text bg-light text-muted">PKR</span>
                    <input 
                      type="number" 
                      class="form-control" 
                      v-model="expenseForm.amount" 
                      placeholder="0.00" 
                      min="1" 
                      step="any" 
                      required 
                    />
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold small text-secondary">Category <span class="text-danger">*</span></label>
                  <select class="form-select" v-model="expenseForm.category" required>
                    <option value="" disabled>Select Category...</option>
                    <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold small text-secondary">Expense Date <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" v-model="expenseForm.date" required />
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold small text-secondary">Payment Method</label>
                  <select class="form-select" v-model="expenseForm.method">
                    <option value="Cash">Cash</option>
                    <option value="Bank">Bank Transfer</option>
                    <option value="Card">Card</option>
                    <option value="Online">Online</option>
                  </select>
                </div>

                <div class="col-md-6" v-if="navigationStore.isMultiBranch">
                  <label class="form-label fw-semibold small text-secondary">Branch Allocation</label>
                  <select class="form-select" v-model="expenseForm.branch">
                    <option v-for="b in navigationStore.activeBranches" :key="b.id" :value="b.name">{{ b.name }}</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold small text-secondary">Reference / Receipt #</label>
                  <input type="text" class="form-control" v-model="expenseForm.reference" placeholder="e.g. REC-8821" />
                </div>

                <div class="col-12">
                  <label class="form-label fw-semibold small text-secondary">Additional Notes</label>
                  <textarea class="form-control" v-model="expenseForm.notes" rows="2" placeholder="Optional context or memo..."></textarea>
                </div>
              </div>
            </form>

            <!-- LIVE PREVIEW SIDEBAR -->
            <aside class="expense-modal-preview">
              <div class="preview-dark-card p-3 rounded-3 bg-dark text-white">
                <div class="preview-header mb-2 text-white-50 small">
                  <i class="bi bi-receipt me-1"></i> Live Expense Preview
                </div>
                <div class="preview-amount mb-2">
                  <span class="text-white-50 small">Total Cost:</span>
                  <div class="fs-4 fw-bold text-white">{{ formatCurrency(expenseForm.amount || 0) }}</div>
                </div>
                <div class="preview-details small text-white-50">
                  <div class="d-flex justify-content-between py-1 border-top border-secondary">
                    <span>Category:</span>
                    <span class="text-white">{{ expenseForm.category || 'Not selected' }}</span>
                  </div>
                  <div class="d-flex justify-content-between py-1 border-top border-secondary">
                    <span>Method:</span>
                    <span class="text-white">{{ expenseForm.method || 'Cash' }}</span>
                  </div>
                  <div class="d-flex justify-content-between py-1 border-top border-secondary">
                    <span>Date:</span>
                    <span class="text-white">{{ expenseForm.date || 'Today' }}</span>
                  </div>
                </div>
              </div>

              <div class="modal-action-buttons mt-3">
                <button 
                  class="btn btn-primary w-100 fw-bold py-2.5 rounded-3 mb-2 shadow-sm" 
                  type="submit" 
                  form="expenseModalForm"
                  :disabled="isSubmitting"
                >
                  <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-1"></span>
                  {{ isEditing ? 'Update Expense' : 'Save Expense' }}
                </button>
                <button class="btn btn-light border w-100 rounded-3 py-2" type="button" @click="closeExpenseModal">
                  Cancel
                </button>
              </div>
            </aside>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useExpensesStore } from '../stores/expenses';
import { useNavigationStore } from '../stores/navigation';
import { storeToRefs } from 'pinia';

const props = defineProps({
  isModal: { type: Boolean, default: false },
  modalPage: { type: String, default: '' }
});
const emit = defineEmits(['success', 'close']);

const router = useRouter();
const route = useRoute();
const navigationStore = useNavigationStore();
const expensesStore = useExpensesStore();

const { 
  expenses, 
  monthlyLimit, 
  categories, 
  totalExpensesToday, 
  totalExpensesThisMonth, 
  largestCategoryThisMonth, 
  budgetUtilization, 
  expensesByCategory, 
  avgDailyExpense 
} = storeToRefs(expensesStore);

// ============================================================
// VIEW SWITCHER STATE (NO TABS)
// ============================================================
const currentView = ref('expenses'); // 'expenses' | 'categories'

watch(() => route.query.tab, (newTab) => {
  if (newTab === 'categories') {
    currentView.value = 'categories';
  } else {
    currentView.value = 'expenses';
  }
}, { immediate: true });

const openCategoriesView = () => {
  currentView.value = 'categories';
  router.push({ query: { ...route.query, tab: 'categories' } });
};

const closeCategoriesView = () => {
  currentView.value = 'expenses';
  const q = { ...route.query };
  delete q.tab;
  router.push({ query: q });
};

const formatCurrency = (amount) => {
  return 'PKR ' + Number(amount || 0).toLocaleString();
};

// ============================================================
// SEARCH & FILTER STATE
// ============================================================
const searchQuery = ref('');
const selectedCategory = ref('');
const selectedMethod = ref('');
const selectedDuration = ref('all');
const customStartDate = ref('');
const customEndDate = ref('');

// Helper to determine if an expense falls within the selected duration
const isExpenseInDuration = (dateStr, duration, from, to) => {
  if (!dateStr || duration === 'all') return true;
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return true;

  const now = new Date();
  const year = now.getFullYear();
  const month = now.getMonth();
  const day = now.getDate();

  const startOfDay = (date) => new Date(date.getFullYear(), date.getMonth(), date.getDate(), 0, 0, 0, 0);
  const endOfDay = (date) => new Date(date.getFullYear(), date.getMonth(), date.getDate(), 23, 59, 59, 999);

  let start, end;
  switch (duration) {
    case 'today':
      start = startOfDay(now);
      end = endOfDay(now);
      break;
    case 'yesterday': {
      const y = new Date(year, month, day - 1);
      start = startOfDay(y);
      end = endOfDay(y);
      break;
    }
    case 'this_week':
      start = new Date(year, month, day - 6, 0, 0, 0, 0);
      end = endOfDay(now);
      break;
    case 'this_month':
      start = new Date(year, month, 1, 0, 0, 0, 0);
      end = new Date(year, month + 1, 0, 23, 59, 59, 999);
      break;
    case 'last_month':
      start = new Date(year, month - 1, 1, 0, 0, 0, 0);
      end = new Date(year, month, 0, 23, 59, 59, 999);
      break;
    case 'this_quarter': {
      const qStart = Math.floor(month / 3) * 3;
      start = new Date(year, qStart, 1, 0, 0, 0, 0);
      end = new Date(year, qStart + 3, 0, 23, 59, 59, 999);
      break;
    }
    case 'this_year':
      start = new Date(year, 0, 1, 0, 0, 0, 0);
      end = new Date(year, 11, 31, 23, 59, 59, 999);
      break;
    case 'custom':
      if (from) start = new Date(`${from}T00:00:00`);
      if (to) end = new Date(`${to}T23:59:59`);
      if (!from && !to) return true;
      if (start && !end) return d >= start;
      if (!start && end) return d <= end;
      break;
    default:
      return true;
  }

  return d >= start && d <= end;
};

const filteredExpenses = computed(() => {
  let list = expenses.value || [];
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase().trim();
    list = list.filter(exp => 
      (exp.description && exp.description.toLowerCase().includes(q)) || 
      (exp.reference && exp.reference.toLowerCase().includes(q))
    );
  }
  if (selectedCategory.value) {
    list = list.filter(exp => exp.category === selectedCategory.value);
  }
  if (selectedMethod.value) {
    list = list.filter(exp => (exp.method || '').toLowerCase() === selectedMethod.value.toLowerCase());
  }
  if (selectedDuration.value && selectedDuration.value !== 'all') {
    list = list.filter(exp => isExpenseInDuration(exp.date || exp.created_at, selectedDuration.value, customStartDate.value, customEndDate.value));
  }
  return list;
});

const filteredTotalAmount = computed(() => {
  return filteredExpenses.value.reduce((sum, e) => sum + Number(e.amount || 0), 0);
});

const resetFilters = () => {
  searchQuery.value = '';
  selectedCategory.value = '';
  selectedMethod.value = '';
  selectedDuration.value = 'all';
  customStartDate.value = '';
  customEndDate.value = '';
};

// ============================================================
// PAGINATION
// ============================================================
const currentPage = ref(1);
const itemsPerPage = 8;
const totalPages = computed(() => Math.ceil(filteredExpenses.value.length / itemsPerPage) || 1);
const paginatedExpenses = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return filteredExpenses.value.slice(start, start + itemsPerPage);
});

const prevPage = () => { if (currentPage.value > 1) currentPage.value--; };
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++; };
watch([searchQuery, selectedCategory, selectedMethod, selectedDuration, customStartDate, customEndDate], () => { currentPage.value = 1; });

// ============================================================
// MODAL & EXPENSE RECORDING
// ============================================================
const showExpenseModal = ref(false);
const isEditing = ref(false);
const isSubmitting = ref(false);
const successAlertMessage = ref('');
const errorAlertMessage = ref('');
let successAlertTimer = null;

const triggerSuccessAlert = (msg) => {
  successAlertMessage.value = msg;
  if (successAlertTimer) clearTimeout(successAlertTimer);
  successAlertTimer = setTimeout(() => { successAlertMessage.value = ''; }, 4500);
};

const expenseForm = ref({
  id: null,
  description: '',
  amount: null,
  category: '',
  date: new Date().toISOString().slice(0, 10),
  method: 'Cash',
  branch: '',
  reference: '',
  notes: ''
});

const openAddExpense = () => {
  isEditing.value = false;
  expenseForm.value = {
    id: null,
    description: '',
    amount: null,
    category: categories.value?.[0] || '',
    date: new Date().toISOString().slice(0, 10),
    method: 'Cash',
    branch: navigationStore.currentBranchName || 'Main Store',
    reference: 'EXP-' + Date.now().toString().slice(-5),
    notes: ''
  };
  showExpenseModal.value = true;
};

const openEditExpense = (expense) => {
  isEditing.value = true;
  expenseForm.value = { ...expense };
  showExpenseModal.value = true;
};

const closeExpenseModal = () => {
  showExpenseModal.value = false;
  if (props.isModal) emit('close');
};

const submitExpenseForm = async () => {
  if (!expenseForm.value.description || !expenseForm.value.amount || !expenseForm.value.category) {
    showError("Please complete all required fields.");
    return;
  }

  isSubmitting.value = true;
  try {
    if (isEditing.value) {
      const res = await expensesStore.updateExpense(expenseForm.value.id, expenseForm.value);
      if (res && res.success !== false) {
        await expensesStore.fetchExpenses();
        showExpenseModal.value = false;
        triggerSuccessAlert('Expense updated successfully!');
      } else {
        showError(res?.message || 'Failed to update expense');
      }
    } else {
      const res = await expensesStore.addExpense(expenseForm.value);
      if (res && res.success !== false) {
        await expensesStore.fetchExpenses();
        showExpenseModal.value = false;
        triggerSuccessAlert('Expense recorded successfully!');
      } else {
        showError(res?.message || 'Failed to record expense');
      }
    }
  } catch (err) {
    showError('An unexpected error occurred.');
  } finally {
    isSubmitting.value = false;
  }
};

const deleteExpense = async (id) => {
  if (!confirm('Are you sure you want to delete this expense record?')) return;
  const res = await expensesStore.deleteExpense(id);
  if (res && res.success !== false) {
    await expensesStore.fetchExpenses();
    triggerSuccessAlert('Expense deleted successfully.');
  } else {
    showError(res?.message || 'Failed to delete expense.');
  }
};

// ============================================================
// CATEGORY ACTIONS
// ============================================================
const newCategoryName = ref('');
const isAddingCategory = ref(false);
const isDeletingCategory = ref(false);

const handleAddCategory = async () => {
  if (!newCategoryName.value.trim()) return;
  isAddingCategory.value = true;
  const res = await expensesStore.addExpenseCategory(newCategoryName.value.trim());
  if (res && res.success !== false) {
    newCategoryName.value = '';
    triggerSuccessAlert("Category added successfully!");
  } else {
    showError(res?.message || "Failed to add category.");
  }
  isAddingCategory.value = false;
};

const handleDeleteCategory = async (name) => {
  if (!confirm(`Are you sure you want to delete the category "${name}"?`)) return;
  isDeletingCategory.value = true;
  const res = await expensesStore.deleteExpenseCategory(name);
  if (res && res.success !== false) {
    triggerSuccessAlert("Category deleted successfully.");
  } else {
    showError(res?.message || "Failed to delete category.");
  }
  isDeletingCategory.value = false;
};

const showError = (msg) => { 
  errorAlertMessage.value = msg; 
  setTimeout(() => errorAlertMessage.value = '', 4500); 
};

// ============================================================
// EXPORT & COLOR PALETTE
// ============================================================
const exportExpenses = () => {
  const data = (expenses.value || []).map(e => ({
    Date: e.date,
    Ref: e.reference || ('EXP-' + e.id),
    Category: e.category,
    Description: e.description,
    Amount: e.amount,
    Method: e.method
  }));
  const csv = "data:text/csv;charset=utf-8," + 
    ["Date,Ref,Category,Description,Amount,Method"]
    .concat(data.map(d => `"${d.Date}","${d.Ref}","${d.Category}","${d.Description}",${d.Amount},"${d.Method}"`))
    .join("\n");
  const link = document.createElement("a");
  link.setAttribute("href", encodeURI(csv));
  link.setAttribute("download", `HBOS_Expenses_${new Date().toISOString().slice(0, 10)}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const palette = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#64748b'];
const getCategoryColor = (idx) => palette[idx % palette.length];

onMounted(async () => {
  if (props.isModal) {
    showExpenseModal.value = true;
  }
  await Promise.all([
    expensesStore.fetchExpenseCategories(),
    expensesStore.fetchExpenses(),
    expensesStore.fetchRecurringExpenses()
  ]);
});
</script>

<style scoped>
.expenses-page-wrapper {
  width: 100%;
  min-height: 100%;
  background: #f8fafc;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  color: #0f172a;
}

.expense-main-container {
  padding: 24px 32px;
}

/* TOPBAR */
.expense-topbar {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 20px;
}

.expense-badge-pill {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  padding: 2px 8px;
  border-radius: 6px;
  background: #fee2e2;
  color: #b91c1c;
}

.expense-main-title {
  font-size: 26px;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.02em;
  margin: 0;
  line-height: 1.2;
}

.expense-main-sub {
  font-size: 13.5px;
  color: #64748b;
  margin: 4px 0 0 0;
}

/* ALERTS */
.expense-top-alert {
  background: #f0fdf4;
  border-bottom: 1px solid #bbf7d0;
  color: #166534;
  padding: 12px 32px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.alert-slide-enter-active,
.alert-slide-leave-active {
  transition: all 0.25s ease;
}

.alert-slide-enter-from,
.alert-slide-leave-to {
  transform: translateY(-100%);
  opacity: 0;
}

/* FLUID AUTO-FIT KPI GRID */
.expense-kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  gap: 16px;
}

.expense-kpi-box {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 16px 18px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.expense-kpi-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}

.expense-kpi-data {
  flex: 1;
  min-width: 0;
}

.expense-kpi-label {
  font-size: 11.5px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.expense-kpi-num {
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
  margin: 2px 0;
}

.expense-kpi-note {
  font-size: 11.5px;
}

/* PANEL CARD */
.expense-panel-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  overflow: hidden;
}

.category-color-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
  flex-shrink: 0;
}

/* TABLE */
.expense-table thead th {
  background: #f8fafc;
  color: #475569;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 12px 14px;
  border-bottom: 1px solid #e2e8f0;
}

.expense-table tbody td {
  padding: 14px;
  border-bottom: 1px solid #f1f5f9;
}

.expense-row:hover {
  background-color: #f8fafc;
}

/* POPUP MODAL */
.expense-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.expense-modal-card {
  background: #ffffff;
  border-radius: 16px;
  width: 100%;
  max-width: 860px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.3);
}

.expense-modal-header {
  padding: 18px 24px;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.expense-modal-header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.expense-modal-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: #fee2e2;
  color: #b91c1c;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.expense-modal-title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.expense-modal-subtitle {
  font-size: 12px;
  color: #64748b;
  margin: 2px 0 0 0;
}

.expense-modal-close {
  background: transparent;
  border: none;
  font-size: 20px;
  color: #94a3b8;
  cursor: pointer;
}

.expense-modal-close:hover {
  color: #0f172a;
}

.expense-modal-body {
  padding: 24px;
}

.expense-modal-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 280px;
  gap: 24px;
  align-items: start;
}

@media (max-width: 768px) {
  .expense-modal-layout {
    grid-template-columns: 1fr;
  }
}
</style>
