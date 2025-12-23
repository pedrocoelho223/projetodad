import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useAPIStore } from './api'
import axios from 'axios' // Precisamos disto para injetar o header

export const useAuthStore = defineStore('auth', () => {
  const apiStore = useAPIStore()

  // 1. Tenta recuperar o token do browser ao iniciar
  const token = ref(localStorage.getItem('token'))
  const currentUser = ref(null)

  // Configura o axios imediatamente se houver token guardado
  if (token.value) {
    axios.defaults.headers.common['Authorization'] = 'Bearer ' + token.value
  }

  // Verificar se está logado pelo Token é mais rápido e evita "flickering" na UI
  const isLoggedIn = computed(() => !!token.value)
  const currentUserID = computed(() => currentUser.value?.id)

  // URL photo
  const userPhotoUrl = computed(() => {
    if (!currentUser.value?.photo_avatar_filename) return null

    // Forçar também aqui o porto 8000
    return `http://localhost:8000/storage/photos/${currentUser.value.photo_avatar_filename}`
  })

  const login = async (credentials) => {
    // 1. Pedir Token
    const responseToken = await apiStore.postLogin(credentials)

    // NOTA: Confirma se o Laravel devolve 'access_token' ou apenas 'token'
    const accessToken = responseToken.data.access_token || responseToken.data.token

    if (accessToken) {
      // 2. Guardar Token e Configurar Axios
      token.value = accessToken
      localStorage.setItem('token', accessToken)
      axios.defaults.headers.common['Authorization'] = 'Bearer ' + accessToken

      // 3. Pedir Utilizador
      const responseUser = await apiStore.getAuthUser()
      currentUser.value = responseUser.data.data || responseUser.data
      return currentUser.value
    }
    throw new Error("Token não recebido da API")
  }

  // Registo com Login Automático
  const register = async (formData) => {
    const response = await apiStore.postRegister(formData)
    const accessToken = response.data.access_token || response.data.token

    if (accessToken) {
      token.value = accessToken
      localStorage.setItem('token', accessToken)
      axios.defaults.headers.common['Authorization'] = 'Bearer ' + accessToken

      // A API de registo no AuthController já devolve o user, aproveitamos
      currentUser.value = response.data.user
      return currentUser.value
    }
  }

  // Atualizar dados do utilizador
  const updateProfile = async (formData) => {
    const response = await apiStore.postUpdateUser(formData)
    // Atualizamos o user local com a resposta fresca da API
    currentUser.value = response.data.data || response.data
    return currentUser.value
  }

  // [NOVO] Apagar Conta
  const deleteAccount = async (password) => {
    await apiStore.deleteUser(password)
    await logout() // Limpa token e sessão
  }

  const logout = async () => {
    try {
      await apiStore.postLogout()
    } catch (error) {
      console.warn('Falha ao fazer logout:  ', error)
      // Ignora erro se o token já tiver expirado
    } finally {
      // Limpeza obrigatória
      token.value = null
      currentUser.value = null
      localStorage.removeItem('token')
      delete axios.defaults.headers.common['Authorization']
    }
  }

  // Chama isto no main.js ou App.vue para não perderes o user no F5
  const loadUserFromStorage = async () => {
    if (token.value && !currentUser.value) {
      try {
        const response = await apiStore.getAuthUser()
        currentUser.value = response.data.data || response.data
      } catch (error) {
        console.warn('Falha ao restaurar sessão:', error)
        logout() // Token inválido/expirado
      }
    }
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
    loadUserFromStorage
  }
})
