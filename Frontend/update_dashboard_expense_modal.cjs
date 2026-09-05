const fs = require('fs');
const path = require('path');

const dashFile = path.resolve(__dirname, 'src/views/DashboardView.vue');
let content = fs.readFileSync(dashFile, 'utf8');

// 1. Replace button @click
content = content.replace(
  /@click="router\.push\('\/expenses\?tab=add'\)" class="dash-action-btn"/g,
  '@click="openModal(\\'expense\\')" class="dash-action-btn"'
);

// 2. Replace the old activeModal === 'expense' modal block
const oldModalBlock = `<div v-if="activeModal === 'expense'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050; background: rgba(0,0,0,0.5); pointer-events: auto;">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="max-height: 90vh;">
          <div class="modal-header bg-light border-0">
            <h5 class="modal-title fw-bold">Add Expense</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body p-4" style="background: var(--bg); overflow-y: auto;">
            <ExpensesView :isModal="true" modalPage="add" @success="handleModalSuccess('Expense saved successfully!')" @close="closeModal" />
          </div>
        </div>
      </div>
    </div>`;

const newModalBlock = `<!-- Add Expense Popup Modal -->
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
    </div>`;

if (content.includes(oldModalBlock)) {
  content = content.replace(oldModalBlock, newModalBlock);
} else {
  // Let's replace whatever v-if="activeModal === 'expense'" block is there
  content = content.replace(/<div v-if="activeModal === 'expense'"[\s\S]*?<\/div>\s*<\/div>\s*<\/div>\s*<\/div>/, newModalBlock);
}

// 3. Add script logic for dashboard expense
const scriptTarget = `const activeModal = ref(null)`;
const scriptReplacement = `const activeModal = ref(null)
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

const submitDashboardExpense = async () => {
  if (!dashExpenseForm.value.description || !dashExpenseForm.value.amount || !dashExpenseForm.value.category) {
    alert('Please fill all required fields.');
    return;
  }
  isSubmittingExpense.value = true;
  try {
    const res = await api.post('/expenses', dashExpenseForm.value);
    if (res && res.data) {
      closeModal();
      alert('Expense recorded successfully!');
      fetchDashboardData();
    } else {
      alert('Failed to record expense.');
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Error occurred while saving expense.');
  } finally {
    isSubmittingExpense.value = false;
  }
}`;

content = content.replace(scriptTarget, scriptReplacement);

// 4. Update openModal & closeModal to not change URL history
content = content.replace(
  /const openModal = \(type\) => \{[\s\S]*?window\.history\.pushState\(null, '', '\/'\);\s*\}/,
  `const openModal = (type) => {
  activeModal.value = type;
  if (type === 'expense') {
    dashExpenseForm.value = {
      description: '',
      category: 'Utilities',
      amount: null,
      date: new Date().toISOString().split('T')[0],
      method: 'Cash',
      reference: '',
      branch: 'Main Branch',
      notes: ''
    };
  }
}

const closeModal = () => {
  activeModal.value = null;
}`
);

// 5. Add CSS for modal if not present
const styleAdditions = `
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
`;

content = content.replace('</style>', styleAdditions);

fs.writeFileSync(dashFile, content, 'utf8');
console.log('Successfully updated DashboardView.vue with clean in-page Expense modal!');
