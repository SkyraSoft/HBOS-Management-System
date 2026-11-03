<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api'
import { useRetailStore } from '@/stores/retail'

const retailStore = useRetailStore()

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
  receiptFooter: '',
  footerNote: '',
  returnPolicy: '',
  whatsappTemplate: 'Thank you for shopping with {store_name}!\n\nInvoice: #{invoice_number}\nDate: {date}\nItems: {item_count} items\nTotal: {total_amount}\n\nFor support, call {phone}.'
})

const isSaving = ref(false)
const toastMsg = ref('')
const isToastOpen = ref(false)

// Barcode Label Sheet Generator State
const selectedLabelProduct = ref(null)
const labelQuantity = ref(24)
const labelGridDensity = ref(24)
const generatedLabels = ref([])

const handleLogoUpload = (event) => {
  const file = event.target.files?.[0];
  if (!file) return;

  if (file.size > 2 * 1024 * 1024) {
    showToast('Logo image must be smaller than 2MB.');
    return;
  }

  const reader = new FileReader();
  reader.onload = (e) => {
    settings.value.receiptLogo = e.target.result;
    settings.value.showLogo = true;
    if (settings.value.headerDisplayMode === 'name_only') {
      settings.value.headerDisplayMode = 'both';
      settings.value.showStoreName = true;
    }
    showToast('Store logo uploaded successfully! Preview updated.');
  };
  reader.readAsDataURL(file);
};

const removeLogo = () => {
  settings.value.receiptLogo = '';
  if (settings.value.headerDisplayMode === 'logo_only') {
    settings.value.headerDisplayMode = 'name_only';
    settings.value.showStoreName = true;
  }
  showToast('Logo removed from receipt template.');
};

const loadSampleLogo = () => {
  // Clean high-contrast monochrome SVG retail emblem
  settings.value.receiptLogo = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 48" width="160" height="48"><rect width="160" height="48" rx="8" fill="%230f172a"/><path d="M16 14h6l4 14h10l3-10H24" fill="none" stroke="%2338bdf8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="27" cy="32" r="2.5" fill="%2338bdf8"/><circle cx="35" cy="32" r="2.5" fill="%2338bdf8"/><text x="48" y="26" fill="%23ffffff" font-family="Arial, sans-serif" font-weight="900" font-size="14" letter-spacing="1">HBOS</text><text x="48" y="36" fill="%2394a3b8" font-family="Arial, sans-serif" font-weight="600" font-size="8.5" letter-spacing="2">ENTERPRISE</text></svg>';
  settings.value.showLogo = true;
  settings.value.headerDisplayMode = 'both';
  settings.value.showStoreName = true;
  showToast('Sample retail logo loaded!');
};

const syncModeFromToggles = () => {
  if (settings.value.showLogo && settings.value.showStoreName) {
    settings.value.headerDisplayMode = 'both';
  } else if (settings.value.showLogo && !settings.value.showStoreName) {
    settings.value.headerDisplayMode = 'logo_only';
  } else if (!settings.value.showLogo && settings.value.showStoreName) {
    settings.value.headerDisplayMode = 'name_only';
  }
};

const showToast = (msg) => {
  toastMsg.value = msg
  isToastOpen.value = true
  setTimeout(() => { isToastOpen.value = false }, 2500)
}

const loadSettings = async () => {
  try {
    const localPaper = localStorage.getItem('hbos_receipt_paper_size')
    if (localPaper) settings.value.paperSize = localPaper

    const res = await api.get('/settings')
    const data = res.data
    if (data.receipt_settings && typeof data.receipt_settings === 'object') {
      settings.value = { ...settings.value, ...data.receipt_settings }
    }
    if (data.receipt_header) settings.value.storeTagline = data.receipt_header
    if (data.receipt_footer) {
      settings.value.footerNote = data.receipt_footer
      settings.value.receiptFooter = data.receipt_footer
    }
  } catch (e) {
    console.error('Failed to load server settings:', e)
  }
}

onMounted(async () => {
  await retailStore.fetchProducts()
  await loadSettings()
  if (retailStore.products.length > 0 && !selectedLabelProduct.value) {
    selectedLabelProduct.value = retailStore.products[0].id
    generateLabelSheet()
  }
})

const saveSettings = async () => {
  isSaving.value = true
  try {
    localStorage.setItem('hbos_receipt_paper_size', settings.value.paperSize || '80mm')
    localStorage.removeItem('hbos_receipt_return_policy')
    await api.post('/settings', {
      settings: {
        receipt_header: settings.value.storeTagline || settings.value.storeName,
        receipt_footer: settings.value.returnPolicy || settings.value.receiptFooter || settings.value.footerNote || '',
        show_tax_number: !!settings.value.taxNumber,
        receipt_settings: settings.value
      }
    })
    isSaving.value = false
    showToast('Receipt & Invoice template settings saved successfully!')
  } catch (e) {
    isSaving.value = false
    showToast(e.response?.data?.message || 'Failed to save settings.')
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
      receiptFooter: '',
      footerNote: '',
      returnPolicy: '',
      whatsappTemplate: 'Thank you for shopping with {store_name}!\n\nInvoice: #{invoice_number}\nDate: {date}\nItems: {item_count} items\nTotal: {total_amount}\n\nFor support, call {phone}.'
    }
    saveSettings()
  }
}

const generateLabelSheet = () => {
  if (!selectedLabelProduct.value) return
  const prod = retailStore.products.find(p => String(p.id) === String(selectedLabelProduct.value))
  if (!prod) return
  const qty = Math.max(1, parseInt(labelQuantity.value, 10) || 1)
  const labels = []
  for (let i = 0; i < qty; i++) {
    labels.push({
      id: `${prod.id}_${i}`,
      name: prod.name,
      sku: prod.sku || `SKU-${prod.id}`,
      barcode: prod.barcode || prod.sku || `PROD-${prod.id}`,
      price: prod.price || 0
    })
  }
  generatedLabels.value = labels
}

const printLabelSheet = () => {
  window.print()
}

const generateBarcodeSvg = (code) => {
  if (!code) return ''
  return `<svg viewBox="0 0 120 28" width="100%" height="24" xmlns="http://www.w3.org/2000/svg">
    <rect x="0" y="0" width="120" height="28" fill="transparent"/>
    <path d="M4 2h2v24H4zM8 2h1v24H8zM11 2h3v24H11zM16 2h2v24H16zM20 2h1v24H20zM23 2h3v24H23zM28 2h2v24H28zM32 2h1v24H32zM35 2h3v24H35zM40 2h2v24H40zM44 2h1v24H44zM47 2h3v24H47zM52 2h2v24H52zM56 2h1v24H56zM59 2h3v24H59zM64 2h2v24H64zM68 2h1v24H68zM71 2h3v24H71zM76 2h2v24H76zM80 2h1v24H80zM83 2h3v24H83zM88 2h2v24H88zM92 2h1v24H92zM95 2h3v24H95zM100 2h2v24H100zM104 2h1v24H104zM107 2h3v24H107zM112 2h2v24H112z" fill="#000"/>
  </svg>`
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
                  <label class="form-check-label small fw-medium" for="chkCashier">Display salesperson / terminal operator name</label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
          <h2 class="fs-6 fw-bold text-dark mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-chat-left-text text-primary"></i> Custom Footer & Messaging
          </h2>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label small fw-semibold text-secondary">Receipt Footer Note</label>
              <textarea v-model="settings.receiptFooter" rows="2" class="form-control form-control-sm rounded-3"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold text-secondary">Return / Exchange Policy Text</label>
              <textarea v-model="settings.returnPolicy" rows="2" class="form-control form-control-sm rounded-3"></textarea>
            </div>
          </div>
        </div>

        <!-- BARCODE LABEL SHEET GENERATOR (PHASE 4 SRT-GAP-011) -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="fs-6 fw-bold text-dark m-0 d-flex align-items-center gap-2">
              <i class="bi bi-upc-scan text-primary"></i> Barcode Label Sheet Generator
            </h2>
            <button class="btn btn-outline-primary btn-sm rounded-3 fw-semibold" @click="printLabelSheet" :disabled="generatedLabels.length === 0" type="button">
              <i class="bi bi-printer me-1"></i> Print Label Sheet
            </button>
          </div>
          <p class="text-muted small mb-3">Generate printable sticker label sheets for shelf or product tagging using existing catalog barcodes.</p>

          <div class="row g-3 align-items-end mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Select Product from Catalog</label>
              <select v-model="selectedLabelProduct" @change="generateLabelSheet" class="form-select form-select-sm rounded-3">
                <option :value="null" disabled>Choose a product...</option>
                <option v-for="prod in retailStore.products" :key="prod.id" :value="prod.id">
                  {{ prod.name }} (SKU: {{ prod.sku || 'N/A' }}) - Rs. {{ Number(prod.price || 0).toLocaleString() }}
                </option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold text-secondary">Label Quantity</label>
              <input type="number" min="1" max="100" v-model.number="labelQuantity" @input="generateLabelSheet" class="form-control form-control-sm rounded-3" />
            </div>
            <div class="col-md-3">
              <button class="btn btn-primary btn-sm w-100 rounded-3 fw-semibold" @click="generateLabelSheet" type="button">
                <i class="bi bi-grid-3x3 me-1"></i> Generate Grid
              </button>
            </div>
          </div>

          <!-- Label Sheet Preview Grid -->
          <div v-if="generatedLabels.length > 0" class="label-sheet-container border rounded-3 p-3 bg-light">
            <div class="d-flex justify-content-between align-items-center small fw-semibold text-secondary mb-2">
              <span>Sticker Sheet Preview ({{ generatedLabels.length }} Labels):</span>
              <span class="badge bg-secondary-subtle text-secondary">A4 Compatible Grid</span>
            </div>
            <div class="label-grid-preview" id="labelPrintArea">
              <div v-for="lbl in generatedLabels" :key="lbl.id" class="barcode-sticker-card bg-white border rounded-2 p-2 text-center shadow-xs">
                <div class="sticker-product-name fw-bold text-truncate" :title="lbl.name" style="font-size: 11px;">{{ lbl.name }}</div>
                <div class="sticker-price fw-bold text-primary" style="font-size: 11px;">Rs. {{ Number(lbl.price).toLocaleString('en-PK') }}</div>
                <div class="sticker-barcode-svg my-1" v-html="generateBarcodeSvg(lbl.barcode)"></div>
                <div class="sticker-barcode-text font-monospace text-muted" style="font-size: 9.5px; letter-spacing: 1px;">{{ lbl.barcode }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- THERMAL PREVIEW (RIGHT) -->
      <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top: 2rem;">
          <h2 class="fs-6 fw-bold text-dark mb-3 d-flex align-items-center justify-content-between">
            <span class="d-flex align-items-center gap-2"><i class="bi bi-receipt text-primary"></i> Live Thermal Slip Preview</span>
            <span class="badge bg-light text-secondary border fw-medium">{{ settings.paperSize }}</span>
          </h2>

          <!-- PREVIEW TICKET -->
          <div class="thermal-ticket bg-white border rounded-3 p-3 shadow-sm mx-auto" :style="{ maxWidth: settings.paperSize === '58mm' ? '280px' : '380px' }">
            <div class="text-center mb-3">
              <div class="fw-bold fs-5 text-dark">{{ settings.storeName || 'My Business' }}</div>
              <div v-if="settings.storeTagline" class="text-muted small" style="font-size: 11px;">{{ settings.storeTagline }}</div>
              <div v-if="settings.storeAddress" class="text-muted small" style="font-size: 11px;">{{ settings.storeAddress }}</div>
              <div v-if="settings.storePhone" class="text-muted small" style="font-size: 11px;">Tel: {{ settings.storePhone }}</div>
              <div v-if="settings.taxNumber" class="text-muted small" style="font-size: 11px;">NTN / Tax ID: {{ settings.taxNumber }}</div>

              <div class="receipt-dashed-line"></div>

              <!-- Metadata -->
              <div class="d-flex justify-content-between small text-muted" style="font-size: 11px;">
                <span>Inv: #INV-928410</span>
                <span>01 Sep 2026 16:40</span>
              </div>
              <div v-if="settings.showCashier" class="d-flex justify-content-between small text-muted" style="font-size: 11px;">
                <span>Salesperson: Ahmed Khan</span>
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

.label-grid-preview {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  gap: 10px;
  max-height: 380px;
  overflow-y: auto;
  padding: 4px;
}

.barcode-sticker-card {
  min-height: 90px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

@media print {
  body * {
    visibility: hidden;
  }
  #labelPrintArea, #labelPrintArea * {
    visibility: visible;
  }
  #labelPrintArea {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    padding: 10mm;
  }
}
</style>
