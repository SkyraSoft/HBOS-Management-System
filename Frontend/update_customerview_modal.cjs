const fs = require('fs');

let content = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/CustomerView.vue', 'utf8');

// 1. Add props/emits
const scriptSetupPattern = /<script setup>\s*import/;
const propsEmits = `<script setup>
import { defineProps, defineEmits } from 'vue'
const props = defineProps({
  isModal: { type: Boolean, default: false },
  modalPage: { type: String, default: '' }
})
const emit = defineEmits(['success'])
import`;
content = content.replace(scriptSetupPattern, propsEmits);

// 2. Hide main page if isModal
// The main page starts with <section class="page"> and ends right before <!-- ADD CUSTOMER MODAL -->
content = content.replace(/<section class="page">/, '<section v-if="!isModal" class="page">');

// 3. Update savePayment to emit success
const savePaymentPattern = /savePayment\(\) \{[\s\S]*?fetchCustomers\(\);\s*closeModal\('recordPaymentModal'\);/;
const newSavePayment = `savePayment() {
    // ...
    // wait, we can just find closeModal('recordPaymentModal') and inject emit
`;
content = content.replace(/fetchCustomers\(\);\s*closeModal\('recordPaymentModal'\);/, `fetchCustomers();
            closeModal('recordPaymentModal');
            if (props.isModal) {
                emit('success', res.message || 'Payment recorded successfully!');
                return;
            }`);

// 4. Auto-open modal if isModal and modalPage == 'payment'
const mountedPattern = /onMounted\(\(\) => \{/;
const newMounted = `onMounted(() => {
    if (props.isModal && props.modalPage === 'payment') {
        setTimeout(() => openRecordPaymentModal(), 150);
    }`;
content = content.replace(mountedPattern, newMounted);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/CustomerView.vue', content);
console.log('CustomerView updated for modal mode.');
