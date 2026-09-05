const fs = require('fs');
const file = 'c:/xampp/htdocs/HBOS/legacy_html/Product.html';
let txt = fs.readFileSync(file, 'utf8');

txt = txt.replace(
    /\.app\s*\{\s*min-height:\s*100vh;\s*display:\s*flex;\s*\}/g,
    '.app {\n            width: 100%;\n            min-height: 100vh;\n            display: flex;\n            flex-direction: row;\n        }'
);

txt = txt.replace(
    /\.main\s*\{\s*margin-left:\s*var\(--sidebar-width\);\s*min-height:\s*100vh;\s*width:\s*calc\(100%\s*-\s*var\(--sidebar-width\)\);\s*background:\s*var\(--background\);\s*transition:\s*all\s*\.25s\s*ease;\s*\}/g,
    '.main {\n            flex: 1;\n            min-width: 0;\n            width: 100%;\n            margin-left: var(--sidebar-width);\n            min-height: 100vh;\n            background: var(--background);\n            transition: all .25s ease;\n        }'
);

txt = txt.replace(
    /\.page\s*\{\s*max-width:\s*1450px;\s*margin:\s*0\s*auto;\s*padding:\s*42px;\s*\}/g,
    '.page {\n            width: 100%;\n            flex: 1;\n            padding: 42px;\n        }'
);

fs.writeFileSync(file, txt);
console.log('Layout fixed successfully');
