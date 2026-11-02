<template>
  <div class="transfers-view-wrapper">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
      <div>
        <h4 class="fw-bold mb-1">Inter-Branch Stock Transfers</h4>
        <p class="text-muted small mb-0">
          State-machine managed movement: Draft &rarr; Dispatched (In-Transit) &rarr; Received & Verified. Zero P&amp;L impact.
        </p>
      </div>
      <button class="btn btn-primary d-flex align-items-center gap-2" @click="openCreateModal">
        <i class="bi bi-plus-lg"></i>
        <span>New Transfer</span>
      </button>
    </div>

    <!-- Alert / Feedback -->
    <div v-if="feedbackMessage" :class="['alert', 'alert-dismissible', feedbackType === 'error' ? 'alert-danger' : 'alert-success']">
      {{ feedbackMessage }}
      <button type="button" class="btn-close" @click="feedbackMessage = ''"></button>
    </div>

    <!-- Filter bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-body p-3">
        <div class="d-flex flex-wrap align-items-center gap-3">
          <div class="d-flex align-items-center gap-2">
            <label class="small text-muted fw-semibold">Status:</label>
            <select v-model="filterStatus" class="form-select form-select-sm" style="width: 150px;" @change="loadTransfers">
              <option value="">All Statuses</option>
              <option value="draft">Draft</option>
              <option value="dispatched">Dispatched (In-Transit)</option>
              <option value="received">Received</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <button class="btn btn-sm btn-light border ms-auto" @click="loadTransfers">
            <i class="bi bi-arrow-clockwise"></i> Refresh
          </button>
        </div>
      </div>
    </div>

    <!-- Transfers Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
      <div v-if="isLoading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="text-muted small mt-2">Loading stock transfers...</p>
      </div>

      <div v-else-if="transfers.length === 0" class="text-center py-5 text-muted">
        <i class="bi bi-arrow-left-right fs-1 d-block mb-2 text-secondary opacity-50"></i>
        <h6>No stock transfers found</h6>
        <p class="small mb-0">Create a transfer to dispatch stock between active branches.</p>
      </div>

      <div v-else class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-4">Transfer #</th>
              <th>Date</th>
              <th>Source &rarr; Destination</th>
              <th>Items</th>
              <th>Status</th>
              <th class="text-end pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="t in transfers" :key="t.id">
              <td class="ps-4 fw-semibold text-primary">
                {{ t.transfer_number }}
              </td>
              <td class="small text-muted">
                {{ formatDate(t.created_at) }}
              </td>
              <td>
                <span class="fw-semibold">{{ t.from_branch?.name || 'Branch #' + t.from_branch_id }}</span>
                <i class="bi bi-arrow-right mx-2 text-muted"></i>
                <span class="fw-semibold">{{ t.to_branch?.name || 'Branch #' + t.to_branch_id }}</span>
              </td>
              <td>
                <span class="badge bg-light text-dark border">{{ t.items?.length || 0 }} products</span>
              </td>
              <td>
                <span :class="getStatusBadgeClass(t.status)">
                  {{ formatStatus(t.status) }}
                </span>
              </td>
              <td class="text-end pe-4">
                <div class="btn-group btn-group-sm">
                  <!-- Challan Print -->
                  <button class="btn btn-outline-secondary" title="Print Challan" @click="viewChallan(t.id)">
                    <i class="bi bi-printer"></i>
                  </button>

                  <!-- Details -->
                  <button class="btn btn-outline-primary" title="View Details" @click="viewDetails(t)">
                    <i class="bi bi-eye"></i>
                  </button>

                  <!-- Dispatch -->
                  <button 
                    v-if="t.status === 'draft'" 
                    class="btn btn-outline-success" 
                    title="Dispatch Stock (In-Transit)" 
                    @click="dispatchTransfer(t.id)"
                  >
                    <i class="bi bi-send-fill"></i> Dispatch
                  </button>

                  <!-- Receive -->
                  <button 
                    v-if="t.status === 'dispatched'" 
                    class="btn btn-outline-success" 
                    title="Confirm & Receive Stock" 
                    @click="receiveTransfer(t.id)"
                  >
                    <i class="bi bi-check-circle-fill"></i> Receive
                  </button>

                  <!-- Cancel -->
                  <button 
                    v-if="t.status === 'draft' || t.status === 'dispatched'" 
                    class="btn btn-outline-danger" 
                    title="Cancel Transfer" 
                    @click="promptCancel(t)"
                  >
                    <i class="bi bi-x-circle"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create Transfer Modal -->
    <div v-if="showCreateModal" class="modal-backdrop-custom">
      <div class="modal-dialog-custom modal-lg">
        <div class="modal-content p-4 rounded-4 shadow-lg border-0 bg-white">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold m-0">Initiate Inter-Branch Transfer</h5>
            <button class="btn-close" @click="showCreateModal = false"></button>
          </div>

          <form @submit.prevent="submitCreateTransfer">
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Source Branch *</label>
                <select v-model="createForm.from_branch_id" class="form-select" required>
                  <option value="" disabled>Select source branch</option>
                  <option v-for="b in activeBranches" :key="b.id" :value="b.id">
                    {{ b.name }} ({{ b.code }})
                  </option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Destination Branch *</label>
                <select v-model="createForm.to_branch_id" class="form-select" required>
                  <option value="" disabled>Select destination branch</option>
                  <option v-for="b in destinationBranchOptions" :key="b.id" :value="b.id">
                    {{ b.name }} ({{ b.code }})
                  </option>
                </select>
              </div>
            </div>

            <!-- Items table -->
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label small fw-semibold m-0">Products to Transfer</label>
                <button type="button" class="btn btn-sm btn-outline-primary" @click="addItemRow">
                  <i class="bi bi-plus"></i> Add Product
                </button>
              </div>

              <div class="table-responsive border rounded-3">
                <table class="table table-sm align-middle mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th style="width: 50%;">Product</th>
                      <th style="width: 30%;">Quantity</th>
                      <th style="width: 20%;" class="text-end">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row, idx) in createForm.items" :key="idx">
                      <td>
                        <select v-model="row.product_id" class="form-select form-select-sm" required>
                          <option value="" disabled>Select product</option>
                          <option v-for="p in availableProducts" :key="p.id" :value="p.id">
                            {{ p.name }} (Cost: {{ p.cost_price || '0.00' }})
                          </option>
                        </select>
                      </td>
                      <td>
                        <input 
                          v-model="row.quantity" 
                          type="text" 
                          class="form-control form-select-sm" 
                          required 
                          placeholder="e.g. 5 or 2.500"
                        />
                      </td>
                      <td class="text-end">
                        <button 
                          v-if="createForm.items.length > 1" 
                          type="button" 
                          class="btn btn-sm btn-outline-danger border-0" 
                          @click="removeItemRow(idx)"
                        >
                          <i class="bi bi-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label small fw-semibold">Transfer Notes / Reason</label>
              <textarea v-model="createForm.notes" class="form-control" rows="2" placeholder="e.g. Replenishment for seasonal rush"></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-light" @click="showCreateModal = false">Cancel</button>
              <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                {{ isSubmitting ? 'Creating Draft...' : 'Create Draft Transfer' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Transfer Detail Modal -->
    <div v-if="detailTransfer" class="modal-backdrop-custom">
      <div class="modal-dialog-custom modal-lg">
        <div class="modal-content p-4 rounded-4 shadow-lg border-0 bg-white">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h5 class="fw-bold m-0">{{ detailTransfer.transfer_number }}</h5>
              <span :class="getStatusBadgeClass(detailTransfer.status)">{{ formatStatus(detailTransfer.status) }}</span>
            </div>
            <button class="btn-close" @click="detailTransfer = null"></button>
          </div>

          <div class="row g-2 mb-3 small">
            <div class="col-6"><strong>From:</strong> {{ detailTransfer.from_branch?.name }}</div>
            <div class="col-6"><strong>To:</strong> {{ detailTransfer.to_branch?.name }}</div>
            <div class="col-6" v-if="detailTransfer.dispatched_at"><strong>Dispatched:</strong> {{ formatDate(detailTransfer.dispatched_at) }}</div>
            <div class="col-6" v-if="detailTransfer.received_at"><strong>Received:</strong> {{ formatDate(detailTransfer.received_at) }}</div>
          </div>

          <h6 class="fw-bold mb-2">Manifest Items:</h6>
          <div class="table-responsive border rounded mb-3">
            <table class="table table-sm mb-0">
              <thead class="bg-light">
                <tr>
                  <th>Product</th>
                  <th>Quantity Sent</th>
                  <th>Quantity Received</th>
                  <th>Unit Cost</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="it in detailTransfer.items" :key="it.id">
                  <td>{{ it.product?.name || 'Product #' + it.product_id }}</td>
                  <td>{{ it.quantity_sent || it.quantity_requested }}</td>
                  <td>{{ it.quantity_received || '-' }}</td>
                  <td>{{ it.unit_cost || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-end">
            <button class="btn btn-light" @click="detailTransfer = null">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Printable Challan Modal -->
    <div v-if="challanPayload" class="modal-backdrop-custom">
      <div class="modal-dialog-custom modal-lg">
        <div class="modal-content p-4 rounded-4 shadow-lg border-0 bg-white" id="printable-challan">
          <div class="d-flex justify-content-between align-items-center mb-3 no-print">
            <h5 class="fw-bold m-0">Delivery Challan / Stock Transfer Note</h5>
            <div class="d-flex gap-2">
              <button class="btn btn-primary btn-sm" @click="printChallan"><i class="bi bi-printer"></i> Print</button>
              <button class="btn-close" @click="challanPayload = null"></button>
            </div>
          </div>

          <!-- Challan Paper Surface -->
          <div class="challan-paper p-4 border rounded">
            <div class="text-center mb-4 border-bottom pb-3">
              <h4 class="fw-bold mb-1">INTER-BRANCH STOCK TRANSFER CHALLAN</h4>
              <p class="text-muted small mb-0">Challan #: <strong>{{ challanPayload.transfer_number }}</strong></p>
            </div>

            <div class="row g-3 mb-4">
              <div class="col-6">
                <div class="p-3 bg-light rounded">
                  <div class="text-uppercase small fw-bold text-muted mb-1">Dispatcher (Source):</div>
                  <div class="fw-bold fs-6">{{ challanPayload.source_branch?.name }}</div>
                  <div class="small text-muted">{{ challanPayload.source_branch?.address || challanPayload.source_branch?.city }}</div>
                  <div class="small text-muted">Phone: {{ challanPayload.source_branch?.phone }}</div>
                </div>
              </div>
              <div class="col-6">
                <div class="p-3 bg-light rounded">
                  <div class="text-uppercase small fw-bold text-muted mb-1">Receiver (Destination):</div>
                  <div class="fw-bold fs-6">{{ challanPayload.destination_branch?.name }}</div>
                  <div class="small text-muted">{{ challanPayload.destination_branch?.address || challanPayload.destination_branch?.city }}</div>
                  <div class="small text-muted">Phone: {{ challanPayload.destination_branch?.phone }}</div>
                </div>
              </div>
            </div>

            <table class="table table-bordered mb-4">
              <thead class="bg-light">
                <tr>
                  <th>#</th>
                  <th>Product Description</th>
                  <th class="text-end">Qty Sent</th>
                  <th class="text-end">Qty Received</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(it, i) in challanPayload.items" :key="i">
                  <td>{{ i + 1 }}</td>
                  <td>{{ it.product_name }}</td>
                  <td class="text-end fw-semibold">{{ it.quantity_sent || it.quantity_requested }}</td>
                  <td class="text-end">{{ it.quantity_received || '-' }}</td>
                </tr>
              </tbody>
            </table>

            <div class="row mt-5 pt-4 text-center">
              <div class="col-6">
                <div class="border-top pt-2 text-muted small">Dispatched By / Signature</div>
              </div>
              <div class="col-6">
                <div class="border-top pt-2 text-muted small">Received By / Signature</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import stockTransferService from '../services/stockTransferService';
import api from '../api';
import { useNavigationStore } from '../stores/navigation';

const navStore = useNavigationStore();

const transfers = ref([]);
const isLoading = ref(false);
const filterStatus = ref('');
const feedbackMessage = ref('');
const feedbackType = ref('success');

const showCreateModal = ref(false);
const isSubmitting = ref(false);
const createForm = ref({
  from_branch_id: '',
  to_branch_id: '',
  notes: '',
  items: [{ product_id: '', quantity: '1' }]
});

const availableProducts = ref([]);
const detailTransfer = ref(null);
const challanPayload = ref(null);

const activeBranches = computed(() => navStore.activeBranches);
const destinationBranchOptions = computed(() => {
  return activeBranches.value.filter(b => b.id !== createForm.value.from_branch_id);
});

async function loadTransfers() {
  isLoading.value = true;
  try {
    const params = {};
    if (filterStatus.value) params.status = filterStatus.value;
    transfers.value = await stockTransferService.list(params);
  } catch (err) {
    feedbackType.value = 'error';
    feedbackMessage.value = 'Failed to load transfers: ' + (err.response?.data?.message || err.message);
  } finally {
    isLoading.value = false;
  }
}

async function loadProducts() {
  try {
    const res = await api.get('/products');
    availableProducts.value = res.data?.data || res.data || [];
  } catch (err) {
    console.warn('Could not load products:', err);
  }
}

function openCreateModal() {
  createForm.value = {
    from_branch_id: activeBranches.value[0]?.id || '',
    to_branch_id: '',
    notes: '',
    items: [{ product_id: '', quantity: '1' }]
  };
  showCreateModal.value = true;
  loadProducts();
}

function addItemRow() {
  createForm.value.items.push({ product_id: '', quantity: '1' });
}

function removeItemRow(index) {
  createForm.value.items.splice(index, 1);
}

async function submitCreateTransfer() {
  isSubmitting.value = true;
  try {
    const payload = {
      from_branch_id: createForm.value.from_branch_id,
      to_branch_id: createForm.value.to_branch_id,
      notes: createForm.value.notes,
      items: createForm.value.items.map(it => ({
        product_id: it.product_id,
        quantity: it.quantity,
      }))
    };
    await stockTransferService.create(payload);
    showCreateModal.value = false;
    feedbackType.value = 'success';
    feedbackMessage.value = 'Draft stock transfer created successfully.';
    await loadTransfers();
  } catch (err) {
    feedbackType.value = 'error';
    feedbackMessage.value = 'Could not create transfer: ' + (err.response?.data?.message || err.message);
  } finally {
    isSubmitting.value = false;
  }
}

async function dispatchTransfer(id) {
  if (!confirm('Are you sure you want to dispatch this stock? Source stock will be decremented immediately.')) return;
  try {
    await stockTransferService.dispatch(id);
    feedbackType.value = 'success';
    feedbackMessage.value = 'Transfer dispatched! Stock is now in-transit.';
    await loadTransfers();
  } catch (err) {
    feedbackType.value = 'error';
    feedbackMessage.value = 'Dispatch failed: ' + (err.response?.data?.message || err.message);
  }
}

async function receiveTransfer(id) {
  if (!confirm('Confirm physical arrival and verification of stock at destination branch?')) return;
  try {
    await stockTransferService.receive(id);
    feedbackType.value = 'success';
    feedbackMessage.value = 'Transfer received successfully! Stock incremented at destination.';
    await loadTransfers();
  } catch (err) {
    feedbackType.value = 'error';
    feedbackMessage.value = 'Receive failed: ' + (err.response?.data?.message || err.message);
  }
}

async function promptCancel(transfer) {
  const reason = prompt('Please enter cancellation reason:', 'Cancelled by supervisor');
  if (reason === null) return;
  try {
    await stockTransferService.cancel(transfer.id, reason);
    feedbackType.value = 'success';
    feedbackMessage.value = 'Transfer cancelled.';
    await loadTransfers();
  } catch (err) {
    feedbackType.value = 'error';
    feedbackMessage.value = 'Cancel failed: ' + (err.response?.data?.message || err.message);
  }
}

function viewDetails(transfer) {
  detailTransfer.value = transfer;
}

async function viewChallan(id) {
  try {
    challanPayload.value = await stockTransferService.getChallan(id);
  } catch (err) {
    feedbackType.value = 'error';
    feedbackMessage.value = 'Failed to load delivery challan: ' + (err.response?.data?.message || err.message);
  }
}

function printChallan() {
  window.print();
}

function formatDate(dateStr) {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function formatStatus(status) {
  if (!status) return '';
  if (status === 'dispatched') return 'In-Transit';
  return status.charAt(0).toUpperCase() + status.slice(1);
}

function getStatusBadgeClass(status) {
  switch (status) {
    case 'draft': return 'badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1';
    case 'dispatched': return 'badge bg-warning-subtle text-warning-emphasis rounded-pill px-2 py-1';
    case 'received': return 'badge bg-success-subtle text-success rounded-pill px-2 py-1';
    case 'cancelled': return 'badge bg-danger-subtle text-danger rounded-pill px-2 py-1';
    default: return 'badge bg-light text-dark rounded-pill px-2 py-1';
  }
}

onMounted(() => {
  loadTransfers();
  navStore.fetchBranches();
});
</script>

<style scoped>
.transfers-view-wrapper {
  padding: 8px 0;
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
  max-width: 600px;
}

.modal-dialog-custom.modal-lg {
  max-width: 800px;
}

@media print {
  body * {
    visibility: hidden;
  }
  #printable-challan, #printable-challan * {
    visibility: visible;
  }
  #printable-challan {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
  }
  .no-print {
    display: none !important;
  }
}
</style>
