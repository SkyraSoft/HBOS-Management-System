const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'src', 'views', 'reports1View.vue');
let content = fs.readFileSync(filePath, 'utf8');

// Insert a </div> before </section>
content = content.replace('</section>', '</div>\n        </section>');

fs.writeFileSync(filePath, content, 'utf8');
console.log('Added missing closing div');
