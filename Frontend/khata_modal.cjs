const fs = require('fs');
let khView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', 'utf8');

// 1. Add defineProps and defineEmits
const setupPattern = /<script setup>\s*import { ref, computed } from 'vue';/;
khView = khView.replace(setupPattern, `<script setup>\nimport { ref, computed, defineProps, defineEmits } from 'vue';\n\nconst props = defineProps({\n  isModal: {\n    type: Boolean,\n    default: false\n  },\n  modalPage: {\n    type: String,\n    default: ''\n  }\n});\nconst emit = defineEmits(['close', 'success']);`);

// 2. Change `const isPaymentPage = ref(false);` to use props.isModal
khView = khView.replace(/const isPaymentPage = ref\(false\);/, `const isPaymentPage = ref(props.isModal);`);

// 3. Update `showKhata` to handle `isModal`
const showKhataPattern = /const showKhata = \(\) => {[\s\S]*?};/;
khView = khView.replace(showKhataPattern, `const showKhata = () => {\n  if (props.isModal) {\n    emit('close');\n  } else {\n    isPaymentPage.value = false;\n    paymentForm.value = {\n      customer_name: '',\n      date: new Date().toISOString().split('T')[0],\n      method: 'Cash',\n      newPurchaseAmount: 0,\n      returnDebtAmount: 0,\n      amount: 0,\n      notes: ''\n    };\n  }\n};`);

// 4. Update the main `<section class="page">` to hide when `isModal` is true
khView = khView.replace(/<section class="page" v-show="!isPaymentPage">/, `<section class="page" v-show="!isPaymentPage && !props.isModal">`);

// 5. Update `<section class="page payment-page" id="paymentPage" v-show="isPaymentPage">` to `v-show="isPaymentPage || props.isModal"`
khView = khView.replace(/<section class="page payment-page" id="paymentPage" v-show="isPaymentPage">/, `<section class="page payment-page" id="paymentPage" v-show="isPaymentPage || props.isModal" :style="props.isModal ? 'position: fixed; inset: 0; z-index: 1060; pointer-events: auto; background: var(--bg); overflow-y: auto;' : ''">`);

// 6. In submitPayment, emit success if modal
// Look for `showKhata();` inside submitPayment.
khView = khView.replace(/showKhata\(\);/g, `showKhata();\n          if (props.isModal) emit('success');`);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', khView);
