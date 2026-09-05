const fs = require('fs');

// 1. Update KhataView.vue
let khView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', 'utf8');

// Hide khata-page completely if modal
const khataPagePattern = /<section class="page khata-page" id="khataPage" v-show="!isPaymentPage">/;
khView = khView.replace(khataPagePattern, '<section class="page khata-page" id="khataPage" v-if="!props.isModal" v-show="!isPaymentPage">');

// Remove inline style from payment-page that acts like a modal
const paymentPagePattern = /<section class="page payment-page" id="paymentPage" v-show="isPaymentPage \|\| props\.isModal"[\s\S]*?:style="props\.isModal \? 'position: fixed; top: 10vh; left: 50%; transform: translateX\(-50%\); z-index: 1060; \n?pointer-events: auto; background: var\(--bg\); overflow-y: auto; border: 1px solid #ddd; box-shadow: 0 15px 40px \n?rgba\(0,0,0,0\.15\); border-radius: 16px; padding: 24px; max-width: 95vw; width: 1200px; max-height: 80vh;' : ''">/g;

khView = khView.replace(paymentPagePattern, '<section class="page payment-page" id="paymentPage" v-show="isPaymentPage || props.isModal" :style="props.isModal ? \'padding: 0; background: var(--bg);\' : \'\'">');

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', khView);

// 2. Update DashboardView.vue
let dashView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/DashboardView.vue', 'utf8');

const dashPattern = /<KhataView v-if="activeModal === 'payment'" :isModal="true" modalPage="payment" \n?@success="handleModalSuccess\('Payment recorded successfully!'\)" @close="closeModal" \n?@open-purchase="openModal\('purchase'\)" @open-return-debt="openModal\('returndebt'\)" \/>/;

const newDash = `<div v-if="activeModal === 'payment'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050; background: rgba(0,0,0,0.5); pointer-events: auto;">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="height: 90vh;">
            <div class="modal-header bg-light border-0">
              <h5 class="modal-title fw-bold">Record Payment</h5>
              <button type="button" class="btn-close" @click="closeModal"></button>
            </div>
            <div class="modal-body p-4" style="background: var(--bg);">
              <KhataView :isModal="true" modalPage="payment" 
                @success="handleModalSuccess('Payment recorded successfully!')" @close="closeModal" 
                @open-purchase="openModal('purchase')" @open-return-debt="openModal('returndebt')" />
            </div>
          </div>
        </div>
      </div>`;

dashView = dashView.replace(dashPattern, newDash);

// Fix backdrop transparency for other modals in Dashboard (they were 'background: none;' which means no grey backdrop!)
dashView = dashView.replace(/style="z-index: 1050; \n?background: none; pointer-events: none;"/g, 'style="z-index: 1050; background: rgba(0,0,0,0.5); pointer-events: auto;"');
// And fix the nested pointer-events
dashView = dashView.replace(/style="pointer-events: auto;"/g, '');

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/DashboardView.vue', dashView);
