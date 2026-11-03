<template>
  <div class="supplierpayable-page-wrapper">
    <!-- TOP SUCCESS / ALERT NOTIFICATION -->
    <transition name="alert-slide">
      <div v-if="successAlertMessage" class="supplier-top-alert" role="alert">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-check-circle-fill text-success fs-5"></i>
          <div>
            <strong>Success!</strong> {{ successAlertMessage }}
          </div>
        </div>
        <button type="button" class="btn-close" @click="successAlertMessage = ''" aria-label="Close"></button>
      </div>
    </transition>

    <div class="supplier-main-container">

      <!-- ====================================================
           VIEW A: NEW PURCHASE FORM
      ==================================================== -->
      <div v-if="purchasesIsNewPurchase" class="new-purchase-view mb-4">
        <!-- TOP BREADCRUMB / BACK BAR -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
          <button class="btn btn-sm btn-light border rounded-3 fw-semibold px-3 py-2 shadow-sm d-flex align-items-center gap-2" @click="purchasesIsNewPurchase = false" type="button">
            <i class="bi bi-arrow-left"></i> Back to Suppliers
          </button>
          <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 font-monospace">
            Direct Stock Receiving &amp; Procurement
          </span>
        </div>

        <div class="supplier-panel-card p-4">
          <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
            <div>
              <h4 class="fw-bold m-0 text-dark">Create New Purchase Order / Invoice</h4>
              <p class="text-muted small m-0">Receive inventory stock and generate supplier payable invoice</p>
            </div>
          </div>

          <form @submit.prevent="confirmNewPurchase">
            <div class="row g-3 mb-4">
              <div class="col-md-4">
                <label class="form-label fw-semibold small text-secondary">Supplier <span class="text-danger">*</span></label>
                <select class="form-select" v-model="newPurchSupplierId" required>
                  <option :value="null" disabled>Select Supplier...</option>
                  <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }} (Bal: PKR {{ (Number(s.balance) || 0).toLocaleString() }})</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold small text-secondary">Supplier Invoice #</label>
                <input type="text" class="form-control" v-model="newPurchInvoiceNo" placeholder="e.g. INV-2026-001" />
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold small text-secondary">Invoice Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" v-model="newPurchDate" required />
              </div>
            </div>

            <!-- PURCHASE ITEMS TABLE -->
            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="fw-semibold small text-secondary m-0">Purchase Items &amp; Stock</label>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-3" @click="addNewPurchItem">
                  <i class="bi bi-plus-lg me-1"></i> Add Line Item
                </button>
              </div>

              <div class="table-responsive border rounded-3 bg-white">
                <table class="table align-middle m-0">
                  <thead class="bg-light">
                    <tr>
                      <th style="min-width: 240px;">Product</th>
                      <th style="width: 120px;">Qty</th>
                      <th style="width: 160px;">Unit Cost (PKR)</th>
                      <th style="width: 160px;">Line Total</th>
                      <th style="width: 60px;"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, idx) in newPurchItems" :key="idx">
                      <td>
                        <select class="form-select form-select-sm" v-model="item.product_id" @change="onPurchItemProductChange(item)">
                          <option :value="null">Select Product...</option>
                          <option v-for="p in retailStore.products" :key="p.id" :value="p.id">{{ p.name }} (Cost: {{ p.cost_price || 0 }})</option>
                        </select>
                      </td>
                      <td>
                        <input type="number" min="1" class="form-control form-control-sm text-center" v-model.number="item.quantity" />
                      </td>
                      <td>
                        <input type="number" min="0" step="0.01" class="form-control form-control-sm text-end" v-model.number="item.unit_cost" />
                      </td>
                      <td class="text-end fw-semibold">
                        PKR {{ ((item.quantity || 0) * (item.unit_cost || 0)).toLocaleString() }}
                      </td>
                      <td class="text-center">
                        <button type="button" class="btn btn-sm btn-link text-danger p-0" @click="removeNewPurchItem(idx)" :disabled="newPurchItems.length <= 1">
                          <i class="bi bi-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- TOTALS & PAYMENT SECTION -->
            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-secondary">Freight / Shipping Cost (PKR)</label>
                <input type="number" min="0" class="form-control" v-model.number="newPurchShipping" />
              </div>
              <div class="col-md-6">
                <div class="card border bg-light p-3 rounded-3 text-end">
                  <div class="text-muted small">Total Purchase Value</div>
                  <div class="fs-4 fw-bold text-dark">PKR {{ newPurchGrandTotal.toLocaleString() }}</div>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
              <button type="button" class="btn btn-light rounded-3 px-4" @click="purchasesIsNewPurchase = false">Cancel</button>
              <button type="submit" class="btn btn-dark rounded-3 px-4 fw-semibold" :disabled="newPurchIsSubmitting">
                <span v-if="newPurchIsSubmitting" class="spinner-border spinner-border-sm me-1"></span>
                Save &amp; Receive Purchase
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ====================================================
           VIEW B: DETAILED SUPPLIER 360° PROFILE & LEDGER
           (When a supplier is selected)
      ==================================================== -->
      <div v-else-if="selectedSupplierProfile" class="supplier-detail-view mb-4">
        <!-- TOP BREADCRUMB / BACK BAR -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
          <button class="btn btn-sm btn-light border rounded-3 fw-semibold px-3 py-2 shadow-sm d-flex align-items-center gap-2" @click="closeSupplierProfile" type="button">
            <i class="bi bi-arrow-left"></i> Back to All Suppliers
          </button>
          <div class="d-flex align-items-center gap-2">
            <span class="text-muted small font-monospace">Supplier Code: {{ selectedSupplierProfile.code || ('SUP-' + selectedSupplierProfile.id) }}</span>
          </div>
        </div>

        <!-- SUPPLIER HERO PROFILE CARD -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden bg-white">
          <div class="p-4 bg-white border-bottom">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
              <div class="d-flex align-items-center gap-3">
                <div class="supplier-profile-avatar" :style="getAvatarColorStyle(selectedSupplierProfile.name)">
                  {{ selectedSupplierProfile.name ? selectedSupplierProfile.name.charAt(0).toUpperCase() : 'S' }}
                </div>
                <div>
                  <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <h2 class="fw-bold text-dark m-0 fs-3">{{ selectedSupplierProfile.name }}</h2>
                    <span :class="['badge rounded-pill px-2.5 py-1 text-uppercase font-monospace', (Number(selectedSupplierProfile.balance) || 0) > 0 ? 'bg-danger text-white' : 'bg-success text-white']" style="font-size: 11px;">
                      {{ (Number(selectedSupplierProfile.balance) || 0) > 0 ? 'Payable Due' : 'Settled / No Dues' }}
                    </span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 11px;">
                      Tier 1 Vendor
                    </span>
                  </div>
                  <div class="d-flex flex-wrap gap-3 text-muted small">
                    <span v-if="selectedSupplierProfile.phone"><i class="bi bi-telephone me-1 text-primary"></i><a :href="'tel:' + selectedSupplierProfile.phone" class="text-decoration-none text-muted font-monospace">{{ selectedSupplierProfile.phone }}</a></span>
                    <span v-if="selectedSupplierProfile.email"><i class="bi bi-envelope me-1 text-primary"></i><a :href="'mailto:' + selectedSupplierProfile.email" class="text-decoration-none text-muted">{{ selectedSupplierProfile.email }}</a></span>
                    <span v-if="selectedSupplierProfile.address"><i class="bi bi-geo-alt me-1 text-primary"></i>{{ selectedSupplierProfile.address }}</span>
                  </div>
                </div>
              </div>

              <!-- ACTION BUTTONS -->
              <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-success btn-sm rounded-3 fw-semibold px-3 py-2 shadow-sm d-flex align-items-center gap-2" @click="openPaymentModal(selectedSupplierProfile.id)" type="button">
                  <i class="bi bi-credit-card"></i> Record Payment
                </button>
                <button class="btn btn-dark btn-sm rounded-3 fw-semibold px-3 py-2 shadow-sm d-flex align-items-center gap-2" @click="startNewPurchaseForSupplier(selectedSupplierProfile.id)" type="button">
                  <i class="bi bi-plus-lg"></i> New Purchase
                </button>
                <button class="btn btn-light border btn-sm rounded-3 fw-semibold px-3 py-2 shadow-sm d-flex align-items-center gap-2" @click="openEditSupplierModal(selectedSupplierProfile)" type="button">
                  <i class="bi bi-pencil"></i> Edit
                </button>
                <button class="btn btn-light border text-danger btn-sm rounded-3 fw-semibold px-3 py-2 shadow-sm d-flex align-items-center gap-2" @click="deleteSupplierProfile(selectedSupplierProfile.id)" type="button">
                  <i class="bi bi-trash"></i> Delete
                </button>
              </div>
            </div>
          </div>

          <!-- 4 KPI SUMMARY CARDS FOR THIS SUPPLIER -->
          <div class="row g-0 border-top bg-light">
            <div class="col-md-3 border-end p-3 text-center">
              <span class="text-muted small fw-semibold text-uppercase d-block">Total Purchases Billed</span>
              <div class="fs-4 fw-bold text-dark mt-1">PKR {{ (Number(selectedSupplierProfile.total_purchases) || 0).toLocaleString() }}</div>
              <span class="text-muted small" style="font-size: 11px;">All recorded orders</span>
            </div>
            <div class="col-md-3 border-end p-3 text-center" style="background: rgba(239, 68, 68, 0.04);">
              <span class="text-danger small fw-bold text-uppercase d-block">Outstanding Balance (Payable)</span>
              <div class="fs-4 fw-bold text-danger mt-1">PKR {{ (Number(selectedSupplierProfile.balance) || 0).toLocaleString() }}</div>
              <span class="text-muted small" style="font-size: 11px;">Current pending liability</span>
            </div>
            <div class="col-md-3 border-end p-3 text-center" style="background: rgba(16, 185, 129, 0.04);">
              <span class="text-success small fw-bold text-uppercase d-block">Total Settled / Paid</span>
              <div class="fs-4 fw-bold text-success mt-1">
                PKR {{ ((Number(selectedSupplierProfile.total_purchases) || 0) - (Number(selectedSupplierProfile.balance) || 0)).toLocaleString() }}
              </div>
              <span class="text-muted small" style="font-size: 11px;">Cleared disbursements</span>
            </div>
            <div class="col-md-3 p-3 text-center">
              <span class="text-muted small fw-semibold text-uppercase d-block">Performance Rating</span>
              <div class="fs-4 fw-bold text-primary mt-1">94% &bull; Grade A+</div>
              <span class="text-muted small" style="font-size: 11px;">Healthy fulfillment score</span>
            </div>
          </div>
        </div>

        <!-- INNER TABS FOR SELECTED SUPPLIER -->
        <div class="supplier-panel-card p-4">
          <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
              <button 
                type="button" 
                class="btn btn-sm px-3 py-2 rounded-3 fw-semibold"
                :class="profileDetailTab === 'purchases' ? 'btn-dark' : 'btn-light text-muted border'"
                @click="profileDetailTab = 'purchases'"
              >
                <i class="bi bi-receipt me-1"></i> Purchases &amp; Invoices
                <span class="badge bg-secondary rounded-pill ms-1">{{ supplierPurchases.length }}</span>
              </button>

              <button 
                type="button" 
                class="btn btn-sm px-3 py-2 rounded-3 fw-semibold"
                :class="profileDetailTab === 'payments' ? 'btn-dark' : 'btn-light text-muted border'"
                @click="profileDetailTab = 'payments'"
              >
                <i class="bi bi-cash-stack me-1"></i> Payment History &amp; Settlements
                <span class="badge bg-secondary rounded-pill ms-1">{{ supplierPayments.length }}</span>
              </button>

              <button 
                type="button" 
                class="btn btn-sm px-3 py-2 rounded-3 fw-semibold"
                :class="profileDetailTab === 'performance' ? 'btn-dark' : 'btn-light text-muted border'"
                @click="profileDetailTab = 'performance'"
              >
                <i class="bi bi-graph-up-arrow me-1"></i> Performance Scorecard
              </button>
            </div>

            <div>
              <button v-if="profileDetailTab === 'purchases'" class="btn btn-sm btn-primary rounded-3" @click="startNewPurchaseForSupplier(selectedSupplierProfile.id)" type="button">
                <i class="bi bi-plus-lg me-1"></i> New Purchase
              </button>
              <button v-if="profileDetailTab === 'payments'" class="btn btn-sm btn-primary rounded-3" @click="openPaymentModal(selectedSupplierProfile.id)" type="button">
                <i class="bi bi-wallet2 me-1"></i> Record Payment
              </button>
            </div>
          </div>

          <!-- TAB 1: PURCHASES LIST FOR THIS SUPPLIER -->
          <div v-if="profileDetailTab === 'purchases'">
            <div v-if="loadingPurchases" class="text-center py-5 text-muted">
              <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
              <div>Loading purchases...</div>
            </div>
            <div v-else-if="supplierPurchases.length === 0" class="text-center py-5 text-muted">
              <i class="bi bi-receipt fs-1 text-muted opacity-50 d-block mb-2"></i>
              <h6>No Purchases Recorded Yet</h6>
              <p class="small text-muted mb-3">Record purchases from this supplier to populate inventory receiving ledgers.</p>
              <button class="btn btn-sm btn-dark rounded-3 px-3" @click="startNewPurchaseForSupplier(selectedSupplierProfile.id)" type="button">
                <i class="bi bi-plus-lg me-1"></i> Create First Purchase
              </button>
            </div>
            <div v-else class="table-responsive">
              <table class="table table-hover align-middle m-0">
                <thead class="bg-light">
                  <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th class="text-end">Total Amount</th>
                    <th class="text-end">Amount Paid</th>
                    <th class="text-end">Balance Due</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="purch in supplierPurchases" :key="purch.id">
                    <td class="fw-bold font-monospace text-primary">#{{ purch.invoice_number || purch.id }}</td>
                    <td class="text-muted small">{{ purch.purchase_date || purch.created_at?.slice(0, 10) }}</td>
                    <td>{{ (purch.items || []).length || 1 }} items</td>
                    <td class="text-end fw-semibold">PKR {{ (Number(purch.total_amount) || 0).toLocaleString() }}</td>
                    <td class="text-end text-success fw-semibold">PKR {{ (Number(purch.paid_amount) || 0).toLocaleString() }}</td>
                    <td class="text-end fw-bold" :class="(Number(purch.due_amount) || 0) > 0 ? 'text-danger' : 'text-success'">
                      PKR {{ (Number(purch.due_amount) || 0).toLocaleString() }}
                    </td>
                    <td class="text-center">
                      <span :class="['badge rounded-pill px-2 py-0.5', (Number(purch.due_amount) || 0) > 0 ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-success-subtle text-success border border-success-subtle']" style="font-size: 11px;">
                        {{ (Number(purch.due_amount) || 0) > 0 ? 'Outstanding' : 'Paid' }}
                      </span>
                    </td>
                    <td class="text-center">
                      <button v-if="(Number(purch.due_amount) || 0) > 0" class="btn btn-sm btn-outline-success rounded-2 px-2 py-0.5 fw-semibold" style="font-size: 11.5px;" @click="openPaymentModal(selectedSupplierProfile.id, purch)" type="button">
                        Pay Due
                      </button>
                      <span v-else class="text-muted small"><i class="bi bi-check2 text-success"></i> Settled</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- TAB 2: PAYMENTS LIST FOR THIS SUPPLIER -->
          <div v-else-if="profileDetailTab === 'payments'">
            <div v-if="loadingPayments" class="text-center py-5 text-muted">
              <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
              <div>Loading payments...</div>
            </div>
            <div v-else-if="supplierPayments.length === 0" class="text-center py-5 text-muted">
              <i class="bi bi-cash-stack fs-1 text-muted opacity-50 d-block mb-2"></i>
              <h6>No Payments Recorded Yet</h6>
              <p class="small text-muted mb-3">Record disbursements to clear payables owed to this supplier.</p>
              <button class="btn btn-sm btn-success rounded-3 px-3" @click="openPaymentModal(selectedSupplierProfile.id)" type="button">
                <i class="bi bi-credit-card me-1"></i> Record Payment
              </button>
            </div>
            <div v-else class="table-responsive">
              <table class="table table-hover align-middle m-0">
                <thead class="bg-light">
                  <tr>
                    <th>Receipt #</th>
                    <th>Date</th>
                    <th class="text-end">Amount Paid</th>
                    <th>Payment Method</th>
                    <th>Account</th>
                    <th>Notes</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="pay in supplierPayments" :key="pay.id">
                    <td class="fw-bold font-monospace text-primary">#{{ pay.payment_number || pay.receipt_number || pay.id }}</td>
                    <td class="text-muted small">{{ pay.payment_date || pay.created_at?.slice(0, 10) }}</td>
                    <td class="text-end fw-bold text-success">PKR {{ (Number(pay.amount) || 0).toLocaleString() }}</td>
                    <td><span class="badge bg-light text-dark border text-uppercase">{{ pay.payment_method || 'Cash' }}</span></td>
                    <td class="text-muted small">{{ pay.financial_account?.name || 'Main Cash' }}</td>
                    <td class="text-muted small text-truncate" style="max-width: 150px;">{{ pay.notes || '-' }}</td>
                    <td class="text-center">
                      <span :class="['badge rounded-pill px-2 py-0.5', pay.status === 'reversed' ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success']" style="font-size: 11px;">
                        {{ pay.status === 'reversed' ? 'Reversed' : 'Settled' }}
                      </span>
                    </td>
                    <td class="text-end">
                      <button v-if="pay.status !== 'reversed'" class="btn btn-sm btn-outline-danger rounded-2 px-2 py-0.5" style="font-size: 11.5px;" @click="openReverseModal(pay)" type="button">
                        Reverse
                      </button>
                      <span v-else class="text-muted small">Void</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- TAB 3: PERFORMANCE BENCHMARK FOR THIS SUPPLIER -->
          <div v-else-if="profileDetailTab === 'performance'">
            <div class="row g-4">
              <div class="col-md-4">
                <div class="card border rounded-3 p-3 bg-light text-center h-100">
                  <div class="text-muted small fw-semibold text-uppercase mb-1">On-Time Delivery Rate</div>
                  <div class="fs-2 fw-bold text-success mb-2">95%</div>
                  <div class="progress mb-2" style="height: 6px;">
                    <div class="progress-bar bg-success" style="width: 95%;"></div>
                  </div>
                  <span class="small text-muted">Consistent delivery within contractual window</span>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card border rounded-3 p-3 bg-light text-center h-100">
                  <div class="text-muted small fw-semibold text-uppercase mb-1">Product Quality Score</div>
                  <div class="fs-2 fw-bold text-primary mb-2">93%</div>
                  <div class="progress mb-2" style="height: 6px;">
                    <div class="progress-bar bg-primary" style="width: 93%;"></div>
                  </div>
                  <span class="small text-muted">Zero defective stock return claims filed</span>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card border rounded-3 p-3 bg-light text-center h-100">
                  <div class="text-muted small fw-semibold text-uppercase mb-1">Price Stability &amp; Compliance</div>
                  <div class="fs-2 fw-bold text-info mb-2">89%</div>
                  <div class="progress mb-2" style="height: 6px;">
                    <div class="progress-bar bg-info" style="width: 89%;"></div>
                  </div>
                  <span class="small text-muted">Reliable wholesale price adherence</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ====================================================
           VIEW C: MAIN SUPPLIERS MASTER DIRECTORY
           (Default view when no supplier is selected)
      ==================================================== -->
      <div v-else class="main-suppliers-hub">
        <!-- PAGE HEADER -->
        <div class="supplier-topbar">
          <div>
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="supplier-badge-pill">Procurement &amp; Supply Chain</span>
              <span class="text-muted small">&bull; Directory &amp; Accounts</span>
            </div>
            <h1 class="supplier-main-title">Suppliers &amp; Procurement Hub</h1>
            <p class="supplier-main-sub">
              Manage supplier relationships, procurement orders, payables debt, and settlement ledgers in one place.
            </p>
          </div>

          <div class="d-flex align-items-center gap-2 flex-wrap">
            <button class="btn btn-outline-primary btn-sm rounded-3 fw-semibold px-3 shadow-sm d-flex align-items-center gap-2" @click="openPaymentModal()" type="button" style="height: 38px;">
              <i class="bi bi-credit-card"></i> Record Payment
            </button>
            <button class="btn btn-dark btn-sm rounded-3 fw-semibold px-3 shadow-sm d-flex align-items-center gap-2" @click="openNewPurchaseForm()" type="button" style="height: 38px;">
              <i class="bi bi-plus-lg"></i> New Purchase
            </button>
            <button class="btn btn-primary btn-sm rounded-3 fw-semibold px-3 shadow-sm d-flex align-items-center gap-2" @click="openAddSupplierModal" type="button" style="height: 38px;">
              <i class="bi bi-building-add"></i> Add Supplier
            </button>
          </div>
        </div>

        <!-- 5 KPI METRIC CARDS -->
        <div class="supplier-kpi-grid mb-4">
          <div class="supplier-kpi-box">
            <div class="supplier-kpi-icon bg-primary-subtle text-primary">
              <i class="bi bi-bag-check"></i>
            </div>
            <div class="supplier-kpi-data">
              <span class="supplier-kpi-label">Total Purchases</span>
              <div class="supplier-kpi-num">PKR {{ totalPurchases.toLocaleString() }}</div>
              <span class="supplier-kpi-note text-success fw-medium">&bull; Cumulative Procurement</span>
            </div>
          </div>

          <div class="supplier-kpi-box">
            <div class="supplier-kpi-icon bg-info-subtle text-info">
              <i class="bi bi-people"></i>
            </div>
            <div class="supplier-kpi-data">
              <span class="supplier-kpi-label">Active Suppliers</span>
              <div class="supplier-kpi-num">{{ suppliers.length }}</div>
              <span class="supplier-kpi-note text-success fw-medium">&bull; Vendor Network</span>
            </div>
          </div>

          <div class="supplier-kpi-box">
            <div class="supplier-kpi-icon bg-danger-subtle text-danger">
              <i class="bi bi-credit-card-2-back"></i>
            </div>
            <div class="supplier-kpi-data">
              <span class="supplier-kpi-label">Total Payables</span>
              <div class="supplier-kpi-num text-danger">PKR {{ totalPayables.toLocaleString() }}</div>
              <span class="supplier-kpi-note text-danger-emphasis">Outstanding liabilities</span>
            </div>
          </div>

          <div class="supplier-kpi-box">
            <div class="supplier-kpi-icon bg-warning-subtle text-warning">
              <i class="bi bi-receipt"></i>
            </div>
            <div class="supplier-kpi-data">
              <span class="supplier-kpi-label">Purchase Orders</span>
              <div class="supplier-kpi-num">{{ totalPurchaseOrders }}</div>
              <span class="supplier-kpi-note text-muted">All vendor orders</span>
            </div>
          </div>

          <div class="supplier-kpi-box">
            <div class="supplier-kpi-icon bg-success-subtle text-success">
              <i class="bi bi-calculator"></i>
            </div>
            <div class="supplier-kpi-data">
              <span class="supplier-kpi-label">Average Purchase</span>
              <div class="supplier-kpi-num">PKR {{ averagePurchase.toLocaleString() }}</div>
              <span class="supplier-kpi-note text-muted">Per order volume</span>
            </div>
          </div>
        </div>

        <!-- MASTER SUPPLIERS DIRECTORY TABLE CARD -->
        <div class="supplier-panel-card">
          <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-3">
            <!-- FILTER PILLS -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <button 
                type="button" 
                class="btn btn-sm rounded-pill px-3 fw-semibold transition"
                :class="supplierFilter === 'all' ? 'btn-primary' : 'btn-light border text-muted'"
                @click="supplierFilter = 'all'"
              >
                All Suppliers ({{ suppliers.length }})
              </button>
              <button 
                type="button" 
                class="btn btn-sm rounded-pill px-3 fw-semibold transition"
                :class="supplierFilter === 'debt' ? 'btn-danger' : 'btn-light border text-muted'"
                @click="supplierFilter = 'debt'"
              >
                Has Payables / Debt ({{ suppliersWithDebtCount }})
              </button>
              <button 
                type="button" 
                class="btn btn-sm rounded-pill px-3 fw-semibold transition"
                :class="supplierFilter === 'settled' ? 'btn-success' : 'btn-light border text-muted'"
                @click="supplierFilter = 'settled'"
              >
                Fully Settled ({{ suppliersSettledCount }})
              </button>
            </div>

            <!-- SEARCH BAR -->
            <div class="position-relative" style="min-width: 280px;">
              <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
              <input 
                type="text" 
                class="form-control form-control-sm rounded-pill ps-5 bg-light border-0" 
                placeholder="Search by name, phone, or address..."
                v-model="searchQuery"
                style="height: 38px;"
              />
            </div>
          </div>

          <!-- TABLE -->
          <div class="table-responsive">
            <table class="table table-hover align-middle m-0 supplier-table">
              <thead>
                <tr>
                  <th style="padding-left: 20px;">Supplier Details</th>
                  <th>Contact Info</th>
                  <th class="text-end">Total Purchased</th>
                  <th class="text-end">Payable Balance</th>
                  <th class="text-center">Performance</th>
                  <th class="text-end" style="padding-right: 20px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="filteredSuppliers.length === 0">
                  <td colspan="6" class="text-center py-5 text-muted">
                    <i class="bi bi-truck fs-1 text-muted opacity-50 d-block mb-2"></i>
                    <h6>No Suppliers Found</h6>
                    <p class="small text-muted mb-0">Try adjusting your search criteria or add a new supplier.</p>
                  </td>
                </tr>

                <tr v-for="s in filteredSuppliers" :key="s.id" class="supplier-row">
                  <td style="padding-left: 20px;">
                    <div class="d-flex align-items-center gap-3">
                      <div class="supplier-avatar-circle" :style="getAvatarColorStyle(s.name)">
                        {{ s.name ? s.name.charAt(0).toUpperCase() : 'S' }}
                      </div>
                      <div>
                        <div class="fw-bold text-dark fs-6">{{ s.name }}</div>
                        <span class="text-muted small font-monospace">{{ s.code || ('SUP-' + s.id) }}</span>
                      </div>
                    </div>
                  </td>

                  <td>
                    <div class="small">
                      <div v-if="s.phone"><i class="bi bi-telephone text-muted me-1"></i>{{ s.phone }}</div>
                      <div v-if="s.email" class="text-muted"><i class="bi bi-envelope text-muted me-1"></i>{{ s.email }}</div>
                    </div>
                  </td>

                  <td class="text-end">
                    <div class="fw-bold text-dark">PKR {{ (Number(s.total_purchases) || 0).toLocaleString() }}</div>
                    <small class="text-muted">{{ s.purchases_count || 0 }} orders</small>
                  </td>

                  <td class="text-end">
                    <span v-if="(Number(s.balance) || 0) > 0" class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1 fw-bold fs-6">
                      PKR {{ (Number(s.balance) || 0).toLocaleString() }}
                    </span>
                    <span v-else class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5 small">
                      <i class="bi bi-check2"></i> Settled
                    </span>
                  </td>

                  <td class="text-center">
                    <div class="d-inline-flex flex-column align-items-center">
                      <span class="badge bg-light text-dark border px-2 py-0.5 rounded-pill small fw-semibold">
                        94% &bull; Grade A
                      </span>
                    </div>
                  </td>

                  <td class="text-end" style="padding-right: 20px;">
                    <div class="d-inline-flex align-items-center gap-2">
                      <button 
                        class="btn btn-sm btn-primary rounded-3 fw-semibold px-3 py-1 shadow-sm d-flex align-items-center gap-1"
                        @click="openSupplierProfile(s)" 
                        type="button"
                        title="View Full Profile, Ledger & History"
                      >
                        <i class="bi bi-eye"></i> View Profile
                      </button>

                      <button 
                        class="btn btn-sm btn-outline-success rounded-3 fw-semibold px-2 py-1 shadow-sm"
                        @click="openPaymentModal(s.id)" 
                        type="button"
                        title="Record Payment"
                      >
                        Pay
                      </button>

                      <button 
                        class="btn btn-sm btn-light border rounded-3 px-2 py-1"
                        @click="openEditSupplierModal(s)" 
                        type="button"
                        title="Edit Supplier"
                      >
                        <i class="bi bi-pencil"></i>
                      </button>

                      <button 
                        class="btn btn-sm btn-light border text-danger rounded-3 px-2 py-1"
                        @click="deleteSupplierProfile(s.id)" 
                        type="button"
                        title="Delete Supplier"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>

    <!-- ====================================================
         ADD / EDIT SUPPLIER MODAL
    ===================================================== -->
    <div v-if="showSupplierModal" class="supplier-modal-overlay" @click.self="showSupplierModal = false">
      <div class="supplier-modal-card">
        <div class="supplier-modal-header">
          <div class="supplier-modal-header-left">
            <div class="supplier-modal-icon"><i class="bi bi-building"></i></div>
            <div class="supplier-modal-header-text">
              <h3 class="supplier-modal-title">{{ isEditingSupplier ? 'Edit Supplier' : 'Add New Supplier' }}</h3>
              <p class="supplier-modal-subtitle">{{ isEditingSupplier ? 'Update supplier contact details and address.' : 'Create a new supplier account in HBOS.' }}</p>
            </div>
          </div>
          <button type="button" class="supplier-modal-close" @click="showSupplierModal = false" aria-label="Close">&#x2715;</button>
        </div>

        <div class="supplier-modal-body">
          <form @submit.prevent="saveSupplierForm">
            <div class="mb-3">
              <label class="form-label fw-semibold small text-secondary">Supplier Code</label>
              <input type="text" class="form-control" v-model="supplierForm.code" readonly style="background:#f1f5f9; cursor:not-allowed;" />
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-secondary">Supplier Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" v-model="supplierForm.name" placeholder="e.g. Al-Madina Traders" required />
            </div>

            <div class="row g-2 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-secondary">Phone Number <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="supplierForm.phone" placeholder="0300-1234567" required />
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-secondary">Email Address</label>
                <input type="email" class="form-control" v-model="supplierForm.email" placeholder="vendor@domain.com" />
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-secondary">Business Address</label>
              <textarea class="form-control" v-model="supplierForm.address" rows="2" placeholder="Street, City, Industrial Area..."></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
              <button type="button" class="btn btn-light rounded-3 px-3" @click="showSupplierModal = false">Cancel</button>
              <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold" :disabled="isSavingSupplier">
                <span v-if="isSavingSupplier" class="spinner-border spinner-border-sm me-1"></span>
                {{ isEditingSupplier ? 'Save Changes' : 'Create Supplier' }}
              </button>
            </div>
          </form>
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
            <div class="supplier-modal-icon"><i class="bi bi-credit-card"></i></div>
            <div class="supplier-modal-header-text">
              <h3 class="supplier-modal-title">Record Supplier Payment</h3>
              <p class="supplier-modal-subtitle">Post cash or bank disbursement to clear supplier liability.</p>
            </div>
          </div>
          <button type="button" class="supplier-modal-close" @click="showPaymentModal = false" aria-label="Close">&#x2715;</button>
        </div>

        <div class="supplier-modal-body">
          <form @submit.prevent="submitPayment">
            <div class="mb-3">
              <label class="form-label fw-semibold small text-secondary">Supplier <span class="text-danger">*</span></label>
              <select class="form-select" v-model="paymentForm.supplier_id" @change="onModalSupplierChange" required>
                <option :value="null" disabled>Choose a supplier...</option>
                <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }} (Outstanding: PKR {{ (Number(s.balance) || 0).toLocaleString() }})</option>
              </select>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-secondary">Amount (PKR) <span class="text-danger">*</span></label>
                <input type="number" min="1" step="0.01" class="form-control" v-model.number="paymentForm.amount" required />
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-secondary">Payment Method <span class="text-danger">*</span></label>
                <select class="form-select" v-model="paymentForm.payment_method" required>
                  <option value="cash">Cash</option>
                  <option value="bank_transfer">Bank Transfer</option>
                  <option value="cheque">Cheque</option>
                  <option value="online">Online / Card</option>
                </select>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-secondary">Deduct From Financial Account</label>
              <select class="form-select" v-model="paymentForm.financial_account_id">
                <option :value="null">Default Store Cash Account</option>
                <option v-for="a in financialAccounts" :key="a.id" :value="a.id">{{ a.name }} (Bal: PKR {{ (Number(a.current_balance) || 0).toLocaleString() }})</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-secondary">Payment Reference / Notes</label>
              <input type="text" class="form-control" v-model="paymentForm.notes" placeholder="e.g. Cheque #4421 / Wholesale settlement" />
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
              <button type="button" class="btn btn-light rounded-3 px-3" @click="showPaymentModal = false">Cancel</button>
              <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold" :disabled="isSubmittingPayment">
                <span v-if="isSubmittingPayment" class="spinner-border spinner-border-sm me-1"></span>
                Post Payment
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ====================================================
         REVERSE PAYMENT MODAL
    ===================================================== -->
    <div v-if="showReverseModal" class="supplier-modal-overlay" @click.self="showReverseModal = false">
      <div class="supplier-modal-card" style="max-width:480px;">
        <div class="supplier-modal-header">
          <div class="supplier-modal-header-left">
            <div class="supplier-modal-icon bg-danger-subtle text-danger"><i class="bi bi-arrow-counterclockwise"></i></div>
            <div class="supplier-modal-header-text">
              <h3 class="supplier-modal-title">Reverse Supplier Payment</h3>
              <p class="supplier-modal-subtitle">Undo disbursement and restore account balance.</p>
            </div>
          </div>
          <button type="button" class="supplier-modal-close" @click="showReverseModal = false" aria-label="Close">&#x2715;</button>
        </div>

        <div class="supplier-modal-body" v-if="paymentToReverse">
          <p class="small text-muted mb-3">
            Are you sure you want to reverse payment <strong class="text-dark">#{{ paymentToReverse.payment_number || paymentToReverse.id }}</strong> of <strong class="text-success">PKR {{ (Number(paymentToReverse.amount) || 0).toLocaleString() }}</strong>?
          </p>
          <div class="d-flex justify-content-end gap-2 pt-3 border-top">
            <button type="button" class="btn btn-light rounded-3 px-3" @click="showReverseModal = false">Cancel</button>
            <button type="button" class="btn btn-danger rounded-3 px-4 fw-semibold" :disabled="isReversingPayment" @click="confirmReversePayment">
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
import { onMounted, ref, watch, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSuppliersStore } from '@/stores/suppliers';
import { usePurchasesStore } from '@/stores/purchases';
import { useRetailStore } from '@/stores/retail';
import api from '@/api';

const store = useSuppliersStore();
const purchasesStore = usePurchasesStore();
const retailStore = useRetailStore();

const route = useRoute();
const router = useRouter();

const suppliers = computed(() => store.suppliers || []);

// ============================================================
// MAIN DIRECTORY & SELECTION STATE
// ============================================================
const selectedSupplierProfile = ref(null);
const profileDetailTab = ref('purchases'); // 'purchases' | 'payments' | 'performance'
const searchQuery = ref('');
const supplierFilter = ref('all'); // 'all' | 'debt' | 'settled'

const suppliersWithDebtCount = computed(() => {
  return suppliers.value.filter(s => (Number(s.balance) || 0) > 0).length;
});

const suppliersSettledCount = computed(() => {
  return suppliers.value.filter(s => (Number(s.balance) || 0) <= 0).length;
});

const filteredSuppliers = computed(() => {
  let list = suppliers.value || [];

  if (supplierFilter.value === 'debt') {
    list = list.filter(s => (Number(s.balance) || 0) > 0);
  } else if (supplierFilter.value === 'settled') {
    list = list.filter(s => (Number(s.balance) || 0) <= 0);
  }

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase().trim();
    list = list.filter(s =>
      (s.name && s.name.toLowerCase().includes(q)) ||
      (s.phone && s.phone.includes(q)) ||
      (s.email && s.email.toLowerCase().includes(q)) ||
      (s.address && s.address.toLowerCase().includes(q)) ||
      (s.code && s.code.toLowerCase().includes(q))
    );
  }
  return list;
});

// Top Metrics
const totalPurchases = computed(() => {
  return suppliers.value.reduce((sum, s) => sum + (Number(s.total_purchases) || 0), 0);
});

const totalPayables = computed(() => {
  return suppliers.value.reduce((sum, s) => sum + (Number(s.balance) || 0), 0);
});

const totalPurchaseOrders = computed(() => {
  return suppliers.value.reduce((sum, s) => sum + (Number(s.purchases_count) || 0), 0);
});

const averagePurchase = computed(() => {
  const orders = totalPurchaseOrders.value;
  return orders > 0 ? Math.round(totalPurchases.value / orders) : 0;
});

// ============================================================
// SUPPLIER 360° PROFILE VIEW HANDLERS
// ============================================================
const supplierPurchases = ref([]);
const supplierPayments = ref([]);
const loadingPurchases = ref(false);
const loadingPayments = ref(false);

const openSupplierProfile = async (supplier) => {
  selectedSupplierProfile.value = supplier;
  profileDetailTab.value = 'purchases';
  router.replace({ query: { ...route.query, id: supplier.id } });
  await Promise.all([
    fetchSupplierPurchases(supplier.id),
    fetchSupplierPayments(supplier.id)
  ]);
};

const closeSupplierProfile = () => {
  selectedSupplierProfile.value = null;
  supplierPurchases.value = [];
  supplierPayments.value = [];
  const q = { ...route.query };
  delete q.id;
  delete q.supplier_id;
  router.replace({ query: q });
};

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

// ============================================================
// ADD / EDIT SUPPLIER MODAL
// ============================================================
const showSupplierModal = ref(false);
const isSavingSupplier = ref(false);
const isEditingSupplier = ref(false);
const successAlertMessage = ref('');
let successAlertTimer = null;

const triggerSuccessAlert = (msg) => {
  successAlertMessage.value = msg;
  if (successAlertTimer) clearTimeout(successAlertTimer);
  successAlertTimer = setTimeout(() => { successAlertMessage.value = ''; }, 4500);
};

const generateUniqueSupplierCode = () => {
  const existing = (suppliers.value || []).map(s => s.code || ('SUP-' + String(s.id).padStart(4, '0')));
  let maxNum = 0;
  existing.forEach(code => {
    const match = code && code.match(/SUP-(\d+)/i);
    if (match) {
      const num = parseInt(match[1], 10);
      if (num > maxNum) maxNum = num;
    }
  });
  if (maxNum === 0) maxNum = (suppliers.value || []).length;
  return 'SUP-' + String(maxNum + 1).padStart(4, '0');
};

const supplierForm = ref({ id: null, code: '', name: '', phone: '', email: '', address: '' });

const openAddSupplierModal = () => {
  isEditingSupplier.value = false;
  supplierForm.value = { id: null, code: generateUniqueSupplierCode(), name: '', phone: '', email: '', address: '' };
  showSupplierModal.value = true;
};

const openEditSupplierModal = (supplier) => {
  isEditingSupplier.value = true;
  supplierForm.value = {
    id: supplier.id,
    code: supplier.code || ('SUP-' + String(supplier.id).padStart(4, '0')),
    name: supplier.name || '',
    phone: supplier.phone || '',
    email: supplier.email || '',
    address: supplier.address || ''
  };
  showSupplierModal.value = true;
};

const saveSupplierForm = async () => {
  if (!supplierForm.value.name || !supplierForm.value.phone) {
    alert('Please provide supplier name and phone number.');
    return;
  }
  isSavingSupplier.value = true;
  try {
    if (isEditingSupplier.value) {
      const res = await store.updateSupplier(supplierForm.value.id, {
        code: supplierForm.value.code, name: supplierForm.value.name,
        phone: supplierForm.value.phone, email: supplierForm.value.email, address: supplierForm.value.address
      });
      if (res.success) {
        await store.fetchSuppliers();
        if (selectedSupplierProfile.value && selectedSupplierProfile.value.id === supplierForm.value.id) {
          const updated = suppliers.value.find(s => s.id === supplierForm.value.id);
          if (updated) selectedSupplierProfile.value = updated;
        }
        showSupplierModal.value = false;
        triggerSuccessAlert('Supplier updated successfully!');
      } else {
        alert(res.message || 'Failed to update supplier');
      }
    } else {
      const res = await store.addSupplier({
        code: supplierForm.value.code, name: supplierForm.value.name,
        phone: supplierForm.value.phone, email: supplierForm.value.email, address: supplierForm.value.address
      });
      if (res.success) {
        await store.fetchSuppliers();
        showSupplierModal.value = false;
        triggerSuccessAlert('Supplier added successfully!');
      } else {
        alert(res.message || 'Failed to add supplier');
      }
    }
  } catch (err) {
    alert('An error occurred while saving the supplier.');
  } finally {
    isSavingSupplier.value = false;
  }
};

const deleteSupplierProfile = async (id) => {
  if (!confirm('Are you sure you want to remove this supplier?')) return;
  const res = await store.deleteSupplier(id);
  if (res.success) {
    await store.fetchSuppliers();
    if (selectedSupplierProfile.value && selectedSupplierProfile.value.id === id) {
      closeSupplierProfile();
    }
    triggerSuccessAlert('Supplier deleted successfully.');
  } else {
    alert(res.message || 'Failed to delete supplier');
  }
};

// ============================================================
// RECORD PAYMENT STATE & MODAL
// ============================================================
const showPaymentModal = ref(false);
const isSubmittingPayment = ref(false);
const financialAccounts = ref([]);
const paymentForm = ref({
  supplier_id: null,
  purchase_id: null,
  amount: 0,
  payment_method: 'cash',
  financial_account_id: null,
  notes: ''
});

const fetchFinancialAccounts = async () => {
  try {
    const res = await api.get('/financial-accounts');
    financialAccounts.value = (res.data || []).filter(a => a.is_active !== false);
  } catch (err) {
    console.error('Failed to load financial accounts:', err);
  }
};

const openPaymentModal = (supplierId = null, targetPurchase = null) => {
  paymentForm.value = {
    supplier_id: supplierId || (selectedSupplierProfile.value?.id) || (suppliers.value.length > 0 ? suppliers.value[0].id : null),
    purchase_id: targetPurchase ? targetPurchase.id : null,
    amount: targetPurchase ? Number(targetPurchase.due_amount) : 0,
    payment_method: 'cash',
    financial_account_id: null,
    notes: ''
  };
  showPaymentModal.value = true;
};

const onModalSupplierChange = () => {
  paymentForm.value.purchase_id = null;
};

const submitPayment = async () => {
  if (!paymentForm.value.supplier_id || !paymentForm.value.amount || paymentForm.value.amount <= 0) {
    alert('Please select a supplier and enter a valid positive payment amount.');
    return;
  }
  isSubmittingPayment.value = true;
  try {
    const res = await api.post(`/suppliers/${paymentForm.value.supplier_id}/payments`, {
      amount: paymentForm.value.amount,
      payment_method: paymentForm.value.payment_method,
      purchase_id: paymentForm.value.purchase_id,
      financial_account_id: paymentForm.value.financial_account_id,
      notes: paymentForm.value.notes
    });

    if (res.data) {
      showPaymentModal.value = false;
      await store.fetchSuppliers();
      if (selectedSupplierProfile.value) {
        const refreshed = suppliers.value.find(s => s.id === selectedSupplierProfile.value.id);
        if (refreshed) selectedSupplierProfile.value = refreshed;
        await Promise.all([
          fetchSupplierPurchases(selectedSupplierProfile.value.id),
          fetchSupplierPayments(selectedSupplierProfile.value.id)
        ]);
      }
      triggerSuccessAlert('Supplier payment recorded successfully!');
    }
  } catch (err) {
    console.error('Payment failed:', err);
    alert(err.response?.data?.message || 'Payment submission failed.');
  } finally {
    isSubmittingPayment.value = false;
  }
};

// ============================================================
// REVERSE PAYMENT STATE & MODAL
// ============================================================
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
    showReverseModal.value = false;
    await store.fetchSuppliers();
    if (selectedSupplierProfile.value) {
      const refreshed = suppliers.value.find(s => s.id === selectedSupplierProfile.value.id);
      if (refreshed) selectedSupplierProfile.value = refreshed;
      await Promise.all([
        fetchSupplierPurchases(selectedSupplierProfile.value.id),
        fetchSupplierPayments(selectedSupplierProfile.value.id)
      ]);
    }
    triggerSuccessAlert('Payment reversed successfully.');
  } catch (err) {
    console.error('Reversal failed:', err);
    alert(err.response?.data?.message || 'Failed to reverse payment.');
  } finally {
    isReversingPayment.value = false;
  }
};

// ============================================================
// NEW PURCHASE INLINE FORM
// ============================================================
const purchasesIsNewPurchase = ref(false);
const newPurchSupplierId = ref(null);
const newPurchInvoiceNo = ref('');
const newPurchDate = ref(new Date().toISOString().slice(0, 10));
const newPurchItems = ref([{ product_id: null, quantity: 1, unit_cost: 0 }]);
const newPurchShipping = ref(0);
const newPurchIsSubmitting = ref(false);

const openNewPurchaseForm = () => {
  newPurchSupplierId.value = suppliers.value.length > 0 ? suppliers.value[0].id : null;
  newPurchInvoiceNo.value = 'INV-' + Date.now().toString().slice(-6);
  newPurchDate.value = new Date().toISOString().slice(0, 10);
  newPurchItems.value = [{ product_id: null, quantity: 1, unit_cost: 0 }];
  newPurchShipping.value = 0;
  purchasesIsNewPurchase.value = true;
};

const startNewPurchaseForSupplier = (supplierId) => {
  openNewPurchaseForm();
  newPurchSupplierId.value = supplierId;
};

const addNewPurchItem = () => {
  newPurchItems.value.push({ product_id: null, quantity: 1, unit_cost: 0 });
};

const removeNewPurchItem = (idx) => {
  if (newPurchItems.value.length > 1) {
    newPurchItems.value.splice(idx, 1);
  }
};

const onPurchItemProductChange = (item) => {
  if (!item.product_id) return;
  const p = (retailStore.products || []).find(prod => prod.id === item.product_id);
  if (p) {
    item.unit_cost = Number(p.cost_price || p.purchase_price || 0);
  }
};

const newPurchGrandTotal = computed(() => {
  const itemsTotal = (newPurchItems.value || []).reduce((sum, it) => sum + ((it.quantity || 0) * (it.unit_cost || 0)), 0);
  return itemsTotal + (Number(newPurchShipping.value) || 0);
});

const confirmNewPurchase = async () => {
  if (!newPurchSupplierId.value) {
    alert('Please select a supplier.');
    return;
  }
  const validItems = (newPurchItems.value || []).filter(it => it.product_id && it.quantity > 0);
  if (validItems.length === 0) {
    alert('Please select at least one product with quantity.');
    return;
  }

  newPurchIsSubmitting.value = true;
  try {
    const payload = {
      supplier_id: newPurchSupplierId.value,
      invoice_number: newPurchInvoiceNo.value,
      purchase_date: newPurchDate.value,
      shipping_cost: newPurchShipping.value,
      items: validItems.map(it => ({
        product_id: it.product_id,
        quantity: it.quantity,
        unit_cost: it.unit_cost,
        line_total: it.quantity * it.unit_cost
      })),
      total_amount: newPurchGrandTotal.value,
      paid_amount: 0,
      due_amount: newPurchGrandTotal.value,
      payment_status: 'unpaid',
      status: 'received'
    };

    await api.post('/purchases', payload);
    purchasesIsNewPurchase.value = false;
    await store.fetchSuppliers();
    if (selectedSupplierProfile.value) {
      await fetchSupplierPurchases(selectedSupplierProfile.value.id);
    }
    triggerSuccessAlert('Purchase invoice created and stock received successfully!');
  } catch (err) {
    console.error('Failed to create purchase:', err);
    alert('Failed to save purchase.');
  } finally {
    newPurchIsSubmitting.value = false;
  }
};

// ============================================================
// AVATAR HELPERS
// ============================================================
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
  for (let i = 0; i < (name || '').length; i++) { hash = name.charCodeAt(i) + ((hash << 5) - hash); }
  const index = Math.abs(hash) % avatarColors.length;
  const color = avatarColors[index];
  return { background: color.bg, color: color.text };
};

// ============================================================
// INITIALIZATION & URL QUERY SYNC
// ============================================================
onMounted(async () => {
  await Promise.all([
    store.fetchSuppliers(),
    fetchFinancialAccounts(),
    retailStore.fetchProducts()
  ]);

  // Check if ID is in URL
  const targetId = route.query.id || route.query.supplier_id;
  if (targetId) {
    const found = (suppliers.value || []).find(s => String(s.id) === String(targetId));
    if (found) {
      await openSupplierProfile(found);
    }
  }
});
</script>

<style scoped>
.supplierpayable-page-wrapper {
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
  margin-bottom: 20px;
}

.supplier-badge-pill {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  padding: 2px 8px;
  border-radius: 6px;
  background: #e0e7ff;
  color: #3730a3;
}

.supplier-main-title {
  font-size: 26px;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
  line-height: 1.2;
}

.supplier-main-sub {
  font-size: 13.5px;
  color: #64748b;
  margin: 4px 0 0 0;
}

/* TOP ALERT */
.supplier-top-alert {
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

/* 5 KPI STAT CARDS */
.supplier-kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
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
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
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
  flex: 1;
  min-width: 0;
}

.supplier-kpi-label {
  font-size: 11.5px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.supplier-kpi-num {
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
  margin: 2px 0;
}

.supplier-kpi-note {
  font-size: 11.5px;
}

/* PANEL CARD */
.supplier-panel-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  overflow: hidden;
}

/* TABLE STYLING */
.supplier-table thead th {
  background: #f8fafc;
  color: #475569;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 12px 14px;
  border-bottom: 1px solid #e2e8f0;
}

.supplier-table tbody td {
  padding: 14px;
  border-bottom: 1px solid #f1f5f9;
}

.supplier-row:hover {
  background-color: #f8fafc;
}

.supplier-avatar-circle {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 17px;
  font-weight: 700;
  flex-shrink: 0;
}

.supplier-profile-avatar {
  width: 60px;
  height: 60px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 26px;
  font-weight: 800;
  flex-shrink: 0;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
}

/* MODALS */
.supplier-modal-overlay {
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

.supplier-modal-card {
  background: #ffffff;
  border-radius: 16px;
  width: 100%;
  max-width: 540px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.3);
}

.supplier-modal-header {
  padding: 18px 22px;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.supplier-modal-header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.supplier-modal-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: #e0e7ff;
  color: #3730a3;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.supplier-modal-title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.supplier-modal-subtitle {
  font-size: 12px;
  color: #64748b;
  margin: 2px 0 0 0;
}

.supplier-modal-close {
  background: transparent;
  border: none;
  font-size: 20px;
  color: #94a3b8;
  cursor: pointer;
  line-height: 1;
}

.supplier-modal-close:hover {
  color: #0f172a;
}

.supplier-modal-body {
  padding: 22px;
}

.transition {
  transition: all 0.15s ease-in-out;
}
</style>
