const fs = require('fs');
const txt = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');

const start = txt.indexOf('class="page invoice-page"');
const end = txt.indexOf('</template>', start);
console.log(txt.substring(start, end));
