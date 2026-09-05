import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api'

export const usePurchasesStore = defineStore('purchases', () => {
  const purchases = ref([])

  const fetchPurchases = async () => {
    try {
      const response = await api.get('/purchases')
      purchases.value = response.data
    } catch (error) {
      console.error('Error fetching purchases:', error)
    }
  }

  const addPurchase = async (newPurchase) => {
    try {
      const response = await api.post('/purchases', newPurchase)
      purchases.value.unshift(response.data)
      return { success: true }
    } catch (error) {
      console.error('Error adding purchase:', error)
      return { success: false, message: error.response?.data?.message || 'Failed to add purchase' }
    }
  }

  const updatePurchase = async (id, updatedData) => {
    try {
      const response = await api.put(`/purchases/${id}`, updatedData)
      const index = purchases.value.findIndex(p => p.id === id)
      if (index !== -1) {
        purchases.value[index] = response.data
      }
      return { success: true }
    } catch (error) {
      console.error('Error updating purchase:', error)
      return { success: false, message: error.response?.data?.message || 'Failed to update purchase' }
    }
  }

  const deletePurchase = async (id) => {
    try {
      await api.delete(`/purchases/${id}`)
      purchases.value = purchases.value.filter(p => p.id !== id)
      return { success: true }
    } catch (error) {
      console.error('Error deleting purchase:', error)
      return { success: false, message: 'Failed to delete purchase' }
    }
  }

  return {
    purchases,
    fetchPurchases,
    addPurchase,
    updatePurchase,
    deletePurchase
  }
})
