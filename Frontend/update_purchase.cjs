const fs = require('fs');
let file = fs.readFileSync('src/views/PurchaseView.vue', 'utf8');

// Update button
file = file.replace(
`<button class="btn btn-black" onclick="confirmPurchase()">`,
`<button class="btn btn-black" @click="confirmPurchase">`
);

// Add confirmPurchase logic
const scriptAddition = `
import { useRetailStore } from '@/stores/retail';

const confirmPurchase = async () => {
    try {
        const retailStore = useRetailStore();
        await retailStore.fetchProducts();
        
        let productId = 1;
        if (retailStore.products && retailStore.products.length > 0) {
            productId = retailStore.products[0].id;
        }

        const invoiceInput = document.getElementById('invoiceNumber');
        const poNumber = (invoiceInput && invoiceInput.value) ? invoiceInput.value : 'PO-' + Date.now();
        
        const dateInput = document.getElementById('purchaseDate');
        const purchaseDate = (dateInput && dateInput.value) ? dateInput.value : new Date().toISOString().split('T')[0];

        const purchaseData = {
            po_number: poNumber,
            date: purchaseDate,
            subtotal: 4986,
            total: 5278.10,
            status: 'received',
            notes: 'Created via Confirm Purchase',
            items: [
                {
                    product_id: productId,
                    quantity: 50,
                    unit_cost: 45,
                    total: 2250
                },
                {
                    product_id: productId,
                    quantity: 24,
                    unit_cost: 120,
                    total: 2736
                }
            ]
        };

        const result = await store.addPurchase(purchaseData);
        if (result.success) {
            showPurchaseList();
        } else {
            alert('Failed to save purchase: ' + result.message);
        }
    } catch (e) {
        console.error(e);
        alert('An error occurred');
    }
};
`;

file = file.replace(
`import { useRouter, useRoute } from 'vue-router';
import { usePurchasesStore } from '@/stores/purchases';`,
`import { useRouter, useRoute } from 'vue-router';
import { usePurchasesStore } from '@/stores/purchases';
` + scriptAddition
);

fs.writeFileSync('src/views/PurchaseView.vue', file);
console.log('PurchaseView updated!');
