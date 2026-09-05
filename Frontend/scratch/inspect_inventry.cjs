const fs = require('fs');
const txt = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');

const panelIdx = txt.indexOf('class="side-panel"');
const invoiceIdx = txt.indexOf('class="page invoice-page"');
const formIdx = txt.indexOf('form-group');
const addBtnIdx = txt.indexOf('Add New Product');

console.log('Side panel:', panelIdx);
console.log('Invoice page:', invoiceIdx);
console.log('Add Button:', txt.substring(Math.max(0, addBtnIdx - 150), addBtnIdx + 100));
