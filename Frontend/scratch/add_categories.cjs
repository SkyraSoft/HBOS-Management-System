const fs = require('fs');
const file = 'c:/xampp/htdocs/HBOS/src/views/ProductView.vue';
let content = fs.readFileSync(file, 'utf8');

// 1. Insert Sidebar Nav Item
const sidebarTarget = `
                <div class="nav-item">
                    <button class="nav-link-custom active" data-page="products" type="button">
                        <i class="bi bi-box"></i>
                        <span>Products</span>
                    </button>
                </div>`;

const sidebarInsert = `
                <div class="nav-item">
                    <button class="nav-link-custom" data-page="categories" type="button">
                        <i class="bi bi-tags"></i>
                        <span>Categories</span>
                    </button>
                </div>`;

if (!content.includes('data-page="categories"')) {
    content = content.replace(sidebarTarget, sidebarTarget + '\n' + sidebarInsert);
}

// 2. Insert Categories Section HTML
const sectionTarget = `</section>


                <!-- =====================================================
                 ADD PRODUCT PAGE`;

const categoriesSectionHTML = `</section>

                <!-- =====================================================
                 CATEGORIES PAGE
            ====================================================== -->

                <section id="categoriesPage" class="page-section d-none">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">Categories</h1>
                            <p class="page-subtitle">Manage your product categories.</p>
                        </div>
                        <div class="header-actions">
                            <button class="btn-primary-custom" type="button" onclick="openCategoryModal()">
                                <i class="bi bi-plus-lg me-1"></i> Add New Category
                            </button>
                        </div>
                    </div>

                    <div class="card-custom">
                        <div class="toolbar">
                            <div><h2 class="card-title">Category Directory</h2></div>
                            <div class="search-box">
                                <i class="bi bi-search"></i>
                                <input type="text" id="categorySearch" placeholder="Search categories...">
                            </div>
                        </div>

                        <div class="table-wrapper">
                            <table class="product-table">
                                <thead>
                                    <tr>
                                        <th>Category ID</th>
                                        <th>Category Name</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="categoryTableBody"></tbody>
                            </table>
                        </div>
                        <div id="emptyCategories" class="empty-state d-none">
                            <div class="empty-icon"><i class="bi bi-tags"></i></div>
                            <h3>No Categories Yet</h3>
                            <p>Start by adding your first category.</p>
                            <button class="btn-primary-custom" type="button" onclick="openCategoryModal()">
                                <i class="bi bi-plus-lg me-1"></i> Add New Category
                            </button>
                        </div>
                    </div>

                    <!-- Category Modal Structure -->
                    <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="categoryModalLabel">Add Category</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="categoryForm">
                                        <input type="hidden" id="categoryId">
                                        <div class="mb-3">
                                            <label class="form-label">Category Name *</label>
                                            <input type="text" class="form-control" id="categoryName" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-select" id="categoryStatus">
                                                <option value="true">Active</option>
                                                <option value="false">Inactive</option>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary-custom" onclick="saveCategory()">Save Category</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>


                <!-- =====================================================
                 ADD PRODUCT PAGE`;

if (!content.includes('id="categoriesPage"')) {
    content = content.replace(sectionTarget, categoriesSectionHTML);
}

// 3. Add JS state for Categories
const jsDataTarget = `        let products = [`;
const jsDataInsert = `        let categories = [
            { id: 1, name: "Beverages", active: true },
            { id: 2, name: "Snacks", active: true },
            { id: 3, name: "Grocery", active: true },
            { id: 4, name: "Dairy", active: true },
            { id: 5, name: "Household", active: true },
            { id: 6, name: "Personal Care", active: true }
        ];

        let products = [`;

if (!content.includes('let categories = [')) {
    content = content.replace(jsDataTarget, jsDataInsert);
}

// 4. Update pages mapping
const pagesTarget = `            products: document.getElementById("productsPage"),
            add: document.getElementById("addPage"),
            details: document.getElementById("detailsPage"),
            edit: document.getElementById("editPage")`;
const pagesInsert = `            products: document.getElementById("productsPage"),
            categories: document.getElementById("categoriesPage"),
            add: document.getElementById("addPage"),
            details: document.getElementById("detailsPage"),
            edit: document.getElementById("editPage")`;

if (!content.includes('categories: document.getElementById("categoriesPage")')) {
    content = content.replace(pagesTarget, pagesInsert);
}

// 5. Update titles mapping
const titlesTarget = `            const titles = {
                products: "Products",
                add: "Products / Add New Product",
                details: "Products / Product Details",
                edit: "Products / Edit Product"
            };`;
const titlesInsert = `            const titles = {
                products: "Products",
                categories: "Products / Categories",
                add: "Products / Add New Product",
                details: "Products / Product Details",
                edit: "Products / Edit Product"
            };`;

if (!content.includes('categories: "Products / Categories"')) {
    content = content.replace(titlesTarget, titlesInsert);
}

// 6. Update showPage function
const showPageTarget = `            if (pageName === "products") {
                renderProducts();
            }`;
const showPageInsert = `            if (pageName === "products") {
                renderProducts();
            }

            if (pageName === "categories") {
                renderCategories();
            }`;

if (!content.includes('renderCategories();')) {
    content = content.replace(showPageTarget, showPageInsert);
}

// 7. Add Category CRUD logic
const logicTarget = `        /* ============================================================
           INITIAL LOAD
        ============================================================ */`;

const logicInsert = `        /* ============================================================
           CATEGORIES LOGIC
        ============================================================ */

        function renderCategories(searchTerm = "") {
            const tbody = document.getElementById("categoryTableBody");
            const empty = document.getElementById("emptyCategories");
            const query = searchTerm.toLowerCase().trim();

            const filtered = categories.filter(c => c.name.toLowerCase().includes(query));

            tbody.innerHTML = "";

            filtered.forEach(cat => {
                let status = cat.active ? "Active" : "Inactive";
                let statusClass = cat.active ? "badge-active" : "badge-out";

                const tr = document.createElement("tr");
                tr.innerHTML = \`
                    <td>#CAT-\${cat.id.toString().padStart(3, '0')}</td>
                    <td><strong>\${escapeHtml(cat.name)}</strong></td>
                    <td><span class="badge-custom \${statusClass}">\${status}</span></td>
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
            document.getElementById("categoryStatus").value = cat.active.toString();
            document.getElementById("categoryModalLabel").textContent = "Edit Category";

            if(!catModalInstance) {
                catModalInstance = new bootstrap.Modal(document.getElementById('categoryModal'));
            }
            catModalInstance.show();
        }

        function saveCategory() {
            const idVal = document.getElementById("categoryId").value;
            const nameVal = document.getElementById("categoryName").value.trim();
            const activeVal = document.getElementById("categoryStatus").value === "true";

            if (!nameVal) {
                alert("Category name is required.");
                return;
            }

            if (idVal) {
                const id = parseInt(idVal);
                const cat = categories.find(c => c.id === id);
                if (cat) {
                    cat.name = nameVal;
                    cat.active = activeVal;
                    showToast("Category updated successfully.");
                }
            } else {
                const newId = categories.length > 0 ? Math.max(...categories.map(c => c.id)) + 1 : 1;
                categories.push({ id: newId, name: nameVal, active: activeVal });
                showToast("Category added successfully.");
            }

            if(catModalInstance) catModalInstance.hide();
            renderCategories(document.getElementById("categorySearch").value);
        }

        function deleteCategory(id) {
            const cat = categories.find(c => c.id === id);
            if (!cat) return;

            if (!confirm(\`Delete category "\${cat.name}"?\`)) return;

            categories = categories.filter(c => c.id !== id);
            showToast("Category deleted successfully.");
            renderCategories(document.getElementById("categorySearch").value);
        }

        document.getElementById("categorySearch").addEventListener("input", function () {
            renderCategories(this.value);
        });

        /* ============================================================
           INITIAL LOAD
        ============================================================ */`;

if (!content.includes('function renderCategories')) {
    content = content.replace(logicTarget, logicInsert);
}

// 8. Add global functions for window
const windowTarget = `        window.showPage = showPage;`;
const windowInsert = `        window.showPage = showPage;
        window.openCategoryModal = openCategoryModal;
        window.editCategory = editCategory;
        window.saveCategory = saveCategory;
        window.deleteCategory = deleteCategory;`;

if (!content.includes('window.openCategoryModal = openCategoryModal;')) {
    content = content.replace(windowTarget, windowInsert);
}

// Also add 'categories' to the categories dropdown inside the form if needed, but not explicitly requested.

fs.writeFileSync(file, content);
console.log("Changes applied successfully!");
