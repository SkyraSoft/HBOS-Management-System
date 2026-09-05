const fs = require('fs');

const vueFile = 'c:/xampp/htdocs/HBOS/src/views/CustomerView.vue';
let content = fs.readFileSync(vueFile, 'utf8');

// Ensure z-index is high enough for modals
content = content.replace(
    /el\.style\.display = 'block';\s*el\.classList\.add\('show'\);/,
    `el.style.display = 'block';\n        el.style.zIndex = '1060';\n        el.classList.add('show');`
);

// We need to also ensure zIndex is unset on close, or just leave it.

fs.writeFileSync(vueFile, content);
console.log("Updated openModal to set zIndex to 1060.");
