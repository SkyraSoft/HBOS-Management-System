const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'src', 'stores', 'retail.js');
let content = fs.readFileSync(filePath, 'utf8');

content = content.replace(
    /updateCategory\n\s*}\n}\)/,
    `updateCategory,\n    fetchSubcategories,\n    fetchBrands\n  }\n})`
);

fs.writeFileSync(filePath, content, 'utf8');
console.log('Fixed retail.js exports');
