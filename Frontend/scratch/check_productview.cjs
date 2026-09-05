const fs = require('fs');
const txt = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/ProductView.vue', 'utf8');
console.log('Products:', txt.indexOf('class="page products-page"'));
console.log('Add Product Btn:', txt.indexOf('Add Product'));
