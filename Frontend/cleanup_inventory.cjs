const fs = require('fs');
const path = require('path');

const inventoryPath = path.join(__dirname, 'src', 'views', 'InventryView.vue');
let inventoryContent = fs.readFileSync(inventoryPath, 'utf8');

// Replace the categoriesPage in InventryView with a message directing them to ProductView
inventoryContent = inventoryContent.replace(
    /<section class="page simple-page" id="categoriesPage" v-if="activeTab === 'categories'">[\s\S]*?<\/section>/,
    `<section class="page simple-page" id="categoriesPage" v-if="activeTab === 'categories'">
            <div class="page-header">
                <div>
                    <button class="secondary-btn" @click="activeTab = 'products'" style="margin-bottom: 20px; border-color: transparent; padding-left: 0;">
                        &larr; Products
                    </button>
                    <h2>Categories Moved</h2>
                    <p>Categories management has been moved to the Product module.</p>
                </div>
            </div>
            <div class="simple-card" style="text-align: center; padding: 40px;">
                <i class="bi bi-info-circle" style="font-size: 3rem; color: var(--text-light); margin-bottom: 20px; display: block;"></i>
                <h3 style="margin-bottom: 15px;">Categories Management is now under Products</h3>
                <p style="color: var(--text-light); max-width: 400px; margin: 0 auto;">
                    Please go to <strong>Products &rarr; Categories</strong> in the main navigation to manage Parent Categories and Sub-categories.
                </p>
            </div>
        </section>`
);

fs.writeFileSync(inventoryPath, inventoryContent, 'utf8');
console.log('InventryView.vue categories replaced with redirect message');
