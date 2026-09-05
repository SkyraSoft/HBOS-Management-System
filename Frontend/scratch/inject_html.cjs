const fs = require('fs');
const file = 'c:/xampp/htdocs/HBOS/src/views/ProductView.vue';
let content = fs.readFileSync(file, 'utf8');

const targetStr = `<!-- =====================================================
                 ADD PRODUCT PAGE
            ====================================================== -->`;

const htmlToInsert = `<!-- =====================================================
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
                                        <th>Category Name</th>
                                        <th>Description</th>
                                        <th>Products</th>
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
                                            <label class="form-label">Description (Optional)</label>
                                            <textarea class="form-control" id="categoryDescription" rows="2" placeholder="Brief description..."></textarea>
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

                `;

if (!content.includes('id="categoriesPage"')) {
    const lines = content.split(targetStr);
    if (lines.length > 1) {
        content = lines[0] + htmlToInsert + targetStr + lines.slice(1).join(targetStr);
        fs.writeFileSync(file, content);
        console.log("HTML inserted successfully.");
    } else {
        console.log("Could not find ADD PRODUCT PAGE target string.");
    }
} else {
    console.log("Categories HTML already exists.");
}
