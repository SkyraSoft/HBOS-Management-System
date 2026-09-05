<template>
  <div class="returndebt-wrapper p-4">
    <div class="header mb-4">
      <h3 class="fw-bold">Return Debt</h3>
      <p class="text-secondary">Process debt returns and clear outstanding balances.</p>
    </div>
    
    <div class="form-container">
      <div class="mb-3">
        <label class="form-label fw-bold">Select Account / Party</label>
        <select class="form-select">
          <option>Walk-in Customer (Default)</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label fw-bold">Amount to Return (PKR)</label>
        <input type="number" class="form-control" placeholder="Enter amount..." />
      </div>
      <div class="mb-3">
        <label class="form-label fw-bold">Payment Method</label>
        <select class="form-select">
          <option>Cash</option>
          <option>Bank Transfer</option>
          <option>Cheque</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label fw-bold">Notes</label>
        <textarea class="form-control" rows="3" placeholder="Additional details..."></textarea>
      </div>
      
      <div class="d-flex justify-content-end mt-4">
        <button class="btn btn-secondary me-2" @click="$emit('close')">Cancel</button>
        <button class="btn btn-primary" @click="$emit('success')">Confirm Return</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'

const props = defineProps({
  isModal: { type: Boolean, default: false }
})
const emit = defineEmits(['success', 'close'])
</script>

<style scoped>
.returndebt-wrapper {
  background: white;
  border-radius: 8px;
}
</style>
