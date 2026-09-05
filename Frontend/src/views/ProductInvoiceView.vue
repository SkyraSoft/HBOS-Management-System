<template>
  <div class="product-module-wrapper">
    <div class="page" v-if="product">
        <div class="page-header" style="margin-bottom: 25px; text-align: center;">
            <div style="background: #e5f8ef; color: #0b8f5a; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 30px; margin: 0 auto 15px;">
                <i class="bi bi-check2"></i>
            </div>
            <h1 class="page-title" style="margin:0; font-size: 28px; font-weight: 750;">Product Successfully Added!</h1>
            <p style="color: var(--muted); margin: 5px 0 0;">The product has been saved to your inventory.</p>
        </div>

        <div style="max-width: 600px; margin: 0 auto; background: white; border: 1px solid var(--border); border-radius: 15px; padding: 30px; margin-bottom: 20px;">
            <div style="text-align: center; border-bottom: 1px dashed var(--border); padding-bottom: 20px; margin-bottom: 20px;">
                <h3 style="margin: 0; font-family: 'Courier New', monospace; font-size: 20px;">HBOS INVENTORY RECEIPT</h3>
                <p style="font-size: 12px; color: var(--muted); margin-top: 5px;">{{ new Date().toLocaleString() }}</p>
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-family: 'Courier New', monospace;">
                <strong>Product:</strong>
                <span>{{ product.name }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-family: 'Courier New', monospace;">
                <strong>SKU:</strong>
                <span>{{ product.sku }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-family: 'Courier New', monospace;">
                <strong>Category:</strong>
                <span>{{ product.category }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-family: 'Courier New', monospace;">
                <strong>Selling Price:</strong>
                <span>Rs {{ formatMoney(product.price || product.sellingPrice) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-family: 'Courier New', monospace;">
                <strong>Added Stock:</strong>
                <span>{{ product.stock }} {{ product.unit || 'pc' }}</span>
            </div>
            
            <div style="border-top: 1px dashed var(--border); padding-top: 20px; margin-top: 20px; display: flex; justify-content: space-between; font-size: 18px; font-weight: bold; font-family: 'Courier New', monospace;">
                <span>Total Value:</span>
                <span>Rs {{ formatMoney((product.price || product.sellingPrice || 0) * (product.stock || 0)) }}</span>
            </div>
        </div>
        
        <div style="text-align: center; max-width: 600px; margin: 0 auto;">
            <button class="btn-primary-custom" @click="router.push('/inventory/products')" style="width: 100%; margin-bottom: 10px; padding: 12px; font-size: 16px;">
                View All Products
            </button>
            <button class="btn-outline-custom" @click="router.push(`/inventory/products/${product.id}`)" style="width: 100%; padding: 12px; font-size: 16px;">
                View Product Details
            </button>
        </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useRetailStore } from '@/stores/retail'

const route = useRoute()
const router = useRouter()
const store = useRetailStore()

const product = computed(() => {
    return store.products.find(p => p.id == route.params.id)
})

const formatMoney = (val) => Number(val).toLocaleString("en-PK", { minimumFractionDigits: 2, maximumFractionDigits: 2 })
</script>

<style scoped>
.product-module-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 40px 20px;
}
.btn-outline-custom {
  background: white;
  border: 1px solid var(--border);
  color: #3e4655;
  padding: 10px 17px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}
.btn-primary-custom {
  background: var(--primary);
  border: 1px solid var(--primary);
  color: white;
  padding: 10px 17px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}
</style>
