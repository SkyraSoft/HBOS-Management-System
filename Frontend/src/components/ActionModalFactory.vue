<script setup>
import { ref, watch } from 'vue';
import { useNotificationStore } from '../stores/notifications';

const props = defineProps({
  notification: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close']);
const notificationStore = useNotificationStore();

const isVisible = ref(false);

watch(() => props.notification, (newVal) => {
  if (newVal && newVal.action_type) {
    isVisible.value = true;
  } else {
    isVisible.value = false;
  }
});

const closeModal = () => {
  isVisible.value = false;
  emit('close');
};

const handleActionSubmit = async () => {
  if (!props.notification) return;

  const actionType = props.notification.action_type;
  const payload = props.notification.action_payload;

  try {
    if (actionType === 'PAY_EXPENSE') {
      // Call expense API here using payload.expense_id
      // await api.post('/expenses/pay', { recurring_expense_id: payload.expense_id });
      console.log('Paying expense', payload);
    } else if (actionType === 'REORDER_STOCK') {
      // Navigate to purchase order page
      // router.push({ name: 'Purchase', query: { product_id: payload.product_id } });
      console.log('Reordering stock', payload);
    } else if (actionType === 'PAY_SALARY') {
      console.log('Paying salary', payload);
    }

    // Dismiss notification after successful action
    await notificationStore.dismissNotification(props.notification.id);
    closeModal();
  } catch (error) {
    console.error('Action failed:', error);
    alert('Failed to process action. Please try again.');
  }
};
</script>

<template>
  <div v-if="isVisible" class="modal-backdrop fade show" style="z-index: 1055;"></div>
  
  <div 
    v-if="isVisible" 
    class="modal fade show d-block" 
    tabindex="-1" 
    style="z-index: 1060;" 
    @click.self="closeModal"
  >
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
        
        <div class="modal-header bg-light border-0 px-4 py-3">
          <div class="d-flex align-items-center gap-2">
            <i class="bi fs-5 text-primary" :class="notification.icon"></i>
            <h5 class="modal-title fw-bold m-0">Take Action</h5>
          </div>
          <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4">
          <p class="text-secondary mb-4">{{ notification.message }}</p>

          <!-- PAY_EXPENSE FORM -->
          <div v-if="notification.action_type === 'PAY_EXPENSE'">
            <div class="mb-3">
              <label class="form-label text-muted fw-semibold small">Have you paid this expense?</label>
              <select class="form-select form-select-lg bg-light border-0">
                <option value="cash">Paid via Cash</option>
                <option value="bank">Paid via Bank Transfer</option>
                <option value="wallet">Paid via Mobile Wallet</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted fw-semibold small">Notes / Reference (Optional)</label>
              <input type="text" class="form-control bg-light border-0" placeholder="e.g., Transaction ID">
            </div>
          </div>

          <!-- REORDER_STOCK FORM -->
          <div v-else-if="notification.action_type === 'REORDER_STOCK'">
            <div class="alert alert-warning border-0 rounded-3 d-flex align-items-center gap-3">
              <i class="bi bi-exclamation-triangle-fill fs-3"></i>
              <div>
                <strong>Low Stock Warning</strong>
                <div class="small">Clicking confirm will take you to the Purchase module to restock this item.</div>
              </div>
            </div>
          </div>

           <!-- PAY_SALARY FORM -->
           <div v-else-if="notification.action_type === 'PAY_SALARY'">
            <div class="mb-3">
              <label class="form-label text-muted fw-semibold small">Confirm Salary Payment</label>
              <select class="form-select form-select-lg bg-light border-0">
                <option value="cash">Paid via Cash</option>
                <option value="bank">Paid via Bank Transfer</option>
              </select>
            </div>
          </div>

          <!-- FALLBACK -->
          <div v-else>
            <p class="text-danger fw-semibold">Unknown action type. Cannot proceed.</p>
          </div>
        </div>

        <div class="modal-footer border-0 px-4 py-3 bg-light d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-medium" @click="closeModal">Cancel</button>
          <button type="button" class="btn btn-primary rounded-pill px-4 fw-medium shadow-sm" @click="handleActionSubmit">
            Confirm Action
          </button>
        </div>
        
      </div>
    </div>
  </div>
</template>
