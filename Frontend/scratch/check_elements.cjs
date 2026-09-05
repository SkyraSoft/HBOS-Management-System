const fs = require('fs');
const txt = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');
console.log('Side panel:', txt.indexOf('class="side-panel"'));
console.log('addProductBtn:', txt.indexOf('id="addProductBtn"'));
