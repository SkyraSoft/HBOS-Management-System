const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'src', 'views', 'reports1View.vue');
let content = fs.readFileSync(filePath, 'utf8');

const cssToAppend = `
/* =========================================================
   OVERRIDE NESTED COMPONENT SIZING (INVENTORY / PRODUCTS)
========================================================= */

/* Hide sidebars when components are embedded as tabs */
:deep(.tab-content-container .sidebar),
:deep(.tab-content-container .pv-sidebar) {
    display: none !important;
}

/* Force main content areas to full width */
:deep(.tab-content-container .main),
:deep(.tab-content-container .pv-main) {
    margin-left: 0 !important;
    width: 100% !important;
    min-width: 100% !important;
    max-width: 100% !important;
}

/* Fix any app wrappers that might be restricting flex layout */
:deep(.tab-content-container .app),
:deep(.tab-content-container .pv-app) {
    display: block !important;
    width: 100% !important;
    min-height: auto !important;
}
`;

// Insert the CSS right before the closing </style> tag
content = content.replace(/<\/style>/, cssToAppend + '\n</style>');

fs.writeFileSync(filePath, content, 'utf8');
console.log('Appended CSS overrides to reports1View.vue');
