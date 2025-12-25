import { defineStore } from 'pinia'
import { apiGetBalance, apiGetTransactions, apiPurchaseCoins } from '@/services/coins'
import { useAuthStore } from '@/stores/auth'

export const useCoinsStore = defineStore('coins', {
  state: () => ({
    balance: 0,
    transactions: [],
    loadingBalance: false,
    loadingTransactions: false,
    loadingPurchase: false,
    error: null,
    success: null,
  }),

  actions: {
    clearMessages() {
      this.error = null
      this.success = null
    },

    async fetchBalance() {
      this.loadingBalance = true
      this.error = null

      try {
        const res = await apiGetBalance()
        this.balance = res.data?.coins_balance ?? 0
      } catch (e) {
        this.balance = 0
        this.error = e?.response?.data?.message || 'Não foi possível obter o saldo'
      } finally {
        this.loadingBalance = false
      }
    },

    async fetchTransactions() {
      this.loadingTransactions = true
      this.error = null

      try {
        const res = await apiGetTransactions()
        this.transactions = Array.isArray(res.data) ? res.data : []
      } catch (e) {
        this.transactions = []
        this.error = e?.response?.data?.message || 'Não foi possível obter o histórico'
      } finally {
        this.loadingTransactions = false
      }
    },

    async purchaseCoins(form) {
      this.loadingPurchase = true
      this.error = null
      this.success = null

      try {
        const res = await apiPurchaseCoins({
          type: form.type,
          reference: form.reference,
          value: form.value,
        })

        this.success = res.data?.message || 'Compra efetuada com sucesso'

        // Atualiza store coins
        await this.fetchBalance()
        await this.fetchTransactions()

        // ✅ Atualiza também o user (para o Profile mostrar coins_balance certo)
        const authStore = useAuthStore()
        await authStore.fetchCurrentUser(true)

        return res.data
      } catch (e) {
        this.error =
          e?.response?.data?.message ||
          e?.response?.data?.details?.message ||
          'Erro ao processar pagamento'
        throw e
      } finally {
        this.loadingPurchase = false
      }
    },
  },
})
