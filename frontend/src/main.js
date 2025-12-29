import { createApp } from 'vue'
import { createPinia } from 'pinia'
//import { io } from 'socket.io-client'
import axios from 'axios'
import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth'

const apiDomain = import.meta.env.VITE_API_DOMAIN

// Para Socket.IO, o mais seguro é usar URL HTTP(S) (ele faz upgrade para websocket)
/*const wsBase = import.meta.env.VITE_WS_CONNECTION || 'http://localhost:8080'

// Se alguém meter ws:// por engano, converte para http:// (Socket.IO lida melhor assim)
const socketIoUrl = wsBase.replace(/^ws:/, 'http:').replace(/^wss:/, 'https:')

console.log('[main.js] api domain', apiDomain)
console.log('[main.js] ws connection', socketIoUrl)*/

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Axios
axios.defaults.baseURL = `${apiDomain}/api`
axios.defaults.withCredentials = true

// Socket.IO (correto)
/*const socket = io(import.meta.env.VITE_WS_CONNECTION, {
  transports: ["polling"], // <--- FORÇA APENAS POLLING POR AGORA
  withCredentials: true,
  reconnection: true,     // Tenta reconectar se falhar
});

// (opcional) logs úteis para debug
socket.on('connect', () => console.log('[socket] connected', socket.id))
socket.on('connect_error', (err) => console.error('[socket] connect_error', err.message))
*/

//app.provide('socket', socket)
app.provide('serverBaseURL', apiDomain)
app.provide('apiBaseURL', `${apiDomain}/api`)

const authStore = useAuthStore()
authStore.loadUserFromStorage().finally(() => {
  app.mount('#app')
})
