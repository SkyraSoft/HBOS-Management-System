const fs = require('fs');
const file = 'c:/xampp/htdocs/HBOS/legacy_html/Product.html';
const txt = fs.readFileSync(file, 'utf8');
const idx = txt.indexOf('row.innerHTML = `');
const idxEnd = txt.indexOf('`;', idx);
console.log(txt.substring(idx, idxEnd + 2));
