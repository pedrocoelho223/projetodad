import { defineStore } from 'pinia'
import api from '../axios'

export const useUserStore = defineStore('user', {
  state: () => ({
    token: localStorage.getItem('token') || null,
    user: null
  }),
  actions: {
    setToken(token) {
      this.token = token
      localStorage.setItem('token', token)
    },
    clearToken() {
      this.token = null
      localStorage.removeItem('token')
    },
    async fetchUser() {
      if (!this.token) return
      try {
        const res = await api.get('/profile', {
          headers: { Authorization: `Bearer ${this.token}` }
        })
        this.user = res.data
      } catch (err) {
        console.error('Erro ao carregar user', err)
        this.clearToken()
      }
    }
  }
})
