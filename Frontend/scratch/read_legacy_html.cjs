const fs = require('fs');
const txt = fs.readFileSync('c:/xampp/htdocs/HBOS/legacy_html/Inventry.html', 'utf8');
const start = txt.indexOf('class="page products-page"');
const end = txt.indexOf('class="page invoice-page"');
console.log(txt.substring(start, start + 1000));
