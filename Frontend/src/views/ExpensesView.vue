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
      <!-- PAGE HEADER -->
      <div class="expense-topbar">
        <div>
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="expense-badge-pill">Cost Control</span>
            <span class="text-muted small">• {{ currentTabTitle }}</span>
          </div>
          <h1 class="expense-main-title">{{ currentTabTitle }}</h1>
          <p class="expense-main-sub">{{ currentTabDescription }}</p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
          <button 
            v-if="activeTab === 'add'"
            class="btn btn-light btn-sm border rounded-3 fw-semibold px-3 shadow-sm" 
            @click="setTab('overview')" 
            type="button" 
            style="height: 38px;"
          >
            <i class="bi bi-arrow-left me-1"></i> Back to Expense List
          </button>
          <button 
            v-if="activeTab === 'overview'"
            class="btn btn-primary btn-sm rounded-3 fw-semibold px-3 shadow-sm" 
            @click="openAddExpense" 
            type="button" 
            style="height: 38px;"
          >
            <i class="bi bi-plus-lg me-1"></i> Add Expense
          </button>
        </div>
      </div>

      <!-- ====================================================
           TAB: FULL-PAGE DEDICATED EXPENSE FORM (activeTab === 'add')
      ===================================================== -->
      <div v-if="activeTab === 'add'" class="tab-content-panel mb-4">
        <div class="expense-panel-card p-4">
          <div class="row g-4">
            <!-- FORM COLUMN -->
            <div class="col-12 col-lg-8 border-end-lg">
              <div class="mb-4">
                <h3 class="fw-bold text-dark fs-5 mb-1">{{ isEditing ? 'Edit Expense Record' : 'Expense Details' }}</h3>
                <p class="text-muted small mb-0">
                  {{ isEditing ? 'Update the details and allocation of this operational expense.' : 'Record a new expense for your business operations and store ledger.' }}
                </p>
              </div>

              <form @submit.prevent="submitExpenseForm">
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label">Expense Description <span class="text-danger">*</span></label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="expenseForm.description" 
                      placeholder="e.g., Monthly Store Electricity Bill" 
                      required 
                    />
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select class="form-select" v-model="expenseForm.category" required>
                      <option value="" disabled>Select Category</option>
                      <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                    </select>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Amount (PKR) <span class="text-danger">*</span></label>
                    <div class="amount-input-wrap">
                      <span>Rs.</span>
                      <input 
                        type="number" 
                        class="form-control" 
                        v-model.number="expenseForm.amount" 
                        min="1" 
                        step="1" 
                        placeholder="0.00" 
                        required 
                      />
                    </div>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" v-model="expenseForm.date" required />
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                    <select class="form-select" v-model="expenseForm.method" required>
                      <option>Cash</option>
                      <option>Bank Transfer</option>
                      <option>Card</option>
                      <option>Cheque</option>
                    </select>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Reference No. (Optional)</label>
                    <input type="text" class="form-control" v-model="expenseForm.reference" placeholder="Receipt or Tx #12345" />
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Branch Allocation</label>
                    <select class="form-select" v-model="expenseForm.branch">
                      <option>Main Branch</option>
                    </select>
                  </div>

                  <div class="col-12">
                    <label class="form-label">Additional Notes (Optional)</label>
                    <textarea class="form-control" rows="3" v-model="expenseForm.notes" placeholder="Additional details or remarks..."></textarea>
                  </div>
                </div>

                <div class="d-flex align-items-center gap-2 mt-4 pt-3 border-top">
                  <button 
                    type="button" 
                    class="btn btn-light rounded-3 px-4 py-2 fw-semibold" 
                    @click="setTab('overview')"
                  >
                    Cancel
                  </button>
                  <button 
                    type="submit" 
                    class="btn btn-primary rounded-3 px-4 py-2 fw-bold shadow-sm"
                    :disabled="isSubmitting"
                  >
                    <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="bi bi-check2-circle me-1"></i>
                    {{ isEditing ? 'Update Expense' : 'Save Expense' }}
                  </button>
                </div>
              </form>
            </div>

            <!-- PREVIEW SIDEBAR COLUMN -->
            <div class="col-12 col-lg-4">
              <div class="preview-dark-card mb-4">
                <div class="preview-header">
                  <i class="bi bi-receipt me-1.5"></i> Expense Preview
                </div>

                <div class="preview-line">
                  <span class="preview-label">Category</span>
                  <span class="preview-value">{{ expenseForm.category || '—' }}</span>
                </div>

                <div class="preview-line">
                  <span class="preview-label">Date</span>
                  <span class="preview-value">{{ expenseForm.date || '—' }}</span>
                </div>

                <div class="preview-line">
                  <span class="preview-label">Payment Method</span>
                  <span class="preview-value">{{ expenseForm.method || 'Cash' }}</span>
                </div>

                <div class="preview-line">
                  <span class="preview-label">Branch</span>
                  <span class="preview-value">{{ expenseForm.branch || 'Main Branch' }}</span>
                </div>

                <div class="preview-line total-line">
                  <span class="preview-label">Total Amount</span>
                  <span class="preview-value text-primary-light">PKR {{ (expenseForm.amount || 0).toLocaleString() }}</span>
                </div>
              </div>

              <div class="budget-card-info p-3.5 bg-light rounded-3 border">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <strong class="text-dark small">Monthly Budget Utilization</strong>
                  <span class="badge bg-primary rounded-pill small">{{ budgetUtilization }}%</span>
                </div>
                <div class="progress mb-2" style="height: 8px; background: #e2e8f0; border-radius: 4px;">
                  <div class="progress-bar bg-primary" :style="{ width: budgetUtilization + '%' }"></div>
                </div>
                <span class="text-muted small">
                  {{ formatCurrency(Math.max(0, monthlyLimit - totalExpensesThisMonth)) }} available of {{ formatCurrency(monthlyLimit) }} limit.
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ====================================================
           TAB 1: EXPENSE LIST
      ===================================================== -->
      <div v-if="activeTab === 'overview'" class="tab-content-panel">
        <!-- 4 KPI CARDS -->
        <div class="expense-kpi-grid mb-4">
          <!-- Today's Expenses -->
          <div class="expense-kpi-box">
            <div class="expense-kpi-icon bg-primary-subtle text-primary">
              <i class="bi bi-calendar-check"></i>
            </div>
            <div class="expense-kpi-data">
              <span class="expense-kpi-label">Today's Expenses</span>
              <div class="expense-kpi-num">{{ formatCurrency(totalExpensesToday) }}</div>
              <span class="expense-kpi-note text-success fw-medium">● Active operations</span>
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

          <!-- Largest Category -->
          <div class="expense-kpi-box">
            <div class="expense-kpi-icon bg-warning-subtle text-warning">
              <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div class="expense-kpi-data">
              <span class="expense-kpi-label">Largest Category</span>
              <div class="expense-kpi-num">{{ largestCategoryThisMonth.name || 'N/A' }}</div>
              <span class="expense-kpi-note text-muted">{{ formatCurrency(largestCategoryThisMonth.amount) }} spent</span>
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

        <!-- MAIN TABLE PANEL -->
        <div class="expense-panel-card">
          <div class="expense-panel-header">
            <div class="d-flex align-items-center gap-2">
              <h2 class="expense-panel-title m-0">Expense Ledger</h2>
              <span class="badge bg-light text-dark border px-2 py-1 rounded-pill small">
                {{ filteredExpenses.length }} Entries
              </span>
            </div>

            <!-- FILTERS TOOLBAR -->
            <div class="expense-tools-bar">
              <div class="expense-search-input">
                <i class="bi bi-search search-icon"></i>
                <input 
                  type="search" 
                  placeholder="Search description, reference..." 
                  v-model="searchQuery" 
                />
                <button v-if="searchQuery" class="clear-btn" @click="searchQuery = ''" type="button">✕</button>
              </div>

              <select class="expense-filter-select" v-model="selectedCategory">
                <option value="">All Categories</option>
                <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
              </select>

              <select class="expense-filter-select" v-model="selectedMethod">
                <option value="">All Methods</option>
                <option>Cash</option>
                <option>Bank Transfer</option>
                <option>Card</option>
                <option>Cheque</option>
              </select>

              <button 
                v-if="searchQuery || selectedCategory || selectedMethod" 
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

          <!-- TABLE -->
          <div class="expense-table-responsive">
            <table class="expense-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Description</th>
                  <th>Category</th>
                  <th>Amount</th>
                  <th>Payment Method</th>
                  <th style="text-align: right;">Action</th>
                </tr>
              </thead>

              <tbody>
                <tr v-if="paginatedExpenses.length === 0">
                  <td colspan="6" class="text-center py-5">
                    <div class="empty-state-wrap">
                      <i class="bi bi-wallet2 text-muted" style="font-size: 2.5rem;"></i>
                      <h5 class="fw-bold text-dark mt-2 mb-1">No expenses recorded</h5>
                      <p class="text-muted small mb-3">No expense entries match your current search or filter criteria.</p>
                      <button class="btn btn-sm btn-primary rounded-3" @click="openAddExpense" type="button">
                        <i class="bi bi-plus-lg me-1"></i> Add New Expense
                      </button>
                    </div>
                  </td>
                </tr>

                <tr v-for="expense in paginatedExpenses" :key="expense.id" class="expense-table-row">
                  <td>
                    <div class="fw-semibold text-dark">{{ expense.date }}</div>
                    <div class="text-muted small">{{ expense.branch || 'Main Branch' }}</div>
                  </td>

                  <td>
                    <div class="fw-bold text-dark">{{ expense.description }}</div>
                    <div v-if="expense.reference" class="text-muted small font-monospace">
                      Ref: {{ expense.reference }}
                    </div>
                  </td>

                  <td>
                    <span class="category-pill" :style="getCategoryBadgeStyle(expense.category)">
                      {{ expense.category }}
                    </span>
                  </td>

                  <td>
                    <span class="fw-bold text-dark fs-6">{{ formatCurrency(expense.amount) }}</span>
                  </td>

                  <td>
                    <span class="method-badge">
                      <i class="bi" :class="getMethodIcon(expense.method)"></i>
                      {{ expense.method }}
                    </span>
                  </td>

                  <td style="text-align: right;">
                    <div class="d-flex justify-content-end gap-1.5">
                      <button class="btn btn-sm btn-light border rounded-2 px-2.5 py-1 text-secondary" @click="openEditExpense(expense)" title="Edit Expense">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button class="btn btn-sm btn-light border text-danger rounded-2 px-2.5 py-1" @click="deleteExpense(expense.id)" title="Delete Expense">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- PAGINATION -->
          <div class="expense-panel-footer">
            <span class="text-muted small">
              Showing {{ filteredExpenses.length > 0 ? (currentPage - 1) * itemsPerPage + 1 : 0 }} to 
              {{ Math.min(currentPage * itemsPerPage, filteredExpenses.length) }} of {{ filteredExpenses.length }} entries
            </span>
            <div class="d-flex align-items-center gap-1">
              <button class="btn btn-sm btn-light border rounded-2 px-2.5" @click="prevPage" :disabled="currentPage === 1">‹</button>
              <span class="px-2 small fw-bold text-dark">Page {{ currentPage }} of {{ totalPages }}</span>
              <button class="btn btn-sm btn-light border rounded-2 px-2.5" @click="nextPage" :disabled="currentPage === totalPages">›</button>
            </div>
          </div>
        </div>
      </div>

      <!-- ====================================================
           TAB 2: EXPENSE CATEGORIES
      ===================================================== -->
      <div v-if="activeTab === 'categories'" class="tab-content-panel">
        <div class="row g-4 mb-4">
          <!-- CATEGORY BREAKDOWN LIST -->
          <div class="col-12 col-xl-7">
            <div class="expense-panel-card h-100">
              <div class="expense-panel-header">
                <h3 class="expense-panel-title m-0">Category Cost Breakdown</h3>
                <span class="badge bg-light text-dark border px-2 py-1 rounded-pill small">{{ expensesByCategory.length }} Active Categories</span>
              </div>

              <div class="p-4">
                <div class="category-breakdown-list">
                  <div v-for="(cat, index) in expensesByCategory" :key="cat.name" class="category-breakdown-item mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <div class="d-flex align-items-center gap-2">
                        <span class="category-color-dot" :style="{ background: getCategoryColor(index) }"></span>
                        <strong class="text-dark">{{ cat.name }}</strong>
                      </div>
                      <div class="text-end">
                        <span class="fw-bold text-dark">{{ formatCurrency(cat.amount) }}</span>
                        <span class="text-muted small ms-2">({{ cat.percentage }}%)</span>
                      </div>
                    </div>
                    <div class="progress" style="height: 8px; border-radius: 4px; background: #f1f5f9;">
                      <div 
                        class="progress-bar rounded-pill" 
                        :style="{ width: cat.percentage + '%', background: getCategoryColor(index) }"
                      ></div>
                    </div>
                  </div>

                  <div v-if="expensesByCategory.length === 0" class="text-center py-4 text-muted">
                    No category expense data recorded yet.
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- BUDGET & LIMIT SUMMARY -->
          <div class="col-12 col-xl-5">
            <div class="expense-panel-card h-100">
              <div class="expense-panel-header">
                <h3 class="expense-panel-title m-0">Budget Allocation & Limits</h3>
              </div>
              <div class="p-4">
                <div class="budget-progress-box mb-4">
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Monthly Expense Budget</span>
                    <strong class="text-dark">{{ formatCurrency(monthlyLimit) }}</strong>
                  </div>
                  <div class="progress mb-2" style="height: 12px; border-radius: 6px; background: #f1f5f9;">
                    <div 
                      class="progress-bar" 
                      :class="Number(budgetUtilization) > 85 ? 'bg-danger' : 'bg-primary'"
                      :style="{ width: budgetUtilization + '%' }"
                    ></div>
                  </div>
                  <div class="d-flex justify-content-between text-muted small">
                    <span>Spent: {{ formatCurrency(totalExpensesThisMonth) }}</span>
                    <span>Utilization: {{ budgetUtilization }}%</span>
                  </div>
                  <div class="category-badges-grid">
                    <h6 class="fw-bold text-dark mb-2.5">Manage Categories</h6>
                    <form @submit.prevent="handleAddCategory" class="d-flex gap-2 mb-3">
                      <input type="text" class="form-control form-control-sm" v-model="newCategoryName" placeholder="New Category Name" required>
                      <button type="submit" class="btn btn-sm btn-primary fw-semibold px-3" :disabled="isAddingCategory">Add</button>
                    </form>

                    <div class="manage-category-list border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                      <div v-for="cat in categories" :key="cat" class="d-flex justify-content-between align-items-center p-2 border-bottom">
                        <span class="text-dark fw-medium"><i class="bi bi-tag-fill text-muted me-2"></i>{{ cat }}</span>
                        <button class="btn btn-sm text-danger p-0" @click="handleDeleteCategory(cat)" title="Delete Category" :disabled="isDeletingCategory">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                      <div v-if="categories.length === 0" class="text-center text-muted py-3 small">
                        No categories found.
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ====================================================
           TAB 3: EXPENSE REPORTS & ANALYTICS
      ===================================================== -->
      <div v-if="activeTab === 'reports'" class="tab-content-panel">
        <!-- 3 ANALYTICS KPI CARDS -->
        <div class="row g-3 mb-4">
          <div class="col-12 col-md-4">
            <div class="expense-kpi-box">
              <div class="expense-kpi-icon bg-danger-subtle text-danger">
                <i class="bi bi-graph-up-arrow"></i>
              </div>
              <div class="expense-kpi-data">
                <span class="expense-kpi-label">Monthly Expense Volume</span>
                <div class="expense-kpi-num">{{ formatCurrency(totalExpensesThisMonth) }}</div>
                <span class="expense-kpi-note text-danger fw-semibold">Active Operational Period</span>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-4">
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
          </div>

          <div class="col-12 col-md-4">
            <div class="expense-kpi-box">
              <div class="expense-kpi-icon bg-warning-subtle text-warning">
                <i class="bi bi-trophy"></i>
              </div>
              <div class="expense-kpi-data">
                <span class="expense-kpi-label">Highest Expense Category</span>
                <div class="expense-kpi-num">{{ largestCategoryThisMonth.name || 'N/A' }}</div>
                <span class="expense-kpi-note text-muted">{{ formatCurrency(largestCategoryThisMonth.amount) }} Total</span>
              </div>
            </div>
          </div>
        </div>

        <!-- RECENT LARGE EXPENSES TABLE -->
        <div class="expense-panel-card">
          <div class="expense-panel-header">
            <div class="d-flex align-items-center gap-2">
              <h3 class="expense-panel-title m-0">Recent Operational Expenditures</h3>
              <span class="badge bg-light text-dark border px-2 py-1 rounded-pill small">Top Expenses</span>
            </div>
            <button class="btn btn-outline-secondary btn-sm rounded-3 px-3" @click="exportExpenses" type="button">
              <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Report
            </button>
          </div>

          <div class="expense-table-responsive">
            <table class="expense-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Description</th>
                  <th>Category</th>
                  <th>Amount</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="exp in expenses.slice(0, 5)" :key="exp.id" class="expense-table-row">
                  <td>{{ exp.date }}</td>
                  <td><strong>{{ exp.description }}</strong></td>
                  <td><span class="category-pill" :style="getCategoryBadgeStyle(exp.category)">{{ exp.category }}</span></td>
                  <td><span class="fw-bold text-dark">{{ formatCurrency(exp.amount) }}</span></td>
                  <td><span class="stock-pill badge-in"><span class="pill-dot"></span> Recorded</span></td>
                </tr>
                <tr v-if="expenses.length === 0">
                  <td colspan="5" class="text-center py-4 text-muted">No expense transactions available.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ====================================================
         IN-PAGE POPUP MODAL (For modal invocation or quick add)
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
          <button type="button" class="expense-modal-close" @click="closeExpenseModal" aria-label="Close">✕</button>
        </div>

        <div class="expense-modal-body">
          <div class="expense-modal-layout">
            <!-- FORM COLUMN -->
            <form @submit.prevent="submitExpenseForm" id="expenseModalForm">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label">Expense Description <span class="text-danger">*</span></label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="expenseForm.description" 
                    placeholder="e.g., Monthly Store Electricity Bill" 
                    required 
                  />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Category <span class="text-danger">*</span></label>
                  <select class="form-select" v-model="expenseForm.category" required>
                    <option value="" disabled>Select Category</option>
                    <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Amount (PKR) <span class="text-danger">*</span></label>
                  <div class="amount-input-wrap">
                    <span>Rs.</span>
                    <input 
                      type="number" 
                      class="form-control" 
                      v-model.number="expenseForm.amount" 
                      min="1" 
                      step="1" 
                      placeholder="0.00" 
                      required 
                    />
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Date <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" v-model="expenseForm.date" required />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                  <select class="form-select" v-model="expenseForm.method" required>
                    <option>Cash</option>
                    <option>Bank Transfer</option>
                    <option>Card</option>
                    <option>Cheque</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Reference No. (Optional)</label>
                  <input type="text" class="form-control" v-model="expenseForm.reference" placeholder="Receipt or Tx #12345" />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Branch Allocation</label>
                  <select class="form-select" v-model="expenseForm.branch">
                    <option>Main Branch</option>
                  </select>
                </div>

                <div class="col-12">
                  <label class="form-label">Additional Notes (Optional)</label>
                  <textarea class="form-control" rows="2" v-model="expenseForm.notes" placeholder="Notes or remarks..."></textarea>
                </div>
              </div>
            </form>

            <!-- LIVE PREVIEW SIDEBAR -->
            <aside class="expense-modal-preview">
              <div class="preview-dark-card">
                <div class="preview-header">
                  <i class="bi bi-receipt me-1"></i> Expense Preview
                </div>

                <div class="preview-line">
                  <span class="preview-label">Category</span>
                  <span class="preview-value">{{ expenseForm.category || '—' }}</span>
                </div>

                <div class="preview-line">
                  <span class="preview-label">Date</span>
                  <span class="preview-value">{{ expenseForm.date || '—' }}</span>
                </div>

                <div class="preview-line">
                  <span class="preview-label">Payment Method</span>
                  <span class="preview-value">{{ expenseForm.method || 'Cash' }}</span>
                </div>

                <div class="preview-line total-line">
                  <span class="preview-label">Total Amount</span>
                  <span class="preview-value text-primary-light">PKR {{ (expenseForm.amount || 0).toLocaleString() }}</span>
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
                <button class="btn btn-light w-100 rounded-3 py-2" type="button" @click="closeExpenseModal">
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
import { defineProps, defineEmits, ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useExpensesStore } from '../stores/expenses';
import { storeToRefs } from 'pinia';

const props = defineProps({
  isModal: { type: Boolean, default: false },
  modalPage: { type: String, default: '' }
});
const emit = defineEmits(['success', 'close']);

const router = useRouter();
const route = useRoute();

const activeTab = ref(props.isModal ? (props.modalPage || 'overview') : (route.query.tab || 'overview'));

const setTab = (tab) => {
  activeTab.value = tab;
  router.push({ query: { ...route.query, tab } });
};

watch(() => route.query.tab, (newTab) => {
  if (newTab) {
    activeTab.value = newTab;
  } else if (!props.isModal) {
    activeTab.value = 'overview';
  }
}, { immediate: true });

const currentTabTitle = computed(() => {
  switch (activeTab.value) {
    case 'add': return isEditing.value ? 'Edit Expense' : 'Record New Expense';
    case 'categories': return 'Expense Categories';
    case 'reports': return 'Expense Reports & Analytics';
    default: return 'Expense Management';
  }
});

const currentTabDescription = computed(() => {
  switch (activeTab.value) {
    case 'add': return 'Enter details below to log business operational expenditures into your store ledger.';
    case 'categories': return 'Categorize store expenditures and review category budget allocations.';
    case 'reports': return 'Detailed analytics, expense trends, and operational cost reports.';
    default: return 'Track, filter, and manage daily store operations costs and overhead expenses.';
  }
});

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

const formatCurrency = (amount) => {
  return 'PKR ' + Number(amount || 0).toLocaleString();
};

// SEARCH & FILTER STATE
const searchQuery = ref('');
const selectedCategory = ref('');
const selectedMethod = ref('');

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
    list = list.filter(exp => exp.method === selectedMethod.value);
  }
  return list;
});

const resetFilters = () => {
  searchQuery.value = '';
  selectedCategory.value = '';
  selectedMethod.value = '';
};

// PAGINATION
const currentPage = ref(1);
const itemsPerPage = 8;
const totalPages = computed(() => Math.ceil(filteredExpenses.value.length / itemsPerPage) || 1);
const paginatedExpenses = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return filteredExpenses.value.slice(start, start + itemsPerPage);
});

const prevPage = () => { if (currentPage.value > 1) currentPage.value--; };
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++; };
watch([searchQuery, selectedCategory, selectedMethod], () => { currentPage.value = 1; });

// MODAL & FORM STATE
const showExpenseModal = ref(false);
const isEditing = ref(false);
const isSubmitting = ref(false);
const successAlertMessage = ref('');
const errorAlertMessage = ref('');
let successAlertTimer = null;

// New Category State
const newCategoryName = ref('');
const isAddingCategory = ref(false);
const isDeletingCategory = ref(false);

const handleAddCategory = async () => {
  if (!newCategoryName.value.trim()) return;
  isAddingCategory.value = true;
  const res = await expensesStore.addExpenseCategory(newCategoryName.value.trim());
  if (res.success) {
    newCategoryName.value = '';
    showSuccess("Category added successfully");
  } else {
    showError(res.message);
  }
  isAddingCategory.value = false;
};

const handleDeleteCategory = async (name) => {
  if (!confirm(`Are you sure you want to delete the category "${name}"?`)) return;
  isDeletingCategory.value = true;
  const res = await expensesStore.deleteExpenseCategory(name);
  if (res.success) {
    showSuccess("Category deleted");
  } else {
    showError(res.message);
  }
  isDeletingCategory.value = false;
};

const showError = (msg) => { errorAlertMessage.value = msg; setTimeout(() => errorAlertMessage.value = '', 4500); };
const showSuccess = (msg) => { successAlertMessage.value = msg; setTimeout(() => successAlertMessage.value = '', 4500); };

const expenseForm = ref({
  id: null,
  description: '',
  category: 'Utilities',
  amount: null,
  date: new Date().toISOString().split('T')[0],
  method: 'Cash',
  reference: '',
  branch: 'Main Branch',
  notes: ''
});

const openAddExpense = () => {
  isEditing.value = false;
  expenseForm.value = {
    id: null,
    description: '',
    category: categories.value[0] || 'Utilities',
    amount: null,
    date: new Date().toISOString().split('T')[0],
    method: 'Cash',
    reference: '',
    branch: 'Main Branch',
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
    alert('Please fill all required fields.');
    return;
  }

  isSubmitting.value = true;
  try {
    if (isEditing.value) {
      const res = await expensesStore.updateExpense(expenseForm.value.id, { ...expenseForm.value });
      if (res && res.success !== false) {
        await expensesStore.fetchExpenses();
        showExpenseModal.value = false;
        triggerSuccessAlert('Expense updated successfully!');
        if (activeTab.value === 'add') {
          setTab('overview');
        }
      } else {
        alert(res?.message || 'Failed to update expense');
      }
    } else {
      const res = await expensesStore.addExpense({ ...expenseForm.value });
      if (res && res.success !== false) {
        await expensesStore.fetchExpenses();
        showExpenseModal.value = false;
        triggerSuccessAlert('Expense added successfully!');
        if (activeTab.value === 'add') {
          setTab('overview');
        }
      } else {
        alert(res?.message || 'Failed to record expense');
      }
    }
  } catch (e) {
    alert('An error occurred while saving the expense.');
  } finally {
    isSubmitting.value = false;
  }
};

const deleteExpense = async (id) => {
  if (!confirm('Are you sure you want to delete this expense?')) return;
  const res = await expensesStore.deleteExpense(id);
  if (res && res.success !== false) {
    await expensesStore.fetchExpenses();
    triggerSuccessAlert('Expense deleted successfully.');
  }
};

const triggerSuccessAlert = (msg) => {
  successAlertMessage.value = msg;
  if (successAlertTimer) clearTimeout(successAlertTimer);
  successAlertTimer = setTimeout(() => {
    successAlertMessage.value = '';
  }, 4500);
};

// EXPORT ACTION
const exportExpenses = () => {
  const rows = [
    ['Date', 'Description', 'Category', 'Amount (PKR)', 'Payment Method', 'Reference', 'Branch'],
    ...filteredExpenses.value.map(e => [
      e.date,
      `"${(e.description || '').replace(/"/g, '""')}"`,
      e.category,
      e.amount,
      e.method,
      e.reference || '',
      e.branch || ''
    ])
  ];
  const csvContent = 'data:text/csv;charset=utf-8,' + rows.map(e => e.join(',')).join('\n');
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', `expenses_${new Date().toISOString().slice(0,10)}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

// VISUAL HELPERS
const palette = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899'];
const getCategoryColor = (idx) => palette[idx % palette.length];

const getCategoryBadgeStyle = (categoryName) => {
  const colors = {
    'Utilities': { bg: '#e0f2fe', text: '#0369a1' },
    'Maintenance': { bg: '#fef3c7', text: '#b45309' },
    'Rent': { bg: '#fee2e2', text: '#b91c1c' },
    'Welfare': { bg: '#dcfce7', text: '#15803d' },
    'Transport': { bg: '#f3e8ff', text: '#7e22ce' },
    'Other': { bg: '#f1f5f9', text: '#475569' }
  };
  const c = colors[categoryName] || { bg: '#f1f5f9', text: '#475569' };
  return { background: c.bg, color: c.text };
};

const getMethodIcon = (method) => {
  switch (method) {
    case 'Bank Transfer': return 'bi-bank';
    case 'Card': return 'bi-credit-card';
    case 'Cheque': return 'bi-card-heading';
    default: return 'bi-cash-stack';
  }
};

onMounted(async () => {
  if (props.isModal) {
    showExpenseModal.value = true;
  }
  await expensesStore.fetchExpenseCategories();
  await expensesStore.fetchExpenses();
  await expensesStore.fetchRecurringExpenses();
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
  font-size: 1.65rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.02em;
  margin: 0 0 4px 0;
}

.expense-main-sub {
  font-size: 0.88rem;
  color: #64748b;
  margin: 0;
  max-width: 700px;
}

/* 4 KPI GRID */
.expense-kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.expense-kpi-box:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
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
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.expense-kpi-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #64748b;
  margin-bottom: 2px;
}

.expense-kpi-num {
  font-size: 1.35rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
}

.expense-kpi-note {
  font-size: 12px;
  margin-top: 2px;
}

/* MAIN PANEL CARD */
.expense-panel-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
  overflow: hidden;
}

.border-end-lg {
  border-right: 1px solid #e2e8f0;
}
@media (max-width: 991px) {
  .border-end-lg {
    border-right: none;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 24px;
  }
}

.expense-panel-header {
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 14px;
  background: #ffffff;
}

.expense-panel-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
}

.expense-tools-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.expense-search-input {
  position: relative;
  min-width: 260px;
}

.expense-search-input .search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 14px;
  pointer-events: none;
}

.expense-search-input input {
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

.expense-search-input input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.expense-search-input .clear-btn {
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

.expense-filter-select {
  height: 38px;
  padding: 0 32px 0 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 13.5px;
  font-weight: 500;
  color: #334155;
  background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 10px center;
  appearance: none;
  cursor: pointer;
  outline: none;
  transition: all 0.15s ease;
}

/* TABLE STYLING */
.expense-table-responsive {
  overflow-x: auto;
}

.expense-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  text-align: left;
}

.expense-table th {
  background: #f8fafc;
  padding: 12px 18px;
  font-size: 11.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #64748b;
  border-bottom: 1px solid #e2e8f0;
}

.expense-table td {
  padding: 14px 18px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 14px;
  vertical-align: middle;
}

.expense-table-row {
  transition: background 0.15s ease;
}

.expense-table-row:hover {
  background: #f8fafc;
}

.category-pill {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.method-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #475569;
  font-weight: 500;
}

.category-pill-large {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  background: #f1f5f9;
  color: #334155;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
}

.category-color-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
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

.expense-panel-footer {
  padding: 14px 20px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  background: #ffffff;
}

/* FORM ELEMENTS */
.form-label {
  display: block;
  margin-bottom: 6px;
  font-size: 13.5px;
  font-weight: 600;
  color: #475569;
}

.form-control, .form-select {
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

.form-control:focus, .form-select:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.amount-input-wrap {
  position: relative;
}

.amount-input-wrap span {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
  font-weight: 700;
  font-size: 14px;
  pointer-events: none;
}

.amount-input-wrap input {
  padding-left: 48px;
  font-weight: 700;
  font-size: 15px;
}

textarea.form-control {
  height: 84px;
  padding-top: 10px;
  resize: vertical;
}

/* PREVIEW DARK CARD */
.preview-dark-card {
  background: #0f172a;
  color: #ffffff;
  border-radius: 14px;
  padding: 20px;
  box-shadow: 0 4px 16px rgba(15, 23, 42, 0.15);
}

.preview-header {
  font-size: 14.5px;
  font-weight: 700;
  margin-bottom: 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  padding-bottom: 10px;
}

.preview-line {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-bottom: 12px;
}

.preview-label {
  font-size: 12.5px;
  color: #94a3b8;
}

.preview-value {
  font-size: 13.5px;
  font-weight: 600;
}

.total-line {
  border-top: 1px dashed rgba(255, 255, 255, 0.15);
  padding-top: 12px;
  margin-top: 6px;
  margin-bottom: 0;
}

.text-primary-light {
  color: #60a5fa;
  font-size: 1.1rem;
}

/* TOP SUCCESS ALERT */
.expense-top-alert {
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

/* POPUP MODAL STYLES */
.expense-modal-overlay {
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

.expense-modal-card {
  background: #ffffff;
  border-radius: 16px;
  width: 100%;
  max-width: 860px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  border: 1px solid #e2e8f0;
  animation: expenseModalFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes expenseModalFadeIn {
  from { opacity: 0; transform: scale(0.96) translateY(-8px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

.expense-modal-header {
  padding: 18px 24px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.expense-modal-header-left {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.expense-modal-icon {
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

.expense-modal-header-text {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.expense-modal-title {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.25;
}

.expense-modal-subtitle {
  margin: 3px 0 0 0;
  font-size: 13px;
  color: #64748b;
  line-height: 1.35;
}

.expense-modal-close {
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

.expense-modal-close:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.expense-modal-body {
  padding: 24px;
  max-height: calc(85vh - 80px);
  overflow-y: auto;
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
