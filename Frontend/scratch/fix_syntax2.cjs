const fs = require('fs');
const file = 'c:/xampp/htdocs/HBOS/src/views/ProductView.vue';
let lines = fs.readFileSync(file, 'utf8').split('\n');

for (let i = 0; i < lines.length; i++) {
    if (lines[i].includes('============================================================ */') && !lines[i].includes('/*')) {
        // standalone end comment
        if (lines[i-1].trim() === '' && lines[i+1].trim() === '') {
             lines[i] = '';
        }
    }
}

fs.writeFileSync(file, lines.join('\n'));
