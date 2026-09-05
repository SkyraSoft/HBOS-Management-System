const fs = require('fs');
const file = 'c:/xampp/htdocs/HBOS/legacy_html/Product.html';
const txt = fs.readFileSync(file, 'utf8');

const patterns = [
    '.product-table',
    '.product-name',
    '.product-sku',
    '.badge-status',
    '.badge-active',
    '.badge-low',
    '.badge-out',
    '.row-actions',
    '.icon-button',
    '.toolbar',
    '.search-box'
];

patterns.forEach(p => {
    let idx = txt.indexOf(p);
    while (idx !== -1) {
        if (txt.lastIndexOf('}', idx) > txt.lastIndexOf('</style>', idx)) break;
        const end = txt.indexOf('}', idx);
        if (end !== -1 && idx < txt.indexOf('</style>')) {
            console.log(txt.substring(idx, end + 1));
        }
        idx = txt.indexOf(p, end);
    }
});
