const fs = require('fs');
let khView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', 'utf8');

// 1. Replace customer select with input
const selectPattern = /<div style="display: flex; justify-content: space-between; align-items: center; \s*margin-bottom: 8px;">\s*<label class="form-label" for="customer" style="margin: 0;">Customer<\/label>\s*<button type="button" @click="showAddCustomerModal = true"[\s\S]*?<\/button>\s*<\/div>\s*<select class="form-control" id="customer" v-model="paymentForm\.customer_id" required>\s*<option value="">\s*Select a customer\.\.\.\s*<\/option>\s*<option v-for="c in customers" :key="c\.id" :value="c\.id">\s*\{\{ c\.name \}\} [^<]*\s*<\/option>\s*<\/select>/;

const newCustomerUI = `<label class="form-label" for="customer" style="margin-bottom: 8px; display: block;">Customer</label>
                            <input type="text" class="form-control" id="customer" v-model="paymentForm.customer_name" placeholder="Enter customer name..." list="customerList" required>
                            <datalist id="customerList">
                                <option v-for="c in customers" :key="c.id" :value="c.name">PKR {{ (c.balance || 0).toLocaleString() }} outstanding</option>
                            </datalist>`;
khView = khView.replace(selectPattern, newCustomerUI);

// 2. Change paymentForm initialization
khView = khView.replace(/paymentForm\.value = \{\s*customer_id: '',/g, `paymentForm.value = {\n            customer_name: '',`);
khView = khView.replace(/customer_id: '',\s*date: new Date\(\)\.toISOString/g, `customer_name: '',\n    date: new Date().toISOString`);

// 3. Change computed balance
khView = khView.replace(/if \(!paymentForm\.value\.customer_id\) return 0;\s*const c = customers\.value\.find\(c => c\.id === paymentForm\.value\.customer_id\);/g, `if (!paymentForm.value.customer_name) return 0;\n    const c = customers.value.find(c => c.name.toLowerCase() === paymentForm.value.customer_name.toLowerCase());`);

// 4. Update submitPayment logic
const submitPattern = /if \(!paymentForm\.value\.customer_id \|\| paymentForm\.value\.amount <= 0\) \{\s*alert\("Please select a customer and enter a valid amount\."\);\s*return;\s*\}\s*const payload = \{\s*customer_id: paymentForm\.value\.customer_id,/;

const newSubmitLogic = `if (!paymentForm.value.customer_name || paymentForm.value.amount <= 0) {
        alert("Please enter a customer name and a valid amount.");
        return;
    }

    let customerId = null;
    let existing = customers.value.find(c => c.name.toLowerCase() === paymentForm.value.customer_name.toLowerCase());
    
    if (existing) {
        customerId = existing.id;
    } else {
        await customerStore.addCustomer({
            name: paymentForm.value.customer_name,
            phone: 'Not provided',
            type: 'regular',
            opening_balance: 0,
            balance: 0
        });
        await customerStore.fetchCustomers();
        let created = customers.value.find(c => c.name.toLowerCase() === paymentForm.value.customer_name.toLowerCase());
        if (created) customerId = created.id;
        else customerId = customers.value.length > 0 ? customers.value[customers.value.length-1].id : null;
    }
    
    if (!customerId) { alert('Error processing customer'); return; }

    const payload = {
        customer_id: customerId,`;

khView = khView.replace(submitPattern, newSubmitLogic);

// 5. Add "New Purchase" and "Return Debt" buttons
const actionPattern2 = /<button class="btn btn-black" type="submit" form="paymentForm">/;
const newActionButtons = `<button class="btn" style="background: #eef2fa; color: #0f46c7; font-weight: 600; border: none; margin-bottom: 14px;" type="button" @click="openNewPurchase">
                                  New Purchase
                              </button>
                              <button class="btn" style="background: #fdf3e1; color: #b7791f; font-weight: 600; border: none; margin-bottom: 14px;" type="button" @click="openReturnDebt">
                                  Return Debt
                              </button>
                              <button class="btn btn-black" type="submit" form="paymentForm">`;

// Only replace if not already added
if (!khView.includes('openNewPurchase')) {
    khView = khView.replace(actionPattern2, newActionButtons);
    
    khView = khView.replace(/const showAddCustomerModal = ref\(false\);/, `import { useRouter } from 'vue-router';\nconst router = useRouter();\nconst openNewPurchase = () => {\n  router.push('/purchases?new=true');\n};\nconst openReturnDebt = () => {\n  router.push('/return-debt');\n};\n\nconst showAddCustomerModal = ref(false);`);
}


fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', khView);
