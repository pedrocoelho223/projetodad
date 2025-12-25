import { defineStore } from 'pinia'
import axios from 'axios'
import { ref } from 'vue'

export const useAPIStore = defineStore('api', () => {
  //const API_BASE_URL = inject('apiBaseURL')
  const API_BASE_URL = (import.meta.env.VITE_API_DOMAIN || 'http://localhost:8000') + '/api'

  const token = ref(localStorage.getItem('token')) // Tenta recuperar do storage

  // Axios instance (melhor do que axios global)
  const http = axios.create({
    baseURL: API_BASE_URL,
    timeout: 15000,
  })

  // Configuração inicial do http se o token existir
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

  /*const gameQueryParameters = ref({
    page: 1,
    filters: {
      type: '',
      status: '',
      sort_by: 'id',
      sort_direction: 'desc',
    },
  })


  Movido para baixo*/

  // AUTH
  const postLogin = async (credentials) => {
    const response = await http.post('/login', credentials)
    setToken(response.data.access_token || response.data.token)
    return response
  }

  //[Novo] Registo
  const postRegister = async (formData) => {
    const response = await http.post('/register', formData)
    setToken(response.data.access_token || response.data.token)
    return response
  }

  // Logout
  const postLogout = async () => {
    try {
      await http.post('/logout')
    } finally {
      setToken(null)
    }
  }

  // Users
  const getAuthUser = () => http.get('/users/me')

  //Atualizar Perfil
  const postUpdateUser = (formData) => {
    // Laravel + ficheiros: POST com _method
    formData.append('_method', 'PUT')
    return http.post('/users/me', formData)
  }

  // Apagar Conta
  const deleteUser = (password) => http.delete('/users/me', { data: { password } })

  // --- GAMES ---
  const gameQueryParameters = ref({
    page: 1,
    filters: {
      type: '',
      status: '',
      sort_by: 'id',
      sort_direction: 'desc',
    },
  })

  // 1. Listar Jogos (GET)
  const getGames = (resetPagination = false) => {
    if (resetPagination) gameQueryParameters.value.page = 1

    const f = gameQueryParameters.value.filters || {}
    const params = new URLSearchParams()

    params.set('page', String(gameQueryParameters.value.page || 1))
    if (f.type) params.set('type', f.type)
    if (f.status) params.set('status', f.status)
    params.set('sort_by', f.sort_by || 'id')
    params.set('sort_direction', f.sort_direction || 'desc')

    return http.get(`/games?${params.toString()}`)
  }

  // 2. Criar Jogo (POST) - ADICIONADO AQUI
  const postGame = (data) => http.post('/games', data)

  const getGame = (id) => http.get(`/games/${id}`)

  const playGameCard = (id, payload) => http.post(`/games/${id}/play`, payload)

  // --- COINS ---
  const getCoinsBalance = () => http.get('/coins/balance')
  const getCoinsTransactions = () => http.get('/coins/transactions')
  const postCoinsPurchase = (payload) => http.post('/coins/purchase', payload)

  return {
    token,
    gameQueryParameters,

    // http (às vezes dá jeito usar diretamente)
    http,

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

    // coins
    getCoinsBalance,
    getCoinsTransactions,
    postCoinsPurchase,
  }
})
