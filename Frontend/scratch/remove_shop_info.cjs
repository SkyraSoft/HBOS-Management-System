const fs = require('fs');
const file = 'c:/xampp/htdocs/HBOS/src/views/ProductView.vue';
let content = fs.readFileSync(file, 'utf8');

// 1. Remove shopInfoForm HTML
const formStart = content.indexOf('<form id="shopInfoForm" class="form-layout mb-5">');
if (formStart !== -1) {
    const formEnd = content.indexOf('</form>', formStart) + 7;
    content = content.substring(0, formStart) + content.substring(formEnd);
}

// 2. Change the page title/subtitle back to just categories
content = content.replace(
    '<h1 class="page-title">Shop & Categories Setup</h1>',
    '<h1 class="page-title">Categories</h1>'
);
content = content.replace(
    '<p class="page-subtitle">Manage your shop information and product categories.</p>',
    '<p class="page-subtitle">Manage your product categories.</p>'
);

// 3. Remove saveShopInfo JS
const jsStart = content.indexOf('window.saveShopInfo = function() {');
if (jsStart !== -1) {
    const jsEnd = content.indexOf('};', jsStart) + 2;
    content = content.substring(0, jsStart) + content.substring(jsEnd);
}

// 4. Remove shopInfo state
const stateStart = content.indexOf("let shopInfo = { name: '', owner: '', phone: '', email: '', address: '', city: '', type: 'Retail' };");
if (stateStart !== -1) {
    const stateEnd = stateStart + "let shopInfo = { name: '', owner: '', phone: '', email: '', address: '', city: '', type: 'Retail' };".length;
    content = content.substring(0, stateStart) + content.substring(stateEnd);
}

fs.writeFileSync(file, content);
console.log("Removed Shop Information section successfully!");
