const fs = require('fs');
let khView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', 'utf8');

// Using an index-based insertion to avoid tricky regex characters
const marker = 'class="btn btn-black" type="submit" form="paymentForm"';

if (khView.includes(marker) && !khView.includes('openNewPurchase')) {
    const lines = khView.split('\n');
    let targetIndex = -1;
    for (let i = 0; i < lines.length; i++) {
        if (lines[i].includes(marker)) {
            targetIndex = i;
            break;
        }
    }
    
    if (targetIndex !== -1) {
        const buttons = `                              <button class="btn" style="background: #eef2fa; color: #0f46c7; font-weight: 600; border: none; margin-bottom: 14px;" type="button" @click="openNewPurchase">
                                  New Purchase
                              </button>
                              <button class="btn" style="background: #fdf3e1; color: #b7791f; font-weight: 600; border: none; margin-bottom: 14px;" type="button" @click="openReturnDebt">
                                  Return Debt
                              </button>`;
        lines.splice(targetIndex, 0, buttons);
        khView = lines.join('\n');
        
        // Now add the methods
        khView = khView.replace(/const showAddCustomerModal = ref\(false\);/, `import { useRouter } from 'vue-router';\nconst router = useRouter();\nconst openNewPurchase = () => {\n  router.push('/purchases?new=true');\n};\nconst openReturnDebt = () => {\n  router.push('/return-debt');\n};\n\nconst showAddCustomerModal = ref(false);`);
        
        fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', khView);
        console.log("Successfully added buttons and methods.");
    }
} else {
    console.log("Either marker not found or already added.");
}
