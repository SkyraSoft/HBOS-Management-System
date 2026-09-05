const fs = require('fs');
let content = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/layouts/MainLayout.vue', 'utf8');

// The issue is that const isItemActive is defined after watch.
// We can simply change them to `function isItemActive(...)` so they get hoisted!

content = content.replace(/const isItemActive = \(item\) => {/g, 'function isItemActive(item) {');
content = content.replace(/const isChildActive = \(child, item\) => {/g, 'function isChildActive(child, item) {');

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/layouts/MainLayout.vue', content);
console.log('Fixed hoisting issue in MainLayout.vue');
