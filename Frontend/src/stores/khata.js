import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api'

export const useKhataStore = defineStore('khata', () => {
  const transactions = ref([])

  const fetchTransactions = async () => {
    try {
      const response = await api.get('/khata')
      transactions.value = response.data
    } catch (error) {
      console.error('Error fetching khata transactions:', error)
    }
  }

  const addTransaction = async (data) => {
    try {
      const response = await api.post('/khata', data)
      transactions.value.unshift(response.data)
      return { success: true }
    } catch (error) {
      console.error('Error adding khata transaction:', error)
      return { success: false, message: error.response?.data?.message || 'Failed to add khata transaction' }
    }
  }

  const deleteTransaction = async (id) => {
    try {
      await api.delete(`/khata/${id}`)
      transactions.value = transactions.value.filter(t => t.id !== id)
      return { success: true }
    } catch (error) {
      console.error('Error deleting khata transaction:', error)
      return { success: false, message: 'Failed to delete khata transaction' }
    }
  }

  return {
    transactions,
    fetchTransactions,
    addTransaction,
    deleteTransaction
  }
})
