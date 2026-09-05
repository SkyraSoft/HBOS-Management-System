const fs = require('fs');
let khView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', 'utf8');

// Remove New Purchase field
const newPurchasePattern = /<div class="form-group">\s*<label class="form-label" for="newPurchaseAmount">\s*New Purchase \(PKR\)\s*<\/label>[\s\S]*?<\/div>\s*<\/div>\s*<div class="form-group">\s*<label class="form-label" for="returnDebtAmount">/;
khView = khView.replace(newPurchasePattern, '<div class="form-group">\n                              <label class="form-label" for="returnDebtAmount">');

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', khView);
