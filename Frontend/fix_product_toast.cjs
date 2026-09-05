const fs = require('fs');
let f = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', 'utf8');

// Stop redirect and ID selection
f = f.replace(/showPage\('details', newProduct.id\)/g, '// showPage(\'details\', newProduct.id)');
f = f.replace(/selectedProductId.value = newProduct.id/g, '// selectedProductId.value = newProduct.id');

// Move toast to top
f = f.replace(/bottom: 24px;/g, 'top: 24px;');
f = f.replace(/right: 24px;/g, 'left: 50%;\n    transform: translate(-50%, -20px);\n    opacity: 0;\n    pointer-events: none;');
f = f.replace(/\.pv-toast-show \{ transform: translateY\(0\); opacity: 1; \}/g, '.pv-toast-show { transform: translate(-50%, 0); opacity: 1; pointer-events: auto; }');

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', f);
