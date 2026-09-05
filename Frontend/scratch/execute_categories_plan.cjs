const fs = require('fs');
const file = 'c:/xampp/htdocs/HBOS/src/views/ProductView.vue';
let content = fs.readFileSync(file, 'utf8');

// 1. Update Categories Array Schema
let categoriesSchemaTarget = `        let categories = [
            { id: 1, name: "Beverages", active: true },
            { id: 2, name: "Snacks", active: true },
            { id: 3, name: "Grocery", active: true },
            { id: 4, name: "Dairy", active: true },
            { id: 5, name: "Household", active: true },
            { id: 6, name: "Personal Care", active: true }
        ];`;
let categoriesSchemaReplace = `        let categories = [
            { id: 1, name: "Beverages", description: "Drinks and liquid refreshments" },
            { id: 2, name: "Snacks", description: "Chips, cookies, and quick bites" },
            { id: 3, name: "Grocery", description: "Everyday household grocery items" },
            { id: 4, name: "Dairy", description: "Milk, cheese, and dairy products" },
            { id: 5, name: "Household", description: "Cleaning supplies and tools" },
            { id: 6, name: "Personal Care", description: "Cosmetics and personal hygiene" }
        ];`;
if (content.includes(categoriesSchemaTarget)) {
    content = content.replace(categoriesSchemaTarget, categoriesSchemaReplace);
}

// 2. Update Table Headers
let tableHeadersTarget = `                                    <tr>
                                        <th>Category ID</th>
                                        <th>Category Name</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>`;
let tableHeadersReplace = `                                    <tr>
                                        <th>Category Name</th>
                                        <th>Description</th>
                                        <th>Products</th>
                                        <th class="text-end">Action</th>
                                    </tr>`;
if (content.includes(tableHeadersTarget)) {
    content = content.replace(tableHeadersTarget, tableHeadersReplace);
}

// 3. Update Modal HTML
let modalHtmlTarget = `                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-select" id="categoryStatus">
                                                <option value="true">Active</option>
                                                <option value="false">Inactive</option>
                                            </select>
                                        </div>`;
let modalHtmlReplace = `                                        <div class="mb-3">
                                            <label class="form-label">Description (Optional)</label>
                                            <textarea class="form-control" id="categoryDescription" rows="2" placeholder="Brief description..."></textarea>
                                        </div>`;
if (content.includes(modalHtmlTarget)) {
    content = content.replace(modalHtmlTarget, modalHtmlReplace);
}

// 4. Update JS logic block for categories
let logicStart = `        function renderCategories(searchTerm = "") {`;
let logicEnd = `        document.getElementById("categorySearch").addEventListener("input", function () {
            renderCategories(this.value);
        });`;
// I will just use regex to replace this whole block
let newLogic = `        function populateCategoryDropdowns() {
            const addSelect = document.getElementById("category");
            const editSelect = document.getElementById("editCategory");
            
            const optionsHtml = \`<option value="">Select Category</option>\` + 
                categories.map(c => \`<option value="\${c.name}">\${c.name}</option>\`).join("");
            
            if (addSelect) addSelect.innerHTML = optionsHtml;
            if (editSelect) editSelect.innerHTML = optionsHtml;
        }

        function renderCategories(searchTerm = "") {
            const tbody = document.getElementById("categoryTableBody");
            const empty = document.getElementById("emptyCategories");
            const query = searchTerm.toLowerCase().trim();

            const filtered = categories.filter(c => c.name.toLowerCase().includes(query));

            tbody.innerHTML = "";

            filtered.forEach(cat => {
                const productCount = products.filter(p => p.category === cat.name).length;

                const tr = document.createElement("tr");
                tr.innerHTML = \`
                    <td><strong>\${escapeHtml(cat.name)}</strong></td>
                    <td><span class="text-muted">\${escapeHtml(cat.description || "—")}</span></td>
                    <td><span class="badge-custom badge-active">\${productCount}</span></td>
                    <td class="text-end">
                        <button class="btn-icon" title="Edit" onclick="editCategory(\${cat.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-icon text-danger" title="Delete" onclick="deleteCategory(\${cat.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                \`;
                tbody.appendChild(tr);
            });

            if (filtered.length === 0) {
                empty.classList.remove("d-none");
                document.querySelector("#categoriesPage .table-wrapper").classList.add("d-none");
            } else {
                empty.classList.add("d-none");
                document.querySelector("#categoriesPage .table-wrapper").classList.remove("d-none");
            }
        }

        let catModalInstance = null;

        function openCategoryModal() {
            document.getElementById("categoryForm").reset();
            document.getElementById("categoryId").value = "";
            document.getElementById("categoryModalLabel").textContent = "Add Category";
            
            if(!catModalInstance) {
                catModalInstance = new bootstrap.Modal(document.getElementById('categoryModal'));
            }
            catModalInstance.show();
        }

        function editCategory(id) {
            const cat = categories.find(c => c.id === id);
            if (!cat) return;

            document.getElementById("categoryId").value = cat.id;
            document.getElementById("categoryName").value = cat.name;
            document.getElementById("categoryDescription").value = cat.description || "";
            document.getElementById("categoryModalLabel").textContent = "Edit Category";

            if(!catModalInstance) {
                catModalInstance = new bootstrap.Modal(document.getElementById('categoryModal'));
            }
            catModalInstance.show();
        }

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
                    
                    // Update products with new category name if it changed
                    if (cat.name !== nameVal) {
                        products.forEach(p => {
                            if(p.category === cat.name) p.category = nameVal;
                        });
                    }

                    cat.name = nameVal;
                    cat.description = descVal;
                    showToast("Category updated successfully.");
                }
            } else {
                const newId = categories.length > 0 ? Math.max(...categories.map(c => c.id)) + 1 : 1;
                categories.push({ id: newId, name: nameVal, description: descVal });
                showToast("Category added successfully.");
            }

            if(catModalInstance) catModalInstance.hide();
            populateCategoryDropdowns();
            renderCategories(document.getElementById("categorySearch").value);
            // Refresh products if category name changed
            if (idVal) renderProducts(document.getElementById("productSearch") ? document.getElementById("productSearch").value : "");
        }

        function deleteCategory(id) {
            const cat = categories.find(c => c.id === id);
            if (!cat) return;
            
            const assignedProducts = products.filter(p => p.category === cat.name).length;
            if (assignedProducts > 0) {
                if (!confirm(\`Category "\${cat.name}" is assigned to \${assignedProducts} product(s). Are you sure you want to delete it? This will unassign the products.\`)) return;
                
                products.forEach(p => {
                    if (p.category === cat.name) p.category = "";
                });
            } else {
                if (!confirm(\`Delete category "\${cat.name}"?\`)) return;
            }

            categories = categories.filter(c => c.id !== id);
            showToast("Category deleted successfully.");
            populateCategoryDropdowns();
            renderCategories(document.getElementById("categorySearch").value);
            if (assignedProducts > 0) renderProducts(document.getElementById("productSearch") ? document.getElementById("productSearch").value : "");
        }

        document.getElementById("categorySearch").addEventListener("input", function () {
            renderCategories(this.value);
        });`;

const logicRegex = /function renderCategories\([\s\S]*?renderCategories\(this\.value\);\s*\}\);/m;
if (logicRegex.test(content)) {
    content = content.replace(logicRegex, newLogic);
} else {
    console.log("Could not find logic regex");
}

// 5. Empty the <select> tags so populateCategoryDropdowns can take over
const addSelectTarget = /<select class="form-select" id="category">[\s\S]*?<\/select>/;
const addSelectReplace = `<select class="form-select" id="category">
                                        <option value="">Select Category</option>
                                    </select>`;
if (addSelectTarget.test(content)) {
    content = content.replace(addSelectTarget, addSelectReplace);
}

const editSelectTarget = /<select class="form-select" id="editCategory">[\s\S]*?<\/select>/;
const editSelectReplace = `<select class="form-select" id="editCategory">
                                        <option value="">Select Category</option>
                                    </select>`;
if (editSelectTarget.test(content)) {
    content = content.replace(editSelectTarget, editSelectReplace);
}

// 6. Fix generateSKU dynamic generation
const genSkuTarget = `            if (category === "Beverages") {
                prefix = "BEV";
            }

            if (category === "Grocery") {
                prefix = "GRC";
            }

            if (category === "Snacks") {
                prefix = "SNK";
            }

            if (category === "Dairy") {
                prefix = "DRY";
            }

            if (category === "Household") {
                prefix = "HOU";
            }

            if (category === "Personal Care") {
                prefix = "PER";
            }`;
const genSkuReplace = `            if (category) {
                const words = category.split(" ");
                if (words.length > 1) {
                    prefix = words[0].substring(0, 1).toUpperCase() + words[1].substring(0, 2).toUpperCase();
                } else {
                    prefix = category.substring(0, 3).toUpperCase();
                }
            }`;
if (content.includes(genSkuTarget)) {
    content = content.replace(genSkuTarget, genSkuReplace);
}

// 7. Call populateCategoryDropdowns on initial load
const initialLoadTarget = `        renderProducts();

        calculateProfit();`;
const initialLoadReplace = `        populateCategoryDropdowns();
        renderProducts();

        calculateProfit();`;
if (content.includes(initialLoadTarget)) {
    content = content.replace(initialLoadTarget, initialLoadReplace);
}

fs.writeFileSync(file, content);
console.log("Categories plan executed successfully.");
