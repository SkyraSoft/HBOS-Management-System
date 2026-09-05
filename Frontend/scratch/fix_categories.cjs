const fs = require('fs');
const file = 'c:/xampp/htdocs/HBOS/src/views/ProductView.vue';
let content = fs.readFileSync(file, 'utf8');

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
    content = content.replace(/[\s\S]*INITIAL LOAD[\s\S]*?(?=\r?\n\s*renderProducts\(\);)/, function(match) {
        return match.replace(/[\/\* =]*INITIAL LOAD[\s\S]*?(?=\r?\n\s*renderProducts\(\);)/, logicInsert);
    });
    fs.writeFileSync(file, content);
    console.log("Categories logic injected!");
} else {
    console.log("Categories logic already injected!");
}
