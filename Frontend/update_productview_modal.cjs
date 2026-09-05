const fs = require('fs');

let content = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', 'utf8');

// 1. Add props and emits definition to script setup
const scriptSetupPattern = /<script setup>\s*import/;
const propsEmits = `<script setup>
import { defineProps, defineEmits } from 'vue'
const props = defineProps({
  isModal: { type: Boolean, default: false },
  modalPage: { type: String, default: '' }
})
const emit = defineEmits(['success'])
import`;

content = content.replace(scriptSetupPattern, propsEmits);

// 2. Add mounted logic to default to modalPage if provided
const mountedPattern = /const currentPage = ref\(route\.query\.tab \|\| 'products'\)/;
const newMounted = `const currentPage = ref(props.isModal ? props.modalPage : (route.query.tab || 'products'))`;
content = content.replace(mountedPattern, newMounted);

// 3. Update saveProduct to emit success if isModal
const saveSuccessPattern = /selectedProductId\.value = newProduct\.id\s*toastMessage\.value = 'Product saved successfully!'/;
const newSaveSuccess = `selectedProductId.value = newProduct.id
    if (props.isModal) {
      emit('success', 'Product saved successfully!');
      return;
    }
    toastMessage.value = 'Product saved successfully!'`;
content = content.replace(saveSuccessPattern, newSaveSuccess);

// 4. Hide Sidebar and Header in Template using v-if="!isModal"
// Wrap <aside class="pv-sidebar">
content = content.replace(/<aside class="pv-sidebar" :class="\{ 'pv-sidebar-open': sidebarOpen \}">/, '<aside v-if="!isModal" class="pv-sidebar" :class="{ \'pv-sidebar-open\': sidebarOpen }">');

// Wrap <header class="pv-header">
content = content.replace(/<header class="pv-header">/, '<header v-if="!isModal" class="pv-header">');

// 5. Remove layout constraints on pv-main if isModal
content = content.replace(/<main class="pv-main">/, '<main class="pv-main" :style="isModal ? \'margin-left: 0; padding: 20px;\' : \'\'">');

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', content);
console.log('ProductView updated for modal mode.');
