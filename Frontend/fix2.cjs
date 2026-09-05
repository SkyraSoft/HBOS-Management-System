const fs = require('fs');
let txt = fs.readFileSync('c:\\xampp\\htdocs\\HBOS\\src\\assets\\main.css', 'utf8');

// Find the literal string '\n            width: calc' and replace the whole block
const startIndex = txt.indexOf('.main {\\n            width: calc(100% - var(--sidebar-width));\\n            margin-left: var(--sidebar-width);\\n            min-height: 100vh;\\n            display: flex;\\n            flex-direction: column;\\n        }');

if (startIndex !== -1) {
    console.log("Found literal block. Replacing it.");
    txt = txt.replace('.main {\\n            width: calc(100% - var(--sidebar-width));\\n            margin-left: var(--sidebar-width);\\n            min-height: 100vh;\\n            display: flex;\\n            flex-direction: column;\\n        }', 
    `.main {
            width: calc(100% - var(--sidebar-width));
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }`);
    fs.writeFileSync('c:\\xampp\\htdocs\\HBOS\\src\\assets\\main.css', txt);
} else {
    // maybe it has actual newlines? Or slightly different? Let's just find '.main {' and '.main-content {' and replace everything between.
    const start = txt.indexOf('/* =========================================================\n           MAIN\n        ========================================================== */');
    if (start !== -1) {
        const end = txt.indexOf('/* =========================================================\n           TOP BAR\n        ========================================================== */');
        
        const newBlock = `/* =========================================================
           MAIN
        ========================================================== */

        .main {
            width: calc(100% - var(--sidebar-width));
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .main-content {
            max-width: 1080px;

            margin: 0 auto;

            padding: 48px 24px 40px;
        }


        `;
        txt = txt.substring(0, start) + newBlock + txt.substring(end);
        fs.writeFileSync('c:\\xampp\\htdocs\\HBOS\\src\\assets\\main.css', txt);
    }
}
