import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useAPIStore } from './api'

const API_DOMAIN = import.meta.env.VITE_API_DOMAIN || 'http://localhost:8000'

export const useAuthStore = defineStore('auth', () => {
  const apiStore = useAPIStore()

  // token único (a store api já o usa; aqui só refletimos o mesmo valor)
  const token = ref(localStorage.getItem('token'))
  const currentUser = ref(null)

  const isLoggedIn = computed(() => !!token.value)
  const currentUserID = computed(() => currentUser.value?.id ?? null)

  // URL do avatar (usa sempre o host do API)
  const userPhotoUrl = computed(() => {
    const filename = currentUser.value?.photo_avatar_filename
    return filename
      ? `${API_DOMAIN}/storage/photos/${filename}`
      : `${API_DOMAIN}/storage/photos/anonymous.png`
  })

  //buscar utilizador atual sempre que precisares (força refresh quando quiseres)
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
    // apiStore.postLogin já guarda token e mete header no http
    const responseToken = await apiStore.postLogin(credentials)
    const accessToken = responseToken.data.access_token || responseToken.data.token

    if (!accessToken) throw new Error('Token não recebido da API')

    // manter espelho local
    token.value = accessToken

    await fetchCurrentUser(true)
    return currentUser.value
  }

  const register = async (formData) => {
    const response = await apiStore.postRegister(formData)
    const accessToken = response.data.access_token || response.data.token

    if (!accessToken) throw new Error('Token não recebido da API')

    token.value = accessToken

    // se a API vier com user, ok; senão fetch
    currentUser.value = response.data.user || null
    if (!currentUser.value) await fetchCurrentUser(true)

    return currentUser.value
  }

  const updateProfile = async (formData) => {
    await apiStore.postUpdateUser(formData)
    // força refresh para apanhar avatar novo e campos atualizados
    await fetchCurrentUser(true)
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
    }
  }

  const loadUserFromStorage = async () => {
    // usado no refresh/F5
    token.value = localStorage.getItem('token')
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
    fetchCurrentUser, // exporta para o Profile poder forçar refresh
  }
})
