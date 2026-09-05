const fs = require('fs');
let layout = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/layouts/MainLayout.vue', 'utf8');

// Decrease the gap on the nav item div that wraps icon and text
layout = layout.replace(/style="display: flex; align-items: center; gap: 12px;"/g, 'style="display: flex; align-items: center; gap: 8px;"');
// And the margin-right
layout = layout.replace(/style="margin-right: 12px;"/g, 'style="margin-right: 8px;"');

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/layouts/MainLayout.vue', layout);
