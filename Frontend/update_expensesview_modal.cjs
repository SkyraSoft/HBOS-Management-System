const fs = require('fs');

let content = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ExpensesView.vue', 'utf8');

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

// 2. Update activePage ref initialization
const mountedPattern = /const activePage = ref\(route\.query\.tab \|\| 'overview'\);/;
const newMounted = `const activePage = ref(props.isModal ? props.modalPage : (route.query.tab || 'overview'));`;
content = content.replace(mountedPattern, newMounted);

// 3. Emit success event inside saveNewExpense
// Look for saveNewExpense
const savePattern = /expensesStore\.addExpense\(\{[\s\S]*?\}\);/;
const newSave = `expensesStore.addExpense({
      ...newExpense.value,
      id: Date.now()
    });
    if (props.isModal) {
      emit('success', 'Expense saved successfully!');
      return;
    }`;
content = content.replace(/expensesStore\.addExpense\(\{[\s\S]*?id: Date\.now\(\)\s*\}\);/, newSave);

// 4. Hide Sidebar and Header in Template using v-if="!isModal"
// Wrap <div class="sidebar">
content = content.replace(/<div class="sidebar">/, '<div v-if="!isModal" class="sidebar">');
// Note: ExpensesView sidebar doesn't seem to be wrapped by <div class="app"> it has:
// <div class="app-container">
//   <div class="sidebar"> ... </div>
//   <div class="main-content"> ...

// So we wrap the sidebar
content = content.replace(/<div class="sidebar">/g, '<div v-if="!isModal" class="sidebar">');

// Wrap header
content = content.replace(/<div class="header">/g, '<div v-if="!isModal" class="header">');

// Apply style to main-content if isModal
content = content.replace(/<div class="main-content">/, '<div class="main-content" :style="isModal ? \'padding: 0; min-height: auto;\' : \'\'">');

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ExpensesView.vue', content);
console.log('ExpensesView updated for modal mode.');
