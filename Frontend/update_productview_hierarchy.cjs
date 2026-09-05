const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'src', 'views', 'ProductView.vue');
let content = fs.readFileSync(filePath, 'utf8');

// Update table headers
content = content.replace(
  '<th>Category</th>',
  '<th>Brand</th>\n                            <th>Category</th>\n                            <th>Subcategory</th>'
);

// Update table rows
content = content.replace(
  '<td>{{ product.category || \'N/A\' }}</td>',
  '<td>{{ product.brand || \'N/A\' }}</td>\n                                <td>{{ product.category || \'N/A\' }}</td>\n                                <td>{{ product.subcategory || \'N/A\' }}</td>'
);

// Update forms
// 1. In Add Product form
const categorySelect = `
                            <div class="form-group">
                                <label>Category</label>
                                <select v-model="newProduct.category">
                                    <option value="">Select Category</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.name">{{ cat.name }}</option>
                                </select>
                            </div>
`;
const hierarchySelects = `
                            <div class="form-group">
                                <label>Brand</label>
                                <select v-model="newProduct.brand">
                                    <option value="">Select Brand</option>
                                    <option v-for="b in brands" :key="b.id" :value="b.name">{{ b.name }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Category</label>
                                <select v-model="newProduct.category">
                                    <option value="">Select Category</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.name">{{ cat.name }}</option>
                                </select>
                            </div>
                            <div class="form-group" v-if="newProduct.category">
                                <label>Subcategory</label>
                                <select v-model="newProduct.subcategory">
                                    <option value="">Select Subcategory</option>
                                    <option v-for="sub in filteredSubcategories(newProduct.category)" :key="sub.id" :value="sub.name">{{ sub.name }}</option>
                                </select>
                            </div>
`;
content = content.replace(categorySelect.trim(), hierarchySelects.trim());

// 2. In Edit Product form
const categoryEditSelect = `
                            <div class="form-group">
                                <label>Category</label>
                                <select v-model="editForm.category">
                                    <option value="">Select Category</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.name">{{ cat.name }}</option>
                                </select>
                            </div>
`;
const hierarchyEditSelects = `
                            <div class="form-group">
                                <label>Brand</label>
                                <select v-model="editForm.brand">
                                    <option value="">Select Brand</option>
                                    <option v-for="b in brands" :key="b.id" :value="b.name">{{ b.name }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Category</label>
                                <select v-model="editForm.category">
                                    <option value="">Select Category</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.name">{{ cat.name }}</option>
                                </select>
                            </div>
                            <div class="form-group" v-if="editForm.category">
                                <label>Subcategory</label>
                                <select v-model="editForm.subcategory">
                                    <option value="">Select Subcategory</option>
                                    <option v-for="sub in filteredSubcategories(editForm.category)" :key="sub.id" :value="sub.name">{{ sub.name }}</option>
                                </select>
                            </div>
`;
content = content.replace(categoryEditSelect.trim(), hierarchyEditSelects.trim());

// Update logic
content = content.replace(
  'const categories = computed(() => store.categories)',
  'const categories = computed(() => store.categories)\nconst subcategories = computed(() => store.subcategories)\nconst brands = computed(() => store.brands)'
);

content = content.replace(
  'await store.fetchCategories()',
  'await store.fetchCategories()\n    await store.fetchSubcategories()\n    await store.fetchBrands()'
);

const newProductObj = `const newProduct = ref({
    name: '',
    category: '',
    brand: '',
    subcategory: '',
    sku: '',
    purchasePrice: 0,
    sellingPrice: 0,
    stock: 0,
    unit: 'Piece',
    minStock: 10,
    notes: '',
    active: true,
    imageFile: null
})`;
content = content.replace(/const newProduct = ref\(\{[\s\S]*?imageFile: null\n\}\)/, newProductObj);

const filteredSubcatsLogic = `
const filteredSubcategories = (categoryName) => {
    const cat = categories.value.find(c => c.name === categoryName);
    if (!cat) return [];
    return subcategories.value.filter(s => s.category_id === cat.id);
}
`;
content = content.replace(
  'const newCategoryName = ref(\'\')',
  filteredSubcatsLogic + '\nconst newCategoryName = ref(\'\')'
);

fs.writeFileSync(filePath, content, 'utf8');
console.log('ProductView.vue updated successfully!');
