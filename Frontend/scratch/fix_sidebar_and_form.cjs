const fs = require('fs');

const vueFile = 'c:/xampp/htdocs/HBOS/src/views/CustomerView.vue';
const htmlFile = 'c:/xampp/htdocs/HBOS/legacy_html/Customer.html';

let vueContent = fs.readFileSync(vueFile, 'utf8');
const legacyHtml = fs.readFileSync(htmlFile, 'utf8');

// 1. Insert Sidebar
if (!vueContent.includes('<aside class="sidebar" id="sidebar">')) {
    // Extract sidebar from legacyHtml
    const sidebarStart = legacyHtml.indexOf('<aside class="sidebar" id="sidebar">');
    const sidebarEnd = legacyHtml.indexOf('</aside>') + 8;
    const sidebarContent = legacyHtml.substring(sidebarStart, sidebarEnd);

    // Insert it right after <div class="app">
    vueContent = vueContent.replace('<div class="app">\r\n', '<div class="app">\r\n' + sidebarContent + '\r\n');
    vueContent = vueContent.replace('<div class="app">\n', '<div class="app">\n' + sidebarContent + '\n');
    
    // Convert hardcoded onclick links in the sidebar to standard hrefs or handle them correctly?
    // The user didn't complain about broken sidebar links, but just in case:
    vueContent = vueContent.replace(/onclick="showPage\('dashboard'\)"/g, 'onclick="window.location.hash=\'#\'"');
    vueContent = vueContent.replace(/onclick="showPage\('products'\)"/g, 'onclick="window.location.hash=\'#products\'"');
    vueContent = vueContent.replace(/onclick="showPage\('sales'\)"/g, 'onclick="window.location.hash=\'#sales\'"');
    vueContent = vueContent.replace(/onclick="showPage\('customers'\)"/g, 'class="nav-link-custom active"');
}

// 2. Expand Add Customer Modal
const newAddModalHtml = `
    <!-- ADD CUSTOMER MODAL -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Customer</h5>
                    <button type="button" class="btn-close" @click="closeModal('addCustomerModal')"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Customer Name *</label>
                            <input type="text" class="form-control" v-model="newCustomer.name" placeholder="Enter full name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone *</label>
                            <input type="text" class="form-control" v-model="newCustomer.phone" placeholder="03XX-XXXXXXX">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" v-model="newCustomer.email" placeholder="email@example.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Customer Type</label>
                            <select class="form-select" v-model="newCustomer.type">
                                <option value="regular">Regular</option>
                                <option value="business">Business / B2B</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" rows="2" v-model="newCustomer.address" placeholder="Physical address"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Credit Limit (PKR)</label>
                            <input type="number" class="form-control" v-model.number="newCustomer.creditLimit" placeholder="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Opening Balance (PKR)</label>
                            <input type="number" class="form-control" v-model.number="newCustomer.openingBalance" placeholder="0">
                            <small class="text-muted">Current outstanding amount.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeModal('addCustomerModal')">Cancel</button>
                    <button type="button" class="btn btn-primary" @click="saveNewCustomer">Save Customer</button>
                </div>
            </div>
        </div>
    </div>
`;

// Replace the old addCustomerModal block
const oldAddModalRegex = /<!-- ADD CUSTOMER MODAL -->[\s\S]*?<!-- EDIT CUSTOMER MODAL -->/;
vueContent = vueContent.replace(oldAddModalRegex, newAddModalHtml + '\n\n    <!-- EDIT CUSTOMER MODAL -->');


// 3. Update Vue script variables and function for saveNewCustomer
const oldNewCustomerRef = `const newCustomer = ref({ name: '', phone: '' });`;
const updatedNewCustomerRef = `const newCustomer = ref({ name: '', phone: '', email: '', type: 'regular', address: '', creditLimit: 0, openingBalance: 0 });`;
vueContent = vueContent.replace(oldNewCustomerRef, updatedNewCustomerRef);

// Fix openAddModal reset
vueContent = vueContent.replace(/newCustomer.value = \{ name: '', phone: '' \};/, `newCustomer.value = { name: '', phone: '', email: '', type: 'regular', address: '', creditLimit: 0, openingBalance: 0 };`);

const updatedSaveCustomerFunc = `
function saveNewCustomer() {
    if(!newCustomer.value.name || !newCustomer.value.phone) {
        alert("Name and Phone are required.");
        return;
    }
    const newId = customers.value.length ? Math.max(...customers.value.map(c => c.id)) + 1 : 1;
    
    let parts = newCustomer.value.name.split(' ');
    let initials = parts.length > 1 ? parts[0][0] + parts[1][0] : parts[0][0];
    
    let bal = newCustomer.value.openingBalance || 0;
    
    customers.value.unshift({
        id: newId,
        name: newCustomer.value.name,
        initials: initials.toUpperCase(),
        phone: newCustomer.value.phone,
        email: newCustomer.value.email,
        address: newCustomer.value.address,
        creditLimit: newCustomer.value.creditLimit || 0,
        sales: 0,
        received: 0,
        balance: bal,
        lastActivity: 'Today',
        typeStr: newCustomer.value.type === 'regular' ? 'New Regular' : 'New B2B',
        type: newCustomer.value.type,
        status: bal > 0 ? 'outstanding' : 'clear'
    });
    
    closeModal('addCustomerModal');
}
`;

// replace old saveNewCustomer function
vueContent = vueContent.replace(/function saveNewCustomer\(\) \{[\s\S]*?closeModal\('addCustomerModal'\);\s*\}/, updatedSaveCustomerFunc);

fs.writeFileSync(vueFile, vueContent);
console.log("Updated CustomerView.vue with Sidebar and expanded Add Customer Modal.");
