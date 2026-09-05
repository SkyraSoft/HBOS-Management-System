const fs = require('fs');
let khView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', 'utf8');

const marker = '<button class="btn btn-black" type="submit" form="paymentForm">';

if (khView.includes(marker) && !khView.includes('openNewPurchase">')) {
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
                              </button>`;
        lines.splice(targetIndex - 1, 0, buttons); // inserting right before the button line
        khView = lines.join('\n');
        
        fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', khView);
    }
}
