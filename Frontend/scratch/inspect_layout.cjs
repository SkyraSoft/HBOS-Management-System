const fs = require('fs');
const txt = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');

const prodStart = txt.indexOf('class="page products-page"');
const prodEnd = txt.indexOf('<div class="stats">', prodStart);

console.log('Products Header Layout:\n', txt.substring(prodStart, prodEnd));

const panelStart = txt.indexOf('class="side-panel"');
console.log('\nSide Panel Layout:\n', txt.substring(panelStart, panelStart + 500));
