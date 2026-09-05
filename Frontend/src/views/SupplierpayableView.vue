<template>
  <div class="supplierpayable-page-wrapper">
    <!-- TOP SUCCESS ALERT -->
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
      <!-- PAGE HEADER -->
      <div class="supplier-topbar">
        <div>
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="supplier-badge-pill">Procurement & Analytics</span>
            <span class="text-muted small">• {{ currentTabTitle }}</span>
          </div>
          <h1 class="supplier-main-title">{{ currentTabTitle }}</h1>
          <p class="supplier-main-sub">
            {{ currentTabDescription }}
          </p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap" v-if="activeTab === 'supplier-intelligence'">
          <button class="btn btn-primary btn-sm rounded-3 fw-semibold px-3 shadow-sm" @click="openAddSupplierModal" type="button" style="height: 38px;">
            <i class="bi bi-plus-lg me-1"></i> Add Supplier
          </button>
        </div>
      </div>



      <!-- ====================================================
           SECTION 1: SUPPLIER INTELLIGENCE
      ===================================================== -->
      <div v-if="activeTab === 'supplier-intelligence'" class="tab-content-panel">
        <!-- 5 METRIC CARDS -->
        <div class="supplier-kpi-grid mb-4">
          <div class="supplier-kpi-box">
            <div class="supplier-kpi-icon bg-primary-subtle text-primary">
              <i class="bi bi-bag-check"></i>
            </div>
            <div class="supplier-kpi-data">
              <span class="supplier-kpi-label">Total Purchases</span>
              <div class="supplier-kpi-num">PKR {{ totalPurchases.toLocaleString() }}</div>
              <span class="supplier-kpi-note text-success fw-medium">↗ +9.6% vs prev</span>
            </div>
          </div>

          <div class="supplier-kpi-box">
            <div class="supplier-kpi-icon bg-info-subtle text-info">
              <i class="bi bi-people"></i>
            </div>
            <div class="supplier-kpi-data">
              <span class="supplier-kpi-label">Active Suppliers</span>
              <div class="supplier-kpi-num">{{ suppliers.length || 32 }}</div>
              <span class="supplier-kpi-note text-success fw-medium">● Active Network</span>
            </div>
          </div>

          <div class="supplier-kpi-box">
            <div class="supplier-kpi-icon bg-danger-subtle text-danger">
              <i class="bi bi-credit-card-2-back"></i>
            </div>
            <div class="supplier-kpi-data">
              <span class="supplier-kpi-label">Payables</span>
              <div class="supplier-kpi-num text-danger">PKR {{ totalPayables.toLocaleString() }}</div>
              <span class="supplier-kpi-note text-danger-emphasis">Outstanding debt</span>
            </div>
          </div>

          <div class="supplier-kpi-box">
            <div class="supplier-kpi-icon bg-warning-subtle text-warning">
              <i class="bi bi-receipt"></i>
            </div>
            <div class="supplier-kpi-data">
              <span class="supplier-kpi-label">Purchase Orders</span>
              <div class="supplier-kpi-num">48</div>
              <span class="supplier-kpi-note text-muted">This quarter</span>
            </div>
          </div>

          <div class="supplier-kpi-box">
            <div class="supplier-kpi-icon bg-success-subtle text-success">
              <i class="bi bi-calculator"></i>
            </div>
            <div class="supplier-kpi-data">
              <span class="supplier-kpi-label">Average Purchase</span>
              <div class="supplier-kpi-num">PKR 13,000</div>
              <span class="supplier-kpi-note text-muted">Per order volume</span>
            </div>
          </div>
        </div>

        <!-- SUPPLIERS TABLE PANEL -->
        <div class="supplier-panel-card">
          <div class="supplier-panel-header">
            <div class="d-flex align-items-center gap-2">
              <h2 class="supplier-panel-title m-0">Suppliers Overview</h2>
              <span class="badge bg-light text-dark border px-2 py-1 rounded-pill small">{{ filteredSuppliers.length }} Suppliers</span>
            </div>

            <div class="supplier-tools-bar">
              <div class="supplier-search-input">
                <i class="bi bi-search search-icon"></i>
                <input 
                  type="search" 
                  placeholder="Search supplier name, phone..." 
                  v-model="searchQuery" 
                />
                <button v-if="searchQuery" class="clear-btn" @click="searchQuery = ''" type="button">✕</button>
              </div>

              <button 
                v-if="searchQuery" 
                class="btn btn-outline-secondary btn-sm rounded-3 px-2.5" 
                @click="searchQuery = ''" 
                title="Reset Search"
                type="button"
                style="height: 38px;"
              >
                <i class="bi bi-arrow-counterclockwise"></i> Reset
              </button>
            </div>
          </div>

          <div class="supplier-table-responsive">
            <table class="supplier-table">
              <thead>
                <tr>
                  <th>Supplier</th>
                  <th>Contact</th>
                  <th>Location</th>
                  <th>Total Purchases</th>
                  <th>Outstanding</th>
                  <th>Status</th>
                  <th style="text-align: right;">Action</th>
                </tr>
              </thead>

              <tbody>
                <tr v-if="filteredSuppliers.length === 0">
                  <td colspan="7" class="text-center py-5">
                    <div class="empty-state-wrap">
                      <i class="bi bi-building-slash text-muted" style="font-size: 2.5rem;"></i>
                      <h5 class="fw-bold text-dark mt-2 mb-1">No suppliers found</h5>
                      <p class="text-muted small mb-3">No registered suppliers match your search filter.</p>
                      <button class="btn btn-sm btn-primary rounded-3" @click="openAddSupplierModal" type="button">
                        <i class="bi bi-plus-lg me-1"></i> Add Supplier
                      </button>
                    </div>
                  </td>
                </tr>

                <tr v-for="s in filteredSuppliers" :key="s.id" class="supplier-table-row">
                  <td>
                    <div class="supplier-info-cell">
                      <div class="supplier-avatar" :style="getAvatarColorStyle(s.name)">
                        {{ s.name ? s.name.charAt(0).toUpperCase() : 'S' }}
                      </div>
                      <div>
                        <div class="supplier-name-text">{{ s.name }}</div>
                        <div class="supplier-id-text">{{ s.code || ('SUP-' + String(s.id).padStart(4, '0')) }}</div>
                      </div>
                    </div>
                  </td>

                  <td>
                    <div class="fw-semibold text-dark">{{ s.phone || 'N/A' }}</div>
                    <div class="text-muted small">{{ s.email || '—' }}</div>
                  </td>

                  <td>
                    <span class="text-muted text-truncate d-inline-block" style="max-width: 200px;" :title="s.address">
                      {{ s.address || '—' }}
                    </span>
                  </td>

                  <td>
                    <span class="fw-semibold text-dark">PKR {{ (s.total_purchases || 0).toLocaleString() }}</span>
                  </td>

                  <td>
                    <span class="fw-bold" :class="(s.balance || 0) > 0 ? 'text-danger' : 'text-dark'">
                      PKR {{ Math.abs(s.balance || 0).toLocaleString() }}
                    </span>
                  </td>

                  <td>
                    <span class="stock-pill badge-in">
                      <span class="pill-dot"></span> Active
                    </span>
                  </td>

                  <td style="text-align: right;">
                    <button class="btn btn-sm btn-outline-primary rounded-3 fw-semibold px-3 py-1" @click="$router.push('/supplier')" type="button">
                      View Profile
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ====================================================
           SECTION 2: PAYABLES & PRICE ANALYSIS
      ===================================================== -->
      <div v-if="activeTab === 'payables'" class="tab-content-panel">
        <div class="row g-4 mb-4">
          <!-- PAYABLE AGING BREAKDOWN -->
          <div class="col-12 col-xl-8">
            <div class="supplier-panel-card h-100">
              <div class="supplier-panel-header">
                <h3 class="supplier-panel-title m-0">Payable Aging Breakdown</h3>
                <span class="badge bg-light text-dark border px-2 py-1 rounded-pill small">Currency: PKR</span>
              </div>
              <div class="p-4">
                <div class="row g-3">
                  <div class="col-6 col-md-3">
                    <div class="aging-box">
                      <span class="aging-label">Current</span>
                      <div class="aging-value text-success">PKR 82k</div>
                      <span class="aging-sub text-muted">0-30 days</span>
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="aging-box">
                      <span class="aging-label">1–30 Days</span>
                      <div class="aging-value text-primary">PKR 45k</div>
                      <span class="aging-sub text-muted">Recent dues</span>
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="aging-box">
                      <span class="aging-label">31–60 Days</span>
                      <div class="aging-value text-warning">PKR 28k</div>
                      <span class="aging-sub text-muted">Approaching overdue</span>
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="aging-box">
                      <span class="aging-label">60+ Days</span>
                      <div class="aging-value text-danger">PKR 30k</div>
                      <span class="aging-sub text-danger">Critical overdue</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- PRICE INSIGHTS -->
          <div class="col-12 col-xl-4">
            <div class="supplier-panel-card h-100">
              <div class="supplier-panel-header">
                <h3 class="supplier-panel-title m-0">Procurement Price Insights</h3>
              </div>
              <div class="p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                  <div class="p-3 bg-warning-subtle text-warning rounded-3 fs-4">
                    <i class="bi bi-graph-up-arrow"></i>
                  </div>
                  <div>
                    <h4 class="fw-bold text-dark m-0">+4.2%</h4>
                    <p class="text-muted small m-0">Average supplier price inflation</p>
                  </div>
                </div>
                <p class="text-muted small mb-0">
                  Review high-cost wholesale suppliers before finalizing the upcoming inventory purchase order cycle.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- PAYABLES DETAIL TABLE -->
        <div class="supplier-panel-card">
          <div class="supplier-panel-header">
            <h3 class="supplier-panel-title m-0">Outstanding Supplier Payables</h3>
            <span class="badge bg-light text-dark border px-2 py-1 rounded-pill small">{{ suppliers.length }} Accounts</span>
          </div>

          <div class="supplier-table-responsive">
            <table class="supplier-table">
              <thead>
                <tr>
                  <th>Supplier Name</th>
                  <th>Contact</th>
                  <th>Total Billed</th>
                  <th>Amount Paid</th>
                  <th>Outstanding Due</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="s in suppliers" :key="s.id" class="supplier-table-row">
                  <td>
                    <div class="fw-bold text-dark">{{ s.name }}</div>
                    <div class="text-muted small">{{ s.code || ('SUP-' + String(s.id).padStart(4, '0')) }}</div>
                  </td>
                  <td>{{ s.phone || 'N/A' }}</td>
                  <td>PKR {{ ((s.total_purchases || 0) + (s.balance || 0)).toLocaleString() }}</td>
                  <td class="text-success fw-semibold">PKR {{ (s.total_purchases || 0).toLocaleString() }}</td>
                  <td class="text-danger fw-bold">PKR {{ (s.balance || 0).toLocaleString() }}</td>
                  <td>
                    <span class="stock-pill" :class="(s.balance || 0) > 0 ? 'badge-out' : 'badge-in'">
                      <span class="pill-dot"></span> {{ (s.balance || 0) > 0 ? 'Due' : 'Clear' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ====================================================
           SECTION 3: PURCHASE ORDERS
      ===================================================== -->
      <div v-if="activeTab === 'purchase-orders'" class="tab-content-panel">
        <div class="supplier-panel-card">
          <div class="supplier-panel-header">
            <div class="d-flex align-items-center gap-2">
              <h2 class="supplier-panel-title m-0">Purchase Orders History</h2>
              <span class="badge bg-light text-dark border px-2 py-1 rounded-pill small">4 Orders</span>
            </div>

            <button class="btn btn-primary btn-sm rounded-3 fw-semibold px-3" @click="$router.push('/purchase?new=true&from=supplier')" type="button">
              <i class="bi bi-plus-lg me-1"></i> Create Purchase Order
            </button>
          </div>

          <div class="supplier-table-responsive">
            <table class="supplier-table">
              <thead>
                <tr>
                  <th>PO Number</th>
                  <th>Supplier</th>
                  <th>Order Date</th>
                  <th>Items Count</th>
                  <th>Total Amount</th>
                  <th>Status</th>
                </tr>
              </thead>

              <tbody>
                <tr class="supplier-table-row">
                  <td><strong class="text-primary font-monospace">PO-1024</strong></td>
                  <td>
                    <div class="fw-bold text-dark">ABC Distributors</div>
                    <div class="text-muted small">Wholesale Beverages</div>
                  </td>
                  <td>12 Aug 2026</td>
                  <td>24 items</td>
                  <td class="fw-bold text-dark">PKR 85,000</td>
                  <td>
                    <span class="stock-pill badge-in">
                      <span class="pill-dot"></span> Approved
                    </span>
                  </td>
                </tr>

                <tr class="supplier-table-row">
                  <td><strong class="text-primary font-monospace">PO-1023</strong></td>
                  <td>
                    <div class="fw-bold text-dark">Global Imports Co.</div>
                    <div class="text-muted small">Imported Confectionery</div>
                  </td>
                  <td>10 Aug 2026</td>
                  <td>18 items</td>
                  <td class="fw-bold text-dark">PKR 62,000</td>
                  <td>
                    <span class="stock-pill badge-pending">
                      <span class="pill-dot"></span> Pending
                    </span>
                  </td>
                </tr>

                <tr class="supplier-table-row">
                  <td><strong class="text-primary font-monospace">PO-1022</strong></td>
                  <td>
                    <div class="fw-bold text-dark">Prime Beverages Ltd</div>
                    <div class="text-muted small">Juices & Sodas</div>
                  </td>
                  <td>04 Aug 2026</td>
                  <td>45 items</td>
                  <td class="fw-bold text-dark">PKR 140,000</td>
                  <td>
                    <span class="stock-pill badge-in">
                      <span class="pill-dot"></span> Completed
                    </span>
                  </td>
                </tr>

                <tr class="supplier-table-row">
                  <td><strong class="text-primary font-monospace">PO-1021</strong></td>
                  <td>
                    <div class="fw-bold text-dark">National Grocery Wholesale</div>
                    <div class="text-muted small">Staples & Grains</div>
                  </td>
                  <td>28 Jul 2026</td>
                  <td>60 items</td>
                  <td class="fw-bold text-dark">PKR 210,000</td>
                  <td>
                    <span class="stock-pill badge-in">
                      <span class="pill-dot"></span> Completed
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ====================================================
           SECTION 4: SUPPLIER PERFORMANCE
      ===================================================== -->
      <div v-if="activeTab === 'supplier-performance'" class="tab-content-panel">
        <!-- 3 PERFORMANCE METRIC CARDS -->
        <div class="row g-3 mb-4">
          <div class="col-12 col-md-4">
            <div class="supplier-kpi-box">
              <div class="supplier-kpi-icon bg-success-subtle text-success">
                <i class="bi bi-clock-history"></i>
              </div>
              <div class="supplier-kpi-data">
                <span class="supplier-kpi-label">On-Time Delivery</span>
                <div class="supplier-kpi-num text-success">92%</div>
                <span class="supplier-kpi-note text-success fw-semibold">Healthy fulfillment rate</span>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-4">
            <div class="supplier-kpi-box">
              <div class="supplier-kpi-icon bg-primary-subtle text-primary">
                <i class="bi bi-tags"></i>
              </div>
              <div class="supplier-kpi-data">
                <span class="supplier-kpi-label">Price Reliability</span>
                <div class="supplier-kpi-num text-primary">87%</div>
                <span class="supplier-kpi-note text-muted">Stable contractual rates</span>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-4">
            <div class="supplier-kpi-box">
              <div class="supplier-kpi-icon bg-info-subtle text-info">
                <i class="bi bi-patch-check"></i>
              </div>
              <div class="supplier-kpi-data">
                <span class="supplier-kpi-label">Supplier Quality</span>
                <div class="supplier-kpi-num text-info">94%</div>
                <span class="supplier-kpi-note text-info fw-semibold">Excellent grade standard</span>
              </div>
            </div>
          </div>
        </div>

        <!-- PERFORMANCE RANKINGS TABLE -->
        <div class="supplier-panel-card">
          <div class="supplier-panel-header">
            <h3 class="supplier-panel-title m-0">Supplier Benchmark Ratings</h3>
            <span class="badge bg-light text-dark border px-2 py-1 rounded-pill small">Scorecard</span>
          </div>

          <div class="supplier-table-responsive">
            <table class="supplier-table">
              <thead>
                <tr>
                  <th>Supplier Name</th>
                  <th>Delivery Rate</th>
                  <th>Quality Score</th>
                  <th>Pricing Compliance</th>
                  <th>Overall Score</th>
                  <th>Grade</th>
                </tr>
              </thead>

              <tbody>
                <tr class="supplier-table-row">
                  <td>
                    <div class="fw-bold text-dark">ABC Distributors</div>
                    <div class="text-muted small">Tier 1 Vendor</div>
                  </td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="progress flex-grow-1" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: 95%;"></div>
                      </div>
                      <span class="small fw-bold">95%</span>
                    </div>
                  </td>
                  <td><span class="fw-semibold">93%</span></td>
                  <td><span class="fw-semibold">89%</span></td>
                  <td><strong class="text-success fs-6">92%</strong></td>
                  <td><span class="stock-pill badge-in"><span class="pill-dot"></span> Grade A+</span></td>
                </tr>

                <tr class="supplier-table-row">
                  <td>
                    <div class="fw-bold text-dark">Global Imports Co.</div>
                    <div class="text-muted small">Tier 1 Vendor</div>
                  </td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="progress flex-grow-1" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: 91%;"></div>
                      </div>
                      <span class="small fw-bold">91%</span>
                    </div>
                  </td>
                  <td><span class="fw-semibold">95%</span></td>
                  <td><span class="fw-semibold">87%</span></td>
                  <td><strong class="text-success fs-6">91%</strong></td>
                  <td><span class="stock-pill badge-in"><span class="pill-dot"></span> Grade A</span></td>
                </tr>

                <tr class="supplier-table-row">
                  <td>
                    <div class="fw-bold text-dark">Prime Beverages Ltd</div>
                    <div class="text-muted small">Tier 2 Vendor</div>
                  </td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="progress flex-grow-1" style="height: 6px;">
                        <div class="progress-bar bg-primary" style="width: 86%;"></div>
                      </div>
                      <span class="small fw-bold">86%</span>
                    </div>
                  </td>
                  <td><span class="fw-semibold">90%</span></td>
                  <td><span class="fw-semibold">84%</span></td>
                  <td><strong class="text-primary fs-6">87%</strong></td>
                  <td><span class="stock-pill badge-in"><span class="pill-dot"></span> Grade B+</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ====================================================
         ADD SUPPLIER POPUP MODAL
    ===================================================== -->
    <div v-if="showSupplierModal" class="supplier-modal-overlay" @click.self="showSupplierModal = false">
      <div class="supplier-modal-card">
        <div class="supplier-modal-header">
          <div class="supplier-modal-header-left">
            <div class="supplier-modal-icon">
              <i class="bi bi-building-add"></i>
            </div>
            <div class="supplier-modal-header-text">
              <h3 class="supplier-modal-title">Add New Supplier</h3>
              <p class="supplier-modal-subtitle">Register a new vendor record in the system.</p>
            </div>
          </div>
          <button type="button" class="supplier-modal-close" @click="showSupplierModal = false" aria-label="Close">✕</button>
        </div>

        <div class="supplier-modal-body">
          <form @submit.prevent="saveSupplierForm">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Supplier Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="supplierForm.name" placeholder="e.g. Al-Madina Distributors" required />
              </div>

              <div class="col-md-6">
                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="supplierForm.phone" placeholder="+92 300 1234567" required />
              </div>

              <div class="col-md-6">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control" v-model="supplierForm.email" placeholder="vendor@supplier.com" />
              </div>

              <div class="col-md-6">
                <label class="form-label">Supplier Code / ID</label>
                <input 
                  type="text" 
                  class="form-control fw-bold font-monospace bg-light" 
                  v-model="supplierForm.code" 
                  readonly 
                />
              </div>

              <div class="col-12">
                <label class="form-label">Physical Address / Location</label>
                <textarea class="form-control" rows="3" v-model="supplierForm.address" placeholder="e.g. Warehouse #12, Wholesale Market, Lahore"></textarea>
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
              <button type="button" class="btn btn-light rounded-3 px-3" @click="showSupplierModal = false">Cancel</button>
              <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold" :disabled="isSavingSupplier">
                <span v-if="isSavingSupplier" class="spinner-border spinner-border-sm me-1"></span>
                Save Supplier
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSuppliersStore } from '@/stores/suppliers';

const store = useSuppliersStore();
const suppliers = computed(() => store.suppliers || []);

const route = useRoute();
const router = useRouter();

const activeTab = ref(route.query.tab || 'supplier-intelligence');
const searchQuery = ref('');

const setTab = (tab) => {
  activeTab.value = tab;
  router.push({ query: { ...route.query, tab } });
};

watch(() => route.query.tab, (newTab) => {
  if (newTab) {
    activeTab.value = newTab;
  }
});

const currentTabTitle = computed(() => {
  switch (activeTab.value) {
    case 'payables': return 'Payables & Price Analysis';
    case 'purchase-orders': return 'Purchase Orders';
    case 'supplier-performance': return 'Supplier Performance';
    default: return 'Supplier Intelligence';
  }
});

const currentTabDescription = computed(() => {
  switch (activeTab.value) {
    case 'payables': return 'Monitor outstanding vendor payments, aging schedules, and wholesale pricing trends.';
    case 'purchase-orders': return 'Create, approve, and track supplier procurement purchase orders.';
    case 'supplier-performance': return 'Benchmark supplier on-time delivery rates, product quality, and pricing compliance.';
    default: return 'Analyze supplier performance, purchasing activity, pricing, and outstanding supplier payments.';
  }
});

const filteredSuppliers = computed(() => {
  let list = suppliers.value || [];
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase().trim();
    list = list.filter(s => 
      (s.name && s.name.toLowerCase().includes(q)) ||
      (s.phone && s.phone.includes(q)) ||
      (s.email && s.email.toLowerCase().includes(q)) ||
      (s.address && s.address.toLowerCase().includes(q))
    );
  }
  return list;
});

const totalPurchases = computed(() => {
  return suppliers.value.reduce((sum, s) => sum + (Number(s.total_purchases) || 0), 0) || 625000;
});

const totalPayables = computed(() => {
  return suppliers.value.reduce((sum, s) => sum + (Number(s.balance) || 0), 0) || 185000;
});

// ADD SUPPLIER MODAL STATE
const showSupplierModal = ref(false);
const isSavingSupplier = ref(false);
const successAlertMessage = ref('');
let successAlertTimer = null;

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
  if (maxNum === 0) {
    maxNum = (suppliers.value || []).length;
  }
  const nextNum = maxNum + 1;
  return 'SUP-' + String(nextNum).padStart(4, '0');
};

const supplierForm = ref({
  code: '',
  name: '',
  phone: '',
  email: '',
  address: ''
});

const openAddSupplierModal = () => {
  supplierForm.value = {
    code: generateUniqueSupplierCode(),
    name: '',
    phone: '',
    email: '',
    address: ''
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
    const res = await store.addSupplier({
      code: supplierForm.value.code,
      name: supplierForm.value.name,
      phone: supplierForm.value.phone,
      email: supplierForm.value.email,
      address: supplierForm.value.address
    });
    if (res.success) {
      await store.fetchSuppliers();
      showSupplierModal.value = false;
      successAlertMessage.value = 'Supplier added successfully!';
      if (successAlertTimer) clearTimeout(successAlertTimer);
      successAlertTimer = setTimeout(() => {
        successAlertMessage.value = '';
      }, 4500);
    } else {
      alert(res.message || 'Failed to add supplier');
    }
  } catch (err) {
    alert('An error occurred while adding the supplier.');
  } finally {
    isSavingSupplier.value = false;
  }
};

// AVATAR COLOR GENERATOR
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

/* UNIFIED TABS NAV */
.supplier-tabs-nav {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #ffffff;
  padding: 6px 8px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
  overflow-x: auto;
}

.supplier-tab-btn {
  background: transparent;
  border: none;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 13.5px;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s ease;
  display: inline-flex;
  align-items: center;
}

.supplier-tab-btn:hover {
  color: #0f172a;
  background: #f1f5f9;
}

.supplier-tab-btn.active {
  background: #2563eb;
  color: #ffffff;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
}

/* 5 KPI GRID */
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
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.supplier-kpi-box:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
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

/* AGING BOX */
.aging-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 14px;
  text-align: center;
}

.aging-label {
  font-size: 11.5px;
  font-weight: 700;
  text-transform: uppercase;
  color: #64748b;
  letter-spacing: 0.5px;
  display: block;
  margin-bottom: 4px;
}

.aging-value {
  font-size: 1.4rem;
  font-weight: 800;
  line-height: 1.2;
}

.aging-sub {
  font-size: 11.5px;
  display: block;
  margin-top: 4px;
}

/* MAIN PANEL CARD */
.supplier-panel-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
  overflow: hidden;
}

.supplier-panel-header {
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 14px;
  background: #ffffff;
}

.supplier-panel-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
}

.supplier-tools-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.supplier-search-input {
  position: relative;
  min-width: 280px;
}

.supplier-search-input .search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 14px;
  pointer-events: none;
}

.supplier-search-input input {
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

.supplier-search-input input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.supplier-search-input .clear-btn {
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

/* TABLE STYLING */
.supplier-table-responsive {
  overflow-x: auto;
}

.supplier-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  text-align: left;
}

.supplier-table th {
  background: #f8fafc;
  padding: 12px 18px;
  font-size: 11.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #64748b;
  border-bottom: 1px solid #e2e8f0;
}

.supplier-table td {
  padding: 14px 18px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 14px;
  vertical-align: middle;
}

.supplier-table-row {
  transition: background 0.15s ease;
}

.supplier-table-row:hover {
  background: #f8fafc;
}

.supplier-info-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.supplier-avatar {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
  flex-shrink: 0;
}

.supplier-name-text {
  font-weight: 700;
  color: #0f172a;
  line-height: 1.25;
}

.supplier-id-text {
  font-size: 12px;
  color: #64748b;
  font-family: monospace;
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

.badge-out {
  background: #fef2f2;
  color: #dc2626;
}
.badge-out .pill-dot {
  background: #ef4444;
}

.badge-pending {
  background: #fffbeb;
  color: #d97706;
}
.badge-pending .pill-dot {
  background: #f59e0b;
}

/* MODAL STYLES */
.supplier-modal-overlay {
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

.supplier-modal-card {
  background: #ffffff;
  border-radius: 16px;
  width: 100%;
  max-width: 620px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  border: 1px solid #e2e8f0;
  animation: supplierModalFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes supplierModalFadeIn {
  from { opacity: 0; transform: scale(0.96) translateY(-8px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

.supplier-modal-header {
  padding: 18px 24px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.supplier-modal-header-left {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.supplier-modal-icon {
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

.supplier-modal-header-text {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.supplier-modal-title {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.25;
}

.supplier-modal-subtitle {
  margin: 3px 0 0 0;
  font-size: 13px;
  color: #64748b;
  line-height: 1.35;
}

.supplier-modal-close {
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

.supplier-modal-close:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.supplier-modal-body {
  padding: 24px;
}

.form-label {
  display: block;
  margin-bottom: 6px;
  font-size: 13.5px;
  font-weight: 600;
  color: #475569;
}

.form-control {
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

.form-control:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

textarea.form-control {
  height: 80px;
  padding-top: 10px;
  resize: vertical;
}

/* TOP SUCCESS ALERT */
.supplier-top-alert {
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
</style>
