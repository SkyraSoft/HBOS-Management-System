<template>
  <div class="dashboard-page px-4 py-4">

    <!-- PAGE HEADER -->
    <div class="dash-topbar-header mb-4 pb-3 border-bottom">
      <div class="dash-greeting-box">
        <h2 class="fw-bold mb-1 dash-greeting-title" style="color: var(--text-primary);">Good Morning, {{ userName }}</h2>
        <p class="text-secondary mb-0 dash-greeting-sub">Here's what's happening with your business today.</p>
      </div>

      <div class="dash-header-controls">
        <!-- GLOBAL DATE SELECTOR WITH VISIBLE CHEVRON -->
        <div class="dash-date-selector">
          <i class="bi bi-calendar3 text-primary date-icon"></i>
          <div class="dash-select-wrap">
            <select class="dash-select" v-model="dateFilterPreset" @change="onPresetChange">
              <option value="Today">Today</option>
              <option value="This Week">This Week</option>
              <option value="This Month">This Month</option>
              <option value="Previous Month">Previous Month</option>
              <option value="Last 3 Months">Last 3 Months</option>
              <option value="Last 6 Months">Last 6 Months</option>
              <option value="Custom">Custom Range...</option>
            </select>
            <i class="bi bi-chevron-down select-chevron"></i>
          </div>
          
          <!-- Custom Date Range Inputs with change triggers and future-date restriction -->
          <div v-if="dateFilterPreset === 'Custom'" class="custom-date-range-strip">
            <input 
              type="date" 
              class="custom-date-input" 
              v-model="customStartDate" 
              :max="todayDate"
              @input="onCustomDateChange"
              @change="onCustomDateChange"
              title="Start Date (Past & Today Only)"
            />
            <span class="text-muted small fw-semibold">to</span>
            <input 
              type="date" 
              class="custom-date-input" 
              v-model="customEndDate" 
              :min="customStartDate"
              :max="todayDate"
              @input="onCustomDateChange"
              @change="onCustomDateChange"
              title="End Date (Past & Today Only)"
            />
          </div>
        </div>

        <!-- QUICK ACTIONS (ALL 4 BUTTONS FULLY VISIBLE & RESPONSIVE) -->
        <div class="dash-actions-row">
          <button @click="handleNewSale" class="dash-action-btn" type="button" title="Open POS Terminal">
            <div class="dash-action-icon text-primary bg-primary-subtle">
              <i class="bi bi-cart-plus"></i>
            </div>
            <span class="dash-action-label">New Sale</span>
            <div class="dash-accent-line line-sale"></div>
          </button>

          <button @click="openProductModal" class="dash-action-btn" type="button" title="Add Product to Inventory">
            <div class="dash-action-icon text-info bg-info-subtle">
              <i class="bi bi-box-seam"></i>
            </div>
            <span class="dash-action-label">Add Product</span>
            <div class="dash-accent-line line-product"></div>
          </button>

          <button @click="openModal('payment')" class="dash-action-btn" type="button" title="Record Payment">
            <div class="dash-action-icon text-success bg-success-subtle">
              <i class="bi bi-wallet2"></i>
            </div>
            <span class="dash-action-label">Record Payment</span>
            <div class="dash-accent-line line-payment"></div>
          </button>

          <button @click="openModal('expense')" class="dash-action-btn" type="button" title="Record Expense">
            <div class="dash-action-icon text-danger bg-danger-subtle">
              <i class="bi bi-receipt"></i>
            </div>
            <span class="dash-action-label">Add Expense</span>
            <div class="dash-accent-line line-expense"></div>
          </button>
        </div>
      </div>
    </div>

    <!-- KPI CARDS -->
    <div class="row g-4 mb-4">
      <div class="col-md-3 col-sm-6" v-for="(kpi, index) in kpis" :key="index">
        <div 
          class="card border-0 shadow-sm rounded-4 h-100 p-4" 
          :style="kpi.title === 'Low Stock Items' ? 'cursor: pointer;' : ''" 
          @click="kpi.title === 'Low Stock Items' ? router.push('/inventry?tab=products&filter=low-stock') : null"
        >
          <div class="d-flex justify-content-between align-items-start mb-3">
            <span class="text-secondary fw-medium">{{ kpi.title }}</span>
            <div :class="'p-2 rounded bg-' + kpi.color + '-subtle text-' + kpi.color">
              <i :class="kpi.icon + ' fs-5'"></i>
            </div>
          </div>
          <h3 class="fw-bold mb-0" style="color: var(--text-primary);">{{ kpi.value }}</h3>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT GRID -->
    <div class="row g-4">
      <!-- SALES OVERVIEW CHART -->
      <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0">Sales Overview</h5>
          </div>
          <div style="height: 300px; position: relative;">
            <canvas id="salesChart"></canvas>
          </div>
        </div>
      </div>

      <!-- RIGHT PANEL: ALERTS -->
      <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
          <h5 class="fw-bold mb-4">Alerts</h5>
          
          <div @click="router.push('/inventry?tab=products&filter=low-stock')" class="d-flex align-items-start gap-3 p-3 rounded-3 bg-danger-subtle mb-3 border border-danger border-opacity-25" style="cursor: pointer;">
            <i class="bi bi-exclamation-triangle text-danger fs-4"></i>
            <div>
              <h6 class="fw-bold text-danger mb-1">Low Stock</h6>
              <p class="small text-danger mb-0">5 products need attention</p>
            </div>
            <i class="bi bi-chevron-right ms-auto text-danger mt-2"></i>
          </div>

          <div @click="router.push('/khata?tab=khata')" class="d-flex align-items-start gap-3 p-3 rounded-3 bg-warning-subtle border border-warning border-opacity-25" style="cursor: pointer;">
            <i class="bi bi-clock-history text-warning fs-4"></i>
            <div>
              <h6 class="fw-bold text-warning mb-1">Overdue Credit</h6>
              <p class="small text-warning mb-0">3 customers pending</p>
            </div>
            <i class="bi bi-chevron-right ms-auto text-warning mt-2"></i>
          </div>

        </div>
      </div>
    </div>

    <!-- LOWER GRID -->
    <div class="row g-4 mt-1">
      
      <!-- RECENT TRANSACTIONS -->
      <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
          <div class="d-flex align-items-center justify-content-between mb-4">
            <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
              <i class="bi bi-graph-up text-primary"></i> Daily Sales Report
            </h5>
            <a href="#" @click.prevent="router.push('/reports?tab=reports')" class="text-primary text-decoration-none small fw-medium">View All</a>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="text-secondary small">
                <tr>
                  <th class="border-0 fw-medium">TYPE</th>
                  <th class="border-0 fw-medium">REF</th>
                  <th class="border-0 fw-medium">ENTITY</th>
                  <th class="border-0 fw-medium">STATUS</th>
                  <th class="border-0 fw-medium text-end">AMOUNT</th>
                </tr>
              </thead>
              <tbody class="border-top-0">
                <tr v-for="t in transactions" :key="t.ref">
                  <td>
                    <span :class="t.type === 'Sale' ? 'badge bg-primary-subtle text-primary' : 'badge bg-danger-subtle text-danger'" class="rounded-pill px-3 py-2">{{ t.type }}</span>
                  </td>
                  <td class="fw-medium text-secondary">{{ t.ref }}</td>
                  <td>{{ t.entity }}</td>
                  <td>
                    <span :class="getStatusBadge(t.status)" class="badge rounded-pill px-3 py-2">{{ t.status }}</span>
                  </td>
                  <td class="fw-bold text-end">{{ t.amount }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- LOW STOCK & TOP SELLING -->
      <div class="col-md-4">
        <!-- LOW STOCK -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">Low Stock Products</h6>
            <a href="#" @click.prevent="router.push('/inventry?tab=products&filter=low-stock')" class="text-primary text-decoration-none small fw-medium">View All</a>
          </div>
          
          <div v-for="(item, index) in lowStockProducts" :key="item.id" class="d-flex justify-content-between align-items-center pb-3 mb-3" :class="{'border-bottom': index !== lowStockProducts.length - 1}">
            <div>
              <div class="fw-medium">{{ item.name }}</div>
              <div class="small text-secondary">Current: {{ item.stock || 0 }} / Min: {{ item.minStock ?? item.min ?? 10 }}</div>
            </div>
            <button @click="router.push('/inventry?tab=products&filter=low-stock')" class="btn btn-sm btn-light border fw-medium text-primary rounded-pill px-3">Restock</button>
          </div>
          <div v-if="lowStockProducts.length === 0" class="text-secondary small text-center py-3">No low stock items.</div>

        </div>
        
        <!-- TOP SELLING -->
        <div class="card border-0 shadow-sm rounded-4 p-4">
          <h6 class="fw-bold mb-3">Top Selling (Today)</h6>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
              <span class="text-secondary fw-bold small">1.</span>
              <span class="fw-medium">Lipton Tea 250g</span>
            </div>
            <span class="badge bg-primary-subtle text-primary rounded-pill">45 units</span>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
              <span class="text-secondary fw-bold small">2.</span>
              <span class="fw-medium">Sugar 1kg</span>
            </div>
            <span class="badge bg-primary-subtle text-primary rounded-pill">32 units</span>
          </div>
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <span class="text-secondary fw-bold small">3.</span>
              <span class="fw-medium">Bread (Small)</span>
            </div>
            <span class="badge bg-primary-subtle text-primary rounded-pill">28 units</span>
          </div>
        </div>
      </div>

    </div>

  
    <!-- Modals -->
    
    
    <!-- Direct Add Product Modal on Dashboard -->
    <div 
      v-if="activeModal === 'product'" 
      class="modal fade show d-block" 
      tabindex="-1"
      style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.6); z-index: 9999; display: flex !important; align-items: center; justify-content: center; overflow-y: auto; padding: 20px;" 
      @click.self="closeProductModal"
    >
      <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 850px; width: 100%; margin: auto;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header border-0 bg-light px-4 py-3">
            <h5 class="modal-title fw-bold text-dark m-0">
              <i class="bi bi-box-seam me-1 text-primary"></i>
              Add New Inventory Product
            </h5>
            <button type="button" class="btn-close" @click="closeProductModal"></button>
          </div>

          <div class="modal-body px-4 py-3">
            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label small fw-semibold text-secondary">Product Name <span class="text-danger">*</span></label>
                <input v-model="dashProductForm.name" type="text" class="form-control rounded-3" placeholder="e.g. Nestlé Pure Life 1.5L" />
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">SKU / Code <span class="text-danger">*</span></label>
                <input v-model="dashProductForm.sku" type="text" class="form-control rounded-3" placeholder="SKU-1001" />
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Category</label>
                <select v-model="dashProductForm.category" class="form-select rounded-3">
                  <option value="">Select Category</option>
                  <option v-for="c in categories" :key="c.id" :value="c.name">{{ c.name }}</option>
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Sub-Category</label>
                <select v-model="dashProductForm.subcategory" class="form-select rounded-3">
                  <option value="">Select Sub-Category</option>
                  <option v-for="s in subcategories" :key="s.id" :value="s.name">{{ s.name }}</option>
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Brand</label>
                <select v-model="dashProductForm.brand" class="form-select rounded-3">
                  <option value="">Select Brand</option>
                  <option v-for="b in brands" :key="b.id" :value="b.name">{{ b.name }}</option>
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Purchase Cost (PKR)</label>
                <input v-model.number="dashProductForm.purchasePrice" type="number" step="0.01" class="form-control rounded-3" placeholder="0.00" />
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Selling Price (PKR) <span class="text-danger">*</span></label>
                <input v-model.number="dashProductForm.sellingPrice" type="number" step="0.01" class="form-control rounded-3" placeholder="0.00" />
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Calculated Margin</label>
                <div class="p-2 rounded-3 bg-success-subtle text-success fw-bold text-center">
                  {{ calculateDashMargin(dashProductForm.purchasePrice, dashProductForm.sellingPrice) }}% Profit
                </div>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Initial Stock Qty</label>
                <input v-model.number="dashProductForm.stock" type="number" class="form-control rounded-3" placeholder="10" />
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Min Reorder Alert</label>
                <input v-model.number="dashProductForm.minStock" type="number" class="form-control rounded-3" placeholder="5" />
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Unit of Measure</label>
                <select v-model="dashProductForm.unit" class="form-select rounded-3">
                  <option value="Piece">Piece (pcs)</option>
                  <option value="Box">Box</option>
                  <option value="Kg">Kg</option>
                  <option value="Liter">Liter</option>
                  <option value="Pack">Pack</option>
                </select>
              </div>

              <div class="col-12">
                <label class="form-label small fw-semibold text-secondary">Product Image</label>
                <input type="file" @change="handleDashImageUpload" accept="image/*" class="form-control rounded-3" />
                <div v-if="dashProductForm.imagePreview" class="mt-2">
                  <img :src="dashProductForm.imagePreview" style="height: 60px; border-radius: 8px; border: 1px solid #e2e8f0;" />
                </div>
              </div>

              <div class="col-12 mt-2">
                <div class="form-check form-switch mb-3">
                  <input class="form-check-input" type="checkbox" role="switch" id="dashHasVariationsToggle" v-model="dashProductForm.hasVariations">
                  <label class="form-check-label fw-semibold text-dark" for="dashHasVariationsToggle">Product has variations (e.g. Size, Color, Pack)</label>
                </div>

                <div v-if="dashProductForm.hasVariations" class="p-3 border rounded-3 bg-light">
                  <h6 class="fw-bold mb-3 text-secondary">Attributes</h6>
                  <div v-for="(attr, index) in dashProductForm.attributes" :key="index" class="d-flex align-items-center mb-2 gap-2">
                    <input type="text" class="form-control rounded-3" placeholder="Attribute (e.g. Size)" v-model="attr.name" style="max-width: 200px;">
                    <input type="text" class="form-control rounded-3" placeholder="Values (comma separated e.g. S, M, L)" v-model="attr.values">
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-3 px-3 py-2" @click="removeDashAttribute(index)" title="Remove Attribute">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                  <button type="button" class="btn btn-sm btn-outline-primary rounded-pill mt-2" @click="addDashAttribute">
                    <i class="bi bi-plus me-1"></i> Add Attribute
                  </button>
                </div>
              </div>

              <div class="col-12">
                <label class="form-label small fw-semibold text-secondary">Description / Internal Notes</label>
                <textarea v-model="dashProductForm.description" rows="2" class="form-control rounded-3" placeholder="Optional notes..."></textarea>
              </div>
            </div>
          </div>

          <div class="modal-footer border-0 bg-light px-4 py-2.5">
            <button type="button" class="btn btn-light rounded-3" @click="closeProductModal">Cancel</button>
            <button type="button" class="btn btn-primary rounded-3 px-4" @click="submitDashboardProduct" :disabled="isSubmittingProduct">
              <span v-if="isSubmittingProduct" class="spinner-border spinner-border-sm me-1"></span>
              Save Product
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="activeModal === 'payment'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050; background: rgba(0,0,0,0.5); pointer-events: auto;">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="max-height: 90vh;">
          <div class="modal-header bg-light border-0">
            <h5 class="modal-title fw-bold">Record Payment</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body p-4" style="background: var(--bg); overflow-y: auto;">
            <KhataView :isModal="true" modalPage="payment" 
              @success="handleModalSuccess('Payment recorded successfully!')" @close="closeModal" 
              @open-purchase="openModal('purchase')" @open-return-debt="openModal('returndebt')" />
          </div>
        </div>
      </div>
    </div>

    <!-- Add Expense Popup Modal -->
    <div v-if="activeModal === 'expense'" class="dash-modal-overlay" @click.self="closeModal">
      <div class="dash-modal-card">
        <div class="dash-modal-header">
          <div class="dash-modal-header-left">
            <div class="dash-modal-icon">
              <i class="bi bi-wallet2"></i>
            </div>
            <div class="dash-modal-header-text">
              <h3 class="dash-modal-title">Record New Expense</h3>
              <p class="dash-modal-subtitle">
                Log a new business operational cost into your store ledger.
              </p>
            </div>
          </div>
          <button type="button" class="dash-modal-close" @click="closeModal" aria-label="Close">✕</button>
        </div>

        <div class="dash-modal-body">
          <div class="dash-modal-layout">
            <!-- FORM COLUMN -->
            <form @submit.prevent="submitDashboardExpense" id="dashExpenseForm">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label">Expense Description <span class="text-danger">*</span></label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="dashExpenseForm.description" 
                    placeholder="e.g., Monthly Store Electricity Bill" 
                    required 
                  />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Category <span class="text-danger">*</span></label>
                  <select class="form-select" v-model="dashExpenseForm.category" required>
                    <option value="" disabled>Select Category</option>
                    <option v-for="cat in expenseCategories" :key="cat" :value="cat">{{ cat }}</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Amount (PKR) <span class="text-danger">*</span></label>
                  <div class="amount-input-wrap">
                    <span>Rs.</span>
                    <input 
                      type="number" 
                      class="form-control" 
                      v-model.number="dashExpenseForm.amount" 
                      min="1" 
                      step="1" 
                      placeholder="0.00" 
                      required 
                    />
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Date <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" v-model="dashExpenseForm.date" required />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                  <select class="form-select" v-model="dashExpenseForm.method" required>
                    <option>Cash</option>
                    <option>Bank Transfer</option>
                    <option>Card</option>
                    <option>Cheque</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Reference No. (Optional)</label>
                  <input type="text" class="form-control" v-model="dashExpenseForm.reference" placeholder="Receipt or Tx #12345" />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Branch Allocation</label>
                  <select class="form-select" v-model="dashExpenseForm.branch">
                    <option>Main Branch</option>
                  </select>
                </div>

                <div class="col-12">
                  <label class="form-label">Additional Notes (Optional)</label>
                  <textarea class="form-control" rows="2" v-model="dashExpenseForm.notes" placeholder="Notes or remarks..."></textarea>
                </div>
              </div>
            </form>

            <!-- LIVE PREVIEW SIDEBAR -->
            <aside class="dash-modal-preview">
              <div class="preview-dark-card">
                <div class="preview-header">
                  <i class="bi bi-receipt me-1"></i> Expense Preview
                </div>

                <div class="preview-line">
                  <span class="preview-label">Category</span>
                  <span class="preview-value">{{ dashExpenseForm.category || '—' }}</span>
                </div>

                <div class="preview-line">
                  <span class="preview-label">Date</span>
                  <span class="preview-value">{{ dashExpenseForm.date || '—' }}</span>
                </div>

                <div class="preview-line">
                  <span class="preview-label">Payment Method</span>
                  <span class="preview-value">{{ dashExpenseForm.method || 'Cash' }}</span>
                </div>

                <div class="preview-line total-line">
                  <span class="preview-label">Total Amount</span>
                  <span class="preview-value text-primary-light">PKR {{ (dashExpenseForm.amount || 0).toLocaleString() }}</span>
                </div>
              </div>

              <div class="modal-action-buttons mt-3">
                <button 
                  class="btn btn-primary w-100 fw-bold py-2.5 rounded-3 mb-2 shadow-sm" 
                  type="submit" 
                  form="dashExpenseForm"
                  :disabled="isSubmittingExpense"
                >
                  <span v-if="isSubmittingExpense" class="spinner-border spinner-border-sm me-1"></span>
                  Save Expense
                </button>
                <button class="btn btn-light w-100 rounded-3 py-2" type="button" @click="closeModal">
                  Close
                </button>
              </div>
            </aside>
          </div>
        </div>
      </div>
    </div>

  
    <!-- Purchase Modal -->
    <div v-if="activeModal === 'purchase'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050; background: rgba(0,0,0,0.5); pointer-events: auto;"><div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" >
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="height: 90vh;">
          <div class="modal-header bg-light border-0">
            <h5 class="modal-title fw-bold">New Purchase</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body p-0" style="background: var(--bg);">
            <PurchaseView :isModal="true" @success="handleModalSuccess('Purchase recorded successfully!')" />
          </div>
        </div>
      </div>
    </div>

    <!-- Return Debt Modal -->
    <div v-if="activeModal === 'returndebt'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050; background: rgba(0,0,0,0.5); pointer-events: auto;"><div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" >
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="height: 90vh;">
          <div class="modal-header bg-light border-0">
            <h5 class="modal-title fw-bold">Return Debt</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body p-0" style="background: var(--bg);">
            <ReturnDebtView :isModal="true" @success="handleModalSuccess('Debt returned successfully!')" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useRetailStore } from '../stores/retail'
import api from '../api'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const retailStore = useRetailStore()

const lowStockProducts = computed(() => {
  if (!retailStore.products) return [];
  return retailStore.products.filter(p => (p.stock || 0) <= (p.minStock ?? p.min ?? 10)).slice(0, 5);
});
const userName = computed(() => authStore.user?.name || 'User')

const getTodayLocal = () => {
  const d = new Date();
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const date = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${date}`;
};

const todayDate = computed(() => getTodayLocal());

// Permanently stored custom date range with past-only boundary
const storedPreset = localStorage.getItem('hbos_dash_date_preset');
const storedStart = localStorage.getItem('hbos_dash_custom_start');
const storedEnd = localStorage.getItem('hbos_dash_custom_end');

const dateFilterPreset = ref(storedPreset || 'This Month');
const customStartDate = ref((storedStart && storedStart <= getTodayLocal()) ? storedStart : getTodayLocal());
const customEndDate = ref((storedEnd && storedEnd <= getTodayLocal()) ? storedEnd : getTodayLocal());

const handleNewSale = () => {
  router.push('/POS?tab=sales');
};

const onPresetChange = () => {
  localStorage.setItem('hbos_dash_date_preset', dateFilterPreset.value);
  fetchDashboardData();
};

const onCustomDateChange = () => {
  const today = getTodayLocal();
  // Prevent any future date selection
  if (customStartDate.value && customStartDate.value > today) {
    customStartDate.value = today;
  }
  if (customEndDate.value && customEndDate.value > today) {
    customEndDate.value = today;
  }
  // Ensure start date does not exceed end date
  if (customStartDate.value && customEndDate.value && customStartDate.value > customEndDate.value) {
    customStartDate.value = customEndDate.value;
  }

  if (customStartDate.value) {
    localStorage.setItem('hbos_dash_custom_start', customStartDate.value);
  }
  if (customEndDate.value) {
    localStorage.setItem('hbos_dash_custom_end', customEndDate.value);
  }
  fetchDashboardData();
};

// Auto-save whenever values change with future date validation
watch(customStartDate, (newVal) => {
  const today = getTodayLocal();
  if (newVal && newVal > today) {
    customStartDate.value = today;
    return;
  }
  if (newVal) {
    localStorage.setItem('hbos_dash_custom_start', newVal);
  }
}, { immediate: true, flush: 'sync' });

watch(customEndDate, (newVal) => {
  const today = getTodayLocal();
  if (newVal && newVal > today) {
    customEndDate.value = today;
    return;
  }
  if (newVal) {
    localStorage.setItem('hbos_dash_custom_end', newVal);
  }
}, { immediate: true, flush: 'sync' });

watch(dateFilterPreset, (newVal) => {
  if (newVal) {
    localStorage.setItem('hbos_dash_date_preset', newVal);
  }
}, { immediate: true, flush: 'sync' });

const stats = ref({
  sales_total: 0,
  purchases_total: 0,
  expenses_total: 0,
  customer_count: 0,
  net_revenue: 0,
  chart_data: {
    labels: [],
    data: []
  }
})

const kpis = computed(() => {
  return [
    { title: "Sales", value: `PKR ${Number(stats.value.sales_total || 0).toLocaleString()}`, icon: "bi-cash-stack", color: "primary" },
    { title: "Net Revenue", value: `PKR ${Number(stats.value.net_revenue || 0).toLocaleString()}`, icon: "bi-graph-up", color: "success" },
    { title: "New Customers", value: `${stats.value.customer_count || 0}`, icon: "bi-people", color: "warning" },
    { title: "Expenses", value: `PKR ${Number(stats.value.expenses_total || 0).toLocaleString()}`, icon: "bi-cash", color: "danger" },
    { title: "Purchases", value: `PKR ${Number(stats.value.purchases_total || 0).toLocaleString()}`, icon: "bi-box", color: "info" },
    { title: "Active Products", value: "1,452", icon: "bi-tags", color: "primary" },
    { title: "Low Stock Items", value: "12", icon: "bi-exclamation-triangle", color: "warning" },
    { title: "Receivables", value: "PKR 24,500", icon: "bi-wallet2", color: "danger" }
  ]
})

const transactions = ref([])

const getDatesForPreset = (preset, customStart = null, customEnd = null) => {
  const formatDate = (d) => {
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const date = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${date}`;
  };

  if (preset === 'Custom') {
    return {
      startDate: customStart || getTodayLocal(),
      endDate: customEnd || getTodayLocal()
    };
  }

  const now = new Date();
  let start = new Date();
  let end = new Date();

  if (preset === 'Today') {
    start.setHours(0,0,0,0);
    end.setHours(23,59,59,999);
  } else if (preset === 'This Week') {
    const day = now.getDay();
    const diff = now.getDate() - day + (day === 0 ? -6 : 1);
    start = new Date(now.setDate(diff));
    start.setHours(0,0,0,0);
    end = new Date(start);
    end.setDate(start.getDate() + 6);
    end.setHours(23,59,59,999);
  } else if (preset === 'This Month') {
    start = new Date(now.getFullYear(), now.getMonth(), 1);
    end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    end.setHours(23,59,59,999);
  } else if (preset === 'Previous Month') {
    start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
    end = new Date(now.getFullYear(), now.getMonth(), 0);
    end.setHours(23,59,59,999);
  } else if (preset === 'Last 3 Months') {
    start = new Date(now.getFullYear(), now.getMonth() - 2, 1);
    end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    end.setHours(23,59,59,999);
  } else if (preset === 'Last 6 Months') {
    start = new Date(now.getFullYear(), now.getMonth() - 5, 1);
    end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    end.setHours(23,59,59,999);
  }

  return {
    startDate: formatDate(start),
    endDate: formatDate(end)
  };
}

const fetchDashboardData = async () => {
  try {
    const { startDate, endDate } = getDatesForPreset(dateFilterPreset.value, customStartDate.value, customEndDate.value)
    const response = await api.get('/dashboard/stats', {
      params: {
        start_date: startDate,
        end_date: endDate
      }
    })
    stats.value = response.data
  } catch (error) {
    console.error('Error fetching dashboard stats:', error)
  }
}

const fetchRecentTransactions = async () => {
  try {
    const [salesRes, purchasesRes] = await Promise.all([
      api.get('/sales'),
      api.get('/purchases')
    ])
    
    let allTx = [
      ...salesRes.data.map(s => ({
        type: 'Sale',
        ref: `INV-${s.id}`,
        entity: s.customer_id ? `Customer #${s.customer_id}` : 'Walk-in Customer',
        status: 'Paid',
        amount: `PKR ${Number(s.total).toLocaleString()}`,
        date: new Date(s.date)
      })),
      ...purchasesRes.data.map(p => ({
        type: 'Purchase',
        ref: `PO-${p.id}`,
        entity: p.supplier_id ? `Supplier #${p.supplier_id}` : 'Supplier',
        status: 'Paid',
        amount: `PKR ${Number(p.total).toLocaleString()}`,
        date: new Date(p.date)
      }))
    ];
    
    allTx.sort((a, b) => b.date - a.date);
    transactions.value = allTx.slice(0, 5);
    
  } catch (error) {
    console.error('Error fetching recent transactions:', error)
    transactions.value = [
      { type: "Sale", ref: "INV-092", entity: "Walk-in Customer", status: "Paid", amount: "PKR 4,200" },
      { type: "Purchase", ref: "PO-145", entity: "Faisal Traders", status: "Pending", amount: "PKR 15,000" },
      { type: "Sale", ref: "INV-091", entity: "Ali (Khata)", status: "Credit", amount: "PKR 2,850" }
    ]
  }
}

const getStatusBadge = (status) => {
  if (status === 'Paid') return 'bg-success-subtle text-success'
  if (status === 'Pending') return 'bg-warning-subtle text-warning'
  if (status === 'Credit') return 'bg-orange-subtle text-orange'
  return 'bg-secondary-subtle text-secondary'
}

let salesChartInstance = null

const handlePopState = () => {
  if (activeModal.value === 'product') {
    activeModal.value = null
  }
}

onMounted(() => {
  window.addEventListener('popstate', handlePopState)
  fetchDashboardData();
  fetchRecentTransactions();
  retailStore.fetchProducts();

  if (window.Chart) {
    const ctx = document.getElementById('salesChart')
    if (ctx) {
      salesChartInstance = new window.Chart(ctx, {
        type: 'line',
        data: {
          labels: stats.value.chart_data?.labels || [],
          datasets: [{
            label: 'Sales (PKR)',
            data: stats.value.chart_data?.data || [],
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            fill: true
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
            x: { grid: { display: false } }
          }
        }
      })
    }
  }
})

onUnmounted(() => {
  window.removeEventListener('popstate', handlePopState)
})

watch([dateFilterPreset, customStartDate, customEndDate], () => {
  fetchDashboardData()
})

watch(() => stats.value.chart_data, (newData) => {
  if (!salesChartInstance || !newData) return;
  salesChartInstance.data.labels = newData.labels || [];
  salesChartInstance.data.datasets[0].data = newData.data || [];
  salesChartInstance.update();
}, { deep: true })

import ProductView from './ProductView.vue'
import ExpensesView from './ExpensesView.vue'
import CustomerView from './CustomerView.vue'
import PurchaseView from './PurchaseView.vue'
import ReturnDebtView from './ReturnDebtView.vue'
import KhataView from './KhataView.vue'

const activeModal = ref(null)
const expenseCategories = ref(['Utilities', 'Maintenance', 'Rent', 'Welfare', 'Transport', 'Other'])
const isSubmittingExpense = ref(false)
const dashExpenseForm = ref({
  description: '',
  category: 'Utilities',
  amount: null,
  date: new Date().toISOString().split('T')[0],
  method: 'Cash',
  reference: '',
  branch: 'Main Branch',
  notes: ''
})

watch(() => [route.path, route.query.tab], ([path, tab]) => {
  if (tab === 'products' || tab === 'add-product') {
    activeModal.value = 'product'
  } else if (tab === 'payment' || tab === 'record-payment') {
    activeModal.value = 'payment'
  } else if (tab === 'expense' || tab === 'add-expense' || tab === 'expenses') {
    activeModal.value = 'expense'
    dashExpenseForm.value = {
      description: '',
      category: 'Utilities',
      amount: null,
      date: new Date().toISOString().split('T')[0],
      method: 'Cash',
      reference: '',
      branch: 'Main Branch',
      notes: ''
    }
  } else if (tab === 'purchase') {
    activeModal.value = 'purchase'
  } else if (tab === 'returndebt') {
    activeModal.value = 'returndebt'
  } else if (!tab && !window.location.search.includes('tab=') && activeModal.value !== 'product') {
    activeModal.value = null
  }
}, { immediate: true })

const isSubmittingProduct = ref(false)
const dashProductForm = ref({
  name: '',
  sku: '',
  barcode: '',
  brand: '',
  category: '',
  subcategory: '',
  purchasePrice: 0,
  sellingPrice: 0,
  stock: 10,
  minStock: 5,
  unit: 'Piece',
  description: '',
  image: null,
  imagePreview: ''
})

const categories = computed(() => retailStore.categories || [])
const subcategories = computed(() => retailStore.subcategories || [])
const brands = computed(() => retailStore.brands || [])

const closeProductModal = () => {
  activeModal.value = null
  if (window.location.pathname.includes('/inventry') || window.location.search.includes('tab=products')) {
    window.history.pushState(null, '', '/')
  }
}

const resetDashProductForm = () => {
  dashProductForm.value = {
    name: '',
    sku: 'SKU-' + Math.floor(1000 + Math.random() * 9000),
    barcode: '',
    brand: '',
    category: '',
    subcategory: '',
    purchasePrice: 0,
    sellingPrice: 0,
    stock: 10,
    minStock: 5,
    unit: 'Piece',
    description: '',
    image: null,
    imagePreview: '',
    hasVariations: false,
    attributes: []
  }
}

const addDashAttribute = () => {
  dashProductForm.value.attributes.push({ name: '', values: '' })
}

const removeDashAttribute = (index) => {
  dashProductForm.value.attributes.splice(index, 1)
}

const handleDashImageUpload = (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  dashProductForm.value.image = file
  const reader = new FileReader()
  reader.onload = (ev) => {
    dashProductForm.value.imagePreview = ev.target.result
  }
  reader.readAsDataURL(file)
}

const calculateDashMargin = (cost, price) => {
  const c = parseFloat(cost) || 0
  const p = parseFloat(price) || 0
  if (p <= 0) return 0
  return (((p - c) / p) * 100).toFixed(1)
}

const submitDashboardProduct = async () => {
  if (!dashProductForm.value.name.trim() || !dashProductForm.value.sku.trim() || !dashProductForm.value.sellingPrice) {
    alert('Please fill Product Name, SKU, and Selling Price.')
    return
  }

  isSubmittingProduct.value = true
  const payload = {
    name: dashProductForm.value.name.trim(),
    sku: dashProductForm.value.sku.trim(),
    barcode: dashProductForm.value.barcode.trim() || dashProductForm.value.sku.trim(),
    brand: dashProductForm.value.brand,
    category: dashProductForm.value.category,
    subcategory: dashProductForm.value.subcategory,
    purchasePrice: parseFloat(dashProductForm.value.purchasePrice) || 0,
    sellingPrice: parseFloat(dashProductForm.value.sellingPrice) || 0,
    cost: parseFloat(dashProductForm.value.purchasePrice) || 0,
    price: parseFloat(dashProductForm.value.sellingPrice) || 0,
    stock: parseInt(dashProductForm.value.stock) || 0,
    minStock: parseInt(dashProductForm.value.minStock) || 5,
    unit: dashProductForm.value.unit,
    description: dashProductForm.value.description,
    imageFile: dashProductForm.value.image,
    image_url: dashProductForm.value.imagePreview || '',
    hasVariations: dashProductForm.value.hasVariations,
    attributes: dashProductForm.value.hasVariations ? dashProductForm.value.attributes : []
  }

  try {
    const res = await retailStore.addProduct(payload)
    if (res && res.success !== false) {
      closeProductModal()
      alert('Product added to inventory successfully!')
      retailStore.fetchProducts()
      fetchDashboardData()
    } else {
      alert(res?.message || 'Error adding product.')
    }
  } catch (e) {
    console.error(e)
    alert('Error saving product.')
  } finally {
    isSubmittingProduct.value = false
  }
}

const openProductModal = () => {
  resetDashProductForm()
  if (retailStore.categories.length === 0) retailStore.fetchCategories()
  if (retailStore.subcategories.length === 0) retailStore.fetchSubcategories()
  if (retailStore.brands.length === 0) retailStore.fetchBrands()
  activeModal.value = 'product'
  window.history.pushState({ modal: 'product' }, '', '/inventry?tab=products')
}

const openModal = (type) => {
  if (type === 'product') {
    openProductModal()
  } else if (type === 'payment') {
    activeModal.value = type;
    router.push({ path: '/', query: { tab: 'record-payment' } })
  } else if (type === 'expense') {
    activeModal.value = type;
    router.push({ path: '/', query: { tab: 'expense' } })
  } else if (type === 'purchase') {
    activeModal.value = type;
    router.push({ path: '/', query: { tab: 'purchase' } })
  } else if (type === 'returndebt') {
    activeModal.value = type;
    router.push({ path: '/', query: { tab: 'returndebt' } })
  }
}

const closeModal = () => {
  activeModal.value = null
  if (route.query.tab) {
    router.push({ path: '/' })
  }
}

const submitDashboardExpense = async () => {
  if (!dashExpenseForm.value.description || !dashExpenseForm.value.amount || !dashExpenseForm.value.category) {
    alert('Please fill all required fields.')
    return
  }
  isSubmittingExpense.value = true
  try {
    const res = await api.post('/expenses', dashExpenseForm.value)
    if (res && res.data) {
      closeModal()
      alert('Expense recorded successfully!')
      fetchDashboardData()
    } else {
      alert('Failed to record expense.')
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Error occurred while saving expense.')
  } finally {
    isSubmittingExpense.value = false
  }
}

const handleModalSuccess = (msg) => {
  closeModal()
  alert(msg || 'Action completed successfully!')
  fetchDashboardData()
}
</script>

<style scoped>
.dashboard-page {
  font-family: 'Inter', sans-serif;
  background-color: var(--bg);
}
.bg-orange-subtle {
  background-color: #ffedd5 !important;
}
.text-orange {
  color: #ea580c !important;
}
/* Ensure consistent KPI card row */
.kpi-grid .col-md-3 {
  flex: 1 0 18%; /* Force 5 columns */
}


/* ===== TOPBAR HEADER & CONTROLS ===== */
.dash-topbar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
}

.dash-greeting-box {
  min-width: 220px;
}

.dash-greeting-title {
  font-size: 1.6rem;
  letter-spacing: -0.02em;
}

.dash-header-controls {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

/* DATE SELECTOR */
.dash-date-selector {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #ffffff;
  padding: 5px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
  height: 42px;
}

.date-icon {
  font-size: 15px;
}

.dash-select-wrap {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.dash-select {
  border: none;
  background: transparent;
  font-size: 13.5px;
  font-weight: 600;
  color: #0f172a;
  padding-right: 22px;
  cursor: pointer;
  outline: none;
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
}

.select-chevron {
  position: absolute;
  right: 2px;
  pointer-events: none;
  font-size: 11px;
  color: #64748b;
  font-weight: 700;
}

.custom-date-range-strip {
  display: flex;
  align-items: center;
  gap: 6px;
  border-left: 1px solid #e2e8f0;
  padding-left: 8px;
  margin-left: 4px;
}

.custom-date-input {
  height: 30px;
  padding: 0 6px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 12.5px;
  font-weight: 500;
  color: #334155;
  background: #f8fafc;
  outline: none;
}

.custom-date-input:focus {
  border-color: #2563eb;
  background: #ffffff;
}

.dash-actions-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

/* ===== ACTION BUTTONS DESIGN ===== */
.dash-action-btn {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 8px 16px;
  cursor: pointer;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02);
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  text-decoration: none;
  white-space: nowrap;
  flex-shrink: 0;
}

.dash-action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.07);
  border-color: #cbd5e1;
}

.dash-action-btn:active {
  transform: translateY(0);
}

.dash-action-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  transition: transform 0.2s ease;
}

.dash-action-btn:hover .dash-action-icon {
  transform: scale(1.08);
}

.dash-action-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1e293b;
  white-space: nowrap;
}

.dash-accent-line {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 3px;
  border-radius: 0 0 10px 10px;
}

.line-sale {
  background: #2563eb;
}

.line-product {
  background: #0284c7;
}

.line-payment {
  background: #10b981;
}

.line-expense {
  background: #ef4444;
}

@media (max-width: 992px) {
  .kpi-grid .col-md-3 {
    flex: 1 0 45%;
  }
}

/* MODAL OVERLAY & POPUP FOR DASHBOARD */
.dash-modal-overlay {
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

.dash-modal-card {
  background: #ffffff;
  border-radius: 16px;
  width: 100%;
  max-width: 860px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  border: 1px solid #e2e8f0;
  animation: dashModalFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes dashModalFadeIn {
  from { opacity: 0; transform: scale(0.96) translateY(-8px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

.dash-modal-header {
  padding: 18px 24px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.dash-modal-header-left {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.dash-modal-icon {
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

.dash-modal-header-text {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.dash-modal-title {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.25;
}

.dash-modal-subtitle {
  margin: 3px 0 0 0;
  font-size: 13px;
  color: #64748b;
  line-height: 1.35;
}

.dash-modal-close {
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

.dash-modal-close:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.dash-modal-body {
  padding: 24px;
  max-height: calc(85vh - 80px);
  overflow-y: auto;
}

.dash-modal-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 280px;
  gap: 24px;
  align-items: start;
}

@media (max-width: 768px) {
  .dash-modal-layout {
    grid-template-columns: 1fr;
  }
}

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
  height: 72px;
  padding-top: 10px;
  resize: vertical;
}

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
</style>
