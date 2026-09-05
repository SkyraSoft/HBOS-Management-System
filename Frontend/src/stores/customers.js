import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api'

export const useCustomersStore = defineStore('customers', () => {
  const customers = ref([])

  const fetchCustomers = async () => {
    try {
      const response = await api.get('/customers')
      customers.value = response.data
    } catch (error) {
      console.error('Error fetching customers:', error)
    }
  }

  const addCustomer = async (newCustomer) => {
    try {
      const response = await api.post('/customers', newCustomer)
      customers.value.unshift(response.data)
      return { success: true, data: response.data }
    } catch (error) {
      console.error('Error adding customer:', error)
      let msg = error.response?.data?.message || 'Failed to add customer'
      if (error.response?.data?.errors) {
        msg = Object.values(error.response.data.errors).flat().join(' ');
      }
      return { success: false, message: msg }
    }
  }

  const updateCustomer = async (id, updatedData) => {
    try {
      const response = await api.put(`/customers/${id}`, updatedData)
      const index = customers.value.findIndex(c => c.id === id)
      if (index !== -1) {
        customers.value[index] = response.data
      }
      return { success: true }
    } catch (error) {
      console.error('Error updating customer:', error)
      return { success: false, message: error.response?.data?.message || 'Failed to update customer' }
    }
  }

  const deleteCustomer = async (id) => {
    try {
      await api.delete(`/customers/${id}`)
      customers.value = customers.value.filter(c => c.id !== id)
      return { success: true }
    } catch (error) {
      console.error('Error deleting customer:', error)
      return { success: false, message: 'Failed to delete customer' }
    }
  }

  return {
    customers,
    fetchCustomers,
    addCustomer,
    updateCustomer,
    deleteCustomer
  }
})
