<template>
  <div class="support-page-wrapper px-3 px-md-4 py-4">

    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-3 border-bottom">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
            <i class="bi bi-patch-question-fill me-1"></i> Knowledge Base & Store Manual
          </span>
          <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1 small">
            90 Store Guides
          </span>
        </div>
        <h2 class="fw-bold mb-1" style="color: var(--text-primary);">Help & Support Center</h2>
        <p class="text-secondary mb-0">Practical solutions, workflows, and answers covering every module of your retail store.</p>
      </div>

      <!-- HEADER ACTIONS -->
      <div class="d-flex flex-wrap gap-2">
        <button 
          class="btn btn-outline-secondary fw-medium d-flex align-items-center gap-2" 
          style="border-radius: 8px;"
          @click="toggleAllQuestions"
        >
          <i :class="allExpanded ? 'bi bi-dash-square' : 'bi bi-plus-square'"></i>
          <span>{{ allExpanded ? 'Collapse All' : 'Expand All' }}</span>
        </button>
        <button 
          class="btn btn-primary fw-medium d-flex align-items-center gap-2" 
          style="border-radius: 8px;"
          @click="showContactModal = true"
        >
          <i class="bi bi-chat-dots-fill"></i>
          <span>Contact Support</span>
        </button>
      </div>
    </div>

    <!-- SEARCH & FILTER TOOLBAR -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-body">
      <div class="row g-2 align-items-center">
        <div class="col-12 col-md-7">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted">
              <i class="bi bi-search"></i>
            </span>
            <input 
              v-model="searchQuery" 
              type="text" 
              class="form-control bg-light border-start-0 ps-0" 
              placeholder="Search across all 90 store questions (e.g., barcode, khata, thermal printer, tax, profit)..."
            />
            <button 
              v-if="searchQuery" 
              class="btn btn-light border" 
              type="button" 
              @click="searchQuery = ''"
            >
              <i class="bi bi-x-circle text-muted"></i>
            </button>
          </div>
        </div>
        <div class="col-12 col-md-5 d-flex justify-content-md-end align-items-center gap-2">
          <span class="small text-muted text-nowrap">
            Showing <strong class="text-primary">{{ filteredFaqs.length }}</strong> questions
            <span v-if="searchQuery">for "<em>{{ searchQuery }}</em>"</span>
          </span>
          <button 
            v-if="searchQuery" 
            class="btn btn-sm btn-outline-secondary" 
            @click="searchQuery = ''"
          >
            Clear Search
          </button>
        </div>
      </div>
    </div>

    <!-- TABS BAR (9 MODULE TABS) -->
    <div class="mb-4">
      <div class="module-tabs-scroll d-flex gap-2 pb-2">
        <button
          v-for="tab in moduleTabs"
          :key="tab.id"
          class="btn module-tab-btn d-flex align-items-center gap-2 text-nowrap px-3 py-2 rounded-pill transition-all"
          :class="activeTab === tab.id ? 'btn-primary shadow-sm fw-semibold' : 'btn-light border text-secondary fw-medium'"
          @click="selectTab(tab.id)"
        >
          <i :class="tab.icon"></i>
          <span>{{ tab.label }}</span>
          <span 
            class="badge rounded-pill ms-1"
            :class="activeTab === tab.id ? 'bg-white text-primary' : 'bg-secondary-subtle text-secondary'"
          >
            {{ getTabQuestionCount(tab.id) }}
          </span>
        </button>
      </div>
    </div>

    <!-- MAIN CONTENT GRID -->
    <div class="row g-4 mb-4">
      
      <!-- FAQ Section Accordion -->
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
          
          <!-- Current Tab Header -->
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-3 border-bottom">
            <div class="d-flex align-items-center gap-3">
              <div class="tab-icon-badge rounded-circle p-2 bg-primary-subtle text-primary d-flex align-items-center justify-content-center">
                <i :class="currentTabInfo.icon + ' fs-4'"></i>
              </div>
              <div>
                <h5 class="fw-bold mb-0 text-dark">{{ currentTabInfo.label }}</h5>
                <span class="small text-muted">{{ currentTabInfo.description }}</span>
              </div>
            </div>
            <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill small">
              {{ filteredFaqs.length }} Questions Found
            </span>
          </div>

          <!-- If Search / Filter has no results -->
          <div v-if="filteredFaqs.length === 0" class="text-center py-5">
            <div class="mb-3 text-muted">
              <i class="bi bi-search display-5 opacity-50"></i>
            </div>
            <h6 class="fw-bold text-secondary">No questions found</h6>
            <p class="text-muted small mb-3">No matching FAQs found for "{{ searchQuery }}". Try searching another term or switch tabs.</p>
            <button class="btn btn-sm btn-outline-primary" @click="searchQuery = ''; activeTab = 'dashboard'">
              Reset Filters
            </button>
          </div>

          <!-- Questions Accordion -->
          <div v-else class="accordion accordion-flush" id="faqAccordion">
            <div 
              v-for="(faq, index) in filteredFaqs" 
              :key="faq.id"
              class="accordion-item mb-3 border rounded-3 overflow-hidden shadow-xs"
            >
              <h2 class="accordion-header">
                <button 
                  class="accordion-button fw-semibold py-3 px-3 d-flex align-items-center gap-2" 
                  :class="{ collapsed: !isQuestionOpen(faq.id) }"
                  type="button" 
                  @click="toggleQuestion(faq.id)"
                  :aria-expanded="isQuestionOpen(faq.id)"
                >
                  <span class="badge bg-light text-primary border rounded-pill px-2 py-1 me-1 small">
                    Q{{ index + 1 }}
                  </span>
                  <span class="text-dark flex-grow-1">{{ faq.question }}</span>
                  <span 
                    v-if="searchQuery" 
                    class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill me-2 small text-nowrap"
                  >
                    {{ getTabLabel(faq.category) }}
                  </span>
                </button>
              </h2>
              <div 
                class="accordion-collapse collapse" 
                :class="{ show: isQuestionOpen(faq.id) }"
              >
                <div class="accordion-body bg-light-subtle text-secondary px-4 py-3" style="font-size: 0.95rem; line-height: 1.65;">
                  <!-- Answer Content -->
                  <div class="answer-text mb-3" v-html="formatAnswer(faq.answer)"></div>

                  <!-- Optional Step-by-Step bullets if provided -->
                  <div v-if="faq.steps && faq.steps.length" class="mt-2 p-3 bg-white rounded-3 border">
                    <strong class="d-block text-dark small mb-2 text-uppercase fw-bold">
                      <i class="bi bi-check2-circle text-success me-1"></i> Quick Step-by-Step Action:
                    </strong>
                    <ol class="mb-0 ps-3 small text-secondary">
                      <li v-for="(step, sIdx) in faq.steps" :key="sIdx" class="mb-1">
                        {{ step }}
                      </li>
                    </ol>
                  </div>

                  <!-- Store Tip Note -->
                  <div v-if="faq.tip" class="mt-2 p-2 px-3 bg-warning-subtle text-warning-emphasis border-start border-warning border-3 rounded-end small d-flex align-items-start gap-2">
                    <i class="bi bi-lightbulb-fill mt-1"></i>
                    <div>
                      <strong>Store Best Practice:</strong> {{ faq.tip }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Quick Resources & Support Sidebar -->
      <div class="col-lg-4">
        
        <!-- Live System Navigation Helper -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
          <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-compass text-primary"></i>
            Quick Jump to Modules
          </h6>
          <div class="list-group list-group-flush small">
            <router-link to="/" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-0 border-bottom">
              <span><i class="bi bi-grid-1x2 text-muted me-2"></i> Main Dashboard</span>
              <i class="bi bi-arrow-right text-muted"></i>
            </router-link>
            <router-link to="/POS?tab=sales" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-0 border-bottom">
              <span><i class="bi bi-cart3 text-muted me-2"></i> POS Checkout</span>
              <i class="bi bi-arrow-right text-muted"></i>
            </router-link>
            <router-link to="/sales" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-0 border-bottom">
              <span><i class="bi bi-receipt text-muted me-2"></i> Sales History</span>
              <i class="bi bi-arrow-right text-muted"></i>
            </router-link>
            <router-link to="/inventry" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-0 border-bottom">
              <span><i class="bi bi-boxes text-muted me-2"></i> Inventory & Stock</span>
              <i class="bi bi-arrow-right text-muted"></i>
            </router-link>
            <router-link to="/customer?tab=customers" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-0 border-bottom">
              <span><i class="bi bi-people text-muted me-2"></i> Customers & Khata</span>
              <i class="bi bi-arrow-right text-muted"></i>
            </router-link>
            <router-link to="/supplierpayable" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-0 border-bottom">
              <span><i class="bi bi-truck text-muted me-2"></i> Suppliers & Bills</span>
              <i class="bi bi-arrow-right text-muted"></i>
            </router-link>
            <router-link to="/expenses" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-0 border-bottom">
              <span><i class="bi bi-wallet2 text-muted me-2"></i> Expenses & Petty Cash</span>
              <i class="bi bi-arrow-right text-muted"></i>
            </router-link>
            <router-link to="/reports?tab=reports" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-0 border-bottom">
              <span><i class="bi bi-graph-up text-muted me-2"></i> Reports & Profit</span>
              <i class="bi bi-arrow-right text-muted"></i>
            </router-link>
            <router-link to="/settings" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-0">
              <span><i class="bi bi-gear text-muted me-2"></i> Store Settings</span>
              <i class="bi bi-arrow-right text-muted"></i>
            </router-link>
          </div>
        </div>

        <!-- Quick Resources Card -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
          <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-journal-bookmark text-primary"></i>
            Quick Resources
          </h6>
          
          <div 
            class="d-flex align-items-center gap-3 p-3 border rounded-3 mb-3 cursor-pointer hover-bg-light transition-all"
            @click="showManualGuide = true"
          >
            <div class="bg-primary-subtle p-2 rounded text-primary">
              <i class="bi bi-journal-text fs-5"></i>
            </div>
            <div>
              <h6 class="fw-semibold mb-0">Store Manual & SOPs</h6>
              <span class="small text-secondary">Step-by-step cashier handbook</span>
            </div>
          </div>

          <div 
            class="d-flex align-items-center gap-3 p-3 border rounded-3 mb-3 cursor-pointer hover-bg-light transition-all"
            @click="showHardwareGuide = true"
          >
            <div class="bg-info-subtle p-2 rounded text-info">
              <i class="bi bi-printer fs-5"></i>
            </div>
            <div>
              <h6 class="fw-semibold mb-0">Hardware Setup Guide</h6>
              <span class="small text-secondary">Printers, scanners & cash drawers</span>
            </div>
          </div>

          <div 
            class="d-flex align-items-center gap-3 p-3 border rounded-3 cursor-pointer hover-bg-light transition-all"
            @click="showBugModal = true"
          >
            <div class="bg-danger-subtle p-2 rounded text-danger">
              <i class="bi bi-bug fs-5"></i>
            </div>
            <div>
              <h6 class="fw-semibold mb-0">Report an Issue</h6>
              <span class="small text-secondary">Send feedback directly to developers</span>
            </div>
          </div>

        </div>

        <!-- Direct Contact Support Box -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-primary text-white position-relative overflow-hidden">
          <div class="position-relative z-1">
            <div class="d-flex align-items-center gap-2 mb-2">
              <i class="bi bi-headset fs-4"></i>
              <h5 class="fw-bold mb-0">Need Live Help?</h5>
            </div>
            <p class="small mb-3 opacity-90">Our technical support engineers and POS specialists are available Mon-Sat, 9:00 AM – 9:00 PM.</p>
            
            <div class="d-flex align-items-center gap-2 mb-2 p-2 rounded bg-white bg-opacity-10">
              <i class="bi bi-telephone-fill"></i>
              <span class="fw-semibold small">+92 300 1234567 / 0800-HBOS</span>
            </div>
            <div class="d-flex align-items-center gap-2 mb-3 p-2 rounded bg-white bg-opacity-10">
              <i class="bi bi-whatsapp"></i>
              <span class="fw-semibold small">WhatsApp: +92 321 7654321</span>
            </div>
            <div class="d-flex align-items-center gap-2 mb-3 p-2 rounded bg-white bg-opacity-10">
              <i class="bi bi-envelope-fill"></i>
              <span class="fw-semibold small">support@skyrasoft.com</span>
            </div>

            <button 
              class="btn btn-light text-primary fw-bold w-100 rounded-3 shadow-sm py-2"
              @click="showContactModal = true"
            >
              <i class="bi bi-send-fill me-1"></i> Request Support Callback
            </button>
          </div>
        </div>

      </div>

    </div>

    <!-- CONTACT SUPPORT MODAL -->
    <div v-if="showContactModal" class="modal-backdrop fade show"></div>
    <div v-if="showContactModal" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-bottom">
            <h5 class="modal-title fw-bold text-dark">
              <i class="bi bi-chat-dots-fill text-primary me-2"></i> Contact Technical Support
            </h5>
            <button type="button" class="btn-close" @click="showContactModal = false"></button>
          </div>
          <div class="modal-body p-4">
            <div v-if="contactSubmitted" class="alert alert-success d-flex align-items-center gap-2 mb-0">
              <i class="bi bi-check-circle-fill fs-4"></i>
              <div>
                <strong>Ticket Created!</strong> Our store support specialist will contact you within 30 minutes.
              </div>
            </div>
            <form v-else @submit.prevent="submitContactTicket">
              <div class="mb-3">
                <label class="form-label small fw-bold">Store / Branch Name</label>
                <input v-model="contactForm.store" type="text" class="form-control" required placeholder="e.g. Main Market Superstore" />
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Contact Person & Phone</label>
                <input v-model="contactForm.phone" type="text" class="form-control" required placeholder="e.g. Asif Khan - 03001234567" />
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Issue Category</label>
                <select v-model="contactForm.category" class="form-select">
                  <option value="POS & Printing">POS & Thermal Printing</option>
                  <option value="Barcode & Scanner">Barcode & Scanner Setup</option>
                  <option value="Inventory & Stock">Inventory & Stock Inconsistency</option>
                  <option value="Khata & Ledgers">Khata & Customer Balances</option>
                  <option value="General Question">General How-To Question</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Describe what you need help with</label>
                <textarea v-model="contactForm.message" class="form-control" rows="3" required placeholder="Explain what happened or what error you are seeing..."></textarea>
              </div>
              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-light" @click="showContactModal = false">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold">Submit Ticket</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- HARDWARE GUIDE MODAL -->
    <div v-if="showHardwareGuide" class="modal-backdrop fade show"></div>
    <div v-if="showHardwareGuide" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-bottom">
            <h5 class="modal-title fw-bold text-dark">
              <i class="bi bi-printer-fill text-info me-2"></i> Store Hardware Setup Guide
            </h5>
            <button type="button" class="btn-close" @click="showHardwareGuide = false"></button>
          </div>
          <div class="modal-body p-4">
            <div class="row g-3">
              <div class="col-md-6">
                <div class="p-3 border rounded-3 bg-light h-100">
                  <h6 class="fw-bold text-dark"><i class="bi bi-printer me-2 text-primary"></i> Thermal Receipt Printers</h6>
                  <p class="small text-secondary mb-2">Supported models: Epson TM-T20, Xprinter XP-58/XP-80, Black Copper, Sunmi POS.</p>
                  <ul class="small ps-3 text-secondary mb-0">
                    <li>Plug USB cable and install vendor Windows ESC/POS driver.</li>
                    <li>Set paper size to <strong>80mm</strong> or <strong>58mm</strong> in Windows printer preferences.</li>
                    <li>In HBOS Settings > Receipts, match paper width and enable Auto-Print.</li>
                  </ul>
                </div>
              </div>
              <div class="col-md-6">
                <div class="p-3 border rounded-3 bg-light h-100">
                  <h6 class="fw-bold text-dark"><i class="bi bi-upc-scan me-2 text-success"></i> Barcode Scanners</h6>
                  <p class="small text-secondary mb-2">Supported: 1D Laser scanners, 2D QR scanners, wireless Bluetooth & USB guns.</p>
                  <ul class="small ps-3 text-secondary mb-0">
                    <li>Most scanners are plug-and-play USB HID keyboard emulators.</li>
                    <li>Ensure scanner is set to send a trailing <strong>"Enter" / Carriage Return</strong> suffix.</li>
                    <li>Scan any product barcode at POS; cart updates instantly.</li>
                  </ul>
                </div>
              </div>
              <div class="col-md-6">
                <div class="p-3 border rounded-3 bg-light h-100">
                  <h6 class="fw-bold text-dark"><i class="bi bi-cash-stack me-2 text-warning"></i> Electronic Cash Drawers</h6>
                  <p class="small text-secondary mb-2">RJ11 / RJ12 interface connected to thermal receipt printer.</p>
                  <ul class="small ps-3 text-secondary mb-0">
                    <li>Connect RJ11 drawer cable to printer kick-out port.</li>
                    <li>In printer driver properties, enable "Open cash drawer before/after print".</li>
                  </ul>
                </div>
              </div>
              <div class="col-md-6">
                <div class="p-3 border rounded-3 bg-light h-100">
                  <h6 class="fw-bold text-dark"><i class="bi bi-tags me-2 text-danger"></i> Barcode Label Printers</h6>
                  <p class="small text-secondary mb-2">Supported: Xprinter 365B, Zebra ZD220, TSC TTP-244 Pro.</p>
                  <ul class="small ps-3 text-secondary mb-0">
                    <li>Use standard 50x25mm or 38x28mm sticker rolls.</li>
                    <li>Print barcode labels directly from Inventory > Products > Print Barcodes.</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top">
            <button type="button" class="btn btn-secondary" @click="showHardwareGuide = false">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- SOP MANUAL MODAL -->
    <div v-if="showManualGuide" class="modal-backdrop fade show"></div>
    <div v-if="showManualGuide" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-bottom">
            <h5 class="modal-title fw-bold text-dark">
              <i class="bi bi-journal-text text-primary me-2"></i> Standard Daily Store SOPs (Opening to Closing)
            </h5>
            <button type="button" class="btn-close" @click="showManualGuide = false"></button>
          </div>
          <div class="modal-body p-4">
            <div class="d-flex flex-column gap-3">
              <div class="border rounded-3 p-3">
                <h6 class="fw-bold text-primary mb-1">1. Morning Opening Routine (08:30 AM)</h6>
                <ul class="small text-secondary mb-0 ps-3">
                  <li>Power on POS PC and thermal printer; check paper roll status.</li>
                  <li>Login to HBOS and check the <strong>Main Dashboard</strong> for low stock alerts.</li>
                  <li>Verify Opening Cash in Register drawer before ringing up the first customer.</li>
                </ul>
              </div>
              <div class="border rounded-3 p-3">
                <h6 class="fw-bold text-success mb-1">2. Day Shift Billing Operations</h6>
                <ul class="small text-secondary mb-0 ps-3">
                  <li>Scan products or search by SKU/name; verify displayed price matches shelf label.</li>
                  <li>For credit customers, always verify customer name and phone before selecting <strong>Khata / Credit</strong>.</li>
                  <li>Record petty cash expenses (tea, cleaning, delivery) immediately in <strong>Expenses</strong> so cash balances stay exact.</li>
                </ul>
              </div>
              <div class="border rounded-3 p-3">
                <h6 class="fw-bold text-danger mb-1">3. Night Closing Routine (10:00 PM)</h6>
                <ul class="small text-secondary mb-0 ps-3">
                  <li>Go to <strong>Sales History</strong> and filter by Today to see total Cash, Card, and Credit sales.</li>
                  <li>Count physical cash in the drawer and reconcile with (Opening Cash + Cash Sales - Cash Expenses).</li>
                  <li>Review <strong>Net Profit & Reports</strong> for daily performance review.</li>
                </ul>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top">
            <button type="button" class="btn btn-secondary" @click="showManualGuide = false">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- REPORT ISSUE MODAL -->
    <div v-if="showBugModal" class="modal-backdrop fade show"></div>
    <div v-if="showBugModal" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-bottom">
            <h5 class="modal-title fw-bold text-danger">
              <i class="bi bi-bug-fill me-2"></i> Report an Issue / Feedback
            </h5>
            <button type="button" class="btn-close" @click="showBugModal = false"></button>
          </div>
          <div class="modal-body p-4">
            <div v-if="bugSubmitted" class="alert alert-success d-flex align-items-center gap-2 mb-0">
              <i class="bi bi-check-circle-fill fs-4"></i>
              <div>
                <strong>Thank you!</strong> Your feedback has been recorded and submitted to our engineering team.
              </div>
            </div>
            <form v-else @submit.prevent="submitBugReport">
              <div class="mb-3">
                <label class="form-label small fw-bold">Affected Page / Feature</label>
                <select v-model="bugForm.module" class="form-select">
                  <option value="POS">Sales & POS</option>
                  <option value="Inventory">Inventory & Products</option>
                  <option value="Khata">Customer Khata</option>
                  <option value="Suppliers">Suppliers & Payables</option>
                  <option value="Reports">Reports & Analytics</option>
                  <option value="Printing">Receipt Printing</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">What happened?</label>
                <textarea v-model="bugForm.description" class="form-control" rows="3" required placeholder="Describe the steps you took and what error or unexpected behavior occurred..."></textarea>
              </div>
              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-light" @click="showBugModal = false">Cancel</button>
                <button type="submit" class="btn btn-danger fw-bold">Send Report</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// Active Tab state
const activeTab = ref('dashboard')
const searchQuery = ref('')
const openQuestions = ref(new Set([1])) // default first open
const allExpanded = ref(false)

// Modals
const showContactModal = ref(false)
const contactSubmitted = ref(false)
const contactForm = ref({ store: '', phone: '', category: 'POS & Printing', message: '' })

const showHardwareGuide = ref(false)
const showManualGuide = ref(false)
const showBugModal = ref(false)
const bugSubmitted = ref(false)
const bugForm = ref({ module: 'POS', description: '' })

// Module Tabs definition (exactly 9 tabs matching single store architecture)
const moduleTabs = [
  { id: 'dashboard', label: 'Main Dashboard', icon: 'bi-grid-1x2', description: 'Real-time sales KPI cards, daily register summary, and low stock warnings.' },
  { id: 'pos', label: 'Sales & POS', icon: 'bi-cart3', description: 'Barcode checkout, manual item search, split tenders, discounts, and receipt printing.' },
  { id: 'sales_history', label: 'Sales History', icon: 'bi-receipt', description: 'Past transaction lookup, reprinting receipts, processing returns, and voiding sales.' },
  { id: 'inventory', label: 'Inventory', icon: 'bi-boxes', description: 'Product catalog, barcode generation, stock adjustments, categories, and low stock alerts.' },
  { id: 'customers', label: 'Customers', icon: 'bi-people', description: 'Customer profiles, digital Khata credit ledgers, balance collection, and statements.' },
  { id: 'suppliers', label: 'Suppliers', icon: 'bi-truck', description: 'Distributor directory, purchase bills, payables tracking, and stock replenishment.' },
  { id: 'expenses', label: 'Expenses', icon: 'bi-wallet2', description: 'Store operating costs, utilities, wages, duration filters, and cash drawer impact.' },
  { id: 'reports', label: 'Reports', icon: 'bi-graph-up', description: 'Sales analytics, inventory valuation, customer aging, and Net Profit statement.' },
  { id: 'settings', label: 'Settings', icon: 'bi-gear', description: 'Store information, receipt logo display, thermal printer widths, staff roles, and audit trail.' }
]

const currentTabInfo = computed(() => {
  return moduleTabs.find(t => t.id === activeTab.value) || moduleTabs[0]
})

const selectTab = (tabId) => {
  activeTab.value = tabId
  // open first question of selected tab if not searching
  if (!searchQuery.value) {
    const firstOfTab = faqs.find(f => f.category === tabId)
    if (firstOfTab) {
      openQuestions.value = new Set([firstOfTab.id])
    }
  }
}

const getTabLabel = (categoryId) => {
  const tab = moduleTabs.find(t => t.id === categoryId)
  return tab ? tab.label : categoryId
}

const getTabQuestionCount = (tabId) => {
  return faqs.filter(f => f.category === tabId).length
}

const isQuestionOpen = (id) => {
  return openQuestions.value.has(id)
}

const toggleQuestion = (id) => {
  const next = new Set(openQuestions.value)
  if (next.has(id)) {
    next.delete(id)
  } else {
    next.add(id)
  }
  openQuestions.value = next
}

const toggleAllQuestions = () => {
  if (allExpanded.value) {
    openQuestions.value = new Set()
    allExpanded.value = false
  } else {
    openQuestions.value = new Set(filteredFaqs.value.map(f => f.id))
    allExpanded.value = true
  }
}

const formatAnswer = (text) => {
  return text.replace(/\n\n/g, '<br><br>').replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
}

// Filtered FAQs (Search across all tabs if search is active; otherwise filter by current active tab)
const filteredFaqs = computed(() => {
  let list = faqs
  if (searchQuery.value && searchQuery.value.trim() !== '') {
    const q = searchQuery.value.toLowerCase().trim()
    return list.filter(f => 
      f.question.toLowerCase().includes(q) || 
      f.answer.toLowerCase().includes(q) ||
      (f.tip && f.tip.toLowerCase().includes(q))
    )
  }
  return list.filter(f => f.category === activeTab.value)
})

const submitContactTicket = () => {
  contactSubmitted.value = true
  setTimeout(() => {
    showContactModal.value = false
    contactSubmitted.value = false
    contactForm.value = { store: '', phone: '', category: 'POS & Printing', message: '' }
  }, 2500)
}

const submitBugReport = () => {
  bugSubmitted.value = true
  setTimeout(() => {
    showBugModal.value = false
    bugSubmitted.value = false
    bugForm.value = { module: 'POS', description: '' }
  }, 2500)
}

// -------------------------------------------------------------------------
// 90 Comprehensive Store FAQs (10 Questions x 9 Modules)
// -------------------------------------------------------------------------
const faqs = [
  // ==========================================
  // TAB 1: MAIN DASHBOARD (10 Questions)
  // ==========================================
  {
    id: 1,
    category: 'dashboard',
    question: 'What is the Main Dashboard and what key metrics does it show?',
    answer: 'The Main Dashboard is your store\'s real-time flight deck. It provides an immediate executive snapshot of today\'s Gross Revenue, Total Completed Invoices, Estimated Net Profit, Low Stock items needing reorder, and Total Outstanding Customer Khata credit balance.',
    steps: [
      'Log into HBOS to land directly on the Main Dashboard.',
      'Review the top 4 KPI cards for today\'s volume and cash drawer collections.',
      'Check the Low Stock card to identify items running out on store shelves.'
    ],
    tip: 'Review your dashboard every morning to know which supplier orders to place and every evening to verify total cash collections.'
  },
  {
    id: 2,
    category: 'dashboard',
    question: 'How often are dashboard statistics updated?',
    answer: 'All dashboard statistics update in real-time. The moment a cashier completes a sale at the POS counter, an expense is recorded, or a purchase order is received, the dashboard counters, revenue charts, and cash balance refresh instantly without needing a manual browser reload.',
    steps: [
      'Perform a checkout in POS.',
      'Return to the Dashboard; notice Today\'s Sales and Order Count have incremented instantly.'
    ],
    tip: 'No manual refresh is necessary—HBOS maintains an active reactive state engine in memory.'
  },
  {
    id: 3,
    category: 'dashboard',
    question: 'What do the Quick Action buttons on the dashboard do?',
    answer: 'Quick Action buttons are one-click shortcuts designed to save time during busy retail hours. They allow you to jump straight into opening a New Sale (POS), Adding a New Product, Recording a Daily Expense, or Receiving a Customer Khata Payment without navigating through side menus.',
    steps: [
      'Look at the Quick Actions banner on the right side of the dashboard.',
      'Click "New POS Sale" (F2) or "Record Expense" to open the respective action immediately.'
    ],
    tip: 'Cashiers can use these shortcuts to handle high-frequency tasks in less than two seconds.'
  },
  {
    id: 4,
    category: 'dashboard',
    question: 'How do I interpret the Daily vs Monthly Sales graph?',
    answer: 'The interactive sales chart visualizes your revenue momentum over the current week or month. It plots gross sales against previous periods so you can quickly spot weekend surges, weekday dips, and peak business hours to optimize staff scheduling.',
    steps: [
      'Hover over any bar or node in the chart to inspect date and sales volume.',
      'Compare weekday peaks (e.g., Saturday grocery rush) with slower mid-week days.'
    ],
    tip: 'Schedule shelf-stocking and supplier deliveries on your store\'s slowest historical sales days.'
  },
  {
    id: 5,
    category: 'dashboard',
    question: 'Why is a product displayed in the "Low Stock Alerts" card?',
    answer: 'An item appears in Low Stock Alerts whenever its current physical inventory falls at or below its configured Minimum Stock Threshold. This ensures you never run out of fast-moving items or lose customer goodwill due to empty shelves.',
    steps: [
      'Click on any item in the Low Stock list to view its product card.',
      'Check remaining quantity versus reorder minimum.',
      'Click "Create Purchase Order" to replenish from the distributor.'
    ],
    tip: 'Set minimum stock levels higher for long-lead items (e.g. 10 days delivery) and lower for local daily bread or milk deliveries.'
  },
  {
    id: 6,
    category: 'dashboard',
    question: 'Can I view today\'s physical cash drawer balance from the dashboard?',
    answer: 'Yes! The Cash Flow & Register Summary card shows Opening Cash + Cash Collected from Sales - Cash Expenses Paid Out. This gives you the exact physical cash amount that should be inside the store cash drawer at any given moment.',
    steps: [
      'Look at the Cash Flow widget on the dashboard.',
      'Observe Net Cash In Drawer = (Total Cash Inflow) minus (Petty Cash Outflows).'
    ],
    tip: 'Count physical drawer cash before changing shifts to ensure zero discrepancy with this figure.'
  },
  {
    id: 7,
    category: 'dashboard',
    question: 'How do I change the dashboard period (Today, This Week, This Month)?',
    answer: 'At the top right corner of the dashboard, you will find the duration selector. Click the dropdown to switch between Today, Yesterday, This Week, This Month, This Quarter, or Year-to-Date to evaluate business growth over different time horizons.',
    steps: [
      'Click the date filter dropdown at the top right of the dashboard.',
      'Select "This Month" to analyze monthly turnover or "Today" for current register progress.'
    ],
    tip: 'Use "Yesterday" during morning meetings to review the previous day\'s full performance.'
  },
  {
    id: 8,
    category: 'dashboard',
    question: 'Why does Net Profit on the dashboard differ from Total Sales?',
    answer: 'Total Sales is your top-line Gross Revenue (every rupee collected from customers). Net Profit is your true bottom line calculated as: Gross Revenue minus Cost of Goods Sold (purchase cost from distributors) minus Operating Expenses (shop rent, electricity, staff wages, tea).',
    steps: [
      'Gross Sales = Total items sold at retail price.',
      'Cost of Goods (COGS) = What you paid distributors for those sold items.',
      'Operating Expenses = Daily store running bills.',
      'Net Profit = Gross Sales - COGS - Expenses.'
    ],
    tip: 'A store can have Rs. 500,000 in sales but if costs and expenses total Rs. 480,000, your real profit is Rs. 20,000. Keep expenses controlled!'
  },
  {
    id: 9,
    category: 'dashboard',
    question: 'Can cashiers and salespersons see store profit numbers on the dashboard?',
    answer: 'No. HBOS uses strict Role-Based Access Control (RBAC). When a cashier or salesperson logs in, sensitive profit margins, cost prices, and financial audit numbers are hidden or restricted. They only see terminal sales tools.',
    steps: [
      'Cashier login automatically lands directly on the POS Checkout screen.',
      'Executive dashboard and profit numbers are reserved strictly for Business Owners and Branch Managers.'
    ],
    tip: 'Assign staff members to the "Salesperson" role in Settings to prevent unauthorized visibility of margins.'
  },
  {
    id: 10,
    category: 'dashboard',
    question: 'What should a store manager check on the dashboard at opening and closing?',
    answer: 'At Morning Opening: Check Low Stock alerts, verify expected supplier deliveries, and confirm opening drawer balance. At Night Closing: Review total sales volume, verify cash drawer balance against physical cash, and check today\'s customer khata credits.',
    steps: [
      'Morning: Review Low Stock alerts & register opening balance.',
      'Evening: Check Total Sales, Cash In Drawer, and total customer credit issued.'
    ],
    tip: 'Establish this 5-minute morning and evening checklist as standard operating procedure for your shift managers.'
  },

  // ==========================================
  // TAB 2: SALES & POS (10 Questions)
  // ==========================================
  {
    id: 11,
    category: 'pos',
    question: 'How do I ring up a quick sale using a barcode scanner?',
    answer: 'HBOS POS is built for ultra-fast barcode scanning. Simply connect your USB or Bluetooth barcode scanner to your computer. When you scan an item\'s barcode, it is instantly recognized, added to the active cart with default quantity 1, and the total updates with audio feedback.',
    steps: [
      'Ensure the POS screen is open (press F2 or click Sales & POS).',
      'Point your barcode gun at the item barcode and pull the trigger.',
      'The item is added to the cart. Scan multiple times to increment quantity or press Enter to checkout.'
    ],
    tip: 'Ensure your scanner is configured to send a trailing "Enter" key suffix so items are added instantly.'
  },
  {
    id: 12,
    category: 'pos',
    question: 'How do I search for an item manually if the barcode is missing or torn?',
    answer: 'If an item has no barcode or the label is damaged, click into the search bar (or press `/` on your keyboard) and type the product name or SKU. A real-time dropdown shows matching items with price and available stock; click or press Enter to add.',
    steps: [
      'Press `/` on your keyboard to instantly focus the product search box.',
      'Type 2-3 letters of the item (e.g. "Dal" or "Olper").',
      'Use Up/Down arrow keys and hit Enter to add to cart.'
    ],
    tip: 'You can also browse products visually by clicking category tabs (Beverages, Spices, Dairy) on the POS screen.'
  },
  {
    id: 13,
    category: 'pos',
    question: 'How do I change item quantity or apply a line-item discount in the cart?',
    answer: 'In the cart table on the right side, each line item has `+` and `-` buttons as well as a direct numeric input for quantity. To apply a discount, click the discount icon on the item or the order-level discount button to choose either a percentage (%) or flat PKR discount.',
    steps: [
      'Click the `+` or `-` buttons to adjust quantity, or type directly into the quantity field.',
      'Click "Apply Discount" to grant a promotional discount.',
      'Verify the updated subtotal and tax calculation.'
    ],
    tip: 'Store owners can restrict cashier permissions so only managers can apply discounts greater than 10%.'
  },
  {
    id: 14,
    category: 'pos',
    question: 'How does selling on credit (Customer Khata) work at POS?',
    answer: 'To make a credit sale, select or search the customer\'s name in the Customer box at the top of the POS cart. At checkout, click the "Khata / Credit" payment button. The sale completes without cash collection, and the invoice amount is automatically added to the customer\'s ledger.',
    steps: [
      'Search and select the customer in the Customer dropdown (or click `+ New Customer`).',
      'Ring up the items as usual.',
      'In the payment modal, select "Khata / Credit" and click Complete Sale.',
      'Print the receipt showing the new outstanding balance on the customer\'s ledger.'
    ],
    tip: 'The customer\'s previous balance, today\'s bill, and total updated debt print automatically on the receipt.'
  },
  {
    id: 15,
    category: 'pos',
    question: 'Can I split payment between Cash, Card, and Credit on a single bill?',
    answer: 'Yes! When a customer wants to pay part in Cash and the remainder via Card or Khata credit, select the "Split Payment" option at checkout. Enter the exact amounts for each tender (e.g., Rs. 2,000 Cash + Rs. 1,500 Debit Card) until the total matches the bill.',
    steps: [
      'Click "Checkout" in POS.',
      'Select "Split Payment" from the payment method tabs.',
      'Enter Rs. 2,000 in Cash and Rs. 1,500 in Card/Bank.',
      'Click "Complete Sale" to finalize and print receipt.'
    ],
    tip: 'Split tenders ensure your bank card terminal reconciliation and cash drawer balances both stay 100% exact.'
  },
  {
    id: 16,
    category: 'pos',
    question: 'How do I hold a customer\'s cart if they forgot an item and resume it later?',
    answer: 'If a customer at the front of the queue needs to grab another item from the aisle, click the "Hold Cart" button. The cart is safely parked in memory with a timestamp, allowing you to serve the next customer. Tap "Held Carts" anytime to resume it.',
    steps: [
      'Click the "Hold Cart" button in the cart footer.',
      'Serve the next customer in line.',
      'When the customer returns, click "Held Orders" and tap "Resume" to restore all items.'
    ],
    tip: 'Holding carts prevents checkout queues from jamming during busy rush hours.'
  },
  {
    id: 17,
    category: 'pos',
    question: 'What thermal printer sizes are supported for receipts?',
    answer: 'HBOS supports standard 80mm (3-inch wide) and 58mm (2-inch compact) thermal POS receipt printers (ESC/POS compatible), as well as standard A4/A5 laser invoices for wholesale and corporate orders.',
    steps: [
      'Go to Settings > Receipts & Invoices.',
      'Select your paper format: 80mm Thermal, 58mm Thermal, or A4 Full Page.',
      'Perform a test print to verify perfect alignment.'
    ],
    tip: '80mm thermal receipts are the global retail standard, offering clear product descriptions and barcode footers.'
  },
  {
    id: 18,
    category: 'pos',
    question: 'How do I configure the POS to automatically print receipts after checkout?',
    answer: 'Under Settings > Receipts & Invoices, switch on the "Auto-Print on Checkout" toggle. Once enabled, the moment you press Enter or click Complete Sale, the system automatically triggers the silent receipt print without requiring an extra dialog.',
    steps: [
      'Open Settings from the main sidebar.',
      'Select the "Receipts & Invoices" tab.',
      'Toggle ON "Auto-Print Receipts on Sale Completion".',
      'Click Save Changes.'
    ],
    tip: 'For fast-paced counters, auto-print cuts checkout time by up to 5 seconds per customer.'
  },
  {
    id: 19,
    category: 'pos',
    question: 'Can a cashier override a product selling price directly at POS?',
    answer: 'Price overrides can be enabled or locked in Settings > Staff & Roles. If allowed, a cashier can click on the item price in the cart to enter an agreed-upon negotiated price. If locked, a manager PIN is required to authorize the change.',
    steps: [
      'Click on the unit price inside the cart line item.',
      'If permitted, enter the negotiated price and press Enter.',
      'The change is permanently logged in the Audit Trail with user ID.'
    ],
    tip: 'Lock price override for general cashiers to prevent unauthorized discounts or fraud.'
  },
  {
    id: 20,
    category: 'pos',
    question: 'What happens if the internet goes down while billing customers?',
    answer: 'HBOS features an offline-first architecture. If your internet connection drops, the POS terminal continues ringing up sales, scanning barcodes, and printing receipts using local browser database storage. When the connection resumes, all sales automatically sync to the server.',
    steps: [
      'Notice the green "Online" badge in the top bar turns yellow/offline.',
      'Continue scanning and printing receipts normally.',
      'Once internet reconnects, HBOS background sync uploads all pending transactions.'
    ],
    tip: 'Your retail counter never stops running even during internet blackouts or network lag.'
  },

  // ==========================================
  // TAB 3: SALES HISTORY (10 Questions)
  // ==========================================
  {
    id: 21,
    category: 'sales_history',
    question: 'Where do I find previously completed sales receipts?',
    answer: 'Click on "Sales History" in the main navigation sidebar. Every sale completed across your counters is listed in reverse chronological order with Invoice Number, Date/Time, Customer Name, Payment Method, Cashier Name, and Total Amount.',
    steps: [
      'Click "Sales History" from the sidebar.',
      'Browse the table or use the search bar to locate any previous sale by Invoice ID or customer name.'
    ],
    tip: 'Sales History is the master register for all audit and customer dispute resolutions.'
  },
  {
    id: 22,
    category: 'sales_history',
    question: 'How do I reprint a lost receipt for a customer?',
    answer: 'In the Sales History list, locate the transaction using the customer phone number or invoice number. Click the Action menu (three dots or eye icon) on the right of the row and select "Reprint Receipt". The receipt preview opens for instant printing.',
    steps: [
      'Search for the Invoice # (e.g. INV-1042) in Sales History.',
      'Click the "Print" icon on the row.',
      'A duplicate receipt prints immediately with a "DUPLICATE COPY" label.'
    ],
    tip: 'Always provide customers with duplicate receipts if they need proof for tax or company reimbursement.'
  },
  {
    id: 23,
    category: 'sales_history',
    question: 'How do I process a customer item return or exchange?',
    answer: 'Locate the original sale in Sales History and click "Process Return". A return window opens listing all items on that invoice. Choose the specific items and quantities being returned, select the reason (Defective, Wrong Item, Customer Choice), and refund via Cash or Customer Khata Credit.',
    steps: [
      'Open Sales History and find the original invoice.',
      'Click "Return Items" in the action menu.',
      'Select item quantities to return (e.g. 1 out of 2 bottles).',
      'Select refund destination: Cash Refund or Add to Khata Credit.',
      'Click Confirm Return; returned stock is automatically restored to inventory.'
    ],
    tip: 'HBOS automatically adds returned items back into active stock so your inventory count stays true.'
  },
  {
    id: 24,
    category: 'sales_history',
    question: 'How do I void or cancel an incorrectly created sale?',
    answer: 'Voiding an entire sale is restricted to Business Owners and Branch Managers. Click "Void Sale" on the invoice row, provide a mandatory reason for cancellation (e.g., Customer had insufficient funds), and submit. Stock is replenished and an audit log entry is permanently recorded.',
    steps: [
      'Locate the mistaken invoice in Sales History.',
      'Click "Void / Cancel Sale" (Manager access required).',
      'Enter the reason for cancellation in the prompt.',
      'Confirm; the invoice status updates to "Voided" and financial totals are reversed.'
    ],
    tip: 'Every voided sale is tracked in Settings > Audit & Governance to prevent cashier skimming.'
  },
  {
    id: 25,
    category: 'sales_history',
    question: 'How can I filter sales history by Cashier / Salesperson?',
    answer: 'At the top of the Sales History page, click the "Cashier" filter dropdown. Select the specific staff member\'s name to view only transactions completed under their login. This is ideal for verifying individual shift sales and calculating commissions.',
    steps: [
      'Click the "Cashier" filter at the top of the table.',
      'Select the staff member (e.g. "Bilal Ahmed - Counter 1").',
      'The list and total summary update to reflect only their sales.'
    ],
    tip: 'Use this at the end of each shift to reconcile the cash collected by that specific cashier.'
  },
  {
    id: 26,
    category: 'sales_history',
    question: 'Can I filter sales by payment method (Cash vs Card vs Khata)?',
    answer: 'Yes! Use the Payment Method filter dropdown at the top of the page. Select "Card" to compare your day\'s bank card swipe slips with software records, or select "Khata" to see all credit sales issued today.',
    steps: [
      'Click the "Payment Method" filter.',
      'Select "Card" or "Khata / Credit".',
      'Verify the total sum matches your bank terminal batch settlement report.'
    ],
    tip: 'Perform card reconciliation daily before closing to quickly catch any dropped card transactions.'
  },
  {
    id: 27,
    category: 'sales_history',
    question: 'How do I download or export my sales history to Excel or CSV?',
    answer: 'Click the "Export" button in the upper right toolbar of the Sales History screen. Choose "Export as CSV" or "Excel". The generated spreadsheet includes full details: date, invoice number, customer name, items, taxes, discounts, payment mode, and net total.',
    steps: [
      'Apply any desired date or status filters first.',
      'Click the "Export" button at the top right.',
      'Open the downloaded `.csv` file in Microsoft Excel or Google Sheets for bookkeeping.'
    ],
    tip: 'Send this monthly sales export file directly to your tax consultant or accounting firm.'
  },
  {
    id: 28,
    category: 'sales_history',
    question: 'What do the different status badges mean in Sales History?',
    answer: 'Sales History uses color-coded badges: "Completed" (Green) means sale was paid and goods delivered; "Returned" (Orange) indicates full or partial return; "Voided" (Red) means cancelled transaction; "On Hold" (Blue) denotes parked carts.',
    steps: [
      'Green (Completed): Successfully finalized.',
      'Orange (Returned): Goods returned and refunded.',
      'Red (Voided): Cancelled by manager; stock restored.',
      'Blue (Held): Parked transaction awaiting resumption.'
    ],
    tip: 'Sort by status to quickly audit all returned or cancelled sales for the week.'
  },
  {
    id: 29,
    category: 'sales_history',
    question: 'How do I view all historical purchases made by a specific customer?',
    answer: 'Type the customer\'s name or phone number into the search bar at the top of Sales History. The table filters instantly to show every invoice ever billed to that customer across your store\'s entire operating history.',
    steps: [
      'Type the customer\'s mobile number (e.g. 03001234567) into the search bar.',
      'All past invoices for this customer appear with dates and totals.',
      'Click any invoice row to view the detailed items list.'
    ],
    tip: 'This makes warranty validation or customer receipt inquiries fast and hassle-free.'
  },
  {
    id: 30,
    category: 'sales_history',
    question: 'How do I verify a cashier\'s shift collections before closing the drawer?',
    answer: 'Filter Sales History by today\'s date and the cashier\'s name. Sum the "Cash" payment column. Compare this software total against the physical cash bills and coins counted in the register drawer to verify zero shortage.',
    steps: [
      'Select Today\'s date filter.',
      'Filter by Cashier Name.',
      'Check Cash Inflow total.',
      'Count physical drawer cash: Physical Cash = (Opening Float + Cash Sales - Recorded Cash Expenses).'
    ],
    tip: 'Require both cashier and manager to sign the end-of-shift register settlement sheet.'
  },

  // ==========================================
  // TAB 4: INVENTORY (10 Questions)
  // ==========================================
  {
    id: 31,
    category: 'inventory',
    question: 'How do I add a new product with barcode, cost, and selling price?',
    answer: 'Navigate to Inventory > Products and click the "+ Add Product" button. Fill in the Barcode (scan or auto-generate), Product Name, Category, Brand, Purchase Cost (what you pay), and Retail Selling Price (what customers pay), then save.',
    steps: [
      'Go to Inventory and click "+ Add Product".',
      'Scan the product barcode with your scanner or click "Generate Barcode".',
      'Enter Product Title, Category, and Brand.',
      'Input Purchase Price (e.g. 150 PKR) and Selling Price (e.g. 200 PKR).',
      'Set initial stock quantity and click "Save Product".'
    ],
    tip: 'Always enter your actual distributor purchase cost so HBOS can calculate true gross margins and net profit.'
  },
  {
    id: 32,
    category: 'inventory',
    question: 'Can HBOS auto-generate barcodes for items that do not have manufacturer barcodes?',
    answer: 'Yes! For loose items, locally packed spices, fruits, or unbranded merchandise, click the "Generate Barcode" button in the Add Product modal. HBOS creates a unique 12 or 13-digit EAN/Code-128 barcode ready for sticker printing.',
    steps: [
      'Open the Add Product modal.',
      'Click the "Generate Barcode" magic wand icon next to the barcode field.',
      'A unique internal barcode is generated automatically.'
    ],
    tip: 'Prefix internal barcodes with "20" or "99" so your staff can immediately recognize internal store labels.'
  },
  {
    id: 33,
    category: 'inventory',
    question: 'How do I print barcode sticker labels for store shelves or packaging?',
    answer: 'In Inventory > Products, locate the item and click the barcode icon or "Print Barcodes". Choose the number of sticker copies to print (e.g. 50 labels) and select your label sheet size (e.g. 50x25mm). Click Print to send to your barcode label printer.',
    steps: [
      'Click the "Print Barcodes" button on any product row.',
      'Enter the quantity of sticker labels needed.',
      'Select your label printer (e.g. Xprinter 365B or Zebra).',
      'Click Print Labels; peel and paste onto your products.'
    ],
    tip: 'Barcoded products speed up checkout by over 400% compared to manual name typing.'
  },
  {
    id: 34,
    category: 'inventory',
    question: 'What is the difference between Purchase Price and Retail Sale Price?',
    answer: 'Purchase Price (Cost) is what you pay the distributor or wholesaler per unit. Selling Price (Retail) is what you charge the customer at the counter. The difference between the two is your Gross Profit Margin.',
    steps: [
      'Purchase Price = Distributor Cost (used for inventory valuation & profit calculation).',
      'Selling Price = Customer Shelf Price (used at POS checkout).',
      'Gross Margin % = ((Selling Price - Purchase Price) / Selling Price) * 100.'
    ],
    tip: 'Cashiers never see the purchase price at POS, protecting your supplier margins from customer visibility.'
  },
  {
    id: 35,
    category: 'inventory',
    question: 'How do I adjust stock when goods are damaged, expired, or recounted?',
    answer: 'Go to Inventory > Products, click "Adjust Stock" on the target item. Select the reason for adjustment: Physical Recount, Damaged Goods, Expired, or Internal Loss. Enter the quantity adjustment (+ or -) and submit. Stock updates instantly with full audit provenance.',
    steps: [
      'Find the product in Inventory.',
      'Click "Stock Adjustment".',
      'Choose Reason: Damaged, Expired, or Physical Audit Count.',
      'Enter new counted stock or delta (e.g. -2 for broken bottles).',
      'Save; stock balance and valuation update immediately.'
    ],
    tip: 'Perform monthly cycle counts on high-value departments (cosmetics, infant milk, cooking oil) to curb shrinkage.'
  },
  {
    id: 36,
    category: 'inventory',
    question: 'How do I configure Low Stock Warning thresholds?',
    answer: 'Edit the product and enter a number in the "Minimum Stock Alert" field (e.g. 10 units). Whenever remaining stock drops to or below 10, the item highlights in yellow/red on the inventory table and surfaces on the Main Dashboard Low Stock card.',
    steps: [
      'Edit the product in Inventory.',
      'Locate the "Min Stock Threshold / Reorder Level" field.',
      'Enter your desired threshold (e.g. 12 units).',
      'Save changes.'
    ],
    tip: 'Set higher thresholds for fast-moving goods so you have adequate lead time before suppliers deliver.'
  },
  {
    id: 37,
    category: 'inventory',
    question: 'How do Categories and Brands help organize store inventory?',
    answer: 'Categories (Beverages, Confectionery, Spices, Dairy) organize your catalog into departments for clean POS touch navigation and department sales reports. Brands (Nestlé, Shan, Dalda, Tapal) allow tracking supplier lines and distributor rebates.',
    steps: [
      'Go to Inventory > Categories to create departments.',
      'Go to Inventory > Brands to register manufacturers.',
      'Assign each product its appropriate category and brand during creation.'
    ],
    tip: 'Department-level reporting shows you which store aisles generate the highest return per square foot.'
  },
  {
    id: 38,
    category: 'inventory',
    question: 'Can I bulk import products from an Excel or CSV file?',
    answer: 'Yes! Click "Import CSV" at the top of the Inventory page. Download the provided sample CSV template, paste your product catalog (Barcode, Name, Category, Cost, Price, Stock), and upload. Hundreds or thousands of items are imported in seconds.',
    steps: [
      'Click "Import CSV" at the top right of Inventory.',
      'Click "Download Sample Template".',
      'Fill in your product list in Microsoft Excel.',
      'Upload the file and click "Start Import".'
    ],
    tip: 'Use bulk CSV import when first setting up your store to save days of manual data entry.'
  },
  {
    id: 39,
    category: 'inventory',
    question: 'How does inventory automatically update during sales and purchases?',
    answer: 'HBOS uses automated perpetual inventory tracking. When a sale is completed at POS, the quantity sold is immediately deducted from inventory. When a Supplier Purchase Bill is received, the stock quantity is automatically incremented with zero manual adjustments.',
    steps: [
      'POS Sale of 2 units -> Stock decrements by 2.',
      'Supplier Purchase of 50 units -> Stock increments by 50.',
      'Customer Return of 1 unit -> Stock increments by 1.'
    ],
    tip: 'Never manually edit stock quantities for normal sales or purchases—let the automated workflow maintain the ledger.'
  },
  {
    id: 40,
    category: 'inventory',
    question: 'What is Total Inventory Valuation and where is it calculated?',
    answer: 'Total Inventory Valuation is the monetary capital currently invested in your store stock. It is calculated as: Sum of (Current Quantity on Hand × Purchase Cost per unit). You can see this live figure in the Inventory summary banner and in the Reports section.',
    steps: [
      'View the summary cards at the top of the Inventory page.',
      'Inspect "Total Inventory Value (Cost)" and "Potential Retail Value".',
      'The difference represents your future unrealized gross profit.'
    ],
    tip: 'Monitor inventory valuation monthly to prevent over-purchasing slow stock that ties up your working capital.'
  },

  // ==========================================
  // TAB 5: CUSTOMERS (10 Questions)
  // ==========================================
  {
    id: 41,
    category: 'customers',
    question: 'How do I register a new customer in the system?',
    answer: 'Go to Customers from the main sidebar and click "+ Add Customer". Enter Customer Full Name, Mobile Phone Number, Address/City, and an optional Opening Khata Balance (if they already owe you money from before using the software).',
    steps: [
      'Click Customers & Khata in the sidebar.',
      'Click "+ Add Customer".',
      'Fill in Name and Phone Number (mandatory for Khata statements).',
      'Set an optional Credit Limit and Opening Balance, then save.'
    ],
    tip: 'Always record customer mobile numbers so you can send automated WhatsApp balance reminders.'
  },
  {
    id: 42,
    category: 'customers',
    question: 'What is Customer Khata and how does the digital ledger work?',
    answer: 'Customer Khata is an automated digital credit ledger replacing manual paper khata books. When a trusted customer buys on credit, their debt balance increases. When they hand you cash or transfer money to pay off their balance, their debt decreases.',
    steps: [
      'Credit Sale in POS -> Customer Khata balance increases (Debit).',
      'Customer pays cash -> Customer Khata balance decreases (Credit).',
      'Running Balance = Exactly what the customer currently owes your store.'
    ],
    tip: 'Eliminate lost credit notes and ledger disputes forever with 100% digital time-stamped khata entries.'
  },
  {
    id: 43,
    category: 'customers',
    question: 'How do I record a payment received from a credit customer?',
    answer: 'Open the customer\'s profile in Customers & Khata, click "Receive Payment", enter the amount paid (e.g. Rs. 5,000), choose payment channel (Cash in Hand or Bank Transfer), and submit. The ledger balance updates immediately and you can print a payment receipt.',
    steps: [
      'Search customer in Customers & Khata and click their profile.',
      'Click "Receive Payment".',
      'Enter Received Amount (e.g. 5,000 PKR).',
      'Select payment method (Cash or Bank Transfer) and save.',
      'Hand or send the customer a signed payment acknowledgment receipt.'
    ],
    tip: 'You can also receive customer khata payments directly from the POS screen during checkout!'
  },
  {
    id: 44,
    category: 'customers',
    question: 'Can I set a maximum credit limit for specific customers?',
    answer: 'Yes! When creating or editing a customer, enter a maximum amount in the "Credit Limit" field (e.g. Rs. 25,000). If a cashier tries to ring up a credit sale that would push the customer\'s balance over this limit, the POS displays a warning and blocks checkout unless approved by a manager.',
    steps: [
      'Edit the customer profile in Customers.',
      'Set "Credit Limit" to your agreed amount (e.g. 25,000 PKR).',
      'Save profile.'
    ],
    tip: 'Credit limits prevent customers from accumulating unmanageable credit balances that risk default.'
  },
  {
    id: 45,
    category: 'customers',
    question: 'How do I send a Khata balance reminder via WhatsApp or SMS?',
    answer: 'On the customer\'s ledger statement, click the "Send WhatsApp Reminder" button. HBOS automatically formats a professional message with your store name, invoice references, total balance due, and payment instructions ready to send with 1 click.',
    steps: [
      'Open the customer\'s profile.',
      'Click the "WhatsApp Statement" button.',
      'WhatsApp opens with a pre-filled, polite balance reminder text.',
      'Click Send.'
    ],
    tip: 'Sending regular monthly WhatsApp reminders speeds up customer credit collection by up to 50%.'
  },
  {
    id: 46,
    category: 'customers',
    question: 'Can I print an official Account Statement (Ledger) for a customer?',
    answer: 'Yes. In the customer profile, click "Print Ledger" or "Download PDF". HBOS generates a clean, formatted statement showing Opening Balance, Date, Invoice Numbers, Purchases, Payments Received, and Running Balance.',
    steps: [
      'Open the customer profile.',
      'Select the desired date range (e.g., Last 30 Days or All Time).',
      'Click "Print Statement" to print on 80mm thermal paper or standard A4 sheet.'
    ],
    tip: 'Provide printed statements to wholesale customers at month-end for fast invoice approval.'
  },
  {
    id: 47,
    category: 'customers',
    question: 'What is an "Opening Balance" when adding an existing customer?',
    answer: 'If you are migrating from a paper ledger book or another software and a customer already owes you money, enter that unpaid amount in "Opening Balance". This ensures their balance starts accurately in HBOS without needing to re-enter old paper bills.',
    steps: [
      'Add or edit customer profile.',
      'Enter the historical unpaid amount in "Opening Balance" (e.g. 14,500 PKR).',
      'Save; the customer\'s starting ledger begins at 14,500 PKR.'
    ],
    tip: 'Double-check paper ledger totals before entering opening balances to prevent customer disputes.'
  },
  {
    id: 48,
    category: 'customers',
    question: 'How do I quickly find a customer during POS checkout?',
    answer: 'In the POS checkout terminal, click the Customer field and type the first 3 letters of their name or the last 4 digits of their phone number. A quick predictive list displays matching profiles; tap or press Enter to select them.',
    steps: [
      'Focus the Customer search field in POS.',
      'Type phone number or customer name.',
      'Select the customer from the autocomplete dropdown.'
    ],
    tip: 'Searching by mobile phone number is faster and eliminates confusion between customers with common names.'
  },
  {
    id: 49,
    category: 'customers',
    question: 'Can I group customers (e.g. Retail, Wholesale, VIP)?',
    answer: 'Yes! Customers can be assigned to customer categories such as Retail, Wholesale, or Staff. You can configure special pricing tiers or discounts that apply automatically whenever a member of that customer group is selected at checkout.',
    steps: [
      'Go to Customers and edit the customer.',
      'Select Customer Type: Retail or Wholesale.',
      'Wholesale customers automatically receive tier-discounted rates at POS.'
    ],
    tip: 'Use wholesale customer groups to offer distributor pricing to bulk buyers without manual discounting.'
  },
  {
    id: 50,
    category: 'customers',
    question: 'How do I view customers with the highest overdue balances?',
    answer: 'In the Customers table, click the "Balance Due" column header to sort descending. The customers who owe your store the highest amounts float to the top of the list for prioritized credit recovery and payment follow-ups.',
    steps: [
      'Go to Customers & Khata.',
      'Sort by "Outstanding Balance (Highest First)".',
      'Identify top debtors and schedule reminder calls or WhatsApp statements.'
    ],
    tip: 'Review top debtors every Monday morning to maintain healthy cash flow for your business.'
  },

  // ==========================================
  // TAB 6: SUPPLIERS (10 Questions)
  // ==========================================
  {
    id: 51,
    category: 'suppliers',
    question: 'How do I add a new distributor or supplier?',
    answer: 'Go to Suppliers in the main sidebar and click "+ Add Supplier". Enter Company/Distributor Name (e.g. Tapal Tea Distributors), Contact Person, Phone Number, Email, Address, and agreed payment terms (e.g. Net 15 days credit).',
    steps: [
      'Navigate to Suppliers & Payables.',
      'Click "+ Add Supplier".',
      'Enter Vendor Name, Sales Rep Contact, and Phone.',
      'Save supplier profile.'
    ],
    tip: 'Save the distributor sales representative\'s direct phone number for easy order placement.'
  },
  {
    id: 52,
    category: 'suppliers',
    question: 'How do I record a new purchase bill / invoice from a distributor?',
    answer: 'Click "New Purchase" or "Record Bill" in Suppliers. Select the vendor, enter their invoice/bilty number, add the items received with quantities and purchase costs, and submit. Your inventory counts increment automatically and the bill is added to Supplier Payables.',
    steps: [
      'Click "New Purchase" in Suppliers.',
      'Select Supplier and enter distributor invoice number.',
      'Scan or select received products and input received quantities and unit costs.',
      'Click "Submit Purchase Bill".',
      'Stock increments immediately and accounts payable balance is updated.'
    ],
    tip: 'Always match received quantities and rates with the distributor\'s physical delivery challan before submitting.'
  },
  {
    id: 53,
    category: 'suppliers',
    question: 'How does Supplier Payable tracking work?',
    answer: 'When a supplier delivers goods on credit, the purchase bill amount is credited to their payable account. HBOS keeps an exact running ledger of what you owe each supplier, which bills are paid, and which bills are nearing their due date.',
    steps: [
      'Purchase on credit -> Supplier Payable balance increases.',
      'Payment made to supplier -> Supplier Payable balance decreases.',
      'Running Balance = Total unpaid dues owed to that distributor.'
    ],
    tip: 'Supplier payables tracking prevents double-payments and ensures you never miss a distributor discount.'
  },
  {
    id: 54,
    category: 'suppliers',
    question: 'How do I record a payment made to a supplier?',
    answer: 'Open the supplier\'s profile, click "Make Payment", enter the payment amount (e.g. Rs. 40,000), choose payment source (Cash Drawer, Meezan Bank, or Cheque), enter reference number, and submit. The supplier\'s payable balance decreases immediately.',
    steps: [
      'Open the Supplier profile.',
      'Click "Make Payment".',
      'Enter Amount Paid and select payment method (Cash or Bank).',
      'Enter Cheque/Transaction reference and save.',
      'Print or archive payment voucher.'
    ],
    tip: 'Paying via Bank Transfer creates a verifiable digital audit trail for tax and accounting records.'
  },
  {
    id: 55,
    category: 'suppliers',
    question: 'How do I handle damaged goods returned back to a distributor?',
    answer: 'In the supplier profile or purchase bill, click "Debit Note / Purchase Return". Select the damaged or expired items and quantities to send back. HBOS reduces your inventory count and deducts the value from what you owe the supplier.',
    steps: [
      'Open the supplier\'s profile and click "Purchase Return".',
      'Select the returned items and quantities.',
      'Enter reason (Damaged in transit, Near expiry).',
      'Submit Debit Note; supplier payable is reduced by the returned amount.'
    ],
    tip: 'Always hand the delivery driver a printed Debit Note to get their signature when handing back damaged stock.'
  },
  {
    id: 56,
    category: 'suppliers',
    question: 'Can I track supplier payment due dates and credit terms?',
    answer: 'Yes! Every purchase bill records an agreed Due Date (e.g. 15 or 30 days). The Suppliers dashboard highlights upcoming bills in yellow and overdue bills in red, so you can plan cash flow and avoid supplier supply stops.',
    steps: [
      'View the "Aging & Due Dates" card on the Suppliers page.',
      'Identify bills maturing this week.',
      'Ensure sufficient cash or bank balance is allocated.'
    ],
    tip: 'Paying distributors on time builds strong credit relationships and secures better volume discounts.'
  },
  {
    id: 57,
    category: 'suppliers',
    question: 'How do I view the complete purchase and payment statement of a supplier?',
    answer: 'Click "View Profile" on any supplier row. The statement displays every purchase bill, payment voucher, debit note, and running balance in clear date order, ready to print or export to Excel.',
    steps: [
      'Click "View Profile" on the supplier row.',
      'Review the full ledger history.',
      'Click "Print Statement" or "Export CSV" to reconcile accounts.'
    ],
    tip: 'Use this statement whenever reconciling monthly statements sent by distributor accounts departments.'
  },
  {
    id: 58,
    category: 'suppliers',
    question: 'Can I reorder low-stock products directly from their supplier?',
    answer: 'Yes! Under Reports > Inventory Report or Reorder List, filter items by supplier. HBOS calculates suggested reorder quantities based on low stock thresholds and sales velocity, allowing you to generate a draft purchase order.',
    steps: [
      'Open the Reorder List filtered by Supplier.',
      'Review recommended quantities.',
      'Export or share the purchase list directly with the sales rep.'
    ],
    tip: 'Prevent stock-outs by sending your distributor order list 2 days before your weekly delivery day.'
  },
  {
    id: 59,
    category: 'suppliers',
    question: 'What happens if a distributor raises wholesale prices?',
    answer: 'When you record a new purchase bill with higher purchase prices, HBOS automatically updates the latest cost and recalculates the weighted average unit cost. You can also prompt the system to adjust retail selling prices to maintain margins.',
    steps: [
      'Enter the new unit cost on the purchase bill.',
      'Submit the bill; inventory valuation updates.',
      'If retail price needs updating, edit the product in Inventory and print new shelf labels.'
    ],
    tip: 'Whenever distributor costs rise, update shelf prices immediately to avoid margin erosion.'
  },
  {
    id: 60,
    category: 'suppliers',
    question: 'How do I reconcile supplier accounts with distributor invoices?',
    answer: 'Compare the distributor\'s monthly statement against your HBOS supplier ledger. Match each delivery challan / bill number and payment voucher. Any discrepancies (such as uncredited returns or missed discounts) stand out clearly.',
    steps: [
      'Download supplier ledger from HBOS.',
      'Compare line-by-line with distributor statement.',
      'Flag any missing debit notes or unrecorded cash payments.'
    ],
    tip: 'Reconcile high-volume distributors (e.g. beverages, cooking oil) at least twice a month.'
  },

  // ==========================================
  // TAB 7: EXPENSES (10 Questions)
  // ==========================================
  {
    id: 61,
    category: 'expenses',
    question: 'What types of business expenses should be recorded in this module?',
    answer: 'All operational and overhead store costs outside of merchandise purchases should be recorded here: Electricity & Utility Bills, Shop Rent, Staff Salaries & Daily Wages, Tea & Refreshments, Packaging bags, Generator fuel, Maintenance, and municipal taxes.',
    steps: [
      'Go to Expenses from the sidebar.',
      'Click "+ Add Expense".',
      'Select Category (Rent, Utilities, Tea/Entertainment, Salaries, Supplies).',
      'Enter Amount, Payment Method, and Description, then save.'
    ],
    tip: 'Recording even small expenses like Rs. 150 tea ensures your financial reports and cash drawer match 100%.'
  },
  {
    id: 62,
    category: 'expenses',
    question: 'How do I record daily petty cash expenses (e.g. tea, cleaning supplies)?',
    answer: 'Click "+ Add Expense", choose category "Tea & Refreshment" or "Store Supplies", enter the exact amount (e.g. Rs. 350), select payment method "Cash in Register", and type a short note. The amount is immediately deducted from the cash drawer balance.',
    steps: [
      'Click "+ Add Expense".',
      'Select "Cash from Register" as payment method.',
      'Enter Rs. 350 and description: "Morning staff tea & biscuits".',
      'Save; cash drawer balance is updated instantly.'
    ],
    tip: 'Always require staff to submit paper slips or vendor receipts for petty cash outlays.'
  },
  {
    id: 63,
    category: 'expenses',
    question: 'How do expense categories help in managing store finances?',
    answer: 'Categories organize your spending into clear buckets. At the end of the month, you can immediately see whether electricity, salaries, or miscellaneous petty cash are consuming too much revenue, allowing you to cut unnecessary costs.',
    steps: [
      'View the Expenses summary chart.',
      'Inspect category breakdown percentages (e.g. Rent 45%, Utilities 25%, Staff 20%, Supplies 10%).',
      'Set budgets for discretionary categories like entertainment or supplies.'
    ],
    tip: 'Review high-percentage expense categories quarterly to renegotiate rates or reduce waste.'
  },
  {
    id: 64,
    category: 'expenses',
    question: 'How do I filter expenses by duration (Today, This Month, Custom Range)?',
    answer: 'Use the Duration filter bar at the top of the Expenses screen. Click any pill: Today, Yesterday, This Week, This Month, Last Month, This Quarter, This Year, or select a Custom Date Range to view exact spending for that period.',
    steps: [
      'Click "This Month" on the duration filter bar.',
      'The table and summary badge update to show total monthly expenses.',
      'Select "Today" to see expenses paid out during the current shift.'
    ],
    tip: 'Use the "Today" filter during day-end shift closeout to see all cash paid out from the register.'
  },
  {
    id: 65,
    category: 'expenses',
    question: 'Does recording a cash expense affect the physical cash drawer balance?',
    answer: 'Yes! When you select "Cash in Register" as the payment method, HBOS automatically deducts that amount from today\'s physical register drawer calculation. This ensures your counted cash matches the software balance at night.',
    steps: [
      'Opening Cash: Rs. 10,000.',
      'Cash Sales: +Rs. 50,000.',
      'Recorded Cash Expense: -Rs. 1,500.',
      'Expected Closing Cash: Rs. 58,500.'
    ],
    tip: 'If an expense was paid from a personal owner bank account rather than the store drawer, choose "Owner Personal / Bank" so drawer cash is not reduced.'
  },
  {
    id: 66,
    category: 'expenses',
    question: 'Can I attach invoice photos or paper receipts to an expense?',
    answer: 'Yes! In the Add Expense modal, click "Attach Receipt" to upload a photo of the bill, paper receipt, or utility voucher. Stored digitally, you can view the receipt anytime for audit and tax proof.',
    steps: [
      'Take a photo of the electricity or repair bill with your phone or webcam.',
      'Upload the file in the expense modal.',
      'Save; click the paperclip icon in the table anytime to view the image.'
    ],
    tip: 'Digital receipt attachments prevent lost paper slips and speed up annual tax filings.'
  },
  {
    id: 67,
    category: 'expenses',
    question: 'How do expenses impact the store\'s Net Profit calculation?',
    answer: 'Net Profit is calculated as: Gross Margin (Sales Revenue - Cost of Goods Sold) MINUS Operating Expenses. Every recorded expense reduces net profit to show you the real net take-home earnings of your business.',
    steps: [
      'Gross Profit: Rs. 100,000.',
      'Total Expenses: Rs. 40,000.',
      'Net Profit: Rs. 60,000.'
    ],
    tip: 'Without recording expenses, your profit figures will be artificially inflated and misleading.'
  },
  {
    id: 68,
    category: 'expenses',
    question: 'Can cashiers add expenses or is it restricted to managers?',
    answer: 'Store Owners can configure permissions in Settings > Staff & Roles. By default, cashiers can be allowed to record small daily petty cash expenses (under Rs. 1,000) while larger expenses and historical audit views are reserved for managers.',
    steps: [
      'Configure role permissions in Settings > Staff & Roles.',
      'Cashiers see only a simple "Record Petty Cash" popup.',
      'Managers and Owners have full access to view, edit, and export all expenses.'
    ],
    tip: 'Empowering cashiers to record small tea/packaging expenses ensures no cash outflows go unrecorded.'
  },
  {
    id: 69,
    category: 'expenses',
    question: 'How do I record monthly recurring expenses like Store Rent or Staff Salaries?',
    answer: 'When paying rent or monthly wages, add an expense under category "Rent" or "Salaries & Wages". Choose payment method (Bank Transfer or Cash), enter recipient name, and record the payment date.',
    steps: [
      'Click "+ Add Expense".',
      'Select Category: "Staff Salaries & Wages" or "Store Rent".',
      'Enter Amount (e.g. 85,000 PKR).',
      'Select Payment Method: Bank Transfer (Meezan Bank).',
      'Save expense.'
    ],
    tip: 'Add employee names in the description to track individual salary disbursements across months.'
  },
  {
    id: 70,
    category: 'expenses',
    question: 'How can I export the expense audit trail for my tax accountant?',
    answer: 'Select your desired date range (e.g. "This Year" or "Last Quarter") using the duration filter bar, then click the "Export CSV" button. You get a clean spreadsheet with dates, expense titles, categories, amounts, and payment channels.',
    steps: [
      'Filter Expenses by "This Year" or "Custom Range".',
      'Click "Export CSV" at the top right.',
      'Email the exported spreadsheet directly to your tax auditor or accountant.'
    ],
    tip: 'Categorized digital expense exports make tax compliance fast and save accounting fees.'
  },

  // ==========================================
  // TAB 8: REPORTS (10 Questions)
  // ==========================================
  {
    id: 71,
    category: 'reports',
    question: 'What reports are available in HBOS and what are they used for?',
    answer: 'HBOS includes 8 primary reports: 1. Sales Report (revenue & margins), 2. Inventory Report (stock value & dead stock), 3. Customer Khata (aging & balances), 4. Supplier Payables (distributor dues), 5. Expense Audit (cost breakdown), 6. Financial Accounts (cash & bank ledgers), 7. Branch Comparison (multi-counter performance), and 8. Net Profit Statement.',
    steps: [
      'Go to Reports from the sidebar.',
      'Click between the 8 report tabs to access specialized analytics.',
      'Use the date duration filter to adjust reporting periods.'
    ],
    tip: 'Each report is designed around a specific operational question: sales, inventory, credit, or profit.'
  },
  {
    id: 72,
    category: 'reports',
    question: 'What is the Net Profit Statement and how is it calculated?',
    answer: 'The Net Profit Statement is the master financial scorecard for your store. It uses standard accounting formula: Gross Revenue - Cost of Goods Sold (COGS) = Gross Profit - Operating Expenses = Net Operating Profit.',
    steps: [
      'Gross Sales Revenue (Total billing across all counters).',
      'Less: Cost of Goods Sold (Wholesale cost of inventory sold).',
      'Equals: Gross Profit (Trading margin).',
      'Less: Operating Expenses (Rent, electricity, wages, tea, maintenance).',
      'Equals: Net Profit (Real bottom-line profit you can withdraw).'
    ],
    tip: 'Always base your business decisions and owner salary withdrawals on Net Profit, never on Gross Revenue.'
  },
  {
    id: 73,
    category: 'reports',
    question: 'How does the Sales Report help increase store revenue?',
    answer: 'The Sales Report reveals your top 20% products that generate 80% of your profits, peak billing hours of the day, and payment mode breakdowns. You can stock more of high-margin winners and discontinue items that don\'t sell.',
    steps: [
      'Open the Sales Report.',
      'Check the "Top Selling Products" leaderboard.',
      'Review hourly sales volume to schedule staff during peak rush periods.'
    ],
    tip: 'Double down on high-margin fast-moving items and place them at eye level on store shelves.'
  },
  {
    id: 74,
    category: 'reports',
    question: 'How do I use the Inventory Valuation Report?',
    answer: 'The Inventory Report shows the total wholesale cost vs expected retail value of all merchandise on your shelves. It highlights slow-moving "dead stock" that has not sold in over 60 days so you can run clearance promotions and recover cash.',
    steps: [
      'Open the Inventory Report.',
      'Inspect Total Inventory Value at Cost.',
      'Sort by "Days Since Last Sale" to identify dead stock.'
    ],
    tip: 'Discount dead stock at purchase cost to free up locked capital and invest in fast-moving items.'
  },
  {
    id: 75,
    category: 'reports',
    question: 'What does the Customer Khata Aging Report tell me?',
    answer: 'The Customer Khata Report categorizes unpaid customer credit by age: 0–30 Days (Current), 31–60 Days (Aging), and 60+ Days (Overdue/High Risk). It flags which customers need immediate follow-up before debts become bad debts.',
    steps: [
      'Open Customer Khata Report.',
      'Review the "Overdue (>60 Days)" bucket.',
      'Click customer names to send WhatsApp reminders or suspend further credit.'
    ],
    tip: 'Never allow customer credit to extend past 60 days without at least a partial payment.'
  },
  {
    id: 76,
    category: 'reports',
    question: 'How does the Supplier Payables Report assist in cash flow planning?',
    answer: 'The Supplier Payables Report lists all unpaid distributor invoices grouped by due dates (Due This Week, Due in 15 Days, Overdue). This allows you to plan your bank cash reserves and avoid running out of funds when distributor cheques mature.',
    steps: [
      'Open Supplier Payables Report.',
      'Check total dues maturing in the next 7 days.',
      'Ensure adequate daily sales cash is deposited into your bank account.'
    ],
    tip: 'Use this report before agreeing to new supplier deliveries to verify you have sufficient credit room.'
  },
  {
    id: 77,
    category: 'reports',
    question: 'What is the Expense Audit Report?',
    answer: 'The Expense Audit Report provides a visual and tabular breakdown of all operational expenses over time. It compares current month spending with previous months to spot unexpected spikes in generator fuel, utility bills, or repairs.',
    steps: [
      'Open Expense Audit Report.',
      'Inspect category spending bar charts.',
      'Identify categories with month-over-month cost increases.'
    ],
    tip: 'Investigating a 15% surge in utility or maintenance costs early can save hundreds of thousands per year.'
  },
  {
    id: 78,
    category: 'reports',
    question: 'How does the Branch / Counter Comparison Report work?',
    answer: 'For stores with multiple billing counters or secondary branches, the Branch Comparison Report compares revenue, customer footfall, average basket size, and margins side-by-side to pinpoint top performers and counter bottlenecks.',
    steps: [
      'Open Branch Comparison Report.',
      'Compare Counter 1 vs Counter 2 or Main Store vs Express Counter.',
      'Analyze average bill value and items per transaction.'
    ],
    tip: 'Use cashier performance comparisons to reward high-speed, accurate billing staff.'
  },
  {
    id: 79,
    category: 'reports',
    question: 'Can I export or print reports as PDF or Excel spreadsheets?',
    answer: 'Yes! Every report features one-click "Export to PDF" and "Export to Excel/CSV" buttons. You can generate formatted printouts for partners or download raw spreadsheets for detailed analysis.',
    steps: [
      'Open any report and select desired date range.',
      'Click "Export PDF" for a printable executive summary.',
      'Click "Export CSV" to open in Microsoft Excel.'
    ],
    tip: 'Save a PDF snapshot of your Net Profit Statement at the end of each month for permanent historical records.'
  },
  {
    id: 80,
    category: 'reports',
    question: 'Are historical reports permanently preserved in HBOS?',
    answer: 'Yes! HBOS uses an append-only transaction ledger. All historical sales, returns, stock movements, and expenses are permanently archived, allowing you to generate reports for any past month, quarter, or year at any time with 100% accuracy.',
    steps: [
      'Select "Custom Date Range" on any report.',
      'Pick any past period (e.g. January 1 to December 31 of last year).',
      'The exact historical figures are calculated instantly.'
    ],
    tip: 'Year-over-year reporting gives you deep insight into holiday and seasonal retail trends.'
  },

  // ==========================================
  // TAB 9: SETTINGS (10 Questions)
  // ==========================================
  {
    id: 81,
    category: 'settings',
    question: 'How do I update store name, address, and contact numbers on receipts?',
    answer: 'Go to Settings > Store Information. Update your Store / Business Name, Branch Address, Contact Numbers, and NTN / STRN Tax Registration Number, then click "Save Changes". All new receipts and invoices will display the updated information immediately.',
    steps: [
      'Click Settings in the main sidebar.',
      'In Store Info, edit Store Title, Address, and Phone Number.',
      'Click "Save Changes".',
      'Print a test receipt from POS to verify the new header.'
    ],
    tip: 'Include your store WhatsApp number on the receipt header so customers can order home delivery.'
  },
  {
    id: 82,
    category: 'settings',
    question: 'How do I upload my store logo and choose how it appears on invoices?',
    answer: 'In Settings > Receipts & Invoices, you can upload your business logo. Then choose your preferred display mode: 1. "Show Both (Logo & Store Name)" (Logo appears centered above the store name), 2. "Show Logo Only", or 3. "Show Store Name Only".',
    steps: [
      'Navigate to Settings > Receipts & Invoices.',
      'Click "Upload Logo" and select your image (PNG or JPG).',
      'Select your Header Display Mode: Show Both, Show Logo Only, or Show Store Name Only.',
      'Click "Save Receipt Settings".'
    ],
    tip: 'Use a high-contrast black-and-white logo for crisp, clear printing on thermal paper.'
  },
  {
    id: 83,
    category: 'settings',
    question: 'How do I configure thermal receipt printer paper width (80mm vs 58mm)?',
    answer: 'In Settings > Receipts & Invoices, select your printer paper width: standard 80mm (3-inch wide) or compact 58mm (2-inch wide). HBOS automatically adjusts text wrapping, font sizes, and column widths to fit your printer without cutoff.',
    steps: [
      'Go to Settings > Receipts.',
      'Choose Paper Width: 80mm (Standard POS) or 58mm (Compact).',
      'Click "Test Print" to verify formatting.'
    ],
    tip: '80mm paper provides ample space for detailed product names, itemized discounts, and barcode footers.'
  },
  {
    id: 84,
    category: 'settings',
    question: 'How do I add staff accounts (Cashiers, Managers, Accountants)?',
    answer: 'Go to Settings > Staff & Roles, click "+ Add User". Enter the staff member\'s name, login username, password, and assign their role (Salesperson/Cashier, Branch Manager, or Accountant). Staff can now log in securely with their own credentials.',
    steps: [
      'Open Settings > Staff & Roles.',
      'Click "+ Add Staff Member".',
      'Enter Username, Full Name, and Password.',
      'Assign Role: Salesperson, Branch Manager, or Accountant.',
      'Save profile.'
    ],
    tip: 'Give each cashier their own separate login so end-of-shift cash drawer reconciliations are always accurate.'
  },
  {
    id: 85,
    category: 'settings',
    question: 'How do role permissions protect my business from unauthorized staff actions?',
    answer: 'HBOS uses granular permissions. Cashiers are restricted to POS checkout and customer balance viewing. Sensitive areas—such as viewing wholesale costs, editing profit margins, voiding completed invoices, or viewing expense logs—are strictly reserved for Owners and Managers.',
    steps: [
      'Salesperson: POS billing, receipt printing, customer khata lookup.',
      'Branch Manager: Stock adjustments, reorders, day-end shift closeout, cashier reports.',
      'Business Owner: Full access to profit statements, settings, staff roles, and audit trails.'
    ],
    tip: 'Never share the Business Owner password with cashiers; use individual staff accounts.'
  },
  {
    id: 86,
    category: 'settings',
    question: 'How do I configure Sales Tax / GST on products and sales?',
    answer: 'In Settings > Taxes & Billing, enable Sales Tax, set the default tax percentage (e.g. 18% GST), and specify your Tax Registration Number (STRN). You can choose whether product shelf prices are tax-inclusive or tax-exclusive.',
    steps: [
      'Open Settings > Taxes.',
      'Toggle ON "Enable Sales Tax".',
      'Enter Tax Rate (e.g. 18%) and STRN number.',
      'Choose "Prices are Tax-Inclusive" for standard retail stores.',
      'Save settings.'
    ],
    tip: 'Tax-inclusive pricing keeps shelf prices round (e.g. Rs. 200) while the invoice itemizes the tax breakdown.'
  },
  {
    id: 87,
    category: 'settings',
    question: 'What is the Audit & Governance tab in Settings?',
    answer: 'Audit & Governance is an immutable, append-only black-box flight recorder. It logs every critical action in the software (price overrides, cancelled sales, stock write-offs, logins, and permission changes) with the exact user, timestamp, branch, and IP address.',
    steps: [
      'Go to Settings > Audit & Governance.',
      'Search or filter by Action Type (e.g. PRICE_OVERRIDE, SALE_VOID).',
      'Click "View Diff" to inspect the before-and-after snapshot of the change.'
    ],
    tip: 'Audit logs cannot be deleted or edited by anyone, providing proof against internal fraud or cash skimming.'
  },
  {
    id: 88,
    category: 'settings',
    question: 'How do I create and download a full backup of my store database?',
    answer: 'Go to Settings > Backup & Data, click "Create Database Backup". HBOS compiles an encrypted snapshot of all products, customers, suppliers, sales, and ledgers into a downloadable backup file for secure offsite storage.',
    steps: [
      'Open Settings > Backup & Data.',
      'Click "Create Full Backup".',
      'Download and save the backup file onto a secure USB drive or cloud drive.'
    ],
    tip: 'Download a database backup at the end of each week so your store data is safe against hardware failure.'
  },
  {
    id: 89,
    category: 'settings',
    question: 'How do I customize invoice footer notes (Return Policy, Greetings)?',
    answer: 'In Settings > Receipts & Invoices, scroll to the "Footer Note" field. Enter your store\'s return policy (e.g. "Goods once sold can be exchanged within 7 days with original receipt") and closing greeting ("Thank you for shopping with us!").',
    steps: [
      'Go to Settings > Receipts & Invoices.',
      'Edit the "Receipt Footer Policy" text box.',
      'Type your exchange rules and promotional messages.',
      'Save; the text prints at the bottom of every receipt.'
    ],
    tip: 'Clearly stating your 7-day exchange policy on receipts prevents customer arguments at the return counter.'
  },
  {
    id: 90,
    category: 'settings',
    question: 'How do I change my login password or update my profile?',
    answer: 'Click your avatar at the top right of the navigation bar or go to Settings > User Profile. Enter your current password, type your new secure password, confirm it, and click "Update Password".',
    steps: [
      'Click on your User Profile icon.',
      'Enter your Current Password.',
      'Enter and confirm your New Password (minimum 8 characters).',
      'Click "Save Changes".'
    ],
    tip: 'Change your administrative password every 90 days and never write it down on sticky notes near the counter.'
  }
]
</script>

<style scoped>
.support-page-wrapper {
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  color: var(--text-primary, #1e293b);
}

.cursor-pointer {
  cursor: pointer;
}

.transition-all {
  transition: all 0.2s ease-in-out;
}

.hover-bg-light:hover {
  background-color: #f8fafc;
  border-color: #cbd5e1 !important;
  transform: translateY(-1px);
}

/* Horizontal scrollable module tabs */
.module-tabs-scroll {
  overflow-x: auto;
  scrollbar-width: thin;
  -webkit-overflow-scrolling: touch;
}

.module-tabs-scroll::-webkit-scrollbar {
  height: 6px;
}

.module-tabs-scroll::-webkit-scrollbar-thumb {
  background-color: #cbd5e1;
  border-radius: 9999px;
}

.module-tab-btn {
  font-size: 0.9rem;
  border-width: 1.5px;
  box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}

.module-tab-btn:hover {
  border-color: var(--bs-primary);
}

.tab-icon-badge {
  width: 46px;
  height: 46px;
}

/* Accordion Styling */
.accordion-item {
  background-color: #ffffff;
  transition: all 0.2s ease;
}

.accordion-item:hover {
  border-color: #94a3b8 !important;
}

.accordion-button {
  font-size: 0.98rem;
  background-color: #ffffff;
  color: #1e293b;
  border-radius: 8px !important;
}

.accordion-button:not(.collapsed) {
  background-color: #f1f5f9;
  color: var(--bs-primary);
  box-shadow: none;
}

.accordion-button:focus {
  border-color: #94a3b8;
  box-shadow: none;
}

.accordion-button::after {
  background-size: 0.9rem;
}

.answer-text {
  font-size: 0.95rem;
  color: #334155;
}

/* Modal styling */
.modal-backdrop.show {
  opacity: 0.5;
}

@media (max-width: 768px) {
  .module-tab-btn {
    font-size: 0.82rem;
    padding: 0.4rem 0.75rem !important;
  }
}
</style>
