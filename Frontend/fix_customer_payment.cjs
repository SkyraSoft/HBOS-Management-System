const fs = require('fs');
let file = fs.readFileSync('src/views/CustomerView.vue', 'utf8');

// Inject useKhataStore import if not exists
if (!file.includes('useKhataStore')) {
    file = file.replace(
        `import { useCustomersStore } from '@/stores/customers';`,
        `import { useCustomersStore } from '@/stores/customers';
import { useKhataStore } from '@/stores/khata';`
    );
    
    file = file.replace(
        `const store = useCustomersStore();`,
        `const store = useCustomersStore();
const khataStore = useKhataStore();`
    );
}

// Update savePayment function
const oldSavePaymentRegex = /function savePayment\(\) \{[\s\S]*?paymentForm\.value = \{ customerId: null, amount: 0 \};\s*\}/;

const newSavePayment = `async function savePayment() {
    if (!paymentForm.value.customerId || !paymentForm.value.amount || paymentForm.value.amount <= 0) return;
    
    const payload = {
        customer_id: paymentForm.value.customerId,
        type: 'got',
        amount: paymentForm.value.amount,
        date: new Date().toISOString().split('T')[0],
        notes: 'Payment received (Cash)'
    };
    
    const res = await khataStore.addTransaction(payload);
    
    if (res.success) {
        await store.fetchCustomers();
        closeModal('recordPaymentModal');
        paymentForm.value = { customerId: null, amount: 0 };
    } else {
        alert(res.message || "Failed to record payment.");
    }
}`;

file = file.replace(oldSavePaymentRegex, newSavePayment);

fs.writeFileSync('src/views/CustomerView.vue', file);
console.log('CustomerView savePayment updated to use khataStore!');
