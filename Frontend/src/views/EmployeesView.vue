<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import api from '../api';
import { useToast } from 'vue-toastification';

const toast = useToast();
const employees = ref([]);
const branches = ref([]);
const isLoading = ref(true);
const error = ref(null);

const showAddModal = ref(false);
const showTokenModal = ref(false);
const generatedInvitation = ref(null);
const isEditing = ref(false);
const editId = ref(null);
const isSubmitting = ref(false);
const searchQuery = ref('');
const filterRole = ref('');
const filterStatus = ref('');

const form = reactive({
  name: '',
  email: '',
  phone: '',
  role: 'Salesperson',
  branch_id: null,
  employee_code: '',
  job_title: '',
  hire_date: '',
  send_invitation: true,
  password: '',
  password_confirmation: '',
});

const loadInitialData = async () => {
  isLoading.value = true;
  error.value = null;
  try {
    const [usersRes, branchesRes] = await Promise.all([
      api.get('/users'),
      api.get('/branches')
    ]);
    employees.value = usersRes.data.data || [];
    branches.value = branchesRes.data.data || [];
  } catch (err) {
    console.error('Failed to load employee governance data', err);
    error.value = 'Failed to load employee directory. Please check network connectivity.';
  } finally {
    isLoading.value = false;
  }
};

const filteredEmployees = computed(() => {
  return employees.value.filter(emp => {
    const matchesSearch = !searchQuery.value || 
      (emp.name && emp.name.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
      (emp.email && emp.email.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
      (emp.phone && emp.phone.includes(searchQuery.value)) ||
      (emp.employee_code && emp.employee_code.toLowerCase().includes(searchQuery.value.toLowerCase()));
    
    const empRole = emp.role || (emp.roles && emp.roles[0]?.name) || '';
    const matchesRole = !filterRole.value || empRole === filterRole.value;
    
    const matchesStatus = !filterStatus.value || 
      (filterStatus.value === 'active' && emp.is_active) ||
      (filterStatus.value === 'inactive' && !emp.is_active);

    return matchesSearch && matchesRole && matchesStatus;
  });
});

const openAddModal = () => {
  isEditing.value = false;
  editId.value = null;
  form.name = '';
  form.email = '';
  form.phone = '';
  form.role = 'Salesperson';
  form.branch_id = branches.value.length > 0 ? branches.value[0].id : null;
  form.employee_code = '';
  form.job_title = '';
  form.hire_date = new Date().toISOString().substring(0, 10);
  form.send_invitation = true;
  form.password = '';
  form.password_confirmation = '';
  showAddModal.value = true;
};

const openEditModal = (emp) => {
  isEditing.value = true;
  editId.value = emp.id;
  form.name = emp.name || '';
  form.email = emp.email || '';
  form.phone = emp.phone || '';
  form.role = emp.role || (emp.roles && emp.roles[0]?.name) || 'Salesperson';
  form.branch_id = emp.branch_id || (emp.branches && emp.branches[0]?.id) || null;
  form.employee_code = emp.employee_code || '';
  form.job_title = emp.job_title || '';
  form.hire_date = emp.hire_date || '';
  form.send_invitation = false;
  form.password = '';
  form.password_confirmation = '';
  showAddModal.value = true;
};

const closeAddModal = () => {
  showAddModal.value = false;
};

const submitEmployeeForm = async () => {
  isSubmitting.value = true;
  try {
    if (isEditing.value) {
      const payload = {
        name: form.name,
        email: form.email,
        phone: form.phone,
        role: form.role,
        branch_id: form.branch_id,
        employee_code: form.employee_code,
        job_title: form.job_title,
        hire_date: form.hire_date,
      };
      if (form.password) {
        payload.password = form.password;
        payload.password_confirmation = form.password_confirmation;
      }
      await api.put(`/users/${editId.value}`, payload);
      toast.success('Employee updated successfully');
      closeAddModal();
      await loadInitialData();
    } else {
      const payload = {
        name: form.name,
        email: form.email,
        phone: form.phone,
        role: form.role,
        branch_id: form.branch_id,
        employee_code: form.employee_code,
        job_title: form.job_title,
        hire_date: form.hire_date,
        send_invitation: form.send_invitation,
      };
      if (!form.send_invitation) {
        payload.password = form.password;
        payload.password_confirmation = form.password_confirmation;
      }
      const res = await api.post('/users', payload);
      toast.success('Employee created successfully');
      closeAddModal();
      
      if (res.data.invitation_token) {
        generatedInvitation.value = {
          name: form.name,
          email: form.email,
          token: res.data.invitation_token,
          setup_url: window.location.origin + '/setup-password?token=' + res.data.invitation_token
        };
        showTokenModal.value = true;
      }
      
      await loadInitialData();
    }
  } catch (err) {
    console.error('Failed to save employee', err);
    const msg = err.response?.data?.message || 'Failed to save employee profile';
    toast.error(msg);
  } finally {
    isSubmitting.value = false;
  }
};

const toggleEmployeeStatus = async (emp) => {
  const newStatus = !emp.is_active;
  const actionText = newStatus ? 'activate' : 'deactivate';
  const confirmMsg = newStatus 
    ? `Are you sure you want to reactivate ${emp.name}? They will be able to log in again.`
    : `Are you sure you want to deactivate ${emp.name}? All active sessions and tokens will be immediately revoked. Historical sales and transactions will be strictly preserved.`;

  if (!confirm(confirmMsg)) return;

  try {
    await api.post(`/users/${emp.id}/status`, { is_active: newStatus });
    toast.success(`Employee ${newStatus ? 'activated' : 'deactivated'} successfully`);
    await loadInitialData();
  } catch (err) {
    console.error('Failed to update status', err);
    toast.error(err.response?.data?.message || `Failed to ${actionText} employee`);
  }
};

const copyInvitationLink = () => {
  if (generatedInvitation.value?.setup_url) {
    navigator.clipboard.writeText(generatedInvitation.value.setup_url);
    toast.success('Invitation link copied to clipboard!');
  }
};

const getRoleBadgeClass = (role) => {
  switch (role) {
    case 'Business Owner': return 'bg-dark text-white';
    case 'Branch Manager': return 'bg-primary text-white';
    case 'Accountant': return 'bg-success text-white';
    case 'Inventory Manager': return 'bg-warning text-dark';
    case 'Salesperson': return 'bg-info text-dark';
    default: return 'bg-secondary text-white';
  }
};

onMounted(loadInitialData);
</script>

<template>
  <div class="employees-page-wrapper p-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
      <div>
        <div class="text-uppercase text-muted fw-bold small mb-1" style="letter-spacing: 0.5px;">
          WORKFORCE &amp; ORGANIZATION
        </div>
        <h2 class="fw-bold mb-1 text-dark">Employee Management</h2>
        <p class="text-muted mb-0">Individual staff logins, {{ branches.length > 1 ? 'branch assignments, ' : '' }}role governance, and security lifecycles.</p>
      </div>
      <button class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold" @click="openAddModal">
        <i class="bi bi-person-plus-fill me-2"></i> Add Employee
      </button>
    </div>

    <!-- Filters & Search -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 p-3 bg-white">
      <div class="row g-3 align-items-center">
        <div class="col-md-5">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input 
              v-model="searchQuery" 
              type="text" 
              class="form-control bg-light border-start-0" 
              placeholder="Search by name, email, phone, or code..." 
            />
          </div>
        </div>
        <div class="col-md-3">
          <select v-model="filterRole" class="form-select bg-light">
            <option value="">All Roles</option>
            <option value="Business Owner">Business Owner</option>
            <option value="Branch Manager" v-if="branches.length > 1">Branch Manager</option>
            <option value="Salesperson">Salesperson</option>
            <option value="Accountant">Accountant</option>
            <option value="Inventory Manager">Inventory Manager</option>
          </select>
        </div>
        <div class="col-md-2">
          <select v-model="filterStatus" class="form-select bg-light">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="col-md-2 text-end text-muted small fw-semibold">
          Total: {{ filteredEmployees.length }} Employees
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading employees...</span>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="alert alert-danger rounded-4 p-4 shadow-sm">
      <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ error }}
    </div>

    <!-- Employee Table -->
    <div v-else class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-4">Employee</th>
              <th>Role</th>
              <th v-if="branches.length > 1">Branch</th>
              <th>Contact</th>
              <th>Status</th>
              <th>Joined</th>
              <th class="text-end pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="emp in filteredEmployees" :key="emp.id">
              <td class="ps-4">
                <div class="d-flex align-items-center gap-3">
                  <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    {{ (emp.name || 'E').substring(0, 2).toUpperCase() }}
                  </div>
                  <div>
                    <div class="fw-bold text-dark">{{ emp.name }}</div>
                    <div class="text-muted small">{{ emp.email }}</div>
                    <div v-if="emp.employee_code" class="badge bg-light text-secondary border" style="font-size: 10px;">{{ emp.employee_code }}</div>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge rounded-pill px-3 py-2" :class="getRoleBadgeClass(emp.role || (emp.roles && emp.roles[0]?.name))">
                  {{ emp.role || (emp.roles && emp.roles[0]?.name) || 'Staff' }}
                </span>
                <div v-if="emp.job_title" class="text-muted small mt-1">{{ emp.job_title }}</div>
              </td>
              <td v-if="branches.length > 1">
                <span v-if="emp.branch" class="fw-semibold text-secondary">{{ emp.branch.name }}</span>
                <span v-else-if="emp.branches && emp.branches.length > 0" class="fw-semibold text-secondary">
                  {{ emp.branches.map(b => b.name).join(', ') }}
                </span>
                <span v-else class="text-muted small">{{ branches && branches.length > 1 ? 'All Branches' : 'Main Store' }}</span>
              </td>
              <td>
                <div class="text-dark">{{ emp.phone || '-' }}</div>
              </td>
              <td>
                <span v-if="emp.is_active" class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1.5">
                  <i class="bi bi-check-circle-fill me-1"></i> Active
                </span>
                <span v-else class="badge bg-danger-subtle text-danger rounded-pill px-2.5 py-1.5">
                  <i class="bi bi-slash-circle me-1"></i> Deactivated
                </span>
              </td>
              <td class="text-secondary small">
                {{ emp.hire_date || (emp.created_at ? emp.created_at.substring(0, 10) : '-') }}
              </td>
              <td class="text-end pe-4">
                <button class="btn btn-sm btn-light rounded-circle me-2" title="Edit Employee" @click="openEditModal(emp)">
                  <i class="bi bi-pencil"></i>
                </button>
                <button 
                  class="btn btn-sm rounded-circle" 
                  :class="emp.is_active ? 'btn-light text-danger' : 'btn-light text-success'"
                  :title="emp.is_active ? 'Deactivate Employee (Preserves History)' : 'Reactivate Employee'" 
                  @click="toggleEmployeeStatus(emp)"
                >
                  <i :class="emp.is_active ? 'bi bi-person-x' : 'bi bi-person-check'"></i>
                </button>
              </td>
            </tr>
            <tr v-if="filteredEmployees.length === 0">
              <td :colspan="branches.length > 1 ? 7 : 6" class="text-center text-muted py-5">
                <i class="bi bi-people display-6 d-block mb-2 text-secondary"></i>
                No matching employee accounts found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add/Edit Employee Modal -->
    <div v-if="showAddModal" class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1050;">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header border-0 bg-light px-4 py-3">
            <h5 class="modal-title fw-bold text-dark m-0">{{ isEditing ? 'Edit Employee Profile' : 'Add Individual Employee Login' }}</h5>
            <button type="button" class="btn-close" @click="closeAddModal"></button>
          </div>
          <form @submit.prevent="submitEmployeeForm">
            <div class="modal-body px-4 py-3">
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-secondary">Full Name <span class="text-danger">*</span></label>
                  <input v-model="form.name" type="text" class="form-control rounded-3" placeholder="e.g. Tariq Mehmood" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-secondary">Email Address (Personal Login) <span class="text-danger">*</span></label>
                  <input v-model="form.email" type="email" class="form-control rounded-3" placeholder="e.g. tariq@business.com" required />
                </div>
              </div>

              <div class="row g-3 mb-3">
                <div :class="branches.length > 1 ? 'col-md-6' : 'col-md-12'">
                  <label class="form-label small fw-semibold text-secondary">System Role <span class="text-danger">*</span></label>
                  <select v-model="form.role" class="form-select rounded-3" required>
                    <option v-if="branches.length > 1" value="Branch Manager">Branch Manager</option>
                    <option value="Salesperson">Cashier / Salesperson</option>
                    <!-- Compatibility Role Preservation: Preserve existing role if legacy user is being viewed/edited -->
                    <option 
                      v-if="form.role && !['Branch Manager', 'Salesperson'].includes(form.role)" 
                      :value="form.role"
                    >
                      {{ form.role }} (Preserved Role)
                    </option>
                  </select>
                </div>
                <div v-if="branches.length > 1" class="col-md-6">
                  <label class="form-label small fw-semibold text-secondary">Primary Branch <span class="text-danger">*</span></label>
                  <select v-model="form.branch_id" class="form-select rounded-3">
                    <option :value="null">All Branches</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }} ({{ b.code || 'BR' }})</option>
                  </select>
                </div>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-4">
                  <label class="form-label small fw-semibold text-secondary">Phone Number</label>
                  <input v-model="form.phone" type="text" class="form-control rounded-3" placeholder="+92..." />
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold text-secondary">Employee Code</label>
                  <input v-model="form.employee_code" type="text" class="form-control rounded-3" placeholder="e.g. EMP-001" />
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold text-secondary">Job Title</label>
                  <input v-model="form.job_title" type="text" class="form-control rounded-3" placeholder="e.g. Senior Cashier" />
                </div>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-secondary">Hire Date</label>
                  <input v-model="form.hire_date" type="date" class="form-control rounded-3" />
                </div>
              </div>

              <!-- Activation / Password Section -->
              <div v-if="!isEditing" class="border rounded-3 p-3 bg-light mt-3">
                <div class="form-check form-switch mb-2">
                  <input v-model="form.send_invitation" class="form-check-input" type="checkbox" id="sendInviteSwitch">
                  <label class="form-check-label fw-semibold text-dark" for="sendInviteSwitch">
                    Use Secure One-Time Invitation Setup (Recommended)
                  </label>
                </div>
                <p class="text-muted small mb-0" v-if="form.send_invitation">
                  A cryptographically secure single-use invitation token will be generated. The employee sets their own password during first activation.
                </p>
                <div v-else class="row g-2 mt-2">
                  <div class="col-md-6">
                    <label class="form-label small text-secondary">Direct Password</label>
                    <input v-model="form.password" type="password" class="form-control rounded-3" placeholder="Min. 8 characters" :required="!form.send_invitation" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small text-secondary">Confirm Password</label>
                    <input v-model="form.password_confirmation" type="password" class="form-control rounded-3" placeholder="Confirm password" :required="!form.send_invitation" />
                  </div>
                </div>
              </div>

              <div v-else class="border rounded-3 p-3 bg-light mt-3">
                <div class="fw-semibold text-dark small mb-2">Change Password (Optional)</div>
                <div class="row g-2">
                  <div class="col-md-6">
                    <input v-model="form.password" type="password" class="form-control rounded-3" placeholder="Leave empty to keep current" />
                  </div>
                  <div class="col-md-6">
                    <input v-model="form.password_confirmation" type="password" class="form-control rounded-3" placeholder="Confirm new password" />
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-footer border-0 bg-light px-4 py-3">
              <button type="button" class="btn btn-light rounded-3 px-4" @click="closeAddModal">Cancel</button>
              <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold" :disabled="isSubmitting">
                {{ isSubmitting ? 'Saving...' : (isEditing ? 'Update Employee' : 'Create Employee') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Invitation Token Modal -->
    <div v-if="showTokenModal" class="modal fade show d-block" style="background: rgba(0,0,0,0.6); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header border-0 bg-primary text-white px-4 py-3">
            <h5 class="modal-title fw-bold m-0"><i class="bi bi-envelope-check me-2"></i> Employee Invitation Created</h5>
            <button type="button" class="btn-close btn-close-white" @click="showTokenModal = false"></button>
          </div>
          <div class="modal-body px-4 py-3">
            <p class="text-secondary">
              An invitation token has been securely generated for <strong>{{ generatedInvitation?.name }}</strong> ({{ generatedInvitation?.email }}).
            </p>
            <div class="mb-3">
              <label class="form-label small fw-bold text-dark">One-Time Activation Link</label>
              <div class="input-group">
                <input type="text" class="form-control bg-light font-monospace small" :value="generatedInvitation?.setup_url" readonly />
                <button class="btn btn-outline-primary" @click="copyInvitationLink">
                  <i class="bi bi-clipboard"></i> Copy
                </button>
              </div>
            </div>
            <div class="alert alert-warning small mb-0">
              <i class="bi bi-info-circle-fill me-1"></i> This link expires in 72 hours and can only be used once. If email delivery is pending configuration, share this link directly with the employee.
            </div>
          </div>
          <div class="modal-footer border-0 bg-light px-4 py-2.5">
            <button type="button" class="btn btn-primary rounded-3 px-4" @click="showTokenModal = false">Done</button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.table > :not(caption) > * > * {
  padding: 1rem 0.75rem;
  border-bottom-color: #f1f5f9;
}
</style>
