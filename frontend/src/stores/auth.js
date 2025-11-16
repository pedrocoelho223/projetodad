import { defineStore } from 'pinia';
import axios from 'axios';

export const api = axios.create({
  baseURL: 'http://127.0.0.1:8000/api' // /api incluído se o backend tiver prefixo
});


export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: null,
    loading: false,
    error: null
  }),
  actions: {
    async register(payload) {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.post('/register', payload);
        this.user = res.data.user;
        this.token = res.data.token || null;
        return res.data;
      } catch (err) {
        this.error = err.response?.data?.message || err.message;
        throw err;
      } finally {
        this.loading = false;
      }
    },

    async login(payload) {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.post('/login', payload);
        this.user = res.data.user;
        this.token = res.data.token;
        return res.data;
      } catch (err) {
        this.error = err.response?.data?.message || err.message;
        throw err;
      } finally {
        this.loading = false;
      }
    },

    logout() {
      this.user = null;
      this.token = null;
    },

    async deleteUser() {
      if (!this.token) {
        this.error = 'Não autenticado';
        return;
      }
      this.loading = true;
      this.error = null;
      try {
        await api.delete('/user', {
          headers: { Authorization: `Bearer ${this.token}` }
        });
        this.logout();
      } catch (err) {
        this.error = err.response?.data?.message || err.message;
        throw err;
      } finally {
        this.loading = false;
      }
    }
  }
});


