const fs = require('fs');
let inventory = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/InventryView.vue', 'utf8');
let productView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', 'utf8');

let startIndex = productView.indexOf('<!-- ====================================================\r\n             CATEGORIES PAGE');
if (startIndex === -1) startIndex = productView.indexOf('<!-- ====================================================\n             CATEGORIES PAGE');
let endIndex = productView.indexOf('</section>', startIndex);

let invStart = inventory.indexOf('<section class="page simple-page" id="categoriesPage" v-if="activeTab === \'categories\'">');
let invEnd = inventory.indexOf('</section>', invStart);

let extracted = productView.substring(startIndex, endIndex + 10);
extracted = extracted.replace("v-if=\"currentPage === 'categories'\"", "v-if=\"activeTab === 'categories'\"");
extracted = extracted.replace("class=\"pv-section\" style=\"padding-top: 20px;\"", "class=\"page\"");

let newInventory = inventory.substring(0, invStart) + extracted + inventory.substring(invEnd + 10);
fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/InventryView.vue', newInventory);
console.log('Success');
