const fs = require('fs');
const html = fs.readFileSync('legacy_html/Product.html', 'utf8');
const vue = `<template>
  <div class="legacy-product-wrapper">
${html}
  </div>
</template>

<script setup>
</script>

<style scoped>
</style>`;
fs.writeFileSync('src/views/ProductView.vue', vue);
console.log('Restored ProductView.vue');
