import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { io } from 'socket.io-client'
import axios from 'axios'
import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth'

// Ler as variáveis de ambiente
const apiDomain = import.meta.env.VITE_API_DOMAIN
const wsConnection = import.meta.env.VITE_WS_CONNECTION

console.log('[main.js] api domain', apiDomain)
console.log('[main.js] ws connection', wsConnection)

const app = createApp(App)

app.use(createPinia())

// Configurar axios
axios.defaults.baseURL = `${apiDomain}/api`
axios.defaults.withCredentials = true;

app.provide('socket', io(wsConnection))

app.provide('serverBaseURL', apiDomain)
app.provide('apiBaseURL', `${apiDomain}/api`)

app.use(router)

const authStore = useAuthStore()

authStore.loadUserFromStorage().finally(() => {
    app.mount('#app')
})
