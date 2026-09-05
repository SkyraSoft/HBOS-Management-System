const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'src', 'views', 'reports1View.vue');
const content = fs.readFileSync(filePath, 'utf8');

const lines = content.split('\n');
let count = 0;
let lastUnmatched = 0;

for (let i = 0; i < lines.length; i++) {
    const startMatches = (lines[i].match(/<div(?=[\s>])/g) || []).length;
    const endMatches = (lines[i].match(/<\/div>/g) || []).length;
    count += startMatches;
    count -= endMatches;
    if (startMatches > endMatches) {
        lastUnmatched = i + 1;
    }
}

console.log(`Final count: ${count}, Last unmatched line (approx): ${lastUnmatched}`);
