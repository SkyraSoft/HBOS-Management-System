const fs = require('fs');
let khView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', 'utf8');

// Replace the payment-actions block to include the two new buttons
const actionPattern = /<div class="payment-actions">\s*<button class="btn btn-black" type="submit" form="paymentForm">\s*â–£ Record Payment\s*<\/button>\s*<button class="btn" type="button" @click="showKhata">\s*Cancel\s*<\/button>\s*<\/div>/;

const newActions = `<div class="payment-actions" style="display: flex; flex-direction: column; gap: 8px;">
    <button class="btn btn-info text-white" type="button" @click="openNewPurchase">
        New Purchase
    </button>
    <button class="btn btn-warning" type="button" @click="openReturnDebt">
        Return Debt
    </button>
    <button class="btn btn-black" type="submit" form="paymentForm">
        â–£ Record Payment
    </button>
    <button class="btn" type="button" @click="showKhata">
        Cancel
    </button>
</div>`;

khView = khView.replace(actionPattern, newActions);

// Add the methods to script setup
if (!khView.includes('const openNewPurchase = () => {')) {
    khView = khView.replace(/const showAddCustomerModal = ref\(false\);/, `import { useRouter } from 'vue-router';\nconst router = useRouter();\nconst openNewPurchase = () => {\n  router.push('/purchases?new=true');\n};\nconst openReturnDebt = () => {\n  router.push('/return-debt');\n};\n\nconst showAddCustomerModal = ref(false);`);
}

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', khView);
