const fs = require('fs');
let pView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', 'utf8');

// Hide pv-edit-sidebar when isModal is true
pView = pView.replace(/<div class="pv-edit-sidebar">/g, '<div class="pv-edit-sidebar" v-if="!isModal">');

// Hide Cancel buttons in ProductView when isModal is true
pView = pView.replace(/<button class="pv-btn-outline" @click="showPage\('products'\)" type="button">Cancel<\/button>/g, '<button class="pv-btn-outline" v-if="!isModal" @click="showPage(\'products\')" type="button">Cancel</button>');
pView = pView.replace(/<button class="pv-btn-outline" @click="showPage\('details', selectedProductId\)" type="button">Cancel<\/button>/g, '<button class="pv-btn-outline" v-if="!isModal" @click="showPage(\'details\', selectedProductId)" type="button">Cancel</button>');

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', pView);
