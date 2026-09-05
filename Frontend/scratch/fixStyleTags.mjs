import fs from 'fs';
let content = fs.readFileSync('src/views/ProductView.vue', 'utf8');

// Use a simpler regex and split/join
const styleRegex = /<style[\s\S]*?<\/style>/g;
const matches = content.match(styleRegex);

if (matches) {
    let styles = matches.join('\n');
    content = content.replace(styleRegex, '');
    content += '\n' + styles;
    fs.writeFileSync('src/views/ProductView.vue', content);
    console.log('Moved ' + matches.length + ' style tags to the bottom.');
} else {
    console.log('No style tags found inside.');
}
