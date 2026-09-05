import fs from 'fs';
let content = fs.readFileSync('src/views/ProductView.vue', 'utf8');

// Use a simpler regex and replace
const scriptRegex = /<script[\s\S]*?<\/script>/g;
const matches = content.match(scriptRegex);

if (matches) {
    // Only remove script tags that are INSIDE the template.
    // Wait, the file ends with:
    // </template>
    // <script setup>
    // </script>
    // We only want to remove the ones before </template>
    
    let templateEnd = content.indexOf('</template>');
    let beforeTemplate = content.substring(0, templateEnd);
    let afterTemplate = content.substring(templateEnd);
    
    const innerMatches = beforeTemplate.match(scriptRegex);
    if(innerMatches) {
        beforeTemplate = beforeTemplate.replace(scriptRegex, '');
        fs.writeFileSync('src/views/ProductView.vue', beforeTemplate + afterTemplate);
        console.log('Removed ' + innerMatches.length + ' script tags from inside template.');
    } else {
        console.log('No script tags found inside template.');
    }
} else {
    console.log('No script tags found at all.');
}
