const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'src', 'views', 'ProductView.vue');
let content = fs.readFileSync(filePath, 'utf8');

// Replace the single category form with 3 forms.
const categoriesSection = `
                    <div class="category-header">
                        <h2>Brands</h2>
                    </div>
                    
                    <div class="category-form">
                        <div class="form-group">
                            <input type="text" v-model="newBrandName" placeholder="Enter brand name" />
                        </div>
                        <button class="action-btn" style="background: var(--primary); color: white;" @click="handleAddBrand">Add Brand</button>
                    </div>

                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="b in brands" :key="b.id">
                                <td>{{ b.name }}</td>
                                <td style="text-align: right;">
                                    <button class="action-btn" @click="handleDeleteBrand(b.id)" style="color: var(--danger); border-color: var(--danger-light); background: var(--danger-light);">Remove</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="category-header" style="margin-top: 40px;">
                        <h2>Categories</h2>
                    </div>
`;

content = content.replace(
  '<div class="category-header">\n                        <h2>Categories</h2>\n                    </div>',
  categoriesSection.trim()
);


const subcatsSection = `
                    <div class="category-header" style="margin-top: 40px;">
                        <h2>Subcategories</h2>
                    </div>
                    
                    <div class="category-form">
                        <div class="form-group">
                            <select v-model="newSubcategoryCatId">
                                <option value="">Select Parent Category</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <input type="text" v-model="newSubcategoryName" placeholder="Enter subcategory name" />
                        </div>
                        <button class="action-btn" style="background: var(--primary); color: white;" @click="handleAddSubcategory">Add Subcategory</button>
                    </div>

                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th>Parent Category</th>
                                <th>Subcategory Name</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="s in subcategories" :key="s.id">
                                <td>{{ categories.find(c => c.id === s.category_id)?.name }}</td>
                                <td>{{ s.name }}</td>
                                <td style="text-align: right;">
                                    <button class="action-btn" @click="handleDeleteSubcategory(s.id)" style="color: var(--danger); border-color: var(--danger-light); background: var(--danger-light);">Remove</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
`;
content = content.replace(
  '</div>\n            </div>\n        </main>',
  '</div>\n' + subcatsSection + '            </div>\n        </main>'
);

const scripts = `
const newBrandName = ref('')
const newSubcategoryCatId = ref('')
const newSubcategoryName = ref('')

const handleAddBrand = async () => {
    if (!newBrandName.value) return
    await store.addBrand(newBrandName.value)
    newBrandName.value = ''
}
const handleDeleteBrand = async (id) => {
    if (confirm('Delete this brand?')) {
        await store.removeBrand(id)
    }
}

const handleAddSubcategory = async () => {
    if (!newSubcategoryName.value || !newSubcategoryCatId.value) return
    await store.addSubcategory({ name: newSubcategoryName.value, category_id: newSubcategoryCatId.value })
    newSubcategoryName.value = ''
    newSubcategoryCatId.value = ''
}
const handleDeleteSubcategory = async (id) => {
    if (confirm('Delete this subcategory?')) {
        await store.removeSubcategory(id)
    }
}
`;

content = content.replace(
  'const newCategoryName = ref(\'\')',
  scripts.trim() + '\nconst newCategoryName = ref(\'\')'
);

fs.writeFileSync(filePath, content, 'utf8');
console.log('ProductView.vue management forms updated successfully!');
