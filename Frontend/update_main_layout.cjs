const fs = require('fs');
let txt = fs.readFileSync('c:\\xampp\\htdocs\\HBOS\\src\\layouts\\MainLayout.vue', 'utf8');

txt = txt.replace(/\['PosSale', 'Product'\]/g, "['PosSale', 'Product', 'Inventry']");

fs.writeFileSync('c:\\xampp\\htdocs\\HBOS\\src\\layouts\\MainLayout.vue', txt);
