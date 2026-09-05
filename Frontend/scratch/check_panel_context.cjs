const fs = require('fs');
const txt = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');
const start = txt.indexOf('class="side-panel"');
console.log(txt.substring(start - 200, start + 200));
