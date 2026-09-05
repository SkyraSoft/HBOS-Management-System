import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api'

export const useSuppliersStore = defineStore('suppliers', () => {
  const suppliers = ref([])

  const fetchSuppliers = async () => {
    try {
      const response = await api.get('/suppliers')
      suppliers.value = response.data
    } catch (error) {
      console.error('Error fetching suppliers:', error)
    }
  }

  const addSupplier = async (newSupplier) => {
    try {
      const response = await api.post('/suppliers', newSupplier)
      suppliers.value.unshift(response.data)
      return { success: true }
    } catch (error) {
      console.error('Error adding supplier:', error)
      return { success: false, message: error.response?.data?.message || 'Failed to add supplier' }
    }
  }

  const updateSupplier = async (id, updatedData) => {
    try {
      const response = await api.put(`/suppliers/${id}`, updatedData)
      const index = suppliers.value.findIndex(s => s.id === id)
      if (index !== -1) {
        suppliers.value[index] = response.data
      }
      return { success: true }
    } catch (error) {
      console.error('Error updating supplier:', error)
      return { success: false, message: error.response?.data?.message || 'Failed to update supplier' }
    }
  }

  const deleteSupplier = async (id) => {
    try {
      await api.delete(`/suppliers/${id}`)
      suppliers.value = suppliers.value.filter(s => s.id !== id)
      return { success: true }
    } catch (error) {
      console.error('Error deleting supplier:', error)
      return { success: false, message: 'Failed to delete supplier' }
    }
  }

  return {
    suppliers,
    fetchSuppliers,
    addSupplier,
    updateSupplier,
    deleteSupplier
  }
})
