import HomePage from '@/pages/home/HomePage.vue'
import LoginPage from '@/pages/login/LoginPage.vue'
import LaravelPage from '@/pages/testing/LaravelPage.vue'
import WebsocketsPage from '@/pages/testing/WebsocketsPage.vue'
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import Register from '@/components/auth/RegisterUser.vue'
import Profile from '@/components/users/ProfileUser.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home', // É boa prática dar nomes às rotas
      //redirect: '/testing/laravel',
      component: HomePage,
    },
    {
      path: '/login',
      name: 'login',
      component: LoginPage,
    },
    {
      path: '/register',
      name: 'Register',
      component: Register,
    },
    {
      path: '/profile',
      name: 'Profile',
      component: Profile,
      // Proteção: só deixa entrar se estiver logado
      beforeEnter: (to, from, next) => {
        const authStore = useAuthStore()
        if (!authStore.isLoggedIn) next({ name: 'login' })
        else next()
      },
    },
    {
      path: '/testing',
      meta: { requiresAuth: false },
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
  // Isto evita o erro do Pinia que tinhamos antes.
  const authStore = useAuthStore()

  // Se a rota precisa de Auth e o user NÃO está logado
  if (to.meta.requiresAuth && !authStore.isLoggedIn) {
    next('/login') // Manda para o login
  } else {
    next() // Deixa passar
  }
})

export default router
