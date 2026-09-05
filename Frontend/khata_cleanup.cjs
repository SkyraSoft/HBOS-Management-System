const fs = require('fs');
let khView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', 'utf8');

// Add New Purchase field before Return Debt
const returnDebtPattern = /<div class="form-group">\s*<label class="form-label" for="returnDebtAmount">/g;
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

// Add newPurchaseAmount to state
khView = khView.replace(/returnDebtAmount: 0,/g, `newPurchaseAmount: 0,\n      returnDebtAmount: 0,`);

// REMOVE the buttons I added previously
const buttonsPattern = /<button class="btn" style="background: #eef2fa; color: #0f46c7; font-weight: 600; border: none; margin-bottom: 14px;" type="button" @click="openNewPurchase">\s*New Purchase\s*<\/button>\s*<button class="btn" style="background: #fdf3e1; color: #b7791f; font-weight: 600; border: none; margin-bottom: 14px;" type="button" @click="openReturnDebt">\s*Return Debt\s*<\/button>/g;
khView = khView.replace(buttonsPattern, '');

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', khView);
