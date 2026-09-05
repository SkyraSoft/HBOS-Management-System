const fs = require('fs');
const file = 'c:/xampp/htdocs/HBOS/src/views/CustomerView.vue';
let content = fs.readFileSync(file, 'utf8');

// The new modals HTML
const modalsHtml = `
    <!-- MODALS -->
    
    <!-- ADD CUSTOMER MODAL -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Customer</h5>
                    <button type="button" class="btn-close" onclick="closeCustomerModal('addCustomerModal')"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Customer Name</label>
                        <input type="text" class="form-control" id="addName">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" id="addPhone">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeCustomerModal('addCustomerModal')">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveNewCustomer()">Save Customer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- EDIT CUSTOMER MODAL -->
    <div class="modal fade" id="editCustomerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Customer</h5>
                    <button type="button" class="btn-close" onclick="closeCustomerModal('editCustomerModal')"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editId">
                    <div class="mb-3">
                        <label class="form-label">Customer Name</label>
                        <input type="text" class="form-control" id="editName">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" id="editPhone">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeCustomerModal('editCustomerModal')">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateCustomer()">Update</button>
                </div>
            </div>
        </div>
    </div>

    <!-- VIEW CUSTOMER MODAL -->
    <div class="modal fade" id="viewCustomerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Customer Details</h5>
                    <button type="button" class="btn-close" onclick="closeCustomerModal('viewCustomerModal')"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Name:</strong> <span id="viewName"></span></p>
                    <p><strong>Phone:</strong> <span id="viewPhone"></span></p>
                    <p><strong>Total Sales:</strong> PKR <span id="viewSales"></span></p>
                    <p><strong>Received:</strong> PKR <span id="viewReceived"></span></p>
                    <p><strong>Balance:</strong> PKR <span id="viewBalance"></span></p>
                    <p><strong>Status:</strong> <span id="viewStatus"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeCustomerModal('viewCustomerModal')">Close</button>
                </div>
            </div>
        </div>
    </div>
    
`;

// Replace `</div>\n</template>` with modals + `</div>\n</template>`
content = content.replace(/<\/div>\s*<\/template>/, modalsHtml + '\n</div>\n</template>');

// Now the JavaScript
const jsLogic = `
<script setup>
import { onMounted } from 'vue';

onMounted(() => {
    window.customers = [];
    for (let i = 1; i <= 85; i++) {
        window.customers.push({
            id: i,
            name: 'Customer ' + i,
            initials: 'C' + i,
            phone: '0300-' + (1000000 + i),
            sales: 10000 + i * 10,
            received: 5000 + i * 5,
            balance: 5000 + i * 5,
            lastActivity: 'Oct 12, 2023',
            type: 'Sale',
            status: i % 2 === 0 ? 'clear' : 'outstanding'
        });
    }

    window.currentPage = 1;
    window.itemsPerPage = 10;

    window.renderTable = function() {
        const tbody = document.getElementById("customerTable");
        if (!tbody) return;

        const start = (window.currentPage - 1) * window.itemsPerPage;
        const end = start + window.itemsPerPage;
        const pageData = window.customers.slice(start, end);

        let html = '';
        pageData.forEach(c => {
            html += \`
                <tr data-type="regular" data-status="\${c.status}">
                    <td>
                        <div class="customer-info">
                            <div class="customer-avatar">\${c.initials}</div>
                            <div>
                                <div class="customer-name">\${c.name}</div>
                                <div class="customer-phone">\${c.phone}</div>
                            </div>
                        </div>
                    </td>
                    <td>PKR \${c.sales.toLocaleString()}</td>
                    <td class="\${c.status === 'outstanding' ? 'amount danger' : ''}">PKR \${c.received.toLocaleString()}</td>
                    <td>PKR \${c.balance.toLocaleString()}</td>
                    <td>\${c.lastActivity}<br>\${c.type}</td>
                    <td><span class="status \${c.status}">\${c.status.charAt(0).toUpperCase() + c.status.slice(1)}</span></td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn" onclick="openViewCustomer(\${c.id})"><i class="bi bi-eye"></i></button>
                            <button class="action-btn" onclick="openEditCustomer(\${c.id})"><i class="bi bi-pencil"></i></button>
                            <button class="action-btn text-danger" onclick="deleteCustomer(\${c.id})" style="color: red;"><i class="bi bi-trash"></i></button>
                        </div>
                    </td>
                </tr>\`;
        });
        tbody.innerHTML = html;

        document.getElementById("resultCount").innerText = \`Showing \${start + 1} to \${Math.min(end, window.customers.length)} of \${window.customers.length} results\`;
        renderPagination();
    };

    window.renderPagination = function() {
        const totalPages = Math.ceil(window.customers.length / window.itemsPerPage);
        const container = document.querySelector(".pagination-custom");
        if (!container) return;

        let html = \`<button class="page-btn" onclick="changePage(\${window.currentPage - 1})" \${window.currentPage === 1 ? 'disabled' : ''}><i class="bi bi-chevron-left"></i></button>\`;
        
        let startPage = Math.max(1, window.currentPage - 2);
        let endPage = Math.min(totalPages, window.currentPage + 2);

        if (startPage > 1) {
            html += \`<button class="page-btn" onclick="changePage(1)">1</button>\`;
            if (startPage > 2) html += \`<button class="page-btn">...</button>\`;
        }

        for (let i = startPage; i <= endPage; i++) {
            html += \`<button class="page-btn \${i === window.currentPage ? 'active' : ''}" onclick="changePage(\${i})">\${i}</button>\`;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) html += \`<button class="page-btn">...</button>\`;
            html += \`<button class="page-btn" onclick="changePage(\${totalPages})">\${totalPages}</button>\`;
        }

        html += \`<button class="page-btn" onclick="changePage(\${window.currentPage + 1})" \${window.currentPage === totalPages ? 'disabled' : ''}><i class="bi bi-chevron-right"></i></button>\`;

        container.innerHTML = html;
    };

    window.changePage = function(p) {
        const totalPages = Math.ceil(window.customers.length / window.itemsPerPage);
        if (p >= 1 && p <= totalPages) {
            window.currentPage = p;
            window.renderTable();
        }
    };

    window.openAddCustomer = function() {
        document.getElementById('addName').value = '';
        document.getElementById('addPhone').value = '';
        document.getElementById("addCustomerModal").style.display = 'block';
        document.getElementById("addCustomerModal").classList.add('show');
        addBackdrop();
    };

    window.saveNewCustomer = function() {
        const name = document.getElementById('addName').value;
        const phone = document.getElementById('addPhone').value;
        if (!name) return alert('Name required');
        
        const newId = window.customers.length ? Math.max(...window.customers.map(c => c.id)) + 1 : 1;
        window.customers.unshift({
            id: newId,
            name: name,
            initials: name.substring(0, 2).toUpperCase(),
            phone: phone,
            sales: 0,
            received: 0,
            balance: 0,
            lastActivity: 'Today',
            type: 'New',
            status: 'clear'
        });
        
        window.closeCustomerModal('addCustomerModal');
        window.renderTable();
    };

    window.openEditCustomer = function(id) {
        const c = window.customers.find(x => x.id === id);
        if (c) {
            document.getElementById('editId').value = c.id;
            document.getElementById('editName').value = c.name;
            document.getElementById('editPhone').value = c.phone;
            document.getElementById("editCustomerModal").style.display = 'block';
            document.getElementById("editCustomerModal").classList.add('show');
            addBackdrop();
        }
    };

    window.updateCustomer = function() {
        const id = parseInt(document.getElementById('editId').value);
        const name = document.getElementById('editName').value;
        const phone = document.getElementById('editPhone').value;
        
        const index = window.customers.findIndex(x => x.id === id);
        if (index > -1) {
            window.customers[index].name = name;
            window.customers[index].phone = phone;
            window.customers[index].initials = name.substring(0, 2).toUpperCase();
        }
        
        window.closeCustomerModal('editCustomerModal');
        window.renderTable();
    };

    window.openViewCustomer = function(id) {
        const c = window.customers.find(x => x.id === id);
        if (c) {
            document.getElementById('viewName').innerText = c.name;
            document.getElementById('viewPhone').innerText = c.phone;
            document.getElementById('viewSales').innerText = c.sales.toLocaleString();
            document.getElementById('viewReceived').innerText = c.received.toLocaleString();
            document.getElementById('viewBalance').innerText = c.balance.toLocaleString();
            document.getElementById('viewStatus').innerText = c.status;
            
            document.getElementById("viewCustomerModal").style.display = 'block';
            document.getElementById("viewCustomerModal").classList.add('show');
            addBackdrop();
        }
    };
    
    window.deleteCustomer = function(id) {
        if(confirm('Are you sure you want to delete this customer?')) {
            window.customers = window.customers.filter(c => c.id !== id);
            window.renderTable();
        }
    };

    window.closeCustomerModal = function(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('show');
        }
        const bd = document.querySelector('.modal-backdrop');
        if (bd) bd.remove();
    };

    function addBackdrop() {
        if (!document.querySelector('.modal-backdrop')) {
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
        }
    }

    // Attach Add Customer button event
    const addBtn = document.querySelector('.btn-primary i.bi-plus-lg');
    if (addBtn && addBtn.parentElement) {
        addBtn.parentElement.setAttribute('onclick', 'openAddCustomer()');
    }
    
    // Clear out the static table rows initially to let renderTable take over
    document.getElementById("customerTable").innerHTML = "";

    window.renderTable();
});
</script>
`;

content = content.replace(/<script setup>[\s\S]*?<\/script>/, jsLogic);

fs.writeFileSync(file, content);
console.log("Updated CustomerView logic!");
