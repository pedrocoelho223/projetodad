import { defineStore } from 'pinia'
import axios from 'axios'

import { ref } from 'vue'

export const useAPIStore = defineStore('api', () => {
  // Base: http://.../api
  const domain = import.meta.env.VITE_API_DOMAIN || 'http://localhost:8000'
  const API_BASE_URL = `${domain}/api`

  const token = ref(localStorage.getItem('token'))

  const http = axios.create({
    baseURL: API_BASE_URL,
    timeout: 15000,
    withCredentials: true, // se não usares cookies/sanctum podes tirar
  })

  // aplica token ao iniciar
  if (token.value) {
    http.defaults.headers.common.Authorization = `Bearer ${token.value}`
  }

  const setToken = (newToken) => {
    token.value = newToken

    if (newToken) {
      localStorage.setItem('token', newToken)
      http.defaults.headers.common.Authorization = `Bearer ${newToken}`
    } else {
      localStorage.removeItem('token')
      delete http.defaults.headers.common.Authorization
    }

  }

    // AUTH
  const postLogin = async (credentials) => {
    const response = await http.post('/login', credentials)
    setToken(response.data.access_token || response.data.token)
    return response
  }

  const postRegister = async (formData) => {
    const response = await http.post('/register', formData)
    setToken(response.data.access_token || response.data.token)
    return response
  }

  const postLogout = async () => {
    try {
      await http.post('/logout')
    } finally {
      setToken(null)
    }
  }

  // USERS
  const getAuthUser = () => http.get('/users/me')

  const postUpdateUser = (formData) => {
    formData.append('_method', 'PUT')
    return http.post('/users/me', formData)
  }

  const deleteUser = (password) => http.delete('/users/me', { data: { password } })

  // GAMES
  const getGames = (page = 1) => http.get('/games', { params: { page } })
  const postGame = (data) => http.post('/games', data)
  const getGame = (id) => http.get(`/games/${id}`)
  const playGameCard = (id, payload) => http.post(`/games/${id}/play`, payload)

  // -------------------------
  // LEADERBOARD (BD via API)
  // -------------------------
  // Endpoint sugerido: GET /api/leaderboard/top?scope=overall&limit=3
  const getTopPlayers = ({ scope = 'overall', limit = 3 } = {}) =>
  http.get('/leaderboard/top', { params: { scope, limit } })

  // COINS
  const getCoinsBalance = () => http.get('/coins/balance')
  const getCoinsTransactions = () => http.get('/coins/transactions')
  const postCoinsPurchase = (payload) => http.post('/coins/purchase', payload)

  return {
    domain,
    API_BASE_URL,
    http,
    token,
    setToken,

    // auth
    postLogin,
    postRegister,
    postLogout,

    // users
    getAuthUser,
    postUpdateUser,
    deleteUser,

    // games
    getGames,
    postGame,
    getGame,
    playGameCard,

    // leaderboard
    getTopPlayers,

    // coins
    getCoinsBalance,
    getCoinsTransactions,
    postCoinsPurchase,
  }
})
