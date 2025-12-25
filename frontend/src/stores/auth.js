import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useAPIStore } from './api'
import axios from 'axios'

// Base URL para links (foto) — não usar localhost hardcoded
const API_BASE = import.meta.env.VITE_API_URL
  ? import.meta.env.VITE_API_URL.replace(/\/api\/?$/, '') // remove /api no fim se existir
  : '' // em prod com ingress /api relativo, foto pode precisar de host absoluto (ver nota abaixo)

export const useAuthStore = defineStore('auth', () => {
  const apiStore = useAPIStore()

  const token = ref(localStorage.getItem('token'))
  const currentUser = ref(null)

  if (token.value) {
    axios.defaults.headers.common['Authorization'] = 'Bearer ' + token.value
  }

  const isLoggedIn = computed(() => !!token.value)
  const currentUserID = computed(() => currentUser.value?.id)

  const userPhotoUrl = computed(() => {
    const filename = currentUser.value?.photo_avatar_filename
    if (!filename) return null

    // Se tiveres API_BASE configurado (dev/prod com host), usa isso
    if (API_BASE) {
      return `${API_BASE}/storage/photos/${filename}`
    }

    // fallback: caminho relativo (funciona se o frontend for servido pelo mesmo host e o backend expuser /storage)
    return `/storage/photos/${filename}`
  })

  // ✅ NOVO: buscar utilizador atual sempre que precisares (força refresh quando quiseres)
  const fetchCurrentUser = async (force = false) => {
    if (!token.value) return null
    if (!force && currentUser.value) return currentUser.value

    try {
      const response = await apiStore.getAuthUser()
      currentUser.value = response.data.data || response.data
      return currentUser.value
    } catch (error) {
      console.warn('Falha ao obter utilizador autenticado:', error)
      await logout()
      return null
    }
  }

  const login = async (credentials) => {
    const responseToken = await apiStore.postLogin(credentials)
    const accessToken = responseToken.data.access_token || responseToken.data.token

    if (!accessToken) throw new Error('Token não recebido da API')

    token.value = accessToken
    localStorage.setItem('token', accessToken)
    axios.defaults.headers.common['Authorization'] = 'Bearer ' + accessToken

    // ✅ garantir user fresco (inclui coins_balance)
    await fetchCurrentUser(true)
    return currentUser.value
  }

  const register = async (formData) => {
    const response = await apiStore.postRegister(formData)
    const accessToken = response.data.access_token || response.data.token

    if (!accessToken) throw new Error('Token não recebido da API')

    token.value = accessToken
    localStorage.setItem('token', accessToken)
    axios.defaults.headers.common['Authorization'] = 'Bearer ' + accessToken

    // Se a API já devolve user ok, guarda-o, senão faz fetch
    currentUser.value = response.data.user || null
    if (!currentUser.value) await fetchCurrentUser(true)

    return currentUser.value
  }

  const updateProfile = async (formData) => {
    const response = await apiStore.postUpdateUser(formData)
    currentUser.value = response.data.data || response.data
    return currentUser.value
  }

  const deleteAccount = async (password) => {
    await apiStore.deleteUser(password)
    await logout()
  }

  const logout = async () => {
    try {
      await apiStore.postLogout()
    } catch (error) {
      console.warn('Falha ao fazer logout:', error)
    } finally {
      token.value = null
      currentUser.value = null
      localStorage.removeItem('token')
      delete axios.defaults.headers.common['Authorization']
    }
  }

  // ✅ mantém este nome porque tu já o usas no app
  // mas agora ele garante refresh do user se quiseres
  const loadUserFromStorage = async () => {
    // Antes: só corria se currentUser fosse null
    // Agora: chama fetchCurrentUser (sem force) para restaurar no F5
    await fetchCurrentUser(false)
  }

  return {
    token,
    currentUser,
    isLoggedIn,
    currentUserID,
    userPhotoUrl,
    login,
    register,
    updateProfile,
    deleteAccount,
    logout,
    loadUserFromStorage,
    fetchCurrentUser, // ✅ exporta para o Profile poder forçar refresh
  }
})
