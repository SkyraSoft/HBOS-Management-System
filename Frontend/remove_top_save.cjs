const fs = require('fs');
let pView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', 'utf8');

// The pattern for the top Save Product button
const topSaveProductPattern = /<div class="pv-header-actions">\s*<button class="pv-btn-outline" v-if="!isModal" @click="showPage\('products'\)" \ntype="button">Cancel<\/button>\s*<button class="pv-btn-primary" @click="saveProduct\(\)" type="button">\s*<i class="bi bi-check2 me-1"><\/i>Save Product\s*<\/button>\s*<\/div>/;

const newTopSaveProduct = `<div class="pv-header-actions">\n                <button class="pv-btn-outline" v-if="!isModal" @click="showPage('products')" type="button">Cancel</button>\n              </div>`;

// Wait, looking at the output, the newline is inside the cancel button tag
const regex2 = /<button class="pv-btn-primary" @click="saveProduct\(\)" type="button">\s*<i class="bi bi-check2 me-1"><\/i>Save Product\s*<\/button>/;

// I only want to remove the FIRST match (the top one).
pView = pView.replace(regex2, '');

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', pView);
