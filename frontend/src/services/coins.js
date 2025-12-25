import api from '@/services/api'

export function apiGetBalance() {
  return api.get('/coins/balance')
}

export function apiGetTransactions() {
  return api.get('/coins/transactions')
}

export function apiPurchaseCoins(payload) {
  return api.post('/coins/purchase', payload)
}
