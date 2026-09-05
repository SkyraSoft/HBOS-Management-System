const fs = require('fs');
const html = fs.readFileSync('c:/xampp/htdocs/HBOS/legacy_html/Product.html', 'utf8');

const regex = /<\/?([a-zA-Z0-9]+)[^>]*>/g;
let match;
const stack = [];
const selfClosing = ['meta', 'link', 'img', 'br', 'hr', 'input'];

while ((match = regex.exec(html)) !== null) {
    const fullTag = match[0];
    const tagName = match[1].toLowerCase();
    
    if (fullTag.startsWith('</')) {
        if (stack.length === 0) {
            console.log(`Error: Extra closing tag </${tagName}> at index ${match.index}`);
        } else {
            const last = stack.pop();
            if (last.name !== tagName) {
                console.log(`Error: Mismatched tag at index ${match.index}. Expected </${last.name}> but found </${tagName}>. <${last.name}> opened at index ${last.index}`);
            }
        }
    } else {
        if (!selfClosing.includes(tagName) && !fullTag.endsWith('/>')) {
            stack.push({ name: tagName, index: match.index });
        }
    }
}

if (stack.length > 0) {
    console.log('Error: Unclosed tags remaining:', stack.map(s => s.name).join(', '));
} else {
    console.log('No tag mismatches found!');
}
