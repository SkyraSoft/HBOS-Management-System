const fs = require('fs');
let file = fs.readFileSync('src/views/KhataView.vue', 'utf8');

file = file.replace(
`    const res = await customerStore.addCustomer(payload);
    if (res.success) {
        await customerStore.fetchCustomers();
        if (customers.value.length > 0) {
            paymentForm.value.customer_id = customers.value[0].id;
        }`,
`    const res = await customerStore.addCustomer(payload);
    if (res.success) {
        await customerStore.fetchCustomers();
        if (res.data && res.data.id) {
            paymentForm.value.customer_id = res.data.id;
        }`
);

fs.writeFileSync('src/views/KhataView.vue', file);
console.log('KhataView logic updated');
