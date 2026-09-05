const fs = require('fs');

// 1. Update CustomerView.vue
let cView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/CustomerView.vue', 'utf8');

// Add buttons to the modal footer
const modalFooterPattern = /<div class="modal-footer">\s*<button type="button" class="btn btn-secondary" @click="closeModal\('recordPaymentModal'\)">Cancel<\/button>\s*<button type="button" class="btn btn-primary" @click="savePayment">Record Payment<\/button>\s*<\/div>/;
const newModalFooter = `<div class="modal-footer justify-content-between">
    <div>
        <button type="button" class="btn btn-info me-2 text-white" @click="$emit('open-purchase')">New Purchase</button>
        <button type="button" class="btn btn-warning" @click="$emit('open-return-debt')">Return Debt</button>
    </div>
    <div>
        <button type="button" class="btn btn-secondary me-2" @click="closeModal('recordPaymentModal')">Cancel</button>
        <button type="button" class="btn btn-primary" @click="savePayment">Record Payment</button>
    </div>
</div>`;
cView = cView.replace(modalFooterPattern, newModalFooter);

// Add emits
cView = cView.replace(/const emit = defineEmits\(\['success', 'close'\]\)/, `const emit = defineEmits(['success', 'close', 'open-purchase', 'open-return-debt'])`);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/CustomerView.vue', cView);

// 2. Update DashboardView.vue
let dView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/DashboardView.vue', 'utf8');

// A. Imports
if (!dView.includes('import PurchaseView')) {
    dView = dView.replace(/import CustomerView from '\.\/CustomerView\.vue'/, `import CustomerView from './CustomerView.vue'\nimport PurchaseView from './PurchaseView.vue'\nimport ReturnDebtView from './ReturnDebtView.vue'`);
}

// B. Template
// Inside <CustomerView ... />, add @open-purchase and @open-return-debt
const cViewTagPattern = /<CustomerView v-if="activeModal === 'payment'" :isModal="true" modalPage="payment" @success="handleModalSuccess\('Payment recorded successfully!'\)" @close="closeModal" \/>/;
const newCViewTag = `<CustomerView v-if="activeModal === 'payment'" :isModal="true" modalPage="payment" @success="handleModalSuccess('Payment recorded successfully!')" @close="closeModal" @open-purchase="openModal('purchase')" @open-return-debt="openModal('returndebt')" />`;
dView = dView.replace(cViewTagPattern, newCViewTag);

// C. Add new modals
const newModals = `
    <!-- Purchase Modal -->
    <div v-if="activeModal === 'purchase'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050;">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="height: 90vh;">
          <div class="modal-header bg-light border-0">
            <h5 class="modal-title fw-bold">New Purchase</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body p-0" style="background: var(--bg);">
            <PurchaseView :isModal="true" @success="handleModalSuccess('Purchase recorded successfully!')" />
          </div>
        </div>
      </div>
    </div>

    <!-- Return Debt Modal -->
    <div v-if="activeModal === 'returndebt'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050;">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="height: 90vh;">
          <div class="modal-header bg-light border-0">
            <h5 class="modal-title fw-bold">Return Debt</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body p-0" style="background: var(--bg);">
            <ReturnDebtView :isModal="true" @success="handleModalSuccess('Debt returned successfully!')" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>`;
dView = dView.replace(/<\/div>\s*<\/template>/, newModals);

// D. openModal routing
const openModalPattern = /const openModal = \(type\) => \{[\s\S]*?window\.history\.pushState\(null, '', path\);\s*\}/;
const newOpenModal = `const openModal = (type) => {
  activeModal.value = type
  let path = '/';
  if (type === 'product') path = '/add-product';
  else if (type === 'payment') path = '/record-payment';
  else if (type === 'expense') path = '/add-expense';
  else if (type === 'purchase') path = '/new-purchase';
  else if (type === 'returndebt') path = '/return-debt';
  window.history.pushState(null, '', path);
}`;
dView = dView.replace(openModalPattern, newOpenModal);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/DashboardView.vue', dView);

// 3. Update PurchaseView.vue to support isModal
let pView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/PurchaseView.vue', 'utf8');

// Add props
if (!pView.includes('defineProps')) {
    pView = pView.replace(/const store = usePurchasesStore\(\);/, `const props = defineProps({ isModal: { type: Boolean, default: false } });\nconst emit = defineEmits(['success']);\nconst store = usePurchasesStore();`);
}
// Force isNewPurchase if isModal
pView = pView.replace(/const isNewPurchase = ref\(route\.query\.new === 'true'\);/, `const isNewPurchase = ref(props.isModal ? true : route.query.new === 'true');`);
// Hide page-header when isModal
pView = pView.replace(/<div class="purchase-header">/, `<div class="purchase-header" v-if="!isModal">`);
pView = pView.replace(/<div class="purchase-header" id="sticky-header">/, `<div class="purchase-header" id="sticky-header" v-if="!isModal">`);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/PurchaseView.vue', pView);
console.log('Finished updating files.');
