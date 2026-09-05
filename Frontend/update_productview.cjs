const fs = require('fs');

let content = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', 'utf8');

const importPattern = /import \{ ref, computed, reactive, onMounted \} from 'vue'/;
const newImport = `import { ref, computed, reactive, onMounted, watch } from 'vue'\nimport { useRoute } from 'vue-router'`;

content = content.replace(importPattern, newImport);

const statePattern = /const currentPage = ref\('products'\)/;
const newState = `const route = useRoute()
const currentPage = ref(route.query.tab || 'products')

watch(() => route.query.tab, (newTab) => {
  if (newTab) {
    currentPage.value = newTab;
  }
})`;

content = content.replace(statePattern, newState);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', content);
console.log('ProductView updated.');
