const fs = require('fs');
const file = 'c:/xampp/htdocs/HBOS/src/views/ProductView.vue';
let content = fs.readFileSync(file, 'utf8');

// Replace openCategoryModal
content = content.replace(
    `        function openCategoryModal() {
            document.getElementById("categoryForm").reset();
            document.getElementById("categoryId").value = "";
            document.getElementById("categoryModalLabel").textContent = "Add Category";
            
            if(!catModalInstance) {
                catModalInstance = new bootstrap.Modal(document.getElementById('categoryModal'));
            }
            catModalInstance.show();
        }`,
    `        function openCategoryModal() {
            document.getElementById("categoryForm").reset();
            document.getElementById("categoryId").value = "";
            document.getElementById("categoryModalLabel").textContent = "Add Category";
            
            const modal = document.getElementById('categoryModal');
            modal.style.display = 'block';
            setTimeout(() => { modal.classList.add('show'); }, 10);
            
            if (!document.querySelector('.modal-backdrop')) {
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
            }
        }`
);

// Replace closeCategoryModal
content = content.replace(
    `        function closeCategoryModal() {
            if(catModalInstance) {
                catModalInstance.hide();
            }
        }`,
    `        function closeCategoryModal() {
            const modal = document.getElementById('categoryModal');
            modal.classList.remove('show');
            setTimeout(() => { modal.style.display = 'none'; }, 150);
            
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
        }`
);

// Replace editCategory
content = content.replace(
    `        function editCategory(id) {
            const cat = categories.find(c => c.id === id);
            if(!cat) return;
            
            document.getElementById("categoryId").value = cat.id;
            document.getElementById("catName").value = cat.name;
            document.getElementById("catDesc").value = cat.description || '';
            document.getElementById("categoryModalLabel").textContent = "Edit Category";
            
            if(!catModalInstance) {
                catModalInstance = new bootstrap.Modal(document.getElementById('categoryModal'));
            }
            catModalInstance.show();
        }`,
    `        function editCategory(id) {
            const cat = categories.find(c => c.id === id);
            if(!cat) return;
            
            document.getElementById("categoryId").value = cat.id;
            document.getElementById("catName").value = cat.name;
            document.getElementById("catDesc").value = cat.description || '';
            document.getElementById("categoryModalLabel").textContent = "Edit Category";
            
            const modal = document.getElementById('categoryModal');
            modal.style.display = 'block';
            setTimeout(() => { modal.classList.add('show'); }, 10);
            
            if (!document.querySelector('.modal-backdrop')) {
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
            }
        }`
);

// We should also replace the modal's Cancel/Close buttons from using data-bs-dismiss="modal" which doesn't work without Bootstrap JS.
// Actually, data-bs-dismiss="modal" will just do nothing, we need to add onclick="closeCategoryModal()" to them.
content = content.replace(
    `<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>`,
    `<button type="button" class="btn-close" onclick="closeCategoryModal()" aria-label="Close"></button>`
);

content = content.replace(
    `<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>`,
    `<button type="button" class="btn btn-secondary" onclick="closeCategoryModal()">Cancel</button>`
);


fs.writeFileSync(file, content);
console.log("Replaced modal logic with vanilla JS!");
