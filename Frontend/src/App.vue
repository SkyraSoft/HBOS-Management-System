<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import MainLayout from './layouts/MainLayout.vue'
import AuthLayout from './layouts/AuthLayout.vue'
import ProductLayout from './layouts/ProductLayout.vue'
import SettingsLayout from './layouts/SettingsLayout.vue'
import EmptyLayout from './layouts/EmptyLayout.vue'

const route = useRoute()

// Determine which layout to use based on the route meta field
const layout = computed(() => {
  if (route.meta.layout === 'auth') return AuthLayout
  if (route.meta.layout === 'ProductLayout') return ProductLayout
  if (route.meta.layout === 'SettingsLayout') return SettingsLayout
  if (route.meta.layout === 'EmptyLayout') return EmptyLayout
  
  // Default to MainLayout
  return MainLayout
})
</script>

<template>
  <component :is="layout">
    <router-view />
  </component>
</template>

<style>
/* Global resets handled in main.css */
</style>
