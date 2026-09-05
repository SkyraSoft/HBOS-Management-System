const fs = require('fs');

let content = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/DashboardView.vue', 'utf8');

// 1. Update kpis array to 8 items
const kpiPattern = /const kpis = computed\(\(\) => \[\s*([\s\S]*?)\s*\]\)/;
const newKpis = `const kpis = computed(() => [
  { title: "Today's Sales", value: \`PKR \${Number(stats.value.today_sales).toLocaleString()}\`, trend: "Today", trendUp: true, icon: "bi-cash-stack", color: "primary" },
  { title: "Net Revenue", value: \`PKR \${Number(stats.value.net_revenue).toLocaleString()}\`, trend: "Overall", trendUp: Number(stats.value.net_revenue) >= 0, icon: "bi-graph-up", color: "success" },
  { title: "Customers", value: \`\${stats.value.customer_count}\`, trend: "Registered", trendUp: true, icon: "bi-people", color: "warning" },
  { title: "Total Expenses", value: \`PKR \${Number(stats.value.total_expenses).toLocaleString()}\`, trend: "Overall", trendUp: false, icon: "bi-cash", color: "danger" },
  { title: "Total Purchases", value: \`PKR \${Number(stats.value.total_purchases).toLocaleString()}\`, trend: "Overall", trendUp: false, icon: "bi-box", color: "info" },
  { title: "Active Products", value: "1,452", trend: "In inventory", trendUp: true, icon: "bi-tags", color: "primary" },
  { title: "Low Stock Items", value: "12", trend: "Needs attention", trendUp: false, icon: "bi-exclamation-triangle", color: "warning" },
  { title: "Receivables", value: "PKR 24,500", trend: "Pending collection", trendUp: true, icon: "bi-wallet2", color: "danger" }
])`;
content = content.replace(kpiPattern, newKpis);

// 2. Update Sales Overview select and chart logic
const selectPattern = /<select class="form-select form-select-sm w-auto border-0 bg-light">[\s\S]*?<\/select>/;
const newSelect = `<select class="form-select form-select-sm w-auto border-0 bg-light" v-model="dateFilter">
              <option value="This Month">This Month</option>
              <option value="Next Month">Next Month</option>
              <option value="Previous Month">Previous Month</option>
              <option value="Combined 3 Months">Combined 3 Months</option>
            </select>`;
content = content.replace(selectPattern, newSelect);

// 3. Add chart reactive logic inside script
const scriptEndPattern = /<\/script>/;

const chartLogic = `
import { watch } from 'vue'

const dateFilter = ref('This Month')
let salesChartInstance = null

watch(dateFilter, (newVal) => {
  if (!salesChartInstance) return;
  
  let newLabels = [];
  let newData = [];
  
  if (newVal === 'This Month') {
    newLabels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
    newData = [120000, 150000, 110000, 175000];
  } else if (newVal === 'Next Month') {
    newLabels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
    newData = [130000, 160000, 120000, 180000];
  } else if (newVal === 'Previous Month') {
    newLabels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
    newData = [105000, 140000, 95000, 155000];
  } else if (newVal === 'Combined 3 Months') {
    newLabels = ['Previous', 'This Month', 'Next Month'];
    newData = [495000, 555000, 590000];
  }
  
  salesChartInstance.data.labels = newLabels;
  salesChartInstance.data.datasets[0].data = newData;
  salesChartInstance.update();
})

import ProductView from './ProductView.vue'
import ExpensesView from './ExpensesView.vue'
import CustomerView from './CustomerView.vue'

const activeModal = ref(null) // 'product', 'payment', 'expense'

const openModal = (type) => {
  activeModal.value = type
}

const closeModal = () => {
  activeModal.value = null
}

const handleModalSuccess = (msg) => {
  closeModal()
  // simple native toast or alert for success
  alert(msg || 'Action completed successfully!')
}

`;

content = content.replace(scriptEndPattern, chartLogic + '\n</script>');

// Update the Chart instance creation to save it
content = content.replace(/new window\.Chart\(ctx, \{/, 'salesChartInstance = new window.Chart(ctx, {');

// 4. Update the buttons to trigger modals
content = content.replace(/@click="router\.push\('\/products\?tab=add'\)"/g, '@click="openModal(\'product\')"');
content = content.replace(/@click="router\.push\('\/supplierpayable\?tab=payables'\)"/g, '@click="openModal(\'payment\')"');
content = content.replace(/@click="router\.push\('\/expenses'\)"/g, '@click="openModal(\'expense\')"');

// 5. Add Modal Templates at the end of the Dashboard template
const modalsTemplate = `
    <!-- Modals -->
    <div v-if="activeModal" class="modal-backdrop fade show" style="z-index: 1040;"></div>
    
    <div v-if="activeModal === 'product'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050;">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="height: 90vh;">
          <div class="modal-header bg-light border-0">
            <h5 class="modal-title fw-bold">Add Product</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body p-0" style="background: var(--bg);">
            <ProductView :isModal="true" modalPage="add" @success="handleModalSuccess('Product saved successfully!')" />
          </div>
        </div>
      </div>
    </div>

    <div v-if="activeModal === 'payment'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050;">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="height: 90vh;">
          <div class="modal-header bg-light border-0">
            <h5 class="modal-title fw-bold">Record Payment</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body p-0" style="background: var(--bg);">
            <CustomerView :isModal="true" modalPage="payment" @success="handleModalSuccess('Payment recorded successfully!')" />
          </div>
        </div>
      </div>
    </div>

    <div v-if="activeModal === 'expense'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050;">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="height: 90vh;">
          <div class="modal-header bg-light border-0">
            <h5 class="modal-title fw-bold">Add Expense</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body p-0" style="background: var(--bg);">
            <ExpensesView :isModal="true" modalPage="add" @success="handleModalSuccess('Expense saved successfully!')" />
          </div>
        </div>
      </div>
    </div>

  </div>
</template>`;

content = content.replace(/<\/div>\s*<\/template>/, modalsTemplate);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/DashboardView.vue', content);
console.log('DashboardView.vue updated.');
