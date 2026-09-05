const fs = require('fs');
let file = fs.readFileSync('src/views/KhataView.vue', 'utf8');

// 1. Replace form HTML with v-models
file = file.replace('<form class="form-card" id="paymentForm">', '<form class="form-card" id="paymentForm" @submit.prevent="submitPayment">');

// Customer select
file = file.replace(
`<select class="form-control" id="customer">

                                <option value="">
                                    Select a customer...
                                </option>

                                <option value="ahmad">
                                    Ahmad Khan — PKR 15,400 outstanding
                                </option>

                                <option value="ali">
                                    Ali Khan — PKR 15,000 outstanding
                                </option>

                                <option value="sara">
                                    Sara Ahmed — PKR 45,000 outstanding
                                </option>

                                <option value="bilal">
                                    Bilal Butt — PKR 22,000 outstanding
                                </option>

                            </select>`,
`<select class="form-control" id="customer" v-model="paymentForm.customer_id" required>
                                <option value="">
                                    Select a customer...
                                </option>
                                <option v-for="c in customers" :key="c.id" :value="c.id">
                                    {{ c.name }} — PKR {{ (c.balance || 0).toLocaleString() }} outstanding
                                </option>
                            </select>`
);

// Payment date
file = file.replace(
`<input class="form-control" type="date" id="paymentDate">`,
`<input class="form-control" type="date" id="paymentDate" v-model="paymentForm.date" required>`
);

// Payment method
file = file.replace(
`<select class="form-control" id="paymentMethod">`,
`<select class="form-control" id="paymentMethod" v-model="paymentForm.method">`
);

// Payment amount
file = file.replace(
`<input class="form-control" type="number" id="paymentAmount" min="0" step="1" placeholder="0.00">`,
`<input class="form-control" type="number" id="paymentAmount" min="0" step="1" placeholder="0.00" v-model.number="paymentForm.amount" required>`
);

// Payment note
file = file.replace(
`<textarea class="form-control" id="paymentNote" placeholder="e.g., Cheque #12345 or 'Monthly settlement'"></textarea>`,
`<textarea class="form-control" id="paymentNote" placeholder="e.g., Cheque #12345 or 'Monthly settlement'" v-model="paymentForm.notes"></textarea>`
);


// 2. Replace Preview
file = file.replace(
`<div class="preview-value" id="outstandingPreview">
                                    PKR<br>
                                    15,400
                                </div>`,
`<div class="preview-value" id="outstandingPreview">
                                    PKR<br>
                                    {{ selectedCustomerBalance.toLocaleString() }}
                                </div>`
);

file = file.replace(
`<div class="preview-value preview-payment" id="paymentPreview">
                                    PKR 0
                                </div>`,
`<div class="preview-value preview-payment" id="paymentPreview">
                                    PKR {{ (paymentForm.amount || 0).toLocaleString() }}
                                </div>`
);

file = file.replace(
`<div class="preview-value" id="remainingPreview">
                                    PKR<br>
                                    15,400
                                </div>`,
`<div class="preview-value" id="remainingPreview">
                                    PKR<br>
                                    {{ Math.max(0, selectedCustomerBalance - (paymentForm.amount || 0)).toLocaleString() }}
                                </div>`
);


// 3. Update Script Setup
const scriptAdditions = `
const paymentForm = ref({
    customer_id: '',
    date: new Date().toISOString().split('T')[0],
    method: 'Cash',
    amount: 0,
    notes: ''
});

const selectedCustomerBalance = computed(() => {
    if (!paymentForm.value.customer_id) return 0;
    const c = customers.value.find(c => c.id === paymentForm.value.customer_id);
    return c ? (c.balance || 0) : 0;
});

const submitPayment = async () => {
    if (!paymentForm.value.customer_id || paymentForm.value.amount <= 0) {
        alert("Please select a customer and enter a valid amount.");
        return;
    }

    const payload = {
        customer_id: paymentForm.value.customer_id,
        type: 'got',
        amount: paymentForm.value.amount,
        date: paymentForm.value.date,
        notes: paymentForm.value.notes + (paymentForm.value.method ? ' (' + paymentForm.value.method + ')' : '')
    };

    const res = await store.addTransaction(payload);
    if (res.success) {
        // Refresh customers to get updated balance
        await customerStore.fetchCustomers();
        showKhata();
        
        // Reset form
        paymentForm.value = {
            customer_id: '',
            date: new Date().toISOString().split('T')[0],
            method: 'Cash',
            amount: 0,
            notes: ''
        };
    } else {
        alert(res.message || "Failed to record payment.");
    }
};
`;

file = file.replace(
`const isPaymentPage = ref(false);`,
`${scriptAdditions}

const isPaymentPage = ref(false);`
);

fs.writeFileSync('src/views/KhataView.vue', file);
console.log('KhataView payment form fixed!');
