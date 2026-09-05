const fs = require('fs');
const content = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/CustomerView.vue', 'utf8');
const lines = content.split('\n');
lines.forEach((line, i) => {
    if (line.includes('id="') && line.toLowerCase().includes('modal')) {
        console.log(`Line ${i+1}: ${line.trim()}`);
    }
});
