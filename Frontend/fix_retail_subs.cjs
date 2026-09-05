const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'src', 'stores', 'retail.js');
let content = fs.readFileSync(filePath, 'utf8');

content = content.replace(
    /fetchSubcategories,\n\s*fetchBrands\n\s*}\n}\)/,
    `fetchSubcategories,\n    fetchBrands,\n    addSubcategory,\n    updateSubcategory,\n    removeSubcategory\n  }\n})`
);

fs.writeFileSync(filePath, content, 'utf8');
console.log('Fixed retail.js exports for subcategories');
