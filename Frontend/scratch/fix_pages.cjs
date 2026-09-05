const fs = require('fs');
const file = 'c:/xampp/htdocs/HBOS/src/views/ProductView.vue';
let content = fs.readFileSync(file, 'utf8');

const targetStr = `        /* ============================================================
           PAGE NAVIGATION
        ============================================================ */`;

const fixStr = `        /* ============================================================
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
        ============================================================ */`;

if (content.includes(targetStr)) {
    if (!content.includes('const pages = {')) {
        content = content.replace(targetStr, fixStr);
        fs.writeFileSync(file, content);
        console.log("Fixed pages object.");
    } else {
        console.log("Pages object already exists?");
    }
} else {
    console.log("Could not find PAGE NAVIGATION");
}
