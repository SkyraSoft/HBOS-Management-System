const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'src', 'views', 'reports1View.vue');
let content = fs.readFileSync(filePath, 'utf8');

// 1. Remove the first errant <div v-if="activeTab === 'Overview'"> before FILTERS
content = content.replace(/<div v-if="activeTab === 'Overview'">\s*<!-- =================================================\s*FILTERS/g, '<!-- =================================================\n                 FILTERS');

// 2. We want the single <div v-if="activeTab === 'Overview'"> to start right AFTER the tabs, so that FILTERS are included in the Overview tab.
// Wait, the tabs end with:
// <div class="report-tab" :class="{ active: activeTab === 'Stock Movement' }" @click="activeTab = 'Stock Movement'">Stock Movement</div>
// </div>

content = content.replace(/(<div class="report-tabs">[\s\S]*?<\/div>)\s*<!-- =================================================\s*FILTERS/g, '$1\n\n            <div v-if="activeTab === \'Overview\'">\n            <!-- =================================================\n                 FILTERS');

// 3. Remove the existing <div v-if="activeTab === 'Overview'"> before KPI CARDS
content = content.replace(/<!-- =================================================\s*KPI CARDS\s*================================================== -->\s*<div v-if="activeTab === 'Overview'">/g, '<!-- =================================================\n                 KPI CARDS\n            ================================================== -->');

// 4. Ensure there is only ONE closing </div> before the <SalesView /> container.
// Right now, there is a closing </div> added by `add_div.cjs` before </section>, and there are `</div>`s closing Overview somewhere.
// Let's check how many <div v-if="activeTab === 'Sales'"> there are.

fs.writeFileSync(filePath, content, 'utf8');
console.log('Fixed v-if structure');
