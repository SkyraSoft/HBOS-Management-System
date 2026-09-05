const fs = require('fs');
let file = fs.readFileSync('src/views/KhataView.vue', 'utf8');

const oldLabel = `<label class="form-label" for="customer">
                                Customer
                            </label>`;
const newLabel = `<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <label class="form-label" for="customer" style="margin: 0;">Customer</label>
                                <button type="button" @click="showAddCustomerModal = true" style="background: none; border: none; color: var(--primary, #0f46c7); font-size: 13px; font-weight: 600; cursor: pointer; padding: 0;">
                                    <i class="fa-solid fa-user-plus me-1"></i> Add New
                                </button>
                            </div>`;

file = file.replace(oldLabel, newLabel);


const modalHTML = `
    <!-- ADD CUSTOMER MODAL FOR KHATA -->
    <div v-if="showAddCustomerModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center;">
        <div style="background: #fff; border-radius: 12px; width: 400px; padding: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            <h3 style="margin-top: 0; margin-bottom: 20px; font-size: 18px;">Add New Customer</h3>
            
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-size: 14px;">Name *</label>
                <input type="text" class="form-control" v-model="newCustomerForm.name" required>
            </div>
            
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-size: 14px;">Phone *</label>
                <input type="tel" class="form-control" v-model="newCustomerForm.phone" required>
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-size: 14px;">Address (Optional)</label>
                <input type="text" class="form-control" v-model="newCustomerForm.address">
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn" style="background: #f1f3f5; color: #333;" @click="showAddCustomerModal = false">Cancel</button>
                <button type="button" class="btn btn-black" @click="saveNewCustomer">Save</button>
            </div>
        </div>
    </div>
</div>
</template>`;

file = file.replace('</div>\n</template>', modalHTML);


const scriptHTML = `
const showAddCustomerModal = ref(false);
const newCustomerForm = ref({
    name: '',
    phone: '',
    address: ''
});

const saveNewCustomer = async () => {
    if (!newCustomerForm.value.name || !newCustomerForm.value.phone) {
        alert("Name and Phone are required.");
        return;
    }
    
    const payload = {
        name: newCustomerForm.value.name,
        phone: newCustomerForm.value.phone,
        email: '',
        address: newCustomerForm.value.address,
        credit_limit: 0,
        opening_balance: 0,
        balance: 0,
        type: 'regular'
    };
    
    const res = await customerStore.addCustomer(payload);
    if (res.success) {
        await customerStore.fetchCustomers();
        if (customers.value.length > 0) {
            paymentForm.value.customer_id = customers.value[0].id;
        }
        showAddCustomerModal.value = false;
        newCustomerForm.value = { name: '', phone: '', address: '' };
    } else {
        alert(res.message || "Failed to add customer.");
    }
};

const isPaymentPage = ref(false);`;

file = file.replace('const isPaymentPage = ref(false);', scriptHTML);

fs.writeFileSync('src/views/KhataView.vue', file);
console.log('KhataView updated with Add Customer Modal!');
