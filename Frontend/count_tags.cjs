const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'src', 'views', 'reports1View.vue');
const content = fs.readFileSync(filePath, 'utf8');

const divStarts = (content.match(/<div/g) || []).length;
const divEnds = (content.match(/<\/div>/g) || []).length;

console.log(`div starts: ${divStarts}, div ends: ${divEnds}`);

const sectionStarts = (content.match(/<section/g) || []).length;
const sectionEnds = (content.match(/<\/section>/g) || []).length;

console.log(`section starts: ${sectionStarts}, section ends: ${sectionEnds}`);
