const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'src', 'views', 'InventryView.vue');
let content = fs.readFileSync(filePath, 'utf8');

// Update filters
const categoryFilter = `
                            <select v-model="categoryFilter" class="filter-select">
                                <option value="">All Categories</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.name">{{ cat.name }}</option>
                            </select>
`;
const newFilters = `
                            <select v-model="brandFilter" class="filter-select">
                                <option value="">All Brands</option>
                                <option v-for="b in brands" :key="b.id" :value="b.name">{{ b.name }}</option>
                            </select>
                            <select v-model="categoryFilter" class="filter-select">
                                <option value="">All Categories</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.name">{{ cat.name }}</option>
                            </select>
                            <select v-model="subcategoryFilter" class="filter-select" v-if="categoryFilter">
                                <option value="">All Subcategories</option>
                                <option v-for="sub in filteredSubcategoriesList" :key="sub.id" :value="sub.name">{{ sub.name }}</option>
                            </select>
`;
content = content.replace(categoryFilter.trim(), newFilters.trim());


// Update Add Product form
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
                                    <option v-for="sub in filteredSubcategoriesForm" :key="sub.id" :value="sub.name">{{ sub.name }}</option>
                                </select>
                            </div>
`;
content = content.replace(categorySelect.trim(), hierarchySelects.trim());

// Update logic
content = content.replace(
  'const categories = computed(() => retailStore.categories)',
  'const categories = computed(() => retailStore.categories)\nconst subcategories = computed(() => retailStore.subcategories)\nconst brands = computed(() => retailStore.brands)'
);

content = content.replace(
  'await retailStore.fetchCategories()',
  'await retailStore.fetchCategories()\n    await retailStore.fetchSubcategories()\n    await retailStore.fetchBrands()'
);

content = content.replace(
  'const categoryFilter = ref(\'\')',
  'const categoryFilter = ref(\'\')\nconst subcategoryFilter = ref(\'\')\nconst brandFilter = ref(\'\')'
);

const newProductObj = `const newProduct = ref({
    name: '',
    sku: '',
    brand: '',
    category: '',
    subcategory: '',
    purchasePrice: 0,
    sellingPrice: 0,
    stock: 0,
    unit: 'Piece',
    minStock: 10,
    notes: ''
})`;
content = content.replace(/const newProduct = ref\(\{[\s\S]*?notes: ''\n\}\)/, newProductObj);

const resetProductObj = `newProduct.value = { name: '', sku: '', brand: '', category: '', subcategory: '', purchasePrice: 0, sellingPrice: 0, stock: 0, unit: 'Piece', minStock: 10, notes: '' }`;
content = content.replace(/newProduct\.value = \{ name: '', sku: '', category: '', purchasePrice: 0[\s\S]*?notes: '' \}/, resetProductObj);

const filterLogic = `
const filteredSubcategoriesList = computed(() => {
    const cat = categories.value.find(c => c.name === categoryFilter.value);
    if (!cat) return [];
    return subcategories.value.filter(s => s.category_id === cat.id);
});

const filteredSubcategoriesForm = computed(() => {
    const cat = categories.value.find(c => c.name === newProduct.value.category);
    if (!cat) return [];
    return subcategories.value.filter(s => s.category_id === cat.id);
});
`;
content = content.replace(
  'const filteredProducts = computed(() => {',
  filterLogic + '\nconst filteredProducts = computed(() => {'
);

const filterCondition = `
        let match = true
        if (searchQuery.value) {
            match = match && p.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || p.sku.toLowerCase().includes(searchQuery.value.toLowerCase())
        }
        if (categoryFilter.value) {
            match = match && p.category === categoryFilter.value
        }
        if (brandFilter.value) {
            match = match && p.brand === brandFilter.value
        }
        if (subcategoryFilter.value) {
            match = match && p.subcategory === subcategoryFilter.value
        }
        return match
`;
content = content.replace(/let match = true[\s\S]*?match = match && p.category === categoryFilter\.value\n        \}\n        return match/, filterCondition.trim());

fs.writeFileSync(filePath, content, 'utf8');
console.log('InventryView.vue updated successfully!');
