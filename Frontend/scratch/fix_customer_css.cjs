const fs = require('fs');
const file = 'c:/xampp/htdocs/HBOS/src/views/CustomerView.vue';
let content = fs.readFileSync(file, 'utf8');

// 1. CSS Adjustments
content = content.replace(
    /(\.page\s*\{\s*padding:\s*24px\s+30px\s+40px;\s*)max-width:\s*1500px;(\s*)margin:\s*0\s+auto;/,
    '$1width: 100%;'
);

fs.writeFileSync(file, content);
console.log("Updated Customer page CSS!");
