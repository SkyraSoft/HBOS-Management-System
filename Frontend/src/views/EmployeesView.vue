<script setup>
import { ref, onMounted } from 'vue';
import api from '../api';
import { useToast } from 'vue-toastification';

const toast = useToast();
const employees = ref([]);
const isLoading = ref(true);
const error = ref(null);

const showAddModal = ref(false);
const isEditing = ref(false);
const editId = ref(null);
const isSubmitting = ref(false);
const newEmployee = ref({
  name: '',
  role: '',
  phone: '',
  salary_amount: '',
  payment_cycle: 'monthly',
});

const fetchEmployees = async () => {
  try {
    const response = await api.get('/employees');
    employees.value = response.data.data;
  } catch (err) {
    console.error('Failed to fetch employees', err);
    error.value = 'Failed to load employees';
  } finally {
    isLoading.value = false;
  }
};

const openAddModal = () => {
  isEditing.value = false;
  editId.value = null;
  newEmployee.value = { name: '', role: '', phone: '', salary_amount: '', payment_cycle: 'monthly' };
  showAddModal.value = true;
};

const openEditModal = (emp) => {
  isEditing.value = true;
  editId.value = emp.id;
  newEmployee.value = { ...emp };
  showAddModal.value = true;
};

const closeAddModal = () => {
  showAddModal.value = false;
  newEmployee.value = {
    name: '',
    role: '',
    phone: '',
    salary_amount: '',
    payment_cycle: 'monthly',
  };
};

const submitAddEmployee = async () => {
  isSubmitting.value = true;
  try {
    if (isEditing.value) {
      await api.put('/employees/' + editId.value, newEmployee.value);
      toast.success('Employee updated successfully');
    } else {
      await api.post('/employees', newEmployee.value);
      toast.success('Employee added successfully');
    }
    closeAddModal();
    fetchEmployees();
  } catch (err) {
    console.error('Failed to save employee', err);
    const msg = err.response?.data?.message || 'Failed to save employee';
    toast.error(msg);
  } finally {
    isSubmitting.value = false;
  }
};

const deleteEmployee = async (id) => {
  if (!confirm('Are you sure you want to delete this employee?')) return;
  try {
    await api.delete('/employees/' + id);
    toast.success('Employee deleted successfully');
    fetchEmployees();
  } catch (err) {
    console.error('Failed to delete employee', err);
    const msg = err.response?.data?.message || 'Failed to delete employee';
    toast.error(msg);
  }
};

onMounted(fetchEmployees);
</script>

<template>
  <div class="employees-page-wrapper p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-bold mb-1">Employees & HR</h2>
        <p class="text-muted mb-0">Manage your staff and salaries.</p>
      </div>
      <button class="btn btn-primary rounded-pill px-4" @click="openAddModal">
        <i class="bi bi-person-plus-fill me-2"></i> Add Employee
      </button>
    </div>

    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <div v-else-if="error" class="alert alert-danger">{{ error }}</div>

    <div v-else class="card border-0 shadow-sm rounded-4">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-4">Employee</th>
              <th>Role</th>
              <th>Phone</th>
              <th>Salary</th>
              <th>Payment Cycle</th>
              <th>Next Payment</th>
              <th class="text-end pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="emp in employees" :key="emp.id">
              <td class="ps-4 fw-medium text-dark">{{ emp.name }}</td>
              <td><span class="badge bg-secondary-subtle text-secondary rounded-pill">{{ emp.role || 'Staff' }}</span></td>
              <td class="text-secondary">{{ emp.phone || '-' }}</td>
              <td class="fw-bold text-success">{{ emp.salary_amount }}</td>
              <td class="text-secondary text-capitalize">{{ emp.payment_cycle }}</td>
              <td class="text-secondary">{{ emp.next_payment_date || '-' }}</td>
              <td class="text-end pe-4">
                <button class="btn btn-sm btn-light rounded-circle me-1" @click="openEditModal(emp)"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-light rounded-circle text-danger" @click="deleteEmployee(emp.id)"><i class="bi bi-trash"></i></button>
              </td>
            </tr>
            <tr v-if="employees.length === 0">
              <td colspan="7" class="text-center text-muted py-4">No employees found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Add Employee Modal -->
    <div v-if="showAddModal" class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1050;">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header border-0 bg-light px-4 py-3">
            <h5 class="modal-title fw-bold text-dark m-0">{{ isEditing ? 'Edit Employee' : 'Add New Employee' }}</h5>
            <button type="button" class="btn-close" @click="closeAddModal"></button>
          </div>
          <form @submit.prevent="submitAddEmployee" id="addEmployeeForm">
            <div class="modal-body px-4 py-3">
              <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Full Name <span class="text-danger">*</span></label>
                <input v-model="newEmployee.name" type="text" class="form-control rounded-3" placeholder="e.g. John Doe" required />
              </div>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-secondary">Role</label>
                  <input v-model="newEmployee.role" type="text" class="form-control rounded-3" placeholder="e.g. Cashier" />
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-secondary">Phone</label>
                  <input v-model="newEmployee.phone" type="text" class="form-control rounded-3" placeholder="e.g. +92..." />
                </div>
              </div>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-secondary">Salary Amount <span class="text-danger">*</span></label>
                  <input v-model="newEmployee.salary_amount" type="number" step="0.01" class="form-control rounded-3" placeholder="0.00" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-secondary">Payment Cycle</label>
                  <select v-model="newEmployee.payment_cycle" class="form-select rounded-3">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="modal-footer border-0 bg-light px-4 py-2.5">
              <button type="button" class="btn btn-light rounded-3 px-4" @click="closeAddModal">Cancel</button>
              <button type="submit" class="btn btn-primary rounded-3 px-4" :disabled="isSubmitting">
                {{ isSubmitting ? 'Saving...' : (isEditing ? 'Update Employee' : 'Save Employee') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.table > :not(caption) > * > * {
  padding: 1rem 0.5rem;
  border-bottom-color: #f1f5f9;
}
</style>
