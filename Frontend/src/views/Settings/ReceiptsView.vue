<script setup>
import { ref, onMounted } from 'vue'

const settings = ref({
  storeName: 'HBOS RETAIL STORE',
  storeTagline: 'Inventory & Retail Management',
  address: 'Commercial Market, Main Boulevard, Lahore',
  phone: '+92 300 1234567',
  taxNumber: 'NTN-893421-9',
  currencySymbol: 'PKR',
  paperSize: '80mm', // '58mm', '80mm', 'A4'
  showBarcode: true,
  showCashier: true,
  showSku: true,
  footerNote: 'Thank you for your business! Goods once sold can be exchanged within 7 days with valid receipt.',
  whatsappTemplate: 'Thank you for shopping with {store_name}!\n\nInvoice: #{invoice_number}\nDate: {date}\nItems: {item_count} items\nTotal: {total_amount}\n\nFor support, call {phone}.'
})

const isSaving = ref(false)
const toastMsg = ref('')
const isToastOpen = ref(false)

const showToast = (msg) => {
  toastMsg.value = msg
  isToastOpen.value = true
  setTimeout(() => { isToastOpen.value = false }, 2500)
}

onMounted(() => {
  const saved = localStorage.getItem('hbos_receipt_settings')
  if (saved) {
    try {
      settings.value = { ...settings.value, ...JSON.parse(saved) }
    } catch (e) {
      console.error('Error loading receipt settings:', e)
    }
  }
})

const saveSettings = () => {
  isSaving.value = true
  try {
    localStorage.setItem('hbos_receipt_settings', JSON.stringify(settings.value))
    setTimeout(() => {
      isSaving.value = false
      showToast('Receipt & Invoice template settings saved successfully!')
    }, 400)
  } catch (e) {
    isSaving.value = false
    showToast('Failed to save settings.')
  }
}

const resetDefaults = () => {
  if (confirm('Reset receipt settings to default template?')) {
    settings.value = {
      storeName: 'HBOS RETAIL STORE',
      storeTagline: 'Inventory & Retail Management',
      address: 'Commercial Market, Main Boulevard, Lahore',
      phone: '+92 300 1234567',
      taxNumber: 'NTN-893421-9',
      currencySymbol: 'PKR',
      paperSize: '80mm',
      showBarcode: true,
      showCashier: true,
      showSku: true,
      footerNote: 'Thank you for your business! Goods once sold can be exchanged within 7 days with valid receipt.',
      whatsappTemplate: 'Thank you for shopping with {store_name}!\n\nInvoice: #{invoice_number}\nDate: {date}\nItems: {item_count} items\nTotal: {total_amount}\n\nFor support, call {phone}.'
    }
    saveSettings()
  }
}
</script>

<template>
  <div class="rcp-page">
    <div class="rcp-header mb-4">
      <div>
        <h1 class="fs-4 fw-bold text-dark m-0">Receipt & Invoice Template Settings</h1>
        <p class="text-muted small m-0">Customize POS thermal slips, inventory intake invoices, store branding, and WhatsApp templates.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm fw-semibold rounded-3" @click="resetDefaults" type="button">
          <i class="bi bi-arrow-counterclockwise"></i> Reset Defaults
        </button>
        <button class="btn btn-primary btn-sm fw-semibold rounded-3 px-3" @click="saveSettings" :disabled="isSaving" type="button">
          <i class="bi bi-check2"></i> {{ isSaving ? 'Saving...' : 'Save Settings' }}
        </button>
      </div>
    </div>

    <div class="row g-4">
      <!-- SETTINGS FORM (LEFT) -->
      <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
          <h2 class="fs-6 fw-bold text-dark mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-shop text-primary"></i> Store & Branding Information
          </h2>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Store / Company Name</label>
              <input v-model="settings.storeName" type="text" class="form-control form-control-sm rounded-3" />
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Tagline / Subtitle</label>
              <input v-model="settings.storeTagline" type="text" class="form-control form-control-sm rounded-3" />
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Store Phone Number</label>
              <input v-model="settings.phone" type="text" class="form-control form-control-sm rounded-3" />
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Tax / NTN Registration #</label>
              <input v-model="settings.taxNumber" type="text" class="form-control form-control-sm rounded-3" />
            </div>

            <div class="col-12">
              <label class="form-label small fw-semibold text-secondary">Store Address (Appears on header)</label>
              <input v-model="settings.address" type="text" class="form-control form-control-sm rounded-3" />
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
          <h2 class="fs-6 fw-bold text-dark mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-printer text-primary"></i> Print & Layout Preferences
          </h2>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Thermal Paper Width</label>
              <select v-model="settings.paperSize" class="form-select form-select-sm rounded-3">
                <option value="80mm">80mm Standard POS Thermal</option>
                <option value="58mm">58mm Compact POS Thermal</option>
                <option value="A4">A4 Full Sheet Voucher</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Currency Symbol</label>
              <input v-model="settings.currencySymbol" type="text" class="form-control form-control-sm rounded-3" />
            </div>

            <div class="col-12">
              <div class="d-flex flex-column gap-2 pt-2">
                <div class="form-check form-switch">
                  <input v-model="settings.showBarcode" class="form-check-input" type="checkbox" id="chkBarcode" />
                  <label class="form-check-label small fw-medium" for="chkBarcode">Print Barcode on bottom of receipt</label>
                </div>
                <div class="form-check form-switch">
                  <input v-model="settings.showSku" class="form-check-input" type="checkbox" id="chkSku" />
                  <label class="form-check-label small fw-medium" for="chkSku">Show item SKU / Barcode code under item name</label>
                </div>
                <div class="form-check form-switch">
                  <input v-model="settings.showCashier" class="form-check-input" type="checkbox" id="chkCashier" />
                  <label class="form-check-label small fw-medium" for="chkCashier">Display cashier / terminal operator name</label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
          <h2 class="fs-6 fw-bold text-dark mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-chat-left-text text-primary"></i> Custom Footer & Messaging
          </h2>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Receipt Footer Policy / Note</label>
            <textarea v-model="settings.footerNote" rows="2" class="form-control form-control-sm rounded-3"></textarea>
          </div>

          <div>
            <label class="form-label small fw-semibold text-secondary">WhatsApp Share Message Template</label>
            <textarea v-model="settings.whatsappTemplate" rows="4" class="form-control form-control-sm rounded-3 font-monospace"></textarea>
            <div class="form-text small text-muted">
              Supported placeholders: <code>{store_name}</code>, <code>{invoice_number}</code>, <code>{date}</code>, <code>{item_count}</code>, <code>{total_amount}</code>, <code>{phone}</code>.
            </div>
          </div>
        </div>
      </div>

      <!-- LIVE RECEIPT PREVIEW (RIGHT) -->
      <div class="col-lg-5">
        <div class="sticky-top" style="top: 20px;">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="small fw-bold text-uppercase text-muted">Live Receipt Preview</span>
            <span class="badge bg-primary-subtle text-primary">{{ settings.paperSize }}</span>
          </div>

          <div class="card border-0 shadow-sm rounded-4 p-4" style="background: #f8fafc;">
            <div class="thermal-slip bg-white p-3 rounded-3 shadow-sm border mx-auto" :style="{ maxWidth: settings.paperSize === '58mm' ? '280px' : '340px' }">
              <!-- Header -->
              <div class="text-center">
                <div class="fw-bold fs-6 text-dark text-uppercase">{{ settings.storeName || 'STORE NAME' }}</div>
                <div class="text-muted small">{{ settings.storeTagline }}</div>
                <div class="text-muted small" style="font-size: 11px;">{{ settings.address }}</div>
                <div class="text-muted small" style="font-size: 11px;">Tel: {{ settings.phone }}</div>
                <div v-if="settings.taxNumber" class="text-muted small" style="font-size: 11px;">{{ settings.taxNumber }}</div>
              </div>

              <div class="receipt-dashed-line"></div>

              <!-- Metadata -->
              <div class="d-flex justify-content-between small text-muted" style="font-size: 11px;">
                <span>Inv: #INV-928410</span>
                <span>01 Sep 2026 16:40</span>
              </div>
              <div v-if="settings.showCashier" class="d-flex justify-content-between small text-muted" style="font-size: 11px;">
                <span>Cashier: Admin</span>
                <span>POS-01</span>
              </div>

              <div class="receipt-dashed-line"></div>

              <!-- Item List -->
              <table class="w-100" style="font-size: 12px; border-collapse: collapse;">
                <thead>
                  <tr class="text-muted text-uppercase" style="font-size: 10px; border-bottom: 1px dashed #cbd5e1;">
                    <th class="py-1 text-start">Item</th>
                    <th class="py-1 text-center">Qty</th>
                    <th class="py-1 text-end">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="py-1">
                      <div class="fw-semibold">Nestle Water 1.5L</div>
                      <div v-if="settings.showSku" class="text-muted font-monospace" style="font-size: 10px;">SKU-1002</div>
                    </td>
                    <td class="py-1 text-center fw-medium">2</td>
                    <td class="py-1 text-end fw-bold">{{ settings.currencySymbol }} 240.00</td>
                  </tr>
                  <tr>
                    <td class="py-1">
                      <div class="fw-semibold">Lays Classic 40g</div>
                      <div v-if="settings.showSku" class="text-muted font-monospace" style="font-size: 10px;">SKU-8921</div>
                    </td>
                    <td class="py-1 text-center fw-medium">3</td>
                    <td class="py-1 text-end fw-bold">{{ settings.currencySymbol }} 180.00</td>
                  </tr>
                </tbody>
              </table>

              <div class="receipt-dashed-line"></div>

              <!-- Totals -->
              <div class="small" style="font-size: 12px;">
                <div class="d-flex justify-content-between text-muted py-0.5">
                  <span>Subtotal:</span>
                  <span>{{ settings.currencySymbol }} 420.00</span>
                </div>
                <div class="d-flex justify-content-between text-muted py-0.5">
                  <span>Tax (0%):</span>
                  <span>{{ settings.currencySymbol }} 0.00</span>
                </div>
                <div class="d-flex justify-content-between fw-bold fs-6 pt-1 text-dark border-top mt-1">
                  <span>NET TOTAL:</span>
                  <span>{{ settings.currencySymbol }} 420.00</span>
                </div>
              </div>

              <!-- Barcode -->
              <div v-if="settings.showBarcode" class="text-center pt-3">
                <div class="barcode-graphic mx-auto"></div>
                <div class="font-monospace text-muted" style="font-size: 10px; letter-spacing: 2px;">* INV-928410 *</div>
              </div>

              <div class="receipt-dashed-line"></div>

              <!-- Footer -->
              <div class="text-center text-muted" style="font-size: 10.5px; line-height: 1.3;">
                {{ settings.footerNote }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast-custom" :class="{ 'toast-custom-show': isToastOpen }">
      <i class="bi bi-check-circle-fill text-success"></i>
      <span>{{ toastMsg }}</span>
    </div>
  </div>
</template>

<style scoped>
.rcp-page {
  padding: 8px;
  font-family: 'Inter', system-ui, sans-serif;
}

.receipt-dashed-line {
  border-top: 1px dashed #cbd5e1;
  margin: 10px 0;
}

.barcode-graphic {
  height: 32px;
  background: repeating-linear-gradient(
    90deg,
    #0f172a,
    #0f172a 2px,
    transparent 2px,
    transparent 4px,
    #0f172a 4px,
    #0f172a 7px,
    transparent 7px,
    transparent 9px
  );
  width: 150px;
  margin-bottom: 2px;
  opacity: 0.85;
}

.toast-custom {
  position: fixed;
  bottom: 24px;
  right: 24px;
  background: #0f172a;
  color: #ffffff;
  padding: 12px 20px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13.5px;
  font-weight: 500;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  z-index: 9999;
  transform: translateY(100px);
  opacity: 0;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.toast-custom-show {
  transform: translateY(0);
  opacity: 1;
}
</style>
