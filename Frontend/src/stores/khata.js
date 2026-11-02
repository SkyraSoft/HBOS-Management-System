import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api'

export const useKhataStore = defineStore('khata', () => {
  const transactions = ref([])

  const fetchTransactions = async () => {
    try {
      const response = await api.get('/customer-payments')
      transactions.value = response.data
    } catch (error) {
      try {
        const response = await api.get('/khata')
        transactions.value = response.data
      } catch (err) {
        console.error('Error fetching khata transactions:', err)
      }
    }
  }

  const addTransaction = async (data) => {
    try {
      const customerId = data.customer_id;
      let response;
      if (customerId) {
        response = await api.post(`/customers/${customerId}/payments`, {
          amount: data.amount,
          payment_method: data.payment_method || data.method || 'Cash',
          date: data.date || new Date().toISOString().split('T')[0],
          notes: data.description || data.notes || 'Khata Payment',
          branch_id: data.branch_id || undefined,
        })
      } else {
        response = await api.post('/khata', data)
      }
      transactions.value.unshift(response.data)
      return { success: true, data: response.data }
    } catch (error) {
      console.error('Error adding khata transaction:', error)
      let msg = error.response?.data?.error || error.response?.data?.message || 'Failed to add khata transaction'
      if (error.response?.data?.errors) {
        msg = Object.values(error.response.data.errors).flat().join(' ');
      }
      return { success: false, message: msg }
    }
  }

  const deleteTransaction = async (id, reason = 'Payment reversed by user') => {
    try {
      const res = await api.post(`/customer-payments/${id}/reverse`, {
        reason: reason || 'Payment reversed by user'
      })
      const idx = transactions.value.findIndex(t => t.id === id)
      if (idx !== -1) {
        transactions.value[idx] = res.data
      }
      return { success: true, data: res.data }
    } catch (error) {
      console.error('Error reversing khata transaction:', error)
      return { success: false, message: error.response?.data?.error || error.response?.data?.message || 'Failed to reverse transaction' }
    }
  }

  return {
    transactions,
    fetchTransactions,
    addTransaction,
    deleteTransaction
  }
})
