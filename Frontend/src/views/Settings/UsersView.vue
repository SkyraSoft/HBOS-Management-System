<template>
  <div class="users-view-container">
    <!-- TOP HEADER -->
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
      <div>
        <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
          SETTINGS <i class="bi bi-chevron-right mx-1"></i> <span class="text-primary">USERS &amp; ACCESS</span>
        </div>
        <h1 class="page-title fw-bolder mb-1" style="font-size: 1.85rem; color: #0f172a;">User Management</h1>
        <p class="text-muted m-0" style="font-size: 0.95rem; color: #64748b;">
          Manage employee accounts, role assignments, permissions, and security status.
        </p>
      </div>

      <div class="d-flex gap-2 align-items-center">
        <button 
          @click="openAddUserModal" 
          class="btn btn-primary fw-semibold px-4 py-2.5 rounded-3 d-flex align-items-center gap-2 shadow-sm" 
          style="background-color: #0f172a; border: none; font-size: 13.5px;"
          type="button"
        >
          <i class="bi bi-person-plus-fill"></i> ADD USER
        </button>
      </div>
    </div>

    <!-- NAVIGATION TABS -->
    <div class="d-flex align-items-center gap-2 mb-4 border-bottom pb-2">
      <button 
        type="button" 
        class="btn btn-sm px-3 py-2 rounded-3 fw-semibold"
        :class="activeTab === 'users' ? 'btn-dark' : 'btn-light text-muted'"
        @click="activeTab = 'users'"
      >
        <i class="bi bi-people-fill me-1"></i> User Directory
      </button>
      <button 
        v-if="isOwner"
        type="button" 
        class="btn btn-sm px-3 py-2 rounded-3 fw-semibold"
        :class="activeTab === 'audit' ? 'btn-dark' : 'btn-light text-muted'"
        @click="selectAuditTab"
      >
        <i class="bi bi-shield-check me-1"></i> Audit Trail
      </button>
    </div>

    <!-- USERS TAB CONTENT -->
    <div v-if="activeTab === 'users'">
      <!-- STATS OVERVIEW CARDS -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="card shadow-sm border-0 rounded-4 p-3 bg-white h-100">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="text-muted small fw-semibold">Total Accounts</span>
            <div class="icon-circle bg-primary-subtle text-primary">
              <i class="bi bi-people-fill"></i>
            </div>
          </div>
          <div class="fs-4 fw-bolder text-dark">{{ users.length }}</div>
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="card shadow-sm border-0 rounded-4 p-3 bg-white h-100">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="text-muted small fw-semibold">Active Users</span>
            <div class="icon-circle bg-success-subtle text-success">
              <i class="bi bi-check-circle-fill"></i>
            </div>
          </div>
          <div class="fs-4 fw-bolder text-success">{{ activeCount }}</div>
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="card shadow-sm border-0 rounded-4 p-3 bg-white h-100">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="text-muted small fw-semibold">Pending Verification</span>
            <div class="icon-circle bg-warning-subtle text-warning">
              <i class="bi bi-hourglass-split"></i>
            </div>
          </div>
          <div class="fs-4 fw-bolder text-warning">{{ pendingCount }}</div>
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="card shadow-sm border-0 rounded-4 p-3 bg-white h-100">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="text-muted small fw-semibold">Assigned Roles</span>
            <div class="icon-circle bg-info-subtle text-info">
              <i class="bi bi-shield-check"></i>
            </div>
          </div>
          <div class="fs-4 fw-bolder text-dark">{{ distinctRolesCount }} Roles</div>
        </div>
      </div>
    </div>

    <!-- USERS TABLE CONTAINER -->
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
      <div class="card-body p-4">
        <!-- FILTER BAR -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
          <div class="search-input-box">
            <i class="bi bi-search search-icon"></i>
            <input 
              type="text" 
              class="form-control form-control-sm search-field" 
              placeholder="Search users by name, email, phone or role..."
              v-model="searchQuery"
            />
            <button v-if="searchQuery" class="clear-search-btn" @click="searchQuery = ''">
              <i class="bi bi-x"></i>
            </button>
          </div>

          <div class="d-flex align-items-center gap-2 flex-wrap">
            <select class="form-select form-select-sm compact-filter-select" v-model="filterRole">
              <option value="">All Roles</option>
              <option value="Business Owner">Business Owner</option>
              <option value="Branch Manager" v-if="branches.length > 1">Branch Manager</option>
              <option value="Salesperson">Salesperson</option>
              <option value="Accountant">Accountant</option>
              <option value="Inventory Manager">Inventory Manager</option>
            </select>

            <select class="form-select form-select-sm compact-filter-select" v-model="filterStatus">
              <option value="">All Statuses</option>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
        </div>

        <!-- USERS TABLE -->
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-users-table">
            <thead>
              <tr>
                <th>User Profile</th>
                <th>Assigned Role</th>
                <th v-if="branches.length > 1">Branch</th>
                <th>Status</th>
                <th>Created</th>
                <th style="text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in filteredUsers" :key="user.id" class="user-row">
                <td>
                  <div class="user-cell d-flex align-items-center gap-3">
                    <div class="avatar-box" :style="{ backgroundColor: getAvatarColor(user.name) }">
                      {{ getInitials(user.name) }}
                    </div>
                    <div>
                      <div class="fw-bold text-dark user-name">{{ user.name }}</div>
                      <div class="text-muted small user-email">{{ user.email }}</div>
                      <div v-if="user.phone" class="text-secondary" style="font-size: 11.5px;">{{ user.phone }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="role-badge" :class="getRoleClass(user.role)">
                    <i class="bi me-1" :class="getRoleIcon(user.role)"></i>
                    {{ user.role }}
                  </span>
                </td>
                <td v-if="branches.length > 1">
                  <span class="text-dark small fw-medium">{{ user.branch_name || 'All Branches' }}</span>
                </td>
                <td>
                  <span class="status-pill" :class="getStatusClass(user.status)">
                    <span class="status-dot"></span>
                    {{ user.status }}
                  </span>
                </td>
                <td>
                  <span class="text-muted small">{{ user.created_at ? new Date(user.created_at).toLocaleDateString() : 'N/A' }}</span>
                </td>
                <td style="text-align: right;">
                  <div class="d-inline-flex align-items-center gap-1.5">
                    <button 
                      class="btn btn-outline-secondary btn-sm px-3 rounded-2 fw-semibold action-edit-btn" 
                      @click="openEditUserModal(user)"
                      type="button"
                    >
                      <i class="bi bi-pencil me-1"></i> Edit
                    </button>
                    <button 
                      :class="['btn btn-sm px-2.5 rounded-2', user.is_active ? 'btn-outline-danger' : 'btn-outline-success']"
                      @click="toggleUserStatus(user)"
                      :title="user.is_active ? 'Deactivate User' : 'Activate User'"
                      type="button"
                    >
                      <i :class="['bi', user.is_active ? 'bi-person-x-fill' : 'bi-person-check-fill']"></i>
                      {{ user.is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="filteredUsers.length === 0">
                <td :colspan="branches.length > 1 ? 6 : 5" class="text-center py-5">
                  <div class="empty-state">
                    <i class="bi bi-people fs-1 text-muted opacity-50 mb-2 d-block"></i>
                    <h6 class="fw-bold text-dark">No users found</h6>
                    <p class="text-muted small m-0">Try adjusting your search criteria or add a new user account.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    </div> <!-- END USERS TAB CONTENT -->

    <!-- AUDIT TRAIL TAB CONTENT (OWNER ONLY) -->
    <div v-else-if="activeTab === 'audit' && isOwner" class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white mb-4">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
          <div>
            <h5 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
              <i class="bi bi-shield-check text-primary"></i> System Activity &amp; Audit Trail
            </h5>
            <p class="text-muted small m-0">Immutable, tenant-scoped audit records for security, auth, and governance events.</p>
          </div>
          <button 
            class="btn btn-outline-secondary btn-sm px-3 rounded-3 d-flex align-items-center gap-1.5"
            @click="loadAuditLogs(auditPagination.current_page)"
            :disabled="isAuditLoading"
            type="button"
          >
            <i class="bi bi-arrow-clockwise" :class="{ 'spin-icon': isAuditLoading }"></i>
            Refresh Logs
          </button>
        </div>

        <!-- AUDIT FILTERS -->
        <div class="row g-2 mb-4 align-items-center bg-light p-3 rounded-3">
          <div class="col-md-3">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
              <input 
                type="text" 
                class="form-control border-start-0" 
                placeholder="Search description or event..." 
                v-model="auditFilters.search"
                @keyup.enter="loadAuditLogs(1)"
              />
            </div>
          </div>
          <div class="col-md-2">
            <select class="form-select form-select-sm" v-model="auditFilters.event" @change="loadAuditLogs(1)">
              <option value="">All Events</option>
              <option value="auth_login">Login (auth_login)</option>
              <option value="auth_logout">Logout (auth_logout)</option>
              <option value="branch_created">Branch Created</option>
              <option value="branch_updated">Branch Updated</option>
              <option value="branch_status_changed">Branch Status</option>
              <option value="user_invited">User Invited</option>
              <option value="user_activated_deactivated">User Status</option>
              <option value="user_role_changed">Role Changed</option>
              <option value="branch_membership_changed">Branch Membership</option>
              <option value="business_profile_changed">Business Profile</option>
            </select>
          </div>
          <div class="col-md-2">
            <input 
              type="date" 
              class="form-control form-control-sm" 
              v-model="auditFilters.from_date" 
              @change="loadAuditLogs(1)"
              title="From Date" 
            />
          </div>
          <div class="col-md-2">
            <input 
              type="date" 
              class="form-control form-control-sm" 
              v-model="auditFilters.to_date" 
              @change="loadAuditLogs(1)"
              title="To Date" 
            />
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary btn-sm flex-fill rounded-2" @click="loadAuditLogs(1)" type="button">
              Apply Filter
            </button>
            <button class="btn btn-outline-secondary btn-sm rounded-2" @click="resetAuditFilters" type="button" title="Reset Filters">
              Reset
            </button>
          </div>
        </div>

        <!-- LOADING STATE -->
        <div v-if="isAuditLoading" class="text-center py-5">
          <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
          <span class="text-muted small">Loading immutable audit logs...</span>
        </div>

        <!-- ERROR STATE -->
        <div v-else-if="auditError" class="alert alert-danger rounded-3 p-3 text-center my-3">
          <i class="bi bi-exclamation-octagon-fill me-2"></i> {{ auditError }}
        </div>

        <!-- AUDIT TABLE -->
        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-users-table">
            <thead>
              <tr>
                <th>Timestamp</th>
                <th>Event / Action</th>
                <th>Actor</th>
                <th v-if="branches.length > 1">Branch</th>
                <th>Subject / Target</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="log in auditLogs" :key="log.id">
                <td class="text-nowrap small text-muted">
                  {{ new Date(log.created_at).toLocaleString() }}
                </td>
                <td>
                  <span class="badge rounded-pill bg-light text-dark border px-2.5 py-1.5 font-monospace" style="font-size: 11px;">
                    {{ log.event || log.log_name || 'action' }}
                  </span>
                </td>
                <td>
                  <div class="fw-semibold text-dark small">{{ log.causer?.name || 'System' }}</div>
                  <div v-if="log.causer?.email" class="text-muted" style="font-size: 11px;">{{ log.causer.email }}</div>
                </td>
                <td v-if="branches.length > 1">
                  <span class="badge bg-secondary-subtle text-secondary small">
                    {{ log.branch?.name || (log.branch?.code ? log.branch.code : 'Central / All') }}
                  </span>
                </td>
                <td class="small text-muted">
                  {{ log.subject_type ? (log.subject_type.split('\\').pop() + (log.subject_id ? ' #' + log.subject_id : '')) : (log.log_name || '-') }}
                </td>
                <td class="small text-dark">
                  {{ log.description || '-' }}
                </td>
              </tr>
              <tr v-if="auditLogs.length === 0">
                <td :colspan="branches.length > 1 ? 6 : 5" class="text-center py-5">
                  <div class="empty-state">
                    <i class="bi bi-shield-x fs-1 text-muted opacity-50 mb-2 d-block"></i>
                    <h6 class="fw-bold text-dark">No audit records found</h6>
                    <p class="text-muted small m-0">No recorded governance or activity logs match the selected criteria.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- PAGINATION -->
        <div v-if="auditPagination.total > 0" class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top flex-wrap gap-2">
          <div class="text-muted small">
            Showing {{ ((auditPagination.current_page - 1) * auditPagination.per_page) + 1 }} to {{ Math.min(auditPagination.current_page * auditPagination.per_page, auditPagination.total) }} of {{ auditPagination.total }} logs
          </div>
          <div class="d-flex gap-1.5">
            <button 
              class="btn btn-sm btn-outline-secondary rounded-2 px-3" 
              :disabled="auditPagination.current_page <= 1 || isAuditLoading"
              @click="loadAuditLogs(auditPagination.current_page - 1)"
              type="button"
            >
              Previous
            </button>
            <span class="btn btn-sm btn-light border disabled">
              Page {{ auditPagination.current_page }} of {{ auditPagination.last_page }}
            </span>
            <button 
              class="btn btn-sm btn-outline-secondary rounded-2 px-3" 
              :disabled="auditPagination.current_page >= auditPagination.last_page || isAuditLoading"
              @click="loadAuditLogs(auditPagination.current_page + 1)"
              type="button"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ADD / EDIT USER MODAL -->
    <div v-if="showUserModal" class="user-modal-overlay" @click.self="closeUserModal">
      <div class="user-modal-dialog shadow-lg">
        <div class="user-modal-header">
          <div class="d-flex align-items-center gap-2.5">
            <div class="modal-icon-badge" :class="isEditMode ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success'">
              <i class="bi" :class="isEditMode ? 'bi-pencil-square' : 'bi-person-plus-fill'"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold text-dark m-0">{{ isEditMode ? 'Edit Staff Account' : 'Create Staff User' }}</h5>
              <p class="text-muted small m-0">{{ isEditMode ? (branches.length > 1 ? 'Update account details, branch, and role assignment' : 'Update account details and role assignment') : 'Set up credentials and assign role permissions' }}</p>
            </div>
          </div>
          <button class="modal-close-btn" @click="closeUserModal" type="button">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <form @submit.prevent="saveUser" class="user-modal-body">
          <div class="row g-3">
            <!-- Full Name -->
            <div class="col-md-6">
              <label class="form-label small fw-bold text-dark">Full Name <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                <input 
                  type="text" 
                  class="form-control border-start-0" 
                  placeholder="e.g. Hamza Malik" 
                  v-model="userForm.name" 
                  required
                />
              </div>
            </div>

            <!-- Email Address -->
            <div class="col-md-6">
              <label class="form-label small fw-bold text-dark">Email Address <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                <input 
                  type="email" 
                  class="form-control border-start-0" 
                  placeholder="name@hbos.com" 
                  v-model="userForm.email" 
                  required
                />
              </div>
            </div>

            <!-- Branch Assignment -->
            <div v-if="branches.length > 1" class="col-md-6">
              <label class="form-label small fw-bold text-dark">Assigned Branch</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-shop text-muted"></i></span>
                <select class="form-select border-start-0" v-model="userForm.branch_id">
                  <option :value="null" v-if="!['Salesperson', 'Branch Manager'].includes(userForm.role)">All Branches (Business Wide)</option>
                  <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
              </div>
            </div>

            <!-- Role Assignment -->
            <div :class="branches.length > 1 ? 'col-md-6' : 'col-md-12'">
              <label class="form-label small fw-bold text-dark">System Role <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-shield-check text-muted"></i></span>
                <select class="form-select border-start-0" v-model="userForm.role" required>
                  <option v-if="branches.length > 1" value="Branch Manager">Branch Manager</option>
                  <option value="Salesperson">Cashier / Salesperson</option>
                  <!-- Compatibility Role Preservation: Preserve existing role if legacy user is being viewed/edited -->
                  <option 
                    v-if="userForm.role && !['Branch Manager', 'Salesperson'].includes(userForm.role)" 
                    :value="userForm.role"
                  >
                    {{ userForm.role }} (Preserved Role)
                  </option>
                </select>
              </div>
            </div>

            <!-- Password -->
            <div class="col-md-6">
              <label class="form-label small fw-bold text-dark">{{ isEditMode ? 'New Password (Optional)' : 'Account Password *' }}</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                <input 
                  type="password" 
                  class="form-control border-start-0" 
                  :placeholder="isEditMode ? 'Leave blank to keep current' : 'Min. 8 characters'" 
                  v-model="userForm.password" 
                  :required="!isEditMode"
                />
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-bold text-dark">{{ isEditMode ? 'Confirm New Password' : 'Confirm Password *' }}</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-shield-lock text-muted"></i></span>
                <input 
                  type="password" 
                  class="form-control border-start-0" 
                  placeholder="Re-enter password" 
                  v-model="userForm.confirmPassword" 
                  :required="!isEditMode && !!userForm.password"
                />
              </div>
            </div>
          </div>

          <!-- FOOTER ACTIONS -->
          <div class="d-flex justify-content-end align-items-center gap-2.5 mt-4 pt-3 border-top">
            <button class="btn btn-light px-4 py-2 rounded-3 fw-semibold border" type="button" @click="closeUserModal">
              Cancel
            </button>
            <button class="btn btn-primary px-4 py-2 rounded-3 fw-semibold d-flex align-items-center gap-2" type="submit" style="background-color: #0f172a; border: none;">
              <i class="bi" :class="isEditMode ? 'bi-check2' : 'bi-person-plus-fill'"></i>
              {{ isEditMode ? 'Save Changes' : 'Create Account' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import { useToast } from 'vue-toastification';
import api from '../../api';
import { useAuthStore } from '../../stores/auth';

const toast = useToast();
const authStore = useAuthStore();

const activeTab = ref('users');
const isOwner = computed(() => {
  const r = authStore.user?.role || (authStore.user?.roles && authStore.user.roles[0]?.name);
  return r === 'Business Owner';
});

const selectAuditTab = () => {
  activeTab.value = 'audit';
  if (auditLogs.value.length === 0) {
    loadAuditLogs(1);
  }
};

const users = ref([]);
const branches = ref([]);
const isLoading = ref(false);
const searchQuery = ref('');
const filterRole = ref('');
const filterStatus = ref('');

// --- AUDIT TRAIL STATE & METHODS ---
const auditLogs = ref([]);
const isAuditLoading = ref(false);
const auditError = ref(null);
const auditPagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 20
});
const auditFilters = reactive({
  search: '',
  event: '',
  from_date: '',
  to_date: '',
  branch_id: ''
});

const loadAuditLogs = async (page = 1) => {
  if (!isOwner.value) return;
  isAuditLoading.value = true;
  auditError.value = null;
  try {
    const params = {
      page,
      per_page: auditPagination.per_page
    };
    if (auditFilters.search) params.search = auditFilters.search;
    if (auditFilters.event && auditFilters.event !== 'All') params.event = auditFilters.event;
    if (auditFilters.from_date) params.from_date = auditFilters.from_date;
    if (auditFilters.to_date) params.to_date = auditFilters.to_date;
    if (auditFilters.branch_id) params.branch_id = auditFilters.branch_id;

    const res = await api.get('/audit-logs', { params });
    const responseData = res.data;
    auditLogs.value = responseData.data || [];
    auditPagination.current_page = responseData.current_page || 1;
    auditPagination.last_page = responseData.last_page || 1;
    auditPagination.total = responseData.total || 0;
  } catch (err) {
    console.error('Failed to load audit logs:', err);
    auditError.value = err.response?.data?.message || 'Failed to load audit logs. Please try again.';
  } finally {
    isAuditLoading.value = false;
  }
};

const resetAuditFilters = () => {
  auditFilters.search = '';
  auditFilters.event = '';
  auditFilters.from_date = '';
  auditFilters.to_date = '';
  auditFilters.branch_id = '';
  loadAuditLogs(1);
};

const showUserModal = ref(false);
const isEditMode = ref(false);
const editingUserId = ref(null);

const userForm = reactive({
  name: '',
  email: '',
  role: 'Salesperson',
  branch_id: null,
  password: '',
  confirmPassword: ''
});

const loadData = async () => {
  isLoading.value = true;
  try {
    const [usersRes, branchesRes] = await Promise.all([
      api.get('/users'),
      api.get('/branches')
    ]);
    users.value = (usersRes.data?.data || []).map(u => ({
      ...u,
      status: u.is_active ? 'Active' : 'Inactive'
    }));
    branches.value = branchesRes.data?.data || (Array.isArray(branchesRes.data) ? branchesRes.data : []);
  } catch (e) {
    console.error('Failed to load user accounts:', e);
    toast.error('Failed to load user accounts.');
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  loadData();
});

// Computed metrics
const activeCount = computed(() => users.value.filter(u => u.is_active).length);
const pendingCount = computed(() => users.value.filter(u => !u.is_active).length);
const distinctRolesCount = computed(() => new Set(users.value.map(u => u.role)).size);

// Filtered list
const filteredUsers = computed(() => {
  return users.value.filter(u => {
    const matchesSearch = !searchQuery.value || 
      u.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      u.email.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      u.role.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (u.branch_name && u.branch_name.toLowerCase().includes(searchQuery.value.toLowerCase()));

    const matchesRole = !filterRole.value || u.role === filterRole.value;
    const matchesStatus = !filterStatus.value || u.status === filterStatus.value;

    return matchesSearch && matchesRole && matchesStatus;
  });
});

// Watch role changes to enforce branch selection for Salesperson and Branch Manager
watch(() => userForm.role, (newRole) => {
  if (['Salesperson', 'Branch Manager'].includes(newRole) && !userForm.branch_id && branches.value.length > 0) {
    userForm.branch_id = branches.value[0].id;
  }
});

// Modal handlers
const openAddUserModal = () => {
  isEditMode.value = false;
  editingUserId.value = null;
  userForm.name = '';
  userForm.email = '';
  userForm.role = 'Salesperson';
  userForm.branch_id = branches.value.length > 0 ? branches.value[0].id : null;
  userForm.password = '';
  userForm.confirmPassword = '';
  showUserModal.value = true;
};

const openEditUserModal = (user) => {
  isEditMode.value = true;
  editingUserId.value = user.id;
  userForm.name = user.name;
  userForm.email = user.email;
  userForm.role = user.role;
  userForm.branch_id = user.branch_id || (['Salesperson', 'Branch Manager'].includes(user.role) && branches.value.length > 0 ? branches.value[0].id : null);
  userForm.password = '';
  userForm.confirmPassword = '';
  showUserModal.value = true;
};

const closeUserModal = () => {
  showUserModal.value = false;
};

const saveUser = async () => {
  if (!userForm.name || !userForm.email) {
    toast.error('Please provide a full name and email address.');
    return;
  }

  if (['Salesperson', 'Branch Manager'].includes(userForm.role) && !userForm.branch_id) {
    toast.error(`Please select an assigned branch for ${userForm.role}.`);
    return;
  }

  if (!isEditMode.value) {
    if (!userForm.password || userForm.password.length < 8) {
      toast.error('Password must be at least 8 characters.');
      return;
    }
    if (userForm.password !== userForm.confirmPassword) {
      toast.error('Passwords do not match.');
      return;
    }
  } else if (userForm.password) {
    if (userForm.password.length < 8) {
      toast.error('Password must be at least 8 characters.');
      return;
    }
    if (userForm.password !== userForm.confirmPassword) {
      toast.error('Passwords do not match.');
      return;
    }
  }

  try {
    if (isEditMode.value) {
      const payload = {
        name: userForm.name,
        email: userForm.email,
        role: userForm.role,
        branch_id: userForm.branch_id || null
      };
      if (userForm.password) {
        payload.password = userForm.password;
        payload.password_confirmation = userForm.confirmPassword;
      }
      await api.put(`/users/${editingUserId.value}`, payload);
      toast.success(`User "${userForm.name}" updated successfully!`);
    } else {
      await api.post('/users', {
        name: userForm.name,
        email: userForm.email,
        role: userForm.role,
        branch_id: userForm.branch_id || null,
        password: userForm.password,
        password_confirmation: userForm.confirmPassword
      });
      toast.success(`Staff user "${userForm.name}" created successfully!`);
    }
    closeUserModal();
    await loadData();
  } catch (e) {
    toast.error(e.response?.data?.message || 'Failed to save user.');
  }
};

const toggleUserStatus = async (user) => {
  const newStatus = !user.is_active;
  const actionText = newStatus ? 'activate' : 'deactivate';
  if (!confirm(`Are you sure you want to ${actionText} user account "${user.name}"?`)) return;

  try {
    await api.post(`/users/${user.id}/status`, { is_active: newStatus });
    toast.success(`User "${user.name}" ${newStatus ? 'activated' : 'deactivated'}.`);
    await loadData();
  } catch (e) {
    toast.error(e.response?.data?.message || `Failed to ${actionText} user.`);
  }
};

// UI Helpers
const getInitials = (name) => {
  if (!name) return 'U';
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .substring(0, 2)
    .toUpperCase();
};

const getAvatarColor = (name) => {
  const colors = ['#0284c7', '#059669', '#7c3aed', '#d97706', '#dc2626', '#4f46e5'];
  let hash = 0;
  for (let i = 0; i < (name || '').length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash);
  }
  return colors[Math.abs(hash) % colors.length];
};

const getRoleClass = (role) => {
  switch (role) {
    case 'Business Owner':
      return 'role-admin';
    case 'Branch Manager':
      return 'role-manager';
    case 'Salesperson':
      return 'role-cashier';
    case 'Accountant':
      return 'role-accountant';
    case 'Inventory Manager':
      return 'role-inventory';
    default:
      return 'role-default';
  }
};

const getRoleIcon = (role) => {
  switch (role) {
    case 'Business Owner':
      return 'bi-shield-shaded';
    case 'Branch Manager':
      return 'bi-briefcase';
    case 'Salesperson':
      return 'bi-person';
    case 'Accountant':
      return 'bi-calculator';
    case 'Inventory Manager':
      return 'bi-boxes';
    default:
      return 'bi-person';
  }
};

const getStatusClass = (status) => {
  switch (status) {
    case 'Active': return 'status-active';
    case 'Inactive': return 'status-inactive';
    default: return 'status-default';
  }
};
</script>

<style scoped>
.users-view-container {
  width: 100%;
}

.icon-circle {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
}

/* SEARCH & FILTERS */
.search-input-box {
  position: relative;
  min-width: 300px;
  flex: 1;
  max-width: 450px;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 14px;
}

.search-field {
  height: 38px;
  padding-left: 36px;
  padding-right: 32px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  font-size: 13.5px;
}

.search-field:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.clear-search-btn {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  border: none;
  background: transparent;
  color: #94a3b8;
  cursor: pointer;
  padding: 0;
}

.compact-filter-select {
  height: 38px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  font-size: 13px;
  font-weight: 500;
  color: #334155;
}

/* TABLE */
.custom-users-table th {
  background: #f8fafc;
  padding: 13px 18px;
  font-size: 11.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #64748b;
  border-bottom: 1px solid #e2e8f0;
}

.custom-users-table td {
  padding: 14px 18px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 13.5px;
  vertical-align: middle;
}

.user-row:hover {
  background: #f8fafc;
}

.avatar-box {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 14px;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.user-name {
  font-size: 14px;
  line-height: 1.25;
}

.role-badge {
  display: inline-flex;
  align-items: center;
  font-size: 12px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 6px;
}

.role-admin { background: #fef2f2; color: #dc2626; }
.role-manager { background: #e0e7ff; color: #4338ca; }
.role-cashier { background: #ecfdf5; color: #059669; }
.role-inventory { background: #fef3c7; color: #b45309; }
.role-accountant { background: #f3e8ff; color: #7e22ce; }
.role-default { background: #f1f5f9; color: #475569; }

.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 20px;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.status-active { background: #ecfdf5; color: #059669; }
.status-pending { background: #fef3c7; color: #b45309; }
.status-inactive { background: #f1f5f9; color: #94a3b8; }

.action-edit-btn {
  height: 32px;
  font-size: 12.5px;
  background: #ffffff;
  border-color: #cbd5e1;
  color: #334155;
}

.action-edit-btn:hover {
  background: #f1f5f9;
  color: #0f172a;
  border-color: #94a3b8;
}

.action-delete-btn {
  height: 32px;
}

/* MODAL OVERLAY */
.user-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(4px);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  animation: modalFadeIn 0.2s ease-out;
}

@keyframes modalFadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.user-modal-dialog {
  background: #ffffff;
  border-radius: 16px;
  width: 100%;
  max-width: 650px;
  overflow: hidden;
  animation: modalSlideUp 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalSlideUp {
  from { transform: translateY(12px) scale(0.98); opacity: 0; }
  to { transform: translateY(0) scale(1); opacity: 1; }
}

.user-modal-header {
  padding: 18px 24px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #f8fafc;
}

.modal-icon-badge {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.modal-close-btn {
  background: transparent;
  border: none;
  color: #64748b;
  font-size: 16px;
  cursor: pointer;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
}

.modal-close-btn:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.user-modal-body {
  padding: 24px;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
.spin-icon {
  animation: spin 1s linear infinite;
  display: inline-block;
}
</style>
