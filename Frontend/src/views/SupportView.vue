<template>
  <div class="support-page-wrapper px-4 py-4">

    <!-- PAGE HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div>
        <h2 class="fw-bold mb-1" style="color: var(--text-primary);">Help & Support</h2>
        <p class="text-secondary mb-0">Get assistance, find answers to frequently asked questions, and learn how to use the system.</p>
      </div>

      <!-- HEADER ACTIONS -->
      <div class="d-flex gap-2">
        <button class="btn btn-primary fw-medium d-flex align-items-center gap-2" style="border-radius: 8px;">
          <i class="bi bi-chat-dots"></i>
          Contact Support
        </button>
      </div>
    </div>

    <!-- MAIN CONTENT GRID -->
    <div class="row g-4 mb-4">
      
      <!-- FAQ Section -->
      <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
          <h5 class="fw-bold mb-4">Frequently Asked Questions</h5>
          
          <div class="accordion accordion-flush" id="faqAccordion">
            <div 
              v-for="faq in faqs" 
              :key="faq.id"
              class="accordion-item mb-3 border rounded-3 overflow-hidden"
            >
              <h2 class="accordion-header">
                <button 
                  class="accordion-button fw-medium bg-light" 
                  :class="{ collapsed: openQuestion !== faq.id }"
                  type="button" 
                  @click="toggleQuestion(faq.id)"
                  :aria-expanded="openQuestion === faq.id"
                >
                  {{ faq.question }}
                </button>
              </h2>
              <div 
                class="accordion-collapse collapse" 
                :class="{ show: openQuestion === faq.id }"
              >
                <div class="accordion-body text-secondary" style="font-size: 0.95rem; line-height: 1.6;">
                  {{ faq.answer }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Resources -->
      <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
          <h5 class="fw-bold mb-3">Quick Resources</h5>
          
          <div class="d-flex align-items-center gap-3 p-3 border rounded-3 mb-3 cursor-pointer hover-bg-light">
            <div class="bg-primary-subtle p-2 rounded text-primary">
              <i class="bi bi-journal-text fs-5"></i>
            </div>
            <div>
              <h6 class="fw-medium mb-0">System Guide</h6>
              <span class="small text-secondary">Read the full manual</span>
            </div>
          </div>

          <div class="d-flex align-items-center gap-3 p-3 border rounded-3 mb-3 cursor-pointer hover-bg-light">
            <div class="bg-info-subtle p-2 rounded text-info">
              <i class="bi bi-play-btn fs-5"></i>
            </div>
            <div>
              <h6 class="fw-medium mb-0">Video Tutorials</h6>
              <span class="small text-secondary">Watch step-by-step guides</span>
            </div>
          </div>

          <div class="d-flex align-items-center gap-3 p-3 border rounded-3 cursor-pointer hover-bg-light">
            <div class="bg-danger-subtle p-2 rounded text-danger">
              <i class="bi bi-bug fs-5"></i>
            </div>
            <div>
              <h6 class="fw-medium mb-0">Report a Problem</h6>
              <span class="small text-secondary">Found a bug? Let us know</span>
            </div>
          </div>

        </div>

        <!-- Contact Support Box -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-primary text-white">
          <h5 class="fw-bold mb-2">Need direct help?</h5>
          <p class="small mb-4 opacity-75">Our support team is available Mon-Fri, 9am - 6pm.</p>
          
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-telephone"></i>
            <span class="fw-medium">0800-HBOS-HELP</span>
          </div>
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-envelope"></i>
            <span class="fw-medium">support@hbos.pk</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const openQuestion = ref(null)

const toggleQuestion = (id) => {
  openQuestion.value = openQuestion.value === id ? null : id
}

const faqs = [
  {
    id: 1,
    question: 'How do I add a new product to inventory?',
    answer: 'Go to the Products page from the main sidebar. Click on the "Add Product" button located in the top right corner. A modal will open where you can fill out the required details such as barcode, name, category, and pricing. Once all details are filled, simply click "Save" to instantly add the item to your inventory.'
  },
  {
    id: 2,
    question: 'How can I manage customer credit (Khata)?',
    answer: 'Navigate to the Khata (Customers) section using the navigation menu. Here you will see a list of all your registered customers along with their current outstanding balances. To record a new payment or purchase on credit, select a specific customer profile. From there, you can enter the received amount to automatically update their ledger.'
  },
  {
    id: 3,
    question: 'Can I generate reports for specific date ranges?',
    answer: 'Yes, you absolutely can! Head over to the Reports module where you will see a comprehensive breakdown of your business metrics. Use the date filter dropdown menu positioned at the top right of the screen. Select "Custom Range" from the options, then pick your desired start and end dates to instantly generate the requested report.'
  }
]
</script>

<style scoped>
.support-page-wrapper {
  font-family: 'Inter', sans-serif;
}
.cursor-pointer {
  cursor: pointer;
}
.hover-bg-light:hover {
  background-color: #f8f9fa;
  transition: background-color 0.2s ease;
}
.accordion-button:not(.collapsed) {
  background-color: #f8f9fa;
  color: var(--text-primary);
  box-shadow: inset 0 -1px 0 rgba(0,0,0,.125);
}
.accordion-button:focus {
  border-color: rgba(0,0,0,.125);
  box-shadow: none;
}
</style>
