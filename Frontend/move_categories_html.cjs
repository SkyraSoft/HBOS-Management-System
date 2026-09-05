const fs = require('fs');
const path = require('path');

const inventoryPath = path.join(__dirname, 'src', 'views', 'InventryView.vue');
const productPath = path.join(__dirname, 'src', 'views', 'ProductView.vue');

let inventoryContent = fs.readFileSync(inventoryPath, 'utf8');
let productContent = fs.readFileSync(productPath, 'utf8');

// Extract the new categories section from InventryView.vue
const categoriesRegex = /<section class="page simple-page" id="categoriesPage" v-if="activeTab === 'categories'">[\s\S]*?<\/section>/;
const categoriesMatch = inventoryContent.match(categoriesRegex);

if (categoriesMatch) {
    let categoriesSection = categoriesMatch[0];

    // Modify the section to fit ProductView.vue
    categoriesSection = categoriesSection.replace(
        /<section class="page simple-page" id="categoriesPage" v-if="activeTab === 'categories'">/,
        '<section v-if="currentPage === \'categories\'" class="pv-section" style="padding-top: 20px;">'
    );
    // Remove the back to products button from list view since ProductView has sidebar
    categoriesSection = categoriesSection.replace(
        /<button class="secondary-btn" @click="activeTab = 'products'" style="margin-bottom: 20px; border-color: transparent; padding-left: 0;">\s*&larr; Products\s*<\/button>/,
        ''
    );
    // Fix breadcrumb in add view to say Product instead of Inventory
    categoriesSection = categoriesSection.replace(
        /<span>Inventory<\/span> <span style="font-size: 10px;">&gt;<\/span> <span style="cursor: pointer;" @click="categoryViewMode = 'list'">Categories<\/span>/,
        '<span>Product</span> <span style="font-size: 10px;">&gt;</span> <span style="cursor: pointer;" @click="categoryViewMode = \'list\'">Categories</span>'
    );

    // Replace ProductView.vue's categories section
    const productCategoriesRegex = /<!-- ====================================================\s*CATEGORIES PAGE\s*===================================================== -->\s*<section v-if="currentPage === 'categories'" class="pv-section">[\s\S]*?<\/section>/;
    
    productContent = productContent.replace(productCategoriesRegex, '<!-- ====================================================\n             CATEGORIES PAGE\n        ===================================================== -->\n        ' + categoriesSection);

    fs.writeFileSync(productPath, productContent, 'utf8');
    console.log('Categories section migrated to ProductView.vue HTML');
} else {
    console.log('Could not find categories section in InventryView.vue');
}
