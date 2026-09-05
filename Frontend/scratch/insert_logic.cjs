const fs = require('fs');
const file = 'c:/xampp/htdocs/HBOS/src/views/ProductView.vue';
let content = fs.readFileSync(file, 'utf8');

const logicInsert = `        /* ============================================================
           CATEGORIES LOGIC
        ============================================================ */

        function populateCategoryDropdowns() {
            const addSelect = document.getElementById("category");
            const editSelect = document.getElementById("editCategory");
            
            const optionsHtml = '<option value="">Select Category</option>' + 
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
        });

        `;

if (!content.includes('function renderCategories')) {
    const splitStr = '/* ============================================================';
    const lines = content.split(splitStr);
    
    // Find the part that contains INITIAL LOAD
    let foundIndex = -1;
    for (let i = 0; i < lines.length; i++) {
        if (lines[i].includes('INITIAL LOAD')) {
            foundIndex = i;
            break;
        }
    }
    
    if (foundIndex !== -1) {
        lines[foundIndex] = logicInsert + splitStr + lines[foundIndex];
        content = lines.join(splitStr);
        fs.writeFileSync(file, content);
        console.log("Successfully inserted Categories logic.");
    } else {
        console.log("Could not find INITIAL LOAD block");
    }
} else {
    console.log("Logic already exists.");
}
