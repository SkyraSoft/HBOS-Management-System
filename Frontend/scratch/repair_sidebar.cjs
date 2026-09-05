const fs = require('fs');
const file = 'c:/xampp/htdocs/HBOS/src/views/ProductView.vue';
let content = fs.readFileSync(file, 'utf8');

const regex = /<!-- =========================================================\s*SIDEBAR\s*========================================================== -->[\s\S]*?<div class="nav-item">\s*<button class="nav-link-custom" data-page="edit" type="button">/i;

const replacement = `<!-- =========================================================
         SIDEBAR
    ========================================================== -->

        <aside class="sidebar" id="sidebar">

            <div class="brand">
                <div class="brand-name">
                    HBOS <span>RETAIL</span>
                </div>
            </div>

            <div class="sidebar-content">

                <div class="sidebar-title">
                    PRODUCTS
                </div>

                <div class="nav-item">
                    <button class="nav-link-custom active" data-page="products" type="button">
                        <i class="bi bi-box"></i>
                        <span>Products</span>
                    </button>
                </div>

                <div class="nav-item">
                    <button class="nav-link-custom" data-page="categories" type="button">
                        <i class="bi bi-tags"></i>
                        <span>Categories</span>
                    </button>
                </div>

                <div class="nav-item">
                    <button class="nav-link-custom" data-page="add" type="button">
                        <i class="bi bi-plus-square"></i>
                        <span>Add New Product</span>
                    </button>
                </div>

                <div class="nav-item">
                    <button class="nav-link-custom" data-page="details" type="button">
                        <i class="bi bi-card-list"></i>
                        <span>Product Details</span>
                    </button>
                </div>

                <div class="nav-item">
                    <button class="nav-link-custom" data-page="edit" type="button">`;

if (regex.test(content)) {
    content = content.replace(regex, replacement);
    fs.writeFileSync(file, content);
    console.log('Sidebar repaired and Categories added.');
} else {
    console.log('Could not find sidebar pattern to repair.');
}
