const fs = require('fs');

const vueFile = 'c:/xampp/htdocs/HBOS/src/views/CustomerView.vue';
let vueContent = fs.readFileSync(vueFile, 'utf8');

// 1. Remove `<div class="app">`, `<aside...>`, `<main...>`, `<header...>` and their closing tags.
// The real content starts at `<section class="page">`.
// The end is `</section>`, followed by modals.

const pageStart = vueContent.indexOf('<section class="page">');
const sectionEnd = vueContent.indexOf('</section>') + 10;
const modalsStart = vueContent.indexOf('<!-- MODALS -->');
const templateEnd = vueContent.indexOf('</template>');

const newTemplate = `
<template>
<div class="customer-view-container" style="width: 100%; height: 100%; overflow: auto;">
` + vueContent.substring(pageStart, sectionEnd) + '\n' + vueContent.substring(modalsStart, templateEnd) + 
`
</div>
</template>
`;

vueContent = vueContent.substring(0, vueContent.indexOf('<template>')) + newTemplate + vueContent.substring(templateEnd + 11);

// We need to fix `.page` to be 100% width if it isn't already.
// Check styles at the end.
if (!vueContent.includes('width: 100%;')) {
    vueContent = vueContent.replace(/\.page\s*\{[\s\S]*?\}/, `.page { padding: 24px 30px 40px; width: 100%; }`);
}

// Ensure the form save works properly
// Actually, earlier I had `let bal = newCustomer.value.openingBalance || 0;` which might not work if it's undefined.

fs.writeFileSync(vueFile, vueContent);
console.log("Removed the double-layout wrappers from CustomerView.vue. It now only contains the page section and modals.");
