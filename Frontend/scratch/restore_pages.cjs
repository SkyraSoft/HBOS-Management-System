const fs = require('fs');
const file = 'c:/xampp/htdocs/HBOS/src/views/ProductView.vue';
let content = fs.readFileSync(file, 'utf8');

const targetStr = `        function showPage(pageName, productId = null) {`;

const insertStr = `        /* ============================================================
           DOM
        ============================================================ */

        const pages = {
            products: document.getElementById("productsPage"),
            categories: document.getElementById("categoriesPage"),
            add: document.getElementById("addPage"),
            details: document.getElementById("detailsPage"),
            edit: document.getElementById("editPage")
        };


        /* ============================================================
           PAGE NAVIGATION
        ============================================================ */

        function showPage(pageName, productId = null) {`;

if (!content.includes('const pages = {')) {
    content = content.replace(targetStr, insertStr);
    fs.writeFileSync(file, content);
    console.log("Restored DOM pages block.");
} else {
    console.log("Pages block exists already.");
}
