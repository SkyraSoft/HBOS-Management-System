import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api'

export const useExpensesStore = defineStore('expenses', () => {
  // --- STATE ---
  
  const expenses = ref([])
  const recurringExpenses = ref([])
  const monthlyLimit = ref(285000)

  // Categories Dropdown (Dynamic)
  const categories = ref([])

  const fetchExpenseCategories = async () => {
    try {
      const response = await api.get('/expense-categories')
      categories.value = response.data.map(cat => cat.name)
    } catch (error) {
      console.error('Failed to fetch expense categories:', error)
    }
  }

  const addExpenseCategory = async (name) => {
    try {
      const response = await api.post('/expense-categories', { name })
      categories.value.push(response.data.name)
      return { success: true }
    } catch (error) {
      console.error('Error adding category:', error)
      return { success: false, message: error.response?.data?.message || 'Failed to add category' }
    }
  }

  const deleteExpenseCategory = async (name) => {
    try {
      const response = await api.get('/expense-categories')
      const target = response.data.find(c => c.name === name)
      if (target) {
        await api.delete(`/expense-categories/${target.id}`)
        categories.value = categories.value.filter(c => c !== name)
      }
      return { success: true }
    } catch (error) {
      console.error('Error deleting category:', error)
      return { success: false, message: 'Failed to delete category' }
    }
  }

  // --- GETTERS ---
  const totalExpensesToday = computed(() => {
    const today = new Date().toISOString().split('T')[0]
    return expenses.value
      .filter(exp => exp.date === today)
      .reduce((sum, exp) => sum + Number(exp.amount), 0)
  })

  const totalExpensesThisMonth = computed(() => {
    const currentMonth = new Date().toISOString().slice(0, 7) // YYYY-MM
    return expenses.value
      .filter(exp => exp.date.startsWith(currentMonth))
      .reduce((sum, exp) => sum + Number(exp.amount), 0)
  })

  const largestCategoryThisMonth = computed(() => {
    const currentMonth = new Date().toISOString().slice(0, 7)
    const categoryTotals = {}
    
    expenses.value.forEach(exp => {
      if (exp.date.startsWith(currentMonth)) {
        categoryTotals[exp.category] = (categoryTotals[exp.category] || 0) + Number(exp.amount)
      }
    })

    let maxCategory = 'N/A'
    let maxAmount = 0
    for (const [category, amount] of Object.entries(categoryTotals)) {
      if (amount > maxAmount) {
        maxAmount = amount
        maxCategory = category
      }
    }
    
    return { name: maxCategory, amount: maxAmount }
  })

  const budgetUtilization = computed(() => {
    if (monthlyLimit.value <= 0) return 0
    const percentage = (totalExpensesThisMonth.value / monthlyLimit.value) * 100
    return Math.min(percentage, 100).toFixed(1)
  })

  const expensesByCategory = computed(() => {
    const categoryTotals = {}
    let total = 0

    expenses.value.forEach(exp => {
      categoryTotals[exp.category] = (categoryTotals[exp.category] || 0) + Number(exp.amount)
      total += Number(exp.amount)
    })

    return Object.keys(categoryTotals).map(category => ({
      name: category,
      amount: categoryTotals[category],
      percentage: total > 0 ? ((categoryTotals[category] / total) * 100).toFixed(1) : 0
    })).sort((a, b) => b.amount - a.amount)
  })

  const avgDailyExpense = computed(() => {
    const today = new Date()
    const daysInMonth = today.getDate() 
    if (daysInMonth === 0) return 0
    return (totalExpensesThisMonth.value / daysInMonth).toFixed(2)
  })

  // --- ACTIONS (API Integrated) ---

  const fetchExpenses = async () => {
    try {
      const response = await api.get('/expenses')
      expenses.value = response.data
    } catch (error) {
      console.error('Error fetching expenses:', error)
    }
  }

  const fetchRecurringExpenses = async () => {
    try {
      const response = await api.get('/recurring-expenses')
      recurringExpenses.value = response.data
    } catch (error) {
      console.error('Error fetching recurring expenses:', error)
    }
  }

  const addExpense = async (newExpense) => {
    try {
      const response = await api.post('/expenses', newExpense)
      expenses.value.unshift(response.data)
      return { success: true }
    } catch (error) {
      console.error('Error adding expense:', error)
      return { success: false, message: error.response?.data?.message || 'Failed to add expense' }
    }
  }

  const updateExpense = async (id, updatedData) => {
    try {
      const response = await api.put(`/expenses/${id}`, updatedData)
      const index = expenses.value.findIndex(e => e.id === id)
      if (index !== -1) {
        expenses.value[index] = response.data
      }
      return { success: true }
    } catch (error) {
      console.error('Error updating expense:', error)
      return { success: false, message: error.response?.data?.message || 'Failed to update expense' }
    }
  }

  const deleteExpense = async (id) => {
    try {
      await api.delete(`/expenses/${id}`)
      expenses.value = expenses.value.filter(e => e.id !== id)
      return { success: true }
    } catch (error) {
      console.error('Error deleting expense:', error)
      return { success: false, message: 'Failed to delete expense' }
    }
  }

  const addRecurringExpense = async (newRecurring) => {
    try {
      const response = await api.post('/recurring-expenses', { ...newRecurring, status: 'Active' })
      recurringExpenses.value.push(response.data)
      return { success: true }
    } catch (error) {
      console.error('Error adding recurring expense:', error)
      return { success: false, message: 'Failed to add recurring expense' }
    }
  }

  const updateRecurringExpense = async (id, updatedData) => {
    try {
      const response = await api.put(`/recurring-expenses/${id}`, updatedData)
      const index = recurringExpenses.value.findIndex(e => e.id === id)
      if (index !== -1) {
        recurringExpenses.value[index] = response.data
      }
      return { success: true }
    } catch (error) {
      console.error('Error updating recurring expense:', error)
      return { success: false, message: 'Failed to update recurring expense' }
    }
  }

  const deleteRecurringExpense = async (id) => {
    try {
      await api.delete(`/recurring-expenses/${id}`)
      recurringExpenses.value = recurringExpenses.value.filter(e => e.id !== id)
      return { success: true }
    } catch (error) {
      console.error('Error deleting recurring expense:', error)
      return { success: false, message: 'Failed to delete recurring expense' }
    }
  }

  const markRecurringPaid = async (id) => {
    const recurring = recurringExpenses.value.find(e => e.id === id)
    if (!recurring) return { success: false, message: 'Recurring expense not found.' }

    try {
      // Create a regular expense based on the recurring one
      await api.post('/expenses', {
        description: recurring.name,
        category: recurring.category,
        amount: recurring.amount,
        date: new Date().toISOString().split('T')[0], // Paid today
        method: recurring.method || 'Cash',
        reference: 'Auto-Generated (Recurring)',
        branch: recurring.branch || 'Main Branch',
        notes: 'Generated from Recurring Expense'
      })

      // Calculate next due date
      const currentDue = new Date(recurring.nextDueDate)
      if (recurring.frequency === 'Monthly') {
        currentDue.setMonth(currentDue.getMonth() + 1)
      } else if (recurring.frequency === 'Weekly') {
        currentDue.setDate(currentDue.getDate() + 7)
      } else if (recurring.frequency === 'Yearly') {
        currentDue.setFullYear(currentDue.getFullYear() + 1)
      } else {
        currentDue.setDate(currentDue.getDate() + 30)
      }

      recurring.nextDueDate = currentDue.toISOString().split('T')[0]
      await api.put(`/recurring-expenses/${id}`, { nextDueDate: recurring.nextDueDate })

      await fetchExpenses()
      return { success: true, message: 'Expense marked as paid and next due date updated.' }
    } catch (error) {
      console.error('Error marking recurring expense paid:', error)
      return { success: false, message: 'Failed to process recurring payment' }
    }
  }

  return {
    expenses,
    recurringExpenses,
    monthlyLimit,
    categories,
    totalExpensesToday,
    totalExpensesThisMonth,
    largestCategoryThisMonth,
    budgetUtilization,
    expensesByCategory,
    avgDailyExpense,
    fetchExpenses,
    fetchRecurringExpenses,
    fetchExpenseCategories,
    addExpenseCategory,
    deleteExpenseCategory,
    addExpense,
    updateExpense,
    deleteExpense,
    addRecurringExpense,
    updateRecurringExpense,
    deleteRecurringExpense,
    markRecurringPaid
  }
})
