const fs = require('fs');
let vue = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');

let fix = `        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .app {
            --blue: #2563eb;
            --blue-light: #eef4ff;
            --text: #172033;`;

vue = vue.replace(/        \* \{\s*margin: 0;\s*padding: 0;\s*box-sizing: border-box;\s*\}\s*--text: #172033;/, fix);
fs.writeFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', vue, 'utf8');
console.log("Fixed CSS block");
