const fs = require('fs');
const txt = fs.readFileSync('c:/xampp/htdocs/HBOS/legacy_html/Khata.html', 'utf8');

const khataStart = txt.indexOf('id="khataPage"');
const khataEnd = txt.indexOf('</section>', khataStart);
const paymentStart = txt.indexOf('id="paymentPage"');

console.log("Khata Page Start:", khataStart);
console.log("Khata Page End:", khataEnd);
console.log("Payment Page Start:", paymentStart);

if (paymentStart > khataStart && paymentStart < khataEnd) {
    console.log("OH NO! paymentPage is nested inside khataPage!");
} else {
    console.log("paymentPage is OUTSIDE khataPage.");
}
