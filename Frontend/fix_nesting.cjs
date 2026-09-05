const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'src', 'views', 'reports1View.vue');
let content = fs.readFileSync(filePath, 'utf8');

// 1. Insert </div> before Sales tab
content = content.replace(
    /(\s*)<div v-if="activeTab === 'Sales'" class="tab-content-container">/,
    '$1</div>$1<div v-if="activeTab === \'Sales\'" class="tab-content-container">'
);

// 2. Remove the extra </div> from the end (before </section>)
// The end of the file currently looks like:
//               </div>
//           
//           </div>
//           </section>
// Let's find the closing section and remove one </div> right before it.
content = content.replace(/<\/div>\s*<\/section>/, '</section>');

fs.writeFileSync(filePath, content, 'utf8');
console.log('Fixed nesting issue');
