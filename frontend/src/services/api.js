import axios from 'axios'

const api = axios.create({
  // ✅ Se existir VITE_API_URL usa (local/dev). Senão usa /api (kubernetes com ingress /api)
  baseURL: import.meta.env.VITE_API_URL || '/api',
  withCredentials: true
})

export default api
