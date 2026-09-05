const fs = require('fs');
let content = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', 'utf8');

// 1. Update handleSaveAdvancedCategory
let saveStart = content.indexOf('const handleSaveAdvancedCategory = async () => {');
let saveEnd = content.indexOf('const editingCategoryId = ref(null)');
if (saveStart !== -1 && saveEnd !== -1) {
    let newSave = `const handleSaveAdvancedCategory = async () => {
    if (!newCat.value.productName || !newCat.value.productName.trim()) {
        toastMessage.value = 'Product Name is required.'
        toastVisible.value = true
        setTimeout(() => toastVisible.value = false, 3000)
        return
    }
    
    try {
        const productData = {
            name: newCat.value.productName.trim(),
            brand: newCat.value.brand,
            category: newCat.value.category,
            sku: newCat.value.sku,
            purchasePrice: newCat.value.purchasePrice,
            sellingPrice: newCat.value.sellingPrice,
            stock: newCat.value.stock,
            status: newCat.value.status ? 'Active' : 'Inactive',
            image: null
        }
        await store.addProduct(productData)
        
        // Also handle the subcategory if a parent category is selected
        if (newCat.value.parentId && newCat.value.subcategoryId) {
            // It's already an existing subcategory, nothing to add. 
            // If we wanted to link them, we would, but our product API handles it.
        }
        
        toastMessage.value = 'Details saved successfully!'
        toastVisible.value = true
        setTimeout(() => toastVisible.value = false, 3000)
        
        categoryViewMode.value = 'list'
        newCat.value = { productName: '', brand: '', category: '', parentId: '', subcategoryId: '', sku: '', purchasePrice: '', sellingPrice: '', stock: '', status: true }
    } catch (e) {
        console.error(e)
        toastMessage.value = 'Error saving details.'
        toastVisible.value = true
        setTimeout(() => toastVisible.value = false, 3000)
    }
}

`;
    content = content.substring(0, saveStart) + newSave + content.substring(saveEnd);
}

// 2. Add helper functions getParentCategory and getSubcategory
let helpersStart = content.indexOf('const filteredSubcategories = (categoryName) => {');
if (helpersStart !== -1) {
    let newHelpers = `const getParentCategory = (categoryName) => {
    const cat = categories.value.find(c => c.name === categoryName)
    return cat ? cat.name : ''
}
const getSubcategory = (categoryName) => {
    const cat = categories.value.find(c => c.name === categoryName)
    if (!cat) return ''
    const sub = subcategories.value.find(s => s.category_id === cat.id)
    return sub ? sub.name : ''
}

const filteredSubcategories = (categoryName) => {`;
    content = content.substring(0, helpersStart) + newHelpers + content.substring(helpersStart + 'const filteredSubcategories = (categoryName) => {'.length);
}

// 3. Update newCat initial state
let stateStart = content.indexOf(`const newCat = ref({
    name: '',
    code: '',
    parentId: '',
    subcategories: '',
    description: '',
    status: true,
    image: null
})`);
if (stateStart !== -1) {
    let stateEnd = stateStart + `const newCat = ref({
    name: '',
    code: '',
    parentId: '',
    subcategories: '',
    description: '',
    status: true,
    image: null
})`.length;
    let newState = `const newCat = ref({
    productName: '',
    brand: '',
    category: '',
    parentId: '',
    subcategoryId: '',
    sku: '',
    purchasePrice: '',
    sellingPrice: '',
    stock: '',
    status: true
})`;
    content = content.substring(0, stateStart) + newState + content.substring(stateEnd);
} else {
    // try removing spaces
    console.log("Could not find newCat ref to replace.");
}

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', content);
console.log('Script updated successfully.');
