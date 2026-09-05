const fs = require('fs');
let khView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', 'utf8');

const actionPattern2 = /<button class="btn btn-black" type="submit" form="paymentForm">/;
const newActionButtons = `<button class="btn" style="background: #eef2fa; color: #0f46c7; font-weight: 600; border: none; margin-bottom: 14px;" type="button" @click="openNewPurchase">
                                  New Purchase
                              </button>
                              <button class="btn" style="background: #fdf3e1; color: #b7791f; font-weight: 600; border: none; margin-bottom: 14px;" type="button" @click="openReturnDebt">
                                  Return Debt
                              </button>
                              <button class="btn btn-black" type="submit" form="paymentForm">`;

// Only replace if not already added
if (!khView.includes('openNewPurchase')) {
    khView = khView.replace(actionPattern2, newActionButtons);
    
    // Add router imports and methods if needed
    if (!khView.includes('import { useRouter } from \'vue-router\';')) {
        khView = khView.replace(/const showAddCustomerModal = ref\(false\);/, `import { useRouter } from 'vue-router';\nconst router = useRouter();\nconst openNewPurchase = () => {\n  router.push('/purchases?new=true');\n};\nconst openReturnDebt = () => {\n  router.push('/return-debt');\n};\n\nconst showAddCustomerModal = ref(false);`);
    }
}

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', khView);
