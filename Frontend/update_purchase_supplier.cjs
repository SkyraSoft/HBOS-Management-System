const fs = require('fs');
let file = fs.readFileSync('src/views/PurchaseView.vue', 'utf8');

// 1. Replace HTML select
const selectHtmlStart = file.indexOf('<select class="select" id="supplier" onchange="updateSupplier()">');
const selectHtmlEnd = file.indexOf('</select>', selectHtmlStart) + 9;

const newDropdownHtml = `<div class="searchable-dropdown" style="position: relative;">
                                        <input 
                                            type="text" 
                                            class="select" 
                                            v-model="supplierSearchQuery" 
                                            @focus="showSupplierDropdown = true" 
                                            @blur="hideSupplierDropdown"
                                            placeholder="Select a supplier..."
                                            style="width: 100%;"
                                        >
                                        <div 
                                            v-if="showSupplierDropdown" 
                                            class="dropdown-list" 
                                            style="position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid var(--border); border-radius: 8px; max-height: 200px; overflow-y: auto; z-index: 10; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-top: 5px;"
                                        >
                                            <div 
                                                v-for="s in filteredSuppliers" 
                                                :key="s.id" 
                                                @mousedown="selectSupplier(s)"
                                                style="padding: 10px 15px; cursor: pointer; border-bottom: 1px solid var(--border-light);"
                                                onmouseover="this.style.background='#f0f3fa'"
                                                onmouseout="this.style.background='transparent'"
                                            >
                                                {{ s.name }}
                                            </div>
                                            <div v-if="filteredSuppliers.length === 0" style="padding: 10px 15px; color: var(--muted);">
                                                No suppliers found
                                            </div>
                                        </div>
                                    </div>`;

file = file.substring(0, selectHtmlStart) + newDropdownHtml + file.substring(selectHtmlEnd);


// 2. Inject Imports & Variables & Logic
const importAnchor = `import { usePurchasesStore } from '@/stores/purchases';`;
const newImportsAndLogic = `import { usePurchasesStore } from '@/stores/purchases';
import { useSuppliersStore } from '@/stores/suppliers';

const suppliersStore = useSuppliersStore();
const suppliers = computed(() => suppliersStore.suppliers);

const supplierSearchQuery = ref('');
const selectedSupplierId = ref(null);
const showSupplierDropdown = ref(false);

const filteredSuppliers = computed(() => {
    if (!supplierSearchQuery.value) return suppliers.value;
    return suppliers.value.filter(s => 
        s.name.toLowerCase().includes(supplierSearchQuery.value.toLowerCase())
    );
});

const selectSupplier = (supplier) => {
    selectedSupplierId.value = supplier.id;
    supplierSearchQuery.value = supplier.name;
    showSupplierDropdown.value = false;
};

const hideSupplierDropdown = () => {
    setTimeout(() => {
        showSupplierDropdown.value = false;
        const match = suppliers.value.find(s => s.id === selectedSupplierId.value);
        if (match) {
            supplierSearchQuery.value = match.name;
        } else {
            supplierSearchQuery.value = '';
            selectedSupplierId.value = null;
        }
    }, 200);
};
`;

file = file.replace(importAnchor, newImportsAndLogic);


// 3. Inject supplier_id into purchaseData
const purchaseDataAnchor = `const purchaseData = {
            po_number: poNumber,`;
const newPurchaseData = `const purchaseData = {
            supplier_id: selectedSupplierId.value,
            po_number: poNumber,`;

file = file.replace(purchaseDataAnchor, newPurchaseData);


// 4. Inject fetchSuppliers into onMounted
const onMountedAnchor = `await store.fetchPurchases();`;
const newOnMounted = `await store.fetchPurchases();
    await suppliersStore.fetchSuppliers();`;

file = file.replace(onMountedAnchor, newOnMounted);


fs.writeFileSync('src/views/PurchaseView.vue', file);
console.log('PurchaseView dropdown updated!');
