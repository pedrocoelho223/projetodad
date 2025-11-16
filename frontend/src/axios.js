// Ficheiro: axios.js
import axios from 'axios'

const api = axios.create({
  baseURL: 'http://127.0.0.1:8000/api',
  headers: {
    'Content-Type': 'application/json',
    // 'Accept': 'application/json', // opcional
  }
})

// *** ADICIONAR ESTE INTERCEPTOR ***
api.interceptors.request.use(
  (config) => {
    // Lê o token do localStorage em cada requisição
    const token = localStorage.getItem('token')

    if (token) {
      // Se o token existir, adiciona-o ao cabeçalho Authorization
      config.headers['Authorization'] = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    // Trata erros da requisição
    return Promise.reject(error)
  }
)
// **********************************

export default api
