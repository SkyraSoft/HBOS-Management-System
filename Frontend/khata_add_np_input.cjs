const fs = require('fs');
let khView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', 'utf8');

// 1. Add New Purchase (PKR) input field before Return Debt (PKR)
const returnDebtPattern = /<div class="form-group">\s*<label class="form-label" for="returnDebtAmount">/;
const newPurchaseHTML = `<div class="form-group">
                              <label class="form-label" for="newPurchaseAmount">
                                  New Purchase (PKR)
                              </label>
                              <div class="amount-input">
                                  <span>
                                      Rs.
                                  </span>
                                  <input class="form-control" type="number" id="newPurchaseAmount" min="0" step="1" placeholder="0.00" v-model.number="paymentForm.newPurchaseAmount">
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="form-label" for="returnDebtAmount">`;
khView = khView.replace(returnDebtPattern, newPurchaseHTML);

// 2. Add `newPurchaseAmount: 0` to state
// Since it might already have `newPurchaseAmount` from my previous run that I removed from HTML but maybe not state?
if (!khView.includes('newPurchaseAmount: 0,')) {
    khView = khView.replace(/returnDebtAmount: 0,/g, `newPurchaseAmount: 0,\n      returnDebtAmount: 0,`);
}

// 3. Remove the New Purchase button
const buttonPattern = /<button class="btn" style="background: #eef2fa; color: #0f46c7; font-weight: 600; border: none; margin-bottom: 14px;" type="button" @click="openNewPurchase">\s*New Purchase\s*<\/button>/g;
khView = khView.replace(buttonPattern, '');

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', khView);
