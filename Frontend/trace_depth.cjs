const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'src', 'views', 'reports1View.vue');
const content = fs.readFileSync(filePath, 'utf8');

const lines = content.split('\n');
let depth = 0;

for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const startMatches = (line.match(/<div(?=[\s>])/g) || []).length;
    const endMatches = (line.match(/<\/div>/g) || []).length;
    
    depth += startMatches;
    depth -= endMatches;
    
    if (line.includes('activeTab === \'Sales\'')) {
        console.log(`Depth at Sales tab: ${depth}`);
        break;
    }
}
