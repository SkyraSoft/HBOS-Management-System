const fs = require('fs');
let content = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/CustomerView.vue', 'utf8');

// Fix button bindings in the header
content = content.replace(/<button class="btn-custom btn-primary-custom" onclick="addCustomer\(\)">/g, 
    '<button class="btn-custom btn-primary-custom" @click="openAddModal">');

content = content.replace(/<button class="btn-custom" onclick="recordPayment\(\)">/g, 
    '<button class="btn-custom" @click="openRecordPaymentModal">');

// Add v-models to Record Payment Modal
content = content.replace(/<select class="form-select">/, '<select class="form-select" v-model="paymentForm.customerId">');
content = content.replace(/<input type="number" class="form-control" placeholder="Enter amount...">/, '<input type="number" class="form-control" placeholder="Enter amount..." v-model.number="paymentForm.amount">');
content = content.replace(/<button type="button" class="btn btn-primary" @click="closeModal\('recordPaymentModal'\)">Record Payment<\/button>/, '<button type="button" class="btn btn-primary" @click="savePayment">Record Payment</button>');

// Add the paymentForm reactive ref and savePayment function to the script
const paymentFormRef = `const paymentForm = ref({ customerId: null, amount: 0 });`;
if (!content.includes('paymentForm = ref')) {
    content = content.replace(/const newCustomer = ref/g, paymentFormRef + '\nconst newCustomer = ref');
}

const savePaymentFunc = `
function savePayment() {
    if (!paymentForm.value.customerId || !paymentForm.value.amount || paymentForm.value.amount <= 0) return;
    
    const index = customers.value.findIndex(c => c.id === paymentForm.value.customerId);
    if (index > -1) {
        customers.value[index].received += paymentForm.value.amount;
        customers.value[index].balance = Math.max(0, customers.value[index].balance - paymentForm.value.amount);
        customers.value[index].lastActivity = 'Today';
        customers.value[index].typeStr = 'Payment Recvd';
        
        if (customers.value[index].balance === 0) {
            customers.value[index].status = 'clear';
        }
    }
    closeModal('recordPaymentModal');
    paymentForm.value = { customerId: null, amount: 0 };
}
`;
if (!content.includes('function savePayment()')) {
    content = content.replace(/<\/script>/, savePaymentFunc + '\n</script>');
}

// Add the initial Customer ID to record payment form when opened
content = content.replace(/function openRecordPaymentModal\(\) \{/, `function openRecordPaymentModal() {\n    if (customers.value.length > 0) paymentForm.value.customerId = customers.value[0].id;`);

fs.writeFileSync('c:/xampp/htdocs/HBOS/src/views/CustomerView.vue', content);
console.log("Updated functionality for top buttons.");
