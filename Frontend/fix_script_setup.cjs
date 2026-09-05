const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'src', 'views', 'reports1View.vue');
let content = fs.readFileSync(filePath, 'utf8');

// The original script setup block
const scriptRegex = /<script setup>[\s\S]*?<\/script>/;

const newScript = `<script setup>
import { ref, onMounted } from 'vue';
import SalesView from './SalesView.vue';
import PurchaseView from './PurchaseView.vue';
import ReportsView from './ReportsView.vue';
import ExpensesView from './ExpensesView.vue';
import InventryView from './InventryView.vue';
import CustomerView from './CustomerView.vue';
import SupplierView from './SupplierView.vue';
import ProductView from './ProductView.vue';
import ReorderView from './ReorderView.vue';

const activeTab = ref('Overview');

onMounted(() => {
  console.log('reports1View mounted');
});
</script>`;

content = content.replace(scriptRegex, newScript);

fs.writeFileSync(filePath, content, 'utf8');
console.log('Fixed script setup');
