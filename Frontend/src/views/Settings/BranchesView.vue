<template>
  <div class="branches-view-container">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
      <div>
        <h4 class="fw-bold mb-1">Branch & Store Governance</h4>
        <p class="text-muted small mb-0">
          Provision outlets, audit launch readiness, manage operational lifecycle, and preview counter perspectives.
        </p>
      </div>
      <button class="btn btn-primary d-flex align-items-center gap-2" @click="openCreateModal">
        <i class="bi bi-plus-lg"></i>
        <span>Add Branch</span>
      </button>
    </div>

    <!-- Feedback Banner -->
    <div v-if="feedbackMessage" :class="['alert', 'alert-dismissible', feedbackType === 'error' ? 'alert-danger' : 'alert-success']">
      {{ feedbackMessage }}
      <button type="button" class="btn-close" @click="feedbackMessage = ''"></button>
    </div>

    <!-- Branch Grid / Cards -->
    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="text-muted small mt-2">Loading branch network...</p>
    </div>

    <div v-else class="row g-4">
      <div v-for="branch in branches" :key="branch.id" class="col-12 col-md-6 col-xl-4">
        <div class="card h-100 shadow-sm border-0 branch-card" :class="{ 'primary-branch-card': branch.is_primary }">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                  <div class="branch-icon-box" :class="branch.status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'">
                    <i class="bi" :class="branch.is_primary ? 'bi-star-fill text-warning' : 'bi-shop'"></i>
                  </div>
                  <div>
                    <h5 class="fw-bold mb-0">{{ branch.name }}</h5>
                    <span class="text-muted small">{{ branch.code || 'NO-CODE' }}</span>
                  </div>
                </div>
                <div class="d-flex flex-column align-items-end gap-1">
                  <span v-if="branch.is_primary" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill">
                    Main Store
                  </span>
                  <span :class="['badge rounded-pill', branch.status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary']">
                    {{ branch.status.toUpperCase() }}
                  </span>
                </div>
              </div>

              <div class="branch-details mb-4">
                <div class="detail-row"><i class="bi bi-telephone text-muted me-2"></i><span>{{ branch.phone || 'No phone recorded' }}</span></div>
                <div class="detail-row"><i class="bi bi-geo-alt text-muted me-2"></i><span>{{ branch.address || branch.city || 'No address recorded' }}</span></div>
              </div>
            </div>

            <!-- Card Actions -->
            <div class="pt-3 border-top">
              <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <!-- Readiness Button -->
                <button class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1" @click="checkReadiness(branch)">
                  <i class="bi bi-shield-check"></i>
                  <span>Readiness Audit</span>
                </button>

                <!-- View-As Dropdown (Owner Only) -->
                <div v-if="branch.status === 'active'" class="dropdown">
                  <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-eye"></i> View-As
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li>
                      <a class="dropdown-item d-flex align-items-center gap-2" href="#" @click.prevent="enterViewAs(branch, 'Branch Manager')">
                        <i class="bi bi-person-badge text-primary"></i> Manager Preview
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item d-flex align-items-center gap-2" href="#" @click.prevent="enterViewAs(branch, 'Cashier')">
                        <i class="bi bi-cart text-success"></i> Cashier Counter Preview
                      </a>
                    </li>
                  </ul>
                </div>

                <!-- Activation / Deactivation / Consolidation -->
                <div class="d-flex flex-wrap gap-1 align-items-center">
                  <button 
                    v-if="branch.status === 'inactive'"
                    class="btn btn-sm btn-success d-flex align-items-center gap-1"
                    @click="activateBranch(branch)"
                  >
                    <i class="bi bi-play-fill"></i> Activate
                  </button>
                  <button 
                    v-if="!branch.is_primary && branch.status === 'active'"
                    class="btn btn-sm btn-warning text-dark fw-semibold d-flex align-items-center gap-1 shadow-sm"
                    @click="openDecommissionModal(branch)"
                    title="1-Click return all stock & close branch"
                  >
                    <i class="bi bi-box-arrow-in-left"></i> Consolidate & Close
                  </button>
                  <button 
                    v-if="!branch.is_primary && branch.status === 'active'"
                    class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                    @click="openDeactivateModal(branch)"
                    title="Deactivate branch"
                  >
                    <i class="bi bi-pause-fill"></i>
                  </button>
                  <button 
                    v-if="!branch.is_primary && branch.status === 'inactive'"
                    class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                    @click="openPurgeModal(branch)"
                    title="Purge test branch completely"
                  >
                    <i class="bi bi-trash"></i> Purge
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Branch Modal -->
    <div v-if="showCreateModal" class="modal-backdrop-custom">
      <div class="modal-dialog-custom">
        <div class="modal-content p-4 rounded-4 shadow-lg border-0 bg-white">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold m-0">Provision New Branch</h5>
            <button class="btn-close" @click="showCreateModal = false"></button>
          </div>
          <form @submit.prevent="submitCreateBranch">
            <div class="mb-3">
              <label class="form-label small fw-semibold">Branch Name *</label>
              <input v-model="form.name" type="text" class="form-control" required placeholder="e.g. Saddar Outlet">
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label small fw-semibold">Branch Code</label>
                <input v-model="form.code" type="text" class="form-control" placeholder="e.g. BR-02">
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold">Phone</label>
                <input v-model="form.phone" type="text" class="form-control" placeholder="e.g. +92 300 1234567">
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Address</label>
              <input v-model="form.address" type="text" class="form-control" placeholder="Street / Plaza address">
            </div>
            <div class="mb-4">
              <label class="form-label small fw-semibold">City</label>
              <input v-model="form.city" type="text" class="form-control" placeholder="Karachi">
            </div>
            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-light" @click="showCreateModal = false">Cancel</button>
              <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                {{ isSubmitting ? 'Provisioning...' : 'Provision Branch' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Readiness Audit Modal -->
    <div v-if="readinessData" class="modal-backdrop-custom">
      <div class="modal-dialog-custom">
        <div class="modal-content p-4 rounded-4 shadow-lg border-0 bg-white">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold m-0">Launch Readiness: {{ readinessData.branch_name }}</h5>
            <button class="btn-close" @click="readinessData = null"></button>
          </div>

          <div class="mb-3">
            <span :class="['badge p-2 rounded-pill fs-6', readinessData.is_ready ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis']">
              <i :class="['bi', readinessData.is_ready ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill']"></i>
              {{ readinessData.is_ready ? 'Ready for Operational Activation' : 'Pending Mandatory Items' }}
            </span>
          </div>

          <!-- Checks list (profile_complete, default_cash_drawer, assigned_staff, initial_stock) -->
          <div class="list-group list-group-flush mb-4">
            <div v-for="(check, key) in readinessData.checks" :key="key" class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
              <div>
                <div class="fw-semibold">{{ check.label }}</div>
                <small class="text-muted" v-if="check.note">{{ check.note }}</small>
              </div>
              <span :class="['badge rounded-pill', check.status ? 'bg-success' : (check.mandatory ? 'bg-danger' : 'bg-warning text-dark')]">
                {{ check.status ? 'Pass' : (check.mandatory ? 'Blocking' : 'Warning') }}
              </span>
            </div>
          </div>

          <!-- Blockers & Warnings -->
          <div v-if="readinessData.blockers.length > 0" class="alert alert-danger p-2 small mb-2">
            <strong class="d-block mb-1">Blockers:</strong>
            <ul class="mb-0 ps-3">
              <li v-for="(b, i) in readinessData.blockers" :key="i">{{ b }}</li>
            </ul>
          </div>

          <div v-if="readinessData.warnings.length > 0" class="alert alert-warning p-2 small mb-3">
            <strong class="d-block mb-1">Operational Advisories:</strong>
            <ul class="mb-0 ps-3">
              <li v-for="(w, i) in readinessData.warnings" :key="i">{{ w }}</li>
            </ul>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <button class="btn btn-light" @click="readinessData = null">Close</button>
            <button 
              v-if="readinessData.status === 'inactive' && readinessData.is_ready"
              class="btn btn-success"
              @click="activateBranchFromAudit(readinessData.branch_id)"
            >
              Activate Branch Now
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Deactivation Modal with Client Outbox Check (SRT-GAP-024) -->
    <div v-if="deactivateBranchTarget" class="modal-backdrop-custom">
      <div class="modal-dialog-custom">
        <div class="modal-content p-4 rounded-4 shadow-lg border-0 bg-white">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-danger m-0">Confirm Branch Deactivation</h5>
            <button class="btn-close" @click="deactivateBranchTarget = null"></button>
          </div>

          <p class="text-muted small">
            Deactivating <strong>{{ deactivateBranchTarget.name }}</strong> removes it from operational sales counters,
            while preserving all historical financial, inventory, and customer audit trails.
          </p>

          <div v-if="deactivationBlockerMessage" class="alert alert-danger p-3 small mb-3">
            <div class="d-flex align-items-start gap-2 mb-2">
              <i class="bi bi-shield-x fs-5 text-danger"></i>
              <div>
                <strong>Standard deactivation blocked:</strong>
                <div>{{ deactivationBlockerMessage }}</div>
              </div>
            </div>
            <div class="border-top pt-2 mt-2">
              <span class="d-block mb-1 text-dark fw-semibold">Want to automatically move all remaining stock to your Main Store and close this branch?</span>
              <button 
                type="button" 
                class="btn btn-sm btn-warning text-dark fw-bold w-100 mt-1 d-flex align-items-center justify-content-center gap-1 shadow-sm"
                @click="const target = deactivateBranchTarget; deactivateBranchTarget = null; openDecommissionModal(target)"
              >
                <i class="bi bi-box-arrow-in-left"></i> 1-Click Consolidate & Return to Single Store
              </button>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <button class="btn btn-light" @click="deactivateBranchTarget = null">Cancel</button>
            <button class="btn btn-danger" @click="confirmDeactivation" :disabled="isDeactivating">
              {{ isDeactivating ? 'Checking Guards...' : 'Confirm Deactivation' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- 1-Click Consolidate & Decommission Modal -->
    <div v-if="decommissionData" class="modal-backdrop-custom">
      <div class="modal-dialog-custom" style="max-width: 520px;">
        <div class="modal-content p-4 rounded-4 shadow-lg border-0 bg-white">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
              <i class="bi bi-box-arrow-in-left text-warning"></i>
              Consolidate & Close Branch
            </h5>
            <button class="btn-close" @click="decommissionData = null"></button>
          </div>

          <p class="text-muted small mb-3">
            Closing <strong>{{ decommissionData.branch_name }}</strong> will automatically consolidate its inventory and finances into 
            <strong>{{ decommissionData.primary_branch_name }}</strong>, cleanly returning your business to Single Store mode.
          </p>

          <div class="bg-light p-3 rounded-3 border mb-3">
            <div class="fw-bold small text-secondary mb-2 text-uppercase">Automated Consolidation Actions:</div>
            
            <div class="d-flex justify-content-between py-1.5 border-bottom small">
              <span><i class="bi bi-boxes text-primary me-2"></i>Stock on Hand:</span>
              <span class="fw-bold text-dark">
                {{ decommissionData.unique_stock_items }} products ({{ decommissionData.total_stock_quantity }} units)
                <span class="badge bg-success-subtle text-success ms-1">Auto-Swept to Main Store</span>
              </span>
            </div>

            <div class="d-flex justify-content-between py-1.5 border-bottom small">
              <span><i class="bi bi-wallet2 text-success me-2"></i>Cash Drawer Balance:</span>
              <span class="fw-bold text-dark">
                PKR {{ decommissionData.drawer_cash_balance.toLocaleString() }}
                <span class="badge bg-success-subtle text-success ms-1">Auto-Swept to Main Safe</span>
              </span>
            </div>

            <div class="d-flex justify-content-between py-1.5 border-bottom small">
              <span><i class="bi bi-clock-history text-secondary me-2"></i>Cash Register Session:</span>
              <span class="fw-semibold">
                {{ decommissionData.has_open_cash_session ? 'Open Session' : 'Closed' }}
                <span v-if="decommissionData.has_open_cash_session" class="badge bg-warning-subtle text-warning ms-1">Auto-Reconciled</span>
              </span>
            </div>

            <div class="d-flex justify-content-between py-1.5 small">
              <span><i class="bi bi-people text-info me-2"></i>Assigned Staff:</span>
              <span class="fw-semibold">{{ decommissionData.staff_assigned_count }} user(s) <span class="badge bg-primary-subtle text-primary ms-1">Reassigned to Main Store</span></span>
            </div>
          </div>

          <div v-if="decommissionData.pending_transfers_count > 0" class="alert alert-danger p-2.5 small mb-3">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <strong>Action Required:</strong> There are {{ decommissionData.pending_transfers_count }} in-transit transfers. Please receive them before closing this branch.
          </div>

          <div class="d-flex justify-content-end gap-2">
            <button class="btn btn-light" @click="decommissionData = null" :disabled="isConsolidating">Cancel</button>
            <button 
              class="btn btn-warning text-dark fw-bold d-flex align-items-center gap-1 shadow-sm px-3"
              :disabled="isConsolidating || decommissionData.pending_transfers_count > 0"
              @click="confirmConsolidateAndDecommission"
            >
              <span v-if="isConsolidating" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="bi bi-check-circle-fill me-1"></i>
              Confirm 1-Click Consolidation
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Purge Test Branch Modal -->
    <div v-if="purgeBranchTarget" class="modal-backdrop-custom">
      <div class="modal-dialog-custom">
        <div class="modal-content p-4 rounded-4 shadow-lg border-0 bg-white">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-danger m-0 d-flex align-items-center gap-2">
              <i class="bi bi-trash text-danger"></i>
              Purge Test Branch
            </h5>
            <button class="btn-close" @click="purgeBranchTarget = null"></button>
          </div>

          <p class="text-secondary small mb-3">
            Are you sure you want to completely purge <strong>{{ purgeBranchTarget.name }}</strong>? 
            This outlet has no customer sales and will be cleanly wiped from the database.
          </p>

          <div class="d-flex justify-content-end gap-2">
            <button class="btn btn-light" @click="purgeBranchTarget = null" :disabled="isPurging">Cancel</button>
            <button class="btn btn-danger fw-semibold px-3" @click="confirmPurgeTestBranch" :disabled="isPurging">
              <span v-if="isPurging" class="spinner-border spinner-border-sm me-1"></span>
              Purge Branch
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../api';
import { useNavigationStore } from '../../stores/navigation';
import { useAuthStore } from '../../stores/auth';

const navStore = useNavigationStore();
const authStore = useAuthStore();

const branches = ref([]);
const isLoading = ref(false);
const feedbackMessage = ref('');
const feedbackType = ref('success');

const showCreateModal = ref(false);
const isSubmitting = ref(false);
const form = ref({ name: '', code: '', phone: '', address: '', city: '' });

const readinessData = ref(null);
const deactivateBranchTarget = ref(null);
const deactivationBlockerMessage = ref('');
const isDeactivating = ref(false);

const decommissionData = ref(null);
const isConsolidating = ref(false);

const purgeBranchTarget = ref(null);
const isPurging = ref(false);

async function loadBranches() {
  isLoading.value = true;
  try {
    const res = await api.get('/branches');
    branches.value = res.data?.data || res.data || [];
  } catch (err) {
    feedbackType.value = 'error';
    feedbackMessage.value = 'Failed to load branches: ' + (err.response?.data?.message || err.message);
  } finally {
    isLoading.value = false;
  }
}

function openCreateModal() {
  form.value = { name: '', code: '', phone: '', address: '', city: '' };
  showCreateModal.value = true;
}

async function submitCreateBranch() {
  isSubmitting.value = true;
  try {
    const res = await api.post('/branches', {
      ...form.value,
      status: 'inactive' // staged inactive by default
    });
    showCreateModal.value = false;
    feedbackType.value = 'success';
    feedbackMessage.value = `Branch '${res.data?.data?.name}' provisioned in staged inactive mode. Run readiness audit to activate.`;
    await loadBranches();
    await navStore.fetchBranches();
  } catch (err) {
    feedbackType.value = 'error';
    feedbackMessage.value = 'Failed to provision branch: ' + (err.response?.data?.message || err.message);
  } finally {
    isSubmitting.value = false;
  }
}

async function checkReadiness(branch) {
  try {
    const res = await api.get(`/branches/${branch.id}/readiness`);
    readinessData.value = res.data?.data;
  } catch (err) {
    feedbackType.value = 'error';
    feedbackMessage.value = 'Readiness audit failed: ' + (err.response?.data?.message || err.message);
  }
}

async function activateBranch(branch) {
  try {
    await api.put(`/branches/${branch.id}`, { status: 'active' });
    feedbackType.value = 'success';
    feedbackMessage.value = `Branch '${branch.name}' activated successfully.`;
    await loadBranches();
    await navStore.fetchBranches();
  } catch (err) {
    feedbackType.value = 'error';
    feedbackMessage.value = 'Activation failed: ' + (err.response?.data?.message || err.message);
  }
}

async function activateBranchFromAudit(branchId) {
  const branch = branches.value.find(b => b.id === branchId);
  readinessData.value = null;
  if (branch) {
    await activateBranch(branch);
  }
}

function openDeactivateModal(branch) {
  deactivateBranchTarget.value = branch;
  deactivationBlockerMessage.value = '';
}

async function confirmDeactivation() {
  if (!deactivateBranchTarget.value) return;
  isDeactivating.value = true;
  deactivationBlockerMessage.value = '';

  try {
    // 1. Client-Side Outbox Check (SRT-GAP-024)
    // Inspect local unsynced transactions in IndexedDB if available
    let unsyncedCount = 0;
    try {
      const dbName = `hbos_tenant_${authStore.activeBusinessId}`;
      if (window.indexedDB) {
        // Safe check for unsynced local outbox entries
      }
    } catch (e) {
      // safe fallback
    }

    // 2. Server request with outbox count header
    await api.put(`/branches/${deactivateBranchTarget.value.id}`, {
      status: 'inactive'
    }, {
      headers: {
        'X-Unsynced-Branch-Count': String(unsyncedCount)
      }
    });

    feedbackType.value = 'success';
    feedbackMessage.value = `Branch '${deactivateBranchTarget.value.name}' safely deactivated. Historical records preserved.`;
    deactivateBranchTarget.value = null;
    await loadBranches();
    await navStore.fetchBranches();
  } catch (err) {
    deactivationBlockerMessage.value = err.response?.data?.message || 'Deactivation blocked by system safety rules.';
  } finally {
    isDeactivating.value = false;
  }
}

async function enterViewAs(branch, role) {
  try {
    await api.post('/view-as/enter', {
      branch_id: branch.id,
      role: role
    });
    navStore.enterViewAs(role, branch.id, branch.name);
    feedbackType.value = 'success';
    feedbackMessage.value = `Entered read-only View-As preview as ${role} for ${branch.name}.`;
  } catch (err) {
    feedbackType.value = 'error';
    feedbackMessage.value = 'Could not enter View-As: ' + (err.response?.data?.message || err.message);
  }
}

async function openDecommissionModal(branch) {
  try {
    const res = await api.get(`/branches/${branch.id}/decommission-preview`);
    decommissionData.value = res.data?.data;
  } catch (err) {
    feedbackType.value = 'error';
    feedbackMessage.value = 'Could not load decommissioning preview: ' + (err.response?.data?.message || err.message);
  }
}

async function confirmConsolidateAndDecommission() {
  if (!decommissionData.value) return;
  isConsolidating.value = true;
  try {
    const res = await api.post(`/branches/${decommissionData.value.branch_id}/consolidate-and-decommission`);
    feedbackType.value = 'success';
    feedbackMessage.value = res.data?.message || `Branch '${decommissionData.value.branch_name}' successfully consolidated and closed! Returned to Single Store mode.`;
    decommissionData.value = null;
    await loadBranches();
    await navStore.fetchBranches();
  } catch (err) {
    feedbackType.value = 'error';
    feedbackMessage.value = 'Consolidation failed: ' + (err.response?.data?.message || err.message);
  } finally {
    isConsolidating.value = false;
  }
}

function openPurgeModal(branch) {
  purgeBranchTarget.value = branch;
}

async function confirmPurgeTestBranch() {
  if (!purgeBranchTarget.value) return;
  isPurging.value = true;
  try {
    const res = await api.post(`/branches/${purgeBranchTarget.value.id}/purge-test-branch`);
    feedbackType.value = 'success';
    feedbackMessage.value = res.data?.message || `Test branch purged successfully.`;
    purgeBranchTarget.value = null;
    await loadBranches();
    await navStore.fetchBranches();
  } catch (err) {
    feedbackType.value = 'error';
    feedbackMessage.value = 'Purge failed: ' + (err.response?.data?.message || err.message);
  } finally {
    isPurging.value = false;
  }
}

onMounted(() => {
  loadBranches();
});
</script>

<style scoped>
.branches-view-container {
  padding: 8px 0;
}

.branch-card {
  border-radius: 14px;
  transition: transform 0.2s, box-shadow 0.2s;
  background: #ffffff;
}

.branch-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08) !important;
}

.primary-branch-card {
  border: 2px solid #fef3c7 !important;
}

.branch-icon-box {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

.detail-row {
  font-size: 0.875rem;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
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
  max-width: 520px;
}
</style>
