const fs = require('fs');
let vue = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');

let lines = vue.split('\n');
if (lines[687].includes('</div>')) {
    lines[687] = '';
    fs.writeFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', lines.join('\n'), 'utf8');
    console.log("Removed stray </div>");
} else {
    console.log("Line 688 is not </div>. It is:", lines[687]);
}
