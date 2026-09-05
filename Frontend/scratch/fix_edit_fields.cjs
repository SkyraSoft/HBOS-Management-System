const fs = require('fs');
const file = 'c:/xampp/htdocs/HBOS/src/views/ProductView.vue';
let content = fs.readFileSync(file, 'utf8');

content = content.replace(
    'document.getElementById("catName").value = cat.name;',
    'document.getElementById("categoryName").value = cat.name;'
);

content = content.replace(
    `document.getElementById("catDesc").value = cat.description || '';`,
    `document.getElementById("categoryDescription").value = cat.description || '';`
);

fs.writeFileSync(file, content);
console.log("Fixed edit category modal fields!");
