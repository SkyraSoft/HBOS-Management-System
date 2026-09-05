const fs = require('fs');
let content = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/DashboardView.vue', 'utf8');

const paymentHtml = `<CustomerView v-if="activeModal === 'payment'" :isModal="true" modalPage="payment" @success="handleModalSuccess('Payment recorded successfully!')" />`;
const newPaymentHtml = `<CustomerView v-if="activeModal === 'payment'" :isModal="true" modalPage="payment" @success="handleModalSuccess('Payment recorded successfully!')" @close="closeModal" />`;

content = content.replace(paymentHtml, newPaymentHtml);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/DashboardView.vue', content);
console.log('DashboardView.vue updated to listen for close event on payment modal.');
