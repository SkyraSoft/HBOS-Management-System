const fs = require('fs');
let txt = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');

const h2 = txt.indexOf('class="page invoice-page"');
console.log(txt.substring(h2, h2 + 800));
