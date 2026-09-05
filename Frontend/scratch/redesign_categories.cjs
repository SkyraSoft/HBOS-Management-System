const fs = require('fs');
const file = 'c:/xampp/htdocs/HBOS/src/views/ProductView.vue';
let lines = fs.readFileSync(file, 'utf8').split('\n');

const catPageStart = lines.findIndex(l => l.includes('<section id="categoriesPage"'));
const addPageStart = lines.findIndex(l => l.includes('<section id="addPage"'));

// New HTML for categoriesPage
const newHtml = `                <section id="categoriesPage" class="page-section d-none">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">Shop & Categories Setup</h1>
                            <p class="page-subtitle">Manage your shop information and product categories.</p>
                        </div>
                    </div>

                    <form id="shopInfoForm" class="form-layout mb-5">
                        <div class="form-card">
                            <div class="section-heading">
                                <i class="bi bi-shop"></i>
                                Shop Information
                            </div>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">Shop Name *</label>
                                    <input type="text" class="form-control" id="shopName" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Owner Name *</label>
                                    <input type="text" class="form-control" id="shopOwner" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Shop Phone *</label>
                                    <input type="tel" class="form-control" id="shopPhone" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Shop Email</label>
                                    <input type="email" class="form-control" id="shopEmail">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Shop Address *</label>
                                    <textarea class="form-control" id="shopAddress" rows="2" required></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">City *</label>
                                    <input type="text" class="form-control" id="shopCity" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Business Type</label>
                                    <select class="form-select" id="shopType">
                                        <option value="Retail">Retail</option>
                                        <option value="Wholesale">Wholesale</option>
                                        <option value="Services">Services</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mt-4">
                                <button class="btn-primary-custom" type="button" onclick="saveShopInfo()">
                                    <i class="bi bi-save me-1"></i> Save Shop Information
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="form-card h-100">
                                <div class="section-heading">
                                    <i class="bi bi-tags"></i>
                                    <span id="categoryFormMode">Add New Category</span>
                                </div>
                                <form id="categoryForm">
                                    <input type="hidden" id="categoryId">
                                    <div class="mb-3">
                                        <label class="form-label">Category Name *</label>
                                        <input type="text" class="form-control" id="categoryName" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Description (Optional)</label>
                                        <textarea class="form-control" id="categoryDescription" rows="3" placeholder="Brief description..."></textarea>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn-primary-custom w-100" type="button" onclick="saveCategory()">
                                            <i class="bi bi-check2 me-1"></i> Save Category
                                        </button>
                                        <button class="btn-outline-custom w-100 d-none" type="button" id="cancelCatBtn" onclick="resetCategoryForm()">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="form-card h-100 p-0 overflow-hidden d-flex flex-column">
                                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                                    <div class="section-heading mb-0">
                                        <i class="bi bi-list-ul"></i>
                                        Category Directory
                                    </div>
                                    <div class="search-box">
                                        <i class="bi bi-search"></i>
                                        <input type="text" id="categorySearch" placeholder="Search categories...">
                                    </div>
                                </div>

                                <div id="emptyCategories" class="empty-state d-none">
                                    <div class="empty-state-icon">
                                        <i class="bi bi-tags"></i>
                                    </div>
                                    <h3>No Categories Yet</h3>
                                    <p>Start by adding your first product category using the form.</p>
                                </div>

                                <div class="table-wrapper flex-grow-1 custom-scrollbar">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Category Name</th>
                                                <th>Description</th>
                                                <th>Products</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="categoryTableBody">
                                            <!-- Dynamically populated -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- =====================================================
                 ADD PRODUCT PAGE
            ====================================================== -->`;

lines.splice(catPageStart, addPageStart - catPageStart, newHtml);

let content = lines.join('\n');

// 1. Inject shopInfo state
if (!content.includes('let shopInfo = {')) {
    content = content.replace(
        '        let categories = [',
        `        let shopInfo = { name: '', owner: '', phone: '', email: '', address: '', city: '', type: 'Retail' };\n\n        let categories = [`
    );
}

// 2. Add saveShopInfo function
if (!content.includes('function saveShopInfo()')) {
    content = content.replace(
        '        /* ============================================================',
        `        window.saveShopInfo = function() {
            shopInfo.name = document.getElementById('shopName').value;
            shopInfo.owner = document.getElementById('shopOwner').value;
            shopInfo.phone = document.getElementById('shopPhone').value;
            shopInfo.email = document.getElementById('shopEmail').value;
            shopInfo.address = document.getElementById('shopAddress').value;
            shopInfo.city = document.getElementById('shopCity').value;
            shopInfo.type = document.getElementById('shopType').value;
            
            showToast("Shop information saved successfully!");
        };\n\n        /* ============================================================`
    );
}

// 3. Update Category Logic
content = content.replace(
    /function saveCategory\(\) \{[\s\S]*?renderCategories\(document\.getElementById\("categorySearch"\)\.value\);\s*\}/,
    `function resetCategoryForm() {
            document.getElementById("categoryForm").reset();
            document.getElementById("categoryId").value = "";
            document.getElementById("categoryFormMode").textContent = "Add New Category";
            document.getElementById("cancelCatBtn").classList.add("d-none");
        }
        window.resetCategoryForm = resetCategoryForm;

        function saveCategory() {
            const idVal = document.getElementById("categoryId").value;
            const nameVal = document.getElementById("categoryName").value.trim();
            const descVal = document.getElementById("categoryDescription").value.trim();

            if (!nameVal) {
                alert("Category name is required.");
                return;
            }

            if (idVal) {
                const id = parseInt(idVal);
                const cat = categories.find(c => c.id === id);
                if (cat) {
                    if (cat.name !== nameVal) {
                        products.forEach(p => {
                            if(p.category === cat.name) p.category = nameVal;
                        });
                    }
                    cat.name = nameVal;
                    cat.description = descVal;
                    showToast("Category updated successfully!");
                }
            } else {
                const newId = categories.length > 0 ? Math.max(...categories.map(c => c.id)) + 1 : 1;
                categories.push({ id: newId, name: nameVal, description: descVal });
                showToast("Category added successfully!");
            }

            populateCategoryDropdowns();
            renderCategories(document.getElementById("categorySearch").value);
            resetCategoryForm();
        }`
);

// 4. Update editCategory Logic
content = content.replace(
    /function editCategory\(id\) \{[\s\S]*?if \(!document\.querySelector\('\.modal-backdrop'\)\) \{[\s\S]*?document\.body\.appendChild\(backdrop\);\s*\}\s*\}/,
    `function editCategory(id) {
            const cat = categories.find(c => c.id === id);
            if(!cat) return;
            
            document.getElementById("categoryId").value = cat.id;
            document.getElementById("categoryName").value = cat.name;
            document.getElementById("categoryDescription").value = cat.description || '';
            
            document.getElementById("categoryFormMode").textContent = "Edit Category";
            document.getElementById("cancelCatBtn").classList.remove("d-none");
            
            // Scroll to top to see form
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }`
);

// 5. Remove openCategoryModal & closeCategoryModal leftovers if any
content = content.replace(/function openCategoryModal\(\) \{[\s\S]*?document\.body\.appendChild\(backdrop\);\s*\}\s*\}/, '');
content = content.replace(/function closeCategoryModal\(\) \{[\s\S]*?backdrop\.remove\(\);\s*\}\s*\}/, '');
content = content.replace(/window\.openCategoryModal = openCategoryModal;/, '');
content = content.replace(/window\.closeCategoryModal = closeCategoryModal;/, '');
content = content.replace(/window\.saveCategory = saveCategory;/, 'window.saveCategory = saveCategory;');

// Write back
fs.writeFileSync(file, content);
console.log("Redesigned Categories setup page!");
