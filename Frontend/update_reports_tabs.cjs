const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'src', 'views', 'reports1View.vue');
let content = fs.readFileSync(filePath, 'utf8');

// 1. Add activeTab to script setup
content = content.replace(
  '<script setup>\n  import { onMounted } from \'vue\';',
  `<script setup>
import { ref, onMounted } from 'vue';

const activeTab = ref('Overview');`
);

// 2. Replace the tab links
const oldTabs = /<div class="report-tabs">[\s\S]*?<\/div>\s*<!--/g;
const newTabs = `<div class="report-tabs">
                <div class="report-tab" :class="{ active: activeTab === 'Overview' }" @click="activeTab = 'Overview'">Overview</div>
                <div class="report-tab" :class="{ active: activeTab === 'Sales' }" @click="activeTab = 'Sales'">Sales</div>
                <div class="report-tab" :class="{ active: activeTab === 'Purchases' }" @click="activeTab = 'Purchases'">Purchases</div>
                <div class="report-tab" :class="{ active: activeTab === 'Profit' }" @click="activeTab = 'Profit'">Profit</div>
                <div class="report-tab" :class="{ active: activeTab === 'Expenses' }" @click="activeTab = 'Expenses'">Expenses</div>
                <div class="report-tab" :class="{ active: activeTab === 'Inventory' }" @click="activeTab = 'Inventory'">Inventory</div>
                <div class="report-tab" :class="{ active: activeTab === 'Customers' }" @click="activeTab = 'Customers'">Customers</div>
                <div class="report-tab" :class="{ active: activeTab === 'Suppliers' }" @click="activeTab = 'Suppliers'">Suppliers</div>
                <div class="report-tab" :class="{ active: activeTab === 'Products' }" @click="activeTab = 'Products'">Products</div>
                <div class="report-tab" :class="{ active: activeTab === 'Stock Movement' }" @click="activeTab = 'Stock Movement'">Stock Movement</div>
            </div>
            
            <!--`;
content = content.replace(oldTabs, newTabs);

// 3. Wrap existing content in <div v-if="activeTab === 'Overview'">
const contentStart = /<!-- =================================================\s*KPI CARDS\s*================================================== -->/;

const parts = content.split(contentStart);

if (parts.length === 2) {
    let beforeContent = parts[0];
    let afterContent = parts[1];

    // Find the end of the content section (before </section>)
    let innerParts = afterContent.split('</section>');
    
    if (innerParts.length >= 2) {
        let overviewContent = innerParts[0];
        
        const otherTabsContent = `
            </div>
            <div v-if="activeTab === 'Sales'" class="tab-content-placeholder">
                <div class="page-title" style="margin-top: 20px;">
                    <h2>Sales Data</h2>
                    <p>Detailed sales reports, charts, and tables will appear here.</p>
                </div>
            </div>
            <div v-if="activeTab === 'Purchases'" class="tab-content-placeholder">
                <div class="page-title" style="margin-top: 20px;">
                    <h2>Purchases Data</h2>
                    <p>Detailed purchases reports, charts, and tables will appear here.</p>
                </div>
            </div>
            <div v-if="activeTab === 'Profit'" class="tab-content-placeholder">
                <div class="page-title" style="margin-top: 20px;">
                    <h2>Profit Analysis</h2>
                    <p>Detailed profit margins, charts, and tables will appear here.</p>
                </div>
            </div>
            <div v-if="activeTab === 'Expenses'" class="tab-content-placeholder">
                <div class="page-title" style="margin-top: 20px;">
                    <h2>Expenses Data</h2>
                    <p>Detailed expense reports, charts, and tables will appear here.</p>
                </div>
            </div>
            <div v-if="activeTab === 'Inventory'" class="tab-content-placeholder">
                <div class="page-title" style="margin-top: 20px;">
                    <h2>Inventory Status</h2>
                    <p>Detailed inventory reports, charts, and tables will appear here.</p>
                </div>
            </div>
            <div v-if="activeTab === 'Customers'" class="tab-content-placeholder">
                <div class="page-title" style="margin-top: 20px;">
                    <h2>Customers Insights</h2>
                    <p>Detailed customer reports, charts, and tables will appear here.</p>
                </div>
            </div>
            <div v-if="activeTab === 'Suppliers'" class="tab-content-placeholder">
                <div class="page-title" style="margin-top: 20px;">
                    <h2>Suppliers Overview</h2>
                    <p>Detailed supplier reports, charts, and tables will appear here.</p>
                </div>
            </div>
            <div v-if="activeTab === 'Products'" class="tab-content-placeholder">
                <div class="page-title" style="margin-top: 20px;">
                    <h2>Products Performance</h2>
                    <p>Detailed product reports, charts, and tables will appear here.</p>
                </div>
            </div>
            <div v-if="activeTab === 'Stock Movement'" class="tab-content-placeholder">
                <div class="page-title" style="margin-top: 20px;">
                    <h2>Stock Movement</h2>
                    <p>Detailed stock movement reports, charts, and tables will appear here.</p>
                </div>
            </div>
        `;
        
        let newAfterContent = 
            `<!-- =================================================\n                 KPI CARDS\n            ================================================== -->\n` + 
            `            <div v-if="activeTab === 'Overview'">\n` + 
            overviewContent + 
            otherTabsContent +
            `\n        </section>` + 
            innerParts.slice(1).join('</section>');
            
        content = beforeContent + newAfterContent;
    }
}

fs.writeFileSync(filePath, content, 'utf8');
console.log('Done');
