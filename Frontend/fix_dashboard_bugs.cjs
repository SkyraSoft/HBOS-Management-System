const fs = require('fs');
let content = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/DashboardView.vue', 'utf8');

// 1. Fix the invalid </div> tag after CustomerView
content = content.replace(/<CustomerView v-if="activeModal === 'payment'" :isModal="true" modalPage="payment" @success="handleModalSuccess\('Payment recorded successfully!'\)" @close="closeModal" \/>\s*<\/div>/, `<CustomerView v-if="activeModal === 'payment'" :isModal="true" modalPage="payment" @success="handleModalSuccess('Payment recorded successfully!')" @close="closeModal" />`);

// 2. Fix the router.push buttons
content = content.replace(/@click="router\.push\('\/product'\)"/, `@click="openModal('product')"`);
content = content.replace(/@click="router\.push\('\/khata'\)"/, `@click="openModal('payment')"`); // The Record Payment button
content = content.replace(/@click="router\.push\('\/expenses'\)"/, `@click="openModal('expense')"`); // Add Expense (in case it failed too)

// Wait, the alerts section ALSO has @click="router.push('/khata')". Let's use a specific replacement for the buttons
content = content.replace(/<button @click="router\.push\('\/product'\)"/g, `<button @click="openModal('product')"`);
content = content.replace(/<button @click="router\.push\('\/khata'\)"/g, `<button @click="openModal('payment')"`);
content = content.replace(/<button @click="router\.push\('\/expenses'\)"/g, `<button @click="openModal('expense')"`);

// 3. Add Combined 6 Months to the select dropdown
content = content.replace(/<option value="Combined 3 Months">Combined 3 Months<\/option>/, `<option value="Combined 3 Months">Combined 3 Months</option>\n              <option value="Combined 6 Months">Combined 6 Months</option>`);

// 4. Update the chart logic to support Combined 6 Months
content = content.replace(/\} else if \(newVal === 'Combined 3 Months'\) \{[\s\S]*?newData = \[495000, 555000, 590000\];\n\s*\}/, `} else if (newVal === 'Combined 3 Months') {
    newLabels = ['Previous', 'This Month', 'Next Month'];
    newData = [495000, 555000, 590000];
  } else if (newVal === 'Combined 6 Months') {
    newLabels = ['M-5', 'M-4', 'M-3', 'M-2', 'M-1', 'This Month'];
    newData = [400000, 420000, 495000, 520000, 555000, 600000];
  }`);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/DashboardView.vue', content);
console.log('DashboardView.vue patched.');
