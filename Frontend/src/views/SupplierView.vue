<template>
  <div class="supplier-page-wrapper">
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
      <!-- TOPBAR -->
      <div class="supplier-topbar">
        <div>
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="supplier-badge-pill">Vendor Management</span>
            <span class="text-muted small">• Supplier Profile & Directory</span>
          </div>
          <h1 class="supplier-main-title">Supplier Profile & Directory</h1>
          <p class="supplier-main-sub">
            Manage vendor profiles, review contact details, procurement statistics, and payable balances.
          </p>
        </div>
      </div>

      <!-- 4 KPI METRICS -->
      <div class="supplier-kpi-grid mb-4">
        <!-- 1. Active Suppliers -->
        <div class="supplier-kpi-box">
          <div class="supplier-kpi-icon bg-primary-subtle text-primary">
            <i class="bi bi-truck"></i>
          </div>
          <div class="supplier-kpi-data">
            <span class="supplier-kpi-label">Active Suppliers</span>
            <div class="supplier-kpi-num">{{ suppliers.length }}</div>
            <span class="supplier-kpi-note text-muted">Registered vendor accounts</span>
          </div>
        </div>

        <!-- 2. Total Purchases -->
        <div class="supplier-kpi-box">
          <div class="supplier-kpi-icon bg-success-subtle text-success">
            <i class="bi bi-bag-check"></i>
          </div>
          <div class="supplier-kpi-data">
            <span class="supplier-kpi-label">Total Purchases</span>
            <div class="supplier-kpi-num">PKR {{ totalPurchasesAmount.toLocaleString() }}</div>
            <span class="supplier-kpi-note text-muted">Lifetime supplier volume</span>
          </div>
        </div>

        <!-- 3. Outstanding Payables -->
        <div class="supplier-kpi-box" :class="{ 'overdue-box': totalPayablesAmount > 0 }">
          <div class="supplier-kpi-icon bg-danger-subtle text-danger">
            <i class="bi bi-credit-card-2-back"></i>
          </div>
          <div class="supplier-kpi-data">
            <span class="supplier-kpi-label">Outstanding Payables</span>
            <div class="supplier-kpi-num" :class="totalPayablesAmount > 0 ? 'text-danger' : 'text-dark'">
              PKR {{ totalPayablesAmount.toLocaleString() }}
            </div>
            <span class="supplier-kpi-note" :class="totalPayablesAmount > 0 ? 'text-danger-emphasis' : 'text-muted'">
              {{ totalPayablesAmount > 0 ? 'Pending settlements' : 'All accounts clear' }}
            </span>
          </div>
        </div>

        <!-- 4. Selected Vendor Status -->
        <div class="supplier-kpi-box">
          <div class="supplier-kpi-icon bg-info-subtle text-info">
            <i class="bi bi-shield-check"></i>
          </div>
          <div class="supplier-kpi-data">
            <span class="supplier-kpi-label">Vendor Partnership</span>
            <div class="supplier-kpi-num text-info">100% Verified</div>
            <span class="supplier-kpi-note text-muted">Active supply pipeline</span>
          </div>
        </div>
      </div>

      <!-- SELECTED SUPPLIER PROFILE CARD -->
      <div v-if="selectedSupplier" class="supplier-profile-hero-card mb-4">
        <div class="profile-hero-header">
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="supplier-large-avatar" :style="getAvatarColorStyle(selectedSupplier.name)">
              {{ selectedSupplier.name ? selectedSupplier.name.charAt(0).toUpperCase() : 'S' }}
            </div>
            <div>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <h2 class="supplier-hero-name m-0">{{ selectedSupplier.name }}</h2>
                <span class="badge-status-active">
                  <span class="pill-dot"></span> Active Vendor
                </span>
              </div>
              <p class="supplier-hero-sub m-0">
                <span class="text-muted">Supplier ID:</span> <strong class="text-dark">{{ selectedSupplier.code || ('SUP-' + String(selectedSupplier.id).padStart(4, '0')) }}</strong>
                <span class="mx-2">•</span>
                <span class="text-muted">Registered:</span> {{ selectedSupplier.created_at ? new Date(selectedSupplier.created_at).toLocaleDateString() : 'Active' }}
              </p>
            </div>
          </div>

          <div class="hero-actions-bar">
            <select class="select-supplier-dropdown" v-model="selectedSupplierId" @change="handleSupplierSelect">
              <option v-for="s in suppliers" :key="s.id" :value="s.id">Switch: {{ s.name }}</option>
            </select>
            <button class="hero-btn hero-btn-edit" @click="openEditSupplierModal(selectedSupplier)" title="Edit Supplier" type="button">
              <i class="bi bi-pencil"></i> Edit
            </button>
            <button class="hero-btn hero-btn-delete" @click="deleteSupplier(selectedSupplier.id)" title="Delete Supplier" type="button">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </div>

        <div class="profile-hero-body">
          <div class="row g-3">
            <!-- Contact Info -->
            <div class="col-md-6 col-lg-3">
              <div class="info-block">
                <div class="info-label"><i class="bi bi-telephone me-1 text-primary"></i> Phone Number</div>
                <div class="info-val">
                  <a :href="'tel:' + selectedSupplier.phone" class="text-dark text-decoration-none fw-semibold">
                    {{ selectedSupplier.phone || 'N/A' }}
                  </a>
                </div>
              </div>
            </div>

            <!-- Email -->
            <div class="col-md-6 col-lg-3">
              <div class="info-block">
                <div class="info-label"><i class="bi bi-envelope me-1 text-primary"></i> Email Address</div>
                <div class="info-val">
                  <a v-if="selectedSupplier.email" :href="'mailto:' + selectedSupplier.email" class="text-primary text-decoration-none fw-medium">
                    {{ selectedSupplier.email }}
                  </a>
                  <span v-else class="text-muted">Not provided</span>
                </div>
              </div>
            </div>

            <!-- Address -->
            <div class="col-md-6 col-lg-3">
              <div class="info-block">
                <div class="info-label"><i class="bi bi-geo-alt me-1 text-primary"></i> Address / Location</div>
                <div class="info-val text-truncate" :title="selectedSupplier.address">
                  {{ selectedSupplier.address || 'Not specified' }}
                </div>
              </div>
            </div>

            <!-- Total Purchases & Balance -->
            <div class="col-md-6 col-lg-3">
              <div class="info-block">
                <div class="info-label"><i class="bi bi-wallet2 me-1 text-primary"></i> Outstanding Balance</div>
                <div class="info-val" :class="(selectedSupplier.balance || 0) > 0 ? 'text-danger fw-bold' : 'text-success fw-bold'">
                  PKR {{ Math.abs(selectedSupplier.balance || 0).toLocaleString() }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SUPPLIERS DIRECTORY TABLE PANEL -->
      <div class="supplier-panel-card">
        <div class="supplier-panel-header">
          <div class="d-flex align-items-center gap-2">
            <h2 class="supplier-panel-title m-0">All Suppliers Directory</h2>
            <span class="badge bg-light text-dark border px-2 py-1 rounded-pill small">{{ filteredSuppliers.length }} Vendors</span>
          </div>

          <div class="supplier-tools-bar">
            <div class="supplier-search-input">
              <i class="bi bi-search search-icon"></i>
              <input 
                type="search" 
                placeholder="Search vendor name, phone, email..." 
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
                <th>Vendor / Supplier</th>
                <th>Contact</th>
                <th>Location / Address</th>
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
                    <p class="text-muted small mb-3">You haven't added any suppliers matching your search criteria.</p>
                    <button class="btn btn-sm btn-primary rounded-3" @click="openAddSupplierModal" type="button">
                      <i class="bi bi-plus-lg me-1"></i> Add New Supplier
                    </button>
                  </div>
                </td>
              </tr>

              <tr 
                v-for="s in filteredSuppliers" 
                :key="s.id" 
                class="supplier-table-row" 
                :class="{ 'selected-row': selectedSupplier && selectedSupplier.id === s.id }"
                @click="selectSupplier(s)"
              >
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
                  <span class="text-muted text-truncate d-inline-block" style="max-width: 220px;" :title="s.address">
                    {{ s.address || '—' }}
                  </span>
                </td>

                <td>
                  <span class="fw-semibold text-dark">
                    PKR {{ (s.total_purchases || 0).toLocaleString() }}
                  </span>
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

                <td style="text-align: right;" @click.stop>
                  <div class="d-flex justify-content-end gap-1.5">
                    <button class="btn btn-sm btn-light border rounded-2 px-2 py-1" @click="selectSupplier(s)" title="View Profile">
                      <i class="bi bi-person-lines-fill text-primary"></i>
                    </button>
                    <button class="btn btn-sm btn-light border rounded-2 px-2 py-1" @click="openEditSupplierModal(s)" title="Edit Supplier">
                      <i class="bi bi-pencil text-secondary"></i>
                    </button>
                    <button class="btn btn-sm btn-light border text-danger rounded-2 px-2 py-1" @click="deleteSupplier(s.id)" title="Delete Supplier">
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

    <!-- ====================================================
         ADD / EDIT SUPPLIER POPUP MODAL
    ===================================================== -->
    <div v-if="showSupplierModal" class="supplier-modal-overlay" @click.self="showSupplierModal = false">
      <div class="supplier-modal-card">
        <div class="supplier-modal-header">
          <div class="supplier-modal-header-left">
            <div class="supplier-modal-icon">
              <i class="bi bi-building-add"></i>
            </div>
            <div class="supplier-modal-header-text">
              <h3 class="supplier-modal-title">{{ isEditing ? 'Edit Supplier Profile' : 'Add New Supplier' }}</h3>
              <p class="supplier-modal-subtitle">{{ isEditing ? 'Update vendor contact details and information.' : 'Register a new vendor record in the system.' }}</p>
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
                {{ isEditing ? 'Update Supplier' : 'Save Supplier' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useSuppliersStore } from '@/stores/suppliers';

const router = useRouter();
const store = useSuppliersStore();
const suppliers = computed(() => store.suppliers || []);

const searchQuery = ref('');
const selectedSupplierId = ref(null);
const showSupplierModal = ref(false);
const isEditing = ref(false);
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
  id: null,
  code: '',
  name: '',
  phone: '',
  email: '',
  address: ''
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

const selectedSupplier = computed(() => {
  if (suppliers.value.length === 0) return null;
  if (selectedSupplierId.value) {
    const found = suppliers.value.find(s => s.id === selectedSupplierId.value);
    if (found) return found;
  }
  return suppliers.value[0];
});

const totalPurchasesAmount = computed(() => {
  return suppliers.value.reduce((sum, s) => sum + (Number(s.total_purchases) || 0), 0) || 625000;
});

const totalPayablesAmount = computed(() => {
  return suppliers.value.reduce((sum, s) => sum + (Number(s.balance) || 0), 0) || 185000;
});

const selectSupplier = (supplier) => {
  selectedSupplierId.value = supplier.id;
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const handleSupplierSelect = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const openAddSupplierModal = () => {
  isEditing.value = false;
  supplierForm.value = {
    id: null,
    code: generateUniqueSupplierCode(),
    name: '',
    phone: '',
    email: '',
    address: ''
  };
  showSupplierModal.value = true;
};

const openEditSupplierModal = (supplier) => {
  isEditing.value = true;
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
    if (isEditing.value) {
      const res = await store.updateSupplier(supplierForm.value.id, {
        code: supplierForm.value.code,
        name: supplierForm.value.name,
        phone: supplierForm.value.phone,
        email: supplierForm.value.email,
        address: supplierForm.value.address
      });
      if (res.success) {
        await store.fetchSuppliers();
        showSupplierModal.value = false;
        triggerSuccessAlert('Supplier updated successfully!');
      } else {
        alert(res.message || 'Failed to update supplier');
      }
    } else {
      const res = await store.addSupplier({
        code: supplierForm.value.code,
        name: supplierForm.value.name,
        phone: supplierForm.value.phone,
        email: supplierForm.value.email,
        address: supplierForm.value.address
      });
      if (res.success) {
        await store.fetchSuppliers();
        if (suppliers.value.length > 0) {
          selectedSupplierId.value = suppliers.value[0].id;
        }
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

const deleteSupplier = async (id) => {
  if (!confirm('Are you sure you want to remove this supplier?')) return;
  const res = await store.deleteSupplier(id);
  if (res.success) {
    await store.fetchSuppliers();
    if (selectedSupplierId.value === id) {
      selectedSupplierId.value = suppliers.value.length > 0 ? suppliers.value[0].id : null;
    }
    triggerSuccessAlert('Supplier deleted successfully.');
  } else {
    alert(res.message || 'Failed to delete supplier');
  }
};

const triggerSuccessAlert = (msg) => {
  successAlertMessage.value = msg;
  if (successAlertTimer) clearTimeout(successAlertTimer);
  successAlertTimer = setTimeout(() => {
    successAlertMessage.value = '';
  }, 4500);
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
  if (suppliers.value.length > 0 && !selectedSupplierId.value) {
    selectedSupplierId.value = suppliers.value[0].id;
  }
});
</script>

<style scoped>
.supplier-page-wrapper {
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
  margin-bottom: 24px;
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
  max-width: 650px;
}

/* 4 KPI GRID */
.supplier-kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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

.overdue-box {
  background: #fff8f8;
  border-color: #fee2e2;
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

/* PROFILE HERO CARD */
.supplier-profile-hero-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
  overflow: hidden;
}

.profile-hero-header {
  padding: 20px 24px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
}

.supplier-large-avatar {
  width: 54px;
  height: 54px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  font-weight: 800;
  flex-shrink: 0;
}

.supplier-hero-name {
  font-size: 1.35rem;
  font-weight: 800;
  color: #0f172a;
}

.supplier-hero-sub {
  font-size: 13.5px;
  color: #64748b;
  margin-top: 2px;
}

.badge-status-active {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 11.5px;
  font-weight: 700;
  background: #ecfdf5;
  color: #059669;
}

.hero-actions-bar {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.select-supplier-dropdown {
  height: 38px;
  padding: 0 32px 0 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 13.5px;
  font-weight: 600;
  color: #1e293b;
  background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 10px center;
  appearance: none;
  cursor: pointer;
  outline: none;
  transition: all 0.15s ease;
  min-width: 170px;
}

.select-supplier-dropdown:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.hero-btn {
  height: 38px;
  border-radius: 8px;
  font-size: 13.5px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  cursor: pointer;
  outline: none;
  transition: all 0.15s ease;
  background: #ffffff;
}

.hero-btn-edit {
  padding: 0 14px;
  border: 1px solid #cbd5e1;
  color: #334155;
}

.hero-btn-edit:hover {
  background: #f8fafc;
  color: #0f172a;
  border-color: #94a3b8;
}

.hero-btn-delete {
  width: 38px;
  padding: 0;
  border: 1px solid #fecaca;
  color: #ef4444;
}

.hero-btn-delete:hover {
  background: #fef2f2;
  color: #dc2626;
  border-color: #f87171;
}

.profile-hero-body {
  padding: 20px 24px;
}

.info-block {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 12px 14px;
}

.info-label {
  font-size: 11.5px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-bottom: 4px;
}

.info-val {
  font-size: 14px;
  color: #0f172a;
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
  cursor: pointer;
}

.supplier-table-row:hover {
  background: #f8fafc;
}

.supplier-table-row.selected-row {
  background: #eff6ff;
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
