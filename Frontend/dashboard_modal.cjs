const fs = require('fs');
let dashView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/DashboardView.vue', 'utf8');

// Import KhataView
if (!dashView.includes("import KhataView")) {
    dashView = dashView.replace(/import CustomerView from '\.\/CustomerView\.vue';/, `import CustomerView from './CustomerView.vue';\nimport KhataView from './KhataView.vue';`);
}

// Replace CustomerView with KhataView for payment modal
const paymentModalPattern = /<CustomerView v-if="activeModal === 'payment'"(.*?)\/>/;
dashView = dashView.replace(paymentModalPattern, `<KhataView v-if="activeModal === 'payment'"$1/>`);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/DashboardView.vue', dashView);
