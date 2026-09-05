const fs = require('fs');
let content = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', 'utf8');

// Insert computed properties
const insertText = `
const products = computed(() => store.products || [])
const categories = computed(() => store.categories || [])
const subcategories = computed(() => store.subcategories || [])
const brands = computed(() => store.brands || [])
`;

// Insert them after const store = useRetailStore()
let storeIndex = content.indexOf('const store = useRetailStore()');
if (storeIndex !== -1) {
    let afterStoreIndex = content.indexOf('\n', storeIndex) + 1;
    if (content.indexOf('const products = computed') === -1) { // Avoid duplicate insertion
        content = content.substring(0, afterStoreIndex) + insertText + content.substring(afterStoreIndex);
    }
}

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', content);
console.log('Added computed properties.');
