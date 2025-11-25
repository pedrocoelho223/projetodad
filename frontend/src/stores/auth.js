import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useAPIStore } from './api'
import { useSocketStore } from './socket'

export const useAuthStore = defineStore('auth', () => {
  const apiStore = useAPIStore()
  const socketStore = useSocketStore()

  const currentUser = ref(undefined)

  const isLoggedIn = computed(() => {
    return currentUser.value !== undefined
  })

  const currentUserID = computed(() => {
    return currentUser.value?.id
  })

  const login = async (credentials) => {
    await apiStore.postLogin(credentials)
    await getUser()
    socketStore.emitJoin(currentUser.value)
    return currentUser.value
  }

  const logout = async () => {
    await apiStore.postLogout()
    socketStore.emitLeave()
    currentUser.value = undefined
  }

  const getUser = async () => {
    const response = await apiStore.getAuthUser()
    currentUser.value = response.data
  }

  return {
    currentUser,
    currentUserID,
    isLoggedIn,
    login,
    logout,
    getUser,
  }
})
