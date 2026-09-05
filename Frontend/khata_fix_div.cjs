const fs = require('fs');
let khView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', 'utf8');

// The opening div was added, but the closing div was never added.
// Find the </select> that comes after `paymentMethod` and replace it with `</select><i class="bi bi-chevron-down" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #697386; font-size: 0.9rem;"></i></div>`

const pattern = /<\/select>\s*<\/div>\s*<div class="form-group">\s*<label class="form-label" for="paymentAmount">/;
// Wait, the original code had:
// </select>
// 
// </div>
//
// <div class="form-group">
//     <label class="form-label" for="paymentAmount">

khView = khView.replace(/<\/select>\s*<\/div>\s*<div class="form-group">\s*<label class="form-label" for="paymentAmount">/, `</select>
                                    <i class="fa-solid fa-chevron-down" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #697386; font-size: 0.9rem;"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="paymentAmount">`);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', khView);
