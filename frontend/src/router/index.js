import HomePage from '@/pages/home/HomePage.vue'
import LoginPage from '@/pages/login/LoginPage.vue'
import LaravelPage from '@/pages/testing/LaravelPage.vue'
import WebsocketsPage from '@/pages/testing/WebsocketsPage.vue'
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth' // <--- 1. Importar a Store

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home', // É boa prática dar nomes às rotas
      component: HomePage,
    },
    {
      path: '/login',
      name: 'login',
      component: LoginPage,
    },
    {
      path: '/testing',
      // Adicionamos 'meta' para dizer que estas rotas exigem autenticação
      meta: { requiresAuth: true },
      children: [
        {
          path: 'laravel',
          component: LaravelPage,
        },
        {
          path: 'websockets',
          component: WebsocketsPage,
        },
      ],
    },
  ],
})

// --- GUARDA DE NAVEGAÇÃO ---
router.beforeEach((to, from, next) => {
  // IMPORTANTE: Chamar a store DENTRO da função, não lá em cima.
  // Isto evita o erro do Pinia que tinhas antes.
  const authStore = useAuthStore()

  // Se a rota precisa de Auth e o user NÃO está logado
  if (to.meta.requiresAuth && !authStore.isLoggedIn) {
    next('/login') // Manda para o login
  } else {
    next() // Deixa passar
  }
})

export default router
