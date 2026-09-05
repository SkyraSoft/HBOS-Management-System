const fs = require('fs');
const txt = fs.readFileSync('c:/xampp/htdocs/HBOS/legacy_html/Khata.html', 'utf8');
const lines = txt.split('\n');
lines.forEach((l, i) => {
    if (l.includes('.khata-page') || l.includes('#khataPage') || l.includes('payment-page')) {
        console.log(i + ': ' + l.trim());
    }
});
