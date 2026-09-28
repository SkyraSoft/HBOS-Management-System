<template>
  <div class="security-view-container">
    <!-- Top Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <div class="header-icon-badge">
            <i class="bi bi-shield-check"></i>
          </div>
          <h1 class="page-title fw-bolder m-0">Security &amp; Audit Center</h1>
        </div>
        <p class="text-muted m-0" style="font-size: 0.95rem;">
          Append-only governance trail, transaction tracking, branch provenance, and compliance logs.
        </p>
      </div>

      <div class="d-flex align-items-center gap-2">
        <div class="system-status-pill">
          <span class="pulse-dot"></span>
          <span>Audit Engine: Active &amp; Append-Only</span>
        </div>
        <button class="btn btn-outline-secondary btn-sm px-3 py-2 rounded-3 d-flex align-items-center gap-2 fw-semibold" @click="loadLogs(1)">
          <i class="bi bi-arrow-clockwise"></i> Refresh Logs
        </button>
      </div>
    </div>

    <!-- 403 Forbidden State (Salesperson) -->
    <div v-if="isForbidden" class="alert alert-danger rounded-4 p-4 shadow-sm mb-4">
      <div class="d-flex align-items-center gap-3">
        <i class="bi bi-shield-slash-fill fs-2 text-danger"></i>
        <div>
          <h5 class="fw-bold m-0">Access Denied (403 Forbidden)</h5>
          <p class="m-0 text-secondary">
            Your role does not have permission to view the audit center. Audit logs are restricted to Business Owners and Branch Managers.
          </p>
        </div>
      </div>
    </div>

    <div v-else>
      <!-- Top KPI Grid -->
      <div class="row g-3 mb-4">
        <!-- 1. Total Events -->
        <div class="col-xl-3 col-md-6">
          <div class="kpi-card card border-0 shadow-sm rounded-4 p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="kpi-label">Total Audit Events</span>
              <div class="kpi-icon-wrap bg-primary-subtle text-primary">
                <i class="bi bi-journal-check"></i>
              </div>
            </div>
            <div class="kpi-value text-dark">{{ pagination.total }}</div>
            <div class="kpi-subtext text-muted">Immutable Governed Records</div>
          </div>
        </div>

        <!-- 2. Active Page -->
        <div class="col-xl-3 col-md-6">
          <div class="kpi-card card border-0 shadow-sm rounded-4 p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="kpi-label">Page Position</span>
              <div class="kpi-icon-wrap bg-info-subtle text-info">
                <i class="bi bi-file-earmark-text"></i>
              </div>
            </div>
            <div class="kpi-value text-dark">Page {{ pagination.current_page }} of {{ pagination.last_page || 1 }}</div>
            <div class="kpi-subtext text-muted">{{ pagination.per_page }} Events Per Page</div>
          </div>
        </div>

        <!-- 3. Active Filters -->
        <div class="col-xl-3 col-md-6">
          <div class="kpi-card card border-0 shadow-sm rounded-4 p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="kpi-label">Filtered Results</span>
              <div class="kpi-icon-wrap bg-secondary-subtle text-secondary">
                <i class="bi bi-funnel-fill"></i>
              </div>
            </div>
            <div class="kpi-value text-dark">{{ auditLogs.length }}</div>
            <div class="kpi-subtext text-muted">Records On This Page</div>
          </div>
        </div>

        <!-- 4. Governance Status -->
        <div class="col-xl-3 col-md-6">
          <div class="kpi-card card border-0 shadow-sm rounded-4 p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="kpi-label">Security Posture</span>
              <div class="kpi-icon-wrap bg-success-subtle text-success">
                <i class="bi bi-shield-fill-check"></i>
              </div>
            </div>
            <div class="kpi-value text-success">Protected</div>
            <div class="kpi-subtext text-muted">Redacted &amp; Tenant-Isolated</div>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="row g-4">
        <!-- Left Column: Primary Audit Log -->
        <div class="col-xl-8 col-lg-7">
          <div class="card shadow-sm border-0 rounded-4 overflow-hidden h-100 main-log-card">
            <!-- Card Header with Filters & Search -->
            <div class="card-header bg-white border-bottom p-3 p-md-4">
              <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                  <h5 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-journal-text text-primary"></i>
                    Audit Trail Records
                  </h5>
                  <p class="text-muted m-0 small">
                    Chronological events of governance, sales, inventory movements, financial accounts, and settings.
                  </p>
                </div>

                <div class="badge-count-pill">
                  {{ pagination.total }} Total Records
                </div>
              </div>

              <!-- Filter Controls Bar -->
              <div class="row g-2 align-items-center">
                <!-- Search Bar -->
                <div class="col-md-4 col-12">
                  <div class="input-group input-group-sm search-group">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                      <i class="bi bi-search"></i>
                    </span>
                    <input 
                      type="text" 
                      v-model="filters.search" 
                      class="form-control bg-light border-start-0 ps-0" 
                      placeholder="Search description..."
                      @keyup.enter="loadLogs(1)"
                    />
                    <button v-if="filters.search" class="btn btn-light border-start-0 text-muted" @click="filters.search = ''; loadLogs(1)">
                      <i class="bi bi-x"></i>
                    </button>
                  </div>
                </div>

                <!-- Module / Category Filter -->
                <div class="col-md-3 col-6">
                  <select v-model="filters.log_name" class="form-select form-select-sm bg-light" @change="loadLogs(1)">
                    <option value="">All Categories</option>
                    <option value="governance">Governance</option>
                    <option value="settings">Settings</option>
                    <option value="inventory">Inventory</option>
                    <option value="purchase">Purchase</option>
                    <option value="sale">Sale</option>
                    <option value="customer">Customer</option>
                    <option value="supplier">Supplier</option>
                    <option value="expense">Expense</option>
                    <option value="financial">Financial</option>
                    <option value="security">Security</option>
                  </select>
                </div>

                <!-- Event Filter -->
                <div class="col-md-2 col-6">
                  <select v-model="filters.event" class="form-select form-select-sm bg-light" @change="loadLogs(1)">
                    <option value="">All Events</option>
                    <option value="created">created</option>
                    <option value="updated">updated</option>
                    <option value="deleted">deleted</option>
                    <option value="cancelled">cancelled</option>
                    <option value="voided">voided</option>
                    <option value="returned">returned</option>
                    <option value="settled">settled</option>
                    <option value="reversed">reversed</option>
                    <option value="settings_updated">settings_updated</option>
                    <option value="price_override">price_override</option>
                    <option value="payment_recorded">payment_recorded</option>
                  </select>
                </div>

                <!-- Branch Filter -->
                <div class="col-md-3 col-12" v-if="branches.length > 0">
                  <select v-model="filters.branch_id" class="form-select form-select-sm bg-light" @change="loadLogs(1)">
                    <option value="">All Branches</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Table Body -->
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 custom-audit-table">
                  <thead>
                    <tr class="table-head-row">
                      <th class="py-3 ps-4">TIMESTAMP</th>
                      <th class="py-3">ACTOR</th>
                      <th class="py-3">ACTION / DESCRIPTION</th>
                      <th class="py-3">CATEGORY</th>
                      <th class="py-3">BRANCH</th>
                      <th class="py-3 pe-4 text-center">EVENT</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr 
                      v-for="log in auditLogs" 
                      :key="log.id" 
                      class="audit-row cursor-pointer" 
                      @click="selectLog(log)"
                      :class="{ 'table-primary': selectedLogItem && selectedLogItem.id === log.id }"
                    >
                      <!-- Date & Time -->
                      <td class="py-3 ps-4 date-cell">
                        <div class="fw-bold text-dark">{{ formatDate(log.timestamp) }}</div>
                        <div class="text-muted small"><i class="bi bi-clock me-1"></i>{{ formatTime(log.timestamp) }}</div>
                      </td>

                      <!-- Actor -->
                      <td class="py-3 user-cell">
                        <div class="d-flex align-items-center gap-2">
                          <div class="user-avatar" :class="log.actor ? 'avatar-admin' : 'avatar-system'">
                            {{ getInitials(log.actor?.name || 'System') }}
                          </div>
                          <div>
                            <div class="fw-bold text-dark line-clamp-1">{{ log.actor?.name || 'System / Auto' }}</div>
                            <span class="user-role-badge" :class="log.actor ? 'badge-role-admin' : 'badge-role-system'">
                              {{ log.actor?.email || 'automated' }}
                            </span>
                          </div>
                        </div>
                      </td>

                      <!-- Action -->
                      <td class="py-3 action-cell">
                        <div class="fw-semibold text-dark">{{ log.description }}</div>
                        <div v-if="log.subject_type" class="text-muted small">
                          Subject: <span class="font-monospace">{{ getSubjectName(log.subject_type) }} #{{ log.subject_id }}</span>
                        </div>
                      </td>

                      <!-- Module -->
                      <td class="py-3 module-cell">
                        <span class="module-badge">
                          <i class="bi bi-folder2 me-1"></i>
                          {{ log.log_name }}
                        </span>
                      </td>

                      <!-- Branch -->
                      <td class="py-3 record-cell">
                        <span class="badge bg-light text-dark border">
                          {{ log.branch?.name || 'Business-Wide' }}
                        </span>
                      </td>

                      <!-- Event -->
                      <td class="py-3 pe-4 text-center status-cell">
                        <span class="status-pill status-success">
                          {{ log.event }}
                        </span>
                      </td>
                    </tr>

                    <!-- Empty State -->
                    <tr v-if="auditLogs.length === 0 && !isLoading">
                      <td colspan="6" class="text-center py-5">
                        <div class="empty-state-box">
                          <i class="bi bi-journal-x text-muted" style="font-size: 2.2rem;"></i>
                          <h6 class="mt-2 fw-bold text-dark">No Audit Events Found</h6>
                          <p class="text-muted small mb-3">No log records matched your search query or active filter settings.</p>
                          <button class="btn btn-sm btn-outline-primary px-3 rounded-3" @click="resetFilters">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                          </button>
                        </div>
                      </td>
                    </tr>

                    <tr v-if="isLoading">
                      <td colspan="6" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                          <span class="visually-hidden">Loading...</span>
                        </div>
                        <div class="text-muted small mt-2">Fetching audit trail...</div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Card Footer: Pagination -->
            <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
              <span class="text-muted small">
                Showing <strong class="text-dark">{{ auditLogs.length }}</strong> of <strong class="text-dark">{{ pagination.total }}</strong> events
              </span>
              <div class="d-flex align-items-center gap-2">
                <button 
                  class="btn btn-sm btn-outline-secondary rounded-2" 
                  :disabled="pagination.current_page <= 1 || isLoading" 
                  @click="loadLogs(pagination.current_page - 1)"
                >
                  <i class="bi bi-chevron-left"></i> Previous
                </button>
                <span class="small text-muted px-2">Page {{ pagination.current_page }} of {{ pagination.last_page || 1 }}</span>
                <button 
                  class="btn btn-sm btn-outline-secondary rounded-2" 
                  :disabled="pagination.current_page >= pagination.last_page || isLoading" 
                  @click="loadLogs(pagination.current_page + 1)"
                >
                  Next <i class="bi bi-chevron-right"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Event Inspector & Governance Posture -->
        <div class="col-xl-4 col-lg-5 d-flex flex-column gap-4">
          <!-- Event Inspector -->
          <div class="card shadow-sm border-0 rounded-4 overflow-hidden side-panel-card">
            <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
              <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-eye text-primary"></i>
                Event Inspector
              </h6>
              <span v-if="selectedLogItem" class="badge bg-primary-subtle text-primary fw-bold px-2 py-1 rounded-pill" style="font-size: 0.75rem;">
                Event #{{ selectedLogItem.id }}
              </span>
            </div>
            <div class="card-body p-3">
              <div v-if="selectedLogItem">
                <div class="mb-3">
                  <label class="text-muted small fw-bold text-uppercase">Description</label>
                  <div class="fw-bold text-dark">{{ selectedLogItem.description }}</div>
                </div>

                <div class="row g-2 mb-3">
                  <div class="col-6">
                    <label class="text-muted small fw-bold text-uppercase">Category</label>
                    <div class="small fw-semibold">{{ selectedLogItem.log_name }}</div>
                  </div>
                  <div class="col-6">
                    <label class="text-muted small fw-bold text-uppercase">Event</label>
                    <div class="small fw-semibold">{{ selectedLogItem.event }}</div>
                  </div>
                  <div class="col-6">
                    <label class="text-muted small fw-bold text-uppercase">Actor</label>
                    <div class="small">{{ selectedLogItem.actor?.name || 'System' }}</div>
                  </div>
                  <div class="col-6">
                    <label class="text-muted small fw-bold text-uppercase">Branch</label>
                    <div class="small">{{ selectedLogItem.branch?.name || 'Business-Wide' }}</div>
                  </div>
                </div>

                <!-- Old vs New Diffs -->
                <div v-if="selectedLogItem.old && Object.keys(selectedLogItem.old).length > 0" class="mb-3">
                  <label class="text-muted small fw-bold text-uppercase">Before Mutation</label>
                  <pre class="bg-light p-2 rounded-3 small font-monospace" style="max-height: 120px; overflow-y: auto;">{{ JSON.stringify(selectedLogItem.old, null, 2) }}</pre>
                </div>

                <div v-if="selectedLogItem.new && Object.keys(selectedLogItem.new).length > 0" class="mb-3">
                  <label class="text-muted small fw-bold text-uppercase">After Mutation</label>
                  <pre class="bg-light p-2 rounded-3 small font-monospace" style="max-height: 120px; overflow-y: auto;">{{ JSON.stringify(selectedLogItem.new, null, 2) }}</pre>
                </div>

                <!-- Structured Properties -->
                <div v-if="selectedLogItem.properties && Object.keys(selectedLogItem.properties).length > 0">
                  <label class="text-muted small fw-bold text-uppercase">Safe Metadata</label>
                  <pre class="bg-light p-2 rounded-3 small font-monospace" style="max-height: 140px; overflow-y: auto;">{{ JSON.stringify(selectedLogItem.properties, null, 2) }}</pre>
                </div>
              </div>
              <div v-else class="text-center py-4 text-muted small">
                <i class="bi bi-cursor-fill fs-3 opacity-50 d-block mb-2"></i>
                Select any audit log row to inspect its structured properties and mutation state.
              </div>
            </div>
          </div>

          <!-- Security Governance Health Check -->
          <div class="card shadow-sm border-0 rounded-4 overflow-hidden side-panel-card">
            <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
              <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-shield-lock text-success"></i>
                Governance Invariants
              </h6>
              <span class="badge bg-success-subtle text-success fw-bold px-2 py-1 rounded-pill" style="font-size: 0.75rem;">
                Enforced
              </span>
            </div>
            <div class="card-body p-3 d-flex flex-column gap-2.5">
              <div class="health-item">
                <div class="health-icon-box text-success">
                  <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="flex-grow-1">
                  <div class="health-title">Append-Only Audit Engine</div>
                  <div class="health-desc">API mutation endpoints strictly forbidden</div>
                </div>
                <span class="badge bg-success-subtle text-success" style="font-size: 0.72rem;">Read-Only</span>
              </div>

              <div class="health-item">
                <div class="health-icon-box text-success">
                  <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="flex-grow-1">
                  <div class="health-title">Strict Tenant Isolation</div>
                  <div class="health-desc">All audit queries resolved via active_business_id</div>
                </div>
                <span class="badge bg-success-subtle text-success" style="font-size: 0.72rem;">Strict</span>
              </div>

              <div class="health-item">
                <div class="health-icon-box text-success">
                  <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="flex-grow-1">
                  <div class="health-title">Last Owner Safety Guard</div>
                  <div class="health-desc">Cannot demote or deactivate final Business Owner</div>
                </div>
                <span class="badge bg-success-subtle text-success" style="font-size: 0.72rem;">Protected</span>
              </div>

              <div class="health-item">
                <div class="health-icon-box text-success">
                  <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="flex-grow-1">
                  <div class="health-title">Sensitive Data Redaction</div>
                  <div class="health-desc">Passwords, tokens, and secret credentials excluded</div>
                </div>
                <span class="badge bg-success-subtle text-success" style="font-size: 0.72rem;">Sanitized</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useToast } from 'vue-toastification';
import api from '../../api';

const toast = useToast();

const auditLogs = ref([]);
const branches = ref([]);
const isLoading = ref(false);
const isForbidden = ref(false);
const selectedLogItem = ref(null);

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0
});

const filters = reactive({
  search: '',
  log_name: '',
  event: '',
  branch_id: '',
  from_date: '',
  to_date: ''
});

const loadLogs = async (page = 1) => {
  isLoading.value = true;
  isForbidden.value = false;
  try {
    const params = {
      page,
      per_page: pagination.per_page
    };
    if (filters.search) params.search = filters.search;
    if (filters.log_name) params.log_name = filters.log_name;
    if (filters.event) params.event = filters.event;
    if (filters.branch_id) params.branch_id = filters.branch_id;
    if (filters.from_date) params.from_date = filters.from_date;
    if (filters.to_date) params.to_date = filters.to_date;

    const res = await api.get('/audit-logs', { params });
    auditLogs.value = res.data.data || [];
    if (res.data.meta) {
      pagination.current_page = res.data.meta.current_page;
      pagination.last_page = res.data.meta.last_page;
      pagination.per_page = res.data.meta.per_page;
      pagination.total = res.data.meta.total;
    }
  } catch (e) {
    if (e.response && e.response.status === 403) {
      isForbidden.value = true;
    } else {
      toast.error(e.response?.data?.message || 'Failed to load audit logs.');
    }
  } finally {
    isLoading.value = false;
  }
};

const loadBranches = async () => {
  try {
    const res = await api.get('/branches');
    branches.value = res.data || [];
  } catch (e) {
    // Branch managers or users without branch manage access may fail gracefully
    branches.value = [];
  }
};

onMounted(() => {
  loadLogs(1);
  loadBranches();
});

const selectLog = (log) => {
  selectedLogItem.value = log;
};

const resetFilters = () => {
  filters.search = '';
  filters.log_name = '';
  filters.event = '';
  filters.branch_id = '';
  filters.from_date = '';
  filters.to_date = '';
  loadLogs(1);
};

const formatDate = (isoString) => {
  if (!isoString) return 'N/A';
  return new Date(isoString).toLocaleDateString();
};

const formatTime = (isoString) => {
  if (!isoString) return '';
  return new Date(isoString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const getInitials = (name) => {
  if (!name) return 'U';
  const parts = name.split(' ');
  if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
  return name.slice(0, 2).toUpperCase();
};

const getSubjectName = (fullClass) => {
  if (!fullClass) return '';
  const parts = fullClass.split('\\');
  return parts[parts.length - 1];
};
</script>

<style scoped>
.security-view-container {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  color: #1e293b;
}

.header-icon-badge {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.35rem;
  box-shadow: 0 4px 12px rgba(37, 99, 255, 0.2);
}

.page-title {
  font-size: 1.85rem;
  color: #0f172a;
  letter-spacing: -0.5px;
}

.system-status-pill {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #166534;
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 0.82rem;
  font-weight: 700;
}

.pulse-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #16a34a;
  box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7);
  animation: pulse-green 2s infinite;
}

@keyframes pulse-green {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(22, 163, 74, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
}

.kpi-label {
  font-size: 0.78rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.kpi-icon-wrap {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
}

.kpi-value {
  font-size: 1.45rem;
  font-weight: 800;
  margin-bottom: 2px;
}

.kpi-subtext {
  font-size: 0.75rem;
  font-weight: 500;
}

.badge-count-pill {
  background: #f1f5f9;
  color: #475569;
  font-size: 0.82rem;
  font-weight: 700;
  padding: 6px 14px;
  border-radius: 20px;
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.8rem;
}

.avatar-admin {
  background: #eff6ff;
  color: #2563eb;
}

.avatar-system {
  background: #f1f5f9;
  color: #64748b;
}

.user-role-badge {
  font-size: 0.68rem;
  font-weight: 600;
}

.module-badge {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  color: #334155;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 6px;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 0.72rem;
  font-weight: 700;
}

.status-success {
  background: #dcfce7;
  color: #15803d;
}

.cursor-pointer {
  cursor: pointer;
}

.health-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px;
  background: #f8fafc;
  border-radius: 10px;
}

.health-icon-box {
  font-size: 1.15rem;
}

.health-title {
  font-size: 0.82rem;
  font-weight: 700;
  color: #1e293b;
}

.health-desc {
  font-size: 0.72rem;
  color: #64748b;
}
</style>
