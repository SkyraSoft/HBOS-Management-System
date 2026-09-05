const fs = require('fs');
const txt = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');
console.log('Matches:', txt.match(/class="side-panel"/g).length);
