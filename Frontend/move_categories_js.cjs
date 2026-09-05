const fs = require('fs');
const path = require('path');

const inventoryPath = path.join(__dirname, 'src', 'views', 'InventryView.vue');
const productPath = path.join(__dirname, 'src', 'views', 'ProductView.vue');

let inventoryContent = fs.readFileSync(inventoryPath, 'utf8');
let productContent = fs.readFileSync(productPath, 'utf8');

// The JS logic to inject into ProductView.vue
const jsLogic = `
const categoryViewMode = ref('list')
const newCat = ref({
    name: '',
    code: '',
    parentId: '',
    subcategories: '',
    description: '',
    status: true,
    image: null
})

const handleSaveAdvancedCategory = async () => {
    if (!newCat.value.name.trim()) {
        toastMessage.value = 'Category Name is required.'
        toastVisible.value = true
        setTimeout(() => toastVisible.value = false, 3000)
        return
    }
    
    try {
        if (!newCat.value.parentId) {
            // Main Category
            await store.addCategory(newCat.value.name.trim())
            // Need to get ID to add subcategories if provided
            const created = store.categories.find(c => c.name === newCat.value.name.trim())
            if (created && newCat.value.subcategories) {
                const subs = newCat.value.subcategories.split(',').map(s => s.trim()).filter(Boolean)
                for (const sub of subs) {
                    await store.addSubcategory({ name: sub, category_id: created.id })
                }
            }
        } else {
            // Sub-category
            await store.addSubcategory({ name: newCat.value.name.trim(), category_id: newCat.value.parentId })
        }
        
        toastMessage.value = 'Category saved successfully!'
        toastVisible.value = true
        setTimeout(() => toastVisible.value = false, 3000)
        
        categoryViewMode.value = 'list'
        newCat.value = { name: '', code: '', parentId: '', subcategories: '', description: '', status: true, image: null }
    } catch (e) {
        console.error(e)
        toastMessage.value = 'Error saving category.'
        toastVisible.value = true
        setTimeout(() => toastVisible.value = false, 3000)
    }
}
`;

// Also need to inject editingCategoryId, editCategoryName, saveEditCategory, cancelEditCategory, startEditCategory
const editingLogic = `
const editingCategoryId = ref(null)
const editCategoryName = ref('')

const startEditCategory = (cat) => {
    editingCategoryId.value = cat.id
    editCategoryName.value = cat.name
}
const cancelEditCategory = () => {
    editingCategoryId.value = null
    editCategoryName.value = ''
}
const saveEditCategory = async () => {
    if (!editCategoryName.value.trim() || !editingCategoryId.value) return
    try {
        await store.updateCategory(editingCategoryId.value, editCategoryName.value.trim())
        editingCategoryId.value = null
        editCategoryName.value = ''
        toastMessage.value = 'Category updated!'
        toastVisible.value = true
        setTimeout(() => toastVisible.value = false, 3000)
    } catch(e) {
        console.error(e)
    }
}
`;

// Inject into ProductView.vue before "const newCategoryName = ref('');"
productContent = productContent.replace(
    /const newCategoryName = ref\(''\);/,
    jsLogic + '\n' + editingLogic + '\nconst newCategoryName = ref(\'\');'
);

fs.writeFileSync(productPath, productContent, 'utf8');
console.log('Categories JS migrated to ProductView.vue');
