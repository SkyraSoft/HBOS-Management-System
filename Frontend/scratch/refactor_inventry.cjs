const fs = require('fs');
let txt = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');

// 1. State changes
txt = txt.replace('const isPanelOpen = ref(false)', '');
txt = txt.replace(
    'const openProductPanel = () => { isPanelOpen.value = true }',
    "const openProductPanel = () => { activeTab.value = 'add_product' }"
);
txt = txt.replace(
    'const closeProductPanel = () => { isPanelOpen.value = false }',
    "const closeProductPanel = () => { activeTab.value = 'products' }"
);

// We had openProductPanelFromInvoice:
txt = txt.replace(
    'const openProductPanelFromInvoice = () => {\n    isPanelOpen.value = true\n}',
    "const openProductPanelFromInvoice = () => {\n    activeTab.value = 'add_product'\n}"
);

// 2. Remove the overlay entirely
txt = txt.replace(/<div class="overlay"[\s\S]*?<\/div>/, '');

// 3. Change the side-panel wrapper to a full-page section
txt = txt.replace(
    /<div class="side-panel"[^>]*id="productPanel">/,
    `<section class="page add-product-page" v-if="activeTab === 'add_product'">
        <div class="page-header">
            <button class="secondary-btn" @click="activeTab = 'products'" style="margin-bottom: 20px; border-color: transparent; padding-left: 0;">
                ← Products
            </button>
        </div>
        <div class="simple-card" style="margin: 0 auto;">`
);

// Now, the side-panel closed with a `</div>` right before `<!-- TOAST -->` or something similar.
// Actually, let's find the closing tag of the side-panel.
// It ends with:
//         <div class="panel-footer">
//             ...
//         </div>
//     </div>
// We need to replace the `</div>` that closes `side-panel` with `</div></section>`.
txt = txt.replace(
    /<\/div>\s*<div class="toast"/,
    `</div>\n    </section>\n\n    <div class="toast"`
);

// Also change the header inside the form so it matches the card styling
txt = txt.replace(
    /<div class="panel-header">/,
    `<div class="panel-header" style="border-bottom: 1px solid var(--border); padding-bottom: 15px; margin-bottom: 20px;">`
);
// Remove the close-btn
txt = txt.replace(/<button class="close-btn" @click="closeProductPanel">×<\/button>/, '');


// 4. Modify Invoice view
// Currently the invoice has <section class="page invoice-page" v-if="activeTab === 'invoice'">
// Let's ensure it has a `← Products` button.
txt = txt.replace(
    /<div class="top-left">[\s\S]*?<div class="breadcrumb">[\s\S]*?<\/div>\s*<\/div>/,
    `<div class="top-left">
        <button class="secondary-btn" @click="activeTab = 'products'" style="margin-bottom: 10px; border-color: transparent; padding-left: 0;">
            ← Products
        </button>
        <div class="breadcrumb">
            <span>Inventory</span> <span class="sep">&gt;</span> <span>Product Invoice</span>
        </div>
    </div>`
);


// 5. Change Save Product flow
// In saveProduct: activeTab.value = 'invoice'; closeProductPanel()
txt = txt.replace(
    /activeTab\.value = 'invoice'\s*closeProductPanel\(\)/,
    "activeTab.value = 'invoice'"
);


fs.writeFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', txt, 'utf8');
console.log("InventryView.vue refactored to full-screen views.");
