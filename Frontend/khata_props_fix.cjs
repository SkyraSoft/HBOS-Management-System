const fs = require('fs');
let khView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', 'utf8');

const setupPattern = /<script setup>[\s\S]*?const store = useKhataStore\(\);/;

const newSetup = `<script setup>
  import { ref, onMounted, computed, defineProps, defineEmits } from 'vue';
  import { useKhataStore } from '@/stores/khata';
  import { useCustomersStore } from '@/stores/customers';
  
  const props = defineProps({
    isModal: {
      type: Boolean,
      default: false
    },
    modalPage: {
      type: String,
      default: ''
    }
  });
  
  const emit = defineEmits(['close', 'success']);
  
  const store = useKhataStore();`;

khView = khView.replace(setupPattern, newSetup);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', khView);
