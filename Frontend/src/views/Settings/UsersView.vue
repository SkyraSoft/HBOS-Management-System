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
              <option value="Branch Manager">Branch Manager</option>
              <option value="Salesperson">Salesperson</option>
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
                <th>Branch</th>
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
                <td>
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
                <td colspan="6" class="text-center py-5">
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
              <p class="text-muted small m-0">{{ isEditMode ? 'Update account details, branch, and role assignment' : 'Set up credentials and assign role permissions' }}</p>
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
            <div class="col-md-6">
              <label class="form-label small fw-bold text-dark">Assigned Branch</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-shop text-muted"></i></span>
                <select class="form-select border-start-0" v-model="userForm.branch_id">
                  <option :value="null">All Branches (Business Wide)</option>
                  <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
              </div>
            </div>

            <!-- Role Assignment -->
            <div class="col-md-6">
              <label class="form-label small fw-bold text-dark">System Role <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-shield-check text-muted"></i></span>
                <select class="form-select border-start-0" v-model="userForm.role" required>
                  <option value="Business Owner">Business Owner (Full Business Governance)</option>
                  <option value="Branch Manager">Branch Manager (Branch Operations)</option>
                  <option value="Salesperson">Salesperson (POS &amp; Counter Sales)</option>
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
import { ref, reactive, computed, onMounted } from 'vue';
import { useToast } from 'vue-toastification';
import api from '../../api';

const toast = useToast();

const users = ref([]);
const branches = ref([]);
const isLoading = ref(false);
const searchQuery = ref('');
const filterRole = ref('');
const filterStatus = ref('');

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
    branches.value = branchesRes.data || [];
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
  userForm.branch_id = user.branch_id || null;
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
</style>
