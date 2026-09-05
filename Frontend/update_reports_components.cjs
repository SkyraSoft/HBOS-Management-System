const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'src', 'views', 'reports1View.vue');
let content = fs.readFileSync(filePath, 'utf8');

// 1. Add imports to script setup
const imports = `import { ref, onMounted } from 'vue';
import SalesView from './SalesView.vue';
import PurchaseView from './PurchaseView.vue';
import ReportsView from './ReportsView.vue';
import ExpensesView from './ExpensesView.vue';
import InventryView from './InventryView.vue';
import CustomerView from './CustomerView.vue';
import SupplierView from './SupplierView.vue';
import ProductView from './ProductView.vue';
import ReorderView from './ReorderView.vue';

const activeTab = ref('Overview');`;

content = content.replace(
  `<script setup>\nimport { ref, onMounted } from 'vue';\n\nconst activeTab = ref('Overview');`,
  `<script setup>\n${imports}`
);

// 2. Replace placeholders with actual components
const replacements = {
  'Sales': '<SalesView />',
  'Purchases': '<PurchaseView />',
  'Profit': '<ReportsView />',
  'Expenses': '<ExpensesView />',
  'Inventory': '<InventryView />',
  'Customers': '<CustomerView />',
  'Suppliers': '<SupplierView />',
  'Products': '<ProductView />',
  'Stock Movement': '<ReorderView />'
};

for (const [tab, component] of Object.entries(replacements)) {
    const regex = new RegExp(`<div v-if="activeTab === '${tab}'" class="tab-content-placeholder">[\\s\\S]*?<\\/div>`, 'g');
    content = content.replace(regex, `<div v-if="activeTab === '${tab}'" class="tab-content-container">\n                ${component}\n            </div>`);
}

fs.writeFileSync(filePath, content, 'utf8');
console.log('Tabs updated to use components');
