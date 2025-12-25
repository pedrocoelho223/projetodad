import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useAPIStore } from '@/stores/api'

export const useCoinsStore = defineStore('coins', () => {
  const api = useAPIStore()

  const balance = ref(0)
  const transactions = ref([])

  const loadingBalance = ref(false)
  const loadingTransactions = ref(false)
  const loadingPurchase = ref(false)

  const error = ref(null)
  const success = ref(null)

  const fetchBalance = async () => {
    loadingBalance.value = true
    error.value = null
    try {
      const res = await api.getCoinsBalance()
      balance.value = res.data?.balance ?? res.data?.data?.balance ?? 0
    } catch (e) {
      error.value = e.response?.data?.message || e.message
    } finally {
      loadingBalance.value = false
    }
  }

  const fetchTransactions = async () => {
    loadingTransactions.value = true
    error.value = null
    try {
      const res = await api.getCoinsTransactions()
      transactions.value = res.data?.data ?? res.data ?? []
    } catch (e) {
      error.value = e.response?.data?.message || e.message
    } finally {
      loadingTransactions.value = false
    }
  }

  const purchaseCoins = async (form) => {
    loadingPurchase.value = true
    error.value = null
    success.value = null
    try {
      await api.postCoinsPurchase({
        type: form.type,
        reference: form.reference,
        value: form.value,
      })
      success.value = 'Pagamento registado com sucesso!'
      await fetchBalance()
      await fetchTransactions()
    } catch (e) {
      error.value = e.response?.data?.message || e.message
    } finally {
      loadingPurchase.value = false
    }
  }

  return {
    balance,
    transactions,
    loadingBalance,
    loadingTransactions,
    loadingPurchase,
    error,
    success,
    fetchBalance,
    fetchTransactions,
    purchaseCoins,
  }
})
