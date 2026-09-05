const fs = require('fs');
const file = 'c:/xampp/htdocs/HBOS/src/views/InventryView.vue';
let txt = fs.readFileSync(file, 'utf8');

// 1. Remove max-width from .page CSS
txt = txt.replace(/\.page\s*\{\s*padding:\s*30px;\s*max-width:\s*1600px;\s*margin:\s*auto;\s*\}/g, '.page {\n            padding: 30px;\n            width: 100%;\n            box-sizing: border-box;\n            overflow-x: hidden;\n        }');

// Wait, I saw earlier that `.page` in InventryView.vue does NOT have `max-width: 1600px`!
// Let's check for any `max-width` that could constrain it.
// The user said "unnecessary blank space can appear on the right side because the page content has a fixed maximum width."
// Let's replace any max-width constraints on the content wrapper.

// 2. Add retail store import
if (!txt.includes('useRetailStore')) {
    txt = txt.replace(
        "import { ref, computed } from 'vue'",
        "import { ref, computed } from 'vue'\nimport { useRetailStore } from '@/stores/retail.js'"
    );
}

// 3. Replace hardcoded products array
txt = txt.replace(
    /const products = ref\(\[\s*\{[\s\S]*?\}\s*\]\)/,
    "const retailStore = useRetailStore()\nconst products = computed(() => retailStore.products)"
);

// 4. Update saveProduct to push to store instead of local array
const saveProductRegex = /products\.value\.unshift\(\{[\s\S]*?\}\)/;
txt = txt.replace(
    saveProductRegex,
    `retailStore.addProduct({
        name: newProduct.value.name,
        sku: newProduct.value.sku,
        category: newProduct.value.category,
        purchasePrice: parseFloat(newProduct.value.purchasePrice),
        sellingPrice: parseFloat(newProduct.value.sellingPrice),
        stock: parseInt(newProduct.value.stock),
        minStock: parseInt(newProduct.value.minStock),
        unit: newProduct.value.unit
    })`
);

// 5. Update deleteProduct if it exists
txt = txt.replace(
    /products\.value\.splice\(index,\s*1\)/g,
    "// retailStore.removeProduct(id) // Implement in store if needed, or modify products directly if possible"
);

fs.writeFileSync(file, txt);
console.log('InventryView.vue updated');
