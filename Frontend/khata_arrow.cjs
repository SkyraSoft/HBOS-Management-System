const fs = require('fs');
let khView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', 'utf8');

// Replace the Payment Method select with one that has an arrow
const selectPattern = /<select class="form-control" id="paymentMethod" v-model="paymentForm\.method">/g;

khView = khView.replace(selectPattern, `<div style="position: relative;">\n                                    <select class="form-control" id="paymentMethod" v-model="paymentForm.method" style="appearance: none; -webkit-appearance: none; padding-right: 32px;">`);

// Close the div after the select
khView = khView.replace(/<\/select>\s*<\/div>\s*<div class="form-group">/g, `</select>\n                                    <i class="fa-solid fa-chevron-down" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #697386; font-size: 0.9rem;"></i>\n                                </div>\n                            </div>\n\n                            <div class="form-group">`);
// Wait, the structure in the file is:
// </select>
// </div>
// </div>
// <div class="form-group">
// Let's use string replace on a precise block:
const block = `<select class="form-control" id="paymentMethod" v-model="paymentForm.method">

                                    <option>
                                        Cash
                                    </option>

                                    <option>
                                        Card
                                    </option>

                                    <option>
                                        Bank Transfer
                                    </option>

                                    <option>
                                        Cheque
                                    </option>

                                </select>`;

const newBlock = `<div style="position: relative;">
                                    <select class="form-control" id="paymentMethod" v-model="paymentForm.method" style="appearance: none; -webkit-appearance: none; padding-right: 32px;">
                                        <option>Cash</option>
                                        <option>Card</option>
                                        <option>Bank Transfer</option>
                                        <option>Cheque</option>
                                    </select>
                                    <i class="bi bi-chevron-down" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #697386; font-size: 0.9rem;"></i>
                                </div>`;

khView = khView.replace(block, newBlock);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', khView);
