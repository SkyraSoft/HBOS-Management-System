const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'src', 'views', 'reports1View.vue');
let content = fs.readFileSync(filePath, 'utf8');

// Replace the buggy structure:
//            </div>
//            </div>
//            <div v-if="activeTab === 'Purchases'" class="tab-content-container">
// With:
//            </div>
//            <div v-if="activeTab === 'Purchases'" class="tab-content-container">

// Wait, the structure is:
/*
              </div>
            <div v-if="activeTab === 'Sales'" class="tab-content-container">
                  <SalesView />
              </div>
              </div>
            <div v-if="activeTab === 'Purchases'" class="tab-content-container">
*/

content = content.replace(/<\/div>\s*<\/div>\s*<div v-if="activeTab === /g, '</div>\n            <div v-if="activeTab === ');
// For the last one (Stock Movement), there is also an extra </div> left behind.
content = content.replace(/<ReorderView \/>\s*<\/div>\s*<\/div>/g, '<ReorderView />\n              </div>');

fs.writeFileSync(filePath, content, 'utf8');
console.log('Fixed extra div tags');
