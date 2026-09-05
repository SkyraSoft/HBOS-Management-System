const fs = require('fs');
const txt = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');
const start = txt.indexOf('id="saveProduct"');
console.log(txt.substring(start - 100, start + 100));
