const fs = require('fs');
let content = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', 'utf8');

const target = `const getParentCategory = (categoryName) => {
    const cat = categories.value.find(c => c.name === categoryName)
    return cat ? cat.name : ''
}
const getSubcategory = (categoryName) => {
    const cat = categories.value.find(c => c.name === categoryName)
    if (!cat) return ''
    const sub = subcategories.value.find(s => s.category_id === cat.id)
    return sub ? sub.name : ''
}`;

let newContent = content.split(target).join('');

newContent = newContent.replace('const filteredSubcategories = (categoryName) => {', target + '\n\nconst filteredSubcategories = (categoryName) => {');

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', newContent);
console.log('Fixed duplicates.');
