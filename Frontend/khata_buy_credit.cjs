const fs = require('fs');
let khView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', 'utf8');

// 1. Add creditFilter and filteredCustomers
const setupPattern = /const customers = computed\(\(\) => customerStore\.customers\);/;
const newSetup = `const customers = computed(() => customerStore.customers);
  const creditFilter = ref('Buy Credit');
  const filteredCustomers = computed(() => {
      let list = customerStore.customers;
      if (creditFilter.value === 'No Credit — no outstanding debt') {
          return list.filter(c => (c.balance || 0) >= 0);
      }
      if (creditFilter.value === 'Low Credit — small outstanding debt') {
          return list.filter(c => (c.balance || 0) < 0 && (c.balance || 0) >= -20000);
      }
      if (creditFilter.value === 'High Credit — large outstanding debt') {
          return list.filter(c => (c.balance || 0) < -20000);
      }
      if (creditFilter.value === 'Overdue Credit — overdue outstanding payment') {
          return list.filter(c => (c.balance || 0) < 0 && (c.overdue_amount > 0 || c.is_overdue || (c.balance || 0) < -50000));
      }
      return list;
  });`;
khView = khView.replace(setupPattern, newSetup);

// 2. Replace Filter button with Dropdown
const filterBtnPattern = /<button class="filter-btn" type="button" onclick="showToast\('Filter options will open here.'\)">[\s\S]*?<\/button>/;
const newDropdown = `<select class="filter-btn" v-model="creditFilter" style="appearance: none; padding-right: 32px; background: #fff url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'16\\' height=\\'16\\' fill=\\'%23697386\\' class=\\'bi bi-chevron-down\\' viewBox=\\'0 0 16 16\\'><path fill-rule=\\'evenodd\\' d=\\'M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z\\'/></svg>') no-repeat right 12px center; background-size: 12px; cursor: pointer; color: #505661; border: 1px solid #e5e7eb; border-radius: 8px; font-weight: 500; font-size: 14px; outline: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                      <option value="Buy Credit" disabled hidden>Buy Credit</option>
                                      <option value="All Customers">All Customers</option>
                                      <option value="No Credit — no outstanding debt">No Credit — no outstanding debt</option>
                                      <option value="Low Credit — small outstanding debt">Low Credit — small outstanding debt</option>
                                      <option value="High Credit — large outstanding debt">High Credit — large outstanding debt</option>
                                      <option value="Overdue Credit — overdue outstanding payment">Overdue Credit — overdue outstanding payment</option>
                                  </select>`;
khView = khView.replace(filterBtnPattern, newDropdown);

// 3. Update v-for to use filteredCustomers
const vforPattern = /v-for="c in customers"/g;
khView = khView.replace(vforPattern, 'v-for="c in filteredCustomers"');

// Wait! If I replace globally, I might also change `<option v-for="c in customers">` in the datalist.
// The prompt says "Only replace the existing Filter button with a “Buy Credit” dropdown... filter the Customer Accounts table".
// So I should only change the one in the table. Let me fix the script to only change the table's v-for.

khView = khView.replace(/<tr class="customer-row" v-for="c in filteredCustomers"/, '<tr class="customer-row" v-for="c in filteredCustomers"'); // In case it was already replaced
khView = khView.replace(/<tr class="customer-row" v-for="c in customers"/, '<tr class="customer-row" v-for="c in filteredCustomers"');

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', khView);
