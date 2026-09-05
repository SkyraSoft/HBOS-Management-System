const fs = require('fs');
const path = require('path');

function searchFiles(dir) {
    const files = fs.readdirSync(dir);
    for (const file of files) {
        const fullPath = path.join(dir, file);
        if (fs.statSync(fullPath).isDirectory()) {
            if (file !== 'node_modules') searchFiles(fullPath);
        } else if (fullPath.endsWith('.vue') || fullPath.endsWith('.js')) {
            const content = fs.readFileSync(fullPath, 'utf8');
            if (content.includes('Inventory') && content.includes('Categories') && content.includes('Add New')) {
                console.log('MATCH FOUND:', fullPath);
            }
        }
    }
}

searchFiles(path.join(__dirname, 'src'));
