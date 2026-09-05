const fs = require('fs');
let khView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', 'utf8');

// Replace the end of the select block to add the icon and close the relative div.
const pattern = /<\/select>\s*<\/div>\s*<\/div>\s*<div class="form-group">\s*<label class="form-label" for="paymentAmount">/;
khView = khView.replace(/<\/select>/, `</select>\n                                    <i class="fa-solid fa-chevron-down" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #697386; font-size: 0.9rem;"></i>\n                                </div>`);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', khView);
