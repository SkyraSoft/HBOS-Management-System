<template>
  <div class="product-module-wrapper">
    <div class="page" v-if="product">
        <div class="page-header" style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <button class="btn-outline-custom" @click="router.push(`/inventory/products/${product.id}`)" style="padding: 6px 12px; margin-bottom: 15px; font-size: 13px;">
                    <i class="bi bi-arrow-left"></i> Cancel Edit
                </button>
                <h1 class="page-title" style="margin:0; font-size: 32px; font-weight: 750;">Edit {{ product.name }}</h1>
            </div>
            <div>
                <button class="btn-primary-custom" @click="saveChanges" style="margin-right: 10px;">
                    <i class="bi bi-check-lg"></i> Save Changes
                </button>
            </div>
        </div>

        <div class="content-card" style="background: white; border: 1px solid var(--border); border-radius: 15px; padding: 25px; margin-bottom: 20px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px;">Product Name</label>
                    <input type="text" v-model="editData.name" class="form-control" style="width: 100%; border: 1px solid var(--border); border-radius: 8px; padding: 10px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px;">Category</label>
                    <input type="text" v-model="editData.category" class="form-control" style="width: 100%; border: 1px solid var(--border); border-radius: 8px; padding: 10px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px;">Purchase Cost</label>
                    <input type="number" v-model="editData.cost" class="form-control" style="width: 100%; border: 1px solid var(--border); border-radius: 8px; padding: 10px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px;">Selling Price</label>
                    <input type="number" v-model="editData.price" class="form-control" style="width: 100%; border: 1px solid var(--border); border-radius: 8px; padding: 10px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px;">Stock Quantity</label>
                    <input type="number" v-model="editData.stock" class="form-control" style="width: 100%; border: 1px solid var(--border); border-radius: 8px; padding: 10px;">
                </div>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useRetailStore } from '@/stores/retail'

const route = useRoute()
const router = useRouter()
const store = useRetailStore()

const product = computed(() => {
    return store.products.find(p => p.id == route.params.id)
})

const editData = ref({
    name: '',
    category: '',
    cost: 0,
    price: 0,
    stock: 0
})

onMounted(() => {
    if (product.value) {
        editData.value = {
            name: product.value.name,
            category: product.value.category,
            cost: product.value.cost || product.value.purchasePrice,
            price: product.value.price || product.value.sellingPrice,
            stock: product.value.stock
        }
    }
})

const saveChanges = () => {
    store.updateProduct(product.value.id, {
        name: editData.value.name,
        category: editData.value.category,
        cost: editData.value.cost,
        purchasePrice: editData.value.cost,
        price: editData.value.price,
        sellingPrice: editData.value.price,
        stock: editData.value.stock
    })
    router.push(`/inventory/products/${product.value.id}`)
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
