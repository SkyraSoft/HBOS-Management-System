const fs = require('fs');
const vue = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');

// Find all section and div tags
const lines = vue.split('\n');
let mainStart = -1;
let mainEnd = -1;

for (let i = 0; i < lines.length; i++) {
    if (lines[i].includes('<main')) {
        if (mainStart === -1) mainStart = i;
    }
    if (lines[i].includes('</main>')) {
        mainEnd = i;
    }
}

let divStack = [];
let sectionStack = [];
let formStack = [];
let errors = [];

for (let i = 0; i < lines.length; i++) {
    let line = lines[i];
    let divsOpen = (line.match(/<div\b[^>]*>/g) || []).length;
    let divsClose = (line.match(/<\/div>/g) || []).length;
    
    let sectionsOpen = (line.match(/<section\b[^>]*>/g) || []).length;
    let sectionsClose = (line.match(/<\/section>/g) || []).length;
    
    let formsOpen = (line.match(/<form\b[^>]*>/g) || []).length;
    let formsClose = (line.match(/<\/form>/g) || []).length;

    for(let k=0; k<divsOpen; k++) divStack.push(i+1);
    for(let k=0; k<divsClose; k++) divStack.pop();

    for(let k=0; k<sectionsOpen; k++) sectionStack.push(i+1);
    for(let k=0; k<sectionsClose; k++) sectionStack.pop();
    
    for(let k=0; k<formsOpen; k++) formStack.push(i+1);
    for(let k=0; k<formsClose; k++) formStack.pop();
    
    if (line.includes('</main>')) {
        console.log(`At </main> line ${i+1}:`);
        console.log('Unclosed divs from lines:', divStack);
        console.log('Unclosed sections from lines:', sectionStack);
        console.log('Unclosed forms from lines:', formStack);
    }
}

console.log('End of file state:');
console.log('Unclosed divs:', divStack);
console.log('Unclosed sections:', sectionStack);
console.log('Unclosed forms:', formStack);
