const fs = require('fs');

let content = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/DashboardView.vue', 'utf8');

// Replace the 'payment' modal HTML with just the raw component since it handles its own modal wrapper
const paymentModalHtml = /<div v-if="activeModal === 'payment'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050;">[\s\S]*?<CustomerView :isModal="true" modalPage="payment" @success="handleModalSuccess\('Payment recorded successfully!'\)" \/>[\s\S]*?<\/div>\s*<\/div>\s*<\/div>/;

const newPaymentHtml = `<CustomerView v-if="activeModal === 'payment'" :isModal="true" modalPage="payment" @success="handleModalSuccess('Payment recorded successfully!')" />`;

content = content.replace(paymentModalHtml, newPaymentHtml);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/DashboardView.vue', content);
console.log('DashboardView.vue fixed for double modal issue.');
