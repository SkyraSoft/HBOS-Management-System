<template>
  <div class="sales-history-wrapper p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-bold mb-1" style="color: var(--text-primary);">Sales History</h2>
        <p class="text-secondary mb-0">View all past transactions and invoices.</p>
      </div>
      <button class="btn btn-primary fw-medium border d-flex align-items-center gap-2" @click="fetchData">
        <i class="bi bi-arrow-clockwise"></i> Refresh
      </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-4">Invoice #</th>
              <th>Date</th>
              <th>Customer</th>
              <th>Status</th>
              <th>Payment Method</th>
              <th class="text-end pe-4">Total (PKR)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.sales.length === 0">
              <td colspan="6" class="text-center py-5 text-secondary">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                No sales found.
              </td>
            </tr>
            <tr v-for="sale in store.sales" :key="sale.id">
              <td class="ps-4 fw-medium text-primary">{{ sale.invoice_number }}</td>
              <td>{{ formatDate(sale.date) }}</td>
              <td>
                  {{ sale.customer?.name || sale.customer_name || 'Walk-in Customer' }}
              </td>
              <td>
                <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1">
                  {{ sale.status || 'Completed' }}
                </span>
              </td>
              <td>{{ sale.payment_method }}</td>
              <td class="text-end pe-4 fw-bold">Rs. {{ Number(sale.total).toLocaleString('en-PK') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRetailStore } from '@/stores/retail'

const store = useRetailStore()

const fetchData = async () => {
    await store.fetchSales()
}

onMounted(() => {
    fetchData()
})

function formatDate(dateStr) {
    if(!dateStr) return '-'
    const d = new Date(dateStr)
    return d.toLocaleDateString('en-PK', {
        year: 'numeric', month: 'short', day: 'numeric'
    })
}
</script>

<style scoped>
.sales-history-wrapper {
  background-color: var(--bg);
  min-height: calc(100vh - 65px);
}
.table th {
  font-size: 0.85rem;
  font-weight: 600;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 2px solid #edf2f9;
}
.table td {
  font-size: 0.95rem;
  color: #344050;
  border-bottom: 1px solid #edf2f9;
  padding: 1rem 0.5rem;
}
</style>
