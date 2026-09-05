<template>
  <div class="product-module-wrapper">
    <div class="page" v-if="product">
        <div class="page-header" style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <button class="btn-outline-custom" @click="router.push('/inventory/products')" style="padding: 6px 12px; margin-bottom: 15px; font-size: 13px;">
                    <i class="bi bi-arrow-left"></i> Back to Products
                </button>
                <h1 class="page-title" style="margin:0; font-size: 32px; font-weight: 750;">{{ product.name }}</h1>
                <p style="color: var(--muted); margin: 5px 0 0;">SKU: {{ product.sku }}</p>
            </div>
            <div>
                <button class="btn-outline-custom" @click="router.push(`/inventory/products/${product.id}/edit`)" style="margin-right: 10px;">
                    <i class="bi bi-pencil"></i> Edit Product
                </button>
            </div>
        </div>

        <div class="detail-grid" style="display: grid; grid-template-columns: 1.7fr 1fr; gap: 20px;">
            <div>
                <div class="content-card" style="background: white; border: 1px solid var(--border); border-radius: 15px; padding: 25px; margin-bottom: 20px;">
                    <h3 style="margin-top: 0; font-size: 18px; border-bottom: 1px solid var(--border); padding-bottom: 15px; margin-bottom: 15px;">Product Information</h3>
                    
                    <div class="detail-row">
                        <div class="detail-label">Name</div>
                        <div class="detail-value">{{ product.name }}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Category</div>
                        <div class="detail-value">{{ product.category || 'General' }}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Description</div>
                        <div class="detail-value" style="color: var(--muted); font-weight: normal; text-align: right; max-width: 300px;">{{ product.description || 'No description provided.' }}</div>
                    </div>
                </div>

                <div class="content-card" style="background: white; border: 1px solid var(--border); border-radius: 15px; padding: 25px;">
                    <h3 style="margin-top: 0; font-size: 18px; border-bottom: 1px solid var(--border); padding-bottom: 15px; margin-bottom: 15px;">Pricing & Inventory</h3>
                    
                    <div class="detail-row">
                        <div class="detail-label">Cost Price</div>
                        <div class="detail-value">Rs {{ formatMoney(product.cost || 0) }}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Selling Price</div>
                        <div class="detail-value">Rs {{ formatMoney(product.price || product.sellingPrice || 0) }}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Margin</div>
                        <div class="detail-value" style="color: var(--success);">{{ calculateMargin(product.cost, product.price || product.sellingPrice) }}%</div>
                    </div>
                </div>
            </div>

            <div>
                <div class="content-card" style="background: white; border: 1px solid var(--border); border-radius: 15px; padding: 25px;">
                    <h3 style="margin-top: 0; font-size: 18px; border-bottom: 1px solid var(--border); padding-bottom: 15px; margin-bottom: 15px;">Stock Level</h3>
                    <div style="font-size: 48px; font-weight: 800; text-align: center; color: var(--primary);">
                        {{ product.stock }}
                    </div>
                    <div style="text-align: center; color: var(--muted); font-size: 14px;">{{ product.unit || 'Pieces' }} available</div>
                </div>
            </div>
        </div>
    </div>
    <div class="page" v-else>
      <div style="padding: 50px; text-align: center;">Product not found.</div>
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

const calculateMargin = (cost, price) => {
    if (!cost || !price || price <= 0) return '0.00'
    return (((price - cost) / price) * 100).toFixed(2)
}
</script>

<style scoped>
.product-module-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
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
.detail-row {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  padding: 14px 0;
  border-bottom: 1px solid #edf0f5;
}
.detail-row:last-child {
  border-bottom: 0;
}
.detail-label {
  color: #697386;
  font-size: 14px;
}
.detail-value {
  font-weight: 700;
  text-align: right;
}
</style>
