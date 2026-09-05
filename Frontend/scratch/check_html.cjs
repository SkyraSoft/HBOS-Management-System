const fs = require('fs');
const txt = fs.readFileSync('c:/xampp/htdocs/HBOS/legacy_html/Khata.html', 'utf8');
const khataStart = txt.indexOf('id="khataPage"');
const khataEnd = txt.indexOf('</section>', khataStart);
const paymentStart = txt.indexOf('id="paymentPage"');
console.log(txt.slice(khataEnd, paymentStart));
