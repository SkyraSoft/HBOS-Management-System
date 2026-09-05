const fs = require('fs');
const txt = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');
const start = txt.indexOf('class="page products-page"');
const end = txt.indexOf('</section>', start);
console.log(txt.substring(start, end));
