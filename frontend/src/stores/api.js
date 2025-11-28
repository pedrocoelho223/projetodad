import { defineStore } from 'pinia'
import axios from 'axios'
import { ref } from 'vue'

export const useAPIStore = defineStore('api', () => {
  //const API_BASE_URL = inject('apiBaseURL')
  const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

  const token = ref(localStorage.getItem('token')) // Tenta recuperar do storage

  // Configuração inicial do axios se o token existir
  if (token.value) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
  }

  const gameQueryParameters = ref({
    page: 1,
    filters: {
      type: '',
      status: '',
      sort_by: 'id',
      sort_direction: 'desc',
    },
  })

  // AUTH
  const postLogin = async (credentials) => {
    const response = await axios.post(`${API_BASE_URL}/login`, credentials)

    // O código só continua aqui se o login for sucesso
    token.value = response.data.access_token || response.data.token
    localStorage.setItem('token', token.value) // Persistir no browser
    axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`

    return response
  }

  const postLogout = async () => {
    try {
      await axios.post(`${API_BASE_URL}/logout`)
    } catch {
      // Ignora erro no logout
    } finally {
      token.value = null
      localStorage.removeItem('token')
      delete axios.defaults.headers.common['Authorization']
    }
  }

  // Users
  const getAuthUser = () => {
    return axios.get(`${API_BASE_URL}/users/me`)
  }

  // --- GAMES ---

  // 1. Listar Jogos (GET)
  const getGames = (resetPagination = false) => {
    if (resetPagination) {
      gameQueryParameters.value.page = 1
    }

    const queryParams = new URLSearchParams({
      page: gameQueryParameters.value.page,
      ...(gameQueryParameters.value.filters.type && {
        type: gameQueryParameters.value.filters.type,
      }),
      ...(gameQueryParameters.value.filters.status && {
        status: gameQueryParameters.value.filters.status,
      }),
      sort_by: gameQueryParameters.value.filters.sort_by,
      sort_direction: gameQueryParameters.value.filters.sort_direction,
    }).toString()

    return axios.get(`${API_BASE_URL}/games?${queryParams}`)
  }

  // 2. Criar Jogo (POST) - ADICIONADO AQUI
  const postGame = (data) => {
    return axios.post(`${API_BASE_URL}/games`, data)
  }

  return {
    postLogin,
    postLogout,
    getAuthUser,
    getGames,
    postGame,
    gameQueryParameters,
    token
  }
})
