const fs = require('fs');
let khView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', 'utf8');

// Insert Return Debt field before the Reference/Note block
const notePattern = /<div class="form-group">\s*<label class="form-label" for="paymentNote">/g;
const returnDebtHTML = `<div class="form-group">
                              <label class="form-label" for="returnDebtAmount">
                                  Return Debt (PKR)
                              </label>
                              <div class="amount-input">
                                  <span>
                                      Rs.
                                  </span>
                                  <input class="form-control" type="number" id="returnDebtAmount" min="0" step="1" placeholder="0.00" v-model.number="paymentForm.returnDebtAmount">
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="form-label" for="paymentNote">`;
khView = khView.replace(notePattern, returnDebtHTML);

// Update paymentForm default object to include returnDebtAmount
// We know it has `method: 'Cash',`
khView = khView.replace(/method: 'Cash',/g, `method: 'Cash',\n      returnDebtAmount: 0,`);

// Also there's one in the reset logic inside submitPayment
// It's the same so the global /g should replace both occurrences.

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', khView);
